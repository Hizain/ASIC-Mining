<?php
/**
 * Plugin Name:       Mine Mirth Commerce
 * Plugin URI:        https://minemirth.com
 * Description:       Fixes and store features for minemirth.com: readable product pages, a page rebuilder with ready-made section shortcodes, better shop filters (attributes and brands), direct-to-wallet crypto payments and built-in subscriptions.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Mine Mirth
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mine-mirth-commerce
 * WC requires at least: 8.0
 *
 * @package MineMirthCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'MMC_VERSION', '1.0.0' );
define( 'MMC_FILE', __FILE__ );
define( 'MMC_DIR', plugin_dir_path( __FILE__ ) );
define( 'MMC_URI', plugin_dir_url( __FILE__ ) );

require_once MMC_DIR . 'includes/helpers.php';
require_once MMC_DIR . 'includes/settings.php';
require_once MMC_DIR . 'includes/sections.php';
require_once MMC_DIR . 'includes/page-rebuilder.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		require_once MMC_DIR . 'includes/product-page.php';
		require_once MMC_DIR . 'includes/shop-filters.php';
		require_once MMC_DIR . 'includes/crypto-gateway.php';
		require_once MMC_DIR . 'includes/subscriptions.php';
	},
	20
);

// Crypto payments in the block checkout. Registered here because WooCommerce
// fires this hook before plugins_loaded priority 20.
add_action(
	'woocommerce_blocks_loaded',
	static function () {
		if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			return;
		}
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			static function ( $registry ) {
				if ( function_exists( 'mmc_crypto_gateway' ) ) {
					require_once MMC_DIR . 'includes/crypto-blocks.php';
					$registry->register( new MMC_Crypto_Blocks() );
				}
			}
		);
	}
);

// Declare compatibility with WooCommerce order storage (HPOS).
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', MMC_FILE, true );
		}
	}
);

register_activation_hook(
	__FILE__,
	static function () {
		if ( ! wp_next_scheduled( 'mmc_cron' ) ) {
			wp_schedule_event( time() + 300, 'mmc_five_minutes', 'mmc_cron' );
		}
		update_option( 'mmc_flush_rewrite', 1 );
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		wp_clear_scheduled_hook( 'mmc_cron' );
		flush_rewrite_rules();
	}
);

add_filter(
	'cron_schedules',
	static function ( $schedules ) {
		$schedules['mmc_five_minutes'] = array(
			'interval' => 300,
			'display'  => __( 'Every five minutes', 'mine-mirth-commerce' ),
		);
		return $schedules;
	}
);

// The cron hook may be missing if the plugin was copied in rather than activated.
add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( 'mmc_cron' ) ) {
			wp_schedule_event( time() + 300, 'mmc_five_minutes', 'mmc_cron' );
		}
	},
	30
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'mmc', MMC_URI . 'assets/css/mmc.css', array(), MMC_VERSION );
	},
	30
);
