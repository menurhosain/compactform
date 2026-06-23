// The template library talks to nothing else in the editor — it reads window.FCF7Builder
// itself rather than importing shared.js, so the whole feature is one component + this file.

const CONFIG = window.FCF7Builder || { ajaxUrl: '', nonce: '' };

// Always the PHP proxy, never the library host directly: the manifest is plain HTTP with no
// CORS headers, and an https-admin fetch of it would be blocked as mixed content.
function ajaxGet( action, params = {} ) {
	if ( ! CONFIG.ajaxUrl ) { return window.Promise.resolve( { ok: false, message: '' } ); }
	const qs = new window.URLSearchParams( { action, nonce: CONFIG.nonce || '', ...params } );
	return window.fetch( `${ CONFIG.ajaxUrl }?${ qs }`, { credentials: 'same-origin' } )
		.then( ( r ) => r.json() )
		.then( ( res ) => ( res?.success
			? { ok: true, data: res.data }
			: { ok: false, message: res?.data?.message || '' } ) )
		.catch( () => ( { ok: false, message: '' } ) );
}

// Module-level, so reopening the modal in the same editing session costs no request at all —
// PHP's transient still caches ACROSS sessions. The in-flight promise is cached too, not just
// the result: a close-and-reopen before the first answer lands would otherwise fire a second.
const CACHE = { list: null, data: null, byId: Object.create( null ) };

// The resolved list, readable synchronously — so a reopen paints the grid on the first render
// instead of flashing "Loading templates…" for a microtask.
export function templatesCached() {
	return CACHE.data;
}

export function templatesFetch( refresh = false ) {
	if ( refresh ) {
		CACHE.list = null;
		CACHE.data = null;
		CACHE.byId = Object.create( null );
	} else if ( CACHE.list ) {
		return CACHE.list;
	}

	const req = ajaxGet( 'fcf7_templates_list', refresh ? { refresh: '1' } : {} )
		.then( ( res ) => {
			// A failure must not stick — the next open should retry.
			if ( res.ok ) {
				CACHE.data = res.data;
			} else {
				CACHE.list = null;
			}
			return res;
		} );

	CACHE.list = req;

	return req;
}

export function templateFetch( id ) {
	if ( CACHE.byId[ id ] ) { return CACHE.byId[ id ]; }

	const req = ajaxGet( 'fcf7_templates_get', { id } )
		.then( ( res ) => {
			if ( ! res.ok ) { delete CACHE.byId[ id ]; }
			return res;
		} );

	CACHE.byId[ id ] = req;

	return req;
}
