<?php
/**
 * Campaigns: written in the block editor, then previewed, tested, sent or
 * scheduled from the "Send" screen.
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		register_post_type(
			'mmm_campaign',
			array(
				'labels'          => array(
					'name'          => __( 'Campaigns', 'mine-mirth-mailer' ),
					'singular_name' => __( 'Campaign', 'mine-mirth-mailer' ),
					'add_new'       => __( 'New email', 'mine-mirth-mailer' ),
					'add_new_item'  => __( 'New email', 'mine-mirth-mailer' ),
					'edit_item'     => __( 'Edit email', 'mine-mirth-mailer' ),
					'all_items'     => __( 'Campaigns', 'mine-mirth-mailer' ),
					'menu_name'     => __( 'Campaigns', 'mine-mirth-mailer' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'mmm',
				'show_in_rest'    => true,
				'supports'        => array( 'title', 'editor', 'revisions' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'template'        => array(
					array( 'mmm/hero', array( 'eyebrow' => 'The Miner’s Brief', 'title' => 'Hi {first_name|there}, here’s what’s new', 'text' => 'A quick look at the miners, tools and articles worth your time this week.', 'buttonText' => 'Shop miners', 'buttonUrl' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ) ),
					array( 'mmm/network' ),
					array( 'mmm/products', array( 'title' => 'Miners worth a look', 'count' => 4 ) ),
					array( 'core/paragraph', array( 'placeholder' => 'Write your message…' ) ),
					array( 'mmm/posts', array( 'title' => 'From the blog', 'count' => 2 ) ),
					array( 'mmm/reply' ),
				),
			)
		);
	}
);

/* ---------------------------------------------------------------------------
 * Email settings box in the editor
 * ------------------------------------------------------------------------- */

add_action(
	'add_meta_boxes_mmm_campaign',
	static function ( $post ) {
		if ( in_array( get_post_meta( $post->ID, '_mmm_kind', true ), array( 'weekly', 'intro' ), true ) ) {
			return;
		}
		add_meta_box( 'mmm-email', __( 'Email', 'mine-mirth-mailer' ), 'mmm_campaign_box', 'mmm_campaign', 'side', 'high' );
	}
);

function mmm_campaign_box( WP_Post $post ): void {
	wp_nonce_field( 'mmm_campaign_box', 'mmm_campaign_nonce' );
	$subject  = (string) get_post_meta( $post->ID, '_mmm_subject', true );
	$pre      = (string) get_post_meta( $post->ID, '_mmm_preheader', true );
	$audience = (string) get_post_meta( $post->ID, '_mmm_audience', true );
	$state    = (string) get_post_meta( $post->ID, '_mmm_state', true );
	?>
	<p><label for="mmm-subject"><strong><?php esc_html_e( 'Subject line', 'mine-mirth-mailer' ); ?></strong></label><br>
	<input id="mmm-subject" type="text" class="widefat" name="mmm_subject" value="<?php echo esc_attr( $subject ); ?>" placeholder="<?php esc_attr_e( 'Uses the title if empty', 'mine-mirth-mailer' ); ?>"></p>
	<p><label for="mmm-pre"><strong><?php esc_html_e( 'Preview text', 'mine-mirth-mailer' ); ?></strong></label><br>
	<input id="mmm-pre" type="text" class="widefat" name="mmm_preheader" value="<?php echo esc_attr( $pre ); ?>" placeholder="<?php esc_attr_e( 'Shown next to the subject in the inbox', 'mine-mirth-mailer' ); ?>"></p>
	<p><label for="mmm-aud"><strong><?php esc_html_e( 'Send to', 'mine-mirth-mailer' ); ?></strong></label><br>
	<select id="mmm-aud" name="mmm_audience" class="widefat">
		<?php foreach ( mmm_audiences() as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $audience ? $audience : 'all', $key ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select></p>
	<p style="color:#646970;font-size:12px"><?php esc_html_e( 'Use {first_name|there} in the title or text to greet people by name.', 'mine-mirth-mailer' ); ?></p>
	<p><a class="button" target="_blank" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mmm_preview&id=' . $post->ID ), 'mmm_preview_' . $post->ID ) ); ?>"><?php esc_html_e( 'Preview email', 'mine-mirth-mailer' ); ?></a>
	<?php if ( in_array( $state, array( 'sending', 'sent' ), true ) ) : ?>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=mmm-send&id=' . $post->ID ) ); ?>"><?php esc_html_e( 'Results', 'mine-mirth-mailer' ); ?></a></p>
	<?php else : ?>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=mmm-send&id=' . $post->ID ) ); ?>"><?php esc_html_e( 'Test & send…', 'mine-mirth-mailer' ); ?></a></p>
		<p style="color:#646970;font-size:12px"><?php esc_html_e( 'Save your changes first.', 'mine-mirth-mailer' ); ?></p>
	<?php endif; ?>
	<?php
}

add_action(
	'save_post_mmm_campaign',
	static function ( $post_id ) {
		if ( ! isset( $_POST['mmm_campaign_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mmm_campaign_nonce'] ) ), 'mmm_campaign_box' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_mmm_subject', sanitize_text_field( wp_unslash( $_POST['mmm_subject'] ?? '' ) ) );
		update_post_meta( $post_id, '_mmm_preheader', sanitize_text_field( wp_unslash( $_POST['mmm_preheader'] ?? '' ) ) );
		$aud = sanitize_key( wp_unslash( $_POST['mmm_audience'] ?? 'all' ) );
		update_post_meta( $post_id, '_mmm_audience', isset( mmm_audiences()[ $aud ] ) ? $aud : 'all' );
	}
);

/* ---------------------------------------------------------------------------
 * Campaign list
 * ------------------------------------------------------------------------- */

function mmm_state_label( int $id ): string {
	$state = (string) get_post_meta( $id, '_mmm_state', true );
	if ( 'scheduled' === $state ) {
		/* translators: %s: date */
		return sprintf( __( 'Scheduled for %s', 'mine-mirth-mailer' ), mmm_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) get_post_meta( $id, '_mmm_scheduled', true ) ) );
	}
	return array( 'sending' => __( 'Sending…', 'mine-mirth-mailer' ), 'sent' => __( 'Sent', 'mine-mirth-mailer' ) )[ $state ] ?? __( 'Draft', 'mine-mirth-mailer' );
}

add_filter(
	'manage_mmm_campaign_posts_columns',
	static fn( $cols ) => array(
		'cb'       => $cols['cb'] ?? '',
		'title'    => __( 'Email', 'mine-mirth-mailer' ),
		'mmm_type' => __( 'Type', 'mine-mirth-mailer' ),
		'mmm_stat' => __( 'Status', 'mine-mirth-mailer' ),
		'mmm_sent' => __( 'Sent', 'mine-mirth-mailer' ),
		'mmm_open' => __( 'Opened', 'mine-mirth-mailer' ),
		'mmm_clk'  => __( 'Clicked', 'mine-mirth-mailer' ),
		'mmm_rep'  => __( 'Replies', 'mine-mirth-mailer' ),
		'date'     => __( 'Date', 'mine-mirth-mailer' ),
	)
);

add_action(
	'manage_mmm_campaign_posts_custom_column',
	static function ( $col, $id ) {
		static $stats = array();
		if ( ! isset( $stats[ $id ] ) && in_array( $col, array( 'mmm_sent', 'mmm_open', 'mmm_clk', 'mmm_rep' ), true ) ) {
			$stats[ $id ] = mmm_campaign_stats( (int) $id );
		}
		$s   = $stats[ $id ] ?? array();
		$pct = static fn( $n ) => ! empty( $s['sent'] ) ? ' <span style="color:#646970">(' . round( 100 * $n / $s['sent'] ) . '%)</span>' : '';
		switch ( $col ) {
			case 'mmm_type':
				$kind = mmm_campaign_kind( (int) $id );
				echo 'weekly' === $kind ? esc_html__( 'Weekly (personalised)', 'mine-mirth-mailer' ) : ( 'intro' === $kind ? esc_html__( 'Introduction (automatic)', 'mine-mirth-mailer' ) : esc_html__( 'Campaign', 'mine-mirth-mailer' ) );
				break;
			case 'mmm_stat':
				echo esc_html( mmm_state_label( (int) $id ) );
				break;
			case 'mmm_sent':
				echo esc_html( number_format_i18n( $s['sent'] ) ) . ( $s['failed'] ? ' <span style="color:#d63638">· ' . esc_html( sprintf( /* translators: %d: count */ __( '%d failed', 'mine-mirth-mailer' ), $s['failed'] ) ) . '</span>' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'mmm_open':
				echo esc_html( number_format_i18n( $s['opens'] ) ) . $pct( $s['opens'] ); // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'mmm_clk':
				echo esc_html( number_format_i18n( $s['clickers'] ) ) . $pct( $s['clickers'] ); // phpcs:ignore WordPress.Security.EscapeOutput
				break;
			case 'mmm_rep':
				echo esc_html( number_format_i18n( $s['replies'] ) );
				break;
		}
	},
	10,
	2
);

add_filter(
	'post_row_actions',
	static function ( $actions, $post ) {
		if ( 'mmm_campaign' === $post->post_type ) {
			$state            = (string) get_post_meta( $post->ID, '_mmm_state', true );
			$actions['mmm_s'] = '<a href="' . esc_url( admin_url( 'admin.php?page=mmm-send&id=' . $post->ID ) ) . '">' . ( in_array( $state, array( 'sending', 'sent' ), true ) ? esc_html__( 'Results', 'mine-mirth-mailer' ) : esc_html__( 'Test & send', 'mine-mirth-mailer' ) ) . '</a>';
			$actions['mmm_p'] = '<a target="_blank" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mmm_preview&id=' . $post->ID ), 'mmm_preview_' . $post->ID ) ) . '">' . esc_html__( 'Preview', 'mine-mirth-mailer' ) . '</a>';
			$actions['mmm_d'] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mmm_duplicate&id=' . $post->ID ), 'mmm_duplicate_' . $post->ID ) ) . '">' . esc_html__( 'Duplicate', 'mine-mirth-mailer' ) . '</a>';
		}
		return $actions;
	},
	10,
	2
);

/* ---------------------------------------------------------------------------
 * Preview, duplicate, test, send, schedule
 * ------------------------------------------------------------------------- */

/**
 * A subscriber to preview with: one chosen, the admin if subscribed, or the newest.
 */
function mmm_preview_sub( int $wanted = 0 ): int {
	if ( $wanted && 'mm_subscriber' === get_post_type( $wanted ) ) {
		return $wanted;
	}
	$me = mmm_sub_by_email( (string) wp_get_current_user()->user_email );
	if ( $me ) {
		return $me;
	}
	$ids = get_posts( array( 'post_type' => 'mm_subscriber', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids' ) );
	return $ids ? (int) $ids[0] : 0;
}

add_action(
	'admin_post_mmm_preview',
	static function () {
		$id = absint( wp_unslash( $_GET['id'] ?? 0 ) );
		check_admin_referer( 'mmm_preview_' . $id );
		if ( ! current_user_can( 'edit_post', $id ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-mailer' ) );
		}
		$sub = mmm_preview_sub( absint( wp_unslash( $_GET['sub'] ?? 0 ) ) );
		if ( ! $sub ) {
			wp_die( esc_html__( 'Add at least one subscriber to preview the email.', 'mine-mirth-mailer' ) );
		}
		$mail = mmm_compose( $id, $sub );
		header( 'Content-Type: text/html; charset=utf-8' );
		// Preview must not count as an open.
		echo str_replace( esc_url( mmm_link( 'o', $id, mmm_sub_token( $sub ) ) ), esc_url( includes_url( 'images/blank.gif' ) ), (string) ( $mail['html'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}
);

add_action(
	'admin_post_mmm_duplicate',
	static function () {
		$id = absint( wp_unslash( $_GET['id'] ?? 0 ) );
		check_admin_referer( 'mmm_duplicate_' . $id );
		if ( ! current_user_can( 'edit_post', $id ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-mailer' ) );
		}
		$src = get_post( $id );
		$new = wp_insert_post( wp_slash( array( 'post_type' => 'mmm_campaign', 'post_status' => 'draft', 'post_title' => $src->post_title . ' ' . __( '(copy)', 'mine-mirth-mailer' ), 'post_content' => $src->post_content ) ) );
		foreach ( array( '_mmm_subject', '_mmm_preheader', '_mmm_audience' ) as $k ) {
			update_post_meta( $new, $k, get_post_meta( $id, $k, true ) );
		}
		wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new ) );
		exit;
	}
);

add_action(
	'admin_post_mmm_send',
	static function () {
		$id = absint( wp_unslash( $_POST['id'] ?? 0 ) );
		check_admin_referer( 'mmm_send_' . $id );
		if ( ! current_user_can( 'edit_post', $id ) || 'mmm_campaign' !== get_post_type( $id ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-mailer' ) );
		}
		$do  = sanitize_key( wp_unslash( $_POST['do'] ?? '' ) );
		// Only the send form carries an audience; a test send must not change the saved one.
		$aud = isset( $_POST['audience'] ) ? sanitize_key( wp_unslash( $_POST['audience'] ) ) : (string) get_post_meta( $id, '_mmm_audience', true );
		$aud = isset( mmm_audiences()[ $aud ] ) ? $aud : 'all';
		if ( isset( $_POST['audience'] ) ) {
			update_post_meta( $id, '_mmm_audience', $aud );
		}
		$msg = '';
		if ( 'test' === $do ) {
			$to  = sanitize_email( wp_unslash( $_POST['test_to'] ?? '' ) );
			$sub = mmm_preview_sub( absint( wp_unslash( $_POST['as_sub'] ?? 0 ) ) );
			if ( is_email( $to ) && $sub ) {
				$mail           = mmm_compose( $id, $sub );
				[ $ok, $error ] = mmm_mail( $to, '[TEST] ' . $mail['subject'], $mail['html'], $mail['text'] );
				$msg            = $ok ? 'test_ok' : 'test_fail';
				update_option( 'mmm_last_test_error', $error, false );
			} else {
				$msg = 'test_fail';
				update_option( 'mmm_last_test_error', __( 'Enter a valid email address, and add at least one subscriber.', 'mine-mirth-mailer' ), false );
			}
		} elseif ( 'now' === $do ) {
			if ( 'publish' !== get_post_status( $id ) ) {
				wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
			}
			$n   = mmm_queue_campaign( $id, mmm_audience_ids( $aud ) );
			$msg = $n ? 'queued' : 'nobody';
		} elseif ( 'schedule' === $do ) {
			$when = strtotime( sanitize_text_field( wp_unslash( $_POST['when'] ?? '' ) ) . ' ' . mmm_tz()->getName() );
			if ( $when && $when > time() ) {
				wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
				update_post_meta( $id, '_mmm_state', 'scheduled' );
				update_post_meta( $id, '_mmm_scheduled', $when );
				$msg = 'scheduled';
			} else {
				$msg = 'bad_time';
			}
		} elseif ( 'unschedule' === $do ) {
			delete_post_meta( $id, '_mmm_state' );
			delete_post_meta( $id, '_mmm_scheduled' );
			$msg = 'unscheduled';
		}
		wp_safe_redirect( admin_url( 'admin.php?page=mmm-send&id=' . $id . '&msg=' . $msg ) );
		exit;
	}
);

/**
 * The Test & send / Results screen.
 */
function mmm_send_page(): void {
	$id = absint( wp_unslash( $_GET['id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $id || 'mmm_campaign' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
		echo '<div class="wrap"><p>' . esc_html__( 'Choose a campaign first.', 'mine-mirth-mailer' ) . '</p></div>';
		return;
	}
	$state    = (string) get_post_meta( $id, '_mmm_state', true );
	$audience = (string) get_post_meta( $id, '_mmm_audience', true ) ?: 'all';
	$msg      = sanitize_key( wp_unslash( $_GET['msg'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$stats    = mmm_campaign_stats( $id );
	$weekly   = mmm_is_auto( $id );
	$messages = array(
		'test_ok'     => array( 'success', __( 'Test email sent. Check your inbox (and spam folder).', 'mine-mirth-mailer' ) ),
		'test_fail'   => array( 'error', __( 'The test email could not be sent:', 'mine-mirth-mailer' ) . ' ' . (string) get_option( 'mmm_last_test_error' ) ),
		'queued'      => array( 'success', __( 'Sending has started. Emails go out in batches every minute; this page shows progress.', 'mine-mirth-mailer' ) ),
		'nobody'      => array( 'warning', __( 'Nobody matched that audience, so nothing was sent.', 'mine-mirth-mailer' ) ),
		'scheduled'   => array( 'success', __( 'Scheduled.', 'mine-mirth-mailer' ) ),
		'unscheduled' => array( 'success', __( 'The schedule was cancelled. The email is a draft again.', 'mine-mirth-mailer' ) ),
		'bad_time'    => array( 'error', __( 'Pick a date and time in the future.', 'mine-mirth-mailer' ) ),
	);
	$preview = wp_nonce_url( admin_url( 'admin-post.php?action=mmm_preview&id=' . $id ), 'mmm_preview_' . $id );
	?>
	<div class="wrap mmm-admin">
		<h1><?php echo esc_html( get_the_title( $id ) ); ?> <span class="mmm-pill"><?php echo esc_html( mmm_state_label( $id ) ); ?></span></h1>
		<?php if ( isset( $messages[ $msg ] ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( $messages[ $msg ][0] ); ?> is-dismissible"><p><?php echo esc_html( $messages[ $msg ][1] ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! trim( (string) mmm_opt( 'address' ) ) ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Add your business postal address in Mailer › Settings. Marketing emails must include one by law in the US (CAN-SPAM) and it helps them reach the inbox.', 'mine-mirth-mailer' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=mmm-settings' ) ); ?>"><?php esc_html_e( 'Open settings', 'mine-mirth-mailer' ); ?></a></p></div>
		<?php endif; ?>

		<div class="mmm-grid">
			<div class="mmm-card">
				<h2><?php esc_html_e( 'Preview', 'mine-mirth-mailer' ); ?></h2>
				<iframe class="mmm-frame" src="<?php echo esc_url( $preview ); ?>" title="<?php esc_attr_e( 'Email preview', 'mine-mirth-mailer' ); ?>"></iframe>
				<p><a target="_blank" href="<?php echo esc_url( $preview ); ?>"><?php esc_html_e( 'Open in a new tab', 'mine-mirth-mailer' ); ?></a><?php if ( ! $weekly ) : ?> · <a href="<?php echo esc_url( (string) get_edit_post_link( $id ) ); ?>"><?php esc_html_e( 'Edit email', 'mine-mirth-mailer' ); ?></a><?php endif; ?></p>
			</div>

			<div>
				<?php if ( $stats['recipients'] ) : ?>
					<div class="mmm-card">
						<h2><?php esc_html_e( 'Results', 'mine-mirth-mailer' ); ?></h2>
						<div class="mmm-stats">
							<?php
							$pct = static fn( $n ) => $stats['sent'] ? round( 100 * $n / $stats['sent'] ) . '%' : '—';
							foreach ( array(
								array( __( 'Sent', 'mine-mirth-mailer' ), number_format_i18n( $stats['sent'] ), $stats['pending'] ? sprintf( /* translators: %d: count */ __( '%d still to send', 'mine-mirth-mailer' ), $stats['pending'] ) : ( $stats['failed'] ? sprintf( /* translators: %d: count */ __( '%d failed', 'mine-mirth-mailer' ), $stats['failed'] ) : '' ) ),
								array( __( 'Opened', 'mine-mirth-mailer' ), $pct( $stats['opens'] ), sprintf( /* translators: %s: count */ _n( '%s person', '%s people', $stats['opens'], 'mine-mirth-mailer' ), number_format_i18n( $stats['opens'] ) ) ),
								array( __( 'Clicked', 'mine-mirth-mailer' ), $pct( $stats['clickers'] ), sprintf( /* translators: %s: count */ _n( '%s click', '%s clicks', $stats['clicks'], 'mine-mirth-mailer' ), number_format_i18n( $stats['clicks'] ) ) ),
								array( __( 'Replies', 'mine-mirth-mailer' ), number_format_i18n( $stats['replies'] ), $stats['unsubs'] ? sprintf( /* translators: %d: count */ __( '%d unsubscribed', 'mine-mirth-mailer' ), $stats['unsubs'] ) : '' ),
							) as $s ) :
								?>
								<div><small><?php echo esc_html( $s[0] ); ?></small><strong><?php echo esc_html( (string) $s[1] ); ?></strong><span><?php echo esc_html( (string) $s[2] ); ?></span></div>
							<?php endforeach; ?>
						</div>
						<?php mmm_recipient_table( $id ); ?>
					</div>
				<?php endif; ?>

				<?php if ( ! in_array( $state, array( 'sending', 'sent' ), true ) && ! $weekly ) : ?>
					<div class="mmm-card">
						<h2><?php esc_html_e( '1. Send yourself a test', 'mine-mirth-mailer' ); ?></h2>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'mmm_send_' . $id ); ?>
							<input type="hidden" name="action" value="mmm_send"><input type="hidden" name="id" value="<?php echo esc_attr( (string) $id ); ?>"><input type="hidden" name="do" value="test">
							<input type="email" name="test_to" class="regular-text" value="<?php echo esc_attr( (string) wp_get_current_user()->user_email ); ?>" required>
							<button class="button"><?php esc_html_e( 'Send test', 'mine-mirth-mailer' ); ?></button>
						</form>
					</div>
					<div class="mmm-card">
						<h2><?php esc_html_e( '2. Send it', 'mine-mirth-mailer' ); ?></h2>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return this.querySelector('[name=do]:checked') && this.querySelector('[name=do]:checked').value==='schedule' ? true : confirm('<?php echo esc_js( __( 'Send this email now?', 'mine-mirth-mailer' ) ); ?>')">
							<?php wp_nonce_field( 'mmm_send_' . $id ); ?>
							<input type="hidden" name="action" value="mmm_send"><input type="hidden" name="id" value="<?php echo esc_attr( (string) $id ); ?>">
							<p><label for="mmm-audience"><strong><?php esc_html_e( 'Send to', 'mine-mirth-mailer' ); ?></strong></label><br>
							<select id="mmm-audience" name="audience">
								<?php foreach ( mmm_audiences() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $audience, $key ); ?>><?php echo esc_html( $label . ' (' . number_format_i18n( count( mmm_audience_ids( $key ) ) ) . ')' ); ?></option>
								<?php endforeach; ?>
							</select></p>
							<p><label><input type="radio" name="do" value="now" checked> <?php esc_html_e( 'Send now', 'mine-mirth-mailer' ); ?></label><br>
							<label><input type="radio" name="do" value="schedule"> <?php esc_html_e( 'Schedule for', 'mine-mirth-mailer' ); ?></label>
							<input type="datetime-local" name="when" value="<?php echo esc_attr( mmm_date( 'Y-m-d\TH:i', time() + DAY_IN_SECONDS ) ); ?>"> <span class="description"><?php echo esc_html( wp_timezone_string() ); ?></span></p>
							<p><button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Send', 'mine-mirth-mailer' ); ?></button></p>
						</form>
					</div>
				<?php elseif ( 'scheduled' === $state ) : ?>
					<div class="mmm-card">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'mmm_send_' . $id ); ?>
							<input type="hidden" name="action" value="mmm_send"><input type="hidden" name="id" value="<?php echo esc_attr( (string) $id ); ?>"><input type="hidden" name="do" value="unschedule">
							<button class="button"><?php esc_html_e( 'Cancel the schedule', 'mine-mirth-mailer' ); ?></button>
						</form>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}

function mmm_recipient_table( int $campaign ): void {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT sub_id, email, status, error FROM ' . mmm_table( 'queue' ) . ' WHERE campaign_id = %d ORDER BY id ASC LIMIT 200', $campaign ) ); // phpcs:ignore WordPress.DB
	if ( ! $rows ) {
		return;
	}
	$e     = mmm_table( 'events' );
	$opens = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT sub_id FROM $e WHERE campaign_id = %d AND type = 'open'", $campaign ) ) ); // phpcs:ignore WordPress.DB
	$click = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT sub_id FROM $e WHERE campaign_id = %d AND type = 'click'", $campaign ) ) ); // phpcs:ignore WordPress.DB
	echo '<details class="mmm-recipients"><summary>' . esc_html__( 'Recipients', 'mine-mirth-mailer' ) . '</summary><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Email', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'Status', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'Opened', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'Clicked', 'mine-mirth-mailer' ) . '</th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		$status = array( 'sent' => __( 'Sent', 'mine-mirth-mailer' ), 'failed' => __( 'Failed', 'mine-mirth-mailer' ), 'skipped' => __( 'Skipped', 'mine-mirth-mailer' ) )[ $r->status ] ?? __( 'Waiting', 'mine-mirth-mailer' );
		echo '<tr><td><a href="' . esc_url( admin_url( 'admin.php?page=mmm-subscribers&sub=' . (int) $r->sub_id ) ) . '">' . esc_html( $r->email ) . '</a></td><td>' . esc_html( $status ) . ( $r->error ? ' <span style="color:#d63638">— ' . esc_html( $r->error ) . '</span>' : '' ) . '</td><td>' . ( in_array( (int) $r->sub_id, $opens, true ) ? '✓' : '' ) . '</td><td>' . ( in_array( (int) $r->sub_id, $click, true ) ? '✓' : '' ) . '</td></tr>';
	}
	echo '</tbody></table></details>';
}
