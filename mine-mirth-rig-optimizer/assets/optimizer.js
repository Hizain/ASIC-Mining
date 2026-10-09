/*!
 * Mine Mirth Rig Optimizer.
 * Everything runs in the browser: live network data in, rig analysis out.
 */
(function () {
	'use strict';

	var MODES = [
		{ key: 'eco', label: 'Eco', note: 'underclocked', h: 0.85, p: 0.72 },
		{ key: 'stock', label: 'Stock', note: 'factory settings', h: 1, p: 1 },
		{ key: 'turbo', label: 'Turbo', note: 'overclocked', h: 1.10, p: 1.20 }
	];
	var BEST_JTH = 13.5;   // best mainstream air-cooled efficiency today (S21 XP class)
	var WORST_JTH = 38;    // efficiency where a rig scores zero on that part

	function esc(s) {
		return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
	}
	function num(v) { var n = parseFloat(v); return isFinite(n) ? n : 0; }
	function clamp(v, lo, hi) { return Math.max(lo, Math.min(hi, v)); }

	function init(root) {
		var cfg = {};
		try { cfg = JSON.parse(root.getAttribute('data-config') || '{}'); } catch (e) { cfg = {}; }
		var cur = cfg.currency || '$';
		var form = root.querySelector('[data-mmro-form]');
		var out = root.querySelector('[data-mmro-results]');
		var status = root.querySelector('[data-mmro-status]');
		var select = root.querySelector('[data-mmro-model]');
		var presets = cfg.presets || [];
		var f = function (name) { return form.elements[name]; };

		function money(v, d) {
			var neg = v < 0;
			var s = Math.abs(v).toLocaleString(undefined, { minimumFractionDigits: d == null ? 2 : d, maximumFractionDigits: d == null ? 2 : d });
			return (neg ? '−' : '') + cur + s;
		}
		function fixed(v, d) { return Number(v).toLocaleString(undefined, { minimumFractionDigits: d, maximumFractionDigits: d }); }

		/* Model list */
		var groups = { store: 'From our store', common: 'Popular miners' };
		['store', 'common'].forEach(function (g) {
			var items = presets.filter(function (p) { return p.group === g; });
			if (!items.length) { return; }
			var og = document.createElement('optgroup');
			og.label = groups[g];
			items.forEach(function (p) {
				var o = document.createElement('option');
				o.value = String(presets.indexOf(p));
				o.textContent = p.name + ' — ' + fixed(p.th, 0) + ' TH/s, ' + fixed(p.w, 0) + ' W';
				og.appendChild(o);
			});
			select.appendChild(og);
		});
		var custom = document.createElement('option');
		custom.value = 'custom';
		custom.textContent = 'My own numbers';
		select.appendChild(custom);

		function applyPreset() {
			var p = presets[parseInt(select.value, 10)];
			if (p) { f('th').value = p.th; f('w').value = p.w; }
		}
		select.addEventListener('change', function () { applyPreset(); run(); });
		['th', 'w'].forEach(function (n) {
			f(n).addEventListener('input', function () { select.value = 'custom'; });
		});

		/* URL prefill, e.g. ?hashrate=200&power=3500&elec=0.08 (avoids WordPress's own query names such as w and m). */
		var qs = new URLSearchParams(location.search);
		applyPreset();
		var fromUrl = { hashrate: 'th', power: 'w', qty: 'qty', elec: 'elec', temp: 'temp', uptime: 'uptime', pool: 'pool', cooling: 'cooling', host: 'host' };
		Object.keys(fromUrl).forEach(function (k) {
			if (qs.has(k) && isFinite(parseFloat(qs.get(k)))) { f(fromUrl[k]).value = qs.get(k); if (k === 'hashrate' || k === 'power') { select.value = 'custom'; } }
		});

		/* Live network data: the theme's cached endpoint first, then the public APIs. */
		function getJSON(url) {
			return fetch(url, { headers: { Accept: 'application/json' } }).then(function (r) { return r.ok ? r.json() : null; }).catch(function () { return null; });
		}
		function setMarket(price, hashHs, fees, subsidy, source) {
			if (price) { f('price').value = Math.round(price); }
			if (hashHs) { f('net').value = (hashHs / 1e18).toFixed(1); }
			if (fees != null && isFinite(fees) && fees >= 0) { f('fees').value = Number(fees).toFixed(3); }
			if (subsidy) { f('subsidy').value = subsidy; }
			status.textContent = 'Live data · BTC ' + money(price || num(f('price').value), 0) + ' · network ' + fixed(num(f('net').value), 0) + ' EH/s · ' + source + ' · ' + new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
			status.classList.remove('is-error');
			run();
		}
		function loadMarket() {
			var theme = cfg.market ? getJSON(cfg.market) : Promise.resolve(null);
			return theme.then(function (d) {
				if (d && d.price && d.hashrate) {
					return setMarket(d.price, d.hashrate, d.fees, d.halving && d.halving.subsidy, 'Mine Mirth');
				}
				var cc = 'usd';
				return Promise.all([
					getJSON('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=' + cc),
					getJSON('https://mempool.space/api/v1/mining/hashrate/3d'),
					getJSON('https://mempool.space/api/blocks/tip/height')
				]).then(function (r) {
					var price = r[0] && r[0].bitcoin ? r[0].bitcoin[cc] : null;
					var hash = r[1] ? r[1].currentHashrate : null;
					var height = typeof r[2] === 'number' ? r[2] : null;
					if (price && hash) {
						return setMarket(price, hash, null, height != null ? 50 / Math.pow(2, Math.floor(height / 210000)) : null, 'CoinGecko and mempool.space');
					}
					status.textContent = 'Live data is unavailable right now. Enter the Bitcoin price and network hashrate under “Network data”.';
					status.classList.add('is-error');
					form.querySelector('.mmro__adv').open = true;
					run();
				});
			});
		}

		/* The analysis */
		function analyse() {
			var v = {
				qty: Math.max(1, Math.round(num(f('qty').value))),
				th: num(f('th').value), w: num(f('w').value),
				elec: num(f('elec').value), temp: num(f('temp').value),
				uptime: clamp(num(f('uptime').value), 1, 100) / 100,
				pool: clamp(num(f('pool').value), 0, 50) / 100,
				cooling: clamp(num(f('cooling').value), 0, 200) / 100,
				host: num(f('host').value),
				price: num(f('price').value), net: num(f('net').value),
				reward: num(f('subsidy').value) + num(f('fees').value)
			};
			if (!v.th || !v.w || !v.price || !v.net || !v.reward) { return null; }

			var derate = v.temp > 30 ? clamp(1 - 0.015 * (v.temp - 30), 0.6, 1) : 1;
			function calc(mode, elec, cooling) {
				var hash = v.th * mode.h * derate * v.qty;
				var kw = v.w * mode.p * v.qty * (1 + cooling) / 1000;
				var btc = 144 * v.reward * hash / (v.net * 1e6) * v.uptime * (1 - v.pool);
				var revenue = btc * v.price;
				var kwh = kw * 24 * v.uptime;
				var cost = kwh * elec;
				return {
					mode: mode, hash: hash, kw: kw, btc: btc, revenue: revenue, cost: cost, profit: revenue - cost,
					jth: (v.w * mode.p) / (v.th * mode.h * derate),
					breakeven: kwh ? revenue / kwh : 0
				};
			}
			var rows = MODES.map(function (m) { return calc(m, v.elec, v.cooling); });
			var allowed = rows.filter(function (r) { return !(r.mode.key === 'turbo' && v.temp > 35); });
			var best = allowed.reduce(function (a, b) { return b.profit > a.profit ? b : a; });
			var stock = rows[1];
			var hosted = MODES.map(function (m) { return calc(m, v.host, 0); }).reduce(function (a, b) { return b.profit > a.profit ? b : a; });

			/* Score out of 100 */
			var sEff = 40 * clamp((WORST_JTH - stock.jth) / (WORST_JTH - BEST_JTH), 0, 1);
			var margin = best.revenue ? best.profit / best.revenue : -1;
			var sMargin = 40 * clamp((margin + 0.2) / 0.8, 0, 1);
			var sUp = 10 * clamp((v.uptime - 0.85) / 0.15, 0, 1);
			var sTemp = 10 * clamp((40 - v.temp) / 15, 0, 1);
			var score = Math.round(sEff + sMargin + sUp + sTemp);

			/* Recommendations, most valuable first */
			var tips = [];
			var month = 30.4;
			if (best.mode.key !== 'stock' && best.profit - stock.profit > 0.005) {
				tips.push({ level: 'good', value: (best.profit - stock.profit) * month, title: 'Switch to ' + best.mode.label + ' mode', text: best.mode.label + ' (' + best.mode.note + ') is ' + money(best.profit - stock.profit) + ' a day better than factory settings at your power price. Most miners can change this in their firmware.' });
			}
			if (best.profit < 0) {
				tips.push({ level: 'bad', value: 1e9, title: 'This rig loses money at ' + money(v.elec, 3) + '/kWh', text: 'It breaks even at ' + money(best.breakeven, 3) + '/kWh. ' + (hosted.profit > 0 ? 'At a hosting rate of ' + money(v.host, 3) + '/kWh it would make ' + money(hosted.profit * month, 0) + ' a month instead.' : 'Lower-cost power, hosting or a more efficient miner would turn it around.') , cta: 'hosting' });
			} else if (hosted.profit - best.profit > 0.01) {
				tips.push({ level: 'warn', value: (hosted.profit - best.profit) * month, title: 'Hosting would earn ' + money((hosted.profit - best.profit) * month, 0) + ' more a month', text: 'At ' + money(v.host, 3) + '/kWh with no cooling overhead, the same miners make ' + money(hosted.profit * month, 0) + ' a month against ' + money(best.profit * month, 0) + ' where they run now. Hosting also removes the noise and heat.', cta: 'hosting' });
			}
			if (stock.jth > 22) {
				var perTh = (v.w / v.th) - 15;
				tips.push({ level: 'warn', value: (perTh / (v.w / v.th)) * stock.cost * month, title: 'Older-generation efficiency: ' + fixed(stock.jth, 1) + ' J/TH', text: 'Current miners run at about 13.5–17.5 J/TH. At 15 J/TH the same hashrate would use ' + Math.round(100 * perTh / (v.w / v.th)) + '% less power, saving about ' + money((perTh / (v.w / v.th)) * stock.cost * month, 0) + ' a month in electricity.', cta: 'shop' });
			} else if (stock.jth <= 17.5) {
				tips.push({ level: 'good', value: 0, title: 'Efficient hardware: ' + fixed(stock.jth, 1) + ' J/TH', text: 'Your miners are in the current efficiency class, so they will stay profitable longer as network difficulty rises.' });
			}
			if (v.temp > 30) {
				tips.push({ level: v.temp > 35 ? 'bad' : 'warn', value: stock.revenue * (1 - derate) * month, title: 'Heat is costing hashrate', text: 'At ' + fixed(v.temp, 0) + ' °C intake air, miners typically throttle about ' + Math.round((1 - derate) * 100) + '% (' + money(stock.revenue / derate * (1 - derate) * month, 0) + ' a month). Better airflow or a cooler room would recover it.' + (v.temp > 35 ? ' Avoid turbo mode at this temperature.' : '') });
			}
			if (v.uptime < 0.97) {
				var perPct = best.revenue / v.uptime * 0.01 * month;
				tips.push({ level: 'warn', value: perPct * (97 - v.uptime * 100), title: 'Uptime is ' + fixed(v.uptime * 100, 1) + '%', text: 'Each 1% of uptime is worth about ' + money(perPct, 0) + ' a month. Check power stability, network drops and pool failover settings.' });
			}
			var saved = best.revenue / (1 - v.pool) * (v.pool - 0.015) * month;
			if (v.pool > 0.02 && saved >= 1) {
				tips.push({ level: 'warn', value: saved, title: 'Pool fee of ' + fixed(v.pool * 100, 1) + '% is high', text: 'Many large pools charge 1–2%. At 1.5% you would keep about ' + money(saved, 0) + ' more a month.' });
			}
			if (v.cooling > 0.15) {
				tips.push({ level: 'warn', value: stock.cost * (v.cooling / (1 + v.cooling)) * month, title: 'Cooling adds ' + Math.round(v.cooling * 100) + '% to your power bill', text: 'That is about ' + money(stock.cost * (v.cooling / (1 + v.cooling)) * month, 0) + ' a month. Venting hot air outside, or hosting, usually costs far less than air conditioning.' });
			}
			tips.sort(function (a, b) { return (b.level === 'bad') - (a.level === 'bad') || b.value - a.value; });

			return { v: v, rows: rows, best: best, stock: stock, hosted: hosted, score: score, derate: derate, tips: tips, month: month };
		}

		function render(r) {
			if (!r) {
				out.innerHTML = '<div class="mmro__empty">Enter your miner’s hashrate and power, and the Bitcoin network data, to see the analysis.</div>';
				return;
			}
			var grade = r.score >= 80 ? 'Strong' : r.score >= 60 ? 'Good' : r.score >= 40 ? 'Needs work' : 'Unprofitable setup';
			var tone = r.score >= 60 ? 'good' : r.score >= 40 ? 'warn' : 'bad';
			var circ = 2 * Math.PI * 52;
			var html = '';
			html += '<div class="mmro__top">';
			html += '<div class="mmro__score is-' + tone + '"><svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="60" r="52" class="mmro__ring-bg"/><circle cx="60" cy="60" r="52" class="mmro__ring" stroke-dasharray="' + circ.toFixed(1) + '" stroke-dashoffset="' + (circ * (1 - r.score / 100)).toFixed(1) + '"/></svg><div><strong>' + r.score + '</strong><span>/ 100</span></div></div>';
			html += '<div class="mmro__verdict"><small>Rig score</small><h3>' + esc(grade) + '</h3><p>Best mode: <b>' + esc(r.best.mode.label) + '</b> · ' + money(r.best.profit) + ' a day, ' + money(r.best.profit * r.month, 0) + ' a month' + (r.v.qty > 1 ? ' for ' + r.v.qty + ' miners' : '') + '.</p></div>';
			html += '</div>';

			html += '<div class="mmro__kpis">';
			html += kpi('Profit per day', money(r.best.profit), r.best.profit >= 0 ? 'good' : 'bad');
			html += kpi('Break-even power price', money(r.best.breakeven, 3) + '/kWh', r.v.elec <= r.best.breakeven ? 'good' : 'bad');
			html += kpi('Efficiency', fixed(r.stock.jth, 1) + ' J/TH', r.stock.jth <= 17.5 ? 'good' : r.stock.jth <= 25 ? 'warn' : 'bad');
			html += kpi('Hashprice', money(r.stock.revenue / r.stock.hash, 4) + ' /TH/day', '');
			html += '</div>';

			html += '<h4 class="mmro__h">Power modes</h4><div class="mmro__table-wrap"><table class="mmro__table"><thead><tr><th>Mode</th><th>Hashrate</th><th>Power</th><th>Revenue / day</th><th>Power cost / day</th><th>Profit / day</th><th>Profit / month</th></tr></thead><tbody>';
			r.rows.forEach(function (row) {
				var isBest = row === r.best;
				var blocked = row.mode.key === 'turbo' && r.v.temp > 35;
				html += '<tr class="' + (isBest ? 'is-best' : '') + (blocked ? ' is-blocked' : '') + '"><th scope="row">' + esc(row.mode.label) + (isBest ? ' <span class="mmro__tag">Best</span>' : '') + (blocked ? ' <span class="mmro__tag is-bad">Too hot</span>' : '') + '<small>' + esc(row.mode.note) + '</small></th>';
				html += '<td>' + fixed(row.hash, row.hash < 100 ? 1 : 0) + ' TH/s</td><td>' + fixed(row.kw, 2) + ' kW</td><td>' + money(row.revenue) + '</td><td>' + money(row.cost) + '</td><td class="' + (row.profit >= 0 ? 'is-pos' : 'is-neg') + '">' + money(row.profit) + '</td><td class="' + (row.profit >= 0 ? 'is-pos' : 'is-neg') + '">' + money(row.profit * r.month, 0) + '</td></tr>';
			});
			html += '</tbody></table></div>';

			var home = r.best.profit * r.month, host = r.hosted.profit * r.month;
			var max = Math.max(Math.abs(home), Math.abs(host), 1);
			html += '<h4 class="mmro__h">Home vs hosting, per month</h4><div class="mmro__vs">';
			html += bar('Where it runs now (' + money(r.v.elec, 3) + '/kWh)', home, max);
			html += bar('Hosted (' + money(r.v.host, 3) + '/kWh)', host, max);
			html += '</div>';

			html += '<h4 class="mmro__h">Recommendations</h4><ol class="mmro__tips">';
			if (!r.tips.length) { r.tips.push({ level: 'good', title: 'Your setup is well tuned', text: 'No changes stand out at today’s price and difficulty. Check again after the next difficulty adjustment.' }); }
			r.tips.forEach(function (t) {
				var cta = t.cta === 'hosting' && cfg.hosting ? ' <a href="' + esc(cfg.hosting) + '">Explore hosting →</a>' : t.cta === 'shop' && cfg.shop ? ' <a href="' + esc(cfg.shop) + '">See efficient miners →</a>' : '';
				html += '<li class="is-' + t.level + '"><strong>' + esc(t.title) + '</strong><p>' + esc(t.text) + cta + '</p></li>';
			});
			html += '</ol>';
			html += '<p class="mmro__fine">Mining ' + fixed(r.best.btc, 8) + ' BTC a day in ' + esc(r.best.mode.label) + ' mode at ' + money(r.v.price, 0) + ' per BTC and ' + fixed(r.v.net, 0) + ' EH/s network hashrate' + (r.derate < 1 ? ', with ' + Math.round((1 - r.derate) * 100) + '% heat throttling' : '') + '.</p>';
			out.innerHTML = html;
		}
		function kpi(label, value, tone) {
			return '<div class="mmro__kpi' + (tone ? ' is-' + tone : '') + '"><small>' + esc(label) + '</small><strong>' + esc(value) + '</strong></div>';
		}
		function bar(label, value, max) {
			var pct = Math.round(Math.abs(value) / max * 100);
			return '<div class="mmro__bar"><span>' + esc(label) + '</span><div class="mmro__bar-track"><i class="' + (value >= 0 ? 'is-pos' : 'is-neg') + '" style="width:' + pct + '%"></i></div><b class="' + (value >= 0 ? 'is-pos' : 'is-neg') + '">' + esc(money(value, 0)) + '</b></div>';
		}

		var t;
		function run() { clearTimeout(t); t = setTimeout(function () { render(analyse()); }, 120); }
		form.addEventListener('input', run);
		form.addEventListener('change', run);
		loadMarket();
	}

	function boot() { document.querySelectorAll('[data-mmro]').forEach(init); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
