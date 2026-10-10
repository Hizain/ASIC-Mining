/* Mine Mirth Buy Now & Price Alerts */
(function () {
	'use strict';
	var cfg = window.mmbnCfg || {};

	/* Buy it now: on variable products, ask for options first (WooCommerce shows its own message). */
	document.querySelectorAll('[data-mmbn-buy]').forEach(function (btn) {
		var form = btn.closest('form.cart');
		if (!form) { return; }
		btn.addEventListener('click', function (e) {
			var variation = form.querySelector('input[name="variation_id"]');
			if (variation && (!variation.value || variation.value === '0')) {
				e.preventDefault();
				var atc = form.querySelector('.single_add_to_cart_button');
				if (atc) { atc.click(); }
				return;
			}
			btn.classList.add('is-loading');
			setTimeout(function () { btn.classList.remove('is-loading'); }, 6000);
		});
		if (form.classList.contains('variations_form') && window.jQuery) {
			window.jQuery(form)
				.on('show_variation', function (ev, v) { btn.classList.toggle('is-disabled', !(v && v.is_purchasable && v.is_in_stock)); })
				.on('hide_variation reset_data', function () { btn.classList.add('is-disabled'); });
			btn.classList.add('is-disabled');
		}
	});

	/* Price alerts */
	document.querySelectorAll('[data-mmbn-alert]').forEach(function (box) {
		var toggle = box.querySelector('.mmbn-alert__toggle');
		var form = box.querySelector('.mmbn-alert__form');
		var msg = box.querySelector('.mmbn-alert__msg');
		var email = form.querySelector('input[type="email"]');
		/* Attention animation: enter on view, then a gentle nudge every few seconds while on screen. */
		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var touched = false, visible = false, timer = null;
		function settle() { box.classList.remove('is-in'); box.classList.add('is-shown'); }
		function nudge() {
			if (touched || !visible || document.hidden || box.classList.contains('is-open')) { return; }
			box.classList.remove('is-nudge');
			void box.offsetWidth; // restart the animation
			box.classList.add('is-nudge');
		}
		function stop() { touched = true; box.classList.remove('is-nudge'); if (timer) { clearInterval(timer); timer = null; } }
		if (reduce || !('IntersectionObserver' in window)) {
			box.classList.add('is-shown');
		} else {
			var io = new IntersectionObserver(function (entries) {
				visible = entries[0].isIntersecting;
				if (visible && !box.classList.contains('is-in') && !box.classList.contains('is-shown')) {
					box.classList.add('is-in');
					setTimeout(settle, 3600);
					timer = setInterval(nudge, 7000);
				}
			}, { threshold: 0.6 });
			io.observe(box);
		}
		box.addEventListener('animationend', function (e) { if (e.target === box && e.animationName === 'mmbn-glow') { box.classList.remove('is-nudge'); } });
		['pointerenter', 'focusin'].forEach(function (ev) { box.addEventListener(ev, stop, { once: true }); });

		toggle.addEventListener('click', function () {
			var open = form.hidden;
			form.hidden = !open;
			box.classList.toggle('is-open', open);
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open) { setTimeout(function () { email.focus(); }, 30); }
		});
		form.addEventListener('submit', function (e) {
			if (!cfg.endpoint || !window.fetch) { return; } // Normal form post instead.
			e.preventDefault();
			if (!email.value || !email.checkValidity()) {
				msg.textContent = email.validationMessage || cfg.error;
				msg.className = 'mmbn-alert__msg is-error';
				email.focus();
				return;
			}
			var btn = form.querySelector('button[type="submit"]');
			var label = btn.textContent;
			btn.disabled = true;
			btn.textContent = cfg.sending || '…';
			fetch(cfg.endpoint, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
				.then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
				.then(function (res) {
					msg.textContent = (res.json && res.json.message) || cfg.error;
					msg.className = 'mmbn-alert__msg ' + (res.ok ? 'is-ok' : 'is-error');
					if (res.ok) {
						box.classList.add('is-watching');
						form.querySelector('.mmbn-alert__row').hidden = true;
						var fine = form.querySelector('.mmbn-alert__fine');
						if (fine) { fine.hidden = true; }
					}
				})
				.catch(function () { msg.textContent = cfg.error; msg.className = 'mmbn-alert__msg is-error'; })
				.finally(function () { btn.disabled = false; btn.textContent = label; });
		});
	});
})();
