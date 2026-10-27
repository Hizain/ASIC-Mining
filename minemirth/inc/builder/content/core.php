<?php
/**
 * Content: home, about, contact, why us, FAQ, reviews, payments, shipping, warranty, returns.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

return array(

	'home'     => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'ASIC miners · hosting · live tools', 'minemirth' ),
				'title'   => __( 'Mine Bitcoin with *real numbers* behind every decision', 'minemirth' ),
				'text'    => __( 'Tested ASIC miners with warranty and insured delivery, hosting in partner facilities, and free tools that run on the live network: Bitcoin is {btc_price} and one petahash earns {hashprice} a day right now.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Shop miners', 'minemirth' ), '{shop}' ), array( __( 'Calculate my profit', 'minemirth' ), '{tool:profit}', 'glass', 'calculator' ) ) ),
				'badges'  => array( __( 'Manufacturer warranty', 'minemirth' ), __( 'Insured, tracked shipping', 'minemirth' ), __( 'Setup help included', 'minemirth' ), __( 'Card, bank or crypto', 'minemirth' ) ),
				'visual'  => 'market',
			)
		)
		. mm_bs(
			'badges',
			array(
				'pad'      => 'sm',
				'payments' => true,
				'items'    => array(
					array( 'icon' => 'shield-check', 'title' => __( 'Warranty-backed', 'minemirth' ), 'text' => __( 'New miners ship with manufacturer cover', 'minemirth' ) ),
					array( 'icon' => 'truck', 'title' => __( 'Insured delivery', 'minemirth' ), 'text' => __( 'To your door or a hosting site', 'minemirth' ) ),
					array( 'icon' => 'headset', 'title' => __( 'Human support', 'minemirth' ), 'text' => __( 'Setup, pools and troubleshooting', 'minemirth' ) ),
					array( 'icon' => 'activity', 'title' => __( 'Live numbers', 'minemirth' ), 'text' => __( 'Profit at today’s price and difficulty', 'minemirth' ) ),
				),
			)
		)
		. mm_bs(
			'products',
			array(
				'eyebrow'    => __( 'In stock now', 'minemirth' ),
				'title'      => __( 'Popular *miners*', 'minemirth' ),
				'text'       => __( 'Every card shows real specs. Tap the calculator icon to see what the miner earns at today’s numbers.', 'minemirth' ),
				'query'      => array( 'sort' => 'popular' ),
				'limit'      => 8,
				'link_label' => __( 'Shop all miners', 'minemirth' ),
				'link_url'   => '{shop}',
			)
		)
		. mm_bs(
			'links',
			array(
				'eyebrow' => __( 'Find your miner', 'minemirth' ),
				'title'   => __( 'Shop by *coin and setup*', 'minemirth' ),
				'columns' => '4',
				'tone'    => 'alt',
				'items'   => array(
					array( 'icon' => 'bitcoin', 'title' => __( 'Bitcoin miners', 'minemirth' ), 'text' => __( 'SHA-256 ASICs from Bitmain, MicroBT and Canaan.', 'minemirth' ), 'url' => '{page:m-bitcoin}' ),
					array( 'icon' => 'hash', 'title' => __( 'Litecoin & Dogecoin', 'minemirth' ), 'text' => __( 'Scrypt miners that earn both coins at once.', 'minemirth' ), 'url' => '{page:m-scrypt}' ),
					array( 'icon' => 'zap-off', 'title' => __( 'Kaspa miners', 'minemirth' ), 'text' => __( 'kHeavyHash machines from compact to full size.', 'minemirth' ), 'url' => '{page:m-kaspa}' ),
					array( 'icon' => 'home', 'title' => __( 'Home & quiet', 'minemirth' ), 'text' => __( 'Low-noise miners for a normal outlet.', 'minemirth' ), 'url' => '{page:m-home}' ),
					array( 'icon' => 'star', 'title' => __( 'Solo miners', 'minemirth' ), 'text' => __( 'Bitaxe-style devices with a shot at a full block.', 'minemirth' ), 'url' => '{page:m-solo}' ),
					array( 'icon' => 'droplet', 'title' => __( 'Hydro-cooled', 'minemirth' ), 'text' => __( 'Quiet, dense and highly efficient.', 'minemirth' ), 'url' => '{page:m-hydro}' ),
					array( 'icon' => 'gauge', 'title' => __( 'Most efficient', 'minemirth' ), 'text' => __( 'Ranked live by joules per terahash.', 'minemirth' ), 'url' => '{page:m-efficient}' ),
					array( 'icon' => 'tag', 'title' => __( 'Best value', 'minemirth' ), 'text' => __( 'The most hashrate for your budget.', 'minemirth' ), 'url' => '{page:m-value}' ),
				),
			)
		)
		. mm_bs(
			'stats',
			array(
				'eyebrow' => __( 'The network right now', 'minemirth' ),
				'title'   => __( 'Mining runs on *four numbers*', 'minemirth' ),
				'text'    => __( 'Price, difficulty, fees and the halving decide what every miner earns. We track them live so you don’t have to.', 'minemirth' ),
				'live'    => true,
				'items'   => array(
					array( 'icon' => 'bitcoin', 'value' => '{btc_price}', 'label' => __( 'Bitcoin price', 'minemirth' ), 'text' => __( '24h change {btc_change}', 'minemirth' ) ),
					array( 'icon' => 'activity', 'value' => '{hashprice}', 'label' => __( 'Hashprice per PH/s per day', 'minemirth' ), 'text' => __( 'What hashrate earns today', 'minemirth' ) ),
					array( 'icon' => 'layers', 'value' => '{difficulty}', 'label' => __( 'Network difficulty', 'minemirth' ), 'text' => __( 'Next: {adjustment}', 'minemirth' ) ),
					array( 'icon' => 'clock', 'value' => '{halving_days}', 'label' => __( 'Days to the halving', 'minemirth' ), 'text' => __( 'Expected around {halving_date}', 'minemirth' ) ),
				),
				'note'    => __( 'Live from mempool.space and CoinGecko, refreshed every few minutes.', 'minemirth' ),
			)
		)
		. mm_bs(
			'tools',
			array(
				'eyebrow'    => __( 'Free tools', 'minemirth' ),
				'title'      => __( 'Plan with *live data*, not guesses', 'minemirth' ),
				'text'       => __( 'Twelve calculators built on the live network and our real stock: profit, hashprice, break-even power, ROI, halving impact, hosting versus home and more.', 'minemirth' ),
				'limit'      => 6,
				'link_label' => __( 'All 12 tools', 'minemirth' ),
				'link_url'   => '{page:tools}',
			)
		)
		. mm_bs(
			'split',
			array(
				'eyebrow' => __( 'Hosting', 'minemirth' ),
				'title'   => __( 'No space, noise or cheap power? *Host it.*', 'minemirth' ),
				'text'    => __( 'We ship your miners straight to a partner facility, set them up on your pool and watch them around the clock. You pay one all-in rate per kWh, published on every plan, and your coins go straight to your own wallet.', 'minemirth' ),
				'bullets' => array( __( 'All-in power rate: no hidden multipliers', 'minemirth' ), __( 'Remote monitoring and on-site technicians', 'minemirth' ), __( 'Payouts to your own pool account and wallet', 'minemirth' ), __( 'Buy and host in one order', 'minemirth' ) ),
				'buttons' => mm_bbtn( array( array( __( 'See hosting plans', 'minemirth' ), '{page:hosting}' ), array( __( 'Hosting vs home calculator', 'minemirth' ), '{tool:hosting}', 'ghost' ) ) ),
				'visual'  => 'orbit',
				'icon'    => 'server',
				'chips'   => mm_bchips( array( array( 'bolt', __( 'All-in', 'minemirth' ), __( 'rate per kWh', 'minemirth' ) ), array( 'activity', '24/7', __( 'monitoring', 'minemirth' ) ), array( 'wrench', __( 'On-site', 'minemirth' ), __( 'technicians', 'minemirth' ) ), array( 'wallet', __( 'Your', 'minemirth' ), __( 'wallet, your coins', 'minemirth' ) ) ) ),
				'tone'    => 'gradient',
			)
		)
		. mm_bs(
			'steps',
			array(
				'eyebrow' => __( 'How it works', 'minemirth' ),
				'title'   => __( 'From first question to *first share*', 'minemirth' ),
				'items'   => array(
					array( 'icon' => 'calculator', 'title' => __( 'Run your numbers', 'minemirth' ), 'text' => __( 'Use the live calculators with your power price, or ask us to model it for you.', 'minemirth' ) ),
					array( 'icon' => 'cpu', 'title' => __( 'Pick the miner', 'minemirth' ), 'text' => __( 'Choose from stock or reserve a batch. We confirm specs, lead time and the total price in writing.', 'minemirth' ) ),
					array( 'icon' => 'truck', 'title' => __( 'Insured delivery', 'minemirth' ), 'text' => __( 'Packed for transport, insured and tracked, to your door or straight to a hosting facility.', 'minemirth' ) ),
					array( 'icon' => 'zap-off', 'title' => __( 'Hashing', 'minemirth' ), 'text' => __( 'Plug in, point it at your pool, and we help with setup and tuning if you need it.', 'minemirth' ) ),
				),
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'Why miners choose us', 'minemirth' ),
				'title'   => __( 'Built for people who *check the numbers*', 'minemirth' ),
				'columns' => '3',
				'style'   => 'bento',
				'tone'    => 'alt',
				'items'   => mm_bitems(
					array(
						array( 'shield-check', __( 'Honest specs and live profit', 'minemirth' ), __( 'Every miner page shows hashrate, power, efficiency and what it earns at today’s difficulty and your power price. No “up to” marketing.', 'minemirth' ), '{page:why}', __( 'Why Mine Mirth', 'minemirth' ) ),
						array( 'package', __( 'Checked before it ships', 'minemirth' ), __( 'Units are inspected and packed for freight, with serial numbers on your invoice.', 'minemirth' ) ),
						array( 'headset', __( 'Help after the sale', 'minemirth' ), __( 'Pool setup, firmware, troubleshooting and warranty claims handled with you.', 'minemirth' ) ),
						array( 'server', __( 'Buy and host together', 'minemirth' ), __( 'One order: miner, shipping and a hosting slot.', 'minemirth' ) ),
						array( 'refresh', __( 'Upgrade path', 'minemirth' ), __( 'Trade older miners toward newer, more efficient models.', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bs(
			'brands',
			array(
				'eyebrow' => __( 'Brands', 'minemirth' ),
				'title'   => __( 'Every major *manufacturer*', 'minemirth' ),
				'items'   => array(
					array( 'name' => 'Bitmain', 'text' => __( 'Antminer S21, L9, KS, AL', 'minemirth' ), 'url' => '{page:brand-bitmain}' ),
					array( 'name' => 'MicroBT', 'text' => __( 'Whatsminer M60 series', 'minemirth' ), 'url' => '{page:brand-microbt}' ),
					array( 'name' => 'Canaan', 'text' => __( 'Avalon industrial and home', 'minemirth' ), 'url' => '{page:brand-canaan}' ),
					array( 'name' => 'Goldshell', 'text' => __( 'Compact altcoin miners', 'minemirth' ), 'url' => '{page:brand-goldshell}' ),
					array( 'name' => 'IceRiver', 'text' => __( 'Kaspa KS series', 'minemirth' ), 'url' => '{page:brand-iceriver}' ),
					array( 'name' => 'Elphapex', 'text' => __( 'Scrypt DG series', 'minemirth' ), 'url' => '{page:brand-elphapex}' ),
				),
			)
		)
		. mm_bs( 'reviews', array( 'eyebrow' => __( 'Reviews', 'minemirth' ), 'title' => __( 'From verified *buyers*', 'minemirth' ), 'limit' => 6, 'hide_empty' => true, 'link_label' => __( 'All reviews', 'minemirth' ), 'link_url' => '{page:reviews}' ) )
		. mm_bs(
			'links',
			array(
				'eyebrow'    => __( 'Learn', 'minemirth' ),
				'title'      => __( 'Guides that make you a *better miner*', 'minemirth' ),
				'columns'    => '3',
				'tone'       => 'alt',
				'link_label' => __( 'All 24 guides', 'minemirth' ),
				'link_url'   => '{page:learn}',
				'items'      => array(
					array( 'icon' => 'rocket', 'title' => __( 'Bitcoin mining for beginners', 'minemirth' ), 'text' => __( 'How mining works, what it costs and how to start.', 'minemirth' ), 'url' => '{page:g-beginners}' ),
					array( 'icon' => 'cpu', 'title' => __( 'How to choose an ASIC miner', 'minemirth' ), 'text' => __( 'Efficiency, price per TH, cooling and noise.', 'minemirth' ), 'url' => '{page:g-choose}' ),
					array( 'icon' => 'trending-up', 'title' => __( 'Mining profitability explained', 'minemirth' ), 'text' => __( 'Revenue, power, difficulty growth and payback.', 'minemirth' ), 'url' => '{page:g-profit}' ),
					array( 'icon' => 'activity', 'title' => __( 'What is hashprice?', 'minemirth' ), 'text' => __( 'The number that tells you what hashrate earns.', 'minemirth' ), 'url' => '{page:g-hashprice}' ),
					array( 'icon' => 'bolt', 'title' => __( 'Power & electrical guide', 'minemirth' ), 'text' => __( 'Voltage, amps, breakers and safe circuits.', 'minemirth' ), 'url' => '{page:g-power}' ),
					array( 'icon' => 'home', 'title' => __( 'Mining at home', 'minemirth' ), 'text' => __( 'Noise, heat, wiring and the right miner.', 'minemirth' ), 'url' => '{page:g-home}' ),
				),
			)
		)
		. mm_bfaq(
			array(
				array( __( 'Is Bitcoin mining still profitable?', 'minemirth' ), __( 'It depends mostly on your electricity price and your miner’s efficiency. With today’s hashprice of {hashprice} per PH/s per day, an efficient modern miner is usually profitable on power below roughly $0.08–0.10 per kWh. Use the <a href="{tool:profit}">profit calculator</a> with your own rate for an exact answer.', 'minemirth' ) ),
				array( __( 'Do you ship internationally?', 'minemirth' ), __( 'Yes. We quote insured, tracked shipping to your country or straight to a hosting facility, and tell you in advance about import duties and paperwork. See <a href="{page:shipping}">shipping & delivery</a>.', 'minemirth' ) ),
				array( __( 'Do miners come with a warranty?', 'minemirth' ), __( 'New miners carry the manufacturer’s warranty; the length depends on the model and is shown on the product page. We help you with any claim. See <a href="{page:warranty}">warranty & support</a>.', 'minemirth' ) ),
				array( __( 'Can I run an ASIC miner at home?', 'minemirth' ), __( 'Full-size ASICs need a 200–240 V circuit, strong ventilation and somewhere the 70–80 dB noise is acceptable. For living spaces, choose a <a href="{page:m-home}">quiet home miner</a> or <a href="{page:hosting}">host</a> a full-size one.', 'minemirth' ) ),
				array( __( 'How do I pay?', 'minemirth' ), __( 'By card, bank transfer or cryptocurrency, depending on the order. The options available to you are shown at checkout. See <a href="{page:payments}">payment methods</a>.', 'minemirth' ) ),
			),
			'',
			array( 'buttons' => mm_bbtn( array( array( __( 'All questions', 'minemirth' ), '{page:faq}', 'ghost' ) ) ) )
		)
		. mm_bs( 'newsletter', array( 'eyebrow' => __( 'Weekly mining brief', 'minemirth' ), 'title' => __( 'Hashprice, difficulty and new stock, *once a week*', 'minemirth' ), 'text' => __( 'The numbers that moved, the miners that arrived and one practical tip. No spam, unsubscribe in one click.', 'minemirth' ), 'context' => 'home' ) )
		. mm_bcta( __( 'Ready to start *mining*?', 'minemirth' ), __( 'Tell us your power price and goals and we’ll recommend the right miners, or a hosting plan, with the numbers to back it up.', 'minemirth' ), array( array( __( 'Shop miners', 'minemirth' ), '{shop}' ), array( __( 'Talk to a specialist', 'minemirth' ), '{page:contact}' ) ) );
	},

	'about'    => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'About us', 'minemirth' ),
				'title'   => __( 'We help people mine with *clear eyes*', 'minemirth' ),
				'text'    => __( 'Mine Mirth supplies ASIC miners, hosting and the know-how around them. We started with a simple idea: every mining decision should come with the real numbers, explained in plain language.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Shop miners', 'minemirth' ), '{shop}' ), array( __( 'Contact us', 'minemirth' ), '{page:contact}', 'glass' ) ) ),
				'visual'  => 'orbit',
				'icon'    => 'pickaxe',
				'chips'   => mm_bchips( array( array( 'cpu', __( 'Miners', 'minemirth' ), __( 'for every coin', 'minemirth' ) ), array( 'server', __( 'Hosting', 'minemirth' ), __( 'in partner sites', 'minemirth' ) ), array( 'calculator', '12', __( 'live tools', 'minemirth' ) ), array( 'book', '24', __( 'practical guides', 'minemirth' ) ) ) ),
			)
		)
		. mm_bs(
			'split',
			array(
				'eyebrow' => __( 'What we do', 'minemirth' ),
				'title'   => __( 'Everything around the *miner*', 'minemirth' ),
				'text'    => __( '<p>Buying an ASIC is the easy part. Making it pay means the right model for your power price, safe electrics, cooling, a good pool, sensible firmware and a plan for the next halving.</p><p>We cover all of it: we supply new and selected miners from the major manufacturers, host them in partner facilities, help with setup and repairs, and publish free tools and guides so you can check every recommendation yourself.</p>', 'minemirth' ),
				'bullets' => array( __( 'ASIC miners for Bitcoin, Litecoin/Dogecoin, Kaspa and Alephium', 'minemirth' ), __( 'Hosting with published all-in rates', 'minemirth' ), __( 'Setup, tuning, repairs and trade-ins', 'minemirth' ), __( 'Fleet supply and farm design for businesses', 'minemirth' ) ),
				'visual'  => 'checklist',
				'checklist_title' => __( 'How we work', 'minemirth' ),
				'checklist' => array( __( 'Specs and prices confirmed in writing', 'minemirth' ), __( 'Profit shown at live network numbers', 'minemirth' ), __( 'No pressure: we’ll tell you when not to buy', 'minemirth' ), __( 'Serial numbers on every invoice', 'minemirth' ), __( 'Support that answers technical questions', 'minemirth' ) ),
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'Our principles', 'minemirth' ),
				'title'   => __( 'Four promises we *keep*', 'minemirth' ),
				'columns' => '4',
				'tone'    => 'alt',
				'items'   => mm_bitems(
					array(
						array( 'eye', __( 'Transparency', 'minemirth' ), __( 'Real specs, real prices, real lead times. If a miner doesn’t make sense at your power price, we say so.', 'minemirth' ) ),
						array( 'activity', __( 'Data first', 'minemirth' ), __( 'Our tools use live price, difficulty and fees, and show their assumptions so you can change them.', 'minemirth' ) ),
						array( 'shield-check', __( 'Care in delivery', 'minemirth' ), __( 'Miners are expensive and heavy. We pack, insure and track every shipment.', 'minemirth' ) ),
						array( 'headset', __( 'Support that knows', 'minemirth' ), __( 'Questions about hashboards, pools or circuits get technical answers, not scripts.', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bs(
			'links',
			array(
				'eyebrow' => __( 'Explore', 'minemirth' ),
				'title'   => __( 'Where to *go next*', 'minemirth' ),
				'columns' => '3',
				'items'   => array(
					array( 'icon' => 'award', 'title' => __( 'Why Mine Mirth', 'minemirth' ), 'text' => __( 'What you get when you buy or host with us.', 'minemirth' ), 'url' => '{page:why}' ),
					array( 'icon' => 'settings', 'title' => __( 'Services', 'minemirth' ), 'text' => __( 'Hosting, setup, repairs, trade-ins and consulting.', 'minemirth' ), 'url' => '{page:services}' ),
					array( 'icon' => 'star', 'title' => __( 'Customer reviews', 'minemirth' ), 'text' => __( 'Verified reviews from people who bought from us.', 'minemirth' ), 'url' => '{page:reviews}' ),
				),
			)
		)
		. mm_bcta( __( 'Let’s plan your *setup*', 'minemirth' ), __( 'Send us your power price, location and budget. We’ll come back with options and the numbers behind them.', 'minemirth' ), array( array( __( 'Contact us', 'minemirth' ), '{page:contact}' ), array( __( 'Profit calculator', 'minemirth' ), '{tool:profit}' ) ) );
	},

	'contact'  => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Contact', 'minemirth' ),
				'title'   => __( 'Talk to a *mining specialist*', 'minemirth' ),
				'text'    => __( 'Quotes, stock and lead times, hosting slots, bulk orders or help with a miner you already own. Tell us what you need and we’ll reply with specifics.', 'minemirth' ),
				'visual'  => 'none',
				'pad'     => 'sm',
			)
		)
		. mm_bs(
			'contact',
			array(
				'eyebrow' => __( 'Write to us', 'minemirth' ),
				'title'   => __( 'How can we *help*?', 'minemirth' ),
				'text'    => __( 'The more you share (model, quantity, power price, country), the faster we can give you a firm answer.', 'minemirth' ),
				'note'    => __( '<p>For support with an order, include your order number. For repairs, include the model, serial number and what the miner’s status page or kernel log shows.</p>', 'minemirth' ),
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'Before you write', 'minemirth' ),
				'title'   => __( 'Answers you can get *right now*', 'minemirth' ),
				'columns' => '3',
				'tone'    => 'alt',
				'items'   => mm_bitems(
					array(
						array( 'calculator', __( 'Will it be profitable?', 'minemirth' ), __( 'The profit calculator uses live price and difficulty with your power rate.', 'minemirth' ), '{tool:profit}', __( 'Open calculator', 'minemirth' ) ),
						array( 'truck', __( 'Shipping and customs', 'minemirth' ), __( 'How we pack, insure and deliver, and what to expect at import.', 'minemirth' ), '{page:shipping}', __( 'Shipping info', 'minemirth' ) ),
						array( 'info', __( 'Common questions', 'minemirth' ), __( 'Orders, payment, warranty, hosting and power, answered.', 'minemirth' ), '{page:faq}', __( 'Read the FAQ', 'minemirth' ) ),
					)
				),
			)
		);
	},

	'why'      => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Why Mine Mirth', 'minemirth' ),
				'title'   => __( 'Buy miners from people who *mine the numbers*', 'minemirth' ),
				'text'    => __( 'Plenty of shops sell ASICs. We help you buy the right one, deliver it safely, get it hashing and keep it profitable, and we show our working at every step.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Shop miners', 'minemirth' ), '{shop}' ), array( __( 'Compare miners live', 'minemirth' ), '{tool:compare}', 'glass' ) ) ),
				'visual'  => 'checklist',
				'checklist_title' => __( 'With every order', 'minemirth' ),
				'checklist' => array( __( 'Specs, price and lead time confirmed in writing', 'minemirth' ), __( 'Serial numbers on your invoice', 'minemirth' ), __( 'Insured, tracked delivery', 'minemirth' ), __( 'Help with setup and your pool', 'minemirth' ), __( 'Warranty claims handled with you', 'minemirth' ) ),
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'The difference', 'minemirth' ),
				'title'   => __( 'Six reasons miners *come back*', 'minemirth' ),
				'columns' => '3',
				'items'   => mm_bitems(
					array(
						array( 'activity', __( 'Live profit on every miner', 'minemirth' ), __( 'Profit is calculated at the current price, difficulty and fees, at your power price, not at a flattering assumption.', 'minemirth' ) ),
						array( 'gauge', __( 'Ranked for you', 'minemirth' ), __( 'Our collections rank stock by efficiency, price per TH and payback, so the best options rise to the top.', 'minemirth' ), '{page:m-efficient}', __( 'Most efficient', 'minemirth' ) ),
						array( 'package', __( 'Packed for freight', 'minemirth' ), __( 'Miners are heavy and fragile. Units are packed to survive freight handling and insured door to door.', 'minemirth' ) ),
						array( 'server', __( 'Hosting in the same order', 'minemirth' ), __( 'Ship straight to a partner facility and start hashing without wiring a thing at home.', 'minemirth' ), '{page:hosting}', __( 'Hosting', 'minemirth' ) ),
						array( 'sliders', __( 'Setup and tuning', 'minemirth' ), __( 'We help configure pools and firmware, and tune efficiency to your power price.', 'minemirth' ), '{page:setup}', __( 'Setup service', 'minemirth' ) ),
						array( 'refresh', __( 'An upgrade path', 'minemirth' ), __( 'When newer miners make yours uneconomic, trade them in toward the next generation.', 'minemirth' ), '{page:tradein}', __( 'Trade-in', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bs(
			'table',
			array(
				'eyebrow'   => __( 'Compared', 'minemirth' ),
				'title'     => __( 'What to expect from *us*', 'minemirth' ),
				'columns'   => array( '', __( 'Mine Mirth', 'minemirth' ), __( 'Typical marketplace listing', 'minemirth' ) ),
				'highlight' => 1,
				'rows'      => array(
					__( 'Profit at live network numbers | yes | no', 'minemirth' ),
					__( 'Specs and lead time confirmed in writing | yes | Varies', 'minemirth' ),
					__( 'Serial numbers on the invoice | yes | Rarely', 'minemirth' ),
					__( 'Insured, tracked shipping | yes | Varies', 'minemirth' ),
					__( 'Setup and pool help | yes | no', 'minemirth' ),
					__( 'Hosting in the same order | yes | no', 'minemirth' ),
					__( 'Warranty claim support | yes | You handle it', 'minemirth' ),
				),
				'tone'      => 'alt',
			)
		)
		. mm_bs( 'reviews', array( 'eyebrow' => __( 'Reviews', 'minemirth' ), 'title' => __( 'What buyers *say*', 'minemirth' ), 'limit' => 3, 'hide_empty' => true ) )
		. mm_bcta( __( 'See the numbers for *your* power price', 'minemirth' ), __( 'Compare every miner we stock at your electricity rate, then talk to us about the shortlist.', 'minemirth' ), array( array( __( 'Compare miners', 'minemirth' ), '{tool:compare}' ), array( __( 'Talk to us', 'minemirth' ), '{page:contact}' ) ) );
	},

	'faq'      => static function () {
		$o = array( 'tone' => 'plain' );
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Help centre', 'minemirth' ),
				'title'   => __( 'Frequently asked *questions*', 'minemirth' ),
				'text'    => __( 'Orders, payment, shipping, warranty, hosting, power and profitability. Can’t find your answer? Ask us and we’ll add it here.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Ask a question', 'minemirth' ), '{page:contact}' ) ) ),
				'visual'  => 'none',
				'pad'     => 'sm',
			)
		)
		. mm_bfaq(
			array(
				array( __( 'How do I order a miner?', 'minemirth' ), __( 'Add it to your cart and check out, or ask for a quote if you need several units, a hosting slot or a specific batch. For larger orders we confirm specs, lead time and the total in writing before you pay.', 'minemirth' ) ),
				array( __( 'Are your miners new or used?', 'minemirth' ), __( 'Each product page states the condition. New miners come with the manufacturer’s warranty. Any used or refurbished unit is clearly labelled and described.', 'minemirth' ) ),
				array( __( 'What is a pre-order?', 'minemirth' ), __( 'Some new models ship in batches. A pre-order reserves units from a batch with an expected shipping window. The window, deposit and balance terms are shown before you commit. See <a href="{page:m-new}">new releases & pre-orders</a>.', 'minemirth' ) ),
				array( __( 'Can I get a bulk price?', 'minemirth' ), __( 'Yes. Volume quotes are available for fleets. See <a href="{page:wholesale}">wholesale & bulk orders</a>.', 'minemirth' ) ),
			),
			__( 'Ordering', 'minemirth' ),
			array( 'eyebrow' => __( 'Orders', 'minemirth' ), 'open_first' => false, 'schema' => true ) + $o
		)
		. mm_bfaq(
			array(
				array( __( 'Which payment methods do you accept?', 'minemirth' ), __( 'Card, bank transfer and cryptocurrency, depending on the order. The options available to you are shown at checkout. See <a href="{page:payments}">payment methods</a>.', 'minemirth' ) ),
				array( __( 'Is my payment secure?', 'minemirth' ), __( 'Card payments are handled by the payment provider; we never see or store your card number. For bank and crypto payments, always use the details on your invoice and confirm with us if anything looks different.', 'minemirth' ) ),
				array( __( 'Do I get an invoice?', 'minemirth' ), __( 'Yes, every order comes with an invoice listing the model, quantity and serial numbers once units are allocated.', 'minemirth' ) ),
			),
			__( 'Payment', 'minemirth' ),
			array( 'eyebrow' => __( 'Payment', 'minemirth' ), 'open_first' => false, 'tone' => 'alt' )
		)
		. mm_bfaq(
			array(
				array( __( 'How are miners shipped?', 'minemirth' ), __( 'Packed for freight, insured and tracked. You receive tracking as soon as the shipment is booked. See <a href="{page:shipping}">shipping & delivery</a>.', 'minemirth' ) ),
				array( __( 'Will I pay import duties?', 'minemirth' ), __( 'Import duties and taxes depend on your country and are usually paid by the receiver. We tell you what to expect and provide the commercial documents customs needs.', 'minemirth' ) ),
				array( __( 'Can you ship directly to a hosting facility?', 'minemirth' ), __( 'Yes. We ship to our partner facilities or to any address you give us.', 'minemirth' ) ),
				array( __( 'What if a miner arrives damaged?', 'minemirth' ), __( 'Photograph the box and the unit before you power it on and contact us within 48 hours. We handle the insurance claim with the carrier. See <a href="{page:returns}">returns & refunds</a>.', 'minemirth' ) ),
			),
			__( 'Shipping', 'minemirth' ),
			array( 'eyebrow' => __( 'Delivery', 'minemirth' ), 'open_first' => false )
		)
		. mm_bfaq(
			array(
				array( __( 'How long is the warranty?', 'minemirth' ), __( 'New miners carry the manufacturer’s warranty. Length varies by brand and model and is shown on the product page. See <a href="{page:warranty}">warranty & support</a>.', 'minemirth' ) ),
				array( __( 'Does custom firmware void the warranty?', 'minemirth' ), __( 'It can. Manufacturers may refuse claims on units running third-party firmware. Ask us before you flash a miner under warranty.', 'minemirth' ) ),
				array( __( 'Do you repair miners?', 'minemirth' ), __( 'Yes: hashboards, power supplies, fans and control boards. See <a href="{page:repairs}">repairs & servicing</a>.', 'minemirth' ) ),
			),
			__( 'Warranty & repairs', 'minemirth' ),
			array( 'eyebrow' => __( 'Warranty', 'minemirth' ), 'open_first' => false, 'tone' => 'alt' )
		)
		. mm_bfaq(
			array(
				array( __( 'What does hosting cost?', 'minemirth' ), __( 'Each plan publishes its all-in rate per kWh, billing basis, minimum and term on the <a href="{page:hosting}">hosting page</a>. A miner drawing 3,500 W uses about 2,557 kWh in a month.', 'minemirth' ) ),
				array( __( 'Who receives the mining rewards?', 'minemirth' ), __( 'You do. Your miners point at your own pool account and payouts go to your wallet.', 'minemirth' ) ),
				array( __( 'Can I visit or take my miners back?', 'minemirth' ), __( 'Visits and removal follow the facility’s rules and your agreement, which we confirm in writing before shipment.', 'minemirth' ) ),
			),
			__( 'Hosting', 'minemirth' ),
			array( 'eyebrow' => __( 'Hosting', 'minemirth' ), 'open_first' => false )
		)
		. mm_bfaq(
			array(
				array( __( 'What electricity does an ASIC need?', 'minemirth' ), __( 'Full-size miners typically need 200–240 V and 15–20 A per unit on a dedicated circuit. Small home miners run on a normal outlet. Use the <a href="{tool:power}">power & circuit planner</a> and have an electrician install the circuit.', 'minemirth' ) ),
				array( __( 'How loud is a miner?', 'minemirth' ), __( 'Air-cooled ASICs typically run at 70–80 dB, like a vacuum cleaner. Hydro, immersion and dedicated home miners are much quieter. See <a href="{page:g-noise}">reducing ASIC noise</a>.', 'minemirth' ) ),
				array( __( 'How much will I earn?', 'minemirth' ), __( 'Revenue = your hashrate × hashprice (now {hashprice} per PH/s per day), minus pool fees; profit subtracts your power cost. The <a href="{tool:profit}">profit calculator</a> does the maths with live data.', 'minemirth' ) ),
				array( __( 'What happens at the next halving?', 'minemirth' ), __( 'The block subsidy halves from 3.125 to 1.5625 BTC around {halving_date}, cutting revenue per TH unless price or fees rise. The <a href="{tool:halving}">halving calculator</a> shows the effect on your miner.', 'minemirth' ) ),
			),
			__( 'Power & profitability', 'minemirth' ),
			array( 'eyebrow' => __( 'Mining', 'minemirth' ), 'open_first' => false, 'tone' => 'alt' )
		)
		. mm_bcta( __( 'Still have a *question*?', 'minemirth' ), __( 'Ask us anything about miners, hosting or your setup.', 'minemirth' ), array( array( __( 'Contact us', 'minemirth' ), '{page:contact}' ), array( __( 'Read the guides', 'minemirth' ), '{page:learn}' ) ) );
	},

	'reviews'  => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Reviews', 'minemirth' ),
				'title'   => __( 'What our *customers* say', 'minemirth' ),
				'text'    => __( 'These are product reviews left by buyers on our product pages. We publish them as written; we don’t pay for reviews or edit them.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Shop miners', 'minemirth' ), '{shop}' ) ) ),
				'visual'  => 'none',
				'pad'     => 'sm',
			)
		)
		. mm_bs( 'reviews', array( 'title' => __( 'Verified *reviews*', 'minemirth' ), 'limit' => 12 ) )
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'Our review policy', 'minemirth' ),
				'title'   => __( 'How reviews work *here*', 'minemirth' ),
				'columns' => '3',
				'tone'    => 'alt',
				'items'   => mm_bitems(
					array(
						array( 'check-circle', __( 'From real orders', 'minemirth' ), __( 'Reviews marked “Verified buyer” come from customers who bought that product from us.', 'minemirth' ) ),
						array( 'eye', __( 'Published as written', 'minemirth' ), __( 'We don’t rewrite reviews. We only remove spam, personal data or abuse.', 'minemirth' ) ),
						array( 'message', __( 'We reply to problems', 'minemirth' ), __( 'If something went wrong, we’ll contact you to fix it, whatever rating you left.', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bcta( __( 'Bought from us? *Share your experience*', 'minemirth' ), __( 'Leave a review on your miner’s product page. It helps other miners choose.', 'minemirth' ), array( array( __( 'Go to my orders', 'minemirth' ), '{account}orders/' ), array( __( 'Contact us', 'minemirth' ), '{page:contact}' ) ), array( 'style' => 'light' ) );
	},

	'payments' => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Payment', 'minemirth' ),
				'title'   => __( 'Pay the way that *suits you*', 'minemirth' ),
				'text'    => __( 'Card, bank transfer or cryptocurrency. The methods available for your order and country are shown at checkout.', 'minemirth' ),
				'visual'  => 'orbit',
				'icon'    => 'wallet',
				'chips'   => mm_bchips( array( array( 'credit-card', __( 'Card', 'minemirth' ), __( 'via secure provider', 'minemirth' ) ), array( 'globe', __( 'Bank', 'minemirth' ), __( 'transfer', 'minemirth' ) ), array( 'bitcoin', __( 'Crypto', 'minemirth' ), __( 'BTC and stablecoins', 'minemirth' ) ) ) ),
			)
		)
		. mm_bs( 'badges', array( 'pad' => 'sm', 'payments' => true, 'items' => array() ) )
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'Methods', 'minemirth' ),
				'title'   => __( 'How each *method* works', 'minemirth' ),
				'columns' => '3',
				'items'   => mm_bitems(
					array(
						array( 'credit-card', __( 'Card and wallets', 'minemirth' ), __( 'Pay by debit or credit card, or a wallet such as Apple Pay or Google Pay where offered. Payments are processed by the payment provider; we never see your full card number. Large orders may need extra verification by your bank.', 'minemirth' ) ),
						array( 'globe', __( 'Bank transfer', 'minemirth' ), __( 'Best for larger orders. Use the account details on your invoice and include the order number as the reference. Units are allocated when the payment clears.', 'minemirth' ) ),
						array( 'bitcoin', __( 'Cryptocurrency', 'minemirth' ), __( 'Where offered at checkout, pay in Bitcoin or supported stablecoins. The amount is locked for a short window; send the exact amount to the address shown, on the network shown.', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bs(
			'split',
			array(
				'eyebrow' => __( 'Security', 'minemirth' ),
				'title'   => __( 'Protect yourself from *payment fraud*', 'minemirth' ),
				'text'    => __( 'Fraudsters impersonate sellers by email and chat and send fake bank or wallet details. These habits keep you safe:', 'minemirth' ),
				'bullets' => array( __( 'Only pay using the details on your invoice or in your account', 'minemirth' ), __( 'We never change bank details by email alone: call or message us to confirm', 'minemirth' ), __( 'For crypto, double-check the first and last characters of the address', 'minemirth' ), __( 'Be wary of prices far below the market: they are usually scams', 'minemirth' ) ),
				'visual'  => 'orbit',
				'icon'    => 'lock',
				'tone'    => 'alt',
				'reverse' => true,
			)
		)
		. mm_bfaq(
			array(
				array( __( 'When is my order confirmed?', 'minemirth' ), __( 'When the payment clears. For cards that is usually immediate; for bank transfers it depends on your bank; for crypto it is after the required network confirmations.', 'minemirth' ) ),
				array( __( 'Can I pay a deposit?', 'minemirth' ), __( 'For some pre-orders and bulk orders, a deposit secures your allocation with the balance due before shipping. The terms are stated on the quote.', 'minemirth' ) ),
				array( __( 'Can I get a business invoice?', 'minemirth' ), __( 'Yes. Add your company name and tax number at checkout or tell us before you pay.', 'minemirth' ) ),
				array( __( 'What if I sent the wrong crypto amount?', 'minemirth' ), __( 'Contact us with the transaction ID. Underpayments can be topped up; overpayments are refunded to an address you provide, less network fees.', 'minemirth' ) ),
			)
		)
		. mm_bcta( __( 'Questions about *paying*?', 'minemirth' ), __( 'We can confirm details by phone or message before you send anything.', 'minemirth' ), array( array( __( 'Contact us', 'minemirth' ), '{page:contact}' ) ), array( 'style' => 'light' ) );
	},

	'shipping' => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Shipping & delivery', 'minemirth' ),
				'title'   => __( 'Insured, tracked, *packed for freight*', 'minemirth' ),
				'text'    => __( 'ASIC miners are heavy, valuable and fragile. Here is how we get them to you, or to a hosting facility, safely.', 'minemirth' ),
				'visual'  => 'orbit',
				'icon'    => 'truck',
				'chips'   => mm_bchips( array( array( 'package', __( 'Packed', 'minemirth' ), __( 'for freight', 'minemirth' ) ), array( 'shield-check', __( 'Insured', 'minemirth' ), __( 'door to door', 'minemirth' ) ), array( 'map-pin', __( 'Tracked', 'minemirth' ), __( 'from dispatch', 'minemirth' ) ) ) ),
			)
		)
		. mm_bs(
			'steps',
			array(
				'eyebrow' => __( 'From order to doorstep', 'minemirth' ),
				'title'   => __( 'How *delivery* works', 'minemirth' ),
				'items'   => array(
					array( 'check-circle', __( 'Allocation', 'minemirth' ), __( 'Once payment clears, units are allocated to your order and serial numbers recorded.', 'minemirth' ) ),
					array( 'package', __( 'Inspection and packing', 'minemirth' ), __( 'Units are checked and packed to protect hashboards and fans in transit.', 'minemirth' ) ),
					array( 'truck', __( 'Dispatch', 'minemirth' ), __( 'We book insured transport and send tracking and the commercial documents.', 'minemirth' ) ),
					array( 'home', __( 'Delivery', 'minemirth' ), __( 'To your address or a hosting facility. Inspect before signing if you can.', 'minemirth' ) ),
				),
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'Good to know', 'minemirth' ),
				'title'   => __( 'Costs, customs and *timing*', 'minemirth' ),
				'columns' => '2',
				'tone'    => 'alt',
				'items'   => mm_bitems(
					array(
						array( 'tag', __( 'Shipping cost', 'minemirth' ), __( 'Shown at checkout or on your quote. It depends on weight, quantity, destination and speed. Combining units in one shipment usually lowers the cost per miner.', 'minemirth' ) ),
						array( 'globe', __( 'Customs and duties', 'minemirth' ), __( 'International deliveries may attract import duties, taxes and brokerage fees, normally paid by the receiver. We provide the invoice and documents customs needs and tell you in advance what to expect where we can.', 'minemirth' ) ),
						array( 'clock', __( 'Delivery time', 'minemirth' ), __( 'In-stock units usually ship within a few business days of payment clearing. Transit time depends on destination and service. Pre-orders ship in their stated batch window.', 'minemirth' ) ),
						array( 'server', __( 'Shipping to hosting', 'minemirth' ), __( 'We can deliver straight to one of our partner facilities or any hosting provider you choose, so your miners can start hashing without coming to you first.', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bcall( 'warning', __( 'When your delivery arrives', 'minemirth' ), __( 'Check the packaging before you sign. Photograph any damage to the box and the miner before powering it on, and tell us within 48 hours so we can claim on the shipping insurance.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'Do you ship to my country?', 'minemirth' ), __( 'In most cases yes. Ask for a quote with your destination and we will confirm the service, cost and any restrictions.', 'minemirth' ) ),
				array( __( 'Can I collect my order?', 'minemirth' ), __( 'Ask us. Where collection is possible we arrange a time and check ID against the order.', 'minemirth' ) ),
				array( __( 'Are batteries or restricted items included?', 'minemirth' ), __( 'ASIC miners and power supplies ship as standard freight. Some accessories may have shipping restrictions, which we tell you about.', 'minemirth' ) ),
				array( __( 'What if tracking stops updating?', 'minemirth' ), __( 'Freight tracking sometimes pauses between hubs. If nothing changes for three business days, contact us and we will chase the carrier.', 'minemirth' ) ),
			)
		)
		. mm_bcta( __( 'Need a *shipping quote*?', 'minemirth' ), __( 'Tell us the model, quantity and destination.', 'minemirth' ), array( array( __( 'Get a quote', 'minemirth' ), '{page:contact}' ), array( __( 'Hosting instead', 'minemirth' ), '{page:hosting}' ) ), array( 'style' => 'light' ) );
	},

	'warranty' => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Warranty & support', 'minemirth' ),
				'title'   => __( 'Covered by the maker, *backed by us*', 'minemirth' ),
				'text'    => __( 'New miners carry the manufacturer’s warranty. We help you diagnose problems, file claims and keep your fleet running.', 'minemirth' ),
				'visual'  => 'orbit',
				'icon'    => 'shield-check',
				'chips'   => mm_bchips( array( array( 'award', __( 'Maker', 'minemirth' ), __( 'warranty', 'minemirth' ) ), array( 'headset', __( 'Remote', 'minemirth' ), __( 'diagnosis', 'minemirth' ) ), array( 'wrench', __( 'Repair', 'minemirth' ), __( 'service', 'minemirth' ) ) ) ),
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'Coverage', 'minemirth' ),
				'title'   => __( 'What the warranty *covers*', 'minemirth' ),
				'columns' => '2',
				'items'   => mm_bitems(
					array(
						array( 'check-circle', __( 'Usually covered', 'minemirth' ), __( '<ul><li>Hashboards, control board and power supply failing in normal use</li><li>Fans that fail within the warranty period</li><li>Manufacturing defects present on arrival</li></ul>', 'minemirth' ) ),
						array( 'x', __( 'Usually not covered', 'minemirth' ), __( '<ul><li>Damage from water, condensation, corrosion or dust build-up</li><li>Power surges, wrong voltage or poor wiring</li><li>Physical damage, opened or modified units</li><li>Units running unapproved firmware or overclocks</li></ul>', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bcall( 'info', __( 'Warranty length', 'minemirth' ), __( 'Warranty length is set by each manufacturer and varies by model and batch (for new miners it is often six to twelve months). The cover for your unit is shown on the product page and invoice; keep your invoice and serial numbers.', 'minemirth' ) )
		. mm_bs(
			'steps',
			array(
				'eyebrow' => __( 'Making a claim', 'minemirth' ),
				'title'   => __( 'Four steps to a *fix*', 'minemirth' ),
				'tone'    => 'alt',
				'items'   => array(
					array( 'message', __( 'Tell us', 'minemirth' ), __( 'Send the order number, serial number, a photo of the label and what the miner shows (status page, error codes, kernel log).', 'minemirth' ) ),
					array( 'headset', __( 'Remote diagnosis', 'minemirth' ), __( 'Many faults are settings, cables, cooling or firmware. We work through them with you first.', 'minemirth' ) ),
					array( 'truck', __( 'Ship the part or unit', 'minemirth' ), __( 'If it is a hardware fault, we tell you exactly what to send and where, packed safely.', 'minemirth' ) ),
					array( 'refresh', __( 'Repair or replace', 'minemirth' ), __( 'The manufacturer or repair centre repairs or replaces the faulty part under its warranty terms.', 'minemirth' ) ),
				),
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'Beyond the warranty', 'minemirth' ),
				'title'   => __( 'Support that *continues*', 'minemirth' ),
				'columns' => '3',
				'items'   => mm_bitems(
					array(
						array( 'sliders', __( 'Setup help', 'minemirth' ), __( 'Network, pool and firmware configuration.', 'minemirth' ), '{page:setup}', __( 'Setup service', 'minemirth' ) ),
						array( 'wrench', __( 'Repairs', 'minemirth' ), __( 'Out-of-warranty diagnosis and repair, quoted first.', 'minemirth' ), '{page:repairs}', __( 'Repair service', 'minemirth' ) ),
						array( 'book', __( 'Self-help guides', 'minemirth' ), __( 'Troubleshooting and maintenance, step by step.', 'minemirth' ), '{page:g-troubleshoot}', __( 'Troubleshooting guide', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bfaq(
			array(
				array( __( 'Will aftermarket firmware void my warranty?', 'minemirth' ), __( 'It can. Some manufacturers refuse claims on units that ran third-party firmware. If your miner is under warranty, ask us before you flash it.', 'minemirth' ) ),
				array( __( 'Who pays shipping for a warranty repair?', 'minemirth' ), __( 'This depends on the manufacturer’s terms and where the repair is done. We tell you before anything is sent.', 'minemirth' ) ),
				array( __( 'Does hosting affect the warranty?', 'minemirth' ), __( 'No. Hosted miners keep their warranty, and the facility technicians can handle the diagnosis and shipping for you.', 'minemirth' ) ),
			)
		)
		. mm_bcta( __( 'Miner not *hashing*?', 'minemirth' ), __( 'Send us the details and we’ll help you find the fault.', 'minemirth' ), array( array( __( 'Get support', 'minemirth' ), '{page:contact}' ), array( __( 'Troubleshooting guide', 'minemirth' ), '{page:g-troubleshoot}' ) ), array( 'style' => 'light' ) );
	},

	'returns'  => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Returns & refunds', 'minemirth' ),
				'title'   => __( 'Clear rules for *returns and refunds*', 'minemirth' ),
				'text'    => __( 'Mining hardware is not like clothing: it is expensive, heavy and its value moves with the market. Here is exactly how returns, dead-on-arrival claims and refunds work.', 'minemirth' ),
				'visual'  => 'none',
				'pad'     => 'sm',
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'The policy', 'minemirth' ),
				'title'   => __( 'What you can *expect*', 'minemirth' ),
				'columns' => '2',
				'items'   => mm_bitems(
					array(
						array( 'alert', __( 'Damaged or dead on arrival', 'minemirth' ), __( 'Tell us within 48 hours of delivery with photos of the packaging and the unit, and what the miner shows when powered. We arrange inspection, the insurance claim and a repair, replacement or refund as appropriate.', 'minemirth' ) ),
						array( 'x', __( 'Change of mind', 'minemirth' ), __( 'Because miner prices move with the market and units cannot be resold as new once opened, change-of-mind returns are only accepted where the law requires it or where we agree it in writing before delivery.', 'minemirth' ) ),
						array( 'package', __( 'Unopened items', 'minemirth' ), __( 'If you want to cancel an order before it ships, contact us straight away. Cancellations after allocation may carry costs, which we tell you before you decide.', 'minemirth' ) ),
						array( 'refresh', __( 'Refunds', 'minemirth' ), __( 'Approved refunds go back to the original payment method. Card and bank refunds take a few business days to appear. Crypto refunds are sent to an address you confirm, less network fees.', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bcall( 'info', __( 'Your legal rights', 'minemirth' ), __( 'Nothing on this page limits rights you have under the consumer law of your country. Business purchases may follow different rules, which we state on the quote.', 'minemirth' ) )
		. mm_bs(
			'steps',
			array(
				'eyebrow' => __( 'Start a return', 'minemirth' ),
				'title'   => __( 'How to *request a return*', 'minemirth' ),
				'tone'    => 'alt',
				'items'   => array(
					array( 'message', __( 'Contact us', 'minemirth' ), __( 'Send your order number, the serial numbers and the reason, with photos.', 'minemirth' ) ),
					array( 'check-circle', __( 'Approval', 'minemirth' ), __( 'We confirm whether the return qualifies and send instructions and the return address.', 'minemirth' ) ),
					array( 'package', __( 'Pack and ship', 'minemirth' ), __( 'Use the original packaging if possible. Unapproved returns cannot be accepted.', 'minemirth' ) ),
					array( 'wallet', __( 'Inspection and refund', 'minemirth' ), __( 'We inspect the unit and process the outcome, usually within a few business days.', 'minemirth' ) ),
				),
			)
		)
		. mm_bcta( __( 'Need to *return something*?', 'minemirth' ), __( 'Start with a message and we will guide you through it.', 'minemirth' ), array( array( __( 'Contact us', 'minemirth' ), '{page:contact}' ) ), array( 'style' => 'light' ) );
	},
);
