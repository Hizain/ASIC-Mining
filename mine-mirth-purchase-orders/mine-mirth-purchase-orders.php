<?php
/**
 * Plugin Name:       Mine Mirth Purchase Orders
 * Description:       Create, edit and track purchase orders with live statuses, and download each one as a PDF in Mine Mirth branding. Pick products from your WooCommerce catalog (or type your own), set prices, shipping, taxes and fees, and the ship-to address. The PDF shows your Oregon address, email and phone in the website's fonts and colours.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-purchase-orders
 *
 * @package MineMirthPurchaseOrders
 */

defined( 'ABSPATH' ) || exit;

define( 'MMPO_VERSION', '1.0.0' );
define( 'MMPO_FILE', __FILE__ );
define( 'MMPO_DIR', plugin_dir_path( __FILE__ ) );
define( 'MMPO_URI', plugin_dir_url( __FILE__ ) );

require_once MMPO_DIR . 'includes/data.php';
require_once MMPO_DIR . 'includes/pdf.php';
require_once MMPO_DIR . 'includes/document.php';
if ( is_admin() ) {
	require_once MMPO_DIR . 'includes/admin.php';
	require_once MMPO_DIR . 'includes/editor.php';
	require_once MMPO_DIR . 'includes/settings.php';
}

/**
 * Purchase orders are stored as private posts of their own type. They never appear on the website.
 */
add_action(
	'init',
	static function (): void {
		register_post_type(
			'mmpo_order',
			array(
				'labels'              => array(
					'name'          => __( 'Purchase orders', 'mine-mirth-purchase-orders' ),
					'singular_name' => __( 'Purchase order', 'mine-mirth-purchase-orders' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'query_var'           => false,
				'rewrite'             => false,
				'can_export'          => true,
				'supports'            => array( 'title' ),
			)
		);
	}
);

register_activation_hook(
	__FILE__,
	static function (): void {
		if ( false === get_option( 'mmpo_settings', false ) ) {
			add_option( 'mmpo_settings', mmpo_default_settings(), '', false );
		}
		if ( false === get_option( 'mmpo_next_number', false ) ) {
			add_option( 'mmpo_next_number', 1001, '', false );
		}
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	static function ( array $links ): array {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=mmpo' ) ) . '">' . esc_html__( 'Purchase orders', 'mine-mirth-purchase-orders' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=mmpo-settings' ) ) . '">' . esc_html__( 'Settings', 'mine-mirth-purchase-orders' ) . '</a>'
		);
		return $links;
	}
);
