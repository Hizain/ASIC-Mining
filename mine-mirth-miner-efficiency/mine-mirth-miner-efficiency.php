<?php
/**
 * Plugin Name:       Mine Mirth Miner Efficiency
 * Description:       Shows each miner's efficiency from its own power draw and hashrate, in the right unit for its algorithm (J/TH, J/GH, J/MH…), instead of forcing everything into J/TH (which made Scrypt, Alephium and other miners show huge numbers). Turn it on or off per product and adjust any product in WooCommerce › Miner efficiency or on the product's edit screen.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Requires Plugins:  woocommerce
 * Author:            Mine Mirth
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-miner-efficiency
 *
 * @package MineMirthMinerEfficiency
 */

defined( 'ABSPATH' ) || exit;

define( 'MMEFF_VERSION', '1.0.0' );
define( 'MMEFF_FILE', __FILE__ );
define( 'MMEFF_DIR', plugin_dir_path( __FILE__ ) );

require_once MMEFF_DIR . 'includes/core.php';
require_once MMEFF_DIR . 'includes/front.php';
if ( is_admin() ) {
	require_once MMEFF_DIR . 'includes/admin.php';
}
