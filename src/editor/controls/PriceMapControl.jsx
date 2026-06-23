
export default function PriceMapControl( { ctrl, value, update, field } ) {
	const options = Array.isArray( field?.options ) ? field.options : [];
	const rows = Array.isArray( value ) ? value : [];

	const priceFor = ( opt ) => {
		const row = rows.find( ( r ) => r.option === opt );
		return row ? row.price : '';
	};

	const setPrice = ( opt, price ) => {
		update( ctrl.key, options.map( ( o ) => ( { option: o, price: o === opt ? price : priceFor( o ) } ) ) );
	};

	if ( ! options.length ) {
		return <p className="fcf7b-price-map-empty">{ 'Add options above first.' }</p>;
	}

	return (
		<div className="fcf7b-price-map">
			{ options.map( ( opt ) => (
				<div className="fcf7b-price-map-row" key={ opt }>
					<span className="fcf7b-price-map-label">{ opt }</span>
					<input
						type="number"
						min="0"
						step="0.01"
						placeholder="0.00"
						value={ priceFor( opt ) }
						onChange={ ( e ) => setPrice( opt, e.target.value ) }
					/>
				</div>
			) ) }
		</div>
	);
}
