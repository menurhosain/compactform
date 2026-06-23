import { __ } from '@wordpress/i18n';
import Popover from './Popover';

const isCustom = ( u ) => 'custom' === u;
const unitLabel = ( u ) => isCustom( u ) ? <i className="ri-pencil-line" /> : u;

export default function UnitPicker( { units, value, onChange } ) {
	if ( ! units || units.length < 2 ) {
		return <span className="fcf7b-ctl-unit is-static">{ unitLabel( value || ( units && units[ 0 ] ) ) }</span>;
	}

	return (
		<Popover
			className="fcf7b-unit"
			triggerClassName={ `fcf7b-ctl-unit fcf7b-unit-trigger${ isCustom( value ) ? ' is-custom' : '' }` }
			popClassName="fcf7b-unit-pop"
			title={ __( 'Unit', 'compactform' ) }
			trigger={ (
				<>
					<span className="fcf7b-unit-cur">{ unitLabel( value ) }</span>
					<i className="ri-arrow-down-s-line fcf7b-unit-caret" />
				</>
			) }
		>
			{ ( { close } ) => (
				<ul className="fcf7b-unit-list">
					{ units.map( ( u ) => (
						<li key={ u }>
							<button
								type="button"
								className={ `fcf7b-unit-opt${ u === value ? ' is-active' : '' }` }
								title={ isCustom( u ) ? __( 'Custom', 'compactform' ) : u }
								onClick={ () => { onChange( u ); close(); } }
							>
								{ unitLabel( u ) }
							</button>
						</li>
					) ) }
				</ul>
			) }
		</Popover>
	);
}
