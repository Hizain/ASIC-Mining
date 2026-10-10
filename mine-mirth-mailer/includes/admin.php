<?php
/**
 * Admin screens: Mailer › Overview, Campaigns, Weekly email, Inbox,
 * Subscribers and Settings.
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	static function () {
		$unread = mmm_unread_count();
		$bubble = $unread ? ' <span class="awaiting-mod"><span class="pending-count">' . (int) $unread . '</span></span>' : '';
		add_menu_page( __( 'Mailer', 'mine-mirth-mailer' ), __( 'Mailer', 'mine-mirth-mailer' ) . $bubble, 'manage_options', 'mmm', 'mmm_overview_page', 'dashicons-email-alt', 57 );
		add_submenu_page( 'mmm', __( 'Overview', 'mine-mirth-mailer' ), __( 'Overview', 'mine-mirth-mailer' ), 'manage_options', 'mmm', 'mmm_overview_page' );
		add_submenu_page( 'mmm', __( 'New email', 'mine-mirth-mailer' ), __( 'New email', 'mine-mirth-mailer' ), 'edit_posts', 'post-new.php?post_type=mmm_campaign' );
		add_submenu_page( 'mmm', __( 'Weekly email', 'mine-mirth-mailer' ), __( 'Weekly email', 'mine-mirth-mailer' ), 'manage_options', 'mmm-weekly', 'mmm_weekly_page' );
		add_submenu_page( 'mmm', __( 'Inbox', 'mine-mirth-mailer' ), __( 'Inbox', 'mine-mirth-mailer' ) . $bubble, 'manage_options', 'mmm-inbox', 'mmm_inbox_page' );
		add_submenu_page( 'mmm', __( 'Subscribers', 'mine-mirth-mailer' ), __( 'Subscribers', 'mine-mirth-mailer' ), 'manage_options', 'mmm-subscribers', 'mmm_subscribers_page' );
		add_submenu_page( 'mmm', __( 'Settings', 'mine-mirth-mailer' ), __( 'Settings', 'mine-mirth-mailer' ), 'manage_options', 'mmm-settings', 'mmm_settings_page' );
		add_submenu_page( 'options.php', __( 'Test & send', 'mine-mirth-mailer' ), '', 'edit_posts', 'mmm-send', 'mmm_send_page' );
	},
	9
);

// Keep the Mailer menu open on campaign screens and the send screen.
add_filter(
	'parent_file',
	static function ( $file ) {
		global $plugin_page;
		$screen = get_current_screen();
		if ( ( $screen && 'mmm_campaign' === $screen->post_type ) || 'mmm-send' === $plugin_page ) {
			return 'mmm';
		}
		return $file;
	}
);

add_action(
	'admin_enqueue_scripts',
	static function () {
		$screen = get_current_screen();
		if ( $screen && ( str_contains( (string) $screen->id, 'mmm' ) || 'mmm_campaign' === $screen->post_type ) ) {
			wp_enqueue_style( 'mmm-admin', MMM_URI . 'assets/admin.css', array(), MMM_VERSION );
		}
	}
);

function mmm_admin_url( string $page, array $args = array() ): string {
	return add_query_arg( $args, admin_url( 'admin.php?page=' . $page ) );
}

/* ---------------------------------------------------------------------------
 * Overview
 * ------------------------------------------------------------------------- */

function mmm_overview_page(): void {
	global $wpdb;
	$e      = mmm_table( 'events' );
	$q      = mmm_table( 'queue' );
	$since  = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
	$active = count( mmm_active_subscribers() );
	$total  = (int) array_sum( array_map( 'intval', (array) wp_count_posts( 'mm_subscriber' ) ) );
	$sent   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $q WHERE status = 'sent' AND sent > %s", $since ) ); // phpcs:ignore WordPress.DB
	$opens  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM ( SELECT DISTINCT sub_id, campaign_id FROM $e WHERE type = 'open' AND campaign_id > 0 AND created > %s ) t", $since ) ); // phpcs:ignore WordPress.DB
	$clicks = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM ( SELECT DISTINCT sub_id, campaign_id FROM $e WHERE type = 'click' AND campaign_id > 0 AND created > %s ) t", $since ) ); // phpcs:ignore WordPress.DB
	$views  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $e WHERE type = 'view' AND created > %s", $since ) ); // phpcs:ignore WordPress.DB
	$unread = mmm_unread_count();
	$pct    = static fn( $n ) => $sent ? round( 100 * $n / $sent ) . '%' : '—';
	?>
	<div class="wrap mmm-admin">
		<h1 class="mmm-title"><?php esc_html_e( 'Mailer', 'mine-mirth-mailer' ); ?> <a class="page-title-action" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=mmm_campaign' ) ); ?>"><?php esc_html_e( 'New email', 'mine-mirth-mailer' ); ?></a></h1>
		<div class="mmm-stats mmm-stats--wide">
			<?php
			foreach ( array(
				array( __( 'Active subscribers', 'mine-mirth-mailer' ), number_format_i18n( $active ), sprintf( /* translators: %d: count */ __( '%d unsubscribed', 'mine-mirth-mailer' ), max( 0, $total - $active ) ), mmm_admin_url( 'mmm-subscribers' ) ),
				array( __( 'Emails sent', 'mine-mirth-mailer' ), number_format_i18n( $sent ), __( 'last 30 days', 'mine-mirth-mailer' ), admin_url( 'edit.php?post_type=mmm_campaign' ) ),
				array( __( 'Opened', 'mine-mirth-mailer' ), $pct( $opens ), __( 'of emails sent', 'mine-mirth-mailer' ), '' ),
				array( __( 'Clicked', 'mine-mirth-mailer' ), $pct( $clicks ), __( 'of emails sent', 'mine-mirth-mailer' ), '' ),
				array( __( 'Site visits by subscribers', 'mine-mirth-mailer' ), number_format_i18n( $views ), __( 'last 30 days', 'mine-mirth-mailer' ), '' ),
				array( __( 'Unread replies', 'mine-mirth-mailer' ), number_format_i18n( $unread ), __( 'open the inbox', 'mine-mirth-mailer' ), mmm_admin_url( 'mmm-inbox' ) ),
			) as $s ) :
				?>
				<div><small><?php echo esc_html( $s[0] ); ?></small><strong><?php echo esc_html( (string) $s[1] ); ?></strong><span><?php echo $s[3] ? '<a href="' . esc_url( $s[3] ) . '">' . esc_html( $s[2] ) . '</a>' : esc_html( $s[2] ); ?></span></div>
			<?php endforeach; ?>
		</div>

		<div class="mmm-grid">
			<div class="mmm-card">
				<h2><?php esc_html_e( 'Recent emails', 'mine-mirth-mailer' ); ?></h2>
				<?php
				$recent = get_posts( array( 'post_type' => 'mmm_campaign', 'post_status' => 'any', 'posts_per_page' => 8 ) );
				if ( ! $recent ) {
					echo '<p>' . esc_html__( 'No emails yet.', 'mine-mirth-mailer' ) . ' <a href="' . esc_url( admin_url( 'post-new.php?post_type=mmm_campaign' ) ) . '">' . esc_html__( 'Write your first one', 'mine-mirth-mailer' ) . '</a></p>';
				} else {
					echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Email', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'Status', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'Sent', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'Opened', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'Clicked', 'mine-mirth-mailer' ) . '</th></tr></thead><tbody>';
					foreach ( $recent as $c ) {
						$s = mmm_campaign_stats( $c->ID );
						$p = static fn( $n ) => $s['sent'] ? round( 100 * $n / $s['sent'] ) . '%' : '—';
						echo '<tr><td><a href="' . esc_url( mmm_admin_url( 'mmm-send', array( 'id' => $c->ID ) ) ) . '">' . esc_html( get_the_title( $c ) ) . '</a></td><td>' . esc_html( mmm_state_label( $c->ID ) ) . '</td><td>' . esc_html( number_format_i18n( $s['sent'] ) ) . '</td><td>' . esc_html( $p( $s['opens'] ) ) . '</td><td>' . esc_html( $p( $s['clickers'] ) ) . '</td></tr>';
					}
					echo '</tbody></table>';
				}
				?>
			</div>
			<div class="mmm-card">
				<h2><?php esc_html_e( 'Weekly email', 'mine-mirth-mailer' ); ?></h2>
				<?php if ( mmm_weekly( 'enabled' ) ) : ?>
					<p><span class="mmm-dot is-on"></span><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'On. Next send: %s', 'mine-mirth-mailer' ), wp_date( 'l, ' . get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), mmm_weekly_next() ) ) ); ?></p>
				<?php else : ?>
					<p><span class="mmm-dot"></span><?php esc_html_e( 'Off. Each subscriber would get their own version based on what they did on the site.', 'mine-mirth-mailer' ); ?></p>
				<?php endif; ?>
				<p><a class="button" href="<?php echo esc_url( mmm_admin_url( 'mmm-weekly' ) ); ?>"><?php esc_html_e( 'Weekly email settings and preview', 'mine-mirth-mailer' ); ?></a></p>
				<h2 style="margin-top:24px"><?php esc_html_e( 'Checklist', 'mine-mirth-mailer' ); ?></h2>
				<ul class="mmm-check">
					<?php
					$items = array(
						array( (bool) trim( (string) mmm_opt( 'address' ) ), __( 'Business postal address added (required for marketing email)', 'mine-mirth-mailer' ), mmm_admin_url( 'mmm-settings' ) ),
						array( (bool) mmm_opt( 'from_email' ), __( '“Send from” address at your own domain', 'mine-mirth-mailer' ), mmm_admin_url( 'mmm-settings' ) ),
						array( (bool) get_option( 'mmm_test_sent' ), __( 'Test email sent to yourself', 'mine-mirth-mailer' ), admin_url( 'edit.php?post_type=mmm_campaign' ) ),
						array( (bool) mmm_weekly( 'enabled' ), __( 'Weekly email turned on', 'mine-mirth-mailer' ), mmm_admin_url( 'mmm-weekly' ) ),
						array( function_exists( 'wp_mail_smtp' ) || class_exists( 'FluentMail\App\Hooks\Handlers\AdminMenuHandler' ) || defined( 'FLUENTMAIL' ) || defined( 'WPMS_PLUGIN_VER' ), __( 'An SMTP plugin is sending your email (WP Mail SMTP or FluentSMTP)', 'mine-mirth-mailer' ), admin_url( 'plugin-install.php?s=smtp&tab=search&type=term' ) ),
					);
					foreach ( $items as $it ) {
						echo '<li class="' . ( $it[0] ? 'is-done' : '' ) . '"><a href="' . esc_url( $it[2] ) . '">' . esc_html( $it[1] ) . '</a></li>';
					}
					?>
				</ul>
			</div>
		</div>
	</div>
	<?php
}

/* ---------------------------------------------------------------------------
 * Weekly email
 * ------------------------------------------------------------------------- */

add_action(
	'admin_init',
	static function () {
		register_setting(
			'mmm_weekly',
			'mmm_weekly',
			array(
				'type'              => 'array',
				'sanitize_callback' => static function ( $in ) {
					$in = is_array( $in ) ? $in : array();
					return array(
						'enabled'  => empty( $in['enabled'] ) ? 0 : 1,
						'day'      => max( 0, min( 6, (int) ( $in['day'] ?? 2 ) ) ),
						'time'     => preg_match( '/^\d{2}:\d{2}$/', (string) ( $in['time'] ?? '' ) ) ? (string) $in['time'] : '09:00',
						'subject'  => sanitize_text_field( (string) ( $in['subject'] ?? '' ) ),
						'intro'    => sanitize_textarea_field( (string) ( $in['intro'] ?? '' ) ),
						'network'  => empty( $in['network'] ) ? 0 : 1,
						'products' => max( 2, min( 6, (int) ( $in['products'] ?? 4 ) ) ),
						'posts'    => max( 1, min( 6, (int) ( $in['posts'] ?? 3 ) ) ),
					);
				},
			)
		);
		register_setting(
			'mmm_settings',
			'mmm_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'mmm_sanitize_settings',
			)
		);
	}
);

add_action(
	'admin_post_mmm_weekly_preview',
	static function () {
		check_admin_referer( 'mmm_weekly_preview' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-mailer' ) );
		}
		$sub = mmm_preview_sub( absint( wp_unslash( $_GET['sub'] ?? 0 ) ) );
		if ( ! $sub ) {
			wp_die( esc_html__( 'Add at least one subscriber to preview the weekly email.', 'mine-mirth-mailer' ) );
		}
		$w    = mmm_weekly_compose( $sub );
		$html = mmm_personalize( mmm_document( array( 'subject' => $w['subject'], 'preheader' => $w['preheader'], 'body' => $w['body'], 'campaign' => 0 ) ), $sub, 0 );
		header( 'Content-Type: text/html; charset=utf-8' );
		echo str_replace( esc_url( mmm_link( 'o', 0, mmm_sub_token( $sub ) ) ), esc_url( includes_url( 'images/blank.gif' ) ), $html ); // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}
);

add_action(
	'admin_post_mmm_weekly_now',
	static function () {
		check_admin_referer( 'mmm_weekly_now' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-mailer' ) );
		}
		$id = mmm_weekly_start();
		wp_safe_redirect( $id ? mmm_admin_url( 'mmm-send', array( 'id' => $id, 'msg' => 'queued' ) ) : mmm_admin_url( 'mmm-weekly', array( 'none' => 1 ) ) );
		exit;
	}
);

function mmm_weekly_page(): void {
	$w     = static fn( $k ) => mmm_weekly( $k );
	$days  = array( __( 'Sunday', 'mine-mirth-mailer' ), __( 'Monday', 'mine-mirth-mailer' ), __( 'Tuesday', 'mine-mirth-mailer' ), __( 'Wednesday', 'mine-mirth-mailer' ), __( 'Thursday', 'mine-mirth-mailer' ), __( 'Friday', 'mine-mirth-mailer' ), __( 'Saturday', 'mine-mirth-mailer' ) );
	$subs  = get_posts( array( 'post_type' => 'mm_subscriber', 'post_status' => 'any', 'posts_per_page' => 200 ) );
	$pick  = mmm_preview_sub( absint( wp_unslash( $_GET['sub'] ?? 0 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$prev  = wp_nonce_url( admin_url( 'admin-post.php?action=mmm_weekly_preview&sub=' . $pick ), 'mmm_weekly_preview' );
	$focus = $pick ? mmm_profile( $pick )['top'] : 'none';
	$names = array( 'shop' => __( 'Shop browser', 'mine-mirth-mailer' ), 'blog' => __( 'Blog reader', 'mine-mirth-mailer' ), 'tools' => __( 'Tools user', 'mine-mirth-mailer' ), 'home' => __( 'General visitor', 'mine-mirth-mailer' ), 'none' => __( 'No activity yet', 'mine-mirth-mailer' ) );
	?>
	<div class="wrap mmm-admin">
		<h1><?php esc_html_e( 'Weekly email', 'mine-mirth-mailer' ); ?></h1>
		<p class="mmm-lead"><?php esc_html_e( 'Every subscriber gets their own weekly email, built from what they did on the site in the last 30 days. Shop visitors see the miners they looked at and similar ones with a quote button; blog readers get new articles in their topics; people using the calculator or hosting pages get network numbers and hosting; everyone else gets what’s new.', 'mine-mirth-mailer' ); ?></p>
		<?php if ( ! empty( $_GET['none'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'There are no active subscribers to send to yet.', 'mine-mirth-mailer' ); ?></p></div>
		<?php endif; ?>
		<div class="mmm-grid mmm-grid--preview">
			<div>
				<div class="mmm-card">
					<form method="post" action="options.php">
						<?php settings_fields( 'mmm_weekly' ); ?>
						<p><label class="mmm-switch"><input type="checkbox" name="mmm_weekly[enabled]" value="1" <?php checked( (int) $w( 'enabled' ), 1 ); ?>> <strong><?php esc_html_e( 'Send the weekly email automatically', 'mine-mirth-mailer' ); ?></strong></label></p>
						<p><?php esc_html_e( 'Every', 'mine-mirth-mailer' ); ?>
							<select name="mmm_weekly[day]"><?php foreach ( $days as $i => $name ) : ?><option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( (int) $w( 'day' ), $i ); ?>><?php echo esc_html( $name ); ?></option><?php endforeach; ?></select>
							<?php esc_html_e( 'at', 'mine-mirth-mailer' ); ?> <input type="time" name="mmm_weekly[time]" value="<?php echo esc_attr( (string) $w( 'time' ) ); ?>"> <span class="description"><?php echo esc_html( wp_timezone_string() ); ?></span></p>
						<?php if ( $w( 'enabled' ) ) : ?>
							<p class="description"><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'Next send: %s', 'mine-mirth-mailer' ), wp_date( 'l, ' . get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), mmm_weekly_next() ) ) ); ?></p>
						<?php endif; ?>
						<p><label><strong><?php esc_html_e( 'Subject line (optional)', 'mine-mirth-mailer' ); ?></strong><br><input type="text" class="widefat" name="mmm_weekly[subject]" value="<?php echo esc_attr( (string) $w( 'subject' ) ); ?>" placeholder="<?php esc_attr_e( 'Leave empty for a personal subject, e.g. “Still thinking about the Antminer S21 XP?”', 'mine-mirth-mailer' ); ?>"></label></p>
						<p><label><strong><?php esc_html_e( 'Note from you this week (optional)', 'mine-mirth-mailer' ); ?></strong><br><textarea class="widefat" rows="3" name="mmm_weekly[intro]" placeholder="<?php esc_attr_e( 'For example: New S21 stock lands Friday. Reply to reserve one.', 'mine-mirth-mailer' ); ?>"><?php echo esc_textarea( (string) $w( 'intro' ) ); ?></textarea></label></p>
						<p><label><input type="checkbox" name="mmm_weekly[network]" value="1" <?php checked( (int) $w( 'network' ), 1 ); ?>> <?php esc_html_e( 'Include the Bitcoin network snapshot', 'mine-mirth-mailer' ); ?></label></p>
						<p><?php esc_html_e( 'Products per section', 'mine-mirth-mailer' ); ?> <input type="number" min="2" max="6" class="small-text" name="mmm_weekly[products]" value="<?php echo esc_attr( (string) $w( 'products' ) ); ?>"> &nbsp; <?php esc_html_e( 'Articles', 'mine-mirth-mailer' ); ?> <input type="number" min="1" max="6" class="small-text" name="mmm_weekly[posts]" value="<?php echo esc_attr( (string) $w( 'posts' ) ); ?>"></p>
						<?php submit_button( __( 'Save', 'mine-mirth-mailer' ), 'primary', 'submit', false ); ?>
					</form>
				</div>
				<div class="mmm-card">
					<h2><?php esc_html_e( 'Send this week’s email now', 'mine-mirth-mailer' ); ?></h2>
					<p class="description"><?php echo esc_html( sprintf( /* translators: %d: count */ __( 'Goes to all %d active subscribers, each with their own version.', 'mine-mirth-mailer' ), count( mmm_active_subscribers() ) ) ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Send the weekly email to everyone now?', 'mine-mirth-mailer' ) ); ?>')">
						<input type="hidden" name="action" value="mmm_weekly_now"><?php wp_nonce_field( 'mmm_weekly_now' ); ?>
						<button class="button"><?php esc_html_e( 'Send now', 'mine-mirth-mailer' ); ?></button>
					</form>
					<?php
					$runs = get_posts( array( 'post_type' => 'mmm_campaign', 'post_status' => 'any', 'posts_per_page' => 6, 'meta_key' => '_mmm_kind', 'meta_value' => 'weekly' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
					if ( $runs ) {
						echo '<h3>' . esc_html__( 'Past weekly emails', 'mine-mirth-mailer' ) . '</h3><ul>';
						foreach ( $runs as $r ) {
							$s = mmm_campaign_stats( $r->ID );
							echo '<li><a href="' . esc_url( mmm_admin_url( 'mmm-send', array( 'id' => $r->ID ) ) ) . '">' . esc_html( get_the_title( $r ) ) . '</a> — ' . esc_html( sprintf( /* translators: 1: sent 2: opened 3: clicked */ __( '%1$d sent, %2$d opened, %3$d clicked', 'mine-mirth-mailer' ), $s['sent'], $s['opens'], $s['clickers'] ) ) . '</li>';
						}
						echo '</ul>';
					}
					?>
				</div>
			</div>
			<div class="mmm-card">
				<h2><?php esc_html_e( 'Preview as a subscriber', 'mine-mirth-mailer' ); ?></h2>
				<form method="get" style="margin-bottom:12px">
					<input type="hidden" name="page" value="mmm-weekly">
					<select name="sub" onchange="this.form.submit()">
						<?php foreach ( $subs as $s ) : ?>
							<option value="<?php echo esc_attr( (string) $s->ID ); ?>" <?php selected( $pick, $s->ID ); ?>><?php echo esc_html( $s->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="mmm-pill"><?php echo esc_html( $names[ $focus ] ?? $focus ); ?></span>
				</form>
				<?php if ( $pick ) : ?>
					<iframe class="mmm-frame" src="<?php echo esc_url( $prev ); ?>" title="<?php esc_attr_e( 'Weekly email preview', 'mine-mirth-mailer' ); ?>"></iframe>
				<?php else : ?>
					<p><?php esc_html_e( 'No subscribers yet.', 'mine-mirth-mailer' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}

/* ---------------------------------------------------------------------------
 * Subscribers
 * ------------------------------------------------------------------------- */

add_action(
	'admin_post_mmm_sub_status',
	static function () {
		$sub = absint( wp_unslash( $_GET['sub'] ?? 0 ) );
		check_admin_referer( 'mmm_sub_status_' . $sub );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-mailer' ) );
		}
		mmm_sub_set_status( $sub, 'unsub' === sanitize_key( wp_unslash( $_GET['to'] ?? '' ) ) ? 'unsubscribed' : 'active' );
		wp_safe_redirect( mmm_admin_url( 'mmm-subscribers', array( 'sub' => $sub ) ) );
		exit;
	}
);

function mmm_event_label( object $ev ): string {
	$obj   = (int) $ev->object_id;
	$title = $obj ? get_the_title( $obj ) : '';
	switch ( $ev->type ) {
		case 'view':
			$map = array(
				'home'       => __( 'Visited the homepage', 'mine-mirth-mailer' ),
				/* translators: %s: product */
				'product'    => sprintf( __( 'Viewed %s', 'mine-mirth-mailer' ), $title ? $title : __( 'a product', 'mine-mirth-mailer' ) ),
				'shop'       => $obj ? sprintf( /* translators: %s: category */ __( 'Browsed %s', 'mine-mirth-mailer' ), ( $t = get_term( $obj, 'product_cat' ) ) && ! is_wp_error( $t ) ? $t->name : __( 'the shop', 'mine-mirth-mailer' ) ) : __( 'Browsed the shop', 'mine-mirth-mailer' ),
				/* translators: %s: article */
				'post'       => sprintf( __( 'Read “%s”', 'mine-mirth-mailer' ), $title ),
				'blog'       => __( 'Browsed the blog', 'mine-mirth-mailer' ),
				'hosting'    => __( 'Looked at hosting', 'mine-mirth-mailer' ),
				/* translators: %s: page */
				'calculator' => sprintf( __( 'Used %s', 'mine-mirth-mailer' ), $title ? $title : __( 'a calculator', 'mine-mirth-mailer' ) ),
				'checkout'   => __( 'Went to cart or checkout', 'mine-mirth-mailer' ),
				/* translators: %s: page */
				'page'       => $title ? sprintf( __( 'Visited %s', 'mine-mirth-mailer' ), $title ) : __( 'Visited a page', 'mine-mirth-mailer' ),
			);
			return $map[ $ev->area ] ?? __( 'Visited the site', 'mine-mirth-mailer' );
		case 'sent':
		case 'open':
			$name = (int) $ev->campaign_id ? get_the_title( (int) $ev->campaign_id ) : '';
			if ( '' === $name ) {
				return 'sent' === $ev->type ? __( 'Was sent an email', 'mine-mirth-mailer' ) : __( 'Opened an email', 'mine-mirth-mailer' );
			}
			/* translators: %s: email */
			return sprintf( 'sent' === $ev->type ? __( 'Was sent “%s”', 'mine-mirth-mailer' ) : __( 'Opened “%s”', 'mine-mirth-mailer' ), $name );
		case 'click':
			/* translators: %s: link */
			return sprintf( __( 'Clicked %s', 'mine-mirth-mailer' ), (string) wp_parse_url( (string) $ev->url, PHP_URL_PATH ) ?: '/' );
		case 'reply':
			return __( 'Sent you a message', 'mine-mirth-mailer' );
		case 'unsub':
			return __( 'Unsubscribed', 'mine-mirth-mailer' );
	}
	return (string) $ev->type;
}

function mmm_subscribers_page(): void {
	global $wpdb;
	$e   = mmm_table( 'events' );
	$sub = absint( wp_unslash( $_GET['sub'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<div class="wrap mmm-admin">';

	if ( $sub && 'mm_subscriber' === get_post_type( $sub ) ) {
		$p      = mmm_profile( $sub, 60 );
		$active = mmm_sub_is_active( $sub );
		$events = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $e WHERE sub_id = %d ORDER BY created DESC, id DESC LIMIT 60", $sub ) ); // phpcs:ignore WordPress.DB
		$name   = mmm_sub_first_name( $sub );
		$names  = array( 'shop' => __( 'Shop browser', 'mine-mirth-mailer' ), 'blog' => __( 'Blog reader', 'mine-mirth-mailer' ), 'tools' => __( 'Tools user', 'mine-mirth-mailer' ), 'home' => __( 'General visitor', 'mine-mirth-mailer' ), 'none' => __( 'No activity yet', 'mine-mirth-mailer' ) );
		echo '<p><a href="' . esc_url( mmm_admin_url( 'mmm-subscribers' ) ) . '">← ' . esc_html__( 'All subscribers', 'mine-mirth-mailer' ) . '</a></p>';
		echo '<h1>' . esc_html( mmm_sub_email( $sub ) ) . ( $name ? ' <span class="mmm-pill">' . esc_html( $name ) . '</span>' : '' ) . ' <span class="mmm-pill ' . ( $active ? 'is-ok' : 'is-off' ) . '">' . ( $active ? esc_html__( 'Subscribed', 'mine-mirth-mailer' ) : esc_html__( 'Unsubscribed', 'mine-mirth-mailer' ) ) . '</span></h1>';
		echo '<div class="mmm-grid"><div class="mmm-card"><h2>' . esc_html__( 'Interests (last 60 days)', 'mine-mirth-mailer' ) . '</h2><p><strong>' . esc_html( $names[ $p['top'] ] ?? $p['top'] ) . '</strong></p><ul class="mmm-bars">';
		$max = max( 1, max( $p['areas'] ) );
		foreach ( array( 'shop' => __( 'Shop', 'mine-mirth-mailer' ), 'blog' => __( 'Blog', 'mine-mirth-mailer' ), 'tools' => __( 'Hosting & tools', 'mine-mirth-mailer' ), 'home' => __( 'Homepage', 'mine-mirth-mailer' ) ) as $k => $label ) {
			echo '<li><span>' . esc_html( $label ) . '</span><i style="width:' . (int) round( 100 * $p['areas'][ $k ] / $max ) . '%"></i><b>' . (int) $p['areas'][ $k ] . '</b></li>';
		}
		echo '</ul>';
		if ( $p['products'] ) {
			echo '<p><strong>' . esc_html__( 'Miners viewed:', 'mine-mirth-mailer' ) . '</strong> ' . esc_html( implode( ', ', array_map( 'get_the_title', array_slice( $p['products'], 0, 8 ) ) ) ) . '</p>';
		}
		if ( $p['purchases'] ) {
			echo '<p><strong>' . esc_html__( 'Bought:', 'mine-mirth-mailer' ) . '</strong> ' . esc_html( implode( ', ', array_map( 'get_the_title', $p['purchases'] ) ) ) . '</p>';
		}
		$phone = (string) get_post_meta( $sub, '_mmm_phone', true );
		echo $phone ? '<p><strong>' . esc_html__( 'Phone:', 'mine-mirth-mailer' ) . '</strong> ' . esc_html( $phone ) . '</p>' : '';
		echo '<p class="mmm-actions"><a class="button button-primary" href="' . esc_url( mmm_admin_url( 'mmm-inbox', array( 'sub' => $sub ) ) ) . '">' . esc_html__( 'Message them', 'mine-mirth-mailer' ) . '</a> <a class="button" href="' . esc_url( mmm_admin_url( 'mmm-weekly', array( 'sub' => $sub ) ) ) . '">' . esc_html__( 'Preview their weekly email', 'mine-mirth-mailer' ) . '</a> ';
		echo '<a class="button-link" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mmm_sub_status&sub=' . $sub . '&to=' . ( $active ? 'unsub' : 'resub' ) ), 'mmm_sub_status_' . $sub ) ) . '">' . ( $active ? esc_html__( 'Unsubscribe', 'mine-mirth-mailer' ) : esc_html__( 'Resubscribe', 'mine-mirth-mailer' ) ) . '</a></p></div>';
		echo '<div class="mmm-card"><h2>' . esc_html__( 'Activity', 'mine-mirth-mailer' ) . '</h2>';
		if ( ! $events ) {
			echo '<p class="description">' . esc_html__( 'Nothing yet. Activity is recorded once they sign up on this browser or click a link in one of your emails.', 'mine-mirth-mailer' ) . '</p>';
		}
		echo '<ul class="mmm-timeline">';
		foreach ( (array) $events as $ev ) {
			echo '<li class="is-' . esc_attr( $ev->type ) . '"><span>' . esc_html( mmm_event_label( $ev ) ) . '</span><time>' . esc_html( human_time_diff( (int) strtotime( $ev->created . ' UTC' ) ) ) . ' ' . esc_html__( 'ago', 'mine-mirth-mailer' ) . '</time></li>';
		}
		echo '</ul></div></div></div>';
		return;
	}

	$search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$paged  = max( 1, absint( wp_unslash( $_GET['paged'] ?? 1 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$q      = new WP_Query( array( 'post_type' => 'mm_subscriber', 'post_status' => 'any', 'posts_per_page' => 50, 'paged' => $paged, 's' => $search ) );
	echo '<h1>' . esc_html__( 'Subscribers', 'mine-mirth-mailer' ) . ' <span class="mmm-pill">' . esc_html( number_format_i18n( $q->found_posts ) ) . '</span></h1>';
	echo '<form method="get" class="mmm-search"><input type="hidden" name="page" value="mmm-subscribers"><input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Search by email', 'mine-mirth-mailer' ) . '"> <button class="button">' . esc_html__( 'Search', 'mine-mirth-mailer' ) . '</button> <a class="button-link" href="' . esc_url( admin_url( 'edit.php?post_type=mm_subscriber' ) ) . '">' . esc_html__( 'Export CSV', 'mine-mirth-mailer' ) . '</a></form>';
	echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Email', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'Status', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'Interested in', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'Last seen on site', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'Opened', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'Clicked', 'mine-mirth-mailer' ) . '</th><th>' . esc_html__( 'Joined', 'mine-mirth-mailer' ) . '</th></tr></thead><tbody>';
	$names = array( 'shop' => __( 'Shop', 'mine-mirth-mailer' ), 'blog' => __( 'Blog', 'mine-mirth-mailer' ), 'tools' => __( 'Hosting & tools', 'mine-mirth-mailer' ), 'home' => __( 'Homepage', 'mine-mirth-mailer' ), 'none' => '—' );
	foreach ( $q->posts as $s ) {
		$prof = mmm_profile( $s->ID );
		$seen = (int) get_post_meta( $s->ID, '_mmm_last_seen', true );
		$cnt  = $wpdb->get_row( $wpdb->prepare( "SELECT SUM(CASE WHEN type = 'open' THEN 1 ELSE 0 END) o, SUM(CASE WHEN type = 'click' THEN 1 ELSE 0 END) c FROM $e WHERE sub_id = %d", $s->ID ) ); // phpcs:ignore WordPress.DB
		echo '<tr><td><a href="' . esc_url( mmm_admin_url( 'mmm-subscribers', array( 'sub' => $s->ID ) ) ) . '"><strong>' . esc_html( $s->post_title ) . '</strong></a></td>';
		echo '<td>' . ( mmm_sub_is_active( $s->ID ) ? esc_html__( 'Subscribed', 'mine-mirth-mailer' ) : '<span style="color:#d63638">' . esc_html__( 'Unsubscribed', 'mine-mirth-mailer' ) . '</span>' ) . '</td>';
		echo '<td>' . esc_html( $names[ $prof['top'] ] ?? '—' ) . '</td><td>' . ( $seen ? esc_html( human_time_diff( $seen ) . ' ' . __( 'ago', 'mine-mirth-mailer' ) ) : '—' ) . '</td>';
		echo '<td>' . esc_html( (string) (int) ( $cnt->o ?? 0 ) ) . '</td><td>' . esc_html( (string) (int) ( $cnt->c ?? 0 ) ) . '</td><td>' . esc_html( get_the_date( '', $s ) ) . '</td></tr>';
	}
	echo '</tbody></table>';
	if ( $q->max_num_pages > 1 ) {
		echo '<p>' . wp_kses_post( (string) paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => $q->max_num_pages ) ) ) . '</p>';
	}
	echo '</div>';
}

/* ---------------------------------------------------------------------------
 * Settings
 * ------------------------------------------------------------------------- */

function mmm_sanitize_settings( $in ): array {
	$in  = is_array( $in ) ? $in : array();
	$d   = mmm_defaults();
	$old = get_option( 'mmm_settings', array() );
	$out = array(
		'from_name'      => sanitize_text_field( (string) ( $in['from_name'] ?? $d['from_name'] ) ),
		'from_email'     => is_email( (string) ( $in['from_email'] ?? '' ) ) ? sanitize_email( (string) $in['from_email'] ) : '',
		'reply_to'       => is_email( (string) ( $in['reply_to'] ?? '' ) ) ? sanitize_email( (string) $in['reply_to'] ) : $d['reply_to'],
		'address'        => sanitize_text_field( (string) ( $in['address'] ?? '' ) ),
		'batch'          => max( 1, min( 200, (int) ( $in['batch'] ?? 25 ) ) ),
		'track_site'     => empty( $in['track_site'] ) ? 0 : 1,
		'track_opens'    => empty( $in['track_opens'] ) ? 0 : 1,
		'track_clicks'   => empty( $in['track_clicks'] ) ? 0 : 1,
		'logo_id'        => absint( $in['logo_id'] ?? 0 ),
		'brand_name'     => sanitize_text_field( (string) ( $in['brand_name'] ?? $d['brand_name'] ) ),
		'footer_note'    => sanitize_text_field( (string) ( $in['footer_note'] ?? $d['footer_note'] ) ),
		'notify_replies' => empty( $in['notify_replies'] ) ? 0 : 1,
		'imap_host'      => sanitize_text_field( (string) ( $in['imap_host'] ?? '' ) ),
		'imap_port'      => absint( $in['imap_port'] ?? 993 ) ? absint( $in['imap_port'] ) : 993,
		'imap_secure'    => in_array( $in['imap_secure'] ?? 'ssl', array( 'ssl', 'tls', 'none' ), true ) ? $in['imap_secure'] : 'ssl',
		'imap_user'      => sanitize_text_field( (string) ( $in['imap_user'] ?? '' ) ),
		'imap_folder'    => sanitize_text_field( (string) ( $in['imap_folder'] ?? 'INBOX' ) ),
	);
	// Password: keep the stored one unless a new one is typed.
	$pass               = (string) ( $in['imap_pass'] ?? '' );
	$out['imap_pass']   = '' !== $pass ? mmm_crypt( $pass ) : (string) ( $old['imap_pass'] ?? '' );
	return $out;
}

add_action(
	'admin_post_mmm_imap_check',
	static function () {
		check_admin_referer( 'mmm_imap_check' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-mailer' ) );
		}
		mmm_imap_fetch();
		wp_safe_redirect( mmm_admin_url( 'mmm-settings', array( 'imap' => 1 ) ) );
		exit;
	}
);

// The checklist's "test sent" tick.
add_action(
	'admin_post_mmm_send',
	static function () {
		if ( 'test' === sanitize_key( wp_unslash( $_POST['do'] ?? '' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked by the main handler.
			update_option( 'mmm_test_sent', 1, false );
		}
	},
	1
);

function mmm_settings_page(): void {
	wp_enqueue_media();
	$o      = static fn( $k ) => mmm_opt( $k );
	$status = get_option( 'mmm_imap_status', array() );
	$logo   = (int) $o( 'logo_id' );
	?>
	<div class="wrap mmm-admin">
		<h1><?php esc_html_e( 'Mailer settings', 'mine-mirth-mailer' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'mmm_settings' ); ?>
			<div class="mmm-card">
				<h2><?php esc_html_e( 'Sender', 'mine-mirth-mailer' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th><label for="mmm-fn"><?php esc_html_e( 'From name', 'mine-mirth-mailer' ); ?></label></th><td><input id="mmm-fn" class="regular-text" name="mmm_settings[from_name]" value="<?php echo esc_attr( (string) $o( 'from_name' ) ); ?>"></td></tr>
					<tr><th><label for="mmm-fe"><?php esc_html_e( 'From email', 'mine-mirth-mailer' ); ?></label></th><td><input id="mmm-fe" type="email" class="regular-text" name="mmm_settings[from_email]" value="<?php echo esc_attr( (string) $o( 'from_email' ) ); ?>" placeholder="news@minemirth.com"><p class="description"><?php esc_html_e( 'Use an address at your own domain. Leave empty to use your SMTP plugin’s setting.', 'mine-mirth-mailer' ); ?></p></td></tr>
					<tr><th><label for="mmm-rt"><?php esc_html_e( 'Replies go to', 'mine-mirth-mailer' ); ?></label></th><td><input id="mmm-rt" type="email" class="regular-text" name="mmm_settings[reply_to]" value="<?php echo esc_attr( (string) $o( 'reply_to' ) ); ?>"><p class="description"><?php esc_html_e( 'When someone hits Reply in their mail app. Messages sent through the “Ask us anything” button land in Mailer › Inbox.', 'mine-mirth-mailer' ); ?></p></td></tr>
					<tr><th><label for="mmm-ad"><?php esc_html_e( 'Business postal address', 'mine-mirth-mailer' ); ?></label></th><td><input id="mmm-ad" class="large-text" name="mmm_settings[address]" value="<?php echo esc_attr( (string) $o( 'address' ) ); ?>" placeholder="Mine Mirth, LLC · Street, City, State ZIP · Country"><p class="description"><?php esc_html_e( 'Required in marketing emails (CAN-SPAM). Shown in the footer.', 'mine-mirth-mailer' ); ?></p></td></tr>
					<tr><th><label for="mmm-b"><?php esc_html_e( 'Emails per minute', 'mine-mirth-mailer' ); ?></label></th><td><input id="mmm-b" type="number" min="1" max="200" class="small-text" name="mmm_settings[batch]" value="<?php echo esc_attr( (string) $o( 'batch' ) ); ?>"><p class="description"><?php esc_html_e( 'Keep this within your email provider’s sending limit.', 'mine-mirth-mailer' ); ?></p></td></tr>
				</table>
			</div>
			<div class="mmm-card">
				<h2><?php esc_html_e( 'Design', 'mine-mirth-mailer' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Colours and fonts come from your theme (Customize › Mine Mirth · Design System), so emails always match the site.', 'mine-mirth-mailer' ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Email logo', 'mine-mirth-mailer' ); ?></th><td>
						<input type="hidden" id="mmm-logo" name="mmm_settings[logo_id]" value="<?php echo esc_attr( (string) $logo ); ?>">
						<span id="mmm-logo-preview" class="mmm-logo-preview"><?php echo $logo ? wp_get_attachment_image( $logo, 'medium' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<button type="button" class="button" id="mmm-logo-pick"><?php esc_html_e( 'Choose logo', 'mine-mirth-mailer' ); ?></button>
						<button type="button" class="button-link" id="mmm-logo-clear"><?php esc_html_e( 'Use the site logo', 'mine-mirth-mailer' ); ?></button>
						<p class="description"><?php esc_html_e( 'Shown on a dark background. Empty uses your dark-mode logo, then the site logo.', 'mine-mirth-mailer' ); ?></p>
					</td></tr>
					<tr><th><label for="mmm-bn"><?php esc_html_e( 'Newsletter name', 'mine-mirth-mailer' ); ?></label></th><td><input id="mmm-bn" class="regular-text" name="mmm_settings[brand_name]" value="<?php echo esc_attr( (string) $o( 'brand_name' ) ); ?>"></td></tr>
					<tr><th><label for="mmm-ft"><?php esc_html_e( 'Footer note', 'mine-mirth-mailer' ); ?></label></th><td><input id="mmm-ft" class="large-text" name="mmm_settings[footer_note]" value="<?php echo esc_attr( (string) $o( 'footer_note' ) ); ?>"></td></tr>
				</table>
			</div>
			<div class="mmm-card">
				<h2><?php esc_html_e( 'Tracking', 'mine-mirth-mailer' ); ?></h2>
				<p><label><input type="checkbox" name="mmm_settings[track_site]" value="1" <?php checked( (int) $o( 'track_site' ), 1 ); ?>> <?php esc_html_e( 'Remember which pages subscribers visit, to personalise the weekly email', 'mine-mirth-mailer' ); ?></label><br>
				<label><input type="checkbox" name="mmm_settings[track_opens]" value="1" <?php checked( (int) $o( 'track_opens' ), 1 ); ?>> <?php esc_html_e( 'Count opens', 'mine-mirth-mailer' ); ?></label><br>
				<label><input type="checkbox" name="mmm_settings[track_clicks]" value="1" <?php checked( (int) $o( 'track_clicks' ), 1 ); ?>> <?php esc_html_e( 'Count clicks', 'mine-mirth-mailer' ); ?></label></p>
				<p class="description"><?php esc_html_e( 'Suggested privacy policy text is in Settings › Privacy › Policy guide.', 'mine-mirth-mailer' ); ?></p>
			</div>
			<div class="mmm-card">
				<h2><?php esc_html_e( 'Replies', 'mine-mirth-mailer' ); ?></h2>
				<p><label><input type="checkbox" name="mmm_settings[notify_replies]" value="1" <?php checked( (int) $o( 'notify_replies' ), 1 ); ?>> <?php esc_html_e( 'Email me when someone sends a message from an email', 'mine-mirth-mailer' ); ?></label></p>
				<h3><?php esc_html_e( 'Import email replies from your mailbox (optional)', 'mine-mirth-mailer' ); ?></h3>
				<p class="description"><?php esc_html_e( 'When subscribers hit Reply in their mail app, their answer goes to the “Replies go to” mailbox. Connect that mailbox here and replies from subscribers are copied into the Inbox every 15 minutes. Other mail is left untouched.', 'mine-mirth-mailer' ); ?></p>
				<?php if ( ! function_exists( 'imap_open' ) ) : ?>
					<p class="notice notice-warning inline" style="padding:8px 12px"><?php esc_html_e( 'Your server’s PHP does not have the IMAP extension, so this can’t be used here. Ask your host to enable "php-imap", or rely on the “Ask us anything” button, which always works.', 'mine-mirth-mailer' ); ?></p>
				<?php endif; ?>
				<table class="form-table" role="presentation">
					<tr><th><label for="mmm-ih"><?php esc_html_e( 'IMAP server', 'mine-mirth-mailer' ); ?></label></th><td><input id="mmm-ih" class="regular-text" name="mmm_settings[imap_host]" value="<?php echo esc_attr( (string) $o( 'imap_host' ) ); ?>" placeholder="imap.gmail.com"> <input type="number" class="small-text" name="mmm_settings[imap_port]" value="<?php echo esc_attr( (string) $o( 'imap_port' ) ); ?>" aria-label="<?php esc_attr_e( 'Port', 'mine-mirth-mailer' ); ?>">
						<select name="mmm_settings[imap_secure]" aria-label="<?php esc_attr_e( 'Security', 'mine-mirth-mailer' ); ?>"><?php foreach ( array( 'ssl' => 'SSL', 'tls' => 'TLS', 'none' => __( 'None', 'mine-mirth-mailer' ) ) as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( (string) $o( 'imap_secure' ), $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></td></tr>
					<tr><th><label for="mmm-iu"><?php esc_html_e( 'Username', 'mine-mirth-mailer' ); ?></label></th><td><input id="mmm-iu" class="regular-text" name="mmm_settings[imap_user]" value="<?php echo esc_attr( (string) $o( 'imap_user' ) ); ?>" autocomplete="off"></td></tr>
					<tr><th><label for="mmm-ip"><?php esc_html_e( 'Password', 'mine-mirth-mailer' ); ?></label></th><td><input id="mmm-ip" type="password" class="regular-text" name="mmm_settings[imap_pass]" value="" autocomplete="new-password" placeholder="<?php echo $o( 'imap_pass' ) ? esc_attr__( 'Saved — type to change', 'mine-mirth-mailer' ) : ''; ?>"><p class="description"><?php esc_html_e( 'Use an app password (Gmail, Outlook, Zoho). Stored encrypted.', 'mine-mirth-mailer' ); ?></p></td></tr>
					<tr><th><label for="mmm-if"><?php esc_html_e( 'Folder', 'mine-mirth-mailer' ); ?></label></th><td><input id="mmm-if" class="regular-text" name="mmm_settings[imap_folder]" value="<?php echo esc_attr( (string) $o( 'imap_folder' ) ); ?>"></td></tr>
				</table>
				<?php if ( $status ) : ?>
					<p><?php echo ! empty( $status['ok'] ) ? esc_html( sprintf( /* translators: 1: time 2: count */ __( 'Last checked %1$s ago: %2$d new replies imported.', 'mine-mirth-mailer' ), human_time_diff( (int) $status['at'] ), (int) ( $status['imported'] ?? 0 ) ) ) : '<span style="color:#d63638">' . esc_html( __( 'Could not connect:', 'mine-mirth-mailer' ) . ' ' . (string) $status['error'] ) . '</span>'; ?></p>
				<?php endif; ?>
			</div>
			<?php submit_button(); ?>
		</form>
		<?php if ( mmm_imap_ready() ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="mmm_imap_check"><?php wp_nonce_field( 'mmm_imap_check' ); ?><button class="button"><?php esc_html_e( 'Check the mailbox now', 'mine-mirth-mailer' ); ?></button></form>
		<?php endif; ?>
	</div>
	<script>
	(function () {
		var pick = document.getElementById('mmm-logo-pick'), clear = document.getElementById('mmm-logo-clear'), input = document.getElementById('mmm-logo'), prev = document.getElementById('mmm-logo-preview');
		if (!pick || !window.wp || !wp.media) { return; }
		var frame;
		pick.addEventListener('click', function () {
			frame = frame || wp.media({ title: pick.textContent, library: { type: 'image' }, multiple: false });
			frame.off('select').on('select', function () {
				var a = frame.state().get('selection').first().toJSON();
				input.value = a.id;
				prev.innerHTML = '';
				var img = document.createElement('img');
				img.src = (a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url);
				prev.appendChild(img);
			});
			frame.open();
		});
		clear.addEventListener('click', function () { input.value = 0; prev.innerHTML = ''; });
	})();
	</script>
	<?php
}
