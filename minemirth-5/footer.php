<?php
/**
 * Site footer.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_focus = mm_is_checkout_focus();

if ( ! $mm_focus && mm_opt( 'footer_cta' ) && ! is_front_page() && ! ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) ) {
	get_template_part( 'template-parts/footer/cta' );
}

if ( $mm_focus ) {
	get_template_part( 'template-parts/footer/checkout' );
} else {
	get_template_part( 'template-parts/footer/main' );
}

if ( mm_opt( 'back_to_top' ) ) :
	?>
	<button class="mm-to-top" type="button" data-mm-to-top aria-label="<?php esc_attr_e( 'Back to top', 'minemirth' ); ?>">
		<svg class="mm-to-top__ring" viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="24" r="22" pathLength="100"/></svg>
		<?php echo mm_icon( 'arrow-up' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</button>
	<?php
endif;

if ( ! $mm_focus ) {
	get_template_part( 'template-parts/header/drawer' );
	get_template_part( 'template-parts/header/search' );
}
?>
<div class="mm-toasts" data-mm-toasts aria-live="polite" aria-atomic="false"></div>
</div><!-- #mm-page -->
<?php wp_footer(); ?>
</body>
</html>
