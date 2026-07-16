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
		// در پورتال فرانت‌اند، نتیجه به‌صورت AJAX مدیریت می‌شود (بدون بارگذاری مجدد صفحه).
		if (window.SZC_PORTAL && typeof window.SZC_PORTAL.handleRes === 'function') {
			window.SZC_PORTAL.handleRes(res, btn);
			return;
		}
		if (res && res.success) {
			var d = res.data || {};
			if (d.redirect) { window.location.href = d.redirect; return; }
			if (d.reload) { if (d.msg) msg(d.msg, 'ok'); setTimeout(function () { window.location.reload(); }, 500); return; }
			msg(d.msg || 'انجام شد', 'ok');
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
		(scope || document).querySelectorAll('[data-cf]').forEach(function (el) {
			d.append('cf[' + el.getAttribute('data-cf') + ']', el.value);
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
			var tplEl = document.querySelector('[data-call-template]');
			if (tplEl) {
				var tv = tplEl.value;
				if (tv === '-1' || tv === '') { d.append('sms', '0'); d.append('template', '0'); }
				else { d.append('sms', '1'); d.append('template', tv); }
			} else {
				d.append('sms', document.querySelector('[data-call-sms]') && document.querySelector('[data-call-sms]').checked ? '1' : '0');
			}
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
		} else if (act === 'quick_call') {
			e.preventDefault();
			d.append('outcome', 'answered');
			d.append('note', '');
			d.append('sms', '1');
			btn.disabled = true;
			ajax('szc_log_call', d, function (r) { handleRes(r, btn); });
		} else if (act === 'merge') {
			e.preventDefault();
			var mm = document.querySelector('[data-merge-mobile]');
			if (!mm || mm.value.trim() === '') { msg('موبایل رکورد دوم را وارد کنید.', 'err'); return; }
			if (!confirm('رکورد دوم در این مخاطب ادغام و سپس حذف شود؟')) return;
			d.append('other_mobile', mm.value);
			btn.disabled = true;
			ajax('szc_merge', d, function (r) { handleRes(r, btn); });
		} else if (act === 'enroll') {
			e.preventDefault();
			var seq = document.querySelector('[data-seq]');
			if (!seq) return;
			d.append('sequence', seq.value);
			btn.disabled = true;
			ajax('szc_enroll', d, function (r) { handleRes(r, btn); });
		} else if (act === 'blacklist') {
			e.preventDefault();
			d.append('op', btn.getAttribute('data-op') || 'add');
			btn.disabled = true;
			ajax('szc_blacklist', d, function (r) { handleRes(r, btn); });
		} else if (act === 'custom_sms') {
			e.preventDefault();
			var ta = document.querySelector('[data-custom-sms]');
			if (!ta || ta.value.trim() === '') { msg('متن پیامک را وارد کنید.', 'err'); return; }
			d.append('text', ta.value);
			btn.disabled = true;
			ajax('szc_custom_sms', d, function (r) { handleRes(r, btn); });
		} else if (act === 'del_contact') {
			e.preventDefault();
			if (!confirm('این مخاطب و همه‌ی سوابقش حذف شود؟')) return;
			btn.disabled = true;
			ajax('szc_del_contact', d, function (r) { handleRes(r, btn); });
		}
	});

	// ===== پیش‌تنظیم‌های پیگیری (Callback) =====
	function pad(n) { return (n < 10 ? '0' : '') + n; }
	function toLocalInput(dt) {
		return dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate()) + 'T' + pad(dt.getHours()) + ':' + pad(dt.getMinutes());
	}
	document.querySelectorAll('[data-preset]').forEach(function (b) {
		b.addEventListener('click', function () {
			var t = b.getAttribute('data-preset');
			var d = new Date();
			d.setSeconds(0, 0);
			if (t === 'tomorrow10') { d.setDate(d.getDate() + 1); d.setHours(10, 0); }
			else if (t === 'today17') { d.setHours(17, 0); }
			else if (t === 'd3') { d.setDate(d.getDate() + 3); d.setHours(10, 0); }
			else if (t === 'week') { d.setDate(d.getDate() + 7); d.setHours(10, 0); }
			var input = document.querySelector('[data-followup-at]');
			if (input) input.value = toLocalInput(d);
		});
	});

	// ===== انتخاب همه در فهرست + نمایش پارامتر اقدام گروهی =====
	var chkAll = document.getElementById('szc-check-all');
	if (chkAll) {
		chkAll.addEventListener('change', function () {
			document.querySelectorAll('.szc-row-check').forEach(function (x) { x.checked = chkAll.checked; });
		});
	}
	var bulkSel = document.querySelector('.szc-bulk-action');
	if (bulkSel) {
		var pmap = { stage: 'p_stage', priority: 'p_priority', tag: 'p_tag', assign: 'p_owner', send: 'p_template', enroll: 'p_sequence' };
		function toggleParams() {
			document.querySelectorAll('.szc-bp').forEach(function (el) { el.style.display = 'none'; });
			var want = pmap[bulkSel.value];
			if (want) {
				var el = document.querySelector('[name="' + want + '"]');
				if (el) el.style.display = '';
			}
		}
		bulkSel.addEventListener('change', toggleParams);
		toggleParams();
	}

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

	// ===== صفحه‌ی تنظیمات: تب‌ها =====
	(function () {
		var tabs = document.querySelectorAll('.szc-tab');
		if (!tabs.length) return;
		function activate(id) {
			tabs.forEach(function (t) { t.classList.toggle('is-active', t.getAttribute('data-tab') === id); });
			document.querySelectorAll('.szc-tabpane').forEach(function (p) {
				p.hidden = (p.getAttribute('data-pane') !== id);
			});
			try { history.replaceState(null, '', '#' + id); } catch (e) {}
		}
		tabs.forEach(function (t) {
			t.addEventListener('click', function () { activate(t.getAttribute('data-tab')); });
		});
		var hash = (location.hash || '').replace('#', '');
		activate(hash && document.querySelector('.szc-tabpane[data-pane="' + hash + '"]') ? hash : tabs[0].getAttribute('data-tab'));
	})();

	// ===== صفحه‌ی ارسال همگانی =====
	(function () {
		var form = document.getElementById('szc-broadcast-form');
		if (!form) return;
		var nonce = form.getAttribute('data-nonce') || '';

		function toggleRadios(name, key) {
			form.querySelectorAll('input[name="' + name + '"]').forEach(function (r) {
				r.addEventListener('change', function () {
					form.querySelectorAll('[data-' + key + ']').forEach(function (el) {
						el.hidden = (el.getAttribute('data-' + key) !== r.value);
					});
				});
			});
		}
		toggleRadios('msg_type', 'msg');
		toggleRadios('when', 'when');

		// شمارش نویسه/پیامک برای متن آزاد.
		var ta = form.querySelector('textarea[name="text"]');
		var cOut = form.querySelector('[data-char-count]');
		var sOut = form.querySelector('[data-sms-count]');
		function fa(n) { return String(n).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[+d]; }); }
		function updateChars() {
			if (!ta) return;
			var len = ta.value.length;
			var fars = /[؀-ۿ]/.test(ta.value);
			var per = fars ? 70 : 160;
			var parts = len === 0 ? 1 : Math.ceil(len / per);
			if (cOut) cOut.textContent = fa(len);
			if (sOut) sOut.textContent = fa(parts);
		}
		if (ta) { ta.addEventListener('input', updateChars); updateChars(); }

		// شمارش زنده‌ی گیرندگان بر اساس فیلترها.
		var out = form.querySelector('[data-count-out]');
		var timer = null;
		function refreshCount() {
			if (!out) return;
			out.textContent = '…';
			var d = new FormData();
			d.append('action', 'szc_broadcast_count');
			d.append('nonce', nonce);
			['b_stage', 'b_priority', 'b_owner', 'b_tag', 'b_due', 'b_s'].forEach(function (n) {
				var el = form.querySelector('[name="' + n + '"]');
				if (el) d.append(n, el.value);
			});
			fetch(CFG.ajax || '', { method: 'POST', credentials: 'same-origin', body: d })
				.then(function (r) { return r.json(); })
				.then(function (r) { out.textContent = (r && r.success && r.data) ? r.data.fa : '—'; })
				.catch(function () { out.textContent = '—'; });
		}
		form.querySelectorAll('[name^="b_"]').forEach(function (el) {
			var ev = (el.tagName === 'SELECT') ? 'change' : 'input';
			el.addEventListener(ev, function () { clearTimeout(timer); timer = setTimeout(refreshCount, 300); });
		});
		refreshCount();
	})();

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
				.then(function (r) { out.textContent = (r && r.data && r.data.msg) || (r && r.success ? 'ارسال شد' : 'خطا'); })
				.catch(function () { out.textContent = 'خطای ارتباط.'; });
		});
	}
})();
