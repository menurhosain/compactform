import { useRef, useEffect } from '@wordpress/element';


export default function CustomPreviewHost( { field, def, handlers, interactions = {} } ) {
	const ref = useRef( null );

	useEffect( () => {
		const el = ref.current;
		if ( ! el ) { return; }
		handlers.mount( el, field, def, interactions );
		return () => handlers.unmount?.( el );
	}, [] );

	const mounted = useRef( false );
	useEffect( () => {
		if ( ! mounted.current ) { mounted.current = true; return; }
		if ( ref.current ) { handlers.update?.( ref.current, field, def, interactions ); }
	}, [ JSON.stringify( field ) ] );

	return <div className="fcf7b-pv-custom" ref={ ref } />;
}
