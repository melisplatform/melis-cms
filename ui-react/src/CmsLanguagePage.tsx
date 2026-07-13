import { useEffect, useMemo, useState, type CSSProperties } from 'react'
import { useLocation, useNavigate, useParams } from 'react-router-dom'
import {
  deleteLanguage, fetchLanguageById, fetchLanguages, fetchLanguageStats,
  saveLanguage, type LangItem, type LangStats,
} from './cms-language-api'
import { ViewToggle } from './ViewToggle'

// Outil Langues (CMS) legacy (vue « Old » en iframe). Voir brick.manifest.json (cms-languages).
const MELIS_KEY = 'meliscms_tool_language'

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
 * Brique « Langues » (MelisCms) — full React, montée à /melis-cms/languages
 * (et /melis-cms/languages/:id pour le formulaire). La brique ne peut PAS importer les
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
    title: 'Langues', subtitle: 'Langues du CMS',
    new: 'Nouvelle langue', search: 'Rechercher une langue…',
    empty: 'Aucune langue trouvée', count: '{n} langues — fin de la liste',
    kpi_total: 'Total',
    col_id: 'ID', col_locale: 'Locale', col_name: 'Nom',
    columns: 'Colonnes', export: 'Exporter', cols_visible: 'Visibles', cols_hidden: 'Masquées', drag_here: 'Glisser ici', reset: 'Réinitialiser', reset_filters: 'Réinitialiser les filtres',
    edit: 'Modifier', del: 'Supprimer', cancel: 'Annuler', save: 'Enregistrer', back: 'retour',
    refresh: 'Rafraîchir', loading: 'Chargement…', saved: 'Enregistré ✓',
    del_title: 'Supprimer la langue', del_confirm: 'Supprimer « {u} » ? Cette action est irréversible.',
    new_title: 'Nouvelle langue', edit_title: 'Modifier la langue',
    f_locale: 'Locale', f_locale_ph: 'en_EN', f_name: 'Nom', f_name_ph: 'English',
    f_locale_hint: 'Code de la langue au format xx_XX (ex. fr_FR).',
    f_name_hint: 'Le libellé de la langue.', err_save: 'Erreur lors de la sauvegarde',
    err_name: 'Le nom est requis.', err_locale: 'La locale doit être au format xx_XX (ex. fr_FR).',
    no_access: 'Vous n’avez pas les droits pour consulter cette liste.',
  },
  en: {
    title: 'Languages', subtitle: 'CMS languages',
    new: 'New language', search: 'Search a language…',
    empty: 'No language found', count: '{n} languages — end of list',
    kpi_total: 'Total',
    col_id: 'ID', col_locale: 'Locale', col_name: 'Name',
    columns: 'Columns', export: 'Export', cols_visible: 'Visible', cols_hidden: 'Hidden', drag_here: 'Drag here', reset: 'Reset', reset_filters: 'Reset filters',
    edit: 'Edit', del: 'Delete', cancel: 'Cancel', save: 'Save', back: 'back',
    refresh: 'Refresh', loading: 'Loading…', saved: 'Saved ✓',
    del_title: 'Delete language', del_confirm: 'Delete “{u}”? This action is irreversible.',
    new_title: 'New language', edit_title: 'Edit language',
    f_locale: 'Locale', f_locale_ph: 'en_EN', f_name: 'Name', f_name_ph: 'English',
    f_locale_hint: 'Language code in xx_XX format (e.g. en_EN).',
    f_name_hint: 'The language label.', err_save: 'Error while saving',
    err_name: 'Name is required.', err_locale: 'Locale must be in xx_XX format (e.g. en_EN).',
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

// ── Colonnes (masquer + réordonner par glisser-déposer, persisté) ──
type ColDef = { id: string; visible: boolean }
const COL_ORDER = ['id', 'locale', 'name'] as const
const COL_LABEL: Record<string, string> = { id: 'col_id', locale: 'col_locale', name: 'col_name' }
const DEFAULT_COLS: ColDef[] = COL_ORDER.map((id) => ({ id, visible: id !== 'id' }))
const COL_KEY = 'melis-cmslanguage-cols-v1'
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
export default function CmsLanguagePage({ active = true }: { active?: boolean }) {
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

  if (effId) return <CmsLanguageForm id={effId} base={base} />
  return <CmsLanguageList base={base} />
}

// ── Liste ───────────────────────────────────────────────────────────────────
function CmsLanguageList({ base }: { base: string }) {
  const t = useT()
  const navigate = useNavigate()
  const [items, setItems] = useState<LangItem[]>([])
  const [stats, setStats] = useState<LangStats | null>(null)
  const [loading, setLoading] = useState(false)
  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [sortCol, setSortCol] = useState<string | null>(null)
  const [sortAsc, setSortAsc] = useState(true)
  const [toDelete, setToDelete] = useState<LangItem | null>(null)
  const [tick, setTick] = useState(0)
  const [cols, setCols] = useState<ColDef[]>(loadCols)
  const [showCols, setShowCols] = useState(false)
  const [mode, setMode] = useState<'react' | 'iframe'>('react')
  const [frameLoaded, setFrameLoaded] = useState(false)

  useEffect(() => { fetchLanguageStats().then(setStats).catch(() => null) }, [tick])
  useEffect(() => {
    setLoading(true)
    fetchLanguages({ search })
      .then((r) => setItems(r.items)).catch(() => null).finally(() => setLoading(false))
  }, [search, tick])

  const cell = (r: LangItem, c: string): string | number => (
    c === 'id' ? r.id : c === 'locale' ? r.locale : c === 'name' ? r.name : ''
  )
  const sorted = useMemo(() => {
    if (!sortCol) return items
    return [...items].sort((a, b) => {
      const va = cell(a, sortCol), vb = cell(b, sortCol)
      const na = typeof va === 'number' ? va : parseFloat(String(va)); const nb = typeof vb === 'number' ? vb : parseFloat(String(vb))
      const cmp = !isNaN(na) && !isNaN(nb) ? na - nb : String(va).localeCompare(String(vb), undefined, { sensitivity: 'base' })
      return sortAsc ? cmp : -cmp
    })
  }, [items, sortCol, sortAsc])

  function toggleSort(id: string) { if (sortCol === id) setSortAsc((v) => !v); else { setSortCol(id); setSortAsc(true) } }

  // Réinitialiser les filtres : recherche (seul filtre) + tri par défaut (aucun → ordre de l'API), puis refetch.
  // On vide `items` : sinon les lignes restent affichées pendant le rechargement et le clic paraît sans effet.
  function resetFilters() {
    setSearchInput(''); setSearch('')
    setSortCol(null); setSortAsc(true)
    setItems([])
    setTick((x) => x + 1)
  }

  async function confirmDelete() {
    if (!toDelete) return
    try { await deleteLanguage(toDelete.id); setToDelete(null); setTick((x) => x + 1) }
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

      {/* Vue « Old » : outil Langues legacy en iframe (montée à la 1ʳᵉ activation, gardée en display:none) */}
      {frameLoaded && (
        <div style={{ ...card, display: mode === 'iframe' ? 'flex' : 'none', flex: 1, minHeight: 480, overflow: 'hidden' }}>
          <iframe src={`/melis/react-tool-page?key=${encodeURIComponent(MELIS_KEY)}`}
            style={{ flex: 1, width: '100%', border: 0 }} title="Langues — Vue Melis"
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
        <button style={{ ...btnGhost, height: 36 }} onClick={resetFilters}><ResetIcon />{t('reset_filters')}</button>
        <div style={{ position: 'relative' }}>
          <button style={{ ...btnGhost, height: 36 }} onClick={() => setShowCols((v) => !v)}><GripIcon />{t('columns')}</button>
          {showCols && <ColManager cols={cols} labelFor={(id) => t(COL_LABEL[id])} onChange={setCols} onClose={() => setShowCols(false)} />}
        </div>
      </div>

      {/* Table */}
      <div style={{ ...card, overflow: 'hidden' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: 480 }}>
          <thead style={{ background: 'var(--color-muted,rgba(0,0,0,.03))' }}>
            <tr>
              {visibleCols(cols).map(({ id }) => (
                <th key={id} style={{ ...th, cursor: 'pointer', ...(id === 'id' ? { width: 70 } : {}) }} onClick={() => toggleSort(id)}>
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
                  <td key={id} style={{ ...td, ...(id === 'id' ? { color: 'var(--color-muted-foreground)', fontVariantNumeric: 'tabular-nums' } : {}), ...(id === 'locale' ? { fontFamily: 'monospace', fontSize: 13 } : {}), ...(id === 'name' ? { fontWeight: 500 } : {}) }}>
                    {id === 'id' && r.id}
                    {id === 'locale' && r.locale}
                    {id === 'name' && r.name}
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
            <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', marginTop: 8 }}>{t('del_confirm', { u: toDelete.name })}</p>
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 20 }}>
              <button style={btnGhost} onClick={() => setToDelete(null)}>{t('cancel')}</button>
              <button style={{ ...btnGhost, borderColor: '#fca5a5', color: '#dc2626' }} onClick={confirmDelete}>{t('del')}</button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

// ── Formulaire ────────────────────────────────────────────────────────────────
function CmsLanguageForm({ id, base }: { id: string; base: string }) {
  const t = useT()
  const navigate = useNavigate()
  const isEdit = id !== 'new'
  const langId = isEdit ? parseInt(id) : null

  const [locale, setLocale] = useState('')
  const [name, setName] = useState('')
  const [loading, setLoading] = useState(false)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [saved, setSaved] = useState(false)

  // Sous-onglet nommé (look Users) : ouvert au montage, renommé avec le nom de la langue au chargement.
  const subTabId = `${base}/${id}`
  useEffect(() => {
    ;(window as unknown as SubTabW).__melisOpenSubTab?.(base, { id: subTabId, label: isEdit ? t('loading') : t('new_title'), path: subTabId })
  }, []) // eslint-disable-line react-hooks/exhaustive-deps
  useEffect(() => {
    if (isEdit && name) (window as unknown as SubTabW).__melisUpdateSubTabLabel?.(base, subTabId, name)
  }, [name]) // eslint-disable-line react-hooks/exhaustive-deps

  useEffect(() => { if (!can(isEdit ? 'edit' : 'create')) navigate(base) }, [isEdit, base, navigate])
  useEffect(() => {
    if (!langId) return
    setLoading(true)
    fetchLanguageById(langId)
      .then((r) => { setLocale(r.locale); setName(r.name) })
      .catch(() => navigate(base))
      .finally(() => setLoading(false))
  }, [langId])

  async function submit() {
    setError(null)
    if (!name.trim()) { setError(t('err_name')); return }
    if (!/^[a-z]{2}_[A-Z]{2}$/.test(locale.trim())) { setError(t('err_locale')); return }
    setSaving(true)
    try {
      await saveLanguage({ id: langId, locale: locale.trim(), name: name.trim() })
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
            <label style={label}>{t('f_locale')}</label>
            <input style={{ ...inputCss, fontFamily: 'monospace' }} value={locale} onChange={(e) => setLocale(e.target.value)} placeholder={t('f_locale_ph')} maxLength={5} autoComplete="off" />
            <p style={hint}>{t('f_locale_hint')}</p>
          </div>
          <div>
            <label style={label}>{t('f_name')}</label>
            <input style={inputCss} value={name} onChange={(e) => setName(e.target.value)} placeholder={t('f_name_ph')} maxLength={255} autoComplete="off" />
            <p style={hint}>{t('f_name_hint')}</p>
          </div>
        </div>
      )}
    </div>
  )
}
