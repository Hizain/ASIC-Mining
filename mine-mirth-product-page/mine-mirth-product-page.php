<?php
/**
 * Plugin Name:       Mine Mirth Product Page
 * Description:       A fast product image gallery (main image with a clickable thumbnail carousel, swipe, zoom and full-screen view) that is drawn at its final size, so product pages no longer jump while loading. Plus a cleaner, more readable product description. Everything is adjustable in WooCommerce › Product page.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Requires Plugins:  woocommerce
 * Author:            Mine Mirth
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-product-page
 *
 * @package MineMirthProductPage
 */

defined( 'ABSPATH' ) || exit;

define( 'MMPP_VERSION', '1.0.0' );
define( 'MMPP_FILE', __FILE__ );
define( 'MMPP_DIR', plugin_dir_path( __FILE__ ) );
define( 'MMPP_URI', plugin_dir_url( __FILE__ ) );

require_once MMPP_DIR . 'includes/settings.php';
require_once MMPP_DIR . 'includes/gallery.php';
require_once MMPP_DIR . 'includes/description.php';
if ( is_admin() ) {
	require_once MMPP_DIR . 'includes/admin.php';
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		wp_enqueue_style( 'mmpp', MMPP_URI . 'assets/product.css', array(), MMPP_VERSION );
		wp_enqueue_script( 'mmpp', MMPP_URI . 'assets/product.js', array(), MMPP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_localize_script(
			'mmpp',
			'mmppL10n',
			array(
				'more' => __( 'Show the full description', 'mine-mirth-product-page' ),
				'less' => __( 'Show less', 'mine-mirth-product-page' ),
			)
		);
	},
	30
);
