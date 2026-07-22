/**
 * Normalisation des erreurs renvoyées par les endpoints LEGACY de MelisCms (savePage / publishPage /
 * createPage…). Le legacy n'a PAS une seule forme d'`errors` — d'où des toasts vides côté React
 * (« Some errors occured… » sans le détail). Formes rencontrées :
 *
 *  a) { champ: { validatorKey: 'message', label: 'Libellé' } }   validation de formulaire Laminas
 *  b) { pseo_url: 'message', label: 'Libellé' }                  erreur métier renvoyée À PLAT
 *                                                                (ex. unicité de l'URL SEO,
 *                                                                 PageSeoController::savePage)
 *  c) [ { pseo_url: 'message', label: 'Libellé' } ]              idem, encapsulée dans un tableau
 *
 * Règle : dans une entrée, `label` est le libellé du champ, TOUTE autre valeur chaîne est un message.
 */
export type NotifField = { label: string; messages: string[] }

type Entry = Record<string, unknown>

const toField = (entry: Entry, fallbackLabel: string): NotifField | null => {
  const messages = Object.entries(entry)
    .filter(([k, v]) => k !== 'label' && typeof v === 'string' && v.trim() !== '')
    .map(([, v]) => String(v).trim())
  if (messages.length === 0) return null
  const label = typeof entry.label === 'string' && entry.label.trim() ? entry.label.trim() : fallbackLabel
  return { label, messages }
}

/** Aplati `errors` (quelle que soit sa forme) en une liste affichable dans le toast / sous le formulaire. */
export function legacyErrorFields(errors: unknown, fallbackLabel = 'Erreur'): NotifField[] {
  if (!errors || typeof errors !== 'object') return []
  const values = Object.values(errors as Entry)
  // Forme (b) : la racine EST déjà l'entrée (au moins une de ses valeurs est une chaîne).
  const entries: Entry[] = values.some((v) => typeof v === 'string')
    ? [errors as Entry]
    : values.filter((v): v is Entry => !!v && typeof v === 'object')
  return entries.map((e) => toField(e, fallbackLabel)).filter((f): f is NotifField => f !== null)
}

/**
 * `textMessage` legacy : certains contrôleurs renvoient la CLÉ de traduction BRUTE (non traduite dans
 * le JSON), ex. « tr_meliscms_page_error_Some errors occured while processing the request… ».
 * On ne l'affiche jamais telle quelle → on retombe sur un message React traduit.
 */
export function legacyText(raw: string | undefined | null, fallback: string): string {
  const t = (raw || '').trim()
  return !t || t.startsWith('tr_') ? fallback : t
}
