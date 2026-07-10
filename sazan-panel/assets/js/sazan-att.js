(function () {
	'use strict';

	var FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
	function faDigits(s) { return String(s).replace(/[0-9]/g, function (d) { return FA[d]; }); }

	function cfg(key) {
		return (window.SZP_FRONT && SZP_FRONT[key]) || (window.SZP_ADMIN && SZP_ADMIN[key]) || '';
	}

	function ajax(action, data, cb) {
		data.append('action', action);
		data.append('nonce', cfg('nonce'));
		fetch(cfg('ajax'), { method: 'POST', credentials: 'same-origin', body: data })
			.then(function (r) { return r.json(); })
			.then(cb)
			.catch(function () { cb({ success: false, data: { msg: 'خطای ارتباط با سرور.' } }); });
	}

	/* ---------------- صفحهٔ ثبت حضور (اسکن دانشجو) ---------------- */

	function initCheckin(root) {
		if (root.dataset.ciInit) return;
		root.dataset.ciInit = '1';

		var session = root.getAttribute('data-session');
		var token = root.getAttribute('data-token');
		var action = root.getAttribute('data-action') || 'szp_att_checkin';
		var msg = root.querySelector('.szp-att-ci-msg');
		var btn = root.querySelector('.szp-att-ci-btn');
		if (!btn || btn.tagName === 'A') return;

		var mobileEl = root.querySelector('.szp-att-ci-mobile');
		var nameEl = root.querySelector('.szp-att-ci-fname');

		function show(text, kind) {
			if (!msg) return;
			msg.textContent = text;
			msg.className = 'szp-att-ci-msg is-show' + (kind ? ' is-' + kind : '');
		}

		btn.addEventListener('click', function () {
			if (btn.disabled) return;
			var mode = btn.getAttribute('data-mode');
			var d = new FormData();
			d.append('session_id', session);
			d.append('token', token);

			if (mode === 'guest') {
				var mob = mobileEl ? mobileEl.value.trim() : '';
				if (mob.replace(/\D/g, '').length < 4) { show('شمارهٔ موبایل را وارد کنید.', 'err'); return; }
				d.append('mobile', mob);
				if (nameEl) d.append('name', nameEl.value.trim());
			}

			d.append('mode', mode || '');
			btn.disabled = true;
			show('در حال ثبت…', '');
			ajax(action, d, function (res) {
				if (res && res.success) {
					var m = (res.data && res.data.msg) || 'ثبت شد ✓';
					show(m, (res.data && (res.data.bucket === 'ontime' || res.data.already)) ? 'ok' : 'warn');
					var form = root.querySelector('.szp-att-ci-form');
					if (form) form.style.display = 'none';
					btn.style.display = 'none';
				} else {
					btn.disabled = false;
					show((res && res.data && res.data.msg) || 'خطا در ثبت حضور.', 'err');
				}
			});
		});
	}

	/* ---------------- تابلوی زندهٔ کلاس ---------------- */

	function initBoard(root) {
		if (root.dataset.bInit) return;
		root.dataset.bInit = '1';

		var session = root.getAttribute('data-session');
		var token = root.getAttribute('data-token');
		var boardAction = root.getAttribute('data-board-action') || 'szp_att_board';
		var poll = (parseInt(root.getAttribute('data-poll'), 10) || 6) * 1000;
		var totalEl = root.querySelector('[data-total]');
		var clockEl = root.querySelector('[data-clock]');

		var cols = {};
		root.querySelectorAll('[data-col]').forEach(function (c) {
			cols[c.getAttribute('data-col')] = {
				list: c.querySelector('[data-list]'),
				n: c.querySelector('[data-n]')
			};
		});

		function tickClock() {
			if (!clockEl) return;
			var d = new Date();
			var hh = ('0' + d.getHours()).slice(-2), mm = ('0' + d.getMinutes()).slice(-2);
			clockEl.textContent = faDigits(hh + ':' + mm);
		}
		tickClock();
		setInterval(tickClock, 15000);

		function known(list) {
			var s = {};
			list.querySelectorAll('.szp-att-person').forEach(function (p) { s[p.getAttribute('data-uid')] = 1; });
			return s;
		}

		function refresh() {
			var d = new FormData();
			d.append('session_id', session);
			d.append('token', token);
			ajax(boardAction, d, function (res) {
				if (!res || !res.success) return;
				var data = res.data;
				if (totalEl) totalEl.textContent = faDigits(data.total);
				Object.keys(cols).forEach(function (key) {
					var col = cols[key];
					if (!col.list) return;
					var before = known(col.list);
					col.list.innerHTML = data.html[key] || '<div class="szp-att-empty">—</div>';
					if (col.n) col.n.textContent = faDigits(data.counts[key]);
					// افراد تازه‌واردشده را برجسته کن.
					col.list.querySelectorAll('.szp-att-person').forEach(function (p) {
						if (!before[p.getAttribute('data-uid')]) {
							p.classList.add('is-new');
							setTimeout(function () { p.classList.remove('is-new'); }, 4000);
						}
					});
				});
			});
		}

		setInterval(refresh, poll);
	}

	/* ---------------- تولید کیوآرکد (صفحهٔ ادمین / چاپ) ---------------- */

	function initQR() {
		document.querySelectorAll('.szp-att-qr[data-url]').forEach(function (el) {
			if (el.dataset.qrDone) return;
			var url = el.getAttribute('data-url');
			if (!url || typeof window.qrcode !== 'function') return;
			el.dataset.qrDone = '1';
			var qr = window.qrcode(0, 'M');
			qr.addData(url);
			qr.make();
			var size = parseInt(el.getAttribute('data-size'), 10) || 8;
			el.innerHTML = qr.createImgTag(size, 8);
			var img = el.querySelector('img');
			if (img) { img.style.width = '100%'; img.style.height = 'auto'; img.style.imageRendering = 'pixelated'; }
		});
	}

	function init() {
		document.querySelectorAll('.szp-att-checkin').forEach(initCheckin);
		document.querySelectorAll('.szp-att-board').forEach(initBoard);
		initQR();
	}
	if (document.readyState !== 'loading') init();
	else document.addEventListener('DOMContentLoaded', init);
})();
