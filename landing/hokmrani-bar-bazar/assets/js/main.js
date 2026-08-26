/* ==========================================================================
   حکمرانی بر بازار — تعاملات لندینگ
   ========================================================================== */
(function () {
	'use strict';

	var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* ── هدر چسبان ─────────────────────────────────────────────────── */
	var header = document.getElementById('siteHeader');
	var toTop  = document.getElementById('toTop');

	function onScroll() {
		var y = window.pageYOffset || document.documentElement.scrollTop;
		if (header) header.classList.toggle('scrolled', y > 20);
		if (toTop)  toTop.classList.toggle('show', y > 600);
	}
	window.addEventListener('scroll', onScroll, { passive: true });
	onScroll();

	if (toTop) {
		toTop.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
		});
	}

	/* ── منوی موبایل ───────────────────────────────────────────────── */
	var navToggle = document.getElementById('navToggle');
	var nav       = document.getElementById('nav');

	function closeNav() {
		if (!nav) return;
		nav.classList.remove('open');
		if (navToggle) navToggle.setAttribute('aria-expanded', 'false');
	}

	if (navToggle && nav) {
		navToggle.addEventListener('click', function () {
			var open = nav.classList.toggle('open');
			navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
		nav.addEventListener('click', function (e) {
			if (e.target.tagName === 'A') closeNav();
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') closeNav();
		});
	}

	/* ── نمایش تدریجی بخش‌ها ───────────────────────────────────────── */
	var revealables = document.querySelectorAll('.reveal');

	if (reduceMotion || !('IntersectionObserver' in window)) {
		Array.prototype.forEach.call(revealables, function (el) { el.classList.add('in'); });
	} else {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;
				var el = entry.target;
				var siblings = el.parentNode ? el.parentNode.children : [];
				var idx = Array.prototype.indexOf.call(siblings, el);
				el.style.transitionDelay = Math.min(idx, 6) * 70 + 'ms';
				el.classList.add('in');
				io.unobserve(el);
			});
		}, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });

		Array.prototype.forEach.call(revealables, function (el) { io.observe(el); });
	}

	/* ── شمارنده آمار هیرو ─────────────────────────────────────────── */
	var FA_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

	function toFa(n) {
		return String(n).replace(/[0-9]/g, function (d) { return FA_DIGITS[+d]; });
	}

	function toEnDigits(str) {
		return String(str)
			.replace(/[۰-۹]/g, function (d) { return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)); })
			.replace(/[٠-٩]/g, function (d) { return String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)); });
	}

	var counters = document.querySelectorAll('[data-count]');

	function runCounter(el) {
		var target = parseInt(el.getAttribute('data-count'), 10);
		if (isNaN(target)) return;
		if (reduceMotion) { el.textContent = toFa(target); return; }

		var start = null;
		var dur = 1100;

		function step(ts) {
			if (start === null) start = ts;
			var p = Math.min((ts - start) / dur, 1);
			var eased = 1 - Math.pow(1 - p, 3);
			el.textContent = toFa(Math.round(target * eased));
			if (p < 1) requestAnimationFrame(step);
		}
		requestAnimationFrame(step);
	}

	if ('IntersectionObserver' in window && counters.length) {
		var co = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;
				runCounter(entry.target);
				co.unobserve(entry.target);
			});
		}, { threshold: 0.5 });
		Array.prototype.forEach.call(counters, function (el) { co.observe(el); });
	}

	/* ── آکاردئون سوالات متداول ────────────────────────────────────── */
	var faqList = document.getElementById('faqList');

	if (faqList) {
		faqList.addEventListener('click', function (e) {
			var btn = e.target.closest('.faq-q');
			if (!btn) return;

			var item = btn.parentNode;
			var isOpen = item.classList.contains('open');

			// فقط یک سوال هم‌زمان باز بماند
			Array.prototype.forEach.call(faqList.querySelectorAll('.faq-item.open'), function (other) {
				other.classList.remove('open');
				var q = other.querySelector('.faq-q');
				if (q) q.setAttribute('aria-expanded', 'false');
			});

			if (!isOpen) {
				item.classList.add('open');
				btn.setAttribute('aria-expanded', 'true');
			}
		});
	}

	/* ── فرم ثبت درخواست ───────────────────────────────────────────── */
	var form = document.getElementById('regForm');

	if (form) {
		var okBox = document.getElementById('formOk');

		function setError(input, message) {
			var field = input.closest('.field');
			var err = field ? field.querySelector('.err') : null;
			if (field) field.classList.toggle('invalid', !!message);
			if (err) err.textContent = message || '';
			return !message;
		}

		function validateName() {
			var input = form.elements.name;
			var v = input.value.trim();
			if (!v) return setError(input, 'لطفاً نام و نام خانوادگی خود را وارد کنید.');
			if (v.length < 3) return setError(input, 'نام واردشده کوتاه است.');
			return setError(input, '');
		}

		function validatePhone() {
			var input = form.elements.phone;
			var v = toEnDigits(input.value).replace(/[\s\-()]/g, '');
			if (!v) return setError(input, 'لطفاً شماره موبایل خود را وارد کنید.');
			if (!/^09\d{9}$/.test(v)) return setError(input, 'شماره موبایل معتبر نیست. مثال: ۰۹۱۲۳۴۵۶۷۸۹');
			return setError(input, '');
		}

		form.elements.name.addEventListener('blur', validateName);
		form.elements.phone.addEventListener('blur', validatePhone);
		form.elements.phone.addEventListener('input', function () {
			var field = this.closest('.field');
			if (field && field.classList.contains('invalid')) validatePhone();
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (okBox) okBox.hidden = true;

			var validName = validateName();
			var validPhone = validatePhone();

			if (!validName || !validPhone) {
				var firstBad = form.querySelector('.field.invalid input');
				if (firstBad) firstBad.focus();
				return;
			}

			// TODO: اتصال به بک‌اند (REST/admin-ajax وردپرس یا سرویس CRM)
			var payload = {
				name:     form.elements.name.value.trim(),
				phone:    toEnDigits(form.elements.phone.value).replace(/[\s\-()]/g, ''),
				business: form.elements.business.value.trim(),
				note:     form.elements.note.value.trim()
			};
			console.log('[حکمرانی بر بازار] درخواست ثبت‌شده:', payload);

			form.reset();
			if (okBox) {
				okBox.hidden = false;
				okBox.scrollIntoView({ block: 'nearest', behavior: reduceMotion ? 'auto' : 'smooth' });
			}
		});
	}
})();
