<?php
/**
 * Shop filters: availability (In Stock / Pre-Order / Out of Stock), the full
 * category tree, price range, brand and every product attribute, with counts
 * for the products on the current page's category or search.
 *
 * Query arguments: mmsp_stock[], mmsp_cat[], mmsp_attr[taxonomy][], mmsp_min, mmsp_max.
 *
 * @package MineMirthShopPro
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Availability
 * ------------------------------------------------------------------------- */

/**
 * Availability groups => WooCommerce stock statuses in each.
 * Pre-Order covers "On backorder" (the theme shows it as Pre-order) and any
 * custom pre-order status a plugin registers.
 *
 * @return array<string,array{label:string,statuses:string[]}>
 */
function mmsp_availability(): array {
	$pre = array( 'onbackorder' );
	foreach ( array_keys( wc_get_product_stock_status_options() ) as $status ) {
		if ( preg_match( '/pre[-_ ]?order/i', (string) $status ) ) {
			$pre[] = (string) $status;
		}
	}
	return array(
		'instock'    => array( 'label' => (string) mmsp_opt( 'label_instock' ), 'statuses' => array( 'instock' ) ),
		'preorder'   => array( 'label' => (string) mmsp_opt( 'label_preorder' ), 'statuses' => array_values( array_unique( $pre ) ) ),
		'outofstock' => array( 'label' => (string) mmsp_opt( 'label_outofstock' ), 'statuses' => array( 'outofstock' ) ),
	);
}

/**
 * Meta flags used by pre-order plugins (WooCommerce Pre-Orders, YITH Pre-Order).
 */
function mmsp_preorder_meta(): array {
	return array( '_wc_pre_orders_enabled', '_ywpo_preorder' );
}

/* ---------------------------------------------------------------------------
 * Taxonomies
 * ------------------------------------------------------------------------- */

function mmsp_brand_taxonomy(): string {
	foreach ( array( 'product_brand', 'pwb-brand', 'yith_product_brand', 'berocket_brand', 'pa_brand', 'pa_manufacturer' ) as $tax ) {
		if ( taxonomy_exists( $tax ) ) {
			return $tax;
		}
	}
	return '';
}

/**
 * Attribute taxonomies to show, keyed by taxonomy with the label typed in Products › Attributes.
 *
 * @return array<string,string>
 */
function mmsp_attribute_taxonomies(): array {
	$out    = array();
	$hidden = (array) mmsp_opt( 'hidden_attrs' );
	$brand  = mmsp_brand_taxonomy();
	if ( $brand && ! in_array( $brand, $hidden, true ) ) {
		$out[ $brand ] = __( 'Brand', 'mine-mirth-shop-pro' );
	}
	foreach ( wc_get_attribute_taxonomies() as $attr ) {
		$tax = wc_attribute_taxonomy_name( $attr->attribute_name );
		if ( $tax === $brand || in_array( $tax, $hidden, true ) || ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$out[ $tax ] = $attr->attribute_label ? $attr->attribute_label : wc_attribute_label( $tax );
	}
	return $out;
}

/* ---------------------------------------------------------------------------
 * Request state
 * ------------------------------------------------------------------------- */

/**
 * Chosen filters.
 *
 * @return array{stock:string[],cat:string[],attr:array<string,string[]>,min:?float,max:?float}
 */
function mmsp_state(): array {
	static $state = null;
	if ( null !== $state ) {
		return $state;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only shop filters.
	$avail = mmsp_availability();
	$stock = array_values( array_intersect( array_map( 'sanitize_key', (array) wp_unslash( $_GET['mmsp_stock'] ?? array() ) ), array_keys( $avail ) ) );
	$cat   = array_values( array_filter( array_map( 'sanitize_title', (array) wp_unslash( $_GET['mmsp_cat'] ?? array() ) ) ) );
	$attr  = array();
	$taxes = mmsp_attribute_taxonomies();
	foreach ( (array) wp_unslash( $_GET['mmsp_attr'] ?? array() ) as $tax => $slugs ) {
		$tax = sanitize_key( (string) $tax );
		if ( isset( $taxes[ $tax ] ) ) {
			$slugs = array_values( array_filter( array_map( 'sanitize_title', (array) $slugs ) ) );
			if ( $slugs ) {
				$attr[ $tax ] = $slugs;
			}
		}
	}
	$min = isset( $_GET['mmsp_min'] ) && '' !== $_GET['mmsp_min'] ? max( 0, (float) wp_unslash( $_GET['mmsp_min'] ) ) : null;
	$max = isset( $_GET['mmsp_max'] ) && '' !== $_GET['mmsp_max'] ? max( 0, (float) wp_unslash( $_GET['mmsp_max'] ) ) : null;
	// phpcs:enable
	$state = compact( 'stock', 'cat', 'attr', 'min', 'max' );
	return $state;
}

function mmsp_has_filters(): bool {
	$s = mmsp_state();
	return $s['stock'] || $s['cat'] || $s['attr'] || null !== $s['min'] || null !== $s['max'];
}

/* ---------------------------------------------------------------------------
 * Query
 * ------------------------------------------------------------------------- */

add_action(
	'woocommerce_product_query',
	static function ( WP_Query $q ) {
		$s = mmsp_state();
		if ( ! mmsp_has_filters() ) {
			return;
		}
		$tax = (array) $q->get( 'tax_query' );
		if ( $s['cat'] ) {
			$tax[] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $s['cat'], 'operator' => 'IN', 'include_children' => true );
		}
		foreach ( $s['attr'] as $taxonomy => $slugs ) {
			$tax[] = array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $slugs, 'operator' => 'IN' );
		}
		// Asking for Out of Stock should show them even when the store hides sold-out products.
		if ( in_array( 'outofstock', $s['stock'], true ) && function_exists( 'wc_get_product_visibility_term_ids' ) ) {
			$oos = (int) ( wc_get_product_visibility_term_ids()['outofstock'] ?? 0 );
			foreach ( $tax as $i => $clause ) {
				if ( is_array( $clause ) && 'product_visibility' === ( $clause['taxonomy'] ?? '' ) && 'NOT IN' === ( $clause['operator'] ?? '' ) && in_array( $oos, array_map( 'intval', (array) ( $clause['terms'] ?? array() ) ), true ) ) {
					$tax[ $i ]['terms'] = array_values( array_diff( array_map( 'intval', (array) $clause['terms'] ), array( $oos ) ) );
					if ( ! $tax[ $i ]['terms'] ) {
						unset( $tax[ $i ] );
					}
				}
			}
		}
		$tax['relation'] = 'AND';
		$q->set( 'tax_query', $tax );

		$meta = (array) $q->get( 'meta_query' );
		if ( $s['stock'] ) {
			$avail    = mmsp_availability();
			$statuses = array();
			foreach ( $s['stock'] as $group ) {
				$statuses = array_merge( $statuses, $avail[ $group ]['statuses'] );
			}
			$or = array( 'relation' => 'OR', array( 'key' => '_stock_status', 'value' => array_values( array_unique( $statuses ) ), 'compare' => 'IN' ) );
			if ( in_array( 'preorder', $s['stock'], true ) ) {
				foreach ( mmsp_preorder_meta() as $key ) {
					$or[] = array( 'key' => $key, 'value' => 'yes' );
				}
			}
			$meta[] = $or;
		}
		if ( null !== $s['min'] || null !== $s['max'] ) {
			$meta[] = array(
				'key'     => '_price',
				'value'   => array( null !== $s['min'] ? $s['min'] : 0, null !== $s['max'] ? $s['max'] : 1e12 ),
				'compare' => 'BETWEEN',
				'type'    => 'DECIMAL(18,4)',
			);
		}
		if ( count( $meta ) ) {
			$q->set( 'meta_query', $meta );
		}
	},
	20
);

/* ---------------------------------------------------------------------------
 * Counts for the current category or search
 * ------------------------------------------------------------------------- */

/**
 * Product IDs in the current shop context (category, tag or search), ignoring our filters.
 *
 * @return int[]
 */
function mmsp_context_ids(): array {
	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}
	$args = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'fields'                 => 'ids',
		'posts_per_page'         => 3000,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'tax_query'              => array(), // phpcs:ignore WordPress.DB.SlowDBQuery
	);
	$hidden = wc_get_product_visibility_term_ids()['exclude-from-catalog'] ?? 0;
	if ( $hidden ) {
		$args['tax_query'][] = array( 'taxonomy' => 'product_visibility', 'field' => 'term_taxonomy_id', 'terms' => array( (int) $hidden ), 'operator' => 'NOT IN' );
	}
	if ( is_product_taxonomy() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$args['tax_query'][] = array( 'taxonomy' => $term->taxonomy, 'field' => 'term_id', 'terms' => array( $term->term_id ), 'include_children' => true );
		}
	}
	if ( is_search() ) {
		$args['s'] = get_search_query();
	}
	$key = 'mmsp_ids_' . md5( wp_json_encode( $args ) . get_option( 'mmsp_cache_bust', '' ) );
	$ids = get_transient( $key );
	if ( ! is_array( $ids ) ) {
		$ids = array_map( 'intval', get_posts( $args ) );
		set_transient( $key, $ids, 15 * MINUTE_IN_SECONDS );
	}
	return $ids;
}

// Any product change clears the cached counts.
foreach ( array( 'woocommerce_update_product', 'woocommerce_new_product', 'woocommerce_product_set_stock_status', 'before_delete_post' ) as $mmsp_hook ) {
	add_action( $mmsp_hook, static fn() => update_option( 'mmsp_cache_bust', (string) microtime( true ), false ) );
}

/**
 * Count of products per term for a taxonomy, within the context.
 *
 * @return array<int,int> term_id => count
 */
function mmsp_term_counts( string $taxonomy ): array {
	$ids = mmsp_context_ids();
	if ( ! $ids ) {
		return array();
	}
	$counts = array();
	foreach ( array_chunk( $ids, 500 ) as $chunk ) {
		$rows = wp_get_object_terms( $chunk, $taxonomy, array( 'fields' => 'all_with_object_id' ) );
		if ( is_wp_error( $rows ) ) {
			continue;
		}
		foreach ( $rows as $row ) {
			$counts[ $row->term_id ] = ( $counts[ $row->term_id ] ?? 0 ) + 1;
		}
	}
	return $counts;
}

/**
 * Products per availability group within the context.
 *
 * @return array<string,int>
 */
function mmsp_stock_counts(): array {
	global $wpdb;
	$ids = mmsp_context_ids();
	$out = array_fill_keys( array_keys( mmsp_availability() ), 0 );
	if ( ! $ids ) {
		return $out;
	}
	$in    = implode( ',', array_map( 'intval', $ids ) );
	$rows  = $wpdb->get_results( "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ($in) AND meta_key IN ('_stock_status','_wc_pre_orders_enabled','_ywpo_preorder')" ); // phpcs:ignore WordPress.DB
	$by    = array();
	foreach ( $rows as $r ) {
		$by[ (int) $r->post_id ][ $r->meta_key ] = (string) $r->meta_value;
	}
	$avail = mmsp_availability();
	foreach ( $by as $meta ) {
		$pre = false;
		foreach ( mmsp_preorder_meta() as $k ) {
			$pre = $pre || 'yes' === ( $meta[ $k ] ?? '' );
		}
		$status = $meta['_stock_status'] ?? 'instock';
		if ( $pre || in_array( $status, $avail['preorder']['statuses'], true ) ) {
			++$out['preorder'];
		} elseif ( 'outofstock' === $status ) {
			++$out['outofstock'];
		} else {
			++$out['instock'];
		}
	}
	return $out;
}

/**
 * Lowest and highest price within the context.
 *
 * @return array{0:float,1:float}
 */
function mmsp_price_bounds(): array {
	global $wpdb;
	$ids = mmsp_context_ids();
	if ( ! $ids ) {
		return array( 0.0, 0.0 );
	}
	$in  = implode( ',', array_map( 'intval', $ids ) );
	$row = $wpdb->get_row( "SELECT MIN(min_price) lo, MAX(max_price) hi FROM {$wpdb->wc_product_meta_lookup} WHERE product_id IN ($in)" ); // phpcs:ignore WordPress.DB
	return array( floor( (float) ( $row->lo ?? 0 ) ), ceil( (float) ( $row->hi ?? 0 ) ) );
}

/**
 * A term like "16 GH/s", "270TH", "3.4 kW" or "3500 W" as one comparable number, or null.
 */
function mmsp_measure( string $name ): ?float {
	if ( ! preg_match( '/([0-9]+(?:[.,][0-9]+)?)\s*([kmgtpe]?)(h|sol|w)\b/i', str_replace( ',', '', $name ), $m ) ) {
		return null;
	}
	$scale = array( '' => 1, 'k' => 1e3, 'm' => 1e6, 'g' => 1e9, 't' => 1e12, 'p' => 1e15, 'e' => 1e18 );
	return (float) $m[1] * ( $scale[ strtolower( $m[2] ) ] ?? 1 );
}

/* ---------------------------------------------------------------------------
 * Panel
 * ------------------------------------------------------------------------- */

function mmsp_base_url(): string {
	if ( is_product_taxonomy() ) {
		$link = get_term_link( get_queried_object() );
		$base = is_wp_error( $link ) ? wc_get_page_permalink( 'shop' ) : $link;
	} else {
		$base = wc_get_page_permalink( 'shop' );
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	foreach ( array( 'orderby', 's', 'post_type' ) as $keep ) {
		if ( isset( $_GET[ $keep ] ) && is_scalar( $_GET[ $keep ] ) ) {
			$base = add_query_arg( $keep, rawurlencode( sanitize_text_field( wp_unslash( (string) $_GET[ $keep ] ) ) ), $base );
		}
	}
	// phpcs:enable
	return (string) $base;
}

/**
 * URL for the current filters with one value removed.
 */
function mmsp_url_without( string $group, string $value = '', string $tax = '' ): string {
	$s    = mmsp_state();
	$args = array();
	foreach ( array( 'stock', 'cat' ) as $g ) {
		$vals = $s[ $g ];
		if ( $g === $group ) {
			$vals = array_values( array_diff( $vals, array( $value ) ) );
		}
		if ( $vals ) {
			$args[ 'mmsp_' . $g ] = $vals;
		}
	}
	foreach ( $s['attr'] as $t => $vals ) {
		if ( 'attr' === $group && $t === $tax ) {
			$vals = array_values( array_diff( $vals, array( $value ) ) );
		}
		if ( $vals ) {
			$args['mmsp_attr'][ $t ] = $vals;
		}
	}
	if ( 'price' !== $group ) {
		if ( null !== $s['min'] ) {
			$args['mmsp_min'] = $s['min'];
		}
		if ( null !== $s['max'] ) {
			$args['mmsp_max'] = $s['max'];
		}
	}
	$base = mmsp_base_url();
	return $args ? $base . ( str_contains( $base, '?' ) ? '&' : '?' ) . http_build_query( $args ) : $base;
}

/**
 * Category tree as nested list items.
 *
 * @param WP_Term[] $terms  All categories.
 * @param array     $counts term_id => products in context (including children).
 */
function mmsp_category_tree( array $terms, array $counts, int $parent, array $chosen, int $depth = 0 ): string {
	$html = '';
	foreach ( $terms as $term ) {
		if ( (int) $term->parent !== $parent ) {
			continue;
		}
		$n = (int) ( $counts[ $term->term_id ] ?? 0 );
		if ( ! $n && ! mmsp_opt( 'show_empty_cats' ) && ! in_array( $term->slug, $chosen, true ) ) {
			continue;
		}
		$kids  = mmsp_category_tree( $terms, $counts, (int) $term->term_id, $chosen, $depth + 1 );
		$open  = $kids && ( $depth < 1 || array_intersect( $chosen, wp_list_pluck( get_terms( array( 'taxonomy' => 'product_cat', 'child_of' => $term->term_id, 'hide_empty' => false ) ), 'slug' ) ) );
		$html .= '<li class="mmsp-tree__item' . ( $kids ? ' has-kids' . ( $open ? ' is-open' : '' ) : '' ) . '">';
		$html .= '<div class="mmsp-tree__row">';
		$html .= '<label class="mmsp-check"><input type="checkbox" name="mmsp_cat[]" value="' . esc_attr( $term->slug ) . '"' . checked( in_array( $term->slug, $chosen, true ), true, false ) . '><span>' . esc_html( $term->name ) . '</span><small>' . esc_html( number_format_i18n( $n ) ) . '</small></label>';
		if ( $kids ) {
			/* translators: %s: category name */
			$html .= '<button type="button" class="mmsp-tree__toggle" aria-expanded="' . ( $open ? 'true' : 'false' ) . '" aria-label="' . esc_attr( sprintf( __( 'Show subcategories of %s', 'mine-mirth-shop-pro' ), $term->name ) ) . '"></button>';
		}
		$html .= '</div>';
		$html .= $kids ? '<ul class="mmsp-tree__kids">' . $kids . '</ul>' : '';
		$html .= '</li>';
	}
	return $html;
}

function mmsp_filters_html(): string {
	$s     = mmsp_state();
	$base  = mmsp_base_url();
	$avail = mmsp_availability();
	$sc    = mmsp_stock_counts();
	ob_start();
	?>
	<form class="mmsp-filters" method="get" action="<?php echo esc_url( strtok( $base, '?' ) ); ?>" data-mmsp-filters>
		<?php
		parse_str( (string) wp_parse_url( $base, PHP_URL_QUERY ), $keep );
		foreach ( $keep as $k => $v ) {
			if ( is_scalar( $v ) ) {
				echo '<input type="hidden" name="' . esc_attr( (string) $k ) . '" value="' . esc_attr( (string) $v ) . '">';
			}
		}
		?>
		<div class="mmsp-filters__head">
			<strong><?php esc_html_e( 'Filter miners', 'mine-mirth-shop-pro' ); ?></strong>
			<?php if ( mmsp_has_filters() ) : ?>
				<a class="mmsp-clear" href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'Clear all', 'mine-mirth-shop-pro' ); ?></a>
			<?php endif; ?>
		</div>

		<?php
		// Active filters as chips.
		$chips = array();
		foreach ( $s['stock'] as $g ) {
			$chips[] = array( $avail[ $g ]['label'], mmsp_url_without( 'stock', $g ) );
		}
		foreach ( $s['cat'] as $slug ) {
			$t = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $t ) {
				$chips[] = array( $t->name, mmsp_url_without( 'cat', $slug ) );
			}
		}
		foreach ( $s['attr'] as $tax => $slugs ) {
			foreach ( $slugs as $slug ) {
				$t = get_term_by( 'slug', $slug, $tax );
				if ( $t ) {
					$chips[] = array( $t->name, mmsp_url_without( 'attr', $slug, $tax ) );
				}
			}
		}
		if ( null !== $s['min'] || null !== $s['max'] ) {
			$chips[] = array( wp_strip_all_tags( wc_price( (float) $s['min'] ) ) . ' – ' . ( null !== $s['max'] ? wp_strip_all_tags( wc_price( $s['max'] ) ) : '∞' ), mmsp_url_without( 'price' ) );
		}
		if ( $chips ) :
			?>
			<div class="mmsp-chips">
				<?php foreach ( $chips as $chip ) : ?>
					<a class="mmsp-chip" href="<?php echo esc_url( $chip[1] ); ?>"><?php echo esc_html( $chip[0] ); ?><span aria-hidden="true">×</span><span class="screen-reader-text"><?php esc_html_e( 'Remove filter', 'mine-mirth-shop-pro' ); ?></span></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<fieldset class="mmsp-group mmsp-group--stock">
			<legend><?php esc_html_e( 'Availability', 'mine-mirth-shop-pro' ); ?></legend>
			<div class="mmsp-stock">
				<?php foreach ( $avail as $key => $group ) : ?>
					<label class="mmsp-stock__opt is-<?php echo esc_attr( $key ); ?><?php echo ! $sc[ $key ] && ! in_array( $key, $s['stock'], true ) ? ' is-empty' : ''; ?>">
						<input type="checkbox" name="mmsp_stock[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $s['stock'], true ) ); ?>>
						<span class="mmsp-stock__dot" aria-hidden="true"></span>
						<span class="mmsp-stock__label"><?php echo esc_html( $group['label'] ); ?></span>
						<small><?php echo esc_html( number_format_i18n( $sc[ $key ] ) ); ?></small>
					</label>
				<?php endforeach; ?>
			</div>
		</fieldset>

		<?php
		$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name' ) );
		if ( ! is_wp_error( $cats ) && $cats ) :
			// Counts include products in subcategories.
			$padded = array();
			foreach ( $cats as $c ) {
				$padded[ $c->term_id ] = 0;
			}
			$ids = mmsp_context_ids();
			if ( $ids ) {
				foreach ( array_chunk( $ids, 500 ) as $chunk ) {
					$rows = wp_get_object_terms( $chunk, 'product_cat', array( 'fields' => 'all_with_object_id' ) );
					$seen = array();
					foreach ( is_wp_error( $rows ) ? array() : $rows as $row ) {
						$tid = (int) $row->term_id;
						while ( $tid ) {
							if ( empty( $seen[ $row->object_id ][ $tid ] ) ) {
								$seen[ $row->object_id ][ $tid ] = true;
								$padded[ $tid ]                  = ( $padded[ $tid ] ?? 0 ) + 1;
							}
							$parent = get_term_field( 'parent', $tid, 'product_cat' );
							$tid    = is_wp_error( $parent ) ? 0 : (int) $parent;
						}
					}
				}
			}
			$tree = mmsp_category_tree( $cats, $padded, 0, $s['cat'] );
			if ( $tree ) :
				?>
				<fieldset class="mmsp-group">
					<legend><?php esc_html_e( 'Categories', 'mine-mirth-shop-pro' ); ?></legend>
					<ul class="mmsp-tree"><?php echo $tree; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in mmsp_category_tree(). ?></ul>
				</fieldset>
				<?php
			endif;
		endif;

		[ $lo, $hi ] = mmsp_price_bounds();
		if ( $hi > $lo ) :
			$cur = get_woocommerce_currency_symbol();
			$vmin = null !== $s['min'] ? $s['min'] : $lo;
			$vmax = null !== $s['max'] ? $s['max'] : $hi;
			?>
			<fieldset class="mmsp-group" data-mmsp-price data-lo="<?php echo esc_attr( (string) $lo ); ?>" data-hi="<?php echo esc_attr( (string) $hi ); ?>">
				<legend><?php esc_html_e( 'Price', 'mine-mirth-shop-pro' ); ?></legend>
				<div class="mmsp-range">
					<input type="range" min="<?php echo esc_attr( (string) $lo ); ?>" max="<?php echo esc_attr( (string) $hi ); ?>" step="1" value="<?php echo esc_attr( (string) $vmin ); ?>" data-mmsp-range="min" aria-label="<?php esc_attr_e( 'Minimum price', 'mine-mirth-shop-pro' ); ?>">
					<input type="range" min="<?php echo esc_attr( (string) $lo ); ?>" max="<?php echo esc_attr( (string) $hi ); ?>" step="1" value="<?php echo esc_attr( (string) $vmax ); ?>" data-mmsp-range="max" aria-label="<?php esc_attr_e( 'Maximum price', 'mine-mirth-shop-pro' ); ?>">
				</div>
				<div class="mmsp-price">
					<label><span><?php echo esc_html( html_entity_decode( $cur ) ); ?></span><input type="number" name="mmsp_min" min="0" step="1" inputmode="numeric" placeholder="<?php echo esc_attr( (string) $lo ); ?>" value="<?php echo esc_attr( null !== $s['min'] ? (string) $s['min'] : '' ); ?>" aria-label="<?php esc_attr_e( 'Minimum price', 'mine-mirth-shop-pro' ); ?>"></label>
					<span aria-hidden="true">–</span>
					<label><span><?php echo esc_html( html_entity_decode( $cur ) ); ?></span><input type="number" name="mmsp_max" min="0" step="1" inputmode="numeric" placeholder="<?php echo esc_attr( (string) $hi ); ?>" value="<?php echo esc_attr( null !== $s['max'] ? (string) $s['max'] : '' ); ?>" aria-label="<?php esc_attr_e( 'Maximum price', 'mine-mirth-shop-pro' ); ?>"></label>
					<button type="submit" class="mmsp-go"><?php esc_html_e( 'Go', 'mine-mirth-shop-pro' ); ?></button>
				</div>
			</fieldset>
			<?php
		endif;

		foreach ( mmsp_attribute_taxonomies() as $tax => $label ) :
			$counts = mmsp_term_counts( $tax );
			$chosen = $s['attr'][ $tax ] ?? array();
			$terms  = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
			if ( is_wp_error( $terms ) ) {
				continue;
			}
			$terms = array_values(
				array_filter(
					$terms,
					static fn( $t ) => ! empty( $counts[ $t->term_id ] ) || in_array( $t->slug, $chosen, true )
				)
			);
			if ( ! $terms ) {
				continue;
			}
			// Smallest to largest by real value ("900 GH/s" before "12 TH/s", "3.4 kW" after "900 W"), otherwise natural order.
			usort(
				$terms,
				static function ( $a, $b ) {
					$x = mmsp_measure( $a->name );
					$y = mmsp_measure( $b->name );
					return ( null !== $x && null !== $y && $x !== $y ) ? $x <=> $y : strnatcasecmp( $a->name, $b->name );
				}
			);
			$as_chips = count( $terms ) <= 8;
			?>
			<fieldset class="mmsp-group" data-mmsp-attr>
				<legend><?php echo esc_html( $label ); ?><?php echo $chosen ? ' <span class="mmsp-n">' . esc_html( (string) count( $chosen ) ) . '</span>' : ''; ?></legend>
				<?php if ( ! $as_chips ) : ?>
					<input type="search" class="mmsp-find" data-mmsp-find placeholder="<?php echo esc_attr( sprintf( /* translators: %s: attribute */ __( 'Search %s', 'mine-mirth-shop-pro' ), strtolower( $label ) ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: attribute */ __( 'Search %s', 'mine-mirth-shop-pro' ), $label ) ); ?>">
				<?php endif; ?>
				<div class="<?php echo $as_chips ? 'mmsp-pills' : 'mmsp-list'; ?>">
					<?php foreach ( $terms as $term ) : ?>
						<label class="<?php echo $as_chips ? 'mmsp-pill' : 'mmsp-check'; ?>" data-name="<?php echo esc_attr( strtolower( $term->name ) ); ?>">
							<input type="checkbox" name="mmsp_attr[<?php echo esc_attr( $tax ); ?>][]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $chosen, true ) ); ?>>
							<span><?php echo esc_html( $term->name ); ?></span>
							<small><?php echo esc_html( number_format_i18n( (int) ( $counts[ $term->term_id ] ?? 0 ) ) ); ?></small>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>
		<?php endforeach; ?>

		<noscript><button type="submit" class="mmsp-apply"><?php esc_html_e( 'Apply filters', 'mine-mirth-shop-pro' ); ?></button></noscript>
	</form>
	<?php
	return (string) ob_get_clean();
}

add_shortcode( 'minemirth_shop_filters', static fn() => ( is_shop() || is_product_taxonomy() ) ? mmsp_filters_html() : '' );

/* ---------------------------------------------------------------------------
 * Place the panel in the theme's shop sidebar
 * ------------------------------------------------------------------------- */

add_filter(
	'is_active_sidebar',
	static function ( $active, $index ) {
		return ( 'sidebar-shop' === $index && mmsp_opt( 'filters_auto' ) && ! is_admin() ) ? true : $active;
	},
	10,
	2
);
add_action(
	'dynamic_sidebar_before',
	static function ( $index ) {
		if ( 'sidebar-shop' === $index && mmsp_opt( 'filters_auto' ) && ! is_admin() && ( is_shop() || is_product_taxonomy() ) ) {
			echo mmsp_filters_html(); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
);

// If the Mine Mirth Commerce plugin is active, stop its older filter panel so only one shows.
$mmsp_off = static function ( $value ) {
	if ( ! mmsp_opt( 'filters_auto' ) ) {
		return $value;
	}
	$value                 = is_array( $value ) ? $value : array();
	$value['filters_auto'] = 0;
	return $value;
};
add_filter( 'option_mmc_settings', $mmsp_off );
add_filter( 'default_option_mmc_settings', $mmsp_off );
