import { Fragment, useEffect, useLayoutEffect, useRef, useState, type CSSProperties, type RefObject } from 'react'
import { useLocation, useNavigate, useParams } from 'react-router-dom'
import {
  deleteStyle, fetchStyleById, fetchStyles, fetchStyleSites, fetchStyleStats,
  saveStyle, type StyleItem, type StyleStats, type SiteOption,
} from './cms-style-api'
import { useKeysetList } from './use-keyset-list'
import { ExportModal, DownloadIcon } from './ExportModal'
import { ViewToggle } from './ViewToggle'
import { useIsNarrow } from './shared/useIsNarrow'
import { ExpandToggle, HiddenColsRow } from './shared/ExpandableRow'
import { FormErrorBanner, koNotify, okNotify, type FormIssue } from './shared/melis-form-errors'

// Outil Styles (CSS) legacy (vue « Old » en iframe). Voir brick.manifest.json (meliscms_tool_styles).
const MELIS_KEY = 'meliscms_tool_styles'

// Capacités (droits avancés) : la brique ne peut PAS importer le hook hôte → lit le global window.MelisCan.
// Default-allow (true) tant que non chargé / pour un admin ; l'API reste gardée côté serveur (403).
function can(cap: string): boolean {
  return (window as unknown as { MelisCan?: (k: string, c: string) => boolean }).MelisCan?.(MELIS_KEY, cap) ?? true
}

// API sous-onglets de l'hôte (la brique ne peut pas importer son contexte React) — cf. manifest subTabs:true.
// Sans ça, éditer/créer ouvrait un onglet top-level « <id> » au lieu d'un sous-onglet nommé (look Users).
type SubTabW = {
  __melisOpenSubTab?: (section: string, tab: { id: string; label: string; path: string }) => void
  __melisUpdateSubTabLabel?: (section: string, id: string, label: string) => void
}

/* ──────────────────────────────────────────────────────────────────────────
 * Brique « Styles » (MelisCms) — full React, montée à /melis-cms/styles
 * (et /melis-cms/styles/:id pour le formulaire). C'est l'outil le plus riche du
 * CMS : chaque style a un STATUT (on/off) et une association SITE. La brique ne peut
 * PAS importer les modules de l'hôte (Tailwind/shadcn/i18n) : tout est en styles inline
 * + variables CSS du thème, avec un mini-dictionnaire FR/EN lu depuis <html lang>.
 * ────────────────────────────────────────────────────────────────────────── */

// ── i18n minimal (la brique ne partage pas le dictionnaire de l'hôte) ──
type Lang = 'fr' | 'en'
function currentLang(): Lang {
  const l = (document.documentElement.lang || 'en').toLowerCase()
  return l.startsWith('fr') ? 'fr' : 'en'
}
const DICT: Record<Lang, Record<string, string>> = {
  fr: {
    title: 'Styles', subtitle: 'Styles CSS des sites',
    new: 'Nouveau style', search: 'Rechercher un style…',
    empty: 'Aucun style trouvé', count: '{n} styles — fin de la liste',
    kpi_total: 'Total', kpi_active: 'Actifs', kpi_inactive: 'Inactifs',
    all_sites: 'Tous les sites', filter_all: 'Tous',
    status_active: 'Actif', status_inactive: 'Inactif',
    col_id: 'ID', col_status: 'Statut', col_name: 'Nom', col_path: 'Chemin', col_site: 'Site',
    columns: 'Colonnes', export: 'Exporter', cols_visible: 'Visibles', cols_hidden: 'Masquées', drag_here: 'Glisser ici', reset: 'Réinitialiser', reset_filters: 'Réinitialiser les filtres',
    edit: 'Modifier', del: 'Supprimer', cancel: 'Annuler', save: 'Enregistrer', back: 'retour',
    refresh: 'Rafraîchir', loading: 'Chargement…', saved: 'Enregistré ✓',
    del_title: 'Supprimer le style', del_confirm: 'Supprimer « {n} » ? Cette action est irréversible.',
    new_title: 'Nouveau style', edit_title: 'Modifier le style',
    f_site: 'Site', f_site_ph: '— Choisir un site —', f_name: 'Nom', f_name_ph: 'Nom du style',
    f_path: 'Chemin', f_path_ph: '/chemin/vers/style.css', f_status: 'Actif',
    f_name_hint: 'Le nom affiché du style.', f_path_hint: 'Le chemin du fichier CSS.',
    f_status_hint: 'Activer ou désactiver ce style.', err_save: 'Erreur lors de la sauvegarde',
    err_check: 'Veuillez corriger les champs suivants :', err_required: 'Ce champ est requis.',
    export_filename: 'styles',
    no_access: 'Vous n’avez pas les droits pour consulter cette liste.',
  },
  en: {
    title: 'Styles', subtitle: 'Site CSS styles',
    new: 'New style', search: 'Search a style…',
    empty: 'No style found', count: '{n} styles — end of list',
    kpi_total: 'Total', kpi_active: 'Active', kpi_inactive: 'Inactive',
    all_sites: 'All sites', filter_all: 'All',
    status_active: 'Active', status_inactive: 'Inactive',
    col_id: 'ID', col_status: 'Status', col_name: 'Name', col_path: 'Path', col_site: 'Site',
    columns: 'Columns', export: 'Export', cols_visible: 'Visible', cols_hidden: 'Hidden', drag_here: 'Drag here', reset: 'Reset', reset_filters: 'Reset filters',
    edit: 'Edit', del: 'Delete', cancel: 'Cancel', save: 'Save', back: 'back',
    refresh: 'Refresh', loading: 'Loading…', saved: 'Saved ✓',
    del_title: 'Delete style', del_confirm: 'Delete “{n}”? This action is irreversible.',
    new_title: 'New style', edit_title: 'Edit style',
    f_site: 'Site', f_site_ph: '— Choose a site —', f_name: 'Name', f_name_ph: 'Style name',
    f_path: 'Path', f_path_ph: '/path/to/style.css', f_status: 'Active',
    f_name_hint: 'The displayed name of the style.', f_path_hint: 'The CSS file path.',
    f_status_hint: 'Enable or disable this style.', err_save: 'Error while saving',
    err_check: 'Please check the following fields:', err_required: 'This field is required.',
    export_filename: 'styles',
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
function notify(kind: 'ok' | 'ko', title: string, message: string) {
  window.postMessage({ __melisNotif: true, kind, title, message }, '*')
}

// ── Styles (variables CSS du thème de l'hôte) ──
const card: CSSProperties = { border: '1px solid var(--color-border)', background: 'var(--color-card)', borderRadius: 12, boxShadow: '0 1px 2px rgba(0,0,0,.04)' }
const inputCss: CSSProperties = { height: 40, width: '100%', boxSizing: 'border-box', borderRadius: 8, border: '1px solid var(--color-input,var(--color-border))', background: 'var(--color-card)', color: 'var(--color-foreground)', padding: '0 12px', fontSize: 14, outline: 'none' }
const btnPrimary: CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 36, padding: '0 14px', borderRadius: 8, border: 0, background: 'var(--color-primary)', color: 'var(--color-primary-foreground,#fff)', fontSize: 14, fontWeight: 500, cursor: 'pointer' }
const btnGhost: CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 36, padding: '0 12px', borderRadius: 8, border: '1px solid var(--color-border)', background: 'var(--color-card)', color: 'var(--color-foreground)', fontSize: 14, cursor: 'pointer' }
const iconBtn: CSSProperties = { display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 28, height: 28, borderRadius: 6, border: 0, background: 'transparent', color: 'var(--color-muted-foreground)', cursor: 'pointer' }
const th: CSSProperties = { textAlign: 'left', padding: '10px 16px', fontSize: 11, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.04em', color: 'var(--color-muted-foreground)', whiteSpace: 'nowrap' }
const td: CSSProperties = { padding: '10px 16px', fontSize: 14, color: 'var(--color-foreground)', borderTop: '1px solid var(--color-border)' }
const label: CSSProperties = { display: 'block', fontSize: 13, fontWeight: 500, marginBottom: 4, color: 'var(--color-foreground)' }
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

// ── Colonnes (masquer + réordonner par glisser-déposer, persisté) ──
type ColDef = { id: string; visible: boolean }
const COL_ORDER = ['id', 'status', 'name', 'path', 'site'] as const
const COL_LABEL: Record<string, string> = { id: 'col_id', status: 'col_status', name: 'col_name', path: 'col_path', site: 'col_site' }
const DEFAULT_COLS: ColDef[] = COL_ORDER.map((id) => ({ id, visible: id !== 'id' }))
const COL_KEY = 'melis-cms-style-cols-v1'
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

const GripIcon = () => <svg style={{ width: 13, height: 13, flexShrink: 0, color: 'var(--color-muted-foreground)' }} viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" /><circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" /><circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" /></svg>

const panelCss: CSSProperties = { display: 'flex', flexDirection: 'column', gap: 2, minHeight: 130, maxHeight: 'min(48vh, 320px)', overflowY: 'auto', minWidth: 0, borderRadius: 8, border: '1px dashed var(--color-border)', padding: 6 }
const panelTitle: CSSProperties = { padding: '0 6px 4px', fontSize: 10, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.06em', color: 'var(--color-muted-foreground)' }

function ColManager({ anchorRef, cols, labelFor, onChange, onClose }: {
  anchorRef: RefObject<HTMLElement | null>; cols: ColDef[]; labelFor: (id: string) => string; onChange: (c: ColDef[]) => void; onClose: () => void
}) {
  const t = useT()
  const [dragId, setDragId] = useState<string | null>(null)
  const [over, setOver] = useState<{ id: string; panel: 'visible' | 'hidden' } | null>(null)
  const [pos, setPos] = useState<{ top: number; left: number; width: number } | null>(null)
  const shown = cols.filter((c) => c.visible)
  const hidden = cols.filter((c) => !c.visible)

  // Clamp `left` explicitly instead of anchoring purely via `right: 0` — the anchor's own right
  // edge isn't necessarily flush with the true viewport edge (flex-wrapped filter buttons on
  // narrow), so a right-anchored panel could still push its left edge (and header) off-screen.
  useLayoutEffect(() => {
    const anchor = anchorRef.current
    if (!anchor) return
    const rect = anchor.getBoundingClientRect()
    const margin = 8
    const width = Math.min(380, window.innerWidth - margin * 2)
    const left = Math.min(Math.max(margin, rect.right - width), window.innerWidth - width - margin)
    setPos({ top: rect.bottom + 6, left, width })
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

  function item(col: ColDef, panel: 'visible' | 'hidden') {
    const isOver = over?.id === col.id && over?.panel === panel
    return (
      <div key={col.id} draggable
        onDragStart={() => setDragId(col.id)}
        onDragEnd={() => { setDragId(null); setOver(null) }}
        onDragOver={(e) => { e.preventDefault(); e.stopPropagation(); if (over?.id !== col.id || over?.panel !== panel) setOver({ id: col.id, panel }) }}
        onDrop={(e) => { e.preventDefault(); drop(panel) }}
        style={{
          display: 'flex', alignItems: 'center', gap: 8, borderRadius: 8, padding: '6px 8px', fontSize: 14, cursor: 'grab', userSelect: 'none',
          opacity: dragId === col.id ? 0.4 : 1,
          background: isOver ? 'color-mix(in srgb, var(--color-primary) 12%, transparent)' : 'transparent',
          boxShadow: isOver ? '0 0 0 1px color-mix(in srgb, var(--color-primary) 35%, transparent)' : 'none',
        }}>
        <GripIcon /><span style={{ flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{labelFor(col.id)}</span>
      </div>
    )
  }

  if (!pos) return null
  return (
    <div style={{ ...card, position: 'fixed', top: pos.top, left: pos.left, width: pos.width, zIndex: 50 }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '10px 12px', borderBottom: '1px solid var(--color-border)' }}>
        <span style={{ fontSize: 14, fontWeight: 600 }}>{t('columns')}</span>
        <button style={{ ...iconBtn, width: 22, height: 22 }} onClick={onClose}>✕</button>
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8, padding: 12 }}>
        <div style={panelCss}
          onDragOver={(e) => { e.preventDefault(); if (over?.id !== '__panel__' || over?.panel !== 'hidden') setOver({ id: '__panel__', panel: 'hidden' }) }}
          onDrop={(e) => { e.preventDefault(); drop('hidden') }}>
          <p style={panelTitle}>{t('cols_hidden')}</p>
          {hidden.length === 0 ? <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 11, color: 'var(--color-muted-foreground)', opacity: 0.5, padding: '16px 0' }}>{t('drag_here')}</div> : hidden.map((c) => item(c, 'hidden'))}
        </div>
        <div style={panelCss}
          onDragOver={(e) => { e.preventDefault(); if (over?.id !== '__panel__' || over?.panel !== 'visible') setOver({ id: '__panel__', panel: 'visible' }) }}
          onDrop={(e) => { e.preventDefault(); drop('visible') }}>
          <p style={panelTitle}>{t('cols_visible')}</p>
          {shown.length === 0 ? <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 11, color: 'var(--color-muted-foreground)', opacity: 0.5, padding: '16px 0' }}>{t('drag_here')}</div> : shown.map((c) => item(c, 'visible'))}
        </div>
      </div>
      <div style={{ borderTop: '1px solid var(--color-border)', padding: 6 }}>
        <button style={{ ...btnGhost, width: '100%', height: 30, border: 0, justifyContent: 'center', color: 'var(--color-muted-foreground)' }}
          onClick={() => { onChange(DEFAULT_COLS); saveCols(DEFAULT_COLS) }}>{t('reset')}</button>
      </div>
    </div>
  )
}

// ── KPI ──
function Kpi({ label: lbl, value }: { label: string; value: number | null }) {
  return (
    <div style={{ ...card, display: 'flex', flexDirection: 'column', gap: 2, padding: 16, flex: 1, minWidth: 140 }}>
      <span style={{ fontSize: 12, color: 'var(--color-muted-foreground)' }}>{lbl}</span>
      <span style={{ fontSize: 22, fontWeight: 700 }}>{value == null ? '…' : value}</span>
    </div>
  )
}

// ── Badge de statut ──
function StatusBadge({ active, label }: { active: boolean; label: string }) {
  return (
    <span style={{
      display: 'inline-flex', alignItems: 'center', gap: 6, padding: '2px 8px', borderRadius: 6, fontSize: 12, fontWeight: 500,
      background: active ? 'color-mix(in srgb, #10b981 14%, transparent)' : 'var(--color-muted,rgba(0,0,0,.06))',
      color: active ? '#059669' : 'var(--color-muted-foreground)',
    }}>
      <span style={{ width: 6, height: 6, borderRadius: '50%', background: active ? '#10b981' : 'var(--color-muted-foreground)' }} />
      {label}
    </span>
  )
}

// ── Interrupteur Actif / Inactif (vert = actif, rouge = inactif) — comme l'outil core Modules ──
function StatusToggle({ checked, onChange, labelOn, labelOff }: {
  checked: boolean; onChange: (v: boolean) => void; labelOn: string; labelOff: string
}) {
  return (
    <button type="button" role="switch" aria-checked={checked} onClick={() => onChange(!checked)}
      style={{ display: 'inline-flex', alignItems: 'center', gap: 8, border: 0, background: 'transparent', cursor: 'pointer', padding: 0 }}>
      <span style={{ position: 'relative', width: 40, height: 22, borderRadius: 999, flexShrink: 0, background: checked ? '#22c55e' : '#ef4444', transition: 'background .15s' }}>
        <span style={{ position: 'absolute', top: 2, left: checked ? 20 : 2, width: 18, height: 18, borderRadius: 999, background: '#fff', boxShadow: '0 1px 2px rgba(0,0,0,.3)', transition: 'left .15s' }} />
      </span>
      <span style={{ fontSize: 14, fontWeight: 500, color: checked ? '#059669' : '#dc2626' }}>{checked ? labelOn : labelOff}</span>
    </button>
  )
}

// ════════════════════════════════════════════════════════════════════════════
export default function CmsStylePage({ active = true }: { active?: boolean }) {
  const { id } = useParams()
  const location = useLocation()
  // La brique reste MONTÉE au changement d'onglet principal (manifest persistent:true) pour que l'état
  // de la liste survive au lieu de se recharger. INACTIVE, elle doit IGNORER le route global (un :id
  // appartenant à un AUTRE outil) : sinon elle basculerait en formulaire, fetcherait un id étranger et
  // navigate() (détournant la navigation de l'outil actif). On GÈLE donc le route (id + pathname) tant
  // qu'elle est inactive ; on ne lit le route vif que lorsqu'elle est active.
  const [frozen, setFrozen] = useState({ id, pathname: location.pathname })
  useEffect(() => {
    if (active) setFrozen({ id, pathname: location.pathname })
  }, [active, id, location.pathname])
  const effId = active ? id : frozen.id
  const effPath = active ? location.pathname : frozen.pathname
  // base = route de la liste (pathname sans le segment /:id éventuel)
  const base = effId ? effPath.slice(0, effPath.length - effId.length - 1) : effPath

  if (effId) return <StyleForm id={effId} base={base} />
  return <StyleList base={base} />
}

// ── Liste ───────────────────────────────────────────────────────────────────
function StyleList({ base }: { base: string }) {
  const t = useT()
  const navigate = useNavigate()
  const narrow = useIsNarrow()
  const [stats, setStats] = useState<StyleStats | null>(null)
  const [sites, setSites] = useState<SiteOption[]>([])
  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [site, setSite] = useState<number | null>(null)
  const [status, setStatus] = useState<'' | '0' | '1'>('')
  const [toDelete, setToDelete] = useState<StyleItem | null>(null)
  const [tick, setTick] = useState(0)
  const [cols, setCols] = useState<ColDef[]>(loadCols)
  const colsAnchorRef = useRef<HTMLDivElement>(null)
  const [showCols, setShowCols] = useState(false)
  const [showExport, setShowExport] = useState(false)
  const [mode, setMode] = useState<'react' | 'iframe'>('react')
  const [frameLoaded, setFrameLoaded] = useState(false)
  // Mobile-only: force the table down to just "name" regardless of the desktop ColManager
  // preference, with the rest reachable via a per-row "+" — desktop behavior (cols as-is, no
  // "+" column at all) is untouched since hasHidden/displayCols only diverge from `cols` when narrow.
  const [expanded, setExpanded] = useState<Set<number>>(new Set())
  const toggleExpand = (id: number) => setExpanded((s) => {
    const n = new Set(s); n.has(id) ? n.delete(id) : n.add(id); return n
  })
  const displayCols = narrow ? cols.map((c) => ({ ...c, visible: c.id === 'name' })) : cols
  const hasHidden = narrow

  // Scroll infini + tri server-side + keyset (mutualisé). Le fetcher capture les filtres courants ;
  // `deps` relance un chargement frais à chaque changement de filtre (ou refresh via `tick`).
  const {
    items, total, loading, hasMore, sentinelRef, sortCol, sortDir, toggleSort, reload, removeLocal,
  } = useKeysetList<StyleItem>({
    fetcher: (a) => fetchStyles({ ...a, search, site, status }),
    deps: [search, site, status, tick],
    limit: 25,
    defaultSort: 'id',
    defaultDir: 'desc',
  })

  useEffect(() => { fetchStyleStats().then(setStats).catch(() => null) }, [tick])
  useEffect(() => { fetchStyleSites().then(setSites).catch(() => null) }, [])

  // Réinitialiser les filtres : recherche + statut + site → refetch frais via bump de `tick`.
  function resetFilters() {
    setSearchInput(''); setSearch('')
    setStatus('')
    setSite(null)
    setTick((x) => x + 1)
  }

  async function confirmDelete() {
    if (!toDelete) return
    try { await deleteStyle(toDelete.id); removeLocal((r) => r.id === toDelete.id); setToDelete(null); reload() }
    catch { setToDelete(null) }
  }

  // ── Filtre statut segmenté (boutons All / Active ● vert / Inactive ● rouge) ──
  const statusTab = (active: boolean): CSSProperties => ({
    display: 'inline-flex', alignItems: 'center', gap: 6, height: 28, padding: '0 12px', borderRadius: 6, border: 0,
    fontSize: 12, fontWeight: 500, cursor: 'pointer',
    background: active ? 'var(--color-card)' : 'transparent',
    color: active ? 'var(--color-foreground)' : 'var(--color-muted-foreground)',
    boxShadow: active ? '0 1px 2px rgba(0,0,0,.06)' : 'none',
  })
  const dot = (color: string): CSSProperties => ({ width: 6, height: 6, borderRadius: '50%', background: color })

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, padding: 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      <style>{'@keyframes melis-spin{to{transform:rotate(360deg)}}'}</style>
      {/* Header — narrow-only additions never remove/replace a desktop style, so at narrow=false
          every style below renders byte-identical to the original desktop layout. */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 }}>
        <div style={narrow ? { minWidth: 0 } : undefined}>
          <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0, ...(narrow ? { overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } : {}) }}>{t('title')}</h1>
          <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', margin: '2px 0 0', ...(narrow ? { overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } : {}) }}>{t('subtitle')}</p>
        </div>
        {/* On narrow: icon row (toggle+refresh) stacks above the "+ New" button, which stretches
            (width 100%) to match that row's width — both stay grouped as a compact column to the
            right of the title. Desktop keeps the original single row. */}
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, ...(narrow ? { flexShrink: 0, flexDirection: 'column' } : {}) }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <ViewToggle mode={mode} compact={narrow} onChange={(m) => { setMode(m); if (m === 'iframe') setFrameLoaded(true) }} />
            <button style={{ ...btnGhost, ...(narrow ? { width: 36, padding: 0, justifyContent: 'center' } : {}) }} onClick={() => setTick((x) => x + 1)} title={t('refresh')}>↻</button>
          </div>
          {can('create') && <button style={{ ...btnPrimary, ...(narrow ? { width: '100%', justifyContent: 'center' } : {}) }} onClick={() => navigate(`${base}/new`)}><PlusIcon />{t('new')}</button>}
        </div>
      </div>

      {/* Vue « Old » : outil Styles legacy en iframe (montée à la 1ʳᵉ activation, gardée en display:none) */}
      {frameLoaded && (
        <div style={{ ...card, display: mode === 'iframe' ? 'flex' : 'none', flex: 1, minHeight: 480, overflow: 'hidden' }}>
          <iframe src={`/melis/react-tool-page?key=${encodeURIComponent(MELIS_KEY)}`}
            style={{ flex: 1, width: '100%', border: 0 }} title="Styles — Vue Melis"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-modals" />
        </div>
      )}

      {/* Vue « New » : liste React native */}
      <div style={{ display: mode === 'react' ? 'flex' : 'none', flexDirection: 'column', gap: 20 }}>
      {!can('list') ? (
        <div style={{ ...card, padding: '40px 16px', textAlign: 'center', fontSize: 14, color: 'var(--color-muted-foreground)' }}>{t('no_access')}</div>
      ) : (<>
      {/* KPI */}
      <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
        <Kpi label={t('kpi_total')} value={stats?.total ?? null} />
        <Kpi label={t('kpi_active')} value={stats?.active ?? null} />
        <Kpi label={t('kpi_inactive')} value={stats?.inactive ?? null} />
      </div>

      {/* Filtres */}
      <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'center' }}>
        <input style={{ ...inputCss, height: 36, flex: narrow ? '1 1 100%' : 1, minWidth: narrow ? 0 : 220 }} value={searchInput}
          onChange={(e) => setSearchInput(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && setSearch(searchInput.trim())}
          placeholder={t('search')} />
        {/* Filtre statut segmenté */}
        <div style={{ display: 'inline-flex', gap: 4, padding: 4, borderRadius: 8, border: '1px solid var(--color-border)', background: 'color-mix(in srgb, var(--color-muted,#888) 12%, transparent)', ...(narrow ? { flex: '1 1 100%' } : {}) }}>
          <button style={{ ...statusTab(status === ''), ...(narrow ? { flex: 1, justifyContent: 'center' } : {}) }} onClick={() => setStatus('')}>{t('filter_all')}</button>
          <button style={{ ...statusTab(status === '1'), ...(narrow ? { flex: 1, justifyContent: 'center' } : {}) }} onClick={() => setStatus('1')}><span style={dot('#10b981')} />{t('status_active')}</button>
          <button style={{ ...statusTab(status === '0'), ...(narrow ? { flex: 1, justifyContent: 'center' } : {}) }} onClick={() => setStatus('0')}><span style={dot('#ef4444')} />{t('status_inactive')}</button>
        </div>
        <select style={{ ...inputCss, height: 36, width: narrow ? '100%' : 'auto', ...(narrow ? { flex: '1 1 100%' } : {}) }} value={site ?? ''} onChange={(e) => setSite(e.target.value ? Number(e.target.value) : null)}>
          <option value="">{t('all_sites')}</option>
          {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
        </select>
        {/* "reset_filters" ("Réinitialiser les filtres") runs much longer than "columns"/"export"
            once translated to French — pairing it 50/50 wraps its label to 2 lines inside a fixed
            36px-tall button while its sibling stays 1 line. Give it its own full-width row instead. */}
        <button style={{ ...btnGhost, height: 36, ...(narrow ? { flex: '1 1 100%', justifyContent: 'center' } : {}) }} onClick={resetFilters}><ResetIcon />{t('reset_filters')}</button>
        <div ref={colsAnchorRef} style={{ position: 'relative', ...(narrow ? { flex: '1 1 calc(50% - 4px)' } : {}) }}>
          <button style={{ ...btnGhost, height: 36, ...(narrow ? { width: '100%', justifyContent: 'center' } : {}) }} onClick={() => setShowCols((v) => !v)}><GripIcon />{t('columns')}</button>
          {showCols && <ColManager anchorRef={colsAnchorRef} cols={cols} labelFor={(id) => t(COL_LABEL[id])} onChange={setCols} onClose={() => setShowCols(false)} />}
        </div>
        {can('export') && <button style={{ ...btnGhost, height: 36, ...(narrow ? { flex: '1 1 calc(50% - 4px)', justifyContent: 'center' } : {}) }} onClick={() => setShowExport(true)}><DownloadIcon />{t('export')}</button>}
      </div>

      {/* Table */}
      <div style={{ ...card, overflow: 'hidden' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse', ...(narrow ? {} : { minWidth: 640 }) }}>
          <thead style={{ background: 'var(--color-muted,rgba(0,0,0,.03))' }}>
            <tr>
              {hasHidden && <th style={{ ...th, width: 32 }} />}
              {visibleCols(displayCols).map(({ id }) => (
                <th key={id} style={{ ...th, cursor: 'pointer', ...(id === 'id' ? { width: 70 } : {}), ...(id === 'status' ? { width: 110 } : {}) }}
                  onClick={() => toggleSort(id)}>
                  {t(COL_LABEL[id])}<SortArrow col={id} sortCol={sortCol} sortDir={sortDir} />
                </th>
              ))}
              <th style={{ ...th, width: 80 }} />
            </tr>
          </thead>
          <tbody>
            {items.length === 0 && !loading ? (
              <tr><td style={{ ...td, textAlign: 'center', color: 'var(--color-muted-foreground)', padding: '40px 16px' }} colSpan={visibleCols(displayCols).length + (hasHidden ? 1 : 0) + 1}>{t('empty')}</td></tr>
            ) : items.map((r) => (
              <Fragment key={r.id}>
                <tr>
                  {hasHidden && <td style={td}><ExpandToggle expanded={expanded.has(r.id)} onClick={() => toggleExpand(r.id)} /></td>}
                  {visibleCols(displayCols).map(({ id }) => (
                    <td key={id} style={{ ...td, ...(id === 'id' ? { color: 'var(--color-muted-foreground)', fontVariantNumeric: 'tabular-nums' } : {}), ...(id === 'path' ? { fontFamily: 'monospace', fontSize: 13 } : {}) }}>
                      {id === 'id' && r.id}
                      {id === 'status' && <StatusBadge active={!!r.status} label={r.status ? t('status_active') : t('status_inactive')} />}
                      {id === 'name' && <span style={{ fontWeight: 500 }}>{r.name}</span>}
                      {id === 'path' && r.path}
                      {id === 'site' && r.siteName}
                    </td>
                  ))}
                  <td style={td}>
                    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 4 }}>
                      {can('edit') && <button style={iconBtn} title={t('edit')} onClick={() => navigate(`${base}/${r.id}`)}><PencilIcon /></button>}
                      {can('delete') && <button style={{ ...iconBtn, color: 'var(--color-destructive,#ef4444)' }} title={t('del')} onClick={() => setToDelete(r)}><TrashIcon /></button>}
                    </div>
                  </td>
                </tr>
                {hasHidden && expanded.has(r.id) && (
                  <HiddenColsRow cols={displayCols} labelFor={(id) => t(COL_LABEL[id])}
                    renderValue={(id) => (
                      id === 'id' ? r.id
                        : id === 'status' ? <StatusBadge active={!!r.status} label={r.status ? t('status_active') : t('status_inactive')} />
                        : id === 'name' ? r.name
                        : id === 'path' ? r.path
                        : id === 'site' ? r.siteName
                        : ''
                    )}
                    colSpan={visibleCols(displayCols).length + 2} narrow={narrow} />
                )}
              </Fragment>
            ))}
          </tbody>
        </table>
        {/* Scroll infini : sentinelle observée → charge le lot suivant ; pied = spinner puis compteur final. */}
        <div ref={sentinelRef} style={{ height: 1 }} />
        {loading && (
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8, padding: '14px 0', fontSize: 12, color: 'var(--color-muted-foreground)' }}>
            <SpinnerIcon />{t('loading')}
          </div>
        )}
        {!hasMore && items.length > 0 && (
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
        <ExportModal<StyleItem>
          cols={cols}
          labelFor={(id) => t(COL_LABEL[id])}
          fetchAll={async () => {
            // Export = tout le jeu filtré : on boucle sur le curseur keyset (100/lot) au lieu d'un limit géant.
            const all: StyleItem[] = []
            let after: string | undefined
            do {
              const r = await fetchStyles({ search, site, status, sort: sortCol, dir: sortDir, limit: 100, after })
              all.push(...r.items)
              after = r.nextCursor ?? undefined
            } while (after)
            return all
          }}
          getCell={(r, id) => id === 'id' ? r.id : id === 'status' ? (r.status ? t('status_active') : t('status_inactive')) : id === 'name' ? r.name : id === 'path' ? r.path : id === 'site' ? r.siteName : ''}
          filename={t('export_filename')}
          sheetName={t('title')}
          total={items.length}
          onClose={() => setShowExport(false)}
        />
      )}
    </div>
  )
}

// ── Formulaire ────────────────────────────────────────────────────────────────
function StyleForm({ id, base }: { id: string; base: string }) {
  const t = useT()
  const navigate = useNavigate()
  const isEdit = id !== 'new'
  const styleId = isEdit ? parseInt(id) : null

  const [sites, setSites] = useState<SiteOption[]>([])
  const [siteId, setSiteId] = useState<number | ''>('')
  const [name, setName] = useState('')
  const [path, setPath] = useState('')
  const [status, setStatus] = useState(true)
  const [loading, setLoading] = useState(false)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [issues, setIssues] = useState<FormIssue[]>([]) // champs fautifs listés dans le bandeau
  const [saved, setSaved] = useState(false)

  // Sous-onglet nommé (look Users) : ouvert au montage, renommé avec le nom du style au chargement.
  const subTabId = `${base}/${id}`
  useEffect(() => {
    ;(window as unknown as SubTabW).__melisOpenSubTab?.(base, { id: subTabId, label: isEdit ? t('loading') : t('new_title'), path: subTabId })
  }, []) // eslint-disable-line react-hooks/exhaustive-deps
  useEffect(() => {
    if (isEdit && name) (window as unknown as SubTabW).__melisUpdateSubTabLabel?.(base, subTabId, name)
  }, [name]) // eslint-disable-line react-hooks/exhaustive-deps

  useEffect(() => { if (!can(isEdit ? 'edit' : 'create')) navigate(base) }, [isEdit, base, navigate])
  useEffect(() => { fetchStyleSites().then(setSites).catch(() => null) }, [])
  useEffect(() => {
    if (!styleId) return
    setLoading(true)
    fetchStyleById(styleId)
      .then((r) => { setSiteId(r.siteId); setName(r.name); setPath(r.path); setStatus(!!r.status) })
      .catch(() => navigate(base))
      .finally(() => setLoading(false))
  }, [styleId])

  async function submit() {
    setError(null); setIssues([])
    // Validation client → un item par champ fautif, listé dans le bandeau (pattern unifié).
    const iss: FormIssue[] = []
    if (!siteId) iss.push({ label: t('f_site'), message: t('err_required') })
    if (!name.trim()) iss.push({ label: t('f_name'), message: t('err_required') })
    if (!path.trim()) iss.push({ label: t('f_path'), message: t('err_required') })
    if (iss.length) { setError(t('err_check')); setIssues(iss); return }
    setSaving(true)
    try {
      await saveStyle({ id: styleId, siteId: Number(siteId), name: name.trim(), status: status ? 1 : 0, path: path.trim() })
      setSaved(true)
      okNotify(t('title'), t('saved'))
      setTimeout(() => navigate(base), 500)
    } catch (e) {
      const msg = e instanceof Error ? e.message : t('err_save')
      setError(msg); koNotify(t('title'), msg)
    } finally { setSaving(false) }
  }
  const hasIssue = (lbl: string) => issues.some((i) => i.label === lbl)

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, padding: 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
          <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0 }}>{isEdit ? t('edit_title') : t('new_title')}</h1>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
          {saved && <span style={{ fontSize: 14, color: '#059669' }}>{t('saved')}</span>}
          <button style={btnPrimary} onClick={submit} disabled={saving || loading}>{saving ? '…' : t('save')}</button>
        </div>
      </div>

      <FormErrorBanner title={error ?? undefined} issues={issues} />

      {loading ? (
        <div style={{ padding: 48, textAlign: 'center', color: 'var(--color-muted-foreground)' }}>{t('loading')}</div>
      ) : (
        <div style={{ ...card, padding: 20, maxWidth: 640 }}>
          <div style={{ marginBottom: 16 }}>
            <label style={label}>{t('f_site')}</label>
            <select style={{ ...inputCss, ...(hasIssue(t('f_site')) ? { borderColor: '#dc2626' } : {}) }} value={siteId} onChange={(e) => setSiteId(e.target.value ? Number(e.target.value) : '')}>
              <option value="">{t('f_site_ph')}</option>
              {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
            </select>
          </div>
          <div style={{ marginBottom: 16 }}>
            <label style={label}>{t('f_name')}</label>
            <input style={{ ...inputCss, ...(hasIssue(t('f_name')) ? { borderColor: '#dc2626' } : {}) }} value={name} onChange={(e) => setName(e.target.value)} placeholder={t('f_name_ph')} maxLength={255} autoComplete="off" />
            <p style={hint}>{t('f_name_hint')}</p>
          </div>
          <div style={{ marginBottom: 16 }}>
            <label style={label}>{t('f_path')}</label>
            <input style={{ ...inputCss, fontFamily: 'monospace', ...(hasIssue(t('f_path')) ? { borderColor: '#dc2626' } : {}) }} value={path} onChange={(e) => setPath(e.target.value)} placeholder={t('f_path_ph')} maxLength={255} autoComplete="off" />
            <p style={hint}>{t('f_path_hint')}</p>
          </div>
          <div>
            <label style={label}>{t('f_status')}</label>
            <StatusToggle checked={status} onChange={setStatus} labelOn={t('status_active')} labelOff={t('status_inactive')} />
            <p style={hint}>{t('f_status_hint')}</p>
          </div>
        </div>
      )}
    </div>
  )
}
