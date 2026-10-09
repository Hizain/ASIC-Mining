<?php
/**
 * Template tags: page heroes, breadcrumbs, post meta, pagination.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build the breadcrumb trail as [ [label, url], ... ].
 */
function mm_breadcrumb_items(): array {
	$items = array( array( __( 'Home', 'minemirth' ), home_url( '/' ) ) );

	if ( is_front_page() ) {
		return array();
	}

	if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
		if ( is_shop() ) {
			$items[] = array( (string) mm_opt( 'shop_hero_title' ), '' );
			return $items;
		}
		if ( is_product() || is_product_taxonomy() ) {
			$items[] = array( get_the_title( wc_get_page_id( 'shop' ) ), mm_shop_url() );
		}
		if ( is_product_taxonomy() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy ) ) as $ancestor_id ) {
					$ancestor = get_term( $ancestor_id, $term->taxonomy );
					if ( $ancestor instanceof WP_Term ) {
						$items[] = array( $ancestor->name, get_term_link( $ancestor ) );
					}
				}
				$items[] = array( $term->name, '' );
			}
			return $items;
		}
		if ( is_product() ) {
			$terms = wc_get_product_terms( get_the_ID(), 'product_cat', array( 'orderby' => 'parent', 'order' => 'DESC' ) );
			if ( $terms ) {
				$items[] = array( $terms[0]->name, get_term_link( $terms[0] ) );
			}
			$items[] = array( get_the_title(), '' );
			return $items;
		}
	}

	if ( is_home() ) {
		$items[] = array( (string) mm_opt( 'blog_hero_title' ), '' );
	} elseif ( is_singular( 'post' ) ) {
		$items[] = array( (string) mm_opt( 'blog_hero_title' ), mm_blog_url() );
		$cats    = get_the_category();
		if ( $cats ) {
			$items[] = array( $cats[0]->name, get_category_link( $cats[0] ) );
		}
		$items[] = array( get_the_title(), '' );
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $ancestor ) {
			$items[] = array( get_the_title( $ancestor ), get_permalink( $ancestor ) );
		}
		$items[] = array( get_the_title(), '' );
	} elseif ( is_singular() ) {
		$items[] = array( get_the_title(), '' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$items[] = array( (string) mm_opt( 'blog_hero_title' ), mm_blog_url() );
		$items[] = array( single_term_title( '', false ), '' );
	} elseif ( is_author() ) {
		$items[] = array( get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) ), '' );
	} elseif ( is_search() ) {
		/* translators: %s: search query */
		$items[] = array( sprintf( __( 'Search: %s', 'minemirth' ), get_search_query() ), '' );
	} elseif ( is_404() ) {
		$items[] = array( __( 'Not found', 'minemirth' ), '' );
	} elseif ( is_archive() ) {
		$items[] = array( wp_strip_all_tags( get_the_archive_title() ), '' );
	}

	return $items;
}

/**
 * Print breadcrumbs.
 */
function mm_breadcrumbs(): void {
	if ( ! mm_opt( 'breadcrumbs' ) ) {
		return;
	}
	$items = mm_breadcrumb_items();
	if ( count( $items ) < 2 ) {
		return;
	}
	echo '<nav class="mm-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'minemirth' ) . '"><ol>';
	$last = count( $items ) - 1;
	foreach ( $items as $i => $item ) {
		list( $label, $url ) = $item;
		echo '<li>';
		if ( $url && $i !== $last ) {
			echo '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		} else {
			echo '<span aria-current="page">' . esc_html( wp_trim_words( $label, 8 ) ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ol></nav>';
}

/**
 * Inner-page hero.
 *
 * @param array $args title, text, eyebrow, image (attachment id), class, actions (html), align.
 */
function mm_page_hero( array $args = array() ): void {
	$args  = wp_parse_args(
		$args,
		array(
			'title'   => '',
			'text'    => '',
			'eyebrow' => '',
			'image'   => 0,
			'class'   => '',
			'actions' => '',
			'align'   => (string) mm_opt( 'page_hero_align' ),
			'after'   => '',
		)
	);
	$style = (string) mm_opt( 'page_hero_style' );
	$image = 'image' === $style && $args['image'] ? mm_image( $args['image'], 'mm-wide', array( 'class' => 'mm-page-hero__bg', 'loading' => 'eager' ) ) : '';
	?>
	<header class="mm-page-hero mm-page-hero--<?php echo esc_attr( $style ); ?> mm-page-hero--<?php echo esc_attr( $args['align'] ); ?> <?php echo esc_attr( $args['class'] ); ?>">
		<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="mm-page-hero__fx" aria-hidden="true"></div>
		<div class="mm-container mm-page-hero__inner">
			<?php mm_breadcrumbs(); ?>
			<?php if ( $args['eyebrow'] ) : ?>
				<span class="mm-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></span>
			<?php endif; ?>
			<h1 class="mm-page-hero__title"><?php echo mm_highlight( $args['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
			<?php if ( $args['text'] ) : ?>
				<div class="mm-page-hero__text"><?php echo wp_kses_post( wpautop( $args['text'] ) ); ?></div>
			<?php endif; ?>
			<?php if ( $args['actions'] ) : ?>
				<div class="mm-btn-row"><?php echo $args['actions']; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php endif; ?>
			<?php echo $args['after']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</header>
	<?php
}

/**
 * Post meta line (author, date, reading time).
 *
 * @param array $args avatar(bool), author, date, reading, updated.
 */
function mm_post_meta( array $args = array() ): void {
	$args = wp_parse_args(
		$args,
		array(
			'avatar'  => false,
			'author'  => (bool) mm_opt( 'blog_meta_author' ),
			'date'    => (bool) mm_opt( 'blog_meta_date' ),
			'reading' => (bool) mm_opt( 'blog_meta_reading' ),
			'updated' => false,
		)
	);
	$bits = array();
	if ( $args['author'] ) {
		$author = '<span class="mm-meta__author">';
		if ( $args['avatar'] ) {
			$author .= get_avatar( get_the_author_meta( 'ID' ), 64, '', '', array( 'class' => 'mm-meta__avatar' ) );
		}
		$author .= '<a href="' . esc_url( get_author_posts_url( (int) get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>';
		$bits[]  = $author;
	}
	if ( $args['date'] ) {
		$bits[] = '<time datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( get_the_date() ) . '</time>';
	}
	if ( $args['updated'] && get_the_modified_time( 'U' ) > get_the_time( 'U' ) + DAY_IN_SECONDS ) {
		/* translators: %s: date */
		$bits[] = '<span class="mm-meta__updated">' . sprintf( esc_html__( 'Updated %s', 'minemirth' ), '<time datetime="' . esc_attr( get_the_modified_date( DATE_W3C ) ) . '">' . esc_html( get_the_modified_date() ) . '</time>' ) . '</span>';
	}
	if ( $args['reading'] ) {
		/* translators: %d: minutes */
		$bits[] = '<span class="mm-meta__reading">' . mm_icon( 'clock' ) . sprintf( esc_html__( '%d min read', 'minemirth' ), mm_reading_time() ) . '</span>';
	}
	if ( $bits ) {
		echo '<div class="mm-meta">' . implode( '<span class="mm-meta__sep" aria-hidden="true">·</span>', $bits ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/**
 * Primary category pill for a post.
 */
function mm_post_category_pill( $post = null ): void {
	$cats = get_the_category( $post ? get_post( $post )->ID : 0 );
	if ( ! $cats ) {
		return;
	}
	echo '<a class="mm-pill" href="' . esc_url( get_category_link( $cats[0] ) ) . '">' . esc_html( $cats[0]->name ) . '</a>';
}

/**
 * Numbered pagination.
 */
function mm_pagination( $query = null ): void {
	$query = $query ? $query : $GLOBALS['wp_query'];
	if ( $query->max_num_pages < 2 ) {
		return;
	}
	$links = paginate_links(
		array(
			'total'     => $query->max_num_pages,
			'current'   => max( 1, get_query_var( 'paged' ) ),
			'mid_size'  => 1,
			'prev_text' => mm_icon( 'arrow-left' ) . '<span class="screen-reader-text">' . __( 'Previous page', 'minemirth' ) . '</span>',
			'next_text' => '<span class="screen-reader-text">' . __( 'Next page', 'minemirth' ) . '</span>' . mm_icon( 'arrow-right' ),
			'type'      => 'list',
		)
	);
	if ( $links ) {
		echo '<nav class="mm-pagination" aria-label="' . esc_attr__( 'Pagination', 'minemirth' ) . '">' . $links . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/**
 * Category filter pills for the blog index.
 */
function mm_blog_category_filter(): void {
	if ( ! mm_opt( 'blog_cat_filter' ) ) {
		return;
	}
	$cats = get_categories(
		array(
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 8,
			'hide_empty' => true,
		)
	);
	if ( count( $cats ) < 2 ) {
		return;
	}
	$current = is_category() ? get_queried_object_id() : 0;
	echo '<nav class="mm-filter-pills" aria-label="' . esc_attr__( 'Article categories', 'minemirth' ) . '">';
	echo '<a class="mm-pill' . ( $current ? '' : ' is-active' ) . '" href="' . esc_url( mm_blog_url() ) . '">' . esc_html__( 'All', 'minemirth' ) . '</a>';
	foreach ( $cats as $cat ) {
		printf(
			'<a class="mm-pill%1$s" href="%2$s">%3$s</a>',
			$current === $cat->term_id ? ' is-active' : '',
			esc_url( get_category_link( $cat ) ),
			esc_html( $cat->name )
		);
	}
	echo '</nav>';
}

/**
 * Comment markup.
 */
function mm_comment_callback( $comment, $args, $depth ): void {
	$tag = 'div' === $args['style'] ? 'div' : 'li';
	?>
	<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( empty( $args['has_children'] ) ? 'mm-comment' : 'mm-comment parent' ); ?>>
		<article class="mm-comment__body">
			<div class="mm-comment__avatar"><?php echo get_avatar( $comment, 48 ); ?></div>
			<div class="mm-comment__main">
				<header class="mm-comment__meta">
					<strong><?php comment_author_link(); ?></strong>
					<a href="<?php echo esc_url( get_comment_link( $comment, $args ) ); ?>"><time datetime="<?php comment_time( 'c' ); ?>"><?php echo esc_html( get_comment_date() ); ?></time></a>
				</header>
				<?php if ( '0' === $comment->comment_approved ) : ?>
					<p class="mm-comment__pending"><?php esc_html_e( 'Your comment is awaiting moderation.', 'minemirth' ); ?></p>
				<?php endif; ?>
				<div class="mm-comment__text"><?php comment_text(); ?></div>
				<?php
				comment_reply_link(
					array_merge(
						$args,
						array(
							'depth'     => $depth,
							'max_depth' => $args['max_depth'],
							'before'    => '<div class="mm-comment__reply">',
							'after'     => '</div>',
						)
					)
				);
				?>
			</div>
		</article>
	<?php
}
