<?php
/**
 * Ready-made page sections as shortcodes.
 *
 * Every section is one shortcode, so a page is a stack of blocks you can edit
 * in place. Styling follows the Mine Mirth theme (its colours, fonts and dark
 * mode) and falls back to neutral styles on other themes.
 *
 * @package MineMirthCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inner content of an enclosing shortcode: nested shortcodes, then paragraphs.
 */
function mmc_inner( $content ): string {
	$content = trim( (string) $content );
	if ( '' === $content ) {
		return '';
	}
	$content = shortcode_unautop( $content );
	if ( has_blocks( $content ) ) {
		$content = do_blocks( $content );
	} elseif ( ! preg_match( '/<(p|div|ul|ol|h[1-6]|table|figure|section)\b/i', $content ) ) {
		$content = wpautop( $content );
	}
	return do_shortcode( $content );
}

/**
 * Section wrapper.
 *
 * @param string $class   Modifier class.
 * @param string $inner   Inner HTML.
 * @param array  $atts    Shortcode attributes (uses id, tone, eyebrow, title, intro).
 */
function mmc_section( string $class, string $inner, array $atts = array() ): string {
	$tone  = in_array( $atts['tone'] ?? '', array( 'alt', 'dark', 'plain' ), true ) ? $atts['tone'] : 'plain';
	$id    = ! empty( $atts['id'] ) ? ' id="' . esc_attr( sanitize_title( $atts['id'] ) ) . '"' : '';
	$head  = '';
	if ( ! empty( $atts['eyebrow'] ) || ! empty( $atts['title'] ) || ! empty( $atts['intro'] ) ) {
		$head .= '<header class="mmc-head' . ( ! empty( $atts['align'] ) && 'left' === $atts['align'] ? ' is-left' : '' ) . '">';
		if ( ! empty( $atts['eyebrow'] ) ) {
			$head .= '<span class="mmc-eyebrow">' . esc_html( $atts['eyebrow'] ) . '</span>';
		}
		if ( ! empty( $atts['title'] ) ) {
			$head .= '<h2 class="mmc-title">' . esc_html( $atts['title'] ) . '</h2>';
		}
		if ( ! empty( $atts['intro'] ) ) {
			$head .= '<p class="mmc-intro">' . esc_html( $atts['intro'] ) . '</p>';
		}
		$head .= '</header>';
	}
	$scheme = 'dark' === $tone ? ' mm-scheme-dark' : '';
	return '<section class="mmc-sec mmc-sec--' . esc_attr( $class ) . ' mmc-tone-' . esc_attr( $tone ) . $scheme . '"' . $id . '><div class="mmc-wrap">' . $head . $inner . '</div></section>';
}

function mmc_btn( string $text, string $url, string $style = 'primary' ): string {
	if ( '' === $text || '' === $url ) {
		return '';
	}
	return '<a class="mmc-btn mmc-btn--' . esc_attr( $style ) . '" href="' . esc_url( mmc_url( $url ) ) . '">' . esc_html( $text ) . '</a>';
}

/* [mmc_hero eyebrow="" title="" text="" button="" url="" button2="" url2="" image="" align="left|center"] */
add_shortcode(
	'mmc_hero',
	static function ( $atts ) {
		$a     = shortcode_atts( array( 'eyebrow' => '', 'title' => get_the_title(), 'text' => '', 'button' => '', 'url' => '', 'button2' => '', 'url2' => '', 'image' => '', 'align' => 'left', 'id' => '' ), $atts );
		$img   = '';
		if ( $a['image'] ) {
			$img = is_numeric( $a['image'] ) ? wp_get_attachment_image( (int) $a['image'], 'large', false, array( 'class' => 'mmc-hero__img' ) ) : '<img class="mmc-hero__img" src="' . esc_url( $a['image'] ) . '" alt="">';
		}
		$html  = '<div class="mmc-hero mmc-hero--' . esc_attr( 'center' === $a['align'] ? 'center' : 'left' ) . ( $img ? ' has-image' : '' ) . '"><div class="mmc-hero__copy">';
		$html .= $a['eyebrow'] ? '<span class="mmc-eyebrow">' . esc_html( $a['eyebrow'] ) . '</span>' : '';
		$html .= '<h1 class="mmc-hero__title">' . esc_html( $a['title'] ) . '</h1>';
		$html .= $a['text'] ? '<p class="mmc-hero__text">' . esc_html( $a['text'] ) . '</p>' : '';
		$btns  = mmc_btn( $a['button'], $a['url'] ) . mmc_btn( $a['button2'], $a['url2'], 'ghost' );
		$html .= $btns ? '<div class="mmc-actions">' . $btns . '</div>' : '';
		$html .= '</div>' . ( $img ? '<div class="mmc-hero__media">' . $img . '</div>' : '' ) . '</div>';
		return mmc_section( 'hero', $html, array( 'tone' => 'alt', 'id' => $a['id'] ) );
	}
);

/* [mmc_section eyebrow="" title="" intro="" tone="plain|alt|dark" width="narrow|wide"]Any content[/mmc_section] */
add_shortcode(
	'mmc_section',
	static function ( $atts, $content = '' ) {
		$a = shortcode_atts( array( 'eyebrow' => '', 'title' => '', 'intro' => '', 'tone' => 'plain', 'width' => 'narrow', 'id' => '', 'align' => 'left' ), $atts );
		return mmc_section( 'text', '<div class="mmc-prose mmc-prose--' . esc_attr( 'wide' === $a['width'] ? 'wide' : 'narrow' ) . '">' . mmc_inner( $content ) . '</div>', $a );
	}
);

/* [mmc_split title="" eyebrow="" image="" reverse="no" button="" url=""]Text[/mmc_split] */
add_shortcode(
	'mmc_split',
	static function ( $atts, $content = '' ) {
		$a   = shortcode_atts( array( 'eyebrow' => '', 'title' => '', 'image' => '', 'reverse' => 'no', 'button' => '', 'url' => '', 'tone' => 'plain', 'id' => '' ), $atts );
		$img = '';
		if ( $a['image'] ) {
			$img = is_numeric( $a['image'] ) ? wp_get_attachment_image( (int) $a['image'], 'large' ) : '<img src="' . esc_url( $a['image'] ) . '" alt="">';
		}
		$copy  = ( $a['eyebrow'] ? '<span class="mmc-eyebrow">' . esc_html( $a['eyebrow'] ) . '</span>' : '' ) . ( $a['title'] ? '<h2 class="mmc-title">' . esc_html( $a['title'] ) . '</h2>' : '' );
		$copy .= '<div class="mmc-prose">' . mmc_inner( $content ) . '</div>';
		$copy .= $a['button'] ? '<div class="mmc-actions">' . mmc_btn( $a['button'], $a['url'] ) . '</div>' : '';
		$html  = '<div class="mmc-split' . ( 'yes' === $a['reverse'] ? ' is-reverse' : '' ) . '"><div class="mmc-split__copy">' . $copy . '</div><div class="mmc-split__media">' . $img . '</div></div>';
		return mmc_section( 'split', $html, array( 'tone' => $a['tone'], 'id' => $a['id'] ) );
	}
);

/* [mmc_features title="" eyebrow="" intro="" columns="3"][mmc_feature icon="shield" title=""]Text[/mmc_feature]…[/mmc_features] */
add_shortcode(
	'mmc_features',
	static function ( $atts, $content = '' ) {
		$a = shortcode_atts( array( 'eyebrow' => '', 'title' => '', 'intro' => '', 'columns' => '3', 'tone' => 'plain', 'id' => '' ), $atts );
		$c = max( 2, min( 4, (int) $a['columns'] ) );
		return mmc_section( 'features', '<div class="mmc-grid mmc-grid--' . $c . '">' . do_shortcode( shortcode_unautop( trim( (string) $content ) ) ) . '</div>', $a );
	}
);
add_shortcode(
	'mmc_feature',
	static function ( $atts, $content = '' ) {
		$a = shortcode_atts( array( 'icon' => 'check', 'title' => '' ), $atts );
		return '<div class="mmc-card"><span class="mmc-card__icon">' . mmc_icon( sanitize_key( $a['icon'] ) ) . '</span><h3>' . esc_html( $a['title'] ) . '</h3>' . mmc_inner( $content ) . '</div>';
	}
);

/* [mmc_steps title=""][mmc_step title=""]Text[/mmc_step][/mmc_steps] */
add_shortcode(
	'mmc_steps',
	static function ( $atts, $content = '' ) {
		$a = shortcode_atts( array( 'eyebrow' => '', 'title' => '', 'intro' => '', 'tone' => 'alt', 'id' => '' ), $atts );
		return mmc_section( 'steps', '<ol class="mmc-steps">' . do_shortcode( shortcode_unautop( trim( (string) $content ) ) ) . '</ol>', $a );
	}
);
add_shortcode(
	'mmc_step',
	static function ( $atts, $content = '' ) {
		$a = shortcode_atts( array( 'title' => '' ), $atts );
		return '<li class="mmc-step"><h3>' . esc_html( $a['title'] ) . '</h3>' . mmc_inner( $content ) . '</li>';
	}
);

/* [mmc_stats][mmc_stat value="500+" label="Miners delivered"][/mmc_stats] */
add_shortcode(
	'mmc_stats',
	static function ( $atts, $content = '' ) {
		$a = shortcode_atts( array( 'eyebrow' => '', 'title' => '', 'intro' => '', 'tone' => 'dark', 'id' => '' ), $atts );
		return mmc_section( 'stats', '<div class="mmc-stats">' . do_shortcode( shortcode_unautop( trim( (string) $content ) ) ) . '</div>', $a );
	}
);
add_shortcode(
	'mmc_stat',
	static function ( $atts ) {
		$a = shortcode_atts( array( 'value' => '', 'label' => '' ), $atts );
		return '<div class="mmc-stat"><strong>' . esc_html( $a['value'] ) . '</strong><span>' . esc_html( $a['label'] ) . '</span></div>';
	}
);

/* [mmc_faq title=""][mmc_q q="Question"]Answer[/mmc_q][/mmc_faq] */
add_shortcode(
	'mmc_faq',
	static function ( $atts, $content = '' ) {
		$a = shortcode_atts( array( 'eyebrow' => '', 'title' => __( 'Frequently asked questions', 'mine-mirth-commerce' ), 'intro' => '', 'tone' => 'plain', 'id' => '' ), $atts );
		return mmc_section( 'faq', '<div class="mmc-faq">' . do_shortcode( shortcode_unautop( trim( (string) $content ) ) ) . '</div>', $a );
	}
);
add_shortcode(
	'mmc_q',
	static function ( $atts, $content = '' ) {
		$a = shortcode_atts( array( 'q' => '' ), $atts );
		if ( function_exists( 'mm_schema_add_faq' ) ) {
			mm_schema_add_faq( array( array( $a['q'], wp_strip_all_tags( (string) $content ) ) ) );
		}
		return '<details class="mmc-faq__item"><summary>' . esc_html( $a['q'] ) . '</summary><div class="mmc-faq__a">' . mmc_inner( $content ) . '</div></details>';
	}
);

/* [mmc_cta title="" text="" button="" url="" button2="" url2=""] */
add_shortcode(
	'mmc_cta',
	static function ( $atts ) {
		$a    = shortcode_atts( array( 'title' => '', 'text' => '', 'button' => __( 'Shop miners', 'mine-mirth-commerce' ), 'url' => '{shop}', 'button2' => '', 'url2' => '', 'id' => '' ), $atts );
		$html = '<div class="mmc-cta"><div><h2 class="mmc-title">' . esc_html( $a['title'] ) . '</h2>' . ( $a['text'] ? '<p>' . esc_html( $a['text'] ) . '</p>' : '' ) . '</div><div class="mmc-actions">' . mmc_btn( $a['button'], $a['url'] ) . mmc_btn( $a['button2'], $a['url2'], 'ghost' ) . '</div></div>';
		return mmc_section( 'cta', $html, array( 'tone' => 'plain', 'id' => $a['id'] ) );
	}
);

/* [mmc_products title="" category="slug" ids="1,2" limit="4" columns="4" orderby="date|popularity|rating|price" on_sale="no"] */
add_shortcode(
	'mmc_products',
	static function ( $atts ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return '';
		}
		$a    = shortcode_atts( array( 'eyebrow' => '', 'title' => '', 'intro' => '', 'category' => '', 'ids' => '', 'limit' => '4', 'columns' => '4', 'orderby' => 'date', 'on_sale' => 'no', 'tone' => 'plain', 'id' => '' ), $atts );
		$args = array( 'limit' => (int) $a['limit'], 'columns' => (int) $a['columns'], 'orderby' => sanitize_key( $a['orderby'] ) );
		if ( $a['category'] ) {
			$args['category'] = sanitize_text_field( $a['category'] );
		}
		if ( $a['ids'] ) {
			$args['ids'] = implode( ',', array_map( 'absint', explode( ',', $a['ids'] ) ) );
		}
		if ( 'yes' === $a['on_sale'] ) {
			$args['on_sale'] = 'true';
		}
		$sc = '[products';
		foreach ( $args as $k => $v ) {
			$sc .= ' ' . $k . '="' . esc_attr( (string) $v ) . '"';
		}
		$sc .= ']';
		return mmc_section( 'products', do_shortcode( $sc ), $a );
	}
);

/* [mmc_contact email="" phone="" address="" hours="" form=""] — form takes a form plugin shortcode name without brackets, e.g. form='contact-form-7 id="12"'. */
add_shortcode(
	'mmc_contact',
	static function ( $atts, $content = '' ) {
		$a     = shortcode_atts(
			array(
				'eyebrow' => '',
				'title'   => __( 'Talk to the team', 'mine-mirth-commerce' ),
				'intro'   => '',
				'email'   => (string) mmc_theme_opt( 'contact_email', get_option( 'admin_email' ) ),
				'phone'   => (string) mmc_theme_opt( 'contact_phone' ),
				'address' => (string) mmc_theme_opt( 'contact_address' ),
				'hours'   => (string) mmc_theme_opt( 'contact_hours' ),
				'form'    => '',
				'id'      => '',
			),
			$atts
		);
		$cards = '';
		$items = array(
			array( 'mail', __( 'Email', 'mine-mirth-commerce' ), $a['email'], $a['email'] ? 'mailto:' . antispambot( $a['email'] ) : '' ),
			array( 'phone', __( 'Phone', 'mine-mirth-commerce' ), $a['phone'], $a['phone'] ? 'tel:' . preg_replace( '/[^0-9+]/', '', $a['phone'] ) : '' ),
			array( 'map-pin', __( 'Address', 'mine-mirth-commerce' ), $a['address'], '' ),
			array( 'clock', __( 'Hours', 'mine-mirth-commerce' ), $a['hours'], '' ),
		);
		foreach ( $items as $it ) {
			if ( '' === trim( (string) $it[2] ) ) {
				continue;
			}
			$val    = $it[3] ? '<a href="' . esc_url( $it[3], array( 'mailto', 'tel' ) ) . '">' . esc_html( 'mail' === $it[0] ? antispambot( $it[2] ) : $it[2] ) . '</a>' : esc_html( $it[2] );
			$cards .= '<div class="mmc-card mmc-card--contact"><span class="mmc-card__icon">' . mmc_icon( $it[0] ) . '</span><small>' . esc_html( $it[1] ) . '</small><strong>' . $val . '</strong></div>';
		}
		$form  = $a['form'] ? do_shortcode( '[' . sanitize_text_field( $a['form'] ) . ']' ) : '';
		$extra = mmc_inner( $content );
		$html  = '<div class="mmc-contact"><div class="mmc-contact__cards">' . $cards . '</div>' . ( $form || $extra ? '<div class="mmc-card mmc-contact__form">' . $extra . $form . '</div>' : '' ) . '</div>';
		return mmc_section( 'contact', $html, $a );
	}
);

/* [mmc_legal updated="2026-10-01"]Policy text[/mmc_legal] */
add_shortcode(
	'mmc_legal',
	static function ( $atts, $content = '' ) {
		$a    = shortcode_atts( array( 'updated' => '', 'id' => '' ), $atts );
		$date = $a['updated'] ? '<p class="mmc-legal__date">' . esc_html( sprintf( /* translators: %s: date */ __( 'Last updated %s', 'mine-mirth-commerce' ), date_i18n( get_option( 'date_format' ), strtotime( $a['updated'] ) ) ) ) . '</p>' : '';
		return mmc_section( 'legal', '<div class="mmc-prose mmc-prose--narrow mmc-legal">' . $date . mmc_inner( $content ) . '</div>', array( 'id' => $a['id'] ) );
	}
);
