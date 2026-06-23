import { useState } from '@wordpress/element';

/** A set of blocked calendar dates — add via a date input, remove via chip 'x'. */
export default function DateListControl( { ctrl, value, update } ) {
	const [ pending, setPending ] = useState( '' );
	const dates = value || [];

	const addDate = () => {
		if ( ! pending || dates.includes( pending ) ) {
			return;
		}
		update( ctrl.key, [ ...dates, pending ].sort() );
		setPending( '' );
	};

	const removeDate = ( date ) => {
		update( ctrl.key, dates.filter( ( d ) => d !== date ) );
	};

	return (
		<div className="fcf7b-date-list">
			<div className="fcf7b-date-list-add">
				<input
					type="date"
					value={ pending }
					onChange={ ( e ) => setPending( e.target.value ) }
				/>
				<button type="button" onClick={ addDate } disabled={ ! pending }>
					{ '+' }
				</button>
			</div>
			{ dates.length > 0 && (
				<div className="fcf7b-date-list-chips">
					{ dates.map( ( date ) => (
						<span className="fcf7b-date-list-chip" key={ date }>
							{ date }
							<button type="button" onClick={ () => removeDate( date ) }>{ '×' }</button>
						</span>
					) ) }
				</div>
			) }
		</div>
	);
}
