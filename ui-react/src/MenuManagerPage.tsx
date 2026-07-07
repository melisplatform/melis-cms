import { useEffect, useMemo, useRef, useState, type CSSProperties, type DragEvent } from 'react'
import { useLocation, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import {
  deleteMenuManagerCategory, fetchMenuManagerCategory, fetchMenuManagerLanguages,
  fetchMenuManagerSites, fetchMenuManagerTree, saveMenuManagerCategory, saveMenuManagerTree,
  type LanguageOption, type SiteOption, type TreeNode,
} from './menu-manager-api'
import { ViewToggle } from './ViewToggle'

/* ──────────────────────────────────────────────────────────────────────────
 * Menu Manager (MelisCms) — brique full React, montée à /melis-cms/menu-manager.
 * Organise les mini-templates d'un site en catégories, sous forme d'arbre (jstree
 * legacy) : catégories (traduites par langue) + mini-templates, glisser-déposer
 * pour réordonner et déplacer un template entre catégories / racine.
 * Réutilise MelisCmsMiniTemplateService (getTree/saveTree/saveCategory/deleteCategory)
 * côté serveur — l'arbre est une liste PLATE (id/parent/type), gardée telle quelle ici.
 * Ajout/édition de catégorie = sous-onglet dédié (comme Mini-Templates), pas une modale.
 * ────────────────────────────────────────────────────────────────────────── */

const MELIS_KEY = 'meliscms_mini_template_menu_manager_tool'
function can(cap: string): boolean {
  return (window as unknown as { MelisCan?: (k: string, c: string) => boolean }).MelisCan?.(MELIS_KEY, cap) ?? true
}

// API sub-tabs de l'hôte (la brique ne peut pas importer le contexte React de l'hôte)
type SubTabW = {
  __melisOpenSubTab?: (section: string, tab: { id: string; label: string; path: string }) => void
  __melisUpdateSubTabLabel?: (section: string, id: string, label: string) => void
}

// ── i18n minimal (la brique ne partage pas le dictionnaire de l'hôte) ──
type Lang = 'fr' | 'en'
function currentLang(): Lang {
  const l = (document.documentElement.lang || 'en').toLowerCase()
  return l.startsWith('fr') ? 'fr' : 'en'
}
const DICT: Record<Lang, Record<string, string>> = {
  fr: {
    title: 'Menu manager', subtitle: 'Organisation des mini-templates en catégories, par site',
    f_site: 'Site', select_site: '— Choisir un site —',
    f_lang: 'Langue', refresh: 'Rafraîchir', loading: 'Chargement…',
    new_category: 'Nouvelle catégorie', empty_site: 'Sélectionnez un site pour voir son arborescence.',
    empty_tree: 'Aucun élément. Créez une catégorie ou déposez un mini-template ici.',
    root_zone: 'Déposer ici pour retirer d’une catégorie',
    edit: 'Modifier', del: 'Supprimer', cancel: 'Annuler', save: 'Enregistrer', back: 'retour',
    active: 'Actif', inactive: 'Inactif', saved: 'Enregistré ✓',
    del_title: 'Supprimer la catégorie',
    del_confirm: 'Supprimer « {n} » ? Les mini-templates qu’elle contient repasseront à la racine.',
    new_title: 'Nouvelle catégorie', edit_title: 'Modifier la catégorie',
    f_category_name: 'Category name', f_category_name_hint: 'Un nom par langue (au moins une langue requise).',
    err_save: 'Erreur lors de la sauvegarde', err_move: 'Erreur lors du déplacement — arbre rechargé.',
    err_name: 'Au moins un nom de catégorie est requis.',
    no_access: 'Vous n’avez pas les droits pour consulter cet outil.',
  },
  en: {
    title: 'Menu manager', subtitle: 'Organize mini-templates into categories, per site',
    f_site: 'Site', select_site: '— Choose a site —',
    f_lang: 'Language', refresh: 'Refresh', loading: 'Loading…',
    new_category: 'New category', empty_site: 'Select a site to view its tree.',
    empty_tree: 'Nothing here yet. Create a category or drop a mini-template here.',
    root_zone: 'Drop here to remove from a category',
    edit: 'Edit', del: 'Delete', cancel: 'Cancel', save: 'Save', back: 'back',
    active: 'Active', inactive: 'Inactive', saved: 'Saved ✓',
    del_title: 'Delete category',
    del_confirm: 'Delete "{n}"? Its mini-templates will move back to the root.',
    new_title: 'New category', edit_title: 'Edit category',
    f_category_name: 'Category name', f_category_name_hint: 'One name per language (at least one required).',
    err_save: 'Error while saving', err_move: 'Error while moving — tree reloaded.',
    err_name: 'At least one category name is required.',
    no_access: 'You do not have permission to view this tool.',
  },
}
function useT() {
  const lang = currentLang()
  return (key: string, vars?: Record<string, string | number>) => {
    let s = DICT[lang][key] ?? key
    if (vars) for (const [k, v] of Object.entries(vars)) s = s.replaceAll(`{${k}}`, String(v))
    return s
  }
}

// Flags par langue (cosmétique — la locale legacy n'est pas un vrai code pays, ex. "en_EN"/"fr_FR").
const FLAG_MAP: Record<string, string> = {
  en: '🇺🇸', fr: '🇫🇷', de: '🇩🇪', es: '🇪🇸', it: '🇮🇹', nl: '🇳🇱', pt: '🇵🇹',
  ar: '🇸🇦', zh: '🇨🇳', ja: '🇯🇵', ru: '🇷🇺', pl: '🇵🇱', tr: '🇹🇷',
}
function langFlag(locale: string): string {
  return FLAG_MAP[locale.slice(0, 2).toLowerCase()] ?? '🏳️'
}

// ── Styles (variables CSS du thème de l'hôte) ──
const card: CSSProperties = { border: '1px solid var(--color-border)', background: 'var(--color-card)', borderRadius: 12, boxShadow: '0 1px 2px rgba(0,0,0,.04)' }
const inputCss: CSSProperties = { height: 40, width: '100%', boxSizing: 'border-box', borderRadius: 8, border: '1px solid var(--color-input,var(--color-border))', background: 'var(--color-card)', color: 'var(--color-foreground)', padding: '0 12px', fontSize: 14, outline: 'none' }
const btnPrimary: CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 36, padding: '0 14px', borderRadius: 8, border: 0, background: 'var(--color-primary)', color: 'var(--color-primary-foreground,#fff)', fontSize: 14, fontWeight: 500, cursor: 'pointer' }
const btnGhost: CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 36, padding: '0 12px', borderRadius: 8, border: '1px solid var(--color-border)', background: 'var(--color-card)', color: 'var(--color-foreground)', fontSize: 14, cursor: 'pointer' }
const iconBtn: CSSProperties = { display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 28, height: 28, borderRadius: 6, border: 0, background: 'transparent', color: 'var(--color-muted-foreground)', cursor: 'pointer' }
const label: CSSProperties = { display: 'block', fontSize: 13, fontWeight: 500, marginBottom: 4, color: 'var(--color-foreground)' }
const hint: CSSProperties = { marginTop: 4, fontSize: 12, color: 'var(--color-muted-foreground)' }

const sIcon = { width: 15, height: 15, flexShrink: 0 } as const
const PencilIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>
const TrashIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /></svg>
const PlusIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14M5 12h14" /></svg>
const FolderIcon = () => <svg style={{ width: 16, height: 16, flexShrink: 0 }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" /></svg>
const PlugIcon = () => <svg style={{ width: 14, height: 14, flexShrink: 0 }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 22v-5M9 8V2M15 8V2M6 8h12l-1 6a5 5 0 0 1-10 0L6 8Z" /></svg>
const GripIcon = () => <svg style={{ width: 13, height: 13, flexShrink: 0, color: 'var(--color-muted-foreground)' }} viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" /><circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" /><circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" /></svg>
const InfoIcon = () => <svg style={{ width: 15, height: 15, flexShrink: 0, color: 'var(--color-muted-foreground)' }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10" /><path d="M12 16v-4M12 8h.01" /></svg>

function catId(node: TreeNode): number {
  return parseInt(node.id.split('-')[0], 10) || 0
}

// Le service legacy renvoie le nom de catégorie déjà HTML-échappé (htmlspecialchars, pour jstree) ;
// React échapperait une seconde fois si on l'affichait tel quel — on le décode pour l'affichage.
function decodeEntities(s: string): string {
  return s.replace(/&amp;|&lt;|&gt;|&quot;|&#0?39;/g, (m) => ({
    '&amp;': '&', '&lt;': '<', '&gt;': '>', '&quot;': '"', '&#39;': "'", '&#039;': "'",
  }[m] ?? m))
}

// ── Drag & drop helpers (liste plate id/parent/type, contiguïté enfants/catégorie préservée) ──
type DropAction =
  | { kind: 'before' | 'after'; targetId: string }
  | { kind: 'into'; categoryId: string }
  | { kind: 'root-end' }

function moveNode(nodes: TreeNode[], draggedId: string, action: DropAction): TreeNode[] {
  const dragged = nodes.find((n) => n.id === draggedId)
  if (!dragged) return nodes
  const rest = nodes.filter((n) => n.id !== draggedId)
  const moved: TreeNode = { ...dragged }

  if (action.kind === 'into') {
    moved.parent = action.categoryId
    const children = rest.filter((n) => n.parent === action.categoryId)
    if (children.length > 0) {
      const insertAt = rest.findIndex((n) => n.id === children[children.length - 1].id) + 1
      rest.splice(insertAt, 0, moved)
    } else {
      const catIdx = rest.findIndex((n) => n.id === action.categoryId)
      rest.splice(catIdx === -1 ? rest.length : catIdx + 1, 0, moved)
    }
    return rest
  }

  if (action.kind === 'root-end') {
    moved.parent = '#'
    rest.push(moved)
    return rest
  }

  const targetIdx = rest.findIndex((n) => n.id === action.targetId)
  if (targetIdx === -1) return nodes
  const target = rest[targetIdx]
  // Une catégorie ne peut vivre qu'à la racine, quelle que soit la cible visée.
  moved.parent = moved.type === 'category' ? '#' : target.parent
  const insertAt = action.kind === 'before' ? targetIdx : targetIdx + 1
  rest.splice(insertAt, 0, moved)
  return rest
}

// ════════════════════════════════════════════════════════════════════════════
// Page root — route-based (comme Mini-Templates) : /melis-cms/menu-manager (arbre),
// /melis-cms/menu-manager/new (création) et /melis-cms/menu-manager/:id (édition).
export default function MenuManagerPage() {
  const { id } = useParams()
  const location = useLocation()
  const base = id ? location.pathname.slice(0, location.pathname.length - id.length - 1) : location.pathname
  if (id) return <CategoryForm key={id} id={id} base={base} />
  return <MenuManagerTree base={base} />
}

// ── Arbre (liste) ──────────────────────────────────────────────────────────────
function MenuManagerTree({ base }: { base: string }) {
  const t = useT()
  const navigate = useNavigate()
  const [sites, setSites] = useState<SiteOption[]>([])
  const [languages, setLanguages] = useState<LanguageOption[]>([])
  const [siteId, setSiteId] = useState<number | null>(null)
  const [locale, setLocale] = useState('')
  const [nodes, setNodes] = useState<TreeNode[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [tick, setTick] = useState(0)
  const [mode, setMode] = useState<'react' | 'iframe'>('react')
  const [frameLoaded, setFrameLoaded] = useState(false)

  const [dragId, setDragId] = useState<string | null>(null)
  const [overTarget, setOverTarget] = useState<{ id: string; edge: 'top' | 'bottom' | 'inside' } | null>(null)
  const [overRoot, setOverRoot] = useState(false)

  const [toDelete, setToDelete] = useState<TreeNode | null>(null)
  const saveErrorTimer = useRef<ReturnType<typeof setTimeout> | null>(null)

  useEffect(() => { fetchMenuManagerSites().then((s) => { setSites(s); if (!siteId && s.length > 0) setSiteId(s[0].id) }).catch(() => null) }, [])
  useEffect(() => { fetchMenuManagerLanguages().then((l) => { setLanguages(l); if (!locale && l.length > 0) setLocale(l[0].locale) }).catch(() => null) }, [])

  useEffect(() => {
    if (!siteId || !locale) return
    setLoading(true)
    setError(null)
    fetchMenuManagerTree(siteId, locale)
      .then(setNodes)
      .catch((e) => setError(e instanceof Error ? e.message : t('err_save')))
      .finally(() => setLoading(false))
  }, [siteId, locale, tick])

  const roots = useMemo(() => nodes.filter((n) => n.parent === '#'), [nodes])
  const childrenOf = (id: string) => nodes.filter((n) => n.parent === id)

  async function persist(next: TreeNode[]) {
    setNodes(next)
    if (!siteId) return
    try {
      await saveMenuManagerTree(siteId, next)
    } catch {
      if (saveErrorTimer.current) clearTimeout(saveErrorTimer.current)
      setError(t('err_move'))
      saveErrorTimer.current = setTimeout(() => setError(null), 4000)
      setTick((x) => x + 1) // reload authoritative state
    }
  }

  function onDrop(action: DropAction) {
    if (!dragId) return
    const id = dragId
    setDragId(null); setOverTarget(null); setOverRoot(false)
    const next = moveNode(nodes, id, action)
    if (next !== nodes) persist(next)
  }

  function rowDragProps(node: TreeNode, allowInside: boolean) {
    return {
      draggable: true,
      onDragStart: (e: DragEvent) => { e.stopPropagation(); setDragId(node.id) },
      onDragEnd: () => { setDragId(null); setOverTarget(null); setOverRoot(false) },
      onDragOver: (e: DragEvent) => {
        e.preventDefault(); e.stopPropagation()
        if (!dragId || dragId === node.id) return
        const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
        const y = e.clientY - rect.top
        let edge: 'top' | 'bottom' | 'inside'
        if (allowInside && dragId !== node.id) {
          const draggedNode = nodes.find((n) => n.id === dragId)
          const ratio = y / rect.height
          edge = draggedNode?.type === 'category' ? (ratio < 0.5 ? 'top' : 'bottom') : (ratio > 0.15 && ratio < 0.85 ? 'inside' : ratio < 0.5 ? 'top' : 'bottom')
        } else {
          edge = y < rect.height / 2 ? 'top' : 'bottom'
        }
        if (overTarget?.id !== node.id || overTarget?.edge !== edge) setOverTarget({ id: node.id, edge })
      },
      onDrop: (e: DragEvent) => {
        e.preventDefault(); e.stopPropagation()
        if (!overTarget || overTarget.id !== node.id) { onDrop({ kind: 'after', targetId: node.id }); return }
        if (overTarget.edge === 'inside') onDrop({ kind: 'into', categoryId: node.id })
        else onDrop({ kind: overTarget.edge === 'top' ? 'before' : 'after', targetId: node.id })
      },
    }
  }

  async function confirmDeleteCategory() {
    if (!toDelete) return
    try { await deleteMenuManagerCategory(catId(toDelete)); setToDelete(null); setTick((x) => x + 1) }
    catch { setToDelete(null) }
  }

  function TemplateRow({ node, nested }: { node: TreeNode; nested: boolean }) {
    const isOver = overTarget?.id === node.id
    return (
      <div {...rowDragProps(node, false)}
        style={{
          display: 'flex', alignItems: 'center', gap: 10, padding: '8px 12px', marginLeft: nested ? 28 : 0,
          borderRadius: 8, cursor: 'grab', opacity: dragId === node.id ? 0.4 : 1,
          borderTop: isOver && overTarget?.edge === 'top' ? '2px solid var(--color-primary)' : '2px solid transparent',
          borderBottom: isOver && overTarget?.edge === 'bottom' ? '2px solid var(--color-primary)' : '2px solid transparent',
        }}>
        <GripIcon />
        {node.imgSource
          ? <img src={node.imgSource} alt={node.text} style={{ width: 28, height: 22, objectFit: 'cover', borderRadius: 4 }} />
          : <PlugIcon />}
        <span style={{ fontSize: 14, flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{node.text}</span>
      </div>
    )
  }

  function CategoryRow({ node }: { node: TreeNode }) {
    const isOver = overTarget?.id === node.id
    const kids = childrenOf(node.id)
    return (
      <div style={{ borderRadius: 10, border: '1px solid var(--color-border)', overflow: 'hidden' }}>
        <div {...rowDragProps(node, true)}
          style={{
            display: 'flex', alignItems: 'center', gap: 10, padding: '10px 12px', cursor: 'grab',
            background: isOver && overTarget?.edge === 'inside' ? 'color-mix(in srgb, var(--color-primary) 10%, transparent)' : 'var(--color-muted,rgba(0,0,0,.02))',
            opacity: dragId === node.id ? 0.4 : 1,
            borderTop: isOver && overTarget?.edge === 'top' ? '2px solid var(--color-primary)' : '2px solid transparent',
            borderBottom: isOver && overTarget?.edge === 'bottom' ? '2px solid var(--color-primary)' : '2px solid transparent',
          }}>
          <GripIcon />
          <FolderIcon />
          <span style={{ width: 8, height: 8, borderRadius: 999, background: node.status ? '#22c55e' : '#ef4444', flexShrink: 0 }} />
          <span style={{ fontSize: 14, fontWeight: 600, flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{decodeEntities(node.text)}</span>
          {can('edit') && <button style={iconBtn} title={t('edit')} onClick={() => navigate(`${base}/${catId(node)}?site=${siteId ?? ''}`)}><PencilIcon /></button>}
          {can('delete') && <button style={{ ...iconBtn, color: 'var(--color-destructive,#ef4444)' }} title={t('del')} onClick={() => setToDelete(node)}><TrashIcon /></button>}
        </div>
        <div style={{ padding: kids.length ? '6px 6px 8px' : 0 }}>
          {kids.map((k) => <TemplateRow key={k.id} node={k} nested />)}
        </div>
      </div>
    )
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, padding: 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16, flexWrap: 'wrap' }}>
        <div>
          <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0 }}>{t('title')}</h1>
          <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', margin: '2px 0 0' }}>{t('subtitle')}</p>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          <ViewToggle mode={mode} onChange={(m) => { setMode(m); if (m === 'iframe') setFrameLoaded(true) }} />
          <button style={btnGhost} onClick={() => setTick((x) => x + 1)} title={t('refresh')}>↻</button>
          {can('create') && <button style={btnPrimary} onClick={() => navigate(`${base}/new?site=${siteId ?? ''}`)} disabled={!siteId}><PlusIcon />{t('new_category')}</button>}
        </div>
      </div>

      {/* Vue « Old » : outil legacy en iframe */}
      {frameLoaded && (
        <div style={{ ...card, display: mode === 'iframe' ? 'flex' : 'none', flex: 1, minHeight: 480, overflow: 'hidden' }}>
          <iframe src={`/melis/react-tool-page?key=${encodeURIComponent(MELIS_KEY)}`}
            style={{ flex: 1, width: '100%', border: 0 }} title="Menu manager — Vue Melis"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-modals" />
        </div>
      )}

      <div style={{ display: mode === 'react' ? 'flex' : 'none', flexDirection: 'column', gap: 16 }}>
      {!can('list') ? (
        <div style={{ ...card, padding: '40px 16px', textAlign: 'center', fontSize: 14, color: 'var(--color-muted-foreground)' }}>{t('no_access')}</div>
      ) : (<>
      {/* Filtres */}
      <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
        <select style={{ ...inputCss, height: 36, width: 'auto', minWidth: 200 }} value={siteId ?? ''} onChange={(e) => setSiteId(e.target.value ? Number(e.target.value) : null)}>
          <option value="">{t('select_site')}</option>
          {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
        </select>
        <select style={{ ...inputCss, height: 36, width: 'auto', minWidth: 140 }} value={locale} onChange={(e) => setLocale(e.target.value)}>
          {languages.map((l) => <option key={l.id} value={l.locale}>{l.name}</option>)}
        </select>
      </div>

      {error && <div style={{ ...card, borderColor: '#fca5a5', background: '#fef2f2', color: '#b91c1c', padding: '8px 14px', fontSize: 14 }}>{error}</div>}

      {/* Arbre */}
      <div style={{ ...card, padding: 16 }}>
        {!siteId ? (
          <div style={{ padding: '32px 16px', textAlign: 'center', fontSize: 14, color: 'var(--color-muted-foreground)' }}>{t('empty_site')}</div>
        ) : loading ? (
          <div style={{ padding: '32px 16px', textAlign: 'center', fontSize: 14, color: 'var(--color-muted-foreground)' }}>{t('loading')}</div>
        ) : roots.length === 0 ? (
          <div style={{ padding: '32px 16px', textAlign: 'center', fontSize: 14, color: 'var(--color-muted-foreground)' }}>{t('empty_tree')}</div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
            {roots.map((n) => n.type === 'category'
              ? <CategoryRow key={n.id} node={n} />
              : <TemplateRow key={n.id} node={n} nested={false} />)}
          </div>
        )}

        {/* Zone de dépôt racine : visible pendant un glisser-déposer, pour retirer un template d'une catégorie */}
        {dragId && (
          <div
            onDragOver={(e) => { e.preventDefault(); setOverRoot(true) }}
            onDragLeave={() => setOverRoot(false)}
            onDrop={(e) => { e.preventDefault(); onDrop({ kind: 'root-end' }) }}
            style={{
              marginTop: 12, padding: '14px 12px', borderRadius: 8, textAlign: 'center', fontSize: 13,
              border: `1px dashed ${overRoot ? 'var(--color-primary)' : 'var(--color-border)'}`,
              color: 'var(--color-muted-foreground)',
              background: overRoot ? 'color-mix(in srgb, var(--color-primary) 8%, transparent)' : 'transparent',
            }}>
            {t('root_zone')}
          </div>
        )}
      </div>
      </>)}
      </div>

      {/* Confirmation suppression catégorie */}
      {toDelete && (
        <div style={{ position: 'fixed', inset: 0, zIndex: 50, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.5)' }}>
          <div style={{ ...card, padding: 24, width: '100%', maxWidth: 360 }}>
            <h3 style={{ fontSize: 16, fontWeight: 600, margin: 0 }}>{t('del_title')}</h3>
            <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', marginTop: 8 }}>{t('del_confirm', { n: decodeEntities(toDelete.text) })}</p>
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 20 }}>
              <button style={btnGhost} onClick={() => setToDelete(null)}>{t('cancel')}</button>
              <button style={{ ...btnGhost, borderColor: '#fca5a5', color: '#dc2626' }} onClick={confirmDeleteCategory}>{t('del')}</button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

// ── Formulaire catégorie (sous-onglet dédié) ────────────────────────────────────
// Layout : liste des langues à gauche (drapeau + surbrillance rouge = active), champ
// "Category name" à droite pour la langue sélectionnée (une langue à la fois).
function CategoryForm({ id, base }: { id: string; base: string }) {
  const t = useT()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const isEdit = id !== 'new'
  const catIdNum = isEdit ? parseInt(id, 10) : null
  const siteIdParam = searchParams.get('site')
  const siteId = siteIdParam ? Number(siteIdParam) : null

  const [languages, setLanguages] = useState<LanguageOption[]>([])
  const [activeLangId, setActiveLangId] = useState<number | null>(null)
  const [translations, setTranslations] = useState<Record<string, string>>({})
  const [status, setStatus] = useState(1)
  const [loading, setLoading] = useState(false)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [saved, setSaved] = useState(false)

  // Sous-onglet hôte : ouvre un onglet dédié à cette édition (édition multiple en parallèle).
  const subTabId = `${base}/${id}`
  useEffect(() => {
    const label = isEdit ? t('edit_title') : t('new_title')
    ;(window as unknown as SubTabW).__melisOpenSubTab?.(base, { id: subTabId, label, path: subTabId })
  }, [])

  useEffect(() => { if (!can(isEdit ? 'edit' : 'create')) navigate(base) }, [isEdit, base, navigate])

  useEffect(() => {
    fetchMenuManagerLanguages().then((l) => {
      setLanguages(l)
      if (l.length > 0) setActiveLangId((cur) => cur ?? l[0].id)
    }).catch(() => null)
  }, [])

  useEffect(() => {
    if (!isEdit || !catIdNum) return
    setLoading(true)
    fetchMenuManagerCategory(catIdNum)
      .then((d) => { setTranslations(d.translations); setStatus(d.status) })
      .catch(() => navigate(base))
      .finally(() => setLoading(false))
  }, [id])

  async function submit() {
    setError(null)
    const hasName = Object.values(translations).some((v) => (v ?? '').trim() !== '')
    if (!hasName) { setError(t('err_name')); return }
    setSaving(true)
    try {
      const activeLang = languages.find((l) => l.id === activeLangId)
      await saveMenuManagerCategory({
        catId: catIdNum,
        siteId: siteId ?? 0,
        status,
        currentLocale: activeLang?.locale ?? '',
        translations,
      })
      setSaved(true)
      setTimeout(() => navigate(base), 500)
    } catch (e) {
      setError(e instanceof Error ? e.message : t('err_save'))
    } finally { setSaving(false) }
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, padding: 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      {/* Header — pas de bouton « retour » : la barre de sous-onglets de l'hôte le fournit déjà. */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 }}>
        <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0 }}>{isEdit ? t('edit_title') : t('new_title')}</h1>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
          {saved && <span style={{ fontSize: 14, color: '#059669' }}>{t('saved')}</span>}
          <button
            onClick={() => setStatus((s) => (s ? 0 : 1))}
            style={{
              ...btnGhost, height: 32, padding: '0 12px',
              color: status ? '#059669' : '#dc2626',
              borderColor: status ? '#86efac' : '#fca5a5',
            }}>
            {status ? t('active') : t('inactive')}
          </button>
          <button style={btnPrimary} onClick={submit} disabled={saving || loading}>{saving ? '…' : t('save')}</button>
        </div>
      </div>

      {error && <div style={{ ...card, borderColor: '#fca5a5', background: '#fef2f2', color: '#b91c1c', padding: '8px 14px', fontSize: 14 }}>{error}</div>}

      {loading ? (
        <div style={{ padding: 48, textAlign: 'center', color: 'var(--color-muted-foreground)' }}>{t('loading')}</div>
      ) : (
        <div style={{ display: 'grid', gridTemplateColumns: '260px 1fr', gap: 20, alignItems: 'start' }}>
          {/* Colonne gauche : liste des langues (drapeau + surbrillance) */}
          <div style={{ ...card, overflow: 'hidden' }}>
            {languages.map((l) => {
              const isActive = activeLangId === l.id
              return (
                <button key={l.id} onClick={() => setActiveLangId(l.id)}
                  style={{
                    display: 'flex', alignItems: 'center', gap: 10, width: '100%', textAlign: 'left',
                    padding: '12px 16px', border: 0, cursor: 'pointer', fontSize: 14, fontWeight: 600,
                    background: isActive ? 'var(--color-primary, #dc2626)' : 'transparent',
                    color: isActive ? 'var(--color-primary-foreground,#fff)' : 'var(--color-foreground)',
                  }}>
                  <span style={{ flex: 1 }}>{l.name}</span>
                  <span style={{ fontSize: 18, lineHeight: 1 }}>{langFlag(l.locale)}</span>
                </button>
              )
            })}
          </div>

          {/* Colonne droite : nom de la catégorie pour la langue sélectionnée */}
          <div style={{ ...card, padding: 20 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginBottom: 8 }}>
              <span style={{ fontSize: 14, fontWeight: 600 }}>{t('f_category_name')}</span>
              <span title={t('f_category_name_hint')}><InfoIcon /></span>
            </div>
            <input style={inputCss}
              value={activeLangId != null ? (translations[String(activeLangId)] ?? '') : ''}
              onChange={(e) => {
                if (activeLangId == null) return
                setTranslations({ ...translations, [String(activeLangId)]: e.target.value })
              }}
              maxLength={128} autoComplete="off" disabled={activeLangId == null} />
            <p style={hint}>{t('f_category_name_hint')}</p>
          </div>
        </div>
      )}
    </div>
  )
}
