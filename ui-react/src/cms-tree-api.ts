/**
 * Page-tree data for the CMS brick.
 *
 * Reuses the legacy lazy-load endpoint (no backend change):
 *   GET /melis/MelisCms/TreeSites/get-tree-pages-by-page-id?nodeId=<id>
 *   nodeId = -1 → site roots; nodeId = <pageId> → that page's children.
 * Requires the session cookie + X-Requested-With (like every back-office route).
 * Returns a FLAT array of the children of <nodeId>.
 */
export interface MelisTreeNode {
  /** Page id (fancytree node key). */
  key: number
  /** Display label, already formatted "<id> - <name>". */
  title: string
  /** True when the node has children (show an expand caret + lazy-load). */
  lazy: boolean
  /** True when the user is allowed to drag-reorder this node (server-side rights). */
  dragdrop?: boolean
  /** Legacy FontAwesome class, e.g. "fa fa-home" / "fa fa-folder-open-o". */
  iconTab?: string
  melisData?: {
    page_title?: string
    page_id?: number
    page_is_online?: number
    page_has_saved_version?: number
    page_type?: string
  }
}

/** Nodes seen so far, keyed by page id — lets the content page show a title without an extra call. */
export const nodeCache = new Map<number, MelisTreeNode>()

/**
 * Deletes a page (legacy endpoint, no backend change):
 *   GET /melis/MelisCms/Page/deletePage?idPage=<id>
 * Returns the server's success flag + notification texts.
 */
export async function deletePage(
  idPage: number,
): Promise<{ success: boolean; title?: string; message?: string }> {
  try {
    const res = await fetch(`/melis/MelisCms/Page/deletePage?idPage=${encodeURIComponent(String(idPage))}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'include',
    })
    const data = await res.json()
    return {
      success: data?.success === 1 || data?.success === true,
      title: data?.textTitle,
      message: data?.textMessage,
    }
  } catch {
    return { success: false }
  }
}

/**
 * Searches pages by NAME on the server (real backend search — finds pages that
 * have not been lazy-loaded into the tree yet), legacy endpoint (no backend change):
 *   POST /melis/MelisCms/Page/searchTreePages   body: value=<query>
 * The server returns key-paths like "0/1/5/6" — the chain of page ids from the
 * virtual root down to each matching page. We parse them into id chains and drop
 * the leading virtual father (id 0 / negative) so each chain starts at a real root.
 * The LAST id of a chain is the matching page; the others are its ancestors (to load
 * + expand so the match becomes visible).
 */
export async function searchTreePages(value: string): Promise<number[][]> {
  try {
    const res = await fetch('/melis/MelisCms/Page/searchTreePages', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      credentials: 'include',
      body: new URLSearchParams({ value }).toString(),
    })
    if (!res.ok) return []
    const data = await res.json()
    const paths: unknown[] = Array.isArray(data) ? data : Object.values(data ?? {})
    return paths
      .map((p) => String(p).split('/').map((s) => Number(s)).filter((n) => Number.isFinite(n) && n > 0))
      .filter((chain) => chain.length > 0)
  } catch {
    return []
  }
}

/**
 * Reorders / re-parents a page (legacy endpoint, no backend change):
 *   GET /melis/MelisCms/Page/movePage?idPage=&oldFatherIdPage=&newFatherIdPage=&newPositionIdPage=
 * Fathers are page ids, or -1 for the (virtual) root level. newPositionIdPage is the 1-based
 * insert position among the destination's children (siblings excluding the moved page).
 */
export async function movePage(params: {
  idPage: number
  oldFatherIdPage: number
  newFatherIdPage: number
  newPositionIdPage: number
}): Promise<{ success: boolean; title?: string; message?: string }> {
  try {
    const qs = new URLSearchParams({
      idPage: String(params.idPage),
      oldFatherIdPage: String(params.oldFatherIdPage),
      newFatherIdPage: String(params.newFatherIdPage),
      newPositionIdPage: String(params.newPositionIdPage),
    }).toString()
    const res = await fetch(`/melis/MelisCms/Page/movePage?${qs}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'include',
    })
    const data = await res.json()
    return { success: data?.success === 1 || data?.success === true, title: data?.textTitle, message: data?.textMessage }
  } catch {
    return { success: false }
  }
}

export async function fetchTreeNodes(nodeId: number): Promise<MelisTreeNode[]> {
  try {
    const res = await fetch(
      `/melis/MelisCms/TreeSites/get-tree-pages-by-page-id?nodeId=${encodeURIComponent(String(nodeId))}`,
      { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'include' },
    )
    if (!res.ok) return []
    const data = await res.json()
    // The endpoint returns a flat array; be defensive about wrappers.
    let nodes: MelisTreeNode[] = []
    if (Array.isArray(data)) nodes = data as MelisTreeNode[]
    else if (Array.isArray(data?.data)) nodes = data.data as MelisTreeNode[]
    else if (Array.isArray(data?.tree)) nodes = data.tree as MelisTreeNode[]
    nodes.forEach((n) => nodeCache.set(n.key, n))
    return nodes
  } catch {
    return []
  }
}
