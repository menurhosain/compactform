export default function HeadingPreview( { field } ) {
	const ALLOWED_TAGS = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span' ];
	const Tag = ALLOWED_TAGS.includes( field.level ) ? field.level : 'h3';
	return (
		<div className={ `fcf7b-field-wrap fcf7b-field-${ field.id }` }>
			<Tag className="fcf7b-pv-heading fcf7b-heading">{ field.label || 'Heading' }</Tag>
		</div>
	);
}
