(function () {
	'use strict';

	var FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
	function faDigits(s) { return String(s).replace(/[0-9]/g, function (d) { return FA[d]; }); }

	var J_MONTHS = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

	/** Gregorian (y,m,d) -> Jalali [jy,jm,jd]. Port of szp_g2j(). */
	function g2j(gy, gm, gd) {
		var gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
		var gy2 = (gm > 2) ? (gy + 1) : gy;
		var days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100)
			+ Math.floor((gy2 + 399) / 400) + gd + gdm[gm - 1];
		var jy = -1595 + (33 * Math.floor(days / 12053));
		days %= 12053;
		jy += 4 * Math.floor(days / 1461);
		days %= 1461;
		if (days > 365) { jy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
		var jm, jd;
		if (days < 186) { jm = 1 + Math.floor(days / 31); jd = 1 + (days % 31); }
		else { jm = 7 + Math.floor((days - 186) / 30); jd = 1 + ((days - 186) % 30); }
		return [jy, jm, jd];
	}

	function jalaliFromISO(iso) {
		var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || '');
		if (!m) return '';
		var j = g2j(parseInt(m[1], 10), parseInt(m[2], 10), parseInt(m[3], 10));
		return faDigits(j[2] + ' ' + J_MONTHS[j[1]] + ' ' + j[0]);
	}

	/* ---- Jalali -> Gregorian + calendar picker ---- */
	var J_DOW = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
	function pad2(n) { return (n < 10 ? '0' : '') + n; }
	function j2g(jy, jm, jd) {
		jy += 1595;
		var days = -355668 + (365 * jy) + (Math.floor(jy / 33) * 8) + Math.floor(((jy % 33) + 3) / 4)
			+ jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
		var gy = 400 * Math.floor(days / 146097); days %= 146097;
		if (days > 36524) { gy += 100 * Math.floor(--days / 36524); days %= 36524; if (days >= 365) days++; }
		gy += 4 * Math.floor(days / 1461); days %= 1461;
		if (days > 365) { gy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
		var gd = days + 1;
		var leap = ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0));
		var sal = [0, 31, leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
		var gm;
		for (gm = 1; gm <= 12; gm++) { if (gd <= sal[gm]) break; gd -= sal[gm]; }
		return [gy, gm, gd];
	}
	function jDays(jy, jm) {
		if (jm <= 6) return 31;
		if (jm <= 11) return 30;
		var g = j2g(jy, 12, 30), b = g2j(g[0], g[1], g[2]);
		return (b[0] === jy && b[1] === 12 && b[2] === 30) ? 30 : 29;
	}

	var sessPop = null;
	function closeSessPop() { if (sessPop) { sessPop.remove(); sessPop = null; } }
	function openDatePicker(anchor, initISO, onPick) {
		closeSessPop();
		var now = new Date();
		var today = g2j(now.getFullYear(), now.getMonth() + 1, now.getDate());
		var sel = null;
		var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(initISO || '');
		if (m) sel = g2j(parseInt(m[1], 10), parseInt(m[2], 10), parseInt(m[3], 10));
		var view = sel ? [sel[0], sel[1]] : [today[0], today[1]];
		var pop = document.createElement('div');
		pop.className = 'szp-jp-pop';
		sessPop = pop;
		function render() {
			var jy = view[0], jm = view[1];
			var g1 = j2g(jy, jm, 1);
			var dow = (new Date(g1[0], g1[1] - 1, g1[2]).getDay() + 1) % 7;
			var total = jDays(jy, jm);
			var h = '<div class="szp-jp-nav"><button type="button" class="szp-jp-pm">‹</button>'
				+ '<span>' + J_MONTHS[jm] + ' ' + faDigits(jy) + '</span>'
				+ '<button type="button" class="szp-jp-nm">›</button></div><div class="szp-jp-grid">';
			for (var d = 0; d < 7; d++) h += '<span class="szp-jp-dow">' + J_DOW[d] + '</span>';
			for (var i = 0; i < dow; i++) h += '<span></span>';
			for (var day = 1; day <= total; day++) {
				var on = sel && sel[0] === jy && sel[1] === jm && sel[2] === day;
				h += '<button type="button" class="szp-jp-day' + (on ? ' on' : '') + '" data-d="' + day + '">' + faDigits(day) + '</button>';
			}
			h += '</div><div class="szp-jp-foot"><button type="button" class="szp-jp-ok">تأیید</button>'
				+ '<button type="button" class="szp-jp-clear">پاک کردن</button></div>';
			pop.innerHTML = h;
		}
		render();
		document.body.appendChild(pop);
		var r = anchor.getBoundingClientRect();
		pop.style.position = 'absolute';
		pop.style.top = (window.scrollY + r.bottom + 6) + 'px';
		pop.style.left = (window.scrollX + r.left) + 'px';
		pop.addEventListener('click', function (e) {
			var t = e.target;
			if (t.classList.contains('szp-jp-pm')) { if (--view[1] < 1) { view[1] = 12; view[0]--; } render(); }
			else if (t.classList.contains('szp-jp-nm')) { if (++view[1] > 12) { view[1] = 1; view[0]++; } render(); }
			else if (t.classList.contains('szp-jp-day')) { sel = [view[0], view[1], parseInt(t.getAttribute('data-d'), 10)]; Array.prototype.forEach.call(pop.querySelectorAll('.szp-jp-day'), function (x) { x.classList.remove('on'); }); t.classList.add('on'); }
			else if (t.classList.contains('szp-jp-clear')) { onPick(''); closeSessPop(); }
			else if (t.classList.contains('szp-jp-ok')) { if (!sel) { closeSessPop(); return; } var g = j2g(sel[0], sel[1], sel[2]); onPick(g[0] + '-' + pad2(g[1]) + '-' + pad2(g[2])); closeSessPop(); }
		});
	}
	document.addEventListener('mousedown', function (e) { if (sessPop && !sessPop.contains(e.target) && !e.target.closest('.szp-jp-disp')) closeSessPop(); });
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSessPop(); });

	function ajax(action, data, cb) {
		data.append('action', action);
		data.append('nonce', (window.SZP_FRONT && SZP_FRONT.nonce) || '');
		fetch((window.SZP_FRONT && SZP_FRONT.ajax) || '', {
			method: 'POST', credentials: 'same-origin', body: data
		}).then(function (r) { return r.json(); })
			.then(cb)
			.catch(function () { cb({ success: false, data: { msg: 'خطای ارتباط با سرور.' } }); });
	}

	function initSess(root) {
		if (root.dataset.sessInit) return;
		root.dataset.sessInit = '1';

		var form = root.querySelector('.szp-sess-form');
		var toast = root.querySelector('.szp-sess-toast');
		if (!form) return;

		var msg = form.querySelector('.szp-sess-msg');
		var saveBtn = form.querySelector('[data-act="save"]');
		var resetBtn = form.querySelector('[data-act="reset"]');
		var jalaliEl = form.querySelector('[data-jalali]');
		var dateEl = form.querySelector('[data-f="date"]');
		var dispEl = form.querySelector('.szp-jp-disp');

		function fld(name) { return form.querySelector('[data-f="' + name + '"]'); }

		function showMsg(text, kind) {
			if (!msg) return;
			msg.textContent = text;
			msg.className = 'szp-sess-msg is-show' + (kind ? ' is-' + kind : '');
		}

		function showToast(text) {
			if (!toast) return;
			toast.textContent = text;
			toast.hidden = false;
			setTimeout(function () { toast.hidden = true; }, 2600);
		}

		function renderJalali() {
			var txt = dateEl && dateEl.value ? jalaliFromISO(dateEl.value) : '';
			if (jalaliEl) jalaliEl.textContent = txt;
			if (dispEl) dispEl.value = txt; // نمایش شمسی روی خودِ فیلد
		}
		if (dateEl) dateEl.addEventListener('change', renderJalali);
		if (dateEl) dateEl.addEventListener('input', renderJalali);
		if (dispEl) {
			dispEl.addEventListener('click', function () {
				openDatePicker(dispEl, dateEl ? dateEl.value : '', function (iso) {
					if (dateEl) dateEl.value = iso;
					renderJalali();
				});
			});
		}
		renderJalali();

		function resetForm() {
			['title', 'customer_name', 'customer_mobile', 'mentor_name', 'mentor_mobile', 'date', 'start', 'end', 'survey_url', 'note'].forEach(function (k) {
				var el = fld(k); if (el) el.value = '';
			});
			fld('id').value = '0';
			renderJalali();
			if (resetBtn) resetBtn.hidden = true;
			if (saveBtn) saveBtn.textContent = 'ثبت جلسه و ارسال پیامک';
			showMsg('', '');
		}

		function fillForm(data) {
			Object.keys(data).forEach(function (k) {
				var el = fld(k);
				if (el) el.value = data[k];
			});
			renderJalali();
			if (resetBtn) resetBtn.hidden = false;
			if (saveBtn) saveBtn.textContent = 'ذخیره تغییرات و اطلاع‌رسانی';
			showMsg('در حال ویرایش جلسه — تغییر زمان، پیامک اطلاع‌رسانی مجدد می‌فرستد.', '');
			var wrap = root.querySelector('.szp-sess-formwrap');
			if (wrap) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (saveBtn && saveBtn.disabled) return;
			var mobile = (fld('customer_mobile').value || '').trim();
			if (mobile === '') { showMsg('موبایل مشتری را وارد کنید.', 'err'); return; }
			if (!fld('date').value || !fld('start').value || !fld('end').value) {
				showMsg('تاریخ و ساعت شروع و پایان را کامل کنید.', 'err'); return;
			}
			if (fld('end').value <= fld('start').value) {
				showMsg('ساعت پایان باید بعد از ساعت شروع باشد.', 'err'); return;
			}
			if (saveBtn) saveBtn.disabled = true;
			showMsg('در حال ثبت…', '');
			var d = new FormData();
			['id', 'title', 'customer_name', 'customer_mobile', 'coach_id', 'mentor_name', 'mentor_mobile', 'date', 'start', 'end', 'survey_url', 'note'].forEach(function (k) {
				var el = fld(k); d.append(k, el ? el.value : '');
			});
			ajax('szp_sess_save', d, function (res) {
				if (res && res.success) {
					showMsg((res.data && res.data.msg) || 'ثبت شد ✓', 'ok');
					setTimeout(function () { window.location.reload(); }, 900);
				} else {
					if (saveBtn) saveBtn.disabled = false;
					showMsg((res && res.data && res.data.msg) || 'خطا در ثبت.', 'err');
				}
			});
		});

		if (resetBtn) resetBtn.addEventListener('click', resetForm);

		// عملیات روی کارت‌ها (ویرایش/لغو/برگزاری/حذف).
		root.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-act]');
			if (!btn || btn.closest('.szp-sess-form')) return;
			var act = btn.getAttribute('data-act');

			if (act === 'edit') {
				try { fillForm(JSON.parse(btn.getAttribute('data-edit'))); } catch (err) {}
				return;
			}

			var id = btn.getAttribute('data-id');
			if (!id) return;
			var confirmMsg = act === 'cancel' ? 'این جلسه لغو شود؟ (پیامک نظرسنجی هم ارسال نخواهد شد)'
				: act === 'delete' ? 'این جلسه برای همیشه حذف شود؟'
					: act === 'done' ? 'این جلسه «برگزار شد» علامت بخورد؟' : '';
			if (confirmMsg && !window.confirm(confirmMsg)) return;

			btn.disabled = true;
			var d = new FormData();
			d.append('id', id);
			var action = act === 'cancel' ? 'szp_sess_cancel' : act === 'delete' ? 'szp_sess_delete' : 'szp_sess_done';
			ajax(action, d, function (res) {
				if (res && res.success) {
					showToast((res.data && res.data.msg) || 'انجام شد ✓');
					setTimeout(function () { window.location.reload(); }, 700);
				} else {
					btn.disabled = false;
					showToast((res && res.data && res.data.msg) || 'خطا در انجام عملیات.');
				}
			});
		});
	}

	function init() { document.querySelectorAll('.szp-sess').forEach(initSess); }
	if (document.readyState !== 'loading') init();
	else document.addEventListener('DOMContentLoaded', init);
})();
