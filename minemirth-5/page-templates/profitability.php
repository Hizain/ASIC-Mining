<?php
/**
 * Template Name: Miner profitability
 * Template Post Type: page
 *
 * The full miner profitability dashboard: every model, every filter, live profit at the visitor's
 * electricity price, with Mine Mirth LLC as supplier for what we stock. Anything written in the page
 * editor appears below the dashboard.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	mm_page_hero(
		array(
			'title' => get_the_title(),
			'text'  => has_excerpt() ? get_the_excerpt() : __( 'Live profit per day for every major ASIC miner at your electricity price. Models we stock link straight to the product with Mine Mirth LLC as the supplier.', 'minemirth' ),
		)
	);
	?>
	<main id="mm-main" class="mm-main mm-premium mm-prof-page" tabindex="-1">
		<?php
		echo mm_section_render( // phpcs:ignore WordPress.Security.EscapeOutput
			array(
				'variant' => 'profitability',
				'data'    => array(
					'limit'   => 0,
					'eyebrow' => __( 'Updated hourly', 'minemirth' ),
					'title'   => __( 'Profitability of *every ASIC miner*', 'minemirth' ),
					'text'    => __( 'Sort by any column, filter by coin, maker or cooling, and set your electricity price. Profit is revenue at today’s price and difficulty, minus the pool fee and your power cost.', 'minemirth' ),
					'pad'     => 'sm',
				),
			)
		);
		echo mm_section_render( // phpcs:ignore WordPress.Security.EscapeOutput
			array(
				'variant' => 'features',
				'data'    => array(
					'eyebrow' => __( 'How it works', 'minemirth' ),
					'title'   => __( 'Numbers you can *check*', 'minemirth' ),
					'columns' => '3',
					'tone'    => 'alt',
					'items'   => array(
						array( 'icon' => 'activity', 'title' => __( 'Live network data', 'minemirth' ), 'text' => __( 'Coin prices, network hashrate, block rewards and difficulty refresh every hour from public sources.', 'minemirth' ) ),
						array( 'icon' => 'bolt', 'title' => __( 'Your power price', 'minemirth' ), 'text' => __( 'Change the electricity price above and every profit figure updates instantly. Hosting rates work too.', 'minemirth' ) ),
						array( 'icon' => 'shield-check', 'title' => __( 'Buy from Mine Mirth', 'minemirth' ), 'text' => __( 'Models we stock show Mine Mirth LLC as the supplier with a link to buy. For anything else, message us on WhatsApp and we check availability for you.', 'minemirth' ) ),
					),
				),
			)
		);
		?>
		<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
			<div class="mm-premium__content entry-content"><?php the_content(); ?></div>
		<?php endif; ?>
	</main>
	<?php
endwhile;

get_footer();
