<?php
/**
 * Listings: every hosting plan gets a public page, plus the compact cards and the grid.
 *
 * @package MineMirthHostingListings
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ One page per plan */

/**
 * The theme registers hosting plans as private records. Make each published plan a page of its own.
 */
add_filter(
	'register_post_type_args',
	static function ( array $args, string $type ): array {
		if ( MMHL_TYPE !== $type ) {
			return $args;
		}
		$args['public']              = true;
		$args['publicly_queryable']  = true;
		$args['exclude_from_search'] = false;
		$args['show_in_nav_menus']   = true;
		$args['has_archive']         = false;
		$args['query_var']           = true;
		$args['rewrite']             = array(
			'slug'       => mmhl_slug(),
			'with_front' => false,
			'feeds'      => false,
			'pages'      => false,
		);
		$args['supports']            = array_values( array_unique( array_merge( (array) ( $args['supports'] ?? array() ), array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ) ) ) );
		return $args;
	},
	10,
	2
);

/** Availability labels, the theme's when available. */
function mmhl_availability_labels(): array {
	if ( function_exists( 'mm_hosting_availability_labels' ) ) {
		return mm_hosting_availability_labels();
	}
	return array(
		'available'   => __( 'Capacity available', 'mine-mirth-hosting-listings' ),
		'limited'     => __( 'Limited capacity', 'mine-mirth-hosting-listings' ),
		'inquiry'     => __( 'Inquiry only', 'mine-mirth-hosting-listings' ),
		'unavailable' => __( 'Fully booked', 'mine-mirth-hosting-listings' ),
	);
}

function mmhl_icon( string $name ): string {
	return function_exists( 'mm_icon' ) ? (string) mm_icon( $name ) : '';
}

/** "Hashrate: 270 TH/s" lines as label/value pairs. */
function mmhl_parse_specs( string $raw ): array {
	$out = array();
	foreach ( preg_split( "/\r\n|\r|\n/", $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = preg_split( '/\s*[:|]\s*/', $line, 2 );
		$out[] = 2 === count( $parts ) && '' !== $parts[1] ? array( $parts[0], $parts[1] ) : array( '', $line );
	}
	return $out;
}

/** Specs of a linked WooCommerce miner: hashrate, power and efficiency. */
function mmhl_product_specs( int $product_id ): array {
	if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
		return array();
	}
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return array();
	}
	$specs = array();
	$hash  = '';
	$power = '';
	foreach ( array( 'hashrate', 'hash-rate', 'hash_rate', 'hash rate' ) as $name ) {
		$hash = '' !== $hash ? $hash : trim( explode( ',', (string) $product->get_attribute( $name ) )[0] );
	}
	foreach ( array( 'power', 'power-consumption', 'power consumption', 'wattage' ) as $name ) {
		$power = '' !== $power ? $power : trim( explode( ',', (string) $product->get_attribute( $name ) )[0] );
	}
	if ( '' !== $hash ) {
		$specs[] = array( __( 'Hashrate', 'mine-mirth-hosting-listings' ), $hash );
	}
	if ( '' !== $power ) {
		$specs[] = array( __( 'Power', 'mine-mirth-hosting-listings' ), $power );
	}
	if ( function_exists( 'mm_product_mining_specs' ) ) {
		$m = mm_product_mining_specs( $product );
		if ( $m['th'] > 0 && $m['watts'] > 0 ) {
			$specs[] = array( __( 'Efficiency', 'mine-mirth-hosting-listings' ), number_format_i18n( $m['watts'] / $m['th'], 1 ) . ' J/TH' );
		}
	}
	return $specs;
}

/**
 * Everything about one listing as an array.
 */
function mmhl_listing( $post ): ?array {
	$post = get_post( $post );
	if ( ! $post || MMHL_TYPE !== $post->post_type ) {
		return null;
	}
	$id     = (int) $post->ID;
	$m      = static fn( string $k ) => get_post_meta( $id, $k, true );
	$labels = mmhl_availability_labels();
	$avail  = (string) $m( '_mm_availability' );
	$avail  = isset( $labels[ $avail ] ) ? $avail : 'inquiry';
	$miner  = absint( $m( '_mmhl_product' ) );
	$specs  = mmhl_parse_specs( (string) $m( '_mmhl_specs' ) );
	if ( ! $specs ) {
		$specs = mmhl_product_specs( $miner );
	}
	$content = (string) $post->post_content;
	$summary = has_excerpt( $post ) ? (string) $post->post_excerpt : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $content ) ), 24 );
	$gallery = array_values( array_filter( array_map( 'absint', explode( ',', (string) $m( '_mmhl_gallery' ) ) ) ) );

	$l = array(
		'id'           => $id,
		'title'        => get_the_title( $post ),
		'url'          => (string) get_permalink( $post ),
		'status'       => $post->post_status,
		'summary'      => $summary,
		'content'      => $content,
		'image_id'     => (int) get_post_thumbnail_id( $post ),
		'gallery'      => $gallery,
		'region'       => (string) $m( '_mm_region' ),
		'availability' => $avail,
		'avail_label'  => $labels[ $avail ],
		'rate'         => (string) $m( '_mm_rate' ),
		'billing'      => (string) $m( '_mm_billing' ),
		'minimum'      => (string) $m( '_mm_minimum' ),
		'cooling'      => (string) $m( '_mm_cooling' ),
		'setup'        => (string) $m( '_mm_setup' ),
		'term'         => (string) $m( '_mm_term' ),
		'featured'     => (bool) $m( '_mm_featured' ),
		'features'     => array_values( array_filter( array_map( 'trim', preg_split( "/\r\n|\r|\n/", (string) $m( '_mm_features' ) ) ) ) ),
		'fine_print'   => (string) $m( '_mm_fine_print' ),
		'cta_url'      => (string) $m( '_mm_cta_url' ),
		'specs'        => $specs,
		'miner'        => $miner,
		'order'        => (int) $post->menu_order,
		'date'         => (int) get_post_time( 'U', true, $post ),
		// Price: from the Subscriptions plugin, else the theme's linked WooCommerce product.
		'price'        => '',
		'price_note'   => '',
		'price_sort'   => 0,
		'subscribable' => false,
		'plan'         => null,
	);

	if ( function_exists( 'mms_plan' ) ) {
		$p = mms_plan( $id );
		if ( $p && $p['enabled'] && $p['amount'] > 0 ) {
			$l['plan']         = $p;
			$l['price']        = mms_money( (int) $p['amount'], (string) $p['currency'] );
			$l['price_note']   = ( $p['qty'] ? sprintf( /* translators: %s: unit, e.g. miner */ __( 'per %s', 'mine-mirth-hosting-listings' ), rtrim( (string) $p['qty_label'], 's' ) ) . ' ' : '' ) . mms_interval_label( (string) $p['interval'], (int) $p['interval_count'] );
			$l['price_sort']   = (int) $p['amount'];
			$l['subscribable'] = function_exists( 'mms_plan_ready' ) && mms_plan_ready( $p ) && 'unavailable' !== $avail;
		}
	}
	if ( '' === $l['price'] && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( absint( $m( '_mm_product_id' ) ) );
		if ( $product && '' !== (string) $product->get_price() ) {
			$l['price']      = html_entity_decode( wp_strip_all_tags( wc_price( (float) $product->get_price() ) ), ENT_QUOTES, 'UTF-8' );
			$l['price_sort'] = (int) round( (float) $product->get_price() * 100 );
		}
	}
	return apply_filters( 'mmhl_listing', $l, $post );
}

/**
 * Published listings: featured first, then your order (Order field), newest first.
 */
function mmhl_listings( array $args = array() ): array {
	$ids = get_posts(
		array(
			'post_type'        => MMHL_TYPE,
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'orderby'          => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'post__not_in'     => array_map( 'absint', (array) ( $args['exclude'] ?? array() ) ),
			'suppress_filters' => false,
		)
	);
	$out = array();
	foreach ( $ids as $id ) {
		$l = mmhl_listing( $id );
		if ( ! $l || ( 'unavailable' === $l['availability'] && ! mmhl_opt( 'show_booked' ) ) ) {
			continue;
		}
		$out[] = $l;
	}
	// Featured listings first; the rest keep their order.
	$i = 0;
	foreach ( $out as &$l ) {
		$l['rank'] = ( $l['featured'] ? 0 : 100000 ) + $i++;
	}
	unset( $l );
	usort( $out, static fn( $a, $b ) => $a['rank'] <=> $b['rank'] );
	return $out;
}

/** Link attributes for a listing link: a new tab when that is on. */
function mmhl_link_attrs( ?bool $new_tab = null ): string {
	$new_tab = null === $new_tab ? (bool) mmhl_opt( 'new_tab' ) : $new_tab;
	return $new_tab ? ' target="_blank" rel="noopener"' : '';
}

/* ------------------------------------------------------------------ Card */

function mmhl_card( array $l ): string {
	$new_tab = (bool) mmhl_opt( 'new_tab' );
	$specs   = mmhl_opt( 'show_specs' ) ? array_slice( array_values( array_filter( $l['specs'], static fn( $s ) => '' !== $s[0] ) ), 0, 3 ) : array();
	$search  = strtolower( wp_strip_all_tags( $l['title'] . ' ' . $l['region'] . ' ' . $l['summary'] . ' ' . implode( ' ', array_map( static fn( $s ) => $s[1], $l['specs'] ) ) ) );
	$classes = 'mmhl-card' . ( $l['featured'] ? ' is-featured' : '' ) . ( 'unavailable' === $l['availability'] ? ' is-booked' : '' );
	ob_start();
	?>
	<article class="<?php echo esc_attr( $classes ); ?>" data-mmhl-item data-region="<?php echo esc_attr( sanitize_title( $l['region'] ) ); ?>" data-avail="<?php echo esc_attr( $l['availability'] ); ?>" data-price="<?php echo (int) $l['price_sort']; ?>" data-rank="<?php echo (int) ( $l['rank'] ?? 0 ); ?>" data-date="<?php echo (int) $l['date']; ?>" data-search="<?php echo esc_attr( $search ); ?>">
		<div class="mmhl-card__media">
			<?php if ( $l['image_id'] ) : ?>
				<?php echo wp_get_attachment_image( $l['image_id'], 'medium_large', false, array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => esc_attr( $l['title'] ), 'sizes' => '(max-width: 640px) 100vw, (max-width: 1100px) 50vw, 420px' ) ); ?>
			<?php else : ?>
				<span class="mmhl-card__ph" aria-hidden="true"><?php echo mmhl_icon( 'server' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<?php endif; ?>
			<span class="mm-avail mm-avail--<?php echo esc_attr( $l['availability'] ); ?> mmhl-card__avail"><?php echo esc_html( $l['avail_label'] ); ?></span>
			<?php if ( $l['featured'] ) : ?>
				<span class="mmhl-card__flag"><?php esc_html_e( 'Featured', 'mine-mirth-hosting-listings' ); ?></span>
			<?php endif; ?>
		</div>
		<div class="mmhl-card__body">
			<?php if ( $l['region'] ) : ?>
				<span class="mmhl-card__region"><?php echo mmhl_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $l['region'] ); ?></span>
			<?php endif; ?>
			<h3 class="mmhl-card__title"><a href="<?php echo esc_url( $l['url'] ); ?>"<?php echo mmhl_link_attrs( $new_tab ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( $l['title'] ); ?><?php echo $new_tab ? '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'mine-mirth-hosting-listings' ) . '</span>' : ''; ?></a></h3>
			<?php if ( mmhl_opt( 'show_summary' ) && '' !== $l['summary'] ) : ?>
				<p class="mmhl-card__summary"><?php echo esc_html( $l['summary'] ); ?></p>
			<?php endif; ?>
			<?php if ( $specs ) : ?>
				<ul class="mmhl-card__specs">
					<?php foreach ( $specs as $s ) : ?>
						<li><span><?php echo esc_html( $s[0] ); ?></span> <strong><?php echo esc_html( $s[1] ); ?></strong></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div class="mmhl-card__foot">
				<div class="mmhl-card__price<?php echo '' === $l['price'] || ! mmhl_opt( 'show_price' ) ? ' is-ask' : ''; ?>">
					<?php if ( mmhl_opt( 'show_price' ) && '' !== $l['price'] ) : ?>
						<strong><?php echo esc_html( $l['price'] ); ?></strong>
						<?php if ( $l['price_note'] ) : ?>
							<span><?php echo esc_html( $l['price_note'] ); ?></span>
						<?php endif; ?>
					<?php else : ?>
						<strong><?php esc_html_e( 'Price on request', 'mine-mirth-hosting-listings' ); ?></strong>
					<?php endif; ?>
					<?php if ( mmhl_opt( 'show_rate' ) && '' !== $l['rate'] ) : ?>
						<em class="mmhl-card__rate"><?php echo mmhl_icon( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $l['rate'] ); ?></em>
					<?php endif; ?>
				</div>
				<span class="mmhl-card__go"><?php esc_html_e( 'View details', 'mine-mirth-hosting-listings' ); ?><?php echo mmhl_icon( $new_tab ? 'arrow-up-right' : 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</div>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ Grid */

/**
 * The listing grid, with an optional search, filter and sort bar.
 *
 * @param array $atts toolbar (bool), limit (int, 0 = all), exclude (array of IDs), per_page, columns.
 */
function mmhl_grid( array $atts = array() ): string {
	$atts     = wp_parse_args(
		$atts,
		array(
			'toolbar'  => true,
			'limit'    => 0,
			'exclude'  => array(),
			'per_page' => (int) mmhl_opt( 'per_page' ),
			'columns'  => (int) mmhl_opt( 'columns' ),
			'empty'    => true,
		)
	);
	$listings = mmhl_listings( array( 'exclude' => $atts['exclude'] ) );
	if ( (int) $atts['limit'] > 0 ) {
		$listings = array_slice( $listings, 0, (int) $atts['limit'] );
	}
	wp_enqueue_style( 'mmhl' );
	wp_enqueue_script( 'mmhl' );
	if ( ! $listings ) {
		if ( ! $atts['empty'] ) {
			return '';
		}
		$msg = current_user_can( 'edit_posts' )
			? __( 'No hosting listings are published yet. Add one under Hosting Plans › Add new: give it an image, a price and its details.', 'mine-mirth-hosting-listings' )
			: __( 'New hosting listings are on their way. Contact us for current availability.', 'mine-mirth-hosting-listings' );
		return '<div class="mm-plans-empty mmhl-empty">' . esc_html( $msg ) . '</div>';
	}
	$cols     = max( 2, min( 4, (int) $atts['columns'] ) );
	$per_page = max( 0, (int) $atts['per_page'] );
	$toolbar  = $atts['toolbar'] && count( $listings ) > 1 && ( mmhl_opt( 'search' ) || mmhl_opt( 'filters' ) || mmhl_opt( 'sort' ) );

	ob_start();
	?>
	<div class="mmhl" data-mmhl data-per-page="<?php echo (int) $per_page; ?>">
		<?php if ( $toolbar ) : ?>
			<?php
			$regions = array();
			$avails  = array();
			foreach ( $listings as $l ) {
				if ( '' !== $l['region'] ) {
					$regions[ sanitize_title( $l['region'] ) ] = $l['region'];
				}
				$avails[ $l['availability'] ] = $l['avail_label'];
			}
			asort( $regions );
			?>
			<div class="mmhl-toolbar" role="search">
				<?php if ( mmhl_opt( 'search' ) ) : ?>
					<label class="mmhl-search">
						<?php echo mmhl_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span class="screen-reader-text"><?php esc_html_e( 'Search hosting listings', 'mine-mirth-hosting-listings' ); ?></span>
						<input type="search" placeholder="<?php esc_attr_e( 'Search miner, model or location', 'mine-mirth-hosting-listings' ); ?>" data-mmhl-q autocomplete="off">
					</label>
				<?php endif; ?>
				<?php if ( mmhl_opt( 'filters' ) && count( $regions ) > 1 ) : ?>
					<label class="mmhl-select"><span class="screen-reader-text"><?php esc_html_e( 'Location', 'mine-mirth-hosting-listings' ); ?></span>
						<select data-mmhl-region>
							<option value=""><?php esc_html_e( 'All locations', 'mine-mirth-hosting-listings' ); ?></option>
							<?php foreach ( $regions as $k => $r ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $r ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>
				<?php if ( mmhl_opt( 'filters' ) && count( $avails ) > 1 ) : ?>
					<label class="mmhl-select"><span class="screen-reader-text"><?php esc_html_e( 'Availability', 'mine-mirth-hosting-listings' ); ?></span>
						<select data-mmhl-avail>
							<option value=""><?php esc_html_e( 'Availability', 'mine-mirth-hosting-listings' ); ?></option>
							<?php foreach ( $avails as $k => $a ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $a ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>
				<?php if ( mmhl_opt( 'sort' ) ) : ?>
					<label class="mmhl-select"><span class="screen-reader-text"><?php esc_html_e( 'Sort by', 'mine-mirth-hosting-listings' ); ?></span>
						<select data-mmhl-sort>
							<option value="rank"><?php esc_html_e( 'Recommended', 'mine-mirth-hosting-listings' ); ?></option>
							<option value="price-asc"><?php esc_html_e( 'Price: low to high', 'mine-mirth-hosting-listings' ); ?></option>
							<option value="price-desc"><?php esc_html_e( 'Price: high to low', 'mine-mirth-hosting-listings' ); ?></option>
							<option value="date"><?php esc_html_e( 'Newest', 'mine-mirth-hosting-listings' ); ?></option>
						</select>
					</label>
				<?php endif; ?>
				<span class="mmhl-count" data-mmhl-count aria-live="polite" data-one="<?php /* translators: %d: number of listings */ echo esc_attr( _n( '%d listing', '%d listings', 1, 'mine-mirth-hosting-listings' ) ); ?>" data-many="<?php /* translators: %d: number of listings */ echo esc_attr( _n( '%d listing', '%d listings', 2, 'mine-mirth-hosting-listings' ) ); ?>">
					<?php
					/* translators: %d: number of listings */
					echo esc_html( sprintf( _n( '%d listing', '%d listings', count( $listings ), 'mine-mirth-hosting-listings' ), count( $listings ) ) );
					?>
				</span>
			</div>
		<?php endif; ?>
		<div class="mmhl-grid mmhl-grid--<?php echo (int) $cols; ?>" data-mmhl-grid>
			<?php
			foreach ( $listings as $l ) {
				echo mmhl_card( $l ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</div>
		<p class="mmhl-none" data-mmhl-none hidden><?php esc_html_e( 'No listing matches. Try another search or clear the filters.', 'mine-mirth-hosting-listings' ); ?> <button type="button" class="mmhl-link" data-mmhl-reset><?php esc_html_e( 'Clear filters', 'mine-mirth-hosting-listings' ); ?></button></p>
		<?php if ( $per_page > 0 && count( $listings ) > $per_page ) : ?>
			<div class="mmhl-more"><button type="button" class="mm-btn mm-btn--ghost" data-mmhl-more><span><?php esc_html_e( 'Show more listings', 'mine-mirth-hosting-listings' ); ?></span><?php echo mmhl_icon( 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button></div>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ Shortcodes and theme sections */

add_shortcode(
	'mm_hosting_listings',
	static function ( $atts ): string {
		$atts = shortcode_atts( array( 'limit' => 0, 'toolbar' => 1, 'columns' => (int) mmhl_opt( 'columns' ) ), (array) $atts );
		return mmhl_grid( array( 'limit' => (int) $atts['limit'], 'toolbar' => (bool) (int) $atts['toolbar'], 'columns' => (int) $atts['columns'] ) );
	}
);

/**
 * Wherever the theme (or the Subscriptions plugin) lists hosting plans, show the compact cards instead.
 * Runs after the Subscriptions plugin, which takes over the same shortcodes at priority 30.
 */
add_action(
	'init',
	static function (): void {
		if ( ! mmhl_opt( 'everywhere' ) ) {
			return;
		}
		foreach ( array( 'mm_hosting_plans', 'mine_mirth_hosting_plans', 'mm_subscription_plans' ) as $tag ) {
			remove_shortcode( $tag );
			add_shortcode(
				$tag,
				static function ( $atts ): string {
					$atts = shortcode_atts( array( 'limit' => 0 ), (array) $atts );
					return mmhl_grid( array( 'limit' => (int) $atts['limit'], 'toolbar' => false ) );
				}
			);
		}
	},
	40
);

/** The theme's "Hosting plans" page section: replace its plan list (before the Subscriptions plugin does). */
add_filter(
	'render_block',
	static function ( $html, $block ) {
		if ( ! mmhl_opt( 'everywhere' ) || 'mm/section' !== ( $block['blockName'] ?? '' ) || 'pricing' !== ( $block['attrs']['variant'] ?? '' ) ) {
			return $html;
		}
		$new = preg_replace( '#<div class="mm-plans">.*</article>\s*</div>|<div class="mm-plans-empty">.*?</div>#s', '%%MMHL_GRID%%', (string) $html, 1, $count );
		return $count ? str_replace( '%%MMHL_GRID%%', mmhl_grid( array( 'toolbar' => false ) ), (string) $new ) : $html;
	},
	5,
	2
);
