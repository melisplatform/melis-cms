/**
 * Client de l'API Platforms IDs pour la brique MelisCms.
 *
 * Appelle la couche REST partagée (module MelisReactApi) :
 *   /melis/react-api/cms-platform-ids[/...]
 * Contrat `{ success, data, error }` (comme les outils natifs). La brique ne peut pas
 * importer les modules de l'hôte (`@/lib/...`) — ce client est donc autonome.
 */

const XHR_HEADER = { 'X-Requested-With': 'XMLHttpRequest' } as const

export interface PlatformIdItem {
  id: number
  name: string           // nom de la plateforme (pids_platform) — affiché mais non éditable
  pageStart: number
  pageCurrent: number
  pageEnd: number
  tplStart: number
  tplCurrent: number
  tplEnd: number
}
export interface AvailablePlatform { id: number; name: string }
// availablePlatforms = plateformes SANS plage : on ne peut créer une plage que pour l'une d'elles.
export interface PlatformIdStats { total: number; availablePlatforms: AvailablePlatform[] }
export interface PlatformIdListResult { items: PlatformIdItem[]; total: number; nextCursor: string | null }
export interface PlatformIdListParams {
  limit?: number
  search?: string
  sort?: string
  dir?: 'asc' | 'desc'
  after?: string
}
export interface PlatformIdSavePayload {
  id?: number | null
  platformId?: number | null   // création : plateforme (plf_id) à rattacher (pids_id = plf_id)
  pageStart: number
  pageCurrent: number
  pageEnd: number
  tplStart: number
  tplCurrent: number
  tplEnd: number
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

export async function fetchPlatformIds(params: PlatformIdListParams = {}): Promise<PlatformIdListResult> {
  const qs = new URLSearchParams()
  qs.set('limit', String(params.limit ?? 25))
  if (params.search) qs.set('search', params.search)
  if (params.sort) qs.set('sort', params.sort)
  if (params.dir) qs.set('dir', params.dir)
  if (params.after) qs.set('after', params.after)
  return apiFetch<PlatformIdListResult>(`/melis/react-api/cms-platform-ids?${qs}`)
}

export async function fetchPlatformIdById(id: number): Promise<PlatformIdItem> {
  return apiFetch<PlatformIdItem>(`/melis/react-api/cms-platform-ids/${id}`)
}

export async function fetchPlatformIdStats(): Promise<PlatformIdStats> {
  return apiFetch<PlatformIdStats>('/melis/react-api/cms-platform-ids/stats')
}

export async function savePlatformId(payload: PlatformIdSavePayload): Promise<{ id: number }> {
  return apiFetch<{ id: number }>('/melis/react-api/cms-platform-ids/save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
}

export async function deletePlatformId(id: number): Promise<void> {
  await apiFetch<null>(`/melis/react-api/cms-platform-ids/delete/${id}`, { method: 'DELETE' })
}
