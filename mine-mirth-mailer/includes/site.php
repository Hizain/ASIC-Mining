<?php
/**
 * What the Mine Mirth 5 website offers, for use in emails: its site-builder pages, the twelve live
 * tools and the guides. Everything falls back gracefully on older versions of the theme.
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Published page the theme's site builder created for a key (for example "hosting", "tools",
 * "tool-profit" or "g-beginners"), or an empty string.
 */
function mmm_builder_page( string $key ): string {
	static $cache = array();
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$ids = get_posts(
		array(
			'post_type'        => 'page',
			'post_status'      => 'publish',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'meta_key'         => '_mm_page_key', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'       => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
			'suppress_filters' => true,
		)
	);
	return $cache[ $key ] = $ids ? (string) get_permalink( (int) $ids[0] ) : '';
}

/**
 * The live tools: id => title, short description, icon and link.
 *
 * @return array<string,array{title:string,desc:string,icon:string,url:string}>
 */
function mmm_tools_list(): array {
	static $list = null;
	if ( null !== $list ) {
		return $list;
	}
	$list  = array();
	$icons = array( 'profit' => '📈', 'hashprice' => '⚡', 'breakeven' => '🔌', 'compare' => '⚖️', 'roi' => '💰', 'halving' => '⏳', 'difficulty' => '📊', 'hosting' => '🏭', 'power' => '🔋', 'upgrade' => '🔄', 'solo' => '🎲', 'altcoin' => '🪙' );
	if ( function_exists( 'mm_tools' ) ) {
		foreach ( mm_tools() as $id => $t ) {
			$url = mmm_builder_page( 'tool-' . $id );
			if ( ! $url && function_exists( 'mm_tool_url' ) ) {
				$url = (string) mm_tool_url( $id );
			}
			$list[ $id ] = array(
				'title' => (string) ( $t['title'] ?? $id ),
				'desc'  => (string) ( $t['desc'] ?? '' ),
				'icon'  => $icons[ $id ] ?? '🧮',
				'url'   => $url ? $url : home_url( '/' ),
			);
		}
	}
	return $list;
}

/**
 * Link to one tool, or the best calculator page this site has.
 */
function mmm_tool_link( string $id ): string {
	$tools = mmm_tools_list();
	return isset( $tools[ $id ] ) ? $tools[ $id ]['url'] : mmm_page_url( 'calculator' );
}

/**
 * Tool cards (two per row), with today's numbers in the text where they help.
 *
 * @param string[] $ids Tool ids in the order to show.
 */
function mmm_c_tools( array $ids, string $title = '', string $eyebrow = '' ): string {
	$tools = mmm_tools_list();
	$items = array();
	foreach ( $ids as $id ) {
		if ( ! isset( $tools[ $id ] ) ) {
			continue;
		}
		$items[] = array(
			'icon'  => $tools[ $id ]['icon'],
			'title' => $tools[ $id ]['title'],
			'text'  => $tools[ $id ]['desc'],
			'link'  => __( 'Open the tool', 'mine-mirth-mailer' ),
			'url'   => $tools[ $id ]['url'],
		);
	}
	if ( ! $items ) {
		// Older theme without the tool set: one calculator card.
		$items[] = array( 'icon' => '📈', 'title' => __( 'Profitability calculator', 'mine-mirth-mailer' ), 'text' => __( 'Profit, power cost and payback for any miner at today’s Bitcoin price and difficulty.', 'mine-mirth-mailer' ), 'link' => __( 'Open the calculator', 'mine-mirth-mailer' ), 'url' => mmm_page_url( 'calculator' ) );
	}
	return ( $title ? mmm_c_heading( $title, 2, $eyebrow ) : '' ) . mmm_c_services( $items );
}

/**
 * Guide pages from the site builder, in the order given, skipping ones already read; topped up with
 * the newest blog posts when there are too few.
 *
 * @param string[] $keys    Builder keys to prefer (for example g-beginners).
 * @param int[]    $exclude Post or page IDs to leave out.
 * @return int[]
 */
function mmm_guide_ids( int $count, array $keys = array(), array $exclude = array() ): array {
	$count = max( 1, min( 6, $count ) );
	$out   = array();
	if ( ! $keys ) {
		$keys = array( 'g-beginners', 'g-choose', 'g-profit', 'g-hashprice', 'g-difficulty', 'g-halving', 'g-home', 'g-hosting', 'g-power', 'g-cooling' );
	}
	foreach ( $keys as $key ) {
		if ( count( $out ) >= $count ) {
			break;
		}
		$ids = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => 'publish',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'meta_key'         => '_mm_page_key', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'       => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
				'suppress_filters' => true,
			)
		);
		if ( $ids && ! in_array( (int) $ids[0], $exclude, true ) && ! in_array( (int) $ids[0], $out, true ) ) {
			$out[] = (int) $ids[0];
		}
	}
	if ( count( $out ) < $count ) {
		$out = array_merge( $out, mmm_post_ids( 'latest', $count - count( $out ), '', $exclude ) );
	}
	return array_slice( $out, 0, $count );
}

/**
 * Best-selling miners in stock (not power supplies or parts), from the theme's catalog when it has
 * one; otherwise the shop's best sellers.
 *
 * @return int[]
 */
function mmm_popular_miner_ids( int $count ): array {
	$ids = mmm_product_ids( 'popular', $count );
	return $ids ? $ids : mmm_product_ids( 'latest', $count );
}

/**
 * Days until the next halving, from the theme's market cache (0 when unknown).
 */
function mmm_halving_days(): int {
	if ( ! function_exists( 'mm_market_cached' ) ) {
		return 0;
	}
	$m      = mm_market_cached();
	$blocks = (int) ( $m['halving']['blocks_left'] ?? 0 );
	return $blocks > 0 ? (int) round( $blocks * 10 / 1440 ) : 0;
}

/**
 * Tool id of a tool page (for the visit history), or an empty string.
 */
function mmm_page_tool( int $page_id ): string {
	$tool = (string) get_post_meta( $page_id, '_mm_tool', true );
	if ( '' !== $tool ) {
		return sanitize_key( $tool );
	}
	$key = (string) get_post_meta( $page_id, '_mm_page_key', true );
	return str_starts_with( $key, 'tool-' ) ? sanitize_key( substr( $key, 5 ) ) : '';
}
