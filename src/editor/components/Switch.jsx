
export default function Switch( { checked, onChange, labelOn = '', labelOff = '' } ) {
	const on = !! checked;
	const hasLabels = !! ( labelOn || labelOff );

	return (
		<button
			type="button"
			role="switch"
			aria-checked={ on }
			className={ `fcf7b-switch${ on ? ' is-on' : '' }${ hasLabels ? ' has-label' : '' }` }
			onClick={ () => onChange( ! on ) }
		>
			{ hasLabels ? <span className="fcf7b-switch-label">{ on ? labelOn : labelOff }</span> : null }
			<span className="fcf7b-switch-knob" aria-hidden="true" />
		</button>
	);
}
