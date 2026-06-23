import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { uid } from '../shared';

export default function StepsControl( { ctrl, value, update } ) {
	/* translators: %d: step number. */
	const stepLabel = ( n ) => sprintf( __( 'Step %d', 'compactform' ), n );

	const steps = Array.isArray( value ) && value.length ? value : [ { id: uid(), label: stepLabel( 1 ) } ];
	const [ dragId, setDragId ] = useState( null );
	const [ overIndex, setOverIndex ] = useState( -1 );

	const setSteps = ( next ) => update( ctrl.key, next );

	const rename = ( id, label ) => setSteps( steps.map( ( s ) => s.id === id ? { ...s, label } : s ) );

	const add = () => setSteps( [ ...steps, { id: uid(), label: stepLabel( steps.length + 1 ) } ] );

	const remove = ( id ) => {
		if ( steps.length <= 1 ) { return; }
		setSteps( steps.filter( ( s ) => s.id !== id ) );
	};

	const reorder = ( id, toIndex ) => {
		const from = steps.findIndex( ( s ) => s.id === id );
		if ( from === -1 || from === toIndex ) { return; }
		const next = [ ...steps ];
		const [ moved ] = next.splice( from, 1 );
		next.splice( toIndex > from ? toIndex - 1 : toIndex, 0, moved );
		setSteps( next );
	};

	const onDrop = ( i ) => {
		if ( dragId ) { reorder( dragId, i ); }
		setDragId( null );
		setOverIndex( -1 );
	};

	return (
		<div className="fcf7b-steps-ctl">
			{ steps.map( ( step, i ) => (
				<div
					key={ step.id }
					className={ `fcf7b-steps-ctl-row${ overIndex === i ? ' is-drop-before' : '' }` }
					onDragOver={ ( e ) => { e.preventDefault(); setOverIndex( i ); } }
					onDrop={ ( e ) => { e.preventDefault(); e.stopPropagation(); onDrop( i ); } }
				>
					<span
						className="fcf7b-steps-ctl-handle"
						title={ __( 'Drag to reorder', 'compactform' ) }
						draggable={ true }
						onDragStart={ ( e ) => { setDragId( step.id ); e.stopPropagation(); } }
						onDragEnd={ () => { setDragId( null ); setOverIndex( -1 ); } }
					>
						<i className="ri-draggable" />
					</span>
					<span className="fcf7b-steps-ctl-index">{ i + 1 }</span>
					<input
						type="text"
						className="fcf7b-steps-ctl-label"
						value={ step.label }
						placeholder={ stepLabel( i + 1 ) }
						onChange={ ( e ) => rename( step.id, e.target.value ) }
					/>
					<button
						type="button"
						className="fcf7b-steps-ctl-remove"
						title={ __( 'Remove step', 'compactform' ) }
						disabled={ steps.length <= 1 }
						onClick={ () => remove( step.id ) }
					>
						<i className="ri-delete-bin-line" />
					</button>
				</div>
			) ) }
			<div
				className={ `fcf7b-steps-ctl-drop-end${ overIndex === steps.length ? ' is-drop-before' : '' }` }
				onDragOver={ ( e ) => { e.preventDefault(); setOverIndex( steps.length ); } }
				onDrop={ ( e ) => { e.preventDefault(); onDrop( steps.length ); } }
			/>
			<button type="button" className="fcf7b-steps-ctl-add" onClick={ add }>
				<i className="ri-add-line" /> Add Step
			</button>
		</div>
	);
}
