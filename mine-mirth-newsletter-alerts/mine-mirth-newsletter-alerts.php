<?php
/**
 * Plugin Name:       Mine Mirth Newsletter Alerts
 * Description:       Emails you whenever someone signs up for the Mine Mirth newsletter, and fixes sign-ups failing on cached pages. Works with the Mine Mirth theme as installed. Settings: Promotions › Sign-up alerts.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-newsletter-alerts
 *
 * Why this exists: the theme saves each subscriber (Promotions › Subscribers)
 * and fires the `mm_new_subscriber` action, but nothing sends an email. Also,
 * its sign-up form carries a security token printed into the page; when a
 * cache serves an older copy of the page, the token has expired and WordPress
 * rejects the sign-up ("Cookie check failed"), so the subscriber is never saved.
 *
 * @package MineMirthNewsletterAlerts
 */

defined( 'ABSPATH' ) || exit;

define( 'MMNA_VERSION', '1.0.0' );

/* ---------------------------------------------------------------------------
 * Settings
 * ------------------------------------------------------------------------- */

function mmna_defaults(): array {
	return array(
		'recipients'    => get_option( 'admin_email' ),
		'mode'          => 'instant',
		'welcome'       => 0,
		'welcome_subj'  => __( 'Welcome to The Miner’s Brief', 'mine-mirth-newsletter-alerts' ),
		'welcome_body'  => __( "Thanks for subscribing to The Miner’s Brief from Mine Mirth.\n\nEach week you’ll get network stats, hardware moves and practical mining tips.\n\nIf you didn’t sign up, just ignore this email.", 'mine-mirth-newsletter-alerts' ),
		'from_name'     => '',
		'from_email'    => '',
		'fix_cache'     => 1,
	);
}

function mmna_opt( string $key ) {
	$all = get_option( 'mmna_settings', array() );
	$def = mmna_defaults();
	return is_array( $all ) && array_key_exists( $key, $all ) ? $all[ $key ] : ( $def[ $key ] ?? null );
}

/**
 * Valid alert recipients.
 *
 * @return string[]
 */
function mmna_recipients(): array {
	$list = array_filter( array_map( 'trim', preg_split( '/[\s,;]+/', (string) mmna_opt( 'recipients' ) ) ) );
	$list = array_values( array_filter( $list, 'is_email' ) );
	return $list ? $list : array( get_option( 'admin_email' ) );
}

/* ---------------------------------------------------------------------------
 * Sending, with a log of every attempt
 * ------------------------------------------------------------------------- */

$GLOBALS['mmna_last_error'] = '';
add_action(
	'wp_mail_failed',
	static function ( $error ) {
		if ( is_wp_error( $error ) ) {
			$GLOBALS['mmna_last_error'] = $error->get_error_message();
		}
	}
);

function mmna_log( string $kind, string $subject, array $to, bool $ok, string $error = '' ): void {
	$log   = get_option( 'mmna_log', array() );
	$log   = is_array( $log ) ? $log : array();
	array_unshift(
		$log,
		array(
			'at'      => time(),
			'kind'    => $kind,
			'subject' => $subject,
			'to'      => implode( ', ', $to ),
			'ok'      => $ok,
			'error'   => $error,
		)
	);
	update_option( 'mmna_log', array_slice( $log, 0, 50 ), false );
	update_option( 'mmna_last_status', array( 'ok' => $ok, 'error' => $error, 'at' => time() ), false );
}

/**
 * Send one email and record the outcome.
 */
function mmna_send( string $kind, array $to, string $subject, string $body, array $headers = array() ): bool {
	$from_email = sanitize_email( (string) mmna_opt( 'from_email' ) );
	$from_name  = sanitize_text_field( (string) mmna_opt( 'from_name' ) );
	if ( $from_email && is_email( $from_email ) ) {
		$headers[] = 'From: ' . ( $from_name ? $from_name : get_bloginfo( 'name' ) ) . ' <' . $from_email . '>';
	}
	$headers[]                  = 'Content-Type: text/plain; charset=UTF-8';
	$GLOBALS['mmna_last_error'] = '';
	$ok                         = (bool) wp_mail( $to, $subject, $body, $headers );
	$error                      = $ok ? '' : ( $GLOBALS['mmna_last_error'] ? $GLOBALS['mmna_last_error'] : __( 'The server did not accept the email.', 'mine-mirth-newsletter-alerts' ) );
	mmna_log( $kind, $subject, $to, $ok, $error );
	return $ok;
}

/**
 * Friendly name for where the form was.
 */
function mmna_context_label( string $context ): string {
	$labels = array(
		'footer'    => __( 'Footer form', 'mine-mirth-newsletter-alerts' ),
		'home'      => __( 'Homepage newsletter section', 'mine-mirth-newsletter-alerts' ),
		'article'   => __( 'Blog article', 'mine-mirth-newsletter-alerts' ),
		'shortcode' => __( 'Newsletter shortcode', 'mine-mirth-newsletter-alerts' ),
		'general'   => __( 'Newsletter form', 'mine-mirth-newsletter-alerts' ),
	);
	if ( str_starts_with( $context, 'promo-' ) ) {
		$where = array(
			'inline'  => __( 'inside an article', 'mine-mirth-newsletter-alerts' ),
			'sidebar' => __( 'article sidebar', 'mine-mirth-newsletter-alerts' ),
			'end'     => __( 'end of an article', 'mine-mirth-newsletter-alerts' ),
			'slidein' => __( 'slide-in', 'mine-mirth-newsletter-alerts' ),
		);
		$spot = substr( $context, 6 );
		/* translators: %s: where in the blog */
		return sprintf( __( 'Blog promotion (%s)', 'mine-mirth-newsletter-alerts' ), $where[ $spot ] ?? str_replace( '-', ' ', $spot ) );
	}
	return $labels[ $context ] ?? ( $context ? ucfirst( str_replace( array( '-', '_' ), ' ', $context ) ) : $labels['general'] );
}

function mmna_subscriber_count(): int {
	$counts = wp_count_posts( 'mm_subscriber' );
	return $counts ? (int) array_sum( array_map( 'intval', (array) $counts ) ) : 0;
}

function mmna_subscribers_url(): string {
	return admin_url( 'edit.php?post_type=mm_subscriber' );
}

/* ---------------------------------------------------------------------------
 * New sign-up
 * ------------------------------------------------------------------------- */

add_action(
	'mm_new_subscriber',
	static function ( $email, $id, $context ) {
		$email   = sanitize_email( (string) $email );
		$id      = (int) $id;
		$context = sanitize_key( (string) $context );
		$page    = wp_get_referer();
		if ( ! $page && ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			$page = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		}
		if ( $id ) {
			update_post_meta( $id, '_mmna_page', $page ? $page : '' );
		}

		if ( 'instant' === mmna_opt( 'mode' ) ) {
			mmna_alert( $email, $id, $context, (string) $page );
		}

		if ( mmna_opt( 'welcome' ) && is_email( $email ) ) {
			$name = get_bloginfo( 'name' );
			mmna_send( 'welcome', array( $email ), (string) mmna_opt( 'welcome_subj' ), (string) mmna_opt( 'welcome_body' ) . "\n\n— " . $name . "\n" . home_url( '/' ) );
		}
	},
	10,
	3
);

/**
 * The alert email for one sign-up.
 */
function mmna_alert( string $email, int $id, string $context, string $page ): bool {
	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	/* translators: 1: site name 2: email */
	$subject = sprintf( __( '[%1$s] New newsletter sign-up: %2$s', 'mine-mirth-newsletter-alerts' ), $site, $email );
	$lines   = array(
		__( 'Someone just subscribed to your newsletter.', 'mine-mirth-newsletter-alerts' ),
		'',
		__( 'Email:', 'mine-mirth-newsletter-alerts' ) . ' ' . $email,
		__( 'Signed up from:', 'mine-mirth-newsletter-alerts' ) . ' ' . mmna_context_label( $context ),
	);
	if ( $page ) {
		$lines[] = __( 'Page:', 'mine-mirth-newsletter-alerts' ) . ' ' . $page;
	}
	$lines[] = __( 'Date:', 'mine-mirth-newsletter-alerts' ) . ' ' . wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
	$lines[] = __( 'Total subscribers:', 'mine-mirth-newsletter-alerts' ) . ' ' . number_format_i18n( mmna_subscriber_count() );
	$lines[] = '';
	$lines[] = __( 'All subscribers (with CSV export):', 'mine-mirth-newsletter-alerts' ) . ' ' . mmna_subscribers_url();
	$ok      = mmna_send( 'alert', mmna_recipients(), $subject, implode( "\n", $lines ), is_email( $email ) ? array( 'Reply-To: ' . $email ) : array() );
	if ( $ok && $id ) {
		update_post_meta( $id, '_mmna_notified', time() );
	}
	return $ok;
}

/* ---------------------------------------------------------------------------
 * Daily summary (optional)
 * ------------------------------------------------------------------------- */

add_action(
	'init',
	static function () {
		$want = 'daily' === mmna_opt( 'mode' );
		$next = wp_next_scheduled( 'mmna_daily_digest' );
		if ( $want && ! $next ) {
			wp_schedule_event( strtotime( 'tomorrow 08:00' ) - (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ), 'daily', 'mmna_daily_digest' );
		} elseif ( ! $want && $next ) {
			wp_clear_scheduled_hook( 'mmna_daily_digest' );
		}
	}
);

add_action(
	'mmna_daily_digest',
	static function () {
		$ids = get_posts(
			array(
				'post_type'      => 'mm_subscriber',
				'post_status'    => 'any',
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'meta_query'     => array( array( 'key' => '_mmna_notified', 'compare' => 'NOT EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
				'date_query'     => array( array( 'after' => '2 days ago' ) ),
			)
		);
		if ( ! $ids ) {
			return;
		}
		$site  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$lines = array();
		foreach ( $ids as $id ) {
			$lines[] = '• ' . get_the_title( $id ) . ' — ' . mmna_context_label( (string) get_post_meta( $id, '_mm_context', true ) ) . ' — ' . get_the_date( get_option( 'time_format' ), $id );
		}
		/* translators: 1: site name 2: count */
		$subject = sprintf( _n( '[%1$s] %2$d new newsletter sign-up', '[%1$s] %2$d new newsletter sign-ups', count( $ids ), 'mine-mirth-newsletter-alerts' ), $site, count( $ids ) );
		$body    = implode( "\n", $lines ) . "\n\n" . __( 'Total subscribers:', 'mine-mirth-newsletter-alerts' ) . ' ' . number_format_i18n( mmna_subscriber_count() ) . "\n" . mmna_subscribers_url();
		if ( mmna_send( 'digest', mmna_recipients(), $subject, $body ) ) {
			foreach ( $ids as $id ) {
				update_post_meta( $id, '_mmna_notified', time() );
			}
		}
	}
);

/* ---------------------------------------------------------------------------
 * Fix: sign-ups failing on cached pages
 * ------------------------------------------------------------------------- */

/**
 * Is this request the theme's sign-up endpoint?
 */
function mmna_is_subscribe_request(): bool {
	$uri   = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$route = isset( $_GET['rest_route'] ) ? (string) wp_unslash( $_GET['rest_route'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
	return str_contains( $uri, '/mine-mirth/v1/subscribe' ) || str_contains( $route, '/mine-mirth/v1/subscribe' );
}

// The sign-up endpoint is public and needs no login, so an expired token from a cached page must not block it.
add_filter(
	'rest_authentication_errors',
	static function ( $result ) {
		if ( mmna_opt( 'fix_cache' ) && is_wp_error( $result ) && 'rest_cookie_invalid_nonce' === $result->get_error_code() && mmna_is_subscribe_request() ) {
			wp_set_current_user( 0 );
			return null;
		}
		return $result;
	},
	200
);

// Same for the form when JavaScript is off: accept it even if the page's token has expired.
// The theme's honeypot and rate limit still apply.
$mmna_form_fix = static function () {
	if ( ! mmna_opt( 'fix_cache' ) || ! function_exists( 'mm_newsletter_store' ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- this handles exactly the case where the cached token has expired.
	$nonce = isset( $_POST['mm_subscribe_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mm_subscribe_nonce'] ) ) : '';
	if ( $nonce && wp_verify_nonce( $nonce, 'mm_subscribe' ) ) {
		return; // Valid token: the theme handles it as usual.
	}
	$redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : home_url( '/' );
	$redirect = wp_validate_redirect( $redirect, home_url( '/' ) );
	if ( ! empty( $_POST['website'] ) ) {
		wp_safe_redirect( $redirect );
		exit;
	}
	list( $ok ) = mm_newsletter_store( isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '', isset( $_POST['context'] ) ? sanitize_key( wp_unslash( $_POST['context'] ) ) : 'general' );
	// phpcs:enable
	wp_safe_redirect( $ok ? add_query_arg( 'mm_subscribed', '1', $redirect ) : $redirect );
	exit;
};
add_action( 'admin_post_mm_subscribe', $mmna_form_fix, 1 );
add_action( 'admin_post_nopriv_mm_subscribe', $mmna_form_fix, 1 );

/* ---------------------------------------------------------------------------
 * Admin: settings, test email, log
 * ------------------------------------------------------------------------- */

add_action(
	'admin_menu',
	static function () {
		$parent = post_type_exists( 'mm_promo' ) ? 'edit.php?post_type=mm_promo' : 'options-general.php';
		add_submenu_page( $parent, __( 'Newsletter sign-up alerts', 'mine-mirth-newsletter-alerts' ), __( 'Sign-up alerts', 'mine-mirth-newsletter-alerts' ), 'manage_options', 'mmna', 'mmna_page' );
	},
	20
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	static function ( $links ) {
		$url = post_type_exists( 'mm_promo' ) ? admin_url( 'edit.php?post_type=mm_promo&page=mmna' ) : admin_url( 'options-general.php?page=mmna' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'mine-mirth-newsletter-alerts' ) . '</a>' );
		return $links;
	}
);

function mmna_page_url( array $args = array() ): string {
	$base = post_type_exists( 'mm_promo' ) ? admin_url( 'edit.php?post_type=mm_promo&page=mmna' ) : admin_url( 'options-general.php?page=mmna' );
	return add_query_arg( $args, $base );
}

add_action(
	'admin_init',
	static function () {
		register_setting(
			'mmna',
			'mmna_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => static function ( $in ) {
					$in  = is_array( $in ) ? $in : array();
					$d   = mmna_defaults();
					$rec = array_values( array_filter( array_map( 'sanitize_email', preg_split( '/[\s,;]+/', (string) ( $in['recipients'] ?? '' ) ) ), 'is_email' ) );
					return array(
						'recipients'   => $rec ? implode( ', ', $rec ) : $d['recipients'],
						'mode'         => in_array( $in['mode'] ?? '', array( 'instant', 'daily', 'off' ), true ) ? $in['mode'] : 'instant',
						'welcome'      => empty( $in['welcome'] ) ? 0 : 1,
						'welcome_subj' => sanitize_text_field( (string) ( $in['welcome_subj'] ?? $d['welcome_subj'] ) ),
						'welcome_body' => sanitize_textarea_field( (string) ( $in['welcome_body'] ?? $d['welcome_body'] ) ),
						'from_name'    => sanitize_text_field( (string) ( $in['from_name'] ?? '' ) ),
						'from_email'   => is_email( (string) ( $in['from_email'] ?? '' ) ) ? sanitize_email( (string) $in['from_email'] ) : '',
						'fix_cache'    => empty( $in['fix_cache'] ) ? 0 : 1,
					);
				},
			)
		);
	}
);

add_action(
	'admin_post_mmna_test',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-newsletter-alerts' ) );
		}
		check_admin_referer( 'mmna_test' );
		$ok = mmna_alert( 'test-subscriber@example.com', 0, 'footer', home_url( '/' ) );
		wp_safe_redirect( mmna_page_url( array( 'mmna_test' => $ok ? 'ok' : 'fail' ) ) );
		exit;
	}
);

add_action(
	'admin_post_mmna_resend',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-newsletter-alerts' ) );
		}
		$id = absint( wp_unslash( $_GET['id'] ?? 0 ) );
		check_admin_referer( 'mmna_resend_' . $id );
		$ok = 'mm_subscriber' === get_post_type( $id ) && mmna_alert( get_the_title( $id ), $id, (string) get_post_meta( $id, '_mm_context', true ), (string) get_post_meta( $id, '_mmna_page', true ) );
		wp_safe_redirect( mmna_page_url( array( 'mmna_test' => $ok ? 'ok' : 'fail' ) ) );
		exit;
	}
);

// Warn on the dashboard when alerts are failing, so sign-ups are never missed silently.
add_action(
	'admin_notices',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$last = get_option( 'mmna_last_status', array() );
		if ( is_array( $last ) && isset( $last['ok'] ) && ! $last['ok'] ) {
			echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Newsletter sign-up alerts are not being delivered.', 'mine-mirth-newsletter-alerts' ) . '</strong> ' . esc_html( (string) $last['error'] ) . ' <a href="' . esc_url( mmna_page_url() ) . '">' . esc_html__( 'See how to fix it', 'mine-mirth-newsletter-alerts' ) . '</a></p></div>';
		}
	}
);

// Dashboard box with the latest sign-ups.
add_action(
	'wp_dashboard_setup',
	static function () {
		if ( ! current_user_can( 'manage_options' ) || ! post_type_exists( 'mm_subscriber' ) ) {
			return;
		}
		wp_add_dashboard_widget(
			'mmna_dashboard',
			__( 'Newsletter sign-ups', 'mine-mirth-newsletter-alerts' ),
			static function () {
				$week = count( get_posts( array( 'post_type' => 'mm_subscriber', 'post_status' => 'any', 'posts_per_page' => 1000, 'fields' => 'ids', 'date_query' => array( array( 'after' => '7 days ago' ) ) ) ) );
				echo '<p><strong>' . esc_html( number_format_i18n( mmna_subscriber_count() ) ) . '</strong> ' . esc_html__( 'subscribers', 'mine-mirth-newsletter-alerts' ) . ' · <strong>' . esc_html( number_format_i18n( $week ) ) . '</strong> ' . esc_html__( 'in the last 7 days', 'mine-mirth-newsletter-alerts' ) . '</p><ul>';
				foreach ( get_posts( array( 'post_type' => 'mm_subscriber', 'post_status' => 'any', 'posts_per_page' => 5 ) ) as $p ) {
					echo '<li>' . esc_html( $p->post_title ) . ' <span style="color:#646970">— ' . esc_html( human_time_diff( get_post_timestamp( $p ) ) ) . ' ' . esc_html__( 'ago', 'mine-mirth-newsletter-alerts' ) . '</span></li>';
				}
				echo '</ul><p><a href="' . esc_url( mmna_subscribers_url() ) . '">' . esc_html__( 'All subscribers', 'mine-mirth-newsletter-alerts' ) . '</a> · <a href="' . esc_url( mmna_page_url() ) . '">' . esc_html__( 'Alert settings', 'mine-mirth-newsletter-alerts' ) . '</a></p>';
			}
		);
	}
);

function mmna_page(): void {
	$o    = static fn( $k ) => mmna_opt( $k );
	$log  = get_option( 'mmna_log', array() );
	$test = isset( $_GET['mmna_test'] ) ? sanitize_key( wp_unslash( $_GET['mmna_test'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$prov = function_exists( 'mm_opt' ) ? (string) mm_opt( 'newsletter_provider' ) : 'local';
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Newsletter sign-up alerts', 'mine-mirth-newsletter-alerts' ); ?></h1>

		<?php if ( 'ok' === $test ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sprintf( /* translators: %s: recipients */ __( 'Alert sent to %s. If it isn’t in your inbox within a few minutes, check spam, then see "Not receiving emails?" below.', 'mine-mirth-newsletter-alerts' ), implode( ', ', mmna_recipients() ) ) ); ?></p></div>
		<?php elseif ( 'fail' === $test ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Your server could not send the email. See "Not receiving emails?" below.', 'mine-mirth-newsletter-alerts' ); ?></p></div>
		<?php endif; ?>

		<?php if ( 'local' !== $prov && '' !== $prov ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Your newsletter forms send sign-ups to an external service (Customize › Mine Mirth · Pages, Data & Advanced › Newsletter forms), so they are not saved in WordPress and no alert can be sent from here. That service sends its own notifications. Switch "Where signups go" to "Save in WordPress" to use these alerts.', 'mine-mirth-newsletter-alerts' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'mmna' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th><label for="mmna-to"><?php esc_html_e( 'Send alerts to', 'mine-mirth-newsletter-alerts' ); ?></label></th>
					<td><input id="mmna-to" type="text" class="regular-text" name="mmna_settings[recipients]" value="<?php echo esc_attr( implode( ', ', mmna_recipients() ) ); ?>"><p class="description"><?php esc_html_e( 'One or more email addresses, separated by commas.', 'mine-mirth-newsletter-alerts' ); ?></p></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'When', 'mine-mirth-newsletter-alerts' ); ?></th>
					<td>
						<label><input type="radio" name="mmna_settings[mode]" value="instant" <?php checked( $o( 'mode' ), 'instant' ); ?>> <?php esc_html_e( 'Right away, one email per sign-up', 'mine-mirth-newsletter-alerts' ); ?></label><br>
						<label><input type="radio" name="mmna_settings[mode]" value="daily" <?php checked( $o( 'mode' ), 'daily' ); ?>> <?php esc_html_e( 'Once a day, a summary of new sign-ups (8:00)', 'mine-mirth-newsletter-alerts' ); ?></label><br>
						<label><input type="radio" name="mmna_settings[mode]" value="off" <?php checked( $o( 'mode' ), 'off' ); ?>> <?php esc_html_e( 'Don’t email me', 'mine-mirth-newsletter-alerts' ); ?></label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Welcome email', 'mine-mirth-newsletter-alerts' ); ?></th>
					<td>
						<label><input type="checkbox" name="mmna_settings[welcome]" value="1" <?php checked( (int) $o( 'welcome' ), 1 ); ?>> <?php esc_html_e( 'Also send new subscribers a welcome email', 'mine-mirth-newsletter-alerts' ); ?></label>
						<p><input type="text" class="large-text" name="mmna_settings[welcome_subj]" value="<?php echo esc_attr( (string) $o( 'welcome_subj' ) ); ?>" aria-label="<?php esc_attr_e( 'Welcome email subject', 'mine-mirth-newsletter-alerts' ); ?>"></p>
						<p><textarea class="large-text" rows="5" name="mmna_settings[welcome_body]" aria-label="<?php esc_attr_e( 'Welcome email message', 'mine-mirth-newsletter-alerts' ); ?>"><?php echo esc_textarea( (string) $o( 'welcome_body' ) ); ?></textarea></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Send from (optional)', 'mine-mirth-newsletter-alerts' ); ?></th>
					<td>
						<input type="text" name="mmna_settings[from_name]" value="<?php echo esc_attr( (string) $o( 'from_name' ) ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" aria-label="<?php esc_attr_e( 'From name', 'mine-mirth-newsletter-alerts' ); ?>">
						<input type="email" name="mmna_settings[from_email]" value="<?php echo esc_attr( (string) $o( 'from_email' ) ); ?>" placeholder="support@minemirth.com" aria-label="<?php esc_attr_e( 'From email', 'mine-mirth-newsletter-alerts' ); ?>">
						<p class="description"><?php esc_html_e( 'Use an address at your own domain. Leave empty to keep WordPress’s default (or your SMTP plugin’s setting).', 'mine-mirth-newsletter-alerts' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Cached pages', 'mine-mirth-newsletter-alerts' ); ?></th>
					<td><label><input type="checkbox" name="mmna_settings[fix_cache]" value="1" <?php checked( (int) $o( 'fix_cache' ), 1 ); ?>> <?php esc_html_e( 'Accept sign-ups from cached pages whose security token has expired (recommended)', 'mine-mirth-newsletter-alerts' ); ?></label></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'Test', 'mine-mirth-newsletter-alerts' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mmna_test"><?php wp_nonce_field( 'mmna_test' ); ?>
			<button class="button button-secondary"><?php esc_html_e( 'Send a test alert', 'mine-mirth-newsletter-alerts' ); ?></button>
		</form>

		<h2><?php esc_html_e( 'Recent sign-ups', 'mine-mirth-newsletter-alerts' ); ?></h2>
		<?php $recent = get_posts( array( 'post_type' => 'mm_subscriber', 'post_status' => 'any', 'posts_per_page' => 15 ) ); ?>
		<?php if ( ! $recent ) : ?>
			<p><?php esc_html_e( 'No sign-ups yet.', 'mine-mirth-newsletter-alerts' ); ?></p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:960px">
				<thead><tr><th><?php esc_html_e( 'Email', 'mine-mirth-newsletter-alerts' ); ?></th><th><?php esc_html_e( 'From', 'mine-mirth-newsletter-alerts' ); ?></th><th><?php esc_html_e( 'Signed up', 'mine-mirth-newsletter-alerts' ); ?></th><th><?php esc_html_e( 'Alert', 'mine-mirth-newsletter-alerts' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $recent as $p ) : ?>
					<?php $sent = (int) get_post_meta( $p->ID, '_mmna_notified', true ); ?>
					<tr>
						<td><?php echo esc_html( $p->post_title ); ?></td>
						<td><?php echo esc_html( mmna_context_label( (string) get_post_meta( $p->ID, '_mm_context', true ) ) ); ?></td>
						<td><?php echo esc_html( get_the_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $p ) ); ?></td>
						<td>
							<?php echo $sent ? esc_html__( 'Sent', 'mine-mirth-newsletter-alerts' ) : esc_html__( 'Not sent', 'mine-mirth-newsletter-alerts' ); ?>
							· <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mmna_resend&id=' . $p->ID ), 'mmna_resend_' . $p->ID ) ); ?>"><?php echo $sent ? esc_html__( 'Send again', 'mine-mirth-newsletter-alerts' ) : esc_html__( 'Send now', 'mine-mirth-newsletter-alerts' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p><a href="<?php echo esc_url( mmna_subscribers_url() ); ?>"><?php esc_html_e( 'All subscribers and CSV export', 'mine-mirth-newsletter-alerts' ); ?></a></p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Email log', 'mine-mirth-newsletter-alerts' ); ?></h2>
		<?php if ( empty( $log ) ) : ?>
			<p><?php esc_html_e( 'Nothing sent yet.', 'mine-mirth-newsletter-alerts' ); ?></p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:960px">
				<thead><tr><th><?php esc_html_e( 'When', 'mine-mirth-newsletter-alerts' ); ?></th><th><?php esc_html_e( 'Email', 'mine-mirth-newsletter-alerts' ); ?></th><th><?php esc_html_e( 'To', 'mine-mirth-newsletter-alerts' ); ?></th><th><?php esc_html_e( 'Result', 'mine-mirth-newsletter-alerts' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( array_slice( (array) $log, 0, 20 ) as $row ) : ?>
					<tr>
						<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $row['at'] ) ); ?></td>
						<td><?php echo esc_html( (string) $row['subject'] ); ?></td>
						<td><?php echo esc_html( (string) $row['to'] ); ?></td>
						<td><?php echo $row['ok'] ? '<span style="color:#008a20">' . esc_html__( 'Sent', 'mine-mirth-newsletter-alerts' ) . '</span>' : '<span style="color:#d63638">' . esc_html__( 'Failed', 'mine-mirth-newsletter-alerts' ) . '</span> — ' . esc_html( (string) $row['error'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Not receiving emails?', 'mine-mirth-newsletter-alerts' ); ?></h2>
		<ol style="max-width:760px">
			<li><?php esc_html_e( 'Check your spam folder, and add the sender to your contacts.', 'mine-mirth-newsletter-alerts' ); ?></li>
			<li><?php esc_html_e( 'If the log shows "Failed", or "Sent" but nothing arrives, your host is not delivering WordPress email. Install a free SMTP plugin such as "WP Mail SMTP" or "FluentSMTP", connect it to your email provider (Google Workspace, Microsoft 365, Zoho, or your host’s mailbox for support@minemirth.com), then click "Send a test alert" again.', 'mine-mirth-newsletter-alerts' ); ?></li>
			<li><?php esc_html_e( 'Set "Send from" to an address at minemirth.com so mail providers trust it.', 'mine-mirth-newsletter-alerts' ); ?></li>
		</ol>
	</div>
	<?php
}
