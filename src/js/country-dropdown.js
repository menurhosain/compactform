( function ( $ ) {
	'use strict';

	var cfg = window.FCF7Country || {};
	var statesData = null;
	var statesPromise = null;

	function loadStates() {
		if ( statesData ) {
			return Promise.resolve( statesData );
		}
		if ( ! statesPromise ) {
			statesPromise = fetch( cfg.statesUrl )
				.then( function ( r ) { return r.json(); } )
				.then( function ( d ) { statesData = d || {}; return statesData; } )
				.catch( function () { statesData = {}; return statesData; } );
		}
		return statesPromise;
	}

	function escapeHtml( s ) {
		return String( s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	function fetchIpCountry( cb ) {
		if ( ! window.fetch ) {
			return cb( null );
		}
		fetch( 'https://ipapi.co/country/' )
			.then( function ( r ) { return r.text(); } )
			.then( function ( t ) {
				t = ( t || '' ).trim().toLowerCase();
				cb( /^[a-z]{2}$/.test( t ) ? t : null );
			} )
			.catch( function () { cb( null ); } );
	}

	function flagOptions( $el ) {
		var only = $el.data( 'only-countries' );
		var def = ( $el.data( 'default' ) || '' ).toString();
		var opts = { responsiveDropdown: true, preferredCountries: [] };
		if ( Array.isArray( only ) && only.length ) {
			opts.onlyCountries = only;
		}
		if ( def ) {
			opts.defaultCountry = def;
		}
		return opts;
	}

	function neutralStart( $inp ) {
		$inp.val( '' ).attr( 'placeholder', cfg.i18n.selectCountry );
		$inp.closest( '.country-select' ).find( '.selected-flag .flag' ).attr( 'class', 'flag' );
	}

	function applyValueFormat( $inp ) {
		if ( 'code' !== $inp.data( 'value-format' ) ) {
			return;
		}
		var d = $inp.countrySelect( 'getSelectedCountryData' ) || {};
		if ( d.iso2 ) {
			$inp.val( d.iso2.toUpperCase() );
		}
	}

	/* -------- Flag dropdown mode -------- */
	function initFlagField() {
		$( 'input.fcf7-country-dropdown' ).each( function () {
			var $inp = $( this );
			if ( $inp.data( 'fcf7-init' ) ) {
				return;
			}
			$inp.data( 'fcf7-init', 1 );

			$inp.countrySelect( flagOptions( $inp ) );
			applyValueFormat( $inp );
			$inp.on( 'change blur', function () { applyValueFormat( $inp ); } );

			if ( $inp.data( 'auto-complete' ) ) {
				fetchIpCountry( function ( code ) {
					if ( code ) {
						$inp.countrySelect( 'selectCountry', code );
						applyValueFormat( $inp );
					}
				} );
			} else if ( ! $inp.data( 'default' ) ) {
				neutralStart( $inp );
			}
		} );
	}

	function initDynamicField() {
		$( '.fcf7-country-dynamic' ).each( function () {
			var $wrap = $( this );
			if ( $wrap.data( 'fcf7-init' ) ) {
				return;
			}
			$wrap.data( 'fcf7-init', 1 );

			var $flag = $wrap.find( '.fcf7-cd-country-flag' );
			var $s = $wrap.find( '.fcf7-cd-state' );
			var $ci = $wrap.find( '.fcf7-cd-city' );
			var $val = $wrap.find( '.fcf7-cd-value' );

			$flag.countrySelect( flagOptions( $wrap ) );

			if ( ! $wrap.data( 'auto-complete' ) && ! $wrap.data( 'default' ) ) {
				neutralStart( $flag );
			}

			function selected() {
				return $flag.countrySelect( 'getSelectedCountryData' ) || {};
			}

			function updateVal() {
				var parts = [];
				var d = selected();
				if ( d.name && $flag.val() ) { parts.push( d.name ); }
				if ( $s.val() ) { parts.push( $s.val() ); }
				if ( $ci.val() ) { parts.push( $ci.val().trim() ); }
				$val.val( parts.join( ', ' ) );
			}

			function fillStates( list ) {
				$s.empty().append( '<option value="">' + escapeHtml( cfg.i18n.selectState ) + '</option>' );
				list.forEach( function ( st ) {
					$s.append( '<option value="' + escapeHtml( st.name ) + '">' + escapeHtml( st.name ) + '</option>' );
				} );
				$s.prop( 'disabled', list.length === 0 );
			}

			function setCity( enabled ) {
				$ci.prop( 'disabled', ! enabled );
				if ( ! enabled ) {
					$ci.val( '' );
				}
			}

			function onCountry() {
				setCity( false );
				fillStates( [] );
				updateVal();

				var iso = ( selected().iso2 || '' ).toUpperCase();
				if ( ! iso || ! $flag.val() ) {
					return;
				}
				loadStates().then( function ( data ) {
					var list = data[ iso ] || [];
					fillStates( list );
					if ( list.length === 0 ) {
						setCity( true );
					}
					updateVal();
				} );
			}

			$flag.on( 'change', onCountry );
			$wrap.on( 'click', '.country-list .country', function () {
				setTimeout( onCountry, 0 );
			} );
			$s.on( 'change', function () {
				setCity( !! $s.val() );
				updateVal();
			} );
			$ci.on( 'input', updateVal );

			if ( $wrap.data( 'auto-complete' ) && ! $wrap.data( 'default' ) ) {
				fetchIpCountry( function ( code ) {
					if ( code ) {
						$flag.countrySelect( 'selectCountry', code );
						onCountry();
					}
				} );
			} else if ( $wrap.data( 'default' ) ) {
				onCountry();
			}
		} );
	}

	function initAll() {
		initFlagField();
		initDynamicField();
	}

	$( initAll );
	document.addEventListener( 'wpcf7submit', initAll, false );

	document.addEventListener( 'fcf7:repeater-row-added', initAll, false );
} )( jQuery );
