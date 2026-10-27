<?php
/**
 * Content: market today, partners, privacy policy, terms.
 *
 * Privacy and terms are starting templates: they are only created when you have no page at
 * that address, and should be reviewed by someone qualified for your business and country.
 *
 * @package MineMirth
 */

defined( 'ABSPATH' ) || exit;

return array(

	'market'   => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Live dashboard', 'minemirth' ),
				'title'   => __( 'The Bitcoin mining market *today*', 'minemirth' ),
				'text'    => __( 'Bitcoin is {btc_price} ({btc_change} in 24 hours). One PH/s earns {hashprice} a day, network difficulty is {difficulty} and the next adjustment is {adjustment}.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Hashprice tracker', 'minemirth' ), '{tool:hashprice}', 'primary', 'activity' ), array( __( 'Profit calculator', 'minemirth' ), '{tool:profit}', 'glass', 'calculator' ) ) ),
				'visual'  => 'market',
			)
		)
		. mm_bs( 'market', array( 'eyebrow' => __( 'Network', 'minemirth' ), 'title' => __( 'Every number that *moves miner revenue*', 'minemirth' ) ) )
		. mm_bs( 'tool', array( 'eyebrow' => __( 'Hashprice', 'minemirth' ), 'title' => __( 'What hashrate earns, *and how it has moved*', 'minemirth' ), 'id' => 'hashprice', 'tone' => 'alt' ) )
		. mm_bs( 'tool', array( 'eyebrow' => __( 'Difficulty', 'minemirth' ), 'title' => __( 'The next *difficulty adjustment*', 'minemirth' ), 'id' => 'difficulty' ) )
		. mm_bs( 'tool', array( 'eyebrow' => __( 'Halving', 'minemirth' ), 'title' => __( 'Countdown to the *next halving*', 'minemirth' ), 'id' => 'halving', 'tone' => 'alt' ) )
		. mm_bs( 'links', array( 'eyebrow' => __( 'Understand the numbers', 'minemirth' ), 'title' => __( 'Explained in *plain English*', 'minemirth' ), 'columns' => '3', 'items' => array( array( 'icon' => 'activity', 'title' => __( 'What is hashprice?', 'minemirth' ), 'text' => __( 'The miner’s key metric.', 'minemirth' ), 'url' => '{page:g-hashprice}' ), array( 'icon' => 'layers', 'title' => __( 'Difficulty explained', 'minemirth' ), 'text' => __( 'Why it changes every two weeks.', 'minemirth' ), 'url' => '{page:g-difficulty}' ), array( 'icon' => 'clock', 'title' => __( 'The halving and miners', 'minemirth' ), 'text' => __( 'How to prepare.', 'minemirth' ), 'url' => '{page:g-halving}' ) ) ) )
		. mm_bs( 'newsletter', array( 'eyebrow' => __( 'Weekly', 'minemirth' ), 'title' => __( 'Get these numbers *every week*', 'minemirth' ), 'text' => __( 'A short weekly email with hashprice, difficulty and what changed.', 'minemirth' ), 'context' => 'market' ) );
	},

	'partners' => static function () {
		return mm_bs(
			'hero',
			array(
				'eyebrow' => __( 'Partners', 'minemirth' ),
				'title'   => __( 'Grow with *Mine Mirth*', 'minemirth' ),
				'text'    => __( 'We work with hosting facilities, resellers, electricians and installers, and creators who teach mining. If you serve miners, let’s talk.', 'minemirth' ),
				'buttons' => mm_bbtn( array( array( __( 'Become a partner', 'minemirth' ), '{page:contact}' ) ) ),
				'visual'  => 'orbit',
				'icon'    => 'globe',
				'chips'   => mm_bchips( array( array( 'server', __( 'Facilities', 'minemirth' ), __( 'capacity partners', 'minemirth' ) ), array( 'package', __( 'Resellers', 'minemirth' ), __( 'supply partners', 'minemirth' ) ), array( 'bolt', __( 'Installers', 'minemirth' ), __( 'electrical partners', 'minemirth' ) ), array( 'message', __( 'Creators', 'minemirth' ), __( 'education partners', 'minemirth' ) ) ) ),
			)
		)
		. mm_bs(
			'features',
			array(
				'eyebrow' => __( 'Who we work with', 'minemirth' ),
				'title'   => __( 'Four ways to *partner*', 'minemirth' ),
				'columns' => '2',
				'items'   => mm_bitems(
					array(
						array( 'server', __( 'Hosting facilities', 'minemirth' ), __( 'Have capacity with reliable power and cooling? We bring miners and customers, and publish clear plans for your space.', 'minemirth' ) ),
						array( 'package', __( 'Resellers and distributors', 'minemirth' ), __( 'Source miners and parts for your customers with volume pricing and logistics support.', 'minemirth' ) ),
						array( 'bolt', __( 'Electricians and installers', 'minemirth' ), __( 'Licensed professionals who wire circuits and ventilation for home and small-farm miners.', 'minemirth' ) ),
						array( 'message', __( 'Creators and educators', 'minemirth' ), __( 'Teach mining honestly? We share data, tools and hardware access for your audience.', 'minemirth' ) ),
					)
				),
			)
		)
		. mm_bs(
			'steps',
			array(
				'eyebrow' => __( 'Getting started', 'minemirth' ),
				'title'   => __( 'How partnerships *begin*', 'minemirth' ),
				'tone'    => 'alt',
				'items'   => array(
					array( 'message', __( 'Introduce yourself', 'minemirth' ), __( 'Who you are, where you operate and what you offer.', 'minemirth' ) ),
					array( 'users', __( 'A call', 'minemirth' ), __( 'We discuss fit, volumes and how we would work together.', 'minemirth' ) ),
					array( 'file', __( 'Agreement', 'minemirth' ), __( 'Clear terms in writing.', 'minemirth' ) ),
				),
			)
		)
		. mm_bcta( __( 'Let’s *work together*', 'minemirth' ), __( 'Tell us about your business.', 'minemirth' ), array( array( __( 'Contact us', 'minemirth' ), '{page:contact}' ) ), array( 'style' => 'light' ) );
	},

	'privacy'  => static function () {
		$site = get_bloginfo( 'name' );
		return mm_bp( '<em>' . __( 'This is a starting template. Review it with someone qualified for your business and the countries you sell to, and keep it up to date.', 'minemirth' ) . '</em>' )
		. mm_bp( sprintf( /* translators: %s: site name */ __( 'This policy explains how %s (“we”) collects, uses and protects personal data when you use this website, buy from us or contact us.', 'minemirth' ), esc_html( $site ) ) )
		. mm_bh( __( 'What we collect', 'minemirth' ) )
		. mm_bl(
			array(
				__( '<strong>Order and account details</strong>: name, email, phone, billing and shipping addresses, company details and order history.', 'minemirth' ),
				__( '<strong>Payment information</strong>: handled by our payment providers; we do not store full card numbers.', 'minemirth' ),
				__( '<strong>Messages</strong> you send us through forms, email or chat.', 'minemirth' ),
				__( '<strong>Newsletter</strong> sign-ups and email engagement (opens and clicks), if you subscribe.', 'minemirth' ),
				__( '<strong>Technical data</strong>: IP address, browser and device information, and cookies needed to run the site, your cart and your account.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'How we use it', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'To process orders, deliveries, warranty claims, hosting and support.', 'minemirth' ),
				__( 'To send service messages about your orders and account.', 'minemirth' ),
				__( 'To send newsletters if you subscribed; you can unsubscribe at any time.', 'minemirth' ),
				__( 'To prevent fraud and keep the site secure.', 'minemirth' ),
				__( 'To meet legal, tax and accounting obligations.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Who we share it with', 'minemirth' ) )
		. mm_bp( __( 'Only with service providers who help us run the business, such as payment processors, carriers and customs brokers, hosting facilities you choose, email delivery and website hosting, and with authorities where the law requires it. We do not sell personal data.', 'minemirth' ) )
		. mm_bh( __( 'Cookies', 'minemirth' ) )
		. mm_bp( __( 'We use essential cookies for the cart, checkout and login, and may use analytics cookies to understand how the site is used. Where required, we ask for your consent first.', 'minemirth' ) )
		. mm_bh( __( 'How long we keep it', 'minemirth' ) )
		. mm_bp( __( 'Order records are kept as long as tax and accounting laws require. Other data is kept only as long as needed for the purpose it was collected for.', 'minemirth' ) )
		. mm_bh( __( 'Your rights', 'minemirth' ) )
		. mm_bp( __( 'Depending on where you live, you may have the right to access, correct, delete or export your data, and to object to or restrict some uses. Contact us to make a request.', 'minemirth' ) )
		. mm_bh( __( 'Contact', 'minemirth' ) )
		. mm_bp( __( 'Questions about privacy? <a href="{page:contact}">Contact us</a>.', 'minemirth' ) );
	},

	'terms'    => static function () {
		$site = get_bloginfo( 'name' );
		return mm_bp( '<em>' . __( 'This is a starting template. Review it with a legal professional for your business and the countries you sell to.', 'minemirth' ) . '</em>' )
		. mm_bp( sprintf( /* translators: %s: site name */ __( 'These terms apply to your use of this website and to orders placed with %s. By placing an order you agree to them.', 'minemirth' ), esc_html( $site ) ) )
		. mm_bh( __( 'Orders and prices', 'minemirth' ) )
		. mm_bl(
			array(
				__( 'Prices, specifications and availability are shown on product pages and quotes and can change with the market until an order is confirmed.', 'minemirth' ),
				__( 'An order is confirmed when payment clears and we confirm allocation.', 'minemirth' ),
				__( 'Pre-orders follow the batch, shipping window and payment terms stated on the quote.', 'minemirth' ),
			)
		)
		. mm_bh( __( 'Shipping, risk and customs', 'minemirth' ) )
		. mm_bp( __( 'Delivery terms are described on the <a href="{page:shipping}">shipping page</a> and your quote. Import duties, taxes and brokerage fees are usually the buyer’s responsibility unless stated otherwise.', 'minemirth' ) )
		. mm_bh( __( 'Warranty and returns', 'minemirth' ) )
		. mm_bp( __( 'New miners carry the manufacturer’s warranty. Returns and refunds follow our <a href="{page:returns}">returns policy</a> and your statutory rights.', 'minemirth' ) )
		. mm_bh( __( 'Mining results', 'minemirth' ) )
		. mm_bp( __( 'Profit estimates, calculators and guides on this site are for information only. Mining revenue depends on factors outside our control, including Bitcoin’s price, network difficulty, fees, your power cost and hardware performance. We do not guarantee any earnings.', 'minemirth' ) )
		. mm_bh( __( 'Hosting', 'minemirth' ) )
		. mm_bp( __( 'Hosting services are governed by a separate written agreement that sets out rates, billing, uptime, curtailment, access and removal.', 'minemirth' ) )
		. mm_bh( __( 'Liability', 'minemirth' ) )
		. mm_bp( __( 'To the extent the law allows, we are not liable for indirect or consequential losses such as lost mining revenue. Nothing in these terms limits liability that cannot be limited by law.', 'minemirth' ) )
		. mm_bh( __( 'Contact', 'minemirth' ) )
		. mm_bp( __( 'Questions about these terms? <a href="{page:contact}">Contact us</a>.', 'minemirth' ) );
	},
);
