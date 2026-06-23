import { __ } from '@wordpress/i18n';
import FieldChrome from './preview-helpers';


const MAX_TICKS = 50;

const num = ( value, fallback ) => (
	value === '' || value === null || value === undefined || isNaN( value )
		? fallback
		: Number( value )
);

const clamp = ( value, min, max ) => Math.max( min, Math.min( max, value ) );

const positionOf = ( ratio ) => (
	`calc(var(--fcf7-range-handle-size) / 2 + ${ Math.round( ratio * 100000 ) / 100000 } * (100% - var(--fcf7-range-handle-size)))`
);

const decimalsOf = ( step ) => {
	const dot = String( step ).indexOf( '.' );
	return dot === -1 ? 0 : String( step ).length - dot - 1;
};

export default function RangeSliderPreview( { field, def } ) {
	const isDouble = String( field.handles ?? '1' ) === '2';

	const min = num( field.min, 0 );
	let max = num( field.max, 100 );
	if ( max <= min ) {
		max = min + 100;
	}
	let step = num( field.step, 1 );
	if ( step <= 0 ) {
		step = 1;
	}

	const to = clamp( num( isDouble ? field.default_max : field.default_value, max ), min, max );
	const from = isDouble ? clamp( num( field.default_min, min ), min, to ) : min;

	const span = max - min || 1;
	const ratioOf = ( value ) => clamp( ( value - min ) / span, 0, 1 );
	const fromRatio = isDouble ? ratioOf( from ) : 0;
	const toRatio = ratioOf( to );

	const suffix = field.suffix || '';
	const separator = field.separator || '-';
	const minLabel = field.min_label || __( 'Min', 'compactform' );
	const maxLabel = field.max_label || __( 'Max', 'compactform' );
	const valuePos = field.value_pos || 'bubble';
	// Inverted, matching the `hide_*` switchers — see the note on their controls.
	const showValue = ! field.hide_value;
	const showMinMax = ! field.hide_minmax;
	const ticks = clamp( Math.floor( num( field.ticks, 0 ) ), 0, MAX_TICKS );

	const decimals = decimalsOf( step );
	const fmt = ( value ) => ( decimals > 0 ? Number( value ).toFixed( decimals ) : String( Math.round( value ) ) );

	const readoutItem = ( value, handle, ratio, label ) => (
		<span
			className="fcf7-range-readout-item"
			data-handle={ handle }
			key={ handle }
			style={ { '--pos': positionOf( ratio ) } }
		>
			{ valuePos === 'split' && label ? (
				<span className="fcf7-range-readout-label">{ label }</span>
			) : null }
			<span className="fcf7-range-number">{ fmt( value ) }</span>
			<span className="fcf7-range-suffix">{ suffix }</span>
		</span>
	);

	const readout = showValue ? (
		<span className="fcf7-range-readout">
			{ isDouble ? readoutItem( from, 'from', fromRatio, minLabel ) : null }
			{ isDouble ? <span className="fcf7-range-readout-sep">{ separator }</span> : null }
			{ readoutItem( to, 'to', toRatio, isDouble ? maxLabel : '' ) }
		</span>
	) : null;

	const readoutBefore = valuePos !== 'bottom' && valuePos !== 'split';

	const tickList = ticks > 0 ? Array.from( { length: ticks + 1 }, ( _, i ) => i ) : [];

	return (
		<FieldChrome field={ field } def={ def }>
			<span
				className={ `wpcf7-form-control fcf7-range fcf7-range--${ isDouble ? 'double' : 'single' } fcf7-range--value-${ valuePos }` }
				style={ {
					'--fcf7-range-from': isDouble ? positionOf( fromRatio ) : '0%',
					'--fcf7-range-to': positionOf( toRatio ),
				} }
			>
				{ readoutBefore ? readout : null }

				<span className="fcf7-range-track">
					<span className="fcf7-range-rail" />
					<span className="fcf7-range-fill" />
					{ ticks > 0 ? (
						<span className="fcf7-range-ticks">
							{ tickList.map( ( i ) => (
								<span
									className="fcf7-range-tick"
									key={ i }
									style={ { '--pos': positionOf( i / ticks ) } }
								/>
							) ) }
						</span>
					) : null }
					{ isDouble ? (
						<input
							type="range"
							className="fcf7-range-input fcf7-range-input--from"
							min={ min }
							max={ max }
							value={ from }
							readOnly
							tabIndex={ -1 }
						/>
					) : null }
					<input
						type="range"
						className="fcf7-range-input fcf7-range-input--to"
						min={ min }
						max={ max }
						value={ to }
						readOnly
						tabIndex={ -1 }
					/>
				</span>

				{ ticks > 0 ? (
					<span className="fcf7-range-tick-labels">
						{ tickList.map( ( i ) => (
							<span
								className="fcf7-range-tick-label"
								key={ i }
								style={ { '--pos': positionOf( i / ticks ) } }
							>
								{ fmt( min + ( max - min ) * i / ticks ) }
							</span>
						) ) }
					</span>
				) : null }

				{ showMinMax ? (
					<span className="fcf7-range-scale">
						<span className="fcf7-range-scale-min">
							{ minLabel ? <span className="fcf7-range-scale-label">{ minLabel }</span> : null }
							<span className="fcf7-range-scale-number">{ min }{ suffix }</span>
						</span>
						<span className="fcf7-range-scale-max">
							{ maxLabel ? <span className="fcf7-range-scale-label">{ maxLabel }</span> : null }
							<span className="fcf7-range-scale-number">{ max }{ suffix }</span>
						</span>
					</span>
				) : null }

				{ readoutBefore ? null : readout }
			</span>
		</FieldChrome>
	);
}
