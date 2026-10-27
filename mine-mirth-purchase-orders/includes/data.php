<?php
/**
 * Purchase order data: settings, statuses, numbering, totals and storage.
 *
 * @package MineMirthPurchaseOrders
 */

defined( 'ABSPATH' ) || exit;

/**
 * Who can work with purchase orders: shop managers and administrators.
 */
function mmpo_cap(): string {
	return (string) apply_filters( 'mmpo_capability', class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options' );
}

/**
 * A Mine Mirth theme setting (Appearance › Customize), or the default.
 *
 * @param mixed $default Fallback.
 * @return mixed
 */
function mmpo_theme_opt( string $key, $default = '' ) {
	$value = function_exists( 'mm_opt' ) ? mm_opt( $key ) : get_theme_mod( 'mm_' . $key, null );
	return null === $value || '' === $value ? $default : $value;
}

/* ------------------------------------------------------------------ Settings */

function mmpo_default_payment(): string {
	return __( 'Pay by bank wire or crypto using only the details on this purchase order or in your Mine Mirth account. We never change payment details by email: call us to confirm before you send funds.', 'mine-mirth-purchase-orders' );
}

function mmpo_default_terms(): string {
	return __( "Prices are in the currency shown and valid until the payment due date. Orders ship once payment has cleared in full. Please inspect your shipment on delivery and tell us about any damage within 48 hours.", 'mine-mirth-purchase-orders' );
}

/**
 * Settings defaults. Company details come from the theme's Contact settings when they are filled in.
 */
function mmpo_default_settings(): array {
	return array(
		'company'      => 'Mine Mirth LLC',
		'address'      => (string) mmpo_theme_opt( 'contact_address', '' ),
		'email'        => (string) mmpo_theme_opt( 'contact_email', 'support@minemirth.com' ),
		'phone'        => (string) mmpo_theme_opt( 'contact_phone', '' ),
		'website'      => '',
		'logo'         => 'mark',
		'logo_id'      => 0,
		'header'       => 'dark',
		'primary'      => (string) mmpo_theme_opt( 'color_primary', '#35a8f6' ),
		'accent'       => (string) mmpo_theme_opt( 'color_accent', '#2563eb' ),
		'highlight'    => (string) mmpo_theme_opt( 'color_accent_2', '#5ee1ff' ),
		'paper'        => 'letter',
		'prefix'       => 'MM-PO-',
		'digits'       => 4,
		'doc'          => 'po',
		'currency'     => function_exists( 'get_woocommerce_currency' ) ? (string) get_woocommerce_currency() : 'USD',
		'due_days'     => 7,
		'payment_terms' => __( 'Payment in full before shipping', 'mine-mirth-purchase-orders' ),
		'payment'      => mmpo_default_payment(),
		'terms'        => mmpo_default_terms(),
		'footer'       => __( 'Thank you for choosing Mine Mirth.', 'mine-mirth-purchase-orders' ),
		'statuses'     => mmpo_default_statuses(),
	);
}

/**
 * Current settings merged with the defaults.
 */
function mmpo_settings(): array {
	static $cache = null;
	if ( null === $cache ) {
		$saved = get_option( 'mmpo_settings', array() );
		$cache = array_merge( mmpo_default_settings(), is_array( $saved ) ? $saved : array() );
		// Company details left empty here come from the website's Contact settings.
		foreach ( array( 'address' => 'contact_address', 'email' => 'contact_email', 'phone' => 'contact_phone' ) as $key => $theme_key ) {
			if ( '' === trim( (string) $cache[ $key ] ) ) {
				$cache[ $key ] = (string) mmpo_theme_opt( $theme_key, 'email' === $key ? 'support@minemirth.com' : '' );
			}
		}
		if ( empty( $cache['statuses'] ) || ! is_array( $cache['statuses'] ) ) {
			$cache['statuses'] = mmpo_default_statuses();
		}
	}
	return $cache;
}

function mmpo_setting( string $key ) {
	$s = mmpo_settings();
	return $s[ $key ] ?? '';
}

/* ------------------------------------------------------------------ Statuses */

/**
 * Default statuses, in workflow order: key => [label, colour, stamp on the PDF].
 */
function mmpo_default_statuses(): array {
	return array(
		'draft'      => array( 'label' => __( 'Draft', 'mine-mirth-purchase-orders' ), 'color' => '#64748b', 'stamp' => __( 'DRAFT', 'mine-mirth-purchase-orders' ) ),
		'sent'       => array( 'label' => __( 'Sent', 'mine-mirth-purchase-orders' ), 'color' => '#35a8f6', 'stamp' => '' ),
		'pending'    => array( 'label' => __( 'Pending payment', 'mine-mirth-purchase-orders' ), 'color' => '#f5a524', 'stamp' => '' ),
		'partial'    => array( 'label' => __( 'Partially paid', 'mine-mirth-purchase-orders' ), 'color' => '#ea8a0c', 'stamp' => '' ),
		'paid'       => array( 'label' => __( 'Paid', 'mine-mirth-purchase-orders' ), 'color' => '#12b981', 'stamp' => __( 'PAID', 'mine-mirth-purchase-orders' ) ),
		'processing' => array( 'label' => __( 'Processing', 'mine-mirth-purchase-orders' ), 'color' => '#2563eb', 'stamp' => '' ),
		'on-hold'    => array( 'label' => __( 'On hold', 'mine-mirth-purchase-orders' ), 'color' => '#a855f7', 'stamp' => '' ),
		'shipped'    => array( 'label' => __( 'Shipped', 'mine-mirth-purchase-orders' ), 'color' => '#0891b2', 'stamp' => '' ),
		'delivered'  => array( 'label' => __( 'Delivered', 'mine-mirth-purchase-orders' ), 'color' => '#10b981', 'stamp' => '' ),
		'completed'  => array( 'label' => __( 'Completed', 'mine-mirth-purchase-orders' ), 'color' => '#059669', 'stamp' => '' ),
		'cancelled'  => array( 'label' => __( 'Cancelled', 'mine-mirth-purchase-orders' ), 'color' => '#f04461', 'stamp' => __( 'CANCELLED', 'mine-mirth-purchase-orders' ) ),
		'refunded'   => array( 'label' => __( 'Refunded', 'mine-mirth-purchase-orders' ), 'color' => '#94a3b8', 'stamp' => __( 'REFUNDED', 'mine-mirth-purchase-orders' ) ),
	);
}

/**
 * Statuses as set in Settings.
 *
 * @return array<string,array{label:string,color:string,stamp:string}>
 */
function mmpo_statuses(): array {
	$out = array();
	foreach ( (array) mmpo_setting( 'statuses' ) as $key => $st ) {
		$key = sanitize_key( (string) $key );
		if ( '' === $key || ! is_array( $st ) || '' === trim( (string) ( $st['label'] ?? '' ) ) ) {
			continue;
		}
		$out[ $key ] = array(
			'label' => (string) $st['label'],
			'color' => sanitize_hex_color( (string) ( $st['color'] ?? '' ) ) ? (string) $st['color'] : '#64748b',
			'stamp' => (string) ( $st['stamp'] ?? '' ),
		);
	}
	return $out ? $out : mmpo_default_statuses();
}

function mmpo_status( string $key ): array {
	$all = mmpo_statuses();
	return $all[ $key ] ?? array( 'label' => ucfirst( str_replace( '-', ' ', $key ) ), 'color' => '#64748b', 'stamp' => '' );
}

/**
 * Statuses that still need work or money (used for the "open" figures on the list screen).
 */
function mmpo_closed_statuses(): array {
	return (array) apply_filters( 'mmpo_closed_statuses', array( 'draft', 'completed', 'delivered', 'cancelled', 'refunded' ) );
}

/* ------------------------------------------------------------------ Documents and currencies */

/**
 * Titles a purchase order can carry on the PDF.
 */
function mmpo_doc_types(): array {
	return array(
		'po'       => __( 'Purchase order', 'mine-mirth-purchase-orders' ),
		'invoice'  => __( 'Invoice', 'mine-mirth-purchase-orders' ),
		'proforma' => __( 'Proforma invoice', 'mine-mirth-purchase-orders' ),
		'quote'    => __( 'Quote', 'mine-mirth-purchase-orders' ),
	);
}

/**
 * Currencies: code => [symbol, decimals, symbol after the amount].
 */
function mmpo_currencies(): array {
	return array(
		'USD'  => array( '$', 2, false ),
		'EUR'  => array( '€', 2, false ),
		'GBP'  => array( '£', 2, false ),
		'CAD'  => array( 'CA$', 2, false ),
		'AUD'  => array( 'A$', 2, false ),
		'AED'  => array( 'AED ', 2, false ),
		'SAR'  => array( 'SAR ', 2, false ),
		'PKR'  => array( 'Rs ', 0, false ),
		'INR'  => array( '₹', 2, false ),
		'CNY'  => array( 'CN¥', 2, false ),
		'HKD'  => array( 'HK$', 2, false ),
		'SGD'  => array( 'S$', 2, false ),
		'USDT' => array( ' USDT', 2, true ),
		'USDC' => array( ' USDC', 2, true ),
		'BTC'  => array( ' BTC', 8, true ),
	);
}

function mmpo_currency( string $code ): array {
	$all = mmpo_currencies();
	if ( isset( $all[ $code ] ) ) {
		return $all[ $code ];
	}
	return array( $code . ' ', 2, false );
}

/**
 * Format an amount, for example $12,450.00 or 0.25000000 BTC.
 */
function mmpo_money( float $amount, string $code ): string {
	list( $symbol, $decimals, $after ) = mmpo_currency( $code );
	$neg = $amount < 0;
	$num = number_format( abs( $amount ), $decimals, '.', ',' );
	$out = $after ? $num . $symbol : $symbol . $num;
	return ( $neg ? '-' : '' ) . $out;
}

/**
 * A positive number from user input ("1,234.50" → 1234.5).
 */
function mmpo_num( $value ): float {
	$value = preg_replace( '/[^0-9.\-]/', '', str_replace( ',', '', (string) $value ) );
	return is_numeric( $value ) ? (float) $value : 0.0;
}

function mmpo_date( string $ymd ): string {
	$t = $ymd ? strtotime( $ymd . ' 12:00:00' ) : false;
	return $t ? wp_date( 'M j, Y', $t, new DateTimeZone( 'UTC' ) ) : '';
}

/* ------------------------------------------------------------------ The purchase order */

/**
 * Fields of an empty purchase order, with today's date and the default texts.
 */
function mmpo_blank(): array {
	$s     = mmpo_settings();
	$today = wp_date( 'Y-m-d' );
	$days  = max( 0, (int) $s['due_days'] );
	return array(
		'number'        => '',
		'doc'           => isset( mmpo_doc_types()[ $s['doc'] ] ) ? $s['doc'] : 'po',
		'date'          => $today,
		'due'           => $days ? wp_date( 'Y-m-d', time() + $days * DAY_IN_SECONDS ) : '',
		'reference'     => '',
		'payment_terms' => (string) $s['payment_terms'],
		'currency'      => (string) $s['currency'],
		'status'        => 'draft',
		'status_time'   => time(),
		'show_status'   => 1,
		'history'       => array(),
		'ship'          => array(
			'name'      => '',
			'company'   => '',
			'address_1' => '',
			'address_2' => '',
			'city'      => '',
			'state'     => '',
			'postcode'  => '',
			'country'   => 'US',
			'phone'     => '',
			'email'     => '',
		),
		'items'         => array(),
		'shipping'      => array( 'label' => __( 'Shipping', 'mine-mirth-purchase-orders' ), 'amount' => 0 ),
		'fees'          => array(),
		'taxes'         => array(),
		'tax_all'       => 0,
		'paid'          => 0,
		'paid_note'     => '',
		'tracking'      => array( 'carrier' => '', 'number' => '' ),
		'notes'         => '',
		'payment'       => (string) $s['payment'],
		'terms'         => (string) $s['terms'],
		'internal'      => '',
	);
}

/**
 * A purchase order's fields, or null.
 */
function mmpo_get( int $id ): ?array {
	$post = $id ? get_post( $id ) : null;
	if ( ! $post || 'mmpo_order' !== $post->post_type ) {
		return null;
	}
	$data = get_post_meta( $id, '_mmpo', true );
	$data = is_array( $data ) ? $data : array();
	$base = mmpo_blank();
	$out  = array_merge( $base, $data );
	foreach ( array( 'ship', 'shipping', 'tracking' ) as $group ) {
		$out[ $group ] = array_merge( $base[ $group ], is_array( $data[ $group ] ?? null ) ? $data[ $group ] : array() );
	}
	foreach ( array( 'items', 'fees', 'taxes', 'history' ) as $list ) {
		$out[ $list ] = is_array( $data[ $list ] ?? null ) ? array_values( $data[ $list ] ) : array();
	}
	$out['id'] = $id;
	return $out;
}

/**
 * Clean fields coming from the editor form.
 */
function mmpo_sanitize( array $in, array $old ): array {
	$d      = $old;
	$text   = static fn( $v ) => sanitize_text_field( wp_unslash( (string) $v ) );
	$area   = static fn( $v ) => sanitize_textarea_field( wp_unslash( (string) $v ) );
	$d['number']        = $text( $in['number'] ?? $old['number'] );
	$d['doc']           = isset( mmpo_doc_types()[ $in['doc'] ?? '' ] ) ? (string) $in['doc'] : $old['doc'];
	$d['date']          = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $in['date'] ?? '' ) ) ? (string) $in['date'] : $old['date'];
	$d['due']           = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $in['due'] ?? '' ) ) ? (string) $in['due'] : '';
	$d['reference']     = $text( $in['reference'] ?? '' );
	$d['payment_terms'] = $text( $in['payment_terms'] ?? '' );
	$cur                = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) ( $in['currency'] ?? '' ) ) );
	$d['currency']      = '' !== $cur ? substr( $cur, 0, 5 ) : $old['currency'];
	$d['show_status']   = empty( $in['show_status'] ) ? 0 : 1;

	foreach ( array_keys( $old['ship'] ) as $k ) {
		$d['ship'][ $k ] = 'email' === $k ? sanitize_email( wp_unslash( (string) ( $in['ship'][ $k ] ?? '' ) ) ) : $text( $in['ship'][ $k ] ?? '' );
	}

	$d['items'] = array();
	foreach ( (array) ( $in['items'] ?? array() ) as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$name = $text( $row['name'] ?? '' );
		if ( '' === $name ) {
			continue;
		}
		$d['items'][] = array(
			'product_id' => absint( $row['product_id'] ?? 0 ),
			'name'       => $name,
			'sku'        => $text( $row['sku'] ?? '' ),
			'desc'       => $area( $row['desc'] ?? '' ),
			'qty'        => max( 0, mmpo_num( $row['qty'] ?? 1 ) ),
			'price'      => mmpo_num( $row['price'] ?? 0 ),
		);
	}

	$d['shipping'] = array(
		'label'  => $text( $in['shipping']['label'] ?? '' ),
		'amount' => mmpo_num( $in['shipping']['amount'] ?? 0 ),
	);
	foreach ( array( 'fees', 'taxes' ) as $list ) {
		$d[ $list ] = array();
		foreach ( (array) ( $in[ $list ] ?? array() ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = $text( $row['label'] ?? '' );
			$value = mmpo_num( $row['value'] ?? 0 );
			if ( '' === $label && ! $value ) {
				continue;
			}
			$d[ $list ][] = array(
				'label' => '' !== $label ? $label : ( 'fees' === $list ? __( 'Fee', 'mine-mirth-purchase-orders' ) : __( 'Tax', 'mine-mirth-purchase-orders' ) ),
				'type'  => 'percent' === ( $row['type'] ?? '' ) ? 'percent' : 'fixed',
				'value' => $value,
			);
		}
	}
	$d['tax_all']   = empty( $in['tax_all'] ) ? 0 : 1;
	$d['paid']      = mmpo_num( $in['paid'] ?? 0 );
	$d['paid_note'] = $text( $in['paid_note'] ?? '' );
	$d['tracking']  = array(
		'carrier' => $text( $in['tracking']['carrier'] ?? '' ),
		'number'  => $text( $in['tracking']['number'] ?? '' ),
	);
	$d['notes']    = $area( $in['notes'] ?? '' );
	$d['payment']  = $area( $in['payment'] ?? '' );
	$d['terms']    = $area( $in['terms'] ?? '' );
	$d['internal'] = $area( $in['internal'] ?? '' );

	$status = sanitize_key( (string) ( $in['status'] ?? $old['status'] ) );
	if ( ! isset( mmpo_statuses()[ $status ] ) ) {
		$status = $old['status'];
	}
	$note = $text( $in['status_note'] ?? '' );
	if ( $status !== $old['status'] || '' !== $note || ! $old['history'] ) {
		$d['history'][] = array(
			's' => $status,
			't' => time(),
			'u' => get_current_user_id(),
			'n' => $note,
		);
		if ( $status !== $old['status'] || ! $old['history'] ) {
			$d['status_time'] = time();
		}
	}
	$d['status'] = $status;
	return $d;
}

/**
 * All the money on a purchase order.
 *
 * @return array{lines:array,subtotal:float,shipping:float,fees:array,fee_total:float,taxable:float,taxes:array,tax_total:float,total:float,paid:float,balance:float,decimals:int}
 */
function mmpo_totals( array $d ): array {
	$dec      = (int) mmpo_currency( (string) $d['currency'] )[1];
	$r        = static fn( float $v ) => round( $v, $dec );
	$lines    = array();
	$subtotal = 0.0;
	foreach ( $d['items'] as $item ) {
		$amount   = $r( (float) $item['qty'] * (float) $item['price'] );
		$lines[]  = $amount;
		$subtotal += $amount;
	}
	$shipping = $r( (float) ( $d['shipping']['amount'] ?? 0 ) );
	$fees     = array();
	$fee_sum  = 0.0;
	foreach ( $d['fees'] as $fee ) {
		$amount  = 'percent' === $fee['type'] ? $r( $subtotal * (float) $fee['value'] / 100 ) : $r( (float) $fee['value'] );
		$fees[]  = array( 'label' => $fee['label'], 'amount' => $amount, 'rate' => 'percent' === $fee['type'] ? (float) $fee['value'] : null );
		$fee_sum += $amount;
	}
	$taxable = $subtotal + ( ! empty( $d['tax_all'] ) ? $shipping + $fee_sum : 0 );
	$taxes   = array();
	$tax_sum = 0.0;
	foreach ( $d['taxes'] as $tax ) {
		$amount  = 'percent' === $tax['type'] ? $r( $taxable * (float) $tax['value'] / 100 ) : $r( (float) $tax['value'] );
		$taxes[] = array( 'label' => $tax['label'], 'amount' => $amount, 'rate' => 'percent' === $tax['type'] ? (float) $tax['value'] : null );
		$tax_sum += $amount;
	}
	$total = $r( $subtotal + $shipping + $fee_sum + $tax_sum );
	$paid  = $r( (float) $d['paid'] );
	return array(
		'lines'     => $lines,
		'subtotal'  => $r( $subtotal ),
		'shipping'  => $shipping,
		'fees'      => $fees,
		'fee_total' => $r( $fee_sum ),
		'taxable'   => $r( $taxable ),
		'taxes'     => $taxes,
		'tax_total' => $r( $tax_sum ),
		'total'     => $total,
		'paid'      => $paid,
		'balance'   => $r( $total - $paid ),
		'decimals'  => $dec,
	);
}

/**
 * The next automatic number, without using it up.
 */
function mmpo_peek_number(): string {
	$n = max( 1, (int) get_option( 'mmpo_next_number', 1001 ) );
	return (string) mmpo_setting( 'prefix' ) . str_pad( (string) $n, max( 1, min( 8, (int) mmpo_setting( 'digits' ) ) ), '0', STR_PAD_LEFT );
}

/**
 * Take the next automatic number (skipping any already used).
 */
function mmpo_take_number(): string {
	for ( $i = 0; $i < 50; $i++ ) {
		$number = mmpo_peek_number();
		update_option( 'mmpo_next_number', max( 1, (int) get_option( 'mmpo_next_number', 1001 ) ) + 1, false );
		if ( ! mmpo_number_in_use( $number ) ) {
			return $number;
		}
	}
	return mmpo_peek_number();
}

/**
 * ID of another purchase order using this number, or 0.
 */
function mmpo_number_in_use( string $number, int $except = 0 ): int {
	$ids = get_posts(
		array(
			'post_type'        => 'mmpo_order',
			'post_status'      => array( 'publish', 'trash' ),
			'posts_per_page'   => 2,
			'fields'           => 'ids',
			'meta_key'         => '_mmpo_number', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'       => $number, // phpcs:ignore WordPress.DB.SlowDBQuery
			'suppress_filters' => true,
		)
	);
	foreach ( $ids as $id ) {
		if ( (int) $id !== $except ) {
			return (int) $id;
		}
	}
	return 0;
}

/**
 * Save a purchase order. Returns its ID.
 */
function mmpo_save( array $d, int $id = 0 ): int {
	if ( '' === trim( (string) $d['number'] ) ) {
		$d['number'] = mmpo_take_number();
	}
	$totals = mmpo_totals( $d );
	$title  = $d['number'] . ( '' !== $d['ship']['name'] ? ' · ' . $d['ship']['name'] : '' );
	$post   = array(
		'post_type'   => 'mmpo_order',
		'post_status' => 'publish',
		'post_title'  => $title,
	);
	if ( $id ) {
		$post['ID'] = $id;
		wp_update_post( wp_slash( $post ) );
	} else {
		$id = (int) wp_insert_post( wp_slash( $post ) );
	}
	if ( ! $id ) {
		return 0;
	}
	unset( $d['id'] );
	update_post_meta( $id, '_mmpo', wp_slash( $d ) );
	update_post_meta( $id, '_mmpo_number', $d['number'] );
	update_post_meta( $id, '_mmpo_status', $d['status'] );
	update_post_meta( $id, '_mmpo_date', $d['date'] );
	update_post_meta( $id, '_mmpo_total', $totals['total'] );
	update_post_meta( $id, '_mmpo_balance', $totals['balance'] );
	$search = array( $d['number'], $d['reference'], $d['ship']['name'], $d['ship']['company'], $d['ship']['email'], $d['ship']['phone'], $d['ship']['city'] );
	foreach ( $d['items'] as $item ) {
		$search[] = $item['name'];
		$search[] = $item['sku'];
	}
	update_post_meta( $id, '_mmpo_search', mb_strtolower( implode( ' ', array_filter( $search ) ) ) );
	return $id;
}

/**
 * Copy a purchase order as a new draft with a new number and today's date.
 */
function mmpo_duplicate( int $id ): int {
	$d = mmpo_get( $id );
	if ( ! $d ) {
		return 0;
	}
	$blank             = mmpo_blank();
	$d['number']       = '';
	$d['date']         = $blank['date'];
	$d['due']          = $blank['due'];
	$d['status']       = 'draft';
	$d['status_time']  = time();
	$d['history']      = array( array( 's' => 'draft', 't' => time(), 'u' => get_current_user_id(), 'n' => sprintf( /* translators: %s: number */ __( 'Copied from %s', 'mine-mirth-purchase-orders' ), (string) get_post_meta( $id, '_mmpo_number', true ) ) ) );
	$d['paid']         = 0;
	$d['paid_note']    = '';
	$d['tracking']     = $blank['tracking'];
	return mmpo_save( $d );
}

/**
 * One-line address: "Portland, OR 97201, United States".
 */
function mmpo_country_name( string $code ): string {
	if ( '' === $code ) {
		return '';
	}
	if ( function_exists( 'WC' ) && WC()->countries ) {
		$all = WC()->countries->get_countries();
		if ( isset( $all[ $code ] ) ) {
			// WooCommerce writes some names with their code, "United States (US)".
			return (string) preg_replace( '/\s*\([A-Z]{2,3}\)$/', '', html_entity_decode( (string) $all[ $code ], ENT_QUOTES, 'UTF-8' ) );
		}
	}
	return array( 'US' => 'United States', 'CA' => 'Canada', 'GB' => 'United Kingdom', 'AE' => 'United Arab Emirates', 'PK' => 'Pakistan' )[ $code ] ?? $code;
}

/**
 * Ship-to address as lines for the PDF.
 *
 * @return string[]
 */
function mmpo_address_lines( array $ship ): array {
	$lines = array();
	foreach ( array( 'address_1', 'address_2' ) as $k ) {
		if ( '' !== trim( (string) $ship[ $k ] ) ) {
			$lines[] = trim( (string) $ship[ $k ] );
		}
	}
	$city = trim( (string) $ship['city'] );
	$st   = trim( (string) $ship['state'] );
	if ( $st && function_exists( 'WC' ) && WC()->countries && $ship['country'] ) {
		$states = WC()->countries->get_states( $ship['country'] );
		// Two-letter US and Canadian codes read better on a label than the full name.
		if ( is_array( $states ) && isset( $states[ $st ] ) && ! in_array( $ship['country'], array( 'US', 'CA', 'AU' ), true ) ) {
			$st = html_entity_decode( (string) $states[ $st ], ENT_QUOTES, 'UTF-8' );
		}
	}
	$line = trim( $city . ( $city && ( $st || $ship['postcode'] ) ? ', ' : '' ) . trim( $st . ' ' . $ship['postcode'] ) );
	if ( '' !== $line ) {
		$lines[] = $line;
	}
	$country = mmpo_country_name( (string) $ship['country'] );
	if ( '' !== $country ) {
		$lines[] = $country;
	}
	return $lines;
}

/**
 * Specs line for a WooCommerce product: "270 TH/s · 3,510 W · 13.0 J/TH".
 */
function mmpo_product_specs( $product ): string {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}
	$parts = array();
	if ( function_exists( 'mm_product_spec_chips' ) ) {
		foreach ( mm_product_spec_chips( $product, 4 ) as $chip ) {
			$parts[] = (string) $chip[1];
		}
	}
	if ( ! $parts ) {
		foreach ( $product->get_attributes() as $attr ) {
			if ( is_object( $attr ) && method_exists( $attr, 'get_visible' ) && $attr->get_visible() ) {
				$value = $product->get_attribute( $attr->get_name() );
				if ( '' !== $value ) {
					$parts[] = wp_trim_words( $value, 4, '' );
				}
			}
			if ( count( $parts ) >= 4 ) {
				break;
			}
		}
	}
	return implode( ' · ', $parts );
}
