import { type CSSProperties } from 'react'

/* Toggle « New (React) / Old (outil legacy en iframe) » — équivalent brique du ViewModeToggle
 * de MelisCore. Partagé par les briques MelisCms (Templates, Redirections…). Styles inline +
 * variables CSS du thème. La page hôte gère l'état `mode` et le montage de l'iframe « Old ». */

export type ViewMode = 'react' | 'iframe'

const sIcon = { width: 15, height: 15, flexShrink: 0 } as const
// Melis "M" logo mark (même tracé que MelisM dans melis-core/MelisClassicView.tsx) — la brique ne
// peut pas importer le composant hôte, donc le tracé SVG est reproduit ici à l'identique.
const MelisM = () => <svg style={sIcon} viewBox="0 0 70 70" fill="currentColor" aria-hidden="true"><path d="M57.4,0c-4.8,0-8.6,3.9-8.6,8.6v49.2c0,4.8,3.9,8.6,8.6,8.6s8.6-3.9,8.6-8.6V8.7C66,3.9,62.2,0,57.4,0Z" /><path d="M16.3,4.6C14,.4,8.8-1.2,4.6,1,.4,3.2-1.2,8.5,1,12.7l26.1,49.3c2.2,4.2,7.4,5.8,11.7,3.6,4.2-2.2,5.8-7.4,3.6-11.7L16.3,4.6Z" /><circle cx="8.8" cy="57.7" r="8.8" /></svg>
const LayoutIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" /><path d="M3 9h18M9 21V9" /></svg>

export function ViewToggle({ mode, onChange, compact = false, labels = { react: 'New', iframe: 'Old' } }: {
  mode: ViewMode; onChange: (m: ViewMode) => void; compact?: boolean; labels?: { react: string; iframe: string }
}) {
  const tab = (active: boolean): CSSProperties => ({ display: 'inline-flex', alignItems: 'center', gap: compact ? 0 : 6, height: 30, padding: compact ? '0 8px' : '0 12px', borderRadius: 6, border: 0, fontSize: 12, fontWeight: 500, cursor: 'pointer', background: active ? 'var(--color-card)' : 'transparent', color: active ? 'var(--color-foreground)' : 'var(--color-muted-foreground)', boxShadow: active ? '0 1px 2px rgba(0,0,0,.06)' : 'none' })
  return (
    <div style={{ display: 'inline-flex', gap: 4, padding: 4, borderRadius: 8, border: '1px solid var(--color-border)', background: 'color-mix(in srgb, var(--color-muted,#888) 12%, transparent)' }}>
      <button style={tab(mode === 'react')} onClick={() => onChange('react')} title={labels.react}><MelisM />{!compact && labels.react}</button>
      <button style={tab(mode === 'iframe')} onClick={() => onChange('iframe')} title={labels.iframe}><LayoutIcon />{!compact && labels.iframe}</button>
    </div>
  )
}
