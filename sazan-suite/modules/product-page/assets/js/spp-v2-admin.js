/** Product Page v2 admin: tabs, nested repeaters, conditions, media and rich text. */
( function ( window, document, $ ) {
	'use strict';

	var root = document.querySelector( '.spp-v2-admin' );
	var uid = 0;
	if ( ! root ) {
		return;
	}

	function directChildren( parent, selector ) {
		return Array.prototype.filter.call( parent.children, function ( child ) { return child.matches( selector ); } );
	}

	function escapeRegExp( value ) {
		return value.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
	}

	function refreshIds( scope ) {
		scope.querySelectorAll( 'input[id], textarea[id], select[id]' ).forEach( function ( field ) {
			var old = field.id;
			var id = 'spp-v2-dynamic-' + Date.now() + '-' + ( ++uid );
			field.id = id;
			var label = scope.querySelector( 'label[for="' + old + '"]' );
			if ( label ) {
				label.setAttribute( 'for', id );
			}
		} );
	}

	function reindexRepeater( repeater ) {
		var base = repeater.getAttribute( 'data-base' );
		var rowsWrap = directChildren( repeater, '.spp-v2-repeater__rows' )[ 0 ];
		if ( ! rowsWrap ) {
			return;
		}

		directChildren( rowsWrap, '.spp-v2-repeater__row' ).forEach( function ( row, index ) {
			var oldPrefix = row.getAttribute( 'data-prefix' );
			var newPrefix = base + '[' + index + ']';
			if ( oldPrefix && oldPrefix !== newPrefix ) {
				var matcher = new RegExp( '^' + escapeRegExp( oldPrefix ) );
				row.querySelectorAll( '[name]' ).forEach( function ( field ) {
					field.name = field.name.replace( matcher, newPrefix );
				} );
				row.querySelectorAll( '[data-base]' ).forEach( function ( nested ) {
					nested.setAttribute( 'data-base', nested.getAttribute( 'data-base' ).replace( matcher, newPrefix ) );
				} );
				row.querySelectorAll( '[data-prefix]' ).forEach( function ( nestedRow ) {
					nestedRow.setAttribute( 'data-prefix', nestedRow.getAttribute( 'data-prefix' ).replace( matcher, newPrefix ) );
				} );
				row.setAttribute( 'data-prefix', newPrefix );
			}
			directChildren( row.querySelector( '.spp-v2-repeater__body' ) || row, '.spp-v2-repeater' ).forEach( reindexRepeater );
		} );
	}

	function initSortable( scope ) {
		if ( ! $ || ! $.fn.sortable ) {
			return;
		}
		$( scope ).find( '.spp-v2-repeater__rows' ).addBack( '.spp-v2-repeater__rows' ).each( function () {
			var rows = $( this );
			if ( rows.data( 'spp-v2-sortable' ) ) {
				return;
			}
			rows.data( 'spp-v2-sortable', true ).sortable( {
				items: '> .spp-v2-repeater__row',
				handle: '> .spp-v2-repeater__rowbar .spp-v2-repeater__grip',
				placeholder: 'spp-v2-repeater__placeholder',
				forcePlaceholderSize: true,
				update: function () { reindexRepeater( rows.closest( '[data-spp-v2-repeater]' )[ 0 ] ); }
			} );
		} );
	}

	function initEditors( scope ) {
		if ( ! window.wp || ! wp.editor || ! wp.editor.initialize ) {
			return;
		}
		scope.querySelectorAll( 'textarea.spp-v2-rich:not([data-editor-ready])' ).forEach( function ( textarea ) {
			if ( textarea.closest( 'template' ) || textarea.offsetParent === null ) {
				return;
			}
			textarea.setAttribute( 'data-editor-ready', '1' );
			wp.editor.initialize( textarea.id, {
				tinymce: { wpautop: true, toolbar1: 'bold italic bullist numlist link unlink removeformat', toolbar2: '' },
				quicktags: true,
				mediaButtons: false
			} );
		} );
	}

	function syncEditors() {
		if ( window.tinyMCE ) {
			window.tinyMCE.triggerSave();
		}
	}

	function conditions( scope ) {
		( scope || root ).querySelectorAll( '[data-spp-v2-show-if]' ).forEach( function ( field ) {
			var parts = field.getAttribute( 'data-spp-v2-show-if' ).split( ':' );
			var panel = field.closest( '.spp-v2-admin__panel' );
			var source = panel && panel.querySelector( '[name$="[' + parts[ 0 ] + ']"]' );
			var current = source && source.type === 'checkbox' ? ( source.checked ? '1' : '' ) : ( source ? source.value : '' );
			field.hidden = current !== ( parts[ 1 ] || '1' );
		} );
	}

	root.addEventListener( 'click', function ( event ) {
		var tab = event.target.closest( '[data-spp-v2-tab]' );
		if ( tab ) {
			var id = tab.getAttribute( 'data-spp-v2-tab' );
			root.querySelectorAll( '[data-spp-v2-tab]' ).forEach( function ( item ) {
				var active = item === tab;
				item.classList.toggle( 'is-active', active );
				item.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			} );
			root.querySelectorAll( '[data-spp-v2-panel]' ).forEach( function ( panel ) {
				var active = panel.getAttribute( 'data-spp-v2-panel' ) === id;
				panel.classList.toggle( 'is-active', active );
				panel.hidden = ! active;
			} );
			setTimeout( function () { initEditors( root.querySelector( '[data-spp-v2-panel="' + id + '"]' ) ); }, 30 );
			return;
		}

		var add = event.target.closest( '.spp-v2-repeater__add' );
		if ( add ) {
			var repeater = add.closest( '[data-spp-v2-repeater]' );
			var rows = directChildren( repeater, '.spp-v2-repeater__rows' )[ 0 ];
			var template = directChildren( repeater, '.spp-v2-repeater__template' )[ 0 ];
			var currentRows = directChildren( rows, '.spp-v2-repeater__row' );
			var max = parseInt( repeater.getAttribute( 'data-max' ), 10 ) || 20;
			if ( currentRows.length >= max ) {
				window.alert( 'حداکثر ' + max + ' ردیف مجاز است.' );
				return;
			}
			var tokenMatch = template.innerHTML.match( /__spp_v2_\d+__/ );
			var html = tokenMatch ? template.innerHTML.split( tokenMatch[ 0 ] ).join( String( currentRows.length ) ) : template.innerHTML;
			rows.insertAdjacentHTML( 'beforeend', html );
			var row = rows.lastElementChild;
			refreshIds( row );
			reindexRepeater( repeater );
			initSortable( row );
			conditions( row );
			initEditors( row );
			row.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			return;
		}

		var remove = event.target.closest( '.spp-v2-repeater__remove' );
		if ( remove ) {
			var removeRep = remove.closest( '[data-spp-v2-repeater]' );
			remove.closest( '.spp-v2-repeater__row' ).remove();
			reindexRepeater( removeRep );
			return;
		}

		var duplicate = event.target.closest( '.spp-v2-repeater__duplicate' );
		if ( duplicate ) {
			syncEditors();
			var original = duplicate.closest( '.spp-v2-repeater__row' );
			var dupRep = duplicate.closest( '[data-spp-v2-repeater]' );
			var dupRows = directChildren( dupRep, '.spp-v2-repeater__rows' )[ 0 ];
			if ( directChildren( dupRows, '.spp-v2-repeater__row' ).length >= ( parseInt( dupRep.getAttribute( 'data-max' ), 10 ) || 20 ) ) {
				return;
			}
			var clone = original.cloneNode( true );
			clone.querySelectorAll( '.wp-editor-wrap' ).forEach( function ( wrap ) {
				var area = wrap.querySelector( 'textarea' );
				if ( area ) {
					area.className = 'spp-v2-rich';
					area.removeAttribute( 'data-editor-ready' );
					wrap.replaceWith( area );
				}
			} );
			dupRows.insertBefore( clone, original.nextSibling );
			refreshIds( clone );
			reindexRepeater( dupRep );
			initSortable( clone );
			initEditors( clone );
			return;
		}

		var collapse = event.target.closest( '.spp-v2-repeater__collapse' );
		if ( collapse ) {
			var rowBox = collapse.closest( '.spp-v2-repeater__row' );
			var collapsed = rowBox.classList.toggle( 'is-collapsed' );
			collapse.setAttribute( 'aria-expanded', collapsed ? 'false' : 'true' );
			collapse.textContent = collapsed ? 'باز کردن' : 'جمع کردن';
			return;
		}

		var pick = event.target.closest( '.spp-v2-media__pick' );
		if ( pick && window.wp && wp.media ) {
			var media = pick.closest( '.spp-v2-media' );
			var frame = wp.media( { title: 'انتخاب تصویر', button: { text: 'استفاده از تصویر' }, library: { type: 'image' }, multiple: false } );
			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				var source = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
				media.querySelector( '.spp-v2-media__id' ).value = attachment.id;
				media.querySelector( '.spp-v2-media__preview' ).innerHTML = '<img src="' + source + '" alt="">';
				media.classList.add( 'has-image' );
			} );
			frame.open();
			return;
		}

		var clear = event.target.closest( '.spp-v2-media__remove' );
		if ( clear ) {
			var mediaBox = clear.closest( '.spp-v2-media' );
			mediaBox.querySelector( '.spp-v2-media__id' ).value = '';
			mediaBox.querySelector( '.spp-v2-media__preview' ).innerHTML = '';
			mediaBox.classList.remove( 'has-image' );
			return;
		}

		var videoPick = event.target.closest( '.spp-v2-video__pick' );
		if ( videoPick && window.wp && wp.media ) {
			var video = videoPick.closest( '.spp-v2-video' );
			var videoFrame = wp.media( { title: 'انتخاب ویدیوی تجربه', button: { text: 'استفاده از ویدیو' }, library: { type: 'video' }, multiple: false } );
			videoFrame.on( 'select', function () {
				var attachment = videoFrame.state().get( 'selection' ).first().toJSON();
				video.querySelector( '.spp-v2-video__id' ).value = attachment.id;
				video.querySelector( '.spp-v2-video__preview' ).innerHTML = '<video src="' + attachment.url + '" controls preload="metadata"></video>';
				video.classList.add( 'has-video' );
			} );
			videoFrame.open();
			return;
		}

		var videoClear = event.target.closest( '.spp-v2-video__remove' );
		if ( videoClear ) {
			var videoBox = videoClear.closest( '.spp-v2-video' );
			videoBox.querySelector( '.spp-v2-video__id' ).value = '';
			videoBox.querySelector( '.spp-v2-video__preview' ).innerHTML = '';
			videoBox.classList.remove( 'has-video' );
		}
	} );

	root.addEventListener( 'change', function () { conditions( root ); } );
	var form = root.closest( 'form' );
	if ( form ) {
		form.addEventListener( 'submit', syncEditors );
	}
	initSortable( root );
	conditions( root );
	initEditors( root.querySelector( '.spp-v2-admin__panel.is-active' ) || root );
}( window, document, window.jQuery ) );
