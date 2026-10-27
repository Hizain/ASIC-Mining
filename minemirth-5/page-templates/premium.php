<?php
/**
 * Template Name: Premium sections
 * Template Post Type: page
 *
 * Full-width page built from Mine Mirth sections. When the content does not start with a
 * hero section, the standard page header is shown first.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$mm_blocks = parse_blocks( mm_page_raw_content( (int) get_the_ID() ) );
	$mm_first  = null;
	foreach ( $mm_blocks as $mm_block ) {
		if ( ! empty( $mm_block['blockName'] ) ) {
			$mm_first = $mm_block;
			break;
		}
	}
	if ( ! $mm_first || 'mm/section' !== $mm_first['blockName'] || 'hero' !== ( $mm_first['attrs']['variant'] ?? '' ) ) {
		mm_page_hero(
			array(
				'title' => get_the_title(),
				'text'  => has_excerpt() ? get_the_excerpt() : '',
			)
		);
	}
	?>
	<main id="mm-main" class="mm-main mm-premium" tabindex="-1">
		<div class="mm-premium__content entry-content">
			<?php the_content(); ?>
		</div>
	</main>
	<?php
endwhile;

get_footer();
