<?php
/**
 * Page rebuilder.
 *
 * Reads the site's own /sitemap_index.xml (Yoast, Rank Math or core
 * wp-sitemap.xml as fallback), lists every page, and rebuilds each one as a
 * stack of section shortcodes. Rebuilds are saved as drafts first; applying a
 * draft copies it onto the original page, and WordPress keeps the old version
 * as a revision.
 *
 * @package MineMirthCommerce
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	static function () {
		add_menu_page( __( 'Mine Mirth Commerce', 'mine-mirth-commerce' ), __( 'MM Commerce', 'mine-mirth-commerce' ), 'manage_options', 'mmc', 'mmc_rebuilder_page', 'dashicons-hammer', 58 );
		add_submenu_page( 'mmc', __( 'Page rebuilder', 'mine-mirth-commerce' ), __( 'Page rebuilder', 'mine-mirth-commerce' ), 'manage_options', 'mmc', 'mmc_rebuilder_page' );
	}
);

/**
 * URLs listed in the site's sitemaps.
 *
 * @return string[]
 */
function mmc_sitemap_urls(): array {
	// Yoast and Rank Math use sitemap_index.xml; WordPress core uses wp-sitemap.xml.
	foreach ( array( '/sitemap_index.xml', '/wp-sitemap.xml' ) as $start ) {
		$queue = array( home_url( $start ) );
		$seen  = array();
		$urls  = array();
		while ( $queue && count( $seen ) < 40 ) {
			$map = array_shift( $queue );
			if ( isset( $seen[ $map ] ) ) {
				continue;
			}
			$seen[ $map ] = true;
			$res          = wp_remote_get( $map, array( 'timeout' => 15, 'redirection' => 3 ) );
			if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
				continue;
			}
			$body = (string) wp_remote_retrieve_body( $res );
			if ( ! preg_match_all( '#<loc>\s*(?:<!\[CDATA\[)?\s*([^<\]\s]+)#i', $body, $m ) ) {
				continue;
			}
			$is_index = false !== stripos( $body, '<sitemapindex' );
			foreach ( $m[1] as $loc ) {
				$loc = html_entity_decode( trim( $loc ) );
				if ( ! $is_index ) {
					$urls[] = $loc;
				} elseif ( preg_match( '#page#i', (string) wp_parse_url( $loc, PHP_URL_PATH ) ) ) {
					// Only page sitemaps; posts and products are not rebuilt.
					$queue[] = $loc;
				}
			}
		}
		if ( $urls ) {
			return array_values( array_unique( $urls ) );
		}
	}
	return array();
}

/**
 * Pages to offer for rebuilding: from the sitemap, plus any published page it missed.
 *
 * @return array<int,array{id:int,url:string,source:string}>
 */
function mmc_scan_pages(): array {
	$found = array();
	foreach ( mmc_sitemap_urls() as $url ) {
		$id = url_to_postid( $url );
		if ( ! $id && trailingslashit( $url ) === trailingslashit( home_url( '/' ) ) ) {
			$id = (int) get_option( 'page_on_front' );
		}
		if ( $id && 'page' === get_post_type( $id ) ) {
			$found[ $id ] = array( 'id' => $id, 'url' => $url, 'source' => 'sitemap' );
		}
	}
	foreach ( get_pages( array( 'post_status' => 'publish' ) ) as $page ) {
		if ( ! isset( $found[ $page->ID ] ) && ! get_post_meta( $page->ID, '_mmc_rebuilt_from', true ) ) {
			$found[ $page->ID ] = array( 'id' => $page->ID, 'url' => get_permalink( $page ), 'source' => 'pages' );
		}
	}
	return $found;
}

/**
 * Guess what a page is for from its slug and title.
 */
function mmc_page_type( WP_Post $post ): string {
	$id = $post->ID;
	if ( function_exists( 'wc_get_page_id' ) ) {
		foreach ( array( 'shop', 'cart', 'checkout', 'myaccount' ) as $wc ) {
			if ( (int) wc_get_page_id( $wc ) === $id ) {
				return 'store';
			}
		}
	}
	if ( (int) get_option( 'page_on_front' ) === $id ) {
		return 'home';
	}
	if ( (int) get_option( 'page_for_posts' ) === $id ) {
		return 'blog';
	}
	$hay = strtolower( $post->post_name . ' ' . $post->post_title );
	$map = array(
		'legal'      => '/privacy|terms|condition|refund|return|shipping|delivery|warranty|cookie|disclaimer|policy|legal|gdpr/',
		'contact'    => '/contact|support|get-in-touch/',
		'faq'        => '/\bfaq|questions/',
		'hosting'    => '/hosting|colocation/',
		'calculator' => '/calculator|profitab/',
		'about'      => '/about|story|who-we-are|team/',
	);
	foreach ( $map as $type => $re ) {
		if ( preg_match( $re, $hay ) ) {
			return $type;
		}
	}
	return 'generic';
}

/**
 * Original content as plain HTML: block comments removed, shortcodes kept.
 */
function mmc_clean_content( string $content ): string {
	$content = preg_replace( '#<!--\s*/?wp:[^>]*-->#', '', $content );
	$content = preg_replace( "/\n{3,}/", "\n\n", (string) $content );
	return trim( (string) $content );
}

/**
 * Wrap a shortcode as its own block so each section is edited separately.
 */
function mmc_block( string $shortcode ): string {
	return "<!-- wp:shortcode -->\n" . trim( $shortcode ) . "\n<!-- /wp:shortcode -->\n\n";
}

function mmc_attr( string $value ): string {
	return str_replace( array( '"', '[', ']' ), array( '&quot;', '&#91;', '&#93;' ), wp_strip_all_tags( $value ) );
}

/**
 * Short intro for a hero: the excerpt, or the first sentence of the content.
 */
function mmc_intro( WP_Post $post ): string {
	$text = has_excerpt( $post ) ? $post->post_excerpt : wp_strip_all_tags( strip_shortcodes( mmc_clean_content( $post->post_content ) ) );
	$text = trim( preg_replace( '/\s+/', ' ', (string) $text ) );
	if ( preg_match( '/^(.{40,220}?[.!?])\s/u', $text . ' ', $m ) ) {
		return $m[1];
	}
	return wp_trim_words( $text, 30, '…' );
}

/**
 * Turn "question heading + answer" pairs into FAQ shortcodes.
 */
function mmc_faq_from_html( string $html ): string {
	$parts = preg_split( '#<h[2-4][^>]*>(.*?)</h[2-4]>#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! $parts || count( $parts ) < 5 ) {
		return '';
	}
	$out = '';
	for ( $i = 1; $i < count( $parts ); $i += 2 ) {
		$q = trim( wp_strip_all_tags( $parts[ $i ] ) );
		$a = trim( $parts[ $i + 1 ] ?? '' );
		if ( '' !== $q && '' !== $a ) {
			$out .= '[mmc_q q="' . mmc_attr( $q ) . '"]' . "\n" . $a . "\n[/mmc_q]\n";
		}
	}
	return $out;
}

/**
 * Rebuilt content and template for a page.
 *
 * @return array{content:string,template:string}
 */
function mmc_blueprint( WP_Post $post, string $type ): array {
	$title    = mmc_attr( get_the_title( $post ) );
	$intro    = mmc_attr( mmc_intro( $post ) );
	$original = mmc_clean_content( $post->post_content );
	$landing  = file_exists( get_stylesheet_directory() . '/page-templates/landing.php' ) ? 'page-templates/landing.php' : '';
	$out      = '';

	switch ( $type ) {
		case 'legal':
			$out .= mmc_block( '[mmc_hero eyebrow="' . esc_attr__( 'Policies', 'mine-mirth-commerce' ) . '" title="' . $title . '" align="center"]' );
			$out .= mmc_block( '[mmc_legal updated="' . esc_attr( get_the_modified_date( 'Y-m-d', $post ) ) . "\"]\n" . $original . "\n[/mmc_legal]" );
			$out .= mmc_block( '[mmc_cta title="' . esc_attr__( 'Questions about this policy?', 'mine-mirth-commerce' ) . '" text="' . esc_attr__( 'Our team is happy to explain anything here.', 'mine-mirth-commerce' ) . '" button="' . esc_attr__( 'Contact us', 'mine-mirth-commerce' ) . '" url="/contact-us/"]' );
			return array( 'content' => $out, 'template' => $landing );

		case 'contact':
			$out .= mmc_block( '[mmc_hero eyebrow="' . esc_attr__( 'Contact', 'mine-mirth-commerce' ) . '" title="' . $title . '" text="' . $intro . '"]' );
			$out .= mmc_block( "[mmc_contact title=\"" . esc_attr__( 'Talk to people who run miners', 'mine-mirth-commerce' ) . "\"]\n" . $original . "\n[/mmc_contact]" );
			return array( 'content' => $out, 'template' => $landing );

		case 'faq':
			$faq  = mmc_faq_from_html( $original );
			$out .= mmc_block( '[mmc_hero eyebrow="' . esc_attr__( 'Help centre', 'mine-mirth-commerce' ) . '" title="' . $title . '" text="' . $intro . '" align="center"]' );
			$out .= $faq ? mmc_block( "[mmc_faq title=\"\"]\n" . $faq . '[/mmc_faq]' ) : mmc_block( "[mmc_section]\n" . $original . "\n[/mmc_section]" );
			$out .= mmc_block( '[mmc_cta title="' . esc_attr__( 'Still have a question?', 'mine-mirth-commerce' ) . '" button="' . esc_attr__( 'Contact us', 'mine-mirth-commerce' ) . '" url="/contact-us/"]' );
			return array( 'content' => $out, 'template' => $landing );

		case 'hosting':
			$tpl = file_exists( get_stylesheet_directory() . '/page-templates/hosting.php' ) ? 'page-templates/hosting.php' : $landing;
			return array( 'content' => $original, 'template' => $tpl );

		case 'calculator':
			$tpl = file_exists( get_stylesheet_directory() . '/page-templates/calculator.php' ) ? 'page-templates/calculator.php' : $landing;
			return array( 'content' => $original, 'template' => $tpl );

		case 'about':
			$out .= mmc_block( '[mmc_hero eyebrow="' . esc_attr__( 'About us', 'mine-mirth-commerce' ) . '" title="' . $title . '" text="' . $intro . '" button="' . esc_attr__( 'Shop miners', 'mine-mirth-commerce' ) . '" url="{shop}" button2="' . esc_attr__( 'Explore hosting', 'mine-mirth-commerce' ) . '" url2="/miner-hosting/"]' );
			$out .= mmc_block( "[mmc_section eyebrow=\"" . esc_attr__( 'Our story', 'mine-mirth-commerce' ) . "\"]\n" . $original . "\n[/mmc_section]" );
			$out .= mmc_block(
				"[mmc_features eyebrow=\"" . esc_attr__( 'How we work', 'mine-mirth-commerce' ) . "\" title=\"" . esc_attr__( 'What you can expect', 'mine-mirth-commerce' ) . "\" tone=\"alt\"]\n" .
				'[mmc_feature icon="shield" title="' . esc_attr__( 'Genuine hardware', 'mine-mirth-commerce' ) . '"]' . esc_html__( 'Edit this text: where your miners come from and how you check them.', 'mine-mirth-commerce' ) . "[/mmc_feature]\n" .
				'[mmc_feature icon="server" title="' . esc_attr__( 'Hosting that fits', 'mine-mirth-commerce' ) . '"]' . esc_html__( 'Edit this text: how hosting works with your partners.', 'mine-mirth-commerce' ) . "[/mmc_feature]\n" .
				'[mmc_feature icon="headset" title="' . esc_attr__( 'People who mine', 'mine-mirth-commerce' ) . '"]' . esc_html__( 'Edit this text: who answers when customers need help.', 'mine-mirth-commerce' ) . "[/mmc_feature]\n" .
				'[/mmc_features]'
			);
			$out .= mmc_block( '[mmc_cta title="' . esc_attr__( 'Ready to start mining?', 'mine-mirth-commerce' ) . '" button="' . esc_attr__( 'Shop miners', 'mine-mirth-commerce' ) . '" url="{shop}" button2="' . esc_attr__( 'Contact us', 'mine-mirth-commerce' ) . '" url2="/contact-us/"]' );
			return array( 'content' => $out, 'template' => $landing );

		default:
			$out .= mmc_block( '[mmc_hero title="' . $title . '" text="' . $intro . '"]' );
			$out .= mmc_block( "[mmc_section]\n" . $original . "\n[/mmc_section]" );
			$out .= mmc_block( '[mmc_cta title="' . esc_attr__( 'Find the right miner', 'mine-mirth-commerce' ) . '" button="' . esc_attr__( 'Shop miners', 'mine-mirth-commerce' ) . '" url="{shop}" button2="' . esc_attr__( 'Contact us', 'mine-mirth-commerce' ) . '" url2="/contact-us/"]' );
			return array( 'content' => $out, 'template' => $landing );
	}
}

/**
 * Create (or refresh) the rebuilt draft for a page.
 */
function mmc_rebuild_draft( int $page_id ): int {
	$post = get_post( $page_id );
	if ( ! $post || 'page' !== $post->post_type ) {
		return 0;
	}
	$type = mmc_page_type( $post );
	if ( in_array( $type, array( 'store', 'home', 'blog' ), true ) ) {
		return 0;
	}
	$plan     = mmc_blueprint( $post, $type );
	$draft_id = (int) get_post_meta( $page_id, '_mmc_draft', true );
	$data     = array(
		'post_type'    => 'page',
		'post_status'  => 'draft',
		'post_title'   => $post->post_title,
		'post_content' => $plan['content'],
		'post_excerpt' => $post->post_excerpt,
		'post_parent'  => $post->post_parent,
		'menu_order'   => $post->menu_order,
	);
	if ( $draft_id && get_post( $draft_id ) && 'draft' === get_post_status( $draft_id ) ) {
		$data['ID'] = $draft_id;
		wp_update_post( wp_slash( $data ) );
	} else {
		$draft_id = (int) wp_insert_post( wp_slash( $data ) );
	}
	if ( $draft_id ) {
		update_post_meta( $draft_id, '_mmc_rebuilt_from', $page_id );
		update_post_meta( $draft_id, '_wp_page_template', $plan['template'] ? $plan['template'] : 'default' );
		update_post_meta( $page_id, '_mmc_draft', $draft_id );
		if ( has_post_thumbnail( $post ) ) {
			set_post_thumbnail( $draft_id, get_post_thumbnail_id( $post ) );
		}
	}
	return $draft_id;
}

/**
 * Copy a rebuilt draft onto its original page. The old content stays in revisions.
 */
function mmc_apply_draft( int $page_id ): bool {
	$draft_id = (int) get_post_meta( $page_id, '_mmc_draft', true );
	$draft    = $draft_id ? get_post( $draft_id ) : null;
	if ( ! $draft ) {
		return false;
	}
	$old_template = (string) get_post_meta( $page_id, '_wp_page_template', true );
	update_post_meta( $page_id, '_mmc_template_before', $old_template );
	wp_update_post(
		wp_slash(
			array(
				'ID'           => $page_id,
				'post_content' => $draft->post_content,
			)
		)
	);
	update_post_meta( $page_id, '_wp_page_template', (string) get_post_meta( $draft_id, '_wp_page_template', true ) );
	update_post_meta( $page_id, '_mmc_applied', time() );
	wp_delete_post( $draft_id, true );
	delete_post_meta( $page_id, '_mmc_draft' );
	return true;
}

add_action(
	'admin_post_mmc_rebuild',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-commerce' ) );
		}
		check_admin_referer( 'mmc_rebuild' );
		$do   = sanitize_key( wp_unslash( $_POST['do'] ?? '' ) );
		$ids  = array_map( 'absint', (array) wp_unslash( $_POST['ids'] ?? array() ) );
		$one  = absint( wp_unslash( $_POST['page_id'] ?? 0 ) );
		$done = 0;
		if ( 'scan' === $do ) {
			update_option( 'mmc_scan', array( 'at' => time(), 'pages' => mmc_scan_pages() ), false );
		} elseif ( 'draft' === $do && $one ) {
			$done = mmc_rebuild_draft( $one ) ? 1 : 0;
		} elseif ( 'apply' === $do && $one ) {
			$done = mmc_apply_draft( $one ) ? 1 : 0;
		} elseif ( 'draft_all' === $do ) {
			foreach ( $ids as $id ) {
				$done += mmc_rebuild_draft( $id ) ? 1 : 0;
			}
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'mmc', 'mmc_done' => $do, 'n' => $done ), admin_url( 'admin.php' ) ) );
		exit;
	}
);

function mmc_rebuilder_page(): void {
	$scan  = get_option( 'mmc_scan', array() );
	$pages = is_array( $scan ) && ! empty( $scan['pages'] ) ? $scan['pages'] : array();
	$types = array(
		'legal'      => __( 'Policy', 'mine-mirth-commerce' ),
		'contact'    => __( 'Contact', 'mine-mirth-commerce' ),
		'faq'        => __( 'FAQ', 'mine-mirth-commerce' ),
		'hosting'    => __( 'Hosting (theme template)', 'mine-mirth-commerce' ),
		'calculator' => __( 'Calculator (theme template)', 'mine-mirth-commerce' ),
		'about'      => __( 'About', 'mine-mirth-commerce' ),
		'generic'    => __( 'General page', 'mine-mirth-commerce' ),
		'store'      => __( 'Store page — kept as is', 'mine-mirth-commerce' ),
		'home'       => __( 'Homepage — use Customize › Homepage', 'mine-mirth-commerce' ),
		'blog'       => __( 'Blog index — kept as is', 'mine-mirth-commerce' ),
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Page rebuilder', 'mine-mirth-commerce' ); ?></h1>
		<p style="max-width:760px"><?php esc_html_e( 'Scan reads your sitemap (/sitemap_index.xml) and lists every page. "Rebuild as draft" makes a draft copy laid out with section shortcodes and your original text inside; nothing live changes. Review the draft, then "Apply" copies it onto the original page. WordPress keeps the old version under Revisions.', 'mine-mirth-commerce' ); ?></p>
		<?php if ( ! empty( $_GET['mmc_done'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Done.', 'mine-mirth-commerce' ); ?></p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:16px 0">
			<?php wp_nonce_field( 'mmc_rebuild' ); ?>
			<input type="hidden" name="action" value="mmc_rebuild"><input type="hidden" name="do" value="scan">
			<button class="button button-primary"><?php echo $pages ? esc_html__( 'Scan again', 'mine-mirth-commerce' ) : esc_html__( 'Scan sitemap', 'mine-mirth-commerce' ); ?></button>
			<?php if ( ! empty( $scan['at'] ) ) : ?>
				<span class="description"><?php echo esc_html( sprintf( /* translators: %s: time ago */ __( 'Last scan %s ago', 'mine-mirth-commerce' ), human_time_diff( (int) $scan['at'] ) ) ); ?></span>
			<?php endif; ?>
		</form>
		<?php if ( $pages ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="mmc-bulk">
				<?php wp_nonce_field( 'mmc_rebuild' ); ?>
				<input type="hidden" name="action" value="mmc_rebuild"><input type="hidden" name="do" value="draft_all">
			</form>
			<table class="widefat striped">
				<thead><tr><th style="width:28px"><input type="checkbox" onclick="document.querySelectorAll('.mmc-pick').forEach(function(c){c.checked=this.checked}.bind(this))"></th><th><?php esc_html_e( 'Page', 'mine-mirth-commerce' ); ?></th><th><?php esc_html_e( 'Layout', 'mine-mirth-commerce' ); ?></th><th><?php esc_html_e( 'Found in', 'mine-mirth-commerce' ); ?></th><th><?php esc_html_e( 'Rebuild', 'mine-mirth-commerce' ); ?></th></tr></thead>
				<tbody>
				<?php
				foreach ( $pages as $row ) :
					$post = get_post( (int) $row['id'] );
					if ( ! $post ) {
						continue;
					}
					$type     = mmc_page_type( $post );
					$draft_id = (int) get_post_meta( $post->ID, '_mmc_draft', true );
					$skip     = in_array( $type, array( 'store', 'home', 'blog' ), true );
					?>
					<tr>
						<td><?php if ( ! $skip ) : ?><input class="mmc-pick" type="checkbox" name="ids[]" value="<?php echo esc_attr( (string) $post->ID ); ?>" form="mmc-bulk"><?php endif; ?></td>
						<td><strong><a href="<?php echo esc_url( (string) get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></strong><br><a href="<?php echo esc_url( $row['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $row['url'], PHP_URL_PATH ) ?: '/' ); ?></a><?php echo get_post_meta( $post->ID, '_mmc_applied', true ) ? ' · <em>' . esc_html__( 'rebuilt', 'mine-mirth-commerce' ) . '</em>' : ''; ?></td>
						<td><?php echo esc_html( $types[ $type ] ?? $type ); ?></td>
						<td><?php echo 'sitemap' === $row['source'] ? esc_html__( 'Sitemap', 'mine-mirth-commerce' ) : esc_html__( 'Pages (not in sitemap)', 'mine-mirth-commerce' ); ?></td>
						<td>
							<?php if ( ! $skip ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
									<?php wp_nonce_field( 'mmc_rebuild' ); ?>
									<input type="hidden" name="action" value="mmc_rebuild"><input type="hidden" name="do" value="draft"><input type="hidden" name="page_id" value="<?php echo esc_attr( (string) $post->ID ); ?>">
									<button class="button"><?php echo $draft_id ? esc_html__( 'Rebuild draft again', 'mine-mirth-commerce' ) : esc_html__( 'Rebuild as draft', 'mine-mirth-commerce' ); ?></button>
								</form>
								<?php if ( $draft_id && get_post( $draft_id ) ) : ?>
									<a class="button" href="<?php echo esc_url( (string) get_preview_post_link( $draft_id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview', 'mine-mirth-commerce' ); ?></a>
									<a class="button" href="<?php echo esc_url( (string) get_edit_post_link( $draft_id ) ); ?>"><?php esc_html_e( 'Edit draft', 'mine-mirth-commerce' ); ?></a>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('<?php echo esc_js( __( 'Replace the live page with this draft? The old version stays in Revisions.', 'mine-mirth-commerce' ) ); ?>')">
										<?php wp_nonce_field( 'mmc_rebuild' ); ?>
										<input type="hidden" name="action" value="mmc_rebuild"><input type="hidden" name="do" value="apply"><input type="hidden" name="page_id" value="<?php echo esc_attr( (string) $post->ID ); ?>">
										<button class="button button-primary"><?php esc_html_e( 'Apply to live page', 'mine-mirth-commerce' ); ?></button>
									</form>
								<?php endif; ?>
							<?php else : ?>
								<span class="description">—</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p><button class="button button-primary" form="mmc-bulk"><?php esc_html_e( 'Rebuild selected as drafts', 'mine-mirth-commerce' ); ?></button></p>
		<?php endif; ?>
		<h2 style="margin-top:32px"><?php esc_html_e( 'Section shortcodes', 'mine-mirth-commerce' ); ?></h2>
		<p><?php esc_html_e( 'Use these on any page (the "Landing" page template gives them the full width). Each one is a separate block you can edit.', 'mine-mirth-commerce' ); ?></p>
		<pre style="background:#fff;padding:16px;border:1px solid #dcdcde;max-width:900px;white-space:pre-wrap"><?php echo esc_html( mmc_shortcode_reference() ); ?></pre>
	</div>
	<?php
}

function mmc_shortcode_reference(): string {
	return implode(
		"\n",
		array(
			'[mmc_hero eyebrow="" title="" text="" button="" url="{shop}" button2="" url2="" image="123" align="left|center"]',
			'[mmc_section eyebrow="" title="" intro="" tone="plain|alt|dark" width="narrow|wide"]Text or blocks[/mmc_section]',
			'[mmc_split eyebrow="" title="" image="123" reverse="no|yes" button="" url=""]Text[/mmc_split]',
			'[mmc_features title="" columns="3"][mmc_feature icon="shield" title=""]Text[/mmc_feature][/mmc_features]',
			'[mmc_steps title=""][mmc_step title=""]Text[/mmc_step][/mmc_steps]',
			'[mmc_stats title=""][mmc_stat value="" label=""][/mmc_stats]',
			'[mmc_faq title=""][mmc_q q="Question?"]Answer[/mmc_q][/mmc_faq]',
			'[mmc_products title="" category="slug" ids="" limit="4" columns="4" orderby="date|popularity|rating|price" on_sale="no"]',
			'[mmc_contact title="" email="" phone="" address="" hours="" form=\'contact-form-7 id="12"\']Optional text[/mmc_contact]',
			'[mmc_legal updated="2026-10-01"]Policy text[/mmc_legal]',
			'[mmc_cta title="" text="" button="" url="" button2="" url2=""]',
			'[mmc_shop_filters]   [mmc_subscriptions]',
			'',
			'URLs accept {shop}, {account}, {cart}, {checkout}, {blog}, {home} or a path like /miner-hosting/.',
		)
	);
}
