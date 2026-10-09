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
					foreach ( array( 'filters_auto', 'show_empty_cats' ) as $k ) {
						$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
					}
					foreach ( array( 'label_instock', 'label_preorder', 'label_outofstock' ) as $k ) {
						$v         = sanitize_text_field( (string) ( $in[ $k ] ?? '' ) );
						$out[ $k ] = '' !== $v ? $v : $d[ $k ];
					}
					$out['hidden_attrs'] = array_values( array_map( 'sanitize_key', (array) ( $in['hidden_attrs'] ?? array() ) ) );
					return $out;
				},
			)
		);
	}
);

function mmsp_admin_page(): void {
	$o = static fn( $k ) => mmsp_opt( $k );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Mine Mirth Shop Pro', 'mine-mirth-shop-pro' ); ?></h1>
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

			<?php submit_button(); ?>
		</form>

	</div>
	<?php
}
