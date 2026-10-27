<?php
/**
 * Motion layer: crypto vector shapes behind sections and the live guide that talks to visitors.
 *
 * Shapes are plain markup that CSS draws and animates (transform and opacity only), and
 * assets/js/motion.js plays them only while they are on screen. The guide's messages are written
 * here, so they can be translated, with {tokens} that motion.js fills from the live market data.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/** Shape layers and how many shapes each one draws. */
function mm_deco_kinds(): array {
	return array(
		'coins'   => 6,
		'blocks'  => 6,
		'chain'   => 6,
		'circuit' => 2,
		'nodes'   => 5,
	);
}

/**
 * A decorative shape layer. Kinds: coins, blocks, chain, circuit, nodes.
 */
function mm_deco( string $kind, string $class = '' ): string {
	$kinds = mm_deco_kinds();
	if ( ! isset( $kinds[ $kind ] ) || ! mm_opt( 'fx_shapes' ) ) {
		return '';
	}
	return '<div class="mm-deco mm-deco--' . $kind . ( $class ? ' ' . esc_attr( $class ) : '' ) . '" aria-hidden="true">' . str_repeat( '<i></i>', $kinds[ $kind ] ) . '</div>';
}

/** The shape layer a section shows: its own choice, or one that suits its layout. */
function mm_deco_for_section( string $variant, array $d ): string {
	$pick = (string) ( $d['deco'] ?? 'auto' );
	if ( 'none' === $pick ) {
		return '';
	}
	if ( 'auto' === $pick || '' === $pick ) {
		$auto = array(
			'hero'       => 'nodes',
			'stats'      => 'chain',
			'market'     => 'chain',
			'features'   => 'blocks',
			'steps'      => 'circuit',
			'tools'      => 'circuit',
			'split'      => 'coins',
			'links'      => 'blocks',
			'newsletter' => 'coins',
			'cta'        => 'coins',
			'brands'     => 'nodes',
			'faq'        => 'circuit',
			'pricing'    => 'chain',
		);
		$pick = $auto[ $variant ] ?? '';
	}
	return '' !== $pick ? mm_deco( $pick ) : '';
}

/* ---------------------------------------------------------------------------
 * Live guide
 * ------------------------------------------------------------------------- */

function mm_guide_enabled(): bool {
	if ( ! mm_opt( 'fx_guide' ) || ! mm_opt( 'market_enable' ) || is_customize_preview() || is_admin() ) {
		return false;
	}
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		return false;
	}
	return ! is_404() && ! is_page_template( 'page-templates/tool.php' );
}

/** Catalog row of the product being viewed, if it has mining specs. */
function mm_guide_product(): ?array {
	if ( ! function_exists( 'is_product' ) || ! is_product() || ! function_exists( 'mm_catalog' ) ) {
		return null;
	}
	$id = (int) get_queried_object_id();
	foreach ( mm_catalog() as $row ) {
		if ( (int) $row['id'] === $id ) {
			return $row;
		}
	}
	return null;
}

/**
 * Messages for this page. Tokens: {price}, {change}, {hashprice}, {adj}, {adj_days}, {halving_days},
 * {height}, {earn}, {breakeven} and {name} (the miner on a product page).
 *
 * @return array<int,array{t:string,a?:string,u?:string}>
 */
function mm_guide_messages(): array {
	$tool  = static fn( string $id ) => function_exists( 'mm_tool_url' ) ? mm_tool_url( $id ) : home_url( '/' );
	$page  = static fn( string $key ) => function_exists( 'mm_page_url_by_key' ) ? mm_page_url_by_key( $key ) : home_url( '/' );
	$key   = is_page() ? (string) get_post_meta( (int) get_queried_object_id(), '_mm_page_key', true ) : '';
	$intro = array(
		't' => __( 'Bitcoin is {price} ({change} today). One petahash of mining power earns {hashprice} a day right now.', 'minemirth' ),
		'a' => __( 'What would my miner earn?', 'minemirth' ),
		'u' => $tool( 'profit' ),
	);
	$adj   = array(
		't' => __( 'The next difficulty change is {adj} in about {adj_days}. Efficient miners feel it the least.', 'minemirth' ),
		'a' => __( 'See the most efficient miners', 'minemirth' ),
		'u' => $page( 'm-efficient' ),
	);
	$halv  = array(
		't' => __( '{halving_days} days until the next halving cuts the block reward in half. Plan your payback before it arrives.', 'minemirth' ),
		'a' => __( 'Halving impact tool', 'minemirth' ),
		'u' => $tool( 'halving' ),
	);

	$row = mm_guide_product();
	if ( $row && $row['th'] > 0 && 'sha256' === $row['algo'] ) {
		$calc = add_query_arg( array( 'tool' => 'profit', 'th' => $row['th'], 'watts' => $row['watts'], 'cost' => $row['price'] ), $tool( 'profit' ) );
		return array(
			array(
				't' => __( 'At today’s difficulty this {name} earns about {earn} a day before electricity.', 'minemirth' ),
				'a' => __( 'Run it with my power price', 'minemirth' ),
				'u' => $calc,
			),
			array(
				't' => __( 'It stays profitable on power up to about {breakeven} per kWh. Above that, hosting is usually cheaper.', 'minemirth' ),
				'a' => __( 'Compare hosting with home', 'minemirth' ),
				'u' => $tool( 'hosting' ),
			),
			$adj,
		);
	}
	if ( $row || ( function_exists( 'is_product' ) && is_product() ) ) {
		return array(
			array(
				't' => __( 'Want the payback on this miner at your power price? Send us your rate and we will model it for you.', 'minemirth' ),
				'a' => __( 'Ask a specialist', 'minemirth' ),
				'u' => $page( 'contact' ),
			),
			$intro,
		);
	}
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		return array(
			array(
				't' => __( 'Hashprice is {hashprice} per PH/s per day. Tap the calculator icon on any miner to see its profit at today’s numbers.', 'minemirth' ),
			),
			$adj,
			$halv,
		);
	}
	if ( 'hosting' === $key || 0 === strpos( $key, 'host' ) || is_page_template( 'page-templates/hosting.php' ) ) {
		return array(
			array(
				't' => __( 'Paying more than about $0.10 per kWh at home? Compare your cost with hosting in under a minute.', 'minemirth' ),
				'a' => __( 'Hosting vs home calculator', 'minemirth' ),
				'u' => $tool( 'hosting' ),
			),
			$intro,
		);
	}
	if ( is_singular( 'post' ) || is_home() || is_archive() || is_search() || is_page_template( 'page-templates/guide.php' ) ) {
		return array(
			array(
				't' => __( 'While you read: hashprice is {hashprice} and difficulty changes {adj} in about {adj_days}.', 'minemirth' ),
				'a' => __( 'Live hashprice tracker', 'minemirth' ),
				'u' => $tool( 'hashprice' ),
			),
			$halv,
		);
	}
	return array( $intro, $adj, $halv );
}

function mm_guide_config(): array {
	$row = mm_guide_product();
	$m   = function_exists( 'mm_market_cached' ) ? mm_market_cached() : array();
	if ( $m ) {
		$m['display'] = mm_market_display( $m );
		unset( $m['spark'], $m['display']['spark_path'] );
	}
	return array(
		'market' => $m ? $m : null,
		'delay'  => max( 2, (int) mm_opt( 'fx_guide_delay' ) ) * 1000,
		'msgs'   => mm_guide_messages(),
		'sym'    => function_exists( 'mm_currency_symbol' ) && function_exists( 'mm_market_currency' ) ? mm_currency_symbol( mm_market_currency() ) : '$',
		'miner'  => $row && $row['th'] > 0 ? array( 'name' => $row['name'], 'th' => (float) $row['th'], 'w' => (float) $row['watts'] ) : null,
		'i18n'   => array(
			'name'    => __( 'Mirth live guide', 'minemirth' ),
			'close'   => __( 'Close the guide', 'minemirth' ),
			'open'    => __( 'Open the live guide', 'minemirth' ),
			'next'    => __( 'Next tip', 'minemirth' ),
			'days'    => __( '%s days', 'minemirth' ),
			'day'     => __( '1 day', 'minemirth' ),
			'hours'   => __( 'a few hours', 'minemirth' ),
			'typing'  => __( 'typing', 'minemirth' ),
		),
	);
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! mm_opt( 'anim_enable' ) && ! mm_opt( 'fx_guide' ) ) {
			return;
		}
		wp_enqueue_script( 'mm-motion', MM_URI . '/assets/js/motion.js', array( 'mm-theme' ), MM_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		if ( mm_guide_enabled() ) {
			wp_enqueue_script( 'mm-market' );
			wp_add_inline_script( 'mm-motion', 'window.MM_GUIDE=' . wp_json_encode( mm_guide_config() ) . ';', 'before' );
		}
	},
	25
);
