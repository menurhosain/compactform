import { useState } from '@wordpress/element';

function Switcher( { label, disabled = false, checked, onChange } ) {
	const [ internal, setInternal ] = useState( false );
	const isChecked = checked !== undefined ? checked : internal;

	const handleChange = () => {
		if ( disabled ) {
			return;
		}
		if ( onChange ) {
			onChange();
		} else {
			setInternal( ! internal );
		}
	};

	return (
		<label className="fcf7-switcher-wrapper">
			<span className="fcf7-switcher">
				<input
					type="checkbox"
					className="fcf7-item-checkbox"
					checked={ isChecked }
					disabled={ disabled }
					onChange={ handleChange }
				/>
				<span className="fcf7-switch-slider"></span>
			</span>
			{ label && <span className="fcf7-switch-label">{ label }</span> }
		</label>
	);
}

export default Switcher;
