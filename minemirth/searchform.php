<?php
/**
 * Search form.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_id = wp_unique_id( 'mm-s-' );
?>
<form role="search" method="get" class="mm-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $mm_id ); ?>"><?php esc_html_e( 'Search for:', 'minemirth' ); ?></label>
	<?php echo mm_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<input type="search" id="<?php echo esc_attr( $mm_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php echo esc_attr( (string) mm_opt( 'search_placeholder' ) ); ?>">
	<button class="mm-btn mm-btn--primary mm-btn--sm" type="submit"><span><?php esc_html_e( 'Search', 'minemirth' ); ?></span></button>
</form>
