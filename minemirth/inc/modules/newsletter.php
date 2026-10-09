<?php
/**
 * Newsletter signups.
 *
 * Providers (Customizer > Mine Mirth · Pages, Data & Advanced > Newsletter):
 *   local     — stored as private "Subscriber" posts, exportable as CSV;
 *   action    — the form posts to an external URL (Mailchimp, ConvertKit…);
 *   shortcode — any form plugin's shortcode is rendered instead.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

function mm_register_subscriber_post_type(): void {
	register_post_type(
		'mm_subscriber',
		array(
			'labels'          => array(
				'name'          => __( 'Subscribers', 'minemirth' ),
				'singular_name' => __( 'Subscriber', 'minemirth' ),
				'menu_name'     => __( 'Subscribers', 'minemirth' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'edit.php?post_type=mm_promo',
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'mm_register_subscriber_post_type' );

/**
 * Render a signup form.
 *
 * @param array $args context, compact, button.
 */
function mm_newsletter_form( array $args = array() ): void {
	$args     = wp_parse_args(
		$args,
		array(
			'context' => 'general',
			'compact' => false,
			'button'  => (string) mm_opt( 'newsletter_button' ),
		)
	);
	$provider = (string) mm_opt( 'newsletter_provider' );
	$classes  = 'mm-subscribe' . ( $args['compact'] ? ' mm-subscribe--compact' : '' );

	if ( 'shortcode' === $provider && mm_opt( 'newsletter_shortcode' ) ) {
		echo '<div class="' . esc_attr( $classes ) . '">' . do_shortcode( (string) mm_opt( 'newsletter_shortcode' ) ) . '</div>';
		return;
	}

	$is_external = 'action' === $provider && mm_opt( 'newsletter_action' );
	$action      = $is_external ? (string) mm_opt( 'newsletter_action' ) : admin_url( 'admin-post.php' );
	$field       = $is_external ? (string) mm_opt( 'newsletter_field' ) : 'email';
	$id          = wp_unique_id( 'mm-sub-' );
	$done        = ! $is_external && isset( $_GET['mm_subscribed'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<div class="<?php echo esc_attr( $classes . ( $done ? ' is-done' : '' ) ); ?>">
		<form method="post" action="<?php echo esc_url( $action ); ?>" <?php echo $is_external ? 'target="_blank" rel="noopener"' : 'data-mm-subscribe="local"'; ?> novalidate>
			<div class="mm-subscribe__row">
				<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Email address', 'minemirth' ); ?></label>
				<input id="<?php echo esc_attr( $id ); ?>" type="email" name="<?php echo esc_attr( $field ); ?>" required autocomplete="email" placeholder="<?php esc_attr_e( 'you@example.com', 'minemirth' ); ?>">
				<button class="mm-btn mm-btn--primary" type="submit"><span><?php echo esc_html( $args['button'] ); ?></span></button>
			</div>
			<?php if ( ! $is_external ) : ?>
				<input type="hidden" name="action" value="mm_subscribe">
				<input type="hidden" name="context" value="<?php echo esc_attr( $args['context'] ); ?>">
				<input type="hidden" name="redirect" value="<?php echo esc_url( add_query_arg( array() ) ); ?>">
				<?php wp_nonce_field( 'mm_subscribe', 'mm_subscribe_nonce', false ); ?>
				<span class="mm-subscribe__hp" aria-hidden="true"><label><?php esc_html_e( 'Leave this empty', 'minemirth' ); ?><input type="text" name="website" tabindex="-1" autocomplete="off"></label></span>
			<?php endif; ?>
		</form>
		<?php if ( mm_opt( 'newsletter_consent' ) ) : ?>
			<p class="mm-subscribe__note"><?php echo esc_html( (string) mm_opt( 'newsletter_consent' ) ); ?></p>
		<?php endif; ?>
		<p class="mm-subscribe__msg<?php echo $done ? ' is-ok' : ''; ?>" role="status"><?php echo $done ? esc_html( (string) mm_opt( 'newsletter_success' ) ) : ''; ?></p>
	</div>
	<?php
}

/**
 * Store a subscriber. Returns [ok(bool), message].
 */
function mm_newsletter_store( string $email, string $context ): array {
	$email = sanitize_email( $email );
	if ( ! is_email( $email ) ) {
		return array( false, __( 'Please enter a valid email address.', 'minemirth' ) );
	}

	// Light rate limit per IP (hashed, never stored raw).
	$ip_hash = substr( hash( 'sha256', ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() ), 0, 16 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$key     = 'mm_sub_rl_' . $ip_hash;
	$hits    = (int) get_transient( $key );
	if ( $hits >= 6 ) {
		return array( false, __( 'Too many attempts. Please try again in a few minutes.', 'minemirth' ) );
	}
	set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

	$existing = get_posts(
		array(
			'post_type'      => 'mm_subscriber',
			'post_status'    => 'any',
			'title'          => $email,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( ! $existing ) {
		$id = wp_insert_post(
			array(
				'post_type'   => 'mm_subscriber',
				'post_status' => 'publish',
				'post_title'  => $email,
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_mm_context', sanitize_key( $context ) );
			update_post_meta( $id, '_mm_consent', current_time( 'mysql' ) );
			/**
			 * Fires after a new subscriber is saved (hook your ESP here).
			 */
			do_action( 'mm_new_subscriber', $email, $id, $context );
		}
	}
	return array( true, (string) mm_opt( 'newsletter_success' ) );
}

/**
 * REST: POST /mine-mirth/v1/subscribe
 */
function mm_newsletter_routes(): void {
	register_rest_route(
		'mine-mirth/v1',
		'/subscribe',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'permission_callback' => '__return_true',
			'callback'            => static function ( WP_REST_Request $request ) {
				if ( '' !== (string) $request->get_param( 'website' ) ) {
					return new WP_REST_Response( array( 'message' => (string) mm_opt( 'newsletter_success' ) ), 200 );
				}
				list( $ok, $message ) = mm_newsletter_store( (string) $request->get_param( 'email' ), (string) $request->get_param( 'context' ) );
				return new WP_REST_Response( array( 'message' => $message ), $ok ? 200 : 400 );
			},
		)
	);
}
add_action( 'rest_api_init', 'mm_newsletter_routes' );

/**
 * No-JS fallback via admin-post.php.
 */
function mm_newsletter_admin_post(): void {
	$redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : home_url( '/' );
	$redirect = wp_validate_redirect( $redirect, home_url( '/' ) );
	if ( ! isset( $_POST['mm_subscribe_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mm_subscribe_nonce'] ) ), 'mm_subscribe' ) || ! empty( $_POST['website'] ) ) {
		wp_safe_redirect( $redirect );
		exit;
	}
	list( $ok ) = mm_newsletter_store( isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '', isset( $_POST['context'] ) ? sanitize_key( $_POST['context'] ) : 'general' );
	wp_safe_redirect( $ok ? add_query_arg( 'mm_subscribed', '1', $redirect ) : $redirect );
	exit;
}
add_action( 'admin_post_mm_subscribe', 'mm_newsletter_admin_post' );
add_action( 'admin_post_nopriv_mm_subscribe', 'mm_newsletter_admin_post' );

/**
 * Admin list columns + CSV export.
 */
function mm_subscriber_columns( array $cols ): array {
	return array(
		'cb'         => $cols['cb'] ?? '',
		'title'      => __( 'Email', 'minemirth' ),
		'mm_context' => __( 'Signed up from', 'minemirth' ),
		'date'       => __( 'Date', 'minemirth' ),
	);
}
add_filter( 'manage_mm_subscriber_posts_columns', 'mm_subscriber_columns' );

function mm_subscriber_column( string $col, int $id ): void {
	if ( 'mm_context' === $col ) {
		echo esc_html( (string) get_post_meta( $id, '_mm_context', true ) );
	}
}
add_action( 'manage_mm_subscriber_posts_custom_column', 'mm_subscriber_column', 10, 2 );

function mm_subscriber_export_button( string $which ): void {
	global $typenow;
	if ( 'mm_subscriber' === $typenow && 'top' === $which ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=mm_export_subscribers' ), 'mm_export_subscribers' );
		echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Export CSV', 'minemirth' ) . '</a>';
	}
}
add_action( 'manage_posts_extra_tablenav', 'mm_subscriber_export_button' );

function mm_subscriber_export(): void {
	if ( ! current_user_can( 'edit_posts' ) || ! check_admin_referer( 'mm_export_subscribers' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'minemirth' ) );
	}
	$ids = get_posts(
		array(
			'post_type'      => 'mm_subscriber',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=mine-mirth-subscribers-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'email', 'source', 'date' ) );
	foreach ( $ids as $id ) {
		fputcsv( $out, array( get_the_title( $id ), get_post_meta( $id, '_mm_context', true ), get_post_field( 'post_date', $id ) ) );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_mm_export_subscribers', 'mm_subscriber_export' );

/**
 * [mm_newsletter] shortcode.
 */
add_shortcode(
	'mm_newsletter',
	static function () {
		ob_start();
		mm_newsletter_form( array( 'context' => 'shortcode' ) );
		return (string) ob_get_clean();
	}
);
