(function () {
	'use strict';

	var FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

	function faDigits(s) { return String(s).replace(/[0-9]/g, function (d) { return FA[d]; }); }
	function latin(s) { return String(s).replace(/[۰-۹]/g, function (d) { return FA.indexOf(d); }).replace(/[٠-٩]/g, function (d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); }); }
	function toNumber(s) { var t = latin(s).replace(/[^\d.]/g, ''); return t === '' ? 0 : parseFloat(t); }
	function group(n) { return faDigits(Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '،')); }

	var STATUS = {
		beyond:  { label: 'فراتر از تارگت', color: '#0ea5e9', emoji: '🚀' },
		success: { label: 'موفق', color: '#16a34a', emoji: '🎯' },
		improve: { label: 'قابل بهبود', color: '#f59e0b', emoji: '📈' },
		ontrack: { label: 'در مسیر', color: '#ef4444', emoji: '🧭' },
		pending: { label: 'در انتظار نتیجه', color: '#9ca3af', emoji: '⏳' }
	};

	function computeStatus(target, result, near) {
		if (!target || target <= 0) return 'pending';
		if (result > target) return 'beyond';
		if (result === target) return 'success';
		if (result >= target * near) return 'improve';
		return 'ontrack';
	}

	function ajax(action, data, cb) {
		data.append('action', action);
		data.append('nonce', (window.SZP_FRONT && SZP_FRONT.nonce) || '');
		fetch((window.SZP_FRONT && SZP_FRONT.ajax) || '', {
			method: 'POST', credentials: 'same-origin', body: data
		}).then(function (r) { return r.json(); })
			.then(cb)
			.catch(function () { cb({ success: false, data: { msg: 'خطای ارتباط با سرور.' } }); });
	}

	function initEval(root) {
		if (root.dataset.evInit) return;
		root.dataset.evInit = '1';

		var currency = root.getAttribute('data-currency') || '';
		var near = parseFloat(root.getAttribute('data-near')) || 0.85;

		if (root.getAttribute('data-can-edit') === '1') {
			initEditField(root, currency, {
				btn: 'szp-ev-edit-target', cell: 'szp-ev-targetcell', val: 'szp-ev-target-val',
				attr: 'data-target', action: 'szp_eval_edit_target', allowZero: false, label: 'تارگت'
			});
		}
		if (root.getAttribute('data-can-edit-result') === '1') {
			initEditField(root, currency, {
				btn: 'szp-ev-edit-result', cell: 'szp-ev-resultcell', val: 'szp-ev-result-val',
				attr: 'data-result', action: 'szp_eval_edit_result', allowZero: true, label: 'نتیجه'
			});
		}

		var form = root.querySelector('.szp-ev-form');
		if (!form) return;
		var mode = form.getAttribute('data-mode');
		var target = parseFloat(form.getAttribute('data-target')) || 0;
		var input = form.querySelector('.szp-ev-amount');
		var btn = form.querySelector('.szp-ev-submit');
		var preview = form.querySelector('.szp-ev-preview');
		var msg = form.querySelector('.szp-ev-msg');
		var noteEl = form.querySelector('.szp-ev-note');

		function renderPreview() {
			var val = toNumber(input.value);
			if (!val) { preview.textContent = ''; preview.removeAttribute('style'); return; }
			if (mode === 'result' && target > 0) {
				var pct = Math.round(val / target * 100);
				var st = STATUS[computeStatus(target, val, near)];
				preview.innerHTML = 'نتیجه: <b>' + group(val) + ' ' + currency + '</b> — تحقق ' +
					faDigits(pct) + '٪ — <span class="szp-ev-pv-badge">' + st.emoji + ' ' + st.label + '</span>';
				preview.style.setProperty('--c', st.color);
				preview.className = 'szp-ev-preview is-show has-status';
			} else {
				preview.innerHTML = 'تارگت: <b>' + group(val) + ' ' + currency + '</b>';
				preview.className = 'szp-ev-preview is-show';
			}
		}

		input.addEventListener('input', renderPreview);

		btn.addEventListener('click', function () {
			if (btn.disabled) return;
			var val = toNumber(input.value);
			if (!val || val <= 0) { showMsg('یک عدد معتبر وارد کنید.', 'err'); return; }
			if (mode === 'result' && val > target * 3) {
				if (!confirm('نتیجه‌ای که وارد کردید چند برابر تارگت است. مطمئنید؟')) return;
			}
			btn.disabled = true;
			showMsg('در حال ثبت…', '');
			var d = new FormData();
			d.append('amount', String(val));
			if (mode === 'result' && noteEl) d.append('note', noteEl.value);
			ajax(mode === 'result' ? 'szp_eval_result' : 'szp_eval_target', d, function (res) {
				if (res && res.success) {
					showMsg((res.data && res.data.msg) || 'ثبت شد ✓', 'ok');
					setTimeout(function () { window.location.reload(); }, 700);
				} else {
					btn.disabled = false;
					showMsg((res && res.data && res.data.msg) || 'خطا در ثبت.', 'err');
				}
			});
		});

		function showMsg(text, kind) {
			if (!msg) return;
			msg.textContent = text;
			msg.className = 'szp-ev-msg is-show' + (kind ? ' is-' + kind : '');
		}
	}

	/* ویرایش درجای تارگت/نتیجه‌ی هفته‌های ثبت‌شده در جدول تاریخچه */
	function initEditField(root, currency, cfg) {
		root.querySelectorAll('.' + cfg.btn).forEach(function (btn) {
			btn.addEventListener('click', function () {
				var cell = btn.closest('.' + cfg.cell);
				if (!cell || cell.querySelector('.szp-ev-edit-box')) return;

				var week = btn.getAttribute('data-week');
				var cur = parseFloat(btn.getAttribute(cfg.attr)) || 0;
				var valSpan = cell.querySelector('.' + cfg.val);

				btn.style.display = 'none';
				if (valSpan) valSpan.style.display = 'none';

				var box = document.createElement('div');
				box.className = 'szp-ev-edit-box';

				var inp = document.createElement('input');
				inp.type = 'text';
				inp.inputMode = 'numeric';
				inp.className = 'szp-ev-edit-input';
				inp.placeholder = cfg.label;
				inp.value = cur > 0 ? group(cur) : '';

				var save = document.createElement('button');
				save.type = 'button';
				save.className = 'szp-ev-edit-save';
				save.textContent = 'ذخیره';

				var cancel = document.createElement('button');
				cancel.type = 'button';
				cancel.className = 'szp-ev-edit-cancel';
				cancel.textContent = 'انصراف';

				var note = document.createElement('span');
				note.className = 'szp-ev-edit-note';

				box.appendChild(inp);
				box.appendChild(save);
				box.appendChild(cancel);
				box.appendChild(note);
				cell.appendChild(box);
				inp.focus();

				function close() {
					box.remove();
					btn.style.display = '';
					if (valSpan) valSpan.style.display = '';
				}

				function invalid() {
					var raw = inp.value.trim();
					if (raw === '') return true;
					var v = toNumber(inp.value);
					return cfg.allowZero ? (v < 0) : (!v || v <= 0);
				}

				inp.addEventListener('input', function () {
					var raw = inp.value.trim();
					var v = toNumber(inp.value);
					note.textContent = raw === '' ? '' : group(v) + ' ' + currency;
				});
				inp.addEventListener('keydown', function (e) {
					if (e.key === 'Enter') { e.preventDefault(); save.click(); }
					else if (e.key === 'Escape') { close(); }
				});
				cancel.addEventListener('click', close);

				save.addEventListener('click', function () {
					if (invalid()) { note.textContent = 'یک عدد معتبر وارد کنید.'; return; }
					var v = toNumber(inp.value);
					save.disabled = true;
					cancel.disabled = true;
					note.textContent = 'در حال ذخیره…';
					var d = new FormData();
					d.append('week', String(week));
					d.append('amount', String(v));
					ajax(cfg.action, d, function (res) {
						if (res && res.success) {
							note.textContent = 'ذخیره شد ✓';
							setTimeout(function () { window.location.reload(); }, 600);
						} else {
							save.disabled = false;
							cancel.disabled = false;
							note.textContent = (res && res.data && res.data.msg) || 'خطا در ویرایش.';
						}
					});
				});
			});
		});
	}

	function init() { document.querySelectorAll('.szp-eval').forEach(initEval); }
	if (document.readyState !== 'loading') init();
	else document.addEventListener('DOMContentLoaded', init);
})();
