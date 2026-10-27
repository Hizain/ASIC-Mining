/**
 * Mine Mirth section block (mm/section): sidebar controls built from window.mmSections.schema,
 * with a live server-rendered preview.
 */
(function (wp) {
	'use strict';
	if (!wp || !wp.blocks || !window.mmSections) { return; }
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var C = wp.components;
	var BE = wp.blockEditor;
	var SSR = wp.serverSideRender;
	var S = window.mmSections;
	var SCHEMA = S.schema || {};
	var ICONS = (S.icons || []).map(function (n) { return { value: n, label: n }; });
	var TOOLS = Object.keys(S.tools || {}).map(function (k) { return { value: k, label: S.tools[k] }; });

	function set(obj, k, v) { var o = Object.assign({}, obj || {}); o[k] = v; return o; }
	function opts(o) { return Object.keys(o || {}).map(function (k) { return { value: k, label: o[k] }; }); }

	var BUTTON_FIELDS = [
		{ k: 'label', t: 'text', l: __('Text', 'minemirth') },
		{ k: 'url', t: 'url', l: __('Link', 'minemirth') },
		{ k: 'style', t: 'select', l: __('Style', 'minemirth'), o: { primary: 'Primary', glass: 'Glass', ghost: 'Ghost', outline: 'Outline', dark: 'Dark' } },
		{ k: 'icon', t: 'icon', l: __('Icon', 'minemirth') }
	];
	var QUERY_FIELDS = [
		{ k: 'algo', t: 'select', l: __('Algorithm', 'minemirth'), o: { '': 'Any', sha256: 'SHA-256 (Bitcoin)', scrypt: 'Scrypt (LTC/DOGE)', kheavyhash: 'kHeavyHash (Kaspa)', blake3: 'Blake3 (Alephium)', etchash: 'Etchash (ETC)', equihash: 'Equihash', x11: 'X11' } },
		{ k: 'cooling', t: 'select', l: __('Cooling', 'minemirth'), o: { '': 'Any', air: 'Air', hydro: 'Hydro', immersion: 'Immersion' } },
		{ k: 'brand', t: 'text', l: __('Brand contains', 'minemirth') },
		{ k: 'cat', t: 'text', l: __('Product category slug', 'minemirth') },
		{ k: 'sort', t: 'select', l: __('Sort', 'minemirth'), o: { popular: 'Best sellers', efficiency: 'Most efficient', value: 'Best $/TH', newest: 'Newest', price: 'Lowest price', hashrate: 'Highest hashrate' } },
		{ k: 'home', t: 'toggle', l: __('Home & quiet miners only', 'minemirth') },
		{ k: 'solo', t: 'toggle', l: __('Solo / open-source miners only', 'minemirth') },
		{ k: 'parts', t: 'toggle', l: __('Include PSUs and accessories', 'minemirth') }
	];

	function itemTitle(it, i) {
		return (it && (it.title || it.label || it.q || it.name || it.value)) || (__('Item', 'minemirth') + ' ' + (i + 1));
	}

	function Repeater(props) {
		var list = Array.isArray(props.value) ? props.value : [];
		var fields = props.fields;
		function update(i, v) { var n = list.slice(); n[i] = v; props.onChange(n); }
		function move(i, d) { var n = list.slice(); var j = i + d; if (j < 0 || j >= n.length) { return; } var t = n[i]; n[i] = n[j]; n[j] = t; props.onChange(n); }
		function remove(i) { var n = list.slice(); n.splice(i, 1); props.onChange(n); }
		return el('div', { className: 'mm-se-repeater' },
			el('p', { className: 'mm-se-label' }, props.label),
			list.map(function (it, i) {
				return el(C.PanelBody, { key: i, title: itemTitle(it, i), initialOpen: false, className: 'mm-se-item' },
					fields.map(function (f) { return el(Field, { key: f.k, def: f, value: (it || {})[f.k], onChange: function (v) { update(i, set(it, f.k, v)); } }); }),
					el('div', { className: 'mm-se-item__tools' },
						el(C.Button, { icon: 'arrow-up-alt2', label: __('Move up', 'minemirth'), onClick: function () { move(i, -1); }, disabled: i === 0 }),
						el(C.Button, { icon: 'arrow-down-alt2', label: __('Move down', 'minemirth'), onClick: function () { move(i, 1); }, disabled: i === list.length - 1 }),
						el(C.Button, { icon: 'admin-page', label: __('Duplicate', 'minemirth'), onClick: function () { var n = list.slice(); n.splice(i + 1, 0, Object.assign({}, it)); props.onChange(n); } }),
						el(C.Button, { icon: 'trash', isDestructive: true, label: __('Remove', 'minemirth'), onClick: function () { remove(i); } })
					)
				);
			}),
			el(C.Button, { variant: 'secondary', icon: 'plus', onClick: function () { props.onChange(list.concat([{}])); } }, __('Add', 'minemirth'))
		);
	}

	function ImageField(props) {
		var v = props.value;
		var url = typeof v === 'string' ? v : '';
		return el('div', { className: 'mm-se-image' },
			el('p', { className: 'mm-se-label' }, props.label),
			url ? el('img', { src: url, alt: '', style: { maxWidth: '100%', borderRadius: '6px', marginBottom: '8px' } }) : null,
			el(BE.MediaUploadCheck, {}, el(BE.MediaUpload, {
				onSelect: function (m) { props.onChange(m.url); },
				allowedTypes: ['image'],
				render: function (o) { return el(C.Button, { variant: 'secondary', onClick: o.open }, url ? __('Replace image', 'minemirth') : __('Choose image', 'minemirth')); }
			})),
			url ? el(C.Button, { variant: 'link', isDestructive: true, onClick: function () { props.onChange(''); } }, __('Remove', 'minemirth')) : null
		);
	}

	function Field(props) {
		var f = props.def, v = props.value, on = props.onChange;
		switch (f.t) {
			case 'textarea':
			case 'html':
				return el(C.TextareaControl, { label: f.l, help: f.h, value: v || '', rows: f.t === 'html' ? 5 : 3, onChange: on });
			case 'lines':
				return el(C.TextareaControl, { label: f.l, help: f.h, rows: 5, value: Array.isArray(v) ? v.join('\n') : (v || ''), onChange: function (x) { on(x.split('\n')); } });
			case 'select':
				return el(C.SelectControl, { label: f.l, help: f.h, value: v === undefined || v === null ? '' : String(v), options: opts(f.o), onChange: on });
			case 'toggle':
				return el(C.ToggleControl, { label: f.l, help: f.h, checked: !!v, onChange: on });
			case 'number':
				return el(C.TextControl, { label: f.l, help: f.h, type: 'number', value: v === undefined ? '' : v, onChange: function (x) { on(x === '' ? '' : Number(x)); } });
			case 'icon':
				return el(C.ComboboxControl, { label: f.l, value: v || '', options: ICONS, onChange: function (x) { on(x || ''); } });
			case 'tool':
				return el(C.SelectControl, { label: f.l, value: v || '', options: [{ value: '', label: '—' }].concat(TOOLS), onChange: on });
			case 'image':
				return el(ImageField, { label: f.l, value: v, onChange: on });
			case 'items':
				return el(Repeater, { label: f.l, value: v, fields: f.f || [], onChange: on });
			case 'buttons':
				return el(Repeater, { label: f.l, value: v, fields: BUTTON_FIELDS, onChange: on });
			case 'query':
				return el('div', { className: 'mm-se-query' }, el('p', { className: 'mm-se-label' }, f.l), QUERY_FIELDS.map(function (q) { return el(Field, { key: q.k, def: q, value: (v || {})[q.k], onChange: function (x) { on(set(v, q.k, x)); } }); }));
			default:
				return el(C.TextControl, { label: f.l, help: f.h, value: v || '', onChange: on });
		}
	}

	function Edit(props) {
		var a = props.attributes;
		var variant = a.variant || 'features';
		var def = SCHEMA[variant] || { fields: [] };
		var data = a.data || {};
		var blockProps = BE.useBlockProps({ className: 'mm-se-block' });
		function setData(k, v) { props.setAttributes({ data: set(data, k, v) }); }
		var common = (S.common || []).filter(function (f) { return !(def.notone && f.k === 'tone'); });
		return el(Fragment, {},
			el(BE.InspectorControls, {},
				el(C.PanelBody, { title: __('Layout', 'minemirth'), initialOpen: true },
					el(C.SelectControl, {
						label: __('Section type', 'minemirth'),
						value: variant,
						options: Object.keys(SCHEMA).map(function (k) { return { value: k, label: SCHEMA[k].label }; }),
						onChange: function (v) { props.setAttributes({ variant: v, data: Object.keys(data).length ? data : (SCHEMA[v] && SCHEMA[v].data) || {} }); }
					})
				),
				el(C.PanelBody, { title: __('Content', 'minemirth'), initialOpen: true },
					def.fields.map(function (f) { return el(Field, { key: f.k, def: f, value: data[f.k], onChange: function (v) { setData(f.k, v); } }); })
				),
				el(C.PanelBody, { title: __('Style', 'minemirth'), initialOpen: false },
					common.map(function (f) { return el(Field, { key: f.k, def: f, value: data[f.k], onChange: function (v) { setData(f.k, v); } }); })
				)
			),
			el('div', blockProps,
				el(C.Disabled, {},
					el(SSR, { block: 'mm/section', attributes: { variant: variant, data: data }, httpMethod: 'POST', LoadingResponsePlaceholder: function () { return el('div', { className: 'mm-se-loading' }, el(C.Spinner)); } })
				)
			)
		);
	}

	wp.blocks.registerBlockType('mm/section', {
		apiVersion: 3,
		title: __('Mine Mirth section', 'minemirth'),
		description: __('Premium page section: pick a layout, then edit its content in the sidebar.', 'minemirth'),
		category: 'mm-sections',
		icon: 'layout',
		attributes: { variant: { type: 'string', default: 'features' }, data: { type: 'object', default: {} } },
		supports: { html: false, customClassName: false, className: false },
		variations: Object.keys(SCHEMA).map(function (k, i) {
			return { name: k, title: SCHEMA[k].label, icon: SCHEMA[k].icon || 'layout', isDefault: i === 0, attributes: { variant: k, data: SCHEMA[k].data || {} }, scope: ['inserter', 'transform'], isActive: function (attrs) { return attrs.variant === k; } };
		}),
		edit: Edit,
		save: function () { return null; }
	});
})(window.wp);
