/* Mine Mirth Mailer: email blocks for the campaign editor (no build step). */
(function (wp) {
	'use strict';
	if (!wp || !wp.blocks || !wp.element) { return; }
	var el = wp.element.createElement;
	var be = wp.blockEditor;
	var c = wp.components;
	var SSR = wp.serverSideRender;
	var __ = wp.i18n.__;

	function text(props, key, label, help) {
		return el(c.TextControl, { label: label, help: help, value: props.attributes[key] || '', onChange: function (v) { var o = {}; o[key] = v; props.setAttributes(o); } });
	}
	function area(props, key, label) {
		return el(c.TextareaControl, { label: label, value: props.attributes[key] || '', onChange: function (v) { var o = {}; o[key] = v; props.setAttributes(o); } });
	}
	function select(props, key, label, options) {
		return el(c.SelectControl, { label: label, value: props.attributes[key], options: options, onChange: function (v) { var o = {}; o[key] = v; props.setAttributes(o); } });
	}
	function range(props, key, label, min, max) {
		return el(c.RangeControl, { label: label, value: props.attributes[key], min: min, max: max, onChange: function (v) { var o = {}; o[key] = v; props.setAttributes(o); } });
	}
	function preview(name, props) {
		return el('div', be.useBlockProps ? be.useBlockProps({ className: 'mmm-block-preview' }) : { className: 'mmm-block-preview' },
			el(SSR, { block: 'mmm/' + name, attributes: props.attributes, EmptyResponsePlaceholder: function () { return el('p', { style: { color: '#8a94a6', padding: '12px', border: '1px dashed #c7d2de', borderRadius: '10px' } }, __('Nothing to show yet. Check the settings in the sidebar.')); } })
		);
	}
	function register(name, title, icon, description, fields) {
		wp.blocks.registerBlockType('mmm/' + name, {
			apiVersion: 2,
			title: title,
			description: description,
			icon: icon,
			category: 'mmm',
			edit: function (props) {
				return el(wp.element.Fragment, null,
					fields ? el(be.InspectorControls, null, el(c.PanelBody, { title: __('Settings'), initialOpen: true }, fields(props))) : null,
					preview(name, props)
				);
			},
			save: function () { return null; }
		});
	}

	var productSources = [
		{ label: __('Newest'), value: 'latest' },
		{ label: __('Best sellers'), value: 'popular' },
		{ label: __('On sale'), value: 'sale' },
		{ label: __('Featured'), value: 'featured' },
		{ label: __('A category (slug below)'), value: 'category' },
		{ label: __('Pick products (IDs below)'), value: 'ids' }
	];

	register('hero', __('Email hero'), 'megaphone', __('Big headline, intro text, button and optional image.'), function (p) {
		return [
			text(p, 'eyebrow', __('Small label above the headline')),
			text(p, 'title', __('Headline')),
			area(p, 'text', __('Intro text')),
			text(p, 'buttonText', __('Button text')),
			text(p, 'buttonUrl', __('Button link')),
			el(be.MediaUploadCheck, null, el(be.MediaUpload, {
				onSelect: function (m) { p.setAttributes({ imageId: m.id }); },
				allowedTypes: ['image'],
				value: p.attributes.imageId,
				render: function (o) {
					return el('div', { style: { marginBottom: '12px' } },
						el(c.Button, { variant: 'secondary', onClick: o.open }, p.attributes.imageId ? __('Change image') : __('Add image')),
						p.attributes.imageId ? el(c.Button, { variant: 'link', isDestructive: true, onClick: function () { p.setAttributes({ imageId: 0 }); }, style: { marginLeft: '8px' } }, __('Remove')) : null
					);
				}
			}))
		];
	});

	register('products', __('Email products'), 'cart', __('Product cards with image, specs, price, stock and a button.'), function (p) {
		return [
			text(p, 'title', __('Heading')),
			text(p, 'eyebrow', __('Small label above the heading')),
			select(p, 'source', __('Which products'), productSources),
			p.attributes.source === 'category' ? text(p, 'category', __('Category slug'), __('For example: bitcoin-miners')) : null,
			p.attributes.source === 'ids' ? text(p, 'ids', __('Product IDs'), __('Comma separated, in the order to show')) : null,
			range(p, 'count', __('How many'), 1, 9),
			range(p, 'columns', __('Per row'), 1, 3)
		];
	});

	register('posts', __('Email articles'), 'admin-post', __('Latest articles with image, summary and a link.'), function (p) {
		return [
			text(p, 'title', __('Heading')),
			text(p, 'eyebrow', __('Small label above the heading')),
			select(p, 'source', __('Which articles'), [{ label: __('Newest'), value: 'latest' }, { label: __('A category (slug below)'), value: 'category' }]),
			p.attributes.source === 'category' ? text(p, 'category', __('Category slug')) : null,
			range(p, 'count', __('How many'), 1, 6)
		];
	});

	register('network', __('Bitcoin network snapshot'), 'chart-line', __('Live price, hashrate, difficulty and next adjustment at send time.'), null);

	register('cta', __('Email call to action'), 'star-filled', __('Dark card with a headline, text and button.'), function (p) {
		return [
			text(p, 'eyebrow', __('Small label')),
			text(p, 'title', __('Headline')),
			area(p, 'text', __('Text')),
			text(p, 'buttonText', __('Button text')),
			text(p, 'buttonUrl', __('Button link'))
		];
	});

	register('reply', __('Ask us anything'), 'format-chat', __('Invites a reply. Answers arrive in Mailer › Inbox.'), function (p) {
		return [
			text(p, 'title', __('Headline'), __('Leave empty for the default')),
			area(p, 'text', __('Text')),
			text(p, 'buttonText', __('Button text')),
			select(p, 'topic', __('Pre-selected topic'), [{ label: __('None'), value: '' }, { label: __('Quote'), value: 'quote' }, { label: __('Hosting'), value: 'hosting' }, { label: __('Question'), value: 'question' }])
		];
	});
})(window.wp);
