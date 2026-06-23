import { __ } from '@wordpress/i18n';

const SIDES = [
	[ 'top', __( 'Top', 'compactform' ) ],
	[ 'right', __( 'Right', 'compactform' ) ],
	[ 'bottom', __( 'Bottom', 'compactform' ) ],
	[ 'left', __( 'Left', 'compactform' ) ],
];
const UNITS = [ 'px', '%', 'em', 'rem', 'custom' ];
const EMPTY = { top: '', right: '', bottom: '', left: '', unit: 'px', linked: true, customValue: '' };

import { DEVICE_ICONS } from '../shared';
import UnitPicker from './UnitPicker';

export default function DimensionsControl( { ctrl, value, inherited, onChange, device } ) {
	const own = ( value && typeof value === 'object' ) ? value : {};
	const inh = ( inherited && typeof inherited === 'object' ) ? inherited : {};
	
	const v = { ...EMPTY, unit: own.unit ?? inh.unit ?? EMPTY.unit, linked: own.linked ?? inh.linked ?? EMPTY.linked, ...own };
	const linked = v.linked !== false;
	const isCustom = 'custom' === v.unit;

	const setSide = ( side, raw ) => {
		if ( linked ) {
			onChange( { ...v, top: raw, right: raw, bottom: raw, left: raw } );
		} else {
			onChange( { ...v, [ side ]: raw } );
		}
	};

	const toggleLink = () => {
		if ( linked ) {
			const fill = ( x ) => ( x === '' || x == null ) ? '0' : x;
			onChange( { ...v, linked: false, top: fill( v.top ), right: fill( v.right ), bottom: fill( v.bottom ), left: fill( v.left ) } );
		} else {
			onChange( { ...v, linked: true } );
		}
	};

	return (
		<div className="fcf7b-prop fcf7b-dim">
			<div className="fcf7b-ctl-head">
				<label className="fcf7b-prop-label">{ ctrl?.label }{ ctrl?.responsive ? <i className={ `fcf7b-ctl-device ${ DEVICE_ICONS[ device ] }` } title={ device } /> : null }</label>
				<UnitPicker units={ UNITS } value={ v.unit } onChange={ ( u ) => onChange( { ...v, unit: u } ) } />
			</div>
			{ isCustom ? (
				<input
					type="text"
					className="fcf7b-dim-custom"
					value={ own.customValue ?? '' }
					placeholder={ ( inh.customValue ?? '' ) || 'e.g. 10px 20px 30px 40px' }
					onChange={ ( e ) => onChange( { ...v, customValue: e.target.value } ) }
				/>
			) : (
				<>
					<div className="fcf7b-dim-box">
						{ SIDES.map( ( [ k ] ) => (
							<input
								key={ k }
								type="number"
								value={ own[ k ] ?? '' }
								placeholder={ inh[ k ] ?? '' }
								onChange={ ( e ) => setSide( k, e.target.value ) }
							/>
						) ) }
						<button
							type="button"
							className={ `fcf7b-dim-link${ linked ? ' is-linked' : '' }` }
							title={ linked ? __( 'Unlink sides', 'compactform' ) : __( 'Link sides', 'compactform' ) }
							onClick={ toggleLink }
						>
							<i className={ linked ? 'ri-links-line' : 'ri-link-unlink' } />
						</button>
					</div>
					<div className="fcf7b-dim-labels">
						{ SIDES.map( ( [ k, lbl ] ) => <span key={ k }>{ lbl }</span> ) }
						<span />
					</div>
				</>
			) }
		</div>
	);
}
