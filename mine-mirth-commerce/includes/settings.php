<?php
/**
 * Settings screen (MM Commerce › Settings). Crypto wallets live in
 * WooCommerce › Settings › Payments › Crypto.
 *
 * @package MineMirthCommerce
 */

defined( 'ABSPATH' ) || exit;

function mmc_settings_fields(): array {
	return array(
		'filters_auto'     => array( 'label' => __( 'Show the filters in the shop sidebar automatically', 'mine-mirth-commerce' ), 'type' => 'bool', 'default' => 1 ),
		'filters_cats'     => array( 'label' => __( 'Filter: categories', 'mine-mirth-commerce' ), 'type' => 'bool', 'default' => 1 ),
		'filters_price'    => array( 'label' => __( 'Filter: price range', 'mine-mirth-commerce' ), 'type' => 'bool', 'default' => 1 ),
		'filters_brand'    => array( 'label' => __( 'Filter: brand', 'mine-mirth-commerce' ), 'type' => 'bool', 'default' => 1 ),
		'filters_attrs'    => array( 'label' => __( 'Filter: product attributes', 'mine-mirth-commerce' ), 'type' => 'bool', 'default' => 1 ),
		'filters_stock'    => array( 'label' => __( 'Filter: in stock / on sale', 'mine-mirth-commerce' ), 'type' => 'bool', 'default' => 1 ),
		'filters_counts'   => array( 'label' => __( 'Show product counts next to filter options', 'mine-mirth-commerce' ), 'type' => 'bool', 'default' => 1 ),
		'brand_on_cards'   => array( 'label' => __( 'Show the brand above product names in the shop', 'mine-mirth-commerce' ), 'type' => 'bool', 'default' => 1 ),
		'sub_grace_days'   => array( 'label' => __( 'Subscriptions: days to pay a renewal before the subscription is put on hold', 'mine-mirth-commerce' ), 'type' => 'int', 'default' => 7 ),
		'sub_remind_days'  => array( 'label' => __( 'Subscriptions: create the renewal invoice this many days before it is due', 'mine-mirth-commerce' ), 'type' => 'int', 'default' => 3 ),
	);
}

function mmc_setting( string $key ) {
	$fields = mmc_settings_fields();
	return mmc_opt( $key, $fields[ $key ]['default'] ?? null );
}

add_action(
	'admin_menu',
	static function () {
		add_submenu_page( 'mmc', __( 'Settings', 'mine-mirth-commerce' ), __( 'Settings', 'mine-mirth-commerce' ), 'manage_options', 'mmc-settings', 'mmc_settings_page' );
	},
	20
);

add_action(
	'admin_init',
	static function () {
		register_setting(
			'mmc_settings',
			'mmc_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => static function ( $in ) {
					$out = array();
					foreach ( mmc_settings_fields() as $key => $f ) {
						$out[ $key ] = 'bool' === $f['type'] ? ( empty( $in[ $key ] ) ? 0 : 1 ) : max( 0, (int) ( $in[ $key ] ?? $f['default'] ) );
					}
					return $out;
				},
			)
		);
	}
);

function mmc_settings_page(): void {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Mine Mirth Commerce settings', 'mine-mirth-commerce' ); ?></h1>
		<p>
			<?php
			printf(
				/* translators: %s: link to payment settings */
				esc_html__( 'Crypto wallet addresses are set in %s.', 'mine-mirth-commerce' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=mmc_crypto' ) ) . '">' . esc_html__( 'WooCommerce › Settings › Payments › Crypto', 'mine-mirth-commerce' ) . '</a>'
			);
			?>
		</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'mmc_settings' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( mmc_settings_fields() as $key => $f ) : ?>
					<tr>
						<th scope="row"><label for="mmc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $f['label'] ); ?></label></th>
						<td>
							<?php if ( 'bool' === $f['type'] ) : ?>
								<input type="checkbox" id="mmc-<?php echo esc_attr( $key ); ?>" name="mmc_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( (int) mmc_setting( $key ), 1 ); ?>>
							<?php else : ?>
								<input type="number" min="0" class="small-text" id="mmc-<?php echo esc_attr( $key ); ?>" name="mmc_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) mmc_setting( $key ) ); ?>">
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
