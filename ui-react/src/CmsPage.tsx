import { useCallback, useEffect, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { ViewToggle, type ViewMode } from './ViewToggle'
import NewPageView from './NewPageView'
import EditionCanvas from './EditionCanvas'
import {
  PropertiesTab, SeoTab, LanguagesTab, HistoricTab, AnalyticsTab, ScriptsTab, VersioningTab, CommentsTab,
  apiGet, apiPost, type PropsData, type SeoData, type Refs,
} from './PageTabs'
import { peT } from './page-editor-i18n'
import { useIsNarrow, useViewportWidth } from './shared/useIsNarrow'
import { legacyErrorFields, legacyText } from './legacy-errors'

/**
 * Éditeur de page CMS — COQUILLE REACT au-dessus de l'outil legacy (UNE iframe, pour l'Édition
 * drag'n'drop couplée aux actions). Chrome React natif : toggle New/Old + en-tête + barre de
 * BOUTONS + barre d'ONGLETS. Contenu des onglets = React natif (sauf Édition = iframe legacy).
 *
 * Principes demandés :
 *  - AUCUN rechargement au changement d'onglet : Propriétés/SEO sont CONTRÔLÉS par un état partagé
 *    chargé UNE fois ; les onglets modulaires (vues) sont montés à la 1ʳᵉ ouverture puis GARDÉS
 *    montés (display toggle) → pas de refetch.
 *  - UNE sauvegarde globale : les boutons du haut agissent sur TOUTE la page. « Sauvegarder » écrit
 *    Propriétés + SEO ensemble (une action) ; « Publier » sauve puis publie. (Le contenu drag'n'drop
 *    est auto-sauvé par les plugins legacy.)
 * Modularité : onglets/boutons viennent de /cms-page/structure (config mergée). Capabilities : gating.
 */
const NEW_PAGE_ROUTE = '/melis-cms/page/new'
/** Préférence « en-tête replié » (mobile) — persistée : le choix vaut pour toutes les pages ouvertes. */
const HEADER_PREF_KEY = 'melis-cms-page-header-open'
const TOOL_KEY = 'meliscms_page'
const KEY_PROPERTIES = 'meliscms_page_properties'
const KEY_SEO = 'meliscms_page_seo'
/** Onglet Édition (drag'n'drop legacy en iframe) — porte un toggle New/Old propre : « New » = canvas React. */
const KEY_EDITION = 'meliscms_page_edition'

type PageTabComp = (p: { idPage: number }) => JSX.Element
/** Onglets modulaires (vues auto-fetch, données de leur module). Propriétés/SEO sont gérés à part (contrôlés). */
const SELF_TABS: Record<string, PageTabComp> = {
  meliscms_page_languages: LanguagesTab,
  melispagehistoric_historic: HistoricTab,
  meliscms_page_analytics_tab: AnalyticsTab,
  meliscms_page_script_editor: ScriptsTab,
  melissb_page_versioning: VersioningTab,
  melissb_page_comments: CommentsTab,
}
const CONTROLLED = new Set([KEY_PROPERTIES, KEY_SEO])

/**
 * Notification native de la coquille (mêmes toasts que les outils legacy). La brique tourne dans
 * la MÊME fenêtre que l'hôte → un postMessage vers `window` est capté par <Notifications> du shell.
 */
type NotifField = { label: string; messages: string[] }
function notify(kind: 'ok' | 'ko', title: string, message: string, fields?: NotifField[]) {
  window.postMessage({ __melisNotif: true, kind, title, message, fields }, '*')
}

// Registre modulaire (pour futurs bricks de modules qui fourniront leur onglet natif).
type PageTabRegistry = { tabs: Record<string, PageTabComp>; v: number }
// Hook de sauvegarde d'un onglet modulaire : la sauvegarde TRANSVERSE (« Sauvegarder »/« Publier »
// en haut de l'éditeur) invoque ces hooks pour que chaque onglet (ex. Open Graph de melis-cms-share)
// persiste ses données SANS bouton propre — sauvegarde centralisée, comme en legacy.
type PageSaveHook = (idPage: number) => Promise<void>
const w = window as unknown as {
  __melisPageTabRegistry?: PageTabRegistry
  __melisRegisterPageTab?: (k: string, c: PageTabComp) => void
  __melisPageSaveHooks?: Record<string, PageSaveHook>
  __melisRegisterPageSaveHook?: (k: string, hook: PageSaveHook | null) => void
}
if (!w.__melisPageTabRegistry) {
  w.__melisPageTabRegistry = { tabs: {}, v: 0 }
  w.__melisRegisterPageTab = (k, c) => { w.__melisPageTabRegistry!.tabs[k] = c; w.__melisPageTabRegistry!.v++; window.dispatchEvent(new CustomEvent('melis:page-tabs-changed')) }
}
if (!w.__melisPageSaveHooks) {
  w.__melisPageSaveHooks = {}
  w.__melisRegisterPageSaveHook = (k, hook) => { if (hook) w.__melisPageSaveHooks![k] = hook; else delete w.__melisPageSaveHooks![k] }
}
// Exécute les hooks de sauvegarde des onglets modulaires montés (séquentiel ; une erreur remonte
// à l'appelant → notification KO du shell). Appelé APRÈS un savePage/publishPage legacy réussi.
async function runPageSaveHooks(idPage: number): Promise<void> {
  for (const hook of Object.values(w.__melisPageSaveHooks ?? {})) await hook(idPage)
}
function isNativeTab(key: string): boolean {
  return CONTROLLED.has(key) || !!SELF_TABS[key] || !!w.__melisPageTabRegistry?.tabs[key]
}

type StructTab = { key: string; label: string; icon: string | null; cap: string }
type StructBtn = { key: string; label: string; cap: string; children?: { key: string; label: string }[] }
type Structure = { idPage: number; header: PageHeader; tabs: StructTab[]; buttons: StructBtn[] }
type PageHeader = { pageName: string | null; status: string | null; hasDraft: boolean; online: boolean; editDate: string | null; editor: string | null }
type Edit = { props: PropsData; seo: SeoData; refs: Refs }

interface CapsApi { can: (cap: string) => boolean; loaded: boolean }
function useCaps(melisKey: string): CapsApi {
  const host = window as unknown as { __melisUseCaps?: (k: string) => CapsApi }
  if (host.__melisUseCaps) return host.__melisUseCaps(melisKey)
  return { can: () => true, loaded: true }
}

function toolSrc(id: string): string {
  if (id === 'new' || id.startsWith('new~')) {
    const father = id.startsWith('new~') ? id.slice('new~'.length) : '0'
    return `/melis/react-tool-page?key=meliscms_page_creation&idPage=0&idFatherPage=${encodeURIComponent(father)}`
  }
  return `/melis/react-tool-page?key=meliscms_page&idPage=${encodeURIComponent(id)}`
}

// ── Icônes d'action (SVG inline, lucide-like) ──
function Icon({ name, size = 13 }: { name: string; size?: number }) {
  const c = { width: size, height: size, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round' as const, strokeLinejoin: 'round' as const }
  const P: Record<string, JSX.Element> = {
    plus: <><path d="M12 5v14M5 12h14" /></>,
    save: <><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" /><path d="M17 21v-8H7v8M7 3v5h8" /></>,
    eraser: <><path d="m7 21-4.3-4.3a1 1 0 0 1 0-1.4L15 3l6 6-9.6 9.6a2 2 0 0 1-1.4.6H7z" /><path d="M22 21H7" /></>,
    publish: <><path d="M12 13V3M8 7l4-4 4 4" /><path d="M20 17v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2" /></>,
    trash: <><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /></>,
    eye: <><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" /><circle cx="12" cy="12" r="3" /></>,
    monitor: <><rect x="2" y="3" width="20" height="14" rx="2" /><path d="M8 21h8M12 17v4" /></>,
    copy: <><rect x="9" y="9" width="13" height="13" rx="2" /><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" /></>,
    workflow: <><rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" /><path d="M10 6.5h4a2 2 0 0 1 2 2V14" /></>,
    mail: <><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 6-10 7L2 6" /></>,
    unlock: <><rect x="3" y="11" width="18" height="11" rx="2" /><path d="M7 11V7a5 5 0 0 1 9.9-1" /></>,
    smartphone: <><rect x="5" y="2" width="14" height="20" rx="2" /><path d="M12 18h.01" /></>,
    tablet: <><rect x="4" y="2" width="16" height="20" rx="2" /><path d="M12 18h.01" /></>,
    globe: <><circle cx="12" cy="12" r="10" /><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20" /></>,
    online: <><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" /><circle cx="12" cy="12" r="3" /></>,
    // Replier/déplier l'en-tête : simple chevron (^ = replier, v = déplier).
    'chevron-up': <><path d="m6 15 6-6 6 6" /></>,
    'chevron-down': <><path d="m6 9 6 6 6-6" /></>,
  }
  return <svg {...c}>{P[name] ?? <circle cx="12" cy="12" r="9" />}</svg>
}
/** icône pour un SOUS-item (options de « Affichage » : desktop/tablette/mobile ; « Voir » : preview/online). */
function childIcon(key: string, label: string): string {
  const s = (key + ' ' + label).toLowerCase()
  if (/(mobile|phone|smartphone)/.test(s)) return 'smartphone'
  if (/(tablet|tablette)/.test(s)) return 'tablet'
  if (/(desktop|ordinateur|bureau)/.test(s)) return 'monitor'
  if (/(online|en ligne|seeonline)/.test(s)) return 'online'
  if (/(preview|aper)/.test(s)) return 'eye'
  return 'globe'
}
/** clé/cap bouton → nom d'icône. */
function iconFor(b: StructBtn): string {
  const k = b.key
  if (k.includes('_new')) return 'plus'
  if (k.includes('_save')) return 'save'
  if (k.includes('_clear')) return 'eraser'
  if (k.includes('_publish')) return 'publish'
  if (k.includes('_delete')) return 'trash'
  if (k.includes('_view')) return 'eye'
  if (k.includes('_display')) return 'monitor'
  if (k.includes('_duplicate')) return 'copy'
  if (k.includes('workflow')) return 'workflow'
  if (k.includes('newsletter')) return 'mail'
  if (k.includes('unlock')) return 'unlock'
  return 'save'
}
/** Section d'appartenance d'un bouton (barre organisée par groupes séparés, plutôt qu'une longue ligne). */
function groupOf(key: string): number {
  if (/action_(save|publish)/.test(key)) return 0            // Sauvegarder · Publier
  if (/action_(new|duplicate|clear|delete)/.test(key)) return 1 // Nouvelle page · Dupliquer · Effacer brouillon · Supprimer
  if (/action_(view|display)/.test(key)) return 2            // Voir · Affichage
  return 3                                                   // Modulaires (newsletter, workflow, unlock…)
}
/** Rang d'affichage EXPLICITE d'un bouton dans sa section (l'ordre config ne suffit pas). Les
 * boutons modulaires (non listés) gardent l'ordre config (rang par défaut élevé, tri stable). */
const BTN_ORDER = ['action_save', 'action_publish', 'action_new', 'action_duplicate', 'action_clear', 'action_delete', 'action_view', 'action_display']
function orderOf(key: string): number {
  const i = BTN_ORDER.findIndex((k) => key.endsWith(k))
  return i === -1 ? 99 : i
}

export default function CmsPage({ active = true }: { active?: boolean }) {
  const tr = peT() // dictionnaire i18n (référence stable : DICT[lang] du BO) → sûr hors deps des useCallback
  const narrow = useIsNarrow()
  const vw = useViewportWidth()
  const { id } = useParams()
  const navigate = useNavigate()
  const { can, loaded: capsLoaded } = useCaps(TOOL_KEY)
  const [mode, setMode] = useState<ViewMode>('react')
  // Toggle New/Old SCOPÉ à l'onglet Édition : « New » = canvas React, « Old » = l'éditeur drag'n'drop
  // legacy dans l'iframe. Défaut « New » (le nouvel éditeur est celui présenté par défaut). L'iframe Old
  // reste montée dessous quand « New » est actif (le canvas la recouvre) → Sauvegarder/Publier legacy inchangés.
  const [editionCanvas, setEditionCanvas] = useState<ViewMode>('react')
  // Responsive preview device for the React canvas (top toolbar « Affichage » desktop/tablette/mobile).
  // In « Old » the same button drives the legacy iframe; in « New » it resizes the canvas instead.
  const [canvasDevice, setCanvasDevice] = useState<'desktop' | 'tablet' | 'mobile'>('desktop')

  const [frozenId, setFrozenId] = useState<string | undefined>(id)
  useEffect(() => { if (active) setFrozenId(id) }, [active, id])
  const current = (active ? id : frozenId) ?? null

  const [opened, setOpened] = useState<string[]>(() => (current ? [current] : []))
  useEffect(() => { if (current) setOpened((o) => (o.includes(current) ? o : [...o, current])) }, [current])

  const isCreation = current === 'new' || (current?.startsWith('new~') ?? false)
  const showChrome = mode === 'react' && !isCreation

  // ── En-tête repliable (MOBILE uniquement) ──────────────────────────────────
  // Sur téléphone, le chrome de l'éditeur (titre + ~5 rangées de boutons + 2 rangées d'onglets)
  // occupait la moitié de l'écran, ne laissant presque rien pour éditer le contenu. Le chevron
  // replie tout sauf une barre compacte (nom de page + statut + chevron) — l'affordance reste
  // donc toujours visible. Préférence persistée : replier une fois vaut pour les pages suivantes.
  // Desktop : `narrow` est faux → `chromeCollapsed` toujours faux, rendu strictement inchangé.
  const [headerOpen, setHeaderOpen] = useState(() => {
    try { return localStorage.getItem(HEADER_PREF_KEY) !== '0' } catch { return true }
  })
  useEffect(() => { try { localStorage.setItem(HEADER_PREF_KEY, headerOpen ? '1' : '0') } catch { /* quota/privé */ } }, [headerOpen])
  const chromeCollapsed = narrow && !headerOpen

  // Multi-tab: struct (structure) and edit (Properties/SEO/refs) are kept PER PAGE and loaded ONCE.
  // We must NOT refetch them when switching tabs — the user may have unsaved changes on a tab's
  // Properties/SEO (or be mid-edit), and reloading would silently wipe that work (ticket 0010738).
  // Switching a tab just re-reads the retained in-memory state; a page is fetched only on first open
  // (or after an explicit reload via reloadEdition). Save/Publish therefore always act on the
  // CURRENT page's own state → never publishes page A with page B's data.
  const [structByPage, setStructByPage] = useState<Record<string, Structure>>({})
  const [editByPage, setEditByPage] = useState<Record<string, Edit>>({})
  // Incrémenté par reloadEdition : les effets de chargement (structure + Propriétés/SEO) l'écoutent
  // pour REFETCH après invalidation. Sans ça, vider structByPage/editByPage ne suffit pas (les effets
  // ne dépendent que de `current`) → l'onglet Propriétés/SEO restait bloqué sur « Chargement… »
  // après un changement de template (ticket 0010873, retour).
  const [reloadNonce, setReloadNonce] = useState(0)
  const struct = current ? (structByPage[current] ?? null) : null
  const edit = current ? (editByPage[current] ?? null) : null
  const [lock, setLock] = useState<{ locked: boolean; byUser: string | null; byMe: boolean; since: string | null } | null>(null)
  const [activeTab, setActiveTab] = useState<string | null>(null)
  const [mountedTabs, setMountedTabs] = useState<Set<string>>(new Set())
  const [openMenu, setOpenMenu] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)
  // Pages dont l'édition est RÉELLEMENT chargée : le canvas imbriqué (.melis-iframe, la page rendue
  // en mode édition DANS l'iframe tool-page) a fini de charger (readyState 'complete' + contenu) et
  // les zones/plugins sont activés. Le `onLoad` du tool-page arrive TROP TÔT (canvas encore en cours
  // → session PHP potentiellement repeuplée/partielle). Tant qu'une page n'est pas ici, Sauvegarder/
  // Publier sont bloqués (cf. editionReady) — sinon on enverrait un XML partiel.
  const [readyPages, setReadyPages] = useState<Set<string>>(new Set())
  const [toast, setToast] = useState<{ ok: boolean; text: string } | null>(null)
  const [unlockOpen, setUnlockOpen] = useState(false)
  const [deleteOpen, setDeleteOpen] = useState(false) // modal React de confirmation de suppression
  const [clearOpen, setClearOpen] = useState(false) // modal React de confirmation d'effacement du brouillon
  const [wfOpen, setWfOpen] = useState(false) // modal Workflow (mutualisée, fournie par melis-small-business)
  const [nlOpen, setNlOpen] = useState(false) // modal « Envoyer la newsletter » (mutualisée, fournie par melis-newsletter)
  const [unlocking, setUnlocking] = useState(false)
  const [, bumpTabs] = useState(0)
  useEffect(() => { const on = () => bumpTabs((n) => n + 1); window.addEventListener('melis:page-tabs-changed', on); return () => window.removeEventListener('melis:page-tabs-changed', on) }, [])

  // Édition prête à être sauvegardée : le canvas d'édition de la page courante a FINI de charger
  // (cf. readyPages, alimenté par le poller) ET les Propriétés/SEO sont chargés. Sinon → boutons off.
  const editionReady = !!current && !isCreation && readyPages.has(current) && !!edit

  // Détecte la fin RÉELLE du chargement de l'édition d'une page : on traverse l'iframe tool-page
  // (même origine) → le canvas imbriqué `.melis-iframe` (page rendue en mode édition) doit avoir du
  // contenu (body.children > 0, exclut l'état vide initial) ET readyState === 'complete' (window.load
  // → zones/plugins activés). NB : le contenu arrive par paliers avec de longs plateaux → NE PAS se
  // fier à la stabilité du nombre d'enfants (faux positif) ; seul 'complete' fait foi.
  const isEditionLoaded = useCallback((cid: string): boolean => {
    const ifr = frameRef.current[cid]
    if (!ifr) return false
    let td: Document | null = null
    try { td = ifr.contentDocument } catch { return false }
    if (!td) return false
    const canvas = td.querySelector('.meliscms-page-tab-edition iframe.melis-iframe, iframe.melis-iframe') as HTMLIFrameElement | null
    if (!canvas) return false
    let cd: Document | null = null
    try { cd = canvas.contentDocument } catch { return false }
    if (!cd || !cd.body || cd.body.children.length === 0) return false
    return cd.readyState === 'complete'
  }, [])

  // Poller : arme editionReady quand le canvas de la page courante est complètement chargé. Confirme
  // 2 lectures 'complete' d'affilée (anti-flicker) + filet de sécurité 30s (page qui ne "complete"
  // jamais : on débloque si le canvas a du contenu et n'est plus en 'loading').
  useEffect(() => {
    if (!current || isCreation || readyPages.has(current)) return
    const cid = current
    const startedAt = Date.now()
    let stable = 0
    const iv = window.setInterval(() => {
      if (isEditionLoaded(cid)) stable++; else stable = 0
      let fallback = false
      if (Date.now() - startedAt > 30000) {
        const ifr = frameRef.current[cid]
        try {
          const canvas = ifr?.contentDocument?.querySelector('.meliscms-page-tab-edition iframe.melis-iframe, iframe.melis-iframe') as HTMLIFrameElement | null
          const cd = canvas?.contentDocument
          fallback = !!cd && !!cd.body && cd.body.children.length > 0 && cd.readyState !== 'loading'
        } catch { /* */ }
      }
      if (stable >= 2 || fallback) { window.clearInterval(iv); setReadyPages((s) => (s.has(cid) ? s : new Set(s).add(cid))) }
    }, 400)
    return () => window.clearInterval(iv)
  }, [current, isCreation, readyPages, isEditionLoaded])

  // structure (onglets/boutons/en-tête) — stockée PAR PAGE (setStructByPage). Sert à la fois au 1er
  // chargement et aux rafraîchissements explicites (après save/publish/renommage).
  const refreshStructure = useCallback(async (idPage: string) => {
    try { const d = await apiGet<Structure>(`structure?idPage=${encodeURIComponent(idPage)}`); setStructByPage((m) => ({ ...m, [idPage]: d })); setActiveTab((t) => t ?? d.tabs?.[0]?.key ?? null) } catch { /* garder l'existant */ }
  }, [])
  // Chargement de la structure UNE SEULE FOIS par page (pas de refetch au switch d'onglet) :
  // `struct` est dérivé de `structByPage[current]`, donc null (et non la page précédente) le temps du
  // 1er chargement. Le flash « page précédente » de l'ancien modèle mono-`struct` ne peut donc plus se
  // produire ; `structMatches` reste une garde défensive : on ne considère `struct` pertinent que s'il
  // porte bien sur la page courante (`struct.idPage === current`).
  const structMatches = !!struct && String(struct.idPage) === String(current)

  // Libellé de l'onglet = vrai nom de la page. En ouverture depuis l'arbre, PageTree pose déjà le
  // nom ; mais sur une navigation DIRECTE vers /melis-cms/page/:id (deep-link, F5, ou l'œil du
  // plugin dashboard Workflow), l'hôte n'a que le fallback « Page N » (deriveTabLabel). Dès que la
  // structure (de la BONNE page) est chargée, on connaît `header.pageName` → on renomme l'onglet
  // (upsert par id). Gaté par `structMatches` : sinon le `struct` périmé renommerait l'onglet de la
  // page courante avec le nom de la page précédente (flash de libellé).
  useEffect(() => {
    if (!structMatches) return
    const name = struct?.header?.pageName?.trim()
    if (!current || isCreation || !name) return
    const path = `/melis-cms/page/${current}`
    ;(window as unknown as { __melisOpenTab?: (t: { id: string; label: string; path: string }) => void })
      .__melisOpenTab?.({ id: path, label: `${current} - ${name}`, path })
  }, [structMatches, struct, current, isCreation])

  // état partagé Propriétés + SEO + refs (chargé UNE fois par page → pas de refetch au switch)
  useEffect(() => {
    if (!current || isCreation) return
    if (!structByPage[current]) refreshStructure(current)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [current, isCreation, refreshStructure, reloadNonce])

  // Propriétés + SEO + refs — chargés UNE SEULE FOIS par page, JAMAIS refetch au switch : l'onglet
  // conserve les saisies non sauvegardées de l'utilisateur (ticket 0010738). Rechargé seulement si
  // la page n'est pas encore en cache (1re ouverture) ou après invalidation via reloadEdition.
  useEffect(() => {
    if (!current || isCreation || editByPage[current]) return
    let x = false; const idPage = current
    Promise.all([
      apiGet<PropsData>(`properties?idPage=${idPage}`),
      apiGet<SeoData>(`seo?idPage=${idPage}`),
      apiGet<Refs>(`refs?idPage=${idPage}`),
    ]).then(([props, seo, refs]) => {
      if (x) return
      // Baseline du template RENDU = le page_tpl_id serveur (celui que le canvas legacy affiche à
      // l'ouverture). Capturé ici, à froid, avant toute saisie utilisateur → pas de course avec
      // editionReady. reloadEdition vide editByPage → cet effet re-tourne et recapture le nouveau template.
      renderedTplRef.current[idPage] = String(props.templateId ?? '')
      setEditByPage((m) => (m[idPage] ? m : { ...m, [idPage]: { props, seo, refs } }))
    }).catch(() => {})
    return () => { x = true }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [current, isCreation, reloadNonce])

  // état de VERROU de la page (modulaire, small-business) → bouton Débloquer + bandeau conditionnels
  useEffect(() => {
    if (!current || isCreation) { setLock(null); return }
    let x = false
    apiGet<{ locked: boolean; byUser: string | null; byMe: boolean; since: string | null }>(`lock?idPage=${current}`)
      .then((l) => { if (!x) setLock(l) }).catch(() => { if (!x) setLock(null) })
    return () => { x = true }
  }, [current, isCreation, struct])

  // marque l'onglet actif comme monté (il le reste → pas de refetch)
  useEffect(() => { if (activeTab && isNativeTab(activeTab)) setMountedTabs((s) => (s.has(activeTab) ? s : new Set(s).add(activeTab))) }, [activeTab])

  // VERROU (mécanisme PageLock, small-business) — comme le legacy (MelisSBPageLockPageActionButtonsAndTabsListener) :
  //  • verrou d'un AUTRE utilisateur → cacher Sauvegarder/Effacer/Publier/Supprimer + montrer « Débloquer » (reprise) + bandeau.
  //  • verrou par MOI (propriétaire) → RIEN (édition normale, pas de bandeau, pas de « Débloquer »). Le verrou me protège
  //    des autres, il ne me bloque jamais moi-même.
  const lockedByOther = !!lock?.locked && !lock.byMe
  const LOCK_HIDDEN_BTN = ['action_save', 'action_clear', 'action_publish', 'action_delete']
  const visibleTabs = (struct?.tabs ?? [])
    .filter((t) => !capsLoaded || can(t.cap))
    .filter((t) => !lockedByOther || !t.key.includes('versioning')) // versioning caché si verrouillé par un autre (legacy)
  const visibleButtons = (struct?.buttons ?? [])
    .filter((b) => !capsLoaded || can(b.cap))
    .filter((b) => !b.key.includes('unlock') || lockedByOther) // « Débloquer » SEULEMENT si verrouillé par un AUTRE user
    .filter((b) => !lockedByOther || !LOCK_HIDDEN_BTN.some((k) => b.key.endsWith(k))) // actions d'édition cachées si verrou d'un autre
    // « Envoyer la newsletter » UNIQUEMENT si la page est de type NEWSLETTER (parité legacy MelisNewsletterSendTool).
    .filter((b) => !b.key.includes('newsletter') || edit?.props.type === 'NEWSLETTER')
    // « Affichage » (aperçu desktop/tablette/mobile) n'a aucun sens sur mobile — on est déjà sur mobile
    // (ticket 0010840). Masqué UNIQUEMENT en viewport étroit ; conservé aux autres résolutions.
    .filter((b) => !narrow || !b.key.endsWith('action_display'))
  // Boutons regroupés en sections (Édition/publication · Page · Aperçu · Modulaires), séparées par un trait.
  const btnGroups = [0, 1, 2, 3]
    .map((gi) => visibleButtons.filter((b) => groupOf(b.key) === gi).sort((a, b) => orderOf(a.key) - orderOf(b.key)))
    .filter((g) => g.length)
  useEffect(() => {
    if (!capsLoaded || !visibleTabs.length) return
    if (!visibleTabs.some((t) => t.key === activeTab)) driveTab(visibleTabs[0].key)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [capsLoaded, struct])

  // ── iframe legacy (pilotage) ──
  const frameRef = useRef<Record<string, HTMLIFrameElement | null>>({})
  // Template RENDU dans le canvas d'édition, par page (= le page_tpl_id serveur au moment où l'édition
  // a été (re)chargée). Sert à détecter, après Sauvegarder/Publier, un changement de template : le
  // canvas legacy rend le template FIGÉ à l'ouverture et ne se met pas à jour tout seul (ticket 0010873)
  // → si le template a changé, on recharge l'édition pour que l'utilisateur reparte sur le bon template.
  const renderedTplRef = useRef<Record<string, string>>({})
  // Pages dont l'iframe est PRÊTE À AFFICHER : on la garde `visibility:hidden` jusqu'à ce que le chrome
  // legacy (barre de boutons + onglets) soit masqué (applyIframeChrome au `load`) → sinon on voit la
  // barre d'actions legacy « flasher » à l'ouverture avant d'être cachée. Révélée juste après.
  const [revealed, setRevealed] = useState<Set<string>>(new Set())
  // Tableaux legacy (DataTables) des onglets — Versioning, Historique, Commentaires… : l'extension
  // **Responsive** est bien chargée (`dt-responsive dtr-inline`) mais elle calcule les largeurs à
  // l'INIT, alors que l'onglet est encore masqué / que l'iframe n'a pas sa largeur finale. Résultat
  // sous 640px : le tableau garde sa largeur « desktop » (562px mesurés dans un conteneur de 348px),
  // déborde et fait défiler toute la page latéralement — au lieu de replier ses colonnes.
  // Un simple événement `resize` DANS l'iframe suffit à le faire recalculer (vérifié : le tableau
  // repasse à 348px, prend la classe `collapsed` et les colonnes en trop passent dans la ligne
  // dépliable « + »). On ne touche donc à AUCUNE API DataTables : pas de dépendance à sa version.
  // Déclenché à l'ouverture puis à chaque clic dans la barre d'onglets legacy — les deux moments où
  // un tableau jusqu'ici masqué devient visible avec une largeur périmée (le pilotage React d'un
  // onglet passe par un .click() sur le lien legacy, il est donc couvert lui aussi).
  // Narrow uniquement : sur desktop le tableau tient déjà, le recalcul serait un no-op.
  const installNarrowTableFix = useCallback((iframe: HTMLIFrameElement, doc: Document) => {
    const win = iframe.contentWindow as (Window & { __melisNarrowTables?: boolean }) | null
    if (!narrow || !win) return
    const kick = () => [0, 200, 700].forEach((ms) => win.setTimeout(() => { try { win.dispatchEvent(new win.Event('resize')) } catch { /* */ } }, ms))
    if (!win.__melisNarrowTables) {
      win.__melisNarrowTables = true
      doc.addEventListener('click', (e) => { if ((e.target as HTMLElement | null)?.closest?.('ul.tabs-label')) kick() }, true)
    }
    kick()
  }, [narrow])

  const applyIframeChrome = useCallback((iframe: HTMLIFrameElement) => {
    try {
      const doc = iframe.contentDocument as (Document & { __melisChromeStyle?: HTMLStyleElement }) | null
      if (!doc) return
      let style = doc.__melisChromeStyle
      if (!style) { style = doc.createElement('style'); doc.__melisChromeStyle = style; doc.head?.appendChild(style) }
      // On masque le chrome LEGACY redondant avec la coquille React : l'en-tête de page, la barre
      // d'onglets (ul.tabs-label) ET son conteneur `.widget-head` (sinon il laisse une bande blanche
      // ~69px en haut de l'édition), + le padding-top du pane d'édition.
      style.textContent = showChrome
        ? `[data-melisKey='meliscms_pagehead']{display:none !important;}`
          + `ul.tabs-label.nav-tabs{display:none !important;}`
          + `[data-melisKey='meliscms_tabs'] > .widget-tabs > .widget-head{display:none !important;}`
          + `[data-melisKey='meliscms_page_edition']{padding-top:0 !important;}`
        // Mode « Old » (chrome legacy visible) sur narrow : la barre d'onglets legacy est faite de
        // `li` FLOTTANTS dans un `ul` et un `.widget-head` à HAUTEUR FIXE (69/70px = une ligne).
        // Sous 640px les 9 onglets passent sur 3 lignes : les lignes 2 et 3 débordent hors du head
        // et sont recouvertes par le `.widget-body` (fond blanc) → seuls Edition/Propriétés/SEO
        // restent visibles. `height:auto` suffit (le `ul` est inline-block = BFC, il contient donc
        // bien ses flottants) et le body reprend sa place dans le flux. Gardé sur narrow seulement :
        // en une seule ligne, `auto` vaudrait 70px contre 69px déclarés → 1px de décalage desktop.
        : narrow
          ? `[data-melisKey='meliscms_tabs'] > .widget-tabs > .widget-head{height:auto !important;}`
            + `[data-melisKey='meliscms_tabs'] ul.tabs-label.nav-tabs{height:auto !important;}`
          : ''
      installNarrowTableFix(iframe, doc)
    } catch { /* */ }
  }, [showChrome, narrow, installNarrowTableFix])

  const driveTab = useCallback((tabKey: string) => {
    setActiveTab(tabKey)
    if (isNativeTab(tabKey)) return // onglet natif → pas de pilotage legacy
    try {
      const doc = current ? frameRef.current[current]?.contentDocument : null
      if (!doc) return
      const pane = doc.querySelector(`.tab-pane[data-melisKey='${tabKey}']`) as HTMLElement | null
      if (pane?.id) (doc.querySelector(`a[href='#${pane.id}']`) as HTMLElement | null)?.click()
    } catch { /* */ }
  }, [current])

  const driveButton = useCallback((btnKey: string) => {
    setOpenMenu(null)
    // « Affichage » desktop/tablette/mobile : en mode « New » le canvas React recouvre l'iframe legacy,
    // donc piloter l'iframe (invisible) ne fait rien. On redimensionne le canvas à la place.
    const dev = /action_display_(mobile|tablet|desktop)$/.exec(btnKey)
    if (dev && editionCanvas === 'react') { setCanvasDevice(dev[1] as 'desktop' | 'tablet' | 'mobile'); return }
    try {
      const doc = current ? frameRef.current[current]?.contentDocument : null
      if (!doc) return
      const el = doc.querySelector(`[data-melisKey='${btnKey}']`) as HTMLElement | null
      const clickable = (el?.querySelector('a,button') as HTMLElement | null) ?? el
      clickable?.click()
    } catch { /* */ }
  }, [current, editionCanvas])

  // ── Sauvegarde / publication : REBRANCHÉES sur les endpoints LEGACY (aucun PHP historique modifié).
  // Un seul bouton « Sauvegarder » envoie TOUTES les infos des onglets d'un coup, exactement comme le
  // legacy (melisCms.js:savePage) : le POST porte Propriétés + SEO (noms de champs EXACTS des forms
  // `pageproperties`/`pageseo`), et la chaîne serveur (meliscms_page_save_start) sauvegarde AUSSI le
  // XML de l'édition — lu depuis la SESSION PHP peuplée par le drag'n'drop de l'iframe — dans
  // melis_cms_page_saved.page_content. saveEdition ne réécrit le contenu QUE si la session est peuplée
  // (sinon l'existant est préservé → pas de perte de contenu).
  type LegacyResp = { success?: number; textTitle?: string; textMessage?: string; errors?: unknown; datas?: { idPage?: number | string } }

  // Corps urlencodé attendu par savePage/publishPage, reconstruit depuis l'état React agrégé (`edit`).
  const buildLegacyBody = useCallback((): string => {
    const p = edit!.props, s = edit!.seo
    const b = new URLSearchParams()
    b.set('page_id', String(p.idPage || current || ''))
    b.set('page_name', p.name ?? '')
    b.set('page_type', p.type ?? 'PAGE')
    b.set('plang_lang_id', String(p.langId ?? ''))
    b.set('page_menu', p.menu ?? 'LINK')
    b.set('page_tpl_id', String(p.templateId ?? ''))
    b.set('style_id', p.styleId ? String(p.styleId) : '')
    b.set('page_taxonomy', p.taxonomy ?? '')
    b.set('page_search_type', 'tr_meliscms_page_tab_properties_search_type_option1')
    b.set('pseo_meta_title', s.metaTitle ?? '')
    b.set('pseo_meta_description', s.metaDesc ?? '')
    b.set('pseo_url', s.url ?? '')
    b.set('pseo_url_redirect', s.urlRedirect ?? '')
    b.set('pseo_url_301', s.url301 ?? '')
    b.set('pseo_canonical', s.canonical ?? '')
    return b.toString()
  }, [edit, current])

  const postLegacyPage = useCallback(async (url: string): Promise<LegacyResp> => {
    const res = await fetch(url, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: buildLegacyBody(),
    })
    return await res.json().catch(() => ({})) as LegacyResp
  }, [buildLegacyBody])

  // Détail des erreurs de champ (URL SEO déjà utilisée, champ requis…) — cf. legacy-errors.ts pour
  // les formes hétérogènes renvoyées par le legacy.
  const errorFields = (data: LegacyResp): NotifField[] => legacyErrorFields(data.errors, tr.errorField)

  // Corps du toast d'échec : détail des champs si on en a (le message legacy générique n'explique rien),
  // sinon le message legacy — jamais une clé `tr_…` brute.
  const failMessage = (data: LegacyResp, fields: NotifField[], fallback: string): string =>
    fields.length ? tr.fixErrorsBelow : legacyText(data.textMessage, fallback)

  // Recharge l'iframe d'édition de la page courante (refléter l'état serveur après clear/publish).
  // On la retire de readyPages → Sauvegarder/Publier re-bloqués jusqu'au rechargement complet du canvas.
  const reloadEdition = useCallback(() => {
    if (!current) return
    setReadyPages((s) => { if (!s.has(current)) return s; const n = new Set(s); n.delete(current); return n })
    setRevealed((s) => { if (!s.has(current)) return s; const n = new Set(s); n.delete(current); return n }) // re-masquer pendant le rechargement (évite le flash du chrome legacy)
    // Invalide le cache par-page de CETTE page (clear/publish/reload explicite) → les effets
    // « chargé une fois » refetchent les Propriétés/SEO + la structure à jour. NB : reloadEdition
    // n'est JAMAIS appelé sur un simple switch d'onglet — donc le travail non sauvegardé est préservé.
    setEditByPage((m) => { if (!m[current]) return m; const n = { ...m }; delete n[current]; return n })
    setStructByPage((m) => { if (!m[current]) return m; const n = { ...m }; delete n[current]; return n })
    delete renderedTplRef.current[current] // baseline recapturée au refetch des propriétés
    // Force le REFETCH des effets « chargés une fois » (structure + Propriétés/SEO) : ils écoutent
    // reloadNonce. Sans ça, l'onglet Propriétés/SEO restait bloqué sur « Chargement… » (ticket 0010873).
    setReloadNonce((n) => n + 1)
    const f = frameRef.current[current]
    try { if (f) f.src = toolSrc(current) } catch { /* */ }
  }, [current])

  // Libère le VERROU de la page (supprime la ligne melis_sb_page_locked) — comme le legacy à la
  // publication (meliscms_page_publish_end → PageLock::unlockPage). On le fait après un save/publish
  // réussi : le verrou est un état d'édition en cours, pas de raison de le garder une fois figé. Effet
  // concret : le cadenas du treeview DISPARAÎT (suppression en base, pas juste masquage). Le verrou se
  // recrée tout seul à la prochaine édition d'un plugin. Endpoint modulaire (small-business) : si absent
  // → no-op silencieux.
  const releaseLock = useCallback(async (idPage: string) => {
    try { await apiPost('unlock', { idPage: Number(idPage) }) } catch { /* module PageLock absent → rien */ }
    setLock({ locked: false, byUser: null, byMe: false, since: null })
  }, [])

  // Sauvegarde globale (« Sauvegarder ») → savePage legacy (Propriétés + SEO + XML d'édition).
  const saveAll = useCallback(async () => {
    if (!edit || !current || !editionReady) return
    setSaving(true)
    try {
      const data = await postLegacyPage(`/melis/MelisCms/Page/savePage?idPage=${encodeURIComponent(current)}&fatherPageId=`)
      if (data.success === 1) {
        await runPageSaveHooks(Number(current)) // onglets modulaires (ex. Open Graph) : save transverse
        notify('ok', (data.textTitle || tr.notifSave).trim(), tr.pageSaved) // notif du shell (comme Publier)
        await releaseLock(current) // libère le verrou → le cadenas du tree disparaît
        window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh', { detail: { revealPageId: Number(current) } })) // nom/statut + cadenas → maj + déploie jusqu'à la page
        window.dispatchEvent(new CustomEvent('melis:cms-historic-refresh')) // save → nouvelle entrée d'historique
        refreshStructure(current) // rafraîchit le statut/en-tête
        // Template changé → le canvas legacy garde l'ancien template figé (il n'est rendu qu'à
        // l'ouverture) : recharger l'édition pour repartir sur le nouveau template (ticket 0010873).
        if (String(edit.props.templateId ?? '') !== renderedTplRef.current[current]) reloadEdition()
      } else {
        const fields = errorFields(data)
        notify('ko', (data.textTitle || tr.notifSave).trim(), failMessage(data, fields, tr.saveFailed), fields)
      }
    } catch (e) { notify('ko', tr.notifSave, (e as Error).message) } finally { setSaving(false) }
  }, [edit, current, editionReady, postLegacyPage, refreshStructure, releaseLock, reloadEdition])

  // Publier (« Publier ») → publishPage legacy : la chaîne sauvegarde (comme save) PUIS publie
  // (saved→published, page_status=1). Même corps que save (cf. melisCms.js:publishPage).
  const doPublish = useCallback(async () => {
    if (!edit || !current || !editionReady) return
    setSaving(true); setToast(null)
    try {
      const data = await postLegacyPage(`/melis/MelisCms/Page/publishPage?idPage=${encodeURIComponent(current)}`)
      if (data.success === 1) {
        await runPageSaveHooks(Number(current)) // onglets modulaires (ex. Open Graph) : save transverse
        notify('ok', (data.textTitle || tr.notifPublish).trim(), tr.pagePublished)
        await releaseLock(current) // libère le verrou (comme le legacy à la publication)
        window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh', { detail: { revealPageId: Number(current) } })) // statut online + cadenas → maj + déploie jusqu'à la page
        window.dispatchEvent(new CustomEvent('melis:cms-versioning-refresh')) // publier crée une version → recharge l'onglet Versioning
        window.dispatchEvent(new CustomEvent('melis:cms-historic-refresh')) // publier → nouvelle entrée d'historique
        refreshStructure(current) // en-tête : plus de brouillon, statut publié
        // Template changé → recharger l'édition (le canvas legacy fige le template à l'ouverture) : idem
        // que Sauvegarder, pour que l'utilisateur reparte sur le nouveau template (ticket 0010873).
        if (String(edit.props.templateId ?? '') !== renderedTplRef.current[current]) reloadEdition()
      } else {
        const fields = errorFields(data)
        notify('ko', (data.textTitle || tr.notifPublish).trim(), failMessage(data, fields, tr.publishFailed), fields)
      }
    } catch (e) { notify('ko', tr.notifPublish, (e as Error).message) } finally { setSaving(false) }
  }, [edit, current, editionReady, postLegacyPage, refreshStructure, releaseLock, reloadEdition])

  // Dépublier (switch Publié/Dépublié → OFF) → unpublishPage legacy (GET) : passe page_status=0 dans
  // la version publiée (la page sort du site, sans rien supprimer). Comme melisCms.js:unpublishPage.
  const doUnpublish = useCallback(async () => {
    if (!current) return
    setSaving(true)
    try {
      const res = await fetch(`/melis/MelisCms/Page/unpublishPage?idPage=${encodeURIComponent(current)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
      const data = await res.json().catch(() => ({})) as LegacyResp
      if (data.success === 1) {
        notify('ok', (data.textTitle || tr.notifUnpublish).trim(), tr.pageUnpublished)
        window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh', { detail: { revealPageId: Number(current) } })) // statut offline → maj + déploie jusqu'à la page
        window.dispatchEvent(new CustomEvent('melis:cms-historic-refresh')) // passage offline → nouvelle entrée d'historique
        refreshStructure(current) // en-tête : switch → Hors ligne
      } else {
        const fields = errorFields(data)
        notify('ko', (data.textTitle || tr.notifUnpublish).trim(), failMessage(data, fields, tr.unpublishFailed), fields)
      }
    } catch (e) { notify('ko', tr.notifUnpublish, (e as Error).message) } finally { setSaving(false) }
  }, [current, refreshStructure])

  // Switch Publié/Dépublié (comme le legacy .page-publishunpublish) : ON = publier, OFF = dépublier.
  const togglePublish = useCallback((toOnline: boolean) => { if (toOnline) doPublish(); else doUnpublish() }, [doPublish, doUnpublish])

  // Effacer le brouillon (« Effacer brouillon ») → clearSavedPage legacy (revient à la version publiée).
  // Effacement EFFECTIF (appelé par la modal React de confirmation ci-dessous, plus de confirm() natif).
  const doClear = useCallback(async () => {
    if (!current) return
    setSaving(true); setToast(null)
    try {
      const res = await fetch(`/melis/MelisCms/Page/clearSavedPage?idPage=${encodeURIComponent(current)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
      const data = await res.json().catch(() => ({})) as LegacyResp
      if (data.success === 1) {
        // clearSavedPage renvoie un textTitle/textMessage = clés `tr_...` NON traduites (traduites côté
        // JS legacy seulement) → on utilise nos propres libellés i18n pour éviter d'afficher « tr_… ».
        notify('ok', tr.notifDraft, tr.draftCleared)
        setClearOpen(false)
        window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh', { detail: { revealPageId: Number(current) } }))
        refreshStructure(current); reloadEdition() // le contenu revient à la version publiée
      } else {
        // clearSavedPage renvoie des clés tr_ (traduites côté legacy par melisHelper). React n'a pas
        // cette map → on traduit les cas connus, sinon on garde un message non-tr_ ou le générique.
        const raw = data.textMessage || ''
        const known: Record<string, string> = { 'tr_meliscms_delete_no_saved_page': tr.noDraftToClear }
        const msg = known[raw] || (raw && !raw.startsWith('tr_') ? raw : tr.draftFailed)
        notify('ko', tr.notifDraft, msg)
      }
    } catch (e) { notify('ko', tr.notifDraft, (e as Error).message) } finally { setSaving(false) }
  }, [current, refreshStructure, reloadEdition])

  // Supprimer la page (« Supprimer page ») → deletePage legacy, puis fermeture de l'onglet + refresh arbre.
  // Suppression EFFECTIVE (appelée par la modal React de confirmation). Ferme l'onglet + refresh arbre.
  const doDelete = useCallback(async () => {
    if (!current) return
    setSaving(true)
    try {
      const res = await fetch(`/melis/MelisCms/Page/deletePage?idPage=${encodeURIComponent(current)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
      const data = await res.json().catch(() => ({})) as LegacyResp
      if (data.success === 1) {
        notify('ok', (data.textTitle || tr.notifDelete).trim(), tr.pageDeleted)
        setDeleteOpen(false)
        window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh'))
        // Fermer l'onglet ne NAVIGUE pas (contrairement au bouton X de la Topbar). Sans navigation,
        // l'URL resterait sur la page supprimée — dont l'iframe vient d'être retirée de `opened` →
        // contenu blanc (ticket 0010702). On bascule d'abord vers une autre page encore ouverte
        // (ou le dashboard s'il n'en reste aucune), PUIS on ferme l'onglet, comme openCreatedPage.
        const rest = openedRef.current.filter((x) => x !== current && x !== 'new' && !x.startsWith('new~'))
        navigate(rest.length ? `/melis-cms/page/${rest[rest.length - 1]}` : '/')
        ;(window as unknown as { __melisCloseTab?: (id: string) => void }).__melisCloseTab?.(`/melis-cms/page/${current}`)
        setOpened((o) => o.filter((x) => x !== current))
      } else notify('ko', (data.textTitle || tr.notifDelete).trim(), legacyText(data.textMessage, tr.deleteFailedMsg))
    } catch (e) { notify('ko', tr.notifDelete, (e as Error).message) } finally { setSaving(false) }
  }, [current, navigate])

  // Nouvelle page (« Nouvelle page ») → route React de création, en enfant de la page courante.
  const openNewPage = useCallback(() => {
    const path = current ? `${NEW_PAGE_ROUTE}~${current}` : NEW_PAGE_ROUTE
    ;(window as unknown as { __melisOpenTab?: (t: { id: string; label: string; path: string }) => void }).__melisOpenTab?.({ id: path, label: tr.newPage, path })
    navigate(path)
  }, [current, navigate])

  // Dupliquer (« Dupliquer ») → réplique EXACTEMENT le bouton toolbar LEGACY (page-duplicate.tool.js) :
  // POST direct « page seule » sur PageDuplication/duplicate-page (duplique UNIQUEMENT la page courante,
  // SANS ses sous-pages), puis ouvre la page dupliquée. NE PAS confondre avec la modal d'arborescence du
  // clic droit du tree (TreeSites/duplicateTreePage), qui elle recopie toute la descendance.
  const doDuplicate = useCallback(async (sourceId?: number | string) => {
    const srcId = sourceId ?? current
    if (!srcId) return
    setSaving(true)
    try {
      const res = await fetch('/melis/MelisCms/PageDuplication/duplicate-page', {
        method: 'POST', credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: new URLSearchParams({ id: String(srcId) }).toString(),
      })
      const data = await res.json().catch(() => ({})) as LegacyResp & { pageId?: number | string; response?: { pageId?: number | string; name?: string; openPageAfterDuplicate?: boolean } }
      if (data.success === 1) {
        const newId = data.response?.pageId ?? data.pageId
        if (newId && data.response?.openPageAfterDuplicate !== false) {
          const editPath = `/melis-cms/page/${newId}`
          const label = `${newId} - ${(data.response?.name || `${tr.pageWord} ${newId}`).trim()}`
          ;(window as unknown as { __melisOpenTab?: (t: { id: string; label: string; path: string }) => void }).__melisOpenTab?.({ id: editPath, label, path: editPath })
          navigate(editPath)
        }
        window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh', { detail: { revealPageId: newId ? Number(newId) : undefined } })) // fait apparaître la copie dans l'arbre
        window.dispatchEvent(new CustomEvent('melis:cms-historic-refresh'))
        notify('ok', (data.textTitle || tr.notifDuplicate).trim(), legacyText(data.textMessage, tr.pageDuplicated))
      } else {
        const fields = errorFields(data)
        notify('ko', (data.textTitle || tr.notifDuplicate).trim(), failMessage(data, fields, tr.duplicateFailed), fields)
      }
    } catch (e) { notify('ko', tr.notifDuplicate, (e as Error).message) } finally { setSaving(false) }
  }, [current, navigate])

  // Distinguer par la CLÉ (le cap 'save' est partagé par Sauvegarder ET Effacer brouillon pour le gating,
  // mais leurs ACTIONS diffèrent : seul le vrai bouton Save déclenche la sauvegarde globale).
  // Déverrouillage NATIF React (pas lié à l'édition) : endpoint modulaire + refresh de l'état verrou.
  const doUnlock = useCallback(async () => {
    if (!current) return
    setUnlocking(true)
    try {
      await apiPost('unlock', { idPage: Number(current) })
      const l = await apiGet<{ locked: boolean; byUser: string | null; byMe: boolean; since: string | null }>(`lock?idPage=${current}`)
      setLock(l); setUnlockOpen(false); setToast({ ok: true, text: tr.pageUnlocked }); setTimeout(() => setToast(null), 3000)
      window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh', { detail: { revealPageId: Number(current) } })) // rafraîchit l'arbre + déploie jusqu'à la page → le cadenas disparaît
    } catch (e) { setToast({ ok: false, text: (e as Error).message }) } finally { setUnlocking(false) }
  }, [current])

  // Recharge l'édition + l'en-tête sur demande d'un onglet natif (ex. Versioning après une restauration).
  useEffect(() => {
    if (!current) return
    const onReload = () => { reloadEdition(); refreshStructure(current) }
    window.addEventListener('melis:cms-reload-edition', onReload)
    return () => window.removeEventListener('melis:cms-reload-edition', onReload)
  }, [current, reloadEdition, refreshStructure])

  const onButton = useCallback(async (b: StructBtn) => {
    setOpenMenu(null)
    if (b.key.includes('unlock')) { setUnlockOpen(true); return }            // Débloquer → modal React natif
    if (b.key.endsWith('action_save')) { await saveAll(); return }          // Sauvegarder → save global (legacy)
    if (b.key.endsWith('action_publish')) { await doPublish(); return }     // Publier → save puis PUBLIE (legacy)
    if (b.key.endsWith('action_clear')) { setClearOpen(true); return }       // Effacer brouillon → modal React de confirmation
    if (b.key.endsWith('action_delete')) { setDeleteOpen(true); return }     // Supprimer page → modal React de confirmation
    if (b.key.endsWith('action_new')) { openNewPage(); return }             // Nouvelle page → route React de création
    if (b.key.endsWith('action_duplicate')) { await doDuplicate(); return } // Dupliquer → POST « page seule » (legacy), PAS l'arborescence
    if (b.key.includes('workflow')) { setWfOpen(true); return }             // Flux de travail → modal Workflow mutualisée (small-business)
    if (b.key.includes('newsletter')) { setNlOpen(true); return }           // Envoyer la newsletter → modal React mutualisée (hors iframe, ticket 0010743)
    driveButton(b.key)                                                       // Voir/Affichage → pilotage iframe legacy
  }, [saveAll, doPublish, doClear, openNewPage, doDuplicate, driveButton])

  useEffect(() => { if (!current) return; const f = frameRef.current[current]; if (f) applyIframeChrome(f) }, [current, applyIframeChrome])

  // ── flux legacy conservés ──
  useEffect(() => {
    const onClosed = (e: Event) => { const path = (e as CustomEvent<{ path?: string }>).detail?.path ?? ''; const m = path.match(/^\/melis-cms\/page\/(.+)$/); if (m) { const cid = decodeURIComponent(m[1]); setOpened((o) => o.filter((x) => x !== cid)) } }
    window.addEventListener('melis:tab-closed', onClosed); return () => window.removeEventListener('melis:tab-closed', onClosed)
  }, [])
  const openedRef = useRef(opened); openedRef.current = opened
  // Ouvre la page fraîchement CRÉÉE en édition : ferme l'onglet de création, ouvre l'onglet d'édition,
  // rafraîchit l'arbre. Utilisé par les DEUX flux de création (form React natif + iframe legacy « Old »).
  const openCreatedPage = useCallback((newId: number | string, name: string) => {
    const newTabId = openedRef.current.find((x) => x === 'new' || x.startsWith('new~')) ?? 'new'
    const editPath = `/melis-cms/page/${newId}`
    const wg = window as unknown as { __melisOpenTab?: (t: { id: string; label: string; path: string }) => void; __melisCloseTab?: (id: string) => void }
    wg.__melisOpenTab?.({ id: editPath, label: `${newId} - ${(name || `Page ${newId}`).trim()}`, path: editPath })
    navigate(editPath); wg.__melisCloseTab?.(`/melis-cms/page/${newTabId}`)
    setOpened((o) => o.filter((x) => x !== newTabId))
    window.dispatchEvent(new CustomEvent('melis:cms-page-created', { detail: { idPage: newId, father: newTabId.startsWith('new~') ? newTabId.slice('new~'.length) : '' } }))
  }, [navigate])
  useEffect(() => {
    const onResult = (e: MessageEvent) => {
      const d = e.data as { __melisToolResult?: boolean; url?: string; data?: { success?: number; datas?: { idPage?: number | string; item_zoneid?: string; item_name?: string } } } | null
      if (!d || !d.__melisToolResult) return
      const data = d.data
      if ((d.url || '').indexOf('/Page/savePage') === -1 || !data || data.success !== 1) return
      if (data.datas?.item_zoneid !== '0_id_meliscms_page') return
      const newId = data.datas?.idPage; if (!newId) return
      openCreatedPage(newId, (data.datas?.item_name || '').trim()) // création via iframe legacy (Old)
    }
    window.addEventListener('message', onResult); return () => window.removeEventListener('message', onResult)
  }, [openCreatedPage])
  function hookNewPage(iframe: HTMLIFrameElement) {
    try {
      const doc = iframe.contentDocument as (Document & { __melisNewPageHooked?: boolean }) | null
      if (!doc || doc.__melisNewPageHooked) return
      doc.__melisNewPageHooked = true
      doc.addEventListener('click', (ev) => {
        const btn = (ev.target as HTMLElement)?.closest?.('.melis-newpage') as HTMLElement | null
        if (!btn) return
        ev.preventDefault(); ev.stopImmediatePropagation()
        const father = btn.getAttribute('data-pagenumber') || ''
        const path = father ? `${NEW_PAGE_ROUTE}~${father}` : NEW_PAGE_ROUTE
        ;(window as unknown as { __melisOpenTab?: (t: { id: string; label: string; path: string }) => void }).__melisOpenTab?.({ id: path, label: tr.newPage, path })
        navigate(path)
      }, true)
    } catch { /* */ }
  }
  // Vue « Old » : le bouton Dupliquer legacy (a.melis-pageduplicate → page-duplicate.tool.js) crée bien
  // la copie mais son handler de succès appelle melisCms.refreshTreeview() qui PLANTE dans la react-tool-page
  // (aucun arbre → « reading 'ext' ») → l'erreur avorte le reste (tabOpen/notif jamais exécutés) : la page
  // dupliquée reste invisible et ne s'ouvre pas. On intercepte donc le clic (capture, comme hookNewPage) et
  // on rejoue le flux REACT doDuplicate (POST + onglet React + refresh arbre React). Le full legacy /melis
  // n'a PAS d'iframe react-tool-page → hook jamais posé là → comportement legacy inchangé.
  function hookDuplicate(iframe: HTMLIFrameElement) {
    try {
      const doc = iframe.contentDocument as (Document & { __melisDuplicateHooked?: boolean }) | null
      if (!doc || doc.__melisDuplicateHooked) return
      doc.__melisDuplicateHooked = true
      doc.addEventListener('click', (ev) => {
        const btn = (ev.target as HTMLElement)?.closest?.('a.melis-pageduplicate') as HTMLElement | null
        if (!btn) return
        ev.preventDefault(); ev.stopImmediatePropagation()
        void doDuplicate(btn.getAttribute('data-pagenumber') || undefined)
      }, true)
    } catch { /* */ }
  }
  function onFrameLoad(cid: string, iframe: HTMLIFrameElement) { frameRef.current[cid] = iframe; hookNewPage(iframe); hookDuplicate(iframe); applyIframeChrome(iframe); setRevealed((s) => s.has(cid) ? s : new Set(s).add(cid)); if (cid === current && activeTab && !isNativeTab(activeTab)) driveTab(activeTab) }

  useEffect(() => { const onDoc = () => setOpenMenu(null); if (openMenu) { document.addEventListener('click', onDoc); return () => document.removeEventListener('click', onDoc) } }, [openMenu])

  // ── rendu ──
  // En-tête (nom/date/auteur/statut) : UNIQUEMENT quand la structure chargée porte sur la page
  // courante — sinon on montre l'état « chargement » (cf. structMatches) plutôt que la page précédente.
  const header = structMatches ? struct?.header : undefined
  const statusLabel = header?.status === 'published' ? tr.statusOnline : header?.status === 'draft' ? tr.statusDraft : header?.status === 'unpublished' ? tr.statusOffline : null
  const statusColor = header?.status === 'published' ? '#16a34a' : header?.status === 'draft' ? '#d97706' : '#6b7280'
  const nativeTabActive = !!(showChrome && activeTab && isNativeTab(activeTab))
  // Onglet Édition actif (dans le chrome React) → affiche le toggle New/Old propre à l'édition et,
  // en « New », l'overlay canvas React par-dessus l'iframe legacy.
  const editionActive = !!(showChrome && activeTab === KEY_EDITION)

  const btnBase: React.CSSProperties = { appearance: 'none', display: 'inline-flex', alignItems: 'center', gap: 5, height: 28, padding: '0 9px', borderRadius: 6, fontSize: 12, fontWeight: 500, cursor: 'pointer', whiteSpace: 'nowrap', transition: 'background .12s' }
  function btnStyle(b: StructBtn): React.CSSProperties {
    if (b.key.endsWith('action_save')) return { ...btnBase, border: 0, background: 'var(--color-primary,#dc2626)', color: '#fff' }
    if (b.key.endsWith('action_publish')) return { ...btnBase, border: 0, background: '#16a34a', color: '#fff' }
    if (b.key.endsWith('action_delete')) return { ...btnBase, border: '1px solid #fecaca', background: 'var(--color-card,#fff)', color: '#dc2626' }
    return { ...btnBase, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', color: 'var(--color-foreground,#111827)' }
  }
  // Largeur des boutons d'action sur narrow : 2 par ligne (`calc(50% - 4px)` = gap de colonne du
  // conteneur), SAUF les libellés qui ne tiennent PAS dans un demi-bouton (whiteSpace:nowrap → ils
  // déborderaient) : ligne entière. Le seuil est géométrique et non « > N caractères » : mesuré,
  // le plus long libellé FR (« Envoyer la newsletter », 116px) tient à 390px mais pas à 320px —
  // un seuil fixe sacrifierait donc une ligne pour rien sur un téléphone standard.
  // dispo = (viewport − 2×16 padding conteneur − 4 gap) / 2 − 18 padding bouton − 19 icône+gap.
  // Desktop : objet vide → styles d'origine strictement inchangés.
  function narrowSlot(label: string): React.CSSProperties {
    if (!narrow) return {}
    const avail = (vw - 36) / 2 - 37
    return { flex: label.length * 5.8 > avail ? '1 1 100%' : '1 1 calc(50% - 4px)', minWidth: 0, justifyContent: 'center' }
  }

  return (
    <div style={{ position: 'relative', height: '100%', width: '100%', minHeight: 0, display: 'flex', flexDirection: 'column' }}>
      <style>{`
        .melis-pgbtn{transition:filter .12s, box-shadow .12s, transform .1s}
        .melis-pgbtn:hover:not(:disabled){filter:brightness(.96); box-shadow:0 2px 8px rgba(0,0,0,.13); transform:translateY(-1px)}
        .melis-pgbtn:active:not(:disabled){transform:translateY(0); box-shadow:0 1px 3px rgba(0,0,0,.12)}
        .melis-pgmenu:hover{background:color-mix(in srgb, var(--color-primary,#dc2626) 10%, transparent)!important}
        .melis-pgtab:hover{color:var(--color-foreground,#111827)!important}
      `}</style>
      {!isCreation && (
        <div style={{ flex: '0 0 auto', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 10, padding: chromeCollapsed ? '5px 12px' : '8px 16px', borderBottom: showChrome && !chromeCollapsed ? 'none' : '1px solid var(--color-border,#e5e7eb)' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 10, minWidth: narrow ? 0 : undefined, overflow: narrow ? 'hidden' : undefined }}>
            {showChrome && (<>
              <span style={{ fontWeight: 600, fontSize: 15, color: 'var(--color-foreground,#111827)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', minWidth: narrow ? 0 : undefined }}>{header?.pageName ? `${current} - ${header.pageName}` : (structMatches ? `Page ${current}` : '')}</span>
              {statusLabel && <span style={{ fontSize: 11, fontWeight: 600, color: '#fff', background: statusColor, borderRadius: 999, padding: '2px 8px', flexShrink: 0 }}>{statusLabel}</span>}
              {/* Métadonnées secondaires (date/auteur, chargement, toast local) : masquées sur narrow pour
                  garder l'en-tête sur UNE seule ligne (règle pattern 1) — les notifications importantes
                  passent de toute façon par le toast global de la coquille (notify() → postMessage). */}
              {!narrow && header?.editDate && <span style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.modifiedOn} {header.editDate}{header.editor ? ` ${tr.byWord} ${header.editor}` : ''}</span>}
              {!narrow && !editionReady && !saving && <span style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.loadingEdition}</span>}
              {!narrow && saving && <span style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.savingEdition}</span>}
              {!narrow && toast && <span style={{ fontSize: 12, fontWeight: 600, color: toast.ok ? '#16a34a' : '#dc2626' }}>{toast.text}</span>}
            </>)}
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: narrow ? 8 : 12, flexShrink: 0 }}>
            {/* Switch Publié / Dépublié (comme le legacy .page-publishunpublish). ON=En ligne (publie), OFF=Hors ligne (dépublie).
                Gaté par la capacité `status` (droits avancés) → masquable dans Users→Droits comme les boutons. */}
            {showChrome && header && !chromeCollapsed && (!capsLoaded || can('status')) && (() => {
              const online = !!header.online
              const disabled = saving || (!online && !editionReady) // pour publier (OFF→ON) il faut l'édition chargée
              return (
                <button type="button" onClick={() => togglePublish(!online)} disabled={disabled}
                  title={online ? tr.onlineTip : tr.offlineTip}
                  style={{ display: 'inline-flex', alignItems: 'center', gap: narrow ? 6 : 8, appearance: 'none', border: 0, background: 'transparent', cursor: disabled ? 'not-allowed' : 'pointer', opacity: disabled ? 0.6 : 1, padding: 0 }}>
                  {!narrow && <span style={{ fontSize: 11, fontWeight: 700, letterSpacing: .3, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.statusLabel}</span>}
                  <span style={{ position: 'relative', width: 40, height: 21, borderRadius: 999, background: online ? '#16a34a' : '#dc2626', transition: 'background .15s', flex: '0 0 auto' }}>
                    <span style={{ position: 'absolute', top: 2, left: 2, width: 17, height: 17, borderRadius: '50%', background: '#fff', boxShadow: '0 1px 2px rgba(0,0,0,.3)', transform: online ? 'translateX(19px)' : 'translateX(0)', transition: 'transform .15s' }} />
                  </span>
                  {!narrow && <span style={{ fontSize: 11, fontWeight: 600, color: online ? '#16a34a' : '#dc2626', minWidth: 54, textAlign: 'left' }}>{online ? tr.statusOnline : tr.statusOffline}</span>}
                </button>
              )
            })()}
            {!chromeCollapsed && <ViewToggle mode={mode} onChange={setMode} compact={narrow} labels={{ react: tr.view_new, iframe: tr.view_old }} />}
            {/* Chevron « masquer/afficher l'en-tête » — mobile uniquement (ticket : sur mobile la zone
                d'édition est trop petite, la moitié de l'écran est prise par les boutons). */}
            {narrow && showChrome && (
              <button type="button" onClick={() => setHeaderOpen((o) => !o)}
                aria-expanded={!chromeCollapsed} title={chromeCollapsed ? tr.showHeader : tr.hideHeader} aria-label={chromeCollapsed ? tr.showHeader : tr.hideHeader}
                // Aligné sur le chevron « encoche » de la barre du haut (hôte) : même couleur
                // (muted-foreground), même taille (16px) et même retrait droit (12px) — d'où la marge
                // négative qui compense le padding du conteneur (12px replié / 16px déplié).
                style={{ appearance: 'none', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 24, height: 28, padding: 0, marginRight: chromeCollapsed ? -4 : -8, border: 0, background: 'transparent', color: 'var(--color-muted-foreground,#6b7280)', cursor: 'pointer', flex: '0 0 auto' }}>
                <Icon name={chromeCollapsed ? 'chevron-down' : 'chevron-up'} size={16} />
              </button>
            )}
          </div>
        </div>
      )}

      {/* Bandeau d'avertissement UNIQUEMENT si la page est verrouillée par un AUTRE utilisateur (mécanisme
          PageLock modulaire). Le propriétaire du verrou ne voit RIEN : le verrou le protège, il ne le bloque pas. */}
      {showChrome && lockedByOther && (
        <div style={{ flex: '0 0 auto', display: 'flex', alignItems: 'center', gap: 8, margin: '0 16px 10px', padding: '9px 12px', borderRadius: 7, background: '#fef3c7', border: '1px solid #fcd34d', color: '#92400e', fontSize: 13 }}>
          <Icon name="unlock" />
          <span>
            <strong>{tr.pageLocked}</strong>{lock?.byUser ? ` ${tr.lockedBy} ${lock.byUser}` : ''}{lock?.since ? ` ${tr.lockedSince} ${lock.since}` : ''}.
            {' '}{tr.lockedMsg}
          </span>
        </div>
      )}

      {/* Boutons d'action + onglets : repliés par le chevron sur mobile (chromeCollapsed). */}
      {showChrome && struct && !chromeCollapsed && (
        <div style={{ flex: '0 0 auto', background: 'var(--color-background,#fff)', borderBottom: '1px solid var(--color-border,#e5e7eb)' }}>
          <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: '10px 4px', padding: '0 16px 12px' }}>
            {btnGroups.map((group, gi) => (
              // narrow : `display:contents` → les boutons du groupe deviennent des enfants DIRECTS
              // du conteneur qui wrap, donc la grille 2-par-ligne est continue d'un groupe à
              // l'autre (au lieu d'un wrap ragged par groupe). Séparateurs verticaux masqués :
              // étirés sur un groupe multi-lignes ils volaient une place et rognaient le bouton
              // suivant. Desktop : la branche `flex/nowrap` d'origine, à l'identique.
              <div key={gi} style={narrow ? { display: 'contents' } : { display: 'flex', flexWrap: 'nowrap', alignItems: 'center', gap: 8 }}>
                {gi > 0 && !narrow && <div style={{ width: 1, minHeight: 24, alignSelf: 'stretch', background: 'var(--color-border,#e5e7eb)', margin: '0 6px' }} />}
                {group.map((b) => (
                  // Dropdown seulement pour de VRAIS sous-menus (Voir/Affichage) ; un enfant "modal"/
                  // "container" (ex. bouton Newsletter modulaire) → bouton DIRECT qui pilote la clé parente.
                  b.children && b.children.length && !b.children.some((cc) => /modal|container/i.test(cc.key)) ? (
                    <div key={b.key} style={{ position: 'relative', ...narrowSlot(b.label) }}>
                      <button className="melis-pgbtn" style={{ ...btnStyle(b), ...(narrow ? { width: '100%', justifyContent: 'center' } : null) }} onClick={(e) => { e.stopPropagation(); setOpenMenu(openMenu === b.key ? null : b.key) }}><Icon name={iconFor(b)} />{b.label} <span style={{ fontSize: 10, opacity: .7 }}>▾</span></button>
                      {openMenu === b.key && (
                        // narrow : le menu épouse la largeur du bouton (left+right à 0, minWidth
                        // levé) — ancré uniquement à gauche avec minWidth:190 il débordait à droite
                        // de l'écran quand le bouton occupe la colonne de droite.
                        <div style={{ position: 'absolute', top: '100%', left: 0, marginTop: 4, background: 'var(--color-card,#fff)', border: '1px solid var(--color-border,#e5e7eb)', borderRadius: 8, boxShadow: '0 6px 18px rgba(0,0,0,.13)', zIndex: 50, minWidth: narrow ? 0 : 190, right: narrow ? 0 : undefined, overflow: 'hidden', padding: 4 }}>
                          {b.children.map((cch) => <button key={cch.key} className="melis-pgmenu" style={{ ...btnBase, border: 0, width: '100%', justifyContent: 'flex-start', borderRadius: 5, background: 'transparent' }} onClick={() => driveButton(cch.key)}><Icon name={childIcon(cch.key, cch.label)} />{cch.label}</button>)}
                        </div>
                      )}
                    </div>
                  ) : (
                    (() => { const gated = b.key.endsWith('action_save') || b.key.endsWith('action_publish'); const dis = gated && (saving || !editionReady); return (
                    <button key={b.key} className="melis-pgbtn" style={{ ...btnStyle(b), ...narrowSlot(b.label), ...(dis ? { opacity: .55, cursor: 'not-allowed' } : null) }} disabled={dis} title={gated && !editionReady ? tr.editionLoadingTip : undefined} onClick={() => onButton(b)}><Icon name={iconFor(b)} />{b.label}</button>
                    ) })()
                  )
                ))}
              </div>
            ))}
          </div>
          {/* Onglets : wrap sur 2ᵉ ligne sur narrow (tous visibles) plutôt qu'un défilement horizontal
              qui masque les onglets tant que l'utilisateur n'a pas swipé (pattern 6). */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 2, padding: '0 12px', flexWrap: narrow ? 'wrap' : 'nowrap', overflowX: narrow ? 'visible' : 'auto' }}>
            {visibleTabs.map((t) => {
              const isActive = t.key === activeTab
              return <button key={t.key} className="melis-pgtab" onClick={() => driveTab(t.key)} title={t.label} style={{ appearance: 'none', border: 0, background: 'transparent', cursor: 'pointer', padding: '8px 12px', fontSize: 13, whiteSpace: 'nowrap', color: isActive ? 'var(--color-primary,#dc2626)' : 'var(--color-muted-foreground,#6b7280)', borderBottom: isActive ? '2px solid var(--color-primary,#dc2626)' : '2px solid transparent', fontWeight: isActive ? 600 : 400 }}>{t.label}</button>
            })}
            {/* Toggle New/Old propre à l'onglet Édition (aligné à droite de la barre d'onglets). */}
            {editionActive && (
              <div style={{ marginLeft: 'auto', paddingLeft: 8, alignSelf: 'center' }}>
                <ViewToggle mode={editionCanvas} onChange={setEditionCanvas} compact labels={{ react: tr.view_new, iframe: tr.view_old }} />
              </div>
            )}
          </div>
        </div>
      )}

      <div style={{ position: 'relative', flex: '1 1 auto', minHeight: 0 }}>
        {opened.map((cid) => {
          // Cid de CRÉATION → écran « Nouvelle page » React natif (form + toggle New/Old), pas l'iframe.
          if (cid === 'new' || cid.startsWith('new~')) {
            const fatherOf = cid.startsWith('new~') ? cid.slice('new~'.length) : ''
            return <NewPageView key={cid} father={fatherOf} visible={cid === current} onCreated={openCreatedPage} />
          }
          return (
            // data-melis-view-mode : lu DEPUIS l'iframe (react-bridge.js de MelisAICommunityExtensions
            // remonte la chaîne des frameElement). En mode « Old » l'outil doit rester 100% legacy —
            // le bouton « Generate with AI » rouvre alors l'ancienne popup mini-template au lieu du
            // dialogue React. Attribut plutôt qu'un global : porté par CETTE instance d'outil, et
            // réactif (le toggle le met à jour, il est relu au moment du clic).
            <iframe key={cid} src={toolSrc(cid)} title={`Page ${cid}`} data-melis-view-mode={mode} onLoad={(e) => onFrameLoad(cid, e.currentTarget)}
              style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', border: 0, display: cid === current && !nativeTabActive ? 'block' : 'none', visibility: revealed.has(cid) ? 'visible' : 'hidden' }} />
          )
        })}
        {/* Onglets natifs — TOUS montés (dès leur 1ʳᵉ ouverture), visibilité togglée → aucun refetch au switch */}
        {showChrome && current && [...mountedTabs].map((key) => {
          const visible = key === activeTab
          const style: React.CSSProperties = { position: 'absolute', inset: 0, overflow: 'auto', background: 'var(--color-background,#fff)', display: visible ? 'block' : 'none' }
          if (CONTROLLED.has(key)) {
            if (!edit) return <div key={key} style={style}><div style={{ padding: 20 }}>{tr.loading}</div></div>
            return <div key={key} style={style}>{key === KEY_PROPERTIES
              ? <PropertiesTab value={edit.props} refs={edit.refs} onChange={(v) => setEditByPage((m) => ({ ...m, [current]: { ...(m[current] ?? edit), props: v } }))} />
              : <SeoTab value={edit.seo} onChange={(v) => setEditByPage((m) => ({ ...m, [current]: { ...(m[current] ?? edit), seo: v } }))} />}</div>
          }
          const Comp = SELF_TABS[key] ?? w.__melisPageTabRegistry?.tabs[key]
          return Comp ? <div key={key} style={style}><Comp idPage={Number(current)} /></div> : null
        })}
        {/* Vue « New » de l'Édition : canvas React (lecture seule) par-dessus l'iframe legacy (gardée montée
            dessous → Sauvegarder/Publier inchangés). Uniquement quand l'onglet Édition est actif. */}
        {editionActive && editionCanvas === 'react' && current && (
          <div style={{ position: 'absolute', inset: 0, zIndex: 5, background: 'var(--color-background,#fff)', overflow: 'hidden' }}>
            <EditionCanvas idPage={Number(current)} device={canvasDevice} />
          </div>
        )}
        {current == null && <div style={{ padding: 24, color: 'var(--color-muted-foreground)', fontSize: 14 }}>{tr.selectPage}</div>}
      </div>

      {/* Modal React natif de déverrouillage (remplace le modal legacy) */}
      {unlockOpen && (
        <div onClick={() => !unlocking && setUnlockOpen(false)} style={{ position: 'fixed', inset: 0, background: 'rgba(15,23,42,.5)', zIndex: 1000, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }}>
          <div onClick={(e) => e.stopPropagation()} style={{ width: 480, maxWidth: '100%', background: 'var(--color-card,#fff)', borderRadius: 10, overflow: 'hidden', boxShadow: '0 20px 60px rgba(0,0,0,.35)' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 18px', background: '#f59e0b', color: '#fff' }}>
              <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8, fontSize: 15, fontWeight: 600 }}><Icon name="unlock" />{tr.unlockTitle}</span>
              <button onClick={() => !unlocking && setUnlockOpen(false)} style={{ appearance: 'none', border: 0, background: 'transparent', color: '#fff', fontSize: 20, lineHeight: 1, cursor: 'pointer' }}>×</button>
            </div>
            <div style={{ padding: 18, fontSize: 13, color: 'var(--color-foreground,#111827)', lineHeight: 1.5 }}>
              {tr.unlockBody1}{lock?.byUser ? ` ${tr.unlockBody1by} ${lock.byUser}` : ''}{lock?.since ? ` ${tr.unlockBody1on} ${lock.since}` : ''}.<br />{tr.unlockBody2}
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', gap: 10, padding: '0 18px 18px' }}>
              <button className="melis-pgbtn" onClick={() => setUnlockOpen(false)} disabled={unlocking} style={{ ...btnBase, height: 34, border: '1px solid #fecaca', background: 'var(--color-card,#fff)', color: '#dc2626' }}>{tr.cancel}</button>
              <button className="melis-pgbtn" onClick={doUnlock} disabled={unlocking} style={{ ...btnBase, height: 34, border: 0, background: '#16a34a', color: '#fff' }}>{unlocking ? tr.unlocking : tr.confirm}</button>
            </div>
          </div>
        </div>
      )}

      {/* Modal React de confirmation de SUPPRESSION (remplace le window.confirm natif) */}
      {deleteOpen && (
        <div onClick={() => !saving && setDeleteOpen(false)} style={{ position: 'fixed', inset: 0, background: 'rgba(15,23,42,.5)', zIndex: 1000, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }}>
          <div onClick={(e) => e.stopPropagation()} style={{ width: 480, maxWidth: '100%', background: 'var(--color-card,#fff)', borderRadius: 10, overflow: 'hidden', boxShadow: '0 20px 60px rgba(0,0,0,.35)' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 18px', background: '#dc2626', color: '#fff' }}>
              <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8, fontSize: 15, fontWeight: 600 }}><Icon name="trash" />{tr.deleteTitle}</span>
              <button onClick={() => !saving && setDeleteOpen(false)} style={{ appearance: 'none', border: 0, background: 'transparent', color: '#fff', fontSize: 20, lineHeight: 1, cursor: 'pointer' }}>×</button>
            </div>
            <div style={{ padding: 18, fontSize: 13, color: 'var(--color-foreground,#111827)', lineHeight: 1.5 }}>
              {tr.deleteBody1a} <strong>{header?.pageName || `Page ${current}`}</strong> {tr.deleteBody1b} <strong>{tr.deleteBody1and}</strong> {tr.deleteBody1c}<br />
              {tr.deleteBody2a}<strong>{tr.deleteBody2b}</strong> {tr.deleteBody2c} <strong>{tr.deleteBody2d}</strong> {tr.deleteBody2e}<br />
              {tr.deleteBody3a} <strong>{tr.deleteBody3b}</strong>.
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', gap: 10, padding: '0 18px 18px' }}>
              <button className="melis-pgbtn" onClick={() => setDeleteOpen(false)} disabled={saving} style={{ ...btnBase, height: 34, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', color: 'var(--color-foreground,#111827)' }}>{tr.cancel}</button>
              <button className="melis-pgbtn" onClick={doDelete} disabled={saving} style={{ ...btnBase, height: 34, border: 0, background: '#dc2626', color: '#fff' }}>{saving ? tr.deleting : tr.deleteConfirm}</button>
            </div>
          </div>
        </div>
      )}

      {/* Modal React de confirmation — EFFACER LE BROUILLON (remplace le confirm() natif) */}
      {clearOpen && (
        <div onClick={() => !saving && setClearOpen(false)} style={{ position: 'fixed', inset: 0, background: 'rgba(15,23,42,.5)', zIndex: 1000, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }}>
          <div onClick={(e) => e.stopPropagation()} style={{ width: 460, maxWidth: '100%', background: 'var(--color-card,#fff)', borderRadius: 10, overflow: 'hidden', boxShadow: '0 20px 60px rgba(0,0,0,.35)' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 18px', background: '#d97706', color: '#fff' }}>
              <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8, fontSize: 15, fontWeight: 600 }}><Icon name="eraser" />{tr.clearTitle}</span>
              <button onClick={() => !saving && setClearOpen(false)} style={{ appearance: 'none', border: 0, background: 'transparent', color: '#fff', fontSize: 20, lineHeight: 1, cursor: 'pointer' }}>×</button>
            </div>
            <div style={{ padding: 18, fontSize: 13, color: 'var(--color-foreground,#111827)', lineHeight: 1.5 }}>
              {tr.clearDraftConfirm}
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', gap: 10, padding: '0 18px 18px' }}>
              <button className="melis-pgbtn" onClick={() => setClearOpen(false)} disabled={saving} style={{ ...btnBase, height: 34, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', color: 'var(--color-foreground,#111827)' }}>{tr.cancel}</button>
              <button className="melis-pgbtn" onClick={doClear} disabled={saving} style={{ ...btnBase, height: 34, border: 0, background: '#d97706', color: '#fff' }}>{saving ? tr.clearing : tr.clearConfirmBtn}</button>
            </div>
          </div>
        </div>
      )}

      {/* Modal WORKFLOW — MUTUALISÉE : composant fourni par melis-small-business via window.__melisWorkflowModal
          (même que le bouton « Flux de travail » de l'outil News). Rendu ici avec un contexte type PAGE. */}
      {wfOpen && current && !isCreation && (() => {
        const WF = (window as unknown as { __melisWorkflowModal?: React.ComponentType<{ ctx: { wfType: string; wfId: number | string; wfDetails: string; wfOpeningJs: string }; appLang: string; onClose: () => void }> }).__melisWorkflowModal
        if (!WF) return null
        const name = (header?.pageName || `Page ${current}`).replace(/'/g, '')
        return (
          <WF
            ctx={{ wfType: 'PAGE', wfId: Number(current), wfDetails: `${name} (${current})`, wfOpeningJs: `melisHelper.tabOpen('${name}', 'fa fa-file-o fa-2x', '${current}_id_meliscms_page', 'meliscms_page', { idPage: ${current} });` }}
            appLang={(document.documentElement.lang || 'fr').slice(0, 2)}
            onClose={() => setWfOpen(false)}
          />
        )
      })()}

      {/* Modal « Envoyer la newsletter » — MUTUALISÉE : composant fourni par melis-newsletter via
          window.__melisNewsletterSendModal. Rendu ICI (arbre de l'éditeur) → via createPortal il
          s'ouvre au-dessus de l'onglet actif, HORS de l'iframe d'édition (ticket 0010743). */}
      {nlOpen && current && !isCreation && (() => {
        const NL = (window as unknown as { __melisNewsletterSendModal?: React.ComponentType<{ pageId: number | string; appLang: string; onClose: () => void }> }).__melisNewsletterSendModal
        if (!NL) return null
        return <NL pageId={current} appLang={(document.documentElement.lang || 'fr').slice(0, 2)} onClose={() => setNlOpen(false)} />
      })()}
    </div>
  )
}
