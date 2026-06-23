import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { BY_TYPE, INSTANCE_MARKUP, resolveResponsive, drag, startMoveDrag, dragIds, uid, useDropIndex, setDropTarget, clearDropTarget, commitDrop, dropOffset, dropPosition } from '../shared';
import FieldPreview from '../components/FieldPreview';
import CardHandle from '../components/CardHandle';

const C = ( w, children, dir ) => ( { w: w || 100, children, dir } );

const PRESETS = [
	{ key: 'v', glyph: '↓', dir: 'column', cells: [] },
	{ key: 'h', glyph: '→', dir: 'row', cells: [] },
	{ key: '2', cells: [ C( 50 ), C( 50 ) ] },
	{ key: '3', cells: [ C( 33.33 ), C( 33.33 ), C( 33.33 ) ] },
	{ key: '4', cells: [ C( 25 ), C( 25 ), C( 25 ), C( 25 ) ] },
	{ key: '6', cells: [ C( 16.66 ), C( 16.66 ), C( 16.66 ), C( 16.66 ), C( 16.66 ), C( 16.66 ) ] },
	{ key: '1-2', cells: [ C( 33.33 ), C( 66.66 ) ] },
	{ key: '2-1', cells: [ C( 66.66 ), C( 33.33 ) ] },
	{ key: '1-2-1', cells: [ C( 25 ), C( 50 ), C( 25 ) ] },
	{ key: 'l-rr', cells: [ C( 50 ), C( 50, [ C(), C() ], 'column' ) ] },
	{ key: 'll-r', cells: [ C( 50, [ C(), C() ], 'column' ), C( 50 ) ] },
	{ key: 'tt-b', dir: 'column', cells: [ C( 100, [ C( 50 ), C( 50 ) ], 'row' ), C( 100 ) ] },
];

function Mock( { cells, dir } ) {
	return (
		<span className="fcf7b-ctnpick-box" style={ { flexDirection: dir || 'row' } }>
			{ cells.map( ( c, i ) => {
				const style = { flex: `0 1 ${ c.w }%` };
				return c.children
					? <span key={ i } className="fcf7b-ctnpick-nest" style={ style }>
						<Mock cells={ c.children } dir={ c.dir } />
					</span>
					: <span key={ i } className="fcf7b-ctnpick-cell" style={ style } />;
			} ) }
		</span>
	);
}

export default function ContainerPreview( { field, allFields = [], actions, selected, multi = [], device = 'desktop', openMenu } ) {
	const overIndex = useDropIndex( field.id );
	const [ picked, setPicked ] = useState( false );

	const children = allFields.filter( ( f ) => f.parentId === field.id );
	const dir = field.direction || 'row';
	const axis = dir.indexOf( 'column' ) === 0 ? 'y' : 'x';
	const reversed = dir.indexOf( '-reverse' ) > -1;
	const axisClass = axis === 'x' ? 'is-drop-x' : 'is-drop-y';

	const isSelfOrDescendant = ( nodeId, ofId ) => {
		let cur = allFields.find( ( f ) => f.id === nodeId );
		while ( cur ) {
			if ( cur.id === ofId ) { return true; }
			cur = cur.parentId ? allFields.find( ( f ) => f.id === cur.parentId ) : null;
		}
		return false;
	};

	const flatIndex = ( pos ) => {
		if ( pos < children.length ) { return allFields.indexOf( children[ pos ] ); }
		const lastChildIdx = allFields.reduce(
			( acc, f, i ) => ( f.parentId === field.id ? i : acc ), -1
		);
		return lastChildIdx === -1 ? allFields.length : lastChildIdx + 1;
	};

	// The box otherwise swallows every pixel of the card, so "before this container" was
	// unreachable — worst on the first container in a form, where the only alternative is a
	// sliver of canvas above it. Inside this band the box declines the event and lets it bubble
	// to whatever holds the container (the canvas cell, or the item of a parent container).
	const EDGE = 8;
	const nearEdge = ( e, el ) => {
		const r = el.getBoundingClientRect();
		return ( e.clientY - r.top ) < EDGE || ( r.bottom - e.clientY ) < EDGE
			|| ( e.clientX - r.left ) < EDGE || ( r.right - e.clientX ) < EDGE;
	};

	const dropAt = ( pos ) => {
		clearDropTarget();
		if ( ! drag.value || ! actions ) { return; }

		if ( dragIds().some( ( id ) => isSelfOrDescendant( field.id, id ) ) ) { return; }

		const insertAt = flatIndex( pos );

		if ( drag.value.kind === 'new' ) {
			actions.add( drag.value.type, insertAt, { parentId: field.id, stepId: null } );
		} else {
			actions.move( dragIds(), insertAt, { parentId: field.id, stepId: null } );
		}
		drag.value = null;
	};

	const treeItems = ( cells, parentId ) => {
		const items = [];
		cells.forEach( ( c ) => {
			const id = uid();
			const overrides = {
				id,
				parentId,
				width: { desktop: { size: c.w, unit: '%' } },
			};
			if ( c.children ) { overrides.wrap = { desktop: 'nowrap' }; }
			if ( c.dir ) { overrides.direction = { desktop: c.dir }; }
			items.push( { type: 'container', overrides } );
			if ( c.children ) { items.push( ...treeItems( c.children, id ) ); }
		} );
		return items;
	};

	const applyPreset = ( preset ) => {
		if ( ! actions || ! actions.insert ) { return; }
		const own = {};
		if ( preset.dir ) { own.direction = { desktop: preset.dir }; }
		if ( preset.cells.length ) { own.wrap = { desktop: 'nowrap' }; }
		const patches = Object.keys( own ).length ? { [ field.id ]: own } : null;
		actions.insert( treeItems( preset.cells, field.id ), null, patches );
		setPicked( true );
	};

	const childCard = ( child, i ) => {
		const typeDef = BY_TYPE[ child.type ] || { title: child.type, icon: '' };
		const def = INSTANCE_MARKUP[ child.id ] ? { ...typeDef, builderMarkup: INSTANCE_MARKUP[ child.id ] } : typeDef;
		const rf = resolveResponsive( child, def, device );
		const interactions = {
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
				className={ `fcf7b-field-wrap fcf7b-field-${ child.id } fcf7b-ctn-item ${ axisClass }${
					overIndex === i ? ' is-drop-before' : ''
				}${ ( overIndex === i + 1 && i === children.length - 1 ) ? ' is-drop-after' : '' }` }
				onDragOver={ ( e ) => {
					e.preventDefault();
					e.stopPropagation();
					const at = i + dropOffset( e, e.currentTarget, axis, reversed );
					setDropTarget( field.id, at, () => dropAt( at ) );
				} }
				onDrop={ ( e ) => { e.preventDefault(); e.stopPropagation(); commitDrop(); } }>
				<div className={ `fcf7b-card${ multi.includes( child.id ) ? ' is-selected' : '' }` }
					draggable={ true }
					onClick={ ( e ) => { e.stopPropagation(); actions.select( child.id, e.ctrlKey || e.metaKey ); } }
					onDragStart={ ( e ) => {
						if ( e.target.closest && e.target.closest( 'input, textarea, select' ) ) { e.preventDefault(); return; }
						startMoveDrag( child.id, multi );
						e.stopPropagation();
					} }
					onContextMenu={ ( e ) => {
						e.preventDefault();
						e.stopPropagation();
						if ( ! multi.includes( child.id ) ) { actions.select( child.id ); }
						if ( openMenu ) { openMenu( child.id, e.clientX, e.clientY ); }
					} }>
					<CardHandle field={ child } def={ typeDef } nested={ true } actions={ actions } multi={ multi } openMenu={ openMenu } />
					<div className="fcf7b-card-body">{ FieldPreview( rf, def, device, interactions ) }</div>
				</div>
			</div>
		);
	};

	return (
		<div className={ `fcf7b-field-wrap fcf7b-field-${ field.id }` }>
			<div className={ `fcf7-container fcf7b-ctnbox${ ( ! children.length && overIndex >= 0 ) ? ' is-drop-in' : '' }` }
				onDragOver={ ( e ) => {
					if ( nearEdge( e, e.currentTarget ) ) { return; }
					e.preventDefault();
					e.stopPropagation();
					const at = dropPosition( e, e.currentTarget, ':scope > .fcf7b-ctn-item', axis, reversed );
					setDropTarget( field.id, at, () => dropAt( at ) );
				} }
				onDrop={ ( e ) => {
					if ( nearEdge( e, e.currentTarget ) ) { return; }
					e.preventDefault();
					e.stopPropagation();
					commitDrop();
				} }>
				{ children.length ? children.map( childCard ) : (
					( picked || field.parentId ) ? (
						<button type="button" className="fcf7b-ctn-empty"
							title={ __( 'Drag a field here', 'compactform' ) }
							onClick={ ( e ) => { e.stopPropagation(); actions.select( field.id ); } }>
							<i className="ri-add-line" />
						</button>
					) : (
						<div className="fcf7b-ctnpick" onClick={ ( e ) => e.stopPropagation() }>
							<div className="fcf7b-ctnpick-head">
								<span>{ __( 'Select your structure', 'compactform' ) }</span>
								<button type="button" title={ __( 'Skip — drop fields in yourself', 'compactform' ) }
									onClick={ () => setPicked( true ) }><i className="ri-close-line" /></button>
							</div>
							<div className="fcf7b-ctnpick-grid">
								{ PRESETS.map( ( p ) => (
									<button key={ p.key } type="button" className="fcf7b-ctnpick-item"
										title={ p.glyph
										? ( p.dir === 'column' ? __( 'Vertical', 'compactform' ) : __( 'Horizontal', 'compactform' ) )
										: sprintf(
											/* translators: %d: number of columns in the layout preset. */
											__( '%d columns', 'compactform' ),
											p.cells.length
										) }
										onClick={ () => applyPreset( p ) }>
										{ p.glyph
											? <span className="fcf7b-ctnpick-box fcf7b-ctnpick-glyph">{ p.glyph }</span>
											: <Mock cells={ p.cells } dir={ p.dir } /> }
									</button>
								) ) }
							</div>
						</div>
					)
				) }
			</div>
		</div>
	);
}
