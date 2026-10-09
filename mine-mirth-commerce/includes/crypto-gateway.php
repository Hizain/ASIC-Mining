<?php
/**
 * Crypto payments straight to your own wallets.
 *
 * No payment processor: the customer sends coins to the address you set, the
 * order waits "on hold", and it is marked paid either by you or, for Bitcoin
 * and Litecoin, automatically by reading the public blockchain
 * (mempool.space / litecoinspace.org, read-only; they never touch funds).
 *
 * @package MineMirthCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Supported coins.
 *
 * @return array<string,array{label:string,symbol:string,gecko:string,decimals:int,scheme:string,tx:string,stable:bool,auto:string}>
 */
function mmc_crypto_coins(): array {
	return array(
		'btc'        => array( 'label' => 'Bitcoin', 'symbol' => 'BTC', 'gecko' => 'bitcoin', 'decimals' => 8, 'scheme' => 'bitcoin', 'tx' => 'https://mempool.space/tx/', 'stable' => false, 'auto' => 'https://mempool.space/api' ),
		'ltc'        => array( 'label' => 'Litecoin', 'symbol' => 'LTC', 'gecko' => 'litecoin', 'decimals' => 8, 'scheme' => 'litecoin', 'tx' => 'https://litecoinspace.org/tx/', 'stable' => false, 'auto' => 'https://litecoinspace.org/api' ),
		'doge'       => array( 'label' => 'Dogecoin', 'symbol' => 'DOGE', 'gecko' => 'dogecoin', 'decimals' => 4, 'scheme' => 'dogecoin', 'tx' => 'https://blockchair.com/dogecoin/transaction/', 'stable' => false, 'auto' => '' ),
		'eth'        => array( 'label' => 'Ethereum', 'symbol' => 'ETH', 'gecko' => 'ethereum', 'decimals' => 6, 'scheme' => 'ethereum', 'tx' => 'https://etherscan.io/tx/', 'stable' => false, 'auto' => '' ),
		'usdt_erc20' => array( 'label' => 'USDT (Ethereum, ERC-20)', 'symbol' => 'USDT', 'gecko' => 'tether', 'decimals' => 2, 'scheme' => '', 'tx' => 'https://etherscan.io/tx/', 'stable' => true, 'auto' => '' ),
		'usdt_trc20' => array( 'label' => 'USDT (Tron, TRC-20)', 'symbol' => 'USDT', 'gecko' => 'tether', 'decimals' => 2, 'scheme' => '', 'tx' => 'https://tronscan.org/#/transaction/', 'stable' => true, 'auto' => '' ),
		'usdc_erc20' => array( 'label' => 'USDC (Ethereum, ERC-20)', 'symbol' => 'USDC', 'gecko' => 'usd-coin', 'decimals' => 2, 'scheme' => '', 'tx' => 'https://etherscan.io/tx/', 'stable' => true, 'auto' => '' ),
		'sol'        => array( 'label' => 'Solana', 'symbol' => 'SOL', 'gecko' => 'solana', 'decimals' => 4, 'scheme' => 'solana', 'tx' => 'https://solscan.io/tx/', 'stable' => false, 'auto' => '' ),
	);
}

/**
 * Price of each coin in the store currency, cached for two minutes.
 *
 * @return array<string,float> keyed by CoinGecko id.
 */
function mmc_crypto_rates(): array {
	$cur   = strtolower( get_woocommerce_currency() );
	$key   = 'mmc_rates_' . $cur;
	$rates = get_transient( $key );
	if ( is_array( $rates ) && $rates ) {
		return $rates;
	}
	$ids   = implode( ',', array_unique( wp_list_pluck( mmc_crypto_coins(), 'gecko' ) ) );
	$res   = wp_remote_get( 'https://api.coingecko.com/api/v3/simple/price?ids=' . rawurlencode( $ids ) . '&vs_currencies=' . rawurlencode( $cur ), array( 'timeout' => 10, 'headers' => array( 'Accept' => 'application/json' ) ) );
	$rates = array();
	if ( ! is_wp_error( $res ) && 200 === wp_remote_retrieve_response_code( $res ) ) {
		$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		foreach ( (array) $json as $id => $row ) {
			if ( isset( $row[ $cur ] ) && (float) $row[ $cur ] > 0 ) {
				$rates[ $id ] = (float) $row[ $cur ];
			}
		}
	}
	if ( $rates ) {
		set_transient( $key, $rates, 2 * MINUTE_IN_SECONDS );
		update_option( 'mmc_rates_last_' . $cur, $rates, false );
		return $rates;
	}
	// The API failed: stablecoins in USD are 1:1; other coins use the last good rate if it is recent.
	$last = get_option( 'mmc_rates_last_' . $cur, array() );
	$last = is_array( $last ) ? $last : array();
	if ( 'usd' === $cur ) {
		$last['tether']   = $last['tether'] ?? 1.0;
		$last['usd-coin'] = $last['usd-coin'] ?? 1.0;
	}
	return $last;
}

/**
 * Format a crypto amount without trailing zeros.
 */
function mmc_crypto_amount( float $value, int $decimals ): string {
	$str = number_format( $value, $decimals, '.', '' );
	return str_contains( $str, '.' ) ? rtrim( rtrim( $str, '0' ), '.' ) : $str;
}

add_filter(
	'woocommerce_payment_gateways',
	static function ( $gateways ) {
		$gateways[] = 'MMC_Gateway_Crypto';
		return $gateways;
	}
);

add_action(
	'woocommerce_loaded',
	static function () {
		mmc_define_crypto_gateway();
	}
);
mmc_define_crypto_gateway();

function mmc_define_crypto_gateway(): void {
	if ( class_exists( 'MMC_Gateway_Crypto' ) || ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	/**
	 * Direct-to-wallet crypto gateway.
	 */
	class MMC_Gateway_Crypto extends WC_Payment_Gateway {

		public function __construct() {
			$this->id                 = 'mmc_crypto';
			$this->method_title       = __( 'Crypto (direct to your wallet)', 'mine-mirth-commerce' );
			$this->method_description = __( 'Customers pay in crypto straight to your own wallet addresses. No processor and no processing fee. Orders wait on hold until the payment is confirmed.', 'mine-mirth-commerce' );
			$this->has_fields         = true;
			$this->supports           = array( 'products' );
			$this->init_form_fields();
			$this->init_settings();
			$this->title       = (string) $this->get_option( 'title' );
			$this->description = (string) $this->get_option( 'description' );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		public function init_form_fields(): void {
			$fields = array(
				'enabled'      => array( 'title' => __( 'Enable', 'mine-mirth-commerce' ), 'type' => 'checkbox', 'label' => __( 'Accept crypto payments', 'mine-mirth-commerce' ), 'default' => 'no' ),
				'title'        => array( 'title' => __( 'Title', 'mine-mirth-commerce' ), 'type' => 'text', 'default' => __( 'Pay with crypto', 'mine-mirth-commerce' ) ),
				'description'  => array( 'title' => __( 'Description', 'mine-mirth-commerce' ), 'type' => 'textarea', 'default' => __( 'Send Bitcoin, USDT or another coin straight to our wallet. You will see the exact amount and address after placing the order.', 'mine-mirth-commerce' ) ),
				'wallets'      => array( 'title' => __( 'Wallet addresses', 'mine-mirth-commerce' ), 'type' => 'title', 'description' => __( 'Fill in the coins you accept. Leave a field empty to hide that coin. Double-check every address: payments sent to a wrong address cannot be recovered.', 'mine-mirth-commerce' ) ),
			);
			foreach ( mmc_crypto_coins() as $key => $coin ) {
				$fields[ 'addr_' . $key ] = array( 'title' => $coin['label'], 'type' => 'text', 'default' => '', 'css' => 'width:32em;font-family:monospace' );
			}
			$fields += array(
				'rules'         => array( 'title' => __( 'Rules', 'mine-mirth-commerce' ), 'type' => 'title' ),
				'discount'      => array( 'title' => __( 'Crypto discount (%)', 'mine-mirth-commerce' ), 'type' => 'number', 'default' => '0', 'description' => __( 'Optional. Pass part of the processing fee you save on to buyers, e.g. 1.', 'mine-mirth-commerce' ), 'custom_attributes' => array( 'min' => '0', 'max' => '50', 'step' => '0.1' ) ),
				'window'        => array( 'title' => __( 'Rate lock (minutes)', 'mine-mirth-commerce' ), 'type' => 'number', 'default' => '30', 'description' => __( 'How long the quoted crypto amount is valid.', 'mine-mirth-commerce' ) ),
				'auto_verify'   => array( 'title' => __( 'Automatic confirmation', 'mine-mirth-commerce' ), 'type' => 'checkbox', 'label' => __( 'Mark Bitcoin and Litecoin orders paid automatically by reading the public blockchain', 'mine-mirth-commerce' ), 'default' => 'yes' ),
				'confirmations' => array( 'title' => __( 'Wait for a confirmation', 'mine-mirth-commerce' ), 'type' => 'checkbox', 'label' => __( 'Only mark paid once the transaction is in a block (safer, about 10 minutes for Bitcoin)', 'mine-mirth-commerce' ), 'default' => 'yes' ),
				'cancel_hours'  => array( 'title' => __( 'Cancel unpaid orders after (hours)', 'mine-mirth-commerce' ), 'type' => 'number', 'default' => '48', 'description' => __( '0 keeps them on hold until you act.', 'mine-mirth-commerce' ) ),
			);
			$this->form_fields = $fields;
		}

		/**
		 * Coins with an address.
		 *
		 * @return array<string,array>
		 */
		public function enabled_coins(): array {
			$out = array();
			foreach ( mmc_crypto_coins() as $key => $coin ) {
				$addr = trim( (string) $this->get_option( 'addr_' . $key ) );
				if ( '' !== $addr ) {
					$out[ $key ] = $coin + array( 'address' => $addr );
				}
			}
			return $out;
		}

		public function is_available() {
			return parent::is_available() && (bool) $this->enabled_coins();
		}

		public function payment_fields(): void {
			if ( $this->description ) {
				echo wp_kses_post( wpautop( wptexturize( $this->description ) ) );
			}
			$coins = $this->enabled_coins();
			echo '<fieldset class="mmc-coins"><legend class="screen-reader-text">' . esc_html__( 'Choose a coin', 'mine-mirth-commerce' ) . '</legend>';
			$first = true;
			foreach ( $coins as $key => $coin ) {
				echo '<label class="mmc-coin"><input type="radio" name="mmc_coin" value="' . esc_attr( $key ) . '" ' . checked( $first, true, false ) . '> <strong>' . esc_html( $coin['symbol'] ) . '</strong> <span>' . esc_html( $coin['label'] ) . '</span></label>';
				$first = false;
			}
			echo '</fieldset>';
			$discount = (float) $this->get_option( 'discount' );
			if ( $discount > 0 ) {
				/* translators: %s: percent */
				echo '<p class="mmc-coins__note">' . esc_html( sprintf( __( '%s%% off when you pay with crypto.', 'mine-mirth-commerce' ), wc_format_localized_decimal( $discount ) ) ) . '</p>';
			}
		}

		public function validate_fields() {
			$coin  = sanitize_key( wp_unslash( $_POST['mmc_coin'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$coins = $this->enabled_coins();
			if ( $coin && ! isset( $coins[ $coin ] ) ) {
				wc_add_notice( __( 'Please choose one of the listed coins.', 'mine-mirth-commerce' ), 'error' );
				return false;
			}
			return true;
		}

		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );
			$coins = $this->enabled_coins();
			$key   = sanitize_key( wp_unslash( $_POST['mmc_coin'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! isset( $coins[ $key ] ) ) {
				$key = (string) array_key_first( $coins );
			}
			$coin  = $coins[ $key ];
			$rates = mmc_crypto_rates();
			$rate  = (float) ( $rates[ $coin['gecko'] ] ?? 0 );
			if ( $rate <= 0 ) {
				wc_add_notice( __( 'We could not get a live exchange rate just now. Please try again in a minute or choose another payment method.', 'mine-mirth-commerce' ), 'error' );
				return array( 'result' => 'failure' );
			}
			$amount = (float) $order->get_total() / $rate;
			if ( $coin['auto'] ) {
				// A few extra base units make each order's amount unique, so the blockchain check can match it.
				$amount += wp_rand( 1, 999 ) / pow( 10, $coin['decimals'] );
			}
			$amount = round( $amount, $coin['decimals'] );

			$order->update_meta_data( '_mmc_coin', $key );
			$order->update_meta_data( '_mmc_address', $coin['address'] );
			$order->update_meta_data( '_mmc_amount', mmc_crypto_amount( $amount, $coin['decimals'] ) );
			$order->update_meta_data( '_mmc_rate', $rate );
			$order->update_meta_data( '_mmc_expires', time() + max( 5, (int) $this->get_option( 'window', 30 ) ) * MINUTE_IN_SECONDS );
			$order->save();
			/* translators: 1: amount 2: symbol 3: address */
			$order->update_status( 'on-hold', sprintf( __( 'Awaiting %1$s %2$s to %3$s.', 'mine-mirth-commerce' ), mmc_crypto_amount( $amount, $coin['decimals'] ), $coin['symbol'], $coin['address'] ) );
			wc_reduce_stock_levels( $order_id );
			if ( WC()->cart ) {
				WC()->cart->empty_cart();
			}
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}
	}
}

/**
 * The gateway instance, when enabled.
 */
function mmc_crypto_gateway(): ?MMC_Gateway_Crypto {
	if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
		return null;
	}
	$all = WC()->payment_gateways()->payment_gateways();
	return isset( $all['mmc_crypto'] ) && $all['mmc_crypto'] instanceof MMC_Gateway_Crypto ? $all['mmc_crypto'] : null;
}

// Optional crypto discount, shown as a negative fee while crypto is selected.
add_action(
	'woocommerce_cart_calculate_fees',
	static function ( WC_Cart $cart ) {
		$gw = mmc_crypto_gateway();
		if ( ! $gw || 'mmc_crypto' !== WC()->session?->get( 'chosen_payment_method' ) ) {
			return;
		}
		$pct = (float) $gw->get_option( 'discount' );
		if ( $pct > 0 ) {
			$base = (float) $cart->get_subtotal() - (float) $cart->get_discount_total();
			$cart->add_fee( __( 'Crypto payment discount', 'mine-mirth-commerce' ), -round( $base * $pct / 100, wc_get_price_decimals() ), false );
		}
	}
);
add_action(
	'wp_footer',
	static function () {
		if ( ! is_checkout() || is_order_received_page() ) {
			return;
		}
		// Recalculate totals when the payment method changes (classic checkout) so the discount appears.
		echo "<script>jQuery&&jQuery(function($){\$(document.body).on('change','input[name=\"payment_method\"]',function(){\$(document.body).trigger('update_checkout');});});</script>";
	}
);

/**
 * Payment box: amount, address, QR code, countdown, transaction ID form.
 */
function mmc_crypto_payment_box( WC_Order $order ): string {
	$key   = (string) $order->get_meta( '_mmc_coin' );
	$coins = mmc_crypto_coins();
	if ( ! isset( $coins[ $key ] ) ) {
		return '';
	}
	$coin    = $coins[ $key ];
	$addr    = (string) $order->get_meta( '_mmc_address' );
	$amount  = (string) $order->get_meta( '_mmc_amount' );
	$expires = (int) $order->get_meta( '_mmc_expires' );
	$txid    = (string) $order->get_meta( '_mmc_txid' );
	$paid    = $order->is_paid();
	$uri     = $coin['scheme'] ? $coin['scheme'] . ':' . $addr . ( in_array( $key, array( 'btc', 'ltc', 'doge' ), true ) ? '?amount=' . $amount : '' ) : $addr;
	wp_enqueue_script( 'mmc-qrcode', MMC_URI . 'assets/js/qrcode.js', array(), '1.4.4', true );
	wp_enqueue_script( 'mmc-crypto', MMC_URI . 'assets/js/mmc-crypto.js', array( 'mmc-qrcode' ), MMC_VERSION, true );
	ob_start();
	?>
	<section class="mmc-pay<?php echo $paid ? ' is-paid' : ''; ?>" data-mmc-pay data-expires="<?php echo esc_attr( (string) $expires ); ?>">
		<header class="mmc-pay__head">
			<h2><?php echo $paid ? esc_html__( 'Payment received', 'mine-mirth-commerce' ) : esc_html( sprintf( /* translators: %s: coin */ __( 'Pay with %s', 'mine-mirth-commerce' ), $coin['label'] ) ); ?></h2>
			<?php if ( ! $paid ) : ?>
				<p><?php esc_html_e( 'Send the exact amount below to this address. Your order ships as soon as the payment is confirmed.', 'mine-mirth-commerce' ); ?></p>
			<?php endif; ?>
		</header>
		<?php if ( ! $paid ) : ?>
			<div class="mmc-pay__grid">
				<div class="mmc-pay__qr" data-mmc-qr="<?php echo esc_attr( $uri ); ?>" role="img" aria-label="<?php esc_attr_e( 'QR code of the payment address', 'mine-mirth-commerce' ); ?>"></div>
				<dl class="mmc-pay__rows">
					<div><dt><?php esc_html_e( 'Amount', 'mine-mirth-commerce' ); ?></dt><dd><code><?php echo esc_html( $amount . ' ' . $coin['symbol'] ); ?></code> <button type="button" class="mmc-copy" data-mmc-copy="<?php echo esc_attr( $amount ); ?>"><?php esc_html_e( 'Copy', 'mine-mirth-commerce' ); ?></button></dd></div>
					<div><dt><?php esc_html_e( 'Address', 'mine-mirth-commerce' ); ?></dt><dd><code class="mmc-pay__addr"><?php echo esc_html( $addr ); ?></code> <button type="button" class="mmc-copy" data-mmc-copy="<?php echo esc_attr( $addr ); ?>"><?php esc_html_e( 'Copy', 'mine-mirth-commerce' ); ?></button></dd></div>
					<div><dt><?php esc_html_e( 'Network', 'mine-mirth-commerce' ); ?></dt><dd><?php echo esc_html( $coin['label'] ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Order total', 'mine-mirth-commerce' ); ?></dt><dd><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></dd></div>
					<?php if ( $expires ) : ?>
						<div><dt><?php esc_html_e( 'Rate valid for', 'mine-mirth-commerce' ); ?></dt><dd data-mmc-countdown><?php echo esc_html( human_time_diff( time(), $expires ) ); ?></dd></div>
					<?php endif; ?>
				</dl>
			</div>
			<p class="mmc-pay__warn"><?php echo esc_html( sprintf( /* translators: %s: network */ __( 'Only send %s on this network. Coins sent on another network are lost.', 'mine-mirth-commerce' ), $coin['label'] ) ); ?></p>
			<form class="mmc-pay__tx" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mmc_txid">
				<input type="hidden" name="order" value="<?php echo esc_attr( (string) $order->get_id() ); ?>">
				<input type="hidden" name="key" value="<?php echo esc_attr( $order->get_order_key() ); ?>">
				<?php wp_nonce_field( 'mmc_txid_' . $order->get_id() ); ?>
				<label for="mmc-txid"><?php echo $txid ? esc_html__( 'Transaction ID received — thank you', 'mine-mirth-commerce' ) : esc_html__( 'Sent it? Paste the transaction ID (optional, speeds things up)', 'mine-mirth-commerce' ); ?></label>
				<div><input id="mmc-txid" type="text" name="txid" value="<?php echo esc_attr( $txid ); ?>" autocomplete="off" spellcheck="false"><button type="submit" class="mmc-btn mmc-btn--primary"><?php esc_html_e( 'Send', 'mine-mirth-commerce' ); ?></button></div>
			</form>
		<?php elseif ( $txid ) : ?>
			<p><a href="<?php echo esc_url( $coin['tx'] . rawurlencode( $txid ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View the transaction', 'mine-mirth-commerce' ); ?></a></p>
		<?php endif; ?>
	</section>
	<?php
	return (string) ob_get_clean();
}

add_action(
	'woocommerce_thankyou_mmc_crypto',
	static function ( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			echo mmc_crypto_payment_box( $order ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	},
	5
);
add_action(
	'woocommerce_view_order',
	static function ( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order && 'mmc_crypto' === $order->get_payment_method() ) {
			echo mmc_crypto_payment_box( $order ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	},
	5
);

// Payment details in the on-hold email.
add_action(
	'woocommerce_email_before_order_table',
	static function ( $order, $sent_to_admin, $plain_text ) {
		if ( ! $order instanceof WC_Order || 'mmc_crypto' !== $order->get_payment_method() || $order->is_paid() || $sent_to_admin ) {
			return;
		}
		$coins = mmc_crypto_coins();
		$coin  = $coins[ (string) $order->get_meta( '_mmc_coin' ) ] ?? null;
		if ( ! $coin ) {
			return;
		}
		$line1 = sprintf( /* translators: 1: amount 2: symbol */ __( 'Amount: %1$s %2$s', 'mine-mirth-commerce' ), $order->get_meta( '_mmc_amount' ), $coin['symbol'] );
		$line2 = sprintf( /* translators: %s: address */ __( 'Address: %s', 'mine-mirth-commerce' ), $order->get_meta( '_mmc_address' ) );
		$line3 = sprintf( /* translators: %s: network */ __( 'Network: %s', 'mine-mirth-commerce' ), $coin['label'] );
		$link  = $order->get_checkout_order_received_url();
		if ( $plain_text ) {
			echo esc_html( __( 'Payment details', 'mine-mirth-commerce' ) . "\n" . $line1 . "\n" . $line2 . "\n" . $line3 . "\n" . $link . "\n\n" );
		} else {
			echo '<h2>' . esc_html__( 'Payment details', 'mine-mirth-commerce' ) . '</h2><p>' . esc_html( $line1 ) . '<br>' . esc_html( $line2 ) . '<br>' . esc_html( $line3 ) . '</p><p><a href="' . esc_url( $link ) . '">' . esc_html__( 'Open the payment page (QR code)', 'mine-mirth-commerce' ) . '</a></p>';
		}
	},
	5,
	3
);

// Customer submits a transaction ID.
$mmc_txid_handler = static function () {
	$order_id = absint( wp_unslash( $_POST['order'] ?? 0 ) );
	$order    = wc_get_order( $order_id );
	$key      = sanitize_text_field( wp_unslash( $_POST['key'] ?? '' ) );
	if ( ! $order || ! hash_equals( $order->get_order_key(), $key ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'mmc_txid_' . $order_id ) ) {
		wp_die( esc_html__( 'This link has expired. Please open your order again.', 'mine-mirth-commerce' ) );
	}
	$txid = preg_replace( '/[^A-Za-z0-9]/', '', (string) wp_unslash( $_POST['txid'] ?? '' ) );
	if ( $txid && strlen( $txid ) >= 20 && strlen( $txid ) <= 128 && ! $order->is_paid() ) {
		$order->update_meta_data( '_mmc_txid', $txid );
		$order->save();
		/* translators: %s: transaction id */
		$order->add_order_note( sprintf( __( 'Customer sent transaction ID %s. Check it and mark the order processing once confirmed.', 'mine-mirth-commerce' ), $txid ) );
		wp_mail( get_option( 'admin_email' ), sprintf( /* translators: %s: order number */ __( 'Crypto payment sent for order #%s', 'mine-mirth-commerce' ), $order->get_order_number() ), sprintf( "%s\n%s", $txid, $order->get_edit_order_url() ) );
	}
	wp_safe_redirect( $order->get_checkout_order_received_url() );
	exit;
};
add_action( 'admin_post_mmc_txid', $mmc_txid_handler );
add_action( 'admin_post_nopriv_mmc_txid', $mmc_txid_handler );

// Payment details on the admin order screen.
add_action(
	'woocommerce_admin_order_data_after_billing_address',
	static function ( $order ) {
		if ( ! $order instanceof WC_Order || 'mmc_crypto' !== $order->get_payment_method() ) {
			return;
		}
		$coins = mmc_crypto_coins();
		$coin  = $coins[ (string) $order->get_meta( '_mmc_coin' ) ] ?? null;
		if ( ! $coin ) {
			return;
		}
		$txid = (string) $order->get_meta( '_mmc_txid' );
		echo '<div class="mmc-admin-pay" style="margin-top:12px;padding:10px 12px;border:1px solid #dcdcde;border-radius:6px;background:#f6f7f7"><strong>' . esc_html__( 'Crypto payment', 'mine-mirth-commerce' ) . '</strong><br>';
		echo esc_html( $order->get_meta( '_mmc_amount' ) . ' ' . $coin['symbol'] . ' · ' . $coin['label'] ) . '<br><code style="word-break:break-all">' . esc_html( (string) $order->get_meta( '_mmc_address' ) ) . '</code><br>';
		echo $txid ? '<a href="' . esc_url( $coin['tx'] . rawurlencode( $txid ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Transaction', 'mine-mirth-commerce' ) . ' ' . esc_html( substr( $txid, 0, 12 ) ) . '…</a>' : esc_html__( 'No transaction ID yet.', 'mine-mirth-commerce' );
		echo '<br><small>' . esc_html__( 'Check the payment in your wallet, then set the status to Processing.', 'mine-mirth-commerce' ) . '</small></div>';
	}
);

/**
 * Check the blockchain for a payment to the order's address (BTC, LTC).
 */
function mmc_crypto_check_order( WC_Order $order, MMC_Gateway_Crypto $gw ): void {
	$coins = mmc_crypto_coins();
	$coin  = $coins[ (string) $order->get_meta( '_mmc_coin' ) ] ?? null;
	if ( ! $coin || ! $coin['auto'] ) {
		return;
	}
	$addr = (string) $order->get_meta( '_mmc_address' );
	$want = (int) round( (float) $order->get_meta( '_mmc_amount' ) * 1e8 );
	$res  = wp_remote_get( $coin['auto'] . '/address/' . rawurlencode( $addr ) . '/txs', array( 'timeout' => 15 ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return;
	}
	$txs     = json_decode( (string) wp_remote_retrieve_body( $res ), true );
	$created = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0;
	$confirm = 'yes' === $gw->get_option( 'confirmations', 'yes' );
	foreach ( (array) $txs as $tx ) {
		$confirmed = ! empty( $tx['status']['confirmed'] );
		$time      = (int) ( $tx['status']['block_time'] ?? time() );
		if ( ( $confirm && ! $confirmed ) || ( $confirmed && $time < $created - 600 ) ) {
			continue;
		}
		foreach ( (array) ( $tx['vout'] ?? array() ) as $out ) {
			if ( ( $out['scriptpubkey_address'] ?? '' ) === $addr && (int) ( $out['value'] ?? 0 ) === $want ) {
				$order->update_meta_data( '_mmc_txid', (string) $tx['txid'] );
				$order->save();
				$order->payment_complete( (string) $tx['txid'] );
				/* translators: %s: transaction id */
				$order->add_order_note( sprintf( __( 'Crypto payment found on the blockchain: %s', 'mine-mirth-commerce' ), $tx['txid'] ) );
				return;
			}
		}
	}
}

add_action(
	'mmc_cron',
	static function () {
		$gw = mmc_crypto_gateway();
		if ( ! $gw || 'yes' !== $gw->enabled ) {
			return;
		}
		$orders = wc_get_orders(
			array(
				'status'         => array( 'on-hold' ),
				'payment_method' => 'mmc_crypto',
				'limit'          => 50,
				'date_created'   => '>' . ( time() - 7 * DAY_IN_SECONDS ),
			)
		);
		$cancel = (int) $gw->get_option( 'cancel_hours', 48 );
		foreach ( $orders as $order ) {
			if ( 'yes' === $gw->get_option( 'auto_verify', 'yes' ) ) {
				mmc_crypto_check_order( $order, $gw );
			}
			if ( ! $order->is_paid() && $cancel > 0 && ! $order->get_meta( '_mmc_txid' ) && $order->get_date_created() && $order->get_date_created()->getTimestamp() < time() - $cancel * HOUR_IN_SECONDS ) {
				$order->update_status( 'cancelled', __( 'No crypto payment received in time.', 'mine-mirth-commerce' ) );
			}
		}
	}
);
