import { useEffect, useMemo, useState, type CSSProperties } from 'react'
import { useLocation, useNavigate, useParams } from 'react-router-dom'
import {
  deleteRedirect, fetchRedirectById, fetchRedirects, fetchRedirectStats, fetchSites,
  saveRedirect, type RedirectItem, type RedirectStats, type SiteOption,
} from './redirect-api'
import { ExportModal, DownloadIcon } from './ExportModal'
import { ViewToggle } from './ViewToggle'

// Outil Redirections 301 legacy (vue « Old » en iframe). Voir brick.manifest.json (cms-site-301).
const MELIS_KEY = 'meliscms_tool_site_301'

// Capacités (droits avancés) : la brique ne peut PAS importer le hook hôte → lit le global window.MelisCan.
// Default-allow (true) tant que non chargé / pour un admin ; l'API reste gardée côté serveur (403).
function can(cap: string): boolean {
  return (window as unknown as { MelisCan?: (k: string, c: string) => boolean }).MelisCan?.(MELIS_KEY, cap) ?? true
}

/* ──────────────────────────────────────────────────────────────────────────
 * Brique « Redirections 301 » (MelisCms) — full React, montée à /melis-cms/site-301
 * (et /melis-cms/site-301/:id pour le formulaire). La brique ne peut PAS importer les
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
    title: 'Redirections 301', subtitle: 'Redirections d’URL par site',
    new: 'Nouvelle redirection', search: 'Rechercher une redirection…',
    empty: 'Aucune redirection trouvée', count: '{n} redirections — fin de la liste',
    kpi_total: 'Total', kpi_sites: 'Sites concernés',
    all_sites: 'Tous les sites', col_id: 'ID', col_site: 'Site', col_old: 'Ancienne URL', col_new: 'Nouvelle URL',
    columns: 'Colonnes', export: 'Exporter', cols_visible: 'Visibles', cols_hidden: 'Masquées', drag_here: 'Glisser ici', reset: 'Réinitialiser',
    edit: 'Modifier', del: 'Supprimer', cancel: 'Annuler', save: 'Enregistrer', back: 'retour',
    refresh: 'Rafraîchir', loading: 'Chargement…', saved: 'Enregistré ✓',
    del_title: 'Supprimer la redirection', del_confirm: 'Supprimer « {u} » ? Cette action est irréversible.',
    new_title: 'Nouvelle redirection', edit_title: 'Modifier la redirection',
    f_site: 'Site', f_site_ph: '— Choisir un site —', f_old: 'Ancienne URL', f_old_ph: '/ancienne-url',
    f_new: 'Nouvelle URL', f_new_ph: '/nouvelle-url', f_old_hint: 'L’URL à rediriger (unique pour ce site).',
    f_new_hint: 'La destination de la redirection.', err_save: 'Erreur lors de la sauvegarde',
    no_access: 'Vous n’avez pas les droits pour consulter cette liste.',
  },
  en: {
    title: '301 Redirects', subtitle: 'Per-site URL redirects',
    new: 'New redirect', search: 'Search a redirect…',
    empty: 'No redirect found', count: '{n} redirects — end of list',
    kpi_total: 'Total', kpi_sites: 'Sites covered',
    all_sites: 'All sites', col_id: 'ID', col_site: 'Site', col_old: 'Old URL', col_new: 'New URL',
    columns: 'Columns', export: 'Export', cols_visible: 'Visible', cols_hidden: 'Hidden', drag_here: 'Drag here', reset: 'Reset',
    edit: 'Edit', del: 'Delete', cancel: 'Cancel', save: 'Save', back: 'back',
    refresh: 'Refresh', loading: 'Loading…', saved: 'Saved ✓',
    del_title: 'Delete redirect', del_confirm: 'Delete “{u}”? This action is irreversible.',
    new_title: 'New redirect', edit_title: 'Edit redirect',
    f_site: 'Site', f_site_ph: '— Choose a site —', f_old: 'Old URL', f_old_ph: '/old-url',
    f_new: 'New URL', f_new_ph: '/new-url', f_old_hint: 'The URL to redirect (unique for this site).',
    f_new_hint: 'The redirect destination.', err_save: 'Error while saving',
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

// API sous-onglets de l'hôte (la brique ne peut pas importer son contexte React) — cf. manifest subTabs:true.
// Sans ça, éditer une redirection ouvrait un onglet top-level « <id> » au lieu d'un sous-onglet nommé (look Users).
type SubTabW = {
  __melisOpenSubTab?: (section: string, tab: { id: string; label: string; path: string }) => void
  __melisUpdateSubTabLabel?: (section: string, id: string, label: string) => void
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
const ArrowRight = () => <svg style={{ width: 13, height: 13, color: 'var(--color-muted-foreground)' }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14M13 5l7 7-7 7" /></svg>

// ── Colonnes (masquer + réordonner par glisser-déposer, persisté) ──
type ColDef = { id: string; visible: boolean }
const COL_ORDER = ['id', 'site', 'old', 'new'] as const
const COL_LABEL: Record<string, string> = { id: 'col_id', site: 'col_site', old: 'col_old', new: 'col_new' }
const DEFAULT_COLS: ColDef[] = COL_ORDER.map((id) => ({ id, visible: id !== 'id' }))
const COL_KEY = 'melis-site301-cols-v1'
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

// ════════════════════════════════════════════════════════════════════════════
export default function SiteRedirectPage({ active = true }: { active?: boolean }) {
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

  if (effId) return <RedirectForm id={effId} base={base} />
  return <RedirectList base={base} />
}

// ── Liste ───────────────────────────────────────────────────────────────────
function RedirectList({ base }: { base: string }) {
  const t = useT()
  const navigate = useNavigate()
  const [items, setItems] = useState<RedirectItem[]>([])
  const [stats, setStats] = useState<RedirectStats | null>(null)
  const [sites, setSites] = useState<SiteOption[]>([])
  const [loading, setLoading] = useState(false)
  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [site, setSite] = useState<number | null>(null)
  const [sortAsc, setSortAsc] = useState(false)
  const [toDelete, setToDelete] = useState<RedirectItem | null>(null)
  const [tick, setTick] = useState(0)
  const [cols, setCols] = useState<ColDef[]>(loadCols)
  const [showCols, setShowCols] = useState(false)
  const [showExport, setShowExport] = useState(false)
  const [mode, setMode] = useState<'react' | 'iframe'>('react')
  const [frameLoaded, setFrameLoaded] = useState(false)

  useEffect(() => { fetchRedirectStats().then(setStats).catch(() => null) }, [tick])
  useEffect(() => { fetchSites().then(setSites).catch(() => null) }, [])
  useEffect(() => {
    setLoading(true)
    fetchRedirects({ search, site })
      .then((r) => setItems(r.items)).catch(() => null).finally(() => setLoading(false))
  }, [search, site, tick])

  const sorted = useMemo(() => [...items].sort((a, b) => sortAsc ? a.id - b.id : b.id - a.id), [items, sortAsc])

  async function confirmDelete() {
    if (!toDelete) return
    try { await deleteRedirect(toDelete.id); setToDelete(null); setTick((x) => x + 1) }
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
          {can('create') && <button style={btnPrimary} onClick={() => navigate(`${base}/new`)}><PlusIcon />{t('new')}</button>}
        </div>
      </div>

      {/* Vue « Old » : outil Redirections 301 legacy en iframe (montée à la 1ʳᵉ activation, gardée en display:none) */}
      {frameLoaded && (
        <div style={{ ...card, display: mode === 'iframe' ? 'flex' : 'none', flex: 1, minHeight: 480, overflow: 'hidden' }}>
          <iframe src={`/melis/react-tool-page?key=${encodeURIComponent(MELIS_KEY)}`}
            style={{ flex: 1, width: '100%', border: 0 }} title="Redirections 301 — Vue Melis"
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
        <Kpi label={t('kpi_sites')} value={stats?.sites ?? null} />
      </div>

      {/* Filtres */}
      <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
        <input style={{ ...inputCss, height: 36, flex: 1, minWidth: 220 }} value={searchInput}
          onChange={(e) => setSearchInput(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && setSearch(searchInput.trim())}
          placeholder={t('search')} />
        <select style={{ ...inputCss, height: 36, width: 'auto' }} value={site ?? ''} onChange={(e) => setSite(e.target.value ? Number(e.target.value) : null)}>
          <option value="">{t('all_sites')}</option>
          {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
        </select>
        <div style={{ position: 'relative' }}>
          <button style={{ ...btnGhost, height: 36 }} onClick={() => setShowCols((v) => !v)}><GripIcon />{t('columns')}</button>
          {showCols && <ColManager cols={cols} labelFor={(id) => t(COL_LABEL[id])} onChange={setCols} onClose={() => setShowCols(false)} />}
        </div>
        {can('export') && <button style={{ ...btnGhost, height: 36 }} onClick={() => setShowExport(true)}><DownloadIcon />{t('export')}</button>}
      </div>

      {/* Table */}
      <div style={{ ...card, overflow: 'hidden' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: 640 }}>
          <thead style={{ background: 'var(--color-muted,rgba(0,0,0,.03))' }}>
            <tr>
              {visibleCols(cols).map(({ id }) => (
                <th key={id} style={{ ...th, ...(id === 'id' ? { cursor: 'pointer', width: 70 } : {}) }}
                  onClick={id === 'id' ? () => setSortAsc((v) => !v) : undefined}>
                  {t(COL_LABEL[id])}{id === 'id' ? ` ${sortAsc ? '↑' : '↓'}` : ''}
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
                  <td key={id} style={{ ...td, ...(id === 'id' ? { color: 'var(--color-muted-foreground)', fontVariantNumeric: 'tabular-nums' } : {}), ...(id === 'old' ? { fontFamily: 'monospace', fontSize: 13 } : {}) }}>
                    {id === 'id' && r.id}
                    {id === 'site' && r.siteName}
                    {id === 'old' && r.oldUrl}
                    {id === 'new' && (
                      <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}>
                        <ArrowRight /><span style={{ fontFamily: 'monospace', fontSize: 13 }}>{r.newUrl}</span>
                      </span>
                    )}
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
            <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', marginTop: 8 }}>{t('del_confirm', { u: toDelete.oldUrl })}</p>
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 20 }}>
              <button style={btnGhost} onClick={() => setToDelete(null)}>{t('cancel')}</button>
              <button style={{ ...btnGhost, borderColor: '#fca5a5', color: '#dc2626' }} onClick={confirmDelete}>{t('del')}</button>
            </div>
          </div>
        </div>
      )}

      {showExport && (
        <ExportModal<RedirectItem>
          cols={cols}
          labelFor={(id) => t(COL_LABEL[id])}
          fetchAll={async () => (await fetchRedirects({ search, site })).items}
          getCell={(r, id) => id === 'id' ? r.id : id === 'site' ? r.siteName : id === 'old' ? r.oldUrl : id === 'new' ? r.newUrl : ''}
          filename={currentLang() === 'fr' ? 'redirections-301' : 'redirects-301'}
          sheetName={t('title')}
          total={items.length}
          onClose={() => setShowExport(false)}
        />
      )}
    </div>
  )
}

// ── Formulaire ────────────────────────────────────────────────────────────────
function RedirectForm({ id, base }: { id: string; base: string }) {
  const t = useT()
  const navigate = useNavigate()
  const isEdit = id !== 'new'
  const redirectId = isEdit ? parseInt(id) : null

  const [sites, setSites] = useState<SiteOption[]>([])
  const [siteId, setSiteId] = useState<number | ''>('')
  const [oldUrl, setOldUrl] = useState('')
  const [newUrl, setNewUrl] = useState('')
  const [loading, setLoading] = useState(false)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [saved, setSaved] = useState(false)

  // Sous-onglet nommé (look Users) : ouvert au montage, renommé avec l'ancienne URL au chargement.
  const subTabId = `${base}/${id}`
  useEffect(() => {
    ;(window as unknown as SubTabW).__melisOpenSubTab?.(base, { id: subTabId, label: isEdit ? t('loading') : t('new_title'), path: subTabId })
  }, []) // eslint-disable-line react-hooks/exhaustive-deps
  useEffect(() => {
    if (isEdit && !loading && oldUrl) (window as unknown as SubTabW).__melisUpdateSubTabLabel?.(base, subTabId, oldUrl)
  }, [loading, oldUrl, isEdit]) // eslint-disable-line react-hooks/exhaustive-deps

  useEffect(() => { if (!can(isEdit ? 'edit' : 'create')) navigate(base) }, [isEdit, base, navigate])
  useEffect(() => { fetchSites().then(setSites).catch(() => null) }, [])
  useEffect(() => {
    if (!redirectId) return
    setLoading(true)
    fetchRedirectById(redirectId)
      .then((r) => { setSiteId(r.siteId); setOldUrl(r.oldUrl); setNewUrl(r.newUrl) })
      .catch(() => navigate(base))
      .finally(() => setLoading(false))
  }, [redirectId])

  async function submit() {
    setError(null)
    if (!siteId) { setError(DICT[currentLang()].f_site + ' *'); return }
    if (!oldUrl.trim() || !newUrl.trim()) { setError(t('err_save')); return }
    setSaving(true)
    try {
      await saveRedirect({ id: redirectId, siteId: Number(siteId), oldUrl: oldUrl.trim(), newUrl: newUrl.trim() })
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
          <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0 }}>{isEdit ? t('edit_title') : t('new_title')}</h1>
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
        <div style={{ ...card, padding: 20, maxWidth: 640 }}>
          <div style={{ marginBottom: 16 }}>
            <label style={label}>{t('f_site')}</label>
            <select style={inputCss} value={siteId} onChange={(e) => setSiteId(e.target.value ? Number(e.target.value) : '')} disabled={isEdit}>
              <option value="">{t('f_site_ph')}</option>
              {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
            </select>
          </div>
          <div style={{ marginBottom: 16 }}>
            <label style={label}>{t('f_old')}</label>
            <input style={{ ...inputCss, fontFamily: 'monospace' }} value={oldUrl} onChange={(e) => setOldUrl(e.target.value)} placeholder={t('f_old_ph')} maxLength={255} autoComplete="off" />
            <p style={hint}>{t('f_old_hint')}</p>
          </div>
          <div>
            <label style={label}>{t('f_new')}</label>
            <input style={{ ...inputCss, fontFamily: 'monospace' }} value={newUrl} onChange={(e) => setNewUrl(e.target.value)} placeholder={t('f_new_ph')} maxLength={255} autoComplete="off" />
            <p style={hint}>{t('f_new_hint')}</p>
          </div>
        </div>
      )}
    </div>
  )
}
