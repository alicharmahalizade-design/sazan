/* ==========================================================================
   سازان لندینگ — تعاملات ویجت‌ها
   همه چیز از یک init(scope) اجرا می‌شود تا در ویرایشگر المنتور هم
   با هر بار رندر شدن ویجت دوباره راه بیفتد.
   ========================================================================== */
(function () {
	'use strict';

	var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

	function toFa(n) {
		return String(n).replace(/[0-9]/g, function (d) { return FA[+d]; });
	}
	function toEn(str) {
		return String(str)
			.replace(/[۰-۹]/g, function (d) { return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)); })
			.replace(/[٠-٩]/g, function (d) { return String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)); });
	}

	/* ── شمارنده اعداد ─────────────────────────────────────────────── */
	function initCounters(scope) {
		var nodes = scope.querySelectorAll('[data-szl-count]:not([data-szl-done])');
		if (!nodes.length) { return; }

		function run(el) {
			el.setAttribute('data-szl-done', '1');
			var raw = toEn(el.getAttribute('data-szl-count')).replace(/[^\d]/g, '');
			var target = parseInt(raw, 10);
			if (isNaN(target)) { return; }

			var isFa = /[۰-۹]/.test(el.getAttribute('data-szl-count'));
			if (reduce) { el.textContent = isFa ? toFa(target) : String(target); return; }

			var start = null;
			(function step(ts) {
				if (start === null) { start = ts; }
				var p = Math.min((ts - start) / 1100, 1);
				var v = Math.round(target * (1 - Math.pow(1 - p, 3)));
				el.textContent = isFa ? toFa(v) : String(v);
				if (p < 1) { requestAnimationFrame(step); }
			})(performance.now());
		}

		if (!('IntersectionObserver' in window)) {
			Array.prototype.forEach.call(nodes, run);
			return;
		}
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (!e.isIntersecting) { return; }
				run(e.target);
				io.unobserve(e.target);
			});
		}, { threshold: 0.5 });
		Array.prototype.forEach.call(nodes, function (el) { io.observe(el); });
	}

	/* ── آکاردئون سوالات متداول ────────────────────────────────────── */
	function initFaq(scope) {
		var lists = scope.querySelectorAll('[data-szl-faq]:not([data-szl-bound])');
		Array.prototype.forEach.call(lists, function (list) {
			list.setAttribute('data-szl-bound', '1');

			list.addEventListener('click', function (e) {
				var btn = e.target.closest('.szl-faq-q');
				if (!btn || !list.contains(btn)) { return; }

				var item = btn.parentNode;
				var wasOpen = item.classList.contains('szl-open');

				if (list.getAttribute('data-single') === '1') {
					Array.prototype.forEach.call(list.querySelectorAll('.szl-faq-item.szl-open'), function (other) {
						other.classList.remove('szl-open');
						var q = other.querySelector('.szl-faq-q');
						if (q) { q.setAttribute('aria-expanded', 'false'); }
					});
				} else if (wasOpen) {
					item.classList.remove('szl-open');
					btn.setAttribute('aria-expanded', 'false');
					return;
				}

				if (!wasOpen) {
					item.classList.add('szl-open');
					btn.setAttribute('aria-expanded', 'true');
				}
			});
		});
	}

	/* ── فرم ثبت درخواست ───────────────────────────────────────────── */
	function initForms(scope) {
		var forms = scope.querySelectorAll('[data-szl-form]:not([data-szl-bound])');

		Array.prototype.forEach.call(forms, function (form) {
			form.setAttribute('data-szl-bound', '1');

			var okBox = form.querySelector('.szl-form-ok');
			var btn = form.querySelector('.szl-submit');
			var btnText = btn ? btn.textContent : '';

			function setError(input, msg) {
				var field = input.closest('.szl-field');
				var err = field ? field.querySelector('.szl-err') : null;
				if (field) { field.classList.toggle('szl-invalid', !!msg); }
				if (err) { err.textContent = msg || ''; }
				return !msg;
			}

			function validName() {
				var i = form.elements.name;
				if (!i) { return true; }
				var v = i.value.trim();
				if (!v) { return setError(i, 'لطفاً نام و نام خانوادگی خود را وارد کنید.'); }
				if (v.length < 3) { return setError(i, 'نام واردشده کوتاه است.'); }
				return setError(i, '');
			}

			function validPhone() {
				var i = form.elements.phone;
				if (!i) { return true; }
				var v = toEn(i.value).replace(/[\s\-()]/g, '').replace(/^(\+98|0098|98)/, '0');
				if (/^9\d{9}$/.test(v)) { v = '0' + v; }
				if (!i.value.trim()) { return setError(i, 'لطفاً شماره موبایل خود را وارد کنید.'); }
				if (!/^09\d{9}$/.test(v)) { return setError(i, 'شماره موبایل معتبر نیست. مثال: ۰۹۱۲۳۴۵۶۷۸۹'); }
				return setError(i, '');
			}

			if (form.elements.name) { form.elements.name.addEventListener('blur', validName); }
			if (form.elements.phone) {
				form.elements.phone.addEventListener('blur', validPhone);
				form.elements.phone.addEventListener('input', function () {
					var f = this.closest('.szl-field');
					if (f && f.classList.contains('szl-invalid')) { validPhone(); }
				});
			}

			function message(text, isError) {
				if (!okBox) { return; }
				okBox.textContent = text;
				okBox.classList.toggle('szl-is-error', !!isError);
				okBox.hidden = false;
			}

			form.addEventListener('submit', function (e) {
				e.preventDefault();
				if (okBox) { okBox.hidden = true; }

				var okName = validName();
				var okPhone = validPhone();
				if (!okName || !okPhone) {
					var bad = form.querySelector('.szl-field.szl-invalid input');
					if (bad) { bad.focus(); }
					return;
				}

				if (typeof window.SZL_CFG === 'undefined') {
					message('پیکربندی فرم در دسترس نیست.', true);
					return;
				}

				var body = new URLSearchParams();
				body.append('action', 'szl_submit');
				body.append('nonce', window.SZL_CFG.nonce);
				body.append('source', window.location.href);
				['name', 'phone', 'business', 'note', 'szl_hp'].forEach(function (k) {
					if (form.elements[k]) { body.append(k, form.elements[k].value); }
				});

				if (btn) { btn.disabled = true; btn.textContent = window.SZL_CFG.i18n.sending; }

				fetch(window.SZL_CFG.ajax, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: body.toString()
				})
					.then(function (r) { return r.json().catch(function () { return null; }); })
					.then(function (res) {
						if (res && res.success) {
							message(form.getAttribute('data-success') || (res.data && res.data.message) || 'ثبت شد.', false);
							form.reset();

							var to = form.getAttribute('data-redirect');
							if (to) { window.location.href = to; return; }

							if (okBox && okBox.scrollIntoView) {
								okBox.scrollIntoView({ block: 'nearest', behavior: reduce ? 'auto' : 'smooth' });
							}
						} else {
							var d = res && res.data ? res.data : {};
							if (d.errors) {
								Object.keys(d.errors).forEach(function (k) {
									if (form.elements[k]) { setError(form.elements[k], d.errors[k]); }
								});
							}
							message(d.message || window.SZL_CFG.i18n.failed, true);
						}
					})
					.catch(function () {
						message(window.SZL_CFG.i18n.failed, true);
					})
					.then(function () {
						if (btn) { btn.disabled = false; btn.textContent = btnText; }
					});
			});
		});
	}

	/* ── نمایش تدریجی ──────────────────────────────────────────────── */
	function initReveal(scope) {
		var targets = scope.querySelectorAll('.szl-card, .szl-qgrid li, .szl-fit li, .szl-flow li');
		if (reduce || !('IntersectionObserver' in window)) { return; }

		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (!e.isIntersecting) { return; }
				e.target.classList.add('szl-in');
				io.unobserve(e.target);
			});
		}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

		Array.prototype.forEach.call(targets, function (el, i) {
			if (el.classList.contains('szl-reveal')) { return; }
			el.classList.add('szl-reveal');
			el.style.transitionDelay = Math.min(i, 6) * 60 + 'ms';
			io.observe(el);
		});
	}

	function init(scope) {
		scope = scope || document;
		initCounters(scope);
		initFaq(scope);
		initForms(scope);
		initReveal(scope);
	}

	window.SZL = window.SZL || {};
	window.SZL.init = init;

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { init(document); });
	} else {
		init(document);
	}

	// المنتور: هر بار که ویجتی در ویرایشگر یا صفحه رندر می‌شود دوباره راه بیفتد.
	window.addEventListener('elementor/frontend/init', function () {
		if (!window.elementorFrontend || !elementorFrontend.hooks) { return; }
		elementorFrontend.hooks.addAction('frontend/element_ready/global', function ($scope) {
			init($scope && $scope[0] ? $scope[0] : document);
		});
	});
})();
