(function () {
	'use strict';

	var CFG = window.SZC_ADMIN || {};

	function ajax(action, data, cb) {
		data.append('action', action);
		data.append('nonce', CFG.nonce || '');
		fetch(CFG.ajax || '', { method: 'POST', credentials: 'same-origin', body: data })
			.then(function (r) { return r.json(); })
			.then(cb)
			.catch(function () { cb({ success: false, data: { msg: 'خطای ارتباط با سرور.' } }); });
	}

	function msg(text, kind) {
		var el = document.querySelector('.szc-msg');
		if (!el) { if (kind === 'err') alert(text); return; }
		el.textContent = text;
		el.className = 'szc-msg is-show' + (kind ? ' is-' + kind : '');
	}

	function handleRes(res, btn) {
		if (res && res.success) {
			var d = res.data || {};
			if (d.redirect) { window.location.href = d.redirect; return; }
			if (d.reload) { if (d.msg) msg(d.msg, 'ok'); setTimeout(function () { window.location.reload(); }, 500); return; }
			msg(d.msg || 'انجام شد ✓', 'ok');
			if (btn) btn.disabled = false;
		} else {
			msg((res && res.data && res.data.msg) || 'خطا رخ داد.', 'err');
			if (btn) btn.disabled = false;
		}
	}

	function contactId() {
		var s = document.querySelector('.szc-single');
		return s ? s.getAttribute('data-contact') : '0';
	}

	function collectFields(scope) {
		var d = new FormData();
		(scope || document).querySelectorAll('[data-f]').forEach(function (el) {
			d.append(el.getAttribute('data-f'), el.value);
		});
		return d;
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-szc-act]');
		if (!btn || btn.tagName === 'SELECT') return;
		var act = btn.getAttribute('data-szc-act');
		var cid = contactId();
		var d = new FormData();
		d.append('contact', cid);

		if (act === 'save_contact') {
			e.preventDefault();
			btn.disabled = true;
			var fd = collectFields(document.querySelector('.szc-single'));
			fd.append('contact', cid);
			ajax('szc_save_contact', fd, function (r) { handleRes(r, btn); });
		} else if (act === 'add_contact') {
			e.preventDefault();
			var form = document.querySelector('.szc-add-form');
			var fd2 = collectFields(form);
			if (!(fd2.get('mobile') || '').trim()) { msg('موبایل را وارد کنید.', 'err'); return; }
			btn.disabled = true;
			ajax('szc_add_contact', fd2, function (r) { handleRes(r, btn); });
		} else if (act === 'add_note') {
			e.preventDefault();
			var nb = document.querySelector('[data-note-body]');
			if (!nb || nb.value.trim() === '') { msg('یادداشت خالی است.', 'err'); return; }
			d.append('body', nb.value);
			btn.disabled = true;
			ajax('szc_add_note', d, function (r) { handleRes(r, btn); });
		} else if (act === 'log_call') {
			e.preventDefault();
			d.append('outcome', (document.querySelector('[data-call-outcome]') || {}).value || 'answered');
			d.append('note', (document.querySelector('[data-call-note]') || {}).value || '');
			d.append('sms', document.querySelector('[data-call-sms]') && document.querySelector('[data-call-sms]').checked ? '1' : '0');
			btn.disabled = true;
			ajax('szc_log_call', d, function (r) { handleRes(r, btn); });
		} else if (act === 'add_followup') {
			e.preventDefault();
			var at = (document.querySelector('[data-followup-at]') || {}).value || '';
			if (!at) { msg('زمان پیگیری را انتخاب کنید.', 'err'); return; }
			d.append('at', at);
			d.append('note', (document.querySelector('[data-followup-note]') || {}).value || '');
			btn.disabled = true;
			ajax('szc_add_followup', d, function (r) { handleRes(r, btn); });
		} else if (act === 'done_followup') {
			e.preventDefault();
			d.append('id', btn.getAttribute('data-id'));
			ajax('szc_done_followup', d, function (r) { handleRes(r, btn); });
		} else if (act === 'del_note' || act === 'del_activity') {
			e.preventDefault();
			if (!confirm('این مورد حذف شود؟')) return;
			d.append('id', btn.getAttribute('data-id'));
			ajax('szc_' + act, d, function (r) { handleRes(r, btn); });
		} else if (act === 'send_sms' || act === 'schedule_sms') {
			e.preventDefault();
			var tpl = document.querySelector('[data-sms-template]');
			if (!tpl) return;
			d.append('template', tpl.value);
			btn.disabled = true;
			ajax('szc_' + act, d, function (r) { handleRes(r, btn); });
		} else if (act === 'del_contact') {
			e.preventDefault();
			if (!confirm('این مخاطب و همه‌ی سوابقش حذف شود؟')) return;
			btn.disabled = true;
			ajax('szc_del_contact', d, function (r) { handleRes(r, btn); });
		}
	});

	// تغییر سریع مرحله/اولویت/لغو پیامک (روی change).
	document.addEventListener('change', function (e) {
		var el = e.target.closest('[data-szc-act="set_field"]');
		if (!el) return;
		var d = new FormData();
		d.append('contact', contactId());
		d.append('field', el.getAttribute('data-field'));
		d.append('value', el.type === 'checkbox' ? (el.checked ? '1' : '0') : el.value);
		ajax('szc_set_field', d, function (r) { handleRes(r); });
	});

	// تست پیامک در صفحه‌ی تنظیمات.
	var testBtn = document.getElementById('szc-test-sms');
	if (testBtn) {
		testBtn.addEventListener('click', function () {
			var num = (document.getElementById('szc-test-num') || {}).value || '';
			var out = document.getElementById('szc-test-msg');
			out.textContent = 'در حال ارسال…';
			var d = new FormData();
			d.append('to', num);
			d.append('nonce', testBtn.getAttribute('data-nonce'));
			d.append('action', 'szc_test_sms');
			fetch(CFG.ajax || '', { method: 'POST', credentials: 'same-origin', body: d })
				.then(function (r) { return r.json(); })
				.then(function (r) { out.textContent = (r && r.data && r.data.msg) || (r && r.success ? 'ارسال شد ✓' : 'خطا'); })
				.catch(function () { out.textContent = 'خطای ارتباط.'; });
		});
	}
})();
