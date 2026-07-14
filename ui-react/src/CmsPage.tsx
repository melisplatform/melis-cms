import { useCallback, useEffect, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { ViewToggle, type ViewMode } from './ViewToggle'
import {
  PropertiesTab, SeoTab, LanguagesTab, HistoricTab, AnalyticsTab, ScriptsTab, VersioningTab, CommentsTab,
  apiGet, apiPost, type PropsData, type SeoData, type Refs,
} from './PageTabs'

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
const TOOL_KEY = 'meliscms_page'
const KEY_PROPERTIES = 'meliscms_page_properties'
const KEY_SEO = 'meliscms_page_seo'

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
const w = window as unknown as { __melisPageTabRegistry?: PageTabRegistry; __melisRegisterPageTab?: (k: string, c: PageTabComp) => void }
if (!w.__melisPageTabRegistry) {
  w.__melisPageTabRegistry = { tabs: {}, v: 0 }
  w.__melisRegisterPageTab = (k, c) => { w.__melisPageTabRegistry!.tabs[k] = c; w.__melisPageTabRegistry!.v++; window.dispatchEvent(new CustomEvent('melis:page-tabs-changed')) }
}
function isNativeTab(key: string): boolean {
  return CONTROLLED.has(key) || !!SELF_TABS[key] || !!w.__melisPageTabRegistry?.tabs[key]
}

type StructTab = { key: string; label: string; icon: string | null; cap: string }
type StructBtn = { key: string; label: string; cap: string; children?: { key: string; label: string }[] }
type Structure = { idPage: number; header: PageHeader; tabs: StructTab[]; buttons: StructBtn[] }
type PageHeader = { pageName: string | null; status: string | null; hasDraft: boolean; editDate: string | null; editor: string | null }
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
function Icon({ name }: { name: string }) {
  const c = { width: 13, height: 13, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round' as const, strokeLinejoin: 'round' as const }
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
  if (/action_(save|clear|publish)/.test(key)) return 0   // Édition / publication
  if (/action_(new|duplicate|delete)/.test(key)) return 1 // Page
  if (/action_(view|display)/.test(key)) return 2         // Aperçu / affichage
  return 3                                                // Modulaires (newsletter, workflow, unlock…)
}

export default function CmsPage({ active = true }: { active?: boolean }) {
  const { id } = useParams()
  const navigate = useNavigate()
  const { can, loaded: capsLoaded } = useCaps(TOOL_KEY)
  const [mode, setMode] = useState<ViewMode>('react')

  const [frozenId, setFrozenId] = useState<string | undefined>(id)
  useEffect(() => { if (active) setFrozenId(id) }, [active, id])
  const current = (active ? id : frozenId) ?? null

  const [opened, setOpened] = useState<string[]>(() => (current ? [current] : []))
  useEffect(() => { if (current) setOpened((o) => (o.includes(current) ? o : [...o, current])) }, [current])

  const isCreation = current === 'new' || (current?.startsWith('new~') ?? false)
  const showChrome = mode === 'react' && !isCreation

  const [struct, setStruct] = useState<Structure | null>(null)
  const [edit, setEdit] = useState<Edit | null>(null)
  const [lock, setLock] = useState<{ locked: boolean; byUser: string | null; byMe: boolean; since: string | null } | null>(null)
  const [activeTab, setActiveTab] = useState<string | null>(null)
  const [mountedTabs, setMountedTabs] = useState<Set<string>>(new Set())
  const [openMenu, setOpenMenu] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)
  const [toast, setToast] = useState<{ ok: boolean; text: string } | null>(null)
  const [unlockOpen, setUnlockOpen] = useState(false)
  const [unlocking, setUnlocking] = useState(false)
  const [, bumpTabs] = useState(0)
  useEffect(() => { const on = () => bumpTabs((n) => n + 1); window.addEventListener('melis:page-tabs-changed', on); return () => window.removeEventListener('melis:page-tabs-changed', on) }, [])

  // structure (onglets/boutons/en-tête)
  const refreshStructure = useCallback(async (idPage: string) => {
    try { const d = await apiGet<Structure>(`structure?idPage=${encodeURIComponent(idPage)}`); setStruct(d); setActiveTab((t) => t ?? d.tabs?.[0]?.key ?? null) } catch { setStruct(null) }
  }, [])
  useEffect(() => { if (!current || isCreation) { setStruct(null); setEdit(null); return } refreshStructure(current) }, [current, isCreation, refreshStructure])

  // état partagé Propriétés + SEO + refs (chargé UNE fois par page → pas de refetch au switch)
  useEffect(() => {
    if (!current || isCreation) return
    let x = false; const idPage = current
    Promise.all([
      apiGet<PropsData>(`properties?idPage=${idPage}`),
      apiGet<SeoData>(`seo?idPage=${idPage}`),
      apiGet<Refs>(`refs?idPage=${idPage}`),
    ]).then(([props, seo, refs]) => { if (!x) setEdit({ props, seo, refs }) }).catch(() => {})
    return () => { x = true }
  }, [current, isCreation])

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

  const visibleTabs = (struct?.tabs ?? []).filter((t) => !capsLoaded || can(t.cap))
  const visibleButtons = (struct?.buttons ?? [])
    .filter((b) => !capsLoaded || can(b.cap))
    .filter((b) => !b.key.includes('unlock') || !!lock?.locked) // Débloquer visible SEULEMENT si la page est verrouillée
  // Boutons regroupés en sections (Édition/publication · Page · Aperçu · Modulaires), séparées par un trait.
  const btnGroups = [0, 1, 2, 3].map((gi) => visibleButtons.filter((b) => groupOf(b.key) === gi)).filter((g) => g.length)
  useEffect(() => {
    if (!capsLoaded || !visibleTabs.length) return
    if (!visibleTabs.some((t) => t.key === activeTab)) driveTab(visibleTabs[0].key)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [capsLoaded, struct])

  // ── iframe legacy (pilotage) ──
  const frameRef = useRef<Record<string, HTMLIFrameElement | null>>({})
  const applyIframeChrome = useCallback((iframe: HTMLIFrameElement) => {
    try {
      const doc = iframe.contentDocument as (Document & { __melisChromeStyle?: HTMLStyleElement }) | null
      if (!doc) return
      let style = doc.__melisChromeStyle
      if (!style) { style = doc.createElement('style'); doc.__melisChromeStyle = style; doc.head?.appendChild(style) }
      style.textContent = showChrome ? `[data-melisKey='meliscms_pagehead']{display:none !important;} ul.tabs-label.nav-tabs{display:none !important;}` : ''
    } catch { /* */ }
  }, [showChrome])

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
    try {
      const doc = current ? frameRef.current[current]?.contentDocument : null
      if (!doc) return
      const el = doc.querySelector(`[data-melisKey='${btnKey}']`) as HTMLElement | null
      const clickable = (el?.querySelector('a,button') as HTMLElement | null) ?? el
      clickable?.click()
    } catch { /* */ }
  }, [current])

  // Persiste le brouillon (Propriétés + SEO ensemble) sans UI. Le contenu drag'n'drop est auto-sauvé.
  const persistDraft = useCallback(async () => {
    if (!edit) return
    await Promise.all([apiPost('properties/save', edit.props), apiPost('seo/save', edit.seo)])
  }, [edit])

  // Sauvegarde globale (bouton « Sauvegarder ») : persiste + toast local.
  const saveAll = useCallback(async () => {
    if (!edit) return
    setSaving(true); setToast(null)
    try {
      await persistDraft()
      setToast({ ok: true, text: 'Page enregistrée.' })
      if (current) refreshStructure(current) // rafraîchit le statut/en-tête
    } catch (e) { setToast({ ok: false, text: (e as Error).message }) } finally { setSaving(false) }
    setTimeout(() => setToast(null), 3500)
  }, [edit, persistDraft, current, refreshStructure])

  // Publier (bouton « Publier ») : sauve le brouillon PUIS PUBLIE réellement via l'endpoint legacy
  // (`publishPage` → événement meliscms_page_publish_start → déplace saved→published, page_status=1).
  // Notification native comme les outils + reload de l'arbre (le statut online y change).
  const doPublish = useCallback(async () => {
    if (!current) return
    setSaving(true); setToast(null)
    try {
      await persistDraft() // le brouillon doit être à jour avant de le publier
      const res = await fetch(`/melis/MelisCms/Page/publishPage?idPage=${current}`, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '',
      })
      const data = await res.json().catch(() => ({} as Record<string, unknown>)) as {
        success?: number; textTitle?: string; textMessage?: string; errors?: Record<string, { errorMessage?: string; label?: string }>
      }
      const ok = data && data.success === 1
      if (ok) {
        notify('ok', (data.textTitle || 'Publication').trim(), 'La page a été publiée.')
        window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh')) // statut online → maj de l'arbre
        refreshStructure(current) // en-tête : plus de brouillon, statut publié
      } else {
        const fields = Object.values(data.errors || {})
          .filter((e) => e && e.label)
          .map((e) => ({ label: String(e.label), messages: [String(e.errorMessage || '')] }))
        notify('ko', (data.textTitle || 'Publication').trim(), 'La publication a échoué.', fields)
      }
    } catch (e) { notify('ko', 'Publication', (e as Error).message) } finally { setSaving(false) }
  }, [current, persistDraft, refreshStructure])

  // Distinguer par la CLÉ (le cap 'save' est partagé par Sauvegarder ET Effacer brouillon pour le gating,
  // mais leurs ACTIONS diffèrent : seul le vrai bouton Save déclenche la sauvegarde globale).
  // Déverrouillage NATIF React (pas lié à l'édition) : endpoint modulaire + refresh de l'état verrou.
  const doUnlock = useCallback(async () => {
    if (!current) return
    setUnlocking(true)
    try {
      await apiPost('unlock', { idPage: Number(current) })
      const l = await apiGet<{ locked: boolean; byUser: string | null; byMe: boolean; since: string | null }>(`lock?idPage=${current}`)
      setLock(l); setUnlockOpen(false); setToast({ ok: true, text: 'Page débloquée.' }); setTimeout(() => setToast(null), 3000)
      window.dispatchEvent(new CustomEvent('melis:cms-tree-refresh')) // rafraîchit l'arbre → le cadenas disparaît
    } catch (e) { setToast({ ok: false, text: (e as Error).message }) } finally { setUnlocking(false) }
  }, [current])

  const onButton = useCallback(async (b: StructBtn) => {
    setOpenMenu(null)
    if (b.key.includes('unlock')) { setUnlockOpen(true); return }            // Débloquer → modal React natif
    if (b.key.endsWith('action_save')) { await saveAll(); return }          // Sauvegarder → save global
    if (b.key.endsWith('action_publish')) { await doPublish(); return }     // Publier → save puis PUBLIE (legacy) + notif + reload arbre
    driveButton(b.key)                                                       // autres (clear/delete/…) → legacy
  }, [saveAll, doPublish, driveButton])

  useEffect(() => { if (!current) return; const f = frameRef.current[current]; if (f) applyIframeChrome(f) }, [current, applyIframeChrome])

  // ── flux legacy conservés ──
  useEffect(() => {
    const onClosed = (e: Event) => { const path = (e as CustomEvent<{ path?: string }>).detail?.path ?? ''; const m = path.match(/^\/melis-cms\/page\/(.+)$/); if (m) { const cid = decodeURIComponent(m[1]); setOpened((o) => o.filter((x) => x !== cid)) } }
    window.addEventListener('melis:tab-closed', onClosed); return () => window.removeEventListener('melis:tab-closed', onClosed)
  }, [])
  const openedRef = useRef(opened); openedRef.current = opened
  useEffect(() => {
    const onResult = (e: MessageEvent) => {
      const d = e.data as { __melisToolResult?: boolean; url?: string; data?: { success?: number; datas?: { idPage?: number | string; item_zoneid?: string; item_name?: string } } } | null
      if (!d || !d.__melisToolResult) return
      const data = d.data
      if ((d.url || '').indexOf('/Page/savePage') === -1 || !data || data.success !== 1) return
      if (data.datas?.item_zoneid !== '0_id_meliscms_page') return
      const newId = data.datas?.idPage; if (!newId) return
      const newTabId = openedRef.current.find((x) => x === 'new' || x.startsWith('new~')) ?? 'new'
      const editPath = `/melis-cms/page/${newId}`
      const wg = window as unknown as { __melisOpenTab?: (t: { id: string; label: string; path: string }) => void; __melisCloseTab?: (id: string) => void }
      wg.__melisOpenTab?.({ id: editPath, label: (data.datas?.item_name || `Page ${newId}`).trim(), path: editPath })
      navigate(editPath); wg.__melisCloseTab?.(`/melis-cms/page/${newTabId}`)
      setOpened((o) => o.filter((x) => x !== newTabId))
      window.dispatchEvent(new CustomEvent('melis:cms-page-created', { detail: { idPage: newId, father: newTabId.startsWith('new~') ? newTabId.slice('new~'.length) : '' } }))
    }
    window.addEventListener('message', onResult); return () => window.removeEventListener('message', onResult)
  }, [navigate])
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
        ;(window as unknown as { __melisOpenTab?: (t: { id: string; label: string; path: string }) => void }).__melisOpenTab?.({ id: path, label: 'Nouvelle page', path })
        navigate(path)
      }, true)
    } catch { /* */ }
  }
  function onFrameLoad(cid: string, iframe: HTMLIFrameElement) { frameRef.current[cid] = iframe; hookNewPage(iframe); applyIframeChrome(iframe); if (cid === current && activeTab && !isNativeTab(activeTab)) driveTab(activeTab) }

  useEffect(() => { const onDoc = () => setOpenMenu(null); if (openMenu) { document.addEventListener('click', onDoc); return () => document.removeEventListener('click', onDoc) } }, [openMenu])

  // ── rendu ──
  const header = struct?.header
  const statusLabel = header?.status === 'published' ? 'En ligne' : header?.status === 'draft' ? 'Brouillon' : header?.status === 'unpublished' ? 'Hors ligne' : null
  const statusColor = header?.status === 'published' ? '#16a34a' : header?.status === 'draft' ? '#d97706' : '#6b7280'
  const nativeTabActive = !!(showChrome && activeTab && isNativeTab(activeTab))

  const btnBase: React.CSSProperties = { appearance: 'none', display: 'inline-flex', alignItems: 'center', gap: 5, height: 28, padding: '0 9px', borderRadius: 6, fontSize: 12, fontWeight: 500, cursor: 'pointer', whiteSpace: 'nowrap', transition: 'background .12s' }
  function btnStyle(b: StructBtn): React.CSSProperties {
    if (b.key.endsWith('action_save')) return { ...btnBase, border: 0, background: 'var(--color-primary,#dc2626)', color: '#fff' }
    if (b.key.endsWith('action_publish')) return { ...btnBase, border: 0, background: '#16a34a', color: '#fff' }
    if (b.key.endsWith('action_delete')) return { ...btnBase, border: '1px solid #fecaca', background: 'var(--color-card,#fff)', color: '#dc2626' }
    return { ...btnBase, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', color: 'var(--color-foreground,#111827)' }
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
        <div style={{ flex: '0 0 auto', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 10, padding: '8px 16px', borderBottom: showChrome ? 'none' : '1px solid var(--color-border,#e5e7eb)' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 10, minWidth: 0 }}>
            {showChrome && (<>
              <span style={{ fontWeight: 600, fontSize: 15, color: 'var(--color-foreground,#111827)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{header?.pageName ?? (struct ? `Page ${struct.idPage}` : '')}</span>
              {statusLabel && <span style={{ fontSize: 11, fontWeight: 600, color: '#fff', background: statusColor, borderRadius: 999, padding: '2px 8px' }}>{statusLabel}</span>}
              {header?.editDate && <span style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>modifiée le {header.editDate}{header.editor ? ` par ${header.editor}` : ''}</span>}
              {saving && <span style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>enregistrement…</span>}
              {toast && <span style={{ fontSize: 12, fontWeight: 600, color: toast.ok ? '#16a34a' : '#dc2626' }}>{toast.text}</span>}
            </>)}
          </div>
          <ViewToggle mode={mode} onChange={setMode} />
        </div>
      )}

      {/* Bandeau d'avertissement si la page est VERROUILLÉE (mécanisme PageLock modulaire) */}
      {showChrome && lock?.locked && (
        <div style={{ flex: '0 0 auto', display: 'flex', alignItems: 'center', gap: 8, margin: '0 16px 10px', padding: '9px 12px', borderRadius: 7, background: '#fef3c7', border: '1px solid #fcd34d', color: '#92400e', fontSize: 13 }}>
          <Icon name="unlock" />
          <span>
            <strong>Page verrouillée</strong>{lock.byMe ? ' par vous' : lock.byUser ? ` par ${lock.byUser}` : ''}{lock.since ? ` depuis le ${lock.since}` : ''}.
            {lock.byMe ? ' Vous pouvez la débloquer.' : ' Un autre utilisateur l’édite ; débloquez-la pour reprendre la main.'}
          </span>
        </div>
      )}

      {showChrome && struct && (
        <div style={{ flex: '0 0 auto', background: 'var(--color-background,#fff)', borderBottom: '1px solid var(--color-border,#e5e7eb)' }}>
          <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: '10px 4px', padding: '0 16px 12px' }}>
            {btnGroups.map((group, gi) => (
              <div key={gi} style={{ display: 'flex', flexWrap: 'nowrap', alignItems: 'center', gap: 8 }}>
                {gi > 0 && <div style={{ width: 1, minHeight: 24, alignSelf: 'stretch', background: 'var(--color-border,#e5e7eb)', margin: '0 6px' }} />}
                {group.map((b) => (
                  // Dropdown seulement pour de VRAIS sous-menus (Voir/Affichage) ; un enfant "modal"/
                  // "container" (ex. bouton Newsletter modulaire) → bouton DIRECT qui pilote la clé parente.
                  b.children && b.children.length && !b.children.some((cc) => /modal|container/i.test(cc.key)) ? (
                    <div key={b.key} style={{ position: 'relative' }}>
                      <button className="melis-pgbtn" style={btnStyle(b)} onClick={(e) => { e.stopPropagation(); setOpenMenu(openMenu === b.key ? null : b.key) }}><Icon name={iconFor(b)} />{b.label} <span style={{ fontSize: 10, opacity: .7 }}>▾</span></button>
                      {openMenu === b.key && (
                        <div style={{ position: 'absolute', top: '100%', left: 0, marginTop: 4, background: 'var(--color-card,#fff)', border: '1px solid var(--color-border,#e5e7eb)', borderRadius: 8, boxShadow: '0 6px 18px rgba(0,0,0,.13)', zIndex: 50, minWidth: 190, overflow: 'hidden', padding: 4 }}>
                          {b.children.map((cch) => <button key={cch.key} className="melis-pgmenu" style={{ ...btnBase, border: 0, width: '100%', justifyContent: 'flex-start', borderRadius: 5, background: 'transparent' }} onClick={() => driveButton(cch.key)}><Icon name={childIcon(cch.key, cch.label)} />{cch.label}</button>)}
                        </div>
                      )}
                    </div>
                  ) : (
                    <button key={b.key} className="melis-pgbtn" style={btnStyle(b)} disabled={saving && (b.key.endsWith('action_save') || b.key.endsWith('action_publish'))} onClick={() => onButton(b)}><Icon name={iconFor(b)} />{b.label}</button>
                  )
                ))}
              </div>
            ))}
          </div>
          <div style={{ display: 'flex', gap: 2, padding: '0 12px', overflowX: 'auto' }}>
            {visibleTabs.map((t) => {
              const isActive = t.key === activeTab
              return <button key={t.key} className="melis-pgtab" onClick={() => driveTab(t.key)} title={t.label} style={{ appearance: 'none', border: 0, background: 'transparent', cursor: 'pointer', padding: '8px 12px', fontSize: 13, whiteSpace: 'nowrap', color: isActive ? 'var(--color-primary,#dc2626)' : 'var(--color-muted-foreground,#6b7280)', borderBottom: isActive ? '2px solid var(--color-primary,#dc2626)' : '2px solid transparent', fontWeight: isActive ? 600 : 400 }}>{t.label}</button>
            })}
          </div>
        </div>
      )}

      <div style={{ position: 'relative', flex: '1 1 auto', minHeight: 0 }}>
        {opened.map((cid) => (
          <iframe key={cid} src={toolSrc(cid)} title={`Page ${cid}`} onLoad={(e) => onFrameLoad(cid, e.currentTarget)}
            style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', border: 0, display: cid === current && !nativeTabActive ? 'block' : 'none' }} />
        ))}
        {/* Onglets natifs — TOUS montés (dès leur 1ʳᵉ ouverture), visibilité togglée → aucun refetch au switch */}
        {showChrome && current && [...mountedTabs].map((key) => {
          const visible = key === activeTab
          const style: React.CSSProperties = { position: 'absolute', inset: 0, overflow: 'auto', background: 'var(--color-background,#fff)', display: visible ? 'block' : 'none' }
          if (CONTROLLED.has(key)) {
            if (!edit) return <div key={key} style={style}><div style={{ padding: 20 }}>Chargement…</div></div>
            return <div key={key} style={style}>{key === KEY_PROPERTIES
              ? <PropertiesTab value={edit.props} refs={edit.refs} onChange={(v) => setEdit({ ...edit, props: v })} />
              : <SeoTab value={edit.seo} onChange={(v) => setEdit({ ...edit, seo: v })} />}</div>
          }
          const Comp = SELF_TABS[key] ?? w.__melisPageTabRegistry?.tabs[key]
          return Comp ? <div key={key} style={style}><Comp idPage={Number(current)} /></div> : null
        })}
        {current == null && <div style={{ padding: 24, color: 'var(--color-muted-foreground)', fontSize: 14 }}>Sélectionnez une page dans l'arbre.</div>}
      </div>

      {/* Modal React natif de déverrouillage (remplace le modal legacy) */}
      {unlockOpen && (
        <div onClick={() => !unlocking && setUnlockOpen(false)} style={{ position: 'fixed', inset: 0, background: 'rgba(15,23,42,.5)', zIndex: 1000, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }}>
          <div onClick={(e) => e.stopPropagation()} style={{ width: 480, maxWidth: '100%', background: 'var(--color-card,#fff)', borderRadius: 10, overflow: 'hidden', boxShadow: '0 20px 60px rgba(0,0,0,.35)' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 18px', background: '#f59e0b', color: '#fff' }}>
              <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8, fontSize: 15, fontWeight: 600 }}><Icon name="unlock" />Débloquer la page</span>
              <button onClick={() => !unlocking && setUnlockOpen(false)} style={{ appearance: 'none', border: 0, background: 'transparent', color: '#fff', fontSize: 20, lineHeight: 1, cursor: 'pointer' }}>×</button>
            </div>
            <div style={{ padding: 18, fontSize: 13, color: 'var(--color-foreground,#111827)', lineHeight: 1.5 }}>
              Cette page a été verrouillée{lock?.byUser ? ` par ${lock.byUser}` : ''}{lock?.since ? ` le ${lock.since}` : ''}.<br />Merci de confirmer le déblocage.
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', gap: 10, padding: '0 18px 18px' }}>
              <button className="melis-pgbtn" onClick={() => setUnlockOpen(false)} disabled={unlocking} style={{ ...btnBase, height: 34, border: '1px solid #fecaca', background: 'var(--color-card,#fff)', color: '#dc2626' }}>Annuler</button>
              <button className="melis-pgbtn" onClick={doUnlock} disabled={unlocking} style={{ ...btnBase, height: 34, border: 0, background: '#16a34a', color: '#fff' }}>{unlocking ? 'Déblocage…' : 'Confirmer'}</button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
