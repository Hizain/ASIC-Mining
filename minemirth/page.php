<?php
/**
 * Default page.
 *
 * WooCommerce cart/checkout/account pages use this template too; they get
 * a slimmer header and a wide content area.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

get_header();

$mm_is_store = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );

while ( have_posts() ) :
	the_post();

	if ( ! $mm_is_store ) {
		mm_page_hero(
			array(
				'title' => get_the_title(),
				'text'  => has_excerpt() ? get_the_excerpt() : '',
				'image' => get_post_thumbnail_id(),
			)
		);
	}
	?>
	<main id="mm-main" class="mm-main" tabindex="-1">
		<div class="mm-container mm-page-body<?php echo $mm_is_store ? ' mm-store-page' : ''; ?>">
			<?php if ( $mm_is_store ) : ?>
				<?php get_template_part( 'template-parts/woocommerce/store-head' ); ?>
			<?php endif; ?>
			<div class="entry-content <?php echo $mm_is_store ? 'mm-store-content' : 'mm-page-content mm-prose'; ?>">
				<?php
				the_content();
				wp_link_pages();
				?>
			</div>
			<?php if ( ! $mm_is_store && ( comments_open() || get_comments_number() ) ) : ?>
				<div class="mm-page-content"><?php comments_template(); ?></div>
			<?php endif; ?>
		</div>
	</main>
	<?php
endwhile;

get_footer();
