/*!
 * Mine Mirth — Customizer controls: sortable sections, sliders, presets,
 * conditional fields and "scroll preview to section".
 */
(function (api, $) {
	'use strict';
	if (!api) { return; }
	var cfg = window.MM_CONTROLS || { deps: {}, homeSections: [] };

	/* Sortable homepage sections */
	api.controlConstructor['mm-sortable'] = api.Control.extend({
		ready: function () {
			var control = this;
			var $list = control.container.find('.mm-ctrl-sortable__list');

			function save() {
				var value = $list.children('li').map(function () {
					var $li = $(this);
					var on = $li.find('input[type="checkbox"]').prop('checked');
					$li.toggleClass('is-off', !on);
					return (on ? '' : '-') + $li.data('id');
				}).get().join(',');
				control.setting.set(value);
			}

			$list.sortable({
				handle: '.mm-ctrl-sortable__handle',
				axis: 'y',
				tolerance: 'pointer',
				update: save
			});
			$list.on('change', 'input[type="checkbox"]', save);
			$list.on('click', '[data-move]', function (e) {
				e.preventDefault();
				var $li = $(this).closest('li');
				if ($(this).data('move') === 'up') { $li.prev().before($li); } else { $li.next().after($li); }
				save();
			});
			$list.on('click', '.mm-ctrl-sortable__name', function (e) {
				e.preventDefault();
				var section = api.section($(this).data('section'));
				if (section) { section.focus(); }
			});
		}
	});

	/* Range sliders with a synced number input + reset */
	api.controlConstructor['mm-range'] = api.Control.extend({
		ready: function () {
			var control = this;
			var $range = control.container.find('input[type="range"]');
			var $num = control.container.find('input[type="number"]');
			$range.on('input change', function () { $num.val($range.val()); });
			$num.on('input change', function () {
				$range.val($num.val());
				control.setting.set($num.val());
			});
			control.setting.bind(function (v) { $range.val(v); $num.val(v); });
			control.container.on('click', '.mm-ctrl-range__reset', function (e) {
				e.preventDefault();
				control.setting.set($(this).data('default'));
			});
		}
	});

	/* One-click presets */
	api.controlConstructor['mm-presets'] = api.Control.extend({
		ready: function () {
			this.container.on('click', '.mm-ctrl-preset', function (e) {
				e.preventDefault();
				var values = $(this).data('preset') || {};
				Object.keys(values).forEach(function (key) {
					var setting = api('mm_' + key);
					if (setting) { setting.set(values[key]); }
				});
				$(this).addClass('is-applied').siblings().removeClass('is-applied');
			});
		}
	});

	api.bind('ready', function () {
		/* Conditional fields */
		Object.keys(cfg.deps).forEach(function (id) {
			var rule = cfg.deps[id];
			api.control(id, function (control) {
				api(rule[0], function (controller) {
					var apply = function (value) {
						var normalized = typeof rule[1][0] === 'boolean' ? !!value && value !== 'false' : value;
						var show = rule[1].indexOf(normalized) > -1;
						control.container.toggle(show);
					};
					apply(controller.get());
					controller.bind(apply);
				});
			});
		});

		/* Scroll the preview to the homepage section being edited */
		cfg.homeSections.forEach(function (id) {
			api.section('mm_home_' + id, function (section) {
				section.expanded.bind(function (open) {
					if (open) { api.previewer.send('mm-scroll', '#mm-section-' + id); }
				});
			});
		});
	});
})(window.wp && window.wp.customize, window.jQuery);
