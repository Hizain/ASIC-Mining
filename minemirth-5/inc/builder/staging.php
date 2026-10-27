<?php
/**
 * Test mode for the site builder (with the Mine Mirth Launch plugin's test drive).
 *
 * While you test-drive Mine Mirth 5, visitors still see your current theme. The builder then
 * works privately:
 *  - new pages are created as private pages (only you can see them);
 *  - changes to existing public pages are saved inside the page as a draft ("_mm_staged") and
 *    shown only to you, so the live page stays exactly as it is;
 *  - the new homepage is remembered and shown only to you.
 * "Go live" applies everything at once and activates Mine Mirth 5 for everyone.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/** True while an administrator test-drives Mine Mirth 5 and visitors see another theme. */
function mm_is_staging(): bool {
	return function_exists( 'mml_testdrive_active' ) && mml_testdrive_active();
}

/** The staged draft saved inside a live page, if any. */
function mm_staged( int $post_id ): ?array {
	if ( ! $post_id ) {
		return null;
	}
	$s = get_post_meta( $post_id, '_mm_staged', true );
	return is_array( $s ) && isset( $s['content'] ) ? $s : null;
}

/** The content this request should show: the staged draft in test mode, otherwise the page. */
function mm_page_raw_content( int $post_id = 0 ): string {
	$post_id = $post_id ? $post_id : (int) get_the_ID();
	if ( mm_is_staging() && ( $s = mm_staged( $post_id ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
		return (string) $s['content'];
	}
	return (string) get_post_field( 'post_content', $post_id, 'raw' );
}

if ( mm_is_staging() ) {
	// Show staged drafts instead of the live content.
	add_filter(
		'the_content',
		static function ( $content ) {
			$id = (int) get_the_ID();
			if ( $id && 'page' === get_post_type( $id ) && ( $s = mm_staged( $id ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
				return (string) $s['content'];
			}
			return $content;
		},
		0
	);
	add_filter(
		'the_title',
		static function ( $title, $id = 0 ) {
			$s = $id ? mm_staged( (int) $id ) : null;
			return $s && ! empty( $s['title'] ) ? (string) $s['title'] : $title;
		},
		10,
		2
	);
	add_filter(
		'get_the_excerpt',
		static function ( $excerpt, $post = null ) {
			$s = $post ? mm_staged( (int) $post->ID ) : null;
			return $s && isset( $s['excerpt'] ) && '' !== $s['excerpt'] ? (string) $s['excerpt'] : $excerpt;
		},
		10,
		2
	);
	// Template, tool and SEO fields come from the draft too.
	add_filter(
		'get_post_metadata',
		static function ( $value, $object_id, $meta_key, $single ) {
			static $map = array( '_wp_page_template' => 'template', '_mm_tool' => 'tool', '_mm_icon' => 'icon', '_mm_group' => 'group', '_mm_seo_title' => 'st', '_mm_seo_desc' => 'sd', '_mm_focus_kw' => 'kw' );
			if ( ! isset( $map[ $meta_key ] ) ) {
				return $value;
			}
			$s = mm_staged( (int) $object_id );
			if ( ! $s || ! isset( $s[ $map[ $meta_key ] ] ) ) {
				return $value;
			}
			return $single ? $s[ $map[ $meta_key ] ] : array( $s[ $map[ $meta_key ] ] );
		},
		10,
		4
	);
	// The new homepage, for you only.
	$mm_front = (int) get_option( 'mm_staged_front' );
	if ( $mm_front && get_post( $mm_front ) ) {
		add_filter( 'pre_option_show_on_front', static fn() => 'page' );
		add_filter( 'pre_option_page_on_front', static fn() => $mm_front );
	}
	// Private builder pages read as normal pages while you test.
	add_filter( 'private_title_format', static fn() => '%s' );

	// In the block editor, edit the draft, not the live page.
	add_filter(
		'rest_prepare_page',
		static function ( $response, $post ) {
			$s = mm_staged( (int) $post->ID );
			if ( $s && $response instanceof WP_REST_Response ) {
				$data = $response->get_data();
				if ( isset( $data['content'] ) && is_array( $data['content'] ) ) {
					$data['content']['raw']      = (string) $s['content'];
					$data['content']['rendered'] = apply_filters( 'the_content', (string) $s['content'] );
				}
				if ( isset( $data['title'] ) && is_array( $data['title'] ) && ! empty( $s['title'] ) ) {
					$data['title']['raw']      = (string) $s['title'];
					$data['title']['rendered'] = esc_html( (string) $s['title'] );
				}
				if ( isset( $data['template'] ) && ! empty( $s['template'] ) ) {
					$data['template'] = 'default' === $s['template'] ? '' : (string) $s['template'];
				}
				$response->set_data( $data );
			}
			return $response;
		},
		10,
		2
	);
	add_filter(
		'rest_pre_insert_page',
		static function ( $prepared, $request ) {
			$id = isset( $prepared->ID ) ? (int) $prepared->ID : 0;
			$s  = $id ? mm_staged( $id ) : null;
			if ( ! $s ) {
				return $prepared;
			}
			$live = get_post( $id );
			if ( isset( $prepared->post_content ) ) {
				$s['content']           = (string) $prepared->post_content;
				$prepared->post_content = $live->post_content;
			}
			if ( isset( $prepared->post_title ) ) {
				$s['title']           = (string) $prepared->post_title;
				$prepared->post_title = $live->post_title;
			}
			if ( isset( $request['template'] ) && '' !== (string) $request['template'] ) {
				$s['template'] = (string) $request['template'];
			}
			$s['edited'] = time();
			update_post_meta( $id, '_mm_staged', wp_slash( $s ) );
			return $prepared;
		},
		10,
		2
	);
}

/** Post statuses a builder lookup may use in this request. */
function mm_page_statuses(): array {
	return mm_is_staging() || current_user_can( 'read_private_pages' ) ? array( 'publish', 'private' ) : array( 'publish' );
}

/* ---------------------------------------------------------------------------
 * Go live
 * ------------------------------------------------------------------------- */

/**
 * Apply every staged draft, publish the private pages, set the homepage, retire the old tool
 * pages and activate Mine Mirth 5 for everyone. Returns a summary.
 */
function mm_builder_go_live( bool $retire = true ): array {
	do_action( 'mm_launch_before_go_live' ); // Mine Mirth Launch remembers every current address first.
	$sum = array( 'applied' => 0, 'published' => 0, 'retired' => 0, 'activated' => false );
	kses_remove_filters();
	$staged = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => '_mm_staged' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	foreach ( $staged as $p ) {
		$s = mm_staged( (int) $p->ID );
		if ( ! $s ) {
			continue;
		}
		if ( wp_revisions_enabled( $p ) && '' !== trim( $p->post_content ) ) {
			_wp_put_post_revision( $p );
		}
		$args = array( 'ID' => $p->ID, 'post_content' => (string) $s['content'] );
		if ( ! empty( $s['title'] ) ) {
			$args['post_title'] = (string) $s['title'];
		}
		if ( isset( $s['excerpt'] ) ) {
			$args['post_excerpt'] = (string) $s['excerpt'];
		}
		if ( isset( $s['parent'] ) && null !== $s['parent'] ) {
			$args['post_parent'] = (int) $s['parent'];
		}
		wp_update_post( wp_slash( $args ) );
		delete_post_meta( $p->ID, '_mm_staged' ); // Remove first so the fields below are the real ones.
		update_post_meta( $p->ID, '_wp_page_template', (string) ( $s['template'] ?? 'default' ) );
		foreach ( array( 'icon' => '_mm_icon', 'group' => '_mm_group', 'tool' => '_mm_tool' ) as $k => $meta ) {
			if ( isset( $s[ $k ] ) && '' !== (string) $s[ $k ] ) {
				update_post_meta( $p->ID, $meta, (string) $s[ $k ] );
			}
		}
		mm_builder_set_seo( (int) $p->ID, (string) ( $s['st'] ?? '' ), (string) ( $s['sd'] ?? '' ), (string) ( $s['kw'] ?? '' ) );
		update_post_meta( $p->ID, '_mm_build_hash', empty( $s['edited'] ) ? md5( (string) get_post_field( 'post_content', $p->ID, 'raw' ) ) : '' );
		++$sum['applied'];
	}
	foreach ( get_posts( array( 'post_type' => 'page', 'post_status' => 'private', 'numberposts' => -1, 'meta_key' => '_mm_build_version' ) ) as $p ) { // phpcs:ignore WordPress.DB.SlowDBQuery
		wp_update_post( array( 'ID' => $p->ID, 'post_status' => 'publish' ) );
		++$sum['published'];
	}
	if ( ! current_user_can( 'unfiltered_html' ) ) {
		kses_init_filters();
	}
	$front = (int) get_option( 'mm_staged_front' );
	if ( $front ) {
		remove_all_filters( 'pre_option_show_on_front' );
		remove_all_filters( 'pre_option_page_on_front' );
		mm_builder_front_page( $front );
		delete_option( 'mm_staged_front' );
	}
	if ( $retire ) {
		$sum['retired'] = mm_builder_retire( mm_builder_retired() );
	}
	$live = function_exists( 'mml_live_stylesheet' ) ? mml_live_stylesheet() : (string) get_option( 'stylesheet' );
	$mine = get_stylesheet();
	if ( $live !== $mine ) {
		// The test-drive filters make WordPress think this theme is already active; remove them so
		// the switch is really saved.
		remove_all_filters( 'pre_option_stylesheet' );
		remove_all_filters( 'pre_option_template' );
		switch_theme( $mine );
		$sum['activated'] = true;
	}
	update_option( 'mm_went_live', time(), false );
	do_action( 'mm_launch_after_go_live', $sum );
	if ( function_exists( 'mm_catalog_flush' ) ) {
		mm_catalog_flush();
	}
	flush_rewrite_rules( false );
	do_action( 'litespeed_purge_all' );
	return $sum;
}
