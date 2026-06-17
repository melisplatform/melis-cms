import CmsPage from './CmsPage'
import CmsSidebar from './CmsSidebar'

/**
 * Brick entry point. Registers:
 *  - Component → routed content area (/cms, /cms/:id),
 *  - Sidebar   → the page tree, mounted in the left nav under the MelisCms section.
 */
declare global {
  interface Window {
    __melisRegisterBrick?: (b: { id: string; Component?: unknown; Sidebar?: unknown }) => void
  }
}

window.__melisRegisterBrick?.({ id: 'cms', Component: CmsPage, Sidebar: CmsSidebar })
