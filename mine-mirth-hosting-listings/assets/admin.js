/* Mine Mirth Hosting Listings: admin helpers. */
(function () {
	'use strict';

	// Listing images: pick from the media library, remove, drag to reorder.
	var input = document.querySelector('[data-mmhl-gallery-input]');
	var list = document.querySelector('[data-mmhl-gallery-list]');
	var add = document.querySelector('[data-mmhl-gallery-add]');
	function save() {
		if (!input || !list) { return; }
		input.value = Array.prototype.map.call(list.children, function (li) { return li.getAttribute('data-id'); }).join(',');
	}
	function item(id, url) {
		var li = document.createElement('li');
		li.setAttribute('data-id', id);
		li.draggable = true;
		var img = document.createElement('img');
		img.src = url;
		img.alt = '';
		var x = document.createElement('button');
		x.type = 'button';
		x.className = 'mmhl-thumbs__x';
		x.setAttribute('aria-label', 'Remove image');
		x.textContent = '×';
		li.appendChild(img);
		li.appendChild(x);
		return li;
	}
	if (list) {
		Array.prototype.forEach.call(list.children, function (li) { li.draggable = true; });
		list.addEventListener('click', function (e) {
			if (e.target.classList.contains('mmhl-thumbs__x')) { e.target.parentNode.remove(); save(); }
		});
		var dragged = null;
		list.addEventListener('dragstart', function (e) { dragged = e.target.closest('li'); });
		list.addEventListener('dragover', function (e) {
			e.preventDefault();
			var over = e.target.closest('li');
			if (dragged && over && over !== dragged) {
				var r = over.getBoundingClientRect();
				list.insertBefore(dragged, e.clientX > r.left + r.width / 2 ? over.nextSibling : over);
			}
		});
		list.addEventListener('drop', function (e) { e.preventDefault(); save(); });
		list.addEventListener('dragend', save);
	}
	if (add && window.wp && wp.media) {
		var frame;
		add.addEventListener('click', function () {
			if (!frame) {
				frame = wp.media({ title: (window.mmhlAdmin || {}).pick, button: { text: (window.mmhlAdmin || {}).use }, library: { type: 'image' }, multiple: 'add' });
				frame.on('select', function () {
					frame.state().get('selection').each(function (a) {
						var d = a.toJSON();
						if (list.querySelector('[data-id="' + d.id + '"]')) { return; }
						var url = d.sizes && d.sizes.thumbnail ? d.sizes.thumbnail.url : d.url;
						list.appendChild(item(d.id, url));
					});
					save();
				});
			}
			frame.open();
		});
	}

	// Product "Host this miner button" box: show only the field for the chosen option.
	document.querySelectorAll('[data-mmhl-host]').forEach(function (box) {
		var mode = box.querySelector('[data-mmhl-host-mode]');
		function sync() {
			box.querySelectorAll('[data-mmhl-show]').forEach(function (el) { el.hidden = el.getAttribute('data-mmhl-show') !== mode.value; });
		}
		mode.addEventListener('change', sync);
		sync();
	});
})();
