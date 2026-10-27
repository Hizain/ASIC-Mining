<?php
/**
 * Blog helpers: heading anchors, table of contents, related posts, sharing.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/**
 * Give h2/h3 headings stable ids so the table of contents can link to them.
 */
function mm_heading_ids( string $content ): string {
	if ( ! is_singular() || false === stripos( $content, '<h' ) ) {
		return $content;
	}
	$used = array();
	return (string) preg_replace_callback(
		'#<h([23])((?:\s[^>]*)?)>(.*?)</h\1>#is',
		static function ( $m ) use ( &$used ) {
			if ( preg_match( '/\sid\s*=\s*["\']([^"\']+)["\']/i', $m[2], $id ) ) {
				$used[ $id[1] ] = true;
				return $m[0];
			}
			$base = sanitize_title( wp_strip_all_tags( $m[3] ) );
			$base = $base ? $base : 'section';
			$slug = $base;
			$i    = 2;
			while ( isset( $used[ $slug ] ) ) {
				$slug = $base . '-' . $i++;
			}
			$used[ $slug ] = true;
			return '<h' . $m[1] . $m[2] . ' id="' . esc_attr( $slug ) . '">' . $m[3] . '</h' . $m[1] . '>';
		},
		$content
	);
}
add_filter( 'the_content', 'mm_heading_ids', 25 );

/**
 * Table-of-contents items from rendered content: [ [level, id, text], ... ].
 */
function mm_toc_items( string $content ): array {
	if ( ! preg_match_all( '#<h([23])[^>]*\sid=["\']([^"\']+)["\'][^>]*>(.*?)</h\1>#is', $content, $m, PREG_SET_ORDER ) ) {
		return array();
	}
	$items = array();
	foreach ( $m as $h ) {
		$text = trim( wp_strip_all_tags( $h[3] ) );
		if ( '' !== $text ) {
			$items[] = array( (int) $h[1], $h[2], $text );
		}
	}
	return $items;
}

/**
 * Print a table of contents.
 *
 * @param array  $items   From mm_toc_items().
 * @param string $variant sidebar|inline.
 */
function mm_toc( array $items, string $variant = 'sidebar' ): void {
	if ( ! $items ) {
		return;
	}
	ob_start();
	echo '<ol>';
	foreach ( $items as $item ) {
		printf( '<li class="is-h%1$d"><a href="#%2$s" data-mm-toc-link>%3$s</a></li>', (int) $item[0], esc_attr( $item[1] ), esc_html( $item[2] ) );
	}
	echo '</ol>';
	$list = (string) ob_get_clean();

	if ( 'inline' === $variant ) {
		echo '<details class="mm-toc mm-toc--inline"><summary>' . esc_html__( 'In this article', 'minemirth' ) . mm_icon( 'chevron-down' ) . '</summary>' . $list . '</details>'; // phpcs:ignore WordPress.Security.EscapeOutput
		return;
	}
	echo '<nav class="mm-toc" aria-label="' . esc_attr__( 'Table of contents', 'minemirth' ) . '" data-mm-toc><span class="mm-toc__title">' . mm_icon( 'list' ) . esc_html__( 'On this page', 'minemirth' ) . '</span>' . $list . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Related posts query.
 */
function mm_related_posts( int $post_id, int $count ): WP_Query {
	$cats = wp_get_post_categories( $post_id );
	$args = array(
		'post_type'           => 'post',
		'posts_per_page'      => $count,
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $cats ) {
		$args['category__in'] = $cats;
	}
	$query = new WP_Query( $args );
	if ( $query->post_count < $count ) {
		unset( $args['category__in'] );
		$query = new WP_Query( $args );
	}
	return $query;
}

/**
 * Share buttons.
 */
function mm_share_buttons(): void {
	$url   = rawurlencode( get_permalink() );
	$title = rawurlencode( wp_strip_all_tags( get_the_title() ) );
	$links = array(
		'x-social' => array( 'X', 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title ),
		'linkedin' => array( 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url ),
		'facebook' => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $url ),
		'telegram' => array( 'Telegram', 'https://t.me/share/url?url=' . $url . '&text=' . $title ),
		'reddit'   => array( 'Reddit', 'https://www.reddit.com/submit?url=' . $url . '&title=' . $title ),
	);
	echo '<div class="mm-share"><span class="mm-share__label">' . esc_html__( 'Share', 'minemirth' ) . '</span>';
	foreach ( $links as $icon => $link ) {
		printf(
			'<a href="%1$s" target="_blank" rel="noopener nofollow" aria-label="%2$s">%3$s</a>',
			esc_url( $link[1] ),
			/* translators: %s: network */
			esc_attr( sprintf( __( 'Share on %s', 'minemirth' ), $link[0] ) ),
			mm_icon( $icon ) // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}
	printf(
		'<button type="button" data-mm-copy="%1$s" aria-label="%2$s">%3$s</button>',
		esc_url( get_permalink() ),
		esc_attr__( 'Copy link', 'minemirth' ),
		mm_icon( 'link' ) // phpcs:ignore WordPress.Security.EscapeOutput
	);
	echo '</div>';
}
