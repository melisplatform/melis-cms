import { useState, type CSSProperties } from 'react'

/* ──────────────────────────────────────────────────────────────────────────
 * Modale d'export partagée par les briques MelisCms (Redirections, Templates…).
 * Mêmes capacités que l'outil de référence (Users BO) : format Excel (.xlsx) / CSV,
 * panneaux Incluses / Exclues réordonnables par glisser-déposer, export de TOUTES les
 * lignes via `fetchAll`. La brique ne peut PAS importer xlsx (React/modules externalisés) :
 * on réutilise l'instance XLSX exposée par l'hôte (`window.MelisXLSX`, posée dans MelisCore's
 * main.tsx) ; si absente, on retombe automatiquement sur l'export CSV (sans dépendance).
 * Styles inline + variables CSS du thème, i18n FR/EN minimal lu depuis <html lang>.
 * ────────────────────────────────────────────────────────────────────────── */

type XlsxLike = {
  utils: {
    aoa_to_sheet: (data: (string | number)[][]) => unknown
    book_new: () => unknown
    book_append_sheet: (wb: unknown, ws: unknown, name: string) => void
  }
  writeFile: (wb: unknown, filename: string) => void
}
function getXLSX(): XlsxLike | null {
  return (window as unknown as { MelisXLSX?: XlsxLike }).MelisXLSX ?? null
}

type Lang = 'fr' | 'en'
function currentLang(): Lang { return (document.documentElement.lang || 'en').toLowerCase().startsWith('fr') ? 'fr' : 'en' }
const DICT: Record<Lang, Record<string, string>> = {
  fr: {
    export: 'Exporter', title: 'Exporter les données', subtitle: '{n} lignes avec les filtres actifs',
    included: 'Incluses', excluded: 'Exclues', drag_here: 'Glisser ici',
    download: 'Télécharger {fmt}', exporting: 'Export…', error: 'Erreur lors de l’export', cancel: 'Annuler',
  },
  en: {
    export: 'Export', title: 'Export data', subtitle: '{n} rows with the active filters',
    included: 'Included', excluded: 'Excluded', drag_here: 'Drag here',
    download: 'Download {fmt}', exporting: 'Exporting…', error: 'Error during export', cancel: 'Cancel',
  },
}
function tr(key: string, vars?: Record<string, string | number>): string {
  let s = DICT[currentLang()][key] ?? key
  if (vars) for (const [k, v] of Object.entries(vars)) s = s.replaceAll(`{${k}}`, String(v))
  return s
}

const card: CSSProperties = { border: '1px solid var(--color-border)', background: 'var(--color-card)', borderRadius: 12, boxShadow: '0 1px 2px rgba(0,0,0,.04)' }
const panelCss: CSSProperties = { display: 'flex', flexDirection: 'column', gap: 2, minHeight: 100, borderRadius: 8, border: '1px dashed var(--color-border)', padding: 6 }
const panelTitle: CSSProperties = { padding: '0 6px 4px', fontSize: 10, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.06em', color: 'var(--color-muted-foreground)' }
const btnGhost: CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 34, padding: '0 12px', borderRadius: 8, border: '1px solid var(--color-border)', background: 'var(--color-card)', color: 'var(--color-foreground)', fontSize: 14, cursor: 'pointer' }
const btnPrimary: CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 34, padding: '0 14px', borderRadius: 8, border: 0, background: 'var(--color-primary)', color: 'var(--color-primary-foreground,#fff)', fontSize: 14, fontWeight: 500, cursor: 'pointer' }
const GripIcon = () => <svg style={{ width: 13, height: 13, flexShrink: 0, color: 'var(--color-muted-foreground)' }} viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" /><circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" /><circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" /></svg>
export const DownloadIcon = () => <svg style={{ width: 15, height: 15, flexShrink: 0 }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" /></svg>
const ExcelIcon = () => <svg style={{ width: 16, height: 16, flexShrink: 0 }} viewBox="0 0 24 24" fill="none"><rect x="1" y="1" width="22" height="22" rx="3" fill="#217346" /><line x1="7.5" y1="7.5" x2="16.5" y2="16.5" stroke="white" strokeWidth="2.5" strokeLinecap="round" /><line x1="16.5" y1="7.5" x2="7.5" y2="16.5" stroke="white" strokeWidth="2.5" strokeLinecap="round" /></svg>
const CsvIcon = () => <svg style={{ width: 16, height: 16, flexShrink: 0 }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" /></svg>

export type ExportCol = { id: string; visible: boolean }

export function ExportModal<T>({ cols, labelFor, fetchAll, getCell, filename, sheetName, total, onClose }: {
  cols: ExportCol[]
  labelFor: (id: string) => string
  fetchAll: () => Promise<T[]>
  getCell: (item: T, id: string) => string | number
  filename: string
  sheetName: string
  total: number
  onClose: () => void
}) {
  const xlsx = getXLSX()
  const [included, setIncluded] = useState<ExportCol[]>(() => cols.filter(c => c.visible))
  const [excluded, setExcluded] = useState<ExportCol[]>(() => cols.filter(c => !c.visible))
  const [format, setFormat] = useState<'csv' | 'xlsx'>(xlsx ? 'xlsx' : 'csv')
  const [exporting, setExporting] = useState(false)
  const [dragId, setDragId] = useState<string | null>(null)
  const [over, setOver] = useState<{ id: string; panel: 'included' | 'excluded' } | null>(null)

  function drop(panel: 'included' | 'excluded') {
    if (!dragId) return
    const src = [...included, ...excluded].find(c => c.id === dragId)!
    let inc = included.filter(c => c.id !== dragId)
    let exc = excluded.filter(c => c.id !== dragId)
    if (panel === 'included') {
      const dst = over?.id
      if (!dst || dst === '__panel__') inc = [...inc, src]
      else { const i = inc.findIndex(c => c.id === dst); inc = i === -1 ? [...inc, src] : [...inc.slice(0, i), src, ...inc.slice(i)] }
    } else { exc = [...exc, src] }
    setIncluded(inc); setExcluded(exc); setDragId(null); setOver(null)
  }

  function item(col: ExportCol, panel: 'included' | 'excluded') {
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
  const ph = () => <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 11, color: 'var(--color-muted-foreground)', opacity: 0.5, padding: '12px 0' }}>{tr('drag_here')}</div>

  async function doExport() {
    if (included.length === 0) return
    setExporting(true)
    try {
      const all = await fetchAll()
      const header = included.map(c => labelFor(c.id))
      const rows = all.map(it => included.map(c => getCell(it, c.id)))
      const dateStr = new Date().toISOString().slice(0, 10)
      if (format === 'xlsx' && xlsx) {
        const ws = xlsx.utils.aoa_to_sheet([header, ...rows])
        const wb = xlsx.utils.book_new()
        xlsx.utils.book_append_sheet(wb, ws, sheetName)
        xlsx.writeFile(wb, `${filename}-${dateStr}.xlsx`)
      } else {
        const csv = [header, ...rows].map(r => r.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')).join('\n')
        const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' })
        const url = URL.createObjectURL(blob)
        const a = Object.assign(document.createElement('a'), { href: url, download: `${filename}-${dateStr}.csv` })
        document.body.appendChild(a); a.click(); document.body.removeChild(a)
        URL.revokeObjectURL(url)
      }
      onClose()
    } catch (e) {
      alert(e instanceof Error ? e.message : tr('error'))
    } finally { setExporting(false) }
  }

  const tab = (active: boolean): CSSProperties => ({ flex: 1, display: 'inline-flex', alignItems: 'center', justifyContent: 'center', gap: 8, height: 36, borderRadius: 6, border: 0, fontSize: 14, fontWeight: 500, cursor: 'pointer', background: active ? 'var(--color-card)' : 'transparent', color: active ? 'var(--color-foreground)' : 'var(--color-muted-foreground)', boxShadow: active ? '0 1px 2px rgba(0,0,0,.06)' : 'none' })

  return (
    <div style={{ position: 'fixed', inset: 0, zIndex: 60, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.5)' }}
      onClick={(e) => { if (e.target === e.currentTarget) onClose() }}>
      <div style={{ ...card, width: '100%', maxWidth: 480 }}>
        <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', padding: '16px 20px', borderBottom: '1px solid var(--color-border)' }}>
          <div>
            <h2 style={{ fontSize: 14, fontWeight: 600, margin: 0 }}>{tr('title')}</h2>
            <p style={{ fontSize: 12, color: 'var(--color-muted-foreground)', margin: '2px 0 0' }}>{tr('subtitle', { n: total })}</p>
          </div>
          <button style={{ border: 0, background: 'transparent', cursor: 'pointer', color: 'var(--color-muted-foreground)', fontSize: 16 }} onClick={onClose}>✕</button>
        </div>
        <div style={{ padding: 16, display: 'flex', flexDirection: 'column', gap: 16 }}>
          <div style={{ display: 'flex', gap: 4, padding: 4, borderRadius: 8, border: '1px solid var(--color-border)', background: 'color-mix(in srgb, var(--color-muted,#888) 12%, transparent)' }}>
            <button style={tab(format === 'xlsx')} disabled={!xlsx} onClick={() => xlsx && setFormat('xlsx')} title={xlsx ? '' : 'XLSX indisponible'}><ExcelIcon />Excel (.xlsx)</button>
            <button style={tab(format === 'csv')} onClick={() => setFormat('csv')}><CsvIcon />CSV (.csv)</button>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8 }}>
            <div style={panelCss}
              onDragOver={(e) => { e.preventDefault(); if (over?.id !== '__panel__' || over?.panel !== 'excluded') setOver({ id: '__panel__', panel: 'excluded' }) }}
              onDrop={(e) => { e.preventDefault(); drop('excluded') }}>
              <p style={panelTitle}>{tr('excluded')}</p>
              {excluded.length === 0 ? ph() : excluded.map(c => item(c, 'excluded'))}
            </div>
            <div style={panelCss}
              onDragOver={(e) => { e.preventDefault(); if (over?.id !== '__panel__' || over?.panel !== 'included') setOver({ id: '__panel__', panel: 'included' }) }}
              onDrop={(e) => { e.preventDefault(); drop('included') }}>
              <p style={panelTitle}>{tr('included')}</p>
              {included.length === 0 ? ph() : included.map(c => item(c, 'included'))}
            </div>
          </div>
        </div>
        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, padding: '12px 16px', borderTop: '1px solid var(--color-border)' }}>
          <button style={btnGhost} onClick={onClose} disabled={exporting}>{tr('cancel')}</button>
          <button style={{ ...btnPrimary, opacity: included.length === 0 || exporting ? 0.6 : 1 }} onClick={doExport} disabled={exporting || included.length === 0}>
            <DownloadIcon />{exporting ? tr('exporting') : tr('download', { fmt: format.toUpperCase() })}
          </button>
        </div>
      </div>
    </div>
  )
}
