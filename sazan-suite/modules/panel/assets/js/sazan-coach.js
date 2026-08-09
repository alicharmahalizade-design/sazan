(function () {
	'use strict';
	if (typeof SZP_FRONT === 'undefined') return;

	function toFa(n) { return String(n).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
	function $(s, c) { return (c || document).querySelector(s); }
	function $all(s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); }

	/* ---------------- toast ---------------- */
	function toast(msg, ok) {
		var t = $('.szp-co-toast');
		if (!t) return;
		t.textContent = msg;
		t.className = 'szp-co-toast ' + (ok === false ? 'err' : 'ok');
		t.hidden = false;
		clearTimeout(t._t);
		t._t = setTimeout(function () { t.hidden = true; }, 3200);
	}

	/* ---------------- ajax ---------------- */
	function post(action, data, btn) {
		var body = new FormData();
		body.append('action', action);
		body.append('nonce', SZP_FRONT.nonce);
		Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
		if (btn) { btn.disabled = true; btn.classList.add('loading'); }
		return fetch(SZP_FRONT.ajax, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				if (btn) { btn.disabled = false; btn.classList.remove('loading'); }
				if (j && j.success) {
					if (j.data && j.data.msg) toast(j.data.msg, true);
					if (j.data && j.data.reload) setTimeout(function () { location.reload(); }, 700);
				} else {
					toast((j && j.data && j.data.msg) || 'خطایی رخ داد.', false);
				}
				return j;
			})
			.catch(function () {
				if (btn) { btn.disabled = false; btn.classList.remove('loading'); }
				toast('ارتباط با سرور برقرار نشد.', false);
			});
	}

	/* ---------------- collectors ---------------- */
	function collectKpis(scope) {
		var rows = $all('.szp-co-kpirow', scope);
		var data = {};
		rows.forEach(function (row, i) {
			$all('[data-k]', row).forEach(function (inp) {
				data['kpis[' + i + '][' + inp.getAttribute('data-k') + ']'] = inp.value;
			});
		});
		return data;
	}

	function collectRepeater(scope, repName) {
		var box = scope.querySelector('[data-repeater="' + repName + '"]');
		var out = {};
		if (!box) return out;
		$all('.szp-co-reprow', box).forEach(function (row, i) {
			$all('[data-r]', row).forEach(function (inp) {
				out[repName + 's[' + i + '][' + inp.getAttribute('data-r') + ']'] = inp.value;
			});
		});
		return out;
	}

	/* ---------------- repeaters (add/del) ---------------- */
	document.addEventListener('click', function (e) {
		var add = e.target.closest('[data-add]');
		if (add) {
			var kind = add.getAttribute('data-add');
			if (kind === 'kpi') {
				var rows = add.closest('.szp-co-kpis').querySelector('.szp-co-kpirows');
				var clone = rows.lastElementChild.cloneNode(true);
				$all('input', clone).forEach(function (i) { i.value = ''; });
				rows.appendChild(clone);
			} else {
				var rbox = add.closest('[data-repeater]').querySelector('.szp-co-reprows');
				var c2 = rbox.lastElementChild.cloneNode(true);
				$all('input', c2).forEach(function (i) { i.value = ''; });
				rbox.appendChild(c2);
			}
			return;
		}
		var del = e.target.closest('[data-del]');
		if (del) {
			var kind2 = del.getAttribute('data-del');
			var parent = (kind2 === 'kpi') ? del.closest('.szp-co-kpirows') : del.closest('.szp-co-reprows');
			if (parent && parent.children.length > 1) del.closest(kind2 === 'kpi' ? '.szp-co-kpirow' : '.szp-co-reprow').remove();
			else { $all('input', del.parentElement).forEach(function (i) { i.value = ''; }); }
			return;
		}

		/* week accordion */
		var head = e.target.closest('[data-toggle="week"]');
		if (head) { head.closest('.szp-co-week').classList.toggle('open'); return; }

		/* scale buttons visual */
	});

	/* scale radio visual */
	document.addEventListener('change', function (e) {
		if (e.target.matches('.szp-co-scale input')) {
			$all('label', e.target.closest('.szp-co-scale')).forEach(function (l) { l.classList.remove('on'); });
			e.target.closest('label').classList.add('on');
		}
		/* course selector (coach) */
		if (e.target.matches('.szp-co-courses')) {
			var base = e.target.getAttribute('data-base');
			var sep = base.indexOf('?') >= 0 ? '&' : '?';
			location.href = base + sep + 'cc=' + e.target.value;
		}
	});

	/* ---------------- actions ---------------- */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-act]');
		if (!btn) return;
		var act = btn.getAttribute('data-act');

		if (act === 'save-scan') {
			var sec = btn.closest('.szp-co-scan');
			var data = { course_id: sec.getAttribute('data-course') };
			$all('[data-f]', sec).forEach(function (inp) { data['scan[' + inp.getAttribute('data-f') + ']'] = inp.value; });
			Object.assign(data, collectKpis(sec));
			post('szp_coach_save_scan', data, btn);
		}

		else if (act === 'submit-form') {
			var f = btn.closest('.szp-co-form');
			var data2 = { course_id: f.getAttribute('data-course') };
			$all('.szp-co-q', f).forEach(function (q) {
				var i = q.getAttribute('data-i');
				var checked = q.querySelector('input[type="radio"]:checked');
				var el = checked || q.querySelector('[data-a]');
				data2['answers[' + i + ']'] = el ? el.value : '';
			});
			post('szp_coach_submit_form', data2, btn);
		}

		else if (act === 'record') {
			var wk = btn.closest('.szp-co-week');
			var data3 = { course_id: wk.getAttribute('data-course'), week_no: btn.getAttribute('data-week') };
			$all('[data-m]', wk).forEach(function (inp) { data3['metrics[' + inp.getAttribute('data-m') + ']'] = inp.value; });
			var rep = wk.querySelector('[data-report]');
			data3.report = rep ? rep.value : '';
			post('szp_coach_record', data3, btn);
		}

		else if (act === 'toggle') {
			var wk2 = btn.closest('.szp-co-week');
			var d4 = {
				course_id: wk2.getAttribute('data-course'),
				week_no: btn.getAttribute('data-week'),
				kind: btn.getAttribute('data-kind'),
				index: btn.getAttribute('data-index')
			};
			if (btn.getAttribute('data-kind') === 'task') {
				d4.st = btn.getAttribute('data-st');
				var box = btn.closest('.szp-co-taskst');
				$all('button', box).forEach(function (b) { b.classList.remove('on'); });
				btn.classList.add('on');
				var task = btn.closest('.szp-co-task');
				task.className = 'szp-co-task st-' + d4.st;
			} else {
				btn.closest('li').classList.toggle('done');
			}
			post('szp_coach_toggle', d4);
		}

		else if (act === 'set-kpis') {
			var sec2 = btn.closest('.szp-co-kpiedit');
			var d5 = { journey_id: btn.getAttribute('data-journey') };
			Object.assign(d5, collectKpis(sec2));
			post('szp_coach_set_kpis', d5, btn);
		}

		else if (act === 'set-week') {
			var wk3 = btn.closest('.szp-co-week');
			var ed = btn.closest('.szp-co-editor');
			var d6 = {
				journey_id: wk3.getAttribute('data-journey'),
				week_no: btn.getAttribute('data-week'),
				publish: 1
			};
			d6.title = (ed.querySelector('[data-w="title"]') || {}).value || '';
			d6.session_at = (ed.querySelector('[data-w="session_at"]') || {}).value || '';
			d6.feedback = (ed.querySelector('[data-w="feedback"]') || {}).value || '';
			Object.assign(d6, collectRepeater(ed, 'action'));
			Object.assign(d6, collectRepeater(ed, 'task'));
			post('szp_coach_set_week', d6, btn);
		}

		else if (act === 'new-week') {
			post('szp_coach_new_week', { journey_id: btn.getAttribute('data-journey') }, btn);
		}
	});

	/* ---------------- count-up gauge ---------------- */
	function countUp(el) {
		var target = parseFloat(el.getAttribute('data-count')) || 0;
		var start = null, dur = 900;
		function step(ts) {
			if (!start) start = ts;
			var p = Math.min((ts - start) / dur, 1);
			var v = (target * (1 - Math.pow(1 - p, 3)));
			el.textContent = toFa(v.toFixed(p < 1 ? 1 : 1)) + '٪';
			if (p < 1) requestAnimationFrame(step);
			else el.textContent = toFa(target) + '٪';
		}
		requestAnimationFrame(step);
	}

	/* ---------------- SVG charts (self-contained) ---------------- */
	var W = 560, H = 220, PAD = 34;

	function scaleY(v, min, max) {
		if (max === min) return H - PAD - (H - 2 * PAD) / 2;
		return H - PAD - ((v - min) / (max - min)) * (H - 2 * PAD);
	}
	// RTL: first point on the right
	function scaleX(i, n) {
		if (n <= 1) return W / 2;
		return W - PAD - (i / (n - 1)) * (W - 2 * PAD);
	}

	function el(tag, attrs) {
		var e = document.createElementNS('http://www.w3.org/2000/svg', tag);
		Object.keys(attrs).forEach(function (k) { e.setAttribute(k, attrs[k]); });
		return e;
	}

	function gridAndLabels(svg, labels, min, max) {
		for (var g = 0; g <= 4; g++) {
			var y = PAD + (g / 4) * (H - 2 * PAD);
			svg.appendChild(el('line', { x1: PAD, y1: y, x2: W - PAD, y2: y, class: 'szp-cg-grid' }));
			var val = max - (g / 4) * (max - min);
			var tx = el('text', { x: W - PAD + 4, y: y + 4, class: 'szp-cg-axis' });
			tx.textContent = toFa(Math.round(val * 10) / 10);
			svg.appendChild(tx);
		}
		labels.forEach(function (lb, i) {
			var x = scaleX(i, labels.length);
			var t = el('text', { x: x, y: H - PAD + 16, class: 'szp-cg-axis', 'text-anchor': 'middle' });
			t.textContent = lb;
			svg.appendChild(t);
		});
	}

	function linePath(pts) {
		return pts.map(function (p, i) { return (i ? 'L' : 'M') + p.x + ' ' + p.y; }).join(' ');
	}

	function drawGrowth(host, data) {
		var vals = data.growth || [];
		if (!vals.length) { host.innerHTML = '<div class="szp-co-noch">پس از ثبت اولین نتایج، نمودار رشد اینجا نمایش داده می‌شود.</div>'; return; }
		var max = Math.max(100, Math.ceil(Math.max.apply(null, vals) / 10) * 10);
		var min = 0;
		var svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, class: 'szp-cg', preserveAspectRatio: 'none' });
		var defs = el('defs', {});
		defs.innerHTML = '<linearGradient id="szpcgArea" x1="0" y1="0" x2="0" y2="1">' +
			'<stop offset="0" stop-color="var(--szp-primary)" stop-opacity="0.35"/>' +
			'<stop offset="1" stop-color="var(--szp-primary)" stop-opacity="0"/></linearGradient>' +
			'<linearGradient id="szpcgLine" x1="1" y1="0" x2="0" y2="0">' +
			'<stop offset="0" stop-color="var(--szp-primary)"/><stop offset="1" stop-color="var(--szp-secondary)"/></linearGradient>';
		svg.appendChild(defs);
		gridAndLabels(svg, data.labels || [], min, max);

		var pts = vals.map(function (v, i) { return { x: scaleX(i, vals.length), y: scaleY(v, min, max) }; });
		// area
		var area = 'M' + pts[0].x + ' ' + (H - PAD) + ' ' + linePath(pts).replace('M', 'L') + ' L' + pts[pts.length - 1].x + ' ' + (H - PAD) + ' Z';
		svg.appendChild(el('path', { d: area, fill: 'url(#szpcgArea)', stroke: 'none' }));
		svg.appendChild(el('path', { d: linePath(pts), class: 'szp-cg-line', stroke: 'url(#szpcgLine)' }));
		pts.forEach(function (p, i) {
			svg.appendChild(el('circle', { cx: p.x, cy: p.y, r: 4, class: 'szp-cg-dot' }));
			var t = el('text', { x: p.x, y: p.y - 10, class: 'szp-cg-pt', 'text-anchor': 'middle' });
			t.textContent = toFa(vals[i]);
			svg.appendChild(t);
		});
		host.innerHTML = '';
		host.appendChild(svg);
	}

	function drawKpi(host, data) {
		var kp = data.kpi || {};
		var vals = (kp.values || []).map(function (v) { return v === null ? null : v; });
		var present = vals.filter(function (v) { return v !== null; });
		if (present.length < 1) { host.innerHTML = '<div class="szp-co-noch">داده‌ای ثبت نشده.</div>'; return; }
		var all = present.concat([kp.baseline, kp.target]);
		var max = Math.max.apply(null, all), min = Math.min.apply(null, all);
		var span = (max - min) || 1; max += span * 0.12; min -= span * 0.12;
		var h = 150, pad = 26;
		function sy(v) { return h - pad - ((v - min) / (max - min)) * (h - 2 * pad); }
		function sx(i, n) { return (n <= 1) ? (W / 2) : (W - pad - (i / (n - 1)) * (W - 2 * pad)); }
		var svg = el('svg', { viewBox: '0 0 ' + W + ' ' + h, class: 'szp-cg', preserveAspectRatio: 'none' });
		// target & baseline reference lines
		[['target', kp.target, 'szp-cg-target'], ['baseline', kp.baseline, 'szp-cg-base']].forEach(function (r) {
			var y = sy(r[1]);
			svg.appendChild(el('line', { x1: pad, y1: y, x2: W - pad, y2: y, class: r[2] }));
		});
		var pts = [];
		vals.forEach(function (v, i) { if (v !== null) pts.push({ x: sx(i, vals.length), y: sy(v), v: v }); });
		svg.appendChild(el('path', { d: linePath(pts), class: 'szp-cg-line', stroke: 'var(--szp-primary)' }));
		pts.forEach(function (p) {
			svg.appendChild(el('circle', { cx: p.x, cy: p.y, r: 3.5, class: 'szp-cg-dot' }));
		});
		host.innerHTML = '';
		host.appendChild(svg);
	}

	function initCharts() {
		$all('.szp-chart').forEach(function (host) {
			var raw = host.getAttribute('data-chart');
			if (!raw) return;
			var data;
			try { data = JSON.parse(raw); } catch (e) { return; }
			if (host.getAttribute('data-type') === 'kpi') drawKpi(host, data);
			else drawGrowth(host, data);
		});
	}

	/* ============ تقویم شمسی (انتخابگر تاریخ/ساعت) ============ */
	function latinDigits(s) { return String(s).replace(/[۰-۹]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); }); }
	function pad2(n) { return (n < 10 ? '0' : '') + n; }
	var jMonths = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
	var jDow = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
	function g2j(gy, gm, gd) {
		var gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
		var gy2 = (gm > 2) ? (gy + 1) : gy;
		var days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100)
			+ Math.floor((gy2 + 399) / 400) + gd + gdm[gm - 1];
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

	var jpPop = null;
	function closePop() { if (jpPop) { jpPop.remove(); jpPop = null; } }

	function openPicker(anchor, mode, init, onConfirm) {
		closePop();
		var today = g2j(new Date().getFullYear(), new Date().getMonth() + 1, new Date().getDate());
		var sel = init && init.sel ? init.sel.slice() : null;
		var hh = init && init.hh != null ? init.hh : 9;
		var mm = init && init.mm != null ? init.mm : 0;
		var view = sel ? [sel[0], sel[1]] : [today[0], today[1]];

		var pop = document.createElement('div');
		pop.className = 'szp-jp-pop';
		jpPop = pop;

		function render() {
			var jy = view[0], jm = view[1];
			var g1 = j2g(jy, jm, 1);
			var dow = (new Date(g1[0], g1[1] - 1, g1[2]).getDay() + 1) % 7;
			var total = jDays(jy, jm);
			var h = '<div class="szp-jp-nav"><button type="button" class="szp-jp-pm">‹</button>'
				+ '<span>' + jMonths[jm] + ' ' + toFa(jy) + '</span>'
				+ '<button type="button" class="szp-jp-nm">›</button></div><div class="szp-jp-grid">';
			for (var d = 0; d < 7; d++) h += '<span class="szp-jp-dow">' + jDow[d] + '</span>';
			for (var i = 0; i < dow; i++) h += '<span></span>';
			for (var day = 1; day <= total; day++) {
				var on = sel && sel[0] === jy && sel[1] === jm && sel[2] === day;
				h += '<button type="button" class="szp-jp-day' + (on ? ' on' : '') + '" data-d="' + day + '">' + toFa(day) + '</button>';
			}
			h += '</div>';
			if (mode === 'datetime') {
				h += '<div class="szp-jp-time"><span>ساعت جلسه</span>'
					+ '<input type="time" dir="ltr" class="szp-jp-tinput" value="' + pad2(hh) + ':' + pad2(mm) + '"></div>';
			}
			h += '<div class="szp-jp-foot"><button type="button" class="szp-co-btn primary szp-jp-ok">تأیید</button>'
				+ '<button type="button" class="szp-co-btn ghost szp-jp-clear">پاک کردن</button></div>';
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
			else if (t.classList.contains('szp-jp-day')) { sel = [view[0], view[1], parseInt(t.getAttribute('data-d'), 10)]; $all('.szp-jp-day', pop).forEach(function (x) { x.classList.remove('on'); }); t.classList.add('on'); }
			else if (t.classList.contains('szp-jp-clear')) { onConfirm(null); closePop(); }
			else if (t.classList.contains('szp-jp-ok')) {
				if (!sel) { closePop(); return; }
				if (mode === 'datetime') { var tv = (pop.querySelector('.szp-jp-tinput').value || '').split(':'); hh = parseInt(tv[0], 10) || 0; mm = parseInt(tv[1], 10) || 0; }
				onConfirm({ sel: sel, hh: hh, mm: mm });
				closePop();
			}
		});
	}

	// باز کردن انتخابگر با کلیک روی فیلدها (delegation، سازگار با ردیف‌های داینامیک).
	document.addEventListener('click', function (e) {
		var dt = e.target.closest('.szp-jp [data-jp="datetime"] .szp-jp-disp, .szp-jp-disp');
		if (dt && dt.closest('.szp-co-editor, .szp-co-week, .szp')) {
			e.preventDefault();
			var wrap = dt.closest('.szp-jp');
			var hidden = wrap ? wrap.querySelector('.szp-jp-val') : null;
			var init = null;
			if (hidden && hidden.value) {
				var d = new Date(hidden.value.replace('T', ' ').replace(/-/g, '/'));
				if (!isNaN(d.getTime())) init = { sel: g2j(d.getFullYear(), d.getMonth() + 1, d.getDate()), hh: d.getHours(), mm: d.getMinutes() };
			}
			openPicker(dt, 'datetime', init, function (res) {
				if (!res) { if (hidden) hidden.value = ''; dt.value = ''; return; }
				var g = j2g(res.sel[0], res.sel[1], res.sel[2]);
				if (hidden) hidden.value = g[0] + '-' + pad2(g[1]) + '-' + pad2(g[2]) + 'T' + pad2(res.hh) + ':' + pad2(res.mm);
				dt.value = toFa(res.sel[2] + ' ' + jMonths[res.sel[1]] + ' ' + res.sel[0] + ' - ' + pad2(res.hh) + ':' + pad2(res.mm));
			});
			return;
		}
		var dd = e.target.closest('.szp-jp-date');
		if (dd) {
			e.preventDefault();
			var init2 = null;
			var v = latinDigits(dd.value || '').match(/(\d{3,4})\D+(\d{1,2})\D+(\d{1,2})/);
			if (v) init2 = { sel: [parseInt(v[1], 10), parseInt(v[2], 10), parseInt(v[3], 10)] };
			openPicker(dd, 'date', init2, function (res) {
				if (!res) { dd.value = ''; return; }
				dd.value = toFa(res.sel[0] + '/' + pad2(res.sel[1]) + '/' + pad2(res.sel[2]));
			});
		}
	});
	document.addEventListener('mousedown', function (e) {
		if (jpPop && !jpPop.contains(e.target) && !e.target.closest('.szp-jp-disp, .szp-jp-date')) closePop();
	});
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePop(); });

	function init() {
		$all('.szp-co-gaugeval').forEach(countUp);
		initCharts();
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
})();
