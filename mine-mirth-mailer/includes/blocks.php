<?php
/**
 * Campaign editor blocks, and turning block content into email HTML.
 *
 * Campaigns are written in the normal WordPress editor. Standard blocks
 * (paragraph, heading, list, image, buttons, quote, columns, separator,
 * spacer) are converted to email-safe markup, and six email blocks are added:
 * Hero, Products, Articles, Bitcoin network, Call to action and Ask us anything.
 *
 * @package MineMirthMailer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product IDs from a source, newest first, skipping hidden products.
 *
 * @return int[]
 */
function mmm_product_ids( string $source, int $count, string $category = '', string $ids = '', array $exclude = array() ): array {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	$count = max( 1, min( 9, $count ) );
	if ( 'ids' === $source ) {
		return array_slice( array_values( array_diff( array_filter( array_map( 'absint', explode( ',', $ids ) ) ), $exclude ) ), 0, $count );
	}
	$args = array(
		'status'     => 'publish',
		'visibility' => 'catalog',
		'limit'      => $count,
		'return'     => 'ids',
		'exclude'    => $exclude,
		'orderby'    => 'date',
		'order'      => 'DESC',
		// Picked lists only promote what can be bought: in stock or pre-order.
		'stock_status' => array( 'instock', 'onbackorder' ),
	);
	if ( 'sale' === $source ) {
		$args['include'] = array_values( array_diff( wc_get_product_ids_on_sale(), $exclude ) );
		if ( ! $args['include'] ) {
			return array();
		}
	} elseif ( 'featured' === $source ) {
		$args['featured'] = true;
	} elseif ( 'category' === $source && $category ) {
		$args['category'] = array( sanitize_title( $category ) );
	} elseif ( 'popular' === $source ) {
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	return array_map( 'intval', wc_get_products( $args ) );
}

/**
 * Post IDs from a source.
 *
 * @return int[]
 */
function mmm_post_ids( string $source, int $count, string $category = '', array $exclude = array() ): array {
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, min( 8, $count ) ),
		'fields'              => 'ids',
		'ignore_sticky_posts' => true,
		'post__not_in'        => $exclude,
	);
	if ( 'category' === $source && $category ) {
		$args['category_name'] = sanitize_title( $category );
	}
	return array_map( 'intval', get_posts( $args ) );
}

/* ---------------------------------------------------------------------------
 * Email blocks (editor)
 * ------------------------------------------------------------------------- */

function mmm_block_defs(): array {
	$s = array( 'type' => 'string', 'default' => '' );
	return array(
		'hero'     => array(
			'attributes' => array( 'eyebrow' => $s, 'title' => array( 'type' => 'string', 'default' => 'This week at Mine Mirth' ), 'text' => $s, 'buttonText' => $s, 'buttonUrl' => $s, 'imageId' => array( 'type' => 'number', 'default' => 0 ) ),
			'render'     => static function ( $a ) {
				$img = $a['imageId'] ? (string) wp_get_attachment_image_url( (int) $a['imageId'], 'large' ) : '';
				return mmm_c_hero( (string) $a['eyebrow'], (string) $a['title'], (string) $a['text'], (string) $a['buttonText'], (string) $a['buttonUrl'], $img );
			},
		),
		'products' => array(
			'attributes' => array( 'title' => array( 'type' => 'string', 'default' => 'Miners worth a look' ), 'eyebrow' => $s, 'source' => array( 'type' => 'string', 'default' => 'latest' ), 'category' => $s, 'ids' => $s, 'count' => array( 'type' => 'number', 'default' => 4 ), 'columns' => array( 'type' => 'number', 'default' => 2 ) ),
			'render'     => static fn( $a ) => mmm_c_products( mmm_product_ids( (string) $a['source'], (int) $a['count'], (string) $a['category'], (string) $a['ids'] ), (string) $a['title'], (string) $a['eyebrow'], (int) $a['columns'] ),
		),
		'posts'    => array(
			'attributes' => array( 'title' => array( 'type' => 'string', 'default' => 'From the blog' ), 'eyebrow' => $s, 'source' => array( 'type' => 'string', 'default' => 'latest' ), 'category' => $s, 'count' => array( 'type' => 'number', 'default' => 3 ) ),
			'render'     => static fn( $a ) => mmm_c_posts( mmm_post_ids( (string) $a['source'], (int) $a['count'], (string) $a['category'] ), (string) $a['title'], (string) $a['eyebrow'] ),
		),
		'network'  => array(
			'attributes' => array(),
			'render'     => static fn() => mmm_c_network(),
		),
		'cta'      => array(
			'attributes' => array( 'eyebrow' => $s, 'title' => array( 'type' => 'string', 'default' => 'Host your miners with us' ), 'text' => $s, 'buttonText' => array( 'type' => 'string', 'default' => 'Explore hosting' ), 'buttonUrl' => $s ),
			'render'     => static fn( $a ) => mmm_c_cta( (string) $a['title'], (string) $a['text'], (string) $a['buttonText'], (string) ( $a['buttonUrl'] ? $a['buttonUrl'] : home_url( '/' ) ), (string) $a['eyebrow'] ),
		),
		'reply'    => array(
			'attributes' => array( 'title' => $s, 'text' => $s, 'buttonText' => $s, 'topic' => $s ),
			'render'     => static fn( $a ) => mmm_c_reply( (string) $a['title'], (string) $a['text'], (string) $a['buttonText'], (string) $a['topic'] ),
		),
	);
}

add_action(
	'init',
	static function () {
		wp_register_script( 'mmm-blocks', MMM_URI . 'assets/blocks.js', array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ), MMM_VERSION, true );
		foreach ( mmm_block_defs() as $name => $def ) {
			register_block_type(
				'mmm/' . $name,
				array(
					'api_version'     => 2,
					'attributes'      => $def['attributes'],
					'render_callback' => static function ( $attrs ) use ( $def ) {
						return (string) call_user_func( $def['render'], $attrs );
					},
				)
			);
		}
	}
);

// Email blocks only appear while editing a campaign.
add_action(
	'enqueue_block_editor_assets',
	static function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'mmm_campaign' !== $screen->post_type ) {
			return;
		}
		wp_enqueue_script( 'mmm-blocks' );
		$d = mmm_design();
		wp_add_inline_style(
			'wp-edit-blocks',
			'.post-type-mmm_campaign .editor-styles-wrapper{background:' . $d['page'] . '!important}' .
			'.post-type-mmm_campaign .editor-styles-wrapper .is-root-container{max-width:600px;margin:24px auto!important;background:#fff;padding:36px 32px!important;border-radius:20px;box-shadow:0 10px 40px -20px rgba(5,11,20,.35);font-family:' . $d['fb'] . ';color:#2b3a4e}' .
			'.post-type-mmm_campaign .editor-styles-wrapper .is-root-container > *{max-width:100%!important}' .
			'.post-type-mmm_campaign .editor-styles-wrapper h1,.post-type-mmm_campaign .editor-styles-wrapper h2,.post-type-mmm_campaign .editor-styles-wrapper h3{font-family:' . $d['fh'] . ';color:' . $d['text'] . '}'
		);
	}
);

add_filter(
	'block_categories_all',
	static function ( $cats, $context ) {
		if ( isset( $context->post ) && $context->post instanceof WP_Post && 'mmm_campaign' === $context->post->post_type ) {
			array_unshift( $cats, array( 'slug' => 'mmm', 'title' => __( 'Email blocks', 'mine-mirth-mailer' ) ) );
		}
		return $cats;
	},
	10,
	2
);

add_filter(
	'allowed_block_types_all',
	static function ( $allowed, $context ) {
		if ( ! isset( $context->post ) || ! $context->post instanceof WP_Post || 'mmm_campaign' !== $context->post->post_type ) {
			return $allowed;
		}
		$list = array( 'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/image', 'core/buttons', 'core/button', 'core/separator', 'core/spacer', 'core/quote', 'core/columns', 'core/column', 'core/group', 'core/html' );
		foreach ( array_keys( mmm_block_defs() ) as $name ) {
			$list[] = 'mmm/' . $name;
		}
		return $list;
	},
	10,
	2
);

/* ---------------------------------------------------------------------------
 * Block content → email HTML
 * ------------------------------------------------------------------------- */

function mmm_render_blocks( string $content ): string {
	$out = '';
	foreach ( parse_blocks( $content ) as $block ) {
		$out .= mmm_render_block( $block );
	}
	return $out;
}

function mmm_strip_wrapper( string $html, string $tag ): string {
	$html = trim( $html );
	$html = (string) preg_replace( '#^<' . $tag . '\b[^>]*>#i', '', $html );
	return trim( (string) preg_replace( '#</' . $tag . '>$#i', '', $html ) );
}

function mmm_block_align( array $attrs ): string {
	$align = (string) ( $attrs['textAlign'] ?? ( $attrs['align'] ?? 'left' ) );
	return in_array( $align, array( 'left', 'center', 'right' ), true ) ? $align : 'left';
}

function mmm_render_block( array $b ): string {
	$name  = (string) ( $b['blockName'] ?? '' );
	$attrs = (array) ( $b['attrs'] ?? array() );
	$html  = (string) ( $b['innerHTML'] ?? '' );
	$inner = (array) ( $b['innerBlocks'] ?? array() );

	if ( str_starts_with( $name, 'mmm/' ) ) {
		$defs = mmm_block_defs();
		$key  = substr( $name, 4 );
		if ( ! isset( $defs[ $key ] ) ) {
			return '';
		}
		$full = array();
		foreach ( $defs[ $key ]['attributes'] as $k => $spec ) {
			$full[ $k ] = $attrs[ $k ] ?? $spec['default'];
		}
		return (string) call_user_func( $defs[ $key ]['render'], $full );
	}

	switch ( $name ) {
		case '':
			$text = trim( $html );
			return '' !== $text ? mmm_c_text( wpautop( $text ) ) : '';

		case 'core/paragraph':
			$text = mmm_strip_wrapper( $html, 'p' );
			return '' !== $text ? mmm_c_text( $text, mmm_block_align( $attrs ) ) : '';

		case 'core/heading':
			$level = (int) ( $attrs['level'] ?? 2 );
			return mmm_c_heading( mmm_strip_wrapper( $html, 'h' . $level ), $level, '', mmm_block_align( $attrs ) );

		case 'core/list':
			$items = array();
			if ( $inner ) {
				foreach ( $inner as $li ) {
					$items[] = mmm_strip_wrapper( (string) $li['innerHTML'], 'li' );
				}
			} elseif ( preg_match_all( '#<li[^>]*>(.*?)</li>#is', $html, $m ) ) {
				$items = $m[1];
			}
			return $items ? mmm_c_list( $items, ! empty( $attrs['ordered'] ) ) : '';

		case 'core/image':
			$src = ! empty( $attrs['id'] ) ? (string) wp_get_attachment_image_url( (int) $attrs['id'], 'large' ) : '';
			if ( ! $src && preg_match( '#<img[^>]+src="([^"]+)"#i', $html, $m ) ) {
				$src = html_entity_decode( $m[1] );
			}
			$alt  = preg_match( '#alt="([^"]*)"#i', $html, $m ) ? html_entity_decode( $m[1] ) : '';
			$link = preg_match( '#<a[^>]+href="([^"]+)"#i', $html, $m ) ? html_entity_decode( $m[1] ) : '';
			$cap  = preg_match( '#<figcaption[^>]*>(.*?)</figcaption>#is', $html, $m ) ? wp_strip_all_tags( $m[1] ) : '';
			return mmm_c_image( $src, $alt, $link, $cap );

		case 'core/buttons':
			$cells = '';
			foreach ( $inner as $btn ) {
				$bh = (string) $btn['innerHTML'];
				if ( ! preg_match( '#<a[^>]*href="([^"]*)"[^>]*>(.*?)</a>#is', $bh, $m ) ) {
					continue;
				}
				$style  = str_contains( (string) ( $btn['attrs']['className'] ?? '' ), 'is-style-outline' ) ? 'ghost' : 'primary';
				$cells .= '<td style="padding:0 10px 10px 0">' . mmm_c_button( wp_strip_all_tags( $m[2] ), html_entity_decode( $m[1] ), $style ) . '</td>';
			}
			if ( ! $cells ) {
				return '';
			}
			$justify = (string) ( $attrs['layout']['justifyContent'] ?? 'left' );
			return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="' . ( 'center' === $justify ? 'center' : 'left' ) . '" style="margin:4px 0 14px' . ( 'center' === $justify ? ';margin-left:auto;margin-right:auto' : '' ) . '"><tr>' . $cells . '</tr></table>' . mmm_c_spacer( 1 );

		case 'core/separator':
			return mmm_c_divider();

		case 'core/spacer':
			return mmm_c_spacer( max( 8, min( 120, (int) ( $attrs['height'] ?? 32 ) ) ) );

		case 'core/quote':
			$text = '';
			foreach ( $inner as $p ) {
				$text .= mmm_strip_wrapper( (string) $p['innerHTML'], 'p' ) . '<br>';
			}
			if ( ! $text && preg_match_all( '#<p[^>]*>(.*?)</p>#is', $html, $m ) ) {
				$text = implode( '<br>', $m[1] );
			}
			$cite = preg_match( '#<cite[^>]*>(.*?)</cite>#is', $html, $m ) ? wp_strip_all_tags( $m[1] ) : '';
			return mmm_c_quote( (string) preg_replace( '#(<br>)+$#', '', $text ), $cite );

		case 'core/columns':
			$cols  = array_values( array_filter( $inner, static fn( $c ) => 'core/column' === ( $c['blockName'] ?? '' ) ) );
			$n     = max( 1, count( $cols ) );
			$width = (int) floor( 100 / $n );
			$row   = '';
			foreach ( $cols as $i => $col ) {
				$pad  = 0 === $i ? '0 10px 0 0' : ( $i === $n - 1 ? '0 0 0 10px' : '0 5px' );
				$row .= '<td class="mmm-col" valign="top" width="' . $width . '%" style="padding:' . $pad . '">';
				foreach ( (array) $col['innerBlocks'] as $child ) {
					$row .= mmm_render_block( $child );
				}
				$row .= '</td>';
			}
			return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>' . $row . '</tr></table>';

		case 'core/group':
		case 'core/column':
			$out = '';
			foreach ( $inner as $child ) {
				$out .= mmm_render_block( $child );
			}
			return $out;

		case 'core/html':
			return $html;

		default:
			$rendered = trim( wp_strip_all_tags( render_block( $b ), true ) );
			return '' !== $rendered ? mmm_c_text( esc_html( $rendered ) ) : '';
	}
}
