import { TabPanel } from '../Tabs';
import { __ } from '@wordpress/i18n';
import ElementCard from './ElementCard';
import Switcher from './Switcher';

function ElementsTab( { elementsMap, inactive, setInactive, toggleElement, search, filterTier } ) {
	const isProActive = !! FCF7Local?.isProActive;

	const matchesFilter = ( element ) => {
		const matchesSearch = ! search || element.title.toLowerCase().includes( search );
		const matchesTier =
			! filterTier ||
			( filterTier === 'free' && ! element.isPro ) ||
			( filterTier === 'pro' && element.isPro );
		return matchesSearch && matchesTier;
	};

	const renderGroup = ( groupKey, group ) => {
		const filtered = Object.entries( group.elements ).filter( ( [ , el ] ) => matchesFilter( el ) );
		if ( filtered.length === 0 ) {
			return null;
		}

		const toggleableGroupKeys = Object.entries( group.elements )
			.filter( ( [ , el ] ) => isProActive || ! el.isPro )
			.map( ( [ k ] ) => k );
		const allGroupActive = toggleableGroupKeys.every( ( k ) => ! inactive.includes( k ) );

		const handleGroupEnableAll = () => {
			if ( allGroupActive ) {
				setInactive( ( prev ) => [ ...new Set( [ ...prev, ...toggleableGroupKeys ] ) ] );
			} else {
				setInactive( ( prev ) => prev.filter( ( k ) => ! toggleableGroupKeys.includes( k ) ) );
			}
		};

		return (
			<div key={ groupKey } className="fcf7-elements-group">
				<div className="fcf7-elements-group-header">
					<h3 className="fcf7-elements-group-title">{ group.title }</h3>
					<Switcher
						label={ __( 'Enable All', 'compactform' ) }
						checked={ allGroupActive }
						onChange={ handleGroupEnableAll }
					/>
				</div>
				<div className="fcf7-elements-group-grid">
					{ filtered.map( ( [ key, widget ] ) => (
						<ElementCard
							key={ key }
							element={ widget }
							active={ ! inactive.includes( key ) }
							onToggle={ () => toggleElement( key ) }
						/>
					) ) }
				</div>
			</div>
		);
	};

	const noResults = <p className="fcf7-no-results">{ __( 'No results found.', 'compactform' ) }</p>;

	const allGroups = Object.entries( elementsMap ).map( ( [ groupKey, group ] ) =>
		renderGroup( groupKey, group )
	);

	return (
		<div className="fcf7-elements-tab-content">
			<TabPanel value="all" unmountOnHide>
				{ allGroups.some( Boolean ) ? allGroups : noResults }
			</TabPanel>
			{ Object.entries( elementsMap )
				.filter( ( [ key ] ) => key !== 'all' )
				.map( ( [ key, group ] ) => (
					<TabPanel key={ key } value={ key } unmountOnHide>
						{ renderGroup( key, group ) ?? noResults }
					</TabPanel>
				) ) }
		</div>
	);
}

export default ElementsTab;
