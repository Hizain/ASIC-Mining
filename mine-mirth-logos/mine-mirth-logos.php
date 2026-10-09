<?php
/**
 * Plugin Name:       Mine Mirth Logos
 * Description:       Separate header and footer logos for light and dark mode, each with its own desktop and mobile size. Works with the Mine Mirth theme as installed (4.0 and later), no theme update needed. Settings: Appearance › Customize › Logos (light & dark).
 * Version:           1.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-logos
 *
 * How it works with the theme: the theme draws every logo with mm_logo(), which
 * reads two settings, the site logo (custom_logo) and "Logo for dark mode"
 * (mm_logo_dark). This plugin notes whether the header or the footer is being
 * drawn and hands those two settings the logos you chose for that place. Sizes
 * use the theme's own CSS variables (--mm-logo-h, --mm-logo-h-mobile).
 *
 * @package MineMirthLogos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Places: key => [label, which theme template starts it].
 */
function mmlg_places(): array {
	return array(
		'header' => __( 'Header', 'mine-mirth-logos' ),
		'footer' => __( 'Footer', 'mine-mirth-logos' ),
	);
}

/* ---------------------------------------------------------------------------
 * Customizer
 * ------------------------------------------------------------------------- */

add_action(
	'customize_register',
	static function ( WP_Customize_Manager $wp ) {
		$wp->add_section(
			'mmlg_logos',
			array(
				'title'       => __( 'Logos (light & dark)', 'mine-mirth-logos' ),
				'description' => __( 'Choose a logo for each place and mode. Any empty slot falls back to the theme’s own logo settings. Heights set to 0 keep the theme’s size.', 'mine-mirth-logos' ),
				'priority'    => 21,
			)
		);
		foreach ( mmlg_places() as $place => $label ) {
			foreach ( array( 'light' => __( 'light mode', 'mine-mirth-logos' ), 'dark' => __( 'dark mode', 'mine-mirth-logos' ) ) as $mode => $mode_label ) {
				$id = "mmlg_{$place}_{$mode}";
				$wp->add_setting( $id, array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
				$wp->add_control(
					new WP_Customize_Media_Control(
						$wp,
						$id,
						array(
							/* translators: 1: Header/Footer 2: light mode/dark mode */
							'label'     => sprintf( __( '%1$s logo, %2$s', 'mine-mirth-logos' ), $label, $mode_label ),
							'section'   => 'mmlg_logos',
							'mime_type' => 'image',
						)
					)
				);
			}
			foreach ( array( 'h' => __( 'height on desktop (px)', 'mine-mirth-logos' ), 'hm' => __( 'height on mobile (px)', 'mine-mirth-logos' ) ) as $key => $size_label ) {
				$id = "mmlg_{$place}_{$key}";
				$wp->add_setting( $id, array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
				$wp->add_control(
					$id,
					array(
						/* translators: 1: Header/Footer 2: size label */
						'label'       => sprintf( __( '%1$s logo %2$s', 'mine-mirth-logos' ), $label, $size_label ),
						'section'     => 'mmlg_logos',
						'type'        => 'number',
						'input_attrs' => array( 'min' => 0, 'max' => 200, 'step' => 1, 'placeholder' => '0' ),
					)
				);
			}
		}
		$wp->add_setting( 'mmlg_max_width', array( 'default' => 240, 'sanitize_callback' => 'absint' ) );
		$wp->add_control(
			'mmlg_max_width',
			array(
				'label'       => __( 'Maximum logo width (px)', 'mine-mirth-logos' ),
				'description' => __( 'Raise this for wide logos that look cut short.', 'mine-mirth-logos' ),
				'section'     => 'mmlg_logos',
				'type'        => 'number',
				'input_attrs' => array( 'min' => 80, 'max' => 800, 'step' => 10 ),
			)
		);
	}
);

/* ---------------------------------------------------------------------------
 * Which place is being drawn
 * ------------------------------------------------------------------------- */

$GLOBALS['mmlg_place'] = 'header';

// The theme loads its parts with get_template_part(); the footer's logo lives in template-parts/footer/main.
add_action(
	'get_template_part',
	static function ( $slug ) {
		$slug = (string) $slug;
		if ( 'template-parts/footer/main' === $slug ) {
			$GLOBALS['mmlg_place'] = 'footer';
		} elseif ( str_starts_with( $slug, 'template-parts/header/' ) ) {
			// Mobile menu and search overlay are printed at the end of the page but belong to the header.
			$GLOBALS['mmlg_place'] = 'header';
		}
	}
);

function mmlg_active(): bool {
	return ! is_admin() && ! wp_doing_ajax() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! wp_is_json_request();
}

// Hand the theme's two logo settings the logos chosen for the current place.
add_filter(
	'theme_mod_custom_logo',
	static function ( $value ) {
		if ( ! mmlg_active() ) {
			return $value;
		}
		$place = $GLOBALS['mmlg_place'] ?? 'header';
		$light = absint( get_theme_mod( "mmlg_{$place}_light" ) );
		if ( $light ) {
			return $light;
		}
		// Only a dark logo chosen and no site logo at all: use it so a logo still shows.
		$dark = absint( get_theme_mod( "mmlg_{$place}_dark" ) );
		return ( ! $value && $dark ) ? $dark : $value;
	},
	20
);
add_filter(
	'theme_mod_mm_logo_dark',
	static function ( $value ) {
		if ( ! mmlg_active() ) {
			return $value;
		}
		$place = $GLOBALS['mmlg_place'] ?? 'header';
		$dark  = absint( get_theme_mod( "mmlg_{$place}_dark" ) );
		return $dark ? $dark : $value;
	},
	20
);

/* ---------------------------------------------------------------------------
 * Sizes and light/dark switching
 * ------------------------------------------------------------------------- */

add_action(
	'wp_head',
	static function () {
		$css = '';
		$max = absint( get_theme_mod( 'mmlg_max_width', 240 ) );
		if ( $max && 240 !== $max ) {
			$css .= '.mm-logo .mm-logo__img{max-width:' . $max . 'px}';
		}
		$hh  = absint( get_theme_mod( 'mmlg_header_h' ) );
		$hhm = absint( get_theme_mod( 'mmlg_header_hm' ) );
		$fh  = absint( get_theme_mod( 'mmlg_footer_h' ) );
		$fhm = absint( get_theme_mod( 'mmlg_footer_hm' ) );
		// Header, mobile menu, checkout header and loader use the root values; the footer gets its own.
		if ( $hh ) {
			$css .= 'html:root{--mm-logo-h:' . $hh . 'px}';
		}
		if ( $hhm ) {
			$css .= 'html:root{--mm-logo-h-mobile:' . $hhm . 'px}';
		}
		if ( $fh || $fhm ) {
			$css .= '.mm-logo.mm-logo--footer{' . ( $fh ? '--mm-logo-h:' . $fh . 'px;' : '' ) . ( $fhm ? '--mm-logo-h-mobile:' . $fhm . 'px;' : '' ) . '}';
		}
		// The theme's footer has a dark background, so it always shows the dark logo there.
		// When both footer logos are chosen, follow the site's light/dark mode instead.
		if ( get_theme_mod( 'mmlg_footer_light' ) && get_theme_mod( 'mmlg_footer_dark' ) ) {
			$css .= '.mm-logo--footer .mm-logo__img--light{display:block!important}.mm-logo--footer .mm-logo__img--dark{display:none!important}';
			$css .= '[data-theme="dark"] .mm-logo--footer .mm-logo__img--light{display:none!important}[data-theme="dark"] .mm-logo--footer .mm-logo__img--dark{display:block!important}';
		}
		if ( $css ) {
			echo '<style id="mmlg-css">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- built from integers and fixed strings.
		}
	},
	99
);
