import FieldChrome from './preview-helpers';

export default function TextareaPreview( { field, def } ) {
	const dv = field.defaultValue || '';
	return (
		<FieldChrome field={ field } def={ def }>
			<textarea
				key={ dv }
				className="fcf7b-pv-input wpcf7-form-control wpcf7-textarea"
				rows={ 3 }
				placeholder={ field.placeholder || '' }
				defaultValue={ dv }
			/>
		</FieldChrome>
	);
}
