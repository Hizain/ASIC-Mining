<?php
/**
 * SEO sections for the company and service pages: one heading with the page's focus keyword and
 * a few paragraphs of practical text. The builder places each one before the page's questions or
 * its closing call to action (see mm_builder_content()).
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

return array(

	'seo:home'       => static fn() => mm_bseo(
		__( 'Buying ASIC miners with the numbers in view', 'minemirth' ),
		array(
			__( 'ASIC miners are built for one job: running a single hashing algorithm as efficiently as possible. That makes the choice simpler than it looks. Start with your electricity price, then compare efficiency (joules per terahash) and price per terahash, and only then look at brand, cooling and noise. Our <a href="{tool:compare}">miner comparison</a> does this for every model in stock at your power price.', 'minemirth' ),
			__( 'Every listing shows the specs that decide profit, and every miner can be opened in the <a href="{tool:profit}">profitability calculator</a> with today’s Bitcoin price, difficulty and fees already filled in. If you would rather not run ASIC miners at home, we can ship them straight to a <a href="{page:hosting}">hosting facility</a> and set them up on your own pool account.', 'minemirth' ),
			__( 'Not sure where to begin? Read <a href="{page:g-beginners}">Bitcoin mining for beginners</a>, then <a href="{page:g-choose}">how to choose an ASIC miner</a>. Both take about ten minutes and use live numbers, so what you learn applies to the ASIC miners you can buy today.', 'minemirth' ),
		),
		__( 'Start here', 'minemirth' )
	),

	'seo:about'      => static fn() => mm_bseo(
		__( 'What to expect from your ASIC miner supplier', 'minemirth' ),
		array(
			__( 'A good ASIC miner supplier does more than ship a box. Before you pay, you should know the exact model, its hashrate and power draw, the total price including shipping, the expected delivery window and who handles the warranty. We put all of that in writing, and we tell you plainly when a miner does not suit your power price.', 'minemirth' ),
			__( 'After delivery, the same team helps you get hashing: network and pool setup, firmware updates, tuning and troubleshooting. If you have no space, cheap power or patience for noise, we can host the miners in a partner facility instead, at an all-in rate that is published on every plan.', 'minemirth' ),
			__( 'We also build the free tools on this site because we use them ourselves. They run on the live Bitcoin price, difficulty and fees, so anyone can check our recommendations. Choosing an ASIC miner supplier should feel like hiring a careful engineer, not like a gamble.', 'minemirth' ),
		)
	),

	'seo:contact'    => static fn() => mm_bseo(
		__( 'How to get an accurate ASIC miner quote', 'minemirth' ),
		array(
			__( 'The more we know, the more precise your ASIC miner quote can be. Tell us the model or the coin you want to mine, the quantity, where the miners will run (your address or a hosting facility) and your electricity price if you would like a payback estimate.', 'minemirth' ),
			__( 'A quote from us lists the unit price, shipping and insurance, the expected delivery window, warranty terms and the payment methods available for your order. For deliveries across borders we explain the paperwork and the import duties you should expect, so the total has no surprises.', 'minemirth' ),
			__( 'Miner prices move with the Bitcoin market and with stock levels, so every ASIC miner quote shows how long it is valid. Nothing is charged until you accept it, and for large orders we confirm serial numbers and batch details before payment.', 'minemirth' ),
			__( 'Already own a miner and need help? Send the model, firmware version, a photo of the error or the kernel log, and what you have tried. That usually lets us answer in one reply instead of three.', 'minemirth' ),
			__( 'For support on a miner you own, the fastest route is the form above with photos and the model name. For orders, a quote request with quantity and destination gets you a written answer you can compare.', 'minemirth' ),
		)
	),

	'seo:why'        => static fn() => mm_bseo(
		__( 'Before you buy ASIC miners anywhere: a quick checklist', 'minemirth' ),
		array(
			__( 'Whether you buy ASIC miners from us or from someone else, a few checks protect you. Make sure the seller names the exact model and hashrate variant, because two miners with the same name can differ by tens of terahashes. Ask whether units are new, refurbished or used, and who provides the warranty.', 'minemirth' ),
			__( 'Check the total price: unit cost, shipping, insurance, import duties and any setup fees. Compare it with what the miner earns at your electricity price in the <a href="{tool:profit}">profitability calculator</a>, and look at the payback in the <a href="{tool:roi}">ROI planner</a>, which includes difficulty growth and the next halving.', 'minemirth' ),
			__( 'Finally, be wary of prices far below the market, pressure to pay quickly by irreversible methods, and sellers who cannot show the unit or a serial number. When you buy ASIC miners, a careful seller welcomes questions.', 'minemirth' ),
		)
	),

	'seo:faq'        => static fn() => mm_bseo(
		__( 'Three questions to answer before buying ASIC miners', 'minemirth' ),
		array(
			__( '<strong>What does your electricity cost?</strong> Power is the biggest running cost of ASIC miners, so your price per kWh decides which models make sense. The <a href="{tool:breakeven}">break-even calculator</a> shows the highest rate each miner can pay.', 'minemirth' ),
			__( '<strong>Where will the miner run?</strong> Full-size ASIC miners need a 200 to 240 V circuit, strong airflow and a place where 70 to 80 dB of noise is acceptable. If home is not right, compare it with <a href="{page:hosting}">hosting</a>.', 'minemirth' ),
			__( '<strong>How long do you plan to run it?</strong> Difficulty usually rises over time and the next halving cuts the block subsidy in half. Efficient ASIC miners stay profitable longest, so plan with scenarios rather than today’s numbers alone.', 'minemirth' ),
		)
	),

	'seo:reviews'    => static fn() => mm_bseo(
		__( 'How Mine Mirth reviews are collected and published', 'minemirth' ),
		array(
			__( 'Mine Mirth reviews are left by customers on the product page of the miner or part they bought. Reviews from confirmed orders are marked as verified purchases, so you can tell them apart from general comments.', 'minemirth' ),
			__( 'We publish reviews as written, including critical ones. We only remove spam, abuse or personal details such as phone numbers and addresses. When a review describes a problem, we reply publicly with what we did about it, because how a seller handles issues tells you more than a row of five stars.', 'minemirth' ),
			__( 'When you read Mine Mirth reviews, or reviews of any seller, look for detail: the exact model, how the unit arrived, how long it has been running and how support responded. Recent, specific reviews are worth more than old or vague ones.', 'minemirth' ),
			__( 'Bought from us? Leave a review on the product page of the miner you bought. It takes a minute and helps other miners choose with confidence.', 'minemirth' ),
			__( 'We do not offer discounts or gifts in exchange for reviews, and we do not hide reviews about delivery times or faults. If something went wrong with your order, contact us first so we can fix it; you are still free to describe your experience in a review afterwards.', 'minemirth' ),
			__( 'Product reviews also help us. They show which miners run well in which conditions, which questions buyers ask most and where our product pages need clearer information.', 'minemirth' ),
			__( 'Reviews about parts and accessories count too. A short note on whether a power cord or fan fitted and worked as described saves the next buyer a question.', 'minemirth' ),
		)
	),

	'seo:payments'   => static fn() => mm_bseo(
		__( 'Choosing between payment methods', 'minemirth' ),
		array(
			__( '<strong>Card</strong> is the quickest of the payment methods for smaller orders. Your bank may apply limits to large purchases, so for a fleet a bank transfer is usually simpler.', 'minemirth' ),
			__( '<strong>Bank transfer</strong> suits larger orders and business purchases, with a full invoice for your records. International transfers can take one to three business days; we confirm by email as soon as the payment arrives.', 'minemirth' ),
			__( '<strong>Cryptocurrency</strong> payments are settled on-chain. Send the exact amount shown at checkout to the address shown at checkout, within the time window given, and include enough network fee for a timely confirmation.', 'minemirth' ),
			__( 'Whichever of the payment methods you use, only follow the instructions shown at checkout or on your invoice. We never change bank details or payment addresses by email or chat. If a message asks you to pay somewhere new, stop and contact us through this website first.', 'minemirth' ),
		)
	),

	'seo:shipping'   => static fn() => mm_bseo(
		__( 'What affects ASIC miner shipping cost and time', 'minemirth' ),
		array(
			__( 'ASIC miner shipping is priced by weight, volume, destination and insured value. A full-size air-cooled miner with its packaging weighs roughly 15 to 20 kg, so several units are often cheaper per miner on a pallet than as individual parcels.', 'minemirth' ),
			__( 'Time depends on stock location and customs. In-stock units usually leave within a few business days of payment; batches and pre-orders follow the window in your quote. Customs checks can add days for international deliveries, and clear paperwork is the best way to avoid delays.', 'minemirth' ),
			__( 'If your miners are going to a hosting facility, we can ship them straight there with the facility’s details on the paperwork. Ask us for an ASIC miner shipping quote with your destination and quantity and we will include insurance and tracking.', 'minemirth' ),
		)
	),

	'seo:warranty'   => static fn() => mm_bseo(
		__( 'Getting the most from your ASIC miner warranty', 'minemirth' ),
		array(
			__( 'Keep your invoice and the miner’s serial number, and leave warranty seals intact. Manufacturers generally do not cover damage from water, dust, corrosion, unstable power or physical impact, so run the miner within its specified temperature, humidity and voltage range.', 'minemirth' ),
			__( 'Aftermarket firmware can affect your ASIC miner warranty with some manufacturers. If you plan to change firmware, check the maker’s policy first, and keep a copy of the stock firmware in case a repair centre asks for it.', 'minemirth' ),
			__( 'When a fault appears, record what you see: the error in the web interface, the kernel log, photos of the board and the steps you tried from our <a href="{page:g-troubleshoot}">troubleshooting guide</a>. Good records make a claim faster, and we use them to help you decide whether a warranty claim or a quick fix on site is the better route.', 'minemirth' ),
		)
	),

	'seo:returns'    => static fn() => mm_bseo(
		__( 'Returns and refunds: practical tips', 'minemirth' ),
		array(
			__( 'Unpack and test your miner as soon as it arrives, and report any problem within the period stated in your order confirmation. Photos of the outer box, the packaging and the unit help with dead-on-arrival claims and with any insurance claim against the carrier.', 'minemirth' ),
			__( 'Keep the original packaging until the miner has run well for a while. Returns and refunds go far more smoothly when a unit travels back in packaging designed for it.', 'minemirth' ),
			__( 'Because miner prices change with the market, the conditions for returns and refunds on mining hardware are different from those for everyday goods. Read the policy above before you order, and ask us if anything is unclear. We would rather answer a question first than resolve a dispute later.', 'minemirth' ),
		)
	),

	'seo:services'   => static fn() => mm_bseo(
		__( 'How our mining services fit together', 'minemirth' ),
		array(
			__( 'Most customers use our mining services in a natural order. First comes advice: which miner suits your power price, space and budget. Then supply, from a single unit to a <a href="{page:wholesale}">wholesale order</a>. Next, the miners are set up at home or in a <a href="{page:hosting}">hosting facility</a>, tuned for efficiency and monitored.', 'minemirth' ),
			__( 'Over time, the work shifts to keeping hashrate high and costs low: <a href="{page:repairs}">repairs</a> when a board fails, firmware updates, and eventually a <a href="{page:tradein}">trade-in</a> when newer models earn far more on the same power. Companies and landowners with a power source can start with <a href="{page:consulting}">farm design</a> instead.', 'minemirth' ),
			__( 'Every one of our mining services comes with a written scope and price before work starts. You choose the pieces you need, and you can always run the numbers yourself with the free tools.', 'minemirth' ),
			__( 'Not sure which of our mining services you need? Describe your situation in a sentence or two, for example “two miners at home, power at $0.09 per kWh, noise is a problem”, and we will suggest the simplest option and what it costs.', 'minemirth' ),
			__( 'Each service can be booked on its own. You do not need to buy your miners from us to use our hosting, setup, repair or consulting help, although combining them often saves shipping and time.', 'minemirth' ),
		)
	),

	'seo:hosting'    => static fn() => mm_bseo(
		__( 'Is ASIC miner hosting right for you?', 'minemirth' ),
		array(
			__( 'ASIC miner hosting usually makes sense when your home electricity is expensive, you have no suitable space, or the noise and heat of a full-size miner would be a problem. A facility buys power at industrial rates, provides cooling and staff, and you pay one all-in rate per kWh.', 'minemirth' ),
			__( 'Mining at home can still win when your power is cheap, you can use the heat, or you only run one or two quiet units. The <a href="{tool:hosting}">hosting vs home calculator</a> compares both on your numbers, including setup costs and uptime.', 'minemirth' ),
			__( 'Before choosing any ASIC miner hosting plan, read the whole rate: what is included, how uptime and curtailment are handled, how repairs work and how you can take your miners back. Our guide to <a href="{page:g-hosting}">choosing a hosting provider</a> lists every question to ask, and our plans above answer them in writing.', 'minemirth' ),
		)
	),

	'seo:repairs'    => static fn() => mm_bseo(
		__( 'When ASIC miner repair is worth it', 'minemirth' ),
		array(
			__( 'ASIC miner repair makes sense when the miner still earns well at your power price. A current or recent model with one failed hashboard is almost always worth fixing; a much older, inefficient model may be worth more as a <a href="{page:tradein}">trade-in</a> than after a repair.', 'minemirth' ),
			__( 'Check the warranty first: a unit under the manufacturer’s cover should go through a <a href="{page:warranty}">warranty claim</a>. For everything else, send us the model, the fault and the kernel log, and we will tell you the likely cause and a quote before any paid work starts.', 'minemirth' ),
			__( 'Shipping a single hashboard is cheaper than shipping a whole miner. Pack boards in anti-static bags with firm padding, and never ship a unit with coolant or fluid inside. A careful ASIC miner repair starts with a board that arrives undamaged.', 'minemirth' ),
		)
	),

	'seo:setup'      => static fn() => mm_bseo(
		__( 'What a good ASIC miner setup includes', 'minemirth' ),
		array(
			__( 'A proper ASIC miner setup starts before the miner is switched on: a circuit rated for the load, the right power cord, airflow with somewhere for the hot exhaust to go, and a wired network connection. Then the miner gets a fixed IP address, a changed admin password and current firmware.', 'minemirth' ),
			__( 'Next is the pool: your worker name, at least two pool servers for failover, and a payout address you control. We check that the hashrate on the pool dashboard matches the miner after 15 to 30 minutes.', 'minemirth' ),
			__( 'Finally, tuning. At expensive power, a slightly lower clock can raise profit by improving efficiency; at cheap power, more hashrate may pay. We tune each ASIC miner setup to your electricity price, and you can test the trade-off yourself in the <a href="{tool:profit}">profitability calculator</a>.', 'minemirth' ),
		)
	),

	'seo:tradein'    => static fn() => mm_bseo(
		__( 'How to prepare for an ASIC miner trade-in', 'minemirth' ),
		array(
			__( 'Send us the model and hashrate variant, the quantity, the condition and a recent screenshot of each miner running. Units that hash at their rated speed, with all fans and boards working, earn the best ASIC miner trade-in offers.', 'minemirth' ),
			__( 'Clean the miners and remove your pool settings before they travel. Keep power supplies and cords together with the units they belong to, and use sturdy packaging: damage in transit lowers the value for everyone.', 'minemirth' ),
			__( 'Timing matters. Used miner prices tend to follow hashprice, and older models lose value fastest when difficulty rises or after a halving. Use the <a href="{tool:upgrade}">upgrade calculator</a> to see what a new model earns on the same power, then ask for an ASIC miner trade-in offer and compare.', 'minemirth' ),
			__( 'You do not have to buy from us to ask for a valuation. An offer is a useful benchmark even if you decide to keep running the miners for a while.', 'minemirth' ),
		)
	),

	'seo:wholesale'  => static fn() => mm_bseo(
		__( 'Buying wholesale ASIC miners: what to agree in writing', 'minemirth' ),
		array(
			__( 'For wholesale ASIC miners, put the details in the contract: the exact model and hashrate variant, quantity, unit price and currency, the batch or shipping window, how deposits and balances are paid, and what happens if a batch is delayed.', 'minemirth' ),
			__( 'Agree how units are checked. We can test and document a sample or every unit, record serial numbers, and send the list with the shipment so your team or your hosting facility can match each miner on arrival.', 'minemirth' ),
			__( 'Plan the site at the same time. A fleet needs power capacity, electrical distribution, cooling and network ready on the day it lands. If you are still building, our <a href="{page:consulting}">farm design</a> service can line up the site with the delivery of your wholesale ASIC miners.', 'minemirth' ),
			__( 'Payment for wholesale ASIC miners is usually split into a deposit to reserve the batch and a balance before shipping. We confirm both amounts, due dates and bank details in writing, and we never change them by email.', 'minemirth' ),
			__( 'For repeat orders, we keep your site details, preferred models and paperwork on file, so the next batch moves faster.', 'minemirth' ),
		)
	),

	'seo:consulting' => static fn() => mm_bseo(
		__( 'What mining farm consulting should deliver', 'minemirth' ),
		array(
			__( 'Good mining farm consulting ends with documents you can act on: a power and electrical plan sized for continuous load, a cooling design (air, hydro or immersion), a layout with service access, a network and monitoring plan, and a financial model with the assumptions written down.', 'minemirth' ),
			__( 'The financial model matters most. It should show revenue under several Bitcoin price and difficulty scenarios, the effect of the next halving, power and staffing costs, and how much curtailment the site can accept. Our <a href="{page:g-farm}">farm planning guide</a> explains each part.', 'minemirth' ),
			__( 'Mining farm consulting is most useful early, before equipment is ordered or a power contract is signed. A few days of planning can prevent a transformer that is too small or a building that cannot shed its heat.', 'minemirth' ),
			__( 'We can work from a rough idea, such as a power source and a budget, or review a plan your team has already drawn up. Either way, you get clear recommendations and the reasoning behind them.', 'minemirth' ),
			__( 'The result is yours to keep and use with any contractor or supplier you choose.', 'minemirth' ),
		)
	),

	'seo:business'   => static fn() => mm_bseo(
		__( 'Making the case for Bitcoin mining for businesses', 'minemirth' ),
		array(
			__( 'Bitcoin mining for businesses works best where energy is cheap, flexible or otherwise wasted: a solar or wind site that curtails at midday, gas that would be flared, or a facility that needs heat. Miners can switch off in seconds, which makes them a useful flexible load.', 'minemirth' ),
			__( 'Finance teams want three things: a clear capital cost, a revenue model with scenarios, and a reporting plan for the coins mined. We provide hardware quotes, model revenue with live <a href="{tool:hashprice}">hashprice</a> data, and help you set up pool accounts and wallets your auditors can follow.', 'minemirth' ),
			__( 'Tax and accounting treatment depends on your country, so involve your advisers early. Bitcoin mining for businesses is an infrastructure decision, and it deserves the same diligence as any other.', 'minemirth' ),
		)
	),

	'seo:market'     => static fn() => mm_bseo(
		__( 'Reading the Bitcoin mining market', 'minemirth' ),
		array(
			__( 'The Bitcoin mining market moves on four numbers: the Bitcoin price, network difficulty, transaction fees and the block subsidy. Hashprice combines them into what one PH/s earns per day, so it is the quickest way to see whether conditions are getting better or worse for miners.', 'minemirth' ),
			__( 'Watch the trend rather than single days. A rising price with stable difficulty raises margins; when difficulty catches up, margins return to normal. Fees can spike briefly during busy periods, then fall back.', 'minemirth' ),
			__( 'For your own decisions, turn the Bitcoin mining market into your numbers: open the <a href="{tool:profit}">profitability calculator</a> with your miner and power price, or the <a href="{tool:breakeven}">break-even calculator</a> to see how much room you have.', 'minemirth' ),
		)
	),

	'seo:partners'   => static fn() => mm_bseo(
		__( 'Why partner with Mine Mirth', 'minemirth' ),
		array(
			__( '<strong>Hosting facilities</strong> that partner with Mine Mirth reach customers who already have miners or are buying them, with clear plan details we publish alongside our own. We only list capacity a facility has confirmed in writing.', 'minemirth' ),
			__( '<strong>Resellers and installers</strong> get access to stock, technical help and pricing for repeat orders. Electricians who understand continuous loads are always in demand by miners setting up at home.', 'minemirth' ),
			__( '<strong>Creators and educators</strong> can use our live tools and guides in their work, and we welcome corrections and new ideas from people who teach mining.', 'minemirth' ),
			__( 'To partner with Mine Mirth, tell us who you serve, where you operate and what you would like to build together. We reply with a short proposal and the next steps.', 'minemirth' ),
			__( 'Partnerships work best when both sides are clear about what they offer. We share our product data, tools and technical knowledge, and we expect partners to describe their own services honestly to the miners we send their way.', 'minemirth' ),
			__( 'There is no fixed form for a proposal. A short email with your company name, what you do and how many miners you serve each month is enough to start a conversation.', 'minemirth' ),
			__( 'We read every proposal and reply to each one, even when the answer is not yet.', 'minemirth' ),
			__( 'If we are a good fit, the next step is a short call to agree on scope, responsibilities and how we measure success together.', 'minemirth' ),
			__( 'We look forward to hearing from you.', 'minemirth' ),
			__( 'Every partnership starts with a conversation.', 'minemirth' ),
		)
	),
);
