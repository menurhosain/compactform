import { __ } from '@wordpress/i18n';
import { BY_TYPE, INSTANCE_MARKUP, resolveResponsive, drag, startMoveDrag, dragIds, useDropIndex, setDropTarget, clearDropTarget, commitDrop, dropOffset, dropPosition } from '../shared';

const ROWS = ':scope > .fcf7b-rep-stack > .fcf7b-rep-cell';
import FieldPreview from '../components/FieldPreview';
import CardHandle from '../components/CardHandle';

export default function RepeaterPreview( { field, allFields = [], actions, selected, multi = [], device = 'desktop', openMenu } ) {
	const overIndex = useDropIndex( field.id );

	const children = allFields.filter( ( f ) => f.parentId === field.id );

	const title = ( field.rowTitle || '' ).trim();

	const dropAt = ( pos ) => {
		clearDropTarget();
		if ( ! drag.value || ! actions ) { return; }
		if ( dragIds().includes( field.id ) ) { return; }

		const lastChildIdx = allFields.reduce(
			( acc, f, i ) => ( f.parentId === field.id ) ? i : acc, -1
		);
		const insertAt = pos >= children.length
			? ( lastChildIdx === -1 ? allFields.length : lastChildIdx + 1 )
			: allFields.indexOf( children[ pos ] );

		if ( drag.value.kind === 'new' ) {
			actions.add( drag.value.type, insertAt, { parentId: field.id } );
		} else if ( drag.value.kind === 'move' ) {
			actions.move( dragIds(), insertAt, { parentId: field.id } );
		}
		drag.value = null;
	};

	return (
		<div className={ `fcf7b-field-wrap fcf7b-field-${ field.id }` }>
			<div className="fcf7-repeater">
				<div className="fcf7-repeater-rows">
					<div className="fcf7-repeater-row">
						<div className="fcf7-repeater-row-head">
							<span className="fcf7-repeater-row-title">{ title ? `${ title } 1` : '' }</span>
							<button type="button" className="fcf7-repeater-remove" disabled={ true }>
								{ field.removeLabel || __( 'Remove', 'compactform' ) }
							</button>
						</div>

						<div className={ `fcf7-repeater-row-body fcf7b-rep-dropbox${ ( ! children.length && overIndex >= 0 ) ? ' is-drop-in' : '' }` }
							onDragOver={ ( e ) => {
								e.preventDefault();
								e.stopPropagation();
								const at = dropPosition( e, e.currentTarget, ROWS );
								setDropTarget( field.id, at, () => dropAt( at ) );
							} }
							onDrop={ ( e ) => { e.preventDefault(); e.stopPropagation(); commitDrop(); } }>
							{ children.length ? <div className="fcf7b-rep-stack">{ children.map( ( child, i ) => {
								const typeDef = BY_TYPE[ child.type ] || { title: child.type, icon: '' };
								const def = INSTANCE_MARKUP[ child.id ] ? { ...typeDef, builderMarkup: INSTANCE_MARKUP[ child.id ] } : typeDef;
								const childInteractions = {
									onChange: ( key, value ) => actions.updateField( child.id, key, value ),
									beginGesture: actions.beginGesture,
									endGesture: actions.endGesture,
									selected,
									multi,
									openMenu,
								};
								return (
									<div key={ child.id }
										className={ `fcf7b-cell fcf7b-rep-cell fcf7b-field-${ child.id }${ overIndex === i ? ' is-drop-before' : '' }${
											( overIndex === i + 1 && i === children.length - 1 ) ? ' is-drop-after' : ''
										}` }
										onDragOver={ ( e ) => {
											e.preventDefault();
											e.stopPropagation();
											const at = i + dropOffset( e, e.currentTarget );
											setDropTarget( field.id, at, () => dropAt( at ) );
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
								<div className="fcf7b-rep-empty">Drag the fields to repeat here</div>
							) }
						</div>
					</div>
				</div>

				<div className="fcf7-repeater-actions">
					<button type="button" className="fcf7-repeater-add" disabled={ true }>
						{ field.addLabel || __( 'Add More', 'compactform' ) }
					</button>
				</div>
			</div>
		</div>
	);
}
