import { useState } from '@wordpress/element';
import { meetsCondition } from '../condition';
import Control from './Control';

export default function TabsGroup( { items, controls, field, update, beginGesture, endGesture, allFields, device } ) {
	const visibleTabs = ( items || [] ).filter( ( t ) => meetsCondition( t.condition, field, device ) );
	const [ active, setActive ] = useState( visibleTabs[ 0 ]?.id );

	if ( ! visibleTabs.length ) { return null; }

	const activeId = visibleTabs.some( ( t ) => t.id === active ) ? active : visibleTabs[ 0 ].id;

	const tabControls = ( controls || [] ).filter(
		( c ) => c.tab_item === activeId && meetsCondition( c.condition, field, device )
	);

	return (
		<div className="fcf7b-ctabs">
			<div className="fcf7b-ctabs-nav">
				{ visibleTabs.map( ( t ) => (
					<button
						key={ t.id }
						type="button"
						className={ `fcf7b-ctab${ t.id === activeId ? ' active' : '' }` }
						onClick={ () => setActive( t.id ) }
					>
						{ t.label }
					</button>
				) ) }
			</div>
			<div className="fcf7b-ctabs-body">
				{ tabControls.map( ( ctrl ) => (
					<Control
						key={ ctrl.key }
						ctrl={ ctrl }
						field={ field }
						value={ field[ ctrl.key ] }
						update={ update }
						beginGesture={ beginGesture }
						endGesture={ endGesture }
						allFields={ allFields }
						device={ device }
					/>
				) ) }
			</div>
		</div>
	);
}
