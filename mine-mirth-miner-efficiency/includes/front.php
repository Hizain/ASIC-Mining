<?php
/**
 * Show the corrected efficiency in the shop.
 *
 * 1. Product cards and the spec boxes on product pages read the product's "Efficiency" attribute and
 *    only calculate their own (always in J/TH) when there is none. On the storefront this plugin gives
 *    each switched-on product an "Efficiency" attribute with the right value, so those places use it.
 *    It is added only when showing products, never saved: your products are not changed.
 * 2. A few theme blocks (promotion boxes, product sections) print their own J/TH figure directly;
 *    those figures are swapped for the right ones on the page.
 *
 * @package MineMirthMinerEfficiency
 */

defined( 'ABSPATH' ) || exit;

function mmeff_front(): bool {
	return ! is_admin() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! wp_doing_cron();
}

/**
 * Storefront only, and only in "view" context: WooCommerce saves products with the "edit" context,
 * which skips this filter, so the attribute can never end up stored on the product.
 */
add_filter(
	'woocommerce_product_get_attributes',
	static function ( $attributes, $product ) {
		if ( ! empty( $GLOBALS['mmeff_reading'] ) || ! mmeff_front() || ! $product instanceof WC_Product || ! is_array( $attributes ) ) {
			return $attributes;
		}
		if ( ! mmeff_is_on( (int) $product->get_id() ) ) {
			return $attributes;
		}
		// Shop cards have room for three specs. Without an efficiency value the theme shows the
		// algorithm there; keep it that way unless "Show efficiency on shop cards" is on.
		if ( doing_action( 'woocommerce_after_shop_loop_item_title' ) && ! mmeff_settings()['cards'] && ! isset( $attributes['efficiency'] ) && ! isset( $attributes['pa_efficiency'] ) ) {
			return $attributes;
		}
		$info = mmeff_info( $product );
		if ( '' === $info['text'] ) {
			return $attributes;
		}
		$visible  = (bool) mmeff_settings()['table'];
		$position = 99;
		foreach ( array( 'efficiency', 'pa_efficiency' ) as $key ) {
			if ( isset( $attributes[ $key ] ) && $attributes[ $key ] instanceof WC_Product_Attribute ) {
				$visible  = $visible || $attributes[ $key ]->get_visible();
				$position = $attributes[ $key ]->get_position();
				unset( $attributes[ $key ] );
			}
		}
		$a = new WC_Product_Attribute();
		$a->set_id( 0 );
		$a->set_name( __( 'Efficiency', 'mine-mirth-miner-efficiency' ) );
		$a->set_options( array( $info['text'] ) );
		$a->set_position( $position );
		$a->set_visible( $visible );
		$a->set_variation( false );
		$attributes['efficiency'] = $a;
		return $attributes;
	},
	20,
	2
);

/**
 * "Wrong figure => right figure" for every switched-on product whose figures differ.
 * The wrong figure is the theme's own: watts ÷ terahashes, one decimal, "J/TH".
 */
function mmeff_map(): array {
	$map = get_transient( 'mmeff_map' );
	if ( is_array( $map ) ) {
		return $map;
	}
	$map = array();
	$ids = wc_get_products( array( 'limit' => -1, 'status' => 'publish', 'return' => 'ids' ) );
	foreach ( $ids as $id ) {
		$p = wc_get_product( $id );
		if ( ! $p || ! mmeff_is_on( (int) $id ) ) {
			continue;
		}
		$info  = mmeff_info( $p );
		$theme = $info['theme'];
		if ( '' !== $theme && '' !== $info['text'] && $theme !== $info['text'] ) {
			$map[ $theme ] = $info['text'];
		}
	}
	// A figure that is correct for one miner must never be "fixed" for another.
	foreach ( array_keys( $map ) as $wrong ) {
		if ( in_array( $wrong, $map, true ) ) {
			unset( $map[ $wrong ] );
		}
	}
	set_transient( 'mmeff_map', $map, DAY_IN_SECONDS );
	return $map;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! mmeff_settings()['enabled'] || ! mmeff_front() || is_feed() || ! function_exists( 'wc_get_products' ) ) {
			return;
		}
		$map = mmeff_map();
		if ( ! $map ) {
			return;
		}
		ob_start(
			static function ( string $html ) use ( $map ): string {
				// Only whole figures: "210,000.0 J/TH" but not part of "1,210,000.0 J/TH".
				foreach ( $map as $wrong => $right ) {
					$html = (string) preg_replace( '/(?<![\d.,])' . preg_quote( $wrong, '/' ) . '/u', str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $right ), $html );
				}
				return $html;
			}
		);
	},
	1
);
