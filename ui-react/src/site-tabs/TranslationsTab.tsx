import { Fragment, useEffect, useMemo, useState } from 'react'
import { fetchTranslations, saveTranslation, deleteTranslation, type TransKey } from '../sites-api'
import { Flag } from '../PageTabs'
import { useIsNarrow } from '../shared/useIsNarrow'
import { ExpandToggle, HiddenColsRow } from '../shared/ExpandableRow'
import { FormErrorBanner, koNotify, okNotify, type FormIssue } from '../shared/melis-form-errors'

/**
 * Onglet "Traductions de site" : CRUD autonome (endpoints legacy dédiés saveTranslation /
 * deleteTranslation / getTranslation). Liste regroupée par clé, une colonne par langue active,
 * modal d'édition avec un textarea par langue. Indépendant du bouton Save global de l'éditeur.
 */

const tr = (fr: string, en: string) => ((document.documentElement.lang || 'fr').slice(0, 2) === 'en' ? en : fr)
const card: React.CSSProperties = { borderRadius: 10, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)' }
const btn: React.CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 34, padding: '0 14px', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'transparent', cursor: 'pointer', fontSize: 14 }
const btnPrimary: React.CSSProperties = { ...btn, border: 0, background: 'var(--color-primary,#cb4040)', color: '#fff', fontWeight: 600 }
const input: React.CSSProperties = { height: 36, width: '100%', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-background,#fff)', padding: '0 10px', fontSize: 14, boxSizing: 'border-box' }
const td: React.CSSProperties = { fontSize: 13, padding: '8px 12px', borderTop: '1px solid var(--color-border,#f0f0f0)', verticalAlign: 'top' }
const th: React.CSSProperties = { textAlign: 'left', fontSize: 12, fontWeight: 600, color: 'var(--color-muted-foreground)', padding: '8px 12px' }
// Boutons d'action alignés sur les autres outils React (SitesList, CmsLanguagePage…) : icônes Lucide + iconBtn.
const iconBtn: React.CSSProperties = { display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 28, height: 28, borderRadius: 6, border: 0, background: 'transparent', color: 'var(--color-muted-foreground)', cursor: 'pointer' }
const sIcon = { width: 15, height: 15, flexShrink: 0 } as const
const PencilIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" /></svg>
const TrashIcon = () => <svg style={sIcon} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /></svg>
// Sur mobile, la clé peut être très longue et repousser les boutons d'action hors de l'écran → on la tronque (titre = clé complète).
const KEY_MAX_NARROW = 22
const truncKey = (key: string, narrow: boolean) => (narrow && key.length > KEY_MAX_NARROW ? key.slice(0, KEY_MAX_NARROW) + '…' : key)

interface Lang { id: number; locale: string; name: string }
interface Draft { key: string; isNew: boolean; texts: Record<number, { mstId: number; msttId: number; text: string }> }

export function TranslationsTab({ siteId, langs }: { siteId: number; langs: Lang[] }) {
  const narrow = useIsNarrow()
  const [rows, setRows] = useState<TransKey[]>([])
  const [loading, setLoading] = useState(false)
  const [search, setSearch] = useState('')
  const [draft, setDraft] = useState<Draft | null>(null)
  const [toDelete, setToDelete] = useState<TransKey | null>(null)
  const [saving, setSaving] = useState(false)
  const [err, setErr] = useState<string | null>(null)
  const [issues, setIssues] = useState<FormIssue[]>([]) // champs fautifs listés dans le bandeau
  const [expandedKeys, setExpandedKeys] = useState<Set<string>>(new Set())
  function toggleExpand(key: string) {
    setExpandedKeys((prev) => { const next = new Set(prev); if (next.has(key)) next.delete(key); else next.add(key); return next })
  }
  // Table sans ColManager (pas de préférence de colonnes) — collapse à la seule clé sur narrow,
  // toutes les langues révélées via le "+" (pattern « sous-page sans ColManager »).
  const colCount = narrow ? 3 : langs.length + 2

  const load = () => { setLoading(true); fetchTranslations(siteId).then(setRows).catch(() => setRows([])).finally(() => setLoading(false)) }
  useEffect(load, [siteId])

  // Recherche sur la clé ET sur les textes de toutes les langues.
  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase()
    if (!q) return rows
    return rows.filter((r) =>
      r.key.toLowerCase().includes(q) ||
      Object.values(r.texts).some((t) => (t?.text ?? '').toLowerCase().includes(q)),
    )
  }, [rows, search])

  function openNew() {
    const texts: Draft['texts'] = {}
    for (const l of langs) texts[l.id] = { mstId: 0, msttId: 0, text: '' }
    setDraft({ key: '', isNew: true, texts }); setErr(null); setIssues([])
  }
  function openEdit(r: TransKey) {
    const texts: Draft['texts'] = {}
    for (const l of langs) {
      const t = r.texts[l.id]
      texts[l.id] = { mstId: t?.mstId ?? r.mstId, msttId: t?.msttId ?? 0, text: t?.text ?? '' }
    }
    setDraft({ key: r.key, isNew: false, texts }); setErr(null); setIssues([])
  }

  async function save() {
    if (!draft) return
    setErr(null); setIssues([])
    // Validation client → un item par champ fautif, listé dans le bandeau (pattern unifié).
    if (!draft.key.trim()) {
      setErr(tr('Veuillez corriger les champs suivants :', 'Please check the following fields:'))
      setIssues([{ label: tr('Clé', 'Key'), message: tr('La clé est requise.', 'Key is required.') }])
      return
    }
    setSaving(true)
    try {
      const res = await saveTranslation(siteId, draft.key.trim(), langs.map((l) => ({
        langId: l.id, mstId: draft.texts[l.id]?.mstId ?? 0, msttId: draft.texts[l.id]?.msttId ?? 0, text: draft.texts[l.id]?.text ?? '',
      })))
      if (res.success === true || (res.success as unknown) === 1) { okNotify(tr('Traductions', 'Translations'), tr('Enregistré ✓', 'Saved ✓')); setDraft(null); load() }
      else { const m = tr('Échec de l’enregistrement.', 'Save failed.'); setErr(m); koNotify(tr('Traductions', 'Translations'), m) }
    } catch (e) { const m = String((e as Error)?.message ?? e); setErr(m); koNotify(tr('Traductions', 'Translations'), m) } finally { setSaving(false) }
  }

  async function confirmDelete() {
    if (!toDelete) return
    try { await deleteTranslation(siteId, toDelete.mstId) } catch { /* ignore */ }
    setToDelete(null); load()
  }

  return (
    <div style={{ maxWidth: narrow ? '100%' : 900, display: 'flex', flexDirection: 'column', gap: 14 }}>
      <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
        <input style={{ ...input, flex: '1 1 220px' }} placeholder={tr('Rechercher une clé ou un texte…', 'Search a key or text…')} value={search} onChange={(e) => setSearch(e.target.value)} />
        <button style={{ ...btnPrimary, ...(narrow ? { flex: '1 1 100%' } : {}) }} onClick={openNew}>+ {tr('Nouvelle clé', 'New key')}</button>
      </div>

      <div style={{ ...card, overflow: 'auto' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse', ...(narrow ? {} : { minWidth: 640 }) }}>
          <thead style={{ background: 'var(--color-muted,rgba(0,0,0,.03))' }}>
            <tr>
              {narrow && <th style={{ ...th, width: 36 }} />}
              <th style={th}>{tr('Clé', 'Key')}</th>
              {!narrow && langs.map((l) => <th key={l.id} style={th}><span style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}><Flag locale={l.locale} size={16} />{l.name}</span></th>)}
              <th style={{ ...th, width: narrow ? 60 : 80 }} />
            </tr>
          </thead>
          <tbody>
            {filtered.length === 0 && !loading ? (
              <tr><td style={{ ...td, textAlign: 'center', color: 'var(--color-muted-foreground)', padding: '32px' }} colSpan={colCount}>{tr('Aucune traduction.', 'No translation.')}</td></tr>
            ) : filtered.map((r) => (
              <Fragment key={r.key}>
              <tr>
                {narrow && <td style={{ ...td, width: 36 }}><ExpandToggle expanded={expandedKeys.has(r.key)} onClick={() => toggleExpand(r.key)} /></td>}
                <td style={{ ...td, fontWeight: 600, fontFamily: 'monospace', whiteSpace: 'nowrap' }} title={narrow ? r.key : undefined}>{truncKey(r.key, narrow)}</td>
                {!narrow && langs.map((l) => <td key={l.id} style={{ ...td, color: 'var(--color-muted-foreground)' }}>{(r.texts[l.id]?.text || '—').slice(0, 60)}</td>)}
                <td style={{ ...td, whiteSpace: 'nowrap', textAlign: 'right' }}>
                  <button style={iconBtn} onClick={() => openEdit(r)} title={tr('Éditer', 'Edit')}><PencilIcon /></button>
                  <button style={{ ...iconBtn, marginLeft: 6, color: 'var(--color-destructive,#ef4444)' }} onClick={() => setToDelete(r)} title={tr('Supprimer', 'Delete')}><TrashIcon /></button>
                </td>
              </tr>
              {narrow && expandedKeys.has(r.key) && (
                <HiddenColsRow
                  cols={langs.map((l) => ({ id: String(l.id), visible: false }))}
                  labelFor={(id) => langs.find((l) => String(l.id) === id)?.name ?? id}
                  renderValue={(id) => r.texts[Number(id)]?.text || '—'}
                  colSpan={colCount}
                  narrow={narrow}
                />
              )}
              </Fragment>
            ))}
          </tbody>
        </table>
      </div>

      {/* Modal add/edit */}
      {draft && (
        <div style={{ position: 'fixed', inset: 0, zIndex: 60, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.5)' }}>
          <div style={{ ...card, width: '100%', maxWidth: 560, padding: 24, maxHeight: '90vh', overflow: 'auto' }}>
            <h3 style={{ margin: '0 0 12px', fontSize: 16, fontWeight: 600 }}>{draft.isNew ? tr('Nouvelle traduction', 'New translation') : tr('Éditer la traduction', 'Edit translation')}</h3>
            <div style={{ marginBottom: 12 }}><FormErrorBanner title={err ?? undefined} issues={issues} /></div>
            <label style={{ display: 'block', fontSize: 13, fontWeight: 600, marginBottom: 4 }}>{tr('Clé', 'Key')} *</label>
            <input style={{ ...input, fontFamily: 'monospace', marginBottom: 14, ...(issues.some((i) => i.label === tr('Clé', 'Key')) ? { borderColor: '#dc2626' } : {}) }} value={draft.key} disabled={!draft.isNew}
              onChange={(e) => setDraft({ ...draft, key: e.target.value })} placeholder="my_translation_key" />
            {langs.map((l) => (
              <div key={l.id} style={{ marginBottom: 12 }}>
                <label style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 13, fontWeight: 600, marginBottom: 4 }}><Flag locale={l.locale} size={16} />{l.name} <span style={{ color: 'var(--color-muted-foreground)', fontSize: 12 }}>({l.locale})</span></label>
                <textarea style={{ ...input, height: 70, padding: 10, resize: 'vertical' }} value={draft.texts[l.id]?.text ?? ''}
                  onChange={(e) => setDraft({ ...draft, texts: { ...draft.texts, [l.id]: { ...draft.texts[l.id], text: e.target.value } } })} />
              </div>
            ))}
            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
              <button style={btn} onClick={() => setDraft(null)}>{tr('Annuler', 'Cancel')}</button>
              <button style={{ ...btnPrimary, opacity: saving ? 0.6 : 1 }} disabled={saving} onClick={save}>{saving ? tr('Enregistrement…', 'Saving…') : tr('Enregistrer', 'Save')}</button>
            </div>
          </div>
        </div>
      )}

      {/* Confirm delete */}
      {toDelete && (
        <div style={{ position: 'fixed', inset: 0, zIndex: 60, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.5)' }}>
          <div style={{ ...card, width: '100%', maxWidth: 420, padding: 24 }}>
            <h3 style={{ margin: 0, fontSize: 16, fontWeight: 600 }}>{tr('Supprimer la traduction', 'Delete translation')}</h3>
            <p style={{ marginTop: 8, fontSize: 14, color: 'var(--color-muted-foreground)' }}>{tr('La clé', 'The key')} « {toDelete.key} » {tr('et tous ses textes seront supprimés.', 'and all its texts will be deleted.')}</p>
            <div style={{ marginTop: 20, display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
              <button style={btn} onClick={() => setToDelete(null)}>{tr('Annuler', 'Cancel')}</button>
              <button style={{ ...btnPrimary, background: '#b91c1c' }} onClick={confirmDelete}>{tr('Supprimer', 'Delete')}</button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default TranslationsTab
