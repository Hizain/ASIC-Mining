<?php
/**
 * My Account navigation — Mine Mirth layout (profile card + icon menu).
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mm_user = wp_get_current_user();

do_action( 'woocommerce_before_account_navigation' );
?>

<nav class="woocommerce-MyAccount-navigation mm-account-nav" aria-label="<?php esc_attr_e( 'Account pages', 'woocommerce' ); ?>">
	<div class="mm-account-nav__profile">
		<?php echo get_avatar( $mm_user->ID, 96, '', '', array( 'class' => 'mm-account-nav__avatar' ) ); ?>
		<div>
			<strong><?php echo esc_html( $mm_user->display_name ); ?></strong>
			<small><?php echo esc_html( $mm_user->user_email ); ?></small>
		</div>
	</div>
	<ul>
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
			<?php
			$mm_current = function_exists( 'wc_is_current_account_menu_item' )
				? wc_is_current_account_menu_item( $endpoint )
				: false !== strpos( wc_get_account_menu_item_classes( $endpoint ), 'is-active' );
			?>
			<li class="<?php echo wc_get_account_menu_item_classes( $endpoint ); // phpcs:ignore WordPress.Security.EscapeOutput ?>">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>" <?php echo $mm_current ? 'aria-current="page"' : ''; ?>>
					<?php echo mm_account_icon( $endpoint ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php echo esc_html( $label ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
