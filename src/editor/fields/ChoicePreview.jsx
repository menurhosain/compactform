import { __, sprintf } from '@wordpress/i18n';
import FieldChrome from './preview-helpers';

export default function ChoicePreview( { field, def } ) {
	const type = field.type === 'checkbox' ? 'checkbox' : 'radio';
	/* translators: %d: option number. */
	const optionLabel = ( n ) => sprintf( __( 'Option %d', 'compactform' ), n );

	const options = field.options || [ optionLabel( 1 ), optionLabel( 2 ) ];
	const defaults = Array.isArray( field.default_option )
		? field.default_option
		: ( field.default_option ? [ field.default_option ] : [] );

	const group = `fcf7b-pv-${ field.id }`;

	return (
		<FieldChrome field={ field } def={ def }>
			<span
				className={ `fcf7b-pv-choices wpcf7-form-control wpcf7-${ type }` }
				key={ `${ options.join( '|' ) }|${ defaults.join( '|' ) }` }
			>
				{ options.map( ( o, i ) => (
					<span
						key={ i }
						className={ `wpcf7-list-item${ i === 0 ? ' first' : '' }${ i === options.length - 1 ? ' last' : '' }` }
					>
						<label>
							<input type={ type } name={ group } defaultChecked={ defaults.includes( o ) } />
							<span className="wpcf7-list-item-label"> { o }</span>
						</label>
					</span>
				) ) }
			</span>
		</FieldChrome>
	);
}
