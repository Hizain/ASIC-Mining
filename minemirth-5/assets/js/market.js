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

	/* ------------------------------------------------------------------
	 * Browser fallback: some hosts block outbound HTTP, so when the server
	 * has no snapshot, read the same public APIs directly (both send CORS).
	 * ---------------------------------------------------------------- */
	var SYM = { USD: '$', EUR: '€', GBP: '£', CAD: 'CA$', AUD: 'A$' };
	function nf(v, d) {
		try { return Number(v).toLocaleString(cfg.locale || undefined, { minimumFractionDigits: d, maximumFractionDigits: d }); }
		catch (e) { return Number(v).toFixed(d); }
	}
	function getJSON(url) {
		return fetch(url, { headers: { Accept: 'application/json' } }).then(function (r) { return r.ok ? r.json() : null; }).catch(function () { return null; });
	}
	function sparkPath(series) {
		if (!series || series.length < 2) { return 'M0 16 L120 16'; }
		var min = Math.min.apply(null, series), max = Math.max.apply(null, series), span = (max - min) || 1;
		return series.map(function (v, i) {
			return (i ? 'L' : 'M') + (i / (series.length - 1) * 120).toFixed(2) + ' ' + (30 - (v - min) / span * 28).toFixed(2);
		}).join(' ');
	}
	function clientFallback() {
		var cur = String(cfg.currency || 'USD').toLowerCase();
		return Promise.all([
			getJSON('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=' + cur + '&include_24hr_change=true'),
			getJSON('https://mempool.space/api/v1/mining/hashrate/3d'),
			getJSON('https://mempool.space/api/blocks/tip/height'),
			getJSON('https://mempool.space/api/v1/difficulty-adjustment'),
			getJSON('https://api.coingecko.com/api/v3/coins/bitcoin/market_chart?vs_currency=' + cur + '&days=1')
		]).then(function (r) {
			var price = r[0] && r[0].bitcoin ? r[0].bitcoin[cur] : null;
			var change = r[0] && r[0].bitcoin ? r[0].bitcoin[cur + '_24h_change'] : null;
			var hash = r[1] ? r[1].currentHashrate : null;
			var diff = r[1] ? r[1].currentDifficulty : null;
			var height = typeof r[2] === 'number' ? r[2] : null;
			if (price == null && hash == null) { return null; }
			var adj = r[3] && r[3].remainingBlocks != null ? r[3] : null;
			var series = r[4] && r[4].prices ? r[4].prices.filter(function (_, i, a) { return i % Math.max(1, Math.floor(a.length / 32)) === 0; }).map(function (p) { return p[1]; }) : [];
			var halvingLeft = height != null ? Math.ceil((height + 1) / 210000) * 210000 - height : null;
			var sym = SYM[String(cfg.currency || 'USD').toUpperCase()] || '$';
			var data = {
				status: 'live', client: true, price: price, change: change, hashrate: hash, difficulty: diff, height: height,
				fees: null, updated_at: Math.round(Date.now() / 1000), is_stale: false,
				halving: height != null ? { blocks_left: halvingLeft, subsidy: 50 / Math.pow(2, Math.floor(height / 210000)) } : null
			};
			data.display = {
				price: price != null ? sym + nf(price, 0) : '—',
				change: change != null ? (change > 0 ? '+' : '') + nf(change, 2) + '%' : '',
				change_class: change == null ? 'is-flat' : (change >= 0 ? 'is-up' : 'is-down'),
				hashrate: hash ? (hash >= 1e21 ? nf(hash / 1e21, 2) + ' ZH/s' : nf(hash / 1e18, 1) + ' EH/s') : '—',
				difficulty: diff ? nf(diff / 1e12, 2) + ' T' : '—',
				height: height != null ? nf(height, 0) : '—',
				fees: '—',
				adjustment: adj ? (adj.difficultyChange > 0 ? '+' : '') + nf(adj.difficultyChange, 2) + '% in ' + nf(adj.remainingBlocks, 0) + ' blocks' : '—',
				halving: halvingLeft != null ? nf(halvingLeft, 0) : '—',
				updated: ((MM.config && MM.config.i18n && MM.config.i18n.updated) || 'Updated') + ' ' + new Date().toLocaleTimeString(cfg.locale || undefined, { hour: 'numeric', minute: '2-digit' }),
				spark_path: sparkPath(series),
				spark_down: change != null && change < 0
			};
			return data;
		});
	}

	function load() {
		return fetch(cfg.endpoint, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
			.then(function (r) { return r.ok ? r.json() : null; })
			.then(function (json) {
				if (json && json.status !== 'unavailable' && json.status !== 'disabled') { apply(json); return json; }
				if (json && json.status === 'disabled') { return json; }
				return clientFallback().then(function (data) {
					if (data) { apply(data); return data; }
					if (json) { apply(json); }
					return json;
				});
			})
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
