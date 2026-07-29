/**
 * Client de l'API Styles (CSS) pour la brique MelisCms.
 *
 * Appelle la couche REST partagée (module MelisReactApi) :
 *   /melis/react-api/cms-styles[/...]
 * Contrat `{ success, data, error }` (comme les outils natifs). La brique ne peut pas
 * importer les modules de l'hôte (`@/lib/...`) — ce client est donc autonome.
 */

const XHR_HEADER = { 'X-Requested-With': 'XMLHttpRequest' } as const

export interface StyleItem {
  id: number
  siteId: number
  siteName: string
  name: string
  status: number
  path: string
}
export interface StyleStats { total: number; active: number; inactive: number }
export interface SiteOption { id: number; name: string }
export interface StyleListResult { items: StyleItem[]; total: number; nextCursor: string | null }
export interface StyleSavePayload { id?: number | null; siteId: number; name: string; status: number; path: string }

export type StyleSortKey = 'id' | 'status' | 'name' | 'path' | 'site'
export interface StyleListParams {
  limit?: number
  search?: string
  site?: number | null
  status?: '' | '0' | '1'
  sort?: StyleSortKey | string
  dir?: 'asc' | 'desc'
  after?: string
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

export async function fetchStyles(params: StyleListParams = {}): Promise<StyleListResult> {
  const qs = new URLSearchParams()
  qs.set('limit', String(params.limit ?? 25))
  if (params.search) qs.set('search', params.search)
  if (params.site) qs.set('site', String(params.site))
  if (params.status) qs.set('status', params.status)
  if (params.sort) qs.set('sort', String(params.sort))
  if (params.dir) qs.set('dir', params.dir)
  if (params.after) qs.set('after', params.after)
  return apiFetch<StyleListResult>(`/melis/react-api/cms-styles?${qs}`)
}

export async function fetchStyleById(id: number): Promise<StyleItem> {
  return apiFetch<StyleItem>(`/melis/react-api/cms-styles/${id}`)
}

export async function fetchStyleStats(): Promise<StyleStats> {
  return apiFetch<StyleStats>('/melis/react-api/cms-styles/stats')
}

export async function fetchStyleSites(): Promise<SiteOption[]> {
  const d = await apiFetch<{ sites: SiteOption[] }>('/melis/react-api/cms-styles/sites')
  return d.sites
}

export async function saveStyle(payload: StyleSavePayload): Promise<{ id: number }> {
  return apiFetch<{ id: number }>('/melis/react-api/cms-styles/save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
}

export async function deleteStyle(id: number): Promise<void> {
  await apiFetch<null>(`/melis/react-api/cms-styles/delete/${id}`, { method: 'DELETE' })
}
