/**
 * سازان — صفحه اختصاصی محصول | اسکریپت فرانت
 * مودالِ ویدیوی پیش‌نمایش + اسکرول نرم روی لینک‌های لنگری.
 */
( function () {
	'use strict';

	var modal = null;

	/**
	 * تبدیل آدرس ویدیو به المان قابل پخش.
	 */
	function player( url ) {
		var embed = null;

		if ( /youtube\.com|youtu\.be/i.test( url ) ) {
			var yt = url.match( /(?:v=|youtu\.be\/|embed\/)([\w-]{6,})/ );
			embed = yt ? 'https://www.youtube.com/embed/' + yt[ 1 ] + '?autoplay=1&rel=0' : null;
		} else if ( /aparat\.com/i.test( url ) ) {
			var ap = url.match( /\/v\/([\w-]+)/ );
			embed = ap ? 'https://www.aparat.com/video/video/embed/videohash/' + ap[ 1 ] + '/vt/frame?autoplay=true' : url;
		} else if ( /vimeo\.com/i.test( url ) ) {
			var vm = url.match( /vimeo\.com\/(\d+)/ );
			embed = vm ? 'https://player.vimeo.com/video/' + vm[ 1 ] + '?autoplay=1' : null;
		}

		if ( embed ) {
			var frame = document.createElement( 'iframe' );
			frame.src = embed;
			frame.setAttribute( 'allow', 'autoplay; fullscreen; picture-in-picture' );
			frame.setAttribute( 'allowfullscreen', 'true' );
			return frame;
		}

		var video = document.createElement( 'video' );
		video.src = url;
		video.controls = true;
		video.autoplay = true;
		video.playsInline = true;
		return video;
	}

	function close() {
		if ( ! modal ) {
			return;
		}
		modal.remove();
		modal = null;
		document.documentElement.style.overflow = '';
	}

	function open( url ) {
		close();

		modal = document.createElement( 'div' );
		modal.className = 'spp-modal';
		modal.setAttribute( 'role', 'dialog' );
		modal.setAttribute( 'aria-modal', 'true' );

		var box = document.createElement( 'div' );
		box.className = 'spp-modal__box';

		var x = document.createElement( 'button' );
		x.type = 'button';
		x.className = 'spp-modal__x';
		x.setAttribute( 'aria-label', 'بستن' );
		x.textContent = '×';
		x.addEventListener( 'click', close );

		box.appendChild( x );
		box.appendChild( player( url ) );
		modal.appendChild( box );

		modal.addEventListener( 'click', function ( e ) {
			if ( e.target === modal ) {
				close();
			}
		} );

		document.body.appendChild( modal );
		document.documentElement.style.overflow = 'hidden';
		x.focus();
	}

	/* =====================================================================
	 * پاپ‌آپِ عمومی (فرم ثبت تجربه)
	 * =================================================================== */

	var lastFocus = null;

	function openPopup( id ) {
		var box = document.getElementById( id );
		if ( ! box ) {
			return;
		}

		lastFocus = document.activeElement;
		box.hidden = false;
		document.documentElement.style.overflow = 'hidden';

		// اولین فیلدِ واقعی، نه دکمه‌ی بستن و نه تله‌ی ربات.
		var first = box.querySelector( 'input:not([type="hidden"]):not([tabindex="-1"]), textarea' )
			|| box.querySelector( 'button' );

		if ( first ) {
			first.focus();
		}
	}

	function closePopup( box ) {
		if ( ! box ) {
			return;
		}
		box.hidden = true;
		document.documentElement.style.overflow = '';
		if ( lastFocus ) {
			lastFocus.focus();
			lastFocus = null;
		}
	}

	function closeAllPopups() {
		document.querySelectorAll( '.spp-modal--form:not([hidden])' ).forEach( closePopup );
	}

	/* =====================================================================
	 * کاروسل نظرات
	 * =================================================================== */

	function step( track ) {
		var item = track.querySelector( ':scope > *' );
		if ( ! item ) {
			return track.clientWidth;
		}
		var gap = parseFloat( getComputedStyle( track ).columnGap ) || 0;
		return item.getBoundingClientRect().width + gap;
	}

	function syncNav( carousel ) {
		var track = carousel.querySelector( '[data-spp-track]' );
		if ( ! track ) {
			return;
		}

		var max  = track.scrollWidth - track.clientWidth;
		// در RTL مقدار scrollLeft منفی (یا نزولی) است؛ با قدرمطلق یکسانش می‌کنیم.
		var pos  = Math.abs( track.scrollLeft );
		var prev = carousel.querySelector( '[data-spp-slide="prev"]' );
		var next = carousel.querySelector( '[data-spp-slide="next"]' );

		// موقعیت اسکرول کسری است، پس با تلورانس مقایسه می‌کنیم.
		var tol = 3;

		if ( max <= tol ) {
			carousel.classList.add( 'is-static' );
			return;
		}

		carousel.classList.remove( 'is-static' );

		if ( next ) {
			next.disabled = pos <= tol;
		}
		if ( prev ) {
			prev.disabled = pos >= max - tol;
		}
	}

	function initCarousels() {
		document.querySelectorAll( '.spp-tst__carousel' ).forEach( function ( carousel ) {
			var track = carousel.querySelector( '[data-spp-track]' );
			if ( ! track || track.dataset.sppReady ) {
				return;
			}
			track.dataset.sppReady = '1';

			track.addEventListener( 'scroll', function () {
				syncNav( carousel );
			}, { passive: true } );

			window.addEventListener( 'resize', function () {
				syncNav( carousel );
			} );

			syncNav( carousel );
		} );
	}

	/* =====================================================================
	 * ارسال فرم
	 * =================================================================== */

	function submitForm( form ) {
		var msg = form.querySelector( '[data-spp-msg]' );
		var btn = form.querySelector( 'button[type="submit"]' );
		var cfg = window.SPP || {};

		if ( ! form.checkValidity() ) {
			form.reportValidity();
			return;
		}

		if ( msg ) {
			msg.className = 'spp-form__msg is-busy';
			msg.textContent = ( cfg.i18n && cfg.i18n.sending ) || '…';
		}
		if ( btn ) {
			btn.disabled = true;
		}

		var data = new FormData( form );
		data.append( 'action', 'spp_submit_review' );
		data.append( 'nonce', cfg.nonce || '' );

		fetch( cfg.ajax, { method: 'POST', body: data, credentials: 'same-origin' } )
			.then( function ( r ) {
				return r.json().catch( function () {
					return { success: false, data: {} };
				} );
			} )
			.then( function ( res ) {
				var ok   = res && res.success;
				var text = ( res && res.data && res.data.message ) || ( cfg.i18n && cfg.i18n.error );

				if ( msg ) {
					msg.className = 'spp-form__msg ' + ( ok ? 'is-ok' : 'is-err' );
					msg.textContent = text || '';
				}

				if ( ok ) {
					form.querySelectorAll( 'input[type="text"], input[type="email"], textarea' ).forEach( function ( el ) {
						el.value = '';
					} );
					if ( btn ) {
						btn.hidden = true;
					}
				} else if ( btn ) {
					btn.disabled = false;
				}
			} )
			.catch( function () {
				if ( msg ) {
					msg.className = 'spp-form__msg is-err';
					msg.textContent = ( cfg.i18n && cfg.i18n.error ) || '';
				}
				if ( btn ) {
					btn.disabled = false;
				}
			} );
	}

	/* =====================================================================
	 * رویدادها
	 * =================================================================== */

	document.addEventListener( 'click', function ( e ) {
		var video = e.target.closest( '[data-spp-video]' );
		if ( video ) {
			e.preventDefault();
			open( video.getAttribute( 'data-spp-video' ) );
			return;
		}

		var opener = e.target.closest( '[data-spp-open]' );
		if ( opener ) {
			e.preventDefault();
			openPopup( opener.getAttribute( 'data-spp-open' ) );
			return;
		}

		if ( e.target.closest( '[data-spp-close]' ) ) {
			e.preventDefault();
			closePopup( e.target.closest( '.spp-modal--form' ) );
			return;
		}

		// کلیک روی پس‌زمینه‌ی پاپ‌آپ
		if ( e.target.classList && e.target.classList.contains( 'spp-modal--form' ) ) {
			closePopup( e.target );
			return;
		}

		var nav = e.target.closest( '[data-spp-slide]' );
		if ( nav ) {
			e.preventDefault();
			var carousel = nav.closest( '.spp-tst__carousel' );
			var track    = carousel && carousel.querySelector( '[data-spp-track]' );
			if ( track ) {
				var dir = 'next' === nav.getAttribute( 'data-spp-slide' ) ? 1 : -1;
				// در RTL محور افقی معکوس است.
				track.scrollBy( { left: dir * step( track ), behavior: 'smooth' } );
			}
			return;
		}

		var anchor = e.target.closest( '.spp a[href^="#"]' );
		if ( anchor && anchor.getAttribute( 'href' ).length > 1 ) {
			var target = document.querySelector( anchor.getAttribute( 'href' ) );
			if ( target ) {
				e.preventDefault();
				target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
			}
		}
	} );

	document.addEventListener( 'submit', function ( e ) {
		var form = e.target.closest( '[data-spp-form]' );
		if ( form ) {
			e.preventDefault();
			submitForm( form );
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key ) {
			close();
			closeAllPopups();
		}
	} );

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', initCarousels );
	} else {
		initCarousels();
	}

	// در ادیتور المنتور محتوا بعداً تزریق می‌شود.
	window.addEventListener( 'elementor/frontend/init', function () {
		setTimeout( initCarousels, 300 );
	} );
}() );
