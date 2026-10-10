<?php
/**
 * Building each email and sending in small batches.
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

function mmm_campaign_kind( int $campaign ): string {
	return 'weekly' === get_post_meta( $campaign, '_mmm_kind', true ) ? 'weekly' : 'manual';
}

/**
 * Subject and full HTML for one subscriber.
 *
 * @return array{subject:string,html:string,text:string}|null
 */
function mmm_compose( int $campaign, int $sub ): ?array {
	static $cache = array();
	if ( 'mmm_campaign' !== get_post_type( $campaign ) || ! $sub ) {
		return null;
	}
	if ( 'weekly' === mmm_campaign_kind( $campaign ) ) {
		$w    = mmm_weekly_compose( $sub );
		$html = mmm_document( array( 'subject' => $w['subject'], 'preheader' => $w['preheader'], 'body' => $w['body'], 'campaign' => $campaign ) );
		$subj = $w['subject'];
	} else {
		if ( ! isset( $cache[ $campaign ] ) ) {
			$post               = get_post( $campaign );
			$subject            = (string) get_post_meta( $campaign, '_mmm_subject', true );
			$subject            = '' !== trim( $subject ) ? $subject : (string) $post->post_title;
			$cache[ $campaign ] = array(
				'subject' => $subject,
				'html'    => mmm_document(
					array(
						'subject'   => $subject,
						'preheader' => (string) get_post_meta( $campaign, '_mmm_preheader', true ),
						'body'      => mmm_render_blocks( (string) $post->post_content ),
						'campaign'  => $campaign,
					)
				),
			);
		}
		$html = $cache[ $campaign ]['html'];
		$subj = $cache[ $campaign ]['subject'];
	}
	$html = mmm_personalize( $html, $sub, $campaign );
	$name = mmm_sub_first_name( $sub );
	$subj = (string) preg_replace_callback( '/\{first_name(?:\|([^}]*))?\}/', static fn( $m ) => $name ? $name : ( $m[1] ?? '' ), $subj );
	return array( 'subject' => trim( $subj ), 'html' => $html, 'text' => mmm_plain_text( $html ) );
}

/**
 * Full email for "view in browser".
 */
function mmm_render_for( int $campaign, int $sub ): string {
	$c = mmm_compose( $campaign, $sub );
	return $c ? $c['html'] : '';
}

/**
 * Send one email (HTML with a plain-text part) through wp_mail, so SMTP plugins apply.
 */
function mmm_mail( string $to, string $subject, string $html, string $text, int $sub = 0, int $campaign = 0 ): array {
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$from    = sanitize_email( (string) mmm_opt( 'from_email' ) );
	if ( $from && is_email( $from ) ) {
		$headers[] = 'From: ' . sanitize_text_field( (string) mmm_opt( 'from_name' ) ) . ' <' . $from . '>';
	}
	$reply = sanitize_email( (string) mmm_opt( 'reply_to' ) );
	if ( $reply && is_email( $reply ) ) {
		$headers[] = 'Reply-To: ' . $reply;
	}
	if ( $sub ) {
		$unsub     = mmm_link( 'unsub', $campaign, mmm_sub_token( $sub ) );
		$headers[] = 'List-Unsubscribe: <' . $unsub . '>' . ( $reply ? ', <mailto:' . $reply . '?subject=unsubscribe>' : '' );
		$headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
	}
	// Add the plain-text alternative for clients that prefer it.
	$alt = static function ( $phpmailer ) use ( $text ) {
		$phpmailer->AltBody = $text;
	};
	add_action( 'phpmailer_init', $alt );
	$error = '';
	$catch = static function ( $e ) use ( &$error ) {
		$error = is_wp_error( $e ) ? $e->get_error_message() : 'Unknown error';
	};
	add_action( 'wp_mail_failed', $catch );
	$ok = (bool) wp_mail( $to, $subject, $html, $headers );
	remove_action( 'phpmailer_init', $alt );
	remove_action( 'wp_mail_failed', $catch );
	return array( $ok, $ok ? '' : ( $error ? $error : __( 'The server did not accept the email.', 'mine-mirth-mailer' ) ) );
}

/* ---------------------------------------------------------------------------
 * Queue
 * ------------------------------------------------------------------------- */

/**
 * Add recipients to a campaign's queue and mark it as sending.
 */
function mmm_queue_campaign( int $campaign, array $sub_ids ): int {
	global $wpdb;
	$table = mmm_table( 'queue' );
	$added = 0;
	foreach ( array_unique( array_map( 'intval', $sub_ids ) ) as $sub ) {
		$email = mmm_sub_email( $sub );
		if ( ! $email || ! mmm_sub_is_active( $sub ) ) {
			continue;
		}
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE campaign_id = %d AND sub_id = %d LIMIT 1", $campaign, $sub ) ); // phpcs:ignore WordPress.DB
		if ( $exists ) {
			continue;
		}
		$wpdb->insert( $table, array( 'campaign_id' => $campaign, 'sub_id' => $sub, 'email' => $email, 'status' => 'pending' ) ); // phpcs:ignore WordPress.DB
		++$added;
	}
	update_post_meta( $campaign, '_mmm_state', 'sending' );
	update_post_meta( $campaign, '_mmm_started', time() );
	delete_post_meta( $campaign, '_mmm_scheduled' );
	if ( ! wp_next_scheduled( 'mmm_send_now' ) ) {
		wp_schedule_single_event( time(), 'mmm_send_now' );
	}
	return $added;
}

/**
 * Send the next batch. Each row is claimed before sending so two runs never send twice.
 */
function mmm_process_queue(): int {
	global $wpdb;
	$table = mmm_table( 'queue' );
	$batch = max( 1, min( 200, (int) mmm_opt( 'batch' ) ) );
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT id, campaign_id, sub_id, email FROM $table WHERE status = 'pending' ORDER BY id ASC LIMIT %d", $batch ) ); // phpcs:ignore WordPress.DB
	$sent  = 0;
	foreach ( (array) $rows as $row ) {
		$claimed = $wpdb->update( $table, array( 'status' => 'sending' ), array( 'id' => $row->id, 'status' => 'pending' ) ); // phpcs:ignore WordPress.DB
		if ( 1 !== (int) $claimed ) {
			continue;
		}
		$campaign = (int) $row->campaign_id;
		$sub      = (int) $row->sub_id;
		if ( ! mmm_sub_is_active( $sub ) ) {
			$wpdb->update( $table, array( 'status' => 'skipped', 'error' => 'Unsubscribed' ), array( 'id' => $row->id ) ); // phpcs:ignore WordPress.DB
			continue;
		}
		$mail = mmm_compose( $campaign, $sub );
		if ( ! $mail ) {
			$wpdb->update( $table, array( 'status' => 'failed', 'error' => 'Campaign missing' ), array( 'id' => $row->id ) ); // phpcs:ignore WordPress.DB
			continue;
		}
		[ $ok, $error ] = mmm_mail( (string) $row->email, $mail['subject'], $mail['html'], $mail['text'], $sub, $campaign );
		$wpdb->update( // phpcs:ignore WordPress.DB
			$table,
			array( 'status' => $ok ? 'sent' : 'failed', 'error' => substr( $error, 0, 250 ), 'sent' => current_time( 'mysql', true ) ),
			array( 'id' => $row->id )
		);
		if ( $ok ) {
			++$sent;
			mmm_event( $sub, 'sent', array( 'campaign_id' => $campaign ) );
			update_post_meta( $sub, '_mmm_last_sent', time() );
		}
	}
	// Close finished campaigns.
	$open = $wpdb->get_col( "SELECT DISTINCT campaign_id FROM $table WHERE status IN ('sent','failed','skipped')" ); // phpcs:ignore WordPress.DB
	foreach ( $open as $cid ) {
		if ( 'sending' === get_post_meta( (int) $cid, '_mmm_state', true ) ) {
			$left = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE campaign_id = %d AND status IN ('pending','sending')", (int) $cid ) ); // phpcs:ignore WordPress.DB
			if ( 0 === $left ) {
				update_post_meta( (int) $cid, '_mmm_state', 'sent' );
				update_post_meta( (int) $cid, '_mmm_finished', time() );
			}
		}
	}
	return $sent;
}

/**
 * Start scheduled campaigns whose time has come.
 */
function mmm_start_due(): void {
	$due = get_posts(
		array(
			'post_type'      => 'mmm_campaign',
			'post_status'    => 'any',
			'posts_per_page' => 20,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => '_mmm_state', 'value' => 'scheduled' ),
				array( 'key' => '_mmm_scheduled', 'value' => time(), 'compare' => '<=', 'type' => 'NUMERIC' ),
			),
		)
	);
	foreach ( $due as $id ) {
		mmm_queue_campaign( (int) $id, mmm_audience_ids( (string) get_post_meta( $id, '_mmm_audience', true ) ?: 'all' ) );
	}
}

add_action(
	'mmm_minute',
	static function () {
		mmm_start_due();
		mmm_process_queue();
	}
);
add_action(
	'mmm_send_now',
	static function () {
		mmm_process_queue();
		global $wpdb;
		$left = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . mmm_table( 'queue' ) . " WHERE status = 'pending'" ); // phpcs:ignore WordPress.DB
		if ( $left && ! wp_next_scheduled( 'mmm_send_now' ) ) {
			wp_schedule_single_event( time() + 60, 'mmm_send_now' );
		}
	}
);

/**
 * Numbers for a campaign.
 *
 * @return array{recipients:int,sent:int,failed:int,pending:int,opens:int,clicks:int,clickers:int,replies:int,unsubs:int}
 */
function mmm_campaign_stats( int $campaign ): array {
	global $wpdb;
	$q  = mmm_table( 'queue' );
	$e  = mmm_table( 'events' );
	$m  = mmm_table( 'messages' );
	$by = array();
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT status, COUNT(*) n FROM $q WHERE campaign_id = %d GROUP BY status", $campaign ) ) as $r ) { // phpcs:ignore WordPress.DB
		$by[ $r->status ] = (int) $r->n;
	}
	$count = static fn( string $type, bool $unique ) => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(' . ( $unique ? 'DISTINCT sub_id' : '*' ) . ") FROM $e WHERE campaign_id = %d AND type = %s", $campaign, $type ) ); // phpcs:ignore WordPress.DB
	return array(
		'recipients' => array_sum( $by ),
		'sent'       => $by['sent'] ?? 0,
		'failed'     => $by['failed'] ?? 0,
		'pending'    => ( $by['pending'] ?? 0 ) + ( $by['sending'] ?? 0 ),
		'opens'      => $count( 'open', true ),
		'clicks'     => $count( 'click', false ),
		'clickers'   => $count( 'click', true ),
		'replies'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT sub_id) FROM $m WHERE campaign_id = %d AND direction = 'in'", $campaign ) ), // phpcs:ignore WordPress.DB
		'unsubs'     => $count( 'unsub', true ),
	);
}
