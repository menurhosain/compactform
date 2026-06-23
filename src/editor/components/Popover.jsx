import { useState, useEffect, useRef } from '@wordpress/element';

export default function Popover( {
	className = '',
	triggerClassName = '',
	popClassName = '',
	backdropClassName = '',
	trigger,
	children,
	onOpenChange,
	title,
} ) {
	const [ open, setOpen ] = useState( false );
	const ref = useRef( null );
	const notify = useRef( onOpenChange );
	notify.current = onOpenChange;

	const set = ( next ) => {
		setOpen( next );
		if ( notify.current ) { notify.current( next ); }
	};

	useEffect( () => {
		if ( ! open ) { return; }
		const onDoc = ( e ) => {
			if ( ref.current && ! ref.current.contains( e.target ) ) { set( false ); }
		};
		const onKey = ( e ) => { if ( e.key === 'Escape' ) { set( false ); } };
		document.addEventListener( 'mousedown', onDoc );
		document.addEventListener( 'keydown', onKey );
		return () => {
			document.removeEventListener( 'mousedown', onDoc );
			document.removeEventListener( 'keydown', onKey );
		};
	}, [ open ] );

	return (
		<div className={ className } ref={ ref }>
			<button
				type="button"
				title={ title }
				aria-expanded={ open }
				className={ `${ triggerClassName }${ open ? ' is-open' : '' }`.trim() }
				onClick={ () => set( ! open ) }
			>
				{ typeof trigger === 'function' ? trigger( open ) : trigger }
			</button>

			{ open && backdropClassName && (
				<div className={ backdropClassName } onMouseDown={ () => set( false ) } />
			) }

			{ open && (
				<div className={ popClassName }>
					{ typeof children === 'function' ? children( { close: () => set( false ) } ) : children }
				</div>
			) }
		</div>
	);
}
