import { __ } from '@wordpress/i18n';
import { BORDER_STYLES, deviceChain } from '../shared';
import DimensionsControl from './DimensionsControl';
import ColorControl from './ColorControl';
import Select from './Select';

const EMPTY_WIDTH = { top: '', right: '', bottom: '', left: '', unit: 'px', linked: true };
const DEFAULT = { style: '', color: '', width: { desktop: { ...EMPTY_WIDTH } } };

export default function BorderControl( { ctrl, value, onChange, device = 'desktop', onGestureStart, onGestureEnd } ) {
	const v = ( value && typeof value === 'object' ) ? { ...DEFAULT, ...value } : { ...DEFAULT };
	const set = ( patch ) => onChange( { ...v, ...patch } );

	const widths = ( v.width && typeof v.width === 'object' && ! Array.isArray( v.width ) ) ? v.width : {};
	const from = deviceChain( device ).find( ( d ) => widths[ d ] !== undefined );

	return (
		<div className="fcf7b-prop fcf7b-border">
			<div className="fcf7b-ctl-row">
				<span className="fcf7b-ctl-rowlabel">{ ctrl?.label }</span>
				<Select
						value={ v.style }
						options={ Object.keys( BORDER_STYLES ).map( ( k ) => ( { value: k, label: BORDER_STYLES[ k ] } ) ) }
						onChange={ ( val ) => set( { style: val } ) }
					/>
			</div>

			{ v.style && 'none' !== v.style ? (
				<>
					<DimensionsControl
						ctrl={ { label: __( 'Width', 'compactform' ), responsive: true } }
						value={ from ? widths[ from ] : undefined }
						onChange={ ( o ) => set( { width: { ...widths, [ device ]: o } } ) }
						device={ device }
					/>
					<div className="fcf7b-ctl-row">
						<span className="fcf7b-ctl-rowlabel">Color</span>
						<ColorControl
							value={ v.color }
							onChange={ ( c ) => set( { color: c } ) }
							onGestureStart={ onGestureStart }
							onGestureEnd={ onGestureEnd }
						/>
					</div>
				</>
			) : null }
		</div>
	);
}
