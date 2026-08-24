(function () {
	'use strict';

	function copyText(value) {
		if (navigator.clipboard && window.isSecureContext) {
			return navigator.clipboard.writeText(value);
		}
		return new Promise(function (resolve, reject) {
			var field = document.createElement('textarea');
			field.value = value;
			field.setAttribute('readonly', '');
			field.style.position = 'fixed';
			field.style.opacity = '0';
			document.body.appendChild(field);
			field.select();
			try {
				document.execCommand('copy') ? resolve() : reject(new Error('copy'));
			} catch (error) {
				reject(error);
			}
			document.body.removeChild(field);
		});
	}

	function init() {
		var root = document.querySelector('.szp-settings-center');
		if (!root) return;

		var form = root.querySelector('[data-settings-form]');
		var tabs = Array.prototype.slice.call(root.querySelectorAll('.szp-set-tab'));
		var panels = Array.prototype.slice.call(root.querySelectorAll('.szp-set-panel'));
		var search = root.querySelector('[data-settings-search]');
		var emptySearch = root.querySelector('.szp-set-empty-search');
		var saveState = root.querySelector('[data-save-state]');
		var activePanel = '';
		var dirty = false;

		function activate(id, updateUrl) {
			var exists = tabs.some(function (tab) {
				return tab.getAttribute('data-tab') === id;
			});
			if (!exists && tabs[0]) id = tabs[0].getAttribute('data-tab');
			activePanel = id;

			tabs.forEach(function (tab) {
				var selected = tab.getAttribute('data-tab') === id;
				tab.classList.toggle('active', selected);
				tab.setAttribute('aria-selected', selected ? 'true' : 'false');
				tab.setAttribute('tabindex', selected ? '0' : '-1');
			});
			panels.forEach(function (panel) {
				var selected = panel.getAttribute('data-panel') === id;
				panel.classList.toggle('active', selected);
				panel.hidden = !selected;
			});
			if (updateUrl !== false) {
				try { history.replaceState(null, '', '#' + id); } catch (error) {}
			}
		}

		function clearSearch() {
			if (!search) return;
			search.value = '';
			runSearch('');
		}

		function runSearch(rawQuery) {
			var query = (rawQuery || '').trim().toLocaleLowerCase('fa');
			var searching = query.length > 0;
			var found = 0;
			root.classList.toggle('is-searching', searching);

			Array.prototype.slice.call(root.querySelectorAll('[data-search-terms]')).forEach(function (item) {
				var haystack = ((item.getAttribute('data-search-terms') || '') + ' ' + (item.textContent || '')).toLocaleLowerCase('fa');
				var matches = !searching || haystack.indexOf(query) !== -1;
				item.classList.toggle('is-filtered-out', !matches);
				if (searching && matches) {
					found += 1;
					if (item.tagName === 'DETAILS') item.open = true;
				}
			});

			panels.forEach(function (panel) {
				if (searching) {
					var visibleItems = panel.querySelectorAll('[data-search-terms]:not(.is-filtered-out)').length;
					panel.hidden = visibleItems === 0;
					panel.classList.toggle('search-match', visibleItems > 0);
				} else {
					panel.classList.remove('search-match');
					panel.hidden = panel.getAttribute('data-panel') !== activePanel;
				}
			});

			if (emptySearch) emptySearch.hidden = !searching || found > 0;
		}

		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				clearSearch();
				activate(tab.getAttribute('data-tab'));
			});
			tab.addEventListener('keydown', function (event) {
				if (['ArrowRight', 'ArrowLeft', 'Home', 'End'].indexOf(event.key) === -1) return;
				event.preventDefault();
				var index = tabs.indexOf(tab);
				if (event.key === 'Home') index = 0;
				else if (event.key === 'End') index = tabs.length - 1;
				else if (event.key === 'ArrowRight') index = (index - 1 + tabs.length) % tabs.length;
				else index = (index + 1) % tabs.length;
				clearSearch();
				activate(tabs[index].getAttribute('data-tab'));
				tabs[index].focus();
			});
		});

		var hash = (window.location.hash || '').replace('#', '');
		activate(hash || (tabs[0] && tabs[0].getAttribute('data-tab')), false);

		if (search) {
			search.addEventListener('input', function () {
				runSearch(search.value);
			});
			search.addEventListener('keydown', function (event) {
				if (event.key === 'Escape') {
					clearSearch();
					search.blur();
				}
			});
		}

		Array.prototype.slice.call(root.querySelectorAll('[data-copy]')).forEach(function (button) {
			button.addEventListener('click', function () {
				var original = button.textContent;
				copyText(button.getAttribute('data-copy') || '').then(function () {
					button.textContent = 'کپی شد';
					button.classList.add('copied');
					window.setTimeout(function () {
						button.textContent = original;
						button.classList.remove('copied');
					}, 1800);
				});
			});
		});

		var preview = root.querySelector('[data-settings-preview]');
		var previewVariables = {
			color_primary: '--preview-primary',
			color_secondary: '--preview-secondary',
			color_success: '--preview-success',
			color_danger: '--preview-danger',
			color_background: '--preview-background',
			color_surface: '--preview-surface',
			color_text: '--preview-text',
			color_muted: '--preview-muted',
			color_border: '--preview-border'
		};
		Array.prototype.slice.call(root.querySelectorAll('[data-preview-var]')).forEach(function (input) {
			function updatePreview() {
				var key = input.getAttribute('data-preview-var');
				if (preview && previewVariables[key]) {
					preview.style.setProperty(previewVariables[key], input.value);
				}
				var code = input.parentElement && input.parentElement.querySelector('code');
				if (code) code.textContent = input.value.toUpperCase();
			}
			input.addEventListener('input', updatePreview);
			updatePreview();
		});

		if (form) {
			function markDirty(event) {
				if (event && event.target && event.target.closest('.szp-set-test')) return;
				dirty = true;
				root.classList.add('has-unsaved-changes');
				if (saveState) saveState.textContent = 'تغییر ذخیره‌نشده دارید.';
			}
			form.addEventListener('input', markDirty);
			form.addEventListener('change', markDirty);
			form.addEventListener('submit', function () {
				dirty = false;
				if (saveState) saveState.textContent = 'در حال ذخیره تنظیمات…';
			});
			window.addEventListener('beforeunload', function (event) {
				if (!dirty) return;
				event.preventDefault();
				event.returnValue = '';
			});
		}

		var testButton = root.querySelector('.szp-ev-testsms');
		if (testButton) {
			testButton.addEventListener('click', function () {
				var number = root.querySelector('#szp-ev-testnum');
				var message = root.querySelector('.szp-ev-testmsg');
				var endpoint = window.ajaxurl || '';
				if (!number || !number.value.trim()) {
					if (message) message.textContent = 'شماره موبایل را وارد کنید.';
					number && number.focus();
					return;
				}
				testButton.disabled = true;
				testButton.textContent = 'در حال ارسال…';
				if (message) {
					message.className = 'szp-ev-testmsg';
					message.textContent = 'لطفاً صبر کنید.';
				}
				var data = new FormData();
				data.append('action', 'szp_eval_test_sms');
				data.append('nonce', testButton.getAttribute('data-nonce') || '');
				data.append('to', number.value);
				fetch(endpoint, { method: 'POST', credentials: 'same-origin', body: data })
					.then(function (response) { return response.json(); })
					.then(function (result) {
						var text = result && result.data && result.data.msg ? result.data.msg : 'پاسخ نامعتبر از سرور.';
						if (message) {
							message.textContent = text;
							message.classList.add(result && result.success ? 'success' : 'error');
						}
					})
					.catch(function () {
						if (message) {
							message.textContent = 'ارتباط با سرور برقرار نشد.';
							message.classList.add('error');
						}
					})
					.finally(function () {
						testButton.disabled = false;
						testButton.textContent = 'ارسال پیامک آزمایشی';
					});
			});
		}
	}

	if (document.readyState !== 'loading') init();
	else document.addEventListener('DOMContentLoaded', init);
})();
