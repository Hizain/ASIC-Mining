<?php
/**
 * Appearance › Mine Mirth: setup checklist, quick links into every
 * Customizer panel, starter pages and settings export / import / reset.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

function mm_admin_menu(): void {
	add_theme_page(
		__( 'Mine Mirth theme', 'minemirth' ),
		__( 'Mine Mirth', 'minemirth' ),
		'edit_theme_options',
		'mine-mirth',
		'mm_admin_page'
	);
}
add_action( 'admin_menu', 'mm_admin_menu' );

/**
 * Customize deep link.
 */
function mm_customize_link( string $type, string $id ): string {
	return add_query_arg( array( 'autofocus[' . $type . ']' => $id ), admin_url( 'customize.php' ) );
}

/**
 * Setup checks: [label, done(bool), action url, action label].
 */
function mm_setup_checks(): array {
	$locations = get_nav_menu_locations();
	$hosting   = get_pages(
		array(
			'meta_key'   => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value' => 'page-templates/hosting.php', // phpcs:ignore WordPress.DB.SlowDBQuery
			'number'     => 1,
		)
	);
	$calc      = get_pages(
		array(
			'meta_key'   => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value' => 'page-templates/calculator.php', // phpcs:ignore WordPress.DB.SlowDBQuery
			'number'     => 1,
		)
	);
	$promos    = wp_count_posts( 'mm_promo' );
	return array(
		array( __( 'WooCommerce is active', 'minemirth' ), mm_is_woo(), admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ), __( 'Install', 'minemirth' ) ),
		array( __( 'Logo uploaded', 'minemirth' ), (bool) get_theme_mod( 'custom_logo' ), mm_customize_link( 'control', 'custom_logo' ), __( 'Upload', 'minemirth' ) ),
		array( __( 'Dark-mode logo uploaded (optional)', 'minemirth' ), (bool) mm_opt( 'logo_dark' ), mm_customize_link( 'control', 'mm_logo_dark' ), __( 'Upload', 'minemirth' ) ),
		array( __( 'Static front page set', 'minemirth' ), 'page' === get_option( 'show_on_front' ), mm_customize_link( 'section', 'static_front_page' ), __( 'Set', 'minemirth' ) ),
		array( __( 'Primary menu assigned', 'minemirth' ), ! empty( $locations['primary'] ), admin_url( 'nav-menus.php?action=locations' ), __( 'Assign', 'minemirth' ) ),
		array( __( 'Footer menus assigned (optional — smart defaults are shown)', 'minemirth' ), ! empty( $locations['footer-1'] ), admin_url( 'nav-menus.php?action=locations' ), __( 'Assign', 'minemirth' ) ),
		array( __( 'Miner Hosting page exists', 'minemirth' ), (bool) $hosting, wp_nonce_url( admin_url( 'admin-post.php?action=mm_starter_pages' ), 'mm_starter_pages' ), __( 'Create', 'minemirth' ) ),
		array( __( 'Mining Calculator page exists', 'minemirth' ), (bool) $calc, wp_nonce_url( admin_url( 'admin-post.php?action=mm_starter_pages' ), 'mm_starter_pages' ), __( 'Create', 'minemirth' ) ),
		array( __( 'At least one promotion created (optional — automatic fallbacks run otherwise)', 'minemirth' ), $promos && $promos->publish > 0, admin_url( 'post-new.php?post_type=mm_promo' ), __( 'Create', 'minemirth' ) ),
		array( __( 'Contact email added', 'minemirth' ), (bool) mm_opt( 'contact_email' ), mm_customize_link( 'section', 'mm_contact' ), __( 'Add', 'minemirth' ) ),
	);
}

function mm_admin_page(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$checks = mm_setup_checks();
	$done   = count( array_filter( array_column( $checks, 1 ) ) );
	$notice = isset( $_GET['mm_notice'] ) ? sanitize_key( $_GET['mm_notice'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$panels = array(
		array( 'mm_global', 'art', __( 'Design system', 'minemirth' ), __( 'Colors, light/dark palettes, fonts, radius, motion.', 'minemirth' ) ),
		array( 'mm_header', 'menu', __( 'Header', 'minemirth' ), __( 'Announcement bar, layout, logo, icons, search.', 'minemirth' ) ),
		array( 'mm_home', 'admin-home', __( 'Homepage', 'minemirth' ), __( 'Reorder sections, hero animation, every text.', 'minemirth' ) ),
		array( 'mm_blog', 'welcome-write-blog', __( 'Blog & promotions', 'minemirth' ), __( 'Layouts, article tools, in-content promos.', 'minemirth' ) ),
		array( 'mm_shop', 'cart', __( 'Shop & checkout', 'minemirth' ), __( 'Cards, product page, mini cart, checkout, account.', 'minemirth' ) ),
		array( 'mm_footer', 'editor-insertmore', __( 'Footer', 'minemirth' ), __( 'Columns, contact, social links, legal.', 'minemirth' ) ),
		array( 'mm_misc', 'admin-generic', __( 'Pages & data', 'minemirth' ), __( 'Hosting page, calculator, market data, newsletter, SEO.', 'minemirth' ) ),
	);
	?>
	<div class="wrap mm-admin">
		<style>
			.mm-admin{max-width:1180px}
			.mm-admin .mm-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;margin:20px 0;padding:32px;border-radius:16px;color:#e7f0fa;background:radial-gradient(60% 120% at 100% 0%,rgba(53,168,246,.45),transparent 60%),linear-gradient(160deg,#0a1b2f,#040b15)}
			.mm-admin .mm-hero h1{color:#fff;font-size:28px;margin:0 0 6px;padding:0}
			.mm-admin .mm-hero p{margin:0;color:#9fb6cf;font-size:14px;max-width:640px}
			.mm-admin .mm-hero .button-hero{background:#35a8f6;border-color:#35a8f6;color:#03111f;font-weight:600}
			.mm-admin .mm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;margin-bottom:28px}
			.mm-admin .mm-card{display:block;padding:18px;border:1px solid #dcdcde;border-radius:12px;background:#fff;text-decoration:none;color:#1d2327;transition:border-color .2s,box-shadow .2s}
			.mm-admin .mm-card:hover{border-color:#35a8f6;box-shadow:0 6px 20px rgba(53,168,246,.15)}
			.mm-admin .mm-card .dashicons{color:#35a8f6;font-size:22px;width:22px;height:22px}
			.mm-admin .mm-card strong{display:block;margin:10px 0 4px;font-size:15px}
			.mm-admin .mm-card span.d{color:#646970}
			.mm-admin .mm-cols{display:grid;grid-template-columns:1.3fr 1fr;gap:20px}
			.mm-admin .mm-box{padding:22px;border:1px solid #dcdcde;border-radius:12px;background:#fff}
			.mm-admin .mm-box h2{margin-top:0}
			.mm-admin .mm-check{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid #f0f0f1}
			.mm-admin .mm-check .ok{color:#12b981}.mm-admin .mm-check .no{color:#c3c4c7}
			.mm-admin .mm-bar{height:8px;border-radius:8px;background:#f0f0f1;overflow:hidden;margin:6px 0 14px}
			.mm-admin .mm-bar span{display:block;height:100%;background:linear-gradient(90deg,#5ee1ff,#35a8f6,#2563eb)}
			.mm-admin textarea{width:100%;min-height:110px;font-family:monospace;font-size:12px}
			.mm-admin code{font-size:12px}
			@media(max-width:960px){.mm-admin .mm-cols{grid-template-columns:1fr}}
		</style>

		<?php if ( 'imported' === $notice ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Settings imported.', 'minemirth' ); ?></p></div>
		<?php elseif ( 'reset' === $notice ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'All Mine Mirth settings were reset to defaults.', 'minemirth' ); ?></p></div>
		<?php elseif ( 'pages' === $notice ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Starter pages are ready.', 'minemirth' ); ?></p></div>
		<?php elseif ( 'invalid' === $notice ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'That file did not contain valid Mine Mirth settings.', 'minemirth' ); ?></p></div>
		<?php endif; ?>

		<div class="mm-hero">
			<div>
				<h1><?php esc_html_e( 'Mine Mirth', 'minemirth' ); ?> <small style="font-size:13px;color:#7cc9ff">v<?php echo esc_html( MM_VERSION ); ?></small></h1>
				<p><?php esc_html_e( 'Every color, word, section and layout is editable with live preview. Start with the Customizer, then set up promotions and hosting plans.', 'minemirth' ); ?></p>
			</div>
			<a class="button button-hero" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>"><?php esc_html_e( 'Open the Customizer', 'minemirth' ); ?></a>
		</div>

		<div class="mm-grid">
			<?php foreach ( $panels as $panel ) : ?>
				<a class="mm-card" href="<?php echo esc_url( mm_customize_link( 'panel', $panel[0] ) ); ?>">
					<span class="dashicons dashicons-<?php echo esc_attr( $panel[1] ); ?>"></span>
					<strong><?php echo esc_html( $panel[2] ); ?></strong>
					<span class="d"><?php echo esc_html( $panel[3] ); ?></span>
				</a>
			<?php endforeach; ?>
			<a class="mm-card" href="<?php echo esc_url( admin_url( 'edit.php?post_type=mm_promo' ) ); ?>">
				<span class="dashicons dashicons-megaphone"></span>
				<strong><?php esc_html_e( 'Promotions', 'minemirth' ); ?></strong>
				<span class="d"><?php esc_html_e( 'Targeted promo cards inside articles.', 'minemirth' ); ?></span>
			</a>
			<a class="mm-card" href="<?php echo esc_url( admin_url( 'edit.php?post_type=mm_hosting_plan' ) ); ?>">
				<span class="dashicons dashicons-database"></span>
				<strong><?php esc_html_e( 'Hosting plans', 'minemirth' ); ?></strong>
				<span class="d"><?php esc_html_e( 'Rates, capacity and checkout links.', 'minemirth' ); ?></span>
			</a>
		</div>

		<div class="mm-cols">
			<div class="mm-box">
				<h2><?php esc_html_e( 'Setup checklist', 'minemirth' ); ?></h2>
				<div class="mm-bar"><span style="width:<?php echo esc_attr( (string) round( $done / max( 1, count( $checks ) ) * 100 ) ); ?>%"></span></div>
				<?php foreach ( $checks as $check ) : ?>
					<div class="mm-check">
						<span><span class="dashicons <?php echo $check[1] ? 'dashicons-yes-alt ok' : 'dashicons-marker no'; ?>"></span> <?php echo esc_html( $check[0] ); ?></span>
						<?php if ( ! $check[1] ) : ?>
							<a class="button button-small" href="<?php echo esc_url( $check[2] ); ?>"><?php echo esc_html( $check[3] ); ?></a>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
				<h3 style="margin-top:22px"><?php esc_html_e( 'Shortcodes', 'minemirth' ); ?></h3>
				<p>
					<code>[mm_calculator]</code> <code>[mm_calculator variant="compact"]</code> <code>[mm_hosting_plans]</code> <code>[mm_hosting_calculator]</code> <code>[mm_market]</code>
					<code>[mm_product id="123"]</code> <code>[mm_product ids="1,2,3"]</code> <code>[mm_promo id="45"]</code> <code>[mm_cta title="…" text="…" button="…" url="/miner-hosting/"]</code> <code>[mm_newsletter]</code>
				</p>
				<p class="description"><?php esc_html_e( 'In the block editor, use the “Mine Mirth promo” block to drop product cards, calculators and banners into posts.', 'minemirth' ); ?></p>
			</div>

			<div>
				<div class="mm-box" style="margin-bottom:20px">
					<h2><?php esc_html_e( 'Starter pages', 'minemirth' ); ?></h2>
					<p><?php esc_html_e( 'Creates any missing Miner Hosting, Mining Calculator and Contact pages with the right templates. Existing pages are never changed.', 'minemirth' ); ?></p>
					<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mm_starter_pages' ), 'mm_starter_pages' ) ); ?>"><?php esc_html_e( 'Create starter pages', 'minemirth' ); ?></a>
				</div>

				<div class="mm-box" style="margin-bottom:20px">
					<h2><?php esc_html_e( 'Export settings', 'minemirth' ); ?></h2>
					<p><?php esc_html_e( 'Download every Mine Mirth Customizer setting as a JSON file (backup or move to another site).', 'minemirth' ); ?></p>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mm_export_settings' ), 'mm_export_settings' ) ); ?>"><?php esc_html_e( 'Download JSON', 'minemirth' ); ?></a>
				</div>

				<div class="mm-box" style="margin-bottom:20px">
					<h2><?php esc_html_e( 'Import settings', 'minemirth' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
						<input type="hidden" name="action" value="mm_import_settings">
						<?php wp_nonce_field( 'mm_import_settings' ); ?>
						<p><input type="file" name="mm_settings_file" accept="application/json,.json"></p>
						<p><button class="button"><?php esc_html_e( 'Import', 'minemirth' ); ?></button></p>
					</form>
				</div>

				<div class="mm-box">
					<h2><?php esc_html_e( 'Reset', 'minemirth' ); ?></h2>
					<p><?php esc_html_e( 'Restore every Mine Mirth option to its default. Your logo, menus, posts and products are not affected.', 'minemirth' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Reset all Mine Mirth settings?', 'minemirth' ) ); ?>');">
						<input type="hidden" name="action" value="mm_reset_settings">
						<?php wp_nonce_field( 'mm_reset_settings' ); ?>
						<button class="button button-link-delete"><?php esc_html_e( 'Reset to defaults', 'minemirth' ); ?></button>
					</form>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Export all mm_* theme mods.
 */
function mm_export_settings(): void {
	if ( ! current_user_can( 'edit_theme_options' ) || ! check_admin_referer( 'mm_export_settings' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'minemirth' ) );
	}
	$mods = array();
	foreach ( (array) get_theme_mods() as $key => $value ) {
		if ( 0 === strpos( (string) $key, 'mm_' ) ) {
			$mods[ $key ] = $value;
		}
	}
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=mine-mirth-settings-' . gmdate( 'Y-m-d' ) . '.json' );
	echo wp_json_encode(
		array(
			'theme'    => 'minemirth',
			'version'  => MM_VERSION,
			'exported' => gmdate( 'c' ),
			'mods'     => $mods,
		),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);
	exit;
}
add_action( 'admin_post_mm_export_settings', 'mm_export_settings' );

/**
 * Import settings (every value re-sanitized through the schema).
 */
function mm_import_settings(): void {
	if ( ! current_user_can( 'edit_theme_options' ) || ! check_admin_referer( 'mm_import_settings' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'minemirth' ) );
	}
	$back = admin_url( 'themes.php?page=mine-mirth' );
	if ( empty( $_FILES['mm_settings_file']['tmp_name'] ) ) {
		wp_safe_redirect( add_query_arg( 'mm_notice', 'invalid', $back ) );
		exit;
	}
	$raw  = file_get_contents( $_FILES['mm_settings_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.ValidatedSanitizedInput
	$data = json_decode( (string) $raw, true );
	if ( ! is_array( $data ) || empty( $data['mods'] ) || ! is_array( $data['mods'] ) ) {
		wp_safe_redirect( add_query_arg( 'mm_notice', 'invalid', $back ) );
		exit;
	}
	$schema = mm_options_schema();
	foreach ( $data['mods'] as $key => $value ) {
		$field = preg_replace( '/^mm_/', '', (string) $key );
		if ( isset( $schema[ $field ] ) && ! in_array( $schema[ $field ]['type'], array( 'heading', 'presets' ), true ) ) {
			set_theme_mod( 'mm_' . $field, mm_sanitize_value( $value, $schema[ $field ] ) );
		}
	}
	wp_safe_redirect( add_query_arg( 'mm_notice', 'imported', $back ) );
	exit;
}
add_action( 'admin_post_mm_import_settings', 'mm_import_settings' );

/**
 * Reset all options.
 */
function mm_reset_settings(): void {
	if ( ! current_user_can( 'edit_theme_options' ) || ! check_admin_referer( 'mm_reset_settings' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'minemirth' ) );
	}
	foreach ( array_keys( (array) get_theme_mods() ) as $key ) {
		if ( 0 === strpos( (string) $key, 'mm_' ) ) {
			remove_theme_mod( $key );
		}
	}
	wp_safe_redirect( add_query_arg( 'mm_notice', 'reset', admin_url( 'themes.php?page=mine-mirth' ) ) );
	exit;
}
add_action( 'admin_post_mm_reset_settings', 'mm_reset_settings' );

/**
 * Create missing starter pages.
 */
function mm_create_starter_pages(): void {
	if ( ! current_user_can( 'publish_pages' ) || ! check_admin_referer( 'mm_starter_pages' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'minemirth' ) );
	}
	$pages = array(
		array( 'miner-hosting', __( 'Miner Hosting', 'minemirth' ), 'page-templates/hosting.php', 0 ),
		array( 'free-calculators', __( 'Free Calculators', 'minemirth' ), 'page-templates/calculator.php', 0 ),
		array( 'contact-us', __( 'Contact Us', 'minemirth' ), 'page-templates/contact.php', 0 ),
	);
	foreach ( $pages as $page ) {
		$existing = get_page_by_path( $page[0] );
		if ( $existing ) {
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $page[1],
				'post_name'   => $page[0],
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_wp_page_template', $page[2] );
		}
	}
	// Bitcoin calculator child page (matches the existing site's URL).
	$parent = get_page_by_path( 'free-calculators' );
	if ( $parent && ! get_page_by_path( 'free-calculators/bitcoin-mining-calculator' ) ) {
		$id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => __( 'Bitcoin Mining Calculator', 'minemirth' ),
				'post_name'   => 'bitcoin-mining-calculator',
				'post_parent' => $parent->ID,
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_wp_page_template', 'page-templates/calculator.php' );
		}
	}
	wp_safe_redirect( add_query_arg( 'mm_notice', 'pages', admin_url( 'themes.php?page=mine-mirth' ) ) );
	exit;
}
add_action( 'admin_post_mm_starter_pages', 'mm_create_starter_pages' );

/**
 * Point new installs at the setup screen once.
 */
function mm_activation_notice(): void {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! current_user_can( 'edit_theme_options' ) || ( $screen && 'appearance_page_mine-mirth' === $screen->id ) || get_option( 'mm_notice_dismissed' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-info is-dismissible mm-welcome-notice"><p><strong>%1$s</strong> %2$s <a class="button button-primary" style="margin-left:8px" href="%3$s">%4$s</a> <a style="margin-left:8px" href="%5$s">%6$s</a></p></div>',
		esc_html__( 'Mine Mirth is active.', 'minemirth' ),
		esc_html__( 'Finish setup in a few clicks.', 'minemirth' ),
		esc_url( admin_url( 'themes.php?page=mine-mirth' ) ),
		esc_html__( 'Open setup', 'minemirth' ),
		esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mm_dismiss_notice' ), 'mm_dismiss_notice' ) ),
		esc_html__( 'Dismiss', 'minemirth' )
	);
}
add_action( 'admin_notices', 'mm_activation_notice' );

add_action(
	'admin_post_mm_dismiss_notice',
	static function () {
		if ( current_user_can( 'edit_theme_options' ) && check_admin_referer( 'mm_dismiss_notice' ) ) {
			update_option( 'mm_notice_dismissed', 1, false );
		}
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}
);

/**
 * Admin bar shortcut.
 */
function mm_admin_bar( WP_Admin_Bar $bar ): void {
	if ( ! current_user_can( 'edit_theme_options' ) || is_admin() ) {
		return;
	}
	$bar->add_node(
		array(
			'id'    => 'mm-theme',
			'title' => '<span class="ab-icon dashicons dashicons-admin-appearance" style="top:2px"></span>' . esc_html__( 'Mine Mirth', 'minemirth' ),
			'href'  => admin_url( 'themes.php?page=mine-mirth' ),
		)
	);
	$bar->add_node(
		array(
			'parent' => 'mm-theme',
			'id'     => 'mm-theme-customize',
			'title'  => esc_html__( 'Customize this page', 'minemirth' ),
			'href'   => add_query_arg( 'url', rawurlencode( home_url( add_query_arg( array() ) ) ), admin_url( 'customize.php' ) ),
		)
	);
}
add_action( 'admin_bar_menu', 'mm_admin_bar', 80 );
