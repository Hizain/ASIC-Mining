<?php
/**
 * Plugin Name:       Mine Mirth Hosting Listings
 * Description:       Turns the miner hosting page into a compact, shop-style grid of hosting listings that you control, gives every hosting plan its own full details page (opened in a new tab), and lets you choose where each product's "Host this miner" button goes. Works with the Mine Mirth Subscriptions plugin.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Mine Mirth
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-hosting-listings
 *
 * @package MineMirthHostingListings
 */

defined( 'ABSPATH' ) || exit;

define( 'MMHL_VERSION', '1.0.0' );
define( 'MMHL_FILE', __FILE__ );
define( 'MMHL_DIR', plugin_dir_path( __FILE__ ) );
define( 'MMHL_URI', plugin_dir_url( __FILE__ ) );

/** The hosting plan post type (created by the Mine Mirth theme or the Subscriptions plugin). */
const MMHL_TYPE = 'mm_hosting_plan';

require_once MMHL_DIR . 'includes/settings.php';
require_once MMHL_DIR . 'includes/listings.php';
require_once MMHL_DIR . 'includes/pages.php';
require_once MMHL_DIR . 'includes/host-button.php';
if ( is_admin() ) {
	require_once MMHL_DIR . 'includes/admin.php';
}

// Listing pages need fresh permalink rules: rebuild them once after activation or a URL change.
register_activation_hook( __FILE__, static fn() => update_option( 'mmhl_flush', 1, false ) );
register_deactivation_hook( __FILE__, static fn() => flush_rewrite_rules( false ) );
add_action(
	'init',
	static function (): void {
		if ( get_option( 'mmhl_flush' ) && post_type_exists( MMHL_TYPE ) ) {
			delete_option( 'mmhl_flush' );
			flush_rewrite_rules( false );
		}
	},
	999
);
