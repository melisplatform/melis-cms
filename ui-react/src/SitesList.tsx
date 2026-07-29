import { useEffect, useState } from 'react'

import { ViewToggle } from './ViewToggle'
import { ExportModal } from './ExportModal'
import { useKeysetList } from './use-keyset-list'
import { fetchSites, deleteSite, minifyAssets, consumeSitesListStale, type SiteItem } from './sites-api'

const MELIS_KEY = 'meliscms_tool_sites'

// Capacités — la brique lit le global window.MelisCan (pas d'import @/ hôte).
function can(cap: string): boolean {
  return (window as unknown as { MelisCan?: (k: string, c: string) => boolean }).MelisCan?.(MELIS_KEY, cap) ?? true
}

const LANG = (document.documentElement.lang || 'fr').slice(0, 2)
const DICT: Record<string, { fr: string; en: string }> = {
  title: { fr: 'Sites', en: 'Sites' },
  subtitle: { fr: 'Gérer les sites de la plateforme.', en: 'Manage the platform sites.' },
  refresh: { fr: 'Rafraîchir', en: 'Refresh' },
  new: { fr: 'Nouveau site', en: 'New site' },
  search: { fr: 'Rechercher un site…', en: 'Search a site…' },
  reset_filters: { fr: 'Réinitialiser les filtres', en: 'Reset filters' },
  columns: { fr: 'Colonnes', en: 'Columns' },
  export: { fr: 'Exporter', en: 'Export' },
  cols_visible: { fr: 'Visibles', en: 'Visible' },
  cols_hidden: { fr: 'Masquées', en: 'Hidden' },
  drag_here: { fr: 'Glisser ici', en: 'Drag here' },
  reset: { fr: 'Réinitialiser', en: 'Reset' },
  col_id: { fr: 'ID', en: 'ID' },
  col_label: { fr: 'Nom du site', en: 'Site name' },
  col_name: { fr: 'Module', en: 'Module' },
  col_lang: { fr: 'Langues', en: 'Languages' },
  edit: { fr: 'Éditer', en: 'Edit' },
  del: { fr: 'Supprimer', en: 'Delete' },
  minify: { fr: 'Minifier les assets', en: 'Minify assets' },
  minify_disabled: { fr: 'Module introuvable — minification indisponible', en: 'Module not found — minification unavailable' },
  minify_success: { fr: 'Assets minifiées avec succès', en: 'Assets minified successfully' },
  minify_error: { fr: 'Erreur lors de la minification', en: 'Error while minifying assets' },
  empty: { fr: 'Aucun site.', en: 'No site.' },
  loading: { fr: 'Chargement…', en: 'Loading…' },
  no_access: { fr: 'Vous n’avez pas les droits pour cet outil.', en: 'You don’t have the rights for this tool.' },
  del_title: { fr: 'Supprimer le site', en: 'Delete site' },
  del_confirm: { fr: 'Le site « {name} » et ses données seront supprimés. Continuer ?', en: 'The site “{name}” and its data will be deleted. Continue?' },
  cancel: { fr: 'Annuler', en: 'Cancel' },
}
const t = (k: string, vars?: Record<string, string>) => {
  let s = DICT[k]?.[LANG === 'en' ? 'en' : 'fr'] ?? k
  if (vars) for (const [kk, vv] of Object.entries(vars)) s = s.replace(`{${kk}}`, vv)
  return s
}
function notify(kind: 'ok' | 'ko', title: string, message: string) {
  window.postMessage({ __melisNotif: true, kind, title, message }, '*')
}

const card: React.CSSProperties = { borderRadius: 12, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', boxShadow: '0 1px 2px rgba(0,0,0,.04)' }
const btnGhost: React.CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 32, padding: '0 10px', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'transparent', cursor: 'pointer', fontSize: 13 }
const btnPrimary: React.CSSProperties = { ...btnGhost, border: 0, background: 'var(--color-primary,#cb4040)', color: '#fff', fontWeight: 600 }
const iconBtn: React.CSSProperties = { display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 28, height: 28, borderRadius: 6, border: 0, background: 'transparent', color: 'var(--color-muted-foreground)', cursor: 'pointer' }
const inputCss: React.CSSProperties = { borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-background,#fff)', padding: '0 10px', fontSize: 14, boxSizing: 'border-box' }
const th: React.CSSProperties = { textAlign: 'left', fontSize: 12, fontWeight: 600, color: 'var(--color-muted-foreground,#6b7280)', padding: '10px 14px' }
const td: React.CSSProperties = { fontSize: 14, padding: '10px 14px', borderTop: '1px solid var(--color-border,#f0f0f0)' }

// Icônes calquées sur lucide-react (la brique n'embarque pas lucide) — mêmes que l'outil Users de référence.
const sIcon = { width: 15, height: 15, flexShrink: 0 } as const
const PencilIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>
const TrashIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /></svg>
const SearchIcon = () => <svg style={{ width: 14, height: 14, flexShrink: 0 }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" /></svg>
const XIcon = () => <svg style={{ width: 14, height: 14, flexShrink: 0 }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
const Columns3Icon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" /><path d="M9 3v18" /><path d="M15 3v18" /></svg>
const FileDownIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" /><path d="M14 2v4a2 2 0 0 0 2 2h4" /><path d="M12 18v-6" /><path d="m9 15 3 3 3-3" /></svg>
const ResetIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 2v6h6" /><path d="M3 13a9 9 0 1 0 3-7.7L3 8" /></svg>
const GripIcon = () => <svg style={{ width: 13, height: 13, flexShrink: 0, color: 'var(--color-muted-foreground)' }} viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" /><circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" /><circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" /></svg>
// Loader2 lucide : spinné par un keyframe CSS AUTOUR DE SON CENTRE (pas de SMIL sur le <svg> racine → dérive).
function Loader2Icon({ size = 14 }: { size?: number }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"
      style={{ animation: 'melis-sites-spin 0.8s linear infinite', transformOrigin: 'center', flexShrink: 0 }}>
      <style>{`@keyframes melis-sites-spin { to { transform: rotate(360deg) } }`}</style>
      <path d="M21 12a9 9 0 1 1-6.219-8.56" />
    </svg>
  )
}
// Refresh : RotateCcw lucide, tourne quand `spinning` (feedback visible du clic Rafraîchir).
const RotateCcwIcon = ({ spinning }: { spinning?: boolean }) => <svg style={{ ...sIcon, ...(spinning ? { animation: 'melis-sites-spin 0.6s linear infinite', transformOrigin: 'center' } : {}) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" /><path d="M3 3v5h5" /></svg>
// Minifier les assets : Minimize2 lucide (mêmes flèches vers le centre que le fa-compress legacy).
const MinifyIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3" /></svg>

// Icône de tri (mêmes tracés que le core) — asc / desc / neutre (opacité réduite).
function SortIcon({ dir }: { dir: 'asc' | 'desc' | null }) {
  const p = { width: 12, height: 12, viewBox: '0 0 24 24', fill: 'none' as const, stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round' as const, strokeLinejoin: 'round' as const, style: { flexShrink: 0, display: 'inline-block', opacity: dir ? 1 : 0.3 } }
  if (dir === 'asc')  return <svg {...p}><path d="m5 12 7-7 7 7" /><path d="M12 19V5" /></svg>
  if (dir === 'desc') return <svg {...p}><path d="M12 5v14" /><path d="m19 12-7 7-7-7" /></svg>
  return <svg {...p}><path d="m21 16-4 4-4-4" /><path d="M17 20V4" /><path d="m3 8 4-4 4 4" /><path d="M7 4v16" /></svg>
}
const SORTABLE = new Set(['id', 'label', 'name'])

/** Drapeau de langue (image MelisCore /assets/images/lang/<short>.png). en_EN → en, fr_FR → fr. */
function LangFlag({ locale, name }: { locale: string; name: string }) {
  const short = (locale || '').slice(0, 2).toLowerCase()
  if (!short) return null
  return (
    <img src={`/MelisCore/assets/images/lang/${short}.png`} alt={name} title={name}
      width={18} height={12}
      style={{ display: 'inline-block', borderRadius: 2, objectFit: 'cover', boxShadow: '0 0 1px rgba(0,0,0,.3)' }}
      onError={(e) => { (e.currentTarget as HTMLImageElement).style.display = 'none' }} />
  )
}

// ── Colonnes (masquer + réordonner par glisser-déposer, persisté) ──
type ColDef = { id: string; visible: boolean }
const COL_ORDER = ['id', 'label', 'name', 'lang'] as const
const COL_LABEL: Record<string, string> = { id: 'col_id', label: 'col_label', name: 'col_name', lang: 'col_lang' }
const DEFAULT_COLS: ColDef[] = COL_ORDER.map((id) => ({ id, visible: true }))
const COL_KEY = 'melis-cmssites-cols-v1'
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

const panelCss: React.CSSProperties = { display: 'flex', flexDirection: 'column', gap: 2, minHeight: 130, maxHeight: 'min(48vh, 320px)', overflowY: 'auto', minWidth: 0, borderRadius: 8, border: '1px dashed var(--color-border)', padding: 6 }
const panelTitle: React.CSSProperties = { padding: '0 6px 4px', fontSize: 10, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.06em', color: 'var(--color-muted-foreground)' }

function ColManager({ cols, labelFor, onChange, onClose }: {
  cols: ColDef[]; labelFor: (id: string) => string; onChange: (c: ColDef[]) => void; onClose: () => void
}) {
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
    <div style={{ ...card, position: 'absolute', right: 0, top: '100%', marginTop: 6, zIndex: 50, width: 340, maxWidth: 'calc(100vw - 1rem)' }}>
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

function getCellExport(item: SiteItem, id: string): string | number {
  if (id === 'id') return item.id
  if (id === 'label') return item.label
  if (id === 'name') return item.name
  if (id === 'lang') return item.languages.map((l) => l.locale).join(', ')
  return ''
}

export default function SitesList({ active, onEdit, onNew }: {
  active: boolean
  onEdit: (id: number, label: string) => void
  onNew: () => void
}) {
  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [tick, setTick] = useState(0)
  const [refreshing, setRefreshing] = useState(false)
  const [toDelete, setToDelete] = useState<SiteItem | null>(null)
  const [minifyingId, setMinifyingId] = useState<number | null>(null)
  const [mode, setMode] = useState<'react' | 'iframe'>('react')
  const [frameLoaded, setFrameLoaded] = useState(false)
  const [cols, setCols] = useState<ColDef[]>(loadCols)
  const [showCols, setShowCols] = useState(false)
  const [showExport, setShowExport] = useState(false)

  // Tri server-side via le hook mutualisé. La liste est courte (nextCursor toujours null → pas de
  // scroll infini), mais le tri passe côté serveur et un changement de colonne relance un fetch.
  const {
    items, loading, sentinelRef, sortCol, sortDir, toggleSort, reload, removeLocal,
  } = useKeysetList<SiteItem>({
    fetcher: (a) => fetchSites({ search, sort: a.sort, dir: a.dir }),
    deps: [search, tick],
    defaultSort: 'id',
    defaultDir: 'asc',
  })

  // Recherche live débouncée (le load dépend de `search`, l'input ne touche que `searchInput`).
  useEffect(() => {
    const id = setTimeout(() => setSearch(searchInput.trim()), 300)
    return () => clearTimeout(id)
  }, [searchInput])

  function clearSearch() { setSearchInput(''); setSearch('') }

  // Recharge quand la liste redevient active après une création/édition/suppression.
  useEffect(() => { if (active && consumeSitesListStale()) setTick((x) => x + 1) }, [active])

  // Rafraîchir : relance un chargement frais + spin le bouton.
  function handleRefresh() {
    setRefreshing(true)
    reload()
    setTimeout(() => setRefreshing(false), 600)
  }

  // Réinitialiser les filtres : recherche (seul filtre de cette liste), puis refetch.
  function resetFilters() {
    setSearchInput(''); setSearch('')
    reload()
  }

  async function confirmDelete() {
    if (!toDelete) return
    const id = toDelete.id
    try { await deleteSite(id) } catch { /* ignore */ }
    setToDelete(null)
    removeLocal((s) => s.id === id)
    reload()
  }

  // Minifier les assets (JS/CSS) du site — même endpoint legacy `/minify-assets` que le bouton
  // fa-compress de la liste classique ; désactivé quand le module du site est introuvable sur
  // disque (`moduleFound`), comme `data-mod-found` côté legacy.
  async function handleMinify(site: SiteItem) {
    if (!site.moduleFound || minifyingId != null) return
    setMinifyingId(site.id)
    try {
      const r = await minifyAssets(site.id)
      notify(r.success ? 'ok' : 'ko', t('title'), r.message || (r.success ? t('minify_success') : t('minify_error')))
    } catch (e) {
      notify('ko', t('title'), e instanceof Error ? e.message : t('minify_error'))
    } finally { setMinifyingId(null) }
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
          <button style={{ ...btnGhost, width: 32, padding: 0, justifyContent: 'center' }} onClick={handleRefresh} title={t('refresh')}><RotateCcwIcon spinning={refreshing} /></button>
          {can('create') && <button style={btnPrimary} onClick={onNew}>+ {t('new')}</button>}
        </div>
      </div>

      {/* Vue « Old » : outil Sites legacy en iframe */}
      {frameLoaded && (
        <div style={{ ...card, display: mode === 'iframe' ? 'flex' : 'none', flex: 1, minHeight: 480, overflow: 'hidden' }}>
          <iframe src={`/melis/react-tool-page?key=${encodeURIComponent(MELIS_KEY)}`}
            style={{ flex: 1, width: '100%', border: 0 }} title="Sites — Vue Melis"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-modals" />
        </div>
      )}

      {/* Vue « New » : liste React native */}
      <div style={{ display: mode === 'react' ? 'flex' : 'none', flexDirection: 'column', gap: 16 }}>
        {!can('list') ? (
          <div style={{ ...card, padding: '40px 16px', textAlign: 'center', fontSize: 14, color: 'var(--color-muted-foreground)' }}>{t('no_access')}</div>
        ) : (<>
          <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
            <div style={{ position: 'relative', flex: 1, minWidth: 220, maxWidth: 384 }}>
              <span style={{ position: 'absolute', left: 10, top: '50%', transform: 'translateY(-50%)', color: 'var(--color-muted-foreground)', display: 'flex', pointerEvents: 'none' }}>
                <SearchIcon />
              </span>
              <input style={{ ...inputCss, height: 36, width: '100%', paddingLeft: 30, paddingRight: searchInput ? 30 : 10 }} value={searchInput}
                onChange={(e) => setSearchInput(e.target.value)}
                placeholder={t('search')} />
              {searchInput && (
                <button type="button" onClick={clearSearch}
                  style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', border: 0, background: 'transparent', color: 'var(--color-muted-foreground)', cursor: 'pointer', display: 'flex' }}>
                  <XIcon />
                </button>
              )}
            </div>
            <div style={{ flex: 1 }} />
            <button style={{ ...btnGhost, height: 36 }} onClick={resetFilters}><ResetIcon />{t('reset_filters')}</button>
            <div style={{ position: 'relative' }}>
              <button style={{ ...btnGhost, height: 36 }} onClick={() => setShowCols((v) => !v)}><Columns3Icon />{t('columns')}</button>
              {showCols && <ColManager cols={cols} labelFor={(id) => t(COL_LABEL[id])} onChange={setCols} onClose={() => setShowCols(false)} />}
            </div>
            {can('export') && <button style={{ ...btnGhost, height: 36 }} onClick={() => setShowExport(true)}><FileDownIcon />{t('export')}</button>}
          </div>

          <div style={{ ...card, overflow: 'hidden' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: 640 }}>
              <thead style={{ background: 'var(--color-muted,rgba(0,0,0,.03))' }}>
                <tr>
                  {visibleCols(cols).map(({ id }) => (
                    SORTABLE.has(id) ? (
                      <th key={id} style={{ ...th, ...(id === 'id' ? { width: 60 } : {}), cursor: 'pointer', ...(sortCol === id ? { color: 'var(--color-primary)' } : {}) }} onClick={() => toggleSort(id)}>
                        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4 }}>{t(COL_LABEL[id])}<SortIcon dir={sortCol === id ? sortDir : null} /></span>
                      </th>
                    ) : (
                      <th key={id} style={{ ...th }}>{t(COL_LABEL[id])}</th>
                    )
                  ))}
                  <th style={{ ...th, width: 120 }} />
                </tr>
              </thead>
              <tbody>
                {loading && items.length === 0 ? (
                  <tr><td style={{ ...td, padding: '40px 16px', color: 'var(--color-muted-foreground)' }} colSpan={visibleCols(cols).length + 1}>
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8 }}><Loader2Icon />{t('loading')}</div>
                  </td></tr>
                ) : items.length === 0 ? (
                  <tr><td style={{ ...td, textAlign: 'center', color: 'var(--color-muted-foreground)', padding: '40px 16px' }} colSpan={visibleCols(cols).length + 1}>{t('empty')}</td></tr>
                ) : items.map((s) => (
                  <tr key={s.id}>
                    {visibleCols(cols).map(({ id }) => (
                      <td key={id} style={{ ...td, ...(id === 'id' ? { color: 'var(--color-muted-foreground)' } : {}), ...(id === 'label' ? { fontWeight: 600 } : {}), ...(id === 'name' ? { color: 'var(--color-muted-foreground)' } : {}) }}>
                        {id === 'id' && s.id}
                        {id === 'label' && s.label}
                        {id === 'name' && s.name}
                        {id === 'lang' && (
                          s.languages && s.languages.length > 0 ? (
                            <span style={{ display: 'inline-flex', gap: 5, flexWrap: 'wrap', alignItems: 'center' }}>
                              {s.languages.map((l) => <LangFlag key={l.id} locale={l.locale} name={l.name} />)}
                            </span>
                          ) : <span style={{ color: 'var(--color-muted-foreground)' }}>—</span>
                        )}
                      </td>
                    ))}
                    <td style={{ ...td, whiteSpace: 'nowrap', textAlign: 'right' }}>
                      <button style={{ ...iconBtn, opacity: s.moduleFound ? 1 : 0.4, cursor: s.moduleFound ? 'pointer' : 'not-allowed' }}
                        title={s.moduleFound ? t('minify') : t('minify_disabled')}
                        disabled={!s.moduleFound || minifyingId === s.id}
                        onClick={() => handleMinify(s)}>
                        {minifyingId === s.id ? <Loader2Icon /> : <MinifyIcon />}
                      </button>
                      <button style={iconBtn} title={t('edit')} onClick={() => onEdit(s.id, s.label || s.name)}><PencilIcon /></button>
                      {can('delete') && <button style={{ ...iconBtn, color: 'var(--color-destructive,#ef4444)' }} title={t('del')} onClick={() => setToDelete(s)}><TrashIcon /></button>}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            {/* Sentinel scroll infini (inactif ici : liste courte, nextCursor toujours null). */}
            <div ref={sentinelRef} style={{ height: 1 }} />
          </div>
        </>)}
      </div>

      {/* Confirmation suppression */}
      {toDelete && (
        <div style={{ position: 'fixed', inset: 0, zIndex: 50, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.5)' }}>
          <div style={{ ...card, width: '100%', maxWidth: 440, padding: 24 }}>
            <h3 style={{ margin: 0, fontSize: 16, fontWeight: 600 }}>{t('del_title')}</h3>
            <p style={{ marginTop: 8, fontSize: 14, color: 'var(--color-muted-foreground)' }}>{t('del_confirm', { name: toDelete.label || toDelete.name })}</p>
            <div style={{ marginTop: 20, display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
              <button style={btnGhost} onClick={() => setToDelete(null)}>{t('cancel')}</button>
              <button style={{ ...btnPrimary, background: '#b91c1c' }} onClick={confirmDelete}>{t('del')}</button>
            </div>
          </div>
        </div>
      )}

      {showExport && (
        <ExportModal<SiteItem>
          cols={cols}
          labelFor={(id) => t(COL_LABEL[id])}
          fetchAll={async () => (await fetchSites({ search, sort: sortCol, dir: sortDir })).items}
          getCell={(item, id) => getCellExport(item, id)}
          filename="sites"
          sheetName={t('title')}
          total={items.length}
          onClose={() => setShowExport(false)}
        />
      )}
    </div>
  )
}
