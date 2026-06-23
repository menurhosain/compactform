import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { drag, startMoveDrag, dragIds, BY_TYPE, INSTANCE_MARKUP, resolveResponsive, previewWidth, useDropIndex, setDropTarget, clearDropTarget, commitDrop, dropOffset, deviceChain, isEmptyValue } from '../shared';
import { hooks } from '../field-preview-registry';
import { buildCanvasCss, sides as dimensionsCss } from '../css-builder';
import FieldPreview from './FieldPreview';
import CardHandle from './CardHandle';
import ContextMenu from './ContextMenu';
import { isCanvasBg } from './CanvasBackground';

export default function Canvas( { fields, selected, multi = [], actions, device = 'desktop', breakpoints = {}, background = '', fieldGap, containerColumnGap, containerRowGap, containerPadding } ) {
	
	const overIndex = useDropIndex( 'root' );
	const [ menu, setMenu ] = useState( null ); // { x, y, id, ids } for the right-click menu

	const openMenu = ( id, x, y ) => setMenu( { id, x, y, ids: id ? actions.targetIds( id ) : [] } );

	const TOP_LEVEL = { parentId: null, stepId: null };

	const gapFor = ( value ) => {
		if ( value === undefined || value === null || '' === value ) { return undefined; }
		if ( 'object' !== typeof value ) { return value; }
		const hit = deviceChain( device ).find( ( d ) => value[ d ] !== undefined && '' !== value[ d ] );
		return hit === undefined ? undefined : value[ hit ];
	};
	const gap = gapFor( fieldGap );

	const containerGapCss = [
		[ 'column-gap', gapFor( containerColumnGap ) ],
		[ 'row-gap', gapFor( containerRowGap ) ],
	]
		.filter( ( [ , v ] ) => v !== undefined )
		.map( ( [ prop, v ] ) => `${ prop }:${ v }px` )
		.join( ';' );

	const padDevice = deviceChain( device ).find( ( d ) => containerPadding && ! isEmptyValue( containerPadding[ d ] ) );
	const containerPadCss = padDevice
		? dimensionsCss( containerPadding[ padDevice ], 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' )
		: '';

	const containerCss = [ containerGapCss, containerPadCss ].filter( Boolean ).join( ';' );

	const onDropAt = ( index ) => {
		clearDropTarget();
		if ( ! drag.value ) { return; }
		if ( drag.value.kind === 'new' ) { actions.add( drag.value.type, index, TOP_LEVEL ); }
		else if ( drag.value.kind === 'move' ) { actions.move( dragIds(), index, TOP_LEVEL ); }
		drag.value = null;
	};

	// The gutters — canvas padding, the strip above the first card, anything a cell does not
	// own. Answers the same before/after midpoint the cells do, against the nearest one, so a
	// drag that never touches a card still points somewhere real instead of at the end.
	const rootIndexAt = ( wrap, clientY ) => {
		const row = wrap.querySelector( '.fcf7b-canvas-row' );
		if ( ! row ) { return fields.length; }
		const cells = Array.from( row.querySelectorAll( ':scope > .fcf7b-cell' ) );
		for ( let n = 0; n < cells.length; n++ ) {
			const r = cells[ n ].getBoundingClientRect();
			if ( clientY < r.top + r.height / 2 ) { return Number( cells[ n ].dataset.index ); }
		}
		return fields.length;
	};

	const cards = fields.map( ( f, i ) => {
		if ( f.parentId ) { return null; }

		const typeDef = BY_TYPE[ f.type ] || { title: f.type, icon: '' };
		const def = INSTANCE_MARKUP[ f.id ] ? { ...typeDef, builderMarkup: INSTANCE_MARKUP[ f.id ] } : typeDef;
		const interactions = {
			onChange: ( key, value ) => actions.updateField( f.id, key, value ),
			beginGesture: actions.beginGesture,
			endGesture: actions.endGesture,
			allFields: fields,
			actions,
			selected,
			multi,
			openMenu,
		};
		const rf = resolveResponsive( f, def, device );
		return (
			<div key={ f.id }
				className={ `fcf7b-cell fcf7b-field-${ f.id }${ overIndex === i ? ' is-drop-before' : '' }${
					( overIndex === i + 1 && ( i + 1 >= fields.length || fields[ i + 1 ].parentId ) ) ? ' is-drop-after' : ''
				}` }
				data-index={ i }
				onDragOver={ ( e ) => {
					e.preventDefault();
					e.stopPropagation();
					const at = i + dropOffset( e, e.currentTarget, 'y' );
					setDropTarget( 'root', at, () => onDropAt( at ) );
				} }
				onDrop={ ( e ) => { e.preventDefault(); e.stopPropagation(); commitDrop(); } }>
				<div
					className={ `fcf7b-card${ multi.includes( f.id ) ? ' is-selected' : '' } ${ hooks.applyFilters( 'fcf7b.cardClassName', '', f ) }`.trim() }
					draggable={ true }
					onDragStart={ ( e ) => {
						if ( e.target.closest && e.target.closest( 'input, textarea, select' ) ) { e.preventDefault(); return; }
						startMoveDrag( f.id, multi ); e.stopPropagation();
					} }
					onClick={ ( e ) => { e.stopPropagation(); actions.select( f.id, e.ctrlKey || e.metaKey ); } }
					onContextMenu={ ( e ) => {
						e.preventDefault();
						e.stopPropagation();
						if ( ! multi.includes( f.id ) ) { actions.select( f.id ); }
						openMenu( f.id, e.clientX, e.clientY );
					} }>
					<CardHandle
						field={ f }
						def={ def }
						nested={ false }
						actions={ actions }
						multi={ multi }
						openMenu={ openMenu }
						onAdd={ () => actions.add( 'container', i + 1, TOP_LEVEL ) }
					/>
					<div className="fcf7b-card-body">{ FieldPreview( rf, def, device, interactions ) }</div>
				</div>
			</div>
		);
	} );

	return (
		<div className={ `fcf7b-canvas-wrap is-${ device }` }
			style={ isCanvasBg( background ) ? { background } : null }
			onClick={ () => actions.select( null ) }
			onClickCapture={ ( e ) => {
				const a = e.target.closest && e.target.closest( 'a[href]' );
				if ( a ) { e.preventDefault(); }
			} }
			onContextMenu={ ( e ) => { e.preventDefault(); openMenu( null, e.clientX, e.clientY ); } }
			onDragOver={ ( e ) => {
				e.preventDefault();
				const at = rootIndexAt( e.currentTarget, e.clientY );
				setDropTarget( 'root', at, () => onDropAt( at ) );
			} }
			onDrop={ ( e ) => { e.preventDefault(); if ( ! commitDrop() ) { onDropAt( fields.length ); } } }>
			{ fields.length ? (
				<div className="fcf7b-canvas-grid wpcf7" style={ previewWidth( device, breakpoints ) ? { maxWidth: previewWidth( device, breakpoints ) + 'px' } : null }>
					<style>
						{ containerCss ? `.fcf7b-canvas-row .fcf7-container{${ containerCss }}\n` : '' }
						{ buildCanvasCss( fields, device ) }
					</style>
					<div className="fcf7b-canvas-row" style={ gap === undefined ? null : { '--fcf7b-cell-gap': gap + 'px' } }>{ cards }</div>
				</div>
			) : (
				<div className={ `fcf7b-empty${ overIndex >= 0 ? ' is-drop-in' : '' }` }>
					<div className="fcf7b-empty-emoji">{ '🧩' }</div>
					<div>{ __( 'Drag a field here, or click one on the left to add it.', 'compactform' ) }</div>
				</div>
			) }

			<ContextMenu menu={ menu } fields={ fields } actions={ actions } onClose={ () => setMenu( null ) } />
		</div>
	);
}
