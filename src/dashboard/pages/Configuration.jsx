import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { TextControl, SelectControl } from "@wordpress/components";
import { LuShieldCheck } from "react-icons/lu";
import { hooks } from "../dashboard-hooks";

function SaveButton({ status }) {
  return (
    <button
      type="submit"
      className={`fcf7-save-btn fcf7-save-btn--${status}`}
      disabled={status === "saving"}
    >
      {status === "saving" && __("Saving…", "compactform")}
      {status === "saved" && __("Saved!", "compactform")}
      {status === "error" && __("Error!", "compactform")}
      {status === "idle" && __("Save Changes", "compactform")}
    </button>
  );
}

function RecaptchaSettings() {
  const initial = FCF7Local?.recaptchaSettings || {};

  const [siteKey, setSiteKey] = useState(initial.siteKey || "");
  const [secretKey, setSecretKey] = useState("");
  const [hasSecretKey, setHasSecretKey] = useState(!!initial.hasSecretKey);
  const [saveStatus, setSaveStatus] = useState("idle");

  const handleSave = async (e) => {
    e.preventDefault();
    setSaveStatus("saving");

    try {
      const body = new FormData();
      body.append("action", "fcf7_save_recaptcha_settings");
      body.append("nonce", FCF7Local.nonce);
      body.append("site_key", siteKey);
      body.append("secret_key", secretKey);

      const res = await fetch(FCF7Local.ajaxUrl, { method: "POST", body });
      const data = await res.json();

      if (data.success) {
        FCF7Local.recaptchaSettings = data.data.settings;
        setHasSecretKey(!!data.data.settings.hasSecretKey);
        setSecretKey("");
        setSaveStatus("saved");
      } else {
        setSaveStatus("error");
      }
    } catch {
      setSaveStatus("error");
    } finally {
      setTimeout(() => setSaveStatus("idle"), 2500);
    }
  };

  return (
    <div className="fcf7-config-container">
      <h2 className="fcf7-config-heading">
        <LuShieldCheck /> {__("Google reCAPTCHA (v3)", "compactform")}
      </h2>
      <p className="fcf7-config-text">
        {__(
          "Credentials for the Google reCAPTCHA field. Generate a v3 key pair at google.com/recaptcha/admin.",
          "compactform",
        )}
      </p>

      <form className="fcf7-config-form" onSubmit={handleSave}>
        <div className="fcf7-config-grid">
          <TextControl
            __next40pxDefaultSize
            label={__("Site Key", "compactform")}
            value={siteKey}
            onChange={setSiteKey}
            autoComplete="off"
          />
          <TextControl
            __next40pxDefaultSize
            type="password"
            label={__("Secret Key", "compactform")}
            value={secretKey}
            onChange={setSecretKey}
            placeholder={
              hasSecretKey
                ? "••••••••••••••••"
                : __("Not set", "compactform")
            }
            help={ hasSecretKey ? __( "Leave blank to keep the saved secret key.", "compactform",) : "" }
            autoComplete="new-password"
          />
        </div>

        <SaveButton status={saveStatus} />
      </form>
    </div>
  );
}

function Configuration() {
  const extraSections = hooks.applyFilters(
    "fcf7Dashboard.configurationSections",
    [],
  );

  return (
    <div className="fcf7-config-page">
      <RecaptchaSettings />
      {extraSections}
    </div>
  );
}

export default Configuration;
