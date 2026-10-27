<?php
/**
 * Mining profitability & hosting cost calculators.
 *
 * Shortcodes:
 *   [mm_calculator variant="full|compact"]
 *   [mine_mirth_hosting_calculator]   (legacy, hosting cost scenario)
 *   [mm_hosting_calculator]
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/**
 * Miner presets: [ [label, th, watts, cost], ... ].
 */
function mm_calculator_presets(): array {
	$presets = array();

	// Store products that carry hashrate + power attributes.
	if ( mm_is_woo() ) {
		$cached = get_transient( 'mm_calc_product_presets' );
		if ( ! is_array( $cached ) ) {
			$cached = array();
			$ids    = wc_get_products(
				array(
					'status' => 'publish',
					'limit'  => 40,
					'return' => 'ids',
				)
			);
			foreach ( $ids as $id ) {
				$product = wc_get_product( $id );
				if ( ! $product ) {
					continue;
				}
				$specs = mm_product_mining_specs( $product );
				if ( $specs['th'] > 0 && $specs['watts'] > 0 ) {
					$cached[] = array( $product->get_name(), $specs['th'], $specs['watts'], (float) $product->get_price() );
				}
			}
			set_transient( 'mm_calc_product_presets', $cached, HOUR_IN_SECONDS );
		}
		$presets = $cached;
	}

	foreach ( mm_lines( mm_opt( 'calc_presets' ) ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( count( $parts ) >= 3 && is_numeric( $parts[1] ) && is_numeric( $parts[2] ) ) {
			$presets[] = array( $parts[0], (float) $parts[1], (float) $parts[2], isset( $parts[3] ) && is_numeric( $parts[3] ) ? (float) $parts[3] : 0 );
		}
	}
	return $presets;
}

/**
 * Clear product presets when products change.
 */
function mm_calculator_flush_presets(): void {
	delete_transient( 'mm_calc_product_presets' );
}
add_action( 'save_post_product', 'mm_calculator_flush_presets' );

/**
 * Hashrate (TH/s) and power (W) from product attributes.
 *
 * @param WC_Product $product Product.
 */
function mm_product_mining_specs( $product ): array {
	$hash  = '';
	$power = '';
	foreach ( array( 'hashrate', 'hash-rate', 'hash_rate', 'hash rate' ) as $name ) {
		$hash = $hash ? $hash : (string) $product->get_attribute( $name );
	}
	foreach ( array( 'power', 'power-consumption', 'power consumption', 'wattage' ) as $name ) {
		$power = $power ? $power : (string) $product->get_attribute( $name );
	}
	// Variation-style values may be comma separated: use the first.
	$hash  = trim( explode( ',', $hash )[0] );
	$power = trim( explode( ',', $power )[0] );
	return array(
		'th'    => $hash ? mm_hashrate_to_th( $hash ) : 0.0,
		'watts' => $power ? mm_parse_number( $power ) : 0.0,
		'raw'   => array( $hash, $power ),
	);
}

/**
 * Read a numeric prefill from the query string.
 */
function mm_calc_query( string $key, $default ) {
	if ( isset( $_GET[ $key ] ) && is_numeric( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return (float) $_GET[ $key ]; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	return $default;
}

/**
 * Number input field.
 */
function mm_calc_field( string $key, string $label, $value, string $unit, array $attrs = array() ): string {
	$attr = '';
	foreach ( wp_parse_args( $attrs, array( 'step' => 'any', 'min' => '0' ) ) as $k => $v ) {
		$attr .= ' ' . esc_attr( $k ) . '="' . esc_attr( (string) $v ) . '"';
	}
	$id = wp_unique_id( 'mm-calc-' . $key . '-' );
	return sprintf(
		'<label class="mm-field" for="%1$s"><span>%2$s</span><span class="mm-input-group"><input id="%1$s" type="number" inputmode="decimal" data-k="%3$s" value="%4$s"%5$s><span class="mm-input-group__unit">%6$s</span></span></label>',
		esc_attr( $id ),
		esc_html( $label ),
		esc_attr( $key ),
		esc_attr( (string) $value ),
		$attr,
		esc_html( $unit )
	);
}

/**
 * Calculator markup.
 *
 * @param array $args variant: full|compact.
 */
function mm_calculator_html( array $args = array() ): string {
	$args    = wp_parse_args( $args, array( 'variant' => 'full', 'title' => '' ) );
	$compact = 'compact' === $args['variant'];
	if ( ! $compact && function_exists( 'mm_tool_html' ) ) {
		return mm_tool_html( 'profit' );
	}
	wp_enqueue_script( 'mm-calculator' );

	$cur      = mm_market_currency();
	$sym      = mm_currency_symbol( $cur );
	$presets  = mm_calculator_presets();
	$first    = $presets[0] ?? array( 'Custom', 200, 3500, 0 );
	$th       = mm_calc_query( 'hashrate', $first[1] );
	$watts    = mm_calc_query( 'power', $first[2] );
	$cost     = mm_calc_query( 'cost', $first[3] );
	$kwh      = (float) mm_opt( 'calc_kwh' );
	$pool     = (float) mm_opt( 'calc_pool_fee' );
	$cached   = mm_market_cached();
	$reward   = ! empty( $cached['halving']['subsidy'] ) ? (float) $cached['halving']['subsidy'] : 3.125;
	$title    = $args['title'] ? $args['title'] : __( 'Bitcoin mining calculator', 'minemirth' );
	$prefill  = isset( $_GET['hashrate'] ) || isset( $_GET['power'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$labelled = isset( $_GET['miner'] ) ? sanitize_text_field( wp_unslash( $_GET['miner'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	ob_start();
	?>
	<div class="mm-calc<?php echo $compact ? ' mm-calc--compact' : ''; ?>" data-mm-calc data-currency="<?php echo esc_attr( $sym ); ?>" data-locale="<?php echo esc_attr( str_replace( '_', '-', get_locale() ) ); ?>">
		<div class="mm-calc__head">
			<h3 class="mm-calc__title"><?php echo mm_icon( 'calculator' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $title ); ?></h3>
			<span class="mm-calc__live" data-mm-calc-status><span class="mm-live-dot" aria-hidden="true"></span><span data-mm-calc-status-text><?php esc_html_e( 'Live network data', 'minemirth' ); ?></span></span>
		</div>

		<div class="mm-calc__grid">
			<?php if ( $presets ) : ?>
				<label class="mm-field mm-calc__full">
					<span><?php esc_html_e( 'Miner', 'minemirth' ); ?></span>
					<select data-mm-calc-preset>
						<option value=""><?php echo esc_html( $prefill ? ( $labelled ? $labelled : __( 'From product page', 'minemirth' ) ) : __( 'Custom specs', 'minemirth' ) ); ?></option>
						<?php foreach ( $presets as $i => $preset ) : ?>
							<option value="<?php echo esc_attr( wp_json_encode( array( 'th' => $preset[1], 'w' => $preset[2], 'cost' => $preset[3] ) ) ); ?>" <?php selected( ! $prefill && 0 === $i ); ?>><?php echo esc_html( sprintf( '%1$s — %2$s TH/s · %3$s W', $preset[0], number_format_i18n( $preset[1], 1 ), number_format_i18n( $preset[2] ) ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>
			<?php
			echo mm_calc_field( 'hashrate', __( 'Hashrate', 'minemirth' ), $th, 'TH/s' ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo mm_calc_field( 'power', __( 'Power draw', 'minemirth' ), $watts, 'W', array( 'step' => '1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo mm_calc_field( 'kwh', __( 'Electricity', 'minemirth' ), $kwh, $sym . '/kWh', array( 'step' => '0.001' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			if ( ! $compact ) {
				echo mm_calc_field( 'pool', __( 'Pool fee', 'minemirth' ), $pool, '%', array( 'step' => '0.1', 'max' => '100' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo mm_calc_field( 'cost', __( 'Hardware cost', 'minemirth' ), $cost, $sym, array( 'step' => '1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo mm_calc_field( 'hosting', __( 'Hosting fee / month', 'minemirth' ), 0, $sym, array( 'step' => '1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo mm_calc_field( 'cost', __( 'Hardware cost', 'minemirth' ), $cost, $sym, array( 'step' => '1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</div>

		<?php if ( ! $compact ) : ?>
			<details>
				<summary><?php esc_html_e( 'Network & advanced inputs', 'minemirth' ); ?><?php echo mm_icon( 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></summary>
				<div class="mm-calc__grid mm-calc__grid--3">
					<?php
					echo mm_calc_field( 'price', __( 'BTC price', 'minemirth' ), isset( $cached['price'] ) ? round( (float) $cached['price'] ) : '', $sym, array( 'step' => '1', 'data-live' => 'price' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo mm_calc_field( 'difficulty', __( 'Difficulty', 'minemirth' ), isset( $cached['difficulty'] ) ? round( (float) $cached['difficulty'] / 1e12, 2 ) : '', 'T', array( 'step' => '0.01', 'data-live' => 'difficulty' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo mm_calc_field( 'reward', __( 'Block subsidy', 'minemirth' ), $reward, 'BTC', array( 'step' => '0.001', 'data-live' => 'reward' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo mm_calc_field( 'fees', __( 'Avg. fees / block', 'minemirth' ), isset( $cached['fees'] ) ? round( (float) $cached['fees'], 4 ) : 0, 'BTC', array( 'step' => '0.001', 'data-live' => 'fees' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo mm_calc_field( 'growth', __( 'Difficulty growth / month', 'minemirth' ), 2, '%', array( 'step' => '0.1', 'min' => '-50' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo mm_calc_field( 'uptime', __( 'Uptime', 'minemirth' ), 98, '%', array( 'step' => '0.5', 'max' => '100' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</div>
			</details>
		<?php else : ?>
			<input type="hidden" data-k="pool" value="<?php echo esc_attr( (string) $pool ); ?>">
		<?php endif; ?>

		<div class="mm-calc__results" aria-live="polite">
			<div class="mm-calc__hero">
				<div>
					<small><?php esc_html_e( 'Estimated net profit', 'minemirth' ); ?></small>
					<span class="mm-calc__big" data-out="profitDay">—</span> <span class="mm-calc__per"><?php esc_html_e( 'per day', 'minemirth' ); ?></span>
				</div>
				<?php if ( ! $compact ) : ?>
					<div class="mm-calc__kpi">
						<small><?php esc_html_e( 'BTC mined / day', 'minemirth' ); ?></small>
						<strong data-out="btcDay">—</strong>
					</div>
				<?php endif; ?>
			</div>
			<div class="mm-calc__kpis">
				<div class="mm-calc__kpi"><small><?php esc_html_e( 'Revenue / day', 'minemirth' ); ?></small><strong data-out="revenueDay">—</strong></div>
				<div class="mm-calc__kpi"><small><?php esc_html_e( 'Power cost / day', 'minemirth' ); ?></small><strong data-out="powerDay">—</strong></div>
				<div class="mm-calc__kpi"><small><?php esc_html_e( 'Profit / month', 'minemirth' ); ?></small><strong data-out="profitMonth">—</strong></div>
				<?php if ( ! $compact ) : ?>
					<div class="mm-calc__kpi"><small><?php esc_html_e( 'Profit / year', 'minemirth' ); ?></small><strong data-out="profitYear">—</strong></div>
					<div class="mm-calc__kpi"><small><?php esc_html_e( 'Break-even', 'minemirth' ); ?></small><strong data-out="breakeven">—</strong></div>
					<div class="mm-calc__kpi"><small><?php esc_html_e( 'Efficiency', 'minemirth' ); ?></small><strong data-out="efficiency">—</strong></div>
				<?php endif; ?>
			</div>
			<?php if ( ! $compact ) : ?>
				<div>
					<svg class="mm-calc__chart" viewBox="0 0 360 120" preserveAspectRatio="none" data-mm-calc-chart role="img" aria-label="<?php esc_attr_e( 'Cumulative profit over 12 months', 'minemirth' ); ?>">
						<defs><linearGradient id="mmCalcGrad" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--mm-accent-2)"/><stop offset="1" stop-color="var(--mm-primary)"/></linearGradient></defs>
					</svg>
					<div class="mm-calc__chart-label"><span><?php esc_html_e( 'Cumulative result after hardware cost — months 1 to 12', 'minemirth' ); ?></span><span data-out="year1">—</span></div>
				</div>
			<?php endif; ?>
		</div>

		<div class="mm-calc__foot">
			<p class="mm-calc__note"><?php echo esc_html( (string) mm_opt( 'calc_disclaimer' ) ); ?></p>
			<?php if ( mm_opt( 'calc_cta_text' ) ) : ?>
				<?php echo mm_button( mm_opt( 'calc_cta_text' ), mm_opt( 'calc_cta_url' ), 'primary', array( 'size' => 'sm', 'icon' => 'arrow-right' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
		</div>
		<?php if ( mm_opt( 'market_sources' ) && ! $compact ) : ?>
			<p class="mm-calc__sources"><?php esc_html_e( 'Live inputs:', 'minemirth' ); ?> <a href="https://www.coingecko.com/en/coins/bitcoin" target="_blank" rel="noopener nofollow">CoinGecko</a> · <a href="https://mempool.space/" target="_blank" rel="noopener nofollow">mempool.space</a> · <span data-mm-m="updated"><?php echo esc_html( mm_market_display()['updated'] ); ?></span></p>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Hosting cost scenario (legacy shortcode).
 */
function mm_hosting_calculator_html(): string {
	wp_enqueue_script( 'mm-calculator' );
	$sym = mm_currency_symbol( mm_market_currency() );
	ob_start();
	?>
	<div class="mm-calc mm-calc--compact" data-mm-hosting-calc data-currency="<?php echo esc_attr( $sym ); ?>">
		<div class="mm-calc__head">
			<h3 class="mm-calc__title"><?php echo mm_icon( 'server' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Hosting cost scenario', 'minemirth' ); ?></h3>
		</div>
		<div class="mm-calc__grid">
			<?php
			echo mm_calc_field( 'miners', __( 'Number of miners', 'minemirth' ), 1, __( 'units', 'minemirth' ), array( 'step' => '1', 'min' => '1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo mm_calc_field( 'watts', __( 'Power per miner', 'minemirth' ), 3500, 'W', array( 'step' => '1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo mm_calc_field( 'kwh', __( 'Hosting rate', 'minemirth' ), (float) mm_opt( 'calc_kwh' ), $sym . '/kWh', array( 'step' => '0.001' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo mm_calc_field( 'fixed', __( 'Fixed fee / miner / month', 'minemirth' ), 0, $sym, array( 'step' => '0.01' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</div>
		<div class="mm-calc__results" aria-live="polite">
			<div class="mm-calc__hero">
				<div>
					<small><?php esc_html_e( 'Estimated monthly total', 'minemirth' ); ?></small>
					<span class="mm-calc__big" data-out="total">—</span>
				</div>
			</div>
			<div class="mm-calc__kpis">
				<div class="mm-calc__kpi"><small><?php esc_html_e( 'Electricity / month', 'minemirth' ); ?></small><strong data-out="power">—</strong></div>
				<div class="mm-calc__kpi"><small><?php esc_html_e( 'Fixed fees / month', 'minemirth' ); ?></small><strong data-out="fixed">—</strong></div>
				<div class="mm-calc__kpi"><small><?php esc_html_e( 'Energy / month', 'minemirth' ); ?></small><strong data-out="energy">—</strong></div>
			</div>
		</div>
		<p class="mm-calc__note"><?php esc_html_e( 'Assumes 24 hours × 30.4 days at nameplate power. A cost scenario, not a quote — taxes, deposits, repairs and curtailment are excluded unless entered.', 'minemirth' ); ?></p>
	</div>
	<?php
	return (string) ob_get_clean();
}

function mm_calculator_shortcode( $atts = array() ): string {
	$atts = shortcode_atts(
		array(
			'variant' => 'full',
			'title'   => '',
		),
		$atts
	);
	if ( 'hosting' === $atts['variant'] ) {
		return mm_hosting_calculator_html();
	}
	return mm_calculator_html( $atts );
}
add_shortcode( 'mm_calculator', 'mm_calculator_shortcode' );
add_shortcode( 'mm_hosting_calculator', 'mm_hosting_calculator_html' );
add_shortcode( 'mine_mirth_hosting_calculator', 'mm_hosting_calculator_html' );
