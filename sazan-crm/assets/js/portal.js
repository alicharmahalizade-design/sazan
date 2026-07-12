(function () {
	'use strict';

	var CFG = window.SZC_PORTAL || {};
	var current = { view: 'dashboard', params: {} };

	function qs(sel) { return document.querySelector(sel); }
	function main() { return qs('.szc-portal .szc-p-main'); }

	/* ---------- توست ---------- */
	var toastEl = null, toastTimer = null;
	function toast(text, kind) {
		if (!text) return;
		if (!toastEl) {
			toastEl = document.createElement('div');
			toastEl.className = 'szc-p-toast';
			document.body.appendChild(toastEl);
		}
		toastEl.textContent = text;
		toastEl.className = 'szc-p-toast is-show' + (kind ? ' is-' + kind : '');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { toastEl.className = 'szc-p-toast'; }, 2600);
	}

	/* ---------- ساخت URL نما ---------- */
	function viewUrl(view, params) {
		var u = (CFG.base || location.href.split('?')[0]);
		var q = ['szc_view=' + encodeURIComponent(view)];
		Object.keys(params || {}).forEach(function (k) {
			if (params[k] !== '' && params[k] != null) q.push(encodeURIComponent(k) + '=' + encodeURIComponent(params[k]));
		});
		return u + (u.indexOf('?') !== -1 ? '&' : '?') + q.join('&');
	}

	function setActiveNav(view) {
		document.querySelectorAll('.szc-p-navlink, .szc-p-bnitem').forEach(function (a) {
			a.classList.toggle('is-active', a.getAttribute('data-view') === view);
		});
	}

	/* ---------- بارگذاری یک نما با AJAX ---------- */
	function loadView(view, params, push) {
		var m = main();
		if (!m) return;
		m.classList.add('is-loading');
		var d = new FormData();
		d.append('action', 'szc_portal_view');
		d.append('nonce', CFG.nonce || '');
		d.append('view', view);
		Object.keys(params || {}).forEach(function (k) { d.append('params[' + k + ']', params[k]); });

		fetch(CFG.ajax || '', { method: 'POST', credentials: 'same-origin', body: d })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				m.classList.remove('is-loading');
				if (res && res.success) {
					m.innerHTML = res.data.html || '';
					current = { view: view, params: params || {} };
					setActiveNav(view);
					if (push !== false) history.pushState({ szc: true, view: view, params: params || {} }, '', viewUrl(view, params));
					m.scrollTop = 0;
					var top = qs('.szc-portal.is-fullscreen .szc-p-main');
					if (top) top.scrollTop = 0;
				} else {
					toast((res && res.data && res.data.msg) || 'خطا در بارگذاری.', 'err');
				}
			})
			.catch(function () { m.classList.remove('is-loading'); toast('خطای ارتباط با سرور.', 'err'); });
	}

	function reloadCurrent() { loadView(current.view, current.params, false); }

	function parseLink(href) {
		var u;
		try { u = new URL(href, location.href); } catch (e) { return null; }
		if (!u.searchParams.has('szc_view')) return null;
		var view = u.searchParams.get('szc_view') || 'dashboard';
		var params = {};
		u.searchParams.forEach(function (v, k) { if (k !== 'szc_view') params[k] = v; });
		return { view: view, params: params };
	}

	/* ---------- رهگیری کلیک روی لینک‌های داخلی ---------- */
	document.addEventListener('click', function (e) {
		var a = e.target.closest('a[href]');
		if (!a || !a.closest('.szc-portal')) return;
		var href = a.getAttribute('href') || '';
		if (href.indexOf('szc_view=') === -1) return;
		var p = parseLink(a.href);
		if (!p) return;
		e.preventDefault();
		loadView(p.view, p.params, true);
	});

	/* ---------- رهگیری ارسال فرم‌های جستجو/فیلتر ---------- */
	document.addEventListener('submit', function (e) {
		var f = e.target.closest('form');
		if (!f || !f.closest('.szc-portal')) return;
		var vi = f.querySelector('[name="szc_view"]');
		if (!vi) return;
		e.preventDefault();
		var params = {};
		new FormData(f).forEach(function (v, k) { if (k !== 'szc_view' && v !== '') params[k] = v; });
		loadView(vi.value, params, true);
	});

	/* ---------- پیش‌تنظیم‌های پیگیری (نمابندی مجدد پس از AJAX) ---------- */
	function pad(n) { return (n < 10 ? '0' : '') + n; }
	function toLocalInput(dt) {
		return dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate()) + 'T' + pad(dt.getHours()) + ':' + pad(dt.getMinutes());
	}
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-preset]');
		if (!b || !b.closest('.szc-portal')) return;
		var t = b.getAttribute('data-preset');
		var d = new Date(); d.setSeconds(0, 0);
		if (t === 'tomorrow10') { d.setDate(d.getDate() + 1); d.setHours(10, 0); }
		else if (t === 'today17') { d.setHours(17, 0); }
		else if (t === 'd3') { d.setDate(d.getDate() + 3); d.setHours(10, 0); }
		else if (t === 'week') { d.setDate(d.getDate() + 7); d.setHours(10, 0); }
		var input = qs('[data-followup-at]');
		if (input) { input.value = toLocalInput(d); syncJpDisp(input); }
	});

	/* ---------- تقویم شمسی (انتخابگر تاریخ/ساعت پیگیری) ---------- */
	var JFA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
	function faD(s) { return String(s).replace(/[0-9]/g, function (x) { return JFA[x]; }); }
	var JMON = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
	var JDOW = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
	function g2j(gy, gm, gd) {
		var gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
		var gy2 = (gm > 2) ? (gy + 1) : gy;
		var days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + gdm[gm - 1];
		var jy = -1595 + (33 * Math.floor(days / 12053)); days %= 12053;
		jy += 4 * Math.floor(days / 1461); days %= 1461;
		if (days > 365) { jy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
		var jm, jd;
		if (days < 186) { jm = 1 + Math.floor(days / 31); jd = 1 + (days % 31); }
		else { jm = 7 + Math.floor((days - 186) / 30); jd = 1 + ((days - 186) % 30); }
		return [jy, jm, jd];
	}
	function j2g(jy, jm, jd) {
		jy += 1595;
		var days = -355668 + (365 * jy) + (Math.floor(jy / 33) * 8) + Math.floor(((jy % 33) + 3) / 4) + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
		var gy = 400 * Math.floor(days / 146097); days %= 146097;
		if (days > 36524) { gy += 100 * Math.floor(--days / 36524); days %= 36524; if (days >= 365) days++; }
		gy += 4 * Math.floor(days / 1461); days %= 1461;
		if (days > 365) { gy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
		var gd = days + 1;
		var leap = ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0));
		var sal = [0, 31, leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
		var gm; for (gm = 1; gm <= 12; gm++) { if (gd <= sal[gm]) break; gd -= sal[gm]; }
		return [gy, gm, gd];
	}
	function jDays(jy, jm) {
		if (jm <= 6) return 31;
		if (jm <= 11) return 30;
		var g = j2g(jy, 12, 30), b = g2j(g[0], g[1], g[2]);
		return (b[0] === jy && b[1] === 12 && b[2] === 30) ? 30 : 29;
	}
	function jalaliDisp(local) {
		var m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/.exec(local || '');
		if (!m) return '';
		var j = g2j(+m[1], +m[2], +m[3]);
		return faD(j[2] + ' ' + JMON[j[1]] + ' ' + j[0] + ' - ' + m[4] + ':' + m[5]);
	}
	function syncJpDisp(hidden) {
		var wrap = hidden.closest('.szc-jp'); if (!wrap) return;
		var disp = wrap.querySelector('.szc-jp-disp'); if (disp) disp.value = jalaliDisp(hidden.value);
	}

	var jpPop = null;
	function closeJp() { if (jpPop) { jpPop.remove(); jpPop = null; } }
	function openJp(anchor, hidden) {
		closeJp();
		var now = new Date();
		var today = g2j(now.getFullYear(), now.getMonth() + 1, now.getDate());
		var sel = null, hh = 10, mm = 0;
		var m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/.exec(hidden.value || '');
		if (m) { sel = g2j(+m[1], +m[2], +m[3]); hh = +m[4]; mm = +m[5]; }
		var view = sel ? [sel[0], sel[1]] : [today[0], today[1]];
		var pop = document.createElement('div'); pop.className = 'szc-jp-pop'; jpPop = pop;
		function render() {
			var jy = view[0], jm = view[1];
			var g1 = j2g(jy, jm, 1);
			var dow = (new Date(g1[0], g1[1] - 1, g1[2]).getDay() + 1) % 7;
			var total = jDays(jy, jm);
			var h = '<div class="szc-jp-nav"><button type="button" class="szc-jp-pm">‹</button><span>' + JMON[jm] + ' ' + faD(jy) + '</span><button type="button" class="szc-jp-nm">›</button></div><div class="szc-jp-grid">';
			for (var i = 0; i < 7; i++) h += '<span class="szc-jp-dow">' + JDOW[i] + '</span>';
			for (var k = 0; k < dow; k++) h += '<span></span>';
			for (var day = 1; day <= total; day++) {
				var on = sel && sel[0] === jy && sel[1] === jm && sel[2] === day;
				h += '<button type="button" class="szc-jp-day' + (on ? ' on' : '') + '" data-d="' + day + '">' + faD(day) + '</button>';
			}
			h += '</div><div class="szc-jp-time"><span>ساعت</span><input type="time" dir="ltr" class="szc-jp-t" value="' + pad(hh) + ':' + pad(mm) + '"></div>';
			h += '<div class="szc-jp-foot"><button type="button" class="szc-jp-ok">تأیید</button><button type="button" class="szc-jp-clear">پاک کردن</button></div>';
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
			if (t.classList.contains('szc-jp-pm')) { if (--view[1] < 1) { view[1] = 12; view[0]--; } render(); }
			else if (t.classList.contains('szc-jp-nm')) { if (++view[1] > 12) { view[1] = 1; view[0]++; } render(); }
			else if (t.classList.contains('szc-jp-day')) { sel = [view[0], view[1], parseInt(t.getAttribute('data-d'), 10)]; Array.prototype.forEach.call(pop.querySelectorAll('.szc-jp-day'), function (x) { x.classList.remove('on'); }); t.classList.add('on'); }
			else if (t.classList.contains('szc-jp-clear')) { hidden.value = ''; syncJpDisp(hidden); closeJp(); }
			else if (t.classList.contains('szc-jp-ok')) {
				if (!sel) { closeJp(); return; }
				var tv = (pop.querySelector('.szc-jp-t').value || '10:00').split(':');
				var g = j2g(sel[0], sel[1], sel[2]);
				hidden.value = g[0] + '-' + pad(g[1]) + '-' + pad(g[2]) + 'T' + pad(parseInt(tv[0], 10) || 0) + ':' + pad(parseInt(tv[1], 10) || 0);
				syncJpDisp(hidden); closeJp();
			}
		});
	}
	document.addEventListener('click', function (e) {
		var disp = e.target.closest('.szc-jp-disp');
		if (!disp || !disp.closest('.szc-portal')) return;
		var wrap = disp.closest('.szc-jp');
		var hidden = wrap ? wrap.querySelector('.szc-jp-val') : null;
		if (hidden) openJp(disp, hidden);
	});
	document.addEventListener('mousedown', function (e) { if (jpPop && !jpPop.contains(e.target) && !e.target.closest('.szc-jp-disp')) closeJp(); });
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeJp(); });

	/* ---------- پوشه‌ها (فولدربندی) ---------- */
	function crmPost(action, data) {
		var d = new FormData();
		d.append('action', action);
		d.append('nonce', CFG.nonce || '');
		Object.keys(data).forEach(function (k) { d.append(k, data[k]); });
		fetch(CFG.ajax || '', { method: 'POST', credentials: 'same-origin', body: d })
			.then(function (r) { return r.json(); })
			.then(function (res) { if (window.SZC_PORTAL && SZC_PORTAL.handleRes) SZC_PORTAL.handleRes(res); })
			.catch(function () { toast('خطای ارتباط با سرور.', 'err'); });
	}
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-folder-act]');
		if (!b || !b.closest('.szc-portal')) return;
		e.preventDefault();
		var act = b.getAttribute('data-folder-act');
		if (act === 'create') {
			var n = prompt('نام پوشه‌ی جدید:');
			if (n && n.trim()) crmPost('szc_group_create', { name: n.trim(), parent: b.getAttribute('data-parent') || '0' });
		} else if (act === 'rename') {
			var nn = prompt('نام جدید پوشه:', b.getAttribute('data-name') || '');
			if (nn && nn.trim()) crmPost('szc_group_rename', { id: b.getAttribute('data-id'), name: nn.trim() });
		} else if (act === 'delete') {
			if (confirm('این پوشه حذف شود؟ مخاطبین و زیرپوشه‌ها به پوشه‌ی بالادست منتقل می‌شوند.')) crmPost('szc_group_delete', { id: b.getAttribute('data-id') });
		} else if (act === 'bulk-stage') {
			var bar = b.closest('.szc-p-bulkbar'), s1 = bar && bar.querySelector('[data-bulk-stage]');
			if (bar && s1 && confirm('مرحله‌ی همه‌ی مخاطبین این پوشه تغییر کند؟')) crmPost('szc_group_bulk', { group: bar.getAttribute('data-group'), op: 'stage', value: s1.value });
		} else if (act === 'bulk-sms') {
			var bar2 = b.closest('.szc-p-bulkbar'), t1 = bar2 && bar2.querySelector('[data-bulk-tpl]');
			if (bar2 && t1 && confirm('پیامک برای همه‌ی مخاطبین این پوشه در صف قرار گیرد؟')) crmPost('szc_group_bulk', { group: bar2.getAttribute('data-group'), op: 'sms', value: t1.value });
		}
	});
	document.addEventListener('change', function (e) {
		var el = e.target.closest('[data-szc-act="set_group"]');
		if (!el || !el.closest('.szc-portal')) return;
		var single = document.querySelector('.szc-single');
		crmPost('szc_set_group', { contact: single ? single.getAttribute('data-contact') : '0', group: el.value });
	});

	/* ---------- دایلر: ثبت تماس و بعدی ---------- */
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-szc-act="dialer_call"]');
		if (!b || !b.closest('.szc-portal')) return;
		e.preventDefault();
		var card = b.closest('.szc-single');
		if (!card) return;
		var data = { contact: card.getAttribute('data-contact') || '0' };
		data.outcome = (card.querySelector('[data-call-outcome]') || {}).value || 'answered';
		data.note = (card.querySelector('[data-call-note]') || {}).value || '';
		var tpl = card.querySelector('[data-call-template]');
		if (tpl) {
			var tv = tpl.value;
			if (tv === '-1' || tv === '') { data.sms = '0'; data.template = '0'; }
			else { data.sms = '1'; data.template = tv; }
		}
		b.disabled = true;
		crmPost('szc_dialer_call', data);
	});

	/* ---------- درگ‌ودراپِ مخاطب به پوشه ---------- */
	document.addEventListener('dragstart', function (e) {
		var row = e.target.closest('[data-contact][draggable="true"]');
		if (!row || !row.closest('.szc-portal')) return;
		e.dataTransfer.setData('text/plain', row.getAttribute('data-contact'));
		e.dataTransfer.effectAllowed = 'move';
		row.classList.add('is-dragging');
	});
	document.addEventListener('dragend', function () {
		Array.prototype.forEach.call(document.querySelectorAll('.is-dragging'), function (x) { x.classList.remove('is-dragging'); });
		Array.prototype.forEach.call(document.querySelectorAll('.szc-p-dropover'), function (x) { x.classList.remove('szc-p-dropover'); });
	});
	document.addEventListener('dragover', function (e) {
		var t = e.target.closest('[data-drop-group]');
		if (!t || !t.closest('.szc-portal')) return;
		e.preventDefault();
		e.dataTransfer.dropEffect = 'move';
		t.classList.add('szc-p-dropover');
	});
	document.addEventListener('dragleave', function (e) {
		var t = e.target.closest('[data-drop-group]');
		if (t) t.classList.remove('szc-p-dropover');
	});
	document.addEventListener('drop', function (e) {
		var t = e.target.closest('[data-drop-group]');
		if (!t || !t.closest('.szc-portal')) return;
		e.preventDefault();
		t.classList.remove('szc-p-dropover');
		var cid = e.dataTransfer.getData('text/plain');
		if (cid) crmPost('szc_set_group', { contact: cid, group: t.getAttribute('data-drop-group') });
	});

	/* ---------- تاریخچه‌ی مرورگر ---------- */
	window.addEventListener('popstate', function (e) {
		var st = e.state;
		if (st && st.szc) loadView(st.view, st.params || {}, false);
	});

	/* ---------- نتیجه‌ی اکشن‌ها (از admin.js فراخوانی می‌شود) ---------- */
	window.SZC_PORTAL = window.SZC_PORTAL || CFG;
	window.SZC_PORTAL.handleRes = function (res, btn) {
		if (res && res.success) {
			var d = res.data || {};
			if (d.redirect) {
				var p = parseLink(d.redirect);
				if (p) { if (d.msg) toast(d.msg, 'ok'); loadView(p.view, p.params, true); return; }
			}
			if (d.reload) { if (d.msg) toast(d.msg, 'ok'); reloadCurrent(); if (btn) btn.disabled = false; return; }
			toast(d.msg || 'انجام شد', 'ok');
			if (btn) btn.disabled = false;
		} else {
			toast((res && res.data && res.data.msg) || 'خطا رخ داد.', 'err');
			if (btn) btn.disabled = false;
		}
	};

	/* ---------- وضعیت اولیه در تاریخچه ---------- */
	(function () {
		var root = qs('.szc-portal');
		if (!root) return;
		if (root.classList.contains('is-fullscreen')) { document.body.classList.add('szc-portal-lock'); }
		var active = document.querySelector('.szc-p-navlink.is-active');
		current.view = active ? (active.getAttribute('data-view') || 'dashboard') : 'dashboard';
		try {
			var u = new URL(location.href);
			u.searchParams.forEach(function (v, k) { if (k !== 'szc_view') current.params[k] = v; });
		} catch (e) {}
		history.replaceState({ szc: true, view: current.view, params: current.params }, '');
	})();
})();
