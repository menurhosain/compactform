export default function DividerPreview( { field } ) {
	const pattern = field.divider_pattern || '';

	return (
		<div className={ `fcf7b-field-wrap fcf7b-field-${ field.id }` }>
			<div
				className="fcf7b-pv-divider fcf7b-divider"
				{ ...( pattern ? { 'data-pattern': pattern } : {} ) }
			/>
		</div>
	);
}
