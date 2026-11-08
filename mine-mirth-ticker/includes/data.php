<?php
/**
 * Live data: the Bitcoin price (CoinGecko) and hashprices for proof-of-work coins (WhatToMine).
 *
 * Hashprice = what one unit of hashrate earns per day:
 *   block reward × blocks per day ÷ network hashrate × coin price.
 * WhatToMine gives each coin's reward, block time, network hashrate and price in BTC (for Bitcoin itself,
 * its price in USD); the BTC price from CoinGecko turns that into your currency.
 *
 * @package MineMirthTicker
 */

defined( 'ABSPATH' ) || exit;

const MMTK_SOURCES = array(
	'asic' => 'https://whattomine.com/asic.json',
	'gpu'  => 'https://whattomine.com/coins.json',
);

/** Hashrate units: label => hashes (or solutions) per second. */
function mmtk_units(): array {
	return array(
		'kH'   => 1e3,
		'MH'   => 1e6,
		'GH'   => 1e9,
		'TH'   => 1e12,
		'PH'   => 1e15,
		'EH'   => 1e18,
		'Sol'  => 1,
		'kSol' => 1e3,
		'MSol' => 1e6,
	);
}

/** The usual unit for each algorithm's hashprice. Unknown algorithms pick a readable unit. */
function mmtk_algo_unit( string $algo ): string {
	$map = array(
		'sha-256'    => 'PH',
		'scrypt'     => 'GH',
		'kheavyhash' => 'TH',
		'blake3'     => 'TH',
		'etchash'    => 'GH',
		'ethash'     => 'GH',
		'equihash'   => 'kSol',
		'x11'        => 'TH',
		'kadena'     => 'PH',
		'handshake'  => 'TH',
		'eaglesong'  => 'TH',
		'randomx'    => 'kH',
		'sha512256d' => 'TH',
	);
	return $map[ strtolower( $algo ) ] ?? 'auto';
}

function mmtk_currency(): string {
	$c = strtolower( (string) mmtk_opt( 'currency' ) );
	if ( 'theme' === $c || '' === $c ) {
		$c = function_exists( 'mm_market_currency' ) ? (string) mm_market_currency() : 'usd';
	}
	return in_array( $c, array( 'usd', 'eur', 'gbp', 'cad', 'aud' ), true ) ? $c : 'usd';
}

function mmtk_symbol( string $cur ): string {
	return array( 'usd' => '$', 'eur' => '€', 'gbp' => '£', 'cad' => 'CA$', 'aud' => 'A$' )[ $cur ] ?? '$';
}

function mmtk_get_json( string $url ) {
	$r = wp_remote_get(
		$url,
		array(
			'timeout'     => 8,
			'redirection' => 2,
			'headers'     => array(
				'Accept'     => 'application/json',
				'User-Agent' => 'MineMirthTicker/' . MMTK_VERSION . '; ' . home_url( '/' ),
			),
		)
	);
	if ( is_wp_error( $r ) || (int) wp_remote_retrieve_response_code( $r ) < 200 || (int) wp_remote_retrieve_response_code( $r ) >= 300 ) {
		return null;
	}
	$json = json_decode( (string) wp_remote_retrieve_body( $r ), true );
	return is_array( $json ) ? $json : null;
}

/** Bitcoin price and 24h change in the chosen currency. */
function mmtk_fetch_btc( string $cur ): ?array {
	$j = mmtk_get_json( 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=' . $cur . '&include_24hr_change=true' );
	if ( ! isset( $j['bitcoin'][ $cur ] ) || ! is_numeric( $j['bitcoin'][ $cur ] ) || (float) $j['bitcoin'][ $cur ] <= 0 ) {
		return null;
	}
	return array(
		'price'  => (float) $j['bitcoin'][ $cur ],
		'change' => isset( $j['bitcoin'][ $cur . '_24h_change' ] ) && is_numeric( $j['bitcoin'][ $cur . '_24h_change' ] ) ? (float) $j['bitcoin'][ $cur . '_24h_change' ] : null,
		'time'   => time(),
	);
}

/**
 * Every coin from WhatToMine (ASIC list first, then GPU list), raw: reward, block time, network hashrate,
 * price in BTC. Rental-market entries (NiceHash) are skipped.
 */
function mmtk_fetch_coins(): ?array {
	$out = array();
	foreach ( MMTK_SOURCES as $kind => $url ) {
		$j = mmtk_get_json( $url );
		if ( ! isset( $j['coins'] ) || ! is_array( $j['coins'] ) ) {
			continue;
		}
		foreach ( $j['coins'] as $name => $c ) {
			if ( ! is_array( $c ) || 0 === stripos( (string) $name, 'nicehash' ) || isset( $out[ $name ] ) ) {
				continue;
			}
			$bt  = (float) ( $c['block_time'] ?? 0 );
			$rw  = (float) ( $c['block_reward'] ?? 0 );
			$nh  = (float) ( $c['nethash'] ?? 0 );
			$ex  = (float) ( $c['exchange_rate'] ?? 0 );
			if ( $bt <= 0 || $rw <= 0 || $nh <= 0 || $ex <= 0 ) {
				continue;
			}
			$out[ (string) $name ] = array(
				'name'    => (string) $name,
				'tag'     => strtoupper( (string) ( $c['tag'] ?? '' ) ),
				'algo'    => (string) ( $c['algorithm'] ?? '' ),
				'kind'    => $kind,
				'bt'      => $bt,
				'reward'  => $rw,
				'reward24'=> (float) ( $c['block_reward24'] ?? $rw ),
				'diff'    => (float) ( $c['difficulty'] ?? 0 ),
				'diff24'  => (float) ( $c['difficulty24'] ?? 0 ),
				'nethash' => $nh,
				// Bitcoin's own entry is priced in USD; every other coin in BTC.
				'rate'    => $ex,
				'rate24'  => (float) ( $c['exchange_rate24'] ?? $ex ),
				'btc_row' => 'BTC' === strtoupper( (string) ( $c['tag'] ?? '' ) ) && 'bitcoin' === strtolower( (string) $name ),
				'lagging' => ! empty( $c['lagging'] ),
				'cap'     => (float) preg_replace( '/[^\d.]/', '', (string) ( $c['market_cap'] ?? '0' ) ),
			);
		}
	}
	return $out ? $out : null;
}

/**
 * Cached data, refreshed when older than its time (Bitcoin price: 60 s, coins: 10 min).
 * Only a REST call (from visitors' browsers) or the first page view ever fetches, never ordinary page views,
 * and a lock stops many visitors fetching at once. Last good values are kept if a source is down.
 */
function mmtk_data( bool $refresh = false ): array {
	$cur  = mmtk_currency();
	$data = get_option( 'mmtk_data_' . $cur );
	$data = is_array( $data ) ? $data : array( 'btc' => null, 'coins' => array(), 'btc_at' => 0, 'coins_at' => 0 );
	$first = ! $data['btc'] && ! $data['coins'];
	if ( ( $refresh || $first ) && ! get_transient( 'mmtk_lock_' . $cur ) ) {
		$now     = time();
		$changed = false;
		set_transient( 'mmtk_lock_' . $cur, 1, 30 );
		if ( $now - (int) $data['btc_at'] >= max( 30, (int) mmtk_opt( 'refresh' ) ) ) {
			$btc = mmtk_fetch_btc( $cur );
			$data['btc_at'] = $now;
			if ( $btc ) {
				$data['btc'] = $btc;
			}
			$changed = true;
		}
		if ( $now - (int) $data['coins_at'] >= 10 * MINUTE_IN_SECONDS ) {
			$coins = mmtk_fetch_coins();
			$data['coins_at'] = $now;
			if ( $coins ) {
				$data['coins']      = $coins;
				$data['coins_time'] = $now;
			}
			$changed = true;
		}
		if ( $changed ) {
			update_option( 'mmtk_data_' . $cur, $data, false );
		}
		delete_transient( 'mmtk_lock_' . $cur );
	}
	return $data;
}

/** The BTC price to convert coin prices with: CoinGecko, else WhatToMine's own (USD only). */
function mmtk_btc_price( array $data ): ?float {
	if ( ! empty( $data['btc']['price'] ) ) {
		return (float) $data['btc']['price'];
	}
	if ( 'usd' === mmtk_currency() && ! empty( $data['coins']['Bitcoin']['rate'] ) ) {
		return (float) $data['coins']['Bitcoin']['rate'];
	}
	return null;
}

/**
 * Hashprice of one coin: currency per (unit of hashrate) per day, its unit, and 24h change in %.
 *
 * @return array{value:float,unit:string,change:?float,price:float}|null
 */
function mmtk_hashprice( array $c, ?float $btc, string $unit = '' ): ?array {
	if ( ! $btc ) {
		return null;
	}
	$usd_btc = ! empty( $c['btc_row'] );
	$price   = $usd_btc ? $btc : $c['rate'] * $btc;
	$per_hs  = $c['reward'] * ( 86400 / $c['bt'] ) / $c['nethash'] * $price;
	if ( $per_hs <= 0 ) {
		return null;
	}
	$units = mmtk_units();
	$sol   = 'equihash' === strtolower( $c['algo'] );
	if ( '' === $unit || 'auto' === $unit || ! isset( $units[ $unit ] ) ) {
		$unit = mmtk_algo_unit( $c['algo'] );
	}
	// Keep solutions and hashes apart, then pick a readable unit if none is set.
	if ( $sol !== str_contains( $unit, 'Sol' ) ) {
		$unit = 'auto';
	}
	if ( 'auto' === $unit ) {
		$ladder = $sol ? array( 'Sol', 'kSol', 'MSol' ) : array( 'kH', 'MH', 'GH', 'TH', 'PH', 'EH' );
		$unit   = $ladder[0];
		foreach ( $ladder as $u ) {
			$unit = $u;
			if ( $per_hs * $units[ $u ] >= 0.01 ) {
				break;
			}
		}
	}
	// 24h change: reward ÷ difficulty × price, now against 24 hours ago.
	$change = null;
	if ( $c['diff'] > 0 && $c['diff24'] > 0 && $c['rate24'] > 0 && $c['reward24'] > 0 ) {
		$then   = $c['reward24'] / $c['diff24'] * $c['rate24'];
		$now    = $c['reward'] / $c['diff'] * $c['rate'];
		$change = $then > 0 ? ( $now / $then - 1 ) * 100 : null;
		if ( null !== $change && abs( $change ) > 90 ) {
			$change = null; // Feed glitch rather than a real move.
		}
	}
	return array(
		'value'  => $per_hs * $units[ $unit ],
		'unit'   => $unit,
		'change' => $change,
		'price'  => $price,
	);
}

/** "75,807", "32.66", "0.143", "0.000512". */
function mmtk_money( float $v ): string {
	$sym = mmtk_symbol( mmtk_currency() );
	if ( $v >= 1000 ) {
		return $sym . number_format_i18n( $v, 0 );
	}
	if ( $v >= 1 ) {
		return $sym . number_format_i18n( $v, 2 );
	}
	$d = max( 2, (int) ceil( -log10( max( $v, 1e-12 ) ) ) + 2 );
	return $sym . number_format_i18n( $v, min( 8, $d ) );
}

function mmtk_pct( ?float $v ): string {
	if ( null === $v ) {
		return '';
	}
	return ( $v >= 0 ? '▲ ' : '▼ ' ) . number_format_i18n( abs( $v ), 2 ) . '%';
}

/* ------------------------------------------------------------------ REST: browsers refresh the figures */

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'mine-mirth-ticker/v1',
			'/data',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static function () {
					$res = new WP_REST_Response( mmtk_payload( mmtk_data( true ) ) );
					$res->header( 'Cache-Control', 'public, max-age=30' );
					return $res;
				},
			)
		);
	}
);

/** Display values for every live field, keyed like the data-mmtk attributes in the bar. */
function mmtk_payload( array $data ): array {
	$btc = mmtk_btc_price( $data );
	$out = array();
	if ( $btc ) {
		$out['btc'] = array(
			'v' => mmtk_money( $btc ),
			'c' => mmtk_pct( $data['btc']['change'] ?? null ),
			'd' => isset( $data['btc']['change'] ) ? ( $data['btc']['change'] >= 0 ? 'up' : 'down' ) : '',
		);
	}
	foreach ( mmtk_selected_coins() as $key => $conf ) {
		if ( empty( $data['coins'][ $key ] ) ) {
			continue;
		}
		$hp = mmtk_hashprice( $data['coins'][ $key ], $btc, (string) ( $conf['unit'] ?? '' ) );
		if ( $hp ) {
			$out[ 'c:' . $key ] = array(
				'v' => mmtk_money( $hp['value'] ) . '/' . $hp['unit'] . '/day',
				'c' => mmtk_pct( $hp['change'] ),
				'd' => null === $hp['change'] ? '' : ( $hp['change'] >= 0 ? 'up' : 'down' ),
			);
		}
	}
	return $out;
}
