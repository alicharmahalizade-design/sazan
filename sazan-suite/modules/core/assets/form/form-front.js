/* فرم‌ساز سازان — تقویم شمسی + پاپ‌آپ + OTP + ارسال AJAX */
(function () {
  'use strict';

  var FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
  var MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
  var WEEK = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

  function fa(n) { return String(n).replace(/\d/g, function (d) { return FA[d]; }); }
  function div(a, b) { return Math.floor(a / b); }
  function pad(n) { return (n < 10 ? '0' : '') + n; }

  function g2j(gy, gm, gd) {
    var gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    var gy2 = (gm > 2) ? (gy + 1) : gy;
    var days = 355666 + (365 * gy) + div(gy2 + 3, 4) - div(gy2 + 99, 100) + div(gy2 + 399, 400) + gd + gdm[gm - 1];
    var jy = -1595 + (33 * div(days, 12053)); days %= 12053;
    jy += 4 * div(days, 1461); days %= 1461;
    if (days > 365) { jy += div(days - 1, 365); days = (days - 1) % 365; }
    var jm = (days < 186) ? 1 + div(days, 31) : 7 + div(days - 186, 30);
    var jd = 1 + ((days < 186) ? (days % 31) : ((days - 186) % 30));
    return [jy, jm, jd];
  }
  function j2g(jy, jm, jd) {
    jy += 1595;
    var days = -355668 + (365 * jy) + (div(jy, 33) * 8) + div((jy % 33) + 3, 4) + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
    var gy = 400 * div(days, 146097); days %= 146097;
    if (days > 36524) { gy += 100 * div(--days, 36524); days %= 36524; if (days >= 365) days++; }
    gy += 4 * div(days, 1461); days %= 1461;
    if (days > 365) { gy += div(days - 1, 365); days = (days - 1) % 365; }
    var gd = days + 1;
    var sal = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    var gm = 0; for (gm = 1; gm <= 12; gm++) { if (gd <= sal[gm]) break; gd -= sal[gm]; }
    return [gy, gm, gd];
  }
  function jLen(jy, jm) {
    if (jm <= 6) return 31; if (jm <= 11) return 30;
    var g = j2g(jy, 12, 30), b = g2j(g[0], g[1], g[2]);
    return (b[1] === 12 && b[2] === 30) ? 30 : 29;
  }
  function jDow(jy, jm, jd) { var g = j2g(jy, jm, jd); return (new Date(g[0], g[1] - 1, g[2]).getDay() + 1) % 7; }

  /* ---- تقویم برای یک فیلد تاریخ ---- */
  function initDate(wrap) {
    var display = wrap.querySelector('.szf-date-display');
    var gIn = wrap.querySelector('.szf-gdate');
    var jIn = wrap.querySelector('.szf-jdate');
    var cal = wrap.querySelector('.szf-cal');
    if (!display || !cal) return;
    var today = new Date();
    var tj = g2j(today.getFullYear(), today.getMonth() + 1, today.getDate());
    var vY = tj[0], vM = tj[1], sel = null;
    function tk() { return tj[0] + '-' + tj[1] + '-' + tj[2]; }
    function render() {
      var len = jLen(vY, vM), start = jDow(vY, vM, 1);
      var h = '<div class="szf-cal-head"><button type="button" class="szf-nav" data-d="-1">‹</button>';
      h += '<span>' + MONTHS[vM - 1] + ' ' + fa(vY) + '</span>';
      h += '<button type="button" class="szf-nav" data-d="1">›</button></div><div class="szf-cal-grid">';
      for (var w = 0; w < 7; w++) h += '<span class="szf-dow">' + WEEK[w] + '</span>';
      for (var i = 0; i < start; i++) h += '<span></span>';
      for (var d = 1; d <= len; d++) {
        var key = vY + '-' + vM + '-' + d, g = j2g(vY, vM, d);
        var past = new Date(g[0], g[1] - 1, g[2]) < new Date(today.getFullYear(), today.getMonth(), today.getDate());
        var c = 'szf-day';
        if (key === tk()) c += ' is-today';
        if (key === sel) c += ' is-sel';
        if (past) c += ' is-past';
        h += '<button type="button" class="' + c + '"' + (past ? ' disabled' : '') + ' data-y="' + vY + '" data-m="' + vM + '" data-dd="' + d + '">' + fa(d) + '</button>';
      }
      cal.innerHTML = h + '</div>';
    }
    display.addEventListener('click', function () { cal.hidden = !cal.hidden; if (!cal.hidden) render(); });
    cal.addEventListener('click', function (e) {
      var nav = e.target.closest('.szf-nav');
      if (nav) { vM += parseInt(nav.getAttribute('data-d'), 10); if (vM > 12) { vM = 1; vY++; } if (vM < 1) { vM = 12; vY--; } render(); return; }
      var day = e.target.closest('.szf-day'); if (!day || day.disabled) return;
      var y = +day.getAttribute('data-y'), m = +day.getAttribute('data-m'), dd = +day.getAttribute('data-dd');
      sel = y + '-' + m + '-' + dd;
      var g = j2g(y, m, dd);
      gIn.value = g[0] + '-' + pad(g[1]) + '-' + pad(g[2]);
      jIn.value = fa(dd) + ' ' + MONTHS[m - 1] + ' ' + fa(y);
      display.value = jIn.value; cal.hidden = true;
    });
    document.addEventListener('click', function (e) { if (!cal.hidden && !wrap.contains(e.target)) cal.hidden = true; });
  }

  /* ---- منطق ارسال یک فرم ---- */
  function initForm(root) {
    if (root.dataset.szfInit) return;
    root.dataset.szfInit = '1';
    var form = root.querySelector('.szf-form'); if (!form) return;
    var cfg = window.SazanForm || {};
    var msg = root.querySelector('.szf-msg');
    var done = root.querySelector('.szf-done');
    var btn = root.querySelector('.szf-submit');
    var otpBox = root.querySelector('.szf-otp');
    var otp = (root.getAttribute('data-otp') | 0) === 1;
    var phoneKey = root.getAttribute('data-phone') || '';
    var otpSent = false;

    root.querySelectorAll('.szf-date').forEach(initDate);

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      hideMsg();
      var data = new FormData(form);

      // اعتبارسنجی فیلدهای اجباری مرورگر
      if (!form.checkValidity()) { return showErr('لطفاً فیلدهای الزامی را کامل کنید.'); }

      if (otp && otpBox && !otpSent) {
        var phone = (data.get(phoneKey) || '').replace(/\D/g, '');
        if (!/^0?9\d{9}$/.test(phone)) { return showErr('ابتدا شماره موبایل معتبر وارد کنید.'); }
        return sendCode(phone);
      }
      submit(data);
    });

    function sendCode(phone) {
      var d = new FormData();
      d.append('action', 'sazan_form_send_code');
      d.append('nonce', cfg.nonce || '');
      d.append('form_id', root.getAttribute('data-form'));
      d.append('phone', phone);
      loading(true);
      fetch(cfg.ajax, { method: 'POST', body: d, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          loading(false);
          if (res && res.success) {
            otpSent = true; otpBox.hidden = false; btn.textContent = 'ثبت نهایی';
            info('کد تایید پیامک شد. آن را وارد کنید.');
            var oi = otpBox.querySelector('input'); if (oi) oi.focus();
          } else { showErr((res && res.data && res.data.msg) || 'ارسال کد ناموفق بود.'); }
        })
        .catch(function () { loading(false); showErr('ارتباط با سرور برقرار نشد.'); });
    }

    function submit(data) {
      data.append('action', 'sazan_form_submit');
      data.append('nonce', cfg.nonce || '');
      data.append('form_id', root.getAttribute('data-form'));
      loading(true);
      fetch(cfg.ajax, { method: 'POST', body: data, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          loading(false);
          if (res && res.success) {
            if (res.data && res.data.redirect) { window.location.href = res.data.redirect; return; }
            form.hidden = true;
            var dm = root.querySelector('.szf-done-msg');
            if (dm) dm.textContent = (res.data && res.data.msg) || root.getAttribute('data-success') || 'ثبت شد.';
            done.hidden = false;
          } else { showErr((res && res.data && res.data.msg) || 'خطایی رخ داد. دوباره تلاش کنید.'); }
        })
        .catch(function () { loading(false); showErr('ارتباط با سرور برقرار نشد.'); });
    }

    function loading(on) { btn.disabled = on; btn.classList.toggle('is-loading', on); }
    function showErr(t) { msg.textContent = t; msg.className = 'szf-msg is-err'; msg.hidden = false; }
    function info(t) { msg.textContent = t; msg.className = 'szf-msg'; msg.hidden = false; }
    function hideMsg() { msg.hidden = true; msg.className = 'szf-msg'; }
  }

  /* ---- مودال پاپ‌آپ ---- */
  function openModal(id) {
    var modal = document.querySelector('.szf-modal[data-form="' + id + '"]');
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add('szf-modal-open');
    initForm(modal.querySelector('.szf'));
  }
  function closeModal(modal) { modal.hidden = true; document.body.classList.remove('szf-modal-open'); }

  function boot() {
    // فرم‌های درون‌خطی
    document.querySelectorAll('.szf:not([data-szf-init])').forEach(initForm);

    // دکمه‌های فراخوان (شورت‌کد پاپ‌آپ + تقویم آموزشی)
    document.addEventListener('click', function (e) {
      var trig = e.target.closest('[data-sz-form]');
      if (trig) { e.preventDefault(); openModal(trig.getAttribute('data-sz-form')); return; }
      var modal = e.target.closest('.szf-modal');
      if (modal && (e.target.classList.contains('szf-modal-overlay') || e.target.closest('.szf-modal-close'))) {
        closeModal(modal);
      }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('.szf-modal:not([hidden])').forEach(closeModal);
      }
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
