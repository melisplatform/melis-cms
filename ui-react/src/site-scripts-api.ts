/**
 * Client de l'onglet « Scripts » de l'éditeur de site (brique MelisCms).
 *
 * L'API vit dans le module MelisCmsPageScriptEditor (PAS melis-react-api) :
 *   /melis/MelisCmsPageScriptEditor/MelisCmsPageScriptEditorReact/*
 * Contrat `{ success, data, error }`. La brique ne peut pas importer les modules de l'hôte —
 * ce client est autonome. Toute la logique métier (règles de save/exception) reste côté Melis
 * (MelisCmsPageScriptEditorService, réutilisé par le contrôleur JSON du module).
 */

const BASE = '/melis/MelisCmsPageScriptEditor/MelisCmsPageScriptEditorReact'
const XHR_HEADER = { 'X-Requested-With': 'XMLHttpRequest' } as const

export interface ScriptData { id: number; headTop: string; headBottom: string; bodyBottom: string }
export interface SiteScriptResult { siteId: number; script: ScriptData | null; exceptionCount: number }
export interface ExceptionItem { id: number; pageId: number; pageName: string }

async function apiFetch<T>(url: string, opts?: RequestInit): Promise<T> {
  const res = await fetch(url, {
    ...opts,
    headers: { ...XHR_HEADER, ...(opts?.headers ?? {}) },
    credentials: 'include',
  })
  let body: { success: boolean; data?: T; error?: string } | null = null
  try { body = await res.json() } catch { /* ignore */ }
  if (!res.ok || !body || !body.success) {
    throw new Error(body?.error ?? `HTTP ${res.status}`)
  }
  return body.data as T
}

export function fetchSiteScript(siteId: number): Promise<SiteScriptResult> {
  return apiFetch<SiteScriptResult>(`${BASE}/site-script?siteId=${siteId}`)
}

export function saveSiteScript(payload: { siteId: number; id?: number | null; headTop: string; headBottom: string; bodyBottom: string }): Promise<null> {
  return apiFetch<null>(`${BASE}/save-site-script`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
}

export function fetchExceptions(siteId: number): Promise<{ items: ExceptionItem[]; total: number }> {
  return apiFetch<{ items: ExceptionItem[]; total: number }>(`${BASE}/exceptions?siteId=${siteId}`)
}

export function addException(siteId: number, pageId: number): Promise<null> {
  return apiFetch<null>(`${BASE}/add-exception`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ siteId, pageId }),
  })
}

export function deleteException(id: number): Promise<null> {
  return apiFetch<null>(`${BASE}/delete-exception`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id }),
  })
}
