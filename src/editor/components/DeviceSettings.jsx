import { createInterpolateElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { DEFAULT_BREAKPOINTS, DEVICE_ICONS, DEVICES, deviceChain, isEmptyValue } from '../shared';
import DimensionsControl from './DimensionsControl';
import { CONTAINER_CSS_DEFAULTS, FIELD_GAP_CSS_DEFAULT, uniformBox } from '../global-defaults';

const BREAKPOINT_ROWS = [
	[ 'tablet', __( 'Tablet', 'compactform' ) ],
	[ 'mobile', __( 'Mobile', 'compactform' ) ],
];

const DEVICE_LABELS = {
	desktop: __( 'Desktop', 'compactform' ),
	tablet: __( 'Tablet', 'compactform' ),
	mobile: __( 'Mobile', 'compactform' ),
};


export default function DeviceSettings( {
	breakpoints = {},
	onChange,
	fieldGap,
	onFieldGapChange,
	containerColumnGap,
	onContainerColumnGapChange,
	containerRowGap,
	onContainerRowGapChange,
	containerPadding,
	onContainerPaddingChange,
} ) {
	const [ device, setDevice ] = useState( 'desktop' );

	const setBreakpoint = ( d, raw ) => {
		const next = { ...breakpoints };
		const n = parseInt( raw, 10 );
		if ( isNaN( n ) ) { delete next[ d ]; } else { next[ d ] = n; }
		onChange( next );
	};

	const asMap = ( value ) => {
		if ( value && 'object' === typeof value && ! Array.isArray( value ) ) { return value; }
		return ( value === undefined || value === null || '' === value ) ? {} : { desktop: value };
	};

	const setGap = ( value, onGapChange, max, raw ) => {
		const next = { ...asMap( value ) };
		const n = parseInt( raw, 10 );
		if ( isNaN( n ) ) { delete next[ device ]; } else { next[ device ] = Math.max( 0, Math.min( max, n ) ); }
		onGapChange( Object.keys( next ).length ? next : undefined );
	};

	const inherited = ( value, fallback ) => {
		const map = asMap( value );
		const hit = deviceChain( device ).slice( 1 ).find( ( d ) => map[ d ] !== undefined );
		return hit === undefined ? fallback : String( map[ hit ] );
	};

	const padding = asMap( containerPadding );
	const padInheritedFrom = deviceChain( device ).slice( 1 ).find( ( d ) => ! isEmptyValue( padding[ d ] ) );

	const setPadding = ( next ) => {
		const out = { ...padding };
		if ( isEmptyValue( next ) ) { delete out[ device ]; } else { out[ device ] = next; }
		onContainerPaddingChange( Object.keys( out ).length ? out : undefined );
	};

	const gapValue = ( value ) => {
		const v = asMap( value )[ device ];
		return v ?? '';
	};

	const numberRow = ( label, value, onGapChange, max, fallback ) => (
		<div className="fcf7b-gspanel-row">
			<span className="fcf7b-gspanel-rowlabel">{ label }</span>
			<span className="fcf7b-gspanel-num">
				<input
					type="number"
					min={ 0 }
					max={ max }
					step={ 1 }
					placeholder={ inherited( value, fallback ) }
					value={ gapValue( value ) }
					onChange={ ( e ) => setGap( value, onGapChange, max, e.target.value ) }
				/>
				<em>px</em>
			</span>
		</div>
	);

	return (
		<div className="fcf7b-gspanel">
			<div className="fcf7b-gspanel-sec">
				<h3 className="fcf7b-gspanel-head">{ __( 'Breakpoints', 'compactform' ) }</h3>

				{ BREAKPOINT_ROWS.map( ( [ d, label ] ) => (
					<div className="fcf7b-gspanel-row" key={ d }>
						<span className="fcf7b-gspanel-rowlabel">
							<i className={ DEVICE_ICONS[ d ] } />
							{ label }
						</span>
						<span className="fcf7b-gspanel-num">
							<input
								type="number"
								min={ 320 }
								max={ 2560 }
								step={ 1 }
								placeholder={ String( DEFAULT_BREAKPOINTS[ d ] ) }
								value={ breakpoints[ d ] ?? '' }
								onChange={ ( e ) => setBreakpoint( d, e.target.value ) }
							/>
							<em>px</em>
						</span>
					</div>
				) ) }

				<p className="fcf7b-gspanel-desc">
					{ createInterpolateElement(
						__( "A device's styles apply at its width <strong>and below</strong>. Blank uses the default.", 'compactform' ),
						{ strong: <strong /> }
					) }
				</p>
			</div>

			<div className="fcf7b-gspanel-sec">
				<h3 className="fcf7b-gspanel-head fcf7b-gspanel-head--split">
					{ __( 'Spacing', 'compactform' ) }
					<span className="fcf7b-gspanel-devsw">
						{ DEVICES.map( ( d ) => (
							<button
								key={ d }
								type="button"
								className={ device === d ? 'is-active' : '' }
								title={ DEVICE_LABELS[ d ] }
								onClick={ () => setDevice( d ) }
							><i className={ DEVICE_ICONS[ d ] } /></button>
						) ) }
					</span>
				</h3>

				{ numberRow( __( 'Field gap', 'compactform' ), fieldGap, onFieldGapChange, 200, String( FIELD_GAP_CSS_DEFAULT ) ) }

				{ numberRow( __( 'Container gap (horizontal)', 'compactform' ), containerColumnGap, onContainerColumnGapChange, 120, String( CONTAINER_CSS_DEFAULTS.columnGap ) ) }

				{ numberRow( __( 'Container gap (vertical)', 'compactform' ), containerRowGap, onContainerRowGapChange, 120, String( CONTAINER_CSS_DEFAULTS.rowGap ) ) }

				<DimensionsControl
					ctrl={ { label: __( 'Container padding', 'compactform' ) } }
					device={ device }
					value={ padding[ device ] }
					inherited={ padInheritedFrom === undefined ? uniformBox( CONTAINER_CSS_DEFAULTS.padding ) : padding[ padInheritedFrom ] }
					onChange={ setPadding }
				/>

				<p className="fcf7b-gspanel-desc">
					{ __( "Field gap sits below every top-level field; the Container values apply inside every Container. A field's or Container's own setting overrides these, and a blank one inherits the wider device.", 'compactform' ) }
				</p>
			</div>
		</div>
	);
}
