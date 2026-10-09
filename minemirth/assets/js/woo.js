/*!
 * Mine Mirth — WooCommerce enhancements:
 * slide-out mini cart, AJAX add to cart (promos & quick view), quick view
 * modal, quantity steppers, shop filter drawer, sticky add-to-cart,
 * checkout progress, account tabs and order-confirmation confetti.
 */
(function ($) {
	'use strict';

	var MM = window.MineMirth || {};
	var cfg = MM.config || {};
	var i18n = cfg.i18n || {};
	var doc = document;
	var $body = $(doc.body);

	function $$(s, c) { return Array.prototype.slice.call((c || doc).querySelectorAll(s)); }
	function t(k, f) { return i18n[k] || f; }
	function endpoint(name) { return (cfg.wcAjax || '').replace('%%endpoint%%', name); }

	var ICON = {
		minus: '<svg class="mm-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/></svg>',
		plus: '<svg class="mm-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>',
		x: '<svg class="mm-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
		bag: '<svg class="mm-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1.2 11.1a2 2 0 0 1-2 1.9H8.2a2 2 0 0 1-2-1.9Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>',
		arrow: '<svg class="mm-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>'
	};

	/* ------------------------------------------------------------------
	 * Mini cart drawer
	 * ---------------------------------------------------------------- */
	var drawer = doc.querySelector('[data-mm-cart-drawer]');
	function openCart() { if (drawer && MM.open) { MM.open(drawer); } }
	function closeCart() { if (drawer && MM.close) { MM.close(drawer); } }

	$(doc).on('click', '[data-mm-cart-open]', function (e) {
		if (!drawer) { return; }
		e.preventDefault();
		openCart();
	});
	$(doc).on('click', '[data-mm-cart-close]', function (e) { e.preventDefault(); closeCart(); });

	function bumpCount() {
		$$('.mm-cart-count').forEach(function (c) {
			c.classList.remove('is-bump');
			void c.offsetWidth;
			c.classList.add('is-bump');
		});
	}

	function applyFragments(fragments) {
		if (!fragments) { return; }
		Object.keys(fragments).forEach(function (selector) {
			$(selector).replaceWith(fragments[selector]);
		});
		$body.trigger('wc_fragments_refreshed');
	}

	$body.on('added_to_cart', function (e, fragments) {
		bumpCount();
		if (cfg.cart && cfg.cart.drawer && cfg.cart.openOnAdd && drawer) {
			setTimeout(openCart, 150);
		} else if (MM.toast) {
			MM.toast(t('added', 'Added to cart'), { link: cfg.cartUrl, linkText: t('viewCart', 'View cart') });
		}
	});

	/* AJAX add to cart for [data-mm-add-to-cart] buttons (promos, quick view). */
	function addToCart(productId, qty, button) {
		if (!cfg.wcAjax) { return Promise.reject(); }
		var data = new FormData();
		data.append('product_id', productId);
		data.append('quantity', qty || 1);
		if (button) { button.classList.add('is-loading'); }
		return fetch(endpoint('add_to_cart'), { method: 'POST', body: data, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || res.error) {
					if (res && res.product_url) { window.location = res.product_url; }
					throw new Error('add_to_cart');
				}
				applyFragments(res.fragments);
				$body.trigger('added_to_cart', [res.fragments, res.cart_hash, $(button)]);
				return res;
			})
			.catch(function (err) {
				if (MM.toast) { MM.toast(t('error', 'Something went wrong.'), { type: 'error' }); }
				throw err;
			})
			.finally(function () { if (button) { button.classList.remove('is-loading'); } });
	}

	$(doc).on('click', '[data-mm-add-to-cart]', function (e) {
		e.preventDefault();
		var btn = this;
		var qtyInput = btn.closest('[data-mm-qv-form]') ? btn.closest('[data-mm-qv-form]').querySelector('input.qty') : null;
		addToCart(btn.getAttribute('data-mm-add-to-cart'), qtyInput ? qtyInput.value : 1, btn).then(function () {
			var modal = btn.closest('.mm-modal');
			if (modal && MM.close) { MM.close(modal); }
		}).catch(function () {});
	});

	/* ------------------------------------------------------------------
	 * Quick view
	 * ---------------------------------------------------------------- */
	var modal = null;
	function ensureModal() {
		if (modal) { return modal; }
		modal = doc.createElement('div');
		modal.className = 'mm-modal';
		modal.setAttribute('inert', '');
		modal.innerHTML =
			'<div class="mm-modal__backdrop" data-mm-modal-close></div>' +
			'<div class="mm-modal__panel" role="dialog" aria-modal="true" aria-label="Quick view">' +
			'<button type="button" class="mm-icon-btn mm-modal__close" data-mm-modal-close aria-label="Close">' + ICON.x + '</button>' +
			'<div class="mm-modal__body"></div></div>';
		doc.body.appendChild(modal);
		$(modal).on('click', '[data-mm-modal-close]', function (e) { e.preventDefault(); MM.close(modal); });
		return modal;
	}

	function qvSkeleton() {
		return '<div class="mm-qv__loading"><div class="mm-skeleton"></div><div style="display:grid;gap:14px;align-content:start">' +
			'<div class="mm-skeleton" style="height:32px;width:80%"></div><div class="mm-skeleton" style="height:22px;width:40%"></div>' +
			'<div class="mm-skeleton" style="height:90px"></div><div class="mm-skeleton" style="height:48px;width:60%"></div></div></div>';
	}

	function renderQuickView(p) {
		var el = MM.el;
		var wrap = el('div', { class: 'mm-qv' });
		var media = el('div', { class: 'mm-qv__media' });
		if (p.images && p.images[0]) { media.appendChild(el('img', { src: p.images[0].src, alt: MM.decode(p.images[0].alt || p.name) })); }
		wrap.appendChild(media);

		var body = el('div', { class: 'mm-qv__body' });
		if (p.on_sale) { body.appendChild(el('span', { class: 'mm-badge mm-badge--sale', text: 'Sale', style: 'justify-self:start' })); }
		body.appendChild(el('h2', { text: MM.decode(p.name) }));
		var price = el('div', { class: 'mm-qv__price' });
		price.innerHTML = p.price_html || MM.formatStorePrice(p.prices);
		body.appendChild(price);
		var stock = el('span', { class: 'mm-stock ' + (p.is_in_stock ? 'mm-stock--instock' : 'mm-stock--outofstock'), text: p.is_in_stock ? t('inStock', 'In stock') : t('outOfStock', 'Out of stock') });
		body.appendChild(stock);
		if (p.short_description) {
			var desc = el('div', { class: 'mm-qv__desc' });
			desc.innerHTML = p.short_description;
			body.appendChild(desc);
		}
		var actions = el('div', { class: 'mm-qv__actions', 'data-mm-qv-form': '' });
		var simple = p.type === 'simple' && p.is_purchasable && p.is_in_stock;
		if (simple) {
			var qty = el('div', { class: 'mm-qv__qty' });
			qty.innerHTML = '<button type="button" data-step="-1" aria-label="-">−</button><input class="qty" type="number" min="1" value="1" aria-label="Quantity"><button type="button" data-step="1" aria-label="+">+</button>';
			$(qty).on('click', 'button', function () {
				var input = qty.querySelector('input');
				input.value = Math.max(1, (parseInt(input.value, 10) || 1) + parseInt(this.getAttribute('data-step'), 10));
			});
			actions.appendChild(qty);
			var add = el('button', { type: 'button', class: 'mm-btn mm-btn--primary', 'data-mm-add-to-cart': p.id });
			add.innerHTML = ICON.bag + '<span>' + t('addToCart', 'Add to cart') + '</span>';
			actions.appendChild(add);
		} else {
			var opt = el('a', { class: 'mm-btn mm-btn--primary', href: p.permalink });
			opt.innerHTML = '<span>' + t('chooseOptions', 'Choose options') + '</span>' + ICON.arrow;
			actions.appendChild(opt);
		}
		var more = el('a', { class: 'mm-btn mm-btn--ghost', href: p.permalink });
		more.innerHTML = '<span>' + t('viewProduct', 'View full details') + '</span>';
		actions.appendChild(more);
		body.appendChild(actions);
		wrap.appendChild(body);
		return wrap;
	}

	$(doc).on('click', '[data-mm-quickview]', function (e) {
		if (!cfg.rest || e.metaKey || e.ctrlKey) { return; }
		e.preventDefault();
		var id = this.getAttribute('data-mm-quickview');
		var m = ensureModal();
		var body = m.querySelector('.mm-modal__body');
		body.innerHTML = qvSkeleton();
		MM.open(m, '.mm-modal__close');
		fetch(cfg.rest + 'wc/store/v1/products/' + encodeURIComponent(id), { credentials: 'same-origin' })
			.then(function (r) { if (!r.ok) { throw new Error('qv'); } return r.json(); })
			.then(function (p) { body.innerHTML = ''; body.appendChild(renderQuickView(p)); })
			.catch(function () {
				var link = e.currentTarget && e.currentTarget.getAttribute('href');
				if (link) { window.location = link; } else { MM.close(m); }
			});
	});

	/* ------------------------------------------------------------------
	 * Quantity steppers
	 * ---------------------------------------------------------------- */
	function enhanceQty(ctx) {
		$$('.quantity', ctx).forEach(function (q) {
			var input = q.querySelector('input.qty');
			if (!input || input.type === 'hidden' || q.dataset.mmQty) { return; }
			q.dataset.mmQty = '1';
			var wrap = doc.createElement('div');
			wrap.className = 'mm-qty';
			var minus = doc.createElement('button');
			minus.type = 'button';
			minus.className = 'mm-qty__btn';
			minus.setAttribute('aria-label', 'Decrease quantity');
			minus.innerHTML = ICON.minus;
			var plus = minus.cloneNode(true);
			plus.setAttribute('aria-label', 'Increase quantity');
			plus.innerHTML = ICON.plus;
			input.parentNode.insertBefore(wrap, input);
			wrap.appendChild(minus);
			wrap.appendChild(input);
			wrap.appendChild(plus);
			function step(dir) {
				var stepVal = parseFloat(input.step) || 1;
				var min = input.min !== '' ? parseFloat(input.min) : 0;
				var max = input.max !== '' ? parseFloat(input.max) : Infinity;
				var v = (parseFloat(input.value) || 0) + dir * stepVal;
				input.value = Math.min(max, Math.max(min, v));
				$(input).trigger('change');
				input.dispatchEvent(new Event('input', { bubbles: true }));
			}
			minus.addEventListener('click', function () { step(-1); });
			plus.addEventListener('click', function () { step(1); });
		});
	}
	enhanceQty();
	$body.on('updated_cart_totals updated_checkout wc_fragments_refreshed', function () { enhanceQty(); });

	/* ------------------------------------------------------------------
	 * Shop filter drawer (mobile)
	 * ---------------------------------------------------------------- */
	var filters = doc.querySelector('[data-mm-filters]');
	var backdrop = null;
	function closeFilters() {
		if (!filters) { return; }
		filters.classList.remove('is-open');
		if (backdrop) { backdrop.remove(); backdrop = null; }
		if (MM.lockScroll) { MM.lockScroll(false); }
	}
	$(doc).on('click', '[data-mm-filters-open]', function (e) {
		e.preventDefault();
		if (!filters) { return; }
		filters.classList.add('is-open');
		backdrop = doc.createElement('div');
		backdrop.className = 'mm-filters-backdrop';
		backdrop.addEventListener('click', closeFilters);
		doc.body.appendChild(backdrop);
		if (MM.lockScroll) { MM.lockScroll(true); }
	});
	$(doc).on('click', '[data-mm-filters-close]', function (e) { e.preventDefault(); closeFilters(); });
	doc.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeFilters(); } });

	/* ------------------------------------------------------------------
	 * Sticky add-to-cart bar
	 * ---------------------------------------------------------------- */
	var sticky = doc.querySelector('[data-mm-sticky-atc]');
	var mainForm = doc.querySelector('.summary form.cart');
	if (sticky && mainForm && 'IntersectionObserver' in window) {
		var io = new IntersectionObserver(function (entries) {
			var past = !entries[0].isIntersecting && entries[0].boundingClientRect.top < 0;
			sticky.classList.toggle('is-visible', past);
			sticky.setAttribute('aria-hidden', past ? 'false' : 'true');
			var btn = sticky.querySelector('[data-mm-sticky-buy]');
			if (btn) { btn.tabIndex = past ? 0 : -1; }
			doc.body.classList.toggle('mm-sticky-visible', past);
		});
		io.observe(mainForm);
		sticky.querySelector('[data-mm-sticky-buy]').addEventListener('click', function () {
			var submit = mainForm.querySelector('.single_add_to_cart_button');
			var isSimple = !mainForm.classList.contains('variations_form') && !mainForm.querySelector('.group_table');
			if (isSimple && submit && !submit.disabled) {
				submit.click();
			} else {
				mainForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		});
	}

	/* ------------------------------------------------------------------
	 * Checkout progress (Details → Payment)
	 * ---------------------------------------------------------------- */
	var steps = doc.querySelector('[data-mm-steps]');
	var payment = doc.querySelector('[data-mm-checkout-payment]') || doc.getElementById('payment');
	if (steps && payment && 'IntersectionObserver' in window) {
		var details = steps.querySelector('[data-mm-step="details"]');
		var pay = steps.querySelector('[data-mm-step="payment"]');
		new IntersectionObserver(function (entries) {
			var inPay = entries[0].isIntersecting || entries[0].boundingClientRect.top < 0;
			if (!details || !pay) { return; }
			details.classList.toggle('is-done', inPay);
			details.classList.toggle('is-current', !inPay);
			pay.classList.toggle('is-current', inPay);
			if (inPay) { pay.setAttribute('aria-current', 'step'); details.removeAttribute('aria-current'); }
			else { details.setAttribute('aria-current', 'step'); pay.removeAttribute('aria-current'); }
		}, { rootMargin: '0px 0px -40% 0px' }).observe(payment);
	}

	/* ------------------------------------------------------------------
	 * Sign in / register tabs
	 * ---------------------------------------------------------------- */
	$(doc).on('click', '[data-mm-auth-tab]', function (e) {
		e.preventDefault();
		var target = this.getAttribute('data-mm-auth-tab');
		var auth = this.closest('[data-mm-auth]');
		if (!auth) { return; }
		$$('[data-mm-auth-tab]', auth).forEach(function (b) {
			if (b.getAttribute('role') === 'tab') { b.setAttribute('aria-selected', b.getAttribute('data-mm-auth-tab') === target ? 'true' : 'false'); }
		});
		var login = auth.querySelector('#mm-pane-login');
		var reg = auth.querySelector('#mm-pane-register');
		if (login) { login.toggleAttribute('data-mm-hidden', target !== 'login'); }
		if (reg) { reg.toggleAttribute('data-mm-hidden', target !== 'register'); }
		var pane = target === 'login' ? login : reg;
		var first = pane && pane.querySelector('input:not([type="hidden"])');
		if (first) { first.focus(); }
	});

	/* ------------------------------------------------------------------
	 * Confetti on the order confirmation page
	 * ---------------------------------------------------------------- */
	var confettiHost = doc.querySelector('[data-mm-confetti]');
	if (confettiHost && !MM.reduceMotion) {
		var canvas = doc.createElement('canvas');
		canvas.className = 'mm-confetti';
		doc.body.appendChild(canvas);
		var ctx = canvas.getContext('2d');
		var dpr = Math.min(2, window.devicePixelRatio || 1);
		canvas.width = innerWidth * dpr;
		canvas.height = innerHeight * dpr;
		canvas.style.width = innerWidth + 'px';
		canvas.style.height = innerHeight + 'px';
		ctx.scale(dpr, dpr);
		var cs = getComputedStyle(doc.documentElement);
		var colors = ['--mm-primary', '--mm-accent', '--mm-accent-2', '--mm-success'].map(function (v) { return cs.getPropertyValue(v).trim() || '#35a8f6'; });
		var rect = confettiHost.getBoundingClientRect();
		var originX = rect.left + rect.width / 2;
		var originY = Math.max(80, rect.top + 60);
		var bits = [];
		for (var i = 0; i < 140; i++) {
			var a = Math.random() * Math.PI * 2;
			var s = 4 + Math.random() * 9;
			bits.push({ x: originX, y: originY, vx: Math.cos(a) * s, vy: Math.sin(a) * s - 6, w: 5 + Math.random() * 6, h: 8 + Math.random() * 8, r: Math.random() * 6, vr: (Math.random() - .5) * .3, c: colors[i % colors.length], life: 0 });
		}
		var start = performance.now();
		(function frame(now) {
			var elapsed = now - start;
			ctx.clearRect(0, 0, innerWidth, innerHeight);
			bits.forEach(function (b) {
				b.vy += 0.22; b.vx *= 0.99; b.x += b.vx; b.y += b.vy; b.r += b.vr;
				ctx.save();
				ctx.globalAlpha = Math.max(0, 1 - elapsed / 2800);
				ctx.translate(b.x, b.y);
				ctx.rotate(b.r);
				ctx.fillStyle = b.c;
				ctx.fillRect(-b.w / 2, -b.h / 2, b.w, b.h * Math.abs(Math.cos(b.r * 2)));
				ctx.restore();
			});
			if (elapsed < 2900) { requestAnimationFrame(frame); } else { canvas.remove(); }
		})(start);
	}
})(window.jQuery);
