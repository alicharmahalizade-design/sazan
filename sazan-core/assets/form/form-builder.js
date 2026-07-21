/* سازنده‌ی فرم سازان — افزودن/حذف/جابجایی فیلدها و سریال‌سازی به JSON */
(function ($) {
  'use strict';

  $(function () {
    var $b = $('#szf-builder');
    if (!$b.length) return;
    var $rows = $b.find('.szf-rows');
    var $store = $('#szf-fields-json');
    var tpl = $('#szf-row-tpl').html();

    function addRow(data) {
      data = data || {};
      var $r = $(tpl);
      $r.find('.szf-f-type').val(data.type || 'text');
      $r.find('.szf-f-label').val(data.label || '');
      $r.find('.szf-f-name').val(data.name || '');
      $r.find('.szf-f-ph').val(data.placeholder || '');
      $r.find('.szf-f-opts').val((data.options || []).join('، '));
      $r.find('.szf-f-width').val(data.width || 'full');
      $r.find('.szf-f-req input').prop('checked', !!data.required);
      toggleOpts($r);
      $rows.append($r);
    }

    function toggleOpts($r) {
      var isSel = $r.find('.szf-f-type').val() === 'select';
      $r.find('.szf-f-opts').prop('hidden', !isSel);
    }

    // اسلاگ‌سازی خودکار کلید لاتین از روی برچسب در صورت خالی بودن
    function autoName($r) {
      var $name = $r.find('.szf-f-name');
      if ($name.val().trim() !== '') return;
      var lbl = $r.find('.szf-f-label').val().trim();
      if (/^[\x00-\x7F]+$/.test(lbl)) {
        $name.val(lbl.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, ''));
      }
    }

    function serialize() {
      var out = [];
      $rows.find('.szf-row').each(function () {
        var $r = $(this);
        var opts = $r.find('.szf-f-opts').val().split(/[,،]/).map(function (s) { return s.trim(); }).filter(Boolean);
        out.push({
          type: $r.find('.szf-f-type').val(),
          label: $r.find('.szf-f-label').val(),
          name: $r.find('.szf-f-name').val(),
          placeholder: $r.find('.szf-f-ph').val(),
          width: $r.find('.szf-f-width').val(),
          required: $r.find('.szf-f-req input').is(':checked') ? 1 : 0,
          options: opts
        });
      });
      $store.val(JSON.stringify(out));
    }

    // بارگذاری اولیه
    var initial = [];
    try { initial = JSON.parse($store.val() || '[]'); } catch (e) {}
    if (initial.length) { initial.forEach(addRow); }
    else { addRow({ type: 'text', label: 'نام و نام خانوادگی', name: 'name', required: 1 }); addRow({ type: 'tel', label: 'شماره موبایل', name: 'phone', required: 1 }); }

    $b.on('click', '.szf-add', function () { addRow(); });
    $b.on('click', '.szf-del', function () { $(this).closest('.szf-row').remove(); serialize(); });
    $b.on('change', '.szf-f-type', function () { toggleOpts($(this).closest('.szf-row')); });
    $b.on('blur', '.szf-f-label', function () { autoName($(this).closest('.szf-row')); });
    $b.on('input change', 'input,select,textarea', serialize);

    $rows.sortable({ handle: '.szf-drag', placeholder: 'szf-placeholder', forcePlaceholderSize: true, update: serialize });

    // اطمینان از سریال‌سازی قبل از ذخیره
    $('form#post').on('submit', serialize);
    serialize();
  });
})(jQuery);
