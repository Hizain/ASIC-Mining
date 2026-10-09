/* Mine Mirth Commerce — crypto payment method for the block checkout. */
(function () {
	'use strict';
	var registry = window.wc && window.wc.wcBlocksRegistry;
	var settingsApi = window.wc && window.wc.wcSettings;
	var el = window.wp && window.wp.element;
	if (!registry || !el) { return; }
	var data = (settingsApi && settingsApi.getSetting('mmc_crypto_data', {})) || {};
	var decode = (window.wp.htmlEntities && window.wp.htmlEntities.decodeEntities) || function (s) { return s; };
	var h = el.createElement;
	var coins = data.coins || [];

	function Content(props) {
		var state = el.useState(coins.length ? coins[0].key : '');
		var coin = state[0], setCoin = state[1];
		var reg = props.eventRegistration;
		el.useEffect(function () {
			var unsubscribe = reg.onPaymentSetup(function () {
				return { type: 'success', meta: { paymentMethodData: { mmc_coin: coin } } };
			});
			return unsubscribe;
		}, [coin, reg.onPaymentSetup]);
		return h('div', { className: 'mmc-coins-block' },
			data.description ? h('p', null, decode(data.description)) : null,
			h('div', { className: 'mmc-coins', role: 'radiogroup' }, coins.map(function (c) {
				return h('label', { key: c.key, className: 'mmc-coin' },
					h('input', { type: 'radio', name: 'mmc_coin_block', value: c.key, checked: coin === c.key, onChange: function () { setCoin(c.key); } }),
					' ', h('strong', null, c.symbol), ' ', h('span', null, c.label));
			}))
		);
	}

	registry.registerPaymentMethod({
		name: 'mmc_crypto',
		label: h('span', null, decode(data.title || 'Pay with crypto')),
		ariaLabel: decode(data.title || 'Pay with crypto'),
		content: h(Content, null),
		edit: h(Content, null),
		canMakePayment: function () { return coins.length > 0; },
		supports: { features: data.supports || ['products'] }
	});
})();
