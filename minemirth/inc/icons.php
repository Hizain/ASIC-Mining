<?php
/**
 * Inline SVG icon set (24×24, stroke based, inherits currentColor).
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icon path data. Each entry is the inner markup of a 24×24 SVG.
 */
function mm_icon_library(): array {
	static $icons = null;
	if ( null !== $icons ) {
		return $icons;
	}

	$icons = array(
		'arrow-right'    => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
		'arrow-left'     => '<path d="M19 12H5"/><path d="m11 18-6-6 6-6"/>',
		'arrow-up-right' => '<path d="M7 17 17 7"/><path d="M8 7h9v9"/>',
		'arrow-up'       => '<path d="M12 19V5"/><path d="m6 11 6-6 6 6"/>',
		'arrow-down'     => '<path d="M12 5v14"/><path d="m18 13-6 6-6-6"/>',
		'chevron-down'   => '<path d="m6 9 6 6 6-6"/>',
		'chevron-right'  => '<path d="m9 6 6 6-6 6"/>',
		'chevron-left'   => '<path d="m15 6-6 6 6 6"/>',
		'check'          => '<path d="M20 6 9 17l-5-5"/>',
		'check-circle'   => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>',
		'x'              => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
		'plus'           => '<path d="M12 5v14"/><path d="M5 12h14"/>',
		'minus'          => '<path d="M5 12h14"/>',
		'menu'           => '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h10"/>',
		'search'         => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/>',
		'sun'            => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m4.9 19.1 1.4-1.4"/><path d="m17.7 6.3 1.4-1.4"/>',
		'moon'           => '<path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z"/>',
		'monitor'        => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8"/><path d="M12 16v4"/>',
		'cart'           => '<circle cx="9" cy="20" r="1.3"/><circle cx="18" cy="20" r="1.3"/><path d="M2.5 3.5h2.3l2.4 11.2a2 2 0 0 0 2 1.6h8.1a2 2 0 0 0 1.9-1.5l1.6-6.8H6"/>',
		'bag'            => '<path d="M5 8h14l-1.2 11.1a2 2 0 0 1-2 1.9H8.2a2 2 0 0 1-2-1.9Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
		'user'           => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'users'          => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8"/><path d="M18 14.3a6.5 6.5 0 0 1 3.5 5.7"/>',
		'heart'          => '<path d="M12 20.5s-8-4.6-8-10.4A4.6 4.6 0 0 1 12 7.3a4.6 4.6 0 0 1 8 2.8c0 5.8-8 10.4-8 10.4Z"/>',
		'shield'         => '<path d="M12 21.5s8-3.6 8-10V5.2L12 2.5 4 5.2v6.3c0 6.4 8 10 8 10Z"/>',
		'shield-check'   => '<path d="M12 21.5s8-3.6 8-10V5.2L12 2.5 4 5.2v6.3c0 6.4 8 10 8 10Z"/><path d="m8.8 12 2.2 2.2 4.4-4.4"/>',
		'lock'           => '<rect x="4.5" y="10.5" width="15" height="10.5" rx="2.5"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/><path d="M12 15v2"/>',
		'bolt'           => '<path d="M13.5 2.5 4.5 13.5h7l-1 8 9-11h-7Z"/>',
		'cpu'            => '<rect x="6" y="6" width="12" height="12" rx="2"/><rect x="9.5" y="9.5" width="5" height="5" rx="1"/><path d="M9 2.5v3.5"/><path d="M15 2.5v3.5"/><path d="M9 18v3.5"/><path d="M15 18v3.5"/><path d="M2.5 9H6"/><path d="M2.5 15H6"/><path d="M18 9h3.5"/><path d="M18 15h3.5"/>',
		'server'         => '<rect x="3" y="3.5" width="18" height="7" rx="2"/><rect x="3" y="13.5" width="18" height="7" rx="2"/><path d="M7 7h.01"/><path d="M7 17h.01"/><path d="M11 7h6"/><path d="M11 17h6"/>',
		'chart'          => '<path d="M3.5 3.5v17h17"/><path d="m7.5 15 4-5 3 3 5-6.5"/>',
		'bar-chart'      => '<path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/>',
		'trending-up'    => '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
		'trending-down'  => '<path d="m3 7 6 6 4-4 8 8"/><path d="M15 17h6v-6"/>',
		'activity'       => '<path d="M3 12h4l2.5-7 5 14 2.5-7h4"/>',
		'globe'          => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.8 2.6 4.2 5.6 4.2 9s-1.4 6.4-4.2 9c-2.8-2.6-4.2-5.6-4.2-9S9.2 5.6 12 3Z"/>',
		'map-pin'        => '<path d="M12 21.5s7-6.2 7-11.5a7 7 0 0 0-14 0c0 5.3 7 11.5 7 11.5Z"/><circle cx="12" cy="10" r="2.5"/>',
		'mail'           => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3.5 7 8.5 6 8.5-6"/>',
		'phone'          => '<path d="M5 3.5h3.2l1.6 4.4-2.1 1.4a11 11 0 0 0 5 5l1.4-2.1 4.4 1.6V17a2.5 2.5 0 0 1-2.7 2.5C9.4 18.9 5.1 14.6 4.5 7.2A2.5 2.5 0 0 1 5 3.5Z"/>',
		'message'        => '<path d="M20.5 12a8.5 8.5 0 0 1-12.4 7.6L3.5 21l1.4-4.6A8.5 8.5 0 1 1 20.5 12Z"/>',
		'headset'        => '<path d="M4 15v-3a8 8 0 0 1 16 0v3"/><rect x="2.5" y="13.5" width="4.5" height="6.5" rx="2"/><rect x="17" y="13.5" width="4.5" height="6.5" rx="2"/><path d="M19.3 20a4 4 0 0 1-4 2.5H13"/>',
		'truck'          => '<path d="M2.5 5.5h11v10h-11Z"/><path d="M13.5 9h4.3l3.2 3.5v3h-7.5"/><circle cx="6.5" cy="17.5" r="2"/><circle cx="17" cy="17.5" r="2"/>',
		'package'        => '<path d="m12 2.8 8.5 4.6v9.2L12 21.2l-8.5-4.6V7.4Z"/><path d="m3.8 7.5 8.2 4.5 8.2-4.5"/><path d="M12 12v9"/><path d="m7.8 5.1 8.4 4.6"/>',
		'refresh'        => '<path d="M20.5 11a8.5 8.5 0 0 0-15.3-4.5L3.5 8.5"/><path d="M3.5 3.5v5h5"/><path d="M3.5 13a8.5 8.5 0 0 0 15.3 4.5l1.7-2"/><path d="M20.5 20.5v-5h-5"/>',
		'wallet'         => '<path d="M19 7.5V5.5a2 2 0 0 0-2-2H5.5a2 2 0 0 0 0 4H19a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5.5a2 2 0 0 1-2-2v-13"/><path d="M16.5 14h.01"/>',
		'credit-card'    => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19"/><path d="M6.5 15h4"/>',
		'bitcoin'        => '<circle cx="12" cy="12" r="9.5"/><path d="M9.5 7.5h4a2.2 2.2 0 0 1 0 4.5h-4"/><path d="M9.5 12h4.6a2.3 2.3 0 0 1 0 4.5H9.5Z"/><path d="M9.5 7.5v9"/><path d="M11 6v1.5"/><path d="M13 6v1.5"/><path d="M11 16.5V18"/><path d="M13 16.5V18"/>',
		'layers'         => '<path d="m12 2.5 9.5 5-9.5 5-9.5-5Z"/><path d="m2.5 12 9.5 5 9.5-5"/><path d="m2.5 16.5 9.5 5 9.5-5"/>',
		'settings'       => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
		'sliders'        => '<path d="M4 6h10"/><path d="M18 6h2"/><circle cx="16" cy="6" r="2"/><path d="M4 12h3"/><path d="M11 12h9"/><circle cx="9" cy="12" r="2"/><path d="M4 18h12"/><path d="M20 18h0"/><circle cx="18" cy="18" r="2"/>',
		'clock'          => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'calendar'       => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17"/><path d="M8 3v4"/><path d="M16 3v4"/>',
		'star'           => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9Z"/>',
		'award'          => '<circle cx="12" cy="9" r="6"/><path d="m8.5 14 -1.5 7.5 5-2.8 5 2.8-1.5-7.5"/>',
		'sparkles'       => '<path d="M12 3.5 13.7 8l4.5 1.7-4.5 1.7L12 16l-1.7-4.6L5.8 9.7 10.3 8Z"/><path d="M19 15.5l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8Z"/><path d="M5 2.5l.6 1.4 1.4.6-1.4.6L5 6.5l-.6-1.4L3 4.5l1.4-.6Z"/>',
		'thermometer'    => '<path d="M14 14.8V4.5a2 2 0 0 0-4 0v10.3a4 4 0 1 0 4 0Z"/><path d="M12 9v8"/>',
		'fan'            => '<circle cx="12" cy="12" r="2"/><path d="M12 10c0-3.5 1-6.5 3.5-6.5S19 6 16.5 8.5 12 10 12 10Z"/><path d="M14 12c3.5 0 6.5 1 6.5 3.5S18 19 15.5 16.5 14 12 14 12Z"/><path d="M12 14c0 3.5-1 6.5-3.5 6.5S5 18 7.5 15.5 12 14 12 14Z"/><path d="M10 12c-3.5 0-6.5-1-6.5-3.5S6 5 8.5 7.5 10 12 10 12Z"/>',
		'droplet'        => '<path d="M12 2.5s6.5 7 6.5 11.5a6.5 6.5 0 0 1-13 0C5.5 9.5 12 2.5 12 2.5Z"/>',
		'wrench'         => '<path d="M14.7 6.3a4 4 0 0 0 5 5L21 12.6a6 6 0 0 1-7.6 2.4l-6.3 6.3a2.1 2.1 0 0 1-3-3l6.3-6.3A6 6 0 0 1 12.8 4.4Z"/>',
		'tag'            => '<path d="M3 3h8.2L21 12.8 12.8 21 3 11.2Z"/><circle cx="7.5" cy="7.5" r="1.3"/>',
		'percent'        => '<path d="M19 5 5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
		'info'           => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/>',
		'alert'          => '<path d="M10.3 3.9 2.4 17.5A2 2 0 0 0 4.1 20.5h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
		'book'           => '<path d="M4 19.5V5a2.5 2.5 0 0 1 2.5-2.5H20v16H6.5A2.5 2.5 0 0 0 4 21Z"/><path d="M4 19a2 2 0 0 1 2-2h14"/>',
		'file'           => '<path d="M14 2.5H6.5a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V8Z"/><path d="M14 2.5V8h5.5"/><path d="M8.5 13h7"/><path d="M8.5 17h5"/>',
		'gift'           => '<rect x="3.5" y="8" width="17" height="4" rx="1"/><path d="M5 12v8.5h14V12"/><path d="M12 8v12.5"/><path d="M12 8S10.5 3.5 8 3.5a2.2 2.2 0 0 0 0 4.5Z"/><path d="M12 8s1.5-4.5 4-4.5a2.2 2.2 0 0 1 0 4.5Z"/>',
		'rocket'         => '<path d="M5 15c-1.5 1.3-2 5-2 5s3.7-.5 5-2c.8-.9.7-2.2-.1-3s-2.1-.9-2.9 0Z"/><path d="m12 15-3-3a22 22 0 0 1 2-4A12.9 12.9 0 0 1 22 2c0 2.7-.8 7.5-6 11a22.4 22.4 0 0 1-4 2Z"/><path d="M9 12H4s.6-3 2-4c1.6-1.1 5 0 5 0"/><path d="M12 15v5s3-.6 4-2c1.1-1.6 0-5 0-5"/>',
		'home'           => '<path d="M3.5 10.5 12 3.5l8.5 7"/><path d="M5.5 9v11.5h13V9"/><path d="M10 20.5v-6h4v6"/>',
		'logout'         => '<path d="M9.5 20.5h-4a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2h4"/><path d="m16 16.5 4.5-4.5L16 7.5"/><path d="M20.5 12H9.5"/>',
		'grid'           => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
		'list'           => '<path d="M8.5 6h12"/><path d="M8.5 12h12"/><path d="M8.5 18h12"/><path d="M3.5 6h.01"/><path d="M3.5 12h.01"/><path d="M3.5 18h.01"/>',
		'filter'         => '<path d="M3 4.5h18l-7 8.3v6.2l-4 2v-8.2Z"/>',
		'eye'            => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
		'calculator'     => '<rect x="4.5" y="2.5" width="15" height="19" rx="2.5"/><rect x="7.5" y="5.5" width="9" height="4" rx="1"/><path d="M8 13h.01"/><path d="M12 13h.01"/><path d="M16 13h.01"/><path d="M8 17h.01"/><path d="M12 17h.01"/><path d="M16 17h.01"/>',
		'play'           => '<path d="M7 4.5v15l12.5-7.5Z"/>',
		'quote'          => '<path d="M9.5 6.5c-3 .8-5 3.3-5 6.5V18h5.5v-5.5H7.2c.1-1.8 1.1-3 2.8-3.7Z"/><path d="M19 6.5c-3 .8-5 3.3-5 6.5V18h5.5v-5.5h-2.8c.1-1.8 1.1-3 2.8-3.7Z"/>',
		'external'       => '<path d="M14 3.5h6.5V10"/><path d="M20.5 3.5 11 13"/><path d="M18 14v5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 4 19V8a1.5 1.5 0 0 1 1.5-1.5h5"/>',
		'copy'           => '<rect x="8.5" y="8.5" width="12" height="12" rx="2"/><path d="M15.5 8.5V5.5a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h3"/>',
		'link'           => '<path d="M10 13.5a4.5 4.5 0 0 0 6.4.4l3-3a4.5 4.5 0 0 0-6.4-6.4l-1.6 1.6"/><path d="M14 10.5a4.5 4.5 0 0 0-6.4-.4l-3 3a4.5 4.5 0 0 0 6.4 6.4l1.6-1.6"/>',
		'share'          => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.2 13.3 7.6 4.4"/><path d="m15.8 6.3-7.6 4.4"/>',
		'hash'           => '<path d="M4 9h16"/><path d="M4 15h16"/><path d="M10 3 8 21"/><path d="m16 3-2 18"/>',
		'cube'           => '<path d="m12 2.5 8.5 4.8v9.4L12 21.5l-8.5-4.8V7.3Z"/><path d="m3.5 7.3 8.5 4.8 8.5-4.8"/><path d="M12 12.1v9.4"/>',
		'zap-off'        => '<path d="M12.4 7.6 13.5 2.5l-3 3.7"/><path d="M15.4 10.5h4.1l-3 3.6"/><path d="M9 9.2 4.5 13.5h7l-1 8 5-6"/><path d="m2.5 2.5 19 19"/>',
		'gauge'          => '<path d="M12 15.5 15.5 10"/><path d="M3.5 18a9 9 0 1 1 17 0"/><circle cx="12" cy="15.5" r="1.5"/>',
		'pickaxe'        => '<path d="M14.5 4.5c3-1 5.5-.5 7 1-1.5-.5-4-.2-6 1"/><path d="M19.5 9.5c1-3 .5-5.5-1-7 .5 1.5.2 4-1 6"/><path d="m16.5 7.5-13 13"/><path d="m13 3 8 8"/>',
		'container'      => '<path d="M2.5 7.5 12 3l9.5 4.5v9L12 21l-9.5-4.5Z"/><path d="M7 9.5v7"/><path d="M12 11.5v7"/><path d="M17 9.5v7"/>',
		'mouse'          => '<rect x="6" y="3" width="12" height="18" rx="6"/><path d="M12 7v3"/>',
		'command'        => '<path d="M15 6v12a3 3 0 1 0 3-3H6a3 3 0 1 0 3 3V6a3 3 0 1 0-3 3h12a3 3 0 1 0-3-3"/>',
		'pause'          => '<rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/>',

		// Social (simplified marks).
		'x-social'       => '<path d="M4 4l6.6 8.8L4.3 20h1.6l5.5-6.2L16 20h4l-6.9-9.3L19 4h-1.6l-5 5.7L8 4Z"/>',
		'youtube'        => '<path d="M21.6 7.2a2.6 2.6 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4a2.6 2.6 0 0 0-1.8 1.8C2 8.8 2 12 2 12s0 3.2.4 4.8a2.6 2.6 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.6 2.6 0 0 0 1.8-1.8c.4-1.6.4-4.8.4-4.8s0-3.2-.4-4.8Z"/><path d="m10 15 5.2-3L10 9Z"/>',
		'telegram'       => '<path d="M21.5 3.5 2.5 11l6.5 2.3 2.3 6.7 3.4-4 5 3.5Z"/><path d="m9 13.3 9-6.3-6.7 8"/>',
		'discord'        => '<path d="M8.5 5.5 8 4.5c-1.7.3-3.3.8-4.8 1.6C1.5 10 1 13.7 1.6 17.3a17 17 0 0 0 5 2.5l1.1-1.8"/><path d="m15.5 5.5.5-1c1.7.3 3.3.8 4.8 1.6 1.7 3.9 2.2 7.6 1.6 11.2a17 17 0 0 1-5 2.5l-1.1-1.8"/><path d="M6.5 16.5c3.5 1.7 7.5 1.7 11 0"/><path d="M6.5 7.5c3.5-1.7 7.5-1.7 11 0"/><circle cx="9" cy="12.5" r="1.4"/><circle cx="15" cy="12.5" r="1.4"/>',
		'linkedin'       => '<rect x="2.5" y="2.5" width="19" height="19" rx="3"/><path d="M7.5 10.5v6"/><path d="M7.5 7.5h.01"/><path d="M11.5 16.5v-6"/><path d="M11.5 13a2.5 2.5 0 0 1 5 0v3.5"/>',
		'instagram'      => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
		'facebook'       => '<path d="M14.5 21.5v-8h2.7l.4-3.2h-3.1V8.3c0-.9.3-1.6 1.6-1.6h1.7V3.9a22 22 0 0 0-2.5-.1c-2.5 0-4.2 1.5-4.2 4.3v2.2H8.4v3.2h2.7v8"/>',
		'tiktok'         => '<path d="M14 3.5v11.8a3.3 3.3 0 1 1-3.3-3.3"/><path d="M14 3.5a5 5 0 0 0 5 5"/>',
		'reddit'         => '<circle cx="12" cy="14" r="7"/><circle cx="18.5" cy="5" r="1.5"/><path d="m12 7 1.5-4.5 5 2.5"/><circle cx="9.3" cy="13.3" r="1"/><circle cx="14.7" cy="13.3" r="1"/><path d="M9.5 17c1.6.9 3.4.9 5 0"/>',
		'github'         => '<path d="M9 19c-4.3 1.4-4.3-2.5-6-3"/><path d="M15 21.5v-3.9a3.4 3.4 0 0 0-.9-2.6c3.1-.4 6.4-1.5 6.4-6.9a5.4 5.4 0 0 0-1.5-3.7 5 5 0 0 0-.1-3.8s-1.2-.4-3.9 1.5a13.4 13.4 0 0 0-7 0C5.3.2 4.1.6 4.1.6a5 5 0 0 0-.1 3.8 5.4 5.4 0 0 0-1.5 3.7c0 5.4 3.3 6.5 6.4 6.9a3.4 3.4 0 0 0-.9 2.6v3.9"/>',
	);

	return $icons;
}

/**
 * Return an inline SVG icon.
 *
 * @param string $name  Icon key.
 * @param string $class Extra classes.
 * @param int    $size  Width/height attribute.
 */
function mm_icon( string $name, string $class = '', int $size = 0 ): string {
	$icons = mm_icon_library();
	if ( 'x' === $name && false !== strpos( $class, 'social' ) ) {
		$name = 'x-social';
	}
	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}
	$size_attr = $size ? sprintf( ' width="%1$d" height="%1$d"', $size ) : '';
	return sprintf(
		'<svg class="mm-icon mm-icon-%1$s%2$s" viewBox="0 0 24 24"%3$s aria-hidden="true" focusable="false">%4$s</svg>',
		esc_attr( $name ),
		$class ? ' ' . esc_attr( $class ) : '',
		$size_attr,
		$icons[ $name ]
	);
}

/**
 * Icons available in Customizer dropdowns.
 */
function mm_icon_choices(): array {
	$skip    = array( 'x-social', 'youtube', 'telegram', 'discord', 'linkedin', 'instagram', 'facebook', 'tiktok', 'reddit', 'github', 'chevron-left', 'arrow-left' );
	$choices = array();
	foreach ( array_keys( mm_icon_library() ) as $key ) {
		if ( in_array( $key, $skip, true ) ) {
			continue;
		}
		$choices[ $key ] = ucwords( str_replace( '-', ' ', $key ) );
	}
	return $choices;
}

/**
 * Small text-based payment badges (no third-party artwork).
 */
function mm_payment_badges( string $list = '' ): string {
	$labels = array(
		'visa'       => 'VISA',
		'mastercard' => 'Mastercard',
		'amex'       => 'AMEX',
		'discover'   => 'Discover',
		'paypal'     => 'PayPal',
		'applepay'   => 'Apple Pay',
		'googlepay'  => 'Google Pay',
		'bitcoin'    => '₿ Bitcoin',
		'usdt'       => '₮ USDT',
		'bank'       => 'Bank transfer',
	);
	$list = $list ? $list : (string) mm_opt( 'checkout_payment_icons' );
	$keys = array_filter( array_map( 'trim', explode( ',', strtolower( $list ) ) ) );
	if ( ! $keys ) {
		return '';
	}
	$out = '<ul class="mm-pay-badges" aria-label="' . esc_attr__( 'Accepted payment methods', 'minemirth' ) . '">';
	foreach ( $keys as $key ) {
		if ( isset( $labels[ $key ] ) ) {
			$out .= '<li class="mm-pay-badge mm-pay-' . esc_attr( $key ) . '">' . esc_html( $labels[ $key ] ) . '</li>';
		}
	}
	return $out . '</ul>';
}
