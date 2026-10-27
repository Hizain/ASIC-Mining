<?php
/**
 * Premium page sections: one dynamic block ("Mine Mirth section", mm/section) with many layouts.
 *
 * Each section stores a layout name and its content as block attributes, so every page built
 * from them stays editable in the block editor (Inspector panel on the right) and keeps the
 * theme's design and live data.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Text, links and live tokens
 * ------------------------------------------------------------------------- */

/**
 * Live values for {tokens}. Market values are wrapped so assets/js/market.js keeps them current.
 */
function mm_s_token_values(): array {
	static $v = null;
	if ( null !== $v ) {
		return $v;
	}
	$d      = function_exists( 'mm_market_display' ) ? mm_market_display() : array();
	$m      = function_exists( 'mm_market_cached' ) ? mm_market_cached() : array();
	$live   = static fn( string $k ) => '<span data-mm-m="' . esc_attr( $k ) . '">' . esc_html( (string) ( $d[ $k ] ?? '—' ) ) . '</span>';
	$blocks = (int) ( $m['halving']['blocks_left'] ?? 0 );
	$eta    = $blocks ? time() + $blocks * 600 : 0;
	$cat    = function_exists( 'mm_catalog_filter' ) ? mm_catalog_filter( array( 'stock' => 'available' ) ) : array();
	$v      = array(
		'{btc_price}'      => $live( 'price' ),
		'{btc_change}'     => $live( 'change' ),
		'{hashprice}'      => $live( 'hashprice' ),
		'{hashrate}'       => $live( 'hashrate' ),
		'{difficulty}'     => $live( 'difficulty' ),
		'{adjustment}'     => $live( 'adjustment' ),
		'{height}'         => $live( 'height' ),
		'{fees}'           => $live( 'fees' ),
		'{halving_blocks}' => $live( 'halving' ),
		'{halving_days}'   => $eta ? esc_html( number_format_i18n( max( 0, ( $eta - time() ) / DAY_IN_SECONDS ), 0 ) ) : '—',
		'{halving_date}'   => $eta ? esc_html( wp_date( 'F Y', $eta ) ) : esc_html__( '2028', 'minemirth' ),
		'{miners_count}'   => esc_html( number_format_i18n( count( $cat ) ) ),
		'{brands_count}'   => esc_html( number_format_i18n( count( array_unique( array_filter( array_column( $cat, 'brand' ) ) ) ) ) ),
		'{year}'           => esc_html( wp_date( 'Y' ) ),
		'{site}'           => esc_html( get_bloginfo( 'name' ) ),
	);
	return $v;
}

function mm_s_live( string $html ): string {
	return false === strpos( $html, '{' ) ? $html : strtr( $html, mm_s_token_values() );
}

/** Heading text: escaped, *highlight* and live tokens. */
function mm_s_title( $text ): string {
	return mm_s_live( mm_highlight( (string) $text ) );
}

/** Short inline text: escaped plus tokens. */
function mm_s_line( $text ): string {
	return mm_s_live( esc_html( (string) $text ) );
}

/** Resolve link tokens inside href attributes (before sanitising, which would strip them). */
function mm_s_links( string $html ): string {
	if ( false === strpos( $html, 'href="{' ) ) {
		return $html;
	}
	return (string) preg_replace_callback( '/href="(\{[^"]+)"/', static fn( $m ) => 'href="' . esc_url( mm_s_url( $m[1] ) ) . '"', $html );
}

/** Body text: limited HTML (links, bold, lists), paragraphs and tokens. */
function mm_s_rich( $text ): string {
	$text = trim( (string) $text );
	if ( '' === $text ) {
		return '';
	}
	$text = mm_s_links( $text );
	return mm_s_live( wp_kses_post( false === strpos( $text, '<p' ) && false === strpos( $text, '<ul' ) ? wpautop( $text ) : $text ) );
}

/**
 * URL with tokens: {shop}, {blog}, {home}…, {page:key} (a site-builder page), {tool:id}, {cat:slug},
 * {brand:slug}, {contact}, {hosting}.
 */
function mm_s_url( $url ): string {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	if ( preg_match( '/^\{(page|tool|cat|brand):([a-z0-9_-]+)\}(.*)$/i', $url, $m ) ) {
		$base = '';
		if ( 'page' === $m[1] ) {
			$base = mm_page_url_by_key( $m[2] );
		} elseif ( 'tool' === $m[1] && function_exists( 'mm_tool_url' ) ) {
			$base = mm_tool_url( $m[2] );
		} elseif ( 'cat' === $m[1] ) {
			$term = get_term_by( 'slug', $m[2], 'product_cat' );
			$base = $term ? (string) get_term_link( $term ) : mm_shop_url();
		} elseif ( 'brand' === $m[1] ) {
			$term = taxonomy_exists( 'product_brand' ) ? get_term_by( 'slug', $m[2], 'product_brand' ) : false;
			$base = $term ? (string) get_term_link( $term ) : mm_page_url_by_key( 'brand-' . $m[2] );
		}
		return $base . $m[3];
	}
	if ( '{contact}' === $url ) {
		return mm_page_url_by_key( 'contact' );
	}
	if ( '{hosting}' === $url ) {
		return mm_page_url_by_key( 'hosting' );
	}
	return mm_url( $url );
}

/**
 * Permalink of a page created by the site builder (meta _mm_page_key), else its planned path.
 */
function mm_page_url_by_key( string $key ): string {
	static $cache = array();
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$ids = get_posts(
		array(
			'post_type'        => 'page',
			'post_status'      => function_exists( 'mm_page_statuses' ) ? mm_page_statuses() : 'publish',
			'meta_key'         => '_mm_page_key', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'       => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'           => 'ids',
			'numberposts'      => 1,
			'suppress_filters' => false,
		)
	);
	if ( $ids ) {
		return $cache[ $key ] = (string) get_permalink( $ids[0] );
	}
	$path = function_exists( 'mm_builder_path' ) ? mm_builder_path( $key ) : '';
	if ( 'shop' === $key ) {
		return $cache[ $key ] = mm_shop_url();
	}
	return $cache[ $key ] = home_url( $path ? '/' . $path . '/' : '/' );
}

function mm_s_buttons( array $buttons, string $class = '' ): string {
	$out = '';
	foreach ( $buttons as $i => $b ) {
		if ( empty( $b['label'] ) || empty( $b['url'] ) ) {
			continue;
		}
		$style = $b['style'] ?? ( 0 === $i ? 'primary' : 'glass' );
		$url   = mm_s_url( $b['url'] );
		$out  .= '<a class="mm-btn mm-btn--' . esc_attr( $style ) . ' mm-btn--lg" href="' . esc_url( $url ) . '"' . ( 'primary' === $style && mm_opt( 'fx_magnetic' ) ? ' data-mm-magnetic' : '' ) . '><span>' . esc_html( $b['label'] ) . '</span>' . ( 0 === $i ? mm_icon( $b['icon'] ?? 'arrow-right' ) : ( ! empty( $b['icon'] ) ? mm_icon( $b['icon'] ) : '' ) ) . '</a>';
	}
	return $out ? '<div class="mm-btn-row ' . esc_attr( $class ) . '">' . $out . '</div>' : '';
}

/** Eyebrow, title, intro and optional action on the right. */
function mm_s_head( array $d, string $tag = 'h2', string $extra = '' ): string {
	if ( empty( $d['eyebrow'] ) && empty( $d['title'] ) && empty( $d['text'] ) ) {
		return '';
	}
	$align = ! empty( $d['center'] ) ? 'center' : 'left';
	$link  = '';
	if ( ! empty( $d['link_label'] ) && ! empty( $d['link_url'] ) ) {
		$link = '<a class="mm-btn mm-btn--ghost" href="' . esc_url( mm_s_url( $d['link_url'] ) ) . '"><span>' . esc_html( $d['link_label'] ) . '</span>' . mm_icon( 'arrow-right' ) . '</a>';
	}
	$extra = $extra . $link;
	return '<div class="mm-section-head mm-section-head--' . $align . ' mm-s__head" data-mm-reveal><div class="mm-section-head__copy">'
		. ( ! empty( $d['eyebrow'] ) ? '<span class="mm-eyebrow">' . mm_s_line( $d['eyebrow'] ) . '</span>' : '' )
		. ( ! empty( $d['title'] ) ? '<' . $tag . ' class="mm-section-title" data-mm-split>' . mm_s_title( $d['title'] ) . '</' . $tag . '>' : '' )
		. ( ! empty( $d['text'] ) ? '<div class="mm-section-sub">' . mm_s_rich( $d['text'] ) . '</div>' : '' )
		. '</div>' . ( $extra ? '<div class="mm-section-head__extra">' . $extra . '</div>' : '' ) . '</div>';
}

function mm_s_image( $image, string $size = 'large', string $class = '' ): string {
	if ( is_numeric( $image ) && (int) $image ) {
		return mm_image( (int) $image, $size, array( 'class' => $class ) );
	}
	if ( is_string( $image ) && '' !== trim( $image ) ) {
		return '<img class="' . esc_attr( $class ) . '" src="' . esc_url( $image ) . '" alt="" loading="lazy" decoding="async">';
	}
	return '';
}

function mm_s_items( $items ): array {
	return is_array( $items ) ? array_values( array_filter( $items, 'is_array' ) ) : array();
}

/** Lines of text (array or newline-separated string). */
function mm_s_lines( $value, bool $keep_empty = false ): array {
	$lines = is_array( $value ) ? array_map( 'strval', $value ) : preg_split( '/\r\n|\r|\n/', (string) $value );
	$lines = array_map( 'trim', $lines );
	return $keep_empty ? $lines : array_values( array_filter( $lines, static fn( $l ) => '' !== $l ) );
}

/** Hashrate in H/s with the right unit (TH/s, GH/s…). */
function mm_s_hashrate( float $hs ): string {
	$units = array( 'H/s', 'kH/s', 'MH/s', 'GH/s', 'TH/s', 'PH/s', 'EH/s' );
	$i     = 0;
	while ( $hs >= 1000 && $i < count( $units ) - 1 ) {
		$hs /= 1000;
		++$i;
	}
	return number_format_i18n( $hs, $hs >= 100 ? 0 : 1 ) . ' ' . $units[ $i ];
}

/* ---------------------------------------------------------------------------
 * Visuals for hero and split sections
 * ------------------------------------------------------------------------- */

function mm_s_visual( array $d ): string {
	$v = (string) ( $d['visual'] ?? 'orbit' );
	switch ( $v ) {
		case 'none':
			return '';
		case 'image':
			$img = mm_s_image( $d['image'] ?? '', 'mm-wide', 'mm-s-visual__img' );
			return $img ? '<div class="mm-s-visual mm-s-visual--image" data-mm-reveal>' . $img . '</div>' : '';
		case 'market':
			return '<div class="mm-s-visual mm-s-visual--market" data-mm-reveal>' . mm_s_market_card() . '</div>';
		case 'product':
			$rows = function_exists( 'mm_catalog_filter' ) ? mm_catalog_filter( array_merge( mm_s_query( $d['query'] ?? array() ), array( 'limit' => 1, 'stock' => 'available' ) ) ) : array();
			if ( $rows ) {
				return '<div class="mm-s-visual mm-s-visual--product" data-mm-reveal>' . mm_s_product_card( $rows[0], true ) . '</div>';
			}
			return mm_s_orbit( $d );
		case 'checklist':
			$li = '';
			foreach ( mm_s_lines( $d['checklist'] ?? array() ) as $line ) {
				$li .= '<li>' . mm_icon( 'check-circle' ) . '<span>' . mm_s_line( $line ) . '</span></li>';
			}
			return '<div class="mm-s-visual mm-s-visual--checklist" data-mm-reveal><div class="mm-s-glass">'
				. ( ! empty( $d['checklist_title'] ) ? '<strong class="mm-s-glass__title">' . mm_s_line( $d['checklist_title'] ) . '</strong>' : '' )
				. '<ul>' . $li . '</ul></div></div>';
		case 'stats':
			$st = '';
			foreach ( mm_s_items( $d['stats'] ?? array() ) as $s ) {
				$st .= '<div><strong>' . mm_s_line( $s['value'] ?? '' ) . '</strong><span>' . mm_s_line( $s['label'] ?? '' ) . '</span></div>';
			}
			return '<div class="mm-s-visual mm-s-visual--stats" data-mm-reveal><div class="mm-s-glass mm-s-glass--stats">' . $st . '</div></div>';
		default:
			return mm_s_orbit( $d );
	}
}

/**
 * Icon composition: a glowing centre tile with orbiting chips. Works for any topic without stock photos.
 */
function mm_s_orbit( array $d ): string {
	$chips = '';
	foreach ( array_slice( mm_s_items( $d['chips'] ?? array() ), 0, 4 ) as $i => $c ) {
		$chips .= '<span class="mm-orbit__chip mm-orbit__chip--' . ( $i + 1 ) . '">' . mm_icon( $c['icon'] ?? 'check' ) . '<span><b>' . mm_s_line( $c['value'] ?? '' ) . '</b>' . mm_s_line( $c['label'] ?? '' ) . '</span></span>';
	}
	return '<div class="mm-s-visual mm-s-visual--orbit" data-mm-reveal aria-hidden="true"><div class="mm-orbit">'
		. '<span class="mm-orbit__ring"></span><span class="mm-orbit__ring mm-orbit__ring--2"></span><span class="mm-orbit__ring mm-orbit__ring--3"></span>'
		. '<span class="mm-orbit__core">' . mm_icon( (string) ( $d['icon'] ?? 'cpu' ) ) . '</span>' . $chips . '</div></div>';
}

function mm_s_market_card(): string {
	if ( function_exists( 'wp_enqueue_script' ) ) {
		wp_enqueue_script( 'mm-market' );
	}
	$d    = mm_market_display();
	$rows = array(
		array( 'hashprice', __( 'Hashprice / PH / day', 'minemirth' ), 'activity' ),
		array( 'difficulty', __( 'Difficulty', 'minemirth' ), 'layers' ),
		array( 'hashrate', __( 'Network hashrate', 'minemirth' ), 'cpu' ),
		array( 'adjustment', __( 'Next adjustment', 'minemirth' ), 'gauge' ),
	);
	$out = '';
	foreach ( $rows as $r ) {
		$out .= '<li>' . mm_icon( $r[2] ) . '<span>' . esc_html( $r[1] ) . '</span><b data-mm-m="' . esc_attr( $r[0] ) . '">' . esc_html( (string) ( $d[ $r[0] ] ?? '—' ) ) . '</b></li>';
	}
	return '<div class="mm-s-glass mm-s-mcard"><div class="mm-s-mcard__top"><span class="mm-s-mcard__coin">' . mm_icon( 'bitcoin' ) . '</span><div><small><span class="mm-live-dot"></span>' . esc_html__( 'Bitcoin · live', 'minemirth' ) . '</small><strong data-mm-m="price">' . esc_html( $d['price'] ?? '—' ) . '</strong></div><span class="mm-change ' . esc_attr( $d['change_class'] ?? '' ) . '" data-mm-m="change">' . esc_html( $d['change'] ?? '' ) . '</span></div>'
		. ( ! empty( $d['spark_path'] ) ? '<svg class="mm-s-mcard__spark' . ( ! empty( $d['spark_down'] ) ? ' is-down' : '' ) . '" data-mm-spark viewBox="0 0 120 32" preserveAspectRatio="none" aria-hidden="true"><path d="' . esc_attr( $d['spark_path'] ) . '"/></svg>' : '' )
		. '<ul>' . $out . '</ul><a class="mm-s-mcard__link" href="' . esc_url( function_exists( 'mm_tool_url' ) ? mm_tool_url( 'hashprice' ) : home_url( '/' ) ) . '">' . esc_html__( 'Open the hashprice tracker', 'minemirth' ) . mm_icon( 'arrow-right' ) . '</a></div>';
}

/* ---------------------------------------------------------------------------
 * Products
 * ------------------------------------------------------------------------- */

function mm_s_query( $q ): array {
	$q = is_array( $q ) ? $q : array();
	return array(
		'algo'    => sanitize_key( (string) ( $q['algo'] ?? '' ) ),
		'cooling' => sanitize_key( (string) ( $q['cooling'] ?? '' ) ),
		'brand'   => sanitize_text_field( (string) ( $q['brand'] ?? '' ) ),
		'home'    => ! empty( $q['home'] ),
		'solo'    => ! empty( $q['solo'] ),
		'cat'     => sanitize_title( (string) ( $q['cat'] ?? '' ) ),
		'sort'    => sanitize_key( (string) ( $q['sort'] ?? 'popular' ) ),
		'miners'  => empty( $q['parts'] ),
		'parts'   => ! empty( $q['parts_only'] ),
	);
}

function mm_s_price( float $amount ): string {
	return function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $amount ) ) : '$' . number_format_i18n( $amount, 0 );
}

function mm_s_product_card( array $r, bool $feature = false ): string {
	$specs = array();
	if ( $r['hash'] > 0 ) {
		$specs[] = array( 'cpu', mm_s_hashrate( (float) $r['hash'] ) );
	}
	if ( $r['watts'] > 0 ) {
		$specs[] = array( 'bolt', number_format_i18n( $r['watts'] ) . ' W' );
	}
	if ( $r['eff'] > 0 && 'sha256' === $r['algo'] ) {
		$specs[] = array( 'gauge', number_format_i18n( $r['eff'], 1 ) . ' J/TH' );
	}
	$algo = array( 'sha256' => 'SHA-256', 'scrypt' => 'Scrypt', 'kheavyhash' => 'kHeavyHash', 'blake3' => 'Blake3', 'etchash' => 'Etchash', 'equihash' => 'Equihash', 'x11' => 'X11' );
	$chips = '';
	foreach ( $specs as $s ) {
		$chips .= '<span>' . mm_icon( $s[0] ) . esc_html( $s[1] ) . '</span>';
	}
	$stock = array(
		'instock'     => array( 'is-in', __( 'In stock', 'minemirth' ) ),
		'onbackorder' => array( 'is-pre', __( 'Pre-order', 'minemirth' ) ),
		'outofstock'  => array( 'is-out', __( 'Sold out', 'minemirth' ) ),
	)[ $r['stock'] ] ?? array( 'is-in', '' );
	$calc  = '';
	if ( $r['th'] > 0 && 'sha256' === $r['algo'] && function_exists( 'mm_tool_url' ) ) {
		$calc = add_query_arg( array( 'tool' => 'profit', 'th' => $r['th'], 'watts' => $r['watts'], 'cost' => $r['price'] ), mm_tool_url( 'profit' ) );
	}
	$price = $r['price'] > 0 ? '<span class="mm-pcard__price">' . ( $r['regular'] > $r['price'] ? '<del>' . esc_html( mm_s_price( (float) $r['regular'] ) ) . '</del>' : '' ) . '<b>' . esc_html( mm_s_price( (float) $r['price'] ) ) . '</b></span>' : '<span class="mm-pcard__price"><b>' . esc_html__( 'Ask for price', 'minemirth' ) . '</b></span>';
	return '<article class="mm-pcard' . ( $feature ? ' mm-pcard--feature' : '' ) . '" data-mm-reveal>'
		. '<a class="mm-pcard__media" href="' . esc_url( $r['url'] ) . '" tabindex="-1" aria-hidden="true">' . ( $r['img'] ? '<img src="' . esc_url( $r['img'] ) . '" alt="" loading="lazy" decoding="async">' : mm_icon( 'cpu' ) ) . '<span class="mm-pcard__stock ' . esc_attr( $stock[0] ) . '">' . esc_html( $stock[1] ) . '</span></a>'
		. '<div class="mm-pcard__body"><div class="mm-pcard__meta">' . ( $r['brand'] ? '<span>' . esc_html( $r['brand'] ) . '</span>' : '' ) . ( isset( $algo[ $r['algo'] ] ) ? '<span>' . esc_html( $algo[ $r['algo'] ] ) . '</span>' : '' ) . ( 'air' !== $r['cooling'] ? '<span>' . esc_html( ucfirst( $r['cooling'] ) ) . '</span>' : '' ) . '</div>'
		. '<h3 class="mm-pcard__title"><a href="' . esc_url( $r['url'] ) . '">' . esc_html( $r['name'] ) . '</a></h3>'
		. ( $chips ? '<div class="mm-pcard__specs">' . $chips . '</div>' : '' )
		. '<div class="mm-pcard__foot">' . $price . '<span class="mm-pcard__actions">' . ( $calc ? '<a class="mm-icon-btn" href="' . esc_url( $calc ) . '" title="' . esc_attr__( 'Profit at today’s numbers', 'minemirth' ) . '" aria-label="' . esc_attr__( 'Profit at today’s numbers', 'minemirth' ) . '">' . mm_icon( 'calculator' ) . '</a>' : '' ) . '<a class="mm-btn mm-btn--primary mm-btn--sm" href="' . esc_url( $r['url'] ) . '"><span>' . esc_html__( 'View', 'minemirth' ) . '</span></a></span></div>'
		. '</div></article>';
}

/* ---------------------------------------------------------------------------
 * Layouts
 * ------------------------------------------------------------------------- */

function mm_s_render_hero( array $d ): string {
	$visual = mm_s_visual( $d );
	$crumbs = '';
	if ( ! is_front_page() && ( $d['crumbs'] ?? true ) && function_exists( 'mm_breadcrumbs' ) ) {
		ob_start();
		mm_breadcrumbs();
		$crumbs = (string) ob_get_clean();
	}
	$chips = '';
	foreach ( mm_s_lines( $d['badges'] ?? array() ) as $b ) {
		$chips .= '<span>' . mm_icon( 'check' ) . mm_s_line( $b ) . '</span>';
	}
	$tag = ! empty( $d['h2'] ) ? 'h2' : 'h1';
	return '<div class="mm-s-hero__bg" aria-hidden="true"><span class="mm-s-hero__glow"></span><span class="mm-s-hero__glow mm-s-hero__glow--2"></span><span class="mm-s-hero__grid"></span></div>'
		. '<div class="mm-container mm-s-hero__inner' . ( $visual ? '' : ' is-text' ) . ( ! empty( $d['center'] ) ? ' is-center' : '' ) . '">'
		. '<div class="mm-s-hero__copy">' . $crumbs
		. ( ! empty( $d['eyebrow'] ) ? '<span class="mm-eyebrow" data-mm-reveal>' . mm_s_line( $d['eyebrow'] ) . '</span>' : '' )
		. '<' . $tag . ' class="mm-s-hero__title" data-mm-reveal>' . mm_s_title( $d['title'] ?? get_the_title() ) . '</' . $tag . '>'
		. ( ! empty( $d['text'] ) ? '<div class="mm-s-hero__text" data-mm-reveal>' . mm_s_rich( $d['text'] ) . '</div>' : '' )
		. mm_s_buttons( mm_s_items( $d['buttons'] ?? array() ), ! empty( $d['center'] ) ? 'mm-btn-row--center' : '' )
		. ( $chips ? '<div class="mm-s-hero__badges" data-mm-reveal>' . $chips . '</div>' : '' )
		. '</div>' . $visual . '</div>';
}

/**
 * Animated hero: the homepage hero with the 3D sphere or rig, live floating cards, rotating word and
 * moving background. Anything left empty comes from Customize › Mine Mirth · Homepage › Hero.
 */
function mm_s_render_fxhero( array $d ): string {
	if ( mm_s_is_editor() ) {
		return '<div class="mm-container"><div class="mm-s-editor-note">' . mm_icon( 'sparkles' ) . '<strong>' . esc_html( str_replace( array( '{rotate}', '*' ), array( implode( ' / ', mm_s_lines( $d['rotate'] ?? array() ) ), '' ), (string) ( $d['title'] ?? '' ) ) ) . '</strong><span>' . esc_html__( 'Animated hero: the 3D visual, live cards and motion appear on the site.', 'minemirth' ) . '</span></div></div>';
	}
	wp_enqueue_script( 'mm-hero' );
	wp_enqueue_script( 'mm-market' );
	$args = array(
		'layout'     => (string) ( $d['layout'] ?? '' ),
		'visual'     => (string) ( $d['visual'] ?? '' ),
		'background' => (string) ( $d['background'] ?? '' ),
		'rotate'     => implode( ', ', mm_s_lines( $d['rotate'] ?? array() ) ),
		'title'      => (string) ( $d['title'] ?? '' ),
		'eyebrow'    => (string) ( $d['eyebrow'] ?? '' ),
		'text'       => (string) ( $d['text'] ?? '' ),
		'btn1_text'  => (string) ( $d['btn1_text'] ?? '' ),
		'btn1_url'   => (string) ( $d['btn1_url'] ?? '' ),
		'btn2_text'  => (string) ( $d['btn2_text'] ?? '' ),
		'btn2_url'   => (string) ( $d['btn2_url'] ?? '' ),
		'trust'      => mm_s_lines( $d['trust'] ?? array() ),
	);
	ob_start();
	get_template_part( 'template-parts/home/hero', null, $args );
	return (string) ob_get_clean();
}

/** Live market ticker: a seamless marquee of network numbers and your own short messages. */
function mm_s_render_ticker( array $d ): string {
	if ( mm_s_is_editor() ) {
		return '<div class="mm-container"><div class="mm-s-editor-note">' . mm_icon( 'activity' ) . '<strong>' . esc_html__( 'Live ticker', 'minemirth' ) . '</strong><span>' . esc_html__( 'Bitcoin price, hashrate, difficulty and your messages scroll here on the site.', 'minemirth' ) . '</span></div></div>';
	}
	wp_enqueue_script( 'mm-market' );
	ob_start();
	get_template_part(
		'template-parts/home/ticker',
		null,
		array(
			'metrics' => (string) ( $d['metrics'] ?? '' ),
			'custom'  => mm_s_lines( $d['custom'] ?? array() ),
		)
	);
	return (string) ob_get_clean();
}

function mm_s_render_features( array $d ): string {
	$cols  = max( 2, min( 4, (int) ( $d['columns'] ?? 3 ) ) );
	$style = in_array( $d['style'] ?? '', array( 'cards', 'minimal', 'numbered', 'bento' ), true ) ? $d['style'] : 'cards';
	$out   = '';
	foreach ( mm_s_items( $d['items'] ?? array() ) as $i => $it ) {
		$url  = ! empty( $it['url'] ) ? mm_s_url( $it['url'] ) : '';
		$tag  = $url ? 'a' : 'div';
		$out .= '<' . $tag . ' class="mm-feat' . ( $url ? ' is-link' : '' ) . '"' . ( $url ? ' href="' . esc_url( $url ) . '"' : '' ) . ' data-mm-reveal style="--d:' . ( $i % $cols ) . '">'
			. ( 'numbered' === $style ? '<span class="mm-feat__num">' . sprintf( '%02d', $i + 1 ) . '</span>' : '<span class="mm-feat__icon">' . mm_icon( (string) ( $it['icon'] ?? 'check' ) ) . '</span>' )
			. '<h3 class="mm-feat__title">' . mm_s_title( $it['title'] ?? '' ) . '</h3>'
			. ( ! empty( $it['text'] ) ? '<div class="mm-feat__text">' . mm_s_rich( $it['text'] ) . '</div>' : '' )
			. ( $url ? '<span class="mm-feat__more">' . esc_html( $it['link_label'] ?? __( 'Learn more', 'minemirth' ) ) . mm_icon( 'arrow-right' ) . '</span>' : '' )
			. '</' . $tag . '>';
	}
	return '<div class="mm-container">' . mm_s_head( $d ) . '<div class="mm-feats mm-feats--' . $style . ' mm-feats--' . $cols . '">' . $out . '</div>' . mm_s_buttons( mm_s_items( $d['buttons'] ?? array() ), 'mm-s__actions' ) . '</div>';
}

function mm_s_render_split( array $d ): string {
	$li = '';
	foreach ( mm_s_lines( $d['bullets'] ?? array() ) as $b ) {
		$li .= '<li>' . mm_icon( 'check' ) . '<span>' . mm_s_live( wp_kses_post( mm_s_links( $b ) ) ) . '</span></li>';
	}
	return '<div class="mm-container mm-s-split' . ( ! empty( $d['reverse'] ) ? ' is-reverse' : '' ) . '">'
		. '<div class="mm-s-split__copy" data-mm-reveal>'
		. ( ! empty( $d['eyebrow'] ) ? '<span class="mm-eyebrow">' . mm_s_line( $d['eyebrow'] ) . '</span>' : '' )
		. ( ! empty( $d['title'] ) ? '<h2 class="mm-section-title">' . mm_s_title( $d['title'] ) . '</h2>' : '' )
		. ( ! empty( $d['text'] ) ? '<div class="mm-s-split__text">' . mm_s_rich( $d['text'] ) . '</div>' : '' )
		. ( $li ? '<ul class="mm-s-ticks">' . $li . '</ul>' : '' )
		. mm_s_buttons( mm_s_items( $d['buttons'] ?? array() ) )
		. '</div>' . mm_s_visual( $d ) . '</div>';
}

function mm_s_render_stats( array $d ): string {
	$out = '';
	foreach ( mm_s_items( $d['items'] ?? array() ) as $i => $s ) {
		$value = (string) ( $s['value'] ?? '' );
		// Plain numbers count up as they scroll into view; live {values} update themselves.
		$count = false === strpos( $value, '{' ) && preg_match( '/^[^0-9]{0,3}[0-9][0-9,.]*[^0-9]{0,8}$/u', $value ) ? ' data-mm-count="' . esc_attr( $value ) . '"' : '';
		$out  .= '<div class="mm-sstat" data-mm-reveal style="--d:' . $i . '">' . ( ! empty( $s['icon'] ) ? '<span class="mm-sstat__icon">' . mm_icon( $s['icon'] ) . '</span>' : '' ) . '<strong class="mm-sstat__value"' . $count . '>' . mm_s_line( $value ) . '</strong><span class="mm-sstat__label">' . mm_s_line( $s['label'] ?? '' ) . '</span>' . ( ! empty( $s['text'] ) ? '<small class="mm-sstat__text">' . mm_s_line( $s['text'] ) . '</small>' : '' ) . '</div>';
	}
	if ( ! empty( $d['live'] ) ) {
		wp_enqueue_script( 'mm-market' );
	}
	return '<div class="mm-container">' . mm_s_head( $d ) . '<div class="mm-sstats mm-sstats--' . esc_attr( (string) ( $d['style'] ?? 'band' ) ) . '">' . $out . '</div>' . ( ! empty( $d['note'] ) ? '<p class="mm-s__note">' . mm_s_line( $d['note'] ) . '</p>' : '' ) . '</div>';
}

function mm_s_render_steps( array $d ): string {
	$out   = '';
	$items = mm_s_items( $d['items'] ?? array() );
	foreach ( $items as $i => $s ) {
		$out .= '<li class="mm-sstep" data-mm-reveal style="--d:' . $i . '"><span class="mm-sstep__num">' . ( ! empty( $s['icon'] ) ? mm_icon( $s['icon'] ) : sprintf( '%02d', $i + 1 ) ) . '</span><div><h3 class="mm-sstep__title">' . mm_s_title( $s['title'] ?? '' ) . '</h3><div class="mm-sstep__text">' . mm_s_rich( $s['text'] ?? '' ) . '</div></div></li>';
	}
	return '<div class="mm-container">' . mm_s_head( $d ) . '<ol class="mm-ssteps mm-ssteps--' . min( 5, max( 3, count( $items ) ) ) . '">' . $out . '</ol>' . mm_s_buttons( mm_s_items( $d['buttons'] ?? array() ), 'mm-s__actions' ) . '</div>';
}

function mm_s_render_faq( array $d ): string {
	$pairs = array();
	$out   = '';
	foreach ( mm_s_items( $d['items'] ?? array() ) as $i => $f ) {
		if ( empty( $f['q'] ) || empty( $f['a'] ) ) {
			continue;
		}
		$pairs[] = array( wp_strip_all_tags( mm_s_live( (string) $f['q'] ) ), wp_strip_all_tags( mm_s_live( (string) $f['a'] ) ) );
		$out    .= '<details class="mm-accordion__item" data-mm-reveal style="--d:' . min( $i, 4 ) . '"' . ( 0 === $i && ! empty( $d['open_first'] ) ? ' open' : '' ) . '><summary><span>' . mm_s_line( $f['q'] ) . '</span><span class="mm-accordion__icon" aria-hidden="true">' . mm_icon( 'plus' ) . '</span></summary><div class="mm-accordion__body">' . mm_s_rich( $f['a'] ) . '</div></details>';
	}
	if ( $pairs && ( $d['schema'] ?? true ) && function_exists( 'mm_schema_add_faq' ) ) {
		mm_schema_add_faq( $pairs );
	}
	$side = mm_s_head( $d ) . mm_s_buttons( mm_s_items( $d['buttons'] ?? array() ) );
	return '<div class="mm-container mm-faq mm-s-faq"><div class="mm-faq__intro">' . $side . '</div><div class="mm-accordion" data-mm-accordion>' . $out . '</div></div>';
}

function mm_s_render_cta( array $d ): string {
	$style = (string) ( $d['style'] ?? 'dark' );
	return '<div class="mm-container"><div class="mm-cta-card mm-s-cta mm-s-cta--' . esc_attr( $style ) . ( 'dark' === $style ? ' mm-scheme-dark' : '' ) . '" data-mm-reveal>'
		. '<div class="mm-cta-card__fx" aria-hidden="true"><span class="mm-cta-card__orbit"></span><span class="mm-cta-card__orbit mm-cta-card__orbit--2"></span><span class="mm-cta-card__orbit mm-cta-card__orbit--3"></span></div>'
		. ( ! empty( $d['icon'] ) ? '<span class="mm-s-cta__icon">' . mm_icon( $d['icon'] ) . '</span>' : '' )
		. mm_s_head( array_merge( $d, array( 'center' => true ) ) )
		. mm_s_buttons( mm_s_items( $d['buttons'] ?? array() ), 'mm-btn-row--center' )
		. ( ! empty( $d['note'] ) ? '<p class="mm-s-cta__note">' . mm_s_line( $d['note'] ) . '</p>' : '' )
		. '</div></div>';
}

/** Comparison table. Rows are "cell | cell | cell"; yes/no become ticks and crosses. */
function mm_s_render_table( array $d ): string {
	$cols = mm_s_lines( $d['columns'] ?? array(), true );
	while ( $cols && '' === end( $cols ) && count( $cols ) > 1 ) {
		array_pop( $cols );
	}
	$hl   = isset( $d['highlight'] ) ? (int) $d['highlight'] : -1;
	$cell = static function ( string $c ) {
		$t = strtolower( trim( $c ) );
		if ( in_array( $t, array( 'yes', '✓', 'y', 'true' ), true ) ) {
			return '<span class="mm-s-yes" aria-label="' . esc_attr__( 'Yes', 'minemirth' ) . '">' . mm_icon( 'check' ) . '</span>';
		}
		if ( in_array( $t, array( 'no', '✗', 'x', 'false', 'n' ), true ) ) {
			return '<span class="mm-s-no" aria-label="' . esc_attr__( 'No', 'minemirth' ) . '">' . mm_icon( 'minus' ) . '</span>';
		}
		return mm_s_live( wp_kses_post( mm_s_links( trim( $c ) ) ) );
	};
	$head = '';
	foreach ( $cols as $i => $c ) {
		$head .= '<th' . ( $i === $hl ? ' class="is-hl"' : '' ) . '>' . mm_s_line( $c ) . '</th>';
	}
	$body = '';
	foreach ( mm_s_lines( $d['rows'] ?? array() ) as $row ) {
		$cells = array_map( 'trim', explode( '|', $row ) );
		$body .= '<tr>';
		foreach ( $cells as $i => $c ) {
			$body .= ( 0 === $i ? '<th>' . mm_s_live( wp_kses_post( mm_s_links( $c ) ) ) . '</th>' : '<td' . ( $i === $hl ? ' class="is-hl"' : '' ) . ( isset( $cols[ $i ] ) ? ' data-label="' . esc_attr( $cols[ $i ] ) . '"' : '' ) . '>' . $cell( $c ) . '</td>' );
		}
		$body .= '</tr>';
	}
	return '<div class="mm-container">' . mm_s_head( $d ) . '<div class="mm-s-table" data-mm-reveal><table><thead><tr>' . $head . '</tr></thead><tbody>' . $body . '</tbody></table></div>' . ( ! empty( $d['note'] ) ? '<p class="mm-s__note">' . mm_s_line( $d['note'] ) . '</p>' : '' ) . '</div>';
}

function mm_s_render_products( array $d ): string {
	$limit = max( 1, min( 12, (int) ( $d['limit'] ?? 8 ) ) );
	$rows  = function_exists( 'mm_catalog_filter' ) ? mm_catalog_filter( array_merge( mm_s_query( $d['query'] ?? array() ), array( 'limit' => $limit, 'stock' => ! empty( $d['show_sold'] ) ? 'any' : 'available' ) ) ) : array();
	$cards = '';
	foreach ( $rows as $r ) {
		$cards .= mm_s_product_card( $r );
	}
	if ( ! $cards ) {
		$cards = '<div class="mm-s-empty" data-mm-reveal>' . mm_icon( 'package' ) . '<p>' . esc_html( $d['empty'] ?? __( 'New stock for this range is on the way. Ask us for current availability and lead times.', 'minemirth' ) ) . '</p><div class="mm-btn-row mm-btn-row--center"><a class="mm-btn mm-btn--primary" href="' . esc_url( mm_shop_url() ) . '"><span>' . esc_html__( 'Browse all miners', 'minemirth' ) . '</span>' . mm_icon( 'arrow-right' ) . '</a><a class="mm-btn mm-btn--ghost" href="' . esc_url( mm_page_url_by_key( 'contact' ) ) . '"><span>' . esc_html__( 'Request a quote', 'minemirth' ) . '</span></a></div></div>';
	} else {
		$cards = '<div class="mm-pcards">' . $cards . '</div>';
	}
	$d['link_label'] = $d['link_label'] ?? __( 'View all miners', 'minemirth' );
	$d['link_url']   = $d['link_url'] ?? '{shop}';
	return '<div class="mm-container">' . mm_s_head( $d ) . $cards . '</div>';
}

/**
 * Link cards: given items, or the current page's child pages (grouped by their "_mm_group").
 */
function mm_s_render_links( array $d ): string {
	$items = mm_s_items( $d['items'] ?? array() );
	if ( ! $items && ! empty( $d['auto'] ) ) {
		$parent = 'children' === $d['auto'] ? (int) get_the_ID() : (int) url_to_postid( mm_page_url_by_key( (string) $d['auto'] ) );
		foreach ( get_pages( array( 'parent' => $parent, 'sort_column' => 'menu_order,post_title', 'post_status' => implode( ',', function_exists( 'mm_page_statuses' ) ? mm_page_statuses() : array( 'publish' ) ) ) ) as $p ) {
			$items[] = array(
				'title' => get_post_meta( $p->ID, '_mm_card_title', true ) ? get_post_meta( $p->ID, '_mm_card_title', true ) : $p->post_title,
				'text'  => $p->post_excerpt,
				'url'   => get_permalink( $p ),
				'icon'  => get_post_meta( $p->ID, '_mm_icon', true ) ? get_post_meta( $p->ID, '_mm_icon', true ) : 'arrow-up-right',
				'group' => (string) get_post_meta( $p->ID, '_mm_group', true ),
				'badge' => (string) get_post_meta( $p->ID, '_mm_badge', true ),
			);
		}
	}
	$cols   = max( 2, min( 4, (int) ( $d['columns'] ?? 3 ) ) );
	$groups = array();
	foreach ( $items as $it ) {
		$groups[ (string) ( $it['group'] ?? '' ) ][] = $it;
	}
	$out = '';
	foreach ( $groups as $g => $list ) {
		$cards = '';
		foreach ( $list as $i => $it ) {
			$cards .= '<a class="mm-lcard" href="' . esc_url( mm_s_url( $it['url'] ?? '' ) ) . '" data-mm-reveal style="--d:' . ( $i % $cols ) . '"><span class="mm-lcard__icon">' . mm_icon( (string) ( $it['icon'] ?? 'arrow-up-right' ) ) . '</span><span class="mm-lcard__body"><strong>' . esc_html( $it['title'] ?? '' ) . ( ! empty( $it['badge'] ) ? ' <em>' . esc_html( $it['badge'] ) . '</em>' : '' ) . '</strong>' . ( ! empty( $it['text'] ) ? '<span>' . esc_html( wp_trim_words( wp_strip_all_tags( (string) $it['text'] ), 24 ) ) . '</span>' : '' ) . '</span><span class="mm-lcard__go">' . mm_icon( 'arrow-right' ) . '</span></a>';
		}
		$out .= ( '' !== $g ? '<h3 class="mm-lgroup__title" data-mm-reveal>' . esc_html( $g ) . '</h3>' : '' ) . '<div class="mm-lcards mm-lcards--' . $cols . '">' . $cards . '</div>';
	}
	return '<div class="mm-container">' . mm_s_head( $d ) . $out . '</div>';
}

function mm_s_render_market( array $d ): string {
	wp_enqueue_script( 'mm-market' );
	$m     = mm_market_display();
	$econ  = function_exists( 'mm_btc_economics' ) ? mm_btc_economics() : array();
	$tiles = array(
		array( 'price', __( 'Bitcoin price', 'minemirth' ), 'bitcoin', 'change' ),
		array( 'hashprice', __( 'Hashprice (per PH/s per day)', 'minemirth' ), 'activity', '' ),
		array( 'hashrate', __( 'Network hashrate', 'minemirth' ), 'cpu', '' ),
		array( 'difficulty', __( 'Difficulty', 'minemirth' ), 'layers', '' ),
		array( 'adjustment', __( 'Next difficulty adjustment', 'minemirth' ), 'gauge', '' ),
		array( 'halving', __( 'Blocks to the halving', 'minemirth' ), 'clock', '' ),
		array( 'fees', __( 'Average fees per block', 'minemirth' ), 'wallet', '' ),
		array( 'height', __( 'Block height', 'minemirth' ), 'cube', '' ),
	);
	$out = '';
	foreach ( $tiles as $i => $t ) {
		$out .= '<div class="mm-mtile' . ( $i < 2 ? ' is-major' : '' ) . '" data-mm-reveal style="--d:' . ( $i % 4 ) . '"><span class="mm-mtile__icon">' . mm_icon( $t[2] ) . '</span><small>' . esc_html( $t[1] ) . '</small><strong data-mm-m="' . esc_attr( $t[0] ) . '">' . esc_html( (string) ( $m[ $t[0] ] ?? '—' ) ) . '</strong>' . ( $t[3] ? '<span class="mm-change ' . esc_attr( $m['change_class'] ) . '" data-mm-m="change">' . esc_html( $m['change'] ) . '</span>' : '' ) . '</div>';
	}
	$sub = ! empty( $econ['btc_per_th_day'] ) ? sprintf(
		/* translators: 1: BTC per PH per day, 2: share of fees */
		__( 'One PH/s earns about %1$s BTC a day right now. Fees are %2$s of each block reward.', 'minemirth' ),
		number_format_i18n( $econ['btc_per_th_day'] * 1000, 6 ),
		number_format_i18n( $econ['fee_share'] * 100, 1 ) . '%'
	) : '';
	return '<div class="mm-container">' . mm_s_head( $d ) . '<div class="mm-mtiles">' . $out . '</div><p class="mm-s__note"><span class="mm-live-dot"></span> <span data-mm-m="updated">' . esc_html( $m['updated'] ) . '</span> · mempool.space · CoinGecko' . ( $sub ? ' · ' . esc_html( $sub ) : '' ) . '</p>' . mm_s_buttons( mm_s_items( $d['buttons'] ?? array() ), 'mm-s__actions' ) . '</div>';
}

function mm_s_render_tool( array $d ): string {
	$id = sanitize_key( (string) ( $d['id'] ?? 'profit' ) );
	if ( mm_s_is_editor() ) {
		$tools = function_exists( 'mm_tools' ) ? mm_tools() : array();
		return '<div class="mm-container">' . mm_s_head( $d ) . '<div class="mm-s-editor-note">' . mm_icon( $tools[ $id ]['icon'] ?? 'calculator' ) . '<strong>' . esc_html( $tools[ $id ]['title'] ?? $id ) . '</strong><span>' . esc_html__( 'The live tool appears here on the site.', 'minemirth' ) . '</span></div></div>';
	}
	return '<div class="mm-container">' . mm_s_head( $d ) . ( function_exists( 'mm_tool_html' ) ? mm_tool_html( $id ) : '' ) . '</div>';
}

function mm_s_render_tools( array $d ): string {
	return '<div class="mm-container">' . mm_s_head( $d ) . ( function_exists( 'mm_tools_grid_html' ) ? mm_tools_grid_html( (string) ( $d['exclude'] ?? '' ), (int) ( $d['limit'] ?? 0 ), (string) ( $d['group'] ?? '' ) ) : '' ) . '</div>';
}

/**
 * Real WooCommerce product reviews only (approved, 4 to 5 stars). Nothing is shown when there are none,
 * except an invitation to review for editors and buyers.
 */
function mm_s_render_reviews( array $d ): string {
	global $wpdb;
	$limit   = max( 3, min( 12, (int) ( $d['limit'] ?? 6 ) ) );
	$reviews = get_comments(
		array(
			'type'       => 'review',
			'status'     => 'approve',
			'number'     => $limit,
			'meta_query' => array( array( 'key' => 'rating', 'value' => 4, 'compare' => '>=', 'type' => 'NUMERIC' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	$stats = $wpdb->get_row( "SELECT COUNT(*) AS n, AVG(cm.meta_value) AS avg FROM {$wpdb->comments} c JOIN {$wpdb->commentmeta} cm ON cm.comment_id = c.comment_ID AND cm.meta_key = 'rating' WHERE c.comment_type = 'review' AND c.comment_approved = '1'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$stars = static function ( float $r ): string {
		$s = '';
		for ( $i = 1; $i <= 5; $i++ ) {
			$s .= '<span class="' . ( $i <= round( $r ) ? 'is-on' : '' ) . '">' . mm_icon( 'star' ) . '</span>';
		}
		return '<span class="mm-rstars" aria-label="' . esc_attr( sprintf( /* translators: %s: rating */ __( '%s out of 5', 'minemirth' ), number_format_i18n( $r, 1 ) ) ) . '">' . $s . '</span>';
	};
	$summary = '';
	if ( $stats && (int) $stats->n > 0 ) {
		$summary = '<div class="mm-s-rsum">' . $stars( (float) $stats->avg ) . '<strong>' . esc_html( number_format_i18n( (float) $stats->avg, 1 ) ) . '</strong><span>' . esc_html( sprintf( /* translators: %s: number of reviews */ _n( 'from %s verified review', 'from %s verified reviews', (int) $stats->n, 'minemirth' ), number_format_i18n( (int) $stats->n ) ) ) . '</span></div>';
	}
	$cards = '';
	foreach ( $reviews as $i => $c ) {
		$rating = (int) get_comment_meta( $c->comment_ID, 'rating', true );
		$name   = trim( (string) $c->comment_author );
		$parts  = preg_split( '/\s+/', $name );
		$short  = $parts[0] . ( isset( $parts[1] ) ? ' ' . mb_substr( $parts[1], 0, 1 ) . '.' : '' );
		$cards .= '<figure class="mm-rcard" data-mm-reveal style="--d:' . ( $i % 3 ) . '">' . $stars( (float) $rating ) . '<blockquote>' . esc_html( wp_trim_words( (string) $c->comment_content, 60 ) ) . '</blockquote><figcaption><span class="mm-rcard__avatar">' . esc_html( mb_strtoupper( mb_substr( $short, 0, 1 ) ) ) . '</span><span><b>' . esc_html( $short ) . '</b>' . ( get_comment_meta( $c->comment_ID, 'verified', true ) ? ' <em>' . esc_html__( 'Verified buyer', 'minemirth' ) . '</em>' : '' ) . '<small>' . esc_html( get_the_title( (int) $c->comment_post_ID ) ) . ' · ' . esc_html( get_comment_date( '', $c ) ) . '</small></span></figcaption></figure>';
	}
	if ( ! $cards && ! empty( $d['hide_empty'] ) ) {
		return '';
	}
	if ( ! $cards ) {
		$cards = '<div class="mm-s-empty" data-mm-reveal>' . mm_icon( 'star' ) . '<p>' . esc_html( $d['empty'] ?? __( 'Reviews from verified buyers appear here as soon as they are published. Bought from us? Leave a review on your miner’s product page; it helps other miners choose.', 'minemirth' ) ) . '</p>' . ( function_exists( 'wc_get_account_endpoint_url' ) ? '<div class="mm-btn-row mm-btn-row--center"><a class="mm-btn mm-btn--ghost" href="' . esc_url( wc_get_account_endpoint_url( 'orders' ) ) . '"><span>' . esc_html__( 'Review a past order', 'minemirth' ) . '</span></a></div>' : '' ) . '</div>';
	} else {
		$cards = '<div class="mm-rcards">' . $cards . '</div>';
	}
	return '<div class="mm-container">' . mm_s_head( $d, 'h2', $summary ) . $cards . '</div>';
}

function mm_s_render_pricing( array $d ): string {
	$plans = function_exists( 'mm_hosting_plans_shortcode' ) ? mm_hosting_plans_shortcode() : '';
	return '<div class="mm-container" id="hosting-plans">' . mm_s_head( $d ) . $plans . ( ! empty( $d['note'] ) ? '<p class="mm-s__note">' . mm_s_line( $d['note'] ) . '</p>' : '' ) . '</div>';
}

function mm_s_render_contact( array $d ): string {
	$methods = array();
	if ( mm_opt( 'contact_email' ) ) {
		$methods[] = array( 'mail', __( 'Email', 'minemirth' ), (string) mm_opt( 'contact_email' ), 'mailto:' . antispambot( (string) mm_opt( 'contact_email' ) ) );
	}
	if ( mm_opt( 'contact_phone' ) ) {
		$methods[] = array( 'phone', __( 'Phone / WhatsApp', 'minemirth' ), (string) mm_opt( 'contact_phone' ), 'tel:' . preg_replace( '/[^0-9+]/', '', (string) mm_opt( 'contact_phone' ) ) );
	}
	if ( mm_opt( 'social_telegram' ) ) {
		$methods[] = array( 'telegram', 'Telegram', __( 'Message us', 'minemirth' ), (string) mm_opt( 'social_telegram' ) );
	}
	if ( mm_opt( 'contact_hours' ) ) {
		$methods[] = array( 'clock', __( 'Support hours', 'minemirth' ), (string) mm_opt( 'contact_hours' ), '' );
	}
	$cards = '';
	foreach ( $methods as $m ) {
		$tag    = $m[3] ? 'a' : 'div';
		$cards .= '<' . $tag . ' class="mm-s-cmethod"' . ( $m[3] ? ' href="' . esc_url( $m[3] ) . '"' : '' ) . '>' . mm_icon( $m[0] ) . '<span><small>' . esc_html( $m[1] ) . '</small><b>' . esc_html( $m[2] ) . '</b></span></' . $tag . '>';
	}
	$form = (string) ( $d['form'] ?? '' );
	if ( '' === $form && shortcode_exists( 'minemirth_contact' ) ) {
		$form = '[minemirth_contact]';
	}
	$form_html = '' !== $form ? do_shortcode( $form ) : '<div class="mm-s-empty">' . mm_icon( 'message' ) . '<p>' . esc_html__( 'Email us and we usually reply within one business day.', 'minemirth' ) . '</p>' . ( mm_opt( 'contact_email' ) ? '<a class="mm-btn mm-btn--primary" href="mailto:' . esc_attr( antispambot( (string) mm_opt( 'contact_email' ) ) ) . '"><span>' . esc_html( antispambot( (string) mm_opt( 'contact_email' ) ) ) . '</span></a>' : '' ) . '</div>';
	return '<div class="mm-container mm-s-contact"><div class="mm-s-contact__side">' . mm_s_head( $d ) . ( $cards ? '<div class="mm-s-cmethods">' . $cards . '</div>' : '' ) . ( ! empty( $d['note'] ) ? '<div class="mm-s__note">' . mm_s_rich( $d['note'] ) . '</div>' : '' ) . '</div><div class="mm-s-contact__form mm-card" data-mm-reveal>' . $form_html . '</div></div>';
}

function mm_s_render_callout( array $d ): string {
	$tone = in_array( $d['tone'] ?? '', array( 'info', 'warning', 'success', 'primary' ), true ) ? $d['tone'] : 'info';
	$icon = (string) ( $d['icon'] ?? array( 'info' => 'info', 'warning' => 'alert', 'success' => 'check-circle', 'primary' => 'sparkles' )[ $tone ] );
	return '<div class="mm-container"><div class="mm-callout mm-callout--' . $tone . '" data-mm-reveal><span class="mm-callout__icon">' . mm_icon( $icon ) . '</span><div class="mm-callout__body">' . ( ! empty( $d['title'] ) ? '<strong>' . mm_s_line( $d['title'] ) . '</strong>' : '' ) . mm_s_rich( $d['text'] ?? '' ) . '</div>' . ( ! empty( $d['button_label'] ) && ! empty( $d['button_url'] ) ? '<a class="mm-btn mm-btn--ghost mm-btn--sm" href="' . esc_url( mm_s_url( $d['button_url'] ) ) . '"><span>' . esc_html( $d['button_label'] ) . '</span>' . mm_icon( 'arrow-right' ) . '</a>' : '' ) . '</div></div>';
}

function mm_s_render_brands( array $d ): string {
	$cat  = function_exists( 'mm_catalog_filter' ) ? mm_catalog_filter( array( 'stock' => 'any' ) ) : array();
	$out  = '';
	foreach ( mm_s_items( $d['items'] ?? array() ) as $i => $b ) {
		$name  = (string) ( $b['name'] ?? '' );
		$count = count( array_filter( $cat, static fn( $r ) => '' !== $name && false !== stripos( $r['brand'] . ' ' . $r['name'], $name ) ) );
		$out  .= '<a class="mm-brand" href="' . esc_url( mm_s_url( $b['url'] ?? '' ) ) . '" data-mm-reveal style="--d:' . ( $i % 4 ) . '"><span class="mm-brand__mark">' . esc_html( mb_strtoupper( mb_substr( $name, 0, 1 ) ) ) . '</span><span class="mm-brand__body"><strong>' . esc_html( $name ) . '</strong><span>' . esc_html( $b['text'] ?? '' ) . '</span></span>' . ( $count ? '<em>' . esc_html( sprintf( /* translators: %s: number */ _n( '%s model', '%s models', $count, 'minemirth' ), number_format_i18n( $count ) ) ) . '</em>' : '' ) . '</a>';
	}
	return '<div class="mm-container">' . mm_s_head( $d ) . '<div class="mm-brandgrid">' . $out . '</div></div>';
}

function mm_s_render_posts( array $d ): string {
	$q = new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => max( 2, min( 9, (int) ( $d['limit'] ?? 3 ) ) ),
			'category_name'       => sanitize_title( (string) ( $d['category'] ?? '' ) ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	if ( ! $q->have_posts() ) {
		return '';
	}
	ob_start();
	$i = 0;
	while ( $q->have_posts() ) {
		$q->the_post();
		get_template_part( 'template-parts/blog/card', null, array( 'index' => $i++ ) );
	}
	wp_reset_postdata();
	$d['link_label'] = $d['link_label'] ?? __( 'All articles', 'minemirth' );
	$d['link_url']   = $d['link_url'] ?? '{blog}';
	return '<div class="mm-container">' . mm_s_head( $d ) . '<div class="mm-post-grid mm-post-grid--3">' . ob_get_clean() . '</div></div>';
}

function mm_s_render_newsletter( array $d ): string {
	ob_start();
	mm_newsletter_form( array( 'context' => (string) ( $d['context'] ?? 'page' ) ) );
	$form = (string) ob_get_clean();
	return '<div class="mm-container"><div class="mm-s-news" data-mm-reveal><div class="mm-s-news__copy"><span class="mm-s-news__icon">' . mm_icon( (string) ( $d['icon'] ?? 'mail' ) ) . '</span>' . mm_s_head( $d ) . '</div><div class="mm-s-news__form">' . $form . ( ! empty( $d['note'] ) ? '<p class="mm-s__note">' . mm_s_line( $d['note'] ) . '</p>' : '' ) . '</div></div></div>';
}

function mm_s_render_badges( array $d ): string {
	$out = '';
	foreach ( mm_s_items( $d['items'] ?? array() ) as $b ) {
		$out .= '<span class="mm-tbadge">' . mm_icon( (string) ( $b['icon'] ?? 'check' ) ) . '<span><b>' . mm_s_line( $b['title'] ?? '' ) . '</b>' . ( ! empty( $b['text'] ) ? '<small>' . mm_s_line( $b['text'] ) . '</small>' : '' ) . '</span></span>';
	}
	$pay = ! empty( $d['payments'] ) && function_exists( 'mm_payment_badges' ) ? mm_payment_badges() : '';
	return '<div class="mm-container"><div class="mm-tbadges" data-mm-reveal>' . $out . '</div>' . ( $pay ? '<div class="mm-s-pay">' . $pay . '</div>' : '' ) . '</div>';
}

function mm_s_render_prose( array $d ): string {
	return '<div class="mm-container mm-container--prose">' . mm_s_head( $d ) . '<div class="mm-prose">' . mm_s_rich( $d['html'] ?? '' ) . '</div></div>';
}

/* ---------------------------------------------------------------------------
 * Block
 * ------------------------------------------------------------------------- */

function mm_s_is_editor(): bool {
	return defined( 'REST_REQUEST' ) && REST_REQUEST && ! empty( $_GET['context'] ) && 'edit' === $_GET['context']; // phpcs:ignore WordPress.Security.NonceVerification
}

/**
 * Render one section. $attrs: variant, data.
 */
function mm_section_render( array $attrs ): string {
	$variant = sanitize_key( (string) ( $attrs['variant'] ?? 'features' ) );
	$d       = is_array( $attrs['data'] ?? null ) ? $attrs['data'] : array();
	$fn      = 'mm_s_render_' . $variant;
	if ( ! function_exists( $fn ) ) {
		return '';
	}
	mm_sections_enqueue();
	$inner = (string) $fn( $d );
	if ( '' === $inner ) {
		return mm_s_is_editor() ? '<div class="mm-s-editor-note">' . esc_html__( 'This section has nothing to show yet (for example, no posts or reviews).', 'minemirth' ) . '</div>' : '';
	}
	$tone = in_array( $d['tone'] ?? '', array( 'alt', 'dark', 'gradient', 'plain' ), true ) ? $d['tone'] : 'plain';
	$pad  = in_array( $d['pad'] ?? '', array( 'sm', 'lg', 'none' ), true ) ? $d['pad'] : 'md';
	$id   = ! empty( $d['anchor'] ) ? ' id="' . esc_attr( sanitize_title( $d['anchor'] ) ) . '"' : '';
	// The animated hero and the ticker bring their own <section>.
	$tag  = in_array( $variant, array( 'fxhero', 'ticker' ), true ) ? 'div' : 'section';
	$deco = 'div' === $tag || mm_s_is_editor() || ! function_exists( 'mm_deco_for_section' ) ? '' : mm_deco_for_section( $variant, $d );
	return '<' . $tag . $id . ' class="mm-s mm-s--' . esc_attr( $variant ) . ' mm-s--tone-' . $tone . ' mm-s--pad-' . $pad . ( 'dark' === $tone ? ' mm-scheme-dark' : '' ) . '">' . $deco . $inner . '</' . $tag . '>';
}

// Link tokens such as href="{tool:profit}" in ordinary blocks (paragraphs, lists) of any page.
add_filter( 'the_content', 'mm_s_links', 8 );

// Live values such as {hashprice} in ordinary blocks. Runs after blocks are rendered, so block
// attributes are never touched.
add_filter(
	'the_content',
	static function ( $content ) {
		$content = (string) $content;
		if ( false === strpos( $content, '{' ) || ! preg_match( '/\{(btc_price|btc_change|hashprice|hashrate|difficulty|adjustment|height|fees|halving_blocks|halving_days|halving_date|miners_count|brands_count)\}/', $content ) ) {
			return $content;
		}
		wp_enqueue_script( 'mm-market' );
		return mm_s_live( $content );
	},
	12
);

function mm_sections_enqueue(): void {
	wp_enqueue_style( 'mm-sections' );
}

add_action(
	'init',
	static function () {
		register_block_type(
			'mm/section',
			array(
				'api_version'     => 3,
				'title'           => __( 'Mine Mirth section', 'minemirth' ),
				'category'        => 'mm-sections',
				'attributes'      => array(
					'variant' => array( 'type' => 'string', 'default' => 'features' ),
					'data'    => array( 'type' => 'object', 'default' => array() ),
				),
				'supports'        => array( 'html' => false, 'customClassName' => false, 'className' => false ),
				'render_callback' => static fn( $attrs ) => mm_section_render( (array) $attrs ),
				'editor_script'   => 'mm-sections-editor',
			)
		);
	}
);

add_filter(
	'block_categories_all',
	static function ( $cats ) {
		array_unshift( $cats, array( 'slug' => 'mm-sections', 'title' => __( 'Mine Mirth sections', 'minemirth' ), 'icon' => null ) );
		return $cats;
	}
);

add_action(
	'init',
	static function () {
		wp_register_style( 'mm-sections', MM_URI . '/assets/css/sections.css', array( 'mm-main' ), MM_VERSION );
		wp_register_script( 'mm-sections-editor', MM_URI . '/assets/js/sections-editor.js', array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n', 'wp-data' ), MM_VERSION, true );
	},
	5
);

add_action(
	'enqueue_block_editor_assets',
	static function () {
		wp_add_inline_script( 'mm-sections-editor', 'window.mmSections=' . wp_json_encode( array( 'schema' => mm_sections_schema(), 'common' => mm_sections_common_fields(), 'icons' => array_keys( mm_icon_library() ), 'tools' => function_exists( 'mm_tools' ) ? wp_list_pluck( mm_tools(), 'title' ) : array() ) ) . ';', 'before' );
	}
);

add_action(
	'enqueue_block_assets',
	static function () {
		if ( is_admin() ) {
			wp_enqueue_style( 'mm-sections-editor-css', MM_URI . '/assets/css/sections.css', array(), MM_VERSION );
			wp_enqueue_style( 'mm-tools-editor-css', MM_URI . '/assets/css/tools.css', array(), MM_VERSION );
		}
	}
);

// Load the section styles in <head> on pages that use them (avoids a flash of unstyled content).
add_action(
	'wp_enqueue_scripts',
	static function () {
		$post = get_queried_object();
		$raw = $post instanceof WP_Post && function_exists( 'mm_page_raw_content' ) ? mm_page_raw_content( (int) $post->ID ) : ( $post instanceof WP_Post ? $post->post_content : '' );
		if ( is_singular() && $post instanceof WP_Post && has_block( 'mm/section', $raw ) ) {
			wp_enqueue_style( 'mm-sections' );
			if ( false !== strpos( $raw, '"variant":"tool"' ) && function_exists( 'mm_tools_enqueue' ) ) {
				wp_enqueue_style( 'mm-tools' );
			}
		}
		if ( is_front_page() || is_page_template( array( 'page-templates/premium.php', 'page-templates/guide.php', 'page-templates/tool.php', 'page-templates/calculator.php' ) ) ) {
			wp_enqueue_style( 'mm-sections' );
		}
	},
	30
);

require_once __DIR__ . '/schema.php';
