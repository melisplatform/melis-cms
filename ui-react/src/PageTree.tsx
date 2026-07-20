import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { deletePage, fetchTreeNodes, movePage, nodeCache, searchTreePages, type MelisTreeNode } from './cms-tree-api'
import { peT } from './page-editor-i18n'

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
function LockIcon() {
  return (
    <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="4" y="11" width="16" height="9" rx="2" /><path d="M8 11V7a4 4 0 0 1 8 0v4" />
    </svg>
  )
}
function UnlockIcon() {
  return (
    <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="4" y="11" width="16" height="9" rx="2" /><path d="M8 11V7a4 4 0 0 1 7.9-1" />
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

/** Wrap every (case-insensitive) occurrence of `q` in `text` with a highlight mark. */
function highlight(text: string, q: string): React.ReactNode {
  const query = q.trim()
  if (!query) return text
  const lower = text.toLowerCase()
  const ql = query.toLowerCase()
  const parts: React.ReactNode[] = []
  let i = 0
  let k = 0
  let idx = lower.indexOf(ql, i)
  while (idx !== -1) {
    if (idx > i) parts.push(text.slice(i, idx))
    parts.push(
      <mark key={k++} style={{ background: 'var(--color-primary)', color: 'var(--color-primary-foreground, #fff)', borderRadius: 3, padding: '0 2px' }}>
        {text.slice(idx, idx + query.length)}
      </mark>,
    )
    i = idx + query.length
    idx = lower.indexOf(ql, i)
  }
  if (i < text.length) parts.push(text.slice(i))
  return parts
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
 * Lazy-loads each node's children from the legacy endpoint; search runs a REAL
 * backend call (POST searchTreePages) so it finds pages not yet loaded, loads +
 * expands the path to every match, filters to the matching branches and highlights
 * the matched word in the page name — like the legacy tree.
 */
export default function PageTree({ selectedId, onSelect, onAction }: PageTreeProps) {
  const tr = peT() // dictionnaire i18n partagé (référence stable → sûr hors deps des useCallback)
  const [childrenByParent, setChildrenByParent] = useState<Record<number, MelisTreeNode[]>>({})
  const [expanded, setExpanded] = useState<Set<number>>(new Set())
  const [loading, setLoading] = useState<Set<number>>(new Set())
  const [rootLoading, setRootLoading] = useState(true)
  const [query, setQuery] = useState('')
  // Server-driven search state: whether a search is active and whether it found nothing.
  // The matched paths are revealed by loading + EXPANDING their ancestors (the whole tree
  // stays visible — we don't filter it), and matched words are highlighted on render.
  const [search, setSearch] = useState<{ active: boolean; notFound: boolean }>({ active: false, notFound: false })
  // Mirror of childrenByParent for the search effect (avoids re-running it on every load).
  const childrenRef = useRef(childrenByParent)
  childrenRef.current = childrenByParent
  // Right-click context menu (legacy "Site tree view" actions).
  const [menu, setMenu] = useState<{ x: number; y: number; node: MelisTreeNode } | null>(null)
  // Modal React de confirmation de suppression (remplace window.confirm/alert natif).
  const [delNode, setDelNode] = useState<MelisTreeNode | null>(null)
  const [deleting, setDeleting] = useState(false)
  const [delError, setDelError] = useState<string | null>(null)
  // Drag-to-reorder is LOCKED by default (an unlock icon enables it), like the legacy tree —
  // so pages aren't moved by accident. `drag` holds the in-flight drag (source + hovered drop).
  const [unlocked, setUnlocked] = useState(false)
  const [drag, setDrag] = useState<{ id: number; over: number; mode: 'before' | 'after' | 'over' } | null>(null)
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

  // Ref stable de la page sélectionnée (pour les listeners d'événements sans re-souscription).
  const selectedIdRef = useRef(selectedId)
  selectedIdRef.current = selectedId

  // Déploie l'arbre jusqu'à `pageId` : récupère la chaîne d'ancêtres (racine → parent) puis charge
  // leurs enfants et les ouvre → la page en cours redevient visible après un reload (qui referme tout).
  const revealPath = useCallback(async (pageId: number | null) => {
    if (!pageId || pageId <= 0) return
    let chain: number[] = []
    try {
      const res = await fetch(`/melis/react-api/cms-page/ancestors?idPage=${pageId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'include' })
      chain = (await res.json())?.data?.ancestors ?? []
    } catch { return }
    if (!chain.length) return
    const loaded = await Promise.all(chain.map(async (id) => [id, await fetchTreeNodes(id)] as const))
    setChildrenByParent((m) => { const n = { ...m }; loaded.forEach(([id, nodes]) => { n[id] = nodes }); return n })
    setExpanded((prev) => { const n = new Set(prev); chain.forEach((id) => n.add(id)); return n })
  }, [])

  // Refresh button: clear the search box (query + active-search state), then reload the tree.
  // Clearing the query also lets the search effect restore the normal (unfiltered) view.
  const clearAndReload = useCallback(async () => {
    setQuery('')
    setSearch((s) => (s.active ? { active: false, notFound: false } : s))
    await reload()
  }, [reload])

  useEffect(() => { reload() }, [reload])

  // Search: debounced REAL backend call. Empty query → restore the normal tree.
  useEffect(() => {
    const q = query.trim()
    if (!q) {
      setSearch((s) => (s.active ? { active: false, notFound: false } : s))
      return
    }
    let cancelled = false
    const t = window.setTimeout(async () => {
      const chains = await searchTreePages(q)
      if (cancelled) return
      if (!chains.length) {
        setSearch({ active: true, notFound: true })
        return
      }
      // Ancestors of every match: load their children (so the branch exists) and expand them,
      // opening the FULL tree down to each matched page. The rest of the tree stays as-is.
      const toLoad = new Set<number>()
      chains.forEach((chain) => chain.forEach((id, i) => { if (i < chain.length - 1) toLoad.add(id) }))
      const missing = [...toLoad].filter((id) => childrenRef.current[id] === undefined)
      if (missing.length) {
        const loaded = await Promise.all(missing.map(async (id) => [id, await fetchTreeNodes(id)] as const))
        if (cancelled) return
        setChildrenByParent((m) => {
          const n = { ...m }
          loaded.forEach(([id, nodes]) => { n[id] = nodes })
          return n
        })
      }
      setExpanded((prev) => { const n = new Set(prev); toLoad.forEach((id) => n.add(id)); return n })
      setSearch({ active: true, notFound: false })
    }, 350)
    return () => { cancelled = true; window.clearTimeout(t) }
  }, [query])

  // After a page is created (CmsPage dispatches melis:cms-page-created), refresh the tree and
  // expand the father so the new page is visible.
  useEffect(() => {
    const onCreated = (e: Event) => {
      const father = Number((e as CustomEvent<{ father?: string }>).detail?.father)
      void (async () => {
        await reload()
        if (father) {
          await loadChildren(father)
          setExpanded((s) => new Set(s).add(father))
        }
      })()
    }
    window.addEventListener('melis:cms-page-created', onCreated)
    return () => window.removeEventListener('melis:cms-page-created', onCreated)
  }, [reload, loadChildren])

  // Une action NATIVE de la coquille React (ex. déverrouillage) émet melis:cms-tree-refresh pour
  // que l'arbre se recharge (le cadenas d'une page débloquée doit disparaître) — pas de message iframe.
  useEffect(() => {
    const onRefresh = (e: Event) => {
      const rid = (e as CustomEvent<{ revealPageId?: number }>).detail?.revealPageId ?? selectedIdRef.current
      void (async () => { await reload(); await revealPath(rid ?? null) })() // recharge PUIS re-déploie jusqu'à la page en cours
    }
    window.addEventListener('melis:cms-tree-refresh', onRefresh)
    return () => window.removeEventListener('melis:cms-tree-refresh', onRefresh)
  }, [reload, revealPath])

  // Reload the tree after a page mutation done in a tool iframe (publish / unpublish / delete /
  // duplicate / page-lock unlock). buildToolPage forwards every tool response as
  // {__melisToolResult, url, data}. Unlocking a page removes its lock row → the tree must
  // refresh so the lock icon (see cms-tree-api `locked`) disappears.
  useEffect(() => {
    const onResult = (e: MessageEvent) => {
      const d = e.data as { __melisToolResult?: boolean; url?: string; data?: { success?: number | boolean } } | null
      if (!d || !d.__melisToolResult) return
      const ok = d.data?.success === 1 || d.data?.success === true
      if (!ok) return
      const url = d.url || ''
      if (/\/Page\/(publishPage|unpublishPage|deletePage)\b/.test(url) || /duplicate-page|duplicateTreePage/i.test(url) || /\/PageLock\/unlockPage\b/.test(url)) {
        const isDelete = /\/Page\/deletePage\b/.test(url)
        void (async () => { await reload(); if (!isDelete) await revealPath(selectedIdRef.current) })() // re-déploie jusqu'à la page (sauf suppression)
      }
    }
    window.addEventListener('message', onResult)
    return () => window.removeEventListener('message', onResult)
  }, [reload, revealPath])

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

  // Delete a page → ouvre la modal React de confirmation (plus de window.confirm natif).
  const handleDelete = useCallback((node: MelisTreeNode) => {
    setDelError(null); setDelNode(node)
  }, [])
  // Confirmation EFFECTIVE (bouton « Supprimer » de la modal) : supprime puis rafraîchit l'arbre.
  const confirmDelete = useCallback(async () => {
    if (!delNode || deleting) return
    setDeleting(true); setDelError(null)
    const res = await deletePage(delNode.key)
    setDeleting(false)
    if (res.success) {
      setDelNode(null)
      await reload()
    } else {
      setDelError(res.message || tr.deleteTreeFailed)
    }
  }, [delNode, deleting, reload, tr])

  const runAction = useCallback((action: CmsTreeAction | 'edit' | 'delete', node: MelisTreeNode) => {
    setMenu(null)
    if (action === 'edit') onSelect(node)
    else if (action === 'delete') handleDelete(node)
    else onAction(action, node)
  }, [onSelect, onAction, handleDelete])

  // Échap ferme la modal de suppression (sauf pendant la suppression en cours).
  useEffect(() => {
    if (!delNode) return
    const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape' && !deleting) setDelNode(null) }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [delNode, deleting])

  /* ── Drag-and-drop reorder (legacy /Page/movePage) ─────────────────────────── */

  // child id → parent id (-1 for roots), derived from the loaded tree.
  const parentOf = useMemo(() => {
    const m = new Map<number, number>()
    for (const [pid, kids] of Object.entries(childrenByParent)) {
      const p = Number(pid)
      kids.forEach((k) => m.set(k.key, p))
    }
    return m
  }, [childrenByParent])

  // Is `nodeId` inside the subtree of `ancestorId`? (can't drop a page into its own descendant)
  const isDescendantOf = useCallback((nodeId: number, ancestorId: number): boolean => {
    let cur = parentOf.get(nodeId)
    while (cur !== undefined && cur !== -1) {
      if (cur === ancestorId) return true
      cur = parentOf.get(cur)
    }
    return false
  }, [parentOf])

  // Re-fetch the children of the given parents (-1 = root level) after a move, keeping expansion.
  const refreshParents = useCallback(async (parents: number[]) => {
    const uniq = [...new Set(parents)]
    const loaded = await Promise.all(uniq.map(async (id) => [id, await fetchTreeNodes(id)] as const))
    setChildrenByParent((m) => { const n = { ...m }; loaded.forEach(([id, nodes]) => { n[id] = nodes }); return n })
  }, [])

  const performMove = useCallback(async (sourceId: number, targetId: number, mode: 'before' | 'after' | 'over') => {
    if (sourceId === targetId || isDescendantOf(targetId, sourceId)) return
    const oldFather = parentOf.get(sourceId) ?? -1
    let newFather: number
    let newPosition: number
    if (mode === 'over') {
      newFather = targetId
      let kids = childrenByParent[targetId]
      if (kids === undefined) kids = await fetchTreeNodes(targetId)
      newPosition = kids.filter((n) => n.key !== sourceId).length + 1 // append at end
    } else {
      newFather = parentOf.get(targetId) ?? -1
      const siblings = (childrenByParent[newFather] || []).filter((n) => n.key !== sourceId)
      const idx = siblings.findIndex((n) => n.key === targetId)
      if (idx === -1) return
      newPosition = (mode === 'before' ? idx : idx + 1) + 1 // 1-based insert position
    }
    const res = await movePage({ idPage: sourceId, oldFatherIdPage: oldFather, newFatherIdPage: newFather, newPositionIdPage: newPosition })
    if (!res.success) { window.alert(res.message || 'Le déplacement a échoué.'); return }
    await refreshParents([oldFather, newFather])
    if (newFather !== -1) setExpanded((s) => new Set(s).add(newFather))
  }, [childrenByParent, parentOf, isDescendantOf, refreshParents])

  const searching = search.active

  const renderLevel = (parentId: number, depth: number) => {
    const items = childrenByParent[parentId] || []
    // The whole tree stays visible during a search; paths to matches were auto-expanded
    // (their ancestors added to `expanded`), so the same open rule covers both modes.
    return items.map((node) => {
      const open = expanded.has(node.key)
      const offline = node.melisData?.page_is_online === 0
      const hasDraft = node.melisData?.page_has_saved_version === 1
      const selected = node.key === selectedId
      const draggable = unlocked && !!node.dragdrop
      const dropHere = drag && drag.over === node.key && drag.id !== node.key && !isDescendantOf(node.key, drag.id)
      const dropMode = dropHere ? drag!.mode : null
      const dropShadow =
        dropMode === 'before' ? 'inset 0 2px 0 0 var(--color-primary)' :
        dropMode === 'after' ? 'inset 0 -2px 0 0 var(--color-primary)' : 'none'
      return (
        <div key={node.key}>
          <div
            data-page-id={node.key}
            draggable={draggable}
            onClick={() => onSelect(node)}
            onContextMenu={(e) => { e.preventDefault(); setMenu({ x: e.clientX, y: e.clientY, node }) }}
            onDragStart={draggable ? (e) => {
              e.stopPropagation()
              setDrag({ id: node.key, over: node.key, mode: 'over' })
              e.dataTransfer.effectAllowed = 'move'
              try { e.dataTransfer.setData('text/plain', String(node.key)) } catch { /* IE guard */ }
            } : undefined}
            onDragOver={drag ? (e) => {
              if (node.key === drag.id || isDescendantOf(node.key, drag.id)) { e.dataTransfer.dropEffect = 'none'; return }
              e.preventDefault()
              e.dataTransfer.dropEffect = 'move'
              const r = (e.currentTarget as HTMLElement).getBoundingClientRect()
              const y = e.clientY - r.top
              const mode: 'before' | 'after' | 'over' = y < r.height * 0.3 ? 'before' : y > r.height * 0.7 ? 'after' : 'over'
              if (drag.over !== node.key || drag.mode !== mode) setDrag({ id: drag.id, over: node.key, mode })
            } : undefined}
            onDrop={drag ? (e) => {
              e.preventDefault(); e.stopPropagation()
              const d = drag
              setDrag(null)
              if (d && node.key !== d.id && !isDescendantOf(node.key, d.id)) void performMove(d.id, node.key, d.mode)
            } : undefined}
            onDragEnd={() => setDrag(null)}
            title={node.title}
            style={{
              display: 'flex', alignItems: 'center', gap: 6,
              padding: '4px 8px', paddingLeft: 8 + depth * 16,
              cursor: draggable ? 'grab' : 'pointer', borderRadius: 6, userSelect: 'none',
              color: offline ? 'var(--color-muted-foreground)' : 'var(--color-foreground)',
              background: dropMode === 'over' ? 'color-mix(in srgb, var(--color-primary) 24%, transparent)'
                : selected ? 'color-mix(in srgb, var(--color-primary) 16%, transparent)' : 'transparent',
              boxShadow: dropShadow,
              fontSize: 13, lineHeight: '20px', whiteSpace: 'nowrap',
            }}
            onMouseEnter={(e) => { if (!selected && !drag) (e.currentTarget as HTMLElement).style.background = 'var(--color-accent, rgba(127,127,127,.12))' }}
            onMouseLeave={(e) => { if (!selected && !drag) (e.currentTarget as HTMLElement).style.background = 'transparent' }}
          >
            <span
              onClick={(e) => { e.stopPropagation(); if (node.lazy) toggle(node) }}
              style={{
                width: 24, height: 24, marginLeft: -4, flexShrink: 0, borderRadius: 4,
                display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
                color: 'var(--color-muted-foreground)', cursor: node.lazy ? 'pointer' : 'default',
              }}
              onMouseEnter={node.lazy ? (e) => { (e.currentTarget as HTMLElement).style.background = 'var(--color-accent, rgba(127,127,127,.18))' } : undefined}
              onMouseLeave={node.lazy ? (e) => { (e.currentTarget as HTMLElement).style.background = 'transparent' } : undefined}
            >
              {node.lazy ? <Caret open={open} /> : null}
            </span>
            <span style={{ color: offline ? 'var(--color-muted-foreground)' : 'var(--color-primary)', display: 'inline-flex' }}>
              {nodeIcon(node.melisData?.page_type)}
            </span>
            {hasDraft && (
              <span title="Brouillon non publié" style={{ width: 6, height: 6, borderRadius: 999, background: '#f59e0b', flexShrink: 0 }} />
            )}
            {node.locked && (
              <span title="Page verrouillée (en cours d'édition par un autre utilisateur)"
                style={{ display: 'inline-flex', flexShrink: 0, color: 'var(--color-muted-foreground)' }}>
                <LockIcon />
              </span>
            )}
            <span style={{ flex: 1, overflow: 'hidden', textOverflow: 'ellipsis' }}>
              {searching ? highlight(node.title, query) : node.title}
            </span>
            {/* No actions button — right-click opens the context menu. */}
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
          onClick={clearAndReload}
          title="Rafraîchir"
          style={{
            padding: '0 10px', borderRadius: 6, border: '1px solid var(--color-border)',
            background: 'transparent', color: 'var(--color-foreground)', cursor: 'pointer',
          }}
        >
          ↻
        </button>
        <button
          onClick={() => setUnlocked((u) => !u)}
          title={unlocked ? 'Verrouiller le glisser-déposer (réorganisation)' : 'Déverrouiller le glisser-déposer (réorganisation)'}
          aria-pressed={unlocked}
          style={{
            display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
            padding: '0 10px', borderRadius: 6, border: '1px solid var(--color-border)',
            background: unlocked ? 'color-mix(in srgb, var(--color-primary) 16%, transparent)' : 'transparent',
            color: unlocked ? 'var(--color-primary)' : 'var(--color-foreground)', cursor: 'pointer',
          }}
        >
          {unlocked ? <UnlockIcon /> : <LockIcon />}
        </button>
      </div>

      {/* Tree */}
      <div ref={treeRef} style={{ flex: 1, minHeight: 0, overflow: 'auto', padding: 6 }}>
        {searching && search.notFound && (
          <div style={{ padding: '6px 8px', marginBottom: 4, fontSize: 12, color: 'var(--color-muted-foreground)' }}>
            Aucune page ne correspond à « {query.trim()} ».
          </div>
        )}
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
            // Masqués temporairement (peu utilisés en réel) — code conservé pour réactivation future.
            // L'action runAction('export'/'import') et ICONS.export/import restent en place.
            // { key: 'export', label: 'Exporter',      icon: ICONS.export },
            // { key: 'import', label: 'Importer',      icon: ICONS.import },
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

      {/* Modal React de confirmation de SUPPRESSION (remplace window.confirm/alert natif). */}
      {delNode && createPortal(
        <div
          onClick={(e) => { if (e.target === e.currentTarget && !deleting) setDelNode(null) }}
          style={{ position: 'fixed', inset: 0, zIndex: 100001, padding: 24, background: 'rgba(15,18,25,.55)', backdropFilter: 'blur(2px)', display: 'flex', alignItems: 'flex-start', justifyContent: 'center', overflow: 'auto' }}
        >
          <div style={{ width: 'min(440px, 96vw)', marginTop: '12vh', border: '1px solid var(--color-border)', background: 'var(--color-card,var(--color-background,#fff))', color: 'var(--color-foreground)', borderRadius: 14, boxShadow: '0 24px 70px rgba(0,0,0,.4)', overflow: 'hidden' }}>
            {/* Header */}
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '16px 18px', borderBottom: '1px solid var(--color-border)' }}>
              <span style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 34, height: 34, borderRadius: 9, flexShrink: 0, background: 'color-mix(in srgb, var(--color-destructive,#c0392b) 14%, transparent)', color: 'var(--color-destructive,#c0392b)' }}>
                <svg width={18} height={18} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M3 6h18" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6M14 11v6" />
                </svg>
              </span>
              <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ fontWeight: 600, fontSize: 15 }}>{tr.deleteTitle}</div>
                <div style={{ fontSize: 12, color: 'var(--color-muted-foreground)', marginTop: 1 }}>{tr.deleteIrreversible}</div>
              </div>
              <button onClick={() => { if (!deleting) setDelNode(null) }} title={tr.cancel} disabled={deleting} style={{ border: 'none', background: 'transparent', color: 'var(--color-muted-foreground)', cursor: deleting ? 'not-allowed' : 'pointer', fontSize: 18, lineHeight: 1, padding: 4 }}>✕</button>
            </div>
            {/* Body */}
            <div style={{ padding: 18, display: 'flex', flexDirection: 'column', gap: 12, fontSize: 13.5 }}>
              <div>{tr.deleteTreeBodyPre} <strong>« {delNode.melisData?.page_title || delNode.title} »</strong> ?</div>
              {delError && (
                <div style={{ padding: '8px 12px', borderRadius: 9, fontSize: 12.5, background: 'color-mix(in srgb, var(--color-destructive,#c0392b) 12%, transparent)', color: 'var(--color-destructive,#c0392b)', border: '1px solid color-mix(in srgb, var(--color-destructive,#c0392b) 40%, transparent)' }}>{delError}</div>
              )}
            </div>
            {/* Footer */}
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 9, padding: '14px 18px', borderTop: '1px solid var(--color-border)', background: 'color-mix(in srgb, var(--color-foreground) 3%, transparent)' }}>
              <button onClick={() => { if (!deleting) setDelNode(null) }} disabled={deleting} style={{ display: 'inline-flex', alignItems: 'center', gap: 6, height: 36, padding: '0 14px', borderRadius: 9, border: '1px solid var(--color-border)', background: 'var(--color-card,transparent)', color: 'var(--color-foreground)', fontSize: 13.5, fontWeight: 500, cursor: deleting ? 'not-allowed' : 'pointer' }}>{tr.cancel}</button>
              <button onClick={confirmDelete} disabled={deleting} style={{ display: 'inline-flex', alignItems: 'center', gap: 7, height: 36, padding: '0 16px', borderRadius: 9, border: 0, background: 'var(--color-destructive,#c0392b)', color: '#fff', fontSize: 13.5, fontWeight: 600, cursor: deleting ? 'not-allowed' : 'pointer', opacity: deleting ? 0.65 : 1 }}>{deleting ? tr.deleting : tr.deleteBtn}</button>
            </div>
          </div>
        </div>,
        document.body,
      )}
    </div>
  )
}
