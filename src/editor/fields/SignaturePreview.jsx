import { __ } from '@wordpress/i18n';
import FieldChrome from './preview-helpers';

export default function SignaturePreview( { field, def } ) {
	const bg = field.bg_color || '#bceeff';
	const pen = field.pen_color || '#000000';
	const buttonTxt = field.clear_text || __( 'Clear', 'compactform' );

	return (
		<FieldChrome field={ field } def={ def }>
			<span className="wpcf7-form-control fcf7-signature-frame">
				<span className="fcf7-signature-pad">
					<canvas style={ { backgroundColor: bg } } />
					<svg
						className="fcf7b-pv-sigink"
						viewBox="0 0 200 60"
						preserveAspectRatio="xMidYMid meet"
						aria-hidden="true"
					>
						<path
							d="M12 42c14-22 22-26 27-16s2 22 9 22 12-24 20-30 12 8 19 8 11-10 18-10 14 12 23 10 16-10 24-16"
							fill="none"
							stroke={ pen }
							strokeWidth="2"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
					</svg>
				</span>
				<span className="fcf7-signature-actions">
					<button type="button" className="fcf7-signature-clear" disabled={ true }>{ buttonTxt }</button>
				</span>
			</span>
		</FieldChrome>
	);
}
