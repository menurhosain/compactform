import { createInterpolateElement, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { DATA, cf7Name, postableFields } from '../shared';
import Select from './Select';
import Switch from './Switch';

const DEFAULT_VALUE = {
	enabled: false,
	url: '',
	method: 'POST',
	format: 'json',
	bodyMode: 'all',
	body: [],
	headers: [],
	meta: false,
	timeout: 10,
	blocking: false,
};

const METHODS = [ 'POST', 'GET', 'PUT', 'PATCH', 'DELETE' ].map( ( m ) => ( { value: m, label: m } ) );

const FORMATS = [
	{ value: 'json', label: 'JSON' },
	{ value: 'form', label: __( 'Form-encoded', 'compactform' ) },
];

const HEADER_MODES = [
	{ value: 'value', label: __( 'Fixed text', 'compactform' ) },
	{ value: 'field', label: __( 'From the form', 'compactform' ) },
];

const QUERY_METHODS = [ 'GET', 'DELETE' ];

const PANES = [
	{ id: 'endpoint', label: __( 'Endpoint', 'compactform' ) },
	{ id: 'payload', label: __( 'Payload', 'compactform' ) },
	{ id: 'advanced', label: __( 'Advanced', 'compactform' ) },
];

const headerName = ( n ) => String( n || '' ).replace( /[^A-Za-z0-9\-_]/g, '' );

const asSources = ( source ) => Array.isArray( source ) ? source : ( source ? [ source ] : [] );

export default function WebhookTab( { value = DEFAULT_VALUE, onChange, fields = [] } ) {
	const v = ( value && typeof value === 'object' ) ? { ...DEFAULT_VALUE, ...value } : { ...DEFAULT_VALUE };
	const set = ( patch ) => onChange( { ...v, ...patch } );

	const [ pane, setPane ] = useState( 'endpoint' );

	const metaChoices = DATA.integrations?.webhook?.metaChoices || [];
	const inQuery = QUERY_METHODS.indexOf( v.method ) !== -1;

	const namedFields = postableFields( fields );

	const fieldChoices = namedFields
		.map( ( f ) => ( { value: cf7Name( f.name ), label: `${ f.label || f.type } (${ cf7Name( f.name ) })` } ) );

	const knownSources = [
		...fieldChoices,
		...metaChoices.map( ( m ) => ( { value: m.value, label: `${ m.label } (metadata)` } ) ),
	];

	const usedSources = [];
	( Array.isArray( v.body ) ? v.body : [] ).forEach( ( r ) => usedSources.push( ...asSources( r && r.source ) ) );
	( Array.isArray( v.headers ) ? v.headers : [] ).forEach( ( r ) => {
		if ( r && 'field' === r.mode && r.value ) {
			usedSources.push( r.value );
		}
	} );

	const orphanSources = [ ...new Set( usedSources ) ]
		.filter( ( t ) => ! knownSources.some( ( o ) => String( o.value ) === String( t ) ) )
		.map( ( t ) => ( { value: t, label: `${ t } (no longer in this form)` } ) );

	const sourceChoices = [ ...knownSources, ...orphanSources ];

	const rows = ( list ) => Array.isArray( v[ list ] ) ? v[ list ] : [];
	const setRow = ( list, i, patch ) => set( { [ list ]: rows( list ).map( ( r, n ) => ( n === i ? { ...r, ...patch } : r ) ) } );
	const addRow = ( list, blank ) => set( { [ list ]: [ ...rows( list ), blank ] } );
	const delRow = ( list, i ) => set( { [ list ]: rows( list ).filter( ( r, n ) => n !== i ) } );

	const halfRows = rows( 'body' ).filter( ( r ) => ( !! r.key ) !== ( asSources( r.source ).length > 0 ) ).length;

	const badUrl = v.url && ! /^https?:\/\//i.test( v.url.trim() );

	// Preview
	const sampleOf = ( token ) => {
		const opt = sourceChoices.find( ( o ) => String( o.value ) === String( token ) );

		return `{${ opt ? opt.label.replace( /\s*\([^)]*\)$/, '' ) : token }}`;
	};

	const previewPayload = () => {
		const out = {};

		if ( 'mapped' === v.bodyMode ) {
			rows( 'body' ).forEach( ( row ) => {
				const sources = asSources( row.source );

				if ( row.key && sources.length ) {
					out[ row.key ] = sources.map( sampleOf ).join( ', ' );
				}
			} );

			return out;
		}

		namedFields.forEach( ( f ) => {
			out[ cf7Name( f.name ) ] = `{${ f.label || f.type }}`;
		} );

		if ( v.meta ) {
			metaChoices.forEach( ( m ) => {
				if ( undefined === out[ m.label ] ) {
					out[ m.label ] = `{${ m.label }}`;
				}
			} );
		}

		return out;
	};

	const previewText = () => {
		const payload = previewPayload();
		const pairs = Object.keys( payload ).map( ( k ) => `${ k }=${ payload[ k ] }` ).join( '&' );
		const url = v.url.trim() || 'https://…';

		const headers = inQuery ? {} : {
			'Content-Type': 'json' === v.format ? 'application/json' : 'application/x-www-form-urlencoded',
		};

		rows( 'headers' ).forEach( ( row ) => {
			const name = headerName( row.name );

			if ( name ) {
				headers[ name ] = 'field' === row.mode
					? ( row.value ? sampleOf( row.value ) : '' )
					: ( row.value || '' );
			}
		} );

		const lines = [ `${ v.method } ${ url }${ inQuery && pairs ? `?${ pairs }` : '' }` ];

		Object.keys( headers ).forEach( ( name ) => lines.push( `${ name }: ${ headers[ name ] }` ) );

		if ( ! inQuery ) {
			lines.push( '' );
			lines.push( 'json' === v.format ? JSON.stringify( payload, null, 2 ) : ( pairs || '(nothing to send)' ) );
		}

		return lines.join( '\n' );
	};

	return (
		<div className="fcf7b-gspanel">
			<h3 className="fcf7b-gspanel-head">{ __( 'Webhook', 'compactform' ) }</h3>

			<p className="fcf7b-gspanel-desc fcf7b-gspanel-intro">
				{ __( 'Hands every submission straight to another service the moment the form is sent — Pabbly Connect, Zapier, Make or your own API. Spam and failed submissions are never sent.', 'compactform' ) }
			</p>

			<div className="fcf7b-gspanel-switch">
				<span className="fcf7b-gspanel-switchlabel">{ __( 'Enable webhook for this form', 'compactform' ) }</span>
				<Switch checked={ v.enabled } onChange={ ( on ) => set( { enabled: on } ) } labelOn={ __( 'Yes', 'compactform' ) } labelOff={ __( 'No', 'compactform' ) } />
			</div>

			{ v.enabled ? (
				<>
					<div className="fcf7b-gspanel-tabs">
						{ PANES.map( ( p ) => (
							<button
								key={ p.id }
								type="button"
								className={ `fcf7b-gspanel-tab${ pane === p.id ? ' is-active' : '' }` }
								onClick={ () => setPane( p.id ) }
							>
								{ p.label }
							</button>
						) ) }
					</div>

					{ 'endpoint' === pane ? (
						<div className="fcf7b-gspanel-field">
							<label htmlFor="fcf7-wh-url">{ __( 'Request URL', 'compactform' ) }</label>
							<input
								type="text"
								id="fcf7-wh-url"
								value={ v.url || '' }
								placeholder="https://connect.pabbly.com/workflow/sendwebhookdata/…"
								onChange={ ( e ) => set( { url: e.target.value } ) }
							/>
							{ badUrl ? (
								<p className="fcf7b-gspanel-desc fcf7b-gspanel-warn">
									{ createInterpolateElement(
										__( 'This doesn’t start with <http/> or <https/>, so it will be discarded when you save.', 'compactform' ),
										{ http: <code>http://</code>, https: <code>https://</code> }
									) }
								</p>
							) : (
								<p className="fcf7b-gspanel-desc">
									{ __( 'The address the other service gave you — it may call this a webhook URL, an endpoint, or an incoming URL.', 'compactform' ) }
								</p>
							) }
						</div>
					) : null }

					{ 'payload' === pane ? (
						<>
							<div className="fcf7b-gspanel-switch">
								<span className="fcf7b-gspanel-switchlabel">
									{ __( 'Send every field', 'compactform' ) }
									<em>{ __( 'Off — send only the keys you list below, renamed however the service expects.', 'compactform' ) }</em>
								</span>
								<Switch
									checked={ 'all' === v.bodyMode }
									onChange={ ( on ) => set( { bodyMode: on ? 'all' : 'mapped' } ) }
									labelOn={ __( 'All', 'compactform' ) }
									labelOff={ __( 'Mapped', 'compactform' ) }
								/>
							</div>

							{ 'all' === v.bodyMode ? (
								<div className="fcf7b-gspanel-switch">
									<span className="fcf7b-gspanel-switchlabel">
										{ __( 'Include submission metadata', 'compactform' ) }
										<em>{ __( 'Adds IP, page URL, date, site and user details alongside the answers.', 'compactform' ) }</em>
									</span>
									<Switch checked={ v.meta } onChange={ ( on ) => set( { meta: on } ) } labelOn={ __( 'Yes', 'compactform' ) } labelOff={ __( 'No', 'compactform' ) } />
								</div>
							) : (
								<div className="fcf7b-gspanel-field">
									<label>{ __( 'Payload keys', 'compactform' ) }</label>

									<div className="fcf7b-gspanel-rows">
										{ rows( 'body' ).length ? (
											<div className="fcf7b-gspanel-rowline fcf7b-gspanel-rowhead">
												<span className="fcf7b-gspanel-cell">{ __( 'Name the service expects', 'compactform' ) }</span>
												<span className="fcf7b-gspanel-cell">{ __( 'Value from this form', 'compactform' ) }</span>
												<span className="fcf7b-gspanel-headspacer" />
											</div>
										) : null }

										{ rows( 'body' ).map( ( row, i ) => (
											<div className="fcf7b-gspanel-rowline" key={ i }>
												<div className="fcf7b-gspanel-cell">
													<input
														type="text"
														value={ row.key || '' }
														placeholder={ __( 'e.g. email', 'compactform' ) }
														onChange={ ( e ) => setRow( 'body', i, { key: e.target.value } ) }
													/>
												</div>
												<div className="fcf7b-gspanel-cell">
													<Select
														multiple
														value={ asSources( row.source ) }
														options={ sourceChoices }
														onChange={ ( s ) => setRow( 'body', i, { source: s } ) }
														placeholder={ knownSources.length ? __( 'Pick a field…', 'compactform' ) : __( 'This form has no named fields yet', 'compactform' ) }
													/>
												</div>
												<button
													type="button"
													className="fcf7b-gspanel-del"
													title={ __( 'Remove this key', 'compactform' ) }
													onClick={ () => delRow( 'body', i ) }
												>
													<i className="ri-delete-bin-line" />
												</button>
											</div>
										) ) }

										{ rows( 'body' ).length ? null : (
											<p className="fcf7b-gspanel-none">{ __( 'No keys yet — nothing would be sent.', 'compactform' ) }</p>
										) }
									</div>

									<button
										type="button"
										className="fcf7b-gspanel-add"
										onClick={ () => addRow( 'body', { key: '', source: [] } ) }
									>
										<i className="ri-add-line" /> { __( 'Add key', 'compactform' ) }
									</button>

									{ halfRows ? (
										<p className="fcf7b-gspanel-desc fcf7b-gspanel-warn">
											{ sprintf(
												/* translators: %d: number of incomplete rows. */
												_n( '%d row is missing a name or a value, and will be skipped.', '%d rows are missing a name or a value, and will be skipped.', halfRows, 'compactform' ),
												halfRows
											) }
										</p>
									) : null }

									<details className="fcf7b-gspanel-more">
										<summary>{ __( 'Can one key take several fields?', 'compactform' ) }</summary>
										<p className="fcf7b-gspanel-desc">
											{ createInterpolateElement(
												__( 'Yes — pick more than one and they collapse into that single key, the same thing a mail-template line like <example/> does. A field the visitor never saw is blank, so only the branch they filled in is sent; anything with content is joined with a comma.', 'compactform' ),
												{ example: <code>Doctor: [doctor-cardiology1][doctor-dermatology1]</code> }
											) }
										</p>
									</details>
								</div>
							) }
						</>
					) : null }

					{ 'advanced' === pane ? (
						<>
							<div className="fcf7b-gspanel-cols">
								<div className="fcf7b-gspanel-field">
									<label>{ __( 'Method', 'compactform' ) }</label>
									<Select value={ v.method } options={ METHODS } onChange={ ( m ) => set( { method: m } ) } />
								</div>
								<div className="fcf7b-gspanel-field">
									<label>{ __( 'Format', 'compactform' ) }</label>
									{ inQuery ? (
										<p className="fcf7b-gspanel-desc">
											{ sprintf(
												/* translators: %s: HTTP method, e.g. GET. */
												__( '%s carries everything in the address itself, so there is nothing to encode.', 'compactform' ),
												v.method
											) }
										</p>
									) : (
										<Select value={ v.format } options={ FORMATS } onChange={ ( f ) => set( { format: f } ) } />
									) }
								</div>
							</div>

							<p className="fcf7b-gspanel-desc">
								{ createInterpolateElement(
									__( '<post>POST</post> and <json>JSON</json> are what Pabbly, Zapier and Make all expect. Change them only if the service’s own instructions ask you to.', 'compactform' ),
									{ post: <strong />, json: <strong /> }
								) }
							</p>

							<h4 className="fcf7b-gspanel-subhead">{ __( 'Request headers', 'compactform' ) }</h4>

							<p className="fcf7b-gspanel-desc fcf7b-gspanel-lead">
								{ __( 'Only needed when the service asks you for an API key or a token.', 'compactform' ) }
							</p>

							<div className="fcf7b-gspanel-rows">
								{ rows( 'headers' ).length ? (
									<div className="fcf7b-gspanel-rowline fcf7b-gspanel-rowhead">
										<span className="fcf7b-gspanel-cell">{ __( 'Header name', 'compactform' ) }</span>
										<span className="fcf7b-gspanel-cell fcf7b-gspanel-cell--narrow">{ __( 'Value is', 'compactform' ) }</span>
										<span className="fcf7b-gspanel-cell">{ __( 'Value', 'compactform' ) }</span>
										<span className="fcf7b-gspanel-headspacer" />
									</div>
								) : null }

								{ rows( 'headers' ).map( ( row, i ) => (
									<div className="fcf7b-gspanel-rowline" key={ i }>
										<div className="fcf7b-gspanel-cell">
											<input
												type="text"
												value={ row.name || '' }
												placeholder="X-Api-Key"
												onChange={ ( e ) => setRow( 'headers', i, { name: headerName( e.target.value ) } ) }
											/>
										</div>
										<div className="fcf7b-gspanel-cell fcf7b-gspanel-cell--narrow">
											<Select
												value={ row.mode || 'value' }
												options={ HEADER_MODES }
												onChange={ ( m ) => setRow( 'headers', i, { mode: m, value: '' } ) }
											/>
										</div>
										<div className="fcf7b-gspanel-cell">
											{ 'field' === row.mode ? (
												<Select
													value={ row.value || '' }
													options={ sourceChoices }
													onChange={ ( s ) => setRow( 'headers', i, { value: s } ) }
													placeholder={ __( 'Pick a field…', 'compactform' ) }
												/>
											) : (
												<input
													type="text"
													value={ row.value || '' }
													placeholder={ __( 'Your API key', 'compactform' ) }
													onChange={ ( e ) => setRow( 'headers', i, { value: e.target.value } ) }
												/>
											) }
										</div>
										<button
											type="button"
											className="fcf7b-gspanel-del"
											title={ __( 'Remove this header', 'compactform' ) }
											onClick={ () => delRow( 'headers', i ) }
										>
											<i className="ri-delete-bin-line" />
										</button>
									</div>
								) ) }

								{ rows( 'headers' ).length ? null : (
									<p className="fcf7b-gspanel-none">
										{ sprintf(
											/* translators: %s: content type name, e.g. JSON. */
											__( 'None — only the %s content type is sent.', 'compactform' ),
											'json' === v.format ? __( 'JSON', 'compactform' ) : __( 'form', 'compactform' )
										) }
									</p>
								) }
							</div>

							<button
								type="button"
								className="fcf7b-gspanel-add"
								onClick={ () => addRow( 'headers', { name: '', mode: 'value', value: '' } ) }
							>
								<i className="ri-add-line" /> { __( 'Add header', 'compactform' ) }
							</button>

							<p className="fcf7b-gspanel-desc">
								{ createInterpolateElement(
									__( 'Your headers are applied last, so one named <ct/> overrides the format above.', 'compactform' ),
									{ ct: <code>Content-Type</code> }
								) }
							</p>

							<h4 className="fcf7b-gspanel-subhead">{ __( 'Delivery', 'compactform' ) }</h4>

							<div className="fcf7b-gspanel-switch">
								<span className="fcf7b-gspanel-switchlabel">
									{ __( 'Wait for a reply', 'compactform' ) }
									<em>{ __( 'Off — fire and forget: the visitor never waits, but a failure can’t be logged.', 'compactform' ) }</em>
								</span>
								<Switch checked={ v.blocking } onChange={ ( on ) => set( { blocking: on } ) } labelOn={ __( 'Yes', 'compactform' ) } labelOff={ __( 'No', 'compactform' ) } />
							</div>

							{ v.blocking ? (
								<div className="fcf7b-gspanel-row">
									<span className="fcf7b-gspanel-rowlabel">{ __( 'Give up after', 'compactform' ) }</span>
									<span className="fcf7b-gspanel-num">
										<input
											type="number"
											min={ 1 }
											max={ 30 }
											step={ 1 }
											value={ v.timeout }
											onChange={ ( e ) => set( { timeout: e.target.value === '' ? '' : parseInt( e.target.value, 10 ) } ) }
										/>
										<em>{ __( 'sec', 'compactform' ) }</em>
									</span>
								</div>
							) : null }

							<p className="fcf7b-gspanel-desc">
								{ __( 'A refused delivery is written to the WordPress debug log. The visitor never sees an error either way — their submission still goes through.', 'compactform' ) }
							</p>
						</>
					) : null }

					<div className="fcf7b-gspanel-pinned">
						<pre className="fcf7b-gspanel-preview">{ previewText() }</pre>

						<p className="fcf7b-gspanel-desc">
							{ createInterpolateElement(
								__( 'One submission, as this form will send it. Each <token/> is replaced with the visitor’s own answer. Save the form when you’re happy with it.', 'compactform' ),
								{ token: <code>{ '{value}' }</code> }
							) }
						</p>
					</div>
				</>
			) : null }
		</div>
	);
}
