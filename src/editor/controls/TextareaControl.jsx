export default function TextareaControl( { ctrl, value, update } ) {
	const shown = value !== undefined ? value : ( ctrl.default ?? '' );
	return <textarea rows={ 3 } value={ shown } placeholder={ ctrl.placeholder || undefined } onChange={ ( e ) => update( ctrl.key, e.target.value ) } />;
}
