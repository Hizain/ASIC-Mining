/*!
 * Mine Mirth ASIC Market Scope.
 * Every number comes from market data or the published ecosystem rating; nothing is random.
 */
(function () {
	'use strict';

	var WEIGHTS = { size: 0.25, trend: 0.20, liquidity: 0.15, ath: 0.15, ecosystem: 0.15, supply: 0.10 };

	function esc(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
	}
	function clamp(v) { return Math.max(0, Math.min(100, v)); }
	function isNum(v) { return typeof v === 'number' && isFinite(v); }
	function usd(v) {
		if (!isNum(v)) { return '—'; }
		var d = v >= 1000 ? 0 : v >= 1 ? 2 : v >= 0.01 ? 4 : 6;
		return '$' + v.toLocaleString(undefined, { minimumFractionDigits: d, maximumFractionDigits: d });
	}
	function big(v) {
		if (!isNum(v)) { return '—'; }
		var units = [[1e12, 'T'], [1e9, 'B'], [1e6, 'M'], [1e3, 'K']];
		for (var i = 0; i < units.length; i++) { if (v >= units[i][0]) { return (v / units[i][0]).toFixed(v / units[i][0] >= 100 ? 0 : 1) + units[i][1]; } }
		return String(Math.round(v));
	}
	function pct(v, d) { return isNum(v) ? (v > 0 ? '+' : v < 0 ? '−' : '') + Math.abs(v).toFixed(d == null ? 1 : d) + '%' : '—'; }
	function band(score) { return score >= 66 ? 'high' : score >= 40 ? 'medium' : 'low'; }
	function bandLabel(b) { return { high: 'Strong', medium: 'Moderate', low: 'Weak' }[b]; }
	function safeImage(url) { return /^https:\/\/(assets|coin-images)\.coingecko\.com\//.test(url || '') ? url : ''; }

	/* The long-term score: each part 0–100, weighted. */
	function score(coin, m, algos) {
		var parts = {};
		parts.size = isNum(m.mcap) && m.mcap > 0 ? clamp((Math.log10(m.mcap) - 7) / 5 * 100) : 0;
		var turnover = isNum(m.volume) && isNum(m.mcap) && m.mcap > 0 ? m.volume / m.mcap : 0;
		parts.liquidity = turnover > 0 ? clamp((Math.log10(turnover) - Math.log10(0.005)) / (Math.log10(0.05) - Math.log10(0.005)) * 100) : 0;
		var trendBasis = isNum(m.ch1y) ? '1y' : isNum(m.ch30d) ? '30d' : '';
		parts.trend = trendBasis === '1y' ? clamp((m.ch1y + 80) / 180 * 100) : trendBasis === '30d' ? clamp((m.ch30d + 40) / 80 * 100) : 50;
		parts.ath = isNum(m.athPct) ? clamp((m.athPct + 95) / 95 * 100) : 50;
		var issued = isNum(m.max) && m.max > 0 && isNum(m.circ) ? Math.min(1, m.circ / m.max) : null;
		parts.supply = issued !== null ? 50 + 50 * issued : 35;
		var algo = algos[coin.algo] || { rating: 40, note: '' };
		parts.ecosystem = algo.rating;
		var total = 0;
		Object.keys(WEIGHTS).forEach(function (k) { total += parts[k] * WEIGHTS[k]; });
		return { total: Math.round(total), parts: parts, turnover: turnover, issued: issued, trendBasis: trendBasis, algoNote: algo.note };
	}

	/* A plain-language summary built from the same numbers. */
	function verdict(coin, m, s) {
		var size = !isNum(m.mcap) ? 'a coin without market cap data' : m.mcap >= 1e10 ? 'a large, established market' : m.mcap >= 1e9 ? 'a mid-size market' : m.mcap >= 1e8 ? 'a small market' : 'a very small market';
		var trend = s.trendBasis === '1y' ? (m.ch1y >= 20 ? 'up ' + Math.round(m.ch1y) + '% over the past year' : m.ch1y <= -20 ? 'down ' + Math.round(-m.ch1y) + '% over the past year' : 'roughly flat over the past year') : 'too new for a one-year trend';
		var ath = isNum(m.athPct) ? (m.athPct > -10 ? 'trading near its all-time high' : Math.round(-m.athPct) + '% below its all-time high') : '';
		var supply = s.issued !== null ? 'a capped supply (' + Math.round(s.issued * 100) + '% already issued)' : 'no fixed supply cap';
		var text = coin.name + ' is ' + size + ', ' + trend + (ath ? ' and ' + ath : '') + ', with ' + supply + '.';
		if (coin.merged) { text += ' It is merged-mined with ' + coin.merged + ', so existing ' + coin.algo + ' miners can earn it on top of their main coin.'; }
		if (s.turnover && s.turnover < 0.005) { text += ' Trading volume is thin, which can make mined coins harder to sell.'; }
		return text;
	}

	function sparkPath(values, w, h) {
		if (!values || values.length < 2) { return ''; }
		var min = Math.min.apply(null, values), max = Math.max.apply(null, values), span = (max - min) || 1;
		return values.map(function (v, i) { return (i ? 'L' : 'M') + (i / (values.length - 1) * w).toFixed(1) + ' ' + (h - 2 - (v - min) / span * (h - 4)).toFixed(1); }).join(' ');
	}

	function init(root) {
		var cfg = {};
		try { cfg = JSON.parse(root.getAttribute('data-config') || '{}'); } catch (e) { cfg = {}; }
		var grid = root.querySelector('[data-mmms-grid]');
		var status = root.querySelector('[data-mmms-status]');
		var search = root.querySelector('[data-mmms-search]');
		var algoSel = root.querySelector('[data-mmms-algo]');
		var sortSel = root.querySelector('[data-mmms-sort]');
		var chips = root.querySelectorAll('[data-mmms-band]');
		var state = { q: '', band: 'all', algo: '', sort: 'score', open: '' };
		var rows = [];

		Object.keys(cfg.algos || {}).forEach(function (a) {
			if ((cfg.coins || []).some(function (c) { return c.algo === a; })) {
				var o = document.createElement('option'); o.value = a; o.textContent = a; algoSel.appendChild(o);
			}
		});

		function build(market) {
			rows = (cfg.coins || []).map(function (coin) {
				var m = market[coin.id];
				if (!m || !isNum(m.price)) { return null; }
				var s = score(coin, m, cfg.algos || {});
				return { coin: coin, m: m, s: s, band: band(s.total), verdict: verdict(coin, m, s) };
			}).filter(Boolean);
		}

		function partRow(label, value, detail, weight) {
			return '<li><div class="mmms__part-head"><span>' + esc(label) + ' <small>' + Math.round(weight * 100) + '%</small></span><b>' + Math.round(value) + '</b></div><div class="mmms__part-bar"><i style="width:' + Math.round(value) + '%"></i></div><small>' + esc(detail) + '</small></li>';
		}

		function card(r) {
			var c = r.coin, m = r.m, s = r.s, open = state.open === c.id;
			var img = safeImage(m.image);
			var up = isNum(m.ch24h) && m.ch24h >= 0;
			var spark = sparkPath(m.spark, 120, 34);
			var sparkUp = m.spark && m.spark.length > 1 ? m.spark[m.spark.length - 1] >= m.spark[0] : true;
			var h = '<article class="mmms__card' + (open ? ' is-open' : '') + '" data-id="' + esc(c.id) + '">';
			h += '<header class="mmms__card-head">';
			h += img ? '<img class="mmms__logo" src="' + esc(img) + '" alt="" width="40" height="40" loading="lazy" decoding="async">' : '<span class="mmms__logo mmms__logo--blank" aria-hidden="true">' + esc(c.symbol.charAt(0)) + '</span>';
			h += '<div class="mmms__id"><h3>' + esc(c.name) + ' <small>' + esc(c.symbol) + '</small></h3><div class="mmms__meta"><span>Rank ' + (isNum(m.rank) ? m.rank : '—') + '</span><span class="mmms__badge">' + esc(c.algo) + '</span>' + (c.merged ? '<span class="mmms__badge is-merged" title="Merged-mined with ' + esc(c.merged) + '">Merged</span>' : '') + '</div></div>';
			h += '<div class="mmms__price"><strong>' + usd(m.price) + '</strong><span class="' + (up ? 'is-up' : 'is-down') + '">' + pct(m.ch24h, 2) + '</span></div>';
			h += '</header>';
			if (spark) {
				h += '<svg class="mmms__spark' + (sparkUp ? '' : ' is-down') + '" viewBox="0 0 120 34" preserveAspectRatio="none" role="img" aria-label="7-day price ' + (sparkUp ? 'up' : 'down') + ' ' + esc(pct(m.ch7d)) + '"><path d="' + spark + '"/></svg>';
			}
			h += '<div class="mmms__score is-' + r.band + '"><div class="mmms__score-top"><span>Long-term score</span><b>' + s.total + '<small>/100</small></b></div><div class="mmms__score-bar"><i style="width:' + s.total + '%"></i></div></div>';
			h += '<button type="button" class="mmms__toggle" aria-expanded="' + (open ? 'true' : 'false') + '" data-toggle="' + esc(c.id) + '">' + (open ? 'Hide insights' : bandLabel(r.band) + ' · view insights') + '</button>';
			if (open) {
				var trendDetail = s.trendBasis === '1y' ? '12-month change ' + pct(m.ch1y) : s.trendBasis === '30d' ? 'Less than a year of data · 30-day change ' + pct(m.ch30d) : 'Not enough history';
				h += '<div class="mmms__insights"><p>' + esc(r.verdict) + '</p><ul class="mmms__parts">';
				h += partRow('Market size', s.parts.size, 'Market cap $' + big(m.mcap), WEIGHTS.size);
				h += partRow('One-year trend', s.parts.trend, trendDetail, WEIGHTS.trend);
				h += partRow('Liquidity', s.parts.liquidity, '24h volume $' + big(m.volume) + ' (' + (s.turnover * 100).toFixed(1) + '% of market cap)', WEIGHTS.liquidity);
				h += partRow('From all-time high', s.parts.ath, isNum(m.athPct) ? pct(m.athPct, 0) + ' from ' + usd(m.ath) + (m.athDate ? ' (' + m.athDate.slice(0, 4) + ')' : '') : 'No data', WEIGHTS.ath);
				h += partRow('Mining ecosystem', s.parts.ecosystem, s.algoNote, WEIGHTS.ecosystem);
				h += partRow('Supply schedule', s.parts.supply, s.issued !== null ? big(m.circ) + ' of ' + big(m.max) + ' issued' : 'No maximum supply', WEIGHTS.supply);
				h += '</ul>';
				if (cfg.shop) { h += '<a class="mmms__shop" href="' + esc(cfg.shop) + '">Find ' + esc(c.algo) + ' miners →</a>'; }
				h += '</div>';
			}
			h += '</article>';
			return h;
		}

		function render() {
			var q = state.q.toLowerCase();
			var list = rows.filter(function (r) {
				if (state.algo && r.coin.algo !== state.algo) { return false; }
				if (q && (r.coin.name + ' ' + r.coin.symbol + ' ' + r.coin.algo).toLowerCase().indexOf(q) === -1) { return false; }
				return true;
			});
			var counts = { all: list.length, high: 0, medium: 0, low: 0 };
			list.forEach(function (r) { counts[r.band]++; });
			root.querySelectorAll('[data-count]').forEach(function (el) { el.textContent = rows.length ? '(' + counts[el.getAttribute('data-count')] + ')' : ''; });
			if (state.band !== 'all') { list = list.filter(function (r) { return r.band === state.band; }); }
			var by = {
				score: function (a, b) { return b.s.total - a.s.total; },
				mcap: function (a, b) { return (b.m.mcap || 0) - (a.m.mcap || 0); },
				ch1y: function (a, b) { return (isNum(b.m.ch1y) ? b.m.ch1y : -1e9) - (isNum(a.m.ch1y) ? a.m.ch1y : -1e9); },
				ch24h: function (a, b) { return (b.m.ch24h || 0) - (a.m.ch24h || 0); },
				name: function (a, b) { return a.coin.name.localeCompare(b.coin.name); }
			};
			list.sort(by[state.sort] || by.score);
			grid.innerHTML = list.length ? list.map(card).join('') : (rows.length ? '<p class="mmms__empty">No coins match. Try another search or filter.</p>' : '');
		}

		function setStatus(data, source) {
			var when = data.updated_at ? new Date(data.updated_at * 1000).toLocaleString([], { hour: 'numeric', minute: '2-digit', month: 'short', day: 'numeric' }) : '';
			status.innerHTML = rows.length
				? '<span class="mmms__dot' + (data.status === 'stale' ? ' is-stale' : '') + '"></span>' + rows.length + ' ASIC-mineable coins · ' + (data.status === 'stale' ? 'last available data from ' : 'updated ') + esc(when) + ' · data by <a href="https://www.coingecko.com/" target="_blank" rel="noopener">CoinGecko</a>' + (source ? ' ' + esc(source) : '')
				: 'Market data is unavailable right now. Please try again in a few minutes.';
		}

		function fromBrowser() {
			var ids = (cfg.coins || []).map(function (c) { return c.id; }).join(',');
			var url = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=' + encodeURIComponent(ids) + '&order=market_cap_desc&per_page=250&page=1&sparkline=true&price_change_percentage=24h,7d,30d,1y';
			return fetch(url, { headers: { Accept: 'application/json' } }).then(function (r) { return r.ok ? r.json() : null; }).then(function (list) {
				if (!Array.isArray(list) || !list.length) { return null; }
				var coins = {};
				list.forEach(function (c) {
					var sp = (c.sparkline_in_7d && c.sparkline_in_7d.price) || [];
					var step = sp.length / 42;
					coins[c.id] = {
						image: c.image, price: c.current_price, mcap: c.market_cap, rank: c.market_cap_rank, volume: c.total_volume,
						ch24h: c.price_change_percentage_24h_in_currency != null ? c.price_change_percentage_24h_in_currency : c.price_change_percentage_24h,
						ch7d: c.price_change_percentage_7d_in_currency, ch30d: c.price_change_percentage_30d_in_currency, ch1y: c.price_change_percentage_1y_in_currency,
						ath: c.ath, athPct: c.ath_change_percentage, athDate: (c.ath_date || '').slice(0, 10), circ: c.circulating_supply, max: c.max_supply,
						spark: sp.length > 42 ? Array.from({ length: 42 }, function (_, i) { return sp[Math.floor(i * step)]; }) : sp
					};
				});
				return { status: 'live', updated_at: Math.round(Date.now() / 1000), coins: coins };
			}).catch(function () { return null; });
		}

		function load() {
			return fetch(cfg.endpoint, { headers: { Accept: 'application/json' } })
				.then(function (r) { return r.ok ? r.json() : null; })
				.catch(function () { return null; })
				.then(function (data) {
					if (data && data.coins && Object.keys(data.coins).length) { return data; }
					return fromBrowser();
				})
				.then(function (data) {
					if (!data) { rows = []; setStatus({}, ''); render(); return; }
					build(data.coins);
					setStatus(data, '');
					render();
				});
		}

		search.addEventListener('input', function () { state.q = search.value.trim(); render(); });
		algoSel.addEventListener('change', function () { state.algo = algoSel.value; render(); });
		sortSel.addEventListener('change', function () { state.sort = sortSel.value; render(); });
		chips.forEach(function (chip) {
			chip.addEventListener('click', function () {
				state.band = chip.getAttribute('data-mmms-band');
				chips.forEach(function (c) { c.classList.toggle('is-active', c === chip); c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
				render();
			});
		});
		grid.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-toggle]');
			if (!btn) { return; }
			var id = btn.getAttribute('data-toggle');
			state.open = state.open === id ? '' : id;
			render();
			var again = grid.querySelector('[data-toggle="' + id.replace(/"/g, '') + '"]');
			if (again) { again.focus({ preventScroll: true }); }
		});

		load();
		setInterval(function () { if (!document.hidden) { load(); } }, 10 * 60 * 1000);
	}

	function boot() { document.querySelectorAll('[data-mmms]').forEach(init); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
