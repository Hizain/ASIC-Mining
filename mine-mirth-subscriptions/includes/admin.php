<?php
/**
 * Admin: menu, overview, and the lists of subscriptions, customers, invoices and activity.
 *
 * @package MineMirthSubscriptions
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	static function (): void {
		$cap = mms_cap();
		add_menu_page( __( 'Subscriptions', 'mine-mirth-subscriptions' ), __( 'Subscriptions', 'mine-mirth-subscriptions' ), $cap, 'mms', 'mms_overview_page', 'dashicons-update-alt', 57 );
		add_submenu_page( 'mms', __( 'Overview', 'mine-mirth-subscriptions' ), __( 'Overview', 'mine-mirth-subscriptions' ), $cap, 'mms', 'mms_overview_page' );
		add_submenu_page( 'mms', __( 'Subscriptions', 'mine-mirth-subscriptions' ), __( 'Subscriptions', 'mine-mirth-subscriptions' ), $cap, 'mms-subs', 'mms_subs_page' );
		add_submenu_page( 'mms', __( 'Customers', 'mine-mirth-subscriptions' ), __( 'Customers', 'mine-mirth-subscriptions' ), $cap, 'mms-customers', 'mms_customers_page' );
		add_submenu_page( 'mms', __( 'Invoices', 'mine-mirth-subscriptions' ), __( 'Invoices', 'mine-mirth-subscriptions' ), $cap, 'mms-invoices', 'mms_invoices_page' );
		add_submenu_page( 'mms', __( 'Plans', 'mine-mirth-subscriptions' ), __( 'Plans', 'mine-mirth-subscriptions' ), $cap, 'edit.php?post_type=' . MMS_PLAN_TYPE );
		add_submenu_page( 'mms', __( 'Activity', 'mine-mirth-subscriptions' ), __( 'Activity', 'mine-mirth-subscriptions' ), $cap, 'mms-activity', 'mms_activity_page' );
		add_submenu_page( 'mms', __( 'Settings', 'mine-mirth-subscriptions' ), __( 'Settings', 'mine-mirth-subscriptions' ), $cap, 'mms-settings', 'mms_settings_page' );
		// Detail screens (no menu entry).
		add_submenu_page( 'mms', __( 'Subscription', 'mine-mirth-subscriptions' ), __( 'Subscription', 'mine-mirth-subscriptions' ), $cap, 'mms-sub', 'mms_sub_page' );
		add_submenu_page( 'mms', __( 'Customer', 'mine-mirth-subscriptions' ), __( 'Customer', 'mine-mirth-subscriptions' ), $cap, 'mms-customer', 'mms_customer_page' );
	}
);
add_action(
	'admin_head',
	static function (): void {
		remove_submenu_page( 'mms', 'mms-sub' );
		remove_submenu_page( 'mms', 'mms-customer' );
	}
);

add_filter(
	'parent_file',
	static function ( $file ) {
		$page = sanitize_key( wp_unslash( $_GET['page'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( in_array( $page, array( 'mms-sub', 'mms-customer' ), true ) ) {
			return 'mms';
		}
		return $file;
	}
);
add_filter(
	'submenu_file',
	static function ( $file ) {
		$page = sanitize_key( wp_unslash( $_GET['page'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		return array( 'mms-sub' => 'mms-subs', 'mms-customer' => 'mms-customers' )[ $page ] ?? $file;
	}
);

add_action(
	'admin_enqueue_scripts',
	static function ( string $hook ): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( str_contains( $hook, 'mms' ) || ( $screen && MMS_PLAN_TYPE === $screen->post_type ) ) {
			wp_enqueue_style( 'mms-admin', MMS_URI . 'assets/admin.css', array(), MMS_VERSION );
			wp_enqueue_script( 'mms-admin', MMS_URI . 'assets/admin.js', array(), MMS_VERSION, true );
		}
	}
);

/**
 * Notice on every plugin screen when Stripe is not connected yet.
 */
function mms_connect_notice(): void {
	if ( mms_connected() ) {
		return;
	}
	echo '<div class="notice notice-warning mms-connect-notice"><p><strong>' . esc_html__( 'Stripe is not connected yet.', 'mine-mirth-subscriptions' ) . '</strong> ' . esc_html__( 'Paste your Stripe keys and click "Save and connect": it takes about two minutes.', 'mine-mirth-subscriptions' ) . ' <a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=mms-settings' ) ) . '">' . esc_html__( 'Connect Stripe', 'mine-mirth-subscriptions' ) . '</a></p></div>';
}

function mms_admin_notice_from_query(): void {
	$msg  = sanitize_key( wp_unslash( $_GET['msg'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$text = (string) get_transient( 'mms_flash_' . get_current_user_id() );
	if ( '' === $msg ) {
		return;
	}
	delete_transient( 'mms_flash_' . get_current_user_id() );
	$ok = 'ok' === $msg;
	echo '<div class="notice ' . ( $ok ? 'notice-success' : 'notice-error' ) . ' is-dismissible"><p>' . esc_html( '' !== $text ? $text : ( $ok ? __( 'Done.', 'mine-mirth-subscriptions' ) : __( 'Something went wrong.', 'mine-mirth-subscriptions' ) ) ) . '</p></div>';
}

/**
 * Redirect back with a message.
 */
function mms_back( bool $ok, string $text, string $url = '' ): void {
	set_transient( 'mms_flash_' . get_current_user_id(), $text, 120 );
	$url = '' !== $url ? $url : (string) wp_get_referer();
	$url = '' !== $url ? remove_query_arg( 'msg', $url ) : admin_url( 'admin.php?page=mms' );
	wp_safe_redirect( add_query_arg( 'msg', $ok ? 'ok' : 'error', $url ) );
	exit;
}

function mms_admin_header( string $title, string $actions = '' ): void {
	$mode = mms_mode();
	echo '<div class="mms-top"><h1 class="mms-title">' . esc_html( $title ) . '</h1>';
	echo '<span class="mms-mode mms-mode--' . esc_attr( $mode ) . '">' . esc_html( 'live' === $mode ? __( 'Live mode', 'mine-mirth-subscriptions' ) : __( 'Test mode', 'mine-mirth-subscriptions' ) ) . '</span>';
	echo '<span class="mms-top__actions">' . $actions . '</span></div><hr class="wp-header-end">'; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts.
	mms_connect_notice();
	mms_admin_notice_from_query();
}

/**
 * Monthly value of a subscription row (for MRR).
 */
function mms_monthly( array $s ): float {
	$per  = mms_from_minor( (int) $s['amount'] * (int) $s['quantity'], (string) $s['currency'] );
	$mult = array( 'day' => 365 / 12, 'week' => 52 / 12, 'month' => 1, 'year' => 1 / 12 )[ $s['interval_unit'] ] ?? 1;
	return $per * $mult / max( 1, (int) $s['interval_count'] );
}

/* ------------------------------------------------------------------ Overview */

function mms_overview_page(): void {
	global $wpdb;
	if ( ! current_user_can( mms_cap() ) ) {
		return;
	}
	$mode   = mms_mode();
	$subs   = mms_table( 'subscriptions' );
	$active = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$subs} WHERE mode = %s AND status IN ('active','trialing','past_due')", $mode ), ARRAY_A ); // phpcs:ignore WordPress.DB
	$mrr    = 0.0;
	$past   = 0;
	foreach ( (array) $active as $s ) {
		if ( 'past_due' === $s['status'] ) {
			++$past;
		}
		if ( 'trialing' !== $s['status'] ) {
			$mrr += mms_monthly( $s );
		}
	}
	$cur      = (string) mms_setting( 'currency' );
	$paid30   = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT SUM(amount_paid - amount_refunded) FROM ' . mms_table( 'invoices' ) . " WHERE mode = %s AND status = 'paid' AND created >= %d", $mode, time() - 30 * DAY_IN_SECONDS ) ); // phpcs:ignore WordPress.DB
	$clients  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(DISTINCT client_no) FROM ' . mms_table( 'customers' ) . ' WHERE mode = %s', $mode ) ); // phpcs:ignore WordPress.DB
	$renewals = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$subs} WHERE mode = %s AND status IN ('active','trialing') AND cancel_at_period_end = 0 AND period_end BETWEEN %d AND %d ORDER BY period_end ASC LIMIT 8", $mode, time(), time() + 7 * DAY_IN_SECONDS ), ARRAY_A ); // phpcs:ignore WordPress.DB
	$recent   = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . mms_table( 'invoices' ) . ' WHERE mode = %s ORDER BY created DESC LIMIT 8', $mode ), ARRAY_A ); // phpcs:ignore WordPress.DB
	$hook     = (int) get_option( 'mms_last_webhook', 0 );
	$sync     = (int) get_option( 'mms_last_sync', 0 );
	?>
	<div class="wrap mms-admin">
		<?php mms_admin_header( __( 'Subscriptions', 'mine-mirth-subscriptions' ), mms_sync_button() ); ?>
		<div class="mms-stats">
			<div><small><?php esc_html_e( 'Active subscriptions', 'mine-mirth-subscriptions' ); ?></small><strong><?php echo (int) count( (array) $active ); ?></strong><span><?php echo esc_html( sprintf( /* translators: %d: count */ _n( '%d client in total', '%d clients in total', $clients, 'mine-mirth-subscriptions' ), $clients ) ); ?></span></div>
			<div><small><?php esc_html_e( 'Monthly recurring revenue', 'mine-mirth-subscriptions' ); ?></small><strong><?php echo esc_html( mms_money( mms_to_minor( $mrr, $cur ), $cur ) ); ?></strong><span><?php esc_html_e( 'Trials not counted', 'mine-mirth-subscriptions' ); ?></span></div>
			<div><small><?php esc_html_e( 'Collected, last 30 days', 'mine-mirth-subscriptions' ); ?></small><strong><?php echo esc_html( mms_money( $paid30, $cur ) ); ?></strong><span><?php esc_html_e( 'After refunds', 'mine-mirth-subscriptions' ); ?></span></div>
			<div class="<?php echo $past ? 'is-warn' : ''; ?>"><small><?php esc_html_e( 'Past due', 'mine-mirth-subscriptions' ); ?></small><strong><?php echo (int) $past; ?></strong><span><?php esc_html_e( 'Failed payments Stripe is retrying', 'mine-mirth-subscriptions' ); ?></span></div>
		</div>
		<div class="mms-grid">
			<section class="mms-card">
				<h2><?php esc_html_e( 'Renewing in the next 7 days', 'mine-mirth-subscriptions' ); ?></h2>
				<?php if ( $renewals ) : ?>
					<ul class="mms-mini">
						<?php foreach ( $renewals as $s ) : ?>
							<?php $c = mms_get( 'customers', 'id', (int) $s['customer_id'] ); ?>
							<li><a href="<?php echo esc_url( mms_sub_url( (int) $s['id'] ) ); ?>"><?php echo mms_plan_thumb( (int) $s['plan_id'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><strong><?php echo esc_html( $c ? ( $c['name'] ?: $c['email'] ) : '' ); ?></strong><small><?php echo esc_html( ( $c ? $c['client_no'] . ' · ' : '' ) . $s['plan_name'] ); ?></small></span><b><?php echo esc_html( mms_money( (int) $s['amount'] * (int) $s['quantity'], $s['currency'] ) ); ?><small><?php echo esc_html( mms_date( (int) $s['period_end'] ) ); ?></small></b></a></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="mms-muted"><?php esc_html_e( 'No renewals this week.', 'mine-mirth-subscriptions' ); ?></p>
				<?php endif; ?>
			</section>
			<section class="mms-card">
				<h2><?php esc_html_e( 'Latest invoices', 'mine-mirth-subscriptions' ); ?></h2>
				<?php if ( $recent ) : ?>
					<ul class="mms-mini">
						<?php foreach ( $recent as $i ) : ?>
							<?php $c = mms_get( 'customers', 'id', (int) $i['customer_id'] ); ?>
							<li><a href="<?php echo esc_url( mms_pdf_url( (int) $i['id'] ) ); ?>" target="_blank" rel="noopener"><span class="mms-doc">PDF</span><span><strong><?php echo esc_html( $c ? ( $c['name'] ?: $c['email'] ) : $i['stripe_customer'] ); ?></strong><small><?php echo esc_html( ( $i['number'] ?: $i['stripe_id'] ) . ' · ' . mms_date( (int) $i['created'] ) ); ?></small></span><b><?php echo esc_html( mms_money( (int) $i['total'], $i['currency'] ) ); ?><small><?php echo mms_pill( mms_invoice_status( $i ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></small></b></a></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="mms-muted"><?php esc_html_e( 'Invoices appear here as soon as customers subscribe.', 'mine-mirth-subscriptions' ); ?></p>
				<?php endif; ?>
			</section>
			<section class="mms-card mms-card--status">
				<h2><?php esc_html_e( 'Stripe connection', 'mine-mirth-subscriptions' ); ?></h2>
				<dl class="mms-dl">
					<div><dt><?php esc_html_e( 'Account', 'mine-mirth-subscriptions' ); ?></dt><dd><?php echo mms_connected() ? esc_html( (string) mms_setting( $mode . '_account' ) ?: __( 'Connected', 'mine-mirth-subscriptions' ) ) : '<span class="mms-err">' . esc_html__( 'Not connected', 'mine-mirth-subscriptions' ) . '</span>'; ?></dd></div>
					<div><dt><?php esc_html_e( 'Webhook', 'mine-mirth-subscriptions' ); ?></dt><dd><?php echo mms_webhook_secret() ? '<span class="mms-ok">' . esc_html__( 'Set up', 'mine-mirth-subscriptions' ) . '</span>' : '<span class="mms-err">' . esc_html__( 'Missing', 'mine-mirth-subscriptions' ) . '</span>'; ?></dd></div>
					<div><dt><?php esc_html_e( 'Last event from Stripe', 'mine-mirth-subscriptions' ); ?></dt><dd><?php echo esc_html( $hook ? human_time_diff( $hook ) . ' ' . __( 'ago', 'mine-mirth-subscriptions' ) : __( 'None yet', 'mine-mirth-subscriptions' ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Last full sync', 'mine-mirth-subscriptions' ); ?></dt><dd><?php echo esc_html( $sync ? human_time_diff( $sync ) . ' ' . __( 'ago', 'mine-mirth-subscriptions' ) : __( 'Never', 'mine-mirth-subscriptions' ) ); ?></dd></div>
				</dl>
				<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mms-settings' ) ); ?>"><?php esc_html_e( 'Connection settings', 'mine-mirth-subscriptions' ); ?></a></p>
			</section>
		</div>
	</div>
	<?php
}

function mms_sync_button(): string {
	return '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mms_sync' ), 'mms_sync' ) ) . '">' . esc_html__( 'Sync with Stripe', 'mine-mirth-subscriptions' ) . '</a>';
}

add_action(
	'admin_post_mms_sync',
	static function (): void {
		if ( ! current_user_can( mms_cap() ) || ! check_admin_referer( 'mms_sync' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-subscriptions' ), 403 );
		}
		$r = mms_full_sync();
		if ( is_wp_error( $r ) ) {
			mms_back( false, $r->get_error_message() );
		}
		/* translators: 1: subscriptions, 2: invoices */
		mms_back( true, sprintf( __( 'Synced %1$d subscriptions and %2$d invoices from Stripe.', 'mine-mirth-subscriptions' ), $r['subscriptions'], $r['invoices'] ) );
	}
);

/* ------------------------------------------------------------------ Helpers for lists */

function mms_sub_url( int $id ): string {
	return admin_url( 'admin.php?page=mms-sub&id=' . $id );
}

function mms_customer_url( int $id ): string {
	return admin_url( 'admin.php?page=mms-customer&id=' . $id );
}

function mms_pdf_url( int $inv_id, bool $download = false ): string {
	return add_query_arg( array( 'action' => 'mms_pdf', 'id' => $inv_id, 'dl' => $download ? 1 : null, '_wpnonce' => wp_create_nonce( 'mms_pdf' ) ), admin_url( 'admin-post.php' ) );
}

function mms_plan_thumb( int $plan_id, string $class = '' ): string {
	$url = mms_plan_image( $plan_id, 'thumbnail' );
	return $url ? '<img class="mms-thumb ' . esc_attr( $class ) . '" src="' . esc_url( $url ) . '" alt="">' : '<span class="mms-thumb mms-thumb--empty ' . esc_attr( $class ) . '"></span>';
}

/**
 * Invoice status including refunds.
 */
function mms_invoice_status( array $i ): string {
	if ( 'paid' === $i['status'] && (int) $i['amount_refunded'] > 0 ) {
		return (int) $i['amount_refunded'] >= (int) $i['amount_paid'] ? 'refunded' : 'partially_refunded';
	}
	return (string) $i['status'];
}

add_action(
	'admin_post_mms_pdf',
	static function (): void {
		if ( ! current_user_can( mms_cap() ) || ! check_admin_referer( 'mms_pdf' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-subscriptions' ), 403 );
		}
		$row = mms_get( 'invoices', 'id', absint( $_GET['id'] ?? 0 ) );
		if ( ! $row ) {
			wp_die( esc_html__( 'Invoice not found.', 'mine-mirth-subscriptions' ), 404 );
		}
		mms_send_pdf( $row, empty( $_GET['dl'] ) );
	}
);

/**
 * Search box and status filter for list screens.
 */
function mms_list_filters( string $page, array $statuses = array() ): array {
	$q      = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$status = sanitize_key( wp_unslash( $_GET['status'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	echo '<form class="mms-filters" method="get"><input type="hidden" name="page" value="' . esc_attr( $page ) . '">';
	if ( $statuses ) {
		echo '<select name="status"><option value="">' . esc_html__( 'All statuses', 'mine-mirth-subscriptions' ) . '</option>';
		foreach ( $statuses as $k ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $status, $k, false ) . '>' . esc_html( mms_statuses()[ $k ][0] ?? $k ) . '</option>';
		}
		echo '</select>';
	}
	echo '<input type="search" name="s" value="' . esc_attr( $q ) . '" placeholder="' . esc_attr__( 'Client number, name or email', 'mine-mirth-subscriptions' ) . '"><button class="button">' . esc_html__( 'Filter', 'mine-mirth-subscriptions' ) . '</button></form>';
	return array( $q, $status );
}

function mms_pager( int $total, int $per, int $paged ): void {
	$pages = (int) ceil( $total / $per );
	if ( $pages < 2 ) {
		return;
	}
	echo '<div class="mms-pager">';
	for ( $p = 1; $p <= $pages; $p++ ) {
		echo $p === $paged ? '<span class="is-on">' . (int) $p . '</span>' : '<a href="' . esc_url( add_query_arg( 'paged', $p ) ) . '">' . (int) $p . '</a>';
	}
	echo '</div>';
}

/* ------------------------------------------------------------------ Subscriptions list */

function mms_subs_page(): void {
	global $wpdb;
	if ( ! current_user_can( mms_cap() ) ) {
		return;
	}
	$per   = 25;
	$paged = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="wrap mms-admin">
		<?php mms_admin_header( __( 'Subscriptions', 'mine-mirth-subscriptions' ), '<button type="button" class="button button-primary" data-mms-open="mms-link-box">' . esc_html__( 'Send a subscription link', 'mine-mirth-subscriptions' ) . '</button> ' . mms_sync_button() ); ?>
		<?php mms_link_box(); ?>
		<?php list( $q, $status ) = mms_list_filters( 'mms-subs', array( 'active', 'trialing', 'past_due', 'unpaid', 'paused', 'canceled', 'incomplete' ) ); ?>
		<?php
		$s     = mms_table( 'subscriptions' );
		$c     = mms_table( 'customers' );
		$where = $wpdb->prepare( 's.mode = %s', mms_mode() );
		if ( '' !== $status ) {
			$where .= $wpdb->prepare( ' AND s.status = %s', $status );
		}
		if ( '' !== $q ) {
			$like   = '%' . $wpdb->esc_like( $q ) . '%';
			$where .= $wpdb->prepare( ' AND ( c.client_no LIKE %s OR c.email LIKE %s OR c.name LIKE %s OR s.plan_name LIKE %s OR s.stripe_id = %s )', $like, $like, $like, $like, $q );
		}
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$s} s LEFT JOIN {$c} c ON c.id = s.customer_id WHERE {$where}" ); // phpcs:ignore WordPress.DB
		$rows  = $wpdb->get_results( "SELECT s.*, c.client_no, c.name AS cname, c.email AS cemail FROM {$s} s LEFT JOIN {$c} c ON c.id = s.customer_id WHERE {$where} ORDER BY FIELD(s.status,'past_due','unpaid','active','trialing','paused','incomplete','canceled'), s.period_end ASC LIMIT " . (int) $per . ' OFFSET ' . (int) ( ( $paged - 1 ) * $per ), ARRAY_A ); // phpcs:ignore WordPress.DB
		?>
		<table class="widefat mms-table">
			<thead><tr><th><?php esc_html_e( 'Plan', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'Client', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'Status', 'mine-mirth-subscriptions' ); ?></th><th class="num"><?php esc_html_e( 'Amount', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'Next billing', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'Since', 'mine-mirth-subscriptions' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="6" class="mms-empty-row"><?php echo mms_connected() ? esc_html__( 'No subscriptions yet. When customers subscribe from your hosting page they appear here.', 'mine-mirth-subscriptions' ) : esc_html__( 'Connect Stripe to start taking subscriptions.', 'mine-mirth-subscriptions' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( (array) $rows as $r ) : ?>
				<tr>
					<td><a class="mms-plancell" href="<?php echo esc_url( mms_sub_url( (int) $r['id'] ) ); ?>"><?php echo mms_plan_thumb( (int) $r['plan_id'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><strong><?php echo esc_html( $r['plan_name'] ?: __( 'Subscription', 'mine-mirth-subscriptions' ) ); ?></strong><small><?php echo (int) $r['quantity'] > 1 ? esc_html( sprintf( /* translators: %d: quantity */ __( 'Quantity %d', 'mine-mirth-subscriptions' ), (int) $r['quantity'] ) ) : '&nbsp;'; ?></small></span></a></td>
					<td><a href="<?php echo esc_url( mms_customer_url( (int) $r['customer_id'] ) ); ?>"><span class="mms-client"><?php echo esc_html( (string) $r['client_no'] ); ?></span> <?php echo esc_html( $r['cname'] ?: $r['cemail'] ); ?></a><br><small class="mms-muted"><?php echo esc_html( (string) $r['cemail'] ); ?></small></td>
					<td><?php echo mms_pill( (string) $r['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo (int) $r['cancel_at_period_end'] ? '<br><small class="mms-warn">' . esc_html__( 'Ends at period end', 'mine-mirth-subscriptions' ) . '</small>' : ''; ?></td>
					<td class="num"><strong><?php echo esc_html( mms_money( (int) $r['amount'] * (int) $r['quantity'], $r['currency'] ) ); ?></strong><br><small class="mms-muted"><?php echo esc_html( mms_interval_label( $r['interval_unit'], (int) $r['interval_count'] ) ); ?></small></td>
					<td><?php echo 'canceled' === $r['status'] ? '—' : esc_html( mms_date( (int) $r['period_end'] ) ); ?></td>
					<td><?php echo esc_html( mms_date( (int) $r['started'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php mms_pager( $total, $per, $paged ); ?>
	</div>
	<?php
}

/**
 * "Send a subscription link": a Stripe Checkout link for one plan, emailed to a customer.
 */
function mms_link_box(): void {
	$plans = array_filter( mms_plans(), 'mms_plan_ready' );
	?>
	<div class="mms-card mms-linkbox" id="mms-link-box" hidden>
		<h2><?php esc_html_e( 'Send a subscription link', 'mine-mirth-subscriptions' ); ?></h2>
		<p class="mms-muted"><?php esc_html_e( 'For customers you spoke to by phone or WhatsApp: we email them a secure Stripe checkout link for the plan. The link works for 24 hours.', 'mine-mirth-subscriptions' ); ?></p>
		<?php if ( ! $plans ) : ?>
			<p class="mms-warn"><?php esc_html_e( 'No plan is ready yet: give a Hosting Plan a price in its "Stripe subscription" box first.', 'mine-mirth-subscriptions' ); ?></p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mms-inline-form">
				<input type="hidden" name="action" value="mms_send_link">
				<?php wp_nonce_field( 'mms_send_link' ); ?>
				<label class="mms-f"><span><?php esc_html_e( 'Plan', 'mine-mirth-subscriptions' ); ?></span><select name="plan"><?php foreach ( $plans as $p ) : ?><option value="<?php echo (int) $p['id']; ?>"><?php echo esc_html( $p['title'] . ' · ' . mms_money( $p['amount'], $p['currency'] ) . ' ' . mms_interval_label( $p['interval'], $p['interval_count'] ) ); ?></option><?php endforeach; ?></select></label>
				<label class="mms-f mms-f--short"><span><?php esc_html_e( 'Quantity', 'mine-mirth-subscriptions' ); ?></span><input type="number" name="qty" value="1" min="1" max="999" class="small-text"></label>
				<label class="mms-f"><span><?php esc_html_e( 'Customer email', 'mine-mirth-subscriptions' ); ?></span><input type="email" name="email" required placeholder="customer@example.com"></label>
				<button class="button button-primary"><?php esc_html_e( 'Email the link', 'mine-mirth-subscriptions' ); ?></button>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

add_action(
	'admin_post_mms_send_link',
	static function (): void {
		if ( ! current_user_can( mms_cap() ) || ! check_admin_referer( 'mms_send_link' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-subscriptions' ), 403 );
		}
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$plan  = absint( $_POST['plan'] ?? 0 );
		if ( ! is_email( $email ) ) {
			mms_back( false, __( 'Enter a valid email address.', 'mine-mirth-subscriptions' ) );
		}
		global $wpdb;
		$customer = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT stripe_id FROM ' . mms_table( 'customers' ) . ' WHERE email = %s AND mode = %s ORDER BY id DESC LIMIT 1', $email, mms_mode() ) ); // phpcs:ignore WordPress.DB
		$url      = mms_checkout_url( $plan, absint( $_POST['qty'] ?? 1 ), $email, $customer );
		if ( is_wp_error( $url ) ) {
			mms_back( false, $url->get_error_message() );
		}
		$p    = mms_plan( $plan );
		$html = mms_email_html(
			array(
				'eyebrow' => __( 'Your hosting plan', 'mine-mirth-subscriptions' ),
				'title'   => sprintf( /* translators: %s: plan */ __( 'Start your %s subscription', 'mine-mirth-subscriptions' ), $p['title'] ),
				'image'   => $p['image'],
				'intro'   => '<p>' . esc_html__( 'Hello,', 'mine-mirth-subscriptions' ) . '</p><p>' . esc_html__( 'As discussed, here is your secure link to start your hosting subscription. Checkout is handled by Stripe; the link works for 24 hours.', 'mine-mirth-subscriptions' ) . '</p>',
				'rows'    => array(
					array( __( 'Plan', 'mine-mirth-subscriptions' ), esc_html( $p['title'] ) ),
					array( __( 'Price', 'mine-mirth-subscriptions' ), esc_html( mms_money( $p['amount'], $p['currency'] ) . ' ' . mms_interval_label( $p['interval'], $p['interval_count'] ) ) ),
				),
				'button'  => array( __( 'Start my subscription', 'mine-mirth-subscriptions' ), $url ),
			)
		);
		$ok = mms_mail( $email, sprintf( /* translators: %s: plan */ __( 'Your %s subscription link', 'mine-mirth-subscriptions' ), $p['title'] ), $html );
		mms_log( 'link', sprintf( 'Subscription link for "%s" sent to %s.', $p['title'], $email ) );
		/* translators: %s: email */
		mms_back( $ok, $ok ? sprintf( __( 'Link sent to %s.', 'mine-mirth-subscriptions' ), $email ) : __( 'The email could not be sent. Check your site’s email setup.', 'mine-mirth-subscriptions' ) );
	}
);

/* ------------------------------------------------------------------ Customers list */

function mms_customers_page(): void {
	global $wpdb;
	if ( ! current_user_can( mms_cap() ) ) {
		return;
	}
	$per   = 25;
	$paged = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="wrap mms-admin">
		<?php mms_admin_header( __( 'Customers', 'mine-mirth-subscriptions' ), mms_sync_button() ); ?>
		<?php list( $q ) = mms_list_filters( 'mms-customers' ); ?>
		<?php
		$c     = mms_table( 'customers' );
		$where = $wpdb->prepare( 'mode = %s', mms_mode() );
		if ( '' !== $q ) {
			$like   = '%' . $wpdb->esc_like( $q ) . '%';
			$where .= $wpdb->prepare( ' AND ( client_no LIKE %s OR email LIKE %s OR name LIKE %s OR phone LIKE %s )', $like, $like, $like, $like );
		}
		// One row per client number (a returning client can have several Stripe customers).
		$where .= " AND id IN ( SELECT MIN(id) FROM {$c} GROUP BY client_no, mode )";
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$c} WHERE {$where}" ); // phpcs:ignore WordPress.DB
		$rows  = $wpdb->get_results( "SELECT * FROM {$c} WHERE {$where} ORDER BY id DESC LIMIT " . (int) $per . ' OFFSET ' . (int) ( ( $paged - 1 ) * $per ), ARRAY_A ); // phpcs:ignore WordPress.DB
		?>
		<table class="widefat mms-table">
			<thead><tr><th><?php esc_html_e( 'Client #', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'Customer', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'Phone', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'Subscriptions', 'mine-mirth-subscriptions' ); ?></th><th class="num"><?php esc_html_e( 'Paid in total', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'Client since', 'mine-mirth-subscriptions' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="6" class="mms-empty-row"><?php esc_html_e( 'No customers yet. Every new subscriber gets a client number automatically.', 'mine-mirth-subscriptions' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( (array) $rows as $r ) : ?>
				<?php
				$same     = $wpdb->prepare( "SELECT id FROM {$c} WHERE client_no = %s AND mode = %s", $r['client_no'], $r['mode'] ); // phpcs:ignore WordPress.DB
				$n_active = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . mms_table( 'subscriptions' ) . " WHERE customer_id IN ({$same}) AND status IN ('active','trialing','past_due')" ); // phpcs:ignore WordPress.DB
				$n_all    = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . mms_table( 'subscriptions' ) . " WHERE customer_id IN ({$same})" ); // phpcs:ignore WordPress.DB
				$paid     = (int) $wpdb->get_var( 'SELECT SUM(amount_paid - amount_refunded) FROM ' . mms_table( 'invoices' ) . " WHERE customer_id IN ({$same}) AND status = 'paid'" ); // phpcs:ignore WordPress.DB
				?>
				<tr>
					<td><a class="mms-client mms-client--big" href="<?php echo esc_url( mms_customer_url( (int) $r['id'] ) ); ?>"><?php echo esc_html( $r['client_no'] ); ?></a></td>
					<td><a href="<?php echo esc_url( mms_customer_url( (int) $r['id'] ) ); ?>"><strong><?php echo esc_html( $r['name'] ?: '—' ); ?></strong></a><br><small class="mms-muted"><?php echo esc_html( $r['email'] ); ?></small></td>
					<td><?php echo esc_html( $r['phone'] ?: '—' ); ?></td>
					<td><?php echo esc_html( sprintf( /* translators: 1: active, 2: all */ __( '%1$d active of %2$d', 'mine-mirth-subscriptions' ), $n_active, $n_all ) ); ?></td>
					<td class="num"><?php echo esc_html( mms_money( $paid, (string) mms_setting( 'currency' ) ) ); ?></td>
					<td><?php echo esc_html( mms_date( (int) strtotime( $r['created'] . ' UTC' ) ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php mms_pager( $total, $per, $paged ); ?>
	</div>
	<?php
}

/* ------------------------------------------------------------------ Invoices list */

function mms_invoices_page(): void {
	global $wpdb;
	if ( ! current_user_can( mms_cap() ) ) {
		return;
	}
	$per   = 30;
	$paged = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="wrap mms-admin">
		<?php mms_admin_header( __( 'Invoices', 'mine-mirth-subscriptions' ), mms_sync_button() ); ?>
		<?php list( $q, $status ) = mms_list_filters( 'mms-invoices', array( 'paid', 'open', 'void', 'uncollectible', 'draft' ) ); ?>
		<?php
		$i     = mms_table( 'invoices' );
		$c     = mms_table( 'customers' );
		$where = $wpdb->prepare( 'i.mode = %s', mms_mode() );
		if ( '' !== $status ) {
			$where .= $wpdb->prepare( ' AND i.status = %s', $status );
		}
		if ( '' !== $q ) {
			$like   = '%' . $wpdb->esc_like( $q ) . '%';
			$where .= $wpdb->prepare( ' AND ( c.client_no LIKE %s OR c.email LIKE %s OR c.name LIKE %s OR i.number LIKE %s )', $like, $like, $like, $like );
		}
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$i} i LEFT JOIN {$c} c ON c.id = i.customer_id WHERE {$where}" ); // phpcs:ignore WordPress.DB
		$rows  = $wpdb->get_results( "SELECT i.*, c.client_no, c.name AS cname, c.email AS cemail FROM {$i} i LEFT JOIN {$c} c ON c.id = i.customer_id WHERE {$where} ORDER BY i.created DESC LIMIT " . (int) $per . ' OFFSET ' . (int) ( ( $paged - 1 ) * $per ), ARRAY_A ); // phpcs:ignore WordPress.DB
		?>
		<table class="widefat mms-table">
			<thead><tr><th><?php esc_html_e( 'Invoice', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'Client', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'Date', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'Status', 'mine-mirth-subscriptions' ); ?></th><th class="num"><?php esc_html_e( 'Total', 'mine-mirth-subscriptions' ); ?></th><th class="num"><?php esc_html_e( 'Refunded', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'PDF', 'mine-mirth-subscriptions' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="7" class="mms-empty-row"><?php esc_html_e( 'No invoices yet.', 'mine-mirth-subscriptions' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( (array) $rows as $r ) : ?>
				<?php $sub = '' !== $r['subscription'] ? mms_get( 'subscriptions', 'stripe_id', $r['subscription'] ) : null; ?>
				<tr>
					<td><strong class="mms-mono"><?php echo esc_html( $r['number'] ?: $r['stripe_id'] ); ?></strong><br><small class="mms-muted"><?php echo esc_html( $sub ? $sub['plan_name'] : ucfirst( str_replace( '_', ' ', $r['billing_reason'] ) ) ); ?></small></td>
					<td><?php if ( $r['customer_id'] ) : ?><a href="<?php echo esc_url( mms_customer_url( (int) $r['customer_id'] ) ); ?>"><span class="mms-client"><?php echo esc_html( (string) $r['client_no'] ); ?></span> <?php echo esc_html( $r['cname'] ?: $r['cemail'] ); ?></a><?php endif; ?></td>
					<td><?php echo esc_html( mms_date( (int) $r['created'] ) ); ?></td>
					<td><?php echo mms_pill( mms_invoice_status( $r ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
					<td class="num"><strong><?php echo esc_html( mms_money( (int) $r['total'], $r['currency'] ) ); ?></strong></td>
					<td class="num"><?php echo (int) $r['amount_refunded'] ? esc_html( mms_money( (int) $r['amount_refunded'], $r['currency'] ) ) : '—'; ?></td>
					<td class="mms-nowrap"><a class="button button-small" href="<?php echo esc_url( mms_pdf_url( (int) $r['id'] ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'mine-mirth-subscriptions' ); ?></a> <a class="button button-small" href="<?php echo esc_url( mms_pdf_url( (int) $r['id'], true ) ); ?>"><?php esc_html_e( 'Download', 'mine-mirth-subscriptions' ); ?></a><?php echo $sub ? ' <a class="button button-small" href="' . esc_url( mms_sub_url( (int) $sub['id'] ) . '#mms-refund' ) . '">' . esc_html__( 'Refund…', 'mine-mirth-subscriptions' ) . '</a>' : ''; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php mms_pager( $total, $per, $paged ); ?>
	</div>
	<?php
}

/* ------------------------------------------------------------------ Activity */

function mms_activity_page(): void {
	global $wpdb;
	if ( ! current_user_can( mms_cap() ) ) {
		return;
	}
	$rows = $wpdb->get_results( 'SELECT * FROM ' . mms_table( 'log' ) . ' ORDER BY id DESC LIMIT 200', ARRAY_A ); // phpcs:ignore WordPress.DB
	?>
	<div class="wrap mms-admin">
		<?php mms_admin_header( __( 'Activity', 'mine-mirth-subscriptions' ) ); ?>
		<table class="widefat mms-table">
			<thead><tr><th><?php esc_html_e( 'When', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'What', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'Details', 'mine-mirth-subscriptions' ); ?></th><th><?php esc_html_e( 'By', 'mine-mirth-subscriptions' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( (array) $rows as $r ) : ?>
				<?php $u = (int) $r['user_id'] ? get_userdata( (int) $r['user_id'] ) : null; ?>
				<tr class="mms-log--<?php echo esc_attr( $r['level'] ); ?>">
					<td class="mms-nowrap"><?php echo esc_html( mms_date( (int) strtotime( $r['time'] . ' UTC' ), true ) ); ?></td>
					<td><span class="mms-tag"><?php echo esc_html( $r['type'] ); ?></span></td>
					<td><?php echo esc_html( (string) $r['message'] ); ?></td>
					<td><?php echo esc_html( $u ? $u->display_name : __( 'Stripe / system', 'mine-mirth-subscriptions' ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
