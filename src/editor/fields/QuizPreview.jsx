import FieldChrome from './preview-helpers';

export default function QuizPreview( { field, def } ) {
	const first = ( field.options || [] ).find( ( o ) => String( o ).includes( '|' ) );
	const question = first ? String( first ).split( '|' )[ 0 ].trim() : '1 + 1 = ?';

	return (
		<FieldChrome field={ field } def={ def }>
			<label className="fcf7b-pv-quiz">
				<span className="wpcf7-quiz-label">{ question }</span>
				<input className="fcf7b-pv-input wpcf7-form-control wpcf7-quiz" type="text" disabled={ true } />
			</label>
		</FieldChrome>
	);
}
