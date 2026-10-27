<?php
/**
 * Site builder: creates or updates the 80 Mine Mirth pages, menus and front page in one click
 * (Appearance › Mine Mirth site builder).
 *
 * Safe by design: a preview lists every page first; pages you edited after a build are left
 * alone unless you tick them; anything replaced is kept in the page's Revisions; WooCommerce
 * pages are never touched.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/registry.php';
require_once __DIR__ . '/staging.php';

/* ---------------------------------------------------------------------------
 * Block markup helpers used by the content files
 * ------------------------------------------------------------------------- */

function mm_bp( string $html ): string {
	return "<!-- wp:paragraph -->\n<p>" . $html . "</p>\n<!-- /wp:paragraph -->\n\n";
}

function mm_bh( string $text, int $level = 2 ): string {
	$attrs = 2 === $level ? '' : ' ' . wp_json_encode( array( 'level' => $level ) );
	return '<!-- wp:heading' . $attrs . " -->\n<h" . $level . ' class="wp-block-heading">' . $text . '</h' . $level . ">\n<!-- /wp:heading -->\n\n";
}

function mm_bl( array $items, bool $ordered = false ): string {
	$li = '';
	foreach ( $items as $item ) {
		$li .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->";
	}
	$tag = $ordered ? 'ol' : 'ul';
	return '<!-- wp:list' . ( $ordered ? ' {"ordered":true}' : '' ) . " -->\n<" . $tag . ' class="wp-block-list">' . $li . '</' . $tag . ">\n<!-- /wp:list -->\n\n";
}

/** A Mine Mirth section block. */
function mm_bs( string $variant, array $data ): string {
	// Steps may be written as [icon, title, text] rows.
	if ( 'steps' === $variant && ! empty( $data['items'] ) ) {
		$data['items'] = array_map( static fn( $r ) => isset( $r[0] ) ? array( 'icon' => $r[0], 'title' => $r[1] ?? '', 'text' => $r[2] ?? '' ) : $r, $data['items'] );
	}
	return serialize_block(
		array(
			'blockName'    => 'mm/section',
			'attrs'        => array( 'variant' => $variant, 'data' => $data ),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		)
	) . "\n\n";
}

/** FAQ section from [question, answer] pairs. */
function mm_bfaq( array $pairs, string $title = '', array $extra = array() ): string {
	$items = array();
	foreach ( $pairs as $p ) {
		$items[] = array( 'q' => $p[0], 'a' => $p[1] );
	}
	return mm_bs( 'faq', array_merge( array( 'eyebrow' => __( 'FAQ', 'minemirth' ), 'title' => $title ? $title : __( 'Questions, *answered*', 'minemirth' ), 'items' => $items, 'schema' => true, 'open_first' => true ), $extra ) );
}

/** Callout section (info, primary, success, warning). */
function mm_bcall( string $tone, string $title, string $text, array $extra = array() ): string {
	return mm_bs( 'callout', array_merge( array( 'tone' => $tone, 'title' => $title, 'text' => $text, 'pad' => 'sm' ), $extra ) );
}

/** Closing call to action. */
function mm_bcta( string $title, string $text, array $buttons, array $extra = array() ): string {
	$b = array();
	foreach ( $buttons as $i => $btn ) {
		$b[] = array( 'label' => $btn[0], 'url' => $btn[1], 'style' => $btn[2] ?? ( 0 === $i ? 'primary' : 'glass' ), 'icon' => $btn[3] ?? '' );
	}
	return mm_bs( 'cta', array_merge( array( 'title' => $title, 'text' => $text, 'buttons' => $b, 'style' => 'dark' ), $extra ) );
}

/** Buttons array from [label, url, style] rows. */
function mm_bbtn( array $rows ): array {
	$out = array();
	foreach ( $rows as $i => $r ) {
		$out[] = array( 'label' => $r[0], 'url' => $r[1], 'style' => $r[2] ?? ( 0 === $i ? 'primary' : 'glass' ), 'icon' => $r[3] ?? '' );
	}
	return $out;
}

/** Feature items from [icon, title, text, url?] rows. */
function mm_bitems( array $rows ): array {
	$out = array();
	foreach ( $rows as $r ) {
		$out[] = array_filter( array( 'icon' => $r[0], 'title' => $r[1], 'text' => $r[2], 'url' => $r[3] ?? '', 'link_label' => $r[4] ?? '' ) );
	}
	return $out;
}

/** Orbit chips from [icon, value, label] rows. */
function mm_bchips( array $rows ): array {
	return array_map( static fn( $r ) => array( 'icon' => $r[0], 'value' => $r[1], 'label' => $r[2] ), $rows );
}

/* ---------------------------------------------------------------------------
 * Content
 * ------------------------------------------------------------------------- */

/**
 * Block markup for a page. Content files return key => callable.
 */
function mm_builder_content( string $key ): string {
	static $map = null;
	if ( null === $map ) {
		$map = array();
		foreach ( glob( __DIR__ . '/content/*.php' ) as $file ) {
			$part = include $file;
			if ( is_array( $part ) ) {
				$map = array_merge( $map, $part );
			}
		}
	}
	$html = '';
	if ( isset( $map[ $key ] ) && is_callable( $map[ $key ] ) ) {
		$html = (string) call_user_func( $map[ $key ] );
	} elseif ( 0 === strpos( $key, 'tool-' ) && isset( $map['tool-default'] ) ) {
		$html = (string) call_user_func( $map['tool-default'], substr( $key, 5 ) );
	}
	if ( '' !== $html && isset( $map[ 'seo:' . $key ] ) && is_callable( $map[ 'seo:' . $key ] ) ) {
		$html = mm_builder_insert_before_end( $html, (string) call_user_func( $map[ 'seo:' . $key ] ) );
	}
	return '' !== $html ? trim( $html ) . "\n" : '';
}

/**
 * Put an extra section before the page's questions or its closing call to action, so the page
 * still ends the way it was designed.
 */
function mm_builder_insert_before_end( string $html, string $extra ): string {
	if ( '' === trim( $extra ) ) {
		return $html;
	}
	$at = strpos( $html, '<!-- wp:mm/section {"variant":"faq"' );
	if ( false === $at ) {
		$at = strrpos( $html, '<!-- wp:mm/section {"variant":"cta"' );
	}
	if ( false === $at ) {
		$at = strrpos( $html, '<!-- wp:mm/section {"variant":"newsletter"' );
	}
	return false === $at ? $html . $extra : substr( $html, 0, $at ) . $extra . substr( $html, $at );
}

/** Times a keyword appears in a text (singular and plural count, any case). */
function mm_keyword_count( string $kw, string $text ): int {
	$words = preg_split( '/\s+/u', mb_strtolower( trim( $kw ) ) );
	if ( ! $words || '' === $words[0] ) {
		return 0;
	}
	$last  = (string) preg_replace( '/(?<=\p{L}{3})(es|s)$/u', '', (string) array_pop( $words ) );
	$parts = array_map( static fn( $w ) => preg_quote( $w, '/' ), $words );
	$parts[] = preg_quote( $last, '/' ) . '(?:s|es)?';
	return (int) preg_match_all( '/(?<![\p{L}\p{N}])' . implode( '[\s-]+', $parts ) . '(?![\p{L}\p{N}])/u', mb_strtolower( $text ) );
}

/**
 * Focus keyword, word count and keyword density of every builder page, measured on the text the
 * page shows (its title, intro and content). Cached per theme version: the text only changes
 * with the theme.
 *
 * @return array<string,array{kw:string,words:int,count:int,density:float}>
 */
function mm_builder_seo_stats(): array {
	$cache = get_transient( 'mm_builder_seo_' . MM_VERSION );
	if ( is_array( $cache ) ) {
		return $cache;
	}
	$out = array();
	foreach ( mm_builder_pages() as $key => $def ) {
		$kw = (string) ( $def['kw'] ?? '' );
		if ( '' === $kw ) {
			continue;
		}
		$html  = do_blocks( mm_builder_content( $key ) );
		$html  = function_exists( 'mm_s_live' ) ? mm_s_live( $html ) : $html; // Live values as visitors see them.
		$html  = str_replace( '<', ' <', $html ); // Keep words apart across tags.
		$text  = wp_strip_all_tags( ( 'premium' === ( $def['tpl'] ?? '' ) ? '' : $def['t'] . ' ' . ( $def['x'] ?? '' ) . ' ' ) . $html );
		$text  = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$words = (int) preg_match_all( "/[\p{L}\p{N}$][\p{L}\p{N}$'’.,\/%-]*/u", $text );
		$count = mm_keyword_count( $kw, $text );
		$out[ $key ] = array(
			'kw'      => $kw,
			'words'   => $words,
			'count'   => $count,
			'density' => $words ? round( 100 * $count / $words, 2 ) : 0.0,
		);
	}
	set_transient( 'mm_builder_seo_' . MM_VERSION, $out, WEEK_IN_SECONDS );
	return $out;
}

/**
 * SEO section: a heading with the page's keyword and a few paragraphs of plain, useful text.
 * Guides and tool pages get ordinary heading and paragraph blocks, other pages a prose section.
 *
 * @param string[] $paras Paragraphs (links and bold allowed).
 */
function mm_bseo( string $heading, array $paras, string $eyebrow = '', bool $article = false ): string {
	if ( $article ) {
		$out = mm_bh( $heading );
		foreach ( $paras as $para ) {
			$out .= 0 === strpos( $para, '###' ) ? mm_bh( trim( substr( $para, 3 ) ), 3 ) : mm_bp( $para );
		}
		return $out;
	}
	$html = '';
	foreach ( $paras as $para ) {
		$html .= 0 === strpos( $para, '###' ) ? '<h3>' . esc_html( trim( substr( $para, 3 ) ) ) . '</h3>' : '<p>' . $para . '</p>';
	}
	return mm_bs( 'prose', array( 'eyebrow' => $eyebrow, 'title' => $heading, 'html' => $html ) );
}

/* ---------------------------------------------------------------------------
 * Plan and build
 * ------------------------------------------------------------------------- */

function mm_builder_find( string $key, array $def ): ?WP_Post {
	$ids = get_posts(
		array(
			'post_type'   => 'page',
			'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'meta_key'    => '_mm_page_key', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'      => 'ids',
			'numberposts' => 1,
		)
	);
	if ( $ids ) {
		return get_post( $ids[0] );
	}
	foreach ( array_merge( array( $def['p'] ), (array) ( $def['alias'] ?? array() ) ) as $path ) {
		$p = get_page_by_path( $path );
		if ( $p && 'trash' !== $p->post_status ) {
			return $p;
		}
	}
	if ( 'privacy' === $key && (int) get_option( 'wp_page_for_privacy_policy' ) ) {
		$p = get_post( (int) get_option( 'wp_page_for_privacy_policy' ) );
		return $p && 'trash' !== $p->post_status ? $p : null;
	}
	return null;
}

function mm_builder_protected_ids(): array {
	$ids = array();
	if ( function_exists( 'wc_get_page_id' ) ) {
		foreach ( array( 'shop', 'cart', 'checkout', 'myaccount' ) as $p ) {
			$ids[] = (int) wc_get_page_id( $p );
		}
	}
	$ids[] = (int) get_option( 'page_for_posts' );
	return array_filter( $ids );
}

/**
 * One row per page: state create|update|edited|replace|keep|protected.
 */
function mm_builder_plan(): array {
	$rows      = array();
	$protected = mm_builder_protected_ids();
	foreach ( mm_builder_pages() as $key => $def ) {
		$post  = mm_builder_find( $key, $def );
		$state = 'create';
		if ( $post ) {
			$ours = get_post_meta( $post->ID, '_mm_page_key', true ) === $key;
			if ( in_array( (int) $post->ID, $protected, true ) ) {
				$state = 'protected';
			} elseif ( mm_staged( (int) $post->ID ) ) {
				$state = empty( mm_staged( (int) $post->ID )['edited'] ) ? 'update' : 'edited';
			} elseif ( $ours ) {
				$state = get_post_meta( $post->ID, '_mm_build_hash', true ) === md5( $post->post_content ) ? 'update' : 'edited';
			} else {
				$state = ! empty( $def['keep'] ) && '' !== trim( $post->post_content ) ? 'keep' : 'replace';
			}
		}
		$rows[ $key ] = array(
			'key'     => $key,
			'def'     => $def,
			'post'    => $post,
			'state'   => $state,
			'checked' => in_array( $state, array( 'create', 'update', 'replace' ), true ),
		);
	}
	return $rows;
}

function mm_builder_template( string $tpl ): string {
	return array(
		'premium' => 'page-templates/premium.php',
		'guide'   => 'page-templates/guide.php',
		'tool'    => 'page-templates/tool.php',
	)[ $tpl ] ?? 'default';
}

/**
 * Create or update the selected pages. Returns key => [id, action].
 */
function mm_builder_run( array $keys ): array {
	$plan   = mm_builder_plan();
	$ids    = array();
	$result = array();
	$order  = 0;
	wp_defer_term_counting( true );
	kses_remove_filters(); // Block attributes and link tokens must be stored exactly as built.
	foreach ( $plan as $key => $row ) {
		++$order;
		$def  = $row['def'];
		$post = $row['post'];
		if ( $post ) {
			$ids[ $key ] = (int) $post->ID;
		}
		if ( ! in_array( $key, $keys, true ) || 'protected' === $row['state'] ) {
			continue;
		}
		$content   = mm_builder_content( $key );
		$parent_id = ! empty( $def['parent'] ) ? (int) ( $ids[ $def['parent'] ] ?? 0 ) : 0;
		$segments  = explode( '/', $def['p'] );
		$staging   = mm_is_staging();
		// Test mode: a live public page is not changed; the new version is saved inside it.
		if ( $staging && $post && 'publish' === $post->post_status ) {
			update_post_meta(
				$post->ID,
				'_mm_staged',
				wp_slash(
					array(
						'content'  => $content,
						'title'    => $def['t'],
						'excerpt'  => $def['x'] ?? '',
						'parent'   => $post->post_name === end( $segments ) ? $parent_id : null,
						'template' => mm_builder_template( (string) $def['tpl'] ),
						'icon'     => (string) ( $def['icon'] ?? '' ),
						'group'    => (string) ( $def['g'] ?? '' ),
						'tool'     => (string) ( $def['tool'] ?? '' ),
						'st'       => (string) ( $def['st'] ?? '' ),
						'sd'       => (string) ( $def['sd'] ?? '' ),
						'kw'       => (string) ( $def['kw'] ?? '' ),
						'built'    => time(),
					)
				)
			);
			update_post_meta( $post->ID, '_mm_page_key', $key );
			$ids[ $key ]    = (int) $post->ID;
			$result[ $key ] = array( (int) $post->ID, 'staged' );
			continue;
		}
		$args      = array(
			'post_type'    => 'page',
			'post_status'  => $staging && ( ! $post || 'publish' !== $post->post_status ) ? 'private' : 'publish',
			'post_title'   => $def['t'],
			'post_content' => $content,
			'post_excerpt' => $def['x'] ?? '',
			'menu_order'   => (int) ( $def['o'] ?? $order ),
		);
		if ( $post ) {
			if ( wp_revisions_enabled( $post ) && '' !== trim( $post->post_content ) ) {
				_wp_put_post_revision( $post ); // Keep the version being replaced.
			} elseif ( '' !== trim( $post->post_content ) ) {
				update_post_meta( $post->ID, '_mm_previous_content', wp_slash( $post->post_content ) );
			}
			$args['ID'] = $post->ID;
			// A page found at its planned path moves under the right parent; one found at an
			// alias (for example /contact-us/) keeps its address.
			if ( $post->post_name === end( $segments ) ) {
				$args['post_parent'] = $parent_id;
			}
			$id     = wp_update_post( wp_slash( $args ), true );
			$action = 'updated';
		} else {
			$args['post_name']   = end( $segments );
			$args['post_parent'] = $parent_id;
			$id                  = wp_insert_post( wp_slash( $args ), true );
			$action              = 'created';
		}
		if ( is_wp_error( $id ) || ! $id ) {
			$result[ $key ] = array( 0, 'error: ' . ( is_wp_error( $id ) ? $id->get_error_message() : '?' ) );
			continue;
		}
		$ids[ $key ] = (int) $id;
		update_post_meta( $id, '_mm_page_key', $key );
		update_post_meta( $id, '_wp_page_template', mm_builder_template( (string) $def['tpl'] ) );
		update_post_meta( $id, '_mm_icon', (string) ( $def['icon'] ?? '' ) );
		update_post_meta( $id, '_mm_group', (string) ( $def['g'] ?? '' ) );
		if ( ! empty( $def['tool'] ) ) {
			update_post_meta( $id, '_mm_tool', $def['tool'] );
		}
		mm_builder_set_seo( (int) $id, (string) ( $def['st'] ?? '' ), (string) ( $def['sd'] ?? '' ), (string) ( $def['kw'] ?? '' ) );
		update_post_meta( $id, '_mm_build_hash', md5( (string) get_post_field( 'post_content', $id, 'raw' ) ) );
		update_post_meta( $id, '_mm_build_version', MM_VERSION );
		$result[ $key ] = array( (int) $id, $action );
	}
	if ( ! current_user_can( 'unfiltered_html' ) ) {
		kses_init_filters();
	}
	wp_defer_term_counting( false );
	update_option( 'mm_builder_ids', $ids, false );
	update_option( 'mm_builder_last', array( 'time' => time(), 'version' => MM_VERSION, 'count' => count( $result ) ), false );
	return $result;
}

function mm_builder_set_seo( int $id, string $title, string $desc, string $kw = '' ): void {
	update_post_meta( $id, '_mm_seo_title', $title );
	update_post_meta( $id, '_mm_seo_desc', $desc );
	update_post_meta( $id, '_mm_focus_kw', $kw );
	$plugins = array(
		'WPSEO_VERSION'     => array( '_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw' ),
		'RANK_MATH_VERSION' => array( 'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword' ),
		'AIOSEO_VERSION'    => array( '_aioseo_title', '_aioseo_description', '_aioseo_keyphrases' ),
		'SEOPRESS_VERSION'  => array( '_seopress_titles_title', '_seopress_titles_desc', '_seopress_analysis_target_kw' ),
	);
	foreach ( $plugins as $const => $keys ) {
		if ( defined( $const ) ) {
			if ( $kw && '' === (string) get_post_meta( $id, $keys[2], true ) && '_aioseo_keyphrases' !== $keys[2] ) {
				update_post_meta( $id, $keys[2], $kw );
			}
			if ( '' === (string) get_post_meta( $id, $keys[0], true ) && $title ) {
				update_post_meta( $id, $keys[0], $title . ' | ' . get_bloginfo( 'name' ) );
			}
			if ( '' === (string) get_post_meta( $id, $keys[1], true ) && $desc ) {
				update_post_meta( $id, $keys[1], $desc );
			}
		}
	}
}

/* ---------------------------------------------------------------------------
 * Front page, menus, retired pages
 * ------------------------------------------------------------------------- */

function mm_builder_front_page( int $id ): void {
	if ( ! $id ) {
		return;
	}
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $id );
	set_theme_mod( 'mm_home_mode', 'content' );
}

function mm_builder_menus( array $ids, bool $assign ): array {
	$url  = static fn( string $key ) => isset( $ids[ $key ] ) ? (string) get_permalink( $ids[ $key ] ) : home_url( '/' . mm_builder_path( $key ) . '/' );
	$page = static function ( string $key, string $label = '', string $desc = '', string $icon = '', array $kids = array(), string $class = '' ) use ( $url ) {
		$def = mm_builder_pages()[ $key ] ?? array();
		return array( 'title' => $label ? $label : ( $def['t'] ?? $key ), 'url' => $url( $key ), 'desc' => $desc, 'icon' => $icon ? $icon : ( $def['icon'] ?? '' ), 'kids' => $kids, 'class' => $class, 'object' => $key );
	};
	$link = static fn( string $title, string $u, string $desc = '', string $icon = '', array $kids = array(), string $class = '' ) => array( 'title' => $title, 'url' => $u, 'desc' => $desc, 'icon' => $icon, 'kids' => $kids, 'class' => $class );
	$tools = array();
	foreach ( mm_tools() as $tid => $t ) {
		$tools[] = $page( 'tool-' . $tid, $t['menu'], wp_trim_words( $t['desc'], 9 ), $t['icon'] );
	}
	$g     = static fn( string $group ) => array_values( array_filter( array_keys( mm_builder_pages() ), static fn( $k ) => 0 === strpos( $k, 'g-' ) && ( mm_builder_pages()[ $k ]['g'] ?? '' ) === $group ) );
	$learn = array();
	foreach ( array( array( __( 'Getting started & economics', 'minemirth' ), 'rocket' ), array( __( 'Setup & environment', 'minemirth' ), 'fan' ), array( __( 'Pools & operation', 'minemirth' ), 'users' ), array( __( 'Farms, buying & business', 'minemirth' ), 'container' ) ) as $grp ) {
		$kids = array();
		foreach ( array_slice( $g( $grp[0] ), 0, 4 ) as $k ) {
			$kids[] = $page( $k );
		}
		$learn[] = $link( $grp[0], $url( 'learn' ), '', $grp[1], $kids );
	}
	$shop = mm_shop_url();
	$menus = array(
		'primary'       => array(
			__( 'Main menu (Mine Mirth)', 'minemirth' ),
			array(
				$link(
					__( 'Shop', 'minemirth' ),
					$shop,
					'',
					'',
					array(
						$page( 'miners', __( 'By coin', 'minemirth' ), __( 'Bitcoin, Litecoin, Kaspa, Alephium', 'minemirth' ), 'bitcoin', array( $page( 'm-bitcoin' ), $page( 'm-scrypt' ), $page( 'm-kaspa' ), $page( 'm-alephium' ) ) ),
						$page( 'miners', __( 'By setup', 'minemirth' ), __( 'Home, solo, hydro and immersion', 'minemirth' ), 'home', array( $page( 'm-home' ), $page( 'm-solo' ), $page( 'm-hydro' ), $page( 'm-immersion' ) ) ),
						$page( 'miners', __( 'Buying guides', 'minemirth' ), __( 'Ranked live from our stock', 'minemirth' ), 'gauge', array( $page( 'm-efficient' ), $page( 'm-value' ), $page( 'm-new' ), $page( 'm-parts' ) ) ),
						$page( 'brands', __( 'Brands', 'minemirth' ), __( 'Every major manufacturer', 'minemirth' ), 'award', array( $page( 'brand-bitmain' ), $page( 'brand-microbt' ), $page( 'brand-canaan' ), $page( 'brand-goldshell' ), $page( 'brand-iceriver' ), $page( 'brand-elphapex' ) ) ),
					),
					'mm-mega'
				),
				$page( 'hosting', __( 'Hosting', 'minemirth' ) ),
				$link( __( 'Tools', 'minemirth' ), $url( 'tools' ), '', '', $tools, 'mm-mega' ),
				$link( __( 'Learn', 'minemirth' ), $url( 'learn' ), '', '', array_merge( $learn, array( $page( 'market', '', __( 'Live price, hashprice and difficulty', 'minemirth' ) ), $page( 'learn', __( 'All 24 guides', 'minemirth' ), __( 'From first miner to full farm', 'minemirth' ), 'book' ) ) ), 'mm-mega' ),
				$link(
					__( 'Company', 'minemirth' ),
					$url( 'about' ),
					'',
					'',
					array(
						$page( 'services', '', __( 'Everything after checkout', 'minemirth' ), 'settings', array( $page( 'setup' ), $page( 'repairs' ), $page( 'tradein' ), $page( 'wholesale' ), $page( 'consulting' ), $page( 'business' ) ) ),
						$page( 'about', __( 'About us', 'minemirth' ), __( 'Who we are and how we work', 'minemirth' ), 'users', array( $page( 'why' ), $page( 'reviews' ), $page( 'partners' ), $page( 'market' ) ) ),
						$page( 'contact', __( 'Help & contact', 'minemirth' ), __( 'Quotes, stock and support', 'minemirth' ), 'headset', array( $page( 'faq', __( 'FAQ', 'minemirth' ) ), $page( 'shipping' ), $page( 'warranty' ), $page( 'payments' ), $page( 'contact' ) ) ),
					),
					'mm-mega'
				),
			),
		),
		'footer-1'      => array( __( 'Footer: Shop (Mine Mirth)', 'minemirth' ), array( $link( __( 'All miners', 'minemirth' ), $shop ), $page( 'm-bitcoin' ), $page( 'm-scrypt' ), $page( 'm-kaspa' ), $page( 'm-home' ), $page( 'm-efficient' ), $page( 'brands' ), $page( 'm-parts' ) ) ),
		'footer-2'      => array( __( 'Footer: Tools & guides (Mine Mirth)', 'minemirth' ), array( $page( 'tool-profit', __( 'Profit calculator', 'minemirth' ) ), $page( 'tool-hashprice', __( 'Hashprice tracker', 'minemirth' ) ), $page( 'tool-halving', __( 'Halving countdown', 'minemirth' ) ), $page( 'tool-compare', __( 'Miner comparison', 'minemirth' ) ), $page( 'tools', __( 'All tools', 'minemirth' ) ), $page( 'market' ), $page( 'learn', __( 'Mining guides', 'minemirth' ) ), $page( 'g-glossary' ) ) ),
		'footer-3'      => array( __( 'Footer: Help (Mine Mirth)', 'minemirth' ), array( $page( 'contact' ), $page( 'faq', __( 'FAQ', 'minemirth' ) ), $page( 'hosting' ), $page( 'shipping' ), $page( 'warranty' ), $page( 'returns' ), $page( 'payments' ), $page( 'about', __( 'About us', 'minemirth' ) ) ) ),
		'footer-bottom' => array( __( 'Footer: Legal (Mine Mirth)', 'minemirth' ), array( $page( 'privacy' ), $page( 'terms' ), $page( 'returns', __( 'Refunds', 'minemirth' ) ) ) ),
	);
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$made      = array();
	foreach ( $menus as $loc => $menu ) {
		$obj = wp_get_nav_menu_object( $menu[0] );
		if ( $obj ) {
			foreach ( (array) wp_get_nav_menu_items( $obj->term_id, array( 'post_status' => 'any' ) ) as $item ) {
				wp_delete_post( $item->ID, true );
			}
			$menu_id = (int) $obj->term_id;
		} else {
			$menu_id = (int) wp_create_nav_menu( $menu[0] );
		}
		if ( ! $menu_id || is_wp_error( $menu_id ) ) {
			continue;
		}
		mm_builder_menu_items( $menu_id, $menu[1], 0 );
		$made[ $loc ] = $menu_id;
		if ( $assign ) {
			$locations[ $loc ] = $menu_id;
		}
	}
	if ( $assign ) {
		set_theme_mod( 'nav_menu_locations', $locations );
		set_theme_mod( 'mm_footer_col1_title', __( 'Shop', 'minemirth' ) );
		set_theme_mod( 'mm_footer_col2_title', __( 'Tools & guides', 'minemirth' ) );
		set_theme_mod( 'mm_footer_col3_title', __( 'Help', 'minemirth' ) );
	}
	return $made;
}

function mm_builder_menu_items( int $menu_id, array $items, int $parent ): void {
	$pos = 0;
	foreach ( $items as $it ) {
		// Icons only inside dropdowns; top-level items stay plain text.
		$classes = trim( ( $it['class'] ?? '' ) . ( $parent && ! empty( $it['icon'] ) ? ' icon-' . $it['icon'] : '' ) );
		$id      = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'       => $it['title'],
				'menu-item-url'         => $it['url'],
				'menu-item-type'        => 'custom',
				'menu-item-status'      => 'publish',
				'menu-item-parent-id'   => $parent,
				'menu-item-position'    => ++$pos,
				'menu-item-classes'     => $classes,
				'menu-item-description' => $it['desc'] ?? '',
			)
		);
		if ( $id && ! is_wp_error( $id ) && ! empty( $it['kids'] ) ) {
			mm_builder_menu_items( $menu_id, $it['kids'], (int) $id );
		}
	}
}

/**
 * Old tool pages (from the earlier tools plugins) and where they now point.
 */
function mm_builder_retired(): array {
	$map   = array(
		'minemirth_rig_optimizer' => 'tool-compare',
		'minemirth_market_scope'  => 'tool-hashprice',
		'minemirth_tools'         => 'tools',
	);
	$known = array(
		'asic-market-scope-long-term-view'                    => 'tool-hashprice',
		'mining-rig-optimizer-ai-driven-analysis-mine-mirth'  => 'tool-compare',
	);
	$ours  = array_map( 'intval', array_values( (array) get_option( 'mm_builder_ids', array() ) ) );
	$found = array();
	foreach ( get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'numberposts' => 200, 's' => '[minemirth_' ) ) as $p ) {
		if ( in_array( (int) $p->ID, $ours, true ) || get_post_meta( $p->ID, '_mm_page_key', true ) ) {
			continue;
		}
		foreach ( $map as $code => $target ) {
			if ( has_shortcode( $p->post_content, $code ) ) {
				$found[ $p->ID ] = array( $p, $target );
				break;
			}
		}
	}
	foreach ( $known as $slug => $target ) {
		$p = get_page_by_path( $slug );
		if ( $p && 'publish' === $p->post_status && ! isset( $found[ $p->ID ] ) && ! get_post_meta( $p->ID, '_mm_page_key', true ) ) {
			$found[ $p->ID ] = array( $p, $target );
		}
	}
	return $found;
}

function mm_builder_retire( array $found ): int {
	$redirects = (array) get_option( 'mm_redirects', array() );
	$n         = 0;
	foreach ( $found as $id => $row ) {
		$path               = trim( (string) wp_parse_url( get_permalink( $id ), PHP_URL_PATH ), '/' );
		$redirects[ $path ] = $row[1];
		wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
		++$n;
	}
	update_option( 'mm_redirects', $redirects, false );
	return $n;
}

// 301 from retired addresses to their replacement pages.
add_action(
	'template_redirect',
	static function () {
		if ( ! is_404() ) {
			return;
		}
		$map = (array) get_option( 'mm_redirects', array() );
		if ( ! $map ) {
			return;
		}
		$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', PHP_URL_PATH ), '/' );
		$home = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( $home && 0 === strpos( $path, $home . '/' ) ) {
			$path = substr( $path, strlen( $home ) + 1 );
		}
		if ( isset( $map[ $path ] ) ) {
			wp_safe_redirect( mm_page_url_by_key( (string) $map[ $path ] ), 301 );
			exit;
		}
	},
	1
);

/* ---------------------------------------------------------------------------
 * SEO title and description (only when no SEO plugin is active)
 * ------------------------------------------------------------------------- */

function mm_seo_plugin_active(): bool {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' );
}

add_filter(
	'pre_get_document_title',
	static function ( $title ) {
		if ( mm_seo_plugin_active() || ! is_singular( 'page' ) ) {
			return $title;
		}
		$t = (string) get_post_meta( (int) get_queried_object_id(), '_mm_seo_title', true );
		return '' !== $t ? $t . ' | ' . get_bloginfo( 'name' ) : $title;
	},
	20
);

add_action(
	'wp_head',
	static function () {
		if ( mm_seo_plugin_active() || ! is_singular( 'page' ) ) {
			return;
		}
		$id   = (int) get_queried_object_id();
		$desc = (string) get_post_meta( $id, '_mm_seo_desc', true );
		if ( '' === $desc ) {
			return;
		}
		$title = (string) get_post_meta( $id, '_mm_seo_title', true );
		$url   = (string) get_permalink( $id );
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
		echo '<meta property="og:type" content="website"><meta property="og:title" content="' . esc_attr( $title ? $title : get_the_title( $id ) ) . '"><meta property="og:description" content="' . esc_attr( $desc ) . '"><meta property="og:url" content="' . esc_url( $url ) . '"><meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '"><meta name="twitter:card" content="summary_large_image">' . "\n";
	},
	2
);
// WordPress prints its own canonical for singular pages; ours includes the same URL.
add_action(
	'wp',
	static function () {
		if ( ! mm_seo_plugin_active() && is_singular( 'page' ) && get_post_meta( (int) get_queried_object_id(), '_mm_seo_desc', true ) ) {
			remove_action( 'wp_head', 'rel_canonical' );
		}
	}
);

require_once __DIR__ . '/admin.php';
