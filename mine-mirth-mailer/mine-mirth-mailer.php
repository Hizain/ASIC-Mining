<?php
/**
 * Plugin Name:       Mine Mirth Mailer
 * Description:       Email marketing in your website's design: a welcome email for every website sign-up, emails you write in a normal editor (or with email blocks) and send to a group or to specific people, a weekly email personalised to what each subscriber did on the site, subscribers you add by hand with an introduction email, open and click tracking, and a reply inbox you answer from WordPress. Uses the subscribers the Mine Mirth theme already collects and links to its live tools and guides.
 * Version:           1.2.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-mailer
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

define( 'MMM_VERSION', '1.2.0' );
define( 'MMM_FILE', __FILE__ );
define( 'MMM_DIR', plugin_dir_path( __FILE__ ) );
define( 'MMM_URI', plugin_dir_url( __FILE__ ) );

require_once MMM_DIR . 'includes/db.php';
require_once MMM_DIR . 'includes/subscribers.php';
require_once MMM_DIR . 'includes/tracking.php';
require_once MMM_DIR . 'includes/email-template.php';
require_once MMM_DIR . 'includes/blocks.php';
require_once MMM_DIR . 'includes/weekly.php';
require_once MMM_DIR . 'includes/campaigns.php';
require_once MMM_DIR . 'includes/sender.php';
require_once MMM_DIR . 'includes/inbox.php';
require_once MMM_DIR . 'includes/manual.php';
require_once MMM_DIR . 'includes/site.php';
require_once MMM_DIR . 'includes/welcome.php';
require_once MMM_DIR . 'includes/compose.php';
require_once MMM_DIR . 'includes/admin.php';

register_activation_hook(
	__FILE__,
	static function () {
		mmm_install();
		mmm_schedule_events();
	}
);
register_deactivation_hook(
	__FILE__,
	static function () {
		foreach ( array( 'mmm_minute', 'mmm_hourly', 'mmm_imap' ) as $hook ) {
			wp_clear_scheduled_hook( $hook );
		}
	}
);

add_filter(
	'cron_schedules',
	static function ( $s ) {
		$s['mmm_every_minute']   = array( 'interval' => 60, 'display' => 'Every minute (Mine Mirth Mailer)' );
		$s['mmm_every_15_minutes'] = array( 'interval' => 900, 'display' => 'Every 15 minutes (Mine Mirth Mailer)' );
		return $s;
	}
);

function mmm_schedule_events(): void {
	if ( ! wp_next_scheduled( 'mmm_minute' ) ) {
		wp_schedule_event( time() + 60, 'mmm_every_minute', 'mmm_minute' );
	}
	if ( ! wp_next_scheduled( 'mmm_hourly' ) ) {
		wp_schedule_event( time() + 120, 'hourly', 'mmm_hourly' );
	}
	if ( ! wp_next_scheduled( 'mmm_imap' ) ) {
		wp_schedule_event( time() + 300, 'mmm_every_15_minutes', 'mmm_imap' );
	}
}

// Upgrades and copies installed without activation still get their tables and schedules.
add_action(
	'init',
	static function () {
		if ( get_option( 'mmm_db_version' ) !== MMM_VERSION ) {
			mmm_install();
			mmm_upgrade();
		}
		mmm_schedule_events();
	},
	5
);

/**
 * Settings with defaults.
 */
function mmm_defaults(): array {
	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	return array(
		'from_name'     => $site,
		'from_email'    => '',
		'reply_to'      => function_exists( 'mm_opt' ) && mm_opt( 'contact_email' ) ? (string) mm_opt( 'contact_email' ) : get_option( 'admin_email' ),
		'address'       => '',
		'batch'         => 25,
		'track_site'    => 1,
		'track_opens'   => 1,
		'track_clicks'  => 1,
		'logo_id'       => 0,
		'brand_name'    => 'The Miner’s Brief',
		'footer_note'   => __( 'You’re receiving this because you subscribed at our website.', 'mine-mirth-mailer' ),
		'footer_manual' => __( 'You’re receiving this because you’re in touch with Mine Mirth.', 'mine-mirth-mailer' ),
		'timezone'      => 'Asia/Karachi',
		'notify_replies'=> 1,
		'imap_host'     => '',
		'imap_port'     => 993,
		'imap_secure'   => 'ssl',
		'imap_user'     => '',
		'imap_pass'     => '',
		'imap_folder'   => 'INBOX',
	);
}

/**
 * Time zone for the weekly schedule, scheduled sends and dates shown by the Mailer (Pakistan time by default).
 */
function mmm_tz(): DateTimeZone {
	static $tz = null;
	if ( null === $tz ) {
		try {
			$tz = new DateTimeZone( (string) mmm_opt( 'timezone' ) );
		} catch ( Exception $e ) {
			$tz = wp_timezone();
		}
	}
	return $tz;
}

function mmm_date( string $format, ?int $ts = null ): string {
	return (string) wp_date( $format, $ts ?? time(), mmm_tz() );
}

/**
 * One-time changes when updating from an older version.
 */
function mmm_upgrade(): void {
	// The introduction email used to promote the retired Rig Optimizer and Market Scope tools.
	$intro = get_option( 'mmm_intro' );
	if ( is_array( $intro ) && isset( $intro['s3_text'] ) && false !== stripos( (string) $intro['s3_text'], 'rig optimizer' ) ) {
		$intro['s3_text'] = mmm_intro_defaults()['s3_text'];
		update_option( 'mmm_intro', $intro );
	}
}

function mmm_opt( string $key ) {
	$all = get_option( 'mmm_settings', array() );
	$def = mmm_defaults();
	return is_array( $all ) && array_key_exists( $key, $all ) ? $all[ $key ] : ( $def[ $key ] ?? null );
}
