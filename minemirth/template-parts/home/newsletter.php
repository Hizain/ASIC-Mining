<?php
/**
 * Newsletter signup band.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;
?>
<section id="mm-section-newsletter" class="mm-section mm-section--newsletter">
	<div class="mm-container">
		<div class="mm-newsletter-card mm-card" data-mm-reveal>
			<div class="mm-newsletter-card__icon" aria-hidden="true"><?php echo mm_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php mm_section_heading( 'news', array( 'align' => 'center' ) ); ?>
			<?php mm_newsletter_form( array( 'context' => 'home' ) ); ?>
		</div>
	</div>
</section>
