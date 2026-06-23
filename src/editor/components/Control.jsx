import { deviceChain, DEVICE_ICONS, isEmptyValue } from '../shared';
import { inheritedFallback } from '../global-defaults';
import { controls } from '../control-registry';
import TextControl from '../controls/TextControl';


export default function Control( { ctrl, value, update, field, beginGesture, endGesture, allFields, device = 'desktop' } ) {
	const type = ctrl.type;

	let inherited;
	if ( ctrl.responsive ) {
		const map = ( value && typeof value === 'object' && ! Array.isArray( value ) ) ? value : {};
		value = map[ device ];
		const from = deviceChain( device ).slice( 1 ).find( ( d ) => ! isEmptyValue( map[ d ] ) );
		inherited = from ? map[ from ] : undefined;

		if ( inherited === undefined && field ) {
			inherited = inheritedFallback( field.type, ctrl.key, device );
		}

		const outer = update;
		update = ( k, v ) => {
			const patch = ( k && typeof k === 'object' ) ? k : { [ k ]: v };
			outer( ctrl.key, { ...map, [ device ]: patch[ ctrl.key ] } );
		};
	}

	const Renderer = controls.getComponent( type ) || TextControl;

	const props = { ctrl, value, inherited, update, field, allFields, device, beginGesture, endGesture };
	const rendered = <Renderer { ...props } />;

	const sep = ( where ) => ctrl.separator === where
		? <div className="fcf7b-ctl-sep" aria-hidden="true" />
		: null;

	const description = ctrl.description
		? <p className="fcf7b-prop-desc">{ ctrl.description }</p>
		: null;

	if ( Renderer.standalone ) {
		return <>{ sep( 'before' ) }{ rendered }{ description }{ sep( 'after' ) }</>;
	}

	const inline = false === ctrl.label_block || 'color' === type || 'typography' === type || 'icon' === type || 'box_shadow' === type;

	return (
		<>
			{ sep( 'before' ) }
			<div className={ `fcf7b-prop${ inline ? ' fcf7b-prop--inline' : '' }` }>
				<label className="fcf7b-prop-label">
					{ ctrl.label }
					{ ctrl.responsive ? <i className={ `fcf7b-ctl-device ${ DEVICE_ICONS[ device ] }` } title={ device } /> : null }
					{ ctrl.hint ? <span className="fcf7b-prop-hint">{ ctrl.hint }</span> : null }
				</label>
				{ rendered }
				{ description }
			</div>
			{ sep( 'after' ) }
		</>
	);
}
