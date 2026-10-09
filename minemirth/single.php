<?php
/**
 * Single article.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	// Render content first so the table of contents can be built from it.
	ob_start();
	the_content();
	wp_link_pages(
		array(
			'before' => '<nav class="mm-pagination"><span>' . esc_html__( 'Pages:', 'minemirth' ) . '</span>',
			'after'  => '</nav>',
		)
	);
	$mm_content = (string) ob_get_clean();

	$mm_toc_items = mm_opt( 'single_toc' ) ? mm_toc_items( $mm_content ) : array();
	$mm_show_toc  = count( $mm_toc_items ) >= (int) mm_opt( 'single_toc_min' );
	$mm_aside     = '';
	if ( mm_opt( 'promo_sidebar' ) ) {
		$mm_aside = mm_promo_html_for( 'sidebar' );
	}
	ob_start();
	if ( is_active_sidebar( 'sidebar-blog' ) ) {
		dynamic_sidebar( 'sidebar-blog' );
	}
	$mm_widgets = (string) ob_get_clean();
	$mm_has_aside = '' !== $mm_aside || '' !== $mm_widgets;
	$mm_featured  = (string) mm_opt( 'single_featured' );

	$mm_layout_class = 'mm-article-layout';
	if ( ! $mm_show_toc ) {
		$mm_layout_class .= ' no-toc';
	}
	if ( ! $mm_has_aside ) {
		$mm_layout_class .= ' no-aside';
	}
	?>
	<?php if ( mm_opt( 'single_progress' ) ) : ?>
		<div class="mm-progress" data-mm-progress-bar aria-hidden="true"><span></span></div>
	<?php endif; ?>

	<main id="mm-main" class="mm-main" tabindex="-1">
		<article <?php post_class( 'mm-article' ); ?>>
			<header class="mm-article-head">
				<div class="mm-article-head__fx" aria-hidden="true"></div>
				<div class="mm-container">
					<?php mm_breadcrumbs(); ?>
					<?php mm_post_category_pill(); ?>
					<h1 class="mm-article-head__title"><?php the_title(); ?></h1>
					<?php if ( has_excerpt() ) : ?>
						<p class="mm-article-head__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
					<?php
					mm_post_meta(
						array(
							'avatar'  => true,
							'updated' => (bool) mm_opt( 'single_updated' ),
							'reading' => true,
						)
					);
					?>
				</div>
			</header>

			<?php if ( has_post_thumbnail() && 'hidden' !== $mm_featured ) : ?>
				<figure class="mm-article-figure mm-article-figure--<?php echo esc_attr( $mm_featured ); ?>">
					<?php the_post_thumbnail( 'mm-wide', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
					<?php if ( get_the_post_thumbnail_caption() ) : ?>
						<figcaption><?php the_post_thumbnail_caption(); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endif; ?>

			<div class="mm-container">
				<div class="<?php echo esc_attr( $mm_layout_class ); ?>">
					<?php if ( $mm_show_toc ) : ?>
						<aside class="mm-article-toc"><?php mm_toc( $mm_toc_items ); ?></aside>
					<?php endif; ?>

					<div class="mm-article-main">
						<?php if ( $mm_show_toc ) : ?>
							<?php mm_toc( $mm_toc_items, 'inline' ); ?>
						<?php endif; ?>
						<div class="entry-content mm-prose" data-mm-article>
							<?php echo $mm_content; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered post content. ?>
						</div>

						<footer class="mm-article-footer">
							<?php if ( has_tag() ) : ?>
								<div class="mm-tags">
									<?php foreach ( get_the_tags() as $mm_tag ) : ?>
										<a class="mm-pill" href="<?php echo esc_url( get_tag_link( $mm_tag ) ); ?>">#<?php echo esc_html( $mm_tag->name ); ?></a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php if ( mm_opt( 'single_share' ) ) : ?>
								<?php mm_share_buttons(); ?>
							<?php endif; ?>

							<?php if ( mm_opt( 'promo_end' ) ) : ?>
								<div class="mm-end-cta"><?php echo mm_promo_html_for( 'end' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							<?php endif; ?>

							<?php if ( mm_opt( 'single_author' ) ) : ?>
								<div class="mm-author-box mm-card">
									<?php echo get_avatar( get_the_author_meta( 'ID' ), 144 ); ?>
									<div>
										<small><?php esc_html_e( 'Written by', 'minemirth' ); ?></small>
										<h2><?php the_author(); ?></h2>
										<?php if ( get_the_author_meta( 'description' ) ) : ?>
											<p><?php echo wp_kses_post( get_the_author_meta( 'description' ) ); ?></p>
										<?php endif; ?>
										<a class="mm-link-arrow" href="<?php echo esc_url( get_author_posts_url( (int) get_the_author_meta( 'ID' ) ) ); ?>"><?php esc_html_e( 'More articles', 'minemirth' ); ?><?php echo mm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
									</div>
								</div>
							<?php endif; ?>

							<?php if ( mm_opt( 'single_newsletter' ) ) : ?>
								<div class="mm-promo mm-promo--newsletter">
									<span class="mm-promo__label"><?php echo mm_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( (string) mm_opt( 'news_eyebrow' ) ); ?></span>
									<h2 class="mm-promo__title"><?php echo esc_html( mm_strip_highlight( mm_opt( 'news_title' ) ) ); ?></h2>
									<p class="mm-promo__text"><?php echo esc_html( (string) mm_opt( 'news_subtitle' ) ); ?></p>
									<?php mm_newsletter_form( array( 'context' => 'article' ) ); ?>
								</div>
							<?php endif; ?>

							<?php if ( mm_opt( 'single_nav' ) ) : ?>
								<?php
								$mm_prev = get_previous_post();
								$mm_next = get_next_post();
								?>
								<?php if ( $mm_prev || $mm_next ) : ?>
									<nav class="mm-post-nav" aria-label="<?php esc_attr_e( 'More articles', 'minemirth' ); ?>">
										<?php if ( $mm_prev ) : ?>
											<a class="mm-card is-prev" href="<?php echo esc_url( get_permalink( $mm_prev ) ); ?>"><small><?php echo mm_icon( 'arrow-left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Previous', 'minemirth' ); ?></small><strong><?php echo esc_html( get_the_title( $mm_prev ) ); ?></strong></a>
										<?php else : ?>
											<span></span>
										<?php endif; ?>
										<?php if ( $mm_next ) : ?>
											<a class="mm-card is-next" href="<?php echo esc_url( get_permalink( $mm_next ) ); ?>"><small><?php esc_html_e( 'Next', 'minemirth' ); ?><?php echo mm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></small><strong><?php echo esc_html( get_the_title( $mm_next ) ); ?></strong></a>
										<?php endif; ?>
									</nav>
								<?php endif; ?>
							<?php endif; ?>
						</footer>

						<?php if ( comments_open() || get_comments_number() ) : ?>
							<?php comments_template(); ?>
						<?php endif; ?>
					</div>

					<?php if ( $mm_has_aside ) : ?>
						<aside class="mm-article-aside">
							<?php if ( $mm_aside ) : ?>
								<div class="mm-aside-promo"><?php echo $mm_aside; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							<?php endif; ?>
							<?php echo $mm_widgets; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</aside>
					<?php endif; ?>
				</div>
			</div>
		</article>

		<?php
		$mm_related_count = (int) mm_opt( 'single_related' );
		if ( $mm_related_count > 0 ) :
			$mm_related = mm_related_posts( get_the_ID(), $mm_related_count );
			if ( $mm_related->have_posts() ) :
				?>
				<section class="mm-related" aria-labelledby="mm-related-title">
					<div class="mm-container">
						<div class="mm-section-head">
							<div class="mm-section-head__copy">
								<span class="mm-eyebrow"><?php esc_html_e( 'Keep reading', 'minemirth' ); ?></span>
								<h2 class="mm-section-title" id="mm-related-title"><?php esc_html_e( 'Related articles', 'minemirth' ); ?></h2>
							</div>
							<div class="mm-section-head__extra"><a class="mm-link-arrow" href="<?php echo esc_url( mm_blog_url() ); ?>"><?php esc_html_e( 'All articles', 'minemirth' ); ?><?php echo mm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></div>
						</div>
						<div class="mm-post-grid mm-post-grid--3">
							<?php
							$mm_i = 0;
							while ( $mm_related->have_posts() ) :
								$mm_related->the_post();
								get_template_part( 'template-parts/blog/card', null, array( 'index' => $mm_i++ ) );
							endwhile;
							wp_reset_postdata();
							?>
						</div>
					</div>
				</section>
				<?php
			endif;
		endif;
		?>
	</main>
	<?php
endwhile;

get_footer();
