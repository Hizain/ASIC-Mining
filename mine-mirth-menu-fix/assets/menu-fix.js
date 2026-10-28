/*
 * Mine Mirth Menu Fix: hover intent for the desktop header dropdowns.
 *
 * A menu opens as soon as the pointer reaches its name, stays open while the pointer travels down
 * into its panel (even at a shallow angle across a neighbouring name), and a neighbouring menu only
 * takes over when the pointer rests on it. Mouse and trackpad only; touch and keyboard are unchanged.
 */
(function () {
	'use strict';

	function init() {
		var nav = document.querySelector('.mm-nav');
		if (!nav || nav.dataset.mmmf || !window.matchMedia || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) { return; }
		nav.dataset.mmmf = '1';
		nav.classList.add('mmmf');

		var tops = Array.prototype.slice.call(nav.querySelectorAll('.mm-menu > li'));
		var items = tops.filter(function (li) { return li.querySelector('.mm-submenu-wrap'); });
		var current = null;
		var closeTimer = 0;
		var switchTimer = 0;
		var trail = [];

		(nav.closest('.mm-masthead') || nav).addEventListener('mousemove', function (e) {
			trail.push({ x: e.clientX, y: e.clientY, t: Date.now() });
			if (trail.length > 12) { trail.shift(); }
		});

		// Is the pointer still travelling down towards the open panel? Then the visitor is not choosing a new menu.
		function headingDown() {
			var now = Date.now();
			var last = trail[trail.length - 1];
			if (!last || now - last.t > 220) { return false; }
			var recent = trail.filter(function (m) { return now - m.t < 340; });
			var first = recent.length >= 2 ? recent[0] : trail[trail.length - 2];
			if (!first || last.t - first.t > 800) { return false; }
			var dy = last.y - first.y;
			var dx = Math.abs(last.x - first.x);
			return dy > 2 && dy >= dx * 0.08;
		}
		function open(li) {
			clearTimeout(closeTimer);
			clearTimeout(switchTimer);
			if (current && current !== li) { current.classList.remove('is-open'); }
			current = li;
			li.classList.add('is-open');
		}
		function closeAll() {
			clearTimeout(switchTimer);
			if (current) { current.classList.remove('is-open'); }
			current = null;
		}
		// Close after a short pause, but keep waiting while the pointer is still on its way down to the panel.
		function closeLater(waited) {
			clearTimeout(closeTimer);
			closeTimer = setTimeout(function () {
				if (current && current.matches(':hover')) { return; }
				if (headingDown() && (waited || 0) < 1400) { closeLater((waited || 0) + 280); return; }
				closeAll();
			}, 280);
		}
		function trySwitch(li) {
			clearTimeout(switchTimer);
			switchTimer = setTimeout(function () {
				if (!li.matches(':hover')) { return; }
				if (headingDown()) { trySwitch(li); return; }
				open(li);
			}, 140);
		}

		items.forEach(function (li) {
			li.addEventListener('mouseenter', function () {
				clearTimeout(closeTimer);
				if (!current || current === li) { open(li); return; }
				trySwitch(li);
			});
			li.addEventListener('mouseleave', function () {
				clearTimeout(switchTimer);
				if (current === li) { closeLater(); }
			});
		});
		// Items without a dropdown (for example Hosting) close an open menu only when the pointer rests on them.
		tops.forEach(function (li) {
			if (items.indexOf(li) !== -1) { return; }
			li.addEventListener('mouseenter', function () {
				if (!current) { return; }
				clearTimeout(closeTimer);
				(function wait() {
					clearTimeout(switchTimer);
					switchTimer = setTimeout(function () {
						if (!li.matches(':hover')) { return; }
						if (headingDown()) { wait(); return; }
						closeAll();
					}, 140);
				}());
			});
			li.addEventListener('mouseleave', function () {
				clearTimeout(switchTimer);
				if (current && !current.matches(':hover')) { closeLater(); }
			});
		});
		document.addEventListener('click', function (e) { if (current && !nav.contains(e.target)) { closeAll(); } });
		document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeAll(); } });
		window.addEventListener('pagehide', closeAll);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
}());
