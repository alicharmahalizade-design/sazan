/* حرکت‌های نرم صفحه‌ی ارزیابی؛ بدون وابستگی و با پشتیبانی از reduced motion */
(function () {
	'use strict';

	function init(root) {
		if (!root || root.classList.contains('sazan-assessment-ready')) {
			return;
		}
		root.classList.add('sazan-assessment-ready');
		if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			root.classList.add('sazan-assessment-no-motion');
			return;
		}
		if (!('IntersectionObserver' in window)) {
			root.classList.add('sazan-assessment-no-motion');
			return;
		}
		root.classList.add('sazan-assessment-animate');
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-visible');
					observer.unobserve(entry.target);
				}
			});
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
		root.querySelectorAll('.sazan-assessment-section, .sazan-assessment-bottom-cta').forEach(function (section) {
			observer.observe(section);
		});
	}

	function boot() {
		document.querySelectorAll('.sazan-assessment-landing').forEach(init);
	}
	if (document.readyState !== 'loading') {
		boot();
	} else {
		document.addEventListener('DOMContentLoaded', boot);
	}
	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction('frontend/element_ready/sazan-assessment-landing.default', function ($scope) {
			init($scope[0] && $scope[0].querySelector('.sazan-assessment-landing'));
		});
	}
}());
