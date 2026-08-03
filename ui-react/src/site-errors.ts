/**
 * Erreurs de champ renvoyées par le save LEGACY du site (`/melis/MelisCms/Sites/saveSite`).
 *
 * Le contrôleur legacy (SitesController::saveSiteAction) fusionne dans `errors` trois familles de
 * clés, PRÉFIXÉES par le groupe auquel le champ appartient :
 *   - `<env>_sdom_<champ>`     → onglet Domaines      (saveSiteDomains)
 *   - `<langId>_shome_<champ>` → onglet Propriétés    (saveSiteHomePages, pages d'accueil par langue)
 *   - `siteprop_<champ>`       → onglet Propriétés    (saveSiteProperties)
 *
 * La VALEUR est soit une chaîne (erreur métier, ex. domaine déjà utilisé), soit un objet de messages
 * de validateurs Laminas (`{ isEmpty: '…' }`). Sans ce parsing, React n'affichait que le message
 * global « Unable to save the site. » et l'utilisateur ne savait pas QUEL champ corriger.
 */

export interface SiteFieldError {
  /** Clé brute renvoyée par le legacy (sert d'ancre pour l'affichage sous le champ). */
  key: string
  /** Onglet de l'éditeur où se trouve le champ. */
  tab: 'props' | 'domains'
  /** Environnement (`local`, `prod`…) ou id de langue selon la famille ; null pour `siteprop_`. */
  scope: string | null
  /** Nom du champ legacy (`sdom_domain`, `site_label`, `shome_page_id`…). */
  field: string
  messages: string[]
}

/** Aplati une valeur d'erreur (chaîne, objet de validateurs, tableau) en liste de messages. */
function toMessages(value: unknown): string[] {
  if (typeof value === 'string') return value.trim() ? [value.trim()] : []
  if (Array.isArray(value)) return value.flatMap(toMessages)
  if (value && typeof value === 'object') return Object.values(value as Record<string, unknown>).flatMap(toMessages)
  return []
}

export function parseSiteSaveErrors(errors: unknown): SiteFieldError[] {
  if (!errors || typeof errors !== 'object') return []
  const out: SiteFieldError[] = []

  for (const [key, value] of Object.entries(errors as Record<string, unknown>)) {
    const messages = toMessages(value)
    if (messages.length === 0) continue

    const dom = /^(.+)_(sdom_.+)$/.exec(key)
    const home = /^(\d+)_(shome_.+)$/.exec(key)
    const prop = /^siteprop_(.+)$/.exec(key)

    if (home) out.push({ key, tab: 'props', scope: home[1], field: home[2], messages })
    else if (dom) out.push({ key, tab: 'domains', scope: dom[1], field: dom[2], messages })
    else if (prop) out.push({ key, tab: 'props', scope: null, field: prop[1], messages })
    // Clé inconnue : on la rattache aux propriétés pour qu'elle reste VISIBLE dans le bandeau.
    else out.push({ key, tab: 'props', scope: null, field: key, messages })
  }

  return out
}

/** Index `clé legacy → messages`, pour afficher l'erreur directement sous le champ concerné. */
export function byKey(list: SiteFieldError[]): Record<string, string[]> {
  const map: Record<string, string[]> = {}
  for (const e of list) map[e.key] = [...(map[e.key] ?? []), ...e.messages]
  return map
}
