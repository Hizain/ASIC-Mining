/* Mine Mirth Product Page: gallery carousel, zoom, full-screen view, and the description extras. */
(function () {
	'use strict';

	var finePointer = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;

	function initGallery(g) {
		var viewport = g.querySelector('.mmpp-viewport');
		var slides = Array.prototype.slice.call(g.querySelectorAll('.mmpp-slide'));
		var thumbs = Array.prototype.slice.call(g.querySelectorAll('[data-mmpp-thumb]'));
		var dots = Array.prototype.slice.call(g.querySelectorAll('.mmpp-dots span'));
		var track = g.querySelector('[data-mmpp-track]');
		var counter = g.querySelector('[data-mmpp-count]');
		var prev = g.querySelector('[data-mmpp-prev]');
		var next = g.querySelector('[data-mmpp-next]');
		var loop = g.getAttribute('data-loop') === '1';
		var n = slides.length;
		var index = 0;
		if (!viewport || !n) { return; }

		function clamp(i) {
			if (loop) { return (i % n + n) % n; }
			return Math.max(0, Math.min(n - 1, i));
		}

		function mark(i) {
			index = i;
			slides.forEach(function (s, k) {
				var on = k === i;
				s.classList.toggle('is-active', on);
				if (on) { s.removeAttribute('aria-hidden'); } else { s.setAttribute('aria-hidden', 'true'); }
				var a = s.querySelector('a');
				if (a) { a.tabIndex = on ? 0 : -1; }
			});
			thumbs.forEach(function (t, k) {
				t.classList.toggle('is-active', k === i);
				if (k === i) { t.setAttribute('aria-current', 'true'); } else { t.removeAttribute('aria-current'); }
			});
			dots.forEach(function (d, k) { d.classList.toggle('is-active', k === i); });
			if (counter) { counter.textContent = (i + 1) + ' / ' + n; }
			if (!loop) {
				if (prev) { prev.disabled = i === 0; }
				if (next) { next.disabled = i === n - 1; }
			}
			// Keep the active thumbnail in view inside the strip (never scrolls the page).
			var t = thumbs[i];
			if (track && t) {
				var vertical = track.scrollHeight > track.clientHeight + 2 && track.scrollWidth <= track.clientWidth + 2;
				if (vertical) {
					track.scrollTo({ top: t.offsetTop - (track.clientHeight - t.offsetHeight) / 2, behavior: 'smooth' });
				} else {
					track.scrollTo({ left: t.offsetLeft - (track.clientWidth - t.offsetWidth) / 2, behavior: 'smooth' });
				}
			}
		}

		function go(i, instant) {
			i = clamp(i);
			viewport.scrollTo({ left: slides[i].offsetLeft, behavior: instant ? 'auto' : 'smooth' });
			mark(i);
		}

		// Swiping (native scroll-snap) updates the active image.
		var timer;
		viewport.addEventListener('scroll', function () {
			clearTimeout(timer);
			timer = setTimeout(function () {
				var i = Math.round(viewport.scrollLeft / viewport.clientWidth);
				if (i !== index && i >= 0 && i < n) { mark(i); }
			}, 60);
		}, { passive: true });

		if (prev) { prev.addEventListener('click', function () { stop(); go(index - 1); }); }
		if (next) { next.addEventListener('click', function () { stop(); go(index + 1); }); }
		thumbs.forEach(function (t, k) {
			t.addEventListener('click', function () { stop(); go(k); });
		});
		g.addEventListener('keydown', function (e) {
			if (e.target.closest('dialog')) { return; }
			if (e.key === 'ArrowLeft') { e.preventDefault(); stop(); go(index - 1); }
			if (e.key === 'ArrowRight') { e.preventDefault(); stop(); go(index + 1); }
		});
		// Keep the right image in place when the window is resized.
		window.addEventListener('resize', function () { viewport.scrollTo({ left: slides[index].offsetLeft }); });

		// Thumbnail strip arrows, shown only when the strip overflows.
		var tprev = g.querySelector('[data-mmpp-tprev]');
		var tnext = g.querySelector('[data-mmpp-tnext]');
		function syncStrip() {
			if (!track || !tprev || !tnext) { return; }
			var vertical = getComputedStyle(track).flexDirection === 'column';
			var pos = vertical ? track.scrollTop : track.scrollLeft;
			var max = vertical ? track.scrollHeight - track.clientHeight : track.scrollWidth - track.clientWidth;
			tprev.hidden = pos <= 2;
			tnext.hidden = pos >= max - 2;
		}
		if (track && tprev && tnext) {
			track.addEventListener('scroll', syncStrip, { passive: true });
			window.addEventListener('resize', syncStrip);
			function page(dir) {
				var vertical = getComputedStyle(track).flexDirection === 'column';
				if (vertical) { track.scrollBy({ top: dir * track.clientHeight * 0.8, behavior: 'smooth' }); }
				else { track.scrollBy({ left: dir * track.clientWidth * 0.8, behavior: 'smooth' }); }
			}
			tprev.addEventListener('click', function () { page(-1); });
			tnext.addEventListener('click', function () { page(1); });
			syncStrip();
		}

		// Hover zoom: the full-size image follows the pointer (desktop only).
		if (g.getAttribute('data-zoom') === '1' && finePointer) {
			var lens = document.createElement('div');
			lens.className = 'mmpp-zoom';
			lens.setAttribute('aria-hidden', 'true');
			g.querySelector('.mmpp-stage').appendChild(lens);
			var loaded = {};
			viewport.addEventListener('mousemove', function (e) {
				var a = slides[index].querySelector('a');
				if (!a || !a.href) { return; }
				var src = a.getAttribute('href');
				if (!loaded[src]) {
					loaded[src] = 'loading';
					var probe = new Image();
					probe.onload = function () { loaded[src] = 'ok'; };
					probe.src = src;
				}
				if (loaded[src] !== 'ok') { return; }
				var r = viewport.getBoundingClientRect();
				lens.style.backgroundImage = 'url("' + src.replace(/"/g, '%22') + '")';
				lens.style.backgroundPosition = ((e.clientX - r.left) / r.width * 100) + '% ' + ((e.clientY - r.top) / r.height * 100) + '%';
				lens.classList.add('is-on');
			});
			viewport.addEventListener('mouseleave', function () { lens.classList.remove('is-on'); });
			viewport.addEventListener('scroll', function () { lens.classList.remove('is-on'); }, { passive: true });
		}

		// Full-screen view.
		var lb = g.querySelector('[data-mmpp-lb]');
		if (lb && typeof lb.showModal === 'function') {
			var lbTrack = lb.querySelector('[data-mmpp-lbtrack]');
			var lbSlides = Array.prototype.slice.call(lb.querySelectorAll('.mmpp-lb__slide'));
			var lbCount = lb.querySelector('[data-mmpp-lbcount]');
			var lbIndex = 0;
			function lbLoad(i) {
				[i, i + 1, i - 1].forEach(function (k) {
					var img = lbSlides[(k % n + n) % n] && lbSlides[(k % n + n) % n].querySelector('img');
					if (img && !img.src && img.getAttribute('data-src')) { img.src = img.getAttribute('data-src'); }
				});
			}
			function lbGo(i, instant) {
				i = (i % n + n) % n;
				lbIndex = i;
				lbLoad(i);
				lbTrack.scrollTo({ left: lbSlides[i].offsetLeft, behavior: instant ? 'auto' : 'smooth' });
				if (lbCount) { lbCount.textContent = (i + 1) + ' / ' + n; }
			}
			function open(i) {
				// A variation may have replaced the first image: show what is on the page.
				var a = slides[0].querySelector('a');
				var first = lbSlides[0].querySelector('img');
				if (a && first && a.getAttribute('href') && first.getAttribute('data-src') !== a.getAttribute('href')) {
					first.setAttribute('data-src', a.getAttribute('href'));
					first.removeAttribute('src');
				}
				document.documentElement.classList.add('mmpp-lock');
				lb.showModal();
				lbGo(i, true);
			}
			lb.addEventListener('close', function () {
				document.documentElement.classList.remove('mmpp-lock');
				go(lbIndex, true);
			});
			slides.forEach(function (s, k) {
				var a = s.querySelector('a');
				if (a) { a.addEventListener('click', function (e) { e.preventDefault(); stop(); open(k); }); }
			});
			var openBtn = g.querySelector('[data-mmpp-open]');
			if (openBtn) { openBtn.addEventListener('click', function () { stop(); open(index); }); }
			lb.querySelector('[data-mmpp-close]').addEventListener('click', function () { lb.close(); });
			var lp = lb.querySelector('[data-mmpp-lbprev]');
			var ln = lb.querySelector('[data-mmpp-lbnext]');
			if (lp) { lp.addEventListener('click', function () { lbGo(lbIndex - 1); }); }
			if (ln) { ln.addEventListener('click', function () { lbGo(lbIndex + 1); }); }
			lb.addEventListener('keydown', function (e) {
				if (e.key === 'ArrowLeft') { e.preventDefault(); lbGo(lbIndex - 1); }
				if (e.key === 'ArrowRight') { e.preventDefault(); lbGo(lbIndex + 1); }
			});
			var lt;
			lbTrack.addEventListener('scroll', function () {
				clearTimeout(lt);
				lt = setTimeout(function () {
					var i = Math.round(lbTrack.scrollLeft / lbTrack.clientWidth);
					if (i !== lbIndex) { lbIndex = i; lbLoad(i); if (lbCount) { lbCount.textContent = (i + 1) + ' / ' + n; } }
				}, 60);
			}, { passive: true });
			// Click on the dark area (not the photo) closes.
			lbTrack.addEventListener('click', function (e) { if (e.target.tagName !== 'IMG') { lb.close(); } });
		} else {
			// No full-screen view: clicking the photo does nothing instead of leaving the page.
			slides.forEach(function (s) {
				var a = s.querySelector('a');
				if (a && g.getAttribute('data-lightbox') !== '1') { a.addEventListener('click', function (e) { e.preventDefault(); }); }
			});
		}

		// Autoplay: stops for good once the visitor touches the gallery.
		var every = parseInt(g.getAttribute('data-autoplay'), 10) || 0;
		var auto = null;
		function stop() { if (auto) { clearInterval(auto); auto = null; } }
		if (every > 0 && n > 1 && !(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) {
			auto = setInterval(function () {
				if (document.hidden || g.matches(':hover') || g.contains(document.activeElement)) { return; }
				go(index + 1 >= n ? 0 : index + 1);
			}, every * 1000);
			viewport.addEventListener('pointerdown', stop);
		}

		// Product variations: WooCommerce swaps the first image; show it and its thumbnail.
		if (window.jQuery) {
			var thumb0 = thumbs[0] && thumbs[0].querySelector('img');
			var original = thumb0 ? { src: thumb0.getAttribute('src'), srcset: thumb0.getAttribute('srcset') } : null;
			var form = g.closest('.product') ? g.closest('.product').querySelector('form.variations_form') : null;
			if (form) {
				window.jQuery(form).on('found_variation', function (e, v) {
					if (v && v.image && v.image.src) {
						if (thumb0 && v.image.gallery_thumbnail_src) { thumb0.src = v.image.gallery_thumbnail_src; thumb0.removeAttribute('srcset'); }
						stop();
						go(0);
					}
				}).on('reset_image', function () {
					if (thumb0 && original) {
						thumb0.src = original.src;
						if (original.srcset) { thumb0.setAttribute('srcset', original.srcset); }
					}
				});
			}
		}

		mark(0);
	}

	document.querySelectorAll('[data-mmpp-gallery]').forEach(initGallery);

	// Long descriptions: "Show the full description".
	var l10n = window.mmppL10n || {};
	document.querySelectorAll('[data-mmpp-collapse]').forEach(function (body) {
		var limit = parseInt(getComputedStyle(body).getPropertyValue('--mmpp-collapse'), 10) || 640;
		// Only when there is clearly more to show; the panel may be hidden in a tab, so measure lazily.
		function setup() {
			if (body.scrollHeight < limit + 160) { return false; }
			body.classList.add('is-collapsed');
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'mmpp-more';
			btn.setAttribute('aria-expanded', 'false');
			btn.textContent = l10n.more || 'Show the full description';
			btn.addEventListener('click', function () {
				var open = body.classList.toggle('is-collapsed') === false;
				btn.setAttribute('aria-expanded', open ? 'true' : 'false');
				btn.textContent = open ? (l10n.less || 'Show less') : (l10n.more || 'Show the full description');
				if (!open) { body.parentNode.scrollIntoView({ block: 'start', behavior: 'smooth' }); }
			});
			body.parentNode.appendChild(btn);
			return true;
		}
		if (body.offsetParent !== null) { setup(); return; }
		var io = new ResizeObserver(function () { if (body.offsetParent !== null) { setup(); io.disconnect(); } });
		io.observe(body);
	});

	// "On this page": open a collapsed description before jumping to a heading.
	document.querySelectorAll('.mmpp-toc a').forEach(function (a) {
		a.addEventListener('click', function () {
			var body = a.closest('.mmpp-desc').querySelector('.mmpp-desc__body.is-collapsed');
			var btn = a.closest('.mmpp-desc').querySelector('.mmpp-more');
			if (body && btn) { btn.click(); }
		});
	});
})();
