import { useState, useEffect, useRef } from 'react'

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
 * Reflète le sous-onglet actif dans l'URL : /[section]/[tool]/:id (ou /new), comme l'outil
 * Utilisateurs. COSMÉTIQUE (history.replaceState) — PAS de navigation React Router, sinon le host
 * créerait un onglet de shell par id (le pattern « sous-onglets in-tool » garde l'état en local).
 * Le host (ToolTabBar) ne réécrit PAS l'URL de cet outil (il est dans SELF_MANAGED_SUBTABS).
 */
function reflectSubTabUrl(seg: string | number | null) {
  const base = window.location.pathname.replace(/\/(?:new|\d+)$/, '')
  const next = seg != null && seg !== '' ? `${base}/${seg}` : base
  if (window.location.pathname !== next) window.history.replaceState(window.history.state, '', next)
}

export default function SitesPage() {
  const [view, setView] = useState<View>({ kind: 'list' })
  const [open, setOpen] = useState<OpenTab[]>([])

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

  // ── Vue « Old » (iframe legacy) : router l'édition vers l'ÉDITEUR REACT ─────────
  // La liste legacy en iframe (SitesList mode Old) ouvre l'édition d'un site dans SA propre pile
  // d'onglets (qu'elle POSTe à l'hôte via __melisToolTabs). Plutôt que de laisser l'hôte afficher
  // une 2ᵉ barre (ToolTabBar) qui s'empile sur SiteSubTabBar, on intercepte le message ici : on
  // ouvre le SiteEditor React (même sous-onglet unique) et on referme l'onglet dans l'iframe pour
  // qu'elle revienne à sa liste (pas de rebond ni d'édition « fantôme »).
  const seenEditTabs = useRef<Set<string>>(new Set())
  useEffect(() => {
    function onMsg(e: MessageEvent) {
      const d = e.data as { __melisToolTabs?: boolean; melisKey?: string; tabs?: { id: string; label: string; active: boolean; primary?: boolean }[] } | null
      if (!d || !d.__melisToolTabs || d.melisKey !== 'meliscms_tool_sites') return
      const tabs = Array.isArray(d.tabs) ? d.tabs : []
      const primary = tabs.find((t) => t.primary)
      const present = new Set<string>()
      for (const t of tabs) {
        if (t.primary) continue
        present.add(t.id)
        if (seenEditTabs.current.has(t.id)) continue
        seenEditTabs.current.add(t.id)
        // id des onglets d'édition : "<siteId>_id_meliscms_tool_sites_edit_site".
        const m = t.id.match(/^(\d+)_id_meliscms_tool_sites_edit_site$/)
        if (!m) continue
        const siteId = Number(m[1])
        openEditor(siteId, t.label || `#${siteId}`)
        // Referme l'onglet dans l'iframe legacy → elle repasse sur sa liste (le SiteEditor React prend le relais).
        const frame = document.querySelector('iframe[title="Sites — Vue Melis"]') as HTMLIFrameElement | null
        try { frame?.contentWindow?.postMessage({ __melisToolTabCmd: true, melisKey: 'meliscms_tool_sites', cmd: 'close', id: t.id, next: primary?.id ?? null }, '*') } catch { /* ignore */ }
      }
      // Purge les ids d'onglets fermés (pour re-router une prochaine édition du même site).
      for (const id of Array.from(seenEditTabs.current)) if (!present.has(id)) seenEditTabs.current.delete(id)
    }
    window.addEventListener('message', onMsg)
    return () => window.removeEventListener('message', onMsg)
    // openEditor n'utilise que des setters stables → capture initiale suffisante.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const activeId = view.kind === 'edit' ? view.id : view.kind === 'new' ? NEW_TAB : null
  const hasNew = open.some((o) => o.id === NEW_TAB)

  // URL = /[section]/[tool]/:id (ou /new), reflétée à chaque changement de sous-onglet actif.
  useEffect(() => {
    reflectSubTabUrl(view.kind === 'edit' ? view.id : view.kind === 'new' ? 'new' : null)
  }, [view])

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
            <SiteEditor siteId={o.id} onSaved={() => markSitesListStale()} onLabel={(l) => setLabel(o.id, l)} />
          </div>
        ))}
      </div>
    </div>
  )
}
