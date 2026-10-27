<?php
/**
 * Site builder screen: Appearance › Site builder.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	static function () {
		add_theme_page( __( 'Mine Mirth site builder', 'minemirth' ), __( 'Site builder', 'minemirth' ), 'edit_theme_options', 'mm-site-builder', 'mm_builder_screen' );
	}
);

function mm_builder_state_label( string $state ): array {
	if ( mm_is_staging() && in_array( $state, array( 'replace', 'update' ), true ) ) {
		return array( __( 'Live page stays as it is; the new version is saved privately until you go live', 'minemirth' ), '#2271b1' );
	}
	if ( mm_is_staging() && 'create' === $state ) {
		return array( __( 'New private page (only you can see it until you go live)', 'minemirth' ), '#12b981' );
	}
	return array(
		'create'    => array( __( 'New page', 'minemirth' ), '#12b981' ),
		'update'    => array( __( 'Update (not edited since the last build)', 'minemirth' ), '#2271b1' ),
		'edited'    => array( __( 'You edited this page: kept unless ticked', 'minemirth' ), '#b26200' ),
		'replace'   => array( __( 'Existing page: content replaced, old version kept in Revisions', 'minemirth' ), '#2271b1' ),
		'keep'      => array( __( 'Your existing page is kept unless ticked', 'minemirth' ), '#b26200' ),
		'protected' => array( __( 'WooCommerce or blog page: never changed', 'minemirth' ), '#8c8f94' ),
	)[ $state ] ?? array( $state, '#8c8f94' );
}

function mm_builder_screen(): void {
	if ( ! current_user_can( 'edit_theme_options' ) || ! current_user_can( 'publish_pages' ) ) {
		return;
	}
	$plan    = mm_builder_plan();
	$seo_stats = mm_builder_seo_stats();
	$retired = mm_builder_retired();
	$last    = get_option( 'mm_builder_last' );
	$result  = get_transient( 'mm_builder_result_' . get_current_user_id() );
	$groups  = array(
		__( 'Core pages', 'minemirth' )        => array( 'home', 'about', 'contact', 'why', 'faq', 'reviews', 'payments', 'shipping', 'warranty', 'returns' ),
		__( 'Services', 'minemirth' )          => array( 'services', 'hosting', 'repairs', 'setup', 'tradein', 'wholesale', 'consulting', 'business' ),
		__( 'Miners', 'minemirth' )            => array_values( array_filter( array_keys( $plan ), static fn( $k ) => 'miners' === $k || 'brands' === $k || 0 === strpos( $k, 'm-' ) || 0 === strpos( $k, 'brand-' ) ) ),
		__( 'Tools', 'minemirth' )             => array_values( array_filter( array_keys( $plan ), static fn( $k ) => 'tools' === $k || 0 === strpos( $k, 'tool-' ) ) ),
		__( 'Learn', 'minemirth' )             => array_values( array_filter( array_keys( $plan ), static fn( $k ) => 'learn' === $k || 0 === strpos( $k, 'g-' ) ) ),
		__( 'Company & legal', 'minemirth' )   => array( 'market', 'partners', 'privacy', 'terms' ),
	);
	$counts = array_count_values( array_column( $plan, 'state' ) );
	?>
	<div class="wrap mm-builder">
		<hr class="wp-header-end" style="display:none">
		<style>
			.mm-builder{max-width:1180px}
			.mm-builder .mm-bhero{margin:20px 0;padding:28px 32px;border-radius:16px;color:#e7f0fa;background:radial-gradient(60% 120% at 100% 0%,rgba(53,168,246,.45),transparent 60%),linear-gradient(160deg,#0a1b2f,#040b15)}
			.mm-builder .mm-bhero h1{color:#fff;font-size:26px;margin:0 0 8px;padding:0}
			.mm-builder .mm-bhero p{margin:0;color:#9fb6cf;font-size:14px;max-width:760px}
			.mm-builder .mm-bstats{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}
			.mm-builder .mm-bstats span{padding:6px 12px;border-radius:99px;background:rgba(255,255,255,.08);font-size:13px}
			.mm-builder .mm-bbox{margin:0 0 18px;padding:18px 22px;border:1px solid #dcdcde;border-radius:12px;background:#fff}
			.mm-builder .mm-bbox h2{margin:0 0 12px;font-size:16px}
			.mm-builder table.widefat td,.mm-builder table.widefat th{vertical-align:middle}
			.mm-builder .mm-state{display:inline-block;padding:2px 8px;border-radius:99px;font-size:12px;color:#fff}
			.mm-builder .mm-path{color:#646970;font-family:monospace;font-size:12px}.mm-builder .mm-seo__kw{display:block;font-weight:600}.mm-builder .mm-seo__nums{font-size:12px;font-family:monospace}
			.mm-builder .mm-opts label{display:block;margin:6px 0}
			.mm-builder .mm-sticky{position:sticky;bottom:0;z-index:5;display:flex;gap:12px;align-items:center;padding:14px 18px;border:1px solid #dcdcde;border-radius:12px;background:#fff;box-shadow:0 -6px 24px rgba(0,0,0,.06)}
			.mm-builder details summary{cursor:pointer;font-weight:600}
		</style>

		<div class="mm-bhero">
			<h1><?php esc_html_e( 'Build the Mine Mirth website', 'minemirth' ); ?></h1>
			<p><?php esc_html_e( 'Creates 80 premium pages (home, services, miner collections, brands, 12 live tools, 24 guides and company pages), the main and footer menus, and sets the new homepage. Every page is made of editable blocks. Run it again after a theme update to refresh pages you have not edited.', 'minemirth' ); ?></p>
			<div class="mm-bstats">
				<span><?php echo esc_html( sprintf( /* translators: %d: count */ __( '%d new', 'minemirth' ), (int) ( $counts['create'] ?? 0 ) ) ); ?></span>
				<span><?php echo esc_html( sprintf( /* translators: %d: count */ __( '%d to update', 'minemirth' ), (int) ( $counts['update'] ?? 0 ) + (int) ( $counts['replace'] ?? 0 ) ) ); ?></span>
				<span><?php echo esc_html( sprintf( /* translators: %d: count */ __( '%d kept (edited by you or your policies)', 'minemirth' ), (int) ( $counts['edited'] ?? 0 ) + (int) ( $counts['keep'] ?? 0 ) ) ); ?></span>
				<?php if ( $last ) : ?>
					<span><?php echo esc_html( sprintf( /* translators: 1: date, 2: version */ __( 'Last build %1$s (theme %2$s)', 'minemirth' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $last['time'] ), $last['version'] ) ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<?php
		$mm_staging = mm_is_staging();
		$mm_live    = get_transient( 'mm_go_live_result_' . get_current_user_id() );
		if ( is_array( $mm_live ) ) :
			delete_transient( 'mm_go_live_result_' . get_current_user_id() );
			?>
			<div class="notice notice-success"><p><strong><?php esc_html_e( 'Mine Mirth 5 is live.', 'minemirth' ); ?></strong> <?php echo esc_html( sprintf( /* translators: 1: applied, 2: published, 3: retired */ __( '%1$d pages updated, %2$d new pages published, %3$d old tool pages retired with redirects.', 'minemirth' ), (int) $mm_live['applied'], (int) $mm_live['published'], (int) $mm_live['retired'] ) ); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank"><?php esc_html_e( 'View the site', 'minemirth' ); ?></a></p></div>
		<?php endif; ?>
		<?php if ( $mm_staging ) : ?>
			<?php
			$mm_staged_n  = count( get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_mm_staged' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
			$mm_private_n = count( get_posts( array( 'post_type' => 'page', 'post_status' => 'private', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_mm_build_version' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
			?>
			<div class="mm-bbox" id="mm-go-live" style="border-color:#35a8f6;box-shadow:0 0 0 3px rgba(53,168,246,.15)">
				<h2><?php esc_html_e( 'Test mode: you are building privately', 'minemirth' ); ?></h2>
				<p><?php esc_html_e( 'Visitors still see your current theme and your current pages. New pages are private, and changes to existing pages are saved inside them as drafts that only you see. Browse the site and edit pages as usual; when everything looks right, go live.', 'minemirth' ); ?></p>
				<p><strong><?php echo esc_html( sprintf( /* translators: 1: private pages, 2: staged pages */ __( 'Ready to publish: %1$d new pages and %2$d updated pages.', 'minemirth' ), $mm_private_n, $mm_staged_n ) ); ?></strong></p>
				<?php if ( $mm_private_n + $mm_staged_n > 0 ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Publish the new pages and activate Mine Mirth 5 for every visitor?', 'minemirth' ) ); ?>');">
						<input type="hidden" name="action" value="mm_builder_go_live"><?php wp_nonce_field( 'mm_builder_go_live' ); ?>
						<label style="display:block;margin:6px 0"><input type="checkbox" name="retire" value="1" checked> <?php esc_html_e( 'Retire the old tool pages and redirect them to the new tools', 'minemirth' ); ?></label>
						<p style="margin:10px 0 0"><button class="button button-primary button-hero"><?php esc_html_e( 'Go live with Mine Mirth 5', 'minemirth' ); ?></button>
						<span class="description" style="margin-left:10px"><?php esc_html_e( 'Every current address is remembered first, so old links keep working. Replaced content stays in Revisions.', 'minemirth' ); ?></span></p>
					</form>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'Build the pages below first; the Go live button appears here afterwards.', 'minemirth' ); ?></p>
				<?php endif; ?>
			</div>
		<?php elseif ( ! function_exists( 'mml_testdrive_active' ) && get_option( 'stylesheet' ) !== 'minemirth-5' ) : ?>
			<div class="notice notice-info"><p><?php esc_html_e( 'Tip: install the Mine Mirth Launch plugin to test Mine Mirth 5 privately before visitors see it.', 'minemirth' ); ?></p></div>
		<?php endif; ?>

		<?php if ( is_array( $result ) ) : ?>
			<?php delete_transient( 'mm_builder_result_' . get_current_user_id() ); ?>
			<div class="notice notice-success"><p><strong><?php echo esc_html( sprintf( /* translators: 1: created, 2: updated */ __( 'Done: %1$d pages created, %2$d updated.', 'minemirth' ), (int) $result['created'], (int) $result['updated'] ) ); ?></strong>
			<?php
			if ( ! empty( $result['menus'] ) ) {
				echo ' ' . esc_html__( 'Menus built.', 'minemirth' );
			}
			if ( ! empty( $result['retired'] ) ) {
				/* translators: %d: count */
				echo ' ' . esc_html( sprintf( __( '%d old tool pages moved to drafts and redirected.', 'minemirth' ), (int) $result['retired'] ) );
			}
			?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank"><?php esc_html_e( 'View the site', 'minemirth' ); ?></a></p>
			<?php if ( ! empty( $result['errors'] ) ) : ?>
				<p><?php echo esc_html( implode( ' · ', $result['errors'] ) ); ?></p>
			<?php endif; ?>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mm_builder_run">
			<?php wp_nonce_field( 'mm_builder_run' ); ?>

			<div class="mm-bbox mm-opts">
				<h2><?php esc_html_e( 'Options', 'minemirth' ); ?></h2>
				<label><input type="checkbox" name="front" value="1" checked> <?php echo esc_html( mm_is_staging() ? __( 'Use the new Home page as the homepage (for you now, for everyone when you go live)', 'minemirth' ) : __( 'Use the new Home page as the homepage', 'minemirth' ) ); ?></label>
				<label><input type="checkbox" name="menus" value="1" checked> <?php esc_html_e( 'Build the main menu (with mega menus) and the footer menus', 'minemirth' ); ?></label>
				<label><input type="checkbox" name="assign" value="1" checked> <?php esc_html_e( 'Show those menus in the header and footer (replaces the menus assigned now; your old menus are not deleted)', 'minemirth' ); ?></label>
				<?php if ( $retired ) : ?>
					<?php if ( mm_is_staging() ) : ?>
						<p class="description"><?php esc_html_e( 'The old tool pages below are retired (with redirects) when you go live:', 'minemirth' ); ?></p>
					<?php else : ?>
						<label><input type="checkbox" name="retire" value="1" checked> <?php esc_html_e( 'Retire the old tool pages: move them to drafts and send visitors to the new tools (301 redirect)', 'minemirth' ); ?></label>
					<?php endif; ?>
					<ul style="margin:4px 0 0 26px;list-style:disc">
						<?php foreach ( $retired as $row ) : ?>
							<li><?php echo esc_html( $row[0]->post_title ); ?> <span class="mm-path"><?php echo esc_html( wp_parse_url( get_permalink( $row[0] ), PHP_URL_PATH ) ); ?></span> → <?php echo esc_html( mm_builder_pages()[ $row[1] ]['t'] ?? $row[1] ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<?php foreach ( $groups as $label => $keys ) : ?>
				<div class="mm-bbox">
					<h2><?php echo esc_html( $label ); ?> <span style="color:#8c8f94;font-weight:400">(<?php echo count( $keys ); ?>)</span></h2>
					<table class="widefat striped">
						<thead><tr><td class="check-column" style="padding-left:10px"><input type="checkbox" onclick="this.closest('table').querySelectorAll('tbody input[type=checkbox]:not(:disabled)').forEach(c=>c.checked=this.checked)"></td><th><?php esc_html_e( 'Page', 'minemirth' ); ?></th><th><?php esc_html_e( 'Address', 'minemirth' ); ?></th><th><?php esc_html_e( 'Focus keyword · density · words', 'minemirth' ); ?></th><th><?php esc_html_e( 'What happens', 'minemirth' ); ?></th></tr></thead>
						<tbody>
						<?php
						foreach ( $keys as $key ) :
							if ( empty( $plan[ $key ] ) ) {
								continue;
							}
							$row   = $plan[ $key ];
							$state = mm_builder_state_label( $row['state'] );
							$path  = $row['post'] ? (string) wp_parse_url( get_permalink( $row['post'] ), PHP_URL_PATH ) : '/' . $row['def']['p'] . '/';
							if ( 'home' === $key ) {
								$path = '/ ' . __( '(homepage)', 'minemirth' );
							}
							?>
							<tr>
								<th scope="row" class="check-column" style="padding-left:10px"><input type="checkbox" name="pages[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $row['checked'] ); ?> <?php disabled( 'protected' === $row['state'] ); ?>></th>
								<td><strong><?php echo esc_html( $row['def']['t'] ); ?></strong><?php if ( $row['post'] ) : ?> · <a href="<?php echo esc_url( get_permalink( $row['post'] ) ); ?>" target="_blank"><?php esc_html_e( 'view', 'minemirth' ); ?></a> · <a href="<?php echo esc_url( get_edit_post_link( $row['post'] ) ); ?>"><?php esc_html_e( 'edit', 'minemirth' ); ?></a><?php endif; ?></td>
								<td class="mm-path"><?php echo esc_html( $path ); ?></td>
								<td class="mm-seo">
									<?php
									$seo = $seo_stats[ $key ] ?? null;
									if ( $seo ) :
										$ok = $seo['density'] >= 0.5 && $seo['density'] <= 2.5;
										?>
										<span class="mm-seo__kw"><?php echo esc_html( $seo['kw'] ); ?></span>
										<span class="mm-seo__nums" style="color:<?php echo $ok ? '#00a32a' : '#b26200'; ?>" title="<?php echo esc_attr( sprintf( /* translators: 1: times used, 2: words */ __( 'Used %1$d times in %2$d words. Healthy range: 0.5%% to 2.5%%.', 'minemirth' ), $seo['count'], $seo['words'] ) ); ?>"><?php echo esc_html( number_format_i18n( $seo['density'], 1 ) . '% · ' . number_format_i18n( $seo['words'] ) ); ?></span>
									<?php else : ?>
										<span style="color:#8c8f94">·</span>
									<?php endif; ?>
								</td>
								<td><span class="mm-state" style="background:<?php echo esc_attr( $state[1] ); ?>"><?php echo esc_html( $state[0] ); ?></span></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>

			<div class="mm-sticky">
				<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Build the ticked pages', 'minemirth' ); ?></button>
				<span class="description"><?php esc_html_e( 'Takes a few seconds. Nothing is deleted: replaced content stays in each page’s Revisions.', 'minemirth' ); ?></span>
			</div>
		</form>
	</div>
	<?php
}

add_action(
	'admin_post_mm_builder_run',
	static function () {
		if ( ! current_user_can( 'edit_theme_options' ) || ! current_user_can( 'publish_pages' ) || ! check_admin_referer( 'mm_builder_run' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'minemirth' ) );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		$keys   = array_map( 'sanitize_key', (array) wp_unslash( $_POST['pages'] ?? array() ) );
		$out    = mm_builder_run( $keys );
		$counts = array( 'created' => 0, 'updated' => 0, 'errors' => array(), 'menus' => false, 'retired' => 0 );
		foreach ( $out as $key => $r ) {
			if ( 'created' === $r[1] ) {
				++$counts['created'];
			} elseif ( 'updated' === $r[1] || 'staged' === $r[1] ) {
				++$counts['updated'];
			} else {
				$counts['errors'][] = $key . ': ' . $r[1];
			}
		}
		$ids = (array) get_option( 'mm_builder_ids', array() );
		if ( ! empty( $_POST['front'] ) && ! empty( $ids['home'] ) ) {
			if ( mm_is_staging() ) {
				update_option( 'mm_staged_front', (int) $ids['home'], false ); // Shown to you now, to everyone at Go live.
				set_theme_mod( 'mm_home_mode', 'content' ); // Mine Mirth 5's own settings only.
			} else {
				mm_builder_front_page( (int) $ids['home'] );
			}
		}
		if ( ! empty( $_POST['menus'] ) ) {
			$counts['menus'] = (bool) mm_builder_menus( $ids, ! empty( $_POST['assign'] ) );
		}
		if ( ! empty( $_POST['retire'] ) && ! mm_is_staging() ) {
			$counts['retired'] = mm_builder_retire( mm_builder_retired() );
		}
		if ( function_exists( 'mm_catalog_flush' ) ) {
			mm_catalog_flush();
		}
		flush_rewrite_rules( false );
		do_action( 'litespeed_purge_all' );
		set_transient( 'mm_builder_result_' . get_current_user_id(), $counts, HOUR_IN_SECONDS );
		wp_safe_redirect( admin_url( 'themes.php?page=mm-site-builder' ) );
		exit;
	}
);

add_action(
	'admin_post_mm_builder_go_live',
	static function () {
		if ( ! current_user_can( 'switch_themes' ) || ! current_user_can( 'publish_pages' ) || ! check_admin_referer( 'mm_builder_go_live' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'minemirth' ) );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		$sum = mm_builder_go_live( ! empty( $_POST['retire'] ) );
		set_transient( 'mm_go_live_result_' . get_current_user_id(), $sum, HOUR_IN_SECONDS );
		wp_safe_redirect( admin_url( 'themes.php?page=mm-site-builder' ) );
		exit;
	}
);

// One-time pointer after switching to this version.
add_action(
	'admin_notices',
	static function () {
		if ( ! current_user_can( 'edit_theme_options' ) || get_option( 'mm_builder_last' ) || ( isset( $_GET['page'] ) && 'mm-site-builder' === $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Mine Mirth 5: your new 80-page website is ready to build.', 'minemirth' ) . '</strong> ' . esc_html__( 'Preview every page first; nothing changes until you click Build.', 'minemirth' ) . ' <a class="button button-primary" href="' . esc_url( admin_url( 'themes.php?page=mm-site-builder' ) ) . '">' . esc_html__( 'Open the site builder', 'minemirth' ) . '</a></p></div>';
	}
);
