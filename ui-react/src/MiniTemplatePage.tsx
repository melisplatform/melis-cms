import { useCallback, useEffect, useMemo, useRef, useState, type CSSProperties, type ChangeEvent } from 'react'
import {
  consumeMiniTemplateListStale, deleteMiniTemplate, fetchMiniTemplateItem, fetchMiniTemplateSites,
  fetchMiniTemplateStats, fetchMiniTemplates, markMiniTemplateListStale, saveMiniTemplate,
  type MiniTemplateItem, type MiniTemplateSiteOption, type MiniTemplateStats,
} from './mini-template-api'
import { ExportModal, DownloadIcon } from './ExportModal'
import { ViewToggle } from './ViewToggle'

/* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
 * Mini-Template Manager (MelisCms) â€” brique full React
 * MontÃ© Ã  /melis-cms/mini-templates. Liste + formulaire ajout/Ã©dition.
 * Identificateur composite (site_module + template_name) â€” pas de PK numÃ©rique.
 * â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */

const MELIS_KEY = 'meliscms_mini_template_manager_tool'
function can(cap: string): boolean {
  return (window as unknown as { MelisCan?: (k: string, c: string) => boolean }).MelisCan?.(MELIS_KEY, cap) ?? true
}

// â”€â”€ i18n minimal â”€â”€
type Lang = 'fr' | 'en'
function currentLang(): Lang {
  const l = (document.documentElement.lang || 'en').toLowerCase()
  return l.startsWith('fr') ? 'fr' : 'en'
}
const DICT: Record<Lang, Record<string, string>> = {
  fr: {
    title: 'Mini-Templates', subtitle: 'Blocs HTML rÃ©utilisables (TinyMCE)',
    new: 'Nouveau template', search: 'Rechercherâ€¦',
    empty_site: 'SÃ©lectionnez un site pour voir ses templates.',
    empty: 'Aucun template trouvÃ©.', count: '{n} templates â€” fin de la liste',
    kpi_total: 'Total', kpi_sites: 'Sites',
    all_sites: 'Tous les sites', select_site: 'â€” Choisir un site â€”',
    col_thumbnail: 'Image', col_path: 'Chemin',
    columns: 'Colonnes', export: 'Exporter',
    cols_visible: 'Visibles', cols_hidden: 'MasquÃ©es', drag_here: 'Glisser ici', reset: 'RÃ©initialiser',
    edit: 'Modifier', del: 'Supprimer', cancel: 'Annuler', save: 'Enregistrer', back: 'retour',
    refresh: 'RafraÃ®chir', loading: 'Chargementâ€¦', saved: 'EnregistrÃ© âœ“',
    del_title: 'Supprimer le template',
    del_confirm: 'Supprimer Â« {n} Â» ? Cette action est irrÃ©versible.',
    new_title: 'Nouveau mini-template', edit_title: 'Modifier le mini-template',
    f_site: 'Site / Module', f_name: 'Nom du template',
    f_name_ph: 'mon_template', f_name_hint: 'Lettres, chiffres et _ uniquement. Doit commencer par une lettre ou _.',
    f_name_invalid: 'Nom invalide (lettres, chiffres, underscore ; commence par lettre ou _).',
    f_html: 'Contenu HTML', f_html_ph: '<!-- HTML du bloc -->',
    f_thumbnail: 'Miniature (png, jpg, gif)', f_thumbnail_hint: 'Optionnel. Formats acceptÃ©s : PNG, JPG, JPEG, GIF.',
    f_thumbnail_change: 'Changer la miniature',
    err_site: 'Le site est obligatoire.', err_name: 'Le nom est obligatoire.', err_save: 'Erreur lors de la sauvegarde.',
    export_filename: 'mini-templates',
    no_access: "Vous nâ€™avez pas les droits pour consulter cette liste.",
  },
  en: {
    title: 'Mini-Templates', subtitle: 'Reusable HTML blocks (TinyMCE)',
    new: 'New template', search: 'Searchâ€¦',
    empty_site: 'Select a site to view its templates.',
    empty: 'No template found.', count: '{n} templates â€” end of list',
    kpi_total: 'Total', kpi_sites: 'Sites',
    all_sites: 'All sites', select_site: 'â€” Choose a site â€”',
    col_thumbnail: 'Image', col_path: 'Path',
    columns: 'Columns', export: 'Export',
    cols_visible: 'Visible', cols_hidden: 'Hidden', drag_here: 'Drag here', reset: 'Reset',
    edit: 'Edit', del: 'Delete', cancel: 'Cancel', save: 'Save', back: 'back',
    refresh: 'Refresh', loading: 'Loadingâ€¦', saved: 'Saved âœ“',
    del_title: 'Delete template',
    del_confirm: 'Delete "{n}"? This action is irreversible.',
    new_title: 'New mini-template', edit_title: 'Edit mini-template',
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

// â”€â”€ Styles â”€â”€
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

// â”€â”€ Column manager â”€â”€
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
const panelCss: CSSProperties = { display: 'flex', flexDirection: 'column', gap: 2, minHeight: 120, borderRadius: 8, border: '1px dashed var(--color-border)', padding: 6 }
const panelTitle: CSSProperties = { padding: '0 6px 4px', fontSize: 10, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.06em', color: 'var(--color-muted-foreground)' }

function ColManager({ cols, labelFor, onChange, onClose }: {
  cols: ColDef[]; labelFor: (id: string) => string; onChange: (c: ColDef[]) => void; onClose: () => void
}) {
  const t = useT()
  const [dragId, setDragId] = useState<string | null>(null)
  const [over, setOver] = useState<{ id: string; panel: 'visible' | 'hidden' } | null>(null)
  const shown = cols.filter((c) => c.visible)
  const hidden = cols.filter((c) => !c.visible)

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

  return (
    <div style={{ ...card, position: 'absolute', right: 0, top: '100%', marginTop: 6, zIndex: 50, width: 380, maxWidth: 'calc(100vw - 1rem)' }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '10px 12px', borderBottom: '1px solid var(--color-border)' }}>
        <span style={{ fontSize: 14, fontWeight: 600 }}>{t('columns')}</span>
        <button style={{ ...iconBtn, width: 22, height: 22 }} onClick={onClose}>âœ•</button>
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

// â”€â”€ KPI card â”€â”€
function Kpi({ label: l, value }: { label: string; value: number | null }) {
  return (
    <div style={{ ...card, display: 'flex', flexDirection: 'column', gap: 2, padding: 16, flex: 1, minWidth: 140 }}>
      <span style={{ fontSize: 12, color: 'var(--color-muted-foreground)' }}>{l}</span>
      <span style={{ fontSize: 22, fontWeight: 700 }}>{value == null ? 'â€¦' : value}</span>
    </div>
  )
}

// â”€â”€ Page root â”€â”€
export default function MiniTemplatePage() {
  const [view, setView] = useState<'list' | 'form'>('list')
  const [editItem, setEditItem] = useState<MiniTemplateItem | null>(null)

  function openNew() { setEditItem(null); setView('form') }
  function openEdit(item: MiniTemplateItem) { setEditItem(item); setView('form') }
  function backToList() { setView('list') }

  if (view === 'form') {
    return <MiniTemplateForm item={editItem} onBack={backToList} />
  }
  return <MiniTemplateList onNew={openNew} onEdit={openEdit} />
}

// â”€â”€ Persistent list cache â”€â”€
type ListCache = {
  items: MiniTemplateItem[]
  total: number
  stats: MiniTemplateStats | null
  sites: MiniTemplateSiteOption[]
  site: string
  search: string
  searchInput: string
  sortAsc: boolean
  cols: ColDef[]
}
let _cache: ListCache | null = null

// â”€â”€ List â”€â”€
function MiniTemplateList({ onNew, onEdit }: { onNew: () => void; onEdit: (item: MiniTemplateItem) => void }) {
  const t = useT()

  const [items, setItems]             = useState<MiniTemplateItem[]>(_cache?.items ?? [])
  const [total, setTotal]             = useState(_cache?.total ?? 0)
  const [stats, setStats]             = useState<MiniTemplateStats | null>(_cache?.stats ?? null)
  const [sites, setSites]             = useState<MiniTemplateSiteOption[]>(_cache?.sites ?? [])
  const [site, setSite]               = useState(_cache?.site ?? '')
  const [searchInput, setSearchInput] = useState(_cache?.searchInput ?? '')
  const [search, setSearch]           = useState(_cache?.search ?? '')
  const [loading, setLoading]         = useState(false)
  const [sortAsc, setSortAsc]         = useState(_cache?.sortAsc ?? true)
  const [cols, setCols]               = useState<ColDef[]>(_cache?.cols ?? loadCols())
  const [showCols, setShowCols]       = useState(false)
  const [showExport, setShowExport]   = useState(false)
  const [toDelete, setToDelete]       = useState<MiniTemplateItem | null>(null)
  const [tick, setTick]               = useState(0)
  const [mode, setMode]               = useState<'react' | 'iframe'>('react')
  const [frameLoaded, setFrameLoaded] = useState(false)

  // Save cache on unmount
  useEffect(() => () => {
    _cache = { items, total, stats, sites, site, search, searchInput, sortAsc, cols }
  })

  // Stale-flag : reload after add/edit
  useEffect(() => {
    if (consumeMiniTemplateListStale()) { setTick((x) => x + 1) }
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

  useEffect(() => {
    if (!site) { setItems([]); setTotal(0); return }
    setLoading(true)
    fetchMiniTemplates({ site, search })
      .then((r) => { setItems(r.items); setTotal(r.total) })
      .catch(() => null)
      .finally(() => setLoading(false))
  }, [site, search, tick])

  const sorted = useMemo(() => [...items].sort((a, b) => sortAsc ? a.path.localeCompare(b.path) : b.path.localeCompare(a.path)), [items, sortAsc])

  async function confirmDelete() {
    if (!toDelete) return
    try {
      await deleteMiniTemplate(toDelete.site, toDelete.name)
      setToDelete(null)
      setTick((x) => x + 1)
    } catch { setToDelete(null) }
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, padding: 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 }}>
        <div>
          <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0 }}>{t('title')}</h1>
          <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', margin: '2px 0 0' }}>{t('subtitle')}</p>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          <ViewToggle mode={mode} onChange={(m) => { setMode(m); if (m === 'iframe') setFrameLoaded(true) }} />
          <button style={btnGhost} onClick={() => { _cache = null; setTick((x) => x + 1) }} title={t('refresh')}>â†»</button>
          {can('create') && <button style={btnPrimary} onClick={onNew}><PlusIcon />{t('new')}</button>}
        </div>
      </div>

      {/* Vue Old (iframe) */}
      {frameLoaded && (
        <div style={{ ...card, display: mode === 'iframe' ? 'flex' : 'none', flex: 1, minHeight: 480, overflow: 'hidden' }}>
          <iframe src={`/melis/react-tool-page?key=${encodeURIComponent(MELIS_KEY)}`}
            style={{ flex: 1, width: '100%', border: 0 }} title="Mini-Templates â€” Vue Melis"
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
          <input style={{ ...inputCss, height: 36, flex: 1, minWidth: 180 }} value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && setSearch(searchInput.trim())}
            placeholder={t('search')} />
          <select style={{ ...inputCss, height: 36, width: 'auto', minWidth: 160 }}
            value={site} onChange={(e) => setSite(e.target.value)}>
            <option value="">{t('all_sites')}</option>
            {sites.map((s) => <option key={s.module} value={s.module}>{s.name}</option>)}
          </select>
          <div style={{ position: 'relative' }}>
            <button style={{ ...btnGhost, height: 36 }} onClick={() => setShowCols((v) => !v)}><GripIcon />{t('columns')}</button>
            {showCols && <ColManager cols={cols} labelFor={(id) => t(COL_LABEL[id])} onChange={setCols} onClose={() => setShowCols(false)} />}
          </div>
          {can('export') && (
            <button style={{ ...btnGhost, height: 36 }} onClick={() => setShowExport(true)}><DownloadIcon />{t('export')}</button>
          )}
        </div>

        {/* Table */}
        <div style={{ ...card, overflow: 'hidden' }}>
          {!site ? (
            <div style={{ padding: '48px 16px', textAlign: 'center', fontSize: 14, color: 'var(--color-muted-foreground)' }}>{t('empty_site')}</div>
          ) : (
            <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: 480 }}>
              <thead style={{ background: 'var(--color-muted,rgba(0,0,0,.03))' }}>
                <tr>
                  {visibleCols(cols).map(({ id }) => (
                    <th key={id} style={{ ...th, ...(id === 'path' ? { cursor: 'pointer' } : {}), ...(id === 'thumbnail' ? { width: 80 } : {}) }}
                      onClick={id === 'path' ? () => setSortAsc((v) => !v) : undefined}>
                      {t(COL_LABEL[id])}{id === 'path' ? ` ${sortAsc ? 'â†‘' : 'â†“'}` : ''}
                    </th>
                  ))}
                  <th style={{ ...th, width: 80 }} />
                </tr>
              </thead>
              <tbody>
                {sorted.length === 0 && !loading ? (
                  <tr><td style={{ ...td, textAlign: 'center', color: 'var(--color-muted-foreground)', padding: '40px 16px' }} colSpan={visibleCols(cols).length + 1}>{t('empty')}</td></tr>
                ) : sorted.map((r) => (
                  <tr key={`${r.site}:${r.name}`}>
                    {visibleCols(cols).map(({ id }) => (
                      <td key={id} style={td}>
                        {id === 'thumbnail' && (
                          r.thumbnailUrl
                            ? <img src={r.thumbnailUrl} alt={r.name} style={{ width: 52, height: 40, objectFit: 'cover', borderRadius: 4, display: 'block' }} />
                            : <div style={{ width: 52, height: 40, borderRadius: 4, background: 'var(--color-muted,rgba(0,0,0,.06))', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 10, color: 'var(--color-muted-foreground)' }}>â€”</div>
                        )}
                        {id === 'path' && <span style={{ fontFamily: 'monospace', fontSize: 13 }}>{r.path}</span>}
                      </td>
                    ))}
                    <td style={td}>
                      <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 4 }}>
                        {can('edit') && <button style={iconBtn} title={t('edit')} onClick={() => onEdit(r)}><PencilIcon /></button>}
                        {can('delete') && <button style={{ ...iconBtn, color: 'var(--color-destructive,#ef4444)' }} title={t('del')} onClick={() => setToDelete(r)}><TrashIcon /></button>}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
          <div style={{ padding: '10px 16px', textAlign: 'center', fontSize: 12, color: 'var(--color-muted-foreground)' }}>
            {site && (loading ? t('loading') : t('count', { n: total }))}
          </div>
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
          fetchAll={async () => (await fetchMiniTemplates({ site, search })).items}
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

// â”€â”€ TinyMCE â€” brick-local loader (mirrors MelisToolEditor from melis-core) â”€â”€
declare global {
  interface Window {
    tinymce?: any
    tinyMceCleaner?: (ed: any) => void
    __melisMiniTemplateExtensions?: {
      renderHtmlActions?: (
        onContent: (html: string) => void,
        context: { site?: string; name?: string }
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
    let disposed = false
    ensureMce().then((ok) => {
      if (disposed || !ok || !window.tinymce || !_toolCfg) return
      const cfg: Record<string, unknown> = {
        ...JSON.parse(JSON.stringify(_toolCfg)),
        selector: `#${EDITOR_ID}`,
        base_url: MCE_BASE,
        height: 320,
        min_height: 320,
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

// â”€â”€ Form (add / edit) â”€â”€
function MiniTemplateForm({ item, onBack }: { item: MiniTemplateItem | null; onBack: () => void }) {
  const t    = useT()
  const isEdit = item !== null

  const [sites, setSites]       = useState<MiniTemplateSiteOption[]>([])
  const [site, setSite]         = useState(item?.site ?? '')
  const [name, setName]         = useState(item?.name ?? '')
  const [html, setHtml]         = useState('')
  const [thumbUrl, setThumbUrl] = useState<string | null>(item?.thumbnailUrl ?? null)
  const [thumbFile, setThumbFile] = useState<File | null>(null)
  const [thumbPreview, setThumbPreview] = useState<string | null>(item?.thumbnailUrl ?? null)
  const [loading, setLoading]   = useState(false)
  const [saving, setSaving]     = useState(false)
  const [error, setError]       = useState<string | null>(null)
  const [saved, setSaved]       = useState(false)
  const [nameError, setNameError] = useState(false)
  const [siteError, setSiteError] = useState(false)
  const [nameRequired, setNameRequired] = useState(false)
  const fileRef = useRef<HTMLInputElement>(null)
  // Re-render when the community-extensions brick registers after initial mount.
  const [, forceUpdate] = useState(0)
  useEffect(() => {
    const handler = () => forceUpdate((n) => n + 1)
    window.addEventListener('melis-ai-community-extensions-loaded', handler)
    return () => window.removeEventListener('melis-ai-community-extensions-loaded', handler)
  }, [])

  useEffect(() => { if (!can(isEdit ? 'edit' : 'create')) onBack() }, [isEdit, onBack])
  useEffect(() => { fetchMiniTemplateSites().then(setSites).catch(() => null) }, [])

  // Load existing template HTML on edit
  useEffect(() => {
    if (!item) return
    setLoading(true)
    fetchMiniTemplateItem(item.site, item.name)
      .then((d) => {
        setHtml(d.html)
        setThumbUrl(d.thumbnailUrl)
        setThumbPreview(d.thumbnailUrl)
      })
      .catch(() => onBack())
      .finally(() => setLoading(false))
  }, [item])

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
        oldSite: isEdit ? item!.site : undefined,
        oldName: isEdit ? item!.name : undefined,
        thumbnail: thumbFile,
      })
      markMiniTemplateListStale()
      setSaved(true)
      setTimeout(onBack, 600)
    } catch (e) {
      setError(e instanceof Error ? e.message : t('err_save'))
    } finally { setSaving(false) }
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, padding: 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
          <button style={{ ...btnGhost, height: 32, padding: '0 10px' }} onClick={onBack}>â† {t('back')}</button>
          <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0 }}>{isEdit ? t('edit_title') : t('new_title')}</h1>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
          {saved && <span style={{ fontSize: 14, color: '#059669' }}>{t('saved')}</span>}
          <button style={btnPrimary} onClick={submit} disabled={saving || loading}>{saving ? 'â€¦' : t('save')}</button>
        </div>
      </div>

      {error && (
        <div style={{ ...card, borderColor: '#fca5a5', background: '#fef2f2', color: '#b91c1c', padding: '8px 14px', fontSize: 14 }}>{error}</div>
      )}

      {loading ? (
        <div style={{ padding: 48, textAlign: 'center', color: 'var(--color-muted-foreground)' }}>{t('loading')}</div>
      ) : (
        <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gap: 20, alignItems: 'start' }}>
          {/* Colonne gauche : site + nom + HTML */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            <div style={{ ...card, padding: 20 }}>
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
            <div style={{ ...card, padding: 20 }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 4 }}>
                <label style={{ ...lbl, marginBottom: 0 }}>{t('f_html')}</label>
                {window.__melisMiniTemplateExtensions?.renderHtmlActions?.(
                  (htmlContent) => {
                    setHtml(htmlContent)
                    try { window.tinymce?.get(EDITOR_ID)?.setContent(htmlContent) } catch { /* */ }
                  },
                  { site, name }
                )}
              </div>
              <TinyMceField value={html} onChange={setHtml} />
            </div>
          </div>

          {/* Colonne droite : miniature */}
          <div style={{ ...card, padding: 20 }}>
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



