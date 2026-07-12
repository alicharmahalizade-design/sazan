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
	function skeletonHtml() {
		var row = '<div class="szc-p-skel"><div class="szc-p-skel-av"></div><div class="szc-p-skel-lines"><div class="szc-p-skel-line"></div><div class="szc-p-skel-line sm"></div></div></div>';
		return '<div class="szc-p-skel-list">' + row + row + row + row + row + row + '</div>';
	}

	function loadView(view, params, push) {
		var m = main();
		if (!m) return;
		var prev = m.innerHTML;
		m.classList.add('is-loading');
		m.innerHTML = skeletonHtml();
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
					m.innerHTML = prev;
					toast((res && res.data && res.data.msg) || 'خطا در بارگذاری.', 'err');
				}
			})
			.catch(function () { m.classList.remove('is-loading'); m.innerHTML = prev; toast('خطای ارتباط با سرور.', 'err'); });
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

	/* ==================== حالت تاریکِ دستی ==================== */
	function applyTheme(t) {
		var root = qs('.szc-portal');
		if (!root) return;
		if (t === 'dark' || t === 'light') { root.setAttribute('data-theme', t); }
		else { root.removeAttribute('data-theme'); }
	}
	(function () { var t = null; try { t = localStorage.getItem('szc_theme'); } catch (e) {} if (t) applyTheme(t); })();
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-theme-toggle]');
		if (!b || !b.closest('.szc-portal')) return;
		var root = qs('.szc-portal');
		var cur = root.getAttribute('data-theme');
		var sysDark = window.matchMedia && window.matchMedia('(prefers-color-scheme:dark)').matches;
		var eff = cur ? cur : (sysDark ? 'dark' : 'light');
		var next = eff === 'dark' ? 'light' : 'dark';
		applyTheme(next);
		try { localStorage.setItem('szc_theme', next); } catch (e) {}
	});

	/* ==================== ارسالِ خودکارِ فرمِ فیلتر (select ها) ==================== */
	document.addEventListener('change', function (e) {
		var s = e.target.closest('[data-autosubmit]');
		if (!s || !s.closest('.szc-portal')) return;
		var f = s.closest('form');
		if (!f) return;
		var params = {};
		new FormData(f).forEach(function (v, k) { if (k !== 'szc_view' && v !== '') params[k] = v; });
		loadView('contacts', params, true);
	});

	/* ==================== جستجوی زنده (به‌روزرسانیِ بخشی، بدون از دست رفتنِ فوکوس) ==================== */
	function partialContacts(params, push) {
		var cur = qs('.szc-p-contactsmain');
		if (!cur) { loadView('contacts', params, push); return; }
		cur.classList.add('is-loading');
		var d = new FormData();
		d.append('action', 'szc_portal_view');
		d.append('nonce', CFG.nonce || '');
		d.append('view', 'contacts');
		Object.keys(params || {}).forEach(function (k) { d.append('params[' + k + ']', params[k]); });
		fetch(CFG.ajax || '', { method: 'POST', credentials: 'same-origin', body: d })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				cur.classList.remove('is-loading');
				if (!res || !res.success) return;
				var tmp = document.createElement('div');
				tmp.innerHTML = res.data.html || '';
				var nu = tmp.querySelector('.szc-p-contactsmain');
				if (!nu) return;
				var active = document.activeElement;
				var wasSearch = active && active.getAttribute && active.getAttribute('data-live-search') !== null && active.hasAttribute('data-live-search');
				cur.innerHTML = nu.innerHTML;
				current = { view: 'contacts', params: params || {} };
				if (push !== false) history.pushState({ szc: true, view: 'contacts', params: params || {} }, '', viewUrl('contacts', params));
				if (wasSearch) {
					var ni = cur.querySelector('[data-live-search]');
					if (ni) { ni.focus(); try { var v = ni.value; ni.setSelectionRange(v.length, v.length); } catch (e) {} }
				}
				enhance();
			})
			.catch(function () { cur.classList.remove('is-loading'); });
	}
	var liveTimer = null;
	document.addEventListener('input', function (e) {
		var inp = e.target.closest('[data-live-search]');
		if (!inp || !inp.closest('.szc-portal')) return;
		clearTimeout(liveTimer);
		liveTimer = setTimeout(function () {
			var f = inp.closest('form');
			if (!f) return;
			var params = {};
			new FormData(f).forEach(function (v, k) { if (k !== 'szc_view' && v !== '') params[k] = v; });
			partialContacts(params, true);
		}, 350);
	});

	/* ==================== هدفِ روزانه‌ی تماس ==================== */
	function goalTarget() { var v = 30; try { v = parseInt(localStorage.getItem('szc_call_goal') || '30', 10); } catch (e) {} return v > 0 ? v : 30; }
	function updateGoalRing() {
		var el = qs('.szc-p-goal');
		if (!el) return;
		var done = parseInt(el.getAttribute('data-goal-calls') || '0', 10);
		var target = goalTarget();
		var pct = target > 0 ? Math.max(0, Math.min(100, Math.round(done / target * 100))) : 0;
		var C = 2 * Math.PI * 19;
		var fg = el.querySelector('[data-goal-fg]');
		if (fg) fg.style.strokeDashoffset = String(C * (1 - pct / 100));
		var p = el.querySelector('[data-goal-pct]'); if (p) p.textContent = faD(pct) + '٪';
		var t = el.querySelector('[data-goal-target]'); if (t) t.textContent = faD(target);
		var d = el.querySelector('[data-goal-done]'); if (d) d.textContent = faD(done);
		el.classList.toggle('is-done', done >= target);
	}
	document.addEventListener('click', function (e) {
		var inc = e.target.closest('[data-goal-inc]'), dec = e.target.closest('[data-goal-dec]');
		if (!inc && !dec) return;
		var t = goalTarget() + (inc ? 5 : -5); if (t < 5) t = 5;
		try { localStorage.setItem('szc_call_goal', String(t)); } catch (e2) {}
		updateGoalRing();
	});

	/* ==================== ایندکسِ الفبایی ==================== */
	function normFa(s) { return (s || '').replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ي/g, 'ی').replace(/ك/g, 'ک').replace(/^\s+/, ''); }
	function flashLetter(letter) {
		var f = qs('.szc-p-alpha-flash');
		if (!f) { f = document.createElement('div'); f.className = 'szc-p-alpha-flash'; document.body.appendChild(f); }
		f.textContent = letter;
		f.classList.add('is-show');
		clearTimeout(f.__t); f.__t = setTimeout(function () { f.classList.remove('is-show'); }, 500);
	}
	document.addEventListener('click', function (e) {
		var l = e.target.closest('.szc-p-alpha-l');
		if (!l || !l.closest('.szc-portal')) return;
		var letter = normFa(l.getAttribute('data-letter'));
		var rows = document.querySelectorAll('.szc-p-clist .szc-p-crow');
		var target = null;
		for (var i = 0; i < rows.length; i++) {
			var nm = normFa(rows[i].getAttribute('data-name'));
			if (nm && nm.charAt(0) === letter) { target = rows[i]; break; }
		}
		if (target) { target.scrollIntoView({ behavior: 'smooth', block: 'center' }); flashLetter(l.getAttribute('data-letter')); }
	});

	/* ==================== بارگذاری بیشتر / اسکرول بی‌نهایت ==================== */
	function loadMore(b) {
		if (!b || b.classList.contains('is-loading')) return;
		b.classList.add('is-loading');
		var link = parseLink(b.href) || { params: {} };
		var d = new FormData();
		d.append('action', 'szc_portal_view');
		d.append('nonce', CFG.nonce || '');
		d.append('view', 'contacts');
		Object.keys(link.params).forEach(function (k) { d.append('params[' + k + ']', link.params[k]); });
		fetch(CFG.ajax || '', { method: 'POST', credentials: 'same-origin', body: d })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (res && res.success) {
					var tmp = document.createElement('div');
					tmp.innerHTML = res.data.html || '';
					var newList = tmp.querySelector('.szc-p-clist');
					var list = qs('.szc-p-clist');
					if (newList && list) { while (newList.firstElementChild) { list.appendChild(newList.firstElementChild); } }
					var newMore = tmp.querySelector('[data-loadmore]');
					if (newMore) { b.setAttribute('href', newMore.getAttribute('href')); b.classList.remove('is-loading'); }
					else { b.remove(); }
				} else { b.classList.remove('is-loading'); }
			})
			.catch(function () { b.classList.remove('is-loading'); });
	}
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-loadmore]');
		if (!b || !b.closest('.szc-portal')) return;
		e.preventDefault();
		loadMore(b);
	});
	var moreObserver = ('IntersectionObserver' in window) ? new IntersectionObserver(function (ents) {
		ents.forEach(function (en) { if (en.isIntersecting) loadMore(en.target); });
	}, { rootMargin: '300px' }) : null;

	/* ==================== Swipe روی کارتِ مخاطب ==================== */
	(function () {
		var OPEN = -128, cur = null, startX = 0, startY = 0, base = 0, active = false, moved = 0;
		function closeAll(except) {
			document.querySelectorAll('.szc-p-crow.is-swiped').forEach(function (r) { if (r !== except) r.classList.remove('is-swiped'); });
		}
		document.addEventListener('touchstart', function (e) {
			var face = e.target.closest('.szc-p-cface');
			var row = face && face.closest('.szc-p-crow');
			if (!row || !row.closest('.szc-portal')) { closeAll(null); cur = null; return; }
			if (e.target.closest('.szc-p-callbtn')) { cur = null; return; }
			cur = row; startX = e.touches[0].clientX; startY = e.touches[0].clientY;
			base = row.classList.contains('is-swiped') ? OPEN : 0; active = false; moved = 0;
			closeAll(row);
		}, { passive: true });
		document.addEventListener('touchmove', function (e) {
			if (!cur) return;
			var mx = e.touches[0].clientX - startX, my = e.touches[0].clientY - startY;
			if (!active) {
				if (Math.abs(mx) > 10 && Math.abs(mx) > Math.abs(my)) active = true;
				else if (Math.abs(my) > 10) { cur = null; return; }
				else return;
			}
			var t = Math.max(OPEN, Math.min(0, base + mx)); moved = t;
			var face = cur.querySelector('.szc-p-cface');
			if (face) { face.style.transition = 'none'; face.style.transform = 'translateX(' + t + 'px)'; }
		}, { passive: true });
		function end() {
			if (!cur) return;
			var face = cur.querySelector('.szc-p-cface');
			if (face) { face.style.transition = ''; face.style.transform = ''; }
			if (moved <= OPEN / 2) cur.classList.add('is-swiped'); else cur.classList.remove('is-swiped');
			cur = null; active = false;
		}
		document.addEventListener('touchend', end);
		document.addEventListener('touchcancel', end);
	})();

	/* ==================== شیتِ ثبتِ نتیجه‌ی تماس (پس از تماس) ==================== */
	var pendingCall = null, sheetEl = null, backdropEl = null;
	var OUTCOMES = [['answered', 'پاسخ داد'], ['no_answer', 'پاسخ نداد'], ['busy', 'مشغول'], ['callback', 'درخواست تماس مجدد'], ['wrong', 'شماره اشتباه'], ['not_interested', 'بی‌علاقه']];
	function ensureSheet() {
		if (sheetEl) return;
		backdropEl = document.createElement('div'); backdropEl.className = 'szc-p-sheet-backdrop';
		sheetEl = document.createElement('div'); sheetEl.className = 'szc-p-sheet';
		document.body.appendChild(backdropEl); document.body.appendChild(sheetEl);
		backdropEl.addEventListener('click', closeSheet);
	}
	function closeSheet() { if (sheetEl) sheetEl.classList.remove('is-show'); if (backdropEl) backdropEl.classList.remove('is-show'); }
	function openCallSheet(pc) {
		ensureSheet();
		var opts = OUTCOMES.map(function (o) { return '<option value="' + o[0] + '">' + o[1] + '</option>'; }).join('');
		sheetEl.innerHTML = '<div class="szc-p-sheet-grab"></div>'
			+ '<h3>نتیجه‌ی تماس با ' + (pc.name ? pc.name.replace(/[<>&]/g, '') : 'مخاطب') + '</h3>'
			+ '<p class="szc-p-muted">نتیجه‌ی تماس را ثبت کنید تا در تاریخچه بماند.</p>'
			+ '<select data-cs-outcome>' + opts + '</select>'
			+ '<textarea data-cs-note rows="2" placeholder="یادداشت تماس (اختیاری)"></textarea>'
			+ '<label class="szc-p-check" style="margin-bottom:12px"><input type="checkbox" data-cs-sms> ارسال پیامک تشکر</label>'
			+ '<div class="szc-p-btnrow"><button class="szc-p-btn szc-p-btn-primary" data-cs-save data-id="' + pc.id + '">ثبت تماس</button><button class="szc-p-btn" data-cs-cancel>بعداً</button></div>';
		backdropEl.classList.add('is-show');
		requestAnimationFrame(function () { sheetEl.classList.add('is-show'); });
	}
	document.addEventListener('click', function (e) {
		if (e.target.closest('[data-cs-cancel]')) { closeSheet(); return; }
		var save = e.target.closest('[data-cs-save]');
		if (!save) return;
		var d = new FormData();
		d.append('contact', save.getAttribute('data-id'));
		d.append('outcome', (sheetEl.querySelector('[data-cs-outcome]') || {}).value || 'answered');
		d.append('note', (sheetEl.querySelector('[data-cs-note]') || {}).value || '');
		var sms = sheetEl.querySelector('[data-cs-sms]');
		d.append('sms', sms && sms.checked ? '1' : '0');
		d.append('template', '0');
		save.disabled = true;
		d.append('action', 'szc_log_call'); d.append('nonce', CFG.nonce || '');
		fetch(CFG.ajax || '', { method: 'POST', credentials: 'same-origin', body: d })
			.then(function (r) { return r.json(); })
			.then(function (res) { closeSheet(); if (window.SZC_PORTAL && SZC_PORTAL.handleRes) SZC_PORTAL.handleRes(res); })
			.catch(function () { closeSheet(); toast('خطای ارتباط با سرور.', 'err'); });
	});
	document.addEventListener('click', function (e) {
		var cb = e.target.closest('.szc-p-callbtn[data-call]');
		if (!cb || !cb.closest('.szc-portal')) return;
		var row = cb.closest('[data-contact]');
		if (row) pendingCall = { id: row.getAttribute('data-contact'), name: row.getAttribute('data-name') || '' };
	});
	document.addEventListener('visibilitychange', function () {
		if (document.visibilityState === 'visible' && pendingCall) { var pc = pendingCall; pendingCall = null; setTimeout(function () { openCallSheet(pc); }, 300); }
	});

	/* ==================== PWA: نصب، سرویس‌ورکر، اعلان ==================== */
	if ('serviceWorker' in navigator && CFG.sw) {
		window.addEventListener('load', function () { navigator.serviceWorker.register(CFG.sw, { scope: CFG.scope || '/' }).catch(function () {}); });
	}
	var deferredPrompt = null, notified = false;
	window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); deferredPrompt = e; showPwaBar(); });
	function showPwaBar() {
		var bar = qs('.szc-p-pwabar');
		if (!bar) return;
		var inst = bar.querySelector('[data-pwa-install]'), noti = bar.querySelector('[data-notify-enable]');
		var show = false;
		if (deferredPrompt && inst) { inst.hidden = false; show = true; }
		if (noti && 'Notification' in window && Notification.permission === 'default') { noti.hidden = false; show = true; }
		bar.hidden = !show;
	}
	function maybeNotifyOverdue() {
		if (notified || !('Notification' in window) || Notification.permission !== 'granted') return;
		var bar = qs('.szc-p-pwabar'); if (!bar) return;
		var n = parseInt(bar.getAttribute('data-overdue') || '0', 10);
		if (n > 0) { notified = true; try { new Notification('سازان CRM', { body: 'شما ' + faD(n) + ' پیگیری سررسیده دارید.' }); } catch (e) {} }
	}
	document.addEventListener('click', function (e) {
		var inst = e.target.closest('[data-pwa-install]');
		if (inst && deferredPrompt) { deferredPrompt.prompt(); deferredPrompt.userChoice.then(function () { deferredPrompt = null; inst.hidden = true; showPwaBar(); }); return; }
		var noti = e.target.closest('[data-notify-enable]');
		if (noti && 'Notification' in window) { Notification.requestPermission().then(function (p) { noti.hidden = true; showPwaBar(); if (p === 'granted') maybeNotifyOverdue(); }); }
	});

	/* ==================== اجرای مجددِ بهبودها پس از هر رندر ==================== */
	function enhance() {
		updateGoalRing();
		showPwaBar();
		maybeNotifyOverdue();
		var lm = qs('[data-loadmore]');
		if (lm && moreObserver) moreObserver.observe(lm);
	}
	(function () {
		var m = main();
		if (m && 'MutationObserver' in window) { new MutationObserver(function () { enhance(); }).observe(m, { childList: true }); }
	})();

	/* ---------- وضعیت اولیه در تاریخچه ---------- */
	(function () {
		var root = qs('.szc-portal');
		if (!root) return;
		enhance();
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
