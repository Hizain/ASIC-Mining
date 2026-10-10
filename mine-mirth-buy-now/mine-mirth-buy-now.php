<?php
/**
 * Plugin Name:       Mine Mirth Buy Now & Price Alerts
 * Description:       Adds a "Buy it now" button that goes straight to checkout, and a "Get price alerts" sign-up on every product page. When you change a product's price, everyone watching it gets an email in your newsletter design. Works with the Mine Mirth theme and the Mine Mirth Mailer.
 * Version:           1.0.4
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-buy-now
 * WC requires at least: 7.0
 *
 * @package MineMirthBuyNow
 */

defined( 'ABSPATH' ) || exit;

define( 'MMBN_VERSION', '1.0.4' );
define( 'MMBN_FILE', __FILE__ );
define( 'MMBN_URI', plugin_dir_url( __FILE__ ) );
define( 'MMBN_DB', 3 );

function mmbn_defaults(): array {
	return array(
		'buy_now'     => 1,
		'buy_label'   => __( 'Buy it now', 'mine-mirth-buy-now' ),
		'pre_label'   => __( 'Pre-order now', 'mine-mirth-buy-now' ),
		'buy_style'   => 'contrast',
		'buy_solo'    => 0,
		'alerts'      => 1,
		'alert_title' => __( 'Get price alerts', 'mine-mirth-buy-now' ),
		'alert_text'  => __( 'We’ll email you as soon as this price changes.', 'mine-mirth-buy-now' ),
		'alert_btn'   => __( 'Watch price', 'mine-mirth-buy-now' ),
		'consent'     => __( 'You’ll also get our weekly newsletter. Unsubscribe anytime.', 'mine-mirth-buy-now' ),
		'notify_down' => 1,
		'notify_up'   => 1,
		'delay'       => 0,
		'show_count'  => 3,
		'purge'       => 1,
		'purge_scope' => 'all',
		'track'       => 0,
		'copy_to'     => '',
		'timezone'    => 'Asia/Karachi',
	);
}

function mmbn_opt( string $key ) {
	static $o = null;
	if ( null === $o ) {
		$saved = get_option( 'mmbn_settings', array() );
		$o     = array_merge( mmbn_defaults(), is_array( $saved ) ? $saved : array() );
	}
	return $o[ $key ] ?? null;
}

/**
 * Time zone for dates shown by this plugin (Pakistan time by default).
 */
function mmbn_tz(): DateTimeZone {
	try {
		return new DateTimeZone( (string) mmbn_opt( 'timezone' ) );
	} catch ( Exception $e ) {
		return wp_timezone();
	}
}

function mmbn_date( int $ts, string $format = '' ): string {
	$format = $format ? $format : get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	return wp_date( $format, $ts, mmbn_tz() ) . ' ' . wp_date( 'T', $ts, mmbn_tz() );
}

/**
 * Theme icon when available (keeps buttons identical to the theme's), else a small inline SVG.
 */
function mmbn_icon( string $name ): string {
	if ( 'bell' === $name ) {
		return '<svg class="mm-icon mmbn-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>';
	}
	if ( function_exists( 'mm_icon' ) ) {
		return (string) mm_icon( $name );
	}
	return 'bolt' === $name ? '<svg class="mm-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 9-12h-7z"/></svg>' : '';
}

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		require_once __DIR__ . '/includes/buy-now.php';
		require_once __DIR__ . '/includes/alerts.php';
		require_once __DIR__ . '/includes/admin.php';
	}
);

register_activation_hook(
	__FILE__,
	static function () {
		require_once __DIR__ . '/includes/alerts.php';
		mmbn_install();
	}
);

// Declare HPOS compatibility (this plugin doesn't touch order storage).
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
