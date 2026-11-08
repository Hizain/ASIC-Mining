<?php
/**
 * The product description: clean typography, consistent headings, styled lists and tables,
 * an optional "On this page" bar and an optional "Show more".
 *
 * Text pasted from other websites often carries its own font sizes and colours. Those are removed
 * (when "Clean pasted formatting" is on), so every description looks the same, in the site's style.
 *
 * @package MineMirthProductPage
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'woocommerce_product_tabs',
	static function ( array $tabs ): array {
		if ( ! mmpp_opt( 'desc' ) || ! isset( $tabs['description'] ) ) {
			return $tabs;
		}
		$tabs['description']['callback'] = 'mmpp_description_tab';
		$title                           = trim( (string) mmpp_opt( 'tab_title' ) );
		if ( '' !== $title ) {
			$tabs['description']['title'] = $title;
		}
		return $tabs;
	},
	98
);

function mmpp_description_tab(): void {
	global $post;
	if ( mmpp_opt( 'desc_heading' ) ) {
		$heading = apply_filters( 'woocommerce_product_description_heading', __( 'Description', 'woocommerce' ) ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- WooCommerce's own heading.
		if ( $heading ) {
			echo '<h2 class="mmpp-desc__title">' . esc_html( $heading ) . '</h2>';
		}
	}
	$html = (string) apply_filters( 'the_content', get_the_content( null, false, $post ) );
	echo mmpp_description_html( $html ); // phpcs:ignore WordPress.Security.EscapeOutput -- post content, already filtered by the_content.
}

/**
 * Clean and wrap description HTML.
 */
function mmpp_description_html( string $html ): string {
	$s = mmpp_settings();
	if ( $s['clean'] ) {
		$html = mmpp_clean_formatting( $html );
	}
	if ( $s['demote_h1'] ) {
		// One H1 per page (the product name): description H1s become H2s.
		$html = (string) preg_replace( '#<h1(\s|>)#i', '<h2 data-mmpp-title="1"$1', $html );
		$html = (string) preg_replace( '#</h1>#i', '</h2>', $html );
	}
	// Wide tables scroll sideways on phones instead of breaking the layout.
	$html = (string) preg_replace( '#<table\b#i', '<div class="mmpp-table"><table', $html );
	$html = (string) preg_replace( '#</table>#i', '</table></div>', $html );

	// Anchors for the "On this page" bar.
	$toc  = array();
	$used = array();
	$html = (string) preg_replace_callback(
		'#<h([23])(\s[^>]*)?>(.*?)</h\1>#is',
		static function ( array $m ) use ( &$toc, &$used ): string {
			$attrs = $m[2] ?? '';
			$text  = trim( wp_strip_all_tags( $m[3] ) );
			if ( preg_match( '/\sid\s*=\s*["\']([^"\']+)["\']/i', $attrs, $idm ) ) {
				$id = $idm[1];
			} else {
				$base = sanitize_title( $text );
				$base = '' !== $base ? 'mmpp-' . $base : 'mmpp-section';
				$id   = $base;
				$n    = 2;
				while ( isset( $used[ $id ] ) ) {
					$id = $base . '-' . $n++;
				}
				$attrs .= ' id="' . esc_attr( $id ) . '"';
			}
			$used[ $id ] = true;
			// A former H1 is a title, not a section: leave it out of "On this page".
			if ( '2' === $m[1] && '' !== $text && false === strpos( $attrs, 'data-mmpp-title' ) ) {
				$toc[] = array( $id, $text );
			}
			return '<h' . $m[1] . $attrs . '>' . $m[3] . '</h' . $m[1] . '>';
		},
		$html
	);

	$classes = array(
		'mmpp-desc',
		'mmpp-desc--size-' . $s['text_size'],
		'mmpp-desc--width-' . $s['width'],
		'mmpp-desc--h-' . $s['headings'],
		'mmpp-desc--hs-' . $s['heading_style'],
		'mmpp-desc--lists-' . $s['lists'],
	);
	if ( $s['tables'] ) {
		$classes[] = 'mmpp-desc--tables';
	}
	$out = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" style="--mmpp-lh:' . esc_attr( (string) $s['line_height'] ) . '">';
	if ( $s['toc'] && count( $toc ) >= max( 2, (int) $s['toc_min'] ) ) {
		$out .= '<nav class="mmpp-toc" aria-label="' . esc_attr__( 'On this page', 'mine-mirth-product-page' ) . '"><span>' . esc_html__( 'On this page', 'mine-mirth-product-page' ) . '</span>';
		foreach ( $toc as $t ) {
			$out .= '<a href="#' . esc_attr( $t[0] ) . '">' . esc_html( wp_trim_words( $t[1], 6 ) ) . '</a>';
		}
		$out .= '</nav>';
	}
	if ( $s['collapse'] ) {
		$out .= '<div class="mmpp-desc__body" data-mmpp-collapse style="--mmpp-collapse:' . max( 240, (int) $s['collapse_h'] ) . 'px">' . $html . '</div>';
	} else {
		$out .= '<div class="mmpp-desc__body">' . $html . '</div>';
	}
	return $out . '</div>';
}

/**
 * Remove pasted fonts, sizes, colours and spacing; keep alignment.
 */
function mmpp_clean_formatting( string $html ): string {
	$html = (string) preg_replace_callback(
		'/\sstyle\s*=\s*("|\')(.*?)\1/is',
		static function ( array $m ): string {
			$keep = array();
			foreach ( explode( ';', html_entity_decode( $m[2], ENT_QUOTES ) ) as $decl ) {
				$decl = trim( $decl );
				if ( '' === $decl || false === strpos( $decl, ':' ) ) {
					continue;
				}
				$prop = strtolower( trim( strstr( $decl, ':', true ) ) );
				if ( in_array( $prop, array( 'text-align' ), true ) ) {
					$keep[] = $decl;
				}
			}
			return $keep ? ' style="' . esc_attr( implode( '; ', $keep ) ) . '"' : '';
		},
		$html
	);
	// Old <font> tags, and size/colour attributes on tables and cells.
	$html = (string) preg_replace( '#</?font\b[^>]*>#i', '', $html );
	do {
		$html = (string) preg_replace( '#(<(?:table|td|th|tr)\b[^>]*?)\s(?:width|height|bgcolor|cellpadding|cellspacing|border)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '$1', $html, -1, $n );
	} while ( $n > 0 );
	// Paragraphs left empty by editors ("&nbsp;").
	return (string) preg_replace( '#<p>(?:\s|&nbsp;|<br\s*/?>)*</p>#i', '', $html );
}
