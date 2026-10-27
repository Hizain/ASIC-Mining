<?php
/**
 * Announcement bar.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

if ( ! mm_opt( 'announce_enable' ) ) {
	if ( is_customize_preview() ) {
		echo '<div id="mm-announcement" hidden></div>';
	}
	return;
}

$mm_text = (string) mm_opt( 'announce_text' );
$mm_link = (string) mm_opt( 'announce_link_text' );
$mm_url  = mm_url( mm_opt( 'announce_link' ) );
?>
<div id="mm-announcement" class="mm-announce mm-announce--<?php echo esc_attr( (string) mm_opt( 'announce_style' ) ); ?>" data-mm-announce="<?php echo esc_attr( mm_announce_id() ); ?>">
	<div class="mm-container mm-announce__inner">
		<p class="mm-announce__text">
			<span class="mm-live-dot" aria-hidden="true"></span>
			<span class="mm-announce__msg"><?php echo esc_html( $mm_text ); ?></span>
			<?php if ( $mm_link && $mm_url ) : ?>
				<a class="mm-announce__link" href="<?php echo esc_url( $mm_url ); ?>"><?php echo esc_html( $mm_link ); ?><?php echo mm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<?php endif; ?>
		</p>
		<?php if ( mm_opt( 'announce_live' ) && mm_opt( 'market_enable' ) ) : ?>
			<?php $mm_m = mm_market_display(); ?>
			<p class="mm-announce__live" data-mm-market>
				<span class="mm-announce__coin" aria-hidden="true">₿</span>
				<span class="screen-reader-text"><?php esc_html_e( 'Bitcoin price:', 'minemirth' ); ?></span>
				<strong data-mm-m="price"><?php echo esc_html( $mm_m['price'] ); ?></strong>
				<span class="mm-change <?php echo esc_attr( $mm_m['change_class'] ); ?>" data-mm-m="change"><?php echo esc_html( $mm_m['change'] ); ?></span>
			</p>
		<?php endif; ?>
		<?php if ( mm_opt( 'announce_dismissible' ) ) : ?>
			<button class="mm-announce__close" type="button" data-mm-announce-close aria-label="<?php esc_attr_e( 'Dismiss announcement', 'minemirth' ); ?>"><?php echo mm_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<?php endif; ?>
	</div>
</div>
