<?php
/**
 * The product image gallery: a main image at a fixed shape (nothing moves while the page loads),
 * a thumbnail carousel under it (or beside it), swipe, arrows, hover zoom and a full-screen view.
 *
 * It replaces WooCommerce's gallery and its slider, zoom and lightbox scripts on product pages.
 * Those scripts are what made the page sit still and then jump: the slider rebuilt the gallery
 * after the page had loaded, hiding the photo and adding the thumbnails late.
 *
 * @package MineMirthProductPage
 */

defined( 'ABSPATH' ) || exit;

// Turn off WooCommerce's gallery scripts (after the theme has turned them on).
add_action(
	'after_setup_theme',
	static function (): void {
		if ( mmpp_opt( 'gallery' ) ) {
			remove_theme_support( 'wc-product-gallery-zoom' );
			remove_theme_support( 'wc-product-gallery-lightbox' );
			remove_theme_support( 'wc-product-gallery-slider' );
		}
	},
	100
);

add_action(
	'wp',
	static function (): void {
		if ( ! mmpp_opt( 'gallery' ) || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
		add_action( 'woocommerce_before_single_product_summary', 'mmpp_gallery', 20 );
	}
);

/** Image IDs of a product: main image first, then the gallery. */
function mmpp_image_ids( WC_Product $product ): array {
	return array_values( array_unique( array_filter( array_map( 'absint', array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) ) ) );
}

function mmpp_gallery(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$s       = mmpp_settings();
	$ids     = mmpp_image_ids( $product );
	$count   = count( $ids );
	$name    = $product->get_name();
	$classes = array(
		'woocommerce-product-gallery',
		'images',
		'mmpp-gallery',
		'mmpp-gallery--thumbs-' . ( $count > 1 ? $s['thumbs'] : 'none' ),
		'mmpp-gallery--arrows-' . $s['arrows'],
		'mmpp-gallery--bg-' . $s['background'],
		'mmpp-gallery--radius-' . $s['radius'],
		'mmpp-gallery--fit-' . $s['fit'],
		'mmpp-gallery--mobile-' . $s['mobile_nav'],
	);
	$style = '--mmpp-ratio:' . esc_attr( str_replace( '/', ' / ', (string) $s['ratio'] ) ) . ';--mmpp-thumbs:' . max( 3, min( 8, (int) $s['thumbs_count'] ) ) . ';--mmpp-zoom:' . max( 1.5, min( 4, (float) $s['zoom_level'] ) );
	$data  = array(
		'data-mmpp-gallery' => '1',
		'data-loop'         => $s['loop'] ? '1' : '0',
		'data-autoplay'     => (string) max( 0, (int) $s['autoplay'] ),
		'data-zoom'         => $s['zoom'] ? '1' : '0',
		'data-lightbox'     => $s['lightbox'] ? '1' : '0',
	);
	$attrs = '';
	foreach ( $data as $k => $v ) {
		$attrs .= ' ' . $k . '="' . esc_attr( $v ) . '"';
	}
	?>
	<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="<?php echo esc_attr( $style ); ?>"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-roledescription="carousel" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( '%s images', 'mine-mirth-product-page' ), $name ) ); ?>">
		<div class="mmpp-stage">
			<div class="mmpp-viewport woocommerce-product-gallery__wrapper" tabindex="0">
				<?php if ( ! $ids ) : ?>
					<div class="mmpp-slide is-active woocommerce-product-gallery__image--placeholder"><?php echo wc_placeholder_img( 'woocommerce_single' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<?php endif; ?>
				<?php foreach ( $ids as $i => $id ) : ?>
					<?php
					$full = wp_get_attachment_image_src( $id, 'full' );
					$alt  = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
					$alt  = '' !== $alt ? $alt : ( 0 === $i ? $name : sprintf( /* translators: 1: product, 2: image number */ __( '%1$s, image %2$d', 'mine-mirth-product-page' ), $name, $i + 1 ) );
					$img  = wp_get_attachment_image(
						$id,
						'woocommerce_single',
						false,
						array(
							'class'                   => 0 === $i ? 'wp-post-image' : '',
							'alt'                     => $alt,
							'loading'                 => 0 === $i ? 'eager' : 'lazy',
							'fetchpriority'           => 0 === $i ? 'high' : 'low',
							'decoding'                => 'async',
							'sizes'                   => '(max-width: 900px) 100vw, 640px',
							'draggable'               => 'false',
							'data-large_image'        => $full ? $full[0] : '',
							'data-large_image_width'  => $full ? (string) $full[1] : '',
							'data-large_image_height' => $full ? (string) $full[2] : '',
						)
					);
					?>
					<div class="mmpp-slide<?php echo 0 === $i ? ' is-active woocommerce-product-gallery__image' : ''; ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( sprintf( /* translators: 1: number, 2: total */ __( '%1$d of %2$d', 'mine-mirth-product-page' ), $i + 1, $count ) ); ?>"<?php echo 0 !== $i ? ' aria-hidden="true"' : ''; ?>>
						<a class="mmpp-slide__link" href="<?php echo esc_url( $full ? $full[0] : '' ); ?>" data-w="<?php echo (int) ( $full[1] ?? 0 ); ?>" data-h="<?php echo (int) ( $full[2] ?? 0 ); ?>"<?php echo 0 !== $i ? ' tabindex="-1"' : ''; ?>><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					</div>
				<?php endforeach; ?>
			</div>
			<?php if ( $count > 1 ) : ?>
				<button type="button" class="mmpp-arrow mmpp-arrow--prev" data-mmpp-prev aria-label="<?php esc_attr_e( 'Previous image', 'mine-mirth-product-page' ); ?>"><?php echo mmpp_icon( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<button type="button" class="mmpp-arrow mmpp-arrow--next" data-mmpp-next aria-label="<?php esc_attr_e( 'Next image', 'mine-mirth-product-page' ); ?>"><?php echo mmpp_icon( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<?php if ( $s['counter'] ) : ?>
					<span class="mmpp-counter" data-mmpp-count aria-hidden="true">1 / <?php echo (int) $count; ?></span>
				<?php endif; ?>
				<div class="mmpp-dots" aria-hidden="true">
					<?php for ( $i = 0; $i < $count; $i++ ) : ?>
						<span class="<?php echo 0 === $i ? 'is-active' : ''; ?>"></span>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
			<?php if ( $s['lightbox'] && $ids ) : ?>
				<button type="button" class="mmpp-expand" data-mmpp-open aria-label="<?php esc_attr_e( 'View full screen', 'mine-mirth-product-page' ); ?>"><?php echo mmpp_icon( 'expand' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<?php endif; ?>
		</div>

		<?php if ( $count > 1 && 'none' !== $s['thumbs'] ) : ?>
			<div class="mmpp-thumbs">
				<button type="button" class="mmpp-thumbs__nav mmpp-thumbs__nav--prev" data-mmpp-tprev aria-label="<?php esc_attr_e( 'Scroll thumbnails back', 'mine-mirth-product-page' ); ?>" hidden><?php echo mmpp_icon( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<div class="mmpp-thumbs__track" data-mmpp-track>
					<?php foreach ( $ids as $i => $id ) : ?>
						<button type="button" class="mmpp-thumb<?php echo 0 === $i ? ' is-active' : ''; ?>" data-mmpp-thumb="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: image number */ __( 'Show image %d', 'mine-mirth-product-page' ), $i + 1 ) ); ?>"<?php echo 0 === $i ? ' aria-current="true"' : ''; ?>>
							<?php echo wp_get_attachment_image( $id, 'woocommerce_gallery_thumbnail', false, array( 'alt' => '', 'loading' => $i < (int) $s['thumbs_count'] ? 'eager' : 'lazy', 'decoding' => 'async', 'draggable' => 'false' ) ); ?>
						</button>
					<?php endforeach; ?>
				</div>
				<button type="button" class="mmpp-thumbs__nav mmpp-thumbs__nav--next" data-mmpp-tnext aria-label="<?php esc_attr_e( 'Scroll thumbnails forward', 'mine-mirth-product-page' ); ?>" hidden><?php echo mmpp_icon( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>
		<?php endif; ?>

		<?php if ( $s['lightbox'] && $ids ) : ?>
			<dialog class="mmpp-lb" data-mmpp-lb aria-label="<?php echo esc_attr( $name ); ?>">
				<div class="mmpp-lb__track" data-mmpp-lbtrack>
					<?php foreach ( $ids as $i => $id ) : ?>
						<figure class="mmpp-lb__slide"><img alt="" data-src="<?php echo esc_url( (string) wp_get_attachment_image_url( $id, 'full' ) ); ?>" decoding="async"></figure>
					<?php endforeach; ?>
				</div>
				<button type="button" class="mmpp-lb__close" data-mmpp-close aria-label="<?php esc_attr_e( 'Close', 'mine-mirth-product-page' ); ?>"><?php echo mmpp_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<?php if ( $count > 1 ) : ?>
					<button type="button" class="mmpp-arrow mmpp-arrow--prev" data-mmpp-lbprev aria-label="<?php esc_attr_e( 'Previous image', 'mine-mirth-product-page' ); ?>"><?php echo mmpp_icon( 'prev' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					<button type="button" class="mmpp-arrow mmpp-arrow--next" data-mmpp-lbnext aria-label="<?php esc_attr_e( 'Next image', 'mine-mirth-product-page' ); ?>"><?php echo mmpp_icon( 'next' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					<span class="mmpp-counter mmpp-lb__count" data-mmpp-lbcount>1 / <?php echo (int) $count; ?></span>
				<?php endif; ?>
			</dialog>
		<?php endif; ?>
	</div>
	<?php
}
