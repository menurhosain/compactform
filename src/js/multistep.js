
( () => {
	'use strict';

	const panels = ( root ) => [ ...root.querySelectorAll( '.fcf7b-ms-panel' ) ];

	const navItems = ( root ) => [
		...root.querySelectorAll( '.fcf7b-ms-tab, .fcf7b-ms-step' ),
	];

	const currentIndex = ( root ) => parseInt( root.getAttribute( 'data-current-step' ) ?? '0', 10 ) || 0;

	const formIdOf = ( root ) => root.closest( '[data-wpcf7-id]' )?.getAttribute( 'data-wpcf7-id' ) ?? '';

	const clearErrors = ( panel ) => {
		panel.querySelectorAll( '.wpcf7-not-valid-tip' ).forEach( ( tip ) => tip.remove() );
		panel.querySelectorAll( '.wpcf7-not-valid' ).forEach( ( field ) => {
			field.classList.remove( 'wpcf7-not-valid' );
			field.removeAttribute( 'aria-invalid' );
		} );
	};

	const showErrors = ( panel, invalidFields ) => {
		( invalidFields ?? [] ).forEach( ( { field: name, message } ) => {
			const control = panel.querySelector( `[name="${ name }"], [name="${ name }[]"]` );
			if ( ! control ) { return; }

			control.classList.add( 'wpcf7-not-valid' );
			control.setAttribute( 'aria-invalid', 'true' );

			const wrap = control.closest( '.wpcf7-form-control-wrap' ) ?? control.parentElement;
			const tip = document.createElement( 'span' );
			tip.className = 'wpcf7-not-valid-tip';
			tip.setAttribute( 'role', 'alert' );
			tip.textContent = message ?? '';
			wrap?.appendChild( tip );
		} );
	};

	const validateStep = ( root, panel, stepId ) => {
		const formId = formIdOf( root );
		const form = root.closest( 'form' );
		if ( ! formId || ! form || ! window.FCF7Multistep ) { return Promise.resolve( true ); }

		clearErrors( panel );

		const body = new FormData( form );
		body.set( 'action', 'fcf7_validate_step' );
		body.set( 'nonce', window.FCF7Multistep.nonce );
		body.set( 'form_id', formId );
		body.set( 'step_id', stepId );

		return fetch( window.FCF7Multistep.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' } )
			.then( ( res ) => res.json() )
			.then( ( json ) => {
				if ( json?.success ) { return true; }
				showErrors( panel, json?.data?.invalid_fields );
				return false;
			} )
			.catch( () => true );
	};

	const goTo = ( root, index ) => {
		const panelList = panels( root );
		const navList   = navItems( root );
		const target    = Math.max( 0, Math.min( panelList.length - 1, index ) );

		panelList.forEach( ( panel, i ) => panel.classList.toggle( 'is-active', i === target ) );
		navList.forEach( ( item, i ) => {
			item.classList.toggle( 'is-active', i === target );
			item.classList.toggle( 'is-done', i < target );
		} );

		const prev = root.querySelector( '.fcf7b-ms-prev' );
		const next = root.querySelector( '.fcf7b-ms-next' );
		if ( prev ) { prev.disabled = target <= 0; }
		if ( next ) { next.style.display = target >= panelList.length - 1 ? 'none' : ''; }

		root.setAttribute( 'data-current-step', String( target ) );
	};

	const TEXT_INPUT_TYPES = [ 'text', 'email', 'tel', 'url', 'number', 'search', 'password', 'date', 'datetime-local', 'month', 'week', 'time' ];

	const init = ( root ) => {
		if ( root.getAttribute( 'data-fcf7-ms-init' ) ) { return; }
		root.setAttribute( 'data-fcf7-ms-init', '1' );

		goTo( root, 0 );

		root.addEventListener( 'keydown', ( e ) => {
			if ( 'Enter' !== e.key ) { return; }
			const el = e.target;
			if ( 'INPUT' !== el.tagName || ! TEXT_INPUT_TYPES.includes( ( el.type || 'text' ).toLowerCase() ) ) { return; }

			const panelList = panels( root );
			if ( currentIndex( root ) >= panelList.length - 1 ) { return; }

			e.preventDefault();
			root.querySelector( '.fcf7b-ms-next' )?.click();
		} );

		root.querySelector( '.fcf7b-ms-next' )?.addEventListener( 'click', ( e ) => {
			const button    = e.currentTarget;
			const index     = currentIndex( root );
			const panelList = panels( root );
			const panel     = panelList[ index ];
			const stepId    = panel?.getAttribute( 'data-step-id' ) ?? '';

			button.disabled = true;
			validateStep( root, panel, stepId ).then( ( ok ) => {
				button.disabled = false;
				if ( ok ) { goTo( root, index + 1 ); }
			} );
		} );

		root.querySelector( '.fcf7b-ms-prev' )?.addEventListener( 'click', () => {
			goTo( root, currentIndex( root ) - 1 );
		} );

		root.addEventListener( 'fcf7:multistep-goto', ( e ) => {
			if ( e.target !== root ) { return; }
			goTo( root, parseInt( e.detail?.index ?? 0, 10 ) || 0 );
		} );

		navItems( root ).forEach( ( item, i ) => {
			item.addEventListener( 'click', () => {
				const index = currentIndex( root );
				if ( i <= index ) { goTo( root, i ); return; }

				const panelList = panels( root );
				const steps = [];
				for ( let s = index; s < i; s++ ) { steps.push( panelList[ s ] ); }

				steps
					.reduce(
						( chain, panel ) => chain.then( ( ok ) => ( ok ? validateStep( root, panel, panel?.getAttribute( 'data-step-id' ) ?? '' ) : false ) ),
						Promise.resolve( true )
					)
					.then( ( ok ) => { if ( ok ) { goTo( root, i ); } } );
			} );
		} );
	};

	const boot = () => {
		document.querySelectorAll( '.fcf7b-multistep[data-fcf7-multistep]' ).forEach( init );
	};

	const resetToFirstStep = ( form ) => {
		form.querySelectorAll( ':scope .fcf7b-multistep[data-fcf7-multistep]' ).forEach( ( root ) => {
			panels( root ).forEach( clearErrors );
			goTo( root, 0 );
		} );
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
	document.addEventListener( 'wpcf7submit', boot );
	document.addEventListener( 'wpcf7reset', boot );

	document.addEventListener( 'wpcf7mailsent', ( e ) => {
		if ( e.target instanceof HTMLFormElement ) { resetToFirstStep( e.target ); }
	} );
} )();
