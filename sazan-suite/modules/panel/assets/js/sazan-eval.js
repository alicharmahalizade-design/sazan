(function () {
	'use strict';

	var FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
	var UI = (window.SZP_FRONT && window.SZP_FRONT.ui) || {};

	function faDigits(s) { return String(s).replace(/[0-9]/g, function (d) { return FA[d]; }); }
	function latin(s) { return String(s).replace(/[۰-۹]/g, function (d) { return FA.indexOf(d); }).replace(/[٠-٩]/g, function (d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); }); }
	function toNumber(s) { var t = latin(s).replace(/[^\d.]/g, ''); return t === '' ? 0 : parseFloat(t); }
	function group(n) { return faDigits(Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '٬')); }

	function formatMoneyInput(input) {
		var raw = latin(input.value).replace(/[^\d]/g, '');
		input.value = raw === '' ? '' : faDigits(raw.replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, '٬'));
	}

	var STATUS = {
		beyond:  { label: UI.statusBeyond || 'بیشتر از هدف', color: '#0284c7', symbol: '↑' },
		success: { label: UI.statusSuccess || 'هدف کامل محقق شد', color: '#15803d', symbol: '✓' },
		improve: { label: UI.statusImprove || 'نزدیک به هدف', color: '#b45309', symbol: '≈' },
		ontrack: { label: UI.statusOntrack || 'کمتر از هدف', color: '#b91c1c', symbol: '↓' },
		pending: { label: UI.statusPending || 'نتیجه ثبت نشده', color: '#475569', symbol: '…' }
	};

	function computeStatus(target, result, near) {
		if (!target || target <= 0) return 'pending';
		if (result > target) return 'beyond';
		if (result === target) return 'success';
		if (result >= target * near) return 'improve';
		return 'ontrack';
	}

	function ajax(action, data, cb) {
		data.append('action', action);
		data.append('nonce', (window.SZP_FRONT && SZP_FRONT.nonce) || '');
		fetch((window.SZP_FRONT && SZP_FRONT.ajax) || '', {
			method: 'POST', credentials: 'same-origin', body: data
		}).then(function (r) { return r.json(); })
			.then(cb)
			.catch(function () { cb({ success: false, data: { msg: 'خطای ارتباط با سرور.' } }); });
	}

	function initEval(root) {
		if (root.dataset.evInit) return;
		root.dataset.evInit = '1';

		var currency = root.getAttribute('data-currency') || '';
		var near = parseFloat(root.getAttribute('data-near')) || 0.85;
		initProfile(root);
		initGrowthCountdown(root);
		initGrowthWizard(root);
		initGrowthTeam(root);
		initGrowthMobileApp(root);

		// دکمه‌های ویرایش درجای تارگت/نتیجه (کارت مرحله‌ای + سوابق).
		initEditField(root, currency, {
			btn: 'szp-ev-edit-target', cell: 'szp-ev-targetcell', val: 'szp-ev-target-val',
			attr: 'data-target', action: 'szp_eval_edit_target', allowZero: false, label: 'تارگت'
		});
		initEditField(root, currency, {
			btn: 'szp-ev-edit-result', cell: 'szp-ev-resultcell', val: 'szp-ev-result-val',
			attr: 'data-result', action: 'szp_eval_edit_result', allowZero: true, label: 'نتیجه'
		});

		// فرمِ فعالِ ثبت (تارگت یا نتیجه) — همیشه یکی در کارت است.
		root.querySelectorAll('.szp-ev-form').forEach(function (form) {
			initForm(form, currency, near);
		});
	}

	function initGrowthMobileApp(root) {
		var navs = root.querySelectorAll('[data-mobile-quick]');
		if (!navs.length) return;

		navs.forEach(function (nav) {
			if (nav.dataset.mobileQuickInit) return;
			nav.dataset.mobileQuickInit = '1';
			var pairs = [];
			nav.querySelectorAll('[data-mobile-quick-target]').forEach(function (button) {
				var key = button.getAttribute('data-mobile-quick-target');
				var panel = root.querySelector('[data-mobile-panel="' + key + '"]');
				if (!panel) {
					button.hidden = true;
					return;
				}
				panel.classList.add('szp-mobile-optional');
				pairs.push({ button: button, panel: panel });
				button.addEventListener('click', function () {
					var shouldOpen = !panel.classList.contains('is-mobile-open');
					pairs.forEach(function (pair) {
						pair.panel.classList.remove('is-mobile-open');
						pair.button.classList.remove('is-active');
						pair.button.setAttribute('aria-expanded', 'false');
					});
					if (shouldOpen) {
						panel.classList.add('is-mobile-open');
						button.classList.add('is-active');
						button.setAttribute('aria-expanded', 'true');
						setTimeout(function () {
							var top = panel.getBoundingClientRect().top;
							if (top < 0 || top > window.innerHeight * 0.7) {
								panel.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
							}
						}, 50);
					}
				});
			});
			nav.setAttribute('data-mobile-quick-count', String(pairs.length));
		});
	}

	function initGrowthTeam(root) {
		var team = root.querySelector('[data-growth-team]');
		if (!team || team.dataset.teamInit) return;
		team.dataset.teamInit = '1';
		var search = team.querySelector('[data-team-search]');
		var filter = team.querySelector('[data-team-filter]');
		var found = team.querySelector('[data-team-found]');
		var empty = team.querySelector('[data-team-no-result]');
		var members = Array.prototype.slice.call(team.querySelectorAll('[data-team-member]'));
		var groups = team.querySelector('[data-team-groups]');
		var membersView = team.querySelector('[data-team-members-view]');
		var selectedName = team.querySelector('[data-team-selected-name]');
		var activeGroup = '';
		var dialog = team.querySelector('[data-team-dialog]');
		var dialogBody = team.querySelector('[data-team-dialog-body]');
		var dialogPanel = dialog ? dialog.querySelector('[role="dialog"]') : null;
		var lastOpener = null;

		function normalize(value) {
			return latin(value || '').replace(/ي/g, 'ی').replace(/ك/g, 'ک').replace(/\s+/g, ' ').trim().toLowerCase();
		}

		function applyFilters() {
			var query = normalize(search ? search.value : '');
			var status = filter ? filter.value : 'all';
			var visible = 0;
			members.forEach(function (member) {
				var matchesText = !query || normalize(member.getAttribute('data-team-search-text')).indexOf(query) !== -1;
				var matchesStatus = status === 'all' || member.getAttribute('data-team-status') === status;
				var matchesGroup = !activeGroup || member.getAttribute('data-team-group-id') === activeGroup;
				member.hidden = !(matchesText && matchesStatus && matchesGroup);
				if (!member.hidden) visible++;
			});
			if (found) found.textContent = faDigits(visible) + ' نفر';
			if (empty) empty.hidden = visible !== 0;
		}

		if (search) search.addEventListener('input', applyFilters);
		if (filter) filter.addEventListener('change', applyFilters);
		team.querySelectorAll('[data-team-group-open]').forEach(function (button) {
			button.addEventListener('click', function () {
				activeGroup = button.getAttribute('data-team-group-open') || '';
				if (selectedName) selectedName.textContent = button.getAttribute('data-team-group-name') || 'تیم انتخاب‌شده';
				if (groups) groups.hidden = true;
				if (membersView) membersView.hidden = false;
				if (search) search.value = '';
				if (filter) filter.value = 'all';
				applyFilters();
				var back = team.querySelector('[data-team-groups-back]');
				if (back) back.focus();
			});
		});
		var groupsBack = team.querySelector('[data-team-groups-back]');
		if (groupsBack) {
			groupsBack.addEventListener('click', function () {
				activeGroup = '';
				if (membersView) membersView.hidden = true;
				if (groups) groups.hidden = false;
				var firstGroup = groups ? groups.querySelector('[data-team-group-open]') : null;
				if (firstGroup) firstGroup.focus();
			});
		}

		function focusable() {
			if (!dialogPanel) return [];
			return Array.prototype.slice.call(dialogPanel.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'))
				.filter(function (item) { return !item.disabled && item.offsetParent !== null; });
		}

		function closeDialog() {
			if (!dialog || dialog.hidden) return;
			dialog.classList.remove('is-open');
			document.body.classList.remove('szp-growth-team-dialog-open');
			setTimeout(function () {
				dialog.hidden = true;
				if (dialogBody) dialogBody.innerHTML = '';
				if (lastOpener) lastOpener.focus();
			}, 180);
		}

		team.querySelectorAll('[data-team-report-open]').forEach(function (button) {
			button.addEventListener('click', function () {
				if (!dialog || !dialogBody || !dialogPanel) return;
				var id = button.getAttribute('data-team-report-open');
				var template = team.querySelector('[data-team-report-template="' + id + '"]');
				if (!template) return;
				lastOpener = button;
				dialogBody.innerHTML = template.innerHTML;
				dialog.hidden = false;
				document.body.classList.add('szp-growth-team-dialog-open');
				window.requestAnimationFrame(function () {
					dialog.classList.add('is-open');
					dialogPanel.focus();
				});
			});
		});

		if (dialog) {
			dialog.querySelectorAll('[data-team-dialog-close]').forEach(function (button) {
				button.addEventListener('click', closeDialog);
			});
			dialog.addEventListener('keydown', function (event) {
				if (event.key === 'Escape') {
					event.preventDefault();
					closeDialog();
					return;
				}
				if (event.key !== 'Tab') return;
				var items = focusable();
				if (!items.length) return;
				var first = items[0];
				var last = items[items.length - 1];
				if (event.shiftKey && document.activeElement === first) {
					event.preventDefault();
					last.focus();
				} else if (!event.shiftKey && document.activeElement === last) {
					event.preventDefault();
					first.focus();
				}
			});
		}
	}

	function initGrowthCountdown(root) {
		root.querySelectorAll('[data-growth-countdown]').forEach(function (timer) {
			if (timer.dataset.countdownInit) return;
			timer.dataset.countdownInit = '1';
			var end = parseInt(timer.getAttribute('data-growth-countdown'), 10) * 1000;
			var parts = {
				days: timer.querySelector('[data-countdown-part="days"]'),
				hours: timer.querySelector('[data-countdown-part="hours"]'),
				minutes: timer.querySelector('[data-countdown-part="minutes"]')
			};
			var interval = null;

			function two(value) {
				return faDigits(String(value).padStart(2, '0'));
			}

			function render() {
				var remaining = Math.max(0, end - Date.now());
				var totalMinutes = Math.ceil(remaining / 60000);
				var days = Math.floor(totalMinutes / 1440);
				var hours = Math.floor((totalMinutes % 1440) / 60);
				var minutes = totalMinutes % 60;
				if (parts.days) parts.days.textContent = faDigits(days);
				if (parts.hours) parts.hours.textContent = two(hours);
				if (parts.minutes) parts.minutes.textContent = two(minutes);
				timer.setAttribute('aria-label', faDigits(days) + ' روز و ' + faDigits(hours) + ' ساعت و ' + faDigits(minutes) + ' دقیقه تا بازشدن ثبت نتیجه');
				if (remaining <= 0 && interval) {
					window.clearInterval(interval);
					window.location.reload();
				}
			}

			render();
			if (end > Date.now()) interval = window.setInterval(render, 30000);
		});
	}

	function initForm(form, currency, near) {
		var mode = form.getAttribute('data-mode');
		var session = form.getAttribute('data-session') || '';
		var target = parseFloat(form.getAttribute('data-target')) || 0;
		var input = form.querySelector('[data-growth-field="amount"]') || form.querySelector('.szp-ev-amount');
		var btn = form.querySelector('.szp-ev-submit');
		var preview = form.querySelector('.szp-ev-preview');
		var msg = form.querySelector('.szp-ev-msg');
		if (!input || !btn) return;
		formatMoneyInput(input);
		initActionRepeater(form);
		initStepForm(form);

		function showMsg(text, kind) {
			if (!msg) return;
			msg.textContent = text;
			msg.className = 'szp-ev-msg is-show' + (kind ? ' is-' + kind : '');
		}

		function renderPreview() {
			var val = toNumber(input.value);
			if (!val) { preview.textContent = ''; preview.className = 'szp-ev-preview'; return; }
			if (mode === 'result' && target > 0) {
				var pct = Math.round(val / target * 100);
				var st = STATUS[computeStatus(target, val, near)];
				preview.innerHTML = 'فروش واقعی: <b>' + group(val) + ' ' + currency + '</b> — تحقق ' +
					faDigits(pct) + '٪ — <span class="szp-ev-pv-badge">' + st.symbol + ' ' + st.label + '</span>';
				preview.style.setProperty('--c', st.color);
				preview.className = 'szp-ev-preview is-show has-status';
			} else {
				preview.innerHTML = 'تارگت: <b>' + group(val) + ' ' + currency + '</b>';
				preview.className = 'szp-ev-preview is-show';
			}
		}

		input.addEventListener('input', function () {
			formatMoneyInput(input);
			renderPreview();
		});
		renderPreview();

		btn.addEventListener('click', function () {
			if (btn.disabled) return;
			var val = toNumber(input.value);
			var allowZero = mode === 'result' || input.getAttribute('data-allow-zero') === '1';
			if ((allowZero && input.value.trim() === '') || (!allowZero && (!val || val <= 0))) {
				showMsg('یک عدد معتبر وارد کنید.', 'err'); input.focus(); return;
			}
			var invalid = null;
			form.querySelectorAll('[data-growth-field]').forEach(function (field) {
				field.removeAttribute('aria-invalid');
				if (invalid) return;
				if (String(field.value || '').trim() === '') {
					invalid = field;
					field.setAttribute('aria-invalid', 'true');
				}
			});
			if (invalid) {
				showMsg('لطفاً همه پرسش‌ها را تکمیل کنید.', 'err');
				invalid.focus();
				return;
			}
			if (mode === 'target') {
				var actionItems = Array.prototype.slice.call(form.querySelectorAll('[data-growth-action-item]'));
				var filledActions = actionItems.filter(function (field) { return String(field.value || '').trim() !== ''; });
				actionItems.forEach(function (field) { field.removeAttribute('aria-invalid'); });
				if (!filledActions.length) {
					showMsg('حداقل یک اقدام اصلی را با توضیح کامل بنویسید.', 'err');
					if (actionItems[0]) {
						actionItems[0].setAttribute('aria-invalid', 'true');
						actionItems[0].focus();
					}
					return;
				}
			}
			if (mode === 'result' && target > 0 && val > target * 3) {
				if (!confirm('نتیجه‌ای که وارد کردید چند برابر تارگت است. مطمئنید؟')) return;
			}
			btn.disabled = true;
			showMsg('در حال ثبت…', '');
			var d = new FormData();
			form.querySelectorAll('[data-growth-field]').forEach(function (field) {
				d.append(field.getAttribute('data-growth-field'), field.value);
			});
			if (mode === 'target') {
				form.querySelectorAll('[data-growth-action-item]').forEach(function (field) {
					if (String(field.value || '').trim() !== '') d.append('actions[]', field.value);
				});
			}
			d.set('amount', String(val));
			d.append('session', session);
			ajax(mode === 'result' ? 'szp_eval_result' : 'szp_eval_target', d, function (res) {
				if (res && res.success) {
					showMsg((res.data && res.data.msg) || 'ثبت شد ✓', 'ok');
					setTimeout(function () { window.location.reload(); }, 800);
				} else {
					btn.disabled = false;
					showMsg((res && res.data && res.data.msg) || 'خطا در ثبت.', 'err');
				}
			});
		});
	}

	function initStepForm(form) {
		var steps = Array.prototype.slice.call(form.querySelectorAll('[data-growth-form-step]'));
		var previous = form.querySelector('[data-growth-step-prev]');
		var next = form.querySelector('[data-growth-step-next]');
		var progress = form.querySelector('[data-growth-step-progress]');
		var submit = form.querySelector('[data-growth-final-submit]');
		var msg = form.querySelector('.szp-ev-msg');
		if (steps.length < 2 || !previous || !next || !progress || !submit) return;
		var current = 0;

		function showError(text, field) {
			if (msg) {
				msg.textContent = text;
				msg.className = 'szp-ev-msg is-show is-err';
			}
			if (field) {
				field.setAttribute('aria-invalid', 'true');
				field.focus();
			}
		}

		function validateStep() {
			var invalid = null;
			steps[current].querySelectorAll('[data-growth-field]').forEach(function (field) {
				field.removeAttribute('aria-invalid');
				if (!invalid && String(field.value || '').trim() === '') invalid = field;
			});
			if (invalid) {
				showError('لطفاً این مرحله را کامل کنید.', invalid);
				return false;
			}
			var amount = steps[current].querySelector('[data-growth-field="amount"]');
			if (amount && form.getAttribute('data-mode') === 'target' && toNumber(amount.value) <= 0) {
				showError('یک تارگت مالی معتبر وارد کنید.', amount);
				return false;
			}
			if (form.getAttribute('data-mode') === 'target' && current === 1) {
				var actions = Array.prototype.slice.call(steps[current].querySelectorAll('[data-growth-action-item]'));
				var filled = actions.filter(function (field) { return String(field.value || '').trim() !== ''; });
				if (!filled.length) {
					showError('حداقل یک اقدام اصلی را بنویسید.', actions[0] || null);
					return false;
				}
			}
			if (msg) {
				msg.textContent = '';
				msg.className = 'szp-ev-msg';
			}
			return true;
		}

		function render() {
			steps.forEach(function (step, index) { step.hidden = index !== current; });
			previous.hidden = current === 0;
			next.hidden = current === steps.length - 1;
			submit.hidden = current !== steps.length - 1;
			progress.textContent = 'مرحله ' + faDigits(current + 1) + ' از ' + faDigits(steps.length);
		}

		next.addEventListener('click', function () {
			if (!validateStep()) return;
			current++;
			render();
			var field = steps[current].querySelector('input, textarea, select');
			if (field) field.focus();
		});
		previous.addEventListener('click', function () {
			if (current <= 0) return;
			current--;
			render();
			var field = steps[current].querySelector('input, textarea, select');
			if (field) field.focus();
		});
		render();
	}

	function initActionRepeater(form) {
		var list = form.querySelector('[data-growth-action-list]');
		var add = form.querySelector('[data-growth-add-action]');
		if (!list || !add) return;

		function renumber() {
			var rows = list.querySelectorAll('.szp-growth-action-row');
			rows.forEach(function (row, index) {
				var number = row.querySelector('[data-growth-action-number]');
				var remove = row.querySelector('[data-growth-remove-action]');
				if (number) number.textContent = faDigits(index + 1);
				if (remove) remove.hidden = rows.length === 1;
			});
		}

		function bindRemove(button) {
			if (!button || button.dataset.bound) return;
			button.dataset.bound = '1';
			button.addEventListener('click', function () {
				var rows = list.querySelectorAll('.szp-growth-action-row');
				if (rows.length <= 1) return;
				var row = button.closest('.szp-growth-action-row');
				if (row) row.remove();
				renumber();
			});
		}

		list.querySelectorAll('[data-growth-remove-action]').forEach(bindRemove);
		add.addEventListener('click', function () {
			var row = document.createElement('div');
			row.className = 'szp-growth-action-row';

			var label = document.createElement('label');
			var title = document.createElement('span');
			title.appendChild(document.createTextNode((UI.actionLabel || 'اقدام') + ' '));
			var number = document.createElement('b');
			number.setAttribute('data-growth-action-number', '');
			title.appendChild(number);

			var textarea = document.createElement('textarea');
			textarea.rows = 4;
			textarea.setAttribute('data-growth-action-item', '');
			textarea.placeholder = UI.actionPlaceholder || 'این اقدام را دقیق و قابل اجرا توضیح دهید';
			label.appendChild(title);
			label.appendChild(textarea);

			var remove = document.createElement('button');
			remove.type = 'button';
			remove.className = 'szp-growth-remove-action';
			remove.setAttribute('data-growth-remove-action', '');
			remove.setAttribute('aria-label', UI.actionRemove || 'حذف اقدام');
			remove.textContent = UI.actionRemove || 'حذف اقدام';

			row.appendChild(label);
			row.appendChild(remove);
			list.appendChild(row);
			bindRemove(remove);
			renumber();
			textarea.focus();
		});
		renumber();
	}

	function initProfile(root) {
		var card = root.querySelector('[data-growth-profile-form]') || root.querySelector('.szp-growth-profile');
		if (!card) return;
		var btn = card.querySelector('[data-growth-action="save-profile"]');
		var msg = card.querySelector('.szp-growth-msg');
		if (!btn) return;

		function show(text, kind) {
			if (!msg) return;
			msg.textContent = text;
			msg.className = 'szp-growth-msg is-show' + (kind ? ' is-' + kind : '');
		}

		btn.addEventListener('click', function () {
			if (btn.disabled) return;
			var fields = card.querySelectorAll('[data-profile]');
			var firstInvalid = null;
			fields.forEach(function (field) {
				field.removeAttribute('aria-invalid');
				if (!String(field.value || '').trim() && !firstInvalid) {
					firstInvalid = field;
					field.setAttribute('aria-invalid', 'true');
				}
			});
			if (firstInvalid) {
				show('لطفاً همه اطلاعات پایه را تکمیل کنید.', 'err');
				firstInvalid.focus();
				return;
			}
			var data = new FormData();
			fields.forEach(function (field) {
				data.append('profile[' + field.getAttribute('data-profile') + ']', field.value);
			});
			btn.disabled = true;
			show('در حال ذخیره…', '');
			ajax('szp_growth_profile', data, function (res) {
				if (res && res.success) {
					show((res.data && res.data.msg) || 'ذخیره شد.', 'ok');
					setTimeout(function () { window.location.reload(); }, 500);
				} else {
					btn.disabled = false;
					show((res && res.data && res.data.msg) || 'خطا در ذخیره.', 'err');
				}
			});
		});
	}

	function initGrowthWizard(root) {
		var modal = root.querySelector('[data-growth-wizard]');
		var openers = root.querySelectorAll('[data-growth-wizard-open]');
		if (!modal || !openers.length || modal.dataset.wizardInit) return;
		modal.dataset.wizardInit = '1';
		var panel = modal.querySelector('.szp-growth-wizard-panel');
		var title = modal.querySelector('#szp-growth-wizard-title');
		var views = Array.prototype.slice.call(modal.querySelectorAll('[data-growth-wizard-view-panel]'));
		var closeButtons = modal.querySelectorAll('[data-growth-wizard-close]');
		var lastFocused = null;
		var closeTimer = null;

		function focusable() {
			return Array.prototype.slice.call(modal.querySelectorAll('button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), a[href]'))
				.filter(function (el) { return el.offsetParent !== null && !el.classList.contains('szp-growth-wizard-backdrop'); });
		}

		function selectView(key) {
			var selected = null;
			views.forEach(function (view) {
				var match = view.getAttribute('data-growth-wizard-view-panel') === key;
				view.hidden = !match;
				if (match) selected = view;
			});
			if (!selected && views.length) {
				selected = views[0];
				selected.hidden = false;
			}
			if (selected && title) {
				title.textContent = selected.getAttribute('data-growth-wizard-view-title') || title.textContent;
			}
		}

		function openWizard(opener) {
			if (closeTimer) window.clearTimeout(closeTimer);
			lastFocused = opener || document.activeElement;
			selectView((opener && opener.getAttribute('data-growth-wizard-view')) || 'primary');
			modal.hidden = false;
			document.body.classList.add('szp-growth-modal-open');
			window.requestAnimationFrame(function () {
				modal.classList.add('is-open');
				var items = focusable();
				(items.length ? items[0] : panel).focus();
			});
		}

		function closeWizard() {
			modal.classList.remove('is-open');
			document.body.classList.remove('szp-growth-modal-open');
			closeTimer = window.setTimeout(function () {
				modal.hidden = true;
				if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
			}, 180);
		}

		openers.forEach(function (opener) {
			opener.addEventListener('click', function () { openWizard(opener); });
		});
		closeButtons.forEach(function (button) { button.addEventListener('click', closeWizard); });
		modal.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				event.preventDefault();
				closeWizard();
				return;
			}
			if (event.key !== 'Tab') return;
			var items = focusable();
			if (!items.length) {
				event.preventDefault();
				panel.focus();
				return;
			}
			var first = items[0];
			var last = items[items.length - 1];
			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		});
	}

	/* ویرایش درجای تارگت/نتیجه‌ی جلسات ثبت‌شده */
	function initEditField(root, currency, cfg) {
		root.querySelectorAll('.' + cfg.btn).forEach(function (btn) {
			btn.addEventListener('click', function () {
				var cell = btn.closest('.' + cfg.cell) || btn.parentNode;
				if (!cell || cell.querySelector('.szp-ev-edit-box')) return;

				var week = btn.getAttribute('data-week');
				var cur = parseFloat(btn.getAttribute(cfg.attr)) || 0;
				var valSpan = cell.querySelector('.' + cfg.val);

				btn.style.display = 'none';
				if (valSpan) valSpan.style.display = 'none';

				var box = document.createElement('div');
				box.className = 'szp-ev-edit-box';

				var inp = document.createElement('input');
				inp.type = 'text';
				inp.inputMode = 'numeric';
				inp.className = 'szp-ev-edit-input';
				inp.placeholder = cfg.label;
				inp.value = cur > 0 ? group(cur) : '';

				var save = document.createElement('button');
				save.type = 'button';
				save.className = 'szp-ev-edit-save';
				save.textContent = 'ذخیره';

				var cancel = document.createElement('button');
				cancel.type = 'button';
				cancel.className = 'szp-ev-edit-cancel';
				cancel.textContent = 'انصراف';

				var note = document.createElement('span');
				note.className = 'szp-ev-edit-note';

				box.appendChild(inp);
				box.appendChild(save);
				box.appendChild(cancel);
				box.appendChild(note);
				cell.appendChild(box);
				inp.focus();

				function close() {
					box.remove();
					btn.style.display = '';
					if (valSpan) valSpan.style.display = '';
				}

				function invalid() {
					var raw = inp.value.trim();
					if (raw === '') return true;
					var v = toNumber(inp.value);
					return cfg.allowZero ? (v < 0) : (!v || v <= 0);
				}

				inp.addEventListener('input', function () {
					formatMoneyInput(inp);
					var raw = inp.value.trim();
					var v = toNumber(inp.value);
					note.textContent = raw === '' ? '' : group(v) + ' ' + currency;
				});
				inp.addEventListener('keydown', function (e) {
					if (e.key === 'Enter') { e.preventDefault(); save.click(); }
					else if (e.key === 'Escape') { close(); }
				});
				cancel.addEventListener('click', close);

				save.addEventListener('click', function () {
					if (invalid()) { note.textContent = 'یک عدد معتبر وارد کنید.'; return; }
					var v = toNumber(inp.value);
					save.disabled = true;
					cancel.disabled = true;
					note.textContent = 'در حال ذخیره…';
					var d = new FormData();
					d.append('week', String(week));
					d.append('amount', String(v));
					ajax(cfg.action, d, function (res) {
						if (res && res.success) {
							note.textContent = 'ذخیره شد ✓';
							setTimeout(function () { window.location.reload(); }, 600);
						} else {
							save.disabled = false;
							cancel.disabled = false;
							note.textContent = (res && res.data && res.data.msg) || 'خطا در ویرایش.';
						}
					});
				});
			});
		});
	}

	function init() { document.querySelectorAll('.szp-eval2').forEach(initEval); }
	if (document.readyState !== 'loading') init();
	else document.addEventListener('DOMContentLoaded', init);
})();
