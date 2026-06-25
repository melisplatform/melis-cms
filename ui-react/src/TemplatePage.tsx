import { useEffect, useMemo, useRef, useState, type CSSProperties } from 'react'
import { useLocation, useNavigate, useParams } from 'react-router-dom'
import {
  deleteTemplate, fetchTemplates, fetchTemplateSites, fetchTemplateStats,
  type TemplateItem, type TemplateStats, type SiteOption,
} from './template-api'

/* ──────────────────────────────────────────────────────────────────────────
 * Brique « Templates » (MelisCms). La LISTE est full React (montée à /melis-cms/templates) ;
 * la CRÉATION/ÉDITION (/melis-cms/templates/new|:id) rend l'outil LEGACY en iframe — son
 * formulaire est couplé au système de fichiers (scan contrôleurs/actions/layouts) et à un
 * compteur de plateforme pour `tpl_id` : on ne le réimplémente pas. Styles inline + variables
 * CSS du thème, i18n FR/EN via <html lang> (la brique ne partage pas les modules de l'hôte).
 * ────────────────────────────────────────────────────────────────────────── */

const MELIS_KEY = 'meliscms_tool_templates'
const FRAME_ID = 'melis-brick-frame-cms-templates'

// ── i18n minimal ──
type Lang = 'fr' | 'en'
function currentLang(): Lang { return (document.documentElement.lang || 'en').toLowerCase().startsWith('fr') ? 'fr' : 'en' }
const DICT: Record<Lang, Record<string, string>> = {
  fr: {
    title: 'Templates', subtitle: 'Templates des sites', new: 'Nouveau template', search: 'Rechercher un template…',
    empty: 'Aucun template trouvé', count: '{n} templates — fin de la liste',
    kpi_total: 'Total', kpi_sites: 'Sites', kpi_types: 'Types',
    all_sites: 'Tous les sites', all_types: 'Tous les types',
    col_id: 'ID', col_name: 'Nom', col_type: 'Type', col_ctrl: 'Contrôleur / Action', col_layout: 'Layout', col_site: 'Site', col_date: 'Création',
    columns: 'Colonnes', cols_visible: 'Visibles', cols_hidden: 'Masquées', drag_here: 'Glisser ici', reset: 'Réinitialiser',
    edit: 'Modifier', del: 'Supprimer', cancel: 'Annuler', back: 'retour', refresh: 'Rafraîchir', loading: 'Chargement…',
    del_title: 'Supprimer le template', del_confirm: 'Supprimer « {n} » ? Cette action est irréversible.',
    legacy_note: 'Création / édition via l’outil classique (formulaire lié au code du site).',
  },
  en: {
    title: 'Templates', subtitle: 'Site templates', new: 'New template', search: 'Search a template…',
    empty: 'No template found', count: '{n} templates — end of list',
    kpi_total: 'Total', kpi_sites: 'Sites', kpi_types: 'Types',
    all_sites: 'All sites', all_types: 'All types',
    col_id: 'ID', col_name: 'Name', col_type: 'Type', col_ctrl: 'Controller / Action', col_layout: 'Layout', col_site: 'Site', col_date: 'Created',
    columns: 'Columns', cols_visible: 'Visible', cols_hidden: 'Hidden', drag_here: 'Drag here', reset: 'Reset',
    edit: 'Edit', del: 'Delete', cancel: 'Cancel', back: 'back', refresh: 'Refresh', loading: 'Loading…',
    del_title: 'Delete template', del_confirm: 'Delete “{n}”? This action is irreversible.',
    legacy_note: 'Create / edit via the classic tool (form bound to the site code).',
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
const td: CSSProperties = { padding: '10px 16px', fontSize: 14, color: 'var(--color-foreground)', borderTop: '1px solid var(--color-border)' }
const panelCss: CSSProperties = { display: 'flex', flexDirection: 'column', gap: 2, minHeight: 130, borderRadius: 8, border: '1px dashed var(--color-border)', padding: 6 }
const panelTitle: CSSProperties = { padding: '0 6px 4px', fontSize: 10, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.06em', color: 'var(--color-muted-foreground)' }

const sIcon = { width: 15, height: 15, flexShrink: 0 } as const
const PencilIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>
const TrashIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /></svg>
const PlusIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14M5 12h14" /></svg>
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
        onDragStart={() => setDragId(col.id)} onDragEnd={() => { setDragId(null); setOver(null) }}
        onDragOver={(e) => { e.preventDefault(); e.stopPropagation(); if (over?.id !== col.id || over?.panel !== panel) setOver({ id: col.id, panel }) }}
        onDrop={(e) => { e.preventDefault(); drop(panel) }}
        style={{ display: 'flex', alignItems: 'center', gap: 8, borderRadius: 8, padding: '6px 8px', fontSize: 14, cursor: 'grab', userSelect: 'none', opacity: dragId === col.id ? 0.4 : 1, background: isOver ? 'color-mix(in srgb, var(--color-primary) 12%, transparent)' : 'transparent', boxShadow: isOver ? '0 0 0 1px color-mix(in srgb, var(--color-primary) 35%, transparent)' : 'none' }}>
        <GripIcon /><span style={{ flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{labelFor(col.id)}</span>
      </div>
    )
  }
  const ph = (txt: string) => <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 11, color: 'var(--color-muted-foreground)', opacity: 0.5, padding: '16px 0' }}>{txt}</div>
  return (
    <div style={{ ...card, position: 'absolute', right: 0, top: '100%', marginTop: 6, zIndex: 50, width: 380, maxWidth: 'calc(100vw - 1rem)' }}>
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
export default function TemplatePage() {
  const { id } = useParams()
  const location = useLocation()
  const base = id ? location.pathname.slice(0, location.pathname.length - id.length - 1) : location.pathname
  if (id) return <TemplateLegacyForm base={base} />
  return <TemplateList base={base} />
}

// ── Liste (native) ──────────────────────────────────────────────────────────
function TemplateList({ base }: { base: string }) {
  const t = useT()
  const navigate = useNavigate()
  const [items, setItems] = useState<TemplateItem[]>([])
  const [stats, setStats] = useState<TemplateStats | null>(null)
  const [sites, setSites] = useState<SiteOption[]>([])
  const [loading, setLoading] = useState(false)
  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [site, setSite] = useState<number | null>(null)
  const [type, setType] = useState('')
  const [sortCol, setSortCol] = useState<string | null>(null)
  const [sortAsc, setSortAsc] = useState(true)
  const [toDelete, setToDelete] = useState<TemplateItem | null>(null)
  const [tick, setTick] = useState(0)
  const [cols, setCols] = useState<ColDef[]>(loadCols)
  const [showCols, setShowCols] = useState(false)

  useEffect(() => { fetchTemplateStats().then(setStats).catch(() => null) }, [tick])
  useEffect(() => { fetchTemplateSites().then(setSites).catch(() => null) }, [])
  useEffect(() => {
    setLoading(true)
    fetchTemplates({ search, site, type }).then((r) => setItems(r.items)).catch(() => null).finally(() => setLoading(false))
  }, [search, site, type, tick])

  const cell = (r: TemplateItem, c: string): string | number => (
    c === 'id' ? r.id : c === 'name' ? r.name : c === 'type' ? r.typeLabel : c === 'ctrl' ? r.controllerAction : c === 'layout' ? r.layout : c === 'site' ? r.siteName : c === 'date' ? r.creationDate : ''
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
  async function confirmDelete() {
    if (!toDelete) return
    try { await deleteTemplate(toDelete.id); setToDelete(null); setTick((x) => x + 1) } catch { setToDelete(null) }
  }
  const renderCellNode = (r: TemplateItem, c: string) => {
    if (c === 'type') return <span style={{ display: 'inline-block', padding: '2px 8px', borderRadius: 6, fontSize: 12, background: 'color-mix(in srgb, var(--color-primary) 10%, transparent)', color: 'var(--color-primary)' }}>{r.typeLabel}</span>
    if (c === 'ctrl' || c === 'layout') return <span style={{ fontFamily: 'monospace', fontSize: 13 }}>{String(cell(r, c)) || '—'}</span>
    if (c === 'name') return <span style={{ fontWeight: 500 }}>{r.name}</span>
    if (c === 'date') return fmtDate(r.creationDate)
    return String(cell(r, c)) || '—'
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, padding: 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 }}>
        <div>
          <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0 }}>{t('title')}</h1>
          <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', margin: '2px 0 0' }}>{t('subtitle')}</p>
        </div>
        <div style={{ display: 'flex', gap: 8 }}>
          <button style={btnGhost} onClick={() => setTick((x) => x + 1)} title={t('refresh')}>↻</button>
          <button style={btnPrimary} onClick={() => navigate(`${base}/new`)}><PlusIcon />{t('new')}</button>
        </div>
      </div>

      <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
        <Kpi label={t('kpi_total')} value={stats?.total ?? null} />
        <Kpi label={t('kpi_sites')} value={stats?.sites ?? null} />
        <Kpi label={t('kpi_types')} value={stats?.types ?? null} />
      </div>

      <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
        <input style={{ ...inputCss, flex: 1, minWidth: 220 }} value={searchInput} onChange={(e) => setSearchInput(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && setSearch(searchInput.trim())} placeholder={t('search')} />
        <select style={{ ...inputCss, width: 'auto' }} value={site ?? ''} onChange={(e) => setSite(e.target.value ? Number(e.target.value) : null)}>
          <option value="">{t('all_sites')}</option>
          {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
        </select>
        <select style={{ ...inputCss, width: 'auto' }} value={type} onChange={(e) => setType(e.target.value)}>
          <option value="">{t('all_types')}</option>
          <option value="ZF2">Laminas</option>
          <option value="PHP">PHP</option>
          <option value="TWG">Twig</option>
        </select>
        <div style={{ position: 'relative' }}>
          <button style={btnGhost} onClick={() => setShowCols((v) => !v)}><GripIcon />{t('columns')}</button>
          {showCols && <ColManager cols={cols} labelFor={(id) => t(COL_LABEL[id])} onChange={setCols} onClose={() => setShowCols(false)} />}
        </div>
      </div>

      <div style={{ ...card, overflow: 'hidden' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: 720 }}>
          <thead style={{ background: 'var(--color-muted,rgba(0,0,0,.03))' }}>
            <tr>
              {visibleCols(cols).map(({ id }) => (
                <th key={id} style={{ ...th, cursor: 'pointer', ...(id === 'id' ? { width: 60 } : {}) }} onClick={() => toggleSort(id)}>
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
                  <td key={id} style={{ ...td, ...(id === 'id' ? { color: 'var(--color-muted-foreground)', fontVariantNumeric: 'tabular-nums' } : {}) }}>{renderCellNode(r, id)}</td>
                ))}
                <td style={td}>
                  <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 4 }}>
                    <button style={iconBtn} title={t('edit')} onClick={() => navigate(`${base}/${r.id}`)}><PencilIcon /></button>
                    <button style={{ ...iconBtn, color: 'var(--color-destructive,#ef4444)' }} title={t('del')} onClick={() => setToDelete(r)}><TrashIcon /></button>
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
    </div>
  )
}

// ── Création / édition : outil legacy en iframe persistante ───────────────────
function getFrame(): HTMLIFrameElement {
  let f = document.getElementById(FRAME_ID) as HTMLIFrameElement | null
  if (!f) {
    f = document.createElement('iframe')
    f.id = FRAME_ID
    f.src = `/melis/react-tool-page?key=${encodeURIComponent(MELIS_KEY)}`
    f.title = 'Templates'
    f.style.cssText = 'position:fixed;border:0;display:none;z-index:1;'
    document.body.appendChild(f)
  }
  return f
}

function TemplateLegacyForm({ base }: { base: string }) {
  const t = useT()
  const navigate = useNavigate()
  const anchorRef = useRef<HTMLDivElement>(null)
  useEffect(() => {
    const f = getFrame()
    const anchor = anchorRef.current!
    const sync = () => {
      const r = anchor.getBoundingClientRect()
      f.style.left = `${r.left}px`; f.style.top = `${r.top}px`
      f.style.width = `${r.width}px`; f.style.height = `${r.height}px`; f.style.display = 'block'
    }
    sync()
    const ro = new ResizeObserver(sync); ro.observe(anchor)
    window.addEventListener('resize', sync); window.addEventListener('scroll', sync, true)
    return () => { f.style.display = 'none'; ro.disconnect(); window.removeEventListener('resize', sync); window.removeEventListener('scroll', sync, true) }
  }, [])
  return (
    <div style={{ display: 'flex', flexDirection: 'column', height: '100%', minHeight: 0 }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '12px 16px', borderBottom: '1px solid var(--color-border)' }}>
        <button style={{ ...btnGhost, height: 32, padding: '0 10px' }} onClick={() => navigate(base)}>← {t('back')}</button>
        <span style={{ fontSize: 13, color: 'var(--color-muted-foreground)' }}>{t('legacy_note')}</span>
      </div>
      <div ref={anchorRef} style={{ flex: 1, width: '100%', minHeight: 0 }} />
    </div>
  )
}
