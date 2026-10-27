<?php
/**
 * WooCommerce integration: layout wrappers, shop header & toolbar,
 * product cards, single product extras, mini cart drawer, free-shipping
 * progress, checkout and account enhancements.
 *
 * Loaded only when WooCommerce is active.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Assets
 * ------------------------------------------------------------------------- */
function mm_woo_assets(): void {
	wp_enqueue_script(
		'mm-woo',
		MM_URI . '/assets/js/woo.js',
		array( 'jquery', 'mm-theme' ),
		MM_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	if ( mm_opt( 'cart_drawer' ) ) {
		wp_enqueue_script( 'wc-cart-fragments' );
	}
}
add_action( 'wp_enqueue_scripts', 'mm_woo_assets', 30 );

/* ---------------------------------------------------------------------------
 * Grid settings
 * ------------------------------------------------------------------------- */
add_filter( 'loop_shop_columns', static fn() => max( 2, (int) mm_opt( 'shop_columns' ) ) );
add_filter( 'loop_shop_per_page', static fn() => max( 1, (int) mm_opt( 'shop_per_page' ) ), 20 );
add_filter(
	'woocommerce_output_related_products_args',
	static function ( $args ) {
		$args['posts_per_page'] = 4;
		$args['columns']        = 4;
		return $args;
	}
);
add_filter( 'woocommerce_upsell_display_args', static fn( $args ) => array_merge( $args, array( 'columns' => 4, 'posts_per_page' => 4 ) ) );
add_filter( 'woocommerce_cross_sells_columns', static fn() => 3 );

/* ---------------------------------------------------------------------------
 * Layout wrappers
 * ------------------------------------------------------------------------- */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
remove_action( 'woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header', 10 );
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
add_filter( 'woocommerce_show_page_title', '__return_false' );

function mm_woo_has_sidebar(): bool {
	return ( is_shop() || is_product_taxonomy() || is_post_type_archive( 'product' ) ) && 'none' !== mm_opt( 'shop_sidebar' ) && is_active_sidebar( 'sidebar-shop' );
}

function mm_woo_wrapper_start(): void {
	$is_archive = is_shop() || is_product_taxonomy() || is_post_type_archive( 'product' );
	if ( $is_archive && mm_opt( 'shop_hero' ) ) {
		mm_shop_hero();
	}
	echo '<main id="mm-main" class="mm-main mm-shop' . ( is_product() ? ' mm-shop--single' : '' ) . '" tabindex="-1">';
	if ( is_product() && function_exists( 'mm_deco' ) ) {
		echo mm_deco( 'coins' ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '<div class="mm-container mm-shop__wrap">';
	if ( is_product() || ( $is_archive && ! mm_opt( 'shop_hero' ) ) ) {
		mm_breadcrumbs();
	}
	if ( mm_woo_has_sidebar() ) {
		echo '<div class="mm-shop-layout mm-shop-layout--' . esc_attr( (string) mm_opt( 'shop_sidebar' ) ) . '">';
		echo '<aside class="mm-shop-sidebar" id="mm-shop-filters" data-mm-filters aria-label="' . esc_attr__( 'Product filters', 'minemirth' ) . '">';
		echo '<div class="mm-shop-sidebar__head"><strong>' . esc_html__( 'Filters', 'minemirth' ) . '</strong><button type="button" class="mm-icon-btn" data-mm-filters-close aria-label="' . esc_attr__( 'Close filters', 'minemirth' ) . '">' . mm_icon( 'x' ) . '</button></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		dynamic_sidebar( 'sidebar-shop' );
		echo '</aside><div class="mm-shop-main">';
	}
}
add_action( 'woocommerce_before_main_content', 'mm_woo_wrapper_start', 10 );

function mm_woo_wrapper_end(): void {
	if ( mm_woo_has_sidebar() ) {
		echo '</div></div>';
	}
	echo '</div></main>';
}
add_action( 'woocommerce_after_main_content', 'mm_woo_wrapper_end', 10 );

/**
 * Shop / category header.
 */
function mm_shop_hero(): void {
	$title = (string) mm_opt( 'shop_hero_title' );
	$text  = (string) mm_opt( 'shop_hero_text' );
	$image = 0;
	if ( is_product_taxonomy() ) {
		$term  = get_queried_object();
		$title = $term instanceof WP_Term ? $term->name : $title;
		$text  = $term instanceof WP_Term && $term->description ? $term->description : '';
		$image = $term instanceof WP_Term ? (int) get_term_meta( $term->term_id, 'thumbnail_id', true ) : 0;
	} elseif ( is_search() ) {
		/* translators: %s: search query */
		$title = sprintf( __( 'Results for “%s”', 'minemirth' ), get_search_query() );
		$text  = '';
	}

	ob_start();
	if ( mm_opt( 'shop_cat_pills' ) ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => true,
				'number'     => 10,
				'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			)
		);
		if ( ! is_wp_error( $terms ) && $terms ) {
			$current = is_product_category() ? get_queried_object_id() : 0;
			echo '<nav class="mm-filter-pills" aria-label="' . esc_attr__( 'Product categories', 'minemirth' ) . '">';
			echo '<a class="mm-pill' . ( $current ? '' : ' is-active' ) . '" href="' . esc_url( mm_shop_url() ) . '">' . esc_html__( 'All', 'minemirth' ) . '</a>';
			foreach ( $terms as $term ) {
				printf( '<a class="mm-pill%1$s" href="%2$s">%3$s <small>%4$d</small></a>', $current === $term->term_id ? ' is-active' : '', esc_url( get_term_link( $term ) ), esc_html( $term->name ), (int) $term->count );
			}
			echo '</nav>';
		}
	}
	$after = (string) ob_get_clean();

	mm_page_hero(
		array(
			'title' => $title,
			'text'  => $text,
			'image' => $image,
			'class' => 'mm-page-hero--shop',
			'deco'  => 'blocks',
			'after' => $after,
		)
	);
}

/* ---------------------------------------------------------------------------
 * Toolbar (result count + ordering + mobile filter button)
 * ------------------------------------------------------------------------- */
function mm_shop_toolbar_open(): void {
	echo '<div class="mm-shop-toolbar">';
	if ( mm_woo_has_sidebar() ) {
		echo '<button type="button" class="mm-btn mm-btn--ghost mm-btn--sm mm-shop-filter-btn" data-mm-filters-open aria-controls="mm-shop-filters">' . mm_icon( 'sliders' ) . '<span>' . esc_html__( 'Filters', 'minemirth' ) . '</span></button>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
function mm_shop_toolbar_close(): void {
	echo '</div>';
}
add_action( 'woocommerce_before_shop_loop', 'mm_shop_toolbar_open', 15 );
add_action( 'woocommerce_before_shop_loop', 'mm_shop_toolbar_close', 35 );

/* ---------------------------------------------------------------------------
 * Product cards
 * ------------------------------------------------------------------------- */
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );

/**
 * Card badges.
 *
 * @param WC_Product $product Product.
 */
function mm_product_badges( $product ): string {
	$badges = array();
	if ( ! $product->is_in_stock() ) {
		$badges[] = '<span class="mm-badge mm-badge--out">' . esc_html__( 'Sold out', 'minemirth' ) . '</span>';
	} elseif ( $product->is_on_sale() ) {
		$label = __( 'Sale', 'minemirth' );
		if ( mm_opt( 'card_sale_percent' ) && $product->is_type( 'simple' ) ) {
			$regular = (float) $product->get_regular_price();
			$sale    = (float) $product->get_sale_price();
			if ( $regular > 0 && $sale > 0 ) {
				$label = '−' . round( ( 1 - $sale / $regular ) * 100 ) . '%';
			}
		}
		$badges[] = '<span class="mm-badge mm-badge--sale">' . esc_html( $label ) . '</span>';
	}
	$days = (int) mm_opt( 'card_new_days' );
	if ( $days > 0 && $product->get_date_created() && $product->get_date_created()->getTimestamp() > time() - $days * DAY_IN_SECONDS ) {
		$badges[] = '<span class="mm-badge mm-badge--new">' . esc_html__( 'New', 'minemirth' ) . '</span>';
	}
	if ( $product->is_featured() && $product->is_in_stock() ) {
		$badges[] = '<span class="mm-badge mm-badge--hot">' . esc_html__( 'Featured', 'minemirth' ) . '</span>';
	}
	return $badges ? '<span class="mm-badges">' . implode( '', $badges ) . '</span>' : '';
}

function mm_loop_media(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$size   = 'woocommerce_thumbnail';
	$second = '';
	if ( mm_opt( 'card_hover_image' ) ) {
		$gallery = $product->get_gallery_image_ids();
		if ( $gallery ) {
			$second = wp_get_attachment_image( (int) $gallery[0], $size, false, array( 'class' => 'mm-card-media__alt', 'loading' => 'lazy', 'alt' => '' ) );
		}
	}
	echo '<span class="mm-card-media' . ( $second ? ' has-alt' : '' ) . '">';
	echo woocommerce_get_product_thumbnail( $size ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo $second; // phpcs:ignore WordPress.Security.EscapeOutput
	echo mm_product_badges( $product ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</span>';
}
add_action( 'woocommerce_before_shop_loop_item_title', 'mm_loop_media', 10 );

/**
 * Spec chips (hashrate, power…) and stock line under the title.
 *
 * @param WC_Product $product Product.
 * @param int        $limit   Max chips.
 */
function mm_product_spec_chips( $product, int $limit = 4 ): array {
	$chips = array();
	foreach ( mm_list( mm_opt( 'card_specs' ) ) as $attr ) {
		$value = trim( (string) $product->get_attribute( $attr ) );
		if ( '' !== $value ) {
			$chips[] = array( $attr, wp_trim_words( $value, 4, '' ) );
		}
		if ( count( $chips ) >= $limit ) {
			break;
		}
	}
	// Derive efficiency when not provided.
	$has_eff = (bool) array_filter( $chips, static fn( $c ) => false !== stripos( $c[0], 'effic' ) );
	if ( ! $has_eff && count( $chips ) < $limit ) {
		$specs = mm_product_mining_specs( $product );
		if ( $specs['th'] > 0 && $specs['watts'] > 0 ) {
			$chips[] = array( 'efficiency', number_format_i18n( $specs['watts'] / $specs['th'], 1 ) . ' J/TH' );
		}
	}
	return $chips;
}

function mm_loop_specs(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$chips = mm_product_spec_chips( $product, 3 );
	if ( $chips ) {
		echo '<ul class="mm-spec-chips">';
		foreach ( $chips as $chip ) {
			echo '<li title="' . esc_attr( ucfirst( $chip[0] ) ) . '">' . esc_html( $chip[1] ) . '</li>';
		}
		echo '</ul>';
	}
}
add_action( 'woocommerce_after_shop_loop_item_title', 'mm_loop_specs', 3 );

function mm_loop_stock(): void {
	global $product;
	if ( ! mm_opt( 'card_stock' ) || ! $product instanceof WC_Product ) {
		return;
	}
	$status = $product->get_stock_status();
	$labels = array(
		'instock'     => __( 'In stock', 'minemirth' ),
		'outofstock'  => __( 'Sold out', 'minemirth' ),
		'onbackorder' => __( 'Pre-order', 'minemirth' ),
	);
	echo '<span class="mm-stock mm-stock--' . esc_attr( $status ) . '">' . esc_html( $labels[ $status ] ?? $labels['instock'] ) . '</span>';
}
add_action( 'woocommerce_after_shop_loop_item_title', 'mm_loop_stock', 12 );

function mm_loop_quickview(): void {
	global $product;
	if ( ! mm_opt( 'card_quickview' ) || ! $product instanceof WC_Product ) {
		return;
	}
	printf(
		'<button type="button" class="mm-quickview-btn" data-mm-quickview="%1$d" aria-label="%2$s">%3$s<span>%4$s</span></button>',
		(int) $product->get_id(),
		/* translators: %s: product name */
		esc_attr( sprintf( __( 'Quick view %s', 'minemirth' ), $product->get_name() ) ),
		mm_icon( 'eye' ), // phpcs:ignore WordPress.Security.EscapeOutput
		esc_html__( 'Quick view', 'minemirth' )
	);
}
add_action( 'woocommerce_after_shop_loop_item', 'mm_loop_quickview', 7 );

add_action(
	'init',
	static function () {
		if ( ! mm_opt( 'card_rating' ) ) {
			remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
		}
	}
);

/**
 * Class hook for cards.
 */
add_filter(
	'woocommerce_post_class',
	static function ( $classes, $product ) {
		if ( ! is_product() || wc_get_loop_prop( 'name' ) ) {
			$classes[] = 'mm-product-card';
		}
		return $classes;
	},
	10,
	2
);

/**
 * Add an icon to loop add-to-cart buttons.
 */
add_filter(
	'woocommerce_loop_add_to_cart_link',
	static function ( $html, $product ) {
		$icon = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ? mm_icon( 'bag' ) : mm_icon( 'arrow-right' );
		return preg_replace( '#(<a[^>]*>)(.*?)(</a>)#s', '$1' . $icon . '<span>$2</span>$3', $html, 1 );
	},
	10,
	2
);

/* ---------------------------------------------------------------------------
 * Single product
 * ------------------------------------------------------------------------- */
function mm_single_spec_highlights(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$chips = mm_product_spec_chips( $product, 4 );
	if ( ! $chips ) {
		return;
	}
	echo '<dl class="mm-spec-grid">';
	foreach ( $chips as $chip ) {
		echo '<div><dt>' . esc_html( ucwords( str_replace( array( '-', '_', 'pa ' ), ' ', $chip[0] ) ) ) . '</dt><dd>' . esc_html( $chip[1] ) . '</dd></div>';
	}
	echo '</dl>';
}
add_action( 'woocommerce_single_product_summary', 'mm_single_spec_highlights', 15 );

function mm_single_trust(): void {
	$items = array();
	for ( $i = 1; $i <= 3; $i++ ) {
		$title = (string) mm_opt( "ptrust_{$i}_title" );
		if ( '' !== $title ) {
			$items[] = array( (string) mm_opt( "ptrust_{$i}_icon" ), $title, (string) mm_opt( "ptrust_{$i}_text" ) );
		}
	}
	if ( ! $items ) {
		return;
	}
	echo '<ul class="mm-trust-list">';
	foreach ( $items as $item ) {
		echo '<li>' . mm_icon( $item[0] ) . '<span><strong>' . esc_html( $item[1] ) . '</strong><small>' . esc_html( $item[2] ) . '</small></span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</ul>';
}
add_action( 'woocommerce_single_product_summary', 'mm_single_trust', 35 );

function mm_single_ctas(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$specs = mm_product_mining_specs( $product );
	$boxes = '';
	if ( mm_opt( 'product_calc_cta' ) && $specs['th'] > 0 && $specs['watts'] > 0 ) {
		$url    = add_query_arg(
			array(
				'hashrate' => $specs['th'],
				'power'    => $specs['watts'],
				'cost'     => (float) $product->get_price(),
				'miner'    => rawurlencode( $product->get_name() ),
			),
			mm_url( mm_opt( 'calc_btn_url' ) )
		);
		$boxes .= '<a class="mm-cta-box" href="' . esc_url( $url ) . '">' . mm_icon( 'calculator' ) . '<span><strong>' . esc_html__( 'Estimate profitability', 'minemirth' ) . '</strong><small>' . esc_html__( 'Prefilled with this miner’s specs and live network data', 'minemirth' ) . '</small></span>' . mm_icon( 'arrow-right' ) . '</a>';
	}
	if ( mm_opt( 'product_hosting_cta' ) ) {
		$boxes .= '<a class="mm-cta-box" href="' . esc_url( home_url( '/miner-hosting/' ) ) . '">' . mm_icon( 'server' ) . '<span><strong>' . esc_html__( 'Host this miner', 'minemirth' ) . '</strong><small>' . esc_html( (string) mm_opt( 'product_hosting_text' ) ) . '</small></span>' . mm_icon( 'arrow-right' ) . '</a>';
	}
	if ( $boxes ) {
		echo '<div class="mm-cta-boxes">' . $boxes . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	if ( mm_opt( 'product_delivery' ) ) {
		echo '<details class="mm-delivery"><summary>' . mm_icon( 'truck' ) . '<span>' . esc_html__( 'Shipping & returns', 'minemirth' ) . '</span>' . mm_icon( 'chevron-down' ) . '</summary><div>' . wp_kses_post( wpautop( (string) mm_opt( 'product_delivery' ) ) ) . '</div></details>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
add_action( 'woocommerce_single_product_summary', 'mm_single_ctas', 36 );

/**
 * Sticky add-to-cart bar.
 */
function mm_sticky_add_to_cart(): void {
	if ( ! is_product() || ! mm_opt( 'product_sticky_atc' ) ) {
		return;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product || ! $product->is_purchasable() ) {
		return;
	}
	?>
	<div class="mm-sticky-atc" data-mm-sticky-atc aria-hidden="true">
		<div class="mm-container mm-sticky-atc__inner">
			<span class="mm-sticky-atc__thumb"><?php echo $product->get_image( 'woocommerce_gallery_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<span class="mm-sticky-atc__info">
				<strong><?php echo esc_html( $product->get_name() ); ?></strong>
				<span class="mm-sticky-atc__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
			</span>
			<button type="button" class="mm-btn mm-btn--primary" data-mm-sticky-buy tabindex="-1">
				<?php echo mm_icon( 'bag' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span><?php echo esc_html( $product->is_type( 'simple' ) ? $product->single_add_to_cart_text() : __( 'Choose options', 'minemirth' ) ); ?></span>
			</button>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'mm_sticky_add_to_cart', 3 );

/* ---------------------------------------------------------------------------
 * Free shipping progress
 * ------------------------------------------------------------------------- */
function mm_free_shipping_html(): string {
	$threshold = (float) mm_opt( 'cart_free_shipping' );
	if ( $threshold <= 0 || ! WC()->cart ) {
		return '<div class="mm-free-ship" hidden></div>';
	}
	$subtotal = (float) WC()->cart->get_displayed_subtotal();
	$left     = max( 0, $threshold - $subtotal );
	$pct      = min( 100, $subtotal / $threshold * 100 );
	$message  = $left > 0
		/* translators: %s: amount */
		? sprintf( __( 'You’re %s away from free shipping', 'minemirth' ), '<strong>' . wc_price( $left ) . '</strong>' )
		: '<strong>' . __( 'You’ve unlocked free shipping', 'minemirth' ) . '</strong>';
	return '<div class="mm-free-ship' . ( $left > 0 ? '' : ' is-done' ) . '"><p>' . mm_icon( 'truck' ) . '<span>' . wp_kses_post( $message ) . '</span></p><span class="mm-free-ship__bar"><span style="width:' . esc_attr( (string) round( $pct, 1 ) ) . '%"></span></span></div>';
}
add_action( 'woocommerce_before_cart', static fn() => print( mm_free_shipping_html() ), 5 ); // phpcs:ignore WordPress.Security.EscapeOutput

/* ---------------------------------------------------------------------------
 * Mini cart drawer + fragments
 * ------------------------------------------------------------------------- */
function mm_cart_drawer(): void {
	if ( ! mm_opt( 'cart_drawer' ) || is_cart() || is_checkout() ) {
		return;
	}
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	?>
	<div class="mm-cart-drawer mm-drawer" data-mm-cart-drawer inert>
		<div class="mm-drawer__backdrop" data-mm-cart-close></div>
		<aside class="mm-drawer__panel mm-cart-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Shopping cart', 'minemirth' ); ?>">
			<div class="mm-drawer__head">
				<strong class="mm-cart-drawer__title"><?php esc_html_e( 'Your cart', 'minemirth' ); ?> <span class="mm-cart-count-inline" data-mm-cart-count-inline><?php echo esc_html( (string) $count ); ?></span></strong>
				<button class="mm-icon-btn" type="button" data-mm-cart-close aria-label="<?php esc_attr_e( 'Close cart', 'minemirth' ); ?>"><?php echo mm_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>
			<?php echo mm_free_shipping_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div>
			<div class="mm-cart-empty-cta">
				<?php echo mm_icon( 'bag' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php echo mm_button( __( 'Browse miners', 'minemirth' ), '{shop}', 'primary', array( 'size' => 'sm', 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<?php if ( mm_opt( 'cart_note' ) ) : ?>
				<p class="mm-cart-drawer__note"><?php echo mm_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( (string) mm_opt( 'cart_note' ) ); ?></p>
			<?php endif; ?>
		</aside>
	</div>
	<?php
}
add_action( 'wp_footer', 'mm_cart_drawer', 2 );

function mm_cart_fragments( array $fragments ): array {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	$fragments['.mm-cart-count']                  = '<span class="mm-cart-count' . ( $count ? '' : ' is-empty' ) . '" data-mm-cart-count>' . esc_html( (string) $count ) . '</span>';
	$fragments['[data-mm-cart-count-inline]']      = '<span class="mm-cart-count-inline" data-mm-cart-count-inline>' . esc_html( (string) $count ) . '</span>';
	$fragments['div.mm-free-ship']                 = mm_free_shipping_html();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'mm_cart_fragments' );

/* ---------------------------------------------------------------------------
 * Cart page extras
 * ------------------------------------------------------------------------- */
function mm_cart_trust(): void {
	echo '<div class="mm-cart-trust">';
	mm_checkout_trust_points();
	echo mm_payment_badges(); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</div>';
}
add_action( 'woocommerce_after_cart_totals', 'mm_cart_trust' );

/* ---------------------------------------------------------------------------
 * Checkout
 * ------------------------------------------------------------------------- */
function mm_checkout_trust_points(): void {
	$items = array();
	for ( $i = 1; $i <= 3; $i++ ) {
		$title = (string) mm_opt( "ctrust_{$i}_title" );
		if ( '' !== $title ) {
			$items[] = array( (string) mm_opt( "ctrust_{$i}_icon" ), $title, (string) mm_opt( "ctrust_{$i}_text" ) );
		}
	}
	if ( ! $items ) {
		return;
	}
	echo '<ul class="mm-trust-list mm-trust-list--compact">';
	foreach ( $items as $item ) {
		echo '<li>' . mm_icon( $item[0] ) . '<span><strong>' . esc_html( $item[1] ) . '</strong><small>' . esc_html( $item[2] ) . '</small></span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</ul>';
}

/**
 * Optionally move the payment block into the main column.
 */
function mm_checkout_layout(): void {
	if ( is_checkout() && mm_opt( 'checkout_payment_left' ) ) {
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		add_action( 'mm_checkout_payment', 'woocommerce_checkout_payment', 10 );
	}
}
add_action( 'template_redirect', 'mm_checkout_layout' );

/**
 * Reassurance under the "Place order" button.
 */
function mm_checkout_after_submit(): void {
	$note = (string) mm_opt( 'checkout_guarantee' );
	echo '<div class="mm-checkout-assure">';
	if ( $note ) {
		echo '<p>' . mm_icon( 'lock' ) . '<span>' . esc_html( $note ) . '</span></p>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo mm_payment_badges(); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</div>';
}
add_action( 'woocommerce_review_order_after_submit', 'mm_checkout_after_submit' );

/**
 * Product thumbnails in the checkout order summary.
 */
add_action( 'woocommerce_review_order_before_cart_contents', static function () { $GLOBALS['mm_in_review'] = true; } );
add_action( 'woocommerce_review_order_after_cart_contents', static function () { $GLOBALS['mm_in_review'] = false; } );
add_filter(
	'woocommerce_cart_item_name',
	static function ( $name, $cart_item ) {
		if ( empty( $GLOBALS['mm_in_review'] ) || empty( $cart_item['data'] ) ) {
			return $name;
		}
		$thumb = $cart_item['data']->get_image( 'woocommerce_gallery_thumbnail', array( 'class' => 'mm-review-thumb' ) );
		$qty   = '<span class="mm-review-qty">' . (int) $cart_item['quantity'] . '</span>';
		return '<span class="mm-review-item"><span class="mm-review-media">' . $thumb . $qty . '</span><span class="mm-review-name">' . $name . '</span></span>';
	},
	10,
	2
);
add_filter(
	'woocommerce_checkout_cart_item_quantity',
	static fn( $html ) => empty( $GLOBALS['mm_in_review'] ) ? $html : '',
	10
);

/**
 * Block-based checkout: append trust points & badges after the block.
 */
add_filter(
	'render_block_woocommerce/checkout',
	static function ( $html ) {
		ob_start();
		echo '<div class="mm-block-checkout-assure">';
		mm_checkout_trust_points();
		mm_checkout_after_submit();
		echo '</div>';
		return $html . ob_get_clean();
	}
);

/* ---------------------------------------------------------------------------
 * Account
 * ------------------------------------------------------------------------- */

/**
 * Icon for an account endpoint.
 */
function mm_account_icon( string $endpoint ): string {
	$map = array(
		'dashboard'       => 'grid',
		'orders'          => 'package',
		'downloads'       => 'file',
		'edit-address'    => 'map-pin',
		'payment-methods' => 'credit-card',
		'edit-account'    => 'user',
		'customer-logout' => 'logout',
		'subscriptions'   => 'refresh',
		'wishlist'        => 'heart',
	);
	return mm_icon( $map[ $endpoint ] ?? 'chevron-right' );
}

/**
 * Status badge for orders.
 *
 * @param WC_Order $order Order.
 */
function mm_order_status_badge( $order ): string {
	$status = $order->get_status();
	return '<span class="mm-order-status mm-order-status--' . esc_attr( $status ) . '">' . esc_html( wc_get_order_status_name( $status ) ) . '</span>';
}

/**
 * Status pills inside the orders table.
 */
add_action(
	'woocommerce_my_account_my_orders_column_order-status',
	static function ( $order ) {
		echo mm_order_status_badge( $order ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
);

/* ---------------------------------------------------------------------------
 * Misc
 * ------------------------------------------------------------------------- */
// Breadcrumb defaults for any plugin calling woocommerce_breadcrumb().
add_filter(
	'woocommerce_breadcrumb_defaults',
	static fn( $d ) => array_merge(
		$d,
		array(
			'delimiter'   => '<span class="mm-bc-sep" aria-hidden="true">/</span>',
			'wrap_before' => '<nav class="woocommerce-breadcrumb mm-wc-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'minemirth' ) . '">',
		)
	)
);

// Star rating markup that works without WooCommerce's icon font.
add_filter( 'woocommerce_product_get_rating_html', static fn( $html ) => str_replace( 'class="star-rating"', 'class="star-rating mm-stars-css"', $html ) );

// Larger gallery thumbnails.
add_filter(
	'woocommerce_get_image_size_gallery_thumbnail',
	static fn() => array(
		'width'  => 200,
		'height' => 200,
		'crop'   => 1,
	)
);
