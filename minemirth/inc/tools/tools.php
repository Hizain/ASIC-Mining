<?php
/**
 * Mining tools: registry, shared form parts and the markup of each tool.
 *
 * Every tool is plain HTML rendered here (readable and indexable without JavaScript) and brought to
 * life by assets/js/tools.js, which reads inputs marked data-k="…" and writes results into
 * data-o="…". Market data comes from mm_tools_payload() inline on the page and refreshes over REST.
 *
 * Embed anywhere with [mm_tool id="profit"] or the "Mine Mirth section → Tool" block.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/**
 * The tool suite. Slugs live under the tools hub (/free-calculators/ by default).
 */
function mm_tools(): array {
	return array(
		'profit'     => array( 'title' => __( 'Bitcoin Mining Profitability Calculator', 'minemirth' ), 'menu' => __( 'Profitability calculator', 'minemirth' ), 'slug' => 'bitcoin-mining-calculator', 'icon' => 'calculator', 'group' => 'profit', 'desc' => __( 'Daily, monthly and yearly profit for any ASIC with live price, difficulty and fees, plus a 24-month outlook.', 'minemirth' ) ),
		'hashprice'  => array( 'title' => __( 'Hashprice Tracker', 'minemirth' ), 'menu' => __( 'Hashprice tracker', 'minemirth' ), 'slug' => 'hashprice-tracker', 'icon' => 'activity', 'group' => 'market', 'desc' => __( 'What a petahash earns today, how it has moved and where your miner breaks even.', 'minemirth' ) ),
		'breakeven'  => array( 'title' => __( 'Break-even Electricity Calculator', 'minemirth' ), 'menu' => __( 'Break-even power price', 'minemirth' ), 'slug' => 'break-even-electricity-calculator', 'icon' => 'bolt', 'group' => 'profit', 'desc' => __( 'The highest power price your miner can pay and still profit, across price and difficulty scenarios.', 'minemirth' ) ),
		'compare'    => array( 'title' => __( 'ASIC Miner Comparison', 'minemirth' ), 'menu' => __( 'Miner comparison', 'minemirth' ), 'slug' => 'asic-miner-comparison', 'icon' => 'bar-chart', 'group' => 'profit', 'desc' => __( 'Every miner in the store ranked by profit, efficiency, cost per TH and payback at your power price.', 'minemirth' ) ),
		'roi'        => array( 'title' => __( 'ROI & Payback Planner', 'minemirth' ), 'menu' => __( 'ROI & payback planner', 'minemirth' ), 'slug' => 'roi-payback-planner', 'icon' => 'trending-up', 'group' => 'planning', 'desc' => __( 'Bear, base and bull scenarios over 12–36 months, with difficulty growth and the next halving built in.', 'minemirth' ) ),
		'halving'    => array( 'title' => __( 'Bitcoin Halving Countdown & Impact', 'minemirth' ), 'menu' => __( 'Halving countdown', 'minemirth' ), 'slug' => 'bitcoin-halving-countdown', 'icon' => 'gauge', 'group' => 'market', 'desc' => __( 'Live countdown to the next halving and exactly what it does to your revenue and break-even power price.', 'minemirth' ) ),
		'difficulty' => array( 'title' => __( 'Difficulty Adjustment Estimator', 'minemirth' ), 'menu' => __( 'Difficulty estimator', 'minemirth' ), 'slug' => 'difficulty-adjustment-estimator', 'icon' => 'layers', 'group' => 'market', 'desc' => __( 'Progress through the current epoch, the estimated next change and its effect on your earnings.', 'minemirth' ) ),
		'hosting'    => array( 'title' => __( 'Hosting vs Home Mining Calculator', 'minemirth' ), 'menu' => __( 'Hosting vs home', 'minemirth' ), 'slug' => 'hosting-vs-home-mining-calculator', 'icon' => 'home', 'group' => 'planning', 'desc' => __( 'Compare running miners at home with hosting: upfront costs, monthly profit and break-even side by side.', 'minemirth' ) ),
		'power'      => array( 'title' => __( 'Mining Power & Circuit Planner', 'minemirth' ), 'menu' => __( 'Power & circuit planner', 'minemirth' ), 'slug' => 'mining-power-circuit-planner', 'icon' => 'sliders', 'group' => 'planning', 'desc' => __( 'Amps, circuits, breakers, heat output and airflow for one miner or a whole fleet.', 'minemirth' ) ),
		'upgrade'    => array( 'title' => __( 'Miner Upgrade Calculator', 'minemirth' ), 'menu' => __( 'Upgrade or keep?', 'minemirth' ), 'slug' => 'miner-upgrade-calculator', 'icon' => 'refresh', 'group' => 'planning', 'desc' => __( 'Should you replace older miners? Profit unit-for-unit and on the same power budget, with upgrade payback.', 'minemirth' ) ),
		'solo'       => array( 'title' => __( 'Solo Mining Odds Calculator', 'minemirth' ), 'menu' => __( 'Solo mining odds', 'minemirth' ), 'slug' => 'solo-mining-calculator', 'icon' => 'star', 'group' => 'market', 'desc' => __( 'Your real chance of finding a whole Bitcoin block with a Bitaxe, NerdAxe or full-size miner.', 'minemirth' ) ),
		'altcoin'    => array( 'title' => __( 'Litecoin, Dogecoin & Kaspa Mining Calculator', 'minemirth' ), 'menu' => __( 'Altcoin calculator', 'minemirth' ), 'slug' => 'altcoin-mining-calculator', 'icon' => 'hash', 'group' => 'profit', 'desc' => __( 'Scrypt miners earning LTC + DOGE together, and Kaspa miners, with live prices and network hashrate.', 'minemirth' ) ),
	);
}

/**
 * Tools hub URL and the URL of one tool (prefers the page the site builder created).
 */
function mm_tools_hub_url(): string {
	$hub = get_page_by_path( (string) mm_opt( 'tools_hub_slug' ) ?: 'free-calculators' );
	return $hub ? (string) get_permalink( $hub ) : home_url( '/free-calculators/' );
}

function mm_tool_url( string $id ): string {
	$tools = mm_tools();
	if ( empty( $tools[ $id ] ) ) {
		return mm_tools_hub_url();
	}
	$hub  = trim( (string) mm_opt( 'tools_hub_slug' ) ?: 'free-calculators', '/' );
	$page = get_page_by_path( $hub . '/' . $tools[ $id ]['slug'] );
	return $page ? (string) get_permalink( $page ) : home_url( '/' . $hub . '/' . $tools[ $id ]['slug'] . '/' );
}

/* ---------------------------------------------------------------------------
 * Form parts
 * ------------------------------------------------------------------------- */

/**
 * Number input. $a: step, min, max, hint, wide (bool), id.
 */
function mm_tf( string $k, string $label, $value, string $unit = '', array $a = array() ): string {
	$attrs = '';
	foreach ( array( 'step' => 'any', 'min' => '0', 'max' => '' ) as $attr => $def ) {
		$v = $a[ $attr ] ?? $def;
		if ( '' !== (string) $v ) {
			$attrs .= ' ' . $attr . '="' . esc_attr( (string) $v ) . '"';
		}
	}
	return '<label class="mm-tf' . ( ! empty( $a['wide'] ) ? ' mm-tf--wide' : '' ) . '"><span class="mm-tf__label">' . esc_html( $label ) . '</span>'
		. '<span class="mm-tf__control"><input type="number" inputmode="decimal" data-k="' . esc_attr( $k ) . '" value="' . esc_attr( (string) $value ) . '"' . $attrs . '>'
		. ( $unit ? '<span class="mm-tf__unit">' . esc_html( $unit ) . '</span>' : '' ) . '</span>'
		. ( ! empty( $a['hint'] ) ? '<small class="mm-tf__hint">' . esc_html( $a['hint'] ) . '</small>' : '' ) . '</label>';
}

function mm_tselect( string $k, string $label, array $options, string $selected = '', array $a = array() ): string {
	$html = '';
	foreach ( $options as $value => $text ) {
		$html .= '<option value="' . esc_attr( (string) $value ) . '"' . selected( (string) $value, $selected, false ) . '>' . esc_html( $text ) . '</option>';
	}
	return '<label class="mm-tf' . ( ! empty( $a['wide'] ) ? ' mm-tf--wide' : '' ) . '"><span class="mm-tf__label">' . esc_html( $label ) . '</span><span class="mm-tf__control mm-tf__control--select"><select data-k="' . esc_attr( $k ) . '">' . $html . '</select></span>'
		. ( ! empty( $a['hint'] ) ? '<small class="mm-tf__hint">' . esc_html( $a['hint'] ) . '</small>' : '' ) . '</label>';
}

/**
 * Miner picker filled by JavaScript from the store catalog (algorithm-filtered) and the presets.
 */
function mm_tminer( string $k = 'miner', string $algo = 'sha256', string $label = '' ): string {
	return '<label class="mm-tf mm-tf--wide"><span class="mm-tf__label">' . esc_html( $label ? $label : __( 'Miner', 'minemirth' ) ) . '</span><span class="mm-tf__control mm-tf__control--select"><select data-k="' . esc_attr( $k ) . '" data-mm-miners="' . esc_attr( $algo ) . '"><option value="">' . esc_html__( 'Custom miner (enter specs)', 'minemirth' ) . '</option></select></span></label>';
}

function mm_tkpi( string $o, string $label, string $tone = '', string $sub = '' ): string {
	return '<div class="mm-kpi' . ( $tone ? ' mm-kpi--' . esc_attr( $tone ) : '' ) . '"><small>' . esc_html( $label ) . '</small><strong data-o="' . esc_attr( $o ) . '">—</strong>' . ( $sub ? '<span data-o="' . esc_attr( $sub ) . '"></span>' : '' ) . '</div>';
}

/**
 * Editable live market values (BTC price, difficulty, fees), with a reset-to-live button.
 */
function mm_tmarket( array $which = array( 'price', 'diff', 'fees' ) ): string {
	$sym    = mm_currency_symbol( mm_market_currency() );
	$fields = array(
		'price' => mm_tf( 'price', __( 'BTC price', 'minemirth' ), '', $sym, array( 'step' => 'any' ) ),
		'diff'  => mm_tf( 'diff', __( 'Network difficulty', 'minemirth' ), '', 'T', array( 'hint' => __( 'In trillions (T).', 'minemirth' ) ) ),
		'fees'  => mm_tf( 'fees', __( 'Fees per block', 'minemirth' ), '', 'BTC', array( 'step' => '0.001' ) ),
	);
	$out = '';
	foreach ( $which as $f ) {
		$out .= $fields[ $f ] ?? '';
	}
	return '<details class="mm-tool__market"><summary><span class="mm-live-dot" aria-hidden="true"></span><span data-o="live_label">' . esc_html__( 'Live network data', 'minemirth' ) . '</span><span class="mm-tool__market-hint">' . esc_html__( 'Edit to test a scenario', 'minemirth' ) . '</span></summary><div class="mm-tool__fields">' . $out . '</div><button type="button" class="mm-btn mm-btn--ghost mm-btn--sm" data-mm-reset-market>' . esc_html__( 'Reset to live values', 'minemirth' ) . '</button></details>';
}

/**
 * Wrapper shared by every tool.
 */
function mm_tool_shell( string $id, string $inputs, string $results, string $after = '', string $before = '' ): string {
	static $n = 0;
	++$n;
	return '<section class="mm-tool mm-tool--' . esc_attr( $id ) . '" data-mm-tool="' . esc_attr( $id ) . '" id="mm-tool-' . esc_attr( $id ) . ( $n > 1 ? '-' . $n : '' ) . '">'
		. ( $before ? '<div class="mm-tool__before">' . $before . '</div>' : '' )
		. '<div class="mm-tool__grid">'
		. '<form class="mm-tool__panel mm-tool__inputs" novalidate onsubmit="return false">' . $inputs . '</form>'
		. '<div class="mm-tool__panel mm-tool__results" aria-live="polite">' . $results . '</div>'
		. '</div>' . $after
		. '<footer class="mm-tool__foot"><span class="mm-tool__status" data-o="status"></span>'
		. '<button type="button" class="mm-btn mm-btn--ghost mm-btn--sm" data-mm-share>' . mm_icon( 'link' ) . '<span>' . esc_html__( 'Copy link to these numbers', 'minemirth' ) . '</span></button>'
		. '<p class="mm-tool__disclaimer">' . esc_html( (string) mm_opt( 'calc_disclaimer' ) ) . '</p></footer>'
		. '</section>';
}

function mm_tool_chart( string $key, string $label, array $tabs = array() ): string {
	$t = '';
	foreach ( $tabs as $value => $text ) {
		$t .= '<button type="button" data-mm-range="' . esc_attr( (string) $value ) . '">' . esc_html( $text ) . '</button>';
	}
	return '<figure class="mm-chart" data-chart="' . esc_attr( $key ) . '"><figcaption><span>' . esc_html( $label ) . '</span>' . ( $t ? '<span class="mm-chart__tabs">' . $t . '</span>' : '' ) . '</figcaption><div class="mm-chart__plot" role="img" aria-label="' . esc_attr( $label ) . '"></div><div class="mm-chart__legend"></div></figure>';
}

/* ---------------------------------------------------------------------------
 * The tools
 * ------------------------------------------------------------------------- */

function mm_tool_html( string $id ): string {
	$tools = mm_tools();
	if ( empty( $tools[ $id ] ) || ! function_exists( 'mm_tool_' . $id ) ) {
		return '';
	}
	mm_tools_enqueue();
	return (string) call_user_func( 'mm_tool_' . $id );
}

function mm_tools_defaults(): array {
	return array( 'kwh' => (float) mm_opt( 'calc_kwh' ), 'pool' => (float) mm_opt( 'calc_pool_fee' ) );
}

function mm_tool_profit(): string {
	$d   = mm_tools_defaults();
	$sym = mm_currency_symbol( mm_market_currency() );
	$in  = mm_tminer()
		. '<div class="mm-tool__fields">'
		. mm_tf( 'th', __( 'Hashrate', 'minemirth' ), 200, 'TH/s' )
		. mm_tf( 'watts', __( 'Power draw', 'minemirth' ), 3500, 'W' )
		. mm_tf( 'kwh', __( 'Electricity price', 'minemirth' ), $d['kwh'], $sym . '/kWh', array( 'step' => '0.001' ) )
		. mm_tf( 'pool', __( 'Pool fee', 'minemirth' ), $d['pool'], '%', array( 'step' => '0.1', 'max' => 20 ) )
		. mm_tf( 'uptime', __( 'Uptime', 'minemirth' ), 98, '%', array( 'max' => 100 ) )
		. mm_tf( 'cost', __( 'Hardware cost', 'minemirth' ), 0, $sym, array( 'hint' => __( 'For payback and ROI. Optional.', 'minemirth' ) ) )
		. mm_tf( 'growth', __( 'Difficulty growth', 'minemirth' ), 2.5, __( '%/month', 'minemirth' ), array( 'step' => '0.1', 'min' => '-20', 'hint' => __( 'Used for the 24-month outlook. Prefilled from recent history.', 'minemirth' ) ) )
		. '</div>' . mm_tmarket();
	$out = '<div class="mm-tool__hero"><small>' . esc_html__( 'Estimated profit per day', 'minemirth' ) . '</small><strong data-o="profit_day" class="mm-tool__big">—</strong><span data-o="profit_note"></span></div>'
		. '<div class="mm-kpis">' . mm_tkpi( 'revenue_day', __( 'Revenue / day', 'minemirth' ) ) . mm_tkpi( 'power_day', __( 'Power / day', 'minemirth' ) ) . mm_tkpi( 'btc_day', __( 'BTC / day', 'minemirth' ) ) . mm_tkpi( 'eff', __( 'Efficiency', 'minemirth' ) ) . mm_tkpi( 'breakeven', __( 'Break-even power', 'minemirth' ) ) . mm_tkpi( 'payback', __( 'Payback', 'minemirth' ) ) . '</div>'
		. '<div class="mm-table-wrap"><table class="mm-ttable"><thead><tr><th>' . esc_html__( 'Period', 'minemirth' ) . '</th><th>' . esc_html__( 'Revenue', 'minemirth' ) . '</th><th>' . esc_html__( 'Power', 'minemirth' ) . '</th><th>' . esc_html__( 'Profit', 'minemirth' ) . '</th><th>BTC</th></tr></thead><tbody data-o="periods"></tbody></table></div>'
		. mm_tool_chart( 'outlook', __( '24-month outlook: cumulative profit (difficulty growth and the next halving included)', 'minemirth' ) )
		. '<div class="mm-tool__cta" data-o="cta"></div>';
	return mm_tool_shell( 'profit', $in, $out );
}

function mm_tool_hashprice(): string {
	$d   = mm_tools_defaults();
	$sym = mm_currency_symbol( mm_market_currency() );
	$top = '<div class="mm-tool__strip">'
		. '<div class="mm-tool__hero"><small>' . esc_html__( 'Hashprice today', 'minemirth' ) . '</small><strong class="mm-tool__big" data-o="hp_ph">—</strong><span>' . esc_html__( 'per PH/s per day', 'minemirth' ) . ' · <b data-o="hp_th"></b> ' . esc_html__( 'per TH/s', 'minemirth' ) . '</span></div>'
		. '<div class="mm-kpis mm-kpis--4">' . mm_tkpi( 'chg_7', __( '7 days', 'minemirth' ) ) . mm_tkpi( 'chg_30', __( '30 days', 'minemirth' ) ) . mm_tkpi( 'chg_90', __( '90 days', 'minemirth' ) ) . mm_tkpi( 'chg_365', __( '1 year', 'minemirth' ) ) . mm_tkpi( 'btc_ph', __( 'BTC per PH/day', 'minemirth' ) ) . mm_tkpi( 'fee_share', __( 'Fees share of reward', 'minemirth' ) ) . mm_tkpi( 'hp_high', __( '1-year high', 'minemirth' ) ) . mm_tkpi( 'hp_low', __( '1-year low', 'minemirth' ) ) . '</div></div>';
	$chart = mm_tool_chart( 'hashprice', __( 'Hashprice history', 'minemirth' ), array( 30 => '30D', 90 => '90D', 365 => '1Y', 730 => '2Y' ) );
	$in    = '<h3 class="mm-tool__title">' . esc_html__( 'What it means for your miner', 'minemirth' ) . '</h3>' . mm_tminer()
		. '<div class="mm-tool__fields">' . mm_tf( 'th', __( 'Your hashrate', 'minemirth' ), 200, 'TH/s' ) . mm_tf( 'watts', __( 'Power draw', 'minemirth' ), 3500, 'W' ) . mm_tf( 'kwh', __( 'Electricity price', 'minemirth' ), $d['kwh'], $sym . '/kWh', array( 'step' => '0.001' ) ) . '</div>';
	$out   = '<div class="mm-kpis">' . mm_tkpi( 'you_rev', __( 'Your revenue / day', 'minemirth' ) ) . mm_tkpi( 'you_cost', __( 'Your power / day', 'minemirth' ) ) . mm_tkpi( 'you_profit', __( 'Your profit / day', 'minemirth' ), 'accent' ) . mm_tkpi( 'you_be', __( 'Your break-even hashprice', 'minemirth' ) ) . '</div><p class="mm-tool__verdict" data-o="verdict"></p>';
	return mm_tool_shell( 'hashprice', $in, $out, '', $top . $chart );
}

function mm_tool_breakeven(): string {
	$d   = mm_tools_defaults();
	$sym = mm_currency_symbol( mm_market_currency() );
	$in  = mm_tminer() . '<div class="mm-tool__fields">'
		. mm_tf( 'th', __( 'Hashrate', 'minemirth' ), 200, 'TH/s' ) . mm_tf( 'watts', __( 'Power draw', 'minemirth' ), 3500, 'W' )
		. mm_tf( 'pool', __( 'Pool fee', 'minemirth' ), $d['pool'], '%', array( 'step' => '0.1' ) ) . mm_tf( 'uptime', __( 'Uptime', 'minemirth' ), 98, '%', array( 'max' => 100 ) )
		. mm_tf( 'kwh', __( 'Your electricity price', 'minemirth' ), $d['kwh'], $sym . '/kWh', array( 'step' => '0.001', 'hint' => __( 'To see where you stand.', 'minemirth' ) ) )
		. '</div>' . mm_tmarket();
	$out = '<div class="mm-tool__hero"><small>' . esc_html__( 'Break-even electricity price today', 'minemirth' ) . '</small><strong class="mm-tool__big" data-o="be">—</strong><span data-o="be_note"></span></div>'
		. '<div class="mm-gauge" data-o="gauge"></div>'
		. '<h3 class="mm-tool__title">' . esc_html__( 'Break-even power price by scenario', 'minemirth' ) . '</h3><p class="mm-tool__sub">' . esc_html__( 'Rows: BTC price change. Columns: difficulty change. Green cells are above your power price.', 'minemirth' ) . '</p>'
		. '<div class="mm-table-wrap"><table class="mm-ttable mm-ttable--matrix" data-o="matrix"></table></div>';
	return mm_tool_shell( 'breakeven', $in, $out );
}

function mm_tool_compare(): string {
	$d   = mm_tools_defaults();
	$sym = mm_currency_symbol( mm_market_currency() );
	$in  = '<div class="mm-tool__fields mm-tool__fields--row">'
		. mm_tf( 'kwh', __( 'Electricity price', 'minemirth' ), $d['kwh'], $sym . '/kWh', array( 'step' => '0.001' ) )
		. mm_tf( 'pool', __( 'Pool fee', 'minemirth' ), $d['pool'], '%', array( 'step' => '0.1' ) )
		. mm_tselect( 'cooling', __( 'Cooling', 'minemirth' ), array( '' => __( 'All', 'minemirth' ), 'air' => __( 'Air', 'minemirth' ), 'hydro' => __( 'Hydro', 'minemirth' ), 'immersion' => __( 'Immersion', 'minemirth' ) ) )
		. mm_tselect( 'sort', __( 'Sort by', 'minemirth' ), array( 'profit' => __( 'Profit per day', 'minemirth' ), 'eff' => __( 'Efficiency (J/TH)', 'minemirth' ), 'value' => __( 'Price per TH', 'minemirth' ), 'payback' => __( 'Payback', 'minemirth' ), 'th' => __( 'Hashrate', 'minemirth' ) ), 'profit' )
		. mm_tselect( 'stock', __( 'Show', 'minemirth' ), array( 'available' => __( 'Available to buy', 'minemirth' ), 'all' => __( 'All models', 'minemirth' ) ), 'available' )
		. '</div>' . mm_tmarket();
	$out = '<div class="mm-badges-row" data-o="leaders"></div><div class="mm-table-wrap mm-table-wrap--sticky"><table class="mm-ttable mm-ttable--rank"><thead><tr><th>' . esc_html__( 'Compare', 'minemirth' ) . '</th><th>' . esc_html__( 'Miner', 'minemirth' ) . '</th><th>' . esc_html__( 'Hashrate', 'minemirth' ) . '</th><th>' . esc_html__( 'Power', 'minemirth' ) . '</th><th>J/TH</th><th>' . esc_html__( 'Price', 'minemirth' ) . '</th><th>' . esc_html__( '$/TH', 'minemirth' ) . '</th><th>' . esc_html__( 'Profit / day', 'minemirth' ) . '</th><th>' . esc_html__( 'Payback', 'minemirth' ) . '</th></tr></thead><tbody data-o="rows"></tbody></table></div>'
		. '<div class="mm-compare-cards" data-o="picked"></div>';
	return mm_tool_shell( 'compare', $in, $out );
}

function mm_tool_roi(): string {
	$d   = mm_tools_defaults();
	$sym = mm_currency_symbol( mm_market_currency() );
	$in  = mm_tminer() . '<div class="mm-tool__fields">'
		. mm_tf( 'th', __( 'Hashrate', 'minemirth' ), 200, 'TH/s' ) . mm_tf( 'watts', __( 'Power draw', 'minemirth' ), 3500, 'W' )
		. mm_tf( 'cost', __( 'Hardware cost', 'minemirth' ), 4000, $sym ) . mm_tf( 'kwh', __( 'Electricity price', 'minemirth' ), $d['kwh'], $sym . '/kWh', array( 'step' => '0.001' ) )
		. mm_tf( 'pool', __( 'Pool fee', 'minemirth' ), $d['pool'], '%', array( 'step' => '0.1' ) ) . mm_tf( 'uptime', __( 'Uptime', 'minemirth' ), 98, '%', array( 'max' => 100 ) )
		. mm_tselect( 'months', __( 'Horizon', 'minemirth' ), array( 12 => __( '12 months', 'minemirth' ), 18 => __( '18 months', 'minemirth' ), 24 => __( '24 months', 'minemirth' ), 36 => __( '36 months', 'minemirth' ) ), '24' )
		. '</div><div class="mm-seg" role="group" aria-label="' . esc_attr__( 'Scenario', 'minemirth' ) . '"><button type="button" data-mm-scenario="bear">' . esc_html__( 'Bear', 'minemirth' ) . '</button><button type="button" data-mm-scenario="base" class="is-active">' . esc_html__( 'Base', 'minemirth' ) . '</button><button type="button" data-mm-scenario="bull">' . esc_html__( 'Bull', 'minemirth' ) . '</button></div>'
		. '<div class="mm-tool__fields">' . mm_tf( 'pgrowth', __( 'BTC price change', 'minemirth' ), 1, __( '%/month', 'minemirth' ), array( 'step' => '0.1', 'min' => '-30' ) ) . mm_tf( 'growth', __( 'Difficulty growth', 'minemirth' ), 2.5, __( '%/month', 'minemirth' ), array( 'step' => '0.1', 'min' => '-20' ) ) . '</div>'
		. mm_tmarket();
	$out = '<div class="mm-kpis">' . mm_tkpi( 'roi', __( 'ROI', 'minemirth' ), 'accent' ) . mm_tkpi( 'payback', __( 'Payback', 'minemirth' ) ) . mm_tkpi( 'net', __( 'Net profit', 'minemirth' ) ) . mm_tkpi( 'btc', __( 'BTC mined', 'minemirth' ) ) . mm_tkpi( 'month1', __( 'Profit, month 1', 'minemirth' ) ) . mm_tkpi( 'monthN', __( 'Profit, last month', 'minemirth' ) ) . '</div>'
		. mm_tool_chart( 'roi', __( 'Cumulative cash flow (hardware cost at month 0)', 'minemirth' ) )
		. '<p class="mm-tool__verdict" data-o="verdict"></p>'
		. '<div class="mm-table-wrap"><table class="mm-ttable"><thead><tr><th>' . esc_html__( 'Month', 'minemirth' ) . '</th><th>BTC</th><th>' . esc_html__( 'BTC price', 'minemirth' ) . '</th><th>' . esc_html__( 'Revenue', 'minemirth' ) . '</th><th>' . esc_html__( 'Power', 'minemirth' ) . '</th><th>' . esc_html__( 'Profit', 'minemirth' ) . '</th><th>' . esc_html__( 'Cumulative', 'minemirth' ) . '</th></tr></thead><tbody data-o="months"></tbody></table></div>';
	return mm_tool_shell( 'roi', $in, $out );
}

function mm_tool_halving(): string {
	$d   = mm_tools_defaults();
	$sym = mm_currency_symbol( mm_market_currency() );
	$cd  = '<div class="mm-countdown" data-o="countdown" aria-label="' . esc_attr__( 'Time until the next halving', 'minemirth' ) . '">';
	foreach ( array( 'd' => __( 'Days', 'minemirth' ), 'h' => __( 'Hours', 'minemirth' ), 'm' => __( 'Minutes', 'minemirth' ), 's' => __( 'Seconds', 'minemirth' ) ) as $k => $label ) {
		$cd .= '<div><strong data-o="cd_' . $k . '">—</strong><small>' . esc_html( $label ) . '</small></div>';
	}
	$cd .= '</div>';
	$top = '<div class="mm-tool__strip mm-tool__strip--halving">' . $cd
		. '<div class="mm-tprogress"><div class="mm-tprogress__bar"><span data-o="epoch_bar"></span></div><div class="mm-tprogress__labels"><span data-o="epoch_pct"></span><span data-o="eta"></span></div></div>'
		. '<div class="mm-kpis mm-kpis--4">' . mm_tkpi( 'blocks_left', __( 'Blocks to go', 'minemirth' ) ) . mm_tkpi( 'next_height', __( 'Halving block', 'minemirth' ) ) . mm_tkpi( 'subsidy_now', __( 'Reward now', 'minemirth' ) ) . mm_tkpi( 'subsidy_next', __( 'Reward after', 'minemirth' ) ) . '</div></div>';
	$in  = '<h3 class="mm-tool__title">' . esc_html__( 'Impact on your miner', 'minemirth' ) . '</h3>' . mm_tminer() . '<div class="mm-tool__fields">'
		. mm_tf( 'th', __( 'Hashrate', 'minemirth' ), 200, 'TH/s' ) . mm_tf( 'watts', __( 'Power draw', 'minemirth' ), 3500, 'W' ) . mm_tf( 'kwh', __( 'Electricity price', 'minemirth' ), $d['kwh'], $sym . '/kWh', array( 'step' => '0.001' ) ) . mm_tf( 'pool', __( 'Pool fee', 'minemirth' ), $d['pool'], '%', array( 'step' => '0.1' ) )
		. '</div>' . mm_tmarket();
	$out = '<div class="mm-versus"><div><small>' . esc_html__( 'Today', 'minemirth' ) . '</small><strong data-o="now_profit">—</strong><span data-o="now_rev"></span></div><div class="mm-versus__arrow">' . mm_icon( 'arrow-right' ) . '</div><div><small>' . esc_html__( 'After the halving', 'minemirth' ) . '</small><strong data-o="after_profit">—</strong><span data-o="after_rev"></span></div></div>'
		. '<div class="mm-kpis">' . mm_tkpi( 'be_now', __( 'Break-even power today', 'minemirth' ) ) . mm_tkpi( 'be_after', __( 'Break-even after', 'minemirth' ) ) . mm_tkpi( 'price_needed', __( 'BTC price to keep today’s revenue', 'minemirth' ), 'accent' ) . '</div><p class="mm-tool__verdict" data-o="verdict"></p>'
		. '<h3 class="mm-tool__title">' . esc_html__( 'Every halving so far', 'minemirth' ) . '</h3><div class="mm-table-wrap"><table class="mm-ttable"><thead><tr><th>' . esc_html__( 'Date', 'minemirth' ) . '</th><th>' . esc_html__( 'Block', 'minemirth' ) . '</th><th>' . esc_html__( 'Reward (BTC)', 'minemirth' ) . '</th></tr></thead><tbody>'
		. '<tr><td>' . esc_html__( 'Nov 28, 2012', 'minemirth' ) . '</td><td>210,000</td><td>50 → 25</td></tr>'
		. '<tr><td>' . esc_html__( 'Jul 9, 2016', 'minemirth' ) . '</td><td>420,000</td><td>25 → 12.5</td></tr>'
		. '<tr><td>' . esc_html__( 'May 11, 2020', 'minemirth' ) . '</td><td>630,000</td><td>12.5 → 6.25</td></tr>'
		. '<tr><td>' . esc_html__( 'Apr 20, 2024', 'minemirth' ) . '</td><td>840,000</td><td>6.25 → 3.125</td></tr>'
		. '<tr class="is-next"><td data-o="next_date">' . esc_html__( '≈ 2028', 'minemirth' ) . '</td><td>1,050,000</td><td>3.125 → 1.5625</td></tr></tbody></table></div>';
	return mm_tool_shell( 'halving', $in, $out, '', $top );
}

function mm_tool_difficulty(): string {
	$top = '<div class="mm-tool__strip">'
		. '<div class="mm-tool__hero"><small>' . esc_html__( 'Estimated next adjustment', 'minemirth' ) . '</small><strong class="mm-tool__big" data-o="adj">—</strong><span data-o="adj_when"></span></div>'
		. '<div class="mm-tprogress"><div class="mm-tprogress__bar"><span data-o="epoch_bar"></span></div><div class="mm-tprogress__labels"><span data-o="epoch_pct"></span><span data-o="blocks_left"></span></div></div>'
		. '<div class="mm-kpis mm-kpis--4">' . mm_tkpi( 'diff_now', __( 'Difficulty now', 'minemirth' ) ) . mm_tkpi( 'diff_next', __( 'Estimated next', 'minemirth' ) ) . mm_tkpi( 'block_time', __( 'Average block time', 'minemirth' ) ) . mm_tkpi( 'hashrate', __( 'Network hashrate', 'minemirth' ) ) . '</div></div>'
		. mm_tool_chart( 'difficulty', __( 'Difficulty history', 'minemirth' ), array( 90 => '90D', 365 => '1Y', 730 => '2Y' ) );
	$d   = mm_tools_defaults();
	$sym = mm_currency_symbol( mm_market_currency() );
	$in  = '<h3 class="mm-tool__title">' . esc_html__( 'Your earnings before and after', 'minemirth' ) . '</h3>' . mm_tminer() . '<div class="mm-tool__fields">'
		. mm_tf( 'th', __( 'Hashrate', 'minemirth' ), 200, 'TH/s' ) . mm_tf( 'watts', __( 'Power draw', 'minemirth' ), 3500, 'W' ) . mm_tf( 'kwh', __( 'Electricity price', 'minemirth' ), $d['kwh'], $sym . '/kWh', array( 'step' => '0.001' ) )
		. mm_tf( 'custom', __( 'Or try your own change', 'minemirth' ), '', '%', array( 'step' => '0.1', 'min' => '-50', 'hint' => __( 'Leave empty to use the live estimate.', 'minemirth' ) ) ) . '</div>';
	$out = '<div class="mm-versus"><div><small>' . esc_html__( 'Now', 'minemirth' ) . '</small><strong data-o="now_profit">—</strong><span data-o="now_rev"></span></div><div class="mm-versus__arrow">' . mm_icon( 'arrow-right' ) . '</div><div><small>' . esc_html__( 'After the adjustment', 'minemirth' ) . '</small><strong data-o="after_profit">—</strong><span data-o="after_rev"></span></div></div><p class="mm-tool__verdict" data-o="verdict"></p>';
	return mm_tool_shell( 'difficulty', $in, $out, '', $top );
}

function mm_tool_hosting(): string {
	$d     = mm_tools_defaults();
	$sym   = mm_currency_symbol( mm_market_currency() );
	$rates = mm_hosting_rates();
	$rate  = $rates ? $rates[0]['rate'] : 0.075;
	$in    = mm_tminer() . '<div class="mm-tool__fields">'
		. mm_tf( 'th', __( 'Hashrate per miner', 'minemirth' ), 200, 'TH/s' ) . mm_tf( 'watts', __( 'Power per miner', 'minemirth' ), 3500, 'W' )
		. mm_tf( 'qty', __( 'Number of miners', 'minemirth' ), 2, '', array( 'step' => 1, 'min' => 1 ) ) . mm_tf( 'cost', __( 'Price per miner', 'minemirth' ), 4000, $sym )
		. mm_tselect( 'months', __( 'Compare over', 'minemirth' ), array( 12 => __( '12 months', 'minemirth' ), 24 => __( '24 months', 'minemirth' ), 36 => __( '36 months', 'minemirth' ) ), '24' )
		. mm_tf( 'pool', __( 'Pool fee', 'minemirth' ), $d['pool'], '%', array( 'step' => '0.1' ) )
		. '</div>'
		. '<fieldset class="mm-tool__group"><legend>' . mm_icon( 'home' ) . esc_html__( 'At home', 'minemirth' ) . '</legend><div class="mm-tool__fields">'
		. mm_tf( 'home_kwh', __( 'Home electricity', 'minemirth' ), max( 0.12, $d['kwh'] ), $sym . '/kWh', array( 'step' => '0.001' ) )
		. mm_tf( 'home_cooling', __( 'Cooling & fans overhead', 'minemirth' ), 10, '%', array( 'hint' => __( 'Extra power for ventilation or AC.', 'minemirth' ) ) )
		. mm_tf( 'home_setup', __( 'One-off setup', 'minemirth' ), 600, $sym, array( 'hint' => __( 'Wiring, outlets, ducting, sound box.', 'minemirth' ) ) )
		. mm_tf( 'home_uptime', __( 'Uptime', 'minemirth' ), 95, '%', array( 'max' => 100 ) )
		. '</div></fieldset>'
		. '<fieldset class="mm-tool__group"><legend>' . mm_icon( 'server' ) . esc_html__( 'Hosted', 'minemirth' ) . '</legend><div class="mm-tool__fields">'
		. mm_tf( 'host_kwh', __( 'Hosting rate (all-in)', 'minemirth' ), $rate, $sym . '/kWh', array( 'step' => '0.001', 'hint' => $rates ? __( 'From your current hosting plans.', 'minemirth' ) : '' ) )
		. mm_tf( 'host_setup', __( 'Setup fee per miner', 'minemirth' ), 50, $sym ) . mm_tf( 'host_ship', __( 'Shipping per miner', 'minemirth' ), 120, $sym )
		. mm_tf( 'host_uptime', __( 'Uptime', 'minemirth' ), 98, '%', array( 'max' => 100 ) )
		. '</div></fieldset>' . mm_tmarket();
	$out   = '<div class="mm-versus mm-versus--two"><div><small>' . mm_icon( 'home' ) . esc_html__( 'Home: profit / month', 'minemirth' ) . '</small><strong data-o="home_month">—</strong><span data-o="home_sub"></span></div><div><small>' . mm_icon( 'server' ) . esc_html__( 'Hosted: profit / month', 'minemirth' ) . '</small><strong data-o="host_month">—</strong><span data-o="host_sub"></span></div></div>'
		. '<p class="mm-tool__verdict" data-o="verdict"></p>'
		. mm_tool_chart( 'hosting', __( 'Cumulative profit after upfront costs', 'minemirth' ) )
		. '<div class="mm-table-wrap"><table class="mm-ttable mm-ttable--compact"><thead><tr><th></th><th>' . esc_html__( 'Home', 'minemirth' ) . '</th><th>' . esc_html__( 'Hosted', 'minemirth' ) . '</th></tr></thead><tbody data-o="table"></tbody></table></div>'
		. '<div class="mm-tool__cta" data-o="cta"></div>';
	return mm_tool_shell( 'hosting', $in, $out );
}

function mm_tool_power(): string {
	$sym = mm_currency_symbol( mm_market_currency() );
	$d   = mm_tools_defaults();
	$in  = mm_tminer( 'miner', 'any' ) . '<div class="mm-tool__fields">'
		. mm_tf( 'watts', __( 'Power per miner', 'minemirth' ), 3500, 'W' ) . mm_tf( 'qty', __( 'Number of miners', 'minemirth' ), 4, '', array( 'step' => 1, 'min' => 1 ) )
		. mm_tselect( 'volts', __( 'Supply voltage', 'minemirth' ), array( '120' => '120 V · ' . __( 'single phase', 'minemirth' ), '208' => '208 V · ' . __( 'single phase', 'minemirth' ), '220' => '220 V · ' . __( 'single phase', 'minemirth' ), '230' => '230 V · ' . __( 'single phase', 'minemirth' ), '240' => '240 V · ' . __( 'single phase', 'minemirth' ), '277' => '277 V · ' . __( 'single phase', 'minemirth' ), '208-3' => '208 V · ' . __( '3-phase', 'minemirth' ), '400-3' => '400 V · ' . __( '3-phase', 'minemirth' ), '415-3' => '415 V · ' . __( '3-phase', 'minemirth' ), '480-3' => '480 V · ' . __( '3-phase', 'minemirth' ) ), '240' )
		. mm_tselect( 'breaker', __( 'Breaker size', 'minemirth' ), array( 15 => '15 A', 20 => '20 A', 30 => '30 A', 40 => '40 A', 50 => '50 A', 60 => '60 A', 100 => '100 A' ), '30' )
		. mm_tf( 'pf', __( 'Power factor', 'minemirth' ), 0.98, '', array( 'step' => '0.01', 'max' => 1, 'hint' => __( 'Most ASIC PSUs: 0.95–0.99.', 'minemirth' ) ) )
		. mm_tf( 'dt', __( 'Allowed air temperature rise', 'minemirth' ), 20, '°F', array( 'hint' => __( 'Intake to exhaust. 20 °F ≈ 11 °C.', 'minemirth' ) ) )
		. mm_tf( 'kwh', __( 'Electricity price', 'minemirth' ), $d['kwh'], $sym . '/kWh', array( 'step' => '0.001' ) )
		. '</div>';
	$out = '<div class="mm-kpis">' . mm_tkpi( 'kw', __( 'Total load', 'minemirth' ), 'accent' ) . mm_tkpi( 'amps', __( 'Total current', 'minemirth' ) ) . mm_tkpi( 'per_circuit', __( 'Miners per circuit', 'minemirth' ) ) . mm_tkpi( 'circuits', __( 'Circuits needed', 'minemirth' ) ) . mm_tkpi( 'kwh_month', __( 'Energy / month', 'minemirth' ) ) . mm_tkpi( 'cost_month', __( 'Power cost / month', 'minemirth' ) ) . mm_tkpi( 'btu', __( 'Heat output', 'minemirth' ) ) . mm_tkpi( 'tons', __( 'Cooling needed', 'minemirth' ) ) . mm_tkpi( 'cfm', __( 'Airflow needed', 'minemirth' ) ) . '</div>'
		. '<div class="mm-tool__notes" data-o="notes"></div>';
	return mm_tool_shell( 'power', $in, $out );
}

function mm_tool_upgrade(): string {
	$d   = mm_tools_defaults();
	$sym = mm_currency_symbol( mm_market_currency() );
	$in  = '<fieldset class="mm-tool__group"><legend>' . esc_html__( 'Miners you run now', 'minemirth' ) . '</legend>' . mm_tminer( 'old_miner', 'sha256', __( 'Current model', 'minemirth' ) ) . '<div class="mm-tool__fields">'
		. mm_tf( 'old_th', __( 'Hashrate', 'minemirth' ), 110, 'TH/s' ) . mm_tf( 'old_watts', __( 'Power draw', 'minemirth' ), 3250, 'W' ) . mm_tf( 'qty', __( 'How many', 'minemirth' ), 10, '', array( 'step' => 1, 'min' => 1 ) ) . mm_tf( 'resale', __( 'Resale value each', 'minemirth' ), 300, $sym )
		. '</div></fieldset>'
		. '<fieldset class="mm-tool__group"><legend>' . esc_html__( 'Upgrade to', 'minemirth' ) . '</legend>' . mm_tminer( 'new_miner', 'sha256', __( 'New model', 'minemirth' ) ) . '<div class="mm-tool__fields">'
		. mm_tf( 'new_th', __( 'Hashrate', 'minemirth' ), 234, 'TH/s' ) . mm_tf( 'new_watts', __( 'Power draw', 'minemirth' ), 3510, 'W' ) . mm_tf( 'new_cost', __( 'Price each', 'minemirth' ), 4300, $sym )
		. '</div></fieldset><div class="mm-tool__fields">'
		. mm_tf( 'kwh', __( 'Electricity price', 'minemirth' ), $d['kwh'], $sym . '/kWh', array( 'step' => '0.001' ) ) . mm_tf( 'pool', __( 'Pool fee', 'minemirth' ), $d['pool'], '%', array( 'step' => '0.1' ) )
		. '</div>' . mm_tmarket();
	$out = '<div class="mm-seg" role="group"><button type="button" data-mm-mode="power" class="is-active">' . esc_html__( 'Same power budget', 'minemirth' ) . '</button><button type="button" data-mm-mode="unit">' . esc_html__( 'Unit for unit', 'minemirth' ) . '</button></div>'
		. '<div class="mm-versus mm-versus--two"><div><small>' . esc_html__( 'Keep: profit / day', 'minemirth' ) . '</small><strong data-o="old_profit">—</strong><span data-o="old_sub"></span></div><div><small>' . esc_html__( 'Upgrade: profit / day', 'minemirth' ) . '</small><strong data-o="new_profit">—</strong><span data-o="new_sub"></span></div></div>'
		. '<div class="mm-kpis">' . mm_tkpi( 'gain', __( 'Extra profit / month', 'minemirth' ), 'accent' ) . mm_tkpi( 'net_cost', __( 'Net upgrade cost', 'minemirth' ) ) . mm_tkpi( 'payback', __( 'Upgrade pays back in', 'minemirth' ) ) . mm_tkpi( 'eff_gain', __( 'Efficiency gain', 'minemirth' ) ) . '</div>'
		. '<p class="mm-tool__verdict" data-o="verdict"></p><div class="mm-tool__cta" data-o="cta"></div>';
	return mm_tool_shell( 'upgrade', $in, $out );
}

function mm_tool_solo(): string {
	$d   = mm_tools_defaults();
	$sym = mm_currency_symbol( mm_market_currency() );
	$in  = '<div class="mm-chips" data-mm-solo-presets></div>' . mm_tminer( 'miner', 'sha256' ) . '<div class="mm-tool__fields">'
		. mm_tf( 'hash', __( 'Your hashrate', 'minemirth' ), 1.2, '', array( 'step' => 'any' ) )
		. mm_tselect( 'unit', __( 'Unit', 'minemirth' ), array( 'gh' => 'GH/s', 'th' => 'TH/s', 'ph' => 'PH/s' ), 'th' )
		. mm_tf( 'watts', __( 'Power draw', 'minemirth' ), 18, 'W' ) . mm_tf( 'kwh', __( 'Electricity price', 'minemirth' ), $d['kwh'], $sym . '/kWh', array( 'step' => '0.001' ) )
		. '</div>' . mm_tmarket();
	$out = '<div class="mm-tool__hero"><small>' . esc_html__( 'Chance of finding a block in a year', 'minemirth' ) . '</small><strong class="mm-tool__big" data-o="p_year">—</strong><span data-o="odds_year"></span></div>'
		. '<div class="mm-odds" data-o="odds_bar"></div>'
		. '<div class="mm-kpis">' . mm_tkpi( 'p_day', __( 'Chance per day', 'minemirth' ) ) . mm_tkpi( 'p_month', __( 'Chance per month', 'minemirth' ) ) . mm_tkpi( 'expected', __( 'Expected time to a block', 'minemirth' ) ) . mm_tkpi( 'prize', __( 'Block reward value', 'minemirth' ), 'accent' ) . mm_tkpi( 'ev_year', __( 'Expected value / year', 'minemirth' ) ) . mm_tkpi( 'power_year', __( 'Power cost / year', 'minemirth' ) ) . '</div>'
		. '<p class="mm-tool__verdict" data-o="verdict"></p>';
	return mm_tool_shell( 'solo', $in, $out );
}

function mm_tool_altcoin(): string {
	$d   = mm_tools_defaults();
	$sym = mm_currency_symbol( mm_market_currency() );
	$in  = '<div class="mm-seg" role="group" aria-label="' . esc_attr__( 'Coin', 'minemirth' ) . '"><button type="button" data-mm-coin="scrypt" class="is-active">' . esc_html__( 'Scrypt · LTC + DOGE', 'minemirth' ) . '</button><button type="button" data-mm-coin="kas">' . esc_html__( 'Kaspa · KAS', 'minemirth' ) . '</button></div>'
		. mm_tminer( 'miner', 'scrypt' ) . '<div class="mm-tool__fields">'
		. mm_tf( 'hash', __( 'Hashrate', 'minemirth' ), 9.5, '', array( 'step' => 'any' ) )
		. mm_tselect( 'unit', __( 'Unit', 'minemirth' ), array( 'mh' => 'MH/s', 'gh' => 'GH/s', 'th' => 'TH/s' ), 'gh' )
		. mm_tf( 'watts', __( 'Power draw', 'minemirth' ), 3425, 'W' ) . mm_tf( 'kwh', __( 'Electricity price', 'minemirth' ), $d['kwh'], $sym . '/kWh', array( 'step' => '0.001' ) )
		. mm_tf( 'pool', __( 'Pool fee', 'minemirth' ), $d['pool'], '%', array( 'step' => '0.1' ) )
		. '</div>'
		. '<details class="mm-tool__market"><summary><span class="mm-live-dot" aria-hidden="true"></span><span data-o="live_label">' . esc_html__( 'Live network data', 'minemirth' ) . '</span><span class="mm-tool__market-hint">' . esc_html__( 'Edit to test a scenario', 'minemirth' ) . '</span></summary>'
		. '<div class="mm-tool__fields" data-mm-coin-fields="scrypt">' . mm_tf( 'ltc_price', __( 'LTC price', 'minemirth' ), '', $sym ) . mm_tf( 'doge_price', __( 'DOGE price', 'minemirth' ), '', $sym ) . mm_tf( 'ltc_net', __( 'Litecoin network', 'minemirth' ), '', 'TH/s' ) . mm_tf( 'doge_net', __( 'Dogecoin network', 'minemirth' ), '', 'TH/s' ) . mm_tf( 'ltc_reward', __( 'LTC per block', 'minemirth' ), 6.25, 'LTC' ) . '</div>'
		. '<div class="mm-tool__fields" data-mm-coin-fields="kas" hidden>' . mm_tf( 'kas_price', __( 'KAS price', 'minemirth' ), '', $sym ) . mm_tf( 'kas_net', __( 'Kaspa network', 'minemirth' ), '', 'PH/s' ) . mm_tf( 'kas_day', __( 'New KAS per day', 'minemirth' ), '', 'KAS' ) . '</div>'
		. '<button type="button" class="mm-btn mm-btn--ghost mm-btn--sm" data-mm-reset-market>' . esc_html__( 'Reset to live values', 'minemirth' ) . '</button></details>';
	$out = '<div class="mm-tool__hero"><small>' . esc_html__( 'Estimated profit per day', 'minemirth' ) . '</small><strong class="mm-tool__big" data-o="profit_day">—</strong><span data-o="profit_note"></span></div>'
		. '<div class="mm-coins" data-o="coins"></div>'
		. '<div class="mm-kpis">' . mm_tkpi( 'revenue_day', __( 'Revenue / day', 'minemirth' ) ) . mm_tkpi( 'power_day', __( 'Power / day', 'minemirth' ) ) . mm_tkpi( 'profit_month', __( 'Profit / month', 'minemirth' ), 'accent' ) . mm_tkpi( 'breakeven', __( 'Break-even power', 'minemirth' ) ) . mm_tkpi( 'eff', __( 'Efficiency', 'minemirth' ) ) . mm_tkpi( 'share', __( 'Share of network', 'minemirth' ) ) . '</div>'
		. '<p class="mm-tool__verdict" data-o="verdict"></p>';
	return mm_tool_shell( 'altcoin', $in, $out );
}

/* ---------------------------------------------------------------------------
 * Assets, shortcodes, legacy compatibility
 * ------------------------------------------------------------------------- */

function mm_tools_enqueue(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	wp_enqueue_style( 'mm-tools' );
	wp_enqueue_script( 'mm-tools' );
	$payload = mm_tools_payload( false, true );
	$i18n    = array(
		'live'      => __( 'Live network data', 'minemirth' ),
		'edited'    => __( 'Your scenario (edited values)', 'minemirth' ),
		'stale'     => __( 'Last known network data', 'minemirth' ),
		'offline'   => __( 'Live data unavailable: enter values to calculate', 'minemirth' ),
		'updated'   => __( 'Updated', 'minemirth' ),
		'copied'    => __( 'Link copied', 'minemirth' ),
		'days'      => __( 'days', 'minemirth' ),
		'months'    => __( 'months', 'minemirth' ),
		'years'     => __( 'years', 'minemirth' ),
		'never'     => __( 'Not at these numbers', 'minemirth' ),
		'custom'    => __( 'Custom miner (enter specs)', 'minemirth' ),
		'inStore'   => __( 'In our store', 'minemirth' ),
		'presets'   => __( 'Popular models', 'minemirth' ),
		'profit'    => __( 'Profitable', 'minemirth' ),
		'loss'      => __( 'Losing money', 'minemirth' ),
		'day'       => __( 'Day', 'minemirth' ),
		'week'      => __( 'Week', 'minemirth' ),
		'month'     => __( 'Month', 'minemirth' ),
		'year'      => __( 'Year', 'minemirth' ),
		'shopLike'  => __( 'Shop miners like this', 'minemirth' ),
		'viewMiner' => __( 'View this miner', 'minemirth' ),
		'hosting'   => __( 'See hosting plans', 'minemirth' ),
		'quote'     => __( 'Get a quote', 'minemirth' ),
		'addCost'   => __( 'Add hardware cost', 'minemirth' ),
	);
	wp_add_inline_script( 'mm-tools', 'window.mmTools=' . wp_json_encode( $payload ) . ';window.mmToolsI18n=' . wp_json_encode( $i18n ) . ';window.mmToolsApi=' . wp_json_encode( esc_url_raw( rest_url( 'mine-mirth/v1/tools' ) ) ) . ';', 'before' );
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		$ver = MM_VERSION;
		wp_register_style( 'mm-tools', MM_URI . '/assets/css/tools.css', array( 'mm-main' ), $ver );
		wp_register_script( 'mm-tools', MM_URI . '/assets/js/tools.js', array( 'mm-theme' ), $ver, array( 'strategy' => 'defer', 'in_footer' => true ) );
	},
	5
);

add_shortcode(
	'mm_tool',
	static function ( $atts ) {
		$atts = shortcode_atts( array( 'id' => 'profit' ), $atts, 'mm_tool' );
		return mm_tool_html( sanitize_key( $atts['id'] ) );
	}
);

/**
 * Grid of tool cards (hub page, "more tools" sections). $exclude: a tool id to leave out.
 */
function mm_tools_grid_html( string $exclude = '', int $limit = 0, string $group = '' ): string {
	$cards = '';
	$i     = 0;
	foreach ( mm_tools() as $id => $t ) {
		if ( $id === $exclude || ( $group && $t['group'] !== $group ) ) {
			continue;
		}
		if ( $limit && ++$i > $limit ) {
			break;
		}
		$cards .= '<a class="mm-tool-card" href="' . esc_url( mm_tool_url( $id ) ) . '" data-mm-reveal><span class="mm-tool-card__icon">' . mm_icon( $t['icon'] ) . '</span><span class="mm-tool-card__body"><strong>' . esc_html( $t['menu'] ) . '</strong><span>' . esc_html( $t['desc'] ) . '</span></span><span class="mm-tool-card__go">' . mm_icon( 'arrow-right' ) . '</span></a>';
	}
	wp_enqueue_style( 'mm-tools' );
	return '<div class="mm-tool-cards">' . $cards . '</div>';
}
add_shortcode( 'mm_tools_grid', static fn( $a ) => mm_tools_grid_html( (string) ( $a['exclude'] ?? '' ), (int) ( $a['limit'] ?? 0 ), (string) ( $a['group'] ?? '' ) ) );

// Old shortcodes keep working: the theme calculator and the retired tool plugins (when inactive).
add_action(
	'init',
	static function () {
		$map = array(
			'mm_calculator'                 => 'profit',
			'mm_hosting_calculator'         => 'hosting',
			'mine_mirth_hosting_calculator' => 'hosting',
			'minemirth_rig_optimizer'       => 'compare',
			'minemirth_market_scope'        => 'hashprice',
		);
		foreach ( $map as $tag => $tool ) {
			if ( in_array( $tag, array( 'mm_calculator', 'mm_hosting_calculator', 'mine_mirth_hosting_calculator' ), true ) || ! shortcode_exists( $tag ) ) {
				add_shortcode( $tag, static fn() => mm_tool_html( $tool ) );
			}
		}
		if ( ! shortcode_exists( 'minemirth_tools' ) ) {
			add_shortcode( 'minemirth_tools', static fn() => mm_tools_grid_html() );
		}
	},
	99
);

/**
 * JSON-LD for a tool page.
 */
function mm_tool_schema( string $id ): void {
	$tools = mm_tools();
	if ( empty( $tools[ $id ] ) || ! function_exists( 'mm_schema_enabled' ) || ! mm_schema_enabled() ) {
		return;
	}
	$data = array(
		'@context'            => 'https://schema.org',
		'@type'               => 'WebApplication',
		'name'                => $tools[ $id ]['title'],
		'description'         => $tools[ $id ]['desc'],
		'url'                 => mm_tool_url( $id ),
		'applicationCategory' => 'FinanceApplication',
		'operatingSystem'     => 'Any',
		'offers'              => array( '@type' => 'Offer', 'price' => '0', 'priceCurrency' => strtoupper( mm_market_currency() ) ),
		'publisher'           => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

/**
 * Which tool a page shows: the "_mm_tool" meta (set by the site builder), else a page whose slug matches a tool.
 */
function mm_page_tool_id( int $post_id = 0 ): string {
	$post_id = $post_id ? $post_id : (int) get_the_ID();
	$id      = sanitize_key( (string) get_post_meta( $post_id, '_mm_tool', true ) );
	$tools   = mm_tools();
	if ( $id && isset( $tools[ $id ] ) ) {
		return $id;
	}
	$slug = (string) get_post_field( 'post_name', $post_id );
	foreach ( $tools as $tid => $t ) {
		if ( $t['slug'] === $slug ) {
			return $tid;
		}
	}
	return '';
}

add_action(
	'wp_head',
	static function () {
		if ( is_page() && ( $id = mm_page_tool_id() ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
			mm_tool_schema( $id );
		}
	},
	20
);

/**
 * Full tool page: hero, the tool, the page's own content (guide, FAQ) and the other tools.
 */
function mm_render_tool_page( string $id ): void {
	$tools = mm_tools();
	$tool  = $tools[ $id ] ?? null;
	mm_page_hero(
		array(
			'eyebrow' => __( 'Free tool · live network data', 'minemirth' ),
			'title'   => get_the_title(),
			'text'    => has_excerpt() ? get_the_excerpt() : ( $tool ? $tool['desc'] : '' ),
			'class'   => 'mm-page-hero--tool',
		)
	);
	?>
	<main id="mm-main" class="mm-main mm-tool-page" tabindex="-1">
		<div class="mm-container" style="padding-top:clamp(20px,3vw,40px)">
			<?php echo $tool ? mm_tool_html( $id ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
			<div class="mm-container mm-container--prose">
				<div class="entry-content mm-prose mm-tool-page__content"><?php the_content(); ?></div>
			</div>
		<?php endif; ?>
		<section class="mm-section mm-section--alt mm-tool-page__more">
			<div class="mm-container">
				<div class="mm-section-head">
					<div class="mm-section-head__copy">
						<span class="mm-eyebrow"><?php esc_html_e( 'Keep planning', 'minemirth' ); ?></span>
						<h2 class="mm-section-title"><?php echo mm_highlight( __( 'More *free mining tools*', 'minemirth' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
					</div>
					<a class="mm-btn mm-btn--ghost" href="<?php echo esc_url( mm_tools_hub_url() ); ?>"><?php esc_html_e( 'All tools', 'minemirth' ); ?><?php echo mm_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				</div>
				<?php echo mm_tools_grid_html( $id, 6 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</section>
	</main>
	<?php
}
