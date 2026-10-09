<?php
/**
 * Plugin Name:       Mine Mirth Shop Pro
 * Description:       Shop filters for Mine Mirth: In Stock / Pre-Order / Out of Stock, the full category tree, price range and every product attribute with live counts. Works with the Mine Mirth theme as installed.
 * Version:           1.2.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-shop-pro
 * WC requires at least: 8.0
 *
 * @package MineMirthShopPro
 */

defined( 'ABSPATH' ) || exit;

define( 'MMSP_VERSION', '1.2.0' );
define( 'MMSP_FILE', __FILE__ );
define( 'MMSP_DIR', plugin_dir_path( __FILE__ ) );
define( 'MMSP_URI', plugin_dir_url( __FILE__ ) );

/**
 * Settings with defaults.
 */
function mmsp_defaults(): array {
	return array(
		'filters_auto'     => 1,
		'label_instock'    => 'In Stock',
		'label_preorder'   => 'Pre-Order',
		'label_outofstock' => 'Out of Stock',
		'show_empty_cats'  => 0,
		'hidden_attrs'     => array(),
	);
}

function mmsp_opt( string $key ) {
	$all = get_option( 'mmsp_settings', array() );
	$def = mmsp_defaults();
	return is_array( $all ) && array_key_exists( $key, $all ) ? $all[ $key ] : ( $def[ $key ] ?? null );
}

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		require_once MMSP_DIR . 'includes/filters.php';
		require_once MMSP_DIR . 'includes/admin.php';
	},
	25
);

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', MMSP_FILE, true );
		}
	}
);

// Version 1.0 scheduled a "best choice" refresh; that feature is removed, so clear it.
add_action(
	'init',
	static function () {
		if ( wp_next_scheduled( 'mmsp_refresh_best' ) ) {
			wp_clear_scheduled_hook( 'mmsp_refresh_best' );
			delete_option( 'mmsp_best' );
		}
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return;
		}
		wp_register_style( 'mmsp', MMSP_URI . 'assets/shop-pro.css', array(), MMSP_VERSION );
		wp_register_script( 'mmsp', MMSP_URI . 'assets/shop-pro.js', array(), MMSP_VERSION, true );
		if ( is_shop() || is_product_taxonomy() ) {
			wp_enqueue_style( 'mmsp' );
			wp_enqueue_script( 'mmsp' );
		}
	},
	30
);
