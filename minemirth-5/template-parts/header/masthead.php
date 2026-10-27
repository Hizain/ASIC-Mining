<?php
/**
 * Main header bar.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_has_woo = mm_is_woo();
$mm_count   = ( $mm_has_woo && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
?>
<header class="mm-masthead" data-mm-header>
	<div class="mm-container mm-masthead__inner">
		<a class="mm-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> <?php esc_attr_e( 'home', 'minemirth' ); ?>">
			<?php echo mm_logo( 'header' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</a>

		<nav class="mm-nav" aria-label="<?php esc_attr_e( 'Primary', 'minemirth' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'mm-menu',
					'depth'          => 3,
					'walker'         => new MM_Nav_Walker(),
					'fallback_cb'    => 'mm_menu_fallback',
				)
			);
			?>
		</nav>

		<div class="mm-actions">
			<?php if ( mm_opt( 'header_search' ) ) : ?>
				<button class="mm-icon-btn mm-search-trigger" type="button" data-mm-search-open aria-label="<?php esc_attr_e( 'Search', 'minemirth' ); ?>">
					<?php echo mm_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<kbd class="mm-kbd" aria-hidden="true"><span data-mm-kbd-mod>Ctrl</span>K</kbd>
				</button>
			<?php endif; ?>

			<?php if ( mm_opt( 'mode_toggle' ) ) : ?>
				<button class="mm-icon-btn mm-mode-toggle" type="button" data-mm-mode-toggle aria-pressed="false" aria-label="<?php esc_attr_e( 'Switch to dark mode', 'minemirth' ); ?>">
					<span class="mm-mode-toggle__icons" aria-hidden="true">
						<?php echo mm_icon( 'sun', 'mm-mode-toggle__sun' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo mm_icon( 'moon', 'mm-mode-toggle__moon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</span>
				</button>
			<?php endif; ?>

			<?php if ( $mm_has_woo && mm_opt( 'header_account' ) ) : ?>
				<a class="mm-icon-btn mm-hide-sm" href="<?php echo esc_url( mm_account_url() ); ?>" aria-label="<?php echo esc_attr( is_user_logged_in() ? __( 'My account', 'minemirth' ) : __( 'Sign in', 'minemirth' ) ); ?>">
					<?php echo mm_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</a>
			<?php endif; ?>

			<?php if ( $mm_has_woo && mm_opt( 'header_cart' ) ) : ?>
				<a class="mm-icon-btn mm-cart-trigger" href="<?php echo esc_url( wc_get_cart_url() ); ?>" <?php echo mm_opt( 'cart_drawer' ) ? 'data-mm-cart-open' : ''; ?> aria-label="<?php esc_attr_e( 'Cart', 'minemirth' ); ?>">
					<?php echo mm_icon( 'bag' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="mm-cart-count<?php echo $mm_count ? '' : ' is-empty'; ?>" data-mm-cart-count><?php echo esc_html( (string) $mm_count ); ?></span>
				</a>
			<?php endif; ?>

			<?php if ( mm_opt( 'header_cta' ) ) : ?>
				<?php echo mm_button( mm_opt( 'header_cta_text' ), mm_opt( 'header_cta_url' ), 'primary', array( 'size' => 'sm', 'class' => 'mm-header-cta mm-hide-md', 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>

			<button class="mm-icon-btn mm-burger" type="button" data-mm-drawer-open aria-controls="mm-drawer" aria-expanded="false" aria-label="<?php esc_attr_e( 'Open menu', 'minemirth' ); ?>">
				<span class="mm-burger__lines" aria-hidden="true"><span></span><span></span><span></span></span>
			</button>
		</div>
	</div>
</header>
