import FieldChrome from './preview-helpers';

const STAR_COUNT = 5;
const STAR_PATH = 'M341.5 45.1C337.4 37.1 329.1 32 320.1 32C311.1 32 302.8 37.1 298.7 45.1L225.1 189.3L65.2 214.7C56.3 216.1 48.9 222.4 46.1 231C43.3 239.6 45.6 249 51.9 255.4L166.3 369.9L141.1 529.8C139.7 538.7 143.4 547.7 150.7 553C158 558.3 167.6 559.1 175.7 555L320.1 481.6L464.4 555C472.4 559.1 482.1 558.3 489.4 553C496.7 547.7 500.4 538.8 499 529.8L473.7 369.9L588.1 255.4C594.5 249 596.7 239.6 593.9 231C591.1 222.4 583.8 216.1 574.8 214.7L415 189.3L341.5 45.1z';

export default function StarRatingPreview( { field, def, onChange } ) {
	const selected = parseInt( field.selected, 10 ) || 0;

	return (
		<FieldChrome field={ field } def={ def }>
			<div className="fcf7b-pv-stars fcf7-rating">
				<span className="icon">
					{ Array.from( { length: STAR_COUNT } ).map( ( _, idx ) => {
						const i = idx + 1;
						return (
							<span
								key={ i }
								className={ `fcf7b-pv-star fcf7-star${ i <= selected ? ' is-active' : '' }` }
								title={ onChange ? `Set default rating to ${ i }` : undefined }
								onClick={ onChange ? () => onChange( 'selected', i === selected ? 0 : i ) : undefined }
							>
								<svg viewBox="0 0 640 640" fill="currentColor">
									<path d={ STAR_PATH } />
								</svg>
							</span>
						);
					} ) }
				</span>
			</div>
		</FieldChrome>
	);
}
