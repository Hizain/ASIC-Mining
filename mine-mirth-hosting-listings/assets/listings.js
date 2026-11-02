/* Mine Mirth Hosting Listings: search, filters, sorting, "Show more" and the listing gallery. */
(function () {
	'use strict';

	document.querySelectorAll('[data-mmhl]').forEach(function (root) {
		var grid = root.querySelector('[data-mmhl-grid]');
		if (!grid) { return; }
		var items = Array.prototype.slice.call(grid.querySelectorAll('[data-mmhl-item]'));
		var perPage = parseInt(root.getAttribute('data-per-page'), 10) || 0;
		var shown = perPage;
		var q = root.querySelector('[data-mmhl-q]');
		var region = root.querySelector('[data-mmhl-region]');
		var avail = root.querySelector('[data-mmhl-avail]');
		var sort = root.querySelector('[data-mmhl-sort]');
		var count = root.querySelector('[data-mmhl-count]');
		var none = root.querySelector('[data-mmhl-none]');
		var more = root.querySelector('[data-mmhl-more]');
		var reset = root.querySelector('[data-mmhl-reset]');

		function num(el, key) { return parseFloat(el.getAttribute('data-' + key)) || 0; }

		function apply() {
			var term = q ? q.value.trim().toLowerCase() : '';
			var r = region ? region.value : '';
			var a = avail ? avail.value : '';
			var mode = sort ? sort.value : 'rank';
			var list = items.slice();
			list.sort(function (x, y) {
				if (mode === 'price-asc' || mode === 'price-desc') {
					var px = num(x, 'price'), py = num(y, 'price');
					// Listings without a price go last either way.
					if (!px !== !py) { return px ? -1 : 1; }
					return mode === 'price-asc' ? px - py : py - px;
				}
				if (mode === 'date') { return num(y, 'date') - num(x, 'date'); }
				return num(x, 'rank') - num(y, 'rank');
			});
			var visible = 0;
			list.forEach(function (el) {
				grid.appendChild(el);
				var ok = (!term || (el.getAttribute('data-search') || '').indexOf(term) !== -1) &&
					(!r || el.getAttribute('data-region') === r) &&
					(!a || el.getAttribute('data-avail') === a);
				el.classList.toggle('is-hidden', !ok);
				el.classList.remove('is-paged');
				if (ok) {
					visible++;
					if (shown && visible > shown) { el.classList.add('is-paged'); }
				}
			});
			if (count) {
				count.textContent = (count.getAttribute(visible === 1 ? 'data-one' : 'data-many') || '%d').replace('%d', visible);
			}
			if (none) { none.hidden = visible > 0; }
			if (more) { more.parentNode.hidden = !(shown && visible > shown); }
		}

		[q, region, avail, sort].forEach(function (el) {
			if (el) { el.addEventListener(el === q ? 'input' : 'change', function () { shown = perPage; apply(); }); }
		});
		if (more) {
			more.addEventListener('click', function () { shown += perPage; apply(); });
		}
		if (reset) {
			reset.addEventListener('click', function () {
				if (q) { q.value = ''; }
				if (region) { region.value = ''; }
				if (avail) { avail.value = ''; }
				shown = perPage;
				apply();
			});
		}
		apply();
	});

	// Listing page gallery: thumbnails swap the main image.
	document.querySelectorAll('[data-mmhl-gallery]').forEach(function (g) {
		var main = g.querySelector('[data-mmhl-main]');
		g.querySelectorAll('.mmhl-gallery__thumbs button').forEach(function (b) {
			b.addEventListener('click', function () {
				if (!main) { return; }
				main.src = b.getAttribute('data-full');
				var set = b.getAttribute('data-srcset');
				if (set) { main.srcset = set; } else { main.removeAttribute('srcset'); }
				g.querySelectorAll('.mmhl-gallery__thumbs button').forEach(function (x) { x.classList.toggle('is-active', x === b); });
			});
		});
	});
})();
