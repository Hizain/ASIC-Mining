/*!
 * Mine Mirth — live market data.
 * Pulls the cached snapshot from /wp-json/mine-mirth/v1/market and updates
 * every [data-mm-m="key"] element. Other scripts listen via MineMirth.on('market').
 */
(function () {
	'use strict';

	var MM = window.MineMirth;
	if (!MM) { return; }
	var cfg = (MM.config && MM.config.market) || {};
	if (!cfg.enabled || !cfg.endpoint) { return; }

	var last = null;
	var lastHeight = null;
	var timer = null;

	function flash(node) {
		node.classList.remove('is-flash');
		void node.offsetWidth;
		node.classList.add('is-flash');
	}

	function apply(data) {
		if (!data || !data.display) { return; }
		last = data;
		var d = data.display;
		Array.prototype.forEach.call(document.querySelectorAll('[data-mm-m]'), function (node) {
			var key = node.getAttribute('data-mm-m');
			if (!(key in d)) { return; }
			var value = String(d[key]);
			if (node.textContent !== value) {
				node.textContent = value;
				if (lastHeight !== null) { flash(node); }
			}
			if (key === 'change') {
				node.classList.remove('is-up', 'is-down', 'is-flat');
				node.classList.add(d.change_class || 'is-flat');
			}
		});
		Array.prototype.forEach.call(document.querySelectorAll('[data-mm-spark]'), function (svg) {
			var path = svg.querySelector('path');
			if (path && d.spark_path) { path.setAttribute('d', d.spark_path); }
			svg.classList.toggle('is-down', !!d.spark_down);
		});
		if (lastHeight !== null && data.height && data.height !== lastHeight) {
			Array.prototype.forEach.call(document.querySelectorAll('.mm-float--block'), function (b) {
				b.classList.remove('is-new');
				void b.offsetWidth;
				b.classList.add('is-new');
			});
		}
		lastHeight = data.height || lastHeight || 0;
		MM.emit('market', data);
	}

	function load() {
		return fetch(cfg.endpoint, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
			.then(function (r) { return r.ok ? r.json() : null; })
			.then(function (json) { if (json) { apply(json); } return json; })
			.catch(function () { return null; });
	}

	function schedule() {
		clearInterval(timer);
		timer = setInterval(function () { if (!document.hidden) { load(); } }, Math.max(60, cfg.refresh || 120) * 1000);
	}

	MM.market = {
		get: function () { return last; },
		refresh: load,
		ready: load()
	};
	schedule();
	document.addEventListener('visibilitychange', function () {
		if (!document.hidden && last && Date.now() / 1000 - (last.updated_at || 0) > (cfg.refresh || 120)) { load(); }
	});
})();
