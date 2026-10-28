<?php
/**
 * Miner profitability dashboard: every ASIC model with live revenue, power cost and profit per day
 * at the visitor's electricity price. Models we sell link to their product page with Mine Mirth LLC
 * as the supplier; anything else gets a WhatsApp button to check availability.
 *
 * Revenue per hash per day comes from the same live sources as the mining tools (mempool.space,
 * litecoinspace.org, api.kaspa.org, CoinGecko) and, for the other ASIC coins, WhatToMine's public
 * coin list. Everything is cached; the page is never slowed down by a fetch.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

const MM_PROF_WTM      = 'mm_prof_wtm_v1';
const MM_PROF_WTM_LAST = 'mm_prof_wtm_last_v1';

/**
 * Algorithms: key => label, coins earned, hashrate unit and the unit hashrates are shown in.
 */
function mm_prof_algos(): array {
	return array(
		'sha256'     => array( 'label' => 'SHA-256', 'coins' => array( 'BTC' ), 'unit' => 'H', 'show' => 'T' ),
		'scrypt'     => array( 'label' => 'Scrypt', 'coins' => array( 'LTC', 'DOGE' ), 'unit' => 'H', 'show' => 'G' ),
		'kheavyhash' => array( 'label' => 'kHeavyHash', 'coins' => array( 'KAS' ), 'unit' => 'H', 'show' => 'T' ),
		'blake3'     => array( 'label' => 'Blake3', 'coins' => array( 'ALPH' ), 'unit' => 'H', 'show' => 'T' ),
		'equihash'   => array( 'label' => 'Equihash', 'coins' => array( 'ZEC' ), 'unit' => 'Sol', 'show' => 'k' ),
		'etchash'    => array( 'label' => 'Etchash', 'coins' => array( 'ETC' ), 'unit' => 'H', 'show' => 'G' ),
		'x11'        => array( 'label' => 'X11', 'coins' => array( 'DASH' ), 'unit' => 'H', 'show' => 'G' ),
		'randomx'    => array( 'label' => 'RandomX', 'coins' => array( 'XMR' ), 'unit' => 'H', 'show' => 'k' ),
		'eaglesong'  => array( 'label' => 'Eaglesong', 'coins' => array( 'CKB' ), 'unit' => 'H', 'show' => 'T' ),
	);
}

/** SI prefixes. */
function mm_prof_prefixes(): array {
	return array( '' => 1, 'k' => 1e3, 'M' => 1e6, 'G' => 1e9, 'T' => 1e12, 'P' => 1e15, 'E' => 1e18, 'Z' => 1e21 );
}

/**
 * "270 TH", "840 kSol/s" or "1.16 PH/s" to hashes (or solutions) per second.
 */
function mm_prof_parse_hash( string $h ): float {
	if ( ! preg_match( '/([\d.,]+)\s*([kKMGTPEZ]?)\s*(?:h|sol|H|Sol|SOL)/', $h, $m ) ) {
		return (float) $h;
	}
	$p = 'K' === $m[2] ? 'k' : $m[2];
	return (float) str_replace( ',', '', $m[1] ) * ( mm_prof_prefixes()[ $p ] ?? 1 );
}

/**
 * Hashrate for display, scaled to a readable prefix: 270 TH/s, 1.16 PH/s, 840 kSol/s.
 */
function mm_prof_format_hash( float $hs, string $algo ): string {
	$a    = mm_prof_algos()[ $algo ] ?? array( 'unit' => 'H', 'show' => 'T' );
	$pref = mm_prof_prefixes();
	$best = $a['show'];
	foreach ( array( 'k', 'M', 'G', 'T', 'P', 'E' ) as $p ) {
		if ( $hs >= $pref[ $p ] * 0.9995 ) {
			$best = $p;
		}
	}
	// Keep the usual unit unless the number gets very large (for example 1,160 TH/s becomes 1.16 PH/s).
	if ( $pref[ $best ] > $pref[ $a['show'] ] && $hs / $pref[ $a['show'] ] < 1000 ) {
		$best = $a['show'];
	}
	$v = $hs / $pref[ $best ];
	$d = $v >= 100 ? 0 : ( $v >= 10 ? 1 : 2 );
	return rtrim( rtrim( number_format_i18n( $v, $d ), '0' ), '.' ) . ' ' . $best . $a['unit'] . '/s';
}

/**
 * Efficiency in the algorithm's usual unit: J/TH for Bitcoin, J/GH for Scrypt, W/kSol for Equihash.
 */
function mm_prof_efficiency( float $hs, float $watts, string $algo ): array {
	$a    = mm_prof_algos()[ $algo ] ?? array( 'unit' => 'H', 'show' => 'T' );
	$unit = mm_prof_prefixes()[ $a['show'] ];
	if ( $hs <= 0 || $watts <= 0 ) {
		return array( 0.0, '' );
	}
	$v = $watts / ( $hs / $unit );
	return array( $v, 'J/' . $a['show'] . ( 'Sol' === $a['unit'] ? 'Sol' : 'H' ) );
}

/* ---------------------------------------------------------------------------
 * Live revenue
 * ------------------------------------------------------------------------- */

/**
 * WhatToMine algorithm name to our key.
 */
function mm_prof_algo_key( string $name ): string {
	$n = strtolower( preg_replace( '/[^a-z0-9]/i', '', $name ) );
	$map = array( 'sha256' => 'sha256', 'scrypt' => 'scrypt', 'kheavyhash' => 'kheavyhash', 'blake3' => 'blake3', 'equihash' => 'equihash', 'equihash2009' => 'equihash', 'etchash' => 'etchash', 'x11' => 'x11', 'randomx' => 'randomx', 'eaglesong' => 'eaglesong' );
	return $map[ $n ] ?? '';
}

/**
 * Coins from WhatToMine's public lists, keyed by ticker: algorithm, block time, reward, network
 * hashrate (H/s or Sol/s) and price in BTC.
 */
function mm_prof_wtm_fetch(): array {
	$coins = array();
	foreach ( array( 'https://whattomine.com/asic.json', 'https://whattomine.com/coins.json' ) as $url ) {
		$json = mm_market_get_json( $url );
		if ( ! is_array( $json ) || empty( $json['coins'] ) || ! is_array( $json['coins'] ) ) {
			continue;
		}
		foreach ( $json['coins'] as $name => $c ) {
			if ( ! is_array( $c ) || empty( $c['tag'] ) ) {
				continue;
			}
			$algo = mm_prof_algo_key( (string) ( $c['algorithm'] ?? '' ) );
			$tag  = strtoupper( (string) $c['tag'] );
			if ( '' === $algo || isset( $coins[ $tag ] ) ) {
				continue;
			}
			$coins[ $tag ] = array(
				'name'       => (string) $name,
				'algo'       => $algo,
				'block_time' => (float) ( $c['block_time'] ?? 0 ),
				'reward'     => (float) ( $c['block_reward'] ?? 0 ),
				'nethash'    => (float) ( $c['nethash'] ?? 0 ),
				'btc'        => (float) ( $c['exchange_rate'] ?? 0 ),
				'lagging'    => ! empty( $c['lagging'] ),
			);
		}
	}
	return $coins;
}

/**
 * Cached WhatToMine coins (refreshed hourly), with the last good list kept for a week.
 */
function mm_prof_wtm( bool $refresh = false ): array {
	$data = get_transient( MM_PROF_WTM );
	if ( $refresh && ( ! is_array( $data ) || time() - (int) ( $data['t'] ?? 0 ) > HOUR_IN_SECONDS ) && ! get_transient( 'mm_prof_lock' ) ) {
		set_transient( 'mm_prof_lock', 1, 60 );
		$coins = mm_prof_wtm_fetch();
		if ( $coins ) {
			$data = array( 't' => time(), 'coins' => $coins );
			set_transient( MM_PROF_WTM_LAST, $data, WEEK_IN_SECONDS );
		} else {
			$last = get_transient( MM_PROF_WTM_LAST );
			$data = is_array( $last ) ? $last + array( 'stale' => true ) : array( 't' => time(), 'coins' => array() );
		}
		set_transient( MM_PROF_WTM, $data, 2 * HOUR_IN_SECONDS );
		delete_transient( 'mm_prof_lock' );
	}
	if ( ! is_array( $data ) ) {
		$data = get_transient( MM_PROF_WTM_LAST );
	}
	return is_array( $data ) ? $data : array( 't' => 0, 'coins' => array() );
}

/**
 * Revenue per hash per second per day, in the market currency, for each algorithm.
 *
 * @return array<string,array{rev:?float,coins:string[]}>
 */
function mm_prof_revenue( bool $refresh = false ): array {
	$m     = function_exists( 'mm_market_cached' ) ? mm_market_cached() : array();
	$btc   = isset( $m['price'] ) && is_numeric( $m['price'] ) ? (float) $m['price'] : 0.0;
	$wtm   = mm_prof_wtm( $refresh )['coins'] ?? array();
	$alt   = function_exists( 'mm_alt_market' ) ? mm_alt_market( $refresh ) : array();
	$out   = array();
	$coin  = static function ( string $tag ) use ( $wtm, $btc ): ?float {
		$c = $wtm[ $tag ] ?? null;
		if ( ! $c || $c['nethash'] <= 0 || $c['block_time'] <= 0 || $c['btc'] <= 0 || $btc <= 0 ) {
			return null;
		}
		return ( 86400 / $c['block_time'] ) * $c['reward'] * $c['btc'] * $btc / $c['nethash'];
	};
	$chain = static function ( array $c, float $block_time, float $reward ): ?float {
		$price = (float) ( $c['price'] ?? 0 );
		$hash  = (float) ( $c['hashrate'] ?? 0 );
		return $price > 0 && $hash > 0 ? ( 86400 / $block_time ) * $reward * $price / $hash : null;
	};

	foreach ( mm_prof_algos() as $algo => $a ) {
		$rev   = null;
		$coins = $a['coins'];
		if ( 'sha256' === $algo && function_exists( 'mm_btc_economics' ) ) {
			$e   = mm_btc_economics( $m );
			$rev = ! empty( $e['hashprice_th'] ) ? (float) $e['hashprice_th'] / 1e12 : $coin( 'BTC' );
		} elseif ( 'scrypt' === $algo ) {
			// Litecoin and Dogecoin are merge-mined: a Scrypt miner earns both.
			$ltc = ! empty( $alt['ltc'] ) ? $chain( $alt['ltc'], (float) ( $alt['ltc']['block_time'] ?? 150 ), (float) ( $alt['ltc']['reward'] ?? 6.25 ) ) : null;
			$doge = ! empty( $alt['doge'] ) ? $chain( $alt['doge'], (float) ( $alt['doge']['block_time'] ?? 60 ), (float) ( $alt['doge']['reward'] ?? 10000 ) ) : null;
			$ltc  = $ltc ?? $coin( 'LTC' );
			$doge = $doge ?? $coin( 'DOGE' );
			$rev  = null !== $ltc ? $ltc + (float) $doge : null;
		} elseif ( 'kheavyhash' === $algo ) {
			$k   = $alt['kas'] ?? array();
			$rev = ! empty( $k['price'] ) && ! empty( $k['hashrate'] ) ? (float) $k['emission_day'] * (float) $k['price'] / (float) $k['hashrate'] : $coin( 'KAS' );
		} else {
			// The best-paying coin for this algorithm.
			foreach ( $wtm as $tag => $c ) {
				if ( $c['algo'] !== $algo || $c['lagging'] ) {
					continue;
				}
				$r = $coin( $tag );
				if ( null !== $r && ( null === $rev || $r > $rev ) ) {
					$rev   = $r;
					$coins = array( (string) $tag );
				}
			}
		}
		$out[ $algo ] = array(
			'rev'   => null !== $rev && $rev > 0 && is_finite( $rev ) ? $rev : null,
			'coins' => $coins,
		);
	}
	return (array) apply_filters( 'mm_prof_revenue', $out );
}

/** Refresh the cached data in the background (hourly). */
function mm_prof_refresh(): void {
	mm_prof_revenue( true );
	delete_transient( 'mm_prof_rows' );
}
add_action( 'mm_prof_refresh', 'mm_prof_refresh' );
add_action(
	'init',
	static function () {
		if ( mm_opt( 'prof_enable' ) && ! wp_next_scheduled( 'mm_prof_refresh' ) ) {
			wp_schedule_event( time() + 60, 'hourly', 'mm_prof_refresh' );
		}
	}
);
add_action( 'switch_theme', static fn() => wp_clear_scheduled_hook( 'mm_prof_refresh' ) );

/* ---------------------------------------------------------------------------
 * Rows: reference models matched with the shop, plus shop miners not in the list
 * ------------------------------------------------------------------------- */

/**
 * Name reduced to its model code for matching: "Bitmain Antminer S21 XP 270TH" → "s21xp270th".
 */
function mm_prof_key( string $name ): string {
	$n = strtolower( preg_replace( '/\([^)]*\)/', '', $name ) );
	$n = preg_replace( '/\b(bitmain|antminer|microbt|whatsminer|canaan|avalon|iceriver|ice river|elphapex|goldshell|bitdeer|sealminer|auradine|teraflux|jasminer|bitaxe|asic|miner|new|used|refurbished)\b/', ' ', $n );
	return (string) preg_replace( '/[^a-z0-9+]/', '', $n );
}

/**
 * Rows for the dashboard, cached until a product or the market data changes.
 */
function mm_prof_rows(): array {
	$cached = get_transient( 'mm_prof_rows' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$algos   = mm_prof_algos();
	$catalog = function_exists( 'mm_catalog' ) ? array_values( array_filter( mm_catalog(), static fn( $r ) => ! $r['is_part'] && $r['hash'] > 0 && isset( $algos[ $r['algo'] ] ) ) ) : array();
	$used    = array();
	$rows    = array();
	$rank    = array( 'instock' => 0, 'onbackorder' => 1, 'outofstock' => 2 );

	foreach ( mm_miner_models() as $mod ) {
		$hs  = mm_prof_parse_hash( (string) $mod['h'] );
		$key = mm_prof_key( (string) $mod['m'] );
		if ( $hs <= 0 || '' === $key || ! isset( $algos[ $mod['a'] ] ) ) {
			continue;
		}
		// The shop product for this model: same algorithm, the model code in its name, hashrate within 4%.
		$best = null;
		foreach ( $catalog as $i => $p ) {
			$p_hs = (float) $p['hash'];
			if ( $p['algo'] !== $mod['a'] || abs( $p_hs - $hs ) > $hs * 0.04 ) {
				continue;
			}
			$pk = mm_prof_key( (string) $p['name'] );
			if ( ! str_starts_with( $pk, $key ) && ! str_contains( $pk, $key ) ) {
				continue;
			}
			// Several products can be the same model: prefer one in stock, then the lower price.
			if ( null === $best || array( $rank[ $p['stock'] ] ?? 3, $p['price'] ?: INF ) < array( $rank[ $best[1]['stock'] ] ?? 3, $best[1]['price'] ?: INF ) ) {
				$best = array( $i, $p );
			}
		}
		if ( $best ) {
			$used[ $best[0] ] = true;
		}
		$rows[] = mm_prof_row( (string) $mod['b'], (string) $mod['m'], $hs, (float) $mod['w'], (string) $mod['a'], (string) $mod['c'], (string) $mod['r'], $best ? $best[1] : null );
	}
	// Shop miners that are not in the reference list are shown too.
	foreach ( $catalog as $i => $p ) {
		if ( isset( $used[ $i ] ) || $p['watts'] <= 0 ) {
			continue;
		}
		$brand  = (string) $p['brand'];
		$name   = (string) $p['name'];
		$model  = '' !== $brand && 0 === stripos( $name, $brand ) ? trim( substr( $name, strlen( $brand ) ) ) : $name;
		$rows[] = mm_prof_row( $brand, $model, (float) $p['hash'], (float) $p['watts'], (string) $p['algo'], (string) $p['cooling'], gmdate( 'Y-m', (int) $p['date'] ), $p );
	}
	set_transient( 'mm_prof_rows', $rows, 6 * HOUR_IN_SECONDS );
	return $rows;
}

function mm_prof_row( string $brand, string $model, float $hs, float $watts, string $algo, string $cooling, string $release, ?array $product ): array {
	list( $eff, $eff_unit ) = mm_prof_efficiency( $hs, $watts, $algo );
	$stock = $product ? (string) $product['stock'] : '';
	return array(
		'brand'    => $brand,
		'model'    => $model,
		'name'     => trim( $brand . ' ' . $model ),
		'hs'       => $hs,
		'watts'    => $watts,
		'algo'     => $algo,
		'cooling'  => in_array( $cooling, array( 'air', 'hydro', 'immersion' ), true ) ? $cooling : 'air',
		'release'  => preg_match( '/^\d{4}-\d{2}$/', $release ) ? $release : '',
		'eff'      => $eff,
		'eff_unit' => $eff_unit,
		'url'      => $product ? (string) $product['url'] : '',
		'price'    => $product && 'outofstock' !== $stock ? (float) $product['price'] : 0.0,
		'stock'    => $stock,
		'img'      => $product ? (string) $product['img'] : '',
	);
}

foreach ( array( 'woocommerce_update_product', 'woocommerce_new_product', 'woocommerce_delete_product', 'woocommerce_trash_product', 'woocommerce_product_set_stock_status', 'woocommerce_variation_set_stock_status' ) as $mm_hook ) {
	add_action( $mm_hook, static fn() => delete_transient( 'mm_prof_rows' ) );
}
unset( $mm_hook );

/* ---------------------------------------------------------------------------
 * Links: product page, calculator and WhatsApp
 * ------------------------------------------------------------------------- */

/**
 * WhatsApp chat link with a message, or the contact page when no number is set.
 */
function mm_whatsapp_url( string $message ): string {
	$number = preg_replace( '/\D/', '', (string) mm_opt( 'contact_whatsapp' ) );
	if ( '' === $number ) {
		$number = preg_replace( '/\D/', '', (string) mm_opt( 'contact_phone' ) );
	}
	if ( strlen( $number ) < 8 ) {
		return add_query_arg( 'subject', rawurlencode( $message ), function_exists( 'mm_page_url_by_key' ) ? mm_page_url_by_key( 'contact' ) : home_url( '/contact/' ) );
	}
	return 'https://wa.me/' . $number . '?text=' . rawurlencode( $message );
}

function mm_prof_page_url(): string {
	return function_exists( 'mm_page_url_by_key' ) ? mm_page_url_by_key( 'profitability' ) : home_url( '/miner-profitability/' );
}

/* ---------------------------------------------------------------------------
 * Markup
 * ------------------------------------------------------------------------- */

/**
 * Coin badges for an algorithm.
 */
function mm_prof_coins_html( array $coins ): string {
	$out = '';
	foreach ( $coins as $c ) {
		$out .= '<i class="mm-prof__coin mm-prof__coin--' . esc_attr( strtolower( $c ) ) . '" title="' . esc_attr( $c ) . '">' . esc_html( mb_substr( $c, 0, 1 ) ) . '</i>';
	}
	return '<span class="mm-prof__coins">' . $out . '</span>';
}

/**
 * The dashboard.
 *
 * @param array $a limit (0 = all), compact (homepage: fewer controls), id.
 */
function mm_prof_html( array $a = array() ): string {
	$a       = wp_parse_args( $a, array( 'limit' => 0, 'compact' => false ) );
	$rows    = mm_prof_rows();
	$rev     = mm_prof_revenue();
	$algos   = mm_prof_algos();
	$cur     = function_exists( 'mm_market_currency' ) ? mm_market_currency() : 'usd';
	$sym     = function_exists( 'mm_currency_symbol' ) ? mm_currency_symbol( $cur ) : '$';
	$kwh     = (float) mm_opt( 'calc_kwh' );
	$fee     = (float) mm_opt( 'calc_pool_fee' );
	$compact = (bool) $a['compact'];
	if ( ! $rows ) {
		return '';
	}
	wp_enqueue_style( 'mm-profitability' );
	wp_enqueue_script( 'mm-profitability' );

	$money = static function ( float $v, int $dec = 2 ) use ( $sym ): string {
		return ( $v < 0 ? '-' : '' ) . $sym . number_format_i18n( abs( $v ), $dec );
	};
	// Rank by today's profit so the server-rendered order (and the homepage top list) is right before JS runs.
	foreach ( $rows as &$r ) {
		$per       = $rev[ $r['algo'] ]['rev'] ?? null;
		$r['rev']  = null !== $per ? $per * $r['hs'] : null;
		$r['cost'] = $r['watts'] * 24 / 1000 * $kwh;
		$r['net']  = null !== $r['rev'] ? $r['rev'] * ( 1 - $fee / 100 ) - $r['cost'] : null;
	}
	unset( $r );
	usort( $rows, static fn( $x, $y ) => ( $y['net'] ?? -INF ) <=> ( $x['net'] ?? -INF ) );

	$brands = array_values( array_unique( array_filter( array_column( $rows, 'brand' ) ) ) );
	sort( $brands );
	$present = array_values( array_unique( array_column( $rows, 'algo' ) ) );
	$now_ym  = gmdate( 'Y-m' );
	$soon    = gmdate( 'Y-m', strtotime( '-6 months' ) );
	$total   = count( $rows );
	$avail   = count( array_filter( $rows, static fn( $r ) => $r['url'] && 'outofstock' !== $r['stock'] ) );

	$algo_opts = '';
	foreach ( $algos as $k => $al ) {
		if ( in_array( $k, $present, true ) ) {
			$algo_opts .= '<option value="' . esc_attr( $k ) . '">' . esc_html( $al['label'] . ' · ' . implode( ' + ', $al['coins'] ) ) . '</option>';
		}
	}

	ob_start();
	?>
	<div class="mm-prof<?php echo $compact ? ' mm-prof--compact' : ''; ?>" data-mm-prof data-limit="<?php echo (int) $a['limit']; ?>" data-kwh="<?php echo esc_attr( (string) $kwh ); ?>" data-fee="<?php echo esc_attr( (string) $fee ); ?>" data-sym="<?php echo esc_attr( $sym ); ?>" data-rev="<?php echo esc_attr( (string) wp_json_encode( array_map( static fn( $x ) => $x['rev'], $rev ) ) ); ?>" data-api="<?php echo esc_url( rest_url( 'mine-mirth/v1/profitability' ) ); ?>">
		<div class="mm-prof__bar">
			<?php if ( ! $compact ) : ?>
				<label class="mm-prof__search"><?php echo mm_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="screen-reader-text"><?php esc_html_e( 'Search miners', 'minemirth' ); ?></span><input type="search" data-f="q" placeholder="<?php esc_attr_e( 'Search miners', 'minemirth' ); ?>"></label>
				<label class="mm-prof__select"><span class="screen-reader-text"><?php esc_html_e( 'Algorithm', 'minemirth' ); ?></span><select data-f="algo"><option value=""><?php esc_html_e( 'All algorithms', 'minemirth' ); ?></option><?php echo $algo_opts; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above. ?></select></label>
				<label class="mm-prof__select"><span class="screen-reader-text"><?php esc_html_e( 'Manufacturer', 'minemirth' ); ?></span><select data-f="brand"><option value=""><?php esc_html_e( 'All manufacturers', 'minemirth' ); ?></option><?php foreach ( $brands as $b ) : ?><option value="<?php echo esc_attr( $b ); ?>"><?php echo esc_html( $b ); ?></option><?php endforeach; ?></select></label>
				<label class="mm-prof__select"><span class="screen-reader-text"><?php esc_html_e( 'Cooling', 'minemirth' ); ?></span><select data-f="cool"><option value=""><?php esc_html_e( 'Any cooling', 'minemirth' ); ?></option><option value="air"><?php esc_html_e( 'Air', 'minemirth' ); ?></option><option value="hydro"><?php esc_html_e( 'Hydro', 'minemirth' ); ?></option><option value="immersion"><?php esc_html_e( 'Immersion', 'minemirth' ); ?></option></select></label>
				<label class="mm-prof__toggle"><input type="checkbox" data-f="stock"> <span><?php echo esc_html( sprintf( /* translators: %d: count */ __( 'Available at Mine Mirth (%d)', 'minemirth' ), $avail ) ); ?></span></label>
			<?php else : ?>
				<div class="mm-prof__chips" role="group" aria-label="<?php esc_attr_e( 'Algorithm', 'minemirth' ); ?>">
					<button type="button" class="is-on" data-algo=""><?php esc_html_e( 'All', 'minemirth' ); ?></button>
					<?php foreach ( $algos as $k => $al ) : ?>
						<?php if ( in_array( $k, $present, true ) ) : ?>
							<button type="button" data-algo="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( implode( '+', $al['coins'] ) ); ?></button>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<label class="mm-prof__kwh"><span><?php esc_html_e( 'Electricity', 'minemirth' ); ?></span><span class="mm-prof__kwh-in"><b><?php echo esc_html( $sym ); ?></b><input type="number" inputmode="decimal" min="0" max="1" step="0.005" value="<?php echo esc_attr( (string) $kwh ); ?>" data-f="kwh" aria-label="<?php esc_attr_e( 'Electricity price per kWh', 'minemirth' ); ?>"><i>/kWh</i></span></label>
		</div>

		<div class="mm-prof__wrap">
			<table class="mm-prof__table">
				<thead>
					<tr>
						<th scope="col" data-sort="name" class="mm-prof__c-model"><?php esc_html_e( 'Model', 'minemirth' ); ?></th>
						<th scope="col" data-sort="release"><?php esc_html_e( 'Release', 'minemirth' ); ?></th>
						<th scope="col" data-sort="hash" class="is-num"><?php esc_html_e( 'Hashrate', 'minemirth' ); ?></th>
						<th scope="col" data-sort="watts" class="is-num"><?php esc_html_e( 'Power', 'minemirth' ); ?></th>
						<th scope="col" data-sort="eff" class="is-num mm-prof__c-eff"><?php esc_html_e( 'Efficiency', 'minemirth' ); ?></th>
						<th scope="col" data-sort="algo"><?php esc_html_e( 'Algorithm', 'minemirth' ); ?></th>
						<th scope="col" data-sort="price" class="is-num"><?php esc_html_e( 'Price', 'minemirth' ); ?></th>
						<th scope="col" data-sort="profit" class="is-num is-sorted" aria-sort="descending"><?php esc_html_e( 'Profit', 'minemirth' ); ?></th>
						<th scope="col" class="mm-prof__c-supplier"><?php esc_html_e( 'Supplier', 'minemirth' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $rows as $i => $r ) :
					$al        = $algos[ $r['algo'] ];
					$available = $r['url'] && 'outofstock' !== $r['stock'];
					$future    = $r['release'] && $r['release'] > $now_ym;
					$new       = $r['release'] && ! $future && $r['release'] >= $soon;
					$wa        = mm_whatsapp_url( sprintf( /* translators: 1: miner, 2: hashrate */ __( 'Hi Mine Mirth, is the %1$s (%2$s) available? Please share price and lead time.', 'minemirth' ), $r['name'], mm_prof_format_hash( $r['hs'], $r['algo'] ) ) );
					$hidden    = $a['limit'] && $i >= (int) $a['limit'];
					?>
					<tr data-name="<?php echo esc_attr( strtolower( $r['name'] ) ); ?>" data-brand="<?php echo esc_attr( $r['brand'] ); ?>" data-algo="<?php echo esc_attr( $r['algo'] ); ?>" data-cool="<?php echo esc_attr( $r['cooling'] ); ?>" data-hash="<?php echo esc_attr( (string) $r['hs'] ); ?>" data-watts="<?php echo esc_attr( (string) $r['watts'] ); ?>" data-eff="<?php echo esc_attr( (string) round( $r['eff'], 4 ) ); ?>" data-release="<?php echo esc_attr( str_replace( '-', '', $r['release'] ) ); ?>" data-price="<?php echo esc_attr( (string) $r['price'] ); ?>" data-stock="<?php echo $available ? '1' : '0'; ?>"<?php echo $hidden ? ' hidden' : ''; ?>>
						<th scope="row" class="mm-prof__c-model">
							<span class="mm-prof__avatar" aria-hidden="true"><?php echo esc_html( mb_substr( $r['brand'] ? $r['brand'] : $r['model'], 0, 1 ) ); ?></span>
							<span class="mm-prof__name">
								<?php if ( $r['url'] ) : ?>
									<a href="<?php echo esc_url( $r['url'] ); ?>"><?php echo esc_html( $r['model'] ); ?></a>
								<?php else : ?>
									<span><?php echo esc_html( $r['model'] ); ?></span>
								<?php endif; ?>
								<small><?php echo esc_html( trim( $r['brand'] . ' · ' . array( 'air' => __( 'Air', 'minemirth' ), 'hydro' => __( 'Hydro', 'minemirth' ), 'immersion' => __( 'Immersion', 'minemirth' ) )[ $r['cooling'] ], ' ·' ) ); ?></small>
							</span>
						</th>
						<td class="mm-prof__release<?php echo $future ? ' is-soon' : ( $new ? ' is-new' : '' ); ?>" data-label="<?php esc_attr_e( 'Release', 'minemirth' ); ?>"><?php echo $r['release'] ? esc_html( wp_date( 'M Y', strtotime( $r['release'] . '-15' ) ) ) : '—'; ?></td>
						<td class="is-num" data-label="<?php esc_attr_e( 'Hashrate', 'minemirth' ); ?>"><?php echo esc_html( mm_prof_format_hash( $r['hs'], $r['algo'] ) ); ?></td>
						<td class="is-num" data-label="<?php esc_attr_e( 'Power', 'minemirth' ); ?>"><?php echo esc_html( number_format_i18n( $r['watts'] ) ); ?> W</td>
						<td class="is-num mm-prof__c-eff" data-label="<?php esc_attr_e( 'Efficiency', 'minemirth' ); ?>"><?php echo $r['eff'] ? esc_html( number_format_i18n( $r['eff'], $r['eff'] < 10 ? 2 : 1 ) . ' ' . $r['eff_unit'] ) : '—'; ?></td>
						<td data-label="<?php esc_attr_e( 'Algorithm', 'minemirth' ); ?>"><span class="mm-prof__algo"><?php echo mm_prof_coins_html( $rev[ $r['algo'] ]['coins'] ?? $al['coins'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $al['label'] ); ?></span></span></td>
						<td class="is-num mm-prof__price" data-label="<?php esc_attr_e( 'Price', 'minemirth' ); ?>"><?php echo $r['price'] > 0 ? esc_html( $money( $r['price'], 0 ) ) : '<span class="mm-prof__muted">' . esc_html__( 'On request', 'minemirth' ) . '</span>'; ?></td>
						<td class="is-num" data-label="<?php esc_attr_e( 'Profit', 'minemirth' ); ?>">
							<span class="mm-prof__profit<?php echo null === $r['net'] ? ' is-na' : ( $r['net'] >= 0 ? ' is-up' : ' is-down' ); ?>" data-out="profit"><?php echo null === $r['net'] ? '—' : esc_html( $money( $r['net'] ) ) . '<i>' . esc_html__( '/day', 'minemirth' ) . '</i>'; ?></span>
							<small class="mm-prof__rev" data-out="rev"><?php echo null === $r['rev'] ? esc_html__( 'Live data updating', 'minemirth' ) : esc_html( sprintf( /* translators: %s: revenue */ __( 'Revenue %s', 'minemirth' ), $money( $r['rev'] ) ) ); ?></small>
						</td>
						<td class="mm-prof__c-supplier" data-label="<?php esc_attr_e( 'Supplier', 'minemirth' ); ?>">
							<?php if ( $available ) : ?>
								<a class="mm-prof__supplier" href="<?php echo esc_url( $r['url'] ); ?>">
									<span class="mm-prof__supplier-name"><?php echo mm_icon( 'shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>Mine Mirth LLC</span>
									<span class="mm-prof__supplier-stock"><?php echo 'onbackorder' === $r['stock'] ? esc_html__( 'Pre-order', 'minemirth' ) : esc_html__( 'In stock', 'minemirth' ); ?> · <?php esc_html_e( 'View', 'minemirth' ); ?> →</span>
								</a>
							<?php else : ?>
								<a class="mm-prof__wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">
									<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.6.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .1-1.3c0-.1-.2-.2-.4-.3Z"/></svg>
									<span><?php echo $r['url'] ? esc_html__( 'Sold out · ask on WhatsApp', 'minemirth' ) : esc_html__( 'Check availability', 'minemirth' ); ?></span>
								</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="mm-prof__empty" hidden><?php esc_html_e( 'No miners match these filters.', 'minemirth' ); ?></p>
		</div>
		<p class="mm-prof__foot">
			<span><span class="mm-live-dot" aria-hidden="true"></span><?php echo esc_html( sprintf( /* translators: 1: count, 2: fee */ __( '%1$d miners · profit after a %2$s%% pool fee and your electricity, at today’s prices and difficulty.', 'minemirth' ), $total, number_format_i18n( $fee, 1 ) ) ); ?></span>
			<span><?php esc_html_e( 'Estimates, not guarantees. Specifications are manufacturer figures.', 'minemirth' ); ?></span>
		</p>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Section layout "Miner profitability" (also on the homepage).
 */
function mm_s_render_profitability( array $d ): string {
	if ( ! mm_opt( 'prof_enable' ) ) {
		return '';
	}
	$limit = max( 0, (int) ( $d['limit'] ?? 10 ) );
	$html  = mm_prof_html( array( 'limit' => $limit, 'compact' => $limit > 0 ) );
	if ( '' === $html ) {
		return '';
	}
	$d['eyebrow'] = $d['eyebrow'] ?? __( 'Live profitability', 'minemirth' );
	$d['title']   = $d['title'] ?? __( 'What every miner *earns today*', 'minemirth' );
	$d['text']    = $d['text'] ?? __( 'Revenue, power cost and profit per day for every major ASIC, recalculated from live prices and difficulty. Change the electricity price to see your numbers.', 'minemirth' );
	if ( $limit ) {
		$d['link_label'] = $d['link_label'] ?? __( 'Full profitability dashboard', 'minemirth' );
		$d['link_url']   = $d['link_url'] ?? mm_prof_page_url();
	}
	$more = $limit ? '<div class="mm-prof__more"><a class="mm-btn mm-btn--primary" href="' . esc_url( mm_prof_page_url() ) . '"><span>' . esc_html( sprintf( /* translators: %d: count */ __( 'Compare all %d miners', 'minemirth' ), count( mm_prof_rows() ) ) ) . '</span>' . mm_icon( 'arrow-right' ) . '</a></div>' : '';
	return '<div class="mm-container">' . mm_s_head( $d ) . $html . $more . '</div>';
}

/* ---------------------------------------------------------------------------
 * REST: fresh revenue for the dashboard's script
 * ------------------------------------------------------------------------- */

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'mine-mirth/v1',
			'/profitability',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => static function () {
					$wtm = mm_prof_wtm();
					$rev = mm_prof_revenue( time() - (int) ( $wtm['t'] ?? 0 ) > HOUR_IN_SECONDS );
					$res = new WP_REST_Response(
						array(
							'rev'     => array_map( static fn( $x ) => $x['rev'], $rev ),
							'updated' => (int) ( mm_prof_wtm()['t'] ?? 0 ),
						),
						200
					);
					$res->header( 'Cache-Control', 'public, max-age=300' );
					return $res;
				},
			)
		);
	}
);

/* ---------------------------------------------------------------------------
 * Homepage: show the dashboard after "Popular ASIC miners" when the homepage does not place it
 * ------------------------------------------------------------------------- */

add_filter(
	'render_block',
	static function ( $html, $block ) {
		static $done = false;
		if ( $done || 'mm/section' !== ( $block['blockName'] ?? '' ) || 'products' !== ( $block['attrs']['variant'] ?? '' ) ) {
			return $html;
		}
		if ( ! mm_opt( 'prof_enable' ) || ! mm_opt( 'prof_home' ) || is_admin() || ! is_front_page() || ( function_exists( 'mm_s_is_editor' ) && mm_s_is_editor() ) ) {
			return $html;
		}
		$done = true;
		$raw  = function_exists( 'mm_page_raw_content' ) ? mm_page_raw_content( (int) get_queried_object_id() ) : (string) get_post_field( 'post_content', (int) get_queried_object_id() );
		if ( str_contains( $raw, '"variant":"profitability"' ) ) {
			return $html;
		}
		return $html . mm_section_render( array( 'variant' => 'profitability', 'data' => array( 'limit' => (int) mm_opt( 'prof_home_limit' ), 'tone' => 'alt' ) ) );
	},
	10,
	2
);

/* ---------------------------------------------------------------------------
 * The dashboard page: created once, and added to the Shop menu next to "Most efficient miners"
 * ------------------------------------------------------------------------- */

function mm_prof_ensure_page(): void {
	if ( ! mm_opt( 'prof_enable' ) || get_option( 'mm_prof_page_v1' ) || ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	update_option( 'mm_prof_page_v1', 1, false );
	$existing = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'meta_key' => '_mm_page_key', 'meta_value' => 'profitability', 'fields' => 'ids', 'numberposts' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( $existing ) {
		return;
	}
	$parent = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'meta_key' => '_mm_page_key', 'meta_value' => 'miners', 'fields' => 'ids', 'numberposts' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	$id     = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => __( 'ASIC miner profitability', 'minemirth' ),
			'post_name'    => 'miner-profitability',
			'post_parent'  => $parent ? (int) $parent[0] : 0,
			'post_excerpt' => __( 'Live profit per day for every major ASIC miner at your electricity price, with Mine Mirth stock and prices.', 'minemirth' ),
			'post_content' => '',
		)
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return;
	}
	update_post_meta( $id, '_wp_page_template', 'page-templates/profitability.php' );
	update_post_meta( $id, '_mm_page_key', 'profitability' );
	update_post_meta( $id, '_mm_icon', 'activity' );

	// Add it to the main menu, next to "Most efficient miners" when that item exists.
	$eff = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'meta_key' => '_mm_page_key', 'meta_value' => 'm-efficient', 'fields' => 'ids', 'numberposts' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( ! $eff ) {
		return;
	}
	foreach ( wp_get_nav_menus() as $menu ) {
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
			if ( 'post_type' === $item->type && (int) $item->object_id === (int) $eff[0] ) {
				wp_update_nav_menu_item(
					$menu->term_id,
					0,
					array(
						'menu-item-title'     => __( 'Miner profitability', 'minemirth' ),
						'menu-item-object'    => 'page',
						'menu-item-object-id' => $id,
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
						'menu-item-parent-id' => (int) $item->menu_item_parent,
						'menu-item-position'  => (int) $item->menu_order,
						'menu-item-description' => __( 'Live profit for every ASIC', 'minemirth' ),
					)
				);
				break 2;
			}
		}
	}
}
add_action( 'admin_init', 'mm_prof_ensure_page' );
