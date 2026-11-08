<?php
/**
 * Settings, stored in one option.
 *
 * @package MineMirthProductPage
 */

defined( 'ABSPATH' ) || exit;

function mmpp_defaults(): array {
	return array(
		// Gallery.
		'gallery'        => 1,
		'ratio'          => '1/1',
		'fit'            => 'contain',
		'background'     => 'glow',
		'radius'         => 'theme',
		'arrows'         => 'hover',
		'counter'        => 1,
		'thumbs'         => 'below',
		'thumbs_count'   => 5,
		'mobile_nav'     => 'thumbs',
		'zoom'           => 1,
		'zoom_level'     => 2,
		'lightbox'       => 1,
		'autoplay'       => 0,
		'loop'           => 1,
		// Description.
		'desc'           => 1,
		'tab_title'      => '',
		'desc_heading'   => 0,
		'text_size'      => 'md',
		'line_height'    => '1.75',
		'width'          => 'comfortable',
		'headings'       => 'balanced',
		'heading_style'  => 'bar',
		'lists'          => 'check',
		'tables'         => 1,
		'clean'          => 1,
		'demote_h1'      => 1,
		'toc'            => 1,
		'toc_min'        => 3,
		'collapse'       => 0,
		'collapse_h'     => 640,
	);
}

/** Allowed values for the choice settings (first is the default). */
function mmpp_choices(): array {
	return array(
		'ratio'         => array( '1/1', '4/3', '3/2', '16/10', '16/9', '3/4' ),
		'fit'           => array( 'contain', 'cover' ),
		'background'    => array( 'glow', 'surface', 'white', 'none' ),
		'radius'        => array( 'theme', 'small', 'none' ),
		'arrows'        => array( 'hover', 'always', 'never' ),
		'thumbs'        => array( 'below', 'left', 'none' ),
		'mobile_nav'    => array( 'thumbs', 'dots' ),
		'text_size'     => array( 'md', 'sm', 'lg' ),
		'line_height'   => array( '1.75', '1.6', '1.9' ),
		'width'         => array( 'comfortable', 'narrow', 'full' ),
		'headings'      => array( 'balanced', 'compact', 'large' ),
		'heading_style' => array( 'bar', 'line', 'plain' ),
		'lists'         => array( 'check', 'dot', 'plain' ),
	);
}

function mmpp_settings( bool $fresh = false ): array {
	static $s = null;
	if ( null === $s || $fresh ) {
		$s = wp_parse_args( (array) get_option( 'mmpp_settings', array() ), mmpp_defaults() );
	}
	return $s;
}

function mmpp_opt( string $key ) {
	return mmpp_settings()[ $key ] ?? null;
}

function mmpp_icon( string $name ): string {
	$paths = array(
		'prev'   => '<path d="m15 18-6-6 6-6"/>',
		'next'   => '<path d="m9 18 6-6-6-6"/>',
		'expand' => '<path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>',
		'close'  => '<path d="M18 6 6 18M6 6l12 12"/>',
	);
	return '<svg class="mmpp-ico" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? '' ) . '</svg>';
}
