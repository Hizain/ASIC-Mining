<?php
/**
 * Plugin Name:       Mine Mirth Shop Pro
 * Description:       Shop page upgrades for Mine Mirth: a clearer filter panel (full category tree, every attribute with live counts, In Stock / Pre-Order / Out of Stock, price range) and automatic "Best choice" miners picked from live market data, refreshed through the day. Works with the Mine Mirth theme as installed.
 * Version:           1.0.0
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

define( 'MMSP_VERSION', '1.0.0' );
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
		'best_enabled'     => 1,
		'best_count'       => 4,
		'best_power'       => '0.08',
		'best_hours'       => 6,
		'best_strip'       => 1,
		'best_badge'       => 1,
		'best_exclude'     => '',
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
		require_once MMSP_DIR . 'includes/best-choice.php';
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

register_activation_hook(
	__FILE__,
	static function () {
		if ( ! wp_next_scheduled( 'mmsp_refresh_best' ) ) {
			wp_schedule_event( time() + 60, 'hourly', 'mmsp_refresh_best' );
		}
	}
);
register_deactivation_hook( __FILE__, static fn() => wp_clear_scheduled_hook( 'mmsp_refresh_best' ) );

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
