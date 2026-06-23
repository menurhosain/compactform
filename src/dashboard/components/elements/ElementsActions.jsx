import { __ } from '@wordpress/i18n';
import { Tab, TabList } from '../Tabs';

function ElementsActions( { elementsMap, saveStatus, handleSave } ) {
	const elementsTabs = [
		{ value: 'all', title: __( 'All', 'compactform' ) },
		...Object.entries( elementsMap )
			.filter( ( [ key ] ) => key !== 'all' )
			.map( ( [ key, group ] ) => ( { value: key, title: group.tabTitle } ) ),
	];

	return (
		<div className="fcf7-elements-action-area">
			<TabList>
				{ elementsTabs.map( ( tab, key ) => (
					<Tab value={ tab.value } key={ key }>
						{ tab.title }
					</Tab>
				) ) }
			</TabList>
			<div className="fcf7-elements-action-btns">
				<button
					className={ `fcf7-save-btn fcf7-save-btn--${ saveStatus }` }
					onClick={ handleSave }
					disabled={ saveStatus === 'saving' }
				>
					{ saveStatus === 'saving' && __( 'Saving…', 'compactform' ) }
					{ saveStatus === 'saved' && __( 'Saved!', 'compactform' ) }
					{ saveStatus === 'error' && __( 'Error!', 'compactform' ) }
					{ saveStatus === 'idle' && __( 'Save Changes', 'compactform' ) }
				</button>
			</div>
		</div>
	);
}

export default ElementsActions;
