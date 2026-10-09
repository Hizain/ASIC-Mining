<?php
/**
 * Plugin Name:       Mine Mirth Rig Optimizer
 * Description:       Mining rig optimizer for Mine Mirth: enter your ASIC miners, power price and conditions, and get a rig score, the most profitable power mode, break-even power price, home vs hosting comparison and specific recommendations, using live Bitcoin network data. Shortcode: [minemirth_rig_optimizer].
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Mine Mirth, LLC
 * License:           GPLv2 or later
 * Text Domain:       mine-mirth-rig-optimizer
 *
 * @package MineMirthRigOptimizer
 */

defined( 'ABSPATH' ) || exit;

define( 'MMRO_VERSION', '1.0.0' );
define( 'MMRO_URI', plugin_dir_url( __FILE__ ) );

/**
 * Built-in SHA-256 miner presets: typical nameplate hashrate (TH/s) and power (W).
 * Customers can edit both numbers after choosing a preset.
 */
function mmro_presets(): array {
	$list = array(
		array( 'Antminer S21 XP', 270, 3645 ),
		array( 'Antminer S21 Pro', 234, 3510 ),
		array( 'Antminer S21', 200, 3500 ),
		array( 'Antminer S21 Hyd', 335, 5360 ),
		array( 'Antminer S19 XP', 141, 3010 ),
		array( 'Antminer S19k Pro', 120, 2760 ),
		array( 'Antminer S19 Pro', 110, 3250 ),
		array( 'Antminer S19j Pro', 104, 3068 ),
		array( 'Whatsminer M60S', 186, 3441 ),
		array( 'Whatsminer M50S', 126, 3276 ),
		array( 'Whatsminer M30S++', 112, 3472 ),
		array( 'Avalon A1466', 150, 3230 ),
	);
	$out = array();
	foreach ( $list as $row ) {
		$out[] = array( 'name' => $row[0], 'th' => $row[1], 'w' => $row[2], 'group' => 'common' );
	}

	// Miners from the store, when products carry Hashrate and Power attributes (Mine Mirth theme helper).
	if ( function_exists( 'wc_get_products' ) && function_exists( 'mm_product_mining_specs' ) ) {
		$cached = get_transient( 'mmro_store_presets' );
		if ( ! is_array( $cached ) ) {
			$cached = array();
			foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => 60, 'orderby' => 'date', 'order' => 'DESC' ) ) as $product ) {
				$spec = mm_product_mining_specs( $product );
				if ( $spec['th'] > 0 && $spec['watts'] > 0 ) {
					$cached[] = array(
						'name'  => $product->get_name(),
						'th'    => round( (float) $spec['th'], 2 ),
						'w'     => round( (float) $spec['watts'] ),
						'group' => 'store',
						'url'   => get_permalink( $product->get_id() ),
						'price' => (float) $product->get_price(),
					);
				}
			}
			set_transient( 'mmro_store_presets', $cached, 6 * HOUR_IN_SECONDS );
		}
		$out = array_merge( $cached, $out );
	}
	return $out;
}
add_action( 'woocommerce_update_product', static fn() => delete_transient( 'mmro_store_presets' ) );

function mmro_link( string $url ): string {
	if ( function_exists( 'mm_url' ) ) {
		return (string) mm_url( $url );
	}
	if ( '{shop}' === $url ) {
		return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	}
	return str_starts_with( $url, '/' ) ? home_url( $url ) : $url;
}

/**
 * [minemirth_rig_optimizer title="" electricity="0.10" hosting_rate="0.078" shop="{shop}" hosting="/miner-hosting/" intro="yes" faq="yes"]
 */
function mmro_shortcode( $atts ): string {
	$a = shortcode_atts(
		array(
			// The Mine Mirth theme already shows the page title in its header, so no second title by default.
			'title'        => function_exists( 'mm_page_hero' ) ? '' : __( 'Mining Rig Optimizer', 'mine-mirth-rig-optimizer' ),
			'electricity'  => '0.10',
			'hosting_rate' => '0.078',
			'shop'         => '{shop}',
			'hosting'      => '/miner-hosting/',
			'intro'        => 'yes',
			'faq'          => 'yes',
		),
		$atts
	);
	wp_enqueue_style( 'mmro', MMRO_URI . 'assets/optimizer.css', array(), MMRO_VERSION );
	wp_enqueue_script( 'mmro', MMRO_URI . 'assets/optimizer.js', array(), MMRO_VERSION, true );

	$config = array(
		'presets'  => mmro_presets(),
		'market'   => function_exists( 'mm_market_shortcode' ) ? rest_url( 'mine-mirth/v1/market' ) : '',
		'currency' => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol() ) : '$',
		'shop'     => mmro_link( (string) $a['shop'] ),
		'hosting'  => mmro_link( (string) $a['hosting'] ),
	);
	$cur = esc_html( $config['currency'] );

	ob_start();
	?>
	<section class="mmro" data-mmro data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
		<?php if ( 'no' !== $a['intro'] ) : ?>
			<header class="mmro__intro">
				<span class="mmro__eyebrow"><?php esc_html_e( 'Free tool · live network data', 'mine-mirth-rig-optimizer' ); ?></span>
				<?php if ( '' !== trim( (string) $a['title'] ) ) : ?>
					<h2 class="mmro__title"><?php echo esc_html( $a['title'] ); ?></h2>
				<?php endif; ?>
				<p><?php esc_html_e( 'Tell us about your miners, your power price and where they run. The optimizer scores your rig, finds the most profitable power mode, shows the power price where you stop making money, compares running at home with hosting, and lists the changes that would earn you the most.', 'mine-mirth-rig-optimizer' ); ?></p>
			</header>
		<?php endif; ?>

		<div class="mmro__layout">
			<form class="mmro__form" data-mmro-form onsubmit="return false">
				<fieldset>
					<legend><?php esc_html_e( 'Your rig', 'mine-mirth-rig-optimizer' ); ?></legend>
					<label class="mmro__field mmro__field--full"><span><?php esc_html_e( 'Miner model', 'mine-mirth-rig-optimizer' ); ?></span>
						<select name="model" data-mmro-model></select>
					</label>
					<label class="mmro__field"><span><?php esc_html_e( 'Number of miners', 'mine-mirth-rig-optimizer' ); ?></span><input type="number" name="qty" min="1" step="1" value="1" inputmode="numeric"></label>
					<label class="mmro__field"><span><?php esc_html_e( 'Hashrate per miner', 'mine-mirth-rig-optimizer' ); ?></span><span class="mmro__unit"><input type="number" name="th" min="0" step="any" inputmode="decimal"><em>TH/s</em></span></label>
					<label class="mmro__field"><span><?php esc_html_e( 'Power per miner', 'mine-mirth-rig-optimizer' ); ?></span><span class="mmro__unit"><input type="number" name="w" min="0" step="any" inputmode="decimal"><em>W</em></span></label>
				</fieldset>
				<fieldset>
					<legend><?php esc_html_e( 'Where it runs', 'mine-mirth-rig-optimizer' ); ?></legend>
					<label class="mmro__field"><span><?php esc_html_e( 'Electricity price', 'mine-mirth-rig-optimizer' ); ?></span><span class="mmro__unit"><input type="number" name="elec" min="0" step="0.001" value="<?php echo esc_attr( (string) (float) $a['electricity'] ); ?>" inputmode="decimal"><em><?php echo $cur; // phpcs:ignore WordPress.Security.EscapeOutput ?>/kWh</em></span></label>
					<label class="mmro__field"><span><?php esc_html_e( 'Room temperature', 'mine-mirth-rig-optimizer' ); ?></span><span class="mmro__unit"><input type="number" name="temp" step="1" value="25" inputmode="numeric"><em>°C</em></span></label>
					<label class="mmro__field"><span><?php esc_html_e( 'Uptime', 'mine-mirth-rig-optimizer' ); ?></span><span class="mmro__unit"><input type="number" name="uptime" min="1" max="100" step="0.5" value="97" inputmode="decimal"><em>%</em></span></label>
					<label class="mmro__field"><span><?php esc_html_e( 'Pool fee', 'mine-mirth-rig-optimizer' ); ?></span><span class="mmro__unit"><input type="number" name="pool" min="0" max="10" step="0.1" value="2" inputmode="decimal"><em>%</em></span></label>
					<label class="mmro__field"><span><?php esc_html_e( 'Cooling and fans overhead', 'mine-mirth-rig-optimizer' ); ?></span><span class="mmro__unit"><input type="number" name="cooling" min="0" max="100" step="1" value="0" inputmode="numeric"><em>%</em></span></label>
					<label class="mmro__field"><span><?php esc_html_e( 'Hosting rate to compare', 'mine-mirth-rig-optimizer' ); ?></span><span class="mmro__unit"><input type="number" name="host" min="0" step="0.001" value="<?php echo esc_attr( (string) (float) $a['hosting_rate'] ); ?>" inputmode="decimal"><em><?php echo $cur; // phpcs:ignore WordPress.Security.EscapeOutput ?>/kWh</em></span></label>
				</fieldset>
				<details class="mmro__adv">
					<summary><?php esc_html_e( 'Network data (filled in live)', 'mine-mirth-rig-optimizer' ); ?></summary>
					<div class="mmro__adv-grid">
						<label class="mmro__field"><span><?php esc_html_e( 'Bitcoin price', 'mine-mirth-rig-optimizer' ); ?></span><span class="mmro__unit"><input type="number" name="price" min="0" step="any" inputmode="decimal"><em><?php echo $cur; // phpcs:ignore WordPress.Security.EscapeOutput ?></em></span></label>
						<label class="mmro__field"><span><?php esc_html_e( 'Network hashrate', 'mine-mirth-rig-optimizer' ); ?></span><span class="mmro__unit"><input type="number" name="net" min="0" step="any" inputmode="decimal"><em>EH/s</em></span></label>
						<label class="mmro__field"><span><?php esc_html_e( 'Block subsidy', 'mine-mirth-rig-optimizer' ); ?></span><span class="mmro__unit"><input type="number" name="subsidy" min="0" step="any" value="3.125" inputmode="decimal"><em>BTC</em></span></label>
						<label class="mmro__field"><span><?php esc_html_e( 'Average fees per block', 'mine-mirth-rig-optimizer' ); ?></span><span class="mmro__unit"><input type="number" name="fees" min="0" step="any" value="0.03" inputmode="decimal"><em>BTC</em></span></label>
					</div>
				</details>
				<p class="mmro__status" data-mmro-status aria-live="polite"><?php esc_html_e( 'Loading live network data…', 'mine-mirth-rig-optimizer' ); ?></p>
			</form>

			<div class="mmro__results" data-mmro-results aria-live="polite">
				<div class="mmro__empty"><?php esc_html_e( 'Choose a miner to see the analysis.', 'mine-mirth-rig-optimizer' ); ?></div>
			</div>
		</div>

		<?php if ( 'no' !== $a['faq'] ) : ?>
			<div class="mmro__faq">
				<h3><?php esc_html_e( 'How the optimizer works', 'mine-mirth-rig-optimizer' ); ?></h3>
				<details><summary><?php esc_html_e( 'Where do the numbers come from?', 'mine-mirth-rig-optimizer' ); ?></summary><p><?php esc_html_e( 'Revenue uses the current Bitcoin price and network hashrate: 144 blocks a day, each paying the block subsidy plus average fees, shared in proportion to your hashrate. Your uptime and pool fee are taken off, and power cost is your miners’ wattage plus any cooling overhead, multiplied by your electricity price.', 'mine-mirth-rig-optimizer' ); ?></p></details>
				<details><summary><?php esc_html_e( 'What are Eco, Stock and Turbo modes?', 'mine-mirth-rig-optimizer' ); ?></summary><p><?php esc_html_e( 'Most modern ASICs can be underclocked or overclocked with their firmware. The optimizer models a typical eco profile (about 85% hashrate for 72% power) and a turbo profile (about 110% hashrate for 120% power), then picks the one that earns the most at your power price. Real results depend on the model, firmware and cooling.', 'mine-mirth-rig-optimizer' ); ?></p></details>
				<details><summary><?php esc_html_e( 'How is the rig score calculated?', 'mine-mirth-rig-optimizer' ); ?></summary><p><?php esc_html_e( 'Out of 100: efficiency in joules per terahash (40 points), profit margin in the best mode (40), uptime (10) and room temperature (10). Above 80 is a strong setup; under 50 means a change, such as a different power mode, hosting or newer hardware, would pay off.', 'mine-mirth-rig-optimizer' ); ?></p></details>
				<details><summary><?php esc_html_e( 'Why does room temperature matter?', 'mine-mirth-rig-optimizer' ); ?></summary><p><?php esc_html_e( 'ASICs throttle to protect their chips when the intake air is hot. The optimizer assumes about 1.5% less hashrate for every degree above 30 °C, and advises against turbo mode above 35 °C.', 'mine-mirth-rig-optimizer' ); ?></p></details>
				<p class="mmro__disclaimer"><?php esc_html_e( 'Estimates only. Bitcoin price, network difficulty and fees change constantly, and real hardware varies. Nothing here is financial advice.', 'mine-mirth-rig-optimizer' ); ?></p>
			</div>
		<?php endif; ?>
	</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'minemirth_rig_optimizer', 'mmro_shortcode' );
