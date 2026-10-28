<?php
/**
 * Plugin Name:       Mine Mirth Menu Fix
 * Description:       Fixes the desktop header menus on the Mine Mirth 5 theme: dropdowns no longer jump to another menu while you move to a link, invisible menus no longer catch your clicks, and a menu stays open while you travel down into it. No settings: activate it and clear your page cache.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-menu-fix
 *
 * What went wrong in the theme's header:
 * 1. A closed dropdown only fades to invisible. Its links (the inner columns of the big Shop, Tools,
 *    Learn and Company panels stay "visible" to the mouse) still catch the pointer and clicks, so
 *    moving towards a link can open another menu, and a click can go to a link you cannot see.
 * 2. A panel that is fading out keeps catching the pointer for a moment.
 * 3. There is a small dead gap between a menu name and its panel, so moving slowly closes the menu.
 * 4. Cutting across a neighbouring menu name on the way to a link switches menus at once.
 *
 * This plugin switches closed panels off for the mouse, closes the gap, and adds "hover intent":
 * a menu opens at once, stays open while you move down into it, and a neighbouring menu only takes
 * over when you rest on its name. Keyboard and touch use are unchanged.
 *
 * @package MineMirthMenuFix
 */

defined( 'ABSPATH' ) || exit;

define( 'MMMF_VERSION', '1.0.0' );

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( is_admin() ) {
			return;
		}
		$url = plugin_dir_url( __FILE__ );
		// After the theme's stylesheet so these rules win.
		wp_enqueue_style( 'mine-mirth-menu-fix', $url . 'assets/menu-fix.css', wp_style_is( 'mm-main', 'registered' ) ? array( 'mm-main' ) : array(), MMMF_VERSION );
		wp_enqueue_script(
			'mine-mirth-menu-fix',
			$url . 'assets/menu-fix.js',
			array(),
			MMMF_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	},
	30
);
