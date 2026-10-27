<?php
/**
 * Theme setup: supports, menus, widget areas, image sizes, body classes.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

function mm_setup(): void {
	load_theme_textdomain( 'minemirth', MM_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 120,
			'width'       => 400,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_theme_support( 'woocommerce', array( 'product_grid' => array( 'default_columns' => 3 ) ) );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	add_editor_style( array( 'assets/css/main.css', 'assets/css/editor.css' ) );

	register_nav_menus(
		array(
			'primary'       => __( 'Primary menu', 'minemirth' ),
			'mobile'        => __( 'Mobile menu (optional, falls back to Primary)', 'minemirth' ),
			'footer-1'      => __( 'Footer column 1', 'minemirth' ),
			'footer-2'      => __( 'Footer column 2', 'minemirth' ),
			'footer-3'      => __( 'Footer column 3', 'minemirth' ),
			'footer-bottom' => __( 'Footer bottom links', 'minemirth' ),
		)
	);

	add_image_size( 'mm-card', 800, 520, true );
	add_image_size( 'mm-wide', 1600, 840, true );
	add_image_size( 'mm-square', 600, 600, true );
}
add_action( 'after_setup_theme', 'mm_setup' );

function mm_content_width(): void {
	$GLOBALS['content_width'] = apply_filters( 'mm_content_width', 760 );
}
add_action( 'after_setup_theme', 'mm_content_width', 0 );

function mm_widgets_init(): void {
	$common = array(
		'before_widget' => '<section id="%1$s" class="mm-widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="mm-widget__title">',
		'after_title'   => '</h2>',
	);
	register_sidebar(
		array_merge(
			$common,
			array(
				'name'        => __( 'Blog sidebar', 'minemirth' ),
				'id'          => 'sidebar-blog',
				'description' => __( 'Shown on blog archives (when enabled) and under the sticky promo on articles.', 'minemirth' ),
			)
		)
	);
	register_sidebar(
		array_merge(
			$common,
			array(
				'name'        => __( 'Shop sidebar', 'minemirth' ),
				'id'          => 'sidebar-shop',
				'description' => __( 'Product filters for shop and category pages.', 'minemirth' ),
			)
		)
	);
	register_sidebar(
		array_merge(
			$common,
			array(
				'name'        => __( 'Page sidebar', 'minemirth' ),
				'id'          => 'sidebar-page',
				'description' => __( 'Used by the "Page with sidebar" template.', 'minemirth' ),
			)
		)
	);
}
add_action( 'widgets_init', 'mm_widgets_init' );

/**
 * Body classes driven by options.
 */
function mm_body_classes( array $classes ): array {
	$classes[] = 'mm-theme';

	foreach ( mm_options_schema() as $key => $field ) {
		if ( ! empty( $field['body_class'] ) ) {
			$classes[] = sanitize_html_class( sprintf( $field['body_class'], (string) mm_opt( $key ) ) );
		}
	}

	$classes[] = 'mm-header-' . sanitize_html_class( (string) mm_opt( 'header_layout' ) );
	if ( mm_opt( 'header_sticky' ) ) {
		$classes[] = 'mm-header-sticky';
	}
	if ( mm_opt( 'header_transparent' ) && is_front_page() && in_array( 'hero', mm_home_sections_enabled(), true ) && 'content' !== mm_opt( 'home_mode' ) ) {
		$classes[] = 'mm-header-overlay';
	}
	if ( 'auto' !== mm_opt( 'footer_style' ) ) {
		$classes[] = 'mm-footer-dark';
	}
	if ( mm_is_checkout_focus() ) {
		$classes[] = 'mm-checkout-focus';
	}
	if ( mm_opt( 'fx_spotlight' ) ) {
		$classes[] = 'mm-fx-spotlight';
	}
	if ( is_singular() && has_post_thumbnail() ) {
		$classes[] = 'mm-has-thumb';
	}
	if ( mm_is_woo() ) {
		$classes[] = 'mm-woo';
	}
	return $classes;
}
add_filter( 'body_class', 'mm_body_classes' );

/**
 * Should the distraction-free checkout chrome be used?
 */
function mm_is_checkout_focus(): bool {
	if ( ! function_exists( 'is_checkout' ) || ! mm_opt( 'checkout_focus' ) ) {
		return false;
	}
	return is_checkout() && ! is_order_received_page() && ! is_checkout_pay_page();
}

/**
 * Excerpt length for cards.
 */
function mm_excerpt_length( int $length ): int {
	if ( is_admin() ) {
		return $length;
	}
	$words = (int) mm_opt( 'blog_excerpt' );
	return $words > 0 ? $words : $length;
}
add_filter( 'excerpt_length', 'mm_excerpt_length', 99 );

function mm_excerpt_more(): string {
	return '…';
}
add_filter( 'excerpt_more', 'mm_excerpt_more' );

/**
 * Remove emoji assets when requested.
 */
function mm_maybe_disable_emoji(): void {
	if ( ! mm_opt( 'perf_emoji' ) ) {
		return;
	}
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'mm_maybe_disable_emoji' );

/**
 * Pingback header for singular pages that accept pings.
 */
function mm_pingback_header(): void {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">' . "\n", esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'mm_pingback_header' );

/**
 * When switching from the previous Mine Mirth theme, carry over the logo and
 * menu assignments so the site does not lose its branding.
 *
 * @param string   $old_name  Previous theme name.
 * @param WP_Theme $old_theme Previous theme object.
 */
function mm_after_switch_theme( $old_name = '', $old_theme = null ): void {
	if ( $old_theme instanceof WP_Theme ) {
		$old_mods = get_option( 'theme_mods_' . $old_theme->get_stylesheet() );
		if ( is_array( $old_mods ) ) {
			if ( ! get_theme_mod( 'custom_logo' ) && ! empty( $old_mods['custom_logo'] ) ) {
				set_theme_mod( 'custom_logo', (int) $old_mods['custom_logo'] );
			}
			$locations = get_theme_mod( 'nav_menu_locations', array() );
			if ( empty( $locations ) && ! empty( $old_mods['nav_menu_locations'] ) && is_array( $old_mods['nav_menu_locations'] ) ) {
				$old = $old_mods['nav_menu_locations'];
				$new = array();
				if ( ! empty( $old['primary'] ) ) {
					$new['primary'] = (int) $old['primary'];
				}
				if ( ! empty( $old['footer'] ) ) {
					$new['footer-bottom'] = (int) $old['footer'];
				}
				if ( $new ) {
					set_theme_mod( 'nav_menu_locations', $new );
				}
			}
		}
	}

	if ( function_exists( 'mm_register_hosting_post_type' ) ) {
		mm_register_hosting_post_type();
	}
	if ( function_exists( 'mm_register_promo_post_types' ) ) {
		mm_register_promo_post_types();
	}
	flush_rewrite_rules( false );
}
add_action( 'after_switch_theme', 'mm_after_switch_theme', 10, 2 );

/**
 * Mine Mirth 5 installs next to the current Mine Mirth theme. The first time it runs (when you
 * preview it, test-drive it or activate it), it copies every setting from that theme: logo, colors,
 * texts, homepage settings and menu locations. Run again from Appearance › Site builder.
 *
 * @param bool $force Copy even if settings were already imported.
 */
function mm5_import_settings( bool $force = false ): bool {
	$own = 'theme_mods_' . get_option( 'stylesheet' );
	if ( 'theme_mods_minemirth' === $own ) {
		return false;
	}
	$mine = get_option( $own );
	if ( ! $force && is_array( $mine ) && ! empty( $mine['mm5_imported'] ) ) {
		return false;
	}
	$source = get_option( 'theme_mods_minemirth' );
	if ( ! is_array( $source ) ) {
		if ( ! is_array( $mine ) || empty( $mine['mm5_imported'] ) ) {
			$mine                 = is_array( $mine ) ? $mine : array();
			$mine['mm5_imported'] = 'none';
			update_option( $own, $mine );
		}
		return false;
	}
	unset( $source['mm5_imported'] );
	$merged                 = mm5_clean_mods( array_merge( is_array( $mine ) ? $mine : array(), $source ) );
	$merged['mm5_imported'] = time();
	update_option( $own, $merged );
	return true;
}
add_action( 'after_setup_theme', static fn() => mm5_import_settings(), 1 );

/**
 * Writing style: no long dashes in visible text. Number ranges become "200 to 240", and a dash
 * between two thoughts becomes a comma.
 */
function mm5_clean_dashes( string $text ): string {
	if ( ! preg_match( '/[—–]| -- /u', $text ) ) {
		return $text;
	}
	$text = (string) preg_replace( '/(\d)\s*[–—]\s*(\$?\d)/u', '$1 to $2', $text );
	$text = (string) preg_replace( '/^\s*[—–]\s*|\s*[—–]\s*$/u', '', $text );
	$text = (string) preg_replace( '/\s*[—–]\s*|\s+--\s+/u', ', ', $text );
	return (string) preg_replace( '/,\s*,/', ',', $text );
}

/** Clean the text settings of Mine Mirth 5 (its own copy only; the current theme is never touched). */
function mm5_clean_mods( array $mods ): array {
	foreach ( $mods as $key => $value ) {
		if ( is_string( $value ) && 0 === strpos( (string) $key, 'mm_' ) ) {
			$mods[ $key ] = mm5_clean_dashes( $value );
		}
	}
	$mods['mm5_dashes'] = 1;
	return $mods;
}

add_action(
	'after_setup_theme',
	static function () {
		$own = 'theme_mods_' . get_option( 'stylesheet' );
		if ( 'theme_mods_minemirth' === $own ) {
			return;
		}
		$mods = get_option( $own );
		if ( is_array( $mods ) && empty( $mods['mm5_dashes'] ) ) {
			update_option( $own, mm5_clean_mods( $mods ) );
		}
	},
	2
);
