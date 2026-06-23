import { __ } from '@wordpress/i18n';

export default function SubmitPreview( { field } ) {
	return (
		<div className={ `fcf7b-field-wrap fcf7b-field-${ field.id }` }>
			<input
				type="submit"
				className="fcf7b-pv-submit wpcf7-form-control wpcf7-submit has-spinner"
				value={ field.label || __( 'Send', 'compactform' ) }
			/>
		</div>
	);
}
