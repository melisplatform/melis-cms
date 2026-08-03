import { useState, useEffect } from 'react'

import SitesList from './SitesList'
import SiteWizard from './SiteWizard'
import SiteEditor from './SiteEditor'
import { markSitesListStale } from './sites-api'

/**
 * Conteneur de l'outil Sites (brique MelisCms), monté une fois par le shell sur l'onglet « Sites ».
 * Reproduit le système de SOUS-ONGLETS des Utilisateurs : on reste sur l'unique onglet de shell
 * « Sites » et on affiche une barre de sous-onglets DANS l'outil (← Retour + un onglet par site
 * édité). Plusieurs sites éditables en parallèle — un SiteEditor gardé monté par site (état préservé).
 */

const LANG = (document.documentElement.lang || 'fr').slice(0, 2)
const tr = (fr: string, en: string) => (LANG === 'en' ? en : fr)

type View = { kind: 'list' } | { kind: 'new' } | { kind: 'edit'; id: number }
interface OpenTab { id: number; label: string }

const PageIcon = () => (
  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{ flexShrink: 0 }}>
    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" /><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
  </svg>
)

function SiteSubTabBar({ tabs, activeId, onBack, onSelect, onClose }: {
  tabs: OpenTab[]
  activeId: number | null
  onBack: () => void
  onSelect: (id: number) => void
  onClose: (id: number) => void
}) {
  return (
    <div style={{ display: 'flex', alignItems: 'stretch', borderBottom: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-background,#fff)', padding: '0 8px', overflowX: 'auto', flexShrink: 0 }}>
      <button onClick={onBack}
        style={{ marginRight: 4, flexShrink: 0, display: 'inline-flex', alignItems: 'center', padding: '6px 8px', fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)', background: 'transparent', border: 0, cursor: 'pointer', whiteSpace: 'nowrap' }}>
        ← {tr('Retour', 'Back')}
      </button>
      {tabs.map((tab) => {
        const isActive = activeId === tab.id
        return (
          <div key={tab.id} onClick={() => onSelect(tab.id)}
            style={{ display: 'inline-flex', alignItems: 'center', gap: 6, padding: '0 10px', fontSize: 12, fontWeight: 500, cursor: 'pointer', whiteSpace: 'nowrap', userSelect: 'none',
              borderBottom: isActive ? '2px solid var(--color-primary,#cb4040)' : '2px solid transparent',
              color: isActive ? 'var(--color-foreground)' : 'var(--color-muted-foreground,#6b7280)',
              background: isActive ? 'var(--color-background,#fff)' : 'transparent' }}>
            <PageIcon />
            <span style={{ maxWidth: 140, overflow: 'hidden', textOverflow: 'ellipsis' }}>{tab.label}</span>
            <button onClick={(e) => { e.stopPropagation(); onClose(tab.id) }}
              style={{ marginLeft: 2, borderRadius: 4, padding: 2, border: 0, background: 'transparent', cursor: 'pointer', color: 'inherit', lineHeight: 0 }} title={tr('Fermer', 'Close')}>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
            </button>
          </div>
        )
      })}
    </div>
  )
}

// Sentinelle d'id pour le sous-onglet « Nouveau site » (les ids de site réels sont > 0).
const NEW_TAB = 0

/**
 * Survie au RECHARGEMENT (F5). Les sous-onglets sont de l'état d'outil, PAS des routes : un reload
 * reboote le SPA et les perdrait. On les persiste donc en `sessionStorage` (per-onglet-navigateur,
 * comme le store d'onglets de l'hôte — `localStorage` les ressusciterait dans chaque fenêtre).
 *
 * ⚠️ L'id de l'URL est capturé ICI, au CHARGEMENT DU BUNDLE (module-scope), pas au 1er rendu :
 * la brique est chargée en `React.lazy`, et au moment où SitesPage rend, l'URL a déjà pu être
 * réécrite sans le `/:id` (par l'effet de reflet ci-dessous, qui tourne d'abord avec « aucun
 * onglet ouvert »). Lire l'URL plus tard = perdre la course.
 */
const SUBTABS_KEY = 'melis-sites-subtabs'
const URL_BOOT_SEG = /\/sites\/(new|\d+)\/?$/.exec(window.location.pathname)?.[1] ?? null
const URL_BOOT_ID = URL_BOOT_SEG == null ? null : URL_BOOT_SEG === 'new' ? NEW_TAB : Number(URL_BOOT_SEG)

interface BootState { open: OpenTab[]; activeId: number | null }

function loadBootState(): BootState {
  let open: OpenTab[] = []
  let activeId: number | null = null
  try {
    const p = JSON.parse(sessionStorage.getItem(SUBTABS_KEY) ?? 'null') as Partial<BootState> | null
    if (p && Array.isArray(p.open)) open = p.open.filter((o) => o && typeof o.id === 'number')
    if (p && typeof p.activeId === 'number') activeId = p.activeId
  } catch { /* storage indisponible / corrompu → on repart de zéro */ }
  if (URL_BOOT_ID != null) {
    if (!open.some((o) => o.id === URL_BOOT_ID)) {
      open = [...open, { id: URL_BOOT_ID, label: URL_BOOT_ID === NEW_TAB ? tr('Nouveau site', 'New site') : `Site #${URL_BOOT_ID}` }]
    }
    activeId = URL_BOOT_ID
  }
  // Un actif qui n'est plus dans la liste (storage incohérent) → on retombe sur la liste.
  if (activeId != null && !open.some((o) => o.id === activeId)) activeId = null
  return { open, activeId }
}

function saveBootState(open: OpenTab[], activeId: number | null) {
  try { sessionStorage.setItem(SUBTABS_KEY, JSON.stringify({ open, activeId })) } catch { /* quota / mode privé */ }
}

/**
 * Reflète le sous-onglet actif dans l'URL : /[section]/[tool]/:id (ou /new), comme l'outil
 * Utilisateurs. COSMÉTIQUE (history.replaceState) — PAS de navigation React Router, sinon le host
 * créerait un onglet de shell par id (le pattern « sous-onglets in-tool » garde l'état en local).
 * Le host (ToolTabBar) ne réécrit PAS l'URL de cet outil (il est dans SELF_MANAGED_URL).
 */
function reflectSubTabUrl(seg: string | number | null) {
  const base = window.location.pathname.replace(/\/(?:new|\d+)$/, '')
  const next = seg != null && seg !== '' ? `${base}/${seg}` : base
  if (window.location.pathname !== next) window.history.replaceState(window.history.state, '', next)
}

export default function SitesPage() {
  // Initialiseurs PARESSEUX : l'état restauré doit exister avant tout effet (notamment celui qui
  // reflète l'URL, qui effacerait le /:id s'il tournait sur un état vide).
  const [boot] = useState(loadBootState)
  const [view, setView] = useState<View>(
    boot.activeId == null ? { kind: 'list' } : boot.activeId === NEW_TAB ? { kind: 'new' } : { kind: 'edit', id: boot.activeId },
  )
  const [open, setOpen] = useState<OpenTab[]>(boot.open)

  function openEditor(id: number, label: string) {
    setOpen((prev) => (prev.some((o) => o.id === id) ? prev : [...prev, { id, label }]))
    setView({ kind: 'edit', id })
  }
  // « Nouveau » ouvre AUSSI un sous-onglet (comme l'édition) — plus d'onglet hors système.
  function openNew() {
    setOpen((prev) => (prev.some((o) => o.id === NEW_TAB) ? prev : [...prev, { id: NEW_TAB, label: tr('Nouveau site', 'New site') }]))
    setView({ kind: 'new' })
  }
  function closeTab(id: number) {
    setOpen((prev) => {
      const rest = prev.filter((o) => o.id !== id)
      setView((v) => {
        const isActive = (v.kind === 'edit' && v.id === id) || (v.kind === 'new' && id === NEW_TAB)
        if (!isActive) return v
        const last = rest[rest.length - 1]
        return last ? (last.id === NEW_TAB ? { kind: 'new' } : { kind: 'edit', id: last.id }) : { kind: 'list' }
      })
      return rest
    })
  }
  function setLabel(id: number, label: string) {
    setOpen((prev) => prev.map((o) => (o.id === id ? { ...o, label } : o)))
  }

  // ── Vue « Old » (iframe legacy) ──────────────────────────────────────────────────
  // Elle reste 100% LEGACY : ouvrir un site depuis la liste legacy ouvre le formulaire legacy DANS
  // l'iframe (tabOpen), et la barre d'onglets de l'hôte (ToolTabBar, alimentée par le pont
  // __melisToolTabs) permet de revenir à la liste — comme pour tout autre outil de la plateforme.
  // On ne détourne PLUS ces onglets vers le SiteEditor React : c'était le but du toggle de pouvoir
  // comparer les deux interfaces, et le détournement rendait la vue Old inutilisable.

  const activeId = view.kind === 'edit' ? view.id : view.kind === 'new' ? NEW_TAB : null
  const hasNew = open.some((o) => o.id === NEW_TAB)

  // URL = /[section]/[tool]/:id (ou /new), reflétée à chaque changement de sous-onglet actif.
  useEffect(() => {
    reflectSubTabUrl(view.kind === 'edit' ? view.id : view.kind === 'new' ? 'new' : null)
  }, [view])

  // Persistance des sous-onglets (survie au F5) — cf. SUBTABS_KEY.
  useEffect(() => { saveBootState(open, activeId) }, [open, activeId])

  // Fermer l'onglet de shell « Sites » doit VIDER ses sous-onglets, sinon rouvrir l'outil les
  // ressusciterait. L'hôte (Topbar) émet `melis:tab-closed` avec le path de l'onglet fermé.
  useEffect(() => {
    const onClosed = (e: Event) => {
      const path = (e as CustomEvent<{ path?: string }>).detail?.path ?? ''
      if (!/\/sites\/?$/.test(path)) return
      setOpen([])
      setView({ kind: 'list' })
    }
    window.addEventListener('melis:tab-closed', onClosed)
    return () => window.removeEventListener('melis:tab-closed', onClosed)
  }, [])

  return (
    <div style={{ display: 'flex', flexDirection: 'column', height: '100%' }}>
      {open.length > 0 && (
        <SiteSubTabBar tabs={open} activeId={activeId}
          onBack={() => setView({ kind: 'list' })}
          onSelect={(id) => setView(id === NEW_TAB ? { kind: 'new' } : { kind: 'edit', id })}
          onClose={closeTab} />
      )}

      <div style={{ flex: 1, minHeight: 0 }}>
        <div style={{ height: '100%', display: view.kind === 'list' ? 'block' : 'none' }}>
          <SitesList active={view.kind === 'list'} onEdit={openEditor} onNew={openNew} />
        </div>

        {/* Assistant de création — monté tant que le sous-onglet « Nouveau site » est ouvert (état préservé). */}
        {hasNew && (
          <div style={{ height: '100%', display: view.kind === 'new' ? 'block' : 'none' }}>
            <SiteWizard onCancel={() => closeTab(NEW_TAB)} onCreated={() => { markSitesListStale(); closeTab(NEW_TAB) }} />
          </div>
        )}

        {open.filter((o) => o.id !== NEW_TAB).map((o) => (
          <div key={o.id} style={{ height: '100%', display: activeId === o.id ? 'block' : 'none' }}>
            <SiteEditor siteId={o.id} onSaved={() => markSitesListStale()} onLabel={(l) => setLabel(o.id, l)}
              onLoadFail={() => closeTab(o.id)} />
          </div>
        ))}
      </div>
    </div>
  )
}
