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

function mmbn_queue_table(): string {
	global $wpdb;
	return $wpdb->prefix . 'mmbn_queue';
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
	$queue = mmbn_queue_table();
	dbDelta(
		"CREATE TABLE $queue (
			product_id bigint(20) unsigned NOT NULL,
			due datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			PRIMARY KEY  (product_id),
			KEY due (due)
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

/**
 * The stored active price, read straight from the database so no object cache (LiteSpeed, Redis,
 * Memcached) can hand back an old value. Variable products store one row per variation price.
 */
function mmbn_db_price( int $product_id ): ?float {
	global $wpdb;
	$v = $wpdb->get_var( $wpdb->prepare( "SELECT MIN(CAST(meta_value AS DECIMAL(20,6))) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_price' AND meta_value <> ''", $product_id ) ); // phpcs:ignore WordPress.DB
	return null === $v || '' === $v ? null : (float) $v;
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

// Lets the stylesheet hide the alert box until its entrance animation runs (only when scripts run).
add_action(
	'wp_head',
	static function () {
		if ( mmbn_opt( 'alerts' ) && function_exists( 'is_product' ) && is_product() ) {
			echo "<script data-no-optimize=\"1\" data-no-defer=\"1\" data-no-minify=\"1\" data-cfasync=\"false\" nowprocket>document.documentElement.classList.add('mmbn-js');</script>\n";
		}
	},
	1
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
	$price = mmbn_db_price( $product_id ) ?? (float) mmbn_current_price( $product );
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT id, status FROM $table WHERE product_id = %d AND email = %s LIMIT 1", $product_id, $email ) ); // phpcs:ignore WordPress.DB
	/* translators: %s: email address */
	$done = sprintf( __( 'Done! We’ll email %s when this price changes.', 'mine-mirth-buy-now' ), $email );
	if ( $row && 'active' === $row->status ) {
		return array( true, __( 'You’re already watching this price. We’ll email you when it changes.', 'mine-mirth-buy-now' ) );
	}
	$sub = mmbn_subscriber( $email );
	if ( $row ) {
		$wpdb->update( $table, array( 'status' => 'active', 'last_price' => $price, 'sub_id' => $sub, 'created' => current_time( 'mysql', true ) ), array( 'id' => $row->id ) ); // phpcs:ignore WordPress.DB
		mmbn_purge( $product_id );
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
	mmbn_purge( $product_id );
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
 * Products whose price was saved during this request.
 */
function mmbn_touched( int $product_id = 0 ): array {
	static $ids = array();
	if ( $product_id ) {
		$ids[ $product_id ] = true;
	}
	return array_keys( $ids );
}

/**
 * Queue rows live in their own table and are read with plain SQL, never through options or
 * transients, so caching plugins can't hide them.
 */
function mmbn_enqueue( int $product_id, int $when ): void {
	global $wpdb;
	$wpdb->replace( mmbn_queue_table(), array( 'product_id' => $product_id, 'due' => gmdate( 'Y-m-d H:i:s', $when ) ) ); // phpcs:ignore WordPress.DB
	if ( ! wp_next_scheduled( 'mmbn_process' ) ) {
		wp_schedule_single_event( $when + 30, 'mmbn_process' ); // Backup only.
	}
}

function mmbn_queue_check( int $product_id ): void {
	if ( ! $product_id || in_array( $product_id, mmbn_touched(), true ) || ! mmbn_watch_count( $product_id ) ) {
		return;
	}
	$delay = max( 0, (int) mmbn_opt( 'delay' ) );
	if ( $delay ) {
		mmbn_enqueue( $product_id, time() + $delay * MINUTE_IN_SECONDS );
		return;
	}
	mmbn_touched( $product_id ); // Sent at the end of this request (below).
}

add_action( 'woocommerce_update_product', static fn( $id ) => mmbn_queue_check( (int) $id ), 20 );
add_action( 'woocommerce_update_product_variation', static fn( $id ) => mmbn_queue_check( (int) wp_get_post_parent_id( (int) $id ) ), 20 );

/**
 * Send alerts for products saved in this request, then anything due in the queue.
 *
 * @return array{sent:int,failed:int,skipped:int,products:array}
 */
function mmbn_process( int $budget = 25 ): array {
	global $wpdb;
	$total = array( 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'products' => array() );
	$run   = static function ( int $pid ) use ( &$total, &$budget ) {
		$r       = mmbn_check_price( $pid, max( 1, $budget ) );
		$budget -= $r['sent'] + $r['failed'];
		foreach ( array( 'sent', 'failed', 'skipped' ) as $k ) {
			$total[ $k ] += $r[ $k ];
		}
		$total['products'][ $pid ] = $r;
		if ( $r['more'] ) {
			mmbn_enqueue( $pid, time() );
		}
		if ( $r['sent'] ) {
			mmbn_purge( $pid );
		}
	};
	foreach ( mmbn_touched() as $pid ) {
		$run( (int) $pid );
	}
	$queue = mmbn_queue_table();
	$due   = (array) $wpdb->get_col( $wpdb->prepare( "SELECT product_id FROM $queue WHERE due <= %s ORDER BY due ASC LIMIT 5", gmdate( 'Y-m-d H:i:s' ) ) ); // phpcs:ignore WordPress.DB
	foreach ( $due as $pid ) {
		if ( $budget <= 0 ) {
			break;
		}
		// Claim the row first; if another request already took it, skip.
		if ( 1 !== (int) $wpdb->query( $wpdb->prepare( "DELETE FROM $queue WHERE product_id = %d", (int) $pid ) ) ) { // phpcs:ignore WordPress.DB
			continue;
		}
		$run( (int) $pid );
	}
	return $total;
}

add_action( 'mmbn_process', static fn() => mmbn_process( 100 ) );
add_action( 'mmbn_check_price', static fn( $id ) => mmbn_enqueue( (int) $id, time() ) ); // Old 1.0.0 jobs.

// Move anything left by versions 1.0.0 / 1.0.1 into the queue table, once.
add_action(
	'init',
	static function () {
		if ( get_option( 'mmbn_migrated_102' ) || (int) get_option( 'mmbn_db' ) < 3 ) {
			return;
		}
		update_option( 'mmbn_migrated_102', 1, false );
		foreach ( (array) get_option( 'mmbn_pending', array() ) as $pid => $due ) {
			mmbn_enqueue( (int) $pid, min( time(), (int) $due ) );
		}
		delete_option( 'mmbn_pending' );
		delete_transient( 'mmbn_lock' );
		if ( function_exists( 'as_get_scheduled_actions' ) ) {
			foreach ( (array) as_get_scheduled_actions( array( 'hook' => 'mmbn_check_price', 'status' => 'pending', 'per_page' => 200 ) ) as $action ) {
				$args = $action->get_args();
				if ( ! empty( $args[0] ) ) {
					mmbn_enqueue( (int) $args[0], time() );
				}
			}
			as_unschedule_all_actions( 'mmbn_check_price' );
		}
		// Anyone whose remembered price differs from today's price is due an alert.
		global $wpdb;
		foreach ( (array) $wpdb->get_col( 'SELECT DISTINCT product_id FROM ' . mmbn_table() . " WHERE status = 'active'" ) as $pid ) { // phpcs:ignore WordPress.DB
			mmbn_enqueue( (int) $pid, time() );
		}
	},
	30
);

// Runs before caching plugins finish the response (LiteSpeed sends its purge header at priority 0).
add_action(
	'shutdown',
	static function () {
		global $wpdb;
		if ( ! mmbn_touched() ) {
			$queue = mmbn_queue_table();
			if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM $queue WHERE due <= %s LIMIT 1", gmdate( 'Y-m-d H:i:s' ) ) ) ) { // phpcs:ignore WordPress.DB
				return;
			}
		}
		$r = mmbn_process( is_admin() ? 50 : 10 );
		if ( ( $r['sent'] || $r['failed'] || $r['skipped'] ) && is_user_logged_in() && current_user_can( 'edit_products' ) ) {
			update_user_meta( get_current_user_id(), '_mmbn_notice', $r );
		}
	},
	-20
);

add_action(
	'admin_notices',
	static function () {
		$r = get_user_meta( get_current_user_id(), '_mmbn_notice', true );
		if ( ! $r || ! is_array( $r ) ) {
			return;
		}
		delete_user_meta( get_current_user_id(), '_mmbn_notice' );
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
			if ( ! $p['sent'] && $p['note'] ) {
				$msg .= ' ' . $p['note'];
			}
			if ( $p['sent'] && mmbn_opt( 'purge' ) ) {
				$msg .= ' ' . __( 'Caches purged.', 'mine-mirth-buy-now' );
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
	$out = array( 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'more' => false, 'note' => '', 'error' => '' );
	// Fresh data only: drop any cached copy of this product first.
	clean_post_cache( $product_id );
	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients( $product_id );
	}
	$status = $wpdb->get_var( $wpdb->prepare( "SELECT post_status FROM {$wpdb->posts} WHERE ID = %d", $product_id ) ); // phpcs:ignore WordPress.DB
	$product = 'publish' === $status ? wc_get_product( $product_id ) : null;
	if ( ! $product ) {
		$out['note'] = __( 'The product isn’t published.', 'mine-mirth-buy-now' );
		return $out;
	}
	$now = mmbn_db_price( $product_id ) ?? mmbn_current_price( $product );
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
		// Claim this watcher for this price before emailing, so two requests can never send twice.
		$claimed = $wpdb->query( $wpdb->prepare( "UPDATE $table SET last_price = %f WHERE id = %d AND last_price = %s", $now, (int) $w->id, (string) $w->last_price ) ); // phpcs:ignore WordPress.DB
		if ( 1 !== (int) $claimed || $was <= 0 ) {
			continue;
		}
		// Every change counts, however small (even a few cents).
		$dir = $now < $was ? 'down' : 'up';
		if ( ! mmbn_opt( 'down' === $dir ? 'notify_down' : 'notify_up' ) ) {
			++$out['skipped'];
			$out['note'] = 'down' === $dir ? __( 'Not sent: alerts for price drops are turned off.', 'mine-mirth-buy-now' ) : __( 'Not sent: alerts for price increases are turned off.', 'mine-mirth-buy-now' );
			continue;
		}
		// Respect a Mailer unsubscribe made after they set up the alert.
		if ( (int) $w->sub_id && function_exists( 'mmm_sub_is_active' ) && ! mmm_sub_is_active( (int) $w->sub_id ) && (int) get_post_meta( (int) $w->sub_id, '_mmm_unsub_at', true ) > (int) strtotime( $w->created . ' UTC' ) ) {
			++$out['skipped'];
			$wpdb->update( $table, array( 'status' => 'stopped' ), array( 'id' => $w->id ) ); // phpcs:ignore WordPress.DB
			mmbn_log( $product_id, (string) $w->email, $was, $now, false, __( 'Not sent: they unsubscribed from your emails.', 'mine-mirth-buy-now' ) );
			continue;
		}
		$mail                 = mmbn_compose( $w, $product, $was, $now );
		[ $ok, $error, $via ] = mmbn_send( $w, $mail );
		mmbn_log( $product_id, (string) $w->email, $was, $now, $ok, $ok ? '' : $error, $via );
		// Optional copy to the shop owner (once per price change) to confirm alerts arrive.
		$copy = sanitize_email( (string) mmbn_opt( 'copy_to' ) );
		if ( $ok && $copy && empty( $out['copied'] ) && strtolower( $copy ) !== strtolower( (string) $w->email ) ) {
			$out['copied'] = true;
			$owner         = (object) array( 'id' => 0, 'token' => '', 'sub_id' => 0, 'email' => $copy, 'created' => current_time( 'mysql', true ) );
			$copy_mail     = mmbn_compose( $owner, $product, $was, $now ); // Its own email: no customer links inside.
			$copy_mail['subject'] = '[' . __( 'Copy', 'mine-mirth-buy-now' ) . '] ' . $copy_mail['subject'];
			mmbn_send( $owner, $copy_mail );
		}
		if ( $ok ) {
			++$out['sent'];
			$wpdb->query( $wpdb->prepare( "UPDATE $table SET notified = %s, alerts = alerts + 1 WHERE id = %d", current_time( 'mysql', true ), (int) $w->id ) ); // phpcs:ignore WordPress.DB
		} else {
			// Put their old price back so "Send alerts now" (or the next change) tries again.
			$wpdb->update( $table, array( 'last_price' => $w->last_price ), array( 'id' => $w->id ) ); // phpcs:ignore WordPress.DB
			++$out['failed'];
			$out['error'] = $error;
		}
	}
	return $out;
}

/* ---------------------------------------------------------------------------
 * Cache purge
 * ------------------------------------------------------------------------- */

/**
 * Purge caches after an alert action (sign-up, alerts sent, alerts stopped, settings saved), so
 * visitors see the new price and watcher count straight away. Works with LiteSpeed Cache, WP Rocket,
 * W3 Total Cache, WP Super Cache, WP Fastest Cache, SiteGround, Cloudflare, Breeze, Hummingbird,
 * Nginx Helper, Autoptimize and Comet Cache. The object cache is left alone on purpose: flushing it
 * can throw away emails an SMTP plugin has queued.
 */
function mmbn_purge( int $product_id = 0, bool $force_all = false ): void {
	static $done = array();
	if ( ! mmbn_opt( 'purge' ) && ! $force_all ) {
		return;
	}
	$all = $force_all || 'all' === mmbn_opt( 'purge_scope' ) || ! $product_id;
	$key = $all ? 'all' : 'p' . $product_id;
	if ( isset( $done[ $key ] ) || isset( $done['all'] ) ) {
		return;
	}
	$done[ $key ] = true;

	if ( $all ) {
		do_action( 'litespeed_purge_all' );                                   // LiteSpeed Cache (page, CSS/JS, object).
		function_exists( 'rocket_clean_domain' ) && rocket_clean_domain();     // WP Rocket.
		function_exists( 'w3tc_flush_all' ) && w3tc_flush_all();               // W3 Total Cache.
		function_exists( 'wp_cache_clear_cache' ) && wp_cache_clear_cache();   // WP Super Cache.
		do_action( 'wpfc_clear_all_cache' );                                   // WP Fastest Cache.
		function_exists( 'sg_cachepress_purge_everything' ) ? sg_cachepress_purge_everything() : ( function_exists( 'sg_cachepress_purge_cache' ) && sg_cachepress_purge_cache() ); // SiteGround.
		do_action( 'breeze_clear_all_cache' );                                 // Breeze.
		do_action( 'wphb_clear_page_cache' );                                  // Hummingbird.
		do_action( 'rt_nginx_helper_purge_all' );                              // Nginx Helper.
		do_action( 'cloudflare_purge_everything' );                            // Cloudflare (if hooked).
		class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) && autoptimizeCache::clearall(); // Autoptimize.
		class_exists( 'comet_cache' ) && method_exists( 'comet_cache', 'clear' ) && comet_cache::clear(); // Comet Cache.
		wp_cache_flush();                                                      // Object cache (Redis, Memcached, LiteSpeed).
	} else {
		$url = (string) get_permalink( $product_id );
		do_action( 'litespeed_purge_post', $product_id );
		function_exists( 'rocket_clean_post' ) && rocket_clean_post( $product_id );
		function_exists( 'w3tc_flush_post' ) && w3tc_flush_post( $product_id );
		function_exists( 'wp_cache_post_change' ) && wp_cache_post_change( $product_id );
		do_action( 'wpfc_clear_post_cache_by_id', false, $product_id );
		function_exists( 'sg_cachepress_purge_cache' ) && sg_cachepress_purge_cache( $url );
		do_action( 'rt_nginx_helper_purge_url', $url );
		clean_post_cache( $product_id );
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients( $product_id );
		}
	}
	update_option( 'mmbn_last_purge', time(), false );
	do_action( 'mmbn_purged', $product_id, $all );
}

function mmbn_log( int $product_id, string $email, float $was, float $now, bool $ok, string $note = '', string $via = '' ): void {
	$log = (array) get_option( 'mmbn_log', array() );
	array_unshift( $log, array( 't' => time(), 'p' => $product_id, 'e' => $email, 'was' => $was, 'now' => $now, 'ok' => $ok, 'note' => $note, 'via' => $via ) );
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
	if ( ! mmbn_opt( 'track' ) ) {
		// Plain links and no tracking pixel: alerts look personal, not bulk, so Gmail keeps them in the inbox.
		$no_track = static function ( $v ) {
			$v                 = is_array( $v ) ? $v : array();
			$v['track_clicks'] = 0;
			$v['track_opens']  = 0;
			return $v;
		};
		add_filter( 'option_mmm_settings', $no_track, 99 );
		add_filter( 'default_option_mmm_settings', $no_track, 99 );
		$mail = mmbn_compose_inner( $w, $product, $was, $now );
		remove_filter( 'option_mmm_settings', $no_track, 99 );
		remove_filter( 'default_option_mmm_settings', $no_track, 99 );
		return $mail;
	}
	return mmbn_compose_inner( $w, $product, $was, $now );
}

/**
 * @return array{subject:string,html:string,text:string}
 */
function mmbn_compose_inner( object $w, WC_Product $product, float $was, float $now ): array {
	$name   = $product->get_name();
	$down   = $now < $was;
	$diff   = abs( $now - $was );
	$raw    = $diff / $was * 100;
	$pct    = $raw >= 1 ? number_format_i18n( $raw, 0 ) : number_format_i18n( max( 0.01, $raw ), 2 ); // "0.02%" for tiny changes, never "0%".
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
		$pre     = sprintf( __( 'Save %1$s (%2$s%% off) on the miner you’re watching.', 'mine-mirth-buy-now' ), mmbn_plain_price( $diff ), $pct );
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
 * Send one alert and record how it left the site (SMTP host, API mailer or PHP mail()).
 *
 * @return array{0:bool,1:string,2:string}
 */
function mmbn_send( object $w, array $mail ): array {
	$via   = '';
	$error = '';
	$probe = static function ( $pm ) use ( &$via ) {
		$type = strtolower( (string) $pm->Mailer );
		if ( 'smtp' === $type ) {
			$via = 'SMTP ' . $pm->Host;
		} elseif ( 'mail' === $type ) {
			$via = 'PHP mail()';
			// Match the envelope sender to the From address so SPF lines up.
			if ( ! $pm->Sender && is_email( $pm->From ) ) {
				$pm->Sender = $pm->From;
			}
		} else {
			$via = $type;
		}
		if ( $pm->From ) {
			$via .= ' · from ' . $pm->From;
		}
	};
	$catch = static function ( $e ) use ( &$error ) {
		$error = is_wp_error( $e ) ? $e->get_error_message() : 'Unknown error';
	};
	add_action( 'phpmailer_init', $probe, PHP_INT_MAX );
	add_action( 'wp_mail_failed', $catch );
	if ( function_exists( 'mmm_mail' ) ) {
		[ $ok, $err ] = mmm_mail( (string) $w->email, $mail['subject'], $mail['html'], $mail['text'], (int) $w->sub_id, 0 );
		$error        = $error ? $error : (string) $err;
	} else {
		$headers = array( 'Content-Type: text/html; charset=UTF-8', 'List-Unsubscribe: <' . mmbn_stop_url( $w ) . '>', 'List-Unsubscribe-Post: List-Unsubscribe=One-Click' );
		$ok      = (bool) wp_mail( (string) $w->email, $mail['subject'], $mail['html'], $headers );
	}
	remove_action( 'phpmailer_init', $probe, PHP_INT_MAX );
	remove_action( 'wp_mail_failed', $catch );
	if ( '' === $via ) {
		$plugin = mmbn_mail_plugin();
		$via    = $plugin ? sprintf( /* translators: %s: plugin name */ __( 'handed to %s', 'mine-mirth-buy-now' ), $plugin['name'] ) : __( 'handed to another plugin', 'mine-mirth-buy-now' );
	}
	update_option( 'mmbn_last_via', $via, false );
	return array( (bool) $ok, $ok ? '' : ( $error ? $error : __( 'The server did not accept the email. Check your SMTP plugin.', 'mine-mirth-buy-now' ) ), $via );
}

/**
 * The SMTP/email plugin in charge of sending, and settings that silently stop delivery.
 *
 * @return array{name:string,mailer:string,blocked:string}|null
 */
function mmbn_mail_plugin(): ?array {
	if ( defined( 'WPMS_PLUGIN_VER' ) || class_exists( 'WPMailSMTP\\Core' ) ) {
		$o       = (array) get_option( 'wp_mail_smtp', array() );
		$blocked = ( ! empty( $o['general']['do_not_send'] ) || ( defined( 'WPMS_DO_NOT_SEND' ) && WPMS_DO_NOT_SEND ) ) ? __( 'WP Mail SMTP › Settings › Misc › "Do Not Send" is turned on, so no email leaves your site even though WordPress reports it as sent.', 'mine-mirth-buy-now' ) : '';
		return array( 'name' => 'WP Mail SMTP', 'mailer' => (string) ( $o['mail']['mailer'] ?? '' ), 'blocked' => $blocked );
	}
	if ( defined( 'FLUENTMAIL' ) || defined( 'FLUENTMAIL_PLUGIN_FILE' ) ) {
		$o       = (array) get_option( 'fluentmail-settings', array() );
		$blocked = 'yes' === ( $o['misc']['simulate_emails'] ?? '' ) ? __( 'FluentSMTP › Settings › "Email Simulation" is turned on, so emails are only logged, never sent.', 'mine-mirth-buy-now' ) : '';
		return array( 'name' => 'FluentSMTP', 'mailer' => '', 'blocked' => $blocked );
	}
	foreach ( array(
		'Post SMTP'     => defined( 'POST_SMTP_VER' ) || class_exists( 'PostmanWpMail' ),
		'Easy WP SMTP'  => defined( 'EasyWPSMTP_PLUGIN_VERSION' ) || class_exists( 'EasyWPSMTP' ),
		'SMTP Mailer'   => class_exists( 'SMTP_MAILER' ),
		'Brevo'         => class_exists( 'SIB_Manager' ),
		'Gmail SMTP'    => class_exists( 'GMAIL_SMTP' ),
		'Mailgun'       => class_exists( 'Mailgun' ),
		'SendGrid'      => class_exists( 'Sendgrid_Settings' ),
		'Site Mailer'   => defined( 'SITE_MAILER_VERSION' ),
	) as $name => $on ) {
		if ( $on ) {
			return array( 'name' => $name, 'mailer' => '', 'blocked' => '' );
		}
	}
	return null;
}

/**
 * Plain-language checks shown on the settings page.
 *
 * @return array<int,array{0:string,1:string}> [ level (ok|warn|bad), text ]
 */
function mmbn_mail_checks(): array {
	$out    = array();
	$plugin = mmbn_mail_plugin();
	if ( ! $plugin ) {
		$out[] = array( 'bad', __( 'No SMTP plugin found. Your site sends with PHP mail(), which Gmail and Outlook often drop without any error. Install "WP Mail SMTP" or "FluentSMTP", connect your mailbox (for example support@minemirth.com), then send a test alert below.', 'mine-mirth-buy-now' ) );
	} else {
		if ( $plugin['blocked'] ) {
			$out[] = array( 'bad', $plugin['blocked'] );
		}
		if ( 'mail' === $plugin['mailer'] ) {
			$out[] = array( 'bad', __( 'WP Mail SMTP is set to "Default (none)", which still uses PHP mail(). Choose a real mailer (Other SMTP, Gmail, Brevo…) in WP Mail SMTP › Settings.', 'mine-mirth-buy-now' ) );
		}
		if ( ! $plugin['blocked'] && 'mail' !== $plugin['mailer'] ) {
			/* translators: %s: plugin name */
			$out[] = array( 'ok', sprintf( __( 'Emails are sent by %s.', 'mine-mirth-buy-now' ), $plugin['name'] . ( $plugin['mailer'] ? ' (' . $plugin['mailer'] . ')' : '' ) ) );
		}
	}
	$via = (string) get_option( 'mmbn_last_via' );
	if ( $via ) {
		/* translators: %s: transport details */
		$out[] = array( false !== strpos( $via, 'PHP mail()' ) ? 'warn' : 'ok', sprintf( __( 'Last alert left your site via: %s.', 'mine-mirth-buy-now' ), $via ) );
	}
	$out[] = array( 'warn', __( '"Sent" means your mail server accepted the email. If it doesn’t arrive, check Spam, Promotions and All Mail (search for "Price drop"). If the alert is sent from and to the same Gmail account, Gmail may file it only under Sent. Test with a different address.', 'mine-mirth-buy-now' ) );
	return $out;
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
			mmbn_purge( (int) $w->product_id );
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
