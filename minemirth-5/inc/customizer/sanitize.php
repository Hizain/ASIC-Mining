<?php
/**
 * Sanitizers for every option type.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL settings may hold {shop}-style tokens, site-relative paths, #anchors
 * or absolute URLs.
 */
function mm_sanitize_url_setting( $value ): string {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	if ( preg_match( '/^\{(shop|account|cart|checkout|blog|home)\}/', $value ) ) {
		return preg_replace( '/[^\w\-{}\/?=&.%#+:]/', '', $value );
	}
	if ( '#' === $value[0] ) {
		return '#' . sanitize_title( substr( $value, 1 ) );
	}
	if ( '/' === $value[0] && ( ! isset( $value[1] ) || '/' !== $value[1] ) ) {
		return '/' . ltrim( (string) preg_replace( '/[^\w\-\/?=&.%#+~]/', '', $value ), '/' );
	}
	return esc_url_raw( $value );
}

/**
 * Allowed inline HTML for textarea options.
 */
function mm_textarea_allowed_html(): array {
	return array(
		'a'      => array(
			'href'   => true,
			'target' => true,
			'rel'    => true,
		),
		'strong' => array(),
		'b'      => array(),
		'em'     => array(),
		'i'      => array(),
		'br'     => array(),
		'span'   => array( 'class' => true ),
		'code'   => array(),
	);
}

/**
 * Sanitize one value according to its schema field.
 */
function mm_sanitize_value( $value, array $field ) {
	$default = $field['default'] ?? '';
	switch ( $field['type'] ) {
		case 'toggle':
			return ! empty( $value ) && 'false' !== $value;
		case 'color':
			$hex = sanitize_hex_color( (string) $value );
			return $hex ? $hex : $default;
		case 'number':
		case 'range':
			if ( ! is_numeric( $value ) ) {
				return $default;
			}
			$num = (float) $value;
			if ( isset( $field['min'] ) ) {
				$num = max( (float) $field['min'], $num );
			}
			if ( isset( $field['max'] ) ) {
				$num = min( (float) $field['max'], $num );
			}
			return floor( $num ) === $num ? (int) $num : $num;
		case 'select':
		case 'radio':
		case 'icon':
			$choices = 'icon' === $field['type'] ? mm_icon_choices() : (array) ( $field['choices'] ?? array() );
			return array_key_exists( (string) $value, $choices ) ? (string) $value : $default;
		case 'media':
			return absint( $value );
		case 'url':
			return mm_sanitize_url_setting( $value );
		case 'email':
			return sanitize_email( (string) $value );
		case 'textarea':
			return wp_kses( (string) $value, mm_textarea_allowed_html() );
		case 'sortable':
			$choices = (array) ( $field['choices'] ?? array() );
			$out     = array();
			foreach ( explode( ',', (string) $value ) as $item ) {
				$item = trim( $item );
				$id   = ltrim( $item, '-' );
				if ( isset( $choices[ $id ] ) ) {
					$out[ $id ] = ( '-' === substr( $item, 0, 1 ) ? '-' : '' ) . $id;
				}
			}
			foreach ( array_keys( $choices ) as $id ) {
				if ( ! isset( $out[ $id ] ) ) {
					$out[ $id ] = '-' . $id;
				}
			}
			return implode( ',', $out );
		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Customizer sanitize_callback: resolves the schema field from the setting id.
 *
 * @param mixed                $value   Raw value.
 * @param WP_Customize_Setting $setting Setting object.
 */
function mm_sanitize_setting( $value, $setting ) {
	$key    = preg_replace( '/^mm_/', '', $setting->id );
	$schema = mm_options_schema();
	if ( ! isset( $schema[ $key ] ) ) {
		return sanitize_text_field( (string) $value );
	}
	return mm_sanitize_value( $value, $schema[ $key ] );
}
