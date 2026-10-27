<?php
/**
 * Admin: menu, the list of purchase orders, PDF download and row actions, product and customer search.
 *
 * @package MineMirthPurchaseOrders
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	static function (): void {
		$cap = mmpo_cap();
		add_menu_page( __( 'Purchase orders', 'mine-mirth-purchase-orders' ), __( 'Purchase orders', 'mine-mirth-purchase-orders' ), $cap, 'mmpo', 'mmpo_list_page', 'dashicons-media-spreadsheet', 56 );
		add_submenu_page( 'mmpo', __( 'Purchase orders', 'mine-mirth-purchase-orders' ), __( 'All purchase orders', 'mine-mirth-purchase-orders' ), $cap, 'mmpo', 'mmpo_list_page' );
		add_submenu_page( 'mmpo', __( 'New purchase order', 'mine-mirth-purchase-orders' ), __( 'Add new', 'mine-mirth-purchase-orders' ), $cap, 'mmpo-edit', 'mmpo_edit_page' );
		add_submenu_page( 'mmpo', __( 'Purchase order settings', 'mine-mirth-purchase-orders' ), __( 'Settings', 'mine-mirth-purchase-orders' ), $cap, 'mmpo-settings', 'mmpo_settings_page' );
	}
);

/**
 * Highlight "Add new" only for new purchase orders; editing one keeps "All purchase orders" active.
 */
add_filter(
	'submenu_file',
	static function ( $file ) {
		if ( isset( $_GET['page'] ) && 'mmpo-edit' === $_GET['page'] && ! empty( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return 'mmpo';
		}
		return $file;
	}
);

add_action(
	'admin_enqueue_scripts',
	static function ( string $hook ): void {
		if ( ! str_contains( $hook, 'mmpo' ) ) {
			return;
		}
		wp_enqueue_style( 'mmpo-admin', MMPO_URI . 'assets/admin.css', array(), MMPO_VERSION );
		if ( str_contains( $hook, 'mmpo-settings' ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'mmpo-settings', MMPO_URI . 'assets/settings.js', array(), MMPO_VERSION, true );
		}
		if ( str_contains( $hook, 'mmpo-edit' ) ) {
			wp_enqueue_script( 'mmpo-editor', MMPO_URI . 'assets/editor.js', array(), MMPO_VERSION, true );
		}
	}
);

/**
 * Link to a purchase order's PDF.
 */
/**
 * Links are returned unescaped (plain &), so they also work in JavaScript; escape them when printing.
 */
function mmpo_pdf_url( int $id, bool $download = false ): string {
	$args = array( 'action' => 'mmpo_pdf', 'id' => $id );
	if ( $download ) {
		$args['dl'] = 1;
	}
	$args['_wpnonce'] = wp_create_nonce( 'mmpo_pdf_' . $id );
	return add_query_arg( $args, admin_url( 'admin-post.php' ) );
}

function mmpo_edit_url( int $id = 0 ): string {
	return admin_url( 'admin.php?page=mmpo-edit' . ( $id ? '&id=' . $id : '' ) );
}

function mmpo_action_url( string $action, int $id ): string {
	return add_query_arg(
		array(
			'action'   => 'mmpo_' . $action,
			'id'       => $id,
			'_wpnonce' => wp_create_nonce( 'mmpo_' . $action . '_' . $id ),
		),
		admin_url( 'admin-post.php' )
	);
}

/**
 * Status pill (HTML).
 */
function mmpo_status_pill( string $key ): string {
	$st = mmpo_status( $key );
	return '<span class="mmpo-pill" style="--c:' . esc_attr( $st['color'] ) . '"><i></i>' . esc_html( $st['label'] ) . '</span>';
}

/* ------------------------------------------------------------------ PDF and row actions */

add_action(
	'admin_post_mmpo_pdf',
	static function (): void {
		$id = absint( $_GET['id'] ?? 0 );
		if ( ! current_user_can( mmpo_cap() ) || ! check_admin_referer( 'mmpo_pdf_' . $id ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-purchase-orders' ), 403 );
		}
		$d = mmpo_get( $id );
		if ( ! $d ) {
			wp_die( esc_html__( 'That purchase order no longer exists.', 'mine-mirth-purchase-orders' ), 404 );
		}
		if ( '' !== mmpo_pdf_problem() ) {
			wp_die( esc_html( mmpo_pdf_problem() ) );
		}
		$pdf  = mmpo_pdf( $d );
		$name = mmpo_pdf_filename( $d );
		while ( ob_get_level() ) {
			ob_end_clean();
		}
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: ' . ( empty( $_GET['dl'] ) ? 'inline' : 'attachment' ) . '; filename="' . $name . '"' );
		header( 'Content-Length: ' . strlen( $pdf ) );
		header( 'X-Content-Type-Options: nosniff' );
		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput -- binary PDF.
		exit;
	}
);

foreach ( array( 'duplicate', 'trash', 'restore', 'delete' ) as $mmpo_action ) {
	add_action(
		'admin_post_mmpo_' . $mmpo_action,
		static function () use ( $mmpo_action ): void {
			$id = absint( $_GET['id'] ?? 0 );
			if ( ! current_user_can( mmpo_cap() ) || ! check_admin_referer( 'mmpo_' . $mmpo_action . '_' . $id ) || ! mmpo_get( $id ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-purchase-orders' ), 403 );
			}
			switch ( $mmpo_action ) {
				case 'duplicate':
					$new = mmpo_duplicate( $id );
					wp_safe_redirect( add_query_arg( 'msg', 'copied', mmpo_edit_url( $new ) ) );
					exit;
				case 'trash':
					wp_trash_post( $id );
					$msg = 'trashed';
					break;
				case 'restore':
					wp_untrash_post( $id );
					wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
					$msg = 'restored';
					break;
				default:
					wp_delete_post( $id, true );
					$msg = 'deleted';
			}
			wp_safe_redirect( add_query_arg( array( 'page' => 'mmpo', 'msg' => $msg ), admin_url( 'admin.php' ) ) );
			exit;
		}
	);
}

/* ------------------------------------------------------------------ The list */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Purchase orders table.
 */
class MMPO_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'purchase-order',
				'plural'   => 'purchase-orders',
				'ajax'     => false,
			)
		);
	}

	private function view(): string {
		return sanitize_key( wp_unslash( $_REQUEST['status'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	public function get_columns(): array {
		return array(
			'cb'      => '<input type="checkbox">',
			'number'  => __( 'Number', 'mine-mirth-purchase-orders' ),
			'ship'    => __( 'Ship to', 'mine-mirth-purchase-orders' ),
			'items'   => __( 'Products', 'mine-mirth-purchase-orders' ),
			'date'    => __( 'Date', 'mine-mirth-purchase-orders' ),
			'status'  => __( 'Status', 'mine-mirth-purchase-orders' ),
			'total'   => __( 'Total', 'mine-mirth-purchase-orders' ),
			'balance' => __( 'Balance due', 'mine-mirth-purchase-orders' ),
		);
	}

	protected function get_sortable_columns(): array {
		return array(
			'number' => array( 'number', false ),
			'date'   => array( 'date', true ),
			'total'  => array( 'total', false ),
		);
	}

	protected function get_views(): array {
		$base   = admin_url( 'admin.php?page=mmpo' );
		$counts = mmpo_status_counts();
		$view   = $this->view();
		$all    = array_sum( $counts );
		$views  = array(
			'all' => sprintf( '<a href="%s"%s>%s <span class="count">(%d)</span></a>', esc_url( $base ), '' === $view ? ' class="current" aria-current="page"' : '', esc_html__( 'All', 'mine-mirth-purchase-orders' ), $all ),
		);
		foreach ( mmpo_statuses() as $key => $st ) {
			if ( empty( $counts[ $key ] ) ) {
				continue;
			}
			$views[ $key ] = sprintf( '<a href="%s"%s>%s <span class="count">(%d)</span></a>', esc_url( add_query_arg( 'status', $key, $base ) ), $view === $key ? ' class="current" aria-current="page"' : '', esc_html( $st['label'] ), (int) $counts[ $key ] );
		}
		$trash = (int) wp_count_posts( 'mmpo_order' )->trash;
		if ( $trash ) {
			$views['trash'] = sprintf( '<a href="%s"%s>%s <span class="count">(%d)</span></a>', esc_url( add_query_arg( 'status', 'trash', $base ) ), 'trash' === $view ? ' class="current" aria-current="page"' : '', esc_html__( 'Trash', 'mine-mirth-purchase-orders' ), $trash );
		}
		return $views;
	}

	protected function get_bulk_actions(): array {
		if ( 'trash' === $this->view() ) {
			return array(
				'restore' => __( 'Restore', 'mine-mirth-purchase-orders' ),
				'delete'  => __( 'Delete permanently', 'mine-mirth-purchase-orders' ),
			);
		}
		$actions = array();
		foreach ( mmpo_statuses() as $key => $st ) {
			/* translators: %s: status */
			$actions[ 'status-' . $key ] = sprintf( __( 'Change status to %s', 'mine-mirth-purchase-orders' ), $st['label'] );
		}
		$actions['trash'] = __( 'Move to Trash', 'mine-mirth-purchase-orders' );
		return $actions;
	}

	public function prepare_items(): void {
		$per   = 20;
		$paged = max( 1, $this->get_pagenum() );
		$view  = $this->view();
		$args  = array(
			'post_type'      => 'mmpo_order',
			'post_status'    => 'trash' === $view ? 'trash' : 'publish',
			'posts_per_page' => $per,
			'paged'          => $paged,
			'meta_query'     => array(), // phpcs:ignore WordPress.DB.SlowDBQuery
		);
		if ( '' !== $view && 'trash' !== $view ) {
			$args['meta_query'][] = array( 'key' => '_mmpo_status', 'value' => $view );
		}
		$search = trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' !== $search ) {
			$args['meta_query'][] = array( 'key' => '_mmpo_search', 'value' => mb_strtolower( $search ), 'compare' => 'LIKE' );
		}
		$orderby = sanitize_key( wp_unslash( $_REQUEST['orderby'] ?? 'date' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$order   = 'asc' === strtolower( (string) wp_unslash( $_REQUEST['order'] ?? 'desc' ) ) ? 'ASC' : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'total' === $orderby ) {
			$args['meta_key'] = '_mmpo_total'; // phpcs:ignore WordPress.DB.SlowDBQuery
			$args['orderby']  = 'meta_value_num';
		} elseif ( 'number' === $orderby ) {
			$args['meta_key'] = '_mmpo_number'; // phpcs:ignore WordPress.DB.SlowDBQuery
			$args['orderby']  = 'meta_value';
		} else {
			$args['meta_key'] = '_mmpo_date'; // phpcs:ignore WordPress.DB.SlowDBQuery
			$args['orderby']  = array( 'meta_value' => $order, 'ID' => $order );
		}
		$args['order'] = $order;
		$q             = new WP_Query( $args );
		$this->items   = $q->posts;
		$this->set_pagination_args(
			array(
				'total_items' => (int) $q->found_posts,
				'per_page'    => $per,
			)
		);
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'number' );
	}

	protected function column_cb( $item ): string {
		return '<input type="checkbox" name="ids[]" value="' . (int) $item->ID . '">';
	}

	protected function column_number( $item ): string {
		$d    = mmpo_get( (int) $item->ID );
		$id   = (int) $item->ID;
		$out  = '<strong><a class="row-title mmpo-mono" href="' . esc_url( mmpo_edit_url( $id ) ) . '">' . esc_html( $d['number'] ) . '</a></strong>';
		$out .= '<div class="mmpo-sub">' . esc_html( mmpo_doc_types()[ $d['doc'] ] ?? '' ) . ( '' !== $d['reference'] ? ' · ' . esc_html( $d['reference'] ) : '' ) . '</div>';
		if ( 'trash' === $this->view() ) {
			$actions = array(
				'restore' => '<a href="' . esc_url( mmpo_action_url( 'restore', $id ) ) . '">' . esc_html__( 'Restore', 'mine-mirth-purchase-orders' ) . '</a>',
				'delete'  => '<a class="submitdelete" onclick="return confirm(\'' . esc_js( __( 'Delete this purchase order for good?', 'mine-mirth-purchase-orders' ) ) . '\')" href="' . esc_url( mmpo_action_url( 'delete', $id ) ) . '">' . esc_html__( 'Delete permanently', 'mine-mirth-purchase-orders' ) . '</a>',
			);
		} else {
			$actions = array(
				'edit'      => '<a href="' . esc_url( mmpo_edit_url( $id ) ) . '">' . esc_html__( 'Edit', 'mine-mirth-purchase-orders' ) . '</a>',
				'pdf'       => '<a href="' . esc_url( mmpo_pdf_url( $id ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'View PDF', 'mine-mirth-purchase-orders' ) . '</a>',
				'download'  => '<a href="' . esc_url( mmpo_pdf_url( $id, true ) ) . '">' . esc_html__( 'Download', 'mine-mirth-purchase-orders' ) . '</a>',
				'duplicate' => '<a href="' . esc_url( mmpo_action_url( 'duplicate', $id ) ) . '">' . esc_html__( 'Duplicate', 'mine-mirth-purchase-orders' ) . '</a>',
				'trash'     => '<a class="submitdelete" href="' . esc_url( mmpo_action_url( 'trash', $id ) ) . '">' . esc_html__( 'Trash', 'mine-mirth-purchase-orders' ) . '</a>',
			);
		}
		return $out . $this->row_actions( $actions );
	}

	protected function column_ship( $item ): string {
		$d     = mmpo_get( (int) $item->ID );
		$s     = $d['ship'];
		$where = trim( $s['city'] . ( $s['city'] && $s['state'] ? ', ' : '' ) . $s['state'] . ( 'US' !== $s['country'] && $s['country'] ? ' · ' . $s['country'] : '' ) );
		$out   = '<strong>' . esc_html( '' !== $s['name'] ? $s['name'] : '—' ) . '</strong>';
		if ( '' !== $s['company'] ) {
			$out .= '<div class="mmpo-sub">' . esc_html( $s['company'] ) . '</div>';
		}
		if ( '' !== $where ) {
			$out .= '<div class="mmpo-sub">' . esc_html( $where ) . '</div>';
		}
		return $out;
	}

	protected function column_items( $item ): string {
		$d = mmpo_get( (int) $item->ID );
		if ( ! $d['items'] ) {
			return '<span class="mmpo-sub">—</span>';
		}
		$first = $d['items'][0];
		$qty   = array_sum( array_map( static fn( $i ) => (float) $i['qty'], $d['items'] ) );
		$out   = esc_html( mmpo_qty( (float) $first['qty'] ) . ' × ' . $first['name'] );
		if ( count( $d['items'] ) > 1 ) {
			/* translators: %d: number of other products */
			$out .= '<div class="mmpo-sub">' . esc_html( sprintf( _n( 'and %d more line', 'and %d more lines', count( $d['items'] ) - 1, 'mine-mirth-purchase-orders' ), count( $d['items'] ) - 1 ) ) . ' · ' . esc_html( sprintf( /* translators: %s: units */ __( '%s units', 'mine-mirth-purchase-orders' ), mmpo_qty( $qty ) ) ) . '</div>';
		}
		return $out;
	}

	protected function column_date( $item ): string {
		$d = mmpo_get( (int) $item->ID );
		return esc_html( mmpo_date( $d['date'] ) ) . ( '' !== $d['due'] ? '<div class="mmpo-sub">' . esc_html( sprintf( /* translators: %s: date */ __( 'Due %s', 'mine-mirth-purchase-orders' ), mmpo_date( $d['due'] ) ) ) . '</div>' : '' );
	}

	protected function column_status( $item ): string {
		$d = mmpo_get( (int) $item->ID );
		return mmpo_status_pill( $d['status'] ) . '<div class="mmpo-sub">' . esc_html( wp_date( 'M j, Y', (int) $d['status_time'] ) ) . '</div>';
	}

	protected function column_total( $item ): string {
		$d = mmpo_get( (int) $item->ID );
		return '<strong class="mmpo-num">' . esc_html( mmpo_money( mmpo_totals( $d )['total'], $d['currency'] ) ) . '</strong>';
	}

	protected function column_balance( $item ): string {
		$d = mmpo_get( (int) $item->ID );
		$t = mmpo_totals( $d );
		if ( in_array( $d['status'], array( 'cancelled', 'refunded' ), true ) ) {
			return '<span class="mmpo-sub">—</span>';
		}
		if ( $t['paid'] > 0 && $t['balance'] <= 0 ) {
			return '<span class="mmpo-is-paid">' . esc_html__( 'Paid', 'mine-mirth-purchase-orders' ) . '</span>';
		}
		return '<span class="mmpo-num' . ( $t['paid'] > 0 ? ' is-part' : '' ) . '">' . esc_html( mmpo_money( $t['balance'], $d['currency'] ) ) . '</span>';
	}

	public function no_items(): void {
		esc_html_e( 'No purchase orders here yet.', 'mine-mirth-purchase-orders' );
	}
}

/**
 * Number of purchase orders in each status.
 *
 * @return array<string,int>
 */
function mmpo_status_counts(): array {
	global $wpdb;
	$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"SELECT pm.meta_value AS s, COUNT(*) AS n FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE pm.meta_key = '_mmpo_status' AND p.post_type = 'mmpo_order' AND p.post_status = 'publish' GROUP BY pm.meta_value"
	);
	$out = array();
	foreach ( (array) $rows as $r ) {
		$out[ (string) $r->s ] = (int) $r->n;
	}
	return $out;
}

/**
 * Figures for the top of the list: open balance, this month and orders waiting to ship.
 */
function mmpo_list_stats(): array {
	$ids    = get_posts(
		array(
			'post_type'      => 'mmpo_order',
			'post_status'    => 'publish',
			'posts_per_page' => 2000,
			'fields'         => 'ids',
		)
	);
	$month  = wp_date( 'Y-m' );
	$cur    = (string) mmpo_setting( 'currency' );
	$out    = array( 'open' => 0.0, 'open_n' => 0, 'month' => 0.0, 'month_n' => 0, 'ship_n' => 0, 'mixed' => false );
	$closed = mmpo_closed_statuses();
	update_meta_cache( 'post', $ids );
	foreach ( $ids as $id ) {
		$status  = (string) get_post_meta( $id, '_mmpo_status', true );
		$d       = mmpo_get( (int) $id );
		$t       = mmpo_totals( $d );
		$same    = $d['currency'] === $cur;
		if ( ! $same ) {
			$out['mixed'] = true;
		}
		if ( ! in_array( $status, $closed, true ) && $t['balance'] > 0 ) {
			$out['open_n']++;
			$out['open'] += $same ? $t['balance'] : 0;
		}
		if ( str_starts_with( (string) $d['date'], $month ) && ! in_array( $status, array( 'draft', 'cancelled', 'refunded' ), true ) ) {
			$out['month_n']++;
			$out['month'] += $same ? $t['total'] : 0;
		}
		if ( in_array( $status, array( 'paid', 'processing' ), true ) ) {
			$out['ship_n']++;
		}
	}
	return $out;
}

/**
 * Bulk actions on the list (before any output, so they can redirect).
 */
add_action(
	'load-toplevel_page_mmpo',
	static function (): void {
		$action = sanitize_key( wp_unslash( $_REQUEST['action'] ?? '' ) );
		if ( '-1' === $action || '' === $action ) {
			$action = sanitize_key( wp_unslash( $_REQUEST['action2'] ?? '' ) );
		}
		if ( '' === $action || '-1' === $action || empty( $_REQUEST['ids'] ) ) {
			return;
		}
		check_admin_referer( 'bulk-purchase-orders' );
		if ( ! current_user_can( mmpo_cap() ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-purchase-orders' ), 403 );
		}
		$ids = array_map( 'absint', (array) wp_unslash( $_REQUEST['ids'] ) );
		$n   = 0;
		foreach ( $ids as $id ) {
			$d = mmpo_get( $id );
			if ( ! $d ) {
				continue;
			}
			if ( str_starts_with( $action, 'status-' ) ) {
				$status = substr( $action, 7 );
				if ( isset( mmpo_statuses()[ $status ] ) && $status !== $d['status'] ) {
					$d['status']      = $status;
					$d['status_time'] = time();
					$d['history'][]   = array( 's' => $status, 't' => time(), 'u' => get_current_user_id(), 'n' => __( 'Changed from the list', 'mine-mirth-purchase-orders' ) );
					mmpo_save( $d, $id );
					++$n;
				}
			} elseif ( 'trash' === $action ) {
				wp_trash_post( $id );
				++$n;
			} elseif ( 'restore' === $action ) {
				wp_untrash_post( $id );
				wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
				++$n;
			} elseif ( 'delete' === $action ) {
				wp_delete_post( $id, true );
				++$n;
			}
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'mmpo', 'msg' => 'bulk', 'n' => $n ), admin_url( 'admin.php' ) ) );
		exit;
	}
);

/**
 * Notice when the company details the PDF needs are missing.
 */
function mmpo_setup_notice(): void {
	$s       = mmpo_settings();
	$missing = array();
	if ( '' === trim( (string) $s['address'] ) ) {
		$missing[] = __( 'address', 'mine-mirth-purchase-orders' );
	}
	if ( '' === trim( (string) $s['phone'] ) ) {
		$missing[] = __( 'phone number', 'mine-mirth-purchase-orders' );
	}
	if ( '' === trim( (string) $s['email'] ) ) {
		$missing[] = __( 'email', 'mine-mirth-purchase-orders' );
	}
	if ( ! $missing ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Add your company details for the PDF.', 'mine-mirth-purchase-orders' ) . '</strong> ';
	/* translators: %s: list of missing fields */
	echo esc_html( sprintf( __( 'Your Oregon %s is not set yet, so it can not be printed on purchase orders.', 'mine-mirth-purchase-orders' ), implode( ', ', $missing ) ) );
	echo ' <a href="' . esc_url( admin_url( 'admin.php?page=mmpo-settings' ) ) . '">' . esc_html__( 'Open Settings', 'mine-mirth-purchase-orders' ) . '</a></p></div>';
}

function mmpo_list_page(): void {
	if ( ! current_user_can( mmpo_cap() ) ) {
		return;
	}
	$table = new MMPO_List_Table();
	$table->prepare_items();
	$stats = mmpo_list_stats();
	$cur   = (string) mmpo_setting( 'currency' );
	$msg   = sanitize_key( wp_unslash( $_GET['msg'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$msgs  = array(
		'trashed'  => __( 'Moved to the Trash.', 'mine-mirth-purchase-orders' ),
		'restored' => __( 'Restored.', 'mine-mirth-purchase-orders' ),
		'deleted'  => __( 'Deleted for good.', 'mine-mirth-purchase-orders' ),
		/* translators: %d: count */
		'bulk'     => sprintf( _n( '%d purchase order updated.', '%d purchase orders updated.', absint( $_GET['n'] ?? 0 ), 'mine-mirth-purchase-orders' ), absint( $_GET['n'] ?? 0 ) ), // phpcs:ignore WordPress.Security.NonceVerification
	);
	?>
	<div class="wrap mmpo-admin">
		<h1 class="wp-heading-inline mmpo-title"><?php esc_html_e( 'Purchase orders', 'mine-mirth-purchase-orders' ); ?></h1>
		<a href="<?php echo esc_url( mmpo_edit_url() ); ?>" class="page-title-action"><?php esc_html_e( 'New purchase order', 'mine-mirth-purchase-orders' ); ?></a>
		<hr class="wp-header-end">
		<?php
		mmpo_setup_notice();
		if ( isset( $msgs[ $msg ] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msgs[ $msg ] ) . '</p></div>';
		}
		?>
		<div class="mmpo-stats">
			<div><small><?php esc_html_e( 'Balance due', 'mine-mirth-purchase-orders' ); ?></small><strong><?php echo esc_html( mmpo_money( $stats['open'], $cur ) ); ?></strong><span><?php echo esc_html( sprintf( /* translators: %d: count */ _n( '%d open purchase order', '%d open purchase orders', $stats['open_n'], 'mine-mirth-purchase-orders' ), $stats['open_n'] ) ); ?></span></div>
			<div><small><?php esc_html_e( 'This month', 'mine-mirth-purchase-orders' ); ?></small><strong><?php echo esc_html( mmpo_money( $stats['month'], $cur ) ); ?></strong><span><?php echo esc_html( sprintf( /* translators: %d: count */ _n( '%d purchase order', '%d purchase orders', $stats['month_n'], 'mine-mirth-purchase-orders' ), $stats['month_n'] ) ); ?></span></div>
			<div><small><?php esc_html_e( 'Ready to ship', 'mine-mirth-purchase-orders' ); ?></small><strong><?php echo (int) $stats['ship_n']; ?></strong><span><?php esc_html_e( 'Paid or processing', 'mine-mirth-purchase-orders' ); ?></span></div>
		</div>
		<?php if ( $stats['mixed'] ) : ?>
			<p class="mmpo-sub"><?php echo esc_html( sprintf( /* translators: %s: currency */ __( 'Figures above count purchase orders in %s only.', 'mine-mirth-purchase-orders' ), $cur ) ); ?></p>
		<?php endif; ?>
		<form method="get">
			<input type="hidden" name="page" value="mmpo">
			<?php if ( ! empty( $_GET['status'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<input type="hidden" name="status" value="<?php echo esc_attr( sanitize_key( wp_unslash( $_GET['status'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification ?>">
			<?php endif; ?>
			<?php
			$table->views();
			$table->search_box( __( 'Search purchase orders', 'mine-mirth-purchase-orders' ), 'mmpo-search' );
			$table->display();
			?>
		</form>
	</div>
	<?php
}

/* ------------------------------------------------------------------ Search (editor) */

add_action(
	'wp_ajax_mmpo_products',
	static function (): void {
		check_ajax_referer( 'mmpo_edit', 'nonce' );
		if ( ! current_user_can( mmpo_cap() ) ) {
			wp_send_json_error( null, 403 );
		}
		if ( ! function_exists( 'wc_get_product' ) ) {
			wp_send_json_success( array() );
		}
		$term = trim( sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ) );
		if ( '' !== $term && class_exists( 'WC_Data_Store' ) ) {
			$store = WC_Data_Store::load( 'product' );
			$ids   = array_slice( array_map( 'intval', (array) $store->search_products( $term, '', true, false, 30 ) ), 0, 30 );
		} else {
			$ids = wc_get_products(
				array(
					'limit'   => 20,
					'status'  => 'publish',
					'orderby' => 'date',
					'order'   => 'DESC',
					'return'  => 'ids',
				)
			);
		}
		$out = array();
		foreach ( $ids as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p || $p->is_type( 'variable' ) && $p->get_children() && '' !== $term || ! in_array( $p->get_status(), array( 'publish', 'private' ), true ) ) {
				continue;
			}
			$stock = $p->get_stock_status();
			$img   = wp_get_attachment_image_url( (int) $p->get_image_id(), 'thumbnail' );
			$out[] = array(
				'id'    => $p->get_id(),
				'name'  => wp_strip_all_tags( $p->get_name() ),
				'sku'   => (string) $p->get_sku(),
				'price' => '' !== $p->get_price() ? (float) $p->get_price() : null,
				'specs' => mmpo_product_specs( $p ),
				'stock' => 'instock' === $stock ? __( 'In stock', 'mine-mirth-purchase-orders' ) : ( 'onbackorder' === $stock ? __( 'Pre-order', 'mine-mirth-purchase-orders' ) : __( 'Sold out', 'mine-mirth-purchase-orders' ) ),
				'in'    => 'outofstock' !== $stock,
				'img'   => $img ? (string) $img : '',
			);
			if ( count( $out ) >= 20 ) {
				break;
			}
		}
		wp_send_json_success( $out );
	}
);

add_action(
	'wp_ajax_mmpo_customers',
	static function (): void {
		check_ajax_referer( 'mmpo_edit', 'nonce' );
		if ( ! current_user_can( mmpo_cap() ) ) {
			wp_send_json_error( null, 403 );
		}
		$term = trim( sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ) );
		if ( mb_strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}
		$out  = array();
		$seen = array();
		$add  = static function ( array $ship, string $source ) use ( &$out, &$seen ): void {
			$key = mb_strtolower( $ship['name'] . '|' . $ship['address_1'] . '|' . $ship['postcode'] );
			if ( '|' === trim( $key, '|' ) || isset( $seen[ $key ] ) ) {
				return;
			}
			$seen[ $key ] = true;
			$out[]        = array( 'ship' => $ship, 'source' => $source );
		};

		// Earlier purchase orders.
		$q = new WP_Query(
			array(
				'post_type'      => 'mmpo_order',
				'post_status'    => 'publish',
				'posts_per_page' => 8,
				'meta_query'     => array( array( 'key' => '_mmpo_search', 'value' => mb_strtolower( $term ), 'compare' => 'LIKE' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		foreach ( $q->posts as $p ) {
			$d = mmpo_get( (int) $p->ID );
			/* translators: %s: number */
			$add( $d['ship'], sprintf( __( 'Purchase order %s', 'mine-mirth-purchase-orders' ), $d['number'] ) );
		}

		// WooCommerce orders and customers.
		if ( function_exists( 'wc_get_order' ) && class_exists( 'WC_Data_Store' ) ) {
			$store = WC_Data_Store::load( 'order' );
			$ids   = array_slice( (array) $store->search_orders( $term ), 0, 8 );
			foreach ( $ids as $oid ) {
				$o = wc_get_order( $oid );
				if ( ! $o ) {
					continue;
				}
				$has_ship = '' !== $o->get_shipping_address_1();
				$pre      = $has_ship ? 'shipping' : 'billing';
				$get      = static fn( string $f ) => (string) ( is_callable( array( $o, 'get_' . $pre . '_' . $f ) ) ? $o->{'get_' . $pre . '_' . $f}() : '' );
				$add(
					array(
						'name'      => trim( $get( 'first_name' ) . ' ' . $get( 'last_name' ) ),
						'company'   => $get( 'company' ),
						'address_1' => $get( 'address_1' ),
						'address_2' => $get( 'address_2' ),
						'city'      => $get( 'city' ),
						'state'     => $get( 'state' ),
						'postcode'  => $get( 'postcode' ),
						'country'   => $get( 'country' ),
						'phone'     => (string) ( $o->get_shipping_phone() ? $o->get_shipping_phone() : $o->get_billing_phone() ),
						'email'     => (string) $o->get_billing_email(),
					),
					/* translators: %s: order number */
					sprintf( __( 'Order #%s', 'mine-mirth-purchase-orders' ), $o->get_order_number() )
				);
			}
			$users = get_users(
				array(
					'search'         => '*' . $term . '*',
					'search_columns' => array( 'user_email', 'display_name', 'user_login' ),
					'number'         => 6,
				)
			);
			foreach ( $users as $u ) {
				try {
					$c = new WC_Customer( $u->ID );
				} catch ( Exception $e ) {
					continue;
				}
				$has_ship = '' !== $c->get_shipping_address_1();
				$pre      = $has_ship ? 'shipping' : 'billing';
				$get      = static fn( string $f ) => (string) ( is_callable( array( $c, 'get_' . $pre . '_' . $f ) ) ? $c->{'get_' . $pre . '_' . $f}() : '' );
				$name     = trim( $get( 'first_name' ) . ' ' . $get( 'last_name' ) );
				$add(
					array(
						'name'      => '' !== $name ? $name : $u->display_name,
						'company'   => $get( 'company' ),
						'address_1' => $get( 'address_1' ),
						'address_2' => $get( 'address_2' ),
						'city'      => $get( 'city' ),
						'state'     => $get( 'state' ),
						'postcode'  => $get( 'postcode' ),
						'country'   => $get( 'country' ),
						'phone'     => (string) $c->get_billing_phone(),
						'email'     => (string) $u->user_email,
					),
					__( 'Customer account', 'mine-mirth-purchase-orders' )
				);
			}
		}
		wp_send_json_success( array_slice( $out, 0, 12 ) );
	}
);
