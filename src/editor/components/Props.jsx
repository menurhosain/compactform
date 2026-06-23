import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { DATA, BY_TYPE } from '../shared';
import { hooks } from '../field-preview-registry';
import { meetsCondition } from '../condition';
import Control from './Control';
import PopoverGroup from './PopoverGroup';
import TabsGroup from './TabsGroup';

export default function Props( { field, update, beginGesture, endGesture, allFields, device } ) {
	const [ ptab, setPtab ] = useState( 'settings' );
	const [ openSections, setOpenSections ] = useState( {} );

	if ( ! field ) {
		return (
			<div className="fcf7b-props fcf7b-props-empty">
				<div className="fcf7b-empty-emoji"><i class="ri-cursor-hand"></i></div>
				<p>{ __( 'Select a field to edit its settings.', 'compactform' ) }</p>
			</div>
		);
	}

	const def = BY_TYPE[ field.type ] || { controls: [], sections: [] };

	const byId = ( rows ) => ( rows || [] ).reduce( ( m, r ) => { if ( r.id ) { m[ r.id ] = r; } return m; }, {} );
	const tabGroupsById = byId( def.tab_groups );
	const tabItemsById = byId( def.tab_items );
	const controlShows = ( c ) => meetsCondition( c.condition, field, device )
		&& ( ! c.tab_group || meetsCondition( ( tabGroupsById[ c.tab_group ] || {} ).condition, field, device ) )
		&& ( ! c.tab_item || meetsCondition( ( tabItemsById[ c.tab_item ] || {} ).condition, field, device ) );
	const tabsOrder = Object.keys( DATA.tabs || {
		settings: __( 'Settings', 'compactform' ),
		style: __( 'Style', 'compactform' ),
		advanced: __( 'Advanced', 'compactform' ),
	} );
	const tabMeta = ( t ) => {
		const meta = DATA.tabs?.[ t ];
		return ( meta && typeof meta === 'object' ) ? meta : { label: meta || t, icon: '' };
	};
	const tabsWith = tabsOrder.filter( ( t ) => def.controls.some( ( c ) => c.tab === t ) );
	const active = tabsWith.includes( ptab ) ? ptab : ( tabsWith[ 0 ] || 'settings' );

	const visible = ( def.sections || [] )
		.filter( ( s ) => s.tab === active && meetsCondition( s.condition, field, device ) )
		.map( ( section ) => ( {
			section,
			controls: def.controls.filter( ( c ) =>
				c.section === section.id && ! c.popover && controlShows( c )
			),
		} ) )
		.filter( ( entry ) => entry.controls.length > 0 );

	const openKey = `${ field.type }|${ active }`;
	const remembered = openSections[ openKey ];
	const firstId = visible[ 0 ]?.section.id || '';

	let openId;
	if ( remembered === undefined ) {
		openId = firstId;
	} else if ( '' === remembered ) {
		openId = '';
	} else {
		openId = visible.some( ( e ) => e.section.id === remembered ) ? remembered : firstId;
	}

	const toggle = ( id ) => setOpenSections( ( prev ) => ( {
		...prev,
		[ openKey ]: openId === id ? '' : id,
	} ) );

	const renderControls = ( list ) => {
		const out = [];
		const seenGroups = new Set();
		list.forEach( ( ctrl ) => {
			if ( ctrl.tab_group ) {
				if ( seenGroups.has( ctrl.tab_group ) ) { return; }
				seenGroups.add( ctrl.tab_group );
				const group = tabGroupsById[ ctrl.tab_group ] || {};
				if ( 'before' === group.separator ) {
					out.push( <div key={ `sep-b-${ ctrl.tab_group }` } className="fcf7b-ctl-sep" aria-hidden="true" /> );
				}
				out.push(
					<TabsGroup
						key={ `${ field.id }:tabs-${ ctrl.tab_group }` }
						items={ ( def.tab_items || [] ).filter( ( t ) => t.group === ctrl.tab_group ) }
						controls={ ( def.controls || [] ).filter( ( c ) => c.tab_group === ctrl.tab_group ) }
						field={ field }
						update={ update }
						beginGesture={ beginGesture }
						endGesture={ endGesture }
						allFields={ allFields }
						device={ device }
					/>
				);
				if ( 'after' === group.separator ) {
					out.push( <div key={ `sep-a-${ ctrl.tab_group }` } className="fcf7b-ctl-sep" aria-hidden="true" /> );
				}
				return;
			}
			if ( ctrl.popover_toggle ) {
				out.push(
					<PopoverGroup
						key={ `${ field.id }:${ ctrl.key }` }
						toggleCtrl={ ctrl }
						controls={ ( def.controls || [] ).filter( ( c ) => c.popover === ctrl.key ) }
						field={ field }
						update={ update }
						beginGesture={ beginGesture }
						endGesture={ endGesture }
						allFields={ allFields }
						device={ device }
					/>
				);
				return;
			}
			out.push(
				<Control key={ `${ field.id }:${ ctrl.key }` } ctrl={ ctrl } field={ field } value={ field[ ctrl.key ] } update={ update } beginGesture={ beginGesture } endGesture={ endGesture } allFields={ allFields } device={ device } />
			);
		} );
		return out;
	};

	const banners = hooks.applyFilters( 'fcf7b.propsBanners', [], field, def, allFields );

	return (
		<div className="fcf7b-props">
			<div className="fcf7b-props-head">
				{ def.icon ? <span className={ `fcf7b-props-head-icon ${ def.icon }` } aria-hidden="true" /> : null }
				<span>{ sprintf( /* translators: %s: field name. */ __( 'Edit %s', 'compactform' ), def.title || field.type ) }</span>
			</div>
			{ banners.length > 0 && (
				<div className="fcf7b-props-banners">
					{ banners.map( ( b ) => <span key={ b.id }>{ b.render() }</span> ) }
				</div>
			) }
			<div className="fcf7b-props-tabs">
				{ tabsWith.map( ( t ) => {
					const { label, icon } = tabMeta( t );
					return (
						<button key={ t } type="button" className={ `fcf7b-ptab${ active === t ? ' active' : '' }` } onClick={ () => setPtab( t ) }>
							{ icon ? <i className={ icon } aria-hidden="true" /> : null }
							<span>{ label }</span>
						</button>
					);
				} ) }
			</div>
			<div className="fcf7b-props-body">
				{ visible.map( ( { section, controls } ) => {
					const isOpen = section.id === openId;
					return (
						<div key={ section.id } className={ `fcf7b-sec${ isOpen ? ' open' : '' }` }>
							<button
								type="button"
								className="fcf7b-sec-head"
								aria-expanded={ isOpen }
								onClick={ () => toggle( section.id ) }
							>
								<i className="ri-arrow-right-s-line fcf7b-sec-arrow" aria-hidden="true"></i>
								<span>{ section.label }</span>
							</button>
							{ isOpen && (
								<div className="fcf7b-sec-body">
									{ renderControls( controls ) }
								</div>
							) }
						</div>
					);
				} ) }
			</div>
		</div>
	);
}
