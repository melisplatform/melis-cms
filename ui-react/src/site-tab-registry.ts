import { useEffect, useReducer, type ReactNode } from 'react'

/**
 * Registre GÉNÉRIQUE d'onglets pour l'éditeur de site (SiteEditor).
 *
 * Point d'extension module-agnostique : n'importe quelle brique de module peut AJOUTER un onglet
 * à l'éditeur de site sans que MelisCms ne connaisse ce module. Comme les briques sont des bundles
 * séparés (pas d'import cross-bundle), le contrat passe par un GLOBAL `window` :
 *
 *   // côté brique (ex. MelisCmsPageScriptEditor) — JS pur, aucun import de l'hôte :
 *   ;(window.__melisSiteTabs ||= [])
 *   const tab = { id, label, order, Component }        // Component: (props:{siteId, registerSave}) => ReactNode
 *   const i = window.__melisSiteTabs.findIndex(t => t.id === tab.id)
 *   if (i >= 0) window.__melisSiteTabs[i] = tab; else window.__melisSiteTabs.push(tab)
 *   window.dispatchEvent(new CustomEvent('melis:site-tabs-changed'))
 *
 * La brique n'est chargée QUE si son module est actif (discovery /react-modules + prefetch dans
 * loadBricks), donc l'onglet n'apparaît que module actif — même garantie que les briques routées.
 *
 * Persistance : SiteEditor garde l'onglet monté une fois ouvert et déclenche son `registerSave`
 * lors du Save GLOBAL — l'onglet fournit sa fonction de sauvegarde, l'hôte ne connaît pas son métier.
 */

export type SiteTabSaveFn = () => Promise<void>

export interface SiteTabDef {
  /** Identifiant unique de l'onglet (ex. 'scripts'). */
  id: string
  /** Libellé : chaîne unique, ou objet { fr, en } (l'hôte choisit selon <html lang>). */
  label: string | { fr: string; en: string }
  /** Ordre d'affichage parmi les onglets (défaut 100 → après les onglets natifs). */
  order?: number
  /** Composant de l'onglet. Reçoit le siteId + un registerSave pour brancher son save au Save global. */
  Component: (props: { siteId: number; registerSave: (fn: SiteTabSaveFn | null) => void }) => ReactNode
}

declare global {
  interface Window {
    __melisSiteTabs?: SiteTabDef[]
  }
}

/** Onglets enregistrés, triés par `order`. */
export function getSiteTabs(): SiteTabDef[] {
  return (window.__melisSiteTabs ?? []).slice().sort((a, b) => (a.order ?? 100) - (b.order ?? 100))
}

/** Variante réactive : re-render sur (dé)enregistrement d'un onglet. */
export function useSiteTabs(): SiteTabDef[] {
  const [, force] = useReducer((x: number) => x + 1, 0)
  useEffect(() => {
    const on = () => force()
    window.addEventListener('melis:site-tabs-changed', on)
    return () => window.removeEventListener('melis:site-tabs-changed', on)
  }, [])
  return getSiteTabs()
}
