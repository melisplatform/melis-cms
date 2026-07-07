/**
 * Client de l'API Mini-Template Manager (MelisCms) pour la brique.
 *
 * Les mini-templates sont des fichiers .phtml sur disque ; l'identifiant est
 * composite (site + name) — pas de PK numérique. Le save utilise multipart/form-data
 * pour supporter l'upload de thumbnail.
 *
 * Appelle : /melis/react-api/cms-mini-templates[/...]
 */

const XHR_HEADER = { 'X-Requested-With': 'XMLHttpRequest' } as const

export interface MiniTemplateItem {
  site: string
  name: string
  thumbnailUrl: string | null
  path: string
}

export interface MiniTemplateDetail extends MiniTemplateItem {
  html: string
}

export interface MiniTemplateStats {
  total: number
  sites: number
}

export interface MiniTemplateSiteOption {
  id: number
  name: string   // label affiché
  module: string // site_name (clé technique utilisée pour identifier le site dans les templates)
}

export interface MiniTemplateListResult {
  items: MiniTemplateItem[]
  total: number
  page: number
  limit: number
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

export async function fetchMiniTemplates(params: {
  site?: string
  search?: string
  page?: number
  limit?: number
} = {}): Promise<MiniTemplateListResult> {
  const qs = new URLSearchParams()
  if (params.site)              qs.set('site', params.site)
  if (params.search)            qs.set('search', params.search)
  if (params.page != null)      qs.set('page', String(params.page))
  qs.set('limit', String(params.limit ?? 9999))
  return apiFetch<MiniTemplateListResult>(`/melis/react-api/cms-mini-templates?${qs}`)
}

export async function fetchMiniTemplateStats(): Promise<MiniTemplateStats> {
  return apiFetch<MiniTemplateStats>('/melis/react-api/cms-mini-templates/stats')
}

export async function fetchMiniTemplateSites(): Promise<MiniTemplateSiteOption[]> {
  const d = await apiFetch<{ sites: MiniTemplateSiteOption[] }>('/melis/react-api/cms-mini-templates/sites')
  return d.sites
}

export async function fetchMiniTemplateItem(site: string, name: string): Promise<MiniTemplateDetail> {
  const qs = new URLSearchParams({ site, name })
  return apiFetch<MiniTemplateDetail>(`/melis/react-api/cms-mini-templates/item?${qs}`)
}

export interface MiniTemplateSaveResult {
  site: string
  name: string
  thumbnailUrl: string | null
}

/**
 * Crée ou met à jour un mini-template. Utilise multipart/form-data (thumbnail optionnel).
 * Pour un edit : fournir oldSite + oldName = anciens identifiants.
 */
export async function saveMiniTemplate(payload: {
  site: string
  name: string
  html: string
  oldSite?: string
  oldName?: string
  thumbnail?: File | null
  /** Menu-manager « + » : lie le template fraîchement créé à cette catégorie (mtplc_id). Création uniquement. */
  category?: number | null
}): Promise<MiniTemplateSaveResult> {
  const fd = new FormData()
  fd.append('site', payload.site)
  fd.append('name', payload.name)
  fd.append('html', payload.html)
  if (payload.oldSite) fd.append('oldSite', payload.oldSite)
  if (payload.oldName) fd.append('oldName', payload.oldName)
  if (payload.thumbnail) fd.append('thumbnail', payload.thumbnail)
  if (payload.category) fd.append('category', String(payload.category))

  // Ne PAS passer Content-Type — le navigateur l'ajoute avec le boundary multipart.
  const res = await fetch('/melis/react-api/cms-mini-templates/save', {
    method: 'POST',
    headers: { ...XHR_HEADER },
    credentials: 'include',
    body: fd,
  })
  if (!res.ok) {
    let msg = `HTTP ${res.status}`
    try { const d = (await res.json()) as { error?: string }; if (d.error) msg = d.error } catch { /* */ }
    throw new Error(msg)
  }
  const data = (await res.json()) as { success: boolean; data?: MiniTemplateSaveResult; error?: string }
  if (!data.success) throw new Error(data.error ?? 'API error')
  return data.data as MiniTemplateSaveResult
}

export async function deleteMiniTemplate(site: string, name: string): Promise<void> {
  await apiFetch<null>('/melis/react-api/cms-mini-templates/delete', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ site, name }),
  })
}

// ── Stale-flag : la liste est persistante (montée une seule fois) ──
let _stale = false
export function markMiniTemplateListStale(): void  { _stale = true }
export function consumeMiniTemplateListStale(): boolean { const s = _stale; _stale = false; return s }
