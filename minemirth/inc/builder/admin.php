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
			.mm-builder .mm-path{color:#646970;font-family:monospace;font-size:12px}
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
				<label><input type="checkbox" name="front" value="1" checked> <?php esc_html_e( 'Use the new Home page as the homepage', 'minemirth' ); ?></label>
				<label><input type="checkbox" name="menus" value="1" checked> <?php esc_html_e( 'Build the main menu (with mega menus) and the footer menus', 'minemirth' ); ?></label>
				<label><input type="checkbox" name="assign" value="1" checked> <?php esc_html_e( 'Show those menus in the header and footer (replaces the menus assigned now; your old menus are not deleted)', 'minemirth' ); ?></label>
				<?php if ( $retired ) : ?>
					<label><input type="checkbox" name="retire" value="1" checked> <?php esc_html_e( 'Retire the old tool pages: move them to drafts and send visitors to the new tools (301 redirect)', 'minemirth' ); ?></label>
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
						<thead><tr><td class="check-column" style="padding-left:10px"><input type="checkbox" onclick="this.closest('table').querySelectorAll('tbody input[type=checkbox]:not(:disabled)').forEach(c=>c.checked=this.checked)"></td><th><?php esc_html_e( 'Page', 'minemirth' ); ?></th><th><?php esc_html_e( 'Address', 'minemirth' ); ?></th><th><?php esc_html_e( 'What happens', 'minemirth' ); ?></th></tr></thead>
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
			} elseif ( 'updated' === $r[1] ) {
				++$counts['updated'];
			} else {
				$counts['errors'][] = $key . ': ' . $r[1];
			}
		}
		$ids = (array) get_option( 'mm_builder_ids', array() );
		if ( ! empty( $_POST['front'] ) && ! empty( $ids['home'] ) ) {
			mm_builder_front_page( (int) $ids['home'] );
		}
		if ( ! empty( $_POST['menus'] ) ) {
			$counts['menus'] = (bool) mm_builder_menus( $ids, ! empty( $_POST['assign'] ) );
		}
		if ( ! empty( $_POST['retire'] ) ) {
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
