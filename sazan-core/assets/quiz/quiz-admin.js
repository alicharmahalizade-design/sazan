/* سازنده‌ی آزمون سازان — رابط ریپیتر محور/سوال/بازه */
(function ($) {
  'use strict';
  var $store = $('#sazan-quiz-data');
  var D = {};
  try { D = JSON.parse($store.val() || '{}'); } catch (e) { D = {}; }
  D.lead = D.lead || {}; D.intro = D.intro || {}; D.result = D.result || {}; D.sms = D.sms || {};
  D.axes = D.axes || []; D.questions = D.questions || []; D.tiers = D.tiers || [];

  var esc = function (s) { return $('<i>').text(s == null ? '' : s).html(); };
  var uid = function () { return 'ax' + Math.random().toString(36).slice(2, 8); };

  function sync() { $store.val(JSON.stringify(D)); }

  function axisOptions(sel) {
    var h = '<option value="">— بدون محور —</option>';
    D.axes.forEach(function (a) { h += '<option value="' + esc(a.id) + '"' + (a.id === sel ? ' selected' : '') + '>' + esc(a.label) + '</option>'; });
    return h;
  }

  function render() {
    var h = '';
    /* تنظیمات کلی */
    h += '<div class="szq-card"><h3>تنظیمات کلی</h3>';
    h += '<label>مدل امتیازدهی</label><select data-k="scoring">' +
         '<option value="sum"' + (D.scoring === 'sum' ? ' selected' : '') + '>مجموع/درصدی ساده</option>' +
         '<option value="axis"' + (D.scoring === 'axis' ? ' selected' : '') + '>چندمحوری (نمودار رادار)</option></select>';
    h += '<label>عنوان معرفی</label><input data-k="intro.title" value="' + esc(D.intro.title) + '">';
    h += '<label>توضیح معرفی</label><textarea data-k="intro.desc">' + esc(D.intro.desc) + '</textarea>';
    h += '<label>متن دکمه شروع</label><input data-k="intro.start_label" value="' + esc(D.intro.start_label || 'شروع آزمون') + '">';
    h += '<div class="szq-row">' +
         chk('lead.required', 'گرفتن اطلاعات قبل از نتیجه', D.lead.required) +
         chk('lead.name', 'نام', D.lead.name) +
         chk('lead.mobile', 'موبایل', D.lead.mobile) +
         chk('lead.email', 'ایمیل', D.lead.email) +
         chk('lead.company', 'نام شرکت', D.lead.company) + '</div>';
    h += '<div class="szq-row">' +
         chk('result.show_radar', 'نمایش نمودار رادار', D.result.show_radar) +
         chk('result.show_products', 'نمایش محصولات پیشنهادی', D.result.show_products) +
         chk('result.show_pdf', 'دکمه دانلود PDF', D.result.show_pdf) + '</div>';
    h += '</div>';

    /* محورها */
    h += '<div class="szq-card"><h3>محورهای ارزیابی <small>(برای حالت چندمحوری)</small></h3><div id="szq-axes">';
    D.axes.forEach(function (a, i) {
      h += '<div class="szq-line" data-i="' + i + '"><input class="ax-label" value="' + esc(a.label) + '" placeholder="نام محور (مثلاً فروش)">' +
           '<button class="button szq-del" data-t="ax" data-i="' + i + '">حذف</button></div>';
    });
    h += '</div><button class="button button-secondary szq-add" data-t="ax">+ افزودن محور</button></div>';

    /* سوالات */
    h += '<div class="szq-card"><h3>سوالات</h3><div id="szq-qs">';
    D.questions.forEach(function (q, i) {
      h += '<div class="szq-q" data-i="' + i + '">';
      h += '<div class="szq-q-head"><span class="szq-num">' + (i + 1) + '</span>' +
           '<input class="q-text" value="' + esc(q.text) + '" placeholder="متن سوال">' +
           '<select class="q-type"><option value="single"' + (q.type === 'single' ? ' selected' : '') + '>تک‌گزینه</option>' +
           '<option value="multi"' + (q.type === 'multi' ? ' selected' : '') + '>چندگزینه</option>' +
           '<option value="scale"' + (q.type === 'scale' ? ' selected' : '') + '>طیفی</option></select>' +
           '<select class="q-axis">' + axisOptions(q.axis) + '</select>' +
           '<button class="button szq-del" data-t="q" data-i="' + i + '">حذف</button></div>';
      h += '<div class="szq-opts">';
      (q.options || []).forEach(function (o, oi) {
        h += '<div class="szq-opt" data-oi="' + oi + '"><input class="o-label" value="' + esc(o.label) + '" placeholder="گزینه">' +
             '<input class="o-score" type="number" step="0.5" value="' + (o.score || 0) + '" title="امتیاز">' +
             '<button class="button-link szq-del-opt">×</button></div>';
      });
      h += '</div><button class="button-link szq-add-opt">+ گزینه</button></div>';
    });
    h += '</div><button class="button button-secondary szq-add" data-t="q">+ افزودن سوال</button></div>';

    /* بازه‌های نتیجه */
    h += '<div class="szq-card"><h3>بازه‌های امتیاز و پیام مشاوره‌ای</h3><div id="szq-tiers">';
    D.tiers.forEach(function (t, i) {
      h += '<div class="szq-tier" data-i="' + i + '">' +
           '<div class="szq-row"><label>از</label><input class="t-min" type="number" value="' + (t.min || 0) + '">' +
           '<label>تا</label><input class="t-max" type="number" value="' + (t.max != null ? t.max : 100) + '">' +
           '<input class="t-title" value="' + esc(t.title) + '" placeholder="عنوان سطح (مثلاً نیازمند بازنگری)">' +
           '<button class="button szq-del" data-t="t" data-i="' + i + '">حذف</button></div>' +
           '<textarea class="t-message" placeholder="پیام کوتاه مشاوره‌ای">' + esc(t.message) + '</textarea>' +
           '<textarea class="t-desc" placeholder="توضیحات کامل (HTML مجاز)">' + esc(t.desc) + '</textarea>' +
           '<div class="szq-row"><input class="t-products" value="' + esc((t.products || []).join(',')) + '" placeholder="شناسه محصولات پیشنهادی با کاما (مثلاً 12,34)">' +
           '<input class="t-cta-label" value="' + esc(t.cta_label) + '" placeholder="متن دکمه اقدام">' +
           '<input class="t-cta-url" value="' + esc(t.cta_url) + '" placeholder="لینک دکمه"></div></div>';
    });
    h += '</div><button class="button button-secondary szq-add" data-t="t">+ افزودن بازه</button></div>';

    /* پیامک */
    h += '<div class="szq-card"><h3>پیامک نتیجه</h3>' +
         chk('sms.enabled', 'ارسال پیامک نتیجه به شرکت‌کننده', D.sms.enabled) +
         '<label>متن پیامک <small>متغیرها: {score} {tier} {message}</small></label>' +
         '<textarea data-k="sms.template">' + esc(D.sms.template) + '</textarea>' +
         '<p class="description">برای فعال‌سازی واقعی پیامک باید پنل پیامکی از طریق هوک <code>sazan_quiz_send_sms</code> وصل شود (راهنما در readme).</p></div>';

    $('#sazan-quiz-app').html(h);
  }

  function chk(k, label, v) {
    return '<label class="szq-chk"><input type="checkbox" data-k="' + k + '"' + (v ? ' checked' : '') + '> ' + label + '</label>';
  }

  function setPath(k, val) {
    var p = k.split('.'), o = D;
    for (var i = 0; i < p.length - 1; i++) { o[p[i]] = o[p[i]] || {}; o = o[p[i]]; }
    o[p[p.length - 1]] = val;
  }

  /* جمع‌آوری مقادیر سوال/محور/بازه از DOM (چون inputهای تکراری data-k ندارند) */
  function collect() {
    D.axes = [];
    $('#szq-axes .szq-line').each(function () {
      var label = $(this).find('.ax-label').val().trim();
      if (label) D.axes.push({ id: ($(this).data('id') || uid()), label: label });
    });
    D.questions = [];
    $('#szq-qs .szq-q').each(function () {
      var $q = $(this), opts = [];
      $q.find('.szq-opt').each(function () {
        var l = $(this).find('.o-label').val().trim();
        if (l) opts.push({ label: l, score: parseFloat($(this).find('.o-score').val()) || 0 });
      });
      D.questions.push({
        text: $q.find('.q-text').val().trim(),
        type: $q.find('.q-type').val(),
        axis: $q.find('.q-axis').val(),
        options: opts
      });
    });
    D.tiers = [];
    $('#szq-tiers .szq-tier').each(function () {
      var $t = $(this);
      D.tiers.push({
        min: parseFloat($t.find('.t-min').val()) || 0,
        max: parseFloat($t.find('.t-max').val()) || 0,
        title: $t.find('.t-title').val().trim(),
        message: $t.find('.t-message').val(),
        desc: $t.find('.t-desc').val(),
        products: $t.find('.t-products').val().split(',').map(function (x) { return parseInt(x, 10); }).filter(Boolean),
        cta_label: $t.find('.t-cta-label').val(),
        cta_url: $t.find('.t-cta-url').val()
      });
    });
    sync();
  }

  var $app = $('#sazan-quiz-app');

  $app.on('input change', '[data-k]', function () {
    var k = $(this).data('k');
    var val = this.type === 'checkbox' ? (this.checked ? 1 : 0) : $(this).val();
    setPath(k, val);
    sync();
  });

  // هر تغییر در فیلدهای تکراری → collect
  $app.on('input change', '.szq-q input, .szq-q select, .szq-q textarea, .szq-tier input, .szq-tier textarea, .ax-label', function () {
    collect();
  });

  $app.on('click', '.szq-add', function (e) {
    e.preventDefault();
    collect();
    var t = $(this).data('t');
    if (t === 'ax') D.axes.push({ id: uid(), label: '' });
    if (t === 'q') D.questions.push({ text: '', type: 'single', axis: '', options: [{ label: '', score: 0 }] });
    if (t === 't') D.tiers.push({ min: 0, max: 100, title: '', message: '', desc: '', products: [], cta_label: '', cta_url: '' });
    sync(); render();
  });

  $app.on('click', '.szq-del', function (e) {
    e.preventDefault();
    collect();
    var t = $(this).data('t'), i = $(this).data('i');
    if (t === 'ax') D.axes.splice(i, 1);
    if (t === 'q') D.questions.splice(i, 1);
    if (t === 't') D.tiers.splice(i, 1);
    sync(); render();
  });

  $app.on('click', '.szq-add-opt', function (e) {
    e.preventDefault();
    collect();
    var i = $(this).closest('.szq-q').data('i');
    D.questions[i].options.push({ label: '', score: 0 });
    sync(); render();
  });

  $app.on('click', '.szq-del-opt', function (e) {
    e.preventDefault();
    collect();
    var i = $(this).closest('.szq-q').data('i');
    var oi = $(this).closest('.szq-opt').data('oi');
    D.questions[i].options.splice(oi, 1);
    sync(); render();
  });

  // ذخیره‌ی نهایی قبل از submit فرم پست
  $('#post').on('submit', collect);

  render();
})(jQuery);
