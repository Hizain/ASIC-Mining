<?php
/**
 * Generic helpers used across templates.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is WooCommerce active?
 */
function mm_is_woo(): bool {
	return class_exists( 'WooCommerce' );
}

/**
 * Store page URLs with graceful fallbacks.
 */
function mm_shop_url(): string {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'shop' );
	}
	return home_url( '/shop/' );
}

function mm_account_url(): string {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'myaccount' );
	}
	return wp_login_url();
}

function mm_blog_url(): string {
	$page_for_posts = (int) get_option( 'page_for_posts' );
	if ( $page_for_posts ) {
		return (string) get_permalink( $page_for_posts );
	}
	return home_url( '/blogs/' );
}

/**
 * Resolve a URL setting. Supports {shop}, {account}, {cart}, {checkout},
 * {blog}, {home} tokens (optionally followed by a query/path) and
 * site-relative paths.
 */
function mm_url( $value ): string {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}

	if ( preg_match( '/^\{(shop|account|cart|checkout|blog|home)\}(.*)$/', $value, $m ) ) {
		switch ( $m[1] ) {
			case 'shop':
				$base = mm_shop_url();
				break;
			case 'account':
				$base = mm_account_url();
				break;
			case 'cart':
				$base = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
				break;
			case 'checkout':
				$base = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
				break;
			case 'blog':
				$base = mm_blog_url();
				break;
			default:
				$base = home_url( '/' );
		}
		$rest = $m[2];
		if ( '' !== $rest && '?' === $rest[0] ) {
			parse_str( substr( $rest, 1 ), $args );
			return add_query_arg( $args, $base );
		}
		return $base . ltrim( $rest, '/' );
	}

	if ( '/' === $value[0] && ( ! isset( $value[1] ) || '/' !== $value[1] ) ) {
		return home_url( $value );
	}
	return $value;
}

/**
 * Escape text and turn *words* into gradient highlights.
 */
function mm_highlight( $text ): string {
	$text = esc_html( (string) $text );
	return (string) preg_replace( '/\*(.+?)\*/u', '<span class="mm-grad-text">$1</span>', $text );
}

/**
 * Split a textarea value into clean lines.
 */
function mm_lines( $text ): array {
	$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
	return array_values( array_filter( array_map( 'trim', (array) $lines ), 'strlen' ) );
}

/**
 * Split a comma list into clean items.
 */
function mm_list( $text, string $sep = ',' ): array {
	return array_values( array_filter( array_map( 'trim', explode( $sep, (string) $text ) ), 'strlen' ) );
}

/**
 * Parse the homepage sortable setting into [ id => enabled ].
 */
function mm_home_sections_state(): array {
	$raw     = (string) mm_opt( 'home_sections' );
	$choices = mm_home_section_choices();
	$state   = array();
	foreach ( mm_list( $raw ) as $item ) {
		$enabled = '-' !== $item[0];
		$id      = ltrim( $item, '-' );
		if ( isset( $choices[ $id ] ) && ! isset( $state[ $id ] ) ) {
			$state[ $id ] = $enabled;
		}
	}
	foreach ( array_keys( $choices ) as $id ) {
		if ( ! isset( $state[ $id ] ) ) {
			$state[ $id ] = false;
		}
	}
	return $state;
}

function mm_home_sections_enabled(): array {
	return array_keys( array_filter( mm_home_sections_state() ) );
}

/**
 * Render a homepage section heading from the standard eyebrow/title/subtitle trio.
 *
 * @param string $prefix Option prefix.
 * @param array  $args   align (left|center), tag, extra (html appended to the row).
 */
function mm_section_heading( string $prefix, array $args = array() ): void {
	$args     = wp_parse_args( $args, array( 'align' => 'left', 'tag' => 'h2', 'extra' => '', 'id' => '' ) );
	$eyebrow  = mm_opt( $prefix . '_eyebrow' );
	$title    = mm_opt( $prefix . '_title' );
	$subtitle = mm_opt( $prefix . '_subtitle' );
	if ( ! $eyebrow && ! $title && ! $subtitle && ! $args['extra'] ) {
		return;
	}
	$tag = in_array( $args['tag'], array( 'h1', 'h2', 'h3' ), true ) ? $args['tag'] : 'h2';
	?>
	<div class="mm-section-head mm-section-head--<?php echo esc_attr( $args['align'] ); ?>" data-mm-reveal>
		<div class="mm-section-head__copy">
			<?php if ( $eyebrow ) : ?>
				<span class="mm-eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
			<?php endif; ?>
			<?php if ( $title ) : ?>
				<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="mm-section-title"<?php echo $args['id'] ? ' id="' . esc_attr( $args['id'] ) . '"' : ''; ?>><?php echo mm_highlight( $title ); // phpcs:ignore WordPress.Security.EscapeOutput ?></<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<?php endif; ?>
			<?php if ( $subtitle ) : ?>
				<p class="mm-section-sub"><?php echo wp_kses_post( $subtitle ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		if ( $args['extra'] ) {
			echo '<div class="mm-section-head__extra">' . $args['extra'] . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts by callers.
		}
		?>
	</div>
	<?php
}

/**
 * Button markup.
 *
 * @param string $text  Label.
 * @param string $url   Raw URL setting (tokens allowed).
 * @param string $style primary|ghost|outline|glass|link.
 * @param array  $args  icon, size, class, attrs.
 */
function mm_button( $text, $url, string $style = 'primary', array $args = array() ): string {
	if ( '' === trim( (string) $text ) ) {
		return '';
	}
	$args    = wp_parse_args( $args, array( 'icon' => '', 'size' => '', 'class' => '', 'attrs' => '' ) );
	$classes = array( 'mm-btn', 'mm-btn--' . $style );
	if ( $args['size'] ) {
		$classes[] = 'mm-btn--' . $args['size'];
	}
	if ( $args['class'] ) {
		$classes[] = $args['class'];
	}
	if ( 'primary' === $style && mm_opt( 'fx_magnetic' ) ) {
		$args['attrs'] .= ' data-mm-magnetic';
	}
	$icon = $args['icon'] ? mm_icon( $args['icon'] ) : '';
	return sprintf(
		'<a class="%1$s" href="%2$s"%3$s><span>%4$s</span>%5$s</a>',
		esc_attr( implode( ' ', $classes ) ),
		esc_url( mm_url( $url ) ),
		$args['attrs'],
		esc_html( $text ),
		$icon
	);
}

/**
 * Image by attachment ID, or empty string.
 */
function mm_image( $id, string $size = 'large', array $attr = array() ): string {
	$id = absint( $id );
	if ( ! $id ) {
		return '';
	}
	return (string) wp_get_attachment_image( $id, $size, false, $attr );
}

/**
 * Estimated reading time in minutes.
 */
function mm_reading_time( $post = null ): int {
	$post = get_post( $post );
	if ( ! $post ) {
		return 1;
	}
	$words = str_word_count( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) );
	return max( 1, (int) ceil( $words / 230 ) );
}

/**
 * True inside the Customizer preview.
 */
function mm_is_preview(): bool {
	return is_customize_preview();
}

/**
 * Render a template part and return its HTML.
 */
function mm_get_template_html( string $slug, array $args = array() ): string {
	ob_start();
	get_template_part( $slug, null, $args );
	return (string) ob_get_clean();
}

/**
 * Brand logo: custom logo (+ optional dark-mode logo) or text mark.
 */
function mm_logo( string $context = 'header' ): string {
	// Plugins (e.g. Mine Mirth Logos) can supply their own logo per context and mode.
	$custom = apply_filters( 'mm_logo_html', '', $context );
	if ( is_string( $custom ) && '' !== $custom ) {
		return $custom;
	}
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$dark_id = (int) mm_opt( 'logo_dark' );
	$name    = get_bloginfo( 'name' );
	$loading = 'header' === $context ? 'eager' : 'lazy';

	if ( $logo_id ) {
		$out = wp_get_attachment_image(
			$logo_id,
			'full',
			false,
			array(
				'class'         => 'mm-logo__img' . ( $dark_id ? ' mm-logo__img--light' : '' ),
				'alt'           => $name,
				'loading'       => $loading,
				'decoding'      => 'async',
				'fetchpriority' => 'header' === $context ? 'high' : 'auto',
			)
		);
		if ( $dark_id ) {
			$out .= wp_get_attachment_image(
				$dark_id,
				'full',
				false,
				array(
					'class'    => 'mm-logo__img mm-logo__img--dark',
					'alt'      => $name,
					'loading'  => $loading,
					'decoding' => 'async',
				)
			);
		}
		if ( $out ) {
			return '<span class="mm-logo__media">' . $out . '</span>';
		}
	}

	$mark = (string) mm_opt( 'logo_text_mark' );
	return '<span class="mm-logo__mark" aria-hidden="true">' . esc_html( $mark ? $mark : mb_substr( (string) $name, 0, 1 ) ) . '</span><span class="mm-logo__text">' . esc_html( $name ) . '</span>';
}

/**
 * Social links list.
 */
function mm_social_links( string $class = '' ): string {
	$items = '';
	foreach ( mm_social_networks() as $network => $label ) {
		$url = mm_opt( 'social_' . $network );
		if ( ! $url ) {
			continue;
		}
		$icon   = 'x' === $network ? 'x-social' : $network;
		$items .= sprintf(
			'<li><a href="%1$s" target="_blank" rel="noopener me" aria-label="%2$s">%3$s</a></li>',
			esc_url( $url ),
			esc_attr( $label ),
			mm_icon( $icon )
		);
	}
	if ( ! $items ) {
		return '';
	}
	return '<ul class="mm-social ' . esc_attr( $class ) . '">' . $items . '</ul>';
}

/**
 * Replace {year} and {site} tokens.
 */
function mm_tokens( $text ): string {
	return strtr(
		(string) $text,
		array(
			'{year}' => wp_date( 'Y' ),
			'{site}' => get_bloginfo( 'name' ),
		)
	);
}

/**
 * Parse a numeric value from a spec string like "200 TH/s" or "3,500W".
 */
function mm_parse_number( $value ): float {
	if ( preg_match( '/([0-9]+(?:[.,][0-9]+)*)/', (string) $value, $m ) ) {
		$num = $m[1];
		// "3,500" → 3500 ; "3,5" → 3.5.
		if ( preg_match( '/^\d{1,3}(,\d{3})+(\.\d+)?$/', $num ) ) {
			$num = str_replace( ',', '', $num );
		} else {
			$num = str_replace( ',', '.', $num );
		}
		return (float) $num;
	}
	return 0.0;
}

/**
 * Convert a hashrate spec to TH/s ("200 TH/s", "9.5 GH/s", "1.1 PH/s").
 */
function mm_hashrate_to_th( $value ): float {
	$num  = mm_parse_number( $value );
	$unit = strtoupper( (string) $value );
	if ( str_contains( $unit, 'PH' ) ) {
		return $num * 1000;
	}
	if ( str_contains( $unit, 'GH' ) ) {
		return $num / 1000;
	}
	if ( str_contains( $unit, 'MH' ) ) {
		return $num / 1e6;
	}
	return $num;
}

/**
 * Stable id for the current announcement (re-shows after the text changes).
 */
function mm_announce_id(): string {
	return substr( md5( (string) mm_opt( 'announce_text' ) . '|' . (string) mm_opt( 'announce_link' ) ), 0, 10 );
}

/**
 * Product for the hero showcase / floating card: the chosen ID, else the
 * newest featured product, else the newest product. Null without WooCommerce.
 *
 * @return WC_Product|null
 */
function mm_hero_product() {
	if ( ! mm_is_woo() ) {
		return null;
	}
	$id = (int) mm_opt( 'hero_product' );
	if ( $id ) {
		$product = wc_get_product( $id );
		return $product && $product->is_visible() ? $product : null;
	}
	$ids = get_transient( 'mm_hero_product_id' );
	if ( false === $ids ) {
		$ids = wc_get_products(
			array(
				'status'     => 'publish',
				'limit'      => 1,
				'featured'   => true,
				'return'     => 'ids',
				'visibility' => 'catalog',
			)
		);
		if ( ! $ids ) {
			$ids = wc_get_products(
				array(
					'status'     => 'publish',
					'limit'      => 1,
					'return'     => 'ids',
					'visibility' => 'catalog',
				)
			);
		}
		set_transient( 'mm_hero_product_id', $ids, HOUR_IN_SECONDS );
	}
	$product = $ids ? wc_get_product( (int) $ids[0] ) : null;
	return $product ?: null;
}
add_action( 'save_post_product', static fn() => delete_transient( 'mm_hero_product_id' ) );
