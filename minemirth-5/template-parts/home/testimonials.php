<?php
/**
 * Testimonials: verified WooCommerce reviews or custom quotes.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_items = array();

if ( 'reviews' === mm_opt( 'testimonials_source' ) && mm_is_woo() ) {
	$mm_reviews = get_comments(
		array(
			'type'       => 'review',
			'status'     => 'approve',
			'number'     => 12,
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => 'rating',
					'value'   => 4,
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			),
		)
	);
	foreach ( $mm_reviews as $mm_review ) {
		$mm_text = trim( wp_strip_all_tags( $mm_review->comment_content ) );
		if ( mb_strlen( $mm_text ) < 20 ) {
			continue;
		}
		$mm_items[] = array(
			'quote'    => wp_trim_words( $mm_text, 46 ),
			'name'     => $mm_review->comment_author,
			'role'     => get_the_title( $mm_review->comment_post_ID ),
			'rating'   => (int) get_comment_meta( $mm_review->comment_ID, 'rating', true ),
			'verified' => function_exists( 'wc_review_is_from_verified_owner' ) && wc_review_is_from_verified_owner( $mm_review->comment_ID ),
		);
	}
} else {
	for ( $mm_i = 1; $mm_i <= 6; $mm_i++ ) {
		$mm_quote = trim( (string) mm_opt( "testimonial_{$mm_i}_quote" ) );
		if ( '' === $mm_quote ) {
			continue;
		}
		$mm_rating  = (string) mm_opt( "testimonial_{$mm_i}_rating" );
		$mm_items[] = array(
			'quote'    => $mm_quote,
			'name'     => (string) mm_opt( "testimonial_{$mm_i}_name" ),
			'role'     => (string) mm_opt( "testimonial_{$mm_i}_role" ),
			'rating'   => '' === $mm_rating ? 5 : (int) $mm_rating,
			'verified' => false,
		);
	}
}

if ( ! $mm_items ) {
	if ( is_customize_preview() ) {
		echo '<section id="mm-section-testimonials" class="mm-section"><div class="mm-container"><p class="mm-admin-note">' . esc_html__( 'Testimonials are hidden: there are no 4★+ product reviews yet. Switch the source to “Custom testimonials” to add your own.', 'minemirth' ) . '</p></div></section>';
	}
	return;
}

$mm_marquee = count( $mm_items ) >= 4;
?>
<section id="mm-section-testimonials" class="mm-section mm-section--testimonials">
	<div class="mm-container">
		<?php mm_section_heading( 'testimonials', array( 'align' => 'center' ) ); ?>
	</div>
	<div class="<?php echo $mm_marquee ? 'mm-quotes mm-quotes--marquee' : 'mm-container mm-quotes mm-quotes--grid'; ?>">
		<div class="mm-quotes__track">
			<?php for ( $mm_pass = 0; $mm_pass < ( $mm_marquee ? 2 : 1 ); $mm_pass++ ) : ?>
				<?php foreach ( $mm_items as $mm_item ) : ?>
					<figure class="mm-quote mm-card"<?php echo $mm_pass ? ' aria-hidden="true"' : ''; ?>>
						<?php if ( $mm_item['rating'] > 0 ) : ?>
							<div class="mm-stars" role="img" aria-label="<?php /* translators: %d: rating */ echo esc_attr( sprintf( __( 'Rated %d out of 5', 'minemirth' ), $mm_item['rating'] ) ); ?>">
								<?php for ( $mm_s = 1; $mm_s <= 5; $mm_s++ ) : ?>
									<span class="<?php echo $mm_s <= $mm_item['rating'] ? 'is-on' : ''; ?>">★</span>
								<?php endfor; ?>
							</div>
						<?php endif; ?>
						<blockquote><p><?php echo esc_html( $mm_item['quote'] ); ?></p></blockquote>
						<figcaption>
							<span class="mm-quote__avatar" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( $mm_item['name'], 0, 1 ) ) ); ?></span>
							<span>
								<strong><?php echo esc_html( $mm_item['name'] ); ?></strong>
								<?php if ( $mm_item['role'] ) : ?>
									<small><?php echo esc_html( $mm_item['role'] ); ?></small>
								<?php endif; ?>
							</span>
							<?php if ( $mm_item['verified'] ) : ?>
								<span class="mm-verified"><?php echo mm_icon( 'shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Verified owner', 'minemirth' ); ?></span>
							<?php endif; ?>
						</figcaption>
					</figure>
				<?php endforeach; ?>
			<?php endfor; ?>
		</div>
	</div>
</section>
