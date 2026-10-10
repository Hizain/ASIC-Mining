<?php
/**
 * Price alerts: sign-up box on the product page, price-change detection and alert emails.
 *
 * Each watch remembers the price the person last saw. Whenever a product is saved (edit screen,
 * quick edit, bulk edit, scheduled sale, REST), watchers whose price differs by at least the
 * minimum change get an email, and their remembered price moves to the new one.
 *
 * @package MineMirthBuyNow
 */

defined( 'ABSPATH' ) || exit;

function mmbn_table(): string {
	global $wpdb;
	return $wpdb->prefix . 'mmbn_watches';
}

function mmbn_install(): void {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset = $wpdb->get_charset_collate();
	$table   = mmbn_table();
	dbDelta(
		"CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			product_id bigint(20) unsigned NOT NULL DEFAULT 0,
			sub_id bigint(20) unsigned NOT NULL DEFAULT 0,
			email varchar(190) NOT NULL DEFAULT '',
			token char(32) NOT NULL DEFAULT '',
			last_price decimal(20,6) NOT NULL DEFAULT 0,
			status varchar(12) NOT NULL DEFAULT 'active',
			alerts int(10) unsigned NOT NULL DEFAULT 0,
			created datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			notified datetime NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY product_status (product_id,status),
			KEY email (email)
		) $charset;"
	);
	update_option( 'mmbn_db', MMBN_DB );
}

add_action(
	'init',
	static function () {
		if ( (int) get_option( 'mmbn_db' ) !== MMBN_DB ) {
			mmbn_install();
		}
	}
);

/**
 * The price shoppers see: the active (sale) price, or the lowest variation price.
 */
function mmbn_current_price( WC_Product $product ): ?float {
	$price = $product->is_type( 'variable' ) ? $product->get_variation_price( 'min', true ) : $product->get_price();
	return '' === (string) $price || null === $price ? null : (float) $price;
}

function mmbn_plain_price( float $amount ): string {
	return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
}

function mmbn_watch_count( int $product_id ): int {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . mmbn_table() . " WHERE product_id = %d AND status = 'active'", $product_id ) ); // phpcs:ignore WordPress.DB
}

/* ---------------------------------------------------------------------------
 * Product page box
 * ------------------------------------------------------------------------- */

add_action(
	'woocommerce_single_product_summary',
	static function () {
		global $product;
		if ( ! mmbn_opt( 'alerts' ) || ! $product instanceof WC_Product || $product->is_type( 'external' ) || null === mmbn_current_price( $product ) ) {
			return;
		}
		$id    = (int) $product->get_id();
		$user  = wp_get_current_user();
		$count = mmbn_watch_count( $id );
		$min   = (int) mmbn_opt( 'show_count' );
		$note  = (string) mmbn_opt( 'alert_text' );
		if ( $min > 0 && $count >= $min ) {
			/* translators: %s: number of people */
			$note .= ' ' . sprintf( _n( '%s person is watching.', '%s people are watching.', $count, 'mine-mirth-buy-now' ), number_format_i18n( $count ) );
		}
		?>
		<div class="mmbn-alert" data-mmbn-alert>
			<button type="button" class="mmbn-alert__toggle" aria-expanded="false" aria-controls="mmbn-alert-<?php echo esc_attr( (string) $id ); ?>">
				<span class="mmbn-alert__icon"><?php echo mmbn_icon( 'bell' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="mmbn-alert__text"><strong><?php echo esc_html( (string) mmbn_opt( 'alert_title' ) ); ?></strong><small><?php echo esc_html( $note ); ?></small></span>
				<span class="mmbn-alert__chev" aria-hidden="true"><?php echo function_exists( 'mm_icon' ) ? mm_icon( 'chevron-down' ) : '&#9662;'; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</button>
			<form id="mmbn-alert-<?php echo esc_attr( (string) $id ); ?>" class="mmbn-alert__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden novalidate>
				<input type="hidden" name="action" value="mmbn_watch">
				<input type="hidden" name="product" value="<?php echo esc_attr( (string) $id ); ?>">
				<input type="hidden" name="back" value="<?php echo esc_url( get_permalink( $id ) ); ?>">
				<div class="mmbn-alert__row">
					<label class="screen-reader-text" for="mmbn-email-<?php echo esc_attr( (string) $id ); ?>"><?php esc_html_e( 'Email address', 'mine-mirth-buy-now' ); ?></label>
					<input id="mmbn-email-<?php echo esc_attr( (string) $id ); ?>" type="email" name="email" required autocomplete="email" placeholder="<?php esc_attr_e( 'you@example.com', 'mine-mirth-buy-now' ); ?>" value="<?php echo esc_attr( $user->exists() ? $user->user_email : '' ); ?>">
					<button type="submit" class="mm-btn mm-btn--primary"><?php echo esc_html( (string) mmbn_opt( 'alert_btn' ) ); ?></button>
				</div>
				<span class="mmbn-alert__hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></span>
				<?php if ( mmbn_opt( 'consent' ) ) : ?><p class="mmbn-alert__fine"><?php echo esc_html( (string) mmbn_opt( 'consent' ) ); ?></p><?php endif; ?>
				<p class="mmbn-alert__msg" role="status" aria-live="polite"></p>
			</form>
		</div>
		<?php
	},
	31
);

/* ---------------------------------------------------------------------------
 * Sign-up
 * ------------------------------------------------------------------------- */

/**
 * The theme's subscriber for an email, created through the theme's own newsletter store when new.
 */
function mmbn_subscriber( string $email ): int {
	$find = static function () use ( $email ) {
		if ( function_exists( 'mmm_sub_by_email' ) ) {
			return (int) mmm_sub_by_email( $email );
		}
		$ids = get_posts( array( 'post_type' => 'mm_subscriber', 'post_status' => 'any', 'title' => $email, 'posts_per_page' => 1, 'fields' => 'ids' ) );
		return $ids ? (int) $ids[0] : 0;
	};
	$id = $find();
	if ( $id ) {
		return $id;
	}
	if ( function_exists( 'mm_newsletter_store' ) ) {
		mm_newsletter_store( $email, 'price-alert' ); // Saves it and fires mm_new_subscriber (alerts, Mailer).
		return $find();
	}
	if ( ! post_type_exists( 'mm_subscriber' ) ) {
		return 0;
	}
	$id = wp_insert_post( array( 'post_type' => 'mm_subscriber', 'post_status' => 'publish', 'post_title' => $email ) );
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_mm_context', 'price-alert' );
		update_post_meta( $id, '_mm_consent', current_time( 'mysql' ) );
		do_action( 'mm_new_subscriber', $email, $id, 'price-alert' );
		return (int) $id;
	}
	return 0;
}

/**
 * Start watching a product's price.
 *
 * @return array{0:bool,1:string}
 */
function mmbn_watch( int $product_id, string $email ): array {
	global $wpdb;
	$email   = strtolower( sanitize_email( $email ) );
	$product = wc_get_product( $product_id );
	if ( ! is_email( $email ) ) {
		return array( false, __( 'Please enter a valid email address.', 'mine-mirth-buy-now' ) );
	}
	if ( ! $product || 'publish' !== $product->get_status() || null === mmbn_current_price( $product ) ) {
		return array( false, __( 'This product isn’t available for price alerts.', 'mine-mirth-buy-now' ) );
	}
	// Light rate limit per visitor (hashed IP, never stored raw).
	$key  = 'mmbn_rl_' . substr( hash( 'sha256', ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() ), 0, 16 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$hits = (int) get_transient( $key );
	if ( $hits >= 10 ) {
		return array( false, __( 'Too many attempts. Please try again in a few minutes.', 'mine-mirth-buy-now' ) );
	}
	set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

	$table = mmbn_table();
	$price = (float) mmbn_current_price( $product );
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT id, status FROM $table WHERE product_id = %d AND email = %s LIMIT 1", $product_id, $email ) ); // phpcs:ignore WordPress.DB
	/* translators: %s: email address */
	$done = sprintf( __( 'Done! We’ll email %s when this price changes.', 'mine-mirth-buy-now' ), $email );
	if ( $row && 'active' === $row->status ) {
		return array( true, __( 'You’re already watching this price. We’ll email you when it changes.', 'mine-mirth-buy-now' ) );
	}
	$sub = mmbn_subscriber( $email );
	if ( $row ) {
		$wpdb->update( $table, array( 'status' => 'active', 'last_price' => $price, 'sub_id' => $sub, 'created' => current_time( 'mysql', true ) ), array( 'id' => $row->id ) ); // phpcs:ignore WordPress.DB
		return array( true, $done );
	}
	$wpdb->insert( // phpcs:ignore WordPress.DB
		$table,
		array(
			'product_id' => $product_id,
			'sub_id'     => $sub,
			'email'      => $email,
			'token'      => bin2hex( random_bytes( 16 ) ),
			'last_price' => $price,
			'status'     => 'active',
			'created'    => current_time( 'mysql', true ),
		)
	);
	do_action( 'mmbn_new_watch', $product_id, $email, $sub );
	return array( true, $done );
}

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'mm-price-alerts/v1',
			'/watch',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true', // Public sign-up; works on cached pages (no nonce).
				'callback'            => static function ( WP_REST_Request $r ) {
					if ( '' !== (string) $r->get_param( 'website' ) ) {
						return new WP_REST_Response( array( 'message' => __( 'Thanks!', 'mine-mirth-buy-now' ) ), 200 );
					}
					[ $ok, $msg ] = mmbn_watch( absint( $r->get_param( 'product' ) ), (string) $r->get_param( 'email' ) );
					return new WP_REST_Response( array( 'message' => $msg ), $ok ? 200 : 400 );
				},
			)
		);
	}
);

// A logged-in admin's REST cookie without a nonce would be rejected on cached pages; this route needs no auth.
add_filter(
	'rest_authentication_errors',
	static function ( $result ) {
		$uri = (string) ( $_SERVER['REQUEST_URI'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( is_wp_error( $result ) && false !== strpos( $uri, 'mm-price-alerts/v1/watch' ) && 'rest_cookie_invalid_nonce' === $result->get_error_code() ) {
			return true;
		}
		return $result;
	},
	200
);

// No-JavaScript fallback.
foreach ( array( 'admin_post_mmbn_watch', 'admin_post_nopriv_mmbn_watch' ) as $hook ) {
	add_action(
		$hook,
		static function () {
			$back = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['back'] ?? '' ) ), home_url( '/' ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( empty( $_POST['website'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				[ $ok ] = mmbn_watch( absint( wp_unslash( $_POST['product'] ?? 0 ) ), sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$back   = add_query_arg( 'mmbn', $ok ? 'watching' : 'error', $back );
			}
			wp_safe_redirect( $back );
			exit;
		}
	);
}

add_action(
	'woocommerce_before_single_product',
	static function () {
		$state = sanitize_key( wp_unslash( $_GET['mmbn'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'watching' === $state ) {
			wc_print_notice( __( 'Price alert saved. We’ll email you when this price changes.', 'mine-mirth-buy-now' ), 'success' );
		} elseif ( 'error' === $state ) {
			wc_print_notice( __( 'We couldn’t save that price alert. Please check the email address and try again.', 'mine-mirth-buy-now' ), 'error' );
		}
	}
);

/* ---------------------------------------------------------------------------
 * Price-change detection
 * ------------------------------------------------------------------------- */

/**
 * Products waiting for a price check: [ product_id => due timestamp ]. Kept in an option so alerts
 * never depend on WP-Cron or loopback requests, which many hosts block.
 */
function mmbn_pending(): array {
	$p = get_option( 'mmbn_pending', array() );
	return is_array( $p ) ? $p : array();
}

function mmbn_queue_check( int $product_id ): void {
	static $seen = array();
	if ( ! $product_id || isset( $seen[ $product_id ] ) || ! mmbn_watch_count( $product_id ) ) {
		return;
	}
	$seen[ $product_id ]      = true;
	$pending                  = mmbn_pending();
	$pending[ $product_id ]   = time() + max( 0, (int) mmbn_opt( 'delay' ) ) * MINUTE_IN_SECONDS;
	update_option( 'mmbn_pending', $pending, true );
	// Backup trigger for delayed sends on sites with working cron.
	if ( ! wp_next_scheduled( 'mmbn_process' ) ) {
		wp_schedule_single_event( $pending[ $product_id ] + 30, 'mmbn_process' );
	}
}

add_action( 'woocommerce_update_product', static fn( $id ) => mmbn_queue_check( (int) $id ), 20 );
add_action( 'woocommerce_update_product_variation', static fn( $id ) => mmbn_queue_check( (int) wp_get_post_parent_id( (int) $id ) ), 20 );

/**
 * Send what's due. Runs at the end of the request that saved the price (so "Instantly" really is
 * instant), on later page loads for anything left over or delayed, and from cron when available.
 */
function mmbn_process( int $budget = 25 ): array {
	$pending = mmbn_pending();
	$total   = array( 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'products' => array() );
	if ( ! $pending || get_transient( 'mmbn_lock' ) ) {
		return $total;
	}
	set_transient( 'mmbn_lock', 1, 2 * MINUTE_IN_SECONDS );
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
	foreach ( $pending as $pid => $due ) {
		if ( $due > time() || $budget <= 0 ) {
			continue;
		}
		$r = mmbn_check_price( (int) $pid, $budget );
		$budget -= $r['sent'] + $r['failed'];
		foreach ( array( 'sent', 'failed', 'skipped' ) as $k ) {
			$total[ $k ] += $r[ $k ];
		}
		$total['products'][ (int) $pid ] = $r;
		$pending = mmbn_pending();
		if ( empty( $r['more'] ) ) {
			unset( $pending[ $pid ] );
		}
		update_option( 'mmbn_pending', $pending, true );
	}
	delete_transient( 'mmbn_lock' );
	return $total;
}

add_action( 'mmbn_process', static fn() => mmbn_process( 100 ) );

// Version 1.0.0 queued checks in Action Scheduler, which some hosts never run. Move them here once.
add_action(
	'init',
	static function () {
		if ( get_option( 'mmbn_migrated_101' ) || ! function_exists( 'as_get_scheduled_actions' ) ) {
			return;
		}
		update_option( 'mmbn_migrated_101', 1, false );
		$pending = mmbn_pending();
		foreach ( (array) as_get_scheduled_actions( array( 'hook' => 'mmbn_check_price', 'status' => 'pending', 'per_page' => 200 ) ) as $action ) {
			$args = $action->get_args();
			if ( ! empty( $args[0] ) ) {
				$pending[ (int) $args[0] ] = time();
			}
		}
		as_unschedule_all_actions( 'mmbn_check_price' );
		// Anyone whose remembered price differs from today's price is due an alert.
		global $wpdb;
		foreach ( (array) $wpdb->get_col( 'SELECT DISTINCT product_id FROM ' . mmbn_table() . " WHERE status = 'active'" ) as $pid ) { // phpcs:ignore WordPress.DB
			$pending[ (int) $pid ] = $pending[ (int) $pid ] ?? time();
		}
		update_option( 'mmbn_pending', $pending, true );
	},
	30
);
add_action( 'mmbn_check_price', static fn( $id ) => mmbn_check_price( (int) $id, 100 ) ); // Actions queued by version 1.0.0.

// After any request (including the product save), send what's due; tell the admin who saved it.
add_action(
	'shutdown',
	static function () {
		$pending = mmbn_pending();
		if ( ! $pending || min( $pending ) > time() ) {
			return;
		}
		$r = mmbn_process( is_admin() ? 25 : 10 );
		if ( ( $r['sent'] || $r['failed'] || $r['skipped'] ) && is_user_logged_in() && current_user_can( 'edit_products' ) ) {
			set_transient( 'mmbn_notice_' . get_current_user_id(), $r, 10 * MINUTE_IN_SECONDS );
		}
	},
	1
);

add_action(
	'admin_notices',
	static function () {
		$key = 'mmbn_notice_' . get_current_user_id();
		$r   = get_transient( $key );
		if ( ! $r ) {
			return;
		}
		delete_transient( $key );
		foreach ( (array) $r['products'] as $pid => $p ) {
			$msg = sprintf(
				/* translators: 1: product, 2: sent count */
				__( 'Price alerts for %1$s: sent to %2$d.', 'mine-mirth-buy-now' ),
				get_the_title( (int) $pid ),
				(int) $p['sent']
			);
			if ( $p['failed'] ) {
				/* translators: 1: count, 2: error */
				$msg .= ' ' . sprintf( __( '%1$d could not be sent: %2$s', 'mine-mirth-buy-now' ), (int) $p['failed'], $p['error'] );
			}
			if ( $p['skipped'] && ! $p['sent'] ) {
				$msg .= ' ' . $p['note'];
			}
			$type = $p['failed'] ? 'error' : ( $p['sent'] ? 'success' : 'info' );
			echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p><span class="dashicons dashicons-bell" style="color:#35a8f6"></span> ' . esc_html( $msg ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=mmbn' ) ) . '">' . esc_html__( 'Alert log', 'mine-mirth-buy-now' ) . '</a></p></div>';
		}
	}
);

/**
 * Compare each watcher's last-seen price with the current price and send alerts.
 *
 * @return array{sent:int,failed:int,skipped:int,more:bool,note:string,error:string}
 */
function mmbn_check_price( int $product_id, int $limit = 25 ): array {
	global $wpdb;
	$out     = array( 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'more' => false, 'note' => '', 'error' => '' );
	$product = wc_get_product( $product_id );
	if ( ! $product || 'publish' !== $product->get_status() ) {
		$out['note'] = __( 'The product isn’t published.', 'mine-mirth-buy-now' );
		return $out;
	}
	$now = mmbn_current_price( $product );
	if ( null === $now || $now <= 0 ) {
		$out['note'] = __( 'The product has no price.', 'mine-mirth-buy-now' );
		return $out;
	}
	$table = mmbn_table();
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE product_id = %d AND status = 'active' AND ABS(last_price - %f) > 0.000001 ORDER BY id ASC LIMIT %d", $product_id, $now, max( 1, $limit ) + 1 ) ); // phpcs:ignore WordPress.DB
	if ( ! $rows ) {
		$out['note'] = __( 'Nobody is waiting on a different price.', 'mine-mirth-buy-now' );
		return $out;
	}
	if ( count( $rows ) > $limit ) {
		$out['more'] = true;
		array_pop( $rows );
	}
	foreach ( $rows as $w ) {
		$was = (float) $w->last_price;
		if ( $was <= 0 ) {
			$wpdb->update( $table, array( 'last_price' => $now ), array( 'id' => $w->id ) ); // phpcs:ignore WordPress.DB
			continue;
		}
		$dir    = $now < $was ? 'down' : 'up';
		$change = abs( $now - $was ) / $was * 100;
		if ( $change + 1e-9 < (float) mmbn_opt( 'min_change' ) ) {
			++$out['skipped'];
			/* translators: %s: percent */
			$out['note'] = sprintf( __( 'Not sent: the change is smaller than the %s%% minimum (Settings).', 'mine-mirth-buy-now' ), mmbn_opt( 'min_change' ) );
			mmbn_log( $product_id, (string) $w->email, $was, $now, false, $out['note'] );
			continue; // Keep their price so small changes can add up.
		}
		if ( ! mmbn_opt( 'down' === $dir ? 'notify_down' : 'notify_up' ) ) {
			++$out['skipped'];
			$out['note'] = 'down' === $dir ? __( 'Not sent: alerts for price drops are turned off.', 'mine-mirth-buy-now' ) : __( 'Not sent: alerts for price increases are turned off.', 'mine-mirth-buy-now' );
			$wpdb->update( $table, array( 'last_price' => $now ), array( 'id' => $w->id ) ); // phpcs:ignore WordPress.DB
			continue;
		}
		// Respect a Mailer unsubscribe made after they set up the alert.
		if ( (int) $w->sub_id && function_exists( 'mmm_sub_is_active' ) && ! mmm_sub_is_active( (int) $w->sub_id ) && (int) get_post_meta( (int) $w->sub_id, '_mmm_unsub_at', true ) > (int) strtotime( $w->created . ' UTC' ) ) {
			++$out['skipped'];
			$wpdb->update( $table, array( 'status' => 'stopped' ), array( 'id' => $w->id ) ); // phpcs:ignore WordPress.DB
			mmbn_log( $product_id, (string) $w->email, $was, $now, false, __( 'Not sent: they unsubscribed from your emails.', 'mine-mirth-buy-now' ) );
			continue;
		}
		[ $ok, $error ] = mmbn_send( $w, mmbn_compose( $w, $product, $was, $now ) );
		mmbn_log( $product_id, (string) $w->email, $was, $now, $ok, $ok ? '' : $error );
		if ( $ok ) {
			++$out['sent'];
			$wpdb->update( // phpcs:ignore WordPress.DB
				$table,
				array( 'last_price' => $now, 'notified' => current_time( 'mysql', true ), 'alerts' => (int) $w->alerts + 1 ),
				array( 'id' => $w->id )
			);
		} else {
			++$out['failed'];
			$out['error'] = $error;
		}
	}
	return $out;
}

function mmbn_log( int $product_id, string $email, float $was, float $now, bool $ok, string $note = '' ): void {
	$log = (array) get_option( 'mmbn_log', array() );
	array_unshift( $log, array( 't' => time(), 'p' => $product_id, 'e' => $email, 'was' => $was, 'now' => $now, 'ok' => $ok, 'note' => $note ) );
	update_option( 'mmbn_log', array_slice( $log, 0, 100 ), false );
}

/* ---------------------------------------------------------------------------
 * Emails
 * ------------------------------------------------------------------------- */

function mmbn_stop_url( object $w ): string {
	return add_query_arg( array( 'mmbn_stop' => (int) $w->id, 't' => $w->token ), home_url( '/' ) );
}

/**
 * Subject, HTML and text for one alert. Uses the Mine Mirth Mailer design when it's active.
 *
 * @return array{subject:string,html:string,text:string}
 */
function mmbn_compose( object $w, WC_Product $product, float $was, float $now ): array {
	$name   = $product->get_name();
	$down   = $now < $was;
	$diff   = abs( $now - $was );
	$pct    = (int) round( $diff / $was * 100 );
	$first  = ( (int) $w->sub_id && function_exists( 'mmm_sub_first_name' ) ) ? mmm_sub_first_name( (int) $w->sub_id ) : '';
	$buy    = mmbn_buy_url( $product );
	$view   = (string) get_permalink( $product->get_id() );
	$stop   = mmbn_stop_url( $w );
	$stock  = $product->is_in_stock() ? ( $product->is_on_backorder() ? __( 'Available to pre-order', 'mine-mirth-buy-now' ) : __( 'In stock now', 'mine-mirth-buy-now' ) ) : __( 'Currently sold out', 'mine-mirth-buy-now' );
	$cta    = $product->is_in_stock() ? ( $product->is_type( 'simple' ) ? __( 'Buy it now', 'mine-mirth-buy-now' ) : __( 'Choose options', 'mine-mirth-buy-now' ) ) : __( 'View product', 'mine-mirth-buy-now' );
	$wasf   = mmbn_plain_price( $was );
	$nowf   = mmbn_plain_price( $now );
	if ( $down ) {
		/* translators: 1: product, 2: new price */
		$subject = sprintf( __( 'Price drop: %1$s is now %2$s', 'mine-mirth-buy-now' ), $name, $nowf );
		/* translators: 1: amount saved, 2: percent */
		$pre     = sprintf( __( 'Save %1$s (%2$d%% off) on the miner you’re watching.', 'mine-mirth-buy-now' ), mmbn_plain_price( $diff ), $pct );
		$eyebrow = __( 'Price drop', 'mine-mirth-buy-now' );
		/* translators: %s: first name */
		$head    = $first ? sprintf( __( 'Good news, %s: the price just dropped', 'mine-mirth-buy-now' ), $first ) : __( 'Good news: the price just dropped', 'mine-mirth-buy-now' );
		/* translators: %s: product */
		$intro   = sprintf( __( 'The %s you asked us to watch is cheaper today. Miner prices move with the market, so if this is the one, now is a good moment.', 'mine-mirth-buy-now' ), $name );
		$tone    = '#16a34a';
	} else {
		/* translators: %s: product */
		$subject = sprintf( __( 'Heads up: the %s price went up', 'mine-mirth-buy-now' ), $name );
		/* translators: 1: old price, 2: new price */
		$pre     = sprintf( __( 'It moved from %1$s to %2$s. Here’s where it stands.', 'mine-mirth-buy-now' ), $wasf, $nowf );
		$eyebrow = __( 'Price update', 'mine-mirth-buy-now' );
		/* translators: %s: first name */
		$head    = $first ? sprintf( __( '%s, the price you’re watching changed', 'mine-mirth-buy-now' ), $first ) : __( 'The price you’re watching changed', 'mine-mirth-buy-now' );
		/* translators: %s: product */
		$intro   = sprintf( __( 'The %s went up in price. Prices follow the market and stock levels, so they can keep moving. If you’ve been planning to buy, ordering soon locks in today’s price.', 'mine-mirth-buy-now' ), $name );
		$tone    = '#d97706';
	}
	$change = ( $down ? '−' : '+' ) . mmbn_plain_price( $diff ) . ' (' . ( $down ? '−' : '+' ) . $pct . '%)';

	// Price box: was → now, shared by both designs.
	$box = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;margin:8px 0 22px;border:1px solid #d9e4ef;border-radius:16px;background:#f4f8fc"><tr>'
		. '<td class="mmm-col" width="40%" valign="top" style="padding:18px 20px;font-family:Inter,Arial,sans-serif"><div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:1px">' . esc_html__( 'Was', 'mine-mirth-buy-now' ) . '</div><div style="font-family:\'JetBrains Mono\',Menlo,monospace;font-size:20px;color:#64748b;text-decoration:line-through">' . esc_html( $wasf ) . '</div></td>'
		. '<td class="mmm-col" width="60%" valign="top" style="padding:18px 20px;font-family:Inter,Arial,sans-serif"><div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:1px">' . esc_html__( 'Now', 'mine-mirth-buy-now' ) . '</div><div style="font-family:\'JetBrains Mono\',Menlo,monospace;font-size:26px;font-weight:700;color:#0f172a">' . esc_html( $nowf ) . '</div><div style="padding-top:4px;font-size:14px;font-weight:700;color:' . esc_attr( $tone ) . '">' . esc_html( $change ) . ' <span style="font-weight:400;color:#64748b">· ' . esc_html( $stock ) . '</span></div></td>'
		. '</tr></table>';
	$fine = '<p style="margin:18px 0 0;font-family:Inter,Arial,sans-serif;font-size:12px;line-height:1.6;color:#64748b">'
		. esc_html__( 'You’re getting this because you asked for price alerts on this product. Prices can change again at any time.', 'mine-mirth-buy-now' )
		. ' <a href="' . esc_url( $stop ) . '" style="color:#64748b">' . esc_html__( 'Stop alerts for this product', 'mine-mirth-buy-now' ) . '</a></p>';

	if ( function_exists( 'mmm_document' ) && function_exists( 'mmm_personalize' ) ) {
		$body  = mmm_c_heading( $head, 1, $eyebrow );
		$body .= mmm_c_text( esc_html( $intro ) );
		$body .= $box;
		$body .= mmm_c_button( $cta, $product->is_in_stock() ? $buy : $view );
		$body .= mmm_c_spacer( 24 );
		$body .= mmm_c_products( array( $product->get_id() ) );
		if ( function_exists( 'mmm_c_reply' ) ) {
			$body .= mmm_c_reply( __( 'Buying more than one?', 'mine-mirth-buy-now' ), __( 'Tell us how many units you need and we’ll send a quote, including volume pricing and hosting.', 'mine-mirth-buy-now' ), __( 'Get a quote', 'mine-mirth-buy-now' ), 'quote' );
		}
		$body .= $fine;
		$html  = mmm_document( array( 'subject' => $subject, 'preheader' => $pre, 'body' => $body, 'campaign' => 0, 'label' => __( 'Price alert', 'mine-mirth-buy-now' ) ) );
		$html  = (int) $w->sub_id ? mmm_personalize( $html, (int) $w->sub_id, 0 ) : str_replace( array( '%%MMM_OPEN%%', '%%MMM_TOKEN%%', '%%MMM_UNSUB%%', '%%MMM_VIEW%%', '%%MMM_REPLY%%' ), array( includes_url( 'images/blank.gif' ), '', esc_url( $stop ), '', esc_url( $view ) ), $html );
		$text  = function_exists( 'mmm_plain_text' ) ? mmm_plain_text( $html ) : wp_strip_all_tags( $html );
		return array( 'subject' => $subject, 'html' => $html, 'text' => $text );
	}

	// Fallback design (Mailer not active): same colours as the site.
	$primary = function_exists( 'mm_opt' ) && mm_opt( 'color_primary' ) ? (string) mm_opt( 'color_primary' ) : '#35a8f6';
	$site    = get_bloginfo( 'name' );
	$html    = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html( $subject ) . '</title></head>'
		. '<body style="margin:0;background:#eef3f8"><div style="display:none;max-height:0;overflow:hidden">' . esc_html( $pre ) . '</div>'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef3f8"><tr><td align="center" style="padding:24px 12px">'
		. '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;border-collapse:separate">'
		. '<tr><td style="background:#050b14;border-radius:20px 20px 0 0;padding:22px 32px;font-family:Inter,Arial,sans-serif;font-size:19px;font-weight:700;color:#fff">' . esc_html( $site ) . '</td></tr>'
		. '<tr><td style="height:4px;background:' . esc_attr( $primary ) . '"></td></tr>'
		. '<tr><td style="background:#fff;padding:32px;font-family:Inter,Arial,sans-serif;color:#0f172a">'
		. '<div style="font-family:Menlo,monospace;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:' . esc_attr( $primary ) . '">' . esc_html( $eyebrow ) . '</div>'
		. '<h1 style="margin:6px 0 12px;font-size:26px;line-height:1.25">' . esc_html( $head ) . '</h1>'
		. '<p style="margin:0 0 16px;font-size:16px;line-height:1.65;color:#334155">' . esc_html( $intro ) . '</p>'
		. '<p style="margin:0 0 4px;font-size:17px;font-weight:700"><a href="' . esc_url( $view ) . '" style="color:#0f172a;text-decoration:none">' . esc_html( $name ) . '</a></p>'
		. $box
		. '<a href="' . esc_url( $product->is_in_stock() ? $buy : $view ) . '" style="display:inline-block;padding:14px 26px;border-radius:999px;background:' . esc_attr( $primary ) . ';color:#04121f;font-weight:700;text-decoration:none">' . esc_html( $cta ) . '</a>'
		. $fine
		. '</td></tr><tr><td style="background:#050b14;border-radius:0 0 20px 20px;padding:20px 32px;font-family:Inter,Arial,sans-serif;font-size:12px;color:#8ea3bb">' . esc_html( $site ) . ' · <a href="' . esc_url( home_url( '/' ) ) . '" style="color:#cfe0f2">' . esc_html( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) . '</a></td></tr>'
		. '</table></td></tr></table></body></html>';
	return array( 'subject' => $subject, 'html' => $html, 'text' => $intro . "\n\n" . $name . ': ' . $wasf . ' → ' . $nowf . "\n" . $view . "\n\n" . __( 'Stop alerts for this product:', 'mine-mirth-buy-now' ) . ' ' . $stop );
}

/**
 * @return array{0:bool,1:string}
 */
function mmbn_send( object $w, array $mail ): array {
	if ( function_exists( 'mmm_mail' ) ) {
		[ $ok, $error ] = mmm_mail( (string) $w->email, $mail['subject'], $mail['html'], $mail['text'], (int) $w->sub_id, 0 );
		return array( (bool) $ok, (string) $error );
	}
	$error = '';
	$catch = static function ( $e ) use ( &$error ) {
		$error = is_wp_error( $e ) ? $e->get_error_message() : 'Unknown error';
	};
	add_action( 'wp_mail_failed', $catch );
	$headers = array( 'Content-Type: text/html; charset=UTF-8', 'List-Unsubscribe: <' . mmbn_stop_url( $w ) . '>', 'List-Unsubscribe-Post: List-Unsubscribe=One-Click' );
	$ok      = (bool) wp_mail( (string) $w->email, $mail['subject'], $mail['html'], $headers );
	remove_action( 'wp_mail_failed', $catch );
	return array( $ok, $ok ? '' : ( $error ? $error : __( 'The server did not accept the email. Check your SMTP plugin.', 'mine-mirth-buy-now' ) ) );
}

/* ---------------------------------------------------------------------------
 * Stop link
 * ------------------------------------------------------------------------- */

add_action(
	'template_redirect',
	static function () {
		if ( empty( $_GET['mmbn_stop'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		global $wpdb;
		$id    = absint( wp_unslash( $_GET['mmbn_stop'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$token = sanitize_key( wp_unslash( $_GET['t'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$w     = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . mmbn_table() . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
		$valid = $w && hash_equals( (string) $w->token, $token );
		if ( $valid ) {
			$wpdb->update( mmbn_table(), array( 'status' => 'stopped' ), array( 'id' => $id ) ); // phpcs:ignore WordPress.DB
		}
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			status_header( 200 );
			exit;
		}
		$title = $valid ? __( 'Price alerts stopped', 'mine-mirth-buy-now' ) : __( 'Link expired', 'mine-mirth-buy-now' );
		$text  = $valid
			/* translators: %s: product name */
			? sprintf( __( 'You won’t get more price alerts for %s.', 'mine-mirth-buy-now' ), get_the_title( (int) $w->product_id ) )
			: __( 'This link isn’t valid any more.', 'mine-mirth-buy-now' );
		status_header( 200 );
		nocache_headers();
		get_header();
		echo '<main id="main" class="mm-container" style="padding-block:clamp(48px,8vw,110px);max-width:720px"><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $text ) . '</p>';
		if ( $valid ) {
			echo '<p><a class="mm-btn mm-btn--primary" href="' . esc_url( get_permalink( (int) $w->product_id ) ) . '">' . esc_html__( 'Back to the product', 'mine-mirth-buy-now' ) . '</a></p>';
		}
		echo '</main>';
		get_footer();
		exit;
	}
);
