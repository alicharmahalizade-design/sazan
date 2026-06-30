(function () {
	var F = window.SZP_FRONT || {};
	function fa(n) { return String(n).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
	function pad(n) { return n < 10 ? '0' + n : '' + n; }

	function ajax(action, data) {
		if (!(data instanceof FormData)) {
			var fd = new FormData();
			for (var k in data) { if (data.hasOwnProperty(k)) fd.append(k, data[k]); }
			data = fd;
		}
		data.append('action', action);
		data.append('nonce', F.nonce || '');
		return fetch(F.ajax || '', { method: 'POST', credentials: 'same-origin', body: data })
			.then(function (r) { return r.json(); })
			.catch(function () { return { success: false, data: { msg: 'خطای ارتباط با سرور.' } }; });
	}

	function Room(root) {
		this.root = root;
		this.course = root.getAttribute('data-course');
		this.me = parseInt(root.getAttribute('data-me'), 10);
		this.app = root.querySelector('.szp-chat-app');
		this.lastKey = '';
		this.lastFree = 0;
		this.lastGrp = 0;
		this.bind();
		this.poll();
		var self = this;
		setInterval(function () { self.poll(); }, 4000);
		setInterval(function () { self.ticks(); }, 1000);
	}

	Room.prototype.poll = function () {
		var self = this;
		ajax('szp_chat_state', { course_id: this.course }).then(function (res) {
			if (!res.success) { self.app.innerHTML = '<div class="szp-empty">' + esc(res.data && res.data.msg) + '</div>'; return; }
			self.apply(res.data);
		});
	};

	Room.prototype.act = function (action, data) {
		var self = this;
		return ajax(action, data).then(function (res) {
			if (!res.success) { self.flash(res.data && res.data.msg); return res; }
			if (res.data && res.data.phase) { self.lastKey = ''; self.apply(res.data); }
			return res;
		});
	};

	Room.prototype.flash = function (msg) {
		var b = this.root.querySelector('.szp-chat-flash');
		if (!b) return;
		b.textContent = msg || 'خطا';
		b.classList.add('on');
		var self = this;
		clearTimeout(this._ft);
		this._ft = setTimeout(function () { b.classList.remove('on'); }, 3500);
	};

	Room.prototype.structKey = function (s) {
		var g = s.group || {};
		return JSON.stringify({
			p: s.phase, mg: s.my_group_id || g.id || 0, li: g.leader_id,
			nm: g.name, lg: g.logo, ms: g.mission, sl: g.slogan,
			mem: (g.members || []).map(function (m) { return m.id + ':' + m.role; }),
			grp: (s.groups || []).map(function (x) { return x.id + ':' + x.count + ':' + x.is_mine; }),
			tl: g.tally, mv: g.my_vote, dl: s.deadline, vdl: g.vote_deadline
		});
	};

	Room.prototype.apply = function (s) {
		this.state = s;
		var key = this.structKey(s);
		if (key !== this.lastKey) { this.lastKey = key; this.render(s); }
		// incremental messages for chat phases
		if (s.phase === 'free') this.appendMsgs(s.free_msgs, 'free');
		if (s.phase === 'group_active') this.appendMsgs(s.grp_msgs, 'grp');
	};

	/* ---------------- render ---------------- */

	Room.prototype.render = function (s) {
		var html = '<div class="szp-chat-flash"></div>';
		switch (s.phase) {
			case 'pending':      html += this.notice('اتاق گفتگوی این دوره هنوز فعال نشده است.'); break;
			case 'free':         html += this.viewFree(s); break;
			case 'free_closed':  html += this.notice('گفتگوی آزاد بسته شد. منتظر تعیین گروه‌ها توسط مدیریت بمانید.'); break;
			case 'grouping':     html += this.viewGrouping(s); break;
			case 'no_group':     html += this.notice('شما در هیچ گروهی عضو نشدید. با مدیریت تماس بگیرید.'); break;
			case 'group_voting': html += this.viewVoting(s); break;
			case 'group_active': html += this.viewGroup(s); break;
			default:             html += this.notice('وضعیت نامشخص.');
		}
		this.app.innerHTML = html;
		this.lastFree = 0; this.lastGrp = 0;
		if (s.phase === 'free') this.appendMsgs(s.free_msgs, 'free');
		if (s.phase === 'group_active') this.appendMsgs(s.grp_msgs, 'grp');
	};

	Room.prototype.notice = function (t) {
		return '<h2 class="szp-h">' + esc(this.state.course_title) + '</h2><div class="szp-empty">' + esc(t) + '</div>';
	};

	Room.prototype.cd = function (ms, label) {
		if (!ms) return '';
		return '<div class="szp-cd2" data-deadline="' + ms + '"><span class="szp-cd2-lbl">' + esc(label) + '</span> <b class="szp-cd2-val">—</b></div>';
	};

	Room.prototype.composer = function (scope, withFile) {
		return '<form class="szp-chat-form" data-scope="' + scope + '">' +
			'<textarea class="szp-chat-input" rows="1" placeholder="پیام خود را بنویسید…"></textarea>' +
			(withFile ? '<label class="szp-chat-attach" title="پیوست فایل">📎<input type="file" class="szp-chat-file" hidden></label>' : '') +
			'<button type="submit" class="szp-chat-send">ارسال</button>' +
			'<span class="szp-chat-fname"></span></form>';
	};

	Room.prototype.msgList = function (id) {
		return '<div class="szp-msgs" data-stream="' + id + '"></div>';
	};

	Room.prototype.chatbox = function (stream, scope, withFile, title) {
		return '<div class="szp-chatbox">' +
			'<div class="szp-chat-bar"><span class="szp-chat-bar-t">' + esc(title) + '</span>' +
			'<button type="button" class="szp-chat-fs" title="تمام‌صفحه"><span class="szp-fs-i">⛶</span><span class="szp-fs-t">تمام‌صفحه</span></button></div>' +
			this.msgList(stream) + this.composer(scope, withFile) + '</div>';
	};

	Room.prototype.viewFree = function (s) {
		return '<h2 class="szp-h">گفتگوی آزاد · ' + esc(s.course_title) + '</h2>' +
			'<p class="szp-text" style="margin-bottom:12px">این گفتگو برای هماهنگی و تعیین گروه‌بندی است. پس از پایان مهلت، بسته می‌شود.</p>' +
			this.cd(s.deadline, 'پایان گفتگوی آزاد:') +
			this.chatbox('free', 'free', false, 'گفتگوی آزاد');
	};

	Room.prototype.viewGrouping = function (s) {
		var self = this, html = '<h2 class="szp-h">انتخاب گروه · ' + esc(s.course_title) + '</h2>' +
			'<p class="szp-text" style="margin-bottom:16px">گروه خود را انتخاب کنید. می‌توانید اعضا و تعداد هر گروه را ببینید و تا پیش از نهایی‌سازی، گروه را تغییر دهید.</p>' +
			'<div class="szp-grp-grid">';
		(s.groups || []).forEach(function (g) {
			html += '<div class="szp-grp-card' + (g.is_mine ? ' is-mine' : '') + '">' +
				'<div class="szp-grp-top"><span class="szp-grp-name">' + esc(g.name) + '</span><span class="szp-grp-count">' + fa(g.count) + ' نفر</span></div>' +
				'<div class="szp-grp-ava">' + self.avatars(g.members) + '</div>' +
				(g.is_mine
					? '<button class="szp-btn-ghost" data-leave="1">ترک گروه</button>'
					: '<button class="szp-btn" data-join="' + g.id + '">عضویت در این گروه</button>') +
				'</div>';
		});
		return html + '</div>';
	};

	Room.prototype.avatars = function (members) {
		var h = '', m = members || [];
		m.slice(0, 8).forEach(function (x) {
			h += '<img class="szp-ava" src="' + esc(x.avatar) + '" alt="" title="' + esc(x.name) + '">';
		});
		if (m.length > 8) h += '<span class="szp-ava szp-ava-more">+' + fa(m.length - 8) + '</span>';
		return h;
	};

	Room.prototype.viewVoting = function (s) {
		var g = s.group, self = this;
		var html = '<h2 class="szp-h">' + esc(g.name) + '</h2>' +
			'<p class="szp-text" style="margin-bottom:12px">پیش از باز شدن چت، سرگروه با رای‌گیری مشخص می‌شود. به یکی از اعضا (به جز خودتان) رای دهید.</p>' +
			this.cd(g.vote_deadline, 'پایان رای‌گیری:') +
			'<div class="szp-vote">';
		(g.members || []).forEach(function (m) {
			if (m.id === self.me) return;
			var votes = (g.tally && g.tally[m.id]) ? g.tally[m.id] : 0;
			var on = (g.my_vote === m.id);
			html += '<label class="szp-vote-row' + (on ? ' on' : '') + '">' +
				'<input type="radio" name="szpvote" value="' + m.id + '"' + (on ? ' checked' : '') + '>' +
				'<img class="szp-ava" src="' + esc(m.avatar) + '" alt="">' +
				'<span class="szp-vote-name">' + esc(m.name) + '</span>' +
				'<span class="szp-vote-count">' + fa(votes) + ' رای</span></label>';
		});
		html += '</div><p class="szp-text szp-mini">سرگروه به‌صورت خودکار بر اساس بیشترین رای پس از پایان مهلت تعیین می‌شود.</p>';
		return html;
	};

	Room.prototype.viewGroup = function (s) {
		var g = s.group, self = this;
		var roleEdit = g.is_leader;
		var head = '<div class="szp-grp-profile">' +
			'<div class="szp-grp-logo">' + (g.logo ? '<img src="' + esc(g.logo) + '" alt="">' : '🛡️') + '</div>' +
			'<div class="szp-grp-info"><h2 class="szp-h" style="margin:0">' + esc(g.name) + '</h2>' +
			(g.slogan ? '<div class="szp-grp-slogan">«' + esc(g.slogan) + '»</div>' : '') +
			'<div class="szp-grp-leader">سرگروه: ' + esc(g.leader || '—') + '</div></div>' +
			'<button class="szp-btn-ghost" data-editprofile="1">ویرایش پروفایل گروه</button></div>';

		if (g.mission) head += '<div class="szp-sec"><div class="szp-sec-h">ماموریت گروه</div><div class="szp-rich">' + esc(g.mission) + '</div></div>';

		var mem = '<div class="szp-sec"><div class="szp-sec-h">اعضای گروه</div><div class="szp-mem-list">';
		(g.members || []).forEach(function (m) {
			var tag = (m.id === g.leader_id) ? '<span class="szp-mem-leader">سرگروه</span>' : '';
			var role = m.role ? '<span class="szp-mem-role">' + esc(m.role) + '</span>' : '';
			var editBtn = (roleEdit && m.id !== g.leader_id) ? '<button class="szp-mem-rolebtn" data-role-member="' + m.id + '" data-role-val="' + esc(m.role) + '">نقش ✎</button>' : '';
			mem += '<div class="szp-mem"><img class="szp-ava" src="' + esc(m.avatar) + '"><span class="szp-mem-name">' + esc(m.name) + '</span>' + tag + role + editBtn + '</div>';
		});
		mem += '</div></div>';

		var chat = this.chatbox('grp', 'group', true, 'گفتگوی گروه');
		return head + mem + chat;
	};

	/* ---------------- messages ---------------- */

	Room.prototype.appendMsgs = function (list, stream) {
		var box = this.app.querySelector('.szp-msgs[data-stream="' + stream + '"]');
		if (!box || !list) return;
		var last = stream === 'free' ? this.lastFree : this.lastGrp;
		var atBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 60;
		var added = false, self = this;
		list.forEach(function (m) {
			if (m.id <= last) return;
			last = m.id; added = true;
			box.insertAdjacentHTML('beforeend', self.msgHtml(m));
		});
		if (stream === 'free') this.lastFree = last; else this.lastGrp = last;
		if (added && atBottom) box.scrollTop = box.scrollHeight;
	};

	Room.prototype.msgHtml = function (m) {
		var body = '';
		if (m.content) body += '<div class="szp-msg-text">' + m.content + '</div>';
		if (m.file_url) {
			body += m.is_image
				? '<a class="szp-msg-img" href="' + esc(m.file_url) + '" target="_blank"><img src="' + esc(m.file_url) + '" alt=""></a>'
				: '<a class="szp-msg-file" href="' + esc(m.file_url) + '" target="_blank">📎 ' + esc(m.file_name || 'دانلود فایل') + '</a>';
		}
		return '<div class="szp-msg' + (m.mine ? ' mine' : '') + '">' +
			'<img class="szp-ava" src="' + esc(m.avatar) + '" alt="">' +
			'<div class="szp-msg-b"><div class="szp-msg-head"><b>' + esc(m.name) + '</b><i>' + esc(m.time) + '</i></div>' + body + '</div></div>';
	};

	/* ---------------- countdown ticker ---------------- */

	Room.prototype.ticks = function () {
		var els = this.app.querySelectorAll('.szp-cd2[data-deadline]');
		els.forEach(function (el) {
			var dl = parseInt(el.getAttribute('data-deadline'), 10);
			var v = el.querySelector('.szp-cd2-val');
			var diff = dl - Date.now();
			if (isNaN(dl)) return;
			if (diff <= 0) { v.textContent = 'پایان یافت'; el.classList.add('done'); return; }
			var s = Math.floor(diff / 1000), d = Math.floor(s / 86400); s -= d * 86400;
			var h = Math.floor(s / 3600); s -= h * 3600; var m = Math.floor(s / 60); s -= m * 60;
			var t = (d ? fa(d) + ' روز ' : '') + fa(pad(h)) + ':' + fa(pad(m)) + ':' + fa(pad(s));
			v.textContent = t;
		});
	};

	/* ---------------- events ---------------- */

	Room.prototype.bind = function () {
		var self = this, root = this.root;

		root.addEventListener('submit', function (e) {
			var f = e.target;
			if (f.classList.contains('szp-chat-form')) {
				e.preventDefault();
				var ta = f.querySelector('.szp-chat-input');
				var fileIn = f.querySelector('.szp-chat-file');
				var fd = new FormData();
				fd.append('course_id', self.course);
				fd.append('scope', f.getAttribute('data-scope'));
				fd.append('content', ta ? ta.value : '');
				if (fileIn && fileIn.files[0]) fd.append('file', fileIn.files[0]);
				var btn = f.querySelector('.szp-chat-send'); if (btn) btn.disabled = true;
				ajax('szp_chat_send', fd).then(function (res) {
					if (btn) btn.disabled = false;
					if (!res.success) { self.flash(res.data && res.data.msg); return; }
					if (ta) { ta.value = ''; ta.style.height = 'auto'; }
					if (fileIn) { fileIn.value = ''; var fn = f.querySelector('.szp-chat-fname'); if (fn) fn.textContent = ''; }
					self.poll();
				});
			}
		});

		root.addEventListener('click', function (e) {
			var t = e.target;
			var join = t.closest && t.closest('[data-join]');
			if (join) { self.act('szp_chat_join', { course_id: self.course, group_id: join.getAttribute('data-join') }); return; }
			if (t.closest && t.closest('[data-leave]')) { self.act('szp_chat_leave', { course_id: self.course }); return; }
			if (t.closest && t.closest('[data-editprofile]')) { self.editProfile(); return; }
			var rb = t.closest && t.closest('[data-role-member]');
			if (rb) { self.editRole(rb.getAttribute('data-role-member'), rb.getAttribute('data-role-val')); return; }
			var fs = t.closest && t.closest('.szp-chat-fs');
			if (fs) { self.fsToggle(fs.closest('.szp-chatbox')); return; }
		});

		root.addEventListener('change', function (e) {
			var r = e.target;
			if (r.type === 'radio' && r.name === 'szpvote') {
				self.act('szp_chat_vote', { course_id: self.course, candidate_id: r.value });
			}
			if (r.classList && r.classList.contains('szp-chat-file')) {
				var fn = r.closest('form').querySelector('.szp-chat-fname');
				if (fn) fn.textContent = r.files[0] ? r.files[0].name : '';
			}
		});

		root.addEventListener('input', function (e) {
			if (e.target.classList.contains('szp-chat-input')) {
				e.target.style.height = 'auto';
				e.target.style.height = Math.min(e.target.scrollHeight, 140) + 'px';
			}
		});

		var onFsChange = function () {
			var fe = document.fullscreenElement || document.webkitFullscreenElement;
			self.root.querySelectorAll('.szp-chatbox').forEach(function (box) {
				self.fsSync(box, fe === box);
			});
		};
		document.addEventListener('fullscreenchange', onFsChange);
		document.addEventListener('webkitfullscreenchange', onFsChange);
	};

	Room.prototype.editProfile = function () {
		var g = (this.state && this.state.group) || {};
		var name = prompt('نام گروه:', g.name || ''); if (name === null) return;
		var slogan = prompt('شعار گروه:', g.slogan || ''); if (slogan === null) return;
		var mission = prompt('ماموریت گروه:', g.mission || ''); if (mission === null) return;
		this.act('szp_chat_profile', { course_id: this.course, name: name, slogan: slogan, mission: mission });
		this.flash('برای تغییر لوگو از بخش ویرایش پیشرفته استفاده کنید.');
	};

	Room.prototype.editRole = function (member, current) {
		var role = prompt('نقش این عضو:', current || ''); if (role === null) return;
		this.act('szp_chat_role', { course_id: this.course, member_id: member, role: role });
	};

	/* ---------------- fullscreen ---------------- */

	Room.prototype.fsToggle = function (box) {
		if (!box) return;
		// already in native fullscreen → exit
		if (document.fullscreenElement === box || document.webkitFullscreenElement === box) {
			(document.exitFullscreen || document.webkitExitFullscreen || function () {}).call(document);
			return;
		}
		// fallback overlay already on → turn off
		if (box.classList.contains('szp-fs-fallback')) { this.fsFallback(box, false); return; }
		var req = box.requestFullscreen || box.webkitRequestFullscreen;
		var self = this;
		if (req) {
			var p = req.call(box);
			if (p && p.catch) p.catch(function () { self.fsFallback(box, true); });
		} else {
			this.fsFallback(box, true);
		}
	};

	Room.prototype.fsFallback = function (box, on) {
		box.classList.toggle('szp-fs-fallback', on);
		document.body.classList.toggle('szp-fs-lock', on);
		this.fsSync(box, on);
	};

	Room.prototype.fsSync = function (box, on) {
		var btnT = box.querySelector('.szp-fs-t'), btnI = box.querySelector('.szp-fs-i');
		if (btnT) btnT.textContent = on ? 'خروج از تمام‌صفحه' : 'تمام‌صفحه';
		if (btnI) btnI.textContent = on ? '⤢' : '⛶';
		var msgs = box.querySelector('.szp-msgs');
		if (msgs) msgs.scrollTop = msgs.scrollHeight;
	};

	function boot() {
		document.querySelectorAll('.szp-chat-root').forEach(function (r) {
			if (!r._szpChat) r._szpChat = new Room(r);
		});
	}
	if (document.readyState !== 'loading') boot();
	else document.addEventListener('DOMContentLoaded', boot);
})();
