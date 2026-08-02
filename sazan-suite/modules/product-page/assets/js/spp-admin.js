/**
 * سازان — صفحه اختصاصی محصول | اسکریپت متاباکس
 * تب‌ها، ریپیتر (افزودن/حذف/جابه‌جایی) و انتخاب تصویر.
 */
jQuery( function ( $ ) {
	'use strict';

	var root = $( '.spp-mb' );
	if ( ! root.length ) {
		return;
	}

	/* --- تب‌ها ----------------------------------------------------------- */
	root.on( 'click', '.spp-mb__tab', function () {
		var id = $( this ).data( 'spp-tab' );

		root.find( '.spp-mb__tab' ).removeClass( 'is-active' );
		$( this ).addClass( 'is-active' );

		root.find( '.spp-mb__panel' ).removeClass( 'is-active' )
			.filter( '[data-spp-panel="' + id + '"]' ).addClass( 'is-active' );
	} );

	/* --- ریپیتر --------------------------------------------------------- */
	function reindex( rep ) {
		rep.find( '> .spp-rep__rows > .spp-rep__row' ).each( function ( i ) {
			$( this ).find( '[name]' ).each( function () {
				var name = $( this ).attr( 'name' );
				$( this ).attr( 'name', name.replace( /\[(\d+|__i__)\](\[[^\]]+\])$/, '[' + i + ']$2' ) );
			} );
		} );
	}

	root.on( 'click', '.spp-rep__add', function () {
		var rep  = $( this ).closest( '[data-spp-rep]' );
		var rows = rep.find( '> .spp-rep__rows' );
		var max  = parseInt( rep.data( 'max' ), 10 ) || 20;

		if ( rows.children( '.spp-rep__row' ).length >= max ) {
			window.alert( 'حداکثر ' + max + ' ردیف مجاز است.' );
			return;
		}

		var html = rep.find( '> .spp-rep__tpl' ).html()
			.replace( /__i__/g, rows.children( '.spp-rep__row' ).length );

		rows.append( html );
		reindex( rep );
	} );

	root.on( 'click', '.spp-rep__del', function () {
		var rep = $( this ).closest( '[data-spp-rep]' );
		$( this ).closest( '.spp-rep__row' ).remove();
		reindex( rep );
	} );

	if ( $.fn.sortable ) {
		root.find( '.spp-rep__rows' ).each( function () {
			var rows   = $( this );
			var inline = rows.closest( '[data-spp-rep]' ).hasClass( 'spp-rep--inline' );

			rows.sortable( {
				handle: '.spp-rep__grip',
				axis: inline ? false : 'y',
				placeholder: 'spp-rep__ph',
				update: function () {
					reindex( $( this ).closest( '[data-spp-rep]' ) );
				}
			} );
		} );
	}

	/* --- انتخاب تصویر --------------------------------------------------- */
	var frame = null;

	root.on( 'click', '.spp-media__pick', function () {
		var wrap = $( this ).closest( '.spp-media' );

		frame = wp.media( {
			title: 'انتخاب تصویر',
			button: { text: 'استفاده از این تصویر' },
			library: { type: 'image' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var att   = frame.state().get( 'selection' ).first().toJSON();
			var sizes = att.sizes || {};
			var url   = ( sizes.thumbnail && sizes.thumbnail.url ) || att.url;

			wrap.find( '.spp-media__id' ).val( att.id );
			wrap.find( '.spp-media__prev' ).html( '<img src="' + url + '" alt="">' );
			wrap.addClass( 'has-img' );
		} );

		frame.open();
	} );

	root.on( 'click', '.spp-media__del', function () {
		var wrap = $( this ).closest( '.spp-media' );
		wrap.find( '.spp-media__id' ).val( '' );
		wrap.find( '.spp-media__prev' ).empty();
		wrap.removeClass( 'has-img' );
	} );
} );
