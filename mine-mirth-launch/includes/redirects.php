<?php
/**
 * Redirect engine. Runs only when WordPress is about to show a 404, so normal pages are untouched.
 *
 * Order: your manual rules → pages retired by the Mine Mirth site builder → the URL memory (the
 * object that used to live at that address) → smart matching (same slug, topic keywords, search)
 * → the closest section (shop or blog). Anything still unknown is logged as a 404.
 *
 * @package MineMirthLaunch
 */

defined( 'ABSPATH' ) || exit;

/* ---------------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------------- */

function mml_rules(): array {
	$r = get_option( 'mml_rules' );
	return is_array( $r ) ? $r : array();
}

function mml_save_rules( array $rules ): void {
	update_option( 'mml_rules', array_values( $rules ), false );
}

/** A page created by the Mine Mirth site builder, by its key. */
function mml_key_url( string $key ): string {
	static $cache = array();
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$ids = get_posts(
		array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'meta_key'    => '_mm_page_key', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'      => 'ids',
			'numberposts' => 1,
		)
	);
	return $cache[ $key ] = $ids ? (string) get_permalink( $ids[0] ) : '';
}

/** First published page found at one of these paths (@shop and @blog mean those sections). */
function mml_path_url( array $paths ): string {
	foreach ( $paths as $p ) {
		if ( '@shop' === $p ) {
			return mml_shop_url();
		}
		if ( '@blog' === $p ) {
			return mml_blog_url();
		}
		$page = get_page_by_path( $p );
		if ( $page && 'publish' === $page->post_status ) {
			return (string) get_permalink( $page );
		}
	}
	return '';
}

function mml_shop_url(): string {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$u = wc_get_page_permalink( 'shop' );
		if ( $u ) {
			return $u;
		}
	}
	return home_url( '/' );
}

function mml_blog_url(): string {
	$id = (int) get_option( 'page_for_posts' );
	return $id ? (string) get_permalink( $id ) : home_url( '/' );
}

/** Resolve a target written as a URL, a site path, or {key} for a site-builder page. */
function mml_target_url( string $to ): string {
	$to = trim( $to );
	if ( preg_match( '/^\{([a-z0-9_-]+)\}$/i', $to, $m ) ) {
		return mml_key_url( $m[1] );
	}
	if ( '' !== $to && '/' === $to[0] && ( ! isset( $to[1] ) || '/' !== $to[1] ) ) {
		return home_url( $to );
	}
	return esc_url_raw( $to );
}

/* ---------------------------------------------------------------------------
 * Topics: words in an old address → the page that now covers that topic
 * ------------------------------------------------------------------------- */

function mml_topics(): array {
	return apply_filters(
		'mml_topics',
		array(
			array( '/rig-optimi[sz]er|optimi[sz]er|compar/', array( 'tool-compare' ), array() ),
			array( '/market-?scope|hashprice|market-today|mining-market/', array( 'tool-hashprice', 'market' ), array() ),
			array( '/halving/', array( 'tool-halving', 'g-halving' ), array() ),
			array( '/difficulty/', array( 'tool-difficulty', 'g-difficulty' ), array() ),
			array( '/break-?even|electricity-cost|power-cost/', array( 'tool-breakeven' ), array() ),
			array( '/roi|payback/', array( 'tool-roi' ), array() ),
			array( '/solo/', array( 'tool-solo', 'm-solo' ), array() ),
			array( '/kaspa|kas-miner|iceriver/', array( 'm-kaspa' ), array( '@shop' ) ),
			array( '/litecoin|dogecoin|scrypt|ltc|doge/', array( 'm-scrypt', 'tool-altcoin' ), array( '@shop' ) ),
			array( '/alephium|blake3/', array( 'm-alephium' ), array() ),
			array( '/antminer|bitmain/', array( 'brand-bitmain' ), array() ),
			array( '/whatsminer|microbt/', array( 'brand-microbt' ), array() ),
			array( '/avalon|canaan/', array( 'brand-canaan' ), array() ),
			array( '/goldshell/', array( 'brand-goldshell' ), array() ),
			array( '/elphapex/', array( 'brand-elphapex' ), array() ),
			array( '/hydro/', array( 'm-hydro' ), array() ),
			array( '/immersion/', array( 'm-immersion' ), array() ),
			array( '/power-supply|psu|accessor|spare-part/', array( 'm-parts' ), array() ),
			array( '/hosting|colocation|host-/', array( 'hosting' ), array( 'miner-hosting', 'hosting' ) ),
			array( '/repair|service-center/', array( 'repairs' ), array() ),
			array( '/trade-?in|buy-?back|sell-your/', array( 'tradein' ), array() ),
			array( '/wholesale|bulk/', array( 'wholesale' ), array() ),
			array( '/calculator|calc|profit/', array( 'tool-profit', 'tools' ), array( 'free-calculators/bitcoin-mining-calculator', 'free-calculators' ) ),
			array( '/tools?$|free-tools/', array( 'tools' ), array( 'free-calculators' ) ),
			array( '/contact|support|help-desk/', array( 'contact' ), array( 'contact-us', 'contact' ) ),
			array( '/about|our-story|company/', array( 'about' ), array( 'about-us', 'about' ) ),
			array( '/faq|questions/', array( 'faq' ), array( 'faq', 'faqs' ) ),
			array( '/shipping|delivery/', array( 'shipping' ), array( 'shipping', 'shipping-policy' ) ),
			array( '/warranty/', array( 'warranty' ), array( 'warranty' ) ),
			array( '/refund|return/', array( 'returns' ), array( 'refund_returns', 'returns' ) ),
			array( '/privacy/', array( 'privacy' ), array( 'privacy-policy' ) ),
			array( '/terms|conditions|tos/', array( 'terms' ), array( 'terms', 'terms-and-conditions' ) ),
			array( '/payment/', array( 'payments' ), array() ),
			array( '/review|testimonial/', array( 'reviews' ), array() ),
			array( '/glossary/', array( 'g-glossary' ), array() ),
			array( '/beginner|getting-started|how-to-mine/', array( 'g-beginners', 'learn' ), array() ),
			array( '/guide|learn|academy|knowledge|news|article/', array( 'learn' ), array( '@blog' ) ),
			array( '/home-miner|quiet/', array( 'm-home' ), array() ),
			array( '/miners?$|asic|bitcoin-miner|shop|store/', array( 'miners', 'm-bitcoin' ), array( '@shop' ) ),
		)
	);
}

/* ---------------------------------------------------------------------------
 * Resolve an address
 * ------------------------------------------------------------------------- */

/**
 * @return array{0:string,1:string,2:int}|null [target URL, reason, status code]
 */
function mml_resolve( string $path ): ?array {
	if ( '' === $path ) {
		return null;
	}
	// 1. Manual rules: exact, then the longest "prefix/*" rule.
	$best = null;
	foreach ( mml_rules() as $r ) {
		$from = strtolower( trim( (string) ( $r['from'] ?? '' ), '/ ' ) );
		if ( '' === $from ) {
			continue;
		}
		if ( $from === $path ) {
			$best = $r;
			break;
		}
		if ( '*' === substr( $from, -1 ) ) {
			$prefix = rtrim( substr( $from, 0, -1 ), '/' );
			if ( ( '' === $prefix || 0 === strpos( $path, $prefix ) ) && ( ! $best || strlen( $prefix ) > strlen( rtrim( (string) $best['from'], '/*' ) ) ) ) {
				$best = $r;
			}
		}
	}
	if ( $best ) {
		$url = mml_target_url( (string) $best['to'] );
		if ( $url ) {
			return array( $url, 'rule', in_array( (int) ( $best['code'] ?? 301 ), array( 301, 302, 307, 308 ), true ) ? (int) $best['code'] : 301 );
		}
	}

	// 2. Pages retired by the Mine Mirth site builder.
	$retired = (array) get_option( 'mm_redirects', array() );
	if ( isset( $retired[ $path ] ) && ( $u = mml_key_url( (string) $retired[ $path ] ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
		return array( $u, 'retired', 301 );
	}

	// 2b. Old addresses the site builder knows belong to one of its pages (for example /hosting/).
	if ( function_exists( 'mm_builder_pages' ) ) {
		foreach ( mm_builder_pages() as $key => $def ) {
			if ( in_array( $path, array_map( static fn( $a ) => trim( (string) $a, '/' ), (array) ( $def['alias'] ?? array() ) ), true ) && ( $u = mml_key_url( (string) $key ) ) && mml_normalize_path( $u ) !== $path ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
				return array( $u, 'replaced', 301 );
			}
		}
	}

	// 3. URL memory: what used to live here.
	$mem = mml_memory_get( $path );
	if ( $mem ) {
		$u = mml_resolve_object( $mem, $path );
		if ( $u ) {
			return array( $u[0], $u[1], 301 );
		}
	}

	$s = mml_settings();
	// 4. Smart matching.
	if ( $s['smart'] ) {
		$u = mml_smart_match( $path );
		if ( $u ) {
			return array( $u[0], $u[1], 301 );
		}
	}

	// 5. The closest section.
	if ( $s['sections'] ) {
		$first = explode( '/', $path )[0];
		if ( in_array( $first, array( 'product', 'products', 'product-category', 'product-tag', 'shop', 'store', 'brand' ), true ) ) {
			return array( mml_shop_url(), 'section', 301 );
		}
		if ( in_array( $first, array( 'category', 'tag', 'blog', 'blogs', 'news', 'author', 'articles' ), true ) || preg_match( '/^\d{4}$/', $first ) ) {
			return array( mml_blog_url(), 'section', 301 );
		}
	}
	return null;
}

/**
 * Where the object that used to be at $path lives now (or what replaces it).
 *
 * @return array{0:string,1:string}|null
 */
function mml_resolve_object( object $mem, string $path ): ?array {
	$here = static fn( string $url ) => mml_normalize_path( $url ) === $path;
	switch ( $mem->obj_type ) {
		case 'post':
			$p = get_post( (int) $mem->obj_id );
			if ( $p && 'publish' === $p->post_status ) {
				$u = (string) get_permalink( $p );
				return $here( $u ) ? null : array( $u, 'moved' );
			}
			// Removed: a replacement set on the page, its parent, its category, or its section.
			if ( $p ) {
				$to = (string) get_post_meta( $p->ID, '_mml_replaced_by', true );
				if ( $to && ( $u = mml_target_url( $to ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
					return array( $u, 'replaced' );
				}
				if ( $p->post_parent && 'publish' === get_post_status( $p->post_parent ) ) {
					return array( (string) get_permalink( $p->post_parent ), 'parent' );
				}
				if ( 'product' === $p->post_type ) {
					$cats = wp_get_post_terms( $p->ID, 'product_cat' );
					if ( $cats && ! is_wp_error( $cats ) ) {
						$link = get_term_link( $cats[0] );
						if ( ! is_wp_error( $link ) ) {
							return array( $link, 'category' );
						}
					}
					return array( mml_shop_url(), 'section' );
				}
				if ( 'post' === $p->post_type ) {
					$cats = get_the_category( $p->ID );
					if ( $cats ) {
						return array( (string) get_category_link( $cats[0] ), 'category' );
					}
					return array( mml_blog_url(), 'section' );
				}
			}
			$smart = mml_smart_match( $path );
			if ( $smart ) {
				return $smart;
			}
			return 'product' === $mem->obj_sub ? array( mml_shop_url(), 'section' ) : ( 'post' === $mem->obj_sub ? array( mml_blog_url(), 'section' ) : null );
		case 'term':
			$t = get_term( (int) $mem->obj_id, (string) $mem->obj_sub );
			if ( $t && ! is_wp_error( $t ) ) {
				$u = get_term_link( $t );
				if ( ! is_wp_error( $u ) ) {
					return $here( $u ) ? null : array( $u, 'moved' );
				}
			}
			return in_array( $mem->obj_sub, array( 'product_cat', 'product_tag', 'product_brand' ), true ) ? array( mml_shop_url(), 'section' ) : array( mml_blog_url(), 'section' );
		case 'archive':
			$u = get_post_type_archive_link( (string) $mem->obj_sub );
			return $u && ! $here( $u ) ? array( $u, 'moved' ) : null;
		case 'author':
			$user = get_userdata( (int) $mem->obj_id );
			if ( $user ) {
				$u = get_author_posts_url( $user->ID );
				return $here( $u ) ? null : array( $u, 'moved' );
			}
			return array( mml_blog_url(), 'section' );
	}
	return null;
}

/**
 * Same slug elsewhere, a category of that name, an address that starts the same way, topic
 * keywords, then a confident search match.
 *
 * @return array{0:string,1:string}|null
 */
function mml_smart_match( string $path ): ?array {
	$segments = array_values( array_filter( explode( '/', $path ) ) );
	$last     = (string) end( $segments );
	$last     = preg_replace( '/\.(html?|php|aspx?)$/', '', $last );
	if ( '' === $last ) {
		return null;
	}
	// Same slug: a published page, post or product called the same thing.
	$types = array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );
	$found = get_posts( array( 'name' => sanitize_title( $last ), 'post_type' => $types, 'post_status' => 'publish', 'numberposts' => 2, 'suppress_filters' => true ) );
	if ( 1 === count( $found ) ) {
		$u = (string) get_permalink( $found[0] );
		if ( mml_normalize_path( $u ) !== $path ) {
			return array( $u, 'slug' );
		}
	}
	// A product category, blog category or brand with exactly that name.
	foreach ( array( 'product_cat', 'category', 'product_brand' ) as $tax ) {
		$exact = taxonomy_exists( $tax ) ? get_term_by( 'slug', sanitize_title( $last ), $tax ) : false;
		if ( $exact ) {
			$u = get_term_link( $exact );
			if ( ! is_wp_error( $u ) && mml_normalize_path( $u ) !== $path ) {
				return array( $u, 'category' );
			}
		}
	}
	// An address that starts the same way (a shortened or truncated old link).
	if ( strlen( $last ) >= 6 ) {
		global $wpdb;
		$like = $wpdb->esc_like( sanitize_title( $last ) ) . '%';
		$in   = implode( ',', array_map( static fn( $t ) => $wpdb->prepare( '%s', $t ), $types ) );
		$ids  = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($in) AND post_name LIKE %s LIMIT 2", $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		if ( 1 === count( $ids ) ) {
			$u = (string) get_permalink( (int) $ids[0] );
			if ( mml_normalize_path( $u ) !== $path ) {
				return array( $u, 'slug' );
			}
		}
	}
	// A category or brand whose name contains those words.
	foreach ( array( 'product_cat', 'category', 'product_brand' ) as $tax ) {
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'search' => str_replace( '-', ' ', $last ), 'number' => 2 ) );
		if ( ! is_wp_error( $terms ) && 1 === count( $terms ) ) {
			$u = get_term_link( $terms[0] );
			if ( ! is_wp_error( $u ) && mml_normalize_path( $u ) !== $path ) {
				return array( $u, 'category' );
			}
		}
	}
	// Topic keywords.
	foreach ( mml_topics() as $topic ) {
		if ( preg_match( $topic[0], $path ) ) {
			foreach ( $topic[1] as $key ) {
				$u = mml_key_url( $key );
				if ( $u && mml_normalize_path( $u ) !== $path ) {
					return array( $u, 'topic' );
				}
			}
			$u = mml_path_url( $topic[2] );
			if ( $u && mml_normalize_path( $u ) !== $path ) {
				return array( $u, 'topic' );
			}
		}
	}
	// Search: accept only a confident match (most of the words appear in the title).
	$words = array_values( array_filter( preg_split( '/[-_\s]+/', $last ), static fn( $w ) => strlen( $w ) > 2 && ! in_array( $w, array( 'the', 'and', 'for', 'with', 'your', 'from', 'html', 'page' ), true ) ) );
	if ( count( $words ) >= 2 ) {
		$q = new WP_Query( array( 's' => implode( ' ', $words ), 'post_type' => $types, 'post_status' => 'publish', 'posts_per_page' => 5, 'no_found_rows' => true ) );
		foreach ( $q->posts as $p ) {
			$title = strtolower( wp_strip_all_tags( $p->post_title ) );
			$hits  = count( array_filter( $words, static fn( $w ) => false !== strpos( $title, $w ) ) );
			if ( $hits / count( $words ) >= 0.6 ) {
				return array( (string) get_permalink( $p ), 'search' );
			}
		}
	}
	return null;
}

/* ---------------------------------------------------------------------------
 * Log
 * ------------------------------------------------------------------------- */

function mml_log( string $path, string $status, string $target = '', string $reason = '' ): void {
	global $wpdb;
	if ( ! mml_settings()['log'] ) {
		return;
	}
	$table = $wpdb->prefix . 'mml_log';
	$ref   = isset( $_SERVER['HTTP_REFERER'] ) ? substr( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), 0, 250 ) : '';
	$now   = current_time( 'mysql', true );
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT id, hits FROM {$table} WHERE path = %s", $path ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$data  = array( 'status' => $status, 'target' => substr( $target, 0, 250 ), 'reason' => $reason, 'last_seen' => $now );
	if ( $ref ) {
		$data['referrer'] = $ref;
	}
	if ( $row ) {
		$data['hits'] = (int) $row->hits + 1;
		$wpdb->update( $table, $data, array( 'id' => (int) $row->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	} else {
		$wpdb->insert( $table, $data + array( 'path' => $path, 'hits' => 1, 'referrer' => $ref ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}

// Keep the log to a sensible size.
add_action(
	'mml_daily',
	static function () {
		global $wpdb;
		$table = $wpdb->prefix . 'mml_log';
		$n     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $n > 5000 ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} ORDER BY last_seen ASC LIMIT %d", $n - 4000 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
	}
);

/* ---------------------------------------------------------------------------
 * The 404 handler
 * ------------------------------------------------------------------------- */

add_action(
	'template_redirect',
	static function () {
		if ( ! is_404() || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
			return;
		}
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = mml_normalize_path( $uri );
		if ( '' === $path || preg_match( '/\.(jpe?g|png|gif|webp|avif|svg|ico|css|js|map|woff2?|ttf|eot|mp4|webm|zip|pdf|txt|xml|json)$/', $path ) || 0 === strpos( $path, 'wp-' ) ) {
			return;
		}
		$res = mml_resolve( $path );
		if ( ! $res ) {
			mml_log( $path, '404' );
			return;
		}
		$target = $res[0];
		$query  = (string) wp_parse_url( $uri, PHP_URL_QUERY );
		if ( '' !== $query && false === strpos( $target, '?' ) ) {
			$target .= '?' . $query;
		}
		if ( mml_normalize_path( $target ) === $path && false === strpos( $target, '#' ) ) {
			mml_log( $path, '404', '', 'loop' );
			return;
		}
		mml_log( $path, 'redirect', $res[0], $res[1] );
		// Your own rules may point to another site; everything else stays on this one.
		if ( 'rule' === $res[1] ) {
			wp_redirect( $target, $res[2], 'Mine Mirth Launch' ); // phpcs:ignore WordPress.Security.SafeRedirect
		} else {
			wp_safe_redirect( $target, $res[2], 'Mine Mirth Launch' );
		}
		exit;
	},
	0
);

