<?php
/**
 * Closing call to action.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;
?>
<section id="mm-section-cta" class="mm-section mm-section--cta">
	<div class="mm-container">
		<div class="mm-cta-card mm-scheme-dark" data-mm-reveal>
			<div class="mm-cta-card__fx" aria-hidden="true">
				<span class="mm-cta-card__orbit"></span>
				<span class="mm-cta-card__orbit mm-cta-card__orbit--2"></span>
				<span class="mm-cta-card__orbit mm-cta-card__orbit--3"></span>
			</div>
			<?php mm_section_heading( 'cta', array( 'align' => 'center' ) ); ?>
			<div class="mm-btn-row mm-btn-row--center">
				<?php
				echo mm_button( mm_opt( 'cta_btn1_text' ), mm_opt( 'cta_btn1_url' ), 'primary', array( 'size' => 'lg', 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo mm_button( mm_opt( 'cta_btn2_text' ), mm_opt( 'cta_btn2_url' ), 'glass', array( 'size' => 'lg' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>
		</div>
	</div>
</section>
