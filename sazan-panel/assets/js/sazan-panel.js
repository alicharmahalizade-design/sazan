(function () {
	function toFa(n) {
		return String(n).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
	}
	function pad(n) { return n < 10 ? '0' + n : '' + n; }

	function tick(el) {
		var dl = parseInt(el.getAttribute('data-deadline'), 10);
		var diff = dl - Date.now();
		var d = el.querySelector('[data-d]'), h = el.querySelector('[data-h]'),
			m = el.querySelector('[data-m]'), s = el.querySelector('[data-s]');
		if (isNaN(dl)) return;
		if (diff <= 0) {
			el.classList.add('szp-cd-done');
			if (d) d.textContent = '۰';
			if (h) h.textContent = '۰۰';
			if (m) m.textContent = '۰۰';
			if (s) s.textContent = '۰۰';
			var lbl = el.querySelector('.szp-cd-label');
			if (lbl) lbl.textContent = 'جلسه آغاز شد';
			return;
		}
		var sec = Math.floor(diff / 1000);
		var dd = Math.floor(sec / 86400); sec -= dd * 86400;
		var hh = Math.floor(sec / 3600); sec -= hh * 3600;
		var mm = Math.floor(sec / 60); var ss = sec - mm * 60;
		if (d) d.textContent = toFa(dd);
		if (h) h.textContent = toFa(pad(hh));
		if (m) m.textContent = toFa(pad(mm));
		if (s) s.textContent = toFa(pad(ss));
	}

	function init() {
		var els = document.querySelectorAll('.szp-countdown');
		if (els.length) {
			els.forEach(tick);
			setInterval(function () { els.forEach(tick); }, 1000);
		}
		initCarousels();
		initAudioPlayers();
		initSkin();
		initPosters();
		document.querySelectorAll('.szp-stars input:checked').forEach(function (r) {
			var lab = r.closest('.szp-star');
			if (lab) lab.classList.add('on');
		});
	}

	function applySkin(skin) {
		if (skin !== 'neon' && skin !== 'glass') skin = 'glass';
		document.querySelectorAll('.szp').forEach(function (el) {
			el.classList.remove('szp-skin-glass', 'szp-skin-neon');
			el.classList.add('szp-skin-' + skin);
		});
		document.querySelectorAll('.szp-skin-btn').forEach(function (b) {
			b.classList.toggle('on', b.getAttribute('data-skin') === skin);
		});
	}
	function initSkin() {
		document.querySelectorAll('.szp').forEach(function (el) {
			el.classList.remove('szp-skin-glass');
			el.classList.add('szp-skin-neon');
		});
	}

	function initPosters() {
		document.querySelectorAll('.szp-sched-poster').forEach(function (wrap) {
			if (wrap.dataset.pInit) return;
			wrap.dataset.pInit = '1';
			var cards = [].slice.call(wrap.querySelectorAll('.szp-poster'));
			var btn = wrap.querySelector('.szp-more-btn');
			if (!cards.length) return;
			var show = 8;
			function apply() {
				cards.forEach(function (c, i) {
					c.classList.remove('is-teaser', 'is-hidden');
					if (i < show) { /* visible */ }
					else if (i < show + 4) c.classList.add('is-teaser');
					else c.classList.add('is-hidden');
				});
				if (btn) btn.style.display = (show < cards.length) ? '' : 'none';
			}
			if (btn) btn.addEventListener('click', function () { show += 4; apply(); });
			apply();
		});
	}

	function szRoundBar(c, x, y, w, h, r) {
		r = Math.min(r, w / 2, h);
		c.beginPath();
		c.moveTo(x, y + r);
		c.arcTo(x, y, x + r, y, r);
		c.arcTo(x + w, y, x + w, y + r, r);
		c.lineTo(x + w, y + h);
		c.lineTo(x, y + h);
		c.closePath();
		c.fill();
	}

	function initAudioPlayers() {
		document.querySelectorAll('.szp-audio').forEach(function (wrap) {
			if (wrap.dataset.apInit) return;
			wrap.dataset.apInit = '1';
			var audio = wrap.querySelector('.szp-audio-el');
			var canvas = wrap.querySelector('.szp-audio-viz');
			var play = wrap.querySelector('.szp-ap-play');
			var bar = wrap.querySelector('.szp-ap-bar');
			var fill = wrap.querySelector('.szp-ap-fill');
			var cur = wrap.querySelector('.szp-ap-cur');
			var dur = wrap.querySelector('.szp-ap-dur');
			if (!audio) return;
			// Cross-origin media (download host) tainted by Web Audio = silent output.
			// Detect it and skip the analyser so playback keeps its sound.
			var crossOrigin = false;
			try {
				var _au = new URL(audio.currentSrc || audio.src, location.href);
				crossOrigin = _au.origin !== location.origin;
			} catch (e) { crossOrigin = true; }
			if (crossOrigin && canvas) canvas.style.display = 'none';
			var speedBtn = wrap.querySelector('.szp-ap-speed');
			if (speedBtn) {
				var rates = [1, 1.5, 2, 4], ri = 0;
				var fa = function (n) { return ('' + n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
				speedBtn.addEventListener('click', function () {
					ri = (ri + 1) % rates.length;
					audio.playbackRate = rates[ri];
					speedBtn.textContent = fa(rates[ri]) + 'x';
					speedBtn.dataset.speed = rates[ri];
				});
			}
			var cctx = canvas ? canvas.getContext('2d') : null;
			var actx, analyser, raf;

			function fa(s) { return String(s).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
			function fmt(t) { if (!isFinite(t)) t = 0; var m = Math.floor(t / 60), s = Math.floor(t % 60); return fa(m + ':' + (s < 10 ? '0' : '') + s); }

			function setupAC() {
				if (actx) return true;
				if (crossOrigin) return false; // never route cross-origin audio (would mute it)
				var AC = window.AudioContext || window.webkitAudioContext;
				if (!AC) return false;
				try {
					actx = new AC();
					analyser = actx.createAnalyser();
					analyser.fftSize = 256;
					var src = actx.createMediaElementSource(audio);
					src.connect(analyser);
					analyser.connect(actx.destination);
				} catch (e) { return false; }
				return true;
			}
			function draw() {
				raf = requestAnimationFrame(draw);
				if (!analyser || !cctx) return;
				var n = analyser.frequencyBinCount, data = new Uint8Array(n);
				analyser.getByteFrequencyData(data);
				var dpr = window.devicePixelRatio || 1;
				var w = canvas.width = canvas.clientWidth * dpr;
				var h = canvas.height = canvas.clientHeight * dpr;
				cctx.clearRect(0, 0, w, h);
				var bars = 56, step = Math.max(1, Math.floor(n / bars)), bw = w / bars;
				for (var i = 0; i < bars; i++) {
					var v = data[i * step] / 255;
					var bh = Math.max(2 * dpr, v * h);
					var g = cctx.createLinearGradient(0, h, 0, h - bh);
					g.addColorStop(0, 'rgba(0,182,241,0.95)');
					g.addColorStop(1, 'rgba(247,148,29,0.95)');
					cctx.fillStyle = g;
					szRoundBar(cctx, i * bw + bw * 0.22, h - bh, bw * 0.56, bh, bw * 0.28);
				}
			}
			if (play) {
				play.addEventListener('click', function () {
					if (audio.paused) {
						setupAC();
						if (actx && actx.state === 'suspended') actx.resume();
						audio.play();
					} else {
						audio.pause();
					}
				});
			}
			audio.addEventListener('play', function () { wrap.classList.add('szp-playing'); if (!raf) draw(); });
			audio.addEventListener('pause', function () { wrap.classList.remove('szp-playing'); });
			audio.addEventListener('ended', function () { wrap.classList.remove('szp-playing'); });
			audio.addEventListener('loadedmetadata', function () { if (dur) dur.textContent = fmt(audio.duration); });
			audio.addEventListener('timeupdate', function () {
				var p = audio.duration ? audio.currentTime / audio.duration : 0;
				if (fill) fill.style.width = (p * 100) + '%';
				if (cur) cur.textContent = fmt(audio.currentTime);
			});
			if (bar) {
				bar.addEventListener('click', function (e) {
					var rect = bar.getBoundingClientRect();
					var ratio = (e.clientX - rect.left) / rect.width;
					if (getComputedStyle(bar).direction === 'rtl') ratio = 1 - ratio;
					ratio = Math.min(1, Math.max(0, ratio));
					if (audio.duration) audio.currentTime = ratio * audio.duration;
				});
			}
		});
	}

	function initCarousels() {
		document.querySelectorAll('.szp-carousel').forEach(function (c) {
			var track = c.querySelector('.szp-car-track');
			if (!track) return;
			var prev = c.querySelector('.szp-car-prev');
			var next = c.querySelector('.szp-car-next');
			function step() {
				var card = track.querySelector('.szp-card');
				return card ? card.offsetWidth + 16 : 280;
			}
			// RTL: later cards sit to the left, so "next" scrolls left.
			if (next) next.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); });
			if (prev) prev.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: 'smooth' }); });
		});
	}

	if (document.readyState !== 'loading') init();
	else document.addEventListener('DOMContentLoaded', init);

	/* ---------- Phase 2 interactions ---------- */
	function ajax(action, data, cb) {
		data.append('action', action);
		data.append('nonce', (window.SZP_FRONT && SZP_FRONT.nonce) || '');
		fetch((window.SZP_FRONT && SZP_FRONT.ajax) || '', {
			method: 'POST', credentials: 'same-origin', body: data
		}).then(function (r) { return r.json(); })
			.then(cb)
			.catch(function () { cb({ success: false, data: { msg: 'خطای ارتباط با سرور.' } }); });
	}
	function showMsg(form, res) {
		var msg = form.querySelector('.szp-form-msg');
		if (!msg) return;
		msg.textContent = (res.data && res.data.msg) ? res.data.msg : (res.success ? 'انجام شد.' : 'خطا.');
		msg.className = 'szp-form-msg ' + (res.success ? 'szp-ok' : 'szp-err');
	}

	document.addEventListener('change', function (e) {
		var r = e.target;
		if (r.type === 'radio' && r.closest && r.closest('.szp-stars')) {
			var box = r.closest('.szp-stars');
			box.querySelectorAll('.szp-star').forEach(function (l) { l.classList.remove('on'); });
			var lab = r.closest('.szp-star');
			if (lab) lab.classList.add('on');
		}
		var cb = e.target;
		if (!cb.classList || !cb.classList.contains('szp-check-item')) return;
		var li = cb.closest('li');
		var d = new FormData();
		d.append('session_id', cb.getAttribute('data-session'));
		d.append('index', cb.getAttribute('data-index'));
		d.append('done', cb.checked ? '1' : '');
		cb.disabled = true;
		ajax('szp_save_checklist', d, function () {
			cb.disabled = false;
			if (li) li.classList.toggle('szp-done', cb.checked);
		});
	});

	document.addEventListener('submit', function (e) {
		var f = e.target;
		if (!f.classList) return;
		if (f.classList.contains('szp-task-form') || f.classList.contains('szp-survey-form')) {
			e.preventDefault();
			var action = f.classList.contains('szp-task-form') ? 'szp_submit_task' : 'szp_submit_survey';
			var btn = f.querySelector('button[type=submit]');
			if (btn) btn.disabled = true;
			ajax(action, new FormData(f), function (res) {
				if (btn) btn.disabled = false;
				showMsg(f, res);
			});
		}
	});

	/* theme sync — تشخیص خودکار دارک/لایت از روشنایی پس‌زمینه صفحه ------------ */
	function bgLuma(el) {
		while (el) {
			var c = getComputedStyle(el).backgroundColor;
			var m = c && c.match(/rgba?\(([^)]+)\)/);
			if (m) {
				var p = m[1].split(',').map(parseFloat);
				if (p[3] === undefined || p[3] > 0.1) {
					return (0.2126 * p[0] + 0.7152 * p[1] + 0.0722 * p[2]) / 255;
				}
			}
			el = el.parentElement;
		}
		return null;
	}
	function syncTheme() {
		var l = bgLuma(document.body);
		var theme = l === null ? 'dark' : (l > 0.5 ? 'light' : 'dark');
		document.querySelectorAll('.szp').forEach(function (s) {
			s.setAttribute('data-szp-theme', theme);
		});
	}
	syncTheme();
	var mo = new MutationObserver(syncTheme);
	mo.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'style', 'data-theme', 'data-mode'] });
	if (document.body) mo.observe(document.body, { attributes: true, attributeFilter: ['class', 'style', 'data-theme', 'data-mode'] });
	document.addEventListener('click', function (e) {
		if (e.target.closest && e.target.closest('.dashboard-change-theme-btn')) {
			setTimeout(syncTheme, 60); // بعد از اعمال تم توسط پنل
		}
	});
})();
