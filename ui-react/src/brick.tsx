import CmsPage from './CmsPage'
import CmsSidebar from './CmsSidebar'
import SiteRedirectPage from './SiteRedirectPage'
import TemplatePage from './TemplatePage'

/**
 * Brick entry point. MelisCms ships SEVERAL React tools from this single bundle; each
 * self-registers under its own id and the host discovery lists them from the multi-brick
 * manifest (public/ui-react/brick.manifest.json → `bricks: [...]`).
 *  - cms           → page tree (Sidebar) + page editor content area (/melis-cms/page),
 *  - cms-site-301  → 301 redirects, native full-React list+form (/melis-cms/site-301),
 *  - cms-templates → templates, native React list (create/edit = legacy iframe) (/melis-cms/templates).
 */
declare global {
  interface Window {
    __melisRegisterBrick?: (b: { id: string; Component?: unknown; Sidebar?: unknown }) => void
  }
}

window.__melisRegisterBrick?.({ id: 'cms', Component: CmsPage, Sidebar: CmsSidebar })
window.__melisRegisterBrick?.({ id: 'cms-site-301', Component: SiteRedirectPage })
window.__melisRegisterBrick?.({ id: 'cms-templates', Component: TemplatePage })
