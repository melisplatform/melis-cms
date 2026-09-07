import { useCallback, useEffect, useRef, useState } from 'react'
import { apiGet, apiPost } from './PageTabs'
import { hasPluginForm, PluginTabbedForm, SchemaForm, PluginFormBoundary } from './PluginForms'
import { PagePicker } from './PagePicker'
import { ViewToggle } from './ViewToggle'
import { peT } from './page-editor-i18n'

/**
 * EditionCanvas — vue « New » de l'onglet Édition (evo/page-edition-react), path C.
 *
 * Canvas = notre RENDU PROPRE (`/edition/render`, aperçu live, template+CSS, sans JS legacy). Les
 * contrôles d'édition sont PILOTÉS PAR LE MODÈLE (`edition/document`) et vivent dans un PANNEAU React
 * à droite — pas dans le DOM legacy (certains plugins, ex. blockSection, n'ont AUCUN élément
 * adressable dans le canvas ; seul le modèle couvre tous les types de façon fiable).
 *
 * Blocs (par cellule) : réordonner ↑/↓, largeurs D/T/M, ajouter/retirer — patch DOM LIVE + persistés
 * via ops stateless `edition/save` (setZoneRefs/setWidths/addPlugin), sans rechargement.
 *
 * V2 layout (drag-and-drop) : chaque zone/cellule a un SÉLECTEUR DE DISPOSITION (schémas
 * `doc.layouts`). Appliquer un schéma → op `applyLayout` → la zone se scinde en cellules imbriquées
 * `<zone>_1.._N` (physiquement dans le XML, comme le legacy) → l'iframe du canvas se recharge pour
 * afficher les colonnes. Chaque cellule est elle-même reconfigurable (imbrication). Aucun fichier
 * legacy modifié ; le XML produit est byte-compatible avec l'éditeur Old.
 */

type Ref = { id?: string; module?: string; name?: string }
type DocZone = { kind: string; id: string | null; tag?: string; raw?: string; template?: string; refs?: Ref[]; zones?: DocZone[]; attrs?: Record<string, string> }
type Layout = { key: string; template: string; cols: number; icon: string }
type Doc = { idPage: number; source: string | null; namespace: string; nodes: DocZone[]; layouts?: Layout[]; pluginTitles?: Record<string, string>; pluginThumbs?: Record<string, string> }
type Cell = { id: string; template: string; refs: { id: string; label: string; mini?: boolean }[]; cells: Cell[] }
type PalettePlugin = { module: string; name: string; title: string; description: string; thumbnail: string; type: string }
type PaletteGroup = { id: string; title: string; plugins: PalettePlugin[] }
type PaletteModule = { key: string; label: string; groups: PaletteGroup[] }
type PaletteSection = { key: string; label: string; modules: PaletteModule[] }
type Palette = { sections: PaletteSection[] }

const DEFAULT_TPL = 'MelisFront/dnd-default-tpl'
const TINY_BASE = '/MelisCore/js/library/tinymce' // the TinyMCE build the legacy Old editor uses (v6.7.0)

function notify(kind: 'ok' | 'ko', title: string, message: string) {
  window.postMessage({ __melisNotif: true, kind, title, message }, '*')
}
/** Libellé lisible d'un bloc à partir de son nom de plugin. */
function label(r: Ref): string {
  const n = r.name || r.id || '?'
  const mini = n.match(/^MiniTemplatePlugin_(.+?)_[^_]+$/)
  if (mini) return mini[1].replace(/-/g, ' ')
  return n.replace(/^MelisFront/, '').replace(/Plugin$/, '')
}

/** Build the editable recursive cell tree from a document zone node. `titles` = refId → real plugin name
 *  (the legacy melis.name, from the document endpoint) so the panel shows names, not the technical class. */
function toCell(n: DocZone, titles: Record<string, string>): Cell {
  return {
    id: n.id as string,
    template: n.template || '',
    refs: (n.refs || []).filter((r) => r.id).map((r) => ({ id: r.id as string, label: titles[r.id as string] || label(r), mini: r.module === 'MelisMiniTemplate' || (r.name || '').startsWith('MiniTemplatePlugin_') })),
    cells: (n.zones || []).filter((z) => z.id).map((z) => toCell(z, titles)),
  }
}
/** Immutably replace the cell with id === $id via $fn, anywhere in the tree. */
function mapCell(cells: Cell[], id: string, fn: (c: Cell) => Cell): Cell[] {
  return cells.map((c) => (c.id === id ? fn(c) : { ...c, cells: mapCell(c.cells, id, fn) }))
}
function findCell(cells: Cell[], id: string): Cell | null {
  for (const c of cells) { if (c.id === id) return c; const r = findCell(c.cells, id); if (r) return r }
  return null
}

// Marketplace SECTION accent colours — same values as MelisCore's getMelisSectionIcons() so the add
// palette shows the exact Melis section logos/colours the legacy plugin menu does.
const SECTION_COLORS: Record<string, string> = {
  MelisCore: '#ee6622', MelisCms: '#69b344', MelisMarketing: '#70469c',
  MelisCommerce: '#2780c4', CustomProjects: '#676767', __all__: '#9ca3af',
}
/** The Melis section logo (rounded square + Melis mark), coloured per marketplace section. */
function MelisSectionIcon({ sectionKey, size = 22 }: { sectionKey: string; size?: number }) {
  const fill = SECTION_COLORS[sectionKey] || '#ff0000' // unknown section → red, like the legacy helper
  return (
    <svg width={size} height={size} viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" style={{ display: 'block', flex: '0 0 auto' }} aria-hidden="true">
      <rect fill={fill} x=".07" y=".13" width="79.86" height="79.86" rx="15.36" ry="15.36" />
      <g>
        <path fill="#FFFFFF" d="M57.78,15.87c-3.47,0-6.29,2.81-6.29,6.29v35.85c0,3.47,2.81,6.29,6.29,6.29s6.29-2.81,6.29-6.29V22.16c0-3.47-2.81-6.29-6.29-6.29Z" />
        <path fill="#FFFFFF" d="M27.79,19.16c-1.62-3.07-5.43-4.24-8.5-2.62-3.07,1.62-4.24,5.43-2.62,8.5l19.01,35.93c1.62,3.07,5.43,4.24,8.5,2.62,3.07-1.62,4.24-5.43,2.62-8.5L27.79,19.16Z" />
        <circle fill="#FFFFFF" cx="22.36" cy="57.88" r="6.43" />
      </g>
    </svg>
  )
}
/** Section display label — matches the legacy special-case for CustomProjects. */
function sectionLabel(key: string, display: string): string {
  if (key === 'CustomProjects') return 'Custom / Projects'
  return display || key
}
/** Short chip label for the marketplace-style section filter (MelisCms → Cms, CustomProjects → Custom). */
function shortSectionLabel(key: string): string {
  if (key === 'CustomProjects') return 'Custom'
  return key.replace(/^Melis/, '') || key
}
/** Filter the palette tree to sections/modules/groups that still hold a matching plugin. */
function filterPalette(sections: PaletteSection[], q: string): PaletteSection[] {
  if (!q) return sections
  const hit = (p: PalettePlugin) => p.title.toLowerCase().includes(q) || p.name.toLowerCase().includes(q) || (p.description || '').toLowerCase().includes(q)
  return sections
    .map((s) => ({ ...s, modules: s.modules
      .map((m) => ({ ...m, groups: m.groups.map((g) => ({ ...g, plugins: g.plugins.filter(hit) })).filter((g) => g.plugins.length) }))
      .filter((m) => m.groups.length) }))
    .filter((s) => s.modules.length)
}

/** Find a plugin ref's {module,name} anywhere in the document zones (data nodes don't carry them). */
function findPluginRef(nodes: DocZone[] | undefined, refId: string): { module: string; name: string } | null {
  for (const n of nodes || []) {
    if (n.kind !== 'zone') continue
    for (const r of n.refs || []) if (r.id === refId) return { module: r.module || '', name: r.name || '' }
    const deep = findPluginRef(n.zones, refId)
    if (deep) return deep
  }
  return null
}

/** Extract a plugin node's text/HTML body (the CDATA) from its raw XML. */
function extractContent(raw: string): string {
  try {
    const p = new DOMParser().parseFromString(raw, 'application/xml')
    if (!p.querySelector('parsererror')) return p.documentElement?.textContent ?? ''
  } catch { /* fall through */ }
  const m = raw.match(/<!\[CDATA\[([\s\S]*?)\]\]>/)
  return m ? m[1] : ''
}

/**
 * Outline a zone in the canvas iframe. Outlines the INNER `.melis-dragdropzone` content element
 * (hugs the actual drop area) rather than the outer `.melis-dragdropzone-container` (which reserves
 * a ~25px strip for the — hidden — edit chrome above the content).
 */
function outlineZoneInCanvas(d: Document, zoneId: string): void {
  d.querySelectorAll('.melis-react-sel').forEach((el) => el.classList.remove('melis-react-sel'))
  const esc = zoneId.replace(/["\\]/g, '\\$&')
  const el = (d.querySelector(`.melis-dragdropzone[data-dragdropzone-id="${esc}"]`)
    || d.querySelector(`[data-dragdropzone-id="${esc}"]`)) as HTMLElement | null
  if (el) { el.classList.add('melis-react-sel'); el.scrollIntoView({ block: 'nearest', behavior: 'smooth' }) }
}

/** Outline a single plugin/block by its ref id (its outer wrapper `<page>_<mod>_<name>_<refId>`). */
function outlineBlockInCanvas(d: Document, refId: string): void {
  d.querySelectorAll('.melis-react-sel').forEach((el) => el.classList.remove('melis-react-sel'))
  const esc = refId.replace(/["\\]/g, '\\$&')
  const cands = Array.from(d.querySelectorAll(`[id$="_${esc}"]`)) as HTMLElement[]
  const wrap = cands.find((e) => /plugin-width|melis-ui-outlined/.test(e.className)) || cands[0]
  if (wrap) { wrap.classList.add('melis-react-sel'); wrap.scrollIntoView({ block: 'nearest', behavior: 'smooth' }) }
}

// Responsive preview widths for the top toolbar's "Display" (Affichage) button — mirrors the legacy
// desktop/tablet/mobile device preview, but resizes THIS canvas iframe (the legacy one is hidden under it).
const DEVICE_W: Record<'desktop' | 'tablet' | 'mobile', string> = { desktop: '100%', tablet: '768px', mobile: '375px' }

// Is the current BO theme dark? Decided by the BACKGROUND luminance, NOT the accent colour: the accent
// (`--color-primary`) is the CSS keyword `red` in the light "platform" theme (and a hex/other value in
// custom themes), so an exact `=== '#dc2626'` test wrongly read light-mode as dark and served the plugin
// config iframe its dark palette on a light app. Any theme (custom brand colours included) is classified
// right from its resolved background: a probe element normalises keyword/hex/rgb to rgb, then perceived
// luminance < 128 → dark. The config iframe (its own document, no theme vars) gets `theme=light|dark`.
function isDarkTheme(): boolean {
  try {
    const cs = getComputedStyle(document.documentElement)
    const bg = (cs.getPropertyValue('--color-background') || '').trim() || getComputedStyle(document.body).backgroundColor
    const probe = document.createElement('span')
    probe.style.color = bg; probe.style.display = 'none'
    document.body.appendChild(probe)
    const rgb = getComputedStyle(probe).color
    probe.remove()
    const m = rgb.match(/[\d.]+/g)
    if (!m || m.length < 3) return false
    const [r, g, b] = m.map(Number)
    return (0.2126 * r + 0.7152 * g + 0.0722 * b) < 128
  } catch { return false }
}

export default function EditionCanvas({ idPage, device = 'desktop' }: { idPage: number; device?: 'desktop' | 'tablet' | 'mobile' }) {
  const [doc, setDoc] = useState<Doc | null>(null)
  const [tree, setTree] = useState<Cell[]>([])
  const [layouts, setLayouts] = useState<Layout[]>([])
  const [err, setErr] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)
  const [nonce, setNonce] = useState(0)
  const [blockW, setBlockW] = useState<Record<string, { d: string; t: string; m: string }>>({}) // refId -> responsive widths
  const [picker, setPicker] = useState<{ cellId: string; x: number; y: number } | null>(null) // open layout popover
  const [openWidth, setOpenWidth] = useState<string | null>(null) // block whose responsive-width panel is deployed
  const [confirmRemove, setConfirmRemove] = useState<{ zoneId: string; refId: string; label: string } | null>(null) // remove-plugin confirm
  const [panelCollapsed, setPanelCollapsed] = useState(false) // structure panel collapsed to a thin bar
  const [isMobile, setIsMobile] = useState(false) // real viewport is phone-narrow → panel becomes a drawer
  const [selected, setSelected] = useState<{ zoneId: string; refId: string | null } | null>(null) // canvas→panel locate
  const [config, setConfig] = useState<{ zoneId: string; ref: { id: string; label: string }; node: DocZone | null; module: string; pluginName: string; tag: string; useIframe: boolean; v: number } | null>(null) // plugin config modal
  const [catalog, setCatalog] = useState<Palette | null>(null) // addable-plugins palette (lazy)
  const [pluginPicker, setPluginPicker] = useState<{ cellId: string } | null>(null) // "+" add-plugin modal
  const [pickerQuery, setPickerQuery] = useState('')
  const [pickerSection, setPickerSection] = useState<string | null>(null) // marketplace-style section filter
  const [pagePicker, setPagePicker] = useState<{ value: string; source: Window } | null>(null) // page-select bridge for the config iframe
  const iframeRef = useRef<HTMLIFrameElement | null>(null)
  const panelRef = useRef<HTMLDivElement | null>(null)
  const [accent, setAccent] = useState<string>('#dc2626') // theme primary: red (platform/light) / blue (studio/dark)
  const [dark, setDark] = useState<boolean>(false) // BO theme is dark — from background luminance (see isDarkTheme)
  const accentRef = useRef('#dc2626')
  const selectedRef = useRef<{ zoneId: string; refId: string | null } | null>(null)
  const editInlineRef = useRef<((zoneId: string, refId: string) => void) | null>(null)
  const injectControlsRef = useRef<(() => void) | null>(null) // (re)inject the in-canvas reorder arrows
  const tinyConfigsRef = useRef<Record<string, any> | null>(null) // the real Melis tinymce configs by type
  const docEmptyRef = useRef(false)   // the fetched document had NO zones (fresh, unsaved page)
  const seedTriedRef = useRef(false)  // guard: only seed a page's template zones once
  const maybeSeedZonesRef = useRef<(() => void) | null>(null) // called from onFrameLoad once the canvas is up

  // On a phone-narrow viewport the structure panel can't sit next to the canvas (360px would eat the
  // whole screen and there's nowhere to close it). Switch it to a right-side DRAWER: full page preview
  // behind, panel slides over it, closable — you drive order + configs from the drawer. Start closed on
  // mobile so the editor isn't covered on open.
  useEffect(() => {
    const mq = window.matchMedia('(max-width: 767px)')
    const apply = (m: boolean) => { setIsMobile(m); setPanelCollapsed(m) }
    apply(mq.matches)
    const on = (e: MediaQueryListEvent) => apply(e.matches)
    mq.addEventListener('change', on)
    return () => mq.removeEventListener('change', on)
  }, [])

  // `_r=${nonce}` busts the browser cache on each reload — otherwise the iframe re-fetches the SAME
  // url and the browser serves the STALE render (edits look like they didn't apply).
  // `vw` bakes the device-preview viewport width into the render (mobile 375 / tablet 768 / desktop 0):
  // changing the device changes the src → the iframe RELOADS at the right viewport → deterministic reflow
  // (resizing an already-loaded iframe doesn't relayout reliably across browsers).
  const vw = device === 'mobile' ? 375 : device === 'tablet' ? 768 : 0
  const renderSrc = `/melis/react-api/cms-page/edition/render?idPage=${idPage}&_r=${nonce}&vw=${vw}`

  const onFrameLoad = useCallback(() => {
    const d = iframeRef.current?.contentDocument
    if (!d) return
    let st = d.getElementById('melis-react-hl') as HTMLStyleElement | null
    if (!st) { st = d.createElement('style'); st.id = 'melis-react-hl'; d.head?.appendChild(st) }
    st.textContent =
      '.melis-react-hl{outline:3px solid var(--melis-accent,#dc2626) !important;outline-offset:-3px;transition:outline .1s}'
      // Outline shown while hovering a plugin's in-canvas ⚙ config button (removed on mouse-leave).
      + '.melis-react-cfg-hl{outline:3px solid var(--melis-accent,#dc2626) !important;outline-offset:-3px}'
      + '.melis-react-sel{outline:3px solid var(--melis-accent,#dc2626) !important;outline-offset:-2px}'
      // Legacy reserves space for the (now-hidden) tools box by shifting the zone content down with
      // `position:relative; top:25px`. That creates a dead strip ABOVE the content AND makes it overflow
      // the container's bottom by 25px (so a parent/container outline starts too low & ends too short).
      // Neutralise it → content aligns with its container: outlines hug top-to-bottom for leaf AND parent.
      + '.dnd-layout-indicator,.dnd-layout-buttons,.melis-plugin-tools-box,.melis-plugin-indicator{display:none !important}'
      + '.melis-dragdropzone{top:0 !important}'
      // Keep the zone design (its background/border/"DRAG & DROP ZONE" placeholder = the drop-zone cue).
      // A stored/explicit height on an editable block (from resize) balloons a short/added block into empty
      // space; force content-height so a block hugs its content (the zone keeps its own min-height).
      + '.html-editable,.melis-editable,.melis-ui-outlined{height:auto !important;min-height:0 !important}'
      // The edit chrome floats each plugin wrapper (`.melis-dragdropzone .melis-ui-outlined{float:left}`
      // in plugin.melisdragdropzone.css). A float with `width:auto` sizes to its MAX-CONTENT — i.e. the
      // text laid out on ONE line — so a full-width block (e.g. a mini-template with no plugin-width class)
      // balloons to ~1080px and never wraps → the device-preview never reflows, content just overflows the
      // narrow frame. Capping the float at the container width makes the content wrap = reflow. Doesn't
      // touch plugins with an explicit width (50% etc. stay < 100%), so multi-column desktop is unchanged.
      // The legacy editor avoids this by writing an explicit px width via its (stripped-here) resize JS.
      + '.melis-ui-outlined{max-width:100% !important;box-sizing:border-box}'
      // …but `max-width` only caps the BOX; a shrink-to-fit float still lays its CONTENT out at max-content
      // (text on one line) and overflows/clips. The tag plugins (html/textarea/media/mini-template) carry
      // NO `plugin-width-*` class (unlike module plugins, which get plugin-width-*-100 = width:100% and so
      // already reflow), so give THOSE an explicit width:100% → content wraps to the container = reflow.
      // Scoped with :not([class*=plugin-width]) so a plugin resized to 50% (which owns a plugin-width class)
      // keeps its width and desktop multi-column is untouched.
      + '.melis-ui-outlined:not([class*="plugin-width"]){width:100% !important;float:none !important}'
      // In-canvas config button, injected on every module plugin (incl. hardcoded template ones).
      // Top-LEFT, always visible, accent-coloured pill with a label so it's obvious.
      + '.melis-react-has-cfg{position:relative}'
      + '.melis-react-cfg{position:absolute;top:8px;left:8px;z-index:2147483000;display:flex;'
      + 'align-items:center;justify-content:center;width:34px;height:34px;padding:0;border-radius:50%;'
      + 'border:2px solid #fff;background:var(--melis-accent,#dc2626);color:#fff;font-size:17px;line-height:1;'
      + 'cursor:pointer;box-shadow:0 3px 14px rgba(0,0,0,.35);opacity:1;transition:transform .1s,filter .1s}'
      + '.melis-react-cfg:hover{transform:scale(1.1);filter:brightness(1.08)}'
      // In-canvas reorder arrows (top-RIGHT, opposite the config ⚙), injected on every plugin that sits
      // in a drag-drop zone holding >1 block. Same reorder as the right panel's drag → move()/setZoneRefs.
      // HIDDEN by default, revealed only for the HOVERED or SELECTED plugin — otherwise adjacent plugins'
      // bars stack and overlap when the blocks are short/tightly spaced (only one shows at a time now).
      + '.melis-react-move{position:absolute;top:8px;right:8px;z-index:2147483000;display:flex;flex-direction:column;gap:3px;opacity:0;pointer-events:none;transition:opacity .12s}'
      + '.melis-react-has-cfg:hover>.melis-react-move,.melis-react-sel>.melis-react-move{opacity:1;pointer-events:auto}'
      + '.melis-react-mv{display:flex;align-items:center;justify-content:center;width:30px;height:26px;padding:0;'
      + 'border:2px solid #fff;background:var(--melis-accent,#dc2626);color:#fff;font-size:12px;line-height:1;cursor:pointer;'
      + 'box-shadow:0 3px 14px rgba(0,0,0,.35);opacity:1;transition:filter .1s}'
      + '.melis-react-mv:first-child{border-radius:8px 8px 3px 3px}'
      + '.melis-react-mv:last-child{border-radius:3px 3px 8px 8px}'
      + '.melis-react-mv:hover:not(:disabled){filter:brightness(1.12)}'
      + '.melis-react-mv:disabled{opacity:.35;cursor:default}'
    d.documentElement.style.setProperty('--melis-accent', accentRef.current) // iframe has no theme vars → push it
    // Mimic the legacy JS (absent from this clean render): a zone that HAS content (a plugin or a
    // sub-zone) isn't an empty drop target → drop its `no-content` class, so the red fill + "DRAG & DROP
    // ZONE" placeholder show ONLY on genuinely empty zones — exactly like the Old editor.
    d.querySelectorAll('.melis-dragdropzone.no-content').forEach((z) => {
      if (z.querySelector('.melis-ui-outlined, .melis-dragdropzone')) z.classList.remove('no-content')
    })
    // WYSIWYG width parity (see stripLegacyEdit): mirror each block's plugin-width class onto its
    // edit-chrome wrapper `.melis-ui-outlined` so THAT becomes the float box carrying the width — like
    // the front. Module plugins already carry it; tags/mini-templates carry it on their inner wrapper
    // only, so copy it up from the tools-box `data-plugin-width-{desktop,tablet,mobile}` attrs. The CSS
    // then floats+sizes the wrapper and forces inner wrappers to 100%. domWidth keeps the matching
    // breakpoint class (lg/md/xs) in sync on live resize; on reload the server render's data-attrs win.
    d.querySelectorAll('.melis-ui-outlined').forEach((wrapEl) => {
      const wrap = wrapEl as HTMLElement
      if (/plugin-width/.test(wrap.className)) return // module plugins already have it
      const tbw = wrap.querySelector('.melis-plugin-tools-box') as HTMLElement | null
      if (!tbw) return
      for (const k of ['desktop', 'tablet', 'mobile']) {
        const c = tbw.getAttribute('data-plugin-width-' + k)
        if (c) wrap.classList.add(c)
      }
    })
    // Config ⚙ on EVERY module plugin — the dnd-zone ones AND the template's HARDCODED plugins (menu,
    // header/footer…) which have no zone and no panel entry. Each plugin wrapper carries a hidden
    // `.melis-plugin-tools-box` with data-module/-plugin/-plugin-id; we read those and open the shared
    // config modal. Classic tag plugins (melisTag html/text/media) edit inline on click → no icon.
    d.querySelectorAll('.melis-ui-outlined').forEach((wrapEl) => {
      const wrap = wrapEl as HTMLElement
      if (wrap.querySelector(':scope > .melis-react-cfg')) return // already injected
      const tb = wrap.querySelector('.melis-plugin-tools-box[data-plugin-id]') as HTMLElement | null
      if (!tb) return
      const module = tb.getAttribute('data-module') || ''
      const name = tb.getAttribute('data-plugin') || ''
      const pid = tb.getAttribute('data-plugin-id') || ''
      if (!module || !name || !pid || tb.getAttribute('data-melis-tag') === 'melisTag') return
      wrap.classList.add('melis-react-has-cfg')
      const btn = d.createElement('button')
      btn.className = 'melis-react-cfg'; btn.type = 'button'; btn.textContent = '⚙'
      btn.title = peT().ecConfigurePluginNamed + ' (' + name + ')'
      btn.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); openConfigDirectRef.current?.(module, name, pid) })
      // Hovering the ⚙ outlines the plugin's block (like selecting it from the panel), only while hovered.
      btn.addEventListener('mouseenter', () => wrap.classList.add('melis-react-cfg-hl'))
      btn.addEventListener('mouseleave', () => wrap.classList.remove('melis-react-cfg-hl'))
      wrap.appendChild(btn)
    })
    // Click anything in the render → identify its drag-drop zone + the block if any → select it (outline
    // the zone's CONTENT in the canvas, highlight+scroll in the panel). Clicking an already-selected
    // zone's empty area climbs to the PARENT zone (so nested cells' parent is reachable from the canvas).
    d.addEventListener('click', (e) => {
      const t = e.target as HTMLElement | null
      if (!t) return
      if (t.closest('a')) e.preventDefault() // this is an editing canvas — don't navigate away
      // Require the click to land on the zone's CONTENT (.melis-dragdropzone), NOT the outer
      // container's dead chrome strip above it — so "clicking just above" the zone selects nothing.
      const zoneEl = t.closest('.melis-dragdropzone') as HTMLElement | null
      if (!zoneEl || !zoneEl.hasAttribute('data-dragdropzone-id')) return
      let zoneId = zoneEl.getAttribute('data-dragdropzone-id') || ''
      const pidEl = t.closest('[data-plugin-id]') as HTMLElement | null
      let refId = pidEl?.getAttribute('data-plugin-id') || null
      // The zone container itself carries data-plugin-id = its own id. Null it FIRST (before the fallback),
      // because plugins that render custom markup have NO data-plugin-id on their visible content, so
      // `closest('[data-plugin-id]')` climbs straight past their wrapper to the ZONE's id — which must be
      // treated as "no plugin found" so the generic fallback below can kick in.
      if (refId === zoneId) refId = null
      // GENERIC plugin resolution (system-level, not per-plugin): html/text/media/mini-template blocks
      // expose data-plugin-id on their visible `.html-editable`, but plugins that render custom markup
      // (menu, slider, blockSection, news…) carry it ONLY on their hidden `.melis-plugin-tools-box`, a
      // SIBLING of the visible content — so a click there finds no data-plugin-id ancestor. Fall back to
      // the enclosing plugin wrapper `.melis-ui-outlined` and read the refId off its tools-box. We pick
      // whichever of {plugin wrapper, nested drag-drop zone} is CLOSEST to the click, so clicking a nested
      // zone's empty area still selects the zone (not its parent plugin).
      if (!refId) {
        const near = t.closest('.melis-ui-outlined, .melis-dragdropzone[data-dragdropzone-id]') as HTMLElement | null
        if (near && near.classList.contains('melis-ui-outlined')) {
          const wrapRef = near.querySelector('[data-plugin-id]')?.getAttribute('data-plugin-id') || null
          if (wrapRef && wrapRef !== zoneId) refId = wrapRef
        }
      }
      if (!refId && selectedRef.current?.zoneId === zoneId) {
        // climb to the nearest ancestor zone with a DIFFERENT id (skip the same-id outer wrapper)
        let p = zoneEl.parentElement?.closest('[data-dragdropzone-id]') as HTMLElement | null
        while (p && p.getAttribute('data-dragdropzone-id') === zoneId) p = p.parentElement?.closest('[data-dragdropzone-id]') as HTMLElement | null
        if (p) zoneId = p.getAttribute('data-dragdropzone-id') || zoneId
      }
      if (refId) {
        outlineBlockInCanvas(d, refId) // a plugin/block → outline the block itself
        setSelected({ zoneId, refId })
        // classic editable block: clicking IN its content enters inline WYSIWYG (no edit button needed).
        // Match `.melis-editable` (shared by html/textarea/media bodies) — `.html-editable` gated out
        // textarea/media tags, so clicking them only selected the block and never started TinyMCE.
        if (t.closest('.melis-editable')) editInlineRef.current?.(zoneId, refId)
      } else {
        outlineZoneInCanvas(d, zoneId)       // zone content → outline the zone
        setSelected({ zoneId, refId })
      }
    })
    // Inject the in-canvas reorder arrows once the fresh render is in the DOM (gone after every reload).
    injectControlsRef.current?.()
    // Fresh page: its template drag-drop zones are rendered here but absent from the (empty) document —
    // seed them so the structure panel lists them. The canvas is guaranteed in the DOM now.
    maybeSeedZonesRef.current?.()
  }, [])

  // Keep the selection ref current for the canvas click listener (attached once per iframe load).
  useEffect(() => { selectedRef.current = selected }, [selected])

  // Select a zone/cell from the PANEL (works for parent zones too, which a canvas leaf-click can't reach
  // directly): highlight it in the panel + outline its content in the canvas.
  const selectZone = useCallback((zoneId: string) => {
    setSelected({ zoneId, refId: null })
    const d = iframeRef.current?.contentDocument
    if (d) outlineZoneInCanvas(d, zoneId)
  }, [])

  // Select a single plugin/block (from a panel row or the canvas) → outline the block in the canvas.
  const selectBlock = useCallback((zoneId: string, refId: string) => {
    setSelected({ zoneId, refId })
    const d = iframeRef.current?.contentDocument
    if (d) outlineBlockInCanvas(d, refId)
  }, [])

  // Find a block's live content element in the canvas (the editable body inside its wrapper).
  const blockContentEl = useCallback((refId: string): HTMLElement | null => {
    const d = iframeRef.current?.contentDocument
    if (!d) return null
    const esc = refId.replace(/["\\]/g, '\\$&')
    const cands = Array.from(d.querySelectorAll(`[id$="_${esc}"]`)) as HTMLElement[]
    const wrap = cands.find((e) => /plugin-width|melis-ui-outlined/.test(e.className)) || cands[0]
    // The editable body carries `.melis-editable` for EVERY tag type — the type-specific class differs
    // (`html-editable` / `textarea-editable` / `media-editable`). Targeting `.html-editable` only found
    // html tags; textarea/media fell back to the whole `melis-ui-outlined` wrapper, so TinyMCE attached
    // to the plugin chrome (or not at all) and a blur-save wrote the tools markup back as the tag content
    // (→ empty/broken in front). `.melis-editable` selects the right body for all three.
    return (wrap?.querySelector('.melis-editable') as HTMLElement | null) || wrap || null
  }, [])

  // Lazy-load Melis's TinyMCE into the render iframe (its own window; gone after a reload → re-inject).
  const ensureTinymce = useCallback((): Promise<any> => {
    const w = iframeRef.current?.contentWindow as (Window & { tinymce?: any }) | undefined
    const d = iframeRef.current?.contentDocument
    if (!w || !d) return Promise.reject(new Error('canvas not ready'))
    if (w.tinymce) return Promise.resolve(w.tinymce)
    return new Promise((resolve, reject) => {
      const s = d.createElement('script')
      s.src = TINY_BASE + '/tinymce.min.js' // the build the legacy uses (v6.7.0); its plugins match the core
      s.onload = () => resolve(w.tinymce)
      s.onerror = () => reject(new Error('TinyMCE load failed'))
      d.head.appendChild(s)
    })
  }, [])

  // Load melis_tinymce.js into the render iframe (jQuery is already present there) → provides the Melis env
  // the real config's callbacks reference: `melisTinyMCE.tinyMceActionEvent` (setup), `filePickerCallback`
  // (media library), `tinyMceCleaner` (init). Gone after an iframe reload → re-inject.
  const ensureMelisEnv = useCallback((): Promise<any> => {
    const w = iframeRef.current?.contentWindow as (Window & { melisTinyMCE?: any }) | undefined
    const d = iframeRef.current?.contentDocument
    if (!w || !d) return Promise.reject(new Error('canvas not ready'))
    if (w.melisTinyMCE) return Promise.resolve(w.melisTinyMCE)
    return new Promise((resolve, reject) => {
      const s = d.createElement('script')
      s.src = '/MelisCore/js/tinyMCE/melis_tinymce.js'
      s.onload = () => resolve(w.melisTinyMCE)
      s.onerror = () => reject(new Error('melis_tinymce.js load failed'))
      d.head.appendChild(s)
    })
  }, [])

  // Fetch the REAL Melis TinyMCE configs (by type: html/textarea/media/tool), once. Same endpoint the
  // legacy uses; same-origin cookie auth (the BO session).
  const loadTinyConfigs = useCallback(async (): Promise<Record<string, any>> => {
    if (tinyConfigsRef.current) return tinyConfigsRef.current
    try {
      const r = await fetch('/melis/MelisCore/MelisTinyMce/preloadTinyMceConfig', { credentials: 'same-origin' })
      tinyConfigsRef.current = (await r.json()) || {}
    } catch { tinyConfigsRef.current = {} }
    return tinyConfigsRef.current
  }, [])

  // Persist an inline WYSIWYG edit (setTagContent). The DOM is already updated by TinyMCE.
  const saveInline = useCallback(async (refId: string, html: string) => {
    setSaving(true)
    try {
      await apiPost('edition/save', { idPage, ops: [{ op: 'setTagContent', id: refId, content: html }] })
      // No toast on edits — editing only updates the working session; notifications belong to the
      // top toolbar's Save/Publish (like legacy). Errors below still notify.
      window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh', { detail: { revealPageId: idPage } }))
    } catch (e) { notify('ko', 'MelisCms', (e as Error).message) } finally { setSaving(false) }
  }, [idPage])

  // WYSIWYG in-block editing for classic (html/text/media) plugins — the whole point of WYSIWYG: TinyMCE
  // attaches INLINE to the block, a floating toolbar appears, you edit directly; blur → save (stateless).
  const editInline = useCallback(async (zoneId: string, refId: string) => {
    selectBlock(zoneId, refId)
    const el = blockContentEl(refId)
    if (!el) return
    const elId = 'melis-inline-' + refId.replace(/[^A-Za-z0-9_-]/g, '')
    try {
      const w = iframeRef.current?.contentWindow as any
      const tinymce = await ensureTinymce()
      // already an editor on this exact block → just focus it (avoids re-init flicker on re-click)
      if (el.id === elId && tinymce.get(elId)) { tinymce.get(elId).focus(); return }
      await ensureMelisEnv()
      const configs = await loadTinyConfigs()
      // the REAL Melis config for this tag TYPE (melis-cms: html/textarea/media), used AS-IS.
      // Read the type from the edited element itself — the `.melis-editable` body carries the
      // authoritative `data-tag-type` — and only fall back to the document model. (The model lookup
      // alone silently resolved to 'html' when it missed, so textarea/media loaded the html config.)
      const typeEl = (el.matches?.('[data-tag-type]') ? el : (el.querySelector?.('[data-tag-type]') as HTMLElement | null)) || (el.closest?.('[data-tag-type]') as HTMLElement | null)
      const t = String(typeEl?.getAttribute('data-tag-type') || doc?.nodes.find((n) => n.id === refId)?.attrs?.type || 'html').toLowerCase()
      const key = t.includes('media') ? 'media' : (t.includes('text') ? 'textarea' : 'html')
      const cfg: Record<string, any> = { ...(configs[key] || configs.html || {}) }
      delete cfg.selector
      cfg.target = el; cfg.inline = true; cfg.base_url = TINY_BASE; cfg.suffix = '.min'
      cfg.branding = false; cfg.promotion = false
      cfg.toolbar_mode = 'wrap' // show ALL buttons on multiple rows (vs 'sliding' → hidden behind "…")
      // wire the config's string-named callbacks to the real Melis env functions (in the iframe)
      cfg.file_picker_callback = w.filePickerCallback
      cfg.init_instance_callback = w.tinyMceCleaner
      const legacySetup = w.melisTinyMCE?.tinyMceActionEvent
      cfg.setup = (ed: any) => {
        try { legacySetup?.(ed) } catch { /* legacy setup needs the full BO JS; ignore its failures */ }
        ed.on('init', () => ed.focus())
        // save on blur; leave the editor attached (removing it mid-blur-dispatch corrupts TinyMCE).
        ed.on('blur', () => saveInline(refId, ed.getContent()))
      }
      tinymce.remove() // one inline editor at a time
      el.setAttribute('id', elId)
      tinymce.init(cfg)
    } catch (e) { notify('ko', 'MelisCms', (e as Error).message) }
  }, [selectBlock, blockContentEl, ensureTinymce, ensureMelisEnv, loadTinyConfigs, saveInline, doc])
  useEffect(() => { editInlineRef.current = editInline }, [editInline])

  // ⚙/✎ action on a block. Classic plugins (melisTag html/text/media) → inline WYSIWYG (no modal). Other
  // plugins → the config modal: a FULL-REACT form when the plugin is registered in PLUGIN_FORMS, else the
  // GENERIC legacy iframe (edition/plugin-config). Both persist via the shared stateless save endpoint.
  const openConfig = useCallback((zoneId: string, ref: { id: string; label: string }) => {
    const node = (doc?.nodes || []).find((n) => n.id === ref.id) || null
    const tag = node?.tag || ''
    if (tag === 'melisTag') { editInline(zoneId, ref.id); return }
    const meta = findPluginRef(doc?.nodes, ref.id)
    if (!meta || !meta.name || !meta.module) {
      notify('ko', 'MelisCms', peT().ecPluginNotFound)
      return
    }
    // React-first for EVERY plugin: a hand-written form if it has one, else the runtime SchemaForm
    // (which itself falls back to the legacy iframe when the plugin exposes no usable schema).
    setConfig({ zoneId, ref, node, module: meta.module, pluginName: meta.name, tag, useIframe: false, v: Date.now() })
    selectBlock(zoneId, ref.id)
  }, [doc, editInline, selectBlock])

  // Open the config modal for ANY plugin identified DIRECTLY by (module, name, id) — no doc-model lookup.
  // Used by the in-canvas ⚙ icon injected on every module plugin, INCLUDING the template's HARDCODED ones
  // (menu, header/footer…) that live outside any drag'n'drop zone (so they're absent from the panel/model).
  const openConfigDirect = useCallback((module: string, name: string, id: string, label?: string) => {
    if (!module || !name || !id) return
    // Prefill: a hardcoded plugin may still have a top-level data node in the document (its page-XML
    // config override) — pass it so the native/iframe form prefills from the current values, not defaults.
    const node = (doc?.nodes || []).find((n) => n.id === id) || null
    // Prefer the resolved, translated plugin TITLE (same source as the right panel) over the raw plugin
    // CLASS name — otherwise the in-canvas ⚙ showed the doubled class name (e.g. "FooFooPlugin").
    const nice = label || doc?.pluginTitles?.[id] || name
    setConfig({ zoneId: '', ref: { id, label: nice }, node, module, pluginName: name, tag: node?.tag || '', useIframe: false, v: Date.now() })
  }, [doc])
  const openConfigDirectRef = useRef<((module: string, name: string, id: string, label?: string) => void) | null>(null)
  useEffect(() => { openConfigDirectRef.current = openConfigDirect }, [openConfigDirect])

  // Config saved (from a React form or the iframe): close, notify, and — if something changed —
  // reload the canvas (re-render the reconfigured plugin) + refresh the document/model.
  const onConfigSaved = useCallback((changed: boolean) => {
    setConfig(null)
    // No toast — editing the config only updates the working session (see saveInline).
    if (changed) {
      setNonce((n) => n + 1)
      window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh', { detail: { revealPageId: idPage } }))
    }
  }, [idPage])

  // Bridge the generic iframe's postMessage (save/cancel) into the modal lifecycle.
  useEffect(() => {
    if (!config || !config.useIframe) return
    const onMsg = (e: MessageEvent) => {
      const d = e.data as { type?: string; changed?: boolean } | null
      if (!d || typeof d !== 'object') return
      if (d.type === 'melis-plugin-config-saved') onConfigSaved(!!d.changed)
      else if (d.type === 'melis-plugin-config-cancel') setConfig(null)
    }
    window.addEventListener('message', onMsg)
    return () => window.removeEventListener('message', onMsg)
  }, [config, onConfigSaved])

  // A config iframe field asks for a page: open the native React PagePicker and answer back to that iframe.
  useEffect(() => {
    const onMsg = (e: MessageEvent) => {
      const d = e.data as { type?: string; value?: string } | null
      if (d && d.type === 'melis-open-page-picker' && e.source) {
        setPagePicker({ value: String(d.value ?? ''), source: e.source as Window })
      }
    }
    window.addEventListener('message', onMsg)
    return () => window.removeEventListener('message', onMsg)
  }, [])

  // Track the theme accent (--color-primary follows data-theme: red platform / blue studio) so the
  // canvas outlines (separate iframe document — no theme vars) match the panel accent, live on toggle.
  useEffect(() => {
    const read = () => {
      const cs = getComputedStyle(document.documentElement)
      return (cs.getPropertyValue('--color-primary') || cs.getPropertyValue('--primary') || '#dc2626').trim()
    }
    const upd = () => { const a = read(); accentRef.current = a; setAccent(a); setDark(isDarkTheme()) }
    upd()
    const obs = new MutationObserver(upd)
    obs.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'class', 'style'] })
    return () => obs.disconnect()
  }, [])
  // Push the current accent into the loaded canvas iframe (its :root var) on accent change.
  useEffect(() => {
    iframeRef.current?.contentDocument?.documentElement?.style.setProperty('--melis-accent', accent)
  }, [accent])

  // Bring the selected zone/block into view in the panel + (CSS) highlight it there.
  useEffect(() => {
    if (!selected) return
    const root = panelRef.current
    if (!root) return
    const esc = (s: string) => (typeof CSS !== 'undefined' && CSS.escape ? CSS.escape(s) : s.replace(/[^\w-]/g, '\\$&'))
    const zEl = root.querySelector(`[data-testid="zone-${esc(selected.zoneId)}"]`) as HTMLElement | null
    zEl?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
    if (selected.refId) {
      const bEl = root.querySelector(`[data-testid="block-${esc(selected.refId)}"]`) as HTMLElement | null
      bEl?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
    }
  }, [selected])

  // Locate a block's outer WRAPPER in the render: it carries id="<page>_<module>_<name>_<refId>" and
  // a plugin-width / melis-ui-outlined class, a SIBLING of the other blocks (so it can be reordered)
  // — also covers types with no visible [data-plugin-id] content (e.g. blockSection).
  const locate = useCallback((id: string): HTMLElement | null => {
    const d = iframeRef.current?.contentDocument
    if (!d) return null
    const esc = id.replace(/["\\]/g, '\\$&')
    const cands = Array.from(d.querySelectorAll(`[id$="_${esc}"]`)) as HTMLElement[]
    const wrapper = cands.find((e) => /plugin-width|melis-ui-outlined/.test(e.className)) || cands[0]
    if (wrapper) return wrapper
    let best: HTMLElement | null = null, area = 0
    d.querySelectorAll(`[data-plugin-id="${esc}"]`).forEach((e) => { const el = e as HTMLElement; const r = el.getBoundingClientRect(); const a = r.width * r.height; if (a > area) { area = a; best = el } })
    return best
  }, [])

  const highlight = useCallback((id: string, on: boolean) => { locate(id)?.classList.toggle('melis-react-hl', on) }, [locate])
  const reveal = useCallback((id: string) => { locate(id)?.scrollIntoView({ block: 'center', behavior: 'smooth' }) }, [locate])

  // A FRESH page has an empty <document/>: its drag-drop zones exist only in the template render, so the
  // structure panel would stay empty until the first save. Seed them — read the top-level zone ids off the
  // rendered canvas and persist empty zone nodes into the edit session, so the panel lists them right away
  // AND ops (add plugin / apply layout) can target them. Idempotent (guarded); a no-op once zones exist.
  const maybeSeedZones = useCallback(async () => {
    if (seedTriedRef.current || !docEmptyRef.current) return
    const d = iframeRef.current?.contentDocument
    if (!d) return
    const ids = Array.from(d.querySelectorAll('[data-dragdropzone-id]'))
      .filter((el) => !(el as HTMLElement).parentElement?.closest('[data-dragdropzone-id]')) // top-level zones only
      .map((el) => (el as HTMLElement).getAttribute('data-dragdropzone-id') || '')
      .filter(Boolean)
    const uniq = Array.from(new Set(ids))
    if (uniq.length === 0) return
    seedTriedRef.current = true
    try {
      await apiPost('edition/save', { idPage, ops: [{ op: 'ensureZones', zones: uniq }] })
      const d2 = await apiGet<Doc>(`edition/document?idPage=${idPage}`)
      const zoneNodes = (d2.nodes || []).filter((n) => n.kind === 'zone' && n.id)
      if (zoneNodes.length) { docEmptyRef.current = false; setTree(zoneNodes.map((z) => toCell(z, d2.pluginTitles || {}))) }
    } catch { /* leave the panel empty — same as before the seed */ }
  }, [idPage])
  maybeSeedZonesRef.current = maybeSeedZones

  useEffect(() => {
    let cancelled = false
    seedTriedRef.current = false
    setDoc(null); setErr(null); setSelected(null)
    apiGet<Doc>(`edition/document?idPage=${idPage}`).then((d) => {
      if (cancelled) return
      const zoneNodes = (d.nodes || []).filter((n) => n.kind === 'zone' && n.id)
      setTree(zoneNodes.map((z) => toCell(z, d.pluginTitles || {})))
      // Fresh, zoneless page → try to seed the template zones (also attempted from onFrameLoad; whichever
      // fires with the canvas ready wins, the other early-returns).
      docEmptyRef.current = zoneNodes.length === 0
      void maybeSeedZones()
      setLayouts(d.layouts || [])
      // responsive widths per plugin DATA node (top-level siblings; the ref id === the node id) —
      // covers blocks in every cell since all data nodes are top-level whatever cell references them.
      const w: Record<string, { d: string; t: string; m: string }> = {}
      for (const n of d.nodes || []) {
        if (n.id && n.kind !== 'zone') {
          const a = (n as { attrs?: Record<string, string> }).attrs || {}
          w[n.id] = { d: a.width_desktop ?? '100', t: a.width_tablet ?? '100', m: a.width_mobile ?? '100' }
        }
      }
      setBlockW(w)
      setDoc(d)
    }).catch((e) => { if (!cancelled) setErr((e as Error).message) })
    return () => { cancelled = true }
  }, [idPage, nonce])

  // Load the REAL layout-schema icon CSS (the very sheet the legacy Old editor uses) so the
  // `html-button-icon` markup renders with its exact bootstrap-grid proportions (distinct per
  // schema — column ratios AND extra rows), + a small override to make the icon blocks visible
  // on our light panel and show the active/selected schema. The .icon-* / .column-icon rules only
  // match our icons; the sheet's other rules are scoped under legacy .melis-dragdropzone/.dnd-*.
  useEffect(() => {
    if (!document.getElementById('melis-dnd-icons-css')) {
      const link = document.createElement('link')
      link.id = 'melis-dnd-icons-css'; link.rel = 'stylesheet'
      link.href = '/MelisCms/css/dynamic-dragndrop/dynamic-dragndrop.css'
      document.head.appendChild(link)
    }
    if (!document.getElementById('melis-dnd-icons-override')) {
      const st = document.createElement('style')
      st.id = 'melis-dnd-icons-override'
      st.textContent = [
        '.melis-di button.column-icon{pointer-events:none;background:#f1f5f9;border:1px solid #e5e7eb;border-radius:4px;margin:0}',
        '.melis-di .icon-col-bg{background:#94a3b8 !important;border-radius:2px}',
        '.melis-di-pop button.column-icon{width:42px;height:34px}',
        '.melis-di-pop{cursor:pointer;border-radius:6px}',
        '.melis-di-pop:hover button.column-icon{border-color:#94a3b8}',
        // compact trigger for the zone header: legacy icon-row heights are fixed px, so scale the whole icon
        '.melis-di-mini{display:inline-block;width:18px;height:18px;overflow:hidden;vertical-align:middle;line-height:0}',
        '.melis-di-mini button.column-icon{transform:scale(.55);transform-origin:top left;display:block;margin:0;border:0;background:transparent}',
        '.melis-di-active button.column-icon{background:color-mix(in srgb,var(--color-primary,#dc2626) 14%,transparent);border-color:var(--color-primary,#dc2626)}',
        '.melis-di-active .icon-col-bg{background:var(--color-primary,#dc2626) !important}',
        // The legacy icon-col widths sum to LESS than 100% on purpose (they were meant to be distributed
        // with gutters), but nothing in dynamic-dragndrop.css spreads them → the equal N-col schemas
        // (4/5/6-col…) packed left and left an empty strip on the RIGHT. space-between distributes them
        // across the full width with even gutters (fills; single-child rows unaffected).
        '.melis-di .icon-row{justify-content:space-between}',
        // …except a small CENTERED zone (icon-col-4 + Bootstrap `justify-content-center`, a utility not in
        // the sheet nor loaded here): keep it centered (more specific → wins over space-between above).
        '.melis-di .icon-row.justify-content-center{justify-content:center}',
      ].join('\n')
      document.head.appendChild(st)
    }
  }, [])

  const domReorder = useCallback((orderedIds: string[]) => {
    let prev: HTMLElement | null = null
    for (const id of orderedIds) {
      const el = locate(id)
      if (!el) continue
      if (prev && prev.parentElement && prev.parentElement === el.parentElement) {
        prev.parentElement.insertBefore(el, prev.nextSibling)
      }
      prev = el
    }
  }, [locate])

  const domRemove = useCallback((id: string) => { locate(id)?.remove() }, [locate])

  // Live-patch the plugin-width class of the MATCHING breakpoint (desktop→lg / tablet→md / mobile→xs)
  // on the float wrapper (.melis-ui-outlined) so the ACTIVE device preview resizes immediately. Was
  // desktop-only, so tablet/mobile width changes showed nothing until a reload. The class VALUE is
  // resolved by the loaded plugin-width.min.css (media-query scoped), so only the active viewport's
  // class actually takes effect — matching the front.
  const domWidth = useCallback((id: string, dim: 'd' | 't' | 'm', v: string) => {
    const el = locate(id)
    if (!el) return
    const prefix = dim === 'd' ? 'lg' : dim === 't' ? 'md' : 'xs'
    const [i, f = '0'] = String(v).split('.')
    const cls = `plugin-width-${prefix}-${i}-${(f + '00').slice(0, 2)}`
    const re = new RegExp(`plugin-width-${prefix}-[0-9]+-[0-9]+`)
    if (re.test(el.className)) el.className = el.className.replace(re, cls)
    else el.className += ' ' + cls
  }, [locate])

  // Persist a zone's ref order/set to the WORKING EDIT SESSION immediately (like addPlugin/applyLayout/
  // setTagContent). Editing writes the session, never the draft — the legacy model: the top toolbar's
  // Save flushes that session into the draft (meliscms_page_save_start → saveEdition), Publish copies it
  // on to published. So each structural edit (reorder/remove/width) updates the session on the spot; the
  // canvas render reads the session, so it shows live, and the top Save carries it. setZoneRefs is the
  // exact-set op (also drops removed refs).
  const persistZoneRefs = useCallback(async (zoneId: string, refIds: string[]) => {
    setSaving(true)
    try {
      await apiPost('edition/save', { idPage, ops: [{ op: 'setZoneRefs', zoneId, refIds }] })
      window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh', { detail: { revealPageId: idPage } }))
    } catch (e) { notify('ko', 'MelisCms', (e as Error).message) } finally { setSaving(false) }
  }, [idPage])

  const move = useCallback((zoneId: string, from: number, to: number) => {
    const z = findCell(tree, zoneId)
    if (!z || to < 0 || to >= z.refs.length) return
    const refs = z.refs.slice()
    const [x] = refs.splice(from, 1)
    refs.splice(to, 0, x)
    setTree((t) => mapCell(t, zoneId, (zz) => ({ ...zz, refs })))
    queueMicrotask(() => domReorder(refs.map((r) => r.id)))
    persistZoneRefs(zoneId, refs.map((r) => r.id)) // immediate draft save → survives top-toolbar Publish
  }, [tree, domReorder, persistZoneRefs])

  // Inject ↑/↓ reorder arrows IN THE CANVAS, on each plugin wrapper of a leaf zone holding >1 block.
  // The panel's drag-reorder is unreliable inside the iframe; these arrows give the same move() (→
  // setZoneRefs, immediate draft save) with a single click. Driven by the tree (authoritative order),
  // located via locate(refId) — so it's independent of the iframe's DOM ambiguities. Rebuilt fresh each
  // call (remove-all then re-add) so a move()'s new indices/boundaries always show. Arrows sit top-RIGHT,
  // opposite the config ⚙; a click doesn't select/inline-edit (stopPropagation).
  const injectControls = useCallback(() => {
    const d = iframeRef.current?.contentDocument
    if (!d) return
    const tr = peT()
    d.querySelectorAll('.melis-react-move').forEach((b) => b.remove())
    const build = (cells: Cell[]) => {
      for (const c of cells) {
        if (c.cells.length === 0 && c.refs.length > 1) {
          c.refs.forEach((r, i) => {
            const wrap = locate(r.id)
            if (!wrap) return
            wrap.classList.add('melis-react-has-cfg') // gives the wrapper position:relative for the absolute bar
            const bar = d.createElement('div')
            bar.className = 'melis-react-move'
            const mk = (txt: string, title: string, dir: -1 | 1, disabled: boolean) => {
              const b = d.createElement('button')
              b.type = 'button'; b.className = 'melis-react-mv'; b.textContent = txt; b.title = title; b.disabled = disabled
              if (!disabled) b.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); move(c.id, i, i + dir) })
              return b
            }
            bar.appendChild(mk('▲', tr.ecMoveBlockUp, -1, i === 0))
            bar.appendChild(mk('▼', tr.ecMoveBlockDown, 1, i === c.refs.length - 1))
            wrap.appendChild(bar)
          })
        }
        build(c.cells)
      }
    }
    build(tree)
  }, [tree, locate, move])
  useEffect(() => { injectControlsRef.current = injectControls }, [injectControls])
  // Rebuild arrows whenever the tree changes (i.e. after a move — no iframe reload) so indices/boundaries
  // refresh. The iframe-reload case (nonce) is covered by onFrameLoad's own call.
  useEffect(() => { injectControls() }, [injectControls])

  const removeBlock = useCallback((zoneId: string, refId: string) => {
    const z = findCell(tree, zoneId)
    const refs = (z?.refs || []).filter((r) => r.id !== refId).map((r) => r.id)
    setTree((t) => mapCell(t, zoneId, (zz) => ({ ...zz, refs: zz.refs.filter((r) => r.id !== refId) })))
    queueMicrotask(() => {
      domRemove(refId)
      // A fresh server render marks an empty zone `no-content` (pale bg + "+" placeholder), but remove is a
      // LIVE DOM patch with no reload — so a zone that just lost its last block would stay blank. Re-add the
      // class when nothing is left, restoring the empty drop-zone look without a reload.
      const d = iframeRef.current?.contentDocument
      if (d) {
        const esc = zoneId.replace(/["\\]/g, '\\$&')
        const cont = d.querySelector(`[data-dragdropzone-id="${esc}"]`)
        const zoneEl = (cont?.matches('.melis-dragdropzone') ? cont : cont?.querySelector('.melis-dragdropzone')) as HTMLElement | null
        if (zoneEl && !zoneEl.querySelector('.melis-ui-outlined, [data-melis-plugin-tag-id]')) zoneEl.classList.add('no-content')
      }
    })
    persistZoneRefs(zoneId, refs) // immediate draft save (exact set drops the removed ref)
  }, [tree, domRemove, persistZoneRefs])

  // Typing updates local state + live desktop preview only; the draft is written on blur (persistWidths)
  // so we don't POST on every keystroke.
  const setWidth = useCallback((id: string, dim: 'd' | 't' | 'm', v: string) => {
    setBlockW((w) => ({ ...w, [id]: { ...(w[id] ?? { d: '100', t: '100', m: '100' }), [dim]: v } }))
    // patch the matching breakpoint class live so the active device preview updates immediately
    queueMicrotask(() => domWidth(id, dim, v))
  }, [domWidth])

  // Immediate session save of a block's responsive widths (on blur) — same rationale as persistZoneRefs:
  // edits go to the working session so the top-toolbar Save can flush them to the draft.
  const persistWidths = useCallback((id: string, v?: { d: string; t: string; m: string }) => {
    if (!v) return
    setSaving(true)
    apiPost('edition/save', { idPage, ops: [{ op: 'setWidths', id, desktop: v.d, tablet: v.t, mobile: v.m }] })
      .then(() => window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh', { detail: { revealPageId: idPage } })))
      .catch((e) => notify('ko', 'MelisCms', (e as Error).message))
      .finally(() => setSaving(false))
  }, [idPage])

  // Apply a drag-and-drop SCHEMA to a zone/cell. Structural → persist immediately then reload the
  // canvas iframe (nonce) so the columns render. (Everything else already persists on the spot, so
  // there is nothing to flush first.)
  const applyLayout = useCallback(async (zoneId: string, template: string) => {
    setSaving(true)
    try {
      await apiPost('edition/save', { idPage, ops: [{ op: 'applyLayout', zoneId, template }] })
      // No toast on edits (session only) — see saveInline.
      setNonce((n) => n + 1) // re-fetch document + reload canvas → cells/colonnes apparaissent
      window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh', { detail: { revealPageId: idPage } }))
    } catch (e) { notify('ko', 'MelisCms', (e as Error).message) } finally { setSaving(false) }
  }, [idPage])

  // The addable-plugins palette is PER SITE (it lists only plugins whose module is loaded for THIS
  // page's site) → drop the cached catalog whenever the edited page changes, so switching to a page of
  // another site refetches the right list instead of reusing the first page's.
  useEffect(() => { setCatalog(null) }, [idPage])

  // Open the "+" plugin palette for a cell; fetch the catalog once per page (lazy).
  const openPluginPicker = useCallback(async (cellId: string) => {
    setPluginPicker({ cellId }); setPickerQuery(''); setPickerSection(null)
    if (catalog === null) {
      try { setCatalog(await apiGet<Palette>(`edition/plugins?idPage=${idPage}`)) }
      catch (e) { notify('ko', 'MelisCms', (e as Error).message) }
    }
  }, [catalog, idPage])

  // Add the chosen plugin to the cell: persist the addPlugin op, reload the canvas (the new plugin is
  // server-rendered — no live inject). Same immediate-save shape as applyLayout.
  const addPluginFromCatalog = useCallback(async (cellId: string, entry: PalettePlugin) => {
    setPluginPicker(null)
    setSaving(true)
    try {
      await apiPost('edition/save', { idPage, ops: [{ op: 'addPlugin', zoneId: cellId, module: entry.module, name: entry.name }] })
      // No toast on edits (session only) — see saveInline.
      setNonce((n) => n + 1)
      window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh', { detail: { revealPageId: idPage } }))
    } catch (e) { notify('ko', 'MelisCms', (e as Error).message) } finally { setSaving(false) }
  }, [idPage])

  const onDrop = (zoneId: string, targetIdx: number, e: React.DragEvent) => {
    e.preventDefault()
    const from = Number(e.dataTransfer.getData('text/plain'))
    if (Number.isNaN(from)) return
    move(zoneId, from, targetIdx)
  }

  const tr = peT()
  const msg: React.CSSProperties = { padding: 20, fontSize: 13, color: 'var(--color-muted-foreground,#6b7280)' }
  if (err) return <div style={{ ...msg, color: '#dc2626' }}>{tr.ecErrorPrefix}{err}</div>
  if (!doc) return <div style={msg}>{tr.ecLoadingEditor}</div>

  const iconBtn: React.CSSProperties = { appearance: 'none', border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', borderRadius: 5, width: 22, height: 22, lineHeight: '1', cursor: 'pointer', fontSize: 12, color: 'var(--color-foreground,#111827)' }

  // The current schema's real icon markup for a cell (falls back to the single-column "default").
  const iconFor = (tpl: string): string => {
    const t = tpl || DEFAULT_TPL
    return (layouts.find((l) => l.template === t) || layouts.find((l) => l.key === 'default') || layouts[0])?.icon || ''
  }

  const openPicker = (e: React.MouseEvent, cellId: string) => {
    e.stopPropagation() // don't also trigger the header's select-zone
    const r = (e.currentTarget as HTMLElement).getBoundingClientRect()
    setPicker({ cellId, x: Math.max(8, Math.min(r.right - 300, window.innerWidth - 312)), y: r.bottom + 4 })
  }

  // A compact trigger (current layout icon) that sits in the zone header row; clicking DEPLOYS the full
  // schema list (popover) instead of flooding every zone with 27 icons.
  const LayoutTrigger = ({ cell }: { cell: Cell }) => (
    <div role="button" tabIndex={0} data-testid={`layout-trigger-${cell.id}`} title={tr.ecLayoutTitle}
      onClick={(e) => openPicker(e, cell.id)}
      style={{ display: 'inline-flex', alignItems: 'center', gap: 2, cursor: saving ? 'not-allowed' : 'pointer', border: '1px solid var(--color-border,#e5e7eb)', borderRadius: 5, padding: '1px 3px', background: 'var(--color-card,#fff)', opacity: saving ? .6 : 1 }}>
      <span className="melis-di melis-di-mini" dangerouslySetInnerHTML={{ __html: iconFor(cell.template) }} />
      <span style={{ fontSize: 8, color: 'var(--color-muted-foreground,#6b7280)' }}>▾</span>
    </div>
  )

  // Recursive zone/cell panel. `path` = hierarchical position ("1", "1-2", "1-2-1"…) → the user-facing
  // zone name (the raw zone id is meaningless to the end user; kept in the tooltip). Depth tints the border.
  // NOTE: CellView is CALLED as a plain function `CellView({...})`, NOT rendered as `<CellView/>`.
  // It's defined inside EditionCanvas, so as a JSX component its function identity changes every
  // render → React would unmount/remount the whole subtree on each state change. That made the width
  // <input> lose focus per keystroke AND swallowed its onBlur → persistWidths never fired → widths
  // never reached the session (bug). Calling it as a function inlines its JSX (host <div>, stable
  // type) so React reconciles in place: focus kept, onBlur fires. It uses no hooks, so this is safe.
  const CellView = ({ cell, depth, path }: { cell: Cell; depth: number; path: string }) => {
    const isLeaf = cell.cells.length === 0
    const isSel = selected?.zoneId === cell.id
    const zoneName = `${tr.ecZonePrefix} ${path}`
    return (
      <div key={cell.id} data-testid={`zone-${cell.id}`} style={{ marginBottom: depth === 0 ? 12 : 8, marginLeft: depth ? 8 : 0, border: '1px solid var(--color-border,#e5e7eb)', borderLeft: depth ? '3px solid color-mix(in srgb, var(--color-primary,#dc2626) 35%, #e5e7eb)' : '1px solid var(--color-border,#e5e7eb)', borderRadius: 8, overflow: 'hidden', boxShadow: isSel ? '0 0 0 2px var(--color-primary,#dc2626)' : undefined }}>
        <div data-testid={`zone-head-${cell.id}`} onClick={() => selectZone(cell.id)} title={`${tr.ecSelectZone} ${zoneName} (${cell.id})`}
          style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 10, fontWeight: 600, color: 'var(--color-muted-foreground,#6b7280)', background: isSel ? 'color-mix(in srgb, var(--color-primary,#dc2626) 16%, transparent)' : 'color-mix(in srgb, var(--color-primary,#dc2626) 6%, transparent)', padding: '4px 8px', cursor: 'pointer' }}>
          <span style={{ flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={cell.id}>{depth ? '▫' : '⛶'} {zoneName}</span>
          {isLeaf && (
            <button data-testid={`add-${cell.id}`} title={tr.ecAddPlugin} onClick={(e) => { e.stopPropagation(); openPluginPicker(cell.id) }}
              style={{ appearance: 'none', border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', color: 'var(--color-foreground,#111827)', borderRadius: 5, height: 18, minWidth: 20, padding: '0 6px', fontSize: 12, fontWeight: 700, cursor: 'pointer', lineHeight: '1' }}>+</button>
          )}
          {/* compact schema picker — deploys the full list; each cell reconfigurable */}
          <LayoutTrigger cell={cell} />
        </div>

        {/* this cell's own blocks (only meaningful for a leaf; a split cell holds sub-cells instead) */}
        {isLeaf && cell.refs.map((r, i) => (
          <div key={r.id} data-testid={`block-${r.id}`} draggable
            onDragStart={(e) => e.dataTransfer.setData('text/plain', String(i))}
            onDragOver={(e) => e.preventDefault()}
            onDrop={(e) => onDrop(cell.id, i, e)}
            onMouseEnter={() => highlight(r.id, true)}
            onMouseLeave={() => highlight(r.id, false)}
            style={{ padding: '5px 8px', borderTop: '1px solid var(--color-border,#e5e7eb)', fontSize: 12, cursor: 'grab', background: selected?.refId === r.id ? 'color-mix(in srgb, var(--color-primary,#dc2626) 14%, transparent)' : undefined }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 6 }} onClick={() => selectBlock(cell.id, r.id)}>
              <span style={{ color: 'var(--color-muted-foreground,#9ca3af)' }}>⠿</span>
              {/* plugin / mini-template thumbnail (if any) — small, aspect kept, so the row stays compact */}
              {doc?.pluginThumbs?.[r.id] ? (
                <img src={doc.pluginThumbs[r.id]} alt="" onError={(e) => { (e.currentTarget as HTMLImageElement).style.display = 'none' }}
                  style={{ height: 44, width: 'auto', maxWidth: 66, objectFit: 'contain', borderRadius: 3, flex: '0 0 auto', background: 'color-mix(in srgb, var(--color-muted-foreground,#6b7280) 8%, transparent)' }} />
              ) : null}
              {r.mini ? (
                <span style={{ flex: 1, minWidth: 0, lineHeight: 1.2 }} title={r.id}>
                  <span style={{ display: 'block', fontWeight: selected?.refId === r.id ? 700 : 600 }}>{tr.ecMiniTemplate}</span>
                  <span style={{ display: 'block', fontSize: 10, color: 'var(--color-muted-foreground,#6b7280)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{r.label}</span>
                </span>
              ) : (
                <span style={{ flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', fontWeight: selected?.refId === r.id ? 700 : 400 }} title={r.id}>{r.label}</span>
              )}
              {/* classic (html/media/textarea) blocks are edited by CLICKING in them (WYSIWYG) — no button;
                  only non-classic plugins get a config button */}
              {(doc?.nodes.find((n) => n.id === r.id)?.tag || '') !== 'melisTag' && (
                <button data-testid={`config-${r.id}`} title={tr.ecConfigurePlugin} onClick={(e) => { e.stopPropagation(); openConfig(cell.id, r) }} style={{ ...iconBtn, borderColor: 'var(--color-border,#e5e7eb)' }}>⚙</button>
              )}
              {/* responsive-width toggle — the 3 inputs are deployed on demand (they're rarely used and
                  take up room otherwise). Highlighted when open. */}
              <button data-testid={`width-toggle-${r.id}`} title={tr.ecResponsiveWidths}
                onClick={(e) => { e.stopPropagation(); setOpenWidth((w) => (w === r.id ? null : r.id)) }}
                style={{ ...iconBtn, borderColor: openWidth === r.id ? 'var(--color-primary,#dc2626)' : 'var(--color-border,#e5e7eb)', color: openWidth === r.id ? 'var(--color-primary,#dc2626)' : 'var(--color-foreground,#111827)' }}>↔</button>
              <button data-testid={`remove-${r.id}`} title={tr.ecRemoveFromZone} onClick={(e) => { e.stopPropagation(); setConfirmRemove({ zoneId: cell.id, refId: r.id, label: r.label }) }} style={{ ...iconBtn, borderColor: '#fecaca', color: '#dc2626' }}>×</button>
            </div>
            {openWidth === r.id && (
              <div data-testid={`widths-${r.id}`} onClick={(e) => e.stopPropagation()} style={{ display: 'flex', alignItems: 'center', gap: 8, marginTop: 6, paddingLeft: 18 }}>
                {(['d', 't', 'm'] as const).map((dim) => (
                  <label key={dim} style={{ display: 'inline-flex', alignItems: 'center', gap: 3, fontSize: 10, color: 'var(--color-muted-foreground,#6b7280)' }} title={dim === 'd' ? tr.ecWidthDesktop : dim === 't' ? tr.ecWidthTablet : tr.ecWidthMobile}>
                    {dim === 'd' ? '🖥' : dim === 't' ? '📱' : '📲'}
                    <input data-testid={`width-${dim}-${r.id}`} type="number" min={0} max={100} step={1}
                      value={blockW[r.id]?.[dim] ?? '100'} onClick={(e) => e.stopPropagation()} onChange={(e) => setWidth(r.id, dim, e.target.value)}
                      onBlur={() => persistWidths(r.id, blockW[r.id])}
                      style={{ width: 40, height: 20, border: '1px solid var(--color-border,#e5e7eb)', borderRadius: 5, fontSize: 10, textAlign: 'right', padding: '0 3px' }} />
                  </label>
                ))}
              </div>
            )}
          </div>
        ))}
        {isLeaf && cell.refs.length === 0 && <div style={{ fontSize: 11, color: 'var(--color-muted-foreground,#9ca3af)', padding: '6px 8px' }}>{tr.ecEmptyCell}</div>}

        {/* nested sub-cells (columns/rows), recursive */}
        {cell.cells.length > 0 && (
          <div style={{ padding: '6px 6px 2px' }}>
            {cell.cells.map((c, i) => CellView({ cell: c, depth: depth + 1, path: `${path}-${i + 1}` }))}
          </div>
        )}
      </div>
    )
  }

  return (
    <div style={{ height: '100%', display: 'flex', flexDirection: 'column', background: 'var(--color-background,#fff)' }}>
      {/* discreet saving indicator, floated (no header bar) */}
      {saving && <div style={{ position: 'absolute', top: 6, right: 12, zIndex: 5, fontSize: 11, fontWeight: 600, color: 'var(--color-muted-foreground,#6b7280)', background: 'var(--color-card,#fff)', border: '1px solid var(--color-border,#e5e7eb)', borderRadius: 6, padding: '2px 8px' }}>{tr.ecSaving}</div>}
      <div style={{ flex: '1 1 auto', minHeight: 0, display: 'flex', position: 'relative' }}>
        {/* Device-preview frame: desktop = full bleed; tablet/mobile = fixed width, centered on a
            neutral backdrop (like the legacy responsive preview), so the page reflows to that width. */}
        <div style={{ flex: '1 1 auto', minWidth: 0, display: 'flex', justifyContent: 'center', overflow: 'auto', background: device === 'desktop' ? undefined : 'var(--color-muted,#f1f5f9)', padding: device === 'desktop' ? 0 : '12px 0' }}>
          <iframe key={nonce} ref={iframeRef} onLoad={onFrameLoad} src={renderSrc} title={`Canvas page ${idPage}`}
            style={{ width: DEVICE_W[device], maxWidth: '100%', height: '100%', flex: '0 0 auto', border: device === 'desktop' ? 0 : '1px solid var(--color-border,#e5e7eb)', borderRadius: device === 'desktop' ? 0 : 6, background: '#fff', boxShadow: device === 'desktop' ? undefined : '0 4px 18px rgba(0,0,0,.10)', transition: 'width .18s ease' }} />
        </div>
        {/* Mobile drawer backdrop — tap to close (only when the drawer is open). */}
        {isMobile && !panelCollapsed && (
          <div onClick={() => setPanelCollapsed(true)} style={{ position: 'absolute', inset: 0, zIndex: 40, background: 'rgba(0,0,0,.4)' }} />
        )}
        {/* Structure panel — desktop: in-flow collapsible column; mobile: full-height drawer sliding over
            the canvas (page preview is secondary on a phone; you manage order + configs from here). */}
        <div ref={panelRef} style={
          isMobile
            ? { position: 'absolute', top: 0, right: 0, bottom: 0, zIndex: 41, width: 'min(90vw, 360px)', transform: panelCollapsed ? 'translateX(100%)' : 'none', borderLeft: '1px solid var(--color-border,#e5e7eb)', overflow: 'auto', padding: 10, background: 'var(--color-card,#fff)', boxShadow: panelCollapsed ? 'none' : '-10px 0 30px rgba(0,0,0,.22)', transition: 'transform .2s ease' }
            : { flex: `0 0 ${panelCollapsed ? 32 : 360}px`, borderLeft: '1px solid var(--color-border,#e5e7eb)', overflow: panelCollapsed ? 'hidden' : 'auto', padding: panelCollapsed ? '8px 3px' : 10, background: 'var(--color-card,#fff)', transition: 'flex-basis .15s ease' }
        }>
          {(!isMobile && panelCollapsed) ? (
            <button data-testid="panel-expand" title={tr.ecExpandPanel} onClick={() => setPanelCollapsed(false)}
              style={{ ...iconBtn, width: 26, height: 26, margin: '0 auto', display: 'block' }}>«</button>
          ) : (
            <>
              <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginBottom: 8 }}>
                <span style={{ flex: '0 0 auto', width: 26 }} />
                <span style={{ flex: 1, textAlign: 'center', fontSize: 12, fontWeight: 700, letterSpacing: .2, color: 'var(--color-foreground,#111827)' }}>{tr.ecPanelTitle}</span>
                <button data-testid="panel-collapse" title={isMobile ? tr.ecClosePanel : tr.ecCollapsePanel} onClick={() => setPanelCollapsed(true)}
                  style={{ ...iconBtn, flex: '0 0 auto', width: 26, height: 26 }}>{isMobile ? '✕' : '»'}</button>
              </div>
              {tree.length === 0 && <div style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)', padding: 6 }}>{tr.ecNoZones}</div>}
              {tree.map((z, i) => CellView({ cell: z, depth: 0, path: String(i + 1) }))}
            </>
          )}
        </div>
        {/* Mobile: floating button to (re)open the drawer when it's closed. */}
        {isMobile && panelCollapsed && (
          <button data-testid="panel-open-mobile" title={tr.ecOpenDrawer} onClick={() => setPanelCollapsed(false)}
            style={{ position: 'absolute', bottom: 16, right: 16, zIndex: 42, display: 'inline-flex', alignItems: 'center', gap: 6, height: 44, padding: '0 16px', borderRadius: 22, border: 'none', background: 'var(--color-primary,#dc2626)', color: 'var(--color-primary-foreground,#fff)', fontSize: 13, fontWeight: 600, boxShadow: '0 6px 20px rgba(0,0,0,.28)', cursor: 'pointer' }}>
            ☰ {tr.ecZonesButton}
          </button>
        )}
      </div>

      {/* Deployed schema list (fixed → escapes the panel's overflow clip). Real Old-editor icons. */}
      {picker && (() => {
        const cur = (findCell(tree, picker.cellId)?.template) || DEFAULT_TPL
        return (
          <>
            <div onClick={() => setPicker(null)} style={{ position: 'fixed', inset: 0, zIndex: 60 }} />
            <div style={{ position: 'fixed', left: picker.x, top: picker.y, zIndex: 61, width: 300, maxHeight: '62vh', overflow: 'auto', background: 'var(--color-card,#fff)', border: '1px solid var(--color-border,#e5e7eb)', borderRadius: 8, boxShadow: '0 12px 34px rgba(0,0,0,.18)', padding: 8 }}>
              <div style={{ fontSize: 10, fontWeight: 700, letterSpacing: .3, color: 'var(--color-muted-foreground,#6b7280)', margin: '2px 2px 6px' }}>{tr.ecZoneLayoutHeader}</div>
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
                {layouts.map((l) => (
                  <div key={l.key} role="button" data-testid={`layout-${picker.cellId}-${l.key}`} title={`${l.key} (${l.cols || 1})`}
                    onClick={() => { const id = picker.cellId; setPicker(null); applyLayout(id, l.template) }}
                    className={`melis-di melis-di-pop${l.template === cur ? ' melis-di-active' : ''}`}
                    dangerouslySetInnerHTML={{ __html: l.icon }} />
                ))}
              </div>
            </div>
          </>
        )
      })()}

      {/* Plugin CONFIG modal. Full-React form (registered plugin) OR generic legacy iframe (any other
          plugin), each owning its own Save/Cancel. Both persist via edition/plugin-config/save. */}
      {config && (() => {
        const theme = dark ? 'dark' : 'light'
        const iframeSrc = `/melis/react-api/cms-page/edition/plugin-config?idPage=${idPage}`
          + `&module=${encodeURIComponent(config.module)}&pluginName=${encodeURIComponent(config.pluginName)}`
          + `&pluginId=${encodeURIComponent(config.ref.id)}&theme=${theme}&_=${config.v}`
        const useReact = !config.useIframe
        const isBespoke = hasPluginForm(config.pluginName)
        const formProps = {
          idPage, module: config.module, pluginName: config.pluginName, pluginId: config.ref.id,
          tag: config.tag, rawXml: config.node?.raw || '', accent,
          onSaved: onConfigSaved, onCancel: () => setConfig(null),
        }
        const toIframe = () => setConfig((c) => (c ? { ...c, useIframe: true } : c))
        return (
          <div style={{ position: 'fixed', inset: 0, zIndex: 80, background: 'rgba(0,0,0,.45)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }} onClick={() => setConfig(null)}>
            <div onClick={(e) => e.stopPropagation()} style={{ width: 'min(760px, 94vw)', maxHeight: '88vh', display: 'flex', flexDirection: 'column', background: 'var(--color-card,#fff)', color: 'var(--color-foreground,#111827)', borderRadius: 12, boxShadow: '0 24px 70px rgba(0,0,0,.45)', overflow: 'hidden' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '12px 16px', borderBottom: '1px solid var(--color-border,#e5e7eb)' }}>
                <span style={{ fontWeight: 700, fontSize: 14 }}>{tr.ecPluginPrefix} · {config.ref.label}</span>
                <div style={{ marginLeft: 'auto', display: 'flex', alignItems: 'center', gap: 8 }}>
                  {/* New/Old toggle — every plugin now has a React config (a hand-written form OR the runtime
                      schema form) AND the legacy iframe; this switches between them (New = React, Old = iframe). */}
                  <ViewToggle compact mode={config.useIframe ? 'iframe' : 'react'}
                    onChange={(m) => setConfig((c) => (c ? { ...c, useIframe: m === 'iframe', v: Date.now() } : c))} />
                  <button onClick={() => setConfig(null)} title={tr.ecClose} style={{ ...iconBtn, width: 26, height: 26 }}>✕</button>
                </div>
              </div>
              <div style={{ padding: useReact ? 16 : 0, overflow: 'auto', flex: '1 1 auto', minHeight: 220 }}>
                {!useReact ? (
                  <iframe data-testid={`config-iframe-${config.ref.id}`} title={`${tr.ecConfigFor} ${config.ref.label}`} src={iframeSrc}
                    style={{ width: '100%', height: '62vh', border: 0, display: 'block', background: 'var(--color-background,#fff)' }} />
                ) : isBespoke ? (
                  <PluginFormBoundary onError={toIframe}>
                    <PluginTabbedForm {...formProps} />
                  </PluginFormBoundary>
                ) : (
                  <PluginFormBoundary onError={toIframe}>
                    <SchemaForm {...formProps} onUnavailable={toIframe} />
                  </PluginFormBoundary>
                )}
              </div>
            </div>
          </div>
        )
      })()}

      {/* "+" ADD-PLUGIN palette: every active/addable plugin (edition/plugins), grouped by section.
          Pick one → addPlugin op → canvas reload. Its config is then editable via the config modal. */}
      {pluginPicker && (() => {
        const q = pickerQuery.trim().toLowerCase()
        const allSecs = catalog?.sections || []
        const secs = filterPalette(allSecs, q).filter((s) => !pickerSection || s.key === pickerSection)
        return (
          <div data-testid="plugin-picker" style={{ position: 'fixed', inset: 0, zIndex: 85, background: 'rgba(0,0,0,.45)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }} onClick={() => setPluginPicker(null)}>
            <div onClick={(e) => e.stopPropagation()} style={{ width: 'min(820px, 95vw)', maxHeight: '88vh', display: 'flex', flexDirection: 'column', background: 'var(--color-card,#fff)', color: 'var(--color-foreground,#111827)', borderRadius: 12, boxShadow: '0 24px 70px rgba(0,0,0,.45)', overflow: 'hidden' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '12px 16px', borderBottom: '1px solid var(--color-border,#e5e7eb)' }}>
                <span style={{ fontWeight: 700, fontSize: 14 }}>{tr.ecAddPlugin}</span>
                <span style={{ fontSize: 11, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.ecZoneWord} {pluginPicker.cellId}</span>
                <button onClick={() => setPluginPicker(null)} title={tr.ecClose} style={{ ...iconBtn, marginLeft: 'auto', width: 26, height: 26 }}>✕</button>
              </div>
              {/* marketplace-style section filter chips (above the search) */}
              {allSecs.length > 1 && (
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6, padding: '10px 16px 0' }}>
                  {(() => {
                    const chip = (active: boolean): React.CSSProperties => ({
                      display: 'inline-flex', alignItems: 'center', gap: 6, padding: '4px 10px', borderRadius: 999,
                      border: active ? '1px solid var(--color-primary,#dc2626)' : '1px solid var(--color-border,#e5e7eb)',
                      background: active ? 'color-mix(in srgb, var(--color-primary,#dc2626) 12%, transparent)' : 'var(--color-background,#fff)',
                      color: 'inherit', fontSize: 12, fontWeight: 600, cursor: 'pointer',
                    })
                    return (
                      <>
                        <button data-testid="picker-section-all" onClick={() => setPickerSection(null)} style={chip(pickerSection === null)}>
                          <MelisSectionIcon sectionKey="__all__" size={16} />
                          {tr.ecAllGroups}
                        </button>
                        {allSecs.map((s) => (
                          <button key={s.key} data-testid={`picker-section-${s.key}`} onClick={() => setPickerSection((cur) => cur === s.key ? null : s.key)} style={chip(pickerSection === s.key)}>
                            <MelisSectionIcon sectionKey={s.key} size={16} />
                            {shortSectionLabel(s.key)}
                          </button>
                        ))}
                      </>
                    )
                  })()}
                </div>
              )}
              <div style={{ padding: '10px 16px', borderBottom: '1px solid var(--color-border,#e5e7eb)' }}>
                <input data-testid="plugin-picker-search" autoFocus value={pickerQuery} onChange={(e) => setPickerQuery(e.target.value)}
                  placeholder={tr.ecSearchPlugin} style={{ width: '100%', padding: '8px 10px', borderRadius: 6, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-background,#fff)', color: 'inherit', fontSize: 13, boxSizing: 'border-box' }} />
              </div>
              <div style={{ padding: 12, overflow: 'auto', flex: '1 1 auto' }}>
                {catalog === null ? (
                  <div style={{ ...msg }}>{tr.ecLoadingPlugins}</div>
                ) : secs.length === 0 ? (
                  <div style={{ ...msg }}>{tr.ecNoPluginMatch}</div>
                ) : secs.map((sec) => (
                  <div key={sec.key} style={{ marginBottom: 18 }}>
                    {/* section header — the Melis section logo + label, like the legacy plugin menu */}
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, margin: '2px 4px 10px' }}>
                      <MelisSectionIcon sectionKey={sec.key} size={22} />
                      <span style={{ fontSize: 14, fontWeight: 700, color: 'var(--color-foreground,#111827)' }}>{sectionLabel(sec.key, sec.label)}</span>
                    </div>
                    {sec.modules.map((mod) => (
                      <div key={mod.key} style={{ marginBottom: 12 }}>
                        {/* module header — a tinted bar, clearly distinct from the subcategory labels
                            below it (only shown when the section groups several modules, like legacy) */}
                        {sec.modules.length > 1 && (
                          <div style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 12, fontWeight: 700, color: 'var(--color-foreground,#111827)', background: 'color-mix(in srgb, var(--color-primary,#dc2626) 9%, transparent)', borderRadius: 6, padding: '6px 10px', margin: '10px 0 8px' }}>
                            <span style={{ width: 4, height: 14, borderRadius: 2, background: 'var(--color-primary,#dc2626)' }} />
                            {mod.label}
                          </div>
                        )}
                        {mod.groups.map((g) => (
                          <div key={g.id || '_'} style={{ marginBottom: 8 }}>
                            {g.title && <div style={{ fontSize: 10, fontWeight: 700, letterSpacing: .4, textTransform: 'uppercase', color: 'var(--color-muted-foreground,#6b7280)', margin: '4px 4px 6px 4px' }}>{g.title}</div>}
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(150px, 1fr))', gap: 10 }}>
                              {g.plugins.map((p) => (
                                <button key={p.module + '/' + p.name} data-testid={`palette-item-${p.name}`} disabled={saving}
                                  title={p.description || p.title}
                                  onClick={() => addPluginFromCatalog(pluginPicker.cellId, p)}
                                  style={{ display: 'flex', flexDirection: 'column', alignItems: 'stretch', gap: 6, textAlign: 'center', padding: 8, borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-background,#fff)', color: 'inherit', cursor: saving ? 'not-allowed' : 'pointer', opacity: saving ? .6 : 1 }}>
                                  {/* thumbnail ON TOP, larger, original aspect kept (contain → no crop) */}
                                  <span style={{ width: '100%', height: 84, borderRadius: 6, overflow: 'hidden', display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'color-mix(in srgb, var(--color-muted-foreground,#6b7280) 8%, transparent)', color: 'var(--color-primary,#dc2626)', fontWeight: 700, fontSize: 22 }}>
                                    {p.thumbnail
                                      ? <img src={p.thumbnail} alt="" style={{ maxWidth: '100%', maxHeight: '100%', objectFit: 'contain', display: 'block' }} onError={(e) => { (e.currentTarget as HTMLImageElement).style.display = 'none' }} />
                                      : (p.title || p.name).slice(0, 1).toUpperCase()}
                                  </span>
                                  <span style={{ fontSize: 12, fontWeight: 600, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{p.title}</span>
                                </button>
                              ))}
                            </div>
                          </div>
                        ))}
                      </div>
                    ))}
                  </div>
                ))}
              </div>
            </div>
          </div>
        )
      })()}

      {/* Page picker requested by a config iframe field (bridged via postMessage). Native React tree →
          answers the page id back to the iframe that asked. Above the config modal. */}
      {pagePicker && (
        <div style={{ position: 'fixed', inset: 0, zIndex: 92, background: 'rgba(0,0,0,.45)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }} onClick={() => setPagePicker(null)}>
          <div onClick={(e) => e.stopPropagation()} style={{ width: 'min(460px, 94vw)', background: 'var(--color-card,#fff)', color: 'var(--color-foreground,#111827)', borderRadius: 12, boxShadow: '0 24px 70px rgba(0,0,0,.45)', overflow: 'visible' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '12px 16px', borderBottom: '1px solid var(--color-border,#e5e7eb)' }}>
              <span style={{ fontWeight: 700, fontSize: 14 }}>{tr.ecSelectPage}</span>
              <button onClick={() => setPagePicker(null)} title={tr.ecClose} style={{ ...iconBtn, marginLeft: 'auto', width: 26, height: 26 }}>✕</button>
            </div>
            <div style={{ padding: 16 }}>
              <PagePicker value={Number(pagePicker.value) || 0}
                onChange={(id) => { try { pagePicker.source.postMessage({ type: 'melis-page-picked', pageId: String(id) }, '*') } catch { /* iframe gone */ } setPagePicker(null) }} />
            </div>
          </div>
        </div>
      )}

      {/* Confirm before removing a plugin from a zone (destructive — the block disappears from the page). */}
      {confirmRemove && (
        <div data-testid="confirm-remove" style={{ position: 'fixed', inset: 0, zIndex: 95, background: 'rgba(0,0,0,.45)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }} onClick={() => setConfirmRemove(null)}>
          <div onClick={(e) => e.stopPropagation()} style={{ width: 'min(420px, 94vw)', background: 'var(--color-card,#fff)', color: 'var(--color-foreground,#111827)', borderRadius: 12, boxShadow: '0 24px 70px rgba(0,0,0,.45)', overflow: 'hidden' }}>
            <div style={{ padding: '16px 18px 6px', fontWeight: 700, fontSize: 15 }}>{tr.ecRemovePluginTitle}</div>
            <div style={{ padding: '0 18px 16px', fontSize: 13, color: 'var(--color-muted-foreground,#6b7280)' }}>
              « {confirmRemove.label} » {tr.ecRemovePluginBody1}
            </div>
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, padding: '12px 16px', borderTop: '1px solid var(--color-border,#e5e7eb)' }}>
              <button data-testid="confirm-remove-cancel" onClick={() => setConfirmRemove(null)}
                style={{ appearance: 'none', border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', color: 'var(--color-foreground,#111827)', borderRadius: 6, padding: '7px 14px', fontSize: 13, fontWeight: 600, cursor: 'pointer' }}>{tr.cancel}</button>
              <button data-testid="confirm-remove-ok" onClick={() => { removeBlock(confirmRemove.zoneId, confirmRemove.refId); setConfirmRemove(null) }}
                style={{ appearance: 'none', border: '1px solid #dc2626', background: '#dc2626', color: '#fff', borderRadius: 6, padding: '7px 14px', fontSize: 13, fontWeight: 700, cursor: 'pointer' }}>{tr.ecRemoveBtn}</button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
