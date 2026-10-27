<?php
/**
 * Template Name: Miner Hosting
 * Template Post Type: page
 *
 * Same file path as the previous theme, so an existing hosting page keeps
 * this template automatically.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

get_header();

ob_start();
?>
<div class="mm-hosting-specs">
	<div><?php echo mm_icon( 'server' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong><?php esc_html_e( 'Facility', 'minemirth' ); ?></strong><small><?php esc_html_e( 'Region and capacity shown on every plan', 'minemirth' ); ?></small></div>
	<div><?php echo mm_icon( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong><?php esc_html_e( 'Power', 'minemirth' ); ?></strong><small><?php esc_html_e( 'All-in rate and billing basis stated up front', 'minemirth' ); ?></small></div>
	<div><?php echo mm_icon( 'shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong><?php esc_html_e( 'Agreement', 'minemirth' ); ?></strong><small><?php esc_html_e( 'Terms confirmed in writing before shipment', 'minemirth' ); ?></small></div>
</div>
<?php
$mm_after = (string) ob_get_clean();

mm_page_hero(
	array(
		'eyebrow' => __( 'Partner-facility hosting', 'minemirth' ),
		'title'   => (string) mm_opt( 'hostpage_title' ),
		'text'    => (string) mm_opt( 'hostpage_text' ),
		'actions' => mm_button( __( 'View plans', 'minemirth' ), '#hosting-plans', 'primary', array( 'icon' => 'arrow-down' ) ) . mm_button( __( 'Shop hosting-ready miners', 'minemirth' ), '{shop}', 'glass' ),
		'after'   => $mm_after,
	)
);
?>
<main id="mm-main" class="mm-main" tabindex="-1">
	<section id="hosting-plans" class="mm-section" style="padding-top:clamp(32px,5vw,64px)">
		<div class="mm-container">
			<div class="mm-section-head">
				<div class="mm-section-head__copy">
					<span class="mm-eyebrow"><?php esc_html_e( 'Published options', 'minemirth' ); ?></span>
					<h2 class="mm-section-title"><?php echo mm_highlight( __( 'Current *hosting plans*', 'minemirth' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
				</div>
			</div>
			<?php echo mm_hosting_plans_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( mm_opt( 'hostpage_note' ) ) : ?>
				<p class="mm-plans-note"><?php echo esc_html( (string) mm_opt( 'hostpage_note' ) ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<?php get_template_part( 'template-parts/home/process' ); ?>

	<section class="mm-section mm-section--alt">
		<div class="mm-container mm-split">
			<div>
				<span class="mm-eyebrow"><?php esc_html_e( 'Cost scenario', 'minemirth' ); ?></span>
				<h2 class="mm-section-title"><?php echo mm_highlight( __( 'Model your *monthly hosting cost*', 'minemirth' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
				<p class="mm-section-sub"><?php esc_html_e( 'No hidden multipliers: enter the rate and fees you expect and keep every assumption visible. Then compare against expected revenue in the profitability calculator.', 'minemirth' ); ?></p>
				<div class="mm-btn-row" style="margin-top:28px"><?php echo mm_button( mm_opt( 'calc_btn_text' ), mm_opt( 'calc_btn_url' ), 'ghost', array( 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</div>
			<div><?php echo mm_hosting_calculator_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		</div>
	</section>

	<?php
	while ( have_posts() ) :
		the_post();
		if ( '' !== trim( get_the_content() ) ) :
			?>
			<section class="mm-section">
				<div class="mm-container"><div class="entry-content mm-page-content mm-prose"><?php the_content(); ?></div></div>
			</section>
			<?php
		endif;
	endwhile;
	?>

	<?php get_template_part( 'template-parts/home/faq' ); ?>
</main>
<?php
get_footer();
