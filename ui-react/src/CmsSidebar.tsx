import { useState } from 'react'
import { createPortal } from 'react-dom'
import { useNavigate, useParams } from 'react-router-dom'
import PageTree, { type CmsTreeAction } from './PageTree'
import DuplicatePageModal from './DuplicatePageModal'
import { peT } from './page-editor-i18n'
import type { MelisTreeNode } from './cms-tree-api'

/**
 * Legacy modal actions (export/import) → rendered standalone via react-tool-page
 * (full platform env) inside a React overlay. Each is a real melisKey zone in
 * melis-cms/config/app.interface.php; the query param is what its forward reads.
 * `dupe` is now a NATIVE React modal (DuplicatePageModal) — no iframe.
 */
const MODALS: Record<'export' | 'import', { key: string; param: string }> = {
  export: { key: 'meliscms_page_export_modal',             param: 'pageId' },
  import: { key: 'meliscms_page_import_modal',             param: 'pageId' },
}

type OpenTab = (t: { id: string; label: string; path: string }) => void

// Pli/dépli de l'arbre (ticket 0010822 : "expand/retract the site tree view", façon legacy).
// Persisté en sessionStorage → l'état survit à un reload / réouverture de l'outil.
const TREE_OPEN_KEY = 'melis-cms-tree-open'
function getTreeOpen(): boolean {
  try { const v = sessionStorage.getItem(TREE_OPEN_KEY); return v === null ? true : v === '1' } catch { return true }
}
function setTreeOpenStore(v: boolean): void {
  try { sessionStorage.setItem(TREE_OPEN_KEY, v ? '1' : '0') } catch { /* ignore */ }
}

/**
 * CMS sidebar panel — the page tree, rendered INSIDE the left navigation under the
 * MelisCms section (reproduces the legacy "Site tree view"). Left-click opens a page in a
 * tab (/cms/:id); right-click opens the context menu (new / edit / dupe / export / import /
 * delete) — the same actions as the legacy fancytree contextMenu.
 *
 * Height is dynamic: the panel grows with the loaded tree, capped at a max with internal
 * scroll — so the Site Tools below it stay right under the tree.
 */
export default function CmsSidebar() {
  const tr = peT()
  const navigate = useNavigate()
  const { id } = useParams()
  // id may be "<number>" (edit) or "new~<father>" (create) — only a numeric id is "selected".
  const selectedId = id && /^\d+$/.test(id) ? Number(id) : null
  const [modal, setModal] = useState<{ src: string; title: string } | null>(null)
  // Native "Duplicate tree" modal (replaces the legacy iframe tool) — holds the source page.
  const [dupNode, setDupNode] = useState<MelisTreeNode | null>(null)
  // Arbre déplié/replié (persisté).
  const [open, setOpen] = useState(getTreeOpen)

  const openTab = (path: string, label: string) => {
    ;(window as unknown as { __melisOpenTab?: OpenTab }).__melisOpenTab?.({ id: path, label, path })
    navigate(path)
  }

  const handleAction = (action: CmsTreeAction, node: MelisTreeNode) => {
    if (action === 'new') {
      // Create a child page under this node (idFatherPage = node.key).
      openTab(`/melis-cms/page/new~${node.key}`, tr.newPage)
      return
    }
    if (action === 'dupe') {
      // Native React modal (no iframe): duplicate the whole page tree from this node.
      setDupNode(node)
      return
    }
    const m = MODALS[action]
    if (m) setModal({ src: `/melis/react-tool-page?key=${m.key}&${m.param}=${node.key}`, title: action === 'export' ? tr.exportPage : tr.importPage })
  }

  return (
    <div
      style={{
        display: 'flex', flexDirection: 'column',
        maxHeight: '60vh',
        margin: '2px 0 6px',
        border: '1px solid var(--color-border)', borderRadius: 8, overflow: 'hidden',
        background: 'color-mix(in srgb, var(--color-foreground) 4%, transparent)',
      }}
    >
      <button
        type="button"
        onClick={() => setOpen((v) => { const next = !v; setTreeOpenStore(next); return next })}
        title={tr.pageTree}
        style={{
          display: 'flex', alignItems: 'center', gap: 6, width: '100%', textAlign: 'left',
          border: 0, background: 'transparent', cursor: 'pointer',
          padding: '6px 10px', fontSize: 11, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.05em', color: 'var(--color-muted-foreground)',
        }}
      >
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"
          style={{ flexShrink: 0, transition: 'transform .15s', transform: open ? 'rotate(90deg)' : 'none' }}>
          <path d="m9 18 6-6-6-6" />
        </svg>
        <span style={{ flex: 1 }}>{tr.pageTree}</span>
      </button>
      {open && (
        <div style={{ minHeight: 0, overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
          <PageTree
            selectedId={selectedId}
            onSelect={(n) => {
              const path = `/melis-cms/page/${n.key}`
              const label = n.melisData?.page_title || n.title
              openTab(path, label)
            }}
            onAction={handleAction}
          />
        </div>
      )}

      {/* Modal overlay (dupe / export / import) — legacy tool rendered in an iframe. */}
      {modal && createPortal(
        <div
          onClick={() => setModal(null)}
          style={{
            position: 'fixed', inset: 0, zIndex: 99999, padding: 24,
            background: 'rgba(0,0,0,.45)', display: 'flex', alignItems: 'center', justifyContent: 'center',
          }}
        >
          <div
            onClick={(e) => e.stopPropagation()}
            style={{
              width: 'min(960px, calc(100vw - 48px))', height: 'min(720px, calc(100vh - 48px))',
              display: 'flex', flexDirection: 'column', overflow: 'hidden', borderRadius: 10,
              background: 'var(--color-background, #fff)', color: 'var(--color-foreground)',
              boxShadow: '0 20px 60px rgba(0,0,0,.35)',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '10px 14px', borderBottom: '1px solid var(--color-border)' }}>
              <span style={{ fontWeight: 600, fontSize: 14 }}>{modal.title}</span>
              <button
                onClick={() => setModal(null)}
                title={tr.close}
                style={{ border: 'none', background: 'transparent', color: 'var(--color-muted-foreground)', cursor: 'pointer', fontSize: 18, lineHeight: 1, padding: 4 }}
              >
                ✕
              </button>
            </div>
            <iframe src={modal.src} title={modal.title} style={{ flex: 1, width: '100%', border: 0 }} />
          </div>
        </div>,
        document.body,
      )}

      {/* Native "Duplicate tree" modal (replaces the legacy iframe tool). */}
      {dupNode && (
        <DuplicatePageModal
          sourcePageId={dupNode.key}
          sourceTitle={dupNode.title}
          onClose={() => setDupNode(null)}
          onDone={() => setDupNode(null)}
        />
      )}
    </div>
  )
}
