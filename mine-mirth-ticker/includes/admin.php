<?php
/**
 * Settings page: Appearance › Ticker bar.
 *
 * @package MineMirthTicker
 */

defined( 'ABSPATH' ) || exit;

function mmtk_cap(): string {
	return (string) apply_filters( 'mmtk_capability', 'edit_theme_options' );
}

add_action(
	'admin_menu',
	static function (): void {
		add_theme_page( __( 'Ticker bar', 'mine-mirth-ticker' ), __( 'Ticker bar', 'mine-mirth-ticker' ), mmtk_cap(), 'mmtk', 'mmtk_page' );
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( MMTK_FILE ),
	static function ( array $links ): array {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'themes.php?page=mmtk' ) ) . '">' . esc_html__( 'Settings', 'mine-mirth-ticker' ) . '</a>' );
		return $links;
	}
);

add_action(
	'admin_enqueue_scripts',
	static function ( string $hook ): void {
		if ( 'appearance_page_mmtk' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'mmtk', MMTK_URI . 'assets/ticker.css', array(), MMTK_VERSION );
		wp_enqueue_script( 'mmtk', MMTK_URI . 'assets/ticker.js', array(), MMTK_VERSION, true );
		wp_enqueue_script( 'mmtk-admin', MMTK_URI . 'assets/admin.js', array(), MMTK_VERSION, true );
	}
);

function mmtk_hex( $v, string $fallback ): string {
	$v = sanitize_hex_color( (string) $v );
	return $v ? $v : $fallback;
}

add_action(
	'admin_post_mmtk_save',
	static function (): void {
		if ( ! current_user_can( mmtk_cap() ) || ! check_admin_referer( 'mmtk_save' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'mine-mirth-ticker' ), 403 );
		}
		if ( ! empty( $_POST['mmtk_reset'] ) ) {
			delete_option( 'mmtk_settings' );
			wp_safe_redirect( admin_url( 'themes.php?page=mmtk&saved=reset' ) );
			exit;
		}
		$in = (array) wp_unslash( $_POST['mmtk'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per field below.
		$d  = mmtk_defaults();
		$s  = mmtk_settings( true );

		foreach ( array( 'enabled', 'pause', 'hide_checkout', 'mobile', 'dismiss', 'change' ) as $k ) {
			$s[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
		}
		$s['placement']    = in_array( $in['placement'] ?? '', array( 'replace', 'above', 'bottom' ), true ) ? $in['placement'] : 'replace';
		$s['pages']        = in_array( $in['pages'] ?? '', array( 'all', 'home' ), true ) ? $in['pages'] : 'all';
		$s['size']         = in_array( $in['size'] ?? '', array( 'sm', 'md', 'lg' ), true ) ? $in['size'] : 'md';
		$s['currency']     = in_array( $in['currency'] ?? '', array( 'theme', 'usd', 'eur', 'gbp', 'cad', 'aud' ), true ) ? $in['currency'] : 'theme';
		$s['speed']        = max( 10, min( 300, absint( $in['speed'] ?? 55 ) ) );
		$s['refresh']      = max( 30, min( 3600, absint( $in['refresh'] ?? 60 ) ) );
		$s['repeat_every'] = min( 50, absint( $in['repeat_every'] ?? 0 ) );
		$s['separator']    = mb_substr( sanitize_text_field( (string) ( $in['separator'] ?? '' ) ), 0, 3 );
		$s['btc_label']    = sanitize_text_field( (string) ( $in['btc_label'] ?? '' ) );
		$s['hp_label']     = sanitize_text_field( (string) ( $in['hp_label'] ?? '' ) );
		foreach ( array( 'bg', 'text', 'muted', 'accent', 'up', 'down', 'msg_bg', 'msg_text' ) as $k ) {
			$s[ $k ] = mmtk_hex( $in[ $k ] ?? '', $d[ $k ] );
		}

		// Items, in the order they were posted.
		$items = array();
		foreach ( (array) ( $in['items'] ?? array() ) as $it ) {
			$type = in_array( $it['type'] ?? '', array( 'btc', 'coins', 'text' ), true ) ? $it['type'] : '';
			if ( '' === $type ) {
				continue;
			}
			$row = array( 'type' => $type, 'on' => empty( $it['on'] ) ? 0 : 1 );
			if ( 'btc' === $type ) {
				$row['label'] = sanitize_text_field( (string) ( $it['label'] ?? '' ) );
			}
			if ( 'text' === $type ) {
				$row['icon']      = mb_substr( sanitize_text_field( (string) ( $it['icon'] ?? '' ) ), 0, 8 );
				$row['text']      = sanitize_text_field( (string) ( $it['text'] ?? '' ) );
				$row['link_text'] = sanitize_text_field( (string) ( $it['link_text'] ?? '' ) );
				$link             = trim( (string) ( $it['link'] ?? '' ) );
				$row['link']      = str_starts_with( $link, '/' ) ? sanitize_text_field( $link ) : esc_url_raw( $link );
				$row['style']     = 'plain' === ( $it['style'] ?? '' ) ? 'plain' : 'highlight';
				$row['repeat']    = empty( $it['repeat'] ) ? 0 : 1;
				if ( '' === $row['text'] && '' === $row['link_text'] ) {
					continue;
				}
			}
			$items[] = $row;
		}
		$s['items'] = $items;

		// Coins, sorted by the order numbers.
		$coins = array();
		$rows  = (array) ( $in['coins'] ?? array() );
		uasort( $rows, static fn( $a, $b ) => ( (int) ( $a['order'] ?? 999 ) ) <=> ( (int) ( $b['order'] ?? 999 ) ) );
		foreach ( $rows as $name => $c ) {
			$name = sanitize_text_field( (string) $name );
			$unit = (string) ( $c['unit'] ?? 'auto' );
			if ( empty( $c['on'] ) && '' === trim( (string) ( $c['label'] ?? '' ) ) && 'auto' === $unit ) {
				continue; // Nothing to remember for a coin that is off and untouched.
			}
			$coins[ $name ] = array(
				'on'    => empty( $c['on'] ) ? 0 : 1,
				'label' => sanitize_text_field( (string) ( $c['label'] ?? '' ) ),
				'unit'  => 'auto' === $unit || isset( mmtk_units()[ $unit ] ) ? $unit : 'auto',
			);
		}
		$s['coins'] = $coins;

		update_option( 'mmtk_settings', $s, true );
		wp_safe_redirect( admin_url( 'themes.php?page=mmtk&saved=1' ) );
		exit;
	}
);

/** One row of the items list (also used as the template for new rows). */
function mmtk_item_row( array $it, string $i ): string {
	$n     = 'mmtk[items][' . $i . ']';
	$type  = (string) ( $it['type'] ?? 'text' );
	$names = array(
		'btc'   => __( 'Bitcoin live price', 'mine-mirth-ticker' ),
		'coins' => __( 'Hashprices (the coins ticked below, one by one)', 'mine-mirth-ticker' ),
		'text'  => __( 'Message', 'mine-mirth-ticker' ),
	);
	ob_start();
	?>
	<div class="mmtk-row" data-mmtk-row>
		<input type="hidden" name="<?php echo esc_attr( $n ); ?>[type]" value="<?php echo esc_attr( $type ); ?>">
		<div class="mmtk-row__head">
			<label class="mmtk-row__on"><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[on]" value="1" <?php checked( ! empty( $it['on'] ) ); ?>> <strong><?php echo esc_html( $names[ $type ] ?? $type ); ?></strong></label>
			<span class="mmtk-row__tools">
				<button type="button" class="button button-small" data-mmtk-up aria-label="<?php esc_attr_e( 'Move up', 'mine-mirth-ticker' ); ?>">↑</button>
				<button type="button" class="button button-small" data-mmtk-down aria-label="<?php esc_attr_e( 'Move down', 'mine-mirth-ticker' ); ?>">↓</button>
				<button type="button" class="button button-small button-link-delete" data-mmtk-remove><?php esc_html_e( 'Remove', 'mine-mirth-ticker' ); ?></button>
			</span>
		</div>
		<?php if ( 'btc' === $type ) : ?>
			<div class="mmtk-row__fields"><label><?php esc_html_e( 'Label', 'mine-mirth-ticker' ); ?> <input type="text" name="<?php echo esc_attr( $n ); ?>[label]" value="<?php echo esc_attr( (string) ( $it['label'] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( (string) mmtk_opt( 'btc_label' ) ); ?>"></label></div>
		<?php elseif ( 'text' === $type ) : ?>
			<div class="mmtk-row__fields">
				<label class="mmtk-w-icon"><?php esc_html_e( 'Icon', 'mine-mirth-ticker' ); ?> <input type="text" name="<?php echo esc_attr( $n ); ?>[icon]" value="<?php echo esc_attr( (string) ( $it['icon'] ?? '' ) ); ?>" placeholder="☀️"></label>
				<label class="mmtk-w-text"><?php esc_html_e( 'Text', 'mine-mirth-ticker' ); ?> <input type="text" name="<?php echo esc_attr( $n ); ?>[text]" value="<?php echo esc_attr( (string) ( $it['text'] ?? '' ) ); ?>"></label>
				<label><?php esc_html_e( 'Link text', 'mine-mirth-ticker' ); ?> <input type="text" name="<?php echo esc_attr( $n ); ?>[link_text]" value="<?php echo esc_attr( (string) ( $it['link_text'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'optional', 'mine-mirth-ticker' ); ?>"></label>
				<label><?php esc_html_e( 'Link', 'mine-mirth-ticker' ); ?> <input type="text" name="<?php echo esc_attr( $n ); ?>[link]" value="<?php echo esc_attr( (string) ( $it['link'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'contact page', 'mine-mirth-ticker' ); ?>"></label>
				<label><?php esc_html_e( 'Style', 'mine-mirth-ticker' ); ?>
					<select name="<?php echo esc_attr( $n ); ?>[style]">
						<option value="highlight" <?php selected( $it['style'] ?? 'highlight', 'highlight' ); ?>><?php esc_html_e( 'Highlighted', 'mine-mirth-ticker' ); ?></option>
						<option value="plain" <?php selected( $it['style'] ?? '', 'plain' ); ?>><?php esc_html_e( 'Plain', 'mine-mirth-ticker' ); ?></option>
					</select>
				</label>
				<label class="mmtk-inline"><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[repeat]" value="1" <?php checked( ! empty( $it['repeat'] ) ); ?>> <?php esc_html_e( 'Also repeat between coins', 'mine-mirth-ticker' ); ?></label>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

function mmtk_page(): void {
	if ( ! current_user_can( mmtk_cap() ) ) {
		return;
	}
	$s     = mmtk_settings( true );
	$data  = mmtk_data( true );
	$btc   = mmtk_btc_price( $data );
	$feed  = (array) $data['coins'];
	// Configured coins first (in your order), then every other coin in the feed by market size.
	$rows  = array();
	foreach ( (array) $s['coins'] as $name => $conf ) {
		$rows[ $name ] = $conf;
	}
	$rest = array_diff_key( $feed, $rows );
	uasort( $rest, static fn( $a, $b ) => $b['cap'] <=> $a['cap'] );
	foreach ( $rest as $name => $c ) {
		$rows[ $name ] = array( 'on' => 0, 'label' => '', 'unit' => 'auto' );
	}
	$color = static fn( string $k, string $label ) => '<label class="mmtk-color"><input type="color" name="mmtk[' . esc_attr( $k ) . ']" value="' . esc_attr( (string) $s[ $k ] ) . '"> ' . esc_html( $label ) . '</label>';
	$chk   = static fn( string $k, string $label ) => '<label class="mmtk-inline"><input type="checkbox" name="mmtk[' . esc_attr( $k ) . ']" value="1" ' . checked( ! empty( $s[ $k ] ), true, false ) . '> ' . esc_html( $label ) . '</label>';
	?>
	<div class="wrap mmtk-admin">
		<style>
			.mmtk-admin .mmtk-card { max-width: 1180px; margin: 18px 0; padding: 4px 22px 16px; background: #fff; border: 1px solid #dcdcde; border-radius: 12px; }
			.mmtk-admin .mmtk-card h2 { margin: 18px 0 6px; }
			.mmtk-preview { max-width: 1180px; margin: 14px 0; border-radius: 10px; overflow: hidden; }
			.mmtk-row { margin: 10px 0; padding: 10px 14px; border: 1px solid #dcdcde; border-radius: 10px; background: #f9fafb; }
			.mmtk-row__head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
			.mmtk-row__fields { display: flex; flex-wrap: wrap; gap: 8px 14px; margin-top: 10px; }
			.mmtk-row__fields label { display: flex; flex-direction: column; gap: 4px; font-size: 12px; color: #50575e; }
			.mmtk-row__fields label.mmtk-inline { flex-direction: row; align-items: center; align-self: flex-end; padding-bottom: 6px; }
			.mmtk-w-icon input { width: 70px; }
			.mmtk-w-text { flex: 1 1 360px; }
			.mmtk-w-text input { width: 100%; }
			.mmtk-inline { display: inline-flex; align-items: center; gap: 6px; margin: 0 18px 8px 0; }
			.mmtk-color { display: inline-flex; align-items: center; gap: 6px; margin: 0 18px 8px 0; }
			.mmtk-color input { width: 42px; height: 30px; padding: 0; border: 1px solid #c3c4c7; border-radius: 6px; }
			.mmtk-coins { width: 100%; border-collapse: collapse; }
			.mmtk-coins th, .mmtk-coins td { padding: 7px 8px; border-bottom: 1px solid #f0f0f1; text-align: left; }
			.mmtk-coins thead th { border-bottom: 2px solid #dcdcde; }
			.mmtk-coins .small-text { width: 56px; }
			.mmtk-coins tr.is-off td:not(:first-child) { opacity: .6; }
			.mmtk-tag { display: inline-block; min-width: 38px; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 11px; text-align: center; color: #1d2327; background: #f0f0f1; }
			.mmtk-coins-wrap { max-height: 560px; overflow: auto; border: 1px solid #dcdcde; border-radius: 8px; }
			.mmtk-coins thead th { position: sticky; top: 0; background: #fff; z-index: 1; }
		</style>
		<h1><?php esc_html_e( 'Ticker bar', 'mine-mirth-ticker' ); ?></h1>
		<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><?php echo 'reset' === $_GET['saved'] ? esc_html__( 'Back to the original settings.', 'mine-mirth-ticker' ) : esc_html__( 'Saved. The preview below shows the bar as visitors will see it.', 'mine-mirth-ticker' ); // phpcs:ignore WordPress.Security.NonceVerification ?></p></div>
		<?php endif; ?>
		<?php if ( ! $feed && ! $btc ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Live data could not be loaded yet (CoinGecko and WhatToMine). The bar shows your messages until it can; this page tries again each time it opens.', 'mine-mirth-ticker' ); ?></p></div>
		<?php endif; ?>

		<h2 class="title"><?php esc_html_e( 'Preview', 'mine-mirth-ticker' ); ?></h2>
		<div class="mmtk-preview"><?php echo mmtk_bar_html( true ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="mmtk-form">
			<input type="hidden" name="action" value="mmtk_save">
			<?php wp_nonce_field( 'mmtk_save' ); ?>

			<div class="mmtk-card">
				<h2><?php esc_html_e( 'Bar', 'mine-mirth-ticker' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><?php esc_html_e( 'Show', 'mine-mirth-ticker' ); ?></th><td>
						<?php echo $chk( 'enabled', __( 'Show the ticker bar', 'mine-mirth-ticker' ) ) . $chk( 'mobile', __( 'Also on phones', 'mine-mirth-ticker' ) ) . $chk( 'hide_checkout', __( 'Hide on cart and checkout', 'mine-mirth-ticker' ) ) . $chk( 'dismiss', __( 'Visitors can close it', 'mine-mirth-ticker' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Where', 'mine-mirth-ticker' ); ?></th><td>
						<select name="mmtk[placement]">
							<option value="replace" <?php selected( $s['placement'], 'replace' ); ?>><?php esc_html_e( 'Top of the page, instead of the theme\'s announcement bar', 'mine-mirth-ticker' ); ?></option>
							<option value="above" <?php selected( $s['placement'], 'above' ); ?>><?php esc_html_e( 'Top of the page, above the theme\'s announcement bar', 'mine-mirth-ticker' ); ?></option>
							<option value="bottom" <?php selected( $s['placement'], 'bottom' ); ?>><?php esc_html_e( 'Bottom of the screen, always visible', 'mine-mirth-ticker' ); ?></option>
						</select>
						<select name="mmtk[pages]">
							<option value="all" <?php selected( $s['pages'], 'all' ); ?>><?php esc_html_e( 'On every page', 'mine-mirth-ticker' ); ?></option>
							<option value="home" <?php selected( $s['pages'], 'home' ); ?>><?php esc_html_e( 'On the homepage only', 'mine-mirth-ticker' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'To place it inside a page instead, use the shortcode [mm_ticker].', 'mine-mirth-ticker' ); ?></p>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Movement', 'mine-mirth-ticker' ); ?></th><td>
						<label class="mmtk-inline"><?php esc_html_e( 'Speed', 'mine-mirth-ticker' ); ?> <input type="range" min="15" max="160" step="5" name="mmtk[speed]" value="<?php echo (int) $s['speed']; ?>" oninput="this.nextElementSibling.textContent=this.value"> <output><?php echo (int) $s['speed']; ?></output> <?php esc_html_e( 'pixels per second', 'mine-mirth-ticker' ); ?></label>
						<?php echo $chk( 'pause', __( 'Pause while the mouse is over it', 'mine-mirth-ticker' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<p class="description"><?php esc_html_e( 'Text moves from right to left. Visitors who turned off animations in their device settings see it still, and can scroll it by hand.', 'mine-mirth-ticker' ); ?></p>
					</td></tr>
				</table>
			</div>

			<div class="mmtk-card">
				<h2><?php esc_html_e( 'What it shows', 'mine-mirth-ticker' ); ?></h2>
				<p class="description"><?php esc_html_e( 'In this order, then it starts again. Untick to hide an item, use the arrows to reorder, add as many messages as you like.', 'mine-mirth-ticker' ); ?></p>
				<div id="mmtk-items">
					<?php foreach ( array_values( (array) $s['items'] ) as $i => $it ) : ?>
						<?php echo mmtk_item_row( $it, (string) $i ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endforeach; ?>
				</div>
				<p>
					<button type="button" class="button" data-mmtk-add="text"><?php esc_html_e( '+ Add a message', 'mine-mirth-ticker' ); ?></button>
					<button type="button" class="button" data-mmtk-add="btc"><?php esc_html_e( '+ Add the Bitcoin price', 'mine-mirth-ticker' ); ?></button>
					<button type="button" class="button" data-mmtk-add="coins"><?php esc_html_e( '+ Add the hashprices', 'mine-mirth-ticker' ); ?></button>
				</p>
				<template id="mmtk-tpl-text"><?php echo mmtk_item_row( array( 'type' => 'text', 'on' => 1, 'style' => 'highlight' ), '__i__' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></template>
				<template id="mmtk-tpl-btc"><?php echo mmtk_item_row( array( 'type' => 'btc', 'on' => 1 ), '__i__' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></template>
				<template id="mmtk-tpl-coins"><?php echo mmtk_item_row( array( 'type' => 'coins', 'on' => 1 ), '__i__' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></template>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><?php esc_html_e( 'Labels', 'mine-mirth-ticker' ); ?></th><td>
						<label class="mmtk-inline"><?php esc_html_e( 'Bitcoin price', 'mine-mirth-ticker' ); ?> <input type="text" name="mmtk[btc_label]" value="<?php echo esc_attr( (string) $s['btc_label'] ); ?>"></label>
						<label class="mmtk-inline"><?php esc_html_e( 'Hashprice', 'mine-mirth-ticker' ); ?> <input type="text" name="mmtk[hp_label]" value="<?php echo esc_attr( (string) $s['hp_label'] ); ?>"></label>
						<p class="description"><?php esc_html_e( '{name} is the coin\'s name, {tag} its ticker (BTC, LTC…).', 'mine-mirth-ticker' ); ?></p>
						<?php echo $chk( 'change', __( 'Show the 24-hour change (▲ green / ▼ red)', 'mine-mirth-ticker' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<label class="mmtk-inline"><?php esc_html_e( 'Between items', 'mine-mirth-ticker' ); ?> <input type="text" name="mmtk[separator]" value="<?php echo esc_attr( (string) $s['separator'] ); ?>" class="small-text"></label>
						<label class="mmtk-inline"><?php esc_html_e( 'Repeat messages after every', 'mine-mirth-ticker' ); ?> <input type="number" min="0" max="50" name="mmtk[repeat_every]" value="<?php echo (int) $s['repeat_every']; ?>" class="small-text"> <?php esc_html_e( 'coins (0 = once per round; only messages with "Also repeat between coins")', 'mine-mirth-ticker' ); ?></label>
					</td></tr>
				</table>
			</div>

			<div class="mmtk-card">
				<h2><?php esc_html_e( 'Coins', 'mine-mirth-ticker' ); ?></h2>
				<p class="description">
					<?php
					/* translators: %d: number of coins */
					echo esc_html( sprintf( __( 'Every proof-of-work coin in the WhatToMine feed (%d right now; new coins appear here as they are added). Tick the ones to show; the order number sets their order. Hashprice is what one unit of hashrate earns per day, in the unit shown.', 'mine-mirth-ticker' ), count( $feed ) ) );
					?>
				</p>
				<p>
					<button type="button" class="button button-small" data-mmtk-coins="all"><?php esc_html_e( 'Tick all', 'mine-mirth-ticker' ); ?></button>
					<button type="button" class="button button-small" data-mmtk-coins="none"><?php esc_html_e( 'Untick all', 'mine-mirth-ticker' ); ?></button>
				</p>
				<div class="mmtk-coins-wrap">
					<table class="mmtk-coins">
						<thead><tr>
							<th><?php esc_html_e( 'Show', 'mine-mirth-ticker' ); ?></th>
							<th><?php esc_html_e( 'Order', 'mine-mirth-ticker' ); ?></th>
							<th><?php esc_html_e( 'Coin', 'mine-mirth-ticker' ); ?></th>
							<th><?php esc_html_e( 'Algorithm', 'mine-mirth-ticker' ); ?></th>
							<th><?php esc_html_e( 'Name shown', 'mine-mirth-ticker' ); ?></th>
							<th><?php esc_html_e( 'Unit', 'mine-mirth-ticker' ); ?></th>
							<th><?php esc_html_e( 'Hashprice now', 'mine-mirth-ticker' ); ?></th>
						</tr></thead>
						<tbody>
							<?php $o = 0; ?>
							<?php foreach ( $rows as $name => $conf ) : ?>
								<?php
								$o++;
								$c  = $feed[ $name ] ?? null;
								$hp = $c ? mmtk_hashprice( $c, $btc, (string) ( $conf['unit'] ?? '' ) ) : null;
								$n  = 'mmtk[coins][' . $name . ']';
								?>
								<tr class="<?php echo empty( $conf['on'] ) ? 'is-off' : ''; ?>">
									<td><input type="checkbox" class="mmtk-coin-on" name="<?php echo esc_attr( $n ); ?>[on]" value="1" <?php checked( ! empty( $conf['on'] ) ); ?>></td>
									<td><input type="number" class="small-text" name="<?php echo esc_attr( $n ); ?>[order]" value="<?php echo (int) $o; ?>"></td>
									<td><span class="mmtk-tag" style="border-left:3px solid <?php echo esc_attr( $c ? mmtk_coin_color( $c['tag'] ) : '#50575e' ); ?>"><?php echo esc_html( $c ? $c['tag'] : '?' ); ?></span> <?php echo esc_html( mmtk_pretty_name( $name ) ); ?><?php echo $c && $c['lagging'] ? ' <em style="color:#b26200">' . esc_html__( '(data delayed)', 'mine-mirth-ticker' ) . '</em>' : ''; ?><?php echo $c ? '' : ' <em style="color:#b32d2e">' . esc_html__( '(not in the feed now: hidden)', 'mine-mirth-ticker' ) . '</em>'; ?></td>
									<td><?php echo esc_html( $c ? $c['algo'] : '' ); ?></td>
									<td><input type="text" name="<?php echo esc_attr( $n ); ?>[label]" value="<?php echo esc_attr( (string) ( $conf['label'] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( mmtk_pretty_name( $name ) ); ?>"></td>
									<td>
										<select name="<?php echo esc_attr( $n ); ?>[unit]">
											<option value="auto" <?php selected( $conf['unit'] ?? 'auto', 'auto' ); ?>><?php echo esc_html( sprintf( /* translators: %s: unit */ __( 'Usual (%s)', 'mine-mirth-ticker' ), $hp && 'auto' === ( $conf['unit'] ?? 'auto' ) ? $hp['unit'] : ( $c ? mmtk_algo_unit( $c['algo'] ) : 'auto' ) ) ); ?></option>
											<?php foreach ( array_keys( mmtk_units() ) as $u ) : ?>
												<option value="<?php echo esc_attr( $u ); ?>" <?php selected( $conf['unit'] ?? '', $u ); ?>><?php echo esc_html( $u ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
									<td><?php echo $hp ? esc_html( mmtk_money( $hp['value'] ) . '/' . $hp['unit'] . '/day' ) . ( null !== $hp['change'] ? ' <span style="color:' . ( $hp['change'] >= 0 ? '#007017' : '#b32d2e' ) . '">' . esc_html( mmtk_pct( $hp['change'] ) ) . '</span>' : '' ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><?php esc_html_e( 'Currency', 'mine-mirth-ticker' ); ?></th><td>
						<select name="mmtk[currency]">
							<option value="theme" <?php selected( $s['currency'], 'theme' ); ?>><?php esc_html_e( 'Same as the theme\'s market data', 'mine-mirth-ticker' ); ?></option>
							<?php foreach ( array( 'usd' => 'USD $', 'eur' => 'EUR €', 'gbp' => 'GBP £', 'cad' => 'CAD', 'aud' => 'AUD' ) as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['currency'], $k ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<label class="mmtk-inline"><?php esc_html_e( 'Update prices every', 'mine-mirth-ticker' ); ?> <input type="number" min="30" max="3600" name="mmtk[refresh]" value="<?php echo (int) $s['refresh']; ?>" class="small-text"> <?php esc_html_e( 'seconds', 'mine-mirth-ticker' ); ?></label>
						<p class="description"><?php esc_html_e( 'Bitcoin price: CoinGecko. Coin data: WhatToMine (refreshed every 10 minutes). Your server fetches them and shares one copy with all visitors; if a source is down, the last known figures stay.', 'mine-mirth-ticker' ); ?></p>
					</td></tr>
				</table>
			</div>

			<div class="mmtk-card">
				<h2><?php esc_html_e( 'Look', 'mine-mirth-ticker' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><?php esc_html_e( 'Size', 'mine-mirth-ticker' ); ?></th><td>
						<select name="mmtk[size]">
							<option value="sm" <?php selected( $s['size'], 'sm' ); ?>><?php esc_html_e( 'Compact', 'mine-mirth-ticker' ); ?></option>
							<option value="md" <?php selected( $s['size'], 'md' ); ?>><?php esc_html_e( 'Standard', 'mine-mirth-ticker' ); ?></option>
							<option value="lg" <?php selected( $s['size'], 'lg' ); ?>><?php esc_html_e( 'Large', 'mine-mirth-ticker' ); ?></option>
						</select>
					</td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Colours', 'mine-mirth-ticker' ); ?></th><td>
						<?php
						echo $color( 'bg', __( 'Background', 'mine-mirth-ticker' ) ) . $color( 'text', __( 'Figures', 'mine-mirth-ticker' ) ) . $color( 'muted', __( 'Labels', 'mine-mirth-ticker' ) ) . $color( 'accent', __( 'Accent', 'mine-mirth-ticker' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						echo '<br>' . $color( 'up', __( 'Going up', 'mine-mirth-ticker' ) ) . $color( 'down', __( 'Going down', 'mine-mirth-ticker' ) ) . $color( 'msg_bg', __( 'Highlighted message', 'mine-mirth-ticker' ) ) . $color( 'msg_text', __( 'Highlighted message text', 'mine-mirth-ticker' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</td></tr>
				</table>
			</div>

			<p class="submit">
				<?php submit_button( __( 'Save', 'mine-mirth-ticker' ), 'primary', 'submit', false ); ?>
				<button type="submit" name="mmtk_reset" value="1" class="button" onclick="return confirm('<?php echo esc_js( __( 'Go back to the original settings?', 'mine-mirth-ticker' ) ); ?>');"><?php esc_html_e( 'Reset everything', 'mine-mirth-ticker' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}
