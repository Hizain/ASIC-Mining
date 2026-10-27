<?php
/**
 * In-content promotions.
 *
 * Native-looking promo cards inside articles that let readers add to cart,
 * quick-view a product or run the calculator without leaving the post.
 *
 *   Admin › Promotions           create targeted promos (type, placement, categories, schedule)
 *   Customizer › Blog › Promos   placement rules + automatic fallbacks
 *   Per post                     "Promotions" box: automatic / off / choose promos
 *   Manual                       [mm_promo id="12"], [mm_product id="34"], [mm_cta], block "Mine Mirth promo"
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Post type + fields
 * ------------------------------------------------------------------------- */
function mm_register_promo_post_types(): void {
	register_post_type(
		'mm_promo',
		array(
			'labels'          => array(
				'name'          => __( 'Promotions', 'minemirth' ),
				'singular_name' => __( 'Promotion', 'minemirth' ),
				'add_new_item'  => __( 'Add promotion', 'minemirth' ),
				'edit_item'     => __( 'Edit promotion', 'minemirth' ),
				'all_items'     => __( 'All promotions', 'minemirth' ),
				'menu_name'     => __( 'Promotions', 'minemirth' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'show_in_rest'    => true,
			'menu_icon'       => 'dashicons-megaphone',
			'menu_position'   => 26,
			'supports'        => array( 'title', 'editor', 'thumbnail' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'mm_register_promo_post_types' );

function mm_promo_types(): array {
	return array(
		'product'    => __( 'Single product card (add to cart + quick view)', 'minemirth' ),
		'products'   => __( 'Product row (up to 3)', 'minemirth' ),
		'banner'     => __( 'Banner with button', 'minemirth' ),
		'calculator' => __( 'Mini profitability calculator', 'minemirth' ),
		'newsletter' => __( 'Newsletter signup', 'minemirth' ),
		'custom'     => __( 'Custom content (uses the editor above)', 'minemirth' ),
	);
}

function mm_promo_placements(): array {
	return array(
		'inline'  => __( 'Inside article content', 'minemirth' ),
		'sidebar' => __( 'Sticky sidebar', 'minemirth' ),
		'slidein' => __( 'Slide-in while reading', 'minemirth' ),
		'end'     => __( 'End of article', 'minemirth' ),
	);
}

function mm_promo_meta( int $id, string $key, $default = '' ) {
	$value = get_post_meta( $id, '_mm_promo_' . $key, true );
	return '' === $value || null === $value ? $default : $value;
}

function mm_promo_add_meta_boxes(): void {
	add_meta_box( 'mm-promo-settings', __( 'Promotion settings', 'minemirth' ), 'mm_promo_meta_box', 'mm_promo', 'normal', 'high' );
	add_meta_box( 'mm-post-promos', __( 'Mine Mirth promotions', 'minemirth' ), 'mm_post_promo_meta_box', 'post', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'mm_promo_add_meta_boxes' );

function mm_promo_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'mm_promo_save', 'mm_promo_nonce' );
	$id         = $post->ID;
	$type       = mm_promo_meta( $id, 'type', 'product' );
	$placements = (array) mm_promo_meta( $id, 'placements', array( 'inline' ) );
	$cats       = (array) mm_promo_meta( $id, 'categories', array() );
	?>
	<style>
		.mm-pm{display:grid;grid-template-columns:1fr 1fr;gap:16px 20px}
		.mm-pm label{display:block;font-weight:600;margin-bottom:5px}
		.mm-pm input[type=text],.mm-pm input[type=url],.mm-pm input[type=number],.mm-pm input[type=date],.mm-pm select,.mm-pm textarea{width:100%}
		.mm-pm .wide{grid-column:1/-1}.mm-pm .help{color:#646970;font-size:12px;margin-top:4px}
		.mm-pm .checks{display:flex;flex-wrap:wrap;gap:6px 18px;font-weight:400}
		.mm-pm .checks label{font-weight:400;display:inline-flex;gap:6px;align-items:center}
		.mm-pm .cats{max-height:150px;overflow:auto;border:1px solid #dcdcde;padding:8px;border-radius:4px}
		@media(max-width:782px){.mm-pm{grid-template-columns:1fr}}
	</style>
	<div class="mm-pm">
		<div>
			<label for="mm_promo_type"><?php esc_html_e( 'Type', 'minemirth' ); ?></label>
			<select id="mm_promo_type" name="mm_promo[type]">
				<?php foreach ( mm_promo_types() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $type, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<label for="mm_promo_products"><?php esc_html_e( 'Product IDs (comma separated)', 'minemirth' ); ?></label>
			<input type="text" id="mm_promo_products" name="mm_promo[products]" value="<?php echo esc_attr( (string) mm_promo_meta( $id, 'products' ) ); ?>" placeholder="123, 456">
			<div class="help"><?php esc_html_e( 'For product types. Find the ID by hovering a product in Products.', 'minemirth' ); ?></div>
		</div>
		<div>
			<label for="mm_promo_label"><?php esc_html_e( 'Label', 'minemirth' ); ?></label>
			<input type="text" id="mm_promo_label" name="mm_promo[label]" value="<?php echo esc_attr( (string) mm_promo_meta( $id, 'label' ) ); ?>" placeholder="<?php echo esc_attr( (string) mm_opt( 'promo_label' ) ); ?>">
		</div>
		<div>
			<label for="mm_promo_icon"><?php esc_html_e( 'Icon (banner)', 'minemirth' ); ?></label>
			<select id="mm_promo_icon" name="mm_promo[icon]">
				<?php foreach ( mm_icon_choices() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( mm_promo_meta( $id, 'icon', 'server' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="wide">
			<label for="mm_promo_heading"><?php esc_html_e( 'Headline (defaults to the title)', 'minemirth' ); ?></label>
			<input type="text" id="mm_promo_heading" name="mm_promo[heading]" value="<?php echo esc_attr( (string) mm_promo_meta( $id, 'heading' ) ); ?>">
		</div>
		<div class="wide">
			<label for="mm_promo_text"><?php esc_html_e( 'Text', 'minemirth' ); ?></label>
			<textarea id="mm_promo_text" rows="3" name="mm_promo[text]"><?php echo esc_textarea( (string) mm_promo_meta( $id, 'text' ) ); ?></textarea>
		</div>
		<div>
			<label for="mm_promo_btn"><?php esc_html_e( 'Button text', 'minemirth' ); ?></label>
			<input type="text" id="mm_promo_btn" name="mm_promo[button_text]" value="<?php echo esc_attr( (string) mm_promo_meta( $id, 'button_text' ) ); ?>">
		</div>
		<div>
			<label for="mm_promo_url"><?php esc_html_e( 'Button URL', 'minemirth' ); ?></label>
			<input type="text" id="mm_promo_url" name="mm_promo[button_url]" value="<?php echo esc_attr( (string) mm_promo_meta( $id, 'button_url' ) ); ?>" placeholder="/miner-hosting/ or {shop}">
		</div>
		<div class="wide">
			<label><?php esc_html_e( 'Where it can appear', 'minemirth' ); ?></label>
			<div class="checks">
				<?php foreach ( mm_promo_placements() as $key => $label ) : ?>
					<label><input type="checkbox" name="mm_promo[placements][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $placements, true ) ); ?>> <?php echo esc_html( $label ); ?></label>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="wide">
			<label><?php esc_html_e( 'Only on articles in these categories (none = all)', 'minemirth' ); ?></label>
			<div class="cats checks">
				<?php foreach ( get_categories( array( 'hide_empty' => false ) ) as $cat ) : ?>
					<label><input type="checkbox" name="mm_promo[categories][]" value="<?php echo esc_attr( (string) $cat->term_id ); ?>" <?php checked( in_array( (string) $cat->term_id, array_map( 'strval', $cats ), true ) ); ?>> <?php echo esc_html( $cat->name ); ?></label>
				<?php endforeach; ?>
			</div>
		</div>
		<div>
			<label for="mm_promo_priority"><?php esc_html_e( 'Priority (higher shows first)', 'minemirth' ); ?></label>
			<input type="number" id="mm_promo_priority" name="mm_promo[priority]" value="<?php echo esc_attr( (string) mm_promo_meta( $id, 'priority', 10 ) ); ?>">
		</div>
		<div>
			<label for="mm_promo_device"><?php esc_html_e( 'Devices', 'minemirth' ); ?></label>
			<select id="mm_promo_device" name="mm_promo[device]">
				<?php foreach ( array( 'all' => __( 'All devices', 'minemirth' ), 'desktop' => __( 'Desktop only', 'minemirth' ), 'mobile' => __( 'Mobile only', 'minemirth' ) ) as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( mm_promo_meta( $id, 'device', 'all' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<label for="mm_promo_start"><?php esc_html_e( 'Start date (optional)', 'minemirth' ); ?></label>
			<input type="date" id="mm_promo_start" name="mm_promo[start]" value="<?php echo esc_attr( (string) mm_promo_meta( $id, 'start' ) ); ?>">
		</div>
		<div>
			<label for="mm_promo_end"><?php esc_html_e( 'End date (optional)', 'minemirth' ); ?></label>
			<input type="date" id="mm_promo_end" name="mm_promo[end]" value="<?php echo esc_attr( (string) mm_promo_meta( $id, 'end' ) ); ?>">
		</div>
		<div class="wide">
			<label for="mm_promo_exclude"><?php esc_html_e( 'Never show on these post IDs', 'minemirth' ); ?></label>
			<input type="text" id="mm_promo_exclude" name="mm_promo[exclude]" value="<?php echo esc_attr( (string) mm_promo_meta( $id, 'exclude' ) ); ?>">
			<div class="help"><?php esc_html_e( 'Shortcode for manual placement:', 'minemirth' ); ?> <code>[mm_promo id="<?php echo esc_html( (string) $id ); ?>"]</code></div>
		</div>
	</div>
	<?php
}

function mm_promo_save( int $post_id ): void {
	if ( ! isset( $_POST['mm_promo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mm_promo_nonce'] ) ), 'mm_promo_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in = isset( $_POST['mm_promo'] ) && is_array( $_POST['mm_promo'] ) ? wp_unslash( $_POST['mm_promo'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.

	$type = isset( $in['type'] ) && array_key_exists( $in['type'], mm_promo_types() ) ? $in['type'] : 'product';
	$data = array(
		'type'        => $type,
		'products'    => implode( ',', array_filter( array_map( 'absint', explode( ',', (string) ( $in['products'] ?? '' ) ) ) ) ),
		'label'       => sanitize_text_field( (string) ( $in['label'] ?? '' ) ),
		'icon'        => array_key_exists( (string) ( $in['icon'] ?? '' ), mm_icon_choices() ) ? (string) $in['icon'] : 'server',
		'heading'     => sanitize_text_field( (string) ( $in['heading'] ?? '' ) ),
		'text'        => sanitize_textarea_field( (string) ( $in['text'] ?? '' ) ),
		'button_text' => sanitize_text_field( (string) ( $in['button_text'] ?? '' ) ),
		'button_url'  => mm_sanitize_url_setting( (string) ( $in['button_url'] ?? '' ) ),
		'placements'  => array_values( array_intersect( (array) ( $in['placements'] ?? array() ), array_keys( mm_promo_placements() ) ) ),
		'categories'  => array_values( array_filter( array_map( 'absint', (array) ( $in['categories'] ?? array() ) ) ) ),
		'priority'    => (int) ( $in['priority'] ?? 10 ),
		'device'      => in_array( $in['device'] ?? 'all', array( 'all', 'desktop', 'mobile' ), true ) ? $in['device'] : 'all',
		'start'       => preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $in['start'] ?? '' ) ) ? $in['start'] : '',
		'end'         => preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $in['end'] ?? '' ) ) ? $in['end'] : '',
		'exclude'     => implode( ',', array_filter( array_map( 'absint', explode( ',', (string) ( $in['exclude'] ?? '' ) ) ) ) ),
	);
	foreach ( $data as $key => $value ) {
		update_post_meta( $post_id, '_mm_promo_' . $key, $value );
	}
}
add_action( 'save_post_mm_promo', 'mm_promo_save' );

/**
 * Per-article controls.
 */
function mm_post_promo_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'mm_post_promo_save', 'mm_post_promo_nonce' );
	$mode   = (string) get_post_meta( $post->ID, '_mm_promo_mode', true );
	$chosen = array_map( 'intval', explode( ',', (string) get_post_meta( $post->ID, '_mm_promo_inline', true ) ) );
	$promos = get_posts(
		array(
			'post_type'   => 'mm_promo',
			'numberposts' => 100,
			'post_status' => 'publish',
		)
	);
	?>
	<p>
		<label for="mm_promo_mode" style="display:block;font-weight:600;margin-bottom:4px"><?php esc_html_e( 'On this article', 'minemirth' ); ?></label>
		<select id="mm_promo_mode" name="mm_promo_mode" style="width:100%">
			<option value="" <?php selected( $mode, '' ); ?>><?php esc_html_e( 'Automatic', 'minemirth' ); ?></option>
			<option value="inline_off" <?php selected( $mode, 'inline_off' ); ?>><?php esc_html_e( 'No in-content promos', 'minemirth' ); ?></option>
			<option value="off" <?php selected( $mode, 'off' ); ?>><?php esc_html_e( 'No promotions at all', 'minemirth' ); ?></option>
		</select>
	</p>
	<?php if ( $promos ) : ?>
		<p style="margin-bottom:4px;font-weight:600"><?php esc_html_e( 'Use these in-content promos', 'minemirth' ); ?></p>
		<div style="max-height:160px;overflow:auto">
			<?php foreach ( $promos as $promo ) : ?>
				<label style="display:block;margin:3px 0"><input type="checkbox" name="mm_promo_inline[]" value="<?php echo esc_attr( (string) $promo->ID ); ?>" <?php checked( in_array( $promo->ID, $chosen, true ) ); ?>> <?php echo esc_html( get_the_title( $promo ) ); ?></label>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php esc_html_e( 'Leave empty to pick automatically.', 'minemirth' ); ?></p>
	<?php else : ?>
		<p class="description"><?php esc_html_e( 'Create promotions under Promotions in the admin menu, or rely on the automatic product/calculator cards.', 'minemirth' ); ?></p>
	<?php endif; ?>
	<?php
}

function mm_post_promo_save( int $post_id ): void {
	if ( ! isset( $_POST['mm_post_promo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mm_post_promo_nonce'] ) ), 'mm_post_promo_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$mode = isset( $_POST['mm_promo_mode'] ) ? sanitize_key( $_POST['mm_promo_mode'] ) : '';
	update_post_meta( $post_id, '_mm_promo_mode', in_array( $mode, array( 'off', 'inline_off' ), true ) ? $mode : '' );
	$ids = isset( $_POST['mm_promo_inline'] ) ? array_filter( array_map( 'absint', (array) $_POST['mm_promo_inline'] ) ) : array();
	update_post_meta( $post_id, '_mm_promo_inline', implode( ',', $ids ) );
}
add_action( 'save_post_post', 'mm_post_promo_save' );

function mm_promo_admin_columns( array $cols ): array {
	$new = array();
	foreach ( $cols as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['mm_type']       = __( 'Type', 'minemirth' );
			$new['mm_placements'] = __( 'Placements', 'minemirth' );
			$new['mm_shortcode']  = __( 'Shortcode', 'minemirth' );
		}
	}
	return $new;
}
add_filter( 'manage_mm_promo_posts_columns', 'mm_promo_admin_columns' );

function mm_promo_admin_column( string $col, int $id ): void {
	if ( 'mm_type' === $col ) {
		$types = mm_promo_types();
		$type  = (string) mm_promo_meta( $id, 'type', 'product' );
		echo esc_html( strtok( $types[ $type ] ?? $type, '(' ) );
	} elseif ( 'mm_placements' === $col ) {
		$labels = mm_promo_placements();
		echo esc_html( implode( ', ', array_map( static fn( $p ) => $labels[ $p ] ?? $p, (array) mm_promo_meta( $id, 'placements', array() ) ) ) );
	} elseif ( 'mm_shortcode' === $col ) {
		echo '<code>[mm_promo id="' . esc_html( (string) $id ) . '"]</code>';
	}
}
add_action( 'manage_mm_promo_posts_custom_column', 'mm_promo_admin_column', 10, 2 );

/* ---------------------------------------------------------------------------
 * Selection
 * ------------------------------------------------------------------------- */

/**
 * Should promos run on this request?
 */
function mm_promos_active( ?WP_Post $post = null ): bool {
	$post = $post ? $post : get_post();
	if ( ! $post || 'post' !== $post->post_type || ! mm_opt( 'promo_enable' ) || is_feed() || is_admin() ) {
		return false;
	}
	return 'off' !== get_post_meta( $post->ID, '_mm_promo_mode', true );
}

/**
 * Promo IDs eligible for a placement on a post, best first.
 */
function mm_promos_for( string $placement, WP_Post $post ): array {
	static $cache = array();
	$key = $placement . ':' . $post->ID;
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	if ( 'inline' === $placement ) {
		$chosen = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, '_mm_promo_inline', true ) ) ) );
		if ( $chosen ) {
			return $cache[ $key ] = array_values( array_filter( $chosen, static fn( $id ) => 'publish' === get_post_status( $id ) ) );
		}
	}

	$all = get_posts(
		array(
			'post_type'   => 'mm_promo',
			'post_status' => 'publish',
			'numberposts' => 50,
			'fields'      => 'ids',
		)
	);
	$cats  = wp_get_post_categories( $post->ID );
	$today = wp_date( 'Y-m-d' );
	$found = array();
	foreach ( $all as $id ) {
		if ( ! in_array( $placement, (array) mm_promo_meta( $id, 'placements', array() ), true ) ) {
			continue;
		}
		$targets = array_map( 'intval', (array) mm_promo_meta( $id, 'categories', array() ) );
		if ( $targets && ! array_intersect( $targets, $cats ) ) {
			continue;
		}
		if ( in_array( $post->ID, array_map( 'intval', explode( ',', (string) mm_promo_meta( $id, 'exclude' ) ) ), true ) ) {
			continue;
		}
		$start = (string) mm_promo_meta( $id, 'start' );
		$end   = (string) mm_promo_meta( $id, 'end' );
		if ( ( $start && $today < $start ) || ( $end && $today > $end ) ) {
			continue;
		}
		$found[ $id ] = (int) mm_promo_meta( $id, 'priority', 10 ) + ( $targets ? 1000 : 0 );
	}
	arsort( $found );
	return $cache[ $key ] = array_keys( $found );
}

/**
 * Products for automatic product cards (featured → best sellers → newest).
 */
function mm_promo_auto_products( int $count = 6 ): array {
	if ( ! mm_is_woo() ) {
		return array();
	}
	$ids = get_transient( 'mm_promo_auto_products' );
	if ( ! is_array( $ids ) ) {
		$base = array(
			'status'     => 'publish',
			'limit'      => 12,
			'return'     => 'ids',
			'visibility' => 'catalog',
		);
		$ids  = wc_get_products( array_merge( $base, array( 'featured' => true ) ) );
		if ( count( $ids ) < $count ) {
			$ids = array_merge( $ids, wc_get_products( array_merge( $base, array( 'orderby' => 'popularity' ) ) ) );
		}
		$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
		set_transient( 'mm_promo_auto_products', $ids, HOUR_IN_SECONDS );
	}
	return array_slice( $ids, 0, $count );
}
add_action( 'save_post_product', static fn() => delete_transient( 'mm_promo_auto_products' ) );

/**
 * Topic signatures already shown on this request (so an article never
 * repeats the same promotion in several places).
 */
function mm_promo_topics( array $spec ): array {
	$topics = array();
	if ( ! empty( $spec['id'] ) ) {
		$topics[] = 'id:' . (int) $spec['id'];
	}
	$type = $spec['type'] ?? 'banner';
	switch ( $type ) {
		case 'product':
		case 'products':
			foreach ( (array) ( $spec['products'] ?? array() ) as $pid ) {
				$topics[] = 'product:' . (int) $pid;
			}
			break;
		case 'banner':
			$topics[] = 'banner:' . ( ! empty( $spec['button_url'] ) ? $spec['button_url'] : ( $spec['heading'] ?? '' ) );
			break;
		default:
			$topics[] = $type;
	}
	return $topics;
}

function mm_promo_seen( array $spec ): bool {
	$seen = $GLOBALS['mm_promo_seen'] ?? array();
	foreach ( mm_promo_topics( $spec ) as $topic ) {
		if ( isset( $seen[ $topic ] ) ) {
			return true;
		}
	}
	return false;
}

function mm_promo_mark_seen( array $spec ): void {
	foreach ( mm_promo_topics( $spec ) as $topic ) {
		$GLOBALS['mm_promo_seen'][ $topic ] = true;
	}
}

/**
 * Fallback promo spec for a placement when no promotion matches. Picks the
 * first candidate that has not already been shown on this page.
 *
 * @param string $placement inline|sidebar|slidein|end.
 * @param int    $slot      Inline slot index (0-based).
 */
function mm_promo_fallback( string $placement, int $slot = 0 ): ?array {
	$mode     = (string) mm_opt( 'promo_fallback' );
	$products = mm_promo_auto_products();
	if ( 'none' === $mode ) {
		return null;
	}

	$hosting = array(
		'type'        => 'banner',
		'icon'        => 'server',
		'heading'     => (string) mm_opt( 'promo_hosting_title' ),
		'text'        => (string) mm_opt( 'promo_hosting_text' ),
		'button_text' => __( 'See hosting plans', 'minemirth' ),
		'button_url'  => '/miner-hosting/',
	);
	$calc    = array(
		'type'    => 'calculator',
		'heading' => (string) mm_opt( 'promo_calc_title' ),
	);
	$product = static function ( int $offset ) use ( $products ) {
		if ( ! $products ) {
			return null;
		}
		return array(
			'type'     => 'product',
			'products' => array( $products[ $offset % count( $products ) ] ),
		);
	};
	$row     = count( $products ) >= 3 ? array(
		'type'        => 'products',
		'products'    => array_slice( $products, 0, 3 ),
		'heading'     => __( 'Popular with miners reading this', 'minemirth' ),
		'button_text' => __( 'Browse all miners', 'minemirth' ),
		'button_url'  => '{shop}',
	) : null;

	switch ( $mode ) {
		case 'products':
			$candidates = 'end' === $placement ? array( $row, $product( 3 ), $product( 4 ) ) : array( $product( $slot ), $product( $slot + 1 ), $product( $slot + 2 ), $product( $slot + 3 ) );
			break;
		case 'calculator':
			$candidates = array( $calc );
			break;
		case 'hosting':
			$candidates = array( $hosting );
			break;
		default: // Smart mix.
			if ( 'inline' === $placement ) {
				$cycle      = array( $product( 0 ), $calc, $product( 1 ), $hosting );
				$candidates = array( $cycle[ $slot % 4 ], $calc, $hosting, $product( $slot + 2 ) );
			} elseif ( 'sidebar' === $placement ) {
				$candidates = array( $product( 2 ), $hosting, $calc );
			} elseif ( 'slidein' === $placement ) {
				$candidates = array( $product( 1 ), $product( 3 ), $calc, $hosting );
			} else {
				$candidates = array( $row, $calc, $hosting, $product( 4 ), array( 'type' => 'newsletter' ) );
			}
	}

	foreach ( array_filter( $candidates ) as $candidate ) {
		if ( ! mm_promo_seen( $candidate ) ) {
			return $candidate;
		}
	}
	// Calculator / banner modes intentionally repeat their only option once per slot.
	return in_array( $mode, array( 'calculator', 'hosting' ), true ) && 'inline' === $placement ? reset( $candidates ) : null;
}

/**
 * Normalize a promo post ID into a spec array.
 */
function mm_promo_spec( int $id ): ?array {
	$post = get_post( $id );
	if ( ! $post || 'mm_promo' !== $post->post_type || 'publish' !== $post->post_status ) {
		return null;
	}
	$type = (string) mm_promo_meta( $id, 'type', 'product' );
	return array(
		'id'          => $id,
		'type'        => $type,
		'products'    => array_filter( array_map( 'absint', explode( ',', (string) mm_promo_meta( $id, 'products' ) ) ) ),
		'label'       => (string) mm_promo_meta( $id, 'label' ),
		'icon'        => (string) mm_promo_meta( $id, 'icon', 'server' ),
		'heading'     => (string) mm_promo_meta( $id, 'heading', 'product' === $type ? '' : get_the_title( $post ) ),
		'text'        => (string) mm_promo_meta( $id, 'text' ),
		'button_text' => (string) mm_promo_meta( $id, 'button_text' ),
		'button_url'  => (string) mm_promo_meta( $id, 'button_url' ),
		'image'       => (int) get_post_thumbnail_id( $post ),
		'device'      => (string) mm_promo_meta( $id, 'device', 'all' ),
		'content'     => $post->post_content,
	);
}

/* ---------------------------------------------------------------------------
 * Rendering
 * ------------------------------------------------------------------------- */

/**
 * Render a promo.
 *
 * @param int|array $promo   Promo post ID or spec array.
 * @param string    $context inline|sidebar|slidein|end|shortcode|block.
 */
function mm_render_promo( $promo, string $context = 'inline' ): string {
	$spec = is_array( $promo ) ? $promo : mm_promo_spec( (int) $promo );
	if ( ! $spec ) {
		return '';
	}
	$spec = wp_parse_args(
		$spec,
		array(
			'id'          => 0,
			'type'        => 'banner',
			'products'    => array(),
			'label'       => '',
			'icon'        => 'server',
			'heading'     => '',
			'text'        => '',
			'button_text' => '',
			'button_url'  => '',
			'image'       => 0,
			'device'      => 'all',
			'content'     => '',
		)
	);
	$label = $spec['label'] ? $spec['label'] : (string) mm_opt( 'promo_label' );
	$type  = $spec['type'];
	mm_promo_mark_seen( $spec );

	$attrs = sprintf(
		' data-mm-promo="%1$s" data-mm-context="%2$s"%3$s',
		esc_attr( $spec['id'] ? (string) $spec['id'] : 'auto-' . $type ),
		esc_attr( $context ),
		'all' !== $spec['device'] ? ' data-mm-device="' . esc_attr( $spec['device'] ) . '"' : ''
	);
	$label_html = '<span class="mm-promo__label">' . mm_icon( 'sparkles' ) . esc_html( $label ) . '</span>';

	ob_start();
	switch ( $type ) {
		case 'product':
			$product = mm_is_woo() && $spec['products'] ? wc_get_product( (int) reset( $spec['products'] ) ) : null;
			if ( ! $product || ! $product->is_visible() ) {
				return '';
			}
			$specs  = mm_product_mining_specs( $product );
			$chips  = array_filter( array( $specs['raw'][0], $specs['raw'][1], ( $specs['th'] > 0 && $specs['watts'] > 0 ) ? number_format_i18n( $specs['watts'] / $specs['th'], 1 ) . ' J/TH' : '' ) );
			$link   = $product->get_permalink();
			$simple = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock();
			$sale   = $product->is_on_sale();
			?>
			<aside class="mm-promo mm-promo--product"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-label="<?php echo esc_attr( $label ); ?>">
				<?php echo $label_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<div class="mm-promo__grid">
					<a class="mm-promo__media" href="<?php echo esc_url( $link ); ?>" data-mm-quickview="<?php echo esc_attr( (string) $product->get_id() ); ?>">
						<?php echo $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php if ( $sale ) : ?><span class="mm-badge mm-badge--sale"><?php esc_html_e( 'Sale', 'minemirth' ); ?></span><?php endif; ?>
					</a>
					<div class="mm-promo__body">
						<h4 class="mm-promo__title"><a href="<?php echo esc_url( $link ); ?>" data-mm-quickview="<?php echo esc_attr( (string) $product->get_id() ); ?>"><?php echo esc_html( $spec['heading'] ? $spec['heading'] : $product->get_name() ); ?></a></h4>
						<?php if ( $spec['text'] ) : ?>
							<p class="mm-promo__text"><?php echo esc_html( $spec['text'] ); ?></p>
						<?php endif; ?>
						<?php if ( $chips ) : ?>
							<ul class="mm-promo__specs"><?php foreach ( $chips as $chip ) : ?><li><?php echo esc_html( $chip ); ?></li><?php endforeach; ?></ul>
						<?php endif; ?>
						<div class="mm-promo__price">
							<?php echo wp_kses_post( $product->get_price_html() ); ?>
							<span class="mm-promo__stock<?php echo $product->is_in_stock() ? '' : ' is-out'; ?>"><?php echo esc_html( $product->is_in_stock() ? __( 'In stock', 'minemirth' ) : __( 'Out of stock', 'minemirth' ) ); ?></span>
						</div>
						<div class="mm-btn-row">
							<?php if ( $simple ) : ?>
								<button type="button" class="mm-btn mm-btn--primary mm-btn--sm" data-mm-add-to-cart="<?php echo esc_attr( (string) $product->get_id() ); ?>"><?php echo mm_icon( 'bag' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Add to cart', 'minemirth' ); ?></span></button>
							<?php else : ?>
								<a class="mm-btn mm-btn--primary mm-btn--sm" href="<?php echo esc_url( $link ); ?>"><span><?php esc_html_e( 'View options', 'minemirth' ); ?></span><?php echo mm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
							<?php endif; ?>
							<button type="button" class="mm-btn mm-btn--ghost mm-btn--sm" data-mm-quickview="<?php echo esc_attr( (string) $product->get_id() ); ?>"><?php echo mm_icon( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Quick view', 'minemirth' ); ?></span></button>
							<?php if ( $specs['th'] > 0 && $specs['watts'] > 0 && 'slidein' !== $context ) : ?>
								<a class="mm-btn mm-btn--ghost mm-btn--sm mm-hide-sm" href="<?php echo esc_url( add_query_arg( array( 'hashrate' => $specs['th'], 'power' => $specs['watts'], 'cost' => (float) $product->get_price(), 'miner' => rawurlencode( $product->get_name() ) ), mm_url( mm_opt( 'calc_btn_url' ) ) ) ); ?>"><?php echo mm_icon( 'calculator' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Estimate profit', 'minemirth' ); ?></span></a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</aside>
			<?php
			break;

		case 'products':
			$products = mm_is_woo() ? array_filter( array_map( 'wc_get_product', array_slice( $spec['products'], 0, 3 ) ) ) : array();
			$products = array_filter( $products, static fn( $p ) => $p && $p->is_visible() );
			if ( ! $products ) {
				return '';
			}
			?>
			<aside class="mm-promo mm-promo--products"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-label="<?php echo esc_attr( $label ); ?>">
				<?php echo $label_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php if ( $spec['heading'] ) : ?><h4 class="mm-promo__title"><?php echo esc_html( $spec['heading'] ); ?></h4><?php endif; ?>
				<?php if ( $spec['text'] ) : ?><p class="mm-promo__text"><?php echo esc_html( $spec['text'] ); ?></p><?php endif; ?>
				<div class="mm-promo__row">
					<?php foreach ( $products as $p ) : ?>
						<a class="mm-promo__mini" href="<?php echo esc_url( $p->get_permalink() ); ?>" data-mm-quickview="<?php echo esc_attr( (string) $p->get_id() ); ?>">
							<?php echo $p->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<strong><?php echo esc_html( $p->get_name() ); ?></strong>
							<span><?php echo wp_kses_post( $p->get_price_html() ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
				<?php if ( $spec['button_text'] ) : ?>
					<div class="mm-btn-row" style="margin-top:14px"><?php echo mm_button( $spec['button_text'], $spec['button_url'] ? $spec['button_url'] : '{shop}', 'ghost', array( 'size' => 'sm', 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<?php endif; ?>
			</aside>
			<?php
			break;

		case 'calculator':
			wp_enqueue_script( 'mm-calculator' );
			$presets = mm_calculator_presets();
			$first   = $presets[0] ?? array( '', 200, 3500, 0 );
			$sym     = mm_currency_symbol( mm_market_currency() );
			?>
			<aside class="mm-promo mm-promo--calculator"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-label="<?php echo esc_attr( $label ); ?>">
				<?php echo $label_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<h4 class="mm-promo__title"><?php echo esc_html( $spec['heading'] ? $spec['heading'] : (string) mm_opt( 'promo_calc_title' ) ); ?></h4>
				<?php if ( $spec['text'] ) : ?><p class="mm-promo__text"><?php echo esc_html( $spec['text'] ); ?></p><?php endif; ?>
				<div data-mm-calc data-currency="<?php echo esc_attr( $sym ); ?>">
					<div class="mm-promo__calc">
						<?php
						echo mm_calc_field( 'hashrate', __( 'Hashrate', 'minemirth' ), $first[1], 'TH/s' ); // phpcs:ignore WordPress.Security.EscapeOutput
						echo mm_calc_field( 'power', __( 'Power', 'minemirth' ), $first[2], 'W', array( 'step' => '1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						echo mm_calc_field( 'kwh', __( 'Electricity', 'minemirth' ), (float) mm_opt( 'calc_kwh' ), $sym . '/kWh', array( 'step' => '0.001' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
						<input type="hidden" data-k="pool" value="<?php echo esc_attr( (string) mm_opt( 'calc_pool_fee' ) ); ?>">
						<div class="mm-promo__result" aria-live="polite">
							<small><?php esc_html_e( 'Net profit / day', 'minemirth' ); ?></small>
							<strong data-out="profitDay">—</strong>
						</div>
					</div>
				</div>
				<div class="mm-promo__foot">
					<span><?php esc_html_e( 'Uses live BTC price & difficulty. Estimate only.', 'minemirth' ); ?></span>
					<?php echo mm_button( $spec['button_text'] ? $spec['button_text'] : (string) mm_opt( 'calc_btn_text' ), $spec['button_url'] ? $spec['button_url'] : mm_opt( 'calc_btn_url' ), 'ghost', array( 'size' => 'sm', 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</aside>
			<?php
			break;

		case 'newsletter':
			?>
			<aside class="mm-promo mm-promo--newsletter"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-label="<?php echo esc_attr( $label ); ?>">
				<?php echo $label_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<h4 class="mm-promo__title"><?php echo esc_html( $spec['heading'] ? $spec['heading'] : (string) mm_opt( 'news_title' ) ); ?></h4>
				<p class="mm-promo__text"><?php echo esc_html( $spec['text'] ? $spec['text'] : (string) mm_opt( 'news_subtitle' ) ); ?></p>
				<?php mm_newsletter_form( array( 'context' => 'promo-' . $context ) ); ?>
			</aside>
			<?php
			break;

		case 'custom':
			$content = $spec['content'];
			if ( function_exists( 'do_blocks' ) ) {
				$content = do_blocks( $content );
			}
			$content = do_shortcode( wpautop( shortcode_unautop( $content ) ) );
			?>
			<aside class="mm-promo mm-promo--custom"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-label="<?php echo esc_attr( $label ); ?>">
				<?php echo $label_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<div class="mm-promo__content"><?php echo wp_kses_post( $content ); ?></div>
			</aside>
			<?php
			break;

		default: // banner.
			$heading = $spec['heading'] ? $spec['heading'] : (string) mm_opt( 'promo_hosting_title' );
			?>
			<aside class="mm-promo mm-promo--banner mm-scheme-dark"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-label="<?php echo esc_attr( $label ); ?>">
				<div>
					<?php echo $label_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<h4 class="mm-promo__title"><?php echo esc_html( mm_strip_highlight( $heading ) ); ?></h4>
					<?php if ( $spec['text'] ) : ?><p class="mm-promo__text"><?php echo esc_html( $spec['text'] ); ?></p><?php endif; ?>
					<?php if ( $spec['button_text'] ) : ?>
						<div class="mm-btn-row"><?php echo mm_button( $spec['button_text'], $spec['button_url'], 'primary', array( 'size' => 'sm', 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php endif; ?>
				</div>
				<?php if ( $spec['image'] ) : ?>
					<?php echo mm_image( $spec['image'], 'medium', array( 'class' => 'mm-promo__img', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php else : ?>
					<span class="mm-promo__icon" aria-hidden="true"><?php echo mm_icon( $spec['icon'] ? $spec['icon'] : 'server' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<?php endif; ?>
			</aside>
			<?php
	}
	return (string) ob_get_clean();
}

/**
 * Remove *highlight* markers from plain-text contexts.
 */
function mm_strip_highlight( $text ): string {
	return str_replace( '*', '', (string) $text );
}

/**
 * First promo for a non-inline placement (or the fallback).
 */
function mm_promo_html_for( string $placement, ?WP_Post $post = null ): string {
	$post = $post ? $post : get_post();
	if ( ! $post || ! mm_promos_active( $post ) ) {
		return '';
	}
	$ids = mm_promos_for( $placement, $post );
	foreach ( $ids as $id ) {
		$spec = mm_promo_spec( $id );
		if ( ! $spec || mm_promo_seen( $spec ) ) {
			continue;
		}
		$html = mm_render_promo( $spec, $placement );
		if ( $html ) {
			return $html;
		}
	}
	$fallback = mm_promo_fallback( $placement );
	return $fallback ? mm_render_promo( $fallback, $placement ) : '';
}

/* ---------------------------------------------------------------------------
 * Inline injection
 * ------------------------------------------------------------------------- */

/**
 * Byte offsets just after each top-level </p> (not inside lists, quotes,
 * tables, columns, etc.), skipping paragraphs that introduce something
 * (ending with ":") and paragraphs directly followed by a heading.
 */
function mm_promo_paragraph_breaks( string $html ): array {
	$containers = 'div|blockquote|ul|ol|table|figure|section|aside|details|pre|form|dl|nav';
	if ( ! preg_match_all( '#<(/?)(' . $containers . ')\b[^>]*>|</p\s*>#i', $html, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER ) ) {
		return array();
	}
	$depth  = 0;
	$breaks = array();
	foreach ( $matches as $m ) {
		$token = $m[0][0];
		$pos   = $m[0][1];
		if ( 0 === stripos( $token, '</p' ) ) {
			if ( 0 === $depth ) {
				$end    = $pos + strlen( $token );
				$before = rtrim( wp_strip_all_tags( substr( $html, max( 0, $pos - 160 ), 160 ) ) );
				$after  = ltrim( substr( $html, $end, 40 ) );
				$breaks[] = array(
					'offset'     => $end,
					'introduces' => '' !== $before && ':' === substr( $before, -1 ),
					'heading'    => (bool) preg_match( '#^<h[1-6]\b#i', $after ),
				);
			}
			continue;
		}
		if ( '/' === $m[1][0] ) {
			$depth = max( 0, $depth - 1 );
		} elseif ( '/>' !== substr( $token, -2 ) ) {
			++$depth;
		}
	}
	return $breaks;
}

function mm_promo_inject( string $content ): string {
	static $done = array();

	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || doing_filter( 'get_the_excerpt' ) || doing_filter( 'wp_trim_excerpt' ) ) {
		return $content;
	}
	$post = get_post();
	if ( ! $post || get_queried_object_id() !== $post->ID || isset( $done[ $post->ID ] ) || ! mm_promos_active( $post ) ) {
		return $content;
	}
	if ( 'inline_off' === get_post_meta( $post->ID, '_mm_promo_mode', true ) ) {
		return $content;
	}
	if ( false !== strpos( $content, 'data-mm-promo=' ) ) {
		return $content; // Manual placement wins.
	}
	$done[ $post->ID ] = true;

	$breaks = mm_promo_paragraph_breaks( $content );
	$total  = count( $breaks );
	if ( $total < max( 2, (int) mm_opt( 'promo_min_paras' ) ) ) {
		return $content;
	}

	$first = max( 1, (int) mm_opt( 'promo_first' ) );
	$every = max( 0, (int) mm_opt( 'promo_every' ) );
	$max   = max( 1, (int) mm_opt( 'promo_max' ) );
	$ids   = mm_promos_for( 'inline', $post );

	// Pick insertion paragraph numbers (1-based), keeping ≥2 paragraphs after each.
	$targets = array();
	$n       = $first;
	while ( count( $targets ) < $max && $n <= $total - 2 ) {
		// Nudge forward past paragraphs that introduce lists or precede a heading.
		$k = $n;
		while ( $k <= $total - 2 && ( $breaks[ $k - 1 ]['introduces'] ) ) {
			++$k;
		}
		if ( $k > $total - 2 ) {
			break;
		}
		$targets[] = $k;
		if ( ! $every ) {
			break;
		}
		$n = $k + $every;
	}

	$inserts = array();
	foreach ( $targets as $slot => $paragraph ) {
		$html = '';
		if ( isset( $ids[ $slot ] ) ) {
			$html = mm_render_promo( $ids[ $slot ], 'inline' );
		}
		if ( ! $html ) {
			$fallback = mm_promo_fallback( 'inline', $slot );
			$html     = $fallback ? mm_render_promo( $fallback, 'inline' ) : '';
		}
		if ( ! $html ) {
			continue;
		}
		if ( mm_opt( 'promo_continue' ) ) {
			$anchor = 'mm-continue-' . ( $slot + 1 );
			$html  .= '<div class="mm-promo-continue"><a href="#' . esc_attr( $anchor ) . '" data-mm-continue>' . esc_html__( 'Continue reading', 'minemirth' ) . mm_icon( 'arrow-down' ) . '</a></div><span id="' . esc_attr( $anchor ) . '" class="mm-continue-anchor"></span>';
		}
		$inserts[ $breaks[ $paragraph - 1 ]['offset'] ] = $html;
	}

	krsort( $inserts );
	foreach ( $inserts as $offset => $html ) {
		$content = substr_replace( $content, "\n" . $html . "\n", $offset, 0 );
	}
	return $content;
}
add_filter( 'the_content', 'mm_promo_inject', 20 );

/* ---------------------------------------------------------------------------
 * Slide-in (footer)
 * ------------------------------------------------------------------------- */
function mm_promo_slidein(): void {
	if ( ! is_singular( 'post' ) || ! mm_opt( 'promo_slidein' ) ) {
		return;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return;
	}
	$html = mm_promo_html_for( 'slidein', $post );
	if ( ! $html ) {
		return;
	}
	?>
	<div class="mm-slidein" data-mm-slidein role="complementary" aria-label="<?php esc_attr_e( 'Recommended', 'minemirth' ); ?>">
		<button class="mm-icon-btn mm-slidein__close" type="button" data-mm-slidein-close aria-label="<?php esc_attr_e( 'Dismiss', 'minemirth' ); ?>"><?php echo mm_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php
}
add_action( 'wp_footer', 'mm_promo_slidein', 4 );

/* ---------------------------------------------------------------------------
 * Shortcodes
 * ------------------------------------------------------------------------- */
add_shortcode(
	'mm_promo',
	static function ( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts );
		return mm_render_promo( (int) $atts['id'], 'shortcode' );
	}
);

add_shortcode(
	'mm_product',
	static function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'   => 0,
				'ids'  => '',
				'text' => '',
			),
			$atts
		);
		$ids  = $atts['ids'] ? array_filter( array_map( 'absint', explode( ',', $atts['ids'] ) ) ) : array( absint( $atts['id'] ) );
		return mm_render_promo(
			array(
				'type'     => count( $ids ) > 1 ? 'products' : 'product',
				'products' => $ids,
				'text'     => sanitize_text_field( $atts['text'] ),
			),
			'shortcode'
		);
	}
);

add_shortcode(
	'mm_cta',
	static function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'title'  => (string) mm_opt( 'promo_hosting_title' ),
				'text'   => '',
				'button' => __( 'Learn more', 'minemirth' ),
				'url'    => '/miner-hosting/',
				'icon'   => 'server',
				'label'  => '',
			),
			$atts
		);
		return mm_render_promo(
			array(
				'type'        => 'banner',
				'heading'     => sanitize_text_field( $atts['title'] ),
				'text'        => sanitize_text_field( $atts['text'] ),
				'button_text' => sanitize_text_field( $atts['button'] ),
				'button_url'  => (string) $atts['url'],
				'icon'        => sanitize_key( $atts['icon'] ),
				'label'       => sanitize_text_field( $atts['label'] ),
			),
			'shortcode'
		);
	}
);

/* ---------------------------------------------------------------------------
 * Block: "Mine Mirth promo"
 * ------------------------------------------------------------------------- */
function mm_register_promo_block(): void {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	wp_register_script(
		'mm-promo-block',
		MM_URI . '/assets/js/promo-block.js',
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-data', 'wp-i18n' ),
		MM_VERSION,
		true
	);
	register_block_type(
		'minemirth/promo',
		array(
			'api_version'     => 3,
			'editor_script'   => 'mm-promo-block',
			'attributes'      => array(
				'mode'      => array( 'type' => 'string', 'default' => 'promo' ),
				'promoId'   => array( 'type' => 'number', 'default' => 0 ),
				'productId' => array( 'type' => 'number', 'default' => 0 ),
				'heading'   => array( 'type' => 'string', 'default' => '' ),
			),
			'render_callback' => 'mm_render_promo_block',
		)
	);
}
add_action( 'init', 'mm_register_promo_block' );

function mm_render_promo_block( array $attrs ): string {
	$mode = $attrs['mode'] ?? 'promo';
	switch ( $mode ) {
		case 'product':
			$spec = array( 'type' => 'product', 'products' => array( (int) ( $attrs['productId'] ?? 0 ) ) );
			break;
		case 'calculator':
			$spec = array( 'type' => 'calculator', 'heading' => (string) ( $attrs['heading'] ?? '' ) );
			break;
		case 'hosting':
			$spec = array(
				'type'        => 'banner',
				'heading'     => ! empty( $attrs['heading'] ) ? (string) $attrs['heading'] : (string) mm_opt( 'promo_hosting_title' ),
				'text'        => (string) mm_opt( 'promo_hosting_text' ),
				'button_text' => __( 'See hosting plans', 'minemirth' ),
				'button_url'  => '/miner-hosting/',
			);
			break;
		case 'newsletter':
			$spec = array( 'type' => 'newsletter', 'heading' => (string) ( $attrs['heading'] ?? '' ) );
			break;
		default:
			$spec = (int) ( $attrs['promoId'] ?? 0 );
	}
	$html = mm_render_promo( $spec, 'block' );
	if ( ! $html && ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return '<p class="mm-admin-note">' . esc_html__( 'Choose a promotion or product in the block settings.', 'minemirth' ) . '</p>';
	}
	return $html;
}
