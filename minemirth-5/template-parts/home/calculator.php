<?php
/**
 * Calculator section.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;
?>
<section id="mm-section-calculator" class="mm-section mm-section--calculator mm-section--alt">
	<div class="mm-container">
		<div class="mm-split mm-split--calc">
			<div class="mm-split__copy">
				<?php mm_section_heading( 'calc' ); ?>
				<ul class="mm-mini-stats" data-mm-reveal>
					<li><?php echo mm_icon( 'activity' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Live price & difficulty', 'minemirth' ); ?></span></li>
					<li><?php echo mm_icon( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Your power cost', 'minemirth' ); ?></span></li>
					<li><?php echo mm_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Payback estimate', 'minemirth' ); ?></span></li>
				</ul>
				<?php if ( mm_opt( 'calc_btn_text' ) ) : ?>
					<div class="mm-btn-row" data-mm-reveal>
						<?php echo mm_button( mm_opt( 'calc_btn_text' ), mm_opt( 'calc_btn_url' ), 'ghost', array( 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="mm-split__visual" data-mm-reveal>
				<?php echo mm_calculator_html( array( 'variant' => 'compact' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</div>
	</div>
</section>
