
import { BY_TYPE, DEVICES, deviceChain, isEmptyValue } from './shared';
import { meetsCondition } from './condition';

const cssSafe = ( v ) => ( v === null || v === undefined )
	? ''
	: String( v ).replace( /[{};]|[\r\n]/g, '' ).trim();

const cssNumber = ( v, max = 9999 ) => {
	if ( v === '' || v === null || v === undefined || isNaN( v ) ) { return ''; }
	const n = Number( v );
	return Math.abs( n ) <= max ? String( n ) : '';
};

/** `{{SIZE}}{{UNIT}}` — slider and width. */
const sizeUnit = ( value, template ) => {
	if ( ! value || typeof value !== 'object' ) { return ''; }
	if ( 'custom' === value.unit ) {
		const custom = cssSafe( value.size );
		if ( '' === custom ) { return ''; }
		return template.replace( /\{\{SIZE\}\}/g, custom ).replace( /\{\{UNIT\}\}/g, '' );
	}
	const size = cssNumber( value.size, 5000 );
	if ( '' === size ) { return ''; }
	return template
		.replace( /\{\{SIZE\}\}/g, size )
		.replace( /\{\{UNIT\}\}/g, value.unit || 'px' );
};

/**
 * `{{TOP}}{{RIGHT}}{{BOTTOM}}{{LEFT}}{{UNIT}}` — dimensions.
 */
export const sides = ( value, template ) => {
	if ( ! value || typeof value !== 'object' ) { return ''; }
	if ( 'custom' === value.unit ) {
		const custom = cssSafe( value.customValue );
		if ( '' === custom ) { return ''; }
		return template
			.replace( /\{\{TOP\}\}/g, custom )
			.replace( /\{\{RIGHT\}\}|\{\{BOTTOM\}\}|\{\{LEFT\}\}|\{\{UNIT\}\}/g, '' )
			.replace( / {2,}/g, ' ' )
			.replace( / ;/g, ';' )
			.trim();
	}
	const out = {};
	let any = false;
	[ 'top', 'right', 'bottom', 'left' ].forEach( ( side ) => {
		const n = cssNumber( value[ side ] );
		if ( '' !== n ) { any = true; }
		out[ side ] = '' !== n ? n : '0';
	} );
	if ( ! any ) { return ''; }

	return template
		.replace( /\{\{TOP\}\}/g, out.top )
		.replace( /\{\{RIGHT\}\}/g, out.right )
		.replace( /\{\{BOTTOM\}\}/g, out.bottom )
		.replace( /\{\{LEFT\}\}/g, out.left )
		.replace( /\{\{UNIT\}\}/g, value.unit || 'px' );
};

/** Composite: emits its whole declaration block, template ignored. */
const typography = ( value ) => {
	if ( ! value || typeof value !== 'object' ) { return ''; }
	const decl = [];
	const stack = ( window.FCF7Builder?.fontFamilies || {} )[ value.family ]?.stack;
	if ( stack ) { decl.push( `font-family:${ cssSafe( stack ) }` ); }

	const len = ( v, fallback ) => {
		if ( ! v || typeof v !== 'object' ) { return ''; }
		if ( 'custom' === v.unit ) { return cssSafe( v.size ); } // free-text CSS value
		const n = cssNumber( v.size );
		return '' === n ? '' : n + ( v.unit || fallback );
	};

	const size = len( value.size, 'px' );
	if ( size ) { decl.push( `font-size:${ size }` ); }
	if ( value.weight ) { decl.push( `font-weight:${ cssSafe( value.weight ) }` ); }
	if ( value.transform ) { decl.push( `text-transform:${ cssSafe( value.transform ) }` ); }
	if ( value.style ) { decl.push( `font-style:${ cssSafe( value.style ) }` ); }
	if ( value.decoration ) { decl.push( `text-decoration:${ cssSafe( value.decoration ) }` ); }
	const lh = len( value.lineHeight, 'em' );
	if ( lh ) { decl.push( `line-height:${ lh }` ); }
	const ls = len( value.letterSpacing, 'px' );
	if ( ls ) { decl.push( `letter-spacing:${ ls }` ); }

	return decl.length ? decl.join( ';' ) + ';' : '';
};

/** Composite. Only its `width` sub-value is responsive, so it takes a device. */
const border = ( value, template, device ) => {
	if ( ! value || typeof value !== 'object' || ! value.style ) { return ''; }
	if ( 'none' === value.style ) { return 'border-style:none;'; }
	const decl = [ `border-style:${ cssSafe( value.style ) }` ];

	const widths = ( value.width && typeof value.width === 'object' ) ? value.width : {};
	const from = deviceChain( device ).find( ( d ) => widths[ d ] !== undefined );
	const box = from ? widths[ from ] : ( DEVICES.some( ( d ) => widths[ d ] !== undefined ) ? null : widths );
	const w = box
		? sides( box, 'border-width:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' )
		: '';
	if ( w ) { decl.push( w.replace( /;$/, '' ) ); }

	if ( value.color ) { decl.push( `border-color:${ cssSafe( value.color ) }` ); }
	return decl.join( ';' ) + ';';
};

/** Composite (like border): emits `box-shadow: …;` itself, template ignored. */
const boxShadow = ( value ) => {
	if ( ! value || typeof value !== 'object' ) { return ''; }
	const color = cssSafe( value.color );
	const num = ( k ) => { const x = cssNumber( value[ k ] ); return '' === x ? '0' : x; };
	const anyNum = [ 'horizontal', 'vertical', 'blur', 'spread' ].some( ( k ) => cssNumber( value[ k ] ) !== '' );
	if ( '' === color && ! anyNum ) { return ''; }
	const inset = value.inset ? 'inset ' : '';
	const shadow = `${ inset }${ num( 'horizontal' ) }px ${ num( 'vertical' ) }px ${ num( 'blur' ) }px ${ num( 'spread' ) }px${ color ? ' ' + color : '' }`;
	return `box-shadow: ${ shadow };`;
};

const RENDERERS = { slider: sizeUnit, dimensions: sides, typography, border, box_shadow: boxShadow };

/** The scalar {{VALUE}} path every other control type uses. */
const scalar = ( value, template ) => {
	if ( value === null || value === undefined || typeof value === 'object' ) { return ''; }
	const v = typeof value === 'boolean' ? ( value ? '1' : '' ) : cssSafe( value );
	return '' === v ? '' : template.replace( /\{\{VALUE\}\}/g, v );
};

export function buildCanvasCss( fields, device = 'desktop', scope = '.fcf7b-canvas-row' ) {
	const rules = [];

	( fields || [] ).forEach( ( field ) => {
		const def = BY_TYPE[ field.type ];
		if ( ! def || ! field.id ) { return; }

		const wrapper = `${ scope } .fcf7b-field-${ field.id }`;

		const byId = ( rows ) => ( rows || [] ).reduce( ( m, r ) => { if ( r.id ) { m[ r.id ] = r; } return m; }, {} );
		const sections = byId( def.sections );
		const popovers = byId( def.popovers );
		const tabGroups = byId( def.tab_groups );
		const tabItems = byId( def.tab_items );

		( def.controls || [] ).forEach( ( ctrl ) => {
			const key = ctrl.key;
			if ( ! key || ! ( key in field ) ) { return; }

			if ( ctrl.popover && ! field[ ctrl.popover ] ) { return; }

			const sec = sections[ ctrl.section ];
			if ( sec && sec.condition && ! meetsCondition( sec.condition, field, 'desktop' ) ) { return; }
			const pop = ctrl.popover ? popovers[ ctrl.popover ] : null;
			if ( pop && pop.condition && ! meetsCondition( pop.condition, field, 'desktop' ) ) { return; }

			const tg = ctrl.tab_group ? tabGroups[ ctrl.tab_group ] : null;
			if ( tg && tg.condition && ! meetsCondition( tg.condition, field, 'desktop' ) ) { return; }
			const ti = ctrl.tab_item ? tabItems[ ctrl.tab_item ] : null;
			if ( ti && ti.condition && ! meetsCondition( ti.condition, field, 'desktop' ) ) { return; }

			if ( ctrl.condition && ! meetsCondition( ctrl.condition, field, device ) ) { return; }

			const targets = ctrl.selectors
				|| ( ctrl.selector ? { [ ctrl.selector ]: '' } : null );
			if ( ! targets ) { return; }

			let value = field[ key ];
			if ( ctrl.responsive && value && typeof value === 'object' && ! Array.isArray( value ) ) {
				const chain = deviceChain( device );
				const hasDevices = DEVICES.some( ( d ) => value[ d ] !== undefined );
				if ( hasDevices ) {
					const from = chain.find( ( d ) => ! isEmptyValue( value[ d ] ) );
					value = from ? value[ from ] : undefined;
				}
			}
			if ( value === undefined ) { return; }

			const render = RENDERERS[ ctrl.type ] || scalar;

			Object.keys( targets ).forEach( ( raw ) => {
				const decl = render( value, String( targets[ raw ] ), device );
				if ( ! decl || ! decl.trim() ) { return; }

				const selector = raw.replace( /\{\{WRAPPER\}\}/g, wrapper );

				rules.push( `${ selector }{${ decl.trim() }}` );
			} );
		} );
	} );

	return rules.join( '\n' );
}
