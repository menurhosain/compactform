import { useEffect, useRef, useState } from '@wordpress/element';

export default function useHistory( schema, setSchema ) {
	const [ canUndo, setCanUndo ] = useState( false );
	const [ canRedo, setCanRedo ] = useState( false );
	const hist = useRef( { stack: [], idx: -1, lock: false } );

	useEffect( () => {
		const h = hist.current;
		if ( h.lock ) { h.lock = false; return; }
		if ( h.gesture && h.gesturePushed ) {
			h.stack[ h.idx ] = schema; // same gesture → update the top entry
		} else {
			h.stack = h.stack.slice( 0, h.idx + 1 );
			h.stack.push( schema );
			if ( h.stack.length > 100 ) { h.stack.shift(); }
			h.idx = h.stack.length - 1;
			if ( h.gesture ) { h.gesturePushed = true; }
		}
		setCanUndo( h.idx > 0 );
		setCanRedo( false );
	}, [ schema ] );

	const undo = () => {
		const h = hist.current;
		if ( h.idx <= 0 ) { return; }
		h.idx -= 1;
		h.lock = true;
		setSchema( h.stack[ h.idx ] );
		setCanUndo( h.idx > 0 );
		setCanRedo( true );
	};

	const redo = () => {
		const h = hist.current;
		if ( h.idx >= h.stack.length - 1 ) { return; }
		h.idx += 1;
		h.lock = true;
		setSchema( h.stack[ h.idx ] );
		setCanUndo( true );
		setCanRedo( h.idx < h.stack.length - 1 );
	};

	const beginGesture = () => { hist.current.gesture = true; hist.current.gesturePushed = false; };
	const endGesture = () => { hist.current.gesture = false; hist.current.gesturePushed = false; };

	return { canUndo, canRedo, undo, redo, beginGesture, endGesture };
}
