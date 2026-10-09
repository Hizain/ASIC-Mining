<?php
/**
 * Product page readability.
 *
 * The product gallery used to be sticky inside a grid that also holds the
 * description tabs, so it slid over the description while scrolling. The CSS
 * fix lives in assets/css/mmc.css (and in Mine Mirth theme 4.0.1+). This file
 * also opens the description tab by default and shows the brand in the summary.
 *
 * @package MineMirthCommerce
 */

defined( 'ABSPATH' ) || exit;

// Make sure the description tab is first so it is what people see.
add_filter(
	'woocommerce_product_tabs',
	static function ( $tabs ) {
		if ( isset( $tabs['description'] ) ) {
			$tabs['description']['priority'] = 1;
		}
		return $tabs;
	},
	99
);

// Brand line above the product title on the product page.
add_action(
	'woocommerce_single_product_summary',
	static function () {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$brands = mmc_product_brands( $product->get_id() );
		if ( ! $brands ) {
			return;
		}
		echo '<div class="mmc-brand-line">';
		foreach ( $brands as $term ) {
			$link = get_term_link( $term );
			echo is_wp_error( $link ) ? '<span>' . esc_html( $term->name ) . '</span>' : '<a href="' . esc_url( $link ) . '">' . esc_html( $term->name ) . '</a>';
		}
		echo '</div>';
	},
	4
);
