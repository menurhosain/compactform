
( function () {
	'use strict';

	function toFile( canvas, fieldName ) {
		return new Promise( function ( resolve ) {
			canvas.toBlob( function ( blob ) {
				resolve( blob ? new File( [ blob ], 'signature-' + fieldName + '.png', { type: 'image/png' } ) : null );
			}, 'image/png' );
		} );
	}

	function resize( canvas, signaturePad ) {
		var ratio = Math.max( window.devicePixelRatio || 1, 1 );
		var width = Math.round( canvas.offsetWidth * ratio );
		var height = Math.round( canvas.offsetHeight * ratio );

		if ( ! width || ! height ) { return false; }
		if ( canvas.width === width && canvas.height === height ) { return false; }

		var data = signaturePad ? signaturePad.toData() : null;

		canvas.width = width;
		canvas.height = height;
		canvas.getContext( '2d' ).scale( ratio, ratio );

		if ( signaturePad ) {
			signaturePad.clear();
			if ( data && data.length ) { signaturePad.fromData( data ); }
		}

		return !! ( data && data.length );
	}

	function setup( pad ) {
		if ( pad.fcf7Signature || typeof SignaturePad === 'undefined' ) { return; }

		var canvas = pad.querySelector( 'canvas' );
		var wrap = pad.closest( '.wpcf7-form-control-wrap' );
		var input = wrap ? wrap.querySelector( '.fcf7-signature-file' ) : null;
		var clearBtn = wrap ? wrap.querySelector( '.fcf7-signature-clear' ) : null;
		if ( ! canvas || ! input ) { return; }

		var field = pad.getAttribute( 'data-field' ) || 'signature';

		resize( canvas, null );

		var signaturePad = new SignaturePad( canvas, {
			backgroundColor: pad.getAttribute( 'data-bg-color' ) || '#bceeff',
			penColor: pad.getAttribute( 'data-pen-color' ) || '#000000',
		} );
		pad.fcf7Signature = signaturePad;

		function sync() {
			toFile( canvas, field ).then( function ( file ) {
				if ( ! file ) { return; }
				var list = new DataTransfer();
				list.items.add( file );
				input.files = list.files;
			} );
		}

		input.addEventListener( 'click', function ( e ) {
			e.preventDefault();
		} );
		pad.addEventListener( 'click', function ( e ) {
			e.preventDefault();
		} );

		signaturePad.addEventListener( 'endStroke', sync );

		if ( clearBtn ) {
			clearBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				signaturePad.clear();
				input.value = '';
			} );
		}

		var onResize = function () {
			if ( resize( canvas, signaturePad ) ) { sync(); }
		};

		if ( typeof ResizeObserver !== 'undefined' ) {
			new ResizeObserver( onResize ).observe( canvas );
		} else {
			window.addEventListener( 'resize', onResize );
		}
	}

	function boot( root ) {
		var pads = ( root || document ).querySelectorAll( '.fcf7-signature-pad' );
		Array.prototype.forEach.call( pads, setup );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () { boot(); } );
	} else {
		boot();
	}

	document.addEventListener( 'wpcf7submit', function () { boot(); } );
	
	document.addEventListener( 'fcf7:repeater-row-added', function ( e ) { boot( e.detail.row ); } );
	
	document.addEventListener( 'wpcf7mailsent', function ( event ) {
		var pads = event.target.querySelectorAll( '.fcf7-signature-pad' );
		Array.prototype.forEach.call( pads, function ( pad ) {
			if ( pad.fcf7Signature ) { pad.fcf7Signature.clear(); }
			var wrap = pad.closest( '.wpcf7-form-control-wrap' );
			var input = wrap ? wrap.querySelector( '.fcf7-signature-file' ) : null;
			if ( input ) { input.value = ''; }
		} );
	} );
} )();
