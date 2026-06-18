import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'

/**
 * CMS content area (route /cms and /cms/:id) — loads the LEGACY Melis page editor in an
 * iframe (/melis/react-tool-page?key=meliscms_page&idPage=<id>).
 *
 * The :id param is a string so the tree's context menu can open more than a plain page edit:
 *   - "<number>"      → edit that page          (key=meliscms_page&idPage=<n>)
 *   - "new~<father>"  → create a child page     (key=meliscms_page_creation&idPage=0&idFatherPage=<father>)
 *
 * Iframe pool: every id opened in this session keeps its iframe mounted; switching tabs only
 * toggles visibility (display) instead of recreating the iframe — so the editor content is
 * preserved and not reloaded on every tab switch (same idea as the host's zone-frame pool).
 */
function toolSrc(id: string): string {
  if (id.startsWith('new~')) {
    const father = id.slice('new~'.length)
    return `/melis/react-tool-page?key=meliscms_page_creation&idPage=0&idFatherPage=${encodeURIComponent(father)}`
  }
  return `/melis/react-tool-page?key=meliscms_page&idPage=${encodeURIComponent(id)}`
}

export default function CmsPage() {
  const { id } = useParams()
  const current = id ?? null

  // Ids whose editor iframe is kept mounted.
  const [opened, setOpened] = useState<string[]>(() => (current ? [current] : []))

  useEffect(() => {
    if (current) setOpened((o) => (o.includes(current) ? o : [...o, current]))
  }, [current])

  // When a /cms/:id tab is closed, drop its iframe from the pool so reopening reloads it fresh
  // (not the kept-alive copy). The host dispatches melis:tab-closed with the closed path.
  useEffect(() => {
    const onClosed = (e: Event) => {
      const path = (e as CustomEvent<{ path?: string }>).detail?.path ?? ''
      const m = path.match(/^\/melis-cms\/page\/(.+)$/)
      if (m) {
        const cid = decodeURIComponent(m[1])
        setOpened((o) => o.filter((x) => x !== cid))
      }
    }
    window.addEventListener('melis:tab-closed', onClosed)
    return () => window.removeEventListener('melis:tab-closed', onClosed)
  }, [])

  return (
    <div style={{ position: 'relative', height: '100%', width: '100%', minHeight: 0 }}>
      {opened.map((cid) => (
        <iframe
          key={cid}
          src={toolSrc(cid)}
          title={`Page ${cid}`}
          // No sandbox: same-origin trusted Melis content, exactly like the legacy
          // back-office (which doesn't sandbox it either). The TinyMCE rich toolbar is
          // fixed server-side instead — the tool page (buildToolPage) now loads
          // melis_tinymce.js so window.parent.melisTinyMCE.tinyMceConfigs holds the config
          // that the nested edition iframe reads (see PlatformAssetsService).
          style={{
            position: 'absolute', inset: 0, width: '100%', height: '100%', border: 0,
            display: cid === current ? 'block' : 'none',
          }}
        />
      ))}
      {current == null && (
        <div style={{ padding: 24, color: 'var(--color-muted-foreground)', fontSize: 14 }}>
          Sélectionnez une page dans l'arbre.
        </div>
      )}
    </div>
  )
}
