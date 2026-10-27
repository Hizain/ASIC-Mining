<?php
/**
 * "Write an email": compose a one-off email with the normal WordPress text editor, add live
 * sections (network numbers, miners, guides, tools, a reply box), pick who gets it (an audience or
 * specific people), then preview, test, send now or schedule, all on one screen.
 *
 * Emails written here are ordinary campaigns: the same design, tracking, results and inbox. Emails
 * built in the block editor keep working as before.
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

function mmm_compose_defaults(): array {
	return array(
		'subject'        => '',
		'preheader'      => '',
		'message'        => '',
		'headline'       => '',
		'button'         => '',
		'button_url'     => '',
		'network'        => 0,
		'tools'          => 0,
		'reply'          => 1,
		'products'       => 0,
		'product_source' => 'popular',
		'posts'          => 0,
		'post_source'    => 'latest',
		'to'             => 'audience',
		'audience'       => 'all',
		'list'           => '',
		'add_missing'    => 0,
	);
}

function mmm_compose_data( int $id ): array {
	$saved = $id ? get_post_meta( $id, '_mmm_compose', true ) : array();
	return array_merge( mmm_compose_defaults(), is_array( $saved ) ? $saved : array() );
}

/**
 * Campaign content (blocks) for the composed email.
 */
function mmm_compose_content( array $c ): string {
	$block = static fn( string $name, array $attrs = array() ) => serialize_block( array( 'blockName' => $name, 'attrs' => $attrs, 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) ) . "\n\n";
	$out   = '';
	if ( '' !== trim( (string) $c['headline'] ) ) {
		$out .= $block( 'mmm/hero', array( 'eyebrow' => '', 'title' => (string) $c['headline'], 'text' => '', 'buttonText' => (string) $c['button'], 'buttonUrl' => (string) $c['button_url'] ) );
	}
	$out .= trim( (string) $c['message'] ) . "\n\n";
	if ( $c['network'] ) {
		$out .= $block( 'mmm/network' );
	}
	if ( (int) $c['products'] > 0 ) {
		$out .= $block( 'mmm/products', array( 'title' => __( 'Miners worth a look', 'mine-mirth-mailer' ), 'source' => (string) $c['product_source'], 'count' => (int) $c['products'], 'columns' => 2 ) );
	}
	if ( (int) $c['posts'] > 0 ) {
		$out .= $block( 'mmm/posts', array( 'title' => 'guides' === $c['post_source'] ? __( 'Guides worth reading', 'mine-mirth-mailer' ) : __( 'From the blog', 'mine-mirth-mailer' ), 'source' => (string) $c['post_source'], 'count' => (int) $c['posts'] ) );
	}
	if ( $c['tools'] ) {
		$out .= $block( 'mmm/tools' );
	}
	if ( $c['reply'] ) {
		$out .= $block( 'mmm/reply' );
	}
	return $out;
}

/**
 * Subscribers for a typed list of addresses.
 *
 * @return array{ids:int[],missing:string[],unsubscribed:string[],added:string[]}
 */
function mmm_resolve_list( string $text, bool $add_missing = false ): array {
	$r = array( 'ids' => array(), 'missing' => array(), 'unsubscribed' => array(), 'added' => array() );
	preg_match_all( '/[A-Z0-9._%+\'-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $m );
	foreach ( array_unique( array_map( 'strtolower', $m[0] ) ) as $email ) {
		if ( ! is_email( $email ) ) {
			continue;
		}
		$id = mmm_sub_by_email( $email );
		if ( ! $id && $add_missing && function_exists( 'mmm_add_subscriber' ) ) {
			[ $status, $id ] = mmm_add_subscriber( array( 'email' => $email, 'source' => __( 'Write an email', 'mine-mirth-mailer' ) ) );
			if ( 'added' === $status ) {
				$r['added'][] = $email;
			}
		}
		if ( ! $id ) {
			$r['missing'][] = $email;
		} elseif ( ! mmm_sub_is_active( (int) $id ) ) {
			$r['unsubscribed'][] = $email;
		} else {
			$r['ids'][] = (int) $id;
		}
	}
	return $r;
}

/**
 * Send a test of a campaign. Returns [ok, error].
 */
function mmm_send_test( int $campaign, string $to, int $as_sub = 0 ): array {
	$sub = mmm_preview_sub( $as_sub );
	if ( ! is_email( $to ) || ! $sub ) {
		return array( false, __( 'Enter a valid email address, and add at least one subscriber.', 'mine-mirth-mailer' ) );
	}
	$mail = mmm_compose( $campaign, $sub );
	if ( ! $mail ) {
		return array( false, __( 'The email could not be built.', 'mine-mirth-mailer' ) );
	}
	return mmm_mail( $to, '[TEST] ' . $mail['subject'], $mail['html'], $mail['text'] );
}

add_action(
	'admin_post_mmm_compose',
	static function () {
		$id = absint( wp_unslash( $_POST['id'] ?? 0 ) );
		check_admin_referer( 'mmm_compose' );
		if ( ! current_user_can( 'edit_posts' ) || ( $id && ( 'mmm_campaign' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-mailer' ) );
		}
		if ( $id && in_array( (string) get_post_meta( $id, '_mmm_state', true ), array( 'sending', 'sent' ), true ) ) {
			wp_safe_redirect( mmm_admin_url( 'mmm-send', array( 'id' => $id ) ) );
			exit;
		}
		$in  = wp_unslash( $_POST );
		$def = mmm_compose_defaults();
		$c   = array(
			'subject'        => sanitize_text_field( (string) ( $in['subject'] ?? '' ) ),
			'preheader'      => sanitize_text_field( (string) ( $in['preheader'] ?? '' ) ),
			'message'        => wp_kses_post( (string) ( $in['message'] ?? '' ) ),
			'headline'       => sanitize_text_field( (string) ( $in['headline'] ?? '' ) ),
			'button'         => sanitize_text_field( (string) ( $in['button'] ?? '' ) ),
			'button_url'     => esc_url_raw( trim( (string) ( $in['button_url'] ?? '' ) ) ),
			'network'        => empty( $in['network'] ) ? 0 : 1,
			'tools'          => empty( $in['tools'] ) ? 0 : 1,
			'reply'          => empty( $in['reply'] ) ? 0 : 1,
			'products'       => max( 0, min( 6, (int) ( $in['products'] ?? 0 ) ) ),
			'product_source' => in_array( $in['product_source'] ?? '', array( 'popular', 'latest', 'sale', 'featured' ), true ) ? $in['product_source'] : $def['product_source'],
			'posts'          => max( 0, min( 4, (int) ( $in['posts'] ?? 0 ) ) ),
			'post_source'    => in_array( $in['post_source'] ?? '', array( 'latest', 'guides' ), true ) ? $in['post_source'] : $def['post_source'],
			'to'             => 'list' === ( $in['to'] ?? '' ) ? 'list' : 'audience',
			'audience'       => isset( mmm_audiences()[ $in['audience'] ?? '' ] ) ? $in['audience'] : 'all',
			'list'           => sanitize_textarea_field( (string) ( $in['list'] ?? '' ) ),
			'add_missing'    => empty( $in['add_missing'] ) ? 0 : 1,
		);
		$title = '' !== $c['subject'] ? $c['subject'] : __( 'Untitled email', 'mine-mirth-mailer' );
		$post  = array(
			'post_type'    => 'mmm_campaign',
			'post_title'   => $title,
			'post_content' => mmm_compose_content( $c ),
		);
		kses_remove_filters(); // Content is already filtered above; block attributes must stay intact.
		if ( $id ) {
			$post['ID'] = $id;
			wp_update_post( wp_slash( $post ) );
		} else {
			$post['post_status'] = 'draft';
			$id                  = (int) wp_insert_post( wp_slash( $post ) );
		}
		kses_init_filters();
		if ( ! $id ) {
			wp_die( esc_html__( 'The email could not be saved.', 'mine-mirth-mailer' ) );
		}
		update_post_meta( $id, '_mmm_compose', wp_slash( $c ) );
		update_post_meta( $id, '_mmm_composer', 1 );
		update_post_meta( $id, '_mmm_subject', $c['subject'] );
		update_post_meta( $id, '_mmm_preheader', $c['preheader'] );
		update_post_meta( $id, '_mmm_audience', 'list' === $c['to'] ? 'list' : $c['audience'] );

		$do   = sanitize_key( (string) ( $in['do'] ?? 'save' ) );
		$args = array( 'id' => $id, 'msg' => 'saved' );
		if ( 'test' === $do ) {
			[ $ok, $error ] = mmm_send_test( $id, sanitize_email( (string) ( $in['test_to'] ?? '' ) ) );
			update_option( 'mmm_last_test_error', $error, false );
			$args['msg']    = $ok ? 'test_ok' : 'test_fail';
		} elseif ( in_array( $do, array( 'send', 'schedule' ), true ) ) {
			if ( '' === $c['subject'] || '' === trim( wp_strip_all_tags( $c['message'] ) ) && '' === $c['headline'] ) {
				$args['msg'] = 'empty';
			} else {
				$ids = mmm_audience_ids( $c['audience'] );
				if ( 'list' === $c['to'] ) {
					$res = mmm_resolve_list( $c['list'], (bool) $c['add_missing'] );
					$ids = $res['ids'];
					set_transient( 'mmm_list_result_' . get_current_user_id(), $res, 10 * MINUTE_IN_SECONDS );
					update_post_meta( $id, '_mmm_to_ids', $ids );
				}
				if ( ! $ids ) {
					$args['msg'] = 'nobody';
				} elseif ( 'send' === $do ) {
					wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
					mmm_queue_campaign( $id, $ids );
					wp_safe_redirect( mmm_admin_url( 'mmm-send', array( 'id' => $id, 'msg' => 'queued' ) ) );
					exit;
				} else {
					$when = strtotime( sanitize_text_field( (string) ( $in['when'] ?? '' ) ) . ' ' . mmm_tz()->getName() );
					if ( $when && $when > time() ) {
						wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
						update_post_meta( $id, '_mmm_state', 'scheduled' );
						update_post_meta( $id, '_mmm_scheduled', $when );
						$args['msg'] = 'scheduled';
					} else {
						$args['msg'] = 'bad_time';
					}
				}
			}
		}
		wp_safe_redirect( mmm_admin_url( 'mmm-compose', $args ) );
		exit;
	}
);

// Emails written here open in this screen from the campaign list too.
add_filter(
	'get_edit_post_link',
	static function ( $link, $post_id ) {
		if ( 'mmm_campaign' === get_post_type( $post_id ) && get_post_meta( $post_id, '_mmm_composer', true ) ) {
			return mmm_admin_url( 'mmm-compose', array( 'id' => (int) $post_id ) );
		}
		return $link;
	},
	10,
	2
);

function mmm_compose_page(): void {
	$id    = absint( wp_unslash( $_GET['id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$msg   = sanitize_key( wp_unslash( $_GET['msg'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$valid = $id && 'mmm_campaign' === get_post_type( $id ) && current_user_can( 'edit_post', $id );
	$id    = $valid ? $id : 0;
	$state = $id ? (string) get_post_meta( $id, '_mmm_state', true ) : '';
	$c     = mmm_compose_data( $id );
	$block = admin_url( 'post-new.php?post_type=mmm_campaign' );
	?>
	<div class="wrap mmm-admin mmm-compose">
		<h1 class="mmm-title"><?php echo $id ? esc_html__( 'Edit email', 'mine-mirth-mailer' ) : esc_html__( 'Write an email', 'mine-mirth-mailer' ); ?> <?php if ( $id ) : ?><span class="mmm-pill"><?php echo esc_html( mmm_state_label( $id ) ); ?></span><?php endif; ?> <a class="page-title-action" href="<?php echo esc_url( mmm_admin_url( 'mmm-compose' ) ); ?>"><?php esc_html_e( 'New email', 'mine-mirth-mailer' ); ?></a></h1>
		<p class="mmm-lead"><?php echo wp_kses( sprintf( /* translators: %s: link */ __( 'Write your message like any email; it goes out in your website’s design, with your logo, colours and footer. Prefer building with email blocks? %s.', 'mine-mirth-mailer' ), '<a href="' . esc_url( $block ) . '">' . esc_html__( 'Use the block editor', 'mine-mirth-mailer' ) . '</a>' ), array( 'a' => array( 'href' => true ) ) ); ?></p>
		<?php
		$messages = array(
			'saved'     => array( 'success', __( 'Saved. The preview on the right shows the latest version.', 'mine-mirth-mailer' ) ),
			'test_ok'   => array( 'success', __( 'Test email sent. Check your inbox (and the spam folder).', 'mine-mirth-mailer' ) ),
			'test_fail' => array( 'error', __( 'The test email could not be sent:', 'mine-mirth-mailer' ) . ' ' . (string) get_option( 'mmm_last_test_error' ) ),
			'nobody'    => array( 'warning', __( 'Nobody to send to: check the recipients.', 'mine-mirth-mailer' ) ),
			'empty'     => array( 'error', __( 'Add a subject and a message before sending.', 'mine-mirth-mailer' ) ),
			'scheduled' => array( 'success', __( 'Scheduled. It goes out automatically at the time you picked.', 'mine-mirth-mailer' ) ),
			'bad_time'  => array( 'error', __( 'Pick a date and time in the future.', 'mine-mirth-mailer' ) ),
		);
		if ( isset( $messages[ $msg ] ) ) {
			echo '<div class="notice notice-' . esc_attr( $messages[ $msg ][0] ) . ' is-dismissible"><p>' . esc_html( $messages[ $msg ][1] ) . '</p></div>';
		}
		$list = get_transient( 'mmm_list_result_' . get_current_user_id() );
		if ( is_array( $list ) && ( $list['missing'] || $list['unsubscribed'] ) && in_array( $msg, array( 'nobody', 'scheduled' ), true ) ) {
			delete_transient( 'mmm_list_result_' . get_current_user_id() );
			if ( $list['missing'] ) {
				echo '<div class="notice notice-warning"><p>' . esc_html__( 'Not on your list (skipped):', 'mine-mirth-mailer' ) . ' ' . esc_html( implode( ', ', $list['missing'] ) ) . '</p></div>';
			}
			if ( $list['unsubscribed'] ) {
				echo '<div class="notice notice-warning"><p>' . esc_html__( 'Unsubscribed (skipped):', 'mine-mirth-mailer' ) . ' ' . esc_html( implode( ', ', $list['unsubscribed'] ) ) . '</p></div>';
			}
		}
		if ( in_array( $state, array( 'sending', 'sent' ), true ) ) :
			?>
			<div class="mmm-card"><p><?php esc_html_e( 'This email has been sent, so it can no longer be changed.', 'mine-mirth-mailer' ); ?></p>
			<p><a class="button button-primary" href="<?php echo esc_url( mmm_admin_url( 'mmm-send', array( 'id' => $id ) ) ); ?>"><?php esc_html_e( 'See the results', 'mine-mirth-mailer' ); ?></a> <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mmm_duplicate&id=' . $id ), 'mmm_duplicate_' . $id ) ); ?>"><?php esc_html_e( 'Duplicate it', 'mine-mirth-mailer' ); ?></a></p></div>
		</div>
			<?php
			return;
		endif;
		if ( $id && ! get_post_meta( $id, '_mmm_composer', true ) && ! mmm_is_auto( $id ) ) :
			?>
			<div class="mmm-card"><p><?php esc_html_e( 'This email was built in the block editor. Open it there to change it.', 'mine-mirth-mailer' ); ?></p><p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' . $id ) ); ?>"><?php esc_html_e( 'Open in the block editor', 'mine-mirth-mailer' ); ?></a></p></div>
		</div>
			<?php
			return;
		endif;
		$preview = $id ? wp_nonce_url( admin_url( 'admin-post.php?action=mmm_preview&id=' . $id ), 'mmm_preview_' . $id ) : '';
		$counts  = array();
		foreach ( mmm_audiences() as $key => $label ) {
			$counts[ $key ] = count( mmm_audience_ids( $key ) );
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mmm-grid mmm-grid--preview" id="mmm-compose-form">
			<?php wp_nonce_field( 'mmm_compose' ); ?>
			<input type="hidden" name="action" value="mmm_compose"><input type="hidden" name="id" value="<?php echo esc_attr( (string) $id ); ?>">
			<div>
				<div class="mmm-card">
					<h2><?php esc_html_e( '1. Subject', 'mine-mirth-mailer' ); ?></h2>
					<div class="mmm-fields">
						<label class="is-wide"><?php esc_html_e( 'Subject line', 'mine-mirth-mailer' ); ?> <span class="required">*</span><input type="text" name="subject" value="<?php echo esc_attr( (string) $c['subject'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. New S21 XP stock and this week’s hashprice', 'mine-mirth-mailer' ); ?>"></label>
						<label class="is-wide"><?php esc_html_e( 'Preview text (shown after the subject in the inbox)', 'mine-mirth-mailer' ); ?><input type="text" name="preheader" value="<?php echo esc_attr( (string) $c['preheader'] ); ?>"></label>
					</div>
				</div>

				<div class="mmm-card">
					<h2><?php esc_html_e( '2. Message', 'mine-mirth-mailer' ); ?></h2>
					<details class="mmm-banner" <?php echo '' !== $c['headline'] ? 'open' : ''; ?>>
						<summary><?php esc_html_e( 'Add a big headline with a button at the top (optional)', 'mine-mirth-mailer' ); ?></summary>
						<div class="mmm-fields">
							<label class="is-wide"><?php esc_html_e( 'Headline', 'mine-mirth-mailer' ); ?><input type="text" name="headline" value="<?php echo esc_attr( (string) $c['headline'] ); ?>"></label>
							<label><?php esc_html_e( 'Button text', 'mine-mirth-mailer' ); ?><input type="text" name="button" value="<?php echo esc_attr( (string) $c['button'] ); ?>"></label>
							<label><?php esc_html_e( 'Button link', 'mine-mirth-mailer' ); ?><input type="url" name="button_url" value="<?php echo esc_attr( (string) $c['button_url'] ); ?>" placeholder="https://"></label>
						</div>
					</details>
					<?php
					wp_editor(
						(string) $c['message'],
						'mmm_message',
						array(
							'textarea_name' => 'message',
							'textarea_rows' => 16,
							'media_buttons' => true,
							'tinymce'       => array( 'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,link,unlink,hr,undo,redo', 'toolbar2' => '' ),
						)
					);
					?>
					<p class="description"><?php esc_html_e( 'Use {first_name|there} to greet people by name. Headings, lists, links, quotes and images all come out in your email design.', 'mine-mirth-mailer' ); ?></p>
				</div>

				<div class="mmm-card">
					<h2><?php esc_html_e( '3. Add to the email (optional)', 'mine-mirth-mailer' ); ?></h2>
					<p class="mmm-checks">
						<label><input type="checkbox" name="network" value="1" <?php checked( (int) $c['network'], 1 ); ?>> <?php esc_html_e( 'Live Bitcoin network numbers (price, hashprice, difficulty, halving)', 'mine-mirth-mailer' ); ?></label><br>
						<label><input type="checkbox" name="tools" value="1" <?php checked( (int) $c['tools'], 1 ); ?>> <?php esc_html_e( 'Free tools cards (profit, hashprice, break-even, hosting)', 'mine-mirth-mailer' ); ?></label><br>
						<label><input type="checkbox" name="reply" value="1" <?php checked( (int) $c['reply'], 1 ); ?>> <?php esc_html_e( '“Ask us anything” reply box (answers land in Mailer › Inbox)', 'mine-mirth-mailer' ); ?></label>
					</p>
					<p>
						<label><?php esc_html_e( 'Miners', 'mine-mirth-mailer' ); ?> <input type="number" min="0" max="6" class="small-text" name="products" value="<?php echo esc_attr( (string) $c['products'] ); ?>"></label>
						<select name="product_source">
							<?php foreach ( array( 'popular' => __( 'Best sellers', 'mine-mirth-mailer' ), 'latest' => __( 'Newest', 'mine-mirth-mailer' ), 'sale' => __( 'On sale', 'mine-mirth-mailer' ), 'featured' => __( 'Featured', 'mine-mirth-mailer' ) ) as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $c['product_source'], $k ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						&nbsp; <label><?php esc_html_e( 'Articles', 'mine-mirth-mailer' ); ?> <input type="number" min="0" max="4" class="small-text" name="posts" value="<?php echo esc_attr( (string) $c['posts'] ); ?>"></label>
						<select name="post_source">
							<option value="latest" <?php selected( $c['post_source'], 'latest' ); ?>><?php esc_html_e( 'Newest blog posts', 'mine-mirth-mailer' ); ?></option>
							<option value="guides" <?php selected( $c['post_source'], 'guides' ); ?>><?php esc_html_e( 'Mining guides', 'mine-mirth-mailer' ); ?></option>
						</select>
					</p>
				</div>

				<div class="mmm-card">
					<h2><?php esc_html_e( '4. Who gets it', 'mine-mirth-mailer' ); ?></h2>
					<p><label><input type="radio" name="to" value="audience" <?php checked( $c['to'], 'audience' ); ?>> <?php esc_html_e( 'A group of subscribers', 'mine-mirth-mailer' ); ?></label>
					<select name="audience">
						<?php foreach ( mmm_audiences() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $c['audience'], $key ); ?>><?php echo esc_html( $label . ' (' . number_format_i18n( $counts[ $key ] ) . ')' ); ?></option>
						<?php endforeach; ?>
					</select></p>
					<p><label><input type="radio" name="to" value="list" <?php checked( $c['to'], 'list' ); ?>> <?php esc_html_e( 'Specific people', 'mine-mirth-mailer' ); ?></label></p>
					<textarea name="list" rows="3" class="large-text code" placeholder="ali@example.com, sara@example.com"><?php echo esc_textarea( (string) $c['list'] ); ?></textarea>
					<p><label><input type="checkbox" name="add_missing" value="1" <?php checked( (int) $c['add_missing'], 1 ); ?>> <?php esc_html_e( 'Add anyone who is not on my list yet (they agreed to hear from me)', 'mine-mirth-mailer' ); ?></label></p>
					<p class="description"><?php esc_html_e( 'Unsubscribed people are always skipped.', 'mine-mirth-mailer' ); ?></p>
				</div>

				<div class="mmm-card mmm-actions-card">
					<h2><?php esc_html_e( '5. Save, test and send', 'mine-mirth-mailer' ); ?></h2>
					<p><button class="button button-large" name="do" value="save"><?php esc_html_e( 'Save draft', 'mine-mirth-mailer' ); ?></button></p>
					<p><input type="email" name="test_to" class="regular-text" value="<?php echo esc_attr( (string) wp_get_current_user()->user_email ); ?>"> <button class="button" name="do" value="test"><?php esc_html_e( 'Save and send me a test', 'mine-mirth-mailer' ); ?></button></p>
					<p><button class="button button-primary button-hero" name="do" value="send" onclick="return confirm('<?php echo esc_js( __( 'Send this email now?', 'mine-mirth-mailer' ) ); ?>')"><?php esc_html_e( 'Send now', 'mine-mirth-mailer' ); ?></button></p>
					<p><?php esc_html_e( 'Or schedule for', 'mine-mirth-mailer' ); ?> <input type="datetime-local" name="when" value="<?php echo esc_attr( mmm_date( 'Y-m-d\TH:i', $id && 'scheduled' === $state ? (int) get_post_meta( $id, '_mmm_scheduled', true ) : time() + DAY_IN_SECONDS ) ); ?>"> <span class="description"><?php echo esc_html( mmm_tz()->getName() ); ?></span> <button class="button" name="do" value="schedule"><?php esc_html_e( 'Schedule', 'mine-mirth-mailer' ); ?></button></p>
				</div>
			</div>

			<div class="mmm-card mmm-sticky-preview">
				<h2><?php esc_html_e( 'Preview', 'mine-mirth-mailer' ); ?></h2>
				<?php if ( $preview ) : ?>
					<iframe class="mmm-frame" src="<?php echo esc_url( $preview ); ?>" title="<?php esc_attr_e( 'Email preview', 'mine-mirth-mailer' ); ?>"></iframe>
					<p><a target="_blank" href="<?php echo esc_url( $preview ); ?>"><?php esc_html_e( 'Open in a new tab', 'mine-mirth-mailer' ); ?></a></p>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'Save a draft to see exactly how the email looks, with your logo, colours and today’s numbers.', 'mine-mirth-mailer' ); ?></p>
				<?php endif; ?>
			</div>
		</form>
	</div>
	<?php
}
