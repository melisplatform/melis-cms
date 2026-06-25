/**
 * Client de l'API Langues (CMS) pour la brique MelisCms.
 *
 * Appelle la couche REST partagée (module MelisReactApi) :
 *   /melis/react-api/cms-languages[/...]
 * Contrat `{ success, data, error }` (comme les outils natifs). La brique ne peut pas
 * importer les modules de l'hôte (`@/lib/...`) — ce client est donc autonome.
 */

const XHR_HEADER = { 'X-Requested-With': 'XMLHttpRequest' } as const

export interface LangItem {
  id: number
  locale: string
  name: string
}
export interface LangStats { total: number }
export interface LangListResult { items: LangItem[]; total: number; page: number; limit: number }
export interface LangSavePayload { id?: number | null; locale: string; name: string }

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

export async function fetchLanguages(params: { search?: string } = {}): Promise<LangListResult> {
  const qs = new URLSearchParams()
  qs.set('limit', '9999')
  if (params.search) qs.set('search', params.search)
  return apiFetch<LangListResult>(`/melis/react-api/cms-languages?${qs}`)
}

export async function fetchLanguageById(id: number): Promise<LangItem> {
  return apiFetch<LangItem>(`/melis/react-api/cms-languages/${id}`)
}

export async function fetchLanguageStats(): Promise<LangStats> {
  return apiFetch<LangStats>('/melis/react-api/cms-languages/stats')
}

export async function saveLanguage(payload: LangSavePayload): Promise<{ id: number }> {
  return apiFetch<{ id: number }>('/melis/react-api/cms-languages/save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
}

export async function deleteLanguage(id: number): Promise<void> {
  await apiFetch<null>(`/melis/react-api/cms-languages/delete/${id}`, { method: 'DELETE' })
}
