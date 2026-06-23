import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Popover from './Popover';

/* ---------------------------------------------------------------------------
 * Colour maths (hex <-> rgb <-> hsv). Hex is the stored format: 6-digit when
 * opaque, 8-digit (#rrggbbaa) when an alpha < 1 is chosen.
 * ------------------------------------------------------------------------- */
const clamp = ( n, min, max ) => Math.min( max, Math.max( min, n ) );
const h2 = ( n ) => clamp( Math.round( n ), 0, 255 ).toString( 16 ).padStart( 2, '0' );

function hexToRgb( hex ) {
	let h = String( hex || '' ).replace( '#', '' ).trim();
	if ( /^[0-9a-f]{3}$/i.test( h ) ) { h = h.split( '' ).map( ( c ) => c + c ).join( '' ); }
	if ( /^[0-9a-f]{6}$/i.test( h ) ) { h += 'ff'; }
	if ( ! /^[0-9a-f]{8}$/i.test( h ) ) { return null; }
	return {
		r: parseInt( h.slice( 0, 2 ), 16 ),
		g: parseInt( h.slice( 2, 4 ), 16 ),
		b: parseInt( h.slice( 4, 6 ), 16 ),
		a: parseInt( h.slice( 6, 8 ), 16 ) / 255,
	};
}

function rgbToHex( r, g, b, a ) {
	let out = '#' + h2( r ) + h2( g ) + h2( b );
	if ( a != null && a < 1 ) { out += h2( a * 255 ); }
	return out;
}

function rgbToHsv( r, g, b ) {
	r /= 255; g /= 255; b /= 255;
	const max = Math.max( r, g, b ), min = Math.min( r, g, b ), d = max - min;
	let hue = 0;
	if ( d ) {
		if ( max === r ) { hue = ( ( g - b ) / d ) % 6; }
		else if ( max === g ) { hue = ( b - r ) / d + 2; }
		else { hue = ( r - g ) / d + 4; }
		hue *= 60;
		if ( hue < 0 ) { hue += 360; }
	}
	return { h: hue, s: max === 0 ? 0 : d / max, v: max };
}

function hsvToRgb( h, s, v ) {
	const c = v * s, x = c * ( 1 - Math.abs( ( ( h / 60 ) % 2 ) - 1 ) ), m = v - c;
	let r = 0, g = 0, b = 0;
	if ( h < 60 ) { r = c; g = x; }
	else if ( h < 120 ) { r = x; g = c; }
	else if ( h < 180 ) { g = c; b = x; }
	else if ( h < 240 ) { g = x; b = c; }
	else if ( h < 300 ) { r = x; b = c; }
	else { r = c; b = x; }
	return { r: ( r + m ) * 255, g: ( g + m ) * 255, b: ( b + m ) * 255 };
}

/**
 * Debug: log the rule this colour will produce in the generated stylesheet.
 *
 * Mirrors the substitution Form_Builder::generate_form_css() performs, so what
 * is logged is what actually ships: {{WRAPPER}} -> .fcf7b-field-{id},
 * {{VALUE}} -> the colour.
 *
 * @param {Object} ctrl  The control config (carries `selectors` / `selector`).
 * @param {Object} field The field being edited (for its id).
 * @param {string} value The new colour, '' when cleared.
 */

export default function ColorControl( { ctrl, field, value = '', onChange, onGestureStart, onGestureEnd } ) {
	const [ hsv, setHsv ] = useState( { h: 0, s: 0, v: 0 } );
	const [ alpha, setAlpha ] = useState( 1 );
	const [ hexInput, setHexInput ] = useState( value );
	const area = useRef( null );
	const editing = useRef( false );

	const validHex = /^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i.test( value ) ? value : '';

	// Sync internal HSV from the incoming value when the picker opens.
	const onOpenChange = ( isOpen ) => {
		if ( ! isOpen ) { return; }
		const rgb = hexToRgb( validHex || '#000000' );
		if ( rgb ) { setHsv( rgbToHsv( rgb.r, rgb.g, rgb.b ) ); setAlpha( rgb.a ); }
	};

	// Keep the hex field in sync with the value unless it is being typed in.
	useEffect( () => { if ( ! editing.current ) { setHexInput( value || '' ); } }, [ value ] );

	// Single exit point for every way this control changes — drag, hex input,
	// reset, clear — so the log can never miss one.
	const emitChange = ( next ) => {
		onChange( next );
	};

	const emit = ( nh, a ) => {
		const { r, g, b } = hsvToRgb( nh.h, nh.s, nh.v );
		emitChange( rgbToHex( r, g, b, a ) );
	};
	const setSV = ( s, v ) => { const nh = { ...hsv, s, v }; setHsv( nh ); emit( nh, alpha ); };
	const setHue = ( h ) => { const nh = { ...hsv, h }; setHsv( nh ); emit( nh, alpha ); };
	const setAlphaV = ( a ) => { setAlpha( a ); emit( hsv, a ); };

	const applyHex = ( raw ) => {
		const hex = raw.startsWith( '#' ) ? raw : '#' + raw;
		const rgb = hexToRgb( hex );
		if ( rgb ) { setHsv( rgbToHsv( rgb.r, rgb.g, rgb.b ) ); setAlpha( rgb.a ); emitChange( rgbToHex( rgb.r, rgb.g, rgb.b, rgb.a ) ); }
	};

	// Pointer-drag helper: maps the pointer to 0..1 fractions within a target.
	// The whole drag is wrapped in a gesture so it collapses to one undo step.
	const startDrag = ( el, onFrac ) => ( e ) => {
		e.preventDefault();
		const rect = el.getBoundingClientRect();
		if ( onGestureStart ) { onGestureStart(); }
		const move = ( ev ) => {
			onFrac(
				clamp( ( ev.clientX - rect.left ) / rect.width, 0, 1 ),
				clamp( ( ev.clientY - rect.top ) / rect.height, 0, 1 )
			);
		};
		move( e );
		const up = () => {
			document.removeEventListener( 'pointermove', move );
			document.removeEventListener( 'pointerup', up );
			if ( onGestureEnd ) { onGestureEnd(); }
		};
		document.addEventListener( 'pointermove', move );
		document.addEventListener( 'pointerup', up );
	};

	const pickEyedropper = () => {
		if ( ! window.EyeDropper ) { return; }
		try {
			new window.EyeDropper().open().then( ( res ) => res && applyHex( res.sRGBHex ) ).catch( () => {} );
		} catch ( e ) { /* cancelled / unsupported */ }
	};

	const { r, g, b } = hsvToRgb( hsv.h, hsv.s, hsv.v );
	const solid = rgbToHex( r, g, b );

	return (
		<Popover
			className="fcf7b-color"
			triggerClassName="fcf7b-color-trigger"
			popClassName="fcf7b-color-pop"
			onOpenChange={ onOpenChange }
			trigger={ (
				<>
					<span className={ `fcf7b-color-swatch${ validHex ? '' : ' is-empty' }` } style={ validHex ? { background: validHex } : null } />
					<span className="fcf7b-color-value">{ validHex ? validHex.toUpperCase() : __( 'None', 'compactform' ) }</span>
				</>
			) }
		>
			<div className="fcf7b-pop-head">
						<span>{ __( 'Color Picker', 'compactform' ) }</span>
						<div className="fcf7b-pop-tools">
							<button type="button" title={ __( 'Reset', 'compactform' ) } onClick={ () => { emitChange( '' ); setHsv( { h: 0, s: 0, v: 0 } ); setAlpha( 1 ); } }><i className="ri-refresh-line" /></button>
							{ window.EyeDropper ? <button type="button" title={ __( 'Pick from screen', 'compactform' ) } onClick={ pickEyedropper }><i className="ri-sip-line" /></button> : null }
						</div>
					</div>

					<div
						className="fcf7b-cp-area"
						ref={ area }
						style={ { background: `hsl(${ hsv.h } 100% 50%)` } }
						onPointerDown={ ( e ) => startDrag( area.current, ( x, y ) => setSV( x, 1 - y ) )( e ) }
					>
						<div className="fcf7b-cp-area-white" />
						<div className="fcf7b-cp-area-black" />
						<span className="fcf7b-cp-area-handle" style={ { left: hsv.s * 100 + '%', top: ( 1 - hsv.v ) * 100 + '%' } } />
					</div>

					<div className="fcf7b-cp-slider fcf7b-cp-hue" onPointerDown={ ( e ) => startDrag( e.currentTarget, ( x ) => setHue( x * 360 ) )( e ) }>
						<span className="fcf7b-cp-slider-handle" style={ { left: ( hsv.h / 360 ) * 100 + '%' } } />
					</div>

					<div className="fcf7b-cp-slider fcf7b-cp-alpha" style={ { '--cp-color': solid } } onPointerDown={ ( e ) => startDrag( e.currentTarget, ( x ) => setAlphaV( x ) )( e ) }>
						<span className="fcf7b-cp-slider-handle" style={ { left: alpha * 100 + '%' } } />
					</div>

					<div className="fcf7b-cp-foot">
						<input
							className="fcf7b-cp-hex"
							value={ hexInput }
							placeholder="#rrggbb"
							onFocus={ () => { editing.current = true; } }
							onBlur={ () => { editing.current = false; setHexInput( value || '' ); } }
							onChange={ ( e ) => { setHexInput( e.target.value ); applyHex( e.target.value ); } }
						/>
						<span className="fcf7b-cp-fmt">HEX</span>
						<button type="button" className="fcf7b-cp-none" onClick={ () => emitChange( '' ) }>{ __( 'Clear', 'compactform' ) }</button>
			</div>
		</Popover>
	);
}
