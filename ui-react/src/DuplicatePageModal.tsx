import { useEffect, useRef, useState, type CSSProperties } from 'react'
import { createPortal } from 'react-dom'
import { PagePicker } from './PagePicker'
import { duplicateTreePage } from './cms-tree-api'
import { fetchLanguages, type LangItem } from './cms-language-api'

/* ──────────────────────────────────────────────────────────────────────────
 * Modale « Dupliquer l'arborescence » — remplace en natif React l'outil legacy
 * (meliscms_tools_tree_modal_form_handler) ouvert jusqu'ici en iframe.
 * Reproduit le formulaire d'origine (TreeSitesController::duplicateTreePage) :
 *   ID page d'origine · Langue · Relation avec une page initiale · ID page de
 *   destination · Racine. Même endpoint POST (cf. cms-tree-api duplicateTreePage),
 *   aucun changement backend. Styles inline + variables CSS du thème (règle des briques).
 * ────────────────────────────────────────────────────────────────────────── */

type Lang = 'fr' | 'en'
function currentLang(): Lang {
  return (document.documentElement.lang || 'en').toLowerCase().startsWith('fr') ? 'fr' : 'en'
}
const DICT: Record<Lang, Record<string, string>> = {
  fr: {
    title: "Dupliquer l'arborescence",
    subtitle: "Copier une page et ses sous-pages vers une destination",
    source: "Page d'origine",
    source_tt: "Identifiant de la page de l'arborescence à dupliquer",
    language: 'Langue',
    language_tt: 'Langue de la copie',
    choose: 'Choisir une langue…',
    relation: 'Relation avec une page initiale',
    relation_tt: 'Crée la copie comme version de langue liée à la page d’origine',
    destination: 'Page de destination',
    destination_tt:
      'Page de destination de l\'arborescence dupliquée, ou cochez « Racine » pour la placer à la racine',
    root: 'Placer à la racine',
    root_tt: 'Place la copie à la racine du site (ignore la destination).',
    choose_page: 'Choisir une page…',
    cancel: 'Annuler',
    save: 'Dupliquer',
    saving: 'Duplication…',
    required: 'Champ obligatoire',
    fail: "Échec de la duplication de l'arborescence",
    affected: 'Pages concernées :',
  },
  en: {
    title: 'Duplicate tree',
    subtitle: 'Copy a page and its sub-pages to a destination',
    source: 'Source page',
    source_tt: 'Identifier of the page tree to be duplicated',
    language: 'Language',
    language_tt: 'Language of the copy',
    choose: 'Choose a language…',
    relation: 'Relationship with an initial page',
    relation_tt: 'Create the copy as a language version linked to the source page',
    destination: 'Destination page',
    destination_tt:
      'Destination page of the duplicated tree, or check "Root" to put it at the root',
    root: 'Place at root',
    root_tt: 'Places the copy at the site root (ignores the destination).',
    choose_page: 'Choose a page…',
    cancel: 'Cancel',
    save: 'Duplicate',
    saving: 'Duplicating…',
    required: 'This field is required',
    fail: 'Failed to duplicate page tree',
    affected: 'Affected pages:',
  },
}
function tr(key: string): string {
  return DICT[currentLang()][key] ?? key
}

/**
 * Pages en conflit à afficher de façon compacte. Le backend renvoie `errors` comme un
 * TABLEAU `[{ errorMessage, label:'Page X' }]` uniquement pour le conflit de version de
 * langue (option « Relation ») ; les autres échecs (source/destination absente, validation
 * de formulaire) renvoient un OBJET — déjà couvert par `textMessage`, donc on l'ignore ici.
 * On dédoublonne et on retire le préfixe « Page » (le libellé de la liste le porte déjà).
 */
function affectedPages(errors: unknown): string[] {
  if (!Array.isArray(errors)) return []
  const labels = errors
    .map((e) => (e && typeof e === 'object' ? String((e as { label?: string }).label ?? '') : ''))
    .filter(Boolean)
    .map((l) => l.replace(/^Page\s+/i, ''))
  return Array.from(new Set(labels))
}

/* ── styles ── */
const card: CSSProperties = { border: '1px solid var(--color-border)', background: 'var(--color-card,var(--color-background,#fff))', borderRadius: 14, boxShadow: '0 24px 70px rgba(0,0,0,.4)', overflow: 'hidden' }
const labelCss: CSSProperties = { fontSize: 12.5, fontWeight: 600, color: 'var(--color-foreground)' }
const controlBox: CSSProperties = { display: 'inline-flex', alignItems: 'center', justifyContent: 'space-between', gap: 8, height: 38, width: '100%', padding: '0 10px', cursor: 'pointer', fontSize: 13.5, borderRadius: 9, border: '1px solid var(--color-border)', background: 'var(--color-background,#fff)', color: 'var(--color-foreground)' }
const btnGhost: CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 36, padding: '0 14px', borderRadius: 9, border: '1px solid var(--color-border)', background: 'var(--color-card,transparent)', color: 'var(--color-foreground)', fontSize: 13.5, fontWeight: 500, cursor: 'pointer' }
const btnPrimary: CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 7, height: 36, padding: '0 16px', borderRadius: 9, border: 0, background: 'var(--color-primary)', color: 'var(--color-primary-foreground,#fff)', fontSize: 13.5, fontWeight: 600, cursor: 'pointer' }
const popover: CSSProperties = { position: 'absolute', zIndex: 60, top: 42, left: 0, right: 0, maxHeight: 260, overflow: 'auto', padding: 5, borderRadius: 10, border: '1px solid var(--color-border)', background: 'var(--color-background,#fff)', boxShadow: '0 12px 34px rgba(0,0,0,.16)' }

function InfoIcon({ tip }: { tip: string }) {
  return (
    <span
      title={tip}
      style={{
        display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
        width: 14, height: 14, borderRadius: 999, flexShrink: 0, cursor: 'help',
        border: '1px solid var(--color-muted-foreground)', color: 'var(--color-muted-foreground)',
        fontSize: 9.5, fontStyle: 'italic', fontWeight: 700, fontFamily: 'Georgia, serif', lineHeight: 1,
      }}
    >
      i
    </span>
  )
}

/** Label row: text + inline info icon, optional required asterisk. */
function FieldLabel({ label, tip, required }: { label: string; tip: string; required?: boolean }) {
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginBottom: 6 }}>
      <span style={labelCss}>{label}</span>
      {required && <span style={{ color: 'var(--color-destructive,#c0392b)', fontWeight: 700, marginLeft: -3 }}>*</span>}
      <InfoIcon tip={tip} />
    </div>
  )
}

const RequiredHint = ({ show }: { show: boolean }) =>
  show ? <p style={{ margin: '5px 0 0', fontSize: 11.5, color: 'var(--color-destructive,#c0392b)' }}>{tr('required')}</p> : null

/* ── flags (real PNGs shipped by MelisCore; emoji flags don't render on Windows) ── */
function localeShort(locale: string): string { return (locale || '').slice(0, 2).toLowerCase() }
function Flag({ locale }: { locale: string }) {
  return (
    <img
      src={`/MelisCore/assets/images/lang/${localeShort(locale)}.png`}
      alt=""
      width={20}
      height={14}
      style={{ borderRadius: 3, objectFit: 'cover', boxShadow: '0 0 0 1px rgba(0,0,0,.08)', flexShrink: 0 }}
      onError={(e) => { (e.currentTarget as HTMLImageElement).style.visibility = 'hidden' }}
    />
  )
}

const CheckIcon = ({ size = 14 }: { size?: number }) => (
  <svg viewBox="0 0 24 24" width={size} height={size} fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round">
    <path d="M20 6 9 17l-5-5" />
  </svg>
)

/** Custom language dropdown with flags. */
function FlagSelect({ langs, value, onChange, placeholder }: {
  langs: LangItem[]
  value: number | ''
  onChange: (id: number) => void
  placeholder: string
}) {
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)
  useEffect(() => {
    const onDoc = (e: MouseEvent) => { if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false) }
    document.addEventListener('mousedown', onDoc)
    return () => document.removeEventListener('mousedown', onDoc)
  }, [])
  const current = langs.find((l) => l.id === value)
  return (
    <div ref={ref} style={{ position: 'relative' }}>
      <button type="button" onClick={() => setOpen((o) => !o)} style={controlBox}>
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 9, overflow: 'hidden' }}>
          {current ? <><Flag locale={current.locale} /><span>{current.name}</span></>
            : <span style={{ color: 'var(--color-muted-foreground)' }}>{placeholder}</span>}
        </span>
        <span style={{ color: 'var(--color-muted-foreground)', fontSize: 11 }}>▾</span>
      </button>
      {open && (
        <div style={popover}>
          {langs.length === 0 && <div style={{ padding: 10, fontSize: 12.5, color: 'var(--color-muted-foreground)' }}>…</div>}
          {langs.map((l) => {
            const sel = l.id === value
            return (
              <button
                key={l.id}
                type="button"
                onClick={() => { onChange(l.id); setOpen(false) }}
                style={{ display: 'flex', alignItems: 'center', gap: 9, width: '100%', textAlign: 'left', padding: '8px 9px', border: 0, borderRadius: 7, cursor: 'pointer', fontSize: 13.5, background: sel ? 'color-mix(in srgb, var(--color-primary) 12%, transparent)' : 'transparent', color: 'inherit' }}
                onMouseEnter={(e) => { if (!sel) (e.currentTarget as HTMLElement).style.background = 'var(--color-accent, rgba(127,127,127,.1))' }}
                onMouseLeave={(e) => { if (!sel) (e.currentTarget as HTMLElement).style.background = 'transparent' }}
              >
                <Flag locale={l.locale} />
                <span style={{ flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{l.name}</span>
                {sel && <span style={{ color: 'var(--color-primary)' }}><CheckIcon /></span>}
              </button>
            )
          })}
        </div>
      )}
    </div>
  )
}

/** Compact clickable checkbox row (checkbox glyph + label + info). */
function CheckRow({ checked, onChange, label, tip }: { checked: boolean; onChange: (v: boolean) => void; label: string; tip: string }) {
  return (
    <button
      type="button"
      onClick={() => onChange(!checked)}
      style={{
        display: 'flex', alignItems: 'center', gap: 10, width: '100%', textAlign: 'left',
        padding: '9px 11px', borderRadius: 9, cursor: 'pointer', color: 'var(--color-foreground)',
        border: '1px solid ' + (checked ? 'color-mix(in srgb, var(--color-primary) 45%, var(--color-border))' : 'var(--color-border)'),
        background: checked ? 'color-mix(in srgb, var(--color-primary) 8%, transparent)' : 'var(--color-background,#fff)',
      }}
    >
      <span
        style={{
          width: 20, height: 20, flexShrink: 0, borderRadius: 6, display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
          border: '1px solid ' + (checked ? 'var(--color-primary)' : 'var(--color-border)'),
          background: checked ? 'var(--color-primary)' : 'var(--color-background,#fff)',
          color: 'var(--color-primary-foreground,#fff)',
        }}
      >
        {checked && <CheckIcon size={13} />}
      </span>
      <span style={{ flex: 1, fontSize: 13, fontWeight: 500 }}>{label}</span>
      <InfoIcon tip={tip} />
    </button>
  )
}

const DupeGlyph = () => (
  <svg viewBox="0 0 24 24" width={17} height={17} fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <rect x="9" y="9" width="11" height="11" rx="2" /><path d="M5 15V5a2 2 0 0 1 2-2h10" />
  </svg>
)

export default function DuplicatePageModal({ sourcePageId, sourceTitle, onClose, onDone }: {
  sourcePageId: number
  sourceTitle?: string
  onClose: () => void
  onDone: () => void
}) {
  const [langs, setLangs] = useState<LangItem[]>([])
  const [srcId, setSrcId] = useState<number>(sourcePageId)
  const [srcTitle, setSrcTitle] = useState<string>(sourceTitle || '')
  const [langId, setLangId] = useState<number | ''>('')
  const [pageRelation, setPageRelation] = useState(false)
  const [destId, setDestId] = useState<number>(0)
  const [destTitle, setDestTitle] = useState<string>('')
  const [useRoot, setUseRoot] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [affected, setAffected] = useState<string[]>([])
  const [touched, setTouched] = useState(false)

  useEffect(() => {
    let cancelled = false
    fetchLanguages().then((r) => { if (!cancelled) setLangs(r.items) }).catch(() => {})
    return () => { cancelled = true }
  }, [])

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose() }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [onClose])

  const srcMissing = !srcId
  const langMissing = langId === ''
  const destMissing = !useRoot && !destId
  const invalid = srcMissing || langMissing || destMissing

  async function submit() {
    setTouched(true)
    if (invalid || submitting) return
    setSubmitting(true)
    setError(null)
    setAffected([])
    const res = await duplicateTreePage({
      sourcePageId: srcId,
      langId: Number(langId),
      pageRelation,
      destinationPageId: useRoot ? null : destId,
      useRoot,
    })
    setSubmitting(false)
    if (res.success) {
      // Reuse the tree's existing refresh contract (PageTree listens for this message and reloads).
      window.postMessage({ __melisToolResult: true, url: '/melis/MelisCms/TreeSites/duplicateTreePage', data: { success: 1 } }, '*')
      onDone()
      onClose()
    } else {
      setError(res.message || tr('fail'))
      setAffected(affectedPages(res.errors))
    }
  }

  return createPortal(
    <div
      onClick={(e) => { if (e.target === e.currentTarget) onClose() }}
      style={{ position: 'fixed', inset: 0, zIndex: 99999, padding: 24, background: 'rgba(15,18,25,.55)', backdropFilter: 'blur(2px)', display: 'flex', alignItems: 'flex-start', justifyContent: 'center', overflow: 'auto' }}
    >
      <div style={{ ...card, width: 'min(500px, calc(100vw - 48px))', marginTop: '8vh', color: 'var(--color-foreground)' }}>
        {/* Header */}
        <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '16px 18px', borderBottom: '1px solid var(--color-border)' }}>
          <span style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 34, height: 34, borderRadius: 9, flexShrink: 0, background: 'color-mix(in srgb, var(--color-primary) 14%, transparent)', color: 'var(--color-primary)' }}>
            <DupeGlyph />
          </span>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ fontWeight: 600, fontSize: 15 }}>{tr('title')}</div>
            <div style={{ fontSize: 12, color: 'var(--color-muted-foreground)', marginTop: 1 }}>{tr('subtitle')}</div>
          </div>
          <button onClick={onClose} title={tr('cancel')} style={{ border: 'none', background: 'transparent', color: 'var(--color-muted-foreground)', cursor: 'pointer', fontSize: 18, lineHeight: 1, padding: 4 }}>✕</button>
        </div>

        {/* Body */}
        <div style={{ padding: 18, display: 'flex', flexDirection: 'column', gap: 15 }}>
          {error && (
            <div style={{ display: 'flex', flexDirection: 'column', gap: 6, padding: '8px 12px', borderRadius: 9, fontSize: 12.5, background: 'color-mix(in srgb, var(--color-destructive,#c0392b) 12%, transparent)', color: 'var(--color-destructive,#c0392b)', border: '1px solid color-mix(in srgb, var(--color-destructive,#c0392b) 40%, transparent)' }}>
              <span>{error}</span>
              {affected.length > 0 && (
                <span style={{ fontSize: 11.5, opacity: 0.85 }}>
                  <strong style={{ fontWeight: 600 }}>{tr('affected')}</strong> {affected.join(', ')}
                </span>
              )}
            </div>
          )}

          <div>
            <FieldLabel label={tr('source')} tip={tr('source_tt')} required />
            <PagePicker value={srcId} title={srcTitle} placeholder={tr('choose_page')} onChange={(id, t) => { setSrcId(id); setSrcTitle(t) }} />
            <RequiredHint show={touched && srcMissing} />
          </div>

          <div>
            <FieldLabel label={tr('language')} tip={tr('language_tt')} required />
            <FlagSelect langs={langs} value={langId} placeholder={tr('choose')} onChange={(v) => setLangId(v)} />
            <RequiredHint show={touched && langMissing} />
          </div>

          <div>
            <FieldLabel label={tr('destination')} tip={tr('destination_tt')} required />
            <div style={{ opacity: useRoot ? 0.45 : 1, pointerEvents: useRoot ? 'none' : 'auto' }}>
              <PagePicker value={destId} title={destTitle} placeholder={tr('choose_page')} onChange={(id, t) => { setDestId(id); setDestTitle(t) }} />
            </div>
            <RequiredHint show={touched && destMissing} />
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: 8, marginTop: 2 }}>
            <CheckRow checked={useRoot} onChange={(v) => { setUseRoot(v); if (v) { setDestId(0); setDestTitle('') } }} label={tr('root')} tip={tr('root_tt')} />
            <CheckRow checked={pageRelation} onChange={setPageRelation} label={tr('relation')} tip={tr('relation_tt')} />
          </div>
        </div>

        {/* Footer */}
        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 9, padding: '14px 18px', borderTop: '1px solid var(--color-border)', background: 'color-mix(in srgb, var(--color-foreground) 3%, transparent)' }}>
          <button style={btnGhost} onClick={onClose} disabled={submitting}>{tr('cancel')}</button>
          <button style={{ ...btnPrimary, opacity: submitting ? 0.65 : 1 }} onClick={submit} disabled={submitting}>
            <DupeGlyph />{submitting ? tr('saving') : tr('save')}
          </button>
        </div>
      </div>
    </div>,
    document.body,
  )
}
