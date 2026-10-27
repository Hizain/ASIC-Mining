/*!
 * Mine Mirth — "Mine Mirth promo" block (editor).
 * Server-rendered; this file only provides the sidebar settings.
 */
(function (wp) {
	'use strict';
	if (!wp || !wp.blocks) { return; }

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var InspectorControls = (wp.blockEditor || wp.editor).InspectorControls;
	var useBlockProps = (wp.blockEditor || {}).useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var ServerSideRender = wp.serverSideRender;
	var useSelect = wp.data.useSelect;

	wp.blocks.registerBlockType('minemirth/promo', {
		apiVersion: 3,
		title: __('Mine Mirth promo', 'minemirth'),
		description: __('A product card, mini calculator, hosting banner, newsletter form or saved promotion inside your article.', 'minemirth'),
		icon: 'megaphone',
		category: 'widgets',
		keywords: ['product', 'promo', 'calculator', 'hosting', 'cta'],
		attributes: {
			mode: { type: 'string', default: 'promo' },
			promoId: { type: 'number', default: 0 },
			productId: { type: 'number', default: 0 },
			heading: { type: 'string', default: '' }
		},
		supports: { html: false },
		edit: function (props) {
			var a = props.attributes;
			var set = props.setAttributes;
			var blockProps = useBlockProps ? useBlockProps() : {};
			var promos = useSelect(function (select) {
				return select('core').getEntityRecords('postType', 'mm_promo', { per_page: 100, status: 'publish' }) || [];
			}, []);
			var options = [{ label: __('— Select a promotion —', 'minemirth'), value: 0 }].concat(promos.map(function (p) {
				return { label: (p.title && p.title.rendered) || ('#' + p.id), value: p.id };
			}));

			return el('div', blockProps,
				el(InspectorControls, null,
					el(PanelBody, { title: __('Promo settings', 'minemirth'), initialOpen: true },
						el(SelectControl, {
							label: __('Show', 'minemirth'),
							value: a.mode,
							options: [
								{ label: __('Saved promotion', 'minemirth'), value: 'promo' },
								{ label: __('Product card', 'minemirth'), value: 'product' },
								{ label: __('Mini calculator', 'minemirth'), value: 'calculator' },
								{ label: __('Hosting banner', 'minemirth'), value: 'hosting' },
								{ label: __('Newsletter signup', 'minemirth'), value: 'newsletter' }
							],
							onChange: function (v) { set({ mode: v }); }
						}),
						a.mode === 'promo' && el(SelectControl, {
							label: __('Promotion', 'minemirth'),
							value: a.promoId,
							options: options,
							onChange: function (v) { set({ promoId: parseInt(v, 10) || 0 }); }
						}),
						a.mode === 'product' && el(TextControl, {
							label: __('Product ID', 'minemirth'),
							type: 'number',
							value: a.productId || '',
							onChange: function (v) { set({ productId: parseInt(v, 10) || 0 }); }
						}),
						(a.mode === 'calculator' || a.mode === 'hosting' || a.mode === 'newsletter') && el(TextControl, {
							label: __('Headline (optional)', 'minemirth'),
							value: a.heading,
							onChange: function (v) { set({ heading: v }); }
						})
					)
				),
				el(ServerSideRender, { block: 'minemirth/promo', attributes: a })
			);
		},
		save: function () { return null; }
	});
})(window.wp);
