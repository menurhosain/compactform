( function () {
	var boot = function () {
		var data = window.fcf7AdminBar;
		var root = document.getElementById( 'wp-admin-bar-root-default' );
		if ( ! data || ! root || ! data.forms || ! data.forms.length ) { return; }

		// Inline styles: the admin bar carries no plugin stylesheet on the front end.
		var icon = function () {
			if ( ! data.icon ) { return null; }
			var img = document.createElement( 'img' );
			img.src = data.icon;
			img.alt = '';
			img.style.cssText = 'width:16px;height:16px;margin-right:6px;vertical-align:middle;position:relative;top:-1px;';
			return img;
		};

		var item = function ( id, form ) {
			var li = document.createElement( 'li' );
			li.id = id;
			var a = document.createElement( 'a' );
			a.className = 'ab-item';
			a.href = form.url;
			a.textContent = form.title;
			li.appendChild( a );
			return li;
		};

		if ( 1 === data.forms.length ) {
			var single = item( 'wp-admin-bar-fcf7-edit-form', data.forms[ 0 ] );
			var link = single.querySelector( 'a' );
			link.textContent = data.label;
			link.title = data.forms[ 0 ].title;
			var singleIcon = icon();
			if ( singleIcon ) { link.insertBefore( singleIcon, link.firstChild ); }
			root.appendChild( single );
			return;
		}

		var parent = document.createElement( 'li' );
		parent.id = 'wp-admin-bar-fcf7-edit-form';
		parent.className = 'menupop';

		var toggle = document.createElement( 'a' );
		toggle.className = 'ab-item';
		toggle.href = data.forms[ 0 ].url;
		toggle.setAttribute( 'aria-haspopup', 'true' );
		toggle.textContent = data.label + ' (' + data.forms.length + ')';
		var toggleIcon = icon();
		if ( toggleIcon ) { toggle.insertBefore( toggleIcon, toggle.firstChild ); }
		parent.appendChild( toggle );

		var wrapper = document.createElement( 'div' );
		wrapper.className = 'ab-sub-wrapper';
		var list = document.createElement( 'ul' );
		list.id = 'wp-admin-bar-fcf7-edit-form-default';
		list.className = 'ab-submenu';

		data.forms.forEach( function ( form, i ) {
			list.appendChild( item( 'wp-admin-bar-fcf7-edit-form-' + i, form ) );
		} );

		wrapper.appendChild( list );
		parent.appendChild( wrapper );
		root.appendChild( parent );

		var open = function () { parent.classList.add( 'hover' ); };
		var close = function () { parent.classList.remove( 'hover' ); };
		parent.addEventListener( 'mouseenter', open );
		parent.addEventListener( 'mouseleave', close );
		parent.addEventListener( 'focusin', open );
		parent.addEventListener( 'focusout', close );
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
