<?php
/**
 * "Best choice" miners, picked automatically from live market data.
 *
 * Every few hours each miner with Hashrate and Power attributes is scored:
 *   50%  value: payback time at the reference power price (SHA-256 and Scrypt,
 *        from live network data), against the other miners and in absolute
 *        terms; other algorithms get a neutral value
 *   20%  efficiency against other miners of the same algorithm in your store
 *   20%  30-day price trend of the coin(s) it mines
 *   10%  availability (In Stock ahead of Pre-Order; Out of Stock never picked)
 * As prices, difficulty and stock change, so do the picks.
 *
 * @package MineMirthShopPro
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Product specs and algorithm
 * ------------------------------------------------------------------------- */

/**
 * Hashrate in TH/s and power in W from the product's attributes.
 *
 * @return array{th:float,w:float}
 */
function mmsp_specs( WC_Product $p ): array {
	if ( function_exists( 'mm_product_mining_specs' ) ) {
		$s = mm_product_mining_specs( $p );
		return array( 'th' => (float) $s['th'], 'w' => (float) $s['watts'] );
	}
	$hash = '';
	$pow  = '';
	foreach ( array( 'hashrate', 'hash-rate', 'hash_rate', 'hash rate' ) as $n ) {
		$hash = $hash ? $hash : (string) $p->get_attribute( $n );
	}
	foreach ( array( 'power', 'power-consumption', 'power consumption', 'wattage' ) as $n ) {
		$pow = $pow ? $pow : (string) $p->get_attribute( $n );
	}
	$num  = static fn( $v ) => (float) preg_replace( '/[^0-9.]/', '', str_replace( ',', '', explode( ',', (string) $v )[0] ) );
	$th   = $num( $hash );
	$unit = strtolower( (string) preg_replace( '/[^a-z]/i', '', explode( ',', $hash )[0] ) );
	$mult = array( 'phs' => 1000, 'ths' => 1, 'ghs' => 0.001, 'mhs' => 0.000001, 'khs' => 1e-9 );
	foreach ( $mult as $u => $m ) {
		if ( str_starts_with( $unit, rtrim( $u, 's' ) ) ) {
			$th *= $m;
			break;
		}
	}
	return array( 'th' => $th, 'w' => $num( $pow ) );
}

/**
 * Mining algorithm from an "Algorithm" attribute, or from the model name and categories.
 */
function mmsp_algorithm( WC_Product $p, float $th ): string {
	$attr = strtolower( trim( (string) $p->get_attribute( 'algorithm' ) . ' ' . (string) $p->get_attribute( 'algo' ) ) );
	$cats = wp_strip_all_tags( (string) wc_get_product_category_list( $p->get_id(), ' ' ) );
	$hay  = strtolower( $attr . ' ' . $p->get_name() . ' ' . $cats );
	$map  = array(
		'scrypt'     => '/scrypt|litecoin|\bltc\b|doge|antminer l\d|\bl[379]\b|elphapex|\bdg ?1|\bdg home|goldshell (lt|mini.?doge)/',
		'kheavyhash' => '/kheavyhash|kaspa|\bkas\b|antminer ks|\bks[0-9]|iceriver/',
		'blake3'     => '/blake3|alephium|\balph\b|\bal[01]\b|al-box/',
		'etchash'    => '/etchash|ethash|ethereum classic|\betc\b|antminer e\d|jasminer/',
		'equihash'   => '/equihash|zcash|\bzec\b|antminer z\d/',
		'x11'        => '/\bx11\b|\bdash\b|antminer d\d/',
		'blake2s'    => '/blake2s|kadena|\bkda\b|\bka3\b/',
		'eaglesong'  => '/eaglesong|nervos|\bckb\b|antminer k7/',
		'sha256'     => '/sha-?256|bitcoin|\bbtc\b|antminer [st]\d|whatsminer|avalon|canaan|bitaxe|nerdaxe|auradine|teraflux/',
	);
	foreach ( $map as $algo => $re ) {
		if ( preg_match( $re, $hay ) ) {
			return $algo;
		}
	}
	return $th >= 1 ? 'sha256' : 'other';
}

function mmsp_algo_label( string $algo ): string {
	$labels = array( 'sha256' => 'SHA-256', 'scrypt' => 'Scrypt', 'kheavyhash' => 'Kaspa', 'blake3' => 'Alephium', 'etchash' => 'Etchash', 'equihash' => 'Equihash', 'x11' => 'X11', 'blake2s' => 'Kadena', 'eaglesong' => 'Nervos', 'other' => 'Other' );
	return $labels[ $algo ] ?? $algo;
}

/**
 * Coins each algorithm earns, for the price trend (CoinGecko ids).
 */
function mmsp_algo_coins(): array {
	return array(
		'sha256'     => array( 'bitcoin' ),
		'scrypt'     => array( 'litecoin', 'dogecoin' ),
		'kheavyhash' => array( 'kaspa' ),
		'blake3'     => array( 'alephium' ),
		'etchash'    => array( 'ethereum-classic' ),
		'equihash'   => array( 'zcash' ),
		'x11'        => array( 'dash' ),
		'blake2s'    => array( 'kadena' ),
		'eaglesong'  => array( 'nervos-network' ),
	);
}

/* ---------------------------------------------------------------------------
 * Market data
 * ------------------------------------------------------------------------- */

function mmsp_get_json( string $url ) {
	$res = wp_remote_get( $url, array( 'timeout' => 12, 'headers' => array( 'Accept' => 'application/json' ) ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return null;
	}
	$body = wp_remote_retrieve_body( $res );
	$json = json_decode( (string) $body, true );
	return null === $json && is_numeric( trim( (string) $body ) ) ? (float) $body : $json;
}

/**
 * Market snapshot used for the picks.
 */
function mmsp_market(): array {
	$m = array( 'coins' => array() );

	// Coin prices and 30-day trends.
	$ids  = implode( ',', array_unique( array_merge( ...array_values( mmsp_algo_coins() ) ) ) );
	$list = mmsp_get_json( 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=' . rawurlencode( $ids ) . '&price_change_percentage=30d' );
	foreach ( is_array( $list ) ? $list : array() as $c ) {
		if ( ! empty( $c['id'] ) ) {
			$m['coins'][ $c['id'] ] = array(
				'price' => (float) ( $c['current_price'] ?? 0 ),
				'ch30'  => isset( $c['price_change_percentage_30d_in_currency'] ) ? (float) $c['price_change_percentage_30d_in_currency'] : null,
			);
		}
	}

	// Bitcoin network: the theme's cached data first, then mempool.space.
	$btc = function_exists( 'mm_market_cached' ) ? mm_market_cached() : array();
	if ( empty( $btc['hashrate'] ) && function_exists( 'mm_market_refresh' ) ) {
		$btc = mm_market_refresh();
	}
	$m['btc_price'] = (float) ( $btc['price'] ?? ( $m['coins']['bitcoin']['price'] ?? 0 ) );
	$m['btc_hash']  = (float) ( $btc['hashrate'] ?? 0 );
	$m['btc_fees']  = (float) ( $btc['fees'] ?? 0.02 );
	$m['btc_sub']   = (float) ( $btc['halving']['subsidy'] ?? 3.125 );
	if ( ! $m['btc_hash'] ) {
		$h              = mmsp_get_json( 'https://mempool.space/api/v1/mining/hashrate/3d' );
		$m['btc_hash']  = (float) ( $h['currentHashrate'] ?? 0 );
	}

	// Litecoin network (Dogecoin is merge-mined with it).
	$ltc            = mmsp_get_json( 'https://litecoinspace.org/api/v1/mining/hashrate/3d' );
	$m['ltc_hash']  = (float) ( $ltc['currentHashrate'] ?? 0 );
	$doge           = mmsp_get_json( 'https://api.blockchair.com/dogecoin/stats' );
	$m['doge_hash'] = (float) ( $doge['data']['hashrate_24h'] ?? 0 );
	if ( ! $m['doge_hash'] ) {
		$m['doge_hash'] = $m['ltc_hash'];
	}
	$m['at'] = time();
	return $m;
}

/**
 * Revenue per TH/s per day in USD for algorithms with live network data.
 */
function mmsp_revenue_per_th( string $algo, array $m ): ?float {
	if ( 'sha256' === $algo && $m['btc_hash'] > 0 && $m['btc_price'] > 0 ) {
		return 144 * ( $m['btc_sub'] + $m['btc_fees'] ) * $m['btc_price'] / ( $m['btc_hash'] / 1e12 );
	}
	if ( 'scrypt' === $algo && $m['ltc_hash'] > 0 ) {
		$ltc  = (float) ( $m['coins']['litecoin']['price'] ?? 0 );
		$doge = (float) ( $m['coins']['dogecoin']['price'] ?? 0 );
		if ( $ltc <= 0 ) {
			return null;
		}
		// 576 Litecoin blocks of 6.25 LTC and 1,440 Dogecoin blocks of 10,000 DOGE a day.
		$per_th = 576 * 6.25 * $ltc / ( $m['ltc_hash'] / 1e12 );
		if ( $doge > 0 && $m['doge_hash'] > 0 ) {
			$per_th += 1440 * 10000 * $doge / ( $m['doge_hash'] / 1e12 );
		}
		return $per_th;
	}
	return null;
}

/* ---------------------------------------------------------------------------
 * Scoring
 * ------------------------------------------------------------------------- */

function mmsp_is_preorder( WC_Product $p ): bool {
	foreach ( array( '_wc_pre_orders_enabled', '_ywpo_preorder' ) as $k ) {
		if ( 'yes' === get_post_meta( $p->get_id(), $k, true ) ) {
			return true;
		}
	}
	return 'instock' !== $p->get_stock_status() && 'outofstock' !== $p->get_stock_status();
}

/**
 * Score every miner and store the picks.
 */
function mmsp_refresh_best( bool $force = false ): array {
	$saved = get_option( 'mmsp_best', array() );
	$ttl   = max( 1, (int) mmsp_opt( 'best_hours' ) ) * HOUR_IN_SECONDS;
	if ( ! $force && is_array( $saved ) && ! empty( $saved['at'] ) && time() - (int) $saved['at'] < $ttl ) {
		return $saved;
	}
	if ( get_transient( 'mmsp_best_lock' ) ) {
		return is_array( $saved ) ? $saved : array();
	}
	set_transient( 'mmsp_best_lock', 1, 5 * MINUTE_IN_SECONDS );

	$m       = mmsp_market();
	$power   = max( 0, (float) mmsp_opt( 'best_power' ) );
	$exclude = array_filter( array_map( 'absint', explode( ',', (string) mmsp_opt( 'best_exclude' ) ) ) );
	$rows    = array();
	$ids     = wc_get_products( array( 'status' => 'publish', 'limit' => 500, 'return' => 'ids', 'visibility' => 'catalog' ) );
	foreach ( $ids as $id ) {
		$p = wc_get_product( $id );
		if ( ! $p ) {
			continue;
		}
		$spec = mmsp_specs( $p );
		if ( $spec['th'] <= 0 || $spec['w'] <= 0 ) {
			continue;
		}
		$algo  = mmsp_algorithm( $p, $spec['th'] );
		$stock = 'outofstock' === $p->get_stock_status() ? 'outofstock' : ( mmsp_is_preorder( $p ) ? 'preorder' : 'instock' );
		$rev   = mmsp_revenue_per_th( $algo, $m );
		$row   = array(
			'id'      => $id,
			'name'    => $p->get_name(),
			'algo'    => $algo,
			'th'      => $spec['th'],
			'w'       => $spec['w'],
			'eff'     => $spec['w'] / $spec['th'],
			'price'   => (float) $p->get_price(),
			'stock'   => $stock,
			'revenue' => null,
			'profit'  => null,
			'payback' => null,
		);
		if ( null !== $rev ) {
			$row['revenue'] = $rev * $spec['th'];
			$row['profit']  = $row['revenue'] - $spec['w'] * 24 / 1000 * $power;
			$row['payback'] = ( $row['profit'] > 0 && $row['price'] > 0 ) ? $row['price'] / $row['profit'] : null;
		}
		$trend = array();
		foreach ( mmsp_algo_coins()[ $algo ] ?? array() as $coin ) {
			if ( isset( $m['coins'][ $coin ]['ch30'] ) ) {
				$trend[] = $m['coins'][ $coin ]['ch30'];
			}
		}
		$row['trend']  = $trend ? array_sum( $trend ) / count( $trend ) : null;
		$row['excl']   = in_array( $id, $exclude, true ) || 'outofstock' === $stock;
		$rows[ $id ]   = $row;
	}

	// Payback ranking among miners whose profit could be measured (fastest = 100).
	$paybacks = array_values( array_filter( array_map( static fn( $r ) => $r['excl'] ? null : $r['payback'], $rows ) ) );
	sort( $paybacks );

	// Efficiency against the same algorithm in the store (best = 100, worst = 0).
	$by_algo = array();
	foreach ( $rows as $r ) {
		$by_algo[ $r['algo'] ][] = $r['eff'];
	}
	foreach ( $rows as $id => $r ) {
		$best = min( $by_algo[ $r['algo'] ] );
		$worst = max( $by_algo[ $r['algo'] ] );
		$eff_s = $worst > $best ? 100 * ( $worst - $r['eff'] ) / ( $worst - $best ) : 70;

		if ( null !== $r['payback'] ) {
			// Half how it compares with the other miners, half absolute (250 days = 100, 1,500+ = 0).
			$abs   = max( 0, min( 100, ( 1500 - $r['payback'] ) / 1250 * 100 ) );
			$pos   = array_search( $r['payback'], $paybacks, true );
			$rel   = count( $paybacks ) > 1 && false !== $pos ? 100 * ( 1 - $pos / ( count( $paybacks ) - 1 ) ) : 70;
			$value = 0.5 * $rel + 0.5 * $abs;
		} elseif ( null !== $r['profit'] ) {
			// Profitable but no price to work out payback, or losing money.
			$value = $r['profit'] > 0 ? 40 : 0;
		} else {
			// No live profit data for this algorithm: neutral, so it never beats measured miners on value alone.
			$value = 45;
		}
		$trend_s = null === $r['trend'] ? 50 : max( 0, min( 100, ( $r['trend'] + 30 ) / 60 * 100 ) );
		$avail_s = 'instock' === $r['stock'] ? 100 : 60;
		$score   = round( 0.5 * $value + 0.2 * $eff_s + 0.2 * $trend_s + 0.1 * $avail_s, 1 );

		$rows[ $id ]['score']  = $score;
		$rows[ $id ]['reason'] = mmsp_reason( $rows[ $id ], $power, $eff_s );
	}

	uasort( $rows, static fn( $a, $b ) => $b['score'] <=> $a['score'] );
	$picks = array();
	foreach ( $rows as $id => $r ) {
		if ( ! $r['excl'] && count( $picks ) < max( 1, (int) mmsp_opt( 'best_count' ) ) ) {
			$picks[] = $id;
		}
	}
	$saved = array(
		'at'     => time(),
		'picks'  => $picks,
		'rows'   => $rows,
		'market' => array( 'btc_price' => $m['btc_price'], 'btc_hash' => $m['btc_hash'], 'power' => $power ),
	);
	update_option( 'mmsp_best', $saved, false );
	delete_transient( 'mmsp_best_lock' );
	return $saved;
}
add_action( 'mmsp_refresh_best', static fn() => mmsp_refresh_best() );

/**
 * Saved picks for visitors. Never makes them wait for market data: when the
 * picks are due, a background refresh is queued and the saved ones are shown.
 */
function mmsp_best_cached(): array {
	$saved = get_option( 'mmsp_best', array() );
	$saved = is_array( $saved ) ? $saved : array();
	$ttl   = max( 1, (int) mmsp_opt( 'best_hours' ) ) * HOUR_IN_SECONDS;
	if ( ( empty( $saved['at'] ) || time() - (int) $saved['at'] >= $ttl ) && ! wp_next_scheduled( 'mmsp_refresh_best_now' ) ) {
		wp_schedule_single_event( time(), 'mmsp_refresh_best_now' );
	}
	return $saved;
}
add_action( 'mmsp_refresh_best_now', static fn() => mmsp_refresh_best() );

// Stock or price changes re-rank at the next look.
add_action(
	'woocommerce_product_set_stock_status',
	static function () {
		$saved = get_option( 'mmsp_best', array() );
		if ( is_array( $saved ) && ! empty( $saved['at'] ) ) {
			$saved['at'] = 0;
			update_option( 'mmsp_best', $saved, false );
		}
	}
);

/**
 * One-line reason shown under each pick.
 */
function mmsp_reason( array $r, float $power, float $eff_s ): string {
	$parts = array();
	if ( null !== $r['payback'] ) {
		/* translators: 1: days 2: power price */
		$parts[] = sprintf( __( 'Pays back in about %1$s days at $%2$s/kWh', 'mine-mirth-shop-pro' ), number_format_i18n( round( $r['payback'] ) ), rtrim( rtrim( number_format( $power, 3 ), '0' ), '.' ) );
	} elseif ( null !== $r['profit'] && $r['profit'] > 0 ) {
		/* translators: %s: money per day */
		$parts[] = sprintf( __( 'Earns about %s a day after power', 'mine-mirth-shop-pro' ), wp_strip_all_tags( wc_price( $r['profit'] ) ) );
	} elseif ( $eff_s >= 80 ) {
		/* translators: %s: algorithm */
		$parts[] = sprintf( __( 'Most efficient %s miner we sell', 'mine-mirth-shop-pro' ), mmsp_algo_label( $r['algo'] ) );
	}
	// Efficiency in the unit miners quote for each algorithm.
	$units   = array( 'scrypt' => array( 'J/MH', 1e6 ), 'blake3' => array( 'J/GH', 1e3 ), 'etchash' => array( 'J/MH', 1e6 ), 'equihash' => array( 'J/KSol', 1e9 ), 'x11' => array( 'J/GH', 1e3 ) );
	[ $unit, $div ] = $units[ $r['algo'] ] ?? array( 'J/TH', 1 );
	$eff     = $r['eff'] / $div;
	$parts[] = sprintf( '%s %s', number_format_i18n( $eff, $eff < 10 ? 2 : 1 ), $unit );
	if ( null !== $r['trend'] && abs( $r['trend'] ) >= 3 ) {
		$coin    = array( 'sha256' => 'BTC', 'scrypt' => 'LTC/DOGE', 'kheavyhash' => 'KAS', 'blake3' => 'ALPH', 'etchash' => 'ETC', 'equihash' => 'ZEC', 'x11' => 'DASH', 'blake2s' => 'KDA', 'eaglesong' => 'CKB' )[ $r['algo'] ] ?? '';
		/* translators: 1: coin 2: percent */
		$parts[] = sprintf( __( '%1$s %2$s this month', 'mine-mirth-shop-pro' ), $coin, ( $r['trend'] > 0 ? '+' : '−' ) . number_format_i18n( abs( $r['trend'] ), 0 ) . '%' );
	}
	return implode( ' · ', $parts );
}

/* ---------------------------------------------------------------------------
 * Display
 * ------------------------------------------------------------------------- */

/**
 * Picks for the current page: the store-wide picks on the shop page, or the
 * best-scoring miners within the category being viewed.
 *
 * @return int[]
 */
function mmsp_current_picks( int $count ): array {
	$saved = mmsp_best_cached();
	if ( empty( $saved['rows'] ) ) {
		return array();
	}
	$term = is_product_taxonomy() ? get_queried_object() : null;
	$out  = array();
	foreach ( $saved['rows'] as $id => $r ) {
		if ( $r['excl'] ) {
			continue;
		}
		if ( $term instanceof WP_Term && ! has_term( $term->term_id, $term->taxonomy, $id ) ) {
			continue;
		}
		$out[] = (int) $id;
		if ( count( $out ) >= $count ) {
			break;
		}
	}
	return $out;
}

/**
 * The "Best choices right now" strip.
 */
function mmsp_best_html( int $count = 0, string $title = '' ): string {
	if ( ! mmsp_opt( 'best_enabled' ) ) {
		return '';
	}
	$count = $count > 0 ? $count : (int) mmsp_opt( 'best_count' );
	$ids   = mmsp_current_picks( $count );
	if ( count( $ids ) < 2 ) {
		return '';
	}
	$saved = get_option( 'mmsp_best', array() );
	$title = $title ? $title : __( 'Best choices right now', 'mine-mirth-shop-pro' );
	$q     = new WP_Query( array( 'post_type' => 'product', 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => count( $ids ), 'ignore_sticky_posts' => true, 'no_found_rows' => true ) );
	if ( ! $q->have_posts() ) {
		return '';
	}
	$loop_before               = $GLOBALS['woocommerce_loop'] ?? null;
	$GLOBALS['mmsp_rank']      = 0;
	$GLOBALS['mmsp_in_strip']  = true;
	wc_set_loop_prop( 'name', 'mmsp_best' );
	wc_set_loop_prop( 'columns', min( 4, count( $ids ) ) );
	wc_set_loop_prop( 'is_paginated', false );
	ob_start();
	?>
	<section class="mmsp-best" aria-labelledby="mmsp-best-title">
		<header class="mmsp-best__head">
			<div>
				<span class="mmsp-best__eyebrow"><span class="mmsp-live" aria-hidden="true"></span><?php esc_html_e( 'Picked automatically from live market data', 'mine-mirth-shop-pro' ); ?></span>
				<h2 id="mmsp-best-title"><?php echo esc_html( $title ); ?></h2>
			</div>
			<p class="mmsp-best__meta">
				<?php
				$bits = array();
				if ( ! empty( $saved['at'] ) ) {
					/* translators: %s: time ago */
					$bits[] = sprintf( __( 'Updated %s ago', 'mine-mirth-shop-pro' ), human_time_diff( (int) $saved['at'] ) );
				}
				if ( ! empty( $saved['market']['btc_price'] ) ) {
					$bits[] = 'BTC ' . wp_strip_all_tags( wc_price( (float) $saved['market']['btc_price'], array( 'decimals' => 0 ) ) );
				}
				if ( ! empty( $saved['market']['btc_hash'] ) ) {
					$bits[] = number_format_i18n( $saved['market']['btc_hash'] / 1e18, 0 ) . ' EH/s';
				}
				echo esc_html( implode( ' · ', $bits ) );
				?>
			</p>
		</header>
		<?php
		woocommerce_product_loop_start();
		while ( $q->have_posts() ) {
			$q->the_post();
			wc_get_template_part( 'content', 'product' );
		}
		woocommerce_product_loop_end();
		?>
		<p class="mmsp-best__note"><?php esc_html_e( 'Ranked by payback time, efficiency, coin price trend and availability. Picks change as the market moves. Estimates, not guarantees.', 'mine-mirth-shop-pro' ); ?></p>
	</section>
	<?php
	wp_reset_postdata();
	$GLOBALS['mmsp_in_strip'] = false;
	if ( null !== $loop_before ) {
		$GLOBALS['woocommerce_loop'] = $loop_before;
	}
	return (string) ob_get_clean();
}

// Strip above the products on page 1 of the shop and category pages, when no filters are set.
add_action(
	'woocommerce_before_shop_loop',
	static function () {
		if ( ! mmsp_opt( 'best_strip' ) || is_paged() || is_search() || mmsp_has_filters() || ! ( is_shop() || is_product_category() ) ) {
			return;
		}
		echo mmsp_best_html(); // phpcs:ignore WordPress.Security.EscapeOutput
	},
	12
);

add_shortcode(
	'minemirth_best_miners',
	static function ( $atts ) {
		$a = shortcode_atts( array( 'count' => 0, 'title' => '' ), $atts );
		wp_enqueue_style( 'mmsp' );
		return mmsp_best_html( (int) $a['count'], sanitize_text_field( $a['title'] ) );
	}
);

// Badge on picked miners, everywhere in the shop; rank and reason inside the strip.
add_action(
	'woocommerce_before_shop_loop_item_title',
	static function () {
		global $product;
		if ( ! $product instanceof WC_Product || ! mmsp_opt( 'best_enabled' ) ) {
			return;
		}
		$saved = get_option( 'mmsp_best', array() );
		$picks = array_map( 'intval', (array) ( $saved['picks'] ?? array() ) );
		$in    = ! empty( $GLOBALS['mmsp_in_strip'] );
		if ( $in ) {
			$GLOBALS['mmsp_rank'] = (int) ( $GLOBALS['mmsp_rank'] ?? 0 ) + 1;
			/* translators: %d: rank */
			echo '<span class="mmsp-badge is-rank">' . esc_html( sprintf( __( '#%d Best choice', 'mine-mirth-shop-pro' ), $GLOBALS['mmsp_rank'] ) ) . '</span>';
		} elseif ( mmsp_opt( 'best_badge' ) && in_array( $product->get_id(), $picks, true ) ) {
			echo '<span class="mmsp-badge">' . esc_html__( 'Best choice', 'mine-mirth-shop-pro' ) . '</span>';
		}
	},
	11
);
add_action(
	'woocommerce_after_shop_loop_item_title',
	static function () {
		global $product;
		if ( empty( $GLOBALS['mmsp_in_strip'] ) || ! $product instanceof WC_Product ) {
			return;
		}
		$saved = get_option( 'mmsp_best', array() );
		$row   = $saved['rows'][ $product->get_id() ] ?? null;
		if ( $row && ! empty( $row['reason'] ) ) {
			echo '<p class="mmsp-reason">' . esc_html( $row['reason'] ) . '</p>';
		}
	},
	20
);
