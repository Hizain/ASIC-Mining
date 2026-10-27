<?php
/**
 * Template Name: Guide
 * Template Post Type: page
 *
 * Long-form guide: header with reading time, sticky table of contents, the article,
 * then related guides from the same group.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'mm-blog' );
get_header();

while ( have_posts() ) :
	the_post();
	ob_start();
	the_content();
	$mm_content = (string) ob_get_clean();

	// Contents: the guide's own headings, not the titles inside embedded sections.
	$mm_toc = array_values(
		array_filter(
			mm_toc_items( $mm_content ),
			static function ( $item ) use ( $mm_content ) {
				return ! preg_match( '#<h[23][^>]*class="[^"]*mm-section-title[^"]*"[^>]*id="' . preg_quote( $item[1], '#' ) . '"#', $mm_content );
			}
		)
	);
	$mm_group   = (string) get_post_meta( get_the_ID(), '_mm_group', true );
	$mm_minutes = max( 1, (int) round( str_word_count( wp_strip_all_tags( $mm_content ) ) / 230 ) );
	?>
	<?php if ( mm_opt( 'single_progress' ) ) : ?>
		<div class="mm-progress" data-mm-progress-bar aria-hidden="true"><span></span></div>
	<?php endif; ?>
	<main id="mm-main" class="mm-main mm-guide" tabindex="-1">
		<article class="mm-article">
			<header class="mm-article-head mm-guide__head">
				<div class="mm-article-head__fx" aria-hidden="true"></div>
				<?php echo function_exists( 'mm_deco' ) ? mm_deco( 'circuit' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<div class="mm-container">
					<?php mm_breadcrumbs(); ?>
					<span class="mm-pill mm-guide__pill"><?php echo mm_icon( 'book' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $mm_group ? $mm_group : __( 'Mining guide', 'minemirth' ) ); ?></span>
					<h1 class="mm-article-head__title"><?php the_title(); ?></h1>
					<?php if ( has_excerpt() ) : ?>
						<p class="mm-article-head__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
					<div class="mm-meta mm-guide__meta">
						<span><?php echo mm_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( sprintf( /* translators: %d: minutes */ _n( '%d min read', '%d min read', $mm_minutes, 'minemirth' ), $mm_minutes ) ); ?></span>
						<span><?php echo mm_icon( 'refresh' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'Updated %s', 'minemirth' ), get_the_modified_date() ) ); ?></span>
						<span><?php echo mm_icon( 'activity' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Live network figures', 'minemirth' ); ?></span>
					</div>
				</div>
			</header>
			<div class="mm-container">
				<div class="mm-article-layout no-aside<?php echo count( $mm_toc ) < 3 ? ' no-toc' : ''; ?>">
					<?php if ( count( $mm_toc ) >= 3 ) : ?>
						<aside class="mm-article-toc"><?php mm_toc( $mm_toc ); ?></aside>
					<?php endif; ?>
					<div class="mm-article-main">
						<?php if ( count( $mm_toc ) >= 3 ) : ?>
							<?php mm_toc( $mm_toc, 'inline' ); ?>
						<?php endif; ?>
						<div class="entry-content mm-prose mm-guide__body" data-mm-article>
							<?php echo $mm_content; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered post content. ?>
						</div>
					</div>
				</div>
			</div>
		</article>
		<?php
		$mm_parent  = (int) wp_get_post_parent_id( get_the_ID() );
		$mm_related = array();
		if ( $mm_parent ) {
			foreach ( get_pages( array( 'parent' => $mm_parent, 'sort_column' => 'menu_order', 'exclude' => array( get_the_ID() ), 'post_status' => implode( ',', mm_page_statuses() ) ) ) as $mm_p ) { // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				$mm_g = (string) get_post_meta( $mm_p->ID, '_mm_group', true );
				$mm_related[ $mm_g === $mm_group ? 0 : 1 ][] = $mm_p;
			}
		}
		$mm_related = array_slice( array_merge( $mm_related[0] ?? array(), $mm_related[1] ?? array() ), 0, 3 );
		if ( $mm_related ) :
			$mm_items = array();
			foreach ( $mm_related as $mm_p ) {
				$mm_items[] = array(
					'title' => $mm_p->post_title,
					'text'  => $mm_p->post_excerpt,
					'url'   => get_permalink( $mm_p ),
					'icon'  => get_post_meta( $mm_p->ID, '_mm_icon', true ) ? get_post_meta( $mm_p->ID, '_mm_icon', true ) : 'book',
					'badge' => (string) get_post_meta( $mm_p->ID, '_mm_group', true ),
				);
			}
			echo mm_section_render( // phpcs:ignore WordPress.Security.EscapeOutput
				array(
					'variant' => 'links',
					'data'    => array(
						'eyebrow'    => __( 'Keep learning', 'minemirth' ),
						'title'      => __( 'Related *guides*', 'minemirth' ),
						'items'      => $mm_items,
						'columns'    => '3',
						'tone'       => 'alt',
						'link_label' => __( 'All guides', 'minemirth' ),
						'link_url'   => $mm_parent ? get_permalink( $mm_parent ) : '',
					),
				)
			);
		endif;
		echo mm_section_render( // phpcs:ignore WordPress.Security.EscapeOutput
			array(
				'variant' => 'cta',
				'data'    => array(
					'eyebrow' => __( 'Put it into practice', 'minemirth' ),
					'title'   => __( 'Run the numbers on *your* setup', 'minemirth' ),
					'text'    => __( 'Live price, difficulty and fees, your power price, and every miner we stock.', 'minemirth' ),
					'style'   => 'dark',
					'buttons' => array(
						array( 'label' => __( 'Open the profit calculator', 'minemirth' ), 'url' => '{tool:profit}', 'style' => 'primary', 'icon' => 'calculator' ),
						array( 'label' => __( 'Shop miners', 'minemirth' ), 'url' => '{shop}', 'style' => 'glass' ),
					),
				),
			)
		);
		?>
	</main>
	<?php
endwhile;

get_footer();
