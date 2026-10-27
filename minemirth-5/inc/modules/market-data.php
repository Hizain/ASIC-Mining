<?php
/**
 * Live Bitcoin market & network data.
 *
 * Sources: CoinGecko (price, 24h change, 24h sparkline) and mempool.space
 * (hashrate, difficulty, tip height, difficulty adjustment, fees).
 *
 * Page renders never call external APIs: templates print the cached values
 * and the browser refreshes them through the REST endpoint, which fetches
 * upstream only when the cache is older than the configured interval.
 * When a source fails the last good snapshot (≤ 24h) is served and marked
 * stale; nothing is ever invented.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

const MM_MARKET_CACHE     = 'mm_market_v4_';
const MM_MARKET_LAST_GOOD = 'mm_market_last_v4_';
const MM_HALVING_INTERVAL = 210000;

/**
 * Cache key per currency.
 */
function mm_market_currency(): string {
	$c = strtolower( (string) mm_opt( 'market_currency' ) );
	return in_array( $c, array( 'usd', 'eur', 'gbp', 'cad', 'aud' ), true ) ? $c : 'usd';
}

/**
 * Cached snapshot (never fetches).
 */
function mm_market_cached(): array {
	$cur  = mm_market_currency();
	$data = get_transient( MM_MARKET_CACHE . $cur );
	if ( ! is_array( $data ) ) {
		$data = get_transient( MM_MARKET_LAST_GOOD . $cur );
		if ( is_array( $data ) ) {
			$data['is_stale'] = true;
		}
	}
	return is_array( $data ) ? $data : array();
}

/**
 * GET JSON helper. Returns decoded array/scalar or null.
 */
function mm_market_get_json( string $url ) {
	$response = wp_remote_get(
		$url,
		array(
			'timeout'     => 6,
			'redirection' => 2,
			'headers'     => array(
				'Accept'     => 'application/json',
				'User-Agent' => 'MineMirth/' . MM_VERSION . '; ' . home_url( '/' ),
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		return null;
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code < 200 || $code >= 300 ) {
		return null;
	}
	$body = wp_remote_retrieve_body( $response );
	$json = json_decode( $body, true );
	if ( null === $json && is_numeric( trim( $body ) ) ) {
		return (float) trim( $body );
	}
	return $json;
}

/**
 * Fetch every upstream value. Requests run in parallel when the bundled
 * Requests library is available.
 */
function mm_market_fetch(): array {
	$cur  = mm_market_currency();
	$urls = array(
		'price'  => 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=' . $cur . '&include_24hr_change=true',
		'spark'  => 'https://api.coingecko.com/api/v3/coins/bitcoin/market_chart?vs_currency=' . $cur . '&days=1',
		'net'    => 'https://mempool.space/api/v1/mining/hashrate/3d',
		'height' => 'https://mempool.space/api/blocks/tip/height',
		'adj'    => 'https://mempool.space/api/v1/difficulty-adjustment',
		'reward' => 'https://mempool.space/api/v1/mining/reward-stats/144',
	);

	// Sparkline changes slowly: reuse it for 10 minutes.
	$spark_cache = get_transient( 'mm_market_spark_' . $cur );
	if ( is_array( $spark_cache ) ) {
		unset( $urls['spark'] );
	}

	$raw = array();
	if ( class_exists( '\WpOrg\Requests\Requests' ) ) {
		$requests = array();
		foreach ( $urls as $key => $url ) {
			$requests[ $key ] = array(
				'url'     => $url,
				'headers' => array(
					'Accept'     => 'application/json',
					'User-Agent' => 'MineMirth/' . MM_VERSION,
				),
			);
		}
		try {
			$responses = \WpOrg\Requests\Requests::request_multiple(
				$requests,
				array(
					'timeout'         => 6,
					'connect_timeout' => 4,
					'verify'          => ABSPATH . WPINC . '/certificates/ca-bundle.crt',
				)
			);
			foreach ( $responses as $key => $res ) {
				if ( $res instanceof \WpOrg\Requests\Response && $res->success ) {
					$json        = json_decode( $res->body, true );
					$raw[ $key ] = null === $json && is_numeric( trim( $res->body ) ) ? (float) trim( $res->body ) : $json;
				}
			}
		} catch ( \Throwable $e ) {
			$raw = array();
		}
	}
	foreach ( $urls as $key => $url ) {
		if ( ! array_key_exists( $key, $raw ) || null === $raw[ $key ] ) {
			$raw[ $key ] = mm_market_get_json( $url );
		}
	}

	$price  = isset( $raw['price']['bitcoin'][ $cur ] ) && is_numeric( $raw['price']['bitcoin'][ $cur ] ) ? (float) $raw['price']['bitcoin'][ $cur ] : null;
	$change = isset( $raw['price']['bitcoin'][ $cur . '_24h_change' ] ) && is_numeric( $raw['price']['bitcoin'][ $cur . '_24h_change' ] ) ? (float) $raw['price']['bitcoin'][ $cur . '_24h_change' ] : null;
	$hash   = isset( $raw['net']['currentHashrate'] ) && is_numeric( $raw['net']['currentHashrate'] ) ? (float) $raw['net']['currentHashrate'] : null;
	$diff   = isset( $raw['net']['currentDifficulty'] ) && is_numeric( $raw['net']['currentDifficulty'] ) ? (float) $raw['net']['currentDifficulty'] : null;
	$height = isset( $raw['height'] ) && is_numeric( $raw['height'] ) ? (int) $raw['height'] : null;

	$adjust = null;
	if ( is_array( $raw['adj'] ?? null ) && isset( $raw['adj']['remainingBlocks'] ) ) {
		$adjust = array(
			'progress'         => (float) ( $raw['adj']['progressPercent'] ?? 0 ),
			'change'           => (float) ( $raw['adj']['difficultyChange'] ?? 0 ),
			'remaining_blocks' => (int) $raw['adj']['remainingBlocks'],
			'eta'              => isset( $raw['adj']['estimatedRetargetDate'] ) ? (int) round( (float) $raw['adj']['estimatedRetargetDate'] / 1000 ) : null,
		);
	}

	$fees = null;
	if ( is_array( $raw['reward'] ?? null ) && isset( $raw['reward']['totalFee'], $raw['reward']['startBlock'], $raw['reward']['endBlock'] ) ) {
		$blocks = max( 1, (int) $raw['reward']['endBlock'] - (int) $raw['reward']['startBlock'] + 1 );
		$fees   = ( (float) $raw['reward']['totalFee'] / 1e8 ) / $blocks;
	}

	$spark = is_array( $spark_cache ) ? $spark_cache : array();
	if ( ! $spark && is_array( $raw['spark']['prices'] ?? null ) ) {
		$series = array_values( array_filter( array_map( static fn( $p ) => is_array( $p ) && isset( $p[1] ) ? (float) $p[1] : null, $raw['spark']['prices'] ) ) );
		$n      = count( $series );
		if ( $n > 2 ) {
			$step = max( 1, (int) floor( $n / 32 ) );
			for ( $i = 0; $i < $n; $i += $step ) {
				$spark[] = round( $series[ $i ], 2 );
			}
			$spark[] = round( $series[ $n - 1 ], 2 );
			set_transient( 'mm_market_spark_' . $cur, $spark, 10 * MINUTE_IN_SECONDS );
		}
	}

	$halving = null;
	if ( null !== $height ) {
		$next    = (int) ( ceil( ( $height + 1 ) / MM_HALVING_INTERVAL ) * MM_HALVING_INTERVAL );
		$halving = array(
			'next_height' => $next,
			'blocks_left' => $next - $height,
			'eta'         => time() + ( $next - $height ) * 600,
			'subsidy'     => 50 / pow( 2, (int) floor( $height / MM_HALVING_INTERVAL ) ),
		);
	}

	return array(
		'status'      => ( null !== $price && null !== $hash && null !== $diff ) ? 'live' : 'partial',
		'currency'    => $cur,
		'price'       => $price,
		'change'      => $change,
		'hashrate'    => $hash,
		'difficulty'  => $diff,
		'height'      => $height,
		'fees'        => $fees,
		'adjustment'  => $adjust,
		'halving'     => $halving,
		'spark'       => $spark,
		'updated_at'  => time(),
		'is_stale'    => false,
		'sources'     => array(
			array( 'name' => 'CoinGecko', 'url' => 'https://www.coingecko.com/en/coins/bitcoin' ),
			array( 'name' => 'mempool.space', 'url' => 'https://mempool.space/' ),
		),
	);
}

/**
 * Snapshot for the REST endpoint: refresh when older than the interval.
 */
function mm_market_refresh(): array {
	$cur      = mm_market_currency();
	$ttl      = max( 60, (int) mm_opt( 'market_cache' ) );
	$cached   = get_transient( MM_MARKET_CACHE . $cur );
	if ( is_array( $cached ) && ( time() - (int) ( $cached['updated_at'] ?? 0 ) ) < $ttl ) {
		return $cached;
	}
	// Simple lock so concurrent visitors don't stampede the upstream APIs.
	if ( get_transient( 'mm_market_lock_' . $cur ) ) {
		return mm_market_cached();
	}
	set_transient( 'mm_market_lock_' . $cur, 1, 20 );

	$fresh = mm_market_fetch();
	$last  = get_transient( MM_MARKET_LAST_GOOD . $cur );

	// Fill gaps from the last good snapshot, flagged stale.
	if ( is_array( $last ) ) {
		foreach ( array( 'price', 'change', 'hashrate', 'difficulty', 'height', 'fees', 'adjustment', 'halving' ) as $key ) {
			if ( null === $fresh[ $key ] && isset( $last[ $key ] ) ) {
				$fresh[ $key ]    = $last[ $key ];
				$fresh['is_stale'] = true;
			}
		}
		if ( ! $fresh['spark'] && ! empty( $last['spark'] ) ) {
			$fresh['spark'] = $last['spark'];
		}
	}

	if ( null === $fresh['price'] && null === $fresh['hashrate'] ) {
		$fresh['status'] = 'unavailable';
	}

	set_transient( MM_MARKET_CACHE . $cur, $fresh, DAY_IN_SECONDS );
	if ( 'live' === $fresh['status'] && ! $fresh['is_stale'] ) {
		set_transient( MM_MARKET_LAST_GOOD . $cur, $fresh, DAY_IN_SECONDS );
		/**
		 * Fires after a fresh, complete market snapshot is stored (history recording hooks in here).
		 */
		do_action( 'mm_market_refreshed', $fresh );
	}
	delete_transient( 'mm_market_lock_' . $cur );
	return $fresh;
}

/* ---------------------------------------------------------------------------
 * Formatting
 * ------------------------------------------------------------------------- */

function mm_currency_symbol( string $cur ): string {
	return array(
		'usd' => '$',
		'eur' => '€',
		'gbp' => '£',
		'cad' => 'CA$',
		'aud' => 'A$',
	)[ $cur ] ?? '$';
}

function mm_format_hashrate( $hs ): string {
	if ( ! is_numeric( $hs ) || $hs <= 0 ) {
		return '—';
	}
	$hs = (float) $hs;
	if ( $hs >= 1e21 ) {
		return number_format_i18n( $hs / 1e21, 2 ) . ' ZH/s';
	}
	return number_format_i18n( $hs / 1e18, 1 ) . ' EH/s';
}

function mm_format_difficulty( $d ): string {
	if ( ! is_numeric( $d ) || $d <= 0 ) {
		return '—';
	}
	return number_format_i18n( (float) $d / 1e12, 2 ) . ' T';
}

/**
 * SVG path (viewBox 120×32) for a sparkline series.
 */
function mm_spark_path( array $series ): string {
	$n = count( $series );
	if ( $n < 2 ) {
		return 'M0 16 L120 16';
	}
	$min  = min( $series );
	$max  = max( $series );
	$span = $max - $min ? $max - $min : 1;
	$path = '';
	foreach ( array_values( $series ) as $i => $v ) {
		$x     = round( $i / ( $n - 1 ) * 120, 2 );
		$y     = round( 30 - ( ( $v - $min ) / $span ) * 28, 2 );
		$path .= ( $i ? ' L' : 'M' ) . $x . ' ' . $y;
	}
	return $path;
}

/**
 * Human-readable display strings for every metric.
 *
 * @param array|null $data Snapshot (defaults to cached).
 */
function mm_market_display( ?array $data = null ): array {
	$d   = null === $data ? mm_market_cached() : $data;
	$cur = (string) ( $d['currency'] ?? mm_market_currency() );
	$sym = mm_currency_symbol( $cur );

	$price  = isset( $d['price'] ) && is_numeric( $d['price'] ) ? $sym . number_format_i18n( (float) $d['price'], 0 ) : '—';
	$change = isset( $d['change'] ) && is_numeric( $d['change'] ) ? ( $d['change'] > 0 ? '+' : '' ) . number_format_i18n( (float) $d['change'], 2 ) . '%' : '';
	$class  = ! isset( $d['change'] ) || ! is_numeric( $d['change'] ) ? 'is-flat' : ( $d['change'] >= 0 ? 'is-up' : 'is-down' );

	$adjust = '—';
	if ( ! empty( $d['adjustment'] ) ) {
		$a      = $d['adjustment'];
		$adjust = sprintf(
			/* translators: 1: percent change, 2: number of blocks */
			__( '%1$s in %2$s blocks', 'minemirth' ),
			( $a['change'] > 0 ? '+' : '' ) . number_format_i18n( (float) $a['change'], 2 ) . '%',
			number_format_i18n( (int) $a['remaining_blocks'] )
		);
	}

	$updated = '';
	if ( ! empty( $d['updated_at'] ) ) {
		$updated = sprintf(
			/* translators: %s: time */
			__( 'Updated %s', 'minemirth' ),
			wp_date( get_option( 'time_format' ), (int) $d['updated_at'] )
		) . ( ! empty( $d['is_stale'] ) ? ' · ' . __( 'last known', 'minemirth' ) : '' );
	}

	return array(
		'price'        => $price,
		'change'       => $change,
		'change_class' => $class,
		'hashrate'     => mm_format_hashrate( $d['hashrate'] ?? null ),
		'difficulty'   => mm_format_difficulty( $d['difficulty'] ?? null ),
		'height'       => isset( $d['height'] ) && $d['height'] ? number_format_i18n( (int) $d['height'] ) : '—',
		'fees'         => isset( $d['fees'] ) && is_numeric( $d['fees'] ) ? number_format_i18n( (float) $d['fees'], 3 ) . ' BTC' : '—',
		'adjustment'   => $adjust,
		'halving'      => ! empty( $d['halving'] ) ? number_format_i18n( (int) $d['halving']['blocks_left'] ) : '—',
		'updated'      => $updated,
		'spark_path'   => mm_spark_path( (array) ( $d['spark'] ?? array() ) ),
		'spark_down'   => is_numeric( $d['change'] ?? null ) && $d['change'] < 0,
		'hashprice'    => function_exists( 'mm_btc_economics' ) && ( $e = mm_btc_economics( $d ) ) && $e['hashprice_ph'] ? $sym . number_format_i18n( (float) $e['hashprice_ph'], 2 ) : '—', // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
	);
}

/* ---------------------------------------------------------------------------
 * REST endpoint
 * ------------------------------------------------------------------------- */
function mm_market_register_routes(): void {
	register_rest_route(
		'mine-mirth/v1',
		'/market',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => '__return_true',
			'callback'            => static function () {
				if ( ! mm_opt( 'market_enable' ) ) {
					return new WP_REST_Response( array( 'status' => 'disabled' ), 200 );
				}
				$data            = mm_market_refresh();
				$data['display'] = mm_market_display( $data );
				$response        = new WP_REST_Response( $data, 200 );
				$response->header( 'Cache-Control', 'public, max-age=30' );
				return $response;
			},
		)
	);
}
add_action( 'rest_api_init', 'mm_market_register_routes' );

/* ---------------------------------------------------------------------------
 * Shortcode: [mm_market] (alias [mine_mirth_market_snapshot])
 * ------------------------------------------------------------------------- */
function mm_market_shortcode(): string {
	if ( ! mm_opt( 'market_enable' ) ) {
		return '';
	}
	wp_enqueue_script( 'mm-market' );
	$m     = mm_market_display();
	$cards = array(
		array( 'price', __( 'Bitcoin price', 'minemirth' ), 'bitcoin' ),
		array( 'hashrate', __( 'Network hashrate', 'minemirth' ), 'activity' ),
		array( 'difficulty', __( 'Difficulty', 'minemirth' ), 'gauge' ),
		array( 'height', __( 'Block height', 'minemirth' ), 'cube' ),
		array( 'adjustment', __( 'Next adjustment', 'minemirth' ), 'refresh' ),
		array( 'halving', __( 'Blocks to halving', 'minemirth' ), 'calendar' ),
	);
	ob_start();
	?>
	<div class="mm-market" data-mm-market>
		<div class="mm-market__grid">
			<?php foreach ( $cards as $card ) : ?>
				<div class="mm-market__card mm-card">
					<span class="mm-market__label"><?php echo mm_icon( $card[2] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $card[1] ); ?></span>
					<strong class="mm-market__value" data-mm-m="<?php echo esc_attr( $card[0] ); ?>"><?php echo esc_html( $m[ $card[0] ] ); ?></strong>
					<?php if ( 'price' === $card[0] ) : ?>
						<span class="mm-change <?php echo esc_attr( $m['change_class'] ); ?>" data-mm-m="change"><?php echo esc_html( $m['change'] ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( mm_opt( 'market_sources' ) ) : ?>
			<p class="mm-market__foot">
				<span data-mm-m="updated"><?php echo esc_html( $m['updated'] ); ?></span>
				<span><?php esc_html_e( 'Sources:', 'minemirth' ); ?> <a href="https://www.coingecko.com/en/coins/bitcoin" target="_blank" rel="noopener nofollow">CoinGecko</a> · <a href="https://mempool.space/" target="_blank" rel="noopener nofollow">mempool.space</a></span>
			</p>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'mm_market', 'mm_market_shortcode' );
add_shortcode( 'mine_mirth_market_snapshot', 'mm_market_shortcode' );
