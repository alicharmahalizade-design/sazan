(function () {
	'use strict';

	function copyText(text) {
		if (navigator.clipboard && window.isSecureContext) {
			return navigator.clipboard.writeText(text);
		}
		var input = document.createElement('textarea');
		input.value = text;
		input.style.position = 'fixed';
		input.style.opacity = '0';
		document.body.appendChild(input);
		input.select();
		document.execCommand('copy');
		input.remove();
		return Promise.resolve();
	}

	function init(root) {
		(root || document).querySelectorAll('.sazan-sb-copy-link').forEach(function (button) {
			if (button.dataset.sazanReady) return;
			button.dataset.sazanReady = '1';
			button.addEventListener('click', function () {
				copyText(button.dataset.copy || window.location.href).then(function () {
					button.classList.add('is-copied');
					button.setAttribute('aria-label', 'لینک کپی شد');
					window.setTimeout(function () {
						button.classList.remove('is-copied');
						button.setAttribute('aria-label', 'کپی لینک');
					}, 1600);
				});
			});
		});

		(root || document).querySelectorAll('.sazan-sb-outline').forEach(function (outline) {
			if (outline.dataset.sazanReady) return;
			outline.dataset.sazanReady = '1';
			outline.querySelectorAll('details').forEach(function (item) {
				item.addEventListener('toggle', function () {
					if (!item.open) return;
					outline.querySelectorAll('details[open]').forEach(function (other) {
						if (other !== item) other.open = false;
					});
				});
			});
		});

		(root || document).querySelectorAll('.sazan-sb-backtop').forEach(function (button) {
			if (button.dataset.sazanReady) return;
			button.dataset.sazanReady = '1';
			button.addEventListener('click', function () {
				/* Direct assignments keep this reliable inside Elementor previews and embedded browsers. */
				document.documentElement.scrollTop = 0;
				document.body.scrollTop = 0;
				window.scrollTo(0, 0);
			});
		});

		var tocLinks = Array.prototype.slice.call((root || document).querySelectorAll('.sazan-sb-toc a[href^="#"]'));
		if ('IntersectionObserver' in window && tocLinks.length) {
			var headings = tocLinks.map(function (link) {
				var selector = link.getAttribute('href');
				if (!selector || selector === '#') return null;
				try {
					return document.querySelector(selector);
				} catch (error) {
					return null;
				}
			}).filter(Boolean);
			var observer = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting) return;
					tocLinks.forEach(function (link) { link.classList.toggle('is-active', link.getAttribute('href') === '#' + entry.target.id); });
				});
			}, { rootMargin: '-15% 0px -70% 0px' });
			headings.forEach(function (heading) { observer.observe(heading); });
		}
	}

	document.addEventListener('DOMContentLoaded', function () { init(document); });
	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction('frontend/element_ready/global', function (scope) { init(scope[0] || scope); });
	}
})();
