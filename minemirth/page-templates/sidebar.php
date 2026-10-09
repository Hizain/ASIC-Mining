<?php
/**
 * Template Name: Page with sidebar
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
			'title' => get_the_title(),
			'text'  => has_excerpt() ? get_the_excerpt() : '',
			'image' => get_post_thumbnail_id(),
		)
	);
	?>
	<main id="mm-main" class="mm-main" tabindex="-1">
		<div class="mm-container mm-page-body mm-page-body--sidebar">
			<div class="entry-content mm-prose"><?php the_content(); ?></div>
			<aside class="mm-sidebar"><?php dynamic_sidebar( 'sidebar-page' ); ?></aside>
		</div>
	</main>
	<?php
endwhile;

get_footer();
