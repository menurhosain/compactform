
import { deviceChain, isEmptyValue, globalSpacing } from './shared';

export const CONTAINER_CSS_DEFAULTS = { columnGap: 20, rowGap: 20, padding: 10 };

export const FIELD_GAP_CSS_DEFAULT = 20;

export const uniformBox = ( n ) => ( {
	top: String( n ),
	right: String( n ),
	bottom: String( n ),
	left: String( n ),
	unit: 'px',
} );

const GLOBAL_KEYS = {
	container: {
		columnGap: 'containerColumnGap',
		rowGap: 'containerRowGap',
		padding: 'containerPadding',
	},
};

export function resolveGlobal( globalKey, device = 'desktop' ) {
	const value = ( globalSpacing.value || {} )[ globalKey ];
	if ( isEmptyValue( value ) ) { return undefined; }
	if ( 'object' !== typeof value || Array.isArray( value ) ) { return value; }
	const hit = deviceChain( device ).find( ( d ) => ! isEmptyValue( value[ d ] ) );
	return hit === undefined ? undefined : value[ hit ];
}

export function cssDefaultFor( fieldType, ctrlKey ) {
	if ( 'container' !== fieldType ) { return undefined; }
	const css = CONTAINER_CSS_DEFAULTS[ ctrlKey ];
	if ( css === undefined ) { return undefined; }
	return 'padding' === ctrlKey ? uniformBox( css ) : css;
}

export function inheritedFallback( fieldType, ctrlKey, device = 'desktop' ) {
	const globalKey = ( GLOBAL_KEYS[ fieldType ] || {} )[ ctrlKey ];
	if ( ! globalKey ) { return undefined; }

	const fromGlobal = resolveGlobal( globalKey, device );
	return fromGlobal === undefined ? cssDefaultFor( fieldType, ctrlKey ) : fromGlobal;
}
