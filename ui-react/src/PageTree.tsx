import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { deletePage, fetchTreeNodes, nodeCache, type MelisTreeNode } from './cms-tree-api'

/* ── Tiny inline icons (the brick can't use host Tailwind/lucide; SVG uses currentColor) ── */
const sIcon = { width: 15, height: 15, flexShrink: 0 } as const
function HomeIcon() {
  return (
    <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M3 9.5 12 3l9 6.5" /><path d="M5 10v10h14V10" />
    </svg>
  )
}
function FolderIcon() {
  return (
    <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
    </svg>
  )
}
function FileIcon() {
  return (
    <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" /><path d="M14 3v5h5" />
    </svg>
  )
}
function NewspaperIcon() {
  return (
    <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M4 5h13v14H5a1 1 0 0 1-1-1z" /><path d="M17 8h3v9a2 2 0 0 1-2 2" /><path d="M8 8h5M8 12h5M8 16h5" />
    </svg>
  )
}
function Caret({ open }: { open: boolean }) {
  return (
    <svg style={{ width: 12, height: 12, flexShrink: 0, transform: open ? 'rotate(90deg)' : 'none', transition: 'transform .12s' }}
      viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
      <path d="m9 6 6 6-6 6" />
    </svg>
  )
}

function nodeIcon(type?: string) {
  switch ((type || '').toUpperCase()) {
    case 'SITE': return <HomeIcon />
    case 'FOLDER': return <FolderIcon />
    case 'NEWSLETTER': return <NewspaperIcon />
    default: return <FileIcon />
  }
}

/* ── Context-menu icons ── */
const mIcon = { width: 15, height: 15, flexShrink: 0 } as const
const mSvg = (children: React.ReactNode) => (
  <svg style={mIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">{children}</svg>
)
const ICONS = {
  new:    mSvg(<><path d="M12 5v14" /><path d="M5 12h14" /></>),
  edit:   mSvg(<><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z" /></>),
  delete: mSvg(<><path d="M3 6h18" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /></>),
  dupe:   mSvg(<><rect x="9" y="9" width="11" height="11" rx="2" /><path d="M5 15V5a2 2 0 0 1 2-2h10" /></>),
  export: mSvg(<><path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" /></>),
  import: mSvg(<><path d="M12 15V3" /><path d="m7 8 5-5 5 5" /><path d="M5 21h14" /></>),
} as const

/** Actions delegated to the parent (CmsSidebar); 'edit'/'delete' are handled here. */
export type CmsTreeAction = 'new' | 'dupe' | 'export' | 'import'

export interface PageTreeProps {
  selectedId: number | null
  onSelect: (node: MelisTreeNode) => void
  onAction: (action: CmsTreeAction, node: MelisTreeNode) => void
}

/**
 * Native React page tree — reproduces the legacy "Site tree view".
 * Lazy-loads each node's children from the legacy endpoint; client-side search
 * filters the already-loaded nodes.
 */
export default function PageTree({ selectedId, onSelect, onAction }: PageTreeProps) {
  const [childrenByParent, setChildrenByParent] = useState<Record<number, MelisTreeNode[]>>({})
  const [expanded, setExpanded] = useState<Set<number>>(new Set())
  const [loading, setLoading] = useState<Set<number>>(new Set())
  const [rootLoading, setRootLoading] = useState(true)
  const [query, setQuery] = useState('')
  // Right-click context menu (legacy "Site tree view" actions).
  const [menu, setMenu] = useState<{ x: number; y: number; node: MelisTreeNode } | null>(null)
  const treeRef = useRef<HTMLDivElement>(null)

  const loadChildren = useCallback(async (parentId: number) => {
    setLoading((s) => new Set(s).add(parentId))
    const nodes = await fetchTreeNodes(parentId)
    setChildrenByParent((m) => ({ ...m, [parentId]: nodes }))
    setLoading((s) => { const n = new Set(s); n.delete(parentId); return n })
  }, [])

  const reload = useCallback(async () => {
    setChildrenByParent({})
    setExpanded(new Set())
    setRootLoading(true)
    await loadChildren(-1)
    setRootLoading(false)
  }, [loadChildren])

  useEffect(() => { reload() }, [reload])

  const toggle = useCallback(async (node: MelisTreeNode) => {
    const id = node.key
    const isOpen = expanded.has(id)
    if (!isOpen && childrenByParent[id] === undefined) await loadChildren(id)
    setExpanded((s) => { const n = new Set(s); isOpen ? n.delete(id) : n.add(id); return n })
  }, [expanded, childrenByParent, loadChildren])

  // Native contextmenu listener at the DOCUMENT level, CAPTURE phase. React's delegated
  // onContextMenu and a container-level native listener both failed to fire for this brick
  // subtree, so we bind at the earliest possible point (document capture — nothing can stop
  // the event before it) and resolve the row via a data-page-id attribute + the node cache.
  useEffect(() => {
    const onCtx = (e: MouseEvent) => {
      const row = (e.target as HTMLElement)?.closest?.('[data-page-id]') as HTMLElement | null
      if (!row) return
      const node = nodeCache.get(Number(row.dataset.pageId))
      if (!node) return
      e.preventDefault()
      setMenu({ x: e.clientX, y: e.clientY, node })
    }
    document.addEventListener('contextmenu', onCtx, true)
    return () => document.removeEventListener('contextmenu', onCtx, true)
  }, [])

  // Close the context menu on any outside click / scroll / Escape.
  // The listeners are attached on a macrotask (setTimeout 0) so the SAME event that opened the
  // menu — a capture-phase contextmenu that fires before its own bubble phase reaches window —
  // can't immediately trigger `close` (open-then-instant-close race).
  useEffect(() => {
    if (!menu) return
    const close = () => setMenu(null)
    const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') setMenu(null) }
    const id = window.setTimeout(() => {
      window.addEventListener('click', close)
      window.addEventListener('contextmenu', close)
      window.addEventListener('scroll', close, true)
      window.addEventListener('keydown', onKey)
    }, 0)
    return () => {
      window.clearTimeout(id)
      window.removeEventListener('click', close)
      window.removeEventListener('contextmenu', close)
      window.removeEventListener('scroll', close, true)
      window.removeEventListener('keydown', onKey)
    }
  }, [menu])

  // Delete a page (handled here because it must refresh the tree afterwards).
  const handleDelete = useCallback(async (node: MelisTreeNode) => {
    const ok = window.confirm(`Supprimer la page « ${node.melisData?.page_title || node.title} » ?`)
    if (!ok) return
    const res = await deletePage(node.key)
    if (res.success) {
      await reload()
    } else {
      window.alert(res.message || 'La page n\'a pas pu être supprimée (elle a peut-être des sous-pages).')
    }
  }, [reload])

  const runAction = useCallback((action: CmsTreeAction | 'edit' | 'delete', node: MelisTreeNode) => {
    setMenu(null)
    if (action === 'edit') onSelect(node)
    else if (action === 'delete') handleDelete(node)
    else onAction(action, node)
  }, [onSelect, onAction, handleDelete])

  // Does this node (or any loaded descendant) match the search query?
  const matchesDeep = useCallback((node: MelisTreeNode): boolean => {
    const q = query.trim().toLowerCase()
    if (!q) return true
    if (node.title.toLowerCase().includes(q)) return true
    return (childrenByParent[node.key] || []).some(matchesDeep)
  }, [query, childrenByParent])

  const searching = query.trim().length > 0

  const renderLevel = (parentId: number, depth: number) => {
    const items = childrenByParent[parentId] || []
    return items.filter(matchesDeep).map((node) => {
      const open = expanded.has(node.key) || (searching && !!childrenByParent[node.key])
      const offline = node.melisData?.page_is_online === 0
      const hasDraft = node.melisData?.page_has_saved_version === 1
      const selected = node.key === selectedId
      return (
        <div key={node.key}>
          <div
            data-page-id={node.key}
            onClick={() => onSelect(node)}
            onContextMenu={(e) => { e.preventDefault(); setMenu({ x: e.clientX, y: e.clientY, node }) }}
            title={node.title}
            style={{
              display: 'flex', alignItems: 'center', gap: 6,
              padding: '4px 8px', paddingLeft: 8 + depth * 16,
              cursor: 'pointer', borderRadius: 6, userSelect: 'none',
              color: offline ? 'var(--color-muted-foreground)' : 'var(--color-foreground)',
              background: selected ? 'color-mix(in srgb, var(--color-primary) 16%, transparent)' : 'transparent',
              fontSize: 13, lineHeight: '20px', whiteSpace: 'nowrap',
            }}
            onMouseEnter={(e) => { if (!selected) (e.currentTarget as HTMLElement).style.background = 'var(--color-accent, rgba(127,127,127,.12))' }}
            onMouseLeave={(e) => { if (!selected) (e.currentTarget as HTMLElement).style.background = 'transparent' }}
          >
            <span
              onClick={(e) => { e.stopPropagation(); if (node.lazy) toggle(node) }}
              style={{ width: 14, display: 'inline-flex', justifyContent: 'center', color: 'var(--color-muted-foreground)' }}
            >
              {node.lazy ? <Caret open={open} /> : null}
            </span>
            <span style={{ color: offline ? 'var(--color-muted-foreground)' : 'var(--color-primary)', display: 'inline-flex' }}>
              {nodeIcon(node.melisData?.page_type)}
            </span>
            {hasDraft && (
              <span title="Brouillon non publié" style={{ width: 6, height: 6, borderRadius: 999, background: '#f59e0b', flexShrink: 0 }} />
            )}
            <span style={{ flex: 1, overflow: 'hidden', textOverflow: 'ellipsis' }}>{node.title}</span>
            {/* Visible actions trigger (opens the same context menu) — works regardless of right-click. */}
            <button
              onClick={(e) => {
                e.stopPropagation()
                const r = (e.currentTarget as HTMLElement).getBoundingClientRect()
                setMenu({ x: r.right, y: r.bottom, node })
              }}
              title="Actions"
              style={{
                border: 'none', background: 'transparent', color: 'var(--color-muted-foreground)',
                cursor: 'pointer', padding: '0 4px', margin: 0, lineHeight: 1, fontSize: 16,
                flexShrink: 0, borderRadius: 4,
              }}
            >
              ⋯
            </button>
          </div>
          {open && childrenByParent[node.key] && renderLevel(node.key, depth + 1)}
          {open && loading.has(node.key) && (
            <div style={{ paddingLeft: 8 + (depth + 1) * 16, fontSize: 12, color: 'var(--color-muted-foreground)' }}>…</div>
          )}
        </div>
      )
    })
  }

  const roots = useMemo(() => childrenByParent[-1] || [], [childrenByParent])

  return (
    <div style={{ display: 'flex', flexDirection: 'column', minHeight: 0 }}>
      {/* Search + refresh */}
      <div style={{ display: 'flex', gap: 6, padding: 8, borderBottom: '1px solid var(--color-border)' }}>
        <input
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Rechercher…"
          style={{
            flex: 1, minWidth: 0, padding: '6px 10px', fontSize: 13, borderRadius: 6,
            border: '1px solid var(--color-border)', background: 'var(--color-background, transparent)',
            color: 'var(--color-foreground)', outline: 'none',
          }}
        />
        <button
          onClick={reload}
          title="Rafraîchir"
          style={{
            padding: '0 10px', borderRadius: 6, border: '1px solid var(--color-border)',
            background: 'transparent', color: 'var(--color-foreground)', cursor: 'pointer',
          }}
        >
          ↻
        </button>
      </div>

      {/* Tree */}
      <div ref={treeRef} style={{ flex: 1, minHeight: 0, overflow: 'auto', padding: 6 }}>
        {rootLoading ? (
          <div style={{ padding: 12, fontSize: 13, color: 'var(--color-muted-foreground)' }}>Chargement de l'arbre…</div>
        ) : roots.length === 0 ? (
          <div style={{ padding: 12, fontSize: 13, color: 'var(--color-muted-foreground)' }}>Aucune page.</div>
        ) : (
          renderLevel(-1, 0)
        )}
      </div>

      {/* Right-click context menu (portal to body so overflow/transform ancestors don't clip it). */}
      {menu && createPortal(
        <div
          onClick={(e) => e.stopPropagation()}
          onContextMenu={(e) => { e.preventDefault(); e.stopPropagation() }}
          style={{
            position: 'fixed',
            left: Math.min(menu.x, window.innerWidth - 210),
            top: Math.min(menu.y, window.innerHeight - 230),
            zIndex: 100000, minWidth: 190, padding: 4,
            background: 'var(--color-popover, var(--color-background, #fff))',
            color: 'var(--color-popover-foreground, var(--color-foreground, #111))',
            border: '1px solid var(--color-border)', borderRadius: 8,
            boxShadow: '0 10px 30px rgba(0,0,0,.18)', fontSize: 13,
          }}
        >
          {([
            { key: 'new',    label: 'Nouvelle page', icon: ICONS.new },
            { key: 'edit',   label: 'Éditer',        icon: ICONS.edit },
            { key: 'dupe',   label: 'Dupliquer',     icon: ICONS.dupe },
            { key: 'export', label: 'Exporter',      icon: ICONS.export },
            { key: 'import', label: 'Importer',      icon: ICONS.import },
            { key: 'sep' },
            { key: 'delete', label: 'Supprimer',     icon: ICONS.delete, danger: true },
          ] as Array<{ key: string; label?: string; icon?: React.ReactNode; danger?: boolean }>).map((it) =>
            it.key === 'sep' ? (
              <div key="sep" style={{ height: 1, margin: '4px 2px', background: 'var(--color-border)' }} />
            ) : (
              <div
                key={it.key}
                onClick={() => runAction(it.key as CmsTreeAction | 'edit' | 'delete', menu.node)}
                style={{
                  display: 'flex', alignItems: 'center', gap: 8,
                  padding: '7px 10px', borderRadius: 6, cursor: 'pointer',
                  color: it.danger ? 'var(--color-destructive, #c0392b)' : 'inherit',
                }}
                onMouseEnter={(e) => { (e.currentTarget as HTMLElement).style.background = 'var(--color-accent, rgba(127,127,127,.12))' }}
                onMouseLeave={(e) => { (e.currentTarget as HTMLElement).style.background = 'transparent' }}
              >
                <span style={{ display: 'inline-flex', color: it.danger ? 'inherit' : 'var(--color-muted-foreground)' }}>{it.icon}</span>
                {it.label}
              </div>
            ),
          )}
        </div>,
        document.body,
      )}
    </div>
  )
}
