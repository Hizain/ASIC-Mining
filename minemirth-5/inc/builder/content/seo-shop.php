<?php
/**
 * SEO sections for the miner collections and brand pages (see seo-company.php).
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

return array(

	'seo:miners'          => static fn() => mm_bseo(
		__( 'Comparing ASIC miners for sale: the short version', 'minemirth' ),
		array(
			__( 'When you compare ASIC miners for sale, start with the coin, because each miner runs one algorithm: SHA-256 for Bitcoin, Scrypt for Litecoin and Dogecoin, kHeavyHash for Kaspa. Then match the cooling to your site: air-cooled units need strong airflow, while hydro and immersion miners need a liquid system.', 'minemirth' ),
			__( 'Within a coin, efficiency and price per terahash decide almost everything. Efficient models cost more up front but survive rising difficulty and the next halving; cheaper models recover their price faster on very cheap power. The <a href="{tool:compare}">miner comparison</a> ranks all our ASIC miners for sale on both at your electricity price.', 'minemirth' ),
			__( 'If noise or power at home is a concern, look at <a href="{page:m-home}">quiet home miners</a> or plan for <a href="{page:hosting}">hosting</a> from the start.', 'minemirth' ),
		)
	),

	'seo:m-scrypt'        => static fn() => mm_bseo(
		__( 'Choosing Scrypt miners for Litecoin and Dogecoin', 'minemirth' ),
		array(
			__( 'Scrypt miners are judged like Bitcoin miners, by efficiency and price per unit of hashrate, but the units differ: Scrypt hashrate is measured in GH/s and efficiency in joules per megahash. Full-size models draw about 3.3 to 3.5 kW and need the same 200 to 240 V circuits as Bitcoin ASICs.', 'minemirth' ),
			__( 'Because Litecoin and Dogecoin are merge-mined, your earnings depend on two coin prices. Use the <a href="{tool:altcoin}">altcoin calculator</a> to see what a miner earns in LTC and DOGE together at today’s prices and your power cost.', 'minemirth' ),
			__( 'Pick a pool that pays both coins automatically, and check its fees and minimum payouts for each. Most large Scrypt pools support merged mining out of the box.', 'minemirth' ),
		)
	),

	'seo:m-kaspa'         => static fn() => mm_bseo(
		__( 'What to check before buying Kaspa miners', 'minemirth' ),
		array(
			__( 'The Kaspa block reward shrinks a little every month, so revenue per terahash falls steadily even when the price and network hashrate stay flat. Size your payback for that decline: efficient, current Kaspa miners hold up best.', 'minemirth' ),
			__( 'Compare models by efficiency in joules per terahash and by price per TH, and check the power needs: compact units run on a normal outlet, while full-size machines need a dedicated circuit and strong airflow. The <a href="{tool:altcoin}">altcoin calculator</a> shows live KAS earnings for any hashrate and power draw.', 'minemirth' ),
		)
	),

	'seo:m-alephium'      => static fn() => mm_bseo(
		__( 'How to size Alephium miners for your setup', 'minemirth' ),
		array(
			__( 'Alephium miners range from small, quiet boxes that draw a few hundred watts to full-size machines that need a dedicated circuit. For a home or office, a compact unit is easier to run and to resell; for a hosted or industrial site, larger units usually cost less per unit of hashrate.', 'minemirth' ),
			__( 'Alephium is a smaller network than Bitcoin, so prices and network hashrate can move quickly. Plan conservatively: model several price scenarios, keep your payback target short and buy only what you can run profitably if the price falls.', 'minemirth' ),
			__( 'Pools for Alephium are fewer than for Bitcoin. Check fees, minimum payouts and server locations before pointing your miners at one.', 'minemirth' ),
			__( 'As with any miner, check the power draw and noise rating against where it will run, and keep the original packaging: smaller networks mean a smaller resale market, and good packaging protects your resale value.', 'minemirth' ),
			__( 'Ask us about lead times and current batches before ordering, since supply for smaller networks changes often.', 'minemirth' ),
			__( 'We can also suggest pools and wallets for ALPH.', 'minemirth' ),
		)
	),

	'seo:m-home'          => static fn() => mm_bseo(
		__( 'What to expect from quiet home miners', 'minemirth' ),
		array(
			__( 'Quiet home miners trade raw hashrate for comfort. They draw tens to a few hundred watts instead of several kilowatts, so they earn far less than a full-size ASIC, but they run on a normal outlet and sit comfortably in a living space.', 'minemirth' ),
			__( 'Their heat is useful: in winter, a home miner warms the room it sits in, so part of its electricity cost would have been spent on heating anyway. That is why the economics of a home unit look better in a cold month than on paper.', 'minemirth' ),
			__( 'Check the noise rating, the power draw and the coin before you buy. Read our guide to <a href="{page:g-home}">mining at home</a> for wiring, heat and noise tips.', 'minemirth' ),
		)
	),

	'seo:m-solo'          => static fn() => mm_bseo(
		__( 'Getting started with solo miners', 'minemirth' ),
		array(
			__( 'Solo miners connect to a solo pool with your own Bitcoin address as the username. If the device ever finds a block, the full reward goes to that address. Until then, it simply keeps trying, using about as much power as a light bulb.', 'minemirth' ),
			__( 'Set realistic expectations: the odds per day are tiny. Check them with the <a href="{tool:solo}">solo odds calculator</a>, and enjoy the device as a way to learn how mining works and to support the network.', 'minemirth' ),
		)
	),

	'seo:m-hydro'         => static fn() => mm_bseo(
		__( 'Before you buy hydro miners', 'minemirth' ),
		array(
			__( 'Water-cooled units need a cooling loop: pumps, a heat exchanger or dry cooler, the right coolant and flow rate, and filtration. Check the manufacturer’s specification for inlet temperature, flow and water quality before you order.', 'minemirth' ),
			__( 'The payoff is density and silence: a rack of liquid-cooled ASICs fits far more hashrate into the same space than air-cooled ones, and the warm water can heat a building or a greenhouse. Our <a href="{page:g-hydro}">hydro setup guide</a> walks through the whole system.', 'minemirth' ),
			__( 'Many sites run a mix: air-cooled units where airflow is easy, and liquid-cooled racks where space, noise or climate make air cooling hard. Ask us which models fit your loop.', 'minemirth' ),
		)
	),

	'seo:m-immersion'     => static fn() => mm_bseo(
		__( 'Choosing immersion miners', 'minemirth' ),
		array(
			__( 'Some immersion miners are built for fluid from the factory; others are air-cooled models prepared for the tank by removing the fans and adjusting the firmware. Factory immersion versions are simpler and keep the warranty intact, while converted units can be cheaper.', 'minemirth' ),
			__( 'Immersion lets a miner run cooler and more steadily, so many operators raise the power target for extra hashrate. Do it gradually, watching chip temperatures and efficiency, and only with a cooling system sized for the extra heat.', 'minemirth' ),
			__( 'Plan the whole system before buying immersion miners: tanks, dielectric fluid, pumps, a heat exchanger and dry cooler, and space for maintenance. Our <a href="{page:g-immersion}">immersion cooling guide</a> covers each part.', 'minemirth' ),
			__( 'Fluid choice matters as well. Single-phase dielectric fluids are the common choice for mining; check compatibility with the miner’s cables and components and follow the fluid supplier’s handling guidance.', 'minemirth' ),
			__( 'Ask us which models are available in immersion versions and what lead times look like.', 'minemirth' ),
		)
	),

	'seo:m-efficient'     => static fn() => mm_bseo(
		__( 'Why the most efficient Bitcoin miners last the longest', 'minemirth' ),
		array(
			__( 'Every difficulty increase and every halving lowers what a terahash earns. A miner’s power cost per terahash stays the same, so the less power it needs per terahash, the longer it stays above break-even. That is why the most efficient Bitcoin miners usually have the longest useful life.', 'minemirth' ),
			__( 'Efficiency also protects you if your power price rises. Check the break-even power price of each model in the <a href="{tool:breakeven}">break-even calculator</a>: the higher it is above your rate, the larger your safety margin.', 'minemirth' ),
			__( 'The trade-off is price. The most efficient Bitcoin miners cost more per terahash, so on very cheap power a cheaper, less efficient model can pay back faster. Compare both in the <a href="{tool:compare}">miner comparison</a>.', 'minemirth' ),
		)
	),

	'seo:m-value'         => static fn() => mm_bseo(
		__( 'When best value ASIC miners beat efficient ones', 'minemirth' ),
		array(
			__( 'On cheap power, electricity is a small share of your costs, so the price you pay for each terahash matters most. That is where best value ASIC miners shine: they put the most hashrate in your hands for your budget and can recover their cost quickly.', 'minemirth' ),
			__( 'On expensive power, the picture flips: a cheaper but less efficient miner may never pay back. Check both numbers in the <a href="{tool:roi}">ROI planner</a> with bear, base and bull scenarios before you decide.', 'minemirth' ),
			__( 'Older or refurbished models often appear in this list. They can be excellent best value ASIC miners if they are tested, come with a clear warranty or return policy, and still sit comfortably above break-even at your rate.', 'minemirth' ),
		)
	),

	'seo:m-new'           => static fn() => mm_bseo(
		__( 'Pre-ordering new ASIC miners safely', 'minemirth' ),
		array(
			__( 'New ASIC miners are often sold in batches months before they ship. Before you reserve, read the terms: the exact model and hashrate, the shipping window, the deposit and when the balance is due, and what happens if the batch is delayed.', 'minemirth' ),
			__( 'Model the payback from the expected delivery date, not from today. Difficulty usually rises in the months before a batch arrives, so a miner that looks excellent today will earn a little less on arrival. The <a href="{tool:roi}">ROI planner</a> lets you start the projection later.', 'minemirth' ),
			__( 'We confirm every batch in writing and tell you as soon as a shipping date changes. If you would rather not wait, ask which new ASIC miners are in stock now.', 'minemirth' ),
		)
	),

	'seo:m-parts'         => static fn() => mm_bseo(
		__( 'Which ASIC miner parts to keep in stock', 'minemirth' ),
		array(
			__( 'Fans fail first, so spare fans are the most useful ASIC miner parts to keep on the shelf. A failed fan stops a miner within minutes, and a replacement takes five minutes to fit. Keep at least one spare per few miners, matched to the model.', 'minemirth' ),
			__( 'Power cords and connectors come next. A cord rated for the miner’s current, with the right plug and inlet, prevents the most common cause of melted connectors. For larger setups, a spare power supply and a spare control board let you swap a failed part and keep hashing while the old one is repaired.', 'minemirth' ),
			__( 'When ordering ASIC miner parts, check compatibility by exact model and revision. Parts that look identical can differ in connectors, firmware or power rating. Ask us if you are unsure, and see our <a href="{page:g-maintenance}">maintenance checklist</a> for what to inspect each month.', 'minemirth' ),
			__( 'We stock parts for the most common Antminer, Whatsminer and Avalon models and can source others on request. Tell us the model and revision printed on the label, and we will confirm compatibility before you order.', 'minemirth' ),
			__( 'Fast shipping on small parts gets a miner back online quickly, which often matters more than the part price.', 'minemirth' ),
		)
	),

	'seo:brands'          => static fn() => mm_bseo(
		__( 'How to compare ASIC miner brands fairly', 'minemirth' ),
		array(
			__( 'Brand matters less than the model and the batch. Compare ASIC miner brands on the specific machines you would buy: efficiency, price per terahash, cooling options, firmware, warranty terms and how easy it is to get parts and repairs where you live.', 'minemirth' ),
			__( 'Fleet operators also look at reliability over years, the quality of management software and how a maker handles warranty claims. Home miners care more about noise, size and power draw. Our <a href="{tool:compare}">miner comparison</a> lets you rank models across ASIC miner brands at your power price.', 'minemirth' ),
			__( 'We sell several brands and have no reason to push one. Tell us your site and goals, and we will suggest the models that fit.', 'minemirth' ),
			__( 'Availability also varies by region. A model that is easy to buy and repair in one country may be scarce in another, so check lead times and local repair options before committing to one brand for a fleet.', 'minemirth' ),
			__( 'If you already run one brand, sticking with it can simplify spare parts and management, but it is worth checking whether another maker offers better efficiency for your next batch.', 'minemirth' ),
		)
	),

	'seo:brand-bitmain'   => static fn() => mm_bseo(
		__( 'Choosing a Bitmain Antminer model', 'minemirth' ),
		array(
			__( 'Start with the coin: the S series mines Bitcoin, the L series Litecoin and Dogecoin, the KS series Kaspa and the AL series Alephium. Within a series, the newest generation is usually the most efficient, while the previous one can offer better value per terahash.', 'minemirth' ),
			__( 'Then pick the cooling. Air-cooled Antminers need strong airflow; Hydro versions need a liquid loop; some models are offered for immersion. Every Bitmain Antminer in our stock can be opened in the <a href="{tool:profit}">profitability calculator</a> at your power price.', 'minemirth' ),
		)
	),

	'seo:brand-microbt'   => static fn() => mm_bseo(
		__( 'Running a MicroBT Whatsminer fleet', 'minemirth' ),
		array(
			__( 'Operators choose MicroBT Whatsminer units for steady performance in hot, dusty and demanding sites. Their power supplies are built in, which keeps installation simple, and management tools make it practical to monitor and update many units at once.', 'minemirth' ),
			__( 'As with any miner, efficiency decides how long a model stays profitable. Compare a MicroBT Whatsminer with other brands in the <a href="{tool:compare}">miner comparison</a>, and plan cooling and spare parts before a fleet arrives.', 'minemirth' ),
			__( 'Hydro and immersion versions suit dense sites and warm climates, where air cooling struggles. Ask us which variants are available and what each needs from your site.', 'minemirth' ),
			__( 'Spare parts and repairs are easiest to arrange for popular models. Keep a few fans and a spare control board for every group of units, and log serial numbers so warranty claims go smoothly.', 'minemirth' ),
		)
	),

	'seo:brand-canaan'    => static fn() => mm_bseo(
		__( 'Canaan Avalon for home and industrial mining', 'minemirth' ),
		array(
			__( 'Canaan Avalon covers two very different buyers. Its industrial Bitcoin miners are built for farms and hosting sites, judged on efficiency and price per terahash like any other full-size ASIC.', 'minemirth' ),
			__( 'Its home units are designed around comfort: quiet operation, a normal outlet and heat that warms a room. They earn modest amounts, but they are one of the easiest ways to run a real Bitcoin miner at home.', 'minemirth' ),
			__( 'Whichever Canaan Avalon you consider, check the power draw and noise rating, then model it in the <a href="{tool:profit}">profitability calculator</a> with your own electricity price.', 'minemirth' ),
			__( 'Canaan Avalon units also have a long history in the market, which helps with spare parts, firmware support and resale.', 'minemirth' ),
			__( 'Ask us which current models are in stock and how they compare with other brands at your power price.', 'minemirth' ),
		)
	),

	'seo:brand-goldshell' => static fn() => mm_bseo(
		__( 'Are Goldshell miners right for you?', 'minemirth' ),
		array(
			__( 'Goldshell miners suit people who want to mine altcoins without a server room. Most models are small, draw a few hundred watts and run quietly enough for an office or a spare room.', 'minemirth' ),
			__( 'The trade-off is that altcoin networks are smaller and their prices more volatile, so earnings can change quickly. Check the coin each model mines, then model its earnings in the <a href="{tool:altcoin}">altcoin calculator</a> with several price scenarios.', 'minemirth' ),
			__( 'For a first miner, Goldshell miners are a gentle start: easy to set up, easy to move and easy to resell if you decide to upgrade.', 'minemirth' ),
			__( 'Before buying, check that the coin your model mines still has an active network, good pools and a liquid market. Our team can help you compare options if you are unsure which coin to choose.', 'minemirth' ),
			__( 'Keep firmware current and the box in a ventilated spot: even small miners need airflow to stay at their rated performance.', 'minemirth' ),
			__( 'Ask us which models are in stock and what they mine today.', 'minemirth' ),
		)
	),

	'seo:brand-iceriver'  => static fn() => mm_bseo(
		__( 'Picking IceRiver Kaspa miners by size', 'minemirth' ),
		array(
			__( 'IceRiver Kaspa miners come in sizes for every setup. The smallest units run on a normal outlet with little noise, which suits a home or office. Mid-size models need more power and airflow, and the full-size machines need a dedicated circuit like any industrial ASIC.', 'minemirth' ),
			__( 'Because the Kaspa reward shrinks every month, newer and more efficient IceRiver Kaspa miners hold their earnings best. Compare efficiency and price per terahash, then check live KAS earnings in the <a href="{tool:altcoin}">altcoin calculator</a>.', 'minemirth' ),
			__( 'Kaspa’s network hashrate can grow quickly when new hardware ships, so model a few growth scenarios and keep your payback target short.', 'minemirth' ),
			__( 'For home units, check the noise rating and power draw, and give the miner a ventilated spot. For full-size models, plan a dedicated 200 to 240 V circuit and a way to move the hot exhaust out of the room.', 'minemirth' ),
			__( 'Ask us which IceRiver models are in stock and when the next batches are expected; availability changes often with new releases.', 'minemirth' ),
			__( 'We can also help you choose a Kaspa pool and wallet.', 'minemirth' ),
		)
	),

	'seo:brand-elphapex'  => static fn() => mm_bseo(
		__( 'What to know about Elphapex miners', 'minemirth' ),
		array(
			__( 'Elphapex miners mine the Scrypt algorithm, so one machine earns Litecoin and Dogecoin together through merged mining. Pools that support both coins pay you in each automatically.', 'minemirth' ),
			__( 'Compare Elphapex miners with other Scrypt models on efficiency (joules per megahash) and price per unit of hashrate, and check the power and cooling they need. Full-size units need a dedicated 200 to 240 V circuit and strong airflow; home versions are smaller and quieter.', 'minemirth' ),
			__( 'Model earnings with the <a href="{tool:altcoin}">altcoin calculator</a>, which uses live LTC and DOGE prices and network hashrate.', 'minemirth' ),
			__( 'Because earnings depend on two coin prices, run your numbers at lower LTC and DOGE prices as well as today’s, and keep a margin for difficulty growth.', 'minemirth' ),
			__( 'Pick a pool that supports merged mining, check its fee and minimum payouts for both coins, and set up wallets for LTC and DOGE before your miner arrives.', 'minemirth' ),
			__( 'Ask us about current stock and lead times, and how the DG models compare with other Scrypt miners at your power price.', 'minemirth' ),
			__( 'We can also help you choose a merged-mining pool.', 'minemirth' ),
			__( 'Questions about a specific model? Just ask.', 'minemirth' ),
		)
	),
);
