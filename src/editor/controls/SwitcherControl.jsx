import Switch from '../components/Switch';

export default function SwitcherControl( { ctrl, value, update } ) {
	return (
		<div className="fcf7b-prop fcf7b-prop--inline fcf7b-switch-row">
			<span className="fcf7b-prop-label">{ ctrl.label }</span>
			<Switch
				checked={ value }
				onChange={ ( on ) => update( ctrl.key, on ) }
				labelOn={ ctrl.label_on || '' }
				labelOff={ ctrl.label_off || '' }
			/>
		</div>
	);
}

SwitcherControl.standalone = true;
