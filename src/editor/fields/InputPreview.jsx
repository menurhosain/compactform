import FieldChrome from './preview-helpers';

const INPUT_TYPES = {
	email: 'email',
	tel: 'tel',
	url: 'url',
	number: 'number',
	date: 'date',
};

export default function InputPreview( { field, def } ) {
	const dv = field.defaultValue || '';
	return (
		<FieldChrome field={ field } def={ def }>
			<input
				key={ dv }
				className={ `fcf7b-pv-input wpcf7-form-control wpcf7-${ field.type === 'tel' ? 'tel' : ( INPUT_TYPES[ field.type ] || 'text' ) }` }
				type={ INPUT_TYPES[ field.type ] || 'text' }
				placeholder={ field.placeholder || '' }
				defaultValue={ dv }
			/>
		</FieldChrome>
	);
}
