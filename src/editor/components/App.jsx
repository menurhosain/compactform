import { useState, useEffect, useMemo } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { CANVAS_BG_KEY, readCanvasBg } from "./CanvasBackground";
import { warmIconCache, globalSpacing } from "../shared";
import { hooks } from "../field-preview-registry";
import { highlightGeneratedMarkup } from "../utils/generatedMarkup";
import useHistory from "../hooks/useHistory";
import useFieldActions from "../hooks/useFieldActions";
import useCf7Persistence from "../hooks/useCf7Persistence";
import useKeyboardShortcuts from "../hooks/useKeyboardShortcuts";
import useImportExport from "../hooks/useImportExport";
import Toolbar from "./Toolbar";
import Palette from "./Palette";
import Canvas from "./Canvas";
import Props from "./Props";
import Navigator from "./Navigator";

function setOrDrop(schema, key, value) {
  const next = { ...schema };
  if (value === undefined) {
    delete next[key];
  } else {
    next[key] = value;
  }
  return next;
}

export default function App(props) {
  const [schema, setSchema] = useState(props.initial);
  const [view, setView] = useState("preview");
  const [search, setSearch] = useState("");
  const [theme, setTheme] = useState(() => {
    try {
      return window.localStorage.getItem("fcf7b-theme") === "dark"
        ? "dark"
        : "light";
    } catch (e) {
      return "light";
    }
  });
  const [fullscreen, setFullscreen] = useState(false);
  const [navOpen, setNavOpen] = useState(() => {
    try {
      return window.localStorage.getItem("fcf7b-nav-open") === "1";
    } catch (e) {
      return false;
    }
  });
  const [canvasBg, setCanvasBg] = useState(readCanvasBg);
  const [device, setDevice] = useState("desktop");

  //TEMP
  window.CF7_BUILDER_STATE = schema;

  const {
    selected,
    multi,
    actions: baseActions,
  } = useFieldActions(schema, setSchema);
  const history = useHistory(schema, setSchema);
  const persistence = useCf7Persistence(schema);
  useKeyboardShortcuts({
    schema,
    selected,
    multi,
    actions: baseActions,
    undo: history.undo,
    redo: history.redo,
  });
  const { exportForm, onImportFile, importInput, importSchema } = useImportExport(
    schema,
    setSchema,
    baseActions.select,
  );

  const actions = {
    ...baseActions,
    beginGesture: history.beginGesture,
    endGesture: history.endGesture,
  };

  // Preload the first batch of field icons at boot (warms the picker cache).
  useEffect(() => {
    warmIconCache();
  }, []);

  // Persist theme across reloads.
  useEffect(() => {
    try {
      window.localStorage.setItem("fcf7b-theme", theme);
    } catch (e) {}
  }, [theme]);

  useEffect(() => {
    try {
      window.localStorage.setItem("fcf7b-nav-open", navOpen ? "1" : "0");
    } catch (e) {}
  }, [navOpen]);

  // Canvas background
  useEffect(() => {
    try {
      window.localStorage.setItem(CANVAS_BG_KEY, canvasBg);
    } catch (e) {}
  }, [canvasBg]);

  // Esc exits fullscreen.
  useEffect(() => {
    if (!fullscreen) {
      return;
    }
    const onKey = (e) => {
      if (e.key === "Escape") {
        setFullscreen(false);
      }
    };
    document.addEventListener("keydown", onKey);
    return () => document.removeEventListener("keydown", onKey);
  }, [fullscreen]);

  const selField = schema.fields.filter((f) => f.id === selected)[0] || null;

  globalSpacing.value = {
    containerColumnGap: schema.containerColumnGap,
    containerRowGap: schema.containerRowGap,
    containerPadding: schema.containerPadding,
  };

  const highlightedMarkup = useMemo(
    () => highlightGeneratedMarkup(persistence.render.markup),
    [persistence.render.markup],
  );

  return (
    <div
      className={`fcf7b-app fcf7-builder-theme-${theme}${
        fullscreen ? " is-fullscreen" : ""
      }`}
    >
      <Toolbar
        view={view}
        setView={setView}
        fieldsCount={schema.fields.length}
        renderLoading={persistence.render.loading}
        device={device}
        setDevice={setDevice}
        navOpen={navOpen}
        setNavOpen={setNavOpen}
        schema={schema}
        setSchema={setSchema}
        breakpoints={schema.breakpoints || {}}
        onBreakpointsChange={(bp) => setSchema({ ...schema, breakpoints: bp })}
        fieldGap={schema.fieldGap}
        onFieldGapChange={(gap) =>
          setSchema(setOrDrop(schema, "fieldGap", gap))
        }
        containerColumnGap={schema.containerColumnGap}
        onContainerColumnGapChange={(gap) =>
          setSchema(setOrDrop(schema, "containerColumnGap", gap))
        }
        containerRowGap={schema.containerRowGap}
        onContainerRowGapChange={(gap) =>
          setSchema(setOrDrop(schema, "containerRowGap", gap))
        }
        containerPadding={schema.containerPadding}
        onContainerPaddingChange={(pad) =>
          setSchema(setOrDrop(schema, "containerPadding", pad))
        }
        webhook={schema.integrations?.webhook}
        onWebhookChange={(wh) =>
          setSchema({
            ...schema,
            integrations: { ...schema.integrations, webhook: wh },
          })
        }
        spamProtection={schema.configuration?.spamProtection}
        onSpamProtectionChange={(sp) =>
          setSchema({
            ...schema,
            configuration: { ...schema.configuration, spamProtection: sp },
          })
        }
        fields={schema.fields}
        canUndo={history.canUndo}
        canRedo={history.canRedo}
        undo={history.undo}
        redo={history.redo}
        exportForm={exportForm}
        onImportFile={onImportFile}
        importInput={importInput}
        importSchema={importSchema}
        canvasBg={canvasBg}
        setCanvasBg={setCanvasBg}
        theme={theme}
        setTheme={setTheme}
        fullscreen={fullscreen}
        setFullscreen={setFullscreen}
        saveState={persistence.saveState}
        dirty={persistence.dirty}
        saveError={persistence.saveError}
        saveProgress={persistence.saveProgress}
        quickSave={persistence.quickSave}
      />
      {view === "code" ? (
        <div className="fcf7b-code">
          <div className="fcf7b-code-note">
            {__(
              "Generated from the builder on the server. Save the form with CF7’s Save button.",
              "compactform",
            )}
          </div>
          <pre className="fcf7b-code-pre">
            {highlightedMarkup.length
              ? highlightedMarkup
              : __("(empty form)", "compactform")}
          </pre>
        </div>
      ) : (
        <div className="fcf7b-columns">
          <Palette
            search={search}
            setSearch={setSearch}
            onAdd={(t, overrides) => actions.add(t, null, overrides)}
            schema={schema}
            setSchema={setSchema}
          />
          <Canvas
            fields={schema.fields}
            selected={selected}
            multi={multi}
            actions={actions}
            device={device}
            breakpoints={schema.breakpoints || {}}
            background={canvasBg}
            fieldGap={schema.fieldGap}
            containerColumnGap={schema.containerColumnGap}
            containerRowGap={schema.containerRowGap}
            containerPadding={schema.containerPadding}
          />
          <Props
            field={multi.length > 1 ? null : selField}
            update={actions.update}
            beginGesture={actions.beginGesture}
            endGesture={actions.endGesture}
            allFields={schema.fields}
            device={device}
          />
          {navOpen && (
            <Navigator
              fields={schema.fields}
              selected={selected}
              multi={multi}
              actions={actions}
              onClose={() => setNavOpen(false)}
            />
          )}
        </div>
      )}
      {hooks
        .applyFilters("fcf7b.appOverlays", [], {
          fields: schema.fields,
          selected,
          multi,
          actions,
        })
        .map((o) => (
          <span key={o.id}>{o.render()}</span>
        ))}
    </div>
  );
}
