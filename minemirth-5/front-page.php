<?php
/**
 * Homepage.
 *
 * Renders the Customizer-managed sections (Appearance > Customize >
 * Mine Mirth · Homepage) and/or the static front page's own content.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

get_header();

$mm_mode       = (string) mm_opt( 'home_mode' );
$mm_page_front = 'page' === get_option( 'show_on_front' );
?>
<main id="mm-main" class="mm-main mm-home" tabindex="-1">
	<?php if ( 'content' !== $mm_mode || ! $mm_page_front ) : ?>
		<div id="mm-home-sections" class="mm-home-sections">
			<?php get_template_part( 'template-parts/home/sections' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $mm_page_front && 'sections' !== $mm_mode ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			if ( has_block( 'mm/section', mm_page_raw_content( (int) get_the_ID() ) ) ) :
				// Built from Mine Mirth sections: full-width, no article styling.
				?>
				<div class="mm-premium__content mm-home-premium"><?php the_content(); ?></div>
				<?php
			elseif ( '' !== trim( mm_page_raw_content( (int) get_the_ID() ) ) ) :
				?>
				<div class="mm-section mm-home-content">
					<div class="mm-container entry-content mm-prose mm-prose--wide">
						<?php the_content(); ?>
					</div>
				</div>
				<?php
			endif;
		endwhile;
		?>
	<?php endif; ?>
</main>
<?php
get_footer();
