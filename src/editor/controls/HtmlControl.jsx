import { useEffect, useRef } from '@wordpress/element';

export default function HtmlControl( { ctrl, value, update } ) {
	const shown = value !== undefined ? value : ( ctrl.default ?? '' );

	const taRef = useRef( null );
	const cmRef = useRef( null );
	const pushRef = useRef( null );
	pushRef.current = ( v ) => update( ctrl.key, v );

	useEffect( () => {
		const settings = window.FCF7CodeEditor?.settings;
		if ( ! settings || ! window.wp?.codeEditor || ! taRef.current ) {
			return;
		}

		const editor = window.wp.codeEditor.initialize( taRef.current, settings );
		cmRef.current = editor.codemirror;

		cmRef.current.on( 'change', ( cm ) => pushRef.current( cm.getValue() ) );

		const raf = requestAnimationFrame( () => cmRef.current?.refresh() );

		return () => {
			cancelAnimationFrame( raf );
			cmRef.current?.toTextArea();
			cmRef.current = null;
		};
	}, [] );
	useEffect( () => {
		const cm = cmRef.current;
		if ( cm && cm.getValue() !== shown ) {
			cm.setValue( shown );
		}
	}, [ shown ] );

	return (
		<textarea
			ref={ taRef }
			className="fcf7b-code-input"
			rows={ 8 }
			spellCheck={ false }
			autoComplete="off"
			autoCorrect="off"
			autoCapitalize="off"
			value={ shown }
			placeholder={ ctrl.placeholder || undefined }
			onChange={ ( e ) => update( ctrl.key, e.target.value ) }
		/>
	);
}
