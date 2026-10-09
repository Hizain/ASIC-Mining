<?php
/**
 * Shop filters: categories, price, brand, every product attribute (with its
 * proper label and term names), in stock and on sale.
 *
 * Brands come from WooCommerce Brands (product_brand), Perfect Brands
 * (pwb-brand), YITH Brands (yith_product_brand) or a "Brand" attribute.
 * Choices use their own query arguments (mmc_tax[...], mmc_min, mmc_max,
 * mmc_stock, mmc_sale) so they never clash with WooCommerce's layered nav.
 *
 * @package MineMirthCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * The brand taxonomy in use, or '' when none.
 */
function mmc_brand_taxonomy(): string {
	static $tax = null;
	if ( null !== $tax ) {
		return $tax;
	}
	$tax = '';
	foreach ( array( 'product_brand', 'pwb-brand', 'yith_product_brand', 'berocket_brand', 'pa_brand', 'pa_manufacturer' ) as $candidate ) {
		if ( taxonomy_exists( $candidate ) ) {
			$tax = $candidate;
			break;
		}
	}
	return (string) apply_filters( 'mmc_brand_taxonomy', $tax );
}

/**
 * Brand terms of a product.
 *
 * @return WP_Term[]
 */
function mmc_product_brands( int $product_id ): array {
	$tax = mmc_brand_taxonomy();
	if ( ! $tax ) {
		return array();
	}
	$terms = get_the_terms( $product_id, $tax );
	return is_array( $terms ) ? $terms : array();
}

/**
 * Taxonomies offered as filters, keyed by taxonomy with their label.
 *
 * @return array<string,string>
 */
function mmc_filter_taxonomies(): array {
	$out   = array();
	$brand = mmc_brand_taxonomy();
	if ( $brand && mmc_setting( 'filters_brand' ) ) {
		$out[ $brand ] = __( 'Brand', 'mine-mirth-commerce' );
	}
	if ( mmc_setting( 'filters_attrs' ) && function_exists( 'wc_get_attribute_taxonomies' ) ) {
		foreach ( wc_get_attribute_taxonomies() as $attr ) {
			$tax = wc_attribute_taxonomy_name( $attr->attribute_name );
			if ( $tax === $brand || ! taxonomy_exists( $tax ) ) {
				continue;
			}
			// The attribute's label as typed in Products › Attributes, never its slug.
			$out[ $tax ] = $attr->attribute_label ? $attr->attribute_label : wc_attribute_label( $tax );
		}
	}
	return $out;
}

/**
 * Chosen filter values from the request.
 *
 * @return array{tax:array<string,string[]>,min:?float,max:?float,stock:bool,sale:bool}
 */
function mmc_filter_state(): array {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only shop filters.
	$allowed = mmc_filter_taxonomies();
	if ( mmc_setting( 'filters_cats' ) ) {
		$allowed['product_cat'] = __( 'Category', 'mine-mirth-commerce' );
	}
	$tax = array();
	$raw = isset( $_GET['mmc_tax'] ) && is_array( $_GET['mmc_tax'] ) ? wp_unslash( $_GET['mmc_tax'] ) : array();
	foreach ( $raw as $taxonomy => $slugs ) {
		$taxonomy = sanitize_key( (string) $taxonomy );
		if ( ! isset( $allowed[ $taxonomy ] ) ) {
			continue;
		}
		$slugs = array_values( array_filter( array_map( 'sanitize_title', (array) $slugs ) ) );
		if ( $slugs ) {
			$tax[ $taxonomy ] = $slugs;
		}
	}
	$min = isset( $_GET['mmc_min'] ) && '' !== $_GET['mmc_min'] ? (float) wp_unslash( $_GET['mmc_min'] ) : null;
	$max = isset( $_GET['mmc_max'] ) && '' !== $_GET['mmc_max'] ? (float) wp_unslash( $_GET['mmc_max'] ) : null;
	$out = array(
		'tax'   => $tax,
		'min'   => $min,
		'max'   => $max,
		'stock' => ! empty( $_GET['mmc_stock'] ),
		'sale'  => ! empty( $_GET['mmc_sale'] ),
	);
	// phpcs:enable
	return $out;
}

// Apply the filters to the main shop query.
add_action(
	'woocommerce_product_query',
	static function ( WP_Query $q ) {
		$state = mmc_filter_state();
		$tq    = (array) $q->get( 'tax_query' );
		foreach ( $state['tax'] as $taxonomy => $slugs ) {
			$tq[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $slugs,
				'operator' => 'IN',
			);
		}
		if ( $state['tax'] ) {
			$tq['relation'] = 'AND';
			$q->set( 'tax_query', $tq );
		}
		$mq = (array) $q->get( 'meta_query' );
		if ( $state['stock'] ) {
			$mq[] = array(
				'key'   => '_stock_status',
				'value' => 'instock',
			);
		}
		if ( null !== $state['min'] || null !== $state['max'] ) {
			$mq[] = array(
				'key'     => '_price',
				'value'   => array( null !== $state['min'] ? $state['min'] : 0, null !== $state['max'] ? $state['max'] : PHP_INT_MAX ),
				'compare' => 'BETWEEN',
				'type'    => 'DECIMAL(16,4)',
			);
		}
		if ( count( $mq ) > 0 ) {
			$q->set( 'meta_query', $mq );
		}
		if ( $state['sale'] ) {
			$ids = wc_get_product_ids_on_sale();
			$in  = (array) $q->get( 'post__in' );
			$q->set( 'post__in', $in ? array_values( array_intersect( $in, $ids ) ) : ( $ids ? $ids : array( 0 ) ) );
		}
	}
);

/**
 * Current shop URL without our filter arguments.
 */
function mmc_filter_base_url(): string {
	if ( is_product_taxonomy() ) {
		$link = get_term_link( get_queried_object() );
		$base = is_wp_error( $link ) ? wc_get_page_permalink( 'shop' ) : $link;
	} else {
		$base = wc_get_page_permalink( 'shop' );
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	foreach ( array( 'orderby', 's', 'post_type' ) as $keep ) {
		if ( isset( $_GET[ $keep ] ) && is_scalar( $_GET[ $keep ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$base = add_query_arg( $keep, sanitize_text_field( wp_unslash( (string) $_GET[ $keep ] ) ), $base ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}
	return (string) $base;
}

/**
 * Lowest and highest product price in the store.
 *
 * @return array{0:float,1:float}
 */
function mmc_price_bounds(): array {
	$cached = get_transient( 'mmc_price_bounds' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	global $wpdb;
	$row    = $wpdb->get_row( "SELECT MIN(min_price) AS lo, MAX(max_price) AS hi FROM {$wpdb->wc_product_meta_lookup} WHERE stock_status != 'outofstock' OR stock_status IS NULL" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$bounds = array( floor( (float) ( $row->lo ?? 0 ) ), ceil( (float) ( $row->hi ?? 0 ) ) );
	set_transient( 'mmc_price_bounds', $bounds, HOUR_IN_SECONDS );
	return $bounds;
}
add_action( 'woocommerce_update_product', static fn() => delete_transient( 'mmc_price_bounds' ) );

/**
 * The filter panel.
 */
function mmc_shop_filters_html(): string {
	$state = mmc_filter_state();
	$base  = mmc_filter_base_url();
	$count = (bool) mmc_setting( 'filters_counts' );
	$cur   = get_woocommerce_currency_symbol();
	ob_start();
	?>
	<form class="mmc-filters" method="get" action="<?php echo esc_url( strtok( $base, '?' ) ); ?>" data-mmc-filters>
		<?php
		// Keep sorting and search when filtering.
		$query = (string) wp_parse_url( $base, PHP_URL_QUERY );
		parse_str( $query, $keep );
		foreach ( $keep as $k => $v ) {
			if ( is_scalar( $v ) ) {
				echo '<input type="hidden" name="' . esc_attr( (string) $k ) . '" value="' . esc_attr( (string) $v ) . '">';
			}
		}

		// Active filters as removable chips.
		$chips = array();
		foreach ( $state['tax'] as $taxonomy => $slugs ) {
			foreach ( $slugs as $slug ) {
				$term = get_term_by( 'slug', $slug, $taxonomy );
				if ( ! $term ) {
					continue;
				}
				$args                          = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$args['mmc_tax'][ $taxonomy ] = array_values( array_diff( $slugs, array( $slug ) ) );
				$chips[]                       = array( $term->name, add_query_arg( map_deep( wp_unslash( $args ), 'sanitize_text_field' ), strtok( $base, '?' ) ) );
			}
		}
		if ( null !== $state['min'] || null !== $state['max'] ) {
			$chips[] = array( wp_strip_all_tags( wc_price( (float) $state['min'] ) ) . ' – ' . ( null !== $state['max'] ? wp_strip_all_tags( wc_price( $state['max'] ) ) : '∞' ), remove_query_arg( array( 'mmc_min', 'mmc_max' ) ) );
		}
		if ( $state['stock'] ) {
			$chips[] = array( __( 'In stock', 'mine-mirth-commerce' ), remove_query_arg( 'mmc_stock' ) );
		}
		if ( $state['sale'] ) {
			$chips[] = array( __( 'On sale', 'mine-mirth-commerce' ), remove_query_arg( 'mmc_sale' ) );
		}
		if ( $chips ) :
			?>
			<div class="mmc-filters__active">
				<?php foreach ( $chips as $chip ) : ?>
					<a class="mmc-chip" href="<?php echo esc_url( $chip[1] ); ?>"><?php echo esc_html( $chip[0] ); ?> <span aria-hidden="true">×</span><span class="screen-reader-text"><?php esc_html_e( 'Remove filter', 'mine-mirth-commerce' ); ?></span></a>
				<?php endforeach; ?>
				<a class="mmc-filters__clear" href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'Clear all', 'mine-mirth-commerce' ); ?></a>
			</div>
		<?php endif; ?>

		<?php
		if ( mmc_setting( 'filters_cats' ) ) {
			$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
			if ( ! is_wp_error( $cats ) && $cats ) {
				mmc_filter_group( 'product_cat', __( 'Category', 'mine-mirth-commerce' ), $cats, $state, $count );
			}
		}

		if ( mmc_setting( 'filters_price' ) ) {
			[ $lo, $hi ] = mmc_price_bounds();
			if ( $hi > 0 ) :
				?>
				<details class="mmc-filter" open>
					<summary><?php esc_html_e( 'Price', 'mine-mirth-commerce' ); ?></summary>
					<div class="mmc-filter__price">
						<label><span><?php echo esc_html( $cur ); ?></span><input type="number" name="mmc_min" min="0" step="1" inputmode="numeric" placeholder="<?php echo esc_attr( (string) $lo ); ?>" value="<?php echo esc_attr( null !== $state['min'] ? (string) $state['min'] : '' ); ?>" aria-label="<?php esc_attr_e( 'Minimum price', 'mine-mirth-commerce' ); ?>"></label>
						<span aria-hidden="true">–</span>
						<label><span><?php echo esc_html( $cur ); ?></span><input type="number" name="mmc_max" min="0" step="1" inputmode="numeric" placeholder="<?php echo esc_attr( (string) $hi ); ?>" value="<?php echo esc_attr( null !== $state['max'] ? (string) $state['max'] : '' ); ?>" aria-label="<?php esc_attr_e( 'Maximum price', 'mine-mirth-commerce' ); ?>"></label>
					</div>
				</details>
				<?php
			endif;
		}

		foreach ( mmc_filter_taxonomies() as $taxonomy => $label ) {
			$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true, 'orderby' => 'name' ) );
			if ( ! is_wp_error( $terms ) && $terms ) {
				mmc_filter_group( $taxonomy, $label, $terms, $state, $count );
			}
		}

		if ( mmc_setting( 'filters_stock' ) ) :
			?>
			<details class="mmc-filter" open>
				<summary><?php esc_html_e( 'Availability', 'mine-mirth-commerce' ); ?></summary>
				<label class="mmc-check"><input type="checkbox" name="mmc_stock" value="1" <?php checked( $state['stock'] ); ?>> <span><?php esc_html_e( 'In stock only', 'mine-mirth-commerce' ); ?></span></label>
				<label class="mmc-check"><input type="checkbox" name="mmc_sale" value="1" <?php checked( $state['sale'] ); ?>> <span><?php esc_html_e( 'On sale', 'mine-mirth-commerce' ); ?></span></label>
			</details>
		<?php endif; ?>

		<button type="submit" class="mmc-btn mmc-btn--primary mmc-filters__apply"><?php esc_html_e( 'Apply filters', 'mine-mirth-commerce' ); ?></button>
	</form>
	<script>
	(function () {
		var f = document.currentScript.previousElementSibling;
		if (!f || !f.matches('[data-mmc-filters]')) { return; }
		// Checkboxes apply at once; price applies on Enter or the button.
		f.addEventListener('change', function (e) { if (e.target.type === 'checkbox') { f.requestSubmit ? f.requestSubmit() : f.submit(); } });
		// Drop empty fields so URLs stay short.
		f.addEventListener('submit', function () { Array.prototype.forEach.call(f.elements, function (el) { if (el.name && el.value === '' ) { el.disabled = true; } }); });
	})();
	</script>
	<?php
	return (string) ob_get_clean();
}

/**
 * One group of checkbox options.
 *
 * @param WP_Term[] $terms Terms.
 */
function mmc_filter_group( string $taxonomy, string $label, array $terms, array $state, bool $count ): void {
	$chosen = $state['tax'][ $taxonomy ] ?? array();
	$many   = count( $terms ) > 8;
	?>
	<details class="mmc-filter" <?php echo $chosen || count( $terms ) <= 12 ? 'open' : ''; ?>>
		<summary><?php echo esc_html( $label ); ?><?php echo $chosen ? ' <span class="mmc-filter__n">' . esc_html( (string) count( $chosen ) ) . '</span>' : ''; ?></summary>
		<div class="mmc-filter__list<?php echo $many ? ' is-scroll' : ''; ?>">
			<?php foreach ( $terms as $term ) : ?>
				<label class="mmc-check">
					<input type="checkbox" name="mmc_tax[<?php echo esc_attr( $taxonomy ); ?>][]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $chosen, true ) ); ?>>
					<span><?php echo esc_html( $term->name ); ?></span>
					<?php if ( $count ) : ?>
						<small><?php echo esc_html( number_format_i18n( (int) $term->count ) ); ?></small>
					<?php endif; ?>
				</label>
			<?php endforeach; ?>
		</div>
	</details>
	<?php
}

add_shortcode( 'mmc_shop_filters', static fn() => mmc_shop_filters_html() );

// Put the filters at the top of the theme's shop sidebar, and make sure the sidebar shows.
add_filter(
	'is_active_sidebar',
	static function ( $active, $index ) {
		if ( 'sidebar-shop' === $index && mmc_setting( 'filters_auto' ) && ! is_admin() ) {
			return true;
		}
		return $active;
	},
	10,
	2
);
add_action(
	'dynamic_sidebar_before',
	static function ( $index ) {
		if ( 'sidebar-shop' === $index && mmc_setting( 'filters_auto' ) && ! is_admin() && ( is_shop() || is_product_taxonomy() ) ) {
			echo mmc_shop_filters_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts.
		}
	}
);

// Brand above the product name on shop cards.
add_action(
	'woocommerce_shop_loop_item_title',
	static function () {
		global $product;
		if ( ! mmc_setting( 'brand_on_cards' ) || ! $product instanceof WC_Product ) {
			return;
		}
		$brands = mmc_product_brands( $product->get_id() );
		if ( $brands ) {
			echo '<span class="mmc-card-brand">' . esc_html( implode( ', ', wp_list_pluck( $brands, 'name' ) ) ) . '</span>';
		}
	},
	5
);
