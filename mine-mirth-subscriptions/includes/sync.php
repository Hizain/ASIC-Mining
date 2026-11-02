<?php
/**
 * Keep the local copy in step with Stripe: customers (with client numbers), subscriptions and
 * invoices. Webhooks call these as things happen; "Sync with Stripe" and a twice-daily job catch up.
 *
 * @package MineMirthSubscriptions
 */

defined( 'ABSPATH' ) || exit;

/**
 * The ID of a Stripe object or expanded object.
 */
function mms_id( $value ): string {
	return is_array( $value ) ? (string) ( $value['id'] ?? '' ) : (string) $value;
}

/* ------------------------------------------------------------------ Customers */

/**
 * Save a Stripe customer and give them a client number. Returns the local customer ID.
 */
function mms_sync_customer( array $c ): int {
	$sid = (string) ( $c['id'] ?? '' );
	if ( '' === $sid || ! empty( $c['deleted'] ) ) {
		return 0;
	}
	$row    = mms_get( 'customers', 'stripe_id', $sid );
	$email  = sanitize_email( (string) ( $c['email'] ?? '' ) );
	$fields = array(
		'mode'    => empty( $c['livemode'] ) ? 'test' : 'live',
		'email'   => $email,
		'name'    => sanitize_text_field( (string) ( $c['name'] ?? '' ) ),
		'phone'   => sanitize_text_field( (string) ( $c['phone'] ?? '' ) ),
		'address' => ! empty( $c['address'] ) ? wp_json_encode( $c['address'] ) : ( $row['address'] ?? '' ),
	);
	if ( '' === $fields['name'] && $row ) {
		$fields['name'] = (string) $row['name'];
	}
	// Client number: keep the one we gave, or one already saved on the customer in Stripe.
	$client = $row ? (string) $row['client_no'] : '';
	if ( '' === $client && '' !== $email ) {
		// A returning client (Checkout makes a new Stripe customer each time) keeps the number they already have.
		global $wpdb;
		$client = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT client_no FROM ' . mms_table( 'customers' ) . " WHERE email = %s AND mode = %s AND client_no <> '' ORDER BY id ASC LIMIT 1", $email, $fields['mode'] ) ); // phpcs:ignore WordPress.DB
	}
	if ( '' === $client ) {
		$meta   = (string) ( $c['metadata']['client_number'] ?? '' );
		$client = '' !== $meta && ! mms_get( 'customers', 'client_no', $meta ) ? sanitize_text_field( $meta ) : mms_take_client_no();
	}
	$fields['client_no'] = $client;
	if ( ! $row || ! (int) $row['wp_user_id'] ) {
		$user                 = $email ? get_user_by( 'email', $email ) : false;
		$fields['wp_user_id'] = $user ? (int) $user->ID : 0;
	}
	$id = mms_upsert( 'customers', $sid, $fields );
	if ( ( $c['metadata']['client_number'] ?? '' ) !== $client ) {
		mms_stripe( 'POST', '/v1/customers/' . rawurlencode( $sid ), array( 'metadata' => array( 'client_number' => $client ) ) );
	}
	if ( ! $row ) {
		mms_log( 'customer', sprintf( 'New client %s: %s', $client, $email ), $sid );
	}
	return $id;
}

/**
 * Local customer for a Stripe customer ID, fetching it from Stripe when it is not stored yet.
 */
function mms_customer_for( string $stripe_id ): ?array {
	if ( '' === $stripe_id ) {
		return null;
	}
	$row = mms_get( 'customers', 'stripe_id', $stripe_id );
	if ( ! $row ) {
		$c = mms_stripe( 'GET', '/v1/customers/' . rawurlencode( $stripe_id ) );
		if ( ! is_wp_error( $c ) && mms_sync_customer( $c ) ) {
			$row = mms_get( 'customers', 'stripe_id', $stripe_id );
		}
	}
	return $row;
}

/* ------------------------------------------------------------------ Subscriptions */

/**
 * Save a Stripe subscription. Returns the local subscription ID.
 */
function mms_sync_subscription( array $s ): int {
	$sid = (string) ( $s['id'] ?? '' );
	if ( '' === $sid ) {
		return 0;
	}
	$cust_sid = mms_id( $s['customer'] ?? '' );
	if ( is_array( $s['customer'] ?? null ) ) {
		mms_sync_customer( $s['customer'] );
	}
	$cust  = mms_customer_for( $cust_sid );
	$item  = (array) ( $s['items']['data'][0] ?? array() );
	$price = (array) ( $item['price'] ?? array() );
	$rec   = (array) ( $price['recurring'] ?? array() );
	$prod  = mms_id( $price['product'] ?? '' );
	$plan  = absint( $s['metadata']['wp_plan_id'] ?? 0 );
	if ( ! $plan || MMS_PLAN_TYPE !== get_post_type( $plan ) ) {
		$plan = mms_plan_by_stripe( (string) ( $price['id'] ?? '' ), $prod );
	}
	$name = $plan ? get_the_title( $plan ) : '';
	if ( '' === $name ) {
		$name = (string) ( $price['nickname'] ?? '' );
		$name = '' !== $name ? $name : ( is_array( $price['product'] ?? null ) ? (string) ( $price['product']['name'] ?? '' ) : '' );
	}
	// Period dates moved from the subscription to its items in newer API versions; read either.
	$start = (int) ( $s['current_period_start'] ?? $item['current_period_start'] ?? 0 );
	$end   = (int) ( $s['current_period_end'] ?? $item['current_period_end'] ?? 0 );
	$old   = mms_get( 'subscriptions', 'stripe_id', $sid );
	$id    = mms_upsert(
		'subscriptions',
		$sid,
		array(
			'mode'                 => empty( $s['livemode'] ) ? 'test' : 'live',
			'customer_id'          => $cust ? (int) $cust['id'] : 0,
			'stripe_customer'      => $cust_sid,
			'plan_id'              => $plan,
			'price_id'             => (string) ( $price['id'] ?? '' ),
			'product_id'           => $prod,
			'plan_name'            => mb_substr( $name, 0, 190 ),
			'status'               => (string) ( $s['status'] ?? '' ),
			'quantity'             => max( 1, (int) ( $item['quantity'] ?? $s['quantity'] ?? 1 ) ),
			'amount'               => (int) ( $price['unit_amount'] ?? 0 ),
			'currency'             => (string) ( $price['currency'] ?? $s['currency'] ?? mms_setting( 'currency' ) ),
			'interval_unit'        => (string) ( $rec['interval'] ?? 'month' ),
			'interval_count'       => max( 1, (int) ( $rec['interval_count'] ?? 1 ) ),
			'period_start'         => $start,
			'period_end'           => $end,
			'cancel_at_period_end' => empty( $s['cancel_at_period_end'] ) ? 0 : 1,
			'canceled_at'          => (int) ( $s['canceled_at'] ?? 0 ),
			'started'              => (int) ( $s['start_date'] ?? $s['created'] ?? 0 ),
			'default_pm'           => mms_id( $s['default_payment_method'] ?? '' ),
			'latest_invoice'       => mms_id( $s['latest_invoice'] ?? '' ),
		)
	);
	$new_status = (string) ( $s['status'] ?? '' );
	$old_status = $old ? (string) $old['status'] : '';
	if ( $new_status !== $old_status ) {
		mms_log( 'subscription', sprintf( 'Subscription %s: %s', $name, $old_status ? $old_status . ' → ' . $new_status : $new_status ), $sid );
		/**
		 * Fires when a subscription's status changes (welcome and cancellation emails hook in here).
		 */
		do_action( 'mms_subscription_status', $id, $new_status, $old_status );
	}
	return $id;
}

/* ------------------------------------------------------------------ Invoices */

/**
 * Save a Stripe invoice with its lines. Returns the local invoice ID.
 */
function mms_sync_invoice( array $inv ): int {
	$sid = (string) ( $inv['id'] ?? '' );
	if ( '' === $sid ) {
		return 0;
	}
	$cust_sid = mms_id( $inv['customer'] ?? '' );
	$cust     = mms_customer_for( $cust_sid );
	$lines    = array();
	foreach ( (array) ( $inv['lines']['data'] ?? array() ) as $l ) {
		$price   = (array) ( $l['price'] ?? array() );
		$lines[] = array(
			'description' => (string) ( $l['description'] ?? '' ),
			'amount'      => (int) ( $l['amount'] ?? 0 ),
			'quantity'    => (int) ( $l['quantity'] ?? 1 ),
			'unit'        => (int) ( $price['unit_amount'] ?? 0 ),
			'start'       => (int) ( $l['period']['start'] ?? 0 ),
			'end'         => (int) ( $l['period']['end'] ?? 0 ),
			'price'       => (string) ( $price['id'] ?? '' ),
			'product'     => mms_id( $price['product'] ?? '' ),
			'proration'   => ! empty( $l['proration'] ),
			'type'        => (string) ( $l['type'] ?? '' ),
		);
	}
	$discount = 0;
	foreach ( (array) ( $inv['total_discount_amounts'] ?? array() ) as $d ) {
		$discount += (int) ( $d['amount'] ?? 0 );
	}
	$tax = (int) ( $inv['tax'] ?? 0 );
	if ( ! $tax ) {
		foreach ( (array) ( $inv['total_taxes'] ?? $inv['total_tax_amounts'] ?? array() ) as $t ) {
			$tax += (int) ( $t['amount'] ?? 0 );
		}
	}
	$old     = mms_get( 'invoices', 'stripe_id', $sid );
	$sub     = mms_id( $inv['subscription'] ?? ( $inv['parent']['subscription_details']['subscription'] ?? '' ) );
	$charge  = mms_id( $inv['charge'] ?? '' );
	$pi      = mms_id( $inv['payment_intent'] ?? '' );
	$refund  = $old ? (int) $old['amount_refunded'] : 0;
	if ( is_array( $inv['charge'] ?? null ) ) {
		$refund = (int) ( $inv['charge']['amount_refunded'] ?? $refund );
	}
	$id = mms_upsert(
		'invoices',
		$sid,
		array(
			'mode'            => empty( $inv['livemode'] ) ? 'test' : 'live',
			'number'          => (string) ( $inv['number'] ?? '' ),
			'customer_id'     => $cust ? (int) $cust['id'] : 0,
			'stripe_customer' => $cust_sid,
			'subscription'    => $sub,
			'status'          => (string) ( $inv['status'] ?? '' ),
			'billing_reason'  => (string) ( $inv['billing_reason'] ?? '' ),
			'currency'        => (string) ( $inv['currency'] ?? 'usd' ),
			'subtotal'        => (int) ( $inv['subtotal'] ?? 0 ),
			'discount'        => $discount,
			'tax'             => $tax,
			'total'           => (int) ( $inv['total'] ?? 0 ),
			'amount_paid'     => (int) ( $inv['amount_paid'] ?? 0 ),
			'amount_due'      => (int) ( $inv['amount_due'] ?? 0 ),
			'amount_refunded' => $refund,
			'created'         => (int) ( $inv['created'] ?? 0 ),
			'period_start'    => (int) ( $lines[0]['start'] ?? $inv['period_start'] ?? 0 ),
			'period_end'      => (int) ( $lines[0]['end'] ?? $inv['period_end'] ?? 0 ),
			'paid_at'         => (int) ( $inv['status_transitions']['paid_at'] ?? 0 ),
			'hosted_url'      => (string) ( $inv['hosted_invoice_url'] ?? '' ),
			'pdf_url'         => (string) ( $inv['invoice_pdf'] ?? '' ),
			'payment_intent'  => '' !== $pi ? $pi : ( $old['payment_intent'] ?? '' ),
			'charge'          => '' !== $charge ? $charge : ( $old['charge'] ?? '' ),
			'line_items'      => wp_json_encode( $lines ),
		)
	);
	return $id;
}

/**
 * The payment behind an invoice, for refunds: [payment_intent, charge].
 */
function mms_invoice_payment( array $row ): array {
	$pi     = (string) $row['payment_intent'];
	$charge = (string) $row['charge'];
	if ( '' === $pi && '' === $charge ) {
		$inv = mms_stripe( 'GET', '/v1/invoices/' . rawurlencode( $row['stripe_id'] ) );
		if ( ! is_wp_error( $inv ) ) {
			$pi     = mms_id( $inv['payment_intent'] ?? '' );
			$charge = mms_id( $inv['charge'] ?? '' );
		}
	}
	return array( $pi, $charge );
}

/* ------------------------------------------------------------------ Full sync */

/**
 * Pull every subscription and recent invoice from Stripe.
 *
 * @return array|WP_Error Counts.
 */
function mms_full_sync() {
	if ( ! mms_connected() ) {
		return new WP_Error( 'mms_not_connected', __( 'Stripe is not connected yet.', 'mine-mirth-subscriptions' ) );
	}
	$subs = mms_stripe_all( '/v1/subscriptions', array( 'status' => 'all', 'expand' => array( 'data.customer' ) ), 2000 );
	if ( is_wp_error( $subs ) ) {
		return $subs;
	}
	foreach ( $subs as $s ) {
		mms_sync_subscription( $s );
	}
	$invoices = mms_stripe_all( '/v1/invoices', array( 'created' => array( 'gte' => time() - 400 * DAY_IN_SECONDS ) ), 3000 );
	$n_inv    = 0;
	if ( ! is_wp_error( $invoices ) ) {
		foreach ( $invoices as $inv ) {
			if ( '' !== mms_id( $inv['subscription'] ?? '' ) || mms_get( 'customers', 'stripe_id', mms_id( $inv['customer'] ?? '' ) ) ) {
				mms_sync_invoice( $inv );
				++$n_inv;
			}
		}
	}
	update_option( 'mms_last_sync', time(), false );
	mms_log( 'sync', sprintf( 'Synced %d subscriptions and %d invoices from Stripe.', count( $subs ), $n_inv ) );
	return array( 'subscriptions' => count( $subs ), 'invoices' => $n_inv );
}

add_action(
	'mms_hourly',
	static function (): void {
		if ( mms_connected() && time() - (int) get_option( 'mms_last_sync', 0 ) > 12 * HOUR_IN_SECONDS ) {
			mms_full_sync();
		}
	}
);
