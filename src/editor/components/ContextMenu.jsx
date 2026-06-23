import { useRef, useState, useLayoutEffect } from '@wordpress/element';
import { fieldClipboard, copyToClipboard, styleFromClipboard, BY_TYPE } from '../shared';
import { hooks } from '../field-preview-registry';

const MOD_LABEL = /Mac|iPhone|iPad/i.test(
	( window.navigator && ( window.navigator.userAgentData?.platform || window.navigator.platform ) ) || ''
) ? '⌘' : '^';

export default function ContextMenu( { menu, fields, actions, onClose } ) {
	const menuRef = useRef( null );
	// The clamped position, once the menu has been measured — see below.
	const [ menuPos, setMenuPos ] = useState( null );

	useLayoutEffect( () => {
		if ( ! menu || ! menuRef.current ) { setMenuPos( null ); return; }
		const r = menuRef.current.getBoundingClientRect();
		const pad = 8;
		setMenuPos( {
			x: Math.max( pad, Math.min( menu.x, window.innerWidth - r.width - pad ) ),
			y: Math.max( pad, Math.min( menu.y, window.innerHeight - r.height - pad ) ),
		} );
	}, [ menu ] );

	if ( ! menu ) { return null; }

	// No `id` means the menu was opened on the canvas background rather than on a card.
	const blank = ! menu.id;
	const ids = blank ? [] : ( ( menu.ids && menu.ids.length ) ? menu.ids : [ menu.id ] );
	const many = ids.length > 1;
	const n = ids.length;

	const copy = () => { copyToClipboard( fields, ids ); onClose(); };

	const paste = () => {
		if ( fieldClipboard.value ) { actions.paste( fieldClipboard.value, menu.id ); }
		onClose();
	};

	const pasteStyle = () => {
		ids.forEach( ( id ) => {
			const patch = styleFromClipboard( fields.find( ( x ) => x.id === id ) );
			if ( Object.keys( patch ).length ) { actions.updateField( id, patch ); }
		} );
		onClose();
	};

	return (
		<div
			className="fcf7b-ctxmenu-backdrop"
			onClick={ ( e ) => { e.stopPropagation(); onClose(); } }
			onContextMenu={ ( e ) => { e.preventDefault(); e.stopPropagation(); onClose(); } }
		>
			<div
				ref={ menuRef }
				className="fcf7b-ctxmenu"
				style={ {
					left: menuPos ? menuPos.x : menu.x,
					top: menuPos ? menuPos.y : menu.y,
					visibility: menuPos ? 'visible' : 'hidden',
				} }
				onClick={ ( e ) => e.stopPropagation() }>
				{ many && <div className="fcf7b-ctxmenu-title">{ n } selected</div> }

				{ ! blank && (
					<button type="button" onClick={ copy }>
						<i className="ri-file-copy-2-line" /> { many ? `Copy All (${ n })` : 'Copy' }
						<span className="fcf7b-ctxmenu-key">{ MOD_LABEL }+C</span>
					</button>
				) }
				<button type="button" disabled={ ! fieldClipboard.value } onClick={ paste }>
					<i className="ri-clipboard-line" /> Paste
					<span className="fcf7b-ctxmenu-key">{ MOD_LABEL }+V</span>
				</button>
				{ ! blank && (
					<button type="button" disabled={ ! fieldClipboard.value } onClick={ pasteStyle }>
						<i className="ri-brush-line" /> { many ? 'Paste Style to All' : 'Paste Style' }
						<span className="fcf7b-ctxmenu-key">{ MOD_LABEL }+⇧+V</span>
					</button>
				) }

				{ ! many && ! blank && ( () => {
					const f = fields.find( ( x ) => x.id === menu.id );
					const d = f ? BY_TYPE[ f.type ] : null;
					if ( ! f ) { return null; }
					return hooks.applyFilters( 'fcf7b.cardContextMenuItems', [], f, d, actions, onClose )
						.map( ( it ) => <span key={ it.id }>{ it.render() }</span> );
				} )() }

				{ ! blank && <>
					<div className="fcf7b-ctxmenu-sep" />
					<button type="button" onClick={ () => { onClose(); actions.duplicateMany( ids ); } }>
						<i className="ri-file-copy-line" /> { many ? `Duplicate All (${ n })` : 'Duplicate' }
						<span className="fcf7b-ctxmenu-key">{ MOD_LABEL }+D</span>
					</button>
					<button type="button" className="danger" onClick={ () => { onClose(); actions.removeMany( ids ); } }>
						<i className="ri-delete-bin-line" /> { many ? `Delete All (${ n })` : 'Delete' }
						<span className="fcf7b-ctxmenu-key">⌦</span>
					</button>
				</> }
			</div>
		</div>
	);
}
