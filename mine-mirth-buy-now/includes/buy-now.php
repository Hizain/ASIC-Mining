<?php
/**
 * "Buy it now": adds the product (with the chosen quantity and options) and goes straight to checkout.
 *
 * @package MineMirthBuyNow
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! is_product() ) {
			return;
		}
		wp_enqueue_style( 'mmbn', MMBN_URI . 'assets/front.css', array(), MMBN_VERSION );
		wp_enqueue_script( 'mmbn', MMBN_URI . 'assets/front.js', array(), MMBN_VERSION, true );
		wp_localize_script(
			'mmbn',
			'mmbnCfg',
			array(
				'endpoint' => esc_url_raw( rest_url( 'mm-price-alerts/v1/watch' ) ),
				'error'    => __( 'Something went wrong. Please try again.', 'mine-mirth-buy-now' ),
				'sending'  => __( 'Saving…', 'mine-mirth-buy-now' ),
			)
		);
	},
	20
);

// Keep the small front-end script out of "delay JS" optimisations (LiteSpeed, WP Rocket, Cloudflare),
// so the price-alert box animates and responds on first view.
add_filter(
	'script_loader_tag',
	static function ( $tag, $handle ) {
		return 'mmbn' === $handle ? str_replace( ' src=', ' data-no-optimize="1" data-no-defer="1" data-cfasync="false" nowprocket src=', $tag ) : $tag;
	},
	10,
	2
);

/**
 * The button sits inside the product form, right under "Add to cart", so it uses the same quantity and options.
 */
add_action(
	'woocommerce_after_add_to_cart_button',
	static function () {
		global $product;
		if ( ! mmbn_opt( 'buy_now' ) || ! $product instanceof WC_Product || $product->is_type( 'external' ) || ! $product->is_purchasable() ) {
			return;
		}
		$label = $product->is_on_backorder() ? (string) mmbn_opt( 'pre_label' ) : (string) mmbn_opt( 'buy_label' );
		$style = in_array( mmbn_opt( 'buy_style' ), array( 'contrast', 'outline', 'primary' ), true ) ? mmbn_opt( 'buy_style' ) : 'contrast';
		printf(
			'<button type="submit" name="mmbn_buy_now" value="%1$d" class="mm-btn mm-btn--lg mmbn-buy mmbn-buy--%2$s%3$s" data-mmbn-buy>%4$s<span>%5$s</span></button>',
			(int) $product->get_id(),
			esc_attr( $style ),
			'outline' === $style ? ' mm-btn--outline' : ( 'primary' === $style ? ' mm-btn--primary' : '' ),
			mmbn_icon( 'bolt' ), // phpcs:ignore WordPress.Security.EscapeOutput
			esc_html( $label )
		);
	}
);

/**
 * Simple products send their ID through the "Add to cart" button's name, which isn't submitted when
 * "Buy it now" is clicked, so pass it along before WooCommerce handles the form (priority 20).
 */
add_action(
	'wp_loaded',
	static function () {
		if ( empty( $_REQUEST['mmbn_buy_now'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$id = absint( wp_unslash( $_REQUEST['mmbn_buy_now'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_REQUEST['add-to-cart'] ) && $id ) { // phpcs:ignore WordPress.Security.NonceVerification
			$_REQUEST['add-to-cart'] = $id;
		}
		// Optional: check out this product on its own.
		if ( mmbn_opt( 'buy_solo' ) && function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
			WC()->cart->empty_cart();
		}
	},
	19
);

add_filter(
	'woocommerce_add_to_cart_redirect',
	static function ( $url ) {
		if ( empty( $_REQUEST['mmbn_buy_now'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $url;
		}
		// Skip the "added to your cart" message: they're going straight to checkout.
		if ( function_exists( 'wc_get_notices' ) ) {
			$notices = wc_get_notices();
			unset( $notices['success'] );
			wc_set_notices( $notices );
		}
		return wc_get_checkout_url();
	},
	99
);

/**
 * Link that adds a simple product and opens checkout (used in alert emails).
 */
function mmbn_buy_url( WC_Product $product ): string {
	if ( ! $product->is_type( 'simple' ) || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return (string) get_permalink( $product->get_id() );
	}
	return add_query_arg( array( 'add-to-cart' => $product->get_id(), 'mmbn_buy_now' => $product->get_id() ), get_permalink( $product->get_id() ) );
}
