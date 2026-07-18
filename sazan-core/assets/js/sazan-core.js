( function( $ ) {
	'use strict';

	function debounce( fn, wait ) {
		var t;
		return function() {
			var ctx = this, args = arguments;
			clearTimeout( t );
			t = setTimeout( function() { fn.apply( ctx, args ); }, wait );
		};
	}

	function initSearch( $h ) {
		var $wrap = $h.find( '.sazan-header__search' );
		$wrap.each( function() {
			var $s = $( this ),
				$toggle = $s.find( '.sazan-header__search-toggle' ),
				$panel = $s.find( '.sazan-header__search-panel' ),
				$input = $s.find( '.sazan-header__search-input' ),
				$res = $s.find( '.sazan-header__search-results' );

			$toggle.off( 'click.sz' ).on( 'click.sz', function( e ) {
				e.preventDefault();
				$s.toggleClass( 'is-open' );
				if ( $s.hasClass( 'is-open' ) ) { setTimeout( function() { $input.trigger( 'focus' ); }, 50 ); }
			} );

			var run = debounce( function() {
				var q = $.trim( $input.val() );
				if ( q.length < 2 ) { $res.empty(); return; }
				if ( typeof SazanCore === 'undefined' ) { return; }
				$res.html( '<div class="sr-loading">در حال جستجو…</div>' );
				$.ajax( {
					url: SazanCore.ajax, method: 'GET', dataType: 'json',
					data: {
						action: 'sazan_search', nonce: SazanCore.nonce, q: q,
						source: $panel.data( 'source' ) || 'any',
						count: $panel.data( 'count' ) || 6
					}
				} ).done( function( resp ) {
					if ( ! resp || ! resp.success ) { $res.html( '<div class="sr-empty">نتیجه‌ای یافت نشد.</div>' ); return; }
					var items = resp.data.items || [];
					if ( ! items.length ) { $res.html( '<div class="sr-empty">نتیجه‌ای یافت نشد.</div>' ); return; }
					var html = items.map( function( it ) {
						var img = it.thumb ? '<img src="' + it.thumb + '" alt="">' : '';
						var price = it.price ? '<span class="sr-p">' + it.price + '</span>' : '';
						return '<a href="' + it.link + '">' + img + '<span class="sr-t">' + it.title + price + '</span></a>';
					} ).join( '' );
					$res.html( html );
				} ).fail( function() {
					$res.html( '<div class="sr-empty">خطا در جستجو.</div>' );
				} );
			}, 300 );

			$input.off( 'input.sz keyup.sz' ).on( 'input.sz', run );
		} );
	}

	function initToggles( $h ) {
		// منوی حساب و مینی‌کارت با تریگر کلیک
		$h.find( '.sazan-header__account-wrap, .sazan-header__cart-wrap' ).each( function() {
			var $w = $( this );
			if ( $w.data( 'trigger' ) === 'click' ) {
				$w.children( 'a' ).off( 'click.sz' ).on( 'click.sz', function( e ) {
					e.preventDefault();
					$w.toggleClass( 'is-open' );
				} );
			}
		} );
	}

	function initSticky( $h ) {
		var mode = $h.attr( 'data-sticky' ) || 'none';
		if ( mode !== 'full' && mode !== 'bottom' ) { return; }
		var offset = parseInt( $h.attr( 'data-sticky-offset' ), 10 ) || 0;
		var smart  = $h.attr( 'data-sticky-smart' ) === '1';
		var shrink = $h.attr( 'data-sticky-shrink' ) === '1';
		var stickyBg = $h.attr( 'data-sticky-bg' ) || '';
		// نوار شناور شیشه‌ای: هدر و «نوار چسبان» یکی می‌شوند. فقط برای حالت «ردیف دوم» روی دسکتاپ.
		var floaty = ( $h.attr( 'data-sticky-style' ) || 'bar' ) !== 'solid';
		var header = $h[0];
		var lastY = window.pageYOffset || 0;
		var stuck = false, info = null, triggerY = 0, floated = false;
		var ph = document.createElement( 'div' );
		ph.className = 'sazan-sticky-ph';
		ph.setAttribute( 'aria-hidden', 'true' );

		function pickEl() {
			var full = ( mode === 'full' ) || ( window.innerWidth <= 1024 );
			return full ? header : ( header.querySelector( '.sazan-header__bottom' ) || header );
		}

		// نوار شناور فقط وقتی فعال است که ردیف دوم چسبیده باشد (نه کل هدر، نه موبایل)
		function useFloat( el ) { return floaty && el !== header && window.innerWidth > 1024; }

		function copyInnerWidths( el, clear ) {
			var inners = el.querySelectorAll( '.sazan-header__top-inner, .sazan-header__bottom-inner' );
			for ( var i = 0; i < inners.length; i++ ) {
				if ( clear ) {
					inners[i].style.maxWidth = '';
					inners[i].style.marginLeft = '';
					inners[i].style.marginRight = '';
				} else {
					var mw = getComputedStyle( inners[i] ).maxWidth;
					if ( mw && mw !== 'none' ) {
						inners[i].style.maxWidth = mw;
						inners[i].style.marginLeft = 'auto';
						inners[i].style.marginRight = 'auto';
					}
				}
			}
		}

		function stick( el ) {
			var cs = getComputedStyle( el );
			var bg  = stickyBg || cs.backgroundColor;
			var bgi = cs.backgroundImage;
			var bdf = cs.webkitBackdropFilter || cs.backdropFilter;
			info = { el: el, parent: el.parentNode, next: el.nextSibling };
			ph.style.height = el.offsetHeight + 'px';
			el.parentNode.insertBefore( ph, el );

			floated = useFloat( el );
			// جای و عرضِ دقیقِ ردیفِ عادی را همین‌جا (قبل از انتقال به body) بگیر
			// تا نوارِ شناور دقیقاً هم‌عرض و هم‌راستای هدرِ عادی باشد.
			var floatBox = null;
			if ( floated ) {
				var innerEl = el.querySelector( '.sazan-header__bottom-inner' );
				var r0 = innerEl ? innerEl.getBoundingClientRect() : el.getBoundingClientRect();
				floatBox = { left: Math.round( r0.left ), width: Math.round( r0.width ) };
			}
			document.body.appendChild( el );
			el.classList.add( 'sz-fixed' );
			el.style.position = 'fixed';
			el.style.top = ( offset + ( floated ? 12 : 0 ) ) + 'px';
			el.style.zIndex = '99990';

			if ( floated ) {
				// نوار را دقیقاً به عرض/جای ردیفِ عادی (پیش‌فرض ۸۰٪) قفل کن
				el.classList.add( 'sz-float' );
				el.style.left = floatBox.left + 'px';
				el.style.right = 'auto';
				el.style.margin = '0';
				el.style.width = floatBox.width + 'px';
				el.style.maxWidth = 'none';
				// اینر داخلِ پیل، تمام‌عرض شود
				copyInnerWidths( el, true );
			} else {
				copyInnerWidths( el, false );
				el.style.left = '0';
				el.style.right = '0';
				el.style.margin = '0';
				if ( bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent' ) { el.style.backgroundColor = bg; }
				if ( bgi && bgi !== 'none' ) { el.style.backgroundImage = bgi; }
				if ( bdf && bdf !== 'none' ) { el.style.backdropFilter = bdf; el.style.webkitBackdropFilter = bdf; }
			}
			if ( shrink ) { el.classList.add( 'sz-shrunk' ); }
			stuck = true;
		}

		function unstick() {
			if ( ! stuck || ! info ) { return; }
			var el = info.el;
			copyInnerWidths( el, true );
			el.classList.remove( 'sz-fixed', 'sz-shrunk', 'sz-hidden', 'sz-float' );
			[ 'position', 'left', 'right', 'top', 'margin', 'width', 'maxWidth', 'zIndex', 'backgroundColor', 'backgroundImage', 'backdropFilter', 'webkitBackdropFilter' ].forEach( function ( p ) { el.style[ p ] = ''; } );
			if ( info.next && info.next.parentNode === info.parent ) { info.parent.insertBefore( el, info.next ); }
			else { info.parent.appendChild( el ); }
			if ( ph.parentNode ) { ph.parentNode.removeChild( ph ); }
			stuck = false; info = null; floated = false;
		}

		function applySmart( y ) {
			if ( ! stuck || ! info ) { return; }
			if ( smart && y > triggerY + 80 ) {
				if ( y > lastY ) { info.el.classList.add( 'sz-hidden' ); }
				else { info.el.classList.remove( 'sz-hidden' ); }
			} else {
				info.el.classList.remove( 'sz-hidden' );
			}
		}

		function onScroll() {
			var y = window.pageYOffset || document.documentElement.scrollTop || 0;
			if ( stuck && info && pickEl() !== info.el && ! ( info.el === header ) ) {
				// تغییر حالت موبایل/دسکتاپ هنگام چسبیده‌بودن
				unstick();
			}
			if ( ! stuck ) {
				var el = pickEl();
				triggerY = el.getBoundingClientRect().top + y - offset;
				if ( y > triggerY + 1 ) { stick( el ); applySmart( y ); }
			} else {
				if ( y <= triggerY ) { unstick(); }
				else { applySmart( y ); }
			}
			lastY = y;
		}

		$( window ).off( 'scroll.szsticky resize.szsticky' ).on( 'scroll.szsticky resize.szsticky', onScroll );
		onScroll();
	}

	// نوارِ پیشرفتِ اسکرول (سراسری، تک‌نمونه)
	function initScrollProgress( accent ) {
		if ( document.getElementById( 'sazan-scroll-progress' ) ) { return; }
		var bar = document.createElement( 'div' );
		bar.id = 'sazan-scroll-progress';
		bar.className = 'sazan-scroll-progress';
		bar.setAttribute( 'aria-hidden', 'true' );
		if ( accent ) { bar.style.background = accent; }
		document.body.appendChild( bar );
		var ticking = false;
		function update() {
			var h = document.documentElement;
			var max = ( h.scrollHeight - h.clientHeight ) || 1;
			var p = Math.min( 1, Math.max( 0, ( window.pageYOffset || h.scrollTop || 0 ) / max ) );
			bar.style.transform = 'scaleX(' + p + ')';
			ticking = false;
		}
		function onScroll() { if ( ! ticking ) { ticking = true; requestAnimationFrame( update ); } }
		$( window ).off( 'scroll.szprog resize.szprog' ).on( 'scroll.szprog resize.szprog', onScroll );
		update();
	}

	// مجیک‌لاین: خطِ متحرک زیر منوی اصلی
	function initMagicLine( $h ) {
		var nav = $h.find( '.sazan-header__nav' ).first()[0];
		if ( ! nav ) { return; }
		var ul = nav.querySelector( 'ul, .sazan-menu' );
		if ( ! ul || ul.querySelectorAll( ':scope > li' ).length < 2 ) { return; }
		nav.classList.add( 'has-magic' );
		var line = nav.querySelector( '.sazan-magic-line' );
		if ( ! line ) {
			line = document.createElement( 'span' );
			line.className = 'sazan-magic-line';
			line.setAttribute( 'aria-hidden', 'true' );
			nav.appendChild( line );
		}
		function activeLink() {
			return ul.querySelector( ':scope > li.current-menu-item > a' )
				|| ul.querySelector( ':scope > li.current-menu-parent > a' )
				|| ul.querySelector( ':scope > li.current-menu-ancestor > a' );
		}
		function moveTo( link, show ) {
			if ( ! link ) { line.style.opacity = '0'; return; }
			var nr = nav.getBoundingClientRect(), r = link.getBoundingClientRect();
			if ( ! r.width ) { line.style.opacity = '0'; return; }
			line.style.width = r.width + 'px';
			line.style.transform = 'translateX(' + ( r.left - nr.left ) + 'px)';
			line.style.opacity = show ? '1' : '0';
		}
		function rest() { var a = activeLink(); moveTo( a, !! a ); }
		$( ul ).find( '> li > a' ).off( 'mouseenter.szml focus.szml' ).on( 'mouseenter.szml focus.szml', function () { moveTo( this, true ); } );
		$( ul ).off( 'mouseleave.szml' ).on( 'mouseleave.szml', rest );
		$( window ).off( 'resize.szml' ).on( 'resize.szml', rest );
		setTimeout( rest, 60 );
		rest();
	}

	// پالسِ سبد هنگام افزودن محصول (ووکامرس) — تک‌بار بایند سراسری
	function initCartPulse() {
		if ( window.__szCartPulse ) { return; }
		window.__szCartPulse = true;
		$( document.body ).on( 'added_to_cart', function ( e, fragments, cart_hash, $button ) {
			var qty = 1;
			try { if ( $button && $button.data( 'quantity' ) ) { qty = parseInt( $button.data( 'quantity' ), 10 ) || 1; } } catch ( er ) {}
			// همه‌ی سبدها (نوار چسبانِ شناور به body منتقل می‌شود، پس سراسری هدف می‌گیریم)
			$( '.sazan-header__cart' ).each( function () {
				var $cart = $( this );
				$cart.removeClass( 'sz-cart-pulse' );
				void this.offsetWidth;
				$cart.addClass( 'sz-cart-pulse' );
				var $c = $cart.find( '.sazan-header__cart-count' );
				if ( $c.length ) {
					var cur = parseInt( ( $c.text() || '0' ).replace( /[^\d]/g, '' ), 10 ) || 0;
					$c.text( cur + qty ).removeClass( 'sz-count-bump' );
					void $c[0].offsetWidth;
					$c.addClass( 'sz-count-bump' );
				}
			} );
		} );
	}

	function initFx( $h ) {
		if ( $h.attr( 'data-fx-progress' ) === '1' ) {
			var accent = ( getComputedStyle( $h[0] ).getPropertyValue( '--sz-progress' ) || '' ).trim();
			initScrollProgress( accent );
		}
		if ( $h.attr( 'data-fx-magic' ) === '1' ) { initMagicLine( $h ); }
		if ( $h.attr( 'data-fx-cartpulse' ) === '1' ) { initCartPulse(); }
		if ( $h.attr( 'data-fx-entrance' ) === '1' && $h.attr( 'data-fx-entered' ) !== '1' ) {
			$h.attr( 'data-fx-entered', '1' );
			var isEditor = document.body.classList.contains( 'elementor-editor-active' );
			if ( ! isEditor ) { $h[0].classList.add( 'sz-enter' ); }
		}
	}

	function initHeader( scope ) {
		var $h = $( scope ).hasClass( 'sazan-header' ) ? $( scope ) : $( scope ).find( '.sazan-header' ).first();
		if ( ! $h.length ) { return; }

		// دراپ‌داون دسته‌بندی‌ها
		var $cats = $h.find( '.sazan-header__categories' );
		$cats.find( '.sazan-header__cat-btn' ).off( 'click.sz' ).on( 'click.sz', function( e ) {
			e.preventDefault(); $cats.toggleClass( 'is-open' );
		} );

		// آکاردئونِ دراپ‌داونِ دسته‌بندی: فلش برای آیتم‌های دارای زیرمنو
		$h.find( '.sazan-header__cat-dropdown.sazan-cat-accordion .menu-item-has-children' ).each( function() {
			var $li = $( this );
			if ( ! $li.children( 'a' ).children( '.sazan-mhead-sub-toggle' ).length ) {
				$li.children( 'a' ).append( '<span class="sazan-mhead-sub-toggle" role="button" aria-label="expand"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg></span>' );
			}
		} );

		// کشوی موبایل
		$h.find( '.sazan-header__burger' ).off( 'click.sz' ).on( 'click.sz', function( e ) {
			e.preventDefault();
			$h.toggleClass( 'is-open' );
			$( 'body' ).toggleClass( 'sazan-drawer-open', $h.hasClass( 'is-open' ) );
		} );
		$h.find( '.sazan-header__overlay, .sazan-header__drawer-close' ).off( 'click.sz' ).on( 'click.sz', function() {
			$h.removeClass( 'is-open' );
			$( 'body' ).removeClass( 'sazan-drawer-open' );
		} );

		// زیرمنوهای کشو
		$h.find( '.sazan-header__drawer-nav .menu-item-has-children > a' ).off( 'click.sz' ).on( 'click.sz', function( e ) {
			var $sub = $( this ).siblings( '.sub-menu' );
			if ( $sub.length ) { e.preventDefault(); $sub.slideToggle( 180 ); }
		} );

		initSearch( $h );
		initToggles( $h );
		initSticky( $h );
		initFx( $h );

		// بستن با کلیک بیرون
		$( document ).off( 'click.sz' + ( scope.dataset ? scope.dataset.szid : '' ) );
	}

	// نوار چسبانِ مستقل: انتقال به body و نمایش هنگام اسکرول
	function initStickyBar( scope ) {
		var bar = ( scope && scope.classList && scope.classList.contains( 'sazan-sbar' ) ) ? scope : ( scope ? scope.querySelector( '.sazan-sbar' ) : null );
		if ( ! bar || bar.getAttribute( 'data-sbar-init' ) === '1' ) { return; }
		bar.setAttribute( 'data-sbar-init', '1' );

		var reveal = parseInt( bar.getAttribute( 'data-reveal' ), 10 ); if ( isNaN( reveal ) ) { reveal = 300; }
		var smart  = bar.getAttribute( 'data-smart' ) === '1';
		var pos    = bar.getAttribute( 'data-pos' ) === 'bottom' ? 'bottom' : 'top';
		var off    = parseInt( bar.getAttribute( 'data-offset' ), 10 ) || 0;
		var width  = parseInt( bar.getAttribute( 'data-width' ), 10 ) || 0;
		var bg     = bar.getAttribute( 'data-bg' ) || '';
		var radius = parseInt( bar.getAttribute( 'data-radius' ), 10 ); if ( isNaN( radius ) ) { radius = 18; }
		var pad    = parseInt( bar.getAttribute( 'data-pad' ), 10 ); if ( isNaN( pad ) ) { pad = 10; }
		var editor = document.body.classList.contains( 'elementor-editor-active' );

		bar.classList.add( 'sazan-sbar--' + pos );
		var inner  = bar.querySelector( '.sazan-header__bottom-inner' );
		var bottom = bar.querySelector( '.sazan-header__bottom' );
		var glass  = bar.classList.contains( 'skin-glass' );
		if ( glass ) {
			if ( bottom && width ) { bottom.style.maxWidth = width + 'px'; }   // کارت شیشه‌ای وسط‌چین با عرضِ تنظیم‌شده
		} else if ( inner && width ) {
			inner.style.maxWidth = width + 'px'; inner.style.marginLeft = 'auto'; inner.style.marginRight = 'auto';
		}
		if ( inner ) { inner.style.paddingTop = pad + 'px'; inner.style.paddingBottom = pad + 'px'; }
		if ( bottom ) {
			if ( glass ) { bottom.style.borderRadius = radius + 'px'; }
			else { bottom.style.borderRadius = ( pos === 'bottom' ) ? ( radius + 'px ' + radius + 'px 0 0' ) : ( '0 0 ' + radius + 'px ' + radius + 'px' ); }
		}
		if ( bg && bottom && ! glass ) { bottom.style.background = bg; }

		// در ویرایشگر، ثابت/مخفی نکن تا قابل ویرایش بماند
		if ( editor ) { bar.classList.add( 'sazan-sbar--preview' ); return; }

		if ( bar.parentNode !== document.body ) { document.body.appendChild( bar ); }
		if ( pos === 'top' ) { bar.style.top = off + 'px'; } else { bar.style.bottom = off + 'px'; }

		var lastY = window.pageYOffset || 0;
		function onScroll() {
			var y = window.pageYOffset || document.documentElement.scrollTop || 0;
			var show = y > reveal;
			if ( show && smart && y > lastY + 2 ) { show = false; }
			bar.classList.toggle( 'is-visible', show );
			lastY = y;
		}
		$( window ).off( 'scroll.szsbar resize.szsbar' ).on( 'scroll.szsbar resize.szsbar', onScroll );
		onScroll();
	}

	// نوار موبایل: ثابت، فقط موبایل، با پنل پاپ‌آپ/کشویی و بک‌گراند تیره
	function initMobileBar( scope ) {
		var bar = ( scope && scope.classList && scope.classList.contains( 'sazan-mbar' ) ) ? scope : ( scope ? scope.querySelector( '.sazan-mbar' ) : null );
		if ( ! bar || bar.getAttribute( 'data-mbar-init' ) === '1' ) { return; }
		bar.setAttribute( 'data-mbar-init', '1' );

		var bp     = parseInt( bar.getAttribute( 'data-breakpoint' ), 10 ) || 1024;
		var pos    = bar.getAttribute( 'data-pos' ) === 'top' ? 'top' : 'bottom';
		var smart  = bar.getAttribute( 'data-smart' ) === '1';
		var mode   = bar.getAttribute( 'data-mode' ) === 'sheet' ? 'sheet' : 'popup';
		var editor = document.body.classList.contains( 'elementor-editor-active' );
		var $bar   = $( bar );

		// برو بالا
		$bar.find( '.sazan-mbar-top' ).off( 'click.mbar' ).on( 'click.mbar', function ( e ) {
			e.preventDefault(); window.scrollTo( { top: 0, behavior: 'smooth' } );
		} );

		// آیتم فعال براساس مسیر فعلی
		try {
			var here = location.pathname.replace( /\/+$/, '' );
			$bar.find( 'a.sazan-mbar-item' ).each( function () {
				try { var u = new URL( this.getAttribute( 'data-href' ) || '', location.origin ); if ( here !== '' && u.pathname.replace( /\/+$/, '' ) === here ) { this.classList.add( 'is-active' ); } } catch ( e2 ) {}
			} );
		} catch ( e1 ) {}

		/* ---- سیستم پنل (پاپ‌آپ/کشویی) ---- */
		var $backdrop = $bar.find( '.sazan-mbar-backdrop' );
		var $panelsWrap = $bar.find( '.sazan-mbar-panels' );
		var $panels   = $panelsWrap.find( '.sazan-mbar-panel' );
		var gap = parseInt( $panelsWrap.attr( 'data-gap' ), 10 ); if ( isNaN( gap ) ) { gap = 14; }

		function positionPanel( $panel ) {
			if ( mode === 'sheet' ) {
				var r = bar.getBoundingClientRect();
				var css = { left: r.left + 'px', width: r.width + 'px', right: 'auto', top: 'auto', bottom: 'auto' };
				if ( pos === 'top' ) { css.top = ( r.bottom + gap ) + 'px'; }
				else { css.bottom = ( window.innerHeight - r.top + gap ) + 'px'; }
				$panel.css( css );
			} else {
				$panel.css( { left: '', right: '', width: '', bottom: '', top: 'calc(50% - ' + gap + 'px)' } );
			}
		}

		function openPanel( type ) {
			var $p = $panels.filter( '[data-panel="' + type + '"]' );
			if ( ! $p.length ) { return; }
			$panels.removeClass( 'is-active' );
			positionPanel( $p );
			$p.addClass( 'is-active' );
			$panelsWrap.addClass( 'is-open' );
			$backdrop.addClass( 'is-open' );
			$( 'body' ).addClass( 'sazan-mbar-lock' );
			if ( 'search' === type ) { setTimeout( function () { $p.find( '.sazan-mbar-search-input' ).trigger( 'focus' ); }, 60 ); }
		}
		function closePanel() {
			$panels.removeClass( 'is-active' );
			$panelsWrap.removeClass( 'is-open' );
			$backdrop.removeClass( 'is-open' );
			$( 'body' ).removeClass( 'sazan-mbar-lock' );
		}

		$bar.find( '.sazan-mbar-trigger' ).off( 'click.mbar' ).on( 'click.mbar', function ( e ) {
			e.preventDefault();
			var t = this.getAttribute( 'data-open' );
			if ( $panels.filter( '[data-panel="' + t + '"]' ).hasClass( 'is-active' ) ) { closePanel(); }
			else { openPanel( t ); }
		} );
		$backdrop.off( 'click.mbar' ).on( 'click.mbar', closePanel );
		$panelsWrap.find( '.sazan-mbar-panel-close' ).off( 'click.mbar' ).on( 'click.mbar', closePanel );
		$( document ).off( 'keyup.mbar' ).on( 'keyup.mbar', function ( e ) { if ( e.key === 'Escape' ) { closePanel(); } } );

		// جستجوی ایجکسی داخل پنل
		$panelsWrap.find( '.sazan-mbar-search' ).each( function () {
			var $sc = $( this ), $input = $sc.find( '.sazan-mbar-search-input' ), $res = $sc.find( '.sazan-mbar-search-results' );
			var run = debounce( function () {
				var q = $.trim( $input.val() );
				if ( q.length < 2 ) { $res.empty(); return; }
				if ( typeof SazanCore === 'undefined' ) { return; }
				$res.html( '<div class="sr-loading">در حال جستجو…</div>' );
				$.ajax( { url: SazanCore.ajax, method: 'GET', dataType: 'json',
					data: { action: 'sazan_search', nonce: SazanCore.nonce, q: q, source: $sc.data( 'source' ) || 'any', count: $sc.data( 'count' ) || 6 }
				} ).done( function ( resp ) {
					if ( ! resp || ! resp.success || ! ( resp.data.items || [] ).length ) { $res.html( '<div class="sr-empty">نتیجه‌ای یافت نشد.</div>' ); return; }
					$res.html( resp.data.items.map( function ( it ) {
						var img = it.thumb ? '<img src="' + it.thumb + '" alt="">' : '';
						var price = it.price ? '<span class="sr-p">' + it.price + '</span>' : '';
						return '<a href="' + it.link + '">' + img + '<span class="sr-t">' + it.title + price + '</span></a>';
					} ).join( '' ) );
				} ).fail( function () { $res.html( '<div class="sr-empty">خطا در جستجو.</div>' ); } );
			}, 300 );
			$input.off( 'input.mbar' ).on( 'input.mbar', run );
		} );

		if ( editor ) { bar.classList.add( 'sazan-mbar--preview' ); return; }
		if ( bar.parentNode !== document.body ) { document.body.appendChild( bar ); }
		// پنل‌ها و بک‌گراند را از داخل نوارِ بلوردار خارج می‌کنیم تا fixed نسبت به صفحه باشند
		if ( $backdrop.length && $backdrop[0].parentNode !== document.body ) { document.body.appendChild( $backdrop[0] ); }
		if ( $panelsWrap.length && $panelsWrap[0].parentNode !== document.body ) { document.body.appendChild( $panelsWrap[0] ); }

		function vis() {
			var hide = window.innerWidth > bp;
			bar.classList.toggle( 'mbar-off', hide );
			$panelsWrap.toggleClass( 'mbar-off', hide );
			$backdrop.toggleClass( 'mbar-off', hide );
			if ( pos === 'bottom' ) { document.body.style.paddingBottom = hide ? '' : ( bar.offsetHeight + 'px' ); }
			if ( hide ) { closePanel(); }
			else if ( $panelsWrap.hasClass( 'is-open' ) ) { positionPanel( $panels.filter( '.is-active' ) ); }
		}
		var lastY = window.pageYOffset || 0;
		function onScroll() {
			if ( $panelsWrap.hasClass( 'is-open' ) ) { return; }
			var y = window.pageYOffset || document.documentElement.scrollTop || 0;
			if ( y > lastY + 4 && y > 60 ) { bar.classList.add( 'mbar-hidden' ); }
			else if ( y < lastY - 4 ) { bar.classList.remove( 'mbar-hidden' ); }
			lastY = y;
		}
		vis();
		$( window ).off( 'resize.mbar' ).on( 'resize.mbar', vis );
		if ( smart ) { $( window ).off( 'scroll.mbar' ).on( 'scroll.mbar', onScroll ); }
	}

	// هدر موبایل: نوار بالا + کشوی شیشه‌ای از راست/چپ
	/* ---- هدر موبایل: کنترل سراسریِ کشو (مستقل از زمان‌بندیِ المنتور) ---- */
	function szFixedLayer() {
		var l = document.getElementById( 'sazan-fixed-layer' );
		if ( ! l ) {
			l = document.createElement( 'div' );
			l.id = 'sazan-fixed-layer';
			l.className = 'sazan-fixed-layer';
			document.body.appendChild( l );
		}
		return l;
	}
	function szMheadSearch( el ) {
		var $sc = $( el ).closest( '.sazan-mhead-search' ), $res = $sc.find( '.sazan-mhead-search-results' ), q = $.trim( el.value );
		if ( q.length < 2 ) { $res.empty(); return; }
		if ( typeof SazanCore === 'undefined' ) { return; }
		$res.html( '<div class="sr-loading">در حال جستجو…</div>' );
		$.ajax( { url: SazanCore.ajax, method: 'GET', dataType: 'json',
			data: { action: 'sazan_search', nonce: SazanCore.nonce, q: q, source: $sc.data( 'source' ) || 'any', count: $sc.data( 'count' ) || 6 }
		} ).done( function ( resp ) {
			if ( ! resp || ! resp.success || ! ( resp.data.items || [] ).length ) { $res.html( '<div class="sr-empty">نتیجه‌ای یافت نشد.</div>' ); return; }
			$res.html( resp.data.items.map( function ( it ) {
				var img = it.thumb ? '<img src="' + it.thumb + '" alt="">' : '';
				var price = it.price ? '<span class="sr-p">' + it.price + '</span>' : '';
				return '<a href="' + it.link + '">' + img + '<span class="sr-t">' + it.title + price + '</span></a>';
			} ).join( '' ) );
		} ).fail( function () { $res.html( '<div class="sr-empty">خطا در جستجو.</div>' ); } );
	}
	function szMheadOpen( id, focus ) {
		var sel = id ? ( '[data-mh="' + id + '"]' ) : '';
		$( '.sazan-mhead-drawer' + sel ).addClass( 'is-open' );
		$( '.sazan-mhead-backdrop' + sel ).addClass( 'is-open' );
		$( 'body' ).addClass( 'sazan-mbar-lock' );
		if ( focus ) { setTimeout( function () { $( '.sazan-mhead-drawer' + sel ).find( '.sazan-mhead-search-input' ).trigger( 'focus' ); }, 90 ); }
	}
	function szMheadClose( id ) {
		var sel = id ? ( '[data-mh="' + id + '"]' ) : '';
		$( '.sazan-mhead-drawer' + sel ).removeClass( 'is-open' );
		$( '.sazan-mhead-backdrop' + sel ).removeClass( 'is-open' );
		$( 'body' ).removeClass( 'sazan-mbar-lock' );
	}
	$( document )
		.on( 'click.mhg', '.sazan-mhead-toggle', function ( e ) {
			e.preventDefault();
			szMheadOpen( $( this ).closest( '.sazan-mhead' ).attr( 'data-mh' ), this.getAttribute( 'data-focus' ) === '1' );
		} )
		.on( 'click.mhg', '.sazan-mhead-backdrop, .sazan-mhead-close', function ( e ) {
			e.preventDefault();
			szMheadClose( $( this ).closest( '[data-mh]' ).attr( 'data-mh' ) );
		} )
		.on( 'click.mhg', '.sazan-mhead-sub-toggle', function ( e ) {
			e.preventDefault(); e.stopPropagation();
			$( this ).closest( '.menu-item-has-children' ).toggleClass( 'is-expanded' );
		} )
		.on( 'keyup.mhg', function ( e ) { if ( e.key === 'Escape' ) { szMheadClose( '' ); } } )
		.on( 'input.mhg', '.sazan-mhead-search-input', function () {
			var el = this; clearTimeout( el.__szt ); el.__szt = setTimeout( function () { szMheadSearch( el ); }, 300 );
		} );

	function initMobileHeader( scope ) {
		var bar = ( scope && scope.classList && scope.classList.contains( 'sazan-mhead' ) ) ? scope : ( scope ? scope.querySelector( '.sazan-mhead' ) : null );
		if ( ! bar || bar.getAttribute( 'data-mhead-init' ) === '1' ) { return; }
		bar.setAttribute( 'data-mhead-init', '1' );

		var bp     = parseInt( bar.getAttribute( 'data-breakpoint' ), 10 ) || 1024;
		var sticky = bar.getAttribute( 'data-sticky' ) === '1';
		var editor = document.body.classList.contains( 'elementor-editor-active' );
		var mid    = bar.getAttribute( 'data-mh' );
		var $backdrop = $( '.sazan-mhead-backdrop[data-mh="' + mid + '"]' );
		var $drawer   = $( '.sazan-mhead-drawer[data-mh="' + mid + '"]' );

		if ( editor ) { bar.classList.add( 'sazan-mhead--preview' ); return; }

		// کشو و بک‌گراند را داخل لایه‌ی برش‌خورده می‌گذاریم تا در حالت بسته از صفحه بیرون نزنند (رفع اسکرول افقی)
		var layer = szFixedLayer();
		if ( $backdrop.length && $backdrop[0].parentNode !== layer ) { layer.appendChild( $backdrop[0] ); }
		if ( $drawer.length && $drawer[0].parentNode !== layer ) { layer.appendChild( $drawer[0] ); }

		// آکاردئونی: دکمه‌ی فلش برای آیتم‌های دارای زیرمنو
		var $menu = $drawer.find( '.sazan-mhead-menu' );
		if ( $menu.attr( 'data-accordion' ) === '1' ) {
			$menu.find( '.menu-item-has-children' ).each( function () {
				var $li = $( this );
				if ( ! $li.children( 'a' ).children( '.sazan-mhead-sub-toggle' ).length ) {
					$li.children( 'a' ).append( '<span class="sazan-mhead-sub-toggle" role="button" aria-label="expand"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg></span>' );
				}
			} );
		}

		// چسبان: یک «جای‌گیر» در محل اصلی می‌گذاریم تا محتوا (هیرو) هل بخورد پایین،
		// سپس نوار را به body می‌بریم و با اولویت بالا fixed می‌کنیم.
		var spacer = null;
		if ( sticky ) {
			spacer = document.createElement( 'div' );
			spacer.className = 'sazan-mhead-spacer';
			spacer.style.height = bar.offsetHeight + 'px';
			if ( bar.parentNode ) { bar.parentNode.insertBefore( spacer, bar ); }
			document.body.appendChild( bar );
			bar.classList.add( 'mhead-fixed' );
		}

		function vis() {
			var hide = window.innerWidth > bp;
			bar.classList.toggle( 'mhead-off', hide );
			$backdrop.toggleClass( 'mhead-off', hide );
			$drawer.toggleClass( 'mhead-off', hide );
			if ( hide ) { szMheadClose( mid ); }
			if ( spacer ) { spacer.style.height = hide ? '0px' : ( bar.offsetHeight + 'px' ); }
		}
		vis();
		$( window ).off( 'resize.mh' ).on( 'resize.mh', vis );
		if ( sticky ) {
			var onScrollMh = function () { bar.classList.toggle( 'mhead-scrolled', ( window.pageYOffset || document.documentElement.scrollTop || 0 ) > 8 ); };
			onScrollMh();
			$( window ).off( 'scroll.mh' ).on( 'scroll.mh', onScrollMh );
		}
	}

	// بستن پنل‌های باز با کلیک بیرون (سراسری)
	$( document ).on( 'click.szglobal', function( e ) {
		var $t = $( e.target );
		if ( ! $t.closest( '.sazan-header__categories' ).length ) { $( '.sazan-header__categories' ).removeClass( 'is-open' ); }
		if ( ! $t.closest( '.sazan-header__search' ).length ) { $( '.sazan-header__search' ).removeClass( 'is-open' ); }
		if ( ! $t.closest( '.sazan-header__account-wrap' ).length ) { $( '.sazan-header__account-wrap' ).removeClass( 'is-open' ); }
		if ( ! $t.closest( '.sazan-header__cart-wrap' ).length ) { $( '.sazan-header__cart-wrap' ).removeClass( 'is-open' ); }
	} );

	$( function() {
		// هدر موبایل را همیشه (حتی با المنتور) راه‌اندازی می‌کنیم؛ گارد از تکرار جلوگیری می‌کند
		$( '.sazan-mhead' ).each( function() { initMobileHeader( this ); } );
		if ( typeof elementorFrontend === 'undefined' ) {
			$( '.sazan-header' ).not( '.sazan-sbar, .sazan-mbar' ).each( function() { initHeader( this ); } );
			$( '.sazan-sbar' ).each( function() { initHeader( this ); initStickyBar( this ); } );
			$( '.sazan-mbar' ).each( function() { initHeader( this ); initMobileBar( this ); } );
		}
	} );

	$( window ).on( 'elementor/frontend/init', function() {
		if ( window.elementorFrontend ) {
			elementorFrontend.hooks.addAction( 'frontend/element_ready/sazan-header.default', function( $scope ) {
				initHeader( $scope[0] );
			} );
			elementorFrontend.hooks.addAction( 'frontend/element_ready/sazan-sticky-bar.default', function( $scope ) {
				initHeader( $scope[0] );
				initStickyBar( $scope[0] );
			} );
			elementorFrontend.hooks.addAction( 'frontend/element_ready/sazan-mobile-bar.default', function( $scope ) {
				initHeader( $scope[0] );
				initMobileBar( $scope[0] );
			} );
			elementorFrontend.hooks.addAction( 'frontend/element_ready/sazan-mobile-header.default', function( $scope ) {
				initMobileHeader( $scope[0] );
			} );
		}
	} );

	$( window ).on( 'load', function() {
		// شبکهٔ اطمینان: اگر به هر دلیلی element_ready اجرا نشد (مثل قالب‌های هدرِ Theme Builder)
		$( '.sazan-mhead' ).each( function() { initMobileHeader( this ); } );
		$( '.sazan-mbar' ).each( function() { initHeader( this ); initMobileBar( this ); } );
		$( '.sazan-sbar' ).each( function() { initHeader( this ); initStickyBar( this ); } );
	} );

} )( jQuery );
