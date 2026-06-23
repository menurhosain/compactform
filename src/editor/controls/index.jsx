
// Keys must match the PHP control type slug exactly (Base_Control::get_type()).
import { controls } from '../control-registry';

import TextControl from './TextControl';
import TextareaControl from './TextareaControl';
import HtmlControl from './HtmlControl';
import SelectControl from './SelectControl';
import ChooseControl from './ChooseControl';
import OptionsControl from './OptionsControl';
import DateListControl from './DateListControl';
import TimeRangesControl from './TimeRangesControl';
import PriceMapControl from './PriceMapControl';
import SwitcherControl from './SwitcherControl';
import HeadingControl from './HeadingControl';
import StepsControl from './StepsControl';

import ColorControl from '../components/ColorControl';
import IconPicker from '../components/IconPicker';
import DimensionsControl from '../components/DimensionsControl';
import SliderControl from '../components/SliderControl';
import ConditionsControl from '../components/ConditionsControl';
import TypographyControl from '../components/TypographyControl';
import BorderControl from '../components/BorderControl';
import BoxShadowControl from '../components/BoxShadowControl';

const Color = ( { ctrl, field, value, update, beginGesture, endGesture } ) => (
	<ColorControl ctrl={ ctrl } field={ field } value={ value }
		onChange={ ( v ) => update( ctrl.key, v ) }
		onGestureStart={ beginGesture } onGestureEnd={ endGesture } />
);

const Typography = ( { ctrl, value, inherited, update, beginGesture, endGesture } ) => (
	<TypographyControl value={ value } inherited={ inherited } onChange={ ( v ) => update( ctrl.key, v ) }
		onGestureStart={ beginGesture } onGestureEnd={ endGesture } />
);

const Border = ( { ctrl, value, update, device, beginGesture, endGesture } ) => (
	<BorderControl ctrl={ ctrl } value={ value } device={ device } onChange={ ( v ) => update( ctrl.key, v ) }
		onGestureStart={ beginGesture } onGestureEnd={ endGesture } />
);
Border.standalone = true;

const BoxShadow = ( { ctrl, value, update, beginGesture, endGesture } ) => (
	<BoxShadowControl value={ value } onChange={ ( v ) => update( ctrl.key, v ) }
		onGestureStart={ beginGesture } onGestureEnd={ endGesture } />
);

const Icon = ( { ctrl, value, update } ) => (
	<IconPicker value={ value } onChange={ ( v ) => update( ctrl.key, v ) } />
);

const Dimensions = ( { ctrl, value, inherited, update, device } ) => (
	<DimensionsControl ctrl={ ctrl } value={ value } inherited={ inherited } device={ device }
		onChange={ ( v ) => update( ctrl.key, v ) } />
);
Dimensions.standalone = true;

const Slider = ( { ctrl, value, inherited, update, device, beginGesture, endGesture } ) => (
	<SliderControl ctrl={ ctrl } value={ value } inherited={ inherited } device={ device }
		onChange={ ( v ) => update( ctrl.key, v ) }
		onGestureStart={ beginGesture } onGestureEnd={ endGesture } />
);
Slider.standalone = true;

const Conditions = ( { ctrl, value, update, field, allFields } ) => (
	<ConditionsControl value={ value } field={ field } allFields={ allFields }
		onChange={ ( v ) => update( ctrl.key, v ) } />
);
Conditions.standalone = true;

/* registration */

// Plain inputs — text/number/time/date share one renderer.
controls.registerComponent( 'text', TextControl );
controls.registerComponent( 'number', TextControl );
controls.registerComponent( 'time', TextControl );
controls.registerComponent( 'date', TextControl );
controls.registerComponent( 'textarea', TextareaControl );
controls.registerComponent( 'html', HtmlControl );
controls.registerComponent( 'select', SelectControl );
controls.registerComponent( 'choose', ChooseControl );
controls.registerComponent( 'options', OptionsControl );
controls.registerComponent( 'date_list', DateListControl );
controls.registerComponent( 'time_ranges', TimeRangesControl );

controls.registerComponent( 'price_map', PriceMapControl );
controls.registerComponent( 'switcher', SwitcherControl );
controls.registerComponent( 'heading', HeadingControl );
controls.registerComponent( 'steps', StepsControl );

// Composite / richer controls.
controls.registerComponent( 'color', Color );
controls.registerComponent( 'typography', Typography );
controls.registerComponent( 'border', Border );
controls.registerComponent( 'box_shadow', BoxShadow );
controls.registerComponent( 'icon', Icon );
controls.registerComponent( 'dimensions', Dimensions );
controls.registerComponent( 'slider', Slider );
controls.registerComponent( 'conditions', Conditions );
