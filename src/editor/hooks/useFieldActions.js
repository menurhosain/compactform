import { useState } from '@wordpress/element';
import { makeField, uid, drag, topMostIds } from '../shared';
import { hooks } from '../field-preview-registry';

export default function useFieldActions( schema, setSchema ) {
	const [ selected, setSelected ] = useState( null );
	const [ multi, setMulti ] = useState( [] );

	const setFields = ( fields ) => setSchema( { ...schema, fields } );

	const updateField = ( id, key, value ) => {
		const patch = ( key && typeof key === 'object' ) ? key : { [ key ]: value };
		setFields( schema.fields.map( ( f ) => f.id === id ? { ...f, ...patch } : f ) );
	};

	// Collapse the selection to one field — what every insert path (add, insert, duplicate, paste) means by "select the thing I just made".
	const selectOnly = ( id ) => { setSelected( id ); setMulti( id ? [ id ] : [] ); };

	const actions = {
		select( id, additive ) {
			if ( ! additive || ! id ) { selectOnly( id ); return; }
			const has = multi.includes( id );
			const next = has ? multi.filter( ( x ) => x !== id ) : [ ...multi, id ];
			setMulti( next );
			setSelected( has ? ( next[ next.length - 1 ] || null ) : id );
		},

		targetIds( id ) {
			return ( id && multi.length > 1 && multi.includes( id ) ) ? multi.slice() : [ id ];
		},

		add( type, index, overrides ) {
			let f = overrides ? { ...makeField( type ), ...overrides } : makeField( type );
			if ( ! f || ! f.type ) { return null; }
			f = hooks.applyFilters( 'fcf7b.newField', f, drag.value );
			const fields = schema.fields.slice();
			const at = ( index == null || index > fields.length ) ? fields.length : index;
			fields.splice( at, 0, f );
			setFields( fields );
			selectOnly( f.id );
			return f;
		},

		remove( id ) { actions.removeMany( [ id ] ); },

		removeMany( ids ) {
			const doomed = new Set( ( ids || [] ).filter( Boolean ) );
			if ( ! doomed.size ) { return; }
			let grew = true;
			while ( grew ) {
				grew = false;
				schema.fields.forEach( ( f ) => {
					if ( f.parentId && doomed.has( f.parentId ) && ! doomed.has( f.id ) ) {
						doomed.add( f.id );
						grew = true;
					}
				} );
			}

			setFields( schema.fields.filter( ( f ) => ! doomed.has( f.id ) ) );
			const left = multi.filter( ( x ) => ! doomed.has( x ) );
			setMulti( left );
			if ( doomed.has( selected ) ) { setSelected( left[ left.length - 1 ] || null ); }
		},

		insert( items, index, patches ) {
			const made = ( items || [] ).map( ( item ) => {
				const f = makeField( item.type );
				return f ? { ...f, ...( item.overrides || {} ) } : null;
			} ).filter( Boolean );
			if ( ! made.length && ! patches ) { return []; }

			let fields = schema.fields.slice();
			if ( patches ) {
				fields = fields.map( ( f ) => ( patches[ f.id ] ? { ...f, ...patches[ f.id ] } : f ) );
			}
			if ( made.length ) {
				const at = ( index == null || index > fields.length ) ? fields.length : index;
				fields.splice( at, 0, ...made );
			}
			setFields( fields );
			if ( made.length ) { selectOnly( made[ 0 ].id ); }
			return made;
		},

		duplicate( id ) { actions.duplicateMany( [ id ] ); },

		duplicateMany( ids ) {
			const fields = schema.fields.slice();
			const roots = topMostIds( fields, ( ids || [] ).filter( Boolean ) )
				.sort( ( a, b ) => fields.findIndex( ( f ) => f.id === a ) - fields.findIndex( ( f ) => f.id === b ) );
			const made = [];
			const block = [];
			let end = -1;

			roots.forEach( ( id ) => {
				const i = fields.findIndex( ( f ) => f.id === id );
				if ( i === -1 ) { return; }

				const idMap = { [ id ]: uid() };
				const subtree = [];
				const collect = ( parentId ) => {
					fields.forEach( ( f ) => {
						if ( f.parentId !== parentId || idMap[ f.id ] ) { return; }
						idMap[ f.id ] = uid();
						subtree.push( f );
						collect( f.id );
					} );
				};
				collect( id );

				const clone = ( f ) => {
					const c = JSON.parse( JSON.stringify( f ) );
					c.id = idMap[ f.id ];
					if ( c.parentId && idMap[ c.parentId ] ) { c.parentId = idMap[ c.parentId ]; }
					if ( c.name ) { c.name += '-copy'; }
					return c;
				};

				// Root position only — descendants may sit far away in the flat array after a drag-move
				// (move() relocates only the dragged root, not its children), but their relative order
				// among themselves survives via subtree's DFS order regardless of flat contiguity.
				end = Math.max( end, i );

				const copies = [ clone( fields[ i ] ), ...subtree.map( clone ) ];
				block.push( ...copies );
				made.push( copies[ 0 ].id );
			} );

			if ( ! block.length ) { return; }

			fields.splice( end + 1, 0, ...block );
			setFields( fields );
			setMulti( made );
			setSelected( made[ made.length - 1 ] );
		},

		paste( clip, targetId ) {
			const src = ( clip && clip.fields ) || [];
			if ( ! src.length ) { return; }

			const fields = schema.fields.slice();
			const i = targetId ? fields.findIndex( ( f ) => f.id === targetId ) : -1;
			const target = i === -1 ? null : fields[ i ];

			// Pasting onto a Container/Repeater/Multistep means "inside it", the way a
			// drop onto one does — anything else means "next to the thing I right-clicked".
			const into = target && [ 'container', 'repeater', 'multistep' ].includes( target.type );
			const intoStepId = into && 'multistep' === target.type
				? ( ( Array.isArray( target.steps ) && target.steps[ 0 ] && target.steps[ 0 ].id ) || 'step_1' )
				: null;

			const idMap = {};
			src.forEach( ( f ) => { idMap[ f.id ] = uid(); } );
			const rootIds = new Set( ( clip.roots && clip.roots.length ) ? clip.roots : [ src[ 0 ].id ] );

			const copies = src.map( ( f ) => {
				const c = JSON.parse( JSON.stringify( f ) );
				c.id = idMap[ f.id ];
				if ( rootIds.has( f.id ) ) {
					if ( into ) {
						c.parentId = target.id;
						c.stepId = intoStepId;
					} else {
						c.parentId = target ? ( target.parentId || null ) : null;
						c.stepId = target ? ( target.stepId || null ) : null;
					}
				} else if ( c.parentId && idMap[ c.parentId ] ) {
					c.parentId = idMap[ c.parentId ];
				}
				if ( c.name ) { c.name += '-copy'; }
				return c;
			} );

			let at = i === -1 ? fields.length : i + 1;
			if ( into ) {
				// After the container's (or, for a multistep, the target step's) last direct
				// child, so the new one lands at the end of it.
				const last = fields.reduce( ( acc, f, n ) => (
					f.parentId === target.id && ( 'multistep' !== target.type || f.stepId === intoStepId ) ? n : acc
				), -1 );
				at = last === -1 ? i + 1 : last + 1;
			}

			fields.splice( at, 0, ...copies );
			setFields( fields );
			const pastedRoots = copies.filter( ( c, n ) => rootIds.has( src[ n ].id ) ).map( ( c ) => c.id );
			setMulti( pastedRoots );
			setSelected( pastedRoots[ 0 ] || copies[ 0 ].id );
		},

		move( idOrIds, index, patch ) {
			let fields = schema.fields.slice();
			const roots = topMostIds( fields, ( Array.isArray( idOrIds ) ? idOrIds : [ idOrIds ] ).filter( Boolean ) )
				.sort( ( a, b ) => fields.findIndex( ( f ) => f.id === a ) - fields.findIndex( ( f ) => f.id === b ) );
			if ( ! roots.length ) { return; }

			const moving = new Set( roots );
			const items = fields.filter( ( f ) => moving.has( f.id ) );
			const before = fields.filter( ( f, i ) => moving.has( f.id ) && i < index ).length;

			fields = fields.filter( ( f ) => ! moving.has( f.id ) );
			let at = index - before;
			if ( at < 0 ) { at = 0; }
			if ( at > fields.length ) { at = fields.length; }
			fields.splice( at, 0, ...items.map( ( it ) => ( patch ? { ...it, ...patch } : it ) ) );
			setFields( fields );
		},
		updateField,
		update( key, value ) {
			updateField( selected, key, value );
		}
	};

	return { selected, multi, actions };
}
