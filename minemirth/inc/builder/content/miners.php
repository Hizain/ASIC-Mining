<?php
/**
 * Content: miners hub, collections by coin and setup, buying guides, brands.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

/**
 * Collection page: hero, live product grid, explainer, optional tool, FAQ, CTA.
 *
 * @param array $c eyebrow, title, text, query, icon, chips, products_title, explain (split data),
 *                 features (feature data), tool, tool_title, faq, cta.
 */
$mm_collection = static function ( array $c ): string {
	$out = mm_bs(
		'hero',
		array(
			'eyebrow' => $c['eyebrow'],
			'title'   => $c['title'],
			'text'    => $c['text'],
			'buttons' => mm_bbtn( array( array( __( 'See the miners', 'minemirth' ), '#miners', 'primary', 'arrow-down' ), $c['button2'] ?? array( __( 'Compare at my power price', 'minemirth' ), '{tool:compare}', 'glass', 'bar-chart' ) ) ),
			'badges'  => $c['badges'] ?? array(),
			'visual'  => $c['visual'] ?? 'product',
			'query'   => $c['query'],
			'icon'    => $c['icon'] ?? 'cpu',
			'chips'   => $c['chips'] ?? array(),
		)
	);
	$out .= mm_bs(
		'products',
		array(
			'anchor'     => 'miners',
			'eyebrow'    => $c['products_eyebrow'] ?? __( 'Live from our stock', 'minemirth' ),
			'title'      => $c['products_title'],
			'text'       => $c['products_text'] ?? '',
			'query'      => $c['query'],
			'limit'      => $c['limit'] ?? 12,
			'show_sold'  => ! empty( $c['show_sold'] ),
			'empty'      => $c['empty'] ?? '',
			'link_label' => __( 'All miners', 'minemirth' ),
			'link_url'   => '{shop}',
		)
	);
	if ( ! empty( $c['explain'] ) ) {
		$out .= mm_bs( 'split', array_merge( array( 'tone' => 'alt', 'visual' => 'orbit', 'icon' => $c['icon'] ?? 'cpu' ), $c['explain'] ) );
	}
	if ( ! empty( $c['features'] ) ) {
		$out .= mm_bs( 'features', array_merge( array( 'columns' => '3' ), $c['features'] ) );
	}
	if ( ! empty( $c['table'] ) ) {
		$out .= mm_bs( 'table', array_merge( array( 'tone' => 'alt' ), $c['table'] ) );
	}
	if ( ! empty( $c['tool'] ) ) {
		$out .= mm_bs( 'tool', array( 'eyebrow' => __( 'Calculator', 'minemirth' ), 'title' => $c['tool_title'], 'text' => $c['tool_text'] ?? '', 'id' => $c['tool'] ) );
	}
	if ( ! empty( $c['faq'] ) ) {
		$out .= mm_bfaq( $c['faq'], '', array( 'tone' => 'alt' ) );
	}
	if ( ! empty( $c['related'] ) ) {
		$out .= mm_bs( 'links', array( 'eyebrow' => __( 'Keep exploring', 'minemirth' ), 'title' => __( 'Related *collections and guides*', 'minemirth' ), 'columns' => '3', 'items' => $c['related'] ) );
	}
	return $out . mm_bcta( $c['cta'][0], $c['cta'][1], array( array( __( 'Shop all miners', 'minemirth' ), '{shop}' ), array( __( 'Ask for a recommendation', 'minemirth' ), '{page:contact}' ) ) );
};

$mm_link = static fn( string $key, string $icon, string $title, string $text ) => array( 'icon' => $icon, 'title' => $title, 'text' => $text, 'url' => '{page:' . $key . '}' );

/**
 * Brand page.
 */
$mm_brand = static function ( array $b ) use ( $mm_collection ): string {
	return $mm_collection(
		array(
			'eyebrow'        => __( 'Brand', 'minemirth' ),
			'title'          => $b['title'],
			'text'           => $b['text'],
			'query'          => array( 'brand' => $b['match'], 'sort' => 'popular' ),
			'icon'           => 'award',
			'products_title' => $b['products_title'],
			'explain'        => array( 'eyebrow' => __( 'About the brand', 'minemirth' ), 'title' => $b['about_title'], 'text' => $b['about'], 'bullets' => $b['bullets'], 'visual' => 'checklist', 'checklist_title' => __( 'Best known for', 'minemirth' ), 'checklist' => $b['known'] ),
			'features'       => array( 'eyebrow' => __( 'Product lines', 'minemirth' ), 'title' => $b['lines_title'], 'items' => mm_bitems( $b['lines'] ), 'columns' => (string) min( 4, max( 2, count( $b['lines'] ) ) ) ),
			'faq'            => $b['faq'],
			'related'        => $b['related'],
			'cta'            => $b['cta'],
			'show_sold'      => true,
			'empty'          => sprintf( /* translators: %s: brand */ __( 'No %s models are listed right now. Ask us for current stock, pre-orders and lead times.', 'minemirth' ), $b['name'] ),
		)
	);
};

return array(

	'miners'          => static function () use ( $mm_link ) {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'ASIC miners', 'minemirth' ),
				'title'   => __( 'Find the right miner for *your power and goals*', 'minemirth' ),
				'text'    => __( 'Browse by coin, by setup or by what matters most to you. Every miner shows live profit at today’s numbers, and you can re-run it at your own electricity price.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Shop all miners', 'minemirth' ), '{shop}' ), array( __( 'Compare every model', 'minemirth' ), '{tool:compare}', 'glass', 'bar-chart' ) ) ),
				'visual'  => 'product',
				'query'   => array( 'sort' => 'popular' ),
			)
		)
		. mm_bs( 'links', array( 'eyebrow' => __( 'By coin', 'minemirth' ), 'title' => __( 'What do you want to *mine*?', 'minemirth' ), 'columns' => '4', 'items' => array( $mm_link( 'm-bitcoin', 'bitcoin', __( 'Bitcoin', 'minemirth' ), __( 'SHA-256 ASICs. The largest, most liquid mining market.', 'minemirth' ) ), $mm_link( 'm-scrypt', 'hash', __( 'Litecoin & Dogecoin', 'minemirth' ), __( 'Scrypt miners earning both coins at once.', 'minemirth' ) ), $mm_link( 'm-kaspa', 'zap-off', __( 'Kaspa', 'minemirth' ), __( 'kHeavyHash miners for a fast proof-of-work chain.', 'minemirth' ) ), $mm_link( 'm-alephium', 'cube', __( 'Alephium', 'minemirth' ), __( 'Blake3 miners, including compact home units.', 'minemirth' ) ) ) ) )
		. mm_bs( 'links', array( 'eyebrow' => __( 'By setup', 'minemirth' ), 'title' => __( 'Where will it *run*?', 'minemirth' ), 'columns' => '4', 'tone' => 'alt', 'items' => array( $mm_link( 'm-home', 'home', __( 'Home & quiet', 'minemirth' ), __( 'Low noise, normal outlet, heats a room.', 'minemirth' ) ), $mm_link( 'm-solo', 'star', __( 'Solo & open-source', 'minemirth' ), __( 'Bitaxe-style lottery miners.', 'minemirth' ) ), $mm_link( 'm-hydro', 'droplet', __( 'Hydro-cooled', 'minemirth' ), __( 'Water-cooled, dense and quiet.', 'minemirth' ) ), $mm_link( 'm-immersion', 'thermometer', __( 'Immersion-ready', 'minemirth' ), __( 'For dielectric-fluid tanks.', 'minemirth' ) ) ) ) )
		. mm_bs( 'links', array( 'eyebrow' => __( 'Buying guides', 'minemirth' ), 'title' => __( 'Ranked live from *our stock*', 'minemirth' ), 'columns' => '4', 'items' => array( $mm_link( 'm-efficient', 'gauge', __( 'Most efficient', 'minemirth' ), __( 'Lowest joules per terahash.', 'minemirth' ) ), $mm_link( 'm-value', 'tag', __( 'Best value', 'minemirth' ), __( 'Most hashrate per dollar.', 'minemirth' ) ), $mm_link( 'm-new', 'sparkles', __( 'New & pre-order', 'minemirth' ), __( 'The latest models and batches.', 'minemirth' ) ), $mm_link( 'm-parts', 'bolt', __( 'PSUs & accessories', 'minemirth' ), __( 'Power supplies, cords and spares.', 'minemirth' ) ) ) ) )
		. mm_bs( 'products', array( 'eyebrow' => __( 'Best sellers', 'minemirth' ), 'title' => __( 'Popular *right now*', 'minemirth' ), 'query' => array( 'sort' => 'popular' ), 'limit' => 8, 'tone' => 'alt' ) )
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'How to choose', 'minemirth' ),
				'title'   => __( 'Three numbers decide *almost everything*', 'minemirth' ),
				'columns' => '3',
				'items'   => mm_bitems(
					array(
						array( 'gauge', __( 'Efficiency (J/TH)', 'minemirth' ), __( 'Energy per terahash. Lower means cheaper coins and a longer profitable life, especially after the halving.', 'minemirth' ) ),
						array( 'tag', __( 'Price per TH', 'minemirth' ), __( 'What you pay for each terahash of capacity. It sets your payback time.', 'minemirth' ) ),
						array( 'bolt', __( 'Your power price', 'minemirth' ), __( 'The cost you can control. It decides which miners make sense for you.', 'minemirth' ), '{tool:breakeven}', __( 'Find your break-even', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bs( 'brands', array( 'eyebrow' => __( 'Brands', 'minemirth' ), 'title' => __( 'Shop by *manufacturer*', 'minemirth' ), 'tone' => 'alt', 'link_label' => __( 'Compare brands', 'minemirth' ), 'link_url' => '{page:brands}', 'items' => array( array( 'name' => 'Bitmain', 'text' => 'Antminer', 'url' => '{page:brand-bitmain}' ), array( 'name' => 'MicroBT', 'text' => 'Whatsminer', 'url' => '{page:brand-microbt}' ), array( 'name' => 'Canaan', 'text' => 'Avalon', 'url' => '{page:brand-canaan}' ), array( 'name' => 'Goldshell', 'text' => __( 'Compact altcoin miners', 'minemirth' ), 'url' => '{page:brand-goldshell}' ), array( 'name' => 'IceRiver', 'text' => __( 'Kaspa KS series', 'minemirth' ), 'url' => '{page:brand-iceriver}' ), array( 'name' => 'Elphapex', 'text' => __( 'Scrypt DG series', 'minemirth' ), 'url' => '{page:brand-elphapex}' ) ) ) )
		. mm_bcta( __( 'Want a *shortlist*?', 'minemirth' ), __( 'Send your power price, budget and where the miner will run. We’ll reply with two or three options and the numbers.', 'minemirth' ), array( array( __( 'Get a recommendation', 'minemirth' ), '{page:contact}' ), array( __( 'Buying guide', 'minemirth' ), '{page:g-choose}' ) ) );
	},

	'm-bitcoin'       => static function () use ( $mm_collection, $mm_link ) {
		return $mm_collection(
			array(
				'eyebrow'        => __( 'Bitcoin miners', 'minemirth' ),
				'title'          => __( 'SHA-256 miners for the *Bitcoin network*', 'minemirth' ),
				'text'           => __( 'Air-cooled, hydro and immersion ASICs from Bitmain, MicroBT and Canaan. Today one PH/s earns {hashprice} a day before power, and difficulty is {difficulty}.', 'minemirth' ),
				'query'          => array( 'algo' => 'sha256', 'sort' => 'efficiency' ),
				'icon'           => 'bitcoin',
				'badges'         => array( __( 'Sorted by efficiency', 'minemirth' ), __( 'Live profit on every model', 'minemirth' ) ),
				'products_title' => __( 'Bitcoin miners, *most efficient first*', 'minemirth' ),
				'explain'        => array(
					'eyebrow' => __( 'Choosing a Bitcoin miner', 'minemirth' ),
					'title'   => __( 'Efficiency wins *over the long run*', 'minemirth' ),
					'text'    => __( '<p>Every Bitcoin ASIC does the same job: SHA-256 hashing. What differs is how much electricity it needs per terahash (J/TH) and how much hashrate you get for your money.</p><p>Network difficulty tends to rise over time and the halving cuts the block subsidy, so revenue per TH tends to fall. Efficient miners stay profitable longest. If your power is cheap, older or higher-J/TH units can still pay back fast at a lower price.</p>', 'minemirth' ),
					'bullets' => array( __( 'Under ~$0.05/kWh: most current models are profitable; price per TH matters most', 'minemirth' ), __( '$0.05–0.08/kWh: favour modern units around 15–18 J/TH or better', 'minemirth' ), __( 'Above ~$0.08/kWh: only the most efficient, or hosting, makes sense', 'minemirth' ) ),
					'buttons' => mm_bbtn( array( array( __( 'Most efficient miners', 'minemirth' ), '{page:m-efficient}', 'ghost' ) ) ),
				),
				'tool'           => 'compare',
				'tool_title'     => __( 'Rank every Bitcoin miner at *your power price*', 'minemirth' ),
				'faq'            => array(
					array( __( 'Which Bitcoin miner is the most profitable?', 'minemirth' ), __( 'At typical power prices, the most efficient model you can afford. The comparison tool above ranks our stock by profit per day at your electricity rate.', 'minemirth' ) ),
					array( __( 'Air, hydro or immersion?', 'minemirth' ), __( 'Air-cooled miners are simplest and suit most sites. Hydro models are quieter, denser and more efficient but need a coolant loop. Immersion suits large, engineered sites. See <a href="{page:g-cooling}">cooling & ventilation</a>.', 'minemirth' ) ),
					array( __( 'What power supply do I need?', 'minemirth' ), __( 'Most current Bitcoin ASICs have a built-in PSU that needs 200–240 V (some accept up to 277 V). Plan a dedicated circuit per miner. See the <a href="{tool:power}">power & circuit planner</a>.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'g-choose', 'book', __( 'How to choose an ASIC', 'minemirth' ), __( 'The buyer’s framework.', 'minemirth' ) ), $mm_link( 'g-profit', 'trending-up', __( 'Profitability explained', 'minemirth' ), __( 'Revenue, costs and payback.', 'minemirth' ) ), $mm_link( 'm-hydro', 'droplet', __( 'Hydro-cooled miners', 'minemirth' ), __( 'Quiet and dense.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Pick your *Bitcoin miner*', 'minemirth' ), __( 'Tell us your power price and quantity for a firm quote.', 'minemirth' ) ),
			)
		);
	},

	'm-scrypt'        => static function () use ( $mm_collection, $mm_link ) {
		return $mm_collection(
			array(
				'eyebrow'        => __( 'Litecoin & Dogecoin', 'minemirth' ),
				'title'          => __( 'Scrypt miners that earn *two coins at once*', 'minemirth' ),
				'text'           => __( 'Litecoin and Dogecoin share the Scrypt algorithm and are merge-mined: the same hashrate earns LTC and DOGE together, and most pools pay both automatically.', 'minemirth' ),
				'query'          => array( 'algo' => 'scrypt', 'sort' => 'efficiency' ),
				'icon'           => 'hash',
				'button2'        => array( __( 'Altcoin calculator', 'minemirth' ), '{tool:altcoin}', 'glass', 'calculator' ),
				'products_title' => __( 'Scrypt *miners*', 'minemirth' ),
				'explain'        => array(
					'eyebrow' => __( 'How merged mining works', 'minemirth' ),
					'title'   => __( 'One hash, *two rewards*', 'minemirth' ),
					'text'    => __( '<p>Dogecoin accepts proof of work done for Litecoin blocks (auxiliary proof of work). A Scrypt pool builds work for both chains at once, so your miner earns Litecoin’s 6.25 LTC block reward share and Dogecoin’s 10,000 DOGE block reward share from the same hashing.</p><p>Litecoin produces a block about every 2.5 minutes and Dogecoin about every minute. Many pools also add other merge-mined coins.</p>', 'minemirth' ),
					'bullets' => array( __( 'Revenue = LTC earned + DOGE earned, minus pool fee', 'minemirth' ), __( 'Efficiency is measured in J/MH (joules per megahash)', 'minemirth' ), __( 'Choose a pool that pays both coins', 'minemirth' ) ),
				),
				'tool'           => 'altcoin',
				'tool_title'     => __( 'Litecoin + Dogecoin *earnings*', 'minemirth' ),
				'faq'            => array(
					array( __( 'Do I need two miners to earn LTC and DOGE?', 'minemirth' ), __( 'No. One Scrypt miner on a merged-mining pool earns both.', 'minemirth' ) ),
					array( __( 'Is Scrypt mining more profitable than Bitcoin?', 'minemirth' ), __( 'It varies with LTC and DOGE prices and network hashrate. Compare the same power budget in the <a href="{tool:altcoin}">altcoin calculator</a> and the <a href="{tool:profit}">Bitcoin calculator</a>.', 'minemirth' ) ),
					array( __( 'What power do Scrypt miners need?', 'minemirth' ), __( 'Full-size Scrypt ASICs draw about 3.3–3.5 kW and need 200–240 V circuits, like Bitcoin ASICs.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'brand-elphapex', 'award', __( 'Elphapex', 'minemirth' ), __( 'Scrypt DG series.', 'minemirth' ) ), $mm_link( 'brand-bitmain', 'award', __( 'Bitmain Antminer', 'minemirth' ), __( 'L-series Scrypt miners.', 'minemirth' ) ), $mm_link( 'g-pool', 'users', __( 'Choosing a pool', 'minemirth' ), __( 'Fees and payouts.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Mine *Litecoin and Dogecoin*', 'minemirth' ), __( 'Ask about stock, batches and hosting for Scrypt miners.', 'minemirth' ) ),
			)
		);
	},

	'm-kaspa'         => static function () use ( $mm_collection, $mm_link ) {
		return $mm_collection(
			array(
				'eyebrow'        => __( 'Kaspa miners', 'minemirth' ),
				'title'          => __( 'kHeavyHash miners for *Kaspa*', 'minemirth' ),
				'text'           => __( 'Kaspa is a fast proof-of-work network built on a blockDAG. Its kHeavyHash algorithm is mined by dedicated ASICs from compact home units to full-size machines.', 'minemirth' ),
				'query'          => array( 'algo' => 'kheavyhash', 'sort' => 'efficiency' ),
				'icon'           => 'zap-off',
				'button2'        => array( __( 'Kaspa calculator', 'minemirth' ), '{tool:altcoin}', 'glass', 'calculator' ),
				'products_title' => __( 'Kaspa *miners*', 'minemirth' ),
				'explain'        => array(
					'eyebrow' => __( 'Kaspa economics', 'minemirth' ),
					'title'   => __( 'A reward that *shrinks every month*', 'minemirth' ),
					'text'    => __( '<p>Instead of a halving every four years, Kaspa’s emission falls smoothly: the block reward drops a little each month, halving over roughly a year. That means daily KAS per terahash drifts down even if network hashrate stays flat.</p><p>Plan with that in mind: efficiency and a low purchase price matter, and payback periods should be modelled month by month.</p>', 'minemirth' ),
					'bullets' => array( __( 'Algorithm: kHeavyHash', 'minemirth' ), __( 'Efficiency measured in J/TH (or J/GH for small units)', 'minemirth' ), __( 'Emission declines monthly (chromatic halving)', 'minemirth' ) ),
				),
				'tool'           => 'altcoin',
				'tool_title'     => __( 'Kaspa *earnings*', 'minemirth' ),
				'tool_text'      => __( 'Switch the calculator to “Kaspa · KAS” to use live KAS price and network hashrate.', 'minemirth' ),
				'faq'            => array(
					array( __( 'Can I mine Kaspa with a Bitcoin miner?', 'minemirth' ), __( 'No. ASICs are built for one algorithm. Kaspa needs a kHeavyHash miner.', 'minemirth' ) ),
					array( __( 'Are there quiet Kaspa miners?', 'minemirth' ), __( 'Yes, small units from IceRiver and Goldshell draw far less power and are much quieter, though they earn less.', 'minemirth' ) ),
					array( __( 'Which pool should I use?', 'minemirth' ), __( 'Choose a reputable Kaspa pool with low fees, nearby servers and frequent payouts. See <a href="{page:g-pool}">choosing a mining pool</a>.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'brand-iceriver', 'award', __( 'IceRiver', 'minemirth' ), __( 'Kaspa specialists.', 'minemirth' ) ), $mm_link( 'brand-goldshell', 'award', __( 'Goldshell', 'minemirth' ), __( 'Compact miners.', 'minemirth' ) ), $mm_link( 'm-home', 'home', __( 'Home miners', 'minemirth' ), __( 'Quiet options.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Start mining *Kaspa*', 'minemirth' ), __( 'Ask about stock and expected delivery for kHeavyHash miners.', 'minemirth' ) ),
			)
		);
	},

	'm-alephium'      => static function () use ( $mm_collection, $mm_link ) {
		return $mm_collection(
			array(
				'eyebrow'        => __( 'Alephium miners', 'minemirth' ),
				'title'          => __( 'Blake3 miners for *Alephium*', 'minemirth' ),
				'text'           => __( 'Alephium is a sharded proof-of-work blockchain using the Blake3 algorithm. ASIC options range from small, quiet home units to full-size industrial miners.', 'minemirth' ),
				'query'          => array( 'algo' => 'blake3', 'sort' => 'efficiency' ),
				'icon'           => 'cube',
				'products_title' => __( 'Alephium *miners*', 'minemirth' ),
				'explain'        => array(
					'eyebrow' => __( 'Before you buy', 'minemirth' ),
					'title'   => __( 'A smaller market: *plan carefully*', 'minemirth' ),
					'text'    => __( '<p>Alephium is a younger, smaller network than Bitcoin. Prices and network hashrate can move fast, and new hardware generations can change the economics quickly.</p><p>Compact Blake3 miners are a popular, low-risk way to start because they use little power and run quietly at home.</p>', 'minemirth' ),
					'bullets' => array( __( 'Check the coin’s liquidity and exchanges', 'minemirth' ), __( 'Model payback with conservative prices', 'minemirth' ), __( 'Prefer efficient, recent hardware', 'minemirth' ) ),
				),
				'faq'            => array(
					array( __( 'How do I calculate Alephium earnings?', 'minemirth' ), __( 'Use a pool’s calculator or a coin calculator with the current ALPH price and network hashrate, then subtract your power cost.', 'minemirth' ) ),
					array( __( 'Can Alephium miners mine other coins?', 'minemirth' ), __( 'Only coins that use Blake3 with a compatible implementation. Check before buying for that purpose.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'brand-goldshell', 'award', __( 'Goldshell', 'minemirth' ), __( 'AL-BOX and more.', 'minemirth' ) ), $mm_link( 'm-home', 'home', __( 'Home miners', 'minemirth' ), __( 'Quiet options.', 'minemirth' ) ), $mm_link( 'g-choose', 'book', __( 'How to choose an ASIC', 'minemirth' ), __( 'The buyer’s framework.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Interested in *Alephium*?', 'minemirth' ), __( 'Ask about current models and lead times.', 'minemirth' ) ),
			)
		);
	},

	'm-home'          => static function () use ( $mm_collection, $mm_link ) {
		return $mm_collection(
			array(
				'eyebrow'        => __( 'Home & quiet miners', 'minemirth' ),
				'title'          => __( 'Mine at home *without the roar*', 'minemirth' ),
				'text'           => __( 'Compact, low-noise miners that plug into a normal outlet. Many double as space heaters, so the electricity heats your room while it earns sats.', 'minemirth' ),
				'query'          => array( 'home' => true, 'sort' => 'popular' ),
				'icon'           => 'home',
				'badges'         => array( __( 'Normal outlet', 'minemirth' ), __( 'Low noise', 'minemirth' ), __( 'Heat you can use', 'minemirth' ) ),
				'button2'        => array( __( 'Hosting vs home', 'minemirth' ), '{tool:hosting}', 'glass', 'home' ),
				'products_title' => __( 'Quiet *home miners*', 'minemirth' ),
				'explain'        => array(
					'eyebrow' => __( 'Home mining, honestly', 'minemirth' ),
					'title'   => __( 'Small miners earn *small amounts*', 'minemirth' ),
					'text'    => __( '<p>A home miner earns in proportion to its hashrate, so a quiet unit drawing a few hundred watts earns a fraction of a full-size ASIC. The win is in the heat: in cold months, the power you’d spend on a heater also mines Bitcoin.</p><p>Want serious hashrate without the noise? Host a full-size miner instead and keep a small one at home for the fun of it.</p>', 'minemirth' ),
					'bullets' => array( __( 'No electrician needed for most units', 'minemirth' ), __( 'Noise from whisper-quiet to a desk fan', 'minemirth' ), __( 'Best value when you’d heat the room anyway', 'minemirth' ) ),
					'buttons' => mm_bbtn( array( array( __( 'Mining at home guide', 'minemirth' ), '{page:g-home}', 'ghost' ) ) ),
				),
				'faq'            => array(
					array( __( 'Can I run a full-size ASIC at home?', 'minemirth' ), __( 'Technically yes, with a 240 V circuit and strong ventilation, but at 70–80 dB it is too loud for living spaces. See <a href="{page:g-noise}">reducing ASIC noise</a>.', 'minemirth' ) ),
					array( __( 'Will a home miner raise my power bill?', 'minemirth' ), __( 'Yes, by its watts × hours × your rate. A 500 W unit running all month uses about 365 kWh. If it replaces a heater, part of that cost was coming anyway.', 'minemirth' ) ),
					array( __( 'Do home miners need special internet?', 'minemirth' ), __( 'No. Mining uses very little bandwidth; a normal Wi-Fi or Ethernet connection is enough.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'm-solo', 'star', __( 'Solo miners', 'minemirth' ), __( 'Lottery-style mining.', 'minemirth' ) ), $mm_link( 'g-home', 'book', __( 'Mining at home', 'minemirth' ), __( 'Full guide.', 'minemirth' ) ), $mm_link( 'hosting', 'server', __( 'Hosting', 'minemirth' ), __( 'Full-size miners, no noise.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Find a *quiet* miner', 'minemirth' ), __( 'Tell us the room and your power price; we’ll suggest the right unit.', 'minemirth' ) ),
				'empty'          => __( 'Home miners sell out fast. Ask us about the next delivery.', 'minemirth' ),
			)
		);
	},

	'm-solo'          => static function () use ( $mm_collection, $mm_link ) {
		return $mm_collection(
			array(
				'eyebrow'        => __( 'Solo & open-source', 'minemirth' ),
				'title'          => __( 'A few watts, a shot at a *whole block*', 'minemirth' ),
				'text'           => __( 'Bitaxe and other open-source miners use a single efficient ASIC chip and a few watts. Pointed at a solo pool, they buy you a real chance at the full block reward: {fees} in fees plus the 3.125 BTC subsidy.', 'minemirth' ),
				'query'          => array( 'solo' => true, 'sort' => 'popular' ),
				'icon'           => 'star',
				'button2'        => array( __( 'Solo odds calculator', 'minemirth' ), '{tool:solo}', 'glass', 'star' ),
				'products_title' => __( 'Solo *miners*', 'minemirth' ),
				'explain'        => array(
					'eyebrow' => __( 'The honest odds', 'minemirth' ),
					'title'   => __( 'A lottery ticket that *secures the network*', 'minemirth' ),
					'text'    => __( '<p>A 1 TH/s miner contributes a tiny share of the network’s hashrate, so the chance of finding a block on any day is very small, yet blocks have been found by solo home miners. The expected value is the same as pool mining; it just arrives all at once, or never.</p><p>Solo miners are also the best way to learn: open hardware, open firmware, a web interface and a fan you can barely hear.</p>', 'minemirth' ),
					'bullets' => array( __( 'Draws roughly 15–100 W depending on model', 'minemirth' ), __( 'Runs on USB-C or a small DC supply', 'minemirth' ), __( 'Point it at a solo pool with your own address', 'minemirth' ) ),
					'buttons' => mm_bbtn( array( array( __( 'Solo mining guide', 'minemirth' ), '{page:g-solo}', 'ghost' ) ) ),
				),
				'tool'           => 'solo',
				'tool_title'     => __( 'Your odds of *finding a block*', 'minemirth' ),
				'faq'            => array(
					array( __( 'What is a Bitaxe?', 'minemirth' ), __( 'An open-source Bitcoin miner built around one ASIC chip. Its hardware and firmware designs are public, so anyone can build, repair or improve one.', 'minemirth' ) ),
					array( __( 'Can a solo miner pay for itself?', 'minemirth' ), __( 'Not reliably: on average it earns about what its hashrate would earn in a pool, which is small. Buy it for the chance at a block and for learning.', 'minemirth' ) ),
					array( __( 'Which solo pool should I use?', 'minemirth' ), __( 'Use a reputable solo pool, or your own node, and enter your own Bitcoin address as the username so any reward goes to you. See the <a href="{page:g-solo}">solo mining guide</a>.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'm-home', 'home', __( 'Home miners', 'minemirth' ), __( 'Quiet units.', 'minemirth' ) ), $mm_link( 'g-solo', 'book', __( 'Solo mining guide', 'minemirth' ), __( 'Setup and odds.', 'minemirth' ) ), $mm_link( 'g-security', 'lock', __( 'Wallet security', 'minemirth' ), __( 'Keep your reward safe.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Get your *ticket*', 'minemirth' ), __( 'Ask about solo miners in stock.', 'minemirth' ) ),
			)
		);
	},

	'm-hydro'         => static function () use ( $mm_collection, $mm_link ) {
		return $mm_collection(
			array(
				'eyebrow'        => __( 'Hydro-cooled miners', 'minemirth' ),
				'title'          => __( 'Water-cooled ASICs: *quieter, denser, more efficient*', 'minemirth' ),
				'text'           => __( 'Hydro miners replace fans with a liquid cold plate. They run near-silent, pack more hashrate into less space and deliver warm water you can reuse.', 'minemirth' ),
				'query'          => array( 'cooling' => 'hydro', 'sort' => 'efficiency' ),
				'icon'           => 'droplet',
				'products_title' => __( 'Hydro *miners*', 'minemirth' ),
				'explain'        => array(
					'eyebrow' => __( 'What you need', 'minemirth' ),
					'title'   => __( 'A hydro miner is *part of a system*', 'minemirth' ),
					'text'    => __( '<p>Each unit connects to a coolant loop with supply and return lines. A pump circulates treated coolant through the miners and a dry cooler, cooling tower or heat exchanger removes the heat.</p><p>Follow the manufacturer’s specifications for flow rate, inlet temperature, pressure and water quality for your exact model.</p>', 'minemirth' ),
					'bullets' => array( __( 'Coolant loop with pump, filter and manifolds', 'minemirth' ), __( 'Dry cooler or tower sized for the total heat load', 'minemirth' ), __( 'Treated coolant to prevent corrosion and scaling', 'minemirth' ), __( 'Usually 3-phase or high-voltage power', 'minemirth' ) ),
					'buttons' => mm_bbtn( array( array( __( 'Hydro setup guide', 'minemirth' ), '{page:g-hydro}', 'ghost' ) ) ),
				),
				'faq'            => array(
					array( __( 'Are hydro miners worth it?', 'minemirth' ), __( 'For dense sites, noise-sensitive locations or heat reuse, yes. For a single unit at home, the loop is usually more trouble than it’s worth.', 'minemirth' ) ),
					array( __( 'Can I put a hydro miner in my garage?', 'minemirth' ), __( 'Only with a complete loop and a heat rejection path outdoors. Most single-unit buyers host hydro miners instead.', 'minemirth' ) ),
					array( __( 'Can you host hydro miners?', 'minemirth' ), __( 'Where a partner facility has liquid cooling capacity, yes. Check the cooling type on each <a href="{page:hosting}">hosting plan</a>.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'g-hydro', 'book', __( 'Hydro guide', 'minemirth' ), __( 'Loops and flow rates.', 'minemirth' ) ), $mm_link( 'm-immersion', 'thermometer', __( 'Immersion-ready', 'minemirth' ), __( 'Fluid tanks.', 'minemirth' ) ), $mm_link( 'consulting', 'layers', __( 'Farm design', 'minemirth' ), __( 'Plan the system.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Planning a *hydro* deployment?', 'minemirth' ), __( 'We can help size the miners and the cooling.', 'minemirth' ) ),
			)
		);
	},

	'm-immersion'     => static function () use ( $mm_collection, $mm_link ) {
		return $mm_collection(
			array(
				'eyebrow'        => __( 'Immersion-ready', 'minemirth' ),
				'title'          => __( 'Miners built for *dielectric fluid*', 'minemirth' ),
				'text'           => __( 'In immersion cooling, miners sit in a tank of non-conductive fluid that carries heat away silently. It allows higher, steadier performance and protects hardware from dust and humidity.', 'minemirth' ),
				'query'          => array( 'cooling' => 'immersion', 'sort' => 'efficiency' ),
				'icon'           => 'thermometer',
				'products_title' => __( 'Immersion *miners*', 'minemirth' ),
				'empty'          => __( 'Many air-cooled models can be converted for immersion. Ask us which units suit your tanks.', 'minemirth' ),
				'explain'        => array(
					'eyebrow' => __( 'Conversion', 'minemirth' ),
					'title'   => __( 'Air-cooled miners *can* go in the tank', 'minemirth' ),
					'text'    => __( '<p>Most air-cooled ASICs can be immersed once the fans are removed and the firmware is set to run without fan feedback. Dedicated immersion models arrive ready, with suitable connectors and firmware profiles.</p><p>Always check the manufacturer’s position on warranty for immersed units before converting.</p>', 'minemirth' ),
					'bullets' => array( __( 'Remove fans; disable fan checks in firmware', 'minemirth' ), __( 'Use a fluid compatible with the hardware', 'minemirth' ), __( 'Keep fluid within the recommended temperature range', 'minemirth' ), __( 'Overclock only within safe chip temperatures', 'minemirth' ) ),
					'buttons' => mm_bbtn( array( array( __( 'Immersion guide', 'minemirth' ), '{page:g-immersion}', 'ghost' ) ) ),
				),
				'faq'            => array(
					array( __( 'Does immersion increase hashrate?', 'minemirth' ), __( 'It lets you run higher clocks at stable temperatures, so many operators overclock. Efficiency at those clocks is usually lower, so it pays when power is cheap.', 'minemirth' ) ),
					array( __( 'Can immersed miners go back to air?', 'minemirth' ), __( 'Yes, after cleaning and refitting fans, but residue can remain. Ex-immersion miners usually sell for less.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'g-immersion', 'book', __( 'Immersion guide', 'minemirth' ), __( 'Tanks and fluids.', 'minemirth' ) ), $mm_link( 'm-hydro', 'droplet', __( 'Hydro miners', 'minemirth' ), __( 'Liquid cold plates.', 'minemirth' ) ), $mm_link( 'g-firmware', 'sliders', __( 'Firmware & tuning', 'minemirth' ), __( 'Overclock safely.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Building an *immersion* site?', 'minemirth' ), __( 'We can recommend miners and help plan the tanks.', 'minemirth' ) ),
			)
		);
	},

	'm-efficient'     => static function () use ( $mm_collection, $mm_link ) {
		return $mm_collection(
			array(
				'eyebrow'        => __( 'Most efficient', 'minemirth' ),
				'title'          => __( 'The lowest *joules per terahash*, ranked live', 'minemirth' ),
				'text'           => __( 'Efficiency decides how cheaply you produce each bitcoin. These are the Bitcoin miners in our stock, ordered from the lowest J/TH up.', 'minemirth' ),
				'query'          => array( 'algo' => 'sha256', 'sort' => 'efficiency' ),
				'icon'           => 'gauge',
				'button2'        => array( __( 'Break-even power price', 'minemirth' ), '{tool:breakeven}', 'glass', 'bolt' ),
				'products_title' => __( 'Ranked by *efficiency*', 'minemirth' ),
				'explain'        => array(
					'eyebrow' => __( 'Why J/TH matters', 'minemirth' ),
					'title'   => __( 'Efficiency is your *margin of safety*', 'minemirth' ),
					'text'    => __( '<p>J/TH is the energy a miner uses to perform one terahash. Divide your power price by it and you know your cost per terahash. When hashprice falls, after a difficulty jump, a price drop or the halving, the most efficient miners keep earning longest.</p><p>A miner at 15 J/TH uses about 0.36 kWh per TH/s per day; at 25 J/TH it is 0.6 kWh. At $0.07/kWh that is about 2.5¢ versus 4.2¢ per TH every day.</p>', 'minemirth' ),
					'bullets' => array( __( 'Lower J/TH = lower break-even power price', 'minemirth' ), __( 'Efficient miners hold resale value longer', 'minemirth' ), __( 'Hydro models are often the most efficient', 'minemirth' ) ),
				),
				'tool'           => 'breakeven',
				'tool_title'     => __( 'Your *break-even* power price', 'minemirth' ),
				'faq'            => array(
					array( __( 'What is a good J/TH today?', 'minemirth' ), __( 'Current-generation air-cooled Bitcoin miners are roughly 13–18 J/TH; hydro models go lower. Older generations above 25 J/TH need cheap power.', 'minemirth' ) ),
					array( __( 'Is the most efficient miner always the best buy?', 'minemirth' ), __( 'Not always. If power is very cheap, a less efficient miner with a lower price per TH can pay back sooner. Compare both in the <a href="{tool:compare}">miner comparison</a>.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'm-value', 'tag', __( 'Best value', 'minemirth' ), __( 'Most TH per dollar.', 'minemirth' ) ), $mm_link( 'g-choose', 'book', __( 'How to choose', 'minemirth' ), __( 'Buyer’s framework.', 'minemirth' ) ), $mm_link( 'g-halving', 'clock', __( 'The halving', 'minemirth' ), __( 'Prepare your fleet.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Lower your *cost per coin*', 'minemirth' ), __( 'Ask for a quote on the most efficient models.', 'minemirth' ) ),
			)
		);
	},

	'm-value'         => static function () use ( $mm_collection, $mm_link ) {
		return $mm_collection(
			array(
				'eyebrow'        => __( 'Best value', 'minemirth' ),
				'title'          => __( 'The most *hashrate per dollar*', 'minemirth' ),
				'text'           => __( 'Ranked by price per terahash so your budget buys the most capacity. Best for cheap power, where hashrate counts more than efficiency.', 'minemirth' ),
				'query'          => array( 'algo' => 'sha256', 'sort' => 'value' ),
				'icon'           => 'tag',
				'button2'        => array( __( 'ROI planner', 'minemirth' ), '{tool:roi}', 'glass', 'trending-up' ),
				'products_title' => __( 'Ranked by *price per TH*', 'minemirth' ),
				'explain'        => array(
					'eyebrow' => __( 'Value vs efficiency', 'minemirth' ),
					'title'   => __( 'Cheap power changes *the maths*', 'minemirth' ),
					'text'    => __( '<p>Payback ≈ purchase price ÷ daily profit. When electricity is cheap, power is a small part of the cost and the miner that costs least per TH often pays back first, even if it is less efficient.</p><p>The trade-off: less efficient miners lose profitability sooner when hashprice falls. Check the halving date ({halving_date}) against your payback time.</p>', 'minemirth' ),
					'bullets' => array( __( 'Best for power under ~$0.05/kWh', 'minemirth' ), __( 'Check payback against the halving', 'minemirth' ), __( 'Factor in resale value at the end', 'minemirth' ) ),
				),
				'tool'           => 'roi',
				'tool_title'     => __( 'Payback in *bear, base and bull* scenarios', 'minemirth' ),
				'faq'            => array(
					array( __( 'Why do some miners cost so much less per TH?', 'minemirth' ), __( 'Usually because they are an older generation or less efficient. They make sense where power is cheap.', 'minemirth' ) ),
					array( __( 'Should I buy used for value?', 'minemirth' ), __( 'Used miners can be great value if tested properly. See <a href="{page:g-used}">buying used ASIC miners</a>.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'm-efficient', 'gauge', __( 'Most efficient', 'minemirth' ), __( 'Lowest J/TH.', 'minemirth' ) ), $mm_link( 'g-used', 'refresh', __( 'Buying used', 'minemirth' ), __( 'Inspect and price.', 'minemirth' ) ), $mm_link( 'g-profit', 'trending-up', __( 'Profitability', 'minemirth' ), __( 'Payback explained.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Stretch your *budget*', 'minemirth' ), __( 'Tell us your budget and power price for the best-value fleet.', 'minemirth' ) ),
			)
		);
	},

	'm-new'           => static function () use ( $mm_collection, $mm_link ) {
		return $mm_collection(
			array(
				'eyebrow'        => __( 'New & pre-order', 'minemirth' ),
				'title'          => __( 'The latest miners and *upcoming batches*', 'minemirth' ),
				'text'           => __( 'Newest models first, including pre-orders. New generations usually bring a big step in efficiency, so they are worth a look before you buy.', 'minemirth' ),
				'query'          => array( 'sort' => 'newest' ),
				'icon'           => 'sparkles',
				'show_sold'      => false,
				'products_title' => __( 'Newest *arrivals*', 'minemirth' ),
				'explain'        => array(
					'eyebrow' => __( 'How pre-orders work', 'minemirth' ),
					'title'   => __( 'Reserve from a batch, *know the terms*', 'minemirth' ),
					'text'    => __( '<p>Manufacturers produce new models in batches with expected shipping months. A pre-order reserves units from a batch. Before you pay, we state the batch, the expected shipping window, any deposit and when the balance is due.</p><p>Delivery windows can move with manufacturing and freight. We keep you updated and tell you straight away if a batch slips.</p>', 'minemirth' ),
					'bullets' => array( __( 'Batch and shipping window stated up front', 'minemirth' ), __( 'Deposit and balance terms in writing', 'minemirth' ), __( 'Updates as the batch progresses', 'minemirth' ) ),
				),
				'faq'            => array(
					array( __( 'Is it better to wait for the next generation?', 'minemirth' ), __( 'If it is months away, a miner hashing today earns while you wait. Compare the payback of both with the <a href="{tool:roi}">ROI planner</a>.', 'minemirth' ) ),
					array( __( 'Can I cancel a pre-order?', 'minemirth' ), __( 'The cancellation terms are on your quote. Deposits may be non-refundable once units are allocated by the manufacturer.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'm-efficient', 'gauge', __( 'Most efficient', 'minemirth' ), __( 'Ranked by J/TH.', 'minemirth' ) ), $mm_link( 'tradein', 'refresh', __( 'Trade-in', 'minemirth' ), __( 'Fund your upgrade.', 'minemirth' ) ), $mm_link( 'wholesale', 'package', __( 'Bulk orders', 'minemirth' ), __( 'Batch allocations.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Reserve the *next batch*', 'minemirth' ), __( 'Ask about allocations and shipping windows.', 'minemirth' ) ),
			)
		);
	},

	'm-parts'         => static function () use ( $mm_collection, $mm_link ) {
		return $mm_collection(
			array(
				'eyebrow'        => __( 'PSUs & accessories', 'minemirth' ),
				'title'          => __( 'Power supplies, cords, fans and *spares*', 'minemirth' ),
				'text'           => __( 'Keep miners running with the right power supply, the right power cords and spare fans, control boards and cables on the shelf.', 'minemirth' ),
				'query'          => array( 'parts' => true, 'parts_only' => true, 'sort' => 'popular' ),
				'icon'           => 'bolt',
				'visual'         => 'orbit',
				'chips'          => mm_bchips( array( array( 'bolt', __( 'PSUs', 'minemirth' ), __( 'matched to models', 'minemirth' ) ), array( 'fan', __( 'Fans', 'minemirth' ), __( 'and shrouds', 'minemirth' ) ), array( 'link', __( 'Cords', 'minemirth' ), __( 'C13 / C19', 'minemirth' ) ) ) ),
				'button2'        => array( __( 'Power planner', 'minemirth' ), '{tool:power}', 'glass', 'sliders' ),
				'products_title' => __( 'Parts & *accessories*', 'minemirth' ),
				'empty'          => __( 'Need a specific PSU, fan or board? Tell us the miner model and we’ll source it.', 'minemirth' ),
				'explain'        => array(
					'eyebrow' => __( 'Get the power right', 'minemirth' ),
					'title'   => __( 'Cords and plugs *matter*', 'minemirth' ),
					'text'    => __( '<p>ASIC power supplies typically use C13 or C19 inlets. The cord must be rated for the current: a miner drawing 14–16 A at 240 V needs a heavy-gauge cord rated for at least that, ideally more. Undersized cords and cheap splitters cause heat and fires.</p><p>Spare fans and a spare PSU are the cheapest insurance for a fleet: they are the parts that fail most.</p>', 'minemirth' ),
					'bullets' => array( __( 'Match the cord to the inlet and current', 'minemirth' ), __( 'Never daisy-chain power strips', 'minemirth' ), __( 'Keep one spare fan per few miners', 'minemirth' ) ),
					'buttons' => mm_bbtn( array( array( __( 'Power & electrical guide', 'minemirth' ), '{page:g-power}', 'ghost' ) ) ),
				),
				'related'        => array( $mm_link( 'g-power', 'bolt', __( 'Electrical guide', 'minemirth' ), __( 'Circuits and cords.', 'minemirth' ) ), $mm_link( 'repairs', 'wrench', __( 'Repairs', 'minemirth' ), __( 'PSU and board repair.', 'minemirth' ) ), $mm_link( 'g-maintenance', 'check-circle', __( 'Maintenance checklist', 'minemirth' ), __( 'What fails and when.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Need a *specific part*?', 'minemirth' ), __( 'Send the model and part and we’ll check stock.', 'minemirth' ) ),
			)
		);
	},

	'brands'          => static function () use ( $mm_link ) {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Brands', 'minemirth' ),
				'title'   => __( 'Every major ASIC *manufacturer*, compared', 'minemirth' ),
				'text'    => __( 'Each brand has strengths: Bitmain’s range, MicroBT’s durability, Canaan’s home units, and the altcoin specialists. Here is how they differ.', 'minemirth' ),
				'visual'  => 'orbit',
				'icon'    => 'award',
			)
		)
		. mm_bs( 'brands', array( 'title' => __( 'Choose a *brand*', 'minemirth' ), 'items' => array( array( 'name' => 'Bitmain', 'text' => __( 'Antminer: Bitcoin, Scrypt, Kaspa, Alephium', 'minemirth' ), 'url' => '{page:brand-bitmain}' ), array( 'name' => 'MicroBT', 'text' => __( 'Whatsminer: Bitcoin, air and hydro', 'minemirth' ), 'url' => '{page:brand-microbt}' ), array( 'name' => 'Canaan', 'text' => __( 'Avalon: Bitcoin, industrial and home', 'minemirth' ), 'url' => '{page:brand-canaan}' ), array( 'name' => 'Goldshell', 'text' => __( 'Compact altcoin miners', 'minemirth' ), 'url' => '{page:brand-goldshell}' ), array( 'name' => 'IceRiver', 'text' => __( 'Kaspa KS series', 'minemirth' ), 'url' => '{page:brand-iceriver}' ), array( 'name' => 'Elphapex', 'text' => __( 'Scrypt DG series', 'minemirth' ), 'url' => '{page:brand-elphapex}' ) ) ) )
		. mm_bs(
			'table',
			array(
				'eyebrow' => __( 'At a glance', 'minemirth' ),
				'title'   => __( 'Brands *side by side*', 'minemirth' ),
				'columns' => array( __( 'Brand', 'minemirth' ), __( 'Main coins', 'minemirth' ), __( 'Known for', 'minemirth' ), __( 'Best for', 'minemirth' ) ),
				'rows'    => array(
					__( 'Bitmain (Antminer) | Bitcoin, LTC/DOGE, Kaspa, Alephium | The widest range and largest installed base | Almost any operation', 'minemirth' ),
					__( 'MicroBT (Whatsminer) | Bitcoin | Robust air-cooled designs and stable fleets | Large farms and hosting', 'minemirth' ),
					__( 'Canaan (Avalon) | Bitcoin | Industrial miners and home units that double as heaters | Home miners and mixed fleets', 'minemirth' ),
					__( 'Goldshell | Alephium, Kaspa, Scrypt and others | Compact, quiet, low-power boxes | Home and hobby mining', 'minemirth' ),
					__( 'IceRiver | Kaspa | Dedicated kHeavyHash miners from KS0 to KS5 | Kaspa miners of every size', 'minemirth' ),
					__( 'Elphapex | LTC/DOGE | Scrypt miners in the DG series | Merged Litecoin and Dogecoin mining', 'minemirth' ),
				),
				'tone'    => 'alt',
			)
		)
		. mm_bs( 'links', array( 'eyebrow' => __( 'Still deciding?', 'minemirth' ), 'title' => __( 'Pick by *what you need*', 'minemirth' ), 'columns' => '3', 'items' => array( $mm_link( 'm-efficient', 'gauge', __( 'Most efficient', 'minemirth' ), __( 'Any brand, ranked by J/TH.', 'minemirth' ) ), $mm_link( 'm-value', 'tag', __( 'Best value', 'minemirth' ), __( 'Any brand, ranked by $/TH.', 'minemirth' ) ), $mm_link( 'g-choose', 'book', __( 'How to choose', 'minemirth' ), __( 'A full buying framework.', 'minemirth' ) ) ) ) )
		. mm_bcta( __( 'Brand-neutral *advice*', 'minemirth' ), __( 'We stock several brands, so we recommend what fits your site, not what we must clear.', 'minemirth' ), array( array( __( 'Ask us', 'minemirth' ), '{page:contact}' ), array( __( 'Compare miners', 'minemirth' ), '{tool:compare}' ) ) );
	},

	'brand-bitmain'   => static function () use ( $mm_brand, $mm_link ) {
		return $mm_brand(
			array(
				'name'           => 'Bitmain',
				'match'          => 'Bitmain|Antminer',
				'title'          => __( 'Bitmain *Antminer*', 'minemirth' ),
				'text'           => __( 'Bitmain’s Antminer line is the most widely deployed family of ASIC miners in the world, covering Bitcoin, Litecoin/Dogecoin, Kaspa and Alephium.', 'minemirth' ),
				'products_title' => __( 'Antminer *models*', 'minemirth' ),
				'about_title'    => __( 'The industry’s *broadest* line-up', 'minemirth' ),
				'about'          => __( '<p>Founded in 2013, Bitmain designs its own mining chips and builds miners for several algorithms. The S-series is its Bitcoin line, with air-cooled, hydro (Hyd) and immersion variants; the L-series mines Scrypt; KS models mine Kaspa; and AL models mine Alephium.</p><p>Because Antminers are so common, parts, firmware options, repair knowledge and resale buyers are easy to find.</p>', 'minemirth' ),
				'bullets'        => array( __( 'Bitcoin: S21 family (air, Hyd, immersion)', 'minemirth' ), __( 'Scrypt: L-series for LTC/DOGE', 'minemirth' ), __( 'Kaspa: KS-series; Alephium: AL-series', 'minemirth' ) ),
				'known'          => array( __( 'Huge installed base and resale market', 'minemirth' ), __( 'Air, hydro and immersion versions', 'minemirth' ), __( 'Wide firmware and repair support', 'minemirth' ) ),
				'lines_title'    => __( 'Antminer *families*', 'minemirth' ),
				'lines'          => array(
					array( 'bitcoin', __( 'S-series', 'minemirth' ), __( 'SHA-256 Bitcoin miners. “Pro”, “XP” and “Hyd” variants trade price, efficiency and cooling type.', 'minemirth' ), '{page:m-bitcoin}', __( 'Bitcoin miners', 'minemirth' ) ),
					array( 'hash', __( 'L-series', 'minemirth' ), __( 'Scrypt miners for merged Litecoin and Dogecoin mining.', 'minemirth' ), '{page:m-scrypt}', __( 'Scrypt miners', 'minemirth' ) ),
					array( 'zap-off', __( 'KS-series', 'minemirth' ), __( 'kHeavyHash miners for Kaspa.', 'minemirth' ), '{page:m-kaspa}', __( 'Kaspa miners', 'minemirth' ) ),
					array( 'cube', __( 'AL-series', 'minemirth' ), __( 'Blake3 miners for Alephium.', 'minemirth' ), '{page:m-alephium}', __( 'Alephium miners', 'minemirth' ) ),
				),
				'faq'            => array(
					array( __( 'What do Antminer model names mean?', 'minemirth' ), __( 'The letter is the family (S for Bitcoin, L for Scrypt, KS for Kaspa, AL for Alephium), the number is the generation, and suffixes such as Pro, XP and Hyd mark higher performance or hydro cooling. The number at the end is usually the hashrate.', 'minemirth' ) ),
					array( __( 'What warranty do Antminers have?', 'minemirth' ), __( 'Bitmain sets warranty terms by model and batch. The cover for each unit is shown on its product page. See <a href="{page:warranty}">warranty & support</a>.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'brand-microbt', 'award', __( 'MicroBT Whatsminer', 'minemirth' ), __( 'The main alternative.', 'minemirth' ) ), $mm_link( 'm-efficient', 'gauge', __( 'Most efficient', 'minemirth' ), __( 'Ranked by J/TH.', 'minemirth' ) ), $mm_link( 'g-firmware', 'sliders', __( 'Firmware & tuning', 'minemirth' ), __( 'Get more from an Antminer.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Looking for an *Antminer*?', 'minemirth' ), __( 'Ask about stock, batches and pricing for any model.', 'minemirth' ) ),
			)
		);
	},

	'brand-microbt'   => static function () use ( $mm_brand, $mm_link ) {
		return $mm_brand(
			array(
				'name'           => 'MicroBT',
				'match'          => 'MicroBT|Whatsminer',
				'title'          => __( 'MicroBT *Whatsminer*', 'minemirth' ),
				'text'           => __( 'MicroBT’s Whatsminer Bitcoin miners are known for rugged construction and steady performance in large fleets, in air-cooled, hydro and immersion versions.', 'minemirth' ),
				'products_title' => __( 'Whatsminer *models*', 'minemirth' ),
				'about_title'    => __( 'Built for *fleets*', 'minemirth' ),
				'about'          => __( '<p>Founded in 2016 in Shenzhen, MicroBT focuses on SHA-256 Bitcoin miners. Whatsminers are a staple of large mining farms, where reliability across thousands of units matters as much as headline efficiency.</p><p>The M-series spans air-cooled models and hydro (“Hydro”) and immersion versions for high-density sites.</p>', 'minemirth' ),
				'bullets'        => array( __( 'Bitcoin only: SHA-256', 'minemirth' ), __( 'Air, hydro and immersion variants', 'minemirth' ), __( 'Popular with large farms and hosting sites', 'minemirth' ) ),
				'known'          => array( __( 'Durable, fleet-proven hardware', 'minemirth' ), __( 'Strong airflow design', 'minemirth' ), __( 'Consistent hashrate', 'minemirth' ) ),
				'lines_title'    => __( 'Whatsminer *ranges*', 'minemirth' ),
				'lines'          => array(
					array( 'fan', __( 'Air-cooled M-series', 'minemirth' ), __( 'Standard air-cooled Bitcoin miners for most sites.', 'minemirth' ), '{page:m-bitcoin}', __( 'Bitcoin miners', 'minemirth' ) ),
					array( 'droplet', __( 'Hydro models', 'minemirth' ), __( 'Liquid-cooled units for dense, quiet deployments.', 'minemirth' ), '{page:m-hydro}', __( 'Hydro miners', 'minemirth' ) ),
					array( 'thermometer', __( 'Immersion models', 'minemirth' ), __( 'Designed to run in dielectric fluid.', 'minemirth' ), '{page:m-immersion}', __( 'Immersion miners', 'minemirth' ) ),
				),
				'faq'            => array(
					array( __( 'Whatsminer or Antminer?', 'minemirth' ), __( 'Both make excellent Bitcoin miners. Compare specific models on efficiency, price per TH and availability in the <a href="{tool:compare}">miner comparison</a>.', 'minemirth' ) ),
					array( __( 'Do Whatsminers support aftermarket firmware?', 'minemirth' ), __( 'Options are more limited than for Antminers. Many operators run stock firmware with its built-in power modes.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'brand-bitmain', 'award', __( 'Bitmain Antminer', 'minemirth' ), __( 'The main alternative.', 'minemirth' ) ), $mm_link( 'm-hydro', 'droplet', __( 'Hydro miners', 'minemirth' ), __( 'Liquid cooling.', 'minemirth' ) ), $mm_link( 'hosting', 'server', __( 'Hosting', 'minemirth' ), __( 'Run them professionally.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Building a *Whatsminer* fleet?', 'minemirth' ), __( 'Ask about volume pricing and hosting.', 'minemirth' ) ),
			)
		);
	},

	'brand-canaan'    => static function () use ( $mm_brand, $mm_link ) {
		return $mm_brand(
			array(
				'name'           => 'Canaan',
				'match'          => 'Canaan|Avalon',
				'title'          => __( 'Canaan *Avalon*', 'minemirth' ),
				'text'           => __( 'Canaan built one of the first Bitcoin ASICs and today makes both industrial Avalon miners and quiet home units designed to heat a room while they mine.', 'minemirth' ),
				'products_title' => __( 'Avalon *models*', 'minemirth' ),
				'about_title'    => __( 'From the first ASIC to *home heaters*', 'minemirth' ),
				'about'          => __( '<p>Canaan, based in China and listed in the United States, shipped one of the first commercial Bitcoin ASIC miners in 2013. Its Avalon range covers full-size Bitcoin miners for farms and a home line of compact, quiet miners that run on a normal outlet.</p><p>The home units are a simple, safe way to mine Bitcoin at home and put the waste heat to use.</p>', 'minemirth' ),
				'bullets'        => array( __( 'Industrial Avalon A-series', 'minemirth' ), __( 'Home miners that double as heaters', 'minemirth' ), __( 'App-based control on home units', 'minemirth' ) ),
				'known'          => array( __( 'Pioneer of ASIC mining', 'minemirth' ), __( 'Quiet home Bitcoin miners', 'minemirth' ), __( 'Good value industrial units', 'minemirth' ) ),
				'lines_title'    => __( 'Avalon *ranges*', 'minemirth' ),
				'lines'          => array(
					array( 'server', __( 'Industrial A-series', 'minemirth' ), __( 'Full-size air-cooled Bitcoin miners.', 'minemirth' ), '{page:m-bitcoin}', __( 'Bitcoin miners', 'minemirth' ) ),
					array( 'home', __( 'Home miners', 'minemirth' ), __( 'Compact, quiet units for living spaces, some designed as heaters.', 'minemirth' ), '{page:m-home}', __( 'Home miners', 'minemirth' ) ),
				),
				'faq'            => array(
					array( __( 'Are Avalon home miners profitable?', 'minemirth' ), __( 'They earn modest amounts because of their low hashrate. The value comes from heat you would otherwise pay for. See <a href="{page:g-home}">mining at home</a>.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'm-home', 'home', __( 'Home miners', 'minemirth' ), __( 'Quiet units.', 'minemirth' ) ), $mm_link( 'brand-bitmain', 'award', __( 'Bitmain', 'minemirth' ), __( 'Antminer.', 'minemirth' ) ), $mm_link( 'g-noise', 'zap-off', __( 'Reducing noise', 'minemirth' ), __( 'What works.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Interested in *Avalon*?', 'minemirth' ), __( 'Ask about industrial and home models.', 'minemirth' ) ),
			)
		);
	},

	'brand-goldshell' => static function () use ( $mm_brand, $mm_link ) {
		return $mm_brand(
			array(
				'name'           => 'Goldshell',
				'match'          => 'Goldshell',
				'title'          => __( '*Goldshell* compact miners', 'minemirth' ),
				'text'           => __( 'Goldshell is known for compact, quiet, low-power ASIC boxes for altcoins, a favourite with home and hobby miners.', 'minemirth' ),
				'products_title' => __( 'Goldshell *models*', 'minemirth' ),
				'about_title'    => __( 'Small boxes, *many coins*', 'minemirth' ),
				'about'          => __( '<p>Goldshell builds desk-sized miners for algorithms such as Blake3 (Alephium), kHeavyHash (Kaspa) and Scrypt (Litecoin/Dogecoin), alongside larger models. Their small units draw a few hundred watts or less and run on a standard outlet.</p><p>They are an easy way to try mining an altcoin without an electrician or a noisy room.</p>', 'minemirth' ),
				'bullets'        => array( __( 'Box and mini models for home use', 'minemirth' ), __( 'Multiple algorithms across the range', 'minemirth' ), __( 'Plug-and-play setup', 'minemirth' ) ),
				'known'          => array( __( 'Compact, quiet design', 'minemirth' ), __( 'Low power draw', 'minemirth' ), __( 'Altcoin specialists', 'minemirth' ) ),
				'lines_title'    => __( 'Popular *uses*', 'minemirth' ),
				'lines'          => array(
					array( 'cube', __( 'Alephium (Blake3)', 'minemirth' ), __( 'AL-series boxes for ALPH.', 'minemirth' ), '{page:m-alephium}', __( 'Alephium miners', 'minemirth' ) ),
					array( 'zap-off', __( 'Kaspa (kHeavyHash)', 'minemirth' ), __( 'Compact Kaspa units.', 'minemirth' ), '{page:m-kaspa}', __( 'Kaspa miners', 'minemirth' ) ),
					array( 'hash', __( 'Scrypt', 'minemirth' ), __( 'Small LTC/DOGE miners.', 'minemirth' ), '{page:m-scrypt}', __( 'Scrypt miners', 'minemirth' ) ),
				),
				'faq'            => array(
					array( __( 'Can I switch a Goldshell miner between coins?', 'minemirth' ), __( 'Only between coins using the same algorithm, unless the model is specifically multi-algorithm. Check the model’s supported algorithms.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'm-home', 'home', __( 'Home miners', 'minemirth' ), __( 'Quiet units.', 'minemirth' ) ), $mm_link( 'm-alephium', 'cube', __( 'Alephium miners', 'minemirth' ), __( 'Blake3.', 'minemirth' ) ), $mm_link( 'm-kaspa', 'zap-off', __( 'Kaspa miners', 'minemirth' ), __( 'kHeavyHash.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Try *altcoin* mining at home', 'minemirth' ), __( 'Ask which Goldshell model suits you.', 'minemirth' ) ),
			)
		);
	},

	'brand-iceriver'  => static function () use ( $mm_brand, $mm_link ) {
		return $mm_brand(
			array(
				'name'           => 'IceRiver',
				'match'          => 'IceRiver|Ice River',
				'title'          => __( '*IceRiver* Kaspa miners', 'minemirth' ),
				'text'           => __( 'IceRiver specialises in kHeavyHash miners for Kaspa, from the small KS0 for home use to full-size machines.', 'minemirth' ),
				'products_title' => __( 'IceRiver *models*', 'minemirth' ),
				'about_title'    => __( 'Kaspa, *and only Kaspa*', 'minemirth' ),
				'about'          => __( '<p>IceRiver’s KS series was among the first dedicated Kaspa ASICs. The range runs from compact, quiet units drawing around a hundred watts to full-size machines drawing several kilowatts.</p><p>Because Kaspa’s emission falls every month, efficiency and purchase price both matter when comparing models.</p>', 'minemirth' ),
				'bullets'        => array( __( 'KS0 class: compact and quiet', 'minemirth' ), __( 'Mid-size KS units for small setups', 'minemirth' ), __( 'Full-size KS machines for farms', 'minemirth' ) ),
				'known'          => array( __( 'Dedicated Kaspa hardware', 'minemirth' ), __( 'Options from home to farm', 'minemirth' ), __( 'Simple web interface', 'minemirth' ) ),
				'lines_title'    => __( 'The *KS range*', 'minemirth' ),
				'lines'          => array(
					array( 'home', __( 'Compact', 'minemirth' ), __( 'Low-power units for home mining.', 'minemirth' ), '{page:m-home}', __( 'Home miners', 'minemirth' ) ),
					array( 'server', __( 'Full-size', 'minemirth' ), __( 'Multi-kilowatt miners for farms and hosting.', 'minemirth' ), '{page:m-kaspa}', __( 'Kaspa miners', 'minemirth' ) ),
				),
				'faq'            => array(
					array( __( 'How much does an IceRiver miner earn?', 'minemirth' ), __( 'Use the <a href="{tool:altcoin}">altcoin calculator</a> in Kaspa mode with the model’s hashrate and your power price.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'm-kaspa', 'zap-off', __( 'Kaspa miners', 'minemirth' ), __( 'All models.', 'minemirth' ) ), $mm_link( 'brand-goldshell', 'award', __( 'Goldshell', 'minemirth' ), __( 'Compact miners.', 'minemirth' ) ), $mm_link( 'g-pool', 'users', __( 'Choosing a pool', 'minemirth' ), __( 'Fees and payouts.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Mine *Kaspa* with IceRiver', 'minemirth' ), __( 'Ask about stock and lead times.', 'minemirth' ) ),
			)
		);
	},

	'brand-elphapex'  => static function () use ( $mm_brand, $mm_link ) {
		return $mm_brand(
			array(
				'name'           => 'Elphapex',
				'match'          => 'Elphapex',
				'title'          => __( '*Elphapex* Scrypt miners', 'minemirth' ),
				'text'           => __( 'Elphapex builds Scrypt ASICs in the DG series for merged Litecoin and Dogecoin mining.', 'minemirth' ),
				'products_title' => __( 'Elphapex *models*', 'minemirth' ),
				'about_title'    => __( 'A newer name in *Scrypt*', 'minemirth' ),
				'about'          => __( '<p>Elphapex entered the Scrypt market with the DG series, giving Litecoin and Dogecoin miners another option alongside Bitmain’s L-series. Models range from full-size industrial units to smaller home-oriented versions.</p><p>As with any Scrypt miner, earnings combine LTC and DOGE from the same hashrate.</p>', 'minemirth' ),
				'bullets'        => array( __( 'DG-series Scrypt miners', 'minemirth' ), __( 'Merged LTC + DOGE mining', 'minemirth' ), __( 'Industrial and home-oriented models', 'minemirth' ) ),
				'known'          => array( __( 'Competitive Scrypt hashrate', 'minemirth' ), __( 'An alternative to the L-series', 'minemirth' ), __( 'Home-friendly variants', 'minemirth' ) ),
				'lines_title'    => __( 'The *DG range*', 'minemirth' ),
				'lines'          => array(
					array( 'server', __( 'Industrial DG units', 'minemirth' ), __( 'Full-size Scrypt miners.', 'minemirth' ), '{page:m-scrypt}', __( 'Scrypt miners', 'minemirth' ) ),
					array( 'home', __( 'Home versions', 'minemirth' ), __( 'Smaller, quieter Scrypt miners.', 'minemirth' ), '{page:m-home}', __( 'Home miners', 'minemirth' ) ),
				),
				'faq'            => array(
					array( __( 'Elphapex or Antminer L-series?', 'minemirth' ), __( 'Compare hashrate, power and price per MH/s for the models available, plus warranty and support. The <a href="{tool:altcoin}">altcoin calculator</a> shows profit for each.', 'minemirth' ) ),
				),
				'related'        => array( $mm_link( 'm-scrypt', 'hash', __( 'Scrypt miners', 'minemirth' ), __( 'All models.', 'minemirth' ) ), $mm_link( 'brand-bitmain', 'award', __( 'Bitmain', 'minemirth' ), __( 'L-series.', 'minemirth' ) ), $mm_link( 'g-payouts', 'wallet', __( 'Pool payouts', 'minemirth' ), __( 'PPS vs PPLNS.', 'minemirth' ) ) ),
				'cta'            => array( __( 'Mine *LTC and DOGE*', 'minemirth' ), __( 'Ask about Elphapex availability.', 'minemirth' ) ),
			)
		);
	},
);
