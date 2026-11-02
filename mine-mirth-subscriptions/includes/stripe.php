<?php
/**
 * Stripe API client (no SDK needed): form-encoded requests with your secret key, a pinned API
 * version so responses keep the same shape, idempotency keys for anything that creates or charges,
 * and webhook signature checks.
 *
 * @package MineMirthSubscriptions
 */

defined( 'ABSPATH' ) || exit;

/** The Stripe API version the plugin is written for. */
const MMS_STRIPE_VERSION = '2024-06-20';

/**
 * Current mode: "live" or "test".
 */
function mms_mode(): string {
	return 'live' === mms_setting( 'mode' ) ? 'live' : 'test';
}

function mms_secret_key(): string {
	return trim( (string) mms_setting( mms_mode() . '_secret' ) );
}

function mms_publishable_key(): string {
	return trim( (string) mms_setting( mms_mode() . '_publishable' ) );
}

function mms_webhook_secret(): string {
	return trim( (string) mms_setting( mms_mode() . '_webhook_secret' ) );
}

function mms_connected(): bool {
	return (bool) preg_match( '/^(sk|rk)_(test|live)_/', mms_secret_key() );
}

/**
 * Stripe API base URL (a filter lets a test server stand in for Stripe).
 */
function mms_api_base(): string {
	return rtrim( (string) apply_filters( 'mms_stripe_api_base', 'https://api.stripe.com' ), '/' );
}

/**
 * Flatten nested parameters into Stripe's form encoding: a[b][0]=c. Booleans become "true"/"false".
 */
function mms_encode( array $params, string $prefix = '' ): array {
	$out = array();
	foreach ( $params as $k => $v ) {
		if ( null === $v ) {
			continue;
		}
		$key = '' === $prefix ? (string) $k : $prefix . '[' . $k . ']';
		if ( is_array( $v ) ) {
			if ( ! $v ) {
				$out[ $key ] = ''; // An empty value clears a field in Stripe.
				continue;
			}
			$out += mms_encode( $v, $key );
		} elseif ( is_bool( $v ) ) {
			$out[ $key ] = $v ? 'true' : 'false';
		} else {
			$out[ $key ] = (string) $v;
		}
	}
	return $out;
}

/**
 * Call the Stripe API. Returns the decoded object or a WP_Error with Stripe's message.
 *
 * @param string $method     GET, POST or DELETE.
 * @param string $path       For example "/v1/products".
 * @param array  $params     Request parameters.
 * @param string $idempotent An idempotency key for POSTs that create or charge (Stripe ignores
 *                           a repeat with the same key, so a double click never charges twice).
 * @return array|WP_Error
 */
function mms_stripe( string $method, string $path, array $params = array(), string $idempotent = '', ?string $key = null ) {
	$key = null === $key ? mms_secret_key() : $key;
	if ( '' === $key ) {
		return new WP_Error( 'mms_not_connected', __( 'Stripe is not connected yet. Add your secret key in Subscriptions › Settings.', 'mine-mirth-subscriptions' ) );
	}
	$url     = mms_api_base() . $path;
	$body    = mms_encode( $params );
	$headers = array(
		'Authorization'  => 'Bearer ' . $key,
		'Stripe-Version' => MMS_STRIPE_VERSION,
		'User-Agent'     => 'MineMirthSubscriptions/' . MMS_VERSION . ' (WordPress)',
	);
	if ( 'POST' === $method ) {
		$headers['Content-Type'] = 'application/x-www-form-urlencoded';
		if ( '' !== $idempotent ) {
			$headers['Idempotency-Key'] = substr( 'mms-' . $idempotent, 0, 255 );
		}
	} elseif ( $body ) {
		$url .= ( str_contains( $url, '?' ) ? '&' : '?' ) . http_build_query( $body, '', '&' );
		$body = array();
	}
	$attempt = 0;
	do {
		$response = wp_remote_request(
			$url,
			array(
				'method'  => $method,
				'timeout' => 30,
				'headers' => $headers,
				'body'    => 'POST' === $method ? http_build_query( $body, '', '&' ) : null,
			)
		);
		$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
		// Retry once on network trouble or Stripe 429/5xx (safe: POSTs carry an idempotency key or are updates).
		$retry = ( 0 === $code || 429 === $code || $code >= 500 ) && 0 === $attempt && ( 'POST' !== $method || '' !== $idempotent || str_contains( $path, '/v1/products/' ) || str_contains( $path, '/v1/subscriptions/' ) );
		if ( $retry ) {
			sleep( 1 );
		}
		++$attempt;
	} while ( $retry && $attempt < 2 );

	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'mms_http', sprintf( /* translators: %s: error */ __( 'Could not reach Stripe: %s', 'mine-mirth-subscriptions' ), $response->get_error_message() ) );
	}
	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( $code < 200 || $code >= 300 ) {
		$msg = is_array( $data ) && ! empty( $data['error']['message'] ) ? (string) $data['error']['message'] : sprintf( /* translators: %d: HTTP code */ __( 'Stripe answered with error %d.', 'mine-mirth-subscriptions' ), $code );
		return new WP_Error( 'mms_stripe', $msg, array( 'status' => $code, 'error' => $data['error'] ?? array() ) );
	}
	return is_array( $data ) ? $data : new WP_Error( 'mms_stripe', __( 'Stripe sent an answer the plugin could not read.', 'mine-mirth-subscriptions' ) );
}

/**
 * All pages of a Stripe list (up to $max objects).
 *
 * @return array|WP_Error
 */
function mms_stripe_all( string $path, array $params = array(), int $max = 1000 ) {
	$out              = array();
	$params['limit']  = 100;
	do {
		$page = mms_stripe( 'GET', $path, $params );
		if ( is_wp_error( $page ) ) {
			return $page;
		}
		$data = (array) ( $page['data'] ?? array() );
		$out  = array_merge( $out, $data );
		$last = end( $data );
		if ( ! empty( $page['has_more'] ) && $last ) {
			$params['starting_after'] = $last['id'];
		}
	} while ( ! empty( $page['has_more'] ) && count( $out ) < $max && $data );
	return $out;
}

/**
 * Link to an object in the Stripe Dashboard.
 */
function mms_dashboard_url( string $type, string $id ): string {
	if ( '' === $id ) {
		return '';
	}
	return 'https://dashboard.stripe.com/' . ( 'test' === mms_mode() ? 'test/' : '' ) . $type . '/' . rawurlencode( $id );
}

/**
 * Check a webhook's Stripe-Signature header (HMAC-SHA256 of "timestamp.payload", 5-minute window).
 */
function mms_verify_signature( string $payload, string $header, string $secret, int $tolerance = 300 ): bool {
	if ( '' === $secret || '' === $header ) {
		return false;
	}
	$ts   = 0;
	$sigs = array();
	foreach ( explode( ',', $header ) as $part ) {
		$kv = explode( '=', trim( $part ), 2 );
		if ( 2 !== count( $kv ) ) {
			continue;
		}
		if ( 't' === $kv[0] ) {
			$ts = (int) $kv[1];
		} elseif ( 'v1' === $kv[0] ) {
			$sigs[] = $kv[1];
		}
	}
	if ( ! $ts || ! $sigs || abs( time() - $ts ) > $tolerance ) {
		return false;
	}
	$expected = hash_hmac( 'sha256', $ts . '.' . $payload, $secret );
	foreach ( $sigs as $sig ) {
		if ( hash_equals( $expected, $sig ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Events the webhook listens to.
 */
function mms_webhook_events(): array {
	return array(
		'checkout.session.completed',
		'customer.subscription.created',
		'customer.subscription.updated',
		'customer.subscription.deleted',
		'customer.updated',
		'invoice.finalized',
		'invoice.paid',
		'invoice.payment_failed',
		'invoice.voided',
		'charge.refunded',
	);
}

function mms_webhook_url(): string {
	return rest_url( 'mine-mirth-subscriptions/v1/webhook' );
}
