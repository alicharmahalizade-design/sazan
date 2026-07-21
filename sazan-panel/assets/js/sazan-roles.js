(function () {
	'use strict';

	var CFG = window.SZP_ROLES || {};
	var ROLES = CFG.roles || {};
	var S = { users: (CFG.users || []).slice() };
	var app, toastEl;

	var PLACEHOLDER = 'data:image/svg+xml;utf8,' + encodeURIComponent(
		'<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"><rect width="48" height="48" rx="24" fill="%23d7dbe6"/><circle cx="24" cy="19" r="8" fill="%23fff"/><path d="M8 44c2-9 9-13 16-13s14 4 16 13z" fill="%23fff"/></svg>'
	);

	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
	function isMgr(r) { return r === 'szp_org_manager' || r === 'szp_manager_sales'; }
	function isSale(r) { return r === 'szp_sales' || r === 'szp_manager_sales'; }
	function isCoach(u) { return u.role === 'szp_coaching' || u.coaching; }
	function avatarOf(u) { return u.avatar || PLACEHOLDER; }
	function byId(id) { for (var i = 0; i < S.users.length; i++) { if (S.users[i].id === id) return S.users[i]; } return null; }
	function managers() { return S.users.filter(function (u) { return isMgr(u.role); }); }

	function roleClass(r) { return r ? ('role-' + r) : 'role-none'; }

	/* ---------- render ---------- */

	function render() {
		var y = window.scrollY;
		app.innerHTML = tplHeader() + '<div class="szp-rgrid">' + tplEditor() + tplOrg() + '</div>';
		window.scrollTo(0, y);
	}

	function tplHeader() {
		var nc = 0, nm = 0, ns = 0, nn = 0;
		S.users.forEach(function (u) {
			if (isCoach(u)) nc++;
			else if (isMgr(u.role)) nm++;
			else if (isSale(u.role)) ns++;
			else nn++;
		});
		return '' +
			'<div class="szp-rh">' +
				'<h1>مدیریت نقش‌ها و تیم‌ها</h1>' +
				'<p>از همین‌جا کوچینگ، مدیران سازمان و تیم فروش را مشخص کن و هر عضو تیم را زیر مدیرش بگذار. تغییرات <b>خودکار ذخیره</b> می‌شوند.</p>' +
				'<div class="szp-rh-stats">' +
					stat('#7c3aed', nc, 'کوچینگ') +
					stat('#2563eb', nm, 'مدیر سازمان') +
					stat('#16a34a', ns, 'تیم فروش') +
					stat('#94a3b8', nn, 'بدون نقش') +
				'</div>' +
			'</div>';
	}
	function stat(color, n, label) {
		return '<div class="szp-rstat"><i style="background:' + color + '"></i><b>' + faNum(n) + '</b><span>' + esc(label) + '</span></div>';
	}

	function tplEditor() {
		var rows = S.users.map(tplRow).join('');
		if (!rows) rows = '<div class="szp-rempty">هنوز کسی اضافه نشده. از کادر بالا کاربر را جستجو و اضافه کن.</div>';
		return '' +
			'<div class="szp-rpanel">' +
				'<div class="szp-rpanel-head"><h2>افراد</h2><span class="szp-rhint">نقش و مدیرِ هر نفر را تعیین کن</span></div>' +
				'<div class="szp-rpanel-body">' +
					'<div class="szp-radd">' +
						'<input type="text" id="szp-radd-input" placeholder="🔎 جستجوی کاربر برای افزودن (نام، ایمیل یا موبایل)…" autocomplete="off">' +
						'<div class="szp-radd-res" id="szp-radd-res"></div>' +
					'</div>' +
					'<div id="szp-rlist">' + rows + '</div>' +
				'</div>' +
			'</div>';
	}

	function tplRow(u) {
		var roleSel = '<select data-act="role" data-uid="' + u.id + '">' +
			'<option value=""' + (u.role ? '' : ' selected') + '>— بدون نقش —</option>' +
			Object.keys(ROLES).map(function (slug) {
				return '<option value="' + slug + '"' + (u.role === slug ? ' selected' : '') + '>' + esc(ROLES[slug]) + '</option>';
			}).join('') + '</select>';

		var mgrs = managers().filter(function (m) { return m.id !== u.id; });
		var canMgr = isSale(u.role);
		var mgrSel = '<select data-act="manager" data-uid="' + u.id + '"' + (canMgr ? '' : ' disabled') + ' title="مدیر سازمان مربوطه">' +
			'<option value="0">' + (canMgr ? '— مدیرش را انتخاب کن —' : 'مدیر (فقط برای تیم فروش)') + '</option>' +
			mgrs.map(function (m) {
				return '<option value="' + m.id + '"' + (u.manager === m.id ? ' selected' : '') + '>' + esc(m.name) + '</option>';
			}).join('') + '</select>';

		return '' +
			'<div class="szp-ru" data-uid="' + u.id + '">' +
				'<span class="szp-ru-role ' + roleClass(u.role) + '"></span>' +
				'<img class="szp-ru-av" src="' + esc(avatarOf(u)) + '" alt="">' +
				'<div class="szp-ru-id">' +
					'<div class="szp-ru-name">' + esc(u.name) + '</div>' +
					(u.mobile ? '<div class="szp-ru-mobile">' + esc(u.mobile) + '</div>' : '') +
				'</div>' +
				'<div class="szp-ru-ctrls">' + roleSel + mgrSel +
					'<span class="szp-ru-save" data-save="' + u.id + '">ذخیره شد ✓</span>' +
				'</div>' +
			'</div>';
	}

	function tplOrg() {
		var html = '<div class="szp-rpanel"><div class="szp-rpanel-head"><h2>نمای ساختار تیم</h2><span class="szp-rhint">زنده</span></div><div class="szp-rpanel-body"><div class="szp-rorg">';

		// کوچینگ
		var coaches = S.users.filter(isCoach);
		html += group('coach', '🎓 کوچینگ — همه را می‌بینند', coaches.length, chips(coaches) || '<div class="szp-rorg-empty">هنوز کوچی مشخص نشده.</div>');

		// هر مدیر سازمان + تیمش
		managers().forEach(function (m) {
			var team = S.users.filter(function (u) { return u.manager === m.id && isSale(u.role); });
			var head = '<img src="' + esc(avatarOf(m)) + '" alt=""><span class="t">👤 ' + esc(m.name) + ' <small style="color:var(--text2)">(' + esc(ROLES[m.role] || '') + ')</small></span><span class="c">' + faNum(team.length) + ' نفر</span>';
			var body = team.length ? '<div class="szp-rorg-members">' + chips(team) + '</div>' : '<div class="szp-rorg-empty">هنوز عضوی به این تیم اضافه نشده.</div>';
			html += '<div class="szp-rorg-group"><div class="szp-rorg-head">' + head + '</div>' + body + '</div>';
		});

		// تیم فروش بدون مدیر
		var orphans = S.users.filter(function (u) { return isSale(u.role) && !u.manager; });
		if (orphans.length) {
			html += group('none', '⚠️ تیم فروش بدون مدیر', orphans.length, '<div class="szp-rorg-members">' + chips(orphans) + '</div>');
		}

		html += '</div>' +
			'<div class="szp-rlegend">' +
				'<span><i style="background:#7c3aed"></i>کوچینگ</span>' +
				'<span><i style="background:#2563eb"></i>مدیر سازمان</span>' +
				'<span><i style="background:#0891b2"></i>مدیر/فروش</span>' +
				'<span><i style="background:#16a34a"></i>تیم فروش</span>' +
			'</div></div></div>';
		return html;
	}

	function group(kind, title, count, inner) {
		return '<div class="szp-rorg-group"><div class="szp-rorg-head ' + kind + '"><span class="t">' + esc(title) + '</span><span class="c">' + faNum(count) + ' نفر</span></div>' + inner + '</div>';
	}
	function chips(list) {
		if (!list.length) return '';
		return '<div class="szp-rorg-members">' + list.map(function (u) {
			return '<span class="szp-rchip"><img src="' + esc(avatarOf(u)) + '" alt="">' + esc(u.name) + '</span>';
		}).join('') + '</div>';
	}

	/* ---------- helpers ---------- */

	var FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
	function faNum(n) { return String(n).replace(/[0-9]/g, function (d) { return FA[d]; }); }

	function toast(msg, err) {
		if (!toastEl) { toastEl = document.createElement('div'); toastEl.className = 'szp-rtoast'; document.body.appendChild(toastEl); }
		toastEl.textContent = msg;
		toastEl.className = 'szp-rtoast show' + (err ? ' err' : '');
		clearTimeout(toastEl._t);
		toastEl._t = setTimeout(function () { toastEl.className = 'szp-rtoast' + (err ? ' err' : ''); }, 2200);
	}

	function saveUser(u, flashEl) {
		var body = new URLSearchParams();
		body.set('action', 'szp_role_save');
		body.set('nonce', CFG.nonce || '');
		body.set('user', u.id);
		body.set('role', u.role || '');
		body.set('manager', u.manager || 0);
		fetch(CFG.ajax, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (res && res.success) {
					if (res.data && res.data.user) {
						var i = S.users.map(function (x) { return x.id; }).indexOf(u.id);
						if (i > -1) S.users[i] = res.data.user;
					}
					toast('ذخیره شد ✓');
					render();
				} else {
					toast((res && res.data && res.data.msg) || 'خطا در ذخیره', true);
				}
			})
			.catch(function () { toast('خطای ارتباط با سرور', true); });
	}

	/* ---------- events ---------- */

	function onChange(e) {
		var el = e.target;
		if (el.tagName !== 'SELECT') return;
		var act = el.getAttribute('data-act');
		if (!act) return;
		var uid = parseInt(el.getAttribute('data-uid'), 10);
		var u = byId(uid);
		if (!u) return;
		if (act === 'role') {
			u.role = el.value;
			if (!isSale(u.role)) u.manager = 0;
		} else if (act === 'manager') {
			u.manager = parseInt(el.value, 10) || 0;
		}
		saveUser(u);
	}

	/* ---------- search / add ---------- */

	var searchTimer, lastQ = '';
	function onInput(e) {
		if (e.target.id !== 'szp-radd-input') return;
		var res = document.getElementById('szp-radd-res');
		if (!res) return;
		var q = e.target.value.trim();
		clearTimeout(searchTimer);
		if (q.length < 2) { res.className = 'szp-radd-res'; res.innerHTML = ''; return; }
		searchTimer = setTimeout(function () { doSearch(q, res); }, 250);
	}

	function doSearch(q, res) {
		lastQ = q;
		var url = CFG.ajax + '?action=szp_user_search&nonce=' + encodeURIComponent(CFG.nonce) + '&q=' + encodeURIComponent(q);
		fetch(url, { credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (r) {
				if (q !== lastQ) return;
				var list = (r && r.success && r.data) || [];
				if (!list.length) { res.innerHTML = '<div class="szp-radd-empty">کاربری یافت نشد.</div>'; res.className = 'szp-radd-res show'; return; }
				res.innerHTML = list.map(function (it) {
					var already = byId(it.id) ? ' style="opacity:.5"' : '';
					return '<div class="szp-radd-item" data-add="' + it.id + '" data-name="' + esc(it.name) + '" data-mobile="' + esc(it.mobile || '') + '" data-email="' + esc(it.email || '') + '"' + already + '>' +
						'<img src="' + PLACEHOLDER + '" alt="">' +
						'<div><div class="n">' + esc(it.name) + '</div><div class="m">' + esc(it.mobile || it.email || '') + '</div></div></div>';
				}).join('');
				res.className = 'szp-radd-res show';
			})
			.catch(function () { res.innerHTML = '<div class="szp-radd-empty">خطا در جستجو.</div>'; res.className = 'szp-radd-res show'; });
	}

	function onClick(e) {
		var item = e.target.closest('.szp-radd-item');
		if (!item) return;
		var id = parseInt(item.getAttribute('data-add'), 10);
		if (byId(id)) { toast('این کاربر از قبل در لیست است.'); return; }
		S.users.unshift({
			id: id,
			name: item.getAttribute('data-name'),
			mobile: item.getAttribute('data-mobile'),
			email: item.getAttribute('data-email'),
			avatar: '',
			role: '',
			coaching: 0,
			manager: 0
		});
		var input = document.getElementById('szp-radd-input');
		if (input) input.value = '';
		render();
		toast('اضافه شد — حالا نقشش را انتخاب کن');
	}

	/* ---------- boot ---------- */

	function init() {
		app = document.getElementById('szp-roles-app');
		if (!app) return;
		render();
		// همه‌ی رویدادها با واگذاری روی ریشه‌ی اپ (که بین رندرها ثابت است).
		app.addEventListener('change', onChange);
		app.addEventListener('click', onClick);
		app.addEventListener('input', onInput);
		document.addEventListener('click', function (e) {
			if (!e.target.closest('.szp-radd')) {
				var res = document.getElementById('szp-radd-res');
				if (res) res.className = 'szp-radd-res';
			}
		});
	}

	if (document.readyState !== 'loading') init();
	else document.addEventListener('DOMContentLoaded', init);
})();
