(function () {
	'use strict';

	function init() {
		var root = document.querySelector('.szp-set');
		if (!root) return;
		var tabs = root.querySelectorAll('.szp-set-tab');
		var panels = root.querySelectorAll('.szp-set-panel');

		function activate(id) {
			tabs.forEach(function (t) { t.classList.toggle('active', t.getAttribute('data-tab') === id); });
			panels.forEach(function (p) { p.classList.toggle('active', p.getAttribute('data-panel') === id); });
			try { history.replaceState(null, '', '#' + id); } catch (e) {}
		}

		tabs.forEach(function (t) {
			t.addEventListener('click', function () { activate(t.getAttribute('data-tab')); });
		});

		var hash = (location.hash || '').replace('#', '');
		var has = false;
		tabs.forEach(function (t) { if (t.getAttribute('data-tab') === hash) has = true; });
		activate(has ? hash : (tabs[0] && tabs[0].getAttribute('data-tab')));

		// اگر ذخیره با موفقیت انجام شده، اسکرول به بالا و نمایش پیام.
	}

	if (document.readyState !== 'loading') init();
	else document.addEventListener('DOMContentLoaded', init);
})();
