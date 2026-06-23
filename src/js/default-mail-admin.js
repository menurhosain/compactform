
( () => {
	"use strict";

	if ( "undefined" === typeof FCF7DefaultMail ) {
		return;
	}

	const FIELD_IDS = {
		mail: {
			recipient: "wpcf7-mail-recipient",
			sender: "wpcf7-mail-sender",
			subject: "wpcf7-mail-subject",
			additional_headers: "wpcf7-mail-additional-headers",
			body: "wpcf7-mail-body",
			attachments: "wpcf7-mail-attachments",
		},
		mail_2: {
			recipient: "wpcf7-mail-2-recipient",
			sender: "wpcf7-mail-2-sender",
			subject: "wpcf7-mail-2-subject",
			additional_headers: "wpcf7-mail-2-additional-headers",
			body: "wpcf7-mail-2-body",
			attachments: "wpcf7-mail-2-attachments",
		},
	};

	const editors = {};


	const autofill = ( input, value, box ) => {
		const cm = editors[ box ];

		if ( cm && input.id.endsWith( "-body" ) ) {
			cm.setValue( value );
			cm.save();
		} else {
			input.value = value;
		}

		input.dispatchEvent( new Event( "input", { bubbles: true } ) );
		input.dispatchEvent( new Event( "change", { bubbles: true } ) );
	};

	const initEditor = ( box ) => {
		const settings = FCF7DefaultMail.editor;
		const textarea = document.getElementById( FIELD_IDS[ box ].body );

		if ( ! settings || ! textarea || ! window.wp || ! window.wp.codeEditor ) {
			return;
		}

		const instance = window.wp.codeEditor.initialize( textarea, settings );
		if ( ! instance || ! instance.codemirror ) {
			return;
		}

		const cm = instance.codemirror;
		editors[ box ] = cm;
		cm.getWrapperElement().classList.add( "fcf7-mail-code" );

		cm.getWrapperElement().appendChild( buildFullscreenToggle( cm ) );
		cm.getWrapperElement().appendChild( buildPreview( cm, box ) );

		cm.on( "change", () => cm.save() );
	};

	const buildFullscreenToggle = ( cm ) => {
		const i18n = FCF7DefaultMail.i18n || {};
		const wrapper = cm.getWrapperElement();

		const button = document.createElement( "button" );
		button.type = "button";
		button.className = "fcf7-mail-fullscreen";

		const setState = ( on ) => {
			const label = on
				? ( i18n.exitFullscreen || "Exit fullscreen" )
				: ( i18n.fullscreen || "Fullscreen" );

			wrapper.classList.toggle( "fcf7-mail-code-full", on );
			document.body.classList.toggle( "fcf7-mail-code-lock", on );
			button.innerHTML = on ? '<i class="ri-collapse-diagonal-line"></i>' : '<i class="ri-expand-diagonal-line"></i>';
			button.title = label;
			button.setAttribute( "aria-label", label );
			cm.refresh();
			if ( on ) {
				cm.focus();
			}
		};

		button.addEventListener( "click", () => {
			setState( ! wrapper.classList.contains( "fcf7-mail-code-full" ) );
		} );

		document.addEventListener( "keydown", ( e ) => {
			if ( "Escape" !== e.key || document.body.classList.contains( "fcf7-mail-previewing" ) ) {
				return;
			}
			if ( wrapper.classList.contains( "fcf7-mail-code-full" ) ) {
				setState( false );
			}
		} );

		setState( false );

		return button;
	};

	const buildPreview = ( cm, box ) => {
		const i18n = FCF7DefaultMail.i18n || {};

		const modal = document.createElement( "div" );
		modal.className = "fcf7-mail-modal";
		modal.hidden = true;
		modal.innerHTML =
			'<div class="fcf7-mail-modal__box" role="dialog" aria-modal="true">'
			+ '<div class="fcf7-mail-modal__bar"><span class="fcf7-mail-modal__title"></span>'
			+ '<button type="button" class="fcf7-mail-modal__close">&times;</button></div>'
			+ "</div>";
		document.body.appendChild( modal );

		const box_el = modal.querySelector( ".fcf7-mail-modal__box" );
		let frame = null;

		const button = document.createElement( "button" );
		button.type = "button";
		button.className = "fcf7-mail-preview-toggle";

		const isHtml = () => {
			const box_id = FIELD_IDS[ box ].body.replace( /-body$/, "-use-html" );
			const checkbox = document.getElementById( box_id );
			return ! checkbox || checkbox.checked;
		};

		const escapeHtml = ( s ) => s.replace( /&/g, "&amp;" ).replace( /</g, "&lt;" ).replace( />/g, "&gt;" );

		const doc = () => {
			const value = cm.getValue();
			return isHtml()
				? value
				: '<pre style="font:13px/1.6 ui-monospace,Menlo,Consolas,monospace;white-space:pre-wrap;padding:24px;">'
					+ escapeHtml( value ) + "</pre>";
		};

		const render = () => {
			const next = document.createElement( "iframe" );
			next.className = "fcf7-mail-modal__frame";
			next.setAttribute( "sandbox", "" );
			next.srcdoc = doc();

			if ( frame ) {
				frame.remove();
			}
			frame = next;
			box_el.appendChild( frame );
		};

		let timer = null;
		const isOpen = () => ! modal.hidden;

		const scheduleRender = () => {
			if ( ! isOpen() ) {
				return;
			}
			window.clearTimeout( timer );
			timer = window.setTimeout( render, 300 );
		};

		cm.on( "change", scheduleRender );

		const setState = ( on ) => {
			const label = on ? ( i18n.closePreview || "Close preview" ) : ( i18n.preview || "Preview" );

			modal.hidden = ! on;

			if ( on ) {
				render();
			} else if ( frame ) {
				frame.remove();
				frame = null;
			}
			document.body.classList.toggle( "fcf7-mail-previewing", on );
			button.title = label;
			button.setAttribute( "aria-label", label );
		};

		button.innerHTML = '<i class="ri-eye-line"></i>';
		modal.querySelector( ".fcf7-mail-modal__title" ).textContent = i18n.preview || "Preview";
		modal.querySelector( ".fcf7-mail-modal__close" ).addEventListener( "click", () => setState( false ) );

		modal.addEventListener( "click", ( e ) => {
			if ( e.target === modal ) {
				setState( false );
			}
		} );

		button.addEventListener( "click", () => setState( ! isOpen() ) );

		document.addEventListener( "keydown", ( e ) => {
			if ( "Escape" === e.key && isOpen() ) {
				setState( false );
			}
		} );

		setState( false );

		return button;
	};

	const refreshEditors = () => {
		Object.keys( editors ).forEach( ( box ) => {
			window.requestAnimationFrame( () => editors[ box ].refresh() );
		} );
	};

	const addLink = ( box, key ) => {
		const input = document.getElementById( FIELD_IDS[ box ][ key ] );
		const data = ( FCF7DefaultMail.boxes || {} )[ box ];
		if ( ! input || ! data ) {
			return;
		}

		const row = document.createElement( "div" );
		row.style.display = "flex";
		row.style.alignItems = "center";
		row.style.gap = "12px";
		row.style.marginTop = "4px";

		const link = document.createElement( "button" );
		link.type = "button";
		link.className = "button-link fcf7-mail-autofill";
		link.textContent = FCF7DefaultMail.label;
		link.style.fontSize = "12px";

		link.addEventListener( "click", () => {
			const value = ( data.fields || {} )[ key ];
			if ( value ) {
				autofill( input, value, box );
			}
		} );

		row.appendChild( link );

		if ( "body" === key && data.template ) {
			row.appendChild( buildTemplateSelect( box, data.template ) );
		}


		input.insertAdjacentElement( "afterend", row );
	};

	const BOX_TEMPLATE_KEYS = { mail: "template", mail_2: "template2" };

	const patchSchemaInput = ( box, templateId ) => {
		const key = BOX_TEMPLATE_KEYS[ box ];
		const input = document.getElementById( "fcf7-builder-schema-input" );
		if ( ! key || ! input ) {
			return;
		}
		let schema;
		try {
			schema = JSON.parse( input.value );
		} catch ( e ) {
			return;
		}
		if ( ! schema || "object" !== typeof schema ) {
			return;
		}
		schema.configuration = schema.configuration || {};
		schema.configuration.defaultMail = schema.configuration.defaultMail || {};
		schema.configuration.defaultMail[ key ] = templateId;
		input.value = JSON.stringify( schema );
	};

	const buildTemplateSelect = ( box, template ) => {
		const wrap = document.createElement( "span" );
		wrap.style.fontSize = "12px";

		const label = document.createElement( "label" );
		label.textContent = template.label + ": ";
		label.style.marginRight = "4px";

		const select = document.createElement( "select" );
		select.style.fontSize = "12px";
		( template.options || [] ).forEach( ( opt ) => {
			const option = document.createElement( "option" );
			option.value = opt.id;
			option.textContent = opt.title;
			option.selected = opt.id === template.selected;
			option.defaultSelected = option.selected;
			select.appendChild( option );
		} );

		const status = document.createElement( "span" );
		status.style.marginLeft = "6px";
		status.style.color = "#2271b1";

		const spinner = document.createElement( "span" );
		spinner.className = "spinner";
		spinner.style.float = "none";
		spinner.style.marginLeft = "6px";

		select.addEventListener( "change", () => {
			const templateId = select.value;
			status.textContent = "";
			select.disabled = true;
			spinner.classList.add( "is-active" );

			const body = new FormData();
			body.append( "action", "fcf7_default_mail_set_template" );
			body.append( "nonce", FCF7DefaultMail.nonce );
			body.append( "form_id", FCF7DefaultMail.formId );
			body.append( "box", box );
			body.append( "template", templateId );

			fetch( FCF7DefaultMail.ajaxUrl, { method: "POST", credentials: "same-origin", body } )
				.then( ( r ) => r.json() )
				.then( ( res ) => {
					if ( res && res.success ) {
						template.selected = templateId;
						Object.assign( FCF7DefaultMail.boxes[ box ].fields, res.data.fields );
						patchSchemaInput( box, templateId );
						status.textContent = "✓";
						[ ...select.options ].forEach( ( o ) => { o.defaultSelected = o.selected; } );
					} else {
						status.textContent = ( res && res.data && res.data.message ) || "Error";
						status.style.color = "#d63638";
					}
				} )
				.catch( () => {
					status.textContent = "Error";
					status.style.color = "#d63638";
				} )
				.finally( () => {
					select.disabled = false;
					spinner.classList.remove( "is-active" );
				} );
		} );

		wrap.appendChild( label );
		wrap.appendChild( select );
		wrap.appendChild( spinner );
		wrap.appendChild( status );
		return wrap;
	};

	FCF7DefaultMail.setBodyValue = ( box, value ) => {
		const ids = FIELD_IDS[ box ];
		const input = ids && document.getElementById( ids.body );
		if ( input ) {
			autofill( input, value, box );
		}
	};

	document.addEventListener( "DOMContentLoaded", () => {
		Object.keys( FIELD_IDS ).forEach( ( box ) => {
			Object.keys( FIELD_IDS[ box ] ).forEach( ( key ) => addLink( box, key ) );
			initEditor( box );
		} );

		document.addEventListener( "click", refreshEditors );
		window.addEventListener( "load", refreshEditors );
	} );
} )();
