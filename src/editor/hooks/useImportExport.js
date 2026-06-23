import { useRef } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { DATA } from '../shared';
import { applyMailPanel, readMailPanel } from '../utils/mailPanel';

const exportFileSlug = () => {
	const titleInput = document.getElementById( 'title' ) || document.querySelector( 'input[name="post_title"]' );
	const slug = ( titleInput ? titleInput.value : '' )
		.trim()
		.toLowerCase()
		.replace( /[^a-z0-9]+/g, '-' )
		.replace( /^-+|-+$/g, '' );
	return slug || DATA.formId || 'export';
};

export default function useImportExport( schema, setSchema, selectField ) {
	const importInput = useRef( null );

	const exportForm = () => {
		const payload = { ...schema };
		const mail = {};

		[ 'mail', 'mail_2' ].forEach( ( box ) => {
			const data = readMailPanel( box );
			if ( data ) {
				mail[ box ] = data;
			}
		} );
		if ( Object.keys( mail ).length ) {
			payload.mail = mail;
		}

		const blob = new window.Blob( [ JSON.stringify( payload, null, 2 ) ], { type: 'application/json' } );
		const url = window.URL.createObjectURL( blob );
		const a = document.createElement( 'a' );
		a.href = url;
		a.download = `compactform-${ exportFileSlug() }.json`;
		document.body.appendChild( a );
		a.click();
		a.remove();
		window.URL.revokeObjectURL( url );
	};

	// Also the template library's apply path — everything after "we have a parsed export"
	// is identical, and a template IS an export (same `exportForm` payload shape).
	const importSchema = ( parsed ) => {
		if ( ! parsed || ! Array.isArray( parsed.fields ) ) {
			window.alert( __( 'Could not import: not a CompactForm export.', 'compactform' ) );
			return;
		}
		const hasMail = parsed.mail && 'object' === typeof parsed.mail;

		const confirmMessage = hasMail
			? sprintf(
				/* translators: %d: number of fields in the imported file. */
				_n( 'Import %d field and mail settings? This replaces the current form and saves it immediately.', 'Import %d fields and mail settings? This replaces the current form and saves it immediately.', parsed.fields.length, 'compactform' ),
				parsed.fields.length
			)
			: sprintf(
				/* translators: %d: number of fields in the imported file. */
				_n( 'Import %d field? This replaces the current form and saves it immediately.', 'Import %d fields? This replaces the current form and saves it immediately.', parsed.fields.length, 'compactform' ),
				parsed.fields.length
			);

		if ( ! window.confirm( confirmMessage ) ) { return; }
		selectField( null );

		const nextSchema = {
			fields: parsed.fields,
			breakpoints: parsed.breakpoints || {},
			integrations: parsed.integrations || schema.integrations,
			configuration: parsed.configuration || schema.configuration,
		};
		setSchema( nextSchema );

		if ( hasMail ) {
			Object.keys( parsed.mail ).forEach( ( box ) => applyMailPanel( parsed.mail[ box ], box ) );
		}

		// Quick save only posts `schema`, and its response overwrites the Mail-tab DOM
		// from whatever's in the DB — it would clobber the just-imported mail values
		// before they're ever persisted. A classic CF7 submit posts both the schema
		// (via the hidden input, set directly here since the React state update above
		// hasn't re-rendered yet) and the real Mail-tab inputs `applyMailPanel` just
		// wrote to, in one request. `form.submit()` (not `requestSubmit()`) bypasses the
		// 'submit' listener in `useCf7Persistence.js` entirely, so nothing re-reads the
		// stale pre-import `schema` closure and stomps the hidden input we just set.
		const schemaInput = document.getElementById( 'fcf7-builder-schema-input' );
		if ( schemaInput ) { schemaInput.value = JSON.stringify( nextSchema ); }
		const form = document.getElementById( 'wpcf7-admin-form-element' );
		// Deferred one tick: applyMailPanel()'s change events (CodeMirror's
		// setValue/save included) need to run and settle before the DOM is
		// serialized for submit.
		window.setTimeout( () => {
			if ( form ) { form.submit(); }
		}, 0 );
	};

	const onImportFile = ( e ) => {
		const file = e.target.files && e.target.files[ 0 ];
		e.target.value = '';
		if ( ! file ) { return; }
		const reader = new window.FileReader();
		reader.onload = () => {
			let parsed;
			try {
				parsed = JSON.parse( reader.result );
			} catch ( err ) {
				window.alert( __( 'Could not import: that file is not valid JSON.', 'compactform' ) );
				return;
			}
			importSchema( parsed );
		};
		reader.readAsText( file );
	};

	return { exportForm, onImportFile, importInput, importSchema };
}
