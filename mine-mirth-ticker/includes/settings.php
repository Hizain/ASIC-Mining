<?php
/**
 * Settings (one option) and the coin list.
 *
 * @package MineMirthTicker
 */

defined( 'ABSPATH' ) || exit;

/** Coins shown by default, in this order (WhatToMine names). */
function mmtk_default_coins(): array {
	return array( 'Bitcoin', 'BitcoinCash', 'Litecoin', 'Dogecoin', 'Kaspa', 'Alephium', 'EthereumClassic', 'Zcash', 'Dash', 'Kadena', 'Handshake', 'Nervos', 'Monero' );
}

function mmtk_contact_url(): string {
	$url = function_exists( 'mm_opt' ) ? (string) mm_opt( 'contact_url' ) : '';
	if ( function_exists( 'mm_url' ) && '' !== $url ) {
		return (string) mm_url( $url );
	}
	return home_url( '' !== $url ? $url : '/contact-us/' );
}

function mmtk_defaults(): array {
	$coins = array();
	foreach ( mmtk_default_coins() as $name ) {
		$coins[ $name ] = array( 'on' => 1, 'label' => '', 'unit' => 'auto' );
	}
	return array(
		'enabled'      => 1,
		'placement'    => 'replace',
		'pages'        => 'all',
		'hide_checkout'=> 1,
		'mobile'       => 1,
		'speed'        => 55,
		'pause'        => 1,
		'size'         => 'md',
		'bg'           => '#050b14',
		'text'         => '#eaf3fc',
		'muted'        => '#9db3ca',
		'accent'       => '#35a8f6',
		'up'           => '#19d38b',
		'down'         => '#ff6b6b',
		'msg_bg'       => '#ffc53d',
		'msg_text'     => '#0b1220',
		'separator'    => '•',
		'currency'     => 'theme',
		'refresh'      => 60,
		'change'       => 1,
		'dismiss'      => 0,
		'btc_label'    => __( 'Bitcoin', 'mine-mirth-ticker' ),
		'hp_label'     => __( '{name} hashprice', 'mine-mirth-ticker' ),
		'repeat_every' => 0,
		'coins'        => $coins,
		'items'        => array(
			array( 'type' => 'btc', 'on' => 1 ),
			array( 'type' => 'coins', 'on' => 1 ),
			array(
				'type'      => 'text',
				'on'        => 1,
				'icon'      => '☀️',
				'text'      => __( 'Install Solar, Wind or Hydro Energy For Long Time Gains.', 'mine-mirth-ticker' ),
				'link_text' => __( 'Consult with us!!!', 'mine-mirth-ticker' ),
				'link'      => '',
				'style'     => 'highlight',
				'repeat'    => 1,
			),
		),
	);
}

function mmtk_settings( bool $fresh = false ): array {
	static $s = null;
	if ( null === $s || $fresh ) {
		$saved = get_option( 'mmtk_settings', array() );
		$s     = wp_parse_args( is_array( $saved ) ? $saved : array(), mmtk_defaults() );
	}
	return $s;
}

function mmtk_opt( string $key ) {
	return mmtk_settings()[ $key ] ?? null;
}

/** Coins switched on, in display order: name => [on, label, unit]. */
function mmtk_selected_coins(): array {
	return array_filter( (array) mmtk_opt( 'coins' ), static fn( $c ) => ! empty( $c['on'] ) );
}

/** Brand colours for the coin badges (fallback: the accent colour). */
function mmtk_coin_color( string $tag ): string {
	return array(
		'BTC'  => '#f7931a',
		'BCH'  => '#8dc351',
		'LTC'  => '#5d8bd4',
		'DOGE' => '#c2a633',
		'KAS'  => '#49eacb',
		'ALPH' => '#d9d9d9',
		'ETC'  => '#3ab83a',
		'ZEC'  => '#f4b728',
		'DASH' => '#1c8ce4',
		'KDA'  => '#ed098f',
		'HNS'  => '#e8e8e8',
		'CKB'  => '#3cc68a',
		'XMR'  => '#ff6600',
		'DGB'  => '#2a7ad0',
		'XEC'  => '#0074c2',
	)[ strtoupper( $tag ) ] ?? (string) mmtk_opt( 'accent' );
}
