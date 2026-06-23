import FieldChrome from './preview-helpers';

export default function FilePreview( { field, def } ) {
	return (
		<FieldChrome field={ field } def={ def }>
			<input className="fcf7b-pv-file wpcf7-form-control wpcf7-file" type="file" disabled={ true } />
		</FieldChrome>
	);
}
