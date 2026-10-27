<?php
/**
 * Launch screen: test drive, launch checklist, redirects, URL check and 404 log.
 *
 * @package MineMirthLaunch
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	static function () {
		add_menu_page( __( 'Mine Mirth Launch', 'mine-mirth-launch' ), __( 'Launch', 'mine-mirth-launch' ), 'manage_options', 'mine-mirth-launch', 'mml_screen', 'dashicons-migrate', 59 );
	}
);

function mml_admin_url( string $tab = '', array $args = array() ): string {
	return add_query_arg( array_merge( array( 'page' => 'mine-mirth-launch' ), $tab ? array( 'tab' => $tab ) : array(), $args ), admin_url( 'admin.php' ) );
}

function mml_reason_label( string $reason ): string {
	return array(
		'rule'     => __( 'Your rule', 'mine-mirth-launch' ),
		'retired'  => __( 'Retired by the site builder', 'mine-mirth-launch' ),
		'moved'    => __( 'Same page, new address', 'mine-mirth-launch' ),
		'replaced' => __( 'Replacement page', 'mine-mirth-launch' ),
		'parent'   => __( 'Parent page', 'mine-mirth-launch' ),
		'category' => __( 'Its category', 'mine-mirth-launch' ),
		'slug'     => __( 'Same name elsewhere', 'mine-mirth-launch' ),
		'topic'    => __( 'Page on the same topic', 'mine-mirth-launch' ),
		'search'   => __( 'Best matching page', 'mine-mirth-launch' ),
		'section'  => __( 'Closest section', 'mine-mirth-launch' ),
	)[ $reason ] ?? $reason;
}

function mml_screen(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification
	$tabs = array(
		'overview'  => __( 'Launch', 'mine-mirth-launch' ),
		'redirects' => __( 'Redirects', 'mine-mirth-launch' ),
		'check'     => __( 'URL check', 'mine-mirth-launch' ),
		'log'       => __( '404 log', 'mine-mirth-launch' ),
		'settings'  => __( 'Settings', 'mine-mirth-launch' ),
	);
	$msg = isset( $_GET['mml_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['mml_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="wrap mml">
		<hr class="wp-header-end" style="display:none">
		<style>
			.mml{max-width:1180px}
			.mml .mml-hero{margin:20px 0 16px;padding:26px 30px;border-radius:16px;color:#e7f0fa;background:radial-gradient(60% 120% at 100% 0%,rgba(53,168,246,.45),transparent 60%),linear-gradient(160deg,#0a1b2f,#040b15)}
			.mml .mml-hero h1{color:#fff;font-size:24px;margin:0 0 6px;padding:0}
			.mml .mml-hero p{margin:0;color:#9fb6cf;max-width:760px;font-size:14px}
			.mml .nav-tab-wrapper{margin-bottom:16px}
			.mml .mml-box{margin:0 0 16px;padding:18px 22px;border:1px solid #dcdcde;border-radius:12px;background:#fff}
			.mml .mml-box h2{margin:0 0 10px;font-size:16px}
			.mml .mml-steps{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}
			.mml .mml-step{position:relative;padding:16px 16px 16px 54px;border:1px solid #dcdcde;border-radius:12px;background:#fff}
			.mml .mml-step b{display:block;margin-bottom:4px;font-size:14px}
			.mml .mml-step .n{position:absolute;left:14px;top:14px;display:grid;place-items:center;width:28px;height:28px;border-radius:50%;background:#f0f0f1;font-weight:700}
			.mml .mml-step.is-done .n{background:#12b981;color:#fff}
			.mml .mml-step.is-now{border-color:#35a8f6;box-shadow:0 0 0 3px rgba(53,168,246,.15)}
			.mml .mml-stats{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px}
			.mml .mml-stats span{padding:6px 12px;border-radius:99px;background:rgba(255,255,255,.08);font-size:13px}
			.mml .mml-path{font-family:monospace;font-size:12px;word-break:break-all}
			.mml .mml-ok{color:#12b981}.mml .mml-warn{color:#b26200}.mml .mml-bad{color:#d63638}
			.mml table.widefat td{vertical-align:middle}
			.mml .mml-inline{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
			.mml .mml-inline input[type=text]{min-width:220px}
		</style>
		<div class="mml-hero">
			<h1><?php esc_html_e( 'Mine Mirth Launch', 'mine-mirth-launch' ); ?></h1>
			<p><?php esc_html_e( 'Test Mine Mirth 5 privately, build the new pages without touching the live site, then launch with every old address sent to the right place.', 'mine-mirth-launch' ); ?></p>
			<div class="mml-stats">
				<span><?php echo esc_html( sprintf( /* translators: %s: number */ __( '%s addresses remembered', 'mine-mirth-launch' ), number_format_i18n( mml_memory_count() ) ) ); ?></span>
				<span><?php echo esc_html( sprintf( /* translators: %s: number */ __( '%s redirect rules', 'mine-mirth-launch' ), number_format_i18n( count( mml_rules() ) + count( (array) get_option( 'mm_redirects', array() ) ) ) ) ); ?></span>
				<?php $last = (int) get_option( 'mml_last_scan' ); ?>
				<span><?php echo esc_html( $last ? sprintf( /* translators: %s: date */ __( 'Last scan %s', 'mine-mirth-launch' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last ) ) : __( 'Not scanned yet', 'mine-mirth-launch' ) ); ?></span>
			</div>
		</div>
		<?php if ( $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div>
		<?php endif; ?>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $k => $label ) : ?>
				<a class="nav-tab<?php echo $k === $tab ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( mml_admin_url( $k ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
		$fn = 'mml_tab_' . $tab;
		if ( function_exists( $fn ) ) {
			$fn();
		}
		?>
	</div>
	<?php
}

function mml_tab_overview(): void {
	$cfg       = mml_testdrive_config();
	$theme     = wp_get_theme( $cfg['theme'] );
	$installed = $theme->exists();
	$live      = mml_live_stylesheet() === $cfg['theme'];
	$on        = mml_testdrive_active();
	$built     = (bool) get_option( 'mm_builder_last' );
	$steps     = array(
		array( $installed, __( 'Install Mine Mirth 5', 'mine-mirth-launch' ), $installed ? sprintf( /* translators: %s: version */ __( 'Installed (version %s). It sits next to your current theme; nothing changes for visitors.', 'mine-mirth-launch' ), $theme->get( 'Version' ) ) : __( 'Appearance › Themes › Add New › Upload Theme, choose minemirth-5.zip and click Install. Do not activate it.', 'mine-mirth-launch' ), $installed ? '' : admin_url( 'theme-install.php' ), __( 'Upload theme', 'mine-mirth-launch' ) ),
		array( $on || $live, __( 'Turn on test drive', 'mine-mirth-launch' ), $live ? __( 'Mine Mirth 5 is live.', 'mine-mirth-launch' ) : ( $on ? __( 'On: you see Mine Mirth 5 everywhere (site, admin, editor). Visitors still see your current theme.', 'mine-mirth-launch' ) : __( 'Only you will see Mine Mirth 5, on the site and in wp-admin. Visitors keep the current theme.', 'mine-mirth-launch' ) ), $installed && ! $live ? mml_testdrive_toggle_url( ! $on ) : '', $on ? __( 'Turn test drive off', 'mine-mirth-launch' ) : __( 'Start test drive', 'mine-mirth-launch' ) ),
		array( $built, __( 'Build the new pages privately', 'mine-mirth-launch' ), __( 'In test drive, Appearance › Site builder creates new pages as private and saves changes to existing pages as drafts inside them. Visitors see nothing until you launch.', 'mine-mirth-launch' ), $on ? admin_url( 'themes.php?page=mm-site-builder' ) : '', __( 'Open the Site builder', 'mine-mirth-launch' ) ),
		array( mml_memory_count() > 0, __( 'Remember every current address', 'mine-mirth-launch' ), __( 'Done automatically (and again just before launch): every page, post, product, category and tag address is saved, so old links keep working after the change.', 'mine-mirth-launch' ), wp_nonce_url( admin_url( 'admin-post.php?action=mml_scan' ), 'mml_scan' ), __( 'Scan now', 'mine-mirth-launch' ) ),
		array( $live, __( 'Go live', 'mine-mirth-launch' ), __( 'When you are happy, click "Go live" at the top of the Site builder. It publishes the pages, applies the changes, retires the old tool pages and activates Mine Mirth 5 for everyone.', 'mine-mirth-launch' ), $on ? admin_url( 'themes.php?page=mm-site-builder#mm-go-live' ) : '', __( 'Go to Go live', 'mine-mirth-launch' ) ),
		array( false, __( 'Watch the 404 log for a week', 'mine-mirth-launch' ), __( 'Any address that still cannot be matched shows up in the 404 log with a suggested page. Fix it with one click.', 'mine-mirth-launch' ), mml_admin_url( 'log' ), __( 'Open the 404 log', 'mine-mirth-launch' ) ),
	);
	$current = 0;
	foreach ( $steps as $i => $s ) {
		if ( ! $s[0] ) {
			$current = $i;
			break;
		}
	}
	echo '<div class="mml-steps">';
	foreach ( $steps as $i => $s ) {
		echo '<div class="mml-step' . ( $s[0] ? ' is-done' : '' ) . ( $i === $current ? ' is-now' : '' ) . '"><span class="n">' . ( $s[0] ? '✓' : (int) ( $i + 1 ) ) . '</span><b>' . esc_html( $s[1] ) . '</b><p style="margin:0 0 10px;color:#50575e">' . esc_html( $s[2] ) . '</p>' . ( $s[3] ? '<a class="button' . ( $i === $current ? ' button-primary' : '' ) . '" href="' . esc_url( $s[3] ) . '">' . esc_html( $s[4] ) . '</a>' : '' ) . '</div>';
	}
	echo '</div>';
	mml_tab_test();
}

function mml_tab_test(): void {
	$q = isset( $_GET['test'] ) ? sanitize_text_field( wp_unslash( $_GET['test'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="mml-box" style="margin-top:16px">
		<h2><?php esc_html_e( 'Test an old address', 'mine-mirth-launch' ); ?></h2>
		<form method="get" class="mml-inline">
			<input type="hidden" name="page" value="mine-mirth-launch">
			<input type="hidden" name="tab" value="<?php echo esc_attr( isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview' ); // phpcs:ignore WordPress.Security.NonceVerification ?>">
			<input type="text" name="test" class="regular-text" value="<?php echo esc_attr( $q ); ?>" placeholder="/old-page/ or https://minemirth.com/old-page/">
			<button class="button"><?php esc_html_e( 'Where does it go?', 'mine-mirth-launch' ); ?></button>
		</form>
		<?php
		if ( '' !== $q ) {
			$path = mml_normalize_path( $q );
			$id   = url_to_postid( home_url( '/' . $path . '/' ) );
			if ( $id && 'publish' === get_post_status( $id ) && mml_normalize_path( (string) get_permalink( $id ) ) === $path ) {
				echo '<p class="mml-ok">' . esc_html__( 'This address works as it is (no redirect needed).', 'mine-mirth-launch' ) . '</p>';
			} else {
				$r = mml_resolve( $path );
				echo $r ? '<p class="mml-ok">→ <a href="' . esc_url( $r[0] ) . '" target="_blank">' . esc_html( $r[0] ) . '</a> <em>(' . esc_html( mml_reason_label( $r[1] ) . ', ' . $r[2] ) . ')</em></p>' : '<p class="mml-bad">' . esc_html__( 'No match: this address would show a 404. Add a rule in Redirects.', 'mine-mirth-launch' ) . '</p>';
			}
		}
		?>
	</div>
	<?php
}

function mml_tab_redirects(): void {
	$rules   = mml_rules();
	$retired = (array) get_option( 'mm_redirects', array() );
	?>
	<div class="mml-box">
		<h2><?php esc_html_e( 'Add a redirect', 'mine-mirth-launch' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mml-inline">
			<input type="hidden" name="action" value="mml_rule_add"><?php wp_nonce_field( 'mml_rule_add' ); ?>
			<input type="text" name="from" placeholder="<?php esc_attr_e( 'Old address, e.g. /old-page/ or /old-folder/*', 'mine-mirth-launch' ); ?>" required>
			<span>→</span>
			<input type="text" name="to" placeholder="<?php esc_attr_e( 'New address, e.g. /new-page/ or full URL', 'mine-mirth-launch' ); ?>" required>
			<select name="code"><option value="301">301 <?php esc_html_e( 'permanent', 'mine-mirth-launch' ); ?></option><option value="302">302 <?php esc_html_e( 'temporary', 'mine-mirth-launch' ); ?></option></select>
			<button class="button button-primary"><?php esc_html_e( 'Add', 'mine-mirth-launch' ); ?></button>
		</form>
		<p class="description"><?php esc_html_e( 'End the old address with * to redirect a whole folder. Rules only apply to addresses that would otherwise show a 404, so they never hide a live page.', 'mine-mirth-launch' ); ?></p>
	</div>
	<div class="mml-box">
		<h2><?php echo esc_html( sprintf( /* translators: %d: count */ __( 'Your rules (%d)', 'mine-mirth-launch' ), count( $rules ) ) ); ?></h2>
		<?php if ( $rules ) : ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Old address', 'mine-mirth-launch' ); ?></th><th><?php esc_html_e( 'Goes to', 'mine-mirth-launch' ); ?></th><th><?php esc_html_e( 'Type', 'mine-mirth-launch' ); ?></th><th></th></tr></thead><tbody>
			<?php foreach ( $rules as $i => $r ) : ?>
				<tr><td class="mml-path">/<?php echo esc_html( trim( (string) $r['from'], '/' ) ); ?></td><td class="mml-path"><?php echo esc_html( (string) $r['to'] ); ?></td><td><?php echo (int) $r['code']; ?></td><td><a class="button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mml_rule_del&i=' . $i ), 'mml_rule_del' ) ); ?>"><?php esc_html_e( 'Delete', 'mine-mirth-launch' ); ?></a></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php else : ?>
			<p><?php esc_html_e( 'No manual rules yet. Most old addresses are handled automatically.', 'mine-mirth-launch' ); ?></p>
		<?php endif; ?>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mml-inline" style="margin-top:14px">
			<input type="hidden" name="action" value="mml_import"><?php wp_nonce_field( 'mml_import' ); ?>
			<input type="file" name="csv" accept=".csv,text/csv">
			<button class="button"><?php esc_html_e( 'Import CSV (old, new, code)', 'mine-mirth-launch' ); ?></button>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mml_export' ), 'mml_export' ) ); ?>"><?php esc_html_e( 'Export CSV', 'mine-mirth-launch' ); ?></a>
		</form>
	</div>
	<?php if ( $retired ) : ?>
		<div class="mml-box">
			<h2><?php esc_html_e( 'Old tool pages retired by the site builder', 'mine-mirth-launch' ); ?></h2>
			<table class="widefat striped"><tbody>
			<?php foreach ( $retired as $from => $key ) : ?>
				<tr><td class="mml-path">/<?php echo esc_html( $from ); ?>/</td><td class="mml-path">→ <?php echo esc_html( mml_key_url( (string) $key ) ? mml_key_url( (string) $key ) : '{' . $key . '}' ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		</div>
	<?php endif; ?>
	<?php
	mml_tab_test();
}

function mml_tab_check(): void {
	global $wpdb;
	$rows  = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}mml_urls ORDER BY path ASC LIMIT 3000" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$fine  = 0;
	$list  = array();
	foreach ( $rows as $r ) {
		$res = mml_resolve_object( $r, $r->path );
		if ( null === $res ) {
			// Still lives here, or (for removed items without a match) falls through to the full resolver.
			$alive = false;
			if ( 'post' === $r->obj_type ) {
				$alive = 'publish' === get_post_status( (int) $r->obj_id ) && mml_normalize_path( (string) get_permalink( (int) $r->obj_id ) ) === $r->path;
			} elseif ( 'term' === $r->obj_type ) {
				$link  = get_term_link( (int) $r->obj_id, $r->obj_sub );
				$alive = ! is_wp_error( $link ) && mml_normalize_path( $link ) === $r->path;
			} else {
				$alive = true;
			}
			if ( $alive ) {
				++$fine;
				continue;
			}
			$full   = mml_resolve( $r->path );
			$list[] = array( $r, $full );
			continue;
		}
		$list[] = array( $r, array( $res[0], $res[1], 301 ) );
	}
	?>
	<div class="mml-box">
		<h2><?php esc_html_e( 'Every remembered address', 'mine-mirth-launch' ); ?></h2>
		<p><span class="mml-ok"><?php echo esc_html( sprintf( /* translators: %s: number */ __( '%s still work as they are.', 'mine-mirth-launch' ), number_format_i18n( $fine ) ) ); ?></span>
		<?php echo esc_html( sprintf( /* translators: %s: number */ __( '%s have moved or been removed and are listed below with where they now go.', 'mine-mirth-launch' ), number_format_i18n( count( $list ) ) ) ); ?></p>
		<?php if ( $list ) : ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Old address', 'mine-mirth-launch' ); ?></th><th><?php esc_html_e( 'Now goes to', 'mine-mirth-launch' ); ?></th><th><?php esc_html_e( 'Why', 'mine-mirth-launch' ); ?></th></tr></thead><tbody>
			<?php foreach ( array_slice( $list, 0, 500 ) as $item ) : ?>
				<tr>
					<td class="mml-path">/<?php echo esc_html( $item[0]->path ); ?>/</td>
					<?php if ( $item[1] ) : ?>
						<td class="mml-path"><a href="<?php echo esc_url( $item[1][0] ); ?>" target="_blank"><?php echo esc_html( $item[1][0] ); ?></a></td><td><?php echo esc_html( mml_reason_label( $item[1][1] ) ); ?></td>
					<?php else : ?>
						<td class="mml-bad" colspan="2"><?php esc_html_e( 'No match yet: add a rule in Redirects', 'mine-mirth-launch' ); ?></td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php endif; ?>
	</div>
	<?php
}

function mml_tab_log(): void {
	global $wpdb;
	$table   = $wpdb->prefix . 'mml_log';
	$missing = $wpdb->get_results( "SELECT * FROM {$table} WHERE status = '404' ORDER BY hits DESC, last_seen DESC LIMIT 200" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$served  = $wpdb->get_results( "SELECT * FROM {$table} WHERE status = 'redirect' ORDER BY last_seen DESC LIMIT 100" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	?>
	<div class="mml-box">
		<h2><?php echo esc_html( sprintf( /* translators: %d: count */ __( 'Addresses that showed a 404 (%d)', 'mine-mirth-launch' ), count( $missing ) ) ); ?></h2>
		<?php if ( $missing ) : ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Address', 'mine-mirth-launch' ); ?></th><th><?php esc_html_e( 'Hits', 'mine-mirth-launch' ); ?></th><th><?php esc_html_e( 'Last seen', 'mine-mirth-launch' ); ?></th><th><?php esc_html_e( 'Send it to', 'mine-mirth-launch' ); ?></th></tr></thead><tbody>
			<?php foreach ( $missing as $m ) : ?>
				<?php $guess = mml_smart_match( $m->path ); ?>
				<tr>
					<td class="mml-path">/<?php echo esc_html( $m->path ); ?>/<?php echo $m->referrer ? '<br><small style="color:#787c82">' . esc_html( sprintf( /* translators: %s: referrer */ __( 'from %s', 'mine-mirth-launch' ), $m->referrer ) ) . '</small>' : ''; ?></td>
					<td><?php echo (int) $m->hits; ?></td>
					<td><?php echo esc_html( human_time_diff( strtotime( $m->last_seen . ' UTC' ) ) ); ?></td>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mml-inline">
							<input type="hidden" name="action" value="mml_rule_add"><?php wp_nonce_field( 'mml_rule_add' ); ?>
							<input type="hidden" name="from" value="<?php echo esc_attr( $m->path ); ?>">
							<input type="hidden" name="code" value="301">
							<input type="text" name="to" value="<?php echo esc_attr( $guess ? $guess[0] : '' ); ?>" placeholder="/new-page/">
							<button class="button"><?php esc_html_e( 'Redirect', 'mine-mirth-launch' ); ?></button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php else : ?>
			<p class="mml-ok"><?php esc_html_e( 'No 404s recorded. Every address visitors used was found or redirected.', 'mine-mirth-launch' ); ?></p>
		<?php endif; ?>
		<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mml_clear_log' ), 'mml_clear_log' ) ); ?>"><?php esc_html_e( 'Clear the log', 'mine-mirth-launch' ); ?></a></p>
	</div>
	<div class="mml-box">
		<h2><?php esc_html_e( 'Recent automatic redirects', 'mine-mirth-launch' ); ?></h2>
		<?php if ( $served ) : ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Old address', 'mine-mirth-launch' ); ?></th><th><?php esc_html_e( 'Sent to', 'mine-mirth-launch' ); ?></th><th><?php esc_html_e( 'Why', 'mine-mirth-launch' ); ?></th><th><?php esc_html_e( 'Hits', 'mine-mirth-launch' ); ?></th></tr></thead><tbody>
			<?php foreach ( $served as $m ) : ?>
				<tr><td class="mml-path">/<?php echo esc_html( $m->path ); ?>/</td><td class="mml-path"><?php echo esc_html( $m->target ); ?></td><td><?php echo esc_html( mml_reason_label( $m->reason ) ); ?></td><td><?php echo (int) $m->hits; ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php else : ?>
			<p><?php esc_html_e( 'None yet.', 'mine-mirth-launch' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

function mml_tab_settings(): void {
	$s = mml_settings();
	?>
	<form class="mml-box" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="mml_settings"><?php wp_nonce_field( 'mml_settings' ); ?>
		<h2><?php esc_html_e( 'Settings', 'mine-mirth-launch' ); ?></h2>
		<p><label><input type="checkbox" name="smart" value="1" <?php checked( $s['smart'] ); ?>> <?php esc_html_e( 'Find the best page for unknown addresses (same name elsewhere, same topic, or a confident search match)', 'mine-mirth-launch' ); ?></label></p>
		<p><label><input type="checkbox" name="sections" value="1" <?php checked( $s['sections'] ); ?>> <?php esc_html_e( 'Otherwise send old product and blog addresses to the shop or the blog', 'mine-mirth-launch' ); ?></label></p>
		<p><label><input type="checkbox" name="log" value="1" <?php checked( $s['log'] ); ?>> <?php esc_html_e( 'Keep a log of redirects and 404s', 'mine-mirth-launch' ); ?></label></p>
		<p><button class="button button-primary"><?php esc_html_e( 'Save', 'mine-mirth-launch' ); ?></button></p>
	</form>
	<?php
}

/* ---------------------------------------------------------------------------
 * Actions
 * ------------------------------------------------------------------------- */

function mml_guard( string $action ): void {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $action ) ) {
		wp_die( esc_html__( 'Not allowed.', 'mine-mirth-launch' ) );
	}
}

function mml_back( string $tab, string $msg ): void {
	wp_safe_redirect( mml_admin_url( $tab, array( 'mml_msg' => rawurlencode( $msg ) ) ) );
	exit;
}

add_action(
	'admin_post_mml_scan',
	static function () {
		mml_guard( 'mml_scan' );
		$n = mml_memory_scan();
		/* translators: %s: number */
		mml_back( 'overview', sprintf( __( 'Scan finished: %s addresses checked and remembered.', 'mine-mirth-launch' ), number_format_i18n( $n ) ) );
	}
);

add_action(
	'admin_post_mml_rule_add',
	static function () {
		mml_guard( 'mml_rule_add' );
		$from  = mml_normalize_path( sanitize_text_field( wp_unslash( $_POST['from'] ?? '' ) ) );
		$raw   = sanitize_text_field( wp_unslash( $_POST['from'] ?? '' ) );
		$from  = '*' === substr( trim( $raw ), -1 ) ? rtrim( mml_normalize_path( rtrim( trim( $raw ), '*' ) ), '/' ) . '/*' : $from;
		$to    = trim( sanitize_text_field( wp_unslash( $_POST['to'] ?? '' ) ) );
		$code  = in_array( (int) ( $_POST['code'] ?? 301 ), array( 301, 302 ), true ) ? (int) $_POST['code'] : 301;
		if ( '' === trim( $from, '/*' ) && '/*' !== $from ) {
			mml_back( 'redirects', __( 'Enter the old address.', 'mine-mirth-launch' ) );
		}
		if ( '' === $to ) {
			mml_back( 'redirects', __( 'Enter where it should go.', 'mine-mirth-launch' ) );
		}
		$home = home_url();
		if ( 0 === strpos( $to, $home ) ) {
			$to = '/' . ltrim( substr( $to, strlen( $home ) ), '/' );
		} elseif ( ! preg_match( '#^(https?://|/|\{)#', $to ) ) {
			$to = '/' . ltrim( $to, '/' );
		}
		$rules = array_values( array_filter( mml_rules(), static fn( $r ) => trim( (string) $r['from'], '/' ) !== trim( $from, '/' ) ) );
		$rules[] = array( 'from' => $from, 'to' => $to, 'code' => $code );
		mml_save_rules( $rules );
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'mml_log', array( 'path' => trim( $from, '/' ), 'status' => '404' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$tab = isset( $_POST['from'] ) && wp_get_referer() && false !== strpos( (string) wp_get_referer(), 'tab=log' ) ? 'log' : 'redirects';
		mml_back( $tab, __( 'Redirect saved.', 'mine-mirth-launch' ) );
	}
);

add_action(
	'admin_post_mml_rule_del',
	static function () {
		mml_guard( 'mml_rule_del' );
		$rules = mml_rules();
		unset( $rules[ (int) ( $_GET['i'] ?? -1 ) ] ); // phpcs:ignore WordPress.Security.NonceVerification
		mml_save_rules( $rules );
		mml_back( 'redirects', __( 'Redirect deleted.', 'mine-mirth-launch' ) );
	}
);

add_action(
	'admin_post_mml_import',
	static function () {
		mml_guard( 'mml_import' );
		if ( empty( $_FILES['csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['csv']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			mml_back( 'redirects', __( 'Choose a CSV file first.', 'mine-mirth-launch' ) );
		}
		$fh    = fopen( $_FILES['csv']['tmp_name'], 'r' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput,WordPress.WP.AlternativeFunctions
		$rules = mml_rules();
		$have  = array_map( static fn( $r ) => trim( (string) $r['from'], '/' ), $rules );
		$added = 0;
		while ( $fh && ( $row = fgetcsv( $fh ) ) !== false ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
			if ( count( $row ) < 2 || preg_match( '/^(old|from|source)/i', (string) $row[0] ) ) {
				continue;
			}
			$from = trim( sanitize_text_field( $row[0] ) );
			$from = '*' === substr( $from, -1 ) ? rtrim( mml_normalize_path( rtrim( $from, '*' ) ), '/' ) . '/*' : mml_normalize_path( $from );
			$to   = trim( sanitize_text_field( $row[1] ) );
			if ( '' === trim( $from, '/*' ) || '' === $to || in_array( trim( $from, '/' ), $have, true ) ) {
				continue;
			}
			$rules[] = array( 'from' => $from, 'to' => $to, 'code' => in_array( (int) ( $row[2] ?? 301 ), array( 301, 302 ), true ) ? (int) $row[2] : 301 );
			$have[]  = trim( $from, '/' );
			++$added;
		}
		if ( $fh ) {
			fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		mml_save_rules( $rules );
		/* translators: %d: count */
		mml_back( 'redirects', sprintf( __( '%d redirects imported.', 'mine-mirth-launch' ), $added ) );
	}
);

add_action(
	'admin_post_mml_export',
	static function () {
		mml_guard( 'mml_export' );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=mine-mirth-redirects-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fputcsv( $out, array( 'old', 'new', 'code' ) );
		foreach ( mml_rules() as $r ) {
			fputcsv( $out, array( '/' . trim( (string) $r['from'], '/' ) . ( '*' === substr( (string) $r['from'], -1 ) ? '' : '/' ), $r['to'], $r['code'] ) );
		}
		foreach ( (array) get_option( 'mm_redirects', array() ) as $from => $key ) {
			fputcsv( $out, array( '/' . $from . '/', mml_key_url( (string) $key ), 301 ) );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
);

add_action(
	'admin_post_mml_clear_log',
	static function () {
		mml_guard( 'mml_clear_log' );
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}mml_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		mml_back( 'log', __( 'Log cleared.', 'mine-mirth-launch' ) );
	}
);

add_action(
	'admin_post_mml_settings',
	static function () {
		mml_guard( 'mml_settings' );
		update_option( 'mml_settings', array( 'smart' => (int) ! empty( $_POST['smart'] ), 'sections' => (int) ! empty( $_POST['sections'] ), 'log' => (int) ! empty( $_POST['log'] ) ), false );
		mml_back( 'settings', __( 'Settings saved.', 'mine-mirth-launch' ) );
	}
);

// A "Replaced by" box on pages, posts and products: where to send visitors if this one is removed.
add_action(
	'add_meta_boxes',
	static function () {
		foreach ( array( 'page', 'post', 'product' ) as $type ) {
			add_meta_box( 'mml-replaced', __( 'If this is removed, redirect to', 'mine-mirth-launch' ), 'mml_replaced_box', $type, 'side', 'low' );
		}
	}
);

function mml_replaced_box( WP_Post $post ): void {
	wp_nonce_field( 'mml_replaced', 'mml_replaced_nonce' );
	echo '<input type="text" class="widefat" name="mml_replaced_by" value="' . esc_attr( (string) get_post_meta( $post->ID, '_mml_replaced_by', true ) ) . '" placeholder="/new-page/"><p class="description">' . esc_html__( 'Optional. Used if this page is ever unpublished or deleted.', 'mine-mirth-launch' ) . '</p>';
}

add_action(
	'save_post',
	static function ( $id ) {
		if ( ! isset( $_POST['mml_replaced_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mml_replaced_nonce'] ) ), 'mml_replaced' ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		$v = trim( sanitize_text_field( wp_unslash( $_POST['mml_replaced_by'] ?? '' ) ) );
		if ( '' === $v ) {
			delete_post_meta( $id, '_mml_replaced_by' );
		} else {
			update_post_meta( $id, '_mml_replaced_by', $v );
		}
	}
);
