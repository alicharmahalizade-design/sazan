(function () {
	'use strict';

	var FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
	function fa(s) { return String(s).replace(/[0-9]/g, function (d) { return FA[d]; }); }

	/* یک «سند طرح» را کنترل می‌کند (اسلاید فعال، نقاط، شمارنده، پخش خودکار). */
	function initDoc(doc) {
		if (doc.dataset.csInit) return;
		doc.dataset.csInit = '1';

		var slides = Array.prototype.slice.call(doc.querySelectorAll('.szp-cs-slide'));
		var dots = Array.prototype.slice.call(doc.querySelectorAll('.szp-cs-dot'));
		var counters = Array.prototype.slice.call(doc.querySelectorAll('.szp-cs-counter'));
		var n = 0;
		slides.forEach(function (el) { n = Math.max(n, (parseInt(el.getAttribute('data-i'), 10) || 0) + 1); });
		if (n < 1) n = 1;

		var idx = 0, paused = false;

		function apply() {
			slides.forEach(function (el) { el.classList.toggle('is-active', (parseInt(el.getAttribute('data-i'), 10) || 0) === idx); });
			dots.forEach(function (el) { el.classList.toggle('is-active', (parseInt(el.getAttribute('data-i'), 10) || 0) === idx); });
			counters.forEach(function (el) {
				el.textContent = el.getAttribute('data-en')
					? ('0' + (idx + 1) + ' / 0' + n)
					: (fa(idx + 1) + ' / ' + fa(n));
			});
		}
		function go(i) { idx = ((i % n) + n) % n; apply(); }

		doc.querySelectorAll('[data-cs="next"]').forEach(function (b) { b.addEventListener('click', function () { go(idx + 1); }); });
		doc.querySelectorAll('[data-cs="prev"]').forEach(function (b) { b.addEventListener('click', function () { go(idx - 1); }); });
		dots.forEach(function (b) { b.addEventListener('click', function () { go(parseInt(b.getAttribute('data-i'), 10) || 0); }); });

		var stage = doc.querySelector('.szp-cs-stage') || doc;
		stage.addEventListener('mouseenter', function () { paused = true; });
		stage.addEventListener('mouseleave', function () { paused = false; });

		apply();

		var autoplay = doc.getAttribute('data-autoplay') !== '0';
		var interval = Math.max(2, parseInt(doc.getAttribute('data-interval'), 10) || 5) * 1000;
		if (autoplay && n > 1) {
			setInterval(function () {
				if (paused) return;
				if (doc.offsetParent === null) return; // طرح پنهان (سوییچ) پیش نرود
				go(idx + 1);
			}, interval);
		}
	}

	/* مقیاس‌بندی واکنش‌گرا: صحنه‌ی ۱۴۴۰px به عرض ظرف اسکیل می‌شود. */
	function fit(root) {
		var scaler = root.querySelector('.szp-cs-scaler');
		var wrap = root.querySelector('.szp-cs-stagewrap');
		if (!scaler || !wrap) return;
		var s = scaler.clientWidth / 1440;
		if (!isFinite(s) || s <= 0) return;
		wrap.style.transform = 'scale(' + s + ')';
		scaler.style.height = (440 * s) + 'px';
	}

	/* نوار سوییچ طرح. */
	function initSwitch(root) {
		var btns = Array.prototype.slice.call(root.querySelectorAll('.szp-cs-switch-btn'));
		if (!btns.length) return;
		var docs = Array.prototype.slice.call(root.querySelectorAll('.szp-cs-doc'));
		btns.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var d = btn.getAttribute('data-doc');
				btns.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
				docs.forEach(function (dc) { dc.classList.toggle('is-active', dc.getAttribute('data-doc') === d); });
				fit(root);
			});
		});
	}

	function initRoot(root) {
		if (root.dataset.csRoot) return;
		root.dataset.csRoot = '1';
		root.querySelectorAll('.szp-cs-doc').forEach(initDoc);
		initSwitch(root);
		fit(root);
		if (window.ResizeObserver) {
			var ro = new ResizeObserver(function () { fit(root); });
			ro.observe(root);
		} else {
			window.addEventListener('resize', function () { fit(root); });
		}
		// اطمینان از اسکیل درست پس از بارگذاری کامل/فونت‌ها.
		window.addEventListener('load', function () { fit(root); });
		setTimeout(function () { fit(root); }, 300);
	}

	function init() { document.querySelectorAll('.szp-cslider').forEach(initRoot); }
	if (document.readyState !== 'loading') init();
	else document.addEventListener('DOMContentLoaded', init);

	// سازگاری با ویرایشگر المنتور (رندر مجدد ویجت).
	if (window.jQuery) {
		window.jQuery(window).on('elementor/frontend/init', function () {
			if (window.elementorFrontend && elementorFrontend.hooks) {
				elementorFrontend.hooks.addAction('frontend/element_ready/szp_courses_slider.default', function ($scope) {
					$scope.find('.szp-cslider').each(function () { initRoot(this); });
				});
			}
		});
	}
})();
