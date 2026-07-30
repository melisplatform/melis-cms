import { useCallback, useEffect, useState } from 'react'
import { ViewToggle, type ViewMode } from './ViewToggle'
import { apiGet, FlagSelect, type Refs } from './PageTabs'
import { peT } from './page-editor-i18n'
import { legacyErrorFields, legacyText } from './legacy-errors'
import { useIsNarrow } from './shared/useIsNarrow'

/**
 * Écran « Nouvelle page » — création d'une page CMS, en NATIF React avec toggle New/Old.
 * Accédé via le bouton « Nouvelle page » de l'éditeur ET via le clic droit « Nouvelle page » de l'arbre
 * (les deux ouvrent la route /melis-cms/page/new~<father>). CmsPage monte ce composant pour les cids de
 * création à la place de l'iframe legacy.
 *
 * - New (React) : formulaire natif (nom, type, template, langue, menu, style, taxonomie) → POST direct
 *   sur l'endpoint legacy `savePage?idPage=0&fatherPageId=<father>` (mêmes noms de champs que le form
 *   `pageproperties`). La chaîne serveur crée l'arbre + les propriétés (isNew) → renvoie le nouvel idPage.
 * - Old (legacy) : l'outil de création historique en iframe (`meliscms_page_creation`). Son save passe
 *   par le même endpoint ; le résultat est capté par le message-listener de CmsPage (inchangé).
 *
 * `onCreated(newId, name)` : ouvre la page créée en édition (fourni par CmsPage).
 */

const XHR = { 'X-Requested-With': 'XMLHttpRequest' }
type NotifField = { label: string; messages: string[] }
function notify(kind: 'ok' | 'ko', title: string, message: string, fields?: NotifField[]) {
  window.postMessage({ __melisNotif: true, kind, title, message, fields }, '*')
}

// ── styles (alignés sur PageTabs) ──
const label: React.CSSProperties = { display: 'block', fontSize: 12, fontWeight: 600, margin: '14px 0 5px', color: 'var(--color-foreground,#111827)' }
const field: React.CSSProperties = { width: '100%', height: 36, padding: '0 10px', borderRadius: 6, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', color: 'var(--color-foreground,#111827)', fontSize: 13, boxSizing: 'border-box' }

type Form = { name: string; type: string; templateId: number; langId: number; menu: string; styleId: number; taxonomy: string }

export default function NewPageView({ father, visible, onCreated }: { father: string; visible: boolean; onCreated: (newId: number | string, name: string) => void }) {
  const tr = peT() // dictionnaire i18n du BO
  const narrow = useIsNarrow()
  const [mode, setMode] = useState<ViewMode>('react')
  const [refs, setRefs] = useState<Refs | null>(null)
  const [form, setForm] = useState<Form>({ name: '', type: father ? 'PAGE' : 'SITE', templateId: 0, langId: 0, menu: 'LINK', styleId: 0, taxonomy: '' })
  const [saving, setSaving] = useState(false)
  const [err, setErr] = useState<string | null>(null)
  const set = <K extends keyof Form>(k: K, v: Form[K]) => setForm((f) => ({ ...f, [k]: v }))

  // Références du SITE du père (templates/styles) + langues. Père absent (page racine) → refs global (idPage=0).
  useEffect(() => {
    let x = false
    apiGet<Refs>(`refs?idPage=${encodeURIComponent(father || '0')}`)
      .then((r) => { if (x) return; setRefs(r); setForm((f) => ({ ...f, langId: f.langId || r.languages[0]?.id || 0 })) })
      .catch((e) => { if (!x) setErr((e as Error).message) })
    return () => { x = true }
  }, [father])

  const create = useCallback(async () => {
    if (!form.name.trim()) { setErr(tr.errNameRequired); return }
    if (!form.templateId) { setErr(tr.errTemplateRequired); return }
    if (!form.langId) { setErr(tr.errLangRequired); return }
    setSaving(true); setErr(null)
    try {
      const b = new URLSearchParams()
      b.set('page_id', '0')
      b.set('page_name', form.name)
      b.set('page_type', form.type)
      b.set('plang_lang_id', String(form.langId))
      b.set('page_menu', form.menu)
      b.set('page_tpl_id', String(form.templateId))
      b.set('style_id', form.styleId ? String(form.styleId) : '')
      b.set('page_taxonomy', form.taxonomy ?? '')
      b.set('page_search_type', 'tr_meliscms_page_tab_properties_search_type_option1')
      const res = await fetch(`/melis/MelisCms/Page/savePage?idPage=0&fatherPageId=${encodeURIComponent(father)}`, {
        method: 'POST', credentials: 'same-origin',
        headers: { ...XHR, 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: b.toString(),
      })
      const data = await res.json().catch(() => ({})) as { success?: number; textTitle?: string; textMessage?: string; errors?: unknown; datas?: { idPage?: number | string; item_name?: string } }
      if (data.success === 1 && data.datas?.idPage) {
        notify('ok', (data.textTitle || tr.newPage).trim(), tr.pageCreated)
        onCreated(data.datas.idPage, (data.datas.item_name || form.name).trim())
      } else {
        // Détail des erreurs de champ (cf. legacy-errors.ts : le legacy a plusieurs formes d'`errors`).
        const fields = legacyErrorFields(data.errors, tr.errorField)
        const generic = legacyText(data.textMessage, tr.createFailed)
        notify('ko', (data.textTitle || tr.newPage).trim(), fields.length ? tr.fixErrorsBelow : generic, fields)
        setErr(fields.length ? fields.map((f) => `${f.label} : ${f.messages.join(', ')}`).join(' · ') : generic)
      }
    } catch (e) { setErr((e as Error).message) } finally { setSaving(false) }
  }, [form, father, onCreated])

  return (
    <div style={{ position: 'absolute', inset: 0, display: visible ? 'flex' : 'none', flexDirection: 'column', background: 'var(--color-background,#fff)' }}>
      {/* En-tête : titre + toggle New/Old (indépendant de l'éditeur) */}
      <div style={{ flex: '0 0 auto', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 10, padding: '8px 16px', borderBottom: '1px solid var(--color-border,#e5e7eb)' }}>
        <span style={{
          fontWeight: 600, fontSize: 15, color: 'var(--color-foreground,#111827)',
          minWidth: narrow ? 0 : undefined, overflow: narrow ? 'hidden' : undefined,
          textOverflow: narrow ? 'ellipsis' : undefined, whiteSpace: narrow ? 'nowrap' : undefined,
          flex: narrow ? '1 1 auto' : undefined,
        }}>
          {tr.newPage}{father ? <span style={{ fontWeight: 400, fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}> — {tr.underPage} {father}</span> : ''}
        </span>
        <span style={{ flexShrink: 0 }}>
          <ViewToggle mode={mode} onChange={setMode} compact={narrow} />
        </span>
      </div>

      <div style={{ position: 'relative', flex: '1 1 auto', minHeight: 0, overflow: 'auto' }}>
        {mode === 'iframe' ? (
          <iframe title={tr.newPageLegacy} src={`/melis/react-tool-page?key=meliscms_page_creation&idPage=0&idFatherPage=${encodeURIComponent(father)}`}
            style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', border: 0 }} />
        ) : !refs ? (
          <div style={{ padding: 20, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.loading}</div>
        ) : (
          <div style={{ padding: 20, maxWidth: 640 }}>
            <label style={label}>{tr.name} *</label>
            <input style={field} value={form.name} onChange={(e) => set('name', e.target.value)} autoFocus placeholder={tr.namePlaceholder} />

            <label style={label}>{tr.type} *</label>
            <select style={field} value={form.type} onChange={(e) => set('type', e.target.value)}>{refs.types.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}</select>

            <label style={label}>{tr.template} *</label>
            <select style={field} value={form.templateId} onChange={(e) => set('templateId', Number(e.target.value))}><option value={0}>{tr.choose}</option>{refs.templates.map((t) => <option key={t.id} value={t.id}>{t.name} ({t.id})</option>)}</select>

            <label style={label}>{tr.language} *</label>
            <FlagSelect value={form.langId} onChange={(id) => set('langId', id)} options={refs.languages} placeholder={tr.choose} />

            <label style={label}>{tr.menuDisplay} *</label>
            <select style={field} value={form.menu} onChange={(e) => set('menu', e.target.value)}>{refs.menus.map((m) => <option key={m} value={m}>{m}</option>)}</select>

            <label style={label}>{tr.style}</label>
            <select style={field} value={form.styleId} onChange={(e) => set('styleId', Number(e.target.value))}><option value={0}>{tr.choose}</option>{refs.styles.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}</select>

            <label style={label}>{tr.taxonomy}</label>
            <input style={field} value={form.taxonomy} onChange={(e) => set('taxonomy', e.target.value)} placeholder={tr.taxonomyPlaceholder} />

            {err && <div style={{ marginTop: 14, padding: '8px 12px', borderRadius: 6, fontSize: 13, background: '#fee2e2', color: '#991b1b' }}>{err}</div>}

            <div style={{ marginTop: 18 }}>
              <button className="melis-pgbtn" onClick={create} disabled={saving}
                style={{ appearance: 'none', display: 'inline-flex', alignItems: 'center', gap: 6, height: 34, padding: '0 16px', borderRadius: 6, fontSize: 13, fontWeight: 600, cursor: saving ? 'not-allowed' : 'pointer', border: 0, background: 'var(--color-primary,#dc2626)', color: '#fff', opacity: saving ? 0.6 : 1 }}>
                {saving ? tr.creating : tr.createPage}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
