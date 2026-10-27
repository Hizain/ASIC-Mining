<?php
/**
 * Content: "Farms, buying & business" guides.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

return array(

	'g-farm'     => static function () {
		return mm_bp( __( 'A mining farm is an energy project with computers attached. The hardware is the easy part; power, cooling, permits and operations decide whether the site makes money. This guide walks through the planning stages and the questions to answer before you build.', 'minemirth' ) )
		. mm_bh( __( '1. Power: the foundation', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Capacity</strong>: how many megawatts are available, and how quickly can they be connected?', 'minemirth' ),
				__( '<strong>Price</strong>: the all-in cost per kWh, including demand charges, transmission and taxes.', 'minemirth' ),
				__( '<strong>Contract terms</strong>: fixed or indexed, term length, curtailment rights and any credits for flexibility.', 'minemirth' ),
				__( '<strong>Reliability</strong>: how often does the supply fail or get curtailed?', 'minemirth' ),
			)
		)
		. mm_bp( __( 'As a guide, 1 MW runs roughly 280 miners of 3.5 kW each, and uses about 8,760 MWh a year at full load.', 'minemirth' ) )
		. mm_bh( __( '2. Site and building', 'minemirth' ) )
		. mm_bp( __( 'Options range from purpose-built halls to shipping containers with integrated racks, power distribution and fans or hydro loops. Containers deploy quickly and can move; buildings scale more cheaply over time. Check zoning, noise limits at the boundary, access roads, flood risk and security.', 'minemirth' ) )
		. mm_bh( __( '3. Electrical design', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Transformers sized for the load, with headroom.', 'minemirth' ),
				__( 'Switchgear, distribution panels and PDUs designed for continuous load.', 'minemirth' ),
				__( 'Balanced three-phase distribution.', 'minemirth' ),
				__( 'Protection, earthing and metering by licensed engineers.', 'minemirth' ),
			)
		)
		. mm_bs( 'tool', array( 'title' => __( 'Quick sizing: amps, circuits and *heat*', 'minemirth' ), 'id' => 'power' ) )
		. mm_bh( __( '4. Cooling', 'minemirth' ) )
		. mm_bp( __( 'Air cooling needs large volumes of filtered air and hot-aisle containment. In hot climates, evaporative cooling extends the operating range. Hydro and immersion allow higher density and quieter sites, and make heat reuse practical. See <a href="{page:g-cooling}">cooling & ventilation</a>.', 'minemirth' ) )
		. mm_bh( __( '5. Network and operations', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Two independent internet connections if possible; miners use little bandwidth but must stay online.', 'minemirth' ),
				__( 'Segmented networks for miners, with secure remote access.', 'minemirth' ),
				__( 'Fleet management software for monitoring, firmware, power targets and alerts.', 'minemirth' ),
				__( 'Staffing or a service contract for daily checks, swaps and repairs, with a spares stock.', 'minemirth' ),
			)
		)
		. mm_bh( __( '6. The financial model', 'minemirth' ) )
		. mm_bp( __( 'Model capital costs (miners, infrastructure, connection), operating costs (power, staff, maintenance, insurance) and revenue under several scenarios for Bitcoin price, difficulty growth and the halving. Stress-test the model: what happens if hashprice falls 30% for a year?', 'minemirth' ) )
		. mm_bs( 'tool', array( 'title' => __( 'Scenario *ROI* for one unit', 'minemirth' ), 'id' => 'roi' ) )
		. mm_bcall( 'info', __( 'We can help', 'minemirth' ), __( 'Our farm design service works with your engineers on power, cooling, layout and the model.', 'minemirth' ), array( 'button_label' => __( 'Farm design & consulting', 'minemirth' ), 'button_url' => '{page:consulting}' ) )
		. mm_bfaq(
			array(
				array( __( 'How long does it take to build a farm?', 'minemirth' ), __( 'Containers on an existing power connection can be live in weeks; new grid connections and buildings can take many months.', 'minemirth' ) ),
				array( __( 'What is a good power price for a farm?', 'minemirth' ), __( 'Large operators often target industrial rates well below residential levels. Check your break-even with the <a href="{tool:breakeven}">break-even calculator</a>.', 'minemirth' ) ),
			)
		);
	},

	'g-hosting'  => static function () {
		return mm_bp( __( 'Hosting means paying a facility to run your miners on its power and cooling. A good host can make mining simple and profitable; a bad one can turn it into a dispute. Use this checklist before you sign.', 'minemirth' ) )
		. mm_bh( __( 'The price: read the whole rate', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>All-in rate per kWh</strong>: does it include cooling, staff and monitoring, or are they extra?', 'minemirth' ),
				__( '<strong>Billing basis</strong>: measured power or rated power? Monthly in advance or in arrears?', 'minemirth' ),
				__( '<strong>Setup and removal fees</strong>, per unit.', 'minemirth' ),
				__( '<strong>Deposits</strong>: how many months, and when are they returned?', 'minemirth' ),
				__( '<strong>Price changes</strong>: fixed for the term, or can the host change the rate?', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Uptime and curtailment', 'minemirth' ) )
		. mm_bp( __( 'Ask for the expected uptime and how curtailment works. Some sites switch off during grid peaks or high prices; that can be fine if you are not billed for power you don’t use, but you need to know how often it happens and how it is accounted for.', 'minemirth' ) )
		. mm_bh( __( 'Operations and repairs', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'What is included: reboots, cable checks, fan swaps?', 'minemirth' ),
				__( 'How quickly do they respond to an offline miner?', 'minemirth' ),
				__( 'Who handles warranty shipping, and what do repairs cost?', 'minemirth' ),
				__( 'Can you see your miners’ status and temperatures?', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Control, security and exit', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Your miners should point to <strong>your</strong> pool account; never let a host collect your rewards.', 'minemirth' ),
				__( 'Serial numbers recorded in the agreement prove ownership.', 'minemirth' ),
				__( 'Insurance: what does the facility cover, up to what limit?', 'minemirth' ),
				__( 'Exit: notice period, removal fees and how quickly miners are shipped back.', 'minemirth' ),
				__( 'Site visits: are they allowed, and how are they arranged?', 'minemirth' ),
			)
		)
		. mm_bcall( 'warning', __( 'Red flags', 'minemirth' ), __( 'Rates far below the market, no written agreement, the host insisting on its own pool account, no serial numbers recorded, or vague answers about the facility’s location and power source.', 'minemirth' ) )
		. mm_bs( 'tool', array( 'title' => __( 'Hosting vs home: *run your numbers*', 'minemirth' ), 'id' => 'hosting' ) )
		. mm_bs( 'pricing', array( 'title' => __( 'Our current *hosting plans*', 'minemirth' ), 'text' => __( 'Each plan publishes its rate, billing basis, minimum and term.', 'minemirth' ) ) )
		. mm_bfaq(
			array(
				array( __( 'How do I calculate hosting cost?', 'minemirth' ), __( 'kW per miner × 730 hours × rate per kWh. A 3.5 kW miner at $0.075 costs about $192 a month.', 'minemirth' ) ),
				array( __( 'Is hosting worth it for one miner?', 'minemirth' ), __( 'Often yes, if your home power is expensive or noise and heat are a problem. Check minimums: some hosts require several units.', 'minemirth' ) ),
			)
		);
	},

	'g-used'     => static function () {
		return mm_bp( __( 'A used ASIC can be the best-value miner you buy, or the worst. Miners from closed farms, upgraded fleets and hosting sites change hands constantly. Here is how to separate a healthy unit from a problem.', 'minemirth' ) )
		. mm_bh( __( 'Before you buy', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Run the numbers</strong> at your power price. An older model might be cheap but barely profitable. Use the <a href="{tool:profit}">profit calculator</a>.', 'minemirth' ),
				__( '<strong>Price it fairly</strong>: compare $/TH with new and other used listings; check how long it would take to pay back before the halving.', 'minemirth' ),
				__( '<strong>Know the seller</strong>: business details, history, and willingness to show the miner hashing.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'What to inspect', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Serial number labels on the case and hashboards that match.', 'minemirth' ),
				__( 'Corrosion, white residue or green spots on boards and connectors (signs of humidity or immersion).', 'minemirth' ),
				__( 'Dents, bent fins, burned connectors or melted cord ends.', 'minemirth' ),
				__( 'Fans: spin freely, no wobble or grinding.', 'minemirth' ),
				__( 'Missing screws or broken warranty seals (signs it has been opened).', 'minemirth' ),
			)
		)
		. mm_bh( __( 'How to test', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Run the miner for at least several hours on a pool, ideally 24.', 'minemirth' ),
				__( 'Check that all hashboards report the expected number of chips.', 'minemirth' ),
				__( 'Compare average hashrate with the model’s rated figure.', 'minemirth' ),
				__( 'Watch temperatures and hardware errors in the kernel log.', 'minemirth' ),
				__( 'Measure power draw at the wall if you can.', 'minemirth' ),
			),
			true
		)
		. mm_bcall( 'warning', __( 'Common scams', 'minemirth' ), __( 'Prices far below market, sellers who refuse video of the miner hashing, payment only by irreversible methods to personal accounts, and “new in box” units with mismatched serials. If it feels wrong, walk away.', 'minemirth' ) )
		. mm_bh( __( 'Warranty and returns', 'minemirth' ) )
		. mm_bp( __( 'Manufacturer warranties may or may not transfer, and many used miners are out of warranty. Ask for a short return window for dead-on-arrival units, and get it in writing.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'Are ex-immersion miners bad?', 'minemirth' ), __( 'Not necessarily, but they need thorough cleaning and new fans to run on air, and they usually sell for less.', 'minemirth' ) ),
				array( __( 'Can you sell my used miners?', 'minemirth' ), __( 'Ask about our <a href="{page:tradein}">trade-in & buyback</a>.', 'minemirth' ) ),
			)
		);
	},

	'g-security' => static function () {
		return mm_bp( __( 'Miners are computers connected to the internet, pools are accounts with your earnings, and payouts go to wallets. Each is a target. A few simple habits close the most common holes.', 'minemirth' ) )
		. mm_bh( __( 'Secure the miners', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Change default passwords</strong> on every miner’s web interface immediately.', 'minemirth' ),
				__( '<strong>Never expose miners directly to the internet.</strong> Don’t forward ports to them. Use a VPN for remote access.', 'minemirth' ),
				__( '<strong>Put miners on their own network</strong> (VLAN or separate router), away from computers and phones.', 'minemirth' ),
				__( '<strong>Use official firmware sources only.</strong> Malicious firmware can silently redirect your hashrate to someone else’s pool.', 'minemirth' ),
				__( '<strong>Check pool settings regularly</strong>: unexpected pool URLs or worker names are a sign of tampering.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Secure the pool account', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Use a unique, strong password and two-factor authentication.', 'minemirth' ),
				__( 'Lock or whitelist payout addresses where the pool allows it.', 'minemirth' ),
				__( 'Use read-only “watcher” links for dashboards you share.', 'minemirth' ),
				__( 'Watch for phishing emails pretending to be your pool.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Secure the coins', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Pay out to a wallet you control, not an exchange account, for anything you intend to keep.', 'minemirth' ),
				__( 'Use a hardware wallet for savings and keep the recovery phrase offline, never in photos or cloud notes.', 'minemirth' ),
				__( 'Test a backup recovery before relying on it.', 'minemirth' ),
				__( 'Consider a multisig setup for larger holdings or a business.', 'minemirth' ),
			)
		)
		. mm_bcall( 'warning', __( 'Nobody legitimate will ask for your recovery phrase', 'minemirth' ), __( 'Not a pool, not a wallet maker, not us. Anyone who asks is trying to steal your coins.', 'minemirth' ) )
		. mm_bh( __( 'Physical security', 'minemirth' ) )
		. mm_bp( __( 'Miners are valuable and easy to resell. Lock rooms or containers, record serial numbers, add cameras at larger sites, and check that your insurance covers mining equipment.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'How do I know if my hashrate is being stolen?', 'minemirth' ), __( 'Compare your miners’ reported hashrate with what your pool receives. A consistent gap, or unknown pool URLs in settings, are warning signs.', 'minemirth' ) ),
				array( __( 'Is it safe to manage miners from my phone?', 'minemirth' ), __( 'Through a VPN or the pool’s app, yes. Avoid exposing the miner interfaces directly.', 'minemirth' ) ),
			)
		);
	},

	'g-tax'      => static function () {
		return mm_bcall( 'warning', __( 'General information, not tax advice', 'minemirth' ), __( 'Tax rules for mining vary by country and change over time. Use this guide to prepare questions for a qualified tax professional in your jurisdiction.', 'minemirth' ) )
		. mm_bp( __( 'In many countries, mining creates two separate tax events: income when you receive mined coins, and a capital gain or loss when you later sell or spend them. How each is treated depends on where you live and whether you mine as a hobby or a business.', 'minemirth' ) )
		. mm_bh( __( 'Mining income', 'minemirth' ) )
		. mm_bp( __( 'A common approach (for example in the United States) treats mined coins as income at their fair market value on the day you receive them. Each pool payout is then an income event, valued in your local currency at that time.', 'minemirth' ) )
		. mm_bh( __( 'When you sell', 'minemirth' ) )
		. mm_bp( __( 'The value recorded as income often becomes your cost basis. When you later sell, swap or spend the coins, the difference between the sale value and that basis may be a capital gain or loss, sometimes taxed at different rates depending on how long you held them.', 'minemirth' ) )
		. mm_bh( __( 'Hobby or business', 'minemirth' ) )
		. mm_bp( __( 'Operating as a business may allow you to deduct expenses such as electricity, hosting fees, repairs and depreciation of miners, but can also bring self-employment or business taxes. Whether mining counts as a business depends on factors like scale, intent and how it is run.', 'minemirth' ) )
		. mm_bh( __( 'Records to keep', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Every payout: date, amount of coin, and its value in your currency at that time.', 'minemirth' ),
				__( 'Every sale, swap or purchase made with mined coins.', 'minemirth' ),
				__( 'Invoices for miners (with serial numbers), shipping, import duties and parts.', 'minemirth' ),
				__( 'Electricity and hosting bills, and how much was used by mining.', 'minemirth' ),
				__( 'Pool statements and wallet exports.', 'minemirth' ),
			)
		)
		. mm_bcall( 'success', __( 'Make it easy', 'minemirth' ), __( 'Export pool payout history monthly and keep it with your power bills. Crypto tax software can import pool and wallet data and value payouts automatically.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'Do I pay tax if I don’t sell?', 'minemirth' ), __( 'In many places, yes: the income is often taxable when the coins are received, even if you hold them.', 'minemirth' ) ),
				array( __( 'Can I deduct the cost of my miner?', 'minemirth' ), __( 'If you mine as a business, miners are often depreciated over time. Ask your tax adviser how this works where you live.', 'minemirth' ) ),
			)
		);
	},

	'g-glossary' => static function () {
		$terms = array(
			__( 'ASIC', 'minemirth' )                => __( 'Application-specific integrated circuit: a chip designed for one task, here hashing a specific algorithm.', 'minemirth' ),
			__( 'Algorithm', 'minemirth' )           => __( 'The hashing function a coin uses, such as SHA-256 (Bitcoin), Scrypt (Litecoin, Dogecoin) or kHeavyHash (Kaspa).', 'minemirth' ),
			__( 'Block', 'minemirth' )               => __( 'A batch of transactions added to the blockchain, about every 10 minutes for Bitcoin.', 'minemirth' ),
			__( 'Block height', 'minemirth' )        => __( 'The number of blocks before the current one; the chain’s length (now {height}).', 'minemirth' ),
			__( 'Block reward', 'minemirth' )        => __( 'Subsidy plus transaction fees paid to the miner who finds a block.', 'minemirth' ),
			__( 'Block subsidy', 'minemirth' )       => __( 'Newly issued bitcoin in each block: 3.125 BTC until the next halving.', 'minemirth' ),
			__( 'Break-even power price', 'minemirth' ) => __( 'The electricity price at which a miner’s revenue equals its power cost.', 'minemirth' ),
			__( 'BTU/h', 'minemirth' )               => __( 'British thermal units per hour, a unit of heat. 1 watt = 3.412 BTU/h.', 'minemirth' ),
			__( 'C13 / C19', 'minemirth' )           => __( 'Common power inlet types on ASIC power supplies; C19 carries higher current.', 'minemirth' ),
			__( 'CFM', 'minemirth' )                 => __( 'Cubic feet per minute, a measure of airflow.', 'minemirth' ),
			__( 'Cold plate', 'minemirth' )          => __( 'The water-cooled metal block on a hydro miner’s hashboards.', 'minemirth' ),
			__( 'Control board', 'minemirth' )       => __( 'The miner’s small computer, which runs the firmware and connects to the network.', 'minemirth' ),
			__( 'Curtailment', 'minemirth' )         => __( 'Reducing or switching off miners, for example during grid peaks.', 'minemirth' ),
			__( 'Dev fee', 'minemirth' )             => __( 'A share of hashrate taken by aftermarket firmware as payment.', 'minemirth' ),
			__( 'Dielectric fluid', 'minemirth' )    => __( 'Non-conductive liquid used in immersion cooling.', 'minemirth' ),
			__( 'Difficulty', 'minemirth' )          => __( 'How hard it is to find a block; adjusted every 2,016 blocks (now {difficulty}).', 'minemirth' ),
			__( 'Difficulty adjustment', 'minemirth' ) => __( 'The automatic change in difficulty that keeps blocks at about 10 minutes.', 'minemirth' ),
			__( 'Dry cooler', 'minemirth' )          => __( 'A fan-driven radiator that removes heat from a liquid cooling loop.', 'minemirth' ),
			__( 'Efficiency (J/TH)', 'minemirth' )   => __( 'Joules of energy per terahash. Lower is better.', 'minemirth' ),
			__( 'EH/s', 'minemirth' )                => __( 'Exahashes per second: 10¹⁸ hashes per second, used for network hashrate.', 'minemirth' ),
			__( 'Epoch', 'minemirth' )               => __( 'The 2,016-block period between difficulty adjustments.', 'minemirth' ),
			__( 'Firmware', 'minemirth' )            => __( 'The software running on a miner.', 'minemirth' ),
			__( 'FPPS', 'minemirth' )                => __( 'Full pay per share: a pool payout that pays a fixed amount per share including estimated fees.', 'minemirth' ),
			__( 'Halving', 'minemirth' )             => __( 'The halving of the block subsidy every 210,000 blocks; next expected around {halving_date}.', 'minemirth' ),
			__( 'Hash', 'minemirth' )                => __( 'The output of a hashing function; each guess a miner makes.', 'minemirth' ),
			__( 'Hashboard', 'minemirth' )           => __( 'A circuit board carrying many ASIC chips; most miners have three.', 'minemirth' ),
			__( 'Hashprice', 'minemirth' )           => __( 'Expected revenue per unit of hashrate per day, usually $ per PH/s per day (now {hashprice}).', 'minemirth' ),
			__( 'Hashrate', 'minemirth' )            => __( 'Hashes per second. For a miner in TH/s; for the network in EH/s (now {hashrate}).', 'minemirth' ),
			__( 'Hosting', 'minemirth' )             => __( 'Running your miners in a third-party facility for a fee.', 'minemirth' ),
			__( 'Hydro miner', 'minemirth' )         => __( 'A miner cooled by liquid through cold plates instead of fans.', 'minemirth' ),
			__( 'Immersion cooling', 'minemirth' )   => __( 'Cooling miners by submerging them in dielectric fluid.', 'minemirth' ),
			__( 'Kernel log', 'minemirth' )          => __( 'The miner’s system log, useful for diagnosing faults.', 'minemirth' ),
			__( 'kWh', 'minemirth' )                 => __( 'Kilowatt-hour: 1,000 watts for one hour; how electricity is billed.', 'minemirth' ),
			__( 'Luck', 'minemirth' )                => __( 'How many blocks a pool found compared with how many it was expected to find.', 'minemirth' ),
			__( 'Mempool', 'minemirth' )             => __( 'The set of unconfirmed transactions waiting to be included in a block.', 'minemirth' ),
			__( 'Merged mining', 'minemirth' )       => __( 'Mining two coins with the same work, such as Litecoin and Dogecoin.', 'minemirth' ),
			__( 'Nonce', 'minemirth' )               => __( 'The number miners change to produce different hashes.', 'minemirth' ),
			__( 'Overclocking', 'minemirth' )        => __( 'Running chips faster than stock for more hashrate, usually at lower efficiency.', 'minemirth' ),
			__( 'PDU', 'minemirth' )                 => __( 'Power distribution unit: a rack-mounted unit that distributes power to several miners.', 'minemirth' ),
			__( 'PH/s', 'minemirth' )                => __( 'Petahashes per second: 1,000 TH/s.', 'minemirth' ),
			__( 'Pool', 'minemirth' )                => __( 'A service that combines miners’ hashrate and shares the rewards.', 'minemirth' ),
			__( 'PPLNS', 'minemirth' )               => __( 'Pay per last N shares: payouts depend on blocks the pool actually finds.', 'minemirth' ),
			__( 'PPS', 'minemirth' )                 => __( 'Pay per share: a fixed payment per share regardless of the pool’s luck.', 'minemirth' ),
			__( 'Proof of work', 'minemirth' )       => __( 'The system that makes miners spend energy to produce valid blocks.', 'minemirth' ),
			__( 'PSU', 'minemirth' )                 => __( 'Power supply unit, which converts mains power for the hashboards.', 'minemirth' ),
			__( 'Share', 'minemirth' )               => __( 'A partial solution submitted to a pool to prove work.', 'minemirth' ),
			__( 'Solo mining', 'minemirth' )         => __( 'Mining alone for the whole block reward instead of a pool share.', 'minemirth' ),
			__( 'Stale share', 'minemirth' )         => __( 'A share submitted too late to count, often due to latency.', 'minemirth' ),
			__( 'Stratum', 'minemirth' )             => __( 'The protocol miners use to talk to pools. Stratum V2 adds encryption and more miner control.', 'minemirth' ),
			__( 'TH/s', 'minemirth' )                => __( 'Terahashes per second: 10¹² hashes per second.', 'minemirth' ),
			__( 'Underclocking', 'minemirth' )       => __( 'Running chips slower to save power and improve efficiency.', 'minemirth' ),
			__( 'Uptime', 'minemirth' )              => __( 'The share of time a miner is hashing.', 'minemirth' ),
		);
		$out = mm_bp( __( 'Plain-English definitions of the terms you’ll meet in mining, from hardware and power to pools and the network. Live values are shown where they help.', 'minemirth' ) );
		$by  = array();
		foreach ( $terms as $term => $def ) {
			$first = strtoupper( mb_substr( wp_strip_all_tags( (string) $term ), 0, 1 ) );
			$by[ ctype_alpha( $first ) ? $first : '#' ][ $term ] = $def;
		}
		ksort( $by );
		foreach ( $by as $letter => $list ) {
			$out .= mm_bh( $letter );
			$items = array();
			foreach ( $list as $term => $def ) {
				$items[] = '<strong>' . esc_html( $term ) . '</strong>: ' . $def;
			}
			$out .= mm_bl( $items );
		}
		return $out . mm_bs( 'tools', array( 'title' => __( 'See the terms *in action*', 'minemirth' ), 'limit' => 4 ) );
	},
);
