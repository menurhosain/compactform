import { __ } from '@wordpress/i18n';
import Switcher from './Switcher';

function ElementCard( { element, active, onToggle } ) {
	const isProLocked = element?.isPro && ! FCF7Local?.isProActive;

	return (
		<div className={ `fcf7-element-card${ active ? '' : ' is-inactive' }` }>
			<div className="fcf7-element-left">
				<h5 className="fcf7-element-title">
					{ element?.title }
					{ element?.isPro && <span className="fcf7-element-pro-badge">Pro</span> }
				</h5>
				<div className="fcf7-element-meta">
					{ element?.docUrl && (
						<a className="fcf7-element-metalink" href={ element.docUrl } target="_blank" rel="noreferrer">
							{ __( 'Docs', 'compactform' ) }
						</a>
					) }
				</div>
			</div>
			<Switcher checked={ active } onChange={ onToggle } disabled={ isProLocked } />
		</div>
	);
}

export default ElementCard;
