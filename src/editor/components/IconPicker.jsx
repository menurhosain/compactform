import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ICON_CACHE, ICON_INITIAL_SIZE, iconFetch } from '../shared';
import Popover from './Popover';

export default function IconPicker( { value, onChange } ) {
	const [ query, setQuery ] = useState( '' );
	const [ state, setState ] = useState( () => ICON_CACHE[ '' ] || { icons: [], offset: 0, hasMore: true, total: 0 } );
	const [ loading, setLoading ] = useState( '' ); // '' | 'reset' | 'more'
	const busy = useRef( false );
	const reqRef = useRef( 0 );
	const term = query.trim().toLowerCase();

	const fetchPage = ( search, offset, reset ) => {
		if ( ! reset && busy.current ) { return; }
		busy.current = true;
		const my = ++reqRef.current;
		setLoading( reset ? 'reset' : 'more' );
		iconFetch( search, offset, reset ? ICON_INITIAL_SIZE : undefined )
			.then( ( d ) => {
				if ( my !== reqRef.current ) { return; }
				if ( ! d ) { setState( ( s ) => ( { ...s, hasMore: false } ) ); return; }
				setState( ( s ) => {
					const next = { icons: ( reset ? [] : s.icons ).concat( d.icons || [] ), offset: d.nextOffset, hasMore: !! d.hasMore, total: d.total || 0 };
					ICON_CACHE[ search ] = next;
					return next;
				} );
			} )
			.then( () => {
				if ( my !== reqRef.current ) { return; }
				busy.current = false;
				setLoading( '' );
			} );
	};

	useEffect( () => {
		const cached = ICON_CACHE[ term ];
		if ( cached?.icons.length ) { setState( cached ); setLoading( '' ); return; }
		setLoading( 'reset' );
		const t = setTimeout( () => fetchPage( term, 0, true ), term ? 250 : 0 );
		return () => clearTimeout( t );
	}, [ term ] );

	const onScroll = ( e ) => {
		if ( busy.current || ! state.hasMore ) { return; }
		const box = e.currentTarget;
		if ( box.scrollTop + box.clientHeight >= box.scrollHeight - 48 ) { fetchPage( term, state.offset, false ); }
	};

	return (
		<Popover
			className="fcf7b-iconpick"
			triggerClassName={ `fcf7b-iconpick-trigger${ value ? ' is-set' : '' }` }
			popClassName="fcf7b-iconpick-pop"
			title={ __( 'Choose Icon', 'compactform' ) }
			trigger={ value ? (
				<i className={ value } />
			) : (
				<span className="fcf7b-iconpick-placeholder">{ __( 'Choose', 'compactform' ) }</span>
			) }
		>
			<div className="fcf7b-pop-head">
				<span>{ __( 'Icon', 'compactform' ) }</span>
				<div className="fcf7b-pop-tools">
					{ value ? (
						<button type="button" title={ __( 'Reset', 'compactform' ) } onClick={ () => onChange( '' ) }>
							<i className="ri-refresh-line" />
						</button>
					) : null }
				</div>
			</div>

			<input
				className="fcf7b-iconpick-search"
				type="search"
				placeholder={ __( 'Search icons…', 'compactform' ) }
				value={ query }
				autoFocus={ true }
				onChange={ ( e ) => setQuery( e.target.value ) }
			/>

			<div className="fcf7b-iconpick-gridwrap">
				<div
					className={ `fcf7b-iconpick-grid${ loading === 'reset' ? ' is-loading' : '' }` }
					onScroll={ onScroll }
					aria-busy={ !! loading }
				>
					{ state.icons.map( ( ic ) => (
						<button
							key={ ic }
							type="button"
							title={ ic }
							className={ `fcf7b-iconpick-cell${ value === ic ? ' active' : '' }` }
							onClick={ () => onChange( ic ) }
						><i className={ ic } /></button>
					) ) }
					{ ( state.icons.length || loading ) ? null : <div className="fcf7b-iconpick-empty">{ __( 'No icons found', 'compactform' ) }</div> }
					{ loading === 'more' ? (
						<div className="fcf7b-iconpick-more">
							<span className="fcf7b-spinner" />
							{ __( 'Loading…', 'compactform' ) }
						</div>
					) : null }
				</div>
				{ loading === 'reset' ? (
					<div className="fcf7b-iconpick-loader" role="status" aria-live="polite">
						<span className="fcf7b-spinner" />
						<span>{ __( 'Loading icons…', 'compactform' ) }</span>
					</div>
				) : null }
			</div>

			<div className="fcf7b-iconpick-selected">
				{ value ? (
					<>
						<i className={ value } />
						<code>{ value }</code>
					</>
				) : (
					<span className="fcf7b-iconpick-placeholder">{ __( 'No icon selected', 'compactform' ) }</span>
				) }
			</div>
		</Popover>
	);
}
