<?php
/**
 * Plugin Name:       Mine Mirth Hero Spacing
 * Description:       Removes the large empty band between the header and the hero on the Mine Mirth 5 theme, on the homepage and every other page, on desktop and phones. Every hero starts the same short distance below the header. No settings: activate it and clear your page cache.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-hero-spacing
 *
 * Why there was a gap: the homepage hero is stretched to 92% of the screen height with its content
 * centred, and its top padding adds the header's height again even though the header sits above it.
 * Other pages each use their own top padding (24 to 96 pixels). This plugin gives every hero the
 * same top spacing (about 48px on desktop, 28px on phones) and sizes the homepage hero to its content.
 *
 * @package MineMirthHeroSpacing
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( is_admin() ) {
			return;
		}
		wp_enqueue_style( 'mine-mirth-hero-spacing', plugin_dir_url( __FILE__ ) . 'assets/hero-spacing.css', wp_style_is( 'mm-main', 'registered' ) ? array( 'mm-main' ) : array(), '1.0.0' );
	},
	30
);
