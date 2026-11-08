/* Mine Mirth Cookie Consent. */
(function () {
	'use strict';
	var C = window.mmccConfig || {};
	var banner = document.querySelector('[data-mmcc]');
	var reopen = document.querySelector('.mmcc-reopen[data-mmcc-open]');
	var loaded = false;

	function read() {
		var m = document.cookie.match(new RegExp('(?:^|; )' + C.cookie + '=([^;]*)'));
		if (!m) { return ''; }
		var p = decodeURIComponent(m[1]).split('|');
		return (p[0] === 'accept' || p[0] === 'deny') && +p[1] === +C.version ? p[0] : '';
	}

	function write(choice) {
		var v = encodeURIComponent(choice + '|' + C.version + '|' + Math.floor(Date.now() / 1000));
		document.cookie = C.cookie + '=' + v + '; Max-Age=' + (C.days * 86400) + '; Path=' + (C.path || '/') + '; SameSite=Lax' + (C.secure ? '; Secure' : '');
	}

	/* Cookies analytics and ad tags may already have set (Google, Meta, Microsoft, HubSpot, Hotjar). */
	function clearTrackingCookies() {
		var names = document.cookie.split(';').map(function (c) { return c.split('=')[0].trim(); });
		var host = location.hostname.split('.');
		var domains = [''];
		for (var i = 0; i < host.length - 1; i++) { domains.push('; Domain=.' + host.slice(i).join('.')); }
		names.forEach(function (n) {
			if (/^(_ga|_gid|_gat|_gcl_|_fbp|_fbc|_uet|_clck|_clsk|__hs|hubspotutk|_hj)/.test(n)) {
				domains.forEach(function (d) { document.cookie = n + '=; Max-Age=0; Path=/' + d; });
			}
		});
	}

	function signal(choice) {
		var g = choice === 'accept' ? 'granted' : 'denied';
		if (C.mode && typeof window.gtag === 'function') {
			window.gtag('consent', 'update', { ad_storage: g, analytics_storage: g, ad_user_data: g, ad_personalization: g });
			window.gtag('set', 'ads_data_redaction', g === 'denied');
		}
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push({ event: 'mm_consent_update', mm_consent: choice });
		// WP Consent API: plugins that follow it (e.g. Site Kit) read the visitor's choice.
		if (typeof window.wp_set_consent === 'function') {
			var a = choice === 'accept' ? 'allow' : 'deny';
			window.wp_set_consent('functional', 'allow');
			['preferences', 'statistics', 'statistics-anonymous', 'marketing'].forEach(function (cat) { window.wp_set_consent(cat, a); });
		}
		document.documentElement.setAttribute('data-mm-consent', choice);
		document.dispatchEvent(new CustomEvent('mmcc:consent', { detail: { choice: choice } }));
	}

	/* Scripts from "Scripts to load after Accept": recreated so the browser runs them. */
	function loadScripts() {
		if (loaded) { return; }
		loaded = true;
		var tpl = document.getElementById('mmcc-scripts');
		if (!tpl || !tpl.content) { return; }
		Array.prototype.forEach.call(tpl.content.childNodes, function (node) {
			if (node.nodeName === 'SCRIPT') {
				var s = document.createElement('script');
				Array.prototype.forEach.call(node.attributes, function (a) { s.setAttribute(a.name, a.value); });
				s.text = node.textContent;
				document.head.appendChild(s);
			} else if (node.nodeType === 1) {
				document.body.appendChild(node.cloneNode(true));
			}
		});
	}

	function record(choice) {
		if (!C.record || !window.fetch) { return; }
		try {
			fetch(C.record, { method: 'POST', credentials: 'omit', keepalive: true, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ choice: choice, page: location.pathname }) }).catch(function () {});
		} catch (e) {}
	}

	function show() {
		if (!banner) { return; }
		document.body.insertBefore(banner, document.body.firstChild); // First in reading and Tab order.
		banner.hidden = false;
		banner.classList.remove('is-out');
		banner.classList.add('is-in');
		if (reopen) { reopen.hidden = true; }
	}

	function hide() {
		if (!banner || banner.hidden) { return; }
		banner.classList.remove('is-in');
		banner.classList.add('is-out');
		var done = function () { banner.hidden = true; banner.classList.remove('is-out'); };
		if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { done(); } else { setTimeout(done, 300); }
		if (reopen) { reopen.hidden = false; }
	}

	function choose(choice, how) {
		var before = read();
		write(choice);
		signal(choice);
		record(how || choice);
		hide();
		if (choice === 'accept') {
			loadScripts();
		} else {
			clearTrackingCookies();
			// Trackers already running on this page cannot be unloaded: reload so they stop.
			if (before === 'accept') { location.reload(); }
		}
	}

	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-mmcc-choice]');
		if (b) { choose(b.getAttribute('data-mmcc-choice')); return; }
		var o = e.target.closest('[data-mmcc-open], a[href="#mm-cookie-settings"]');
		if (o) {
			e.preventDefault();
			show();
			var current = read();
			var focus = banner && banner.querySelector('[data-mmcc-choice="' + (current === 'deny' ? 'accept' : 'deny') + '"]');
			if (focus) { focus.focus(); }
		}
	});

	// Public helpers for other scripts: mmConsent.get(), .accepted(), .open().
	window.mmConsent = {
		get: function () { return read() || 'none'; },
		accepted: function () { return read() === 'accept'; },
		open: show
	};

	var current = read();
	if (C.preview) {
		show();
	} else if (current) {
		if (current === 'accept') { loadScripts(); }
		if (reopen) { reopen.hidden = false; }
	} else if (C.gpc && navigator.globalPrivacyControl === true) {
		// The browser says "do not sell or share": treat it as Deny, without asking.
		write('deny');
		signal('deny');
		record('gpc');
		if (reopen) { reopen.hidden = false; }
	} else {
		setTimeout(show, C.delay || 0);
	}
})();
