import FieldChrome from './preview-helpers';

export default function AcceptancePreview( { field, def } ) {
	// Pre-`label`-control schemas kept the checkbox wording in `label` itself.
	const legacy = ! Object.prototype.hasOwnProperty.call( field, 'text' );
	const text = ( legacy ? field.label : field.text ) || 'I accept the terms and conditions.';
	const chromeField = legacy ? { ...field, label: '' } : field;

	return (
		<FieldChrome field={ chromeField } def={ def }>
			<span className="fcf7b-pv-choices wpcf7-form-control wpcf7-acceptance">
				<span className="wpcf7-list-item">
					<label className="fcf7b-pv-inline">
						<input type="checkbox" />
						<span className="wpcf7-list-item-label"> { text }</span>
					</label>
				</span>
			</span>
		</FieldChrome>
	);
}
