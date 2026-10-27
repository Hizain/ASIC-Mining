<?php
/**
 * Content: Learn hub and the "Getting started & economics" guides.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

return array(

	'learn'        => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Learn', 'minemirth' ),
				'title'   => __( 'Mining guides for *better miners*', 'minemirth' ),
				'text'    => __( 'Twenty-four practical mining guides written for people who actually run miners: the economics, the electrics, cooling and noise, pools and firmware, farms, buying used, security and tax. Every guide uses live network numbers.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Start with the basics', 'minemirth' ), '{page:g-beginners}' ), array( __( 'Mining glossary', 'minemirth' ), '{page:g-glossary}', 'glass', 'list' ) ) ),
				'visual'  => 'orbit',
				'icon'    => 'book',
				'chips'   => mm_bchips( array( array( 'trending-up', '6', __( 'economics guides', 'minemirth' ) ), array( 'fan', '7', __( 'setup guides', 'minemirth' ) ), array( 'users', '5', __( 'operations guides', 'minemirth' ) ), array( 'container', '6', __( 'business guides', 'minemirth' ) ) ) ),
			)
		)
		. mm_bs( 'links', array( 'eyebrow' => __( 'All guides', 'minemirth' ), 'title' => __( 'Pick a *topic*', 'minemirth' ), 'auto' => 'children', 'columns' => '3' ) )
		. mm_bs( 'stats', array( 'eyebrow' => __( 'Today’s numbers', 'minemirth' ), 'title' => __( 'The guides use *live data*', 'minemirth' ), 'live' => true, 'tone' => 'alt', 'items' => array( array( 'value' => '{btc_price}', 'label' => __( 'Bitcoin price', 'minemirth' ) ), array( 'value' => '{hashprice}', 'label' => __( 'Hashprice / PH / day', 'minemirth' ) ), array( 'value' => '{difficulty}', 'label' => __( 'Difficulty', 'minemirth' ) ), array( 'value' => '{halving_days}', 'label' => __( 'Days to the halving', 'minemirth' ) ) ) ) )
		. mm_bs( 'tools', array( 'eyebrow' => __( 'Practice', 'minemirth' ), 'title' => __( 'Try the ideas in the *calculators*', 'minemirth' ), 'limit' => 6, 'link_label' => __( 'All tools', 'minemirth' ), 'link_url' => '{page:tools}' ) )
		. mm_bs( 'posts', array( 'eyebrow' => __( 'Blog', 'minemirth' ), 'title' => __( 'Latest *articles*', 'minemirth' ), 'limit' => 3, 'tone' => 'alt' ) )
		. mm_bs( 'newsletter', array( 'eyebrow' => __( 'Weekly', 'minemirth' ), 'title' => __( 'One useful mining lesson *a week*', 'minemirth' ), 'text' => __( 'Plus the week’s hashprice and difficulty, in two minutes.', 'minemirth' ), 'context' => 'learn' ) );
	},

	'g-beginners'  => static function () {
		return mm_bp( __( 'Bitcoin mining is the process that adds new blocks to the Bitcoin blockchain and issues new bitcoin. Miners run specialised computers that compete to find a valid block; the winner earns the block reward. This guide explains how it works, what you need, what it costs and how to start without expensive mistakes.', 'minemirth' ) )
		. mm_bh( __( 'How mining works in one minute', 'minemirth' ) )
		. mm_bp( __( 'Every ten minutes or so, the network accepts a new block of transactions. To create one, a miner must find a number that makes the block’s SHA-256 hash fall below a target set by the network. There is no shortcut: miners try trillions of guesses per second. Whoever finds a valid hash first broadcasts the block and collects the reward.', 'minemirth' ) )
		. mm_bp( __( 'The reward has two parts: the <strong>block subsidy</strong> (3.125 BTC since the April 2024 halving) and the <strong>transaction fees</strong> paid by users, currently about {fees} per block on average.', 'minemirth' ) )
		. mm_bh( __( 'Why you need an ASIC', 'minemirth' ) )
		. mm_bp( __( 'Mining is a race of hashrate. Ordinary computers and graphics cards are millions of times too slow for Bitcoin today. ASICs (application-specific integrated circuits) are chips built to do only SHA-256, and a modern ASIC miner performs hundreds of terahashes (trillions of hashes) per second.', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Hashrate</strong> (TH/s): how many guesses per second. More hashrate, bigger share of rewards.', 'minemirth' ),
				__( '<strong>Power</strong> (W): how much electricity it draws, typically 3,000 to 3,600 W for a full-size miner.', 'minemirth' ),
				__( '<strong>Efficiency</strong> (J/TH): power ÷ hashrate. The lower, the cheaper each coin is to produce.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Pools: earning steadily', 'minemirth' ) )
		. mm_bp( __( 'With the network running at {hashrate}, a single miner might wait many years to find a block alone. So almost everyone mines in a <strong>pool</strong>: thousands of miners combine hashrate and the pool pays each one in proportion to the work they contribute, usually daily. You keep control: the pool only sends work and payments, and you choose your own payout address.', 'minemirth' ) )
		. mm_bh( __( 'What it costs', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>The miner</strong>: a one-off purchase. Prices move with the market and the model’s efficiency.', 'minemirth' ),
				__( '<strong>Electricity</strong>: the big ongoing cost. A 3,500 W miner uses 84 kWh a day, so at $0.10/kWh that is $8.40 a day.', 'minemirth' ),
				__( '<strong>Setup</strong>: a 200 to 240 V circuit, ventilation or cooling, and an internet connection. Or a hosting fee if you host.', 'minemirth' ),
				__( '<strong>Pool fees</strong>: usually 0 to 4% of earnings.', 'minemirth' ),
			)
		)
		. mm_bs( 'tool', array( 'title' => __( 'Try it: what would a miner earn *today*?', 'minemirth' ), 'id' => 'profit' ) )
		. mm_bh( __( 'Five steps to your first miner', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Know your power price.</strong> Find your all-in rate per kWh on your bill. It decides everything else.', 'minemirth' ),
				__( '<strong>Decide where it will run.</strong> Home (noise, heat, wiring) or <a href="{page:hosting}">hosting</a> (no noise, industrial power).', 'minemirth' ),
				__( '<strong>Choose a miner.</strong> Use the <a href="{tool:compare}">comparison tool</a> at your power price. See <a href="{page:g-choose}">how to choose an ASIC</a>.', 'minemirth' ),
				__( '<strong>Set up a wallet and a pool account.</strong> Use a wallet you control. See <a href="{page:g-pool}">choosing a pool</a>.', 'minemirth' ),
				__( '<strong>Plug in and monitor.</strong> Point the miner at the pool, check hashrate and temperatures, and keep it clean.', 'minemirth' ),
			),
			true
		)
		. mm_bcall( 'warning', __( 'Avoid these beginner mistakes', 'minemirth' ), __( 'Buying an old, inefficient miner because it is cheap; running a 3 kW miner on a household extension cord; ignoring the noise and heat; trusting “guaranteed returns” cloud-mining offers; and forgetting that difficulty usually rises over time.', 'minemirth' ) )
		. mm_bh( __( 'Is it worth it?', 'minemirth' ) )
		. mm_bp( __( 'Mining is a business with thin margins at times and strong ones at others. It pays best with cheap power, efficient hardware and patience through price swings. It is also a way to acquire bitcoin without buying it on an exchange, and to support the network’s security. Run the numbers, start small if you’re unsure, and learn as you go.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'Can I mine Bitcoin on my laptop or phone?', 'minemirth' ), __( 'Technically yes, but the earnings would be effectively zero. Bitcoin mining needs ASICs.', 'minemirth' ) ),
				array( __( 'How much Bitcoin does one miner make a day?', 'minemirth' ), __( 'Roughly your hashrate × today’s hashprice ({hashprice} per PH/s). A 200 TH/s miner is 0.2 PH/s. The <a href="{tool:profit}">profit calculator</a> gives exact figures.', 'minemirth' ) ),
				array( __( 'Is mining legal?', 'minemirth' ), __( 'In most countries, yes, but rules differ. Check local regulations, electricity contracts and tax rules.', 'minemirth' ) ),
			)
		);
	},

	'g-choose'     => static function () {
		return mm_bp( __( 'Choosing an ASIC miner comes down to a handful of numbers and a few practical questions about where it will run. This framework helps you shortlist the right model for your power price, space and budget, and avoid the common traps.', 'minemirth' ) )
		. mm_bh( __( 'Step 1: start with your electricity price', 'minemirth' ) )
		. mm_bp( __( 'Power is the cost you pay every day, so it decides which miners can be profitable for you. Use your all-in price per kWh, including delivery charges and taxes. Then find the break-even power price of the miners you’re considering: anything comfortably above your rate is a candidate.', 'minemirth' ) )
		. mm_bs( 'tool', array( 'title' => __( 'Break-even power price for *any miner*', 'minemirth' ), 'id' => 'breakeven' ) )
		. mm_bh( __( 'Step 2: compare efficiency (J/TH)', 'minemirth' ) )
		. mm_bp( __( 'Efficiency is power divided by hashrate. A miner at 15 J/TH uses 40% less electricity per terahash than one at 25 J/TH. The more expensive your power, the more efficiency matters, and efficient miners stay profitable longer as difficulty rises and after the halving.', 'minemirth' ) )
		. mm_bh( __( 'Step 3: compare price per terahash', 'minemirth' ) )
		. mm_bp( __( 'Divide the miner’s price by its hashrate to get $/TH. This is what you pay for capacity and it drives payback time. Newer, more efficient models usually cost more per TH; older ones less. With very cheap power, a low $/TH can beat a low J/TH.', 'minemirth' ) )
		. mm_bs(
			'table',
			array(
				'title'   => __( 'Efficiency vs value: *which wins*?', 'minemirth' ),
				'columns' => array( __( 'Your power price', 'minemirth' ), __( 'Prioritise', 'minemirth' ), __( 'Why', 'minemirth' ) ),
				'rows'    => array(
					__( 'Below ~$0.04/kWh | Price per TH | Power is a small share of costs; capacity per dollar wins', 'minemirth' ),
					__( '$0.04 to $0.07/kWh | A balance | Look for good $/TH among efficient models', 'minemirth' ),
					__( 'Above ~$0.07/kWh | Efficiency (J/TH) | Power dominates; only efficient miners keep a margin', 'minemirth' ),
				),
			)
		)
		. mm_bh( __( 'Step 4: match the cooling to your site', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Air-cooled</strong>: simplest. Needs strong airflow and tolerates dust poorly. Loud (70 to 80 dB).', 'minemirth' ),
				__( '<strong>Hydro</strong>: water cold plates. Quiet, dense and very efficient, but needs a coolant loop and heat rejection.', 'minemirth' ),
				__( '<strong>Immersion</strong>: miners submerged in dielectric fluid. Silent, protects hardware and allows overclocking; needs tanks and engineering.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Step 5: check power and noise requirements', 'minemirth' ) )
		. mm_bp( __( 'Full-size miners need 200 to 240 V (some up to 277 V) and roughly 13 to 16 A each on a dedicated circuit, plus a plan for 70 to 80 dB of noise. If you can’t provide that, look at <a href="{page:m-home}">home miners</a> or <a href="{page:hosting}">hosting</a>.', 'minemirth' ) )
		. mm_bh( __( 'Step 6: think about the whole life of the miner', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Payback before the halving</strong>: model it with the <a href="{tool:roi}">ROI planner</a>.', 'minemirth' ),
				__( '<strong>Warranty and support</strong>: new units carry manufacturer cover; know how claims work.', 'minemirth' ),
				__( '<strong>Resale value</strong>: popular, efficient models hold value better.', 'minemirth' ),
				__( '<strong>Availability</strong>: in stock now versus a pre-order batch months away.', 'minemirth' ),
			)
		)
		. mm_bcall( 'success', __( 'Quick rule of thumb', 'minemirth' ), __( 'Shortlist miners whose break-even power price is at least 30 to 50% above your rate, then pick the one with the best payback that you can actually power, cool and live with.', 'minemirth' ) )
		. mm_bs( 'tool', array( 'title' => __( 'Shortlist from *our stock*', 'minemirth' ), 'id' => 'compare' ) )
		. mm_bfaq(
			array(
				array( __( 'Is a higher hashrate always better?', 'minemirth' ), __( 'Only if efficiency and price per TH are also good. A big, inefficient miner can earn less profit than a smaller efficient one.', 'minemirth' ) ),
				array( __( 'New or used?', 'minemirth' ), __( 'New gives warranty and the latest efficiency; used can be excellent value if tested. See <a href="{page:g-used}">buying used miners</a>.', 'minemirth' ) ),
				array( __( 'Bitcoin or an altcoin miner?', 'minemirth' ), __( 'Bitcoin has the deepest market and most predictable rules. Altcoins can pay more at times but carry more price and liquidity risk.', 'minemirth' ) ),
			)
		);
	},

	'g-profit'     => static function () {
		return mm_bp( __( 'Mining profit is simple arithmetic with a few moving parts: what your hashrate earns, what your electricity costs, and how both change over time. This guide walks through the formula, a worked example and the factors that make projections go wrong.', 'minemirth' ) )
		. mm_bh( __( 'The profit formula', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Revenue</strong> = your hashrate × hashprice × (1 − pool fee) × uptime.', 'minemirth' ),
				__( '<strong>Power cost</strong> = watts ÷ 1,000 × 24 hours × your $/kWh.', 'minemirth' ),
				__( '<strong>Profit</strong> = revenue − power cost (− hosting fees if you host).', 'minemirth' ),
			)
		)
		. mm_bp( __( 'Hashprice, the expected earnings of one PH/s per day, is {hashprice} right now. It already combines Bitcoin’s price, network difficulty and fees.', 'minemirth' ) )
		. mm_bh( __( 'A worked example', 'minemirth' ) )
		. mm_bp( __( 'Take a miner with 200 TH/s at 3,500 W (17.5 J/TH), a 2% pool fee, 98% uptime and power at $0.07/kWh:', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Hashrate = 0.2 PH/s, so gross revenue = 0.2 × hashprice per day.', 'minemirth' ),
				__( 'Power = 3.5 kW × 24 h = 84 kWh a day → $5.88 at $0.07.', 'minemirth' ),
				__( 'Break-even hashprice for this miner = $5.88 ÷ 0.2 = about $29.40 per PH/s per day.', 'minemirth' ),
			)
		)
		. mm_bp( __( 'If hashprice is above about $29.40, this miner makes money at $0.07/kWh. The calculator below runs the same maths with today’s live numbers.', 'minemirth' ) )
		. mm_bs( 'tool', array( 'title' => __( 'Your *profit* at live numbers', 'minemirth' ), 'id' => 'profit' ) )
		. mm_bh( __( 'What changes over time', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Difficulty</strong> tends to rise as new miners join. Each rise cuts your share. Many models assume 1 to 4% a month; check recent history in the <a href="{tool:difficulty}">difficulty estimator</a>.', 'minemirth' ),
				__( '<strong>Bitcoin price</strong> moves revenue directly, in both directions.', 'minemirth' ),
				__( '<strong>The halving</strong> cuts the subsidy in half around {halving_date}.', 'minemirth' ),
				__( '<strong>Fees</strong> vary with network demand; busy periods can lift revenue noticeably.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Payback and ROI', 'minemirth' ) )
		. mm_bp( __( 'Payback is how long it takes cumulative profit to cover the miner’s price. A flat calculation (price ÷ daily profit) is optimistic because profit per TH usually falls over time. The <a href="{tool:roi}">ROI planner</a> projects month by month with difficulty growth and the halving, in bear, base and bull scenarios.', 'minemirth' ) )
		. mm_bcall( 'warning', __( 'Hidden costs to include', 'minemirth' ), __( 'Cooling power (fans, AC or pumps), import duties and shipping, electrical work, repairs and spare parts, downtime, and hosting setup fees. A realistic model includes them.', 'minemirth' ) )
		. mm_bh( __( 'Ways to improve profitability', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Lower your power price: negotiate, use off-peak tariffs or <a href="{page:hosting}">host</a>.', 'minemirth' ),
				__( 'Improve efficiency with <a href="{page:g-firmware}">firmware tuning</a>.', 'minemirth' ),
				__( 'Keep uptime high with good cooling and maintenance.', 'minemirth' ),
				__( 'Upgrade older miners when the <a href="{tool:upgrade}">numbers say so</a>.', 'minemirth' ),
				__( 'Reuse the heat in winter to offset heating bills.', 'minemirth' ),
			)
		)
		. mm_bfaq(
			array(
				array( __( 'What power price makes mining profitable?', 'minemirth' ), __( 'It depends on the miner: break-even = hashprice ÷ 1,000 ÷ (J/TH × 24 ÷ 1,000). The <a href="{tool:breakeven}">break-even calculator</a> shows it for any miner.', 'minemirth' ) ),
				array( __( 'Should I sell my mined bitcoin?', 'minemirth' ), __( 'Many miners sell enough to cover power and keep the rest. It’s a personal and tax decision. See <a href="{page:g-tax}">mining taxes</a>.', 'minemirth' ) ),
			)
		);
	},

	'g-hashprice'  => static function () {
		return mm_bp( __( 'If you only track one number as a miner, make it hashprice. It tells you, in dollars, what a unit of hashrate earns per day right now, and it already accounts for Bitcoin’s price, network difficulty and transaction fees.', 'minemirth' ) )
		. mm_bh( __( 'The definition', 'minemirth' ) )
		. mm_bp( __( 'Hashprice is the expected daily revenue of 1 PH/s (1,000 TH/s) of hashrate. Today it is {hashprice}. To get your revenue, multiply by your hashrate in PH/s: a 200 TH/s miner is 0.2 PH/s, so it earns 0.2 × hashprice before pool fees.', 'minemirth' ) )
		. mm_bh( __( 'How it is calculated', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'BTC per PH/s per day = 10¹⁵ × 86,400 × block reward ÷ (difficulty × 2³²).', 'minemirth' ),
				__( 'Block reward = 3.125 BTC subsidy + average fees per block (now about {fees}).', 'minemirth' ),
				__( 'Hashprice = BTC per PH/s per day × BTC price.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'What moves hashprice', 'minemirth' ) )
		. mm_bs(
			'table',
			array(
				'columns' => array( __( 'Change', 'minemirth' ), __( 'Effect on hashprice', 'minemirth' ), __( 'Typical speed', 'minemirth' ) ),
				'rows'    => array(
					__( 'BTC price +10% | +10% | Any time', 'minemirth' ),
					__( 'Difficulty +5% | About −4.8% | Every ~2 weeks', 'minemirth' ),
					__( 'Fees double | Up by the fee share of the reward | Bursts with demand', 'minemirth' ),
					__( 'Halving | About −50% of the subsidy part | Every ~4 years', 'minemirth' ),
				),
			)
		)
		. mm_bh( __( 'Using hashprice to make decisions', 'minemirth' ) )
		. mm_bp( __( 'Compare it with your break-even: your power cost per PH/s per day. For a miner at J joules per TH and power at P dollars per kWh, break-even hashprice = J × 24 × P (in $/PH/day). At 17.5 J/TH and $0.07/kWh, that is about $29.40.', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Well above break-even: healthy margin, consider expanding.', 'minemirth' ),
				__( 'Close to break-even: tune for efficiency, check costs, avoid new purchases at full price.', 'minemirth' ),
				__( 'Below break-even: you lose money on power; curtail, tune down, upgrade or host.', 'minemirth' ),
			)
		)
		. mm_bs( 'tool', array( 'title' => __( 'Live *hashprice* and history', 'minemirth' ), 'id' => 'hashprice' ) )
		. mm_bcall( 'info', __( 'Hashprice in sats', 'minemirth' ), __( 'Some miners track it in BTC (or sats) per PH/s per day, which removes the price effect and shows only difficulty and fees. The tracker shows both.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'Why do pools quote slightly different hashprices?', 'minemirth' ), __( 'They use different fee averaging windows and payout schemes. FPPS pools include an estimate of fees; PPS pools may not.', 'minemirth' ) ),
				array( __( 'Is a falling hashprice bad?', 'minemirth' ), __( 'For revenue, yes. But it often means more competition, which can push less efficient miners off the network and later lower difficulty.', 'minemirth' ) ),
			)
		);
	},

	'g-difficulty' => static function () {
		return mm_bp( __( 'Difficulty is how hard it is to find a valid Bitcoin block. It rises and falls automatically so that, on average, a block is found every ten minutes no matter how much hashrate joins or leaves. For miners, it is the number that steadily erodes revenue per terahash.', 'minemirth' ) )
		. mm_bh( __( 'How the adjustment works', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Every 2,016 blocks (about two weeks) the network checks how long those blocks took.', 'minemirth' ),
				__( 'If they took less than 20,160 minutes, difficulty goes up by the same proportion; if more, it goes down.', 'minemirth' ),
				__( 'A single adjustment is limited to a factor of four, though real changes are usually a few percent.', 'minemirth' ),
			)
		)
		. mm_bp( __( 'Current difficulty is {difficulty} and the next adjustment is estimated at {adjustment}.', 'minemirth' ) )
		. mm_bs( 'tool', array( 'title' => __( 'Live *difficulty* estimate', 'minemirth' ), 'id' => 'difficulty' ) )
		. mm_bh( __( 'Why difficulty keeps rising', 'minemirth' ) )
		. mm_bp( __( 'When mining is profitable, operators add machines and manufacturers ship more efficient models, so network hashrate grows. Difficulty follows. Over most of Bitcoin’s history the long-run trend has been strongly upward, with pauses after price crashes and halvings.', 'minemirth' ) )
		. mm_bh( __( 'Planning for difficulty growth', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Use a monthly growth assumption in projections; recent history is a starting point, not a promise.', 'minemirth' ),
				__( 'Prefer efficient miners, which stay profitable as revenue per TH falls.', 'minemirth' ),
				__( 'Watch seasonal effects: heat waves and grid curtailment can slow growth or even cause drops.', 'minemirth' ),
			)
		)
		. mm_bcall( 'success', __( 'Rule of thumb', 'minemirth' ), __( 'Revenue per TH moves inversely with difficulty. A +3% adjustment means about 3% fewer satoshis for the same hashrate.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'What is the difference between difficulty and hashrate?', 'minemirth' ), __( 'Hashrate is how many hashes the network performs per second; difficulty is the target the network sets. Difficulty is adjusted to match hashrate.', 'minemirth' ) ),
				array( __( 'Why do estimates change during an epoch?', 'minemirth' ), __( 'Early estimates are based on few blocks. As more blocks arrive, the average block time is known more precisely.', 'minemirth' ) ),
			)
		);
	},

	'g-halving'    => static function () {
		return mm_bp( __( 'Roughly every four years, the Bitcoin protocol cuts the reward for mining a block in half. Halvings are the biggest scheduled shock to miner revenue, so planning for them is one of the most important things an operator does.', 'minemirth' ) )
		. mm_bh( __( 'The schedule', 'minemirth' ) )
		. mm_bs(
			'table',
			array(
				'columns' => array( __( 'Halving', 'minemirth' ), __( 'Block', 'minemirth' ), __( 'Subsidy', 'minemirth' ) ),
				'rows'    => array(
					__( 'November 2012 | 210,000 | 50 → 25 BTC', 'minemirth' ),
					__( 'July 2016 | 420,000 | 25 → 12.5 BTC', 'minemirth' ),
					__( 'May 2020 | 630,000 | 12.5 → 6.25 BTC', 'minemirth' ),
					__( 'April 2024 | 840,000 | 6.25 → 3.125 BTC', 'minemirth' ),
					__( 'Expected {halving_date} | 1,050,000 | 3.125 → 1.5625 BTC', 'minemirth' ),
				),
			)
		)
		. mm_bs( 'tool', array( 'title' => __( 'Countdown and *impact on your miner*', 'minemirth' ), 'id' => 'halving' ) )
		. mm_bh( __( 'What happens to miners', 'minemirth' ) )
		. mm_bp( __( 'On the day, revenue per TH drops by roughly half of the subsidy share (fees continue). Miners whose power cost is above the new revenue switch off; that reduces network hashrate and, after the next adjustment, difficulty falls a little, partly offsetting the cut for those who keep running. Over the following months, the Bitcoin price and fee levels decide how quickly margins recover.', 'minemirth' ) )
		. mm_bh( __( 'How to prepare', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Check your post-halving break-even.</strong> The calculator above shows it for your miner.', 'minemirth' ),
				__( '<strong>Upgrade inefficient units</strong> before the event, while they still have resale value. See the <a href="{tool:upgrade}">upgrade calculator</a>.', 'minemirth' ),
				__( '<strong>Lower your power cost</strong> through better contracts, curtailment programs or <a href="{page:hosting}">hosting</a>.', 'minemirth' ),
				__( '<strong>Hold reserves</strong> to ride out a few lean months.', 'minemirth' ),
				__( '<strong>Tune firmware</strong> for efficiency rather than maximum hashrate.', 'minemirth' ),
			)
		)
		. mm_bcall( 'info', __( 'Fees after halvings', 'minemirth' ), __( 'As the subsidy shrinks, transaction fees become a larger share of miner revenue. Fees are volatile, so treat them as upside rather than a guarantee.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'Why does Bitcoin have halvings?', 'minemirth' ), __( 'To make issuance predictable and capped: the subsidy halves every 210,000 blocks until roughly 21 million BTC exist.', 'minemirth' ) ),
				array( __( 'Should I buy miners before or after a halving?', 'minemirth' ), __( 'Prices of older miners often fall after a halving. Buying an efficient miner before, with payback planned across it, is common. Model both cases with the <a href="{tool:roi}">ROI planner</a>.', 'minemirth' ) ),
			)
		);
	},
);
