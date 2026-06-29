import { useState } from 'react'

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

export default function SitesPage() {
  const [view, setView] = useState<View>({ kind: 'list' })
  const [open, setOpen] = useState<OpenTab[]>([])

  function openEditor(id: number, label: string) {
    setOpen((prev) => (prev.some((o) => o.id === id) ? prev : [...prev, { id, label }]))
    setView({ kind: 'edit', id })
  }
  function closeEditor(id: number) {
    setOpen((prev) => {
      const rest = prev.filter((o) => o.id !== id)
      setView((v) => (v.kind === 'edit' && v.id === id ? (rest.length ? { kind: 'edit', id: rest[rest.length - 1].id } : { kind: 'list' }) : v))
      return rest
    })
  }
  function setLabel(id: number, label: string) {
    setOpen((prev) => prev.map((o) => (o.id === id ? { ...o, label } : o)))
  }

  const activeId = view.kind === 'edit' ? view.id : null

  return (
    <div style={{ display: 'flex', flexDirection: 'column', height: '100%' }}>
      {open.length > 0 && (
        <SiteSubTabBar tabs={open} activeId={activeId}
          onBack={() => setView({ kind: 'list' })} onSelect={(id) => setView({ kind: 'edit', id })} onClose={closeEditor} />
      )}

      <div style={{ flex: 1, minHeight: 0 }}>
        <div style={{ height: '100%', display: view.kind === 'list' ? 'block' : 'none' }}>
          <SitesList active={view.kind === 'list'} onEdit={openEditor} onNew={() => setView({ kind: 'new' })} />
        </div>

        {view.kind === 'new' && (
          <SiteWizard onCancel={() => setView({ kind: 'list' })} onCreated={() => { markSitesListStale(); setView({ kind: 'list' }) }} />
        )}

        {open.map((o) => (
          <div key={o.id} style={{ height: '100%', display: activeId === o.id ? 'block' : 'none' }}>
            <SiteEditor siteId={o.id} onSaved={() => markSitesListStale()} onLabel={(l) => setLabel(o.id, l)} />
          </div>
        ))}
      </div>
    </div>
  )
}
