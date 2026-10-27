<?php
/**
 * SEO sections for the 24 guides (see seo-company.php). Paragraphs starting with ### become
 * sub-headings.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_a = static fn( string $h, array $p ) => mm_bseo( $h, $p, '', true );

return array(

	'seo:g-beginners'     => static fn() => $mm_a(
		__( 'Bitcoin mining for beginners: common mistakes to avoid', 'minemirth' ),
		array(
			__( '<strong>Buying before checking your power price.</strong> Electricity is the largest running cost, so the same miner can be profitable for one person and a loss for another. Look up your all-in rate per kWh first and test it in the <a href="{tool:breakeven}">break-even calculator</a>.', 'minemirth' ),
			__( '<strong>Planning with today’s numbers only.</strong> Difficulty usually rises and the halving cuts the subsidy in half every four years. Model a cautious scenario in the <a href="{tool:roi}">ROI planner</a> before you buy.', 'minemirth' ),
			__( '<strong>Underestimating noise and heat.</strong> A full-size ASIC is as loud as a vacuum cleaner and as warm as a heater. If that will not work at home, consider a quiet home miner or hosting. Every guide on Bitcoin mining for beginners should say this plainly, so we do.', 'minemirth' ),
			__( 'Following a guide to Bitcoin mining for beginners is the cheapest way to start: learn first, then buy.', 'minemirth' ),
		)
	),

	'seo:g-choose'        => static fn() => $mm_a(
		__( 'How to choose an ASIC miner in five minutes', 'minemirth' ),
		array(
			__( 'If you only have a few minutes, here is how to choose an ASIC miner: open the <a href="{tool:compare}">miner comparison</a>, enter your electricity price, filter by the cooling you can support, and sort by profit per day. Then look at the top three for price per TH, efficiency and payback.', 'minemirth' ),
			__( 'Cross out anything with a break-even power price close to what you pay, because it will struggle after the next difficulty increases. Of the rest, choose the model whose noise, size and power needs fit your site. Finally, confirm lead time and warranty. That shortlist covers most of the decision; the rest of this guide explains each step in more depth.', 'minemirth' ),
			__( 'Once you know how to choose an ASIC miner, the same steps work for every future purchase and upgrade.', 'minemirth' ),
		)
	),

	'seo:g-profit'        => static fn() => $mm_a(
		__( 'Mining profitability in different situations', 'minemirth' ),
		array(
			'###' . __( 'Cheap power, older miners', 'minemirth' ),
			__( 'With electricity well below the network average, even older, less efficient miners can earn. Mining profitability here depends mostly on the price you pay for the hardware, so used and best value models can pay back quickly.', 'minemirth' ),
			'###' . __( 'Average power, current miners', 'minemirth' ),
			__( 'At typical commercial rates, only efficient current-generation miners stay comfortably profitable. Margins are thinner, so uptime, pool fees and tuning start to matter.', 'minemirth' ),
			'###' . __( 'Expensive power at home', 'minemirth' ),
			__( 'At high residential rates, full-size miners often lose money. Mining profitability can still be positive if you use the heat to replace other heating, or if you move the miner to <a href="{page:hosting}">hosting</a> on industrial power.', 'minemirth' ),
			__( 'In every case, check mining profitability over the miner’s whole life, not just today: run the <a href="{tool:roi}">ROI planner</a> with difficulty growth and the next halving.', 'minemirth' ),
			__( 'Finally, remember that mining profitability is not fixed after you buy. Tuning, cleaning, a better pool and cheaper power can all lift it over the miner’s life.', 'minemirth' ),
			'###' . __( 'A quick sanity check', 'minemirth' ),
			__( 'If a projection shows payback in a few weeks, look again: it probably assumes no difficulty growth, a very high price or a missing cost. Realistic projections for new miners usually show payback in many months, not weeks.', 'minemirth' ),
		)
	),

	'seo:g-hashprice'     => static fn() => $mm_a(
		__( 'Hashprice and your own break-even', 'minemirth' ),
		array(
			__( 'The network-wide number becomes useful when you compare it with your own costs. Your miner’s power cost per terahash per day is fixed by two things: its efficiency in joules per terahash and what you pay for electricity. When revenue per terahash falls toward that cost, your margin disappears.', 'minemirth' ),
			__( 'Here is a simple way to think about it. A miner at 17 J/TH uses about 0.41 kWh per terahash per day. At $0.07 per kWh, that is about $0.029 per terahash per day, or $29 per petahash. While revenue per petahash stays above that line, the miner earns; below it, it runs at a loss.', 'minemirth' ),
			__( 'This is why efficient miners survive downturns. When revenue drops after a halving or a price fall, the least efficient units cross their break-even line first and are switched off, which slows difficulty growth and eventually helps the remaining miners.', 'minemirth' ),
			__( 'Use the <a href="{tool:hashprice}">tracker</a> to watch the trend and the <a href="{tool:breakeven}">break-even calculator</a> to find your own line. Keeping a comfortable gap between the two is the core of running a miner profitably.', 'minemirth' ),
			__( 'A practical habit: note revenue per petahash on the first day of each month, next to your fleet’s average efficiency and your power price. After a few months you will see your margin trend clearly and can act before it turns negative.', 'minemirth' ),
			__( 'When comparing miners for a purchase, look at the same picture in reverse. Ask how far revenue per terahash could fall before each candidate reaches break-even. The model with the largest cushion is usually the safer buy, even if another one earns slightly more today.', 'minemirth' ),
		)
	),

	'seo:g-difficulty'    => static fn() => $mm_a(
		__( 'Mining difficulty in practice', 'minemirth' ),
		array(
			'###' . __( 'What a rise means for one miner', 'minemirth' ),
			__( 'When mining difficulty rises by 3%, the same miner finds about 3% fewer bitcoin per day, because it now holds a smaller share of the network. Its power bill stays the same, so the whole loss comes out of your margin.', 'minemirth' ),
			'###' . __( 'Why it rarely falls for long', 'minemirth' ),
			__( 'Decreases happen, usually after a price drop or a halving when older miners switch off. But as long as mining is profitable for someone, new, more efficient machines keep arriving, and mining difficulty climbs again.', 'minemirth' ),
			'###' . __( 'How to plan for it', 'minemirth' ),
			__( 'Assume steady growth in your projections. Many operators model 1 to 4% per month and check how sensitive their payback is to that number. The <a href="{tool:difficulty}">difficulty estimator</a> shows the next adjustment, and the <a href="{tool:roi}">ROI planner</a> lets you set the monthly growth rate.', 'minemirth' ),
			__( 'You cannot control mining difficulty, but you can control your exposure to it: buy efficient hardware, keep power costs low and aim for a payback period that does not depend on the network standing still.', 'minemirth' ),
			__( 'It also helps to know what moves difficulty in the short term. New hardware shipments, large farms coming online, seasonal power prices and curtailment events in big mining regions all change the network hashrate, and the next adjustment reflects them about two weeks later.', 'minemirth' ),
			__( 'Watching these signals will not let you predict every adjustment, but it explains why mining difficulty sometimes jumps after a quiet period, and why a cautious growth rate belongs in every projection.', 'minemirth' ),
			'###' . __( 'Difficulty and hashprice', 'minemirth' ),
			__( 'Difficulty is one half of the story. The other is price: when the Bitcoin price rises faster than difficulty, miners earn more per terahash even as the network grows. Tracking both together, as the <a href="{tool:hashprice}">hashprice tracker</a> does, gives the clearest view of your earnings over time.', 'minemirth' ),
			__( 'In short, expect difficulty to rise over the life of any miner, and let efficiency and cheap power carry you through.', 'minemirth' ),
			__( 'Plan for it, and difficulty becomes just another number in your model rather than a surprise.', 'minemirth' ),
		)
	),

	'seo:g-halving'       => static fn() => $mm_a(
		__( 'Preparing for the Bitcoin halving step by step', 'minemirth' ),
		array(
			'###' . __( 'Twelve months before', 'minemirth' ),
			__( 'List every miner with its efficiency and its break-even power price at half the current subsidy. Units that will fall below break-even after the Bitcoin halving are candidates for replacement, resale or a move to cheaper power.', 'minemirth' ),
			'###' . __( 'Six months before', 'minemirth' ),
			__( 'Order replacements early. Demand for efficient miners tends to rise ahead of a halving, and delivery windows can stretch. Lock in power contracts and hosting plans while you can still compare offers.', 'minemirth' ),
			'###' . __( 'On the day and after', 'minemirth' ),
			__( 'Revenue per terahash drops overnight. Expect difficulty to fall a little in the following weeks as older machines switch off, then to resume its usual climb. Keep watching the <a href="{tool:halving}">halving countdown and impact tool</a>, and re-tune miners for efficiency if margins are thin.', 'minemirth' ),
			__( 'The Bitcoin halving is the most predictable event in mining. Operators who plan for it calmly usually come out stronger, because their competition shrinks just as their own costs are under control.', 'minemirth' ),
			'###' . __( 'What changes and what does not', 'minemirth' ),
			__( 'The Bitcoin halving cuts only the block subsidy. Transaction fees, difficulty and the price are set by the market. That is why revenue usually falls by a little less than half on the day, and why the months after a halving can look very different from one cycle to the next.', 'minemirth' ),
			__( 'Plan for the subsidy cut you know about, and treat any price or fee changes as a bonus rather than the plan.', 'minemirth' ),
			'###' . __( 'Lessons from past halvings', 'minemirth' ),
			__( 'Each halving so far has been followed by a period of lower revenue per terahash, a drop in the least efficient hashrate and then renewed growth. The details differ every time, which is why sensible operators plan for the subsidy cut and keep a margin for everything else.', 'minemirth' ),
		)
	),

	'seo:g-power'         => static fn() => $mm_a(
		__( 'ASIC power checklist before you plug in', 'minemirth' ),
		array(
			__( 'Before you connect a miner, check four things. First, the ASIC power supply input range on the label matches your voltage. Second, the circuit and breaker are rated for the continuous current with the 80% margin. Third, the cord and plug are rated for the current, with the right inlet such as C19 for high-current units. Fourth, nothing else heavy shares the circuit.', 'minemirth' ),
			__( 'Then watch the first hours. Feel the plug and cord after an hour of running: warm is normal, hot is not. A loose or undersized connection is the most common cause of melted plugs. If you are planning more than one or two miners, use the <a href="{tool:power}">power and circuit planner</a> to size the whole ASIC power setup and take the results to your electrician.', 'minemirth' ),
		)
	),

	'seo:g-cooling'       => static fn() => $mm_a(
		__( 'ASIC cooling mistakes to avoid', 'minemirth' ),
		array(
			__( '<strong>Recirculating hot air.</strong> The most common ASIC cooling problem is exhaust air finding its way back to the intake. Separate the hot and cold sides with a wall, a curtain or ducting, and make sure hot air leaves the room.', 'minemirth' ),
			__( '<strong>Ignoring the incoming air.</strong> Intake temperature matters more than room temperature. In summer, a shed or garage can be far hotter than the outside air; measure at the front of the miner.', 'minemirth' ),
			__( '<strong>Forgetting dust and humidity.</strong> Filters on the intake keep heatsinks clean but need regular changes. Avoid condensation: cold, humid air on warm boards causes corrosion.', 'minemirth' ),
			__( '<strong>Undersizing the fans.</strong> Exhaust fans must move at least as much air as all the miners combined. The <a href="{tool:power}">power planner</a> estimates the airflow you need. Good ASIC cooling is mostly about moving enough air in the right direction.', 'minemirth' ),
			'###' . __( 'Measuring what matters', 'minemirth' ),
			__( 'Place a cheap thermometer and hygrometer at the miner intakes and another at the exhaust. Log the readings for a week across warm and cool hours. The difference tells you whether airflow is enough, and the intake readings show whether your room or shed is drawing in heat from the sun or from recirculated exhaust.', 'minemirth' ),
			'###' . __( 'When to upgrade cooling', 'minemirth' ),
			__( 'If miners throttle on warm afternoons, if fan speeds sit near maximum, or if hashrate dips in summer, the cooling has reached its limit. Adding exhaust capacity, better separation of hot and cold air, or moving to hydro or immersion are the usual next steps. Good ASIC cooling pays for itself in steadier hashrate and longer hardware life.', 'minemirth' ),
		)
	),

	'seo:g-noise'         => static fn() => $mm_a(
		__( 'Planning around ASIC noise at home', 'minemirth' ),
		array(
			__( 'Start with distance and walls. Sound falls quickly with distance, and every solid wall between you and the miner helps. A detached garage, a basement room or a shed usually solves ASIC noise better than any accessory.', 'minemirth' ),
			__( 'Duct the airflow, not just the sound. A sound-dampening duct on the exhaust, with gentle bends and absorbent lining, can take a large bite out of the noise that reaches the rest of the house, while still letting hot air out. Never box a miner in without enough airflow: it will overheat.', 'minemirth' ),
			__( 'Lower the fan speed by lowering the heat. Underclocking with suitable firmware reduces power, heat and fan speed together, which can make the miner noticeably quieter while also improving efficiency.', 'minemirth' ),
			__( 'Or choose a quieter design. Hydro, immersion and dedicated home miners avoid most fan noise. If ASIC noise is the main obstacle, a <a href="{page:m-home}">quiet home miner</a> or <a href="{page:hosting}">hosting</a> is often the simplest answer.', 'minemirth' ),
			'###' . __( 'Measure before and after', 'minemirth' ),
			__( 'A free sound meter app gives a rough reading in decibels. Measure at the miner, in the next room and at the property line before you start, then after each change. Remember that the scale is logarithmic: a drop of 10 dB sounds roughly half as loud, so small numbers make a big difference.', 'minemirth' ),
			'###' . __( 'Think about the neighbours', 'minemirth' ),
			__( 'Low-frequency fan hum travels further than you expect, especially at night. Point exhausts away from neighbouring homes, avoid thin outbuildings close to property lines and check local noise rules before running miners around the clock. Managing ASIC noise early avoids far bigger problems later.', 'minemirth' ),
			'###' . __( 'Ask before you buy', 'minemirth' ),
			__( 'Noise ratings on spec sheets are measured in specific conditions. If quiet operation matters, ask for real-world experience with the exact model, or choose a design built for quiet operation from the start.', 'minemirth' ),
			__( 'Combining two or three of these steps usually works better than any single fix, and each one also helps keep the miner cool.', 'minemirth' ),
		)
	),

	'seo:g-immersion'     => static fn() => $mm_a(
		__( 'Planning an immersion cooling system', 'minemirth' ),
		array(
			'###' . __( 'Sizing', 'minemirth' ),
			__( 'Start from the heat load: the total power of all miners, plus a margin if you plan to overclock. Tanks, pumps and dry coolers are rated in kilowatts of heat they can remove at given fluid and outside air temperatures. Size for your hottest days.', 'minemirth' ),
			'###' . __( 'Fluid', 'minemirth' ),
			__( 'Single-phase dielectric fluids are the usual choice for mining. Check compatibility with cables and components, the flash point and the supplier’s handling guidance. Fluid is a large part of the cost, so tank design that minimises volume pays off.', 'minemirth' ),
			'###' . __( 'Site and maintenance', 'minemirth' ),
			__( 'Immersion cooling needs a level floor that can take the weight of full tanks, spill containment, and room to lift miners out for service. Plan for filter changes and periodic fluid checks.', 'minemirth' ),
			__( 'Done well, immersion cooling gives quiet operation, stable temperatures and longer hardware life. Our <a href="{page:m-immersion}">immersion miners</a> page lists models suited to tanks.', 'minemirth' ),
			'###' . __( 'Overclocking with care', 'minemirth' ),
			__( 'Immersion cooling lets miners run at higher power targets because chip temperatures stay low and stable. Raise power in small steps, watch efficiency and error rates, and stop when extra hashrate costs more power than it is worth at your electricity price.', 'minemirth' ),
			'###' . __( 'Costs to include', 'minemirth' ),
			__( 'Budget for tanks, fluid, pumps, heat exchangers, dry coolers, piping, monitoring and the electrical work. Immersion usually costs more up front than air cooling, and it pays off through higher density, longer hardware life, lower noise and the option to reuse heat.', 'minemirth' ),
			'###' . __( 'Is immersion right for you?', 'minemirth' ),
			__( 'Immersion suits dense sites, hot or dusty climates, places where noise matters and operators who can use the heat. For one or two miners in a garage, air cooling with good ducting is usually simpler and cheaper. For a growing fleet, immersion cooling deserves a serious look, and our team can help you size a first system.', 'minemirth' ),
			'###' . __( 'Safety', 'minemirth' ),
			__( 'Keep fluid away from ignition sources, follow the supplier’s safety data sheet, wear gloves when handling wet hardware, and make sure electrical work around tanks is done by a qualified electrician to local code.', 'minemirth' ),
			__( 'With careful planning, an immersion site can run quietly and reliably for years.', 'minemirth' ),
		)
	),

	'seo:g-hydro'         => static fn() => $mm_a(
		__( 'Running hydro-cooled miners day to day', 'minemirth' ),
		array(
			'###' . __( 'Water quality', 'minemirth' ),
			__( 'Follow the manufacturer’s coolant specification. Most hydro-cooled miners expect clean, treated water or a specified coolant mix, filtered to remove particles. Poor water quality causes scaling and corrosion inside the cold plates, which slowly reduces cooling.', 'minemirth' ),
			'###' . __( 'Flow and temperature', 'minemirth' ),
			__( 'Monitor flow rate and inlet and outlet temperatures for every rack. A drop in flow is often the first sign of a blocked filter or a failing pump. Keep inlet temperature inside the specified window: too warm reduces performance, too cold risks condensation.', 'minemirth' ),
			'###' . __( 'Leaks and maintenance', 'minemirth' ),
			__( 'Use quality hoses and quick connectors, check them during routine maintenance and keep leak detection under the racks. With good water and steady flow, hydro-cooled miners are among the most reliable machines in a site.', 'minemirth' ),
			'###' . __( 'Using the heat', 'minemirth' ),
			__( 'The warm outlet water is a real asset. It can heat buildings, greenhouses or process water through a heat exchanger. If you can sell or use the heat, hydro-cooled miners become even more attractive. See our <a href="{page:m-hydro}">hydro miners</a> for models.', 'minemirth' ),
			'###' . __( 'Planning the loop', 'minemirth' ),
			__( 'Size pumps and heat rejection for the full load at your hottest expected weather. Include valves to isolate each rack, filters that are easy to reach and enough spare capacity to add miners later. A well-designed loop is quiet, simple to service and lets hydro-cooled miners run at their rated efficiency all year.', 'minemirth' ),
			'###' . __( 'Costs', 'minemirth' ),
			__( 'Hydro sites cost more to build than air-cooled ones, mainly for the loop and heat rejection equipment. In return, they fit more hashrate into less space, cut noise and can earn money from heat, which can make the total cost per terahash lower over the life of the site.', 'minemirth' ),
			'###' . __( 'Getting started', 'minemirth' ),
			__( 'If you are new to liquid cooling, start with a small loop and a few units before scaling. You will learn the routines for filling, bleeding air, checking flow and servicing filters, and those habits make a larger hydro site run smoothly. Ask us which hydro models we have in stock and what each needs.', 'minemirth' ),
			'###' . __( 'Choosing a coolant', 'minemirth' ),
			__( 'Use the coolant the manufacturer specifies, or treated water within its stated limits, and check it on a schedule. Mixing coolants or topping up with untreated tap water can shorten the life of cold plates and pumps.', 'minemirth' ),
		)
	),

	'seo:g-home'          => static fn() => $mm_a(
		__( 'Is mining at home worth it?', 'minemirth' ),
		array(
			__( 'Mining at home is worth it when three things line up: affordable electricity, a place where heat and noise are acceptable, and a miner sized for your circuit. If you pay residential rates in the higher range, a full-size miner may lose money unless you use its heat.', 'minemirth' ),
			__( 'Heat is the hidden advantage. In a cold climate, a miner replaces some of the energy you would spend on heating anyway, so the real cost of running it in winter is lower than the bill suggests. In summer, the opposite is true: the heat may need cooling.', 'minemirth' ),
			__( 'Start small if you are unsure. A quiet home miner lets you learn the setup, pool and monitoring with little risk. When you are ready to scale, compare the numbers for mining at home and hosting in the <a href="{tool:hosting}">hosting vs home calculator</a>.', 'minemirth' ),
			'###' . __( 'Talk to the people you live with', 'minemirth' ),
			__( 'Explain the noise, the heat and the electricity use before the miner arrives, and agree where it will go. A setup everyone is comfortable with keeps running; one that annoys the household gets switched off.', 'minemirth' ),
			'###' . __( 'Plan for summer', 'minemirth' ),
			__( 'In warm months, the heat that helped in winter becomes a problem. Plan how you will vent it outside, reduce power or switch the miner off on hot afternoons when your electricity is most expensive. Good planning makes mining at home work all year round.', 'minemirth' ),
			__( 'If you decide home is not right, nothing is lost: the same miner can be shipped to a hosting facility later.', 'minemirth' ),
			__( 'Start with one miner, learn how it behaves in your home through a full season, and grow from there.', 'minemirth' ),
		)
	),

	'seo:g-solo'          => static fn() => $mm_a(
		__( 'Solo mining safely and sensibly', 'minemirth' ),
		array(
			__( 'Use a reputable solo pool, or run your own node and pool software if you have the skills. Check the pool’s fee and how it pays: with solo mining, the block reward should go directly to your own address.', 'minemirth' ),
			__( 'Treat it as a lottery ticket with a learning bonus, not an income. Budget the electricity as entertainment, keep the device updated and secure on your network, and enjoy watching your best share climb.', 'minemirth' ),
			__( 'If you run full-size miners for income, keep them in a pool. Many people point one small device at a solo pool for the chance of a block and keep everything else on steady pool payouts.', 'minemirth' ),
			'###' . __( 'Running your own node', 'minemirth' ),
			__( 'Advanced users can run their own Bitcoin node with pool software and point miners at it. You then build your own block templates, which supports decentralisation, but it needs a reliable machine, good internet and some technical comfort.', 'minemirth' ),
			'###' . __( 'Hashrate rental and big miners', 'minemirth' ),
			__( 'Some people point a full-size miner or rented hashrate at a solo pool for a short time. The odds improve with hashrate, but the expected value is still the same as in a pool on average, only far more variable. Decide in advance how long you will try and how much you are willing to spend.', 'minemirth' ),
			__( 'Whatever you choose, keep your expectations honest and your setup secure, and enjoy the small chance of a very large win.', 'minemirth' ),
			__( 'Many solo miners also enjoy the community around open-source devices.', 'minemirth' ),
		)
	),

	'seo:g-pool'          => static fn() => $mm_a(
		__( 'Switching or adding a mining pool', 'minemirth' ),
		array(
			__( 'You can change pools at any time by updating the pool address and worker name in your miner’s settings. Before switching, note your current balance and check the new pool’s minimum payout, so funds are not left below a threshold.', 'minemirth' ),
			__( 'Always configure at least two pool servers. If the main one is unreachable, the miner fails over to the backup within seconds and keeps earning. Some operators use a second mining pool entirely as the backup, for resilience against outages.', 'minemirth' ),
			__( 'Compare results fairly. Pool earnings vary day to day with luck, so judge a mining pool over weeks, against the expected earnings for your hashrate. A consistent shortfall points to fees, stale shares or connection problems; a short dip is usually just variance.', 'minemirth' ),
			__( 'Check transparency, too: good pools publish their payout method, fees, blocks found and a clear way to verify your shares. That openness is a good sign whatever mining pool you choose.', 'minemirth' ),
			'###' . __( 'Large pools and decentralisation', 'minemirth' ),
			__( 'A small number of large pools find most Bitcoin blocks. Spreading hashrate across more pools, or using protocols that let miners build their own block templates, supports a healthier network. If you care about this, look for pools that support Stratum V2 or similar features.', 'minemirth' ),
			'###' . __( 'Minimum payouts and fees', 'minemirth' ),
			__( 'Low minimum payouts help small miners receive their earnings regularly, but frequent on-chain payouts cost fees. Choose a threshold that balances how often you want to be paid with the fee per payout.', 'minemirth' ),
			__( 'Taking an hour to compare pools carefully can add up to a noticeable difference in earnings over the life of a miner.', 'minemirth' ),
		)
	),

	'seo:g-payouts'       => static fn() => $mm_a(
		__( 'Comparing pool payouts in practice', 'minemirth' ),
		array(
			'###' . __( 'Same hashrate, different patterns', 'minemirth' ),
			__( 'Under FPPS, pool payouts arrive as a steady daily amount that includes an estimate of transaction fees. Under PPLNS, your daily income moves with the pool’s luck: some days above average, some below, with the long-run average depending on how often the pool finds blocks.', 'minemirth' ),
			'###' . __( 'Fees and risk', 'minemirth' ),
			__( 'Pools that carry the luck for you charge for it, so FPPS fees are usually higher than PPLNS fees. If you can live with variance, a lower-fee, luck-based scheme can pay more over time. If you need predictable income, for example to cover a power bill, steady pool payouts are worth the extra fee.', 'minemirth' ),
			'###' . __( 'Minimums and timing', 'minemirth' ),
			__( 'Check the minimum payout and how often the pool pays. Small miners can take a long time to reach a high threshold, and on-chain payout fees matter more for small amounts. Some pools offer Lightning payouts for small balances.', 'minemirth' ),
			__( 'Whatever you choose, compare pool payouts over at least a few weeks, using the expected earnings for your hashrate as the benchmark.', 'minemirth' ),
			'###' . __( 'Transaction fees', 'minemirth' ),
			__( 'Pay attention to how fees are shared. Under PPS, you are usually paid for the subsidy only; FPPS adds an estimate of fees; PPLNS and TIDES share the actual fees in the blocks the pool finds. When fees are high, that difference can be meaningful.', 'minemirth' ),
			'###' . __( 'Switching schemes', 'minemirth' ),
			__( 'Under luck-based schemes, leaving a pool early can mean giving up part of the rewards for shares you already submitted, because payment depends on blocks found after your work. Read how the scheme handles this before you move hashrate.', 'minemirth' ),
			'###' . __( 'A simple rule', 'minemirth' ),
			__( 'If you value predictability, choose steady pool payouts and accept a slightly higher fee. If you run enough hashrate to ride out variance, compare the long-run results of luck-based schemes.', 'minemirth' ),
			__( 'Ask the pool for its payout history and fee schedule in writing if anything is unclear; a reputable pool will explain it.', 'minemirth' ),
			'###' . __( 'Checking your earnings', 'minemirth' ),
			__( 'Most pools show your expected and actual earnings. Compare them over a month, and if the gap stays large, check for stale shares, connection problems or a misconfigured worker before blaming the payout method.', 'minemirth' ),
		)
	),

	'seo:g-firmware'      => static fn() => $mm_a(
		__( 'Choosing ASIC firmware for your goals', 'minemirth' ),
		array(
			'###' . __( 'Lowest cost per terahash', 'minemirth' ),
			__( 'If your power is expensive, use ASIC firmware that can tune each chip for efficiency and set a lower power target. You give up some hashrate but earn more per kWh, which often raises profit.', 'minemirth' ),
			'###' . __( 'Maximum hashrate', 'minemirth' ),
			__( 'On very cheap power with strong cooling, a higher power target can pay. Raise it gradually, watching chip temperatures, error rates and the power supply’s limits.', 'minemirth' ),
			'###' . __( 'Quiet operation', 'minemirth' ),
			__( 'Lower power means lower heat and slower fans. Some ASIC firmware offers fan curves and quiet modes for home use.', 'minemirth' ),
			'###' . __( 'Fleet management', 'minemirth' ),
			__( 'For many units, look for remote management, bulk updates and monitoring. Whatever you choose, download ASIC firmware only from the manufacturer or the developer’s official site, check its signature or checksum, and keep a copy of the stock firmware.', 'minemirth' ),
			'###' . __( 'Testing a change', 'minemirth' ),
			__( 'Change one setting at a time and let the miner run for at least a few hours before judging it. Compare hashrate on the pool side, not just the miner’s own display, and measure power at the wall if you can. The <a href="{tool:profit}">profitability calculator</a> turns the result into profit at your power price.', 'minemirth' ),
			'###' . __( 'Updates', 'minemirth' ),
			__( 'Read release notes before updating, update one unit first, and keep a rollback plan. A careful process makes ASIC firmware changes low-risk even across a large fleet.', 'minemirth' ),
			'###' . __( 'Warranty and support', 'minemirth' ),
			__( 'Some manufacturers limit warranty cover when aftermarket firmware is installed. Check before changing it, and keep the stock image so you can restore it if a unit has to go back for repair.', 'minemirth' ),
			'###' . __( 'Keeping records', 'minemirth' ),
			__( 'Note the firmware version and the main settings of every miner. When something changes, you can see what was different and roll back quickly.', 'minemirth' ),
			__( 'Small, careful changes add up to real savings over a year of running.', 'minemirth' ),
			__( 'If you are unsure, ask us before you flash anything.', 'minemirth' ),
		)
	),

	'seo:g-maintenance'   => static fn() => $mm_a(
		__( 'Why ASIC maintenance pays for itself', 'minemirth' ),
		array(
			__( 'A miner that is offline earns nothing while its share of rent, hosting or depreciation keeps running. Simple ASIC maintenance prevents most downtime: clean intakes, healthy fans and tight connections keep chips cool and boards stable.', 'minemirth' ),
			'###' . __( 'Cleaning', 'minemirth' ),
			__( 'Dust builds up on intake grills and between heatsink fins, raising temperatures and fan speed. Power the miner off, let it cool, then blow dust out with dry compressed air from the exhaust side. In dusty sites, intake filters and a regular cleaning schedule make a large difference.', 'minemirth' ),
			'###' . __( 'Fans and connectors', 'minemirth' ),
			__( 'Listen for bearing noise and watch fan speed readings. Replace a fan at the first sign of trouble. Check power and data connectors for discolouration, which points to a loose or overloaded connection.', 'minemirth' ),
			'###' . __( 'Records', 'minemirth' ),
			__( 'Keep a simple log for each miner: serial number, firmware version, repairs and parts replaced. Good records turn ASIC maintenance from guesswork into a routine, and they help with warranty claims and resale.', 'minemirth' ),
			__( 'Keep spare fans, cords and a control board on hand; our <a href="{page:m-parts}">parts and accessories</a> page lists them.', 'minemirth' ),
			'###' . __( 'After a move or a long stop', 'minemirth' ),
			__( 'If a miner has been shipped, stored or switched off for weeks, inspect it before powering on: check for loose connectors, moisture and dust, and let a cold unit warm to room temperature to avoid condensation. Then watch it closely for the first hour.', 'minemirth' ),
			'###' . __( 'Seasonal checks', 'minemirth' ),
			__( 'Before summer, clean intakes and check that exhaust fans and ducts are clear. Before winter, check for condensation risk if intake air gets very cold and humid. A short seasonal round of ASIC maintenance prevents most temperature-related faults.', 'minemirth' ),
			'###' . __( 'Spares and tools', 'minemirth' ),
			__( 'A small kit makes routine work quick: compressed air, a soft brush, spare fans, a spare power cord, a network cable, a screwdriver set and an anti-static wrist strap. With these on the shelf, most maintenance takes minutes instead of days.', 'minemirth' ),
			__( 'Schedule the work for times when power is most expensive, so the downtime costs you as little as possible.', 'minemirth' ),
			'###' . __( 'Hosted miners', 'minemirth' ),
			__( 'If your miners are hosted, ask the facility what maintenance they perform, how often, and how they report it. A clear maintenance log from your host is a good sign of a well-run site, and it makes your own ASIC maintenance planning simpler.', 'minemirth' ),
			__( 'Ten minutes of care a month can add weeks of hashing a year.', 'minemirth' ),
		)
	),

	'seo:g-troubleshoot'  => static fn() => $mm_a(
		__( 'ASIC troubleshooting: when to stop and ask', 'minemirth' ),
		array(
			__( 'Some faults are safe to work on yourself: reseating cables, cleaning, replacing a fan, updating firmware or swapping a known-good power cord. Others are not: anything involving mains wiring inside the power supply, hashboard soldering or a burnt smell from the unit.', 'minemirth' ),
			__( 'Before opening a miner under warranty, check the manufacturer’s policy. Broken seals can end the cover. Record the error, the kernel log and what you tried; this ASIC troubleshooting record makes a warranty claim or a <a href="{page:repairs}">repair</a> much faster.', 'minemirth' ),
			__( 'If a fault keeps coming back after the basic checks, the cause is often the environment rather than the miner: unstable power, high intake temperature or dust. Fix that first, or the same ASIC troubleshooting will be needed again next month.', 'minemirth' ),
			'###' . __( 'Keep a known-good kit', 'minemirth' ),
			__( 'A spare power cord, a spare fan, a network cable and a laptop with the miner’s tools let you test one part at a time. Swapping in a known-good part is the quickest way to find the faulty one.', 'minemirth' ),
			'###' . __( 'Keep notes', 'minemirth' ),
			__( 'Write down every fault, what you checked and what fixed it. Over time, the notes show patterns, such as a particular board, a hot corner of the room or a weak circuit, and they make the next round of troubleshooting much faster.', 'minemirth' ),
			'###' . __( 'When everything fails', 'minemirth' ),
			__( 'If a miner still will not run after every check, restore the stock firmware and factory settings, test with a known-good power supply and cord, and try each hashboard on its own. If the fault remains, it is time for a professional repair or a warranty claim.', 'minemirth' ),
		)
	),

	'seo:g-farm'          => static fn() => $mm_a(
		__( 'Mining farm mistakes that cost the most', 'minemirth' ),
		array(
			__( '<strong>Signing for hardware before power.</strong> A mining farm without confirmed, contracted power is a warehouse of expensive boxes. Secure capacity, price and terms in writing first.', 'minemirth' ),
			__( '<strong>Undersizing electrical and cooling.</strong> Plan for the full load at the hottest time of year, plus growth. Retrofitting a transformer or a cooling system costs far more than building it right.', 'minemirth' ),
			__( '<strong>Ignoring curtailment and uptime.</strong> Many power deals include curtailment. Model how often you will switch off and what that does to revenue.', 'minemirth' ),
			__( '<strong>Thin financial margins.</strong> Test your mining farm model against lower prices, faster difficulty growth and the next halving. If it only works in the best case, scale down or wait. Our <a href="{page:consulting}">farm design service</a> can review a plan before you commit.', 'minemirth' ),
			__( 'Before committing, visit sites that already run at a similar scale and ask what they would do differently. Real operating experience is the best check on any mining farm plan.', 'minemirth' ),
			'###' . __( 'Start smaller than you think', 'minemirth' ),
			__( 'Many successful farms start with a pilot of a few racks to test power, cooling and operations, then scale in stages. A pilot reveals problems while they are still cheap to fix.', 'minemirth' ),
		)
	),

	'seo:g-hosting'       => static fn() => $mm_a(
		__( 'Red flags when choosing a hosting provider', 'minemirth' ),
		array(
			__( 'Be cautious if a hosting provider will not put the all-in rate in writing, cannot say where the facility is, or avoids questions about uptime, curtailment and repairs. Unusually low rates with large upfront deposits deserve extra checks.', 'minemirth' ),
			__( 'Ask for references, visit if you can, and start with a small number of miners before moving a whole fleet. Check that you keep control of your pool account and payout address, so the coins go straight to you.', 'minemirth' ),
			__( 'A good hosting provider is happy to answer every question on this page in writing. Compare offers with the <a href="{tool:hosting}">hosting vs home calculator</a>, then read the contract line by line before you sign.', 'minemirth' ),
			__( 'Finally, plan your exit. Ask how long it takes to collect your miners, what it costs and what condition they will be in. A clear exit clause makes the whole relationship easier.', 'minemirth' ),
			'###' . __( 'Questions worth asking on a visit', 'minemirth' ),
			__( 'If you can visit, look at how racks are labelled, how hot and cold air are separated, how quickly staff can reach a failed miner and how security is handled. A tidy, well-run floor is one of the best signs of a reliable host.', 'minemirth' ),
		)
	),

	'seo:g-used'          => static fn() => $mm_a(
		__( 'Pricing used ASIC miners fairly', 'minemirth' ),
		array(
			__( 'The fair price of used ASIC miners depends on what they can still earn. Start with the miner’s profit per day at your power price in the <a href="{tool:profit}">profitability calculator</a>, then estimate how many months it will stay above break-even with difficulty growth and the next halving.', 'minemirth' ),
			__( 'A simple rule many buyers use is to aim for payback well before the next halving, with a margin for repairs. Older, less efficient models lose value fastest, so a low price may still be too high if the miner will soon fall below break-even.', 'minemirth' ),
			'###' . __( 'Condition and history', 'minemirth' ),
			__( 'Ask where the miners ran and for how long. Units from clean, climate-controlled facilities usually age better than units from dusty or humid sites. Corrosion on heatsinks or connectors is a warning sign.', 'minemirth' ),
			'###' . __( 'Paying safely', 'minemirth' ),
			__( 'Use payment methods that give you some protection, test the units before final payment where possible, and get the serial numbers on your invoice. With these checks, used ASIC miners can be the best-value way to add hashrate.', 'minemirth' ),
			'###' . __( 'Shipping used miners', 'minemirth' ),
			__( 'Ask how the seller will pack the units. Used miners travel best in original or custom foam packaging, with power supplies secured and nothing loose inside. Insure the shipment for its full value.', 'minemirth' ),
			'###' . __( 'After arrival', 'minemirth' ),
			__( 'Inspect, clean and test each unit when it arrives, and run it for at least a day before you rely on it. Note any boards with low hashrate or high temperatures and raise them with the seller straight away. Following these steps makes buying used ASIC miners far less risky.', 'minemirth' ),
			'###' . __( 'Firmware on used miners', 'minemirth' ),
			__( 'Reset used miners to factory settings and install current firmware from the manufacturer before connecting them to your pool. This removes old pool settings and any unwanted software left by a previous owner.', 'minemirth' ),
			'###' . __( 'Questions to ask the seller', 'minemirth' ),
			__( 'Ask how long each unit ran, whether it was repaired, what firmware it uses and whether the seller will show it hashing on a video call before you pay. A seller who answers clearly is usually a seller you can trust. These questions make buying used ASIC miners much safer.', 'minemirth' ),
			__( 'Take your time: a good deal on a used miner is one you have checked properly.', 'minemirth' ),
			__( 'We are always happy to give a second opinion.', 'minemirth' ),
		)
	),

	'seo:g-security'      => static fn() => $mm_a(
		__( 'A simple mining security routine', 'minemirth' ),
		array(
			'###' . __( 'Monthly', 'minemirth' ),
			__( 'Check that every miner still points to your own pool and worker, and that the payout address on your pool account is the one you set. Unexpected changes are the clearest sign of a compromised miner or account.', 'minemirth' ),
			'###' . __( 'When something changes', 'minemirth' ),
			__( 'After a firmware update, a new staff member or a new hosting arrangement, change passwords and review who has access. Remove accounts that are no longer needed.', 'minemirth' ),
			'###' . __( 'For the coins', 'minemirth' ),
			__( 'Move earnings from exchange or pool balances to a wallet you control, and keep the recovery phrase offline in a safe place. For larger holdings, a hardware wallet and a written recovery plan are good mining security practice.', 'minemirth' ),
			'###' . __( 'For the site', 'minemirth' ),
			__( 'Lock up miners and network equipment, keep the miners on their own network segment, and never expose a miner’s web interface directly to the internet. Good mining security is mostly simple habits done consistently.', 'minemirth' ),
			'###' . __( 'Phishing and fake support', 'minemirth' ),
			__( 'Be wary of messages that claim to be from your pool, hosting provider or a manufacturer and ask you to log in through a link, install software or change payout details. Open the site yourself instead, and confirm unusual requests by phone or through the official support channel.', 'minemirth' ),
			'###' . __( 'Shared and hosted miners', 'minemirth' ),
			__( 'In hosting, ask who can access your miners and how changes are logged. Keep your own pool account credentials, and do not share them with anyone who does not need them. Clear rules about access are part of mining security for every hosted fleet.', 'minemirth' ),
			'###' . __( 'Insurance and records', 'minemirth' ),
			__( 'Keep serial numbers, invoices and photos of your equipment in a safe place, and check whether your insurance covers miners at home or in a hosting facility. Records help with insurance claims as well as with warranty and resale.', 'minemirth' ),
			'###' . __( 'If something goes wrong', 'minemirth' ),
			__( 'If you see an unknown pool or payout address, disconnect the miner from the network, reset it to factory settings, install official firmware and change every password involved. Then check your pool account and email for other signs of access, and turn on two-factor authentication everywhere it is offered.', 'minemirth' ),
			__( 'A few minutes of setup now prevents most of the problems miners run into later.', 'minemirth' ),
			__( 'Write your routine down, and review it every few months.', 'minemirth' ),
		)
	),

	'seo:g-tax'           => static fn() => $mm_a(
		__( 'Keeping mining taxes simple', 'minemirth' ),
		array(
			__( 'The easiest way to handle mining taxes is to keep good records from the first day. Export your pool payouts regularly with dates and amounts, and record the coin price at the time you received each payout. Many pools and portfolio tools can export this for you.', 'minemirth' ),
			__( 'Keep invoices for miners, parts, hosting and electricity, and note how much power the mining equipment used if it shares a meter with your home or business. Depending on where you live, some of these costs may reduce your taxable income.', 'minemirth' ),
			__( 'When you sell or spend mined coins, record the date, the amount and the value received. Mining taxes on disposals are usually calculated against the value when the coins were first received, so the earlier records matter.', 'minemirth' ),
			__( 'Rules differ widely between countries and change over time. Talk to a qualified tax adviser before your first filing, and bring your records. This guide gives general background only and is not tax advice.', 'minemirth' ),
			'###' . __( 'Business or hobby', 'minemirth' ),
			__( 'Where you mine at a larger scale, with a plan to make a profit, many tax systems treat it as a business. That can change which costs you may deduct, how equipment is depreciated and which forms you file. An adviser can tell you which treatment applies.', 'minemirth' ),
			'###' . __( 'Hosting and international setups', 'minemirth' ),
			__( 'If your miners run in another country, ask your adviser how that affects your filing. Keep the hosting contract and invoices with your other records, because they show where and how the income was earned. Good records make mining taxes simpler wherever you mine.', 'minemirth' ),
			'###' . __( 'Common record-keeping mistakes', 'minemirth' ),
			__( 'The most common mistakes are missing payout records, using the wrong date for the coin value and mixing personal and mining electricity without a way to separate them. A simple spreadsheet updated monthly avoids all three.', 'minemirth' ),
			__( 'Keep copies of your records for as long as your local rules require, often several years, and back them up somewhere safe.', 'minemirth' ),
			__( 'Good habits now save time and stress at every filing.', 'minemirth' ),
		)
	),

	'seo:g-glossary'      => static fn() => $mm_a(
		__( 'How to use this mining glossary', 'minemirth' ),
		array(
			__( 'Use the letters above to jump to a term. Where a definition depends on live numbers, such as hashprice or difficulty, the mining glossary shows today’s value so you can see what the term means right now.', 'minemirth' ),
			__( 'Missing a word? Tell us and we will add it. We keep this mining glossary in plain English on purpose, so it stays useful for beginners and quick to scan for experienced miners.', 'minemirth' ),
		)
	),
);
