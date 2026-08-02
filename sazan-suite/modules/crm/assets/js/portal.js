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
					m.focus({ preventScroll: true });
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

	/* ---------- میان‌بر جستجو و جمع‌کردن پوشه‌ها در موبایل ---------- */
	document.addEventListener('keydown', function (e) {
		if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
		var tag = (document.activeElement && document.activeElement.tagName) || '';
		if (/INPUT|TEXTAREA|SELECT/.test(tag)) return;
		var search = qs('#szc-global-search');
		if (search) { e.preventDefault(); search.focus(); search.select(); }
	});
	document.addEventListener('click', function (e) {
		var toggle = e.target.closest('[data-folders-toggle]');
		if (!toggle || !toggle.closest('.szc-portal')) return;
		var folders = toggle.closest('[data-folders]');
		if (!folders) return;
		var collapsed = folders.classList.toggle('is-collapsed');
		toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
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

	var jpPop = null, jpBackdrop = null;
	function closeJp() {
		if (jpPop) { jpPop.remove(); jpPop = null; }
		if (jpBackdrop) { jpBackdrop.remove(); jpBackdrop = null; }
	}
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
		if (window.innerWidth <= 640) {
			// موبایل: نمایش به‌صورتِ شیتِ پایین‌چسبان با پس‌زمینه‌ی تیره (بدونِ برش از لبه‌ی صفحه).
			pop.classList.add('is-sheet');
			jpBackdrop = document.createElement('div');
			jpBackdrop.className = 'szc-jp-backdrop';
			document.body.appendChild(jpBackdrop);
			jpBackdrop.addEventListener('click', closeJp);
			document.body.appendChild(pop);
		} else {
			document.body.appendChild(pop);
			var r = anchor.getBoundingClientRect();
			pop.style.position = 'absolute';
			var w = pop.offsetWidth || 304;
			var left = window.scrollX + r.left;
			var maxLeft = window.scrollX + document.documentElement.clientWidth - w - 12;
			if (left > maxLeft) { left = Math.max(window.scrollX + 12, maxLeft); }
			pop.style.top = (window.scrollY + r.bottom + 6) + 'px';
			pop.style.left = left + 'px';
		}
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
	function crmPost(action, data, callback) {
		var d = new FormData();
		d.append('action', action);
		d.append('nonce', CFG.nonce || '');
		Object.keys(data).forEach(function (k) { d.append(k, data[k]); });
		fetch(CFG.ajax || '', { method: 'POST', credentials: 'same-origin', body: d, keepalive: true })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (callback) { callback(res); return; }
				if (window.SZC_PORTAL && SZC_PORTAL.handleRes) SZC_PORTAL.handleRes(res);
			})
			.catch(function () { toast('خطای ارتباط با سرور.', 'err'); });
	}

	document.addEventListener('click', function (e) {
		var call = e.target.closest('[data-reserve-call]');
		if (call && call.closest('.szc-portal')) {
			e.preventDefault();
			var href = call.getAttribute('href');
			crmPost('szc_reserve_lead', { contact: call.getAttribute('data-contact') || '0' }, function (res) {
				if (res && res.success) location.href = href;
				else toast((res && res.data && res.data.msg) || 'این مخاطب در حال پیگیری است.', 'err');
			});
			return;
		}
		var external = e.target.closest('[data-external]');
		if (external && external.closest('.szc-portal')) {
			crmPost('szc_log_external', {
				contact: external.getAttribute('data-contact') || '0',
				channel: external.getAttribute('data-external') || ''
			}, function () {});
		}
	});
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

	/* ---------- انتخابِ گروهی در فهرست مخاطبین (پیامک / انتقال به پوشه) ---------- */
	function selCount(m) { return m ? m.querySelectorAll('[data-sel-cb]:checked').length : 0; }
	function selRefresh(m) { if (!m) return; var el = m.querySelector('[data-sel-count]'); if (el) el.textContent = faD(selCount(m)); }
	document.addEventListener('click', function (e) {
		var tgl = e.target.closest('[data-sel-toggle]');
		if (tgl && tgl.closest('.szc-portal')) {
			e.preventDefault();
			var m = tgl.closest('.szc-p-contactsmain');
			var on = m.classList.toggle('is-selecting');
			var acts = m.querySelector('.szc-p-selactions');
			if (acts) acts.hidden = !on;
			tgl.classList.toggle('is-on', on);
			if (!on) {
				Array.prototype.forEach.call(m.querySelectorAll('[data-sel-cb]:checked'), function (cb) { cb.checked = false; });
				var sa = m.querySelector('[data-sel-all]'); if (sa) sa.checked = false;
				var sc = m.querySelector('[data-sel-scope-all]'); if (sc) sc.checked = false;
				selRefresh(m);
			}
			return;
		}
		var act = e.target.closest('[data-sel-act]');
		if (!act || !act.closest('.szc-portal')) return;
		e.preventDefault();
		var main = act.closest('.szc-p-contactsmain');
		var bar = main.querySelector('.szc-p-selbar');
		var op = act.getAttribute('data-sel-act');
		var scopeEl = main.querySelector('[data-sel-scope-all]');
		var isAll = scopeEl && scopeEl.checked;
		var ids = [];
		if (!isAll) {
			Array.prototype.forEach.call(main.querySelectorAll('[data-sel-cb]:checked'), function (cb) { ids.push(cb.value); });
			if (!ids.length) { toast('ابتدا چند مخاطب را انتخاب کنید یا «روی کلِ نتایج» را بزنید.', 'err'); return; }
		}
		var data = { op: op, scope: isAll ? 'all' : 'selected' };
		if (isAll) {
			data.f_s = bar.getAttribute('data-f-s') || '';
			data.f_stage = bar.getAttribute('data-f-stage') || '';
			data.f_priority = bar.getAttribute('data-f-priority') || '';
			data.f_due = bar.getAttribute('data-f-due') || '';
			data.f_group = bar.getAttribute('data-f-group') || '0';
		} else {
			ids.forEach(function (id, i) { data['ids[' + i + ']'] = id; });
		}
		var n = isAll ? faD(bar.getAttribute('data-total') || '') : faD(ids.length);
		if (op === 'sms') {
			var tpl = main.querySelector('[data-sel-tpl]');
			if (!tpl) { toast('قالبی تعریف نشده است.', 'err'); return; }
			data.value = tpl.value;
			var opt = tpl.options[tpl.selectedIndex];
			var preview = (opt && opt.getAttribute('data-body')) || '';
			var parts = Math.max(1, Math.ceil(preview.length / 70));
			var recipientCount = isAll ? parseInt(bar.getAttribute('data-total') || '0', 10) : ids.length;
			if (!confirm('پیش‌نمایش پیامک:\n' + preview + '\n\nگیرنده: ' + n + '\nبخش برای هر نفر: ' + faD(parts) + '\nمجموع تقریبی پیامک: ' + faD(recipientCount * parts) + '\n\nارسال شود؟')) return;
		} else if (op === 'move') {
			var fol = main.querySelector('[data-sel-folder]');
			if (!fol || fol.value === '') { toast('پوشه‌ی مقصد را انتخاب کنید.', 'err'); return; }
			data.value = fol.value;
			if (!confirm('انتقالِ ' + n + ' مخاطب به پوشه؟')) return;
		} else { return; }
		crmPost('szc_list_bulk', data);
	});
	document.addEventListener('change', function (e) {
		var cb = e.target.closest('[data-sel-cb]');
		if (cb && cb.closest('.szc-portal')) { selRefresh(cb.closest('.szc-p-contactsmain')); return; }
		var all = e.target.closest('[data-sel-all]');
		if (all && all.closest('.szc-portal')) {
			var m = all.closest('.szc-p-contactsmain');
			Array.prototype.forEach.call(m.querySelectorAll('[data-sel-cb]'), function (x) { x.checked = all.checked; });
			selRefresh(m);
		}
	});

	/* ---------- صفحه ارسال گروهی: پیام آماده یا متن آزاد ---------- */
	var broadcastCountTimer = null;
	function refreshBroadcastCount(form) {
		if (!form) return;
		var d = new FormData();
		d.append('action', 'szc_broadcast_count');
		d.append('nonce', CFG.nonce || '');
		d.append('b_stage', (form.querySelector('[data-broadcast-stage]') || {}).value || '');
		d.append('b_priority', (form.querySelector('[data-broadcast-priority]') || {}).value || '');
		fetch(CFG.ajax || '', { method: 'POST', credentials: 'same-origin', body: d })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || !res.success) return;
				form.setAttribute('data-current-count', res.data.count || 0);
				var out = document.querySelector('[data-broadcast-live-count]');
				if (out) out.textContent = res.data.fa || faD(res.data.count || 0);
			});
	}
	document.addEventListener('change', function (e) {
		var form = e.target.closest('[data-portal-broadcast]');
		if (!form) return;
		if (e.target.matches('input[name="broadcast_type"]')) {
			var type = e.target.value;
			form.querySelectorAll('[data-broadcast-panel]').forEach(function (panel) {
				panel.hidden = panel.getAttribute('data-broadcast-panel') !== type;
			});
		}
		if (e.target.matches('[data-broadcast-template]')) {
			var option = e.target.options[e.target.selectedIndex];
			var preview = form.querySelector('[data-broadcast-template-preview]');
			if (preview) preview.textContent = (option && option.getAttribute('data-body')) || '';
		}
		if (e.target.matches('[data-broadcast-stage], [data-broadcast-priority]')) {
			clearTimeout(broadcastCountTimer);
			broadcastCountTimer = setTimeout(function () { refreshBroadcastCount(form); }, 180);
		}
	});
	document.addEventListener('input', function (e) {
		if (!e.target.matches('[data-broadcast-text]')) return;
		var form = e.target.closest('[data-portal-broadcast]');
		var chars = e.target.value.length;
		var charOut = form && form.querySelector('[data-broadcast-chars]');
		var partOut = form && form.querySelector('[data-broadcast-parts]');
		if (charOut) charOut.textContent = faD(chars);
		if (partOut) partOut.textContent = faD(Math.max(1, Math.ceil(chars / 70)));
	});
	document.addEventListener('submit', function (e) {
		var form = e.target.closest('[data-portal-broadcast]');
		if (!form) return;
		e.preventDefault();
		var chosen = form.querySelector('input[name="broadcast_type"]:checked');
		if (!chosen) { toast('نوع پیام را انتخاب کنید.', 'err'); return; }
		var type = chosen.value;
		var data = {
			op: 'sms', scope: 'all', message_type: type,
			f_stage: (form.querySelector('[data-broadcast-stage]') || {}).value || '',
			f_priority: (form.querySelector('[data-broadcast-priority]') || {}).value || '',
			f_due: '', f_group: '0', f_s: ''
		};
		var preview = '';
		if (type === 'free') {
			var text = form.querySelector('[data-broadcast-text]');
			data.text = text ? text.value.trim() : '';
			if (!data.text) { toast('متن پیام را وارد کنید.', 'err'); if (text) text.focus(); return; }
			preview = data.text;
		} else {
			var tpl = form.querySelector('[data-broadcast-template]');
			if (!tpl) { toast('پیام آماده‌ای وجود ندارد.', 'err'); return; }
			data.value = tpl.value;
			var opt = tpl.options[tpl.selectedIndex];
			preview = (opt && opt.getAttribute('data-body')) || '';
		}
		var count = parseInt(form.getAttribute('data-current-count') || form.getAttribute('data-total') || '0', 10);
		var parts = Math.max(1, Math.ceil(preview.length / 70));
		if (!confirm('پیش‌نمایش پیام:\n' + preview + '\n\nگیرندگان مطابق فیلتر: ' + faD(count) + '\nبخش برای هر نفر: ' + faD(parts) + '\nمجموع تقریبی: ' + faD(count * parts) + '\n\nپیام‌ها در صف قرار بگیرند؟')) return;
		var button = form.querySelector('button[type="submit"]');
		if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
		crmPost('szc_list_bulk', data, function (res) {
			if (button) { button.disabled = false; button.removeAttribute('aria-busy'); }
			if (res && res.success) {
				toast((res.data && res.data.msg) || 'پیام‌ها در صف قرار گرفتند.', 'ok');
				var text = form.querySelector('[data-broadcast-text]'); if (text) { text.value = ''; text.dispatchEvent(new Event('input', { bubbles: true })); }
			} else toast((res && res.data && res.data.msg) || 'ارسال گروهی ناموفق بود.', 'err');
		});
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
		if ((data.outcome === 'not_interested' || data.outcome === 'wrong') && !data.note.trim()) { toast('برای این نتیجه ثبت توضیح الزامی است.', 'err'); return; }
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
		var kcard = e.target.closest('[data-kanban-contact]');
		if (kcard && kcard.closest('.szc-portal')) {
			e.dataTransfer.setData('application/x-szc-kanban', kcard.getAttribute('data-kanban-contact'));
			e.dataTransfer.effectAllowed = 'move';
			kcard.classList.add('is-dragging');
			return;
		}
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
		var stage = e.target.closest('[data-drop-stage]');
		if (stage && stage.closest('.szc-portal')) {
			e.preventDefault(); e.dataTransfer.dropEffect = 'move'; stage.classList.add('is-drop'); return;
		}
		var t = e.target.closest('[data-drop-group]');
		if (!t || !t.closest('.szc-portal')) return;
		e.preventDefault();
		e.dataTransfer.dropEffect = 'move';
		t.classList.add('szc-p-dropover');
	});
	document.addEventListener('dragleave', function (e) {
		var stage = e.target.closest('[data-drop-stage]');
		if (stage) stage.classList.remove('is-drop');
	});
	document.addEventListener('drop', function (e) {
		var stage = e.target.closest('[data-drop-stage]');
		if (!stage || !stage.closest('.szc-portal')) return;
		var id = e.dataTransfer.getData('application/x-szc-kanban');
		if (!id) return;
		e.preventDefault();
		stage.classList.remove('is-drop');
		crmPost('szc_set_field', { contact: id, field: 'stage', value: stage.getAttribute('data-drop-stage') }, function (res) {
			if (res && res.success) {
				var card = document.querySelector('[data-kanban-contact="' + id + '"]');
				var target = stage.querySelector('.szc-kanban-cards');
				if (card && target) target.prepend(card);
				toast((res.data && res.data.msg) || 'مرحله تغییر کرد.', 'ok');
			} else toast((res && res.data && res.data.msg) || 'تغییر مرحله ناموفق بود.', 'err');
		});
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

	/* ==================== ورود کارشناسان با کد یک‌بارمصرف ==================== */
	(function () {
		var f = document.querySelector('[data-login-form]');
		if (!f) return;
		var mobileInput = f.querySelector('[data-login-mobile]');
		var codeInput = f.querySelector('[data-login-code]');
		var codeWrap = f.querySelector('[data-login-code-wrap]');
		var passwordInput = f.querySelector('[data-login-password]');
		var passwordWrap = f.querySelector('[data-login-password-wrap]');
		var passwordToggle = f.querySelector('[data-login-password-toggle]');
		var methodButtons = f.querySelectorAll('[data-login-method]');
		var nonce = (f.querySelector('[data-login-nonce]') || {}).value || '';
		var msg = f.querySelector('[data-login-msg]');
		var btn = f.querySelector('.szc-p-login-btn');
		var btnLabel = f.querySelector('[data-login-button-label]');
		var sub = f.querySelector('[data-login-sub]');
		var resend = f.querySelector('[data-login-resend]');
		var change = f.querySelector('[data-login-change-mobile]');
		var countdown = f.querySelector('[data-login-countdown]');
		var help = f.querySelector('[data-login-help]');
		var successPanel = f.querySelector('[data-login-success]');
		var method = 'otp';
		var step = 'mobile';
		var timer = null;
		var verifying = false;
		var autoVerifyTimer = null;
		var otpController = null;
		document.body.classList.add('szc-login-lock');
		function fa(n) { return String(n).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[+d]; }); }
		function say(text, ok) {
			if (!msg) return;
			msg.textContent = text;
			msg.className = 'szc-p-login-msg' + (ok ? ' is-ok' : ' is-err');
		}
		function busy(on, label) {
			if (!btn) return;
			btn.disabled = on;
			btn.setAttribute('aria-busy', on ? 'true' : 'false');
			if (label && btnLabel) btnLabel.textContent = label;
		}
		function startCountdown(seconds) {
			clearInterval(timer);
			var left = Math.max(1, Number(seconds) || 60);
			if (resend) resend.disabled = true;
			function paint() {
				if (countdown) countdown.textContent = left > 0 ? '(' + fa(left) + ' ثانیه)' : '';
				if (left <= 0) {
					clearInterval(timer);
					if (resend) resend.disabled = false;
					return;
				}
				left--;
			}
			paint();
			timer = setInterval(paint, 1000);
		}
		function completeLogin(res) {
			step = 'success';
			verifying = true;
			if (otpController) otpController.abort();
			f.classList.add('is-success');
			if (successPanel) successPanel.hidden = false;
			setTimeout(function () { location.replace((res.data && res.data.redirect) || location.href); }, 900);
		}
		function selectMethod(next) {
			method = next === 'password' ? 'password' : 'otp';
			clearInterval(timer);
			clearTimeout(autoVerifyTimer);
			if (otpController) otpController.abort();
			verifying = false;
			step = method === 'password' ? 'password' : 'mobile';
			if (codeWrap) codeWrap.hidden = true;
			if (passwordWrap) passwordWrap.hidden = method !== 'password';
			if (codeInput) codeInput.value = '';
			if (method !== 'password' && passwordInput) passwordInput.value = '';
			if (mobileInput) mobileInput.readOnly = false;
			methodButtons.forEach(function (button) {
				var active = button.getAttribute('data-login-method') === method;
				button.classList.toggle('is-active', active);
				button.setAttribute('aria-selected', active ? 'true' : 'false');
			});
			if (method === 'password') {
				if (sub) sub.textContent = 'شماره موبایل و رمز ثابت تعریف‌شده توسط مدیر را وارد کنید.';
				if (help) help.textContent = 'در صورت فراموشی رمز، می‌توانید از روش کد یک‌بارمصرف استفاده کنید.';
				busy(false, 'ورود با رمز ثابت');
				setTimeout(function () { if (mobileInput && !mobileInput.value) mobileInput.focus(); else if (passwordInput) passwordInput.focus(); }, 50);
			} else {
				if (sub) sub.textContent = 'شماره موبایل ثبت‌شده خود را وارد کنید تا کد ورود برایتان پیامک شود.';
				if (help) help.textContent = 'کد ورود کوتاه‌مدت است و فقط یک‌بار قابل استفاده خواهد بود.';
				busy(false, 'دریافت کد ورود');
			}
			say('', true);
		}
		function showCode(data) {
			step = 'code';
			if (codeWrap) codeWrap.hidden = false;
			if (mobileInput) mobileInput.readOnly = true;
			if (btnLabel) btnLabel.textContent = 'تأیید کد و ورود';
			if (sub) sub.textContent = 'کد پیامک‌شده را وارد کنید. این کد فقط یک‌بار قابل استفاده است.';
			startCountdown((data && data.resend_after) || 60);
			startWebOtp();
			setTimeout(function () { if (codeInput) codeInput.focus(); }, 50);
		}
		function normalizeDigits(value) {
			return String(value || '').replace(/[۰-۹]/g, function (d) { return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)); }).replace(/[^0-9]/g, '').slice(0, 6);
		}
		function startWebOtp() {
			if (!('OTPCredential' in window) || !navigator.credentials || typeof AbortController === 'undefined') return;
			if (otpController) otpController.abort();
			otpController = new AbortController();
			var currentController = otpController;
			navigator.credentials.get({ otp: { transport: ['sms'] }, signal: otpController.signal })
				.then(function (credential) {
					if (!credential || !credential.code || step !== 'code') return;
					if (codeInput) codeInput.value = normalizeDigits(credential.code);
					verifyCode();
				})
				.catch(function () { /* لغو، عدم پشتیبانی یا انقضا نیاز به پیام خطا ندارد. */ });
			setTimeout(function () { if (otpController === currentController) currentController.abort(); }, 180000);
		}
		function requestCode() {
			var mobile = mobileInput ? mobileInput.value : '';
			if (!mobile.trim()) { say('شماره موبایل را وارد کنید.'); if (mobileInput) mobileInput.focus(); return; }
			busy(true, 'در حال ارسال کد…');
			if (resend) resend.disabled = true;
			var data = new FormData();
			data.append('action', 'szc_portal_otp_request');
			data.append('nonce', nonce);
			data.append('mobile', mobile);
			fetch(CFG.ajax || '', { method: 'POST', credentials: 'same-origin', body: data })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (res && res.success) {
						say((res.data && res.data.msg) || 'کد ورود ارسال شد.', true);
						showCode(res.data || {});
						busy(false, 'تأیید کد و ورود');
					} else {
						say((res && res.data && res.data.msg) || 'ارسال کد انجام نشد.');
						busy(false, step === 'code' ? 'تأیید کد و ورود' : 'دریافت کد ورود');
						if (step === 'code' && resend) resend.disabled = false;
					}
				})
				.catch(function () {
					say('ارتباط با سرور برقرار نشد. دوباره تلاش کنید.');
					busy(false, step === 'code' ? 'تأیید کد و ورود' : 'دریافت کد ورود');
					if (step === 'code' && resend) resend.disabled = false;
				});
		}
		function verifyCode() {
			if (verifying || step !== 'code') return;
			var code = codeInput ? codeInput.value : '';
			if (!/^[0-9۰-۹]{6}$/.test(code.trim())) { say('کد ورود باید شش رقم باشد.'); if (codeInput) codeInput.focus(); return; }
			verifying = true;
			busy(true, 'در حال بررسی کد…');
			var data = new FormData();
			data.append('action', 'szc_portal_otp_verify');
			data.append('nonce', nonce);
			data.append('mobile', mobileInput ? mobileInput.value : '');
			data.append('code', code);
			fetch(CFG.ajax || '', { method: 'POST', credentials: 'same-origin', body: data })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (res && res.success) {
						completeLogin(res);
					} else {
						say((res && res.data && res.data.msg) || 'کد ورود صحیح نیست.');
						verifying = false;
						busy(false, 'تأیید کد و ورود');
						if (res && res.data && res.data.expired && resend) resend.disabled = false;
					}
				})
				.catch(function () { verifying = false; say('خطای ارتباط با سرور.'); busy(false, 'تأیید کد و ورود'); });
		}
		function passwordLogin() {
			if (verifying || method !== 'password') return;
			var mobile = mobileInput ? mobileInput.value.trim() : '';
			var password = passwordInput ? passwordInput.value : '';
			if (!mobile) { say('شماره موبایل را وارد کنید.'); if (mobileInput) mobileInput.focus(); return; }
			if (!password) { say('رمز ثابت را وارد کنید.'); if (passwordInput) passwordInput.focus(); return; }
			verifying = true;
			busy(true, 'در حال بررسی اطلاعات…');
			var data = new FormData();
			data.append('action', 'szc_portal_password_login');
			data.append('nonce', nonce);
			data.append('mobile', mobile);
			data.append('password', password);
			fetch(CFG.ajax || '', { method: 'POST', credentials: 'same-origin', body: data })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (res && res.success) { completeLogin(res); return; }
					verifying = false;
					say((res && res.data && res.data.msg) || 'شماره موبایل یا رمز عبور صحیح نیست.');
					busy(false, 'ورود با رمز ثابت');
				})
				.catch(function () { verifying = false; say('ارتباط با سرور برقرار نشد. دوباره تلاش کنید.'); busy(false, 'ورود با رمز ثابت'); });
		}
		f.addEventListener('submit', function (event) {
			event.preventDefault();
			if (method === 'password') { passwordLogin(); return; }
			if (step === 'mobile') { requestCode(); return; }
			verifyCode();
		});
		if (codeInput) codeInput.addEventListener('input', function () {
			var normalized = normalizeDigits(codeInput.value);
			if (codeInput.value !== normalized) codeInput.value = normalized;
			clearTimeout(autoVerifyTimer);
			if (normalized.length === 6) autoVerifyTimer = setTimeout(verifyCode, 180);
		});
		if (resend) resend.addEventListener('click', requestCode);
		methodButtons.forEach(function (button) { button.addEventListener('click', function () { selectMethod(button.getAttribute('data-login-method')); }); });
		if (passwordToggle) passwordToggle.addEventListener('click', function () {
			if (!passwordInput) return;
			var show = passwordInput.type === 'password';
			passwordInput.type = show ? 'text' : 'password';
			passwordToggle.setAttribute('aria-pressed', show ? 'true' : 'false');
			passwordToggle.setAttribute('aria-label', show ? 'پنهان‌کردن رمز' : 'نمایش رمز');
		});
		if (change) change.addEventListener('click', function () {
			clearInterval(timer);
			if (otpController) otpController.abort();
			verifying = false;
			selectMethod('otp');
			if (mobileInput) mobileInput.focus();
		});
	})();

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
		if (liveRequest && typeof liveRequest.abort === 'function') liveRequest.abort();
		var controller = ('AbortController' in window) ? new AbortController() : null;
		liveRequest = controller;
		var sequence = ++liveSequence;
		cur.classList.add('is-loading');
		var d = new FormData();
		d.append('action', 'szc_portal_view');
		d.append('nonce', CFG.nonce || '');
		d.append('view', 'contacts');
		Object.keys(params || {}).forEach(function (k) { d.append('params[' + k + ']', params[k]); });
		var fetchOptions = { method: 'POST', credentials: 'same-origin', body: d };
		if (controller) fetchOptions.signal = controller.signal;
		fetch(CFG.ajax || '', fetchOptions)
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (sequence !== liveSequence || !cur.isConnected) return;
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
				else history.replaceState({ szc: true, view: 'contacts', params: params || {} }, '', viewUrl('contacts', params));
				if (wasSearch) {
					var ni = cur.querySelector('[data-live-search]');
					if (ni) { ni.focus(); try { var v = ni.value; ni.setSelectionRange(v.length, v.length); } catch (e) {} }
				}
				enhance();
			})
			.catch(function (err) {
				if (err && err.name === 'AbortError') return;
				if (sequence === liveSequence && cur.isConnected) cur.classList.remove('is-loading');
			});
	}
	var liveTimer = null;
	var liveRequest = null;
	var liveSequence = 0;
	document.addEventListener('input', function (e) {
		var inp = e.target.closest('[data-live-search]');
		if (!inp || !inp.closest('.szc-portal')) return;
		clearTimeout(liveTimer);
		liveTimer = setTimeout(function () {
			var f = inp.closest('form');
			if (!f) return;
			var params = {};
			new FormData(f).forEach(function (v, k) { if (k !== 'szc_view' && v !== '') params[k] = v; });
			// هر حرف یک تاریخچه‌ی جدا نسازد؛ URL و نتایج فقط با آخرین عبارت همگام شوند.
			partialContacts(params, false);
		}, 350);
	});

	/* ==================== هدفِ روزانه‌ی تماس ==================== */
	function updateGoalRing() {
		var el = qs('.szc-p-goal');
		if (!el) return;
		var done = parseInt(el.getAttribute('data-goal-calls') || '0', 10);
		var target = parseInt(el.getAttribute('data-goal-target-value') || '0', 10);
		var pct = target > 0 ? Math.max(0, Math.min(100, Math.round(done / target * 100))) : 0;
		var C = 2 * Math.PI * 19;
		var fg = el.querySelector('[data-goal-fg]');
		if (fg) fg.style.strokeDashoffset = String(C * (1 - pct / 100));
		var p = el.querySelector('[data-goal-pct]'); if (p) p.textContent = faD(pct) + '٪';
		var t = el.querySelector('[data-goal-target]'); if (t) t.textContent = faD(target);
		var d = el.querySelector('[data-goal-done]'); if (d) d.textContent = faD(done);
		el.classList.toggle('is-done', done >= target);
	}

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
			+ '<p class="szc-p-muted">ثبت کوتاه و دقیق نتیجه، پیگیری بعدی را برای خودت و تیم روشن می‌کند.</p>'
			+ '<label class="szc-p-control"><span>نتیجه تماس</span><select data-cs-outcome>' + opts + '</select></label>'
			+ '<label class="szc-p-control"><span>خلاصه مکالمه <small>اختیاری</small></span><textarea data-cs-note rows="2" placeholder="نکته مهم گفتگو را بنویسید…"></textarea></label>'
			+ '<label class="szc-p-check" style="margin-bottom:12px"><input type="checkbox" data-cs-sms> ارسال پیامک تشکر</label>'
			+ '<div class="szc-p-btnrow"><button class="szc-p-btn szc-p-btn-primary" data-cs-save data-id="' + pc.id + '">ثبت نتیجه تماس</button><button class="szc-p-btn" data-cs-cancel>بعداً</button></div>';
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
	var lastAlertSignature = '';
	function pollTodayAlerts() {
		if (!qs('.szc-portal') || !CFG.nonce) return;
		crmPost('szc_today_alerts', {}, function (res) {
			if (!res || !res.success || !res.data) return;
			var d = res.data, signature = [d.due, d.upcoming, d.forgotten].join(':');
			if (!d.message || signature === lastAlertSignature) return;
			lastAlertSignature = signature;
			toast(d.message, d.due > 0 ? 'err' : 'ok');
			if ('Notification' in window && Notification.permission === 'granted') {
				try { new Notification('میزکار امروز سازان CRM', { body: d.message, tag: 'szc-today-alerts' }); } catch (e) {}
			}
		});
	}
	window.setInterval(pollTodayAlerts, 2 * 60 * 1000);
	document.addEventListener('click', function (e) {
		var inst = e.target.closest('[data-pwa-install]');
		if (inst && deferredPrompt) { deferredPrompt.prompt(); deferredPrompt.userChoice.then(function () { deferredPrompt = null; inst.hidden = true; showPwaBar(); }); return; }
		var noti = e.target.closest('[data-notify-enable]');
		if (noti && 'Notification' in window) { Notification.requestPermission().then(function (p) { noti.hidden = true; showPwaBar(); if (p === 'granted') maybeNotifyOverdue(); }); }
	});

	/* ==================== اجرای مجددِ بهبودها پس از هر رندر ==================== */
	function enhance() {
		applyPermissions();
		updateGoalRing();
		showPwaBar();
		maybeNotifyOverdue();
		var lm = qs('[data-loadmore]');
		if (lm && moreObserver) moreObserver.observe(lm);
	}

	function applyPermissions() {
		var p = CFG.permissions || {};
		var cardRules = {
			calls: ['log_call', 'dialer_call'],
			followups: ['add_followup'],
			sms: ['send_sms', 'schedule_sms', 'custom_sms'],
			notes: ['add_note'],
			sequences: ['enroll'],
			merge: ['merge']
		};
		Object.keys(cardRules).forEach(function (permission) {
			if (p[permission]) return;
			cardRules[permission].forEach(function (action) {
				document.querySelectorAll('[data-szc-act="' + action + '"]').forEach(function (el) {
					var card = el.closest('.szc-p-card');
					if (card) card.hidden = true; else el.hidden = true;
				});
			});
		});
		var actionRules = {
			delete_contact: ['del_contact'],
			blacklist: ['blacklist'],
			move_groups: ['set_group'],
			manage_groups: ['group_create', 'group_rename', 'group_delete'],
			notes: ['del_note'],
			calls: ['del_activity']
		};
		Object.keys(actionRules).forEach(function (permission) {
			if (p[permission]) return;
			actionRules[permission].forEach(function (action) {
				document.querySelectorAll('[data-szc-act="' + action + '"]').forEach(function (el) { el.hidden = true; });
			});
		});
		if (!p.manage_groups) {
			document.querySelectorAll('[data-folder-act="create"], [data-folder-act="rename"], [data-folder-act="delete"]').forEach(function (el) { el.hidden = true; });
		}
		if (!p.edit_contact) {
			document.querySelectorAll('[data-folder-act="bulk-stage"]').forEach(function (el) { el.hidden = true; });
		}
		if (!p.bulk_sms) {
			document.querySelectorAll('[data-folder-act="bulk-sms"]').forEach(function (el) { el.hidden = true; });
			document.querySelectorAll('[data-sel-act="sms"]').forEach(function (el) {
				var group = el.closest('.szc-p-selgroup'); if (group) group.hidden = true; else el.hidden = true;
			});
		}
		if (!p.move_groups) {
			document.querySelectorAll('[data-sel-act="move"]').forEach(function (el) {
				var group = el.closest('.szc-p-selgroup'); if (group) group.hidden = true; else el.hidden = true;
			});
		}
		if (!p.edit_contact) {
			document.querySelectorAll('[data-szc-act="set_field"], [data-szc-act="save_contact"]').forEach(function (el) { el.hidden = true; });
			document.querySelectorAll('.szc-p-form2 input, .szc-p-form2 select, .szc-p-form2 textarea').forEach(function (el) { el.disabled = true; });
		}
		if (!p.followups) {
			document.querySelectorAll('[data-call-followup-at], [data-call-followup-note]').forEach(function (el) { el.hidden = true; });
		}
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
		pollTodayAlerts();
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
