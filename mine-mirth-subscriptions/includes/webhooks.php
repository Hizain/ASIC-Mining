<?php
/**
 * Webhook: Stripe tells the site about checkouts, renewals, failed payments, refunds and
 * cancellations. Every request is checked against the signing secret and each event is handled once.
 *
 * @package MineMirthSubscriptions
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'mine-mirth-subscriptions/v1',
			'/webhook',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => 'mms_webhook',
			)
		);
	}
);

function mms_webhook( WP_REST_Request $request ): WP_REST_Response {
	$payload = (string) $request->get_body();
	$header  = (string) $request->get_header( 'stripe-signature' );
	$secrets = array_filter( array( trim( (string) mms_setting( 'test_webhook_secret' ) ), trim( (string) mms_setting( 'live_webhook_secret' ) ) ) );
	$ok      = false;
	foreach ( $secrets as $secret ) {
		if ( mms_verify_signature( $payload, $header, $secret ) ) {
			$ok = true;
			break;
		}
	}
	if ( ! $ok ) {
		return new WP_REST_Response( array( 'error' => 'invalid signature' ), 400 );
	}
	$event = json_decode( $payload, true );
	if ( ! is_array( $event ) || empty( $event['id'] ) || empty( $event['type'] ) ) {
		return new WP_REST_Response( array( 'error' => 'invalid event' ), 400 );
	}
	// Events for the other mode are acknowledged but not stored (test data never mixes with live).
	if ( ( ! empty( $event['livemode'] ) ? 'live' : 'test' ) !== mms_mode() ) {
		return new WP_REST_Response( array( 'ignored' => 'other mode' ), 200 );
	}
	// Stripe may send an event more than once; handle each one once.
	if ( get_transient( 'mms_evt_' . md5( (string) $event['id'] ) ) ) {
		return new WP_REST_Response( array( 'duplicate' => true ), 200 );
	}
	set_transient( 'mms_evt_' . md5( (string) $event['id'] ), 1, 3 * DAY_IN_SECONDS );
	update_option( 'mms_last_webhook', time(), false );

	try {
		mms_handle_event( (string) $event['type'], mms_event_object( $event ) );
	} catch ( Throwable $e ) {
		delete_transient( 'mms_evt_' . md5( (string) $event['id'] ) ); // Let Stripe retry.
		mms_log( 'webhook', sprintf( '%s failed: %s', $event['type'], $e->getMessage() ), (string) $event['id'], 'error' );
		return new WP_REST_Response( array( 'error' => 'handler failed' ), 500 );
	}
	return new WP_REST_Response( array( 'received' => true ), 200 );
}

/**
 * The event's object in the API version this plugin speaks. A webhook added by hand in the
 * Stripe Dashboard sends events in the account's default version, whose objects can be shaped
 * differently, so those objects are read again through the API.
 */
function mms_event_object( array $event ): array {
	$obj = (array) ( $event['data']['object'] ?? array() );
	if ( ( $event['api_version'] ?? MMS_STRIPE_VERSION ) === MMS_STRIPE_VERSION || empty( $obj['id'] ) ) {
		return $obj;
	}
	$paths = array(
		'subscription' => '/v1/subscriptions/',
		'invoice'      => '/v1/invoices/',
		'charge'       => '/v1/charges/',
		'customer'     => '/v1/customers/',
	);
	$kind = (string) ( $obj['object'] ?? '' );
	if ( ! isset( $paths[ $kind ] ) ) {
		return $obj;
	}
	$fresh = mms_stripe( 'GET', $paths[ $kind ] . rawurlencode( (string) $obj['id'] ), 'charge' === $kind ? array( 'expand' => array( 'refunds' ) ) : array() );
	if ( is_wp_error( $fresh ) ) {
		throw new RuntimeException( $fresh->get_error_message() ); // Stripe retries the event later.
	}
	return $fresh;
}

function mms_handle_event( string $type, array $obj ): void {
	switch ( $type ) {
		case 'checkout.session.completed':
			if ( 'subscription' === ( $obj['mode'] ?? '' ) ) {
				mms_process_checkout( (string) $obj['id'] );
			}
			break;
		case 'customer.subscription.created':
		case 'customer.subscription.updated':
		case 'customer.subscription.deleted':
			mms_sync_subscription( $obj );
			break;
		case 'customer.updated':
			if ( mms_get( 'customers', 'stripe_id', (string) ( $obj['id'] ?? '' ) ) ) {
				mms_sync_customer( $obj );
			}
			break;
		case 'invoice.finalized':
		case 'invoice.voided':
			mms_sync_invoice( $obj );
			break;
		case 'invoice.paid':
			$id = mms_sync_invoice( $obj );
			mms_log( 'payment', sprintf( 'Invoice %s paid: %s', (string) ( $obj['number'] ?? $obj['id'] ), mms_money( (int) ( $obj['amount_paid'] ?? 0 ), (string) ( $obj['currency'] ?? 'usd' ) ) ), (string) $obj['id'] );
			// Renewals move the period on: refresh the subscription too.
			$sub = mms_id( $obj['subscription'] ?? '' );
			if ( '' !== $sub ) {
				$s = mms_stripe( 'GET', '/v1/subscriptions/' . rawurlencode( $sub ) );
				if ( ! is_wp_error( $s ) ) {
					mms_sync_subscription( $s );
				}
			}
			do_action( 'mms_invoice_paid', $id );
			break;
		case 'invoice.payment_failed':
			$id = mms_sync_invoice( $obj );
			mms_log( 'payment', sprintf( 'Payment failed for invoice %s (%s).', (string) ( $obj['number'] ?? $obj['id'] ), mms_money( (int) ( $obj['amount_due'] ?? 0 ), (string) ( $obj['currency'] ?? 'usd' ) ) ), (string) $obj['id'], 'warn' );
			do_action( 'mms_invoice_failed', $id );
			break;
		case 'charge.refunded':
			mms_charge_refunded( $obj );
			break;
	}
}

/**
 * A refund, from WordPress or the Stripe Dashboard: update the invoice and tell the customer once.
 */
function mms_charge_refunded( array $charge ): void {
	global $wpdb;
	$cid = (string) ( $charge['id'] ?? '' );
	$pi  = mms_id( $charge['payment_intent'] ?? '' );
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mms_table( 'invoices' ) . ' WHERE charge = %s OR ( payment_intent <> \'\' AND payment_intent = %s ) LIMIT 1', $cid, $pi ), ARRAY_A ); // phpcs:ignore WordPress.DB
	if ( ! $row && ! empty( $charge['invoice'] ) ) {
		$row = mms_get( 'invoices', 'stripe_id', mms_id( $charge['invoice'] ) );
	}
	if ( ! $row ) {
		return;
	}
	$before = (int) $row['amount_refunded'];
	$after  = (int) ( $charge['amount_refunded'] ?? 0 );
	$wpdb->update( mms_table( 'invoices' ), array( 'amount_refunded' => $after, 'charge' => $cid ), array( 'id' => (int) $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	if ( $after > $before ) {
		// Refunds made in WordPress already emailed the customer.
		if ( ! get_transient( 'mms_local_refund_' . md5( $row['stripe_id'] . '|' . $after ) ) ) {
			mms_log( 'refund', sprintf( 'Refund of %s on invoice %s (from Stripe).', mms_money( $after - $before, (string) $row['currency'] ), (string) ( $row['number'] ?: $row['stripe_id'] ) ), (string) $row['stripe_id'] );
			do_action( 'mms_refunded', (int) $row['id'], $after - $before );
		}
	}
}
