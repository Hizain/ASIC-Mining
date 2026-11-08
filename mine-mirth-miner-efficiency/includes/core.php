<?php
/**
 * Settings, hashrate/power reading and the efficiency calculation.
 *
 * Efficiency = power (W) ÷ hashrate, in the unit used for the miner's algorithm.
 * Example: Antminer L9, 3,360 W at 16 GH/s = 16,000 MH/s → 3,360 ÷ 16,000 = 0.21 J/MH.
 *
 * @package MineMirthMinerEfficiency
 */

defined( 'ABSPATH' ) || exit;

/** Units: label => hashes (or solutions) per second in one unit. */
function mmeff_units(): array {
	return array(
		'J/PH'   => 1e15,
		'J/TH'   => 1e12,
		'J/GH'   => 1e9,
		'J/MH'   => 1e6,
		'J/kH'   => 1e3,
		'J/kSol' => 1e3,
		'J/Sol'  => 1,
	);
}

function mmeff_algorithms(): array {
	return array(
		'sha256'     => 'SHA-256 (Bitcoin)',
		'scrypt'     => 'Scrypt (Litecoin, Dogecoin)',
		'kheavyhash' => 'kHeavyHash (Kaspa)',
		'blake3'     => 'Blake3 (Alephium)',
		'etchash'    => 'Etchash (Ethereum Classic)',
		'equihash'   => 'Equihash (Zcash)',
		'x11'        => 'X11 (Dash)',
		'other'      => 'Other',
	);
}

function mmeff_defaults(): array {
	return array(
		'enabled'   => 1,
		'auto'      => 1,
		'table'     => 1,
		'cards'     => 0,
		'decimals'  => 'auto',
		'units'     => array(
			'sha256'     => 'J/TH',
			'scrypt'     => 'J/MH',
			'kheavyhash' => 'J/TH',
			'blake3'     => 'J/GH',
			'etchash'    => 'J/MH',
			'equihash'   => 'J/kSol',
			'x11'        => 'J/GH',
			'other'      => 'listed',
		),
	);
}

function mmeff_settings( bool $fresh = false ): array {
	static $s = null;
	if ( null === $s || $fresh ) {
		$saved      = (array) get_option( 'mmeff_settings', array() );
		$d          = mmeff_defaults();
		$s          = wp_parse_args( $saved, $d );
		$s['units'] = wp_parse_args( (array) ( $saved['units'] ?? array() ), $d['units'] );
	}
	return $s;
}

/**
 * Parse "16 GH/s", "1.2 PH", "360GH", "21 TH/s", "120 kSol/s" into hashes per second.
 *
 * @return array{hs:float,unit:string} hs = hashes (or solutions) per second; unit = the J/x unit as listed.
 */
function mmeff_parse_hashrate( string $raw ): array {
	$raw = trim( $raw );
	if ( '' === $raw || ! preg_match( '/([\d][\d.,\s]*)\s*([PTGMKk]?)\s*(H|Sol)\b/u', $raw, $m ) ) {
		// A bare number: read it as TH/s (the theme does the same).
		if ( preg_match( '/^[\d.,\s]+$/', $raw ) && (float) str_replace( ',', '', $raw ) > 0 ) {
			return array( 'hs' => (float) str_replace( array( ',', ' ' ), '', $raw ) * 1e12, 'unit' => 'J/TH' );
		}
		return array( 'hs' => 0.0, 'unit' => '' );
	}
	$num    = (float) str_replace( array( ',', ' ' ), '', $m[1] );
	$prefix = strtoupper( $m[2] );
	$sol    = 'Sol' === $m[3];
	$mult   = array( 'P' => 1e15, 'T' => 1e12, 'G' => 1e9, 'M' => 1e6, 'K' => 1e3, '' => 1 )[ $prefix ] ?? 1;
	$unit   = $sol ? ( 'K' === $prefix ? 'J/kSol' : 'J/Sol' ) : 'J/' . ( 'K' === $prefix ? 'k' : $prefix ) . 'H';
	if ( 'J/H' === $unit ) {
		$unit = 'J/kH';
	}
	return array( 'hs' => $num * $mult, 'unit' => $unit );
}

function mmeff_parse_watts( string $raw ): float {
	if ( preg_match( '/([\d][\d.,]*)\s*(k?)W/i', $raw, $m ) ) {
		return (float) str_replace( ',', '', $m[1] ) * ( '' !== $m[2] ? 1000 : 1 );
	}
	return (float) str_replace( ',', '', preg_replace( '/[^\d.,]/', '', $raw ) );
}

/** First non-empty attribute value from a list of names. */
function mmeff_attr( WC_Product $p, array $names ): string {
	foreach ( $names as $n ) {
		$v = trim( explode( ',', (string) $p->get_attribute( $n ) )[0] );
		if ( '' !== $v ) {
			return $v;
		}
	}
	return '';
}

function mmeff_algorithm( WC_Product $p, string $hash_raw ): string {
	if ( function_exists( 'mm_product_algorithm' ) ) {
		$a = (string) mm_product_algorithm( $p, $hash_raw );
		return isset( mmeff_algorithms()[ $a ] ) ? $a : 'other';
	}
	$text = strtolower( mmeff_attr( $p, array( 'algorithm', 'coin' ) ) . ' ' . $p->get_name() );
	foreach ( array( 'sha256' => '/sha-?256|bitcoin|\bbtc\b/', 'scrypt' => '/scrypt|litecoin|doge/', 'kheavyhash' => '/kheavy|kaspa/', 'blake3' => '/blake3|alephium/', 'etchash' => '/etchash|ethash/', 'equihash' => '/equihash|zcash/', 'x11' => '/\bx11\b|\bdash\b/' ) as $k => $re ) {
		if ( preg_match( $re, $text ) ) {
			return $k;
		}
	}
	return 'other';
}

/** "0.21", "13.5", "150" — three significant figures, or a fixed number of decimals. */
function mmeff_format( float $v ): string {
	$dec = (string) mmeff_settings()['decimals'];
	if ( 'auto' !== $dec ) {
		return number_format_i18n( $v, (int) $dec );
	}
	if ( $v >= 100 ) {
		$d = 0;
	} elseif ( $v >= 10 ) {
		$d = 1;
	} elseif ( $v >= 1 ) {
		$d = 2;
	} else {
		$d = max( 2, (int) ceil( -log10( $v ) ) + 2 );
	}
	$out = number_format_i18n( $v, $d );
	// Trim trailing zeros after the decimal point ("0.50" → "0.5", "13.0" → "13").
	$point = wp_specialchars_decode( (string) ( $GLOBALS['wp_locale']->number_format['decimal_point'] ?? '.' ) );
	if ( false !== strpos( $out, $point ) ) {
		$out = rtrim( rtrim( $out, '0' ), $point );
	}
	return $out;
}

/** Is the product ticked (its own choice, or the default for new products)? */
function mmeff_product_on( int $product_id ): bool {
	$flag = (string) get_post_meta( $product_id, '_mmeff_on', true );
	return 'yes' === $flag || ( '' === $flag && mmeff_settings()['auto'] );
}

/** Is the corrected efficiency shown for this product? */
function mmeff_is_on( int $product_id ): bool {
	return (bool) mmeff_settings()['enabled'] && mmeff_product_on( $product_id );
}

/**
 * Everything about a product's efficiency.
 *
 * @return array{product:int,algo:string,hash_raw:string,watts_raw:string,hs:float,watts:float,unit:string,value:float,text:string,theme:string,custom:string,on:bool}
 */
function mmeff_info( WC_Product $p ): array {
	static $cache = array();
	$id = (int) $p->get_id();
	if ( isset( $cache[ $id ] ) ) {
		return $cache[ $id ];
	}
	$GLOBALS['mmeff_reading'] = true; // Reading attributes must not re-enter the attribute filter.
	$hash_raw  = trim( (string) get_post_meta( $id, '_mmeff_hash', true ) );
	$watts_raw = trim( (string) get_post_meta( $id, '_mmeff_watts', true ) );
	$det_hash  = mmeff_attr( $p, array( 'hashrate', 'hash-rate', 'hash_rate', 'hash rate' ) );
	$det_watts = mmeff_attr( $p, array( 'power', 'power-consumption', 'power consumption', 'wattage' ) );
	$algo      = mmeff_algorithm( $p, '' !== $hash_raw ? $hash_raw : $det_hash );
	$GLOBALS['mmeff_reading'] = false;

	$h      = mmeff_parse_hashrate( '' !== $hash_raw ? $hash_raw : $det_hash );
	$watts  = mmeff_parse_watts( '' !== $watts_raw ? $watts_raw : $det_watts );
	$s      = mmeff_settings();
	$unit   = (string) get_post_meta( $id, '_mmeff_unit', true );
	if ( '' === $unit || ! isset( mmeff_units()[ $unit ] ) ) {
		$unit = (string) ( $s['units'][ $algo ] ?? 'listed' );
	}
	if ( 'listed' === $unit || ! isset( mmeff_units()[ $unit ] ) ) {
		$unit = '' !== $h['unit'] ? $h['unit'] : 'J/TH';
	}
	// Hashes and solutions are different things: keep "Sol" miners on a Sol unit.
	if ( str_contains( $h['unit'], 'Sol' ) !== str_contains( $unit, 'Sol' ) && '' !== $h['unit'] ) {
		$unit = $h['unit'];
	}
	$value  = $h['hs'] > 0 && $watts > 0 ? $watts / ( $h['hs'] / mmeff_units()[ $unit ] ) : 0.0;
	$custom = trim( (string) get_post_meta( $id, '_mmeff_text', true ) );
	$text   = '' !== $custom ? $custom : ( $value > 0 ? mmeff_format( $value ) . ' ' . $unit : '' );
	// What the theme shows without this plugin: watts ÷ terahashes, always called J/TH.
	$theme = '';
	if ( function_exists( 'mm_product_mining_specs' ) ) {
		$GLOBALS['mmeff_reading'] = true;
		$sp                       = mm_product_mining_specs( $p );
		$GLOBALS['mmeff_reading'] = false;
		if ( $sp['th'] > 0 && $sp['watts'] > 0 ) {
			$theme = number_format_i18n( $sp['watts'] / $sp['th'], 1 ) . ' J/TH';
		}
	} else {
		$dh    = mmeff_parse_hashrate( $det_hash )['hs'] / 1e12;
		$dw    = mmeff_parse_watts( $det_watts );
		$theme = $dh > 0 && $dw > 0 ? number_format_i18n( $dw / $dh, 1 ) . ' J/TH' : '';
	}

	return $cache[ $id ] = array(
		'product'   => $id,
		'algo'      => $algo,
		'hash_raw'  => '' !== $hash_raw ? $hash_raw : $det_hash,
		'watts_raw' => '' !== $watts_raw ? $watts_raw : $det_watts,
		'det_hash'  => $det_hash,
		'det_watts' => $det_watts,
		'hs'        => $h['hs'],
		'watts'     => $watts,
		'unit'      => $unit,
		'value'     => $value,
		'text'      => $text,
		'theme'     => $theme,
		'custom'    => $custom,
		'on'        => mmeff_product_on( $id ),
	);
}

/** Drop the cached replacement list when products or settings change. */
function mmeff_flush(): void {
	delete_transient( 'mmeff_map' );
}
add_action( 'save_post_product', 'mmeff_flush' );
add_action( 'woocommerce_update_product', 'mmeff_flush' );
add_action( 'update_option_mmeff_settings', 'mmeff_flush' );
