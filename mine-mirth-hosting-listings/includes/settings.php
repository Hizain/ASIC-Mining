<?php
/**
 * Settings: stored in one option, read with mmhl_opt().
 *
 * @package MineMirthHostingListings
 */

defined( 'ABSPATH' ) || exit;

function mmhl_defaults(): array {
	return array(
		// Hosting page.
		'page'         => 0,
		'takeover'     => 1,
		'eyebrow'      => __( 'Miner hosting', 'mine-mirth-hosting-listings' ),
		'title'        => __( 'Hosting *listings*', 'mine-mirth-hosting-listings' ),
		'intro'        => __( 'Browse our hosting listings and open any of them for the full details. Subscribe online in a few clicks, or ask us to reserve space for your miners.', 'mine-mirth-hosting-listings' ),
		'btn_text'     => '',
		'btn_url'      => '',
		'search'       => 1,
		'filters'      => 1,
		'sort'         => 1,
		'columns'      => 3,
		'per_page'     => 12,
		'new_tab'      => 1,
		'show_summary' => 1,
		'show_specs'   => 1,
		'show_price'   => 1,
		'show_rate'    => 1,
		'show_booked'  => 1,
		'below'        => '',
		'page_content' => 0,
		'everywhere'   => 1,
		// Listing pages.
		'slug'         => 'miner-hosting',
		'related'      => 1,
		'help_text'    => __( 'Questions about this listing?', 'mine-mirth-hosting-listings' ),
		'help_label'   => __( 'Message us', 'mine-mirth-hosting-listings' ),
		'help_url'     => '',
		// "Host this miner" on product pages.
		'host_mode'    => 'page',
		'host_listing' => 0,
		'host_url'     => '',
		'host_auto'    => 1,
		'host_new_tab' => 0,
		'host_label'   => '',
		'host_text'    => '',
	);
}

function mmhl_settings( bool $fresh = false ): array {
	static $s = null;
	if ( null === $s || $fresh ) {
		$s = wp_parse_args( (array) get_option( 'mmhl_settings', array() ), mmhl_defaults() );
	}
	return $s;
}

function mmhl_opt( string $key ) {
	$s = mmhl_settings();
	return $s[ $key ] ?? null;
}

/** Theme option, when the Mine Mirth theme is active. */
function mmhl_theme_opt( string $key, $default = '' ) {
	return function_exists( 'mm_opt' ) ? mm_opt( $key ) : $default;
}

/** Turn a theme-style URL ("/contact-us/", "{shop}") into a full URL. */
function mmhl_url( string $url ): string {
	$url = trim( $url );
	if ( '' === $url ) {
		return '';
	}
	return function_exists( 'mm_url' ) ? (string) mm_url( $url ) : ( str_starts_with( $url, '/' ) ? home_url( $url ) : $url );
}

/** The page the listings take over (the miner hosting page). */
function mmhl_page_id(): int {
	$id = (int) mmhl_opt( 'page' );
	if ( $id && 'page' === get_post_type( $id ) ) {
		return $id;
	}
	if ( function_exists( 'mms_plans_page_id' ) && mms_plans_page_id() ) {
		return (int) mms_plans_page_id();
	}
	$ids = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'meta_key' => '_mm_page_key', 'meta_value' => 'hosting', 'fields' => 'ids', 'numberposts' => 1 ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( $ids ) {
		return (int) $ids[0];
	}
	$page = get_page_by_path( 'miner-hosting' );
	return $page ? (int) $page->ID : 0;
}

function mmhl_page_url(): string {
	$id = mmhl_page_id();
	return $id ? (string) get_permalink( $id ) : home_url( '/miner-hosting/' );
}

/** URL base of the listing pages: /miner-hosting/{listing}/ by default. */
function mmhl_slug(): string {
	$slug = trim( (string) mmhl_opt( 'slug' ), '/' );
	$slug = implode( '/', array_filter( array_map( 'sanitize_title', explode( '/', $slug ) ) ) );
	return '' !== $slug ? $slug : 'miner-hosting';
}

function mmhl_contact_url(): string {
	$url = mmhl_url( (string) mmhl_opt( 'help_url' ) );
	if ( '' === $url ) {
		$url = mmhl_url( (string) mmhl_theme_opt( 'contact_url', '/contact-us/' ) );
	}
	return '' !== $url ? $url : home_url( '/contact-us/' );
}
