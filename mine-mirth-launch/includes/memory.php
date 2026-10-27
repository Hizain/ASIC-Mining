<?php
/**
 * URL memory: every address the site has had (pages, posts, products, categories, tags, archives,
 * authors), remembered with the object it belongs to. Nothing is ever forgotten, so when an address
 * changes or its page disappears, the redirect engine still knows what it was.
 *
 * @package MineMirthLaunch
 */

defined( 'ABSPATH' ) || exit;

function mml_remember( string $url, string $type, string $sub, int $id ): void {
	global $wpdb;
	$path = mml_normalize_path( $url );
	if ( '' === $path || false !== strpos( $path, '?' ) ) {
		return;
	}
	$now   = current_time( 'mysql', true );
	$table = $wpdb->prefix . 'mml_urls';
	$have  = $wpdb->get_row( $wpdb->prepare( "SELECT id, obj_type, obj_id FROM {$table} WHERE path = %s", $path ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	if ( $have ) {
		// The address now belongs to this object (it is the one published there).
		$wpdb->update( $table, array( 'obj_type' => $type, 'obj_sub' => $sub, 'obj_id' => $id, 'last_seen' => $now ), array( 'id' => (int) $have->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return;
	}
	$wpdb->insert( $table, array( 'path' => $path, 'obj_type' => $type, 'obj_sub' => $sub, 'obj_id' => $id, 'first_seen' => $now, 'last_seen' => $now ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/** Look up a remembered address. */
function mml_memory_get( string $path ): ?object {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mml_urls WHERE path = %s", $path ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	return $row ? $row : null;
}

function mml_memory_count(): int {
	global $wpdb;
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}mml_urls" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/**
 * Record every current public address. Safe to run any time; existing entries are kept.
 */
function mml_memory_scan(): int {
	global $wpdb;
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'mml_urls' ) ) !== $wpdb->prefix . 'mml_urls' ) {
		mml_install();
	}
	wp_suspend_cache_addition( true );
	$n     = 0;
	$types = array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) );
	foreach ( $types as $type ) {
		$page = 1;
		do {
			$ids = get_posts(
				array(
					'post_type'        => $type,
					'post_status'      => 'publish',
					'fields'           => 'ids',
					'posts_per_page'   => 300,
					'paged'            => $page,
					'orderby'          => 'ID',
					'order'            => 'ASC',
					'suppress_filters' => true,
					'no_found_rows'    => true,
				)
			);
			foreach ( $ids as $id ) {
				mml_remember( (string) get_permalink( $id ), 'post', $type, (int) $id );
				++$n;
			}
			++$page;
		} while ( count( $ids ) === 300 );
		$archive = get_post_type_archive_link( $type );
		if ( $archive && 'post' !== $type ) {
			mml_remember( $archive, 'archive', $type, 0 );
		}
	}
	foreach ( get_taxonomies( array( 'public' => true ) ) as $tax ) {
		if ( 'post_format' === $tax ) {
			continue;
		}
		$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'number' => 2000 ) );
		if ( is_wp_error( $terms ) ) {
			continue;
		}
		foreach ( $terms as $t ) {
			$link = get_term_link( $t );
			if ( ! is_wp_error( $link ) ) {
				mml_remember( $link, 'term', $tax, (int) $t->term_id );
				++$n;
			}
		}
	}
	foreach ( get_users( array( 'has_published_posts' => true, 'fields' => 'ID' ) ) as $uid ) {
		mml_remember( get_author_posts_url( (int) $uid ), 'author', '', (int) $uid );
	}
	$blog = (int) get_option( 'page_for_posts' );
	if ( $blog ) {
		mml_remember( (string) get_permalink( $blog ), 'post', 'page', $blog );
	}
	wp_suspend_cache_addition( false );
	update_option( 'mml_last_scan', time(), false );
	return $n;
}
add_action( 'mm_launch_before_go_live', 'mml_memory_scan' );
add_action( 'mml_take_snapshot', 'mml_memory_scan' );

// Remember an address the moment it is about to change or disappear.
add_action(
	'post_updated',
	static function ( $id, $after, $before ) {
		if ( 'publish' === $before->post_status && is_post_type_viewable( $before->post_type ) && 'attachment' !== $before->post_type ) {
			$old = get_permalink( $before );
			mml_remember( (string) $old, 'post', $before->post_type, (int) $id );
		}
	},
	5,
	3
);
add_action(
	'pre_post_update',
	static function ( $id ) {
		$p = get_post( $id );
		if ( $p && 'publish' === $p->post_status && is_post_type_viewable( $p->post_type ) && 'attachment' !== $p->post_type ) {
			mml_remember( (string) get_permalink( $p ), 'post', $p->post_type, (int) $id );
		}
	},
	5
);
foreach ( array( 'wp_trash_post', 'before_delete_post' ) as $mml_hook ) {
	add_action(
		$mml_hook,
		static function ( $id ) {
			$p = get_post( $id );
			if ( $p && 'publish' === $p->post_status && is_post_type_viewable( $p->post_type ) && 'attachment' !== $p->post_type ) {
				mml_remember( (string) get_permalink( $p ), 'post', $p->post_type, (int) $id );
			}
		},
		5
	);
}
add_action(
	'edit_terms',
	static function ( $term_id, $tax ) {
		$link = get_term_link( (int) $term_id, $tax );
		if ( ! is_wp_error( $link ) ) {
			mml_remember( $link, 'term', $tax, (int) $term_id );
		}
	},
	5,
	2
);
add_action(
	'pre_delete_term',
	static function ( $term_id, $tax ) {
		$link = get_term_link( (int) $term_id, $tax );
		if ( ! is_wp_error( $link ) ) {
			mml_remember( $link, 'term', $tax, (int) $term_id );
		}
	},
	5,
	2
);
