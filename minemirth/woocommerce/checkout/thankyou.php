<?php
/**
 * Thankyou page — Mine Mirth layout.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-order mm-thankyou">
	<?php
	if ( $order ) :

		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<div class="mm-thankyou__hero is-failed mm-card">
				<span class="mm-thankyou__icon" aria-hidden="true"><?php echo mm_icon( 'alert' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>
				<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions mm-btn-row">
					<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
					<?php if ( is_user_logged_in() ) : ?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
					<?php endif; ?>
				</p>
			</div>

		<?php else : ?>

			<div class="mm-thankyou__hero mm-card" <?php echo mm_opt( 'thanks_confetti' ) ? 'data-mm-confetti' : ''; ?>>
				<span class="mm-thankyou__icon is-success" aria-hidden="true">
					<svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="24"/><path d="m15 27 7 7 15-16"/></svg>
				</span>
				<h2 class="mm-thankyou__title"><?php echo esc_html( (string) mm_opt( 'thanks_title' ) ); ?></h2>
				<?php wc_get_template( 'checkout/order-received.php', array( 'order' => $order ) ); ?>
				<?php if ( mm_opt( 'thanks_text' ) ) : ?>
					<p class="mm-thankyou__text"><?php echo esc_html( (string) mm_opt( 'thanks_text' ) ); ?></p>
				<?php endif; ?>

				<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details mm-order-overview">
					<li class="woocommerce-order-overview__order order">
						<?php esc_html_e( 'Order number:', 'woocommerce' ); ?>
						<strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>
					<li class="woocommerce-order-overview__date date">
						<?php esc_html_e( 'Date:', 'woocommerce' ); ?>
						<strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>
					<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
						<li class="woocommerce-order-overview__email email">
							<?php esc_html_e( 'Email:', 'woocommerce' ); ?>
							<strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
						</li>
					<?php endif; ?>
					<li class="woocommerce-order-overview__total total">
						<?php esc_html_e( 'Total:', 'woocommerce' ); ?>
						<strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>
					<?php if ( $order->get_payment_method_title() ) : ?>
						<li class="woocommerce-order-overview__payment-method method">
							<?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
							<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
						</li>
					<?php endif; ?>
				</ul>
			</div>

			<?php
			$mm_steps = array();
			for ( $mm_i = 1; $mm_i <= 3; $mm_i++ ) {
				$mm_t = (string) mm_opt( "next_{$mm_i}_title" );
				if ( '' !== $mm_t ) {
					$mm_steps[] = array( $mm_t, (string) mm_opt( "next_{$mm_i}_text" ) );
				}
			}
			?>
			<?php if ( $mm_steps ) : ?>
				<section class="mm-thankyou__next" aria-label="<?php esc_attr_e( 'What happens next', 'minemirth' ); ?>">
					<h3><?php esc_html_e( 'What happens next', 'minemirth' ); ?></h3>
					<ol class="mm-timeline">
						<?php foreach ( $mm_steps as $mm_index => $mm_step ) : ?>
							<li class="<?php echo 0 === $mm_index ? 'is-current' : ''; ?>">
								<span class="mm-timeline__dot" aria-hidden="true"><?php echo esc_html( (string) ( $mm_index + 1 ) ); ?></span>
								<strong><?php echo esc_html( $mm_step[0] ); ?></strong>
								<p><?php echo esc_html( $mm_step[1] ); ?></p>
							</li>
						<?php endforeach; ?>
					</ol>
					<div class="mm-btn-row">
						<?php
						if ( is_user_logged_in() ) {
							echo mm_button( __( 'Track this order', 'minemirth' ), $order->get_view_order_url(), 'primary', array( 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						}
						echo mm_button( __( 'Continue shopping', 'minemirth' ), '{shop}', 'ghost' ); // phpcs:ignore WordPress.Security.EscapeOutput
						echo mm_button( __( 'Explore hosting', 'minemirth' ), '/miner-hosting/', 'ghost' ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</div>
				</section>
			<?php endif; ?>

		<?php endif; ?>

		<div class="mm-thankyou__details">
			<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
			<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
		</div>

	<?php else : ?>

		<div class="mm-thankyou__hero mm-card">
			<span class="mm-thankyou__icon is-success" aria-hidden="true">
				<svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="24"/><path d="m15 27 7 7 15-16"/></svg>
			</span>
			<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>
		</div>

	<?php endif; ?>
</div>
