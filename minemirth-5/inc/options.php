<?php
/**
 * Theme options schema.
 *
 * One schema drives everything: Customizer panels/sections/controls, the
 * sanitizers, the default values, the CSS custom properties and the live
 * preview. Add a setting here and it appears in Appearance > Customize.
 *
 * Setting keys are stored as theme mods prefixed with "mm_".
 *
 * Field keys:
 *   section     Customizer section id.
 *   type        text|textarea|url|email|number|range|toggle|select|radio|color|media|sortable|heading|icon|page|presets
 *   label       Control label.
 *   description Optional help text.
 *   default     Default value.
 *   choices     Options for select/radio/sortable.
 *   min/max/step/unit  For range/number.
 *   transport   refresh (default) or postMessage.
 *   css         ['var' => '--mm-x', 'scope' => 'root|light|dark', 'unit' => 'px', 'map' => [...]].
 *   body_class  sprintf pattern added to <body> (e.g. 'mm-cards-%s').
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customizer panels.
 */
function mm_options_panels(): array {
	return array(
		'mm_global' => array(
			'title'       => __( 'Mine Mirth · Design System', 'minemirth' ),
			'description' => __( 'Brand colors, light & dark mode, typography, layout and motion.', 'minemirth' ),
			'priority'    => 21,
		),
		'mm_header' => array(
			'title'    => __( 'Mine Mirth · Header', 'minemirth' ),
			'priority' => 22,
		),
		'mm_home'   => array(
			'title'       => __( 'Mine Mirth · Homepage', 'minemirth' ),
			'description' => __( 'Reorder, show/hide and edit every homepage section. Changes preview live.', 'minemirth' ),
			'priority'    => 23,
		),
		'mm_blog'   => array(
			'title'    => __( 'Mine Mirth · Blog & Promotions', 'minemirth' ),
			'priority' => 24,
		),
		'mm_shop'   => array(
			'title'    => __( 'Mine Mirth · Shop, Checkout & Account', 'minemirth' ),
			'priority' => 25,
		),
		'mm_footer' => array(
			'title'    => __( 'Mine Mirth · Footer', 'minemirth' ),
			'priority' => 26,
		),
		'mm_misc'   => array(
			'title'    => __( 'Mine Mirth · Pages, Data & Advanced', 'minemirth' ),
			'priority' => 27,
		),
	);
}

/**
 * Customizer sections. A 'partial' lets the section re-render live without
 * a full preview refresh.
 */
function mm_options_sections(): array {
	$home_partial = static function ( string $id ): array {
		return array(
			'selector' => '#mm-section-' . $id,
			'template' => 'template-parts/home/' . $id,
		);
	};

	return array(
		// Design system.
		'mm_presets'          => array( 'panel' => 'mm_global', 'title' => __( 'Style presets', 'minemirth' ), 'description' => __( 'Apply a full color scheme in one click, then fine-tune any color below.', 'minemirth' ) ),
		'mm_colors'           => array( 'panel' => 'mm_global', 'title' => __( 'Brand colors', 'minemirth' ) ),
		'mm_mode'             => array( 'panel' => 'mm_global', 'title' => __( 'Light & dark mode', 'minemirth' ) ),
		'mm_typography'       => array( 'panel' => 'mm_global', 'title' => __( 'Typography', 'minemirth' ) ),
		'mm_layout'           => array( 'panel' => 'mm_global', 'title' => __( 'Layout & shape', 'minemirth' ) ),
		'mm_motion'           => array( 'panel' => 'mm_global', 'title' => __( 'Animation & effects', 'minemirth' ) ),

		// Header.
		'mm_announcement'     => array( 'panel' => 'mm_header', 'title' => __( 'Announcement bar', 'minemirth' ), 'partial' => array( 'selector' => '#mm-announcement', 'template' => 'template-parts/header/announcement' ) ),
		'mm_header_layout'    => array( 'panel' => 'mm_header', 'title' => __( 'Layout & behavior', 'minemirth' ) ),
		'mm_header_logo'      => array( 'panel' => 'mm_header', 'title' => __( 'Logo', 'minemirth' ), 'description' => __( 'Upload your main logo under Site Identity. Add an alternate logo here for dark mode.', 'minemirth' ) ),
		'mm_header_actions'   => array( 'panel' => 'mm_header', 'title' => __( 'Icons & call to action', 'minemirth' ) ),
		'mm_search'           => array( 'panel' => 'mm_header', 'title' => __( 'Search overlay', 'minemirth' ) ),

		// Homepage.
		'mm_home_layout'      => array( 'panel' => 'mm_home', 'title' => __( 'Sections: order & visibility', 'minemirth' ), 'partial' => array( 'selector' => '#mm-home-sections', 'template' => 'template-parts/home/sections' ) ),
		'mm_home_hero'        => array( 'panel' => 'mm_home', 'title' => __( 'Hero', 'minemirth' ), 'partial' => $home_partial( 'hero' ) ),
		'mm_home_ticker'      => array( 'panel' => 'mm_home', 'title' => __( 'Live ticker', 'minemirth' ), 'partial' => $home_partial( 'ticker' ) ),
		'mm_home_categories'  => array( 'panel' => 'mm_home', 'title' => __( 'Categories', 'minemirth' ), 'partial' => $home_partial( 'categories' ) ),
		'mm_home_products'    => array( 'panel' => 'mm_home', 'title' => __( 'Products', 'minemirth' ), 'partial' => $home_partial( 'products' ) ),
		'mm_home_features'    => array( 'panel' => 'mm_home', 'title' => __( 'Why choose us (bento)', 'minemirth' ), 'partial' => $home_partial( 'features' ) ),
		'mm_home_hosting'     => array( 'panel' => 'mm_home', 'title' => __( 'Hosting', 'minemirth' ), 'partial' => $home_partial( 'hosting' ) ),
		'mm_home_calculator'  => array( 'panel' => 'mm_home', 'title' => __( 'Calculator', 'minemirth' ), 'partial' => $home_partial( 'calculator' ) ),
		'mm_home_process'     => array( 'panel' => 'mm_home', 'title' => __( 'Process steps', 'minemirth' ), 'partial' => $home_partial( 'process' ) ),
		'mm_home_stats'       => array( 'panel' => 'mm_home', 'title' => __( 'Stats', 'minemirth' ), 'partial' => $home_partial( 'stats' ) ),
		'mm_home_testimonials'=> array( 'panel' => 'mm_home', 'title' => __( 'Testimonials', 'minemirth' ), 'partial' => $home_partial( 'testimonials' ) ),
		'mm_home_brands'      => array( 'panel' => 'mm_home', 'title' => __( 'Manufacturers', 'minemirth' ), 'partial' => $home_partial( 'brands' ) ),
		'mm_home_blog'        => array( 'panel' => 'mm_home', 'title' => __( 'Latest articles', 'minemirth' ), 'partial' => $home_partial( 'blog' ) ),
		'mm_home_faq'         => array( 'panel' => 'mm_home', 'title' => __( 'FAQ', 'minemirth' ), 'partial' => $home_partial( 'faq' ) ),
		'mm_home_newsletter'  => array( 'panel' => 'mm_home', 'title' => __( 'Newsletter', 'minemirth' ), 'partial' => $home_partial( 'newsletter' ) ),
		'mm_home_cta'         => array( 'panel' => 'mm_home', 'title' => __( 'Closing call to action', 'minemirth' ), 'partial' => $home_partial( 'cta' ) ),

		// Blog.
		'mm_blog_archive'     => array( 'panel' => 'mm_blog', 'title' => __( 'Blog index & archives', 'minemirth' ) ),
		'mm_blog_single'      => array( 'panel' => 'mm_blog', 'title' => __( 'Single article', 'minemirth' ) ),
		'mm_blog_promos'      => array( 'panel' => 'mm_blog', 'title' => __( 'In-content promotions', 'minemirth' ), 'description' => __( 'Native-looking product, hosting and calculator cards placed inside articles. Readers can add to cart, quick-view or calculate without leaving the post. Create targeted promotions under Promotions in the admin menu; these settings control placement and fallbacks.', 'minemirth' ) ),

		// Shop.
		'mm_shop_archive'     => array( 'panel' => 'mm_shop', 'title' => __( 'Shop & category pages', 'minemirth' ) ),
		'mm_shop_card'        => array( 'panel' => 'mm_shop', 'title' => __( 'Product cards', 'minemirth' ) ),
		'mm_shop_single'      => array( 'panel' => 'mm_shop', 'title' => __( 'Single product', 'minemirth' ) ),
		'mm_cart'             => array( 'panel' => 'mm_shop', 'title' => __( 'Cart & mini cart', 'minemirth' ) ),
		'mm_checkout'         => array( 'panel' => 'mm_shop', 'title' => __( 'Checkout', 'minemirth' ) ),
		'mm_thankyou'         => array( 'panel' => 'mm_shop', 'title' => __( 'Order confirmation', 'minemirth' ) ),
		'mm_account'          => array( 'panel' => 'mm_shop', 'title' => __( 'Customer account', 'minemirth' ) ),

		// Footer.
		'mm_footer_cta'       => array( 'panel' => 'mm_footer', 'title' => __( 'Footer call to action', 'minemirth' ) ),
		'mm_footer_main'      => array( 'panel' => 'mm_footer', 'title' => __( 'Footer content', 'minemirth' ) ),
		'mm_contact'          => array( 'panel' => 'mm_footer', 'title' => __( 'Contact details', 'minemirth' ) ),
		'mm_social'           => array( 'panel' => 'mm_footer', 'title' => __( 'Social links', 'minemirth' ) ),
		'mm_footer_bottom'    => array( 'panel' => 'mm_footer', 'title' => __( 'Bottom bar', 'minemirth' ) ),

		// Misc.
		'mm_pages'            => array( 'panel' => 'mm_misc', 'title' => __( 'Page headers & 404', 'minemirth' ) ),
		'mm_hosting_page'     => array( 'panel' => 'mm_misc', 'title' => __( 'Miner hosting page', 'minemirth' ) ),
		'mm_calculator'       => array( 'panel' => 'mm_misc', 'title' => __( 'Mining tools', 'minemirth' ) ),
		'mm_market'           => array( 'panel' => 'mm_misc', 'title' => __( 'Live market data', 'minemirth' ) ),
		'mm_newsletter'       => array( 'panel' => 'mm_misc', 'title' => __( 'Newsletter forms', 'minemirth' ) ),
		'mm_performance'      => array( 'panel' => 'mm_misc', 'title' => __( 'Performance & SEO', 'minemirth' ) ),
	);
}

/**
 * Font choices: label => CSS stack. Google families are loaded on demand.
 */
function mm_font_choices(): array {
	return array(
		'Sora'              => 'Sora',
		'Inter'             => 'Inter',
		'Manrope'           => 'Manrope',
		'Plus Jakarta Sans' => 'Plus Jakarta Sans',
		'Space Grotesk'     => 'Space Grotesk',
		'Outfit'            => 'Outfit',
		'DM Sans'           => 'DM Sans',
		'Urbanist'          => 'Urbanist',
		'Lexend'            => 'Lexend',
		'Figtree'           => 'Figtree',
		'IBM Plex Sans'     => 'IBM Plex Sans',
		'Poppins'           => 'Poppins',
		'Montserrat'        => 'Montserrat',
		'Rubik'             => 'Rubik',
		'Work Sans'         => 'Work Sans',
		'Onest'             => 'Onest',
		'Geist'             => 'Geist',
		'system'            => 'System UI (no download)',
	);
}

function mm_mono_font_choices(): array {
	return array(
		'JetBrains Mono' => 'JetBrains Mono',
		'IBM Plex Mono'  => 'IBM Plex Mono',
		'Space Mono'     => 'Space Mono',
		'Geist Mono'     => 'Geist Mono',
		'Roboto Mono'    => 'Roboto Mono',
		'system'         => 'System mono (no download)',
	);
}

/**
 * Homepage section registry: id => label.
 */
function mm_home_section_choices(): array {
	return array(
		'hero'         => __( 'Hero', 'minemirth' ),
		'ticker'       => __( 'Live ticker', 'minemirth' ),
		'categories'   => __( 'Categories', 'minemirth' ),
		'products'     => __( 'Products', 'minemirth' ),
		'features'     => __( 'Why choose us', 'minemirth' ),
		'hosting'      => __( 'Hosting', 'minemirth' ),
		'calculator'   => __( 'Calculator', 'minemirth' ),
		'process'      => __( 'Process steps', 'minemirth' ),
		'stats'        => __( 'Stats', 'minemirth' ),
		'testimonials' => __( 'Testimonials', 'minemirth' ),
		'brands'       => __( 'Manufacturers', 'minemirth' ),
		'blog'         => __( 'Latest articles', 'minemirth' ),
		'faq'          => __( 'FAQ', 'minemirth' ),
		'newsletter'   => __( 'Newsletter', 'minemirth' ),
		'cta'          => __( 'Closing call to action', 'minemirth' ),
	);
}

/**
 * Standard eyebrow/title/subtitle trio for a section.
 */
function mm_options_heading_fields( string $section, string $prefix, string $eyebrow, string $title, string $subtitle ): array {
	return array(
		$prefix . '_eyebrow'  => array( 'section' => $section, 'type' => 'text', 'label' => __( 'Eyebrow', 'minemirth' ), 'default' => $eyebrow, 'transport' => 'postMessage' ),
		$prefix . '_title'    => array( 'section' => $section, 'type' => 'text', 'label' => __( 'Title', 'minemirth' ), 'description' => __( 'Wrap words in *asterisks* to highlight them with the brand gradient.', 'minemirth' ), 'default' => $title, 'transport' => 'postMessage' ),
		$prefix . '_subtitle' => array( 'section' => $section, 'type' => 'textarea', 'label' => __( 'Subtitle', 'minemirth' ), 'default' => $subtitle, 'transport' => 'postMessage' ),
	);
}

/**
 * Build N repeated "slots" (e.g. 6 feature cards) from a field template.
 *
 * @param string $section  Section id.
 * @param string $prefix   Setting prefix, e.g. 'feature'.
 * @param array  $fields   field => [type, label].
 * @param array  $defaults List of rows (field => default).
 * @param int    $count    Number of slots.
 */
function mm_options_slots( string $section, string $prefix, array $fields, array $defaults, int $count ): array {
	$out = array();
	for ( $i = 1; $i <= $count; $i++ ) {
		$row   = $defaults[ $i - 1 ] ?? array();
		$first = true;
		foreach ( $fields as $field => $def ) {
			$setting = array(
				'section'   => $section,
				'type'      => $def[0],
				/* translators: 1: item label, 2: item number, 3: field label */
				'label'     => $first ? sprintf( __( '%1$s %2$d — %3$s', 'minemirth' ), $def[2] ?? ucfirst( $prefix ), $i, $def[1] ) : $def[1],
				'default'   => $row[ $field ] ?? ( 'icon' === $def[0] ? 'sparkles' : '' ),
				'transport' => 'postMessage',
			);
			if ( isset( $def[3] ) ) {
				$setting['choices'] = $def[3];
			}
			if ( $first ) {
				$setting['separator'] = true;
			}
			$out[ "{$prefix}_{$i}_{$field}" ] = $setting;
			$first                           = false;
		}
	}
	return $out;
}

/**
 * The full settings schema.
 */
function mm_options_schema(): array {
	static $schema = null;
	if ( null !== $schema ) {
		return $schema;
	}

	$s = array();

	/* ---------------------------------------------------------------------
	 * Presets
	 * ------------------------------------------------------------------- */
	$s['style_preset'] = array(
		'section'     => 'mm_presets',
		'type'        => 'presets',
		'label'       => __( 'Color presets', 'minemirth' ),
		'description' => __( 'Every preset keeps your #35a8f6 brand blue and changes the supporting palette.', 'minemirth' ),
	);

	/* ---------------------------------------------------------------------
	 * Brand colors
	 * ------------------------------------------------------------------- */
	$s += array(
		'color_primary'      => array( 'section' => 'mm_colors', 'type' => 'color', 'label' => __( 'Primary brand color', 'minemirth' ), 'default' => '#35a8f6', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-primary' ) ),
		'color_accent'       => array( 'section' => 'mm_colors', 'type' => 'color', 'label' => __( 'Gradient partner', 'minemirth' ), 'description' => __( 'Blends with the primary color in buttons, highlights and glows.', 'minemirth' ), 'default' => '#2563eb', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-accent' ) ),
		'color_accent_2'     => array( 'section' => 'mm_colors', 'type' => 'color', 'label' => __( 'Highlight color', 'minemirth' ), 'description' => __( 'Used sparingly for live indicators and sparkles.', 'minemirth' ), 'default' => '#5ee1ff', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-accent-2' ) ),
		'color_on_primary'   => array( 'section' => 'mm_colors', 'type' => 'color', 'label' => __( 'Text on primary buttons', 'minemirth' ), 'description' => __( 'Dark text keeps buttons readable on the bright brand blue.', 'minemirth' ), 'default' => '#03111f', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-on-primary' ) ),
		'color_link_light'   => array( 'section' => 'mm_colors', 'type' => 'color', 'label' => __( 'Link color (light mode)', 'minemirth' ), 'default' => '#0a6dc2', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-link', 'scope' => 'light' ) ),
		'color_link_dark'    => array( 'section' => 'mm_colors', 'type' => 'color', 'label' => __( 'Link color (dark mode)', 'minemirth' ), 'default' => '#7cc9ff', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-link', 'scope' => 'dark' ) ),
		'color_success'      => array( 'section' => 'mm_colors', 'type' => 'color', 'label' => __( 'Success', 'minemirth' ), 'default' => '#12b981', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-success' ) ),
		'color_warning'      => array( 'section' => 'mm_colors', 'type' => 'color', 'label' => __( 'Warning', 'minemirth' ), 'default' => '#f5a524', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-warning' ) ),
		'color_danger'       => array( 'section' => 'mm_colors', 'type' => 'color', 'label' => __( 'Danger / sale', 'minemirth' ), 'default' => '#f04461', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-danger' ) ),
		'gradient_angle'     => array( 'section' => 'mm_colors', 'type' => 'range', 'label' => __( 'Gradient angle', 'minemirth' ), 'default' => 120, 'min' => 0, 'max' => 360, 'step' => 5, 'unit' => 'deg', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-grad-angle', 'unit' => 'deg' ) ),
	);

	/* ---------------------------------------------------------------------
	 * Light & dark mode
	 * ------------------------------------------------------------------- */
	$s += array(
		'mode_default'      => array(
			'section' => 'mm_mode',
			'type'    => 'select',
			'label'   => __( 'Default appearance for new visitors', 'minemirth' ),
			'default' => 'system',
			'choices' => array(
				'system' => __( 'Follow device setting', 'minemirth' ),
				'light'  => __( 'Light', 'minemirth' ),
				'dark'   => __( 'Dark', 'minemirth' ),
			),
		),
		'mode_toggle'       => array( 'section' => 'mm_mode', 'type' => 'toggle', 'label' => __( 'Show light/dark switch in the header', 'minemirth' ), 'default' => true ),
		'mode_transition'   => array( 'section' => 'mm_mode', 'type' => 'toggle', 'label' => __( 'Animated circular reveal when switching', 'minemirth' ), 'default' => true ),
		'heading_light'     => array( 'section' => 'mm_mode', 'type' => 'heading', 'label' => __( 'Light mode palette', 'minemirth' ) ),
		'light_bg'          => array( 'section' => 'mm_mode', 'type' => 'color', 'label' => __( 'Page background', 'minemirth' ), 'default' => '#f4f8fc', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-bg', 'scope' => 'light' ) ),
		'light_bg_alt'      => array( 'section' => 'mm_mode', 'type' => 'color', 'label' => __( 'Alternate section background', 'minemirth' ), 'default' => '#eaf2fa', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-bg-alt', 'scope' => 'light' ) ),
		'light_surface'     => array( 'section' => 'mm_mode', 'type' => 'color', 'label' => __( 'Cards & panels', 'minemirth' ), 'default' => '#ffffff', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-surface', 'scope' => 'light' ) ),
		'light_text'        => array( 'section' => 'mm_mode', 'type' => 'color', 'label' => __( 'Text', 'minemirth' ), 'default' => '#0a1626', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-text', 'scope' => 'light' ) ),
		'light_muted'       => array( 'section' => 'mm_mode', 'type' => 'color', 'label' => __( 'Secondary text', 'minemirth' ), 'default' => '#506279', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-muted', 'scope' => 'light' ) ),
		'light_border'      => array( 'section' => 'mm_mode', 'type' => 'color', 'label' => __( 'Borders', 'minemirth' ), 'default' => '#d9e4ef', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-border', 'scope' => 'light' ) ),
		'heading_dark'      => array( 'section' => 'mm_mode', 'type' => 'heading', 'label' => __( 'Dark mode palette', 'minemirth' ) ),
		'dark_bg'           => array( 'section' => 'mm_mode', 'type' => 'color', 'label' => __( 'Page background', 'minemirth' ), 'default' => '#050b14', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-bg', 'scope' => 'dark' ) ),
		'dark_bg_alt'       => array( 'section' => 'mm_mode', 'type' => 'color', 'label' => __( 'Alternate section background', 'minemirth' ), 'default' => '#08121f', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-bg-alt', 'scope' => 'dark' ) ),
		'dark_surface'      => array( 'section' => 'mm_mode', 'type' => 'color', 'label' => __( 'Cards & panels', 'minemirth' ), 'default' => '#0b1624', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-surface', 'scope' => 'dark' ) ),
		'dark_text'         => array( 'section' => 'mm_mode', 'type' => 'color', 'label' => __( 'Text', 'minemirth' ), 'default' => '#e7f0fa', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-text', 'scope' => 'dark' ) ),
		'dark_muted'        => array( 'section' => 'mm_mode', 'type' => 'color', 'label' => __( 'Secondary text', 'minemirth' ), 'default' => '#8ea3bb', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-muted', 'scope' => 'dark' ) ),
		'dark_border'       => array( 'section' => 'mm_mode', 'type' => 'color', 'label' => __( 'Borders', 'minemirth' ), 'default' => '#172a3f', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-border', 'scope' => 'dark' ) ),
		'dark_glow'         => array( 'section' => 'mm_mode', 'type' => 'range', 'label' => __( 'Ambient glow strength (dark mode)', 'minemirth' ), 'default' => 60, 'min' => 0, 'max' => 100, 'step' => 5, 'unit' => '%', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-glow', 'scope' => 'dark', 'divide' => 100 ) ),
	);

	/* ---------------------------------------------------------------------
	 * Typography
	 * ------------------------------------------------------------------- */
	$s += array(
		'font_heading'        => array( 'section' => 'mm_typography', 'type' => 'select', 'label' => __( 'Heading font', 'minemirth' ), 'default' => 'Sora', 'choices' => mm_font_choices() ),
		'font_body'           => array( 'section' => 'mm_typography', 'type' => 'select', 'label' => __( 'Body font', 'minemirth' ), 'default' => 'Inter', 'choices' => mm_font_choices() ),
		'font_mono'           => array( 'section' => 'mm_typography', 'type' => 'select', 'label' => __( 'Data / numbers font', 'minemirth' ), 'default' => 'JetBrains Mono', 'choices' => mm_mono_font_choices() ),
		'font_local'          => array( 'section' => 'mm_typography', 'type' => 'toggle', 'label' => __( 'Privacy mode: never load Google Fonts', 'minemirth' ), 'description' => __( 'Uses system fonts only. Good for strict GDPR setups.', 'minemirth' ), 'default' => false ),
		'font_size_base'      => array( 'section' => 'mm_typography', 'type' => 'range', 'label' => __( 'Base font size', 'minemirth' ), 'default' => 16, 'min' => 14, 'max' => 20, 'step' => 0.5, 'unit' => 'px', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-fs-base', 'unit' => 'px' ) ),
		'font_line_height'    => array( 'section' => 'mm_typography', 'type' => 'range', 'label' => __( 'Body line height', 'minemirth' ), 'default' => 1.65, 'min' => 1.3, 'max' => 2, 'step' => 0.05, 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-lh' ) ),
		'heading_weight'      => array( 'section' => 'mm_typography', 'type' => 'select', 'label' => __( 'Heading weight', 'minemirth' ), 'default' => '600', 'choices' => array( '400' => 'Regular 400', '500' => 'Medium 500', '600' => 'Semibold 600', '700' => 'Bold 700', '800' => 'Extra bold 800' ), 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-heading-weight' ) ),
		'heading_tracking'    => array( 'section' => 'mm_typography', 'type' => 'range', 'label' => __( 'Heading letter spacing', 'minemirth' ), 'default' => -0.03, 'min' => -0.08, 'max' => 0.05, 'step' => 0.005, 'unit' => 'em', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-heading-tracking', 'unit' => 'em' ) ),
		'heading_scale'       => array( 'section' => 'mm_typography', 'type' => 'range', 'label' => __( 'Heading size scale', 'minemirth' ), 'default' => 1, 'min' => 0.8, 'max' => 1.25, 'step' => 0.05, 'unit' => '×', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-heading-scale' ) ),
	);

	/* ---------------------------------------------------------------------
	 * Layout & shape
	 * ------------------------------------------------------------------- */
	$s += array(
		'container_width' => array( 'section' => 'mm_layout', 'type' => 'range', 'label' => __( 'Content width', 'minemirth' ), 'default' => 1280, 'min' => 1040, 'max' => 1680, 'step' => 10, 'unit' => 'px', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-container', 'unit' => 'px' ) ),
		'radius'          => array( 'section' => 'mm_layout', 'type' => 'range', 'label' => __( 'Corner radius', 'minemirth' ), 'default' => 18, 'min' => 0, 'max' => 36, 'step' => 1, 'unit' => 'px', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-radius', 'unit' => 'px' ) ),
		'button_shape'    => array(
			'section'   => 'mm_layout',
			'type'      => 'select',
			'label'     => __( 'Button shape', 'minemirth' ),
			'default'   => 'pill',
			'choices'   => array( 'pill' => __( 'Pill', 'minemirth' ), 'rounded' => __( 'Rounded', 'minemirth' ), 'square' => __( 'Square', 'minemirth' ) ),
			'transport' => 'postMessage',
			'css'       => array( 'var' => '--mm-btn-radius', 'map' => array( 'pill' => '999px', 'rounded' => '12px', 'square' => '4px' ) ),
		),
		'card_style'      => array(
			'section'    => 'mm_layout',
			'type'       => 'select',
			'label'      => __( 'Card style', 'minemirth' ),
			'default'    => 'glass',
			'choices'    => array( 'bordered' => __( 'Bordered', 'minemirth' ), 'elevated' => __( 'Elevated (shadow)', 'minemirth' ), 'glass' => __( 'Glass (frosted)', 'minemirth' ), 'flat' => __( 'Flat', 'minemirth' ) ),
			'transport'  => 'postMessage',
			'body_class' => 'mm-cards-%s',
		),
		'section_spacing' => array( 'section' => 'mm_layout', 'type' => 'range', 'label' => __( 'Space between sections', 'minemirth' ), 'default' => 112, 'min' => 48, 'max' => 180, 'step' => 4, 'unit' => 'px', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-section-y', 'unit' => 'px' ) ),
		'bg_texture'      => array(
			'section'    => 'mm_layout',
			'type'       => 'select',
			'label'      => __( 'Background texture', 'minemirth' ),
			'default'    => 'grid',
			'choices'    => array( 'none' => __( 'None', 'minemirth' ), 'grid' => __( 'Blueprint grid', 'minemirth' ), 'dots' => __( 'Dot matrix', 'minemirth' ), 'noise' => __( 'Film grain', 'minemirth' ) ),
			'transport'  => 'postMessage',
			'body_class' => 'mm-texture-%s',
		),
		'back_to_top'     => array( 'section' => 'mm_layout', 'type' => 'toggle', 'label' => __( 'Back-to-top button', 'minemirth' ), 'default' => true ),
		'breadcrumbs'     => array( 'section' => 'mm_layout', 'type' => 'toggle', 'label' => __( 'Show breadcrumbs', 'minemirth' ), 'default' => true ),
	);

	/* ---------------------------------------------------------------------
	 * Motion
	 * ------------------------------------------------------------------- */
	$s += array(
		'anim_enable'     => array( 'section' => 'mm_motion', 'type' => 'toggle', 'label' => __( 'Scroll reveal animations', 'minemirth' ), 'description' => __( 'Always disabled for visitors who ask their device to reduce motion.', 'minemirth' ), 'default' => true ),
		'anim_style'      => array(
			'section'    => 'mm_motion',
			'type'       => 'select',
			'label'      => __( 'Reveal style', 'minemirth' ),
			'default'    => 'rise',
			'choices'    => array( 'rise' => __( 'Rise & fade', 'minemirth' ), 'fade' => __( 'Fade', 'minemirth' ), 'blur' => __( 'Blur in', 'minemirth' ), 'scale' => __( 'Scale up', 'minemirth' ) ),
			'transport'  => 'postMessage',
			'body_class' => 'mm-reveal-%s',
		),
		'anim_speed'      => array( 'section' => 'mm_motion', 'type' => 'range', 'label' => __( 'Animation speed', 'minemirth' ), 'default' => 700, 'min' => 300, 'max' => 1400, 'step' => 50, 'unit' => 'ms', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-reveal-duration', 'unit' => 'ms' ) ),
		'fx_spotlight'    => array( 'section' => 'mm_motion', 'type' => 'toggle', 'label' => __( 'Cursor spotlight on cards', 'minemirth' ), 'default' => true ),
		'fx_magnetic'     => array( 'section' => 'mm_motion', 'type' => 'toggle', 'label' => __( 'Magnetic primary buttons', 'minemirth' ), 'default' => true ),
		'fx_tilt'         => array( 'section' => 'mm_motion', 'type' => 'toggle', 'label' => __( '3D tilt on hero visuals', 'minemirth' ), 'default' => true ),
		'fx_page_loader'  => array( 'section' => 'mm_motion', 'type' => 'toggle', 'label' => __( 'Brand loading screen on first visit', 'minemirth' ), 'default' => false ),
		'fx_shapes'       => array( 'section' => 'mm_motion', 'type' => 'toggle', 'label' => __( 'Crypto vector shapes', 'minemirth' ), 'description' => __( 'Coins, blocks, circuits and network lines that drift behind sections. Pure CSS, paused when off screen.', 'minemirth' ), 'default' => true ),
		'fx_guide'        => array( 'section' => 'mm_motion', 'type' => 'toggle', 'label' => __( 'Live guide bubble', 'minemirth' ), 'description' => __( 'A small assistant that tells visitors what today’s numbers mean for the page they are on (profit of this miner, hashprice, next difficulty change). Hidden on cart, checkout and account pages.', 'minemirth' ), 'default' => true ),
		'fx_guide_delay'  => array( 'section' => 'mm_motion', 'type' => 'range', 'label' => __( 'Guide appears after', 'minemirth' ), 'default' => 6, 'min' => 2, 'max' => 30, 'step' => 1, 'unit' => 's' ),
	);

	/* ---------------------------------------------------------------------
	 * Header
	 * ------------------------------------------------------------------- */
	$s += array(
		'announce_enable'      => array( 'section' => 'mm_announcement', 'type' => 'toggle', 'label' => __( 'Show announcement bar', 'minemirth' ), 'default' => true ),
		'announce_text'        => array( 'section' => 'mm_announcement', 'type' => 'text', 'label' => __( 'Message', 'minemirth' ), 'default' => __( 'New: live mining profitability calculator with real-time network data', 'minemirth' ), 'transport' => 'postMessage' ),
		'announce_link_text'   => array( 'section' => 'mm_announcement', 'type' => 'text', 'label' => __( 'Link text', 'minemirth' ), 'default' => __( 'Try it free', 'minemirth' ), 'transport' => 'postMessage' ),
		'announce_link'        => array( 'section' => 'mm_announcement', 'type' => 'url', 'label' => __( 'Link URL', 'minemirth' ), 'default' => '/free-calculators/bitcoin-mining-calculator/', 'transport' => 'postMessage' ),
		'announce_style'       => array( 'section' => 'mm_announcement', 'type' => 'select', 'label' => __( 'Style', 'minemirth' ), 'default' => 'gradient', 'choices' => array( 'gradient' => __( 'Brand gradient', 'minemirth' ), 'dark' => __( 'Dark', 'minemirth' ), 'subtle' => __( 'Subtle', 'minemirth' ) ), 'transport' => 'postMessage' ),
		'announce_live'        => array( 'section' => 'mm_announcement', 'type' => 'toggle', 'label' => __( 'Show live BTC price on the right', 'minemirth' ), 'default' => true, 'transport' => 'postMessage' ),
		'announce_dismissible' => array( 'section' => 'mm_announcement', 'type' => 'toggle', 'label' => __( 'Visitors can close it', 'minemirth' ), 'default' => true, 'transport' => 'postMessage' ),

		'header_layout'        => array(
			'section' => 'mm_header_layout',
			'type'    => 'select',
			'label'   => __( 'Header layout', 'minemirth' ),
			'default' => 'split',
			'choices' => array(
				'split'  => __( 'Logo left · menu center · icons right', 'minemirth' ),
				'left'   => __( 'Logo and menu left · icons right', 'minemirth' ),
				'center' => __( 'Menu left · logo center · icons right', 'minemirth' ),
			),
		),
		'header_sticky'        => array( 'section' => 'mm_header_layout', 'type' => 'toggle', 'label' => __( 'Sticky header', 'minemirth' ), 'default' => true ),
		'header_autohide'      => array( 'section' => 'mm_header_layout', 'type' => 'toggle', 'label' => __( 'Hide while scrolling down, show when scrolling up', 'minemirth' ), 'default' => false ),
		'header_transparent'   => array( 'section' => 'mm_header_layout', 'type' => 'toggle', 'label' => __( 'Transparent over the homepage hero', 'minemirth' ), 'default' => true ),
		'header_height'        => array( 'section' => 'mm_header_layout', 'type' => 'range', 'label' => __( 'Header height', 'minemirth' ), 'default' => 76, 'min' => 56, 'max' => 110, 'step' => 2, 'unit' => 'px', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-header-h', 'unit' => 'px' ) ),
		'header_menu_style'    => array( 'section' => 'mm_header_layout', 'type' => 'select', 'label' => __( 'Menu hover style', 'minemirth' ), 'default' => 'pill', 'choices' => array( 'pill' => __( 'Glass pill', 'minemirth' ), 'underline' => __( 'Underline', 'minemirth' ), 'plain' => __( 'Color only', 'minemirth' ) ), 'transport' => 'postMessage', 'body_class' => 'mm-menu-%s' ),

		'logo_height'          => array( 'section' => 'mm_header_logo', 'type' => 'range', 'label' => __( 'Logo height (desktop)', 'minemirth' ), 'default' => 34, 'min' => 18, 'max' => 90, 'step' => 1, 'unit' => 'px', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-logo-h', 'unit' => 'px' ) ),
		'logo_height_mobile'   => array( 'section' => 'mm_header_logo', 'type' => 'range', 'label' => __( 'Logo height (mobile)', 'minemirth' ), 'default' => 28, 'min' => 16, 'max' => 60, 'step' => 1, 'unit' => 'px', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-logo-h-mobile', 'unit' => 'px' ) ),
		'logo_dark'            => array( 'section' => 'mm_header_logo', 'type' => 'media', 'label' => __( 'Logo for dark mode', 'minemirth' ), 'description' => __( 'Optional. A light version of your logo shown when dark mode is active.', 'minemirth' ), 'default' => 0 ),
		'logo_text_mark'       => array( 'section' => 'mm_header_logo', 'type' => 'text', 'label' => __( 'Text logo mark (when no logo uploaded)', 'minemirth' ), 'default' => 'M', 'transport' => 'postMessage' ),

		'header_search'        => array( 'section' => 'mm_header_actions', 'type' => 'toggle', 'label' => __( 'Search button', 'minemirth' ), 'default' => true ),
		'header_account'       => array( 'section' => 'mm_header_actions', 'type' => 'toggle', 'label' => __( 'Account button', 'minemirth' ), 'default' => true ),
		'header_cart'          => array( 'section' => 'mm_header_actions', 'type' => 'toggle', 'label' => __( 'Cart button', 'minemirth' ), 'default' => true ),
		'header_cta'           => array( 'section' => 'mm_header_actions', 'type' => 'toggle', 'label' => __( 'Call-to-action button', 'minemirth' ), 'default' => true ),
		'header_cta_text'      => array( 'section' => 'mm_header_actions', 'type' => 'text', 'label' => __( 'Button text', 'minemirth' ), 'default' => __( 'Shop miners', 'minemirth' ), 'transport' => 'postMessage' ),
		'header_cta_url'       => array( 'section' => 'mm_header_actions', 'type' => 'url', 'label' => __( 'Button URL', 'minemirth' ), 'description' => __( 'Use {shop}, {account}, {cart} or {blog} for automatic store links.', 'minemirth' ), 'default' => '{shop}' ),

		'search_products'      => array( 'section' => 'mm_search', 'type' => 'toggle', 'label' => __( 'Live product results', 'minemirth' ), 'default' => true ),
		'search_posts'         => array( 'section' => 'mm_search', 'type' => 'toggle', 'label' => __( 'Live article & page results', 'minemirth' ), 'default' => true ),
		'search_placeholder'   => array( 'section' => 'mm_search', 'type' => 'text', 'label' => __( 'Placeholder', 'minemirth' ), 'default' => __( 'Search miners, guides, hosting…', 'minemirth' ) ),
		'search_popular'       => array( 'section' => 'mm_search', 'type' => 'text', 'label' => __( 'Popular searches (comma separated)', 'minemirth' ), 'default' => 'Antminer S21, Whatsminer, Scrypt miner, Hosting, Calculator' ),
	);

	/* ---------------------------------------------------------------------
	 * Homepage
	 * ------------------------------------------------------------------- */
	$s['home_sections'] = array(
		'section'     => 'mm_home_layout',
		'type'        => 'sortable',
		'label'       => __( 'Homepage sections', 'minemirth' ),
		'description' => __( 'Drag to reorder. Untick to hide. Click a name to edit that section.', 'minemirth' ),
		'default'     => 'hero,ticker,categories,products,features,hosting,calculator,process,testimonials,brands,blog,faq,newsletter,cta,-stats',
		'choices'     => mm_home_section_choices(),
		'transport'   => 'postMessage',
	);
	$s['home_mode'] = array(
		'section'     => 'mm_home_layout',
		'type'        => 'select',
		'label'       => __( 'Homepage content source', 'minemirth' ),
		'description' => __( 'Show the theme sections, your own page content (e.g. from a page builder), or both.', 'minemirth' ),
		'default'     => 'sections',
		'choices'     => array(
			'sections' => __( 'Theme sections only', 'minemirth' ),
			'both'     => __( 'Theme sections, then page content', 'minemirth' ),
			'content'  => __( 'Page content only', 'minemirth' ),
		),
	);

	// Hero.
	$s += array(
		'hero_layout'        => array( 'section' => 'mm_home_hero', 'type' => 'select', 'label' => __( 'Layout', 'minemirth' ), 'default' => 'split', 'choices' => array( 'split' => __( 'Text left · visual right', 'minemirth' ), 'centered' => __( 'Centered text · visual below', 'minemirth' ) ), 'transport' => 'postMessage' ),
		'hero_visual'        => array(
			'section'   => 'mm_home_hero',
			'type'      => 'select',
			'label'     => __( 'Visual', 'minemirth' ),
			'default'   => 'sphere',
			'choices'   => array(
				'sphere'  => __( 'Animated hash network sphere', 'minemirth' ),
				'rig'     => __( 'Animated ASIC miner', 'minemirth' ),
				'product' => __( 'Featured product showcase', 'minemirth' ),
				'image'   => __( 'Custom image', 'minemirth' ),
				'video'   => __( 'Background video', 'minemirth' ),
				'none'    => __( 'None', 'minemirth' ),
			),
			'transport' => 'postMessage',
		),
		'hero_background'    => array(
			'section'   => 'mm_home_hero',
			'type'      => 'select',
			'label'     => __( 'Background effect', 'minemirth' ),
			'default'   => 'aurora',
			'choices'   => array( 'aurora' => __( 'Aurora glow + grid', 'minemirth' ), 'particles' => __( 'Floating hash particles', 'minemirth' ), 'beams' => __( 'Light beams', 'minemirth' ), 'plain' => __( 'Plain', 'minemirth' ) ),
			'transport' => 'postMessage',
		),
		'hero_eyebrow'       => array( 'section' => 'mm_home_hero', 'type' => 'text', 'label' => __( 'Eyebrow', 'minemirth' ), 'default' => __( 'ASIC hardware · Hosting · Live network data', 'minemirth' ), 'transport' => 'postMessage' ),
		'hero_title'         => array( 'section' => 'mm_home_hero', 'type' => 'text', 'label' => __( 'Headline', 'minemirth' ), 'description' => __( 'Use {rotate} where the rotating words should appear, and *asterisks* for gradient text.', 'minemirth' ), 'default' => __( 'The professional way to mine *{rotate}*', 'minemirth' ), 'transport' => 'postMessage' ),
		'hero_rotate'        => array( 'section' => 'mm_home_hero', 'type' => 'text', 'label' => __( 'Rotating words (comma separated)', 'minemirth' ), 'default' => 'Bitcoin, Litecoin, Dogecoin, Kaspa', 'transport' => 'postMessage' ),
		'hero_text'          => array( 'section' => 'mm_home_hero', 'type' => 'textarea', 'label' => __( 'Supporting text', 'minemirth' ), 'default' => __( 'Shop high-efficiency ASIC miners, model returns with live network data, and deploy into professional hosting facilities — with one team that speaks mining.', 'minemirth' ), 'transport' => 'postMessage' ),
		'hero_btn1_text'     => array( 'section' => 'mm_home_hero', 'type' => 'text', 'label' => __( 'Primary button text', 'minemirth' ), 'default' => __( 'Shop ASIC miners', 'minemirth' ), 'transport' => 'postMessage' ),
		'hero_btn1_url'      => array( 'section' => 'mm_home_hero', 'type' => 'url', 'label' => __( 'Primary button URL', 'minemirth' ), 'default' => '{shop}', 'transport' => 'postMessage' ),
		'hero_btn2_text'     => array( 'section' => 'mm_home_hero', 'type' => 'text', 'label' => __( 'Secondary button text', 'minemirth' ), 'default' => __( 'Explore hosting', 'minemirth' ), 'transport' => 'postMessage' ),
		'hero_btn2_url'      => array( 'section' => 'mm_home_hero', 'type' => 'url', 'label' => __( 'Secondary button URL', 'minemirth' ), 'default' => '/miner-hosting/', 'transport' => 'postMessage' ),
		'hero_trust'         => array( 'section' => 'mm_home_hero', 'type' => 'textarea', 'label' => __( 'Trust points (one per line)', 'minemirth' ), 'default' => "Secure, encrypted checkout\nTransparent hosting terms\nLive, timestamped market data", 'transport' => 'postMessage' ),
		'hero_live_cards'    => array( 'section' => 'mm_home_hero', 'type' => 'toggle', 'label' => __( 'Floating live data cards', 'minemirth' ), 'default' => true, 'transport' => 'postMessage' ),
		'hero_product'       => array( 'section' => 'mm_home_hero', 'type' => 'number', 'label' => __( 'Showcase product ID', 'minemirth' ), 'description' => __( 'For the "Featured product showcase" visual and the floating product card. Leave 0 to use your newest featured product.', 'minemirth' ), 'default' => 0, 'min' => 0, 'step' => 1, 'transport' => 'postMessage' ),
		'hero_image'         => array( 'section' => 'mm_home_hero', 'type' => 'media', 'label' => __( 'Custom image', 'minemirth' ), 'default' => 0, 'transport' => 'postMessage' ),
		'hero_video'         => array( 'section' => 'mm_home_hero', 'type' => 'url', 'label' => __( 'Background video URL (.mp4)', 'minemirth' ), 'default' => '', 'transport' => 'postMessage' ),
		'hero_height'        => array( 'section' => 'mm_home_hero', 'type' => 'range', 'label' => __( 'Minimum height', 'minemirth' ), 'default' => 92, 'min' => 50, 'max' => 100, 'step' => 2, 'unit' => 'vh', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-hero-h', 'unit' => 'vh' ) ),
		'hero_density'       => array( 'section' => 'mm_home_hero', 'type' => 'range', 'label' => __( 'Animation density', 'minemirth' ), 'default' => 100, 'min' => 30, 'max' => 160, 'step' => 10, 'unit' => '%', 'transport' => 'postMessage' ),
	);

	// Ticker.
	$s += array(
		'ticker_metrics' => array( 'section' => 'mm_home_ticker', 'type' => 'text', 'label' => __( 'Live metrics to show', 'minemirth' ), 'description' => __( 'Comma separated: price, change, hashrate, difficulty, height, fees, adjustment, halving', 'minemirth' ), 'default' => 'price, change, hashrate, difficulty, height, adjustment, halving', 'transport' => 'postMessage' ),
		'ticker_custom'  => array( 'section' => 'mm_home_ticker', 'type' => 'text', 'label' => __( 'Extra messages (separate with |)', 'minemirth' ), 'default' => __( 'New & refurbished ASICs | Hosting-ready setups | Pre-sale advice from miners', 'minemirth' ), 'transport' => 'postMessage' ),
		'ticker_speed'   => array( 'section' => 'mm_home_ticker', 'type' => 'range', 'label' => __( 'Scroll duration', 'minemirth' ), 'default' => 45, 'min' => 15, 'max' => 120, 'step' => 5, 'unit' => 's', 'transport' => 'postMessage', 'css' => array( 'var' => '--mm-ticker-speed', 'unit' => 's' ) ),
	);

	// Categories.
	$s += mm_options_heading_fields( 'mm_home_categories', 'cats', __( 'Shop by algorithm', 'minemirth' ), __( 'Hardware for *every hash*', 'minemirth' ), __( 'From flagship SHA-256 machines to Scrypt miners and turnkey hosting — start with what you want to mine.', 'minemirth' ) );
	$s['cats_source'] = array( 'section' => 'mm_home_categories', 'type' => 'select', 'label' => __( 'Cards source', 'minemirth' ), 'default' => 'custom', 'choices' => array( 'custom' => __( 'Custom cards below', 'minemirth' ), 'auto' => __( 'WooCommerce product categories', 'minemirth' ) ), 'transport' => 'postMessage' );
	$s += mm_options_slots(
		'mm_home_categories',
		'cat',
		array(
			'tag'   => array( 'text', __( 'Tag', 'minemirth' ), __( 'Card', 'minemirth' ) ),
			'title' => array( 'text', __( 'Title', 'minemirth' ) ),
			'text'  => array( 'textarea', __( 'Text', 'minemirth' ) ),
			'url'   => array( 'url', __( 'Link', 'minemirth' ) ),
			'icon'  => array( 'icon', __( 'Icon', 'minemirth' ) ),
			'image' => array( 'media', __( 'Image (optional)', 'minemirth' ) ),
		),
		array(
			array( 'tag' => 'SHA-256', 'title' => __( 'Bitcoin miners', 'minemirth' ), 'text' => __( 'Flagship BTC hardware compared by hashrate, power and J/TH.', 'minemirth' ), 'url' => '{shop}?cryptocurrency=btc', 'icon' => 'bitcoin' ),
			array( 'tag' => 'Scrypt', 'title' => __( 'LTC / DOGE miners', 'minemirth' ), 'text' => __( 'Merge-mine Litecoin and Dogecoin with proven Scrypt machines.', 'minemirth' ), 'url' => '{shop}?cryptocurrency=ltc-doge', 'icon' => 'layers' ),
			array( 'tag' => __( 'Hosting', 'minemirth' ), 'title' => __( 'Miner hosting', 'minemirth' ), 'text' => __( 'Ship straight to a partner facility. Skip the heat, noise and wiring.', 'minemirth' ), 'url' => '/miner-hosting/', 'icon' => 'server' ),
			array( 'tag' => __( 'Tools', 'minemirth' ), 'title' => __( 'Free calculators', 'minemirth' ), 'text' => __( 'Estimate daily revenue, power cost and payback with live data.', 'minemirth' ), 'url' => '/free-calculators/', 'icon' => 'calculator' ),
		),
		4
	);

	// Products.
	$s += mm_options_heading_fields( 'mm_home_products', 'products', __( 'In the shop', 'minemirth' ), __( 'Featured *ASIC miners*', 'minemirth' ), __( 'Current listings with availability, efficiency and price on every card.', 'minemirth' ) );
	$s += array(
		'products_tabs'   => array( 'section' => 'mm_home_products', 'type' => 'toggle', 'label' => __( 'Show tabs (Featured · New · On sale · Best sellers)', 'minemirth' ), 'default' => true, 'transport' => 'postMessage' ),
		'products_source' => array( 'section' => 'mm_home_products', 'type' => 'select', 'label' => __( 'Products to show (when tabs are off)', 'minemirth' ), 'default' => 'featured', 'choices' => array( 'featured' => __( 'Featured', 'minemirth' ), 'recent' => __( 'Newest', 'minemirth' ), 'sale' => __( 'On sale', 'minemirth' ), 'best' => __( 'Best sellers', 'minemirth' ), 'rated' => __( 'Top rated', 'minemirth' ) ), 'transport' => 'postMessage' ),
		'products_count'  => array( 'section' => 'mm_home_products', 'type' => 'range', 'label' => __( 'Number of products', 'minemirth' ), 'default' => 8, 'min' => 3, 'max' => 16, 'step' => 1, 'transport' => 'postMessage' ),
		'products_cat'    => array( 'section' => 'mm_home_products', 'type' => 'text', 'label' => __( 'Limit to category slug (optional)', 'minemirth' ), 'default' => '', 'transport' => 'postMessage' ),
		'products_button' => array( 'section' => 'mm_home_products', 'type' => 'text', 'label' => __( 'View-all link text', 'minemirth' ), 'default' => __( 'View all miners', 'minemirth' ), 'transport' => 'postMessage' ),
	);

	// Features (bento).
	$s += mm_options_heading_fields( 'mm_home_features', 'features', __( 'Why Mine Mirth', 'minemirth' ), __( 'Built for miners, *not tourists*', 'minemirth' ), __( 'Everything you need to choose, buy and run ASIC hardware — in one place.', 'minemirth' ) );
	$s += mm_options_slots(
		'mm_home_features',
		'feature',
		array(
			'icon'  => array( 'icon', __( 'Icon', 'minemirth' ), __( 'Feature', 'minemirth' ) ),
			'title' => array( 'text', __( 'Title', 'minemirth' ) ),
			'text'  => array( 'textarea', __( 'Text', 'minemirth' ) ),
		),
		array(
			array( 'icon' => 'shield-check', 'title' => __( 'Buy with confidence', 'minemirth' ), 'text' => __( 'Clear availability, condition and warranty details on listings — no guesswork before checkout.', 'minemirth' ) ),
			array( 'icon' => 'bolt', 'title' => __( 'Efficiency first', 'minemirth' ), 'text' => __( 'Compare J/TH, hashrate and power draw side by side, then match the machine to your electricity rate.', 'minemirth' ) ),
			array( 'icon' => 'server', 'title' => __( 'Hosting-ready', 'minemirth' ), 'text' => __( 'Ship directly to a partner facility and skip the noise, heat and wiring at home.', 'minemirth' ) ),
			array( 'icon' => 'chart', 'title' => __( 'Live profitability', 'minemirth' ), 'text' => __( 'Model returns with real-time BTC price, difficulty and network hashrate.', 'minemirth' ) ),
			array( 'icon' => 'headset', 'title' => __( 'Talk to a human', 'minemirth' ), 'text' => __( 'Pre-sale advice from people who run miners, not scripts.', 'minemirth' ) ),
			array( 'icon' => 'truck', 'title' => __( 'Tracked delivery', 'minemirth' ), 'text' => __( 'Follow every order from dispatch to delivery in your account.', 'minemirth' ) ),
		),
		6
	);

	// Hosting.
	$s += mm_options_heading_fields( 'mm_home_hosting', 'hosting', __( 'Miner hosting', 'minemirth' ), __( 'Own the hardware. *Skip the heat.*', 'minemirth' ), __( 'Deploy to professional partner facilities with industrial power, airflow and on-site technicians. You keep ownership of every machine and see the terms up front.', 'minemirth' ) );
	$s += array(
		'hosting_points'    => array( 'section' => 'mm_home_hosting', 'type' => 'textarea', 'label' => __( 'Bullet points (one per line)', 'minemirth' ), 'default' => "Transparent all-in rates per kWh\nOn-site setup and maintenance\nRemote monitoring access\nTerms for one unit or a full container", 'transport' => 'postMessage' ),
		'hosting_btn1_text' => array( 'section' => 'mm_home_hosting', 'type' => 'text', 'label' => __( 'Primary button text', 'minemirth' ), 'default' => __( 'View hosting plans', 'minemirth' ), 'transport' => 'postMessage' ),
		'hosting_btn1_url'  => array( 'section' => 'mm_home_hosting', 'type' => 'url', 'label' => __( 'Primary button URL', 'minemirth' ), 'default' => '/miner-hosting/', 'transport' => 'postMessage' ),
		'hosting_btn2_text' => array( 'section' => 'mm_home_hosting', 'type' => 'text', 'label' => __( 'Secondary button text', 'minemirth' ), 'default' => __( 'Request a quote', 'minemirth' ), 'transport' => 'postMessage' ),
		'hosting_btn2_url'  => array( 'section' => 'mm_home_hosting', 'type' => 'url', 'label' => __( 'Secondary button URL', 'minemirth' ), 'default' => '/contact-us/', 'transport' => 'postMessage' ),
		'hosting_image'     => array( 'section' => 'mm_home_hosting', 'type' => 'media', 'label' => __( 'Image (optional — replaces the animated rack)', 'minemirth' ), 'default' => 0, 'transport' => 'postMessage' ),
		'hosting_show_plans'=> array( 'section' => 'mm_home_hosting', 'type' => 'toggle', 'label' => __( 'Show up to 3 published hosting plans', 'minemirth' ), 'default' => true, 'transport' => 'postMessage' ),
	);

	// Calculator.
	$s += mm_options_heading_fields( 'mm_home_calculator', 'calc', __( 'Profitability calculator', 'minemirth' ), __( 'Run the numbers *before you buy*', 'minemirth' ), __( 'Live BTC price and network difficulty, your electricity rate, your hardware. Results update as you type — no sign-up.', 'minemirth' ) );
	$s += array(
		'calc_btn_text' => array( 'section' => 'mm_home_calculator', 'type' => 'text', 'label' => __( 'Full calculator link text', 'minemirth' ), 'default' => __( 'Open the full calculator', 'minemirth' ), 'transport' => 'postMessage' ),
		'calc_btn_url'  => array( 'section' => 'mm_home_calculator', 'type' => 'url', 'label' => __( 'Full calculator URL', 'minemirth' ), 'default' => '/free-calculators/bitcoin-mining-calculator/', 'transport' => 'postMessage' ),
	);

	// Process.
	$s += mm_options_heading_fields( 'mm_home_process', 'process', __( 'How it works', 'minemirth' ), __( 'From checkout to *hashing*', 'minemirth' ), __( 'A clear path from choosing hardware to your first shares.', 'minemirth' ) );
	$s += mm_options_slots(
		'mm_home_process',
		'step',
		array(
			'title' => array( 'text', __( 'Title', 'minemirth' ), __( 'Step', 'minemirth' ) ),
			'text'  => array( 'textarea', __( 'Text', 'minemirth' ) ),
		),
		array(
			array( 'title' => __( 'Choose your hardware', 'minemirth' ), 'text' => __( 'Compare miners by algorithm, efficiency and price — or ask us for a recommendation.', 'minemirth' ) ),
			array( 'title' => __( 'Pick where it runs', 'minemirth' ), 'text' => __( 'Ship to your site, or select a hosting plan with published rates and terms.', 'minemirth' ) ),
			array( 'title' => __( 'Configure & ship', 'minemirth' ), 'text' => __( 'Your order is prepared and dispatched with tracking in your account.', 'minemirth' ) ),
			array( 'title' => __( 'Start mining', 'minemirth' ), 'text' => __( 'Point it at your pool, watch the hashrate climb, and track it all in one place.', 'minemirth' ) ),
		),
		4
	);

	// Stats.
	$s += mm_options_heading_fields( 'mm_home_stats', 'stats', __( 'Live network', 'minemirth' ), __( 'The Bitcoin network, *right now*', 'minemirth' ), __( 'Values refresh automatically from public data sources.', 'minemirth' ) );
	$s += mm_options_slots(
		'mm_home_stats',
		'stat',
		array(
			'value' => array( 'text', __( 'Value (or live:price, live:hashrate, live:difficulty, live:height, live:halving)', 'minemirth' ), __( 'Stat', 'minemirth' ) ),
			'label' => array( 'text', __( 'Label', 'minemirth' ) ),
		),
		array(
			array( 'value' => 'live:price', 'label' => __( 'BTC price', 'minemirth' ) ),
			array( 'value' => 'live:hashrate', 'label' => __( 'Network hashrate', 'minemirth' ) ),
			array( 'value' => 'live:difficulty', 'label' => __( 'Difficulty', 'minemirth' ) ),
			array( 'value' => 'live:halving', 'label' => __( 'Blocks to next halving', 'minemirth' ) ),
		),
		4
	);

	// Testimonials.
	$s += mm_options_heading_fields( 'mm_home_testimonials', 'testimonials', __( 'Reviews', 'minemirth' ), __( 'What *miners* say', 'minemirth' ), __( 'Verified reviews from customers who bought through our store.', 'minemirth' ) );
	$s['testimonials_source'] = array( 'section' => 'mm_home_testimonials', 'type' => 'select', 'label' => __( 'Source', 'minemirth' ), 'description' => __( 'Product reviews are pulled from WooCommerce automatically. The section hides itself when there is nothing to show.', 'minemirth' ), 'default' => 'reviews', 'choices' => array( 'reviews' => __( 'WooCommerce product reviews (4★+)', 'minemirth' ), 'custom' => __( 'Custom testimonials below', 'minemirth' ) ), 'transport' => 'postMessage' );
	$s += mm_options_slots(
		'mm_home_testimonials',
		'testimonial',
		array(
			'quote'  => array( 'textarea', __( 'Quote', 'minemirth' ), __( 'Testimonial', 'minemirth' ) ),
			'name'   => array( 'text', __( 'Name', 'minemirth' ) ),
			'role'   => array( 'text', __( 'Role / location', 'minemirth' ) ),
			'rating' => array( 'select', __( 'Rating', 'minemirth' ), null, array( '5' => '★★★★★', '4' => '★★★★☆', '0' => __( 'Hide stars', 'minemirth' ) ) ),
		),
		array(),
		6
	);

	// Brands.
	$s += mm_options_heading_fields( 'mm_home_brands', 'brands', __( 'Manufacturers', 'minemirth' ), __( 'Shop by *manufacturer*', 'minemirth' ), '' );
	$s['brands_list'] = array( 'section' => 'mm_home_brands', 'type' => 'textarea', 'label' => __( 'Manufacturers (one per line, optional "Name | URL")', 'minemirth' ), 'description' => __( 'Without a URL, each name links to a store search for that manufacturer.', 'minemirth' ), 'default' => "Bitmain\nMicroBT\nCanaan\nGoldshell\nIceRiver\nJasminer\nBitdeer", 'transport' => 'postMessage' );

	// Blog.
	$s += mm_options_heading_fields( 'mm_home_blog', 'homeblog', __( 'Mining academy', 'minemirth' ), __( 'Guides, data and *deep dives*', 'minemirth' ), __( 'Practical, sourced articles for miners at every scale.', 'minemirth' ) );
	$s += array(
		'homeblog_count' => array( 'section' => 'mm_home_blog', 'type' => 'range', 'label' => __( 'Number of posts', 'minemirth' ), 'default' => 3, 'min' => 2, 'max' => 9, 'step' => 1, 'transport' => 'postMessage' ),
		'homeblog_cat'   => array( 'section' => 'mm_home_blog', 'type' => 'text', 'label' => __( 'Limit to category slug (optional)', 'minemirth' ), 'default' => '', 'transport' => 'postMessage' ),
	);

	// FAQ.
	$s += mm_options_heading_fields( 'mm_home_faq', 'faq', __( 'Quick answers', 'minemirth' ), __( 'Questions miners *ask us*', 'minemirth' ), __( 'Still curious? Our team answers pre-sale questions every day.', 'minemirth' ) );
	$s += array(
		'faq_btn_text' => array( 'section' => 'mm_home_faq', 'type' => 'text', 'label' => __( 'Button text', 'minemirth' ), 'default' => __( 'Ask a question', 'minemirth' ), 'transport' => 'postMessage' ),
		'faq_btn_url'  => array( 'section' => 'mm_home_faq', 'type' => 'url', 'label' => __( 'Button URL', 'minemirth' ), 'default' => '/contact-us/', 'transport' => 'postMessage' ),
	);
	$s += mm_options_slots(
		'mm_home_faq',
		'faq',
		array(
			'q' => array( 'text', __( 'Question', 'minemirth' ), __( 'FAQ', 'minemirth' ) ),
			'a' => array( 'textarea', __( 'Answer', 'minemirth' ) ),
		),
		array(
			array( 'q' => __( 'Is mining profitable right now?', 'minemirth' ), 'a' => __( 'It depends on your electricity cost, hardware efficiency, BTC price and network difficulty. Use the live calculator to model your exact setup — results are estimates, not guarantees.', 'minemirth' ) ),
			array( 'q' => __( 'Can my miner ship straight to a hosting facility?', 'minemirth' ), 'a' => __( 'Yes. Choose a hosting plan or contact us before checkout and we coordinate delivery and setup with the partner facility once capacity is confirmed.', 'minemirth' ) ),
			array( 'q' => __( 'Do miners come with a warranty?', 'minemirth' ), 'a' => __( 'Coverage depends on the manufacturer and the unit’s condition. Each product page lists the warranty that applies to that listing.', 'minemirth' ) ),
			array( 'q' => __( 'Which payment methods can I use?', 'minemirth' ), 'a' => __( 'Every available payment method is shown at checkout. For large or multi-unit orders, contact us about invoicing.', 'minemirth' ) ),
			array( 'q' => __( 'How long does delivery take?', 'minemirth' ), 'a' => __( 'It depends on stock location and destination. In-stock items show their dispatch estimate on the product page, and you can track every order from your account.', 'minemirth' ) ),
			array( 'q' => __( 'What’s the difference between SHA-256 and Scrypt miners?', 'minemirth' ), 'a' => __( 'SHA-256 machines mine Bitcoin. Scrypt machines mine Litecoin and Dogecoin, usually merge-mined together. They are not interchangeable — choose the algorithm first, then the machine.', 'minemirth' ) ),
		),
		8
	);

	// Newsletter (home).
	$s += mm_options_heading_fields( 'mm_home_newsletter', 'news', __( 'The miner’s brief', 'minemirth' ), __( 'Network stats and hardware moves, *weekly*', 'minemirth' ), __( 'Difficulty changes, price moves on popular machines and new guides. No spam — unsubscribe anytime.', 'minemirth' ) );

	// CTA.
	$s += mm_options_heading_fields( 'mm_home_cta', 'cta', __( 'Let’s build your setup', 'minemirth' ), __( 'Ready to put *hashrate* to work?', 'minemirth' ), __( 'Talk to our team about hardware, hosting or a full deployment plan.', 'minemirth' ) );
	$s += array(
		'cta_btn1_text' => array( 'section' => 'mm_home_cta', 'type' => 'text', 'label' => __( 'Primary button text', 'minemirth' ), 'default' => __( 'Shop miners', 'minemirth' ), 'transport' => 'postMessage' ),
		'cta_btn1_url'  => array( 'section' => 'mm_home_cta', 'type' => 'url', 'label' => __( 'Primary button URL', 'minemirth' ), 'default' => '{shop}', 'transport' => 'postMessage' ),
		'cta_btn2_text' => array( 'section' => 'mm_home_cta', 'type' => 'text', 'label' => __( 'Secondary button text', 'minemirth' ), 'default' => __( 'Talk to sales', 'minemirth' ), 'transport' => 'postMessage' ),
		'cta_btn2_url'  => array( 'section' => 'mm_home_cta', 'type' => 'url', 'label' => __( 'Secondary button URL', 'minemirth' ), 'default' => '/contact-us/', 'transport' => 'postMessage' ),
	);

	/* ---------------------------------------------------------------------
	 * Blog
	 * ------------------------------------------------------------------- */
	$s += array(
		'blog_hero_title'    => array( 'section' => 'mm_blog_archive', 'type' => 'text', 'label' => __( 'Blog page title', 'minemirth' ), 'default' => __( 'Mining Academy', 'minemirth' ) ),
		'blog_hero_text'     => array( 'section' => 'mm_blog_archive', 'type' => 'textarea', 'label' => __( 'Blog page intro', 'minemirth' ), 'default' => __( 'Practical guides, hardware breakdowns and network analysis for miners at every scale.', 'minemirth' ) ),
		'blog_layout'        => array( 'section' => 'mm_blog_archive', 'type' => 'select', 'label' => __( 'Layout', 'minemirth' ), 'default' => 'magazine', 'choices' => array( 'magazine' => __( 'Magazine (large first post)', 'minemirth' ), 'grid' => __( 'Grid', 'minemirth' ), 'list' => __( 'List', 'minemirth' ) ) ),
		'blog_columns'       => array( 'section' => 'mm_blog_archive', 'type' => 'select', 'label' => __( 'Grid columns', 'minemirth' ), 'default' => '3', 'choices' => array( '2' => '2', '3' => '3' ) ),
		'blog_sidebar'       => array( 'section' => 'mm_blog_archive', 'type' => 'toggle', 'label' => __( 'Show blog sidebar widgets on archives', 'minemirth' ), 'default' => false ),
		'blog_cat_filter'    => array( 'section' => 'mm_blog_archive', 'type' => 'toggle', 'label' => __( 'Category filter pills', 'minemirth' ), 'default' => true ),
		'blog_meta_author'   => array( 'section' => 'mm_blog_archive', 'type' => 'toggle', 'label' => __( 'Show author', 'minemirth' ), 'default' => true ),
		'blog_meta_date'     => array( 'section' => 'mm_blog_archive', 'type' => 'toggle', 'label' => __( 'Show date', 'minemirth' ), 'default' => true ),
		'blog_meta_reading'  => array( 'section' => 'mm_blog_archive', 'type' => 'toggle', 'label' => __( 'Show reading time', 'minemirth' ), 'default' => true ),
		'blog_excerpt'       => array( 'section' => 'mm_blog_archive', 'type' => 'range', 'label' => __( 'Excerpt length (words, 0 hides)', 'minemirth' ), 'default' => 22, 'min' => 0, 'max' => 60, 'step' => 1 ),

		'single_progress'    => array( 'section' => 'mm_blog_single', 'type' => 'toggle', 'label' => __( 'Reading progress bar', 'minemirth' ), 'default' => true ),
		'single_toc'         => array( 'section' => 'mm_blog_single', 'type' => 'toggle', 'label' => __( 'Automatic table of contents', 'minemirth' ), 'default' => true ),
		'single_toc_min'     => array( 'section' => 'mm_blog_single', 'type' => 'range', 'label' => __( 'Minimum headings to show the contents', 'minemirth' ), 'default' => 3, 'min' => 2, 'max' => 8, 'step' => 1 ),
		'single_featured'    => array( 'section' => 'mm_blog_single', 'type' => 'select', 'label' => __( 'Featured image', 'minemirth' ), 'default' => 'wide', 'choices' => array( 'wide' => __( 'Wide', 'minemirth' ), 'contained' => __( 'Content width', 'minemirth' ), 'hidden' => __( 'Hidden', 'minemirth' ) ) ),
		'single_share'       => array( 'section' => 'mm_blog_single', 'type' => 'toggle', 'label' => __( 'Share buttons', 'minemirth' ), 'default' => true ),
		'single_author'      => array( 'section' => 'mm_blog_single', 'type' => 'toggle', 'label' => __( 'Author box', 'minemirth' ), 'default' => true ),
		'single_updated'     => array( 'section' => 'mm_blog_single', 'type' => 'toggle', 'label' => __( 'Show “Updated” date when edited', 'minemirth' ), 'default' => true ),
		'single_related'     => array( 'section' => 'mm_blog_single', 'type' => 'range', 'label' => __( 'Related articles (0 hides)', 'minemirth' ), 'default' => 3, 'min' => 0, 'max' => 6, 'step' => 1 ),
		'single_nav'         => array( 'section' => 'mm_blog_single', 'type' => 'toggle', 'label' => __( 'Previous / next article links', 'minemirth' ), 'default' => true ),
		'single_newsletter'  => array( 'section' => 'mm_blog_single', 'type' => 'toggle', 'label' => __( 'Newsletter box after article', 'minemirth' ), 'default' => true ),

		'promo_enable'       => array( 'section' => 'mm_blog_promos', 'type' => 'toggle', 'label' => __( 'Enable in-content promotions', 'minemirth' ), 'default' => true ),
		'promo_label'        => array( 'section' => 'mm_blog_promos', 'type' => 'text', 'label' => __( 'Promo label', 'minemirth' ), 'default' => __( 'Mine Mirth pick', 'minemirth' ) ),
		'promo_first'        => array( 'section' => 'mm_blog_promos', 'type' => 'range', 'label' => __( 'First promo after paragraph', 'minemirth' ), 'default' => 3, 'min' => 1, 'max' => 12, 'step' => 1 ),
		'promo_every'        => array( 'section' => 'mm_blog_promos', 'type' => 'range', 'label' => __( 'Then repeat every N paragraphs (0 = once)', 'minemirth' ), 'default' => 7, 'min' => 0, 'max' => 20, 'step' => 1 ),
		'promo_max'          => array( 'section' => 'mm_blog_promos', 'type' => 'range', 'label' => __( 'Maximum inline promos per article', 'minemirth' ), 'default' => 2, 'min' => 1, 'max' => 5, 'step' => 1 ),
		'promo_min_paras'    => array( 'section' => 'mm_blog_promos', 'type' => 'range', 'label' => __( 'Skip articles shorter than (paragraphs)', 'minemirth' ), 'default' => 5, 'min' => 1, 'max' => 20, 'step' => 1 ),
		'promo_continue'     => array( 'section' => 'mm_blog_promos', 'type' => 'toggle', 'label' => __( 'Show “Continue reading” cue under promos', 'minemirth' ), 'description' => __( 'Keeps readers moving through the article after a promo.', 'minemirth' ), 'default' => true ),
		'promo_sidebar'      => array( 'section' => 'mm_blog_promos', 'type' => 'toggle', 'label' => __( 'Sticky promo in the article sidebar', 'minemirth' ), 'default' => true ),
		'promo_slidein'      => array( 'section' => 'mm_blog_promos', 'type' => 'toggle', 'label' => __( 'Slide-in recommendation while reading', 'minemirth' ), 'default' => true ),
		'promo_slidein_at'   => array( 'section' => 'mm_blog_promos', 'type' => 'range', 'label' => __( 'Slide-in appears at % of article read', 'minemirth' ), 'default' => 55, 'min' => 20, 'max' => 90, 'step' => 5, 'unit' => '%' ),
		'promo_end'          => array( 'section' => 'mm_blog_promos', 'type' => 'toggle', 'label' => __( 'Call to action at the end of articles', 'minemirth' ), 'default' => true ),
		'promo_fallback'     => array( 'section' => 'mm_blog_promos', 'type' => 'select', 'label' => __( 'When no promotion matches an article', 'minemirth' ), 'default' => 'auto', 'choices' => array( 'auto' => __( 'Smart mix: products, calculator, hosting', 'minemirth' ), 'products' => __( 'Products only', 'minemirth' ), 'calculator' => __( 'Calculator only', 'minemirth' ), 'hosting' => __( 'Hosting only', 'minemirth' ), 'none' => __( 'Show nothing', 'minemirth' ) ) ),
		'promo_hosting_title'=> array( 'section' => 'mm_blog_promos', 'type' => 'text', 'label' => __( 'Hosting promo headline', 'minemirth' ), 'default' => __( 'No room for a miner at home?', 'minemirth' ) ),
		'promo_hosting_text' => array( 'section' => 'mm_blog_promos', 'type' => 'textarea', 'label' => __( 'Hosting promo text', 'minemirth' ), 'default' => __( 'Ship your ASIC straight to a partner facility with industrial power and cooling. Published rates, no surprises.', 'minemirth' ) ),
		'promo_calc_title'   => array( 'section' => 'mm_blog_promos', 'type' => 'text', 'label' => __( 'Calculator promo headline', 'minemirth' ), 'default' => __( 'What would this earn you today?', 'minemirth' ) ),
	);

	/* ---------------------------------------------------------------------
	 * Shop
	 * ------------------------------------------------------------------- */
	$s += array(
		'shop_hero'           => array( 'section' => 'mm_shop_archive', 'type' => 'toggle', 'label' => __( 'Show shop header', 'minemirth' ), 'default' => true ),
		'shop_hero_title'     => array( 'section' => 'mm_shop_archive', 'type' => 'text', 'label' => __( 'Shop title', 'minemirth' ), 'default' => __( 'ASIC Miners', 'minemirth' ) ),
		'shop_hero_text'      => array( 'section' => 'mm_shop_archive', 'type' => 'textarea', 'label' => __( 'Shop intro', 'minemirth' ), 'default' => __( 'Compare hashrate, efficiency and availability — then run the numbers with live network data before you buy.', 'minemirth' ) ),
		'shop_columns'        => array( 'section' => 'mm_shop_archive', 'type' => 'select', 'label' => __( 'Products per row (desktop)', 'minemirth' ), 'default' => '3', 'choices' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5' ) ),
		'shop_columns_mobile' => array( 'section' => 'mm_shop_archive', 'type' => 'select', 'label' => __( 'Products per row (mobile)', 'minemirth' ), 'default' => '1', 'choices' => array( '1' => '1', '2' => '2' ) ),
		'shop_per_page'       => array( 'section' => 'mm_shop_archive', 'type' => 'range', 'label' => __( 'Products per page', 'minemirth' ), 'default' => 12, 'min' => 4, 'max' => 48, 'step' => 1 ),
		'shop_sidebar'        => array( 'section' => 'mm_shop_archive', 'type' => 'select', 'label' => __( 'Filter sidebar', 'minemirth' ), 'description' => __( 'Add filter widgets under Appearance > Widgets > Shop Sidebar.', 'minemirth' ), 'default' => 'left', 'choices' => array( 'left' => __( 'Left', 'minemirth' ), 'right' => __( 'Right', 'minemirth' ), 'none' => __( 'None', 'minemirth' ) ) ),
		'shop_cat_pills'      => array( 'section' => 'mm_shop_archive', 'type' => 'toggle', 'label' => __( 'Category pills under the shop header', 'minemirth' ), 'default' => true ),

		'card_hover_image'    => array( 'section' => 'mm_shop_card', 'type' => 'toggle', 'label' => __( 'Show second gallery image on hover', 'minemirth' ), 'default' => true ),
		'card_specs'          => array( 'section' => 'mm_shop_card', 'type' => 'text', 'label' => __( 'Spec attributes to show', 'minemirth' ), 'description' => __( 'Comma separated product attribute names or slugs, e.g. hashrate, power, efficiency, algorithm.', 'minemirth' ), 'default' => 'hashrate, power, efficiency, algorithm' ),
		'card_rating'         => array( 'section' => 'mm_shop_card', 'type' => 'toggle', 'label' => __( 'Show star rating', 'minemirth' ), 'default' => true ),
		'card_stock'          => array( 'section' => 'mm_shop_card', 'type' => 'toggle', 'label' => __( 'Show stock status', 'minemirth' ), 'default' => true ),
		'card_quickview'      => array( 'section' => 'mm_shop_card', 'type' => 'toggle', 'label' => __( 'Quick view button', 'minemirth' ), 'default' => true ),
		'card_sale_percent'   => array( 'section' => 'mm_shop_card', 'type' => 'toggle', 'label' => __( 'Show sale badge as a percentage', 'minemirth' ), 'default' => true ),
		'card_new_days'       => array( 'section' => 'mm_shop_card', 'type' => 'range', 'label' => __( '“New” badge for products added in the last N days (0 = off)', 'minemirth' ), 'default' => 30, 'min' => 0, 'max' => 120, 'step' => 1 ),

		'product_sticky_atc'  => array( 'section' => 'mm_shop_single', 'type' => 'toggle', 'label' => __( 'Sticky add-to-cart bar on scroll', 'minemirth' ), 'default' => true ),
		'product_calc_cta'    => array( 'section' => 'mm_shop_single', 'type' => 'toggle', 'label' => __( '“Estimate profitability” box (prefilled from product specs)', 'minemirth' ), 'default' => true ),
		'product_hosting_cta' => array( 'section' => 'mm_shop_single', 'type' => 'toggle', 'label' => __( 'Hosting upsell box', 'minemirth' ), 'default' => true ),
		'product_hosting_text'=> array( 'section' => 'mm_shop_single', 'type' => 'text', 'label' => __( 'Hosting upsell text', 'minemirth' ), 'default' => __( 'Ship this miner directly to a hosting facility.', 'minemirth' ) ),
		'product_delivery'    => array( 'section' => 'mm_shop_single', 'type' => 'textarea', 'label' => __( 'Shipping & returns note (accordion under the button)', 'minemirth' ), 'default' => __( 'Dispatch estimates are shown for in-stock items. Every order includes tracking in your account. See our shipping and returns policies for full details.', 'minemirth' ) ),
		'product_trust_heading' => array( 'section' => 'mm_shop_single', 'type' => 'heading', 'label' => __( 'Trust badges', 'minemirth' ) ),
	);
	$s += mm_options_slots(
		'mm_shop_single',
		'ptrust',
		array(
			'icon'  => array( 'icon', __( 'Icon', 'minemirth' ), __( 'Badge', 'minemirth' ) ),
			'title' => array( 'text', __( 'Title', 'minemirth' ) ),
			'text'  => array( 'text', __( 'Text', 'minemirth' ) ),
		),
		array(
			array( 'icon' => 'lock', 'title' => __( 'Secure checkout', 'minemirth' ), 'text' => __( 'Encrypted payment processing', 'minemirth' ) ),
			array( 'icon' => 'truck', 'title' => __( 'Tracked shipping', 'minemirth' ), 'text' => __( 'Follow your order in your account', 'minemirth' ) ),
			array( 'icon' => 'headset', 'title' => __( 'Miner support', 'minemirth' ), 'text' => __( 'Setup and hosting questions answered', 'minemirth' ) ),
		),
		3
	);

	$s += array(
		'cart_drawer'         => array( 'section' => 'mm_cart', 'type' => 'toggle', 'label' => __( 'Slide-out mini cart', 'minemirth' ), 'default' => true ),
		'cart_drawer_open'    => array( 'section' => 'mm_cart', 'type' => 'toggle', 'label' => __( 'Open mini cart after adding a product', 'minemirth' ), 'default' => true ),
		'cart_free_shipping'  => array( 'section' => 'mm_cart', 'type' => 'number', 'label' => __( 'Free shipping progress bar threshold', 'minemirth' ), 'description' => __( 'Order subtotal for free shipping. 0 hides the bar.', 'minemirth' ), 'default' => 0, 'min' => 0, 'step' => 1 ),
		'cart_note'           => array( 'section' => 'mm_cart', 'type' => 'text', 'label' => __( 'Mini cart footer note', 'minemirth' ), 'default' => __( 'Taxes and shipping calculated at checkout.', 'minemirth' ) ),

		'checkout_focus'      => array( 'section' => 'mm_checkout', 'type' => 'toggle', 'label' => __( 'Distraction-free checkout header', 'minemirth' ), 'description' => __( 'Hides the main menu and footer links during checkout.', 'minemirth' ), 'default' => true ),
		'checkout_steps'      => array( 'section' => 'mm_checkout', 'type' => 'toggle', 'label' => __( 'Progress steps (Cart → Details → Payment → Done)', 'minemirth' ), 'default' => true ),
		'checkout_payment_left' => array( 'section' => 'mm_checkout', 'type' => 'toggle', 'label' => __( 'Payment section in the main column', 'minemirth' ), 'description' => __( 'Off places payment under the order summary.', 'minemirth' ), 'default' => true ),
		'checkout_secure_text'=> array( 'section' => 'mm_checkout', 'type' => 'text', 'label' => __( 'Secure checkout label', 'minemirth' ), 'default' => __( 'Secure checkout', 'minemirth' ) ),
		'checkout_help_text'  => array( 'section' => 'mm_checkout', 'type' => 'text', 'label' => __( 'Help text', 'minemirth' ), 'default' => __( 'Questions about your order? We’re here to help.', 'minemirth' ) ),
		'checkout_guarantee'  => array( 'section' => 'mm_checkout', 'type' => 'textarea', 'label' => __( 'Reassurance note under the order button', 'minemirth' ), 'default' => __( 'Your payment details are encrypted and processed securely by our payment provider. Mine Mirth never sees or stores full card numbers.', 'minemirth' ) ),
		'checkout_payment_icons' => array( 'section' => 'mm_checkout', 'type' => 'text', 'label' => __( 'Payment badges', 'minemirth' ), 'description' => __( 'Comma separated: visa, mastercard, amex, discover, paypal, applepay, googlepay, bitcoin, usdt, bank', 'minemirth' ), 'default' => 'visa, mastercard, amex, applepay, googlepay, bitcoin' ),
		'checkout_trust_heading' => array( 'section' => 'mm_checkout', 'type' => 'heading', 'label' => __( 'Trust points in the order summary', 'minemirth' ) ),
	);
	$s += mm_options_slots(
		'mm_checkout',
		'ctrust',
		array(
			'icon'  => array( 'icon', __( 'Icon', 'minemirth' ), __( 'Point', 'minemirth' ) ),
			'title' => array( 'text', __( 'Title', 'minemirth' ) ),
			'text'  => array( 'text', __( 'Text', 'minemirth' ) ),
		),
		array(
			array( 'icon' => 'lock', 'title' => __( 'Encrypted checkout', 'minemirth' ), 'text' => __( 'TLS-secured from start to finish', 'minemirth' ) ),
			array( 'icon' => 'package', 'title' => __( 'Tracked delivery', 'minemirth' ), 'text' => __( 'Tracking appears in your account', 'minemirth' ) ),
			array( 'icon' => 'headset', 'title' => __( 'Real support', 'minemirth' ), 'text' => __( 'Talk to people who run miners', 'minemirth' ) ),
		),
		3
	);

	$s += array(
		'thanks_title'   => array( 'section' => 'mm_thankyou', 'type' => 'text', 'label' => __( 'Heading', 'minemirth' ), 'default' => __( 'Order confirmed — thank you!', 'minemirth' ) ),
		'thanks_text'    => array( 'section' => 'mm_thankyou', 'type' => 'textarea', 'label' => __( 'Message', 'minemirth' ), 'default' => __( 'We’ve emailed your receipt. Here’s what happens next.', 'minemirth' ) ),
		'thanks_confetti'=> array( 'section' => 'mm_thankyou', 'type' => 'toggle', 'label' => __( 'Celebration animation', 'minemirth' ), 'default' => true ),
	);
	$s += mm_options_slots(
		'mm_thankyou',
		'next',
		array(
			'title' => array( 'text', __( 'Title', 'minemirth' ), __( 'Next step', 'minemirth' ) ),
			'text'  => array( 'textarea', __( 'Text', 'minemirth' ) ),
		),
		array(
			array( 'title' => __( 'Order review', 'minemirth' ), 'text' => __( 'We confirm payment and stock for every item in your order.', 'minemirth' ) ),
			array( 'title' => __( 'Preparation & dispatch', 'minemirth' ), 'text' => __( 'Your hardware is packed and shipped. Tracking appears in your account.', 'minemirth' ) ),
			array( 'title' => __( 'Start mining', 'minemirth' ), 'text' => __( 'Plug in at home, or let a hosting partner handle setup for you.', 'minemirth' ) ),
		),
		3
	);

	$s += array(
		'account_panel_title' => array( 'section' => 'mm_account', 'type' => 'text', 'label' => __( 'Login page: panel title', 'minemirth' ), 'default' => __( 'Your mining HQ', 'minemirth' ) ),
		'account_panel_text'  => array( 'section' => 'mm_account', 'type' => 'textarea', 'label' => __( 'Login page: panel text', 'minemirth' ), 'default' => __( 'Track orders, manage addresses and check out faster with a Mine Mirth account.', 'minemirth' ) ),
		'account_panel_points'=> array( 'section' => 'mm_account', 'type' => 'textarea', 'label' => __( 'Login page: benefits (one per line)', 'minemirth' ), 'default' => "Order tracking and invoices\nSaved addresses for one-click checkout\nHosting and support in one place" ),
		'account_panel_image' => array( 'section' => 'mm_account', 'type' => 'media', 'label' => __( 'Login page: background image (optional)', 'minemirth' ), 'default' => 0 ),
		'account_welcome'     => array( 'section' => 'mm_account', 'type' => 'text', 'label' => __( 'Dashboard greeting', 'minemirth' ), 'description' => __( '{name} is replaced with the customer’s first name.', 'minemirth' ), 'default' => __( 'Welcome back, {name}', 'minemirth' ) ),
		'account_stats'       => array( 'section' => 'mm_account', 'type' => 'toggle', 'label' => __( 'Dashboard stat cards', 'minemirth' ), 'default' => true ),
		'account_recent'      => array( 'section' => 'mm_account', 'type' => 'toggle', 'label' => __( 'Recent orders on the dashboard', 'minemirth' ), 'default' => true ),
		'account_support_title' => array( 'section' => 'mm_account', 'type' => 'text', 'label' => __( 'Support box title', 'minemirth' ), 'default' => __( 'Need help with an order?', 'minemirth' ) ),
		'account_support_text'  => array( 'section' => 'mm_account', 'type' => 'textarea', 'label' => __( 'Support box text', 'minemirth' ), 'default' => __( 'Our team can help with shipping, setup, warranty and hosting questions.', 'minemirth' ) ),
		'account_support_url'   => array( 'section' => 'mm_account', 'type' => 'url', 'label' => __( 'Support box link', 'minemirth' ), 'default' => '/contact-us/' ),
	);

	/* ---------------------------------------------------------------------
	 * Footer
	 * ------------------------------------------------------------------- */
	$s += array(
		'footer_cta'           => array( 'section' => 'mm_footer_cta', 'type' => 'toggle', 'label' => __( 'Show footer call to action (inner pages)', 'minemirth' ), 'default' => true ),
		'footer_cta_title'     => array( 'section' => 'mm_footer_cta', 'type' => 'text', 'label' => __( 'Title', 'minemirth' ), 'default' => __( 'Compare the hardware. Model the power. *Then decide.*', 'minemirth' ) ),
		'footer_cta_text'      => array( 'section' => 'mm_footer_cta', 'type' => 'textarea', 'label' => __( 'Text', 'minemirth' ), 'default' => __( 'Live availability, public network data and published hosting terms — so every decision is an informed one.', 'minemirth' ) ),
		'footer_cta_btn1_text' => array( 'section' => 'mm_footer_cta', 'type' => 'text', 'label' => __( 'Primary button text', 'minemirth' ), 'default' => __( 'Shop ASIC miners', 'minemirth' ) ),
		'footer_cta_btn1_url'  => array( 'section' => 'mm_footer_cta', 'type' => 'url', 'label' => __( 'Primary button URL', 'minemirth' ), 'default' => '{shop}' ),
		'footer_cta_btn2_text' => array( 'section' => 'mm_footer_cta', 'type' => 'text', 'label' => __( 'Secondary button text', 'minemirth' ), 'default' => __( 'Contact us', 'minemirth' ) ),
		'footer_cta_btn2_url'  => array( 'section' => 'mm_footer_cta', 'type' => 'url', 'label' => __( 'Secondary button URL', 'minemirth' ), 'default' => '/contact-us/' ),

		'footer_style'         => array( 'section' => 'mm_footer_main', 'type' => 'select', 'label' => __( 'Footer color', 'minemirth' ), 'default' => 'dark', 'choices' => array( 'dark' => __( 'Always dark', 'minemirth' ), 'auto' => __( 'Follow light/dark mode', 'minemirth' ) ) ),
		'footer_about'         => array( 'section' => 'mm_footer_main', 'type' => 'textarea', 'label' => __( 'About text', 'minemirth' ), 'default' => __( 'Mine Mirth supplies ASIC mining hardware, partner-facility hosting and practical tools for miners — from a first rig to full deployments.', 'minemirth' ) ),
		'footer_col1_title'    => array( 'section' => 'mm_footer_main', 'type' => 'text', 'label' => __( 'Column 1 title (menu: Footer column 1)', 'minemirth' ), 'default' => __( 'Shop', 'minemirth' ) ),
		'footer_col2_title'    => array( 'section' => 'mm_footer_main', 'type' => 'text', 'label' => __( 'Column 2 title (menu: Footer column 2)', 'minemirth' ), 'default' => __( 'Research', 'minemirth' ) ),
		'footer_col3_title'    => array( 'section' => 'mm_footer_main', 'type' => 'text', 'label' => __( 'Column 3 title (menu: Footer column 3)', 'minemirth' ), 'default' => __( 'Customer', 'minemirth' ) ),
		'footer_newsletter'    => array( 'section' => 'mm_footer_main', 'type' => 'toggle', 'label' => __( 'Newsletter signup column', 'minemirth' ), 'default' => true ),
		'footer_newsletter_title' => array( 'section' => 'mm_footer_main', 'type' => 'text', 'label' => __( 'Newsletter title', 'minemirth' ), 'default' => __( 'The miner’s brief', 'minemirth' ) ),
		'footer_newsletter_text'  => array( 'section' => 'mm_footer_main', 'type' => 'text', 'label' => __( 'Newsletter text', 'minemirth' ), 'default' => __( 'Weekly network stats and hardware moves.', 'minemirth' ) ),
		'footer_live'          => array( 'section' => 'mm_footer_main', 'type' => 'toggle', 'label' => __( 'Live network status strip', 'minemirth' ), 'default' => true ),

		'contact_email'        => array( 'section' => 'mm_contact', 'type' => 'text', 'label' => __( 'Email', 'minemirth' ), 'default' => '' ),
		'contact_phone'        => array( 'section' => 'mm_contact', 'type' => 'text', 'label' => __( 'Phone', 'minemirth' ), 'default' => '' ),
		'contact_address'      => array( 'section' => 'mm_contact', 'type' => 'textarea', 'label' => __( 'Address', 'minemirth' ), 'default' => '' ),
		'contact_hours'        => array( 'section' => 'mm_contact', 'type' => 'text', 'label' => __( 'Support hours', 'minemirth' ), 'default' => '' ),
		'contact_url'          => array( 'section' => 'mm_contact', 'type' => 'url', 'label' => __( 'Contact page URL', 'minemirth' ), 'default' => '/contact-us/' ),
	);
	foreach ( mm_social_networks() as $network => $label ) {
		$s[ 'social_' . $network ] = array( 'section' => 'mm_social', 'type' => 'url', 'label' => $label, 'default' => '' );
	}
	$s += array(
		'footer_copyright'     => array( 'section' => 'mm_footer_bottom', 'type' => 'text', 'label' => __( 'Copyright', 'minemirth' ), 'description' => __( 'Tokens: {year}, {site}', 'minemirth' ), 'default' => '© {year} {site}. All rights reserved.' ),
		'footer_disclaimer'    => array( 'section' => 'mm_footer_bottom', 'type' => 'textarea', 'label' => __( 'Disclaimer', 'minemirth' ), 'default' => __( 'Mining revenue, asset prices, network difficulty and hosting availability change constantly. Calculator outputs and market figures are estimates, not guarantees of future results.', 'minemirth' ) ),
		'footer_payments'      => array( 'section' => 'mm_footer_bottom', 'type' => 'toggle', 'label' => __( 'Show payment badges (from Checkout settings)', 'minemirth' ), 'default' => true ),
	);

	/* ---------------------------------------------------------------------
	 * Misc
	 * ------------------------------------------------------------------- */
	$s += array(
		'page_hero_style'   => array( 'section' => 'mm_pages', 'type' => 'select', 'label' => __( 'Page header style', 'minemirth' ), 'default' => 'glow', 'choices' => array( 'glow' => __( 'Glow + grid', 'minemirth' ), 'plain' => __( 'Plain', 'minemirth' ), 'image' => __( 'Featured image background', 'minemirth' ) ) ),
		'page_hero_align'   => array( 'section' => 'mm_pages', 'type' => 'select', 'label' => __( 'Page header alignment', 'minemirth' ), 'default' => 'left', 'choices' => array( 'left' => __( 'Left', 'minemirth' ), 'center' => __( 'Center', 'minemirth' ) ) ),
		'notfound_title'    => array( 'section' => 'mm_pages', 'type' => 'text', 'label' => __( '404 title', 'minemirth' ), 'default' => __( 'This block was never mined.', 'minemirth' ) ),
		'notfound_text'     => array( 'section' => 'mm_pages', 'type' => 'textarea', 'label' => __( '404 text', 'minemirth' ), 'default' => __( 'The page you’re looking for doesn’t exist or has moved. Try a search, or jump back to the shop.', 'minemirth' ) ),

		'hostpage_title'    => array( 'section' => 'mm_hosting_page', 'type' => 'text', 'label' => __( 'Hero title', 'minemirth' ), 'default' => __( 'ASIC miner hosting with *terms you can read*', 'minemirth' ) ),
		'hostpage_text'     => array( 'section' => 'mm_hosting_page', 'type' => 'textarea', 'label' => __( 'Hero text', 'minemirth' ), 'default' => __( 'Compare published hosting options by region, rate, cooling and availability. Ship your miners straight from checkout to the facility.', 'minemirth' ) ),
		'hostpage_note'     => array( 'section' => 'mm_hosting_page', 'type' => 'textarea', 'label' => __( 'Plans footnote', 'minemirth' ), 'default' => __( 'Rates and capacity are confirmed in writing before deployment. Hosting changes operating conditions; it does not remove market, network or hardware risk.', 'minemirth' ) ),

		'calc_presets'      => array( 'section' => 'mm_calculator', 'type' => 'textarea', 'label' => __( 'Miner presets (Name | TH/s | Watts)', 'minemirth' ), 'description' => __( 'One per line. Products with “hashrate” and “power” attributes are added automatically.', 'minemirth' ), 'default' => "Antminer S21 XP | 270 | 3645\nAntminer S21 Pro | 234 | 3510\nAntminer S21 | 200 | 3500\nWhatsminer M60S | 186 | 3441\nAvalon A1466 | 150 | 3230\nAntminer S19k Pro | 120 | 2760" ),
		'calc_kwh'          => array( 'section' => 'mm_calculator', 'type' => 'number', 'label' => __( 'Default electricity price ($/kWh)', 'minemirth' ), 'default' => 0.07, 'min' => 0, 'step' => 0.001 ),
		'calc_pool_fee'     => array( 'section' => 'mm_calculator', 'type' => 'number', 'label' => __( 'Default pool fee (%)', 'minemirth' ), 'default' => 2, 'min' => 0, 'max' => 10, 'step' => 0.1 ),
		'calc_cta_text'     => array( 'section' => 'mm_calculator', 'type' => 'text', 'label' => __( 'Result call-to-action text', 'minemirth' ), 'default' => __( 'Shop miners like this', 'minemirth' ) ),
		'calc_cta_url'      => array( 'section' => 'mm_calculator', 'type' => 'url', 'label' => __( 'Result call-to-action URL', 'minemirth' ), 'default' => '{shop}' ),
		'calc_disclaimer'   => array( 'section' => 'mm_calculator', 'type' => 'textarea', 'label' => __( 'Calculator disclaimer', 'minemirth' ), 'default' => __( 'Estimates assume constant price, difficulty and 100% uptime at nameplate specs. Real results vary with network conditions, hardware performance, curtailment and fees.', 'minemirth' ) ),
		'tools_hub_slug'    => array( 'section' => 'mm_calculator', 'type' => 'text', 'label' => __( 'Tools hub page slug', 'minemirth' ), 'description' => __( 'The page that lists every tool. Tool pages live under it.', 'minemirth' ), 'default' => 'free-calculators' ),
		'tools_ltc_hash'    => array( 'section' => 'mm_calculator', 'type' => 'number', 'label' => __( 'Litecoin network hashrate override (PH/s)', 'minemirth' ), 'description' => __( '0 = use live data.', 'minemirth' ), 'default' => 0, 'min' => 0, 'step' => 0.01 ),
		'tools_doge_hash'   => array( 'section' => 'mm_calculator', 'type' => 'number', 'label' => __( 'Dogecoin network hashrate override (PH/s)', 'minemirth' ), 'description' => __( '0 = estimate from Litecoin (merged mining).', 'minemirth' ), 'default' => 0, 'min' => 0, 'step' => 0.01 ),
		'tools_doge_ratio'  => array( 'section' => 'mm_calculator', 'type' => 'number', 'label' => __( 'Dogecoin / Litecoin hashrate ratio', 'minemirth' ), 'default' => 1, 'min' => 0.1, 'max' => 3, 'step' => 0.01 ),
		'tools_kas_hash'    => array( 'section' => 'mm_calculator', 'type' => 'number', 'label' => __( 'Kaspa network hashrate override (PH/s)', 'minemirth' ), 'description' => __( '0 = use live data.', 'minemirth' ), 'default' => 0, 'min' => 0, 'step' => 0.01 ),

		'market_enable'     => array( 'section' => 'mm_market', 'type' => 'toggle', 'label' => __( 'Enable live market data', 'minemirth' ), 'description' => __( 'BTC price from CoinGecko; network data from mempool.space. Cached on your server.', 'minemirth' ), 'default' => true ),
		'market_currency'   => array( 'section' => 'mm_market', 'type' => 'select', 'label' => __( 'Price currency', 'minemirth' ), 'default' => 'usd', 'choices' => array( 'usd' => 'USD $', 'eur' => 'EUR €', 'gbp' => 'GBP £', 'cad' => 'CAD $', 'aud' => 'AUD $' ) ),
		'market_cache'      => array( 'section' => 'mm_market', 'type' => 'range', 'label' => __( 'Refresh interval', 'minemirth' ), 'default' => 120, 'min' => 60, 'max' => 900, 'step' => 30, 'unit' => 's' ),
		'market_sources'    => array( 'section' => 'mm_market', 'type' => 'toggle', 'label' => __( 'Show source attribution', 'minemirth' ), 'default' => true ),

		'newsletter_provider' => array( 'section' => 'mm_newsletter', 'type' => 'select', 'label' => __( 'Where signups go', 'minemirth' ), 'default' => 'local', 'choices' => array( 'local' => __( 'Save in WordPress (Promotions › Subscribers)', 'minemirth' ), 'action' => __( 'Post to an external form URL (Mailchimp, ConvertKit…)', 'minemirth' ), 'shortcode' => __( 'Use a form plugin shortcode', 'minemirth' ) ) ),
		'newsletter_action'   => array( 'section' => 'mm_newsletter', 'type' => 'url', 'label' => __( 'External form action URL', 'minemirth' ), 'default' => '' ),
		'newsletter_field'    => array( 'section' => 'mm_newsletter', 'type' => 'text', 'label' => __( 'External email field name', 'minemirth' ), 'default' => 'EMAIL' ),
		'newsletter_shortcode'=> array( 'section' => 'mm_newsletter', 'type' => 'text', 'label' => __( 'Form shortcode', 'minemirth' ), 'default' => '' ),
		'newsletter_button'   => array( 'section' => 'mm_newsletter', 'type' => 'text', 'label' => __( 'Button text', 'minemirth' ), 'default' => __( 'Subscribe', 'minemirth' ) ),
		'newsletter_consent'  => array( 'section' => 'mm_newsletter', 'type' => 'text', 'label' => __( 'Consent note', 'minemirth' ), 'default' => __( 'By subscribing you agree to receive emails from Mine Mirth. Unsubscribe anytime.', 'minemirth' ) ),
		'newsletter_success'  => array( 'section' => 'mm_newsletter', 'type' => 'text', 'label' => __( 'Success message', 'minemirth' ), 'default' => __( 'You’re in. Watch your inbox for the next brief.', 'minemirth' ) ),

		'perf_emoji'        => array( 'section' => 'mm_performance', 'type' => 'toggle', 'label' => __( 'Remove WordPress emoji script', 'minemirth' ), 'default' => true ),
		'perf_preload_font' => array( 'section' => 'mm_performance', 'type' => 'toggle', 'label' => __( 'Preconnect to Google Fonts', 'minemirth' ), 'default' => true ),
		'seo_schema'        => array( 'section' => 'mm_performance', 'type' => 'toggle', 'label' => __( 'Output structured data (skipped automatically when Yoast or Rank Math is active)', 'minemirth' ), 'default' => true ),
		'seo_llms'          => array( 'section' => 'mm_performance', 'type' => 'toggle', 'label' => __( 'Serve /llms.txt site summary for AI assistants', 'minemirth' ), 'default' => true ),
	);

	/*
	 * Conditional visibility in the Customizer: key (or prefix*) => [controller, allowed values].
	 */
	$deps = array(
		'hero_image'           => array( 'hero_visual', array( 'image' ) ),
		'hero_video'           => array( 'hero_visual', array( 'video' ) ),
		'products_source'      => array( 'products_tabs', array( false ) ),
		'header_cta_text'      => array( 'header_cta', array( true ) ),
		'header_cta_url'       => array( 'header_cta', array( true ) ),
		'announce_text'        => array( 'announce_enable', array( true ) ),
		'announce_link_text'   => array( 'announce_enable', array( true ) ),
		'announce_link'        => array( 'announce_enable', array( true ) ),
		'announce_style'       => array( 'announce_enable', array( true ) ),
		'announce_live'        => array( 'announce_enable', array( true ) ),
		'announce_dismissible' => array( 'announce_enable', array( true ) ),
		'mode_transition'      => array( 'mode_toggle', array( true ) ),
		'newsletter_action'    => array( 'newsletter_provider', array( 'action' ) ),
		'newsletter_field'     => array( 'newsletter_provider', array( 'action' ) ),
		'newsletter_shortcode' => array( 'newsletter_provider', array( 'shortcode' ) ),
		'promo_slidein_at'     => array( 'promo_slidein', array( true ) ),
		'single_toc_min'       => array( 'single_toc', array( true ) ),
		'cart_drawer_open'     => array( 'cart_drawer', array( true ) ),
		'product_hosting_text' => array( 'product_hosting_cta', array( true ) ),
		'cat_*'                => array( 'cats_source', array( 'custom' ) ),
		'testimonial_*'        => array( 'testimonials_source', array( 'custom' ) ),
		'footer_cta_*'         => array( 'footer_cta', array( true ) ),
		'footer_newsletter_*'  => array( 'footer_newsletter', array( true ) ),
	);
	foreach ( $deps as $pattern => $rule ) {
		if ( '*' === substr( $pattern, -1 ) ) {
			$prefix = substr( $pattern, 0, -1 );
			foreach ( array_keys( $s ) as $key ) {
				if ( 0 === strpos( $key, $prefix ) && $key !== $rule[0] ) {
					$s[ $key ]['show_if'] = $rule;
				}
			}
		} elseif ( isset( $s[ $pattern ] ) ) {
			$s[ $pattern ]['show_if'] = $rule;
		}
	}

	$schema = apply_filters( 'mm_options_schema', $s );
	return $schema;
}

/**
 * Social networks supported in the footer and mobile menu.
 */
function mm_social_networks(): array {
	return array(
		'x'         => 'X (Twitter)',
		'youtube'   => 'YouTube',
		'telegram'  => 'Telegram',
		'discord'   => 'Discord',
		'linkedin'  => 'LinkedIn',
		'instagram' => 'Instagram',
		'facebook'  => 'Facebook',
		'tiktok'    => 'TikTok',
		'reddit'    => 'Reddit',
		'github'    => 'GitHub',
	);
}

/**
 * Style presets: name => [label, swatches, settings].
 */
function mm_style_presets(): array {
	return array(
		'azure'   => array(
			'label'    => __( 'Azure (default)', 'minemirth' ),
			'settings' => array(
				'color_primary' => '#35a8f6', 'color_accent' => '#2563eb', 'color_accent_2' => '#5ee1ff', 'color_on_primary' => '#03111f',
				'light_bg' => '#f4f8fc', 'light_bg_alt' => '#eaf2fa', 'light_surface' => '#ffffff', 'light_text' => '#0a1626', 'light_muted' => '#506279', 'light_border' => '#d9e4ef',
				'dark_bg' => '#050b14', 'dark_bg_alt' => '#08121f', 'dark_surface' => '#0b1624', 'dark_text' => '#e7f0fa', 'dark_muted' => '#8ea3bb', 'dark_border' => '#172a3f',
			),
		),
		'glacier' => array(
			'label'    => __( 'Glacier', 'minemirth' ),
			'settings' => array(
				'color_primary' => '#35a8f6', 'color_accent' => '#38bdf8', 'color_accent_2' => '#a5f3fc', 'color_on_primary' => '#03111f',
				'light_bg' => '#f7fbff', 'light_bg_alt' => '#eef6fd', 'light_surface' => '#ffffff', 'light_text' => '#0b1b2b', 'light_muted' => '#55697f', 'light_border' => '#dde9f4',
				'dark_bg' => '#071321', 'dark_bg_alt' => '#0a1829', 'dark_surface' => '#0e1f33', 'dark_text' => '#eaf5ff', 'dark_muted' => '#93abc4', 'dark_border' => '#1b3350',
			),
		),
		'abyss'   => array(
			'label'    => __( 'Abyss', 'minemirth' ),
			'settings' => array(
				'color_primary' => '#35a8f6', 'color_accent' => '#1d4ed8', 'color_accent_2' => '#60a5fa', 'color_on_primary' => '#020b16',
				'light_bg' => '#f2f6fb', 'light_bg_alt' => '#e6eef8', 'light_surface' => '#ffffff', 'light_text' => '#081220', 'light_muted' => '#4b5d74', 'light_border' => '#d3dfec',
				'dark_bg' => '#020610', 'dark_bg_alt' => '#050c18', 'dark_surface' => '#08111f', 'dark_text' => '#e3ecf7', 'dark_muted' => '#8396ae', 'dark_border' => '#13223a',
			),
		),
		'electric'=> array(
			'label'    => __( 'Electric', 'minemirth' ),
			'settings' => array(
				'color_primary' => '#35a8f6', 'color_accent' => '#6366f1', 'color_accent_2' => '#c084fc', 'color_on_primary' => '#03111f',
				'light_bg' => '#f6f7fe', 'light_bg_alt' => '#eceffd', 'light_surface' => '#ffffff', 'light_text' => '#0d1230', 'light_muted' => '#545b7d', 'light_border' => '#dfe2f5',
				'dark_bg' => '#06061a', 'dark_bg_alt' => '#0a0b24', 'dark_surface' => '#0f1130', 'dark_text' => '#ecedfb', 'dark_muted' => '#9599c2', 'dark_border' => '#1f2250',
			),
		),
		'graphite'=> array(
			'label'    => __( 'Graphite', 'minemirth' ),
			'settings' => array(
				'color_primary' => '#35a8f6', 'color_accent' => '#35a8f6', 'color_accent_2' => '#7dd3fc', 'color_on_primary' => '#03111f',
				'light_bg' => '#f7f7f8', 'light_bg_alt' => '#eeeef0', 'light_surface' => '#ffffff', 'light_text' => '#111214', 'light_muted' => '#5d6168', 'light_border' => '#e2e3e6',
				'dark_bg' => '#09090b', 'dark_bg_alt' => '#0e0e11', 'dark_surface' => '#141418', 'dark_text' => '#ededef', 'dark_muted' => '#9a9ca3', 'dark_border' => '#24242a',
			),
		),
	);
}

/**
 * Default value for a setting.
 */
function mm_default( string $key ) {
	$schema = mm_options_schema();
	return $schema[ $key ]['default'] ?? '';
}

/**
 * Read an option (theme mod) with its schema default.
 */
function mm_opt( string $key ) {
	$schema = mm_options_schema();
	$def    = $schema[ $key ]['default'] ?? '';
	$value  = get_theme_mod( 'mm_' . $key, $def );

	if ( isset( $schema[ $key ]['type'] ) && 'toggle' === $schema[ $key ]['type'] ) {
		return (bool) $value;
	}
	return $value;
}
