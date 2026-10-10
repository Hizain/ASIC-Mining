<?php
/**
 * Database tables: activity events, the sending queue and the reply inbox.
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

function mmm_table( string $name ): string {
	global $wpdb;
	return $wpdb->prefix . 'mmm_' . $name;
}

function mmm_install(): void {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset = $wpdb->get_charset_collate();
	dbDelta(
		'CREATE TABLE ' . mmm_table( 'events' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			sub_id bigint(20) unsigned NOT NULL,
			type varchar(20) NOT NULL,
			area varchar(20) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			campaign_id bigint(20) unsigned NOT NULL DEFAULT 0,
			url varchar(255) NOT NULL DEFAULT '',
			created datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY sub_created (sub_id,created),
			KEY campaign_type (campaign_id,type)
		) $charset;"
	);
	dbDelta(
		'CREATE TABLE ' . mmm_table( 'queue' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			campaign_id bigint(20) unsigned NOT NULL,
			sub_id bigint(20) unsigned NOT NULL,
			email varchar(190) NOT NULL,
			status varchar(12) NOT NULL DEFAULT 'pending',
			error varchar(255) NOT NULL DEFAULT '',
			sent datetime NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY campaign_status (campaign_id,status)
		) $charset;"
	);
	dbDelta(
		'CREATE TABLE ' . mmm_table( 'messages' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			sub_id bigint(20) unsigned NOT NULL,
			direction varchar(3) NOT NULL,
			subject varchar(255) NOT NULL DEFAULT '',
			body longtext NOT NULL,
			campaign_id bigint(20) unsigned NOT NULL DEFAULT 0,
			source varchar(12) NOT NULL DEFAULT '',
			is_read tinyint(1) NOT NULL DEFAULT 0,
			created datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY sub_created (sub_id,created)
		) $charset;"
	);
	update_option( 'mmm_db_version', MMM_VERSION, false );
}

/**
 * Record an event (view, open, click, reply, unsub).
 */
function mmm_event( int $sub_id, string $type, array $data = array() ): void {
	global $wpdb;
	if ( $sub_id <= 0 ) {
		return;
	}
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		mmm_table( 'events' ),
		array(
			'sub_id'      => $sub_id,
			'type'        => substr( $type, 0, 20 ),
			'area'        => substr( (string) ( $data['area'] ?? '' ), 0, 20 ),
			'object_id'   => (int) ( $data['object_id'] ?? 0 ),
			'campaign_id' => (int) ( $data['campaign_id'] ?? 0 ),
			'url'         => substr( (string) ( $data['url'] ?? '' ), 0, 255 ),
			'created'     => current_time( 'mysql', true ),
		)
	);
}

/**
 * Has this event already been recorded recently? Used to skip duplicates.
 */
function mmm_event_exists( int $sub_id, string $type, int $campaign_id, int $object_id = -1, int $within = 0 ): bool {
	global $wpdb;
	$sql  = 'SELECT id FROM ' . mmm_table( 'events' ) . ' WHERE sub_id = %d AND type = %s AND campaign_id = %d';
	$args = array( $sub_id, $type, $campaign_id );
	if ( $object_id >= 0 ) {
		$sql   .= ' AND object_id = %d';
		$args[] = $object_id;
	}
	if ( $within > 0 ) {
		$sql   .= ' AND created > %s';
		$args[] = gmdate( 'Y-m-d H:i:s', time() - $within );
	}
	return (bool) $wpdb->get_var( $wpdb->prepare( $sql . ' LIMIT 1', $args ) ); // phpcs:ignore WordPress.DB
}
