<?php
/**
 * Discovery helpers: /llms.txt site summary and sitemap hint in robots.txt.
 *
 * llms.txt is an optional convention for AI assistants; it is not a search
 * ranking factor.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

function mm_maybe_serve_llms_txt(): void {
	if ( ! mm_opt( 'seo_llms' ) ) {
		return;
	}
	$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', PHP_URL_PATH );
	$home = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( rtrim( (string) $home, '/' ) . '/llms.txt' !== $path ) {
		return;
	}

	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Cache-Control: public, max-age=3600' );

	$lines   = array();
	$lines[] = '# ' . get_bloginfo( 'name' );
	$lines[] = '';
	if ( get_bloginfo( 'description' ) ) {
		$lines[] = '> ' . get_bloginfo( 'description' );
		$lines[] = '';
	}
	$lines[] = wp_strip_all_tags( (string) mm_opt( 'footer_about' ) );
	$lines[] = '';
	$lines[] = '## Core pages';
	$lines[] = '- [Home](' . home_url( '/' ) . ')';
	$lines[] = '- [ASIC miner shop](' . mm_shop_url() . ')';
	$lines[] = '- [Miner hosting](' . home_url( '/miner-hosting/' ) . ')';
	$lines[] = '- [Mining calculator](' . mm_url( (string) mm_opt( 'calc_btn_url' ) ) . ')';
	$lines[] = '- [Guides](' . mm_blog_url() . ')';
	$lines[] = '- [Contact](' . mm_url( (string) mm_opt( 'contact_url' ) ) . ')';

	if ( taxonomy_exists( 'product_cat' ) ) {
		$cats = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'number'     => 12,
			)
		);
		if ( ! is_wp_error( $cats ) && $cats ) {
			$lines[] = '';
			$lines[] = '## Product categories';
			foreach ( $cats as $cat ) {
				$lines[] = '- [' . $cat->name . '](' . get_term_link( $cat ) . ')';
			}
		}
	}

	$posts = get_posts(
		array(
			'numberposts' => 15,
			'post_status' => 'publish',
		)
	);
	if ( $posts ) {
		$lines[] = '';
		$lines[] = '## Recent guides';
		foreach ( $posts as $post ) {
			$lines[] = '- [' . wp_strip_all_tags( get_the_title( $post ) ) . '](' . get_permalink( $post ) . ')';
		}
	}

	$lines[] = '';
	$lines[] = '## Notes';
	$lines[] = '- Product availability and hosting capacity are time-sensitive.';
	$lines[] = '- Calculator outputs are estimates, not guarantees.';
	$lines[] = '- Live market values are timestamped and sourced from CoinGecko and mempool.space.';

	echo implode( "\n", $lines ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- plain text response.
	exit;
}
add_action( 'template_redirect', 'mm_maybe_serve_llms_txt', 1 );

function mm_robots_txt( string $output, bool $public ): string {
	if ( ! $public || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return $output;
	}
	$line = 'Sitemap: ' . home_url( '/wp-sitemap.xml' );
	if ( false === strpos( $output, 'Sitemap:' ) ) {
		$output = rtrim( $output ) . "\n" . $line . "\n";
	}
	return $output;
}
add_filter( 'robots_txt', 'mm_robots_txt', 10, 2 );
