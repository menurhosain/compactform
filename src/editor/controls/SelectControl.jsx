import { __ } from '@wordpress/i18n';
import Select from '../components/Select';
import { cf7Name, postableFields, repeaterRowParents } from '../shared';

export default function SelectControl( { ctrl, value, inherited, update, field, allFields } ) {
	const multiple = !! ( ctrl.multiSelect || ctrl.multiple );
	const creatable = !! ( ctrl.create || ctrl.insert_option );
	const fallback = multiple ? [] : '';
	const shown = value !== undefined ? value : ( inherited ?? fallback );
	const chosen = Array.isArray( shown ) ? shown.map( String ) : ( shown ? [ String( shown ) ] : [] );
	const opts = ctrl.options || {};
	let options;
	if ( ctrl.sourceFields ) {
		const rows = ctrl.sourceFieldsRows ? repeaterRowParents( allFields ) : new Map();
		const pool = ctrl.sourceFieldsRows
			? ( Array.isArray( allFields ) ? allFields : [] ).filter( ( f ) => f && f.name )
			: postableFields( allFields );

		options = [
			...( multiple ? [] : [ { value: '', label: ctrl.emptyLabel || '— none —' } ] ),
			...pool
				.filter( ( f ) => f.id !== field?.id )
				.filter( ( f ) => ! ctrl.sourceFieldsType || f.type === ctrl.sourceFieldsType )
				.map( ( f ) => {
					const name = cf7Name( f.name );
					const group = rows.get( name );

					return {
						value: name,
						label: group
							? `${ f.label || f.type } (${ name }) — ${ group }`
							: `${ f.label || f.type } (${ name })`,
					};
				} ),
		];

		const seen = new Set();
		options = options.filter( ( o ) => {
			if ( seen.has( o.value ) ) { return false; }
			seen.add( o.value );

			return true;
		} );
	} else if ( ctrl.get_option ) {
		const src = Array.isArray( field?.[ ctrl.get_option ] ) ? field[ ctrl.get_option ] : [];
		options = src.map( ( v ) => ( { value: String( v ), label: String( v ) } ) );
		if ( ! multiple ) {
			options = [ { value: '', label: __( 'None', 'compactform' ) }, ...options ];
		}
	} else if ( Array.isArray( ctrl.optionList ) ) {
		options = ctrl.optionList;
	} else {
		options = Object.keys( opts ).map( ( k ) => ( { value: k, label: opts[ k ] } ) );
	}

	if ( ! creatable ) {
		return (
			<Select
				value={ shown }
				options={ options }
				multiple={ multiple }
				onChange={ ( v ) => update( ctrl.key, v ) }
			/>
		);
	}

	/* ----------------------------------------------------------------------- *
	 * Creatable select: the custom options live in the schema under a
	 * companion key (`<key>__opts`), NOT in component state — so they persist
	 * across reload and export, an unchecked one stays until removed with ✕,
	 * and nothing bleeds between fields. The server whitelists this key for any
	 * control flagged `create` (see Fields_Manager::sanitize_schema()).
	 * ----------------------------------------------------------------------- */
	const poolKey = `${ ctrl.key }__opts`;
	const predefinedVals = options.map( ( o ) => String( o.value ) );
	const predefinedSet = new Set( predefinedVals );

	const stored = Array.isArray( field?.[ poolKey ] ) ? field[ poolKey ].map( String ) : [];
	const pool = [ ...stored ];
	chosen.forEach( ( v ) => {
		if ( ! predefinedSet.has( v ) && ! pool.includes( v ) ) { pool.push( v ); }
	} );

	const buildValue = ( checkedSet, poolArr ) => [
		...predefinedVals.filter( ( v ) => checkedSet.has( v ) ),
		...poolArr.filter( ( v ) => checkedSet.has( v ) ),
	];

	const writeBoth = ( checkedSet, poolArr ) => {
		if ( multiple ) {
			update( { [ ctrl.key ]: buildValue( checkedSet, poolArr ), [ poolKey ]: poolArr } );
		} else {
			update( { [ ctrl.key ]: [ ...checkedSet ][ 0 ] ?? '', [ poolKey ]: poolArr } );
		}
	};

	const handleChange = ( arr ) => {
		const set = new Set( ( Array.isArray( arr ) ? arr : ( arr ? [ arr ] : [] ) ).map( String ) );
		writeBoth( set, pool );
	};

	const handleCreate = ( raw ) => {
		const v = String( raw ).trim();
		if ( '' === v || predefinedSet.has( v ) ) { return; }
		const poolArr = pool.includes( v ) ? pool : [ ...pool, v ];
		const set = new Set( chosen );
		set.add( v );
		writeBoth( set, poolArr );
	};

	const handleRemove = ( raw ) => {
		const v = String( raw );
		const poolArr = pool.filter( ( x ) => x !== v );
		const set = new Set( chosen );
		set.delete( v );
		writeBoth( set, poolArr );
	};

	const handleReorder = ( nextCustom ) => {
		writeBoth( new Set( chosen ), nextCustom.map( String ) );
	};

	options = [ ...options, ...pool.map( ( v ) => ( { value: v, label: v, custom: true } ) ) ];

	return (
		<Select
			value={ shown }
			options={ options }
			multiple={ multiple }
			creatable={ creatable }
			onChange={ handleChange }
			onCreate={ handleCreate }
			onRemove={ handleRemove }
			onReorder={ handleReorder }
		/>
	);
}
