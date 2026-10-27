<?php
/**
 * Compact header for cart, checkout and account pages.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_step = '';
if ( is_cart() ) {
	$mm_step = 'cart';
} elseif ( is_order_received_page() ) {
	$mm_step = 'done';
} elseif ( is_checkout() ) {
	$mm_step = 'details';
}

$mm_title = get_the_title();
if ( is_account_page() && ! is_user_logged_in() ) {
	$mm_title = __( 'Sign in or create an account', 'minemirth' );
} elseif ( is_order_received_page() ) {
	$mm_title = __( 'Order received', 'minemirth' );
} elseif ( is_account_page() ) {
	$endpoint = WC()->query->get_current_endpoint();
	if ( $endpoint ) {
		$mm_title = WC()->query->get_endpoint_title( $endpoint );
	}
}

// Checkout in focus mode already shows the steps in its header.
$mm_show_steps = $mm_step && mm_opt( 'checkout_steps' ) && ! ( 'details' === $mm_step && mm_is_checkout_focus() );
?>
<header class="mm-store-head<?php echo is_account_page() && ! is_user_logged_in() ? ' is-auth' : ''; ?>">
	<?php echo ! $mm_step && function_exists( 'mm_deco' ) ? mm_deco( 'blocks' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php if ( 'details' !== $mm_step || ! mm_is_checkout_focus() ) : ?>
		<h1 class="mm-store-head__title"><?php echo esc_html( $mm_title ); ?></h1>
	<?php else : ?>
		<h1 class="screen-reader-text"><?php echo esc_html( $mm_title ); ?></h1>
	<?php endif; ?>
	<?php if ( $mm_show_steps ) : ?>
		<?php get_template_part( 'template-parts/checkout/steps', null, array( 'current' => $mm_step ) ); ?>
	<?php endif; ?>
</header>
