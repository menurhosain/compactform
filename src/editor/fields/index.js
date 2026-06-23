// Registers every first-party field's canvas-preview component — the JS
import { registry } from "../field-preview-registry";
import { FIELDS } from "../shared";
import ServerMarkupPreview from "./ServerMarkupPreview";

import HeadingPreview from "./HeadingPreview";
import DividerPreview from "./DividerPreview";
import HtmlPreview from "./HtmlPreview";
import SubmitPreview from "./SubmitPreview";
import MultistepFormPreview from "./MultistepFormPreview";
import RepeaterPreview from "./RepeaterPreview";
import ContainerPreview from "./ContainerPreview";
import AcceptancePreview from "./AcceptancePreview";
import TextareaPreview from "./TextareaPreview";
import SelectPreview from "./SelectPreview";
import ChoicePreview from "./ChoicePreview";
import InputPreview from "./InputPreview";
import PickerPreview from "./PickerPreview";
import StarRatingPreview from "./StarRatingPreview";
import SignaturePreview from "./SignaturePreview";
import RangeSliderPreview from "./RangeSliderPreview";
import QuizPreview from './QuizPreview';
import FilePreview from './FilePreview';
import GoogleRecaptchaPreview from './GoogleRecaptchaPreview';
import SpamProtectionPreview from './SpamProtectionPreview';

// Layout.
registry.registerComponent("heading", HeadingPreview);
registry.registerComponent("divider", DividerPreview);
registry.registerComponent("html", HtmlPreview);
registry.registerComponent("submit", SubmitPreview);
registry.registerComponent("acceptance", AcceptancePreview);
registry.registerComponent("multistep", MultistepFormPreview);
registry.registerComponent("repeater", RepeaterPreview);
registry.registerComponent("container", ContainerPreview);

// Basic inputs — text/email/tel/url/number/date share one preview (mirrors
// PHP's Input_Field base).
registry.registerComponent("text", InputPreview);
registry.registerComponent("email", InputPreview);
registry.registerComponent("tel", InputPreview);
registry.registerComponent("url", InputPreview);
registry.registerComponent("number", InputPreview);
registry.registerComponent("date", InputPreview);
// The three pickers run the real flatpickr widget in the canvas, through their
// own front-end init scripts — see PickerPreview.jsx.
registry.registerComponent("fcf7_date_picker", PickerPreview);
registry.registerComponent("fcf7_time_picker", PickerPreview);
registry.registerComponent("fcf7_datetime_picker", PickerPreview);
registry.registerComponent("textarea", TextareaPreview);
registry.registerComponent( 'quiz', QuizPreview );
registry.registerComponent( 'file', FilePreview );

// Choice.
registry.registerComponent("select", SelectPreview);
registry.registerComponent("fcf7_country_dropdown", SelectPreview);
registry.registerComponent("radio", ChoicePreview);
registry.registerComponent("checkbox", ChoicePreview);

// Extensions.
registry.registerComponent("fcf7_star_rating", StarRatingPreview);
registry.registerComponent("fcf7_signature", SignaturePreview);
registry.registerComponent("fcf7_range_slider", RangeSliderPreview);
registry.registerComponent("fcf7_google_recaptcha", GoogleRecaptchaPreview);

registry.registerComponent("fcf7_spam_protection", SpamProtectionPreview);

export function registerServerMarkupPreviews() {
  FIELDS.forEach((def) => {
    if (!def.builderMarkup) {
      return;
    }
    if (registry.getComponent(def.type) || registry.get(def.type)) {
      return;
    }
    registry.registerComponent(def.type, ServerMarkupPreview);
  });
}
