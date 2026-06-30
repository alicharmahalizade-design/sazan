(function ($) {
	'use strict';

	// ---- repeater add / remove ----
	$(document).on('click', '.szp-rep-add', function () {
		var box = $(this).closest('.szp-rep');
		var tpl = box.find('.szp-rep-tpl').html();
		box.find('.szp-rep-rows').first().append(tpl);
	});
	$(document).on('click', '.szp-rep-del', function () {
		$(this).closest('.szp-rep-row').remove();
	});

	// ---- single media ----
	$(document).on('click', '.szp-media-pick', function (e) {
		e.preventDefault();
		var wrap = $(this).closest('.szp-media');
		var frame = wp.media({ title: SZP_ADMIN.media_title, button: { text: SZP_ADMIN.media_button }, multiple: false });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			wrap.find('input[type=hidden]').val(a.id);
			wrap.find('.szp-media-name').text(a.title || a.filename);
		});
		frame.open();
	});
	$(document).on('click', '.szp-media-clear', function (e) {
		e.preventDefault();
		var wrap = $(this).closest('.szp-media');
		wrap.find('input[type=hidden]').val('');
		wrap.find('.szp-media-name').text('');
	});

	// ---- pick from media library, write the URL into the adjacent field ----
	$(document).on('click', '.szp-media-url-pick', function (e) {
		e.preventDefault();
		var row = $(this).closest('.szp-rep-row');
		var target = row.find('.szp-audio-url');
		var frame = wp.media({ title: SZP_ADMIN.media_title, button: { text: SZP_ADMIN.media_button }, multiple: false });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			target.val(a.url);
			var title = row.find('input[name$="[title][]"]');
			if (title.length && !title.val()) title.val(a.title || a.filename || '');
		});
		frame.open();
	});

	// ---- gallery (multiple) ----
	$(document).on('click', '.szp-gallery-pick', function (e) {
		e.preventDefault();
		var wrap = $(this).closest('.szp-gallery');
		var frame = wp.media({ title: SZP_ADMIN.media_title, button: { text: SZP_ADMIN.media_button }, multiple: true });
		frame.on('select', function () {
			var sel = frame.state().get('selection').toJSON();
			var input = wrap.find('input[type=hidden]');
			var ids = input.val() ? input.val().split(',').filter(Boolean) : [];
			sel.forEach(function (a) {
				if (ids.indexOf(String(a.id)) === -1) {
					ids.push(String(a.id));
					wrap.find('.szp-gallery-items').append(
						'<span class="szp-gallery-item" data-id="' + a.id + '">' +
						$('<i>').text(a.title || a.filename).html() +
						' <a href="#" class="szp-gallery-del">×</a></span>'
					);
				}
			});
			input.val(ids.join(','));
		});
		frame.open();
	});
	$(document).on('click', '.szp-gallery-del', function (e) {
		e.preventDefault();
		var item = $(this).closest('.szp-gallery-item');
		var wrap = $(this).closest('.szp-gallery');
		var id = String(item.data('id'));
		var input = wrap.find('input[type=hidden]');
		var ids = input.val().split(',').filter(function (x) { return x && x !== id; });
		input.val(ids.join(','));
		item.remove();
	});

	// ---- user search ----
	var timer;
	$(document).on('input', '.szp-user-q', function () {
		var q = $(this).val();
		var box = $(this).closest('.szp-user-search');
		var res = box.find('.szp-user-results');
		clearTimeout(timer);
		if (q.length < 2) { res.empty(); return; }
		timer = setTimeout(function () {
			$.getJSON(SZP_ADMIN.ajax, { action: 'szp_user_search', nonce: SZP_ADMIN.nonce, q: q }, function (r) {
				res.empty();
				if (r.success && r.data.length) {
					r.data.forEach(function (u) {
						$('<div class="szp-ur"></div>')
							.attr('data-id', u.id)
							.attr('data-text', u.text)
							.data('u', u)
							.text(u.text)
							.appendTo(res);
					});
				} else {
					res.html('<div class="szp-ur szp-ur-none">نتیجه‌ای یافت نشد</div>');
				}
			});
		}, 300);
	});
	// Renumber rows + refresh count / empty-state for the members table.
	function szpRefreshMembers(box) {
		var rows = box.find('.szp-members-body tr');
		rows.each(function (i) { $(this).find('.szp-mrow-n').text(faDigits(i + 1)); });
		box.find('.szp-members-empty').toggle(rows.length === 0);
		var cnt = box.closest('form').find('.szp-members-count');
		if (cnt.length) cnt.text('(' + faDigits(rows.length) + ' نفر)');
	}

	$(document).on('click', '.szp-ur', function () {
		if ($(this).hasClass('szp-ur-none')) return;
		var box = $(this).closest('.szp-user-search');
		var name = box.data('target');
		var id = String($(this).data('id'));
		var text = $(this).attr('data-text');
		var clear = function () { box.find('.szp-user-results').empty(); box.find('.szp-user-q').val(''); };

		// --- "go" mode: navigate to a per-user admin page ---
		var go = box.attr('data-go');
		if (go) {
			window.location = go + (go.indexOf('?') === -1 ? '?' : '&') + 'user=' + encodeURIComponent(id);
			return;
		}

		// --- table mode (group members with full info) ---
		var body = box.find('.szp-members-body');
		if (body.length) {
			if (body.find('tr[data-id="' + id + '"]').length) { clear(); return; }
			var u = $(this).data('u') || {};
			var tr = $('<tr></tr>').attr('data-id', id);
			$('<td class="szp-mrow-n"></td>').appendTo(tr);
			$('<td></td>').text(u.first || u.name || text).appendTo(tr);
			$('<td></td>').text(u.last || '').appendTo(tr);
			$('<td></td>').text(u.mobile ? faDigits(u.mobile) : '—').appendTo(tr);
			$('<td></td>').text(u.email || '').appendTo(tr);
			var td = $('<td></td>');
			$('<input type="hidden">').attr('name', name + '[]').val(id).appendTo(td);
			$('<a href="#" class="szp-member-del" title="حذف از گروه">×</a>').appendTo(td);
			td.appendTo(tr);
			body.append(tr);
			szpRefreshMembers(box);
			clear();
			return;
		}

		// --- chip mode (default) ---
		var chips = box.find('.szp-user-chips');
		if (chips.find('.szp-chip[data-id="' + id + '"]').length) { clear(); return; }
		var chip = $('<span class="szp-chip"></span>').attr('data-id', id);
		$('<input type="hidden">').attr('name', name + '[]').val(id).appendTo(chip);
		chip.append(document.createTextNode(' ' + text + ' '));
		$('<a href="#" class="szp-chip-del">×</a>').appendTo(chip);
		chips.append(chip);
		clear();
	});
	$(document).on('click', '.szp-chip-del', function (e) {
		e.preventDefault();
		$(this).closest('.szp-chip').remove();
	});
	$(document).on('click', '.szp-member-del', function (e) {
		e.preventDefault();
		var box = $(this).closest('.szp-user-search');
		$(this).closest('tr').remove();
		szpRefreshMembers(box);
	});

	/* ============ Jalali date + time picker ============ */
	function faDigits(s) {
		return String(s).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
	}
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
	function pad2(n) { return (n < 10 ? '0' : '') + n; }
	var jMonths = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
	var jDow = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج']; // Saturday-first

	function fmtDisplay(jy, jm, jd, hh, mm) {
		return faDigits(jd + ' ' + jMonths[jm] + ' ' + jy + ' - ' + pad2(hh) + ':' + pad2(mm));
	}

	function buildPicker($wrap) {
		var $hidden = $wrap.find('.szp-jdt-value');
		var $disp = $wrap.find('.szp-jdt-display');
		var sel = null; // [jy,jm,jd]
		var hh = 9, mm = 0, view;

		// init from stored Gregorian value
		var v = $hidden.val();
		if (v) {
			var d = new Date(v.replace('T', ' ').replace(/-/g, '/'));
			if (!isNaN(d.getTime())) {
				sel = g2j(d.getFullYear(), d.getMonth() + 1, d.getDate());
				hh = d.getHours(); mm = d.getMinutes();
				$disp.val(fmtDisplay(sel[0], sel[1], sel[2], hh, mm));
			}
		}
		var today = g2j(new Date().getFullYear(), new Date().getMonth() + 1, new Date().getDate());
		view = sel ? [sel[0], sel[1]] : [today[0], today[1]];

		var $pop = $('<div class="szp-jdt-pop"></div>').hide();
		$wrap.append($pop);

		function renderGrid() {
			var jy = view[0], jm = view[1];
			var g1 = j2g(jy, jm, 1);
			var dow = (new Date(g1[0], g1[1] - 1, g1[2]).getDay() + 1) % 7; // Sat=0
			var total = jDays(jy, jm);
			var html = '';
			html += '<div class="szp-jdt-nav">';
			html += '<button type="button" class="szp-jdt-pm">‹</button>';
			html += '<span>' + jMonths[jm] + ' ' + faDigits(jy) + '</span>';
			html += '<button type="button" class="szp-jdt-nm">›</button>';
			html += '</div><div class="szp-jdt-grid">';
			for (var d = 0; d < 7; d++) html += '<span class="szp-jdt-dow">' + jDow[d] + '</span>';
			for (var i = 0; i < dow; i++) html += '<span></span>';
			for (var day = 1; day <= total; day++) {
				var on = sel && sel[0] === jy && sel[1] === jm && sel[2] === day;
				html += '<button type="button" class="szp-jdt-day' + (on ? ' on' : '') + '" data-d="' + day + '">' + faDigits(day) + '</button>';
			}
			html += '</div>';
			// time
			html += '<div class="szp-jdt-time"><span>ساعت</span>';
			html += '<select class="szp-jdt-h">';
			for (var h = 0; h < 24; h++) html += '<option value="' + h + '"' + (h === hh ? ' selected' : '') + '>' + faDigits(pad2(h)) + '</option>';
			html += '</select> : <select class="szp-jdt-m">';
			for (var m = 0; m < 60; m++) html += '<option value="' + m + '"' + (m === mm ? ' selected' : '') + '>' + faDigits(pad2(m)) + '</option>';
			html += '</select></div>';
			html += '<div class="szp-jdt-foot"><button type="button" class="button button-primary szp-jdt-ok">تأیید</button>';
			html += '<button type="button" class="button szp-jdt-clear">پاک کردن</button></div>';
			$pop.html(html);
		}

		$disp.on('click', function () { renderGrid(); $pop.toggle(); });

		$pop.on('click', '.szp-jdt-pm', function () { if (--view[1] < 1) { view[1] = 12; view[0]--; } renderGrid(); });
		$pop.on('click', '.szp-jdt-nm', function () { if (++view[1] > 12) { view[1] = 1; view[0]++; } renderGrid(); });
		$pop.on('click', '.szp-jdt-day', function () { sel = [view[0], view[1], parseInt($(this).data('d'), 10)]; $pop.find('.szp-jdt-day').removeClass('on'); $(this).addClass('on'); });
		$pop.on('change', '.szp-jdt-h', function () { hh = parseInt(this.value, 10); });
		$pop.on('change', '.szp-jdt-m', function () { mm = parseInt(this.value, 10); });
		$pop.on('click', '.szp-jdt-clear', function () { sel = null; $hidden.val(''); $disp.val(''); $pop.hide(); });
		$pop.on('click', '.szp-jdt-ok', function () {
			if (!sel) { $pop.hide(); return; }
			var g = j2g(sel[0], sel[1], sel[2]);
			$hidden.val(g[0] + '-' + pad2(g[1]) + '-' + pad2(g[2]) + 'T' + pad2(hh) + ':' + pad2(mm));
			$disp.val(fmtDisplay(sel[0], sel[1], sel[2], hh, mm));
			$pop.hide();
		});
		// close on outside click
		$(document).on('mousedown', function (e) {
			if (!$wrap[0].contains(e.target)) $pop.hide();
		});
	}

	$('.szp-jdt').each(function () { buildPicker($(this)); });

})(jQuery);
