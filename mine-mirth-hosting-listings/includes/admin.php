<?php
/**
 * Admin: the "Listing page" box on each hosting plan, the "Host this miner" box on products,
 * and the settings page (Hosting Plans › Listings & page).
 *
 * @package MineMirthHostingListings
 */

defined( 'ABSPATH' ) || exit;

function mmhl_cap(): string {
	return (string) apply_filters( 'mmhl_capability', 'manage_options' );
}

/** WooCommerce products for a select box (id => name), newest first. */
function mmhl_product_choices(): array {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	$out = array();
	foreach ( wc_get_products( array( 'limit' => 400, 'status' => array( 'publish', 'private', 'draft' ), 'orderby' => 'title', 'order' => 'ASC', 'return' => 'objects' ) ) as $p ) {
		$out[ $p->get_id() ] = $p->get_name() . ( 'publish' !== $p->get_status() ? ' (' . $p->get_status() . ')' : '' );
	}
	return $out;
}

function mmhl_listing_choices(): array {
	$out = array();
	foreach ( get_posts( array( 'post_type' => MMHL_TYPE, 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $p ) {
		$out[ $p->ID ] = get_the_title( $p );
	}
	return $out;
}

function mmhl_select( string $name, array $choices, $current, string $empty = '' ): string {
	$out = '<select name="' . esc_attr( $name ) . '">';
	if ( '' !== $empty ) {
		$out .= '<option value="0">' . esc_html( $empty ) . '</option>';
	}
	foreach ( $choices as $k => $v ) {
		$out .= '<option value="' . esc_attr( (string) $k ) . '"' . selected( (string) $current, (string) $k, false ) . '>' . esc_html( $v ) . '</option>';
	}
	return $out . '</select>';
}

/* ------------------------------------------------------------------ Listing box on hosting plans */

add_action(
	'add_meta_boxes_' . MMHL_TYPE,
	static function ( WP_Post $post ): void {
		add_meta_box( 'mmhl_listing', __( 'Listing page', 'mine-mirth-hosting-listings' ), 'mmhl_listing_box', MMHL_TYPE, 'normal', 'high' );
		// The theme labels it "Excerpt": here it is the short text on the listing card.
		remove_meta_box( 'postexcerpt', MMHL_TYPE, 'normal' );
		add_meta_box( 'postexcerpt', __( 'Card summary (one or two sentences)', 'mine-mirth-hosting-listings' ), 'post_excerpt_meta_box', MMHL_TYPE, 'normal', 'high' );
	}
);

function mmhl_listing_box( WP_Post $post ): void {
	wp_nonce_field( 'mmhl_listing', 'mmhl_listing_nonce' );
	$specs   = (string) get_post_meta( $post->ID, '_mmhl_specs', true );
	$miner   = absint( get_post_meta( $post->ID, '_mmhl_product', true ) );
	$gallery = array_values( array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, '_mmhl_gallery', true ) ) ) ) );
	?>
	<div class="mmhl-box">
		<?php if ( 'publish' === $post->post_status ) : ?>
			<p class="mmhl-box__live">
				<span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
				<?php esc_html_e( 'This listing has its own page:', 'mine-mirth-hosting-listings' ); ?>
				<a href="<?php echo esc_url( (string) get_permalink( $post ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( (string) get_permalink( $post ) ); ?> ↗</a>
			</p>
		<?php else : ?>
			<p class="mmhl-box__live is-draft"><?php esc_html_e( 'Publish this plan and it gets its own page, linked from every listing card on your hosting page.', 'mine-mirth-hosting-listings' ); ?></p>
		<?php endif; ?>

		<div class="mmhl-box__grid">
			<label class="mmhl-f">
				<span><?php esc_html_e( 'Key specs', 'mine-mirth-hosting-listings' ); ?> <i><?php esc_html_e( 'one per line, "Label: value". The first three show on the card.', 'mine-mirth-hosting-listings' ); ?></i></span>
				<textarea name="mmhl_specs" rows="6" placeholder="<?php echo esc_attr( "Hashrate: 270 TH/s\nPower: 3,645 W\nEfficiency: 13.5 J/TH\nAlgorithm: SHA-256" ); ?>"><?php echo esc_textarea( $specs ); ?></textarea>
			</label>
			<div>
				<label class="mmhl-f">
					<span><?php esc_html_e( 'Miner product', 'mine-mirth-hosting-listings' ); ?> <i><?php esc_html_e( 'optional', 'mine-mirth-hosting-listings' ); ?></i></span>
					<?php echo mmhl_select( 'mmhl_product', mmhl_product_choices(), $miner, __( '— None —', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</label>
				<p class="description"><?php esc_html_e( 'The miner this listing hosts. Its page shows "Need the miner too?" with a link to the product, its specs fill in when "Key specs" is empty, and that product\'s "Host this miner" button comes straight here.', 'mine-mirth-hosting-listings' ); ?></p>
			</div>
		</div>

		<div class="mmhl-f">
			<span><?php esc_html_e( 'More images', 'mine-mirth-hosting-listings' ); ?> <i><?php esc_html_e( 'optional; the Plan image (right column) is always first', 'mine-mirth-hosting-listings' ); ?></i></span>
			<input type="hidden" name="mmhl_gallery" value="<?php echo esc_attr( implode( ',', $gallery ) ); ?>" data-mmhl-gallery-input>
			<ul class="mmhl-thumbs" data-mmhl-gallery-list>
				<?php foreach ( $gallery as $img ) : ?>
					<li data-id="<?php echo (int) $img; ?>"><?php echo wp_get_attachment_image( $img, 'thumbnail' ); ?><button type="button" class="mmhl-thumbs__x" aria-label="<?php esc_attr_e( 'Remove image', 'mine-mirth-hosting-listings' ); ?>">×</button></li>
				<?php endforeach; ?>
			</ul>
			<button type="button" class="button" data-mmhl-gallery-add><?php esc_html_e( 'Add images', 'mine-mirth-hosting-listings' ); ?></button>
		</div>

		<p class="description"><?php esc_html_e( 'The rest of the listing page comes from this plan: title, the main text box (Overview), Hosting plan details (location, availability, rate, included, notes) and the price and Subscribe button from the Stripe subscription box.', 'mine-mirth-hosting-listings' ); ?></p>
	</div>
	<?php
}

add_action(
	'save_post_' . MMHL_TYPE,
	static function ( int $post_id ): void {
		if ( ! isset( $_POST['mmhl_listing_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mmhl_listing_nonce'] ) ), 'mmhl_listing' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_mmhl_specs', sanitize_textarea_field( wp_unslash( $_POST['mmhl_specs'] ?? '' ) ) );
		update_post_meta( $post_id, '_mmhl_product', absint( $_POST['mmhl_product'] ?? 0 ) );
		$ids = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['mmhl_gallery'] ?? '' ) ) ) ) );
		update_post_meta( $post_id, '_mmhl_gallery', implode( ',', $ids ) );
	}
);

/** "View" links for listings open in a new tab. */
add_filter(
	'post_row_actions',
	static function ( array $actions, WP_Post $post ): array {
		if ( MMHL_TYPE === $post->post_type && isset( $actions['view'] ) ) {
			$actions['view'] = str_replace( '<a ', '<a target="_blank" rel="noopener" ', $actions['view'] );
		}
		return $actions;
	},
	10,
	2
);

/** After saving: "Listing page updated. View listing ↗" (in a new tab). */
add_filter(
	'post_updated_messages',
	static function ( array $messages ): array {
		global $post;
		if ( ! $post || MMHL_TYPE !== $post->post_type ) {
			return $messages;
		}
		$link = ' <a href="' . esc_url( (string) get_permalink( $post ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View listing page ↗', 'mine-mirth-hosting-listings' ) . '</a>';
		$messages[ MMHL_TYPE ] = array(
			1  => __( 'Listing updated.', 'mine-mirth-hosting-listings' ) . $link,
			4  => __( 'Listing updated.', 'mine-mirth-hosting-listings' ),
			6  => __( 'Listing published. It now has its own page.', 'mine-mirth-hosting-listings' ) . $link,
			7  => __( 'Listing saved.', 'mine-mirth-hosting-listings' ),
			8  => __( 'Listing submitted.', 'mine-mirth-hosting-listings' ),
			10 => __( 'Listing draft updated.', 'mine-mirth-hosting-listings' ),
		);
		return $messages;
	}
);

add_action(
	'admin_enqueue_scripts',
	static function ( string $hook ): void {
		$screen = get_current_screen();
		$ours   = $screen && ( MMHL_TYPE === $screen->post_type || 'product' === $screen->post_type || str_contains( $hook, 'mmhl' ) );
		if ( ! $ours ) {
			return;
		}
		if ( $screen && MMHL_TYPE === $screen->post_type && 'post' === $screen->base ) {
			wp_enqueue_media();
		}
		wp_enqueue_style( 'mmhl-admin', MMHL_URI . 'assets/admin.css', array(), MMHL_VERSION );
		wp_enqueue_script( 'mmhl-admin', MMHL_URI . 'assets/admin.js', array(), MMHL_VERSION, true );
		wp_localize_script( 'mmhl-admin', 'mmhlAdmin', array( 'pick' => __( 'Choose listing images', 'mine-mirth-hosting-listings' ), 'use' => __( 'Add to listing', 'mine-mirth-hosting-listings' ) ) );
	}
);

/* ------------------------------------------------------------------ "Host this miner" box on products */

add_action(
	'add_meta_boxes_product',
	static function (): void {
		add_meta_box( 'mmhl_host', __( 'Host this miner button', 'mine-mirth-hosting-listings' ), 'mmhl_host_box', 'product', 'side', 'default' );
	}
);

function mmhl_host_box( WP_Post $post ): void {
	wp_nonce_field( 'mmhl_host', 'mmhl_host_nonce' );
	$mode    = (string) get_post_meta( $post->ID, '_mmhl_host', true );
	$listing = absint( get_post_meta( $post->ID, '_mmhl_host_listing', true ) );
	$url     = (string) get_post_meta( $post->ID, '_mmhl_host_url', true );
	$modes   = array(
		''        => __( 'Use the default (settings)', 'mine-mirth-hosting-listings' ),
		'listing' => __( 'A hosting listing', 'mine-mirth-hosting-listings' ),
		'custom'  => __( 'A custom link', 'mine-mirth-hosting-listings' ),
		'hide'    => __( 'Hide on this product', 'mine-mirth-hosting-listings' ),
	);
	$now = mmhl_host_target( (int) $post->ID );
	?>
	<div class="mmhl-host-box" data-mmhl-host>
		<p><label class="screen-reader-text" for="mmhl_host_mode"><?php esc_html_e( 'Button goes to', 'mine-mirth-hosting-listings' ); ?></label>
			<select id="mmhl_host_mode" name="mmhl_host" data-mmhl-host-mode style="width:100%">
				<?php foreach ( $modes as $k => $v ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $mode, $k ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endforeach; ?>
			</select></p>
		<p data-mmhl-show="listing"><?php echo mmhl_select( 'mmhl_host_listing', mmhl_listing_choices(), $listing, __( '— Choose a listing —', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<p data-mmhl-show="custom"><input type="url" name="mmhl_host_url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://" style="width:100%"></p>
		<p class="description">
			<?php if ( '' === $now['url'] ) : ?>
				<?php esc_html_e( 'Now: hidden on this product.', 'mine-mirth-hosting-listings' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Now goes to:', 'mine-mirth-hosting-listings' ); ?> <a href="<?php echo esc_url( $now['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $now['url'], PHP_URL_PATH ) ?: $now['url'] ); ?></a>
			<?php endif; ?>
			<br><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . MMHL_TYPE . '&page=mmhl&tab=host' ) ); ?>"><?php esc_html_e( 'Default, text and new tab settings', 'mine-mirth-hosting-listings' ); ?></a>
		</p>
	</div>
	<?php
}

add_action(
	'save_post_product',
	static function ( int $post_id ): void {
		if ( ! isset( $_POST['mmhl_host_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mmhl_host_nonce'] ) ), 'mmhl_host' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$mode = sanitize_key( wp_unslash( $_POST['mmhl_host'] ?? '' ) );
		update_post_meta( $post_id, '_mmhl_host', in_array( $mode, array( 'listing', 'custom', 'hide' ), true ) ? $mode : '' );
		update_post_meta( $post_id, '_mmhl_host_listing', absint( $_POST['mmhl_host_listing'] ?? 0 ) );
		update_post_meta( $post_id, '_mmhl_host_url', esc_url_raw( wp_unslash( $_POST['mmhl_host_url'] ?? '' ) ) );
	}
);

/* ------------------------------------------------------------------ Settings page */

add_action(
	'admin_menu',
	static function (): void {
		add_submenu_page( 'edit.php?post_type=' . MMHL_TYPE, __( 'Listings & hosting page', 'mine-mirth-hosting-listings' ), __( 'Listings & page', 'mine-mirth-hosting-listings' ), mmhl_cap(), 'mmhl', 'mmhl_settings_page' );
	},
	20
);

add_filter(
	'plugin_action_links_' . plugin_basename( MMHL_FILE ),
	static function ( array $links ): array {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'edit.php?post_type=' . MMHL_TYPE . '&page=mmhl' ) ) . '">' . esc_html__( 'Settings', 'mine-mirth-hosting-listings' ) . '</a>' );
		return $links;
	}
);

add_action(
	'admin_post_mmhl_save',
	static function (): void {
		if ( ! current_user_can( mmhl_cap() ) || ! check_admin_referer( 'mmhl_save' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-hosting-listings' ), 403 );
		}
		$tab  = sanitize_key( wp_unslash( $_POST['tab'] ?? 'page' ) );
		$in   = (array) wp_unslash( $_POST['mmhl'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field below.
		$s    = mmhl_settings( true );
		$old  = mmhl_slug();
		$text = array( 'eyebrow', 'title', 'btn_text', 'help_text', 'help_label', 'host_label', 'host_text' );
		$urls = array( 'btn_url', 'help_url', 'host_url' );
		$bool = array(
			'page' => array( 'takeover', 'search', 'filters', 'sort', 'new_tab', 'show_summary', 'show_specs', 'show_price', 'show_rate', 'show_booked', 'page_content', 'everywhere' ),
			'listing' => array( 'related' ),
			'host' => array( 'host_auto', 'host_new_tab' ),
		);
		$fields = array(
			'page'    => array( 'page', 'eyebrow', 'title', 'intro', 'btn_text', 'btn_url', 'columns', 'per_page', 'below' ),
			'listing' => array( 'slug', 'help_text', 'help_label', 'help_url' ),
			'host'    => array( 'host_mode', 'host_listing', 'host_url', 'host_label', 'host_text' ),
		);
		foreach ( $fields[ $tab ] ?? array() as $k ) {
			$v = $in[ $k ] ?? '';
			if ( in_array( $k, $text, true ) ) {
				$s[ $k ] = sanitize_text_field( (string) $v );
			} elseif ( in_array( $k, $urls, true ) ) {
				$v       = trim( (string) $v );
				$s[ $k ] = str_starts_with( $v, '/' ) || str_starts_with( $v, '{' ) || str_starts_with( $v, '#' ) ? sanitize_text_field( $v ) : esc_url_raw( $v );
			} elseif ( 'intro' === $k ) {
				$s[ $k ] = sanitize_textarea_field( (string) $v );
			} elseif ( 'below' === $k ) {
				$s[ $k ] = wp_kses_post( (string) $v );
			} elseif ( 'columns' === $k ) {
				$s[ $k ] = max( 2, min( 4, absint( $v ) ) );
			} elseif ( 'per_page' === $k ) {
				$s[ $k ] = min( 200, absint( $v ) );
			} elseif ( 'page' === $k || 'host_listing' === $k ) {
				$s[ $k ] = absint( $v );
			} elseif ( 'host_mode' === $k ) {
				$s[ $k ] = in_array( $v, array( 'page', 'listing', 'custom' ), true ) ? $v : 'page';
			} elseif ( 'slug' === $k ) {
				$s[ $k ] = implode( '/', array_filter( array_map( 'sanitize_title', explode( '/', (string) $v ) ) ) );
			}
		}
		foreach ( $bool[ $tab ] ?? array() as $k ) {
			$s[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
		}
		update_option( 'mmhl_settings', $s, true );
		mmhl_settings( true );
		if ( mmhl_slug() !== $old ) {
			update_option( 'mmhl_flush', 1, false );
		}
		wp_safe_redirect( add_query_arg( array( 'post_type' => MMHL_TYPE, 'page' => 'mmhl', 'tab' => $tab, 'saved' => 1 ), admin_url( 'edit.php' ) ) );
		exit;
	}
);

function mmhl_settings_page(): void {
	if ( ! current_user_can( mmhl_cap() ) ) {
		return;
	}
	$s    = mmhl_settings( true );
	$tab  = sanitize_key( wp_unslash( $_GET['tab'] ?? 'page' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$tabs = array(
		'page'    => __( 'Hosting page', 'mine-mirth-hosting-listings' ),
		'listing' => __( 'Listing pages', 'mine-mirth-hosting-listings' ),
		'host'    => __( 'Host this miner button', 'mine-mirth-hosting-listings' ),
	);
	$tab  = isset( $tabs[ $tab ] ) ? $tab : 'page';
	$chk  = static fn( string $k, string $label, string $help = '' ) => '<label class="mmhl-check"><input type="checkbox" name="mmhl[' . esc_attr( $k ) . ']" value="1" ' . checked( ! empty( $s[ $k ] ), true, false ) . '> <span>' . esc_html( $label ) . ( $help ? ' <i>' . esc_html( $help ) . '</i>' : '' ) . '</span></label>';
	$txt  = static fn( string $k, string $placeholder = '' ) => '<input type="text" class="regular-text" name="mmhl[' . esc_attr( $k ) . ']" value="' . esc_attr( (string) $s[ $k ] ) . '" placeholder="' . esc_attr( $placeholder ) . '">';
	?>
	<div class="wrap mmhl-admin">
		<h1><?php esc_html_e( 'Hosting listings', 'mine-mirth-hosting-listings' ); ?></h1>
		<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved.', 'mine-mirth-hosting-listings' ); ?> <a href="<?php echo esc_url( mmhl_page_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View the hosting page ↗', 'mine-mirth-hosting-listings' ); ?></a></p></div>
		<?php endif; ?>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $k => $label ) : ?>
				<a class="nav-tab<?php echo $k === $tab ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'post_type' => MMHL_TYPE, 'page' => 'mmhl', 'tab' => $k ), admin_url( 'edit.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mmhl-form">
			<input type="hidden" name="action" value="mmhl_save">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
			<?php wp_nonce_field( 'mmhl_save' ); ?>

			<?php if ( 'page' === $tab ) : ?>
				<div class="mmhl-card">
					<h2><?php esc_html_e( 'The page', 'mine-mirth-hosting-listings' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr><th scope="row"><?php esc_html_e( 'Hosting page', 'mine-mirth-hosting-listings' ); ?></th><td>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => 'mmhl[page]',
									'selected'          => mmhl_page_id(),
									'show_option_none'  => __( '— Choose —', 'mine-mirth-hosting-listings' ),
									'option_none_value' => 0,
								)
							);
							?>
							<a href="<?php echo esc_url( mmhl_page_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View ↗', 'mine-mirth-hosting-listings' ); ?></a>
						</td></tr>
						<tr><th scope="row"><?php esc_html_e( 'Control', 'mine-mirth-hosting-listings' ); ?></th><td>
							<?php echo $chk( 'takeover', __( 'Build this whole page from the settings below', 'mine-mirth-hosting-listings' ), __( '(header, listings, your content). Untick to go back to the page as the theme draws it.', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</td></tr>
					</table>
				</div>

				<div class="mmhl-card">
					<h2><?php esc_html_e( 'Header', 'mine-mirth-hosting-listings' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr><th scope="row"><?php esc_html_e( 'Small label', 'mine-mirth-hosting-listings' ); ?></th><td><?php echo $txt( 'eyebrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
						<tr><th scope="row"><?php esc_html_e( 'Title', 'mine-mirth-hosting-listings' ); ?></th><td><?php echo $txt( 'title' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><p class="description"><?php esc_html_e( 'Put *stars* around words to show them in the brand gradient.', 'mine-mirth-hosting-listings' ); ?></p></td></tr>
						<tr><th scope="row"><?php esc_html_e( 'Intro', 'mine-mirth-hosting-listings' ); ?></th><td><textarea name="mmhl[intro]" rows="3" class="large-text"><?php echo esc_textarea( (string) $s['intro'] ); ?></textarea></td></tr>
						<tr><th scope="row"><?php esc_html_e( 'Button', 'mine-mirth-hosting-listings' ); ?> <i><?php esc_html_e( 'optional', 'mine-mirth-hosting-listings' ); ?></i></th><td class="mmhl-inline"><?php echo $txt( 'btn_text', __( 'Text, e.g. Talk to us', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo $txt( 'btn_url', '/contact-us/' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
					</table>
				</div>

				<div class="mmhl-card">
					<h2><?php esc_html_e( 'Listings', 'mine-mirth-hosting-listings' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr><th scope="row"><?php esc_html_e( 'Layout', 'mine-mirth-hosting-listings' ); ?></th><td class="mmhl-inline">
							<label><?php esc_html_e( 'Cards per row', 'mine-mirth-hosting-listings' ); ?> <select name="mmhl[columns]"><?php foreach ( array( 2, 3, 4 ) as $c ) : ?><option value="<?php echo (int) $c; ?>" <?php selected( (int) $s['columns'], $c ); ?>><?php echo (int) $c; ?></option><?php endforeach; ?></select></label>
							<label><?php esc_html_e( 'Show', 'mine-mirth-hosting-listings' ); ?> <input type="number" min="0" max="200" name="mmhl[per_page]" value="<?php echo (int) $s['per_page']; ?>" class="small-text"> <?php esc_html_e( 'at first, then a "Show more" button (0 = all)', 'mine-mirth-hosting-listings' ); ?></label>
						</td></tr>
						<tr><th scope="row"><?php esc_html_e( 'Search bar', 'mine-mirth-hosting-listings' ); ?></th><td>
							<?php echo $chk( 'search', __( 'Search box', 'mine-mirth-hosting-listings' ) ) . $chk( 'filters', __( 'Location and availability filters', 'mine-mirth-hosting-listings' ) ) . $chk( 'sort', __( 'Sorting (recommended, price, newest)', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</td></tr>
						<tr><th scope="row"><?php esc_html_e( 'On each card', 'mine-mirth-hosting-listings' ); ?></th><td>
							<?php echo $chk( 'show_summary', __( 'Short summary', 'mine-mirth-hosting-listings' ) ) . $chk( 'show_specs', __( 'Three key specs', 'mine-mirth-hosting-listings' ) ) . $chk( 'show_price', __( 'Monthly price', 'mine-mirth-hosting-listings' ) ) . $chk( 'show_rate', __( 'The listing\'s hosting rate (per kWh)', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</td></tr>
						<tr><th scope="row"><?php esc_html_e( 'Opening a listing', 'mine-mirth-hosting-listings' ); ?></th><td>
							<?php echo $chk( 'new_tab', __( 'Open the full listing page in a new tab', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</td></tr>
						<tr><th scope="row"><?php esc_html_e( 'Fully booked', 'mine-mirth-hosting-listings' ); ?></th><td>
							<?php echo $chk( 'show_booked', __( 'Still show listings marked "Fully booked"', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</td></tr>
						<tr><th scope="row"><?php esc_html_e( 'Elsewhere', 'mine-mirth-hosting-listings' ); ?></th><td>
							<?php echo $chk( 'everywhere', __( 'Use these compact cards wherever the site lists hosting plans', 'mine-mirth-hosting-listings' ), __( '(other pages\' "Hosting plans" sections and plan shortcodes). Shortcode for anywhere: [mm_hosting_listings]', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</td></tr>
					</table>
				</div>

				<div class="mmhl-card">
					<h2><?php esc_html_e( 'Below the listings', 'mine-mirth-hosting-listings' ); ?> <i><?php esc_html_e( 'optional', 'mine-mirth-hosting-listings' ); ?></i></h2>
					<p class="description"><?php esc_html_e( 'Anything you want under the listings: how hosting works, terms, a FAQ. Leave empty for listings only.', 'mine-mirth-hosting-listings' ); ?></p>
					<?php wp_editor( (string) $s['below'], 'mmhl_below', array( 'textarea_name' => 'mmhl[below]', 'textarea_rows' => 10, 'media_buttons' => true ) ); ?>
					<p><?php echo $chk( 'page_content', __( 'Then also show the page\'s own content (the sections you built on it in the page editor)', 'mine-mirth-hosting-listings' ), __( 'Off by default: those sections mention a general per-kWh rate.', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				</div>

			<?php elseif ( 'listing' === $tab ) : ?>
				<div class="mmhl-card">
					<h2><?php esc_html_e( 'Listing pages', 'mine-mirth-hosting-listings' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Every published hosting plan has its own page. Create one under Hosting Plans › Add new; it appears on the hosting page as a card that opens this page.', 'mine-mirth-hosting-listings' ); ?></p>
					<table class="form-table" role="presentation">
						<tr><th scope="row"><?php esc_html_e( 'Web address', 'mine-mirth-hosting-listings' ); ?></th><td>
							<code><?php echo esc_html( home_url( '/' ) ); ?></code><input type="text" name="mmhl[slug]" value="<?php echo esc_attr( (string) $s['slug'] ); ?>" class="regular-text code" style="width:200px"><code>/<?php esc_html_e( 'listing-name', 'mine-mirth-hosting-listings' ); ?>/</code>
						</td></tr>
						<tr><th scope="row"><?php esc_html_e( 'Help line', 'mine-mirth-hosting-listings' ); ?></th><td class="mmhl-inline">
							<?php echo $txt( 'help_text' ) . ' ' . $txt( 'help_label' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<p><?php echo $txt( 'help_url', __( 'Link (empty = your contact page)', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
							<p class="description"><?php esc_html_e( 'Under the Subscribe button. A WhatsApp link works too, e.g. https://wa.me/15035550100. Leave the link text empty to hide it.', 'mine-mirth-hosting-listings' ); ?></p>
						</td></tr>
						<tr><th scope="row"><?php esc_html_e( 'More listings', 'mine-mirth-hosting-listings' ); ?></th><td>
							<?php echo $chk( 'related', __( 'Show other listings at the bottom of each listing page', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</td></tr>
					</table>
				</div>

			<?php else : ?>
				<div class="mmhl-card">
					<h2><?php esc_html_e( '"Host this miner" on product pages', 'mine-mirth-hosting-listings' ); ?></h2>
					<p class="description"><?php esc_html_e( 'The box under Add to cart on each miner\'s page. Set the default here; any product can override it in its own "Host this miner button" box (right column of the product editor).', 'mine-mirth-hosting-listings' ); ?></p>
					<table class="form-table" role="presentation">
						<tr><th scope="row"><?php esc_html_e( 'Goes to', 'mine-mirth-hosting-listings' ); ?></th><td>
							<label class="mmhl-check"><input type="radio" name="mmhl[host_mode]" value="page" <?php checked( $s['host_mode'], 'page' ); ?>> <span><?php esc_html_e( 'The hosting page', 'mine-mirth-hosting-listings' ); ?> <i><?php echo esc_html( wp_parse_url( mmhl_page_url(), PHP_URL_PATH ) ); ?></i></span></label>
							<label class="mmhl-check"><input type="radio" name="mmhl[host_mode]" value="listing" <?php checked( $s['host_mode'], 'listing' ); ?>> <span><?php esc_html_e( 'One hosting listing:', 'mine-mirth-hosting-listings' ); ?></span> <?php echo mmhl_select( 'mmhl[host_listing]', mmhl_listing_choices(), (int) $s['host_listing'], __( '— Choose —', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
							<label class="mmhl-check"><input type="radio" name="mmhl[host_mode]" value="custom" <?php checked( $s['host_mode'], 'custom' ); ?>> <span><?php esc_html_e( 'A custom link:', 'mine-mirth-hosting-listings' ); ?></span> <?php echo $txt( 'host_url', 'https://' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
							<?php echo $chk( 'host_auto', __( 'When a listing names a miner as its "Miner product", that miner\'s button goes to the listing', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</td></tr>
						<tr><th scope="row"><?php esc_html_e( 'Text', 'mine-mirth-hosting-listings' ); ?> <i><?php esc_html_e( 'optional', 'mine-mirth-hosting-listings' ); ?></i></th><td>
							<p><?php echo $txt( 'host_label', __( 'Host this miner', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
							<p><?php echo $txt( 'host_text', (string) mmhl_theme_opt( 'product_hosting_text', '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
							<p class="description"><?php esc_html_e( 'Empty = the theme\'s text. To hide the box on every product, turn off "Hosting upsell box" in Appearance › Customize (product page).', 'mine-mirth-hosting-listings' ); ?></p>
						</td></tr>
						<tr><th scope="row"><?php esc_html_e( 'New tab', 'mine-mirth-hosting-listings' ); ?></th><td>
							<?php echo $chk( 'host_new_tab', __( 'Open the link in a new tab', 'mine-mirth-hosting-listings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</td></tr>
					</table>
				</div>
			<?php endif; ?>

			<?php submit_button( __( 'Save', 'mine-mirth-hosting-listings' ) ); ?>
		</form>
	</div>
	<?php
}
