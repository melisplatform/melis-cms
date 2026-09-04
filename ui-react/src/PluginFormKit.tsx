import { useEffect, useReducer, useState } from 'react'
import type { ReactNode } from 'react'
import { PagePicker } from './PagePicker'
import { peT } from './page-editor-i18n'

/**
 * PluginFormKit — the SHARED toolkit for full-React plugin config forms (evo/page-edition-react).
 *
 * A plugin's native config form lives in ITS OWN module (e.g. melis-front's breadcrumb form under
 * `melis-front/ui-react/plugin-config/`), and imports these primitives so every form looks and behaves
 * the same and submits the same way. Kept in a standalone module (not PluginForms.tsx) so the per-module
 * forms can import it without a circular dependency (PluginForms.tsx imports the forms to register them).
 *
 * A form only owns the UI. On submit it POSTs field name/value pairs to the SAME stateless endpoint the
 * generic legacy iframe uses (`edition/plugin-config/save`), which runs the plugin's own input filters and
 * `savePluginConfigToXml()` → the persisted XML stays byte-compatible with the legacy reader.
 */

export type PluginFormProps = {
  idPage: number
  module: string
  pluginName: string
  pluginId: string
  tag: string
  /** The plugin's current XML fragment (node.raw) — parse it for prefill. */
  rawXml: string
  accent: string
  onSaved: (changed: boolean) => void
  onCancel: () => void
}

/** Server error shape from EditionPluginConfigController::saveAction (per-tab, per-field). */
type TabError = { name?: string; success?: boolean; errors?: Record<string, Record<string, string> & { label?: string }> }

/** Shared submit: POST values to the stateless save endpoint. */
export async function savePluginConfig(
  args: { idPage: number; module: string; pluginName: string; pluginId: string; values: Record<string, string | string[]> },
): Promise<{ ok: true; changed: boolean } | { ok: false; fieldErrors: Record<string, string>; message: string }> {
  // Raw fetch (NOT apiPost): a validation failure comes back as HTTP 200 {success:false, errors:[…]},
  // and apiPost would throw away that body — we need the per-field errors here.
  const r = await fetch('/melis/react-api/cms-page/edition/plugin-config/save', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify(args),
  })
  const res: any = await r.json().catch(() => ({}))
  if (res && res.success) {
    // The save just changed this instance's config server-side (draft session) — drop any cached
    // options/schema fetch for it so the NEXT time this modal opens, it re-fetches instead of replaying
    // the pre-save promise (fieldOptions/fieldValues/fieldList and the schema are both cached per
    // idPage|module|pluginName|pluginId, keyed identically — see fetchFieldOptions/fetchSchema below).
    const cacheKey = `${args.idPage}|${args.module}|${args.pluginName}|${args.pluginId}`
    _optionsCache.delete(cacheKey)
    _schemaCache.delete(cacheKey)
    return { ok: true, changed: !!(res.data && res.data.changed) }
  }
  const fieldErrors: Record<string, string> = {}
  const tabNames: string[] = []
  for (const tab of (res?.errors || []) as TabError[]) {
    if (tab && tab.success === false) {
      if (tab.name) tabNames.push(tab.name)
      const errs = tab.errors || {}
      for (const field of Object.keys(errs)) {
        const e = errs[field] || {}
        const msgs = Object.keys(e).filter((k) => k !== 'label' && typeof (e as any)[k] === 'string').map((k) => (e as any)[k])
        fieldErrors[field] = msgs.join(' ')
      }
    }
  }
  const tr = peT()
  const message = res?.error
    ? String(res.error)
    : (tabNames.length ? `${tr.pfFixFields} (${tabNames.join(', ')}).` : tr.pfSaveFailed)
  return { ok: false, fieldErrors, message }
}

/** Read one CDATA/text child value out of a plugin XML fragment (for prefill from props.rawXml). */
export function readTag(rawXml: string, tag: string): string {
  try {
    const doc = new DOMParser().parseFromString(rawXml, 'application/xml')
    if (!doc.querySelector('parsererror')) {
      const el = doc.getElementsByTagName(tag)[0]
      if (el) return el.textContent ?? ''
    }
  } catch { /* fall through */ }
  const m = rawXml.match(new RegExp('<' + tag + '[^>]*>(?:<!\\[CDATA\\[)?([\\s\\S]*?)(?:\\]\\]>)?</' + tag + '>'))
  return m ? m[1] : ''
}

export type Option = { value: string; label: string }
export type FieldListRow = { name: string; label: string; shown: boolean; required: boolean }
export type PluginOptions = { templateOptions: Option[]; fieldOptions: Record<string, Option[]>; fieldValues: Record<string, string>; fieldList: FieldListRow[] }

// One fetch per (plugin,page) shared by every field on the form (cached promise).
const _optionsCache = new Map<string, Promise<PluginOptions>>()

/** GET the option lists a plugin's config SELECT fields need (template_path + custom selects) from the
 *  server, once per plugin INSTANCE/page (keyed by pluginId too — a page can hold several instances of
 *  the SAME plugin, e.g. two Sliders, each with its own saved config). `fieldOptions[name]` holds a
 *  field's options; `templateOptions` is a convenience alias for the template_path field. */
export function fetchFieldOptions(args: { idPage: number; module: string; pluginName: string; pluginId: string }): Promise<PluginOptions> {
  const key = `${args.idPage}|${args.module}|${args.pluginName}|${args.pluginId}`
  let p = _optionsCache.get(key)
  if (!p) {
    p = (async () => {
      try {
        const q = `idPage=${args.idPage}&module=${encodeURIComponent(args.module)}&pluginName=${encodeURIComponent(args.pluginName)}&pluginId=${encodeURIComponent(args.pluginId)}`
        const r = await fetch(`/melis/react-api/cms-page/edition/plugin-config/options?${q}`, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        const res: any = await r.json().catch(() => ({}))
        const fieldOptions = (res?.data?.fieldOptions || {}) as Record<string, Option[]>
        const fieldValues = (res?.data?.fieldValues || {}) as Record<string, string>
        const fieldList = (res?.data?.fieldList || []) as FieldListRow[]
        const templateOptions = (res?.data?.templateOptions || fieldOptions.template_path || []) as Option[]
        return { templateOptions, fieldOptions, fieldValues, fieldList }
      } catch {
        return { templateOptions: [], fieldOptions: {}, fieldValues: {}, fieldList: [] }
      }
    })()
    _optionsCache.set(key, p)
  }
  return p
}

// ---------------------------------------------------------------- shared UI ---

export const inputStyle: React.CSSProperties = {
  width: '100%', padding: '8px 10px', borderRadius: 6, border: '1px solid var(--color-border,#e5e7eb)',
  background: 'var(--color-background,#fff)', color: 'inherit', fontSize: 13, boxSizing: 'border-box',
}

/** A labeled field wrapper: a header row with the label (left) and the info/hint (right), then the
 *  control, then an optional error line. */
export function Field({ label, error, children, hint }: { label: string; error?: string; children: ReactNode; hint?: string }) {
  return (
    <div style={{ marginBottom: 14 }}>
      <div style={{ display: 'flex', alignItems: 'baseline', justifyContent: 'space-between', gap: 12, margin: '0 0 5px' }}>
        <label style={{ fontSize: 12, fontWeight: 600, color: 'var(--color-foreground,#111827)', flex: '0 0 auto' }}>{label}</label>
        {hint ? <span style={{ fontSize: 11, color: 'var(--color-muted-foreground,#6b7280)', textAlign: 'right', flex: '1 1 auto', minWidth: 0 }}>{hint}</span> : null}
      </div>
      {children}
      {error ? <div style={{ color: '#dc2626', fontSize: 12, marginTop: 3 }}>{error}</div> : null}
    </div>
  )
}

/** A submit hook shared by native forms: manages saving/errors and calls onSaved. */
export function useSubmit(props: PluginFormProps) {
  const [saving, setSaving] = useState(false)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({})
  const [message, setMessage] = useState<string | null>(null)
  const submit = async (values: Record<string, string | string[]>) => {
    setSaving(true); setMessage(null); setFieldErrors({})
    try {
      const r = await savePluginConfig({ idPage: props.idPage, module: props.module, pluginName: props.pluginName, pluginId: props.pluginId, values })
      if (r.ok) { props.onSaved(r.changed); return }
      setFieldErrors(r.fieldErrors); setMessage(r.message)
    } catch (e) {
      setMessage((e as Error).message || peT().pfNetworkError)
    } finally { setSaving(false) }
  }
  return { saving, fieldErrors, message, submit }
}

/** Footer with Cancel / Save buttons, shared look with the modal. */
export function FormFooter({ saving, accent, onCancel, onSave }: { saving: boolean; accent: string; onCancel: () => void; onSave: () => void }) {
  const tr = peT()
  return (
    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 18 }}>
      <button type="button" data-testid="plugin-form-cancel" onClick={onCancel} style={{ border: '1px solid var(--color-border,#e5e7eb)', background: 'transparent', color: 'inherit', borderRadius: 6, padding: '7px 14px', fontSize: 12, cursor: 'pointer' }}>{tr.cancel}</button>
      <button type="button" data-testid="plugin-form-save" onClick={onSave} disabled={saving} style={{ border: 0, borderRadius: 6, padding: '7px 16px', fontSize: 12, fontWeight: 600, cursor: saving ? 'not-allowed' : 'pointer', background: accent, color: '#fff', opacity: saving ? .6 : 1 }}>{saving ? tr.savingScripts : tr.save}</button>
    </div>
  )
}

export function FormMessage({ message }: { message: string | null }) {
  if (!message) return null
  return <div data-testid="plugin-form-error" style={{ padding: '9px 11px', borderRadius: 6, marginBottom: 4, background: 'rgba(220,38,38,.12)', color: '#dc2626', border: '1px solid rgba(220,38,38,.3)', fontSize: 12 }}>{message}</div>
}

// ------------------------------------------------------------ TABS system ---

/**
 * The shared context every tab receives. All tabs of a plugin write into ONE `values` map (field →
 * value), so a single Save posts them all to the plugin's savePluginConfigToXml() — exactly like the
 * legacy multi-tab modal_form. `props.rawXml` is the current fragment for prefill (via readTag).
 */
export type FieldValue = string | string[]
export type PluginTabContext = {
  props: PluginFormProps
  /** Scalar value of a field ('' when unset or when it holds an array). */
  value: (name: string) => string
  /** List value of a field (for array fields like `fields[]`). */
  valueList: (name: string) => string[]
  setValue: (name: string, v: FieldValue) => void
  error: (name: string) => string | undefined
}

/** A config tab (from the plugin's own module OR contributed by another module). */
export type PluginTab = {
  id: string
  title: string
  icon?: string // a FontAwesome class, e.g. 'fa fa-cog' (loaded in the BO)
  order?: number // lower = earlier; the plugin's own tab(s) default to 0
  /** Owning Melis module. When set, the tab is shown only while that module is ACTIVE — mirrors the
   *  legacy `isModuleLoaded(...)` gate. Used by GLOBAL tabs contributed by another module. */
  module?: string
  Component: (p: { ctx: PluginTabContext }) => ReactNode
}

/**
 * Registry of config tabs per plugin CLASS name. GENERIC + MODULAR: a plugin's own module registers its
 * tab(s), and ANY other module can contribute more tabs to the SAME plugin — its tab source lives in that
 * module and is imported into the SPA build (mirrors the legacy `modal_form` config merge, but in React).
 */
export const PLUGIN_FORM_TABS: Record<string, PluginTab[]> = {}

/** Register a config tab for a plugin. Idempotent per (plugin,tab.id) so a rebuilt module doesn't dup. */
export function registerPluginTab(pluginName: string, tab: PluginTab): void {
  const list = (PLUGIN_FORM_TABS[pluginName] ||= [])
  const i = list.findIndex((t) => t.id === tab.id)
  if (i >= 0) list[i] = tab
  else list.push(tab)
}

/**
 * GLOBAL tabs contributed to EVERY plugin's config (e.g. melis-cache-internal's "Cache partiel" tab, which
 * the legacy config listener injects into every plugin's `modal_form`). Appended after the plugin's own
 * tabs. They only surface on plugins that ALREADY have a native React form — plugins on the legacy iframe
 * fallback keep showing these tabs the legacy way (via createOptionsForms), so nothing is duplicated.
 */
export const GLOBAL_PLUGIN_TABS: PluginTab[] = []

/** Register a config tab shown on EVERY plugin (idempotent per tab.id). Give it a high `order` to sit last. */
export function registerGlobalPluginTab(tab: PluginTab): void {
  const i = GLOBAL_PLUGIN_TABS.findIndex((t) => t.id === tab.id)
  if (i >= 0) GLOBAL_PLUGIN_TABS[i] = tab
  else GLOBAL_PLUGIN_TABS.push(tab)
}

// ── Active Melis modules ───────────────────────────────────────────────────────
// A module-gated tab (PluginTab.module) must vanish when its module is disabled — same as the legacy
// listener's `isModuleLoaded()` gate. The BO's /react-api/react-modules lists a module ONLY while it is
// active (and ships a brick), so its `module` values are our active-module set. Fetched once (cached),
// re-fetchable on demand; the shell has no window bridge for this, so the brick fetches it itself.
let _activeModules: Set<string> | null = null
let _activeInFlight: Promise<void> | null = null
const _activeListeners = new Set<() => void>()

function fetchActiveModules(): Promise<void> {
  if (_activeInFlight) return _activeInFlight
  _activeInFlight = (async () => {
    try {
      const r = await fetch('/melis/react-api/react-modules', { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      const j: any = await r.json().catch(() => null)
      const arr: any[] = Array.isArray(j) ? j : (j?.data ?? j?.bricks ?? [])
      _activeModules = new Set(arr.map((m) => String(m?.module ?? '')).filter(Boolean))
    } catch {
      _activeModules = new Set()
    }
    _activeListeners.forEach((l) => l())
  })()
  return _activeInFlight
}

/** The set of active Melis modules (null until loaded). Fetches once on first use. */
function useActiveModules(): Set<string> | null {
  const [, force] = useReducer((x: number) => x + 1, 0)
  useEffect(() => {
    _activeListeners.add(force)
    void fetchActiveModules()
    return () => { _activeListeners.delete(force) }
  }, [])
  return _activeModules
}

/** Whether a plugin has at least one native React config tab (→ use the React form, not the iframe).
 *  Global tabs do NOT count: they only augment plugins that already have their own React form. */
export function hasPluginForm(pluginName: string): boolean {
  return (PLUGIN_FORM_TABS[pluginName]?.length ?? 0) > 0
}

/** Tabs for a plugin, ordered (own tabs first via `order`, then global tabs, all sorted by `order`). */
function tabsFor(pluginName: string): PluginTab[] {
  return (PLUGIN_FORM_TABS[pluginName] || []).concat(GLOBAL_PLUGIN_TABS).slice().sort((a, b) => (a.order ?? 0) - (b.order ?? 0))
}

/**
 * The generic TABBED plugin config form. Reads the plugin's tabs from the registry, shows a tab bar
 * (title + icon), keeps ONE shared `values` map across all tabs, and a single Save/Cancel footer that
 * submits every field. ALL tabs stay mounted (inactive ones hidden) so their fields prefill and are
 * included in the save even if never opened — same semantics as the legacy multi-tab modal.
 */
export function PluginTabbedForm(props: PluginFormProps & { tabs?: PluginTab[] }) {
  const activeModules = useActiveModules()
  // Tab source: an explicit list (the runtime SchemaForm passes schema-derived tabs) OR the registry
  // (hand-written per-module tabs). Either way GLOBAL tabs (e.g. cache-internal) are appended + sorted.
  const source = props.tabs ?? (PLUGIN_FORM_TABS[props.pluginName] || [])
  // Hide a module-gated tab when its module is disabled (legacy `isModuleLoaded` parity). While the
  // active-module set is still loading (null) we keep all tabs — for an ACTIVE module that avoids any
  // flash; a DISABLED module's tab shows for the fetch's brief moment, then drops.
  const tabs = source.concat(GLOBAL_PLUGIN_TABS).slice()
    .sort((a, b) => (a.order ?? 0) - (b.order ?? 0))
    .filter((t) => !t.module || activeModules === null || activeModules.has(t.module))
  const { saving, fieldErrors, message, submit } = useSubmit(props)
  const [values, setValues] = useState<Record<string, FieldValue>>({})
  const [active, setActive] = useState(0)

  const ctx: PluginTabContext = {
    props,
    value: (n) => { const v = values[n]; return Array.isArray(v) ? '' : (v ?? '') },
    valueList: (n) => { const v = values[n]; return Array.isArray(v) ? v : (v ? [v] : []) },
    setValue: (n, v) => setValues((s) => (s[n] === v ? s : { ...s, [n]: v })),
    error: (n) => fieldErrors[n],
  }

  const tabBtn = (activeTab: boolean): React.CSSProperties => ({
    display: 'inline-flex', alignItems: 'center', gap: 6, padding: '8px 14px', border: 0, borderBottom: '2px solid ' + (activeTab ? props.accent : 'transparent'),
    background: 'transparent', color: activeTab ? 'var(--color-foreground,#111827)' : 'var(--color-muted-foreground,#6b7280)',
    fontSize: 13, fontWeight: activeTab ? 700 : 500, cursor: 'pointer', marginBottom: -1,
  })

  return (
    <div>
      {/* Tab bar — always shown (consistent + signals that modules can add tabs here). */}
      <div role="tablist" style={{ display: 'flex', gap: 2, borderBottom: '1px solid var(--color-border,#e5e7eb)', margin: '0 0 14px', flexWrap: 'wrap' }}>
        {tabs.map((t, i) => (
          <button key={t.id} type="button" role="tab" data-testid={`plugin-tab-${t.id}`} aria-selected={i === active}
            onClick={() => setActive(i)} style={tabBtn(i === active)}>
            {t.icon ? <i className={t.icon} aria-hidden="true" /> : null}{t.title}
          </button>
        ))}
      </div>

      <FormMessage message={message} />

      {tabs.map((t, i) => (
        <div key={t.id} data-testid={`plugin-tabpanel-${t.id}`} style={{ display: i === active ? 'block' : 'none' }}>
          <t.Component ctx={ctx} />
        </div>
      ))}

      <FormFooter saving={saving} accent={props.accent} onCancel={props.onCancel} onSave={() => submit(values)} />
    </div>
  )
}

// -------------------------------------------------- reusable tab fields ---
// ctx-aware, SELF-PREFILLING field components so a plugin tab is just a list of them. Each prefills its
// own field from props.rawXml on mount (all tabs stay mounted → every field prefills + is saved).

/** Prefill a field's value once on mount. Instantly from the page XML fragment (props.rawXml), then
 *  corrected to the SERVER-resolved value (fieldValues) — which carries template params / front-config
 *  defaults the page XML lacks, so HARDCODED plugins (menu…) prefill their real current value. */
export function usePrefill(ctx: PluginTabContext, name: string) {
  useEffect(() => {
    let cancelled = false
    const rawv = readTag(ctx.props.rawXml, name)
    if (rawv) ctx.setValue(name, rawv)
    fetchFieldOptions({ idPage: ctx.props.idPage, module: ctx.props.module, pluginName: ctx.props.pluginName, pluginId: ctx.props.pluginId }).then((o) => {
      if (cancelled) return
      const sv = o.fieldValues[name]
      if (sv !== undefined && sv !== '') ctx.setValue(name, sv)
    })
    return () => { cancelled = true }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])
}

/** A plain text (or number) input bound to ctx[name]. */
export function TextField({ ctx, name, label, hint, placeholder, type = 'text' }: { ctx: PluginTabContext; name: string; label: string; hint?: string; placeholder?: string; type?: string }) {
  usePrefill(ctx, name)
  return (
    <Field label={label} error={ctx.error(name)} hint={hint}>
      <input data-testid={`field-${name}`} type={type} value={ctx.value(name)} placeholder={placeholder}
        onChange={(e) => ctx.setValue(name, e.target.value)} style={inputStyle} />
    </Field>
  )
}

// mm/dd/yyyy (the format Melis plugins store/expect) <-> yyyy-mm-dd (native <input type=date>).
function mdyToIso(v: string): string {
  const m = (v || '').match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/)
  return m ? `${m[3]}-${m[1].padStart(2, '0')}-${m[2].padStart(2, '0')}` : ''
}
function isoToMdy(v: string): string {
  const m = (v || '').match(/^(\d{4})-(\d{2})-(\d{2})$/)
  return m ? `${m[2]}/${m[3]}/${m[1]}` : ''
}

/** A date field — native date picker in the UI, stored in the plugin's `mm/dd/yyyy` format (ctx[name]). */
export function DateField({ ctx, name, label, hint }: { ctx: PluginTabContext; name: string; label: string; hint?: string }) {
  usePrefill(ctx, name)
  return (
    <Field label={label} error={ctx.error(name)} hint={hint}>
      <input data-testid={`field-${name}`} type="date" value={mdyToIso(ctx.value(name))}
        onChange={(e) => ctx.setValue(name, e.target.value ? isoToMdy(e.target.value) : '')} style={inputStyle} />
    </Field>
  )
}

/** A hidden pass-through field (kept in the saved values, no UI). */
export function HiddenField({ ctx, name }: { ctx: PluginTabContext; name: string }) {
  usePrefill(ctx, name)
  return null
}

/** A select bound to ctx[name] with the given options. */
export function SelectField({ ctx, name, label, options, hint, empty }: { ctx: PluginTabContext; name: string; label: string; options: Option[]; hint?: string; empty?: string }) {
  usePrefill(ctx, name)
  return (
    <Field label={label} error={ctx.error(name)} hint={hint}>
      <select data-testid={`field-${name}`} value={ctx.value(name)} onChange={(e) => ctx.setValue(name, e.target.value)} style={inputStyle}>
        <option value="">{empty ?? peT().pfChoose}</option>
        {options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
      </select>
    </Field>
  )
}

/** A select whose options are loaded from the server (fetchFieldOptions → fieldOptions[name]). */
export function RemoteSelectField({ ctx, name, label, hint, empty }: { ctx: PluginTabContext; name: string; label: string; hint?: string; empty?: string }) {
  const [options, setOptions] = useState<Option[]>([])
  useEffect(() => {
    let c = false
    fetchFieldOptions({ idPage: ctx.props.idPage, module: ctx.props.module, pluginName: ctx.props.pluginName, pluginId: ctx.props.pluginId })
      .then((o) => { if (!c) setOptions(o.fieldOptions[name] || []) })
    return () => { c = true }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [ctx.props.idPage, ctx.props.module, ctx.props.pluginName, ctx.props.pluginId, name])
  return <SelectField ctx={ctx} name={name} label={label} options={options} hint={hint} empty={empty} />
}

/** The `template_path` select — self-contained (loads template options; defaults to the sole one). */
export function TemplateField({ ctx, name = 'template_path', label, hint }: { ctx: PluginTabContext; name?: string; label?: string; hint?: string }) {
  const tr = peT()
  label = label ?? tr.template
  hint = hint ?? tr.pfTemplateHint
  usePrefill(ctx, name) // value comes from the server-resolved fieldValues (incl. defaults)
  const [options, setOptions] = useState<Option[]>([])
  useEffect(() => {
    let c = false
    fetchFieldOptions({ idPage: ctx.props.idPage, module: ctx.props.module, pluginName: ctx.props.pluginName, pluginId: ctx.props.pluginId }).then((o) => { if (!c) setOptions(o.templateOptions) })
    return () => { c = true }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [ctx.props.idPage, ctx.props.module, ctx.props.pluginName, ctx.props.pluginId])
  return (
    <Field label={label} error={ctx.error(name)} hint={hint}>
      <select data-testid={`field-${name}`} value={ctx.value(name)} onChange={(e) => ctx.setValue(name, e.target.value)} style={inputStyle}>
        <option value="">{tr.pfChooseTemplate}</option>
        {options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
      </select>
    </Field>
  )
}

/**
 * A themed checkbox that matches the platform's rights-management tool (melis-core's Radix `Checkbox`):
 * a 16px rounded box, `--color-primary` fill + white check when on. Inline-styled with the SAME theme CSS
 * vars so it works inside the standalone melis-cms brick (no Tailwind pipeline here) and follows dark mode.
 * A visually-hidden native <input> drives state + keyboard focus for accessibility.
 */
export function CheckBox({ checked, disabled, onChange, title, label }: { checked: boolean; disabled?: boolean; onChange: (v: boolean) => void; title?: string; label?: ReactNode }) {
  const [focus, setFocus] = useState(false)
  return (
    <label title={title} style={{ display: 'inline-flex', alignItems: 'center', gap: 8, cursor: disabled ? 'not-allowed' : 'pointer', opacity: disabled ? .5 : 1 }}>
      <input
        type="checkbox" checked={checked} disabled={disabled}
        onChange={(e) => onChange(e.target.checked)}
        onFocus={() => setFocus(true)} onBlur={() => setFocus(false)}
        style={{ position: 'absolute', width: 1, height: 1, padding: 0, margin: -1, overflow: 'hidden', clip: 'rect(0 0 0 0)', whiteSpace: 'nowrap', border: 0 }}
      />
      <span aria-hidden style={{
        width: 16, height: 16, flex: '0 0 auto', borderRadius: 4, display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
        border: `1px solid ${checked ? 'var(--color-primary,#2563eb)' : 'var(--color-input,#e5e7eb)'}`,
        background: checked ? 'var(--color-primary,#2563eb)' : 'var(--color-card,#fff)',
        boxShadow: focus ? '0 0 0 2px color-mix(in srgb, var(--color-ring,#2563eb) 40%, transparent)' : '0 1px 1px rgba(0,0,0,.04)',
        transition: 'background .12s, border-color .12s, box-shadow .12s',
      }}>
        {checked && (
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary-foreground,#fff)" strokeWidth={3.5} strokeLinecap="round" strokeLinejoin="round">
            <polyline points="20 6 9 17 4 12" />
          </svg>
        )}
      </span>
      {label != null ? <span style={{ fontSize: 13, color: 'var(--color-foreground,#111827)' }}>{label}</span> : null}
    </label>
  )
}

/**
 * A ctx-bound, self-prefilling CHECKBOX field (label + themed CheckBox), posting '1'/'0' so the plugin /
 * a listener reads a truthy/falsy value. Prefill: checked when the server-resolved form rendered the box
 * `checked` (its name is present in fieldValues) or the page XML holds a truthy `<name>`.
 */
export function CheckboxField({ ctx, name, label, boxLabel, hint }: { ctx: PluginTabContext; name: string; label: string; boxLabel?: string; hint?: string }) {
  useEffect(() => {
    let cancelled = false
    const raw = readTag(ctx.props.rawXml, name)
    if (raw) ctx.setValue(name, raw === '0' ? '0' : '1')
    fetchFieldOptions({ idPage: ctx.props.idPage, module: ctx.props.module, pluginName: ctx.props.pluginName, pluginId: ctx.props.pluginId }).then((o) => {
      if (cancelled) return
      // parseFieldValues only emits a checkbox's name when it was rendered `checked` → presence = on.
      if (Object.prototype.hasOwnProperty.call(o.fieldValues, name)) ctx.setValue(name, '1')
    })
    return () => { cancelled = true }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])
  // The box carries its OWN clear, actionable label next to it (the section `label` is the heading) —
  // mirrors the legacy layout where the checkbox has a distinct inline caption.
  return (
    <Field label={label} error={ctx.error(name)} hint={hint}>
      <CheckBox checked={ctx.value(name) === '1'} label={boxLabel ?? label} title={boxLabel ?? label} onChange={(v) => ctx.setValue(name, v ? '1' : '0')} />
    </Field>
  )
}

/**
 * A `fields[]` / `required_fields[]` GRID (e.g. prospects' "Field list"): one row per configurable field
 * with a "show" toggle, a "mandatory" toggle (only when shown) and DRAG-AND-DROP reordering (grab the ⠿
 * handle). Loads the rows from the server (fetchFieldOptions().fieldList) and writes two ARRAYS into the
 * shared values on change: `fields` (shown field names, IN ORDER) and `required_fields` (mandatory ones).
 * The plugin's savePluginConfigToXml() reads `$post['fields']` / `$post['required_fields']`.
 */
export function FieldListField({ ctx, label, hint }: { ctx: PluginTabContext; label?: string; hint?: string }) {
  const tr = peT()
  label = label ?? tr.pfFormFields
  const [rows, setRows] = useState<FieldListRow[]>([])
  const [dragIdx, setDragIdx] = useState<number | null>(null)
  const [overIdx, setOverIdx] = useState<number | null>(null)
  const apply = (rs: FieldListRow[]) => {
    setRows(rs)
    ctx.setValue('fields', rs.filter((r) => r.shown).map((r) => r.name))
    ctx.setValue('required_fields', rs.filter((r) => r.shown && r.required).map((r) => r.name))
  }
  useEffect(() => {
    let c = false
    fetchFieldOptions({ idPage: ctx.props.idPage, module: ctx.props.module, pluginName: ctx.props.pluginName, pluginId: ctx.props.pluginId }).then((o) => { if (!c) apply(o.fieldList) })
    return () => { c = true }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])
  const setRow = (name: string, patch: Partial<FieldListRow>) => apply(rows.map((r) => (r.name === name ? { ...r, ...patch } : r)))
  // Move the dragged row to the target index (insert semantics, not swap).
  const reorder = (from: number, to: number) => {
    if (from === to || from < 0 || to < 0 || from >= rows.length || to >= rows.length) return
    const rs = rows.slice(); const [x] = rs.splice(from, 1); rs.splice(to, 0, x); apply(rs)
  }
  const endDrag = () => { setDragIdx(null); setOverIdx(null) }

  const cellHead: React.CSSProperties = { fontSize: 10, fontWeight: 700, textTransform: 'uppercase', letterSpacing: .3, color: 'var(--color-muted-foreground,#6b7280)' }
  const cols = '22px 1fr 64px 74px'

  return (
    <Field label={label} error={ctx.error('fields')} hint={hint}>
      <div style={{ border: '1px solid var(--color-border,#e5e7eb)', borderRadius: 8, overflow: 'hidden' }}>
        <div style={{ display: 'grid', gridTemplateColumns: cols, gap: 8, alignItems: 'center', padding: '7px 10px', background: 'color-mix(in srgb, var(--color-muted-foreground,#6b7280) 7%, transparent)' }}>
          <span /><span style={cellHead}>{tr.pfColField}</span>
          <span style={{ ...cellHead, textAlign: 'center' }}>{tr.pfColShow}</span><span style={{ ...cellHead, textAlign: 'center' }}>{tr.pfColRequired}</span>
        </div>
        {rows.length === 0 && <div style={{ padding: 10, fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.pfNoField}</div>}
        {rows.map((r, i) => (
          <div
            key={r.name}
            data-testid={`fieldrow-${r.name}`}
            onDragOver={(e) => { e.preventDefault(); if (overIdx !== i) setOverIdx(i) }}
            onDrop={(e) => { e.preventDefault(); const from = Number(e.dataTransfer.getData('text/plain')); reorder(Number.isNaN(from) ? (dragIdx ?? -1) : from, i); endDrag() }}
            style={{
              display: 'grid', gridTemplateColumns: cols, gap: 8, alignItems: 'center', padding: '6px 10px',
              borderTop: '1px solid var(--color-border,#e5e7eb)', opacity: dragIdx === i ? .4 : r.shown ? 1 : .6,
              boxShadow: overIdx === i && dragIdx !== null && dragIdx !== i ? 'inset 0 2px 0 var(--color-primary,#2563eb)' : undefined,
              background: dragIdx === i ? 'color-mix(in srgb, var(--color-muted-foreground,#6b7280) 8%, transparent)' : undefined,
            }}
          >
            <span
              draggable
              title={tr.pfDragToReorder}
              onDragStart={(e) => { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', String(i)); setDragIdx(i) }}
              onDragEnd={endDrag}
              style={{ cursor: 'grab', color: 'var(--color-muted-foreground,#9ca3af)', fontSize: 13, lineHeight: 1, textAlign: 'center', userSelect: 'none' }}
            >⠿</span>
            <span style={{ fontSize: 13, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{r.label}</span>
            <span style={{ display: 'inline-flex', justifyContent: 'center' }}><CheckBox checked={r.shown} title={tr.pfShowField} onChange={(v) => setRow(r.name, { shown: v, ...(v ? {} : { required: false }) })} /></span>
            <span style={{ display: 'inline-flex', justifyContent: 'center' }}><CheckBox checked={r.required} disabled={!r.shown} title={tr.pfMakeRequired} onChange={(v) => setRow(r.name, { required: v })} /></span>
          </div>
        ))}
      </div>
    </Field>
  )
}

/** A page selector bound to ctx[name] (the numeric page id) — reuses the shared PagePicker tree. */
export function PageField({ ctx, name, label, hint, placeholder }: { ctx: PluginTabContext; name: string; label: string; hint?: string; placeholder?: string }) {
  usePrefill(ctx, name)
  const id = parseInt(ctx.value(name), 10)
  return (
    <Field label={label} error={ctx.error(name)} hint={hint}>
      <div data-testid={`field-${name}`}>
        <PagePicker value={Number.isFinite(id) ? id : 0} onChange={(pid) => ctx.setValue(name, pid ? String(pid) : '')} placeholder={placeholder} />
      </div>
    </Field>
  )
}

/** A multi-line text field bound to ctx[name]. */
export function TextareaField({ ctx, name, label, hint }: { ctx: PluginTabContext; name: string; label: string; hint?: string }) {
  usePrefill(ctx, name)
  return (
    <Field label={label} error={ctx.error(name)} hint={hint}>
      <textarea data-testid={`field-${name}`} value={ctx.value(name)} onChange={(e) => ctx.setValue(name, e.target.value)}
        style={{ ...inputStyle, minHeight: 90, resize: 'vertical', fontFamily: 'inherit' }} />
    </Field>
  )
}

// =============================================================================
// RUNTIME schema-driven form — the no-build path for plugins created LIVE.
// A plugin's config is DERIVED (server-side) from its own createOptionsForms() into a JSON schema
// (edition/plugin-config/schema), and rendered here by the SAME field kit as the hand-written forms.
// So a plugin created on a live platform (no build) gets a native React config for free, while the
// legacy iframe keeps rendering the identical form (golden rule) — same declaration, two renderers.
// =============================================================================

export type SchemaField = { name: string; type: string; label: string; hint?: string; required?: boolean; value?: string; options?: Option[]; rows?: FieldListRow[] }
export type SchemaTab = { id: string; title: string; fields: SchemaField[] }

const _schemaCache = new Map<string, Promise<SchemaTab[]>>()

/** GET a plugin's declarative config schema (tabs → fields), once per plugin/page (cached promise). */
export function fetchSchema(args: { idPage: number; module: string; pluginName: string; pluginId: string }): Promise<SchemaTab[]> {
  const key = `${args.idPage}|${args.module}|${args.pluginName}|${args.pluginId}`
  let p = _schemaCache.get(key)
  if (!p) {
    p = (async () => {
      try {
        const q = `idPage=${args.idPage}&module=${encodeURIComponent(args.module)}&pluginName=${encodeURIComponent(args.pluginName)}&pluginId=${encodeURIComponent(args.pluginId)}`
        const r = await fetch(`/melis/react-api/cms-page/edition/plugin-config/schema?${q}`, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        const res: any = await r.json().catch(() => ({}))
        return (res?.data?.tabs || []) as SchemaTab[]
      } catch {
        return []
      }
    })()
    _schemaCache.set(key, p)
  }
  return p
}

/** Render one schema field via the shared kit (type → component). template_path always gets TemplateField. */
function SchemaFieldView({ f, ctx }: { f: SchemaField; ctx: PluginTabContext }) {
  const label = f.label || f.name
  if (f.name === 'template_path') return <TemplateField ctx={ctx} label={label} hint={f.hint} />
  switch (f.type) {
    case 'select':   return <RemoteSelectField ctx={ctx} name={f.name} label={label} hint={f.hint} />
    case 'page':     return <PageField ctx={ctx} name={f.name} label={label} hint={f.hint} placeholder={peT().pickPage} />
    case 'date':     return <DateField ctx={ctx} name={f.name} label={label} hint={f.hint} />
    case 'number':   return <TextField ctx={ctx} name={f.name} label={label} hint={f.hint} type="number" />
    case 'textarea': return <TextareaField ctx={ctx} name={f.name} label={label} hint={f.hint} />
    case 'checkbox': return <CheckboxField ctx={ctx} name={f.name} label={label} hint={f.hint} />
    case 'fieldlist':return <FieldListField ctx={ctx} label={label} hint={f.hint} />
    default:         return <TextField ctx={ctx} name={f.name} label={label} hint={f.hint} />
  }
}

function SchemaFields({ fields, ctx }: { fields: SchemaField[]; ctx: PluginTabContext }) {
  return (<div>{fields.map((f) => <SchemaFieldView key={f.name} f={f} ctx={ctx} />)}</div>)
}

/**
 * The runtime schema-driven config form. Fetches the plugin's schema, turns each tab into a PluginTab
 * (whose Component renders its fields via the kit) and hands them to PluginTabbedForm — so it inherits the
 * exact same tabs UX, shared values, single Save (byte-compatible XML) and GLOBAL tabs. If no schema is
 * available it calls onUnavailable() so the caller can fall back to the legacy iframe.
 */
export function SchemaForm(props: PluginFormProps & { onUnavailable?: () => void }) {
  const [tabs, setTabs] = useState<PluginTab[] | null>(null)
  useEffect(() => {
    let cancelled = false
    fetchSchema(props).then((schemaTabs) => {
      if (cancelled) return
      if (!schemaTabs.length) { props.onUnavailable?.(); return }
      setTabs(schemaTabs.map((t, i): PluginTab => ({
        id: t.id, title: t.title, order: i,
        Component: ({ ctx }) => <SchemaFields fields={t.fields} ctx={ctx} />,
      })))
    }).catch(() => { if (!cancelled) props.onUnavailable?.() })
    return () => { cancelled = true }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [props.idPage, props.module, props.pluginName, props.pluginId])

  if (!tabs) return <div style={{ padding: 20, fontSize: 13, color: 'var(--color-muted-foreground,#6b7280)' }}>{peT().loading}</div>
  return <PluginTabbedForm {...props} tabs={tabs} />
}
