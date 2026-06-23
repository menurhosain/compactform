import { registry } from '../field-preview-registry';
import CustomPreviewHost from './CustomPreviewHost';
import FieldChrome from '../fields/preview-helpers';

export default function FieldPreview( field, def = {}, device = 'desktop', interactions = {} ) {
	const Component = registry.getComponent( field.type );
	if ( Component ) {
		return <Component field={ field } def={ def } device={ device }
			onChange={ interactions.onChange } beginGesture={ interactions.beginGesture } endGesture={ interactions.endGesture }
			allFields={ interactions.allFields } actions={ interactions.actions } selected={ interactions.selected }
			multi={ interactions.multi }
			openMenu={ interactions.openMenu } />;
	}

	const handlers = registry.get( field.type );
	if ( handlers ) {
		return <CustomPreviewHost key={ field.type } field={ field } def={ def } handlers={ handlers } interactions={ interactions } />;
	}

	return (
		<FieldChrome field={ field } def={ def }>
			<input className="fcf7b-pv-input wpcf7-form-control" type="text" placeholder={ field.placeholder || '' } />
		</FieldChrome>
	);
}
