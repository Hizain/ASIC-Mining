/* Mine Mirth Subscriptions: quantity stepper with a live total on plan cards. */
(function () {
	'use strict';
	function money(minor, cur) {
		var zero = /^(bif|clp|djf|gnf|jpy|kmf|krw|mga|pyg|rwf|ugx|vnd|vuv|xaf|xof|xpf)$/i.test(cur);
		var v = zero ? minor : minor / 100;
		try { return new Intl.NumberFormat(undefined, { style: 'currency', currency: cur.toUpperCase() }).format(v); } catch (e) { return v.toFixed(zero ? 0 : 2); }
	}
	document.querySelectorAll('[data-mms-qty]').forEach(function (box) {
		var input = box.querySelector('input');
		var total = box.querySelector('[data-mms-total]');
		var unit = parseInt(box.getAttribute('data-unit'), 10) || 0;
		var cur = box.getAttribute('data-cur') || 'usd';
		var max = parseInt(input.getAttribute('max'), 10) || 999;
		function update() {
			var n = Math.max(1, Math.min(max, parseInt(input.value, 10) || 1));
			input.value = n;
			if (total) { total.textContent = n > 1 ? n + ' × ' + money(unit, cur) + ' = ' + money(unit * n, cur) : money(unit, cur); }
		}
		box.querySelectorAll('[data-step]').forEach(function (b) {
			b.addEventListener('click', function () { input.value = (parseInt(input.value, 10) || 1) + parseInt(b.getAttribute('data-step'), 10); update(); });
		});
		input.addEventListener('change', update);
		input.addEventListener('input', function () { if (input.value !== '') { update(); } });
		update();
	});
	// Show that something is happening while Stripe Checkout loads.
	document.querySelectorAll('.mms-subscribe').forEach(function (f) {
		f.addEventListener('submit', function () {
			var b = f.querySelector('button[type=submit]');
			if (b) { b.setAttribute('aria-busy', 'true'); b.style.opacity = '.7'; }
		});
	});
}());
