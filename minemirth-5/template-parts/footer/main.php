<?php
/**
 * Main footer.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_email   = (string) mm_opt( 'contact_email' );
$mm_phone   = (string) mm_opt( 'contact_phone' );
$mm_address = (string) mm_opt( 'contact_address' );
$mm_hours   = (string) mm_opt( 'contact_hours' );
?>
<footer class="mm-footer<?php echo 'auto' !== mm_opt( 'footer_style' ) ? ' mm-scheme-dark' : ''; ?>" role="contentinfo">
	<div class="mm-footer__fx" aria-hidden="true"></div>
	<?php echo function_exists( 'mm_deco' ) ? mm_deco( 'chain' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<div class="mm-container">
		<?php if ( mm_opt( 'footer_live' ) && mm_opt( 'market_enable' ) ) : ?>
			<?php $mm_m = mm_market_display(); ?>
			<div class="mm-footer__live" data-mm-market>
				<span class="mm-footer__live-status"><span class="mm-live-dot" aria-hidden="true"></span><?php esc_html_e( 'Bitcoin network', 'minemirth' ); ?></span>
				<span><?php esc_html_e( 'Block', 'minemirth' ); ?> <strong data-mm-m="height"><?php echo esc_html( $mm_m['height'] ); ?></strong></span>
				<span><?php esc_html_e( 'Hashrate', 'minemirth' ); ?> <strong data-mm-m="hashrate"><?php echo esc_html( $mm_m['hashrate'] ); ?></strong></span>
				<span><?php esc_html_e( 'Difficulty', 'minemirth' ); ?> <strong data-mm-m="difficulty"><?php echo esc_html( $mm_m['difficulty'] ); ?></strong></span>
				<span class="mm-hide-sm"><?php esc_html_e( 'Next adjustment', 'minemirth' ); ?> <strong data-mm-m="adjustment"><?php echo esc_html( $mm_m['adjustment'] ); ?></strong></span>
				<span class="mm-footer__live-time mm-hide-sm" data-mm-m="updated"><?php echo esc_html( $mm_m['updated'] ); ?></span>
			</div>
		<?php endif; ?>

		<div class="mm-footer__grid">
			<div class="mm-footer__brand">
				<a class="mm-logo mm-logo--footer" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo mm_logo( 'footer' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<?php if ( mm_opt( 'footer_about' ) ) : ?>
					<p class="mm-footer__about"><?php echo wp_kses_post( (string) mm_opt( 'footer_about' ) ); ?></p>
				<?php endif; ?>
				<?php if ( $mm_email || $mm_phone || $mm_address || $mm_hours ) : ?>
					<ul class="mm-footer__contact">
						<?php if ( $mm_email ) : ?>
							<li><?php echo mm_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><a href="mailto:<?php echo esc_attr( antispambot( $mm_email ) ); ?>"><?php echo esc_html( antispambot( $mm_email ) ); ?></a></li>
						<?php endif; ?>
						<?php if ( $mm_phone ) : ?>
							<li><?php echo mm_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $mm_phone ) ); ?>"><?php echo esc_html( $mm_phone ); ?></a></li>
						<?php endif; ?>
						<?php if ( $mm_address ) : ?>
							<li><?php echo mm_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo nl2br( esc_html( $mm_address ) ); ?></span></li>
						<?php endif; ?>
						<?php if ( $mm_hours ) : ?>
							<li><?php echo mm_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $mm_hours ); ?></span></li>
						<?php endif; ?>
					</ul>
				<?php endif; ?>
				<?php echo mm_social_links(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>

			<?php for ( $mm_col = 1; $mm_col <= 3; $mm_col++ ) : ?>
				<div class="mm-footer__col">
					<h2 class="mm-footer__title"><?php echo esc_html( (string) mm_opt( 'footer_col' . $mm_col . '_title' ) ); ?></h2>
					<?php if ( has_nav_menu( 'footer-' . $mm_col ) ) : ?>
						<?php
						wp_nav_menu(
							array(
								'theme_location' => 'footer-' . $mm_col,
								'container'      => false,
								'menu_class'     => 'mm-footer__links',
								'depth'          => 1,
							)
						);
						?>
					<?php else : ?>
						<ul class="mm-footer__links">
							<?php foreach ( mm_footer_fallback_links( $mm_col ) as $mm_url => $mm_label ) : ?>
								<li><a href="<?php echo esc_url( $mm_url ); ?>"><?php echo esc_html( $mm_label ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endfor; ?>

			<?php if ( mm_opt( 'footer_newsletter' ) ) : ?>
				<div class="mm-footer__col mm-footer__newsletter">
					<h2 class="mm-footer__title"><?php echo esc_html( (string) mm_opt( 'footer_newsletter_title' ) ); ?></h2>
					<p><?php echo esc_html( (string) mm_opt( 'footer_newsletter_text' ) ); ?></p>
					<?php mm_newsletter_form( array( 'context' => 'footer', 'compact' => true ) ); ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="mm-footer__bottom">
			<p class="mm-footer__copy"><?php echo esc_html( mm_tokens( mm_opt( 'footer_copyright' ) ) ); ?></p>
			<?php
			if ( has_nav_menu( 'footer-bottom' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'footer-bottom',
						'container'      => 'nav',
						'container_class'=> 'mm-footer__legal',
						'menu_class'     => 'mm-footer__inline',
						'depth'          => 1,
					)
				);
			}
			if ( mm_opt( 'footer_payments' ) ) {
				echo mm_payment_badges(); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</div>

		<?php if ( mm_opt( 'footer_disclaimer' ) ) : ?>
			<p class="mm-footer__disclaimer"><?php echo wp_kses_post( (string) mm_opt( 'footer_disclaimer' ) ); ?></p>
		<?php endif; ?>
	</div>
	<div class="mm-footer__wordmark" aria-hidden="true"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
</footer>
