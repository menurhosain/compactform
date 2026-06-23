import { __, sprintf } from '@wordpress/i18n';
import { hooks } from '../field-preview-registry';

export default function CardHandle( { field, def, nested, actions, openMenu, onAdd, multi = [] } ) {
	const title = def && def.title ? def.title : field.type;
	const isContainer = 'container' === field.type;

	const pick = ( e ) => { e.stopPropagation(); actions.select( field.id, e.ctrlKey || e.metaKey ); };

	const menuOn = ( e ) => {
		e.preventDefault();
		e.stopPropagation();
		if ( ! multi.includes( field.id ) ) { actions.select( field.id ); }
		if ( openMenu ) { openMenu( field.id, e.clientX, e.clientY ); }
	};

	const globalBadge = hooks.applyFilters( 'fcf7b.cardHandleBadge', null, field, def );

	if ( isContainer && ! nested ) {
		return (
			<div className="fcf7b-hdl fcf7b-hdl-tab" onContextMenu={ menuOn }>
				{ onAdd && (
					<button type="button" title={ __( 'Add a container below', 'compactform' ) }
						onClick={ ( e ) => { e.stopPropagation(); onAdd(); } }>
						<i className="ri-add-line" />
					</button>
				) }
				<span className="fcf7b-hdl-grip" title={ sprintf( /* translators: %s: field name. */ __( '%s — drag to reorder', 'compactform' ), title ) }
					onClick={ pick }>
					<i className="ri-draggable" />
				</span>
				<button type="button" title={ __( 'Delete', 'compactform' ) } className="danger"
					onClick={ ( e ) => { e.stopPropagation(); actions.remove( field.id ); } }>
					<i className="ri-close-line" />
				</button>
			</div>
		);
	}

	if ( isContainer ) {
		return (
			<div className="fcf7b-hdl fcf7b-hdl-box" onContextMenu={ menuOn }>
				<button type="button" title={ sprintf( /* translators: %s: field name. */ __( '%s — click to edit, drag to move', 'compactform' ), title ) }
					onClick={ pick }>
					<i className="ri-draggable" />
				</button>
				{ globalBadge }
			</div>
		);
	}

	return (
		<div className="fcf7b-hdl fcf7b-hdl-pen" onContextMenu={ menuOn }>
			{ globalBadge }
			<button type="button" title={ sprintf( /* translators: %s: field name. */ __( 'Edit %s', 'compactform' ), title ) }
				onClick={ pick }>
				<i className="ri-pencil-line" />
			</button>
		</div>
	);
}
