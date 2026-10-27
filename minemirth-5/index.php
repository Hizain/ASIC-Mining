<?php
/**
 * Blog index, archives and search results.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

get_header();

$mm_layout  = (string) mm_opt( 'blog_layout' );
$mm_cols    = (string) mm_opt( 'blog_columns' );
$mm_sidebar = mm_opt( 'blog_sidebar' ) && is_active_sidebar( 'sidebar-blog' );
$mm_paged   = max( 1, (int) get_query_var( 'paged' ) );

if ( is_search() ) {
	/* translators: %s: search query */
	$mm_title = sprintf( __( 'Results for “%s”', 'minemirth' ), get_search_query() );
	$mm_text  = '';
	$mm_eyebrow = __( 'Search', 'minemirth' );
} elseif ( is_home() ) {
	$mm_title   = (string) mm_opt( 'blog_hero_title' );
	$mm_text    = (string) mm_opt( 'blog_hero_text' );
	$mm_eyebrow = '';
} else {
	$mm_title   = wp_strip_all_tags( get_the_archive_title() );
	$mm_text    = get_the_archive_description();
	$mm_eyebrow = is_category() ? __( 'Category', 'minemirth' ) : ( is_tag() ? __( 'Topic', 'minemirth' ) : ( is_author() ? __( 'Author', 'minemirth' ) : '' ) );
	$mm_title   = is_category() || is_tag() ? single_term_title( '', false ) : ( is_author() ? get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) ) : $mm_title );
}

ob_start();
if ( is_home() || is_category() ) {
	mm_blog_category_filter();
}
if ( is_search() ) {
	echo '<div class="mm-search-again" style="max-width:560px;margin-top:24px">';
	get_search_form();
	echo '</div>';
}
$mm_after = (string) ob_get_clean();

mm_page_hero(
	array(
		'title'   => $mm_title,
		'text'    => $mm_text,
		'eyebrow' => $mm_eyebrow,
		'after'   => $mm_after,
	)
);
?>
<main id="mm-main" class="mm-main" tabindex="-1">
	<div class="mm-container mm-page-body">
		<div class="mm-blog-layout<?php echo $mm_sidebar ? ' has-sidebar' : ''; ?>">
			<div class="mm-blog-main">
				<?php if ( have_posts() ) : ?>
					<?php
					$mm_index = 0;
					if ( 'magazine' === $mm_layout && 1 === $mm_paged && ! is_search() ) {
						the_post();
						get_template_part( 'template-parts/blog/card', null, array( 'variant' => 'hero', 'index' => 0 ) );
						++$mm_index;
					}
					$mm_grid_class = 'list' === $mm_layout ? 'mm-post-grid--list' : ( '2' === $mm_cols || $mm_sidebar ? 'mm-post-grid--2' : 'mm-post-grid--3' );
					?>
					<div class="mm-post-grid <?php echo esc_attr( $mm_grid_class ); ?>">
						<?php
						while ( have_posts() ) :
							the_post();
							get_template_part( 'template-parts/blog/card', null, array( 'index' => $mm_index++ ) );
						endwhile;
						?>
					</div>
					<?php mm_pagination(); ?>
				<?php else : ?>
					<?php get_template_part( 'template-parts/content-none' ); ?>
				<?php endif; ?>
			</div>
			<?php if ( $mm_sidebar ) : ?>
				<?php get_sidebar(); ?>
			<?php endif; ?>
		</div>
	</div>
</main>
<?php
get_footer();
