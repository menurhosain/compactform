import { createInterpolateElement, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { DATA } from '../shared';
import { hooks } from '../field-preview-registry';
import Switch from './Switch';

const MAX_LIST_BYTES = 1048576;

const DEFAULT_BOT_MESSAGE = __( 'Your submission looks automated and was not sent. Please try again.', 'compactform' );
const DEFAULT_BANNED_MESSAGE = __( 'Your message contains words that are not allowed.', 'compactform' );

const DEFAULT_VALUE = {
	honeypotEnabled: true,
	minTimeEnabled: false,
	minTimeSeconds: 3,
	botMessage: '',
	bannedEnabled: false,
	bannedWords: '',
	bannedMessage: '',
};

const PANES = [
	{ id: 'traps', label: __( 'Bot traps', 'compactform' ) },
	{ id: 'words', label: __( 'Words', 'compactform' ) },
];

export default function SpamProtectionTab( { value = DEFAULT_VALUE, onChange } ) {
	const v = ( value && typeof value === 'object' ) ? { ...DEFAULT_VALUE, ...value } : { ...DEFAULT_VALUE };
	const set = ( patch ) => onChange( { ...v, ...patch } );

	const [ pane, setPane ] = useState( 'traps' );

	const extraPanes = hooks.applyFilters( 'fcf7b.spamProtectionPanes', [], { value: v, set } );
	const allPanes = [ ...PANES, ...extraPanes ];
	const activeExtraPane = extraPanes.find( ( p ) => p.id === pane );
	const [ url, setUrl ] = useState( '' );
	const [ importing, setImporting ] = useState( false );
	const [ report, setReport ] = useState( null );
	const fileRef = useRef( null );

	const banning = !! v.bannedEnabled;
	const hasWords = banning && !! ( v.bannedWords || '' ).trim();

	const importList = ( body ) => {
		body.append( 'action', 'fcf7_sp_import_wordlist' );
		body.append( 'nonce', DATA.nonce || '' );
		body.append( 'existing', v.bannedWords || '' );

		setImporting( true );
		setReport( null );

		window
			.fetch( DATA.ajaxUrl, { method: 'POST', credentials: 'same-origin', body } )
			.then( ( r ) => r.json() )
			.then( ( payload ) => {
				if ( payload && payload.success ) {
					set( { bannedWords: payload.data.words } );
					setReport( {
						state: 'ok',
						message: payload.data.added
							? sprintf(
								/* translators: 1: number of words added, 2: total number of words. */
								__( 'Added %1$d — %2$d words in total.', 'compactform' ),
								payload.data.added,
								payload.data.total
							)
							: sprintf(
								/* translators: %d: total number of words already listed. */
								__( 'Nothing new — all %d were already listed.', 'compactform' ),
								payload.data.total
							),
					} );
				} else {
					setReport( { state: 'fail', message: payload?.data?.message || __( 'Import failed.', 'compactform' ) } );
				}
			} )
			.catch( () => setReport( { state: 'fail', message: __( 'Import failed.', 'compactform' ) } ) )
			.finally( () => setImporting( false ) );
	};

	const importFile = ( file ) => {
		if ( ! file ) {
			return;
		}

		if ( file.size > MAX_LIST_BYTES ) {
			setReport( { state: 'fail', message: __( 'That file is larger than 1 MB.', 'compactform' ) } );
			return;
		}

		const reader = new window.FileReader();
		reader.onload = () => {
			const body = new window.FormData();
			body.append( 'text', String( reader.result || '' ) );
			importList( body );
		};
		reader.onerror = () => setReport( { state: 'fail', message: __( 'Could not read that file.', 'compactform' ) } );
		reader.readAsText( file );
	};

	const importUrl = () => {
		const body = new window.FormData();
		body.append( 'url', url.trim() );
		importList( body );
	};

	const message = ( id, key, placeholder ) => (
		<div className="fcf7b-gspanel-field">
			<label htmlFor={ id }>{ __( 'Error message', 'compactform' ) }</label>
			<input
				type="text"
				id={ id }
				value={ v[ key ] || '' }
				placeholder={ placeholder }
				onChange={ ( e ) => set( { [ key ]: e.target.value } ) }
			/>
		</div>
	);

	const seconds = ( label, key, max ) => (
		<div className="fcf7b-gspanel-row">
			<span className="fcf7b-gspanel-rowlabel">{ label }</span>
			<span className="fcf7b-gspanel-num">
				<input
					type="number"
					min={ 1 }
					max={ max }
					step={ 1 }
					value={ v[ key ] }
					onChange={ ( e ) => set( {
						[ key ]: e.target.value === '' ? '' : parseInt( e.target.value, 10 ),
					} ) }
				/>
				<em>{ __( 'seconds', 'compactform' ) }</em>
			</span>
		</div>
	);

	return (
		<div className="fcf7b-gspanel">
			<h3 className="fcf7b-gspanel-head">{ __( 'Spam Protection', 'compactform' ) }</h3>

			<p className="fcf7b-gspanel-desc fcf7b-gspanel-intro">
				{ __( 'Form-level rules, all checked on the server. The arithmetic CAPTCHA is a field — drop it on the canvas and use its own panel.', 'compactform' ) }
			</p>

			<div className="fcf7b-gspanel-tabs">
				{ allPanes.map( ( p ) => (
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

			{ 'traps' === pane ? (
				<>
					<div className="fcf7b-gspanel-switch">
						<span className="fcf7b-gspanel-switchlabel">
							{ __( 'Honeypot', 'compactform' ) }
							<em>{ __( 'An invisible field bots fill in and people never see.', 'compactform' ) }</em>
						</span>
						<Switch checked={ v.honeypotEnabled } onChange={ ( on ) => set( { honeypotEnabled: on } ) } labelOn={ __( 'Yes', 'compactform' ) } labelOff={ __( 'No', 'compactform' ) } />
					</div>

					<div className="fcf7b-gspanel-switch">
						<span className="fcf7b-gspanel-switchlabel">
							{ __( 'Minimum fill time', 'compactform' ) }
							<em>{ __( 'Refuse a form that comes back faster than a person could type it.', 'compactform' ) }</em>
						</span>
						<Switch checked={ v.minTimeEnabled } onChange={ ( on ) => set( { minTimeEnabled: on } ) } labelOn={ __( 'Yes', 'compactform' ) } labelOff={ __( 'No', 'compactform' ) } />
					</div>

					{ v.minTimeEnabled ? seconds( __( 'Minimum', 'compactform' ), 'minTimeSeconds', 300 ) : null }

					{ v.honeypotEnabled || v.minTimeEnabled
						? message( 'fcf7-sp-bot-msg', 'botMessage', DEFAULT_BOT_MESSAGE )
						: null }
				</>
			) : null }

			{ 'words' === pane ? (
				<div className="fcf7b-gspanel-switch">
					<span className="fcf7b-gspanel-switchlabel">
						{ __( 'Banned words', 'compactform' ) }
						<em>{ __( 'Refuse a submission whose answers contain a listed word.', 'compactform' ) }</em>
					</span>
					<Switch checked={ banning } onChange={ ( on ) => set( { bannedEnabled: on } ) } labelOn={ __( 'Yes', 'compactform' ) } labelOff={ __( 'No', 'compactform' ) } />
				</div>
			) : null }

			{ 'words' === pane && banning ? (
				<>
					<div className="fcf7b-gspanel-field">
						<label htmlFor="fcf7-sp-banned">{ __( 'Word list', 'compactform' ) }</label>
						<textarea
							id="fcf7-sp-banned"
							rows={ 5 }
							value={ v.bannedWords || '' }
							placeholder={ __( 'casino, cheap loans, crypto giveaway', 'compactform' ) }
							onChange={ ( e ) => set( { bannedWords: e.target.value } ) }
						/>
						<p className="fcf7b-gspanel-desc">
							{ createInterpolateElement(
								__( 'Comma separated, checked against every answer. A single word matches whole words only — <word/> will not refuse “class”. A phrase may contain spaces, so <phrase/> is one entry. Empty switches it off.', 'compactform' ),
								{ word: <code>ass</code>, phrase: <code>cheap loans</code> }
							) }
						</p>
					</div>

					<h4 className="fcf7b-gspanel-subhead">{ __( 'Import into the list', 'compactform' ) }</h4>

					<p className="fcf7b-gspanel-desc fcf7b-gspanel-intro">
						{ createInterpolateElement(
							__( 'Adds to what is already above rather than replacing it, so you can start from a ready-made list and keep writing your own. Duplicates are dropped, lines starting with <hash/> or <slashes/> are ignored, and the result is written back as one comma-separated list you can edit or prune afterwards.', 'compactform' ),
							{ hash: <code>#</code>, slashes: <code>//</code> }
						) }
					</p>

					<div className="fcf7b-gspanel-field">
						<label htmlFor="fcf7-sp-file">{ __( 'From a file', 'compactform' ) }</label>
						<input
							type="file"
							id="fcf7-sp-file"
							ref={ fileRef }
							accept=".txt,.csv,text/plain,text/csv"
							disabled={ importing }
							onChange={ ( e ) => {
								importFile( e.target.files && e.target.files[ 0 ] );
								// Cleared so picking the same file twice fires onChange again.
								e.target.value = '';
							} }
						/>
						<p className="fcf7b-gspanel-desc">
							{ __( 'A plain .txt or .csv, up to 1 MB. Read in your browser — nothing is uploaded or stored on the server.', 'compactform' ) }
						</p>
					</div>

					<div className="fcf7b-gspanel-field">
						<label htmlFor="fcf7-sp-url">{ __( 'From a URL', 'compactform' ) }</label>
						<input
							type="text"
							id="fcf7-sp-url"
							value={ url }
							placeholder="https://example.com/badwords.txt"
							onChange={ ( e ) => setUrl( e.target.value ) }
						/>
						<p className="fcf7b-gspanel-desc">
							{ __( 'Fetched once, now. The address is not saved — the words are.', 'compactform' ) }
						</p>
					</div>

					<div className="fcf7b-gspanel-testrow">
						<button
							type="button"
							className="fcf7b-gspanel-add"
							disabled={ importing || ! url.trim() }
							onClick={ importUrl }
						>
							<i className="ri-download-line" /> { importing ? __( 'Importing…', 'compactform' ) : __( 'Import from URL', 'compactform' ) }
						</button>
						{ report ? (
							<span className={ `fcf7b-gspanel-testmsg is-${ report.state }` }>{ report.message }</span>
						) : null }
					</div>

					{ hasWords
						? message( 'fcf7-sp-banned-msg', 'bannedMessage', DEFAULT_BANNED_MESSAGE )
						: null }
				</>
			) : null }

			{ activeExtraPane ? activeExtraPane.render( { value: v, set } ) : null }
		</div>
	);
}
