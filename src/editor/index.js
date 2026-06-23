import { createRoot, render } from "@wordpress/element";
import App from "./components/App";
import "./editor.scss";
import { registerServerMarkupPreviews } from "./fields";
import "./controls";
import {
  hooks,
  collectThirdPartyFieldPreviews,
} from "./field-preview-registry";
import { collectThirdPartyControls } from "./control-registry";
import { drag, BY_TYPE } from "./shared";

window.FCF7Builder = window.FCF7Builder || {};
window.FCF7Builder.hooks = hooks;
window.FCF7Builder.drag = drag;

function boot() {
  const mount = document.getElementById("fcf7-builder-root");
  if (!mount) {
    return;
  }

  mount.addEventListener(
    "keydown",
    (e) => {
      if (e.key !== "Enter") {
        return;
      }
      const tag = e.target?.tagName;
      if (tag === "TEXTAREA" || e.target?.isContentEditable) {
        return;
      }
      e.preventDefault();
    },
    true,
  );

  collectThirdPartyFieldPreviews();
  collectThirdPartyControls();
  registerServerMarkupPreviews();
  const input = document.getElementById("fcf7-builder-schema-input");
  let initial = { version: 1, fields: [] };
  if (input?.value) {
    try {
      initial = JSON.parse(input.value);
    } catch (e) {}
  }
  if (!Array.isArray(initial.fields)) {
    initial.fields = Object.values(initial.fields || {});
  }
  initial.fields = initial.fields.filter(
    (f) => f && typeof f === "object" && !Array.isArray(f) && f.type,
  );
  const dropped = new Set(
    initial.fields.filter((f) => !BY_TYPE[f.type]).map((f) => f.id),
  );
  if (dropped.size) {
    let grew = true;
    while (grew) {
      grew = false;
      initial.fields.forEach((f) => {
        if (!dropped.has(f.id) && f.parentId && dropped.has(f.parentId)) {
          dropped.add(f.id);
          grew = true;
        }
      });
    }
    initial.fields = initial.fields.filter((f) => !dropped.has(f.id));
  }
  const gs = (initial.integrations && initial.integrations.googleSheet) || {};
  const wh = (initial.integrations && initial.integrations.webhook) || {};
  initial.integrations = {
    ...(initial.integrations || {}),
    googleSheet: {
      enabled: !!gs.enabled,
      sheetId: gs.sheetId || "",
      tabId: gs.tabId || "",
    },
    webhook: {
      enabled: !!wh.enabled,
      url: wh.url || "",
      method: wh.method || "POST",
      format: wh.format || "json",
      bodyMode: "mapped" === wh.bodyMode ? "mapped" : "all",
      body: Array.isArray(wh.body) ? wh.body : [],
      headers: Array.isArray(wh.headers) ? wh.headers : [],
      meta: !!wh.meta,
      timeout: wh.timeout === undefined ? 10 : wh.timeout,
      blocking: !!wh.blocking,
    },
  };
  const sp = (initial.configuration && initial.configuration.spamProtection) || {};
  const dm = (initial.configuration && initial.configuration.defaultMail) || {};
  initial.configuration = {
    ...(initial.configuration || {}),
    spamProtection: {
      ...sp,
      honeypotEnabled:
        sp.honeypotEnabled === undefined ? true : !!sp.honeypotEnabled,
      minTimeEnabled: !!sp.minTimeEnabled,
      minTimeSeconds: sp.minTimeSeconds === undefined ? 3 : sp.minTimeSeconds,
      botMessage: sp.botMessage || "",
      bannedEnabled: !!sp.bannedEnabled,
      bannedWords: sp.bannedWords || "",
      bannedMessage: sp.bannedMessage || "",
    },
    defaultMail: {
      template: dm.template || "html-table",
      template2: dm.template2 || "html-table",
    },
  };
  if (createRoot) {
    createRoot(mount).render(<App initial={initial} />);
  } else {
    render(<App initial={initial} />, mount);
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", boot);
} else {
  boot();
}
