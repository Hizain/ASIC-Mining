<?php
/**
 * Mobile navigation drawer.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_location = has_nav_menu( 'mobile' ) ? 'mobile' : 'primary';
?>
<div class="mm-drawer" id="mm-drawer" data-mm-drawer inert>
	<div class="mm-drawer__backdrop" data-mm-drawer-close></div>
	<aside class="mm-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'minemirth' ); ?>">
		<div class="mm-drawer__head">
			<a class="mm-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo mm_logo( 'drawer' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<button class="mm-icon-btn" type="button" data-mm-drawer-close aria-label="<?php esc_attr_e( 'Close menu', 'minemirth' ); ?>"><?php echo mm_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</div>

		<form class="mm-drawer__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php echo mm_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<label class="screen-reader-text" for="mm-drawer-s"><?php esc_html_e( 'Search', 'minemirth' ); ?></label>
			<input id="mm-drawer-s" type="search" name="s" placeholder="<?php echo esc_attr( (string) mm_opt( 'search_placeholder' ) ); ?>">
		</form>

		<nav class="mm-drawer__nav" aria-label="<?php esc_attr_e( 'Mobile', 'minemirth' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => $mm_location,
					'container'      => false,
					'menu_class'     => 'mm-menu mm-menu--drawer',
					'depth'          => 3,
					'walker'         => new MM_Nav_Walker(),
					'fallback_cb'    => 'mm_menu_fallback',
				)
			);
			?>
		</nav>

		<?php if ( mm_is_woo() ) : ?>
			<div class="mm-drawer__quick">
				<a href="<?php echo esc_url( mm_account_url() ); ?>"><?php echo mm_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( is_user_logged_in() ? __( 'My account', 'minemirth' ) : __( 'Sign in', 'minemirth' ) ); ?></span></a>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php echo mm_icon( 'bag' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Cart', 'minemirth' ); ?></span></a>
			</div>
		<?php endif; ?>

		<?php if ( mm_opt( 'mode_toggle' ) ) : ?>
			<div class="mm-drawer__mode">
				<span class="mm-drawer__label"><?php esc_html_e( 'Appearance', 'minemirth' ); ?></span>
				<div class="mm-segmented" role="radiogroup" aria-label="<?php esc_attr_e( 'Color mode', 'minemirth' ); ?>">
					<button type="button" role="radio" data-mm-mode-set="light" aria-checked="false"><?php echo mm_icon( 'sun' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Light', 'minemirth' ); ?></span></button>
					<button type="button" role="radio" data-mm-mode-set="system" aria-checked="false"><?php echo mm_icon( 'monitor' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Auto', 'minemirth' ); ?></span></button>
					<button type="button" role="radio" data-mm-mode-set="dark" aria-checked="false"><?php echo mm_icon( 'moon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Dark', 'minemirth' ); ?></span></button>
				</div>
			</div>
		<?php endif; ?>

		<div class="mm-drawer__foot">
			<?php if ( mm_opt( 'header_cta' ) ) : ?>
				<?php echo mm_button( mm_opt( 'header_cta_text' ), mm_opt( 'header_cta_url' ), 'primary', array( 'class' => 'mm-btn--block', 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
			<?php echo mm_social_links( 'mm-social--drawer' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</aside>
</div>
