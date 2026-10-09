<?php
/**
 * FAQ accordion (+ FAQPage structured data).
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_faqs = array();
for ( $mm_i = 1; $mm_i <= 8; $mm_i++ ) {
	$mm_q = trim( (string) mm_opt( "faq_{$mm_i}_q" ) );
	$mm_a = trim( (string) mm_opt( "faq_{$mm_i}_a" ) );
	if ( '' !== $mm_q && '' !== $mm_a ) {
		$mm_faqs[] = array( $mm_q, $mm_a );
	}
}
if ( ! $mm_faqs ) {
	return;
}
mm_schema_add_faq( $mm_faqs );
?>
<section id="mm-section-faq" class="mm-section mm-section--faq mm-section--alt">
	<div class="mm-container mm-faq">
		<div class="mm-faq__intro">
			<?php mm_section_heading( 'faq' ); ?>
			<?php if ( mm_opt( 'faq_btn_text' ) ) : ?>
				<div class="mm-btn-row" data-mm-reveal>
					<?php echo mm_button( mm_opt( 'faq_btn_text' ), mm_opt( 'faq_btn_url' ), 'ghost', array( 'icon' => 'message' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="mm-accordion" data-mm-accordion>
			<?php foreach ( $mm_faqs as $mm_index => $mm_faq ) : ?>
				<details class="mm-accordion__item" data-mm-reveal style="--d:<?php echo esc_attr( (string) $mm_index ); ?>"<?php echo 0 === $mm_index ? ' open' : ''; ?>>
					<summary><span><?php echo esc_html( $mm_faq[0] ); ?></span><span class="mm-accordion__icon" aria-hidden="true"><?php echo mm_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></summary>
					<div class="mm-accordion__body"><?php echo wp_kses_post( wpautop( $mm_faq[1] ) ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
