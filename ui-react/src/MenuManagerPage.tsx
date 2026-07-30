import { useEffect, useMemo, useRef, useState, type CSSProperties, type DragEvent, type ReactNode } from 'react'
import { useLocation, useNavigate, useSearchParams } from 'react-router-dom'
import {
  deleteMenuManagerCategory, fetchMenuManagerCategory, fetchMenuManagerLanguages,
  fetchMenuManagerSites, fetchMenuManagerTree, saveMenuManagerCategory, saveMenuManagerTree,
  type LanguageOption, type SiteOption, type TreeNode,
} from './menu-manager-api'
import { ViewToggle } from './ViewToggle'
import { Flag, FlagSelect } from './PageTabs'
import { useIsNarrow } from './shared/useIsNarrow'

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

// Route effective de la brique Mini-Templates : dérivée de l'arbre du menu (pas forcément
// /melis-cms/mini-templates, son chemin de manifeste — cf. lib/bricks.ts:brickRoute côté hôte),
// donc pas hardcodable. L'hôte persiste ce mapping forwardKey→route dans sessionStorage
// (lib/tool-routes.ts) pour un deep-link à froid ; on le relit ici pour une navigation
// cross-brique fiable, avec repli sur la route de manifeste si le registre n'est pas encore prêt.
const MINI_TEMPLATE_MANAGER_FORWARD_KEY = 'MelisCms/MiniTemplateManager'
const MINI_TEMPLATE_MANAGER_FALLBACK_ROUTE = '/melis-cms/mini-templates'
function miniTemplateManagerRoute(): string {
  try {
    const raw = sessionStorage.getItem('melis-tool-routes')
    if (raw) {
      const route = (JSON.parse(raw).forwardToRoute ?? {})[MINI_TEMPLATE_MANAGER_FORWARD_KEY]
      if (route) return route
    }
  } catch { /* ignore */ }
  return MINI_TEMPLATE_MANAGER_FALLBACK_ROUTE
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
    title: 'Menu manager', subtitle: 'Gérez ici les catégories de mini-templates depuis le menu du plugin',
    f_site: 'Site', select_site: '— Choisir un site —',
    f_lang: 'Langue', refresh: 'Rafraîchir', loading: 'Chargement…',
    new_category: 'Nouvelle catégorie', add_minitemplate: 'Ajouter un mini-template',
    edit_minitemplate: 'Modifier le mini-template', collapse: 'Réduire', expand: 'Développer',
    empty_site: 'Sélectionnez un site pour voir son arborescence.',
    empty_tree: 'Aucun élément. Créez une catégorie ou déposez un mini-template ici.',
    root_zone: 'Déposer ici pour retirer d’une catégorie',
    edit: 'Modifier', del: 'Supprimer', cancel: 'Annuler', close: 'Fermer', save: 'Enregistrer', back: 'retour',
    active: 'Actif', inactive: 'Inactif', saved: 'Enregistré ✓',
    del_title: 'Supprimer la catégorie',
    del_confirm: 'Êtes-vous sûr(e) de vouloir supprimer cette catégorie ?',
    del_blocked_text: 'Vous ne pouvez supprimer que des catégories vides',
    new_title: 'Nouvelle catégorie', edit_title: 'Modifier la catégorie',
    f_site_hint: 'Le site ne peut plus être modifié une fois la catégorie créée.',
    err_site: 'Le site est obligatoire.',
    f_category_name: 'Category name', f_category_name_hint: 'Un nom par langue (au moins une langue requise).',
    err_save: 'Erreur lors de la sauvegarde', err_move: 'Erreur lors du déplacement — arbre rechargé.',
    err_name: 'Au moins un nom de catégorie est requis.',
    no_access: 'Vous n’avez pas les droits pour consulter cet outil.',
  },
  en: {
    title: 'Menu manager', subtitle: 'Manage here the categories of mini-templates from the plugin menu',
    f_site: 'Site', select_site: '— Choose a site —',
    f_lang: 'Language', refresh: 'Refresh', loading: 'Loading…',
    new_category: 'New category', add_minitemplate: 'Add mini-template',
    edit_minitemplate: 'Edit mini-template', collapse: 'Collapse', expand: 'Expand',
    empty_site: 'Select a site to view its tree.',
    empty_tree: 'Nothing here yet. Create a category or drop a mini-template here.',
    root_zone: 'Drop here to remove from a category',
    edit: 'Edit', del: 'Delete', cancel: 'Cancel', close: 'Close', save: 'Save', back: 'back',
    active: 'Active', inactive: 'Inactive', saved: 'Saved ✓',
    del_title: 'Delete category',
    del_confirm: 'Are you sure you want to delete this category?',
    del_blocked_text: 'You can only delete an empty category',
    new_title: 'New category', edit_title: 'Edit category',
    f_site_hint: 'The site cannot be changed once the category is created.',
    err_site: 'Site is required.',
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
const ChevronIcon = ({ open }: { open: boolean }) => <svg style={{ width: 14, height: 14, flexShrink: 0, transform: open ? 'rotate(90deg)' : 'none', transition: 'transform .12s', color: 'var(--color-muted-foreground)' }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="M9 6l6 6-6 6" /></svg>
const GripIcon = () => <svg style={{ width: 13, height: 13, flexShrink: 0, color: 'var(--color-muted-foreground)' }} viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" /><circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" /><circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" /></svg>
const InfoIcon = () => <svg style={{ width: 15, height: 15, flexShrink: 0, color: 'var(--color-muted-foreground)' }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10" /><path d="M12 16v-4M12 8h.01" /></svg>

// ── Interrupteur actif/inactif (vert = actif, rouge = inactif) ──
function StatusToggle({ checked, onChange, labelOn, labelOff }: {
  checked: boolean; onChange: (v: boolean) => void; labelOn: string; labelOff: string
}) {
  return (
    <button type="button" role="switch" aria-checked={checked} onClick={() => onChange(!checked)}
      style={{ display: 'inline-flex', alignItems: 'center', gap: 8, border: 0, background: 'transparent', cursor: 'pointer', padding: 0 }}>
      <span style={{
        position: 'relative', width: 40, height: 22, borderRadius: 999, flexShrink: 0,
        background: checked ? '#22c55e' : '#ef4444', transition: 'background .15s',
      }}>
        <span style={{
          position: 'absolute', top: 2, left: checked ? 20 : 2, width: 18, height: 18, borderRadius: 999,
          background: '#fff', boxShadow: '0 1px 2px rgba(0,0,0,.3)', transition: 'left .15s',
        }} />
      </span>
      <span style={{ fontSize: 14, fontWeight: 500, color: checked ? '#059669' : '#dc2626' }}>
        {checked ? labelOn : labelOff}
      </span>
    </button>
  )
}

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
export default function MenuManagerPage({ active = true }: { active?: boolean }) {
  const location = useLocation()
  // ⚠️ Les briques sont montées par Shell HORS de la route `:id` de l'hôte (rendu direct dans
  // <main>, PAS via l'Outlet) : `useParams()` ne voit donc jamais le segment `:id` → `id` serait
  // toujours undefined et le formulaire ne s'afficherait jamais (clic « Modifier » = URL change
  // mais l'arbre reste affiché). On dérive l'id + la base du pathname : la brique est montée sur
  // 2 segments (/melis-cms/menu-manager) ; un 3e segment (`new` ou l'id numérique d'une catégorie)
  // déclenche le formulaire en sous-onglet.
  // Persistante (manifest) : on GÈLE le pathname quand inactive, sinon un pathname étranger dériverait
  // un id étranger → bascule formulaire + fetch d'un autre outil = détournement. Cf. skill.
  const [frozenPath, setFrozenPath] = useState(location.pathname)
  useEffect(() => { if (active) setFrozenPath(location.pathname) }, [active, location.pathname])
  const segs = (active ? location.pathname : frozenPath).replace(/^\/+|\/+$/g, '').split('/')
  const base = '/' + segs.slice(0, 2).join('/')
  const id = segs.length > 2 ? segs[2] : undefined
  if (id) return <CategoryForm key={id} id={id} base={base} />
  return <MenuManagerTree base={base} />
}

// ── Lignes de l'arbre ────────────────────────────────────────────────────────────
// Déclarées au niveau module (PAS imbriquées dans MenuManagerTree) : un composant défini
// à l'intérieur d'un autre est recréé (nouvelle identité de type) à chaque rendu du
// parent, ce qui force React à démonter/remonter son DOM. Pendant un drag natif HTML5,
// ce remount en plein "dragover" (déclenché par le setOverTarget de handleRowDragOver)
// arrache le nœud source de la session de drag → le drop n'aboutit jamais (ligne restée
// grisée). Garder ces composants stables — seules les props changent entre les rendus.
type RowDnDProps = {
  dragId: string | null
  overTarget: { id: string; edge: 'top' | 'bottom' | 'inside' } | null
  onDragStart: (id: string) => void
  onDragEnd: () => void
  onRowDragOver: (node: TreeNode, allowInside: boolean, e: DragEvent) => void
  onRowDrop: (node: TreeNode, e: DragEvent) => void
}

// Colonne de connecteurs d'arbre (jstree-like) : un tronçon vertical (spine) + un té horizontal
// vers l'en-tête du nœud. 'mid' = spine pleine hauteur (relie au frère suivant), 'last' = demi-spine
// s'arrêtant au coude (└). La spine longe TOUT le sous-arbre (en-tête + enfants) → ligne continue
// entre frères. Chaque niveau ajoute son propre paddingLeft → indentation cumulée.
const ROW_MID = 19 // centre vertical d'une ligne (~hauteur/2) : position du té horizontal
function Branch({ connector, children }: { connector?: 'mid' | 'last'; children: ReactNode }) {
  // Indentation réduite sur narrow : un arbre profond pousserait sinon les libellés hors écran
  // (cumul paddingLeft × niveaux) — le texte tronque déjà (voir CategoryRow/TemplateRow), mais
  // moins d'indent par niveau laisse plus de place au texte avant la troncature.
  const narrow = useIsNarrow()
  const indent = narrow ? 14 : 22
  const teeWidth = narrow ? 8 : 13
  return (
    <div style={{ position: 'relative', paddingLeft: connector ? indent : 0 }}>
      {connector && (<>
        <span style={{ position: 'absolute', left: 7, top: 0, height: connector === 'last' ? ROW_MID : '100%', width: 1, background: 'var(--color-border)' }} />
        <span style={{ position: 'absolute', left: 7, top: ROW_MID, width: teeWidth, height: 1, background: 'var(--color-border)' }} />
      </>)}
      {children}
    </div>
  )
}

function TemplateRow({ node, dragId, overTarget, onDragStart, onDragEnd, onRowDragOver, onRowDrop, t, onEditTemplate }: RowDnDProps & {
  node: TreeNode
  t: (key: string, vars?: Record<string, string | number>) => string
  onEditTemplate: (node: TreeNode) => void
}) {
  const isOver = overTarget?.id === node.id
  const narrow = useIsNarrow()
  return (
    <div
      draggable
      onDragStart={(e) => { e.stopPropagation(); onDragStart(node.id) }}
      onDragEnd={onDragEnd}
      onDragOver={(e) => onRowDragOver(node, false, e)}
      onDrop={(e) => onRowDrop(node, e)}
      style={{
        display: 'flex', alignItems: 'center', gap: narrow ? 6 : 10, padding: narrow ? '7px 6px' : '7px 10px',
        borderRadius: 8, cursor: 'grab', opacity: dragId === node.id ? 0.4 : 1,
        borderTop: isOver && overTarget?.edge === 'top' ? '2px solid var(--color-primary)' : '2px solid transparent',
        borderBottom: isOver && overTarget?.edge === 'bottom' ? '2px solid var(--color-primary)' : '2px solid transparent',
      }}>
      <GripIcon />
      {node.imgSource
        ? <img src={node.imgSource} alt={node.text} style={{ width: 28, height: 22, objectFit: 'cover', borderRadius: 4 }} />
        : <PlugIcon />}
      <span style={{ fontSize: 14, flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{node.text}</span>
      {can('edit') && <button style={iconBtn} title={t('edit_minitemplate')} onClick={(e) => { e.stopPropagation(); onEditTemplate(node) }}><PencilIcon /></button>}
    </div>
  )
}

// En-tête de catégorie seul (nœud « dossier », cible de drop « into ») — les enfants sont
// composés par le rendu de l'arbre (renderNode) pour que la spine du connecteur les traverse.
function CategoryRow({ node, kids, expanded, onToggle, dragId, overTarget, onDragStart, onDragEnd, onRowDragOver, onRowDrop, t, onEdit, onAddMiniTemplate, onDeleteRequest }: RowDnDProps & {
  node: TreeNode; kids: TreeNode[]
  expanded: boolean
  onToggle: () => void
  t: (key: string, vars?: Record<string, string | number>) => string
  onEdit: (node: TreeNode) => void
  onAddMiniTemplate: (node: TreeNode) => void
  onDeleteRequest: (node: TreeNode) => void
}) {
  const isOver = overTarget?.id === node.id
  const hasKids = kids.length > 0
  const narrow = useIsNarrow()
  return (
    <div
      draggable
      onDragStart={(e) => { e.stopPropagation(); onDragStart(node.id) }}
      onDragEnd={onDragEnd}
      onDragOver={(e) => onRowDragOver(node, true, e)}
      onDrop={(e) => onRowDrop(node, e)}
      style={{
        display: 'flex', alignItems: 'center', gap: narrow ? 6 : 8, padding: narrow ? '8px 6px' : '8px 10px', cursor: 'grab', borderRadius: 8,
        background: isOver && overTarget?.edge === 'inside' ? 'color-mix(in srgb, var(--color-primary) 12%, transparent)' : 'var(--color-muted,rgba(0,0,0,.03))',
        opacity: dragId === node.id ? 0.4 : 1,
        borderTop: isOver && overTarget?.edge === 'top' ? '2px solid var(--color-primary)' : '2px solid transparent',
        borderBottom: isOver && overTarget?.edge === 'bottom' ? '2px solid var(--color-primary)' : '2px solid transparent',
      }}>
      <GripIcon />
      {hasKids
        ? <button style={{ ...iconBtn, width: 20, height: 20 }} title={expanded ? t('collapse') : t('expand')}
            onClick={(e) => { e.stopPropagation(); onToggle() }}><ChevronIcon open={expanded} /></button>
        : <span style={{ width: 20, flexShrink: 0 }} />}
      <FolderIcon />
      <span style={{ width: 8, height: 8, borderRadius: 999, background: node.status ? '#22c55e' : '#ef4444', flexShrink: 0 }} />
      <span style={{ fontSize: 14, fontWeight: 600, flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{decodeEntities(node.text)}</span>
      {hasKids && <span style={{ fontSize: 12, color: 'var(--color-muted-foreground)', flexShrink: 0 }}>{kids.length}</span>}
      {can('create') && <button style={iconBtn} title={t('add_minitemplate')} onClick={() => onAddMiniTemplate(node)}><PlusIcon /></button>}
      {can('edit') && <button style={iconBtn} title={t('edit')} onClick={() => onEdit(node)}><PencilIcon /></button>}
      {can('delete') && <button style={{ ...iconBtn, color: 'var(--color-destructive,#ef4444)' }} title={t('del')} onClick={() => onDeleteRequest(node)}><TrashIcon /></button>}
    </div>
  )
}

// ── Arbre (liste) ──────────────────────────────────────────────────────────────
function MenuManagerTree({ base }: { base: string }) {
  const t = useT()
  const narrow = useIsNarrow()
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
  const [deleteBlocked, setDeleteBlocked] = useState<TreeNode | null>(null)
  const saveErrorTimer = useRef<ReturnType<typeof setTimeout> | null>(null)

  // Catégories réduites (arbre) — vide = toutes développées par défaut.
  const [collapsed, setCollapsed] = useState<Set<string>>(new Set())
  const toggleCollapse = (id: string) => setCollapsed((prev) => {
    const next = new Set(prev)
    next.has(id) ? next.delete(id) : next.add(id)
    return next
  })

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

  function applyDrop(action: DropAction) {
    if (!dragId) return
    const id = dragId
    setDragId(null); setOverTarget(null); setOverRoot(false)
    const next = moveNode(nodes, id, action)
    if (next !== nodes) persist(next)
  }

  function handleDragStart(id: string) { setDragId(id) }
  function handleDragEnd() { setDragId(null); setOverTarget(null); setOverRoot(false) }
  function handleRowDragOver(node: TreeNode, allowInside: boolean, e: DragEvent) {
    e.preventDefault(); e.stopPropagation()
    if (!dragId || dragId === node.id) return
    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
    const y = e.clientY - rect.top
    let edge: 'top' | 'bottom' | 'inside'
    if (allowInside) {
      const draggedNode = nodes.find((n) => n.id === dragId)
      const ratio = y / rect.height
      edge = draggedNode?.type === 'category' ? (ratio < 0.5 ? 'top' : 'bottom') : (ratio > 0.15 && ratio < 0.85 ? 'inside' : ratio < 0.5 ? 'top' : 'bottom')
    } else {
      edge = y < rect.height / 2 ? 'top' : 'bottom'
    }
    setOverTarget((cur) => (cur?.id === node.id && cur?.edge === edge ? cur : { id: node.id, edge }))
  }
  function handleRowDrop(node: TreeNode, e: DragEvent) {
    e.preventDefault(); e.stopPropagation()
    if (!overTarget || overTarget.id !== node.id) { applyDrop({ kind: 'after', targetId: node.id }); return }
    if (overTarget.edge === 'inside') applyDrop({ kind: 'into', categoryId: node.id })
    else applyDrop({ kind: overTarget.edge === 'top' ? 'before' : 'after', targetId: node.id })
  }

  async function confirmDeleteCategory() {
    if (!toDelete) return
    try { await deleteMenuManagerCategory(catId(toDelete)); setToDelete(null); setTick((x) => x + 1) }
    catch { setToDelete(null) }
  }

  // Ouvre l'édition d'un mini-template dans l'outil Mini-Templates (sous-onglet dédié).
  // Id composite site~name (module + nom), tel qu'attendu par la route :id de la brique.
  function editTemplate(node: TreeNode) {
    navigate(`${miniTemplateManagerRoute()}/${node.module ?? ''}~${node.id}`)
  }

  // Rendu récursif d'un nœud : chaque nœud est enveloppé d'un Branch (connecteur ├/└). La spine
  // du Branch parent traverse l'en-tête ET les enfants → l'arbre est continu, y compris à la racine.
  function renderNode(n: TreeNode, connector?: 'mid' | 'last'): ReactNode {
    const rowDnD = {
      dragId, overTarget,
      onDragStart: handleDragStart, onDragEnd: handleDragEnd,
      onRowDragOver: handleRowDragOver, onRowDrop: handleRowDrop,
    }
    if (n.type === 'category') {
      const kids = childrenOf(n.id)
      const expanded = !collapsed.has(n.id)
      return (
        <Branch key={n.id} connector={connector}>
          <CategoryRow node={n} kids={kids} expanded={expanded} onToggle={() => toggleCollapse(n.id)}
            {...rowDnD} t={t}
            onEdit={(cat) => navigate(`${base}/${catId(cat)}?site=${siteId ?? ''}`)}
            onAddMiniTemplate={(cat) => navigate(`${miniTemplateManagerRoute()}/new?site=${siteId ?? ''}&category=${catId(cat)}&catName=${encodeURIComponent(decodeEntities(cat.text))}`)}
            onDeleteRequest={(cat) => (childrenOf(cat.id).length > 0 ? setDeleteBlocked(cat) : setToDelete(cat))} />
          {expanded && kids.map((k, i) => renderNode(k, i === kids.length - 1 ? 'last' : 'mid'))}
        </Branch>
      )
    }
    return (
      <Branch key={n.id} connector={connector}>
        <TemplateRow node={n} {...rowDnD} t={t} onEditTemplate={editTemplate} />
      </Branch>
    )
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, padding: narrow ? 16 : 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      {/* Header — même rangée sur desktop ; sur narrow, gros bloc titre + sous-rangées de contrôles
          (icônes) puis le bouton primaire « Nouvelle catégorie » en pleine largeur, comme le
          « + New » d'un pattern déjà en place ailleurs. */}
      {narrow ? (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
          <div style={{ minWidth: 0 }}>
            <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{t('title')}</h1>
            <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', margin: '2px 0 0' }}>{t('subtitle')}</p>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <ViewToggle mode={mode} onChange={(m) => { setMode(m); if (m === 'iframe') setFrameLoaded(true) }} compact />
            <button style={btnGhost} onClick={() => setTick((x) => x + 1)} title={t('refresh')}>↻</button>
            {can('create') && <button style={btnGhost} onClick={() => navigate(`${miniTemplateManagerRoute()}/new?site=${siteId ?? ''}`)} disabled={!siteId} title={t('add_minitemplate')}><PlusIcon /></button>}
          </div>
          {can('create') && <button style={{ ...btnPrimary, width: '100%', justifyContent: 'center' }} onClick={() => navigate(`${base}/new?site=${siteId ?? ''}`)} disabled={!siteId}><PlusIcon />{t('new_category')}</button>}
        </div>
      ) : (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16, flexWrap: 'wrap' }}>
          <div>
            <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0 }}>{t('title')}</h1>
            <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', margin: '2px 0 0' }}>{t('subtitle')}</p>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <ViewToggle mode={mode} onChange={(m) => { setMode(m); if (m === 'iframe') setFrameLoaded(true) }} />
            <button style={btnGhost} onClick={() => setTick((x) => x + 1)} title={t('refresh')}>↻</button>
            {can('create') && <button style={btnGhost} onClick={() => navigate(`${miniTemplateManagerRoute()}/new?site=${siteId ?? ''}`)} disabled={!siteId}><PlusIcon />{t('add_minitemplate')}</button>}
            {can('create') && <button style={btnPrimary} onClick={() => navigate(`${base}/new?site=${siteId ?? ''}`)} disabled={!siteId}><PlusIcon />{t('new_category')}</button>}
          </div>
        </div>
      )}

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
      <div style={{ display: 'flex', flexDirection: narrow ? 'column' : 'row', gap: 8, flexWrap: narrow ? 'nowrap' : 'wrap' }}>
        <select style={{ ...inputCss, height: 36, width: narrow ? '100%' : 'auto', minWidth: narrow ? undefined : 200 }} value={siteId ?? ''} onChange={(e) => setSiteId(e.target.value ? Number(e.target.value) : null)}>
          <option value="">{t('select_site')}</option>
          {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
        </select>
        <div style={{ width: narrow ? '100%' : 190 }}>
          <FlagSelect
            value={languages.find((l) => l.locale === locale)?.id ?? 0}
            onChange={(id) => { const l = languages.find((x) => x.id === id); if (l) setLocale(l.locale) }}
            options={languages}
          />
        </div>
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
          <div style={{ display: 'flex', flexDirection: 'column' }}>
            {roots.map((n, i) => renderNode(n, i === roots.length - 1 ? 'last' : 'mid'))}
          </div>
        )}

        {/* Zone de dépôt racine : visible pendant un glisser-déposer, pour retirer un template d'une catégorie */}
        {dragId && (
          <div
            onDragOver={(e) => { e.preventDefault(); setOverRoot(true) }}
            onDragLeave={() => setOverRoot(false)}
            onDrop={(e) => { e.preventDefault(); applyDrop({ kind: 'root-end' }) }}
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

      {/* Confirmation suppression catégorie (catégorie vide uniquement — cf. deleteBlocked ci-dessous) */}
      {toDelete && (
        <div style={{ position: 'fixed', inset: 0, zIndex: 50, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.5)' }}>
          <div style={{ ...card, padding: 24, width: '100%', maxWidth: 360 }}>
            <h3 style={{ fontSize: 16, fontWeight: 600, margin: 0 }}>{t('del_title')}</h3>
            <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', marginTop: 8 }}>{t('del_confirm')}</p>
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 20 }}>
              <button style={btnGhost} onClick={() => setToDelete(null)}>{t('cancel')}</button>
              <button style={{ ...btnGhost, borderColor: '#fca5a5', color: '#dc2626' }} onClick={confirmDeleteCategory}>{t('del')}</button>
            </div>
          </div>
        </div>
      )}

      {/* Catégorie non vide : suppression bloquée (comme en legacy — pas de confirmation, juste info) */}
      {deleteBlocked && (
        <div style={{ position: 'fixed', inset: 0, zIndex: 50, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.5)' }}>
          <div style={{ ...card, padding: 24, width: '100%', maxWidth: 360 }}>
            <h3 style={{ fontSize: 16, fontWeight: 600, margin: 0 }}>{t('del_title')}</h3>
            <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', marginTop: 8 }}>{t('del_blocked_text')}</p>
            <div style={{ marginTop: 20 }}>
              <button style={{ ...btnPrimary, width: '100%', justifyContent: 'center', background: '#dc2626' }} onClick={() => setDeleteBlocked(null)}>{t('close')}</button>
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
  const narrow = useIsNarrow()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const isEdit = id !== 'new'
  const catIdNum = isEdit ? parseInt(id, 10) : null
  const siteIdParam = searchParams.get('site')

  const [sites, setSites] = useState<SiteOption[]>([])
  const [siteId, setSiteId] = useState<number | null>(siteIdParam ? Number(siteIdParam) : null)
  const [siteError, setSiteError] = useState(false)
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

  useEffect(() => { fetchMenuManagerSites().then(setSites).catch(() => null) }, [])

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
    const noSite = !isEdit && !siteId
    setSiteError(noSite)
    const hasName = Object.values(translations).some((v) => (v ?? '').trim() !== '')
    if (noSite) return
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
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, padding: narrow ? 16 : 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      {/* Header — pas de bouton « retour » : la barre de sous-onglets de l'hôte le fournit déjà. */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16, flexWrap: narrow ? 'wrap' : 'nowrap' }}>
        <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0 }}>{isEdit ? t('edit_title') : t('new_title')}</h1>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
          {saved && <span style={{ fontSize: 14, color: '#059669' }}>{t('saved')}</span>}
          <StatusToggle checked={!!status} onChange={(v) => setStatus(v ? 1 : 0)} labelOn={t('active')} labelOff={t('inactive')} />
          <button style={btnPrimary} onClick={submit} disabled={saving || loading}>{saving ? '…' : t('save')}</button>
        </div>
      </div>

      {error && <div style={{ ...card, borderColor: '#fca5a5', background: '#fef2f2', color: '#b91c1c', padding: '8px 14px', fontSize: 14 }}>{error}</div>}

      {loading ? (
        <div style={{ padding: 48, textAlign: 'center', color: 'var(--color-muted-foreground)' }}>{t('loading')}</div>
      ) : (<>
        {/* Site — indépendant des onglets de langue, fixé à la création (comme en legacy) */}
        <div style={{ ...card, padding: 20, maxWidth: narrow ? undefined : 420 }}>
          <label style={label}>{t('f_site')}</label>
          <select style={{ ...inputCss, borderColor: siteError ? '#fca5a5' : undefined }}
            value={siteId ?? ''} disabled={isEdit}
            onChange={(e) => { const v = e.target.value ? Number(e.target.value) : null; setSiteId(v); if (v) setSiteError(false) }}>
            <option value="">{t('select_site')}</option>
            {sites.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
          </select>
          {siteError
            ? <p style={{ ...hint, color: '#b91c1c' }}>{t('err_site')}</p>
            : <p style={hint}>{t('f_site_hint')}</p>}
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: narrow ? '1fr' : '260px 1fr', gap: 20, alignItems: 'start' }}>
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
                  <Flag locale={l.locale} size={22} />
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
      </>)}
    </div>
  )
}
