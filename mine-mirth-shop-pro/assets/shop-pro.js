/* Mine Mirth Shop Pro: filter panel behaviour. */
(function () {
	'use strict';
	document.querySelectorAll('[data-mmsp-filters]').forEach(function (form) {
		var submit = function () {
			// Leave empty fields out so links stay short.
			Array.prototype.forEach.call(form.elements, function (el) { if (el.name && el.value === '') { el.disabled = true; } });
			form.classList.add('is-loading');
			form.submit();
		};

		// Checkboxes apply straight away.
		form.addEventListener('change', function (e) {
			if (e.target.type === 'checkbox') { submit(); }
		});

		// Category tree: open and close subcategories.
		form.addEventListener('click', function (e) {
			var t = e.target.closest('.mmsp-tree__toggle');
			if (!t) { return; }
			var item = t.closest('.mmsp-tree__item');
			var open = !item.classList.contains('is-open');
			item.classList.toggle('is-open', open);
			t.setAttribute('aria-expanded', open ? 'true' : 'false');
		});

		// Search inside long attribute lists.
		form.querySelectorAll('[data-mmsp-find]').forEach(function (input) {
			var items = input.parentNode.querySelectorAll('[data-name]');
			input.addEventListener('input', function () {
				var q = input.value.trim().toLowerCase();
				items.forEach(function (it) { it.hidden = q !== '' && it.getAttribute('data-name').indexOf(q) === -1; });
			});
			input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); } });
		});

		// Price: two sliders kept in step with the number boxes.
		form.querySelectorAll('[data-mmsp-price]').forEach(function (box) {
			var lo = box.querySelector('[data-mmsp-range="min"]');
			var hi = box.querySelector('[data-mmsp-range="max"]');
			var nmin = box.querySelector('input[name="mmsp_min"]');
			var nmax = box.querySelector('input[name="mmsp_max"]');
			var low = parseFloat(box.getAttribute('data-lo')), high = parseFloat(box.getAttribute('data-hi'));
			var paint = function () {
				var a = (parseFloat(lo.value) - low) / (high - low) * 100, b = (parseFloat(hi.value) - low) / (high - low) * 100;
				box.style.setProperty('--a', a + '%');
				box.style.setProperty('--b', b + '%');
			};
			var fromSliders = function () {
				if (parseFloat(lo.value) > parseFloat(hi.value)) { var t = lo.value; lo.value = hi.value; hi.value = t; }
				nmin.value = parseFloat(lo.value) > low ? lo.value : '';
				nmax.value = parseFloat(hi.value) < high ? hi.value : '';
				paint();
			};
			lo.addEventListener('input', fromSliders);
			hi.addEventListener('input', fromSliders);
			lo.addEventListener('change', submit);
			hi.addEventListener('change', submit);
			[nmin, nmax].forEach(function (n) {
				n.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); submit(); } });
			});
			paint();
		});

		form.addEventListener('submit', function (e) { e.preventDefault(); submit(); });
	});
})();
