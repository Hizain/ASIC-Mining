/*!
 * Mine Mirth — Customizer live preview.
 * Applies color/size tokens and body classes instantly, re-initialises
 * scripts after selective refresh, and scrolls to the section being edited.
 */
(function (api, $) {
	'use strict';
	if (!api) { return; }

	var cfg = window.MM_PREVIEW || { css: {}, classes: {} };
	var style = document.createElement('style');
	style.id = 'mm-preview-tokens';
	document.head.appendChild(style);

	function format(def, value) {
		if (def.map) { return def.map[value] || Object.keys(def.map).map(function (k) { return def.map[k]; })[0]; }
		if (value === '' || value === null || value === undefined) { value = def.default; }
		if (def.divide && !isNaN(value)) { value = parseFloat(value) / def.divide; }
		return String(value) + (def.unit || '');
	}

	function rebuild() {
		var groups = { root: [], light: [], dark: [] };
		Object.keys(cfg.css).forEach(function (id) {
			var def = cfg.css[id];
			var setting = api(id);
			if (!setting) { return; }
			groups[def.scope] = groups[def.scope] || [];
			groups[def.scope].push(def.var + ':' + format(def, setting.get()));
		});
		style.textContent =
			':root{' + groups.root.join(';') + '}' +
			':root,:root[data-theme="light"],.mm-scheme-light{' + groups.light.join(';') + '}' +
			':root[data-theme="dark"],.mm-scheme-dark{' + groups.dark.join(';') + '}';
		document.dispatchEvent(new CustomEvent('mm:themechange', { detail: { preview: true } }));
	}

	Object.keys(cfg.css).forEach(function (id) {
		api(id, function (value) { value.bind(rebuild); });
	});

	Object.keys(cfg.classes).forEach(function (id) {
		var def = cfg.classes[id];
		api(id, function (value) {
			value.bind(function (to) {
				var body = document.body;
				def.choices.forEach(function (c) { body.classList.remove(def.pattern.replace('%s', c)); });
				body.classList.add(def.pattern.replace('%s', to));
			});
		});
	});

	api('blogname', function (value) {
		value.bind(function (to) {
			$('.mm-logo__text').text(to);
			$('.mm-footer__wordmark').text(to);
		});
	});

	api.bind('preview-ready', function () {
		rebuild();
		api.preview.bind('mm-scroll', function (selector) {
			var el = document.querySelector(selector);
			if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
		});
	});

	if (api.selectiveRefresh) {
		api.selectiveRefresh.bind('partial-content-rendered', function (placement) {
			var node = placement.container && placement.container[0];
			if (window.MineMirth && node) {
				window.MineMirth.init(node.parentNode || node);
			}
			if (window.MineMirth && window.MineMirth.market && window.MineMirth.market.refresh) {
				window.MineMirth.market.refresh();
			}
		});
	}
})(window.wp && window.wp.customize, window.jQuery);
