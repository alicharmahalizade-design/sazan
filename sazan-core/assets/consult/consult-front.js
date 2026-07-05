/* فرم مشاوره کسب‌وکار سازان — تقویم شمسی خودبسنده + ارسال AJAX */
(function () {
  'use strict';

  var FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
  var MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
  var WEEK = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج']; // شنبه..جمعه

  function fa(n) {
    return String(n).replace(/\d/g, function (d) { return FA[d]; });
  }

  /* ---- تبدیل میلادی <-> شمسی (الگوریتم خودبسنده) ---- */
  function div(a, b) { return Math.floor(a / b); }

  function g2j(gy, gm, gd) {
    var gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    var gy2 = (gm > 2) ? (gy + 1) : gy;
    var days = 355666 + (365 * gy) + div(gy2 + 3, 4) - div(gy2 + 99, 100) + div(gy2 + 399, 400) + gd + gdm[gm - 1];
    var jy = -1595 + (33 * div(days, 12053));
    days %= 12053;
    jy += 4 * div(days, 1461);
    days %= 1461;
    if (days > 365) { jy += div(days - 1, 365); days = (days - 1) % 365; }
    var jm = (days < 186) ? 1 + div(days, 31) : 7 + div(days - 186, 30);
    var jd = 1 + ((days < 186) ? (days % 31) : ((days - 186) % 30));
    return [jy, jm, jd];
  }

  function j2g(jy, jm, jd) {
    jy += 1595;
    var days = -355668 + (365 * jy) + (div(jy, 33) * 8) + div((jy % 33) + 3, 4) + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
    var gy = 400 * div(days, 146097);
    days %= 146097;
    if (days > 36524) {
      gy += 100 * div(--days, 36524);
      days %= 36524;
      if (days >= 365) days++;
    }
    gy += 4 * div(days, 1461);
    days %= 1461;
    if (days > 365) { gy += div(days - 1, 365); days = (days - 1) % 365; }
    var gd = days + 1;
    var sal_a = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    var gm = 0;
    for (gm = 1; gm <= 12; gm++) {
      if (gd <= sal_a[gm]) break;
      gd -= sal_a[gm];
    }
    return [gy, gm, gd];
  }

  function jMonthLen(jy, jm) {
    if (jm <= 6) return 31;
    if (jm <= 11) return 30;
    // اسفند: بررسی کبیسه با تبدیل
    var g = j2g(jy, 12, 30);
    var back = g2j(g[0], g[1], g[2]);
    return (back[1] === 12 && back[2] === 30) ? 30 : 29;
  }

  function jDayOfWeek(jy, jm, jd) {
    var g = j2g(jy, jm, jd);
    var d = new Date(g[0], g[1] - 1, g[2]).getDay(); // 0=یکشنبه
    return (d + 1) % 7; // 0=شنبه
  }

  function pad(n) { return (n < 10 ? '0' : '') + n; }

  /* ---- ساخت تقویم برای یک فرم ---- */
  function initCalendar(root) {
    var display = root.querySelector('.szc-date-display');
    var gIn = root.querySelector('.szc-gdate');
    var jIn = root.querySelector('.szc-jdate');
    var cal = root.querySelector('.szc-cal');
    if (!display || !cal) return;

    var today = new Date();
    var tj = g2j(today.getFullYear(), today.getMonth() + 1, today.getDate());
    var viewY = tj[0], viewM = tj[1];
    var selKey = null;

    function todayKey() { return tj[0] + '-' + tj[1] + '-' + tj[2]; }

    function render() {
      var len = jMonthLen(viewY, viewM);
      var startDow = jDayOfWeek(viewY, viewM, 1);
      var html = '<div class="szc-cal-head">';
      html += '<button type="button" class="szc-nav" data-dir="-1">‹</button>';
      html += '<span>' + MONTHS[viewM - 1] + ' ' + fa(viewY) + '</span>';
      html += '<button type="button" class="szc-nav" data-dir="1">›</button></div>';
      html += '<div class="szc-cal-grid">';
      for (var w = 0; w < 7; w++) html += '<span class="szc-dow">' + WEEK[w] + '</span>';
      for (var i = 0; i < startDow; i++) html += '<span></span>';
      for (var d = 1; d <= len; d++) {
        var key = viewY + '-' + viewM + '-' + d;
        var g = j2g(viewY, viewM, d);
        var isPast = new Date(g[0], g[1] - 1, g[2]) < new Date(today.getFullYear(), today.getMonth(), today.getDate());
        var cls = 'szc-day';
        if (key === todayKey()) cls += ' is-today';
        if (key === selKey) cls += ' is-sel';
        if (isPast) cls += ' is-past';
        html += '<button type="button" class="' + cls + '"' + (isPast ? ' disabled' : '') +
          ' data-y="' + viewY + '" data-m="' + viewM + '" data-d="' + d + '">' + fa(d) + '</button>';
      }
      html += '</div>';
      cal.innerHTML = html;
    }

    display.addEventListener('click', function () {
      cal.hidden = !cal.hidden;
      if (!cal.hidden) render();
    });

    cal.addEventListener('click', function (e) {
      var nav = e.target.closest('.szc-nav');
      if (nav) {
        viewM += parseInt(nav.getAttribute('data-dir'), 10);
        if (viewM > 12) { viewM = 1; viewY++; }
        if (viewM < 1) { viewM = 12; viewY--; }
        render();
        return;
      }
      var day = e.target.closest('.szc-day');
      if (!day || day.disabled) return;
      var y = +day.getAttribute('data-y'), m = +day.getAttribute('data-m'), d = +day.getAttribute('data-d');
      selKey = y + '-' + m + '-' + d;
      var g = j2g(y, m, d);
      gIn.value = g[0] + '-' + pad(g[1]) + '-' + pad(g[2]);
      jIn.value = fa(d) + ' ' + MONTHS[m - 1] + ' ' + fa(y);
      display.value = jIn.value;
      cal.hidden = true;
      root.dispatchEvent(new CustomEvent('szc:date', { detail: gIn.value }));
    });

    document.addEventListener('click', function (e) {
      if (!cal.hidden && !root.contains(e.target)) cal.hidden = true;
    });
  }

  /* ---- ارسال فرم ---- */
  function initForm(root) {
    var form = root.querySelector('.szc-form');
    if (!form) return;
    var msg = root.querySelector('.szc-msg');
    var done = root.querySelector('.szc-done');
    var btn = root.querySelector('.szc-submit');
    var cfg = window.SazanConsult || {};
    var slotBox = root.querySelector('.szc-slots');
    var otpBox = root.querySelector('.szc-otp');
    var otpSent = false;

    /* بارگذاری ظرفیت ساعت‌ها هنگام انتخاب تاریخ */
    root.addEventListener('szc:date', function (e) {
      loadSlots(e.detail);
    });

    function loadSlots(gdate) {
      if (!slotBox || !gdate) return;
      var data = new FormData();
      data.append('action', 'sazan_consult_slots');
      data.append('nonce', cfg.nonce || '');
      data.append('gdate', gdate);
      fetch(cfg.ajax, { method: 'POST', body: data, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res || !res.success) return;
          var taken = res.data.taken || {};
          var cap = cfg.slotCap || 1;
          var dayFull = !!res.data.full;
          slotBox.querySelectorAll('.szc-slot').forEach(function (lab) {
            var inp = lab.querySelector('input');
            var cnt = taken[inp.value] || 0;
            var full = dayFull || cnt >= cap;
            inp.disabled = full;
            lab.classList.toggle('is-full', full);
            if (full && inp.checked) inp.checked = false;
          });
          if (dayFull) showErr('ظرفیت این روز تکمیل است؛ روز دیگری انتخاب کنید.');
        })
        .catch(function () {});
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      msg.hidden = true;
      msg.className = 'szc-msg';

      var data = new FormData(form);
      if (!data.get('gdate')) { return showErr('لطفاً تاریخ مشاوره را انتخاب کنید.'); }
      if (!data.get('time')) { return showErr('لطفاً ساعت مشاوره را انتخاب کنید.'); }

      // مرحله‌ی تایید شماره (OTP)
      if ((cfg.otp | 0) === 1 && otpBox && !otpSent) {
        var phone = (data.get('phone') || '').replace(/\D/g, '');
        if (!/^0?9\d{9}$/.test(phone)) { return showErr('ابتدا شماره تماس معتبر وارد کنید.'); }
        return sendCode(phone);
      }

      submit(data);
    });

    function sendCode(phone) {
      var data = new FormData();
      data.append('action', 'sazan_consult_send_code');
      data.append('nonce', cfg.nonce || '');
      data.append('phone', phone);
      btn.disabled = true; btn.classList.add('is-loading');
      fetch(cfg.ajax, { method: 'POST', body: data, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          btn.disabled = false; btn.classList.remove('is-loading');
          if (res && res.success) {
            otpSent = true;
            otpBox.hidden = false;
            btn.textContent = 'ثبت نهایی';
            msg.textContent = 'کد تایید پیامک شد. آن را وارد کنید.';
            msg.className = 'szc-msg'; msg.hidden = false;
            var oi = otpBox.querySelector('input'); if (oi) oi.focus();
          } else {
            showErr((res && res.data && res.data.msg) || 'ارسال کد ناموفق بود.');
          }
        })
        .catch(function () { btn.disabled = false; btn.classList.remove('is-loading'); showErr('ارتباط با سرور برقرار نشد.'); });
    }

    function submit(data) {
      data.append('action', 'sazan_consult_submit');
      data.append('nonce', cfg.nonce || '');
      btn.disabled = true;
      btn.classList.add('is-loading');

      fetch(cfg.ajax, { method: 'POST', body: data, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          btn.disabled = false;
          btn.classList.remove('is-loading');
          if (res && res.success) {
            form.hidden = true;
            var dm = root.querySelector('.szc-done-msg');
            if (dm) dm.textContent = root.getAttribute('data-success') || 'ثبت شد.';
            done.hidden = false;
          } else {
            showErr((res && res.data && res.data.msg) || 'خطایی رخ داد. دوباره تلاش کنید.');
          }
        })
        .catch(function () {
          btn.disabled = false;
          btn.classList.remove('is-loading');
          showErr('ارتباط با سرور برقرار نشد.');
        });
    }

    function showErr(t) {
      msg.textContent = t;
      msg.className = 'szc-msg is-err';
      msg.hidden = false;
    }
  }

  function boot() {
    document.querySelectorAll('.sazan-consult').forEach(function (root) {
      initCalendar(root);
      initForm(root);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
