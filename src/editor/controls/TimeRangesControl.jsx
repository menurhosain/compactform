import { __ } from '@wordpress/i18n';

export default function TimeRangesControl( { ctrl, value, update } ) {
	const ranges = Array.isArray( value ) ? value : [];

	const setRanges = ( next ) => update( ctrl.key, next );

	const addRange = () => setRanges( [ ...ranges, { from: '12:00', to: '13:00' } ] );

	const changeRange = ( i, key, val ) => {
		setRanges( ranges.map( ( r, idx ) => idx === i ? { ...r, [ key ]: val } : r ) );
	};

	const removeRange = ( i ) => setRanges( ranges.filter( ( _, idx ) => idx !== i ) );

	return (
		<div className="fcf7b-steps-ctl">
			{ ranges.map( ( range, i ) => (
				<div className="fcf7b-steps-ctl-row" key={ i }>
					<input
						type="time"
						className="fcf7b-steps-ctl-label"
						value={ range.from || '' }
						onChange={ ( e ) => changeRange( i, 'from', e.target.value ) }
					/>
					<span>{ '–' }</span>
					<input
						type="time"
						className="fcf7b-steps-ctl-label"
						value={ range.to || '' }
						onChange={ ( e ) => changeRange( i, 'to', e.target.value ) }
					/>
					<button
						type="button"
						className="fcf7b-steps-ctl-remove"
						title={ __( 'Remove range', 'compactform' ) }
						onClick={ () => removeRange( i ) }
					>
						<i className="ri-delete-bin-line" />
					</button>
				</div>
			) ) }
			<button type="button" className="fcf7b-steps-ctl-add" onClick={ addRange }>
				<i className="ri-add-line" /> Add Blocked Range
			</button>
		</div>
	);
}
