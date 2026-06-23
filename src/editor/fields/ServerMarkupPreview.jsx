import FieldChrome from './preview-helpers';

export default function ServerMarkupPreview( { field, def } ) {
	return (
		<FieldChrome field={ field } def={ def }>
			<div className="fcf7b-pv-server" dangerouslySetInnerHTML={ { __html: def?.builderMarkup || '' } } />
		</FieldChrome>
	);
}
