<?php
/**
 * Content: "Pools & operation" guides.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

return array(

	'g-pool'         => static function () {
		return mm_bp( __( 'Almost every miner earns through a pool. The pool sends your miner work, counts the shares it returns and pays you for them. The pool you choose affects your earnings, how often you’re paid and how much you can verify. Here is how to compare them.', 'minemirth' ) )
		. mm_bh( __( 'What a pool does', 'minemirth' ) )
		. mm_bp( __( 'Your miner connects to the pool’s server over the Stratum protocol, receives block templates to work on and submits “shares”: solutions that meet an easier target than a real block. Shares prove how much work you did. When anyone in the pool finds a block, the pool distributes rewards according to its payout scheme.', 'minemirth' ) )
		. mm_bh( __( 'What to compare', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Payout scheme</strong>: FPPS, PPS+, PPLNS or TIDES decide who carries the luck and how fees are shared. See <a href="{page:g-payouts}">pool payouts explained</a>.', 'minemirth' ),
				__( '<strong>Fee</strong>: typically 0 to 4%. Compare it together with the payout scheme; a slightly higher fee with FPPS can still pay more than a lower one without fee sharing.', 'minemirth' ),
				__( '<strong>Minimum payout and frequency</strong>: daily payouts above a threshold are common; Lightning payouts allow tiny amounts.', 'minemirth' ),
				__( '<strong>Server locations</strong>: a nearby server lowers latency and stale shares.', 'minemirth' ),
				__( '<strong>Transparency</strong>: can you see blocks found, luck and fee calculations? Can you verify payouts?', 'minemirth' ),
				__( '<strong>Account security</strong>: two-factor authentication, watcher links and payout address locks.', 'minemirth' ),
				__( '<strong>Decentralisation features</strong>: some pools support Stratum V2 or let miners build their own block templates.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Setting up a pool connection', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Create an account on the pool and set your payout address (a wallet you control).', 'minemirth' ),
				__( 'Create a worker name for each miner, such as account.miner01.', 'minemirth' ),
				__( 'In the miner’s web interface, enter the pool’s stratum URL, worker name and password (often any value).', 'minemirth' ),
				__( 'Add a second and third pool or server as backups so your miner keeps working if one goes down.', 'minemirth' ),
				__( 'Check the pool dashboard after 15 to 30 minutes: hashrate should match your miner.', 'minemirth' ),
			),
			true
		)
		. mm_bcall( 'warning', __( 'Avoid unknown pools promising more', 'minemirth' ), __( 'Pools can only pay out what the network pays them. Pools promising far above market rates, or hiding their fee structure, are a red flag.', 'minemirth' ) )
		. mm_bh( __( 'Large pool or small pool?', 'minemirth' ) )
		. mm_bp( __( 'Large pools find blocks often and pay predictably. Smaller pools can be excellent and help keep mining decentralised, but on non-FPPS schemes your earnings vary more with luck. Many miners spread hashrate across two pools.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'Can I switch pools any time?', 'minemirth' ), __( 'Yes. Change the stratum URL in your miner. On PPLNS-style pools, switching may forfeit some pending rewards.', 'minemirth' ) ),
				array( __( 'Why does the pool show lower hashrate than my miner?', 'minemirth' ), __( 'Pool hashrate is estimated from shares and fluctuates over short periods. Compare 24-hour averages. Persistent gaps can mean stale or rejected shares.', 'minemirth' ) ),
			)
		);
	},

	'g-payouts'      => static function () {
		return mm_bp( __( 'Pools turn the lumpy reality of finding blocks into regular payments. How they do that is the payout scheme, and it decides who carries the risk of bad luck and whether you share in transaction fees.', 'minemirth' ) )
		. mm_bh( __( 'The main schemes', 'minemirth' ) )
		. mm_bs(
			'table',
			array(
				'columns' => array( __( 'Scheme', 'minemirth' ), __( 'How you are paid', 'minemirth' ), __( 'Luck risk', 'minemirth' ), __( 'Fees included?', 'minemirth' ) ),
				'rows'    => array(
					__( 'PPS (pay per share) | Fixed amount per share, based on the subsidy | Pool | Usually no', 'minemirth' ),
					__( 'FPPS (full pay per share) | Fixed per share, including an estimate of fees | Pool | Yes, estimated', 'minemirth' ),
					__( 'PPS+ | PPS for the subsidy; fees shared PPLNS-style | Pool for subsidy, you for fees | Yes, actual', 'minemirth' ),
					__( 'PPLNS (pay per last N shares) | Share of each block found, based on your recent shares | You | Yes, actual', 'minemirth' ),
					__( 'TIDES | A transparent variant of PPLNS over a long window | You | Yes, actual', 'minemirth' ),
				),
			)
		)
		. mm_bh( __( 'Who carries the luck', 'minemirth' ) )
		. mm_bp( __( 'With PPS and FPPS, the pool pays you the expected value of every share immediately, whether or not it finds blocks. It carries the variance, so fees tend to be higher. With PPLNS and TIDES, you get paid when the pool finds blocks, so your income goes up and down with the pool’s luck, but fees are usually lower and over the long run you receive the actual rewards, including real fees.', 'minemirth' ) )
		. mm_bh( __( 'Which should you choose?', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Predictable income</strong> (for example to pay a power bill): FPPS or PPS+.', 'minemirth' ),
				__( '<strong>Long-term, low-fee, verifiable</strong>: PPLNS or TIDES on a large enough pool.', 'minemirth' ),
				__( '<strong>Hopping between pools</strong>: avoid it on PPLNS; it can cost you pending rewards.', 'minemirth' ),
			)
		)
		. mm_bcall( 'info', __( 'Compare like with like', 'minemirth' ), __( 'Compare pools on net earnings per TH over a month, not on headline fees. An FPPS pool’s fee covers the risk it takes; a PPLNS pool’s lower fee comes with your own variance.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'What is a share?', 'minemirth' ), __( 'A solution to an easier version of the block puzzle. Shares prove how much work your miner did.', 'minemirth' ) ),
				array( __( 'Why are my PPLNS earnings different every day?', 'minemirth' ), __( 'Because they depend on how many blocks the pool found that day. Over weeks they average out.', 'minemirth' ) ),
			)
		);
	},

	'g-firmware'     => static function () {
		return mm_bp( __( 'Firmware is the software that runs your miner: it controls chip frequency and voltage, fans, pool connections and the web interface. Choosing and configuring it well can improve efficiency, reduce noise and add useful features.', 'minemirth' ) )
		. mm_bh( __( 'Stock firmware', 'minemirth' ) )
		. mm_bp( __( 'Manufacturer firmware is tested for the hardware and keeps your warranty intact. Recent versions often include power modes (for example normal, low-power and high-performance). Keep it updated from the manufacturer’s official site only.', 'minemirth' ) )
		. mm_bh( __( 'Aftermarket firmware', 'minemirth' ) )
		. mm_bp( __( 'Several third-party firmwares are available for popular models, especially Antminers. They typically add:', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Autotuning</strong>: per-chip frequency and voltage tuning for a target power or hashrate.', 'minemirth' ),
				__( '<strong>Power targets</strong>: set the watts you want the miner to use.', 'minemirth' ),
				__( '<strong>Better efficiency</strong> at lower power, often a meaningful improvement in J/TH.', 'minemirth' ),
				__( '<strong>Fleet tools</strong>: APIs, scheduling and curtailment support.', 'minemirth' ),
				__( '<strong>Immersion profiles</strong> and fan-less operation.', 'minemirth' ),
			)
		)
		. mm_bp( __( 'Most charge a development fee as a small percentage of hashrate. Compare net results after that fee.', 'minemirth' ) )
		. mm_bcall( 'warning', __( 'Warranty and safety', 'minemirth' ), __( 'Third-party firmware can void the manufacturer’s warranty. Only download from the developer’s official source, check signatures where offered, and never install firmware sent by strangers: malicious firmware can redirect your hashrate.', 'minemirth' ) )
		. mm_bh( __( 'Tuning for profit, not just hashrate', 'minemirth' ) )
		. mm_bp( __( 'Lowering power usually improves efficiency, because chips at lower voltage use disproportionately less energy. The most profitable setting is where the extra hashrate from more power still earns more than the power costs. With expensive power or a low hashprice, that point is below stock; with cheap power, it may be above.', 'minemirth' ) )
		. mm_bs( 'tool', array( 'title' => __( 'Test settings: hashrate and power → *profit*', 'minemirth' ), 'id' => 'profit' ) )
		. mm_bh( __( 'Safe tuning checklist', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Change one thing at a time and let the miner stabilise for an hour.', 'minemirth' ),
				__( 'Watch chip and board temperatures and hardware error rates.', 'minemirth' ),
				__( 'Keep PSU load within its rating when overclocking.', 'minemirth' ),
				__( 'Record settings that work for each model and season.', 'minemirth' ),
			)
		)
		. mm_bfaq(
			array(
				array( __( 'Can I go back to stock firmware?', 'minemirth' ), __( 'Usually yes, following the developer’s instructions or via SD card recovery. It may not restore warranty eligibility.', 'minemirth' ) ),
				array( __( 'Does firmware change the noise?', 'minemirth' ), __( 'Yes. Lower power targets mean less heat and slower fans.', 'minemirth' ) ),
			)
		);
	},

	'g-maintenance'  => static function () {
		return mm_bp( __( 'ASICs have few moving parts, but the ones they have, mostly fans, wear out, and dust, heat and loose connections cause most failures. A simple routine keeps uptime high and avoids expensive repairs.', 'minemirth' ) )
		. mm_bh( __( 'Daily (or automated)', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Check hashrate per miner against its expected value; investigate drops over 5 to 10%.', 'minemirth' ),
				__( 'Watch temperatures and fan speeds for outliers.', 'minemirth' ),
				__( 'Check the pool for stale or rejected shares.', 'minemirth' ),
				__( 'Set alerts so offline miners are noticed within minutes, not days.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Monthly', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Blow dust out of heatsinks and fans with dry compressed air, holding fans still so they don’t over-spin. Power off first.', 'minemirth' ),
				__( 'Clean or replace intake filters.', 'minemirth' ),
				__( 'Inspect power cords and connectors for discoloration or heat.', 'minemirth' ),
				__( 'Check fans for noise, wobble or slow spin-up.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Every 6 to 12 months', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Deep clean in dusty environments; inspect hashboards for corrosion.', 'minemirth' ),
				__( 'Re-seat data and power cables between hashboards and the control board.', 'minemirth' ),
				__( 'Update firmware after reading the release notes.', 'minemirth' ),
				__( 'Review settings for the season: efficiency in summer heat, more hashrate in cool months.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Spares to keep on the shelf', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Fans: the most common failure. One spare per few miners.', 'minemirth' ),
				__( 'A power supply matching your models.', 'minemirth' ),
				__( 'Power cords and network cables.', 'minemirth' ),
				__( 'An SD card with recovery firmware for each model.', 'minemirth' ),
			)
		)
		. mm_bcall( 'success', __( 'Log everything', 'minemirth' ), __( 'Keep a simple log per miner: serial, location, firmware, faults and repairs. Patterns show up fast and warranty claims get easier.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'Can I clean a miner with water?', 'minemirth' ), __( 'No. Use dry compressed air or a soft brush. Some professional services wash boards with special fluids and full drying, but never with the miner powered.', 'minemirth' ) ),
				array( __( 'How long do miners last?', 'minemirth' ), __( 'Well-cooled miners can run for many years. Economic life is usually shorter: they are replaced when newer, more efficient models earn much more on the same power.', 'minemirth' ) ),
			)
		);
	},

	'g-troubleshoot' => static function () {
		return mm_bp( __( 'Most ASIC problems come down to a handful of causes: power, heat, cables, network or a failing component. Work through these checks in order before assuming a board is dead.', 'minemirth' ) )
		. mm_bcall( 'warning', __( 'Under warranty?', 'minemirth' ), __( 'Don’t open sealed parts, change firmware or attempt board repairs on a unit under warranty. Collect the symptoms and logs and contact us or the manufacturer first.', 'minemirth' ), array( 'button_label' => __( 'Warranty & support', 'minemirth' ), 'button_url' => '{page:warranty}' ) )
		. mm_bh( __( 'Miner won’t power on', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Confirm the outlet and breaker are live and the voltage matches the PSU.', 'minemirth' ),
				__( 'Try a different power cord.', 'minemirth' ),
				__( 'Listen for the PSU fan and look for lights on the control board.', 'minemirth' ),
				__( 'If the PSU is dead, test with a known-good PSU before replacing other parts.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Can’t find the miner on the network', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Check the Ethernet cable and the link lights on the port.', 'minemirth' ),
				__( 'Look for the miner’s IP in your router’s DHCP list, or use the manufacturer’s IP scanner tool.', 'minemirth' ),
				__( 'Many miners have an IP report button that broadcasts the address.', 'minemirth' ),
				__( 'If it was set to a static IP on another network, use the reset procedure for that model.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'A hashboard is missing or shows 0 chips', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Power off and re-seat the data (ribbon) cable and power connectors for that board.', 'minemirth' ),
				__( 'Swap the data cable with a working board’s to see if the fault follows the cable or the board.', 'minemirth' ),
				__( 'Read the kernel log: it often names the failing chip or domain.', 'minemirth' ),
				__( 'If the fault follows the board, it needs repair. See <a href="{page:repairs}">repairs & servicing</a>.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Fan errors', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Check that every fan spins and its plug is fully seated.', 'minemirth' ),
				__( 'Swap fans between positions to find the faulty one.', 'minemirth' ),
				__( 'Replace worn fans with the same model or an exact spec match.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Overheating or “temperature too high”', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Measure intake air temperature; reduce it or improve airflow.', 'minemirth' ),
				__( 'Make sure exhaust air is not recirculating into the intake.', 'minemirth' ),
				__( 'Clean dust from heatsinks and fans.', 'minemirth' ),
				__( 'Lower the power target until cooling is fixed. See <a href="{page:g-cooling}">cooling & ventilation</a>.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Hashrate lower than expected', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Compare the miner’s own reading with the pool’s 24-hour average.', 'minemirth' ),
				__( 'Check for high hardware error rates in the logs, a sign of heat or a weak board.', 'minemirth' ),
				__( 'Check stale or rejected shares at the pool; try a closer server.', 'minemirth' ),
				__( 'Confirm the power mode and firmware settings.', 'minemirth' ),
			)
		)
		. mm_bcall( 'info', __( 'When to ask for help', 'minemirth' ), __( 'Send us the model, serial, a photo of the status page and the kernel log. That is usually enough to identify the fault.', 'minemirth' ), array( 'button_label' => __( 'Contact support', 'minemirth' ), 'button_url' => '{page:contact}' ) )
		. mm_bfaq(
			array(
				array( __( 'Where do I find the kernel log?', 'minemirth' ), __( 'In the miner’s web interface, usually under System or Status. Copy the full text when reporting a fault.', 'minemirth' ) ),
				array( __( 'Should I reflash the firmware?', 'minemirth' ), __( 'If the control board boot-loops or the interface is broken, a recovery flash from SD card often fixes it. Use the manufacturer’s official image.', 'minemirth' ) ),
			)
		);
	},
);
