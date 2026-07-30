import { Fragment, useEffect, useLayoutEffect, useRef, useState, type CSSProperties, type ChangeEvent, type RefObject } from 'react'
import { useLocation, useNavigate, useSearchParams } from 'react-router-dom'
import {
  consumeMiniTemplateListStale, deleteMiniTemplate, fetchAllMiniTemplates, fetchMiniTemplateItem,
  fetchMiniTemplateSites, fetchMiniTemplateStats, fetchMiniTemplates, markMiniTemplateListStale, saveMiniTemplate,
  type MiniTemplateItem, type MiniTemplateSiteOption, type MiniTemplateStats,
} from './mini-template-api'
import { useKeysetList } from './use-keyset-list'
import { ExportModal, DownloadIcon } from './ExportModal'
import { ViewToggle, type ViewMode } from './ViewToggle'
import { useIsNarrow } from './shared/useIsNarrow'
import { ExpandToggle, HiddenColsRow } from './shared/ExpandableRow'

/* ──────────────────────────────────────────────────────────────────────────
 * Mini-Template Manager (MelisCms) — brique full React
 * Monté à /melis-cms/mini-templates. Liste + formulaire ajout/édition.
 * Identificateur composite (site_module + template_name) — pas de PK numérique.
 * ────────────────────────────────────────────────────────────────────────── */

const MELIS_KEY = 'meliscms_mini_template_manager_tool'
function can(cap: string): boolean {
  return (window as unknown as { MelisCan?: (k: string, c: string) => boolean }).MelisCan?.(MELIS_KEY, cap) ?? true
}

// API sub-tabs de l'hôte (la brique ne peut pas importer le contexte React de l'hôte)
type SubTabW = {
  __melisOpenSubTab?: (section: string, tab: { id: string; label: string; path: string }) => void
  __melisCloseSubTab?: (section: string, id: string) => void
  __melisUpdateSubTabLabel?: (section: string, id: string, label: string) => void
  __melisSetToolView?: (melisKey: string, view: ViewMode) => void
}

// Le segment /:id de la route = juste le NOM du mini-template (…/mini-templates/<name>).
// Le site propriétaire est résolu par nom à l'édition (cf. MiniTemplateForm). Les anciens liens
// composites `site~name` restent compris (rétro-compat).
const idFor = (_site: string, name: string) => name
function decodeId(id: string): { site: string; name: string } {
  const i = id.indexOf('~')
  return i === -1 ? { site: '', name: id } : { site: id.slice(0, i), name: id.slice(i + 1) }
}

// ── i18n minimal ──
type Lang = 'fr' | 'en'
function currentLang(): Lang {
  const l = (document.documentElement.lang || 'en').toLowerCase()
  return l.startsWith('fr') ? 'fr' : 'en'
}
const DICT: Record<Lang, Record<string, string>> = {
  fr: {
    title: 'Mini-Templates', subtitle: 'Blocs HTML réutilisables (TinyMCE)',
    new: 'Nouveau template', search: 'Rechercher…',
    empty_site: 'Sélectionnez un site pour voir ses templates.',
    empty: 'Aucun template trouvé.', count: '{n} templates — fin de la liste',
    kpi_total: 'Total', kpi_sites: 'Sites',
    all_sites: 'Tous les sites', select_site: '— Choisir un site —',
    col_thumbnail: 'Image', col_path: 'Chemin',
    columns: 'Colonnes', export: 'Exporter',
    cols_visible: 'Visibles', cols_hidden: 'Masquées', drag_here: 'Glisser ici', reset: 'Réinitialiser', reset_filters: 'Réinitialiser les filtres',
    edit: 'Modifier', del: 'Supprimer', cancel: 'Annuler', save: 'Enregistrer', back: 'retour',
    refresh: 'Rafraîchir', loading: 'Chargement…', saved: 'Enregistré ✓',
    del_title: 'Supprimer le template',
    del_confirm: 'Supprimer « {n} » ? Cette action est irréversible.',
    new_title: 'Nouveau mini-template', edit_title: 'Modifier le mini-template',
    add_to_category: 'Ce template sera ajouté à la catégorie « {n} ».',
    f_site: 'Site / Module', f_name: 'Nom du template',
    f_name_ph: 'mon_template', f_name_hint: 'Lettres, chiffres et _ uniquement. Doit commencer par une lettre ou _.',
    f_name_invalid: 'Nom invalide (lettres, chiffres, underscore ; commence par lettre ou _).',
    f_html: 'Contenu HTML', f_html_ph: '<!-- HTML du bloc -->',
    f_thumbnail: 'Miniature (png, jpg, gif)', f_thumbnail_hint: 'Optionnel. Formats acceptés : PNG, JPG, JPEG, GIF.',
    f_thumbnail_change: 'Changer la miniature',
    err_site: 'Le site est obligatoire.', err_name: 'Le nom est obligatoire.', err_save: 'Erreur lors de la sauvegarde.',
    export_filename: 'mini-templates',
    no_access: "Vous n’avez pas les droits pour consulter cette liste.",
  },
  en: {
    title: 'Mini-Templates', subtitle: 'Reusable HTML blocks (TinyMCE)',
    new: 'New template', search: 'Search…',
    empty_site: 'Select a site to view its templates.',
    empty: 'No template found.', count: '{n} templates — end of list',
    kpi_total: 'Total', kpi_sites: 'Sites',
    all_sites: 'All sites', select_site: '— Choose a site —',
    col_thumbnail: 'Image', col_path: 'Path',
    columns: 'Columns', export: 'Export',
    cols_visible: 'Visible', cols_hidden: 'Hidden', drag_here: 'Drag here', reset: 'Reset', reset_filters: 'Reset filters',
    edit: 'Edit', del: 'Delete', cancel: 'Cancel', save: 'Save', back: 'back',
    refresh: 'Refresh', loading: 'Loading…', saved: 'Saved ✓',
    del_title: 'Delete template',
    del_confirm: 'Delete "{n}"? This action is irreversible.',
    new_title: 'New mini-template', edit_title: 'Edit mini-template',
    add_to_category: 'This template will be added to the "{n}" category.',
    f_site: 'Site / Module', f_name: 'Template name',
    f_name_ph: 'my_template', f_name_hint: 'Letters, digits and _ only. Must start with a letter or _.',
    f_name_invalid: 'Invalid name (letters, digits, underscore; starts with letter or _).',
    f_html: 'HTML content', f_html_ph: '<!-- HTML block -->',
    f_thumbnail: 'Thumbnail (png, jpg, gif)', f_thumbnail_hint: 'Optional. Accepted formats: PNG, JPG, JPEG, GIF.',
    f_thumbnail_change: 'Change thumbnail',
    err_site: 'Site is required.', err_name: 'Name is required.', err_save: 'Error while saving.',
    export_filename: 'mini-templates',
    no_access: 'You do not have permission to view this list.',
  },
}
function useT() {
  const lang = currentLang()
  return (key: string, vars?: Record<string, string | number>) => {
    let s = DICT[lang][key] ?? key
    if (vars) for (const [k, v] of Object.entries(vars)) s = s.replaceAll(`{${k}}`, String(v))
    return s
  }
}

// ── Styles ──
const card: CSSProperties = { border: '1px solid var(--color-border)', background: 'var(--color-card)', borderRadius: 12, boxShadow: '0 1px 2px rgba(0,0,0,.04)' }
const inputCss: CSSProperties = { height: 40, width: '100%', boxSizing: 'border-box', borderRadius: 8, border: '1px solid var(--color-input,var(--color-border))', background: 'var(--color-card)', color: 'var(--color-foreground)', padding: '0 12px', fontSize: 14, outline: 'none' }
const btnPrimary: CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 36, padding: '0 14px', borderRadius: 8, border: 0, background: 'var(--color-primary)', color: 'var(--color-primary-foreground,#fff)', fontSize: 14, fontWeight: 500, cursor: 'pointer' }
const btnGhost: CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 36, padding: '0 12px', borderRadius: 8, border: '1px solid var(--color-border)', background: 'var(--color-card)', color: 'var(--color-foreground)', fontSize: 14, cursor: 'pointer' }
const iconBtn: CSSProperties = { display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 28, height: 28, borderRadius: 6, border: 0, background: 'transparent', color: 'var(--color-muted-foreground)', cursor: 'pointer' }
const th: CSSProperties = { textAlign: 'left', padding: '10px 16px', fontSize: 11, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.04em', color: 'var(--color-muted-foreground)', whiteSpace: 'nowrap' }
const td: CSSProperties = { padding: '10px 16px', fontSize: 14, color: 'var(--color-foreground)', borderTop: '1px solid var(--color-border)' }
const lbl: CSSProperties = { display: 'block', fontSize: 13, fontWeight: 500, marginBottom: 4, color: 'var(--color-foreground)' }
const hint: CSSProperties = { marginTop: 4, fontSize: 12, color: 'var(--color-muted-foreground)' }

const sIcon = { width: 15, height: 15, flexShrink: 0 } as const
const PencilIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>
const TrashIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /></svg>
const PlusIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14M5 12h14" /></svg>
const ResetIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 2v6h6" /><path d="M3 13a9 9 0 1 0 3-7.7L3 8" /></svg>
// Icônes de tri : neutre (ArrowUpDown) sur les en-têtes triables non actives, flèche haut/bas sur l'active.
const sortSvg = { width: 12, height: 12, flexShrink: 0, marginLeft: 4, verticalAlign: 'middle', display: 'inline-block', opacity: 0.75 } as const
const ArrowUpDown = () => <svg style={sortSvg} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m21 16-4 4-4-4" /><path d="M17 20V4" /><path d="m3 8 4-4 4 4" /><path d="M7 4v16" /></svg>
const ArrowUp = () => <svg style={sortSvg} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><path d="m5 12 7-7 7 7" /><path d="M12 19V5" /></svg>
const ArrowDown = () => <svg style={sortSvg} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14" /><path d="m19 12-7 7-7-7" /></svg>
const SortArrow = ({ col, sortCol, sortDir }: { col: string; sortCol: string; sortDir: 'asc' | 'desc' }) =>
  sortCol === col ? (sortDir === 'asc' ? <ArrowUp /> : <ArrowDown />) : <ArrowUpDown />
const SpinnerIcon = () => <svg style={{ width: 14, height: 14, animation: 'melis-spin 0.7s linear infinite' }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round"><path d="M21 12a9 9 0 1 1-6.219-8.56" /></svg>

// ── Column manager ──
type ColDef = { id: string; visible: boolean }
const DEFAULT_COLS: ColDef[] = [
  { id: 'thumbnail', visible: true },
  { id: 'path', visible: true },
]
const COL_KEY = 'melis-cms-mini-templates-cols-v2'
const COL_LABEL: Record<string, string> = { thumbnail: 'col_thumbnail', path: 'col_path' }
function loadCols(): ColDef[] {
  try {
    const raw = localStorage.getItem(COL_KEY)
    if (!raw) return DEFAULT_COLS
    const saved: ColDef[] = JSON.parse(raw)
    const ordered = saved.map((s) => { const d = DEFAULT_COLS.find((c) => c.id === s.id); return d ? { id: d.id, visible: s.visible } : null }).filter(Boolean) as ColDef[]
    const missing = DEFAULT_COLS.filter((d) => !saved.find((s) => s.id === d.id))
    return [...ordered, ...missing]
  } catch { return DEFAULT_COLS }
}
function saveCols(c: ColDef[]) { try { localStorage.setItem(COL_KEY, JSON.stringify(c)) } catch { /* */ } }
const visibleCols = (c: ColDef[]) => c.filter((x) => x.visible)

const GripIcon = () => <svg style={{ width: 13, height: 13, color: 'var(--color-muted-foreground)' }} viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" /><circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" /><circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" /></svg>
const panelCss: CSSProperties = { display: 'flex', flexDirection: 'column', gap: 2, minHeight: 120, maxHeight: 'min(48vh, 320px)', overflowY: 'auto', minWidth: 0, borderRadius: 8, border: '1px dashed var(--color-border)', padding: 6 }
const panelTitle: CSSProperties = { padding: '0 6px 4px', fontSize: 10, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.06em', color: 'var(--color-muted-foreground)' }

function ColManager({ cols, labelFor, onChange, onClose, anchorRef }: {
  cols: ColDef[]; labelFor: (id: string) => string; onChange: (c: ColDef[]) => void; onClose: () => void
  anchorRef: RefObject<HTMLButtonElement | null>
}) {
  const t = useT()
  const [dragId, setDragId] = useState<string | null>(null)
  const [over, setOver] = useState<{ id: string; panel: 'visible' | 'hidden' } | null>(null)
  const shown = cols.filter((c) => c.visible)
  const hidden = cols.filter((c) => !c.visible)

  // Position clampée (viewport), calculée depuis le bouton ancre plutôt qu'un simple `right: 0` —
  // sinon le popover peut border son propre bord gauche hors écran une fois les boutons du filtre
  // wrappés sur narrow (cf. skill melis-react-mobile-responsive, piège ColManager).
  const [pos, setPos] = useState<{ top: number; left: number; width: number } | null>(null)
  useLayoutEffect(() => {
    const margin = 8
    const width = Math.min(380, window.innerWidth - margin * 2)
    const rect = anchorRef.current?.getBoundingClientRect()
    const right = rect ? rect.right : window.innerWidth - margin
    const top = rect ? rect.bottom + 6 : margin
    const left = Math.min(Math.max(margin, right - width), window.innerWidth - width - margin)
    setPos({ top, left, width })
  }, [anchorRef])

  function drop(panel: 'visible' | 'hidden') {
    if (!dragId) return
    const src = cols.find((c) => c.id === dragId)!
    const upd = { ...src, visible: panel === 'visible' }
    let vList = shown.filter((c) => c.id !== dragId)
    const hList = hidden.filter((c) => c.id !== dragId)
    if (panel === 'visible') {
      const dst = over?.id
      if (!dst || dst === '__panel__') vList = [...vList, upd]
      else { const i = vList.findIndex((c) => c.id === dst); vList = i === -1 ? [...vList, upd] : [...vList.slice(0, i), upd, ...vList.slice(i)] }
      const next = [...vList, ...hList]; onChange(next); saveCols(next)
    } else { const next = [...vList, ...hList, upd]; onChange(next); saveCols(next) }
    setDragId(null); setOver(null)
  }

  function colItem(col: ColDef, panel: 'visible' | 'hidden') {
    const isOver = over?.id === col.id && over?.panel === panel
    return (
      <div key={col.id} draggable
        onDragStart={() => setDragId(col.id)} onDragEnd={() => { setDragId(null); setOver(null) }}
        onDragOver={(e) => { e.preventDefault(); e.stopPropagation(); if (over?.id !== col.id || over?.panel !== panel) setOver({ id: col.id, panel }) }}
        onDrop={(e) => { e.preventDefault(); drop(panel) }}
        style={{ display: 'flex', alignItems: 'center', gap: 8, borderRadius: 8, padding: '6px 8px', fontSize: 14, cursor: 'grab', userSelect: 'none', opacity: dragId === col.id ? 0.4 : 1, background: isOver ? 'color-mix(in srgb, var(--color-primary) 12%, transparent)' : 'transparent' }}>
        <GripIcon /><span style={{ flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{labelFor(col.id)}</span>
      </div>
    )
  }

  if (!pos) return null
  return (
    <div style={{ ...card, position: 'fixed', top: pos.top, left: pos.left, zIndex: 50, width: pos.width }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '10px 12px', borderBottom: '1px solid var(--color-border)' }}>
        <span style={{ fontSize: 14, fontWeight: 600 }}>{t('columns')}</span>
        <button style={{ ...iconBtn, width: 22, height: 22 }} onClick={onClose}>✕</button>
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8, padding: 12 }}>
        <div style={panelCss} onDragOver={(e) => { e.preventDefault(); if (over?.id !== '__panel__' || over?.panel !== 'hidden') setOver({ id: '__panel__', panel: 'hidden' }) }} onDrop={(e) => { e.preventDefault(); drop('hidden') }}>
          <p style={panelTitle}>{t('cols_hidden')}</p>
          {hidden.length === 0 ? <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 11, color: 'var(--color-muted-foreground)', opacity: 0.5, padding: '12px 0' }}>{t('drag_here')}</div> : hidden.map((c) => colItem(c, 'hidden'))}
        </div>
        <div style={panelCss} onDragOver={(e) => { e.preventDefault(); if (over?.id !== '__panel__' || over?.panel !== 'visible') setOver({ id: '__panel__', panel: 'visible' }) }} onDrop={(e) => { e.preventDefault(); drop('visible') }}>
          <p style={panelTitle}>{t('cols_visible')}</p>
          {shown.length === 0 ? <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 11, color: 'var(--color-muted-foreground)', opacity: 0.5, padding: '12px 0' }}>{t('drag_here')}</div> : shown.map((c) => colItem(c, 'visible'))}
        </div>
      </div>
      <div style={{ borderTop: '1px solid var(--color-border)', padding: 6 }}>
        <button style={{ ...btnGhost, width: '100%', height: 30, border: 0, justifyContent: 'center', color: 'var(--color-muted-foreground)' }}
          onClick={() => { onChange(DEFAULT_COLS); saveCols(DEFAULT_COLS) }}>{t('reset')}</button>
      </div>
    </div>
  )
}

// ── KPI card ──
function Kpi({ label: l, value }: { label: string; value: number | null }) {
  return (
    <div style={{ ...card, display: 'flex', flexDirection: 'column', gap: 2, padding: 16, flex: 1, minWidth: 140 }}>
      <span style={{ fontSize: 12, color: 'var(--color-muted-foreground)' }}>{l}</span>
      <span style={{ fontSize: 22, fontWeight: 700 }}>{value == null ? '…' : value}</span>
    </div>
  )
}

// ── Page root ──
// Route-based (comme Templates / Platforms IDs) : /melis-cms/mini-templates (liste),
// /melis-cms/mini-templates/new (création) et /melis-cms/mini-templates/:id (édition).
// Le routage par URL est la condition des sous-onglets (subTabs) : chaque édition ouvre
// son propre onglet côté hôte → plusieurs entrées éditables en parallèle.
export default function MiniTemplatePage({ active = true }: { active?: boolean }) {
  const location = useLocation()
  // ⚠️ Les briques sont montées par Shell HORS de la route `:id` de l'hôte (rendu direct, pas via
  // l'Outlet) : `useParams()` ne voit jamais le segment `:id` ici → il faut dériver l'id du pathname.
  // La brique est montée sur 2 segments (/melis-cms/mini-templates) ; un 3e segment (`new` ou l'id
  // composite site~name) déclenche le formulaire en sous-onglet.
  // Persistante (manifest) : on GÈLE le pathname quand inactive, sinon un pathname étranger dériverait
  // un id étranger → bascule formulaire + fetch d'un autre outil = détournement. Cf. skill.
  const [frozenPath, setFrozenPath] = useState(location.pathname)
  useEffect(() => { if (active) setFrozenPath(location.pathname) }, [active, location.pathname])
  const segs = (active ? location.pathname : frozenPath).replace(/^\/+|\/+$/g, '').split('/')
  const base = '/' + segs.slice(0, 2).join('/')
  // Segments après /mini-templates. La modal IA est une SOUS-route du formulaire courant :
  //   …/mini-templates/<name>/ai-generator (édition)  ·  …/mini-templates/new/ai-generator (création)
  // Fermer la modal retire juste ce dernier segment → on revient au formulaire (le template édité).
  const rest = segs.slice(2)
  const aiOpen = rest[rest.length - 1] === 'ai-generator'
  const idSegs = aiOpen ? rest.slice(0, -1) : rest
  // …/mini-templates/ai-generator (nu) = nouveau template + modal ouverte (rétro-compat).
  const id = idSegs.length ? idSegs.join('/') : (aiOpen ? 'new' : undefined)

  // Vue courante du toggle New/Old. Portée par la RACINE (et non par la liste) : la liste est
  // démontée dès qu'un formulaire s'ouvre, et l'hôte doit continuer à connaître la vue active.
  const [mode, setMode] = useState<ViewMode>('react')
  // Publier la vue à l'hôte (cf. melis-core lib/tool-view-mode) : tant qu'on est en vue React,
  // l'iframe « Old » — ou son pont d'onglets, dont l'état survit à son démontage — ne doit pas
  // afficher SES onglets legacy à côté des sous-onglets React (deux onglets « Nouveau » pour le
  // même écran). En vue Old, symétriquement, l'hôte masque les sous-onglets React.
  // Un formulaire ouvert (id) est par nature la vue React.
  const view: ViewMode = id ? 'react' : mode
  useEffect(() => { (window as unknown as SubTabW).__melisSetToolView?.(MELIS_KEY, view) }, [view])

  // key={id} : forcer un remount frais à chaque changement de sous-onglet (l'identité vient de l'URL
  // et initialise l'état). L'ouverture/fermeture de la modal IA ne change PAS `id` (juste le segment
  // ai-generator en plus) → même key → pas de remount → le HTML inséré survit à la fermeture.
  if (id) return <MiniTemplateForm key={id} id={id} base={base} aiOpen={aiOpen} />
  return <MiniTemplateList base={base} mode={mode} setMode={setMode} />
}

// ── Persistent list cache ──
// Stocke le snapshot keyset (items/total/cursor/hasMore/sortCol/sortDir) + les filtres et l'UI,
// pour restaurer la liste à l'identique au retour depuis un formulaire (liste montée une seule fois).
type ListCache = {
  items: MiniTemplateItem[]
  total: number
  cursor: string | null
  hasMore: boolean
  sortCol: string
  sortDir: 'asc' | 'desc'
  stats: MiniTemplateStats | null
  sites: MiniTemplateSiteOption[]
  site: string
  search: string
  searchInput: string
  cols: ColDef[]
}
let _cache: ListCache | null = null

// ── List ──
// `mode` vient de la racine (MiniTemplatePage) : la vue du toggle doit survivre au démontage de la
// liste quand un formulaire s'ouvre, et rester publiée à l'hôte.
function MiniTemplateList({ base, mode, setMode }: { base: string; mode: ViewMode; setMode: (m: ViewMode) => void }) {
  const t = useT()
  const navigate = useNavigate()
  const narrow = useIsNarrow()
  const colBtnRef = useRef<HTMLButtonElement>(null)
  const [expandedRows, setExpandedRows] = useState<Set<string>>(new Set())
  function toggleExpanded(key: string) {
    setExpandedRows((prev) => { const next = new Set(prev); next.has(key) ? next.delete(key) : next.add(key); return next })
  }

  const [stats, setStats]             = useState<MiniTemplateStats | null>(_cache?.stats ?? null)
  const [sites, setSites]             = useState<MiniTemplateSiteOption[]>(_cache?.sites ?? [])
  const [site, setSite]               = useState(_cache?.site ?? '')
  const [searchInput, setSearchInput] = useState(_cache?.searchInput ?? '')
  const [search, setSearch]           = useState(_cache?.search ?? '')
  const [cols, setCols]               = useState<ColDef[]>(_cache?.cols ?? loadCols())
  const [showCols, setShowCols]       = useState(false)
  const [showExport, setShowExport]   = useState(false)
  const [toDelete, setToDelete]       = useState<MiniTemplateItem | null>(null)
  const [tick, setTick]               = useState(0)
  const [frameLoaded, setFrameLoaded] = useState(mode === 'iframe')

  // Scroll infini + tri server-side + keyset (mutualisé). Restauré depuis le cache module-level à la
  // navigation (`initial` + `skipInitial`). Le fetcher capture les filtres courants (site, search) ;
  // `deps` relance un chargement frais à chaque changement (ou refresh via `tick`). `site===''` →
  // l'API renvoie une liste vide (nextCursor null), pas de scroll infini parasite.
  const {
    items, total, loading, hasMore, sentinelRef, sortCol, sortDir, toggleSort, reload, removeLocal, snapshot,
  } = useKeysetList<MiniTemplateItem>({
    fetcher: (a) => fetchMiniTemplates({ ...a, site, search }),
    deps: [site, search, tick],
    limit: 50,
    defaultSort: 'path',
    defaultDir: 'asc',
    initial: _cache
      ? { items: _cache.items, total: _cache.total, cursor: _cache.cursor, hasMore: _cache.hasMore, sortCol: _cache.sortCol, sortDir: _cache.sortDir }
      : undefined,
    skipInitial: !!(_cache && _cache.items.length),
  })

  // Snapshot cache on unmount (survit au démontage quand un formulaire s'ouvre).
  const snapRef = useRef(snapshot); snapRef.current = snapshot
  const stateRef = useRef({ stats, sites, site, search, searchInput, cols })
  stateRef.current = { stats, sites, site, search, searchInput, cols }
  useEffect(() => () => {
    _cache = { ...snapRef.current(), ...stateRef.current }
  }, [])

  // Stale-flag : reload after add/edit
  useEffect(() => {
    if (consumeMiniTemplateListStale()) { _cache = null; setTick((x) => x + 1) }
  }, [])

  useEffect(() => {
    fetchMiniTemplateStats().then(setStats).catch(() => null)
  }, [tick])

  useEffect(() => {
    fetchMiniTemplateSites().then((s) => {
      setSites(s)
      if (!site && s.length > 0) setSite(s[0].module)
    }).catch(() => null)
  }, [])

  // Réinitialiser les filtres : recherche + site + tri par défaut (chemin asc), puis refetch.
  // Le site revient au PREMIER site (l'état par défaut de la page, cf. l'effet de chargement des
  // sites), pas à « tous » : sans site sélectionné la liste n'affiche rien, et l'effet qui pose ce
  // défaut ne tourne qu'au montage.
  function resetFilters() {
    setSearchInput(''); setSearch('')
    setSite(sites[0]?.module ?? '')
    setTick((x) => x + 1)
  }

  async function confirmDelete() {
    if (!toDelete) return
    try {
      await deleteMiniTemplate(toDelete.site, toDelete.name)
      removeLocal((r) => r.site === toDelete.site && r.name === toDelete.name)
      setToDelete(null)
      reload()
    } catch { setToDelete(null) }
  }

  // Collapse à la seule colonne essentielle (chemin) sur narrow, quel que soit le réglage
  // ColManager de l'utilisateur — cf. skill melis-react-mobile-responsive.
  const displayCols = narrow ? cols.map((c) => ({ ...c, visible: c.id === 'path' })) : cols
  const hasHidden = narrow

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: narrow ? 14 : 20, padding: narrow ? 14 : 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      <style>{'@keyframes melis-spin{to{transform:rotate(360deg)}}'}</style>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 }}>
        <div style={narrow ? { minWidth: 0 } : undefined}>
          <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0, ...(narrow ? { overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } : {}) }}>{t('title')}</h1>
          <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', margin: '2px 0 0', ...(narrow ? { overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } : {}) }}>{t('subtitle')}</p>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexShrink: 0 }}>
          <ViewToggle mode={mode} onChange={(m) => { setMode(m); if (m === 'iframe') setFrameLoaded(true) }} compact={narrow} />
          <button style={btnGhost} onClick={() => { _cache = null; setTick((x) => x + 1) }} title={t('refresh')}>↻</button>
          {can('create') && <button style={btnPrimary} onClick={() => navigate(`${base}/new`)} title={t('new')}><PlusIcon />{!narrow && t('new')}</button>}
        </div>
      </div>

      {/* Vue Old (iframe) */}
      {frameLoaded && (
        <div style={{ ...card, display: mode === 'iframe' ? 'flex' : 'none', flex: 1, minHeight: 480, overflow: 'hidden' }}>
          <iframe src={`/melis/react-tool-page?key=${encodeURIComponent(MELIS_KEY)}`}
            style={{ flex: 1, width: '100%', border: 0 }} title="Mini-Templates — Vue Melis"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-modals" />
        </div>
      )}

      {/* Vue New (React) */}
      <div style={{ display: mode === 'react' ? 'flex' : 'none', flexDirection: 'column', gap: 20 }}>
      {!can('list') ? (
        <div style={{ ...card, padding: '40px 16px', textAlign: 'center', fontSize: 14, color: 'var(--color-muted-foreground)' }}>{t('no_access')}</div>
      ) : (<>
        {/* KPI */}
        <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
          <Kpi label={t('kpi_total')} value={stats?.total ?? null} />
          <Kpi label={t('kpi_sites')} value={stats?.sites ?? null} />
        </div>

        {/* Filtres */}
        <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'center' }}>
          <input style={{ ...inputCss, height: 36, flex: narrow ? '1 1 100%' : 1, minWidth: narrow ? undefined : 180 }} value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && setSearch(searchInput.trim())}
            placeholder={t('search')} />
          <select style={{ ...inputCss, height: 36, width: narrow ? '100%' : 'auto', minWidth: narrow ? undefined : 160, flex: narrow ? '1 1 100%' : undefined }}
            value={site} onChange={(e) => setSite(e.target.value)}>
            <option value="">{t('all_sites')}</option>
            {sites.map((s) => <option key={s.module} value={s.module}>{s.name}</option>)}
          </select>
          {/* reset_filters : toujours sur sa propre ligne pleine largeur sur narrow — le libellé FR
              est trop long pour partager une ligne 50/50 avec Colonnes/Exporter (cf. skill). */}
          <button style={{ ...btnGhost, height: 36, flex: narrow ? '1 1 100%' : undefined, justifyContent: narrow ? 'center' : undefined }} onClick={resetFilters}><ResetIcon />{t('reset_filters')}</button>
          <div style={{ position: 'relative', flex: narrow ? '1 1 calc(50% - 4px)' : undefined }}>
            <button ref={colBtnRef} style={{ ...btnGhost, height: 36, width: narrow ? '100%' : undefined, justifyContent: narrow ? 'center' : undefined }} onClick={() => setShowCols((v) => !v)}><GripIcon />{t('columns')}</button>
            {showCols && <ColManager cols={cols} labelFor={(id) => t(COL_LABEL[id])} onChange={setCols} onClose={() => setShowCols(false)} anchorRef={colBtnRef} />}
          </div>
          {can('export') && (
            <button style={{ ...btnGhost, height: 36, flex: narrow ? '1 1 calc(50% - 4px)' : undefined, justifyContent: narrow ? 'center' : undefined }} onClick={() => setShowExport(true)}><DownloadIcon />{t('export')}</button>
          )}
        </div>

        {/* Table */}
        <div style={{ ...card, overflow: 'hidden' }}>
          {!site ? (
            <div style={{ padding: '48px 16px', textAlign: 'center', fontSize: 14, color: 'var(--color-muted-foreground)' }}>{t('empty_site')}</div>
          ) : (
            <table style={{ width: '100%', borderCollapse: 'collapse', ...(!narrow ? { minWidth: 480 } : {}) }}>
              <thead style={{ background: 'var(--color-muted,rgba(0,0,0,.03))' }}>
                <tr>
                  {hasHidden && <th style={{ ...th, width: 36 }} />}
                  {visibleCols(displayCols).map(({ id }) => (
                    <th key={id} style={{ ...th, ...(id === 'path' ? { cursor: 'pointer' } : {}), ...(id === 'thumbnail' ? { width: 80 } : {}) }}
                      onClick={id === 'path' ? () => toggleSort('path') : undefined}>
                      {t(COL_LABEL[id])}{id === 'path' ? <SortArrow col="path" sortCol={sortCol} sortDir={sortDir} /> : null}
                    </th>
                  ))}
                  <th style={{ ...th, width: 80 }} />
                </tr>
              </thead>
              <tbody>
                {items.length === 0 && !loading ? (
                  <tr><td style={{ ...td, textAlign: 'center', color: 'var(--color-muted-foreground)', padding: '40px 16px' }} colSpan={(hasHidden ? 1 : 0) + visibleCols(displayCols).length + 1}>{t('empty')}</td></tr>
                ) : items.map((r) => {
                  const rowKey = `${r.site}:${r.name}`
                  return (
                    <Fragment key={rowKey}>
                      <tr>
                        {hasHidden && <td style={td}><ExpandToggle expanded={expandedRows.has(rowKey)} onClick={() => toggleExpanded(rowKey)} /></td>}
                        {visibleCols(displayCols).map(({ id }) => (
                          <td key={id} style={td}>
                            {id === 'thumbnail' && (
                              r.thumbnailUrl
                                ? <img src={r.thumbnailUrl} alt={r.name} style={{ width: 52, height: 40, objectFit: 'cover', borderRadius: 4, display: 'block' }} />
                                : <div style={{ width: 52, height: 40, borderRadius: 4, background: 'var(--color-muted,rgba(0,0,0,.06))', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 10, color: 'var(--color-muted-foreground)' }}>—</div>
                            )}
                            {id === 'path' && <span style={{ fontFamily: 'monospace', fontSize: 13 }}>{r.path}</span>}
                          </td>
                        ))}
                        <td style={td}>
                          <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 4 }}>
                            {can('edit') && <button style={iconBtn} title={t('edit')} onClick={() => navigate(`${base}/${idFor(r.site, r.name)}`)}><PencilIcon /></button>}
                            {can('delete') && <button style={{ ...iconBtn, color: 'var(--color-destructive,#ef4444)' }} title={t('del')} onClick={() => setToDelete(r)}><TrashIcon /></button>}
                          </div>
                        </td>
                      </tr>
                      {hasHidden && expandedRows.has(rowKey) && (
                        <HiddenColsRow cols={displayCols} labelFor={(id) => t(COL_LABEL[id])}
                          renderValue={(id) => id === 'thumbnail' ? (
                            r.thumbnailUrl
                              ? <img src={r.thumbnailUrl} alt={r.name} style={{ width: 52, height: 40, objectFit: 'cover', borderRadius: 4, display: 'block' }} />
                              : '—'
                          ) : r.path}
                          colSpan={1 + visibleCols(displayCols).length + 1} narrow={narrow} />
                      )}
                    </Fragment>
                  )
                })}
              </tbody>
            </table>
          )}
          {/* Scroll infini : sentinelle observée → charge le lot suivant ; pied = spinner puis compteur final. */}
          {site && <div ref={sentinelRef} style={{ height: 1 }} />}
          {site && loading && (
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8, padding: '14px 0', fontSize: 12, color: 'var(--color-muted-foreground)' }}>
              <SpinnerIcon />{t('loading')}
            </div>
          )}
          {site && !hasMore && items.length > 0 && (
            <div style={{ padding: '10px 16px', textAlign: 'center', fontSize: 12, color: 'var(--color-muted-foreground)' }}>
              {t('count', { n: total })}
            </div>
          )}
        </div>
      </>)}
      </div>

      {/* Suppression */}
      {toDelete && (
        <div style={{ position: 'fixed', inset: 0, zIndex: 50, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.5)' }}>
          <div style={{ ...card, padding: 24, width: '100%', maxWidth: 360 }}>
            <h3 style={{ fontSize: 16, fontWeight: 600, margin: 0 }}>{t('del_title')}</h3>
            <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', marginTop: 8 }}>{t('del_confirm', { n: toDelete.name })}</p>
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 20 }}>
              <button style={btnGhost} onClick={() => setToDelete(null)}>{t('cancel')}</button>
              <button style={{ ...btnGhost, borderColor: '#fca5a5', color: '#dc2626' }} onClick={confirmDelete}>{t('del')}</button>
            </div>
          </div>
        </div>
      )}

      {showExport && (
        <ExportModal<MiniTemplateItem>
          cols={cols}
          labelFor={(id) => t(COL_LABEL[id])}
          fetchAll={async () => fetchAllMiniTemplates({ site, search })}
          getCell={(r, id) =>
            id === 'name' ? r.name :
            id === 'site' ? r.site :
            id === 'path' ? r.path :
            id === 'thumbnail' ? (r.thumbnailUrl ?? '') : ''
          }
          filename={t('export_filename')}
          sheetName={t('title')}
          total={total}
          onClose={() => setShowExport(false)}
        />
      )}
    </div>
  )
}

// ── TinyMCE — brick-local loader (mirrors MelisToolEditor from melis-core) ──
declare global {
  interface Window {
    tinymce?: any
    tinyMceCleaner?: (ed: any) => void
    __melisMiniTemplateExtensions?: {
      renderHtmlActions?: (
        onContent: (html: string, thumbnail?: File) => void,
        context: { site?: string; name?: string },
        controlled?: { open?: boolean; onOpenChange?: (open: boolean) => void }
      ) => import('react').ReactNode
    }
  }
}
const MCE_SRC     = '/MelisCore/js/library/tinymce/tinymce.min.js'
const MCE_CLEANER = '/MelisCore/js/tinyMCE/tinymce_cleaner.js'
const MCE_BASE    = '/MelisCore/js/library/tinymce'
const MCE_CONFIG  = '/melis/MelisCore/MelisTinyMce/preloadTinyMceConfig'
const EDITOR_ID   = 'mini-tpl-html-editor'

let _mceReady: Promise<boolean> | null = null
let _toolCfg: Record<string, unknown> | null = null

function loadScript(src: string): Promise<void> {
  return new Promise((resolve, reject) => {
    if (document.querySelector(`script[data-melis-src="${src}"]`)) { resolve(); return }
    const s = document.createElement('script')
    s.src = src; s.async = false; s.dataset.melisSrc = src
    s.onload = () => resolve()
    s.onerror = () => reject(new Error(`Failed to load ${src}`))
    document.head.appendChild(s)
  })
}

function ensureMce(): Promise<boolean> {
  if (_mceReady) return _mceReady
  _mceReady = (async () => {
    try {
      await loadScript(MCE_SRC)
      await loadScript(MCE_CLEANER).catch(() => {})
      const t0 = Date.now()
      while (!window.tinymce && Date.now() - t0 < 8000) { await new Promise((r) => setTimeout(r, 60)) }
      if (!window.tinymce) return false
      const res = await fetch(MCE_CONFIG, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'include' })
      const data = await res.json() as Record<string, unknown>
      _toolCfg = (data?.tool as Record<string, unknown>) ?? null
      return !!_toolCfg
    } catch { return false }
  })()
  return _mceReady
}

function TinyMceField({ value, onChange }: { value: string; onChange: (v: string) => void }) {
  const onChangeRef = useRef(onChange); onChangeRef.current = onChange
  const valueRef    = useRef(value);    valueRef.current    = value

  useEffect(() => {
    // Largeur capturée AU MONTAGE (pas via useIsNarrow) : une dépendance `narrow` re-initialiserait
    // l'éditeur au moindre resize → contenu en cours d'édition perdu. Le passage mobile↔desktop
    // en pleine édition est marginal, la ré-ouverture du formulaire recalcule.
    const narrow = window.innerWidth < 640
    let disposed = false
    ensureMce().then((ok) => {
      if (disposed || !ok || !window.tinymce || !_toolCfg) return
      const cfg: Record<string, unknown> = {
        ...JSON.parse(JSON.stringify(_toolCfg)),
        selector: `#${EDITOR_ID}`,
        base_url: MCE_BASE,
        // Sur mobile la barre d'outils (toolbar_mode 'sliding') mange déjà ~130px : 320 ne laisse
        // que ~185px de zone d'édition. Vérifié en rendu headless à 386px : 360 est le bon compromis.
        // (⚠️ NE PAS passer toolbar_mode à 'wrap' sur narrow : testé, la barre déroule 6 lignes et
        // recouvre toute la zone de contenu — 'sliding' est le bon mode en étroit.)
        height: narrow ? 360 : 320,
        min_height: narrow ? 360 : 320,
      }
      // Le HTML d'un mini-template est du markup desktop (Bootstrap, images à taille naturelle) et
      // l'iframe de contenu ne charge PAS le CSS du site : à 386px l'image sort du cadre et le bloc
      // est rogné. On contraint les médias À L'ÉDITION uniquement (content_style n'est jamais
      // enregistré → zéro impact sur le HTML produit) et seulement en étroit → desktop inchangé.
      if (narrow) {
        cfg.content_style = `${(cfg.content_style as string) ?? ''}\nimg,video,iframe,table,pre{max-width:100%!important;height:auto}body{overflow-x:hidden}`
      }
      delete cfg.mode
      // autoresize creates an absolutely-positioned observer that can overlay sibling elements
      if (Array.isArray(cfg.plugins)) {
        cfg.plugins = (cfg.plugins as string[]).filter((p) => p !== 'autoresize')
      }
      if (typeof cfg.toolbar === 'string') {
        cfg.toolbar = (cfg.toolbar as string).replace(/\binsertfile\b/g, '').replace(/\s{2,}/g, ' ').replace(/\|\s*\|/g, '|').trim()
      }
      cfg.init_instance_callback = typeof window.tinyMceCleaner === 'function' ? window.tinyMceCleaner : undefined
      cfg.setup = (editor: any) => {
        editor.on('init', () => { try { editor.setContent(valueRef.current || '') } catch { /* */ } })
        const push = () => { try { onChangeRef.current(editor.getContent()) } catch { /* */ } }
        editor.on('change keyup input undo redo SetContent blur', push)
      }
      try { window.tinymce.remove(`#${EDITOR_ID}`) } catch { /* */ }
      window.tinymce.init(cfg)
    })
    return () => {
      disposed = true
      try { const ed = window.tinymce?.get(EDITOR_ID); if (ed) ed.remove() } catch { /* */ }
    }
  }, [])

  return (
    <textarea
      id={EDITOR_ID}
      defaultValue={value}
      style={{ width: '100%', minHeight: 280, resize: 'vertical', fontFamily: 'monospace', fontSize: 13,
        borderRadius: 8, border: '1px solid var(--color-border)', padding: '10px 12px', boxSizing: 'border-box' }}
    />
  )
}

// ── Form (add / edit) ──
function MiniTemplateForm({ id, base, aiOpen = false }: { id: string; base: string; aiOpen?: boolean }) {
  const t    = useT()
  const navigate = useNavigate()
  const narrow = useIsNarrow()
  const [searchParams] = useSearchParams()
  const isEdit = id !== 'new'
  const { site: editSite, name: editName } = isEdit ? decodeId(id) : { site: '', name: '' }
  // Cross-tool navigation (ex. bouton « Add mini-template » du Menu manager) : présélectionne
  // le site via son id (?site=<siteId>) — résolu en module une fois la liste des sites chargée.
  const siteIdParam = !isEdit ? searchParams.get('site') : null
  // Menu manager « + » sur une catégorie : lie le nouveau template à cette catégorie (mtplc_id).
  // catName = libellé affiché dans le bandeau d'info (purement cosmétique).
  const categoryParam = !isEdit ? searchParams.get('category') : null
  const catNameParam = !isEdit ? searchParams.get('catName') : null

  const [sites, setSites]       = useState<MiniTemplateSiteOption[]>([])
  const [site, setSite]         = useState(editSite)
  // Site propriétaire d'origine (pour oldSite au save) : résolu par nom si l'URL ne le porte pas.
  const [origSite, setOrigSite] = useState(editSite)
  const [name, setName]         = useState(editName)
  const [html, setHtml]         = useState('')
  const [thumbUrl, setThumbUrl] = useState<string | null>(null)
  const [thumbFile, setThumbFile] = useState<File | null>(null)
  const [thumbPreview, setThumbPreview] = useState<string | null>(null)
  const [loading, setLoading]   = useState(false)
  const [saving, setSaving]     = useState(false)
  const [error, setError]       = useState<string | null>(null)
  const [saved, setSaved]       = useState(false)
  const [nameError, setNameError] = useState(false)
  const [siteError, setSiteError] = useState(false)
  const [nameRequired, setNameRequired] = useState(false)
  const fileRef = useRef<HTMLInputElement>(null)

  // Sous-onglet hôte : ouvre un onglet dédié à cette édition (édition multiple en parallèle).
  const subTabId = `${base}/${id}`
  useEffect(() => {
    const label = isEdit ? (editName || t('loading')) : t('new_title')
    ;(window as unknown as SubTabW).__melisOpenSubTab?.(base, { id: subTabId, label, path: subTabId })
  }, [])

  useEffect(() => { if (!can(isEdit ? 'edit' : 'create')) navigate(base) }, [isEdit, base, navigate])
  useEffect(() => {
    fetchMiniTemplateSites().then((s) => {
      setSites(s)
      if (!isEdit && !site) {
        const preselected = siteIdParam ? s.find((o) => String(o.id) === siteIdParam) : undefined
        if (preselected) setSite(preselected.module)
        else if (s.length > 0) setSite(s[0].module)
      }
    }).catch(() => null)
  }, [])

  // Load existing template HTML on edit. The URL carries only the NAME, so resolve the owning site
  // by name first (search across all sites); legacy site~name URLs already provide `editSite`.
  useEffect(() => {
    if (!isEdit) return
    setLoading(true)
    ;(async () => {
      let resolvedSite = editSite
      if (!resolvedSite) {
        // The list endpoint scans ONE site's folder, so a site-less search returns nothing: scan the
        // sites (last-used first) until one owns a template with this name.
        try {
          const siteOpts = await fetchMiniTemplateSites()
          const seen = new Set<string>()
          const candidates = [_cache?.site ?? '', ...siteOpts.map((s) => s.module)].filter(Boolean)
          for (const m of candidates) {
            if (seen.has(m)) continue
            seen.add(m)
            const list = await fetchAllMiniTemplates({ site: m })
            if (list.some((i) => i.name === editName)) { resolvedSite = m; break }
          }
        } catch { /* leave empty → back to list below */ }
      }
      if (!resolvedSite) { navigate(base); return }
      setSite(resolvedSite)
      setOrigSite(resolvedSite)
      const d = await fetchMiniTemplateItem(resolvedSite, editName)
      setHtml(d.html)
      setThumbUrl(d.thumbnailUrl)
      setThumbPreview(d.thumbnailUrl)
    })()
      .catch(() => navigate(base))
      .finally(() => setLoading(false))
  }, [id])

  function onNameChange(v: string) {
    setName(v)
    setNameError(v !== '' && !/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(v))
    if (v.trim()) setNameRequired(false)
  }

  function onThumbChange(e: ChangeEvent<HTMLInputElement>) {
    const f = e.target.files?.[0] ?? null
    setThumbFile(f)
    if (f) {
      const url = URL.createObjectURL(f)
      setThumbPreview(url)
    } else {
      setThumbPreview(thumbUrl)
    }
  }

  async function submit() {
    setError(null)
    const noSite = !site
    const noName = !name.trim()
    setSiteError(noSite)
    setNameRequired(noName)
    if (noSite || noName || nameError) return
    setSaving(true)
    try {
      await saveMiniTemplate({
        site,
        name: name.trim(),
        html: window.tinymce?.get(EDITOR_ID)?.getContent() ?? html,
        oldSite: isEdit ? origSite : undefined,
        oldName: isEdit ? editName : undefined,
        thumbnail: thumbFile,
        category: categoryParam ? Number(categoryParam) : undefined,
      })
      markMiniTemplateListStale()
      setSaved(true)
      // Fermer le sous-onglet de création : après save on revient à la liste,
      // l'onglet « Nouveau » vide ne doit pas subsister (cf. commerce ContactPage).
      if (!isEdit) (window as unknown as SubTabW).__melisCloseSubTab?.(base, subTabId)
      setTimeout(() => navigate(base), 600)
    } catch (e) {
      setError(e instanceof Error ? e.message : t('err_save'))
    } finally { setSaving(false) }
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: narrow ? 14 : 20, padding: narrow ? 14 : 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12, minWidth: 0 }}>
          {/* Pas de bouton « retour » ici : la barre de sous-onglets de l'hôte fournit déjà « ← Back ». */}
          <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0, ...(narrow ? { minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } : {}) }}>{isEdit ? t('edit_title') : t('new_title')}</h1>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10, flexShrink: 0 }}>
          {saved && !narrow && <span style={{ fontSize: 14, color: '#059669' }}>{t('saved')}</span>}
          <button style={btnPrimary} onClick={submit} disabled={saving || loading}>{saving ? '…' : saved && narrow ? t('saved') : t('save')}</button>
        </div>
      </div>

      {error && (
        <div style={{ ...card, borderColor: '#fca5a5', background: '#fef2f2', color: '#b91c1c', padding: '8px 14px', fontSize: 14 }}>{error}</div>
      )}

      {categoryParam && (
        <div style={{ ...card, borderColor: '#bfdbfe', background: '#eff6ff', color: '#1d4ed8', padding: '8px 14px', fontSize: 14 }}>
          {t('add_to_category', { n: catNameParam ?? '' })}
        </div>
      )}

      {/* ⚠️ `alignItems:'start'` en colonne (narrow) empêcherait l'étirement des cartes sur l'axe
          horizontal → largeur = contenu (le textarea/TinyMCE impose alors la largeur). En étroit
          on repasse donc à 'stretch' ; en grid (desktop) 'start' reste nécessaire pour aligner
          les 2 colonnes en haut. */}
      {loading ? (
        <div style={{ padding: 48, textAlign: 'center', color: 'var(--color-muted-foreground)' }}>{t('loading')}</div>
      ) : (
        <div style={{ display: narrow ? 'flex' : 'grid', flexDirection: narrow ? 'column' : undefined, gridTemplateColumns: narrow ? undefined : '2fr 1fr', gap: 20, alignItems: narrow ? 'stretch' : 'start' }}>
          {/* Colonne gauche : site + nom + HTML */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: 16, ...(narrow ? { minWidth: 0 } : {}) }}>
            <div style={{ ...card, padding: narrow ? 14 : 20 }}>
              <div style={{ marginBottom: 16 }}>
                <label style={lbl}>{t('f_site')}</label>
                <select style={{ ...inputCss, borderColor: siteError ? '#fca5a5' : undefined }}
                  value={site} onChange={(e) => { setSite(e.target.value); if (e.target.value) setSiteError(false) }}>
                  <option value="">{t('select_site')}</option>
                  {sites.map((s) => <option key={s.module} value={s.module}>{s.name}</option>)}
                </select>
                {siteError && <p style={{ ...hint, color: '#b91c1c' }}>{t('err_site')}</p>}
              </div>
              <div>
                <label style={lbl}>{t('f_name')}</label>
                <input style={{ ...inputCss, borderColor: (nameError || nameRequired) ? '#fca5a5' : undefined }}
                  value={name} onChange={(e) => onNameChange(e.target.value)}
                  placeholder={t('f_name_ph')} maxLength={128} autoComplete="off" />
                {nameRequired
                  ? <p style={{ ...hint, color: '#b91c1c' }}>{t('err_name')}</p>
                  : nameError
                    ? <p style={{ ...hint, color: '#b91c1c' }}>{t('f_name_invalid')}</p>
                    : <p style={hint}>{t('f_name_hint')}</p>
                }
              </div>
            </div>
            <div style={{ ...card, padding: narrow ? 14 : 20 }}>
              {/* flexWrap : le libellé + le bouton « Generate with AI » tiennent à 386px, mais un
                  libellé traduit plus long doit passer à la ligne plutôt que d'écraser le bouton. */}
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 8, flexWrap: 'wrap', marginBottom: 4 }}>
                <label style={{ ...lbl, marginBottom: 0 }}>{t('f_html')}</label>
                {window.__melisMiniTemplateExtensions?.renderHtmlActions?.(
                  (htmlContent, thumbnail) => {
                    setHtml(htmlContent)
                    try { window.tinymce?.get(EDITOR_ID)?.setContent(htmlContent) } catch { /* */ }
                    // AI-generated thumbnail (html2canvas capture of the preview) — pre-fill
                    // the form's thumbnail field so the form's submit saves it (mirrors legacy).
                    if (thumbnail) {
                      setThumbFile(thumbnail)
                      setThumbPreview(URL.createObjectURL(thumbnail))
                    }
                  },
                  { site, name },
                  // The dialog open state IS the URL, nested under the current form:
                  //   open → …/<id>/ai-generator, close → …/<id> (back to the edited template / new form).
                  // Same-key form → no remount → the inserted HTML survives. Reload-safe (SPA regex).
                  { open: aiOpen, onOpenChange: (o: boolean) => navigate(o ? `${base}/${id}/ai-generator` : `${base}/${id}`) }
                )}
              </div>
              <TinyMceField value={html} onChange={setHtml} />
            </div>
          </div>

          {/* Colonne droite : miniature */}
          <div style={{ ...card, padding: narrow ? 14 : 20, ...(narrow ? { width: '100%' } : {}) }}>
            <label style={lbl}>{t('f_thumbnail')}</label>
            {thumbPreview && (
              <div style={{ marginBottom: 12 }}>
                <img src={thumbPreview} alt="preview" style={{ width: '100%', maxHeight: 140, objectFit: 'contain', borderRadius: 6, border: '1px solid var(--color-border)' }} />
              </div>
            )}
            <input ref={fileRef} type="file" accept=".png,.jpg,.jpeg,.gif,image/png,image/jpeg,image/gif"
              style={{ display: 'none' }} onChange={onThumbChange} />
            <button style={{ ...btnGhost, height: 34, width: '100%', justifyContent: 'center' }}
              onClick={() => fileRef.current?.click()}>
              {thumbPreview ? t('f_thumbnail_change') : `+ ${t('f_thumbnail')}`}
            </button>
            <p style={hint}>{t('f_thumbnail_hint')}</p>
          </div>
        </div>
      )}
    </div>
  )
}


