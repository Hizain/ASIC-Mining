/* Mine Mirth Ticker: seamless right-to-left scroll and live figures. */
(function () {
	'use strict';

	function setup(bar) {
		var track = bar.querySelector('.mmtk__track');
		var group = bar.querySelector('.mmtk__group');
		if (!track || !group) { return; }
		// One clean copy of the items; the visible row is built from it and live figures update it too.
		var tpl = document.createElement('ul');
		tpl.innerHTML = group.innerHTML;

		// Fill: the group must be at least as wide as the bar, then a copy follows it for a seamless loop.
		function build() {
			bar.classList.remove('is-running');
			var original = tpl.innerHTML;
			group.innerHTML = original;
			var view = bar.querySelector('.mmtk__viewport').clientWidth || window.innerWidth;
			var w = group.scrollWidth;
			var copies = w > 0 ? Math.min(20, Math.ceil(view / w)) : 1;
			if (copies > 1) { group.innerHTML = new Array(copies + 1).join(original); }
			Array.prototype.slice.call(track.querySelectorAll('.mmtk__group[aria-hidden]')).forEach(function (n) { n.remove(); });
			var clone = group.cloneNode(true);
			clone.setAttribute('aria-hidden', 'true');
			clone.querySelectorAll('a, button').forEach(function (a) { a.setAttribute('tabindex', '-1'); });
			track.appendChild(clone);
			var shift = group.getBoundingClientRect().width;
			var speed = Math.max(10, parseInt(bar.getAttribute('data-speed'), 10) || 55); // pixels per second
			track.style.setProperty('--mmtk-shift', shift + 'px');
			track.style.setProperty('--mmtk-dur', (shift / speed).toFixed(2) + 's');
			bar.classList.add('is-running');
		}

		// Live figures: replace the text in place (both copies), without restarting the scroll.
		function apply(data) {
			Object.keys(data || {}).forEach(function (k) {
				var v = data[k];
				var scope = [bar, tpl];
				scope.forEach(function (root) { root.querySelectorAll('[data-mmtk-v="' + k + '"]').forEach(function (n) { if (v.v && n.textContent !== v.v) { n.textContent = v.v; } });
				root.querySelectorAll('[data-mmtk-c="' + k + '"]').forEach(function (n) {
					n.textContent = v.c || '';
					n.classList.toggle('is-up', v.d === 'up');
					n.classList.toggle('is-down', v.d === 'down');
				}); });
			});
		}

		function refresh() {
			var url = bar.getAttribute('data-endpoint');
			if (!url || document.hidden || !window.fetch) { return; }
			fetch(url, { credentials: 'omit' }).then(function (r) { return r.ok ? r.json() : null; }).then(function (d) { if (d) { apply(d); } }).catch(function () {});
		}

		var resizeTimer;
		window.addEventListener('resize', function () { clearTimeout(resizeTimer); resizeTimer = setTimeout(build, 250); });
		if (document.fonts && document.fonts.ready) { document.fonts.ready.then(build); } else { build(); }
		build();

		refresh();
		var every = Math.max(30, parseInt(bar.getAttribute('data-refresh'), 10) || 60) * 1000;
		setInterval(refresh, every);
		document.addEventListener('visibilitychange', function () { if (!document.hidden) { refresh(); } });

		var close = bar.querySelector('[data-mmtk-close]');
		if (close) {
			close.addEventListener('click', function () {
				bar.classList.add('is-closed');
				try { sessionStorage.setItem('mmtk-closed', '1'); } catch (e) {}
			});
			try { if (sessionStorage.getItem('mmtk-closed')) { bar.classList.add('is-closed'); } } catch (e) {}
		}
	}

	document.querySelectorAll('[data-mmtk]').forEach(setup);
})();
