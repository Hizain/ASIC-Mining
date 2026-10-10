/* Mine Mirth Tools Showcase: filter tabs and reveal-on-scroll. */
(function () {
	'use strict';
	function init(section) {
		var cards = section.querySelectorAll('.mmt-card');
		var tabs = section.querySelectorAll('.mmt__tab');
		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				var filter = tab.getAttribute('data-filter');
				tabs.forEach(function (t) { t.classList.toggle('is-active', t === tab); t.setAttribute('aria-selected', t === tab ? 'true' : 'false'); });
				cards.forEach(function (card) {
					var show = filter === 'all' || card.getAttribute('data-status') === filter;
					card.hidden = !show;
					if (show) { card.classList.add('is-in'); }
				});
			});
		});
		if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			cards.forEach(function (c) { c.classList.add('is-in'); });
			return;
		}
		section.classList.add('mmt--animate');
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
			});
		}, { threshold: 0.08 });
		cards.forEach(function (c, i) { c.style.transitionDelay = (i % 3) * 70 + 'ms'; io.observe(c); });
	}
	function boot() { document.querySelectorAll('.mmt').forEach(init); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
