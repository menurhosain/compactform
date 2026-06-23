
export default function HtmlPreview( { field } ) {
	const html = field.html || '';

	return (
		<div className={ `fcf7b-field-wrap fcf7b-field-${ field.id }` }>
			{ html.trim() ? (
				<div className="fcf7b-pv-html fcf7b-html" dangerouslySetInnerHTML={ { __html: html } } />
			) : (
				<div className="fcf7b-pv-html fcf7b-pv-html-empty">HTML</div>
			) }
		</div>
	);
}
