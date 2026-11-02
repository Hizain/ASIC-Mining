<?php
/**
 * The miner hosting page, fully controlled from Hosting Plans › Listings & page:
 * a short header, then the listing grid, then your own content.
 *
 * @package MineMirthHostingListings
 */

defined( 'ABSPATH' ) || exit;

get_header();

$mmhl_actions = '';
if ( mmhl_opt( 'btn_text' ) && mmhl_opt( 'btn_url' ) ) {
	$mmhl_actions = function_exists( 'mm_button' )
		? mm_button( (string) mmhl_opt( 'btn_text' ), (string) mmhl_opt( 'btn_url' ), 'glass', array( 'icon' => 'arrow-right' ) )
		: '<a class="mm-btn mm-btn--glass" href="' . esc_url( mmhl_url( (string) mmhl_opt( 'btn_url' ) ) ) . '">' . esc_html( (string) mmhl_opt( 'btn_text' ) ) . '</a>';
}
$mmhl_title = (string) mmhl_opt( 'title' );
$mmhl_title = '' !== trim( $mmhl_title ) ? $mmhl_title : get_the_title( mmhl_page_id() );

if ( function_exists( 'mm_page_hero' ) ) {
	mm_page_hero(
		array(
			'eyebrow' => (string) mmhl_opt( 'eyebrow' ),
			'title'   => $mmhl_title,
			'text'    => (string) mmhl_opt( 'intro' ),
			'actions' => $mmhl_actions,
			'class'   => 'mmhl-hero',
			'deco'    => 'blocks',
		)
	);
} else {
	?>
	<header class="mmhl-hero mmhl-hero--plain">
		<div class="mm-container">
			<?php if ( mmhl_opt( 'eyebrow' ) ) : ?>
				<span class="mm-eyebrow"><?php echo esc_html( (string) mmhl_opt( 'eyebrow' ) ); ?></span>
			<?php endif; ?>
			<h1><?php echo esc_html( str_replace( '*', '', $mmhl_title ) ); ?></h1>
			<?php echo wp_kses_post( wpautop( (string) mmhl_opt( 'intro' ) ) ); ?>
			<?php echo $mmhl_actions; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</header>
	<?php
}
?>
<main id="mm-main" class="mm-main mmhl-main" tabindex="-1">
	<section id="hosting-plans" class="mmhl-section">
		<div class="mm-container">
			<?php echo mmhl_grid(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>

	<?php if ( '' !== trim( (string) mmhl_opt( 'below' ) ) ) : ?>
		<section class="mmhl-section mmhl-below">
			<div class="mm-container">
				<div class="entry-content mm-page-content mm-prose"><?php echo do_shortcode( wpautop( wp_kses_post( (string) mmhl_opt( 'below' ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	if ( mmhl_opt( 'page_content' ) ) {
		while ( have_posts() ) {
			the_post();
			echo '<div class="mm-premium__content entry-content mmhl-page-content">';
			the_content();
			echo '</div>';
		}
	}
	?>
</main>
<?php
get_footer();
