<?php
/**
 * Structured data (JSON-LD).
 *
 * Organization + WebSite, Article, BreadcrumbList and FAQPage. Skipped
 * automatically when Yoast SEO or Rank Math is active (they own it).
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

function mm_schema_enabled(): bool {
	return (bool) mm_opt( 'seo_schema' ) && ! defined( 'WPSEO_VERSION' ) && ! defined( 'RANK_MATH_VERSION' ) && ! class_exists( 'SEOPress\\Core\\Kernel' );
}

/**
 * Collect FAQ pairs printed in the footer.
 */
function mm_schema_add_faq( array $pairs ): void {
	$GLOBALS['mm_schema_faq'] = array_merge( $GLOBALS['mm_schema_faq'] ?? array(), $pairs );
}

function mm_schema_output(): void {
	$graph = array();

	if ( mm_schema_enabled() ) {
		$logo_id = (int) get_theme_mod( 'custom_logo' );
		$org     = array(
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);
		if ( $logo_id ) {
			$org['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => wp_get_attachment_image_url( $logo_id, 'full' ),
			);
		}
		$same = array();
		foreach ( array_keys( mm_social_networks() ) as $network ) {
			if ( mm_opt( 'social_' . $network ) ) {
				$same[] = esc_url_raw( (string) mm_opt( 'social_' . $network ) );
			}
		}
		if ( $same ) {
			$org['sameAs'] = $same;
		}
		if ( mm_opt( 'contact_email' ) || mm_opt( 'contact_phone' ) ) {
			$org['contactPoint'] = array_filter(
				array(
					'@type'       => 'ContactPoint',
					'contactType' => 'customer support',
					'email'       => (string) mm_opt( 'contact_email' ),
					'telephone'   => (string) mm_opt( 'contact_phone' ),
				)
			);
		}

		if ( is_front_page() ) {
			$graph[] = $org;
			$graph[] = array(
				'@type'           => 'WebSite',
				'@id'             => home_url( '/#website' ),
				'url'             => home_url( '/' ),
				'name'            => get_bloginfo( 'name' ),
				'publisher'       => array( '@id' => home_url( '/#organization' ) ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => home_url( '/?s={search_term_string}' ),
					'query-input' => 'required name=search_term_string',
				),
			);
		}

		if ( is_singular( 'post' ) ) {
			$post    = get_queried_object();
			$article = array(
				'@type'            => 'Article',
				'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
				'datePublished'    => get_the_date( DATE_W3C, $post ),
				'dateModified'     => get_the_modified_date( DATE_W3C, $post ),
				'author'           => array(
					'@type' => 'Person',
					'name'  => get_the_author_meta( 'display_name', (int) $post->post_author ),
					'url'   => get_author_posts_url( (int) $post->post_author ),
				),
				'publisher'        => $org,
				'mainEntityOfPage' => get_permalink( $post ),
				'wordCount'        => str_word_count( wp_strip_all_tags( $post->post_content ) ),
			);
			if ( has_post_thumbnail( $post ) ) {
				$article['image'] = get_the_post_thumbnail_url( $post, 'full' );
			}
			$graph[] = $article;
		}

		$crumbs = function_exists( 'mm_breadcrumb_items' ) ? mm_breadcrumb_items() : array();
		if ( count( $crumbs ) > 1 ) {
			$list = array();
			foreach ( $crumbs as $i => $crumb ) {
				$item = array(
					'@type'    => 'ListItem',
					'position' => $i + 1,
					'name'     => wp_strip_all_tags( (string) $crumb[0] ),
				);
				if ( $crumb[1] ) {
					$item['item'] = $crumb[1];
				}
				$list[] = $item;
			}
			$graph[] = array(
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $list,
			);
		}
	}

	if ( ! empty( $GLOBALS['mm_schema_faq'] ) && mm_opt( 'seo_schema' ) ) {
		$faq = array();
		foreach ( $GLOBALS['mm_schema_faq'] as $pair ) {
			$faq[] = array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( $pair[0] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $pair[1] ),
				),
			);
		}
		$graph[] = array(
			'@type'      => 'FAQPage',
			'mainEntity' => $faq,
		);
	}

	if ( ! $graph ) {
		return;
	}
	echo '<script type="application/ld+json">' . wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	) . '</script>' . "\n";
}
add_action( 'wp_footer', 'mm_schema_output', 5 );
