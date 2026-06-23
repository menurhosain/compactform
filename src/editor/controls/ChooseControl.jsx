
export default function ChooseControl( { ctrl, value, inherited, update } ) {
	const opts = ctrl.options || {};
	const keys = Array.isArray( ctrl.optionList ) ? ctrl.optionList.map( ( o ) => o.value ) : Object.keys( opts );
	const shown = value !== undefined ? value : ( inherited ?? '' );
	const toggleable = false !== ctrl.toggle;

	return (
		<div className="fcf7b-choose" role="radiogroup" aria-label={ ctrl.label || '' }>
			{ keys.map( ( key ) => {
				const opt = opts[ key ];
				const isObj = opt && 'object' === typeof opt;
				const icon = isObj ? opt.icon : null;
				const title = isObj ? ( opt.title || key ) : String( opt ?? key );
				const active = String( shown ) === String( key );

				return (
					<button
						key={ key }
						type="button"
						className={ `fcf7b-choose-btn${ active ? ' active' : '' }` }
						title={ title }
						aria-label={ title }
						aria-pressed={ active }
						onClick={ () => update( ctrl.key, active && toggleable ? '' : key ) }
					>
						{ icon ? <i className={ icon } /> : title }
					</button>
				);
			} ) }
		</div>
	);
}
