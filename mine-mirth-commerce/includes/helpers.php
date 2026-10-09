<?php
/**
 * Shared helpers.
 *
 * @package MineMirthCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin option with a default.
 *
 * @param string $key     Option key inside the mmc_settings array.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function mmc_opt( string $key, $default = null ) {
	$all = get_option( 'mmc_settings', array() );
	return is_array( $all ) && array_key_exists( $key, $all ) ? $all[ $key ] : $default;
}

/**
 * Theme icon when Mine Mirth is active, nothing otherwise.
 */
function mmc_icon( string $name ): string {
	return function_exists( 'mm_icon' ) ? (string) mm_icon( $name ) : '';
}

/**
 * Theme option when the Mine Mirth theme is active.
 *
 * @param string $key     Theme option.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function mmc_theme_opt( string $key, $default = '' ) {
	if ( function_exists( 'mm_opt' ) ) {
		$value = mm_opt( $key );
		return null === $value ? $default : $value;
	}
	return $default;
}

/**
 * Resolve tokens like {shop} and relative paths to full URLs.
 */
function mmc_url( string $url ): string {
	if ( function_exists( 'mm_url' ) ) {
		return (string) mm_url( $url );
	}
	if ( '' === $url ) {
		return '';
	}
	if ( str_starts_with( $url, '/' ) ) {
		return home_url( $url );
	}
	return $url;
}
