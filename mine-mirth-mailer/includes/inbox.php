<?php
/**
 * Replies: the "Ask us anything" page linked from every email, an inbox in
 * WordPress to read and answer them, and (optionally) importing real email
 * replies from your mailbox over IMAP.
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

function mmm_topics(): array {
	return array(
		'question' => __( 'A question', 'mine-mirth-mailer' ),
		'quote'    => __( 'A quote for miners', 'mine-mirth-mailer' ),
		'hosting'  => __( 'Hosting', 'mine-mirth-mailer' ),
		'order'    => __( 'An order', 'mine-mirth-mailer' ),
		'other'    => __( 'Something else', 'mine-mirth-mailer' ),
	);
}

/**
 * Store a message and record the reply.
 */
function mmm_store_message( int $sub, string $direction, string $subject, string $body, int $campaign = 0, string $source = '' ): int {
	global $wpdb;
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		mmm_table( 'messages' ),
		array(
			'sub_id'      => $sub,
			'direction'   => 'in' === $direction ? 'in' : 'out',
			'subject'     => substr( $subject, 0, 250 ),
			'body'        => $body,
			'campaign_id' => $campaign,
			'source'      => $source,
			'is_read'     => 'in' === $direction ? 0 : 1,
			'created'     => current_time( 'mysql', true ),
		)
	);
	if ( 'in' === $direction ) {
		mmm_event( $sub, 'reply', array( 'campaign_id' => $campaign ) );
	}
	return (int) $wpdb->insert_id;
}

function mmm_unread_count(): int {
	global $wpdb;
	return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . mmm_table( 'messages' ) . " WHERE direction = 'in' AND is_read = 0" ); // phpcs:ignore WordPress.DB
}

/**
 * Tell the team a reply arrived.
 */
function mmm_notify_team( int $sub, string $subject, string $body ): void {
	if ( ! mmm_opt( 'notify_replies' ) ) {
		return;
	}
	$to = sanitize_email( (string) mmm_opt( 'reply_to' ) );
	$to = is_email( $to ) ? $to : get_option( 'admin_email' );
	wp_mail(
		$to,
		/* translators: %s: email */
		sprintf( __( 'New reply from %s', 'mine-mirth-mailer' ), mmm_sub_email( $sub ) ),
		$subject . "\n\n" . $body . "\n\n" . __( 'Answer it here:', 'mine-mirth-mailer' ) . ' ' . admin_url( 'admin.php?page=mmm-inbox&sub=' . $sub ),
		array( 'Reply-To: ' . mmm_sub_email( $sub ) )
	);
}

/* ---------------------------------------------------------------------------
 * The reply page subscribers open from an email
 * ------------------------------------------------------------------------- */

add_action(
	'template_redirect',
	static function () {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- token link from an email.
		if ( 'reply' !== ( isset( $_GET['mmm'] ) ? sanitize_key( wp_unslash( $_GET['mmm'] ) ) : '' ) ) {
			return;
		}
		$token    = sanitize_key( wp_unslash( $_GET['s'] ?? '' ) );
		$campaign = absint( wp_unslash( $_GET['c'] ?? 0 ) );
		$topic    = sanitize_key( wp_unslash( $_GET['topic'] ?? '' ) );
		// phpcs:enable
		$sub = $token ? mmm_sub_by_token( $token ) : 0;
		if ( ! $sub ) {
			mmm_site_page( __( 'Link expired', 'mine-mirth-mailer' ), '<p>' . esc_html__( 'Please reply to our email directly, or use the contact page.', 'mine-mirth-mailer' ) . '</p>' );
		}
		if ( mmm_opt( 'track_site' ) ) {
			mmm_set_cookie( $token );
		}

		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( ! isset( $_POST['mmm_reply_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mmm_reply_nonce'] ) ), 'mmm_reply_' . $sub ) || ! empty( $_POST['website'] ) ) {
				mmm_site_page( __( 'Please try again', 'mine-mirth-mailer' ), '<p>' . esc_html__( 'Your message could not be sent. Go back and send it again.', 'mine-mirth-mailer' ) . '</p>' );
			}
			$t     = sanitize_key( wp_unslash( $_POST['topic'] ?? 'question' ) );
			$label = mmm_topics()[ $t ] ?? mmm_topics()['question'];
			$body  = trim( sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ) );
			$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
			if ( '' === $body ) {
				mmm_site_page( __( 'Please write a message', 'mine-mirth-mailer' ), '<p><a href="javascript:history.back()">' . esc_html__( 'Go back', 'mine-mirth-mailer' ) . '</a></p>' );
			}
			if ( $phone ) {
				$body .= "\n\n" . __( 'Phone:', 'mine-mirth-mailer' ) . ' ' . $phone;
				update_post_meta( $sub, '_mmm_phone', $phone );
			}
			mmm_store_message( $sub, 'in', $label, $body, $campaign, 'form' );
			mmm_notify_team( $sub, $label, $body );
			mmm_site_page( __( 'Thanks, we’ve got your message', 'mine-mirth-mailer' ), '<p>' . esc_html__( 'A member of our team will reply to you by email, usually within one business day.', 'mine-mirth-mailer' ) . '</p><p><a class="mm-btn mm-btn--primary" href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Back to the site', 'mine-mirth-mailer' ) . '</a></p>' );
		}

		$email = mmm_sub_email( $sub );
		ob_start();
		?>
		<p style="color:var(--mm-muted,#506279);margin:0 0 24px"><?php echo esc_html( sprintf( /* translators: %s: email */ __( 'We’ll answer you at %s.', 'mine-mirth-mailer' ), $email ) ); ?></p>
		<form method="post" class="mmm-reply-form" style="display:grid;gap:16px">
			<?php wp_nonce_field( 'mmm_reply_' . $sub, 'mmm_reply_nonce' ); ?>
			<label style="display:grid;gap:6px;font-weight:600"><?php esc_html_e( 'What’s it about?', 'mine-mirth-mailer' ); ?>
				<select name="topic">
					<?php foreach ( mmm_topics() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $topic ? $topic : 'question', $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label style="display:grid;gap:6px;font-weight:600"><?php esc_html_e( 'Your message', 'mine-mirth-mailer' ); ?>
				<textarea name="message" rows="7" required placeholder="<?php echo esc_attr( 'quote' === $topic ? __( 'Which miners, how many, and where will they run?', 'mine-mirth-mailer' ) : __( 'How can we help?', 'mine-mirth-mailer' ) ); ?>"></textarea>
			</label>
			<label style="display:grid;gap:6px;font-weight:600"><?php esc_html_e( 'Phone (optional)', 'mine-mirth-mailer' ); ?>
				<input type="tel" name="phone" autocomplete="tel">
			</label>
			<span style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></span>
			<p><button type="submit" class="mm-btn mm-btn--primary"><?php esc_html_e( 'Send message', 'mine-mirth-mailer' ); ?></button></p>
		</form>
		<?php
		mmm_site_page( __( 'Ask us anything', 'mine-mirth-mailer' ), (string) ob_get_clean() );
	},
	0
);

/* ---------------------------------------------------------------------------
 * Answering from WordPress
 * ------------------------------------------------------------------------- */

/**
 * Send a personal message in the site design and keep it in the conversation.
 */
function mmm_send_message( int $sub, string $subject, string $message ): array {
	$email = mmm_sub_email( $sub );
	if ( ! $email ) {
		return array( false, __( 'Subscriber not found.', 'mine-mirth-mailer' ) );
	}
	// Fill {first_name} in the text itself so the stored copy matches what they received.
	$name    = mmm_sub_first_name( $sub );
	$message = (string) preg_replace_callback( '/\{first_name(?:\|([^}]*))?\}/', static fn( $m ) => $name ? $name : ( $m[1] ?? '' ), $message );
	$subject = (string) preg_replace_callback( '/\{first_name(?:\|([^}]*))?\}/', static fn( $m ) => $name ? $name : ( $m[1] ?? '' ), $subject );
	// Reads like a personal letter: add a greeting only when the message has none.
	if ( ! preg_match( '/^\s*(hi|hello|hey|dear|good (morning|afternoon|evening)|greetings)\b/i', $message ) ) {
		/* translators: %s: first name or "there" */
		$message = sprintf( __( 'Hi %s,', 'mine-mirth-mailer' ), $name ? $name : __( 'there', 'mine-mirth-mailer' ) ) . "\n\n" . $message;
	}
	$site   = mmm_design()['site'];
	$user   = wp_get_current_user();
	$signer = '';
	if ( $user && $user->exists() ) {
		$full   = trim( $user->first_name . ' ' . $user->last_name );
		$signer = '' !== $full ? $full : ( $user->display_name !== $user->user_login ? $user->display_name : '' );
	}
	$body   = mmm_c_text( nl2br( esc_html( $message ) ) );
	$body  .= mmm_c_text( $signer && $signer !== $site ? '<strong>' . esc_html( $signer ) . '</strong><br><span style="color:#506279">' . esc_html( $site ) . '</span>' : '<strong>' . esc_html( sprintf( /* translators: %s: site name */ __( 'The %s team', 'mine-mirth-mailer' ), $site ) ) . '</strong>' );
	$body .= mmm_c_reply( __( 'Want to reply?', 'mine-mirth-mailer' ), __( 'Just hit reply, or use the button to send us your answer.', 'mine-mirth-mailer' ), __( 'Reply', 'mine-mirth-mailer' ) );
	$html  = mmm_document( array( 'subject' => $subject, 'preheader' => wp_trim_words( $message, 18, '…' ), 'body' => $body, 'campaign' => 0, 'label' => __( 'A message for you', 'mine-mirth-mailer' ) ) );
	$html  = mmm_personalize( $html, $sub, 0 );
	[ $ok, $error ] = mmm_mail( $email, $subject, $html, mmm_plain_text( $html ), $sub, 0 );
	if ( $ok ) {
		mmm_store_message( $sub, 'out', $subject, $message, 0, 'admin' );
	}
	return array( $ok, $error );
}

add_action(
	'admin_post_mmm_message',
	static function () {
		$sub = absint( wp_unslash( $_POST['sub'] ?? 0 ) );
		check_admin_referer( 'mmm_message_' . $sub );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-mailer' ) );
		}
		$subject        = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
		$message        = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
		[ $ok, $error ] = '' !== trim( $message ) ? mmm_send_message( $sub, $subject ? $subject : __( 'A message from our team', 'mine-mirth-mailer' ), $message ) : array( false, __( 'Write a message first.', 'mine-mirth-mailer' ) );
		if ( ! $ok ) {
			update_option( 'mmm_last_message_error', $error, false );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=mmm-inbox&sub=' . $sub . '&sent=' . ( $ok ? '1' : '0' ) ) );
		exit;
	}
);

function mmm_inbox_page(): void {
	global $wpdb;
	$m   = mmm_table( 'messages' );
	$sub = absint( wp_unslash( $_GET['sub'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<div class="wrap mmm-admin"><h1>' . esc_html__( 'Inbox', 'mine-mirth-mailer' ) . '</h1>';

	if ( $sub ) {
		$wpdb->update( $m, array( 'is_read' => 1 ), array( 'sub_id' => $sub, 'direction' => 'in' ) ); // phpcs:ignore WordPress.DB
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $m WHERE sub_id = %d ORDER BY created ASC, id ASC", $sub ) ); // phpcs:ignore WordPress.DB
		$email = mmm_sub_email( $sub );
		$sent  = isset( $_GET['sent'] ) ? sanitize_key( wp_unslash( $_GET['sent'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '1' === $sent ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Your reply was sent.', 'mine-mirth-mailer' ) . '</p></div>';
		} elseif ( '0' === $sent ) {
			echo '<div class="notice notice-error"><p>' . esc_html( __( 'Your reply could not be sent:', 'mine-mirth-mailer' ) . ' ' . (string) get_option( 'mmm_last_message_error' ) ) . '</p></div>';
		}
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=mmm-inbox' ) ) . '">← ' . esc_html__( 'All conversations', 'mine-mirth-mailer' ) . '</a> · <a href="' . esc_url( admin_url( 'admin.php?page=mmm-subscribers&sub=' . $sub ) ) . '">' . esc_html__( 'Subscriber profile', 'mine-mirth-mailer' ) . '</a></p>';
		echo '<div class="mmm-card mmm-thread"><h2>' . esc_html( $email ) . '</h2>';
		if ( ! $rows ) {
			echo '<p class="description">' . esc_html__( 'No messages yet. Start the conversation below.', 'mine-mirth-mailer' ) . '</p>';
		}
		$last_subject = '';
		foreach ( (array) $rows as $r ) {
			$last_subject = (string) $r->subject;
			$who          = 'in' === $r->direction ? $email : __( 'You', 'mine-mirth-mailer' );
			$via          = array( 'form' => __( 'via the reply page', 'mine-mirth-mailer' ), 'imap' => __( 'by email', 'mine-mirth-mailer' ), 'admin' => '' )[ $r->source ] ?? '';
			$camp         = (int) $r->campaign_id ? ' · ' . sprintf( /* translators: %s: campaign */ __( 'about “%s”', 'mine-mirth-mailer' ), get_the_title( (int) $r->campaign_id ) ) : '';
			echo '<div class="mmm-msg is-' . esc_attr( $r->direction ) . '"><div class="mmm-msg__meta"><strong>' . esc_html( $who ) . '</strong> · ' . esc_html( mmm_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) strtotime( $r->created . ' UTC' ) ) ) . ( $via ? ' · ' . esc_html( $via ) : '' ) . esc_html( $camp ) . '</div>';
			echo $r->subject ? '<div class="mmm-msg__subject">' . esc_html( $r->subject ) . '</div>' : '';
			echo '<div class="mmm-msg__body">' . nl2br( esc_html( $r->body ) ) . '</div></div>';
		}
		$re = $last_subject ? ( str_starts_with( $last_subject, 'Re:' ) ? $last_subject : 'Re: ' . $last_subject ) : '';
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mmm-compose">
			<?php wp_nonce_field( 'mmm_message_' . $sub ); ?>
			<input type="hidden" name="action" value="mmm_message"><input type="hidden" name="sub" value="<?php echo esc_attr( (string) $sub ); ?>">
			<p><input type="text" name="subject" class="widefat" value="<?php echo esc_attr( $re ); ?>" placeholder="<?php esc_attr_e( 'Subject', 'mine-mirth-mailer' ); ?>"></p>
			<p><textarea name="message" rows="7" class="widefat" placeholder="<?php esc_attr_e( 'Write your reply… It is sent in your website’s email design, with a Reply button so the conversation continues here.', 'mine-mirth-mailer' ); ?>" required></textarea></p>
			<p><button class="button button-primary"><?php esc_html_e( 'Send reply', 'mine-mirth-mailer' ); ?></button></p>
		</form>
		</div>
		<?php
		echo '</div>';
		return;
	}

	$threads = $wpdb->get_results( "SELECT sub_id, MAX(created) AS last_at, SUM(CASE WHEN direction = 'in' AND is_read = 0 THEN 1 ELSE 0 END) AS unread, COUNT(*) AS total FROM $m GROUP BY sub_id ORDER BY last_at DESC LIMIT 200" ); // phpcs:ignore WordPress.DB
	if ( ! $threads ) {
		echo '<div class="mmm-card"><p>' . esc_html__( 'No replies yet. Every email has an "Ask us anything" button; answers people send land here, and you can reply in your website’s design.', 'mine-mirth-mailer' ) . '</p></div></div>';
		return;
	}
	echo '<table class="widefat striped mmm-inbox"><thead><tr><th>' . esc_html__( 'From', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'Latest message', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'When', 'mine-mirth-mailer' ) . '</th></tr></thead><tbody>';
	foreach ( $threads as $t ) {
		$last = $wpdb->get_row( $wpdb->prepare( "SELECT direction, subject, body FROM $m WHERE sub_id = %d ORDER BY created DESC, id DESC LIMIT 1", (int) $t->sub_id ) ); // phpcs:ignore WordPress.DB
		$url  = admin_url( 'admin.php?page=mmm-inbox&sub=' . (int) $t->sub_id );
		echo '<tr' . ( (int) $t->unread ? ' class="is-unread"' : '' ) . '><td><a href="' . esc_url( $url ) . '"><strong>' . esc_html( mmm_sub_email( (int) $t->sub_id ) ) . '</strong></a>' . ( (int) $t->unread ? ' <span class="mmm-badge">' . esc_html( (string) (int) $t->unread ) . '</span>' : '' ) . '</td>';
		echo '<td>' . ( 'out' === $last->direction ? '<span class="description">' . esc_html__( 'You:', 'mine-mirth-mailer' ) . '</span> ' : '' ) . '<strong>' . esc_html( (string) $last->subject ) . '</strong> · ' . esc_html( wp_trim_words( (string) $last->body, 16, '…' ) ) . '</td>';
		echo '<td>' . esc_html( human_time_diff( (int) strtotime( $t->last_at . ' UTC' ) ) ) . ' ' . esc_html__( 'ago', 'mine-mirth-mailer' ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/* ---------------------------------------------------------------------------
 * Real email replies, imported from your mailbox (optional)
 * ------------------------------------------------------------------------- */

function mmm_crypt( string $value, bool $decrypt = false ): string {
	if ( '' === $value || ! function_exists( 'openssl_encrypt' ) ) {
		return $value;
	}
	$key = hash( 'sha256', wp_salt( 'secure_auth' ), true );
	if ( $decrypt ) {
		$raw = base64_decode( $value, true );
		if ( false === $raw || strlen( $raw ) < 17 ) {
			return '';
		}
		return (string) openssl_decrypt( substr( $raw, 16 ), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, substr( $raw, 0, 16 ) );
	}
	$iv = random_bytes( 16 );
	return base64_encode( $iv . openssl_encrypt( $value, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv ) );
}

/**
 * Keep only the new part of an email reply.
 */
function mmm_strip_quoted( string $text ): string {
	$lines = preg_split( "/\r\n|\n|\r/", $text );
	$out   = array();
	foreach ( (array) $lines as $line ) {
		// Stop at the quoted original: "On … wrote:", Outlook headers or separator lines, phone signatures.
		if ( preg_match( '/^(On .+wrote:|-{2,}\s*Original Message|From:\s|Sent from my |_{8,}$)/i', trim( $line ) ) ) {
			break;
		}
		if ( str_starts_with( ltrim( $line ), '>' ) ) {
			continue;
		}
		$out[] = $line;
	}
	return trim( implode( "\n", $out ) );
}

function mmm_imap_ready(): bool {
	return function_exists( 'imap_open' ) && mmm_opt( 'imap_host' ) && mmm_opt( 'imap_user' ) && mmm_opt( 'imap_pass' );
}

/**
 * Import unread replies from subscribers.
 */
function mmm_imap_fetch(): array {
	if ( ! mmm_imap_ready() ) {
		return array( 0, __( 'Not set up, or the PHP IMAP extension is not available on this server.', 'mine-mirth-mailer' ) );
	}
	$secure = 'none' === mmm_opt( 'imap_secure' ) ? '/novalidate-cert' : '/' . sanitize_key( (string) mmm_opt( 'imap_secure' ) );
	$box    = '{' . (string) mmm_opt( 'imap_host' ) . ':' . (int) mmm_opt( 'imap_port' ) . '/imap' . $secure . '}' . (string) mmm_opt( 'imap_folder' );
	$imap   = @imap_open( $box, (string) mmm_opt( 'imap_user' ), mmm_crypt( (string) mmm_opt( 'imap_pass' ), true ), 0, 1 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( ! $imap ) {
		$err = (string) imap_last_error();
		update_option( 'mmm_imap_status', array( 'ok' => false, 'error' => $err, 'at' => time() ), false );
		return array( 0, $err );
	}
	$ids      = imap_search( $imap, 'UNSEEN SINCE "' . gmdate( 'j-M-Y', time() - 14 * DAY_IN_SECONDS ) . '"' );
	$imported = 0;
	foreach ( (array) $ids as $num ) {
		$head = imap_headerinfo( $imap, (int) $num );
		$from = isset( $head->from[0] ) ? strtolower( $head->from[0]->mailbox . '@' . $head->from[0]->host ) : '';
		$sub  = $from ? mmm_sub_by_email( $from ) : 0;
		if ( ! $sub ) {
			continue; // Leave other mail untouched and unread.
		}
		$structure = imap_fetchstructure( $imap, (int) $num );
		$section   = ( isset( $structure->parts ) && $structure->parts ) ? '1' : '1';
		$body      = imap_fetchbody( $imap, (int) $num, $section );
		$encoding  = isset( $structure->parts[0] ) ? (int) $structure->parts[0]->encoding : (int) ( $structure->encoding ?? 0 );
		if ( 3 === $encoding ) {
			$body = base64_decode( $body );
		} elseif ( 4 === $encoding ) {
			$body = quoted_printable_decode( $body );
		}
		$body    = mmm_strip_quoted( wp_strip_all_tags( (string) $body ) );
		$subject = isset( $head->subject ) ? iconv_mime_decode( (string) $head->subject, 0, 'UTF-8' ) : '';
		if ( '' !== $body ) {
			mmm_store_message( $sub, 'in', sanitize_text_field( (string) $subject ), sanitize_textarea_field( $body ), 0, 'imap' );
			++$imported;
		}
		imap_setflag_full( $imap, (string) $num, '\\Seen' );
	}
	imap_close( $imap );
	update_option( 'mmm_imap_status', array( 'ok' => true, 'error' => '', 'at' => time(), 'imported' => $imported ), false );
	return array( $imported, '' );
}
add_action( 'mmm_imap', 'mmm_imap_fetch' );
