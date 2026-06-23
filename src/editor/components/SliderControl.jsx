
import { DEVICE_ICONS } from '../shared';
import UnitPicker from './UnitPicker';

export default function SliderControl( { ctrl, value, inherited, onChange, onGestureStart, onGestureEnd, device } ) {
	const units = ( ctrl.units && ctrl.units.length ) ? ctrl.units : [ 'px' ];

	const toObj = ( x ) => ( x && typeof x === 'object' )
		? x
		: { size: ( x === 0 || x ) && ! isNaN( x ) ? String( x ) : '', unit: undefined };
	const own = toObj( value );
	const inh = toObj( inherited );

	const size = own.size ?? '';
	const phSize = inh.size ?? '';
	const unit = units.includes( own.unit ) ? own.unit
		: ( units.includes( inh.unit ) ? inh.unit : units[ 0 ] );
	const isCustom = 'custom' === unit;

	const range = ( ctrl.ranges && ctrl.ranges[ unit ] ) || {};
	const min = range.min ?? ctrl.min ?? 0;
	const max = range.max ?? ctrl.max ?? 100;
	const step = range.step ?? ctrl.step ?? 1;

	const setSize = ( s ) => onChange( { size: s, unit } );
	const setUnit = ( u ) => onChange( { size: ( 'custom' !== u && isNaN( size ) ) ? '' : size, unit: u } );

	const onRangeDown = () => {
		if ( onGestureStart ) { onGestureStart(); }
		const up = () => { document.removeEventListener( 'pointerup', up ); if ( onGestureEnd ) { onGestureEnd(); } };
		document.addEventListener( 'pointerup', up );
	};

	return (
		<div className="fcf7b-prop fcf7b-slider">
			<div className="fcf7b-ctl-head">
				<label className="fcf7b-prop-label">{ ctrl.label }{ ctrl.responsive ? <i className={ `fcf7b-ctl-device ${ DEVICE_ICONS[ device ] }` } title={ device } /> : null }</label>
				<UnitPicker units={ units } value={ unit } onChange={ setUnit } />
			</div>
			<div className="fcf7b-slider-row">
				{ isCustom ? (
					<input
						type="text"
						className="fcf7b-slider-custom"
						value={ size }
						placeholder={ phSize || 'e.g. calc(100% - 20px)' }
						onChange={ ( e ) => setSize( e.target.value ) }
					/>
				) : (
					<>
						<input
							type="range"
							className="fcf7b-slider-range"
							min={ min }
							max={ max }
							step={ step }
							value={ size !== '' ? size : ( phSize !== '' ? phSize : min ) }
							onPointerDown={ onRangeDown }
							onChange={ ( e ) => setSize( e.target.value ) }
						/>
						<input
							type="number"
							className="fcf7b-slider-num"
							min={ min }
							max={ max }
							step={ step }
							value={ size }
							placeholder={ phSize }
							onChange={ ( e ) => setSize( e.target.value ) }
						/>
					</>
				) }
			</div>
		</div>
	);
}
