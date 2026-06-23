
( function () {
	'use strict';

	function has( list, v ) {
		for ( var i = 0; i < list.length; i++ ) { if ( list[ i ] === v ) { return true; } }
		return false;
	}
	function some( list, fn ) {
		for ( var i = 0; i < list.length; i++ ) { if ( fn( list[ i ] ) ) { return true; } }
		return false;
	}

	var OPS = {
		is: function ( a, v ) { return has( a, v ); },
		is_not: function ( a, v ) { return ! has( a, v ); },
		contains: function ( a, v ) { return v !== '' && some( a, function ( x ) { return x.indexOf( v ) !== -1; } ); },
		not_contains: function ( a, v ) { return ! ( v !== '' && some( a, function ( x ) { return x.indexOf( v ) !== -1; } ) ); },
		gt: function ( a, v ) { return some( a, function ( x ) { return x !== '' && ! isNaN( x ) && ! isNaN( v ) && parseFloat( x ) > parseFloat( v ); } ); },
		lt: function ( a, v ) { return some( a, function ( x ) { return x !== '' && ! isNaN( x ) && ! isNaN( v ) && parseFloat( x ) < parseFloat( v ); } ); },
		empty: function ( a ) { return a.join( '' ).trim() === ''; },
		filled: function ( a ) { return a.join( '' ).trim() !== ''; }
	};

	function values( form, name ) {
		var out = [];
		var nodes = form.querySelectorAll( '[name="' + name + '"], [name="' + name + '[]"]' );
		Array.prototype.forEach.call( nodes, function ( el ) {
			if ( el.type === 'checkbox' || el.type === 'radio' ) {
				if ( el.checked ) { out.push( el.value ); }
			} else {
				out.push( el.value );
			}
		} );
		return out;
	}

	function evaluate( form ) {
		var wraps = form.querySelectorAll( '.fcf7b-field-wrap[data-fcf7-cond]' );
		Array.prototype.forEach.call( wraps, function ( wrap ) {
			var rule;
			try { rule = JSON.parse( wrap.getAttribute( 'data-fcf7-cond' ) ); } catch ( e ) { return; }
			if ( ! rule || ! rule.enabled || ! rule.field ) { return; }

			var fn = OPS[ rule.operator ] || OPS.is;
			var passes = fn( values( form, rule.field ), String( rule.value == null ? '' : rule.value ) );
			var visible = rule.action === 'hide' ? ! passes : passes;

			wrap.classList.toggle( 'fcf7b-cond-hidden', ! visible );
		} );
	}

	function init( form ) {
		if ( ! form.querySelector( '.fcf7b-field-wrap[data-fcf7-cond]' ) ) { return; }
		var run = function () { evaluate( form ); };
		form.addEventListener( 'change', run );
		form.addEventListener( 'input', run );
		form.addEventListener( 'keyup', run );
		run();
	}

	function boot() {
		var forms = document.querySelectorAll( 'form.wpcf7-form' );
		Array.prototype.forEach.call( forms, init );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
	document.addEventListener( 'wpcf7submit', boot );
	document.addEventListener( 'wpcf7reset', boot );
} )();
