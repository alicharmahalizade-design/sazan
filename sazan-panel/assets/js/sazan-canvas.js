(function () {
	'use strict';

	/* ارتباط با سرور با همان nonce فرانت پنل. */
	function ajax(action, data, cb) {
		data.append('action', action);
		data.append('nonce', (window.SZP_FRONT && SZP_FRONT.nonce) || '');
		fetch((window.SZP_FRONT && SZP_FRONT.ajax) || '', {
			method: 'POST', credentials: 'same-origin', body: data
		}).then(function (r) { return r.json(); })
			.then(cb)
			.catch(function () { cb({ success: false, data: { msg: 'خطای ارتباط با سرور.' } }); });
	}

	/* رشد خودکار ارتفاع textarea با محتوا. */
	function autogrow(t) {
		t.style.height = 'auto';
		t.style.height = (t.scrollHeight + 2) + 'px';
	}

	function setState(canvas, text, kind) {
		var el = canvas.querySelector('.szp-canvas-state');
		if (!el) return;
		el.textContent = text;
		el.className = 'szp-canvas-state is-show' + (kind ? ' is-' + kind : '');
		if (kind === 'ok') {
			clearTimeout(el._t);
			el._t = setTimeout(function () { el.classList.remove('is-show'); }, 2200);
		}
	}

	/* جمع‌آوری موارد هر بخش به‌صورت { section: [text, ...] }. */
	function collect(canvas) {
		var out = {};
		canvas.querySelectorAll('.szp-cv-row').forEach(function (row) {
			var key = row.getAttribute('data-section');
			var arr = [];
			row.querySelectorAll('.szp-cv-text').forEach(function (t) {
				var v = t.value.trim();
				if (v) arr.push(v);
			});
			out[key] = arr;
		});
		return out;
	}

	var saveTimers = {};
	function scheduleSave(canvas) {
		var key = canvas.getAttribute('data-canvas') || 'service_design';
		clearTimeout(saveTimers[key]);
		setState(canvas, 'در حال ذخیره…', '');
		saveTimers[key] = setTimeout(function () { doSave(canvas); }, 700);
	}

	function doSave(canvas) {
		var key = canvas.getAttribute('data-canvas') || 'service_design';
		var items = collect(canvas);

		/* مهمان: ذخیره موقت روی همین مرورگر. */
		if (canvas.getAttribute('data-logged') !== '1') {
			try { localStorage.setItem('szp_canvas_' + key, JSON.stringify(items)); } catch (e) {}
			setState(canvas, 'ذخیره موقت روی مرورگر', 'ok');
			return;
		}

		var d = new FormData();
		d.append('canvas', key);
		Object.keys(items).forEach(function (sec) {
			items[sec].forEach(function (txt) { d.append('items[' + sec + '][]', txt); });
			/* تضمین ارسال بخش خالی تا حذف کامل هم ذخیره شود. */
			if (!items[sec].length) d.append('items[' + sec + '][]', '');
		});
		ajax('szp_canvas_save', d, function (res) {
			if (res && res.success) setState(canvas, 'ذخیره شد ✓', 'ok');
			else setState(canvas, (res && res.data && res.data.msg) || 'خطا در ذخیره', 'err');
		});
	}

	/* ساخت یک ردیف مورد جدید. */
	function makeItem(value) {
		var li = document.createElement('li');
		li.className = 'szp-cv-item';
		var ta = document.createElement('textarea');
		ta.className = 'szp-cv-text';
		ta.rows = 1;
		ta.placeholder = 'یک روش خدمت بنویسید…';
		ta.value = value || '';
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'szp-cv-del';
		btn.setAttribute('aria-label', 'حذف مورد');
		btn.textContent = '×';
		li.appendChild(ta);
		li.appendChild(btn);
		return li;
	}

	function initCanvas(canvas) {
		if (canvas.dataset.cvInit) return;
		canvas.dataset.cvInit = '1';

		/* مهمان: بازیابی موارد ذخیره‌شده روی مرورگر. */
		if (canvas.getAttribute('data-logged') !== '1') {
			var key = canvas.getAttribute('data-canvas') || 'service_design';
			var saved = null;
			try { saved = JSON.parse(localStorage.getItem('szp_canvas_' + key) || 'null'); } catch (e) {}
			if (saved) {
				canvas.querySelectorAll('.szp-cv-row').forEach(function (row) {
					var sec = row.getAttribute('data-section');
					var list = row.querySelector('.szp-cv-items');
					if (!list || !Array.isArray(saved[sec])) return;
					list.innerHTML = '';
					saved[sec].forEach(function (txt) { list.appendChild(makeItem(txt)); });
				});
			}
		}

		/* ارتفاع اولیه textareaهای موجود. */
		canvas.querySelectorAll('.szp-cv-text').forEach(autogrow);

		/* افزودن مورد. */
		canvas.addEventListener('click', function (e) {
			var add = e.target.closest('.szp-cv-add');
			if (add) {
				var row = add.closest('.szp-cv-row');
				var list = row.querySelector('.szp-cv-items');
				var li = makeItem('');
				li.classList.add('szp-cv-new');
				list.appendChild(li);
				var ta = li.querySelector('.szp-cv-text');
				autogrow(ta);
				try { ta.focus({ preventScroll: true }); } catch (err) { ta.focus(); }
				return;
			}
			var del = e.target.closest('.szp-cv-del');
			if (del) {
				var item = del.closest('.szp-cv-item');
				if (item) item.parentNode.removeChild(item);
				scheduleSave(canvas);
			}
		});

		/* تایپ در موارد → رشد ارتفاع + ذخیره خودکار با تأخیر. */
		canvas.addEventListener('input', function (e) {
			if (!e.target.classList.contains('szp-cv-text')) return;
			autogrow(e.target);
			scheduleSave(canvas);
		});

		/* Enter (بدون Shift) = افزودن مورد بعدی. */
		canvas.addEventListener('keydown', function (e) {
			if (e.key !== 'Enter' || e.shiftKey) return;
			if (!e.target.classList.contains('szp-cv-text')) return;
			e.preventDefault();
			var row = e.target.closest('.szp-cv-row');
			var list = row.querySelector('.szp-cv-items');
			var li = makeItem('');
			li.classList.add('szp-cv-new');
			list.appendChild(li);
			var nta = li.querySelector('.szp-cv-text');
			try { nta.focus({ preventScroll: true }); } catch (err) { nta.focus(); }
		});
	}

	function init() {
		document.querySelectorAll('.szp-canvas').forEach(initCanvas);
	}

	if (document.readyState !== 'loading') init();
	else document.addEventListener('DOMContentLoaded', init);
})();
