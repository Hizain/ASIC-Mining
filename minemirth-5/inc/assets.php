<?php
/**
 * Styles, scripts, fonts and the no-flash light/dark bootstrap.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/**
 * Font stack for a choice.
 */
function mm_font_stack( string $family, string $kind = 'sans' ): string {
	$sans = 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
	$mono = 'ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace';
	$fallback = 'mono' === $kind ? $mono : $sans;
	if ( 'system' === $family || mm_opt( 'font_local' ) ) {
		return $fallback;
	}
	return '"' . str_replace( '"', '', $family ) . '", ' . $fallback;
}

/**
 * Google Fonts stylesheet URL for the selected families (or '').
 */
function mm_google_fonts_url(): string {
	if ( mm_opt( 'font_local' ) ) {
		return '';
	}
	$families = array();
	$weights  = array(
		'heading' => '400;500;600;700;800',
		'body'    => '400;500;600;700',
		'mono'    => '400;500;600',
	);
	foreach ( array( 'heading' => 'font_heading', 'body' => 'font_body', 'mono' => 'font_mono' ) as $role => $key ) {
		$family = (string) mm_opt( $key );
		if ( 'system' === $family || '' === $family ) {
			continue;
		}
		$families[ $family ] = isset( $families[ $family ] ) ? $families[ $family ] . ';' . $weights[ $role ] : $weights[ $role ];
	}
	if ( ! $families ) {
		return '';
	}
	$parts = array();
	foreach ( $families as $family => $w ) {
		$w       = implode( ';', array_unique( explode( ';', $w ) ) );
		$list    = array_map( 'intval', explode( ';', $w ) );
		sort( $list );
		$parts[] = 'family=' . str_replace( ' ', '+', $family ) . ':wght@' . implode( ';', $list );
	}
	return 'https://fonts.googleapis.com/css2?' . implode( '&', $parts ) . '&display=swap';
}

/**
 * Head: preconnect + the theme bootstrap (runs before paint, no flash).
 */
function mm_head_bootstrap(): void {
	if ( mm_opt( 'perf_preload_font' ) && mm_google_fonts_url() ) {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	}
	$default = (string) mm_opt( 'mode_default' );
	$config  = array(
		'd' => in_array( $default, array( 'light', 'dark', 'system' ), true ) ? $default : 'system',
		't' => (bool) mm_opt( 'mode_toggle' ),
		'a' => (bool) mm_opt( 'anim_enable' ),
		'n' => mm_opt( 'announce_enable' ) && mm_opt( 'announce_dismissible' ) && ! is_customize_preview() ? mm_announce_id() : '',
	);
	?>
<script id="mm-bootstrap">
(function(d,w,c){var r=d.documentElement,p=c.d;try{if(c.t){p=w.localStorage.getItem('mm-theme')||c.d;}}catch(e){}
var m=p==='system'?(w.matchMedia&&w.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):p;
r.setAttribute('data-theme',m);r.setAttribute('data-theme-pref',p);r.style.colorScheme=m;r.classList.add('mm-js');
try{if(c.n&&w.localStorage.getItem('mm-announce')===c.n){r.classList.add('mm-announce-off');}}catch(e){}
if(c.a&&!(w.matchMedia&&w.matchMedia('(prefers-reduced-motion: reduce)').matches)){r.classList.add('mm-anim');}
})(document,window,<?php echo wp_json_encode( $config ); ?>);
</script>
	<?php
}
add_action( 'wp_head', 'mm_head_bootstrap', 0 );

/**
 * Front-end assets.
 */
function mm_enqueue_assets(): void {
	$ver   = MM_VERSION;
	$defer = array(
		'in_footer' => true,
		'strategy'  => 'defer',
	);

	$fonts = mm_google_fonts_url();
	if ( $fonts ) {
		wp_enqueue_style( 'mm-fonts', $fonts, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}

	wp_enqueue_style( 'mm-main', MM_URI . '/assets/css/main.css', array(), $ver );
	wp_add_inline_style( 'mm-main', mm_dynamic_css() );

	if ( mm_is_woo() ) {
		wp_enqueue_style( 'mm-woo', MM_URI . '/assets/css/woocommerce.css', array( 'mm-main' ), $ver );
	}

	wp_enqueue_script( 'mm-theme', MM_URI . '/assets/js/theme.js', array(), $ver, $defer );
	wp_add_inline_script( 'mm-theme', 'window.MM_CONFIG=' . wp_json_encode( mm_js_config() ) . ';', 'before' );

	wp_register_script( 'mm-market', MM_URI . '/assets/js/market.js', array( 'mm-theme' ), $ver, $defer );
	wp_register_script( 'mm-calculator', MM_URI . '/assets/js/calculator.js', array( 'mm-theme', 'mm-market' ), $ver, $defer );
	wp_register_script( 'mm-hero', MM_URI . '/assets/js/hero.js', array( 'mm-theme' ), $ver, $defer );
	wp_register_script( 'mm-blog', MM_URI . '/assets/js/blog.js', array( 'mm-theme' ), $ver, $defer );
	wp_register_script( 'mm-profitability', MM_URI . '/assets/js/profitability.js', array( 'mm-theme' ), $ver, $defer );
	wp_register_style( 'mm-profitability', MM_URI . '/assets/css/profitability.css', array( 'mm-main' ), $ver );
	// Load the dashboard styles in the head where it is known to appear, so it never flashes unstyled.
	if ( mm_opt( 'prof_enable' ) && ( ( is_front_page() && mm_opt( 'prof_home' ) ) || is_page_template( 'page-templates/profitability.php' ) ) ) {
		wp_enqueue_style( 'mm-profitability' );
	}

	if ( mm_opt( 'market_enable' ) ) {
		wp_enqueue_script( 'mm-market' );
	}

	if ( is_front_page() ) {
		wp_enqueue_script( 'mm-hero' );
		if ( in_array( 'calculator', mm_home_sections_enabled(), true ) ) {
			wp_enqueue_script( 'mm-calculator' );
		}
	}

	if ( is_singular( 'post' ) || is_home() || is_archive() || is_search() ) {
		wp_enqueue_script( 'mm-blog' );
	}

	if ( is_page_template( array( 'page-templates/hosting.php', 'page-templates/calculator.php' ) ) ) {
		wp_enqueue_script( 'mm-calculator' );
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	if ( mm_is_customize_preview_frame() ) {
		wp_enqueue_script( 'mm-customizer-preview', MM_URI . '/assets/js/customizer-preview.js', array( 'customize-preview', 'customize-selective-refresh', 'mm-theme' ), $ver, true );
		wp_add_inline_script( 'mm-customizer-preview', 'window.MM_PREVIEW=' . wp_json_encode( mm_preview_config() ) . ';', 'before' );
	}
}
add_action( 'wp_enqueue_scripts', 'mm_enqueue_assets', 20 );

function mm_is_customize_preview_frame(): bool {
	return is_customize_preview();
}

/**
 * Data handed to theme.js.
 */
function mm_js_config(): array {
	return array(
		'rest'      => esc_url_raw( rest_url() ),
		'restNonce' => wp_create_nonce( 'wp_rest' ),
		'ajax'      => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
		'wcAjax'    => class_exists( 'WC_AJAX' ) ? esc_url_raw( WC_AJAX::get_endpoint( '%%endpoint%%' ) ) : '',
		'home'      => esc_url_raw( home_url( '/' ) ),
		'shop'      => esc_url_raw( mm_shop_url() ),
		'cartUrl'   => function_exists( 'wc_get_cart_url' ) ? esc_url_raw( wc_get_cart_url() ) : '',
		'woo'       => mm_is_woo(),
		'mode'      => array(
			'default'    => mm_opt( 'mode_default' ),
			'toggle'     => (bool) mm_opt( 'mode_toggle' ),
			'transition' => (bool) mm_opt( 'mode_transition' ),
		),
		'fx'        => array(
			'magnetic'  => (bool) mm_opt( 'fx_magnetic' ),
			'tilt'      => (bool) mm_opt( 'fx_tilt' ),
			'spotlight' => (bool) mm_opt( 'fx_spotlight' ),
			'loader'    => (bool) mm_opt( 'fx_page_loader' ),
			'density'   => (int) mm_opt( 'hero_density' ) / 100,
		),
		'header'    => array(
			'autohide' => (bool) mm_opt( 'header_autohide' ),
		),
		'search'    => array(
			'products' => mm_is_woo() && mm_opt( 'search_products' ),
			'posts'    => (bool) mm_opt( 'search_posts' ),
		),
		'cart'      => array(
			'drawer'     => (bool) mm_opt( 'cart_drawer' ),
			'openOnAdd'  => (bool) mm_opt( 'cart_drawer_open' ),
		),
		'promo'     => array(
			'slideinAt' => (int) mm_opt( 'promo_slidein_at' ),
		),
		'market'    => array(
			'enabled'  => (bool) mm_opt( 'market_enable' ),
			'endpoint' => esc_url_raw( rest_url( 'mine-mirth/v1/market' ) ),
			'refresh'  => max( 60, (int) mm_opt( 'market_cache' ) ),
			'currency' => strtoupper( (string) mm_opt( 'market_currency' ) ),
			'locale'   => str_replace( '_', '-', get_locale() ),
		),
		'newsletter'=> array(
			'endpoint' => esc_url_raw( rest_url( 'mine-mirth/v1/subscribe' ) ),
		),
		'i18n'      => array(
			'light'        => __( 'Light', 'minemirth' ),
			'dark'         => __( 'Dark', 'minemirth' ),
			'toLight'      => __( 'Switch to light mode', 'minemirth' ),
			'toDark'       => __( 'Switch to dark mode', 'minemirth' ),
			'searching'    => __( 'Searching…', 'minemirth' ),
			'noResults'    => __( 'No results for that search yet.', 'minemirth' ),
			'products'     => __( 'Products', 'minemirth' ),
			'articles'     => __( 'Articles & pages', 'minemirth' ),
			'viewAll'      => __( 'See all results', 'minemirth' ),
			'added'        => __( 'Added to cart', 'minemirth' ),
			'viewCart'     => __( 'View cart', 'minemirth' ),
			'addToCart'    => __( 'Add to cart', 'minemirth' ),
			'adding'       => __( 'Adding…', 'minemirth' ),
			'chooseOptions'=> __( 'Choose options', 'minemirth' ),
			'viewProduct'  => __( 'View full details', 'minemirth' ),
			'error'        => __( 'Something went wrong. Please try again.', 'minemirth' ),
			'copied'       => __( 'Link copied', 'minemirth' ),
			'unavailable'  => __( 'Unavailable', 'minemirth' ),
			'updated'      => __( 'Updated', 'minemirth' ),
			'outOfStock'   => __( 'Out of stock', 'minemirth' ),
			'inStock'      => __( 'In stock', 'minemirth' ),
			'perDay'       => __( '/day', 'minemirth' ),
			'lastKnown'    => __( 'Last known data', 'minemirth' ),
			'noLive'       => __( 'Live data unavailable, enter values', 'minemirth' ),
			/* translators: %d: number of days */
			'days'         => __( '%d days', 'minemirth' ),
			'tenYears'     => __( '10+ years', 'minemirth' ),
			'notProfitable'=> __( 'Not at current values', 'minemirth' ),
			'continue'     => __( 'Continue reading', 'minemirth' ),
		),
	);
}

/**
 * Dequeue WooCommerce's generic styles: the theme ships a complete design.
 */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Block editor: same fonts and design tokens as the front end, so posts look
 * like the live site while editing (loaded inside the editor iframe).
 */
function mm_block_editor_assets(): void {
	if ( ! is_admin() ) {
		return;
	}
	$fonts = mm_google_fonts_url();
	if ( $fonts ) {
		wp_enqueue_style( 'mm-editor-fonts', $fonts, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}
	wp_register_style( 'mm-editor-tokens', false, array(), MM_VERSION );
	wp_enqueue_style( 'mm-editor-tokens' );
	wp_add_inline_style( 'mm-editor-tokens', mm_dynamic_css() );
}
add_action( 'enqueue_block_assets', 'mm_block_editor_assets' );
