<?php
/**
 * Plugin Name:       Mine Mirth Ticker
 * Description:       A scrolling announcement bar (right to left) with the live Bitcoin price, hashprices for proof-of-work coins one by one, and your own messages. Every item, coin, colour and speed is adjustable in Appearance › Ticker bar.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Mine Mirth
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-ticker
 *
 * @package MineMirthTicker
 */

defined( 'ABSPATH' ) || exit;

define( 'MMTK_VERSION', '1.0.0' );
define( 'MMTK_FILE', __FILE__ );
define( 'MMTK_DIR', plugin_dir_path( __FILE__ ) );
define( 'MMTK_URI', plugin_dir_url( __FILE__ ) );

require_once MMTK_DIR . 'includes/settings.php';
require_once MMTK_DIR . 'includes/data.php';
require_once MMTK_DIR . 'includes/bar.php';
if ( is_admin() ) {
	require_once MMTK_DIR . 'includes/admin.php';
}
