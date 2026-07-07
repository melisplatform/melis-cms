import { useEffect, useMemo, useState } from 'react'
import { fetchTranslations, saveTranslation, deleteTranslation, type TransKey } from '../sites-api'

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

interface Lang { id: number; locale: string; name: string }
interface Draft { key: string; isNew: boolean; texts: Record<number, { mstId: number; msttId: number; text: string }> }

export function TranslationsTab({ siteId, langs }: { siteId: number; langs: Lang[] }) {
  const [rows, setRows] = useState<TransKey[]>([])
  const [loading, setLoading] = useState(false)
  const [search, setSearch] = useState('')
  const [draft, setDraft] = useState<Draft | null>(null)
  const [toDelete, setToDelete] = useState<TransKey | null>(null)
  const [saving, setSaving] = useState(false)
  const [err, setErr] = useState<string | null>(null)

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
    setDraft({ key: '', isNew: true, texts }); setErr(null)
  }
  function openEdit(r: TransKey) {
    const texts: Draft['texts'] = {}
    for (const l of langs) {
      const t = r.texts[l.id]
      texts[l.id] = { mstId: t?.mstId ?? r.mstId, msttId: t?.msttId ?? 0, text: t?.text ?? '' }
    }
    setDraft({ key: r.key, isNew: false, texts }); setErr(null)
  }

  async function save() {
    if (!draft) return
    if (!draft.key.trim()) { setErr(tr('La clé est requise.', 'Key is required.')); return }
    setSaving(true); setErr(null)
    try {
      const res = await saveTranslation(siteId, draft.key.trim(), langs.map((l) => ({
        langId: l.id, mstId: draft.texts[l.id]?.mstId ?? 0, msttId: draft.texts[l.id]?.msttId ?? 0, text: draft.texts[l.id]?.text ?? '',
      })))
      if (res.success === true || (res.success as unknown) === 1) { setDraft(null); load() }
      else setErr(tr('Échec de l’enregistrement.', 'Save failed.'))
    } catch (e) { setErr(String((e as Error)?.message ?? e)) } finally { setSaving(false) }
  }

  async function confirmDelete() {
    if (!toDelete) return
    try { await deleteTranslation(siteId, toDelete.mstId) } catch { /* ignore */ }
    setToDelete(null); load()
  }

  return (
    <div style={{ maxWidth: 900, display: 'flex', flexDirection: 'column', gap: 14 }}>
      <div style={{ display: 'flex', gap: 8 }}>
        <input style={{ ...input, flex: 1 }} placeholder={tr('Rechercher une clé ou un texte…', 'Search a key or text…')} value={search} onChange={(e) => setSearch(e.target.value)} />
        <button style={btnPrimary} onClick={openNew}>+ {tr('Nouvelle clé', 'New key')}</button>
      </div>

      <div style={{ ...card, overflow: 'auto' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: 640 }}>
          <thead style={{ background: 'var(--color-muted,rgba(0,0,0,.03))' }}>
            <tr>
              <th style={th}>{tr('Clé', 'Key')}</th>
              {langs.map((l) => <th key={l.id} style={th}>{l.name}</th>)}
              <th style={{ ...th, width: 80 }} />
            </tr>
          </thead>
          <tbody>
            {filtered.length === 0 && !loading ? (
              <tr><td style={{ ...td, textAlign: 'center', color: 'var(--color-muted-foreground)', padding: '32px' }} colSpan={langs.length + 2}>{tr('Aucune traduction.', 'No translation.')}</td></tr>
            ) : filtered.map((r) => (
              <tr key={r.key}>
                <td style={{ ...td, fontWeight: 600, fontFamily: 'monospace' }}>{r.key}</td>
                {langs.map((l) => <td key={l.id} style={{ ...td, color: 'var(--color-muted-foreground)' }}>{(r.texts[l.id]?.text || '—').slice(0, 60)}</td>)}
                <td style={{ ...td, whiteSpace: 'nowrap', textAlign: 'right' }}>
                  <button style={{ ...btn, height: 28 }} onClick={() => openEdit(r)} title={tr('Éditer', 'Edit')}>✎</button>
                  <button style={{ ...btn, height: 28, marginLeft: 6, color: '#b91c1c' }} onClick={() => setToDelete(r)} title={tr('Supprimer', 'Delete')}>🗑</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Modal add/edit */}
      {draft && (
        <div style={{ position: 'fixed', inset: 0, zIndex: 60, display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'rgba(0,0,0,.5)' }}>
          <div style={{ ...card, width: '100%', maxWidth: 560, padding: 24, maxHeight: '90vh', overflow: 'auto' }}>
            <h3 style={{ margin: '0 0 12px', fontSize: 16, fontWeight: 600 }}>{draft.isNew ? tr('Nouvelle traduction', 'New translation') : tr('Éditer la traduction', 'Edit translation')}</h3>
            <label style={{ display: 'block', fontSize: 13, fontWeight: 600, marginBottom: 4 }}>{tr('Clé', 'Key')} *</label>
            <input style={{ ...input, fontFamily: 'monospace', marginBottom: 14 }} value={draft.key} disabled={!draft.isNew}
              onChange={(e) => setDraft({ ...draft, key: e.target.value })} placeholder="my_translation_key" />
            {langs.map((l) => (
              <div key={l.id} style={{ marginBottom: 12 }}>
                <label style={{ display: 'block', fontSize: 13, fontWeight: 600, marginBottom: 4 }}>{l.name} <span style={{ color: 'var(--color-muted-foreground)', fontSize: 12 }}>({l.locale})</span></label>
                <textarea style={{ ...input, height: 70, padding: 10, resize: 'vertical' }} value={draft.texts[l.id]?.text ?? ''}
                  onChange={(e) => setDraft({ ...draft, texts: { ...draft.texts, [l.id]: { ...draft.texts[l.id], text: e.target.value } } })} />
              </div>
            ))}
            {err && <div style={{ color: '#b91c1c', fontSize: 13, marginBottom: 10 }}>{err}</div>}
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
