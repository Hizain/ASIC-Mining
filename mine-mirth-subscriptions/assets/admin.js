/* Mine Mirth Subscriptions: admin helpers (confirmations, copy links, refund amounts, panels). */
(function () {
	'use strict';
	document.addEventListener('submit', function (e) {
		var f = e.target.closest('form[data-mms-confirm]');
		if (f && !window.confirm(f.getAttribute('data-mms-confirm'))) { e.preventDefault(); }
	});
	document.addEventListener('click', function (e) {
		var copy = e.target.closest('[data-mms-copy]');
		if (copy) {
			var text = copy.getAttribute('data-mms-copy');
			var done = function () { var t = copy.textContent; copy.textContent = 'Copied'; setTimeout(function () { copy.textContent = t; }, 1600); };
			if (navigator.clipboard) { navigator.clipboard.writeText(text).then(done, function () { window.prompt('Copy this link:', text); }); } else { window.prompt('Copy this link:', text); }
		}
		var open = e.target.closest('[data-mms-open]');
		if (open) {
			var box = document.getElementById(open.getAttribute('data-mms-open'));
			if (box) { box.hidden = !box.hidden; if (!box.hidden) { var i = box.querySelector('input[type=email], select'); if (i) { i.focus(); } } }
		}
	});
	// Show what is left to refund on the chosen invoice.
	var sel = document.querySelector('[data-mms-refund-invoice]');
	var amt = document.querySelector('[data-mms-refund-amount]');
	if (sel && amt) {
		var upd = function () { var o = sel.options[sel.selectedIndex]; amt.placeholder = o ? o.getAttribute('data-left') : '0.00'; };
		sel.addEventListener('change', upd);
		upd();
	}
	// Show only the keys for the chosen mode.
	var modes = document.querySelectorAll('input[name="mms[mode]"]');
	function syncMode() {
		var m = (document.querySelector('input[name="mms[mode]"]:checked') || {}).value || 'test';
		document.querySelectorAll('.mms-keys').forEach(function (k) { k.classList.toggle('is-off', !k.classList.contains('mms-keys--' + m)); });
	}
	modes.forEach(function (r) { r.addEventListener('change', syncMode); });
	if (modes.length) { syncMode(); }
}());
