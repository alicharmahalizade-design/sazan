(function () {
	'use strict';

	function fieldGroup(box) {
		return box ? box.querySelector('.sazan-sb-download-fields') : null;
	}

	document.addEventListener('change', function (event) {
		if (!event.target.matches('input[name="_sazan_sb_download_enabled"]')) return;
		var group = fieldGroup(event.target.closest('.sazan-sb-meta-box'));
		if (group) group.classList.toggle('is-disabled', !event.target.checked);
	});

	document.addEventListener('click', function (event) {
		var select = event.target.closest('.sazan-sb-media-select');
		var remove = event.target.closest('.sazan-sb-media-remove');
		if (!select && !remove) return;
		event.preventDefault();

		var row = event.target.closest('.sazan-sb-media-row');
		if (!row) return;
		var idInput = row.querySelector('.sazan-sb-media-id');
		var urlInput = row.querySelector('.sazan-sb-media-url');
		var status = row.parentElement.querySelector('.sazan-sb-file-status');

		if (remove) {
			idInput.value = '';
			urlInput.value = '';
			remove.hidden = true;
			if (status) status.textContent = 'هنوز فایلی انتخاب نشده است.';
			return;
		}

		if (!window.wp || !wp.media) return;
		var frame = wp.media({
			title: 'انتخاب فایل راهنمای مقاله',
			button: { text: 'استفاده از این فایل' },
			multiple: false
		});
		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			idInput.value = attachment.id || '';
			urlInput.value = attachment.url || '';
			remove.hidden = false;
			if (status) status.textContent = 'فایل انتخاب‌شده: ' + (attachment.filename || attachment.title || 'فایل رسانه');
		});
		frame.open();
	});
})();
