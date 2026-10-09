<?php
/**
 * Plugin Name:       Mine Mirth ASIC Market Scope
 * Description:       ASIC Market Scope, long-term view: live market data for ASIC-mineable coins with a transparent long-term score (market size, liquidity, 1-year trend, distance from all-time high, supply schedule, mining ecosystem), search, filters and per-coin insights. Shortcode: [minemirth_market_scope].
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-market-scope
 *
 * @package MineMirthMarketScope
 */

defined( 'ABSPATH' ) || exit;

define( 'MMMS_VERSION', '1.0.0' );
define( 'MMMS_URI', plugin_dir_url( __FILE__ ) );

/**
 * Mining ecosystem rating per algorithm (0–100) with the reason shown to visitors.
 * This is Mine Mirth's editorial view of ASIC availability, manufacturer choice
 * and resale/hosting depth, and the only part of the score that is not market data.
 */
function mmms_algorithms(): array {
	return apply_filters(
		'mmms_algorithms',
		array(
			'SHA-256'       => array( 100, 'The largest ASIC market: many manufacturers, the deepest resale and hosting market, and the most secure network.' ),
			'Scrypt'        => array( 85, 'A mature ASIC market with several manufacturers; Litecoin and Dogecoin are mined together, so one machine earns both.' ),
			'kHeavyHash'    => array( 70, 'A fast-growing ASIC market with machines from several manufacturers.' ),
			'Equihash'      => array( 65, 'Several generations of dedicated ASICs.' ),
			'X11'           => array( 60, 'Long-established ASICs, mostly from a few manufacturers.' ),
			'Multi-algo'    => array( 55, 'Mined with several algorithms, including SHA-256 and Scrypt ASICs.' ),
			'Etchash'       => array( 55, 'Dedicated ASICs exist alongside GPU mining.' ),
			'Blake3'        => array( 55, 'Newer ASICs from a few manufacturers.' ),
			'Ethash'        => array( 45, 'Dedicated ASICs exist, but the network is small.' ),
			'Blake256R14'   => array( 50, 'Dedicated ASICs; part of block rewards also go to stakers and the treasury.' ),
			'Eaglesong'     => array( 50, 'Dedicated ASICs from a few manufacturers.' ),
			'Blake2S'       => array( 45, 'Dedicated ASICs, with a small number of models.' ),
			'Blake2B+SHA3'  => array( 45, 'Dedicated ASICs, with a small number of models.' ),
			'Blake2B'       => array( 45, 'Dedicated ASICs, with a small number of models.' ),
			'SHA-512/256d'  => array( 45, 'Newer ASICs from a few manufacturers.' ),
			'Cuckatoo32'    => array( 35, 'Very few ASIC models are available.' ),
		)
	);
}

/**
 * ASIC-mineable coins: CoinGecko id, name, symbol, algorithm, and what it is merged-mined with.
 * Coins without dedicated ASICs (Monero, Ravencoin, Ergo…) or no longer mined are left out.
 */
function mmms_coins(): array {
	$rows = array(
		array( 'bitcoin', 'Bitcoin', 'BTC', 'SHA-256', '' ),
		array( 'bitcoin-cash', 'Bitcoin Cash', 'BCH', 'SHA-256', '' ),
		array( 'bitcoin-cash-sv', 'Bitcoin SV', 'BSV', 'SHA-256', '' ),
		array( 'ecash', 'eCash', 'XEC', 'SHA-256', '' ),
		array( 'fractal-bitcoin', 'Fractal Bitcoin', 'FB', 'SHA-256', 'Bitcoin' ),
		array( 'namecoin', 'Namecoin', 'NMC', 'SHA-256', 'Bitcoin' ),
		array( 'syscoin', 'Syscoin', 'SYS', 'SHA-256', 'Bitcoin' ),
		array( 'elastos', 'Elastos', 'ELA', 'SHA-256', 'Bitcoin' ),
		array( 'hathor', 'Hathor', 'HTR', 'SHA-256', 'Bitcoin' ),
		array( 'emercoin', 'Emercoin', 'EMC', 'SHA-256', 'Bitcoin' ),
		array( 'unobtanium', 'Unobtanium', 'UNO', 'SHA-256', 'Bitcoin' ),
		array( 'peercoin', 'Peercoin', 'PPC', 'SHA-256', '' ),
		array( 'litecoin-cash', 'Litecoin Cash', 'LCC', 'SHA-256', '' ),
		array( 'litecoin', 'Litecoin', 'LTC', 'Scrypt', '' ),
		array( 'dogecoin', 'Dogecoin', 'DOGE', 'Scrypt', 'Litecoin' ),
		array( 'bellscoin', 'Bellscoin', 'BEL', 'Scrypt', 'Litecoin' ),
		array( 'digibyte', 'DigiByte', 'DGB', 'Multi-algo', '' ),
		array( 'verge', 'Verge', 'XVG', 'Multi-algo', '' ),
		array( 'zcash', 'Zcash', 'ZEC', 'Equihash', '' ),
		array( 'dash', 'Dash', 'DASH', 'X11', '' ),
		array( 'kaspa', 'Kaspa', 'KAS', 'kHeavyHash', '' ),
		array( 'alephium', 'Alephium', 'ALPH', 'Blake3', '' ),
		array( 'kadena', 'Kadena', 'KDA', 'Blake2S', '' ),
		array( 'nervos-network', 'Nervos Network', 'CKB', 'Eaglesong', '' ),
		array( 'handshake', 'Handshake', 'HNS', 'Blake2B+SHA3', '' ),
		array( 'siacoin', 'Siacoin', 'SC', 'Blake2B', '' ),
		array( 'decred', 'Decred', 'DCR', 'Blake256R14', '' ),
		array( 'ethereum-classic', 'Ethereum Classic', 'ETC', 'Etchash', '' ),
		array( 'ethereum-pow-iou', 'EthereumPoW', 'ETHW', 'Ethash', '' ),
		array( 'radiant', 'Radiant', 'RXD', 'SHA-512/256d', '' ),
		array( 'grin', 'Grin', 'GRIN', 'Cuckatoo32', '' ),
	);
	$out = array();
	foreach ( $rows as $r ) {
		$out[] = array( 'id' => $r[0], 'name' => $r[1], 'symbol' => $r[2], 'algo' => $r[3], 'merged' => $r[4] );
	}
	return apply_filters( 'mmms_coins', $out );
}

/**
 * Market data for the coins from CoinGecko, cached for 10 minutes on the server
 * so visitors never hit the API's rate limit. The last good copy is kept.
 *
 * @return array{status:string,updated_at:int,coins:array}
 */
function mmms_market_data(): array {
	$cached = get_transient( 'mmms_market_v1' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	if ( get_transient( 'mmms_market_lock' ) ) {
		$last = get_option( 'mmms_market_last', array() );
		return is_array( $last ) && $last ? array_merge( $last, array( 'status' => 'stale' ) ) : array( 'status' => 'unavailable', 'updated_at' => 0, 'coins' => array() );
	}
	set_transient( 'mmms_market_lock', 1, 30 );

	$ids = implode( ',', wp_list_pluck( mmms_coins(), 'id' ) );
	$url = add_query_arg(
		array(
			'vs_currency'             => 'usd',
			'ids'                     => $ids,
			'order'                   => 'market_cap_desc',
			'per_page'                => 250,
			'page'                    => 1,
			'sparkline'               => 'true',
			'price_change_percentage' => '24h,7d,30d,1y',
		),
		'https://api.coingecko.com/api/v3/coins/markets'
	);
	$res = wp_remote_get( $url, array( 'timeout' => 15, 'headers' => array( 'Accept' => 'application/json' ) ) );
	$raw = ( ! is_wp_error( $res ) && 200 === wp_remote_retrieve_response_code( $res ) ) ? json_decode( (string) wp_remote_retrieve_body( $res ), true ) : null;

	if ( ! is_array( $raw ) || ! $raw ) {
		delete_transient( 'mmms_market_lock' );
		$last = get_option( 'mmms_market_last', array() );
		$data = is_array( $last ) && $last ? array_merge( $last, array( 'status' => 'stale' ) ) : array( 'status' => 'unavailable', 'updated_at' => 0, 'coins' => array() );
		set_transient( 'mmms_market_v1', $data, 2 * MINUTE_IN_SECONDS );
		return $data;
	}

	$coins = array();
	foreach ( $raw as $c ) {
		if ( empty( $c['id'] ) ) {
			continue;
		}
		$spark = array_values( array_filter( (array) ( $c['sparkline_in_7d']['price'] ?? array() ), 'is_numeric' ) );
		if ( count( $spark ) > 42 ) {
			$step  = count( $spark ) / 42;
			$spark = array_map( static fn( $i ) => $spark[ (int) floor( $i * $step ) ], range( 0, 41 ) );
		}
		$coins[ $c['id'] ] = array(
			'image'   => (string) ( $c['image'] ?? '' ),
			'price'   => $c['current_price'] ?? null,
			'mcap'    => $c['market_cap'] ?? null,
			'rank'    => $c['market_cap_rank'] ?? null,
			'volume'  => $c['total_volume'] ?? null,
			'ch24h'   => $c['price_change_percentage_24h_in_currency'] ?? ( $c['price_change_percentage_24h'] ?? null ),
			'ch7d'    => $c['price_change_percentage_7d_in_currency'] ?? null,
			'ch30d'   => $c['price_change_percentage_30d_in_currency'] ?? null,
			'ch1y'    => $c['price_change_percentage_1y_in_currency'] ?? null,
			'ath'     => $c['ath'] ?? null,
			'athPct'  => $c['ath_change_percentage'] ?? null,
			'athDate' => isset( $c['ath_date'] ) ? substr( (string) $c['ath_date'], 0, 10 ) : '',
			'circ'    => $c['circulating_supply'] ?? null,
			'max'     => $c['max_supply'] ?? null,
			'spark'   => array_map( static fn( $v ) => round( (float) $v, 8 ), $spark ),
		);
	}
	$data = array( 'status' => 'live', 'updated_at' => time(), 'coins' => $coins );
	set_transient( 'mmms_market_v1', $data, 10 * MINUTE_IN_SECONDS );
	update_option( 'mmms_market_last', $data, false );
	delete_transient( 'mmms_market_lock' );
	return $data;
}

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'mine-mirth-scope/v1',
			'/coins',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static function () {
					$res = rest_ensure_response( mmms_market_data() );
					$res->header( 'Cache-Control', 'public, max-age=120' );
					return $res;
				},
			)
		);
	}
);

/**
 * [minemirth_market_scope title="" intro="yes" method="yes"]
 */
function mmms_shortcode( $atts ): string {
	$a = shortcode_atts(
		array(
			// The Mine Mirth theme already shows the page title in its header.
			'title'  => function_exists( 'mm_page_hero' ) ? '' : __( 'ASIC Market Scope — Long-Term View', 'mine-mirth-market-scope' ),
			'intro'  => 'yes',
			'method' => 'yes',
		),
		$atts
	);
	wp_enqueue_style( 'mmms', MMMS_URI . 'assets/scope.css', array(), MMMS_VERSION );
	wp_enqueue_script( 'mmms', MMMS_URI . 'assets/scope.js', array(), MMMS_VERSION, true );

	$algos = array();
	foreach ( mmms_algorithms() as $name => $row ) {
		$algos[ $name ] = array( 'rating' => (int) $row[0], 'note' => (string) $row[1] );
	}
	$config = array(
		'endpoint' => rest_url( 'mine-mirth-scope/v1/coins' ),
		'coins'    => mmms_coins(),
		'algos'    => $algos,
		'shop'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '',
	);

	ob_start();
	?>
	<section class="mmms" data-mmms data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
		<?php if ( 'no' !== $a['intro'] ) : ?>
			<header class="mmms__intro">
				<span class="mmms__eyebrow"><?php esc_html_e( 'Live market data · long-term view', 'mine-mirth-market-scope' ); ?></span>
				<?php if ( '' !== trim( (string) $a['title'] ) ) : ?>
					<h2 class="mmms__title"><?php echo esc_html( $a['title'] ); ?></h2>
				<?php endif; ?>
				<p><?php esc_html_e( 'Every coin here can be mined with ASIC hardware. Each one gets a long-term score out of 100 built from live market data: how big and liquid its market is, its one-year trend, how far it sits from its all-time high, its supply schedule and the depth of its mining hardware ecosystem. Open a coin to see exactly how its score is made.', 'mine-mirth-market-scope' ); ?></p>
			</header>
		<?php endif; ?>

		<div class="mmms__toolbar">
			<label class="mmms__search"><span class="screen-reader-text"><?php esc_html_e( 'Search coins', 'mine-mirth-market-scope' ); ?></span><input type="search" data-mmms-search placeholder="<?php esc_attr_e( 'Search by coin, symbol or algorithm', 'mine-mirth-market-scope' ); ?>"></label>
			<label class="mmms__select"><span><?php esc_html_e( 'Algorithm', 'mine-mirth-market-scope' ); ?></span><select data-mmms-algo><option value=""><?php esc_html_e( 'All algorithms', 'mine-mirth-market-scope' ); ?></option></select></label>
			<label class="mmms__select"><span><?php esc_html_e( 'Sort by', 'mine-mirth-market-scope' ); ?></span>
				<select data-mmms-sort>
					<option value="score"><?php esc_html_e( 'Long-term score', 'mine-mirth-market-scope' ); ?></option>
					<option value="mcap"><?php esc_html_e( 'Market cap', 'mine-mirth-market-scope' ); ?></option>
					<option value="ch1y"><?php esc_html_e( '1-year change', 'mine-mirth-market-scope' ); ?></option>
					<option value="ch24h"><?php esc_html_e( '24-hour change', 'mine-mirth-market-scope' ); ?></option>
					<option value="name"><?php esc_html_e( 'Name', 'mine-mirth-market-scope' ); ?></option>
				</select>
			</label>
		</div>
		<div class="mmms__chips" role="group" aria-label="<?php esc_attr_e( 'Filter by score', 'mine-mirth-market-scope' ); ?>">
			<button type="button" class="mmms__chip is-active" data-mmms-band="all"><?php esc_html_e( 'All coins', 'mine-mirth-market-scope' ); ?> <span data-count="all"></span></button>
			<button type="button" class="mmms__chip is-high" data-mmms-band="high"><?php esc_html_e( 'Strong (66+)', 'mine-mirth-market-scope' ); ?> <span data-count="high"></span></button>
			<button type="button" class="mmms__chip is-medium" data-mmms-band="medium"><?php esc_html_e( 'Moderate (40–65)', 'mine-mirth-market-scope' ); ?> <span data-count="medium"></span></button>
			<button type="button" class="mmms__chip is-low" data-mmms-band="low"><?php esc_html_e( 'Weak (under 40)', 'mine-mirth-market-scope' ); ?> <span data-count="low"></span></button>
		</div>
		<p class="mmms__status" data-mmms-status aria-live="polite"><?php esc_html_e( 'Loading live market data…', 'mine-mirth-market-scope' ); ?></p>
		<div class="mmms__grid" data-mmms-grid></div>

		<?php if ( 'no' !== $a['method'] ) : ?>
			<details class="mmms__method">
				<summary><?php esc_html_e( 'How the long-term score works', 'mine-mirth-market-scope' ); ?></summary>
				<ul>
					<li><strong><?php esc_html_e( 'Market size, 25%:', 'mine-mirth-market-scope' ); ?></strong> <?php esc_html_e( 'market cap on a log scale, from $10 million (0) to $1 trillion (100).', 'mine-mirth-market-scope' ); ?></li>
					<li><strong><?php esc_html_e( 'One-year trend, 20%:', 'mine-mirth-market-scope' ); ?></strong> <?php esc_html_e( 'price change over 12 months, from −80% (0) to +100% (100). Coins with less history use 30 days.', 'mine-mirth-market-scope' ); ?></li>
					<li><strong><?php esc_html_e( 'Liquidity, 15%:', 'mine-mirth-market-scope' ); ?></strong> <?php esc_html_e( '24-hour trading volume as a share of market cap, from 0.5% (0) to 5% or more (100). Liquid coins are easier to sell after mining.', 'mine-mirth-market-scope' ); ?></li>
					<li><strong><?php esc_html_e( 'Distance from all-time high, 15%:', 'mine-mirth-market-scope' ); ?></strong> <?php esc_html_e( 'from 95% below its peak (0) to at its peak (100).', 'mine-mirth-market-scope' ); ?></li>
					<li><strong><?php esc_html_e( 'Mining ecosystem, 15%:', 'mine-mirth-market-scope' ); ?></strong> <?php esc_html_e( 'Mine Mirth’s rating of ASIC availability, manufacturer choice and resale and hosting depth for the coin’s algorithm. This is the only part that is our opinion rather than market data.', 'mine-mirth-market-scope' ); ?></li>
					<li><strong><?php esc_html_e( 'Supply schedule, 10%:', 'mine-mirth-market-scope' ); ?></strong> <?php esc_html_e( 'coins with a fixed maximum supply score 50 plus half the share already issued; coins without a cap score 35.', 'mine-mirth-market-scope' ); ?></li>
				</ul>
				<p><?php esc_html_e( 'Market data from CoinGecko, refreshed every 10 minutes. The score describes a coin’s market today; it is not a price prediction or financial advice.', 'mine-mirth-market-scope' ); ?></p>
			</details>
		<?php endif; ?>
	</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'minemirth_market_scope', 'mmms_shortcode' );
