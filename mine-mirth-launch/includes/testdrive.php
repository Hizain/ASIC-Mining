<?php
/**
 * Test drive: chosen administrators see Mine Mirth 5 while every visitor keeps the current theme.
 *
 * Works on the front end, in wp-admin and in the block editor, so you can use the Site builder,
 * the Customizer and the editor with the new theme before anyone else sees it.
 *
 * @package MineMirthLaunch
 */

defined( 'ABSPATH' ) || exit;

const MML_THEME = 'minemirth-5';

function mml_testdrive_config(): array {
	$c = get_option( 'mml_testdrive' );
	return wp_parse_args( is_array( $c ) ? $c : array(), array( 'theme' => MML_THEME, 'users' => array() ) );
}

/** The theme that is live for visitors (read before any test-drive filter is added). */
function mml_live_stylesheet(): string {
	static $live = null;
	if ( null === $live ) {
		$live = (string) get_option( 'stylesheet' );
	}
	return $live;
}

function mml_testdrive_theme_ready(): bool {
	$cfg = mml_testdrive_config();
	return wp_get_theme( $cfg['theme'] )->exists();
}

/** True when this request is shown with the test-drive theme. */
function mml_testdrive_active(): bool {
	return ! empty( $GLOBALS['mml_testdrive_on'] );
}

function mml_testdrive_enabled_for( int $user_id ): bool {
	return in_array( $user_id, array_map( 'intval', mml_testdrive_config()['users'] ), true );
}

function mml_testdrive_set( int $user_id, bool $on ): void {
	$cfg   = mml_testdrive_config();
	$users = array_values( array_diff( array_map( 'intval', $cfg['users'] ), array( $user_id ) ) );
	if ( $on ) {
		$users[] = $user_id;
	}
	$cfg['users'] = $users;
	update_option( 'mml_testdrive', $cfg, true );
}

add_action(
	'plugins_loaded',
	static function () {
		$cfg = mml_testdrive_config();
		if ( empty( $cfg['users'] ) ) {
			return;
		}
		$live = mml_live_stylesheet();
		if ( $live === $cfg['theme'] ) {
			return;
		}
		$theme = wp_get_theme( $cfg['theme'] );
		if ( ! $theme->exists() || $theme->errors() ) {
			return;
		}
		$uid = get_current_user_id();
		if ( ! $uid || ! mml_testdrive_enabled_for( $uid ) || ! user_can( $uid, 'switch_themes' ) ) {
			return;
		}
		$GLOBALS['mml_testdrive_on'] = true;
		$stylesheet                  = $theme->get_stylesheet();
		$template                    = $theme->get_template();
		add_filter( 'pre_option_stylesheet', static fn() => $stylesheet );
		add_filter( 'pre_option_template', static fn() => $template );
		// Page caches must never store a test-drive page.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		add_action( 'send_headers', static fn() => header( 'Cache-Control: no-store, private' ) );
	},
	0
);

/** Toggle from the admin bar or the Launch screen. */
add_action(
	'admin_post_mml_testdrive',
	static function () {
		if ( ! current_user_can( 'switch_themes' ) || ! check_admin_referer( 'mml_testdrive' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-launch' ) );
		}
		$on = ! empty( $_GET['on'] ); // phpcs:ignore WordPress.Security.NonceVerification
		mml_testdrive_set( get_current_user_id(), $on );
		do_action( 'litespeed_purge_all' );
		$back = wp_get_referer();
		if ( $on && $back && false !== strpos( $back, 'page=mine-mirth-launch' ) ) {
			$back = admin_url( 'themes.php?page=mm-site-builder' );
		}
		wp_safe_redirect( $back ? $back : admin_url( 'admin.php?page=mine-mirth-launch' ) );
		exit;
	}
);

function mml_testdrive_toggle_url( bool $on ): string {
	return wp_nonce_url( admin_url( 'admin-post.php?action=mml_testdrive&on=' . ( $on ? '1' : '0' ) ), 'mml_testdrive' );
}

add_action(
	'admin_bar_menu',
	static function ( WP_Admin_Bar $bar ) {
		if ( ! current_user_can( 'switch_themes' ) || ! mml_testdrive_theme_ready() || mml_live_stylesheet() === mml_testdrive_config()['theme'] ) {
			return;
		}
		$on = mml_testdrive_active();
		$bar->add_node(
			array(
				'id'    => 'mml-testdrive',
				'title' => '<span class="ab-icon dashicons dashicons-visibility" style="margin-top:2px"></span>' . ( $on ? esc_html__( 'Mine Mirth 5: test drive ON', 'mine-mirth-launch' ) : esc_html__( 'Test drive Mine Mirth 5', 'mine-mirth-launch' ) ),
				'href'  => mml_testdrive_toggle_url( ! $on ),
				'meta'  => array( 'title' => $on ? __( 'Only you see Mine Mirth 5. Click to go back to the live theme.', 'mine-mirth-launch' ) : __( 'See Mine Mirth 5 yourself while visitors keep the current theme.', 'mine-mirth-launch' ) ),
			)
		);
		if ( $on ) {
			$bar->add_node( array( 'parent' => 'mml-testdrive', 'id' => 'mml-testdrive-off', 'title' => esc_html__( 'Turn test drive off', 'mine-mirth-launch' ), 'href' => mml_testdrive_toggle_url( false ) ) );
			$bar->add_node( array( 'parent' => 'mml-testdrive', 'id' => 'mml-testdrive-builder', 'title' => esc_html__( 'Open the Site builder', 'mine-mirth-launch' ), 'href' => admin_url( 'themes.php?page=mm-site-builder' ) ) );
		}
		$bar->add_node( array( 'parent' => 'mml-testdrive', 'id' => 'mml-launch-screen', 'title' => esc_html__( 'Launch & redirects', 'mine-mirth-launch' ), 'href' => admin_url( 'admin.php?page=mine-mirth-launch' ) ) );
	},
	90
);

add_action(
	'wp_head',
	static function () {
		if ( mml_testdrive_active() && is_admin_bar_showing() ) {
			echo '<style>#wpadminbar #wp-admin-bar-mml-testdrive>.ab-item{background:#35a8f6;color:#03111f}#wpadminbar #wp-admin-bar-mml-testdrive>.ab-item .ab-icon:before{color:#03111f}</style>';
		}
	}
);
add_action(
	'admin_head',
	static function () {
		if ( mml_testdrive_active() ) {
			echo '<style>#wpadminbar #wp-admin-bar-mml-testdrive>.ab-item{background:#35a8f6;color:#03111f}#wpadminbar #wp-admin-bar-mml-testdrive>.ab-item .ab-icon:before{color:#03111f}</style>';
		}
	}
);

// When Mine Mirth 5 becomes the live theme, test drive is no longer needed.
add_action(
	'after_switch_theme',
	static function () {
		if ( mml_live_stylesheet() === mml_testdrive_config()['theme'] ) {
			delete_option( 'mml_testdrive' );
		}
	}
);
add_action( 'mm_launch_after_go_live', static fn() => delete_option( 'mml_testdrive' ) );
