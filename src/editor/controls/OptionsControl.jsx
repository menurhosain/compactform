import { __ } from '@wordpress/i18n';

/** A choice list: one option per line, stored as an array of strings. */
export default function OptionsControl( { ctrl, value, update } ) {
	return (
		<textarea
			rows={ 4 }
			className="fcf7b-opts"
			value={ ( value || [] ).join( '\n' ) }
			placeholder={ __( 'One option per line', 'compactform' ) }
			onChange={ ( e ) => update( ctrl.key, e.target.value.split( '\n' ) ) }
		/>
	);
}
