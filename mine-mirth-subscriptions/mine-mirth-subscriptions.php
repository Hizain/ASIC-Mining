<?php
/**
 * Plugin Name:       Mine Mirth Subscriptions
 * Description:       Stripe subscriptions for your hosting plans. Plans you create in WordPress sync to Stripe at once (with their images) and show on your hosting page with a Subscribe button. See active subscriptions, issue refunds, add a discount or an extra charge, give every client a client number, download invoice PDFs and send Mine Mirth styled emails when customers subscribe, before each billing and when they pay.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-subscriptions
 *
 * @package MineMirthSubscriptions
 */

defined( 'ABSPATH' ) || exit;

define( 'MMS_VERSION', '1.0.0' );
define( 'MMS_FILE', __FILE__ );
define( 'MMS_DIR', plugin_dir_path( __FILE__ ) );
define( 'MMS_URI', plugin_dir_url( __FILE__ ) );
define( 'MMS_DB', 1 );

require_once MMS_DIR . 'includes/db.php';
require_once MMS_DIR . 'includes/stripe.php';
require_once MMS_DIR . 'includes/settings.php';
require_once MMS_DIR . 'includes/plans.php';
require_once MMS_DIR . 'includes/sync.php';
require_once MMS_DIR . 'includes/webhooks.php';
require_once MMS_DIR . 'includes/checkout.php';
require_once MMS_DIR . 'includes/emails.php';
require_once MMS_DIR . 'includes/pdf.php';
require_once MMS_DIR . 'includes/invoice-pdf.php';
if ( is_admin() ) {
	require_once MMS_DIR . 'includes/admin.php';
	require_once MMS_DIR . 'includes/admin-detail.php';
}

register_activation_hook(
	__FILE__,
	static function (): void {
		mms_install();
		if ( false === get_option( 'mms_next_client', false ) ) {
			add_option( 'mms_next_client', 1001, '', false );
		}
		if ( ! wp_next_scheduled( 'mms_hourly' ) ) {
			wp_schedule_event( time() + 120, 'hourly', 'mms_hourly' );
		}
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		wp_clear_scheduled_hook( 'mms_hourly' );
	}
);

// Upgrades and a cron safety net.
add_action(
	'init',
	static function (): void {
		if ( (int) get_option( 'mms_db', 0 ) < MMS_DB ) {
			mms_install();
		}
		if ( ! wp_next_scheduled( 'mms_hourly' ) ) {
			wp_schedule_event( time() + 120, 'hourly', 'mms_hourly' );
		}
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	static function ( array $links ): array {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'admin.php?page=mms' ) ) . '">' . esc_html__( 'Subscriptions', 'mine-mirth-subscriptions' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=mms-settings' ) ) . '">' . esc_html__( 'Connect Stripe', 'mine-mirth-subscriptions' ) . '</a>'
		);
		return $links;
	}
);
