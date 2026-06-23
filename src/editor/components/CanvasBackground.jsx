import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ColorControl from './ColorControl';

export const CANVAS_BG_KEY = 'fcf7b-canvas-bg';

export const isCanvasBg = ( v ) => typeof v === 'string' && /^#[0-9a-f]{3}([0-9a-f]{3}([0-9a-f]{2})?)?$/i.test( v );

export const readCanvasBg = () => {
	try {
		const v = window.localStorage.getItem( CANVAS_BG_KEY ) || '';
		return isCanvasBg( v ) ? v : '';
	} catch ( e ) { return ''; }
};

const PRESETS = [
	[ '', __( 'Theme default', 'compactform' ) ],
	[ '#ffffff', __( 'White', 'compactform' ) ],
	[ '#f4f5f7', __( 'Light grey', 'compactform' ) ],
	[ '#e5e7eb', __( 'Grey', 'compactform' ) ],
	[ '#1e2127', __( 'Dark', 'compactform' ) ],
	[ '#000000', __( 'Black', 'compactform' ) ],
];

export default function CanvasBackground( { value, onChange, disabled = false } ) {
	const [ open, setOpen ] = useState( false );
	const ref = useRef( null );

	useEffect( () => {
		if ( ! open ) { return; }
		const onDown = ( e ) => { if ( ref.current && ! ref.current.contains( e.target ) ) { setOpen( false ); } };
		const onKey = ( e ) => {
			if ( e.key !== 'Escape' ) { return; }
			if ( ref.current && ref.current.querySelector( '.fcf7b-color-pop' ) ) { return; }
			setOpen( false );
		};
		document.addEventListener( 'mousedown', onDown );
		document.addEventListener( 'keydown', onKey );
		return () => {
			document.removeEventListener( 'mousedown', onDown );
			document.removeEventListener( 'keydown', onKey );
		};
	}, [ open ] );

	return (
		<span className="fcf7b-canvasbg" ref={ ref }>
			<button
				type="button"
				className={ `fcf7b-ghost${ open ? ' is-set' : '' }` }
				title={ __( 'Canvas background', 'compactform' ) }
				disabled={ disabled }
				onClick={ () => setOpen( ! open ) }
			><i className="ri-paint-fill" /></button>

			{ open && (
				<div className="fcf7b-canvasbg-pop">
					<div className="fcf7b-canvasbg-swatches">
						{ PRESETS.map( ( [ c, label ] ) => (
							<button
								key={ c || 'default' }
								type="button"
								title={ label }
								className={ `fcf7b-canvasbg-sw${ value === c ? ' is-active' : '' }${ c ? '' : ' is-default' }` }
								style={ c ? { background: c } : null }
								onClick={ () => { onChange( c ); setOpen( false ); } }
							/>
						) ) }
					</div>
					<div className="fcf7b-canvasbg-custom">
						<span>{ __( 'Custom', 'compactform' ) }</span>
						<ColorControl value={ value } onChange={ onChange } />
					</div>
				</div>
			) }
		</span>
	);
}
