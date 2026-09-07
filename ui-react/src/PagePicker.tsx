import { useEffect, useRef, useState } from 'react'
import { fetchTreeNodes, fetchPageTitle, type MelisTreeNode } from './cms-tree-api'
import { peT } from './page-editor-i18n'

/**
 * Sélecteur de page (id) — réutilise l'arbre lazy legacy
 * (GET /melis/MelisCms/TreeSites/get-tree-pages-by-page-id, cf. cms-tree-api).
 * Affiche le titre courant + un popover arborescent (expand paresseux, clic = sélection).
 * Aucun changement backend. Styles inline + variables CSS du thème (règle des briques).
 */

const box: React.CSSProperties = { borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-background,#fff)' }
const btn: React.CSSProperties = { display: 'inline-flex', alignItems: 'center', justifyContent: 'space-between', gap: 8, height: 36, width: '100%', padding: '0 10px', cursor: 'pointer', fontSize: 14, ...box }

function Node({ node, depth, onPick }: { node: MelisTreeNode; depth: number; onPick: (id: number, title: string) => void }) {
  const tr = peT()
  const [open, setOpen] = useState(false)
  const [children, setChildren] = useState<MelisTreeNode[] | null>(null)
  const [loading, setLoading] = useState(false)

  async function toggle() {
    if (!node.lazy) return
    const next = !open
    setOpen(next)
    if (next && children === null) {
      setLoading(true)
      setChildren(await fetchTreeNodes(node.key))
      setLoading(false)
    }
  }

  return (
    <div>
      <div style={{ display: 'flex', alignItems: 'center', gap: 4, padding: '4px 6px', paddingLeft: 6 + depth * 16 }}>
        <button
          onClick={toggle}
          style={{ width: 18, height: 18, border: 0, background: 'transparent', cursor: node.lazy ? 'pointer' : 'default', color: 'var(--color-muted-foreground,#6b7280)', fontSize: 11 }}
          title={node.lazy ? tr.expand : ''}
        >{node.lazy ? (open ? '▾' : '▸') : '·'}</button>
        <button
          onClick={() => onPick(node.key, node.title)}
          style={{ flex: 1, textAlign: 'left', border: 0, background: 'transparent', cursor: 'pointer', fontSize: 13, padding: '2px 4px', borderRadius: 6 }}
        >{node.title}</button>
      </div>
      {open && (
        <div>
          {loading && <div style={{ paddingLeft: 24 + depth * 16, fontSize: 12, color: 'var(--color-muted-foreground)' }}>…</div>}
          {(children ?? []).map((c) => <Node key={c.key} node={c} depth={depth + 1} onPick={onPick} />)}
        </div>
      )}
    </div>
  )
}

export function PagePicker({ value, title, onChange, placeholder }: {
  value: number
  title?: string
  onChange: (id: number, title: string) => void
  placeholder?: string
}) {
  const tr = peT()
  const [open, setOpen] = useState(false)
  const [roots, setRoots] = useState<MelisTreeNode[] | null>(null)
  // When only a page id is known (title not supplied, e.g. a prefilled config field), resolve its NAME.
  const [resolvedTitle, setResolvedTitle] = useState<string | undefined>(title)
  const ref = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (title) { setResolvedTitle(title); return }
    if (!value) { setResolvedTitle(undefined); return }
    let cancelled = false
    fetchPageTitle(value).then((t) => { if (!cancelled) setResolvedTitle(t || undefined) })
    return () => { cancelled = true }
  }, [value, title])

  useEffect(() => {
    function onDoc(e: MouseEvent) { if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false) }
    document.addEventListener('mousedown', onDoc)
    return () => document.removeEventListener('mousedown', onDoc)
  }, [])

  async function openPanel() {
    setOpen((o) => !o)
    if (roots === null) setRoots(await fetchTreeNodes(-1))
  }

  const display = value ? (resolvedTitle || `Page #${value}`) : (placeholder || tr.pickPage)

  return (
    <div ref={ref} style={{ position: 'relative' }}>
      <button style={btn} onClick={openPanel} type="button">
        <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', color: value ? 'inherit' : 'var(--color-muted-foreground)' }}>{display}</span>
        <span style={{ color: 'var(--color-muted-foreground)' }}>▾</span>
      </button>
      {open && (
        <div style={{ ...box, position: 'absolute', zIndex: 60, top: 40, left: 0, right: 0, maxHeight: 320, overflow: 'auto', boxShadow: '0 8px 24px rgba(0,0,0,.12)', padding: 6 }}>
          {roots === null ? (
            <div style={{ padding: 12, fontSize: 13, color: 'var(--color-muted-foreground)' }}>{tr.loading}</div>
          ) : roots.length === 0 ? (
            <div style={{ padding: 12, fontSize: 13, color: 'var(--color-muted-foreground)' }}>{tr.noPage}</div>
          ) : roots.map((n) => (
            <Node key={n.key} node={n} depth={0} onPick={(id, t) => { onChange(id, t); setOpen(false) }} />
          ))}
        </div>
      )}
    </div>
  )
}

export default PagePicker
