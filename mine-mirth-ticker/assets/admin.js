/* Mine Mirth Ticker: the items builder on the settings page. */
(function () {
	'use strict';
	var list = document.getElementById('mmtk-items');
	if (!list) { return; }
	var next = 1000;

	document.addEventListener('click', function (e) {
		var add = e.target.closest('[data-mmtk-add]');
		if (add) {
			var tpl = document.getElementById('mmtk-tpl-' + add.getAttribute('data-mmtk-add'));
			if (tpl) {
				var wrap = document.createElement('div');
				wrap.innerHTML = tpl.innerHTML.replace(/__i__/g, String(next++));
				var row = wrap.firstElementChild;
				list.appendChild(row);
				var first = row.querySelector('input[type=text]');
				if (first) { first.focus(); }
			}
			return;
		}
		var row = e.target.closest('[data-mmtk-row]');
		if (!row) {
			var coins = e.target.closest('[data-mmtk-coins]');
			if (coins) {
				var on = coins.getAttribute('data-mmtk-coins') === 'all';
				document.querySelectorAll('.mmtk-coin-on').forEach(function (c) { c.checked = on; c.closest('tr').classList.toggle('is-off', !on); });
			}
			return;
		}
		if (e.target.closest('[data-mmtk-up]') && row.previousElementSibling) { list.insertBefore(row, row.previousElementSibling); }
		if (e.target.closest('[data-mmtk-down]') && row.nextElementSibling) { list.insertBefore(row.nextElementSibling, row); }
		if (e.target.closest('[data-mmtk-remove]')) { row.remove(); }
	});

	document.addEventListener('change', function (e) {
		if (e.target.classList.contains('mmtk-coin-on')) { e.target.closest('tr').classList.toggle('is-off', !e.target.checked); }
	});
})();
