<?php
/**
 * Template Name: Contact
 * Template Post Type: page
 *
 * Contact cards come from Customizer › Footer › Contact details. Put your
 * form plugin's shortcode (Contact Form 7, WPForms, Fluent…) in the page content.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

get_header();

$mm_cards = array_filter(
	array(
		array( 'mail', __( 'Email', 'minemirth' ), (string) mm_opt( 'contact_email' ), mm_opt( 'contact_email' ) ? 'mailto:' . antispambot( (string) mm_opt( 'contact_email' ) ) : '' ),
		array( 'phone', __( 'Phone', 'minemirth' ), (string) mm_opt( 'contact_phone' ), mm_opt( 'contact_phone' ) ? 'tel:' . preg_replace( '/[^0-9+]/', '', (string) mm_opt( 'contact_phone' ) ) : '' ),
		array( 'clock', __( 'Support hours', 'minemirth' ), (string) mm_opt( 'contact_hours' ), '' ),
		array( 'map-pin', __( 'Address', 'minemirth' ), (string) mm_opt( 'contact_address' ), '' ),
	),
	static fn( $c ) => '' !== $c[2]
);

while ( have_posts() ) :
	the_post();
	mm_page_hero(
		array(
			'eyebrow' => __( 'Talk to us', 'minemirth' ),
			'title'   => get_the_title(),
			'text'    => has_excerpt() ? get_the_excerpt() : __( 'Questions about hardware, hosting or an order? Send a message and a member of the team will reply.', 'minemirth' ),
		)
	);
	?>
	<main id="mm-main" class="mm-main" tabindex="-1">
		<div class="mm-container mm-page-body">
			<div class="mm-contact-grid">
				<div class="mm-contact-cards">
					<?php foreach ( $mm_cards as $mm_card ) : ?>
						<?php $mm_tag = $mm_card[3] ? 'a' : 'div'; ?>
						<<?php echo esc_html( $mm_tag ); ?> class="mm-contact-card mm-card"<?php echo $mm_card[3] ? ' href="' . esc_url( $mm_card[3], array( 'mailto', 'tel' ) ) . '"' : ''; ?>>
							<span class="mm-icon-badge"><?php echo mm_icon( $mm_card[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span><small><?php echo esc_html( $mm_card[1] ); ?></small><strong><?php echo nl2br( esc_html( 'mail' === $mm_card[0] ? antispambot( $mm_card[2] ) : $mm_card[2] ) ); ?></strong></span>
						</<?php echo esc_html( $mm_tag ); ?>>
					<?php endforeach; ?>
					<?php echo mm_social_links(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<div class="mm-contact-form mm-card">
					<div class="entry-content mm-prose">
						<?php
						if ( '' !== trim( get_the_content() ) ) {
							the_content();
						} elseif ( current_user_can( 'edit_pages' ) ) {
							echo '<p class="mm-admin-note">' . esc_html__( 'Add your contact form shortcode to this page’s content (visible to editors only).', 'minemirth' ) . '</p>';
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</main>
	<?php
endwhile;

get_footer();
