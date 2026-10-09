/*!
 * Mine Mirth — core front-end behaviour.
 * Light/dark mode, header, drawer, search, reveal animations, tabs,
 * accordions, counters, effects, toasts and newsletter forms.
 *
 * Exposes window.MineMirth for the other theme scripts.
 */
(function () {
	'use strict';

	var cfg = window.MM_CONFIG || {};
	var i18n = cfg.i18n || {};
	var doc = document;
	var root = doc.documentElement;
	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var finePointer = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
	var startTime = (window.performance && performance.now()) || 0;

	/* ------------------------------------------------------------------
	 * Small utilities
	 * ---------------------------------------------------------------- */
	function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
	function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
	function on(el, type, fn, opts) { if (el) { el.addEventListener(type, fn, opts || false); } }
	function rafThrottle(fn) {
		var queued = false, lastArgs;
		return function () {
			lastArgs = arguments;
			if (queued) { return; }
			queued = true;
			requestAnimationFrame(function () { queued = false; fn.apply(null, lastArgs); });
		};
	}
	function debounce(fn, wait) {
		var t;
		return function () {
			var args = arguments;
			clearTimeout(t);
			t = setTimeout(function () { fn.apply(null, args); }, wait);
		};
	}
	function decode(str) {
		var t = doc.createElement('textarea');
		t.innerHTML = String(str == null ? '' : str);
		return t.value;
	}
	function el(tag, attrs, children) {
		var node = doc.createElement(tag);
		if (attrs) {
			Object.keys(attrs).forEach(function (k) {
				if (k === 'text') { node.textContent = attrs[k]; }
				else if (k === 'html') { node.innerHTML = attrs[k]; }
				else if (k === 'class') { node.className = attrs[k]; }
				else if (attrs[k] !== null && attrs[k] !== undefined) { node.setAttribute(k, attrs[k]); }
			});
		}
		(children || []).forEach(function (c) { if (c) { node.appendChild(typeof c === 'string' ? doc.createTextNode(c) : c); } });
		return node;
	}
	var store = {
		get: function (k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } },
		set: function (k, v) { try { window.localStorage.setItem(k, v); } catch (e) { /* private mode */ } }
	};

	/* Event bus */
	var listeners = {};
	function onEvent(name, fn) { (listeners[name] = listeners[name] || []).push(fn); }
	function emit(name, data) { (listeners[name] || []).forEach(function (fn) { try { fn(data); } catch (e) { if (window.console) { console.error(e); } } }); }

	/* Icons used by JS-rendered markup (kept tiny). */
	var ICONS = {
		check: '<svg class="mm-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>',
		alert: '<svg class="mm-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16h.01"/></svg>',
		file: '<svg class="mm-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2.5H6.5a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V8Z"/><path d="M14 2.5V8h5.5"/></svg>',
		arrow: '<svg class="mm-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>'
	};

	/* ------------------------------------------------------------------
	 * Toasts
	 * ---------------------------------------------------------------- */
	function toast(message, opts) {
		opts = opts || {};
		var wrap = $('[data-mm-toasts]');
		if (!wrap) { return; }
		var node = el('div', { class: 'mm-toast' + (opts.type === 'error' ? ' mm-toast--error' : ''), role: 'status' });
		node.innerHTML = opts.type === 'error' ? ICONS.alert : ICONS.check;
		node.appendChild(el('span', { text: message }));
		if (opts.link && opts.linkText) {
			node.appendChild(el('a', { href: opts.link, text: opts.linkText }));
		}
		wrap.appendChild(node);
		setTimeout(function () {
			node.classList.add('is-leaving');
			setTimeout(function () { node.remove(); }, 400);
		}, opts.duration || 3800);
	}

	/* ------------------------------------------------------------------
	 * Light / dark mode
	 * ---------------------------------------------------------------- */
	var mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
	function getPref() {
		var p = (cfg.mode && cfg.mode.toggle) ? store.get('mm-theme') : null;
		return p || (cfg.mode && cfg.mode.default) || 'system';
	}
	function resolve(pref) {
		if (pref === 'system') { return mql && mql.matches ? 'dark' : 'light'; }
		return pref === 'dark' ? 'dark' : 'light';
	}
	function applyMode(pref) {
		var mode = resolve(pref);
		root.setAttribute('data-theme', mode);
		root.setAttribute('data-theme-pref', pref);
		root.style.colorScheme = mode;
		$$('[data-mm-mode-toggle]').forEach(function (b) {
			b.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
			b.setAttribute('aria-label', mode === 'dark' ? (i18n.toLight || 'Switch to light mode') : (i18n.toDark || 'Switch to dark mode'));
		});
		$$('[data-mm-mode-set]').forEach(function (b) {
			b.setAttribute('aria-checked', b.getAttribute('data-mm-mode-set') === pref ? 'true' : 'false');
		});
		emit('theme', mode);
		try { doc.dispatchEvent(new CustomEvent('mm:themechange', { detail: { mode: mode, pref: pref } })); } catch (e) { /* old browsers */ }
	}
	function setMode(pref, origin) {
		store.set('mm-theme', pref);
		var current = root.getAttribute('data-theme');
		var next = resolve(pref);
		var useTransition = origin && next !== current && doc.startViewTransition && !reduceMotion && cfg.mode && cfg.mode.transition;
		if (!useTransition) { applyMode(pref); return; }
		var rect = origin.getBoundingClientRect();
		var x = rect.left + rect.width / 2;
		var y = rect.top + rect.height / 2;
		var r = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
		root.classList.add('mm-vt');
		var t = doc.startViewTransition(function () { applyMode(pref); });
		t.ready.then(function () {
			root.animate(
				{ clipPath: ['circle(0px at ' + x + 'px ' + y + 'px)', 'circle(' + r + 'px at ' + x + 'px ' + y + 'px)'] },
				{ duration: 700, easing: 'cubic-bezier(.16,1,.3,1)', pseudoElement: '::view-transition-new(root)' }
			);
		}).catch(function () {});
		t.finished.then(function () { root.classList.remove('mm-vt'); }).catch(function () { root.classList.remove('mm-vt'); });
	}
	function initMode() {
		applyMode(getPref());
		on(doc, 'click', function (e) {
			var t = e.target.closest('[data-mm-mode-toggle]');
			if (t) {
				e.preventDefault();
				setMode(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark', t);
				return;
			}
			var s = e.target.closest('[data-mm-mode-set]');
			if (s) {
				e.preventDefault();
				setMode(s.getAttribute('data-mm-mode-set'), s);
			}
		});
		if (mql) {
			var onSys = function () { if (getPref() === 'system') { applyMode('system'); } };
			if (mql.addEventListener) { mql.addEventListener('change', onSys); } else if (mql.addListener) { mql.addListener(onSys); }
		}
	}

	/* ------------------------------------------------------------------
	 * Header: scrolled state, auto-hide, back-to-top, scroll progress
	 * ---------------------------------------------------------------- */
	function initScroll() {
		var header = $('[data-mm-header]');
		var toTop = $('[data-mm-to-top]');
		var lastY = window.scrollY;
		var autohide = cfg.header && cfg.header.autohide;
		if (autohide) { doc.body.classList.add('mm-header-autohide'); }

		var update = rafThrottle(function () {
			var y = window.scrollY;
			var max = Math.max(1, doc.documentElement.scrollHeight - innerHeight);
			if (header) {
				header.classList.toggle('is-scrolled', y > 8);
				if (autohide && !doc.body.classList.contains('mm-lock')) {
					var down = y > lastY && y > 240;
					header.classList.toggle('is-hidden', down);
				}
			}
			if (toTop) {
				toTop.classList.toggle('is-visible', y > 600);
				toTop.style.setProperty('--p', (y / max).toFixed(3));
			}
			$$('[data-mm-progress]').forEach(function (wrap) {
				var r = wrap.getBoundingClientRect();
				var p = (innerHeight * 0.75 - r.top) / (r.height || 1);
				wrap.style.setProperty('--p', Math.max(0, Math.min(1, p)).toFixed(3));
			});
			lastY = y;
			emit('scroll', y);
		});
		on(window, 'scroll', update, { passive: true });
		on(window, 'resize', update);
		update();

		on(toTop, 'click', function () {
			window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
			var skip = $('#mm-page');
			if (skip) { skip.setAttribute('tabindex', '-1'); skip.focus({ preventScroll: true }); }
		});
	}

	/* ------------------------------------------------------------------
	 * Overlays (drawer, search, modals) with focus management
	 * ---------------------------------------------------------------- */
	var openOverlay = null;
	var lastFocus = null;
	function lockScroll(lock) {
		var sb = innerWidth - doc.documentElement.clientWidth;
		doc.body.classList.toggle('mm-lock', lock);
		root.style.overflow = lock ? 'hidden' : '';
		root.style.paddingRight = lock && sb > 0 ? sb + 'px' : '';
	}
	function focusables(node) {
		return $$('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select, textarea, [tabindex]:not([tabindex="-1"])', node)
			.filter(function (n) { return n.offsetParent !== null || n === doc.activeElement; });
	}
	function openPanel(panel, focusSel) {
		if (!panel) { return; }
		if (openOverlay && openOverlay !== panel) { closePanel(openOverlay, true); }
		lastFocus = doc.activeElement;
		panel.removeAttribute('inert');
		panel.classList.add('is-open');
		openOverlay = panel;
		lockScroll(true);
		var target = focusSel ? $(focusSel, panel) : focusables(panel)[0];
		setTimeout(function () { if (target) { target.focus({ preventScroll: true }); } }, 60);
		emit('overlay:open', panel);
	}
	function closePanel(panel, keepLock) {
		if (!panel) { return; }
		panel.classList.remove('is-open');
		panel.setAttribute('inert', '');
		if (openOverlay === panel) { openOverlay = null; }
		if (!keepLock) { lockScroll(false); }
		if (lastFocus && lastFocus.focus) { lastFocus.focus({ preventScroll: true }); }
		emit('overlay:close', panel);
	}
	on(doc, 'keydown', function (e) {
		if (!openOverlay) { return; }
		if (e.key === 'Escape') {
			e.preventDefault();
			closePanel(openOverlay);
			syncBurger();
			return;
		}
		if (e.key === 'Tab') {
			var f = focusables(openOverlay);
			if (!f.length) { return; }
			var first = f[0], last = f[f.length - 1];
			if (e.shiftKey && doc.activeElement === first) { e.preventDefault(); last.focus(); }
			else if (!e.shiftKey && doc.activeElement === last) { e.preventDefault(); first.focus(); }
		}
	});

	function syncBurger() {
		var drawer = $('[data-mm-drawer]');
		$$('[data-mm-drawer-open]').forEach(function (b) {
			b.setAttribute('aria-expanded', drawer && drawer.classList.contains('is-open') ? 'true' : 'false');
		});
	}

	function initDrawer() {
		var drawer = $('[data-mm-drawer]');
		on(doc, 'click', function (e) {
			if (e.target.closest('[data-mm-drawer-open]')) {
				e.preventDefault();
				if (drawer && drawer.classList.contains('is-open')) { closePanel(drawer); } else { openPanel(drawer); }
				syncBurger();
			} else if (e.target.closest('[data-mm-drawer-close]')) {
				e.preventDefault();
				closePanel(drawer);
				syncBurger();
			}
		});
		// Close the drawer when the viewport grows past the mobile breakpoint.
		on(window, 'resize', debounce(function () {
			if (innerWidth >= 1100 && drawer && drawer.classList.contains('is-open')) { closePanel(drawer); syncBurger(); }
		}, 150));
		// In-page links inside the drawer should close it.
		on(drawer, 'click', function (e) {
			var a = e.target.closest('a[href*="#"]');
			if (a && a.pathname === location.pathname) { closePanel(drawer); syncBurger(); }
		});
	}

	function initSubmenus(ctx) {
		$$('.mm-submenu-toggle', ctx).forEach(function (btn) {
			if (btn.dataset.mmBound) { return; }
			btn.dataset.mmBound = '1';
			on(btn, 'click', function (e) {
				e.preventDefault();
				var li = btn.closest('li');
				var open = !li.classList.contains('is-open');
				li.classList.toggle('is-open', open);
				btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			});
		});
		// Desktop: Escape closes an open dropdown and returns focus to its link.
		$$('.mm-nav .mm-menu > li', ctx).forEach(function (li) {
			on(li, 'keydown', function (e) {
				if (e.key === 'Escape') {
					var link = $('.mm-menu-link', li);
					li.classList.remove('is-open');
					if (link) { link.focus(); link.blur(); link.focus(); }
				}
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Search overlay with live results
	 * ---------------------------------------------------------------- */
	function formatStorePrice(prices) {
		if (!prices || prices.price === undefined) { return ''; }
		var minor = parseInt(prices.currency_minor_unit, 10) || 0;
		var value = parseInt(prices.price, 10) / Math.pow(10, minor);
		var num;
		try {
			num = value.toLocaleString((cfg.market && cfg.market.locale) || undefined, { minimumFractionDigits: minor, maximumFractionDigits: minor });
		} catch (e) { num = value.toFixed(minor); }
		return (prices.currency_prefix || '') + num + (prices.currency_suffix || '');
	}

	function initSearch() {
		var panel = $('[data-mm-search]');
		if (!panel) { return; }
		var input = $('[data-mm-search-input]', panel);
		var results = $('[data-mm-search-results]', panel);
		var controller = null;
		var activeIndex = -1;

		function open() { openPanel(panel, '[data-mm-search-input]'); }
		on(doc, 'click', function (e) {
			if (e.target.closest('[data-mm-search-open]')) { e.preventDefault(); open(); }
			else if (e.target.closest('[data-mm-search-close]')) { e.preventDefault(); closePanel(panel); }
		});
		on(doc, 'keydown', function (e) {
			var tag = (e.target.tagName || '').toLowerCase();
			var typing = tag === 'input' || tag === 'textarea' || e.target.isContentEditable;
			if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) { e.preventDefault(); open(); }
			else if (e.key === '/' && !typing && !openOverlay) { e.preventDefault(); open(); }
		});

		$$('[data-mm-search-term]', panel).forEach(function (chip) {
			on(chip, 'click', function (e) {
				e.preventDefault();
				input.value = chip.getAttribute('data-mm-search-term');
				run();
				input.focus();
			});
		});

		function items() { return $$('.mm-search__item', results); }
		function setActive(i) {
			var list = items();
			list.forEach(function (n) { n.classList.remove('is-active'); });
			activeIndex = i;
			if (list[i]) { list[i].classList.add('is-active'); list[i].scrollIntoView({ block: 'nearest' }); }
		}
		on(input, 'keydown', function (e) {
			var list = items();
			if (!list.length) { return; }
			if (e.key === 'ArrowDown') { e.preventDefault(); setActive(Math.min(list.length - 1, activeIndex + 1)); }
			else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(Math.max(0, activeIndex - 1)); }
			else if (e.key === 'Enter' && activeIndex > -1 && list[activeIndex]) { e.preventDefault(); location.href = list[activeIndex].href; }
		});

		function group(title, nodes) {
			var g = el('div', { class: 'mm-search__group' });
			g.appendChild(el('span', { class: 'mm-search__label', text: title }));
			nodes.forEach(function (n) { g.appendChild(n); });
			return g;
		}
		function productItem(p) {
			var a = el('a', { class: 'mm-search__item', href: p.permalink });
			var thumb = el('span', { class: 'mm-search__thumb' });
			if (p.images && p.images[0]) {
				thumb.appendChild(el('img', { src: p.images[0].thumbnail || p.images[0].src, alt: '', loading: 'lazy' }));
			}
			a.appendChild(thumb);
			var meta = el('span', { class: 'mm-search__meta' });
			meta.appendChild(el('span', { class: 'mm-search__title', text: decode(p.name) }));
			meta.appendChild(el('span', { class: 'mm-search__sub', text: p.is_in_stock === false ? (i18n.outOfStock || 'Out of stock') : (i18n.inStock || 'In stock') }));
			a.appendChild(meta);
			var price = formatStorePrice(p.prices);
			if (price) { a.appendChild(el('span', { class: 'mm-search__price', text: price })); }
			return a;
		}
		function postItem(r) {
			var a = el('a', { class: 'mm-search__item', href: r.url });
			var thumb = el('span', { class: 'mm-search__thumb', html: ICONS.file });
			a.appendChild(thumb);
			var meta = el('span', { class: 'mm-search__meta' });
			meta.appendChild(el('span', { class: 'mm-search__title', text: decode(r.title) }));
			meta.appendChild(el('span', { class: 'mm-search__sub', text: r.subtype === 'page' ? 'Page' : 'Article' }));
			a.appendChild(meta);
			return a;
		}

		var run = debounce(function () {
			var q = input.value.trim();
			panel.classList.toggle('has-query', q.length > 0);
			activeIndex = -1;
			if (q.length < 2) { results.innerHTML = ''; return; }
			if (controller) { controller.abort(); }
			controller = window.AbortController ? new AbortController() : null;
			var signal = controller ? controller.signal : undefined;
			results.innerHTML = '';
			results.appendChild(el('div', { class: 'mm-search__empty', text: i18n.searching || 'Searching…' }));

			var reqs = [];
			var base = cfg.rest || '/wp-json/';
			if (cfg.search && cfg.search.products) {
				reqs.push(fetch(base + 'wc/store/v1/products?per_page=6&search=' + encodeURIComponent(q), { signal: signal, credentials: 'same-origin' })
					.then(function (r) { return r.ok ? r.json() : []; }).catch(function () { return []; }));
			} else { reqs.push(Promise.resolve([])); }
			if (cfg.search && cfg.search.posts) {
				reqs.push(fetch(base + 'wp/v2/search?per_page=5&subtype[]=post&subtype[]=page&search=' + encodeURIComponent(q), { signal: signal, credentials: 'same-origin' })
					.then(function (r) { return r.ok ? r.json() : []; }).catch(function () { return []; }));
			} else { reqs.push(Promise.resolve([])); }

			Promise.all(reqs).then(function (res) {
				if (input.value.trim() !== q) { return; }
				results.innerHTML = '';
				var products = Array.isArray(res[0]) ? res[0] : [];
				var posts = Array.isArray(res[1]) ? res[1] : [];
				if (!products.length && !posts.length) {
					results.appendChild(el('div', { class: 'mm-search__empty', text: i18n.noResults || 'No results.' }));
					return;
				}
				if (products.length) { results.appendChild(group(i18n.products || 'Products', products.map(productItem))); }
				if (posts.length) { results.appendChild(group(i18n.articles || 'Articles', posts.map(postItem))); }
				var all = el('div', { class: 'mm-search__all' });
				var link = el('a', { class: 'mm-btn mm-btn--ghost mm-btn--sm', href: (cfg.home || '/') + '?s=' + encodeURIComponent(q) });
				link.appendChild(el('span', { text: i18n.viewAll || 'See all results' }));
				link.insertAdjacentHTML('beforeend', ICONS.arrow);
				all.appendChild(link);
				results.appendChild(all);
			});
		}, 220);
		on(input, 'input', run);
	}

	/* ------------------------------------------------------------------
	 * Reveal on scroll
	 * ---------------------------------------------------------------- */
	var revealObserver = null;
	function initReveal(ctx) {
		var nodes = $$('[data-mm-reveal]:not(.is-in)', ctx);
		var animate = root.classList.contains('mm-anim') && 'IntersectionObserver' in window && !window.wp?.customize;
		if (!animate) {
			nodes.forEach(function (n) { n.classList.add('is-in'); });
			return;
		}
		if (!revealObserver) {
			revealObserver = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-in');
						revealObserver.unobserve(entry.target);
						emit('reveal', entry.target);
					}
				});
			}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
		}
		var late = ((window.performance && performance.now()) || 0) - startTime > 2400;
		nodes.forEach(function (n) {
			if (late) {
				var r = n.getBoundingClientRect();
				if (r.top < innerHeight) { n.classList.add('is-in'); return; }
			}
			revealObserver.observe(n);
		});
	}

	/* ------------------------------------------------------------------
	 * Hero headline: split words + rotating word
	 * ---------------------------------------------------------------- */
	function splitWords(heading) {
		if (heading.classList.contains('is-split')) { return; }
		var index = 0;
		function walk(node) {
			Array.prototype.slice.call(node.childNodes).forEach(function (child) {
				if (child.nodeType === 3) {
					var parts = child.textContent.split(/(\s+)/);
					var frag = doc.createDocumentFragment();
					var inGrad = !!(child.parentNode.closest && child.parentNode.closest('.mm-grad-text'));
					parts.forEach(function (part) {
						if (!part) { return; }
						if (/^\s+$/.test(part)) { frag.appendChild(doc.createTextNode(part)); return; }
						var span = el('span', { class: 'mm-word' + (inGrad ? ' mm-grad-text' : ''), text: part });
						span.style.setProperty('--w', index++);
						frag.appendChild(span);
					});
					child.parentNode.replaceChild(frag, child);
				} else if (child.nodeType === 1) {
					if (child.classList.contains('mm-rotator')) {
						var wrap = el('span', { class: 'mm-word' });
						wrap.style.setProperty('--w', index++);
						child.parentNode.insertBefore(wrap, child);
						wrap.appendChild(child);
					} else {
						walk(child);
					}
				}
			});
		}
		walk(heading);
		heading.classList.add('is-split');
	}

	function initRotators(ctx) {
		$$('[data-mm-rotate]', ctx).forEach(function (rot) {
			if (rot.dataset.mmBound) { return; }
			rot.dataset.mmBound = '1';
			var words;
			try { words = JSON.parse(rot.getAttribute('data-mm-rotate')); } catch (e) { words = []; }
			if (!words || words.length < 2) { return; }
			rot.innerHTML = '';
			var spans = words.map(function (w, i) {
				var s = el('span', { class: 'mm-rotator__word' + (i === 0 ? ' is-active' : ''), text: w });
				if (i) { s.setAttribute('aria-hidden', 'true'); }
				rot.appendChild(s);
				return s;
			});
			var i = 0;
			function size() { rot.style.width = Math.ceil(spans[i].getBoundingClientRect().width) + 'px'; }
			requestAnimationFrame(size);
			if (doc.fonts && doc.fonts.ready) { doc.fonts.ready.then(size); }
			on(window, 'resize', debounce(size, 150));
			if (reduceMotion) { return; }
			setInterval(function () {
				if (doc.hidden) { return; }
				var prev = spans[i];
				i = (i + 1) % spans.length;
				prev.classList.remove('is-active');
				prev.classList.add('is-leaving');
				prev.setAttribute('aria-hidden', 'true');
				spans[i].classList.remove('is-leaving');
				spans[i].classList.add('is-active');
				spans[i].removeAttribute('aria-hidden');
				size();
				setTimeout(function () { prev.classList.remove('is-leaving'); }, 800);
			}, 2600);
		});
	}

	/* ------------------------------------------------------------------
	 * Pointer effects: tilt + parallax, magnetic buttons, spotlight
	 * ---------------------------------------------------------------- */
	function initTilt(ctx) {
		if (!finePointer || reduceMotion || !(cfg.fx && cfg.fx.tilt)) { return; }
		$$('[data-mm-tilt]', ctx).forEach(function (zone) {
			var hero = zone.closest('.mm-hero') || zone;
			var stage = $('.mm-hero__stage', zone) || zone;
			var floats = $$('[data-mm-depth]', zone);
			var move = rafThrottle(function (e) {
				var r = hero.getBoundingClientRect();
				var px = (e.clientX - r.left) / r.width - 0.5;
				var py = (e.clientY - r.top) / r.height - 0.5;
				stage.style.setProperty('--ry', (px * 10).toFixed(2) + 'deg');
				stage.style.setProperty('--rx', (-py * 8).toFixed(2) + 'deg');
				floats.forEach(function (f) {
					var d = parseFloat(f.getAttribute('data-mm-depth')) || 1;
					f.style.setProperty('--px', (px * 18 * d).toFixed(1) + 'px');
					f.style.setProperty('--py', (py * 18 * d).toFixed(1) + 'px');
				});
			});
			on(hero, 'pointermove', move);
			on(hero, 'pointerleave', function () {
				stage.style.setProperty('--rx', '0deg');
				stage.style.setProperty('--ry', '0deg');
				floats.forEach(function (f) { f.style.setProperty('--px', '0px'); f.style.setProperty('--py', '0px'); });
			});
		});
	}

	function initMagnetic(ctx) {
		if (!finePointer || reduceMotion) { return; }
		$$('[data-mm-magnetic]', ctx).forEach(function (btn) {
			if (btn.dataset.mmMag) { return; }
			btn.dataset.mmMag = '1';
			on(btn, 'pointermove', function (e) {
				var r = btn.getBoundingClientRect();
				var x = (e.clientX - r.left - r.width / 2) / r.width;
				var y = (e.clientY - r.top - r.height / 2) / r.height;
				btn.style.transform = 'translate(' + (x * 8).toFixed(1) + 'px,' + (y * 8).toFixed(1) + 'px)';
			});
			on(btn, 'pointerleave', function () { btn.style.transform = ''; });
		});
	}

	function initSpotlight() {
		if (!finePointer || !(cfg.fx && cfg.fx.spotlight)) { return; }
		on(doc, 'pointermove', rafThrottle(function (e) {
			var card = e.target.closest && e.target.closest('.mm-card');
			if (!card) { return; }
			var r = card.getBoundingClientRect();
			card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
			card.style.setProperty('--my', (e.clientY - r.top) + 'px');
		}), { passive: true });
	}

	/* ------------------------------------------------------------------
	 * Tabs
	 * ---------------------------------------------------------------- */
	function initTabs(ctx) {
		$$('[data-mm-tabs]', ctx).forEach(function (wrap) {
			if (wrap.dataset.mmBound) { return; }
			wrap.dataset.mmBound = '1';
			var tabs = $$('[role="tab"]', wrap);
			var ink = $('.mm-tabs__ink', wrap);
			function moveInk() {
				var current = tabs.filter(function (t) { return t.getAttribute('aria-selected') === 'true'; })[0];
				if (!ink || !current) { return; }
				ink.style.setProperty('--x', current.offsetLeft + 'px');
				ink.style.setProperty('--w', current.offsetWidth + 'px');
			}
			function select(tab, focus) {
				tabs.forEach(function (t) {
					var sel = t === tab;
					t.setAttribute('aria-selected', sel ? 'true' : 'false');
					t.setAttribute('tabindex', sel ? '0' : '-1');
					var panel = doc.getElementById(t.getAttribute('aria-controls'));
					if (panel) {
						panel.hidden = !sel;
						if (sel) {
							panel.classList.remove('is-entering');
							void panel.offsetWidth;
							panel.classList.add('is-entering');
							initReveal(panel);
						}
					}
				});
				if (focus) { tab.focus(); }
				moveInk();
			}
			tabs.forEach(function (tab, i) {
				on(tab, 'click', function () { select(tab); });
				on(tab, 'keydown', function (e) {
					var next = null;
					if (e.key === 'ArrowRight') { next = tabs[(i + 1) % tabs.length]; }
					if (e.key === 'ArrowLeft') { next = tabs[(i - 1 + tabs.length) % tabs.length]; }
					if (e.key === 'Home') { next = tabs[0]; }
					if (e.key === 'End') { next = tabs[tabs.length - 1]; }
					if (next) { e.preventDefault(); select(next, true); }
				});
			});
			moveInk();
			if (doc.fonts && doc.fonts.ready) { doc.fonts.ready.then(moveInk); }
			on(window, 'resize', debounce(moveInk, 120));
		});
	}

	/* ------------------------------------------------------------------
	 * Accordions: animated <details>
	 * ---------------------------------------------------------------- */
	function initAccordions(ctx) {
		$$('[data-mm-accordion]', ctx).forEach(function (acc) {
			if (acc.dataset.mmBound) { return; }
			acc.dataset.mmBound = '1';
			$$('details', acc).forEach(function (d) {
				var summary = $('summary', d);
				var body = $('.mm-accordion__body', d);
				if (!summary || !body) { return; }
				on(summary, 'click', function (e) {
					if (reduceMotion || !body.animate) { return; }
					e.preventDefault();
					if (d.open) {
						var h = body.offsetHeight;
						var anim = body.animate({ height: [h + 'px', '0px'], opacity: [1, 0] }, { duration: 280, easing: 'cubic-bezier(.2,.7,.2,1)' });
						anim.onfinish = function () { d.open = false; };
					} else {
						$$('details[open]', acc).forEach(function (o) {
							if (o !== d) {
								var ob = $('.mm-accordion__body', o);
								var oh = ob.offsetHeight;
								var a2 = ob.animate({ height: [oh + 'px', '0px'], opacity: [1, 0] }, { duration: 260, easing: 'cubic-bezier(.2,.7,.2,1)' });
								a2.onfinish = function () { o.open = false; };
							}
						});
						d.open = true;
						var h2 = body.offsetHeight;
						body.animate({ height: ['0px', h2 + 'px'], opacity: [0, 1] }, { duration: 380, easing: 'cubic-bezier(.16,1,.3,1)' });
					}
				});
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Counters
	 * ---------------------------------------------------------------- */
	function animateCount(node) {
		var raw = node.getAttribute('data-mm-count') || node.textContent;
		var m = String(raw).match(/^([^0-9\-]*)(-?[0-9][0-9,]*\.?[0-9]*)(.*)$/);
		if (!m || reduceMotion) { return; }
		var prefix = m[1], suffix = m[3];
		var hasComma = m[2].indexOf(',') > -1;
		var target = parseFloat(m[2].replace(/,/g, ''));
		var decimals = (m[2].split('.')[1] || '').length;
		var start = null, dur = 1600;
		function frame(ts) {
			if (!start) { start = ts; }
			var p = Math.min(1, (ts - start) / dur);
			var eased = 1 - Math.pow(1 - p, 4);
			var val = target * eased;
			var str = val.toFixed(decimals);
			if (hasComma) { str = Number(str).toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }); }
			node.textContent = prefix + str + suffix;
			if (p < 1) { requestAnimationFrame(frame); }
		}
		requestAnimationFrame(frame);
	}
	function initCounters(ctx) {
		var nodes = $$('[data-mm-count]', ctx);
		if (!nodes.length) { return; }
		if (!('IntersectionObserver' in window)) { return; }
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (e.isIntersecting) { animateCount(e.target); io.unobserve(e.target); }
			});
		}, { threshold: 0.4 });
		nodes.forEach(function (n) { io.observe(n); });
	}

	/* ------------------------------------------------------------------
	 * Announcement bar
	 * ---------------------------------------------------------------- */
	function initAnnouncement() {
		on(doc, 'click', function (e) {
			var btn = e.target.closest('[data-mm-announce-close]');
			if (!btn) { return; }
			var bar = btn.closest('[data-mm-announce]');
			if (!bar) { return; }
			store.set('mm-announce', bar.getAttribute('data-mm-announce'));
			bar.style.maxHeight = bar.offsetHeight + 'px';
			bar.classList.add('is-closing');
			setTimeout(function () { root.classList.add('mm-announce-off'); }, 380);
		});
	}

	/* ------------------------------------------------------------------
	 * Newsletter forms (AJAX for the built-in subscriber list)
	 * ---------------------------------------------------------------- */
	function initNewsletter(ctx) {
		$$('form[data-mm-subscribe="local"]', ctx).forEach(function (form) {
			if (form.dataset.mmBound) { return; }
			form.dataset.mmBound = '1';
			on(form, 'submit', function (e) {
				e.preventDefault();
				var wrap = form.closest('.mm-subscribe') || form;
				var msg = $('.mm-subscribe__msg', wrap);
				var btn = $('button[type="submit"]', form);
				var data = new FormData(form);
				if (btn) { btn.classList.add('is-loading'); }
				fetch((cfg.newsletter && cfg.newsletter.endpoint) || '', {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'X-WP-Nonce': cfg.restNonce || '' },
					body: data
				}).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
					.then(function (res) {
						if (msg) {
							msg.textContent = (res.json && res.json.message) || (res.ok ? '' : (i18n.error || 'Error'));
							msg.className = 'mm-subscribe__msg ' + (res.ok ? 'is-ok' : 'is-error');
						}
						if (res.ok) { wrap.classList.add('is-done'); }
					})
					.catch(function () {
						if (msg) { msg.textContent = i18n.error || 'Error'; msg.className = 'mm-subscribe__msg is-error'; }
					})
					.then(function () { if (btn) { btn.classList.remove('is-loading'); } });
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Misc
	 * ---------------------------------------------------------------- */
	function initKbdHints() {
		var mac = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent);
		if (mac) { $$('[data-mm-kbd-mod]').forEach(function (k) { k.textContent = '⌘'; }); }
	}
	function initLoader() {
		var loader = $('[data-mm-loader]');
		if (!loader) { return; }
		if (store.get('mm-seen')) { loader.remove(); return; }
		var done = function () { loader.classList.add('is-done'); store.set('mm-seen', '1'); setTimeout(function () { loader.remove(); }, 700); };
		if (doc.readyState === 'complete') { setTimeout(done, 200); } else { on(window, 'load', function () { setTimeout(done, 200); }); }
		setTimeout(done, 2500);
	}

	/* ------------------------------------------------------------------
	 * Init (also re-run on Customizer partial refresh)
	 * ---------------------------------------------------------------- */
	function init(ctx) {
		ctx = ctx || doc;
		$$('[data-mm-split]', ctx).forEach(function (h) { if (root.classList.contains('mm-anim')) { splitWords(h); } });
		initRotators(ctx);
		initReveal(ctx);
		initTilt(ctx);
		initMagnetic(ctx);
		initTabs(ctx);
		initAccordions(ctx);
		initCounters(ctx);
		initNewsletter(ctx);
		initSubmenus(ctx);
		emit('init', ctx);
	}

	window.MineMirth = {
		config: cfg,
		init: init,
		on: onEvent,
		emit: emit,
		toast: toast,
		open: openPanel,
		close: closePanel,
		lockScroll: lockScroll,
		decode: decode,
		el: el,
		debounce: debounce,
		rafThrottle: rafThrottle,
		reduceMotion: reduceMotion,
		finePointer: finePointer,
		store: store,
		formatStorePrice: formatStorePrice
	};

	function boot() {
		initMode();
		initScroll();
		initDrawer();
		initSearch();
		initSpotlight();
		initAnnouncement();
		initKbdHints();
		initLoader();
		init(doc);
		root.classList.add('mm-ready');
		emit('ready');
	}

	if (doc.readyState === 'loading') { on(doc, 'DOMContentLoaded', boot); } else { boot(); }
})();
