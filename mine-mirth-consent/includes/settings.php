<?php
/**
 * Settings (one option).
 *
 * @package MineMirthConsent
 */

defined( 'ABSPATH' ) || exit;

function mmcc_defaults(): array {
	return array(
		'enabled'      => 1,
		'title'        => __( 'We value your privacy', 'mine-mirth-consent' ),
		'message'      => __( 'We use essential cookies to run the shop and keep your cart. With your permission we also use analytics and marketing cookies to understand how the site is used and to improve it. You can change your choice at any time.', 'mine-mirth-consent' ),
		'accept'       => __( 'Accept', 'mine-mirth-consent' ),
		'deny'         => __( 'Deny', 'mine-mirth-consent' ),
		'policy_text'  => __( 'Privacy policy', 'mine-mirth-consent' ),
		'policy_url'   => '',
		'position'     => 'bottom-left',
		'scheme'       => 'auto',
		'accent'       => '',
		'delay'        => 600,
		'days'         => 182,
		'version'      => 1,
		'consent_mode' => 1,
		'gpc'          => 1,
		'reopen'       => 1,
		'reopen_text'  => __( 'Cookie settings', 'mine-mirth-consent' ),
		'records'      => 1,
		'scripts'      => '',
		'hide_admins'  => 0,
	);
}

function mmcc_settings( bool $fresh = false ): array {
	static $s = null;
	if ( null === $s || $fresh ) {
		$saved = get_option( 'mmcc_settings', array() );
		$s     = wp_parse_args( is_array( $saved ) ? $saved : array(), mmcc_defaults() );
	}
	return $s;
}

function mmcc_opt( string $key ) {
	return mmcc_settings()[ $key ] ?? null;
}

/** The privacy policy page: the one set here, else WordPress's own privacy page. */
function mmcc_policy_url(): string {
	$url = trim( (string) mmcc_opt( 'policy_url' ) );
	if ( '' !== $url ) {
		return str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
	}
	return (string) get_privacy_policy_url();
}
