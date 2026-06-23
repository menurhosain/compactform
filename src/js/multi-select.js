
( function () {
	'use strict';

	var i18n = window.FCF7MultiSelect || {};

	var TEXT = {
		placeholder: i18n.placeholder || 'Select options…',
		remove: i18n.remove || 'Remove',
	};
	
	var uid = 0;

	function choicesOf( select ) {
		return Array.prototype.filter.call( select.options, function ( option ) {
			return '' !== option.value;
		} );
	}

	function enhance( select ) {
		if ( select.fcf7ms ) {
			return;
		}

		var wrap = document.createElement( 'div' );
		wrap.className = 'fcf7-ms';

		var panelId = 'fcf7-ms-panel-' + ( ++uid );

		var trigger = document.createElement( 'div' );

		trigger.className = select.className + ' fcf7-ms-trigger';
		trigger.setAttribute( 'role', 'combobox' );
		trigger.setAttribute( 'tabindex', '0' );
		trigger.setAttribute( 'aria-haspopup', 'listbox' );
		trigger.setAttribute( 'aria-expanded', 'false' );
		trigger.setAttribute( 'aria-controls', panelId );

		var chips = document.createElement( 'span' );
		chips.className = 'fcf7-ms-chips';

		var caret = document.createElement( 'span' );
		caret.className = 'fcf7-ms-caret';
		caret.setAttribute( 'aria-hidden', 'true' );

		trigger.appendChild( chips );
		trigger.appendChild( caret );

		var panel = document.createElement( 'div' );
		panel.className = 'fcf7-ms-panel';
		panel.id = panelId;
		panel.setAttribute( 'role', 'listbox' );
		panel.setAttribute( 'aria-multiselectable', 'true' );

		var boxes = [];

		choicesOf( select ).forEach( function ( option ) {
			var row = document.createElement( 'label' );
			row.className = 'fcf7-ms-option';

			var box = document.createElement( 'input' );
			box.type = 'checkbox';
			box.checked = option.selected;
			box.removeAttribute( 'name' );

			var label = document.createElement( 'span' );
			label.textContent = option.textContent;

			row.appendChild( box );
			row.appendChild( label );
			panel.appendChild( row );

			box.addEventListener( 'change', function () {
				option.selected = box.checked;
				commit();
			} );

			boxes.push( { box: box, option: option } );
		} );

		var announcing = false;

		function commit() {
			paint();

			announcing = true;
			select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			announcing = false;
		}

		function paint() {
			var selected = choicesOf( select ).filter( function ( option ) {
				return option.selected;
			} );

			chips.textContent = '';

			if ( ! selected.length ) {
				var empty = document.createElement( 'span' );
				empty.className = 'fcf7-ms-placeholder';
				empty.textContent = TEXT.placeholder;
				chips.appendChild( empty );
			}

			selected.forEach( function ( option ) {
				var chip = document.createElement( 'span' );
				chip.className = 'fcf7-ms-chip';
				chip.textContent = option.textContent;

				var remove = document.createElement( 'button' );
				remove.type = 'button';
				remove.className = 'fcf7-ms-chip-x';
				remove.setAttribute( 'aria-label', TEXT.remove + ': ' + option.textContent );
				remove.textContent = '×';

				remove.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					event.stopPropagation();
					option.selected = false;
					syncBoxes();
					commit();
				} );

				chip.appendChild( remove );
				chips.appendChild( chip );
			} );

			trigger.classList.toggle( 'wpcf7-not-valid', select.classList.contains( 'wpcf7-not-valid' ) );
		}

		function syncBoxes() {
			boxes.forEach( function ( pair ) {
				pair.box.checked = pair.option.selected;
			} );
		}

		function open() {
			wrap.classList.add( 'is-open' );
			trigger.setAttribute( 'aria-expanded', 'true' );
		}

		function close() {
			wrap.classList.remove( 'is-open' );
			trigger.setAttribute( 'aria-expanded', 'false' );
		}

		function toggle() {
			if ( wrap.classList.contains( 'is-open' ) ) {
				close();
			} else {
				open();
			}
		}

		trigger.addEventListener( 'click', toggle );

		trigger.addEventListener( 'keydown', function ( event ) {
			if ( 'Enter' === event.key || ' ' === event.key || 'Spacebar' === event.key ) {
				event.preventDefault();
				toggle();
			} else if ( 'ArrowDown' === event.key ) {
				event.preventDefault();
				open();
			}
		} );

		wrap.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				close();
				trigger.focus();
			}
		} );

		document.addEventListener( 'click', function ( event ) {
			if ( ! wrap.contains( event.target ) ) {
				close();
			}
		} );

		select.addEventListener( 'change', function () {
			if ( ! announcing ) {
				syncBoxes();
				paint();
			}
		} );

		var form = select.closest( 'form' );
		if ( form ) {
			form.addEventListener( 'reset', function () {
				window.setTimeout( function () {
					syncBoxes();
					paint();
				}, 0 );
			} );
		}

		select.parentNode.insertBefore( wrap, select );
		wrap.appendChild( select );
		wrap.appendChild( trigger );
		wrap.appendChild( panel );

		select.fcf7ms = { paint: paint, sync: syncBoxes, close: close };
		paint();
	}

	function init( root ) {
		var scope = root && root.querySelectorAll ? root : document;

		Array.prototype.forEach.call(
			scope.querySelectorAll( 'select[multiple]' ),
			enhance
		);
	}

	function refresh( root ) {
		var scope = root && root.querySelectorAll ? root : document;

		Array.prototype.forEach.call(
			scope.querySelectorAll( 'select[multiple]' ),
			function ( select ) {
				if ( select.fcf7ms ) {
					select.fcf7ms.sync();
					select.fcf7ms.paint();
				}
			}
		);
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			init( document );
		} );
	} else {
		init( document );
	}

	document.addEventListener( 'fcf7:repeater-row-added', function ( event ) {
		init( event.detail && event.detail.row ? event.detail.row : document );
	} );

	[ 'wpcf7submit', 'wpcf7invalid', 'wpcf7mailsent' ].forEach( function ( name ) {
		document.addEventListener( name, function ( event ) {
			init( event.target );
			refresh( event.target );
		} );
	} );
} )();
