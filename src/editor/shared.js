import { useState, useEffect } from '@wordpress/element';

export const DATA = window.FCF7Builder || { fields: [], categories: {}, tabs: {}, ajaxUrl: '', nonce: '', formId: 0 };
export const FIELDS = DATA.fields || [];
export const BY_TYPE = {};
FIELDS.forEach( ( f ) => { BY_TYPE[ f.type ] = f; } );

export const INSTANCE_MARKUP = DATA.instanceMarkup || {};

export const drag = { value: null };

export const globalSpacing = { value: {} };

export function startMoveDrag( id, multi ) {
	const many = multi && multi.length > 1 && multi.includes( id );
	drag.value = { kind: 'move', id, ids: many ? multi.slice() : [ id ] };
}

export function dragIds() {
	const d = drag.value;
	if ( ! d || 'move' !== d.kind ) { return []; }
	return ( d.ids && d.ids.length ) ? d.ids : [ d.id ];
}

export const dropTarget = { value: null };
const _dropSubs = new Set();
let _dropCommit = null;

export function setDropTarget( scope, index, commit ) {
	const cur = dropTarget.value;
	const next = ( scope === null || scope === undefined ) ? null : { scope, index };
	// Refreshed even when the position is unchanged — the closure behind it holds this
	// render's fields/actions.
	_dropCommit = next ? ( commit || null ) : null;
	if ( ! next && ! cur ) { return; }
	if ( next && cur && cur.scope === next.scope && cur.index === next.index ) { return; }
	dropTarget.value = next;
	_dropSubs.forEach( ( fn ) => fn() );
}

export const clearDropTarget = () => setDropTarget( null );

// The handler that DREW the marker is the only one that knows what it means, so a drop runs
// that instead of every element recomputing a position from wherever the pointer was released.
// Returns false when nothing was drawn, leaving the caller its own fallback.
export function commitDrop() {
	const fn = _dropCommit;
	clearDropTarget();
	if ( ! fn ) { return false; }
	fn();
	return true;
}

export function dropOffset( e, el, axis = 'y', reversed = false ) {
	const r = el.getBoundingClientRect();
	const past = axis === 'x'
		? ( e.clientX - r.left ) > r.width / 2
		: ( e.clientY - r.top ) > r.height / 2;
	return ( reversed ? ! past : past ) ? 1 : 0;
}

export function dropPosition( e, boxEl, selector, axis = 'y', reversed = false ) {
	const kids = Array.from( boxEl.querySelectorAll( selector ) );
	for ( let i = 0; i < kids.length; i++ ) {
		if ( ! dropOffset( e, kids[ i ], axis, reversed ) ) { return i; }
	}
	return kids.length;
}

export function useDropIndex( scope ) {
	const [ , bump ] = useState( 0 );
	useEffect( () => {
		const fn = () => bump( ( n ) => n + 1 );
		_dropSubs.add( fn );
		return () => { _dropSubs.delete( fn ); };
	}, [] );
	const cur = dropTarget.value;
	return ( cur && cur.scope === scope ) ? cur.index : -1;
}

if ( typeof document !== 'undefined' ) {
	document.addEventListener( 'dragend', () => { drag.value = null; clearDropTarget(); } );
	document.addEventListener( 'drop', clearDropTarget );
}

export const fieldClipboard = { value: null };

export function subtreeOf( fields, id ) {
	const root = ( fields || [] ).find( ( x ) => x.id === id );
	if ( ! root ) { return []; }
	const out = [];
	const seen = new Set();
	const collect = ( f ) => {
		if ( seen.has( f.id ) ) { return; }
		seen.add( f.id );
		out.push( JSON.parse( JSON.stringify( f ) ) );
		fields.forEach( ( c ) => { if ( c.parentId === f.id ) { collect( c ); } } );
	};
	collect( root );
	return out;
}

export function topMostIds( fields, ids ) {
	const set = new Set( ids );
	const byId = new Map( ( fields || [] ).map( ( f ) => [ f.id, f ] ) );
	return ( ids || [] ).filter( ( id ) => {
		let cur = byId.get( id );
		cur = cur && cur.parentId ? byId.get( cur.parentId ) : null;
		const seen = new Set();
		while ( cur && ! seen.has( cur.id ) ) {
			if ( set.has( cur.id ) ) { return false; }
			seen.add( cur.id );
			cur = cur.parentId ? byId.get( cur.parentId ) : null;
		}
		return true;
	} );
}

export function copyToClipboard( fields, ids ) {
	const list = topMostIds( fields, Array.isArray( ids ) ? ids : [ ids ] )
		.slice()
		.sort( ( a, b ) => fields.findIndex( ( f ) => f.id === a ) - fields.findIndex( ( f ) => f.id === b ) );
	const out = [];
	const roots = [];
	list.forEach( ( id ) => {
		const sub = subtreeOf( fields, id );
		if ( ! sub.length ) { return; }
		roots.push( id );
		out.push( ...sub );
	} );
	if ( ! out.length ) { return false; }
	fieldClipboard.value = { fields: out, roots };
	return true;
}

const isStyleControl = ( c ) =>
	true === c.style_transfer ||
	( false !== c.style_transfer && ( !! c.selector || !! c.selectors || !! c.popover_toggle ) );

export const styleKeys = ( type ) => ( ( BY_TYPE[ type ] || {} ).controls || [] )
	.filter( isStyleControl ).map( ( c ) => c.key );

export function styleFromClipboard( target ) {
	const clip = fieldClipboard.value && fieldClipboard.value.fields[ 0 ];
	if ( ! clip || ! target ) { return {}; }
	const valid = new Set( styleKeys( target.type ) );
	const patch = {};
	styleKeys( clip.type ).forEach( ( k ) => {
		if ( k in clip && valid.has( k ) ) { patch[ k ] = clip[ k ]; }
	} );
	return JSON.parse( JSON.stringify( patch ) );
}

export const FONT_FAMILIES = DATA.fontFamilies || {};
export const BORDER_STYLES = DATA.borderStyles || {};

export const DEVICES = [ 'desktop', 'tablet', 'mobile' ];

export const DEFAULT_BREAKPOINTS = { tablet: 1024, mobile: 767 };

export const BREAKPOINT_MIN = 320;
export const BREAKPOINT_MAX = 2560;

export function previewWidth( device, breakpoints = {} ) {
	if ( device === 'desktop' ) { return null; }
	const bp = parseInt( breakpoints[ device ], 10 );
	return ( bp >= BREAKPOINT_MIN && bp <= BREAKPOINT_MAX ) ? bp : DEFAULT_BREAKPOINTS[ device ];
}
export const DEVICE_ICONS = {
	desktop: 'ri-computer-line',
	tablet: 'ri-tablet-line',
	mobile: 'ri-smartphone-line',
};

export const deviceChain = ( device ) => (
	device === 'mobile' ? [ 'mobile', 'tablet', 'desktop' ]
		: device === 'tablet' ? [ 'tablet', 'desktop' ]
			: [ 'desktop' ]
);

const STRUCTURAL_KEYS = new Set( [ 'unit', 'linked', 'custom' ] );

export function isEmptyValue( v ) {
	if ( v === '' || v === undefined || v === null ) { return true; }
	if ( typeof v !== 'object' ) { return false; }
	if ( Array.isArray( v ) ) { return v.length === 0; }
	return Object.keys( v ).every(
		( k ) => STRUCTURAL_KEYS.has( k ) || isEmptyValue( v[ k ] )
	);
}

export function resolveResponsive( field, def, device = 'desktop' ) {
	const controls = ( def && def.controls ) || [];
	const chain = device === 'mobile' ? [ 'mobile', 'tablet', 'desktop' ]
		: device === 'tablet' ? [ 'tablet', 'desktop' ]
			: [ 'desktop' ];

	let out = null;
	const flatten = ( key ) => {
		const map = field[ key ];
		if ( ! map || typeof map !== 'object' || Array.isArray( map ) ) { return; }
		const hit = chain.find( ( d ) => ! isEmptyValue( map[ d ] ) );
		if ( ! out ) { out = { ...field }; }
		out[ key ] = hit ? map[ hit ] : undefined;
	};

	if ( controls.length ) {
		controls.forEach( ( c ) => {
			if ( c.responsive ) { flatten( c.key ); }
		} );
		return out || field;
	}

	Object.keys( field ).forEach( ( key ) => {
		const v = field[ key ];
		if ( ! v || typeof v !== 'object' || Array.isArray( v ) ) { return; }
		const keys = Object.keys( v );
		if ( keys.length && keys.every( ( k ) => DEVICES.includes( k ) ) ) { flatten( key ); }
	} );
	return out || field;
}

let _nameCounter = 0;

export function uid() { return 'f_' + Math.random().toString( 36 ).slice( 2, 10 ); }

export function makeField( type ) {
	const def = BY_TYPE[ type ];
	if ( ! def ) { return null; }
	const f = { ...( def.defaults || {} ), id: uid(), type };
	if ( Object.prototype.hasOwnProperty.call( f, 'name' ) ) {
		_nameCounter += 1;
		f.name = type.replace( /^fcf7_/, '' ).replace( /_/g, '-' ) + '-' + _nameCounter;
	}
	if ( Object.prototype.hasOwnProperty.call( f, 'label' ) && ! f.label ) {
		f.label = def.title;
	}
	return f;
}

export const cf7Name = ( n ) => String( n || '' ).toLowerCase().replace( /[^a-z0-9_-]/g, '-' ).replace( /^-+|-+$/g, '' );

export function postableFields( fields ) {
	const list = Array.isArray( fields ) ? fields : [];
	const byId = new Map( list.map( ( f ) => [ f && f.id, f ] ) );

	return list.filter( ( f ) => {
		if ( ! f || ! f.name ) { return false; }
		if ( ! f.parentId ) { return true; }

		const parent = byId.get( f.parentId );

		return !! parent && 'repeater' !== parent.type;
	} );
}

export function repeaterRowParents( fields ) {
	const list = Array.isArray( fields ) ? fields : [];
	const byId = new Map( list.map( ( f ) => [ f && f.id, f ] ) );
	const out  = new Map();

	list.forEach( ( f ) => {
		if ( ! f || ! f.name || ! f.parentId ) { return; }

		const parent = byId.get( f.parentId );

		if ( parent && 'repeater' === parent.type ) {
			out.set( cf7Name( f.name ), cf7Name( parent.name || '' ) );
		}
	} );

	return out;
}

export const COLOR_SWATCHES = [ '#000000', '#374151', '#6b7280', '#9ca3af', '#ffffff', '#dc2626', '#ea580c', '#f59e0b', '#16a34a', '#0d9488', '#0ea5e9', '#2563eb', '#0091fa', '#7c3aed', '#db2777' ];

export const ICON_INITIAL_SIZE = 60;
export const ICON_PAGE_SIZE = 120;
export const ICON_CACHE = Object.create( null );

export function iconFetch( search, offset, limit = ICON_PAGE_SIZE ) {
	if ( ! DATA.ajaxUrl ) { return window.Promise.resolve( null ); }
	const url = `${ DATA.ajaxUrl }?action=fcf7_builder_icons&nonce=${ encodeURIComponent( DATA.nonce || '' ) }&search=${ encodeURIComponent( search ) }&offset=${ offset }&limit=${ limit }`;
	return window.fetch( url, { credentials: 'same-origin' } )
		.then( ( r ) => r.json() )
		.then( ( res ) => ( res?.success && res.data ) ? res.data : null )
		.catch( () => null );
}

export function warmIconCache() {
	if ( ICON_CACHE[ '' ] && ICON_CACHE[ '' ].icons.length ) { return; }
	iconFetch( '', 0, ICON_INITIAL_SIZE ).then( ( d ) => {
		if ( d && d.icons ) {
			ICON_CACHE[ '' ] = { icons: d.icons, offset: d.nextOffset, hasMore: !! d.hasMore, total: d.total || 0 };
		}
	} );
}
