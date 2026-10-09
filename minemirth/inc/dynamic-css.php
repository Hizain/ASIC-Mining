<?php
/**
 * Build CSS custom properties from the options schema.
 *
 * main.css ships sensible fallbacks for every token; this output overrides
 * them with the merchant's Customizer values for :root, light and dark.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/**
 * Format one value for a css-mapped setting.
 */
function mm_css_value( array $field, $value ): string {
	$css = $field['css'];
	if ( isset( $css['map'] ) ) {
		return (string) ( $css['map'][ $value ] ?? reset( $css['map'] ) );
	}
	if ( 'color' === $field['type'] ) {
		$hex = sanitize_hex_color( (string) $value );
		return $hex ? $hex : (string) $field['default'];
	}
	if ( is_numeric( $value ) ) {
		$num = (float) $value;
		if ( ! empty( $css['divide'] ) ) {
			$num = $num / (float) $css['divide'];
		}
		$num = rtrim( rtrim( number_format( $num, 4, '.', '' ), '0' ), '.' );
		return $num . ( $css['unit'] ?? '' );
	}
	return preg_replace( '/[^a-zA-Z0-9#%.,\-\s"\'()]/', '', (string) $value );
}

/**
 * Collect token declarations grouped by scope.
 *
 * @return array{root: array, light: array, dark: array}
 */
function mm_css_tokens(): array {
	$groups = array(
		'root'  => array(),
		'light' => array(),
		'dark'  => array(),
	);
	foreach ( mm_options_schema() as $key => $field ) {
		if ( empty( $field['css']['var'] ) ) {
			continue;
		}
		$scope                                     = $field['css']['scope'] ?? 'root';
		$groups[ $scope ][ $field['css']['var'] ] = mm_css_value( $field, mm_opt( $key ) );
	}

	$groups['root']['--mm-font-heading'] = mm_font_stack( (string) mm_opt( 'font_heading' ) );
	$groups['root']['--mm-font-body']    = mm_font_stack( (string) mm_opt( 'font_body' ) );
	$groups['root']['--mm-font-mono']    = mm_font_stack( (string) mm_opt( 'font_mono' ), 'mono' );

	return $groups;
}

/**
 * Serialize token groups into CSS.
 */
function mm_dynamic_css(): string {
	$groups = mm_css_tokens();
	$block  = static function ( string $selector, array $decls ): string {
		if ( ! $decls ) {
			return '';
		}
		$out = $selector . '{';
		foreach ( $decls as $prop => $val ) {
			$out .= $prop . ':' . $val . ';';
		}
		return $out . '}';
	};

	$css  = $block( ':root', $groups['root'] );
	$css .= $block( ':root,:root[data-theme="light"],.mm-scheme-light', $groups['light'] );
	$css .= $block( ':root[data-theme="dark"],.mm-scheme-dark', $groups['dark'] );

	/**
	 * Filter the generated design-token CSS.
	 */
	return (string) apply_filters( 'mm_dynamic_css', $css, $groups );
}
