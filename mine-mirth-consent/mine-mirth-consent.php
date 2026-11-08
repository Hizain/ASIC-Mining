<?php
/**
 * Plugin Name:       Mine Mirth Cookie Consent
 * Description:       A premium cookie banner for first-time visitors with "Accept" (highlighted) and "Deny". It really controls tracking: Google Consent Mode v2, scripts that only load after Accept, the WP Consent API and Global Privacy Control. Remembers each choice, lets visitors change it, and keeps an anonymous record of consents. Settings: Settings › Cookie consent.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Mine Mirth
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-consent
 *
 * @package MineMirthConsent
 */

defined( 'ABSPATH' ) || exit;

define( 'MMCC_VERSION', '1.0.0' );
define( 'MMCC_FILE', __FILE__ );
define( 'MMCC_DIR', plugin_dir_path( __FILE__ ) );
define( 'MMCC_URI', plugin_dir_url( __FILE__ ) );
const MMCC_COOKIE = 'mm_consent';

require_once MMCC_DIR . 'includes/settings.php';
require_once MMCC_DIR . 'includes/records.php';
require_once MMCC_DIR . 'includes/front.php';
if ( is_admin() ) {
	require_once MMCC_DIR . 'includes/admin.php';
}

register_activation_hook( __FILE__, 'mmcc_install' );
register_deactivation_hook( __FILE__, static fn() => wp_clear_scheduled_hook( 'mmcc_daily' ) );
