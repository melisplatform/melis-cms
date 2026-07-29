/**
 * Client de l'API Templates pour la brique MelisCms (liste + formulaire d'édition React).
 * Appelle la couche REST partagée : /melis/react-api/templates[/...]
 */

const XHR_HEADER = { 'X-Requested-With': 'XMLHttpRequest' } as const

export interface TemplateItem {
  id: number
  siteId: number
  siteName: string
  name: string
  type: string
  typeLabel: string
  websiteFolder: string
  layout: string
  controller: string
  action: string
  controllerAction: string
  phpPath: string
  creationDate: string
}
export interface TemplateStats { total: number; sites: number; types: number }
export interface SiteOption { id: number; name: string }
export interface TemplateListResult { items: TemplateItem[]; total: number; nextCursor: string | null }

async function apiFetch<T>(url: string, opts?: RequestInit): Promise<T> {
  const res = await fetch(url, {
    ...opts,
    headers: { ...XHR_HEADER, ...(opts?.headers ?? {}) },
    credentials: 'include',
  })
  if (!res.ok) {
    let msg = `HTTP ${res.status}`
    try { const d = (await res.json()) as { error?: string }; if (d.error) msg = d.error } catch { /* */ }
    throw new Error(msg)
  }
  const data = (await res.json()) as { success: boolean; data?: T; error?: string }
  if (!data.success) throw new Error(data.error ?? 'API error')
  return data.data as T
}

export interface TemplateListParams {
  limit?: number
  search?: string
  site?: number | null
  type?: string
  sort?: string
  dir?: 'asc' | 'desc'
  after?: string
}

export async function fetchTemplates(params: TemplateListParams = {}): Promise<TemplateListResult> {
  const qs = new URLSearchParams()
  qs.set('limit', String(params.limit ?? 25))
  if (params.search) qs.set('search', params.search)
  if (params.site) qs.set('site', String(params.site))
  if (params.type) qs.set('type', params.type)
  if (params.sort) qs.set('sort', params.sort)
  if (params.dir) qs.set('dir', params.dir)
  if (params.after) qs.set('after', params.after)
  return apiFetch<TemplateListResult>(`/melis/react-api/templates?${qs}`)
}

export async function fetchTemplateStats(): Promise<TemplateStats> {
  return apiFetch<TemplateStats>('/melis/react-api/templates/stats')
}

export async function fetchTemplateSites(): Promise<SiteOption[]> {
  const d = await apiFetch<{ sites: SiteOption[] }>('/melis/react-api/templates/sites')
  return d.sites
}

export async function fetchTemplate(id: number): Promise<TemplateItem> {
  return apiFetch<TemplateItem>(`/melis/react-api/templates/${id}`)
}

export interface TemplateSaveInput {
  id: number
  name: string
  type: string
  siteId: number | null
  websiteFolder: string
  layout: string
  controller: string
  action: string
  phpPath: string
}

export async function saveTemplate(data: TemplateSaveInput): Promise<number> {
  const res = await apiFetch<{ id: number }>('/melis/react-api/templates/save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  })
  return res.id
}

export async function deleteTemplate(id: number): Promise<void> {
  await apiFetch<null>(`/melis/react-api/templates/delete/${id}`, { method: 'DELETE' })
}
