<?php
/**
 * Distraction-free checkout header.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_phone = (string) mm_opt( 'contact_phone' );
$mm_email = (string) mm_opt( 'contact_email' );
?>
<header class="mm-masthead mm-masthead--checkout">
	<div class="mm-container mm-masthead__inner">
		<a class="mm-checkout-back" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
			<?php echo mm_icon( 'arrow-left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span><?php esc_html_e( 'Back to cart', 'minemirth' ); ?></span>
		</a>
		<a class="mm-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<?php echo mm_logo( 'header' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</a>
		<div class="mm-actions">
			<?php if ( $mm_phone || $mm_email ) : ?>
				<a class="mm-checkout-help mm-hide-md" href="<?php echo esc_url( $mm_phone ? 'tel:' . preg_replace( '/[^0-9+]/', '', $mm_phone ) : 'mailto:' . $mm_email ); ?>" title="<?php echo esc_attr( (string) mm_opt( 'checkout_help_text' ) ); ?>">
					<?php echo mm_icon( 'headset' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php echo esc_html( $mm_phone ? $mm_phone : $mm_email ); ?></span>
				</a>
			<?php endif; ?>
			<span class="mm-secure-badge">
				<?php echo mm_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span><?php echo esc_html( (string) mm_opt( 'checkout_secure_text' ) ); ?></span>
			</span>
			<?php if ( mm_opt( 'mode_toggle' ) ) : ?>
				<button class="mm-icon-btn mm-mode-toggle" type="button" data-mm-mode-toggle aria-pressed="false" aria-label="<?php esc_attr_e( 'Switch to dark mode', 'minemirth' ); ?>">
					<span class="mm-mode-toggle__icons" aria-hidden="true">
						<?php echo mm_icon( 'sun', 'mm-mode-toggle__sun' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo mm_icon( 'moon', 'mm-mode-toggle__moon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</span>
				</button>
			<?php endif; ?>
		</div>
	</div>
	<?php if ( mm_opt( 'checkout_steps' ) ) : ?>
		<div class="mm-container">
			<?php get_template_part( 'template-parts/checkout/steps', null, array( 'current' => 'details' ) ); ?>
		</div>
	<?php endif; ?>
</header>
