<?php
/**
 * Latest articles.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_args = array(
	'post_type'           => 'post',
	'posts_per_page'      => max( 2, (int) mm_opt( 'homeblog_count' ) ),
	'post_status'         => 'publish',
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);
if ( mm_opt( 'homeblog_cat' ) ) {
	$mm_args['category_name'] = sanitize_title( (string) mm_opt( 'homeblog_cat' ) );
}
$mm_posts = new WP_Query( $mm_args );
if ( ! $mm_posts->have_posts() ) {
	return;
}
$mm_all = '<a class="mm-link-arrow" href="' . esc_url( mm_blog_url() ) . '">' . esc_html__( 'All articles', 'minemirth' ) . mm_icon( 'arrow-right' ) . '</a>';
?>
<section id="mm-section-blog" class="mm-section mm-section--blog">
	<div class="mm-container">
		<?php mm_section_heading( 'homeblog', array( 'extra' => $mm_all ) ); ?>
		<div class="mm-post-grid mm-post-grid--3">
			<?php
			$mm_index = 0;
			while ( $mm_posts->have_posts() ) :
				$mm_posts->the_post();
				get_template_part( 'template-parts/blog/card', null, array( 'index' => $mm_index++ ) );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
