<?php
/**
 * The hosting page and the listing pages: templates, assets and structured data.
 *
 * @package MineMirthHostingListings
 */

defined( 'ABSPATH' ) || exit;

function mmhl_is_hosting_page(): bool {
	return mmhl_opt( 'takeover' ) && mmhl_page_id() && is_page( mmhl_page_id() );
}

add_filter(
	'template_include',
	static function ( string $template ): string {
		if ( is_singular( MMHL_TYPE ) ) {
			return MMHL_DIR . 'templates/single-listing.php';
		}
		if ( mmhl_is_hosting_page() ) {
			return MMHL_DIR . 'templates/hosting-page.php';
		}
		return $template;
	},
	99
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_register_style( 'mmhl', MMHL_URI . 'assets/listings.css', array(), MMHL_VERSION );
		wp_register_script( 'mmhl', MMHL_URI . 'assets/listings.js', array(), MMHL_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		if ( is_singular( MMHL_TYPE ) || mmhl_is_hosting_page() ) {
			wp_enqueue_style( 'mmhl' );
			wp_enqueue_script( 'mmhl' );
		}
		if ( is_singular( MMHL_TYPE ) && wp_style_is( 'mms-front', 'registered' ) ) {
			wp_enqueue_style( 'mms-front' );
			wp_enqueue_script( 'mms-front' );
		}
	},
	20
);

add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( is_singular( MMHL_TYPE ) ) {
			$classes[] = 'mmhl-single-page';
		} elseif ( mmhl_is_hosting_page() ) {
			$classes[] = 'mmhl-hosting-page';
		}
		return $classes;
	}
);

/** Breadcrumbs in the theme's style: Home / Hosting page / Listing. */
function mmhl_breadcrumbs( string $current ): string {
	$items = array( array( __( 'Home', 'mine-mirth-hosting-listings' ), home_url( '/' ) ) );
	if ( mmhl_page_id() ) {
		$items[] = array( get_the_title( mmhl_page_id() ), mmhl_page_url() );
	}
	$out = '<nav class="mm-breadcrumbs mmhl-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'mine-mirth-hosting-listings' ) . '"><ol>';
	foreach ( $items as $i ) {
		$out .= '<li><a href="' . esc_url( $i[1] ) . '">' . esc_html( $i[0] ) . '</a></li>';
	}
	return $out . '<li><span aria-current="page">' . esc_html( wp_trim_words( $current, 8 ) ) . '</span></li></ol></nav>';
}

/** Short description for search engines on listing pages, unless an SEO plugin writes one. */
add_action(
	'wp_head',
	static function (): void {
		if ( ! is_singular( MMHL_TYPE ) || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) ) {
			return;
		}
		$l = mmhl_listing( get_queried_object_id() );
		if ( $l && '' !== $l['summary'] ) {
			echo '<meta name="description" content="' . esc_attr( wp_trim_words( $l['summary'], 30 ) ) . '">' . "\n";
		}
	},
	2
);

/** Structured data for a listing page (a service with its monthly price). */
function mmhl_schema( array $l ): string {
	$data = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Product',
		'name'        => $l['title'],
		'description' => wp_strip_all_tags( $l['summary'] ),
		'url'         => $l['url'],
		'brand'       => array( '@type' => 'Brand', 'name' => get_bloginfo( 'name' ) ),
	);
	if ( $l['image_id'] ) {
		$data['image'] = (string) wp_get_attachment_image_url( $l['image_id'], 'large' );
	}
	if ( $l['plan'] ) {
		$zero  = function_exists( 'mms_zero_decimal' ) && mms_zero_decimal( (string) $l['plan']['currency'] );
		$price = $zero ? (int) $l['plan']['amount'] : $l['plan']['amount'] / 100;
		$data['offers'] = array(
			'@type'         => 'Offer',
			'price'         => number_format( (float) $price, $zero ? 0 : 2, '.', '' ),
			'priceCurrency' => strtoupper( (string) $l['plan']['currency'] ),
			'availability'  => 'unavailable' === $l['availability'] ? 'https://schema.org/OutOfStock' : ( 'limited' === $l['availability'] ? 'https://schema.org/LimitedAvailability' : 'https://schema.org/InStock' ),
			'url'           => $l['url'],
		);
	}
	return '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
}
