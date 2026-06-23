const MAIL_FIELD_IDS = {
	mail: {
		recipient: 'wpcf7-mail-recipient',
		sender: 'wpcf7-mail-sender',
		subject: 'wpcf7-mail-subject',
		additional_headers: 'wpcf7-mail-additional-headers',
		body: 'wpcf7-mail-body',
		attachments: 'wpcf7-mail-attachments',
	},
	mail_2: {
		recipient: 'wpcf7-mail-2-recipient',
		sender: 'wpcf7-mail-2-sender',
		subject: 'wpcf7-mail-2-subject',
		additional_headers: 'wpcf7-mail-2-additional-headers',
		body: 'wpcf7-mail-2-body',
		attachments: 'wpcf7-mail-2-attachments',
	},
};

const MAIL_CHECKBOX_IDS = {
	mail: {
		use_html: 'wpcf7-mail-use-html',
		exclude_blank: 'wpcf7-mail-exclude-blank',
	},
	mail_2: {
		active: 'wpcf7-mail-2-active',
		use_html: 'wpcf7-mail-2-use-html',
		exclude_blank: 'wpcf7-mail-2-exclude-blank',
	},
};

export function applyMailPanel( mail, box = 'mail' ) {
	const fieldIds = MAIL_FIELD_IDS[ box ];
	if ( ! fieldIds || ! mail ) { return; }

	Object.keys( fieldIds ).forEach( ( key ) => {
		const value = mail[ key ];
		if ( 'string' !== typeof value ) { return; }

		// The body field is a WP code-editor: a hidden textarea shadowed by a CodeMirror
		// instance that owns the visible content. CodeMirror re-writes the textarea from
		// its own document on every keystroke (and again right before submit), so setting
		// `el.value` here directly gets silently overwritten — only CodeMirror's own
		// `setValue()` sticks. That instance lives in the separate default-mail-admin.js
		// bundle, bridged here via `window.FCF7DefaultMail.setBodyValue`.
		if ( 'body' === key && window.FCF7DefaultMail && 'function' === typeof window.FCF7DefaultMail.setBodyValue ) {
			window.FCF7DefaultMail.setBodyValue( box, value );
			return;
		}

		const el = document.getElementById( fieldIds[ key ] );
		if ( ! el || el.value === value ) { return; }
		el.value = value;
		el.defaultValue = value;
		el.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	} );

	Object.keys( MAIL_CHECKBOX_IDS[ box ] || {} ).forEach( ( key ) => {
		const el = document.getElementById( MAIL_CHECKBOX_IDS[ box ][ key ] );
		if ( ! el || ! ( key in mail ) ) { return; }
		const checked = !! mail[ key ];
		if ( el.checked === checked ) { return; }
		el.checked = checked;
		el.defaultChecked = checked;
		el.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	} );
}

export function readMailPanel( box = 'mail' ) {
	const fieldIds = MAIL_FIELD_IDS[ box ];
	if ( ! fieldIds ) { return null; }

	const data = {};
	let found = false;

	Object.keys( fieldIds ).forEach( ( key ) => {
		const el = document.getElementById( fieldIds[ key ] );
		if ( ! el ) { return; }
		found = true;
		data[ key ] = el.value;
	} );

	Object.keys( MAIL_CHECKBOX_IDS[ box ] || {} ).forEach( ( key ) => {
		const el = document.getElementById( MAIL_CHECKBOX_IDS[ box ][ key ] );
		if ( ! el ) { return; }
		found = true;
		data[ key ] = el.checked;
	} );

	return found ? data : null;
}
