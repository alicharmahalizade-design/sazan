/**
 * کنترل پنل سازان — رفتارهای سمت مرورگر.
 *
 * بدون وابستگی به jQuery.
 */
( function () {
	'use strict';

	var L = window.SazanSuite || {};

	function $( selector, scope ) {
		return ( scope || document ).querySelector( selector );
	}

	function $$( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	/* ------------------------------------------------------------ رنگ */

	function bindColors( scope ) {
		$$( '.szs-color__swatch', scope ).forEach( function ( swatch ) {
			if ( swatch.dataset.bound ) {
				return;
			}
			swatch.dataset.bound = '1';

			var hex = swatch.dataset.sync
				? document.getElementById( swatch.dataset.sync )
				: swatch.parentNode.querySelector( 'input[type="text"]' );

			if ( ! hex ) {
				return;
			}

			swatch.addEventListener( 'input', function () {
				hex.value = swatch.value;
				hex.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} );

			hex.addEventListener( 'input', function () {
				if ( /^#[0-9a-fA-F]{6}$/.test( hex.value ) ) {
					swatch.value = hex.value;
				}
			} );
		} );
	}

	/* ------------------------------------------------------------ کلید محرمانه */

	function bindSecrets() {
		$$( '.szs-secret__toggle' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var input = button.parentNode.querySelector( 'input' );
				if ( ! input ) {
					return;
				}
				var show = 'password' === input.type;
				input.type = show ? 'text' : 'password';
				button.querySelector( '.dashicons' ).className =
					'dashicons dashicons-' + ( show ? 'hidden' : 'visibility' );
			} );
		} );
	}

	/* ------------------------------------------------------------ فیلتر کاربران */

	function bindUserFilters() {
		$$( '.szs-users' ).forEach( function ( box ) {
			var search = $( '.szs-users__search', box );
			if ( ! search ) {
				return;
			}

			search.addEventListener( 'input', function () {
				var needle = search.value.trim().toLowerCase();
				$$( '.szs-users__item', box ).forEach( function ( item ) {
					var hay = ( item.dataset.search || '' ).toLowerCase();
					item.classList.toggle( 'is-hidden', !! needle && -1 === hay.indexOf( needle ) );
				} );
			} );
		} );
	}

	/* ------------------------------------------------------------ رسانه */

	function bindMedia( scope ) {
		$$( '.szs-image', scope ).forEach( function ( box ) {
			if ( box.dataset.bound ) {
				return;
			}
			box.dataset.bound = '1';

			var input = box.querySelector( 'input[type="hidden"]' );
			var preview = $( '.szs-image__preview', box );
			var frame = null;

			var pick = $( '.szs-image__pick', box );
			if ( pick ) {
				pick.addEventListener( 'click', function () {
					if ( ! window.wp || ! window.wp.media ) {
						return;
					}

					if ( ! frame ) {
						frame = window.wp.media( {
							title: L.pick || 'انتخاب تصویر',
							button: { text: L.use || 'استفاده از این تصویر' },
							library: { type: 'image' },
							multiple: false
						} );

						frame.on( 'select', function () {
							var att = frame.state().get( 'selection' ).first().toJSON();
							input.value = att.id;
							var url = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
							preview.innerHTML = '<img alt="">';
							preview.firstChild.src = url;
							markDirty();
						} );
					}

					frame.open();
				} );
			}

			var clear = $( '.szs-image__clear', box );
			if ( clear ) {
				clear.addEventListener( 'click', function () {
					input.value = '0';
					preview.innerHTML = '<span class="dashicons dashicons-format-image"></span>';
					markDirty();
				} );
			}
		} );
	}

	/* ------------------------------------------------------------ ردیف‌های شناسه/برچسب */

	function bindKvRows() {
		$$( '.szs-rows' ).forEach( function ( box ) {
			var body = $( '.szs-rows__body', box );
			var name = box.dataset.name;

			function reindex() {
				$$( '.szs-rows__row', body ).forEach( function ( row, index ) {
					$$( 'input', row ).forEach( function ( input ) {
						input.name = input.name.replace( /\[\d+\]\[/, '[' + index + '][' );
					} );
				} );
			}

			box.addEventListener( 'click', function ( event ) {
				var remove = event.target.closest( '.szs-rows__remove' );
				if ( remove ) {
					event.preventDefault();
					remove.closest( '.szs-rows__row' ).remove();
					reindex();
					markDirty();
					return;
				}

				if ( event.target.closest( '.szs-rows__add' ) ) {
					event.preventDefault();
					var index = $$( '.szs-rows__row', body ).length;
					var row = document.createElement( 'div' );
					row.className = 'szs-rows__row';
					row.innerHTML =
						'<input type="text" class="szs-input" dir="ltr" name="' + name + '[' + index + '][key]" value="" placeholder="stage_key">' +
						'<input type="text" class="szs-input" name="' + name + '[' + index + '][value]" value="">' +
						'<button type="button" class="szs-rows__remove" aria-label="حذف ردیف"><span class="dashicons dashicons-no-alt"></span></button>';
					body.appendChild( row );
					markDirty();
				}
			} );
		} );
	}

	/* ------------------------------------------------------------ ردیف‌های تکرارشونده */

	function bindRepeaters() {
		$$( '.szs-rep' ).forEach( function ( box ) {
			var body = $( '.szs-rep__body', box );
			var tpl = $( '.szs-rep__tpl', box );
			var max = parseInt( box.dataset.max, 10 ) || 0;
			var addButton = $( '.szs-rep__add', box );

			function refresh() {
				if ( ! addButton ) {
					return;
				}
				var count = $$( '.szs-rep__row', body ).length;
				addButton.disabled = max > 0 && count >= max;
			}

			box.addEventListener( 'click', function ( event ) {
				var remove = event.target.closest( '.szs-rep__remove' );
				if ( remove ) {
					event.preventDefault();
					remove.closest( '.szs-rep__row' ).remove();
					refresh();
					markDirty();
					return;
				}

				if ( event.target.closest( '.szs-rep__add' ) && tpl ) {
					event.preventDefault();

					// اندیس یکتا، تا حذف ردیف‌های میانی نام‌ها را خراب نکند.
					var index = Date.now() % 100000 + $$( '.szs-rep__row', body ).length;
					var html = tpl.innerHTML.split( '__i__' ).join( String( index ) );
					var holder = document.createElement( 'div' );
					holder.innerHTML = html;

					var row = holder.firstElementChild;
					body.appendChild( row );
					bindColors( row );
					bindMedia( row );
					refresh();
					markDirty();
				}
			} );

			refresh();
		} );
	}

	/* ------------------------------------------------------------ جست‌وجو */

	function bindSearch() {
		var input = $( '#szs-search' );
		var results = $( '#szs-search-results' );
		var indexNode = $( '#szs-search-index' );

		if ( ! input || ! results || ! indexNode ) {
			return;
		}

		var index = [];
		try {
			index = JSON.parse( indexNode.textContent ) || [];
		} catch ( e ) {
			index = [];
		}

		var emptyNote = $( '.szs-empty-search' );

		function filterCurrentCategory( needle ) {
			var anyVisible = false;

			$$( '.szs-card' ).forEach( function ( card ) {
				var visibleFields = 0;

				$$( '.szs-field', card ).forEach( function ( field ) {
					var hay = ( field.dataset.search || '' ).toLowerCase();
					var match = ! needle || -1 !== hay.indexOf( needle );
					field.classList.toggle( 'is-hidden', ! match );
					if ( match ) {
						visibleFields++;
					}
				} );

				var show = ! needle || visibleFields > 0;
				card.classList.toggle( 'is-hidden', ! show );

				if ( show ) {
					anyVisible = true;
				}
			} );

			if ( emptyNote ) {
				emptyNote.hidden = anyVisible || ! needle;
			}
		}

		function renderGlobal( needle ) {
			if ( ! needle ) {
				results.hidden = true;
				results.innerHTML = '';
				return;
			}

			var matches = index.filter( function ( item ) {
				return -1 !== ( item.l + ' ' + item.k + ' ' + item.sl + ' ' + item.cl ).toLowerCase().indexOf( needle );
			} ).slice( 0, 12 );

			if ( ! matches.length ) {
				results.innerHTML = '<p class="szs-search__empty">چیزی پیدا نشد.</p>';
				results.hidden = false;
				return;
			}

			results.innerHTML = matches.map( function ( item ) {
				return '<a href="' + item.u + '">' + escapeHtml( item.l ) +
					'<small>' + escapeHtml( item.cl ) + ' ← ' + escapeHtml( item.sl ) + '</small></a>';
			} ).join( '' );

			results.hidden = false;
		}

		input.addEventListener( 'input', function () {
			var needle = input.value.trim().toLowerCase();
			filterCurrentCategory( needle );
			renderGlobal( needle );
		} );

		document.addEventListener( 'click', function ( event ) {
			if ( ! event.target.closest( '.szs-search' ) ) {
				results.hidden = true;
			}
		} );
	}

	function escapeHtml( value ) {
		var div = document.createElement( 'div' );
		div.textContent = String( value == null ? '' : value );
		return div.innerHTML;
	}

	/* ------------------------------------------------------------ پیامک آزمایشی */

	function bindTesters() {
		$$( '.szs-tester' ).forEach( function ( box ) {
			var button = $( '.szs-tester__send', box );
			var input = $( '.szs-tester__input', box );
			var result = $( '.szs-tester__result', box );

			if ( ! button || ! input || ! L.ajaxUrl ) {
				return;
			}

			button.addEventListener( 'click', function () {
				var to = input.value.trim();

				if ( ! to ) {
					result.textContent = 'ابتدا شماره موبایل را وارد کنید.';
					result.className = 'szs-tester__result is-error';
					return;
				}

				button.disabled = true;
				result.textContent = 'در حال ارسال…';
				result.className = 'szs-tester__result';

				var body = new URLSearchParams( {
					action: box.dataset.action,
					nonce: box.dataset.nonce,
					to: to
				} );

				fetch( L.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: body.toString()
				} )
					.then( function ( response ) {
						return response.json();
					} )
					.then( function ( payload ) {
						var ok = payload && payload.success;
						var message = payload && payload.data && payload.data.msg;
						result.textContent = message || ( ok ? 'انجام شد.' : 'ارسال ناموفق بود.' );
						result.className = 'szs-tester__result ' + ( ok ? 'is-ok' : 'is-error' );
					} )
					.catch( function () {
						result.textContent = 'ارتباط با سرور برقرار نشد.';
						result.className = 'szs-tester__result is-error';
					} )
					.finally( function () {
						button.disabled = false;
					} );
			} );
		} );
	}

	/* ------------------------------------------------------------ هشدار تغییر ذخیره‌نشده */

	var dirty = false;

	function markDirty() {
		if ( dirty ) {
			return;
		}
		dirty = true;
		var bar = $( '.szs-savebar' );
		if ( bar ) {
			bar.classList.add( 'is-dirty' );
		}
	}

	function bindDirtyTracking() {
		var form = $( '#szs-form' );
		if ( ! form ) {
			return;
		}

		form.addEventListener( 'input', markDirty );
		form.addEventListener( 'change', markDirty );
		form.addEventListener( 'submit', function () {
			dirty = false;
		} );

		window.addEventListener( 'beforeunload', function ( event ) {
			if ( ! dirty ) {
				return undefined;
			}
			event.preventDefault();
			event.returnValue = L.unsaved || '';
			return event.returnValue;
		} );
	}

	/* ------------------------------------------------------------ راه‌اندازی */

	function boot() {
		bindColors( document );
		bindSecrets();
		bindUserFilters();
		bindMedia( document );
		bindKvRows();
		bindRepeaters();
		bindSearch();
		bindTesters();
		bindDirtyTracking();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
