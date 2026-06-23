import { __ } from '@wordpress/i18n';
import Popover from './Popover';
import SliderControl from './SliderControl';
import ColorControl from './ColorControl';
import Select from './Select';

const DEFAULT = { horizontal: '', vertical: '', blur: '', spread: '', color: '', inset: false };
const NUMS = [ 'horizontal', 'vertical', 'blur', 'spread' ];

const n = ( x ) => { const num = Number( x ); return ( x === '' || x == null || isNaN( num ) ) ? 0 : num; };

function compose( v ) {
	const anyNum = NUMS.some( ( k ) => v[ k ] !== '' && v[ k ] != null && ! isNaN( Number( v[ k ] ) ) );
	if ( ! v.color && ! anyNum ) { return ''; }
	return `${ v.inset ? 'inset ' : '' }${ n( v.horizontal ) }px ${ n( v.vertical ) }px ${ n( v.blur ) }px ${ n( v.spread ) }px${ v.color ? ' ' + v.color : '' }`;
}

export default function BoxShadowControl( { value, onChange, onGestureStart, onGestureEnd } ) {
	const v = ( value && typeof value === 'object' ) ? { ...DEFAULT, ...value } : { ...DEFAULT };
	const set = ( patch ) => onChange( { ...v, ...patch } );
	const shadow = compose( v );

	const slider = ( label, key, min, max ) => (
		<SliderControl
			ctrl={ { label, units: [ 'px' ], min, max, step: 1 } }
			value={ { size: v[ key ] ?? '', unit: 'px' } }
			onChange={ ( o ) => set( { [ key ]: o.size } ) }
			onGestureStart={ onGestureStart }
			onGestureEnd={ onGestureEnd }
		/>
	);

	return (
		<Popover
			className="fcf7b-bxsh"
			triggerClassName={ `fcf7b-bxsh-trigger${ shadow ? ' is-set' : '' }` }
			popClassName="fcf7b-bxsh-pop"
			title={ __( 'Edit box shadow', 'compactform' ) }
			trigger={ <i className="ri-pencil-line" /> }
		>
			<div className="fcf7b-pop-head">
				<span>{ __( 'Box Shadow', 'compactform' ) }</span>
				<div className="fcf7b-pop-tools">
					<button type="button" title={ __( 'Reset', 'compactform' ) } onClick={ () => onChange( { ...DEFAULT } ) }>
						<i className="ri-refresh-line" />
					</button>
				</div>
			</div>

			<div className="fcf7b-ctl-row">
				<span className="fcf7b-ctl-rowlabel">{ __( 'Color', 'compactform' ) }</span>
				<ColorControl
					value={ v.color }
					onChange={ ( c ) => set( { color: c } ) }
					onGestureStart={ onGestureStart }
					onGestureEnd={ onGestureEnd }
				/>
			</div>

			{ slider( __( 'Horizontal', 'compactform' ), 'horizontal', -100, 100 ) }
			{ slider( __( 'Vertical', 'compactform' ), 'vertical', -100, 100 ) }
			{ slider( __( 'Blur', 'compactform' ), 'blur', 0, 100 ) }
			{ slider( __( 'Spread', 'compactform' ), 'spread', -100, 100 ) }

			<div className="fcf7b-ctl-row">
				<span className="fcf7b-ctl-rowlabel">{ __( 'Position', 'compactform' ) }</span>
				<Select
					value={ v.inset ? 'inset' : 'outline' }
					options={ [
						{ value: 'outline', label: __( 'Outline', 'compactform' ) },
						{ value: 'inset', label: __( 'Inset', 'compactform' ) },
					] }
					onChange={ ( val ) => set( { inset: 'inset' === val } ) }
				/>
			</div>
		</Popover>
	);
}
