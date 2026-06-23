import { useState, useEffect, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { BY_TYPE, drag, startMoveDrag, dragIds, clearDropTarget } from '../shared';
import ContextMenu from './ContextMenu';

const BOX_KEY = 'fcf7b-nav-box';
const MIN_W = 220;
const MIN_H = 180;

const stepsOf = ( field ) => (
	Array.isArray( field.steps ) && field.steps.length
		? field.steps
		: [ { id: 'step_1', label: sprintf( /* translators: %d: step number. */ __( 'Step %d', 'compactform' ), 1 ) } ]
);

const isContainerType = ( type ) => 'container' === type || 'repeater' === type || 'multistep' === type;

function readBox() {
	try {
		const raw = JSON.parse( window.localStorage.getItem( BOX_KEY ) );
		if ( raw && typeof raw === 'object' ) { return raw; }
	} catch ( e ) { /* skip */ }
	return null;
}

function clampBox( b ) {
	const pad = 8;
	const w = Math.min( Math.max( MIN_W, b.w || MIN_W ), window.innerWidth - pad * 2 );
	const h = Math.min( Math.max( MIN_H, b.h || MIN_H ), window.innerHeight - pad * 2 );
	return {
		w,
		h,
		x: Math.max( pad, Math.min( b.x || 0, window.innerWidth - w - pad ) ),
		y: Math.max( pad, Math.min( b.y || 0, window.innerHeight - h - pad ) ),
	};
}

export default function Navigator( { fields, selected, multi = [], actions, onClose } ) {
	const [ box, setBox ] = useState( () => clampBox( readBox() || { x: window.innerWidth - 360, y: 120, w: 300, h: 420 } ) );
	const [ collapsed, setCollapsed ] = useState( () => new Set() );
	const [ hint, setHint ] = useState( null );
	const [ menu, setMenu ] = useState( null );
	const gesture = useRef( null );

	useEffect( () => {
		try { window.localStorage.setItem( BOX_KEY, JSON.stringify( box ) ); } catch ( e ) {}
	}, [ box ] );

	const byId = new Map( fields.map( ( f ) => [ f.id, f ] ) );

	const ancestors = ( id ) => {
		const out = [];
		let cur = byId.get( id );
		while ( cur && cur.parentId ) {
			out.push( cur.parentId );
			cur = byId.get( cur.parentId );
		}
		return out;
	};

	useEffect( () => {
		if ( ! selected ) { return; }
		const anc = ancestors( selected );
		if ( ! anc.length ) { return; }
		setCollapsed( ( prev ) => {
			if ( ! anc.some( ( a ) => prev.has( a ) ) ) { return prev; }
			const next = new Set( prev );
			anc.forEach( ( a ) => next.delete( a ) );
			return next;
		} );
	}, [ selected ] );

	// Panel move / resize
	const startGesture = ( kind ) => ( e ) => {
		if ( e.button ) { return; }
		e.preventDefault();
		e.currentTarget.setPointerCapture( e.pointerId );
		gesture.current = { kind, sx: e.clientX, sy: e.clientY, ...box };
	};

	const onPointerMove = ( e ) => {
		const g = gesture.current;
		if ( ! g ) { return; }
		const dx = e.clientX - g.sx;
		const dy = e.clientY - g.sy;
		if ( 'move' === g.kind ) {
			setBox( { ...box, x: g.x + dx, y: g.y + dy } );
		} else {
			setBox( { ...box, w: Math.max( MIN_W, g.w + dx ), h: Math.max( MIN_H, g.h + dy ) } );
		}
	};

	const endGesture = ( e ) => {
		if ( gesture.current ) {
			try { e.currentTarget.releasePointerCapture( e.pointerId ); } catch ( err ) {}
			gesture.current = null;
		}
	};

	// Canvas cross-highlight
	const canvasEl = ( id ) => document.querySelector( `.fcf7b-field-${ id }` );

	const peek = ( id, on ) => {
		const el = canvasEl( id );
		if ( el ) { el.classList.toggle( 'fcf7b-nav-peek', on ); }
	};

	const pick = ( id, additive ) => {
		actions.select( id, additive );
		if ( additive ) { return; }
		const el = canvasEl( id );
		if ( el ) { el.scrollIntoView( { block: 'nearest', behavior: 'smooth' } ); }
	};

	const toggle = ( id ) => setCollapsed( ( prev ) => {
		const next = new Set( prev );
		if ( next.has( id ) ) { next.delete( id ); } else { next.add( id ); }
		return next;
	} );

	// Drop maths
	const siblings = ( parentId, stepId ) => {
		if ( ! parentId ) { return fields.filter( ( f ) => ! f.parentId ); }
		const parent = byId.get( parentId );
		if ( parent && 'multistep' === parent.type ) {
			const steps = stepsOf( parent );
			const ids = new Set( steps.map( ( s ) => s.id ) );
			const isFirst = stepId === steps[ 0 ].id;
			return fields.filter( ( f ) => f.parentId === parentId && ( f.stepId === stepId || ( isFirst && ! ids.has( f.stepId ) ) ) );
		}
		return fields.filter( ( f ) => f.parentId === parentId );
	};

	const flatIndex = ( parentId, stepId, pos ) => {
		const sibs = siblings( parentId, stepId );
		if ( pos < sibs.length ) { return fields.indexOf( sibs[ pos ] ); }
		if ( sibs.length ) { return fields.indexOf( sibs[ sibs.length - 1 ] ) + 1; }
		const pi = parentId ? fields.findIndex( ( f ) => f.id === parentId ) : -1;
		return pi === -1 ? fields.length : pi + 1;
	};

	/** Is `nodeId` `ofId` itself, or anywhere beneath it? Guards a drop into self. */
	const isSelfOrDescendant = ( nodeId, ofId ) => {
		let cur = byId.get( nodeId );
		while ( cur ) {
			if ( cur.id === ofId ) { return true; }
			cur = cur.parentId ? byId.get( cur.parentId ) : null;
		}
		return false;
	};

	const drop = ( node, where ) => {
		setHint( null );
		clearDropTarget();
		if ( ! drag.value ) { return; }

		let parentId;
		let stepId;
		let pos;

		if ( 'inside' === where ) {
			parentId = node.id;
			stepId = 'multistep' === node.type ? stepsOf( node )[ 0 ].id : null;
			pos = siblings( parentId, stepId ).length;
		} else {
			parentId = node.parentId || null;
			stepId = node.stepId || null;
			const sibs = siblings( parentId, stepId );
			const at = sibs.findIndex( ( f ) => f.id === node.id );
			pos = ( at === -1 ? sibs.length : at ) + ( 'after' === where ? 1 : 0 );
		}

		if ( parentId && dragIds().some( ( id ) => isSelfOrDescendant( parentId, id ) ) ) {
			drag.value = null;
			return;
		}

		const patch = { parentId, stepId };
		const index = flatIndex( parentId, stepId, pos );
		if ( 'new' === drag.value.kind ) { actions.add( drag.value.type, index, patch ); }
		else if ( 'move' === drag.value.kind ) { actions.move( dragIds(), index, patch ); }
		drag.value = null;
	};

	const zoneAt = ( e, el, node ) => {
		const r = el.getBoundingClientRect();
		const p = ( e.clientY - r.top ) / ( r.height || 1 );
		if ( isContainerType( node.type ) ) {
			if ( p < 0.3 ) { return 'before'; }
			if ( p > 0.7 ) { return 'after'; }
			return 'inside';
		}
		return p > 0.5 ? 'after' : 'before';
	};

	// Rows
	const row = ( node, depth ) => {
		const def = BY_TYPE[ node.type ] || { title: node.type, icon: 'ri-square-line' };
		const kids = fields.filter( ( f ) => f.parentId === node.id );
		const hasKids = !! kids.length || 'multistep' === node.type;
		const open = ! collapsed.has( node.id );
		const label = ( node.label || node.name || def.title || node.type ).toString();
		const lit = hint && hint.id === node.id ? ` is-drop-${ hint.where }` : '';

		return (
			<div key={ node.id } className="fcf7b-nav-node">
				<div
					className={ `fcf7b-nav-row${ multi.includes( node.id ) ? ' is-selected' : '' }${ lit }` }
					style={ { paddingLeft: 6 + depth * 14 } }
					draggable={ true }
					onDragStart={ ( e ) => { startMoveDrag( node.id, multi ); e.stopPropagation(); } }
					onDragEnd={ () => setHint( null ) }
					onDragOver={ ( e ) => {
						e.preventDefault();
						e.stopPropagation();
						if ( ! drag.value ) { return; }
						setHint( { id: node.id, where: zoneAt( e, e.currentTarget, node ) } );
					} }
					onDrop={ ( e ) => {
						e.preventDefault();
						e.stopPropagation();
						drop( node, zoneAt( e, e.currentTarget, node ) );
					} }
					onClick={ ( e ) => pick( node.id, e.ctrlKey || e.metaKey ) }
					onContextMenu={ ( e ) => {
						e.preventDefault();
						e.stopPropagation();
						peek( node.id, false );
						if ( ! multi.includes( node.id ) ) { actions.select( node.id ); }
						setMenu( { id: node.id, x: e.clientX, y: e.clientY, ids: actions.targetIds( node.id ) } );
					} }
					onMouseEnter={ () => peek( node.id, true ) }
					onMouseLeave={ () => peek( node.id, false ) }
				>
					<button
						type="button"
						className={ `fcf7b-nav-caret${ hasKids ? '' : ' is-empty' }` }
						tabIndex={ hasKids ? 0 : -1 }
						onClick={ ( e ) => { e.stopPropagation(); if ( hasKids ) { toggle( node.id ); } } }
					>{ hasKids ? <i className={ open ? 'ri-arrow-down-s-line' : 'ri-arrow-right-s-line' } /> : null }</button>
					<i className={ `fcf7b-nav-icon ${ def.icon || 'ri-square-line' }` } />
					<span className="fcf7b-nav-label" title={ `${ label } · ${ def.title || node.type }` }>{ label }</span>
					<span className="fcf7b-nav-acts">
						<button type="button" title={ __( 'Duplicate', 'compactform' ) } onClick={ ( e ) => { e.stopPropagation(); actions.duplicate( node.id ); } }>
							<i className="ri-file-copy-line" />
						</button>
						<button type="button" className="danger" title={ __( 'Delete', 'compactform' ) } onClick={ ( e ) => { e.stopPropagation(); peek( node.id, false ); actions.remove( node.id ); } }>
							<i className="ri-delete-bin-line" />
						</button>
					</span>
				</div>

				{ open && 'multistep' === node.type && (
					stepsOf( node ).map( ( s, si ) => {
						const ids = new Set( stepsOf( node ).map( ( x ) => x.id ) );
						const isFirst = 0 === si;
						const inStep = fields.filter( ( f ) => f.parentId === node.id
							&& ( f.stepId === s.id || ( isFirst && ! ids.has( f.stepId ) ) ) );
						return (
							<div key={ s.id } className="fcf7b-nav-node">
								<div className="fcf7b-nav-row is-step" style={ { paddingLeft: 6 + ( depth + 1 ) * 14 } }
									onDragOver={ ( e ) => { e.preventDefault(); e.stopPropagation(); } }
									onDrop={ ( e ) => {
										e.preventDefault();
										e.stopPropagation();
										setHint( null );
										clearDropTarget();
										if ( ! drag.value ) { return; }
										const index = flatIndex( node.id, s.id, inStep.length );
										const patch = { parentId: node.id, stepId: s.id };
										if ( 'new' === drag.value.kind ) { actions.add( drag.value.type, index, patch ); }
										else if ( ! dragIds().some( ( id ) => isSelfOrDescendant( node.id, id ) ) ) { actions.move( dragIds(), index, patch ); }
										drag.value = null;
									} }>
									<span className="fcf7b-nav-caret is-empty" />
									<i className="fcf7b-nav-icon ri-stack-line" />
									<span className="fcf7b-nav-label">{ s.label || `Step ${ si + 1 }` }</span>
								</div>
								{ inStep.map( ( c ) => row( c, depth + 2 ) ) }
							</div>
						);
					} )
				) }

				{ open && 'multistep' !== node.type && kids.map( ( c ) => row( c, depth + 1 ) ) }
			</div>
		);
	};

	const roots = fields.filter( ( f ) => ! f.parentId );

	return (
		<div
			className="fcf7b-nav"
			style={ { left: box.x, top: box.y, width: box.w, height: box.h } }
			onClick={ ( e ) => e.stopPropagation() }
			onPointerMove={ onPointerMove }
			onPointerUp={ endGesture }
		>
			<div className="fcf7b-nav-head" onPointerDown={ startGesture( 'move' ) }>
				<i className="ri-stack-line" />
				<span>{ __( 'Navigator', 'compactform' ) }</span>
				<button type="button" title={ __( 'Close', 'compactform' ) } onPointerDown={ ( e ) => e.stopPropagation() } onClick={ onClose }>
					<i className="ri-close-line" />
				</button>
			</div>

			<div className="fcf7b-nav-body"
				onDragOver={ ( e ) => { e.preventDefault(); setHint( null ); } }
				onDrop={ ( e ) => {
					e.preventDefault();
					clearDropTarget();
					if ( ! drag.value ) { return; }
					const patch = { parentId: null, stepId: null };
					if ( 'new' === drag.value.kind ) { actions.add( drag.value.type, fields.length, patch ); }
					else if ( 'move' === drag.value.kind ) { actions.move( dragIds(), fields.length, patch ); }
					drag.value = null;
				} }>
				{ roots.length
					? roots.map( ( f ) => row( f, 0 ) )
					: <div className="fcf7b-nav-empty">No fields yet.</div> }
			</div>

			<span className="fcf7b-nav-resize" onPointerDown={ startGesture( 'resize' ) } />

			<ContextMenu menu={ menu } fields={ fields } actions={ actions } onClose={ () => setMenu( null ) } />
		</div>
	);
}
