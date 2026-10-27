<?php
/**
 * Footer call-to-action band.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="mm-footer-cta" aria-label="<?php esc_attr_e( 'Next steps', 'minemirth' ); ?>">
	<div class="mm-container">
		<div class="mm-footer-cta__card mm-scheme-dark" data-mm-reveal>
			<div class="mm-footer-cta__fx" aria-hidden="true"></div>
			<?php echo function_exists( 'mm_deco' ) ? mm_deco( 'coins' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="mm-footer-cta__copy">
				<h2><?php echo mm_highlight( mm_opt( 'footer_cta_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
				<?php if ( mm_opt( 'footer_cta_text' ) ) : ?>
					<p><?php echo wp_kses_post( (string) mm_opt( 'footer_cta_text' ) ); ?></p>
				<?php endif; ?>
			</div>
			<div class="mm-btn-row">
				<?php
				echo mm_button( mm_opt( 'footer_cta_btn1_text' ), mm_opt( 'footer_cta_btn1_url' ), 'primary', array( 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo mm_button( mm_opt( 'footer_cta_btn2_text' ), mm_opt( 'footer_cta_btn2_url' ), 'glass' ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>
		</div>
	</div>
</section>
