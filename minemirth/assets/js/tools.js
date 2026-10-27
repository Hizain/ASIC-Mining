/**
 * Mine Mirth mining tools.
 *
 * Reads inputs marked [data-k], writes results into [data-o]. Market data arrives inline
 * (window.mmTools) and refreshes every five minutes from /wp-json/mine-mirth/v1/tools.
 */
(function () {
	'use strict';

	var D = window.mmTools || {};
	var T = window.mmToolsI18n || {};
	var API = window.mmToolsApi || '';
	var SYM = D.symbol || '$';
	var NBSP = ' ';
	var DAY = 86400;
	var MONTH_DAYS = 30.4375;
	var TWO32 = 4294967296;
	var instances = [];

	/* ------------------------------------------------------------------
	 * Formatting
	 * ---------------------------------------------------------------- */
	var nf = {};
	function fmt(v, d) {
		if (v === null || v === undefined || !isFinite(v)) { return '—'; }
		d = d === undefined ? 2 : d;
		var key = d;
		if (!nf[key]) { nf[key] = new Intl.NumberFormat(undefined, { minimumFractionDigits: d, maximumFractionDigits: d }); }
		return nf[key].format(v);
	}
	function smart(v) {
		var a = Math.abs(v);
		return fmt(v, a >= 1000 ? 0 : a >= 100 ? 1 : a >= 1 ? 2 : a >= 0.01 ? 3 : 4);
	}
	function money(v, d) {
		if (v === null || v === undefined || !isFinite(v)) { return '—'; }
		var a = Math.abs(v);
		d = d === undefined ? (a >= 10000 ? 0 : a >= 1 || a === 0 ? 2 : a >= 0.01 ? 3 : 4) : d;
		return (v < 0 ? '−' : '') + SYM + fmt(a, d);
	}
	function pct(v, d, sign) {
		if (v === null || v === undefined || !isFinite(v)) { return '—'; }
		d = d === undefined ? 1 : d;
		if (Math.abs(v) < 0.5 * Math.pow(10, -d)) { v = 0; }
		return (sign && v > 0 ? '+' : v < 0 ? '−' : '') + fmt(Math.abs(v), d) + '%';
	}
	function coin(v, sym) {
		if (!isFinite(v)) { return '—'; }
		var a = Math.abs(v);
		var d = a >= 1000 ? 0 : a >= 1 ? 3 : a > 0 ? Math.min(8, Math.max(3, Math.ceil(-Math.log10(a)) + 3)) : 2;
		return fmt(v, d) + (sym ? NBSP + sym : '');
	}
	function hashrate(hs) {
		if (!isFinite(hs) || hs <= 0) { return '—'; }
		var u = ['H/s', 'kH/s', 'MH/s', 'GH/s', 'TH/s', 'PH/s', 'EH/s', 'ZH/s'];
		var i = 0;
		while (hs >= 1000 && i < u.length - 1) { hs /= 1000; i++; }
		return smart(hs) + NBSP + u[i];
	}
	function duration(days) {
		if (!isFinite(days) || days <= 0) { return T.never || '—'; }
		if (days < 60) { return fmt(days, days < 10 ? 1 : 0) + NBSP + (T.days || 'days'); }
		if (days < 730) { return fmt(days / MONTH_DAYS, 1) + NBSP + (T.months || 'months'); }
		return fmt(days / 365.25, days > 36525 ? 0 : 1) + NBSP + (T.years || 'years');
	}
	function oneIn(p) {
		if (!(p > 0)) { return '—'; }
		var n = 1 / p;
		var s = n >= 1e9 ? fmt(n / 1e9, 1) + 'B' : n >= 1e6 ? fmt(n / 1e6, 1) + 'M' : n >= 1e4 ? fmt(n / 1e3, 0) + 'K' : fmt(n, n < 10 ? 1 : 0);
		return '1 in ' + s;
	}
	function dateLabel(ts, withYear) {
		return new Date(ts * 1000).toLocaleDateString(undefined, withYear ? { month: 'short', day: 'numeric', year: 'numeric' } : { month: 'short', day: 'numeric' });
	}
	function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
	function tone(v) { return v >= 0 ? 'is-up' : 'is-down'; }

	/* ------------------------------------------------------------------
	 * Mining maths
	 * ---------------------------------------------------------------- */
	// Expected BTC per TH/s per day for a difficulty (in T = 10^12) and a block reward.
	function btcPerThDay(diffT, reward) { return diffT > 0 ? 86400 * reward / (diffT * TWO32) : 0; }
	function btc() { return D.btc || {}; }
	function halvingTs() {
		var h = btc().halving, a = btc().adjustment;
		if (!h || !h.blocks_left) { return 0; }
		var bt = a && isFinite(a.change) ? 600 / (1 + a.change / 100) : 600;
		return Math.round(Date.now() / 1000 + h.blocks_left * bt);
	}

	/* ------------------------------------------------------------------
	 * SVG chart with hover read-out
	 * ---------------------------------------------------------------- */
	function niceStep(raw) {
		if (!(raw > 0)) { return 1; }
		var p = Math.pow(10, Math.floor(Math.log10(raw))), f = raw / p;
		return (f <= 1 ? 1 : f <= 2 ? 2 : f <= 2.5 ? 2.5 : f <= 5 ? 5 : 10) * p;
	}
	function chart(fig, series, o) {
		if (!fig) { return; }
		o = o || {};
		var plot = fig.querySelector('.mm-chart__plot');
		var legend = fig.querySelector('.mm-chart__legend');
		var pts = [];
		series.forEach(function (s) { s.points.forEach(function (p) { if (isFinite(p[1])) { pts.push(p); } }); });
		if (pts.length < 2) {
			plot.innerHTML = '<p class="mm-chart__empty">' + esc(o.empty || '') + '</p>';
			legend.innerHTML = '';
			return;
		}
		var W = Math.max(280, plot.clientWidth || 600), H = o.height || (W < 480 ? 190 : 240);
		var padL = 58, padR = 14, padT = 12, padB = 26;
		var xs = pts.map(function (p) { return p[0]; }), ys = pts.map(function (p) { return p[1]; });
		(o.lines || []).forEach(function (l) { ys.push(l.y); });
		if (o.zero) { ys.push(0); }
		var x0 = Math.min.apply(null, xs), x1 = Math.max.apply(null, xs);
		var y0 = Math.min.apply(null, ys), y1 = Math.max.apply(null, ys);
		if (y0 === y1) { y0 -= 1; y1 += 1; }
		var span = y1 - y0; y0 -= span * 0.04; y1 += span * 0.06;
		var step = niceStep((y1 - y0) / 4);
		y0 = Math.floor(y0 / step) * step; y1 = Math.ceil(y1 / step) * step;
		var X = function (x) { return padL + (x - x0) / ((x1 - x0) || 1) * (W - padL - padR); };
		var Y = function (y) { return padT + (1 - (y - y0) / (y1 - y0)) * (H - padT - padB); };
		var yf = o.yFmt || smart, xf = o.xFmt || function (x) { return String(Math.round(x)); };
		var svg = '<svg viewBox="0 0 ' + W + ' ' + H + '" width="100%" height="' + H + '" preserveAspectRatio="none" class="mm-chart__svg">';
		svg += '<defs><linearGradient id="mmg-' + o.id + '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--mm-primary)" stop-opacity=".32"/><stop offset="1" stop-color="var(--mm-primary)" stop-opacity="0"/></linearGradient></defs>';
		for (var gy = y0; gy <= y1 + step / 2; gy += step) {
			var py = Y(gy);
			svg += '<line class="mm-chart__grid" x1="' + padL + '" x2="' + (W - padR) + '" y1="' + py + '" y2="' + py + '"/><text class="mm-chart__ylabel" x="' + (padL - 8) + '" y="' + (py + 4) + '" text-anchor="end">' + esc(yf(gy)) + '</text>';
		}
		var ticks = W < 480 ? 3 : 5, xt = [];
		if (o.xStep) {
			var xs2 = o.xStep * Math.max(1, Math.ceil((x1 - x0) / o.xStep / ticks));
			for (var v = Math.ceil(x0 / xs2) * xs2; v <= x1; v += xs2) { xt.push(v); }
		} else {
			for (var t = 0; t <= ticks; t++) { xt.push(x0 + (x1 - x0) * t / ticks); }
		}
		xt.forEach(function (tx) {
			var px = X(tx);
			svg += '<text class="mm-chart__xlabel" x="' + px + '" y="' + (H - 6) + '" text-anchor="' + (px < padL + 20 ? 'start' : px > W - padR - 20 ? 'end' : 'middle') + '">' + esc(xf(tx)) + '</text>';
		});
		if (o.zero && y0 < 0 && y1 > 0) { svg += '<line class="mm-chart__zero" x1="' + padL + '" x2="' + (W - padR) + '" y1="' + Y(0) + '" y2="' + Y(0) + '"/>'; }
		(o.lines || []).forEach(function (l) { svg += '<line class="mm-chart__ref" x1="' + padL + '" x2="' + (W - padR) + '" y1="' + Y(l.y) + '" y2="' + Y(l.y) + '"/><text class="mm-chart__reflabel" x="' + (W - padR) + '" y="' + (Y(l.y) - 6) + '" text-anchor="end">' + esc(l.label) + '</text>'; });
		(o.markers || []).forEach(function (m) { if (m.x >= x0 && m.x <= x1) { svg += '<line class="mm-chart__marker" x1="' + X(m.x) + '" x2="' + X(m.x) + '" y1="' + padT + '" y2="' + (H - padB) + '"/><text class="mm-chart__reflabel" x="' + (X(m.x) + 4) + '" y="' + (padT + 12) + '">' + esc(m.label) + '</text>'; } });
		series.forEach(function (s, i) {
			var p = s.points.filter(function (q) { return isFinite(q[1]); });
			if (p.length < 2) { return; }
			var d = p.map(function (q, j) { return (j ? 'L' : 'M') + X(q[0]).toFixed(1) + ' ' + Y(q[1]).toFixed(1); }).join(' ');
			if (s.area) { svg += '<path class="mm-chart__area" d="' + d + ' L' + X(p[p.length - 1][0]).toFixed(1) + ' ' + Y(y0).toFixed(1) + ' L' + X(p[0][0]).toFixed(1) + ' ' + Y(y0).toFixed(1) + ' Z" fill="url(#mmg-' + o.id + ')"/>'; }
			svg += '<path class="mm-chart__line mm-chart__line--' + (s.cls || i) + (s.dashed ? ' is-dashed' : '') + '" d="' + d + '"/>';
		});
		svg += '<line class="mm-chart__cursor" x1="0" x2="0" y1="' + padT + '" y2="' + (H - padB) + '" hidden/></svg><div class="mm-chart__tip" hidden></div>';
		plot.innerHTML = svg;
		legend.innerHTML = series.length > 1 || o.legend ? series.map(function (s, i) { return '<span class="mm-chart__key mm-chart__key--' + (s.cls || i) + (s.dashed ? ' is-dashed' : '') + '">' + esc(s.name) + '</span>'; }).join('') : '';
		var cursor = plot.querySelector('.mm-chart__cursor'), tip = plot.querySelector('.mm-chart__tip'), el = plot.querySelector('svg');
		var main = series[0].points.filter(function (q) { return isFinite(q[1]); });
		function move(ev) {
			var r = el.getBoundingClientRect();
			var x = x0 + ((ev.clientX - r.left) / r.width * W - padL) / (W - padL - padR) * (x1 - x0);
			var best = main[0], bd = Infinity;
			main.forEach(function (q) { var dd = Math.abs(q[0] - x); if (dd < bd) { bd = dd; best = q; } });
			var px = X(best[0]);
			cursor.setAttribute('x1', px); cursor.setAttribute('x2', px); cursor.hidden = false;
			var rows = series.map(function (s) {
				var q = s.points.filter(function (z) { return z[0] === best[0]; })[0];
				return q ? '<span>' + esc(s.name) + ': <b>' + esc(yf(q[1])) + '</b></span>' : '';
			}).join('');
			tip.innerHTML = '<strong>' + esc(xf(best[0], true)) + '</strong>' + rows;
			tip.hidden = false;
			var left = px / W * r.width;
			tip.style.left = Math.min(Math.max(left, 70), r.width - 70) + 'px';
		}
		el.addEventListener('pointermove', move);
		el.addEventListener('pointerdown', move);
		el.addEventListener('pointerleave', function () { cursor.hidden = true; tip.hidden = true; });
	}

	/* ------------------------------------------------------------------
	 * Miner list (store catalog + presets)
	 * ---------------------------------------------------------------- */
	function minerList(algo) {
		var store = (D.catalog || []).filter(function (m) { return (algo === 'any' || m.algo === algo) && m.hash > 0 && m.watts > 0; });
		store.sort(function (a, b) { return b.hash - a.hash; });
		var presets = algo === 'sha256' || algo === 'any' ? (D.presets || []).filter(function (p) {
			return !store.some(function (m) { return m.name.toLowerCase() === p.name.toLowerCase(); });
		}).map(function (p) { return { name: p.name, hash: p.th * 1e12, th: p.th, watts: p.watts, price: p.price || 0, preset: true, algo: 'sha256' }; }) : [];
		return { store: store, presets: presets };
	}
	function fillMiners(select, algo) {
		var l = minerList(algo || 'sha256');
		var keep = select.value;
		var html = '<option value="">' + esc(T.custom || 'Custom miner') + '</option>';
		var opt = function (m, key) {
			return '<option value="' + key + '">' + esc(m.name + (m.th ? ' · ' + hashrate(m.hash) : '') + (m.stock === 'outofstock' ? ' (sold out)' : '')) + '</option>';
		};
		if (l.store.length) { html += '<optgroup label="' + esc(T.inStore || 'In our store') + '">' + l.store.map(function (m) { return opt(m, 'p' + m.id); }).join('') + '</optgroup>'; }
		if (l.presets.length) { html += '<optgroup label="' + esc(T.presets || 'Popular models') + '">' + l.presets.map(function (m, i) { return opt(m, 'q' + i); }).join('') + '</optgroup>'; }
		select.innerHTML = html;
		select._list = l;
		if (keep && select.querySelector('option[value="' + keep + '"]')) { select.value = keep; }
	}
	function pickedMiner(select) {
		var v = select.value, l = select._list;
		if (!v || !l) { return null; }
		if (v.charAt(0) === 'p') { return l.store.filter(function (m) { return 'p' + m.id === v; })[0] || null; }
		return l.presets[parseInt(v.slice(1), 10)] || null;
	}

	/* ------------------------------------------------------------------
	 * Tool instance
	 * ---------------------------------------------------------------- */
	function Tool(root) {
		this.root = root;
		this.id = root.getAttribute('data-mm-tool');
		this.edited = false;
		this.state = {};
		this.inputs = {};
		var self = this;
		root.querySelectorAll('[data-k]').forEach(function (el) { self.inputs[el.getAttribute('data-k')] = el; });
		root.querySelectorAll('select[data-mm-miners]').forEach(function (s) {
			fillMiners(s, s.getAttribute('data-mm-miners'));
			s.addEventListener('change', function () { self.applyMiner(s); });
		});
		this.fillMarket(true);
		this.readUrl();
		root.addEventListener('input', function (e) {
			var k = e.target.getAttribute && e.target.getAttribute('data-k');
			if (k && /^(price|diff|fees|ltc_price|doge_price|ltc_net|doge_net|ltc_reward|kas_price|kas_net|kas_day)$/.test(k)) { self.edited = true; }
			if (k && /^(th|watts|hash|old_th|old_watts|new_th|new_watts)$/.test(k)) {
				var sel = self.root.querySelector('select[data-mm-miners][data-k="' + (k.indexOf('old_') === 0 ? 'old_miner' : k.indexOf('new_') === 0 ? 'new_miner' : 'miner') + '"]');
				if (sel && sel.value) { sel.value = ''; }
			}
			self.schedule();
		});
		root.addEventListener('change', function () { self.schedule(); });
		root.addEventListener('click', function (e) {
			var b = e.target.closest('button');
			if (!b) { return; }
			if (b.hasAttribute('data-mm-reset-market')) { self.edited = false; self.fillMarket(true); self.schedule(); }
			if (b.hasAttribute('data-mm-share')) { self.share(b); }
			['scenario', 'mode', 'coin', 'range'].forEach(function (attr) {
				var v = b.getAttribute('data-mm-' + attr);
				if (v === null) { return; }
				b.parentNode.querySelectorAll('button').forEach(function (x) { x.classList.toggle('is-active', x === b); });
				self.state[attr] = v;
				if (self.onSeg) { self.onSeg(attr, v); }
				self.schedule();
			});
		});
		this.schedule();
	}
	Tool.prototype.v = function (k, fallback) {
		var el = this.inputs[k];
		if (!el) { return fallback; }
		if (el.tagName === 'SELECT') { return el.value; }
		var n = parseFloat(String(el.value).replace(',', '.'));
		return isFinite(n) ? n : (fallback === undefined ? 0 : fallback);
	};
	Tool.prototype.set = function (k, v) { var el = this.inputs[k]; if (el && v !== null && v !== undefined && isFinite(v)) { el.value = +(+v).toPrecision(8); } };
	Tool.prototype.o = function (k, html, cls) {
		this.root.querySelectorAll('[data-o="' + k + '"]').forEach(function (el) {
			el.innerHTML = html;
			if (cls !== undefined) { el.classList.remove('is-up', 'is-down'); if (cls) { el.classList.add(cls); } }
		});
	};
	Tool.prototype.el = function (k) { return this.root.querySelector('[data-chart="' + k + '"]') || this.root.querySelector('[data-o="' + k + '"]'); };
	Tool.prototype.schedule = function () {
		var self = this;
		if (this._raf) { return; }
		this._raf = requestAnimationFrame(function () { self._raf = 0; self.run(); });
	};
	Tool.prototype.run = function () {
		var fn = CALC[this.id];
		if (fn) { try { fn(this); } catch (err) { if (window.console) { console.error(err); } } }
		this.status();
	};
	Tool.prototype.applyMiner = function (select) {
		var m = pickedMiner(select);
		if (!m) { return; }
		var k = select.getAttribute('data-k'), pre = k === 'old_miner' ? 'old_' : k === 'new_miner' ? 'new_' : '';
		if (this.inputs[pre + 'th']) { this.set(pre + 'th', m.th || m.hash / 1e12); }
		if (this.inputs.hash && !pre) {
			var unitSel = this.inputs.unit, h = m.hash;
			var unit = h >= 1e12 ? 'th' : h >= 1e9 ? 'gh' : 'mh';
			if (unitSel && unitSel.querySelector('option[value="' + unit + '"]')) { unitSel.value = unit; } else if (unitSel) { unit = unitSel.value; }
			this.set('hash', h / { mh: 1e6, gh: 1e9, th: 1e12, ph: 1e15 }[unit]);
		}
		this.set(pre + 'watts', m.watts);
		if (m.price > 0) {
			if (pre === 'new_') { this.set('new_cost', m.price); } else if (!pre && this.inputs.cost) { this.set('cost', m.price); }
		}
		this.miner = m;
		this.schedule();
	};
	Tool.prototype.fillMarket = function (force) {
		if (this.edited && !force) { return; }
		var b = btc();
		this.set('price', b.price);
		this.set('diff', b.difficulty ? b.difficulty / 1e12 : null);
		this.set('fees', b.fees);
		var a = D.alt || {};
		if (a.ltc) { this.set('ltc_price', a.ltc.price); this.set('ltc_net', a.ltc.hashrate ? a.ltc.hashrate / 1e12 : null); this.set('ltc_reward', a.ltc.reward); }
		if (a.doge) { this.set('doge_price', a.doge.price); this.set('doge_net', a.doge.hashrate ? a.doge.hashrate / 1e12 : null); }
		if (a.kas) { this.set('kas_price', a.kas.price); this.set('kas_net', a.kas.hashrate ? a.kas.hashrate / 1e15 : null); this.set('kas_day', a.kas.emission_day ? Math.round(a.kas.emission_day) : null); }
		if (this.inputs.growth && isFinite(b.growth) && b.growth !== null && !this._growthSet) { this.set('growth', Math.round(b.growth * 10) / 10); this._growthSet = true; }
	};
	Tool.prototype.market = function () {
		var b = btc();
		var price = this.v('price', b.price || 0), diffT = this.v('diff', b.difficulty ? b.difficulty / 1e12 : 0), fees = this.v('fees', b.fees || 0);
		return { price: price, diffT: diffT, fees: fees, subsidy: b.subsidy || 3.125, reward: (b.subsidy || 3.125) + fees, ok: price > 0 && diffT > 0 };
	};
	Tool.prototype.status = function () {
		var live = D.status === 'live' || D.status === 'partial';
		var txt = this.edited ? (T.edited || 'Edited') : live ? (T.live || 'Live') : (D.updated ? (T.stale || 'Last known') : (T.offline || 'Offline'));
		this.o('live_label', esc(txt));
		this.root.classList.toggle('is-edited', this.edited);
		var when = D.updated ? new Date(D.updated * 1000).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }) : '';
		this.o('status', '<span class="mm-live-dot"></span>' + esc(txt) + (when ? ' · ' + esc(T.updated || 'Updated') + ' ' + esc(when) : '') + ' · mempool.space · CoinGecko');
	};
	Tool.prototype.readUrl = function () {
		var q = new URLSearchParams(window.location.search);
		if (q.get('tool') && q.get('tool') !== this.id) { return; }
		var self = this, any = false;
		var alias = { hashrate: 'th', power: 'watts' };
		q.forEach(function (val, key) {
			var k = alias[key] || key;
			if (self.inputs[k] && self.inputs[k].tagName !== 'SELECT' && val !== '') { self.inputs[k].value = val; any = true; }
			else if (self.inputs[k] && self.inputs[k].tagName === 'SELECT' && self.inputs[k].querySelector('option[value="' + val + '"]')) { self.inputs[k].value = val; any = true; }
			if (/^(price|diff|fees)$/.test(k)) { self.edited = true; }
		});
		return any;
	};
	Tool.prototype.share = function (btn) {
		var q = new URLSearchParams();
		q.set('tool', this.id);
		var self = this;
		Object.keys(this.inputs).forEach(function (k) {
			var el = self.inputs[k];
			if (el.tagName === 'SELECT' && el.hasAttribute('data-mm-miners')) { return; }
			if (/^(price|diff|fees)$/.test(k) && !self.edited) { return; }
			if (el.value !== '') { q.set(k, el.value); }
		});
		var url = window.location.origin + window.location.pathname + '?' + q.toString() + '#' + this.root.id;
		var done = function () { var s = btn.querySelector('span'); var old = s.textContent; s.textContent = T.copied || 'Copied'; setTimeout(function () { s.textContent = old; }, 1800); };
		if (navigator.clipboard) { navigator.clipboard.writeText(url).then(done, function () { window.prompt('', url); }); } else { window.prompt('', url); }
		if (window.history && history.replaceState) { history.replaceState(null, '', url); }
	};

	/* ------------------------------------------------------------------
	 * Shared pieces
	 * ---------------------------------------------------------------- */
	function rigDay(t, th, watts, kwh, m, pool, uptime) {
		var up = (uptime === undefined ? 100 : uptime) / 100;
		var btcDay = th * btcPerThDay(m.diffT, m.reward) * (1 - pool / 100) * up;
		var rev = btcDay * m.price;
		var kwhDay = watts / 1000 * 24 * up;
		var power = kwhDay * kwh;
		return { btc: btcDay, rev: rev, power: power, profit: rev - power, kwhDay: kwhDay, be: kwhDay > 0 ? rev / kwhDay : 0 };
	}
	// Monthly projection with difficulty growth, optional price growth and the halving.
	function project(th, watts, kwh, m, pool, uptime, months, g, pg) {
		var out = [], hts = halvingTs(), now = Date.now() / 1000, up = uptime / 100;
		for (var i = 1; i <= months; i++) {
			var mid = now + (i - 0.5) * MONTH_DAYS * DAY;
			var reward = (hts && mid > hts ? m.subsidy / 2 : m.subsidy) + m.fees;
			var diff = m.diffT * Math.pow(1 + g / 100, i - 0.5);
			var price = m.price * Math.pow(1 + (pg || 0) / 100, i - 0.5);
			var b = th * btcPerThDay(diff, reward) * (1 - pool / 100) * up * MONTH_DAYS;
			var rev = b * price, power = watts / 1000 * 24 * MONTH_DAYS * up * kwh;
			out.push({ m: i, btc: b, price: price, rev: rev, power: power, profit: rev - power, halved: !!(hts && mid > hts) });
		}
		return out;
	}
	function needMarket(t, m) {
		if (m.ok) { return false; }
		t.o('profit_note', esc(T.offline || ''));
		return true;
	}
	function link(url, label, cls) { return url ? '<a class="mm-btn ' + (cls || 'mm-btn--primary') + ' mm-btn--sm" href="' + esc(url) + '">' + esc(label) + '</a>' : ''; }

	/* ------------------------------------------------------------------
	 * Calculations per tool
	 * ---------------------------------------------------------------- */
	var CALC = {};

	CALC.profit = function (t) {
		var m = t.market(), th = t.v('th'), w = t.v('watts'), kwh = t.v('kwh'), pool = t.v('pool'), up = t.v('uptime', 100), cost = t.v('cost'), g = t.v('growth', 0);
		if (needMarket(t, m)) { return; }
		var d = rigDay(t, th, w, kwh, m, pool, up);
		t.o('profit_day', money(d.profit), tone(d.profit));
		t.o('profit_note', esc((d.profit >= 0 ? T.profit : T.loss) + ' · ' + money(kwh, 3) + '/kWh · ' + (d.rev > 0 ? pct(d.profit / d.rev * 100, 0) + ' margin' : '')));
		t.o('revenue_day', money(d.rev));
		t.o('power_day', money(d.power));
		t.o('btc_day', coin(d.btc, 'BTC'));
		t.o('eff', th > 0 ? fmt(w / th, 1) + NBSP + 'J/TH' : '—');
		t.o('breakeven', money(d.be, 3) + '/kWh');
		var rows = [[T.day || 'Day', 1], [T.week || 'Week', 7], [T.month || 'Month', MONTH_DAYS], [T.year || 'Year', 365]];
		t.o('periods', rows.map(function (r) { return '<tr><th>' + esc(r[0]) + '</th><td>' + money(d.rev * r[1]) + '</td><td>' + money(d.power * r[1]) + '</td><td class="' + tone(d.profit) + '">' + money(d.profit * r[1]) + '</td><td>' + coin(d.btc * r[1], '') + '</td></tr>'; }).join(''));
		var p = project(th, w, kwh, m, pool, up, 60, g, 0), cum = 0, payback = 0;
		var series = [[0, 0]], flat = [[0, 0]];
		p.forEach(function (r, i) {
			cum += r.profit;
			if (!payback && cost > 0 && cum >= cost) { payback = (i + 1) * MONTH_DAYS; }
			if (i < 24) { series.push([r.m, cum]); flat.push([r.m, d.profit * MONTH_DAYS * r.m]); }
		});
		t.o('payback', cost > 0 ? duration(payback) : '<span>' + esc(T.addCost || 'Add hardware cost') + '</span>');
		var hts = halvingTs(), hm = hts ? (hts - Date.now() / 1000) / (MONTH_DAYS * DAY) : 0;
		chart(t.el('outlook'), [{ name: 'With ' + fmt(g, 1) + '%/mo difficulty growth', points: series, area: true }, { name: 'Today’s numbers held flat', points: flat, dashed: true, cls: 'muted' }], {
			id: 'outlook', zero: true, yFmt: function (v) { return money(v, 0); }, xFmt: function (x) { return 'M' + Math.round(x); }, xStep: 6,
			lines: cost > 0 ? [{ y: cost, label: 'Hardware cost' }] : [], markers: hm > 0 && hm < 24 ? [{ x: hm, label: 'Halving' }] : []
		});
		var cta = link(D.links && D.links.shop, T.shopLike || 'Shop miners');
		if (kwh >= 0.1 && D.links && D.links.hosting) { cta += link(D.links.hosting, T.hosting || 'Hosting', 'mm-btn--ghost'); }
		if (t.miner && t.miner.url) { cta = link(t.miner.url, T.viewMiner || 'View this miner') + cta; }
		t.o('cta', cta);
	};

	CALC.hashprice = function (t) {
		var b = btc(), m = t.market();
		var hpTh = btcPerThDay(m.diffT, m.reward) * m.price, hpPh = hpTh * 1000;
		t.o('hp_ph', m.ok ? money(hpPh) : '—');
		t.o('hp_th', m.ok ? money(hpTh, 4) : '—');
		t.o('btc_ph', m.ok ? coin(btcPerThDay(m.diffT, m.reward) * 1000, 'BTC') : '—');
		t.o('fee_share', m.reward > 0 ? pct(m.fees / m.reward * 100, 1) : '—');
		var h = (D.history || []).filter(function (r) { return r[2] > 0; });
		var at = function (days) {
			if (!h.length) { return null; }
			var target = Date.now() / 1000 - days * DAY, best = null;
			h.forEach(function (r) { if (r[0] <= target + DAY) { best = r; } });
			return best && Math.abs(best[0] - target) < 6 * DAY ? best[2] : null;
		};
		[7, 30, 90, 365].forEach(function (n) { var past = at(n); t.o('chg_' + n, past && m.ok ? pct((hpPh / past - 1) * 100, 1, true) : '—', past && m.ok ? tone(hpPh - past) : ''); });
		var year = h.filter(function (r) { return r[0] >= Date.now() / 1000 - 365 * DAY; });
		if (year.length) {
			var hi = year.reduce(function (a, r) { return r[2] > a[2] ? r : a; }), lo = year.reduce(function (a, r) { return r[2] < a[2] ? r : a; });
			t.o('hp_high', money(hi[2]) + '<span>' + esc(dateLabel(hi[0], true)) + '</span>');
			t.o('hp_low', money(lo[2]) + '<span>' + esc(dateLabel(lo[0], true)) + '</span>');
		} else { t.o('hp_high', '—'); t.o('hp_low', '—'); }
		var range = parseInt(t.state.range || '365', 10);
		t.root.querySelectorAll('[data-mm-range]').forEach(function (x) { x.classList.toggle('is-active', x.getAttribute('data-mm-range') === String(range)); });
		var pts = h.filter(function (r) { return r[0] >= Date.now() / 1000 - range * DAY; }).map(function (r) { return [r[0], r[2]]; });
		if (m.ok && pts.length) { pts.push([Date.now() / 1000, hpPh]); }
		chart(t.el('hashprice'), [{ name: 'Hashprice ($/PH/day)', points: pts, area: true }], {
			id: 'hp', empty: 'History builds up automatically once the site has collected market data.',
			yFmt: function (v) { return money(v, 0); }, xFmt: function (x, full) { return dateLabel(x, full); },
			markers: [{ x: 1713571200, label: 'Halving' }]
		});
		var th = t.v('th'), w = t.v('watts'), kwh = t.v('kwh');
		var rev = th * hpTh, cost = w / 1000 * 24 * kwh, be = th > 0 ? cost / th * 1000 : 0;
		t.o('you_rev', money(rev));
		t.o('you_cost', money(cost));
		t.o('you_profit', money(rev - cost), tone(rev - cost));
		t.o('you_be', money(be) + '<span>/PH/day</span>');
		t.o('verdict', m.ok && be > 0 ? esc(hpPh >= be
			? 'Hashprice is ' + pct((hpPh / be - 1) * 100, 0) + ' above your break-even of ' + money(be) + '/PH/day. Your miner keeps ' + pct((1 - be / hpPh) * 100, 0) + ' of what it earns after power.'
			: 'Hashprice is ' + pct((1 - hpPh / be) * 100, 0) + ' below your break-even of ' + money(be) + '/PH/day: a cheaper power rate, a more efficient miner or hosting would fix this.') : '');
	};

	CALC.breakeven = function (t) {
		var m = t.market(), th = t.v('th'), w = t.v('watts'), pool = t.v('pool'), up = t.v('uptime', 100), kwh = t.v('kwh');
		if (needMarket(t, m)) { return; }
		var d = rigDay(t, th, w, kwh, m, pool, up);
		t.o('be', money(d.be, 3) + '<span>/kWh</span>');
		t.o('be_note', esc(kwh > 0 ? (kwh <= d.be ? 'At ' + money(kwh, 3) + '/kWh you make ' + money(d.profit) + ' a day (' + pct((d.be - kwh) / d.be * 100, 0) + ' headroom).' : 'At ' + money(kwh, 3) + '/kWh you lose ' + money(-d.profit) + ' a day.') : ''));
		var max = Math.max(d.be, kwh) * 1.4 || 0.2;
		t.o('gauge', '<div class="mm-gauge__track"><span class="mm-gauge__fill ' + (kwh <= d.be ? 'is-up' : 'is-down') + '" style="width:' + Math.min(100, d.be / max * 100) + '%"></span><i class="mm-gauge__you" style="left:' + Math.min(100, kwh / max * 100) + '%"><b>' + esc('You ' + money(kwh, 3)) + '</b></i></div><div class="mm-gauge__scale"><span>' + money(0, 2) + '</span><span>' + money(max, 2) + '/kWh</span></div>');
		var prices = [-40, -20, 0, 20, 50], diffs = [0, 10, 25, 50];
		var html = '<thead><tr><th>BTC \\ ' + 'Difficulty</th>' + diffs.map(function (x) { return '<th>' + pct(x, 0, true) + '</th>'; }).join('') + '</tr></thead><tbody>';
		prices.forEach(function (p) {
			html += '<tr><th>' + pct(p, 0, true) + ' <small>' + money(m.price * (1 + p / 100), 0) + '</small></th>';
			diffs.forEach(function (x) {
				var be = d.be * (1 + p / 100) / (1 + x / 100);
				html += '<td class="' + (be >= kwh ? 'is-up' : 'is-down') + (p === 0 && x === 0 ? ' is-now' : '') + '">' + money(be, 3) + '</td>';
			});
			html += '</tr>';
		});
		t.o('matrix', html + '</tbody>');
	};

	CALC.compare = function (t) {
		var m = t.market(), kwh = t.v('kwh'), pool = t.v('pool'), cooling = t.v('cooling'), sort = t.v('sort') || 'profit', stock = t.v('stock') || 'available';
		if (needMarket(t, m)) { return; }
		var perTh = btcPerThDay(m.diffT, m.reward) * m.price * (1 - pool / 100);
		var rows = (D.catalog || []).filter(function (x) { return x.algo === 'sha256' && x.th > 0 && x.watts > 0 && (!cooling || x.cooling === cooling) && (stock === 'all' || x.stock !== 'outofstock'); });
		if (!rows.length) { rows = (D.presets || []).map(function (p, i) { return { id: 'q' + i, name: p.name, th: p.th, hash: p.th * 1e12, watts: p.watts, price: p.price, url: '' }; }); }
		rows = rows.map(function (x) {
			var rev = x.th * perTh, cost = x.watts / 1000 * 24 * kwh, profit = rev - cost;
			return Object.assign({}, x, { rev: rev, profit: profit, eff: x.watts / x.th, value: x.price > 0 ? x.price / x.th : Infinity, payback: x.price > 0 && profit > 0 ? x.price / profit : Infinity });
		});
		var key = { profit: function (a, b) { return b.profit - a.profit; }, eff: function (a, b) { return a.eff - b.eff; }, value: function (a, b) { return a.value - b.value; }, payback: function (a, b) { return a.payback - b.payback; }, th: function (a, b) { return b.th - a.th; } }[sort];
		rows.sort(key);
		var best = function (f, dir) { return rows.filter(function (r) { return isFinite(r[f]); }).reduce(function (a, r) { return !a || (dir < 0 ? r[f] < a[f] : r[f] > a[f]) ? r : a; }, null); };
		var leaders = [['Most profitable', best('profit', 1)], ['Most efficient', best('eff', -1)], ['Best $/TH', best('value', -1)], ['Fastest payback', best('payback', -1)]];
		t.o('leaders', leaders.filter(function (l) { return l[1]; }).map(function (l) { var r = l[1]; return '<' + (r.url ? 'a href="' + esc(r.url) + '"' : 'span') + ' class="mm-leader"><small>' + esc(l[0]) + '</small><strong>' + esc(r.name) + '</strong></' + (r.url ? 'a' : 'span') + '>'; }).join(''));
		t.picked = t.picked || [];
		var L = t.labels || (t.labels = Array.prototype.map.call(t.root.querySelectorAll('.mm-ttable--rank thead th'), function (h) { return esc(h.textContent); }));
		t.o('rows', rows.map(function (r) {
			var on = t.picked.indexOf(String(r.id)) > -1;
			var td = function (i, v, cls) { return '<td data-label="' + (L[i] || '') + '"' + (cls ? ' class="' + cls + '"' : '') + '>' + v + '</td>'; };
			return '<tr class="' + (r.profit < 0 ? 'is-loss' : '') + (on ? ' is-picked' : '') + '"><td class="mm-ttable__pick"><input type="checkbox" data-mm-pick="' + esc(r.id) + '"' + (on ? ' checked' : '') + ' aria-label="Compare ' + esc(r.name) + '"></td><th>' + (r.url ? '<a href="' + esc(r.url) + '">' + esc(r.name) + '</a>' : esc(r.name)) + (r.stock === 'onbackorder' ? ' <em class="mm-pill">Pre-order</em>' : r.stock === 'outofstock' ? ' <em class="mm-pill">Sold out</em>' : '') + '</th>'
				+ td(2, hashrate(r.hash)) + td(3, fmt(r.watts, 0) + NBSP + 'W') + td(4, fmt(r.eff, 1)) + td(5, r.price > 0 ? money(r.price, 0) : '—') + td(6, isFinite(r.value) ? money(r.value, 1) : '—') + td(7, money(r.profit), 'mm-ttable__key ' + tone(r.profit)) + td(8, isFinite(r.payback) ? duration(r.payback) : '—') + '</tr>';
		}).join(''));
		t.root.querySelectorAll('[data-mm-pick]').forEach(function (c) {
			c.onchange = function () {
				var id = c.getAttribute('data-mm-pick'), i = t.picked.indexOf(id);
				if (c.checked && i < 0) { t.picked.push(id); if (t.picked.length > 3) { t.picked.shift(); } }
				if (!c.checked && i > -1) { t.picked.splice(i, 1); }
				t.schedule();
			};
		});
		var picks = rows.filter(function (r) { return t.picked.indexOf(String(r.id)) > -1; });
		if (picks.length) {
			var mx = function (f) { return Math.max.apply(null, picks.map(function (r) { return isFinite(r[f]) ? Math.abs(r[f]) : 0; })) || 1; };
			var bar = function (v, f, label) { return '<div class="mm-bar"><span>' + esc(label) + '</span><i style="width:' + (isFinite(v) ? Math.abs(v) / mx(f) * 100 : 0) + '%"></i><b>' + (f === 'eff' ? fmt(v, 1) + ' J/TH' : f === 'payback' ? duration(v) : f === 'th' ? fmt(v, 0) + ' TH/s' : money(v)) + '</b></div>'; };
			t.o('picked', picks.map(function (r) { return '<article class="mm-compare-card">' + (r.img ? '<img src="' + esc(r.img) + '" alt="" loading="lazy">' : '') + '<h4>' + esc(r.name) + '</h4>' + bar(r.profit, 'profit', 'Profit/day') + bar(r.th, 'th', 'Hashrate') + bar(r.eff, 'eff', 'Efficiency') + bar(r.payback, 'payback', 'Payback') + (r.url ? link(r.url, T.viewMiner || 'View') : '') + '</article>'; }).join(''));
		} else { t.o('picked', ''); }
	};

	CALC.roi = function (t) {
		var m = t.market(), th = t.v('th'), w = t.v('watts'), cost = t.v('cost'), kwh = t.v('kwh'), pool = t.v('pool'), up = t.v('uptime', 100);
		var months = parseInt(t.v('months') || '24', 10), g = t.v('growth', 0), pg = t.v('pgrowth', 0);
		if (needMarket(t, m)) { return; }
		var p = project(th, w, kwh, m, pool, up, months, g, pg), cum = -cost, payback = 0, btcSum = 0, net = 0;
		var pts = [[0, -cost]], rows = '';
		p.forEach(function (r) {
			cum += r.profit; btcSum += r.btc; net += r.profit;
			if (!payback && cum >= 0 && cost > 0) { payback = r.m; }
			pts.push([r.m, cum]);
			rows += '<tr' + (r.halved ? ' class="is-halved"' : '') + '><th>' + r.m + '</th><td>' + coin(r.btc, '') + '</td><td>' + money(r.price, 0) + '</td><td>' + money(r.rev) + '</td><td>' + money(r.power) + '</td><td class="' + tone(r.profit) + '">' + money(r.profit) + '</td><td class="' + tone(cum) + '">' + money(cum) + '</td></tr>';
		});
		t.o('roi', cost > 0 ? pct((net - cost) / cost * 100, 0, true) : '—', tone(net - cost));
		t.o('payback', cost > 0 ? (payback ? payback + NBSP + (T.months || 'months') : T.never) : '—');
		t.o('net', money(net - cost), tone(net - cost));
		t.o('btc', coin(btcSum, 'BTC'));
		t.o('month1', money(p[0].profit), tone(p[0].profit));
		t.o('monthN', money(p[p.length - 1].profit), tone(p[p.length - 1].profit));
		t.o('months', rows);
		var hts = halvingTs(), hm = hts ? (hts - Date.now() / 1000) / (MONTH_DAYS * DAY) : 0;
		chart(t.el('roi'), [{ name: 'Cumulative cash flow', points: pts, area: true }], { id: 'roi', zero: true, yFmt: function (v) { return money(v, 0); }, xFmt: function (x) { return 'M' + Math.round(x); }, xStep: 6, markers: hm > 0 && hm < months ? [{ x: hm, label: 'Halving' }] : [] });
		var sc = t.state.scenario || 'base';
		t.o('verdict', esc((sc.charAt(0).toUpperCase() + sc.slice(1)) + ' case: ' + (payback ? 'the miner pays for itself in month ' + payback + ' and ends ' + months + ' months ' + (net - cost >= 0 ? 'up ' : 'down ') + money(Math.abs(net - cost), 0) + '.' : 'the miner does not pay back within ' + months + ' months at these numbers.')));
	};
	var SCEN = { bear: [-3, 3.5], base: [1, 2.5], bull: [4, 2] };
	function onSegRoi(t) { t.onSeg = function (attr, v) { if (attr === 'scenario' && SCEN[v]) { t.set('pgrowth', SCEN[v][0]); t.set('growth', SCEN[v][1]); } }; }

	CALC.halving = function (t) {
		var b = btc(), m = t.market(), h = b.halving;
		var hts = halvingTs();
		if (h && hts) {
			var tick = function () {
				var left = Math.max(0, hts - Date.now() / 1000);
				t.o('cd_d', fmt(Math.floor(left / DAY), 0)); t.o('cd_h', String(Math.floor(left % DAY / 3600)).padStart(2, '0'));
				t.o('cd_m', String(Math.floor(left % 3600 / 60)).padStart(2, '0')); t.o('cd_s', String(Math.floor(left % 60)).padStart(2, '0'));
			};
			tick();
			if (!t._timer) { t._timer = setInterval(tick, 1000); }
			var last = h.next_height - 210000, done = (b.height - last) / 210000 * 100;
			t.root.querySelector('[data-o="epoch_bar"]').style.width = Math.min(100, Math.max(0, done)) + '%';
			t.o('epoch_pct', esc(pct(done, 1) + ' of the way from block ' + fmt(last, 0)));
			t.o('eta', esc('≈ ' + new Date(hts * 1000).toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' })));
			t.o('next_date', esc('≈ ' + new Date(hts * 1000).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })));
			t.o('blocks_left', fmt(h.blocks_left, 0));
			t.o('next_height', fmt(h.next_height, 0));
		}
		t.o('subsidy_now', coin(m.subsidy, 'BTC'));
		t.o('subsidy_next', coin(m.subsidy / 2, 'BTC'));
		if (needMarket(t, m)) { return; }
		var th = t.v('th'), w = t.v('watts'), kwh = t.v('kwh'), pool = t.v('pool');
		var now = rigDay(t, th, w, kwh, m, pool, 100);
		var after = rigDay(t, th, w, kwh, { price: m.price, diffT: m.diffT, reward: m.subsidy / 2 + m.fees }, pool, 100);
		t.o('now_profit', money(now.profit), tone(now.profit)); t.o('now_rev', esc(money(now.rev) + ' revenue / day'));
		t.o('after_profit', money(after.profit), tone(after.profit)); t.o('after_rev', esc(money(after.rev) + ' revenue / day'));
		t.o('be_now', money(now.be, 3) + '/kWh'); t.o('be_after', money(after.be, 3) + '/kWh');
		t.o('price_needed', money(m.price * m.reward / (m.subsidy / 2 + m.fees), 0));
		t.o('verdict', esc(after.profit >= 0 ? 'At today’s price and difficulty this miner stays profitable after the halving, with ' + money(after.profit) + ' a day.' : 'At today’s price and difficulty this miner would lose ' + money(-after.profit) + ' a day after the halving, unless the BTC price rises, difficulty falls or your power gets cheaper than ' + money(after.be, 3) + '/kWh.'));
	};

	CALC.difficulty = function (t) {
		var b = btc(), a = b.adjustment || {}, m = t.market();
		var change = t.inputs.custom && t.inputs.custom.value !== '' ? t.v('custom') : a.change;
		if (isFinite(a.progress)) {
			t.root.querySelector('[data-o="epoch_bar"]').style.width = Math.min(100, a.progress) + '%';
			t.o('epoch_pct', esc(pct(a.progress, 1) + ' through this 2,016-block epoch'));
			t.o('blocks_left', esc(fmt(a.remaining_blocks, 0) + ' blocks to go'));
		}
		t.o('adj', isFinite(change) ? pct(change, 2, true) : '—', isFinite(change) ? (change > 0 ? 'is-down' : 'is-up') : '');
		t.o('adj_when', a.eta ? esc('≈ ' + new Date(a.eta * 1000).toLocaleString(undefined, { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })) : '');
		t.o('diff_now', m.diffT ? fmt(m.diffT, 2) + NBSP + 'T' : '—');
		t.o('diff_next', m.diffT && isFinite(change) ? fmt(m.diffT * (1 + change / 100), 2) + NBSP + 'T' : '—');
		if (isFinite(a.change)) { var bt = 600 / (1 + a.change / 100); t.o('block_time', Math.floor(bt / 60) + 'm ' + String(Math.round(bt % 60)).padStart(2, '0') + 's'); }
		t.o('hashrate', hashrate(b.hashrate));
		var range = parseInt(t.state.range || '365', 10);
		t.root.querySelectorAll('[data-mm-range]').forEach(function (x) { x.classList.toggle('is-active', x.getAttribute('data-mm-range') === String(range)); });
		var pts = (D.history || []).filter(function (r) { return r[3] > 0 && r[0] >= Date.now() / 1000 - range * DAY; }).map(function (r) { return [r[0], r[3] / 1e12]; });
		chart(t.el('difficulty'), [{ name: 'Difficulty (T)', points: pts, area: true }], { id: 'diff', empty: 'History builds up automatically once the site has collected market data.', yFmt: function (v) { return fmt(v, 0) + 'T'; }, xFmt: function (x, full) { return dateLabel(x, full); } });
		if (needMarket(t, m)) { return; }
		var th = t.v('th'), w = t.v('watts'), kwh = t.v('kwh');
		var now = rigDay(t, th, w, kwh, m, 0, 100);
		var after = rigDay(t, th, w, kwh, { price: m.price, diffT: m.diffT * (1 + (change || 0) / 100), reward: m.reward }, 0, 100);
		t.o('now_profit', money(now.profit), tone(now.profit)); t.o('now_rev', esc(money(now.rev) + ' revenue / day'));
		t.o('after_profit', money(after.profit), tone(after.profit)); t.o('after_rev', esc(money(after.rev) + ' revenue / day'));
		t.o('verdict', esc(isFinite(change) ? 'A ' + pct(change, 2, true) + ' adjustment changes your revenue by ' + money(after.rev - now.rev) + ' a day (' + money((after.rev - now.rev) * MONTH_DAYS) + ' a month) at today’s BTC price.' : ''));
	};

	CALC.hosting = function (t) {
		var m = t.market();
		if (needMarket(t, m)) { return; }
		var th = t.v('th'), w = t.v('watts'), qty = Math.max(1, Math.round(t.v('qty', 1))), cost = t.v('cost'), months = parseInt(t.v('months') || '24', 10), pool = t.v('pool');
		var g = isFinite(btc().growth) && btc().growth !== null ? btc().growth : 2.5;
		var home = project(th * qty, w * qty * (1 + t.v('home_cooling') / 100), t.v('home_kwh'), m, pool, t.v('home_uptime', 95), months, g, 0);
		var host = project(th * qty, w * qty, t.v('host_kwh'), m, pool, t.v('host_uptime', 98), months, g, 0);
		var upHome = cost * qty + t.v('home_setup'), upHost = cost * qty + (t.v('host_setup') + t.v('host_ship')) * qty;
		var cumH = -upHome, cumS = -upHost, beH = 0, beS = 0, pH = [[0, cumH]], pS = [[0, cumS]];
		home.forEach(function (r, i) { cumH += r.profit; cumS += host[i].profit; if (!beH && cumH >= 0) { beH = r.m; } if (!beS && cumS >= 0) { beS = r.m; } pH.push([r.m, cumH]); pS.push([r.m, cumS]); });
		t.o('home_month', money(home[0].profit), tone(home[0].profit)); t.o('home_sub', esc('Power ' + money(home[0].power) + ' / month'));
		t.o('host_month', money(host[0].profit), tone(host[0].profit)); t.o('host_sub', esc('Hosting ' + money(host[0].power) + ' / month'));
		chart(t.el('hosting'), [{ name: 'Home', points: pH, cls: 'alt' }, { name: 'Hosted', points: pS, area: true }], { id: 'host', zero: true, legend: true, yFmt: function (v) { return money(v, 0); }, xFmt: function (x) { return 'M' + Math.round(x); }, xStep: 6 });
		var tr = function (label, a, b) { return '<tr><th>' + esc(label) + '</th><td>' + a + '</td><td>' + b + '</td></tr>'; };
		t.o('table', tr('Upfront cost', money(upHome, 0), money(upHost, 0)) + tr('Power or hosting / month', money(home[0].power), money(host[0].power)) + tr('Profit / month (first)', money(home[0].profit), money(host[0].profit)) + tr('Breaks even', beH ? 'Month ' + beH : 'After ' + months + ' mo', beS ? 'Month ' + beS : 'After ' + months + ' mo') + tr('After ' + months + ' months', '<b class="' + tone(cumH) + '">' + money(cumH, 0) + '</b>', '<b class="' + tone(cumS) + '">' + money(cumS, 0) + '</b>'));
		var diff = cumS - cumH;
		t.o('verdict', esc((diff >= 0 ? 'Hosting comes out ahead by ' : 'Mining at home comes out ahead by ') + money(Math.abs(diff), 0) + ' after ' + months + ' months' + (diff >= 0 ? ', and it removes the heat, noise and wiring from your home.' : '. Plan for the heat (about ' + fmt(w * qty * 3.412, 0) + ' BTU/h) and the noise.')));
		t.o('cta', link(D.links && D.links.hosting, T.hosting || 'Hosting') + link(D.links && D.links.contact, T.quote || 'Get a quote', 'mm-btn--ghost'));
	};

	CALC.power = function (t) {
		var w = t.v('watts'), qty = Math.max(1, Math.round(t.v('qty', 1))), vsel = String(t.v('volts') || '240'), three = /-3$/.test(vsel), V = parseFloat(vsel), breaker = parseFloat(t.v('breaker') || '30'), pf = Math.min(1, Math.max(0.5, t.v('pf', 0.98))), dt = Math.max(5, t.v('dt', 20)), kwh = t.v('kwh');
		var total = w * qty, perAmp = three ? w / (Math.sqrt(3) * V * pf) : w / (V * pf), amps = perAmp * qty;
		var usable = breaker * 0.8, per = perAmp > 0 ? Math.floor(usable / perAmp) : 0, circuits = per > 0 ? Math.ceil(qty / per) : 0;
		var btu = total * 3.412, kwhMonth = total / 1000 * 24 * MONTH_DAYS;
		t.o('kw', fmt(total / 1000, 2) + NBSP + 'kW');
		t.o('amps', fmt(amps, 1) + NBSP + 'A' + '<span>' + fmt(perAmp, 1) + ' A ' + (three ? 'per phase, ' : '') + 'each</span>');
		t.o('per_circuit', per > 0 ? String(per) : '0');
		t.o('circuits', per > 0 ? String(circuits) + '<span>' + esc(fmt(breaker, 0) + ' A · ' + vsel.replace('-3', ' V 3-phase').replace(/^(\d+)$/, '$1 V')) + '</span>' : '—');
		t.o('kwh_month', fmt(kwhMonth, 0) + NBSP + 'kWh');
		t.o('cost_month', money(kwhMonth * kwh, 0));
		t.o('btu', fmt(btu, 0) + NBSP + 'BTU/h');
		t.o('tons', fmt(btu / 12000, 2) + NBSP + 'tons');
		t.o('cfm', fmt(btu / (1.08 * dt), 0) + NBSP + 'CFM');
		var notes = [];
		if (per === 0) { notes.push('One miner draws ' + fmt(perAmp, 1) + ' A, more than 80% of a ' + fmt(breaker, 0) + ' A breaker (' + fmt(usable, 1) + ' A continuous). Use a larger circuit or a higher voltage.'); }
		else { notes.push('Each ' + fmt(breaker, 0) + ' A circuit carries ' + per + ' miner' + (per > 1 ? 's' : '') + ' at the 80% continuous-load limit (' + fmt(usable, 1) + ' A).'); }
		if (V <= 127 && w > 1500) { notes.push('Above about 1,500 W, 120 V is impractical: ASIC power supplies run more efficiently and more safely on 200–277 V.'); }
		if (three) { notes.push('On 3-phase, spread miners evenly across the three phases so the current stays balanced.'); }
		notes.push('All of the power becomes heat: plan to move about ' + fmt(btu / (1.08 * dt), 0) + ' CFM of air, or ' + fmt(btu / 12000, 1) + ' tons of cooling, to hold a ' + fmt(dt, 0) + ' °F rise.');
		notes.push('Figures are for planning. Breakers, cable sizes and receptacles must be chosen and installed by a licensed electrician to your local code.');
		t.o('notes', '<ul>' + notes.map(function (n) { return '<li>' + esc(n) + '</li>'; }).join('') + '</ul>');
	};

	CALC.upgrade = function (t) {
		var m = t.market();
		if (needMarket(t, m)) { return; }
		var kwh = t.v('kwh'), pool = t.v('pool'), qty = Math.max(1, Math.round(t.v('qty', 1)));
		var oth = t.v('old_th'), ow = t.v('old_watts'), nth = t.v('new_th'), nw = t.v('new_watts'), ncost = t.v('new_cost'), resale = t.v('resale');
		var mode = t.state.mode || 'power';
		var nq = mode === 'power' ? Math.max(1, Math.floor(ow * qty / (nw || 1))) : qty;
		var o = rigDay(t, oth * qty, ow * qty, kwh, m, pool, 100), n = rigDay(t, nth * nq, nw * nq, kwh, m, pool, 100);
		var gain = n.profit - o.profit, net = nq * ncost - qty * resale;
		t.o('old_profit', money(o.profit), tone(o.profit)); t.o('old_sub', esc(qty + ' × ' + fmt(oth, 0) + ' TH/s · ' + fmt(ow * qty / 1000, 1) + ' kW'));
		t.o('new_profit', money(n.profit), tone(n.profit)); t.o('new_sub', esc(nq + ' × ' + fmt(nth, 0) + ' TH/s · ' + fmt(nw * nq / 1000, 1) + ' kW'));
		t.o('gain', money(gain * MONTH_DAYS, 0), tone(gain));
		t.o('net_cost', money(net, 0));
		t.o('payback', gain > 0 ? duration(net / gain) : T.never);
		var eo = oth > 0 ? ow / oth : 0, en = nth > 0 ? nw / nth : 0;
		t.o('eff_gain', eo > 0 ? fmt(eo, 1) + ' → ' + fmt(en, 1) + ' J/TH' : '—');
		t.o('verdict', esc(gain <= 0 ? 'At these numbers the upgrade does not earn more than keeping your current miners.' : (net / gain < 365 ? 'Upgrade: the extra ' + money(gain * MONTH_DAYS, 0) + ' a month repays the net cost in ' + duration(net / gain) + '.' : 'The upgrade earns more, but takes ' + duration(net / gain) + ' to repay its net cost. Worth it if you plan to mine that long, or if your power price rises.') + (o.profit < 0 ? ' Your current miners are losing money at this power price.' : '')));
		t.o('cta', link(D.links && D.links.shop, T.shopLike || 'Shop miners') + link(D.links && D.links.contact, 'Trade in your miners', 'mm-btn--ghost'));
	};

	var SOLO = [['Bitaxe Gamma', 1.2, 18], ['NerdQAxe++', 4.8, 76], ['NerdOCTaxe', 9.6, 160], ['Antminer S21', 200, 3500]];
	CALC.solo = function (t) {
		var chips = t.root.querySelector('[data-mm-solo-presets]');
		if (chips && !chips._done) {
			chips._done = true;
			var list = SOLO.slice();
			(D.catalog || []).filter(function (x) { return x.solo && x.th > 0; }).forEach(function (x) { if (!list.some(function (l) { return l[0].toLowerCase() === x.name.toLowerCase(); })) { list.unshift([x.name, x.th, x.watts]); } });
			chips.innerHTML = list.slice(0, 6).map(function (p) { return '<button type="button" class="mm-chip" data-th="' + p[1] + '" data-w="' + p[2] + '">' + esc(p[0]) + '<small>' + fmt(p[1], p[1] < 10 ? 1 : 0) + ' TH/s</small></button>'; }).join('');
			chips.addEventListener('click', function (e) { var b = e.target.closest('[data-th]'); if (!b) { return; } t.inputs.unit.value = 'th'; t.set('hash', parseFloat(b.dataset.th)); t.set('watts', parseFloat(b.dataset.w)); t.schedule(); });
		}
		var m = t.market();
		if (needMarket(t, m)) { return; }
		var H = t.v('hash') * { gh: 1e9, th: 1e12, ph: 1e15 }[t.v('unit') || 'th'];
		var p = H * 600 / (m.diffT * 1e12 * TWO32);
		var chance = function (blocks) { return -Math.expm1(blocks * Math.log1p(-Math.min(p, 0.999999))); };
		var yearBlocks = 144 * 365.25;
		t.o('p_year', pct(chance(yearBlocks) * 100, chance(yearBlocks) < 0.01 ? 3 : 2));
		t.o('odds_year', esc(oneIn(chance(yearBlocks)) + ' per year'));
		t.o('p_day', esc(oneIn(chance(144))));
		t.o('p_month', esc(oneIn(chance(144 * MONTH_DAYS))));
		t.o('expected', duration(1 / (p * 144)));
		t.o('prize', money(m.reward * m.price, 0) + '<span>' + esc(coin(m.reward, 'BTC')) + '</span>');
		t.o('ev_year', money(p * yearBlocks * m.reward * m.price));
		t.o('power_year', money(t.v('watts') / 1000 * 24 * 365.25 * t.v('kwh')));
		var lg = Math.max(0, Math.min(100, (Math.log10(chance(yearBlocks)) + 6) / 6 * 100));
		t.o('odds_bar', '<div class="mm-odds__track"><span style="width:' + lg + '%"></span></div><div class="mm-odds__scale"><span>1 in a million</span><span>1 in 1,000</span><span>Certain</span></div>');
		t.o('verdict', esc('Solo mining is a lottery with real odds: on average you would find a block every ' + duration(1 / (p * 144)) + '. The expected value is the same as pool mining (' + money(p * yearBlocks * m.reward * m.price) + ' a year), but paid all at once, or never. Small solo miners are best seen as a fun, low-power ticket that also secures the network.'));
	};

	CALC.altcoin = function (t) {
		var mode = t.state.coin || 'scrypt';
		var sel = t.root.querySelector('select[data-mm-miners]');
		if (sel && sel.getAttribute('data-mm-miners') !== (mode === 'kas' ? 'kheavyhash' : 'scrypt')) {
			sel.setAttribute('data-mm-miners', mode === 'kas' ? 'kheavyhash' : 'scrypt');
			fillMiners(sel, mode === 'kas' ? 'kheavyhash' : 'scrypt');
			sel.value = '';
			if (mode === 'kas') { t.inputs.unit.value = 'th'; t.set('hash', 12); t.set('watts', 3400); } else { t.inputs.unit.value = 'gh'; t.set('hash', 9.5); t.set('watts', 3425); }
		}
		t.root.querySelectorAll('[data-mm-coin-fields]').forEach(function (f) { f.hidden = f.getAttribute('data-mm-coin-fields') !== mode; });
		var H = t.v('hash') * { mh: 1e6, gh: 1e9, th: 1e12 }[t.v('unit') || 'gh'], w = t.v('watts'), kwh = t.v('kwh'), fee = 1 - t.v('pool') / 100;
		var coins = [], rev = 0, share = 0, missing = false;
		if (mode === 'scrypt') {
			var ln = t.v('ltc_net') * 1e12, dn = t.v('doge_net') * 1e12, lp = t.v('ltc_price'), dp = t.v('doge_price');
			if (!(ln > 0) || !(lp > 0)) { missing = true; }
			var ltc = ln > 0 ? H / ln * 576 * t.v('ltc_reward', 6.25) * fee : 0, doge = dn > 0 ? H / dn * 1440 * 10000 * fee : 0;
			share = ln > 0 ? H / ln : 0;
			coins = [['LTC', 'Litecoin', ltc, lp], ['DOGE', 'Dogecoin', doge, dp]];
		} else {
			var kn = t.v('kas_net') * 1e15, kp = t.v('kas_price');
			if (!(kn > 0) || !(kp > 0)) { missing = true; }
			var kas = kn > 0 ? H / kn * t.v('kas_day') * fee : 0;
			share = kn > 0 ? H / kn : 0;
			coins = [['KAS', 'Kaspa', kas, kp]];
		}
		coins.forEach(function (c) { rev += c[2] * (c[3] || 0); });
		var kwhDay = w / 1000 * 24, power = kwhDay * kwh, profit = rev - power;
		if (missing) { t.o('profit_day', '—'); t.o('profit_note', esc(T.offline || '')); return; }
		t.o('profit_day', money(profit), tone(profit));
		t.o('profit_note', esc((profit >= 0 ? T.profit : T.loss) + ' · ' + money(kwh, 3) + '/kWh'));
		t.o('coins', coins.map(function (c) { return '<div class="mm-coin"><span class="mm-coin__sym">' + c[0] + '</span><div><small>' + esc(c[1]) + ' / day</small><strong>' + esc(coin(c[2], c[0])) + '</strong><span>' + money(c[2] * (c[3] || 0)) + '</span></div></div>'; }).join(''));
		t.o('revenue_day', money(rev)); t.o('power_day', money(power)); t.o('profit_month', money(profit * MONTH_DAYS, 0), tone(profit));
		t.o('breakeven', money(kwhDay > 0 ? rev / kwhDay : 0, 3) + '/kWh');
		t.o('eff', H > 0 ? (mode === 'kas' ? fmt(w / (H / 1e12), 1) + ' J/TH' : fmt(w / (H / 1e6), 3) + ' J/MH') : '—');
		t.o('share', share > 0 ? pct(share * 100, share * 100 < 0.01 ? 5 : 3) : '—');
		t.o('verdict', esc(mode === 'scrypt' ? 'Scrypt miners earn Litecoin and Dogecoin at the same time through merged mining, so both rewards count. Most pools pay both automatically.' : 'Kaspa’s block reward falls a little every month (a smooth yearly halving), so expect daily KAS to drift down even if the network stays the same size.'));
	};

	/* ------------------------------------------------------------------
	 * Boot + live refresh
	 * ---------------------------------------------------------------- */
	function boot() {
		document.querySelectorAll('[data-mm-tool]').forEach(function (root) {
			if (root._mmTool) { return; }
			var t = new Tool(root);
			root._mmTool = t;
			if (t.id === 'roi') { onSegRoi(t); }
			instances.push(t);
		});
		if (!instances.length) { return; }
		var redraw;
		window.addEventListener('resize', function () { clearTimeout(redraw); redraw = setTimeout(function () { instances.forEach(function (t) { t.schedule(); }); }, 150); });
		if (API && window.fetch) {
			var refresh = function (hist) {
				if (document.hidden) { return; }
				fetch(API + (API.indexOf('?') > -1 ? '&' : '?') + (hist ? 'history=1' : 'h=0'), { credentials: 'omit' }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
					if (!data || !data.btc) { return; }
					var oldHist = D.history;
					D = data;
					if (!hist && oldHist) { D.history = oldHist; }
					SYM = D.symbol || SYM;
					instances.forEach(function (t) { t.fillMarket(false); t.schedule(); });
				}).catch(function () {});
			};
			if (!D.updated || Date.now() / 1000 - D.updated > 300 || !(D.history && D.history.length > 30)) { refresh(true); }
			setInterval(function () { refresh(false); }, 300000);
		}
	}
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
