import { BY_TYPE } from '../shared';
import { meetsCondition } from '../condition';
import Popover from './Popover';
import Control from './Control';

const same = ( a, b ) => JSON.stringify( a ?? null ) === JSON.stringify( b ?? null );

export default function PopoverGroup( { toggleCtrl, controls, field, update, beginGesture, endGesture, allFields, device } ) {
	const on = !! field[ toggleCtrl.key ];
	const defaults = ( BY_TYPE[ field.type ] || {} ).defaults || {};

	const reset = () => {
		const patch = { [ toggleCtrl.key ]: false };
		controls.forEach( ( c ) => { patch[ c.key ] = defaults[ c.key ]; } );
		update( patch );
	};

	const visible = controls.filter( ( c ) => meetsCondition( c.condition, field, device ) );

	return (
		<div className={ `fcf7b-popgroup${ on ? ' is-on' : '' }` }>
			<div className="fcf7b-popgroup-head">
				<label className="fcf7b-prop-label">{ toggleCtrl.label }</label>

				<span className="fcf7b-popgroup-tools">
					{ on ? (
						<button type="button" className="fcf7b-popgroup-reset" title="Reset" onClick={ reset }>
							<i className="ri-restart-line" aria-hidden="true" />
						</button>
					) : null }

					<Popover
						className="fcf7b-popgroup-pop"
						triggerClassName={ `fcf7b-popgroup-edit${ on ? ' is-on' : '' }` }
						popClassName="fcf7b-popgroup-panel"
						title={ on ? `Edit ${ toggleCtrl.label }` : `Enable ${ toggleCtrl.label }` }
						onOpenChange={ ( isOpen ) => { if ( isOpen && ! on ) { update( toggleCtrl.key, true ); } } }
						trigger={ <i className="ri-pencil-line" aria-hidden="true" /> }
					>
						{ visible.map( ( ctrl ) => (
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
					</Popover>
				</span>
			</div>
		</div>
	);
}
