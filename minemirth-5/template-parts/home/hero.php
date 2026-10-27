<?php
/**
 * Homepage hero.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

// A page can pass its own text and visual (Mine Mirth section "Animated hero"); anything it
// leaves out comes from Customize › Mine Mirth · Homepage › Hero.
$mm_a      = isset( $args ) && is_array( $args ) ? array_filter( $args, static fn( $v ) => '' !== $v && null !== $v && array() !== $v ) : array();
$mm_o      = static fn( string $k, string $opt ) => $mm_a[ $k ] ?? mm_opt( $opt );
$mm_layout = (string) $mm_o( 'layout', 'hero_layout' );
$mm_visual = (string) $mm_o( 'visual', 'hero_visual' );
$mm_bg     = (string) $mm_o( 'background', 'hero_background' );
$mm_words  = mm_list( $mm_o( 'rotate', 'hero_rotate' ) );
$mm_title  = (string) $mm_o( 'title', 'hero_title' );
$mm_product = mm_hero_product();
$mm_m       = mm_market_display();
$mm_video   = (string) mm_opt( 'hero_video' );

// Headline: escape, apply *highlight*, then swap {rotate} for the word rotator.
$mm_title_html = mm_highlight( $mm_title );
if ( $mm_words && false !== strpos( $mm_title_html, '{rotate}' ) ) {
	$mm_rotator    = '<span class="mm-rotator" data-mm-rotate="' . esc_attr( wp_json_encode( $mm_words ) ) . '"><span class="mm-rotator__word is-active">' . esc_html( $mm_words[0] ) . '</span></span>';
	$mm_title_html = str_replace( '{rotate}', $mm_rotator, $mm_title_html );
} else {
	$mm_title_html = str_replace( '{rotate}', '', $mm_title_html );
}

$mm_classes = array(
	'mm-hero',
	'mm-hero--' . $mm_layout,
	'mm-hero--visual-' . $mm_visual,
	'mm-hero--bg-' . $mm_bg,
);
?>
<section id="mm-section-hero" class="<?php echo esc_attr( implode( ' ', $mm_classes ) ); ?>" data-mm-hero aria-labelledby="mm-hero-title">
	<div class="mm-hero__bg" aria-hidden="true">
		<div class="mm-hero__grid"></div>
		<?php if ( 'aurora' === $mm_bg || 'beams' === $mm_bg ) : ?>
			<div class="mm-hero__aurora"><span></span><span></span><span></span></div>
		<?php endif; ?>
		<?php if ( 'beams' === $mm_bg ) : ?>
			<div class="mm-hero__beams"><i></i><i></i><i></i><i></i><i></i><i></i></div>
		<?php endif; ?>
		<?php if ( 'particles' === $mm_bg ) : ?>
			<canvas class="mm-hero__particles" data-mm-particles></canvas>
		<?php endif; ?>
		<?php if ( 'video' === $mm_visual && $mm_video ) : ?>
			<video class="mm-hero__video" src="<?php echo esc_url( $mm_video ); ?>" autoplay muted loop playsinline preload="metadata"></video>
		<?php endif; ?>
		<div class="mm-hero__fade"></div>
	</div>

	<div class="mm-container mm-hero__inner">
		<div class="mm-hero__copy">
			<?php if ( $mm_o( 'eyebrow', 'hero_eyebrow' ) ) : ?>
				<p class="mm-hero__eyebrow mm-hero__in" style="--i:0">
					<span class="mm-live-dot" aria-hidden="true"></span>
					<span><?php echo esc_html( (string) $mm_o( 'eyebrow', 'hero_eyebrow' ) ); ?></span>
				</p>
			<?php endif; ?>

			<h1 id="mm-hero-title" class="mm-hero__title" data-mm-split><?php echo $mm_title_html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by mm_highlight(). ?></h1>

			<?php if ( $mm_o( 'text', 'hero_text' ) ) : ?>
				<p class="mm-hero__text mm-hero__in" style="--i:3"><?php echo function_exists( 'mm_s_live' ) ? mm_s_live( wp_kses_post( (string) $mm_o( 'text', 'hero_text' ) ) ) : wp_kses_post( (string) $mm_o( 'text', 'hero_text' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<?php endif; ?>

			<div class="mm-btn-row mm-hero__in" style="--i:4">
				<?php
				echo mm_button( $mm_o( 'btn1_text', 'hero_btn1_text' ), function_exists( 'mm_s_url' ) && isset( $mm_a['btn1_url'] ) ? mm_s_url( $mm_a['btn1_url'] ) : mm_opt( 'hero_btn1_url' ), 'primary', array( 'size' => 'lg', 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo mm_button( $mm_o( 'btn2_text', 'hero_btn2_text' ), function_exists( 'mm_s_url' ) && isset( $mm_a['btn2_url'] ) ? mm_s_url( $mm_a['btn2_url'] ) : mm_opt( 'hero_btn2_url' ), 'glass', array( 'size' => 'lg' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>

			<?php $mm_trust = isset( $mm_a['trust'] ) ? (array) $mm_a['trust'] : mm_lines( mm_opt( 'hero_trust' ) ); ?>
			<?php if ( $mm_trust ) : ?>
				<ul class="mm-hero__trust mm-hero__in" style="--i:5">
					<?php foreach ( $mm_trust as $mm_point ) : ?>
						<li><?php echo mm_icon( 'check-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $mm_point ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<?php if ( ! in_array( $mm_visual, array( 'none', 'video' ), true ) ) : ?>
			<div class="mm-hero__visual" <?php echo mm_opt( 'fx_tilt' ) ? 'data-mm-tilt' : ''; ?>>
				<div class="mm-hero__stage">
					<?php if ( 'sphere' === $mm_visual ) : ?>
						<div class="mm-orb" aria-hidden="true">
							<div class="mm-orb__halo"></div>
							<div class="mm-orb__ring mm-orb__ring--1"></div>
							<div class="mm-orb__ring mm-orb__ring--2"></div>
							<div class="mm-orb__ring mm-orb__ring--3"><i></i></div>
							<canvas class="mm-orb__canvas" data-mm-sphere></canvas>
							<div class="mm-orb__core"><?php echo mm_icon( 'bitcoin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
						</div>
					<?php elseif ( 'rig' === $mm_visual ) : ?>
						<?php get_template_part( 'template-parts/home/hero-rig' ); ?>
					<?php elseif ( 'product' === $mm_visual && $mm_product ) : ?>
						<a class="mm-showcase" href="<?php echo esc_url( $mm_product->get_permalink() ); ?>">
							<span class="mm-showcase__glow" aria-hidden="true"></span>
							<span class="mm-showcase__ring" aria-hidden="true"></span>
							<?php echo $mm_product->get_image( 'woocommerce_single', array( 'class' => 'mm-showcase__img', 'loading' => 'eager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span class="mm-showcase__name"><?php echo esc_html( $mm_product->get_name() ); ?></span>
						</a>
					<?php elseif ( 'image' === $mm_visual && mm_opt( 'hero_image' ) ) : ?>
						<div class="mm-hero__image"><?php echo mm_image( mm_opt( 'hero_image' ), 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php else : ?>
						<div class="mm-orb" aria-hidden="true">
							<div class="mm-orb__halo"></div>
							<div class="mm-orb__ring mm-orb__ring--1"></div>
							<div class="mm-orb__ring mm-orb__ring--2"></div>
							<canvas class="mm-orb__canvas" data-mm-sphere></canvas>
						</div>
					<?php endif; ?>

					<?php if ( mm_opt( 'hero_live_cards' ) && mm_opt( 'market_enable' ) ) : ?>
						<div class="mm-float mm-float--price" data-mm-market data-mm-depth="1.4">
							<div class="mm-float__head">
								<span class="mm-float__icon mm-float__icon--btc" aria-hidden="true">₿</span>
								<span class="mm-float__label"><?php esc_html_e( 'Bitcoin', 'minemirth' ); ?></span>
								<span class="mm-change <?php echo esc_attr( $mm_m['change_class'] ); ?>" data-mm-m="change"><?php echo esc_html( $mm_m['change'] ); ?></span>
							</div>
							<strong class="mm-float__value" data-mm-m="price"><?php echo esc_html( $mm_m['price'] ); ?></strong>
							<svg class="mm-spark" viewBox="0 0 120 32" preserveAspectRatio="none" data-mm-spark aria-hidden="true"><path d="<?php echo esc_attr( $mm_m['spark_path'] ); ?>"/></svg>
						</div>

						<div class="mm-float mm-float--network" data-mm-market data-mm-depth="0.9">
							<span class="mm-float__label"><?php echo mm_icon( 'activity' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Network hashrate', 'minemirth' ); ?></span>
							<strong class="mm-float__value" data-mm-m="hashrate"><?php echo esc_html( $mm_m['hashrate'] ); ?></strong>
							<div class="mm-float__row">
								<span><?php esc_html_e( 'Difficulty', 'minemirth' ); ?></span>
								<b data-mm-m="difficulty"><?php echo esc_html( $mm_m['difficulty'] ); ?></b>
							</div>
						</div>

						<div class="mm-float mm-float--block" data-mm-market data-mm-depth="1.8">
							<span class="mm-float__cube" aria-hidden="true"><?php echo mm_icon( 'cube' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span>
								<small><?php esc_html_e( 'Latest block', 'minemirth' ); ?></small>
								<b data-mm-m="height"><?php echo esc_html( $mm_m['height'] ); ?></b>
							</span>
						</div>
					<?php endif; ?>

					<?php if ( $mm_product && 'product' !== $mm_visual ) : ?>
						<a class="mm-float mm-float--product" href="<?php echo esc_url( $mm_product->get_permalink() ); ?>" data-mm-depth="1.2">
							<span class="mm-float__thumb"><?php echo $mm_product->get_image( 'woocommerce_gallery_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span class="mm-float__product">
								<small class="<?php echo $mm_product->is_in_stock() ? 'is-in' : 'is-out'; ?>"><?php echo esc_html( $mm_product->is_in_stock() ? __( 'In stock', 'minemirth' ) : __( 'Pre-order', 'minemirth' ) ); ?></small>
								<b><?php echo esc_html( $mm_product->get_name() ); ?></b>
								<span class="mm-float__price"><?php echo wp_kses_post( $mm_product->get_price_html() ); ?></span>
							</span>
						</a>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<a class="mm-hero__scroll" href="#mm-hero-next" aria-label="<?php esc_attr_e( 'Scroll to content', 'minemirth' ); ?>">
		<?php echo mm_icon( 'mouse' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</a>
	<span id="mm-hero-next" class="mm-hero__anchor"></span>
</section>
