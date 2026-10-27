<?php
/**
 * Content: "Setup & environment" guides.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

return array(

	'g-power'     => static function () {
		return mm_bp( __( 'A full-size ASIC miner draws as much power as an electric oven, all day, every day. Getting the electrics right is the difference between a safe, profitable setup and a melted plug. This guide covers voltage, current, circuits, cords and the rules electricians use.', 'minemirth' ) )
		. mm_bcall( 'warning', __( 'Safety first', 'minemirth' ), __( 'Electrical work must be designed and installed by a licensed electrician to your local code. Use this guide to plan and to ask the right questions, not to do the wiring yourself.', 'minemirth' ) )
		. mm_bh( __( 'Watts, volts and amps', 'minemirth' ) )
		. mm_bp( __( 'Power (watts) = voltage × current (amps) × power factor. ASIC power supplies have a power factor close to 1, so a 3,500 W miner draws about 14.6 A at 240 V, but about 29 A at 120 V. That is why full-size miners run on 200–240 V: half the current means thinner cables, smaller breakers and less heat in the wiring.', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Most current ASIC PSUs accept roughly 200–240 V; some industrial models accept up to 277 V or 380–415 V three-phase.', 'minemirth' ),
				__( 'Small home and solo miners run on normal 100–240 V outlets.', 'minemirth' ),
				__( 'Check the label on the PSU for its exact input range before you plug in.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'The 80% rule for continuous loads', 'minemirth' ) )
		. mm_bp( __( 'Miners run continuously, so in many codes (such as the US NEC) a circuit should carry no more than 80% of its breaker rating. A 20 A circuit carries 16 A of miners; a 30 A circuit, 24 A. At 240 V, one 3.5 kW miner (14.6 A) fits on a 20 A circuit; two fit on a 40 A circuit.', 'minemirth' ) )
		. mm_bs( 'tool', array( 'title' => __( 'Plan your *circuits*', 'minemirth' ), 'id' => 'power' ) )
		. mm_bh( __( 'Cords, plugs and PDUs', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Match the inlet.</strong> ASIC PSUs commonly use C13 (up to about 10–15 A) or C19 (up to about 16–20 A) inlets. Use a C19 cord for high-current units.', 'minemirth' ),
				__( '<strong>Rate the cord for the current.</strong> Use heavy-gauge cords rated above the miner’s draw, as short as practical.', 'minemirth' ),
				__( '<strong>No household power strips or extension reels.</strong> They are not rated for continuous high loads and are a common cause of fires.', 'minemirth' ),
				__( '<strong>Use proper PDUs</strong> (power distribution units) rated for the total load in multi-miner setups.', 'minemirth' ),
				__( '<strong>Check connections</strong> for heat with an infrared thermometer after the first hours and regularly after that.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Single-phase or three-phase?', 'minemirth' ) )
		. mm_bp( __( 'Homes usually have single-phase supply. Larger sites use three-phase, which delivers more power over the same cables. Miners on three-phase are connected phase-to-phase or phase-to-neutral depending on the voltage, and the load should be balanced evenly across the three phases.', 'minemirth' ) )
		. mm_bh( __( 'How much capacity do you have?', 'minemirth' ) )
		. mm_bp( __( 'A typical house service might be 100–200 A at 240 V, shared with everything else in the home. One or two full-size miners can fit; ten will not. Ask an electrician to calculate your spare capacity before you buy, and check your supply contract for limits.', 'minemirth' ) )
		. mm_bcall( 'success', __( 'Checklist before switching on', 'minemirth' ), __( 'Dedicated circuit sized with the 80% rule · correct voltage for the PSU · cord rated for the current · no power strips · earth/ground connected · connections checked for heat after an hour.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'Can I plug a miner into a dryer or EV outlet?', 'minemirth' ), __( 'Those outlets are 240 V, but they may already be in use, the circuit may be shared, and the plug type will need a proper adapter cord. Ask an electrician.', 'minemirth' ) ),
				array( __( 'Do miners need a UPS?', 'minemirth' ), __( 'No. Miners restart automatically after power returns. A small UPS for your router keeps monitoring online.', 'minemirth' ) ),
				array( __( 'What about power surges?', 'minemirth' ), __( 'Surge protection at the panel is a good idea for sites with unstable supply.', 'minemirth' ) ),
			)
		);
	},

	'g-cooling'   => static function () {
		return mm_bp( __( 'Every watt a miner uses becomes heat. A single 3.5 kW miner is a 3.5 kW heater, and ten of them are a furnace. Good cooling keeps chips at safe temperatures, keeps hashrate steady and stretches hardware life.', 'minemirth' ) )
		. mm_bh( __( 'How much heat?', 'minemirth' ) )
		. mm_bl(
			array(
				__( '1 W = 3.412 BTU per hour. A 3,500 W miner produces about 11,900 BTU/h.', 'minemirth' ),
				__( '12,000 BTU/h = 1 ton of cooling. One full-size miner is roughly one ton of air conditioning, if you were to cool it with AC.', 'minemirth' ),
				__( 'Airflow needed (CFM) ≈ BTU/h ÷ (1.08 × temperature rise in °F).', 'minemirth' ),
			)
		)
		. mm_bp( __( 'To keep exhaust within 20 °F (about 11 °C) of intake, one 3.5 kW miner needs roughly 550 CFM of fresh air. The miner’s own fans move the air through it; your job is to supply cool air and remove the hot air.', 'minemirth' ) )
		. mm_bh( __( 'Intake temperature and humidity', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Most air-cooled miners are specified for intake air around 0–40 °C. Lower is better for chips and fans.', 'minemirth' ),
				__( 'Keep humidity non-condensing (typically 10–90% relative humidity). Condensation causes corrosion and short circuits.', 'minemirth' ),
				__( 'Avoid salty or dusty air; filter intakes where you can.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Hot aisle, cold aisle', 'minemirth' ) )
		. mm_bp( __( 'The golden rule: never let hot exhaust recirculate into the intakes. Line miners up with intakes facing a cool aisle and exhausts facing a hot aisle or a duct that leads outside. Seal gaps so hot air cannot sneak around. In small rooms, a single exhaust duct from each miner to the outside, plus a fresh-air inlet, works well.', 'minemirth' ) )
		. mm_bh( __( 'Cooling options compared', 'minemirth' ) )
		. mm_bs(
			'table',
			array(
				'columns' => array( __( 'Method', 'minemirth' ), __( 'Best for', 'minemirth' ), __( 'Trade-offs', 'minemirth' ) ),
				'rows'    => array(
					__( 'Direct air (fans and ducting) | Most sites, cool climates | Dust, noise, depends on outside temperature', 'minemirth' ),
					__( 'Evaporative cooling | Hot, dry climates | Adds humidity; needs water and control', 'minemirth' ),
					__( 'Air conditioning | Small rooms | Uses a lot of extra power; rarely economic at scale', 'minemirth' ),
					__( 'Hydro (liquid cold plates) | Dense or quiet sites, heat reuse | Coolant loop, pumps and dry coolers', 'minemirth' ),
					__( 'Immersion | Large engineered sites | Tanks, fluid and maintenance', 'minemirth' ),
				),
			)
		)
		. mm_bs( 'tool', array( 'title' => __( 'Heat and airflow for *your fleet*', 'minemirth' ), 'id' => 'power' ) )
		. mm_bh( __( 'Signs your cooling isn’t enough', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Fans running at 100% all the time.', 'minemirth' ),
				__( 'Chip or board temperatures near the miner’s limit, or “temperature too high” errors.', 'minemirth' ),
				__( 'Hashrate dropping in the afternoon or on hot days.', 'minemirth' ),
				__( 'Room temperature climbing steadily after switching on.', 'minemirth' ),
			)
		)
		. mm_bfaq(
			array(
				array( __( 'Can I put a miner in a closet?', 'minemirth' ), __( 'Only with a dedicated fresh-air inlet and a ducted exhaust to the outside. A closed closet will overheat quickly.', 'minemirth' ) ),
				array( __( 'Is cold weather a problem?', 'minemirth' ), __( 'Very cold intake air can cause condensation when it meets warm surfaces. Mix some exhaust air back in winter to keep intake above freezing and humidity non-condensing.', 'minemirth' ) ),
			)
		);
	},

	'g-noise'     => static function () {
		return mm_bp( __( 'Air-cooled ASIC miners typically run at 70–80 dB, about as loud as a vacuum cleaner, and they do it around the clock. The fans have to move a lot of air through a dense block of heatsinks. Here is what actually reduces the noise, and what doesn’t.', 'minemirth' ) )
		. mm_bh( __( 'Why miners are so loud', 'minemirth' ) )
		. mm_bp( __( 'Most of the noise comes from high-speed fans pushing air through tightly packed heatsinks. The hotter the intake air and the harder the chips work, the faster the fans spin. Noise also travels: through walls, ducts and the floor if the miner sits directly on it.', 'minemirth' ) )
		. mm_bh( __( 'What works', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Distance and placement.</strong> Every doubling of distance cuts noise by roughly 6 dB outdoors. A garage, basement or outbuilding helps more than any gadget.', 'minemirth' ),
				__( '<strong>Cooler intake air.</strong> Cooler air lets fans run slower.', 'minemirth' ),
				__( '<strong>Underclocking.</strong> Lower power targets in firmware reduce heat, so fans slow down. See <a href="{page:g-firmware}">firmware & tuning</a>.', 'minemirth' ),
				__( '<strong>Insulated ducting.</strong> Acoustic flexible ducts on intake and exhaust absorb fan noise before it escapes.', 'minemirth' ),
				__( '<strong>A proper sound enclosure</strong> with baffled, ventilated airflow. It must move enough air or the miner will overheat.', 'minemirth' ),
				__( '<strong>Vibration isolation.</strong> Rubber feet or pads stop the floor from becoming a speaker.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'What doesn’t work', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Putting a miner in a sealed box or cupboard: it overheats.', 'minemirth' ),
				__( 'Swapping in slow, quiet fans without reducing power: chips overheat or throttle.', 'minemirth' ),
				__( 'Foam on the miner itself: it blocks airflow and traps dust.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Quiet by design', 'minemirth' ) )
		. mm_bp( __( 'If you want real silence, change the cooling method. <a href="{page:m-hydro}">Hydro miners</a> replace fans with water and are very quiet at the unit (the dry cooler outside makes some noise). <a href="{page:m-immersion}">Immersion</a> is near-silent. <a href="{page:m-home}">Home miners</a> are designed for living spaces. Or put the noise somewhere else entirely with <a href="{page:hosting}">hosting</a>.', 'minemirth' ) )
		. mm_bcall( 'info', __( 'Neighbours and the law', 'minemirth' ), __( 'Many areas have noise limits, especially at night. Measure at your property line with a phone sound meter app before and after changes.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'How quiet can I make a full-size air-cooled miner?', 'minemirth' ), __( 'With distance, ducting, underclocking and an enclosure, you can often bring it down by 15–25 dB in the next room. It won’t be silent.', 'minemirth' ) ),
				array( __( 'Are hydro miners silent?', 'minemirth' ), __( 'The miners themselves are quiet. The pumps and dry cooler make noise, which can be placed away from living areas.', 'minemirth' ) ),
			)
		);
	},

	'g-immersion' => static function () {
		return mm_bp( __( 'Immersion cooling submerges miners in a non-conductive (dielectric) fluid. The fluid absorbs heat directly from the chips and carries it to a heat exchanger. It is silent, keeps hardware free of dust and humidity, and lets miners run at higher, steadier performance.', 'minemirth' ) )
		. mm_bh( __( 'How an immersion system works', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Tank</strong>: holds the miners and the fluid, with racks or slots.', 'minemirth' ),
				__( '<strong>Dielectric fluid</strong>: usually a single-phase synthetic hydrocarbon or engineered fluid that stays liquid.', 'minemirth' ),
				__( '<strong>Pump and heat exchanger</strong>: circulate hot fluid out of the tank and transfer the heat to a water loop.', 'minemirth' ),
				__( '<strong>Dry cooler or cooling tower</strong>: rejects the heat outdoors, or sends it to a heat-reuse system.', 'minemirth' ),
				__( '<strong>Controls</strong>: temperature, flow and level sensors with alarms.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Benefits', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Near-silent operation, with no fans on the miners.', 'minemirth' ),
				__( 'Stable chip temperatures, which allow overclocking when power is cheap.', 'minemirth' ),
				__( 'Hardware protected from dust, humidity and salt air.', 'minemirth' ),
				__( 'High density: more hashrate per square metre.', 'minemirth' ),
				__( 'Warm fluid for heat reuse.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Preparing miners for immersion', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Remove the fans (and their shrouds) and keep them for resale or return to air.', 'minemirth' ),
				__( 'Use firmware that runs without fan feedback, ideally with immersion profiles.', 'minemirth' ),
				__( 'Check that cables, labels and any thermal materials are compatible with the fluid.', 'minemirth' ),
				__( 'Check how the manufacturer treats immersion for warranty purposes before converting.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Overclocking in immersion', 'minemirth' ) )
		. mm_bp( __( 'Cooler, stable chips let you raise frequency and hashrate. Efficiency (J/TH) usually gets worse as you push higher, so overclock only when your power is cheap enough that the extra hashrate is profitable, and keep chip temperatures within the firmware’s safe limits. Increase in steps and watch error rates.', 'minemirth' ) )
		. mm_bcall( 'warning', __( 'Fluid safety and care', 'minemirth' ), __( 'Use fluids designed for electronics immersion, follow the supplier’s safety data sheet, keep tanks covered to limit contamination, and monitor the fluid’s condition over time.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'Is immersion worth it for one or two miners?', 'minemirth' ), __( 'Usually not. The fixed cost of tanks, pumps and heat rejection makes most sense from a few dozen units up.', 'minemirth' ) ),
				array( __( 'How long does the fluid last?', 'minemirth' ), __( 'Quality fluids are designed for long service life, but they need monitoring and topping up. Follow the supplier’s guidance.', 'minemirth' ) ),
			)
		);
	},

	'g-hydro'     => static function () {
		return mm_bp( __( 'Hydro-cooled miners have water cold plates bolted to their hashboards instead of fans. A coolant loop carries the heat away. They are quiet, dense and among the most efficient miners available, but they are a system, not a plug-and-play box.', 'minemirth' ) )
		. mm_bh( __( 'The parts of a hydro system', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Supply and return manifolds</strong> that connect each miner to the loop.', 'minemirth' ),
				__( '<strong>Pump</strong> sized for the total flow rate and pressure drop.', 'minemirth' ),
				__( '<strong>Filter</strong> to keep particles out of the cold plates.', 'minemirth' ),
				__( '<strong>Heat rejection</strong>: a dry cooler, cooling tower or heat exchanger to a heat-reuse loop.', 'minemirth' ),
				__( '<strong>Coolant</strong>: treated water or a glycol mix, with corrosion inhibitors.', 'minemirth' ),
				__( '<strong>Sensors</strong> for flow, temperature, pressure and leaks.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Follow the spec sheet', 'minemirth' ) )
		. mm_bp( __( 'Each hydro model specifies a coolant flow rate per unit (often in the range of several litres per minute), an inlet temperature range, a maximum pressure and coolant quality requirements. Running outside those values risks overheating, condensation or corrosion. Design the loop around the exact model you buy.', 'minemirth' ) )
		. mm_bh( __( 'Power', 'minemirth' ) )
		. mm_bp( __( 'Hydro miners often draw more power per unit than air-cooled ones and commonly use high-voltage single-phase or three-phase supplies. Plan the electrical side with the same care as the loop. See the <a href="{page:g-power}">power & electrical guide</a>.', 'minemirth' ) )
		. mm_bh( __( 'Heat reuse', 'minemirth' ) )
		. mm_bp( __( 'Return coolant leaves hydro miners warm, which is ideal for heating buildings, greenhouses, pools or drying processes through a heat exchanger. Heat sold or reused can materially improve the economics. See <a href="{page:business}">mining for businesses</a>.', 'minemirth' ) )
		. mm_bcall( 'warning', __( 'Avoid condensation', 'minemirth' ), __( 'Keep coolant inlet temperature above the room’s dew point. Cold coolant in a humid room causes condensation on the miner, a short-circuit risk.', 'minemirth' ) )
		. mm_bs( 'products', array( 'title' => __( 'Hydro *miners*', 'minemirth' ), 'query' => array( 'cooling' => 'hydro', 'sort' => 'efficiency' ), 'limit' => 4, 'link_label' => __( 'All hydro miners', 'minemirth' ), 'link_url' => '{page:m-hydro}' ) )
		. mm_bfaq(
			array(
				array( __( 'Can I use tap water?', 'minemirth' ), __( 'Use the coolant the manufacturer specifies. Untreated tap water causes scaling and corrosion.', 'minemirth' ) ),
				array( __( 'What happens if the pump fails?', 'minemirth' ), __( 'Miners overheat and shut down within minutes. Use flow alarms, and consider a redundant pump for larger loops.', 'minemirth' ) ),
			)
		);
	},

	'g-home'      => static function () {
		return mm_bp( __( 'Mining at home is how many people start. It can work well, but a full-size ASIC changes your home: it adds a big continuous electrical load, a lot of heat and a lot of noise. Here is how to decide what to run, and how to do it safely.', 'minemirth' ) )
		. mm_bh( __( 'Choose the right kind of miner', 'minemirth' ) )
		. mm_bs(
			'table',
			array(
				'columns' => array( __( 'Option', 'minemirth' ), __( 'Power and wiring', 'minemirth' ), __( 'Noise', 'minemirth' ), __( 'Best for', 'minemirth' ) ),
				'rows'    => array(
					__( 'Solo miner (Bitaxe class) | 15–100 W, USB-C or small adapter | Near silent | Learning, solo lottery', 'minemirth' ),
					__( 'Home miner / heater | Normal outlet | Quiet | Living spaces in cold months', 'minemirth' ),
					__( 'Full-size air ASIC | 240 V dedicated circuit, ~3.5 kW | 70–80 dB | Garage, basement, outbuilding', 'minemirth' ),
					__( 'Hosted full-size ASIC | None at home | None at home | Maximum earnings without the hassle', 'minemirth' ),
				),
			)
		)
		. mm_bh( __( 'Power and wiring', 'minemirth' ) )
		. mm_bp( __( 'A full-size miner needs a dedicated 200–240 V circuit sized for continuous load. Check your service capacity with an electrician, especially if you have electric heating, an EV charger or air conditioning. Never use extension reels or power strips. See the <a href="{page:g-power}">power & electrical guide</a>.', 'minemirth' ) )
		. mm_bh( __( 'Heat: problem or opportunity', 'minemirth' ) )
		. mm_bp( __( 'In winter, a miner’s heat can replace a heater, so part of its power cost is something you’d pay anyway. In summer, the same heat must be exhausted outside or your home will overheat. Plan ducting for both seasons.', 'minemirth' ) )
		. mm_bh( __( 'Noise', 'minemirth' ) )
		. mm_bp( __( 'At 70–80 dB, a full-size miner is too loud for bedrooms and living rooms. Garages, basements and outbuildings work better, combined with ducting and underclocking. See <a href="{page:g-noise}">reducing ASIC noise</a>.', 'minemirth' ) )
		. mm_bh( __( 'Internet and monitoring', 'minemirth' ) )
		. mm_bp( __( 'Mining uses very little data. A wired Ethernet connection is most reliable; Wi-Fi bridges work. Set up pool alerts so you know if the miner stops hashing, and change the default password.', 'minemirth' ) )
		. mm_bs( 'tool', array( 'title' => __( 'Home or hosting: *which pays more*?', 'minemirth' ), 'id' => 'hosting' ) )
		. mm_bcall( 'info', __( 'Check your tariff and contract', 'minemirth' ), __( 'Residential tariffs are often higher than industrial ones, and some have peak-time prices. A time-of-use tariff plus a miner that runs off-peak can change the maths. Some rental agreements and insurance policies restrict heavy electrical loads.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'Will mining at home damage my wiring?', 'minemirth' ), __( 'Not if the circuit is installed for it. Problems come from overloaded circuits, undersized cords and loose connections.', 'minemirth' ) ),
				array( __( 'Can I run a miner on solar?', 'minemirth' ), __( 'Yes, if you have enough generation. Miners can soak up surplus solar during the day; at night you pay grid prices unless you have batteries.', 'minemirth' ) ),
			)
		);
	},

	'g-solo'      => static function () {
		return mm_bp( __( 'Solo mining means trying to find a whole block on your own instead of sharing rewards in a pool. With small open-source miners like the Bitaxe, it has become a popular way to learn and to hold a real, if small, lottery ticket on the full block reward.', 'minemirth' ) )
		. mm_bh( __( 'Pool mining vs solo mining', 'minemirth' ) )
		. mm_bp( __( 'In a pool, your hashrate earns a small, steady share of many blocks. Solo, you earn nothing until your miner finds a block, and then you earn the entire reward: the 3.125 BTC subsidy plus fees (about {fees} per block). The expected value over time is similar; the difference is variance.', 'minemirth' ) )
		. mm_bh( __( 'Your odds', 'minemirth' ) )
		. mm_bp( __( 'Your chance of finding the next block is your hashrate divided by the network’s ({hashrate}). With a 1 TH/s Bitaxe that is a tiny fraction, so the chance in a year is small, but not zero, and blocks have been found by home miners. The calculator below shows the odds for any hashrate.', 'minemirth' ) )
		. mm_bs( 'tool', array( 'title' => __( 'Solo *odds* calculator', 'minemirth' ), 'id' => 'solo' ) )
		. mm_bh( __( 'Setting up a solo miner', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Get a Bitcoin address you control</strong>, ideally from a hardware wallet. See <a href="{page:g-security}">mining security & wallets</a>.', 'minemirth' ),
				__( '<strong>Choose a solo pool</strong> or run your own node with mining software. Solo pools build the block template for you and take a small fee only if you find a block.', 'minemirth' ),
				__( '<strong>Configure the miner</strong>: enter the solo pool’s stratum URL and use your Bitcoin address as the username (many pools let you add “.workername”).', 'minemirth' ),
				__( '<strong>Check it is working</strong>: the pool dashboard should show your hashrate and “best difficulty” share.', 'minemirth' ),
				__( '<strong>Keep it cool and updated</strong>: open-source firmware gets regular improvements.', 'minemirth' ),
			),
			true
		)
		. mm_bcall( 'info', __( 'What “best share” means', 'minemirth' ), __( 'Pools show the highest difficulty share your miner has found. A block needs a share at least as high as the network difficulty, so watching your best share climb is part of the fun.', 'minemirth' ) )
		. mm_bh( __( 'Should a full-size miner go solo?', 'minemirth' ) )
		. mm_bp( __( 'Even a 200 TH/s miner would wait years on average. Unless you can afford long dry spells, full-size miners belong in a pool, and a small solo miner is a better lottery ticket per watt.', 'minemirth' ) )
		. mm_bfaq(
			array(
				array( __( 'Is solo mining legal and safe?', 'minemirth' ), __( 'Yes. It is ordinary mining. Use reputable pools and your own address.', 'minemirth' ) ),
				array( __( 'What if two miners find a block at the same time?', 'minemirth' ), __( 'The network keeps one block; the other becomes stale. It is rare and the pool handles it.', 'minemirth' ) ),
			)
		);
	},
);
