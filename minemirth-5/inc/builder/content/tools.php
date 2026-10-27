<?php
/**
 * Content: tools hub, and the guide text and FAQ shown under each of the 12 tools.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_tool_page = static function ( array $parts, array $faq, array $related = array() ): string {
	$out = '';
	foreach ( $parts as $p ) {
		$out .= $p;
	}
	$out .= mm_bfaq( $faq, '', array( 'open_first' => false ) );
	if ( $related ) {
		$items = array();
		foreach ( $related as $r ) {
			$def     = mm_builder_pages()[ $r ] ?? array();
			$items[] = array( 'icon' => $def['icon'] ?? 'book', 'title' => $def['t'] ?? $r, 'text' => $def['x'] ?? '', 'url' => '{page:' . $r . '}' );
		}
		$out .= mm_bs( 'links', array( 'eyebrow' => __( 'Go deeper', 'minemirth' ), 'title' => __( 'Related *guides*', 'minemirth' ), 'columns' => '2', 'items' => $items ) );
	}
	return $out;
};

return array(

	'tools'           => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Free tools · live data', 'minemirth' ),
				'title'   => __( 'Twelve mining tools that use *today’s network*', 'minemirth' ),
				'text'    => __( 'Every calculator starts from the live Bitcoin price, difficulty and fees, our real miner catalog and your power price. Change any value to test a scenario, and share the link with your exact numbers.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Profit calculator', 'minemirth' ), '{tool:profit}', 'primary', 'calculator' ), array( __( 'Hashprice tracker', 'minemirth' ), '{tool:hashprice}', 'glass', 'activity' ) ) ),
				'badges'  => array( __( 'Live price, difficulty and fees', 'minemirth' ), __( 'Every miner we stock built in', 'minemirth' ), __( 'Shareable results', 'minemirth' ) ),
				'visual'  => 'market',
			)
		)
		. mm_bs( 'tools', array( 'eyebrow' => __( 'Profit & comparison', 'minemirth' ), 'title' => __( 'Will it *pay*?', 'minemirth' ), 'group' => 'profit' ) )
		. mm_bs( 'tools', array( 'eyebrow' => __( 'Market & network', 'minemirth' ), 'title' => __( 'What is the network *doing*?', 'minemirth' ), 'group' => 'market', 'tone' => 'alt' ) )
		. mm_bs( 'tools', array( 'eyebrow' => __( 'Planning', 'minemirth' ), 'title' => __( 'How should I *set it up*?', 'minemirth' ), 'group' => 'planning' ) )
		. mm_bs( 'market', array( 'eyebrow' => __( 'Inputs', 'minemirth' ), 'title' => __( 'The live numbers *behind every tool*', 'minemirth' ), 'tone' => 'alt' ) )
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'How the tools work', 'minemirth' ),
				'title'   => __( 'Transparent maths, *no black boxes*', 'minemirth' ),
				'columns' => '3',
				'items'   => mm_bitems(
					array(
						array( 'activity', __( 'Live inputs', 'minemirth' ), __( 'Bitcoin price from CoinGecko; difficulty, hashrate, fees and block height from mempool.space, refreshed every few minutes and cached for speed.', 'minemirth' ) ),
						array( 'calculator', __( 'Standard formulas', 'minemirth' ), __( 'Expected BTC = your hashrate ÷ (difficulty × 2³²) × seconds × block reward. Power cost = watts × hours × your rate. Nothing hidden.', 'minemirth' ) ),
						array( 'sliders', __( 'Your scenario', 'minemirth' ), __( 'Edit price, difficulty or fees to model the future. Projections add difficulty growth and the next halving.', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bfaq(
			array(
				array( __( 'How accurate are the calculators?', 'minemirth' ), __( 'They give the expected value at the numbers shown. Real results vary with luck, pool fees, uptime, the miner’s actual hashrate and power draw, and future changes in price and difficulty.', 'minemirth' ) ),
				array( __( 'Why does my pool pay a little differently?', 'minemirth' ), __( 'Pools use different payout schemes and fee structures, and some include transaction fees differently. See <a href="{page:g-payouts}">pool payouts explained</a>.', 'minemirth' ) ),
				array( __( 'Can I embed a calculator on my site?', 'minemirth' ), __( 'Link to the tool page with your parameters using “Copy link to these numbers”.', 'minemirth' ) ),
			)
		)
		. mm_bcta( __( 'Numbers look good? *Find the miner.*', 'minemirth' ), __( 'Every model in the shop shows live profit at today’s numbers.', 'minemirth' ), array( array( __( 'Shop miners', 'minemirth' ), '{shop}' ), array( __( 'Compare miners', 'minemirth' ), '{tool:compare}' ) ) );
	},

	'tool-profit'     => static function () use ( $mm_tool_page ) {
		return $mm_tool_page(
			array(
				mm_bh( __( 'How the profitability calculator works', 'minemirth' ) ),
				mm_bp( __( 'Pick a miner from our catalog or enter its hashrate and power draw, then set your electricity price. The calculator takes the live Bitcoin price, network difficulty and average fees per block, works out how much BTC your share of the network earns, and subtracts your power cost and pool fee.', 'minemirth' ) ),
				mm_bl(
					array(
						__( '<strong>Revenue per day</strong> = hashrate × 86,400 × block reward ÷ (difficulty × 2³²) × BTC price, after the pool fee and uptime.', 'minemirth' ),
						__( '<strong>Power per day</strong> = watts ÷ 1,000 × 24 × uptime × your $/kWh.', 'minemirth' ),
						__( '<strong>Break-even power</strong> is the electricity price at which revenue equals power cost.', 'minemirth' ),
						__( '<strong>Payback</strong> uses the 24-month outlook, so it includes difficulty growth and the next halving.', 'minemirth' ),
					)
				),
				mm_bh( __( 'Reading the 24-month outlook', 'minemirth' ) ),
				mm_bp( __( 'The chart compares two lines: today’s numbers held flat (the dashed line) and a projection with monthly difficulty growth and the halving. The gap between them is why a miner that looks great today can take longer to pay back. The growth rate is prefilled from recent network history; change it to test faster or slower growth.', 'minemirth' ) ),
				mm_bcall( 'info', __( 'Tip', 'minemirth' ), __( 'Use “Copy link to these numbers” to save a scenario or send it to us for a second opinion.', 'minemirth' ) ),
			),
			array(
				array( __( 'What electricity price should I enter?', 'minemirth' ), __( 'Your all-in rate per kWh, including delivery charges and taxes. For hosting, use the plan’s all-in rate.', 'minemirth' ) ),
				array( __( 'Why is uptime 98% by default?', 'minemirth' ), __( 'Real miners lose some time to reboots, maintenance and network issues. 95–99% is typical for well-run setups.', 'minemirth' ) ),
				array( __( 'Does it include transaction fees?', 'minemirth' ), __( 'Yes. Average fees per block are added to the 3.125 BTC subsidy, from live data.', 'minemirth' ) ),
				array( __( 'How do I calculate profit in my currency?', 'minemirth' ), __( 'The calculator works in the site’s currency. Convert your power price to that currency before entering it.', 'minemirth' ) ),
			),
			array( 'g-profit', 'g-hashprice' )
		);
	},

	'tool-hashprice'  => static function () use ( $mm_tool_page ) {
		return $mm_tool_page(
			array(
				mm_bh( __( 'What hashprice tells you', 'minemirth' ) ),
				mm_bp( __( 'Hashprice is the expected revenue of one unit of hashrate per day. It folds the Bitcoin price, network difficulty and fees into a single number, so you can compare days, months and years at a glance. Multiply it by your hashrate and you have your daily revenue.', 'minemirth' ) ),
				mm_bl(
					array(
						__( '<strong>Price up</strong> → hashprice up.', 'minemirth' ),
						__( '<strong>Difficulty up</strong> → hashprice down, because the same work earns a smaller share.', 'minemirth' ),
						__( '<strong>Fees up</strong> → hashprice up, because each block is worth more.', 'minemirth' ),
						__( '<strong>Halving</strong> → hashprice roughly halves overnight unless price or fees move.', 'minemirth' ),
					)
				),
				mm_bh( __( 'Your break-even hashprice', 'minemirth' ) ),
				mm_bp( __( 'Your break-even hashprice is your power cost per PH/s per day: watts per PH × 24 × your rate. Hashprice above it means profit; below it means loss. Efficient miners have a lower break-even, which is why they survive downturns.', 'minemirth' ) ),
				mm_bp( __( 'History builds automatically from public data and the site’s own daily records.', 'minemirth' ) ),
			),
			array(
				array( __( 'Why is hashprice quoted per PH/s?', 'minemirth' ), __( 'It is the industry convention and gives readable numbers. Divide by 1,000 for $/TH/s per day.', 'minemirth' ) ),
				array( __( 'What was hashprice before the 2024 halving?', 'minemirth' ), __( 'It roughly halved at the April 2024 halving as the subsidy fell from 6.25 to 3.125 BTC. Use the 2Y chart to see the change.', 'minemirth' ) ),
			),
			array( 'g-hashprice', 'g-difficulty' )
		);
	},

	'tool-breakeven'  => static function () use ( $mm_tool_page ) {
		return $mm_tool_page(
			array(
				mm_bh( __( 'Why break-even power price is the key number', 'minemirth' ) ),
				mm_bp( __( 'Your break-even electricity price is the most you can pay per kWh before your miner stops making money. It depends only on the miner’s efficiency and today’s hashprice, so it’s a quick way to decide whether a miner suits your power.', 'minemirth' ) ),
				mm_bp( __( 'The scenario table shows how break-even moves if Bitcoin’s price changes (rows) or difficulty rises (columns). Green cells are above your power price, so the miner stays profitable in that scenario.', 'minemirth' ) ),
				mm_bh( __( 'Using the result', 'minemirth' ) ),
				mm_bl(
					array(
						__( 'Keep a margin: aim for break-even well above your rate so a price dip or difficulty jump doesn’t push you into a loss.', 'minemirth' ),
						__( 'If your rate is close to break-even, consider a more efficient miner, underclocking or hosting.', 'minemirth' ),
						__( 'Check the halving: break-even roughly halves when the subsidy halves.', 'minemirth' ),
					)
				),
			),
			array(
				array( __( 'Is a 20% margin enough?', 'minemirth' ), __( 'It depends on your payback horizon. Difficulty has historically risen over time, so a bigger margin buys more time.', 'minemirth' ) ),
				array( __( 'Does break-even include the miner’s price?', 'minemirth' ), __( 'No. It is about running costs only. Use the profit calculator or ROI planner for payback.', 'minemirth' ) ),
			),
			array( 'g-profit', 'g-power' )
		);
	},

	'tool-compare'    => static function () use ( $mm_tool_page ) {
		return $mm_tool_page(
			array(
				mm_bh( __( 'Compare every miner at your power price', 'minemirth' ) ),
				mm_bp( __( 'The table lists the Bitcoin miners in our catalog with live profit per day at your electricity price, efficiency in J/TH, price per TH and payback. Sort by what matters to you, filter by cooling, and tick up to three miners to compare them side by side.', 'minemirth' ) ),
				mm_bh( __( 'Which column should decide?', 'minemirth' ) ),
				mm_bl(
					array(
						__( '<strong>Profit per day</strong>: what the miner earns now after power.', 'minemirth' ),
						__( '<strong>Payback</strong>: how quickly the price comes back, at today’s numbers.', 'minemirth' ),
						__( '<strong>J/TH</strong>: resilience when hashprice falls; lower is better.', 'minemirth' ),
						__( '<strong>$/TH</strong>: how much capacity your budget buys.', 'minemirth' ),
					)
				),
				mm_bp( __( 'Payback in this table assumes today’s numbers; the ROI planner adds difficulty growth and the halving.', 'minemirth' ) ),
			),
			array(
				array( __( 'Why are some miners greyed out?', 'minemirth' ), __( 'Those lose money at the power price you entered.', 'minemirth' ) ),
				array( __( 'Are sold-out models included?', 'minemirth' ), __( 'Choose “All models” under Show to include them for reference.', 'minemirth' ) ),
			),
			array( 'g-choose', 'm-efficient' )
		);
	},

	'tool-roi'        => static function () use ( $mm_tool_page ) {
		return $mm_tool_page(
			array(
				mm_bh( __( 'Plan payback with scenarios, not hope', 'minemirth' ) ),
				mm_bp( __( 'The ROI planner projects a miner month by month: BTC mined, BTC price, revenue, power and cumulative cash flow from the day you buy it. It includes monthly difficulty growth and the next halving.', 'minemirth' ) ),
				mm_bl(
					array(
						__( '<strong>Bear</strong>: BTC price falls 3% a month while difficulty grows 3.5% a month.', 'minemirth' ),
						__( '<strong>Base</strong>: price grows 1% a month, difficulty 2.5% a month.', 'minemirth' ),
						__( '<strong>Bull</strong>: price grows 4% a month, difficulty 2% a month.', 'minemirth' ),
					)
				),
				mm_bp( __( 'These are illustrative assumptions, not forecasts. Change them to your own view.', 'minemirth' ) ),
				mm_bh( __( 'What good payback looks like', 'minemirth' ) ),
				mm_bp( __( 'Many miners aim to recover the hardware cost before the next halving or within 12–18 months, because revenue per TH tends to fall over time. Any value left at the end (resale or continued mining) is upside.', 'minemirth' ) ),
			),
			array(
				array( __( 'Does ROI include resale value?', 'minemirth' ), __( 'No. Resale value at the end is extra. Older generations lose value as newer, more efficient miners arrive.', 'minemirth' ) ),
				array( __( 'Why does monthly profit fall over time?', 'minemirth' ), __( 'Difficulty growth means each TH earns a smaller share of the reward each month, and the halving cuts the subsidy.', 'minemirth' ) ),
			),
			array( 'g-profit', 'g-halving' )
		);
	},

	'tool-halving'    => static function () use ( $mm_tool_page ) {
		return $mm_tool_page(
			array(
				mm_bh( __( 'What the halving does to miners', 'minemirth' ) ),
				mm_bp( __( 'Every 210,000 blocks, roughly every four years, the Bitcoin block subsidy halves. The next halving, at block 1,050,000, cuts it from 3.125 to 1.5625 BTC. Fees are unaffected, so revenue falls by a little less than half if price and difficulty stay the same.', 'minemirth' ) ),
				mm_bp( __( 'The countdown estimates the date from the blocks left and the current block time. The impact panel shows your miner’s daily revenue, profit and break-even power price today and after the halving, plus the BTC price that would keep revenue the same.', 'minemirth' ) ),
				mm_bh( __( 'How miners prepare', 'minemirth' ) ),
				mm_bl(
					array(
						__( 'Upgrade to more efficient miners before the event.', 'minemirth' ),
						__( 'Lock in cheaper power or move to hosting.', 'minemirth' ),
						__( 'Plan for difficulty to drop if older miners switch off, which partly offsets the cut.', 'minemirth' ),
					)
				),
			),
			array(
				array( __( 'When is the next halving?', 'minemirth' ), __( 'Around {halving_date}, depending on block times. The countdown above updates live.', 'minemirth' ) ),
				array( __( 'Does the price always rise after a halving?', 'minemirth' ), __( 'Past cycles have seen rises in the following months, but past performance is no guarantee. Plan for a range of outcomes.', 'minemirth' ) ),
			),
			array( 'g-halving', 'g-profit' )
		);
	},

	'tool-difficulty' => static function () use ( $mm_tool_page ) {
		return $mm_tool_page(
			array(
				mm_bh( __( 'How difficulty adjustments work', 'minemirth' ) ),
				mm_bp( __( 'Bitcoin targets one block every ten minutes. Every 2,016 blocks (about two weeks) the network compares how long those blocks actually took and adjusts difficulty to bring the average back to ten minutes. If blocks came faster than ten minutes, difficulty rises; if slower, it falls.', 'minemirth' ) ),
				mm_bp( __( 'The estimate above is based on the average block time so far in the current epoch, so it settles as the epoch progresses. Enter your own percentage to test a different change.', 'minemirth' ) ),
				mm_bh( __( 'What it means for you', 'minemirth' ) ),
				mm_bp( __( 'Revenue per TH moves inversely with difficulty: a +3% adjustment cuts your BTC earned by about 3% at the same price. Over a year, many small increases add up, which is why projections include difficulty growth.', 'minemirth' ) ),
			),
			array(
				array( __( 'Can difficulty go down?', 'minemirth' ), __( 'Yes, when hashrate leaves the network, for example after a price drop, a halving or heat waves that curtail miners.', 'minemirth' ) ),
				array( __( 'Is there a limit to each adjustment?', 'minemirth' ), __( 'The protocol limits a single adjustment to a factor of four either way. In practice, changes are usually within a few percent.', 'minemirth' ) ),
			),
			array( 'g-difficulty', 'g-hashprice' )
		);
	},

	'tool-hosting'    => static function () use ( $mm_tool_page ) {
		return $mm_tool_page(
			array(
				mm_bh( __( 'Hosting or home: what the calculator compares', 'minemirth' ) ),
				mm_bp( __( 'On the home side, enter your electricity price, extra power for cooling and fans, one-off setup costs (wiring, outlets, ducting) and realistic uptime. On the hosted side, the all-in rate, setup fee and shipping per miner. The calculator projects both over 12–36 months with difficulty growth and the halving.', 'minemirth' ) ),
				mm_bh( __( 'What the numbers don’t show', 'minemirth' ) ),
				mm_bl(
					array(
						__( 'Noise: each air-cooled ASIC runs at 70–80 dB.', 'minemirth' ),
						__( 'Heat: every watt becomes heat that must leave the room, or be reused in winter.', 'minemirth' ),
						__( 'Your time for monitoring, cleaning and fixing.', 'minemirth' ),
						__( 'Access: at home you can touch the miner any time.', 'minemirth' ),
					)
				),
			),
			array(
				array( __( 'Is hosting always cheaper?', 'minemirth' ), __( 'No. With very cheap home power and a good space, home mining can win. Hosting tends to win on residential tariffs above industrial rates, and on noise and heat.', 'minemirth' ) ),
				array( __( 'Where does the hosting rate come from?', 'minemirth' ), __( 'It is prefilled from our current hosting plans. Enter any provider’s all-in rate to compare.', 'minemirth' ) ),
			),
			array( 'g-hosting', 'g-home' )
		);
	},

	'tool-power'      => static function () use ( $mm_tool_page ) {
		return $mm_tool_page(
			array(
				mm_bh( __( 'Planning circuits for ASIC miners', 'minemirth' ) ),
				mm_bp( __( 'Current = watts ÷ (volts × power factor) for single-phase, or watts ÷ (√3 × volts × power factor) for three-phase. Breakers for continuous loads such as miners should only be loaded to 80% of their rating, so a 30 A breaker carries 24 A of miners.', 'minemirth' ) ),
				mm_bp( __( 'The planner also turns power into heat: every watt becomes 3.412 BTU per hour. It estimates the cooling capacity in tons and the airflow in CFM needed to hold the temperature rise you choose.', 'minemirth' ) ),
				mm_bcall( 'warning', __( 'Safety first', 'minemirth' ), __( 'These figures are for planning conversations only. Circuits, cable sizes, breakers and receptacles must be designed and installed by a licensed electrician to your local electrical code.', 'minemirth' ) ),
			),
			array(
				array( __( 'Can I run a miner on 120 V?', 'minemirth' ), __( 'Most full-size ASIC power supplies need 200–240 V. Small home and solo miners run on 120 V outlets.', 'minemirth' ) ),
				array( __( 'What cord do I need?', 'minemirth' ), __( 'One rated for the current and matching the PSU inlet (often C13 or C19). See the <a href="{page:g-power}">power & electrical guide</a>.', 'minemirth' ) ),
			),
			array( 'g-power', 'g-cooling' )
		);
	},

	'tool-upgrade'    => static function () use ( $mm_tool_page ) {
		return $mm_tool_page(
			array(
				mm_bh( __( 'When to replace older miners', 'minemirth' ) ),
				mm_bp( __( 'Newer miners produce more hashrate per watt. If your site is limited by power, replacing old units with new ones on the same power budget can multiply your hashrate without raising your bill. If you have spare power, comparing unit for unit is more useful.', 'minemirth' ) ),
				mm_bl(
					array(
						__( '<strong>Same power budget</strong>: how many new miners fit in the power your old ones use.', 'minemirth' ),
						__( '<strong>Unit for unit</strong>: replace each old miner with one new miner.', 'minemirth' ),
						__( '<strong>Net cost</strong>: new miners minus the resale or trade-in value of the old ones.', 'minemirth' ),
					)
				),
				mm_bp( __( 'An upgrade that pays back its net cost before the next halving is usually a strong case.', 'minemirth' ) ),
			),
			array(
				array( __( 'What resale value should I use?', 'minemirth' ), __( 'Ask us for a <a href="{page:tradein}">trade-in offer</a>, or check recent sale prices for your model and condition.', 'minemirth' ) ),
			),
			array( 'tradein', 'g-choose' )
		);
	},

	'tool-solo'       => static function () use ( $mm_tool_page ) {
		return $mm_tool_page(
			array(
				mm_bh( __( 'How solo odds are calculated', 'minemirth' ) ),
				mm_bp( __( 'Each hash has a tiny, fixed chance of finding a block: 1 in (difficulty × 2³²). Multiply by your hashrate and the 600 seconds of an average block and you get your chance per block. Over a day (about 144 blocks) or a year (about 52,560 blocks), those chances add up.', 'minemirth' ) ),
				mm_bp( __( 'The expected time to a block is the average waiting time: it could happen tomorrow or never. The expected value per year equals what the same hashrate would earn in a pool, minus fees; solo just pays it all at once.', 'minemirth' ) ),
			),
			array(
				array( __( 'Has anyone found a block with a Bitaxe?', 'minemirth' ), __( 'Yes, small solo miners have found blocks. It’s rare, which is what makes it exciting.', 'minemirth' ) ),
				array( __( 'Do I need my own node?', 'minemirth' ), __( 'No. A solo pool builds block templates for you. Running your own node gives you full control.', 'minemirth' ) ),
			),
			array( 'g-solo', 'm-solo' )
		);
	},

	'tool-altcoin'    => static function () use ( $mm_tool_page ) {
		return $mm_tool_page(
			array(
				mm_bh( __( 'Scrypt: Litecoin and Dogecoin together', 'minemirth' ) ),
				mm_bp( __( 'Your share of each network’s hashrate decides your share of its rewards. Litecoin pays 6.25 LTC every ~2.5 minutes (576 blocks a day) and Dogecoin 10,000 DOGE every minute (1,440 blocks a day). Because the coins are merge-mined, the same hashrate earns both.', 'minemirth' ) ),
				mm_bh( __( 'Kaspa', 'minemirth' ) ),
				mm_bp( __( 'Kaspa’s daily emission falls each month on a smooth curve that halves it about once a year. The calculator uses the current daily emission and live network hashrate; your share of the hashrate is your share of the day’s KAS.', 'minemirth' ) ),
				mm_bcall( 'info', __( 'Network hashrate', 'minemirth' ), __( 'Altcoin network hashrate is estimated from public explorers and can be noisy. Edit it if your pool shows a different figure.', 'minemirth' ) ),
			),
			array(
				array( __( 'Why does DOGE network hashrate match LTC?', 'minemirth' ), __( 'Most Scrypt hashrate merge-mines both chains, so their hashrates are similar. The site owner can adjust the ratio.', 'minemirth' ) ),
				array( __( 'Can I mine other Scrypt coins?', 'minemirth' ), __( 'Yes, many pools add other merge-mined coins as a bonus. The calculator counts LTC and DOGE only.', 'minemirth' ) ),
			),
			array( 'm-scrypt', 'm-kaspa' )
		);
	},
);
