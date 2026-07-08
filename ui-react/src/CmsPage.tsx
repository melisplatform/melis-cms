import { useEffect, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'

/**
 * CMS content area (route /melis-cms/page and /melis-cms/page/:id) — loads the LEGACY Melis page
 * editor in an iframe (/melis/react-tool-page?key=meliscms_page&idPage=<id>).
 *
 * The :id param is a string so it can open more than a plain page edit:
 *   - "<number>"      → edit that page          (key=meliscms_page&idPage=<n>)
 *   - "new"           → create a page           (key=meliscms_page_creation&idPage=0)
 *   - "new~<father>"  → create a child page     (key=meliscms_page_creation&idPage=0&idFatherPage=<father>)
 *
 * Iframe pool: every id opened in this session keeps its iframe mounted; switching tabs only
 * toggles visibility (display) instead of recreating the iframe — so the editor content is
 * preserved and not reloaded on every tab switch (same idea as the host's zone-frame pool).
 */
const NEW_PAGE_ROUTE = '/melis-cms/page/new'

function toolSrc(id: string): string {
  if (id === 'new' || id.startsWith('new~')) {
    const father = id.startsWith('new~') ? id.slice('new~'.length) : '0'
    return `/melis/react-tool-page?key=meliscms_page_creation&idPage=0&idFatherPage=${encodeURIComponent(father)}`
  }
  return `/melis/react-tool-page?key=meliscms_page&idPage=${encodeURIComponent(id)}`
}

export default function CmsPage({ active = true }: { active?: boolean }) {
  const { id } = useParams()
  const navigate = useNavigate()
  // Persistante (manifest) : geler l'id quand inactive, sinon un :id étranger entrerait dans le pool
  // d'iframes `opened` et tenterait d'afficher l'éditeur d'un autre outil. Cf. skill.
  const [frozenId, setFrozenId] = useState<string | undefined>(id)
  useEffect(() => { if (active) setFrozenId(id) }, [active, id])
  const current = (active ? id : frozenId) ?? null

  // Ids whose editor iframe is kept mounted.
  const [opened, setOpened] = useState<string[]>(() => (current ? [current] : []))

  useEffect(() => {
    if (current) setOpened((o) => (o.includes(current) ? o : [...o, current]))
  }, [current])

  // When a /melis-cms/page/:id tab is closed, drop its iframe from the pool so reopening reloads it
  // fresh (not the kept-alive copy). The host dispatches melis:tab-closed with the closed path.
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

  // After a NEW page is saved, the editor iframe answers Page/savePage. buildToolPage forwards it
  // to the host as {__melisToolResult, url, data}. On a creation success: open the new page in edit
  // (top tab), close the "new~…" creation tab, and tell the tree to refresh + reveal the page.
  const openedRef = useRef(opened)
  openedRef.current = opened
  useEffect(() => {
    const onResult = (e: MessageEvent) => {
      const d = e.data as { __melisToolResult?: boolean; url?: string; data?: { success?: number; datas?: { idPage?: number | string; item_zoneid?: string; item_name?: string } } } | null
      if (!d || !d.__melisToolResult) return
      const data = d.data
      if ((d.url || '').indexOf('/Page/savePage') === -1 || !data || data.success !== 1) return
      if (data.datas?.item_zoneid !== '0_id_meliscms_page') return // creation only
      const newId = data.datas?.idPage
      if (!newId) return
      const newTabId = openedRef.current.find((x) => x === 'new' || x.startsWith('new~')) ?? 'new'
      const father = newTabId.startsWith('new~') ? newTabId.slice('new~'.length) : ''
      const editPath = `/melis-cms/page/${newId}`
      const w = window as unknown as {
        __melisOpenTab?: (t: { id: string; label: string; path: string }) => void
        __melisCloseTab?: (id: string) => void
      }
      w.__melisOpenTab?.({ id: editPath, label: (data.datas?.item_name || `Page ${newId}`).trim(), path: editPath })
      navigate(editPath)
      w.__melisCloseTab?.(`/melis-cms/page/${newTabId}`)
      setOpened((o) => o.filter((x) => x !== newTabId))
      window.dispatchEvent(new CustomEvent('melis:cms-page-created', { detail: { idPage: newId, father } }))
    }
    window.addEventListener('message', onResult)
    return () => window.removeEventListener('message', onResult)
  }, [navigate])

  // The legacy "Nouvelle page" toolbar button (.melis-newpage) opens an in-iframe creation tab.
  // In the React BO we want it to open a NEW top tab at /melis-cms/page/new instead. The editor
  // iframe is same-origin, so on load we capture-intercept its clicks and route to the host
  // (preventing the legacy handler). Capture phase + stopImmediatePropagation beats the legacy
  // $body-delegated bubble handler.
  function hookNewPage(iframe: HTMLIFrameElement) {
    try {
      const doc = iframe.contentDocument as (Document & { __melisNewPageHooked?: boolean }) | null
      if (!doc || doc.__melisNewPageHooked) return
      doc.__melisNewPageHooked = true
      doc.addEventListener(
        'click',
        (ev) => {
          const btn = (ev.target as HTMLElement)?.closest?.('.melis-newpage') as HTMLElement | null
          if (!btn) return
          ev.preventDefault()
          ev.stopImmediatePropagation()
          // The new page is created as a CHILD of the current page (legacy behaviour) — the button
          // carries it in data-pagenumber. A father is required: idFatherPage=0 makes the save 500.
          const father = btn.getAttribute('data-pagenumber') || ''
          const path = father ? `${NEW_PAGE_ROUTE}~${father}` : NEW_PAGE_ROUTE
          ;(window as unknown as { __melisOpenTab?: (t: { id: string; label: string; path: string }) => void })
            .__melisOpenTab?.({ id: path, label: 'Nouvelle page', path })
          navigate(path)
        },
        true,
      )
    } catch { /* cross-origin / not ready — ignore */ }
  }

  return (
    <div style={{ position: 'relative', height: '100%', width: '100%', minHeight: 0 }}>
      {opened.map((cid) => (
        <iframe
          key={cid}
          src={toolSrc(cid)}
          title={`Page ${cid}`}
          onLoad={(e) => hookNewPage(e.currentTarget)}
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
