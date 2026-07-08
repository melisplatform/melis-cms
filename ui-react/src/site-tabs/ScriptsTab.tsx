import { useEffect, useRef, useState } from 'react'
import {
  addException, deleteException, fetchExceptions,
  type ExceptionItem,
} from '../site-scripts-api'
import { fetchTreeNodes, type MelisTreeNode } from '../cms-tree-api'

/**
 * Onglet « Scripts » de l'éditeur de site — équivalent natif React de l'onglet legacy injecté
 * par le module MelisCmsPageScriptEditor dans l'outil Site. Trois blocs, comme le legacy :
 *   1. les scripts du site (head top / head bottom / body bottom) — CHAMPS CONTRÔLÉS par l'éditeur
 *      (état levé dans SiteEditor), persistés par le bouton Save GLOBAL de l'éditeur (comme le legacy,
 *      où le save du site déclenche le listener de save des scripts). Pas de Save propre ici.
 *   2. « Current Exceptions » : liste des pages qui EXCLUENT les scripts du site (ou « No exception found. »),
 *   3. « Add an exception » : ajout d'une page (saisie de l'id + sélecteur d'arbre « sitemap », comme le legacy).
 * Les exceptions restent AUTONOMES (ajout/suppression immédiats), comme le legacy.
 */

const tr = (fr: string, en: string) => ((document.documentElement.lang || 'fr').slice(0, 2) === 'en' ? en : fr)

const card: React.CSSProperties = { borderRadius: 12, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', boxShadow: '0 1px 2px rgba(0,0,0,.04)' }
const btn: React.CSSProperties = { display: 'inline-flex', alignItems: 'center', justifyContent: 'center', gap: 6, height: 36, padding: '0 14px', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'transparent', cursor: 'pointer', fontSize: 14 }
const btnSuccess: React.CSSProperties = { ...btn, border: '1px solid #4caf50', background: 'transparent', color: '#4caf50', fontWeight: 600 }
const input: React.CSSProperties = { height: 36, width: '100%', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-background,#fff)', padding: '0 10px', fontSize: 14, boxSizing: 'border-box' }
const textareaCss: React.CSSProperties = { width: '100%', minHeight: 96, borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-background,#fff)', padding: 10, fontFamily: 'monospace', fontSize: 13, boxSizing: 'border-box', resize: 'vertical' }
const lbl: React.CSSProperties = { display: 'block', fontSize: 13, fontWeight: 600, marginBottom: 6 }
const hint: React.CSSProperties = { fontSize: 12, color: 'var(--color-muted-foreground)', margin: 0 }
const sectionTitle: React.CSSProperties = { fontSize: 16, fontWeight: 700, margin: '0 0 4px' }

/** Icône « sitemap » (équivalent fa-sitemap du bouton de sélection d'arbre legacy). */
const SitemapIcon = () => (
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <rect x="9" y="3" width="6" height="5" rx="1" /><rect x="2" y="16" width="6" height="5" rx="1" /><rect x="16" y="16" width="6" height="5" rx="1" />
    <path d="M12 8v4M5 16v-2h14v2M12 12v2" />
  </svg>
)

/** Nœud d'arbre de pages (réutilise l'arbre lazy legacy via fetchTreeNodes ; key = id de page). */
function TreeNode({ node, depth, onPick }: { node: MelisTreeNode; depth: number; onPick: (id: number, title: string) => void }) {
  const [open, setOpen] = useState(false)
  const [children, setChildren] = useState<MelisTreeNode[] | null>(null)
  const [loading, setLoading] = useState(false)

  async function toggle() {
    if (!node.lazy) return
    const next = !open
    setOpen(next)
    if (next && children === null) {
      setLoading(true)
      setChildren(await fetchTreeNodes(node.key))
      setLoading(false)
    }
  }

  return (
    <div>
      <div style={{ display: 'flex', alignItems: 'center', gap: 4, padding: '3px 6px', paddingLeft: 6 + depth * 16 }}>
        <button onClick={toggle} style={{ width: 18, height: 18, border: 0, background: 'transparent', cursor: node.lazy ? 'pointer' : 'default', color: 'var(--color-muted-foreground,#6b7280)', fontSize: 11 }}>
          {node.lazy ? (open ? '▾' : '▸') : '·'}
        </button>
        <button onClick={() => onPick(node.key, node.title)} style={{ flex: 1, textAlign: 'left', border: 0, background: 'transparent', cursor: 'pointer', fontSize: 13, padding: '2px 4px', borderRadius: 6 }}>
          {node.title}
        </button>
      </div>
      {open && (
        <div>
          {loading && <div style={{ paddingLeft: 24 + depth * 16, fontSize: 12, color: 'var(--color-muted-foreground)' }}>…</div>}
          {(children ?? []).map((c) => <TreeNode key={c.key} node={c} depth={depth + 1} onPick={onPick} />)}
        </div>
      )}
    </div>
  )
}

interface Props {
  siteId: number
  headTop: string
  onHeadTop: (v: string) => void
  headBottom: string
  onHeadBottom: (v: string) => void
  bodyBottom: string
  onBodyBottom: (v: string) => void
}

export function ScriptsTab({ siteId, headTop, onHeadTop, headBottom, onHeadBottom, bodyBottom, onBodyBottom }: Props) {
  const [exceptions, setExceptions] = useState<ExceptionItem[]>([])
  const [pageIdInput, setPageIdInput] = useState('')
  const [addError, setAddError] = useState<string | null>(null)
  const [toDelete, setToDelete] = useState<ExceptionItem | null>(null)

  // Sélecteur d'arbre (bouton « sitemap »)
  const [treeOpen, setTreeOpen] = useState(false)
  const [roots, setRoots] = useState<MelisTreeNode[] | null>(null)
  const treeRef = useRef<HTMLDivElement>(null)

  useEffect(reloadExceptions, [siteId])

  useEffect(() => {
    function onDoc(e: MouseEvent) { if (treeRef.current && !treeRef.current.contains(e.target as Node)) setTreeOpen(false) }
    document.addEventListener('mousedown', onDoc)
    return () => document.removeEventListener('mousedown', onDoc)
  }, [])

  function reloadExceptions() {
    fetchExceptions(siteId).then((r) => setExceptions(r.items)).catch(() => null)
  }

  async function openTree() {
    setTreeOpen((o) => !o)
    if (roots === null) setRoots(await fetchTreeNodes(-1))
  }

  // Les erreurs de l'API arrivent sous forme de clés tr_… (la brique n'a pas le dico du module) :
  // on mappe les deux cas connus, sinon on affiche le message brut.
  function mapAddError(msg: string): string {
    if (msg.includes('wrong_site')) return tr('Cet identifiant de page ne correspond pas à une page de ce site', "This identifier doesn't match any page of this site")
    if (msg.includes('duplicate')) return tr('La page est déjà présente dans la liste des exceptions', 'Page is already added in the exception list')
    return msg
  }

  async function addByInput() {
    const id = parseInt(pageIdInput, 10)
    if (!id) { setAddError(tr('Saisissez un identifiant de page.', 'Enter a page identifier.')); return }
    setAddError(null)
    try {
      await addException(siteId, id)
      setPageIdInput('')
      reloadExceptions()
    } catch (e) {
      setAddError(mapAddError(String((e as Error)?.message ?? e)))
    }
  }

  async function confirmDelete() {
    if (!toDelete) return
    await deleteException(toDelete.id).catch(() => null)
    setToDelete(null)
    reloadExceptions()
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, maxWidth: 760 }}>
      {/* Scripts du site — champs contrôlés, sauvegardés par le Save global de l'éditeur. */}
      <div style={{ ...card, padding: 20, display: 'flex', flexDirection: 'column', gap: 16 }}>
        <p style={hint}>{tr(
          'Scripts personnalisés appliqués à toutes les pages du site (sauf les pages exclues ci-dessous). Utilisez le bouton Enregistrer de l’éditeur.',
          'Custom scripts applied to every page of the site (except the pages excluded below). Use the editor’s Save button.',
        )}</p>
        <div>
          <label style={lbl}>{tr('Head Top (après l’ouverture de <head>)', 'Head Top (after opening <head>)')}</label>
          <textarea style={textareaCss} value={headTop} onChange={(e) => onHeadTop(e.target.value)} />
        </div>
        <div>
          <label style={lbl}>{tr('Head Bottom (avant la fermeture de </head>)', 'Head Bottom (before closing </head>)')}</label>
          <textarea style={textareaCss} value={headBottom} onChange={(e) => onHeadBottom(e.target.value)} />
        </div>
        <div>
          <label style={lbl}>{tr('Body Bottom (avant la fermeture de </body>)', 'Body Bottom (before closing </body>)')}</label>
          <textarea style={textareaCss} value={bodyBottom} onChange={(e) => onBodyBottom(e.target.value)} />
        </div>
      </div>

      {/* Current Exceptions */}
      <div style={{ ...card, padding: 20 }}>
        <h3 style={sectionTitle}>{tr('Exceptions actuelles', 'Current Exceptions')}</h3>
        <p style={{ ...hint, marginBottom: exceptions.length ? 14 : 0 }}>
          {exceptions.length > 0
            ? tr('Liste des pages qui sont des exceptions et n’incluent pas les scripts du site', 'List of pages that are exceptions and do not include the scripts directly from the site')
            : tr('Aucune exception trouvée.', 'No exception found.')}
        </p>
        {exceptions.length > 0 && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
            {exceptions.map((ex) => (
              <div key={ex.id} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '6px 10px', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)' }}>
                <span style={{ fontSize: 14 }}>{ex.pageName} <span style={{ color: 'var(--color-muted-foreground)', fontSize: 12 }}>#{ex.pageId}</span></span>
                <button style={{ ...btn, height: 28, padding: '0 10px', fontSize: 13, color: '#dc2626', borderColor: '#fca5a5' }}
                  title={tr('Supprimer l’exception', 'Delete exception')} onClick={() => setToDelete(ex)}>
                  {tr('Retirer', 'Remove')}
                </button>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Add an exception */}
      <div style={{ ...card, padding: 20 }}>
        <h3 style={sectionTitle}>{tr('Ajouter une exception', 'Add an exception')}</h3>
        <p style={{ ...hint, marginBottom: 14 }}>{tr(
          'Ajouter une page à la liste des exceptions et ne pas inclure de script du site pour cette page',
          'Add a page to the exception list and do not include the scripts directly from the site for this page',
        )}</p>

        <div ref={treeRef} style={{ position: 'relative', display: 'flex', gap: 8, maxWidth: 560 }}>
          <input style={input} value={pageIdInput} inputMode="numeric"
            onChange={(e) => { setPageIdInput(e.target.value.replace(/[^0-9]/g, '')); setAddError(null) }}
            onKeyDown={(e) => e.key === 'Enter' && addByInput()}
            placeholder={tr('Identifiant de page', 'Page identifier')} />
          <button style={{ ...btn, width: 40, padding: 0, flexShrink: 0, color: 'var(--color-muted-foreground)' }}
            title={tr('Choisir dans l’arbre des pages', 'Pick from the page tree')} onClick={openTree}>
            <SitemapIcon />
          </button>
          <button style={{ ...btnSuccess, flexShrink: 0 }} onClick={addByInput}>{tr('Ajouter', 'Add')}</button>

          {treeOpen && (
            <div style={{ ...card, position: 'absolute', zIndex: 60, bottom: 42, left: 0, right: 0, maxHeight: 320, overflow: 'auto', boxShadow: '0 -8px 24px rgba(0,0,0,.12)', padding: 6 }}>
              {roots === null ? (
                <div style={{ padding: 12, fontSize: 13, color: 'var(--color-muted-foreground)' }}>{tr('Chargement…', 'Loading…')}</div>
              ) : roots.length === 0 ? (
                <div style={{ padding: 12, fontSize: 13, color: 'var(--color-muted-foreground)' }}>{tr('Aucune page', 'No page')}</div>
              ) : roots.map((n) => (
                <TreeNode key={n.key} node={n} depth={0} onPick={(id) => { setPageIdInput(String(id)); setAddError(null); setTreeOpen(false) }} />
              ))}
            </div>
          )}
        </div>
        {addError && <p style={{ margin: '10px 0 0', fontSize: 13, color: '#b91c1c' }}>{addError}</p>}
      </div>

      {/* Confirmation de suppression (comme le legacy) */}
      {toDelete && (
        <div style={{ position: 'fixed', inset: 0, zIndex: 80, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.5)' }}>
          <div style={{ ...card, padding: 24, width: '100%', maxWidth: 400 }}>
            <h3 style={{ fontSize: 16, fontWeight: 600, margin: 0 }}>{tr('Supprimer l’exception', 'Delete exception')}</h3>
            <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', marginTop: 8 }}>
              {tr('Êtes-vous sûr(e) de vouloir supprimer cette page de la liste des exceptions ?', 'Are you sure you want to delete this page from the exception list?')}
            </p>
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 20 }}>
              <button style={btn} onClick={() => setToDelete(null)}>{tr('Annuler', 'Cancel')}</button>
              <button style={{ ...btn, borderColor: '#fca5a5', color: '#dc2626' }} onClick={confirmDelete}>{tr('Supprimer', 'Delete')}</button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
