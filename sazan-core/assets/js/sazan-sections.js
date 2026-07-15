( function() {
	'use strict';

	/* ===== فیلتر دوره‌ها ===== */
	function initCourses( scope ) {
		if ( scope.dataset.szCourses === '1' ) { return; }
		scope.dataset.szCourses = '1';
		var chips = scope.querySelectorAll( '.sazan-chip' );
		var cards = scope.querySelectorAll( '.sazan-course-card' );
		if ( ! chips.length ) { return; }
		chips.forEach( function( chip ) {
			chip.addEventListener( 'click', function() {
				chips.forEach( function( c ) { c.classList.remove( 'active' ); } );
				chip.classList.add( 'active' );
				var key = chip.getAttribute( 'data-filter' ) || '';
				cards.forEach( function( card ) {
					var cat = card.getAttribute( 'data-cat' ) || '';
					card.style.display = ( ! key || cat === key ) ? '' : 'none';
				} );
			} );
		} );
	}

	/* ===== ابزارها ===== */
	function toFa( s ) { return String( s ).replace( /[0-9]/g, function( d ) { return '۰۱۲۳۴۵۶۷۸۹'.charAt( +d ); } ); }
	function fmtTime( sec ) {
		if ( ! isFinite( sec ) || sec < 0 ) { return ''; }
		sec = Math.floor( sec );
		var m = Math.floor( sec / 60 ), s = sec % 60;
		return toFa( m + ':' + ( s < 10 ? '0' + s : s ) );
	}
	function isSameOrigin( url ) {
		try { return new URL( url, location.href ).origin === location.origin; }
		catch ( e ) { return false; }
	}

	/* ===== پخش‌کننده پادکست + اکولایزر دقیق ===== */
	var AC = null;
	var srcMap = new WeakMap();

	function audioCtx() {
		if ( ! AC ) {
			var Ctor = window.AudioContext || window.webkitAudioContext;
			if ( Ctor ) { AC = new Ctor(); }
		}
		return AC;
	}
	function setupAnalyser( audio ) {
		if ( srcMap.has( audio ) ) { return srcMap.get( audio ); }
		var c = audioCtx();
		if ( ! c ) { return null; }
		var src = c.createMediaElementSource( audio );
		var an = c.createAnalyser();
		an.fftSize = 128;
		an.smoothingTimeConstant = 0.8;
		src.connect( an );
		an.connect( c.destination );
		var obj = { an: an, data: new Uint8Array( an.frequencyBinCount ) };
		srcMap.set( audio, obj );
		return obj;
	}

	function initCard( card, audios, cards ) {
		if ( card.dataset.szPod === '1' ) { return; }
		card.dataset.szPod = '1';

		var audio = card.querySelector( '.sazan-pod-audio' );
		var btns = card.querySelectorAll( '.sazan-pod-play' );
		var bars = card.querySelectorAll( '.sazan-wave i' );
		var fill = card.querySelector( '.sazan-pod-progress .fill' );
		var barEl = card.querySelector( '.sazan-pod-bar' );
		var curEl = card.querySelector( '.sazan-pod-time .cur' );
		var durEl = card.querySelector( '.sazan-pod-time .dur' );
		if ( ! audio || ! btns.length ) { return; }
		audios.push( audio );

		var base = Array.prototype.map.call( bars, function( b ) { return b.style.height; } );
		var raf = null, analyser = null;
		var useWeb = isSameOrigin( audio.getAttribute( 'src' ) || '' );

		function stopRaf() { if ( raf ) { cancelAnimationFrame( raf ); raf = null; } }

		function loop() {
			if ( analyser ) {
				analyser.an.getByteFrequencyData( analyser.data );
				var n = bars.length;
				var usable = Math.floor( analyser.data.length * 0.78 );
				for ( var i = 0; i < n; i++ ) {
					var v = analyser.data[ Math.floor( i / n * usable ) ] || 0;
					var h = Math.min( 100, Math.max( 6, ( v / 255 ) * 110 ) );
					bars[ i ].style.height = h + '%';
				}
			}
			raf = requestAnimationFrame( loop );
		}

		function onPlay() {
			audios.forEach( function( a ) { if ( a !== audio ) { a.pause(); } } );
			cards.forEach( function( c ) { if ( c !== card ) { c.classList.remove( 'is-playing' ); } } );
			card.classList.add( 'is-playing' );
			if ( useWeb ) {
				try {
					var c = audioCtx();
					if ( c && c.state === 'suspended' ) { c.resume(); }
					analyser = setupAnalyser( audio );
					if ( analyser ) { card.classList.remove( 'eq-fallback' ); stopRaf(); loop(); }
					else { card.classList.add( 'eq-fallback' ); }
				} catch ( e ) {
					useWeb = false;
					card.classList.add( 'eq-fallback' );
				}
			} else {
				card.classList.add( 'eq-fallback' );
			}
		}
		function onStop() {
			card.classList.remove( 'is-playing' );
			stopRaf();
			for ( var i = 0; i < bars.length; i++ ) { bars[ i ].style.height = base[ i ] || ''; }
		}

		btns.forEach( function( btn ) {
			if ( btn.tagName !== 'BUTTON' ) { return; }
			btn.addEventListener( 'click', function( e ) {
				e.preventDefault();
				if ( audio.paused ) { audio.play(); } else { audio.pause(); }
			} );
		} );
		audio.addEventListener( 'play', onPlay );
		audio.addEventListener( 'pause', onStop );
		audio.addEventListener( 'ended', onStop );

		// نوار پیشرفت و زمان
		audio.addEventListener( 'loadedmetadata', function() {
			if ( durEl && isFinite( audio.duration ) ) { durEl.textContent = fmtTime( audio.duration ); }
		} );
		audio.addEventListener( 'timeupdate', function() {
			if ( fill && isFinite( audio.duration ) && audio.duration > 0 ) {
				fill.style.width = ( audio.currentTime / audio.duration * 100 ) + '%';
			}
			if ( curEl ) { curEl.textContent = fmtTime( audio.currentTime ); }
		} );
		if ( barEl ) {
			barEl.addEventListener( 'click', function( e ) {
				if ( ! isFinite( audio.duration ) || audio.duration <= 0 ) { return; }
				var rect = barEl.getBoundingClientRect();
				var ratio = ( e.clientX - rect.left ) / rect.width;
				if ( getComputedStyle( barEl ).direction === 'rtl' ) { ratio = 1 - ratio; }
				ratio = Math.min( 1, Math.max( 0, ratio ) );
				audio.currentTime = ratio * audio.duration;
			} );
		}
	}

	function initPodcast( scope ) {
		var cards = Array.prototype.slice.call( scope.querySelectorAll( '.sazan-pod-card' ) );
		var audios = [];
		cards.forEach( function( card ) { initCard( card, audios, cards ); } );
	}

	/* نوار چرخان: هر گروه را آن‌قدر تکرار می‌کنیم که عرضش از عرض ظرف بیشتر شود،
	   تا حلقهٔ translateX(-50%) هیچ‌وقت فضای خالی نشان ندهد و قطع نشود. */
	function fillGroup( group, minW ) {
		if ( null == group.getAttribute( 'data-sz-seed' ) ) {
			group.setAttribute( 'data-sz-seed', group.innerHTML );
		}
		var seed = group.getAttribute( 'data-sz-seed' );
		group.innerHTML = seed;
		var guard = 0;
		while ( group.scrollWidth < minW && guard < 60 ) {
			group.insertAdjacentHTML( 'beforeend', seed );
			guard++;
		}
	}

	function initMarquee( scope ) {
		var list = Array.prototype.slice.call( scope.querySelectorAll( '.sazan-marquee' ) );
		if ( scope.classList && scope.classList.contains( 'sazan-marquee' ) ) { list.push( scope ); }
		list.forEach( function( mq ) {
			var track = mq.querySelector( '.sazan-track' );
			if ( ! track ) { return; }
			var groups = track.querySelectorAll( '.sazan-mq-group' );
			if ( groups.length < 2 ) { return; }
			function relayout() {
				var minW = mq.clientWidth + 60; // کمی بیشتر از عرض ظرف تا لبه‌ها خالی نماند
				if ( minW < 60 ) { return; }
				fillGroup( groups[0], minW );
				fillGroup( groups[1], minW );
				// انیمیشن را با فاصلهٔ دقیقِ یک گروه اجرا می‌کنیم تا حلقه قطعاً بی‌درز باشد
				if ( typeof track.animate !== 'function' ) { return; } // مرورگر قدیمی: CSS کار می‌کند
				var w = groups[0].getBoundingClientRect().width;
				if ( ! w ) { return; }
				var dur = parseFloat( mq.getAttribute( 'data-sz-speed' ) ) || 26;
				var rev = mq.classList.contains( 'rev' );
				if ( mq._szAnim ) { try { mq._szAnim.cancel(); } catch ( e ) {} }
				track.style.animation = 'none'; // غیرفعال‌کردن انیمیشن CSS و سپردن کار به JS
				mq._szAnim = track.animate(
					[ { transform: 'translateX(0)' }, { transform: 'translateX(' + ( -w ) + 'px)' } ],
					{ duration: dur * 1000, iterations: Infinity, easing: 'linear', direction: rev ? 'reverse' : 'normal' }
				);
			}
			relayout();
			if ( ! mq.getAttribute( 'data-sz-bound' ) ) {
				mq.setAttribute( 'data-sz-bound', '1' );
				var t;
				window.addEventListener( 'resize', function() { clearTimeout( t ); t = setTimeout( relayout, 150 ); } );
				if ( mq.closest && mq.closest( '.sazan-mq-pause-yes' ) ) {
					mq.addEventListener( 'mouseenter', function() { if ( mq._szAnim ) { mq._szAnim.pause(); } } );
					mq.addEventListener( 'mouseleave', function() { if ( mq._szAnim ) { mq._szAnim.play(); } } );
				}
			}
		} );
	}

	function initCarousel( scope ) {
		var car = scope.querySelector ? scope.querySelector( '.sazan-course-carousel' ) : null;
		if ( scope.classList && scope.classList.contains( 'sazan-course-carousel' ) ) { car = scope; }
		if ( ! car || car.dataset.szCc === '1' ) { return; }
		car.dataset.szCc = '1';

		var vp    = car.querySelector( '.sazan-cc-viewport' );
		var cards = Array.prototype.slice.call( car.querySelectorAll( '.sazan-course-card' ) );
		var dotsW = car.querySelector( '.sazan-cc-dots' );
		var prev  = car.querySelector( '.sz-cc-arrow.prev' );
		var next  = car.querySelector( '.sz-cc-arrow.next' );
		if ( ! vp || ! cards.length ) { return; }

		var active = 0, dots = [];

		if ( dotsW ) {
			cards.forEach( function( c, i ) {
				var d = document.createElement( 'button' );
				d.type = 'button'; d.className = 'sz-cc-dot';
				d.addEventListener( 'click', function() { goTo( i ); } );
				dotsW.appendChild( d ); dots.push( d );
			} );
		}

		function scrollToCard( card, smooth ) {
			// فقط خودِ کاروسل اسکرول می‌شود، نه کل صفحه (RTL-safe)
			var vpRect = vp.getBoundingClientRect();
			var cRect  = card.getBoundingClientRect();
			var delta  = ( cRect.left + cRect.width / 2 ) - ( vpRect.left + vpRect.width / 2 );
			vp.scrollTo( { left: vp.scrollLeft + delta, behavior: smooth ? 'smooth' : 'auto' } );
		}
		function goTo( i, smooth ) {
			i = Math.max( 0, Math.min( cards.length - 1, i ) );
			scrollToCard( cards[ i ], smooth !== false );
		}

		function update() {
			var box = vp.getBoundingClientRect();
			var mid = box.left + box.width / 2;
			var best = 0, bestD = Infinity;
			cards.forEach( function( card, i ) {
				var r = card.getBoundingClientRect();
				if ( ! r.width ) { return; }
				var d = Math.abs( ( r.left + r.width / 2 ) - mid );
				if ( d < bestD ) { bestD = d; best = i; }
			} );
			active = best;
			cards.forEach( function( card, i ) { card.classList.toggle( 'is-active', i === best ); } );
			dots.forEach( function( d, i ) { d.classList.toggle( 'active', i === best ); } );
			if ( prev ) { prev.disabled = ( best === 0 ); }
			if ( next ) { next.disabled = ( best === cards.length - 1 ); }
		}

		var ticking = false;
		vp.addEventListener( 'scroll', function() {
			if ( ticking ) { return; }
			ticking = true;
			requestAnimationFrame( function() { update(); ticking = false; } );
		} );
		if ( prev ) { prev.addEventListener( 'click', function() { goTo( active - 1 ); } ); }
		if ( next ) { next.addEventListener( 'click', function() { goTo( active + 1 ); } ); }
		window.addEventListener( 'resize', update );

		update();
		// مرکز کردن کارت اول بدون اسکرول صفحه (آنی)
		requestAnimationFrame( function() { goTo( 0, false ); update(); } );
	}

	/* ===== اسکرول موبایلِ «کارت وسط + همسایه بلور» (وبلاگ) ===== */
	function initPeek( scroller ) {
		if ( ! scroller || scroller.dataset.szPeek === '1' ) { return; }
		scroller.dataset.szPeek = '1';
		var cards = Array.prototype.slice.call( scroller.children ).filter( function( c ) {
			return c.classList && c.classList.contains( 'sazan-blog-card' );
		} );
		if ( ! cards.length ) { return; }

		function scrollable() { return scroller.scrollWidth > scroller.clientWidth + 4; }

		function update() {
			if ( ! scrollable() ) { cards.forEach( function( c ) { c.classList.remove( 'is-active' ); } ); return; }
			var box = scroller.getBoundingClientRect();
			var mid = box.left + box.width / 2;
			var best = 0, bd = Infinity;
			cards.forEach( function( c, i ) {
				var r = c.getBoundingClientRect();
				if ( ! r.width ) { return; }
				var d = Math.abs( ( r.left + r.width / 2 ) - mid );
				if ( d < bd ) { bd = d; best = i; }
			} );
			cards.forEach( function( c, i ) { c.classList.toggle( 'is-active', i === best ); } );
		}

		var t = false;
		scroller.addEventListener( 'scroll', function() {
			if ( t ) { return; }
			t = true;
			requestAnimationFrame( function() { update(); t = false; } );
		} );
		window.addEventListener( 'resize', update );

		update();
		// مرکز کردن کارت اول در موبایل بدون اسکرول صفحه
		requestAnimationFrame( function() {
			if ( scrollable() ) {
				var c = cards[0], vp = scroller.getBoundingClientRect(), cr = c.getBoundingClientRect();
				var delta = ( cr.left + cr.width / 2 ) - ( vp.left + vp.width / 2 );
				scroller.scrollTo( { left: scroller.scrollLeft + delta, behavior: 'auto' } );
			}
			update();
		} );
	}

	function initBlog( scope ) {
		var r = scope || document;
		( r.querySelectorAll ? r.querySelectorAll( '.sazan-blog .sazan-blog-grid' ) : [] ).forEach( initPeek );
		if ( r.classList && r.classList.contains( 'sazan-blog' ) ) {
			var g = r.querySelector( '.sazan-blog-grid' ); if ( g ) { initPeek( g ); }
		}
	}

	/* ===== فروشگاه: افزودن به سبد با AJAX ووکامرس (در صورت دسترسی) ===== */
	function initShopBuy( scope ) {
		var r = scope || document;
		var btns = r.querySelectorAll ? r.querySelectorAll( '.sazan-shop-buy[data-product_id]' ) : [];
		btns.forEach( function( btn ) {
			if ( btn.dataset.szBuy === '1' ) { return; }
			btn.dataset.szBuy = '1';
			btn.addEventListener( 'click', function( e ) {
				var hasWC = window.wc_add_to_cart_params && window.jQuery;
				if ( ! hasWC ) { return; } // بدون ووکامرس‌ایجکس، لینک معمولی عمل می‌کند
				e.preventDefault();
				var $ = window.jQuery, pid = btn.getAttribute( 'data-product_id' );
				var orig = btn.textContent;
				btn.classList.add( 'loading' );
				$.post(
					wc_add_to_cart_params.ajax_url || ( wc_add_to_cart_params.wc_ajax_url || '' ).toString().replace( '%%endpoint%%', 'add_to_cart' ),
					{ product_id: pid, quantity: 1, 'add-to-cart': pid },
					function( res ) {
						btn.classList.remove( 'loading' );
						if ( res && res.error && res.product_url ) { window.location = res.product_url; return; }
						$( document.body ).trigger( 'added_to_cart', [ res && res.fragments, res && res.cart_hash, $( btn ) ] );
						btn.classList.add( 'added' );
						btn.textContent = '✓ اضافه شد';
						setTimeout( function() { btn.classList.remove( 'added' ); btn.textContent = orig; }, 2200 );
					}
				);
			} );
		} );
	}

	/* ===== پاپ‌آپ شورت‌کد هیرو (کوئیز و …) — اتصال یک‌باره روی document ===== */
	var heroModalsBound = false;
	function bindHeroModals() {
		if ( heroModalsBound ) { return; }
		heroModalsBound = true;
		function close( m ) { if ( m ) { m.hidden = true; document.body.classList.remove( 'sz-hero-modal-open' ); } }
		document.addEventListener( 'click', function( e ) {
			var op = e.target.closest ? e.target.closest( '.sz-hero-open' ) : null;
			if ( op ) {
				e.preventDefault();
				var m = document.getElementById( op.getAttribute( 'data-sz-target' ) );
				if ( m ) { m.hidden = false; document.body.classList.add( 'sz-hero-modal-open' ); }
				return;
			}
			var box = e.target.closest ? e.target.closest( '.sz-hero-modal' ) : null;
			if ( box && ( e.target.classList.contains( 'sz-hero-modal-ov' ) || ( e.target.closest && e.target.closest( '.sz-hero-modal-x' ) ) ) ) {
				close( box );
			}
		} );
		document.addEventListener( 'keydown', function( e ) {
			if ( e.key === 'Escape' ) {
				[].forEach.call( document.querySelectorAll( '.sz-hero-modal:not([hidden])' ), close );
			}
		} );
	}

	/* ===== اسلایدر هیرو سازان (چند طرح) ===== */
	function initHero( el ) {
		var hero = null;
		if ( el && el.classList && el.classList.contains( 'sazan-hero' ) ) { hero = el; }
		else if ( el && el.querySelector ) { hero = el.querySelector( '.sazan-hero' ); }
		if ( ! hero || hero.dataset.szHero === '1' ) { return; }
		hero.dataset.szHero = '1';

		var track  = hero.querySelector( '.sz-hero-track' );
		var slides = Array.prototype.slice.call( hero.querySelectorAll( '.sz-hero-slide' ) );
		var dots   = Array.prototype.slice.call( hero.querySelectorAll( '.sz-hero-dot' ) );
		var prev   = hero.querySelector( '.sz-hero-arrow.prev' );
		var next   = hero.querySelector( '.sz-hero-arrow.next' );
		if ( ! track || slides.length === 0 ) { return; }

		var vp     = hero.querySelector( '.sz-hero-viewport' );
		var counter = hero.querySelector( '.sz-hero-counter .cur' );
		var isFade = hero.getAttribute( 'data-effect' ) === 'fade';
		var isPeek = hero.getAttribute( 'data-peek' ) === '1';
		var auto   = hero.getAttribute( 'data-autoplay' ) === '1';
		var speed  = parseInt( hero.getAttribute( 'data-speed' ), 10 ) || 6000;
		var idx = 0, timer = null, n = slides.length;

		// مدت هر اسلاید را برای نوار پیشرفتِ طرح مینیمال در CSS در دسترس می‌گذاریم
		hero.style.setProperty( '--sz-dur', speed + 'ms' );

		function paint() {
			if ( isFade ) {
				slides.forEach( function( s, i ) { s.classList.toggle( 'is-active', i === idx ); } );
			} else if ( isPeek ) {
				// مرکز‌چین با پیک: با محاسبهٔ پیکسلی، اسلاید فعال وسط ویوپورت قرار می‌گیرد
				var vpW = vp.clientWidth;
				var sw  = slides[0].getBoundingClientRect().width;
				var cs  = getComputedStyle( track );
				var gap = parseFloat( cs.columnGap || cs.gap || 0 ) || 0;
				var off = ( vpW / 2 ) - ( idx * ( sw + gap ) + sw / 2 );
				track.style.transform = 'translateX(' + off + 'px)';
			} else {
				// چیدمان داخلی مسیر LTR است؛ پس ترنسلیت منفی همیشه درست کار می‌کند
				track.style.transform = 'translateX(' + ( -idx * 100 ) + '%)';
			}
			dots.forEach( function( d, i ) { d.classList.toggle( 'active', i === idx ); } );
			slides.forEach( function( s, i ) { s.classList.toggle( 'is-current', i === idx ); } );
			if ( counter ) { counter.textContent = toFa( ( idx + 1 < 10 ? '0' : '' ) + ( idx + 1 ) ); }
		}
		function go( i ) { idx = ( i % n + n ) % n; paint(); }
		function nextSlide() { go( idx + 1 ); }
		function prevSlide() { go( idx - 1 ); }

		function stop() { if ( timer ) { clearInterval( timer ); timer = null; } }
		function start() { if ( auto && n > 1 ) { stop(); timer = setInterval( nextSlide, speed ); } }

		if ( next ) { next.addEventListener( 'click', function() { nextSlide(); start(); } ); }
		if ( prev ) { prev.addEventListener( 'click', function() { prevSlide(); start(); } ); }
		dots.forEach( function( d, i ) { d.addEventListener( 'click', function() { go( i ); start(); } ); } );

		hero.addEventListener( 'mouseenter', stop );
		hero.addEventListener( 'mouseleave', start );

		// سوایپ لمسی
		var x0 = null;
		hero.addEventListener( 'touchstart', function( e ) { x0 = e.touches[0].clientX; stop(); }, { passive: true } );
		hero.addEventListener( 'touchend', function( e ) {
			if ( x0 === null ) { return; }
			var dx = e.changedTouches[0].clientX - x0;
			if ( Math.abs( dx ) > 40 ) { ( dx < 0 ? nextSlide : prevSlide )(); } // RTL: کشیدن به چپ = بعدی
			x0 = null; start();
		}, { passive: true } );

		if ( isFade ) { hero.classList.add( 'fx-fade' ); }

		/* پارالاکس موس (فقط دسکتاپ، با احترام به کاهش حرکت) */
		var mm = window.matchMedia;
		var reduce = mm && mm( '(prefers-reduced-motion: reduce)' ).matches;
		var coarse = mm && mm( '(pointer: coarse)' ).matches;
		if ( hero.classList.contains( 'parallax' ) && ! reduce && ! coarse ) {
			var praf = null;
			hero.addEventListener( 'mousemove', function( e ) {
				if ( praf ) { return; }
				praf = requestAnimationFrame( function() {
					var r = hero.getBoundingClientRect();
					hero.style.setProperty( '--sz-px', ( ( e.clientX - r.left ) / r.width - 0.5 ).toFixed( 3 ) );
					hero.style.setProperty( '--sz-py', ( ( e.clientY - r.top ) / r.height - 0.5 ).toFixed( 3 ) );
					praf = null;
				} );
			} );
			hero.addEventListener( 'mouseleave', function() {
				hero.style.setProperty( '--sz-px', 0 ); hero.style.setProperty( '--sz-py', 0 );
			} );
		}

		/* شمارش معکوس */
		[].forEach.call( hero.querySelectorAll( '.sz-hero-countdown' ), function( cd ) {
			if ( cd.dataset.szCd === '1' ) { return; }
			cd.dataset.szCd = '1';
			var dl = new Date( cd.getAttribute( 'data-deadline' ) ).getTime();
			if ( isNaN( dl ) ) { return; }
			var bx = { d: cd.querySelector( '[data-u="d"]' ), h: cd.querySelector( '[data-u="h"]' ), m: cd.querySelector( '[data-u="m"]' ), s: cd.querySelector( '[data-u="s"]' ) };
			function pad2( x ) { return ( x < 10 ? '0' : '' ) + x; }
			var t = setInterval( tick, 1000 );
			function tick() {
				var diff = dl - Date.now();
				if ( diff <= 0 ) {
					clearInterval( t );
					cd.innerHTML = '<span class="cd-lbl cd-done">' + ( cd.getAttribute( 'data-expired' ) || '' ) + '</span>';
					return;
				}
				var sec = Math.floor( diff / 1000 );
				var d = Math.floor( sec / 86400 ); sec -= d * 86400;
				var h = Math.floor( sec / 3600 ); sec -= h * 3600;
				var m = Math.floor( sec / 60 ); sec -= m * 60;
				if ( bx.d ) { bx.d.textContent = toFa( pad2( d ) ); }
				if ( bx.h ) { bx.h.textContent = toFa( pad2( h ) ); }
				if ( bx.m ) { bx.m.textContent = toFa( pad2( m ) ); }
				if ( bx.s ) { bx.s.textContent = toFa( pad2( sec ) ); }
			}
			tick();
		} );

		bindHeroModals();

		// در حالت پیک با تغییر اندازه باید مرکز دوباره محاسبه شود
		if ( isPeek && ! isFade ) {
			var rt;
			window.addEventListener( 'resize', function() { clearTimeout( rt ); rt = setTimeout( paint, 120 ); } );
			if ( 'undefined' !== typeof window.ResizeObserver ) {
				new ResizeObserver( function() { paint(); } ).observe( hero );
			}
		}

		paint();
		// یک بار بعد از چیدمان اولیه برای اطمینان از محاسبهٔ درست عرض‌ها
		requestAnimationFrame( paint );
		start();
	}

	/* ===== هیرو قاب‌دار سازان (sazan-hero-cats) ===== */
	function initHeroFrame( el ) {
		var box = null;
		if ( el && el.classList && el.classList.contains( 'sazan-heroframe' ) ) { box = el; }
		else if ( el && el.querySelector ) { box = el.querySelector( '.sazan-heroframe' ); }
		if ( ! box || box.dataset.szInit === '1' ) { return; }
		box.dataset.szInit = '1';

		/* اسکرول نرم با کلیک روی نشان مرکزی */
		var sc = box.querySelector( '.szhf-scroll[data-szhf-scroll]' );
		if ( sc ) {
			sc.addEventListener( 'click', function( e ) {
				var href = sc.getAttribute( 'href' ) || '';
				if ( href.charAt( 0 ) === '#' ) {
					var t = document.querySelector( href );
					if ( t ) { e.preventDefault(); t.scrollIntoView( { behavior: 'smooth', block: 'start' } ); }
				}
			} );
		}

		var slider = box.querySelector( '.szhf-slider' );
		if ( ! slider ) { return; }
		var track  = slider.querySelector( '.szhf-track' );
		var slides = Array.prototype.slice.call( slider.querySelectorAll( '.szhf-slide' ) );
		var dots   = Array.prototype.slice.call( box.querySelectorAll( '.szhf-dot' ) );
		if ( ! track || slides.length < 2 ) { return; }

		var auto  = slider.getAttribute( 'data-autoplay' ) === '1';
		var speed = parseInt( slider.getAttribute( 'data-speed' ), 10 ) || 5000;
		var idx = 0, timer = null, n = slides.length;

		function paint() {
			track.style.transform = 'translateX(' + ( -idx * 100 ) + '%)';
			dots.forEach( function( d, i ) { d.classList.toggle( 'active', i === idx ); } );
		}
		function go( i ) { idx = ( i % n + n ) % n; paint(); }
		function nextSlide() { go( idx + 1 ); }
		function prevSlide() { go( idx - 1 ); }
		function stop() { if ( timer ) { clearInterval( timer ); timer = null; } }
		function start() { if ( auto && n > 1 ) { stop(); timer = setInterval( nextSlide, speed ); } }

		dots.forEach( function( d, i ) { d.addEventListener( 'click', function() { go( i ); start(); } ); } );
		box.addEventListener( 'mouseenter', stop );
		box.addEventListener( 'mouseleave', start );

		// سوایپ لمسی (RTL: کشیدن به چپ = اسلاید بعدی)
		var x0 = null;
		slider.addEventListener( 'touchstart', function( e ) { x0 = e.touches[0].clientX; stop(); }, { passive: true } );
		slider.addEventListener( 'touchend', function( e ) {
			if ( x0 === null ) { return; }
			var dx = e.changedTouches[0].clientX - x0;
			if ( Math.abs( dx ) > 40 ) { ( dx < 0 ? nextSlide : prevSlide )(); }
			x0 = null; start();
		}, { passive: true } );

		paint();
		start();
	}

	/* ===== اسلایدر محصول سازان (sazan-product-slider) ===== */
	function initProdSlider( el ) {
		var box = null;
		if ( el && el.classList && el.classList.contains( 'sazan-prodslider' ) ) { box = el; }
		else if ( el && el.querySelector ) { box = el.querySelector( '.sazan-prodslider' ); }
		if ( ! box || box.dataset.szInit === '1' ) { return; }
		box.dataset.szInit = '1';

		var items  = Array.prototype.slice.call( box.querySelectorAll( '.sps-item' ) );
		var floats = Array.prototype.slice.call( box.querySelectorAll( '.sps-float__item' ) );
		var prev   = box.querySelector( '.sps-prev' );
		var next   = box.querySelector( '.sps-next' );
		if ( items.length < 1 ) { return; }

		var auto  = box.getAttribute( 'data-autoplay' ) === '1';
		var loop  = box.getAttribute( 'data-loop' ) === '1';
		var speed = parseInt( box.getAttribute( 'data-speed' ), 10 ) || 6000;
		var idx = 0, timer = null, n = items.length;

		function paint() {
			items.forEach( function( it, i ) { it.classList.toggle( 'is-active', i === idx ); } );
			floats.forEach( function( f, i ) { f.classList.toggle( 'is-active', i === idx ); } );
			if ( ! loop ) {
				if ( prev ) { prev.classList.toggle( 'is-disabled', idx === 0 ); }
				if ( next ) { next.classList.toggle( 'is-disabled', idx === n - 1 ); }
			}
		}
		function go( i ) {
			if ( loop ) { idx = ( i % n + n ) % n; }
			else { idx = Math.max( 0, Math.min( n - 1, i ) ); }
			paint();
		}
		function nextSlide() { ( loop || idx < n - 1 ) ? go( idx + 1 ) : go( 0 ); }
		function prevSlide() { go( idx - 1 ); }
		function stop() { if ( timer ) { clearInterval( timer ); timer = null; } }
		function start() { if ( auto && n > 1 ) { stop(); timer = setInterval( nextSlide, speed ); } }

		if ( next ) { next.addEventListener( 'click', function() { nextSlide(); start(); } ); }
		if ( prev ) { prev.addEventListener( 'click', function() { prevSlide(); start(); } ); }
		box.addEventListener( 'mouseenter', stop );
		box.addEventListener( 'mouseleave', start );

		// دکمه‌ی دوم (تاگلِ نشان‌کردن) — فقط وقتی لینک ندارد
		box.querySelectorAll( '.sps-fav--toggle' ).forEach( function( f ) {
			f.addEventListener( 'click', function() { f.classList.toggle( 'is-fav' ); } );
		} );

		// پاپ‌آپِ تیزر (آپارات/یوتیوب/mp4)
		var lb = box.querySelector( '.sps-lightbox' );
		if ( lb ) {
			// انتقال به body تا از overflow/transformِ اجداد رها شود (position:fixed مطمئن).
			if ( box.classList.contains( 'is-glass' ) ) { lb.classList.add( 'is-glass-lb' ); }
			if ( lb.parentNode !== document.body ) { document.body.appendChild( lb ); }
			var lbMedia = lb.querySelector( '.sps-lightbox__media' );
			var lbClose = lb.querySelector( '.sps-lightbox__close' );
			var lbBg    = lb.querySelector( '.sps-lightbox__bg' );
			var buildMedia = function( url ) {
				url = ( url || '' ).trim();
				var yt = url.match( /(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{6,})/ );
				var node;
				if ( /\.mp4(\?|#|$)/i.test( url ) ) {
					node = document.createElement( 'video' );
					node.src = url; node.controls = true; node.autoplay = true; node.playsInline = true;
				} else {
					node = document.createElement( 'iframe' );
					node.setAttribute( 'allow', 'autoplay; fullscreen; encrypted-media' );
					node.setAttribute( 'allowfullscreen', '' );
					if ( yt ) {
						node.src = 'https://www.youtube.com/embed/' + yt[1] + '?autoplay=1&rel=0';
					} else if ( /aparat\.com/i.test( url ) ) {
						var ap = url.match( /aparat\.com\/(?:v\/|video\/video\/embed\/videohash\/)?([A-Za-z0-9]+)/ );
						node.src = ap ? 'https://www.aparat.com/video/video/embed/videohash/' + ap[1] + '/vt/frame?autoplay=true' : url;
					} else {
						node.src = url;
					}
				}
				return node;
			};
			var openLb = function( url ) {
				if ( ! url ) { return; }
				lbMedia.innerHTML = '';
				lbMedia.appendChild( buildMedia( url ) );
				lb.classList.add( 'is-open' );
				lb.setAttribute( 'aria-hidden', 'false' );
				stop();
			};
			var closeLb = function() {
				lb.classList.remove( 'is-open' );
				lb.setAttribute( 'aria-hidden', 'true' );
				lbMedia.innerHTML = '';
				start();
			};
			box.querySelectorAll( '.sps-play' ).forEach( function( b ) {
				b.addEventListener( 'click', function( e ) { e.preventDefault(); openLb( b.getAttribute( 'data-video' ) ); } );
			} );
			if ( lbClose ) { lbClose.addEventListener( 'click', closeLb ); }
			if ( lbBg ) { lbBg.addEventListener( 'click', closeLb ); }
			document.addEventListener( 'keyup', function( e ) { if ( 'Escape' === e.key && lb.classList.contains( 'is-open' ) ) { closeLb(); } } );
		}

		// پارالاکسِ پوستر با موس (فقط دسکتاپ)
		if ( box.getAttribute( 'data-parallax' ) === '1' && window.matchMedia && window.matchMedia( '(min-width:993px) and (pointer:fine)' ).matches ) {
			var floatBox = box.querySelector( '.sps-float' );
			var content  = box.querySelector( '.sps-content' );
			if ( floatBox && content ) {
				content.addEventListener( 'mousemove', function( e ) {
					var r = content.getBoundingClientRect();
					var dx = ( ( e.clientX - r.left ) / r.width - 0.5 ) * 26;
					var dy = ( ( e.clientY - r.top ) / r.height - 0.5 ) * 18;
					floatBox.style.setProperty( '--sps-px', dx.toFixed( 1 ) + 'px' );
					floatBox.style.setProperty( '--sps-py', dy.toFixed( 1 ) + 'px' );
				} );
				content.addEventListener( 'mouseleave', function() {
					floatBox.style.setProperty( '--sps-px', '0px' );
					floatBox.style.setProperty( '--sps-py', '0px' );
				} );
			}
		}

		// سوایپ لمسی
		var x0 = null;
		box.addEventListener( 'touchstart', function( e ) { x0 = e.touches[0].clientX; stop(); }, { passive: true } );
		box.addEventListener( 'touchend', function( e ) {
			if ( x0 === null ) { return; }
			var dx = e.changedTouches[0].clientX - x0;
			if ( Math.abs( dx ) > 40 ) { ( dx < 0 ? nextSlide : prevSlide )(); }
			x0 = null; start();
		}, { passive: true } );

		// شمارش معکوس
		var timers = Array.prototype.slice.call( box.querySelectorAll( '.sps-countdown' ) );
		if ( timers.length ) {
			var faDigit = function( s ) { return String( s ).replace( /[0-9]/g, function( d ) { return '۰۱۲۳۴۵۶۷۸۹'.charAt( +d ); } ); };
			var pad = function( x ) { return faDigit( ( x < 10 ? '0' : '' ) + x ); };
			var tick = function() {
				timers.forEach( function( t ) {
					var dl = new Date( t.getAttribute( 'data-deadline' ) ).getTime();
					var diff = Math.floor( ( dl - Date.now() ) / 1000 );
					if ( isNaN( dl ) ) { return; }
					if ( diff <= 0 ) { t.classList.add( 'is-ended' ); return; }
					t.classList.remove( 'is-ended' );
					var d = Math.floor( diff / 86400 ), h = Math.floor( diff % 86400 / 3600 ), m = Math.floor( diff % 3600 / 60 ), sec = diff % 60;
					var map = { d: d, h: h, m: m, s: sec };
					t.querySelectorAll( 'b[data-u]' ).forEach( function( b ) { b.textContent = pad( map[ b.getAttribute( 'data-u' ) ] ); } );
				} );
			};
			tick();
			setInterval( tick, 1000 );
		}

		paint();
		start();
	}

	function initAll( root ) {
		var r = root || document;
		r.querySelectorAll( '.sazan-prodslider' ).forEach( initProdSlider );
		r.querySelectorAll( '.sazan-courses' ).forEach( initCourses );
		r.querySelectorAll( '.sazan-courses.skin-lux' ).forEach( initCarousel );
		r.querySelectorAll( '.sazan-podcast' ).forEach( initPodcast );
		r.querySelectorAll( '.sazan-hero' ).forEach( initHero );
		r.querySelectorAll( '.sazan-heroframe' ).forEach( initHeroFrame );
		initBlog( r );
		initMarquee( r );
		initShopBuy( r );
	}

	if ( document.readyState !== 'loading' ) { initAll(); }
	else { document.addEventListener( 'DOMContentLoaded', function() { initAll(); } ); }

	if ( window.jQuery ) {
		jQuery( window ).on( 'elementor/frontend/init', function() {
			if ( window.elementorFrontend ) {
				elementorFrontend.hooks.addAction( 'frontend/element_ready/sazan-courses.default', function( $s ) { initCourses( $s[0] ); initCarousel( $s[0] ); } );
				elementorFrontend.hooks.addAction( 'frontend/element_ready/sazan-shop.default', function( $s ) { initCourses( $s[0] ); initCarousel( $s[0] ); initShopBuy( $s[0] ); } );
				elementorFrontend.hooks.addAction( 'frontend/element_ready/sazan-podcast.default', function( $s ) { initPodcast( $s[0] ); } );
				elementorFrontend.hooks.addAction( 'frontend/element_ready/sazan-blog.default', function( $s ) { initBlog( $s[0] ); } );
				elementorFrontend.hooks.addAction( 'frontend/element_ready/sazan-marquee.default', function( $s ) { initMarquee( $s[0] ); } );
				elementorFrontend.hooks.addAction( 'frontend/element_ready/sazan-hero.default', function( $s ) { initHero( $s[0] ); } );
				elementorFrontend.hooks.addAction( 'frontend/element_ready/sazan-hero-cats.default', function( $s ) { initHeroFrame( $s[0] ); } );
				elementorFrontend.hooks.addAction( 'frontend/element_ready/sazan-product-slider.default', function( $s ) { initProdSlider( $s[0] ); } );
			}
		} );
	}
} )();
