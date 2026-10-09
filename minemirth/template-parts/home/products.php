<?php
/**
 * Product showcase with tabs.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

if ( ! mm_is_woo() ) {
	if ( current_user_can( 'manage_options' ) ) {
		echo '<section id="mm-section-products" class="mm-section"><div class="mm-container"><p class="mm-admin-note">' . esc_html__( 'Activate WooCommerce to show products here (visible to admins only).', 'minemirth' ) . '</p></div></section>';
	}
	return;
}

$mm_count = max( 3, (int) mm_opt( 'products_count' ) );
$mm_cat   = sanitize_title( (string) mm_opt( 'products_cat' ) );
$mm_cols  = $mm_count % 4 === 0 ? 4 : 3;
$mm_base  = array(
	'limit'    => $mm_count,
	'columns'  => $mm_cols,
	'category' => $mm_cat,
);
$mm_tabs  = array(
	'featured' => array( __( 'Featured', 'minemirth' ), array( 'visibility' => 'featured' ) ),
	'recent'   => array( __( 'New arrivals', 'minemirth' ), array( 'orderby' => 'date', 'order' => 'DESC' ) ),
	'sale'     => array( __( 'On sale', 'minemirth' ), array( 'on_sale' => 'true' ) ),
	'best'     => array( __( 'Best sellers', 'minemirth' ), array( 'best_selling' => 'true' ) ),
	'rated'    => array( __( 'Top rated', 'minemirth' ), array( 'top_rated' => 'true' ) ),
);

if ( ! mm_opt( 'products_tabs' ) ) {
	$mm_source = (string) mm_opt( 'products_source' );
	$mm_tabs   = array( $mm_source => $mm_tabs[ $mm_source ] ?? $mm_tabs['recent'] );
} else {
	unset( $mm_tabs['rated'] );
}

$mm_panels = array();
foreach ( $mm_tabs as $mm_key => $mm_tab ) {
	$mm_atts = array_merge( $mm_base, $mm_tab[1] );
	$mm_sc   = '[products';
	foreach ( $mm_atts as $mm_att => $mm_val ) {
		if ( '' !== (string) $mm_val ) {
			$mm_sc .= ' ' . $mm_att . '="' . esc_attr( (string) $mm_val ) . '"';
		}
	}
	$mm_html = do_shortcode( $mm_sc . ']' );
	if ( false !== strpos( $mm_html, 'class="products' ) ) {
		$mm_panels[ $mm_key ] = array( $mm_tab[0], $mm_html );
	}
}

// Nothing featured yet? Fall back to newest products.
if ( ! $mm_panels ) {
	$mm_html = do_shortcode( '[products limit="' . (int) $mm_count . '" columns="' . (int) $mm_cols . '" orderby="date" order="DESC"]' );
	if ( false === strpos( $mm_html, 'class="products' ) ) {
		return;
	}
	$mm_panels['recent'] = array( __( 'New arrivals', 'minemirth' ), $mm_html );
}

$mm_view_all = mm_opt( 'products_button' ) ? '<a class="mm-link-arrow" href="' . esc_url( mm_shop_url() ) . '">' . esc_html( (string) mm_opt( 'products_button' ) ) . mm_icon( 'arrow-right' ) . '</a>' : '';
?>
<section id="mm-section-products" class="mm-section mm-section--products">
	<div class="mm-container">
		<?php mm_section_heading( 'products', array( 'extra' => $mm_view_all ) ); ?>

		<?php if ( count( $mm_panels ) > 1 ) : ?>
			<div class="mm-tabs" data-mm-tabs>
				<div class="mm-tabs__list" role="tablist" aria-label="<?php esc_attr_e( 'Product collections', 'minemirth' ); ?>">
					<?php $mm_first = true; ?>
					<?php foreach ( $mm_panels as $mm_key => $mm_panel ) : ?>
						<button class="mm-tabs__tab" role="tab" type="button" id="mm-tab-<?php echo esc_attr( $mm_key ); ?>" aria-controls="mm-panel-<?php echo esc_attr( $mm_key ); ?>" aria-selected="<?php echo $mm_first ? 'true' : 'false'; ?>" tabindex="<?php echo $mm_first ? '0' : '-1'; ?>"><?php echo esc_html( $mm_panel[0] ); ?></button>
						<?php $mm_first = false; ?>
					<?php endforeach; ?>
					<span class="mm-tabs__ink" aria-hidden="true"></span>
				</div>
				<?php $mm_first = true; ?>
				<?php foreach ( $mm_panels as $mm_key => $mm_panel ) : ?>
					<div class="mm-tabs__panel" role="tabpanel" id="mm-panel-<?php echo esc_attr( $mm_key ); ?>" aria-labelledby="mm-tab-<?php echo esc_attr( $mm_key ); ?>" <?php echo $mm_first ? '' : 'hidden'; ?>>
						<?php echo $mm_panel[1]; // phpcs:ignore WordPress.Security.EscapeOutput -- WooCommerce shortcode output. ?>
					</div>
					<?php $mm_first = false; ?>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<?php echo reset( $mm_panels )[1]; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php endif; ?>
	</div>
</section>
