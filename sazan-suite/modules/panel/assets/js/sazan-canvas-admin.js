(function () {
	'use strict';

	function cfg() { return window.SZP_CV_ADMIN || {}; }

	function ajax(action, data, cb) {
		data.append('action', action);
		data.append('nonce', cfg().nonce || '');
		fetch(cfg().ajax || '', { method: 'POST', credentials: 'same-origin', body: data })
			.then(function (r) { return r.json(); })
			.then(cb)
			.catch(function () { cb({ success: false, data: { msg: 'خطای ارتباط با سرور.' } }); });
	}

	function msg(editor, text, kind) {
		var el = editor.querySelector('.szp-cva-msg');
		if (!el) return;
		el.textContent = text || '';
		el.className = 'szp-cva-msg' + (kind ? ' is-' + kind : '');
	}

	/* جمع‌آوری موارد فعلی هر بخش به ترتیب DOM. */
	function collect(editor) {
		var out = {};
		editor.querySelectorAll('.szp-cva-sec').forEach(function (sec) {
			var key = sec.getAttribute('data-section');
			var arr = [];
			sec.querySelectorAll('.szp-cva-item').forEach(function (li) {
				var t = (li.querySelector('.szp-cva-text').textContent || '').trim();
				if (t) arr.push(t);
			});
			out[key] = arr;
		});
		return out;
	}

	function save(editor, after) {
		var d = new FormData();
		d.append('canvas', editor.getAttribute('data-canvas'));
		d.append('user_id', editor.getAttribute('data-user'));
		var items = collect(editor);
		Object.keys(items).forEach(function (sec) {
			items[sec].forEach(function (t) { d.append('items[' + sec + '][]', t); });
			if (!items[sec].length) d.append('items[' + sec + '][]', '');
		});
		msg(editor, 'در حال ذخیره…');
		ajax('szp_canvas_admin_save', d, function (res) {
			if (res && res.success) { msg(editor, (res.data && res.data.msg) || 'ذخیره شد.', 'ok'); }
			else { msg(editor, (res && res.data && res.data.msg) || 'خطا در ذخیره.', 'err'); }
			if (typeof after === 'function') after();
		});
	}

	function clearGroups(editor) {
		editor.querySelectorAll('.szp-cva-item').forEach(function (li) {
			li.className = 'szp-cva-item';
			var b = li.querySelector('.szp-cva-badge');
			if (b) b.textContent = '';
		});
	}

	function runAI(editor, btn) {
		// موارد زندهٔ صفحه به ترتیب DOM؛ اندیس آرایه = شناسهٔ نگاشت بازگشتی.
		var lis = Array.prototype.slice.call(editor.querySelectorAll('.szp-cva-item'));
		var d = new FormData();
		lis.forEach(function (li, i) {
			var t = (li.querySelector('.szp-cva-text').textContent || '').trim();
			d.append('items[' + i + ']', t);
		});

		btn.disabled = true;
		msg(editor, 'در حال تحلیل با هوش مصنوعی…');
		ajax('szp_canvas_ai_dupes', d, function (res) {
			btn.disabled = false;
			if (!res || !res.success) {
				msg(editor, (res && res.data && res.data.msg) || 'خطا در تحلیل.', 'err');
				return;
			}
			clearGroups(editor);
			var groups = (res.data && res.data.groups) || [];
			groups.forEach(function (group, gi) {
				var colorClass = 'szp-cva-g' + (gi % 8);
				group.forEach(function (id, idx) {
					var li = lis[id];
					if (!li) return;
					li.classList.add('szp-cva-dup', colorClass);
					var b = li.querySelector('.szp-cva-badge');
					if (b) b.textContent = 'تکراری ' + toFa(gi + 1);
					// اولین مورد هر گروه نگه داشته می‌شود؛ بقیه برای حذف تیک می‌خورند.
					var cb = li.querySelector('.szp-cva-cb');
					if (cb) cb.checked = idx > 0;
					if (idx === 0) li.classList.add('szp-cva-keep');
				});
			});
			msg(editor, (res.data && res.data.msg) || '', groups.length ? 'ok' : '');
		});
	}

	function toFa(n) { return String(n).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }

	/* تست اتصال API با مقادیر واردشده در فرم تنظیمات. */
	function initTest() {
		var btn = document.querySelector('.szp-ai-test');
		if (!btn) return;
		var out = document.querySelector('.szp-ai-test-msg');
		btn.addEventListener('click', function () {
			var form = btn.closest('form');
			var d = new FormData();
			if (form) {
				var k = form.querySelector('[name="ai_key"]');
				var m = form.querySelector('[name="ai_model"]');
				var b = form.querySelector('[name="ai_base"]');
				if (k) d.append('ai_key', k.value);
				if (m) d.append('ai_model', m.value);
				if (b) d.append('ai_base', b.value);
			}
			btn.disabled = true;
			if (out) { out.textContent = 'در حال تست…'; out.className = 'szp-ai-test-msg'; }
			ajax('szp_ai_test', d, function (res) {
				btn.disabled = false;
				if (!out) return;
				var ok = res && res.success;
				out.textContent = (res && res.data && res.data.msg) || (ok ? 'موفق.' : 'خطا.');
				out.className = 'szp-ai-test-msg ' + (ok ? 'is-ok' : 'is-err');
			});
		});
	}

	function init() {
		initTest();

		var editor = document.querySelector('.szp-cva-editor');
		if (!editor) return;

		editor.addEventListener('click', function (e) {
			if (e.target.closest('.szp-cva-ai')) { runAI(editor, e.target.closest('.szp-cva-ai')); return; }

			if (e.target.closest('.szp-cva-del-sel')) {
				var checked = editor.querySelectorAll('.szp-cva-cb:checked');
				if (!checked.length) { msg(editor, 'موردی برای حذف انتخاب نشده.', 'err'); return; }
				if (!window.confirm('حذف ' + toFa(checked.length) + ' مورد انتخاب‌شده؟')) return;
				checked.forEach(function (cb) {
					var li = cb.closest('.szp-cva-item');
					if (li) li.parentNode.removeChild(li);
				});
				save(editor);
				return;
			}

			if (e.target.closest('.szp-cva-save')) { save(editor); return; }
		});
	}

	if (document.readyState !== 'loading') init();
	else document.addEventListener('DOMContentLoaded', init);
})();
