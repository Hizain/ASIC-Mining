<?php
/**
 * Plugin Name:       Mine Mirth Launch
 * Description:       Test the new Mine Mirth 5 theme privately (only you see it while visitors keep your current theme), then launch it with every existing URL redirected to the right place. Remembers all your page, post, product and category addresses, sends moved or removed ones to their new home with a 301, and logs any remaining 404s so you can fix them in one click.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-launch
 *
 * @package MineMirthLaunch
 */

defined( 'ABSPATH' ) || exit;

define( 'MML_VERSION', '1.0.0' );
define( 'MML_FILE', __FILE__ );
define( 'MML_DIR', __DIR__ );
define( 'MML_URI', plugin_dir_url( __FILE__ ) );
define( 'MML_DB', 1 );

require_once MML_DIR . '/includes/testdrive.php';
require_once MML_DIR . '/includes/memory.php';
require_once MML_DIR . '/includes/redirects.php';
if ( is_admin() ) {
	require_once MML_DIR . '/includes/admin.php';
}

/**
 * Database tables: remembered URLs and the redirect / 404 log.
 */
function mml_install(): void {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$c = $wpdb->get_charset_collate();
	dbDelta(
		"CREATE TABLE {$wpdb->prefix}mml_urls (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			path varchar(191) NOT NULL,
			obj_type varchar(20) NOT NULL DEFAULT '',
			obj_sub varchar(60) NOT NULL DEFAULT '',
			obj_id bigint(20) unsigned NOT NULL DEFAULT 0,
			first_seen datetime NOT NULL,
			last_seen datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY path (path),
			KEY obj (obj_type, obj_id)
		) $c;"
	);
	dbDelta(
		"CREATE TABLE {$wpdb->prefix}mml_log (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			path varchar(191) NOT NULL,
			status varchar(12) NOT NULL DEFAULT '404',
			target varchar(255) NOT NULL DEFAULT '',
			reason varchar(40) NOT NULL DEFAULT '',
			hits bigint(20) unsigned NOT NULL DEFAULT 0,
			referrer varchar(255) NOT NULL DEFAULT '',
			last_seen datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY path (path),
			KEY status (status)
		) $c;"
	);
	update_option( 'mml_db', MML_DB, false );
}

register_activation_hook(
	__FILE__,
	static function () {
		mml_install();
		mml_memory_scan();
		if ( ! wp_next_scheduled( 'mml_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mml_daily' );
		}
		if ( false === get_option( 'mml_settings' ) ) {
			add_option( 'mml_settings', mml_default_settings(), '', false );
		}
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		wp_clear_scheduled_hook( 'mml_daily' );
		delete_option( 'mml_testdrive' );
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( (int) get_option( 'mml_db' ) < MML_DB ) {
			mml_install();
		}
	},
	5
);

add_action( 'mml_daily', 'mml_memory_scan' );

function mml_default_settings(): array {
	return array(
		'smart'    => 1,   // Find the best page for unknown URLs.
		'sections' => 1,   // Fall back to the closest section (shop, blog) when nothing better is found.
		'log'      => 1,   // Log 404s and redirects.
	);
}

function mml_settings(): array {
	$s = get_option( 'mml_settings' );
	return wp_parse_args( is_array( $s ) ? $s : array(), mml_default_settings() );
}

/** Site-relative path without slashes at either end, lowercased, for lookups. */
function mml_normalize_path( string $url ): string {
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	$path = rawurldecode( $path );
	$home = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	$path = trim( $path, '/' );
	if ( '' !== $home && ( $path === $home || 0 === strpos( $path, $home . '/' ) ) ) {
		$path = trim( substr( $path, strlen( $home ) ), '/' );
	}
	return strtolower( substr( $path, 0, 191 ) );
}
