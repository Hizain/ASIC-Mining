<?php
/**
 * Database: a local copy of your Stripe customers (with client numbers), subscriptions and invoices,
 * plus an activity log. Stripe stays the source of truth; webhooks and "Sync with Stripe" keep the
 * copy current so the admin screens are fast and work offline from Stripe.
 *
 * @package MineMirthSubscriptions
 */

defined( 'ABSPATH' ) || exit;

function mms_table( string $name ): string {
	global $wpdb;
	return $wpdb->prefix . 'mms_' . $name;
}

function mms_install(): void {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$c = $wpdb->get_charset_collate();
	dbDelta(
		'CREATE TABLE ' . mms_table( 'customers' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			client_no varchar(32) NOT NULL DEFAULT '',
			stripe_id varchar(64) NOT NULL DEFAULT '',
			mode varchar(4) NOT NULL DEFAULT 'test',
			email varchar(190) NOT NULL DEFAULT '',
			name varchar(190) NOT NULL DEFAULT '',
			phone varchar(64) NOT NULL DEFAULT '',
			address text NULL,
			wp_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			notes text NULL,
			created datetime NOT NULL,
			updated datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY stripe_id (stripe_id),
			KEY client_no (client_no),
			KEY email (email)
		) $c;"
	);
	dbDelta(
		'CREATE TABLE ' . mms_table( 'subscriptions' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			stripe_id varchar(64) NOT NULL DEFAULT '',
			mode varchar(4) NOT NULL DEFAULT 'test',
			customer_id bigint(20) unsigned NOT NULL DEFAULT 0,
			stripe_customer varchar(64) NOT NULL DEFAULT '',
			plan_id bigint(20) unsigned NOT NULL DEFAULT 0,
			price_id varchar(64) NOT NULL DEFAULT '',
			product_id varchar(64) NOT NULL DEFAULT '',
			plan_name varchar(190) NOT NULL DEFAULT '',
			status varchar(32) NOT NULL DEFAULT '',
			quantity int(11) NOT NULL DEFAULT 1,
			amount bigint(20) NOT NULL DEFAULT 0,
			currency varchar(8) NOT NULL DEFAULT 'usd',
			interval_unit varchar(16) NOT NULL DEFAULT 'month',
			interval_count int(11) NOT NULL DEFAULT 1,
			period_start bigint(20) NOT NULL DEFAULT 0,
			period_end bigint(20) NOT NULL DEFAULT 0,
			cancel_at_period_end tinyint(1) NOT NULL DEFAULT 0,
			canceled_at bigint(20) NOT NULL DEFAULT 0,
			started bigint(20) NOT NULL DEFAULT 0,
			default_pm varchar(64) NOT NULL DEFAULT '',
			latest_invoice varchar(64) NOT NULL DEFAULT '',
			data longtext NULL,
			created datetime NOT NULL,
			updated datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY stripe_id (stripe_id),
			KEY customer_id (customer_id),
			KEY status (status),
			KEY period_end (period_end)
		) $c;"
	);
	dbDelta(
		'CREATE TABLE ' . mms_table( 'invoices' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			stripe_id varchar(64) NOT NULL DEFAULT '',
			mode varchar(4) NOT NULL DEFAULT 'test',
			number varchar(64) NOT NULL DEFAULT '',
			customer_id bigint(20) unsigned NOT NULL DEFAULT 0,
			stripe_customer varchar(64) NOT NULL DEFAULT '',
			subscription varchar(64) NOT NULL DEFAULT '',
			status varchar(32) NOT NULL DEFAULT '',
			billing_reason varchar(48) NOT NULL DEFAULT '',
			currency varchar(8) NOT NULL DEFAULT 'usd',
			subtotal bigint(20) NOT NULL DEFAULT 0,
			discount bigint(20) NOT NULL DEFAULT 0,
			tax bigint(20) NOT NULL DEFAULT 0,
			total bigint(20) NOT NULL DEFAULT 0,
			amount_paid bigint(20) NOT NULL DEFAULT 0,
			amount_due bigint(20) NOT NULL DEFAULT 0,
			amount_refunded bigint(20) NOT NULL DEFAULT 0,
			created bigint(20) NOT NULL DEFAULT 0,
			period_start bigint(20) NOT NULL DEFAULT 0,
			period_end bigint(20) NOT NULL DEFAULT 0,
			paid_at bigint(20) NOT NULL DEFAULT 0,
			hosted_url text NULL,
			pdf_url text NULL,
			payment_intent varchar(64) NOT NULL DEFAULT '',
			charge varchar(64) NOT NULL DEFAULT '',
			line_items longtext NULL,
			data longtext NULL,
			updated datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY stripe_id (stripe_id),
			KEY customer_id (customer_id),
			KEY subscription (subscription),
			KEY created (created)
		) $c;"
	);
	dbDelta(
		'CREATE TABLE ' . mms_table( 'log' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			time datetime NOT NULL,
			level varchar(8) NOT NULL DEFAULT 'info',
			type varchar(48) NOT NULL DEFAULT '',
			object varchar(64) NOT NULL DEFAULT '',
			message text NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY object (object),
			KEY time (time)
		) $c;"
	);
	update_option( 'mms_db', MMS_DB, false );
}

/* ------------------------------------------------------------------ Activity log */

function mms_log( string $type, string $message, string $object = '', string $level = 'info' ): void {
	global $wpdb;
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		mms_table( 'log' ),
		array(
			'time'    => current_time( 'mysql', true ),
			'level'   => $level,
			'type'    => substr( $type, 0, 48 ),
			'object'  => substr( $object, 0, 64 ),
			'message' => $message,
			'user_id' => get_current_user_id(),
		)
	);
}

/* ------------------------------------------------------------------ Rows */

function mms_json( $value ): array {
	if ( is_array( $value ) ) {
		return $value;
	}
	$d = json_decode( (string) $value, true );
	return is_array( $d ) ? $d : array();
}

function mms_get( string $table, string $field, $value ): ?array {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mms_table( $table ) . " WHERE {$field} = %s LIMIT 1", (string) $value ), ARRAY_A ); // phpcs:ignore WordPress.DB
	return $row ? $row : null;
}

/**
 * Insert or update a row by its Stripe ID. Returns the local ID.
 */
function mms_upsert( string $table, string $stripe_id, array $fields ): int {
	global $wpdb;
	$fields['updated'] = current_time( 'mysql', true );
	$existing          = mms_get( $table, 'stripe_id', $stripe_id );
	if ( $existing ) {
		$wpdb->update( mms_table( $table ), $fields, array( 'id' => (int) $existing['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $existing['id'];
	}
	$fields['stripe_id'] = $stripe_id;
	if ( 'invoices' !== $table ) {
		$fields['created'] = current_time( 'mysql', true );
	}
	$wpdb->insert( mms_table( $table ), $fields ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	return (int) $wpdb->insert_id;
}

/**
 * Merge flags into a row's "data" column (for example "welcome email sent").
 */
function mms_set_data( string $table, int $id, array $flags ): void {
	global $wpdb;
	$row = mms_get( $table, 'id', $id );
	if ( ! $row ) {
		return;
	}
	$data = array_merge( mms_json( $row['data'] ?? '' ), $flags );
	$wpdb->update( mms_table( $table ), array( 'data' => wp_json_encode( $data ) ), array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/* ------------------------------------------------------------------ Client numbers */

/**
 * The next client number, for example MMC-1001, without using it up.
 */
function mms_peek_client_no(): string {
	return (string) mms_setting( 'client_prefix' ) . (int) get_option( 'mms_next_client', 1001 );
}

/**
 * Take the next client number (skipping any already used).
 */
function mms_take_client_no(): string {
	for ( $i = 0; $i < 100; $i++ ) {
		$no = mms_peek_client_no();
		update_option( 'mms_next_client', (int) get_option( 'mms_next_client', 1001 ) + 1, false );
		if ( ! mms_get( 'customers', 'client_no', $no ) ) {
			return $no;
		}
	}
	return mms_peek_client_no();
}

/* ------------------------------------------------------------------ Money and dates */

function mms_zero_decimal( string $currency ): bool {
	return in_array( strtolower( $currency ), array( 'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf' ), true );
}

/** Stripe's smallest unit (cents) to a decimal amount. */
function mms_from_minor( int $amount, string $currency ): float {
	return mms_zero_decimal( $currency ) ? (float) $amount : $amount / 100;
}

/** A decimal amount to Stripe's smallest unit. */
function mms_to_minor( float $amount, string $currency ): int {
	return (int) round( mms_zero_decimal( $currency ) ? $amount : $amount * 100 );
}

function mms_money( int $minor, string $currency ): string {
	$cur  = strtoupper( $currency );
	$sym  = array( 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'CAD' => 'CA$', 'AUD' => 'A$', 'AED' => 'AED ', 'PKR' => 'Rs ' )[ $cur ] ?? $cur . ' ';
	$dec  = mms_zero_decimal( $currency ) ? 0 : 2;
	$val  = mms_from_minor( abs( $minor ), $currency );
	return ( $minor < 0 ? '-' : '' ) . $sym . number_format( $val, $dec, '.', ',' );
}

function mms_date( int $ts, bool $time = false ): string {
	return $ts ? wp_date( $time ? 'M j, Y g:i a' : 'M j, Y', $ts ) : '—';
}

/**
 * "per month", "every 3 months", "per year".
 */
function mms_interval_label( string $unit, int $count = 1 ): string {
	$units = array(
		'day'   => array( __( 'day', 'mine-mirth-subscriptions' ), __( 'days', 'mine-mirth-subscriptions' ) ),
		'week'  => array( __( 'week', 'mine-mirth-subscriptions' ), __( 'weeks', 'mine-mirth-subscriptions' ) ),
		'month' => array( __( 'month', 'mine-mirth-subscriptions' ), __( 'months', 'mine-mirth-subscriptions' ) ),
		'year'  => array( __( 'year', 'mine-mirth-subscriptions' ), __( 'years', 'mine-mirth-subscriptions' ) ),
	);
	$u = $units[ $unit ] ?? $units['month'];
	/* translators: 1: number, 2: unit */
	return $count > 1 ? sprintf( __( 'every %1$d %2$s', 'mine-mirth-subscriptions' ), $count, $u[1] ) : sprintf( /* translators: %s: unit */ __( 'per %s', 'mine-mirth-subscriptions' ), $u[0] );
}

/**
 * Subscription statuses: Stripe status => [label, colour].
 */
function mms_statuses(): array {
	return array(
		'active'             => array( __( 'Active', 'mine-mirth-subscriptions' ), '#12b981' ),
		'trialing'           => array( __( 'Trial', 'mine-mirth-subscriptions' ), '#35a8f6' ),
		'past_due'           => array( __( 'Past due', 'mine-mirth-subscriptions' ), '#f5a524' ),
		'unpaid'             => array( __( 'Unpaid', 'mine-mirth-subscriptions' ), '#f04461' ),
		'incomplete'         => array( __( 'Incomplete', 'mine-mirth-subscriptions' ), '#94a3b8' ),
		'incomplete_expired' => array( __( 'Expired', 'mine-mirth-subscriptions' ), '#94a3b8' ),
		'paused'             => array( __( 'Paused', 'mine-mirth-subscriptions' ), '#a855f7' ),
		'canceled'           => array( __( 'Cancelled', 'mine-mirth-subscriptions' ), '#64748b' ),
		// Invoices.
		'paid'               => array( __( 'Paid', 'mine-mirth-subscriptions' ), '#12b981' ),
		'open'               => array( __( 'Open', 'mine-mirth-subscriptions' ), '#f5a524' ),
		'draft'              => array( __( 'Draft', 'mine-mirth-subscriptions' ), '#94a3b8' ),
		'void'               => array( __( 'Void', 'mine-mirth-subscriptions' ), '#64748b' ),
		'uncollectible'      => array( __( 'Uncollectible', 'mine-mirth-subscriptions' ), '#f04461' ),
		'refunded'           => array( __( 'Refunded', 'mine-mirth-subscriptions' ), '#64748b' ),
		'partially_refunded' => array( __( 'Partly refunded', 'mine-mirth-subscriptions' ), '#a855f7' ),
	);
}

function mms_pill( string $status ): string {
	$s = mms_statuses()[ $status ] ?? array( ucfirst( str_replace( '_', ' ', $status ) ), '#64748b' );
	return '<span class="mms-pill" style="--c:' . esc_attr( $s[1] ) . '"><i></i>' . esc_html( $s[0] ) . '</span>';
}
