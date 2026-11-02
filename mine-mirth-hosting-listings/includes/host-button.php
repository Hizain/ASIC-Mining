<?php
/**
 * "Host this miner" on product pages: choose where it goes (for all products, or per product),
 * its text, and whether it opens in a new tab.
 *
 * The theme prints the box with a fixed link to /miner-hosting/. Its output is captured and only
 * that link is changed, so everything else in the box stays exactly as the theme draws it.
 *
 * @package MineMirthHostingListings
 */

defined( 'ABSPATH' ) || exit;

/**
 * Where "Host this miner" goes for a product. Empty string = hide the box.
 *
 * @return array{url:string,new_tab:bool}
 */
function mmhl_host_target( int $product_id ): array {
	$new_tab = (bool) mmhl_opt( 'host_new_tab' );
	$mode    = (string) get_post_meta( $product_id, '_mmhl_host', true );
	if ( 'hide' === $mode ) {
		return array( 'url' => '', 'new_tab' => $new_tab );
	}
	if ( 'listing' === $mode ) {
		$id = absint( get_post_meta( $product_id, '_mmhl_host_listing', true ) );
		if ( $id && 'publish' === get_post_status( $id ) ) {
			return array( 'url' => (string) get_permalink( $id ), 'new_tab' => $new_tab );
		}
	}
	if ( 'custom' === $mode ) {
		$url = mmhl_url( (string) get_post_meta( $product_id, '_mmhl_host_url', true ) );
		if ( '' !== $url ) {
			return array( 'url' => $url, 'new_tab' => $new_tab );
		}
	}
	// A listing that names this miner as its "Miner product".
	if ( mmhl_opt( 'host_auto' ) ) {
		$ids = get_posts( array( 'post_type' => MMHL_TYPE, 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_mmhl_product', 'meta_value' => $product_id, 'orderby' => 'menu_order', 'order' => 'ASC' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $ids ) {
			return array( 'url' => (string) get_permalink( $ids[0] ), 'new_tab' => $new_tab );
		}
	}
	switch ( (string) mmhl_opt( 'host_mode' ) ) {
		case 'listing':
			$id = absint( mmhl_opt( 'host_listing' ) );
			if ( $id && 'publish' === get_post_status( $id ) ) {
				return array( 'url' => (string) get_permalink( $id ), 'new_tab' => $new_tab );
			}
			break;
		case 'custom':
			$url = mmhl_url( (string) mmhl_opt( 'host_url' ) );
			if ( '' !== $url ) {
				return array( 'url' => $url, 'new_tab' => $new_tab );
			}
			break;
	}
	return array( 'url' => mmhl_page_url(), 'new_tab' => $new_tab );
}

// Hooked while this file loads, so it runs just before the theme's box (same priority, added earlier).
add_action(
	'woocommerce_single_product_summary',
	static function (): void {
		ob_start();
	},
	36
);

add_action(
	'woocommerce_single_product_summary',
	static function (): void {
		$html = (string) ob_get_clean();
		global $product;
		if ( $product instanceof WC_Product && false !== strpos( $html, 'mm-cta-box' ) ) {
			$html = mmhl_rewrite_host_box( $html, (int) $product->get_id() );
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- the theme's own markup, with one link changed.
	},
	37
);

function mmhl_rewrite_host_box( string $html, int $product_id ): string {
	$theme_href = esc_url( home_url( '/miner-hosting/' ) );
	$pattern    = '#<a class="mm-cta-box" href="' . preg_quote( $theme_href, '#' ) . '">(.*?)</a>#s';
	return (string) preg_replace_callback(
		$pattern,
		static function ( array $m ) use ( $product_id ): string {
			$target = mmhl_host_target( $product_id );
			if ( '' === $target['url'] ) {
				return '';
			}
			$inner = $m[1];
			$label = trim( (string) mmhl_opt( 'host_label' ) );
			$text  = trim( (string) mmhl_opt( 'host_text' ) );
			if ( '' !== $label ) {
				$inner = (string) preg_replace( '#<strong>.*?</strong>#s', '<strong>' . esc_html( $label ) . '</strong>', $inner, 1 );
			}
			if ( '' !== $text ) {
				$inner = (string) preg_replace( '#<small>.*?</small>#s', '<small>' . esc_html( $text ) . '</small>', $inner, 1 );
			}
			return '<a class="mm-cta-box mmhl-host" href="' . esc_url( $target['url'] ) . '"' . ( $target['new_tab'] ? ' target="_blank" rel="noopener"' : '' ) . '>' . $inner . '</a>';
		},
		$html,
		1
	);
}
