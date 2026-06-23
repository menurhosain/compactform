import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { DATA } from '../shared';
import { hooks } from '../field-preview-registry';
import { applyMailPanel } from '../utils/mailPanel';

export default function useCf7Persistence( schema ) {
	const [ render, setRender ] = useState( { markup: '', loading: false } );
	const [ saveState, setSaveState ] = useState( 'idle' );
	const [ saveProgress, setSaveProgress ] = useState( 0 );
	const [ saveError, setSaveError ] = useState( '' );
	const [ dirty, setDirty ] = useState( false );
	const savedSnap = useRef( JSON.stringify( schema ) );
	const createdEditUrl = useRef( null );
	const submitting = useRef( false );

	// Server compile, on demand
	const compileRender = () => {
		const body = new window.FormData();
		body.append( 'action', 'fcf7_builder_render' );
		body.append( 'nonce', DATA.nonce );
		body.append( 'schema', JSON.stringify( schema ) );
		return window.fetch( DATA.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' } )
			.then( ( r ) => r.json() )
			.then( ( res ) => {
				const markup = res && res.success ? ( res.data.markup || '' ) : '';
				setRender( { markup, loading: false } );
				return markup;
			} );
	};

	// Quick AJAX save
	const ensureFormId = () => {
		if (  Number(DATA.formId) ) {
			return Promise.resolve( DATA.formId );
		}
		const titleInput = document.getElementById( 'title' ) || document.querySelector( 'input[name="post_title"]' );
		const title = titleInput ? titleInput.value.trim() : '';
		if ( ! title ) {
			window.alert( __( 'Please give this form a title first.', 'compactform' ) );
			return Promise.resolve( null );
		}
		const body = new window.FormData();
		body.append( 'action', 'fcf7_builder_create_form' );
		body.append( 'nonce', DATA.saveNonce || '' );
		body.append( 'title', title );
		return window.fetch( DATA.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' } )
			.then( ( r ) => r.json() )
			.then( ( res ) => {
				if ( res && res.success && res.data && res.data.formId ) {
					DATA.formId = res.data.formId;
					createdEditUrl.current = res.data.editUrl || null;
					return DATA.formId;
				}
				window.alert( ( res?.data?.message ) || __( 'Could not create the form.', 'compactform' ) );
				return null;
			} )
			.catch( ( err ) => {
				window.alert( String( err ) );
				return null;
			} );
	};

	const runQuickSave = ( formId, markup ) => {
		setSaveState( 'saving' );
		const snap = JSON.stringify( schema );
		const input = document.getElementById( 'fcf7-builder-schema-input' );
		if ( input ) { input.value = snap; }
		const body = new window.FormData();
		body.append( 'action', 'fcf7_builder_save' );
		body.append( 'nonce', DATA.saveNonce || '' );
		body.append( 'form_id', formId );
		body.append( 'schema', snap );
		window.fetch( DATA.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' } )
			.then( ( r ) => r.text().then( ( t ) => ( { status: r.status, text: t } ) ) )
			.then( ( r ) => {
				setSaveProgress( 100 );
				let res = null;
				try { res = JSON.parse( r.text ); } catch ( e ) { /* skip */ }
				if ( res && res.success ) {
					savedSnap.current = snap;
					hooks.doAction( 'fcf7b.afterSave', res.data );
					if ( res.data && res.data.mail ) { applyMailPanel( res.data.mail ); }
					if ( res.data && res.data.mailDefault && window.FCF7DefaultMail && window.FCF7DefaultMail.boxes ) {
						[ 'mail', 'mail_2' ].forEach( ( box ) => {
							if ( res.data.mailDefault[ box ] && window.FCF7DefaultMail.boxes[ box ] ) {
								window.FCF7DefaultMail.boxes[ box ].fields = res.data.mailDefault[ box ];
							}
						} );
					}
					const ta = document.querySelector( 'textarea[name="wpcf7-form"], textarea#wpcf7-form' );
					if ( ta && markup && ta.value !== markup ) {
						ta.value = markup;
						ta.defaultValue = markup;
						ta.dispatchEvent( new Event( 'change', { bubbles: true } ) );
					}
					setDirty( false );
					setSaveState( 'saved' );
					if ( createdEditUrl.current ) {
						submitting.current = true;
						document.getElementById( 'wpcf7-admin-form-element' )?.remove();
						window.location.href = createdEditUrl.current;
						return;
					}
				} else {
					const msg = ( res?.data?.message ) ? res.data.message : `HTTP ${ r.status } — ${ ( r.text || 'no response' ).slice( 0, 200 ) }`;
					window.console?.error( 'CompactForm Quick save failed:', msg );
					setSaveError( msg );
					setSaveState( 'error' );
				}
			} )
			.catch( ( err ) => {
				window.console?.error( 'CompactForm Quick save request failed:', err );
				setSaveProgress( 100 );
				setSaveError( String( err ) );
				setSaveState( 'error' );
			} )
			.then( () => window.setTimeout( () => {
				setSaveState( 'idle' );
				setSaveProgress( 0 );
			}, 3000 ) );
	};

	const quickSave = () => {
		if ( saveState === 'saving' || ! dirty ) { return; }
		setSaveState( 'saving' );
		setSaveProgress( 12 );
		ensureFormId().then( ( formId ) => {
			if ( ! formId ) {
				setSaveState( 'idle' );
				setSaveProgress( 0 );
				return;
			}
			setSaveProgress( 30 );
			compileRender().then( ( markup ) => {
				setSaveProgress( 60 );
				runQuickSave( formId, markup );
			} );
		} );
	};

	// Initial compile
	useEffect( () => {
		compileRender().then( ( markup ) => {
			const ta = document.querySelector( 'textarea[name="wpcf7-form"], textarea#wpcf7-form' );
			if ( ta && markup && ta.value !== markup ) {
				ta.value = markup;
				ta.defaultValue = markup;
				ta.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			}
			const input = document.getElementById( 'fcf7-builder-schema-input' );
			if ( input ) { input.value = JSON.stringify( schema ); }
		} );
	}, [] );

	// CF7 dirty-flag mirror
	useEffect( () => {
		const root = document.getElementById( 'fcf7-builder-root' );
		if ( ! root ) { return; }
		root.querySelectorAll( 'input, textarea, select' ).forEach( ( elm ) => {
			if ( elm.type === 'checkbox' || elm.type === 'radio' ) { elm.defaultChecked = elm.checked; }
			else if ( elm.tagName === 'SELECT' ) { [ ...elm.options ].forEach( ( o ) => { o.defaultSelected = o.selected; } ); }
			else { elm.defaultValue = elm.value; }
		} );
	} );

	// Unsaved-changes tracking
	useEffect( () => {
		setDirty( JSON.stringify( schema ) !== savedSnap.current );
	}, [ JSON.stringify( schema ) ] );

	useEffect( () => {
		if ( ! dirty ) { return; }
		const warn = ( e ) => {
			if ( submitting.current ) { return; }
			e.preventDefault();
			e.returnValue = '';
			return '';
		};
		window.addEventListener( 'beforeunload', warn );
		return () => window.removeEventListener( 'beforeunload', warn );
	}, [ dirty ] );

	// CF7 form submit guard
	useEffect( () => {
		const form = document.getElementById( 'wpcf7-admin-form-element' );
		if ( ! form ) { return; }
		const onSubmit = ( e ) => {
			const from = e.submitter || document.activeElement;
			if ( from && from.closest && from.closest( '.fcf7b-app' ) ) {
				e.preventDefault();
				return;
			}
			const input = document.getElementById( 'fcf7-builder-schema-input' );
			if ( input ) { input.value = JSON.stringify( schema ); }
			submitting.current = true;
		};
		form.addEventListener( 'submit', onSubmit );
		return () => form.removeEventListener( 'submit', onSubmit );
	}, [ schema ] );

	return { saveState, saveProgress, saveError, dirty, quickSave, render };
}
