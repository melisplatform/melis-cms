/**
 * Client de l'API "Sites" pour la brique MelisCms.
 *
 * Liste + meta via la couche REST partagée (MelisReactApi) : /melis/react-api/cms-sites[...].
 * Création + suppression réutilisent les endpoints LEGACY éprouvés (toute la logique métier —
 * scaffolding module, pages, domaines, langues — reste côté Melis) :
 *   POST /melis/MelisCms/Sites/createNewSite   (body { data: formData })
 *   POST /melis/MelisCms/Sites/deleteSite      (body siteId)
 * La brique ne peut pas importer les modules de l'hôte (`@/...`) → client autonome.
 */

const XHR_HEADER = { 'X-Requested-With': 'XMLHttpRequest' } as const

export interface SiteItem { id: number; name: string; label: string; languages: string }
export interface SiteLang { id: number; locale: string; name: string }

// Flag "liste périmée" : posé après une création/édition/suppression, consommé par la liste
// quand elle redevient active (elle reste montée — onglets parallèles — donc ne se recharge pas seule).
let _listStale = false
export function markSitesListStale() { _listStale = true }
export function consumeSitesListStale() { const s = _listStale; _listStale = false; return s }

async function apiFetch<T>(url: string, opts?: RequestInit): Promise<T> {
  const res = await fetch(url, { ...opts, headers: { ...XHR_HEADER, ...(opts?.headers ?? {}) }, credentials: 'include' })
  if (!res.ok) {
    let msg = `HTTP ${res.status}`
    try { const d = (await res.json()) as { error?: string }; if (d.error) msg = d.error } catch { /* ignore */ }
    throw new Error(msg)
  }
  const data = (await res.json()) as { success: boolean; data?: T; error?: string }
  if (!data.success) throw new Error(data.error ?? 'API error')
  return data.data as T
}

export async function fetchSites(search = ''): Promise<SiteItem[]> {
  const qs = search ? `?search=${encodeURIComponent(search)}` : ''
  const data = await apiFetch<{ items: SiteItem[]; total: number }>(`/melis/react-api/cms-sites${qs}`)
  return data.items
}

export async function fetchSiteLangs(): Promise<SiteLang[]> {
  const data = await apiFetch<{ languages: SiteLang[] }>('/melis/react-api/cms-sites/meta')
  return data.languages
}

export interface SiteMeta { languages: SiteLang[]; modules: string[]; defaultDomain: string; platform: string }
/** Meta pour le wizard de création : langues, modules existants, domaine par défaut, plateforme. */
export async function fetchSiteMeta(): Promise<SiteMeta> {
  const data = await apiFetch<{ languages: SiteLang[]; modules?: string[]; defaultDomain?: string; platform?: string }>('/melis/react-api/cms-sites/meta')
  return { languages: data.languages ?? [], modules: data.modules ?? [], defaultDomain: data.defaultDomain ?? '', platform: data.platform ?? '' }
}

/** Suppression via l'endpoint legacy. Retourne le message serveur. */
export async function deleteSite(siteId: number): Promise<{ success: boolean; textTitle?: string; textMessage?: string }> {
  const res = await fetch('/melis/MelisCms/Sites/deleteSite', {
    method: 'POST',
    headers: { ...XHR_HEADER, 'Content-Type': 'application/x-www-form-urlencoded' },
    credentials: 'include',
    body: new URLSearchParams({ siteId: String(siteId) }).toString(),
  })
  return res.json()
}

export interface CreateSitePayload {
  name: string                      // nom du module (nouveau module) — généré en PascalCase côté serveur
  label: string                     // libellé du site (site_label)
  languages: { id: number; locale: string }[]
  domains: Record<string, string>   // locale → domaine (ou clé 'single')
  urlSetting: number                // 1 = locale après domaine, 2 = un domaine par langue, 3 = rien (nom de page)
  isNewSite: boolean                // true = créer un NOUVEAU module ; false = rattacher à un module EXISTANT
  existingModuleName?: string       // nom du module existant si isNewSite=false
  createFile: boolean               // créer les dossiers/fichiers du module
  dndRenderMode: boolean            // mode Drag & Drop (bootstrap)
}

/** Création via la couche react-api propre (qui réutilise MelisCmsSiteService::saveSite). */
export async function createSite(payload: CreateSitePayload): Promise<{ siteIds: number[]; siteName: string; siteLabel: string }> {
  return apiFetch<{ siteIds: number[]; siteName: string; siteLabel: string }>('/melis/react-api/cms-sites/create', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
}

// ─── Édition d'un site (onglets cœur : Propriétés, Domaines, Langues) ──────────
//
// Chargement : couche react-api propre (GET /cms-sites/:id, shape JSON).
// Sauvegarde : endpoint LEGACY transactionnel POST /melis/MelisCms/Sites/saveSite?siteId=X.
// On reconstruit le POST « à plat » attendu par SitesController::saveSiteAction :
//   siteprop_<col>, <env>_sdom_<col>, <langId>_shome_<col>, slang_lang_id[], site_opt_lang_url,
//   to_delete_languages_data[<langId>]. Toute la logique métier (validation, transaction,
//   cache, régénération d'URL) reste donc côté Melis.

export interface SiteEditLang { id: number; locale: string; name: string; active: boolean }
export interface SiteEditHome { shomeId: number; langId: number; pageId: number }
export interface SiteEditDomain { id: number; env: string; scheme: string; domain: string }
export interface SiteEditData {
  site: { id: number; name: string; label: string; mainPageId: number; dndRenderMode: string; optLangUrl: number; s404PageId: number }
  languages: SiteEditLang[]
  homepages: SiteEditHome[]
  domains: SiteEditDomain[]
  environments: string[]
  currentEnv: string
  pageTitles: Record<string, string>
}

export async function fetchSite(id: number): Promise<SiteEditData> {
  return apiFetch<SiteEditData>(`/melis/react-api/cms-sites/${id}`)
}

export interface SiteSaveInput {
  id: number
  name: string
  label: string
  s404PageId: number
  mainPageId: number
  dndRenderMode: string
  optLangUrl: number
  activeLangIds: number[]
  removedLangIds: number[]
  homepages: { langId: number; pageId: number; shomeId: number }[] // langues actives uniquement
  domains: { env: string; scheme: string; domain: string; id: number }[]
  /** Onglet Config : champs POST sconf bruts (clé = nom de champ legacy `gen_sconf_x` / `<langId>_sconf_x[...]`). */
  configFields?: Record<string, string>
  /** Onglet Module Loader : noms des modules actifs (envoyés en `moduleLoad<name>` ssi admin). */
  modules?: { isAdmin: boolean; activeNames: string[] }
}

export interface SaveSiteResult { success: boolean; textTitle?: string; textMessage?: string; errors?: Record<string, unknown> }

export async function saveSiteEdit(input: SiteSaveInput): Promise<SaveSiteResult> {
  const p = new URLSearchParams()
  // Propriétés
  p.append('siteprop_site_id', String(input.id))
  p.append('siteprop_site_label', input.label)
  p.append('siteprop_site_name', input.name)
  p.append('siteprop_s404_page_id', String(input.s404PageId || ''))
  p.append('siteprop_site_main_page_id', String(input.mainPageId || ''))
  p.append('siteprop_site_dnd_render_mode', input.dndRenderMode || '')
  // Pages d'accueil par langue (un groupe complet par langue → détection de groupe côté legacy)
  for (const h of input.homepages) {
    if (h.shomeId > 0) p.append(`${h.langId}_shome_id`, String(h.shomeId))
    p.append(`${h.langId}_shome_site_id`, String(input.id))
    p.append(`${h.langId}_shome_lang_id`, String(h.langId))
    p.append(`${h.langId}_shome_page_id`, String(h.pageId || ''))
  }
  // Domaines par environnement (un groupe complet par env)
  for (const d of input.domains) {
    if (d.id > 0) p.append(`${d.env}_sdom_id`, String(d.id))
    p.append(`${d.env}_sdom_site_id`, String(input.id))
    p.append(`${d.env}_sdom_env`, d.env)
    p.append(`${d.env}_sdom_scheme`, d.scheme)
    p.append(`${d.env}_sdom_domain`, d.domain)
  }
  // Langues
  p.append('site_opt_lang_url', String(input.optLangUrl))
  for (const id of input.activeLangIds) p.append('slang_lang_id[]', String(id))
  for (const id of input.removedLangIds) p.append(`to_delete_languages_data[${id}]`, 'true')
  // Config (champs sconf bruts : le legacy diffe contre la config fichier et ne stocke que les écarts)
  if (input.configFields) for (const [name, val] of Object.entries(input.configFields)) p.append(name, val)
  // Modules (admin only ; le legacy collecte les `moduleLoad<name>` présents = modules actifs)
  if (input.modules?.isAdmin) for (const name of input.modules.activeNames) p.append(`moduleLoad${name}`, 'on')

  const res = await fetch(`/melis/MelisCms/Sites/saveSite?siteId=${input.id}`, {
    method: 'POST',
    headers: { ...XHR_HEADER, 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    credentials: 'include',
    body: p.toString(),
  })
  return res.json()
}

// ─── Onglet Config ────────────────────────────────────────────────────────────

export interface ConfigArrayEntry { key: string; value: string; isInt: boolean }
export interface ConfigItem { key: string; type: 'scalar' | 'array'; value?: string; entries?: ConfigArrayEntry[] }
export interface ConfigSection { langId?: number; locale?: string; name?: string; sconfId: number; items: ConfigItem[] }
export interface SiteConfigData { general: ConfigSection; perLang: ConfigSection[] }

export async function fetchSiteConfig(id: number): Promise<SiteConfigData> {
  return apiFetch<SiteConfigData>(`/melis/react-api/cms-sites/${id}/config`)
}

// ─── Onglet Module Loader ──────────────────────────────────────────────────────

export interface SiteModule { name: string; active: boolean; version: string; package: string; requires: string[]; dependents: string[] }
export interface SiteModulesData { modules: SiteModule[]; isAdmin: boolean }

export async function fetchSiteModules(id: number): Promise<SiteModulesData> {
  return apiFetch<SiteModulesData>(`/melis/react-api/cms-sites/${id}/modules`)
}

interface DepResult { success: number; modules: string[]; message: string }
async function depFetch(url: string, module: string): Promise<DepResult> {
  const res = await fetch(url, {
    method: 'POST',
    headers: { ...XHR_HEADER, 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    credentials: 'include',
    body: new URLSearchParams({ module }).toString(),
  })
  return res.json()
}
/** Modules qui DÉPENDENT de `module` (cassés si on le désactive). */
export const getModuleDependents = (module: string) =>
  depFetch('/melis/MelisCms/SitesModuleLoader/getDependents', module)
/** Prérequis de `module` (à activer avant lui). */
export const getRequiredDependencies = (siteId: number, module: string) =>
  depFetch(`/melis/MelisCms/SitesModuleLoader/getRequiredDependencies?siteId=${siteId}`, module)

// ─── Onglet Traductions (endpoints legacy dédiés) ──────────────────────────────

export interface TransText { langId: number; mstId: number; msttId: number; text: string }
export interface TransKey { mstId: number; key: string; module: string | null; texts: Record<number, TransText> }

/** Liste des traductions du site, regroupées par clé (via le endpoint DataTable legacy). */
export async function fetchTranslations(siteId: number): Promise<TransKey[]> {
  const body = new URLSearchParams()
  body.append('draw', '1'); body.append('start', '0'); body.append('length', '10000')
  body.append('search[value]', ''); body.append('siteId', String(siteId))
  const res = await fetch('/melis/MelisCms/SitesTranslation/getTranslation', {
    method: 'POST',
    headers: { ...XHR_HEADER, 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    credentials: 'include',
    body: body.toString(),
  })
  const data = await res.json() as { data?: Array<Record<string, unknown>> }
  const byKey = new Map<string, TransKey>()
  for (const row of data.data ?? []) {
    const key = String(row.mst_key ?? '')
    const mstId = Number(row.mst_id ?? 0)
    const langId = Number(row.mstt_lang_id ?? 0)
    if (!byKey.has(key)) byKey.set(key, { mstId, key, module: (row.module as string) ?? null, texts: {} })
    const tk = byKey.get(key)!
    if (mstId) tk.mstId = mstId
    tk.texts[langId] = { langId, mstId, msttId: Number(row.mstt_id ?? 0), text: String(row.mstt_text ?? '') }
  }
  return [...byKey.values()].sort((a, b) => a.key.localeCompare(b.key))
}

/** Crée/édite une clé de traduction (un groupe de champs `<langId>-...` par langue). */
export async function saveTranslation(siteId: number, key: string, langs: { langId: number; mstId: number; msttId: number; text: string }[]): Promise<{ success: boolean; errors?: unknown }> {
  const p = new URLSearchParams()
  for (const l of langs) {
    p.append(`${l.langId}-mst_id`, String(l.mstId || 0))
    p.append(`${l.langId}-mstt_id`, String(l.msttId || 0))
    p.append(`${l.langId}-mst_site_id`, String(siteId))
    p.append(`${l.langId}-mstt_lang_id`, String(l.langId))
    p.append(`${l.langId}-mst_key`, key)
    p.append(`${l.langId}-mstt_text`, l.text)
  }
  const res = await fetch('/melis/MelisCms/SitesTranslation/saveTranslation', {
    method: 'POST',
    headers: { ...XHR_HEADER, 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    credentials: 'include',
    body: p.toString(),
  })
  return res.json()
}

export async function deleteTranslation(siteId: number, mstId: number): Promise<{ success: boolean }> {
  const res = await fetch('/melis/MelisCms/SitesTranslation/deleteTranslation', {
    method: 'POST',
    headers: { ...XHR_HEADER, 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    credentials: 'include',
    body: new URLSearchParams({ mst_id: String(mstId), siteId: String(siteId) }).toString(),
  })
  return res.json()
}
