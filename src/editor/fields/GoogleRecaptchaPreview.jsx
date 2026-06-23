import FieldChrome from './preview-helpers';

// Placeholder canvas preview — wired up to a real widget mock next.
export default function GoogleRecaptchaPreview( { field, def } ) {
	return (
		<FieldChrome field={ field } def={ def }>
			<span className="fcf7b-pv-recaptcha"></span>
		</FieldChrome>
	);
}
