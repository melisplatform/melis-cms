/**
 * Client de l'API Redirections 301 pour la brique MelisCms.
 *
 * Appelle la couche REST partagée (module MelisReactApi) :
 *   /melis/react-api/site-redirects[/...]
 * Contrat `{ success, data, error }` (comme les outils natifs). La brique ne peut pas
 * importer les modules de l'hôte (`@/lib/...`) — ce client est donc autonome.
 */

const XHR_HEADER = { 'X-Requested-With': 'XMLHttpRequest' } as const

export interface RedirectItem {
  id: number
  siteId: number
  siteName: string
  oldUrl: string
  newUrl: string
  baseUrl: string | null
}
export interface RedirectStats { total: number; sites: number }
export interface SiteOption { id: number; name: string }
export interface RedirectListResult { items: RedirectItem[]; total: number; page: number; limit: number }
export interface RedirectSavePayload { id?: number | null; siteId: number; oldUrl: string; newUrl: string }

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

export async function fetchRedirects(params: { search?: string; site?: number | null } = {}): Promise<RedirectListResult> {
  const qs = new URLSearchParams()
  qs.set('limit', '9999')
  if (params.search) qs.set('search', params.search)
  if (params.site) qs.set('site', String(params.site))
  return apiFetch<RedirectListResult>(`/melis/react-api/site-redirects?${qs}`)
}

export async function fetchRedirectById(id: number): Promise<RedirectItem> {
  return apiFetch<RedirectItem>(`/melis/react-api/site-redirects/${id}`)
}

export async function fetchRedirectStats(): Promise<RedirectStats> {
  return apiFetch<RedirectStats>('/melis/react-api/site-redirects/stats')
}

export async function fetchSites(): Promise<SiteOption[]> {
  const d = await apiFetch<{ sites: SiteOption[] }>('/melis/react-api/site-redirects/sites')
  return d.sites
}

export async function saveRedirect(payload: RedirectSavePayload): Promise<{ id: number }> {
  return apiFetch<{ id: number }>('/melis/react-api/site-redirects/save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
}

export async function deleteRedirect(id: number): Promise<void> {
  await apiFetch<null>(`/melis/react-api/site-redirects/delete/${id}`, { method: 'DELETE' })
}
