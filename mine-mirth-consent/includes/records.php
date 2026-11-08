<?php
/**
 * Consent records: proof of what visitors chose, kept anonymous.
 *
 * Each record holds the time, the choice (accept / deny / gpc), the consent version, the page path and a
 * one-way hash of the visitor's IP address (salted and rotated monthly, so it cannot be turned back into
 * an IP or used to follow anyone over time). Records older than 12 months are deleted every day.
 *
 * @package MineMirthConsent
 */

defined( 'ABSPATH' ) || exit;

function mmcc_table(): string {
	global $wpdb;
	return $wpdb->prefix . 'mmcc_log';
}

function mmcc_install(): void {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta(
		'CREATE TABLE ' . mmcc_table() . ' (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created datetime NOT NULL,
			choice varchar(10) NOT NULL,
			consent_version smallint(5) unsigned NOT NULL DEFAULT 1,
			visitor char(16) NOT NULL,
			page varchar(190) NOT NULL DEFAULT \'\',
			PRIMARY KEY  (id),
			KEY created (created)
		) ' . $wpdb->get_charset_collate() . ';'
	);
	update_option( 'mmcc_db', 1, false );
	if ( ! wp_next_scheduled( 'mmcc_daily' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'mmcc_daily' );
	}
}

// Install the table after an update without re-activation.
add_action(
	'init',
	static function (): void {
		if ( 1 !== (int) get_option( 'mmcc_db' ) ) {
			mmcc_install();
		}
	}
);

add_action(
	'mmcc_daily',
	static function (): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . mmcc_table() . ' WHERE created < %s', gmdate( 'Y-m-d H:i:s', time() - YEAR_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB
	}
);

/** Anonymous visitor id: salted hash of the IP, different every month. */
function mmcc_visitor(): string {
	$ip = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	return substr( hash_hmac( 'sha256', $ip . '|' . gmdate( 'Y-m' ), wp_salt( 'nonce' ) ), 0, 16 );
}

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'mine-mirth-consent/v1',
			'/record',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'args'                => array(
					'choice' => array( 'required' => true, 'type' => 'string', 'enum' => array( 'accept', 'deny', 'gpc' ) ),
					'page'   => array( 'type' => 'string' ),
				),
				'callback'            => static function ( WP_REST_Request $req ) {
					if ( ! mmcc_opt( 'records' ) ) {
						return new WP_REST_Response( array( 'ok' => true ), 200 );
					}
					$visitor = mmcc_visitor();
					// At most 20 records per visitor per hour: no one can flood the log.
					$key  = 'mmcc_rl_' . $visitor;
					$hits = (int) get_transient( $key );
					if ( $hits >= 20 ) {
						return new WP_REST_Response( array( 'ok' => false ), 429 );
					}
					set_transient( $key, $hits + 1, HOUR_IN_SECONDS );
					global $wpdb;
					$page = (string) wp_parse_url( esc_url_raw( (string) $req->get_param( 'page' ) ), PHP_URL_PATH );
					$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
						mmcc_table(),
						array(
							'created'         => current_time( 'mysql', true ),
							'choice'          => (string) $req->get_param( 'choice' ),
							'consent_version' => (int) mmcc_opt( 'version' ),
							'visitor'         => $visitor,
							'page'            => substr( sanitize_text_field( $page ), 0, 190 ),
						)
					);
					return new WP_REST_Response( array( 'ok' => true ), 201 );
				},
			)
		);
	}
);
