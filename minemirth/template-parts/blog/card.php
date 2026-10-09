<?php
/**
 * Post card.
 *
 * @package MineMirth
 *
 * @var array $args variant (default|hero), index.
 */

defined( 'ABSPATH' ) || exit;

$mm_variant = $args['variant'] ?? 'default';
$mm_index   = (int) ( $args['index'] ?? 0 );
$mm_size    = 'hero' === $mm_variant ? 'mm-wide' : 'mm-card';
?>
<article <?php post_class( 'mm-post-card mm-card' . ( 'hero' === $mm_variant ? ' mm-post-card--hero' : '' ) ); ?> data-mm-reveal style="--d:<?php echo esc_attr( (string) ( $mm_index % 3 ) ); ?>">
	<a class="mm-post-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( $mm_size, array( 'loading' => 'hero' === $mm_variant ? 'eager' : 'lazy', 'alt' => '' ) ); ?>
		<?php else : ?>
			<span class="mm-post-card__placeholder"><?php echo mm_icon( 'book' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<?php endif; ?>
	</a>
	<div class="mm-post-card__body">
		<?php if ( 'post' === get_post_type() ) : ?>
			<div class="mm-post-card__cats"><?php mm_post_category_pill(); ?></div>
		<?php endif; ?>
		<h3 class="mm-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<?php if ( (int) mm_opt( 'blog_excerpt' ) > 0 ) : ?>
			<div class="mm-post-card__excerpt"><?php the_excerpt(); ?></div>
		<?php endif; ?>
		<?php if ( 'post' === get_post_type() ) : ?>
			<?php mm_post_meta( array( 'avatar' => 'hero' === $mm_variant ) ); ?>
		<?php endif; ?>
	</div>
</article>
