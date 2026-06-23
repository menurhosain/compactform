import { useEffect } from '@wordpress/element';
import { fieldClipboard, copyToClipboard, styleFromClipboard } from '../shared';

// Ctrl/Cmd+...
export default function useKeyboardShortcuts( { schema, selected, multi, actions, undo, redo } ) {
	useEffect( () => {
		const onKey = ( e ) => {
			const t = e.target;
			if ( t && ( t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test( t.tagName || '' ) ) ) { return; }

			if ( document.querySelector( '.fcf7b-gs-backdrop, .fcf7b-ctxmenu-backdrop' ) ) { return; }

			const mod = e.ctrlKey || e.metaKey;

			if ( mod && 'z' === e.key.toLowerCase() ) {
				e.preventDefault(); // or the browser undoes wp-admin's own inputs
				if ( e.shiftKey ) { redo(); } else { undo(); }
				return;
			}
			if ( mod && 'y' === e.key.toLowerCase() ) {
				e.preventDefault();
				redo();
				return;
			}

			if ( mod && 'c' === e.key.toLowerCase() && ! e.shiftKey ) {
				const sel = window.getSelection();
				if ( sel && sel.toString() ) { return; }
				if ( multi.length && copyToClipboard( schema.fields, multi ) ) { e.preventDefault(); }
				return;
			}
			if ( mod && 'v' === e.key.toLowerCase() ) {
				if ( ! fieldClipboard.value ) { return; }
				e.preventDefault();
				if ( e.shiftKey ) {
					const target = schema.fields.filter( ( f ) => f.id === selected )[ 0 ];
					const patch = styleFromClipboard( target );
					if ( Object.keys( patch ).length ) { actions.updateField( selected, patch ); }
				} else {
					actions.paste( fieldClipboard.value, selected );
				}
				return;
			}

			if ( mod && 'd' === e.key.toLowerCase() ) {
				e.preventDefault();
				if ( multi.length ) { actions.duplicateMany( multi ); }
				return;
			}

			if ( 'Delete' === e.key && multi.length ) {
				e.preventDefault();
				actions.removeMany( multi );
			}
		};

		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );

	}, [ selected, multi, schema ] );
}
