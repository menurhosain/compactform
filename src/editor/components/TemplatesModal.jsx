import { useState, useEffect, useRef, createPortal } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { templatesFetch, templateFetch, templatesCached } from '../templates-api';
import Popover from './Popover';

function Lightbox( { images, index, setIndex, close } ) {
	const root = useRef( null );

	useEffect( () => {
		const onKey = ( e ) => {
			if ( 'Escape' !== e.key && 'ArrowLeft' !== e.key && 'ArrowRight' !== e.key ) { return; }
			e.stopPropagation();
			if ( 'Escape' === e.key ) { close(); return; }
			setIndex( ( i ) => ( i + ( 'ArrowRight' === e.key ? 1 : images.length - 1 ) ) % images.length );
		};
		const onDown = ( e ) => {
			if ( root.current && root.current.contains( e.target ) ) { e.stopPropagation(); }
		};
		document.addEventListener( 'keydown', onKey, true );
		document.addEventListener( 'mousedown', onDown, true );
		return () => {
			document.removeEventListener( 'keydown', onKey, true );
			document.removeEventListener( 'mousedown', onDown, true );
		};
	}, [ images.length ] );

	const stop = ( e ) => e.stopPropagation();

	return createPortal((
		<div className="fcf7b-tpl-light" ref={ root } onMouseDown={ stop } onClick={ close }>
			<div className="fcf7b-tpl-light-bar" onClick={ stop }>
				{ images.length > 1 && (
					<>
						<button
							type="button"
							className="fcf7b-tpl-light-nav"
							title={ __( 'Previous', 'compactform' ) }
							onClick={ () => setIndex( ( i ) => ( i + images.length - 1 ) % images.length ) }
						><i className="ri-arrow-left-s-line" /></button>
						<span className="fcf7b-tpl-light-count">{ `${ index + 1 } / ${ images.length }` }</span>
						<button
							type="button"
							className="fcf7b-tpl-light-nav"
							title={ __( 'Next', 'compactform' ) }
							onClick={ () => setIndex( ( i ) => ( i + 1 ) % images.length ) }
						><i className="ri-arrow-right-s-line" /></button>
					</>
				) }
				<button
					type="button"
					className="fcf7b-tpl-light-close"
					title={ __( 'Close', 'compactform' ) }
					onClick={ close }
				><i className="ri-close-line" /></button>
			</div>

			<div className="fcf7b-tpl-light-panel">
				<img src={ images[ index ] } alt="" onClick={ stop } />
			</div>
		</div>
	), document.body );
}

const menuIcon = ( window.FCF7Builder || {} ).menuIconUrl || '';

function Library( { onImport, close } ) {
	const cached = templatesCached();
	const [ items, setItems ] = useState( () => ( cached ? cached.items || [] : [] ) );
	const [ categories, setCategories ] = useState( () => ( cached ? cached.categories || [] : [] ) );
	const [ badges, setBadges ] = useState( () => ( cached ? cached.badges || [] : [] ) );
	const [ plan, setPlan ] = useState( '' );
	const [ category, setCategory ] = useState( '' );
	const [ badge, setBadge ] = useState( '' );
	const [ search, setSearch ] = useState( '' );
	const [ loading, setLoading ] = useState( ! cached );
	const [ error, setError ] = useState( '' );
	const [ busyId, setBusyId ] = useState( '' );
	const [ lightbox, setLightbox ] = useState( null );

	const load = ( refresh ) => {
		setLoading( true );
		setError( '' );
		templatesFetch( refresh ).then( ( res ) => {
			setLoading( false );
			if ( ! res.ok ) {
				setError( res.message || __( 'Could not reach the template library.', 'compactform' ) );
				return;
			}
			setItems( res.data.items || [] );
			setCategories( res.data.categories || [] );
			setBadges( res.data.badges || [] );
		} );
	};

	useEffect( () => {
		if ( ! cached ) { load( false ); }
	}, [] );

	const term = search.trim().toLowerCase();
	const matches = ( t ) => ! term || `${ t.name } ${ t.description }`.toLower
	const found = items.filter( matches );
	const except = ( axis ) => found.filter( ( t ) => (
		( 'plan' === axis || ! plan || t.subscription === plan ) &&
		( 'badge' === axis || ! badge || ( t.badge || [] ).includes( badge ) ) &&
		( 'category' === axis || ! category || ( t.category || [] ).includes( category ) )
	) );

	const plans = [ 'free', 'pro' ].filter( ( p ) => items.some( ( t ) => t.subscription === p ) );

	const shown = except( null );
	const forPlan = except( 'plan' );
	const forBadge = except( 'badge' );
	const forCategory = except( 'category' );
	const countIn = ( list, kind, term ) => list.filter( ( t ) => ( t[ kind ] || [] ).includes( term ) ).length;

	const importTemplate = ( t ) => {
		setBusyId( t.id );
		templateFetch( t.id ).then( ( res ) => {
			setBusyId( '' );
			if ( ! res.ok ) {
				window.alert( res.message || __( 'Could not download that template.', 'compactform' ) );
				return;
			}
			close();
			onImport( res.data.schema );
		} );
	};

	return (
		<>
			<div className="fcf7b-gs-head fcf7b-tpl-head">
				{ menuIcon && <img className="fcf7b-tpl-logo" src={ menuIcon } alt="" /> }
				{ __( 'Templates', 'compactform' ) }
				<button
					type="button"
					className={ `fcf7b-tpl-refresh${ loading ? ' is-busy' : '' }` }
					title={ __( 'Refresh', 'compactform' ) }
					disabled={ loading }
					onClick={ () => load( true ) }
				>
					<i className="ri-refresh-line" />
				</button>
				<button type="button" className="fcf7b-gs-close" title={ __( 'Close', 'compactform' ) } onClick={ close }>
					<i className="ri-close-line" />
				</button>
			</div>

			<div className="fcf7b-gs-main">
			<div className="fcf7b-gs-sidebar fcf7b-tpl-sidebar">
				<input
					type="search"
					className="fcf7b-tpl-search"
					placeholder={ __( 'Search templates…', 'compactform' ) }
					value={ search }
					onChange={ ( e ) => setSearch( e.target.value ) }
				/>

				{ plans.length > 1 && (
					<>
						<p className="fcf7b-tpl-navhead">{ __( 'Plan', 'compactform' ) }</p>
						<button type="button" className={ `fcf7b-gs-navitem fcf7b-tpl-navall${ plan ? '' : ' is-on' }` } onClick={ () => setPlan( '' ) }>
							{ __( 'All', 'compactform' ) }
							<span className="fcf7b-tpl-count">{ forPlan.length }</span>
						</button>
						{ plans.map( ( value ) => (
							<button
								key={ value }
								type="button"
								className={ `fcf7b-gs-navitem${ plan === value ? ' active' : '' }` }
								onClick={ () => setPlan( value ) }
							>
								{ 'pro' === value
									? __( 'Pro', 'compactform' )
									: __( 'Free', 'compactform' ) }
								<span className="fcf7b-tpl-count">{ forPlan.filter( ( t ) => t.subscription === value ).length }</span>
							</button>
						) ) }
					</>
				) }

				{ !! badges.length && (
					<>
						<p className="fcf7b-tpl-navhead">{ __( 'Badges', 'compactform' ) }</p>
						<button type="button" className={ `fcf7b-gs-navitem fcf7b-tpl-navall${ badge ? '' : ' is-on' }` } onClick={ () => setBadge( '' ) }>
							{ __( 'All', 'compactform' ) }
							<span className="fcf7b-tpl-count">{ forBadge.length }</span>
						</button>
						{ badges.map( ( b ) => (
							<button
								key={ b }
								type="button"
								className={ `fcf7b-gs-navitem${ badge === b ? ' active' : '' }` }
								onClick={ () => setBadge( b ) }
							>
								{ b }
								<span className="fcf7b-tpl-count">{ countIn( forBadge, 'badge', b ) }</span>
							</button>
						) ) }
					</>
				) }

				{ !! categories.length && (
					<>
						<p className="fcf7b-tpl-navhead">{ __( 'Categories', 'compactform' ) }</p>
						<button type="button" className={ `fcf7b-gs-navitem fcf7b-tpl-navall${ category ? '' : ' is-on' }` } onClick={ () => setCategory( '' ) }>
							{ __( 'All', 'compactform' ) }
							<span className="fcf7b-tpl-count">{ forCategory.length }</span>
						</button>
						{ categories.map( ( c ) => (
							<button
								key={ c }
								type="button"
								className={ `fcf7b-gs-navitem${ category === c ? ' active' : '' }` }
								onClick={ () => setCategory( c ) }
							>
								{ c }
								<span className="fcf7b-tpl-count">{ countIn( forCategory, 'category', c ) }</span>
							</button>
						) ) }
					</>
				) }
			</div>

			<div className="fcf7b-gs-body fcf7b-tpl-body">
				{ loading && (
					<div className="fcf7b-tpl-grid" aria-label={ __( 'Loading templates…', 'compactform' ) } aria-busy="true">
						{ Array.from( { length: shown.length || 6 } ).map( ( _, i ) => (
							<div key={ i } className="fcf7b-tpl-card fcf7b-tpl-skel">
								<span className="fcf7b-tpl-skel-thumb" />
								<span className="fcf7b-tpl-skel-line" />
								<span className="fcf7b-tpl-skel-line fcf7b-tpl-skel-line--short" />
								<span className="fcf7b-tpl-skel-btn" />
							</div>
						) ) }
					</div>
				) }
				{ ! loading && error && <p className="fcf7b-tpl-note fcf7b-tpl-note--error">{ error }</p> }
				{ ! loading && ! error && ! shown.length && (
					<p className="fcf7b-tpl-note">{ __( 'No templates match.', 'compactform' ) }</p>
				) }

				<div className="fcf7b-tpl-grid" hidden={ loading }>
					{ shown.map( ( t ) => (
						<div key={ t.id } className="fcf7b-tpl-card">
							<div className="fcf7b-tpl-thumb">
								{ t.image[ 0 ]
									? <img src={ t.image[ 0 ] } alt="" loading="lazy" />
									: <i className="ri-file-list-3-line" /> }
								{ !! t.image.length && (
									<button
										type="button"
										className="fcf7b-tpl-zoom"
										title={ __( 'View screenshot', 'compactform' ) }
										onClick={ () => setLightbox( { images: t.image, index: 0 } ) }
									><i className="ri-zoom-in-line" /></button>
								) }
								{ ( 'pro' === t.subscription ) && (
									<span className="fcf7b-tpl-badge fcf7b-tpl-badge--pro">{ __( 'Pro', 'compactform' ) }</span>
								) }
							</div>
							<div className="fcf7b-tpl-meta">
								{ ( !! ( t.badge || [] ).length ) && (
									<div className="fcf7b-tpl-badges">
										{ ( t.badge || [] ).map( ( b ) => (
											<span key={ b } className="fcf7b-tpl-badge">{ b }</span>
										) ) }
									</div>
								) }
								<strong>{ t.name }</strong>
								<span>{ t.description }</span>
							</div>
							<div className="fcf7b-tpl-actions">
								{ t.preview && (
									<a className="fcf7b-tpl-preview" href={ t.preview } target="_blank" rel="noreferrer">
										<i className="ri-external-link-line" />
										{ __( 'Preview', 'compactform' ) }
									</a>
								) }
								<button type="button" className="fcf7b-tpl-import" disabled={ !! busyId } onClick={ () => importTemplate( t ) }>
									{ busyId === t.id
										? __( 'Importing…', 'compactform' )
										: __( 'Import', 'compactform' ) }
								</button>
							</div>
						</div>
					) ) }
				</div>
			</div>
			</div>

			{ lightbox && (
				<Lightbox
					images={ lightbox.images }
					index={ lightbox.index }
					setIndex={ ( fn ) => setLightbox( ( l ) => ( { ...l, index: fn( l.index ) } ) ) }
					close={ () => setLightbox( null ) }
				/>
			) }
		</>
	);
}

export default function TemplatesModal( { onImport } ) {
	return null;

	return (
		<Popover
			className="fcf7b-gs fcf7b-tpl"
			triggerClassName="fcf7b-ghost"
			popClassName="fcf7b-gs-pop fcf7b-tpl-pop"
			backdropClassName="fcf7b-gs-backdrop"
			title={ __( 'Templates', 'compactform' ) }
			trigger={ <i className="ri-layout-masonry-line" /> }
		>
			{ ( { close } ) => <Library onImport={ onImport } close={ close } /> }
		</Popover>
	);
}
