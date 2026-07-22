import { useMemo, useState } from 'react'
import type { SiteModule } from '../sites-api'

/**
 * Onglet "Module Loading" du site — portage de l'outil Modules BO (melis-core ModulesPage),
 * mais par SITE et pour le FRONT (écrit module.load.php du site via le Save global → ordre = ordre
 * de la liste). Switches d'activation + drag'n'drop pour l'ORDRE (capital) + cascade de dépendances
 * (active les requis, prévient si des modules actifs dépendent de celui désactivé). Admin only (legacy).
 * La liste ordonnée est l'état remonté à l'éditeur (`modules`/`setModules`).
 */

/**
 * Remonte les modules actifs en tête (partition stable : l'ordre relatif — donc l'ordre de
 * chargement des actifs — est préservé). Appliqué UNIQUEMENT au chargement de l'onglet : ensuite
 * la liste ne bouge plus toute seule, (dés)activer un module le laisse sur place.
 */
export const activeFirst = (list: SiteModule[]): SiteModule[] =>
  [...list.filter((m) => m.active), ...list.filter((m) => !m.active)]

const tr = (fr: string, en: string) => ((document.documentElement.lang || 'fr').slice(0, 2) === 'en' ? en : fr)
const card: React.CSSProperties = { borderRadius: 10, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)' }
const btn: React.CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 32, padding: '0 12px', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'transparent', cursor: 'pointer', fontSize: 13 }
const btnPrimary: React.CSSProperties = { ...btn, border: 0, background: 'var(--color-primary,#cb4040)', color: '#fff', fontWeight: 600 }
const input: React.CSSProperties = { height: 36, width: '100%', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-background,#fff)', padding: '0 10px', fontSize: 14, boxSizing: 'border-box' }
const muted = 'var(--color-muted-foreground,#6b7280)'

function Switch({ checked, disabled, onChange }: { checked: boolean; disabled?: boolean; onChange: (v: boolean) => void }) {
  return (
    <button type="button" role="switch" aria-checked={checked} disabled={disabled} onClick={() => onChange(!checked)}
      style={{ position: 'relative', display: 'inline-flex', alignItems: 'center', height: 20, width: 36, flexShrink: 0, borderRadius: 999, border: 0,
        background: checked ? '#10b981' : '#ef4444',
        cursor: disabled ? 'not-allowed' : 'pointer', opacity: disabled ? 0.5 : 1, transition: 'background .15s', padding: 0 }}>
      <span style={{ display: 'inline-block', width: 16, height: 16, borderRadius: 999, background: '#fff', boxShadow: '0 1px 2px rgba(0,0,0,.2)', transform: checked ? 'translateX(18px)' : 'translateX(2px)', transition: 'transform .15s' }} />
    </button>
  )
}

const GripIcon = () => (
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{ flexShrink: 0, color: muted, cursor: 'grab' }}>
    <circle cx="9" cy="6" r="1" /><circle cx="9" cy="12" r="1" /><circle cx="9" cy="18" r="1" /><circle cx="15" cy="6" r="1" /><circle cx="15" cy="12" r="1" /><circle cx="15" cy="18" r="1" />
  </svg>
)

export function ModuleLoaderTab({ isAdmin, modules, setModules }: {
  siteId: number
  isAdmin: boolean
  modules: SiteModule[]
  setModules: (updater: (prev: SiteModule[]) => SiteModule[]) => void
}) {
  const [search, setSearch] = useState('')
  const [dragIndex, setDragIndex] = useState<number | null>(null)
  const [confirm, setConfirm] = useState<{ module: string; cascade: string[] } | null>(null)

  const byName = useMemo(() => Object.fromEntries(modules.map((m) => [m.name, m])), [modules])
  const activeCount = modules.filter((m) => m.active).length

  // Fermeture transitive des requis (à activer si M l'est).
  function requiresClosure(name: string): string[] {
    const out = new Set<string>(); const stack = [name]
    while (stack.length) { const cur = stack.pop()!; for (const dep of byName[cur]?.requires ?? []) if (!out.has(dep)) { out.add(dep); stack.push(dep) } }
    return [...out]
  }
  // Fermeture transitive de TOUS les dépendants (actifs ou non) — pour l'affichage du popup.
  function allDependentsClosure(name: string): string[] {
    const out = new Set<string>(); const stack = [name]
    while (stack.length) { const cur = stack.pop()!; for (const dep of byName[cur]?.dependents ?? []) if (!out.has(dep)) { out.add(dep); stack.push(dep) } }
    return [...out]
  }
  function applyActive(names: string[], value: boolean) {
    const set = new Set(names)
    setModules((prev) => prev.map((m) => (set.has(m.name) ? { ...m, active: value } : m)))
  }
  function handleToggle(name: string, value: boolean) {
    if (!isAdmin) return
    if (value) {
      const deps = requiresClosure(name).filter((d) => !byName[d]?.active)
      applyActive([name, ...deps], true)
    } else {
      const cascade = allDependentsClosure(name)
      if (cascade.length) { setConfirm({ module: name, cascade }); return }
      applyActive([name], false)
    }
  }
  function setAll(value: boolean) { if (isAdmin) setModules((prev) => prev.map((m) => ({ ...m, active: value }))) }

  const canReorder = isAdmin && search.trim() === ''
  function onDrop(targetIndex: number) {
    if (dragIndex === null || dragIndex === targetIndex) { setDragIndex(null); return }
    setModules((prev) => { const next = [...prev]; const [moved] = next.splice(dragIndex, 1); next.splice(targetIndex, 0, moved); return next })
    setDragIndex(null)
  }

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase()
    return q ? modules.filter((m) => m.name.toLowerCase().includes(q) || m.package.toLowerCase().includes(q)) : modules
  }, [modules, search])

  return (
    <div style={{ maxWidth: 820, display: 'flex', flexDirection: 'column', gap: 14 }}>
      {!isAdmin && (
        <div style={{ ...card, padding: '10px 14px', fontSize: 13, color: '#b45309', borderColor: '#fcd34d', background: '#fffbeb' }}>
          {tr('Seuls les administrateurs peuvent modifier les modules du site (lecture seule).', 'Only administrators can change site modules (read-only).')}
        </div>
      )}

      {/* Barre d'actions */}
      <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 8 }}>
        <input style={{ ...input, flex: 1, minWidth: 220 }} placeholder={tr('Rechercher un module…', 'Search a module…')} value={search} onChange={(e) => setSearch(e.target.value)} />
        <span style={{ fontSize: 12, color: muted, padding: '0 6px' }}>{tr('Actifs', 'Active')}: {activeCount}/{modules.length}</span>
        {isAdmin && <button style={btn} onClick={() => setAll(true)}>{tr('Tout activer', 'Select all')}</button>}
        {isAdmin && <button style={btn} onClick={() => setAll(false)}>{tr('Tout désactiver', 'Deselect all')}</button>}
      </div>

      <p style={{ fontSize: 12, color: muted, margin: 0 }}>
        {canReorder ? tr('Glissez-déposez pour définir l’ordre de chargement (important).', 'Drag & drop to set the load order (important).')
          : tr('Réordonnancement désactivé pendant une recherche.', 'Reordering disabled while searching.')}
      </p>

      {/* Liste */}
      <div style={{ ...card, overflow: 'hidden' }}>
        {filtered.length === 0 ? (
          <div style={{ padding: 24, fontSize: 13, color: muted, textAlign: 'center' }}>{tr('Aucun module.', 'No module.')}</div>
        ) : filtered.map((m) => {
          const realIndex = modules.indexOf(m)
          return (
            <div key={m.name}
              draggable={canReorder}
              onDragStart={() => canReorder && setDragIndex(realIndex)}
              onDragOver={(e) => { if (canReorder) e.preventDefault() }}
              onDrop={() => canReorder && onDrop(realIndex)}
              onDragEnd={() => setDragIndex(null)}
              style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '10px 14px', borderTop: '1px solid var(--color-border,#f0f0f0)',
                background: dragIndex === realIndex ? 'color-mix(in srgb, var(--color-primary,#cb4040) 6%, transparent)' : 'transparent',
                opacity: m.active ? 1 : 0.6 }}>
              {canReorder ? <GripIcon /> : <span style={{ width: 14, flexShrink: 0 }} />}
              <div style={{ minWidth: 0, flex: 1 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                  <span style={{ fontWeight: 600, fontSize: 14, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{m.name}</span>
                  {m.version && <span style={{ fontSize: 11, color: muted, fontVariantNumeric: 'tabular-nums' }}>{m.version}</span>}
                </div>
                <div style={{ display: 'flex', gap: 8, fontSize: 12, color: muted }}>
                  {m.package && <code style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{m.package}</code>}
                  {m.requires.length > 0 && <span title={m.requires.join(', ')} style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>· {tr('requiert', 'requires')}: {m.requires.join(', ')}</span>}
                </div>
              </div>
              <Switch checked={m.active} disabled={!isAdmin} onChange={(v) => handleToggle(m.name, v)} />
            </div>
          )
        })}
      </div>

      {/* Confirmation désactivation en cascade */}
      {confirm && (
        <div style={{ position: 'fixed', inset: 0, zIndex: 60, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.5)' }}>
          <div style={{ ...card, width: '100%', maxWidth: 460, padding: 24 }}>
            <h3 style={{ margin: 0, fontSize: 16, fontWeight: 600 }}>{tr('Désactivation des modules', 'Module deactivation')}</h3>
            <p style={{ marginTop: 8, fontSize: 14, color: muted }}>
              {tr(`Les modules suivants sont dépendants de « ${confirm.module} » :`, `The following modules are dependant of "${confirm.module}":`)}
            </p>
            <ul style={{ marginTop: 8, paddingLeft: 18, fontSize: 14 }}>{confirm.cascade.map((d) => (
              <li key={d}>{d}{!byName[d]?.active && <span style={{ color: muted, fontSize: 12 }}> ({tr('déjà inactif', 'already inactive')})</span>}</li>
            ))}</ul>
            <p style={{ marginTop: 12, fontSize: 14, fontWeight: 500 }}>
              {tr('Voulez-vous désactiver ces modules aussi ?', 'Do you want to deactivate these modules too?')}
            </p>
            <div style={{ marginTop: 20, display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
              {/* No : ne désactive que le module lui-même, laisse les dépendants actifs (comme le legacy). */}
              <button style={{ ...btn, color: '#dc2626', borderColor: '#dc2626' }} onClick={() => { applyActive([confirm.module], false); setConfirm(null) }}>
                {tr('Non', 'No')}
              </button>
              {/* Yes : désactive le module ET tous ses dépendants (les inactifs sont déjà off → no-op). */}
              <button style={{ ...btnPrimary, background: '#16a34a' }} onClick={() => { applyActive([confirm.module, ...confirm.cascade.filter((d) => byName[d]?.active)], false); setConfirm(null) }}>
                {tr('Oui', 'Yes')}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default ModuleLoaderTab
