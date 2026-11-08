<?php
/**
 * Front end: consent defaults in <head>, the banner, the after-Accept scripts and the integrations.
 *
 * The banner is in every page's HTML but hidden; the browser shows it only when no valid choice is stored.
 * That keeps it correct behind page caches, and returning visitors never see it flash.
 *
 * @package MineMirthConsent
 */

defined( 'ABSPATH' ) || exit;

function mmcc_active(): bool {
	if ( ! mmcc_opt( 'enabled' ) || is_admin() || is_feed() || is_embed() || wp_doing_ajax() ) {
		return false;
	}
	if ( mmcc_opt( 'hide_admins' ) && current_user_can( 'manage_options' ) ) {
		return false;
	}
	return (bool) apply_filters( 'mmcc_active', true );
}

/**
 * Before anything else in <head>: Google Consent Mode v2 defaults (denied until Accept), and the stored
 * choice on <html data-mm-consent="accept|deny|none"> for CSS and other scripts.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! mmcc_active() ) {
			return;
		}
		$cfg = array(
			'c' => MMCC_COOKIE,
			'v' => (int) mmcc_opt( 'version' ),
			'm' => (bool) mmcc_opt( 'consent_mode' ),
		);
		?>
<script id="mmcc-defaults">
(function(c){var m=document.cookie.match(new RegExp('(?:^|; )'+c.c+'=([^;]*)')),p=m?decodeURIComponent(m[1]).split('|'):[],s=(p[0]==='accept'||p[0]==='deny')&&+p[1]===c.v?p[0]:'none';document.documentElement.setAttribute('data-mm-consent',s);if(c.m){window.dataLayer=window.dataLayer||[];window.gtag=window.gtag||function(){window.dataLayer.push(arguments);};var g=s==='accept'?'granted':'denied';window.gtag('consent','default',{ad_storage:g,analytics_storage:g,ad_user_data:g,ad_personalization:g,functionality_storage:'granted',security_storage:'granted',wait_for_update:500});window.gtag('set','ads_data_redaction',g==='denied');window.gtag('set','url_passthrough',true);}})(<?php echo wp_json_encode( $cfg ); ?>);
</script>
		<?php
	},
	-1000
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! mmcc_active() ) {
			return;
		}
		wp_enqueue_style( 'mmcc', MMCC_URI . 'assets/consent.css', array(), MMCC_VERSION );
		wp_enqueue_script( 'mmcc', MMCC_URI . 'assets/consent.js', array(), MMCC_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_localize_script(
			'mmcc',
			'mmccConfig',
			array(
				'cookie'   => MMCC_COOKIE,
				'version'  => (int) mmcc_opt( 'version' ),
				'days'     => max( 1, (int) mmcc_opt( 'days' ) ),
				'delay'    => max( 0, (int) mmcc_opt( 'delay' ) ),
				'mode'     => (bool) mmcc_opt( 'consent_mode' ),
				'gpc'      => (bool) mmcc_opt( 'gpc' ),
				'record'   => mmcc_opt( 'records' ) ? esc_url_raw( rest_url( 'mine-mirth-consent/v1/record' ) ) : '',
				'secure'   => is_ssl(),
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'preview'  => isset( $_GET['mmcc_preview'] ), // phpcs:ignore WordPress.Security.NonceVerification
			)
		);
		$accent = sanitize_hex_color( (string) mmcc_opt( 'accent' ) );
		if ( $accent ) {
			wp_add_inline_style( 'mmcc', '.mmcc{--mmcc-accent:' . $accent . ';--mmcc-accent-2:' . $accent . '}' );
		}
	}
);

function mmcc_icon(): string {
	return '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 12.3A9 9 0 1 1 11.7 3a3.5 3.5 0 0 0 4.3 4.3 3.5 3.5 0 0 0 5 5z"/><circle cx="8.5" cy="10.5" r="1" fill="currentColor"/><circle cx="12.5" cy="15.5" r="1" fill="currentColor"/><circle cx="8" cy="15" r=".6" fill="currentColor"/><circle cx="16" cy="13" r=".6" fill="currentColor"/></svg>';
}

/** The banner, the reopen button and the after-Accept scripts (inert until used). */
add_action(
	'wp_footer',
	static function (): void {
		if ( ! mmcc_active() ) {
			return;
		}
		$s      = mmcc_settings();
		$policy = mmcc_policy_url();
		$pos    = in_array( $s['position'], array( 'bottom-left', 'bottom-center', 'bottom-bar' ), true ) ? $s['position'] : 'bottom-left';
		$scheme = in_array( $s['scheme'], array( 'auto', 'dark', 'light' ), true ) ? $s['scheme'] : 'auto';
		?>
		<section class="mmcc mmcc--<?php echo esc_attr( $pos ); ?> mmcc--<?php echo esc_attr( $scheme ); ?>" data-mmcc role="dialog" aria-modal="false" aria-labelledby="mmcc-title" aria-describedby="mmcc-text" hidden>
			<div class="mmcc__card">
				<div class="mmcc__icon"><?php echo mmcc_icon(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<div class="mmcc__body">
					<h2 class="mmcc__title" id="mmcc-title"><?php echo esc_html( (string) $s['title'] ); ?></h2>
					<p class="mmcc__text" id="mmcc-text">
						<?php echo esc_html( (string) $s['message'] ); ?>
						<?php if ( $policy && $s['policy_text'] ) : ?>
							<a class="mmcc__policy" href="<?php echo esc_url( $policy ); ?>"><?php echo esc_html( (string) $s['policy_text'] ); ?></a>
						<?php endif; ?>
					</p>
				</div>
				<div class="mmcc__actions">
					<button type="button" class="mmcc__btn mmcc__btn--deny" data-mmcc-choice="deny"><?php echo esc_html( (string) $s['deny'] ); ?></button>
					<button type="button" class="mmcc__btn mmcc__btn--accept" data-mmcc-choice="accept"><?php echo esc_html( (string) $s['accept'] ); ?></button>
				</div>
			</div>
		</section>
		<?php if ( $s['reopen'] ) : ?>
			<button type="button" class="mmcc-reopen" data-mmcc-open aria-label="<?php echo esc_attr( (string) $s['reopen_text'] ); ?>" title="<?php echo esc_attr( (string) $s['reopen_text'] ); ?>" hidden><?php echo mmcc_icon(); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) $s['scripts'] ) ) : ?>
			<template id="mmcc-scripts"><?php echo $s['scripts']; // phpcs:ignore WordPress.Security.EscapeOutput -- added by an administrator with the unfiltered_html capability; kept inert until Accept. ?></template>
		<?php endif; ?>
		<?php
	},
	5
);

/** [mm_cookie_settings] or any link to #mm-cookie-settings reopens the banner (for the footer or privacy page). */
add_shortcode(
	'mm_cookie_settings',
	static function ( $atts ): string {
		$atts = shortcode_atts( array( 'text' => (string) mmcc_opt( 'reopen_text' ) ), (array) $atts );
		return '<a href="#mm-cookie-settings" class="mmcc-link" data-mmcc-open>' . esc_html( (string) $atts['text'] ) . '</a>';
	}
);

/* ------------------------------------------------------------------ WP Consent API (other plugins read the choice) */

add_filter( 'wp_consent_api_registered_' . plugin_basename( MMCC_FILE ), '__return_true' );
add_filter( 'wp_get_consent_type', static fn( $type ) => $type ? $type : 'optin' );
