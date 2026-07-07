/**
 * Client de l'API Menu Manager (MelisCms) pour la brique.
 *
 * L'arbre est une liste PLATE (id/parent/type), au même format que jstree côté legacy —
 * le serveur (MelisCmsMiniTemplateService::getTree/saveTree) travaille avec cette forme,
 * donc on la garde telle quelle côté client plutôt que de la re-normaliser.
 *
 * Appelle : /melis/react-api/menu-manager[/...]
 */

const XHR_HEADER = { 'X-Requested-With': 'XMLHttpRequest' } as const

export interface SiteOption { id: number; name: string }
export interface LanguageOption { id: number; name: string; locale: string }

export interface TreeNode {
  id: string
  parent: string
  text: string
  icon?: string
  type: 'category' | 'mini-template'
  status?: number
  site_name?: string
  module?: string
  imgSource?: string
  unique_text?: string
  categoryId?: number
}

export interface CategoryDetail {
  id: number
  status: number
  translations: Record<string, string>
}

export interface CategorySavePayload {
  catId?: number | null
  siteId: number
  status: number
  currentLocale: string
  translations: Record<string, string>
}

async function apiFetch<T>(url: string, opts?: RequestInit): Promise<T> {
  const res = await fetch(url, {
    ...opts,
    headers: { ...XHR_HEADER, ...(opts?.headers ?? {}) },
    credentials: 'include',
  })
  if (!res.ok) {
    let msg = `HTTP ${res.status}`
    try {
      const d = (await res.json()) as { error?: string }
      if (d.error) msg = d.error
    } catch { /* ignore */ }
    throw new Error(msg)
  }
  const data = (await res.json()) as { success: boolean; data?: T; error?: string }
  if (!data.success) throw new Error(data.error ?? 'API error')
  return data.data as T
}

export async function fetchMenuManagerSites(): Promise<SiteOption[]> {
  const d = await apiFetch<{ sites: SiteOption[] }>('/melis/react-api/menu-manager/sites')
  return d.sites
}

export async function fetchMenuManagerLanguages(): Promise<LanguageOption[]> {
  const d = await apiFetch<{ languages: LanguageOption[] }>('/melis/react-api/menu-manager/languages')
  return d.languages
}

export async function fetchMenuManagerTree(siteId: number, locale: string): Promise<TreeNode[]> {
  const qs = new URLSearchParams({ siteId: String(siteId), locale })
  const d = await apiFetch<{ nodes: TreeNode[] }>(`/melis/react-api/menu-manager/tree?${qs}`)
  return d.nodes
}

export async function saveMenuManagerTree(siteId: number, nodes: TreeNode[]): Promise<void> {
  await apiFetch<null>('/melis/react-api/menu-manager/tree/save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      siteId,
      nodes: nodes.map((n) => ({ id: n.id, parent: n.parent, type: n.type })),
    }),
  })
}

export async function fetchMenuManagerCategory(id: number): Promise<CategoryDetail> {
  return apiFetch<CategoryDetail>(`/melis/react-api/menu-manager/category/${id}`)
}

export async function saveMenuManagerCategory(payload: CategorySavePayload): Promise<{ id: number }> {
  return apiFetch<{ id: number }>('/melis/react-api/menu-manager/category/save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
}

export async function deleteMenuManagerCategory(id: number): Promise<void> {
  await apiFetch<null>(`/melis/react-api/menu-manager/category/delete/${id}`, { method: 'DELETE' })
}
