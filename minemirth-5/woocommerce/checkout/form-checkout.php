<?php
/**
 * Checkout Form — Mine Mirth layout.
 *
 * Two columns: customer details (+ payment, optional) on the left and a
 * sticky order summary on the right. Every WooCommerce hook, id and class
 * the checkout script relies on is preserved.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 *
 * @var WC_Checkout $checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mm_payment_left = (bool) mm_opt( 'checkout_payment_left' );
?>
<div class="mm-checkout">
	<?php do_action( 'woocommerce_before_checkout_form', $checkout ); ?>

	<?php
	// If checkout registration is disabled and not logged in, the user cannot checkout.
	if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
		echo '<div class="mm-checkout__login">' . esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) ) . '</div></div>';
		return;
	}
	?>

	<form name="checkout" method="post" class="checkout woocommerce-checkout mm-checkout__form<?php echo $mm_payment_left ? ' mm-checkout--pay-left' : ''; ?>" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">

		<div class="mm-checkout__main">
			<?php if ( $checkout->get_checkout_fields() ) : ?>

				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

				<div class="col2-set mm-checkout__details" id="customer_details">
					<div class="col-1 mm-checkout__card">
						<?php do_action( 'woocommerce_checkout_billing' ); ?>
					</div>

					<div class="col-2 mm-checkout__card mm-checkout__card--shipping">
						<?php do_action( 'woocommerce_checkout_shipping' ); ?>
					</div>
				</div>

				<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

			<?php endif; ?>

			<?php if ( $mm_payment_left ) : ?>
				<section class="mm-checkout__card mm-checkout__payment" data-mm-checkout-payment>
					<h3 class="mm-checkout__heading"><?php esc_html_e( 'Payment', 'minemirth' ); ?></h3>
					<?php do_action( 'mm_checkout_payment' ); ?>
				</section>
			<?php endif; ?>
		</div>

		<aside class="mm-checkout__summary">
			<div class="mm-checkout__summary-inner">
				<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

				<h3 id="order_review_heading" class="mm-checkout__summary-title">
					<?php esc_html_e( 'Your order', 'woocommerce' ); ?>
					<span class="mm-checkout__count"><?php echo esc_html( sprintf( /* translators: %d: item count */ _n( '%d item', '%d items', WC()->cart->get_cart_contents_count(), 'minemirth' ), WC()->cart->get_cart_contents_count() ) ); ?></span>
				</h3>

				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>

				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

				<div class="mm-checkout__trust">
					<?php mm_checkout_trust_points(); ?>
				</div>

				<?php if ( mm_opt( 'checkout_help_text' ) && ( mm_opt( 'contact_email' ) || mm_opt( 'contact_phone' ) ) ) : ?>
					<p class="mm-checkout__help">
						<?php echo mm_icon( 'headset' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span>
							<?php echo esc_html( (string) mm_opt( 'checkout_help_text' ) ); ?>
							<?php if ( mm_opt( 'contact_phone' ) ) : ?>
								<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', (string) mm_opt( 'contact_phone' ) ) ); ?>"><?php echo esc_html( (string) mm_opt( 'contact_phone' ) ); ?></a>
							<?php else : ?>
								<a href="mailto:<?php echo esc_attr( antispambot( (string) mm_opt( 'contact_email' ) ) ); ?>"><?php echo esc_html( antispambot( (string) mm_opt( 'contact_email' ) ) ); ?></a>
							<?php endif; ?>
						</span>
					</p>
				<?php endif; ?>
			</div>
		</aside>

	</form>

	<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
</div>
