import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { DATA, FIELDS, drag } from '../shared';
import { hooks } from '../field-preview-registry';

export default function Palette( { search, setSearch, onAdd, schema, setSchema } ) {
	const [ tab, setTab ] = useState( 'fields' );

	const cats = DATA.categories || {};
	const matches = ( f ) => ! search || f.title.toLowerCase().includes( search.toLowerCase() );
	const items = FIELDS.filter( matches );

	const extraTabs = hooks.applyFilters( 'fcf7b.paletteTabs', [] );
	const activeExtra = extraTabs.find( ( t ) => t.id === tab );

	const item = ( f ) => (
		<button key={ f.type } className="fcf7b-pal-item" type="button" draggable={ true }
			onDragStart={ () => { drag.value = { kind: 'new', type: f.type }; } }
			onDragEnd={ () => { drag.value = null; } }
			onClick={ () => onAdd( f.type, null ) }>
			<span className={ `fcf7b-pal-icon ${ f.icon }` } />
			<span className="fcf7b-pal-label">{ f.title }</span>
			{ !! f.isPro && (
				<span className="fcf7b-pal-pro" title={ __( 'Pro', 'compactform' ) }>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M345 151.2C354.2 143.9 360 132.6 360 120C360 97.9 342.1 80 320 80C297.9 80 280 97.9 280 120C280 132.6 285.9 143.9 295 151.2L226.6 258.8C216.6 274.5 195.3 278.4 180.4 267.2L120.9 222.7C125.4 216.3 128 208.4 128 200C128 177.9 110.1 160 88 160C65.9 160 48 177.9 48 200C48 221.8 65.5 239.6 87.2 240L119.8 457.5C124.5 488.8 151.4 512 183.1 512L456.9 512C488.6 512 515.5 488.8 520.2 457.5L552.8 240C574.5 239.6 592 221.8 592 200C592 177.9 574.1 160 552 160C529.9 160 512 177.9 512 200C512 208.4 514.6 216.3 519.1 222.7L459.7 267.3C444.8 278.5 423.5 274.6 413.5 258.9L345 151.2z" /></svg>
				</span>
			) }
			{ hooks.applyFilters( 'fcf7b.paletteItemBadge', null, f ) }
		</button>
	);

	return (
		<div className="fcf7b-palette">
			{ !! extraTabs.length && (
				<div className="fcf7b-pal-switch">
					<button type="button" className={ tab === 'fields' ? 'active' : '' } onClick={ () => setTab( 'fields' ) }>Fields</button>
					{ extraTabs.map( ( t ) => (
						<button key={ t.id } type="button" className={ tab === t.id ? 'active' : '' } onClick={ () => setTab( t.id ) }>{ t.label }</button>
					) ) }
				</div>
			) }

			<input className="fcf7b-search" type="search"
				placeholder={ activeExtra
					? sprintf(
						/* translators: %s: name of the palette group being searched. */
						__( 'Search %s...', 'compactform' ),
						activeExtra.label.toLowerCase()
					)
					: __( 'Search fields...', 'compactform' ) }
				value={ search } onChange={ ( e ) => setSearch( e.target.value ) } />

			{ activeExtra ? activeExtra.render( { search, onAdd, schema, setSchema } ) : (
				Object.keys( cats ).map( ( cat ) => {
					const group = items.filter( ( f ) => f.category === cat );
					if ( ! group.length ) { return null; }
					return (
						<div className="fcf7b-pal-group" key={ cat }>
							<div className="fcf7b-pal-title">{ cats[ cat ] }</div>
							<div className="fcf7b-pal-grid">{ group.map( item ) }</div>
						</div>
					);
				} )
			) }
		</div>
	);
}
