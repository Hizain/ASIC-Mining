<?php
/**
 * Customizer registration, selective refresh and live preview wiring.
 *
 * Everything is generated from inc/options.php.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

function mm_customize_register( WP_Customize_Manager $wpc ): void {
	require_once MM_DIR . '/inc/customizer/controls.php';

	$schema   = mm_options_schema();
	$sections = mm_options_sections();

	foreach ( mm_options_panels() as $id => $panel ) {
		$wpc->add_panel( $id, $panel );
	}
	$priority = 0;
	foreach ( $sections as $id => $section ) {
		$wpc->add_section(
			$id,
			array(
				'title'       => $section['title'],
				'panel'       => $section['panel'],
				'description' => $section['description'] ?? '',
				'priority'    => $priority += 5,
			)
		);
	}

	// Site Identity: live title/tagline.
	$wpc->get_setting( 'blogname' )->transport        = 'postMessage';
	$wpc->get_setting( 'blogdescription' )->transport = 'postMessage';

	$partial_settings = array();
	$priority         = 0;

	foreach ( $schema as $key => $field ) {
		$id       = 'mm_' . $key;
		$priority += 2;
		$type     = $field['type'];
		$common   = array(
			'label'       => $field['label'] ?? '',
			'description' => $field['description'] ?? '',
			'section'     => $field['section'],
			'priority'    => $priority,
		);

		if ( in_array( $type, array( 'heading', 'presets' ), true ) ) {
			$class = 'heading' === $type ? 'MM_Customize_Heading_Control' : 'MM_Customize_Presets_Control';
			$wpc->add_control( new $class( $wpc, $id, array_merge( $common, array( 'settings' => array() ) ) ) );
			continue;
		}

		$transport = $field['transport'] ?? 'refresh';
		$section   = $sections[ $field['section'] ] ?? array();
		// postMessage only works when something in the preview can apply it.
		if ( 'postMessage' === $transport && empty( $section['partial'] ) && empty( $field['css'] ) && empty( $field['body_class'] ) ) {
			$transport = 'refresh';
		}
		$wpc->add_setting(
			$id,
			array(
				'default'           => $field['default'] ?? '',
				'transport'         => $transport,
				'sanitize_callback' => 'mm_sanitize_setting',
				'type'              => 'theme_mod',
				'capability'        => 'edit_theme_options',
			)
		);

		// Settings that re-render through a section partial.
		if ( 'postMessage' === $transport && ! empty( $section['partial'] ) && empty( $field['css'] ) && empty( $field['body_class'] ) ) {
			$partial_settings[ $field['section'] ][] = $id;
		}

		switch ( $type ) {
			case 'color':
				$wpc->add_control( new WP_Customize_Color_Control( $wpc, $id, $common ) );
				break;
			case 'media':
				$wpc->add_control( new WP_Customize_Media_Control( $wpc, $id, array_merge( $common, array( 'mime_type' => 'image' ) ) ) );
				break;
			case 'toggle':
				$wpc->add_control( new MM_Customize_Toggle_Control( $wpc, $id, $common ) );
				break;
			case 'range':
				$control       = new MM_Customize_Range_Control(
					$wpc,
					$id,
					array_merge(
						$common,
						array(
							'input_attrs' => array(
								'min'  => $field['min'] ?? 0,
								'max'  => $field['max'] ?? 100,
								'step' => $field['step'] ?? 1,
							),
						)
					)
				);
				$control->unit = $field['unit'] ?? '';
				$wpc->add_control( $control );
				break;
			case 'sortable':
				$wpc->add_control( new MM_Customize_Sortable_Control( $wpc, $id, array_merge( $common, array( 'choices' => $field['choices'] ) ) ) );
				break;
			case 'select':
			case 'radio':
				$wpc->add_control( $id, array_merge( $common, array( 'type' => $type, 'choices' => $field['choices'] ) ) );
				break;
			case 'icon':
				$wpc->add_control( $id, array_merge( $common, array( 'type' => 'select', 'choices' => mm_icon_choices() ) ) );
				break;
			case 'page':
				$wpc->add_control( $id, array_merge( $common, array( 'type' => 'dropdown-pages' ) ) );
				break;
			case 'number':
				$wpc->add_control(
					$id,
					array_merge(
						$common,
						array(
							'type'        => 'number',
							'input_attrs' => array_filter(
								array(
									'min'  => $field['min'] ?? null,
									'max'  => $field['max'] ?? null,
									'step' => $field['step'] ?? 'any',
								),
								static fn( $v ) => null !== $v
							),
						)
					)
				);
				break;
			case 'textarea':
				$wpc->add_control( $id, array_merge( $common, array( 'type' => 'textarea' ) ) );
				break;
			case 'url':
				$wpc->add_control( $id, array_merge( $common, array( 'type' => 'text', 'input_attrs' => array( 'placeholder' => 'https://… or /path/ or {shop}' ) ) ) );
				break;
			default:
				$wpc->add_control( $id, array_merge( $common, array( 'type' => 'email' === $type ? 'email' : 'text' ) ) );
		}
	}

	// Selective refresh partials (section-level re-render).
	if ( isset( $wpc->selective_refresh ) ) {
		foreach ( $partial_settings as $section_id => $settings ) {
			$partial = $sections[ $section_id ]['partial'];
			$wpc->selective_refresh->add_partial(
				'mm_partial_' . $section_id,
				array(
					'selector'            => $partial['selector'],
					'settings'            => $settings,
					'container_inclusive' => 'mm_home_layout' !== $section_id,
					'fallback_refresh'    => true,
					'render_callback'     => static function () use ( $partial ) {
						return mm_get_template_html( $partial['template'] );
					},
				)
			);
		}
		$wpc->selective_refresh->add_partial(
			'mm_partial_blogname',
			array(
				'selector'        => '.mm-logo__text',
				'settings'        => array( 'blogname' ),
				'render_callback' => static fn() => esc_html( get_bloginfo( 'name' ) ),
			)
		);
	}
}
add_action( 'customize_register', 'mm_customize_register' );

/**
 * Config for the live preview script (CSS tokens + body classes).
 */
function mm_preview_config(): array {
	$css     = array();
	$classes = array();
	foreach ( mm_options_schema() as $key => $field ) {
		if ( ! empty( $field['css'] ) ) {
			$css[ 'mm_' . $key ] = array(
				'var'     => $field['css']['var'],
				'scope'   => $field['css']['scope'] ?? 'root',
				'unit'    => $field['css']['unit'] ?? '',
				'map'     => $field['css']['map'] ?? null,
				'divide'  => $field['css']['divide'] ?? null,
				'default' => $field['default'],
			);
		}
		if ( ! empty( $field['body_class'] ) ) {
			$classes[ 'mm_' . $key ] = array(
				'pattern' => $field['body_class'],
				'choices' => array_keys( (array) ( $field['choices'] ?? array() ) ),
			);
		}
	}
	return array(
		'css'     => $css,
		'classes' => $classes,
	);
}

/**
 * Controls panel assets.
 */
function mm_customize_controls_assets(): void {
	wp_enqueue_style( 'mm-customizer-controls', MM_URI . '/assets/css/customizer-controls.css', array(), MM_VERSION );
	wp_enqueue_script( 'mm-customizer-controls', MM_URI . '/assets/js/customizer-controls.js', array( 'customize-controls', 'jquery', 'jquery-ui-sortable' ), MM_VERSION, true );

	$deps = array();
	foreach ( mm_options_schema() as $key => $field ) {
		if ( ! empty( $field['show_if'] ) ) {
			$deps[ 'mm_' . $key ] = array( 'mm_' . $field['show_if'][0], $field['show_if'][1] );
		}
	}
	wp_add_inline_script(
		'mm-customizer-controls',
		'window.MM_CONTROLS=' . wp_json_encode(
			array(
				'deps'         => $deps,
				'homeSections' => array_keys( mm_home_section_choices() ),
			)
		) . ';',
		'before'
	);
}
add_action( 'customize_controls_enqueue_scripts', 'mm_customize_controls_assets' );
