/* آزمون سازان — فرانت: تایپ‌فرمی تک‌سوالی، لید، نتیجه، رادار، PDF */
(function () {
  'use strict';

  function init(root) {
    var stage = root.querySelector('.sz-quiz-stage');
    if (!stage) return;

    // صفحه‌ی نتیجه (لینک پیامک/اشتراک): مستقیم نتیجه را رندر کن
    var resEl = root.querySelector('.sz-quiz-result-data');
    if (resEl) {
      var rd;
      try { rd = JSON.parse(resEl.textContent); } catch (e) { stage.innerHTML = 'خطا.'; return; }
      paintResultStandalone(stage, rd);
      return;
    }

    var cfgEl = root.querySelector('.sz-quiz-cfg');
    if (!cfgEl) return;
    var cfg, quizId = root.getAttribute('data-quiz');
    try { cfg = JSON.parse(cfgEl.textContent); } catch (e) { stage.innerHTML = 'خطا در بارگذاری.'; return; }

    var testMode = !!cfg.test_mode;
    // -2 = تأیید موبایل، -1 = معرفی و دکمه شروع. در حالت تست، تأیید موبایل رد می‌شود.
    var answers = {}, step = testMode ? -1 : -2;
    var leadFields = cfg.lead || {};
    var needLead = !!leadFields.required;
    var totalSteps = cfg.questions.length;
    var challenge = '', draft = '', verifiedMobile = '';

    function h(s) { var d = document.createElement('div'); d.innerHTML = s; return d; }
    function go(s) { step = s; paint(); }

    function progress() {
      var done = Math.max(0, step);
      var pct = totalSteps ? Math.round((done / totalSteps) * 100) : 0;
      return '<div class="szf-prog"><span style="width:' + pct + '%"></span></div>';
    }

    function paint() {
      if (step === -2) return paintVerify();
      if (step === -1) return paintIntro();
      if (step < totalSteps) return paintQuestion(step);
      if (needLead) return paintLead();
      return submit();
    }

    function paintVerify() {
      stage.innerHTML = '<div class="szf-card szf-verify"><div class="szf-step-kicker">مرحله ۱ از ۳</div>' +
        '<h2>تأیید شماره موبایل</h2><p class="szf-desc">برای ذخیرهٔ مرحله‌ای پاسخ‌ها، شماره موبایل خود را وارد کنید.</p>' +
        '<label class="szf-field">شماره موبایل<input class="szf-mobile" type="tel" inputmode="numeric" autocomplete="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹"></label>' +
        '<div class="szf-otp-row" hidden><label class="szf-field">کد تأیید ارسال‌شده<input class="szf-code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code"></label>' +
        '<button type="button" class="szf-link szf-edit-mobile">ویرایش شماره موبایل</button></div>' +
        '<div class="szf-err" hidden></div>' +
        '<div class="szf-actions"><button class="szf-btn szf-send-code">ارسال کد تأیید</button>' +
        '<button class="szf-btn szf-verify-code" hidden>تأیید و ادامه</button></div></div>';
      var send = stage.querySelector('.szf-send-code'), verify = stage.querySelector('.szf-verify-code'), mobile = stage.querySelector('.szf-mobile'), code = stage.querySelector('.szf-code'), row = stage.querySelector('.szf-otp-row');
      var editBtn = stage.querySelector('.szf-edit-mobile');
      var sendLabel = send.textContent;

      editBtn.onclick = function () { go(-2); };

      send.onclick = function () {
        var val = mobile.value.trim(), fd = new FormData(); fd.append('action','sazan_quiz_start'); fd.append('nonce',SazanQuiz.nonce); fd.append('quiz_id',quizId); fd.append('mobile',val);
        send.disabled = true; send.textContent = 'در حال ارسال…';
        fetch(SazanQuiz.ajax,{method:'POST',credentials:'same-origin',body:fd}).then(function(r){return r.json();}).then(function(res){ if(!res.success){ showErr(res.data&&res.data.msg||'ارسال کد انجام نشد.'); send.disabled=false; send.textContent=sendLabel; return; } challenge=res.data.challenge; verifiedMobile=val; row.hidden=false; send.hidden=true; verify.hidden=false; mobile.readOnly=true; code.focus(); }).catch(function(){showErr('خطای ارتباط با سرور.');send.disabled=false;send.textContent=sendLabel;});
      };
      verify.onclick = function () { var fd=new FormData(); fd.append('action','sazan_quiz_verify'); fd.append('nonce',SazanQuiz.nonce); fd.append('challenge',challenge); fd.append('code',code.value.trim()); verify.disabled=true; verify.textContent='در حال بررسی…'; fetch(SazanQuiz.ajax,{method:'POST',credentials:'same-origin',body:fd}).then(function(r){return r.json();}).then(function(res){if(!res.success){showErr(res.data&&res.data.msg||'کد نادرست است.');verify.disabled=false;verify.textContent='تأیید و ادامه';return;} draft=res.data.draft; go(-1);}).catch(function(){showErr('خطای ارتباط با سرور.');verify.disabled=false;}); };
    }

    function paintIntro() {
      var i = cfg.intro || {};
      stage.innerHTML =
        '<div class="szf-card szf-intro">' +
        (i.title ? '<h2>' + escape(i.title) + '</h2>' : '') +
        (i.desc ? '<div class="szf-desc">' + i.desc + '</div>' : '') +
        '<button class="szf-btn szf-start">' + escape(i.start_label || 'شروع آزمون') + '</button>' +
        '</div>';
      stage.querySelector('.szf-start').onclick = function () { go(0); };
    }

    function paintQuestion(qi) {
      var q = cfg.questions[qi];
      var multi = q.type === 'multi';
      var html = '<div class="szf-card szf-q">' + progress() +
        '<div class="szf-qnum">سوال ' + (qi + 1) + ' از ' + totalSteps + '</div>' +
        '<h3>' + escape(q.text) + '</h3><div class="szf-opts">';
      var sel = answers[qi];
      q.opts.forEach(function (label, oi) {
        var on = multi ? (Array.isArray(sel) && sel.indexOf(oi) > -1) : (sel === oi);
        html += '<button class="szf-opt' + (on ? ' on' : '') + '" data-oi="' + oi + '">' + escape(label) + '</button>';
      });
      html += '</div><div class="szf-nav">' +
        (qi > 0 ? '<button class="szf-back">قبلی</button>' : '<span></span>') +
        (multi ? '<button class="szf-next">بعدی</button>' : '') +
        '</div></div>';
      stage.innerHTML = html;

      stage.querySelectorAll('.szf-opt').forEach(function (b) {
        b.onclick = function () {
          var oi = parseInt(b.getAttribute('data-oi'), 10);
          if (multi) {
            var arr = Array.isArray(answers[qi]) ? answers[qi] : [];
            var idx = arr.indexOf(oi);
            if (idx > -1) arr.splice(idx, 1); else arr.push(oi);
            answers[qi] = arr;
            b.classList.toggle('on');
          } else {
            answers[qi] = oi;
            saveStep(qi, answers[qi]).then(function () { go(qi + 1); });
          }
        };
      });
      var back = stage.querySelector('.szf-back'); if (back) back.onclick = function () { go(qi - 1); };
      var next = stage.querySelector('.szf-next'); if (next) next.onclick = function () { saveStep(qi, answers[qi] || []).then(function () { go(qi + 1); }); };
    }

    function paintLead() {
      var html = '<div class="szf-card szf-lead">' + progress() +
        '<h3>برای مشاهده نتیجه، اطلاعات تماس را وارد کنید</h3>';
      if (leadFields.name) html += inp('name', leadFields.name_label || 'نام و نام خانوادگی', 'text');
      // فقط وقتی شماره‌ای تأیید شده، فیلد قفل می‌ماند؛ در غیر این صورت (مثلاً حالت تست) قابل تایپ است.
      if (leadFields.mobile) html += '<label class="szf-field">' + escape(leadFields.mobile_label || 'شماره موبایل') +
        '<input data-lead="mobile" class="szf-lead-mobile" type="tel" inputmode="numeric" autocomplete="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" value="' + escape(verifiedMobile) + '"' + (verifiedMobile ? ' readonly' : '') + '></label>';
      if (leadFields.email) html += inp('email', leadFields.email_label || 'ایمیل', 'email');
      if (leadFields.company) html += inp('company', leadFields.company_label || 'نام کسب‌وکار', 'text');
      html += '<div class="szf-err" hidden></div>' +
        '<div class="szf-nav"><button class="szf-back">قبلی</button><button class="szf-submit szf-btn">مشاهده نتیجه</button></div></div>';
      stage.innerHTML = html;
      stage.querySelector('.szf-back').onclick = function () { go(totalSteps - 1); };
      stage.querySelector('.szf-submit').onclick = submit;
    }

    function inp(k, label, type) {
      return '<label class="szf-field">' + escape(label) + '<input data-lead="' + k + '" type="' + type + '"></label>';
    }

    function saveStep(qi, value) {
      if (!draft) return Promise.resolve();
      var fd = new FormData(); fd.append('action','sazan_quiz_save_step'); fd.append('nonce',SazanQuiz.nonce); fd.append('quiz_id',quizId); fd.append('draft',draft); fd.append('step',qi); fd.append('value',JSON.stringify(value));
      return fetch(SazanQuiz.ajax,{method:'POST',credentials:'same-origin',body:fd}).then(function(r){return r.json();}).then(function(res){if(!res.success){throw new Error('save');} return res;}).catch(function(){ showErr('ذخیرهٔ این مرحله انجام نشد؛ دوباره تلاش کنید.'); throw new Error('save'); });
    }

    function submit() {
      var lead = {};
      stage.querySelectorAll('[data-lead]').forEach(function (el) { lead[el.getAttribute('data-lead')] = el.value.trim(); });
      var fd = new FormData();
      fd.append('action', 'sazan_quiz_submit');
      fd.append('nonce', SazanQuiz.nonce);
      fd.append('quiz_id', quizId);
      fd.append('draft', draft);
      fd.append('answers', JSON.stringify(answers));
      fd.append('lead_name', lead.name || '');
      fd.append('lead_mobile', lead.mobile || '');
      fd.append('lead_email', lead.email || '');
      fd.append('lead_company', lead.company || '');

      var btn = stage.querySelector('.szf-submit'); if (btn) { btn.disabled = true; btn.textContent = 'در حال محاسبه…'; }

      fetch(SazanQuiz.ajax, { method: 'POST', credentials: 'same-origin', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res.success) { showErr(res.data && res.data.msg || 'خطا'); if (btn) { btn.disabled = false; btn.textContent = 'مشاهده نتیجه'; } return; }
          paintResult(res.data);
        })
        .catch(function () { showErr('خطای ارتباط با سرور.'); if (btn) { btn.disabled = false; btn.textContent = 'مشاهده نتیجه'; } });
    }

    function showErr(m) {
      var e = stage.querySelector('.szf-err');
      if (e) { e.textContent = m; e.hidden = false; } else { alert(m); }
    }

    function paintResult(d) { paintResultStandalone(stage, d); }

    function escape(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    paint();
  }

  /* رندر مشترک نتیجه (هم بعد از ارسال، هم در صفحه‌ی توکن) */
  function paintResultStandalone(stage, d) {
    function escape(s) { var el = document.createElement('div'); el.textContent = s == null ? '' : s; return el.innerHTML; }
    var t = d.tier, displayScore = d.basis === 'raw' ? d.score : d.percent, displayMax = d.basis === 'raw' ? d.max : 100;
    var html = '<div class="szf-card szf-result" id="szf-report">';
    html += '<div class="szf-gauge"><svg viewBox="0 0 120 120"><circle class="bg" cx="60" cy="60" r="52"/>' +
      '<circle class="fg" cx="60" cy="60" r="52" stroke-dasharray="' + (3.2672 * d.percent) + ' 1000"/></svg>' +
      '<div class="szf-score"><b>' + displayScore + '</b><span>از ' + displayMax + '</span></div></div>';
    if (t) {
      html += '<h2 class="szf-tier-title">' + escape(t.title) + '</h2>';
      if (t.message) html += '<p class="szf-tier-msg">' + escape(t.message) + '</p>';
      if (t.desc) html += '<div class="szf-tier-desc">' + t.desc + '</div>';
    }
    if (d.show_radar && d.axes && d.axes.length) {
      html += '<div class="szf-radar-wrap"><canvas class="szf-radar"></canvas></div>';
    }
    if (d.products && d.products.length) {
      html += '<h3 class="szf-prod-h">پیشنهاد ویژه برای شما</h3><div class="szf-prods">';
      d.products.forEach(function (p) {
        html += '<a class="szf-prod" href="' + p.url + '">' +
          (p.img ? '<img src="' + p.img + '" alt="">' : '') +
          '<span class="szf-prod-t">' + escape(p.title) + '</span>' +
          '<span class="szf-prod-p">' + (p.price || '') + '</span></a>';
      });
      html += '</div>';
    }
    html += '</div>'; // پایان کارت گزارش
    html += '<div class="szf-result-actions">';
    if (t && t.cta_url && t.cta_label) html += '<a class="szf-btn" href="' + t.cta_url + '">' + escape(t.cta_label) + '</a>';
    if (d.show_pdf) html += '<button class="szf-btn szf-ghost szf-pdf">دانلود گزارش PDF</button>';
    html += '</div>';
    stage.innerHTML = html;

    if (d.show_radar && d.axes && d.axes.length && window.Chart) {
      new Chart(stage.querySelector('.szf-radar'), {
        type: 'radar',
        data: {
          labels: d.axes.map(function (a) { return a.label; }),
          datasets: [{ data: d.axes.map(function (a) { return a.percent; }), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.18)', pointBackgroundColor: '#2563eb' }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { r: { min: 0, max: 100, ticks: { stepSize: 25 } } } }
      });
    }
    var pdf = stage.querySelector('.szf-pdf');
    if (pdf) pdf.onclick = function () { makePDF(stage.querySelector('#szf-report')); };
  }

  /* PDF فارسی: از کارت نتیجه عکس می‌گیریم (رندر مرورگر = فارسی درست) */
  function makePDF(reportEl) {
    var jsPDF = (window.jspdf && window.jspdf.jsPDF) || window.jsPDF;
    if (!reportEl || !window.html2canvas || !jsPDF) { alert('کتابخانه‌ی تولید PDF در دسترس نیست.'); return; }
    html2canvas(reportEl, { scale: 2, backgroundColor: '#ffffff', useCORS: true }).then(function (canvas) {
      var img = canvas.toDataURL('image/jpeg', 0.95);
      var pdf = new jsPDF('p', 'mm', 'a4');
      var pw = pdf.internal.pageSize.getWidth();
      var w = pw - 20, h = canvas.height * w / canvas.width;
      pdf.addImage(img, 'JPEG', 10, 10, w, h);
      pdf.save('sazan-report.pdf');
    }).catch(function () { alert('تولید PDF با خطا مواجه شد.'); });
  }

  function boot() { document.querySelectorAll('.sz-quiz').forEach(init); }
  if (document.readyState !== 'loading') boot(); else document.addEventListener('DOMContentLoaded', boot);
})();
