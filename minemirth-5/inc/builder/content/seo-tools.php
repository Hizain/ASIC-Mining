<?php
/**
 * SEO sections for the tool pages and the tools and guides hubs (see seo-company.php).
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

$mm_a = static fn( string $h, array $p ) => mm_bseo( $h, $p, '', true );

return array(

	'seo:tools'           => static fn() => mm_bseo(
		__( 'Getting reliable answers from Bitcoin mining calculators', 'minemirth' ),
		array(
			__( 'Bitcoin mining calculators are only as good as their inputs. Ours fill in the live price, difficulty and fees for you, but three numbers are yours: your all-in electricity price per kWh (including fees and taxes on your bill), the miner’s real power draw at the wall, and a realistic uptime, usually 95 to 99%.', 'minemirth' ),
			__( 'Then look beyond today. A miner that earns well now will earn less as difficulty rises and after the next halving. Use the <a href="{tool:roi}">ROI planner</a> for scenarios, and the <a href="{tool:breakeven}">break-even calculator</a> to see how much room your power price leaves.', 'minemirth' ),
			__( 'Every result can be shared as a link with your exact inputs, so you can send a scenario to a partner or to us for a second opinion. Treat Bitcoin mining calculators as estimates, not promises: they show the effect of each assumption so you can decide with open eyes.', 'minemirth' ),
		)
	),

	'seo:learn'           => static fn() => mm_bseo(
		__( 'How to use these mining guides', 'minemirth' ),
		array(
			__( 'New to mining? Read the mining guides in this order: <a href="{page:g-beginners}">Bitcoin mining for beginners</a>, <a href="{page:g-profit}">mining profitability</a>, <a href="{page:g-hashprice}">what hashprice means</a> and <a href="{page:g-choose}">how to choose an ASIC miner</a>. Together they take under an hour and cover the decisions that matter most.', 'minemirth' ),
			__( 'Setting up? Go to the power, cooling and noise guides before the miner arrives, then the pool and firmware guides once it is running. Running a fleet or a farm? Start with farm planning, hosting and security.', 'minemirth' ),
			__( 'Each of our mining guides links to the calculators it mentions, filled with live numbers, so you can test every idea on your own setup straight away.', 'minemirth' ),
		)
	),

	'seo:tool-profit'     => static fn() => $mm_a(
		__( 'Using the mining profitability calculator well', 'minemirth' ),
		array(
			__( 'Enter your real electricity price, including delivery charges and taxes, not just the energy rate. A difference of one cent per kWh changes the yearly result of a full-size miner by hundreds of dollars, so it is worth checking a recent bill.', 'minemirth' ),
			__( 'Use the miner’s measured power at the wall if you have it. Nameplate figures are a good starting point, but tuning, temperature and power supply efficiency all change the real draw.', 'minemirth' ),
			__( 'The mining profitability calculator shows today’s profit and a 24-month outlook. Read the outlook as a range: change difficulty growth and the Bitcoin price to see a cautious and an optimistic case. If the miner only works in the optimistic case, it is a bet rather than a plan.', 'minemirth' ),
		)
	),

	'seo:tool-hashprice'  => static fn() => $mm_a(
		__( 'What to watch on the hashprice tracker', 'minemirth' ),
		array(
			__( 'The hashprice tracker shows today’s revenue per petahash and how it has moved. Watch the long trend more than single days: price rallies raise it, difficulty increases slowly pull it back, and every halving cuts it roughly in half overnight.', 'minemirth' ),
			__( 'Compare it with your break-even line, which depends on your miner’s efficiency and your power price. The wider the gap, the safer your margin. If the hashprice tracker shows the line getting close, it may be time to tune for efficiency, move to cheaper power or retire the least efficient units.', 'minemirth' ),
		)
	),

	'seo:tool-breakeven'  => static fn() => $mm_a(
		__( 'Lowering your break-even electricity price risk', 'minemirth' ),
		array(
			__( 'Your break-even electricity price falls whenever hashprice falls: after difficulty increases, price drops and every halving. Plan with a margin. Many miners want a break-even price at least 30 to 50% above what they pay today.', 'minemirth' ),
			__( 'You can raise your break-even electricity price by improving efficiency: tune the miner for lower joules per terahash, keep intake air cool so fans use less power, and replace the least efficient units first. You can also lower what you pay, through a better tariff, off-peak running or <a href="{page:hosting}">hosting</a> on industrial power.', 'minemirth' ),
			__( 'Check the result again after each difficulty adjustment, because the number moves with the network.', 'minemirth' ),
		)
	),

	'seo:tool-compare'    => static fn() => $mm_a(
		__( 'Making an ASIC miner comparison you can trust', 'minemirth' ),
		array(
			__( 'Compare like with like. An ASIC miner comparison is only fair within one algorithm and one cooling type, because hydro and immersion units need extra equipment that air-cooled ones do not.', 'minemirth' ),
			__( 'Look at three columns together: profit per day at your power price, efficiency in J/TH and price per TH. A miner that wins on all three is rare. On cheap power, price per TH usually decides; on expensive power, efficiency does.', 'minemirth' ),
			__( 'Finally, check the practical things the table cannot show: noise, size, power and cooling needs, warranty and lead time. Use this ASIC miner comparison to make a shortlist of two or three models, then ask us for availability.', 'minemirth' ),
			__( 'The ranking updates with the live price and difficulty, so a miner that tops the list today may move a place after the next adjustment. That is normal; the shortlist usually stays the same.', 'minemirth' ),
			__( 'Prices in the table are the current store prices.', 'minemirth' ),
		)
	),

	'seo:tool-roi'        => static fn() => $mm_a(
		__( 'What drives mining ROI', 'minemirth' ),
		array(
			__( 'Mining ROI depends on four things: what you pay for the miner, what you pay for power, how fast difficulty grows and where the Bitcoin price goes. The first two you control; the last two you can only plan for with scenarios.', 'minemirth' ),
			__( 'Buying at a good price per terahash shortens payback the most. Cheap, reliable power keeps a miner earning for longer. And because the next halving cuts the subsidy in half, a payback that only completes after the halving carries much more risk.', 'minemirth' ),
			__( 'Use the bear case as your test. If the mining ROI still works in the cautious scenario, the purchase is robust; if it only works in the bull case, consider a more efficient model, cheaper power or a smaller first order.', 'minemirth' ),
		)
	),

	'seo:tool-halving'    => static fn() => $mm_a(
		__( 'Using the Bitcoin halving countdown to plan', 'minemirth' ),
		array(
			__( 'The Bitcoin halving countdown estimates the date from the remaining blocks and the recent block time, so it moves a little from day to day. Use it to set deadlines: when your current miners should have paid back, and when an upgrade needs to be running.', 'minemirth' ),
			__( 'After a halving, less efficient miners can drop below break-even overnight. Check what your fleet earns at half the subsidy, then compare upgrade options with the <a href="{tool:upgrade}">upgrade calculator</a> well before the Bitcoin halving countdown reaches zero.', 'minemirth' ),
		)
	),

	'seo:tool-difficulty' => static fn() => $mm_a(
		__( 'Planning around each difficulty adjustment', 'minemirth' ),
		array(
			__( 'A single difficulty adjustment rarely changes your plans, but the trend does. Over months, rising difficulty lowers the bitcoin your miner earns per day, which is why projections include a monthly growth rate.', 'minemirth' ),
			__( 'Use the estimate for short-term decisions. If a large increase is coming and your margin is thin, it may be a good moment to tune for efficiency. If a decrease is expected, revenue per terahash will rise for the next two weeks.', 'minemirth' ),
			__( 'For long-term plans, look at the history in the <a href="{tool:hashprice}">hashprice tracker</a> and model a range of growth rates in the <a href="{tool:roi}">ROI planner</a> rather than relying on the next difficulty adjustment alone.', 'minemirth' ),
			__( 'Remember that estimates early in an epoch are noisy, and they settle as more blocks arrive.', 'minemirth' ),
		)
	),

	'seo:tool-hosting'    => static fn() => $mm_a(
		__( 'Hosting vs home mining: costs people forget', 'minemirth' ),
		array(
			__( 'At home, the hidden costs are electrical work, ventilation, the extra power for fans or air conditioning, and the time you spend fixing problems. Noise can also be a real cost if it affects family or neighbours.', 'minemirth' ),
			__( 'In hosting, read the rate carefully: deposits, setup fees, minimum terms, how curtailment is handled and what repairs cost. Shipping the miners to the facility and back is another line to include.', 'minemirth' ),
			__( 'Enter all of them in the calculator above. A fair hosting vs home mining comparison often shows that home wins on cheap power with one or two units, while hosting wins as soon as power is expensive or the fleet grows.', 'minemirth' ),
			__( 'Run it again whenever your power price or a hosting offer changes; the answer can flip with a few cents per kWh.', 'minemirth' ),
			__( 'Hosting rates from our partners are listed on the hosting page.', 'minemirth' ),
		)
	),

	'seo:tool-power'      => static fn() => $mm_a(
		__( 'Getting the most from the mining power calculator', 'minemirth' ),
		array(
			__( 'Use the real power draw of your miners, measured at the wall if possible, and the voltage you actually have. The mining power calculator then shows the current per miner, how many miners fit on each breaker under the 80% rule, the total heat output and the airflow needed to remove it.', 'minemirth' ),
			__( 'Take the results to a licensed electrician. They can check your service capacity, choose cable sizes and breakers to your local code, and confirm whether single-phase or three-phase supply suits the load. The mining power calculator helps you ask the right questions; it does not replace a qualified installation.', 'minemirth' ),
			__( 'Plan a little headroom for growth too: adding miners later is easier when the circuits and cooling were sized for it from the start.', 'minemirth' ),
		)
	),

	'seo:tool-upgrade'    => static fn() => $mm_a(
		__( 'When the miner upgrade calculator says upgrade', 'minemirth' ),
		array(
			__( 'An upgrade usually makes sense when your site is limited by power and the new model produces far more hashrate per watt. On the same power budget, the extra revenue pays for the new miners, and the old ones can be sold or traded in.', 'minemirth' ),
			__( 'Include everything in the miner upgrade calculator: the new miner price, shipping, any electrical changes, and the resale or <a href="{page:tradein}">trade-in</a> value of the old units. Then check the payback against the next halving. An upgrade that pays back before it is often the safest move a miner can make.', 'minemirth' ),
			__( 'If your power is not the limit, compare unit for unit instead: keep the old miners running while they stay above break-even, and add new ones alongside them.', 'minemirth' ),
			__( 'Either way, the miner upgrade calculator shows the payback of the change itself, so you can judge it like any other investment.', 'minemirth' ),
			__( 'Old miners can also be traded in.', 'minemirth' ),
		)
	),

	'seo:tool-solo'       => static fn() => $mm_a(
		__( 'Making sense of solo mining odds', 'minemirth' ),
		array(
			__( 'Solo mining odds are the same every block: past luck does not change the next try. A small miner may run for years without a block, or find one in its first week. The calculator shows the average time, not a schedule.', 'minemirth' ),
			__( 'Compare the expected value with pool mining. In a pool, the same hashrate earns a small, steady income. Solo, the long-run average is similar before fees, but it arrives as a rare, large reward. Many people run a small solo miner for the chance and the learning, and keep their main hashrate in a pool.', 'minemirth' ),
			__( 'To join in, point the miner at a solo pool with your own Bitcoin address. If you are lucky, the full reward arrives at that address, and the odds update live in this calculator as difficulty changes.', 'minemirth' ),
			__( 'Even tiny devices help keep mining spread across many hands.', 'minemirth' ),
		)
	),

	'seo:tool-altcoin'    => static fn() => $mm_a(
		__( 'Getting realistic numbers from an altcoin mining calculator', 'minemirth' ),
		array(
			__( 'Altcoin prices and network hashrate move faster than Bitcoin’s, so today’s result can change a lot within weeks. Run the altcoin mining calculator at today’s prices, then again at 30% lower prices, and see whether the miner still pays.', 'minemirth' ),
			__( 'Remember the reward schedules. Kaspa’s block reward falls every month, and Litecoin halves about every four years like Bitcoin. Merged mining helps Scrypt miners, because Dogecoin rewards add to Litecoin rewards from the same hashrate.', 'minemirth' ),
			__( 'Check pool fees and minimum payouts for each coin, and the exchange or wallet costs if you plan to convert. An altcoin mining calculator shows gross earnings; your own costs decide what you keep.', 'minemirth' ),
			__( 'For Scrypt miners, set up both a Litecoin and a Dogecoin wallet before you start, so pool payouts have somewhere to go.', 'minemirth' ),
			__( 'Keep a note of the results so you can compare month by month.', 'minemirth' ),
		)
	),
);
