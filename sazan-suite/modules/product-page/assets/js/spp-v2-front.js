/** Sazan Product Page v2 interactions — dependency-free and editor-safe. */
( function ( window, document ) {
	'use strict';

	var lastFocus = null;
	var activeModal = null;
	var previousOverflow = '';
	var formatter = null;
	try { formatter = new Intl.NumberFormat( 'fa-IR', { useGrouping: false, minimumIntegerDigits: 2 } ); } catch ( ignore ) {}

	function formatNumber( value ) {
		return formatter ? formatter.format( value ) : String( value ).padStart( 2, '0' );
	}

	function getModal() {
		var modal = document.querySelector( '[data-spp-v2-modal]' );
		if ( modal ) {
			return modal;
		}
		modal = document.createElement( 'div' );
		modal.className = 'spp-v2 spp-v2-modal';
		modal.hidden = true;
		modal.setAttribute( 'data-spp-v2-modal', '' );
		modal.setAttribute( 'role', 'dialog' );
		modal.setAttribute( 'aria-modal', 'true' );
		modal.setAttribute( 'aria-label', 'ویدیوی دوره' );
		modal.innerHTML = '<div class="spp-v2-modal__dialog"><button type="button" class="spp-v2-modal__close" data-spp-v2-modal-close aria-label="بستن ویدیو">×</button><div class="spp-v2-modal__media" data-spp-v2-modal-media></div></div>';
		document.body.appendChild( modal );
		return modal;
	}

	function player( url ) {
		var embed = '';
		var match;
		try { url = new URL( url, window.location.href ).href; } catch ( ignore ) { return null; }
		if ( /(?:youtube\.com|youtu\.be)/i.test( url ) ) {
			match = url.match( /(?:v=|youtu\.be\/|embed\/)([\w-]{6,})/ );
			embed = match ? 'https://www.youtube-nocookie.com/embed/' + match[ 1 ] + '?autoplay=1&rel=0' : '';
		} else if ( /aparat\.com/i.test( url ) ) {
			match = url.match( /\/v\/([\w-]+)/ );
			embed = match ? 'https://www.aparat.com/video/video/embed/videohash/' + match[ 1 ] + '/vt/frame?autoplay=true' : '';
		} else if ( /vimeo\.com/i.test( url ) ) {
			match = url.match( /vimeo\.com\/(?:video\/)?(\d+)/ );
			embed = match ? 'https://player.vimeo.com/video/' + match[ 1 ] + '?autoplay=1' : '';
		}
		if ( embed ) {
			var iframe = document.createElement( 'iframe' );
			iframe.src = embed;
			iframe.title = 'ویدیوی دوره';
			iframe.loading = 'lazy';
			iframe.allow = 'autoplay; fullscreen; picture-in-picture';
			iframe.setAttribute( 'allowfullscreen', '' );
			return iframe;
		}
		var video = document.createElement( 'video' );
		video.src = url;
		video.controls = true;
		video.autoplay = true;
		video.playsInline = true;
		video.preload = 'metadata';
		return video;
	}

	function openVideo( url, trigger ) {
		var media = player( url );
		if ( ! media ) { return; }
		var modal = getModal();
		var slot = modal.querySelector( '[data-spp-v2-modal-media]' );
		slot.replaceChildren( media );
		lastFocus = trigger || document.activeElement;
		activeModal = modal;
		modal.hidden = false;
		previousOverflow = document.documentElement.style.overflow;
		document.documentElement.style.overflow = 'hidden';
		modal.querySelector( '[data-spp-v2-modal-close]' ).focus();
	}

	function closeVideo() {
		if ( ! activeModal ) { return; }
		var slot = activeModal.querySelector( '[data-spp-v2-modal-media]' );
		if ( slot ) { slot.replaceChildren(); }
		activeModal.hidden = true;
		activeModal = null;
		document.documentElement.style.overflow = previousOverflow;
		previousOverflow = '';
		if ( lastFocus && typeof lastFocus.focus === 'function' ) { lastFocus.focus(); }
		lastFocus = null;
	}

	function trapFocus( event ) {
		if ( ! activeModal || event.key !== 'Tab' ) { return; }
		var focusable = activeModal.querySelectorAll( 'button:not([disabled]), a[href], iframe, video[controls], [tabindex]:not([tabindex="-1"])' );
		if ( ! focusable.length ) { return; }
		var first = focusable[ 0 ];
		var last = focusable[ focusable.length - 1 ];
		if ( event.shiftKey && document.activeElement === first ) { event.preventDefault(); last.focus(); }
		else if ( ! event.shiftKey && document.activeElement === last ) { event.preventDefault(); first.focus(); }
	}

	function toggleAccordion( button ) {
		var target = document.getElementById( button.getAttribute( 'aria-controls' ) );
		if ( ! target ) { return; }
		var expanded = button.getAttribute( 'aria-expanded' ) === 'true';
		button.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
		target.hidden = expanded;
	}

	function activateTabs( button, tabSelector, panelSelector ) {
		var root = button.closest( '[data-spp-v2-curriculum], [data-spp-v2-payment]' );
		if ( ! root ) { return; }
		var key = button.getAttribute( tabSelector === '[data-spp-v2-group-tab]' ? 'data-spp-v2-group-tab' : 'data-spp-v2-payment-tab' );
		root.querySelectorAll( tabSelector ).forEach( function ( tab ) {
			var active = tab === button;
			tab.classList.toggle( 'is-active', active );
			tab.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			tab.setAttribute( 'tabindex', active ? '0' : '-1' );
		} );
		root.querySelectorAll( panelSelector ).forEach( function ( panel ) {
			var attr = panelSelector === '[data-spp-v2-group-panel]' ? 'data-spp-v2-group-panel' : 'data-spp-v2-payment-panel';
			var active = panel.getAttribute( attr ) === key;
			panel.hidden = ! active;
			panel.classList.toggle( 'is-active', active );
		} );
	}

	function keyboardTabs( event, selector ) {
		if ( ! [ 'ArrowLeft', 'ArrowRight', 'Home', 'End' ].includes( event.key ) ) { return; }
		var list = event.currentTarget.closest( '[role="tablist"]' );
		var tabs = Array.from( list.querySelectorAll( selector ) );
		var index = tabs.indexOf( event.currentTarget );
		if ( event.key === 'Home' ) { index = 0; }
		else if ( event.key === 'End' ) { index = tabs.length - 1; }
		else { index = ( index + ( event.key === 'ArrowLeft' ? 1 : -1 ) + tabs.length ) % tabs.length; }
		event.preventDefault(); tabs[ index ].focus(); tabs[ index ].click();
	}

	function initNavigation( nav ) {
		if ( nav.dataset.ready ) { return; }
		nav.dataset.ready = '1';
		var links = Array.from( nav.querySelectorAll( '[data-spp-v2-nav-link]' ) );
		var observed = [];
		links.forEach( function ( link ) {
			var target = document.getElementById( link.getAttribute( 'data-spp-v2-nav-link' ) );
			if ( ! target ) { link.hidden = true; return; }
			observed.push( target );
		} );
		if ( ! ( 'IntersectionObserver' in window ) || ! observed.length ) { return; }
		var observer = new IntersectionObserver( function ( entries ) {
			var active = entries.filter( function ( entry ) { return entry.isIntersecting; } ).sort( function ( a, b ) { return b.intersectionRatio - a.intersectionRatio; } )[ 0 ];
			if ( ! active ) { return; }
			links.forEach( function ( link ) {
				var selected = link.getAttribute( 'data-spp-v2-nav-link' ) === active.target.id;
				link.classList.toggle( 'is-active', selected );
				if ( selected ) { link.setAttribute( 'aria-current', 'location' ); } else { link.removeAttribute( 'aria-current' ); }
				if ( selected ) { link.scrollIntoView( { block: 'nearest', inline: 'center', behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth' } ); }
			} );
		}, { rootMargin: '-28% 0px -58% 0px', threshold: [ 0, .15, .35 ] } );
		observed.forEach( function ( section ) { observer.observe( section ); } );
	}

	function initCountdown( element ) {
		if ( element.dataset.ready ) { return; }
		element.dataset.ready = '1';
		var deadline = new Date( element.getAttribute( 'data-spp-v2-countdown' ) );
		if ( Number.isNaN( deadline.getTime() ) ) { element.hidden = true; return; }
		var timer;
		function update() {
			var distance = Math.max( 0, deadline.getTime() - Date.now() );
			var days = Math.floor( distance / 86400000 );
			var hours = Math.floor( ( distance % 86400000 ) / 3600000 );
			var minutes = Math.floor( ( distance % 3600000 ) / 60000 );
			element.querySelector( '[data-days]' ).textContent = formatNumber( days );
			element.querySelector( '[data-hours]' ).textContent = formatNumber( hours );
			element.querySelector( '[data-minutes]' ).textContent = formatNumber( minutes );
			if ( distance <= 0 && timer ) { window.clearInterval( timer ); element.classList.add( 'is-expired' ); var label = element.querySelector( ':scope > span' ); if ( label ) { label.textContent = 'مهلت به پایان رسیده است'; } }
		}
		update(); timer = window.setInterval( update, 30000 );
	}

	function initMobileBar( bar ) {
		if ( bar.dataset.ready ) { return; }
		bar.dataset.ready = '1';
		var targets = [ document.getElementById( 'spp-v2-enrollment' ), document.getElementById( 'spp-v2-final-cta' ) ].filter( Boolean );
		if ( ! targets.length || ! ( 'IntersectionObserver' in window ) ) { return; }
		var visible = new Set();
		var observer = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) { if ( entry.isIntersecting ) { visible.add( entry.target ); } else { visible.delete( entry.target ); } } );
			bar.style.visibility = visible.size ? 'hidden' : '';
			bar.style.opacity = visible.size ? '0' : '';
		}, { threshold: .15 } );
		targets.forEach( function ( target ) { observer.observe( target ); } );
	}

	function init( scope ) {
		( scope || document ).querySelectorAll( '[data-spp-v2-nav]' ).forEach( initNavigation );
		( scope || document ).querySelectorAll( '[data-spp-v2-countdown]' ).forEach( initCountdown );
		( scope || document ).querySelectorAll( '[data-spp-v2-mobile-bar]' ).forEach( initMobileBar );
	}

	document.addEventListener( 'click', function ( event ) {
		var video = event.target.closest( '[data-spp-v2-video]' );
		if ( video ) { event.preventDefault(); openVideo( video.getAttribute( 'data-spp-v2-video' ), video ); return; }
		if ( event.target.closest( '[data-spp-v2-modal-close]' ) ) { event.preventDefault(); closeVideo(); return; }
		if ( activeModal && event.target === activeModal ) { closeVideo(); return; }
		var accordion = event.target.closest( '[data-spp-v2-accordion]' );
		if ( accordion ) { event.preventDefault(); toggleAccordion( accordion ); return; }
		var groupTab = event.target.closest( '[data-spp-v2-group-tab]' );
		if ( groupTab ) { event.preventDefault(); activateTabs( groupTab, '[data-spp-v2-group-tab]', '[data-spp-v2-group-panel]' ); return; }
		var paymentTab = event.target.closest( '[data-spp-v2-payment-tab]' );
		if ( paymentTab ) { event.preventDefault(); activateTabs( paymentTab, '[data-spp-v2-payment-tab]', '[data-spp-v2-payment-panel]' ); return; }
		var load = event.target.closest( '[data-spp-v2-load-reviews]' );
		if ( load ) { event.preventDefault(); loadReviews( load ); return; }
		var anchor = event.target.closest( '.spp-v2 a[href^="#"], .spp-v2-mobile-bar a[href^="#"]' );
		if ( anchor && anchor.hash && document.querySelector( anchor.hash ) ) {
			event.preventDefault(); document.querySelector( anchor.hash ).scrollIntoView( { behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth', block: 'start' } );
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Escape' ) { closeVideo(); }
		trapFocus( event );
		if ( event.target.matches( '[data-spp-v2-group-tab]' ) ) { keyboardTabs( event, '[data-spp-v2-group-tab]' ); }
		if ( event.target.matches( '[data-spp-v2-payment-tab]' ) ) { keyboardTabs( event, '[data-spp-v2-payment-tab]' ); }
	} );

	function loadReviews( button ) {
		var config = window.SPPV2 || {};
		var status = button.parentElement.querySelector( '[role="status"]' );
		var data = new FormData();
		data.append( 'action', 'spp_v2_load_reviews' );
		data.append( 'nonce', config.reviewsNonce || '' );
		data.append( 'product', button.getAttribute( 'data-product' ) );
		data.append( 'offset', button.getAttribute( 'data-offset' ) );
		button.disabled = true;
		if ( status ) { status.textContent = config.i18n ? config.i18n.loading : '…'; }
		fetch( config.ajax || '', { method: 'POST', body: data, credentials: 'same-origin' } )
			.then( function ( response ) { return response.json(); } )
			.then( function ( response ) {
				if ( ! response || ! response.success ) { throw new Error( 'reviews' ); }
				var grid = button.closest( '.spp-v2-reviews' ).querySelector( '[data-spp-v2-review-grid]' );
				grid.insertAdjacentHTML( 'beforeend', response.data.html );
				button.setAttribute( 'data-offset', response.data.next );
				button.disabled = false;
				if ( ! response.data.hasMore ) { button.hidden = true; }
				if ( status ) { status.textContent = response.data.hasMore ? '' : 'همه نظرها نمایش داده شد.'; }
			} )
			.catch( function () { button.disabled = false; if ( status ) { status.textContent = config.i18n ? config.i18n.error : 'خطا'; } } );
	}

	if ( document.readyState === 'loading' ) { document.addEventListener( 'DOMContentLoaded', function () { init( document ); } ); }
	else { init( document ); }
	window.addEventListener( 'elementor/frontend/init', function () { window.setTimeout( function () { init( document ); }, 250 ); } );
}( window, document ) );
