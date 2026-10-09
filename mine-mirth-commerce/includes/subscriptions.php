<?php
/**
 * Subscriptions without WooCommerce Subscriptions.
 *
 * Any simple product can be a subscription (Product data › General): price
 * every N days/weeks/months/years, optional length, sign-up fee and free trial.
 * The first order starts the subscription. Before each renewal date a renewal
 * order is created and the customer gets an invoice email with a payment link
 * (any gateway works, crypto included). Customers see and cancel their
 * subscriptions under My account › Subscriptions.
 *
 * Cards are not charged automatically: that needs a gateway with saved-card
 * support, which is what the paid extension adds.
 *
 * @package MineMirthCommerce
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Product settings
 * ------------------------------------------------------------------------- */

function mmc_sub_periods(): array {
	return array(
		'day'   => __( 'day', 'mine-mirth-commerce' ),
		'week'  => __( 'week', 'mine-mirth-commerce' ),
		'month' => __( 'month', 'mine-mirth-commerce' ),
		'year'  => __( 'year', 'mine-mirth-commerce' ),
	);
}

/**
 * Subscription terms of a product, or null.
 *
 * @return array{interval:int,period:string,length:int,fee:float,trial:int}|null
 */
function mmc_sub_terms( $product ): ?array {
	$product = $product instanceof WC_Product ? $product : wc_get_product( $product );
	if ( ! $product ) {
		return null;
	}
	$id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
	if ( 'yes' !== get_post_meta( $id, '_mmc_sub', true ) ) {
		return null;
	}
	$period = (string) get_post_meta( $id, '_mmc_sub_period', true );
	return array(
		'interval' => max( 1, (int) get_post_meta( $id, '_mmc_sub_interval', true ) ),
		'period'   => isset( mmc_sub_periods()[ $period ] ) ? $period : 'month',
		'length'   => max( 0, (int) get_post_meta( $id, '_mmc_sub_length', true ) ),
		'fee'      => max( 0, (float) get_post_meta( $id, '_mmc_sub_fee', true ) ),
		'trial'    => max( 0, (int) get_post_meta( $id, '_mmc_sub_trial', true ) ),
	);
}

add_action(
	'woocommerce_product_options_general_product_data',
	static function () {
		global $post;
		echo '<div class="options_group show_if_simple">';
		woocommerce_wp_checkbox( array( 'id' => '_mmc_sub', 'label' => __( 'Subscription', 'mine-mirth-commerce' ), 'description' => __( 'Charge this price on a schedule', 'mine-mirth-commerce' ) ) );
		echo '<div class="mmc-sub-fields">';
		woocommerce_wp_text_input( array( 'id' => '_mmc_sub_interval', 'label' => __( 'Bill every', 'mine-mirth-commerce' ), 'type' => 'number', 'custom_attributes' => array( 'min' => '1', 'step' => '1' ), 'value' => get_post_meta( $post->ID, '_mmc_sub_interval', true ) ?: '1' ) );
		woocommerce_wp_select( array( 'id' => '_mmc_sub_period', 'label' => __( 'Period', 'mine-mirth-commerce' ), 'options' => mmc_sub_periods(), 'value' => get_post_meta( $post->ID, '_mmc_sub_period', true ) ?: 'month' ) );
		woocommerce_wp_text_input( array( 'id' => '_mmc_sub_length', 'label' => __( 'Number of payments', 'mine-mirth-commerce' ), 'type' => 'number', 'description' => __( '0 = until cancelled', 'mine-mirth-commerce' ), 'desc_tip' => true, 'custom_attributes' => array( 'min' => '0', 'step' => '1' ) ) );
		woocommerce_wp_text_input( array( 'id' => '_mmc_sub_fee', 'label' => __( 'Sign-up fee', 'mine-mirth-commerce' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' ) );
		woocommerce_wp_text_input( array( 'id' => '_mmc_sub_trial', 'label' => __( 'Free trial (days)', 'mine-mirth-commerce' ), 'type' => 'number', 'custom_attributes' => array( 'min' => '0', 'step' => '1' ) ) );
		echo '</div></div>';
		?>
		<script>
		jQuery(function ($) {
			var t = function () { $('.mmc-sub-fields').toggle($('#_mmc_sub').is(':checked')); };
			$('#_mmc_sub').on('change', t); t();
		});
		</script>
		<?php
	}
);

add_action(
	'woocommerce_admin_process_product_object',
	static function ( WC_Product $product ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the product form nonce.
		$product->update_meta_data( '_mmc_sub', isset( $_POST['_mmc_sub'] ) ? 'yes' : 'no' );
		$product->update_meta_data( '_mmc_sub_interval', max( 1, absint( wp_unslash( $_POST['_mmc_sub_interval'] ?? 1 ) ) ) );
		$period = sanitize_key( wp_unslash( $_POST['_mmc_sub_period'] ?? 'month' ) );
		$product->update_meta_data( '_mmc_sub_period', isset( mmc_sub_periods()[ $period ] ) ? $period : 'month' );
		$product->update_meta_data( '_mmc_sub_length', absint( wp_unslash( $_POST['_mmc_sub_length'] ?? 0 ) ) );
		$product->update_meta_data( '_mmc_sub_fee', wc_format_decimal( wp_unslash( $_POST['_mmc_sub_fee'] ?? '' ) ) );
		$product->update_meta_data( '_mmc_sub_trial', absint( wp_unslash( $_POST['_mmc_sub_trial'] ?? 0 ) ) );
		// phpcs:enable
	}
);

/**
 * "every 3 months for 12 payments, $50 sign-up fee, 14-day free trial".
 */
function mmc_sub_terms_text( array $t ): string {
	$p    = mmc_sub_periods()[ $t['period'] ];
	/* translators: 1: number 2: period */
	$text = 1 === $t['interval'] ? sprintf( __( '/ %s', 'mine-mirth-commerce' ), $p ) : sprintf( __( 'every %1$d %2$ss', 'mine-mirth-commerce' ), $t['interval'], $p );
	if ( $t['length'] > 0 ) {
		/* translators: %d: payments */
		$text .= ' ' . sprintf( _n( 'for %d payment', 'for %d payments', $t['length'], 'mine-mirth-commerce' ), $t['length'] );
	}
	$extra = array();
	if ( $t['fee'] > 0 ) {
		/* translators: %s: price */
		$extra[] = sprintf( __( '%s sign-up fee', 'mine-mirth-commerce' ), wp_strip_all_tags( wc_price( $t['fee'] ) ) );
	}
	if ( $t['trial'] > 0 ) {
		/* translators: %d: days */
		$extra[] = sprintf( __( '%d-day free trial', 'mine-mirth-commerce' ), $t['trial'] );
	}
	return $text . ( $extra ? ' · ' . implode( ' · ', $extra ) : '' );
}

add_filter(
	'woocommerce_get_price_html',
	static function ( $html, $product ) {
		$t = mmc_sub_terms( $product );
		return $t ? $html . ' <span class="mmc-sub-terms">' . esc_html( mmc_sub_terms_text( $t ) ) . '</span>' : $html;
	},
	20,
	2
);

add_filter(
	'woocommerce_product_single_add_to_cart_text',
	static fn( $text, $product ) => mmc_sub_terms( $product ) ? __( 'Subscribe', 'mine-mirth-commerce' ) : $text,
	20,
	2
);
add_filter(
	'woocommerce_product_add_to_cart_text',
	static fn( $text, $product ) => mmc_sub_terms( $product ) && $product->is_purchasable() && $product->is_in_stock() ? __( 'Subscribe', 'mine-mirth-commerce' ) : $text,
	20,
	2
);

/* ---------------------------------------------------------------------------
 * Cart and checkout
 * ------------------------------------------------------------------------- */

// Trials: the first order charges only the sign-up fee.
add_action(
	'woocommerce_before_calculate_totals',
	static function ( WC_Cart $cart ) {
		foreach ( $cart->get_cart() as $item ) {
			$t = mmc_sub_terms( $item['data'] );
			if ( $t && $t['trial'] > 0 && empty( $item['mmc_renewal'] ) ) {
				$item['data']->set_price( 0 );
			}
		}
	},
	20
);

add_action(
	'woocommerce_cart_calculate_fees',
	static function ( WC_Cart $cart ) {
		foreach ( $cart->get_cart() as $item ) {
			$t = mmc_sub_terms( $item['data'] );
			if ( $t && $t['fee'] > 0 ) {
				/* translators: %s: product name */
				$cart->add_fee( sprintf( __( 'Sign-up fee: %s', 'mine-mirth-commerce' ), $item['data']->get_name() ), $t['fee'] * (int) $item['quantity'], true );
			}
		}
	}
);

add_filter(
	'woocommerce_get_item_data',
	static function ( $data, $item ) {
		$t = mmc_sub_terms( $item['data'] );
		if ( $t ) {
			$price  = (float) wc_get_product( $item['product_id'] )->get_price();
			$data[] = array(
				'key'   => __( 'Subscription', 'mine-mirth-commerce' ),
				'value' => wp_strip_all_tags( wc_price( $price ) ) . ' ' . mmc_sub_terms_text( $t ),
			);
		}
		return $data;
	},
	10,
	2
);

// Guests cannot manage a subscription, so subscriptions need an account.
add_filter(
	'woocommerce_checkout_registration_required',
	static function ( $required ) {
		if ( WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				if ( mmc_sub_terms( $item['data'] ) ) {
					return true;
				}
			}
		}
		return $required;
	}
);

/* ---------------------------------------------------------------------------
 * Subscription records
 * ------------------------------------------------------------------------- */

add_action(
	'init',
	static function () {
		register_post_type(
			'mmc_subscription',
			array(
				'labels'          => array(
					'name'          => __( 'Subscriptions', 'mine-mirth-commerce' ),
					'singular_name' => __( 'Subscription', 'mine-mirth-commerce' ),
					'menu_name'     => __( 'Subscriptions', 'mine-mirth-commerce' ),
					'edit_item'     => __( 'Subscription', 'mine-mirth-commerce' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'woocommerce',
				'supports'        => array( 'title' ),
				'capability_type' => 'shop_order',
				'map_meta_cap'    => true,
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			)
		);
		add_rewrite_endpoint( 'subscriptions', EP_ROOT | EP_PAGES );
		if ( get_option( 'mmc_flush_rewrite' ) ) {
			flush_rewrite_rules();
			delete_option( 'mmc_flush_rewrite' );
		}
	}
);

function mmc_sub_statuses(): array {
	return array(
		'active'    => __( 'Active', 'mine-mirth-commerce' ),
		'trial'     => __( 'In trial', 'mine-mirth-commerce' ),
		'on-hold'   => __( 'On hold (renewal unpaid)', 'mine-mirth-commerce' ),
		'cancelled' => __( 'Cancelled', 'mine-mirth-commerce' ),
		'expired'   => __( 'Finished', 'mine-mirth-commerce' ),
	);
}

/**
 * Add one period to a timestamp.
 */
function mmc_sub_add_period( int $from, array $t ): int {
	$next = strtotime( '+' . $t['interval'] . ' ' . $t['period'], $from );
	return $next ? $next : $from + MONTH_IN_SECONDS;
}

/**
 * Create subscriptions for a paid order (not for renewal orders).
 */
function mmc_sub_create_from_order( $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order || $order->get_meta( '_mmc_renewal_of' ) ) {
		return;
	}
	foreach ( $order->get_items() as $item_id => $item ) {
		if ( ! $item instanceof WC_Order_Item_Product || $item->get_meta( '_mmc_sub_id' ) ) {
			continue;
		}
		$product = $item->get_product();
		$t       = $product ? mmc_sub_terms( $product ) : null;
		if ( ! $t ) {
			continue;
		}
		$now   = time();
		$next  = $t['trial'] > 0 ? $now + $t['trial'] * DAY_IN_SECONDS : mmc_sub_add_period( $now, $t );
		$price = (float) $product->get_price();
		$sub   = wp_insert_post(
			array(
				'post_type'   => 'mmc_subscription',
				'post_status' => 'publish',
				/* translators: 1: product 2: customer */
				'post_title'  => sprintf( __( '%1$s — %2$s', 'mine-mirth-commerce' ), $product->get_name(), $order->get_formatted_billing_full_name() ),
				'post_author' => $order->get_customer_id(),
			)
		);
		if ( ! $sub ) {
			continue;
		}
		$meta = array(
			'_status'       => $t['trial'] > 0 ? 'trial' : 'active',
			'_order_id'     => $order->get_id(),
			'_customer_id'  => $order->get_customer_id(),
			'_product_id'   => $product->get_id(),
			'_qty'          => $item->get_quantity(),
			'_price'        => $price,
			'_terms'        => $t,
			'_payments'     => $t['trial'] > 0 ? 0 : 1,
			'_next_payment' => $next,
			'_method'       => $order->get_payment_method(),
		);
		foreach ( $meta as $k => $v ) {
			update_post_meta( $sub, $k, $v );
		}
		wc_update_order_item_meta( $item_id, '_mmc_sub_id', $sub );
		/* translators: %d: subscription id */
		$order->add_order_note( sprintf( __( 'Subscription #%d started.', 'mine-mirth-commerce' ), $sub ) );
	}
}
add_action( 'woocommerce_order_status_processing', 'mmc_sub_create_from_order' );
add_action( 'woocommerce_order_status_completed', 'mmc_sub_create_from_order' );

// A renewal order was paid: count it and move the next date on.
function mmc_sub_renewal_paid( $order_id ): void {
	$order  = wc_get_order( $order_id );
	$sub_id = $order ? (int) $order->get_meta( '_mmc_renewal_of' ) : 0;
	if ( ! $sub_id || $order->get_meta( '_mmc_counted' ) ) {
		return;
	}
	$t        = (array) get_post_meta( $sub_id, '_terms', true );
	$payments = (int) get_post_meta( $sub_id, '_payments', true ) + 1;
	update_post_meta( $sub_id, '_payments', $payments );
	delete_post_meta( $sub_id, '_pending_order' );
	$status = (string) get_post_meta( $sub_id, '_status', true );
	if ( 'cancelled' !== $status ) {
		$done = ! empty( $t['length'] ) && $payments >= (int) $t['length'];
		update_post_meta( $sub_id, '_status', $done ? 'expired' : 'active' );
	}
	$order->update_meta_data( '_mmc_counted', 1 );
	$order->save();
}
add_action( 'woocommerce_order_status_processing', 'mmc_sub_renewal_paid' );
add_action( 'woocommerce_order_status_completed', 'mmc_sub_renewal_paid' );

/**
 * Create the renewal order for a subscription and email the invoice.
 */
function mmc_sub_create_renewal( int $sub_id ): int {
	$parent  = wc_get_order( (int) get_post_meta( $sub_id, '_order_id', true ) );
	$product = wc_get_product( (int) get_post_meta( $sub_id, '_product_id', true ) );
	if ( ! $parent || ! $product ) {
		return 0;
	}
	$order = wc_create_order( array( 'customer_id' => (int) get_post_meta( $sub_id, '_customer_id', true ) ) );
	if ( is_wp_error( $order ) ) {
		return 0;
	}
	$qty   = max( 1, (int) get_post_meta( $sub_id, '_qty', true ) );
	$price = (float) get_post_meta( $sub_id, '_price', true );
	$order->add_product( $product, $qty, array( 'subtotal' => $price * $qty, 'total' => $price * $qty ) );
	$order->set_address( $parent->get_address( 'billing' ), 'billing' );
	$order->set_address( $parent->get_address( 'shipping' ), 'shipping' );
	$order->set_payment_method( (string) get_post_meta( $sub_id, '_method', true ) );
	$order->update_meta_data( '_mmc_renewal_of', $sub_id );
	$order->calculate_totals();
	$order->set_status( 'pending' );
	/* translators: %d: subscription id */
	$order->add_order_note( sprintf( __( 'Renewal for subscription #%d.', 'mine-mirth-commerce' ), $sub_id ) );
	$order->save();
	update_post_meta( $sub_id, '_pending_order', $order->get_id() );
	$mailer = WC()->mailer()->get_emails();
	if ( isset( $mailer['WC_Email_Customer_Invoice'] ) ) {
		$mailer['WC_Email_Customer_Invoice']->trigger( $order->get_id(), $order );
	}
	return $order->get_id();
}

// Daily housekeeping (runs on the plugin's five-minute cron, at most hourly).
add_action(
	'mmc_cron',
	static function () {
		if ( get_transient( 'mmc_sub_cron_lock' ) ) {
			return;
		}
		set_transient( 'mmc_sub_cron_lock', 1, HOUR_IN_SECONDS );
		$ahead = (int) mmc_setting( 'sub_remind_days' ) * DAY_IN_SECONDS;
		$grace = (int) mmc_setting( 'sub_grace_days' ) * DAY_IN_SECONDS;
		$subs  = get_posts(
			array(
				'post_type'      => 'mmc_subscription',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array( 'key' => '_status', 'value' => array( 'active', 'trial', 'on-hold' ), 'compare' => 'IN' ),
					array( 'key' => '_next_payment', 'value' => time() + $ahead, 'compare' => '<=', 'type' => 'NUMERIC' ),
				),
			)
		);
		foreach ( $subs as $sub_id ) {
			$t       = (array) get_post_meta( $sub_id, '_terms', true );
			$next    = (int) get_post_meta( $sub_id, '_next_payment', true );
			$pending = (int) get_post_meta( $sub_id, '_pending_order', true );
			$status  = (string) get_post_meta( $sub_id, '_status', true );
			if ( ! empty( $t['length'] ) && (int) get_post_meta( $sub_id, '_payments', true ) >= (int) $t['length'] ) {
				update_post_meta( $sub_id, '_status', 'expired' );
				continue;
			}
			if ( $pending ) {
				$o = wc_get_order( $pending );
				if ( $o && ! $o->is_paid() && 'active' === $status && $next + $grace < time() ) {
					update_post_meta( $sub_id, '_status', 'on-hold' );
				}
				continue;
			}
			if ( 'on-hold' === $status ) {
				continue;
			}
			if ( mmc_sub_create_renewal( $sub_id ) ) {
				update_post_meta( $sub_id, '_next_payment', mmc_sub_add_period( $next, $t ) );
			}
		}
	}
);

// An on-hold subscription becomes active again once its renewal is paid (handled in mmc_sub_renewal_paid).

/* ---------------------------------------------------------------------------
 * Admin
 * ------------------------------------------------------------------------- */

add_filter(
	'manage_mmc_subscription_posts_columns',
	static fn() => array(
		'cb'       => '<input type="checkbox">',
		'title'    => __( 'Subscription', 'mine-mirth-commerce' ),
		'status'   => __( 'Status', 'mine-mirth-commerce' ),
		'price'    => __( 'Price', 'mine-mirth-commerce' ),
		'next'     => __( 'Next payment', 'mine-mirth-commerce' ),
		'payments' => __( 'Payments', 'mine-mirth-commerce' ),
		'order'    => __( 'First order', 'mine-mirth-commerce' ),
	)
);
add_action(
	'manage_mmc_subscription_posts_custom_column',
	static function ( $col, $id ) {
		$t = (array) get_post_meta( $id, '_terms', true );
		switch ( $col ) {
			case 'status':
				echo esc_html( mmc_sub_statuses()[ (string) get_post_meta( $id, '_status', true ) ] ?? '' );
				break;
			case 'price':
				echo wp_kses_post( wc_price( (float) get_post_meta( $id, '_price', true ) ) ) . ' ' . esc_html( $t ? mmc_sub_terms_text( array_merge( array( 'fee' => 0, 'trial' => 0 ), $t, array( 'fee' => 0, 'trial' => 0 ) ) ) : '' );
				break;
			case 'next':
				$n = (int) get_post_meta( $id, '_next_payment', true );
				echo $n ? esc_html( wp_date( get_option( 'date_format' ), $n ) ) : '—';
				break;
			case 'payments':
				echo esc_html( (string) (int) get_post_meta( $id, '_payments', true ) ) . ( ! empty( $t['length'] ) ? ' / ' . esc_html( (string) $t['length'] ) : '' );
				break;
			case 'order':
				$o = wc_get_order( (int) get_post_meta( $id, '_order_id', true ) );
				echo $o ? '<a href="' . esc_url( $o->get_edit_order_url() ) . '">#' . esc_html( $o->get_order_number() ) . '</a>' : '—';
				break;
		}
	},
	10,
	2
);

add_action(
	'add_meta_boxes_mmc_subscription',
	static function () {
		add_meta_box(
			'mmc-sub',
			__( 'Subscription details', 'mine-mirth-commerce' ),
			static function ( $post ) {
				$status = (string) get_post_meta( $post->ID, '_status', true );
				$next   = (int) get_post_meta( $post->ID, '_next_payment', true );
				wp_nonce_field( 'mmc_sub_save', 'mmc_sub_nonce' );
				echo '<p><label>' . esc_html__( 'Status', 'mine-mirth-commerce' ) . '<br><select name="mmc_status">';
				foreach ( mmc_sub_statuses() as $k => $label ) {
					echo '<option value="' . esc_attr( $k ) . '" ' . selected( $status, $k, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select></label></p>';
				echo '<p><label>' . esc_html__( 'Next payment', 'mine-mirth-commerce' ) . '<br><input type="date" name="mmc_next" value="' . esc_attr( $next ? wp_date( 'Y-m-d', $next ) : '' ) . '"></label></p>';
				$pending = (int) get_post_meta( $post->ID, '_pending_order', true );
				if ( $pending && ( $o = wc_get_order( $pending ) ) ) {
					echo '<p>' . esc_html__( 'Unpaid renewal:', 'mine-mirth-commerce' ) . ' <a href="' . esc_url( $o->get_edit_order_url() ) . '">#' . esc_html( $o->get_order_number() ) . '</a></p>';
				}
				$customer = get_userdata( (int) get_post_meta( $post->ID, '_customer_id', true ) );
				echo $customer ? '<p>' . esc_html__( 'Customer:', 'mine-mirth-commerce' ) . ' ' . esc_html( $customer->display_name . ' <' . $customer->user_email . '>' ) . '</p>' : '';
			},
			'mmc_subscription',
			'normal',
			'high'
		);
	}
);
add_action(
	'save_post_mmc_subscription',
	static function ( $post_id ) {
		if ( ! isset( $_POST['mmc_sub_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mmc_sub_nonce'] ) ), 'mmc_sub_save' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$status = sanitize_key( wp_unslash( $_POST['mmc_status'] ?? '' ) );
		if ( isset( mmc_sub_statuses()[ $status ] ) ) {
			update_post_meta( $post_id, '_status', $status );
		}
		$next = strtotime( sanitize_text_field( wp_unslash( $_POST['mmc_next'] ?? '' ) ) . ' 09:00:00' );
		if ( $next ) {
			update_post_meta( $post_id, '_next_payment', $next );
		}
	}
);

/* ---------------------------------------------------------------------------
 * My account › Subscriptions
 * ------------------------------------------------------------------------- */

add_filter(
	'woocommerce_account_menu_items',
	static function ( $items ) {
		$out = array();
		foreach ( $items as $k => $v ) {
			$out[ $k ] = $v;
			if ( 'orders' === $k ) {
				$out['subscriptions'] = __( 'Subscriptions', 'mine-mirth-commerce' );
			}
		}
		return isset( $out['subscriptions'] ) ? $out : $items + array( 'subscriptions' => __( 'Subscriptions', 'mine-mirth-commerce' ) );
	}
);
add_filter( 'woocommerce_get_query_vars', static fn( $vars ) => $vars + array( 'subscriptions' => 'subscriptions' ) );
add_filter( 'woocommerce_endpoint_subscriptions_title', static fn() => __( 'Subscriptions', 'mine-mirth-commerce' ) );

function mmc_subscriptions_html(): string {
	if ( ! is_user_logged_in() ) {
		return '';
	}
	$subs = get_posts(
		array(
			'post_type'      => 'mmc_subscription',
			'posts_per_page' => 50,
			'meta_key'       => '_customer_id', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => get_current_user_id(), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	ob_start();
	if ( ! $subs ) {
		echo '<p class="mmc-empty">' . esc_html__( 'You have no subscriptions yet.', 'mine-mirth-commerce' ) . '</p>';
		return (string) ob_get_clean();
	}
	echo '<table class="shop_table shop_table_responsive mmc-subs"><thead><tr><th>' . esc_html__( 'Plan', 'mine-mirth-commerce' ) . '</th><th>' . esc_html__( 'Status', 'mine-mirth-commerce' ) . '</th><th>' . esc_html__( 'Next payment', 'mine-mirth-commerce' ) . '</th><th>' . esc_html__( 'Price', 'mine-mirth-commerce' ) . '</th><th></th></tr></thead><tbody>';
	foreach ( $subs as $sub ) {
		$status  = (string) get_post_meta( $sub->ID, '_status', true );
		$t       = (array) get_post_meta( $sub->ID, '_terms', true );
		$next    = (int) get_post_meta( $sub->ID, '_next_payment', true );
		$product = wc_get_product( (int) get_post_meta( $sub->ID, '_product_id', true ) );
		$pending = wc_get_order( (int) get_post_meta( $sub->ID, '_pending_order', true ) );
		echo '<tr>';
		echo '<td data-title="' . esc_attr__( 'Plan', 'mine-mirth-commerce' ) . '">' . esc_html( $product ? $product->get_name() : $sub->post_title ) . '</td>';
		echo '<td data-title="' . esc_attr__( 'Status', 'mine-mirth-commerce' ) . '">' . esc_html( mmc_sub_statuses()[ $status ] ?? $status ) . '</td>';
		echo '<td data-title="' . esc_attr__( 'Next payment', 'mine-mirth-commerce' ) . '">' . ( $next && in_array( $status, array( 'active', 'trial', 'on-hold' ), true ) ? esc_html( wp_date( get_option( 'date_format' ), $next ) ) : '—' ) . '</td>';
		echo '<td data-title="' . esc_attr__( 'Price', 'mine-mirth-commerce' ) . '">' . wp_kses_post( wc_price( (float) get_post_meta( $sub->ID, '_price', true ) ) ) . ' ' . esc_html( $t ? mmc_sub_terms_text( array_merge( $t, array( 'fee' => 0, 'trial' => 0 ) ) ) : '' ) . '</td>';
		echo '<td class="mmc-subs__actions">';
		if ( $pending && $pending->needs_payment() ) {
			echo '<a class="button" href="' . esc_url( $pending->get_checkout_payment_url() ) . '">' . esc_html__( 'Pay renewal', 'mine-mirth-commerce' ) . '</a> ';
		}
		if ( in_array( $status, array( 'active', 'trial', 'on-hold' ), true ) ) {
			$url = wp_nonce_url( add_query_arg( array( 'mmc_cancel_sub' => $sub->ID ), wc_get_account_endpoint_url( 'subscriptions' ) ), 'mmc_cancel_' . $sub->ID );
			echo '<a class="button mmc-subs__cancel" href="' . esc_url( $url ) . '" onclick="return confirm(\'' . esc_js( __( 'Cancel this subscription? No further renewals will be billed.', 'mine-mirth-commerce' ) ) . '\')">' . esc_html__( 'Cancel', 'mine-mirth-commerce' ) . '</a>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
	return (string) ob_get_clean();
}

add_action( 'woocommerce_account_subscriptions_endpoint', static fn() => print( mmc_subscriptions_html() ) ); // phpcs:ignore WordPress.Security.EscapeOutput
add_shortcode( 'mmc_subscriptions', static fn() => mmc_subscriptions_html() );

add_action(
	'template_redirect',
	static function () {
		if ( empty( $_GET['mmc_cancel_sub'] ) || ! is_user_logged_in() ) {
			return;
		}
		$id = absint( wp_unslash( $_GET['mmc_cancel_sub'] ) );
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'mmc_cancel_' . $id ) || (int) get_post_meta( $id, '_customer_id', true ) !== get_current_user_id() ) {
			return;
		}
		update_post_meta( $id, '_status', 'cancelled' );
		$pending = wc_get_order( (int) get_post_meta( $id, '_pending_order', true ) );
		if ( $pending && $pending->needs_payment() ) {
			$pending->update_status( 'cancelled', __( 'Subscription cancelled by the customer.', 'mine-mirth-commerce' ) );
		}
		wc_add_notice( __( 'Your subscription is cancelled. You will not be billed again.', 'mine-mirth-commerce' ) );
		wp_safe_redirect( wc_get_account_endpoint_url( 'subscriptions' ) );
		exit;
	}
);
