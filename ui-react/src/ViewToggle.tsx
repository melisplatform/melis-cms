import { type CSSProperties } from 'react'

/* Toggle « New (React) / Old (outil legacy en iframe) » — équivalent brique du ViewModeToggle
 * de MelisCore. Partagé par les briques MelisCms (Templates, Redirections…). Styles inline +
 * variables CSS du thème. La page hôte gère l'état `mode` et le montage de l'iframe « Old ». */

export type ViewMode = 'react' | 'iframe'

const sIcon = { width: 15, height: 15, flexShrink: 0 } as const
const SparkIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z" /></svg>
const LayoutIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" /><path d="M3 9h18M9 21V9" /></svg>

export function ViewToggle({ mode, onChange }: { mode: ViewMode; onChange: (m: ViewMode) => void }) {
  const tab = (active: boolean): CSSProperties => ({ display: 'inline-flex', alignItems: 'center', gap: 6, height: 30, padding: '0 12px', borderRadius: 6, border: 0, fontSize: 12, fontWeight: 500, cursor: 'pointer', background: active ? 'var(--color-card)' : 'transparent', color: active ? 'var(--color-foreground)' : 'var(--color-muted-foreground)', boxShadow: active ? '0 1px 2px rgba(0,0,0,.06)' : 'none' })
  return (
    <div style={{ display: 'inline-flex', gap: 4, padding: 4, borderRadius: 8, border: '1px solid var(--color-border)', background: 'color-mix(in srgb, var(--color-muted,#888) 12%, transparent)' }}>
      <button style={tab(mode === 'react')} onClick={() => onChange('react')}><SparkIcon />New</button>
      <button style={tab(mode === 'iframe')} onClick={() => onChange('iframe')}><LayoutIcon />Old</button>
    </div>
  )
}
