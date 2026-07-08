import { useEffect, useMemo, useState, type CSSProperties } from 'react'
import { useLocation, useNavigate, useParams } from 'react-router-dom'
import {
  deletePlatformId, fetchPlatformIdById, fetchPlatformIds, fetchPlatformIdStats,
  savePlatformId, type PlatformIdItem, type PlatformIdStats, type AvailablePlatform,
} from './cms-platform-id-api'
import { ExportModal, DownloadIcon } from './ExportModal'
import { ViewToggle } from './ViewToggle'

// Outil Platforms IDs legacy (vue « Old » en iframe). Voir brick.manifest.json.
const MELIS_KEY = 'meliscms_tool_platform_ids'

// API sub-tabs de l'hôte (la brique ne peut pas importer le contexte React de l'hôte)
type SubTabW = {
  __melisOpenSubTab?: (section: string, tab: { id: string; label: string; path: string }) => void
  __melisUpdateSubTabLabel?: (section: string, id: string, label: string) => void
}

// Capacités (droits avancés) : la brique ne peut PAS importer le hook hôte → lit le global window.MelisCan.
// Default-allow (true) tant que non chargé / pour un admin ; l'API reste gardée côté serveur (403).
function can(cap: string): boolean {
  return (window as unknown as { MelisCan?: (k: string, c: string) => boolean }).MelisCan?.(MELIS_KEY, cap) ?? true
}

/* ──────────────────────────────────────────────────────────────────────────
 * Brique « Platforms IDs » (MelisCms) — full React, montée à /melis-cms/platform-ids
 * (et /melis-cms/platform-ids/:id pour le formulaire). La brique ne peut PAS importer les
 * modules de l'hôte (Tailwind/shadcn/i18n) : tout est en styles inline + variables CSS
 * du thème, avec un mini-dictionnaire FR/EN lu depuis <html lang> (posé par l'hôte).
 * ────────────────────────────────────────────────────────────────────────── */

// ── i18n minimal (la brique ne partage pas le dictionnaire de l'hôte) ──
type Lang = 'fr' | 'en'
function currentLang(): Lang {
  const l = (document.documentElement.lang || 'en').toLowerCase()
  return l.startsWith('fr') ? 'fr' : 'en'
}
const DICT: Record<Lang, Record<string, string>> = {
  fr: {
    title: 'Platforms IDs', subtitle: 'Plages d’ID de pages et templates par plateforme',
    new: 'Nouvelle plage', search: 'Rechercher une plage…',
    empty: 'Aucune plage trouvée', count: '{n} plages — fin de la liste',
    kpi_total: 'Total',
    col_id: 'ID', col_name: 'Plateforme', col_page_start: 'Début page', col_page_current: 'Page courante', col_page_end: 'Fin page',
    col_tpl_start: 'Début template', col_tpl_current: 'Template courant', col_tpl_end: 'Fin template',
    f_platform: 'Plateforme', f_platform_ph: '— Choisir une plateforme —',
    no_available: 'Toutes les plateformes ont déjà une plage définie.',
    err_platform: 'Veuillez choisir une plateforme.',
    columns: 'Colonnes', export: 'Exporter', cols_visible: 'Visibles', cols_hidden: 'Masquées', drag_here: 'Glisser ici', reset: 'Réinitialiser',
    edit: 'Modifier', del: 'Supprimer', cancel: 'Annuler', save: 'Enregistrer', back: 'retour',
    refresh: 'Rafraîchir', loading: 'Chargement…', saved: 'Enregistré ✓',
    del_title: 'Supprimer la plage', del_confirm: 'Supprimer la plage #{u} ? Cette action est irréversible.',
    new_title: 'Nouvelle plage', edit_title: 'Modifier la plage',
    sec_page: 'IDs de pages', sec_tpl: 'IDs de templates',
    f_start: 'Début', f_current: 'Courant', f_end: 'Fin',
    err_save: 'Erreur lors de la sauvegarde',
    err_int: 'Les valeurs doivent être des entiers ≥ 0.',
    err_order_page: 'Pour les pages : début ≤ courant ≤ fin.',
    err_order_tpl: 'Pour les templates : début ≤ courant ≤ fin.',
    no_access: 'Vous n’avez pas les droits pour consulter cette liste.',
  },
  en: {
    title: 'Platforms IDs', subtitle: 'Page & template ID ranges per platform',
    new: 'New range', search: 'Search a range…',
    empty: 'No range found', count: '{n} ranges — end of list',
    kpi_total: 'Total',
    col_id: 'ID', col_name: 'Platform', col_page_start: 'Page start', col_page_current: 'Page current', col_page_end: 'Page end',
    col_tpl_start: 'Template start', col_tpl_current: 'Template current', col_tpl_end: 'Template end',
    f_platform: 'Platform', f_platform_ph: '— Choose a platform —',
    no_available: 'All platforms already have a range defined.',
    err_platform: 'Please choose a platform.',
    columns: 'Columns', export: 'Export', cols_visible: 'Visible', cols_hidden: 'Hidden', drag_here: 'Drag here', reset: 'Reset',
    edit: 'Edit', del: 'Delete', cancel: 'Cancel', save: 'Save', back: 'back',
    refresh: 'Refresh', loading: 'Loading…', saved: 'Saved ✓',
    del_title: 'Delete range', del_confirm: 'Delete range #{u}? This action is irreversible.',
    new_title: 'New range', edit_title: 'Edit range',
    sec_page: 'Page IDs', sec_tpl: 'Template IDs',
    f_start: 'Start', f_current: 'Current', f_end: 'End',
    err_save: 'Error while saving',
    err_int: 'Values must be integers ≥ 0.',
    err_order_page: 'For pages: start ≤ current ≤ end.',
    err_order_tpl: 'For templates: start ≤ current ≤ end.',
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
const secTitle: CSSProperties = { fontSize: 11, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.06em', color: 'var(--color-muted-foreground)', margin: '0 0 10px' }
const numCell: CSSProperties = { fontVariantNumeric: 'tabular-nums' }

const sIcon = { width: 15, height: 15, flexShrink: 0 } as const
const PencilIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>
const TrashIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /></svg>
const PlusIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14M5 12h14" /></svg>

// ── Colonnes (masquer + réordonner par glisser-déposer, persisté) ──
type ColDef = { id: string; visible: boolean }
const COL_ORDER = ['name', 'id', 'pageStart', 'pageCurrent', 'pageEnd', 'tplStart', 'tplCurrent', 'tplEnd'] as const
const COL_LABEL: Record<string, string> = {
  name: 'col_name', id: 'col_id', pageStart: 'col_page_start', pageCurrent: 'col_page_current', pageEnd: 'col_page_end',
  tplStart: 'col_tpl_start', tplCurrent: 'col_tpl_current', tplEnd: 'col_tpl_end',
}
const DEFAULT_COLS: ColDef[] = COL_ORDER.map((id) => ({ id, visible: id !== 'id' }))
const COL_KEY = 'melis-cms-platform-ids-cols-v2'
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

const panelCss: CSSProperties = { display: 'flex', flexDirection: 'column', gap: 2, minHeight: 130, borderRadius: 8, border: '1px dashed var(--color-border)', padding: 6 }
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

  return (
    <div style={{ ...card, position: 'absolute', right: 0, top: '100%', marginTop: 6, zIndex: 50, width: 380, maxWidth: 'calc(100vw - 1rem)' }}>
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

// Valeur numérique d'une cellule (table + export).
function cellValue(r: PlatformIdItem, id: string): number {
  switch (id) {
    case 'id': return r.id
    case 'pageStart': return r.pageStart
    case 'pageCurrent': return r.pageCurrent
    case 'pageEnd': return r.pageEnd
    case 'tplStart': return r.tplStart
    case 'tplCurrent': return r.tplCurrent
    case 'tplEnd': return r.tplEnd
    default: return 0
  }
}

// ════════════════════════════════════════════════════════════════════════════
export default function CmsPlatformIdPage({ active = true }: { active?: boolean }) {
  const { id } = useParams()
  const location = useLocation()
  // Persistante (manifest) : reste montée au changement d'onglet → on GÈLE le route quand inactive
  // (sinon lecture d'un :id étranger → bascule formulaire + fetch + navigate = détournement). Cf. skill.
  const [frozen, setFrozen] = useState({ id, pathname: location.pathname })
  useEffect(() => { if (active) setFrozen({ id, pathname: location.pathname }) }, [active, id, location.pathname])
  const effId = active ? id : frozen.id
  const effPath = active ? location.pathname : frozen.pathname
  // base = route de la liste (pathname sans le segment /:id éventuel)
  const base = effId ? effPath.slice(0, effPath.length - effId.length - 1) : effPath

  if (effId) return <CmsPlatformIdForm id={effId} base={base} />
  return <CmsPlatformIdList base={base} />
}

// ── Liste ───────────────────────────────────────────────────────────────────
function CmsPlatformIdList({ base }: { base: string }) {
  const t = useT()
  const navigate = useNavigate()
  const [items, setItems] = useState<PlatformIdItem[]>([])
  const [stats, setStats] = useState<PlatformIdStats | null>(null)
  const [loading, setLoading] = useState(false)
  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [sortCol, setSortCol] = useState<string>('id')
  const [sortAsc, setSortAsc] = useState(false)
  const [toDelete, setToDelete] = useState<PlatformIdItem | null>(null)
  const [tick, setTick] = useState(0)
  const [cols, setCols] = useState<ColDef[]>(loadCols)
  const [showCols, setShowCols] = useState(false)
  const [showExport, setShowExport] = useState(false)
  const [mode, setMode] = useState<'react' | 'iframe'>('react')
  const [frameLoaded, setFrameLoaded] = useState(false)

  useEffect(() => { fetchPlatformIdStats().then(setStats).catch(() => null) }, [tick])
  useEffect(() => {
    setLoading(true)
    fetchPlatformIds({ search })
      .then((r) => setItems(r.items)).catch(() => null).finally(() => setLoading(false))
  }, [search, tick])

  const sorted = useMemo(() => [...items].sort((a, b) => {
    const cmp = sortCol === 'name'
      ? a.name.localeCompare(b.name, undefined, { sensitivity: 'base' })
      : cellValue(a, sortCol) - cellValue(b, sortCol)
    return sortAsc ? cmp : -cmp
  }), [items, sortCol, sortAsc])

  function toggleSort(id: string) { if (sortCol === id) setSortAsc((v) => !v); else { setSortCol(id); setSortAsc(true) } }

  async function confirmDelete() {
    if (!toDelete) return
    try { await deletePlatformId(toDelete.id); setToDelete(null); setTick((x) => x + 1) }
    catch { setToDelete(null) }
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
          <button style={btnGhost} onClick={() => setTick((x) => x + 1)} title={t('refresh')}>↻</button>
          {can('create') && (() => {
            // On ne peut créer une plage que s'il reste une plateforme SANS plage.
            const hasAvail = !stats || (stats.availablePlatforms?.length ?? 0) > 0
            return (
              <button style={{ ...btnPrimary, opacity: hasAvail ? 1 : 0.5, cursor: hasAvail ? 'pointer' : 'not-allowed' }}
                disabled={!hasAvail} title={hasAvail ? '' : t('no_available')}
                onClick={() => { if (hasAvail) navigate(`${base}/new`) }}>
                <PlusIcon />{t('new')}
              </button>
            )
          })()}
        </div>
      </div>

      {/* Vue « Old » : outil Platforms IDs legacy en iframe (montée à la 1ʳᵉ activation, gardée en display:none) */}
      {frameLoaded && (
        <div style={{ ...card, display: mode === 'iframe' ? 'flex' : 'none', flex: 1, minHeight: 480, overflow: 'hidden' }}>
          <iframe src={`/melis/react-tool-page?key=${encodeURIComponent(MELIS_KEY)}`}
            style={{ flex: 1, width: '100%', border: 0 }} title="Platforms IDs — Vue Melis"
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
      </div>

      {/* Filtres */}
      <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
        <input style={{ ...inputCss, height: 36, flex: 1, minWidth: 220 }} value={searchInput}
          onChange={(e) => setSearchInput(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && setSearch(searchInput.trim())}
          placeholder={t('search')} />
        <div style={{ position: 'relative' }}>
          <button style={{ ...btnGhost, height: 36 }} onClick={() => setShowCols((v) => !v)}><GripIcon />{t('columns')}</button>
          {showCols && <ColManager cols={cols} labelFor={(id) => t(COL_LABEL[id])} onChange={setCols} onClose={() => setShowCols(false)} />}
        </div>
        {can('export') && <button style={{ ...btnGhost, height: 36 }} onClick={() => setShowExport(true)}><DownloadIcon />{t('export')}</button>}
      </div>

      {/* Table */}
      <div style={{ ...card, overflow: 'hidden' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: 720 }}>
          <thead style={{ background: 'var(--color-muted,rgba(0,0,0,.03))' }}>
            <tr>
              {visibleCols(cols).map(({ id }) => (
                <th key={id} style={{ ...th, cursor: 'pointer', ...(id === 'id' ? { width: 70 } : {}) }}
                  onClick={() => toggleSort(id)}>
                  {t(COL_LABEL[id])}{sortCol === id ? (sortAsc ? ' ↑' : ' ↓') : ''}
                </th>
              ))}
              <th style={{ ...th, width: 80 }} />
            </tr>
          </thead>
          <tbody>
            {sorted.length === 0 && !loading ? (
              <tr><td style={{ ...td, textAlign: 'center', color: 'var(--color-muted-foreground)', padding: '40px 16px' }} colSpan={visibleCols(cols).length + 1}>{t('empty')}</td></tr>
            ) : sorted.map((r) => (
              <tr key={r.id}>
                {visibleCols(cols).map(({ id }) => (
                  <td key={id} style={{ ...td, ...(id === 'name' ? { fontWeight: 500 } : numCell), ...(id === 'id' ? { color: 'var(--color-muted-foreground)' } : {}) }}>
                    {id === 'name' ? (r.name || '—') : cellValue(r, id)}
                  </td>
                ))}
                <td style={td}>
                  <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 4 }}>
                    {can('edit') && <button style={iconBtn} title={t('edit')} onClick={() => navigate(`${base}/${r.id}`)}><PencilIcon /></button>}
                    {can('delete') && <button style={{ ...iconBtn, color: 'var(--color-destructive,#ef4444)' }} title={t('del')} onClick={() => setToDelete(r)}><TrashIcon /></button>}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <div style={{ padding: '10px 16px', textAlign: 'center', fontSize: 12, color: 'var(--color-muted-foreground)' }}>
          {loading ? t('loading') : t('count', { n: items.length })}
        </div>
      </div>
      </>)}
      </div>

      {/* Suppression */}
      {toDelete && (
        <div style={{ position: 'fixed', inset: 0, zIndex: 50, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.5)' }}>
          <div style={{ ...card, padding: 24, width: '100%', maxWidth: 360 }}>
            <h3 style={{ fontSize: 16, fontWeight: 600, margin: 0 }}>{t('del_title')}</h3>
            <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', marginTop: 8 }}>{t('del_confirm', { u: toDelete.id })}</p>
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 20 }}>
              <button style={btnGhost} onClick={() => setToDelete(null)}>{t('cancel')}</button>
              <button style={{ ...btnGhost, borderColor: '#fca5a5', color: '#dc2626' }} onClick={confirmDelete}>{t('del')}</button>
            </div>
          </div>
        </div>
      )}

      {showExport && (
        <ExportModal<PlatformIdItem>
          cols={cols}
          labelFor={(id) => t(COL_LABEL[id])}
          fetchAll={async () => (await fetchPlatformIds({ search })).items}
          getCell={(r, id) => id === 'name' ? r.name : cellValue(r, id)}
          filename="platform-ids"
          sheetName={t('title')}
          total={items.length}
          onClose={() => setShowExport(false)}
        />
      )}
    </div>
  )
}

// ── Formulaire ────────────────────────────────────────────────────────────────
function NumField({ lbl, value, onChange }: { lbl: string; value: string; onChange: (v: string) => void }) {
  return (
    <div>
      <label style={label}>{lbl}</label>
      <input style={{ ...inputCss, fontVariantNumeric: 'tabular-nums' }} type="number" min={0} step={1}
        value={value} onChange={(e) => onChange(e.target.value)} autoComplete="off" />
    </div>
  )
}

function CmsPlatformIdForm({ id, base }: { id: string; base: string }) {
  const t = useT()
  const navigate = useNavigate()
  const isEdit = id !== 'new'
  const platformId = isEdit ? parseInt(id) : null

  const [name, setName] = useState('')
  // Création : plateformes sans plage + sélection (pids_id = plf_id choisi).
  const [avail, setAvail] = useState<AvailablePlatform[]>([])
  const [newPlatform, setNewPlatform] = useState<number | ''>('')
  const [pageStart, setPageStart] = useState('')
  const [pageCurrent, setPageCurrent] = useState('')
  const [pageEnd, setPageEnd] = useState('')
  const [tplStart, setTplStart] = useState('')
  const [tplCurrent, setTplCurrent] = useState('')
  const [tplEnd, setTplEnd] = useState('')
  const [loading, setLoading] = useState(false)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [saved, setSaved] = useState(false)

  const subTabId = `${base}/${id}`
  useEffect(() => {
    const label = isEdit ? t('loading') : t('new_title')
    ;(window as unknown as SubTabW).__melisOpenSubTab?.(base, { id: subTabId, label, path: subTabId })
  }, [])
  useEffect(() => {
    // Libellé du sous-onglet : nom de la plateforme en édition (au lieu de « #id »), « Nouvelle plage »
    // en création. updateLabel (et pas seulement openSubTab, idempotent) garantit que le libellé est posé.
    const label = isEdit ? (name || '') : t('new_title')
    if (label) (window as unknown as SubTabW).__melisUpdateSubTabLabel?.(base, subTabId, label)
  }, [loading, isEdit, name]) // eslint-disable-line react-hooks/exhaustive-deps

  useEffect(() => { if (!can(isEdit ? 'edit' : 'create')) navigate(base) }, [isEdit, base, navigate])
  // Création : charge les plateformes SANS plage (pour le sélecteur). Aucune → retour liste (garde-fou).
  useEffect(() => {
    if (isEdit) return
    fetchPlatformIdStats().then((s) => {
      const list = s.availablePlatforms || []
      setAvail(list)
      if (list.length === 0) navigate(base)
      else if (list.length === 1) setNewPlatform(list[0].id)
    }).catch(() => null)
  }, [isEdit]) // eslint-disable-line react-hooks/exhaustive-deps
  useEffect(() => {
    if (!platformId) return
    setLoading(true)
    fetchPlatformIdById(platformId)
      .then((r) => {
        setName(r.name)
        setPageStart(String(r.pageStart)); setPageCurrent(String(r.pageCurrent)); setPageEnd(String(r.pageEnd))
        setTplStart(String(r.tplStart)); setTplCurrent(String(r.tplCurrent)); setTplEnd(String(r.tplEnd))
      })
      .catch(() => navigate(base))
      .finally(() => setLoading(false))
  }, [platformId])

  // Entier ≥ 0 ; rejette les non-numériques / décimaux.
  function parseInt0(v: string): number | null {
    const s = v.trim()
    if (!/^\d+$/.test(s)) return null
    const n = parseInt(s, 10)
    return Number.isInteger(n) && n >= 0 ? n : null
  }

  async function submit() {
    setError(null)
    if (!isEdit && !newPlatform) { setError(t('err_platform')); return }
    const ps = parseInt0(pageStart), pc = parseInt0(pageCurrent), pe = parseInt0(pageEnd)
    const ts = parseInt0(tplStart), tc = parseInt0(tplCurrent), te = parseInt0(tplEnd)
    if ([ps, pc, pe, ts, tc, te].some((n) => n === null)) { setError(t('err_int')); return }
    if (!(ps! <= pc! && pc! <= pe!)) { setError(t('err_order_page')); return }
    if (!(ts! <= tc! && tc! <= te!)) { setError(t('err_order_tpl')); return }
    setSaving(true)
    try {
      await savePlatformId({
        id: platformId,
        platformId: isEdit ? null : Number(newPlatform),
        pageStart: ps!, pageCurrent: pc!, pageEnd: pe!,
        tplStart: ts!, tplCurrent: tc!, tplEnd: te!,
      })
      setSaved(true)
      notify('ok', t('title'), t('saved'))
      setTimeout(() => navigate(base), 500)
    } catch (e) {
      setError(e instanceof Error ? e.message : t('err_save'))
    } finally { setSaving(false) }
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, padding: 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
          <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0 }}>{isEdit ? t('edit_title') : t('new_title')}{isEdit && name ? ` — ${name}` : ''}</h1>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
          {saved && <span style={{ fontSize: 14, color: '#059669' }}>{t('saved')}</span>}
          <button style={btnPrimary} onClick={submit} disabled={saving || loading}>{saving ? '…' : t('save')}</button>
        </div>
      </div>

      {error && <div style={{ ...card, borderColor: '#fca5a5', background: '#fef2f2', color: '#b91c1c', padding: '8px 14px', fontSize: 14 }}>{error}</div>}

      {loading ? (
        <div style={{ padding: 48, textAlign: 'center', color: 'var(--color-muted-foreground)' }}>{t('loading')}</div>
      ) : (
        <div style={{ ...card, padding: 20, maxWidth: 640, display: 'flex', flexDirection: 'column', gap: 24 }}>
          {/* Plateforme : affichée non éditable en édition ; en création on choisit une plateforme SANS plage. */}
          {isEdit ? (
            <div>
              <label style={label}>{t('f_platform')}</label>
              <input style={{ ...inputCss, opacity: 0.7 }} value={name} disabled readOnly />
            </div>
          ) : (
            <div>
              <label style={label}>{t('f_platform')}</label>
              <select style={inputCss} value={newPlatform} onChange={(e) => setNewPlatform(e.target.value ? Number(e.target.value) : '')}>
                <option value="">{t('f_platform_ph')}</option>
                {avail.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
              </select>
            </div>
          )}
          {/* Page IDs */}
          <div>
            <p style={secTitle}>{t('sec_page')}</p>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 12 }}>
              <NumField lbl={t('f_start')} value={pageStart} onChange={setPageStart} />
              <NumField lbl={t('f_current')} value={pageCurrent} onChange={setPageCurrent} />
              <NumField lbl={t('f_end')} value={pageEnd} onChange={setPageEnd} />
            </div>
          </div>
          {/* Template IDs */}
          <div>
            <p style={secTitle}>{t('sec_tpl')}</p>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 12 }}>
              <NumField lbl={t('f_start')} value={tplStart} onChange={setTplStart} />
              <NumField lbl={t('f_current')} value={tplCurrent} onChange={setTplCurrent} />
              <NumField lbl={t('f_end')} value={tplEnd} onChange={setTplEnd} />
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
