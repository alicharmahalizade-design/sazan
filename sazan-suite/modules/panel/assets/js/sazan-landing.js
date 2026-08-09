/* Sazan Landing – فیلتر آزمون‌ها، آکاردئون سوالات و شمارنده آمار */
( function () {
	'use strict';

	function initFilters( root ) {
		var btns = root.querySelectorAll( '.szl-filter' );
		if ( ! btns.length ) { return; }
		var cards = root.querySelectorAll( '.szl-card[data-cat]' );

		Array.prototype.forEach.call( btns, function ( btn ) {
			btn.addEventListener( 'click', function () {
				var cat = btn.getAttribute( 'data-cat' ) || '';
				Array.prototype.forEach.call( btns, function ( b ) {
					b.classList.toggle( 'is-active', b === btn );
				} );
				Array.prototype.forEach.call( cards, function ( c ) {
					var show = ! cat || cat === '*' || c.getAttribute( 'data-cat' ) === cat;
					c.classList.toggle( 'is-hidden', ! show );
				} );
			} );
		} );
	}

	function initFaq( root ) {
		var items = root.querySelectorAll( '.szl-qa' );
		if ( ! items.length ) { return; }
		var single = root.getAttribute( 'data-single' ) === 'yes';

		Array.prototype.forEach.call( items, function ( item ) {
			var q = item.querySelector( '.szl-qa__q' );
			if ( ! q ) { return; }
			q.addEventListener( 'click', function () {
				var open = item.classList.contains( 'is-open' );
				if ( single ) {
					Array.prototype.forEach.call( items, function ( i ) {
						i.classList.remove( 'is-open' );
						var b = i.querySelector( '.szl-qa__q' );
						if ( b ) { b.setAttribute( 'aria-expanded', 'false' ); }
					} );
				}
				item.classList.toggle( 'is-open', ! open );
				q.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
			} );
		} );
	}

	function toFa( str ) {
		var fa = [ '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ];
		return String( str ).replace( /[0-9]/g, function ( d ) { return fa[ d ]; } );
	}

	function initCounters( root ) {
		var nums = root.querySelectorAll( '.szl-stat__num[data-to]' );
		if ( ! nums.length || ! window.IntersectionObserver ) { return; }

		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( e ) {
				if ( ! e.isIntersecting ) { return; }
				io.unobserve( e.target );
				var el   = e.target;
				var to   = parseFloat( el.getAttribute( 'data-to' ) ) || 0;
				var pre  = el.getAttribute( 'data-prefix' ) || '';
				var post = el.getAttribute( 'data-suffix' ) || '';
				var fa   = el.getAttribute( 'data-fa' ) === 'yes';
				var t0   = null;

				function step( ts ) {
					if ( t0 === null ) { t0 = ts; }
					var p = Math.min( ( ts - t0 ) / 1200, 1 );
					var v = Math.round( to * ( 1 - Math.pow( 1 - p, 3 ) ) );
					el.textContent = pre + ( fa ? toFa( v ) : v ) + post;
					if ( p < 1 ) { requestAnimationFrame( step ); }
				}
				requestAnimationFrame( step );
			} );
		}, { threshold: 0.4 } );

		Array.prototype.forEach.call( nums, function ( n ) { io.observe( n ); } );
	}

	function init( scope ) {
		var roots = ( scope || document ).querySelectorAll( '.szl' );
		Array.prototype.forEach.call( roots, function ( root ) {
			if ( root.dataset.szlReady === '1' ) { return; }
			root.dataset.szlReady = '1';
			initFilters( root );
			initFaq( root );
			initCounters( root );
		} );
	}

	if ( document.readyState !== 'loading' ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', function () { init(); } );
	}

	// سازگاری با ویرایشگر المنتور
	window.addEventListener( 'elementor/frontend/init', function () {
		if ( window.elementorFrontend && elementorFrontend.hooks ) {
			elementorFrontend.hooks.addAction( 'frontend/element_ready/global', function ( $scope ) {
				init( $scope && $scope[ 0 ] ? $scope[ 0 ] : document );
			} );
		}
	} );
}() );
