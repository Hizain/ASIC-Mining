<?php
/**
 * Plans: the theme's Hosting Plans become Stripe subscription products. Each plan gets an image and a
 * "Stripe subscription" box (price, billing period, setup fee, quantity); saving it creates or updates
 * the product and price in Stripe straight away.
 *
 * Stripe prices can not be edited, so a new price is made when you change the amount or the billing
 * period. Existing subscribers stay on the price they signed up for; new subscribers get the new one.
 *
 * @package MineMirthSubscriptions
 */

defined( 'ABSPATH' ) || exit;

const MMS_PLAN_TYPE = 'mm_hosting_plan';

/**
 * Use the theme's Hosting Plans; register the same type when the theme is not active, so plans and
 * subscriptions keep working with any theme.
 */
add_action(
	'init',
	static function (): void {
		if ( ! post_type_exists( MMS_PLAN_TYPE ) ) {
			register_post_type(
				MMS_PLAN_TYPE,
				array(
					'labels'       => array(
						'name'          => __( 'Hosting Plans', 'mine-mirth-subscriptions' ),
						'singular_name' => __( 'Hosting Plan', 'mine-mirth-subscriptions' ),
						'add_new_item'  => __( 'Add hosting plan', 'mine-mirth-subscriptions' ),
						'edit_item'     => __( 'Edit hosting plan', 'mine-mirth-subscriptions' ),
					),
					'public'       => false,
					'show_ui'      => true,
					'show_in_menu' => true,
					'menu_icon'    => 'dashicons-database',
					'supports'     => array( 'title', 'editor', 'page-attributes' ),
				)
			);
		}
		add_post_type_support( MMS_PLAN_TYPE, array( 'thumbnail', 'excerpt' ) );
	},
	20
);

add_action(
	'after_setup_theme',
	static function (): void {
		add_theme_support( 'post-thumbnails' );
	},
	99
);

/**
 * Plan image label in the editor.
 */
add_filter(
	'admin_post_thumbnail_html',
	static function ( string $html, int $post_id ): string {
		if ( MMS_PLAN_TYPE !== get_post_type( $post_id ) ) {
			return $html;
		}
		return '<p class="description">' . esc_html__( 'Shown on the plan card, at checkout, in Stripe, in emails and on invoices. A landscape image of at least 1200 × 800 works best.', 'mine-mirth-subscriptions' ) . '</p>' . $html;
	},
	10,
	2
);
add_filter(
	'post_type_labels_' . MMS_PLAN_TYPE,
	static function ( $labels ) {
		$labels->featured_image        = __( 'Plan image', 'mine-mirth-subscriptions' );
		$labels->set_featured_image    = __( 'Set plan image', 'mine-mirth-subscriptions' );
		$labels->remove_featured_image = __( 'Remove plan image', 'mine-mirth-subscriptions' );
		$labels->use_featured_image    = __( 'Use as plan image', 'mine-mirth-subscriptions' );
		return $labels;
	}
);

/**
 * Everything about a plan, from the theme's fields and the Stripe box.
 */
function mms_plan( int $id ): ?array {
	$post = get_post( $id );
	if ( ! $post || MMS_PLAN_TYPE !== $post->post_type ) {
		return null;
	}
	$m    = static fn( string $k ) => get_post_meta( $id, $k, true );
	$mode = mms_mode();
	$img  = get_post_thumbnail_id( $id );
	return array(
		'id'             => $id,
		'title'          => get_the_title( $post ),
		'status'         => $post->post_status,
		'excerpt'        => has_excerpt( $post ) ? (string) $post->post_excerpt : wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 40 ),
		'content'        => (string) $post->post_content,
		'image_id'       => (int) $img,
		'image'          => $img ? (string) wp_get_attachment_image_url( $img, 'large' ) : '',
		'image_full'     => $img ? (string) wp_get_attachment_image_url( $img, 'full' ) : '',
		'enabled'        => (bool) $m( '_mms_enabled' ),
		'amount'         => (int) $m( '_mms_amount' ),
		'currency'       => (string) mms_setting( 'currency' ),
		'interval'       => in_array( $m( '_mms_interval' ), array( 'day', 'week', 'month', 'year' ), true ) ? (string) $m( '_mms_interval' ) : 'month',
		'interval_count' => max( 1, (int) $m( '_mms_interval_count' ) ),
		'setup_fee'      => (int) $m( '_mms_setup_fee' ),
		'qty'            => (bool) $m( '_mms_qty' ),
		'qty_max'        => max( 1, (int) ( $m( '_mms_qty_max' ) ?: 100 ) ),
		'qty_label'      => (string) ( $m( '_mms_qty_label' ) ?: __( 'miners', 'mine-mirth-subscriptions' ) ),
		'trial_days'     => (int) $m( '_mms_trial_days' ),
		'button'         => (string) ( $m( '_mms_button' ) ?: __( 'Subscribe', 'mine-mirth-subscriptions' ) ),
		'product'        => (string) $m( '_mms_product_' . $mode ),
		'price'          => (string) $m( '_mms_price_' . $mode ),
		'synced'         => (int) $m( '_mms_synced_' . $mode ),
		'error'          => (string) $m( '_mms_error_' . $mode ),
		// From the theme's Hosting Plans box.
		'region'         => (string) $m( '_mm_region' ),
		'availability'   => (string) ( $m( '_mm_availability' ) ?: 'inquiry' ),
		'rate'           => (string) $m( '_mm_rate' ),
		'billing'        => (string) $m( '_mm_billing' ),
		'minimum'        => (string) $m( '_mm_minimum' ),
		'cooling'        => (string) $m( '_mm_cooling' ),
		'setup'          => (string) $m( '_mm_setup' ),
		'term'           => (string) $m( '_mm_term' ),
		'featured'       => (bool) $m( '_mm_featured' ),
		'features'       => array_values( array_filter( array_map( 'trim', preg_split( "/\r\n|\r|\n/", (string) $m( '_mm_features' ) ) ) ) ),
		'fine_print'     => (string) $m( '_mm_fine_print' ),
		'cta_url'        => (string) $m( '_mm_cta_url' ),
	);
}

/**
 * Plans customers can subscribe to (published, priced and synced to Stripe).
 *
 * @return array[]
 */
function mms_plans( bool $subscribable_only = false ): array {
	$ids = get_posts(
		array(
			'post_type'      => MMS_PLAN_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'fields'         => 'ids',
		)
	);
	$out = array();
	foreach ( $ids as $id ) {
		$p = mms_plan( (int) $id );
		if ( $p && ( ! $subscribable_only || mms_plan_ready( $p ) ) ) {
			$out[] = $p;
		}
	}
	return $out;
}

function mms_plan_ready( array $p ): bool {
	return $p['enabled'] && $p['amount'] > 0 && '' !== $p['price'] && 'publish' === $p['status'] && 'unavailable' !== $p['availability'];
}

/**
 * The plan a Stripe price or product belongs to.
 */
function mms_plan_by_stripe( string $price_id, string $product_id = '' ): int {
	global $wpdb;
	foreach ( array( 'test', 'live' ) as $mode ) {
		foreach ( array( array( '_mms_price_' . $mode, $price_id ), array( '_mms_product_' . $mode, $product_id ) ) as $pair ) {
			if ( '' === $pair[1] ) {
				continue;
			}
			$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1", $pair[0], $pair[1] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( $id ) {
				return $id;
			}
		}
	}
	// Earlier prices of a plan.
	if ( '' !== $price_id ) {
		$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_mms_old_prices' AND meta_value LIKE %s LIMIT 1", '%' . $wpdb->esc_like( $price_id ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $id ) {
			return $id;
		}
	}
	return 0;
}

/**
 * Plan image for a subscription: the plan's own image (by WordPress ID or Stripe price/product).
 */
function mms_plan_image( int $plan_id, string $size = 'medium' ): string {
	$img = $plan_id ? get_post_thumbnail_id( $plan_id ) : 0;
	return $img ? (string) wp_get_attachment_image_url( $img, $size ) : '';
}

/* ------------------------------------------------------------------ Editor box */

add_action(
	'add_meta_boxes_' . MMS_PLAN_TYPE,
	static function (): void {
		add_meta_box( 'mms-plan', __( 'Stripe subscription', 'mine-mirth-subscriptions' ), 'mms_plan_box', MMS_PLAN_TYPE, 'normal', 'high' );
	}
);

function mms_plan_box( WP_Post $post ): void {
	$p   = mms_plan( (int) $post->ID );
	$cur = strtoupper( (string) mms_setting( 'currency' ) );
	$amt = static fn( int $minor ) => $minor ? rtrim( rtrim( number_format( mms_from_minor( $minor, $p['currency'] ), 2, '.', '' ), '0' ), '.' ) : '';
	wp_nonce_field( 'mms_plan_' . $post->ID, 'mms_plan_nonce' );
	?>
	<div class="mms-planbox">
		<label class="mms-check mms-check--big"><input type="checkbox" name="mms_plan[enabled]" value="1" <?php checked( $p['enabled'] ); ?>> <?php esc_html_e( 'Customers can subscribe to this plan online (Stripe)', 'mine-mirth-subscriptions' ); ?></label>
		<div class="mms-planbox__grid">
			<label class="mms-f"><span><?php esc_html_e( 'Price', 'mine-mirth-subscriptions' ); ?></span><span class="mms-money"><b><?php echo esc_html( $cur ); ?></b><input type="text" inputmode="decimal" name="mms_plan[amount]" value="<?php echo esc_attr( $amt( $p['amount'] ) ); ?>" placeholder="199.00"></span></label>
			<label class="mms-f"><span><?php esc_html_e( 'Billed every', 'mine-mirth-subscriptions' ); ?></span>
				<span class="mms-inline">
					<input type="number" min="1" max="12" name="mms_plan[interval_count]" value="<?php echo (int) $p['interval_count']; ?>" class="small-text">
					<select name="mms_plan[interval]">
						<?php foreach ( array( 'month' => __( 'month(s)', 'mine-mirth-subscriptions' ), 'year' => __( 'year(s)', 'mine-mirth-subscriptions' ), 'week' => __( 'week(s)', 'mine-mirth-subscriptions' ), 'day' => __( 'day(s)', 'mine-mirth-subscriptions' ) ) as $k => $label ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $p['interval'], $k ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
			</label>
			<label class="mms-f"><span><?php esc_html_e( 'One-time setup fee', 'mine-mirth-subscriptions' ); ?> <i><?php esc_html_e( 'optional, on the first bill', 'mine-mirth-subscriptions' ); ?></i></span><span class="mms-money"><b><?php echo esc_html( $cur ); ?></b><input type="text" inputmode="decimal" name="mms_plan[setup_fee]" value="<?php echo esc_attr( $amt( $p['setup_fee'] ) ); ?>" placeholder="0.00"></span></label>
			<label class="mms-f"><span><?php esc_html_e( 'Free trial', 'mine-mirth-subscriptions' ); ?> <i><?php esc_html_e( 'optional', 'mine-mirth-subscriptions' ); ?></i></span><span class="mms-inline"><input type="number" min="0" max="365" name="mms_plan[trial_days]" value="<?php echo (int) $p['trial_days']; ?>" class="small-text"> <?php esc_html_e( 'days', 'mine-mirth-subscriptions' ); ?></span></label>
			<div class="mms-f">
				<label class="mms-check"><input type="checkbox" name="mms_plan[qty]" value="1" <?php checked( $p['qty'] ); ?>> <?php esc_html_e( 'Price is per unit; customers choose how many', 'mine-mirth-subscriptions' ); ?></label>
				<span class="mms-inline"><?php esc_html_e( 'Up to', 'mine-mirth-subscriptions' ); ?> <input type="number" min="1" max="999" name="mms_plan[qty_max]" value="<?php echo (int) $p['qty_max']; ?>" class="small-text"> <input type="text" name="mms_plan[qty_label]" value="<?php echo esc_attr( $p['qty_label'] ); ?>" class="regular-text" style="width:120px"></span>
			</div>
			<label class="mms-f"><span><?php esc_html_e( 'Button text', 'mine-mirth-subscriptions' ); ?></span><input type="text" name="mms_plan[button]" value="<?php echo esc_attr( $p['button'] ); ?>"></label>
		</div>
		<div class="mms-planbox__status">
			<?php if ( ! mms_connected() ) : ?>
				<p class="mms-warn"><?php echo wp_kses_post( sprintf( /* translators: %s: settings link */ __( 'Stripe is not connected yet. <a href="%s">Connect Stripe</a>, then update this plan to create it in Stripe.', 'mine-mirth-subscriptions' ), esc_url( admin_url( 'admin.php?page=mms-settings' ) ) ) ); ?></p>
			<?php elseif ( $p['error'] ) : ?>
				<p class="mms-err"><strong><?php esc_html_e( 'Stripe sync failed:', 'mine-mirth-subscriptions' ); ?></strong> <?php echo esc_html( $p['error'] ); ?></p>
			<?php elseif ( $p['product'] ) : ?>
				<p class="mms-ok">
					<?php echo esc_html( sprintf( /* translators: %s: date */ __( 'In Stripe, synced %s.', 'mine-mirth-subscriptions' ), mms_date( $p['synced'], true ) ) ); ?>
					<a href="<?php echo esc_url( mms_dashboard_url( 'products', $p['product'] ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open in Stripe', 'mine-mirth-subscriptions' ); ?> ↗</a>
				</p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Tick the box, set a price and update the plan: it is created in Stripe straight away.', 'mine-mirth-subscriptions' ); ?></p>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( 'Changing the price or billing period creates a new price in Stripe for new subscribers. Current subscribers keep the price they signed up for.', 'mine-mirth-subscriptions' ); ?></p>
		</div>
	</div>
	<?php
}

/**
 * "1,234.50" to 123450 (cents).
 */
function mms_parse_amount( $value, string $currency ): int {
	$v = preg_replace( '/[^0-9.]/', '', str_replace( ',', '', (string) $value ) );
	return is_numeric( $v ) ? max( 0, mms_to_minor( (float) $v, $currency ) ) : 0;
}

add_action(
	'save_post_' . MMS_PLAN_TYPE,
	static function ( int $post_id ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( isset( $_POST['mms_plan_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mms_plan_nonce'] ) ), 'mms_plan_' . $post_id ) && current_user_can( 'edit_post', $post_id ) ) {
			$in  = wp_unslash( $_POST['mms_plan'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cleaned below.
			$in  = is_array( $in ) ? $in : array();
			$cur = (string) mms_setting( 'currency' );
			update_post_meta( $post_id, '_mms_enabled', empty( $in['enabled'] ) ? 0 : 1 );
			update_post_meta( $post_id, '_mms_amount', mms_parse_amount( $in['amount'] ?? '', $cur ) );
			update_post_meta( $post_id, '_mms_interval', in_array( $in['interval'] ?? '', array( 'day', 'week', 'month', 'year' ), true ) ? $in['interval'] : 'month' );
			update_post_meta( $post_id, '_mms_interval_count', max( 1, min( 12, absint( $in['interval_count'] ?? 1 ) ) ) );
			update_post_meta( $post_id, '_mms_setup_fee', mms_parse_amount( $in['setup_fee'] ?? '', $cur ) );
			update_post_meta( $post_id, '_mms_trial_days', min( 365, absint( $in['trial_days'] ?? 0 ) ) );
			update_post_meta( $post_id, '_mms_qty', empty( $in['qty'] ) ? 0 : 1 );
			update_post_meta( $post_id, '_mms_qty_max', max( 1, min( 999, absint( $in['qty_max'] ?? 100 ) ) ) );
			update_post_meta( $post_id, '_mms_qty_label', sanitize_text_field( (string) ( $in['qty_label'] ?? '' ) ) );
			update_post_meta( $post_id, '_mms_button', sanitize_text_field( (string) ( $in['button'] ?? '' ) ) );
		}
		mms_sync_plan( $post_id );
	},
	30
);

// The plan image is saved after the post itself in the block editor; sync again when it changes.
add_action(
	'updated_post_meta',
	static function ( $meta_id, $post_id, $key ): void {
		if ( '_thumbnail_id' === $key && MMS_PLAN_TYPE === get_post_type( (int) $post_id ) ) {
			mms_sync_plan( (int) $post_id );
		}
	},
	10,
	3
);
add_action(
	'added_post_meta',
	static function ( $meta_id, $post_id, $key ): void {
		if ( '_thumbnail_id' === $key && MMS_PLAN_TYPE === get_post_type( (int) $post_id ) ) {
			mms_sync_plan( (int) $post_id );
		}
	},
	10,
	3
);

// A plan moved to the trash or deleted is archived in Stripe (Stripe keeps products that have prices).
add_action(
	'transition_post_status',
	static function ( $new, $old, $post ): void {
		if ( $post instanceof WP_Post && MMS_PLAN_TYPE === $post->post_type && 'publish' === $old && 'publish' !== $new ) {
			mms_sync_plan( (int) $post->ID );
		}
	},
	10,
	3
);
add_action(
	'before_delete_post',
	static function ( $post_id ): void {
		if ( MMS_PLAN_TYPE === get_post_type( (int) $post_id ) ) {
			$product = (string) get_post_meta( (int) $post_id, '_mms_product_' . mms_mode(), true );
			if ( '' !== $product && mms_connected() ) {
				mms_stripe( 'POST', '/v1/products/' . rawurlencode( $product ), array( 'active' => false ) );
			}
		}
	}
);

/**
 * Create or update the plan's product and price in Stripe.
 *
 * @return true|WP_Error
 */
function mms_sync_plan( int $post_id ) {
	static $running = array();
	if ( isset( $running[ $post_id ] ) || ! mms_connected() ) {
		return true;
	}
	$running[ $post_id ] = true;
	$p    = mms_plan( $post_id );
	$mode = mms_mode();
	if ( ! $p || ( ! $p['enabled'] && '' === $p['product'] ) ) {
		unset( $running[ $post_id ] );
		return true;
	}
	$active  = $p['enabled'] && 'publish' === $p['status'];
	$images  = array_values( array_filter( array( $p['image_full'] ) ) );
	$product = array(
		'name'        => '' !== $p['title'] ? $p['title'] : __( 'Hosting plan', 'mine-mirth-subscriptions' ),
		'description' => '' !== trim( $p['excerpt'] ) ? mb_substr( trim( $p['excerpt'] ), 0, 1000 ) : null,
		'images'      => $images,
		'active'      => $active,
		'metadata'    => array(
			'wp_plan_id' => (string) $post_id,
			'site'       => home_url( '/' ),
			'source'     => 'mine-mirth-subscriptions',
		),
	);
	$res = null;
	if ( '' !== $p['product'] ) {
		$res = mms_stripe( 'POST', '/v1/products/' . rawurlencode( $p['product'] ), $product );
		if ( is_wp_error( $res ) && 404 === (int) ( $res->get_error_data()['status'] ?? 0 ) ) {
			$res          = null; // Deleted in Stripe: create it again.
			$p['product'] = '';
			$p['price']   = '';
		}
	}
	if ( null === $res ) {
		$res = mms_stripe( 'POST', '/v1/products', $product, 'product-' . $mode . '-' . $post_id . '-' . md5( wp_json_encode( $product ) ) . '-' . floor( time() / 300 ) );
	}
	if ( is_wp_error( $res ) ) {
		return mms_plan_error( $post_id, $res );
	}
	$product_id = (string) $res['id'];
	update_post_meta( $post_id, '_mms_product_' . $mode, $product_id );

	// A new price when there is none yet or the amount, currency or period changed.
	if ( $p['enabled'] && $p['amount'] > 0 ) {
		$sig = implode( '|', array( $product_id, $p['amount'], $p['currency'], $p['interval'], $p['interval_count'] ) );
		if ( '' === $p['price'] || get_post_meta( $post_id, '_mms_price_sig_' . $mode, true ) !== $sig ) {
			$price = mms_stripe(
				'POST',
				'/v1/prices',
				array(
					'product'     => $product_id,
					'unit_amount' => $p['amount'],
					'currency'    => $p['currency'],
					'recurring'   => array(
						'interval'       => $p['interval'],
						'interval_count' => $p['interval_count'],
					),
					'nickname'    => $p['title'] . ' · ' . mms_money( $p['amount'], $p['currency'] ) . ' ' . mms_interval_label( $p['interval'], $p['interval_count'] ),
					'metadata'    => array( 'wp_plan_id' => (string) $post_id ),
				),
				'price-' . $mode . '-' . md5( $sig ) . '-' . floor( time() / 300 )
			);
			if ( is_wp_error( $price ) ) {
				return mms_plan_error( $post_id, $price );
			}
			$old = $p['price'];
			update_post_meta( $post_id, '_mms_price_' . $mode, (string) $price['id'] );
			update_post_meta( $post_id, '_mms_price_sig_' . $mode, $sig );
			mms_stripe( 'POST', '/v1/products/' . rawurlencode( $product_id ), array( 'default_price' => (string) $price['id'] ) );
			if ( '' !== $old && $old !== $price['id'] ) {
				mms_stripe( 'POST', '/v1/prices/' . rawurlencode( $old ), array( 'active' => false ) );
				$olds   = array_filter( explode( ',', (string) get_post_meta( $post_id, '_mms_old_prices', true ) ) );
				$olds[] = $old;
				update_post_meta( $post_id, '_mms_old_prices', implode( ',', array_unique( $olds ) ) );
			}
			mms_log( 'plan', sprintf( 'Plan "%s": price %s %s created in Stripe.', $p['title'], mms_money( $p['amount'], $p['currency'] ), mms_interval_label( $p['interval'], $p['interval_count'] ) ), (string) $price['id'] );
		}
	}
	update_post_meta( $post_id, '_mms_synced_' . $mode, time() );
	delete_post_meta( $post_id, '_mms_error_' . $mode );
	unset( $running[ $post_id ] );
	return true;
}

function mms_plan_error( int $post_id, WP_Error $e ): WP_Error {
	update_post_meta( $post_id, '_mms_error_' . mms_mode(), $e->get_error_message() );
	mms_log( 'plan', sprintf( 'Plan %d could not be synced: %s', $post_id, $e->get_error_message() ), '', 'error' );
	return $e;
}

/* ------------------------------------------------------------------ Plans list */

add_filter(
	'manage_' . MMS_PLAN_TYPE . '_posts_columns',
	static function ( array $cols ): array {
		$new = array();
		foreach ( $cols as $k => $label ) {
			if ( 'title' === $k ) {
				$new['mms_image'] = '<span class="screen-reader-text">' . esc_html__( 'Image', 'mine-mirth-subscriptions' ) . '</span>';
			}
			$new[ $k ] = $label;
			if ( 'title' === $k ) {
				$new['mms_stripe'] = __( 'Subscription', 'mine-mirth-subscriptions' );
			}
		}
		return $new;
	}
);
add_action(
	'manage_' . MMS_PLAN_TYPE . '_posts_custom_column',
	static function ( string $col, int $post_id ): void {
		$p = mms_plan( $post_id );
		if ( ! $p ) {
			return;
		}
		if ( 'mms_image' === $col ) {
			$url = mms_plan_image( $post_id, 'thumbnail' );
			echo $url ? '<img class="mms-thumb" src="' . esc_url( $url ) . '" alt="">' : '<span class="mms-thumb mms-thumb--empty"></span>';
		} elseif ( 'mms_stripe' === $col ) {
			if ( ! $p['enabled'] ) {
				echo '<span class="mms-muted">' . esc_html__( 'Not sold online', 'mine-mirth-subscriptions' ) . '</span>';
				return;
			}
			echo '<strong>' . esc_html( mms_money( $p['amount'], $p['currency'] ) ) . '</strong> <span class="mms-muted">' . esc_html( mms_interval_label( $p['interval'], $p['interval_count'] ) ) . '</span><br>';
			if ( $p['error'] ) {
				echo '<span class="mms-err">' . esc_html__( 'Sync failed', 'mine-mirth-subscriptions' ) . '</span>';
			} elseif ( $p['price'] ) {
				echo '<span class="mms-ok">' . esc_html__( 'In Stripe', 'mine-mirth-subscriptions' ) . '</span>';
			} else {
				echo '<span class="mms-muted">' . esc_html__( 'Not synced yet', 'mine-mirth-subscriptions' ) . '</span>';
			}
		}
	},
	10,
	2
);

/**
 * Tell the editor when the last save could not reach Stripe.
 */
add_action(
	'admin_notices',
	static function (): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || MMS_PLAN_TYPE !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}
		$id = absint( $_GET['post'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$p  = $id ? mms_plan( $id ) : null;
		if ( $p && $p['enabled'] && $p['error'] ) {
			echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'This plan is not in Stripe yet.', 'mine-mirth-subscriptions' ) . '</strong> ' . esc_html( $p['error'] ) . '</p></div>';
		}
	}
);
