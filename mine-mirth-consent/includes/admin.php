<?php
/**
 * Settings › Cookie consent: banner, tracking and consent records.
 *
 * @package MineMirthConsent
 */

defined( 'ABSPATH' ) || exit;

function mmcc_cap(): string {
	return (string) apply_filters( 'mmcc_capability', 'manage_options' );
}

add_action(
	'admin_menu',
	static function (): void {
		add_options_page( __( 'Cookie consent', 'mine-mirth-consent' ), __( 'Cookie consent', 'mine-mirth-consent' ), mmcc_cap(), 'mmcc', 'mmcc_page' );
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( MMCC_FILE ),
	static function ( array $links ): array {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=mmcc' ) ) . '">' . esc_html__( 'Settings', 'mine-mirth-consent' ) . '</a>' );
		return $links;
	}
);

add_action(
	'admin_enqueue_scripts',
	static function ( string $hook ): void {
		if ( 'settings_page_mmcc' === $hook ) {
			wp_enqueue_style( 'mmcc', MMCC_URI . 'assets/consent.css', array(), MMCC_VERSION );
		}
	}
);

// Suggested text for the privacy policy page (Settings › Privacy › policy guide).
add_action(
	'admin_init',
	static function (): void {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content(
				'Mine Mirth Cookie Consent',
				'<p>' . esc_html__( 'When you first visit, we ask whether you accept analytics and marketing cookies. We store your choice in a cookie named "mm_consent" for up to six months, so we do not ask again on every page. Essential cookies (for example your cart and login) are always used because the shop cannot work without them. Analytics and marketing tools only run after you click Accept. You can change your choice at any time with the cookie button at the bottom of the page.', 'mine-mirth-consent' ) . '</p>'
				. '<p>' . esc_html__( 'To show that we respect your choice, we keep an anonymous record of it: the date, Accept or Deny, and a one-way code derived from your IP address that cannot be turned back into your address and changes every month. These records are deleted after 12 months.', 'mine-mirth-consent' ) . '</p>'
			);
		}
	}
);

add_action(
	'admin_post_mmcc_save',
	static function (): void {
		if ( ! current_user_can( mmcc_cap() ) || ! check_admin_referer( 'mmcc_save' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-consent' ), 403 );
		}
		$tab = sanitize_key( wp_unslash( $_POST['tab'] ?? 'banner' ) );
		$in  = (array) wp_unslash( $_POST['mmcc'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field below.
		$s   = mmcc_settings( true );
		if ( 'banner' === $tab ) {
			foreach ( array( 'title', 'accept', 'deny', 'policy_text', 'reopen_text' ) as $k ) {
				$s[ $k ] = sanitize_text_field( (string) ( $in[ $k ] ?? '' ) );
			}
			$s['message']    = sanitize_textarea_field( (string) ( $in['message'] ?? '' ) );
			$url             = trim( (string) ( $in['policy_url'] ?? '' ) );
			$s['policy_url'] = str_starts_with( $url, '/' ) ? sanitize_text_field( $url ) : esc_url_raw( $url );
			$s['position']   = in_array( $in['position'] ?? '', array( 'bottom-left', 'bottom-center', 'bottom-bar' ), true ) ? $in['position'] : 'bottom-left';
			$s['scheme']     = in_array( $in['scheme'] ?? '', array( 'auto', 'dark', 'light' ), true ) ? $in['scheme'] : 'auto';
			$s['accent']     = sanitize_hex_color( (string) ( $in['accent'] ?? '' ) ) ?: ''; // phpcs:ignore Universal.Operators.DisallowShortTernary
			if ( empty( $in['accent_custom'] ) ) {
				$s['accent'] = '';
			}
			$s['delay'] = min( 10000, absint( $in['delay'] ?? 600 ) );
			foreach ( array( 'enabled', 'reopen', 'hide_admins' ) as $k ) {
				$s[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
			}
		} elseif ( 'tracking' === $tab ) {
			foreach ( array( 'consent_mode', 'gpc', 'records' ) as $k ) {
				$s[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
			}
			$s['days'] = max( 1, min( 395, absint( $in['days'] ?? 182 ) ) );
			// Raw HTML (tracking snippets): only for administrators who may post unfiltered HTML.
			if ( current_user_can( 'unfiltered_html' ) ) {
				$s['scripts'] = (string) ( $in['scripts'] ?? '' );
			}
			if ( ! empty( $_POST['mmcc_reask'] ) ) {
				$s['version'] = (int) $s['version'] + 1;
			}
		}
		update_option( 'mmcc_settings', $s, true );
		wp_safe_redirect( admin_url( 'options-general.php?page=mmcc&tab=' . $tab . '&saved=' . ( ! empty( $_POST['mmcc_reask'] ) ? 'reask' : '1' ) ) );
		exit;
	}
);

// Consent records as a CSV file.
add_action(
	'admin_post_mmcc_export',
	static function (): void {
		if ( ! current_user_can( mmcc_cap() ) || ! check_admin_referer( 'mmcc_export' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-consent' ), 403 );
		}
		global $wpdb;
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=consent-records-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fputcsv( $out, array( 'Date (UTC)', 'Choice', 'Consent version', 'Anonymous visitor code', 'Page' ) );
		$offset = 0;
		do {
			$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT created, choice, consent_version, visitor, page FROM ' . mmcc_table() . ' ORDER BY id DESC LIMIT 2000 OFFSET %d', $offset ), ARRAY_N ); // phpcs:ignore WordPress.DB
			foreach ( $rows as $r ) {
				fputcsv( $out, $r );
			}
			$offset += 2000;
		} while ( count( $rows ) === 2000 );
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
);

/** The banner as visitors see it, for the preview on this page. */
function mmcc_preview_html(): string {
	$s = mmcc_settings();
	ob_start();
	?>
	<div class="mmcc mmcc--static mmcc--<?php echo esc_attr( 'bottom-left' === $s['position'] ? 'bottom-left' : $s['position'] ); ?> mmcc--<?php echo esc_attr( 'auto' === $s['scheme'] ? 'light' : $s['scheme'] ); ?>"<?php echo $s['accent'] ? ' style="--mmcc-accent:' . esc_attr( $s['accent'] ) . ';--mmcc-accent-2:' . esc_attr( $s['accent'] ) . '"' : ''; ?>>
		<div class="mmcc__card">
			<div class="mmcc__icon"><?php echo mmcc_icon(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<div class="mmcc__body">
				<p class="mmcc__title"><?php echo esc_html( (string) $s['title'] ); ?></p>
				<p class="mmcc__text"><?php echo esc_html( (string) $s['message'] ); ?> <?php if ( mmcc_policy_url() && $s['policy_text'] ) : ?><a class="mmcc__policy" href="<?php echo esc_url( mmcc_policy_url() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( (string) $s['policy_text'] ); ?></a><?php endif; ?></p>
			</div>
			<div class="mmcc__actions">
				<button type="button" class="mmcc__btn mmcc__btn--deny"><?php echo esc_html( (string) $s['deny'] ); ?></button>
				<button type="button" class="mmcc__btn mmcc__btn--accept"><?php echo esc_html( (string) $s['accept'] ); ?></button>
			</div>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

function mmcc_page(): void {
	if ( ! current_user_can( mmcc_cap() ) ) {
		return;
	}
	$s    = mmcc_settings( true );
	$tab  = sanitize_key( wp_unslash( $_GET['tab'] ?? 'banner' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$tabs = array(
		'banner'   => __( 'Banner', 'mine-mirth-consent' ),
		'tracking' => __( 'Tracking', 'mine-mirth-consent' ),
		'records'  => __( 'Consent records', 'mine-mirth-consent' ),
	);
	$tab  = isset( $tabs[ $tab ] ) ? $tab : 'banner';
	$chk  = static fn( string $k, string $label, string $help = '' ) => '<label class="mmcc-check"><input type="checkbox" name="mmcc[' . esc_attr( $k ) . ']" value="1" ' . checked( ! empty( $s[ $k ] ), true, false ) . '> <span>' . esc_html( $label ) . ( $help ? ' <i>' . esc_html( $help ) . '</i>' : '' ) . '</span></label>';
	$txt  = static fn( string $k, string $cls = 'regular-text' ) => '<input type="text" class="' . esc_attr( $cls ) . '" name="mmcc[' . esc_attr( $k ) . ']" value="' . esc_attr( (string) $s[ $k ] ) . '">';
	?>
	<div class="wrap mmcc-admin">
		<style>
			.mmcc-admin .mmcc-box { max-width: 1040px; margin: 18px 0; padding: 4px 22px 14px; background: #fff; border: 1px solid #dcdcde; border-radius: 12px; }
			.mmcc-admin .mmcc-box h2 { margin: 18px 0 6px; }
			.mmcc-check { display: flex; gap: 8px; align-items: flex-start; margin: 0 0 8px; }
			.mmcc-check input { margin-top: 2px; }
			.mmcc-check i, .mmcc-admin th i { font-style: normal; color: #646970; font-weight: 400; }
			.mmcc-preview-wrap { max-width: 1040px; padding: 28px; border-radius: 12px; background: linear-gradient(135deg, #eaf2fa, #f7fafc); border: 1px solid #dcdcde; }
			.mmcc.mmcc--static { position: relative; left: auto; right: auto; bottom: auto; transform: none; z-index: 1; }
			.mmcc.mmcc--static.mmcc--bottom-bar { width: 100%; }
			.mmcc.mmcc--static.mmcc--bottom-center { width: min(720px, 100%); margin: 0 auto; }
			.mmcc-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 14px 0; }
			.mmcc-stats div { padding: 14px 16px; border: 1px solid #dcdcde; border-radius: 10px; background: #f9fafb; }
			.mmcc-stats strong { display: block; font-size: 26px; line-height: 1.2; }
			.mmcc-stats span { color: #646970; }
			.mmcc-bar { height: 10px; border-radius: 999px; background: #f0c3c3; overflow: hidden; }
			.mmcc-bar i { display: block; height: 100%; background: #1e9bf0; }
			.mmcc-admin textarea.code { width: 100%; min-height: 200px; font-size: 12px; }
		</style>
		<h1><?php esc_html_e( 'Cookie consent', 'mine-mirth-consent' ); ?></h1>
		<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><?php echo 'reask' === $_GET['saved'] ? esc_html__( 'Saved. Every visitor will be asked again on their next visit.', 'mine-mirth-consent' ) : esc_html__( 'Saved.', 'mine-mirth-consent' ); // phpcs:ignore WordPress.Security.NonceVerification ?></p></div>
		<?php endif; ?>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $k => $label ) : ?>
				<a class="nav-tab<?php echo $k === $tab ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'options-general.php?page=mmcc&tab=' . $k ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( 'records' === $tab ) : ?>
			<?php
			global $wpdb;
			$t     = mmcc_table();
			$since = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
			$count = static fn( string $choice ) => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE choice = %s AND created >= %s", $choice, $since ) ); // phpcs:ignore WordPress.DB
			$acc   = $count( 'accept' );
			$den   = $count( 'deny' );
			$gpc   = $count( 'gpc' );
			$all   = $acc + $den + $gpc;
			$rate  = $all ? round( $acc / $all * 100 ) : 0;
			$rows  = $wpdb->get_results( "SELECT * FROM {$t} ORDER BY id DESC LIMIT 100", ARRAY_A ); // phpcs:ignore WordPress.DB
			?>
			<div class="mmcc-box">
				<h2><?php esc_html_e( 'Last 30 days', 'mine-mirth-consent' ); ?></h2>
				<div class="mmcc-stats">
					<div><strong><?php echo esc_html( number_format_i18n( $acc ) ); ?></strong><span><?php esc_html_e( 'Accepted', 'mine-mirth-consent' ); ?></span></div>
					<div><strong><?php echo esc_html( number_format_i18n( $den ) ); ?></strong><span><?php esc_html_e( 'Denied', 'mine-mirth-consent' ); ?></span></div>
					<div><strong><?php echo esc_html( number_format_i18n( $gpc ) ); ?></strong><span><?php esc_html_e( 'Denied by browser privacy signal', 'mine-mirth-consent' ); ?></span></div>
					<div><strong><?php echo esc_html( $rate ); ?>%</strong><span><?php esc_html_e( 'Acceptance rate', 'mine-mirth-consent' ); ?></span></div>
				</div>
				<div class="mmcc-bar" aria-hidden="true"><i style="width:<?php echo (int) $rate; ?>%"></i></div>
				<p class="description"><?php esc_html_e( 'Each record: date, choice, consent version, page and an anonymous visitor code (a one-way hash of the IP address, changed every month; it cannot be turned back into the address). Records are deleted after 12 months.', 'mine-mirth-consent' ); ?></p>
				<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mmcc_export' ), 'mmcc_export' ) ); ?>"><?php esc_html_e( 'Download all records (CSV)', 'mine-mirth-consent' ); ?></a></p>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Date', 'mine-mirth-consent' ); ?></th><th><?php esc_html_e( 'Choice', 'mine-mirth-consent' ); ?></th><th><?php esc_html_e( 'Version', 'mine-mirth-consent' ); ?></th><th><?php esc_html_e( 'Visitor code', 'mine-mirth-consent' ); ?></th><th><?php esc_html_e( 'Page', 'mine-mirth-consent' ); ?></th></tr></thead>
					<tbody>
						<?php if ( ! $rows ) : ?>
							<tr><td colspan="5"><?php esc_html_e( 'No choices recorded yet.', 'mine-mirth-consent' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $rows as $r ) : ?>
							<tr>
								<td><?php echo esc_html( get_date_from_gmt( $r['created'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></td>
								<td><?php echo esc_html( array( 'accept' => __( 'Accepted', 'mine-mirth-consent' ), 'deny' => __( 'Denied', 'mine-mirth-consent' ), 'gpc' => __( 'Denied (privacy signal)', 'mine-mirth-consent' ) )[ $r['choice'] ] ?? $r['choice'] ); ?></td>
								<td><?php echo (int) $r['consent_version']; ?></td>
								<td><code><?php echo esc_html( $r['visitor'] ); ?></code></td>
								<td><?php echo esc_html( $r['page'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mmcc_save">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
				<?php wp_nonce_field( 'mmcc_save' ); ?>
				<?php if ( 'banner' === $tab ) : ?>
					<h2 class="title"><?php esc_html_e( 'Preview', 'mine-mirth-consent' ); ?>
						<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'mmcc_preview', 1, home_url( '/' ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'See it on the site ↗', 'mine-mirth-consent' ); ?></a>
					</h2>
					<div class="mmcc-preview-wrap"><?php echo mmcc_preview_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<div class="mmcc-box">
						<h2><?php esc_html_e( 'Banner', 'mine-mirth-consent' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr><th scope="row"><?php esc_html_e( 'Show', 'mine-mirth-consent' ); ?></th><td>
								<?php echo $chk( 'enabled', __( 'Ask first-time visitors for consent', 'mine-mirth-consent' ) ) . $chk( 'hide_admins', __( 'Not for logged-in administrators', 'mine-mirth-consent' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Title', 'mine-mirth-consent' ); ?></th><td><?php echo $txt( 'title', 'large-text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Message', 'mine-mirth-consent' ); ?></th><td><textarea name="mmcc[message]" rows="3" class="large-text"><?php echo esc_textarea( (string) $s['message'] ); ?></textarea></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Buttons', 'mine-mirth-consent' ); ?></th><td>
								<label><?php esc_html_e( 'Accept (highlighted)', 'mine-mirth-consent' ); ?> <?php echo $txt( 'accept', 'regular-text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label><br><br>
								<label><?php esc_html_e( 'Deny', 'mine-mirth-consent' ); ?> <?php echo $txt( 'deny', 'regular-text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
								<p class="description"><?php esc_html_e( 'Both buttons are the same size and on the same screen, as privacy rules expect: refusing must be as easy as accepting.', 'mine-mirth-consent' ); ?></p>
							</td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Privacy policy link', 'mine-mirth-consent' ); ?></th><td>
								<?php echo $txt( 'policy_text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<input type="text" class="regular-text" name="mmcc[policy_url]" value="<?php echo esc_attr( (string) $s['policy_url'] ); ?>" placeholder="<?php echo esc_attr( (string) get_privacy_policy_url() ?: __( 'Settings › Privacy page', 'mine-mirth-consent' ) ); // phpcs:ignore Universal.Operators.DisallowShortTernary ?>">
								<p class="description"><?php esc_html_e( 'Empty: your privacy page from Settings › Privacy. That page also suggests text about this banner for your policy.', 'mine-mirth-consent' ); ?></p>
							</td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Design', 'mine-mirth-consent' ); ?></th><td>
								<select name="mmcc[position]">
									<option value="bottom-left" <?php selected( $s['position'], 'bottom-left' ); ?>><?php esc_html_e( 'Card, bottom left', 'mine-mirth-consent' ); ?></option>
									<option value="bottom-center" <?php selected( $s['position'], 'bottom-center' ); ?>><?php esc_html_e( 'Wide card, bottom centre', 'mine-mirth-consent' ); ?></option>
									<option value="bottom-bar" <?php selected( $s['position'], 'bottom-bar' ); ?>><?php esc_html_e( 'Full-width bar at the bottom', 'mine-mirth-consent' ); ?></option>
								</select>
								<select name="mmcc[scheme]">
									<option value="auto" <?php selected( $s['scheme'], 'auto' ); ?>><?php esc_html_e( 'Follows the site (light or dark)', 'mine-mirth-consent' ); ?></option>
									<option value="dark" <?php selected( $s['scheme'], 'dark' ); ?>><?php esc_html_e( 'Always dark', 'mine-mirth-consent' ); ?></option>
									<option value="light" <?php selected( $s['scheme'], 'light' ); ?>><?php esc_html_e( 'Always light', 'mine-mirth-consent' ); ?></option>
								</select>
								<p><label class="mmcc-check"><input type="checkbox" name="mmcc[accent_custom]" value="1" <?php checked( '' !== (string) $s['accent'] ); ?>> <span><?php esc_html_e( 'Own button colour', 'mine-mirth-consent' ); ?> <input type="color" name="mmcc[accent]" value="<?php echo esc_attr( (string) ( $s['accent'] ?: '#1e9bf0' ) ); // phpcs:ignore Universal.Operators.DisallowShortTernary ?>"> <i><?php esc_html_e( '(otherwise the theme\'s blue gradient)', 'mine-mirth-consent' ); ?></i></span></label></p>
								<label><?php esc_html_e( 'Appear after', 'mine-mirth-consent' ); ?> <input type="number" min="0" max="10000" step="100" name="mmcc[delay]" value="<?php echo (int) $s['delay']; ?>" class="small-text"> <?php esc_html_e( 'milliseconds', 'mine-mirth-consent' ); ?></label>
							</td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Changing their mind', 'mine-mirth-consent' ); ?></th><td>
								<?php echo $chk( 'reopen', __( 'Small cookie button at the bottom left after a choice', 'mine-mirth-consent' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<label><?php esc_html_e( 'Its label', 'mine-mirth-consent' ); ?> <?php echo $txt( 'reopen_text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
								<p class="description"><?php esc_html_e( 'You can also add a "Cookie settings" link anywhere: the shortcode [mm_cookie_settings], or a menu link to #mm-cookie-settings (Appearance › Menus › Custom link), for example in the footer.', 'mine-mirth-consent' ); ?></p>
							</td></tr>
						</table>
					</div>
				<?php else : ?>
					<div class="mmcc-box">
						<h2><?php esc_html_e( 'What the choice controls', 'mine-mirth-consent' ); ?></h2>
						<table class="form-table" role="presentation">
							<tr><th scope="row"><?php esc_html_e( 'Google tags', 'mine-mirth-consent' ); ?></th><td>
								<?php echo $chk( 'consent_mode', __( 'Google Consent Mode v2', 'mine-mirth-consent' ), __( '(Google Analytics, Ads and Tag Manager store nothing until Accept; after Deny they send only anonymous, cookieless signals)', 'mine-mirth-consent' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Scripts to load after Accept', 'mine-mirth-consent' ); ?></th><td>
								<?php if ( current_user_can( 'unfiltered_html' ) ) : ?>
									<textarea name="mmcc[scripts]" class="code" spellcheck="false" placeholder="<?php echo esc_attr( "<!-- Example: Meta Pixel, Hotjar, a Google Analytics snippet -->\n<script async src=\"https://www.googletagmanager.com/gtag/js?id=G-XXXXXXX\"></script>\n<script>gtag('js', new Date()); gtag('config', 'G-XXXXXXX');</script>" ); ?>"><?php echo esc_textarea( (string) $s['scripts'] ); ?></textarea>
									<p class="description"><?php esc_html_e( 'Paste the tracking codes you would normally put in the site\'s <head>. They run only for visitors who click Accept (and on their later visits). Remove the same codes from anywhere else, or they will still run for everyone.', 'mine-mirth-consent' ); ?></p>
								<?php else : ?>
									<p><?php esc_html_e( 'Only an administrator can edit tracking scripts.', 'mine-mirth-consent' ); ?></p>
								<?php endif; ?>
							</td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Other plugins', 'mine-mirth-consent' ); ?></th><td>
								<p><?php esc_html_e( 'Plugins that follow the WP Consent API (for example Google Site Kit) read the visitor\'s choice automatically. To use it, also install the free "WP Consent API" plugin.', 'mine-mirth-consent' ); ?></p>
								<p class="description"><?php esc_html_e( 'For developers: the choice is on <html data-mm-consent="accept|deny|none">, in mmConsent.get(), in the "mmcc:consent" event and as "mm_consent_update" in the dataLayer (for Google Tag Manager triggers).', 'mine-mirth-consent' ); ?></p>
							</td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Privacy signals', 'mine-mirth-consent' ); ?></th><td>
								<?php echo $chk( 'gpc', __( 'Respect Global Privacy Control', 'mine-mirth-consent' ), __( '(browsers and extensions that send "do not sell or share" are treated as Deny without asking, as California law requires)', 'mine-mirth-consent' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Remember the choice for', 'mine-mirth-consent' ); ?></th><td>
								<input type="number" min="1" max="395" name="mmcc[days]" value="<?php echo (int) $s['days']; ?>" class="small-text"> <?php esc_html_e( 'days (6 months is the usual recommendation; at most 13 months)', 'mine-mirth-consent' ); ?>
							</td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Ask everyone again', 'mine-mirth-consent' ); ?></th><td>
								<p><?php echo esc_html( sprintf( /* translators: %d: version */ __( 'Consent version %d. When you add a new tracking tool or change your policy, ask every visitor again:', 'mine-mirth-consent' ), (int) $s['version'] ) ); ?></p>
								<button type="submit" name="mmcc_reask" value="1" class="button" onclick="return confirm('<?php echo esc_js( __( 'Show the banner again to every visitor?', 'mine-mirth-consent' ) ); ?>');"><?php esc_html_e( 'Save and ask everyone again', 'mine-mirth-consent' ); ?></button>
							</td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Records', 'mine-mirth-consent' ); ?></th><td>
								<?php echo $chk( 'records', __( 'Keep an anonymous record of each choice (proof of consent)', 'mine-mirth-consent' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</td></tr>
						</table>
					</div>
				<?php endif; ?>
				<?php submit_button( __( 'Save', 'mine-mirth-consent' ) ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}
