import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { LuHouse, LuComponent, LuFileText, LuSettings } from 'react-icons/lu';

import { Tab, TabList, TabPanel, Tabs } from './components/Tabs';
import { hooks } from './dashboard-hooks';
import Welcome from './pages/Welcome';
import Extensions from './pages/Extensions';
import Configuration from './pages/Configuration';

function App() {
	const [ tab, setTab ] = useState(
		() => new URLSearchParams( location.search ).get( 'tab' ) ?? 'welcome'
	);

	const extraTabs = hooks.applyFilters( 'fcf7Dashboard.tabs', [] );

	const handleChange = ( val ) => {
		setTab( val );
		const url = new URL( location.href );
		url.searchParams.set( 'tab', val );
		history.pushState( {}, '', url );
	};

	return (
		<Tabs value={ tab } onValueChange={ handleChange }>
			<div className="fcf7-dashboard-header">
				<div className="fcf7-logo">
					{ FCF7Local?.logoUrl
						? <img className="fcf7-logo-img" src={ FCF7Local.logoUrl } alt={ __( 'CompactForm', 'compactform' ) } />
						: <>
							<span className="fcf7-logo-mark" aria-hidden="true">F</span>
							{ __( 'CompactForm', 'compactform' ) }
						</> }
					<span className="fcf7-logo-badge">v{ FCF7Local?.version }</span>
				</div>
				<a className="fcf7-doc-link" href={ FCF7Local?.links?.forms }>
					<LuFileText />
					<span>{ __( 'Contact Forms', 'compactform' ) }</span>
				</a>
			</div>

			<div className="fcf7-dashboard-pages">
				<div className="fcf7-dashboard-sidebar">
					<TabList>
						<Tab value="welcome">
							<span className="fcf7-tab-icon"><LuHouse /></span>
							{ __( 'Welcome', 'compactform' ) }
						</Tab>
						<Tab value="extensions">
							<span className="fcf7-tab-icon"><LuComponent /></span>
							{ __( 'Extensions', 'compactform' ) }
						</Tab>
						<Tab value="configuration">
							<span className="fcf7-tab-icon"><LuSettings /></span>
							{ __( 'Configuration', 'compactform' ) }
						</Tab>
						{ extraTabs.map( ( t ) => (
							<Tab key={ t.id } value={ t.id }>
								<span className="fcf7-tab-icon">{ t.icon }</span>
								{ t.label }
							</Tab>
						) ) }
					</TabList>
				</div>

				<div className="fcf7-dashboard-content">
					<TabPanel value="welcome" unmountOnHide><Welcome /></TabPanel>
					<TabPanel value="extensions" unmountOnHide><Extensions /></TabPanel>
					<TabPanel value="configuration" unmountOnHide><Configuration /></TabPanel>
					{ extraTabs.map( ( t ) => (
						<TabPanel key={ t.id } value={ t.id } unmountOnHide>{ t.render() }</TabPanel>
					) ) }
				</div>
			</div>
		</Tabs>
	);
}

export default App;
