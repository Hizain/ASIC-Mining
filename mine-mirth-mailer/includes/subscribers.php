<?php
/**
 * Subscribers: the theme's mm_subscriber posts (title = email), plus a private
 * token for links, an active/unsubscribed status and an activity profile.
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

function mmm_sub_email( int $id ): string {
	return 'mm_subscriber' === get_post_type( $id ) ? sanitize_email( get_the_title( $id ) ) : '';
}

function mmm_sub_token( int $id ): string {
	$token = (string) get_post_meta( $id, '_mmm_token', true );
	if ( ! preg_match( '/^[a-f0-9]{32}$/', $token ) ) {
		$token = bin2hex( random_bytes( 16 ) );
		update_post_meta( $id, '_mmm_token', $token );
	}
	return $token;
}

function mmm_sub_by_token( string $token ): int {
	if ( ! preg_match( '/^[a-f0-9]{32}$/', $token ) ) {
		return 0;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'mm_subscriber',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_mmm_token', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $token, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

function mmm_sub_by_email( string $email ): int {
	$email = sanitize_email( $email );
	if ( ! is_email( $email ) ) {
		return 0;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'mm_subscriber',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'title'          => $email,
		)
	);
	if ( ! $ids ) {
		// Titles are matched exactly; fall back to a case-insensitive search.
		global $wpdb;
		$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'mm_subscriber' AND LOWER(post_title) = LOWER(%s) LIMIT 1", $email ) ); // phpcs:ignore WordPress.DB
		return $id;
	}
	return (int) $ids[0];
}

function mmm_sub_is_active( int $id ): bool {
	return 'mm_subscriber' === get_post_type( $id ) && 'trash' !== get_post_status( $id ) && 'unsubscribed' !== get_post_meta( $id, '_mmm_status', true );
}

function mmm_sub_set_status( int $id, string $status, int $campaign = 0 ): void {
	if ( 'unsubscribed' === $status ) {
		update_post_meta( $id, '_mmm_status', 'unsubscribed' );
		update_post_meta( $id, '_mmm_unsub_at', time() );
		mmm_event( $id, 'unsub', array( 'campaign_id' => $campaign ) );
	} else {
		delete_post_meta( $id, '_mmm_status' );
		delete_post_meta( $id, '_mmm_unsub_at' );
	}
}

/**
 * IDs of every active subscriber.
 *
 * @return int[]
 */
function mmm_active_subscribers(): array {
	$ids = get_posts(
		array(
			'post_type'      => 'mm_subscriber',
			'post_status'    => array( 'publish', 'private', 'draft', 'pending' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => '_mmm_status',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);
	return array_map( 'intval', $ids );
}

/**
 * First name when the subscriber is also a customer or user.
 */
function mmm_sub_first_name( int $id ): string {
	$cached = get_post_meta( $id, '_mmm_first_name', true );
	if ( '' !== $cached ) {
		return (string) $cached;
	}
	$email = mmm_sub_email( $id );
	$name  = '';
	$user  = $email ? get_user_by( 'email', $email ) : null;
	if ( $user ) {
		$name = (string) ( $user->first_name ? $user->first_name : '' );
		if ( ! $name && function_exists( 'get_user_meta' ) ) {
			$name = (string) get_user_meta( $user->ID, 'billing_first_name', true );
		}
	}
	if ( ! $name && $email && function_exists( 'wc_get_orders' ) ) {
		$orders = wc_get_orders( array( 'billing_email' => $email, 'limit' => 1 ) );
		if ( $orders ) {
			$name = (string) $orders[0]->get_billing_first_name();
		}
	}
	update_post_meta( $id, '_mmm_first_name', $name );
	return $name;
}

/**
 * WooCommerce products this subscriber has bought.
 *
 * @return int[]
 */
function mmm_sub_purchases( int $id ): array {
	$email = mmm_sub_email( $id );
	if ( ! $email || ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}
	$out = array();
	foreach ( wc_get_orders( array( 'billing_email' => $email, 'limit' => 20, 'status' => array( 'wc-processing', 'wc-completed', 'wc-on-hold' ) ) ) as $order ) {
		foreach ( $order->get_items() as $item ) {
			if ( is_callable( array( $item, 'get_product_id' ) ) ) {
				$out[] = (int) $item->get_product_id();
			}
		}
	}
	return array_values( array_unique( $out ) );
}

/**
 * What a subscriber has been doing on the site recently.
 *
 * @return array{areas:array<string,int>,products:int[],product_cats:int[],posts:int[],post_cats:int[],last_seen:int,purchases:int[],opens:int,clicks:int,top:string}
 */
function mmm_profile( int $id, int $days = 30 ): array {
	global $wpdb;
	$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB
		$wpdb->prepare(
			'SELECT type, area, object_id, created FROM ' . mmm_table( 'events' ) . ' WHERE sub_id = %d AND created > %s ORDER BY created DESC LIMIT 500',
			$id,
			gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS )
		)
	);
	$p = array(
		'areas'        => array( 'home' => 0, 'shop' => 0, 'blog' => 0, 'tools' => 0, 'checkout' => 0, 'page' => 0 ),
		'products'     => array(),
		'product_cats' => array(),
		'posts'        => array(),
		'post_cats'    => array(),
		'last_seen'    => 0,
		'purchases'    => mmm_sub_purchases( $id ),
		'opens'        => 0,
		'clicks'       => 0,
		'tools'        => array(),
	);
	foreach ( (array) $rows as $r ) {
		if ( 'open' === $r->type ) {
			++$p['opens'];
			continue;
		}
		if ( 'click' === $r->type ) {
			++$p['clicks'];
			continue;
		}
		if ( 'view' !== $r->type ) {
			continue;
		}
		$p['last_seen'] = max( $p['last_seen'], (int) strtotime( $r->created . ' UTC' ) );
		$area           = (string) $r->area;
		$obj            = (int) $r->object_id;
		switch ( $area ) {
			case 'product':
				++$p['areas']['shop'];
				if ( $obj && ! in_array( $obj, $p['products'], true ) ) {
					$p['products'][] = $obj;
				}
				break;
			case 'shop':
				++$p['areas']['shop'];
				if ( $obj ) {
					$p['product_cats'][] = $obj;
				}
				break;
			case 'post':
				++$p['areas']['blog'];
				if ( $obj && ! in_array( $obj, $p['posts'], true ) ) {
					$p['posts'][] = $obj;
				}
				break;
			case 'blog':
				++$p['areas']['blog'];
				if ( $obj ) {
					$p['post_cats'][] = $obj;
				}
				break;
			case 'hosting':
			case 'calculator':
				++$p['areas']['tools'];
				$p['tools'][] = $area;
				break;
			default:
				if ( isset( $p['areas'][ $area ] ) ) {
					++$p['areas'][ $area ];
				}
		}
	}
	foreach ( $p['products'] as $pid ) {
		foreach ( wp_get_post_terms( $pid, 'product_cat', array( 'fields' => 'ids' ) ) ?: array() as $tid ) {
			$p['product_cats'][] = (int) $tid;
		}
	}
	foreach ( $p['posts'] as $pid ) {
		foreach ( wp_get_post_categories( $pid ) as $tid ) {
			$p['post_cats'][] = (int) $tid;
		}
	}
	$p['product_cats'] = array_values( array_unique( array_map( 'intval', $p['product_cats'] ) ) );
	$p['post_cats']    = array_values( array_unique( array_map( 'intval', $p['post_cats'] ) ) );
	$p['tools']        = array_values( array_unique( $p['tools'] ) );
	$areas             = $p['areas'];
	unset( $areas['page'], $areas['checkout'] );
	arsort( $areas );
	$p['top'] = (int) reset( $areas ) > 0 ? (string) key( $areas ) : 'none';
	return $p;
}

/* ---------------------------------------------------------------------------
 * Audiences
 * ------------------------------------------------------------------------- */

function mmm_audiences(): array {
	return array(
		'all'          => __( 'All active subscribers', 'mine-mirth-mailer' ),
		'shop'         => __( 'Browsed the shop in the last 30 days', 'mine-mirth-mailer' ),
		'blog'         => __( 'Read the blog in the last 30 days', 'mine-mirth-mailer' ),
		'tools'        => __( 'Used hosting or calculator pages in the last 30 days', 'mine-mirth-mailer' ),
		'engaged'      => __( 'Opened or clicked an email in the last 60 days', 'mine-mirth-mailer' ),
		'inactive'     => __( 'No activity in the last 60 days', 'mine-mirth-mailer' ),
		'customers'    => __( 'Customers (have ordered)', 'mine-mirth-mailer' ),
		'noncustomers' => __( 'Not customers yet', 'mine-mirth-mailer' ),
	);
}

/**
 * Subscriber IDs in an audience.
 *
 * @return int[]
 */
function mmm_audience_ids( string $audience ): array {
	global $wpdb;
	$all = mmm_active_subscribers();
	if ( 'all' === $audience || ! $all ) {
		return $all;
	}
	$events = mmm_table( 'events' );
	$since  = static fn( int $days ) => gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
	$ids    = array();
	switch ( $audience ) {
		case 'shop':
		case 'blog':
		case 'tools':
			$areas = array( 'shop' => array( 'shop', 'product' ), 'blog' => array( 'blog', 'post' ), 'tools' => array( 'hosting', 'calculator' ) )[ $audience ];
			$in    = "'" . implode( "','", array_map( 'esc_sql', $areas ) ) . "'";
			$ids   = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT sub_id FROM $events WHERE type = 'view' AND area IN ($in) AND created > %s", $since( 30 ) ) ); // phpcs:ignore WordPress.DB
			break;
		case 'engaged':
			$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT sub_id FROM $events WHERE type IN ('open','click','reply') AND created > %s", $since( 60 ) ) ); // phpcs:ignore WordPress.DB
			break;
		case 'inactive':
			$active = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT sub_id FROM $events WHERE type IN ('view','open','click','reply') AND created > %s", $since( 60 ) ) ); // phpcs:ignore WordPress.DB
			return array_values( array_diff( $all, array_map( 'intval', $active ) ) );
		case 'customers':
		case 'noncustomers':
			$buyers = array();
			foreach ( $all as $id ) {
				if ( mmm_sub_purchases( $id ) ) {
					$buyers[] = $id;
				}
			}
			return 'customers' === $audience ? $buyers : array_values( array_diff( $all, $buyers ) );
	}
	return array_values( array_intersect( $all, array_map( 'intval', (array) $ids ) ) );
}
