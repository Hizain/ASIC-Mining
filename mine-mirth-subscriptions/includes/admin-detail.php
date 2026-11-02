<?php
/**
 * Admin: one subscription (refund, discount, extra charge, cancel, resume, emails) and one customer.
 *
 * @package MineMirthSubscriptions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check rights and the nonce for a subscription action; returns the subscription row.
 */
function mms_action_sub( string $action ): array {
	if ( ! current_user_can( mms_cap() ) || ! check_admin_referer( $action ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-subscriptions' ), 403 );
	}
	$sub = mms_get( 'subscriptions', 'id', absint( $_POST['sub'] ?? $_GET['sub'] ?? 0 ) );
	if ( ! $sub ) {
		mms_back( false, __( 'Subscription not found.', 'mine-mirth-subscriptions' ) );
	}
	return $sub;
}

function mms_refresh_sub( array $sub ): void {
	$s = mms_stripe( 'GET', '/v1/subscriptions/' . rawurlencode( $sub['stripe_id'] ) );
	if ( ! is_wp_error( $s ) ) {
		mms_sync_subscription( $s );
	}
}

/* ------------------------------------------------------------------ Actions */

add_action(
	'admin_post_mms_refund',
	static function (): void {
		$sub = mms_action_sub( 'mms_refund' );
		$inv = mms_get( 'invoices', 'id', absint( $_POST['invoice'] ?? 0 ) );
		if ( ! $inv || $inv['stripe_customer'] !== $sub['stripe_customer'] ) {
			mms_back( false, __( 'Choose an invoice to refund.', 'mine-mirth-subscriptions' ) );
		}
		$left   = (int) $inv['amount_paid'] - (int) $inv['amount_refunded'];
		$amount = mms_parse_amount( wp_unslash( $_POST['amount'] ?? '' ), (string) $inv['currency'] );
		$amount = $amount > 0 ? $amount : $left;
		if ( $amount <= 0 || $amount > $left ) {
			/* translators: %s: amount */
			mms_back( false, sprintf( __( 'The refund must be more than zero and at most %s (what is left on this invoice).', 'mine-mirth-subscriptions' ), mms_money( $left, (string) $inv['currency'] ) ) );
		}
		list( $pi, $charge ) = mms_invoice_payment( $inv );
		if ( '' === $pi && '' === $charge ) {
			mms_back( false, __( 'Stripe has no card payment for this invoice, so there is nothing to refund.', 'mine-mirth-subscriptions' ) );
		}
		$reason = sanitize_key( wp_unslash( $_POST['reason'] ?? '' ) );
		$params = array(
			'amount'   => $amount,
			'metadata' => array( 'invoice' => (string) $inv['stripe_id'], 'by' => wp_get_current_user()->user_login ),
		);
		if ( '' !== $pi ) {
			$params['payment_intent'] = $pi;
		} else {
			$params['charge'] = $charge;
		}
		if ( in_array( $reason, array( 'duplicate', 'fraudulent', 'requested_by_customer' ), true ) ) {
			$params['reason'] = $reason;
		}
		// Mark the refund as ours first: Stripe's webhook for it can arrive before the call returns.
		$mark = 'mms_local_refund_' . md5( $inv['stripe_id'] . '|' . ( (int) $inv['amount_refunded'] + $amount ) );
		set_transient( $mark, 1, DAY_IN_SECONDS );
		$r = mms_stripe( 'POST', '/v1/refunds', $params, 'refund-' . $inv['stripe_id'] . '-' . $amount . '-' . floor( time() / 60 ) );
		if ( is_wp_error( $r ) ) {
			delete_transient( $mark );
			mms_back( false, $r->get_error_message() );
		}
		global $wpdb;
		$wpdb->update( mms_table( 'invoices' ), array( 'amount_refunded' => (int) $inv['amount_refunded'] + $amount ), array( 'id' => (int) $inv['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		mms_log( 'refund', sprintf( 'Refunded %s on invoice %s.', mms_money( $amount, (string) $inv['currency'] ), $inv['number'] ?: $inv['stripe_id'] ), (string) $sub['stripe_id'] );
		mms_email_refund( (int) $inv['id'], $amount, sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ) );
		/* translators: %s: amount */
		mms_back( true, sprintf( __( 'Refunded %s. The customer was emailed.', 'mine-mirth-subscriptions' ), mms_money( $amount, (string) $inv['currency'] ) ) );
	}
);

add_action(
	'admin_post_mms_discount',
	static function (): void {
		$sub  = mms_action_sub( 'mms_discount' );
		$type = 'percent' === ( $_POST['type'] ?? '' ) ? 'percent' : 'amount';
		$desc = sanitize_text_field( wp_unslash( $_POST['description'] ?? '' ) );
		$desc = '' !== $desc ? $desc : __( 'Discount', 'mine-mirth-subscriptions' );
		if ( 'percent' === $type ) {
			$pct = (float) preg_replace( '/[^0-9.]/', '', (string) wp_unslash( $_POST['value'] ?? '' ) );
			if ( $pct <= 0 || $pct > 100 ) {
				mms_back( false, __( 'Enter a percentage between 1 and 100.', 'mine-mirth-subscriptions' ) );
			}
			$coupon = mms_stripe( 'POST', '/v1/coupons', array( 'percent_off' => $pct, 'duration' => 'once', 'name' => mb_substr( $desc, 0, 40 ), 'metadata' => array( 'subscription' => $sub['stripe_id'] ) ), 'coupon-' . $sub['stripe_id'] . '-' . $pct . '-' . floor( time() / 60 ) );
			if ( is_wp_error( $coupon ) ) {
				mms_back( false, $coupon->get_error_message() );
			}
			$r = mms_stripe( 'POST', '/v1/subscriptions/' . rawurlencode( $sub['stripe_id'] ), array( 'coupon' => (string) $coupon['id'] ) );
			if ( is_wp_error( $r ) ) {
				// Newer API versions take the coupon through "discounts".
				$r = mms_stripe( 'POST', '/v1/subscriptions/' . rawurlencode( $sub['stripe_id'] ), array( 'discounts' => array( array( 'coupon' => (string) $coupon['id'] ) ) ) );
			}
			if ( is_wp_error( $r ) ) {
				mms_back( false, $r->get_error_message() );
			}
			$text = rtrim( rtrim( number_format( $pct, 2, '.', '' ), '0' ), '.' ) . '%';
		} else {
			$amount = mms_parse_amount( wp_unslash( $_POST['value'] ?? '' ), (string) $sub['currency'] );
			if ( $amount <= 0 ) {
				mms_back( false, __( 'Enter the discount amount.', 'mine-mirth-subscriptions' ) );
			}
			// A negative line on the next renewal invoice.
			$r = mms_stripe( 'POST', '/v1/invoiceitems', array( 'customer' => $sub['stripe_customer'], 'subscription' => $sub['stripe_id'], 'amount' => -$amount, 'currency' => $sub['currency'], 'description' => $desc, 'metadata' => array( 'mms' => 'discount' ) ), 'disc-' . $sub['stripe_id'] . '-' . $amount . '-' . floor( time() / 60 ) );
			if ( is_wp_error( $r ) ) {
				mms_back( false, $r->get_error_message() );
			}
			$text = mms_money( $amount, (string) $sub['currency'] );
		}
		mms_log( 'discount', sprintf( 'Discount of %s on the next billing: %s.', $text, $desc ), (string) $sub['stripe_id'] );
		if ( ! empty( $_POST['notify'] ) ) {
			mms_email_adjustment( (int) $sub['id'], 'discount', $text, $desc );
		}
		/* translators: %s: discount */
		mms_back( true, sprintf( __( 'A discount of %s will apply to the next billing.', 'mine-mirth-subscriptions' ), $text ) );
	}
);

add_action(
	'admin_post_mms_charge',
	static function (): void {
		$sub    = mms_action_sub( 'mms_charge' );
		$amount = mms_parse_amount( wp_unslash( $_POST['amount'] ?? '' ), (string) $sub['currency'] );
		$desc   = sanitize_text_field( wp_unslash( $_POST['description'] ?? '' ) );
		$now    = 'now' === ( $_POST['when'] ?? '' );
		if ( $amount <= 0 || '' === $desc ) {
			mms_back( false, __( 'Enter the amount and what the charge is for (for example "Hashboard repair").', 'mine-mirth-subscriptions' ) );
		}
		$text = mms_money( $amount, (string) $sub['currency'] );
		if ( ! $now ) {
			$r = mms_stripe( 'POST', '/v1/invoiceitems', array( 'customer' => $sub['stripe_customer'], 'subscription' => $sub['stripe_id'], 'amount' => $amount, 'currency' => $sub['currency'], 'description' => $desc, 'metadata' => array( 'mms' => 'charge' ) ), 'chg-' . $sub['stripe_id'] . '-' . $amount . '-' . md5( $desc ) . '-' . floor( time() / 60 ) );
			if ( is_wp_error( $r ) ) {
				mms_back( false, $r->get_error_message() );
			}
			mms_log( 'charge', sprintf( 'Charge of %s added to the next billing: %s.', $text, $desc ), (string) $sub['stripe_id'] );
			if ( ! empty( $_POST['notify'] ) ) {
				mms_email_adjustment( (int) $sub['id'], 'charge_next', $text, $desc );
			}
			/* translators: %s: amount */
			mms_back( true, sprintf( __( '%s will be added to the next billing.', 'mine-mirth-subscriptions' ), $text ) );
		}
		// Charge now: a separate invoice paid with the subscription's card.
		$key = 'now-' . $sub['stripe_id'] . '-' . $amount . '-' . md5( $desc ) . '-' . floor( time() / 60 );
		$inv = mms_stripe(
			'POST',
			'/v1/invoices',
			array(
				'customer'                       => $sub['stripe_customer'],
				'collection_method'              => 'charge_automatically',
				'auto_advance'                   => false,
				'pending_invoice_items_behavior' => 'exclude',
				'description'                    => $desc,
				'default_payment_method'         => '' !== $sub['default_pm'] ? $sub['default_pm'] : null,
				'metadata'                       => array( 'subscription' => $sub['stripe_id'], 'mms' => 'charge_now' ),
			),
			$key . '-inv'
		);
		if ( is_wp_error( $inv ) ) {
			mms_back( false, $inv->get_error_message() );
		}
		$item = mms_stripe( 'POST', '/v1/invoiceitems', array( 'customer' => $sub['stripe_customer'], 'invoice' => (string) $inv['id'], 'amount' => $amount, 'currency' => $sub['currency'], 'description' => $desc ), $key . '-item' );
		if ( is_wp_error( $item ) ) {
			mms_stripe( 'DELETE', '/v1/invoices/' . rawurlencode( (string) $inv['id'] ) );
			mms_back( false, $item->get_error_message() );
		}
		// The webhook for this invoice can arrive before the calls below return.
		set_transient( 'mms_quiet_receipt_' . md5( (string) $inv['id'] ), 1, DAY_IN_SECONDS );
		$fin = mms_stripe( 'POST', '/v1/invoices/' . rawurlencode( (string) $inv['id'] ) . '/finalize', array( 'auto_advance' => false ) );
		if ( is_wp_error( $fin ) ) {
			mms_back( false, $fin->get_error_message() );
		}
		$paid = mms_stripe( 'POST', '/v1/invoices/' . rawurlencode( (string) $inv['id'] ) . '/pay', '' !== $sub['default_pm'] ? array( 'payment_method' => $sub['default_pm'] ) : array(), $key . '-pay' );
		$id   = mms_sync_invoice( is_wp_error( $paid ) ? $fin : $paid );
		mms_set_data( 'invoices', $id, array( 'receipt_sent' => time() ) ); // The charge email below replaces the receipt.
		if ( is_wp_error( $paid ) ) {
			mms_log( 'charge', sprintf( 'Charge of %s (%s) could not be collected: %s', $text, $desc, $paid->get_error_message() ), (string) $sub['stripe_id'], 'warn' );
			if ( ! empty( $_POST['notify'] ) ) {
				mms_email_adjustment( (int) $sub['id'], 'charge_next', $text, $desc, (string) ( $fin['hosted_invoice_url'] ?? '' ) );
			}
			/* translators: %s: error */
			mms_back( false, sprintf( __( 'The invoice was created but the card was declined: %s The customer can pay it from the invoice link in their email.', 'mine-mirth-subscriptions' ), $paid->get_error_message() ) );
		}
		mms_log( 'charge', sprintf( 'Charged %s now: %s.', $text, $desc ), (string) $sub['stripe_id'] );
		if ( ! empty( $_POST['notify'] ) ) {
			mms_email_adjustment( (int) $sub['id'], 'charge_now', $text, $desc, mms_invoice_link( (string) $paid['id'] ) );
		}
		/* translators: %s: amount */
		mms_back( true, sprintf( __( 'Charged %s to the customer’s card.', 'mine-mirth-subscriptions' ), $text ) );
	}
);

add_action(
	'admin_post_mms_remove_item',
	static function (): void {
		$sub  = mms_action_sub( 'mms_remove_item' );
		$item = sanitize_text_field( wp_unslash( $_POST['item'] ?? '' ) );
		$r    = mms_stripe( 'DELETE', '/v1/invoiceitems/' . rawurlencode( $item ) );
		if ( is_wp_error( $r ) ) {
			mms_back( false, $r->get_error_message() );
		}
		mms_log( 'adjustment', sprintf( 'Removed a pending item (%s) from the next billing.', $item ), (string) $sub['stripe_id'] );
		mms_back( true, __( 'Removed from the next billing.', 'mine-mirth-subscriptions' ) );
	}
);

add_action(
	'admin_post_mms_remove_discount',
	static function (): void {
		$sub = mms_action_sub( 'mms_remove_discount' );
		$r   = mms_stripe( 'DELETE', '/v1/subscriptions/' . rawurlencode( $sub['stripe_id'] ) . '/discount' );
		if ( is_wp_error( $r ) ) {
			mms_back( false, $r->get_error_message() );
		}
		mms_log( 'discount', 'Removed the percentage discount from the next billing.', (string) $sub['stripe_id'] );
		mms_back( true, __( 'Discount removed from the next billing.', 'mine-mirth-subscriptions' ) );
	}
);

add_action(
	'admin_post_mms_cancel',
	static function (): void {
		$sub = mms_action_sub( 'mms_cancel' );
		if ( 'now' === ( $_POST['when'] ?? '' ) ) {
			$r = mms_stripe( 'DELETE', '/v1/subscriptions/' . rawurlencode( $sub['stripe_id'] ) );
		} else {
			$r = mms_stripe( 'POST', '/v1/subscriptions/' . rawurlencode( $sub['stripe_id'] ), array( 'cancel_at_period_end' => true ) );
		}
		if ( is_wp_error( $r ) ) {
			mms_back( false, $r->get_error_message() );
		}
		mms_sync_subscription( $r );
		mms_log( 'cancel', 'now' === ( $_POST['when'] ?? '' ) ? 'Subscription cancelled now.' : 'Subscription set to end at the end of the period.', (string) $sub['stripe_id'] );
		mms_back( true, 'now' === ( $_POST['when'] ?? '' ) ? __( 'Subscription cancelled.', 'mine-mirth-subscriptions' ) : __( 'The subscription ends at the end of the current period. No further billing.', 'mine-mirth-subscriptions' ) );
	}
);

add_action(
	'admin_post_mms_resume',
	static function (): void {
		$sub = mms_action_sub( 'mms_resume' );
		$r   = mms_stripe( 'POST', '/v1/subscriptions/' . rawurlencode( $sub['stripe_id'] ), array( 'cancel_at_period_end' => false ) );
		if ( is_wp_error( $r ) ) {
			mms_back( false, $r->get_error_message() );
		}
		mms_sync_subscription( $r );
		mms_log( 'resume', 'Cancellation undone: the subscription keeps renewing.', (string) $sub['stripe_id'] );
		mms_back( true, __( 'The subscription will keep renewing.', 'mine-mirth-subscriptions' ) );
	}
);

add_action(
	'admin_post_mms_email',
	static function (): void {
		$sub  = mms_action_sub( 'mms_email' );
		$kind = sanitize_key( wp_unslash( $_POST['kind'] ?? '' ) );
		$ok   = false;
		if ( 'welcome' === $kind ) {
			$ok = mms_email_welcome( (int) $sub['id'] );
		} elseif ( 'reminder' === $kind ) {
			$ok = mms_email_reminder( (int) $sub['id'] );
		} elseif ( 'receipt' === $kind ) {
			$ok = mms_email_receipt( absint( $_POST['invoice'] ?? 0 ) );
		}
		mms_back( $ok, $ok ? __( 'Email sent.', 'mine-mirth-subscriptions' ) : __( 'The email could not be sent (check that this email type is switched on in Settings and your site can send email).', 'mine-mirth-subscriptions' ) );
	}
);

add_action(
	'admin_post_mms_customer',
	static function (): void {
		if ( ! current_user_can( mms_cap() ) || ! check_admin_referer( 'mms_customer' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-subscriptions' ), 403 );
		}
		$c = mms_get( 'customers', 'id', absint( $_POST['customer'] ?? 0 ) );
		if ( ! $c ) {
			mms_back( false, __( 'Customer not found.', 'mine-mirth-subscriptions' ) );
		}
		$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$notes = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
		$r     = mms_stripe( 'POST', '/v1/customers/' . rawurlencode( $c['stripe_id'] ), array( 'name' => $name, 'phone' => $phone ) );
		if ( is_wp_error( $r ) ) {
			mms_back( false, $r->get_error_message() );
		}
		global $wpdb;
		$wpdb->update( mms_table( 'customers' ), array( 'name' => $name, 'phone' => $phone, 'notes' => $notes ), array( 'id' => (int) $c['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		mms_log( 'customer', sprintf( 'Details of %s updated.', $c['client_no'] ), (string) $c['stripe_id'] );
		mms_back( true, __( 'Customer saved (in Stripe too).', 'mine-mirth-subscriptions' ) );
	}
);

/* ------------------------------------------------------------------ Subscription page */

function mms_sub_page(): void {
	global $wpdb;
	if ( ! current_user_can( mms_cap() ) ) {
		return;
	}
	$sub = mms_get( 'subscriptions', 'id', absint( $_GET['id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $sub ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'Subscription not found', 'mine-mirth-subscriptions' ) . '</h1></div>';
		return;
	}
	$cust     = mms_get( 'customers', 'id', (int) $sub['customer_id'] );
	$invoices = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mms_table( 'invoices' ) . ' WHERE subscription = %s OR ( customer_id = %d AND subscription = \'\' ) ORDER BY created DESC', $sub['stripe_id'], (int) $sub['customer_id'] ), ARRAY_A ); // phpcs:ignore WordPress.DB
	$pending  = mms_connected() ? mms_stripe( 'GET', '/v1/invoiceitems', array( 'customer' => $sub['stripe_customer'], 'pending' => true, 'limit' => 50 ) ) : array();
	$pending  = is_wp_error( $pending ) ? array() : array_values( array_filter( (array) ( $pending['data'] ?? array() ), static fn( $i ) => '' === mms_id( $i['subscription'] ?? '' ) || mms_id( $i['subscription'] ?? '' ) === $sub['stripe_id'] ) );
	$coupon   = null;
	if ( mms_connected() && in_array( $sub['status'], array( 'active', 'trialing', 'past_due', 'unpaid' ), true ) ) {
		$remote = mms_stripe( 'GET', '/v1/subscriptions/' . rawurlencode( (string) $sub['stripe_id'] ) );
		if ( ! is_wp_error( $remote ) && ! empty( $remote['discount']['coupon'] ) ) {
			$coupon = (array) $remote['discount']['coupon'];
		}
	}
	$live     = in_array( $sub['status'], array( 'active', 'trialing', 'past_due', 'unpaid', 'paused' ), true );
	$cur      = (string) $sub['currency'];
	$sym      = trim( preg_replace( '/[\d.,\s-]/', '', mms_money( 0, $cur ) ) );
	$logs     = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mms_table( 'log' ) . ' WHERE object IN (%s, %s) ORDER BY id DESC LIMIT 30', $sub['stripe_id'], $sub['stripe_customer'] ), ARRAY_A ); // phpcs:ignore WordPress.DB
	$refundable = array_filter( (array) $invoices, static fn( $i ) => 'paid' === $i['status'] && (int) $i['amount_paid'] > (int) $i['amount_refunded'] );
	$form       = static function ( string $action, string $inner, string $confirm = '' ) use ( $sub ): string {
		return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"' . ( $confirm ? ' data-mms-confirm="' . esc_attr( $confirm ) . '"' : '' ) . '><input type="hidden" name="action" value="' . esc_attr( $action ) . '"><input type="hidden" name="sub" value="' . (int) $sub['id'] . '">' . wp_nonce_field( $action, '_wpnonce', true, false ) . $inner . '</form>';
	};
	?>
	<div class="wrap mms-admin mms-detail">
		<?php mms_admin_header( __( 'Subscription', 'mine-mirth-subscriptions' ), '<a class="button" href="' . esc_url( mms_dashboard_url( 'subscriptions', $sub['stripe_id'] ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open in Stripe', 'mine-mirth-subscriptions' ) . ' ↗</a>' ); ?>
		<div class="mms-hero">
			<?php $img = mms_plan_image( (int) $sub['plan_id'], 'medium_large' ); ?>
			<div class="mms-hero__img"><?php echo $img ? '<img src="' . esc_url( $img ) . '" alt="">' : '<span class="mms-thumb mms-thumb--empty"></span>'; ?></div>
			<div class="mms-hero__text">
				<p class="mms-eyebrow"><?php echo $cust ? '<span class="mms-client">' . esc_html( $cust['client_no'] ) . '</span> ' . esc_html( $cust['name'] ?: $cust['email'] ) : ''; ?></p>
				<h2><?php echo esc_html( $sub['plan_name'] ?: __( 'Subscription', 'mine-mirth-subscriptions' ) ); ?><?php echo (int) $sub['quantity'] > 1 ? ' <small>× ' . (int) $sub['quantity'] . '</small>' : ''; ?></h2>
				<p class="mms-hero__meta"><?php echo mms_pill( (string) $sub['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <strong><?php echo esc_html( mms_money( (int) $sub['amount'] * (int) $sub['quantity'], $cur ) ); ?></strong> <span class="mms-muted"><?php echo esc_html( mms_interval_label( $sub['interval_unit'], (int) $sub['interval_count'] ) ); ?></span></p>
				<dl class="mms-dl mms-dl--row">
					<div><dt><?php esc_html_e( 'Started', 'mine-mirth-subscriptions' ); ?></dt><dd><?php echo esc_html( mms_date( (int) $sub['started'] ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Current period', 'mine-mirth-subscriptions' ); ?></dt><dd><?php echo esc_html( mms_date( (int) $sub['period_start'] ) . ' to ' . mms_date( (int) $sub['period_end'] ) ); ?></dd></div>
					<div><dt><?php echo (int) $sub['cancel_at_period_end'] ? esc_html__( 'Ends on', 'mine-mirth-subscriptions' ) : esc_html__( 'Next billing', 'mine-mirth-subscriptions' ); ?></dt><dd><?php echo $live ? esc_html( mms_date( (int) $sub['period_end'] ) ) : '—'; ?></dd></div>
				</dl>
			</div>
		</div>

		<div class="mms-layout">
			<div class="mms-main">
				<section class="mms-card">
					<h2><?php esc_html_e( 'Invoices', 'mine-mirth-subscriptions' ); ?></h2>
					<table class="widefat mms-table mms-table--flat">
						<thead><tr><th><?php esc_html_e( 'Invoice', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'Date', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'Status', 'mine-mirth-subscriptions' ); ?></th><th class="num"><?php esc_html_e( 'Total', 'mine-mirth-subscriptions' ); ?></th><th class="num"><?php esc_html_e( 'Refunded', 'mine-mirth-subscriptions' ); ?></th><th></th></tr></thead>
						<tbody>
						<?php if ( ! $invoices ) : ?>
							<tr><td colspan="6" class="mms-empty-row"><?php esc_html_e( 'No invoices yet.', 'mine-mirth-subscriptions' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( (array) $invoices as $i ) : ?>
							<tr>
								<td><strong class="mms-mono"><?php echo esc_html( $i['number'] ?: $i['stripe_id'] ); ?></strong><br><small class="mms-muted"><?php echo esc_html( ucfirst( str_replace( '_', ' ', (string) $i['billing_reason'] ) ) ); ?></small></td>
								<td><?php echo esc_html( mms_date( (int) $i['created'] ) ); ?></td>
								<td><?php echo mms_pill( mms_invoice_status( $i ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td class="num"><strong><?php echo esc_html( mms_money( (int) $i['total'], $i['currency'] ) ); ?></strong></td>
								<td class="num"><?php echo (int) $i['amount_refunded'] ? esc_html( mms_money( (int) $i['amount_refunded'], $i['currency'] ) ) : '—'; ?></td>
								<td class="mms-nowrap">
									<a class="button button-small" href="<?php echo esc_url( mms_pdf_url( (int) $i['id'] ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'PDF', 'mine-mirth-subscriptions' ); ?></a>
									<a class="button button-small" href="<?php echo esc_url( mms_pdf_url( (int) $i['id'], true ) ); ?>" title="<?php esc_attr_e( 'Download', 'mine-mirth-subscriptions' ); ?>">↓</a>
									<?php if ( 'paid' === $i['status'] ) : ?>
										<?php echo $form( 'mms_email', '<input type="hidden" name="kind" value="receipt"><input type="hidden" name="invoice" value="' . (int) $i['id'] . '"><button class="button button-small">' . esc_html__( 'Email receipt', 'mine-mirth-subscriptions' ) . '</button>' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<?php elseif ( $i['hosted_url'] ) : ?>
										<a class="button button-small" href="<?php echo esc_url( (string) $i['hosted_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Pay link', 'mine-mirth-subscriptions' ); ?> ↗</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</section>

				<section class="mms-card">
					<h2><?php esc_html_e( 'On the next billing', 'mine-mirth-subscriptions' ); ?></h2>
					<?php if ( $pending || $coupon ) : ?>
						<ul class="mms-items">
							<?php if ( $coupon ) : ?>
								<li>
									<span><?php echo esc_html( (string) ( $coupon['name'] ?? __( 'Discount', 'mine-mirth-subscriptions' ) ) ); ?></span>
									<strong class="mms-ok"><?php echo esc_html( ! empty( $coupon['percent_off'] ) ? '-' . rtrim( rtrim( number_format( (float) $coupon['percent_off'], 2, '.', '' ), '0' ), '.' ) . '%' : '-' . mms_money( (int) ( $coupon['amount_off'] ?? 0 ), (string) ( $coupon['currency'] ?? $sub['currency'] ) ) ); ?></strong>
									<?php echo $form( 'mms_remove_discount', '<button class="button-link mms-danger">' . esc_html__( 'Remove', 'mine-mirth-subscriptions' ) . '</button>', __( 'Remove this discount from the next billing?', 'mine-mirth-subscriptions' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</li>
							<?php endif; ?>
							<?php foreach ( $pending as $it ) : ?>
								<li>
									<span><?php echo esc_html( (string) ( $it['description'] ?? '' ) ); ?></span>
									<strong class="<?php echo (int) $it['amount'] < 0 ? 'mms-ok' : ''; ?>"><?php echo esc_html( mms_money( (int) $it['amount'], (string) $it['currency'] ) ); ?></strong>
									<?php echo $form( 'mms_remove_item', '<input type="hidden" name="item" value="' . esc_attr( (string) $it['id'] ) . '"><button class="button-link mms-danger">' . esc_html__( 'Remove', 'mine-mirth-subscriptions' ) . '</button>', __( 'Remove this from the next billing?', 'mine-mirth-subscriptions' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="mms-muted"><?php esc_html_e( 'Nothing extra: the next billing is the plan price. Discounts and charges you add below appear here until they are billed.', 'mine-mirth-subscriptions' ); ?></p>
					<?php endif; ?>
				</section>

				<section class="mms-card">
					<h2><?php esc_html_e( 'History', 'mine-mirth-subscriptions' ); ?></h2>
					<ul class="mms-history">
						<?php foreach ( (array) $logs as $l ) : ?>
							<li class="mms-log--<?php echo esc_attr( $l['level'] ); ?>"><span><?php echo esc_html( mms_date( (int) strtotime( $l['time'] . ' UTC' ), true ) ); ?></span><?php echo esc_html( (string) $l['message'] ); ?></li>
						<?php endforeach; ?>
						<?php if ( ! $logs ) : ?>
							<li><?php esc_html_e( 'Changes and payments are listed here.', 'mine-mirth-subscriptions' ); ?></li>
						<?php endif; ?>
					</ul>
				</section>
			</div>

			<aside class="mms-side">
				<?php if ( $cust ) : ?>
					<section class="mms-card">
						<h2><?php esc_html_e( 'Client', 'mine-mirth-subscriptions' ); ?></h2>
						<p class="mms-client mms-client--big"><?php echo esc_html( $cust['client_no'] ); ?></p>
						<p><strong><?php echo esc_html( $cust['name'] ?: '—' ); ?></strong><br><a href="mailto:<?php echo esc_attr( $cust['email'] ); ?>"><?php echo esc_html( $cust['email'] ); ?></a><?php echo $cust['phone'] ? '<br>' . esc_html( $cust['phone'] ) : ''; ?></p>
						<p class="mms-btns"><a class="button" href="<?php echo esc_url( mms_customer_url( (int) $cust['id'] ) ); ?>"><?php esc_html_e( 'Client page', 'mine-mirth-subscriptions' ); ?></a> <button type="button" class="button" data-mms-copy="<?php echo esc_attr( mms_portal_link( (string) $cust['stripe_id'] ) ); ?>"><?php esc_html_e( 'Copy billing link', 'mine-mirth-subscriptions' ); ?></button></p>
					</section>
				<?php endif; ?>

				<section class="mms-card" id="mms-refund">
					<h2><?php esc_html_e( 'Refund', 'mine-mirth-subscriptions' ); ?></h2>
					<?php if ( $refundable ) : ?>
						<?php
						$opts = '';
						foreach ( $refundable as $i ) {
							$left  = (int) $i['amount_paid'] - (int) $i['amount_refunded'];
							$opts .= '<option value="' . (int) $i['id'] . '" data-left="' . esc_attr( number_format( mms_from_minor( $left, $i['currency'] ), 2, '.', '' ) ) . '">' . esc_html( ( $i['number'] ?: $i['stripe_id'] ) . ' · ' . mms_date( (int) $i['created'] ) . ' · ' . sprintf( /* translators: %s: amount */ __( '%s refundable', 'mine-mirth-subscriptions' ), mms_money( $left, $i['currency'] ) ) ) . '</option>';
						}
						echo $form( // phpcs:ignore WordPress.Security.EscapeOutput
							'mms_refund',
							'<label class="mms-f"><span>' . esc_html__( 'Invoice', 'mine-mirth-subscriptions' ) . '</span><select name="invoice" data-mms-refund-invoice>' . $opts . '</select></label>'
							. '<label class="mms-f"><span>' . esc_html__( 'Amount', 'mine-mirth-subscriptions' ) . ' <i>' . esc_html__( 'empty = everything left', 'mine-mirth-subscriptions' ) . '</i></span><span class="mms-money"><b>' . esc_html( $sym ) . '</b><input name="amount" inputmode="decimal" placeholder="0.00" data-mms-refund-amount></span></label>'
							. '<label class="mms-f"><span>' . esc_html__( 'Reason', 'mine-mirth-subscriptions' ) . '</span><select name="reason"><option value="requested_by_customer">' . esc_html__( 'Customer asked', 'mine-mirth-subscriptions' ) . '</option><option value="duplicate">' . esc_html__( 'Duplicate payment', 'mine-mirth-subscriptions' ) . '</option><option value="">' . esc_html__( 'Other', 'mine-mirth-subscriptions' ) . '</option></select></label>'
							. '<label class="mms-f"><span>' . esc_html__( 'Note to the customer', 'mine-mirth-subscriptions' ) . ' <i>' . esc_html__( 'optional', 'mine-mirth-subscriptions' ) . '</i></span><textarea name="note" rows="2"></textarea></label>'
							. '<button class="button button-primary">' . esc_html__( 'Refund', 'mine-mirth-subscriptions' ) . '</button>',
							__( 'Refund this amount to the customer’s card? This can not be undone.', 'mine-mirth-subscriptions' )
						);
						?>
					<?php else : ?>
						<p class="mms-muted"><?php esc_html_e( 'No paid invoice to refund.', 'mine-mirth-subscriptions' ); ?></p>
					<?php endif; ?>
				</section>

				<?php if ( $live ) : ?>
					<section class="mms-card">
						<h2><?php esc_html_e( 'Discount on the next billing', 'mine-mirth-subscriptions' ); ?></h2>
						<?php
						echo $form( // phpcs:ignore WordPress.Security.EscapeOutput
							'mms_discount',
							'<div class="mms-row"><label class="mms-f mms-f--short"><span>' . esc_html__( 'Type', 'mine-mirth-subscriptions' ) . '</span><select name="type"><option value="amount">' . esc_html( $sym ) . ' ' . esc_html__( 'amount', 'mine-mirth-subscriptions' ) . '</option><option value="percent">% ' . esc_html__( 'percent', 'mine-mirth-subscriptions' ) . '</option></select></label>'
							. '<label class="mms-f"><span>' . esc_html__( 'Value', 'mine-mirth-subscriptions' ) . '</span><input name="value" inputmode="decimal" placeholder="25" required></label></div>'
							. '<label class="mms-f"><span>' . esc_html__( 'Shown to the customer as', 'mine-mirth-subscriptions' ) . '</span><input name="description" placeholder="' . esc_attr__( 'Downtime credit for March', 'mine-mirth-subscriptions' ) . '"></label>'
							. '<label class="mms-check"><input type="checkbox" name="notify" value="1" checked> ' . esc_html__( 'Email the customer', 'mine-mirth-subscriptions' ) . '</label>'
							. '<button class="button button-primary">' . esc_html__( 'Add discount', 'mine-mirth-subscriptions' ) . '</button>'
						);
						?>
					</section>

					<section class="mms-card">
						<h2><?php esc_html_e( 'Extra charge', 'mine-mirth-subscriptions' ); ?></h2>
						<?php
						echo $form( // phpcs:ignore WordPress.Security.EscapeOutput
							'mms_charge',
							'<label class="mms-f"><span>' . esc_html__( 'What for', 'mine-mirth-subscriptions' ) . '</span><input name="description" required placeholder="' . esc_attr__( 'Hashboard repair, fan replacement…', 'mine-mirth-subscriptions' ) . '"></label>'
							. '<label class="mms-f"><span>' . esc_html__( 'Amount', 'mine-mirth-subscriptions' ) . '</span><span class="mms-money"><b>' . esc_html( $sym ) . '</b><input name="amount" inputmode="decimal" required placeholder="0.00"></span></label>'
							. '<fieldset class="mms-f"><label class="mms-check"><input type="radio" name="when" value="next" checked> ' . esc_html__( 'Add to the next billing', 'mine-mirth-subscriptions' ) . '</label><label class="mms-check"><input type="radio" name="when" value="now"> ' . esc_html__( 'Charge the card now', 'mine-mirth-subscriptions' ) . '</label></fieldset>'
							. '<label class="mms-check"><input type="checkbox" name="notify" value="1" checked> ' . esc_html__( 'Email the customer', 'mine-mirth-subscriptions' ) . '</label>'
							. '<button class="button button-primary">' . esc_html__( 'Add charge', 'mine-mirth-subscriptions' ) . '</button>',
							__( 'Add this charge for the customer?', 'mine-mirth-subscriptions' )
						);
						?>
					</section>

					<section class="mms-card">
						<h2><?php esc_html_e( 'Subscription', 'mine-mirth-subscriptions' ); ?></h2>
						<div class="mms-btns mms-btns--stack">
							<?php
							if ( (int) $sub['cancel_at_period_end'] ) {
								echo $form( 'mms_resume', '<button class="button">' . esc_html__( 'Keep renewing (undo cancel)', 'mine-mirth-subscriptions' ) . '</button>' ); // phpcs:ignore WordPress.Security.EscapeOutput
							} else {
								echo $form( 'mms_cancel', '<input type="hidden" name="when" value="period_end"><button class="button">' . esc_html__( 'Cancel at period end', 'mine-mirth-subscriptions' ) . '</button>', __( 'End this subscription when the current period is over? The customer is not billed again.', 'mine-mirth-subscriptions' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
							}
							echo $form( 'mms_cancel', '<input type="hidden" name="when" value="now"><button class="button mms-danger">' . esc_html__( 'Cancel now', 'mine-mirth-subscriptions' ) . '</button>', __( 'Cancel this subscription immediately? Use Refund separately if money should go back.', 'mine-mirth-subscriptions' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
							echo $form( 'mms_email', '<input type="hidden" name="kind" value="reminder"><button class="button">' . esc_html__( 'Send billing reminder now', 'mine-mirth-subscriptions' ) . '</button>' ); // phpcs:ignore WordPress.Security.EscapeOutput
							echo $form( 'mms_email', '<input type="hidden" name="kind" value="welcome"><button class="button">' . esc_html__( 'Re-send welcome email', 'mine-mirth-subscriptions' ) . '</button>' ); // phpcs:ignore WordPress.Security.EscapeOutput
							?>
						</div>
					</section>
				<?php endif; ?>
			</aside>
		</div>
	</div>
	<?php
}

/* ------------------------------------------------------------------ Customer page */

function mms_customer_page(): void {
	global $wpdb;
	if ( ! current_user_can( mms_cap() ) ) {
		return;
	}
	$c = mms_get( 'customers', 'id', absint( $_GET['id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $c ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'Customer not found', 'mine-mirth-subscriptions' ) . '</h1></div>';
		return;
	}
	// Everything under this client number (a returning client can have more than one Stripe customer).
	$ids      = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . mms_table( 'customers' ) . ' WHERE client_no = %s AND mode = %s', $c['client_no'], $c['mode'] ) ) ); // phpcs:ignore WordPress.DB
	$ids      = implode( ',', $ids ? $ids : array( (int) $c['id'] ) );
	$subs     = $wpdb->get_results( 'SELECT * FROM ' . mms_table( 'subscriptions' ) . " WHERE customer_id IN ({$ids}) ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB
	$invoices = $wpdb->get_results( 'SELECT * FROM ' . mms_table( 'invoices' ) . " WHERE customer_id IN ({$ids}) ORDER BY created DESC LIMIT 50", ARRAY_A ); // phpcs:ignore WordPress.DB
	$address  = mms_json( $c['address'] );
	$addr     = implode( ', ', array_filter( array( $address['line1'] ?? '', $address['line2'] ?? '', $address['city'] ?? '', $address['state'] ?? '', $address['postal_code'] ?? '', $address['country'] ?? '' ) ) );
	?>
	<div class="wrap mms-admin mms-detail">
		<?php mms_admin_header( __( 'Client', 'mine-mirth-subscriptions' ), '<a class="button" href="' . esc_url( mms_dashboard_url( 'customers', $c['stripe_id'] ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open in Stripe', 'mine-mirth-subscriptions' ) . ' ↗</a>' ); ?>
		<div class="mms-layout">
			<div class="mms-main">
				<section class="mms-card">
					<p class="mms-client mms-client--huge"><?php echo esc_html( $c['client_no'] ); ?></p>
					<h2 class="mms-big"><?php echo esc_html( $c['name'] ?: $c['email'] ); ?></h2>
					<p class="mms-muted"><?php echo esc_html( implode( ' · ', array_filter( array( $c['email'], $c['phone'], $addr ) ) ) ); ?></p>
				</section>
				<section class="mms-card">
					<h2><?php esc_html_e( 'Subscriptions', 'mine-mirth-subscriptions' ); ?></h2>
					<div class="mms-subcards">
						<?php foreach ( (array) $subs as $s ) : ?>
							<a class="mms-subcard" href="<?php echo esc_url( mms_sub_url( (int) $s['id'] ) ); ?>">
								<?php echo mms_plan_thumb( (int) $s['plan_id'], 'mms-thumb--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span><strong><?php echo esc_html( $s['plan_name'] . ( (int) $s['quantity'] > 1 ? ' × ' . (int) $s['quantity'] : '' ) ); ?></strong><small><?php echo esc_html( mms_money( (int) $s['amount'] * (int) $s['quantity'], $s['currency'] ) . ' ' . mms_interval_label( $s['interval_unit'], (int) $s['interval_count'] ) ); ?></small><?php echo mms_pill( (string) $s['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							</a>
						<?php endforeach; ?>
						<?php if ( ! $subs ) : ?>
							<p class="mms-muted"><?php esc_html_e( 'No subscriptions.', 'mine-mirth-subscriptions' ); ?></p>
						<?php endif; ?>
					</div>
				</section>
				<section class="mms-card">
					<h2><?php esc_html_e( 'Invoices', 'mine-mirth-subscriptions' ); ?></h2>
					<table class="widefat mms-table mms-table--flat">
						<tbody>
						<?php foreach ( (array) $invoices as $i ) : ?>
							<tr><td><strong class="mms-mono"><?php echo esc_html( $i['number'] ?: $i['stripe_id'] ); ?></strong></td><td><?php echo esc_html( mms_date( (int) $i['created'] ) ); ?></td><td><?php echo mms_pill( mms_invoice_status( $i ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td><td class="num"><?php echo esc_html( mms_money( (int) $i['total'], $i['currency'] ) ); ?></td><td class="mms-nowrap"><a class="button button-small" href="<?php echo esc_url( mms_pdf_url( (int) $i['id'] ) ); ?>" target="_blank" rel="noopener">PDF</a></td></tr>
						<?php endforeach; ?>
						<?php if ( ! $invoices ) : ?>
							<tr><td class="mms-empty-row"><?php esc_html_e( 'No invoices yet.', 'mine-mirth-subscriptions' ); ?></td></tr>
						<?php endif; ?>
						</tbody>
					</table>
				</section>
			</div>
			<aside class="mms-side">
				<section class="mms-card">
					<h2><?php esc_html_e( 'Details', 'mine-mirth-subscriptions' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="mms_customer">
						<input type="hidden" name="customer" value="<?php echo (int) $c['id']; ?>">
						<?php wp_nonce_field( 'mms_customer' ); ?>
						<label class="mms-f"><span><?php esc_html_e( 'Name', 'mine-mirth-subscriptions' ); ?></span><input name="name" value="<?php echo esc_attr( $c['name'] ); ?>"></label>
						<label class="mms-f"><span><?php esc_html_e( 'Phone / WhatsApp', 'mine-mirth-subscriptions' ); ?></span><input name="phone" value="<?php echo esc_attr( $c['phone'] ); ?>"></label>
						<label class="mms-f"><span><?php esc_html_e( 'Private notes', 'mine-mirth-subscriptions' ); ?> <i><?php esc_html_e( 'only you see these', 'mine-mirth-subscriptions' ); ?></i></span><textarea name="notes" rows="4" placeholder="<?php esc_attr_e( 'Miners at the facility, serial numbers, preferences…', 'mine-mirth-subscriptions' ); ?>"><?php echo esc_textarea( (string) $c['notes'] ); ?></textarea></label>
						<button class="button button-primary"><?php esc_html_e( 'Save', 'mine-mirth-subscriptions' ); ?></button>
					</form>
					<p class="mms-btns"><button type="button" class="button" data-mms-copy="<?php echo esc_attr( mms_portal_link( (string) $c['stripe_id'] ) ); ?>"><?php esc_html_e( 'Copy billing link for the client', 'mine-mirth-subscriptions' ); ?></button></p>
				</section>
			</aside>
		</div>
	</div>
	<?php
}
