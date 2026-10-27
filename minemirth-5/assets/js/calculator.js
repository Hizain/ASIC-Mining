/*!
 * Mine Mirth — profitability & hosting calculators.
 *
 * BTC/day = hashrate(H/s) × 86,400 × (subsidy + fees) ÷ (difficulty × 2^32)
 * Live price/difficulty/subsidy/fees come from MineMirth.market unless the
 * visitor typed their own values.
 */
(function () {
	'use strict';

	var MM = window.MineMirth || {};
	var TWO32 = 4294967296;
	var DAYS_MONTH = 30.4167;

	function t(key, fallback) {
		var i18n = (MM.config && MM.config.i18n) || {};
		return i18n[key] || fallback;
	}

	function num(v, d) {
		var n = parseFloat(String(v).replace(',', '.'));
		return isFinite(n) ? n : (d || 0);
	}

	function money(value, sym, locale) {
		var neg = value < 0;
		var abs = Math.abs(value);
		var digits = abs >= 1000 ? 0 : 2;
		var str;
		try { str = abs.toLocaleString(locale || undefined, { minimumFractionDigits: digits, maximumFractionDigits: digits }); }
		catch (e) { str = abs.toFixed(digits); }
		return (neg ? '−' : '') + sym + str;
	}

	function liveValues() {
		var m = MM.market && MM.market.get ? MM.market.get() : null;
		if (!m) { return null; }
		return {
			price: m.price,
			difficulty: m.difficulty ? m.difficulty / 1e12 : null,
			reward: m.halving && m.halving.subsidy ? m.halving.subsidy : null,
			fees: m.fees,
			stale: !!m.is_stale || m.status !== 'live'
		};
	}

	/* ------------------------------------------------------------------
	 * Profitability calculator
	 * ---------------------------------------------------------------- */
	function Calc(root) {
		var sym = root.getAttribute('data-currency') || '$';
		var locale = root.getAttribute('data-locale') || undefined;
		var inputs = {};
		Array.prototype.forEach.call(root.querySelectorAll('[data-k]'), function (i) { inputs[i.getAttribute('data-k')] = i; });
		var outs = {};
		Array.prototype.forEach.call(root.querySelectorAll('[data-out]'), function (o) { outs[o.getAttribute('data-out')] = o; });
		var preset = root.querySelector('[data-mm-calc-preset]');
		var chart = root.querySelector('[data-mm-calc-chart]');
		var status = root.querySelector('[data-mm-calc-status]');
		var statusText = root.querySelector('[data-mm-calc-status-text]');
		var dirty = {};
		var live = { price: null, difficulty: null, reward: 3.125, fees: 0 };

		Object.keys(inputs).forEach(function (k) {
			inputs[k].addEventListener('input', function () {
				if (inputs[k].hasAttribute('data-live')) { dirty[k] = true; }
				if ((k === 'hashrate' || k === 'power') && preset) { preset.value = ''; }
				compute();
			});
		});
		if (preset) {
			preset.addEventListener('change', function () {
				if (!preset.value) { return; }
				try {
					var p = JSON.parse(preset.value);
					if (inputs.hashrate) { inputs.hashrate.value = p.th; }
					if (inputs.power) { inputs.power.value = p.w; }
					if (inputs.cost && p.cost) { inputs.cost.value = Math.round(p.cost); }
				} catch (e) { /* ignore */ }
				compute();
			});
		}

		function val(k, d) { return inputs[k] ? num(inputs[k].value, d) : d; }
		function out(k, text, negative) {
			if (!outs[k]) { return; }
			outs[k].textContent = text;
			outs[k].classList.toggle('is-negative', !!negative);
		}

		function syncLive() {
			var lv = liveValues();
			if (!lv || (lv.price === null && lv.difficulty === null)) {
				if (status && MM.market && MM.market.get && MM.market.get()) {
					status.classList.add('is-stale');
					if (statusText) { statusText.textContent = t('noLive', 'Live data unavailable, enter values'); }
				}
				return;
			}
			['price', 'difficulty', 'reward', 'fees'].forEach(function (k) {
				if (lv[k] === null || lv[k] === undefined) { return; }
				live[k] = lv[k];
				if (inputs[k] && !dirty[k]) {
					inputs[k].value = k === 'price' ? Math.round(lv[k]) : (k === 'difficulty' ? lv[k].toFixed(2) : +lv[k].toFixed(4));
				}
			});
			if (status) {
				status.classList.toggle('is-stale', lv.stale);
				if (statusText && lv.stale) { statusText.textContent = t('lastKnown', 'Last known data'); }
			}
		}

		function compute() {
			var price = inputs.price ? num(inputs.price.value, live.price || 0) : (live.price || 0);
			var diffT = inputs.difficulty ? num(inputs.difficulty.value, live.difficulty || 0) : (live.difficulty || 0);
			var reward = inputs.reward ? num(inputs.reward.value, live.reward) : live.reward;
			var fees = inputs.fees ? num(inputs.fees.value, live.fees || 0) : (live.fees || 0);
			var th = val('hashrate', 0);
			var watts = val('power', 0);
			var kwh = val('kwh', 0);
			var pool = val('pool', 0) / 100;
			var cost = val('cost', 0);
			var hosting = val('hosting', 0);
			var uptime = inputs.uptime ? Math.min(1, Math.max(0, val('uptime', 100) / 100)) : 1;
			var growth = val('growth', 0) / 100;

			if (!price || !diffT) {
				['profitDay', 'revenueDay', 'powerDay', 'profitMonth', 'profitYear', 'breakeven', 'btcDay', 'year1'].forEach(function (k) { out(k, '—'); });
				out('efficiency', th ? (watts / th).toFixed(1) + ' J/TH' : '—');
				return;
			}

			var difficulty = diffT * 1e12;
			var btcDay = th * 1e12 * 86400 * (reward + fees) / (difficulty * TWO32) * uptime;
			var revenueDay = btcDay * price * (1 - pool);
			var powerDay = watts / 1000 * 24 * kwh * uptime;
			var hostingDay = hosting / DAYS_MONTH;
			var profitDay = revenueDay - powerDay - hostingDay;

			out('profitDay', money(profitDay, sym, locale), profitDay < 0);
			out('revenueDay', money(revenueDay, sym, locale));
			out('powerDay', money(powerDay + hostingDay, sym, locale));
			out('profitMonth', money(profitDay * DAYS_MONTH, sym, locale), profitDay < 0);
			out('profitYear', money(profitDay * 365, sym, locale), profitDay < 0);
			out('btcDay', btcDay.toFixed(8) + ' BTC');
			out('efficiency', th ? (watts / th).toFixed(1) + ' J/TH' : '—');

			// 12-month projection with monthly difficulty growth.
			var cumulative = -cost;
			var series = [];
			var breakevenDay = null;
			for (var m = 1; m <= 12; m++) {
				var factor = Math.pow(1 + growth, m - 0.5);
				var rev = revenueDay / factor;
				var monthProfit = (rev - powerDay - hostingDay) * DAYS_MONTH;
				var before = cumulative;
				cumulative += monthProfit;
				if (breakevenDay === null && before < 0 && cumulative >= 0 && monthProfit > 0) {
					breakevenDay = Math.round((m - 1) * DAYS_MONTH + (-before / monthProfit) * DAYS_MONTH);
				}
				series.push(cumulative);
			}
			if (cost <= 0) {
				out('breakeven', '—');
			} else if (breakevenDay !== null) {
				out('breakeven', t('days', '%d days').replace('%d', breakevenDay));
			} else if (profitDay > 0) {
				var simple = Math.round(cost / profitDay);
				out('breakeven', simple > 3650 ? t('tenYears', '10+ years') : '~' + t('days', '%d days').replace('%d', simple));
			} else {
				out('breakeven', t('notProfitable', 'Not at current values'), true);
			}
			out('year1', money(series[11], sym, locale), series[11] < 0);
			drawChart(series);
			root.classList.toggle('is-unprofitable', profitDay < 0);
		}

		function drawChart(series) {
			if (!chart) { return; }
			var ns = 'http://www.w3.org/2000/svg';
			Array.prototype.slice.call(chart.querySelectorAll('rect,line')).forEach(function (n) { n.remove(); });
			var max = Math.max.apply(null, series.concat([0]));
			var min = Math.min.apply(null, series.concat([0]));
			var span = (max - min) || 1;
			var H = 116, W = 360, gap = 6;
			var bw = (W - gap * 11) / 12;
			var zeroY = 2 + (max / span) * H;
			series.forEach(function (v, i) {
				var h = Math.max(1.5, Math.abs(v) / span * H);
				var y = v >= 0 ? zeroY - h : zeroY;
				var r = document.createElementNS(ns, 'rect');
				r.setAttribute('x', (i * (bw + gap)).toFixed(1));
				r.setAttribute('y', y.toFixed(1));
				r.setAttribute('width', bw.toFixed(1));
				r.setAttribute('height', h.toFixed(1));
				r.setAttribute('rx', '3');
				r.setAttribute('class', v >= 0 ? 'is-pos' : 'is-neg');
				chart.appendChild(r);
			});
			var line = document.createElementNS(ns, 'line');
			line.setAttribute('x1', '0'); line.setAttribute('x2', String(W));
			line.setAttribute('y1', zeroY.toFixed(1)); line.setAttribute('y2', zeroY.toFixed(1));
			line.setAttribute('class', 'mm-calc__zero');
			chart.appendChild(line);
		}

		syncLive();
		compute();
		if (MM.on) { MM.on('market', function () { syncLive(); compute(); }); }
	}

	/* ------------------------------------------------------------------
	 * Hosting cost scenario
	 * ---------------------------------------------------------------- */
	function HostingCalc(root) {
		var sym = root.getAttribute('data-currency') || '$';
		function v(k) { var i = root.querySelector('[data-k="' + k + '"]'); return i ? num(i.value, 0) : 0; }
		function o(k, t) { var n = root.querySelector('[data-out="' + k + '"]'); if (n) { n.textContent = t; } }
		function compute() {
			var miners = Math.max(0, v('miners'));
			var kwhMonth = miners * v('watts') / 1000 * 24 * DAYS_MONTH;
			var power = kwhMonth * v('kwh');
			var fixed = miners * v('fixed');
			o('power', money(power, sym));
			o('fixed', money(fixed, sym));
			o('total', money(power + fixed, sym));
			o('energy', Math.round(kwhMonth).toLocaleString() + ' kWh');
		}
		root.addEventListener('input', compute);
		compute();
	}

	function init(ctx) {
		ctx = ctx || document;
		Array.prototype.forEach.call(ctx.querySelectorAll('[data-mm-calc]'), function (n) {
			if (n.dataset.mmBound) { return; }
			n.dataset.mmBound = '1';
			Calc(n);
		});
		Array.prototype.forEach.call(ctx.querySelectorAll('[data-mm-hosting-calc]'), function (n) {
			if (n.dataset.mmBound) { return; }
			n.dataset.mmBound = '1';
			HostingCalc(n);
		});
	}

	if (MM.on) { MM.on('init', init); }
	window.MineMirthCalc = { init: init };
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { init(); }); } else { init(); }
})();
