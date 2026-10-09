<?php
/**
 * Template Name: Mining Calculator
 * Template Post Type: page
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	mm_page_hero(
		array(
			'eyebrow' => __( 'Free tool · live data', 'minemirth' ),
			'title'   => get_the_title(),
			'text'    => has_excerpt() ? get_the_excerpt() : __( 'Estimate daily revenue, power cost, profit and payback for any ASIC miner using the current BTC price and network difficulty.', 'minemirth' ),
		)
	);
	?>
	<main id="mm-main" class="mm-main" tabindex="-1">
		<div class="mm-container mm-page-body" style="padding-top:clamp(16px,3vw,40px)">
			<div class="mm-split mm-split--calc-page" style="align-items:start">
				<div><?php echo mm_calculator_html( array( 'variant' => 'full', 'title' => get_the_title() ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<div>
					<div class="mm-card" style="padding:28px">
						<span class="mm-eyebrow"><?php esc_html_e( 'Network right now', 'minemirth' ); ?></span>
						<?php echo mm_market_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
					<?php if ( '' !== trim( get_the_content() ) ) : ?>
						<div class="entry-content mm-prose" style="margin-top:32px"><?php the_content(); ?></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</main>
	<?php
endwhile;

get_footer();
