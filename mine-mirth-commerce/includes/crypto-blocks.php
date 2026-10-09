<?php
/**
 * Crypto gateway in the block checkout.
 *
 * @package MineMirthCommerce
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class MMC_Crypto_Blocks extends AbstractPaymentMethodType {

	protected $name = 'mmc_crypto';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_mmc_crypto_settings', array() );
	}

	public function is_active() {
		$gw = mmc_crypto_gateway();
		return $gw && $gw->is_available();
	}

	public function get_payment_method_script_handles() {
		wp_register_script( 'mmc-crypto-blocks', MMC_URI . 'assets/js/mmc-crypto-blocks.js', array( 'wc-blocks-registry', 'wp-element', 'wp-html-entities' ), MMC_VERSION, true );
		return array( 'mmc-crypto-blocks' );
	}

	public function get_payment_method_data() {
		$gw    = mmc_crypto_gateway();
		$coins = array();
		if ( $gw ) {
			foreach ( $gw->enabled_coins() as $key => $coin ) {
				$coins[] = array( 'key' => $key, 'symbol' => $coin['symbol'], 'label' => $coin['label'] );
			}
		}
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'coins'       => $coins,
			'supports'    => array( 'products' ),
		);
	}
}
