import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { BY_TYPE, INSTANCE_MARKUP, resolveResponsive, drag, startMoveDrag, dragIds, useDropIndex, setDropTarget, clearDropTarget, commitDrop, dropOffset, dropPosition } from '../shared';

const CELLS = ':scope > .fcf7b-ms-stack > .fcf7b-ms-cell';
import FieldPreview from '../components/FieldPreview';
import CardHandle from '../components/CardHandle';

export default function MultistepFormPreview( { field, allFields = [], actions, selected, multi = [], device = 'desktop', openMenu } ) {
	const steps = Array.isArray( field.steps ) && field.steps.length
		? field.steps
		: [ { id: 'step_1', label: sprintf( /* translators: %d: step number. */ __( 'Step %d', 'compactform' ), 1 ) } ];
	const [ activeId, setActiveId ] = useState( steps[ 0 ].id );

	const activeStep = steps.find( ( s ) => s.id === activeId ) || steps[ 0 ];
	const activeIndex = steps.findIndex( ( s ) => s.id === activeStep.id );

	const scope = `${ field.id }:${ activeStep.id }`;
	const overIndex = useDropIndex( scope );

	const stepIds = new Set( steps.map( ( s ) => s.id ) );
	const isFirstStep = activeStep.id === steps[ 0 ].id;
	const belongsHere = ( f ) => f.parentId === field.id
		&& ( f.stepId === activeStep.id || ( isFirstStep && ! stepIds.has( f.stepId ) ) );

	const children = allFields.filter( belongsHere );

	const dropAt = ( pos ) => {
		clearDropTarget();
		if ( ! drag.value || ! actions ) { return; }
		if ( dragIds().includes( field.id ) ) { return; }

		const lastChildIdx = allFields.reduce(
			( acc, f, i ) => belongsHere( f ) ? i : acc, -1
		);
		const insertAt = pos >= children.length
			? ( lastChildIdx === -1 ? allFields.length : lastChildIdx + 1 )
			: allFields.indexOf( children[ pos ] );

		if ( drag.value.kind === 'new' ) {
			actions.add( drag.value.type, insertAt, { parentId: field.id, stepId: activeStep.id } );
		} else if ( drag.value.kind === 'move' ) {
			actions.move( dragIds(), insertAt, { parentId: field.id, stepId: activeStep.id } );
		}
		drag.value = null;
	};

	const goTo = ( i ) => setActiveId( steps[ Math.max( 0, Math.min( steps.length - 1, i ) ) ].id );

	return (
		<div className={ `fcf7b-field-wrap fcf7b-field-${ field.id }` }>
			<div className="fcf7b-multistep">
				{ 'circles' === field.navStyle ? (
					<div className="fcf7b-ms-steps">
						{ steps.map( ( s, i ) => {
							const state = i < activeIndex ? 'is-done' : i === activeIndex ? 'is-active' : 'is-upcoming';
							return (
								<div key={ s.id } className={ `fcf7b-ms-step ${ state }` }>
									<button type="button" className="fcf7b-ms-step-circle"
										onClick={ ( e ) => { e.stopPropagation(); setActiveId( s.id ); } }>
										<span className="fcf7b-ms-step-num">{ i + 1 }</span>
										<svg className="fcf7b-ms-step-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
									</button>
									<span className="fcf7b-ms-step-label">{ s.label || `Step ${ i + 1 }` }</span>
								</div>
							);
						} ) }
					</div>
				) : (
					<div className="fcf7b-ms-tabs">
						{ steps.map( ( s, i ) => (
							<button type="button" key={ s.id }
								className={ `fcf7b-ms-tab${ s.id === activeStep.id ? ' is-active' : '' }` }
								onClick={ ( e ) => { e.stopPropagation(); setActiveId( s.id ); } }>
								{ s.label || `Step ${ i + 1 }` }
							</button>
						) ) }
					</div>
				) }

				{ /* Empty step: no cell to draw a marker on, so the panel lights up. */ }
				<div className={ `fcf7b-ms-panel${ ( ! children.length && overIndex >= 0 ) ? ' is-drop-in' : '' }` }
					onDragOver={ ( e ) => {
						e.preventDefault();
						e.stopPropagation();
						const at = dropPosition( e, e.currentTarget, CELLS );
						setDropTarget( scope, at, () => dropAt( at ) );
					} }
					onDrop={ ( e ) => { e.preventDefault(); e.stopPropagation(); commitDrop(); } }>
					{ children.length ? <div className="fcf7b-ms-stack">{ children.map( ( child, i ) => {
						const typeDef = BY_TYPE[ child.type ] || { title: child.type, icon: '' };
						const def = INSTANCE_MARKUP[ child.id ] ? { ...typeDef, builderMarkup: INSTANCE_MARKUP[ child.id ] } : typeDef;
						const childInteractions = {
							onChange: ( key, value ) => actions.updateField( child.id, key, value ),
							beginGesture: actions.beginGesture,
							endGesture: actions.endGesture,
							allFields,
							actions,
							selected,
							multi,
							openMenu,
						};
						return (
							<div key={ child.id }
								className={ `fcf7b-cell fcf7b-ms-cell fcf7b-field-${ child.id }${ overIndex === i ? ' is-drop-before' : '' }${
									( overIndex === i + 1 && i === children.length - 1 ) ? ' is-drop-after' : ''
								}` }
								onDragOver={ ( e ) => {
									e.preventDefault();
									e.stopPropagation();
									const at = i + dropOffset( e, e.currentTarget );
									setDropTarget( scope, at, () => dropAt( at ) );
								} }
								onDrop={ ( e ) => { e.preventDefault(); e.stopPropagation(); commitDrop(); } }>
								<div className={ `fcf7b-card${ multi.includes( child.id ) ? ' is-selected' : '' }` }
									draggable={ true }
									onClick={ ( e ) => { e.stopPropagation(); actions.select( child.id, e.ctrlKey || e.metaKey ); } }
									onDragStart={ ( e ) => { startMoveDrag( child.id, multi ); e.stopPropagation(); } }
									onContextMenu={ ( e ) => {
										e.preventDefault();
										e.stopPropagation();
										if ( ! multi.includes( child.id ) ) { actions.select( child.id ); }
										if ( openMenu ) { openMenu( child.id, e.clientX, e.clientY ); }
									} }>
									<CardHandle field={ child } def={ typeDef } nested={ true } actions={ actions } multi={ multi } openMenu={ openMenu } />
									<div className="fcf7b-card-body">
										{ FieldPreview( resolveResponsive( child, def, device ), def, device, childInteractions ) }
									</div>
								</div>
							</div>
						);
					} ) }</div> : (
						<div className="fcf7b-ms-empty">Drag a field here</div>
					) }
				</div>

				<div className="fcf7b-ms-nav-buttons">
					<button type="button" className="fcf7b-ms-prev" disabled={ activeIndex <= 0 }
						onClick={ ( e ) => { e.stopPropagation(); goTo( activeIndex - 1 ); } }>
						Previous
					</button>
					<button type="button" className="fcf7b-ms-next" disabled={ activeIndex >= steps.length - 1 }
						onClick={ ( e ) => { e.stopPropagation(); goTo( activeIndex + 1 ); } }>
						Next
					</button>
				</div>
			</div>
		</div>
	);
}
