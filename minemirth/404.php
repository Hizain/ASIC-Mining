<?php
/**
 * Not found.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="mm-main" class="mm-main" tabindex="-1">
	<section class="mm-404">
		<div class="mm-container">
			<div class="mm-404__code" aria-hidden="true">404</div>
			<h1><?php echo esc_html( (string) mm_opt( 'notfound_title' ) ); ?></h1>
			<p><?php echo esc_html( (string) mm_opt( 'notfound_text' ) ); ?></p>
			<div class="mm-404__search"><?php get_search_form(); ?></div>
			<div class="mm-btn-row">
				<?php
				echo mm_button( __( 'Back to home', 'minemirth' ), '{home}', 'primary', array( 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				if ( mm_is_woo() ) {
					echo mm_button( __( 'Browse miners', 'minemirth' ), '{shop}', 'ghost' ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
