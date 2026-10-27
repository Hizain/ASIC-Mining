<?php
/**
 * Welcome email: sent once to everyone who subscribes on the website, in the site's design, with
 * today's network numbers, the free tools, starter guides and popular miners.
 *
 * It replaces the plain-text welcome that the Mine Mirth Newsletter Alerts plugin can send, so a new
 * subscriber never gets two welcomes. People you add by hand get the introduction email instead.
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

function mmm_welcome_defaults(): array {
	return array(
		'enabled'    => 1,
		'subject'    => __( 'Welcome to The Miner’s Brief', 'mine-mirth-mailer' ),
		'preheader'  => __( 'Your weekly mining numbers, the free tools we use and where to start today.', 'mine-mirth-mailer' ),
		'eyebrow'    => __( 'Welcome aboard', 'mine-mirth-mailer' ),
		'headline'   => __( 'You’re in. Here’s what you’ll get each week.', 'mine-mirth-mailer' ),
		'intro'      => __( 'Thanks for subscribing. Every week we send the numbers that moved (hashprice, difficulty and the halving countdown), the miners worth watching in our shop and one practical tip for mining profitably.', 'mine-mirth-mailer' ),
		'button'     => __( 'Calculate my mining profit', 'mine-mirth-mailer' ),
		'expect'     => __( "The network numbers that moved, explained in plain words\nNew miners, stock and price changes in our shop\nOne practical tip for running miners profitably", 'mine-mirth-mailer' ),
		'network'    => 1,
		'tools'      => 1,
		'guides'     => 3,
		'products'   => 2,
		'cta_title'  => __( 'Planning your first miner or a bigger setup?', 'mine-mirth-mailer' ),
		'cta_text'   => __( 'Reply with your power price and what you want to mine. A real person will come back with options that fit.', 'mine-mirth-mailer' ),
		'cta_button' => __( 'Ask us anything', 'mine-mirth-mailer' ),
		'signoff'    => __( 'Happy mining,', 'mine-mirth-mailer' ),
		'signer'     => '',
	);
}

function mmm_welcome( string $key ) {
	$saved = get_option( 'mmm_welcome', array() );
	$all   = array_merge( mmm_welcome_defaults(), is_array( $saved ) ? $saved : array() );
	return $all[ $key ] ?? null;
}

/**
 * The one campaign all welcome emails belong to, so their opens and clicks add up.
 */
function mmm_welcome_campaign(): int {
	$id = (int) get_option( 'mmm_welcome_campaign' );
	if ( $id && 'mmm_campaign' === get_post_type( $id ) ) {
		return $id;
	}
	$id = (int) wp_insert_post(
		array(
			'post_type'   => 'mmm_campaign',
			'post_status' => 'publish',
			'post_title'  => __( 'Welcome email (website sign-ups)', 'mine-mirth-mailer' ),
		)
	);
	if ( $id ) {
		update_post_meta( $id, '_mmm_kind', 'welcome' );
		update_post_meta( $id, '_mmm_state', 'sent' );
		update_option( 'mmm_welcome_campaign', $id, false );
	}
	return $id;
}

function mmm_welcome_was_sent( int $sub ): bool {
	global $wpdb;
	$campaign = (int) get_option( 'mmm_welcome_campaign' );
	return $campaign && (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT 1 FROM ' . mmm_table( 'queue' ) . ' WHERE campaign_id = %d AND sub_id = %d LIMIT 1', $campaign, $sub ) ); // phpcs:ignore WordPress.DB
}

// A new website sign-up gets the welcome email straight away (sent at the end of the request).
add_action(
	'mm_new_subscriber',
	static function ( $email, $id ) {
		$id = (int) $id;
		if ( ! $id || ! mmm_welcome( 'enabled' ) || 'manual' === get_post_meta( $id, '_mm_context', true ) || ! mmm_sub_is_active( $id ) || mmm_welcome_was_sent( $id ) ) {
			return;
		}
		$campaign = mmm_welcome_campaign();
		if ( $campaign ) {
			mmm_queue_campaign( $campaign, array( $id ) );
			mmm_send_soon( true );
		}
	},
	20,
	2
);

// One welcome only: while this one is on, the plain-text welcome of Newsletter Alerts stays off.
add_filter(
	'option_mmna_settings',
	static function ( $value ) {
		if ( mmm_welcome( 'enabled' ) && is_array( $value ) ) {
			$value['welcome'] = 0;
		}
		return $value;
	}
);

/**
 * Subject, preheader and body of the welcome email for one subscriber.
 *
 * @return array{subject:string,preheader:string,body:string,label:string,focus:string}
 */
function mmm_welcome_compose( int $sub ): array {
	$signer = trim( (string) mmm_welcome( 'signer' ) );
	$signer = '' !== $signer ? $signer : sprintf( /* translators: %s: site name */ __( 'The %s team', 'mine-mirth-mailer' ), mmm_design()['site'] );
	$expect = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) mmm_welcome( 'expect' ) ) ) ) );

	$body  = mmm_c_text( esc_html__( 'Hi {first_name|there},', 'mine-mirth-mailer' ), 'left', 17 );
	$body .= mmm_c_hero( (string) mmm_welcome( 'eyebrow' ), (string) mmm_welcome( 'headline' ), esc_html( (string) mmm_welcome( 'intro' ) ), (string) mmm_welcome( 'button' ), mmm_tool_link( 'profit' ) );
	if ( $expect ) {
		$body .= mmm_c_heading( __( 'What lands in your inbox', 'mine-mirth-mailer' ), 3 );
		$body .= mmm_c_list( array_map( 'esc_html', $expect ) );
	}
	if ( mmm_welcome( 'network' ) ) {
		$body .= mmm_c_network();
	}
	if ( mmm_welcome( 'tools' ) ) {
		$body .= mmm_c_tools( array( 'profit', 'hashprice', 'breakeven', 'hosting' ), __( 'Free tools on today’s numbers', 'mine-mirth-mailer' ), __( 'Start here', 'mine-mirth-mailer' ) );
	}
	if ( (int) mmm_welcome( 'guides' ) > 0 ) {
		$body .= mmm_c_posts( mmm_guide_ids( (int) mmm_welcome( 'guides' ), array( 'g-beginners', 'g-choose', 'g-profit', 'g-hashprice' ) ), __( 'Guides to get you started', 'mine-mirth-mailer' ), __( 'Learn', 'mine-mirth-mailer' ) );
	}
	if ( (int) mmm_welcome( 'products' ) > 0 ) {
		$body .= mmm_c_products( mmm_popular_miner_ids( (int) mmm_welcome( 'products' ) ), __( 'Popular with miners right now', 'mine-mirth-mailer' ), __( 'In stock', 'mine-mirth-mailer' ) );
	}
	$body .= mmm_c_reply( (string) mmm_welcome( 'cta_title' ), (string) mmm_welcome( 'cta_text' ), (string) mmm_welcome( 'cta_button' ), 'question' );
	$body .= mmm_c_text( esc_html( (string) mmm_welcome( 'signoff' ) ) . '<br><strong>' . esc_html( $signer ) . '</strong>' );

	return array(
		'subject'   => (string) mmm_welcome( 'subject' ),
		'preheader' => (string) mmm_welcome( 'preheader' ),
		'body'      => $body,
		'label'     => __( 'Welcome', 'mine-mirth-mailer' ),
		'focus'     => 'welcome',
	);
}

add_action(
	'admin_init',
	static function () {
		register_setting(
			'mmm_welcome',
			'mmm_welcome',
			array(
				'sanitize_callback' => static function ( $in ) {
					$in  = is_array( $in ) ? $in : array();
					$out = array();
					foreach ( mmm_welcome_defaults() as $k => $def ) {
						if ( in_array( $k, array( 'enabled', 'network', 'tools' ), true ) ) {
							$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
						} elseif ( in_array( $k, array( 'products', 'guides' ), true ) ) {
							$out[ $k ] = max( 0, min( 4, (int) ( $in[ $k ] ?? $def ) ) );
						} elseif ( in_array( $k, array( 'intro', 'expect', 'cta_text' ), true ) ) {
							$out[ $k ] = sanitize_textarea_field( wp_unslash( $in[ $k ] ?? $def ) );
						} else {
							$out[ $k ] = sanitize_text_field( wp_unslash( $in[ $k ] ?? $def ) );
						}
					}
					return $out;
				},
			)
		);
	}
);

function mmm_welcome_page(): void {
	$campaign = mmm_welcome_campaign();
	$stats    = $campaign ? mmm_campaign_stats( $campaign ) : array();
	$preview  = $campaign ? wp_nonce_url( admin_url( 'admin-post.php?action=mmm_preview&id=' . $campaign ), 'mmm_preview_' . $campaign ) : '';
	$f        = static fn( $k ) => mmm_welcome( $k );
	$name     = static fn( $k ) => 'mmm_welcome[' . $k . ']';
	$mmna     = function_exists( 'mmna_opt' );
	?>
	<div class="wrap mmm-admin">
		<h1 class="mmm-title"><?php esc_html_e( 'Welcome email', 'mine-mirth-mailer' ); ?></h1>
		<p class="mmm-lead"><?php esc_html_e( 'Sent once, right away, to everyone who subscribes on your website (footer, homepage, articles, price alerts). It uses your website’s design and shows today’s network numbers, your free tools, starter guides and popular miners. People you add by hand get the introduction email instead.', 'mine-mirth-mailer' ); ?></p>
		<?php
		$mmm_test = sanitize_key( wp_unslash( $_GET['test'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'test_ok' === $mmm_test ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Test email sent. Check your inbox (and the spam folder).', 'mine-mirth-mailer' ) . '</p></div>';
		} elseif ( 'test_fail' === $mmm_test ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The test email could not be sent:', 'mine-mirth-mailer' ) . ' ' . esc_html( (string) get_option( 'mmm_last_test_error' ) ) . '</p></div>';
		}
		?>
		<?php if ( isset( $_GET['settings-updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved. The preview shows the new version.', 'mine-mirth-mailer' ); ?></p></div>
		<?php endif; ?>
		<?php if ( $mmna && $f( 'enabled' ) ) : ?>
			<div class="notice notice-info"><p><?php esc_html_e( 'Mine Mirth Newsletter Alerts is active: its plain-text welcome is switched off while this one is on, so new subscribers get one welcome. Its sign-up alerts to you keep working.', 'mine-mirth-mailer' ); ?></p></div>
		<?php endif; ?>

		<?php if ( $stats && $stats['recipients'] ) : ?>
			<div class="mmm-stats">
				<?php
				$pct = static fn( $n ) => $stats['sent'] ? round( 100 * $n / $stats['sent'] ) . '%' : '0%';
				foreach ( array(
					array( __( 'Sent', 'mine-mirth-mailer' ), number_format_i18n( $stats['sent'] ), $stats['pending'] ? sprintf( /* translators: %d: count */ __( '%d on the way', 'mine-mirth-mailer' ), $stats['pending'] ) : '' ),
					array( __( 'Opened', 'mine-mirth-mailer' ), $pct( $stats['opens'] ), '' ),
					array( __( 'Clicked', 'mine-mirth-mailer' ), $pct( $stats['clickers'] ), '' ),
					array( __( 'Replies', 'mine-mirth-mailer' ), number_format_i18n( $stats['replies'] ), '' ),
				) as $s ) :
					?>
					<div><small><?php echo esc_html( $s[0] ); ?></small><strong><?php echo esc_html( (string) $s[1] ); ?></strong><span><?php echo esc_html( (string) $s[2] ); ?></span></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="mmm-grid mmm-grid--preview">
			<form class="mmm-card" method="post" action="options.php">
				<?php settings_fields( 'mmm_welcome' ); ?>
				<p><label class="mmm-switch"><input type="checkbox" name="<?php echo esc_attr( $name( 'enabled' ) ); ?>" value="1" <?php checked( (int) $f( 'enabled' ), 1 ); ?>> <strong><?php esc_html_e( 'Send the welcome email to new website subscribers', 'mine-mirth-mailer' ); ?></strong></label></p>
				<div class="mmm-fields">
					<label class="is-wide"><?php esc_html_e( 'Subject', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'subject' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'subject' ) ); ?>"></label>
					<label class="is-wide"><?php esc_html_e( 'Preview text (shown after the subject in the inbox)', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'preheader' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'preheader' ) ); ?>"></label>
					<label><?php esc_html_e( 'Small label', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'eyebrow' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'eyebrow' ) ); ?>"></label>
					<label><?php esc_html_e( 'Button (opens the profit calculator)', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'button' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'button' ) ); ?>"></label>
					<label class="is-wide"><?php esc_html_e( 'Headline', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'headline' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'headline' ) ); ?>"></label>
					<label class="is-wide"><?php esc_html_e( 'Opening text', 'mine-mirth-mailer' ); ?><textarea rows="3" name="<?php echo esc_attr( $name( 'intro' ) ); ?>"><?php echo esc_textarea( (string) $f( 'intro' ) ); ?></textarea></label>
					<label class="is-wide"><?php esc_html_e( 'What they will get (one point per line)', 'mine-mirth-mailer' ); ?><textarea rows="3" name="<?php echo esc_attr( $name( 'expect' ) ); ?>"><?php echo esc_textarea( (string) $f( 'expect' ) ); ?></textarea></label>
				</div>
				<p class="description"><?php esc_html_e( 'Use {first_name} or {first_name|fallback} anywhere.', 'mine-mirth-mailer' ); ?></p>

				<h2><?php esc_html_e( 'Also include', 'mine-mirth-mailer' ); ?></h2>
				<p>
					<label><input type="checkbox" name="<?php echo esc_attr( $name( 'network' ) ); ?>" value="1" <?php checked( (int) $f( 'network' ), 1 ); ?>> <?php esc_html_e( 'Live Bitcoin network numbers', 'mine-mirth-mailer' ); ?></label><br>
					<label><input type="checkbox" name="<?php echo esc_attr( $name( 'tools' ) ); ?>" value="1" <?php checked( (int) $f( 'tools' ), 1 ); ?>> <?php esc_html_e( 'Free tools (profit, hashprice, break-even power, hosting vs home)', 'mine-mirth-mailer' ); ?></label><br>
					<label><?php esc_html_e( 'Starter guides', 'mine-mirth-mailer' ); ?> <input type="number" min="0" max="4" class="small-text" name="<?php echo esc_attr( $name( 'guides' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'guides' ) ); ?>"></label> &nbsp;
					<label><?php esc_html_e( 'Popular miners', 'mine-mirth-mailer' ); ?> <input type="number" min="0" max="4" class="small-text" name="<?php echo esc_attr( $name( 'products' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'products' ) ); ?>"></label>
				</p>

				<h2><?php esc_html_e( 'Closing', 'mine-mirth-mailer' ); ?></h2>
				<div class="mmm-fields">
					<label class="is-wide"><?php esc_html_e( 'Reply box headline', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'cta_title' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'cta_title' ) ); ?>"></label>
					<label class="is-wide"><?php esc_html_e( 'Reply box text', 'mine-mirth-mailer' ); ?><textarea rows="2" name="<?php echo esc_attr( $name( 'cta_text' ) ); ?>"><?php echo esc_textarea( (string) $f( 'cta_text' ) ); ?></textarea></label>
					<label><?php esc_html_e( 'Reply button', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'cta_button' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'cta_button' ) ); ?>"></label>
					<label><?php esc_html_e( 'Sign-off', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'signoff' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'signoff' ) ); ?>"></label>
					<label class="is-wide"><?php esc_html_e( 'Signed by (empty = “The Mine Mirth team”)', 'mine-mirth-mailer' ); ?><input type="text" name="<?php echo esc_attr( $name( 'signer' ) ); ?>" value="<?php echo esc_attr( (string) $f( 'signer' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Zain, Mine Mirth', 'mine-mirth-mailer' ); ?>"></label>
				</div>
				<?php submit_button( __( 'Save', 'mine-mirth-mailer' ) ); ?>
			</form>

			<div class="mmm-card">
				<h2><?php esc_html_e( 'Preview', 'mine-mirth-mailer' ); ?></h2>
				<?php if ( $preview ) : ?>
					<iframe class="mmm-frame" src="<?php echo esc_url( $preview ); ?>" title="<?php esc_attr_e( 'Welcome email preview', 'mine-mirth-mailer' ); ?>"></iframe>
					<p><a target="_blank" href="<?php echo esc_url( $preview ); ?>"><?php esc_html_e( 'Open in a new tab', 'mine-mirth-mailer' ); ?></a> · <a href="<?php echo esc_url( mmm_admin_url( 'mmm-send', array( 'id' => $campaign ) ) ); ?>"><?php esc_html_e( 'Results and recipients', 'mine-mirth-mailer' ); ?></a></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mmm-test">
						<?php wp_nonce_field( 'mmm_send_' . $campaign ); ?>
						<input type="hidden" name="action" value="mmm_send"><input type="hidden" name="id" value="<?php echo esc_attr( (string) $campaign ); ?>"><input type="hidden" name="do" value="test"><input type="hidden" name="back" value="mmm-welcome">
						<input type="email" name="test_to" class="regular-text" value="<?php echo esc_attr( (string) wp_get_current_user()->user_email ); ?>" required>
						<button class="button"><?php esc_html_e( 'Send me a test', 'mine-mirth-mailer' ); ?></button>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}
