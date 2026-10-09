<?php
/**
 * Template Name: Landing (canvas for page builders)
 * Template Post Type: page
 *
 * Header and footer stay; no page title, no width limit. Ideal for
 * Elementor, Bricks, Gutenberg patterns or the [mm_*] shortcodes.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="mm-main" class="mm-main" tabindex="-1">
	<?php
	while ( have_posts() ) :
		the_post();
		echo '<div class="entry-content mm-landing">';
		the_content();
		echo '</div>';
	endwhile;
	?>
</main>
<?php
get_footer();
