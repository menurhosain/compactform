import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Popover from './Popover';

export default function Select( { value, options = [], onChange, multiple = false, creatable = false, onCreate, onRemove, onReorder, placeholder = '' } ) {
	const selected = multiple
		? ( Array.isArray( value ) ? value : ( value === '' || value === undefined || value === null ? [] : [ value ] ) )
		: [];
	const isSel = ( v ) => multiple
		? selected.some( ( s ) => String( s ) === String( v ) )
		: String( value ) === String( v );

	const [ dragIdx, setDragIdx ] = useState( -1 );
	const [ overIdx, setOverIdx ] = useState( -1 );
	const customVals = options.filter( ( o ) => o.custom ).map( ( o ) => o.value );

	const dropTo = ( to ) => {
		if ( dragIdx >= 0 && dragIdx !== to && onReorder ) {
			const next = [ ...customVals ];
			const [ moved ] = next.splice( dragIdx, 1 );
			next.splice( to, 0, moved );
			onReorder( next );
		}
		setDragIdx( -1 );
		setOverIdx( -1 );
	};

	let label;
	if ( multiple ) {
		const chosen = options.filter( ( o ) => isSel( o.value ) ).map( ( o ) => o.label );
		label = chosen.length ? chosen.join( ', ' ) : ( placeholder || __( 'Select…', 'compactform' ) );
	} else {
		const current = options.find( ( o ) => String( o.value ) === String( value ) );
		label = current ? current.label : ( placeholder || ( options[ 0 ] && options[ 0 ].label ) || '' );
	}

	const pick = ( v, close ) => {
		if ( multiple ) {
			onChange( isSel( v )
				? selected.filter( ( s ) => String( s ) !== String( v ) )
				: [ ...selected, v ] );
		} else {
			onChange( v );
			close();
		}
	};

	return (
		<Popover
			className="fcf7b-select"
			triggerClassName={ `fcf7b-select-trigger${ multiple ? ' is-multi' : '' }` }
			popClassName="fcf7b-select-pop"
			trigger={ (
				<>
					<span className="fcf7b-select-cur">{ label }</span>
					<i className="ri-arrow-down-s-line fcf7b-select-caret" />
				</>
			) }
		>
			{ ( { close } ) => (
				<ul className={ `fcf7b-select-list${ multiple ? ' is-multi' : '' }` }>
					{ options.map( ( o ) => {
						const isCustom = creatable && o.custom;
						const ci = isCustom ? customVals.indexOf( o.value ) : -1;
						return (
						<li
							key={ String( o.value ) }
							className={ `${ isCustom ? 'fcf7b-select-hasremove' : '' }${ isCustom && overIdx === ci ? ' is-drop' : '' }`.trim() || undefined }
							onDragOver={ isCustom ? ( e ) => { e.preventDefault(); setOverIdx( ci ); } : undefined }
							onDrop={ isCustom ? ( e ) => { e.preventDefault(); dropTo( ci ); } : undefined }
						>
							{ isCustom ? (
								<span
									className="fcf7b-select-grip"
									title={ __( 'Drag to reorder', 'compactform' ) }
									draggable
									onDragStart={ () => setDragIdx( ci ) }
									onDragEnd={ () => { setDragIdx( -1 ); setOverIdx( -1 ); } }
								>
									<i className="ri-draggable" />
								</span>
							) : null }
							<button
								type="button"
								className={ `fcf7b-select-opt${ isSel( o.value ) ? ' is-active' : '' }` }
								onClick={ () => pick( o.value, close ) }
							>
								{ multiple ? (
									<i className={ `fcf7b-select-check ri-${ isSel( o.value ) ? 'checkbox-line' : 'checkbox-blank-line' }` } />
								) : null }
								<span className="fcf7b-select-optlabel">{ o.label }</span>
							</button>
							{ isCustom ? (
								<button
									type="button"
									className="fcf7b-select-remove"
									title={ __( 'Remove', 'compactform' ) }
									onClick={ ( e ) => { e.stopPropagation(); onRemove && onRemove( o.value ); } }
								>
									<i className="ri-close-line" />
								</button>
							) : null }
						</li>
						);
					} ) }
					{ creatable ? (
						<li className="fcf7b-select-create">
							<input
								type="text"
								className="fcf7b-select-createinput"
								placeholder={ __( 'Add…', 'compactform' ) }
								onClick={ ( e ) => e.stopPropagation() }
								onKeyDown={ ( e ) => {
									if ( 'Enter' === e.key ) {
										e.preventDefault();
										if ( onCreate ) { onCreate( e.currentTarget.value ); }
										e.currentTarget.value = '';
										if ( ! multiple ) { close(); }
									}
								} }
							/>
						</li>
					) : null }
				</ul>
			) }
		</Popover>
	);
}
