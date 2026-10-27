/**
 * Mine Mirth motion layer.
 *
 * - Crypto shapes (.mm-deco) animate only while they are on screen.
 * - Cursor spotlight on section cards.
 * - A new Bitcoin block makes the blockchain shapes confirm a block.
 * - Live guide: a small assistant that explains what today's numbers mean for the page the
 *   visitor is reading. Messages come from PHP (window.MM_GUIDE) with {tokens} filled from the
 *   live market data.
 */
(function () {
	'use strict';

	var MM = window.MineMirth;
	if (!MM) { return; }
	var doc = document;
	var root = doc.documentElement;
	var anim = root.classList.contains('mm-anim');
	var cfg = MM.config || {};

	function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }

	/* ------------------------------------------------------------------
	 * Shapes: play while visible
	 * ---------------------------------------------------------------- */
	function initDeco() {
		var nodes = $$('.mm-deco');
		if (!nodes.length || !anim) { return; }
		if (!('IntersectionObserver' in window)) {
			nodes.forEach(function (n) { n.classList.add('is-on'); });
			return;
		}
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) { e.target.classList.toggle('is-on', e.isIntersecting); });
		}, { rootMargin: '80px 0px' });
		nodes.forEach(function (n) { io.observe(n); });
	}

	/* ------------------------------------------------------------------
	 * Spotlight on section cards
	 * ---------------------------------------------------------------- */
	function initSpotlight() {
		if (!MM.finePointer || !(cfg.fx && cfg.fx.spotlight)) { return; }
		var sel = '.mm-feat, .mm-lcard, .mm-pcard, .mm-mtile, .mm-rcard';
		doc.addEventListener('pointermove', MM.rafThrottle(function (e) {
			var card = e.target.closest && e.target.closest(sel);
			if (!card) { return; }
			var r = card.getBoundingClientRect();
			card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
			card.style.setProperty('--my', (e.clientY - r.top) + 'px');
		}), { passive: true });
	}

	/* ------------------------------------------------------------------
	 * New block: the chain confirms it
	 * ---------------------------------------------------------------- */
	function initBlocks() {
		var last = null;
		MM.on('market', function (data) {
			var h = data && data.height;
			if (anim && last && h && h !== last) {
				$$('.mm-deco--chain').forEach(function (c) {
					c.classList.remove('is-mined');
					void c.offsetWidth;
					c.classList.add('is-mined');
				});
			}
			last = h || last;
		});
	}

	/* ------------------------------------------------------------------
	 * Live guide
	 * ---------------------------------------------------------------- */
	var session = {
		get: function (k) { try { return window.sessionStorage.getItem(k); } catch (e) { return null; } },
		set: function (k, v) { try { window.sessionStorage.setItem(k, v); } catch (e) { /* private mode */ } }
	};
	var ICON = {
		x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
		next: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>',
		arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>'
	};

	function initGuide(G) {
		if (!G || !G.msgs || !G.msgs.length) { return; }
		var t = G.i18n || {};
		var quiet = session.get('mm-guide-quiet') === '1';
		var views = parseInt(session.get('mm-guide-views') || '0', 10) || 0;
		session.set('mm-guide-views', String(views + 1));
		var auto = !quiet && views < 3;
		var locale = (cfg.market && cfg.market.locale) || undefined;
		var market = (MM.market && MM.market.get && MM.market.get()) || G.market || null;
		var index = -1;
		var shown = 0;
		var timer = null;
		var typer = null;
		var hold = false;
		var started = false;

		var box = MM.el('aside', { class: 'mm-guide', 'aria-label': t.name || 'Live guide' });
		box.innerHTML =
			'<button class="mm-guide__avatar" type="button" aria-expanded="false" aria-controls="mm-guide-bubble"><span class="mm-guide__coin"></span><span class="mm-guide__dot"></span></button>' +
			'<div class="mm-guide__bubble" id="mm-guide-bubble">' +
				'<div class="mm-guide__head"><span class="mm-live-dot" aria-hidden="true"></span><span class="mm-guide__name"></span>' +
				'<button class="mm-guide__next" type="button">' + ICON.next + '</button><button class="mm-guide__close" type="button">' + ICON.x + '</button></div>' +
				'<p class="mm-guide__text"><span class="mm-guide__vis" aria-hidden="true"></span><span class="screen-reader-text" aria-live="polite"></span></p>' +
				'<div class="mm-guide__foot"><a class="mm-guide__cta" hidden><span></span>' + ICON.arrow + '</a><span class="mm-guide__steps" aria-hidden="true"></span></div>' +
			'</div>';
		var avatar = box.querySelector('.mm-guide__avatar');
		var vis = box.querySelector('.mm-guide__vis');
		var live = box.querySelector('[aria-live]');
		var cta = box.querySelector('.mm-guide__cta');
		var steps = box.querySelector('.mm-guide__steps');
		var next = box.querySelector('.mm-guide__next');
		var close = box.querySelector('.mm-guide__close');
		box.querySelector('.mm-guide__name').textContent = t.name || '';
		avatar.setAttribute('aria-label', t.open || '');
		next.setAttribute('aria-label', t.next || '');
		close.setAttribute('aria-label', t.close || '');
		G.msgs.forEach(function () { steps.appendChild(doc.createElement('span')); });
		if (G.msgs.length < 2) { next.hidden = true; }

		function num(v, d) { return Number(v).toLocaleString(locale, { minimumFractionDigits: d, maximumFractionDigits: d }); }
		function money(v) { return (G.sym || '$') + num(v, v >= 100 ? 0 : (v >= 0.1 ? 2 : 3)); }
		function days(blocks) {
			var dd = blocks * 10 / 1440;
			if (dd < 0.75) { return t.hours || ''; }
			if (dd < 1.5) { return t.day || ''; }
			return (t.days || '%s').replace('%s', num(Math.round(dd), 0));
		}
		function values() {
			var m = market || {};
			var d = m.display || {};
			var v = {};
			['price', 'change', 'hashprice', 'height'].forEach(function (k) { if (d[k] && d[k] !== '—') { v[k] = d[k]; } });
			if (m.adjustment && isFinite(m.adjustment.change)) {
				v.adj = (m.adjustment.change > 0 ? '+' : '') + num(m.adjustment.change, 2) + '%';
				if (m.adjustment.remaining_blocks) { v.adj_days = days(m.adjustment.remaining_blocks); }
			}
			if (m.halving && m.halving.blocks_left) { v.halving_days = num(Math.round(m.halving.blocks_left * 10 / 1440), 0); }
			if (G.miner) {
				v.name = G.miner.name;
				if (m.difficulty > 0 && m.price > 0 && m.height > 0) {
					var reward = 50 / Math.pow(2, Math.floor(m.height / 210000)) + (Number(m.fees) || 0);
					var usd = 1e12 * 86400 * reward / (m.difficulty * 4294967296) * G.miner.th * m.price;
					v.earn = money(usd);
					if (G.miner.w > 0) { v.breakeven = money(usd / (G.miner.w * 24 / 1000)); }
				}
			}
			return v;
		}
		/** Text split into plain and bold (live value) parts, or null when a value is missing. */
		function parts(msg) {
			var v = values();
			var out = [];
			var ok = true;
			String(msg.t).split(/(\{[a-z_]+\})/).forEach(function (p) {
				if (!p) { return; }
				var m = p.match(/^\{([a-z_]+)\}$/);
				if (!m) { out.push({ s: p }); return; }
				if (v[m[1]] === undefined) { ok = false; return; }
				out.push({ s: String(v[m[1]]), b: m[1] !== 'name' });
			});
			return ok ? out : null;
		}
		function render(list, chars) {
			vis.textContent = '';
			var left = chars;
			for (var i = 0; i < list.length && left > 0; i++) {
				var s = list[i].s.slice(0, left);
				left -= s.length;
				if (list[i].b) {
					var b = doc.createElement('b');
					b.textContent = s;
					vis.appendChild(b);
				} else {
					vis.appendChild(doc.createTextNode(s));
				}
			}
		}
		function pick(from) {
			for (var k = 1; k <= G.msgs.length; k++) {
				var i = (from + k) % G.msgs.length;
				var p = parts(G.msgs[i]);
				if (p) { return { i: i, p: p }; }
			}
			return null;
		}
		function say(step) {
			clearTimeout(timer);
			clearInterval(typer);
			var m = pick(step);
			if (!m) { return false; }
			index = m.i;
			var msg = G.msgs[index];
			var total = m.p.reduce(function (n, x) { return n + x.s.length; }, 0);
			live.textContent = m.p.map(function (x) { return x.s; }).join('');
			$$('span', steps).forEach(function (s, i) { s.classList.toggle('is-on', i === index); });
			cta.hidden = true;
			function done() {
				render(m.p, total);
				if (msg.a && msg.u) {
					cta.href = msg.u;
					cta.querySelector('span').textContent = msg.a;
					cta.hidden = false;
				}
				shown++;
				schedule();
			}
			if (!anim) { done(); return true; }
			vis.innerHTML = '<span class="mm-guide__typing"><i></i><i></i><i></i></span>';
			timer = setTimeout(function () {
				var c = 0;
				var per = Math.max(2, Math.ceil(total / 70));
				typer = setInterval(function () {
					c += per;
					if (c >= total) { clearInterval(typer); done(); return; }
					render(m.p, c);
				}, 18);
			}, 850);
			return true;
		}
		function schedule() {
			clearTimeout(timer);
			if (quiet) { return; }
			// Phones have little room: show one message, then fold back to the coin with a dot.
			var small = window.innerWidth < 640;
			timer = setTimeout(function () {
				if (hold || doc.hidden) { schedule(); return; }
				if (small || shown >= G.msgs.length) {
					setOpen(false);
					if (shown < G.msgs.length) { box.classList.add('has-unread'); }
					return;
				}
				say(index);
			}, small ? 9000 : (shown < G.msgs.length ? 11000 : 14000));
		}
		function setOpen(open) {
			box.classList.toggle('is-open', open);
			avatar.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open) { box.classList.remove('has-unread'); }
			if (!open) { clearTimeout(timer); clearInterval(typer); }
		}
		function past() { return window.scrollY > window.innerHeight * 0.45; }
		function start() {
			if (started) { return; }
			started = true;
			doc.body.appendChild(box);
			box.classList.add('is-in');
			// Still on the first screen: just the coin with a dot until the visitor scrolls on.
			if (!auto || !past()) { box.classList.add('has-unread'); return; }
			if (say(-1)) { setOpen(true); } else { box.classList.add('has-unread'); }
		}

		avatar.addEventListener('click', function () {
			if (box.classList.contains('is-open')) { setOpen(false); return; }
			if (say(index)) { setOpen(true); }
		});
		next.addEventListener('click', function () { say(index); });
		close.addEventListener('click', function () {
			quiet = true;
			session.set('mm-guide-quiet', '1');
			setOpen(false);
			avatar.focus();
		});
		box.addEventListener('pointerenter', function () { hold = true; });
		box.addEventListener('pointerleave', function () { hold = false; });
		box.addEventListener('focusin', function () { hold = true; });
		box.addEventListener('focusout', function () { hold = false; });
		doc.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && box.classList.contains('is-open')) { setOpen(false); }
		});

		MM.on('market', function (data) {
			if (!data || !data.display) { return; }
			market = data;
			// The first message may have been waiting for live numbers.
			if (started && auto && !quiet && !shown && !box.classList.contains('is-open') && say(-1)) { setOpen(true); }
		});

		// Speak once the visitor has had time to look and has moved past the first screen, so the
		// bubble never covers the opening headline and buttons. A visitor who stays on the first
		// screen hears from it a little later.
		var t0 = Date.now();
		var delay = G.delay || 6000;
		function ready() {
			var spent = Date.now() - t0;
			return spent >= delay && (past() || spent >= delay + 14000);
		}
		function check() {
			if (!started) {
				if (ready()) { clearInterval(poll); start(); }
				return;
			}
			if (auto && !quiet && !shown && past() && !box.classList.contains('is-open')) {
				window.removeEventListener('scroll', onScroll);
				if (say(-1)) { setOpen(true); }
			} else if (shown || !auto || quiet) {
				window.removeEventListener('scroll', onScroll);
			}
		}
		var onScroll = MM.rafThrottle(check);
		var poll = setInterval(check, 1000);
		window.addEventListener('scroll', onScroll, { passive: true });
	}

	function boot() {
		initDeco();
		initSpotlight();
		initBlocks();
		initGuide(window.MM_GUIDE);
	}
	if (doc.readyState === 'loading') { doc.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
