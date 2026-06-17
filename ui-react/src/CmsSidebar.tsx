import { useState } from 'react'
import { createPortal } from 'react-dom'
import { useNavigate, useParams } from 'react-router-dom'
import PageTree, { type CmsTreeAction } from './PageTree'
import type { MelisTreeNode } from './cms-tree-api'

/**
 * Legacy modal actions (dupe/export/import) → rendered standalone via react-tool-page
 * (full platform env) inside a React overlay. Each is a real melisKey zone in
 * melis-cms/config/app.interface.php; the query param is what its forward reads.
 */
const MODALS: Record<Exclude<CmsTreeAction, 'new'>, { key: string; param: string; title: string }> = {
  dupe:   { key: 'meliscms_tools_tree_modal_form_handler', param: 'sourcePageId', title: 'Dupliquer la page' },
  export: { key: 'meliscms_page_export_modal',             param: 'pageId',       title: 'Exporter la page' },
  import: { key: 'meliscms_page_import_modal',             param: 'pageId',       title: 'Importer une page' },
}

type OpenTab = (t: { id: string; label: string; path: string }) => void

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
  const navigate = useNavigate()
  const { id } = useParams()
  // id may be "<number>" (edit) or "new~<father>" (create) — only a numeric id is "selected".
  const selectedId = id && /^\d+$/.test(id) ? Number(id) : null
  const [modal, setModal] = useState<{ src: string; title: string } | null>(null)

  const openTab = (path: string, label: string) => {
    ;(window as unknown as { __melisOpenTab?: OpenTab }).__melisOpenTab?.({ id: path, label, path })
    navigate(path)
  }

  const handleAction = (action: CmsTreeAction, node: MelisTreeNode) => {
    if (action === 'new') {
      // Create a child page under this node (idFatherPage = node.key).
      openTab(`/cms/new~${node.key}`, 'Nouvelle page')
      return
    }
    const m = MODALS[action]
    if (m) setModal({ src: `/melis/react-tool-page?key=${m.key}&${m.param}=${node.key}`, title: m.title })
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
      <div style={{ padding: '6px 10px', fontSize: 11, fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.05em', color: 'var(--color-muted-foreground)' }}>
        Arborescence des pages
      </div>
      <div style={{ minHeight: 0, overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
        <PageTree
          selectedId={selectedId}
          onSelect={(n) => {
            const path = `/cms/${n.key}`
            const label = n.melisData?.page_title || n.title
            openTab(path, label)
          }}
          onAction={handleAction}
        />
      </div>

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
              width: 'min(960px, 96vw)', height: 'min(80vh, 720px)',
              display: 'flex', flexDirection: 'column', overflow: 'hidden', borderRadius: 10,
              background: 'var(--color-background, #fff)', color: 'var(--color-foreground)',
              boxShadow: '0 20px 60px rgba(0,0,0,.35)',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '10px 14px', borderBottom: '1px solid var(--color-border)' }}>
              <span style={{ fontWeight: 600, fontSize: 14 }}>{modal.title}</span>
              <button
                onClick={() => setModal(null)}
                title="Fermer"
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
    </div>
  )
}
