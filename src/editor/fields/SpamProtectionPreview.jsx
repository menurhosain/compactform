import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import FieldChrome from './preview-helpers';
import { DATA } from '../shared';

export default function SpamProtectionPreview( { field, def } ) {
	const isCanvas = field.method === 'canvas';
	const width = parseInt( field.canvas_width, 10 ) || 160;
	const height = parseInt( field.canvas_height, 10 ) || 50;
	const bg = field.canvas_bg || '';
	const color = field.canvas_color || '';
	const noise = field.noise || 'medium';
	const operations = field.operations || 'plus';
	const max = parseInt( field.max_operand, 10 ) || 10;

	const canvasRef = useRef( null );
	const answerRef = useRef( null );
	const [ image, setImage ] = useState( '' );
	const [ sample, setSample ] = useState( '7 + 3 =' );
	const [ nonce, setNonce ] = useState( 0 );
	const [ busy, setBusy ] = useState( false );

	useEffect( () => {
		if ( ! DATA.ajaxUrl ) {
			return undefined;
		}

		let cancelled = false;
		setBusy( true );
		const body = new window.FormData();
		body.append( 'action', 'fcf7_spam_protection_refresh' );
		body.append( 'nonce', DATA.nonce || '' );
		body.append( 'method', isCanvas ? 'canvas' : 'text' );
		body.append( 'ops', operations );
		body.append( 'max', String( max ) );
		body.append( 'width', String( width ) );
		body.append( 'height', String( height ) );
		body.append( 'bg', bg );
		body.append( 'color', color );
		body.append( 'noise', noise );

		window
			.fetch( DATA.ajaxUrl, { method: 'POST', credentials: 'same-origin', body } )
			.then( ( response ) => response.json() )
			.then( ( payload ) => {
				if ( cancelled || ! payload || ! payload.success ) {
					return;
				}
				setImage( payload.data.image || '' );
				if ( payload.data.question ) {
					setSample( payload.data.question );
				}
				if ( answerRef.current ) {
					answerRef.current.value = '';
				}
			} )
			.catch( () => {} )
			.finally( () => {
				if ( ! cancelled ) {
					setBusy( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ isCanvas, operations, max, width, height, bg, color, noise, nonce ] );

	useEffect( () => {
		const canvas = canvasRef.current;
		if ( ! canvas || ! canvas.getContext ) {
			return;
		}

		const ctx = canvas.getContext( '2d' );
		ctx.clearRect( 0, 0, canvas.width, canvas.height );

		if ( ! image ) {
			return;
		}

		const img = new window.Image();
		img.onload = () => ctx.drawImage( img, 0, 0, canvas.width, canvas.height );
		img.src = image;
	}, [ image, width, height ] );

	return (
		<FieldChrome field={ field } def={ def }>
			<div className="wpcf7-fcf7_spam_protection fcf7-sp">
				<div className="fcf7-sp-challenge">
					{ isCanvas ? (
						<canvas
							ref={ canvasRef }
							className="fcf7-sp-canvas"
							width={ width }
							height={ height }
						/>
					) : (
						<span className="fcf7-sp-question">{ sample }</span>
					) }

					{ 'yes' !== field.hide_refresh && (
						<button
							type="button"
							className={ `fcf7-sp-refresh${ busy ? ' is-busy' : '' }` }
							disabled={ busy }
							onClick={ ( e ) => { e.preventDefault(); e.stopPropagation(); setNonce( ( n ) => n + 1 ); } }
						>
							<svg viewBox="0 0 24 24" aria-hidden="true">
								<path
									fill="currentColor"
									d="M12 5V2L8 6l4 4V7a5 5 0 1 1-5 5H5a7 7 0 1 0 7-7z"
								/>
							</svg>
						</button>
					) }
				</div>
				
				<input
					ref={ answerRef }
					key={ field.answer_placeholder || '' }
					type="text"
					className="fcf7-sp-answer fcf7b-pv-input wpcf7-form-control wpcf7-validates-as-required"
					placeholder={ field.answer_placeholder || __( 'Your answer', 'compactform' ) }
				/>
			</div>
		</FieldChrome>
	);
}
