import { __ } from '@wordpress/i18n';
import Switcher from './Switcher';
import { LuSearch } from 'react-icons/lu';

function Header( { pageTitle, pageInfo, search, setSearch, filterTier, setFilterTier, allActive, onEnableAll } ) {
	return (
		<div className="fcf7-elements-header">
			<div className="fcf7-elements-header-left">
				<h4 className="fcf7-elements-title">{ pageTitle }</h4>
				<p className="fcf7-elements-info">{ pageInfo }</p>
			</div>
			<div className="fcf7-elements-header-right">
				<Switcher
					label={ __( 'Enable All', 'compactform' ) }
					checked={ allActive }
					onChange={ onEnableAll }
				/>
				<div className="fcf7-elements-search">
					<span><LuSearch /></span>
					<input
						type="text"
						placeholder={ __( 'Search…', 'compactform' ) }
						value={ search }
						onChange={ ( e ) => setSearch( e.target.value.toLowerCase() ) }
					/>
				</div>
				<select
					className="fcf7-elements-filter"
					value={ filterTier }
					onChange={ ( e ) => setFilterTier( e.target.value ) }
				>
					<option value="">{ __( 'Free + Pro', 'compactform' ) }</option>
					<option value="free">{ __( 'Free', 'compactform' ) }</option>
					<option value="pro">{ __( 'Pro', 'compactform' ) }</option>
				</select>
			</div>
		</div>
	);
}

export default Header;
