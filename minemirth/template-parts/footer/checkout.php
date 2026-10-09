<?php
/**
 * Minimal checkout footer.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_links = array();
foreach ( array( 'terms' => __( 'Terms', 'minemirth' ) ) as $mm_page => $mm_label ) {
	$mm_id = function_exists( 'wc_terms_and_conditions_page_id' ) ? wc_terms_and_conditions_page_id() : 0;
	if ( $mm_id ) {
		$mm_links[ get_permalink( $mm_id ) ] = $mm_label;
	}
}
if ( get_privacy_policy_url() ) {
	$mm_links[ get_privacy_policy_url() ] = __( 'Privacy', 'minemirth' );
}
$mm_links[ mm_url( (string) mm_opt( 'contact_url' ) ) ] = __( 'Help', 'minemirth' );
?>
<footer class="mm-footer mm-footer--checkout" role="contentinfo">
	<div class="mm-container mm-footer__bottom">
		<p class="mm-footer__copy"><?php echo esc_html( mm_tokens( mm_opt( 'footer_copyright' ) ) ); ?></p>
		<ul class="mm-footer__inline">
			<?php foreach ( $mm_links as $mm_url => $mm_label ) : ?>
				<li><a href="<?php echo esc_url( $mm_url ); ?>"><?php echo esc_html( $mm_label ); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<?php echo mm_payment_badges(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
</footer>
