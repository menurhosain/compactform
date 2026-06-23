import { useEffect, useRef } from '@wordpress/element';
import FieldChrome from './preview-helpers';

const PICKERS = {
	fcf7_date_picker: {
		cls: 'fcf7-datepicker',
		keys: [ 'mode', 'date_format', 'min_date', 'max_date', 'default_date' ],
		global: 'fcf7DatePicker',
	},
	fcf7_time_picker: {
		cls: 'fcf7-timepicker',
		keys: [ 'time_format', 'interval', 'min_time', 'max_time', 'default_time' ],
		global: 'fcf7TimePicker',
	},
	fcf7_datetime_picker: {
		cls: 'fcf7-datetimepicker',
		keys: [ 'mode', 'date_format', 'time_format', 'interval', 'min_date', 'max_date', 'default_date', 'default_time' ],
		global: 'fcf7DateTimePicker',
	},
};

export default function PickerPreview( { field, def } ) {
	const spec = PICKERS[ field.type ] || PICKERS.fcf7_date_picker;
	const ref = useRef( null );

	const config = {};
	spec.keys.forEach( ( k ) => { config[ k ] = field[ k ] == null ? '' : String( field[ k ] ); } );
	const configJson = JSON.stringify( config );

	useEffect( () => {
		const input = ref.current;
		if ( ! input ) { return; }
		const api = window[ spec.global ];
		if ( api && api.init ) { api.init( input.closest( '.fcf7b-pv-field' ) || document ); }

		return () => {
			if ( input._flatpickr ) { input._flatpickr.destroy(); }
		};
	}, [ configJson ] );

	return (
		<FieldChrome field={ field } def={ def } wrapClass="fcf7-field-picker-wrapper">
			<input
				key={ configJson }
				ref={ ref }
				type="text"
				className={ `fcf7b-pv-input wpcf7-form-control ${ spec.cls }` }
				placeholder={ field.placeholder || '' }
				data-config={ configJson }
			/>
		</FieldChrome>
	);
}
