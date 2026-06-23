import { __ } from '@wordpress/i18n';
import { FONT_FAMILIES } from '../shared';
import SliderControl from './SliderControl';
import Popover from './Popover';
import Select from './Select';

const WEIGHTS = ['', '100', '200', '300', '400', '500', '600', '700', '800', '900'];
const TRANSFORMS = [
	['', __( 'Default', 'compactform' )],
	['none', __( 'None', 'compactform' )],
	['uppercase', __( 'Uppercase', 'compactform' )],
	['lowercase', __( 'Lowercase', 'compactform' )],
	['capitalize', __( 'Capitalize', 'compactform' )],
];
const STYLES = [
	['', __( 'Default', 'compactform' )],
	['normal', __( 'Normal', 'compactform' )],
	['italic', __( 'Italic', 'compactform' )],
];
const DECORATIONS = [
	['', __( 'Default', 'compactform' )],
	['none', __( 'None', 'compactform' )],
	['underline', __( 'Underline', 'compactform' )],
	['line-through', __( 'Line through', 'compactform' )],
];

const DEFAULT = {
	family: '', weight: '', transform: '', style: '', decoration: '',
	size: { size: '', unit: 'px' },
	lineHeight: { size: '', unit: 'em' },
	letterSpacing: { size: '', unit: 'px' },
};

const hasSize = (x) => !!(x && x.size !== '' && x.size != null);
const isSet = (v, inh) => !!(
	v.family || inh.family || v.weight || inh.weight ||
	v.transform || inh.transform || v.style || inh.style ||
	v.decoration || inh.decoration ||
	hasSize(v.size) || hasSize(inh.size) ||
	hasSize(v.lineHeight) || hasSize(inh.lineHeight) ||
	hasSize(v.letterSpacing) || hasSize(inh.letterSpacing)
);

export default function TypographyControl({ value, inherited, onChange, onGestureStart, onGestureEnd }) {
	const v = (value && typeof value === 'object') ? { ...DEFAULT, ...value } : { ...DEFAULT };
	const inh = (inherited && typeof inherited === 'object') ? { ...DEFAULT, ...inherited } : { ...DEFAULT };
	const set = (patch) => onChange({ ...v, ...patch });

	const eff = (key) => (v[key] || inh[key]);

	const row = (label, control) => (
		<div className="fcf7b-ctl-row">
			<span className="fcf7b-ctl-rowlabel">{label}</span>
			{control}
		</div>
	);

	return (
		<Popover
			className="fcf7b-typo"
			triggerClassName={`fcf7b-typo-trigger${isSet(v, inh) ? ' is-set' : ''}`}
			popClassName="fcf7b-typo-pop"
			title={ __( 'Edit typography', 'compactform' ) }
			trigger={(
				<>
					<span className="fcf7b-typo-aa" style={FONT_FAMILIES[v.family]?.stack ? { fontFamily: FONT_FAMILIES[v.family].stack } : null}>Aa</span>
					<i className="ri-pencil-line" />
				</>
			)}
		>
			<div className="fcf7b-pop-head">
				<span>{ __( 'Typography', 'compactform' ) }</span>
				<div className="fcf7b-pop-tools">
					<button type="button" title={ __( 'Reset', 'compactform' ) } onClick={() => onChange({ ...DEFAULT })}>
						<i className="ri-refresh-line" />
					</button>
				</div>
			</div>

			<SliderControl
				ctrl={{ label: __( 'Size', 'compactform' ), units: ['px', 'em', 'rem', '%', 'custom'], ranges: { px: { min: 0, max: 100, step: 1 }, em: { min: 0, max: 10, step: 0.1 }, rem: { min: 0, max: 10, step: 0.1 }, '%': { min: 0, max: 300, step: 1 } } }}
				value={v.size}
				inherited={inh.size}
				onChange={(o) => set({ size: o })}
				onGestureStart={onGestureStart}
				onGestureEnd={onGestureEnd}
			/>

			{row(__( 'Weight', 'compactform' ), (
				<Select
					value={eff('weight')}
					options={WEIGHTS.map((w) => ({ value: w, label: w === '' ? __( 'Default', 'compactform' ) : w }))}
					onChange={(val) => set({ weight: val })}
				/>
			))}

			{row(__( 'Transform', 'compactform' ), (
				<Select
					value={eff('transform')}
					options={TRANSFORMS.map(([k, t]) => ({ value: k, label: t }))}
					onChange={(val) => set({ transform: val })}
				/>
			))}

			{row(__( 'Style', 'compactform' ), (
				<Select
					value={eff('style')}
					options={STYLES.map(([k, t]) => ({ value: k, label: t }))}
					onChange={(val) => set({ style: val })}
				/>
			))}

			{row(__( 'Decoration', 'compactform' ), (
				<Select
					value={eff('decoration')}
					options={DECORATIONS.map(([k, t]) => ({ value: k, label: t }))}
					onChange={(val) => set({ decoration: val })}
				/>
			))}

			<SliderControl
				ctrl={{ label: __( 'Line Height', 'compactform' ), units: ['em', 'px', '%', 'custom'], ranges: { em: { min: 0, max: 5, step: 0.1 }, px: { min: 0, max: 100, step: 1 }, '%': { min: 0, max: 300, step: 1 } } }}
				value={v.lineHeight}
				inherited={inh.lineHeight}
				onChange={(o) => set({ lineHeight: o })}
				onGestureStart={onGestureStart}
				onGestureEnd={onGestureEnd}
			/>

			<SliderControl
				ctrl={{ label: __( 'Letter Spacing', 'compactform' ), units: ['px', 'em', 'custom'], ranges: { px: { min: -5, max: 20, step: 0.1 }, em: { min: -1, max: 2, step: 0.01 } } }}
				value={v.letterSpacing}
				inherited={inh.letterSpacing}
				onChange={(o) => set({ letterSpacing: o })}
				onGestureStart={onGestureStart}
				onGestureEnd={onGestureEnd}
			/>

		</Popover>
	);
}
