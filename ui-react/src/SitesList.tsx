import { useEffect, useMemo, useState } from 'react'

import { ViewToggle } from './ViewToggle'
import { fetchSites, deleteSite, consumeSitesListStale, type SiteItem } from './sites-api'

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
  col_id: { fr: 'ID', en: 'ID' },
  col_label: { fr: 'Nom du site', en: 'Site name' },
  col_name: { fr: 'Module', en: 'Module' },
  col_lang: { fr: 'Langues', en: 'Languages' },
  edit: { fr: 'Éditer', en: 'Edit' },
  del: { fr: 'Supprimer', en: 'Delete' },
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

const card: React.CSSProperties = { borderRadius: 12, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', boxShadow: '0 1px 2px rgba(0,0,0,.04)' }
const btnGhost: React.CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 32, padding: '0 10px', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'transparent', cursor: 'pointer', fontSize: 13 }
const btnPrimary: React.CSSProperties = { ...btnGhost, border: 0, background: 'var(--color-primary,#cb4040)', color: '#fff', fontWeight: 600 }
const inputCss: React.CSSProperties = { borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-background,#fff)', padding: '0 10px', fontSize: 14, boxSizing: 'border-box' }
const th: React.CSSProperties = { textAlign: 'left', fontSize: 12, fontWeight: 600, color: 'var(--color-muted-foreground,#6b7280)', padding: '10px 14px' }
const td: React.CSSProperties = { fontSize: 14, padding: '10px 14px', borderTop: '1px solid var(--color-border,#f0f0f0)' }

/** Flèche de rotation anti-horaire — bouton « Réinitialiser les filtres ». */
const ResetIcon = () => <svg style={{ width: 14, height: 14, flexShrink: 0 }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 2v6h6" /><path d="M3 13a9 9 0 1 0 3-7.7L3 8" /></svg>

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

export default function SitesList({ active, onEdit, onNew }: {
  active: boolean
  onEdit: (id: number, label: string) => void
  onNew: () => void
}) {
  const [items, setItems] = useState<SiteItem[]>([])
  const [loading, setLoading] = useState(false)
  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')
  const [tick, setTick] = useState(0)
  const [toDelete, setToDelete] = useState<SiteItem | null>(null)
  const [mode, setMode] = useState<'react' | 'iframe'>('react')
  const [frameLoaded, setFrameLoaded] = useState(false)

  useEffect(() => {
    setLoading(true)
    fetchSites(search).then(setItems).catch(() => null).finally(() => setLoading(false))
  }, [search, tick])

  // Recharge quand la liste redevient active après une création/édition/suppression.
  useEffect(() => { if (active && consumeSitesListStale()) setTick((x) => x + 1) }, [active])

  const sorted = useMemo(() => [...items].sort((a, b) => a.id - b.id), [items])

  // Réinitialiser les filtres : recherche (seul filtre de cette liste ; le tri est fixe, par id),
  // puis refetch. On vide `items` : sinon les lignes restent affichées pendant le rechargement et
  // le clic paraît sans effet quand aucune recherche n'était saisie.
  function resetFilters() {
    setSearchInput(''); setSearch('')
    setItems([])
    setTick((x) => x + 1)
  }

  async function confirmDelete() {
    if (!toDelete) return
    try { await deleteSite(toDelete.id) } catch { /* ignore */ }
    setToDelete(null); setTick((x) => x + 1)
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
            <input style={{ ...inputCss, height: 36, flex: 1, minWidth: 220 }} value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && setSearch(searchInput.trim())}
              placeholder={t('search')} />
            <button style={{ ...btnGhost, height: 36 }} onClick={resetFilters}><ResetIcon />{t('reset_filters')}</button>
          </div>

          <div style={{ ...card, overflow: 'hidden' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: 640 }}>
              <thead style={{ background: 'var(--color-muted,rgba(0,0,0,.03))' }}>
                <tr>
                  <th style={{ ...th, width: 60 }}>{t('col_id')}</th>
                  <th style={th}>{t('col_label')}</th>
                  <th style={th}>{t('col_name')}</th>
                  <th style={th}>{t('col_lang')}</th>
                  <th style={{ ...th, width: 90 }} />
                </tr>
              </thead>
              <tbody>
                {sorted.length === 0 && !loading ? (
                  <tr><td style={{ ...td, textAlign: 'center', color: 'var(--color-muted-foreground)', padding: '40px 16px' }} colSpan={5}>{t('empty')}</td></tr>
                ) : sorted.map((s) => (
                  <tr key={s.id}>
                    <td style={{ ...td, color: 'var(--color-muted-foreground)' }}>{s.id}</td>
                    <td style={{ ...td, fontWeight: 600 }}>{s.label}</td>
                    <td style={{ ...td, color: 'var(--color-muted-foreground)' }}>{s.name}</td>
                    <td style={td}>
                      {s.languages && s.languages.length > 0 ? (
                        <span style={{ display: 'inline-flex', gap: 5, flexWrap: 'wrap', alignItems: 'center' }}>
                          {s.languages.map((l) => <LangFlag key={l.id} locale={l.locale} name={l.name} />)}
                        </span>
                      ) : <span style={{ color: 'var(--color-muted-foreground)' }}>—</span>}
                    </td>
                    <td style={{ ...td, whiteSpace: 'nowrap', textAlign: 'right' }}>
                      <button style={{ ...btnGhost, height: 28 }} title={t('edit')}
                        onClick={() => onEdit(s.id, s.label || s.name)}>✎</button>
                      {can('delete') && <button style={{ ...btnGhost, height: 28, marginLeft: 6, color: '#b91c1c' }}
                        onClick={() => setToDelete(s)} title={t('del')}>🗑</button>}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
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
    </div>
  )
}
