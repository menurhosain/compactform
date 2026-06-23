import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

import Header from '../components/elements/Header';
import ElementsActions from '../components/elements/ElementsActions';
import { Tabs } from '../components/Tabs';
import ElementsTab from '../components/elements/ElementsTab';

function Extensions() {
	const [ search, setSearch ] = useState( '' );
	const [ filterTier, setFilterTier ] = useState( '' );
	const [ inactive, setInactive ] = useState( FCF7Local?.inactiveExtensions || [] );
	const [ saveStatus, setSaveStatus ] = useState( 'idle' );
	const [ activeTab, setActiveTab ] = useState( 'all' );

	const extensionsMap = FCF7Local.extensionsMap;
	const isProActive = !! FCF7Local?.isProActive;

	const toggleableKeys = Object.values( extensionsMap ).flatMap( ( group ) =>
		Object.entries( group.elements )
			.filter( ( [ , el ] ) => isProActive || ! el.isPro )
			.map( ( [ key ] ) => key )
	);
	const totalCount = Object.values( extensionsMap ).flatMap( ( g ) => Object.keys( g.elements ) ).length;
	const activeCount = toggleableKeys.filter( ( k ) => ! inactive.includes( k ) ).length;
	const allActive = toggleableKeys.every( ( k ) => ! inactive.includes( k ) );

	const toggleElement = ( key ) => {
		setInactive( ( prev ) => ( prev.includes( key ) ? prev.filter( ( k ) => k !== key ) : [ ...prev, key ] ) );
	};

	const handleSave = async () => {
		setSaveStatus( 'saving' );
		try {
			const body = new FormData();
			body.append( 'action', 'fcf7_save_elements_config' );
			body.append( 'nonce', FCF7Local.nonce );
			body.append( 'config', JSON.stringify( inactive ) );
			body.append( 'type', 'extensions' );

			const res = await fetch( FCF7Local.ajaxUrl, { method: 'POST', body } );
			const data = await res.json();

			if ( data.success ) {
				FCF7Local.inactiveExtensions = inactive;
				setSaveStatus( 'saved' );
			} else {
				setSaveStatus( 'error' );
			}
		} catch {
			setSaveStatus( 'error' );
		} finally {
			setTimeout( () => setSaveStatus( 'idle' ), 2500 );
		}
	};

	const handleEnableAll = () => {
		if ( allActive ) {
			setInactive( ( prev ) => [ ...new Set( [ ...prev, ...toggleableKeys ] ) ] );
		} else {
			setInactive( ( prev ) => prev.filter( ( k ) => ! toggleableKeys.includes( k ) ) );
		}
	};

	return (
		<>
			<Header
				pageTitle={ __( 'Extensions', 'compactform' ) }
				pageInfo={ `${ totalCount } ${ __( 'Total Extensions', 'compactform' ) } | ${ activeCount } ${ __( 'Active', 'compactform' ) }` }
				search={ search }
				setSearch={ setSearch }
				filterTier={ filterTier }
				setFilterTier={ setFilterTier }
				allActive={ allActive }
				onEnableAll={ handleEnableAll }
			/>
			<Tabs value={ activeTab } onValueChange={ ( val ) => setActiveTab( val ) }>
				<ElementsActions elementsMap={ extensionsMap } saveStatus={ saveStatus } handleSave={ handleSave } />
				<ElementsTab
					elementsMap={ extensionsMap }
					inactive={ inactive }
					setInactive={ setInactive }
					toggleElement={ toggleElement }
					search={ search }
					filterTier={ filterTier }
				/>
			</Tabs>
		</>
	);
}

export default Extensions;
