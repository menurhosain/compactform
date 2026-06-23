
export default function TextControl( { ctrl, value, update } ) {
	const type = [ 'number', 'time', 'date' ].includes( ctrl.type ) ? ctrl.type : 'text';
	const shown = value !== undefined ? value : ( ctrl.default ?? '' );

	const slug = ( v ) => ( ctrl.slug ? String( v ).toLowerCase().replace( /[^a-z0-9_-]/g, '-' ) : v );

	return (
		<input
			type={ type }
			value={ shown }
			placeholder={ ctrl.placeholder || undefined }
			min={ 'number' === type ? 0 : undefined }
			onChange={ ( e ) => update( ctrl.key, slug( e.target.value ) ) }
			onBlur={ ( e ) => {
				if ( ! ctrl.slug ) { return; }
				const trimmed = e.target.value.replace( /^-+|-+$/g, '' );
				if ( trimmed !== e.target.value ) { update( ctrl.key, trimmed ); }
			} }
		/>
	);
}
