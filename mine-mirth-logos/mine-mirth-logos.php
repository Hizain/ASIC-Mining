<?php
/**
 * Plugin Name:       Mine Mirth Logos
 * Description:       Separate header and footer logos for light and dark mode, with their own sizes. Set them in Appearance › Customize › Logos (light & dark). Needs the Mine Mirth theme 4.0.2 or later.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-logos
 *
 * @package MineMirthLogos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings: context => [label, default height in px].
 */
function mmlg_slots(): array {
	return array(
		'header' => array( __( 'Header', 'mine-mirth-logos' ), 40 ),
		'footer' => array( __( 'Footer', 'mine-mirth-logos' ), 44 ),
	);
}

add_action(
	'customize_register',
	static function ( WP_Customize_Manager $wp ) {
		$wp->add_section(
			'mmlg_logos',
			array(
				'title'       => __( 'Logos (light & dark)', 'mine-mirth-logos' ),
				'description' => __( 'Upload a logo for each place and mode. Leave one empty to use the light-mode logo of the same place, then the site logo.', 'mine-mirth-logos' ),
				'priority'    => 21,
			)
		);
		foreach ( mmlg_slots() as $slot => $info ) {
			foreach ( array( 'light' => __( 'light mode', 'mine-mirth-logos' ), 'dark' => __( 'dark mode', 'mine-mirth-logos' ) ) as $mode => $mode_label ) {
				$id = "mmlg_{$slot}_{$mode}";
				$wp->add_setting( $id, array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
				$wp->add_control(
					new WP_Customize_Media_Control(
						$wp,
						$id,
						array(
							/* translators: 1: Header/Footer 2: light mode/dark mode */
							'label'     => sprintf( __( '%1$s logo, %2$s', 'mine-mirth-logos' ), $info[0], $mode_label ),
							'section'   => 'mmlg_logos',
							'mime_type' => 'image',
						)
					)
				);
			}
			$hid = "mmlg_{$slot}_height";
			$wp->add_setting( $hid, array( 'default' => $info[1], 'sanitize_callback' => 'absint' ) );
			$wp->add_control(
				$hid,
				array(
					/* translators: %s: Header/Footer */
					'label'       => sprintf( __( '%s logo height (px)', 'mine-mirth-logos' ), $info[0] ),
					'section'     => 'mmlg_logos',
					'type'        => 'number',
					'input_attrs' => array( 'min' => 16, 'max' => 160, 'step' => 1 ),
				)
			);
		}
	}
);

/**
 * Which slot a theme logo context uses: header, drawer, loader and checkout follow the header; footer and sign-in follow the footer.
 */
function mmlg_slot_for( string $context ): string {
	return in_array( $context, array( 'footer', 'auth' ), true ) ? 'footer' : 'header';
}

function mmlg_img( int $id, string $mode, string $context ): string {
	return (string) wp_get_attachment_image(
		$id,
		'full',
		false,
		array(
			'class'         => 'mm-logo__img mmlg mmlg--' . $mode,
			'alt'           => get_bloginfo( 'name' ),
			'loading'       => 'header' === $context ? 'eager' : 'lazy',
			'decoding'      => 'async',
			'fetchpriority' => 'header' === $context && 'light' === $mode ? 'high' : 'auto',
		)
	);
}

add_filter(
	'mm_logo_html',
	static function ( $html, $context ) {
		$slot  = mmlg_slot_for( (string) $context );
		$light = (int) get_theme_mod( "mmlg_{$slot}_light" );
		$dark  = (int) get_theme_mod( "mmlg_{$slot}_dark" );
		if ( ! $light && ! $dark ) {
			return $html;
		}
		$light = $light ? $light : (int) get_theme_mod( 'custom_logo' );
		$out   = '';
		if ( $light ) {
			$out .= mmlg_img( $light, $dark ? 'light' : 'both', (string) $context );
		}
		if ( $dark ) {
			$out .= mmlg_img( $dark, $light ? 'dark' : 'both', (string) $context );
		}
		return $out ? '<span class="mm-logo__media mmlg-wrap mmlg-wrap--' . esc_attr( $slot ) . '">' . $out . '</span>' : $html;
	},
	10,
	2
);

add_action(
	'wp_head',
	static function () {
		$h = absint( get_theme_mod( 'mmlg_header_height', 40 ) );
		$f = absint( get_theme_mod( 'mmlg_footer_height', 44 ) );
		?>
		<style id="mmlg-css">
			.mmlg-wrap{display:inline-flex;align-items:center}
			.mmlg-wrap .mmlg{width:auto;max-width:none;object-fit:contain}
			.mmlg-wrap--header .mmlg{height:<?php echo (int) $h; ?>px}
			.mmlg-wrap--footer .mmlg{height:<?php echo (int) $f; ?>px}
			.mmlg--dark{display:none!important}
			:root[data-theme="dark"] .mmlg--light{display:none!important}
			:root[data-theme="dark"] .mmlg--dark{display:block!important}
			@media (max-width:640px){.mmlg-wrap--header .mmlg{height:<?php echo (int) max( 16, round( $h * 0.85 ) ); ?>px}}
		</style>
		<?php
	},
	20
);

// The theme needs the mm_logo_html filter, added in Mine Mirth 4.0.2.
add_action(
	'admin_notices',
	static function () {
		if ( defined( 'MM_VERSION' ) && version_compare( MM_VERSION, '4.0.2', '<' ) && current_user_can( 'switch_themes' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Mine Mirth Logos needs the Mine Mirth theme 4.0.2 or later. Please update the theme.', 'mine-mirth-logos' ) . '</p></div>';
		}
	}
);
