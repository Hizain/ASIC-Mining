<?php
/**
 * Plugin Name:       Mine Mirth Hide Footer Wordmark
 * Description:       Removes the large faded "Mine Mirth" text at the very bottom of the footer. Works with the Mine Mirth theme as installed. Deactivate to bring it back.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 *
 * @package MineMirthHideWordmark
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_head',
	static function () {
		echo "<style id=\"mm-hide-wordmark\">.mm-footer .mm-footer__wordmark,.mm-footer__wordmark{display:none!important}</style>\n";
	},
	100
);
