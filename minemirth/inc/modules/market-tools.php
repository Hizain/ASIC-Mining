<?php
/**
 * Market engine for the mining tools.
 *
 * - Bitcoin economics derived from the live snapshot: block subsidy, fees, hashprice and revenue per TH.
 * - Daily market history stored on your server (price, difficulty, hashrate, fees), backfilled once a
 *   week from mempool.space and CoinGecko, so charts work without visitors calling third-party APIs.
 * - Altcoin networks for the altcoin calculator: Litecoin + Dogecoin (merged Scrypt mining) and Kaspa.
 * - A catalog of the store's miners with specs, algorithm, cooling, brand and efficiency.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

const MM_HISTORY_OPTION = 'mm_market_history_v5';
const MM_ALT_CACHE      = 'mm_alt_market_v5_';
const MM_ALT_LAST_GOOD  = 'mm_alt_market_last_v5_';
const MM_CATALOG_OPTION = 'mm_catalog_v5';

/* ---------------------------------------------------------------------------
 * Bitcoin economics
 * ------------------------------------------------------------------------- */

function mm_btc_subsidy_at_height( int $height ): float {
	return 50 / pow( 2, (int) floor( $height / MM_HALVING_INTERVAL ) );
}

/**
 * Block subsidy on a calendar date (for history rows that have no height).
 */
function mm_btc_subsidy_at_time( int $ts ): float {
	$halvings = array( 1354060800 => 25, 1468022400 => 12.5, 1589155200 => 6.25, 1713571200 => 3.125 ); // 2012-11-28, 2016-07-09, 2020-05-11, 2024-04-20.
	$subsidy  = 50.0;
	foreach ( $halvings as $when => $value ) {
		if ( $ts >= $when ) {
			$subsidy = (float) $value;
		}
	}
	return $subsidy;
}

/**
 * Revenue per TH/s per day for a given difficulty and reward (BTC). Uses the expected value
 * from difficulty, which is what pools pay out over time.
 */
function mm_btc_per_th_day( float $difficulty, float $reward ): float {
	return $difficulty > 0 ? 1e12 * 86400 * $reward / ( $difficulty * 4294967296 ) : 0.0;
}

/**
 * Derived economics from a market snapshot.
 *
 * @return array{price:?float,difficulty:?float,hashrate:?float,height:?int,subsidy:float,fees:float,reward:float,fee_share:float,btc_per_th_day:float,hashprice_th:?float,hashprice_ph:?float}
 */
function mm_btc_economics( ?array $m = null ): array {
	$m       = null === $m ? mm_market_cached() : $m;
	$height  = isset( $m['height'] ) && is_numeric( $m['height'] ) ? (int) $m['height'] : null;
	$subsidy = null !== $height ? mm_btc_subsidy_at_height( $height ) : mm_btc_subsidy_at_time( time() );
	$fees    = isset( $m['fees'] ) && is_numeric( $m['fees'] ) ? max( 0.0, (float) $m['fees'] ) : 0.0;
	$diff    = isset( $m['difficulty'] ) && is_numeric( $m['difficulty'] ) ? (float) $m['difficulty'] : null;
	$price   = isset( $m['price'] ) && is_numeric( $m['price'] ) ? (float) $m['price'] : null;
	$btc_th  = null !== $diff ? mm_btc_per_th_day( $diff, $subsidy + $fees ) : 0.0;
	return array(
		'price'          => $price,
		'difficulty'     => $diff,
		'hashrate'       => isset( $m['hashrate'] ) && is_numeric( $m['hashrate'] ) ? (float) $m['hashrate'] : null,
		'height'         => $height,
		'subsidy'        => $subsidy,
		'fees'           => $fees,
		'reward'         => $subsidy + $fees,
		'fee_share'      => ( $subsidy + $fees ) > 0 ? $fees / ( $subsidy + $fees ) : 0.0,
		'btc_per_th_day' => $btc_th,
		'hashprice_th'   => null !== $price && $btc_th ? $btc_th * $price : null,
		'hashprice_ph'   => null !== $price && $btc_th ? $btc_th * $price * 1000 : null,
	);
}

/* ---------------------------------------------------------------------------
 * Daily history
 * ------------------------------------------------------------------------- */

/**
 * @return array<string,array{0:?float,1:?float,2:?float,3:?float}> date => [price, difficulty, hashrate, fees]
 */
function mm_history(): array {
	$h = get_option( MM_HISTORY_OPTION, array() );
	return is_array( $h ) ? $h : array();
}

function mm_history_save( array $rows ): void {
	ksort( $rows );
	$rows = array_slice( $rows, -1100, null, true ); // About three years.
	update_option( MM_HISTORY_OPTION, $rows, false );
}

/**
 * Record today's values from a live snapshot (overwrites today's row).
 */
function mm_history_record( array $m ): void {
	if ( empty( $m['price'] ) || empty( $m['difficulty'] ) ) {
		return;
	}
	$rows                     = mm_history();
	$rows[ gmdate( 'Y-m-d' ) ] = array( (float) $m['price'], (float) $m['difficulty'], isset( $m['hashrate'] ) ? (float) $m['hashrate'] : null, isset( $m['fees'] ) ? (float) $m['fees'] : null );
	mm_history_save( $rows );
}
add_action( 'mm_market_refreshed', 'mm_history_record' );

/**
 * Fill the last year from public APIs (price, difficulty, hashrate, fees). Runs at most once a week
 * and only fills gaps, so values recorded live are kept.
 */
function mm_history_backfill( bool $force = false ): bool {
	$last = (int) get_option( 'mm_history_backfill_at' );
	if ( ! $force && time() - $last < WEEK_IN_SECONDS ) {
		return false;
	}
	if ( get_transient( 'mm_history_backfill_lock' ) ) {
		return false;
	}
	set_transient( 'mm_history_backfill_lock', 1, 10 * MINUTE_IN_SECONDS );
	update_option( 'mm_history_backfill_at', time(), false );

	$cur    = mm_market_currency();
	$net    = mm_market_get_json( 'https://mempool.space/api/v1/mining/hashrate/1y' );
	$fees   = mm_market_get_json( 'https://mempool.space/api/v1/mining/blocks/fees/1y' );
	$prices = mm_market_get_json( 'https://api.coingecko.com/api/v3/coins/bitcoin/market_chart?vs_currency=' . $cur . '&days=365&interval=daily' );

	$day = static fn( $ts ) => gmdate( 'Y-m-d', (int) $ts );
	$by  = array();
	foreach ( (array) ( $prices['prices'] ?? array() ) as $p ) {
		if ( is_array( $p ) && isset( $p[0], $p[1] ) && is_numeric( $p[1] ) ) {
			$by[ $day( $p[0] / 1000 ) ]['price'] = (float) $p[1];
		}
	}
	foreach ( (array) ( $net['hashrates'] ?? array() ) as $r ) {
		if ( isset( $r['timestamp'], $r['avgHashrate'] ) ) {
			$by[ $day( $r['timestamp'] ) ]['hashrate'] = (float) $r['avgHashrate'];
		}
	}
	$adjust = array();
	foreach ( (array) ( $net['difficulty'] ?? array() ) as $r ) {
		$t = $r['time'] ?? ( $r['timestamp'] ?? null );
		if ( null !== $t && isset( $r['difficulty'] ) ) {
			$adjust[ (int) $t ] = (float) $r['difficulty'];
		}
	}
	ksort( $adjust );
	foreach ( (array) $fees as $r ) {
		if ( is_array( $r ) && isset( $r['timestamp'], $r['avgFees'] ) ) {
			$by[ $day( $r['timestamp'] ) ]['fees'] = (float) $r['avgFees'] / 1e8;
		}
	}
	if ( ! $by ) {
		delete_transient( 'mm_history_backfill_lock' );
		return false;
	}
	ksort( $by );
	$rows      = mm_history();
	$last_fee  = null;
	$last_hash = null;
	foreach ( $by as $date => $v ) {
		$ts   = strtotime( $date . ' 12:00:00 UTC' );
		$diff = null;
		foreach ( $adjust as $when => $d ) {
			if ( $when <= $ts ) {
				$diff = $d;
			} else {
				break;
			}
		}
		$last_fee  = $v['fees'] ?? $last_fee;
		$last_hash = $v['hashrate'] ?? $last_hash;
		if ( isset( $rows[ $date ] ) || empty( $v['price'] ) || null === $diff ) {
			continue; // Keep live-recorded rows.
		}
		$rows[ $date ] = array( (float) $v['price'], $diff, $last_hash, $last_fee );
	}
	mm_history_save( $rows );
	delete_transient( 'mm_history_backfill_lock' );
	return true;
}

/**
 * History as rows for charts: [ts, price, hashprice $/PH/day, difficulty, hashrate, fees].
 */
function mm_history_series( int $days = 400 ): array {
	$out  = array();
	$from = time() - $days * DAY_IN_SECONDS;
	foreach ( mm_history() as $date => $r ) {
		$ts = strtotime( $date . ' 12:00:00 UTC' );
		if ( $ts < $from || empty( $r[0] ) || empty( $r[1] ) ) {
			continue;
		}
		$fees  = isset( $r[3] ) && is_numeric( $r[3] ) ? (float) $r[3] : 0.0;
		$hp    = mm_btc_per_th_day( (float) $r[1], mm_btc_subsidy_at_time( $ts ) + $fees ) * (float) $r[0] * 1000;
		$out[] = array( $ts, round( (float) $r[0], 2 ), round( $hp, 4 ), (float) $r[1], isset( $r[2] ) ? (float) $r[2] : null, round( $fees, 5 ) );
	}
	return $out;
}

/**
 * Average monthly difficulty growth over the last N days (used as the default scenario).
 */
function mm_difficulty_growth_monthly( int $days = 180 ): ?float {
	$s = mm_history_series( $days + 5 );
	if ( count( $s ) < 30 ) {
		return null;
	}
	$first = $s[0];
	$last  = $s[ count( $s ) - 1 ];
	$span  = max( 1, ( $last[0] - $first[0] ) / ( 30.44 * DAY_IN_SECONDS ) );
	return $first[3] > 0 ? ( pow( $last[3] / $first[3], 1 / $span ) - 1 ) * 100 : null;
}

/* ---------------------------------------------------------------------------
 * Altcoins: Litecoin + Dogecoin (merged Scrypt) and Kaspa
 * ------------------------------------------------------------------------- */

/**
 * Kaspa emission per day. Since May 2022 the reward started at 440 KAS/s and falls by a factor of
 * (1/2)^(1/12) every month ("chromatic halving").
 */
function mm_kaspa_emission_day( ?int $ts = null ): float {
	$ts     = $ts ?? time();
	$months = max( 0, (int) floor( ( $ts - 1651881600 ) / ( 30.4375 * DAY_IN_SECONDS ) ) ); // From 2022-05-07.
	return 440 * pow( 0.5, $months / 12 ) * 86400;
}

/**
 * Convert an API hashrate to H/s, accepting it only when it lands in a plausible range.
 */
function mm_alt_hashrate( $value, float $unit, float $min, float $max ): ?float {
	if ( ! is_numeric( $value ) || (float) $value <= 0 ) {
		return null;
	}
	foreach ( array( $unit, $unit * 1000, $unit / 1000 ) as $u ) {
		$hs = (float) $value * $u;
		if ( $hs >= $min && $hs <= $max ) {
			return $hs;
		}
	}
	return null;
}

function mm_alt_fetch(): array {
	$cur    = mm_market_currency();
	$prices = mm_market_get_json( 'https://api.coingecko.com/api/v3/simple/price?ids=litecoin,dogecoin,kaspa&vs_currencies=' . $cur . '&include_24hr_change=true' );
	$ltc    = mm_market_get_json( 'https://litecoinspace.org/api/v1/mining/hashrate/3d' );
	$ltc_h  = mm_market_get_json( 'https://litecoinspace.org/api/blocks/tip/height' );
	$kas    = mm_market_get_json( 'https://api.kaspa.org/info/hashrate?stringOnly=false' );

	$p = static function ( string $id ) use ( $prices, $cur ) {
		return array(
			'price'  => isset( $prices[ $id ][ $cur ] ) && is_numeric( $prices[ $id ][ $cur ] ) ? (float) $prices[ $id ][ $cur ] : null,
			'change' => isset( $prices[ $id ][ $cur . '_24h_change' ] ) && is_numeric( $prices[ $id ][ $cur . '_24h_change' ] ) ? (float) $prices[ $id ][ $cur . '_24h_change' ] : null,
		);
	};
	$ltc_hash = mm_alt_hashrate( $ltc['currentHashrate'] ?? null, 1, 1e14, 1e17 );
	$height   = is_numeric( $ltc_h ) ? (int) $ltc_h : null;
	$kas_hash = mm_alt_hashrate( is_array( $kas ) ? ( $kas['hashrate'] ?? null ) : $kas, 1e12, 1e16, 1e20 );

	return array(
		'currency'   => $cur,
		'ltc'        => $p( 'litecoin' ) + array(
			'hashrate'   => $ltc_hash,
			'difficulty' => isset( $ltc['currentDifficulty'] ) && is_numeric( $ltc['currentDifficulty'] ) ? (float) $ltc['currentDifficulty'] : null,
			'height'     => $height,
			'reward'     => null !== $height ? 50 / pow( 2, (int) floor( $height / 840000 ) ) : 6.25,
			'block_time' => 150,
		),
		'doge'       => $p( 'dogecoin' ) + array(
			'hashrate'   => null,
			'reward'     => 10000,
			'block_time' => 60,
		),
		'kas'        => $p( 'kaspa' ) + array(
			'hashrate'     => $kas_hash,
			'emission_day' => mm_kaspa_emission_day(),
		),
		'updated_at' => time(),
	);
}

/**
 * Cached altcoin data (15 minutes), with last good values and your overrides from
 * Customize › Mine Mirth · Mining tools.
 */
function mm_alt_market( bool $refresh = false ): array {
	$cur  = mm_market_currency();
	$data = get_transient( MM_ALT_CACHE . $cur );
	if ( $refresh && ( ! is_array( $data ) || time() - (int) ( $data['updated_at'] ?? 0 ) > 15 * MINUTE_IN_SECONDS ) && ! get_transient( 'mm_alt_lock' ) ) {
		set_transient( 'mm_alt_lock', 1, 30 );
		$fresh = mm_alt_fetch();
		$last  = get_transient( MM_ALT_LAST_GOOD . $cur );
		if ( is_array( $last ) ) {
			foreach ( array( 'ltc', 'doge', 'kas' ) as $coin ) {
				foreach ( $fresh[ $coin ] as $k => $v ) {
					if ( null === $v && isset( $last[ $coin ][ $k ] ) ) {
						$fresh[ $coin ][ $k ] = $last[ $coin ][ $k ];
						$fresh['is_stale']    = true;
					}
				}
			}
		}
		set_transient( MM_ALT_CACHE . $cur, $fresh, DAY_IN_SECONDS );
		if ( null !== $fresh['ltc']['price'] ) {
			set_transient( MM_ALT_LAST_GOOD . $cur, $fresh, WEEK_IN_SECONDS );
		}
		delete_transient( 'mm_alt_lock' );
		$data = $fresh;
	}
	if ( ! is_array( $data ) ) {
		$data = get_transient( MM_ALT_LAST_GOOD . $cur );
	}
	$data = is_array( $data ) ? $data : array(
		'currency'   => $cur,
		'ltc'        => array( 'price' => null, 'change' => null, 'hashrate' => null, 'reward' => 6.25, 'block_time' => 150 ),
		'doge'       => array( 'price' => null, 'change' => null, 'hashrate' => null, 'reward' => 10000, 'block_time' => 60 ),
		'kas'        => array( 'price' => null, 'change' => null, 'hashrate' => null, 'emission_day' => mm_kaspa_emission_day() ),
		'updated_at' => 0,
	);
	// Your overrides (PH/s) win; Dogecoin is merge-mined with Litecoin, so it follows LTC unless set.
	foreach ( array( 'ltc' => 'tools_ltc_hash', 'doge' => 'tools_doge_hash', 'kas' => 'tools_kas_hash' ) as $coin => $opt ) {
		$ph = (float) mm_opt( $opt );
		if ( $ph > 0 ) {
			$data[ $coin ]['hashrate'] = $ph * 1e15;
			$data[ $coin ]['manual']   = true;
		}
	}
	if ( empty( $data['doge']['hashrate'] ) && ! empty( $data['ltc']['hashrate'] ) ) {
		$data['doge']['hashrate'] = (float) $data['ltc']['hashrate'] * max( 0.1, (float) mm_opt( 'tools_doge_ratio' ) );
	}
	$data['kas']['emission_day'] = mm_kaspa_emission_day();
	return $data;
}

/* ---------------------------------------------------------------------------
 * Store catalog
 * ------------------------------------------------------------------------- */

/**
 * Hashing algorithm for a product: the "algorithm" attribute when present, else the model name.
 */
function mm_product_algorithm( $product, string $hash_raw = '' ): string {
	$attr = strtolower( (string) $product->get_attribute( 'algorithm' ) . ' ' . (string) $product->get_attribute( 'coin' ) );
	$map  = array(
		'sha256'     => '/sha-?256|bitcoin|\bbtc\b|\bbch\b/',
		'scrypt'     => '/scrypt|litecoin|dogecoin|\bltc\b|\bdoge\b/',
		'kheavyhash' => '/kheavy|kaspa|\bkas\b/',
		'blake3'     => '/blake3|alephium|\balph\b/',
		'etchash'    => '/etchash|ethash|\betc\b/',
		'equihash'   => '/equihash|zcash|\bzec\b/',
		'x11'        => '/\bx11\b|\bdash\b/',
	);
	foreach ( $map as $algo => $re ) {
		if ( trim( $attr ) && preg_match( $re, $attr ) ) {
			return $algo;
		}
	}
	$name = ' ' . $product->get_name() . ' ';
	$name_map = array(
		'kheavyhash' => '/\bKS\s?\d|ice\s?river|kaspa/i',
		'blake3'     => '/AL[\s-]?BOX|\bAL\s?\d|alephium/i',
		'scrypt'     => '/\bL[379]\b|\bL7\b|\bL9\b|\bDG\s?(1|Home|Max)|Mini\s?DOGE|\bLT\d|\bDG1\+?|scrypt|litecoin|dogecoin/i',
		'etchash'    => '/\bE9\b|Jasminer\s?X|etchash/i',
		'equihash'   => '/\bZ15\b|\bZ11\b|equihash/i',
		'sha256'     => '/\b[ST]\d{2}|\bS19|\bS21|\bS23|\bT21|\bM[3-7]\d|\bA1[3-9]\d{2}|Avalon|Bitaxe|Nerd|Antminer|Whatsminer|sha-?256|bitcoin/i',
	);
	foreach ( $name_map as $algo => $re ) {
		if ( preg_match( $re, $name ) ) {
			return $algo;
		}
	}
	return preg_match( '/TH\/?s|PH\/?s/i', $hash_raw ) ? 'sha256' : 'other';
}

function mm_product_cooling( $product ): string {
	$text = $product->get_name() . ' ' . (string) $product->get_attribute( 'cooling' );
	if ( preg_match( '/immersion|\bimm\b|\bIMM\b/i', $text ) ) {
		return 'immersion';
	}
	if ( preg_match( '/hydro|\bhyd\b|water[\s-]?cool|liquid/i', $text ) ) {
		return 'hydro';
	}
	return 'air';
}

function mm_product_brand( $product ): string {
	if ( taxonomy_exists( 'product_brand' ) ) {
		$terms = get_the_terms( $product->get_id(), 'product_brand' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			return (string) $terms[0]->name;
		}
	}
	$attr = (string) $product->get_attribute( 'brand' );
	if ( $attr ) {
		return trim( explode( ',', $attr )[0] );
	}
	$map = array(
		'Bitmain'   => '/antminer|bitmain/i',
		'MicroBT'   => '/whatsminer|microbt/i',
		'Canaan'    => '/avalon|canaan/i',
		'Goldshell' => '/goldshell|AL[\s-]?BOX|Mini\s?DOGE/i',
		'IceRiver'  => '/ice\s?river|\bKS\s?\d/i',
		'Elphapex'  => '/elphapex|\bDG\s?(1|Home|Max)/i',
		'Bitaxe'    => '/bitaxe/i',
		'NerdAxe'   => '/nerd\s?(q|oct)?axe/i',
		'Jasminer'  => '/jasminer/i',
	);
	foreach ( $map as $brand => $re ) {
		if ( preg_match( $re, $product->get_name() ) ) {
			return $brand;
		}
	}
	return '';
}

/**
 * Every published product with mining specs, cached until a product changes.
 *
 * @return array<int,array>
 */
function mm_catalog(): array {
	if ( ! mm_is_woo() ) {
		return array();
	}
	$cached = get_option( MM_CATALOG_OPTION );
	if ( is_array( $cached ) && isset( $cached['v'], $cached['rows'] ) && 5 === (int) $cached['v'] && time() - (int) $cached['t'] < DAY_IN_SECONDS ) {
		return $cached['rows'];
	}
	$rows = array();
	$ids  = wc_get_products( array( 'status' => 'publish', 'limit' => 600, 'return' => 'ids', 'orderby' => 'menu_order', 'order' => 'ASC' ) );
	foreach ( $ids as $id ) {
		$p = wc_get_product( $id );
		if ( ! $p || ! $p->is_visible() ) {
			continue;
		}
		$specs = mm_product_mining_specs( $p );
		$raw   = (string) ( $specs['raw'][0] ?? '' );
		$hs    = $specs['th'] * 1e12;
		$algo  = mm_product_algorithm( $p, $raw );
		$watts = (float) $specs['watts'];
		$price = (float) $p->get_price();
		$text  = $p->get_name() . ' ' . wp_strip_all_tags( (string) $p->get_short_description() );
		$img   = $p->get_image_id() ? wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_thumbnail' ) : '';
		$rows[] = array(
			'id'      => (int) $id,
			'name'    => $p->get_name(),
			'url'     => (string) get_permalink( $id ),
			'price'   => $price,
			'regular' => (float) $p->get_regular_price(),
			'img'     => $img ? (string) $img : '',
			'hash'    => $hs,
			'th'      => (float) $specs['th'],
			'watts'   => $watts,
			'eff'     => $hs > 0 && $watts > 0 ? $watts / ( $hs / 1e12 ) : 0.0, // J/TH (comparable within an algorithm).
			'algo'    => $algo,
			'cooling' => mm_product_cooling( $p ),
			'brand'   => mm_product_brand( $p ),
			'home'    => ( $watts > 0 && $watts <= 1600 ) || (bool) preg_match( '/\bhome\b|\bmini\b|\bnano\b|bitaxe|nerd|lucky|quiet|silent/i', $text ),
			'solo'    => (bool) preg_match( '/bitaxe|nerd\s?(q|oct)?axe|lucky\s?miner|\bsolo\b|avalon\s?(nano|q)/i', $text ),
			'stock'   => $p->get_stock_status(),
			'cats'    => wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'slugs' ) ),
			'date'    => (int) get_post_time( 'U', true, $id ),
			'sales'   => (int) $p->get_total_sales(),
			'is_part' => $hs <= 0,
		);
	}
	update_option( MM_CATALOG_OPTION, array( 'v' => 5, 't' => time(), 'rows' => $rows ), false );
	return $rows;
}

function mm_catalog_flush(): void {
	delete_option( MM_CATALOG_OPTION );
	delete_transient( 'mm_calc_product_presets' );
}
foreach ( array( 'woocommerce_update_product', 'woocommerce_new_product', 'woocommerce_delete_product', 'woocommerce_trash_product', 'woocommerce_product_set_stock_status', 'woocommerce_variation_set_stock_status' ) as $mm_hook ) {
	add_action( $mm_hook, 'mm_catalog_flush' );
}
unset( $mm_hook );

/**
 * Catalog rows matching a filter (used by the tools and by product sections on landing pages).
 *
 * @param array $f algo, cooling, brand, home, solo, cat, sort (efficiency|value|newest|popular|price), limit, stock (any|available).
 */
function mm_catalog_filter( array $f ): array {
	$f    = wp_parse_args( $f, array( 'algo' => '', 'cooling' => '', 'brand' => '', 'home' => false, 'solo' => false, 'cat' => '', 'sort' => '', 'limit' => 0, 'stock' => 'any', 'miners' => true, 'parts' => false ) );
	$rows = array_filter(
		mm_catalog(),
		static function ( $r ) use ( $f ) {
			if ( $f['miners'] && $r['is_part'] ) {
				return false;
			}
			if ( $f['parts'] && ! $r['is_part'] ) {
				return false;
			}
			if ( $f['algo'] && $r['algo'] !== $f['algo'] ) {
				return false;
			}
			if ( $f['cooling'] && $r['cooling'] !== $f['cooling'] ) {
				return false;
			}
			// Brand: one name or several separated by "|" (for example "Bitmain|Antminer").
			if ( $f['brand'] && ! preg_match( '/' . implode( '|', array_map( static fn( $b ) => preg_quote( trim( $b ), '/' ), explode( '|', (string) $f['brand'] ) ) ) . '/i', $r['brand'] . ' ' . $r['name'] ) ) {
				return false;
			}
			if ( $f['home'] && ! $r['home'] ) {
				return false;
			}
			if ( $f['solo'] && ! $r['solo'] ) {
				return false;
			}
			if ( $f['cat'] && ! in_array( $f['cat'], (array) $r['cats'], true ) ) {
				return false;
			}
			return 'available' !== $f['stock'] || 'outofstock' !== $r['stock'];
		}
	);
	$sorts = array(
		'efficiency' => static fn( $a, $b ) => ( $a['eff'] ?: INF ) <=> ( $b['eff'] ?: INF ),
		'value'      => static fn( $a, $b ) => ( $a['th'] > 0 && $a['price'] > 0 ? $a['price'] / $a['th'] : INF ) <=> ( $b['th'] > 0 && $b['price'] > 0 ? $b['price'] / $b['th'] : INF ),
		'newest'     => static fn( $a, $b ) => $b['date'] <=> $a['date'],
		'popular'    => static fn( $a, $b ) => $b['sales'] <=> $a['sales'],
		'price'      => static fn( $a, $b ) => $a['price'] <=> $b['price'],
		'hashrate'   => static fn( $a, $b ) => $b['hash'] <=> $a['hash'],
	);
	if ( isset( $sorts[ $f['sort'] ] ) ) {
		usort( $rows, $sorts[ $f['sort'] ] );
	}
	// In stock first, keeping the chosen order within each group.
	$rank = array( 'instock' => 0, 'onbackorder' => 1, 'outofstock' => 2 );
	$i    = 0;
	$rows = array_map( static fn( $r ) => $r + array( '_i' => $i++ ), array_values( $rows ) );
	usort( $rows, static fn( $a, $b ) => array( $rank[ $a['stock'] ] ?? 3, $a['_i'] ) <=> array( $rank[ $b['stock'] ] ?? 3, $b['_i'] ) );
	return $f['limit'] ? array_slice( $rows, 0, (int) $f['limit'] ) : $rows;
}

/**
 * Hosting rate ($/kWh) from the published Hosting Plans, for the hosting-vs-home tool.
 */
function mm_hosting_rates(): array {
	$out = array();
	if ( function_exists( 'mm_get_hosting_plans' ) ) {
		foreach ( mm_get_hosting_plans( 12 ) as $plan ) {
			$rate = (string) ( $plan['rate'] ?? '' );
			if ( preg_match( '/(\d+(?:\.\d+)?)\s*(?:¢|c\b|cents)/i', $rate, $m ) ) {
				$value = (float) $m[1] / 100;
			} else {
				$value = mm_parse_number( $rate );
			}
			if ( $value > 0 && $value < 1 ) {
				$out[] = array( 'name' => (string) ( $plan['title'] ?? '' ), 'rate' => $value, 'url' => (string) ( $plan['url'] ?? '' ) );
			}
		}
	}
	return $out;
}

/* ---------------------------------------------------------------------------
 * Payload for the tools (inline on the page, refreshed over REST)
 * ------------------------------------------------------------------------- */

function mm_tools_payload( bool $refresh = false, bool $with_history = true ): array {
	$market = $refresh && mm_opt( 'market_enable' ) ? mm_market_refresh() : mm_market_cached();
	$btc    = mm_btc_economics( $market );
	$catalog = array_values(
		array_map(
			static fn( $r ) => array_intersect_key( $r, array_flip( array( 'id', 'name', 'url', 'price', 'img', 'hash', 'th', 'watts', 'eff', 'algo', 'cooling', 'brand', 'stock', 'home', 'solo' ) ) ),
			mm_catalog_filter( array( 'miners' => true ) )
		)
	);
	$presets = array();
	foreach ( mm_lines( mm_opt( 'calc_presets' ) ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( count( $parts ) >= 3 && is_numeric( $parts[1] ) && is_numeric( $parts[2] ) ) {
			$presets[] = array( 'name' => $parts[0], 'th' => (float) $parts[1], 'watts' => (float) $parts[2], 'price' => isset( $parts[3] ) && is_numeric( $parts[3] ) ? (float) $parts[3] : 0 );
		}
	}
	$halving = $market['halving'] ?? null;
	return array(
		'currency'  => mm_market_currency(),
		'symbol'    => mm_currency_symbol( mm_market_currency() ),
		'status'    => (string) ( $market['status'] ?? 'unavailable' ),
		'updated'   => (int) ( $market['updated_at'] ?? 0 ),
		'btc'       => $btc + array(
			'change'     => $market['change'] ?? null,
			'adjustment' => $market['adjustment'] ?? null,
			'halving'    => $halving,
			'growth'     => mm_difficulty_growth_monthly(),
		),
		'alt'       => mm_alt_market( $refresh ),
		'history'   => $with_history ? mm_history_series( 730 ) : array(),
		'catalog'   => $catalog,
		'presets'   => $presets,
		'hosting'   => mm_hosting_rates(),
		'defaults'  => array(
			'kwh'      => (float) mm_opt( 'calc_kwh' ),
			'pool_fee' => (float) mm_opt( 'calc_pool_fee' ),
		),
		'links'     => array(
			'shop'    => mm_shop_url(),
			'hosting' => home_url( '/miner-hosting/' ),
			'contact' => home_url( '/contact/' ),
		),
	);
}

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'mine-mirth/v1',
			'/tools',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => static function ( WP_REST_Request $r ) {
					if ( $r->get_param( 'history' ) && mm_opt( 'market_enable' ) && count( mm_history() ) < 30 ) {
						mm_history_backfill( time() - (int) get_option( 'mm_history_backfill_at' ) > HOUR_IN_SECONDS );
					}
					$data     = mm_tools_payload( true, (bool) $r->get_param( 'history' ) );
					$response = new WP_REST_Response( $data, 200 );
					$response->header( 'Cache-Control', 'public, max-age=60' );
					return $response;
				},
			)
		);
	}
);

// Keep history filled: weekly backfill from wp-admin visits (never slows down visitors) and from the
// tools' own REST requests below; the daily row is recorded whenever the market snapshot refreshes.
add_action(
	'shutdown',
	static function () {
		if ( ! is_admin() || wp_doing_ajax() || ! mm_opt( 'market_enable' ) ) {
			return;
		}
		if ( time() - (int) get_option( 'mm_history_backfill_at' ) > WEEK_IN_SECONDS && ! get_transient( 'mm_history_backfill_lock' ) ) {
			mm_history_backfill();
		}
	},
	50
);
