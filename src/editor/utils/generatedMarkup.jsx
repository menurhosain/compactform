const OPEN_BLOCK  = /^\[fcf7_(container|repeater)(\s[^\]]*)?\]$/;
const CLOSE_BLOCK = /^\[\/fcf7_(container|repeater)\]$/;

function formatGeneratedMarkup( markup ) {
	if ( ! markup ) { return []; }

	const indent = '    ';
	let depth = 0;
	const out = [];

	for ( const raw of markup.split( '\n' ) ) {
		const line = raw.trim();
		if ( '' === line ) { continue; }

		if ( CLOSE_BLOCK.test( line ) || '</div>' === line || '</label>' === line ) {
			depth = Math.max( 0, depth - 1 );
			out.push( indent.repeat( depth ) + line );
			continue;
		}

		out.push( indent.repeat( depth ) + line );

		if ( OPEN_BLOCK.test( line ) ) {
			depth += 1;
		} else if (
			( line.startsWith( '<div' ) && ! line.includes( '</div>' ) ) ||
			( line.startsWith( '<label' ) && ! line.includes( '</label>' ) )
		) {
			depth += 1;
		}
	}

	return out;
}

const TAG_RE = /<\/?[a-zA-Z][^<>]*>|\[\/?[a-zA-Z_][^[\]]*\]/g;

function tokenizeTag( chunk ) {
	const isHtml = chunk[ 0 ] === '<';
	const isClosing = chunk[ 1 ] === '/';
	const selfClose = isHtml && chunk.length > 1 && chunk[ chunk.length - 2 ] === '/';
	const nameStart = isClosing ? 2 : 1;
	const nameEnd = chunk.length - ( selfClose ? 2 : 1 );
	const rest = chunk.slice( nameStart, nameEnd );
	const m = rest.match( /^(\S+)([\s\S]*)$/ );

	const parts = [ { k: 'punct', t: chunk.slice( 0, nameStart ) } ];

	if ( m ) {
		parts.push( { k: 'tag', t: m[ 1 ] } );
		if ( m[ 2 ] ) {
			const attrRe = /"[^"]*"|[^"]+/g;
			let am;
			while ( ( am = attrRe.exec( m[ 2 ] ) ) ) {
				parts.push( '"' === am[ 0 ][ 0 ] ? { k: 'str', t: am[ 0 ] } : { k: 'attr', t: am[ 0 ] } );
			}
		}
	} else if ( rest ) {
		parts.push( { k: 'tag', t: rest } );
	}

	parts.push( { k: 'punct', t: chunk.slice( nameEnd ) } );
	return parts;
}

function tokenizeLine( line ) {
	const tokens = [];
	let last = 0;
	TAG_RE.lastIndex = 0;
	let m;
	while ( ( m = TAG_RE.exec( line ) ) ) {
		if ( m.index > last ) { tokens.push( { k: 'text', t: line.slice( last, m.index ) } ); }
		tokens.push( ...tokenizeTag( m[ 0 ] ) );
		last = TAG_RE.lastIndex;
	}
	if ( last < line.length ) { tokens.push( { k: 'text', t: line.slice( last ) } ); }
	return tokens;
}

export function highlightGeneratedMarkup( markup ) {
	const lines = formatGeneratedMarkup( markup );
	const nodes = [];
	lines.forEach( ( line, li ) => {
		tokenizeLine( line ).forEach( ( tok, ti ) => {
			if ( '' === tok.t ) { return; }
			nodes.push( <span key={ `${ li }-${ ti }` } className={ `fcf7b-tok fcf7b-tok-${ tok.k }` }>{ tok.t }</span> );
		} );
		if ( li < lines.length - 1 ) { nodes.push( '\n' ); }
	} );
	return nodes;
}
