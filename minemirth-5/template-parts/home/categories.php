<?php
/**
 * Category cards.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_cards = array();

if ( 'auto' === mm_opt( 'cats_source' ) && taxonomy_exists( 'product_cat' ) ) {
	$mm_terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'number'     => 8,
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
		)
	);
	if ( ! is_wp_error( $mm_terms ) ) {
		foreach ( $mm_terms as $mm_term ) {
			$mm_cards[] = array(
				/* translators: %d: product count */
				'tag'   => sprintf( _n( '%d product', '%d products', $mm_term->count, 'minemirth' ), $mm_term->count ),
				'title' => $mm_term->name,
				'text'  => wp_trim_words( wp_strip_all_tags( $mm_term->description ), 16 ),
				'url'   => get_term_link( $mm_term ),
				'icon'  => 'cpu',
				'image' => (int) get_term_meta( $mm_term->term_id, 'thumbnail_id', true ),
			);
		}
	}
}

if ( ! $mm_cards ) {
	for ( $mm_i = 1; $mm_i <= 4; $mm_i++ ) {
		$mm_title = (string) mm_opt( "cat_{$mm_i}_title" );
		if ( '' === $mm_title ) {
			continue;
		}
		$mm_cards[] = array(
			'tag'   => (string) mm_opt( "cat_{$mm_i}_tag" ),
			'title' => $mm_title,
			'text'  => (string) mm_opt( "cat_{$mm_i}_text" ),
			'url'   => mm_url( mm_opt( "cat_{$mm_i}_url" ) ),
			'icon'  => (string) mm_opt( "cat_{$mm_i}_icon" ),
			'image' => (int) mm_opt( "cat_{$mm_i}_image" ),
		);
	}
}

if ( ! $mm_cards ) {
	return;
}
?>
<section id="mm-section-categories" class="mm-section mm-section--categories">
	<div class="mm-container">
		<?php mm_section_heading( 'cats' ); ?>
		<div class="mm-cat-grid mm-cat-grid--<?php echo esc_attr( (string) min( 4, count( $mm_cards ) ) ); ?>">
			<?php foreach ( $mm_cards as $mm_index => $mm_card ) : ?>
				<a class="mm-cat-card mm-card<?php echo $mm_card['image'] ? ' has-image' : ''; ?>" href="<?php echo esc_url( (string) $mm_card['url'] ); ?>" data-mm-reveal style="--d:<?php echo esc_attr( (string) $mm_index ); ?>">
					<?php if ( $mm_card['image'] ) : ?>
						<span class="mm-cat-card__media"><?php echo mm_image( $mm_card['image'], 'mm-card', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<?php endif; ?>
					<span class="mm-cat-card__top">
						<span class="mm-icon-badge"><?php echo mm_icon( $mm_card['icon'] ? $mm_card['icon'] : 'cpu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<?php if ( $mm_card['tag'] ) : ?>
							<span class="mm-cat-card__tag"><?php echo esc_html( $mm_card['tag'] ); ?></span>
						<?php endif; ?>
					</span>
					<span class="mm-cat-card__title"><?php echo esc_html( $mm_card['title'] ); ?></span>
					<?php if ( $mm_card['text'] ) : ?>
						<span class="mm-cat-card__text"><?php echo esc_html( $mm_card['text'] ); ?></span>
					<?php endif; ?>
					<span class="mm-cat-card__go" aria-hidden="true"><?php echo mm_icon( 'arrow-up-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
