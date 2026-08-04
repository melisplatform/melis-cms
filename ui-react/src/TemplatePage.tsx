import { Fragment, useEffect, useLayoutEffect, useRef, useState, type CSSProperties, type FormEvent, type RefObject } from 'react'
import { useLocation, useNavigate, useParams } from 'react-router-dom'
import {
  deleteTemplate, fetchTemplate, fetchTemplates, fetchTemplateSites, fetchTemplateStats, saveTemplate,
  type TemplateItem, type TemplateStats, type SiteOption,
} from './template-api'
import { ExportModal, DownloadIcon } from './ExportModal'
import { ViewToggle } from './ViewToggle'
import { useKeysetList } from './use-keyset-list'
import { useIsNarrow } from './shared/useIsNarrow'
import { ExpandToggle, HiddenColsRow } from './shared/ExpandableRow'
import { FormErrorBanner, koNotify, okNotify, type FormIssue } from './shared/melis-form-errors'

/* ──────────────────────────────────────────────────────────────────────────
 * Brique « Templates » (MelisCms). LISTE + CRÉATION + ÉDITION sont full React (montées à
 * /melis-cms/templates, /new, /:id). La création réutilise le compteur `tpl_id` par plateforme
 * côté serveur (react-api Template save). La vue « Old » (toggle) affiche le tool legacy en iframe.
 * Styles inline + variables CSS du thème, i18n FR/EN via <html lang> (la brique ne partage pas
 * les modules de l'hôte).
 * ────────────────────────────────────────────────────────────────────────── */

const MELIS_KEY = 'meliscms_tool_templates'

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

// ── i18n minimal ──
type Lang = 'fr' | 'en'
function currentLang(): Lang { return (document.documentElement.lang || 'en').toLowerCase().startsWith('fr') ? 'fr' : 'en' }
const DICT: Record<Lang, Record<string, string>> = {
  fr: {
    title: "Templates", subtitle: "Templates des sites", new: "Nouveau template", search: "Rechercher un template…",
    empty: "Aucun template trouvé", count: "{n} templates — fin de la liste",
    kpi_total: "Total", kpi_sites: "Sites", kpi_types: "Types",
    all_sites: "Tous les sites", all_types: "Tous les types",
    col_id: "ID", col_name: "Nom", col_type: "Type", col_ctrl: "Contrôleur / Action", col_layout: "Layout", col_site: "Site", col_date: "Création",
    columns: "Colonnes", export: "Exporter", cols_visible: "Visibles", cols_hidden: "Masquées", drag_here: "Glisser ici", reset: "Réinitialiser", reset_filters: "Réinitialiser les filtres",
    edit: "Modifier", del: "Supprimer", cancel: "Annuler", back: "retour", refresh: "Rafraîchir", loading: "Chargement…",
    del_title: "Supprimer le template", del_confirm: "Supprimer « {n} » ? Cette action est irréversible.",
    no_access: "Vous n'avez pas les droits pour consulter cette liste.",
    form_edit: "Modifier le template", form_new: "Nouveau template",
    field_name: "Nom", field_type: "Type", field_site: "Site", field_folder: "Dossier site (website_folder)",
    field_layout: "Layout", field_ctrl: "Contrôleur", field_action: "Action", field_php_path: "Chemin PHP",
    save: "Enregistrer", saving: "Enregistrement…", saved: "Enregistré ✓", save_err: "Erreur lors de l'enregistrement.",
    err_check: 'Veuillez corriger les champs suivants :', err_required: 'Ce champ est requis.',
    no_edit_access: "Vous n'avez pas les droits pour modifier ce template.",
  },
  en: {
    title: "Templates", subtitle: "Site templates", new: "New template", search: "Search a template…",
    empty: "No template found", count: "{n} templates — end of list",
    kpi_total: "Total", kpi_sites: "Sites", kpi_types: "Types",
    all_sites: "All sites", all_types: "All types",
    col_id: "ID", col_name: "Name", col_type: "Type", col_ctrl: "Controller / Action", col_layout: "Layout", col_site: "Site", col_date: "Created",
    columns: "Columns", export: "Export", cols_visible: "Visible", cols_hidden: "Hidden", drag_here: "Drag here", reset: "Reset", reset_filters: "Reset filters",
    edit: "Edit", del: "Delete", cancel: "Cancel", back: "back", refresh: "Refresh", loading: "Loading…",
    del_title: "Delete template", del_confirm: "Delete \"{n}\"? This action is irreversible.",
    no_access: "You do not have permission to view this list.",
    form_edit: "Edit template", form_new: "New template",
    field_name: "Name", field_type: "Type", field_site: "Site", field_folder: "Website folder",
    field_layout: "Layout", field_ctrl: "Controller", field_action: "Action", field_php_path: "PHP path",
    save: "Save", saving: "Saving…", saved: "Saved ✓", save_err: "Error while saving.",
    err_check: 'Please check the following fields:', err_required: 'This field is required.',
    no_edit_access: "You do not have permission to edit this template.",
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

// ── Styles (variables CSS du thème) ──
const card: CSSProperties = { border: '1px solid var(--color-border)', background: 'var(--color-card)', borderRadius: 12, boxShadow: '0 1px 2px rgba(0,0,0,.04)' }
const inputCss: CSSProperties = { height: 36, boxSizing: 'border-box', borderRadius: 8, border: '1px solid var(--color-input,var(--color-border))', background: 'var(--color-card)', color: 'var(--color-foreground)', padding: '0 12px', fontSize: 14, outline: 'none' }
const btnPrimary: CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 36, padding: '0 14px', borderRadius: 8, border: 0, background: 'var(--color-primary)', color: 'var(--color-primary-foreground,#fff)', fontSize: 14, fontWeight: 500, cursor: 'pointer' }
const btnGhost: CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 36, padding: '0 12px', borderRadius: 8, border: '1px solid var(--color-border)', background: 'var(--color-card)', color: 'var(--color-foreground)', fontSize: 14, cursor: 'pointer' }
const iconBtn: CSSProperties = { display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 28, height: 28, borderRadius: 6, border: 0, background: 'transparent', color: 'var(--color-muted-foreground)', cursor: 'pointer' }
const th: CSSProperties = { textAlign: 'left', padding: '10px 16px', fontSize: 11, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.04em', color: 'var(--color-muted-foreground)', whiteSpace: 'nowrap' }

/** Icône de tri unifiée — mêmes tracés que lucide ArrowUpDown/ArrowUp/ArrowDown du core. */
function SortIcon({ dir }: { dir: 'asc' | 'desc' | null }) {
  const p = { width: 12, height: 12, viewBox: '0 0 24 24', fill: 'none' as const, stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round' as const, strokeLinejoin: 'round' as const, style: { flexShrink: 0, display: 'inline-block', opacity: dir ? 1 : 0.3 } }
  if (dir === 'asc')  return <svg {...p}><path d="m5 12 7-7 7 7" /><path d="M12 19V5" /></svg>
  if (dir === 'desc') return <svg {...p}><path d="M12 5v14" /><path d="m19 12-7 7-7-7" /></svg>
  return <svg {...p}><path d="m21 16-4 4-4-4" /><path d="M17 20V4" /><path d="m3 8 4-4 4 4" /><path d="M7 4v16" /></svg>
}
const td: CSSProperties = { padding: '10px 16px', fontSize: 14, color: 'var(--color-foreground)', borderTop: '1px solid var(--color-border)' }

/** Petit spinner inline (la brique ne peut pas importer lucide/Loader2 de l'hôte). */
function Spinner() {
  return (
    <svg style={{ width: 16, height: 16, verticalAlign: 'middle', animation: 'melis-spin 0.7s linear infinite' }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
      <path d="M21 12a9 9 0 1 1-6.219-8.56" />
      <style>{`@keyframes melis-spin{to{transform:rotate(360deg)}}`}</style>
    </svg>
  )
}
const panelCss: CSSProperties = { display: 'flex', flexDirection: 'column', gap: 2, minHeight: 130, maxHeight: 'min(48vh, 320px)', overflowY: 'auto', minWidth: 0, borderRadius: 8, border: '1px dashed var(--color-border)', padding: 6 }
const panelTitle: CSSProperties = { padding: '0 6px 4px', fontSize: 10, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.06em', color: 'var(--color-muted-foreground)' }

const sIcon = { width: 15, height: 15, flexShrink: 0 } as const
const PencilIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>
const TrashIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /></svg>
const PlusIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14M5 12h14" /></svg>
const ResetIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 2v6h6" /><path d="M3 13a9 9 0 1 0 3-7.7L3 8" /></svg>
const GripIcon = () => <svg style={{ width: 13, height: 13, flexShrink: 0, color: 'var(--color-muted-foreground)' }} viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" /><circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" /><circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" /></svg>

// ── Colonnes (masquer + réordonner, persisté) ──
type ColDef = { id: string; visible: boolean }
const COL_ORDER = ['id', 'name', 'type', 'ctrl', 'layout', 'site', 'date'] as const
const COL_LABEL: Record<string, string> = { id: 'col_id', name: 'col_name', type: 'col_type', ctrl: 'col_ctrl', layout: 'col_layout', site: 'col_site', date: 'col_date' }
const DEFAULT_COLS: ColDef[] = COL_ORDER.map((id) => ({ id, visible: id !== 'id' }))
const COL_KEY = 'melis-template-cols-v1'
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
  function item(col: ColDef, panel: 'visible' | 'hidden') {
    const isOver = over?.id === col.id && over?.panel === panel
    return (
      <div key={col.id} draggable
        onDragStart={() => setDragId(col.id)} onDragEnd={() => { setDragId(null); setOver(null) }}
        onDragOver={(e) => { e.preventDefault(); e.stopPropagation(); if (over?.id !== col.id || over?.panel !== panel) setOver({ id: col.id, panel }) }}
        onDrop={(e) => { e.preventDefault(); drop(panel) }}
        style={{ display: 'flex', alignItems: 'center', gap: 8, borderRadius: 8, padding: '6px 8px', fontSize: 14, cursor: 'grab', userSelect: 'none', opacity: dragId === col.id ? 0.4 : 1, background: isOver ? 'color-mix(in srgb, var(--color-primary) 12%, transparent)' : 'transparent', boxShadow: isOver ? '0 0 0 1px color-mix(in srgb, var(--color-primary) 35%, transparent)' : 'none' }}>
        <GripIcon /><span style={{ flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{labelFor(col.id)}</span>
      </div>
    )
  }
  const ph = (txt: string) => <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 11, color: 'var(--color-muted-foreground)', opacity: 0.5, padding: '16px 0' }}>{txt}</div>
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
          {hidden.length === 0 ? ph(t('drag_here')) : hidden.map((c) => item(c, 'hidden'))}
        </div>
        <div style={panelCss} onDragOver={(e) => { e.preventDefault(); if (over?.id !== '__panel__' || over?.panel !== 'visible') setOver({ id: '__panel__', panel: 'visible' }) }} onDrop={(e) => { e.preventDefault(); drop('visible') }}>
          <p style={panelTitle}>{t('cols_visible')}</p>
          {shown.length === 0 ? ph(t('drag_here')) : shown.map((c) => item(c, 'visible'))}
        </div>
      </div>
      <div style={{ borderTop: '1px solid var(--color-border)', padding: 6 }}>
        <button style={{ ...btnGhost, width: '100%', height: 30, border: 0, justifyContent: 'center', color: 'var(--color-muted-foreground)' }} onClick={() => { onChange(DEFAULT_COLS); saveCols(DEFAULT_COLS) }}>{t('reset')}</button>
      </div>
    </div>
  )
}

function Kpi({ label: lbl, value }: { label: string; value: number | null }) {
  return (
    <div style={{ ...card, display: 'flex', flexDirection: 'column', gap: 2, padding: 16, flex: 1, minWidth: 130 }}>
      <span style={{ fontSize: 12, color: 'var(--color-muted-foreground)' }}>{lbl}</span>
      <span style={{ fontSize: 22, fontWeight: 700 }}>{value == null ? '…' : value}</span>
    </div>
  )
}

function fmtDate(s: string): string {
  if (!s) return '—'
  const d = new Date(s.replace(' ', 'T'))
  return isNaN(d.getTime()) ? s : d.toLocaleString(currentLang() === 'fr' ? 'fr-FR' : 'en-GB', { year: 'numeric', month: 'short', day: '2-digit' })
}

// ════════════════════════════════════════════════════════════════════════════
export default function TemplatePage({ active = true }: { active?: boolean }) {
  const { id } = useParams()
  const location = useLocation()
  // Persistante (manifest) : reste montée au changement d'onglet → on GÈLE le route quand inactive
  // (sinon lecture d'un :id étranger → bascule formulaire + fetch + navigate = détournement). Cf. skill.
  const [frozen, setFrozen] = useState({ id, pathname: location.pathname })
  useEffect(() => { if (active) setFrozen({ id, pathname: location.pathname }) }, [active, id, location.pathname])
  const effId = active ? id : frozen.id
  const effPath = active ? location.pathname : frozen.pathname
  const base = effId ? effPath.slice(0, effPath.length - effId.length - 1) : effPath
  if (effId) return <TemplateForm id={effId} base={base} />
  return <TemplateList base={base} />
}

// ── Liste (native, scroll infini keyset + tri server-side) ────────────────────
function TemplateList({ base }: { base: string }) {
  const t = useT()
  const navigate = useNavigate()
  const narrow = useIsNarrow()
  const [stats, setStats] = useState<TemplateStats | null>(null)
  const [sites, setSites] = useState<SiteOption[]>([])
  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [site, setSite] = useState<number | null>(null)
  const [toDelete, setToDelete] = useState<TemplateItem | null>(null)
  const [tick, setTick] = useState(0)
  const [cols, setCols] = useState<ColDef[]>(loadCols)
  const [showCols, setShowCols] = useState(false)
  const [showExport, setShowExport] = useState(false)
  const [mode, setMode] = useState<'react' | 'iframe'>('react')
  const [frameLoaded, setFrameLoaded] = useState(false)
  const colBtnRef = useRef<HTMLButtonElement>(null)
  const [expandedRows, setExpandedRows] = useState<Set<number>>(new Set())
  function toggleExpanded(id: number) {
    setExpandedRows((prev) => { const next = new Set(prev); next.has(id) ? next.delete(id) : next.add(id); return next })
  }
  // Collapse à la seule colonne essentielle (nom) sur narrow, quel que soit le réglage ColManager
  // de l'utilisateur — cf. skill melis-react-mobile-responsive.
  const displayCols = narrow ? cols.map((c) => ({ ...c, visible: c.id === 'name' })) : cols
  const hasHidden = narrow

  const { items, total, loading, hasMore, sentinelRef, sortCol, sortDir, toggleSort, reload, removeLocal } =
    useKeysetList<TemplateItem>({
      fetcher: (a) => fetchTemplates({ ...a, search, site }),
      deps: [search, site, tick],
      defaultSort: 'id',
      defaultDir: 'asc',
    })

  useEffect(() => { fetchTemplateStats().then(setStats).catch(() => null) }, [tick])
  useEffect(() => { fetchTemplateSites().then(setSites).catch(() => null) }, [])

  const cell = (r: TemplateItem, c: string): string | number => (
    c === 'id' ? r.id : c === 'name' ? r.name : c === 'type' ? r.typeLabel : c === 'ctrl' ? r.controllerAction : c === 'layout' ? r.layout : c === 'site' ? r.siteName : c === 'date' ? r.creationDate : ''
  )

  // Réinitialiser les filtres : recherche + site (le tri repart au défaut via reload/deps).
  function resetFilters() {
    setSearchInput(''); setSearch('')
    setSite(null)
    setTick((x) => x + 1)
  }
  async function confirmDelete() {
    if (!toDelete) return
    try { await deleteTemplate(toDelete.id); removeLocal((r) => r.id === toDelete.id); setToDelete(null); reload() } catch { setToDelete(null) }
  }
  const renderCellNode = (r: TemplateItem, c: string) => {
    if (c === 'type') return <span style={{ display: 'inline-block', padding: '2px 8px', borderRadius: 6, fontSize: 12, background: 'color-mix(in srgb, var(--color-primary) 10%, transparent)', color: 'var(--color-primary)' }}>{r.typeLabel}</span>
    if (c === 'ctrl' || c === 'layout') return <span style={{ fontFamily: 'monospace', fontSize: 13 }}>{String(cell(r, c)) || '—'}</span>
    if (c === 'name') return <span style={{ fontWeight: 500 }}>{r.name}</span>
    if (c === 'date') return fmtDate(r.creationDate)
    return String(cell(r, c)) || '—'
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: narrow ? 14 : 20, padding: narrow ? 14 : 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 }}>
        <div style={narrow ? { minWidth: 0 } : undefined}>
          <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0, ...(narrow ? { overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } : {}) }}>{t('title')}</h1>
          <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', margin: '2px 0 0', ...(narrow ? { overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } : {}) }}>{t('subtitle')}</p>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexShrink: 0 }}>
          <ViewToggle mode={mode} onChange={(m) => { setMode(m); if (m === 'iframe') setFrameLoaded(true) }} compact={narrow} />
          <button style={btnGhost} onClick={() => setTick((x) => x + 1)} title={t('refresh')}>↻</button>
          {can('create') && <button style={btnPrimary} onClick={() => navigate(`${base}/new`)} title={t('new')}><PlusIcon />{!narrow && t('new')}</button>}
        </div>
      </div>

      {/* Vue « Old » : outil Templates legacy en iframe (montée à la 1ʳᵉ activation, gardée en display:none) */}
      {frameLoaded && (
        <div style={{ ...card, display: mode === 'iframe' ? 'flex' : 'none', flex: 1, minHeight: 480, overflow: 'hidden' }}>
          <iframe src={`/melis/react-tool-page?key=${encodeURIComponent(MELIS_KEY)}`}
            style={{ flex: 1, width: '100%', border: 0 }} title="Templates — Vue Melis"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-modals" />
        </div>
      )}

      {/* Vue « New » : liste React native */}
      <div style={{ display: mode === 'react' ? 'flex' : 'none', flexDirection: 'column', gap: 20 }}>
      {!can('list') ? (
        <div style={{ ...card, padding: '40px 16px', textAlign: 'center', fontSize: 14, color: 'var(--color-muted-foreground)' }}>{t('no_access')}</div>
      ) : (<>
      <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
        <Kpi label={t('kpi_total')} value={stats?.total ?? null} />
        <Kpi label={t('kpi_sites')} value={stats?.sites ?? null} />
        <Kpi label={t('kpi_types')} value={stats?.types ?? null} />
      </div>

      <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
        <input style={{ ...inputCss, flex: narrow ? '1 1 100%' : 1, minWidth: narrow ? undefined : 220 }} value={searchInput} onChange={(e) => setSearchInput(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && setSearch(searchInput.trim())} placeholder={t('search')} />
        <select style={{ ...inputCss, width: narrow ? '100%' : 'auto', flex: narrow ? '1 1 100%' : undefined }} value={site ?? ''} onChange={(e) => setSite(e.target.value ? Number(e.target.value) : null)}>
          <option value="">{t('all_sites')}</option>
          {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
        </select>
        {/* reset_filters : toujours sur sa propre ligne pleine largeur sur narrow — le libellé FR
            est trop long pour partager une ligne 50/50 avec Colonnes/Exporter (cf. skill). */}
        <button style={{ ...btnGhost, flex: narrow ? '1 1 100%' : undefined, justifyContent: narrow ? 'center' : undefined }} onClick={resetFilters}><ResetIcon />{t('reset_filters')}</button>
        <div style={{ position: 'relative', flex: narrow ? '1 1 calc(50% - 4px)' : undefined }}>
          <button ref={colBtnRef} style={{ ...btnGhost, width: narrow ? '100%' : undefined, justifyContent: narrow ? 'center' : undefined }} onClick={() => setShowCols((v) => !v)}><GripIcon />{t('columns')}</button>
          {showCols && <ColManager cols={cols} labelFor={(id) => t(COL_LABEL[id])} onChange={setCols} onClose={() => setShowCols(false)} anchorRef={colBtnRef} />}
        </div>
        {can('export') && <button style={{ ...btnGhost, flex: narrow ? '1 1 calc(50% - 4px)' : undefined, justifyContent: narrow ? 'center' : undefined }} onClick={() => setShowExport(true)}><DownloadIcon />{t('export')}</button>}
      </div>

      <div style={{ ...card, overflow: 'hidden' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse', ...(!narrow ? { minWidth: 720 } : {}) }}>
          <thead style={{ background: 'var(--color-muted,rgba(0,0,0,.03))' }}>
            <tr>
              {hasHidden && <th style={{ ...th, width: 36 }} />}
              {visibleCols(displayCols).map(({ id }) => (
                <th key={id} style={{ ...th, cursor: 'pointer', ...(id === 'id' ? { width: 60 } : {}), ...(sortCol === id ? { color: 'var(--color-primary)' } : {}) }} onClick={() => toggleSort(id)}>
                  <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4 }}>{t(COL_LABEL[id])}<SortIcon dir={sortCol === id ? sortDir : null} /></span>
                </th>
              ))}
              <th style={{ ...th, width: 80 }} />
            </tr>
          </thead>
          <tbody>
            {items.length === 0 && !loading ? (
              <tr><td style={{ ...td, textAlign: 'center', color: 'var(--color-muted-foreground)', padding: '40px 16px' }} colSpan={(hasHidden ? 1 : 0) + visibleCols(displayCols).length + 1}>{t('empty')}</td></tr>
            ) : items.map((r) => (
              <Fragment key={r.id}>
                <tr>
                  {hasHidden && <td style={td}><ExpandToggle expanded={expandedRows.has(r.id)} onClick={() => toggleExpanded(r.id)} /></td>}
                  {visibleCols(displayCols).map(({ id }) => (
                    <td key={id} style={{ ...td, ...(id === 'id' ? { color: 'var(--color-muted-foreground)', fontVariantNumeric: 'tabular-nums' } : {}) }}>{renderCellNode(r, id)}</td>
                  ))}
                  <td style={td}>
                    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 4 }}>
                      {can('edit') && <button style={iconBtn} title={t('edit')} onClick={() => navigate(`${base}/${r.id}`)}><PencilIcon /></button>}
                      {can('delete') && <button style={{ ...iconBtn, color: 'var(--color-destructive,#ef4444)' }} title={t('del')} onClick={() => setToDelete(r)}><TrashIcon /></button>}
                    </div>
                  </td>
                </tr>
                {hasHidden && expandedRows.has(r.id) && (
                  <HiddenColsRow cols={displayCols} labelFor={(id) => t(COL_LABEL[id])} renderValue={(id) => renderCellNode(r, id)}
                    colSpan={1 + visibleCols(displayCols).length + 1} narrow={narrow} />
                )}
              </Fragment>
            ))}
          </tbody>
        </table>
        {/* Sentinelle du scroll infini + pied (spinner en chargement, compteur en fin de liste). */}
        <div ref={sentinelRef} style={{ height: 1 }} />
        <div style={{ padding: '10px 16px', textAlign: 'center', fontSize: 12, color: 'var(--color-muted-foreground)' }}>
          {loading ? <Spinner /> : (!hasMore && items.length > 0 ? t('count', { n: total }) : '')}
        </div>
      </div>
      </>)}
      </div>

      {toDelete && (
        <div style={{ position: 'fixed', inset: 0, zIndex: 50, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.5)' }}>
          <div style={{ ...card, padding: 24, width: '100%', maxWidth: 380 }}>
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
        <ExportModal<TemplateItem>
          cols={cols}
          labelFor={(id) => t(COL_LABEL[id])}
          fetchAll={async () => {
            const all: TemplateItem[] = []
            let after: string | undefined
            do {
              const r = await fetchTemplates({ search, site, sort: sortCol, dir: sortDir, after, limit: 100 })
              all.push(...r.items)
              after = r.nextCursor ?? undefined
            } while (after)
            return all
          }}
          getCell={(r, id) => cell(r, id)}
          filename="templates"
          sheetName={t('title')}
          total={total}
          onClose={() => setShowExport(false)}
        />
      )}
    </div>
  )
}

// ── Formulaire d'édition React ────────────────────────────────────────────────
function notify(kind: 'ok' | 'ko', title: string, message: string) {
  window.postMessage({ __melisNotif: true, kind, title, message }, '*')
}

const labelCss: CSSProperties = { fontSize: 13, fontWeight: 500, color: 'var(--color-foreground)', marginBottom: 4, display: 'block' }
const sectionTitle: CSSProperties = { fontSize: 11, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.06em', color: 'var(--color-muted-foreground)', margin: '4px 0 10px' }

function TemplateForm({ id, base }: { id: string; base: string }) {
  const t = useT()
  const navigate = useNavigate()
  const narrow = useIsNarrow()
  const isNew = id === 'new'
  const numId = parseInt(id, 10)
  const [item, setItem] = useState<TemplateItem | null>(null)
  const [sites, setSites] = useState<SiteOption[]>([])
  const [loadErr, setLoadErr] = useState('')
  const [saving, setSaving] = useState(false)
  const [saved, setSaved] = useState(false)
  const [saveErr, setSaveErr] = useState('')
  const [issues, setIssues] = useState<FormIssue[]>([]) // champs fautifs listés dans le bandeau

  // Form fields
  const [name, setName] = useState('')
  const [type, setType] = useState('ZF2')
  const [siteId, setSiteId] = useState<number | null>(null)
  const [websiteFolder, setWebsiteFolder] = useState('')
  const [layout, setLayout] = useState('')
  const [controller, setController] = useState('')
  const [action, setAction] = useState('')
  const [phpPath, setPhpPath] = useState('')

  // Sous-onglet nommé (look Users) : ouvert au montage, renommé avec le nom du template au chargement.
  const subTabId = `${base}/${id}`
  useEffect(() => {
    ;(window as unknown as SubTabW).__melisOpenSubTab?.(base, { id: subTabId, label: isNew ? t('form_new') : t('loading'), path: subTabId })
  }, []) // eslint-disable-line react-hooks/exhaustive-deps
  useEffect(() => {
    if (!isNew && item?.name) (window as unknown as SubTabW).__melisUpdateSubTabLabel?.(base, subTabId, item.name)
  }, [item?.name]) // eslint-disable-line react-hooks/exhaustive-deps

  useEffect(() => { if (!can(isNew ? 'create' : 'edit')) navigate(base) }, [base, navigate, isNew])
  useEffect(() => { fetchTemplateSites().then(setSites).catch(() => null) }, [])
  useEffect(() => {
    if (isNew) return // création : formulaire natif avec des champs vides, pas de fetch.
    if (isNaN(numId) || numId <= 0) { setLoadErr('Invalid ID'); return }
    fetchTemplate(numId).then((tpl) => {
      setItem(tpl)
      setName(tpl.name)
      setType(tpl.type)
      setSiteId(tpl.siteId || null)
      setWebsiteFolder(tpl.websiteFolder)
      setLayout(tpl.layout)
      setController(tpl.controller)
      setAction(tpl.action)
      setPhpPath(tpl.phpPath)
    }).catch((e) => setLoadErr(String(e)))
  }, [numId])

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    if (!isNew && !item) return
    setSaveErr(''); setIssues([]); setSaved(false)
    // Validation client → un item par champ fautif, listé dans le bandeau (pattern unifié).
    if (!name.trim()) { setIssues([{ label: t('field_name'), message: t('err_required') }]); setSaveErr(t('err_check')); return }
    setSaving(true)
    try {
      const savedId = await saveTemplate({ id: isNew ? 0 : item!.id, name, type, siteId, websiteFolder, layout, controller, action, phpPath })
      okNotify(t('title'), t('saved'))
      if (isNew) navigate(`${base}/${savedId}`) // création → bascule sur l'édition du template créé
      else setSaved(true)
    } catch (e) {
      setSaveErr(String(e))
      koNotify(t('title'), t('save_err'))
    } finally {
      setSaving(false)
    }
  }

  if (loadErr) return (
    <div style={{ padding: 24, color: 'var(--color-destructive,#ef4444)', fontSize: 14 }}>{loadErr}</div>
  )
  if (!isNew && !item) return (
    <div style={{ padding: 24, fontSize: 14, color: 'var(--color-muted-foreground)' }}>{t('loading')}</div>
  )

  return (
    <div style={{ display: 'flex', flexDirection: 'column', height: '100%', minHeight: 0, overflow: 'auto' }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: narrow ? '12px 14px' : '12px 24px', borderBottom: '1px solid var(--color-border)', flexShrink: 0 }}>
        <h1 style={{ fontSize: 16, fontWeight: 600, margin: 0, flex: 1, minWidth: 0, ...(narrow ? { overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } : {}) }}>{isNew ? t('form_new') : `${t('form_edit')} — ${item!.name}`}</h1>
        {!isNew && !narrow && <span style={{ fontSize: 12, color: 'var(--color-muted-foreground)', flexShrink: 0 }}>ID {item!.id}</span>}
        <button type="submit" form="template-edit-form" style={{ ...btnPrimary, minWidth: narrow ? undefined : 120, flexShrink: 0 }} disabled={saving}>
          {saving ? t('saving') : saved ? t('saved') : t('save')}
        </button>
      </div>

      {/* Form */}
      <form id="template-edit-form" onSubmit={handleSubmit} style={{ flex: 1, padding: narrow ? 14 : 24, display: 'flex', flexDirection: 'column', gap: narrow ? 16 : 24, maxWidth: 720 }}>
        {/* Bandeau d'erreur unifié (validation client + erreur serveur), en tête de formulaire. */}
        <FormErrorBanner title={saveErr || undefined} issues={issues} />
        {/* Informations de base */}
        <div style={{ ...card, padding: narrow ? 14 : 20, display: 'flex', flexDirection: 'column', gap: 16 }}>
          <p style={sectionTitle}>{t('field_name')} / {t('field_type')} / {t('field_site')}</p>
          <div>
            <label style={labelCss}>{t('field_name')}</label>
            <input style={{ ...inputCss, width: '100%', boxSizing: 'border-box', ...(issues.some((i) => i.label === t('field_name')) ? { borderColor: '#dc2626' } : {}) }} value={name} onChange={(e) => setName(e.target.value)} required />
          </div>
          <div style={{ display: 'flex', flexDirection: narrow ? 'column' : 'row', gap: 12 }}>
            <div style={{ flex: 1 }}>
              <label style={labelCss}>{t('field_type')}</label>
              {/* Legacy : le type ne propose que « Laminas » (ZF2). Pas d'autres options. */}
              <select style={{ ...inputCss, width: '100%', boxSizing: 'border-box' }} value={type} onChange={(e) => setType(e.target.value)}>
                <option value="ZF2">Laminas</option>
              </select>
            </div>
            <div style={{ flex: 1 }}>
              <label style={labelCss}>{t('field_site')}</label>
              <select style={{ ...inputCss, width: '100%', boxSizing: 'border-box' }} value={siteId ?? ''} onChange={(e) => setSiteId(e.target.value ? Number(e.target.value) : null)}>
                <option value="">—</option>
                {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
              </select>
            </div>
          </div>
        </div>

        {/* Layout / Contrôleur / Action (type Laminas) */}
        <div style={{ ...card, padding: narrow ? 14 : 20, display: 'flex', flexDirection: 'column', gap: 16 }}>
          <p style={sectionTitle}>{t('field_layout')} / {t('field_ctrl')} / {t('field_action')}</p>
          <div>
            <label style={labelCss}>{t('field_layout')}</label>
            <input style={{ ...inputCss, width: '100%', boxSizing: 'border-box', fontFamily: 'monospace', fontSize: 13 }} value={layout} onChange={(e) => setLayout(e.target.value)} />
          </div>
          <div style={{ display: 'flex', flexDirection: narrow ? 'column' : 'row', gap: 12 }}>
            <div style={{ flex: 1 }}>
              <label style={labelCss}>{t('field_ctrl')}</label>
              <input style={{ ...inputCss, width: '100%', boxSizing: 'border-box', fontFamily: 'monospace', fontSize: 13 }} value={controller} onChange={(e) => setController(e.target.value)} />
            </div>
            <div style={{ flex: 1 }}>
              <label style={labelCss}>{t('field_action')}</label>
              <input style={{ ...inputCss, width: '100%', boxSizing: 'border-box', fontFamily: 'monospace', fontSize: 13 }} value={action} onChange={(e) => setAction(e.target.value)} />
            </div>
          </div>
        </div>

      </form>
    </div>
  )
}
