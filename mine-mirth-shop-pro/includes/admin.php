<?php
/**
 * Settings: WooCommerce › Shop Pro.
 *
 * @package MineMirthShopPro
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	static function () {
		add_submenu_page( 'woocommerce', __( 'Shop Pro', 'mine-mirth-shop-pro' ), __( 'Shop Pro', 'mine-mirth-shop-pro' ), 'manage_woocommerce', 'mmsp', 'mmsp_admin_page' );
	},
	60
);

add_action(
	'admin_init',
	static function () {
		register_setting(
			'mmsp',
			'mmsp_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => static function ( $in ) {
					$d   = mmsp_defaults();
					$in  = is_array( $in ) ? $in : array();
					$out = array();
					foreach ( array( 'filters_auto', 'show_empty_cats', 'best_enabled', 'best_strip', 'best_badge' ) as $k ) {
						$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
					}
					foreach ( array( 'label_instock', 'label_preorder', 'label_outofstock' ) as $k ) {
						$v         = sanitize_text_field( (string) ( $in[ $k ] ?? '' ) );
						$out[ $k ] = '' !== $v ? $v : $d[ $k ];
					}
					$out['hidden_attrs'] = array_values( array_map( 'sanitize_key', (array) ( $in['hidden_attrs'] ?? array() ) ) );
					$out['best_count']   = max( 2, min( 12, (int) ( $in['best_count'] ?? 4 ) ) );
					$out['best_hours']   = max( 1, min( 48, (int) ( $in['best_hours'] ?? 6 ) ) );
					$out['best_power']   = (string) max( 0, (float) ( $in['best_power'] ?? 0.08 ) );
					$out['best_exclude'] = implode( ',', array_filter( array_map( 'absint', explode( ',', (string) ( $in['best_exclude'] ?? '' ) ) ) ) );
					// New settings change the picks: recalculate at the next look.
					$saved = get_option( 'mmsp_best', array() );
					if ( is_array( $saved ) && $saved ) {
						$saved['at'] = 0;
						update_option( 'mmsp_best', $saved, false );
					}
					return $out;
				},
			)
		);
	}
);

add_action(
	'admin_post_mmsp_recalc',
	static function () {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'mine-mirth-shop-pro' ) );
		}
		check_admin_referer( 'mmsp_recalc' );
		delete_transient( 'mmsp_best_lock' );
		mmsp_refresh_best( true );
		wp_safe_redirect( admin_url( 'admin.php?page=mmsp&recalc=1' ) );
		exit;
	}
);

function mmsp_admin_page(): void {
	$o     = static fn( $k ) => mmsp_opt( $k );
	$saved = get_option( 'mmsp_best', array() );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Mine Mirth Shop Pro', 'mine-mirth-shop-pro' ); ?></h1>
		<?php if ( ! empty( $_GET['recalc'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Best choices recalculated from the latest market data.', 'mine-mirth-shop-pro' ); ?></p></div>
		<?php endif; ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'mmsp' ); ?>
			<h2><?php esc_html_e( 'Filters', 'mine-mirth-shop-pro' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Show the filter panel', 'mine-mirth-shop-pro' ); ?></th><td><label><input type="checkbox" name="mmsp_settings[filters_auto]" value="1" <?php checked( (int) $o( 'filters_auto' ), 1 ); ?>> <?php esc_html_e( 'At the top of the shop sidebar (also usable with [minemirth_shop_filters])', 'mine-mirth-shop-pro' ); ?></label></td></tr>
				<tr><th><?php esc_html_e( 'Availability names', 'mine-mirth-shop-pro' ); ?></th><td>
					<input type="text" name="mmsp_settings[label_instock]" value="<?php echo esc_attr( (string) $o( 'label_instock' ) ); ?>" class="regular-text" aria-label="In stock"><br>
					<input type="text" name="mmsp_settings[label_preorder]" value="<?php echo esc_attr( (string) $o( 'label_preorder' ) ); ?>" class="regular-text" aria-label="Pre-order" style="margin-top:6px"><br>
					<input type="text" name="mmsp_settings[label_outofstock]" value="<?php echo esc_attr( (string) $o( 'label_outofstock' ) ); ?>" class="regular-text" aria-label="Out of stock" style="margin-top:6px">
					<p class="description"><?php esc_html_e( 'Pre-Order includes products set to "On backorder" and products marked as pre-order by a pre-order plugin.', 'mine-mirth-shop-pro' ); ?></p>
				</td></tr>
				<tr><th><?php esc_html_e( 'Empty categories', 'mine-mirth-shop-pro' ); ?></th><td><label><input type="checkbox" name="mmsp_settings[show_empty_cats]" value="1" <?php checked( (int) $o( 'show_empty_cats' ), 1 ); ?>> <?php esc_html_e( 'Also list categories with no products', 'mine-mirth-shop-pro' ); ?></label></td></tr>
				<tr><th><?php esc_html_e( 'Hide these attributes', 'mine-mirth-shop-pro' ); ?></th><td>
					<?php
					$hidden = (array) $o( 'hidden_attrs' );
					foreach ( wc_get_attribute_taxonomies() as $attr ) :
						$tax = wc_attribute_taxonomy_name( $attr->attribute_name );
						?>
						<label style="display:inline-block;margin:0 16px 6px 0"><input type="checkbox" name="mmsp_settings[hidden_attrs][]" value="<?php echo esc_attr( $tax ); ?>" <?php checked( in_array( $tax, $hidden, true ) ); ?>> <?php echo esc_html( $attr->attribute_label ); ?></label>
					<?php endforeach; ?>
					<p class="description"><?php esc_html_e( 'Every attribute in Products › Attributes shows as a filter unless ticked here.', 'mine-mirth-shop-pro' ); ?></p>
				</td></tr>
			</table>

			<h2><?php esc_html_e( 'Best choice miners', 'mine-mirth-shop-pro' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Enable', 'mine-mirth-shop-pro' ); ?></th><td>
					<label><input type="checkbox" name="mmsp_settings[best_enabled]" value="1" <?php checked( (int) $o( 'best_enabled' ), 1 ); ?>> <?php esc_html_e( 'Pick best choice miners automatically', 'mine-mirth-shop-pro' ); ?></label><br>
					<label><input type="checkbox" name="mmsp_settings[best_strip]" value="1" <?php checked( (int) $o( 'best_strip' ), 1 ); ?>> <?php esc_html_e( 'Show "Best choices right now" above the products (shop and category pages)', 'mine-mirth-shop-pro' ); ?></label><br>
					<label><input type="checkbox" name="mmsp_settings[best_badge]" value="1" <?php checked( (int) $o( 'best_badge' ), 1 ); ?>> <?php esc_html_e( 'Add a "Best choice" label to picked miners in the product grid', 'mine-mirth-shop-pro' ); ?></label>
				</td></tr>
				<tr><th><label for="mmsp-count"><?php esc_html_e( 'Number of picks', 'mine-mirth-shop-pro' ); ?></label></th><td><input id="mmsp-count" type="number" min="2" max="12" name="mmsp_settings[best_count]" value="<?php echo esc_attr( (string) $o( 'best_count' ) ); ?>" class="small-text"></td></tr>
				<tr><th><label for="mmsp-power"><?php esc_html_e( 'Power price for payback ($/kWh)', 'mine-mirth-shop-pro' ); ?></label></th><td><input id="mmsp-power" type="number" min="0" step="0.001" name="mmsp_settings[best_power]" value="<?php echo esc_attr( (string) $o( 'best_power' ) ); ?>" class="small-text"> <span class="description"><?php esc_html_e( 'A typical rate for your buyers or hosting.', 'mine-mirth-shop-pro' ); ?></span></td></tr>
				<tr><th><label for="mmsp-hours"><?php esc_html_e( 'Refresh every (hours)', 'mine-mirth-shop-pro' ); ?></label></th><td><input id="mmsp-hours" type="number" min="1" max="48" name="mmsp_settings[best_hours]" value="<?php echo esc_attr( (string) $o( 'best_hours' ) ); ?>" class="small-text"></td></tr>
				<tr><th><label for="mmsp-excl"><?php esc_html_e( 'Never pick (product IDs)', 'mine-mirth-shop-pro' ); ?></label></th><td><input id="mmsp-excl" type="text" name="mmsp_settings[best_exclude]" value="<?php echo esc_attr( (string) $o( 'best_exclude' ) ); ?>" class="regular-text" placeholder="123, 456"></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'Current ranking', 'mine-mirth-shop-pro' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:12px">
			<input type="hidden" name="action" value="mmsp_recalc"><?php wp_nonce_field( 'mmsp_recalc' ); ?>
			<button class="button"><?php esc_html_e( 'Recalculate now', 'mine-mirth-shop-pro' ); ?></button>
			<?php if ( ! empty( $saved['at'] ) ) : ?>
				<span class="description"><?php echo esc_html( sprintf( /* translators: %s: time */ __( 'Last calculated %s ago.', 'mine-mirth-shop-pro' ), human_time_diff( (int) $saved['at'] ) ) ); ?></span>
			<?php endif; ?>
		</form>
		<?php if ( empty( $saved['rows'] ) ) : ?>
			<p><?php esc_html_e( 'No miners ranked yet. Miners need "Hashrate" and "Power" attributes (for example "200 TH/s" and "3500 W"). Click Recalculate now.', 'mine-mirth-shop-pro' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead><tr><th>#</th><th><?php esc_html_e( 'Miner', 'mine-mirth-shop-pro' ); ?></th><th><?php esc_html_e( 'Algorithm', 'mine-mirth-shop-pro' ); ?></th><th><?php esc_html_e( 'Availability', 'mine-mirth-shop-pro' ); ?></th><th><?php esc_html_e( 'Profit / day', 'mine-mirth-shop-pro' ); ?></th><th><?php esc_html_e( 'Payback', 'mine-mirth-shop-pro' ); ?></th><th><?php esc_html_e( 'Coin trend (30 days)', 'mine-mirth-shop-pro' ); ?></th><th><?php esc_html_e( 'Score', 'mine-mirth-shop-pro' ); ?></th></tr></thead>
				<tbody>
				<?php
				$i     = 0;
				$avail = mmsp_availability();
				foreach ( $saved['rows'] as $id => $r ) :
					++$i;
					$picked = in_array( (int) $id, array_map( 'intval', (array) $saved['picks'] ), true );
					?>
					<tr<?php echo $picked ? ' style="font-weight:600"' : ''; ?>>
						<td><?php echo esc_html( (string) $i ); ?></td>
						<td><a href="<?php echo esc_url( (string) get_edit_post_link( (int) $id ) ); ?>"><?php echo esc_html( $r['name'] ); ?></a><?php echo $picked ? ' ★' : ''; ?><?php echo $r['excl'] && 'outofstock' !== $r['stock'] ? ' <em>(' . esc_html__( 'excluded', 'mine-mirth-shop-pro' ) . ')</em>' : ''; ?></td>
						<td><?php echo esc_html( mmsp_algo_label( $r['algo'] ) ); ?></td>
						<td><?php echo esc_html( $avail[ $r['stock'] ]['label'] ?? $r['stock'] ); ?></td>
						<td><?php echo null !== $r['profit'] ? wp_kses_post( wc_price( $r['profit'] ) ) : '—'; ?></td>
						<td><?php echo null !== $r['payback'] ? esc_html( number_format_i18n( round( $r['payback'] ) ) . ' ' . __( 'days', 'mine-mirth-shop-pro' ) ) : '—'; ?></td>
						<td><?php echo null !== $r['trend'] ? esc_html( ( $r['trend'] >= 0 ? '+' : '' ) . number_format_i18n( $r['trend'], 1 ) . '%' ) : '—'; ?></td>
						<td><?php echo esc_html( number_format_i18n( (float) $r['score'], 1 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description"><?php esc_html_e( 'Profit and payback are calculated for SHA-256 (Bitcoin) and Scrypt (Litecoin + Dogecoin) miners from live network data; other algorithms are ranked by efficiency against similar miners and their coin’s price trend.', 'mine-mirth-shop-pro' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}
