<?php
/**
 * Emails in the Mine Mirth style: subscription started, billing reminder, receipt (with the invoice
 * PDF), payment failed, refund, discount or extra charge, and cancellation. Each one is sent once.
 *
 * @package MineMirthSubscriptions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Brand colours, from the theme when available.
 */
function mms_brand(): array {
	return array(
		'primary'   => (string) mms_theme_opt( 'color_primary', '#35a8f6' ),
		'accent'    => (string) mms_theme_opt( 'color_accent', '#2563eb' ),
		'highlight' => (string) mms_theme_opt( 'color_accent_2', '#5ee1ff' ),
		'name'      => (string) ( get_bloginfo( 'name' ) ?: 'Mine Mirth' ),
	);
}

/**
 * A complete email in the Mine Mirth design.
 *
 * @param array $a title, preheader, intro (HTML allowed: p, strong, a, br), image (URL), rows
 *                 ([label, value]), button ([label, url]), button2, note, eyebrow.
 */
function mms_email_html( array $a ): string {
	$b    = mms_brand();
	$s    = mms_settings();
	$e    = static fn( $v ) => esc_html( (string) $v );
	$rows = '';
	foreach ( (array) ( $a['rows'] ?? array() ) as $r ) {
		$rows .= '<tr><td style="padding:10px 0;border-bottom:1px solid #e6edf5;color:#5b6b80;font-size:14px">' . $e( $r[0] ) . '</td><td align="right" style="padding:10px 0;border-bottom:1px solid #e6edf5;color:#0a1626;font-size:14px;font-weight:600">' . wp_kses( (string) $r[1], array( 'a' => array( 'href' => array(), 'style' => array() ), 'span' => array( 'style' => array() ) ) ) . '</td></tr>';
	}
	$btn = static function ( $btn, bool $primary ) use ( $b ): string {
		if ( empty( $btn[0] ) || empty( $btn[1] ) ) {
			return '';
		}
		$style = $primary
			? 'background:' . $b['primary'] . ';background-image:linear-gradient(135deg,' . $b['highlight'] . ',' . $b['primary'] . ' 45%,' . $b['accent'] . ');color:#03111f;'
			: 'background:#ffffff;color:#0a1626;border:1px solid #d4deea;';
		return '<a href="' . esc_url( (string) $btn[1] ) . '" style="display:inline-block;' . $style . 'padding:14px 26px;border-radius:999px;font-weight:700;font-size:15px;text-decoration:none;margin:6px 6px 0 0">' . esc_html( (string) $btn[0] ) . '</a>';
	};
	$intro   = wp_kses( (string) ( $a['intro'] ?? '' ), array( 'p' => array( 'style' => array() ), 'strong' => array(), 'b' => array(), 'br' => array(), 'a' => array( 'href' => array(), 'style' => array() ) ) );
	$contact = array_filter( array( (string) $s['email'], (string) $s['phone'] ) );
	$address = trim( preg_replace( "/\r\n|\r|\n/", ', ', (string) $s['address'] ) );
	ob_start();
	?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><title><?php echo $e( $a['title'] ?? '' ); ?></title></head>
<body style="margin:0;padding:0;background:#eef3f9;font-family:Inter,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#0a1626">
<div style="display:none;max-height:0;overflow:hidden;opacity:0"><?php echo $e( $a['preheader'] ?? '' ); ?></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f9"><tr><td align="center" style="padding:28px 12px">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 18px 40px -26px rgba(7,18,33,.45)">
	<tr><td style="background:#071221;background-image:linear-gradient(135deg,#040a13,#0c2442);padding:24px 30px">
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
			<td style="vertical-align:middle"><span style="display:inline-block;width:38px;height:38px;line-height:38px;text-align:center;border-radius:11px;background:<?php echo esc_attr( $b['primary'] ); ?>;background-image:linear-gradient(135deg,<?php echo esc_attr( $b['highlight'] ); ?>,<?php echo esc_attr( $b['primary'] ); ?> 45%,<?php echo esc_attr( $b['accent'] ); ?>);color:#fff;font-weight:800;font-size:20px;vertical-align:middle">M</span><span style="color:#ffffff;font-weight:700;font-size:19px;margin-left:10px;vertical-align:middle"><?php echo $e( $b['name'] ); ?></span></td>
			<td align="right" style="vertical-align:middle;color:<?php echo esc_attr( $b['highlight'] ); ?>;font-family:ui-monospace,Menlo,monospace;font-size:11px;letter-spacing:.14em;text-transform:uppercase"><?php echo $e( $a['eyebrow'] ?? __( 'Hosting', 'mine-mirth-subscriptions' ) ); ?></td>
		</tr></table>
	</td></tr>
	<tr><td style="height:3px;background:<?php echo esc_attr( $b['primary'] ); ?>;background-image:linear-gradient(90deg,<?php echo esc_attr( $b['highlight'] ); ?>,<?php echo esc_attr( $b['primary'] ); ?>,<?php echo esc_attr( $b['accent'] ); ?>);font-size:0;line-height:0">&nbsp;</td></tr>
	<?php if ( ! empty( $a['image'] ) ) : ?>
		<tr><td style="padding:26px 30px 0"><img src="<?php echo esc_url( (string) $a['image'] ); ?>" alt="" width="540" style="display:block;width:100%;max-width:540px;height:auto;border-radius:14px"></td></tr>
	<?php endif; ?>
	<tr><td style="padding:26px 30px 8px">
		<h1 style="margin:0 0 12px;font-size:26px;line-height:1.25;color:#0a1626;font-weight:800;letter-spacing:-.01em"><?php echo $e( $a['title'] ?? '' ); ?></h1>
		<div style="font-size:15px;line-height:1.65;color:#3b4b60"><?php echo $intro; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered above. ?></div>
	</td></tr>
	<?php if ( $rows ) : ?>
		<tr><td style="padding:6px 30px 4px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><?php echo $rows; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></table></td></tr>
	<?php endif; ?>
	<?php if ( ! empty( $a['button'] ) || ! empty( $a['button2'] ) ) : ?>
		<tr><td style="padding:18px 30px 6px"><?php echo $btn( $a['button'] ?? array(), true ) . $btn( $a['button2'] ?? array(), false ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
	<?php endif; ?>
	<?php if ( ! empty( $a['note'] ) ) : ?>
		<tr><td style="padding:16px 30px 0"><div style="background:#f3f8fd;border:1px solid #e1ebf6;border-radius:12px;padding:14px 16px;font-size:13px;line-height:1.6;color:#4b5b70"><?php echo $e( $a['note'] ); ?></div></td></tr>
	<?php endif; ?>
	<tr><td style="padding:26px 30px 28px;font-size:14px;line-height:1.6;color:#3b4b60"><?php esc_html_e( 'Questions? Just reply to this email or call us.', 'mine-mirth-subscriptions' ); ?><br><strong style="color:#0a1626"><?php echo $e( $b['name'] ); ?></strong></td></tr>
	<tr><td style="background:#071221;padding:20px 30px;color:#8ea3bb;font-size:12px;line-height:1.7">
		<strong style="color:#dbe7f3"><?php echo $e( $s['company'] ); ?></strong><?php echo $address ? ' · ' . $e( $address ) : ''; ?><br><?php echo $e( implode( ' · ', $contact ) ); ?>
	</td></tr>
</table>
</td></tr></table>
</body></html>
	<?php
	return (string) ob_get_clean();
}

/**
 * Send an HTML email in the plugin's name.
 */
function mms_mail( string $to, string $subject, string $html, array $attachments = array() ): bool {
	if ( ! is_email( $to ) ) {
		return false;
	}
	$s       = mms_settings();
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$from    = is_email( (string) $s['from_email'] ) ? (string) $s['from_email'] : '';
	if ( $from ) {
		$headers[] = 'From: ' . wp_specialchars_decode( (string) $s['from_name'], ENT_QUOTES ) . ' <' . $from . '>';
	}
	if ( is_email( (string) $s['email'] ) ) {
		$headers[] = 'Reply-To: ' . (string) $s['email'];
	}
	$ok = wp_mail( $to, $subject, $html, $headers, $attachments );
	mms_log( 'email', sprintf( '%s "%s" to %s', $ok ? 'Sent' : 'Could not send', $subject, $to ), '', $ok ? 'info' : 'warn' );
	return $ok;
}

function mms_admin_mail( string $subject, string $text ): void {
	$s = mms_settings();
	if ( empty( $s['admin_copy'] ) || ! is_email( (string) $s['admin_email'] ) ) {
		return;
	}
	mms_mail( (string) $s['admin_email'], $subject, mms_email_html( array( 'eyebrow' => __( 'Admin', 'mine-mirth-subscriptions' ), 'title' => $subject, 'intro' => '<p>' . esc_html( $text ) . '</p>', 'button' => array( __( 'Open subscriptions', 'mine-mirth-subscriptions' ), admin_url( 'admin.php?page=mms-subs' ) ) ) ) );
}

/**
 * Everything an email needs about a subscription.
 */
function mms_sub_context( int $sub_id ): ?array {
	$sub  = mms_get( 'subscriptions', 'id', $sub_id );
	$cust = $sub ? mms_get( 'customers', 'id', (int) $sub['customer_id'] ) : null;
	if ( ! $sub || ! $cust ) {
		return null;
	}
	$first = trim( explode( ' ', (string) $cust['name'] )[0] );
	return array(
		'sub'    => $sub,
		'cust'   => $cust,
		'hi'     => '' !== $first ? sprintf( /* translators: %s: first name */ __( 'Hi %s,', 'mine-mirth-subscriptions' ), $first ) : __( 'Hello,', 'mine-mirth-subscriptions' ),
		'image'  => mms_plan_image( (int) $sub['plan_id'], 'large' ),
		'amount' => mms_money( (int) $sub['amount'] * (int) $sub['quantity'], (string) $sub['currency'] ) . ' ' . mms_interval_label( (string) $sub['interval_unit'], (int) $sub['interval_count'] ),
		'portal' => mms_portal_link( (string) $sub['stripe_customer'] ),
	);
}

/* ------------------------------------------------------------------ Subscription started / cancelled */

add_action(
	'mms_subscription_status',
	static function ( int $id, string $new, string $old ): void {
		$sub = mms_get( 'subscriptions', 'id', $id );
		if ( ! $sub ) {
			return;
		}
		$data = mms_json( $sub['data'] );
		if ( in_array( $new, array( 'active', 'trialing' ), true ) && empty( $data['welcome_sent'] ) ) {
			mms_set_data( 'subscriptions', $id, array( 'welcome_sent' => time() ) );
			mms_email_welcome( $id );
		}
		if ( 'canceled' === $new && 'canceled' !== $old && empty( $data['cancel_sent'] ) ) {
			mms_set_data( 'subscriptions', $id, array( 'cancel_sent' => time() ) );
			mms_email_canceled( $id );
		}
	},
	10,
	3
);

function mms_email_welcome( int $sub_id ): bool {
	$c = mms_sub_context( $sub_id );
	if ( ! $c ) {
		return false;
	}
	$sub   = $c['sub'];
	$cust  = $c['cust'];
	$trial = 'trialing' === $sub['status'];
	mms_admin_mail(
		sprintf( /* translators: 1: client number, 2: plan */ __( 'New subscription: %1$s on %2$s', 'mine-mirth-subscriptions' ), $cust['client_no'], $sub['plan_name'] ),
		sprintf( /* translators: 1: name, 2: email, 3: amount */ __( '%1$s (%2$s) subscribed: %3$s.', 'mine-mirth-subscriptions' ), $cust['name'] ?: $cust['email'], $cust['email'], $c['amount'] )
	);
	if ( ! mms_setting( 'email_welcome' ) ) {
		return false;
	}
	$html = mms_email_html(
		array(
			'eyebrow'   => __( 'Subscription active', 'mine-mirth-subscriptions' ),
			'preheader' => sprintf( /* translators: %s: client number */ __( 'Your client number is %s.', 'mine-mirth-subscriptions' ), $cust['client_no'] ),
			'title'     => sprintf( /* translators: %s: plan */ __( 'Welcome to %s', 'mine-mirth-subscriptions' ), $sub['plan_name'] ),
			'image'     => $c['image'],
			'intro'     => '<p>' . esc_html( $c['hi'] ) . '</p><p>' . esc_html( $trial ? __( 'Your free trial has started. Here is everything you need, including your client number: please quote it whenever you contact us.', 'mine-mirth-subscriptions' ) : __( 'Thank you for subscribing. Your plan is active. Here is everything you need, including your client number: please quote it whenever you contact us.', 'mine-mirth-subscriptions' ) ) . '</p>',
			'rows'      => array(
				array( __( 'Client number', 'mine-mirth-subscriptions' ), '<span style="font-family:ui-monospace,Menlo,monospace;color:#0a6dc2">' . esc_html( $cust['client_no'] ) . '</span>' ),
				array( __( 'Plan', 'mine-mirth-subscriptions' ), esc_html( $sub['plan_name'] ) ),
				array( __( 'Quantity', 'mine-mirth-subscriptions' ), (string) (int) $sub['quantity'] ),
				array( __( 'Price', 'mine-mirth-subscriptions' ), esc_html( $c['amount'] ) ),
				array( $trial ? __( 'Trial ends', 'mine-mirth-subscriptions' ) : __( 'Next billing', 'mine-mirth-subscriptions' ), esc_html( mms_date( (int) $sub['period_end'] ) ) ),
			),
			'button'    => array( __( 'Manage my subscription', 'mine-mirth-subscriptions' ), $c['portal'] ),
			'note'      => sprintf( /* translators: %d: days */ __( 'We will remind you %d days before each billing. You can update your card or download receipts at any time with the button above.', 'mine-mirth-subscriptions' ), (int) mms_setting( 'reminder_days' ) ),
		)
	);
	/* translators: %s: plan */
	return mms_mail( (string) $cust['email'], sprintf( __( 'Your %s subscription is active', 'mine-mirth-subscriptions' ), $sub['plan_name'] ), $html );
}

function mms_email_canceled( int $sub_id ): bool {
	$c = mms_sub_context( $sub_id );
	if ( ! $c ) {
		return false;
	}
	mms_admin_mail(
		sprintf( /* translators: 1: client number, 2: plan */ __( 'Subscription cancelled: %1$s, %2$s', 'mine-mirth-subscriptions' ), $c['cust']['client_no'], $c['sub']['plan_name'] ),
		sprintf( /* translators: %s: email */ __( 'The subscription of %s has ended.', 'mine-mirth-subscriptions' ), $c['cust']['email'] )
	);
	if ( ! mms_setting( 'email_canceled' ) ) {
		return false;
	}
	$html = mms_email_html(
		array(
			'eyebrow' => __( 'Subscription ended', 'mine-mirth-subscriptions' ),
			'title'   => sprintf( /* translators: %s: plan */ __( 'Your %s subscription has ended', 'mine-mirth-subscriptions' ), $c['sub']['plan_name'] ),
			'image'   => $c['image'],
			'intro'   => '<p>' . esc_html( $c['hi'] ) . '</p><p>' . esc_html__( 'Your subscription is now cancelled and you will not be billed again. Thank you for hosting with us. If this was a mistake or you want to come back, reply to this email.', 'mine-mirth-subscriptions' ) . '</p>',
			'rows'    => array(
				array( __( 'Client number', 'mine-mirth-subscriptions' ), esc_html( $c['cust']['client_no'] ) ),
				array( __( 'Plan', 'mine-mirth-subscriptions' ), esc_html( $c['sub']['plan_name'] ) ),
			),
			'button'  => array( __( 'See hosting plans', 'mine-mirth-subscriptions' ), mms_plans_page_url() ),
		)
	);
	return mms_mail( (string) $c['cust']['email'], __( 'Your subscription has been cancelled', 'mine-mirth-subscriptions' ), $html );
}

/* ------------------------------------------------------------------ Billing reminder */

function mms_email_reminder( int $sub_id ): bool {
	$c = mms_sub_context( $sub_id );
	if ( ! $c ) {
		return false;
	}
	$sub = $c['sub'];
	// The exact next bill, with any discount or extra charge already added.
	$amount  = (int) $sub['amount'] * (int) $sub['quantity'];
	$preview = mms_stripe( 'GET', '/v1/invoices/upcoming', array( 'subscription' => $sub['stripe_id'] ) );
	if ( ! is_wp_error( $preview ) && isset( $preview['amount_due'] ) ) {
		$amount = (int) $preview['amount_due'];
	}
	$html = mms_email_html(
		array(
			'eyebrow'   => __( 'Upcoming billing', 'mine-mirth-subscriptions' ),
			'preheader' => sprintf( /* translators: 1: amount, 2: date */ __( '%1$s on %2$s', 'mine-mirth-subscriptions' ), mms_money( $amount, $sub['currency'] ), mms_date( (int) $sub['period_end'] ) ),
			'title'     => __( 'Your next billing is coming up', 'mine-mirth-subscriptions' ),
			'image'     => $c['image'],
			'intro'     => '<p>' . esc_html( $c['hi'] ) . '</p><p>' . esc_html( sprintf( /* translators: 1: plan, 2: date */ __( 'A quick heads-up: your %1$s subscription renews on %2$s and your card on file will be charged automatically.', 'mine-mirth-subscriptions' ), $sub['plan_name'], mms_date( (int) $sub['period_end'] ) ) ) . '</p>',
			'rows'      => array(
				array( __( 'Client number', 'mine-mirth-subscriptions' ), esc_html( $c['cust']['client_no'] ) ),
				array( __( 'Plan', 'mine-mirth-subscriptions' ), esc_html( $sub['plan_name'] . ( (int) $sub['quantity'] > 1 ? ' × ' . (int) $sub['quantity'] : '' ) ) ),
				array( __( 'Billing date', 'mine-mirth-subscriptions' ), esc_html( mms_date( (int) $sub['period_end'] ) ) ),
				array( __( 'Amount', 'mine-mirth-subscriptions' ), esc_html( mms_money( $amount, $sub['currency'] ) ) ),
			),
			'button'    => array( __( 'Update card or details', 'mine-mirth-subscriptions' ), $c['portal'] ),
			'note'      => __( 'Nothing to do if your card is up to date. To change your plan or quantity, reply to this email.', 'mine-mirth-subscriptions' ),
		)
	);
	/* translators: %s: date */
	return mms_mail( (string) $c['cust']['email'], sprintf( __( 'Your Mine Mirth hosting renews on %s', 'mine-mirth-subscriptions' ), mms_date( (int) $sub['period_end'] ) ), $html );
}

add_action(
	'mms_hourly',
	static function (): void {
		global $wpdb;
		if ( ! mms_setting( 'email_reminder' ) || ! mms_connected() ) {
			return;
		}
		$days = max( 1, (int) mms_setting( 'reminder_days' ) );
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mms_table( 'subscriptions' ) . " WHERE mode = %s AND status IN ('active','trialing') AND cancel_at_period_end = 0 AND period_end > %d AND period_end <= %d LIMIT 200", mms_mode(), time(), time() + $days * DAY_IN_SECONDS ), ARRAY_A ); // phpcs:ignore WordPress.DB
		foreach ( (array) $rows as $sub ) {
			$data = mms_json( $sub['data'] );
			if ( (int) ( $data['reminded_for'] ?? 0 ) === (int) $sub['period_end'] ) {
				continue;
			}
			mms_set_data( 'subscriptions', (int) $sub['id'], array( 'reminded_for' => (int) $sub['period_end'] ) );
			mms_email_reminder( (int) $sub['id'] );
		}
	}
);

/* ------------------------------------------------------------------ Receipts and failed payments */

add_action(
	'mms_invoice_paid',
	static function ( int $inv_id ): void {
		$inv = mms_get( 'invoices', 'id', $inv_id );
		if ( ! $inv || (int) $inv['amount_paid'] <= 0 || ! empty( mms_json( $inv['data'] )['receipt_sent'] ) ) {
			return;
		}
		mms_set_data( 'invoices', $inv_id, array( 'receipt_sent' => time() ) );
		// "Charge now" from WordPress sends its own email with the invoice link.
		if ( mms_setting( 'email_receipt' ) && ! get_transient( 'mms_quiet_receipt_' . md5( (string) $inv['stripe_id'] ) ) ) {
			mms_email_receipt( $inv_id );
		}
	}
);

function mms_email_receipt( int $inv_id ): bool {
	$inv  = mms_get( 'invoices', 'id', $inv_id );
	$cust = $inv ? mms_get( 'customers', 'id', (int) $inv['customer_id'] ) : null;
	if ( ! $inv || ! $cust ) {
		return false;
	}
	$sub   = '' !== $inv['subscription'] ? mms_get( 'subscriptions', 'stripe_id', $inv['subscription'] ) : null;
	$first = trim( explode( ' ', (string) $cust['name'] )[0] );
	$file  = mms_invoice_pdf_file( $inv );
	$html  = mms_email_html(
		array(
			'eyebrow'   => __( 'Payment received', 'mine-mirth-subscriptions' ),
			'preheader' => sprintf( /* translators: %s: amount */ __( 'We received %s. Thank you!', 'mine-mirth-subscriptions' ), mms_money( (int) $inv['amount_paid'], $inv['currency'] ) ),
			'title'     => __( 'Thank you, your payment is in', 'mine-mirth-subscriptions' ),
			'image'     => $sub ? mms_plan_image( (int) $sub['plan_id'], 'large' ) : '',
			'intro'     => '<p>' . esc_html( '' !== $first ? sprintf( /* translators: %s: name */ __( 'Hi %s,', 'mine-mirth-subscriptions' ), $first ) : __( 'Hello,', 'mine-mirth-subscriptions' ) ) . '</p><p>' . esc_html__( 'We received your payment. Your invoice is attached as a PDF and you can also download it with the button below.', 'mine-mirth-subscriptions' ) . '</p>',
			'rows'      => array_filter(
				array(
					array( __( 'Client number', 'mine-mirth-subscriptions' ), esc_html( $cust['client_no'] ) ),
					array( __( 'Invoice', 'mine-mirth-subscriptions' ), esc_html( $inv['number'] ?: $inv['stripe_id'] ) ),
					$sub ? array( __( 'Plan', 'mine-mirth-subscriptions' ), esc_html( $sub['plan_name'] ) ) : null,
					$inv['period_start'] ? array( __( 'Service period', 'mine-mirth-subscriptions' ), esc_html( mms_date( (int) $inv['period_start'] ) . ' to ' . mms_date( (int) $inv['period_end'] ) ) ) : null,
					array( __( 'Amount paid', 'mine-mirth-subscriptions' ), esc_html( mms_money( (int) $inv['amount_paid'], $inv['currency'] ) ) ),
					$sub && in_array( $sub['status'], array( 'active', 'trialing' ), true ) ? array( __( 'Next billing', 'mine-mirth-subscriptions' ), esc_html( mms_date( (int) $sub['period_end'] ) ) ) : null,
				)
			),
			'button'    => array( __( 'Download invoice (PDF)', 'mine-mirth-subscriptions' ), mms_invoice_link( (string) $inv['stripe_id'] ) ),
			'button2'   => array( __( 'Manage subscription', 'mine-mirth-subscriptions' ), mms_portal_link( (string) $inv['stripe_customer'] ) ),
		)
	);
	/* translators: %s: invoice number */
	$ok = mms_mail( (string) $cust['email'], sprintf( __( 'Receipt for invoice %s', 'mine-mirth-subscriptions' ), $inv['number'] ?: $inv['stripe_id'] ), $html, $file ? array( $file ) : array() );
	if ( $file ) {
		wp_delete_file( $file );
		@rmdir( dirname( $file ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
	}
	return $ok;
}

add_action(
	'mms_invoice_failed',
	static function ( int $inv_id ): void {
		$inv  = mms_get( 'invoices', 'id', $inv_id );
		$cust = $inv ? mms_get( 'customers', 'id', (int) $inv['customer_id'] ) : null;
		if ( ! $inv || ! $cust ) {
			return;
		}
		$key = 'failed_' . (int) $inv['created'] . '_' . (int) $inv['amount_due'];
		$data = mms_json( $inv['data'] );
		if ( ! empty( $data[ $key ] ) ) {
			return;
		}
		mms_set_data( 'invoices', $inv_id, array( $key => time() ) );
		mms_admin_mail(
			sprintf( /* translators: %s: client number */ __( 'Payment failed: %s', 'mine-mirth-subscriptions' ), $cust['client_no'] ),
			sprintf( /* translators: 1: email, 2: amount */ __( 'A payment of %2$s from %1$s failed. Stripe will retry automatically.', 'mine-mirth-subscriptions' ), $cust['email'], mms_money( (int) $inv['amount_due'], $inv['currency'] ) )
		);
		if ( ! mms_setting( 'email_failed' ) ) {
			return;
		}
		$first = trim( explode( ' ', (string) $cust['name'] )[0] );
		$html  = mms_email_html(
			array(
				'eyebrow' => __( 'Action needed', 'mine-mirth-subscriptions' ),
				'title'   => __( 'Your payment did not go through', 'mine-mirth-subscriptions' ),
				'intro'   => '<p>' . esc_html( '' !== $first ? sprintf( /* translators: %s: name */ __( 'Hi %s,', 'mine-mirth-subscriptions' ), $first ) : __( 'Hello,', 'mine-mirth-subscriptions' ) ) . '</p><p>' . esc_html__( 'We tried to charge your card for your hosting subscription but the payment was declined. Please update your card so your miners keep running without interruption. We will try again automatically.', 'mine-mirth-subscriptions' ) . '</p>',
				'rows'    => array(
					array( __( 'Client number', 'mine-mirth-subscriptions' ), esc_html( $cust['client_no'] ) ),
					array( __( 'Amount due', 'mine-mirth-subscriptions' ), esc_html( mms_money( (int) $inv['amount_due'], $inv['currency'] ) ) ),
				),
				'button'  => $inv['hosted_url'] ? array( __( 'Pay this invoice', 'mine-mirth-subscriptions' ), $inv['hosted_url'] ) : array( __( 'Update my card', 'mine-mirth-subscriptions' ), mms_portal_link( (string) $inv['stripe_customer'] ) ),
				'button2' => $inv['hosted_url'] ? array( __( 'Update my card', 'mine-mirth-subscriptions' ), mms_portal_link( (string) $inv['stripe_customer'] ) ) : array(),
			)
		);
		mms_mail( (string) $cust['email'], __( 'Action needed: your hosting payment failed', 'mine-mirth-subscriptions' ), $html );
	}
);

/* ------------------------------------------------------------------ Refunds and account changes */

add_action( 'mms_refunded', 'mms_email_refund', 10, 2 );

function mms_email_refund( int $inv_id, int $amount, string $note = '' ): bool {
	$inv  = mms_get( 'invoices', 'id', $inv_id );
	$cust = $inv ? mms_get( 'customers', 'id', (int) $inv['customer_id'] ) : null;
	if ( ! $inv || ! $cust || ! mms_setting( 'email_refund' ) ) {
		return false;
	}
	$first = trim( explode( ' ', (string) $cust['name'] )[0] );
	$html  = mms_email_html(
		array(
			'eyebrow' => __( 'Refund', 'mine-mirth-subscriptions' ),
			'title'   => sprintf( /* translators: %s: amount */ __( 'We refunded %s', 'mine-mirth-subscriptions' ), mms_money( $amount, $inv['currency'] ) ),
			'intro'   => '<p>' . esc_html( '' !== $first ? sprintf( /* translators: %s: name */ __( 'Hi %s,', 'mine-mirth-subscriptions' ), $first ) : __( 'Hello,', 'mine-mirth-subscriptions' ) ) . '</p><p>' . esc_html__( 'A refund is on its way to the card you paid with. It usually shows on your statement within 5 to 10 business days.', 'mine-mirth-subscriptions' ) . '</p>' . ( '' !== $note ? '<p>' . esc_html( $note ) . '</p>' : '' ),
			'rows'    => array(
				array( __( 'Client number', 'mine-mirth-subscriptions' ), esc_html( $cust['client_no'] ) ),
				array( __( 'Invoice', 'mine-mirth-subscriptions' ), esc_html( $inv['number'] ?: $inv['stripe_id'] ) ),
				array( __( 'Refund', 'mine-mirth-subscriptions' ), esc_html( mms_money( $amount, $inv['currency'] ) ) ),
			),
		)
	);
	return mms_mail( (string) $cust['email'], sprintf( /* translators: %s: amount */ __( 'Refund of %s issued', 'mine-mirth-subscriptions' ), mms_money( $amount, $inv['currency'] ) ), $html );
}

/**
 * Tell the customer about a discount or an extra charge.
 *
 * @param string $kind discount|charge_next|charge_now.
 */
function mms_email_adjustment( int $sub_id, string $kind, string $amount_text, string $description, string $pay_url = '' ): bool {
	$c = mms_sub_context( $sub_id );
	if ( ! $c || ! mms_setting( 'email_adjust' ) ) {
		return false;
	}
	$titles = array(
		'discount'    => sprintf( /* translators: %s: amount */ __( 'A discount of %s on your next bill', 'mine-mirth-subscriptions' ), $amount_text ),
		'charge_next' => sprintf( /* translators: %s: amount */ __( 'A charge of %s will be added to your next bill', 'mine-mirth-subscriptions' ), $amount_text ),
		'charge_now'  => sprintf( /* translators: %s: amount */ __( 'We charged %s to your card', 'mine-mirth-subscriptions' ), $amount_text ),
	);
	$texts  = array(
		'discount'    => sprintf( /* translators: %s: date */ __( 'Good news: we added a discount to your next billing on %s. It is applied automatically.', 'mine-mirth-subscriptions' ), mms_date( (int) $c['sub']['period_end'] ) ),
		'charge_next' => sprintf( /* translators: %s: date */ __( 'This charge will be added to your next billing on %s.', 'mine-mirth-subscriptions' ), mms_date( (int) $c['sub']['period_end'] ) ),
		'charge_now'  => __( 'This charge was billed to the card on file for your hosting subscription. Your invoice is available below.', 'mine-mirth-subscriptions' ),
	);
	$html = mms_email_html(
		array(
			'eyebrow' => 'discount' === $kind ? __( 'Discount', 'mine-mirth-subscriptions' ) : __( 'Account update', 'mine-mirth-subscriptions' ),
			'title'   => $titles[ $kind ] ?? $titles['charge_next'],
			'intro'   => '<p>' . esc_html( $c['hi'] ) . '</p><p>' . esc_html( $texts[ $kind ] ?? '' ) . '</p>',
			'rows'    => array(
				array( __( 'Client number', 'mine-mirth-subscriptions' ), esc_html( $c['cust']['client_no'] ) ),
				array( __( 'For', 'mine-mirth-subscriptions' ), esc_html( $description ) ),
				array( __( 'Amount', 'mine-mirth-subscriptions' ), esc_html( $amount_text ) ),
			),
			'button'  => $pay_url ? array( __( 'View invoice', 'mine-mirth-subscriptions' ), $pay_url ) : array( __( 'Manage subscription', 'mine-mirth-subscriptions' ), $c['portal'] ),
		)
	);
	return mms_mail( (string) $c['cust']['email'], $titles[ $kind ] ?? $description, $html );
}
