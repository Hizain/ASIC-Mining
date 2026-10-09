/*!
 * Mine Mirth — articles: reading progress, table-of-contents scrollspy,
 * copy link, slide-in recommendation and promo device targeting.
 */
(function () {
	'use strict';

	var MM = window.MineMirth || {};
	var cfg = MM.config || {};
	var doc = document;

	function $(s, c) { return (c || doc).querySelector(s); }
	function $$(s, c) { return Array.prototype.slice.call((c || doc).querySelectorAll(s)); }

	var article = $('[data-mm-article]');

	/* Device targeting for promos (desktop-only / mobile-only). */
	function applyDevice() {
		var mobile = window.matchMedia('(max-width: 767px)').matches;
		$$('[data-mm-device]').forEach(function (p) {
			var want = p.getAttribute('data-mm-device');
			var hide = (want === 'mobile' && !mobile) || (want === 'desktop' && mobile);
			p.hidden = hide;
			var cue = p.nextElementSibling;
			if (cue && cue.classList.contains('mm-promo-continue')) { cue.hidden = hide; }
		});
	}
	applyDevice();
	window.addEventListener('resize', MM.debounce ? MM.debounce(applyDevice, 200) : applyDevice);

	/* Reading progress + slide-in trigger. */
	var bar = $('[data-mm-progress-bar]');
	var slide = $('[data-mm-slidein]');
	var slideAt = ((cfg.promo && cfg.promo.slideinAt) || 55) / 100;
	var slideKey = slide ? 'mm-slidein-' + (($('[data-mm-promo]', slide) || {}).getAttribute ? $('[data-mm-promo]', slide).getAttribute('data-mm-promo') : 'x') : '';
	var slideDismissed = false;
	try { slideDismissed = !!(slide && window.sessionStorage.getItem(slideKey)); } catch (e) { /* ignore */ }

	function progress() {
		if (!article) { return 0; }
		var r = article.getBoundingClientRect();
		var total = r.height - window.innerHeight * 0.4;
		var p = (-r.top + window.innerHeight * 0.25) / Math.max(1, total);
		return Math.max(0, Math.min(1, p));
	}

	function onScroll() {
		var p = progress();
		if (bar) { bar.style.setProperty('--p', p.toFixed(4)); }
		if (slide && !slideDismissed) {
			var show = p >= slideAt && p < 0.97;
			slide.classList.toggle('is-visible', show);
			doc.body.classList.toggle('mm-to-top-shift', show);
			if (show) { doc.documentElement.style.setProperty('--mm-slidein-h', slide.offsetHeight + 'px'); }
		}
	}
	if (MM.on) { MM.on('scroll', onScroll); } else { window.addEventListener('scroll', onScroll, { passive: true }); }
	onScroll();

	if (slide) {
		var close = $('[data-mm-slidein-close]', slide);
		if (close) {
			close.addEventListener('click', function () {
				slideDismissed = true;
				slide.classList.remove('is-visible');
				doc.body.classList.remove('mm-to-top-shift');
				try { window.sessionStorage.setItem(slideKey, '1'); } catch (e) { /* ignore */ }
			});
		}
	}

	/* Table of contents scrollspy. */
	var links = $$('[data-mm-toc] [data-mm-toc-link]');
	if (links.length && 'IntersectionObserver' in window) {
		var map = {};
		var headings = links.map(function (a) {
			var id = decodeURIComponent(a.getAttribute('href').slice(1));
			var h = doc.getElementById(id);
			if (h) { map[id] = a; }
			return h;
		}).filter(Boolean);
		var visible = {};
		var setActive = function (id) {
			links.forEach(function (l) { l.classList.remove('is-active'); });
			if (map[id]) { map[id].classList.add('is-active'); }
		};
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) { visible[e.target.id] = e.isIntersecting; });
			var current = null;
			for (var i = 0; i < headings.length; i++) {
				if (visible[headings[i].id]) { current = headings[i].id; break; }
			}
			if (!current) {
				// Above the first visible heading: pick the last one scrolled past.
				for (var j = headings.length - 1; j >= 0; j--) {
					if (headings[j].getBoundingClientRect().top < 140) { current = headings[j].id; break; }
				}
			}
			if (current) { setActive(current); }
		}, { rootMargin: '-90px 0px -60% 0px' });
		headings.forEach(function (h) { io.observe(h); });
	}

	/* Close the inline TOC after picking a heading. */
	$$('.mm-toc--inline a').forEach(function (a) {
		a.addEventListener('click', function () { var d = a.closest('details'); if (d) { d.open = false; } });
	});

	/* Copy link. */
	doc.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-mm-copy]');
		if (!btn) { return; }
		e.preventDefault();
		var url = btn.getAttribute('data-mm-copy');
		var done = function () { if (MM.toast) { MM.toast((cfg.i18n && cfg.i18n.copied) || 'Link copied'); } };
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(url).then(done).catch(function () {});
		} else {
			var t = doc.createElement('textarea');
			t.value = url;
			doc.body.appendChild(t);
			t.select();
			try { doc.execCommand('copy'); done(); } catch (err) { /* ignore */ }
			t.remove();
		}
	});
})();
