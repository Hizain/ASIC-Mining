<?php
/**
 * "Why choose us" bento grid.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_items = array();
for ( $mm_i = 1; $mm_i <= 6; $mm_i++ ) {
	$mm_title = (string) mm_opt( "feature_{$mm_i}_title" );
	if ( '' === $mm_title ) {
		continue;
	}
	$mm_items[] = array(
		'icon'  => (string) mm_opt( "feature_{$mm_i}_icon" ),
		'title' => $mm_title,
		'text'  => (string) mm_opt( "feature_{$mm_i}_text" ),
	);
}
if ( ! $mm_items ) {
	return;
}
$mm_bento = 6 === count( $mm_items );
?>
<section id="mm-section-features" class="mm-section mm-section--features mm-section--alt">
	<div class="mm-container">
		<?php mm_section_heading( 'features', array( 'align' => 'center' ) ); ?>
		<div class="mm-bento<?php echo $mm_bento ? ' mm-bento--6' : ''; ?>">
			<?php foreach ( $mm_items as $mm_index => $mm_item ) : ?>
				<?php $mm_big = $mm_bento && in_array( $mm_index, array( 0, 1, 5 ), true ); ?>
				<article class="mm-bento__card mm-card<?php echo $mm_big ? ' is-feature' : ''; ?>" data-mm-reveal style="--d:<?php echo esc_attr( (string) $mm_index ); ?>">
					<?php if ( $mm_big ) : ?>
						<div class="mm-bento__visual" aria-hidden="true">
							<span class="mm-bento__ring"></span>
							<span class="mm-bento__ring mm-bento__ring--2"></span>
							<span class="mm-bento__glyph"><?php echo mm_icon( $mm_item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						</div>
					<?php else : ?>
						<span class="mm-icon-badge"><?php echo mm_icon( $mm_item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<?php endif; ?>
					<h3 class="mm-bento__title"><?php echo esc_html( $mm_item['title'] ); ?></h3>
					<?php if ( $mm_item['text'] ) : ?>
						<p class="mm-bento__text"><?php echo wp_kses_post( $mm_item['text'] ); ?></p>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
