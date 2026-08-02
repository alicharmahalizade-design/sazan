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

	function setBusy(btn, on) {
		if (!btn) return;
		btn.disabled = !!on;
		if (on) btn.setAttribute('aria-busy', 'true');
		else btn.removeAttribute('aria-busy');
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
			setBusy(btn, false);
		} else {
			msg((res && res.data && res.data.msg) || 'خطا رخ داد.', 'err');
			setBusy(btn, false);
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
			setBusy(btn, true);
			var fd = collectFields(document.querySelector('.szc-single'));
			fd.append('contact', cid);
			ajax('szc_save_contact', fd, function (r) { handleRes(r, btn); });
		} else if (act === 'add_contact') {
			e.preventDefault();
			var form = document.querySelector('.szc-add-form');
			var fd2 = collectFields(form);
			if (!(fd2.get('mobile') || '').trim()) { msg('موبایل را وارد کنید.', 'err'); return; }
			setBusy(btn, true);
			ajax('szc_add_contact', fd2, function (r) { handleRes(r, btn); });
		} else if (act === 'add_note') {
			e.preventDefault();
			var nb = document.querySelector('[data-note-body]');
			if (!nb || nb.value.trim() === '') { msg('یادداشت خالی است.', 'err'); return; }
			d.append('body', nb.value);
			setBusy(btn, true);
			ajax('szc_add_note', d, function (r) { handleRes(r, btn); });
		} else if (act === 'log_call') {
			e.preventDefault();
			var callOutcome = (document.querySelector('[data-call-outcome]') || {}).value || 'answered';
			var callNote = (document.querySelector('[data-call-note]') || {}).value || '';
			if ((callOutcome === 'not_interested' || callOutcome === 'wrong') && !callNote.trim()) { msg('برای این نتیجه ثبت توضیح الزامی است.', 'err'); return; }
			d.append('outcome', callOutcome);
			d.append('note', callNote);
			d.append('followup_at', (document.querySelector('[data-call-followup-at]') || {}).value || '');
			d.append('followup_note', (document.querySelector('[data-call-followup-note]') || {}).value || '');
			var tplEl = document.querySelector('[data-call-template]');
			if (tplEl) {
				var tv = tplEl.value;
				if (tv === '-1' || tv === '') { d.append('sms', '0'); d.append('template', '0'); }
				else { d.append('sms', '1'); d.append('template', tv); }
			} else {
				d.append('sms', document.querySelector('[data-call-sms]') && document.querySelector('[data-call-sms]').checked ? '1' : '0');
			}
			setBusy(btn, true);
			ajax('szc_log_call', d, function (r) { handleRes(r, btn); });
		} else if (act === 'add_followup') {
			e.preventDefault();
			var at = (document.querySelector('[data-followup-at]') || {}).value || '';
			if (!at) { msg('زمان پیگیری را انتخاب کنید.', 'err'); return; }
			d.append('at', at);
			d.append('note', (document.querySelector('[data-followup-note]') || {}).value || '');
			setBusy(btn, true);
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
			setBusy(btn, true);
			ajax('szc_' + act, d, function (r) { handleRes(r, btn); });
		} else if (act === 'quick_call') {
			e.preventDefault();
			d.append('outcome', 'answered');
			d.append('note', '');
			d.append('sms', '1');
			setBusy(btn, true);
			ajax('szc_log_call', d, function (r) { handleRes(r, btn); });
		} else if (act === 'merge') {
			e.preventDefault();
			var mm = document.querySelector('[data-merge-mobile]');
			if (!mm || mm.value.trim() === '') { msg('موبایل رکورد دوم را وارد کنید.', 'err'); return; }
			if (!confirm('رکورد دوم در این مخاطب ادغام و سپس حذف شود؟')) return;
			d.append('other_mobile', mm.value);
			setBusy(btn, true);
			ajax('szc_merge', d, function (r) { handleRes(r, btn); });
		} else if (act === 'enroll') {
			e.preventDefault();
			var seq = document.querySelector('[data-seq]');
			if (!seq) return;
			d.append('sequence', seq.value);
			setBusy(btn, true);
			ajax('szc_enroll', d, function (r) { handleRes(r, btn); });
		} else if (act === 'blacklist') {
			e.preventDefault();
			d.append('op', btn.getAttribute('data-op') || 'add');
			setBusy(btn, true);
			ajax('szc_blacklist', d, function (r) { handleRes(r, btn); });
		} else if (act === 'custom_sms') {
			e.preventDefault();
			var ta = document.querySelector('[data-custom-sms]');
			if (!ta || ta.value.trim() === '') { msg('متن پیامک را وارد کنید.', 'err'); return; }
			d.append('text', ta.value);
			setBusy(btn, true);
			ajax('szc_custom_sms', d, function (r) { handleRes(r, btn); });
		} else if (act === 'del_contact') {
			e.preventDefault();
			if (!confirm('این مخاطب و همه‌ی سوابقش حذف شود؟')) return;
			setBusy(btn, true);
			ajax('szc_del_contact', d, function (r) { handleRes(r, btn); });
		} else if (act === 'claim_lead') {
			e.preventDefault();
			d.set('contact', btn.getAttribute('data-id') || '0');
			setBusy(btn, true);
			ajax('szc_claim_lead', d, function (r) { handleRes(r, btn); });
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

	// ===== مرکز تنظیمات: ناوبری، جستجو، اعتبارسنجی و تغییرات ذخیره‌نشده =====
	(function () {
		var center = document.querySelector('.szc-settings-center');
		if (!center) return;
		var nav = Array.prototype.slice.call(center.querySelectorAll('[data-settings-nav]'));
		var panes = Array.prototype.slice.call(center.querySelectorAll('[data-settings-pane]'));
		var search = center.querySelector('[data-settings-search]');
		var clear = center.querySelector('[data-settings-search-clear]');
		var empty = center.querySelector('[data-settings-search-empty]');
		var active = 'overview';
		var dirtyForms = new Set();

		function validSection(id) {
			return panes.some(function (pane) { return pane.getAttribute('data-settings-pane') === id; });
		}
		function activate(id, focusContent) {
			if (!validSection(id)) id = 'overview';
			active = id;
			nav.forEach(function (button) {
				var on = button.getAttribute('data-settings-nav') === id;
				button.classList.toggle('is-active', on);
				button.setAttribute('aria-current', on ? 'page' : 'false');
			});
			panes.forEach(function (pane) {
				pane.hidden = pane.getAttribute('data-settings-pane') !== id;
			});
			try { history.replaceState(null, '', '#' + id); } catch (e) {}
			if (focusContent) {
				var heading = center.querySelector('[data-settings-pane="' + id + '"] h2');
				if (heading) { heading.setAttribute('tabindex', '-1'); heading.focus({ preventScroll: true }); }
			}
		}
		nav.forEach(function (button) {
			button.addEventListener('click', function () {
				if (search && search.value) { search.value = ''; runSearch(); }
				activate(button.getAttribute('data-settings-nav'), true);
			});
		});
		center.querySelectorAll('[data-settings-jump]').forEach(function (button) {
			button.addEventListener('click', function () { activate(button.getAttribute('data-settings-jump'), true); });
		});

		function normalize(value) {
			return String(value || '').toLocaleLowerCase('fa').replace(/\s+/g, ' ').trim();
		}
		function runSearch() {
			var query = normalize(search ? search.value : '');
			if (clear) clear.hidden = !query;
			if (!query) {
				panes.forEach(function (pane) {
					pane.hidden = pane.getAttribute('data-settings-pane') !== active;
					pane.querySelectorAll('[data-settings-item]').forEach(function (item) { item.hidden = false; });
				});
				nav.forEach(function (button) { button.hidden = false; });
				if (empty) empty.hidden = true;
				return;
			}
			var found = 0;
			panes.forEach(function (pane) {
				var items = Array.prototype.slice.call(pane.querySelectorAll('[data-settings-item]')).filter(function (item) {
					return !item.parentElement.closest('[data-settings-item]');
				});
				var paneMatch = false;
				items.forEach(function (item) {
					var match = normalize(item.getAttribute('data-settings-item') + ' ' + item.textContent).indexOf(query) !== -1;
					item.hidden = !match;
					if (match) { paneMatch = true; found++; }
				});
				if (!items.length && normalize(pane.textContent).indexOf(query) !== -1) { paneMatch = true; found++; }
				pane.hidden = !paneMatch;
			});
			nav.forEach(function (button) {
				var target = center.querySelector('[data-settings-pane="' + button.getAttribute('data-settings-nav') + '"]');
				button.hidden = !target || target.hidden;
			});
			if (empty) empty.hidden = found > 0;
		}
		if (search) {
			var searchTimer = null;
			search.addEventListener('input', function () {
				clearTimeout(searchTimer);
				searchTimer = setTimeout(runSearch, 120);
			});
		}
		if (clear) clear.addEventListener('click', function () { search.value = ''; search.focus(); runSearch(); });

		center.querySelectorAll('[data-toggle-secret]').forEach(function (button) {
			button.addEventListener('click', function () {
				var input = document.getElementById(button.getAttribute('aria-controls'));
				if (!input) return;
				var showing = input.type === 'text';
				input.type = showing ? 'password' : 'text';
				button.textContent = showing ? 'نمایش' : 'پنهان';
				button.setAttribute('aria-pressed', showing ? 'false' : 'true');
			});
		});

		center.querySelectorAll('[data-settings-form]').forEach(function (form) {
			function markDirty() {
				dirtyForms.add(form);
				form.classList.add('is-dirty');
				var label = form.querySelector('[data-unsaved-label]');
				if (label) label.textContent = 'تغییر ذخیره‌نشده دارید.';
			}
			form.addEventListener('input', markDirty);
			form.addEventListener('change', markDirty);
			form.addEventListener('submit', function () {
				dirtyForms.delete(form);
				var button = form.querySelector('button[type="submit"]');
				if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); button.textContent = 'در حال ذخیره…'; }
			});
			form.querySelectorAll('input,select,textarea').forEach(function (field) {
				field.addEventListener('blur', function () {
					var error = field.parentElement.querySelector('.szc-inline-error');
					if (!field.checkValidity()) {
						field.setAttribute('aria-invalid', 'true');
						field.classList.add('is-invalid');
						if (!error) {
							error = document.createElement('p');
							error.className = 'szc-inline-error';
							error.setAttribute('role', 'alert');
							field.insertAdjacentElement('afterend', error);
						}
						error.textContent = field.validationMessage || 'مقدار این فیلد معتبر نیست.';
					} else {
						field.removeAttribute('aria-invalid');
						field.classList.remove('is-invalid');
						if (error) error.remove();
					}
				});
			});
		});
		var smsEnabled = center.querySelector('#szc-sms-enabled');
		var smsKey = center.querySelector('#szc-sms-key');
		var smsLine = center.querySelector('#szc-sms-originator');
		function syncSmsRequired() {
			var required = !!(smsEnabled && smsEnabled.checked);
			if (smsKey) smsKey.required = required;
			if (smsLine) smsLine.required = required;
		}
		if (smsEnabled) { smsEnabled.addEventListener('change', syncSmsRequired); syncSmsRequired(); }
		var sendFrom = center.querySelector('#szc-send-from');
		var sendTo = center.querySelector('#szc-send-to');
		function validateSendHours() {
			if (!sendFrom || !sendTo) return;
			var invalid = Number(sendFrom.value) >= Number(sendTo.value);
			sendTo.setCustomValidity(invalid ? 'ساعت پایان باید بعد از ساعت شروع باشد.' : '');
		}
		if (sendFrom && sendTo) {
			sendFrom.addEventListener('change', validateSendHours);
			sendTo.addEventListener('change', validateSendHours);
			validateSendHours();
		}
		window.addEventListener('beforeunload', function (event) {
			if (!dirtyForms.size) return;
			event.preventDefault();
			event.returnValue = '';
		});
		var resetForm = center.querySelector('[data-settings-reset-form]');
		if (resetForm) {
			resetForm.addEventListener('submit', function (event) {
				if (!confirm('تنظیمات این بخش به مقدار اولیه برگردد؟ مخاطبان حذف نمی‌شوند، اما این تغییر قابل بازگشت خودکار نیست.')) event.preventDefault();
			});
		}
		var initial = (location.hash || '').replace('#', '');
		activate(validSection(initial) ? initial : 'overview', false);
	})();

	// ===== صفحه‌ی تنظیمات قدیمی: تب‌ها =====
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
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-talk-copy]');
		if (!btn) return;
		var box = btn.closest('.szc-talk__opening');
		var text = box && box.querySelector('[data-talk-copy-text]');
		if (!text) return;
		function done() {
			var old = btn.textContent;
			btn.textContent = 'کپی شد ✓';
			setTimeout(function () { btn.textContent = old; }, 1600);
		}
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text.textContent.trim()).then(done);
		} else {
			var range = document.createRange();
			range.selectNodeContents(text);
			var selection = window.getSelection();
			selection.removeAllRanges();
			selection.addRange(range);
		}
	});

	// تست پیامک در صفحه‌ی تنظیمات.
	var testBtn = document.getElementById('szc-test-sms');
	if (testBtn) {
		testBtn.addEventListener('click', function () {
			var num = (document.getElementById('szc-test-num') || {}).value || '';
			var out = document.getElementById('szc-test-msg');
			if (!num.trim()) { out.textContent = 'ابتدا شماره دریافت‌کننده را وارد کنید.'; out.className = 'is-error'; return; }
			out.textContent = 'در حال بررسی و ارسال…';
			out.className = '';
			testBtn.disabled = true;
			testBtn.setAttribute('aria-busy', 'true');
			var d = new FormData();
			d.append('to', num);
			d.append('nonce', testBtn.getAttribute('data-nonce'));
			d.append('action', 'szc_test_sms');
			fetch(CFG.ajax || '', { method: 'POST', credentials: 'same-origin', body: d })
				.then(function (r) { return r.json(); })
				.then(function (r) {
					out.textContent = (r && r.data && r.data.msg) || (r && r.success ? 'اتصال موفق است و پیام ارسال شد.' : 'ارسال ناموفق بود.');
					out.className = r && r.success ? 'is-success' : 'is-error';
				})
				.catch(function () { out.textContent = 'ارتباط با سرور برقرار نشد؛ دوباره تلاش کنید.'; out.className = 'is-error'; })
				.then(function () { testBtn.disabled = false; testBtn.removeAttribute('aria-busy'); });
		});
	}
})();
