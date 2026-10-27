<?php
/**
 * Content: services hub, hosting, repairs, setup, trade-in, wholesale, farm design, business.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

return array(

	'services'   => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Services', 'minemirth' ),
				'title'   => __( 'Mining services for everything *after checkout*', 'minemirth' ),
				'text'    => __( 'Mining services from one team that knows the hardware: hosting, setup, tuning, repairs, trade-ins, fleet supply and farm design.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Talk to us', 'minemirth' ), '{page:contact}' ), array( __( 'Hosting plans', 'minemirth' ), '{page:hosting}', 'glass' ) ) ),
				'visual'  => 'orbit',
				'icon'    => 'settings',
				'chips'   => mm_bchips( array( array( 'server', __( 'Hosting', 'minemirth' ), __( 'all-in rates', 'minemirth' ) ), array( 'sliders', __( 'Tuning', 'minemirth' ), __( 'for your power price', 'minemirth' ) ), array( 'wrench', __( 'Repairs', 'minemirth' ), __( 'quoted first', 'minemirth' ) ), array( 'layers', __( 'Design', 'minemirth' ), __( 'sites and farms', 'minemirth' ) ) ) ),
			)
		)
		. mm_bs(
			'links',
			array(
				'eyebrow' => __( 'What we offer', 'minemirth' ),
				'title'   => __( 'Pick a *service*', 'minemirth' ),
				'columns' => '3',
				'items'   => array(
					array( 'icon' => 'server', 'title' => __( 'Miner hosting', 'minemirth' ), 'text' => __( 'Run miners in a professional facility at a published all-in power rate.', 'minemirth' ), 'url' => '{page:hosting}', 'badge' => __( 'Popular', 'minemirth' ) ),
					array( 'icon' => 'sliders', 'title' => __( 'Setup & firmware tuning', 'minemirth' ), 'text' => __( 'Network, pool and firmware set up, then tuned for your electricity price.', 'minemirth' ), 'url' => '{page:setup}' ),
					array( 'icon' => 'wrench', 'title' => __( 'Repairs & servicing', 'minemirth' ), 'text' => __( 'Hashboards, PSUs, fans and control boards diagnosed and repaired.', 'minemirth' ), 'url' => '{page:repairs}' ),
					array( 'icon' => 'refresh', 'title' => __( 'Trade-in & buyback', 'minemirth' ), 'text' => __( 'Put older miners toward newer, more efficient models.', 'minemirth' ), 'url' => '{page:tradein}' ),
					array( 'icon' => 'package', 'title' => __( 'Wholesale & bulk', 'minemirth' ), 'text' => __( 'Volume quotes, batch testing and freight for fleets.', 'minemirth' ), 'url' => '{page:wholesale}' ),
					array( 'icon' => 'layers', 'title' => __( 'Farm design & consulting', 'minemirth' ), 'text' => __( 'Power, cooling, layout and economics before you build.', 'minemirth' ), 'url' => '{page:consulting}' ),
					array( 'icon' => 'bar-chart', 'title' => __( 'Mining for businesses', 'minemirth' ), 'text' => __( 'For companies, investors and energy producers adding mining.', 'minemirth' ), 'url' => '{page:business}' ),
				),
			)
		)
		. mm_bs(
			'steps',
			array(
				'eyebrow' => __( 'How we work', 'minemirth' ),
				'title'   => __( 'Simple, written, *predictable*', 'minemirth' ),
				'tone'    => 'alt',
				'items'   => array(
					array( 'message', __( 'You tell us the goal', 'minemirth' ), __( 'Your miners, site, power price and timeline.', 'minemirth' ) ),
					array( 'file', __( 'We quote in writing', 'minemirth' ), __( 'Scope, price, timing and what is not included.', 'minemirth' ) ),
					array( 'wrench', __( 'We do the work', 'minemirth' ), __( 'With updates as it progresses.', 'minemirth' ) ),
					array( 'check-circle', __( 'You get the results', 'minemirth' ), __( 'Hashrate, efficiency and a report you can keep.', 'minemirth' ) ),
				),
			)
		)
		. mm_bcta( __( 'Not sure which service you *need*?', 'minemirth' ), __( 'Describe your situation and we’ll point you to the right option, even if that means doing nothing yet.', 'minemirth' ), array( array( __( 'Contact us', 'minemirth' ), '{page:contact}' ) ) );
	},

	'hosting'    => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Miner hosting', 'minemirth' ),
				'title'   => __( 'ASIC miner hosting: your miners, *your coins*', 'minemirth' ),
				'text'    => __( 'ASIC miner hosting in professional partner facilities with industrial power, cooling and 24/7 monitoring. One all-in rate per kWh, published on every plan. Payouts go straight to your own pool account and wallet.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'View plans', 'minemirth' ), '#hosting-plans', 'primary', 'arrow-down' ), array( __( 'Hosting vs home calculator', 'minemirth' ), '{tool:hosting}', 'glass', 'calculator' ) ) ),
				'badges'  => array( __( 'All-in rate, no multipliers', 'minemirth' ), __( 'Remote monitoring', 'minemirth' ), __( 'On-site technicians', 'minemirth' ) ),
				'visual'  => 'orbit',
				'icon'    => 'server',
				'chips'   => mm_bchips( array( array( 'bolt', __( 'All-in', 'minemirth' ), __( 'rate per kWh', 'minemirth' ) ), array( 'activity', '24/7', __( 'monitoring', 'minemirth' ) ), array( 'thermometer', __( 'Managed', 'minemirth' ), __( 'cooling', 'minemirth' ) ), array( 'wallet', __( 'Direct', 'minemirth' ), __( 'payouts to you', 'minemirth' ) ) ) ),
			)
		)
		. mm_bs( 'pricing', array( 'eyebrow' => __( 'Published options', 'minemirth' ), 'title' => __( 'Current hosting *plans*', 'minemirth' ), 'text' => __( 'Each plan shows its region, availability, all-in rate, billing, minimum and term. Capacity changes, so ask us to reserve space.', 'minemirth' ), 'note' => __( 'Monthly cost ≈ miner watts ÷ 1,000 × 730 hours × rate. A 3,500 W miner at $0.075/kWh is about $192 a month.', 'minemirth' ) ) )
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'Included', 'minemirth' ),
				'title'   => __( 'What hosting *covers*', 'minemirth' ),
				'columns' => '3',
				'tone'    => 'alt',
				'items'   => mm_bitems(
					array(
						array( 'bolt', __( 'Power and cooling', 'minemirth' ), __( 'Industrial electrical supply and managed airflow or liquid cooling, included in the all-in rate.', 'minemirth' ) ),
						array( 'activity', __( 'Monitoring and alerts', 'minemirth' ), __( 'Hashrate, temperatures and uptime watched around the clock, with action taken on faults.', 'minemirth' ) ),
						array( 'refresh', __( 'Reboots and swaps', 'minemirth' ), __( 'On-site staff power-cycle, reseat cables and swap fans or PSUs from your spares.', 'minemirth' ) ),
						array( 'sliders', __( 'Pool setup', 'minemirth' ), __( 'Your miners are configured on your own pool account; payouts never touch us.', 'minemirth' ) ),
						array( 'shield-check', __( 'Secure site', 'minemirth' ), __( 'Access-controlled facilities with miners labelled and recorded by serial number.', 'minemirth' ) ),
						array( 'wrench', __( 'Repair handling', 'minemirth' ), __( 'Faulty units diagnosed on site and sent for warranty or paid repair with your approval.', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bs(
			'steps',
			array(
				'eyebrow' => __( 'Getting started', 'minemirth' ),
				'title'   => __( 'Hashing in *four steps*', 'minemirth' ),
				'items'   => array(
					array( 'check-circle', __( 'Reserve capacity', 'minemirth' ), __( 'Pick a plan and tell us how many miners. We confirm space and the agreement in writing.', 'minemirth' ) ),
					array( 'truck', __( 'Ship or buy', 'minemirth' ), __( 'Buy miners from us for direct delivery, or ship your own with the labels we provide.', 'minemirth' ) ),
					array( 'sliders', __( 'Install and configure', 'minemirth' ), __( 'Technicians rack, connect and point miners at your pool, then confirm hashrate.', 'minemirth' ) ),
					array( 'activity', __( 'Monitor and get paid', 'minemirth' ), __( 'Your pool pays your wallet. We send monthly power invoices and handle the hardware.', 'minemirth' ) ),
				),
			)
		)
		. mm_bs(
			'table',
			array(
				'eyebrow'   => __( 'Compared', 'minemirth' ),
				'title'     => __( 'Hosting vs mining *at home*', 'minemirth' ),
				'columns'   => array( '', __( 'Hosted', 'minemirth' ), __( 'At home', 'minemirth' ) ),
				'highlight' => 1,
				'rows'      => array(
					__( 'Power price | Industrial all-in rate | Your residential tariff', 'minemirth' ),
					__( 'Noise (70 to 80 dB per miner) | Not your problem | In your home', 'minemirth' ),
					__( 'Heat | Managed by the facility | You must exhaust or reuse it', 'minemirth' ),
					__( 'Electrical work | Done | New 240 V circuits needed', 'minemirth' ),
					__( 'Monitoring and repairs | On-site staff | You', 'minemirth' ),
					__( 'Physical access | By arrangement | Any time', 'minemirth' ),
					__( 'Extra costs | Setup and shipping | Wiring, ventilation, cooling', 'minemirth' ),
				),
				'note'      => __( 'Run your own numbers with the hosting vs home calculator below.', 'minemirth' ),
				'tone'      => 'alt',
			)
		)
		. mm_bs( 'tool', array( 'eyebrow' => __( 'Calculator', 'minemirth' ), 'title' => __( 'Hosting or home: *which pays more*?', 'minemirth' ), 'id' => 'hosting' ) )
		. mm_bfaq(
			array(
				array( __( 'What does the all-in rate include?', 'minemirth' ), __( 'Power, cooling, rack space, monitoring and routine on-site work such as reboots and cable checks. Setup fees, shipping, parts and paid repairs are listed separately on each plan.', 'minemirth' ) ),
				array( __( 'How is power billed?', 'minemirth' ), __( 'By each plan’s stated basis, usually the miner’s measured or rated draw per hour, billed monthly. The billing basis is shown on the plan and in your agreement.', 'minemirth' ) ),
				array( __( 'Is there a minimum term?', 'minemirth' ), __( 'Each plan states its minimum number of units and term. Longer terms and larger fleets can get better rates.', 'minemirth' ) ),
				array( __( 'What about curtailment?', 'minemirth' ), __( 'Some facilities reduce load during grid peaks. Where that applies, the expected uptime and how billing works during curtailment are written into the agreement.', 'minemirth' ) ),
				array( __( 'Can I host miners I bought elsewhere?', 'minemirth' ), __( 'Usually yes, if they are compatible with the facility (power, cooling type and condition). Tell us the models first.', 'minemirth' ) ),
				array( __( 'Are hosted miners insured?', 'minemirth' ), __( 'Facility cover and its limits are stated in the agreement. Many customers also add their own equipment insurance.', 'minemirth' ) ),
			)
		)
		. mm_bcta( __( 'Reserve your *hosting slot*', 'minemirth' ), __( 'Tell us the model, quantity and preferred plan. We’ll confirm capacity and send the agreement.', 'minemirth' ), array( array( __( 'Request availability', 'minemirth' ), '{page:contact}' ), array( __( 'Shop hosting-ready miners', 'minemirth' ), '{shop}' ) ) );
	},

	'repairs'    => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Repairs & servicing', 'minemirth' ),
				'title'   => __( 'ASIC miner repair that brings hashboards *back to work*', 'minemirth' ),
				'text'    => __( 'ASIC miner repair and diagnosis for Antminer, Whatsminer and Avalon miners: hashboards, power supplies, fans and control boards. You get a quote before any paid work starts.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Request a repair', 'minemirth' ), '{page:contact}' ), array( __( 'Troubleshoot first', 'minemirth' ), '{page:g-troubleshoot}', 'glass' ) ) ),
				'visual'  => 'orbit',
				'icon'    => 'wrench',
				'chips'   => mm_bchips( array( array( 'cpu', __( 'Hashboards', 'minemirth' ), __( 'chip-level', 'minemirth' ) ), array( 'bolt', __( 'PSUs', 'minemirth' ), __( 'tested under load', 'minemirth' ) ), array( 'fan', __( 'Fans', 'minemirth' ), __( 'and connectors', 'minemirth' ) ) ) ),
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'What we repair', 'minemirth' ),
				'title'   => __( 'Common faults we *fix*', 'minemirth' ),
				'columns' => '4',
				'items'   => mm_bitems(
					array(
						array( 'cpu', __( 'Hashboards', 'minemirth' ), __( 'Missing chips, “0 ASIC” boards, broken domains, damaged connectors and temperature sensor faults.', 'minemirth' ) ),
						array( 'bolt', __( 'Power supplies', 'minemirth' ), __( 'No output, unstable voltage, blown fuses and fan failure, tested under load after repair.', 'minemirth' ) ),
						array( 'settings', __( 'Control boards', 'minemirth' ), __( 'No network, boot loops, corrupted firmware and failed SD recovery.', 'minemirth' ) ),
						array( 'fan', __( 'Fans and cooling', 'minemirth' ), __( 'Fan errors, worn bearings, damaged cables and clogged heatsinks.', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bs(
			'steps',
			array(
				'eyebrow' => __( 'The process', 'minemirth' ),
				'title'   => __( 'From fault to *fixed*', 'minemirth' ),
				'tone'    => 'alt',
				'items'   => array(
					array( 'message', __( 'Describe the fault', 'minemirth' ), __( 'Model, serial number, symptoms and the kernel log if the miner boots.', 'minemirth' ) ),
					array( 'truck', __( 'Send the unit or part', 'minemirth' ), __( 'Often only the hashboard or PSU needs to travel, which saves shipping.', 'minemirth' ) ),
					array( 'file', __( 'Diagnosis and quote', 'minemirth' ), __( 'We test it and tell you the cause, the cost and the timing before repairing.', 'minemirth' ) ),
					array( 'check-circle', __( 'Repair and test', 'minemirth' ), __( 'Repaired parts are tested for hashrate and stability before they come back.', 'minemirth' ) ),
				),
			)
		)
		. mm_bcall( 'warning', __( 'Under warranty?', 'minemirth' ), __( 'Don’t open the miner or flash new firmware yet: it can void the manufacturer’s warranty. Contact us first and we’ll tell you whether it should go to the manufacturer instead.', 'minemirth' ), array( 'button_label' => __( 'Warranty & support', 'minemirth' ), 'button_url' => '{page:warranty}' ) )
		. mm_bfaq(
			array(
				array( __( 'Is it worth repairing an older miner?', 'minemirth' ), __( 'Compare the repair cost with the profit the miner will make at today’s numbers and with its resale value. Older, inefficient models sometimes make more sense as a trade-in. The <a href="{tool:upgrade}">upgrade calculator</a> helps decide.', 'minemirth' ) ),
				array( __( 'Do you repair hosted miners?', 'minemirth' ), __( 'Yes. Facility technicians can diagnose and send the faulty part to us without you handling it.', 'minemirth' ) ),
				array( __( 'Is there a warranty on repairs?', 'minemirth' ), __( 'Repairs carry a repair warranty on the work done, stated on your quote.', 'minemirth' ) ),
			)
		)
		. mm_bcta( __( 'Get a *repair quote*', 'minemirth' ), __( 'Send the model, serial and symptoms, plus a photo of the board if you can.', 'minemirth' ), array( array( __( 'Request a repair', 'minemirth' ), '{page:contact}' ) ), array( 'style' => 'light' ) );
	},

	'setup'      => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Setup & firmware tuning', 'minemirth' ),
				'title'   => __( 'ASIC miner setup, from box to *best efficiency*', 'minemirth' ),
				'text'    => __( 'Our ASIC miner setup service gets new units hashing on your pool, keeps firmware current and tunes each miner for the best result at your electricity price.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Book setup', 'minemirth' ), '{page:contact}' ), array( __( 'Firmware guide', 'minemirth' ), '{page:g-firmware}', 'glass' ) ) ),
				'visual'  => 'stats',
				'stats'   => array( array( 'value' => __( 'Pool', 'minemirth' ), 'label' => __( 'configured on your account', 'minemirth' ) ), array( 'value' => __( 'J/TH', 'minemirth' ), 'label' => __( 'tuned for your power price', 'minemirth' ) ), array( 'value' => __( 'Network', 'minemirth' ), 'label' => __( 'secured and documented', 'minemirth' ) ), array( 'value' => __( 'Report', 'minemirth' ), 'label' => __( 'hashrate before and after', 'minemirth' ) ) ),
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'What’s included', 'minemirth' ),
				'title'   => __( 'Setup done *properly*', 'minemirth' ),
				'columns' => '3',
				'items'   => mm_bitems(
					array(
						array( 'globe', __( 'Network', 'minemirth' ), __( 'IP addressing, a separate network for miners where possible, and remote access set up safely.', 'minemirth' ) ),
						array( 'users', __( 'Pool configuration', 'minemirth' ), __( 'Primary and backup pools, worker names and payout settings on your own account.', 'minemirth' ), '{page:g-pool}', __( 'Choosing a pool', 'minemirth' ) ),
						array( 'lock', __( 'Security', 'minemirth' ), __( 'Default passwords changed, unnecessary services off and access documented.', 'minemirth' ), '{page:g-security}', __( 'Security guide', 'minemirth' ) ),
						array( 'refresh', __( 'Firmware', 'minemirth' ), __( 'Current stock firmware, or aftermarket firmware where you want it and the warranty allows.', 'minemirth' ) ),
						array( 'sliders', __( 'Efficiency tuning', 'minemirth' ), __( 'Power targets that minimise cost per TH at your rate, or maximise hashrate if power is cheap.', 'minemirth' ) ),
						array( 'activity', __( 'Monitoring', 'minemirth' ), __( 'Dashboards and alerts so you know when a miner drops off.', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bs(
			'split',
			array(
				'eyebrow' => __( 'Why tuning matters', 'minemirth' ),
				'title'   => __( 'The cheapest terahash is the one you *don’t overpay for*', 'minemirth' ),
				'text'    => __( '<p>An ASIC’s efficiency changes with its clock and voltage. Running slightly below stock often lowers joules per terahash, so each coin costs less power, which matters when hashprice is low or electricity is expensive. When power is cheap, a careful overclock can add hashrate instead.</p><p>We pick the setting that earns the most profit per day at your actual electricity price, and keep chips within safe temperatures.</p>', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Find your break-even power price', 'minemirth' ), '{tool:breakeven}', 'ghost', 'bolt' ) ) ),
				'visual'  => 'market',
				'tone'    => 'alt',
			)
		)
		. mm_bfaq(
			array(
				array( __( 'Can you set up miners remotely?', 'minemirth' ), __( 'Yes, if the miners are online and you give temporary access. Otherwise we guide you step by step.', 'minemirth' ) ),
				array( __( 'Will tuning shorten my miner’s life?', 'minemirth' ), __( 'Underclocking usually reduces heat and stress. Overclocking increases both, so we only do it within safe temperatures and with good cooling.', 'minemirth' ) ),
				array( __( 'Does aftermarket firmware charge a fee?', 'minemirth' ), __( 'Most aftermarket firmware takes a small share of hashrate as a dev fee. We include it in the efficiency comparison so you see the net result.', 'minemirth' ) ),
			)
		)
		. mm_bcta( __( 'Book a *setup*', 'minemirth' ), __( 'Tell us the models, quantity and your power price.', 'minemirth' ), array( array( __( 'Contact us', 'minemirth' ), '{page:contact}' ) ), array( 'style' => 'light' ) );
	},

	'tradein'    => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Trade-in & buyback', 'minemirth' ),
				'title'   => __( 'ASIC miner trade-in: turn old miners into *new efficiency*', 'minemirth' ),
				'text'    => __( 'When newer miners earn far more on the same power, your older units still have value. An ASIC miner trade-in puts them toward a new model and lowers your cost per terahash.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Get a trade-in offer', 'minemirth' ), '{page:contact}' ), array( __( 'Upgrade calculator', 'minemirth' ), '{tool:upgrade}', 'glass', 'calculator' ) ) ),
				'visual'  => 'orbit',
				'icon'    => 'refresh',
			)
		)
		. mm_bs( 'tool', array( 'eyebrow' => __( 'Should you upgrade?', 'minemirth' ), 'title' => __( 'Keep or upgrade: *run the numbers*', 'minemirth' ), 'text' => __( 'Compare your current miners with a new model unit-for-unit, or on the same power budget, with resale value included.', 'minemirth' ), 'id' => 'upgrade' ) )
		. mm_bs(
			'steps',
			array(
				'eyebrow' => __( 'How it works', 'minemirth' ),
				'title'   => __( 'Trade in, *trade up*', 'minemirth' ),
				'tone'    => 'alt',
				'items'   => array(
					array( 'message', __( 'Tell us what you have', 'minemirth' ), __( 'Model, quantity, condition, and whether units are hashing at full rate.', 'minemirth' ) ),
					array( 'tag', __( 'Get an offer', 'minemirth' ), __( 'Based on the model, condition and today’s market for that generation.', 'minemirth' ) ),
					array( 'truck', __( 'Ship and inspect', 'minemirth' ), __( 'We inspect and test the units to confirm condition.', 'minemirth' ) ),
					array( 'cpu', __( 'Apply it to new miners', 'minemirth' ), __( 'The agreed value comes off your new order.', 'minemirth' ) ),
				),
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'What affects value', 'minemirth' ),
				'title'   => __( 'How we *price* a trade-in', 'minemirth' ),
				'columns' => '4',
				'items'   => mm_bitems(
					array(
						array( 'gauge', __( 'Efficiency', 'minemirth' ), __( 'Lower J/TH means more profitable for longer, so more value.', 'minemirth' ) ),
						array( 'activity', __( 'Hashing condition', 'minemirth' ), __( 'Full hashrate on all boards is worth far more than a unit with a dead board.', 'minemirth' ) ),
						array( 'droplet', __( 'History', 'minemirth' ), __( 'Air-cooled units with no corrosion fetch more than ex-immersion units with residue.', 'minemirth' ) ),
						array( 'trending-up', __( 'Market', 'minemirth' ), __( 'Hashprice and demand for that generation on the day.', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bcta( __( 'What are your miners *worth*?', 'minemirth' ), __( 'Send the models and quantities for an offer.', 'minemirth' ), array( array( __( 'Get an offer', 'minemirth' ), '{page:contact}' ) ), array( 'style' => 'light' ) );
	},

	'wholesale'  => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Wholesale & bulk', 'minemirth' ),
				'title'   => __( 'Wholesale ASIC miners with *fewer surprises*', 'minemirth' ),
				'text'    => __( 'Wholesale ASIC miners for tens to hundreds of units: volume pricing, batch allocation, inspection, documentation and freight to your site or hosting facility.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Request a volume quote', 'minemirth' ), '{page:contact}' ), array( __( 'Compare models', 'minemirth' ), '{tool:compare}', 'glass' ) ) ),
				'visual'  => 'checklist',
				'checklist_title' => __( 'Every bulk quote includes', 'minemirth' ),
				'checklist' => array( __( 'Model, hashrate and power per unit', 'minemirth' ), __( 'Unit price and total at your quantity', 'minemirth' ), __( 'Batch and expected ship date', 'minemirth' ), __( 'Payment schedule', 'minemirth' ), __( 'Freight, insurance and documents', 'minemirth' ) ),
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'For fleets', 'minemirth' ),
				'title'   => __( 'Why buy in *volume* with us', 'minemirth' ),
				'columns' => '3',
				'items'   => mm_bitems(
					array(
						array( 'tag', __( 'Tiered pricing', 'minemirth' ), __( 'Better unit prices as quantities grow, quoted in writing.', 'minemirth' ) ),
						array( 'layers', __( 'Mixed fleets', 'minemirth' ), __( 'Combine models and coins to balance efficiency, cost and risk.', 'minemirth' ) ),
						array( 'check-circle', __( 'Batch inspection', 'minemirth' ), __( 'Spot checks and serial lists before freight leaves.', 'minemirth' ) ),
						array( 'truck', __( 'Freight handled', 'minemirth' ), __( 'Palletised, insured freight with customs paperwork.', 'minemirth' ) ),
						array( 'server', __( 'Hosting on arrival', 'minemirth' ), __( 'Deliver straight into hosting capacity reserved for you.', 'minemirth' ) ),
						array( 'file', __( 'Business invoicing', 'minemirth' ), __( 'Invoices with company details and serials for your accounts.', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bs( 'products', array( 'eyebrow' => __( 'Fleet favourites', 'minemirth' ), 'title' => __( 'Efficient miners for *scale*', 'minemirth' ), 'query' => array( 'algo' => 'sha256', 'sort' => 'efficiency' ), 'limit' => 4, 'tone' => 'alt' ) )
		. mm_bcta( __( 'Plan your *fleet*', 'minemirth' ), __( 'Tell us the target hashrate or budget, the site and timing.', 'minemirth' ), array( array( __( 'Request a quote', 'minemirth' ), '{page:contact}' ), array( __( 'Farm design', 'minemirth' ), '{page:consulting}' ) ) );
	},

	'consulting' => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Farm design & consulting', 'minemirth' ),
				'title'   => __( 'Mining farm consulting for a site that *earns from day one*', 'minemirth' ),
				'text'    => __( 'Our mining farm consulting covers power capacity, electrical layout, airflow or liquid cooling, noise, networking, monitoring and the financial model, planned before money is spent.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Book a consultation', 'minemirth' ), '{page:contact}' ), array( __( 'Farm planning guide', 'minemirth' ), '{page:g-farm}', 'glass' ) ) ),
				'visual'  => 'orbit',
				'icon'    => 'layers',
				'chips'   => mm_bchips( array( array( 'bolt', __( 'Power', 'minemirth' ), __( 'capacity and layout', 'minemirth' ) ), array( 'fan', __( 'Cooling', 'minemirth' ), __( 'air, hydro, immersion', 'minemirth' ) ), array( 'bar-chart', __( 'Model', 'minemirth' ), __( 'costs and payback', 'minemirth' ) ) ) ),
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'Scope', 'minemirth' ),
				'title'   => __( 'What we *plan with you*', 'minemirth' ),
				'columns' => '3',
				'items'   => mm_bitems(
					array(
						array( 'bolt', __( 'Power and electrical', 'minemirth' ), __( 'Available capacity, transformer and distribution sizing, PDUs, circuit plans and protection, coordinated with your electrical engineer.', 'minemirth' ), '{tool:power}', __( 'Power planner', 'minemirth' ) ),
						array( 'fan', __( 'Cooling and airflow', 'minemirth' ), __( 'Heat load, intake and exhaust, hot and cold aisles, evaporative cooling, hydro loops or immersion tanks.', 'minemirth' ) ),
						array( 'container', __( 'Buildings and containers', 'minemirth' ), __( 'Container versus building, layout, racking, access and maintenance space.', 'minemirth' ) ),
						array( 'zap-off', __( 'Noise', 'minemirth' ), __( 'Expected noise at the boundary and how to reduce it for neighbours and permits.', 'minemirth' ) ),
						array( 'globe', __( 'Network and monitoring', 'minemirth' ), __( 'Connectivity, redundancy, miner management software and alerting.', 'minemirth' ) ),
						array( 'bar-chart', __( 'Financial model', 'minemirth' ), __( 'Capex, opex, difficulty growth, the halving and sensitivity to BTC price and power cost.', 'minemirth' ), '{tool:roi}', __( 'ROI planner', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bs(
			'steps',
			array(
				'eyebrow' => __( 'Engagement', 'minemirth' ),
				'title'   => __( 'How a *project* runs', 'minemirth' ),
				'tone'    => 'alt',
				'items'   => array(
					array( 'message', __( 'Discovery call', 'minemirth' ), __( 'Site, power, budget, goals and constraints.', 'minemirth' ) ),
					array( 'file', __( 'Assessment', 'minemirth' ), __( 'Feasibility, options and a first financial model.', 'minemirth' ) ),
					array( 'layers', __( 'Design', 'minemirth' ), __( 'Layout, equipment list, cooling and electrical plan with your engineers.', 'minemirth' ) ),
					array( 'rocket', __( 'Deployment support', 'minemirth' ), __( 'Miner supply, commissioning and tuning.', 'minemirth' ) ),
				),
			)
		)
		. mm_bcall( 'info', __( 'Licensed professionals', 'minemirth' ), __( 'Electrical and structural work must be designed, approved and installed by licensed professionals to local codes. We work alongside your engineers and contractors.', 'minemirth' ) )
		. mm_bcta( __( 'Have a site or a *power source*?', 'minemirth' ), __( 'Tell us the capacity, location and timeline.', 'minemirth' ), array( array( __( 'Book a consultation', 'minemirth' ), '{page:contact}' ) ) );
	},

	'business'   => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'For businesses', 'minemirth' ),
				'title'   => __( 'Bitcoin mining for businesses with *power to use*', 'minemirth' ),
				'text'    => __( 'Bitcoin mining for businesses: energy producers with surplus or stranded power, companies with heat to use, and investors who want exposure through infrastructure rather than just holding coins.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Talk to us', 'minemirth' ), '{page:contact}' ), array( __( 'ROI planner', 'minemirth' ), '{tool:roi}', 'glass', 'calculator' ) ) ),
				'visual'  => 'market',
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'Use cases', 'minemirth' ),
				'title'   => __( 'Where mining *fits*', 'minemirth' ),
				'columns' => '2',
				'items'   => mm_bitems(
					array(
						array( 'bolt', __( 'Energy producers', 'minemirth' ), __( 'Miners are a flexible load that can be placed at the generation site and switched off in seconds. They can monetise curtailed solar, wind, hydro or flare gas that has no other buyer.', 'minemirth' ) ),
						array( 'thermometer', __( 'Heat reuse', 'minemirth' ), __( 'Every watt becomes heat. Hydro and immersion miners deliver warm fluid useful for greenhouses, drying, pools or buildings, offsetting other heating costs.', 'minemirth' ), '{page:g-hydro}', __( 'Hydro guide', 'minemirth' ) ),
						array( 'bar-chart', __( 'Investors', 'minemirth' ), __( 'Own miners and host them professionally. Revenue is earned in BTC daily, with clear operating costs.', 'minemirth' ), '{page:hosting}', __( 'Hosting', 'minemirth' ) ),
						array( 'globe', __( 'Grid services', 'minemirth' ), __( 'In some markets, flexible loads are paid to reduce demand during peaks. Mining can be part of that strategy.', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bs(
			'stats',
			array(
				'eyebrow' => __( 'Today’s economics', 'minemirth' ),
				'title'   => __( 'What hashrate earns *right now*', 'minemirth' ),
				'live'    => true,
				'style'   => 'cards',
				'items'   => array(
					array( 'value' => '{hashprice}', 'label' => __( 'per PH/s per day', 'minemirth' ), 'text' => __( 'Hashprice before power costs', 'minemirth' ) ),
					array( 'value' => '{btc_price}', 'label' => __( 'Bitcoin price', 'minemirth' ), 'text' => __( 'Revenue is earned in BTC', 'minemirth' ) ),
					array( 'value' => '{halving_date}', 'label' => __( 'Next halving', 'minemirth' ), 'text' => __( 'Block subsidy 3.125 → 1.5625 BTC', 'minemirth' ) ),
				),
				'tone'    => 'alt',
			)
		)
		. mm_bs(
			'split',
			array(
				'eyebrow' => __( 'Reporting', 'minemirth' ),
				'title'   => __( 'Numbers your *finance team* can use', 'minemirth' ),
				'text'    => __( 'We document equipment by serial number, provide invoices with company details, and help you track hashrate, uptime, coins earned and power used, the data you need for management accounts and tax.', 'minemirth' ),
				'bullets' => array( __( 'Asset lists with serials and purchase dates', 'minemirth' ), __( 'Pool earnings exports', 'minemirth' ), __( 'Power invoices for hosted fleets', 'minemirth' ), __( 'Scenario models for budgeting', 'minemirth' ) ),
				'visual'  => 'orbit',
				'icon'    => 'file',
			)
		)
		. mm_bcall( 'info', __( 'Not financial advice', 'minemirth' ), __( 'Mining revenue depends on Bitcoin’s price, network difficulty and fees, all of which change. Model several scenarios and take professional financial and tax advice before investing.', 'minemirth' ) )
		. mm_bcta( __( 'Explore mining for *your company*', 'minemirth' ), __( 'Share your power source, site or investment goals.', 'minemirth' ), array( array( __( 'Contact us', 'minemirth' ), '{page:contact}' ), array( __( 'Farm design', 'minemirth' ), '{page:consulting}' ) ) );
	},
);
