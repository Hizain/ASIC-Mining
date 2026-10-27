<?php
/**
 * My Account Dashboard — Mine Mirth layout.
 *
 * Welcome header, stat cards, recent orders, quick actions and support.
 * The standard dashboard hooks still fire for plugins.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.4.0
 *
 * @var WP_User $current_user
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$allowed_html = array(
	'a' => array(
		'href' => array(),
	),
);

$mm_uid     = (int) $current_user->ID;
$mm_name    = $current_user->first_name ? $current_user->first_name : $current_user->display_name;
$mm_orders  = function_exists( 'wc_get_orders' ) ? wc_get_orders(
	array(
		'customer' => $mm_uid,
		'limit'    => 3,
		'orderby'  => 'date',
		'order'    => 'DESC',
	)
) : array();
$mm_count   = function_exists( 'wc_get_customer_order_count' ) ? wc_get_customer_order_count( $mm_uid ) : count( $mm_orders );
$mm_spent   = function_exists( 'wc_get_customer_total_spent' ) ? (float) wc_get_customer_total_spent( $mm_uid ) : 0;
$mm_last    = $mm_orders ? $mm_orders[0] : null;
$mm_open    = 0;
foreach ( $mm_orders as $mm_o ) {
	if ( in_array( $mm_o->get_status(), array( 'pending', 'processing', 'on-hold' ), true ) ) {
		++$mm_open;
	}
}
?>
<div class="mm-dash">
	<header class="mm-dash__hello">
		<div>
			<span class="mm-eyebrow"><?php esc_html_e( 'Account', 'minemirth' ); ?></span>
			<h2><?php echo esc_html( str_replace( '{name}', $mm_name, (string) mm_opt( 'account_welcome' ) ) ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: 1: user display name 2: logout url */
					wp_kses( __( 'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)', 'woocommerce' ), $allowed_html ),
					'<strong>' . esc_html( $current_user->display_name ) . '</strong>',
					esc_url( wc_logout_url() )
				);
				?>
			</p>
		</div>
		<?php echo mm_button( __( 'Shop miners', 'minemirth' ), '{shop}', 'primary', array( 'icon' => 'arrow-right', 'size' => 'sm' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</header>

	<?php if ( mm_opt( 'account_stats' ) ) : ?>
		<div class="mm-dash__stats">
			<div class="mm-dash__stat mm-card">
				<span class="mm-icon-badge"><?php echo mm_icon( 'package' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<small><?php esc_html_e( 'Orders', 'minemirth' ); ?></small>
				<strong><?php echo esc_html( number_format_i18n( (int) $mm_count ) ); ?></strong>
			</div>
			<div class="mm-dash__stat mm-card">
				<span class="mm-icon-badge"><?php echo mm_icon( 'wallet' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<small><?php esc_html_e( 'Total spent', 'minemirth' ); ?></small>
				<strong><?php echo wp_kses_post( wc_price( $mm_spent ) ); ?></strong>
			</div>
			<div class="mm-dash__stat mm-card">
				<span class="mm-icon-badge"><?php echo mm_icon( 'activity' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<small><?php esc_html_e( 'In progress', 'minemirth' ); ?></small>
				<strong><?php echo esc_html( number_format_i18n( $mm_open ) ); ?></strong>
			</div>
			<div class="mm-dash__stat mm-card">
				<span class="mm-icon-badge"><?php echo mm_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<small><?php esc_html_e( 'Last order', 'minemirth' ); ?></small>
				<strong><?php echo $mm_last && $mm_last->get_date_created() ? esc_html( wc_format_datetime( $mm_last->get_date_created() ) ) : '—'; ?></strong>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( mm_opt( 'account_recent' ) ) : ?>
		<section class="mm-dash__panel mm-card">
			<div class="mm-dash__panel-head">
				<h3><?php esc_html_e( 'Recent orders', 'minemirth' ); ?></h3>
				<?php if ( $mm_orders ) : ?>
					<a class="mm-link-arrow" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'All orders', 'minemirth' ); ?><?php echo mm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<?php endif; ?>
			</div>
			<?php if ( $mm_orders ) : ?>
				<ul class="mm-dash__orders">
					<?php foreach ( $mm_orders as $mm_order ) : ?>
						<?php
						$mm_items = $mm_order->get_items();
						$mm_first = $mm_items ? reset( $mm_items ) : null;
						$mm_prod  = $mm_first && is_callable( array( $mm_first, 'get_product' ) ) ? $mm_first->get_product() : null;
						?>
						<li>
							<a href="<?php echo esc_url( $mm_order->get_view_order_url() ); ?>">
								<span class="mm-dash__thumb"><?php echo $mm_prod ? $mm_prod->get_image( 'woocommerce_gallery_thumbnail' ) : mm_icon( 'package' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								<span class="mm-dash__order-main">
									<strong>
										<?php
										/* translators: %s: order number */
										echo esc_html( sprintf( __( 'Order #%s', 'minemirth' ), $mm_order->get_order_number() ) );
										?>
									</strong>
									<small>
										<?php
										echo esc_html( wc_format_datetime( $mm_order->get_date_created() ) );
										echo ' · ';
										/* translators: %d: item count */
										echo esc_html( sprintf( _n( '%d item', '%d items', $mm_order->get_item_count(), 'minemirth' ), $mm_order->get_item_count() ) );
										?>
									</small>
								</span>
								<?php echo mm_order_status_badge( $mm_order ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span class="mm-dash__total"><?php echo wp_kses_post( $mm_order->get_formatted_order_total() ); ?></span>
								<?php echo mm_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<div class="mm-dash__empty">
					<p><?php esc_html_e( 'No orders yet. When you buy a miner, you can track it here from dispatch to delivery.', 'minemirth' ); ?></p>
					<?php echo mm_button( __( 'Browse miners', 'minemirth' ), '{shop}', 'ghost', array( 'size' => 'sm', 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<div class="mm-dash__actions">
		<?php
		$mm_actions = array(
			array( 'orders', 'package', __( 'Orders', 'minemirth' ), __( 'Track, view invoices, reorder', 'minemirth' ) ),
			array( 'edit-address', 'map-pin', __( 'Addresses', 'minemirth' ), __( 'Billing & shipping details', 'minemirth' ) ),
			array( 'edit-account', 'user', __( 'Account details', 'minemirth' ), __( 'Name, email and password', 'minemirth' ) ),
		);
		$mm_menu    = wc_get_account_menu_items();
		foreach ( $mm_actions as $mm_action ) :
			if ( ! isset( $mm_menu[ $mm_action[0] ] ) ) {
				continue;
			}
			?>
			<a class="mm-dash__action mm-card" href="<?php echo esc_url( wc_get_account_endpoint_url( $mm_action[0] ) ); ?>">
				<span class="mm-icon-badge"><?php echo mm_icon( $mm_action[1] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<strong><?php echo esc_html( $mm_action[2] ); ?></strong>
				<small><?php echo esc_html( $mm_action[3] ); ?></small>
			</a>
		<?php endforeach; ?>
		<a class="mm-dash__action mm-card" href="<?php echo esc_url( home_url( '/miner-hosting/' ) ); ?>">
			<span class="mm-icon-badge"><?php echo mm_icon( 'server' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<strong><?php esc_html_e( 'Hosting', 'minemirth' ); ?></strong>
			<small><?php esc_html_e( 'Plans, rates and availability', 'minemirth' ); ?></small>
		</a>
	</div>

	<?php if ( mm_opt( 'account_support_title' ) ) : ?>
		<section class="mm-dash__support mm-scheme-dark">
			<span class="mm-icon-badge"><?php echo mm_icon( 'headset' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<div>
				<h3><?php echo esc_html( (string) mm_opt( 'account_support_title' ) ); ?></h3>
				<p><?php echo esc_html( (string) mm_opt( 'account_support_text' ) ); ?></p>
			</div>
			<?php echo mm_button( __( 'Contact support', 'minemirth' ), mm_opt( 'account_support_url' ), 'primary', array( 'size' => 'sm', 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</section>
	<?php endif; ?>
</div>

<?php
	/**
	 * My Account dashboard.
	 *
	 * @since 2.6.0
	 */
	do_action( 'woocommerce_account_dashboard' );

	/**
	 * Deprecated woocommerce_before_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_before_my_account' );

	/**
	 * Deprecated woocommerce_after_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_after_my_account' );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
