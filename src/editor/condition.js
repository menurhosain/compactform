
import { BY_TYPE, deviceChain } from './shared';

function parseKey( raw ) {
	let key = String( raw );
	const negate = key.endsWith( '!' );
	if ( negate ) {
		key = key.slice( 0, -1 );
	}

	let sub = '';
	const bracket = key.indexOf( '[' );
	if ( bracket > -1 && key.endsWith( ']' ) ) {
		sub = key.slice( bracket + 1, -1 );
		key = key.slice( 0, bracket );
	}

	return { key, sub, negate };
}

function effectiveValue( field, key, sub, device ) {
	const def = BY_TYPE[ field.type ] || {};
	const defaults = def.defaults || {};
	let value = field[ key ] !== undefined ? field[ key ] : defaults[ key ];

	const ctrl = ( def.controls || [] ).find( ( c ) => c.key === key );
	if ( ctrl && ctrl.responsive && value && typeof value === 'object' && ! Array.isArray( value ) ) {
		const from = deviceChain( device ).find( ( d ) => value[ d ] !== undefined );
		value = from ? value[ from ] : undefined;
	}

	if ( sub ) {
		value = ( value && typeof value === 'object' ) ? value[ sub ] : undefined;
	}

	return ( value === undefined || value === null ) ? '' : value;
}

function equals( a, b ) {
	return a == b || String( a ) === String( b );
}

export function meetsCondition( condition, field, device = 'desktop' ) {
	if ( ! condition || typeof condition !== 'object' ) {
		return true;
	}

	return Object.keys( condition ).every( ( raw ) => {
		const { key, sub, negate } = parseKey( raw );
		const expected = condition[ raw ];
		const actual = effectiveValue( field, key, sub, device );

		const matched = Array.isArray( expected )
			? expected.some( ( one ) => equals( actual, one ) )
			: equals( actual, expected );

		return negate ? ! matched : matched;
	} );
}
