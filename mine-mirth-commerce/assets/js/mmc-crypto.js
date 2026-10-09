/* Mine Mirth Commerce — payment page: QR code, copy buttons, rate countdown. */
(function () {
	'use strict';
	document.querySelectorAll('[data-mmc-qr]').forEach(function (box) {
		if (typeof window.qrcode !== 'function') { return; }
		try {
			var qr = window.qrcode(0, 'M');
			qr.addData(box.getAttribute('data-mmc-qr'));
			qr.make();
			box.innerHTML = qr.createSvgTag({ cellSize: 5, margin: 2, scalable: true });
		} catch (e) { box.remove(); }
	});
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-mmc-copy]');
		if (!btn) { return; }
		var text = btn.getAttribute('data-mmc-copy');
		var done = function () { var t = btn.textContent; btn.textContent = '✓'; setTimeout(function () { btn.textContent = t; }, 1400); };
		if (navigator.clipboard) { navigator.clipboard.writeText(text).then(done, function () {}); }
	});
	document.querySelectorAll('[data-mmc-pay]').forEach(function (box) {
		var end = parseInt(box.getAttribute('data-expires'), 10) * 1000;
		var out = box.querySelector('[data-mmc-countdown]');
		if (!end || !out) { return; }
		var tick = function () {
			var left = Math.max(0, end - Date.now());
			var m = Math.floor(left / 60000), s = Math.floor(left / 1000) % 60;
			out.textContent = left ? m + ':' + (s < 10 ? '0' : '') + s : (out.getAttribute('data-expired') || 'Expired — the amount will be re-checked against the current rate');
			if (left) { setTimeout(tick, 1000); }
		};
		tick();
	});
})();
