<?php
/**
 * WooCommerce › Buy Now & Alerts: settings, watched products, alert log and email previews.
 *
 * @package MineMirthBuyNow
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	static function () {
		add_submenu_page( 'woocommerce', __( 'Buy Now & Price Alerts', 'mine-mirth-buy-now' ), __( 'Buy Now & Alerts', 'mine-mirth-buy-now' ), 'manage_woocommerce', 'mmbn', 'mmbn_admin_page' );
	},
	60
);

add_filter(
	'plugin_action_links_' . plugin_basename( MMBN_FILE ),
	static function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=mmbn' ) ) . '">' . esc_html__( 'Settings', 'mine-mirth-buy-now' ) . '</a>' );
		return $links;
	}
);

add_action(
	'admin_init',
	static function () {
		register_setting(
			'mmbn',
			'mmbn_settings',
			array(
				'sanitize_callback' => static function ( $in ) {
					$in  = is_array( $in ) ? $in : array();
					$d   = mmbn_defaults();
					$out = array();
					foreach ( array( 'buy_now', 'buy_solo', 'alerts', 'notify_down', 'notify_up' ) as $k ) {
						$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
					}
					foreach ( array( 'buy_label', 'pre_label', 'alert_title', 'alert_text', 'alert_btn', 'consent' ) as $k ) {
						$v         = sanitize_text_field( wp_unslash( $in[ $k ] ?? '' ) );
						$out[ $k ] = '' !== $v || 'consent' === $k ? $v : $d[ $k ];
					}
					$out['buy_style']  = in_array( $in['buy_style'] ?? '', array( 'contrast', 'outline', 'primary' ), true ) ? $in['buy_style'] : 'contrast';
					$out['delay']      = in_array( (int) ( $in['delay'] ?? 0 ), array( 0, 5, 15, 60 ), true ) ? (int) $in['delay'] : 0;
					$out['show_count'] = max( 0, min( 1000, (int) ( $in['show_count'] ?? 3 ) ) );
					return $out;
				},
			)
		);
	}
);

/**
 * Preview an alert email for a product (fake old price ±10%).
 */
add_action(
	'admin_post_mmbn_preview',
	static function () {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-buy-now' ) );
		}
		check_admin_referer( 'mmbn_preview' );
		$product = wc_get_product( absint( wp_unslash( $_GET['product'] ?? 0 ) ) );
		if ( ! $product || null === mmbn_current_price( $product ) ) {
			wp_die( esc_html__( 'Pick a product that has a price.', 'mine-mirth-buy-now' ) );
		}
		$now  = (float) mmbn_current_price( $product );
		$down = 'up' !== sanitize_key( wp_unslash( $_GET['dir'] ?? 'down' ) );
		$was  = $down ? round( $now * 1.1, 2 ) : round( $now / 1.1, 2 );
		$user = wp_get_current_user();
		$w    = (object) array( 'id' => 0, 'token' => 'preview', 'sub_id' => 0, 'email' => $user->user_email, 'created' => current_time( 'mysql', true ) );
		if ( function_exists( 'mmm_sub_by_email' ) ) {
			$w->sub_id = (int) mmm_sub_by_email( $user->user_email );
		}
		$mail = mmbn_compose( $w, $product, $was, $now );
		if ( ! empty( $_GET['send'] ) ) {
			$mail['subject'] = '[TEST] ' . $mail['subject'];
			[ $ok, $error ]  = mmbn_send( $w, $mail );
			update_option( 'mmbn_last_test_error', $error, false );
			wp_safe_redirect( admin_url( 'admin.php?page=mmbn&test=' . ( $ok ? 'ok' : 'fail' ) ) );
			exit;
		}
		// Don't count the preview as an open.
		echo str_replace( home_url( '/?mmm=o' ), includes_url( 'images/blank.gif' ) . '?', $mail['html'] ); // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}
);

add_action(
	'admin_post_mmbn_run',
	static function () {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-buy-now' ) );
		}
		$id = absint( wp_unslash( $_GET['product'] ?? 0 ) );
		check_admin_referer( 'mmbn_run_' . $id );
		$pending = mmbn_pending();
		$pending[ $id ] = time();
		update_option( 'mmbn_pending', $pending, true );
		delete_transient( 'mmbn_lock' );
		$r = mmbn_process( 100 );
		if ( empty( $r['products'] ) ) {
			$r['products'][ $id ] = array( 'sent' => 0, 'failed' => 0, 'skipped' => 1, 'note' => __( 'Nobody is waiting on a different price.', 'mine-mirth-buy-now' ), 'error' => '' );
		}
		set_transient( 'mmbn_notice_' . get_current_user_id(), $r, 10 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=mmbn' ) );
		exit;
	}
);

add_action(
	'admin_post_mmbn_stop_all',
	static function () {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-buy-now' ) );
		}
		$id = absint( wp_unslash( $_GET['product'] ?? 0 ) );
		check_admin_referer( 'mmbn_stop_all_' . $id );
		global $wpdb;
		$wpdb->update( mmbn_table(), array( 'status' => 'stopped' ), array( 'product_id' => $id, 'status' => 'active' ) ); // phpcs:ignore WordPress.DB
		wp_safe_redirect( admin_url( 'admin.php?page=mmbn&stopped=1' ) );
		exit;
	}
);

/**
 * Note under the price fields: how many people get an alert when this price changes.
 */
add_action(
	'woocommerce_product_options_pricing',
	static function () {
		global $post;
		$n = $post ? mmbn_watch_count( (int) $post->ID ) : 0;
		if ( $n ) {
			/* translators: %s: number of people */
			echo '<p class="form-field" style="color:#2271b1"><span class="dashicons dashicons-bell" style="font-size:16px;vertical-align:-3px"></span> ' . esc_html( sprintf( _n( '%s person is watching this price. Changing it emails them a price alert.', '%s people are watching this price. Changing it emails them a price alert.', $n, 'mine-mirth-buy-now' ), number_format_i18n( $n ) ) ) . '</p>';
		}
	}
);

function mmbn_admin_page(): void {
	global $wpdb;
	$o     = static fn( $k ) => mmbn_opt( $k );
	$table = mmbn_table();
	$rows  = $wpdb->get_results( "SELECT product_id, COUNT(*) n, MAX(notified) last_alert FROM $table WHERE status = 'active' GROUP BY product_id ORDER BY n DESC LIMIT 100" ); // phpcs:ignore WordPress.DB
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'active'" ); // phpcs:ignore WordPress.DB
	$sent  = (int) $wpdb->get_var( "SELECT COALESCE(SUM(alerts),0) FROM $table" ); // phpcs:ignore WordPress.DB
	$log   = (array) get_option( 'mmbn_log', array() );
	$first = $rows ? (int) $rows[0]->product_id : (int) ( wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'return' => 'ids' ) )[0] ?? 0 );
	$prev  = static fn( $dir, $send = 0 ) => wp_nonce_url( admin_url( 'admin-post.php?action=mmbn_preview&product=' . $first . '&dir=' . $dir . ( $send ? '&send=1' : '' ) ), 'mmbn_preview' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Buy Now & Price Alerts', 'mine-mirth-buy-now' ); ?></h1>
		<?php if ( isset( $_GET['test'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-<?php echo 'ok' === $_GET['test'] ? 'success' : 'error'; // phpcs:ignore WordPress.Security.NonceVerification ?> is-dismissible"><p><?php echo 'ok' === $_GET['test'] ? esc_html__( 'Test alert sent to your email.', 'mine-mirth-buy-now' ) : esc_html__( 'The test alert could not be sent. Check your SMTP plugin.', 'mine-mirth-buy-now' ) . ' ' . esc_html( (string) get_option( 'mmbn_last_test_error' ) ); // phpcs:ignore WordPress.Security.NonceVerification ?></p></div>
		<?php endif; ?>
		<p style="max-width:820px"><?php esc_html_e( 'Every product page gets a "Buy it now" button that skips the cart and opens checkout, and a "Get price alerts" box. People who sign up join your newsletter list, and whenever you change that product\'s price (edit, quick edit, bulk edit or a scheduled sale), they get an email in your newsletter design.', 'mine-mirth-buy-now' ); ?></p>

		<div style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0">
			<?php foreach ( array( array( __( 'People watching prices', 'mine-mirth-buy-now' ), $total ), array( __( 'Products watched', 'mine-mirth-buy-now' ), count( (array) $rows ) ), array( __( 'Alerts sent', 'mine-mirth-buy-now' ), $sent ) ) as $s ) : ?>
				<div style="background:#fff;border:1px solid #dcdcde;border-left:3px solid #35a8f6;border-radius:8px;padding:12px 18px;min-width:170px"><div style="color:#646970;font-size:12px;text-transform:uppercase"><?php echo esc_html( $s[0] ); ?></div><div style="font-size:26px;font-weight:600"><?php echo esc_html( number_format_i18n( $s[1] ) ); ?></div></div>
			<?php endforeach; ?>
		</div>

		<?php
		$waiting = array_filter( mmbn_pending(), static fn( $due ) => $due <= time() - 120 );
		if ( $waiting ) :
			?>
			<div class="notice notice-warning inline"><p><?php echo esc_html( sprintf( /* translators: %s: product names */ __( 'Alerts are waiting to be sent for: %s. Use "Send alerts now" below.', 'mine-mirth-buy-now' ), implode( ', ', array_map( 'get_the_title', array_keys( $waiting ) ) ) ) ); ?></p></div>
		<?php endif; ?>
		<?php if ( $first ) : ?>
			<p>
				<a class="button" target="_blank" href="<?php echo esc_url( $prev( 'down' ) ); ?>"><?php esc_html_e( 'Preview “price drop” email', 'mine-mirth-buy-now' ); ?></a>
				<a class="button" target="_blank" href="<?php echo esc_url( $prev( 'up' ) ); ?>"><?php esc_html_e( 'Preview “price went up” email', 'mine-mirth-buy-now' ); ?></a>
				<a class="button" href="<?php echo esc_url( $prev( 'down', 1 ) ); ?>"><?php esc_html_e( 'Send me a test alert', 'mine-mirth-buy-now' ); ?></a>
				<span class="description"><?php echo esc_html( sprintf( /* translators: %s: product */ __( 'Using: %s', 'mine-mirth-buy-now' ), get_the_title( $first ) ) ); ?></span>
			</p>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'mmbn' ); ?>
			<h2><?php esc_html_e( 'Buy it now', 'mine-mirth-buy-now' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Button', 'mine-mirth-buy-now' ); ?></th><td><label><input type="checkbox" name="mmbn_settings[buy_now]" value="1" <?php checked( (int) $o( 'buy_now' ), 1 ); ?>> <?php esc_html_e( 'Show "Buy it now" on product pages', 'mine-mirth-buy-now' ); ?></label></td></tr>
				<tr><th><label for="mmbn-bl"><?php esc_html_e( 'Label', 'mine-mirth-buy-now' ); ?></label></th><td><input id="mmbn-bl" name="mmbn_settings[buy_label]" value="<?php echo esc_attr( (string) $o( 'buy_label' ) ); ?>"> &nbsp; <label><?php esc_html_e( 'On pre-order products:', 'mine-mirth-buy-now' ); ?> <input name="mmbn_settings[pre_label]" value="<?php echo esc_attr( (string) $o( 'pre_label' ) ); ?>"></label></td></tr>
				<tr><th><label for="mmbn-bs"><?php esc_html_e( 'Style', 'mine-mirth-buy-now' ); ?></label></th><td><select id="mmbn-bs" name="mmbn_settings[buy_style]">
					<option value="contrast" <?php selected( $o( 'buy_style' ), 'contrast' ); ?>><?php esc_html_e( 'Solid dark (white in dark mode) — stands apart from Add to cart', 'mine-mirth-buy-now' ); ?></option>
					<option value="outline" <?php selected( $o( 'buy_style' ), 'outline' ); ?>><?php esc_html_e( 'Blue outline', 'mine-mirth-buy-now' ); ?></option>
					<option value="primary" <?php selected( $o( 'buy_style' ), 'primary' ); ?>><?php esc_html_e( 'Blue gradient (same as Add to cart)', 'mine-mirth-buy-now' ); ?></option>
				</select></td></tr>
				<tr><th><?php esc_html_e( 'Cart', 'mine-mirth-buy-now' ); ?></th><td><label><input type="checkbox" name="mmbn_settings[buy_solo]" value="1" <?php checked( (int) $o( 'buy_solo' ), 1 ); ?>> <?php esc_html_e( 'Check out only this product (removes other items from the cart)', 'mine-mirth-buy-now' ); ?></label><p class="description"><?php esc_html_e( 'Off: the product is added to whatever is already in the cart, then checkout opens.', 'mine-mirth-buy-now' ); ?></p></td></tr>
			</table>

			<h2><?php esc_html_e( 'Price alerts', 'mine-mirth-buy-now' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Sign-up box', 'mine-mirth-buy-now' ); ?></th><td><label><input type="checkbox" name="mmbn_settings[alerts]" value="1" <?php checked( (int) $o( 'alerts' ), 1 ); ?>> <?php esc_html_e( 'Show "Get price alerts" on product pages', 'mine-mirth-buy-now' ); ?></label></td></tr>
				<tr><th><label for="mmbn-at"><?php esc_html_e( 'Title', 'mine-mirth-buy-now' ); ?></label></th><td><input id="mmbn-at" class="regular-text" name="mmbn_settings[alert_title]" value="<?php echo esc_attr( (string) $o( 'alert_title' ) ); ?>"></td></tr>
				<tr><th><label for="mmbn-ax"><?php esc_html_e( 'Text', 'mine-mirth-buy-now' ); ?></label></th><td><input id="mmbn-ax" class="large-text" name="mmbn_settings[alert_text]" value="<?php echo esc_attr( (string) $o( 'alert_text' ) ); ?>"></td></tr>
				<tr><th><label for="mmbn-ab"><?php esc_html_e( 'Button', 'mine-mirth-buy-now' ); ?></label></th><td><input id="mmbn-ab" name="mmbn_settings[alert_btn]" value="<?php echo esc_attr( (string) $o( 'alert_btn' ) ); ?>"></td></tr>
				<tr><th><label for="mmbn-ac"><?php esc_html_e( 'Small print', 'mine-mirth-buy-now' ); ?></label></th><td><input id="mmbn-ac" class="large-text" name="mmbn_settings[consent]" value="<?php echo esc_attr( (string) $o( 'consent' ) ); ?>"><p class="description"><?php esc_html_e( 'Sign-ups are added to your newsletter subscribers, so say so here.', 'mine-mirth-buy-now' ); ?></p></td></tr>
				<tr><th><label for="mmbn-sc"><?php esc_html_e( 'Show how many are watching', 'mine-mirth-buy-now' ); ?></label></th><td><input id="mmbn-sc" type="number" min="0" class="small-text" name="mmbn_settings[show_count]" value="<?php echo esc_attr( (string) $o( 'show_count' ) ); ?>"> <?php esc_html_e( 'people or more (0 = never)', 'mine-mirth-buy-now' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Send an alert when the price', 'mine-mirth-buy-now' ); ?></th><td>
					<label><input type="checkbox" name="mmbn_settings[notify_down]" value="1" <?php checked( (int) $o( 'notify_down' ), 1 ); ?>> <?php esc_html_e( 'drops', 'mine-mirth-buy-now' ); ?></label> &nbsp;
					<label><input type="checkbox" name="mmbn_settings[notify_up]" value="1" <?php checked( (int) $o( 'notify_up' ), 1 ); ?>> <?php esc_html_e( 'goes up', 'mine-mirth-buy-now' ); ?></label></td></tr>
				<tr><th><label for="mmbn-d"><?php esc_html_e( 'Send', 'mine-mirth-buy-now' ); ?></label></th><td><select id="mmbn-d" name="mmbn_settings[delay]">
					<?php foreach ( array( 0 => __( 'Instantly', 'mine-mirth-buy-now' ), 5 => __( 'After 5 minutes', 'mine-mirth-buy-now' ), 15 => __( 'After 15 minutes', 'mine-mirth-buy-now' ), 60 => __( 'After 1 hour', 'mine-mirth-buy-now' ) ) as $k => $label ) : ?>
						<option value="<?php echo esc_attr( (string) $k ); ?>" <?php selected( (int) $o( 'delay' ), $k ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select><p class="description"><?php esc_html_e( 'A short wait lets you fix a typo in a price before anyone is emailed. The alert always uses the price at sending time, and nothing is sent if you put the old price back.', 'mine-mirth-buy-now' ); ?></p></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'Watched products', 'mine-mirth-buy-now' ); ?></h2>
		<table class="widefat striped" style="max-width:1000px">
			<thead><tr><th><?php esc_html_e( 'Product', 'mine-mirth-buy-now' ); ?></th><th><?php esc_html_e( 'Watching', 'mine-mirth-buy-now' ); ?></th><th><?php esc_html_e( 'Current price', 'mine-mirth-buy-now' ); ?></th><th><?php esc_html_e( 'Last alert', 'mine-mirth-buy-now' ); ?></th><th></th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No one is watching a price yet.', 'mine-mirth-buy-now' ); ?></td></tr>
			<?php endif; ?>
			<?php
			foreach ( (array) $rows as $r ) :
				$p = wc_get_product( (int) $r->product_id );
				if ( ! $p ) {
					continue;
				}
				$price = mmbn_current_price( $p );
				?>
				<tr>
					<td><a href="<?php echo esc_url( (string) get_edit_post_link( $p->get_id() ) ); ?>"><?php echo esc_html( $p->get_name() ); ?></a></td>
					<td><?php echo esc_html( number_format_i18n( (int) $r->n ) ); ?></td>
					<td><?php echo null === $price ? '—' : wp_kses_post( wc_price( $price ) ); ?></td>
					<td><?php echo $r->last_alert ? esc_html( sprintf( /* translators: %s: time */ __( '%s ago', 'mine-mirth-buy-now' ), human_time_diff( (int) strtotime( $r->last_alert . ' UTC' ) ) ) ) : '—'; ?></td>
					<td><a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mmbn_run&product=' . $p->get_id() ), 'mmbn_run_' . $p->get_id() ) ); ?>"><?php esc_html_e( 'Send alerts now', 'mine-mirth-buy-now' ); ?></a> &nbsp; <a class="button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Stop all price alerts for this product?', 'mine-mirth-buy-now' ) ); ?>')" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mmbn_stop_all&product=' . $p->get_id() ), 'mmbn_stop_all_' . $p->get_id() ) ); ?>"><?php esc_html_e( 'Stop alerts', 'mine-mirth-buy-now' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Recent alerts', 'mine-mirth-buy-now' ); ?></h2>
		<table class="widefat striped" style="max-width:1000px">
			<thead><tr><th><?php esc_html_e( 'When', 'mine-mirth-buy-now' ); ?></th><th><?php esc_html_e( 'Product', 'mine-mirth-buy-now' ); ?></th><th><?php esc_html_e( 'To', 'mine-mirth-buy-now' ); ?></th><th><?php esc_html_e( 'Price', 'mine-mirth-buy-now' ); ?></th><th><?php esc_html_e( 'Result', 'mine-mirth-buy-now' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $log ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No alerts sent yet.', 'mine-mirth-buy-now' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( array_slice( $log, 0, 50 ) as $l ) : ?>
				<tr>
					<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $l['t'] ) ); ?></td>
					<td><?php echo esc_html( get_the_title( (int) $l['p'] ) ); ?></td>
					<td><?php echo esc_html( (string) $l['e'] ); ?></td>
					<td><?php echo esc_html( mmbn_plain_price( (float) $l['was'] ) . ' → ' . mmbn_plain_price( (float) $l['now'] ) ); ?></td>
					<td><?php echo $l['ok'] ? '<span style="color:#16a34a">' . esc_html__( 'Sent', 'mine-mirth-buy-now' ) . '</span>' : '<span style="color:' . ( 0 === strpos( (string) ( $l['note'] ?? '' ), 'Not sent' ) ? '#996800' : '#d63638' ) . '">' . esc_html( ! empty( $l['note'] ) ? (string) $l['note'] : __( 'Failed', 'mine-mirth-buy-now' ) ) . '</span>'; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
