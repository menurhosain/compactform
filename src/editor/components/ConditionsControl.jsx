import { __ } from '@wordpress/i18n';
import { cf7Name, postableFields } from '../shared';
import Select from './Select';
import Switch from './Switch';

// Per-field conditional logic: show/hide this field based on another field's value.
// Value shape: { enabled, action, field, operator, value }.
const OPS = [
	{ v: 'is', t: __( 'is', 'compactform' ) },
	{ v: 'is_not', t: __( 'is not', 'compactform' ) },
	{ v: 'contains', t: __( 'contains', 'compactform' ) },
	{ v: 'not_contains', t: __( "doesn't contain", 'compactform' ) },
	{ v: 'gt', t: __( 'greater than', 'compactform' ) },
	{ v: 'lt', t: __( 'less than', 'compactform' ) },
	{ v: 'empty', t: __( 'is empty', 'compactform' ) },
	{ v: 'filled', t: __( 'is not empty', 'compactform' ) },
];
const DEFAULT = { enabled: false, action: 'show', field: '', operator: 'is', value: '' };

export default function ConditionsControl( { value, onChange, field, allFields } ) {
	const v = ( value && typeof value === 'object' ) ? { ...DEFAULT, ...value } : { ...DEFAULT };
	const set = ( patch ) => onChange( { ...v, ...patch } );

	// Other named fields (a field can't depend on itself). The name is shown too,
	// so two fields with the same label are still tellable apart. A Repeatable
	// Group's row fields are left out for the same reason Repeater_Field::compile()
	// and Conditions::hidden_field_names() skip them — a rule stored against the
	// bare name can't address the per-row `__N` copies.
	const targets = postableFields( allFields )
		.filter( ( f ) => f.id !== field.id )
		.map( ( f ) => ( { name: cf7Name( f.name ), label: f.label || f.type } ) );

	const needsValue = v.operator !== 'empty' && v.operator !== 'filled';

	return (
		<div className="fcf7b-prop fcf7b-cond-ctl">
			<div className="fcf7b-prop fcf7b-prop--inline fcf7b-switch-row">
				<span className="fcf7b-prop-label">{ __( 'Enable conditional logic', 'compactform' ) }</span>
				<Switch checked={ v.enabled } onChange={ ( on ) => set( { enabled: on } ) } />
			</div>

			{ v.enabled ? (
				<div className="fcf7b-cond-body">
					<div className="fcf7b-ctl-row">
						<span className="fcf7b-ctl-rowlabel">{ __( 'Action', 'compactform' ) }</span>
						<Select
							value={ v.action }
							options={ [
								{ value: 'show', label: __( 'Show', 'compactform' ) },
								{ value: 'hide', label: __( 'Hide', 'compactform' ) },
							] }
							onChange={ ( val ) => set( { action: val } ) }
						/>
					</div>

					<div className="fcf7b-ctl-row">
						<span className="fcf7b-ctl-rowlabel">{ __( 'Field', 'compactform' ) }</span>
						<Select
							value={ v.field }
							options={ [ { value: '', label: __( '— select —', 'compactform' ) }, ...targets.map( ( t ) => ( { value: t.name, label: `${ t.label } (${ t.name })` } ) ) ] }
							onChange={ ( val ) => set( { field: val } ) }
						/>
					</div>

					<div className="fcf7b-ctl-row">
						<span className="fcf7b-ctl-rowlabel">{ __( 'Condition', 'compactform' ) }</span>
						<Select
							value={ v.operator }
							options={ OPS.map( ( o ) => ( { value: o.v, label: o.t } ) ) }
							onChange={ ( val ) => set( { operator: val } ) }
						/>
					</div>

					{ needsValue ? (
						<div className="fcf7b-ctl-row">
							<span className="fcf7b-ctl-rowlabel">{ __( 'Value', 'compactform' ) }</span>
							<input type="text" value={ v.value } onChange={ ( e ) => set( { value: e.target.value } ) } />
						</div>
					) : null }

					{ ! v.field ? <p className="fcf7b-cond-hint">{ __( 'Pick the field this one reacts to.', 'compactform' ) }</p> : null }
				</div>
			) : null }
		</div>
	);
}
