
export const sliderCss = ( v ) => (
	( v && typeof v === 'object' && v.size !== '' && v.size != null && ! isNaN( v.size ) )
		? ( v.size + ( v.unit || 'px' ) )
		: null
);

export const withFieldIcon = ( field, control ) => {
	if ( ! field.field_icon ) { return control; }
	return (
		<span className="fcf7b-pv-iconwrap fcf7-field-icon">
			<i className={ field.field_icon } />
			{ control }
		</span>
	);
};

export default function FieldChrome( { field, def, children, wrapClass = '' } ) {
	const label = field.label ?? ( def.title || '' );
	const mark = ( field.required_asterisk ?? '' ) !== '' ? field.required_asterisk : '*';
	const req = field.required ? <span className="fcf7b-req"> { mark }</span> : null;
	const desc = ( field.description || '' ).trim()
		? <small className="fcf7b-pv-desc fcf7b-desc">{ field.description.trim() }</small>
		: null;

	const control = (
		<span className={ `wpcf7-form-control-wrap ${ wrapClass }`.trim() } data-name={ field.name || '' }>
			{ children }
		</span>
	);

	return (
		<div className={ `fcf7b-pv-field fcf7b-field-wrap fcf7b-field-${ field.id }` }>
			<div className="fcf7b-field">
				{ '' !== label ? <span className="fcf7b-pv-label fcf7b-field-label">{ label }{ req }</span> : null }
				{ withFieldIcon( field, control ) }
			</div>
			{ desc }
		</div>
	);
}
