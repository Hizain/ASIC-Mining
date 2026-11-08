<?php
/**
 * The bar: markup, placement and assets.
 *
 * @package MineMirthTicker
 */

defined( 'ABSPATH' ) || exit;

function mmtk_should_show(): bool {
	$s = mmtk_settings();
	if ( ! $s['enabled'] || is_admin() || is_feed() || is_embed() || wp_doing_ajax() ) {
		return false;
	}
	if ( 'home' === $s['pages'] && ! is_front_page() ) {
		return false;
	}
	if ( $s['hide_checkout'] && function_exists( 'is_checkout' ) && ( is_checkout() || is_cart() ) ) {
		return false;
	}
	return (bool) apply_filters( 'mmtk_show', true );
}

/** One item of the bar. */
function mmtk_li( string $class, string $inner, string $style = '' ): string {
	return '<li class="mmtk__item ' . esc_attr( $class ) . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>' . $inner . '</li>';
}

function mmtk_change_html( string $key, array $v ): string {
	if ( ! mmtk_opt( 'change' ) ) {
		return '';
	}
	return '<span class="mmtk__chg' . ( $v['d'] ? ' is-' . esc_attr( $v['d'] ) : '' ) . '" data-mmtk-c="' . esc_attr( $key ) . '">' . esc_html( $v['c'] ) . '</span>';
}

function mmtk_text_item( array $it ): string {
	$text = trim( (string) ( $it['text'] ?? '' ) );
	$lt   = trim( (string) ( $it['link_text'] ?? '' ) );
	$url  = trim( (string) ( $it['link'] ?? '' ) );
	if ( '' === $text && '' === $lt ) {
		return '';
	}
	$url   = '' !== $url ? ( str_starts_with( $url, '/' ) ? home_url( $url ) : $url ) : ( '' !== $lt ? mmtk_contact_url() : '' );
	$icon  = trim( (string) ( $it['icon'] ?? '' ) );
	$inner = ( '' !== $icon ? '<span class="mmtk__icon" aria-hidden="true">' . esc_html( $icon ) . '</span>' : '' );
	if ( '' !== $lt ) {
		$inner .= '<span class="mmtk__msg">' . esc_html( $text ) . '</span> <a class="mmtk__link" href="' . esc_url( $url ) . '">' . esc_html( $lt ) . '</a>';
	} elseif ( '' !== $url ) {
		$inner .= '<a class="mmtk__msg mmtk__link--whole" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
	} else {
		$inner .= '<span class="mmtk__msg">' . esc_html( $text ) . '</span>';
	}
	return mmtk_li( 'mmtk__item--msg mmtk__item--' . ( 'plain' === ( $it['style'] ?? '' ) ? 'plain' : 'highlight' ), $inner );
}

/** All items in order, as <li> strings. */
function mmtk_items_html(): array {
	$s       = mmtk_settings();
	$data    = mmtk_data();
	$payload = mmtk_payload( $data );
	$out     = array();
	$repeat  = array();
	foreach ( (array) $s['items'] as $it ) {
		if ( ! empty( $it['on'] ) && 'text' === ( $it['type'] ?? '' ) && ! empty( $it['repeat'] ) ) {
			$repeat[] = mmtk_text_item( $it );
		}
	}
	$repeat = array_filter( $repeat );
	foreach ( (array) $s['items'] as $it ) {
		if ( empty( $it['on'] ) ) {
			continue;
		}
		switch ( $it['type'] ?? '' ) {
			case 'btc':
				if ( empty( $payload['btc'] ) ) {
					break;
				}
				$label = trim( (string) ( $it['label'] ?? '' ) );
				$label = '' !== $label ? $label : (string) $s['btc_label'];
				$out[] = mmtk_li(
					'mmtk__item--btc',
					'<span class="mmtk__badge" style="--c:#f7931a">₿</span><span class="mmtk__label">' . esc_html( $label ) . '</span> <strong class="mmtk__val" data-mmtk-v="btc">' . esc_html( $payload['btc']['v'] ) . '</strong>' . mmtk_change_html( 'btc', $payload['btc'] )
				);
				break;
			case 'coins':
				$n = 0;
				foreach ( mmtk_selected_coins() as $key => $conf ) {
					if ( empty( $payload[ 'c:' . $key ] ) ) {
						continue;
					}
					$c     = $data['coins'][ $key ];
					$name  = trim( (string) ( $conf['label'] ?? '' ) );
					$name  = '' !== $name ? $name : mmtk_pretty_name( $key );
					$label = str_replace( array( '{name}', '{tag}' ), array( $name, $c['tag'] ), (string) $s['hp_label'] );
					$v     = $payload[ 'c:' . $key ];
					$out[] = mmtk_li(
						'mmtk__item--coin',
						'<span class="mmtk__badge" style="--c:' . esc_attr( mmtk_coin_color( $c['tag'] ) ) . '">' . esc_html( $c['tag'] ) . '</span><span class="mmtk__label">' . esc_html( $label ) . '</span> <strong class="mmtk__val" data-mmtk-v="' . esc_attr( 'c:' . $key ) . '">' . esc_html( $v['v'] ) . '</strong>' . mmtk_change_html( 'c:' . $key, $v )
					);
					++$n;
					if ( (int) $s['repeat_every'] > 0 && $repeat && 0 === $n % (int) $s['repeat_every'] ) {
						array_push( $out, ...$repeat );
					}
				}
				break;
			case 'text':
				$html = mmtk_text_item( $it );
				if ( '' !== $html ) {
					$out[] = $html;
				}
				break;
		}
	}
	return $out;
}

/** "BitcoinCash" → "Bitcoin Cash", "EthereumClassic" → "Ethereum Classic", "DGB-SHA" stays. */
function mmtk_pretty_name( string $key ): string {
	return trim( (string) preg_replace( '/(?<=[a-z]{2})(?=[A-Z])/', ' ', $key ) );
}

function mmtk_bar_html( bool $preview = false ): string {
	$s     = mmtk_settings();
	$items = mmtk_items_html();
	if ( ! $items ) {
		return '';
	}
	$sep   = trim( (string) $s['separator'] );
	$glue  = '' !== $sep ? '<li class="mmtk__sep" aria-hidden="true">' . esc_html( $sep ) . '</li>' : '';
	$list  = implode( $glue, $items ) . $glue;
	$style = sprintf(
		'--mmtk-bg:%s;--mmtk-text:%s;--mmtk-muted:%s;--mmtk-accent:%s;--mmtk-up:%s;--mmtk-down:%s;--mmtk-msg-bg:%s;--mmtk-msg-text:%s',
		$s['bg'],
		$s['text'],
		$s['muted'],
		$s['accent'],
		$s['up'],
		$s['down'],
		$s['msg_bg'],
		$s['msg_text']
	);
	$classes = 'mmtk mmtk--' . $s['size'] . ' mmtk--' . ( $preview ? 'preview' : $s['placement'] ) . ( $s['pause'] ? ' mmtk--pause' : '' ) . ( $s['mobile'] ? '' : ' mmtk--no-mobile' );
	return '<div class="' . esc_attr( $classes ) . '" style="' . esc_attr( $style ) . '" data-mmtk data-speed="' . (int) $s['speed'] . '" data-refresh="' . (int) max( 30, (int) $s['refresh'] ) . '" data-endpoint="' . esc_url( rest_url( 'mine-mirth-ticker/v1/data' ) ) . '" role="region" aria-label="' . esc_attr__( 'Live mining market ticker', 'mine-mirth-ticker' ) . '">'
		. '<div class="mmtk__viewport"><div class="mmtk__track"><ul class="mmtk__group">' . $list . '</ul></div></div>'
		. ( $s['dismiss'] && ! $preview ? '<button type="button" class="mmtk__close" data-mmtk-close aria-label="' . esc_attr__( 'Close', 'mine-mirth-ticker' ) . '">×</button>' : '' )
		. '</div>';
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! mmtk_should_show() ) {
			return;
		}
		wp_enqueue_style( 'mmtk', MMTK_URI . 'assets/ticker.css', array(), MMTK_VERSION );
		wp_enqueue_script( 'mmtk', MMTK_URI . 'assets/ticker.js', array(), MMTK_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		if ( 'replace' === mmtk_opt( 'placement' ) ) {
			// The theme's own announcement bar makes way for the ticker.
			wp_add_inline_style( 'mmtk', '#mm-announcement{display:none!important}' );
		}
	}
);

// Top of the page: straight after <body>, above the theme's header.
add_action(
	'wp_body_open',
	static function (): void {
		if ( mmtk_should_show() && 'bottom' !== mmtk_opt( 'placement' ) ) {
			echo mmtk_bar_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in mmtk_bar_html().
		}
	},
	5
);

// Bottom of the screen (fixed).
add_action(
	'wp_footer',
	static function (): void {
		if ( mmtk_should_show() && 'bottom' === mmtk_opt( 'placement' ) ) {
			echo mmtk_bar_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in mmtk_bar_html().
		}
	},
	5
);

/** Shortcode [mm_ticker] to place the bar inside any page. */
add_shortcode(
	'mm_ticker',
	static function (): string {
		wp_enqueue_style( 'mmtk', MMTK_URI . 'assets/ticker.css', array(), MMTK_VERSION );
		wp_enqueue_script( 'mmtk', MMTK_URI . 'assets/ticker.js', array(), MMTK_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		return mmtk_bar_html( true );
	}
);
