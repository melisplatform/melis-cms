import { useCallback, useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { peT } from './page-editor-i18n'

/**
 * Contenus NATIFS des onglets de l'éditeur de page CMS.
 * - Propriétés / SEO : formulaires CONTRÔLÉS (état remonté dans CmsPage → une SEULE sauvegarde
 *   globale via le bouton du haut, pas de bouton par onglet, et pas de refetch au changement d'onglet).
 * - Langages / Historique / Analytics / Scripts / Versioning / Commentaires : vues (lecture),
 *   montées une fois puis gardées montées par la coquille → aucun rechargement au switch.
 * Données modulaires : chaque onglet modulaire tire ses données de l'API de SON module.
 */

const XHR = { 'X-Requested-With': 'XMLHttpRequest' }
export async function apiGet<T>(path: string): Promise<T> {
  const r = await fetch(`/melis/react-api/cms-page/${path}`, { headers: XHR, credentials: 'same-origin' })
  const j = await r.json(); if (!j?.success) throw new Error(j?.error || 'Erreur'); return j.data as T
}
export async function apiPost<T>(path: string, body: unknown): Promise<T> {
  const r = await fetch(`/melis/react-api/cms-page/${path}`, { method: 'POST', headers: { ...XHR, 'Content-Type': 'application/json' }, credentials: 'same-origin', body: JSON.stringify(body) })
  const j = await r.json(); if (!j?.success) throw new Error(j?.error || 'Erreur'); return j.data as T
}

/** Drapeau emoji depuis une locale Melis (ex 'fr_FR' → 🇫🇷). EN→GB (langue sans pays propre).
 *  ⚠️ NE PAS utiliser pour l'affichage : Windows ne rend pas les emojis drapeaux (affiche « GB »).
 *  → préférer le composant <Flag> (vraie image). Conservé pour compat. */
export function localeFlag(locale?: string): string {
  const cc = (locale || '').slice(-2).toUpperCase().replace(/^EN$/, 'GB')
  if (!/^[A-Z]{2}$/.test(cc)) return '🏳️'
  return String.fromCodePoint(...[...cc].map((c) => 127397 + c.charCodeAt(0)))
}

/** Drapeau = VRAIE image (`/MelisCore/assets/images/lang/<xx>.png`) — l'emoji ne s'affiche pas sous
 *  Windows. `<xx>` = 2 premières lettres du locale (en_EN→en, fr_FR→fr). Masqué si l'image manque. */
export function Flag({ locale, size = 20 }: { locale?: string; size?: number }) {
  const short = (locale || '').slice(0, 2).toLowerCase()
  if (!/^[a-z]{2}$/.test(short)) return null
  return (
    <img src={`/MelisCore/assets/images/lang/${short}.png`} alt="" width={size} height={Math.round(size * 0.7)}
      style={{ borderRadius: 3, objectFit: 'cover', boxShadow: '0 0 0 1px rgba(0,0,0,.08)', flexShrink: 0, verticalAlign: 'middle', display: 'inline-block' }}
      onError={(e) => { (e.currentTarget as HTMLImageElement).style.display = 'none' }} />
  )
}

// ── styles partagés (full width) ──
const wrap: React.CSSProperties = { padding: 20, width: '100%', boxSizing: 'border-box' }
const label: React.CSSProperties = { display: 'block', fontSize: 12, fontWeight: 600, margin: '14px 0 5px', color: 'var(--color-foreground,#111827)' }
const field: React.CSSProperties = { width: '100%', height: 36, padding: '0 10px', borderRadius: 6, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', color: 'var(--color-foreground,#111827)', fontSize: 13, boxSizing: 'border-box' }
const area: React.CSSProperties = { ...field, height: 'auto', minHeight: 70, padding: 10, resize: 'vertical' as const }

/** Sélecteur de langue custom avec DRAPEAUX IMAGES (un <option> natif ne peut pas contenir d'image). */
export type FlagOpt = { id: number; locale?: string; name: string }
export function FlagSelect({ value, onChange, options, placeholder, disabled }: {
  value: number; onChange: (id: number) => void; options: FlagOpt[]; placeholder?: string; disabled?: boolean
}) {
  const ph = placeholder ?? peT().choose
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)
  useEffect(() => { const onDoc = (e: MouseEvent) => { if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false) }; document.addEventListener('mousedown', onDoc); return () => document.removeEventListener('mousedown', onDoc) }, [])
  const current = options.find((o) => o.id === value)
  return (
    <div ref={ref} style={{ position: 'relative' }}>
      <button type="button" disabled={disabled} onClick={() => !disabled && setOpen((o) => !o)}
        style={{ ...field, display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 8, cursor: disabled ? 'not-allowed' : 'pointer', opacity: disabled ? 0.7 : 1, textAlign: 'left' }}>
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8, overflow: 'hidden' }}>
          {current ? <><Flag locale={current.locale} /><span>{current.name}</span></> : <span style={{ color: 'var(--color-muted-foreground,#6b7280)' }}>{ph}</span>}
        </span>
        {!disabled && <span style={{ color: 'var(--color-muted-foreground,#6b7280)', fontSize: 10 }}>▾</span>}
      </button>
      {open && !disabled && (
        <div style={{ position: 'absolute', top: 'calc(100% + 4px)', left: 0, right: 0, zIndex: 60, background: 'var(--color-card,#fff)', border: '1px solid var(--color-border,#e5e7eb)', borderRadius: 8, boxShadow: '0 8px 24px rgba(0,0,0,.14)', padding: 4, maxHeight: 260, overflow: 'auto' }}>
          {options.length === 0 && <div style={{ padding: 10, fontSize: 12.5, color: 'var(--color-muted-foreground,#6b7280)' }}>…</div>}
          {options.map((o) => { const sel = o.id === value; return (
            <button key={o.id} type="button" onClick={() => { onChange(o.id); setOpen(false) }}
              style={{ display: 'flex', alignItems: 'center', gap: 8, width: '100%', textAlign: 'left', padding: '8px 9px', border: 0, borderRadius: 6, cursor: 'pointer', fontSize: 13, background: sel ? 'color-mix(in srgb, var(--color-primary,#dc2626) 12%, transparent)' : 'transparent', color: 'inherit' }}
              onMouseEnter={(e) => { if (!sel) (e.currentTarget as HTMLElement).style.background = 'var(--color-accent, rgba(127,127,127,.1))' }}
              onMouseLeave={(e) => { if (!sel) (e.currentTarget as HTMLElement).style.background = 'transparent' }}>
              <Flag locale={o.locale} /><span style={{ flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{o.name}</span>
            </button>
          )})}
        </div>
      )}
    </div>
  )
}

/** Notification du shell (même toast que les outils legacy / CmsPage) : postMessage capté par
 *  <Notifications> de la coquille (la brique tourne dans la MÊME fenêtre que l'hôte). */
function notify(kind: 'ok' | 'ko', title: string, message: string) {
  window.postMessage({ __melisNotif: true, kind, title, message }, '*')
}

function Feedback({ msg }: { msg: { ok: boolean; text: string } | null }) {
  if (!msg) return null
  return <div style={{ marginTop: 12, padding: '8px 12px', borderRadius: 6, fontSize: 13, background: msg.ok ? '#dcfce7' : '#fee2e2', color: msg.ok ? '#166534' : '#991b1b' }}>{msg.text}</div>
}

export type Ref = { id: number; name: string; locale?: string }
export type PageType = { value: string; label: string }
export type Refs = { templates: Ref[]; languages: Ref[]; styles: Ref[]; types: PageType[]; menus: string[] }
export type PropsData = { idPage: number; name: string; type: string; menu: string; templateId: number; langId: number; styleId: number; taxonomy: string; creationDate: string | null }
export type SeoData = { idPage: number; url: string; urlRedirect: string; url301: string; metaTitle: string; metaDesc: string; canonical: string }

// ═══ PROPRIÉTÉS (contrôlé) ═══
export function PropertiesTab({ value, onChange, refs }: { value: PropsData; onChange: (v: PropsData) => void; refs: Refs }) {
  const tr = peT()
  const set = (k: keyof PropsData, v: string | number) => onChange({ ...value, [k]: v })
  return (
    <div style={wrap}>
      <label style={label}>{tr.name} *</label>
      <input style={field} value={value.name} onChange={(e) => set('name', e.target.value)} />
      <label style={label}>{tr.type} *</label>
      <select style={field} value={value.type} onChange={(e) => set('type', e.target.value)}>{refs.types.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}</select>
      <label style={label}>{tr.template} *</label>
      <select style={field} value={value.templateId} onChange={(e) => set('templateId', Number(e.target.value))}><option value={0}>—</option>{refs.templates.map((t) => <option key={t.id} value={t.id}>{t.name} ({t.id})</option>)}</select>
      <label style={label}>{tr.langNonEditable}</label>
      <FlagSelect value={value.langId} onChange={() => {}} options={refs.languages} disabled />
      <div style={{ height: 4 }} />
      <label style={label}>{tr.menuDisplay} *</label>
      <select style={field} value={value.menu} onChange={(e) => set('menu', e.target.value)}>{refs.menus.map((m) => <option key={m} value={m}>{m}</option>)}</select>
      <label style={label}>{tr.style}</label>
      <select style={field} value={value.styleId} onChange={(e) => set('styleId', Number(e.target.value))}><option value={0}>{tr.choose}</option>{refs.styles.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}</select>
      <label style={label}>{tr.taxonomy}</label>
      <input style={field} value={value.taxonomy} onChange={(e) => set('taxonomy', e.target.value)} placeholder={tr.taxonomyPlaceholder} />
    </div>
  )
}

// ═══ SEO (contrôlé) ═══
/** Carte de section (titre + sous-titre + contenu) — réutilisée pour regrouper les champs. */
function Section({ title, hint, children }: { title: string; hint?: string; children: React.ReactNode }) {
  return (
    <div style={{ border: '1px solid var(--color-border,#e5e7eb)', borderRadius: 10, background: 'var(--color-card,#fff)', padding: 18, marginBottom: 16 }}>
      <div style={{ fontSize: 13.5, fontWeight: 700, color: 'var(--color-foreground,#111827)' }}>{title}</div>
      {hint && <div style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)', marginTop: 2, marginBottom: 4 }}>{hint}</div>}
      {children}
    </div>
  )
}
/** Champ étiqueté (label au-dessus, texte indicatif optionnel en dessous). */
function Fieldset({ label: lbl, children, first, hint }: { label: string; children: React.ReactNode; first?: boolean; hint?: string }) {
  return (
    <div>
      <label style={{ ...label, marginTop: first ? 8 : 14 }}>{lbl}</label>
      {children}
      {hint && <div style={{ fontSize: 11.5, lineHeight: 1.4, color: 'var(--color-muted-foreground,#6b7280)', margin: '5px 2px 0' }}>{hint}</div>}
    </div>
  )
}
/** Grille 2 colonnes fixes (les champs se réduisent via minmax(0,1fr) au lieu de déborder). */
const grid2: React.CSSProperties = { display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', columnGap: 20, rowGap: 0 }
export function SeoTab({ value, onChange }: { value: SeoData; onChange: (v: SeoData) => void }) {
  const tr = peT()
  const set = (k: keyof SeoData, v: string) => onChange({ ...value, [k]: v })
  return (
    <div style={{ ...wrap, maxWidth: 1000 }}>
      <Section title={tr.seoSectionMeta} hint={tr.seoSectionMetaHint}>
        <Fieldset label={tr.metaTitle} first>
          <input style={field} value={value.metaTitle} onChange={(e) => set('metaTitle', e.target.value)} />
        </Fieldset>
        <Fieldset label={tr.metaDesc}>
          <textarea style={{ ...area, minHeight: 90 }} value={value.metaDesc} onChange={(e) => set('metaDesc', e.target.value)} />
        </Fieldset>
      </Section>
      <Section title={tr.seoSectionUrls} hint={tr.seoSectionUrlsHint}>
        <div style={grid2}>
          <Fieldset label={tr.customUrl} hint={tr.customUrlHint} first>
            <input style={field} value={value.url} onChange={(e) => set('url', e.target.value)} placeholder={tr.customUrlPlaceholder} />
          </Fieldset>
          <Fieldset label={tr.redirectUrl} hint={tr.redirectUrlHint} first>
            <input style={field} value={value.urlRedirect} onChange={(e) => set('urlRedirect', e.target.value)} />
          </Fieldset>
          <Fieldset label={tr.redirect301} hint={tr.redirect301Hint}>
            <input style={field} value={value.url301} onChange={(e) => set('url301', e.target.value)} />
          </Fieldset>
          <Fieldset label={tr.canonical} hint={tr.canonicalHint}>
            <input style={field} value={value.canonical} onChange={(e) => set('canonical', e.target.value)} />
          </Fieldset>
        </div>
      </Section>
    </div>
  )
}

// ═══ ANALYTICS (modulaire) ═══
/** Locale du back-office (attribut <html lang>, ex 'fr' / 'fr_FR' / 'en_EN') normalisée pour Intl. */
function boLocale(): string {
  return (document.documentElement.lang || 'fr').replace('_', '-') || 'fr'
}
/** Formate une date SQL 'Y-m-d H:i:s' selon la langue affichée du back-office (Intl). */
function fmtDate(s: string | null | undefined): string {
  if (!s) return '—'
  const dt = new Date(String(s).replace(' ', 'T'))
  if (isNaN(dt.getTime())) return String(s)
  try { return new Intl.DateTimeFormat(boLocale(), { dateStyle: 'medium', timeStyle: 'short' }).format(dt) }
  catch { return String(s) }
}

const ANALYTICS_PER_PAGE = 100
type AData = { visits: number; sessions: number; lastVisit: string | null; recent: { date: string }[]; recentTotal: number; page: number; perPage: number }
export function AnalyticsTab({ idPage }: { idPage: number }) {
  const tr = peT()
  const [d, setD] = useState<AData | null>(null)
  const [pageNum, setPageNum] = useState(1) // pagination SERVEUR (1-based) — 100 visites/page, jamais tout chargé
  const [loading, setLoading] = useState(false)
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null)
  useEffect(() => { setPageNum(1) }, [idPage]) // changement de page CMS → retour page 1
  useEffect(() => {
    let x = false; setLoading(true)
    apiGet<AData>(`analytics?idPage=${idPage}&page=${pageNum}&perPage=${ANALYTICS_PER_PAGE}`)
      .then((v) => { if (!x) setD(v) })
      .catch((e) => { if (!x) setMsg({ ok: false, text: e.message }) })
      .finally(() => { if (!x) setLoading(false) })
    return () => { x = true }
  }, [idPage, pageNum])
  if (!d) return <div style={wrap}>{tr.loading}</div>
  const total = d.recentTotal ?? d.visits
  const totalPages = Math.max(1, Math.ceil(total / ANALYTICS_PER_PAGE))
  const curPage = Math.min(Math.max(1, pageNum), totalPages)
  const start = (curPage - 1) * ANALYTICS_PER_PAGE
  const card: React.CSSProperties = { flex: 1, minWidth: 140, padding: 16, borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)' }
  return (
    <div style={wrap}>
      <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', marginBottom: 16 }}>
        <div style={card}><div style={{ fontSize: 24, fontWeight: 700 }}>{d.visits}</div><div style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.visits}</div></div>
        <div style={card}><div style={{ fontSize: 24, fontWeight: 700 }}>{d.sessions}</div><div style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.sessions}</div></div>
        <div style={card}><div style={{ fontSize: 15, fontWeight: 600 }}>{fmtDate(d.lastVisit)}</div><div style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.lastVisit}</div></div>
      </div>
      {total > 0 && <div style={{ fontSize: 13, fontWeight: 600, marginBottom: 6, opacity: loading ? 0.5 : 1 }}>{tr.recentVisits}</div>}
      {/* Grille multi-colonnes (auto-fill) : 100 dates par page → on remplit la largeur au lieu
          d'une seule colonne qui gaspille l'espace. Colonnes ~190px, réparties selon la largeur. */}
      <div style={{ opacity: loading ? 0.5 : 1, transition: 'opacity .15s', display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(190px, 1fr))', columnGap: 20, rowGap: 0 }}>
        {d.recent.map((r, i) => (
          <div key={start + i} style={{ display: 'flex', gap: 8, fontSize: 13, padding: '5px 4px', borderBottom: '1px solid var(--color-border,#f3f4f6)', whiteSpace: 'nowrap' }}>
            <span style={{ color: 'var(--color-muted-foreground,#9ca3af)', fontVariantNumeric: 'tabular-nums', minWidth: 34, textAlign: 'right' }}>{start + i + 1}.</span>
            <span>{fmtDate(r.date)}</span>
          </div>
        ))}
      </div>
      {total === 0 && <div style={{ fontSize: 13, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.noVisit}</div>}

      {/* Pagination SERVEUR — 100 visites par page */}
      {totalPages > 1 && (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 12, marginTop: 10, paddingTop: 12, borderTop: '1px solid var(--color-border,#f3f4f6)' }}>
          <button style={{ ...smallBtn, opacity: curPage <= 1 || loading ? 0.5 : 1, cursor: curPage <= 1 || loading ? 'not-allowed' : 'pointer' }} disabled={curPage <= 1 || loading} onClick={() => setPageNum(curPage - 1)}>{tr.prev}</button>
          <span style={{ fontSize: 12.5, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.pageWord} {curPage} / {totalPages} · {total} {tr.visitsWord}</span>
          <button style={{ ...smallBtn, opacity: curPage >= totalPages || loading ? 0.5 : 1, cursor: curPage >= totalPages || loading ? 'not-allowed' : 'pointer' }} disabled={curPage >= totalPages || loading} onClick={() => setPageNum(curPage + 1)}>{tr.next}</button>
        </div>
      )}
      <Feedback msg={msg} />
    </div>
  )
}

// ═══ SCRIPTS (modulaire) — ÉDITABLE ═══
type Scripts = { headTop: string; headBottom: string; bodyBottom: string }
export function ScriptsTab({ idPage }: { idPage: number }) {
  const tr = peT()
  const [d, setD] = useState<Scripts | null>(null)
  const [saving, setSaving] = useState(false)
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null)
  useEffect(() => { let x = false; apiGet<Scripts>(`scripts?idPage=${idPage}`).then((v) => !x && setD(v)).catch((e) => !x && setMsg({ ok: false, text: e.message })); return () => { x = true } }, [idPage])
  if (!d) return <div style={wrap}>{tr.loading}</div>
  const codeBox: React.CSSProperties = { width: '100%', minHeight: 90, padding: 10, borderRadius: 6, border: '1px solid var(--color-border,#e5e7eb)', background: '#0f172a', color: '#e2e8f0', fontFamily: 'monospace', fontSize: 12, boxSizing: 'border-box', resize: 'vertical' as const }
  const set = (k: keyof Scripts, v: string) => setD((s) => (s ? { ...s, [k]: v } : s))
  const blocks: [string, keyof Scripts, string][] = [
    [tr.headTop, 'headTop', tr.headTopHint],
    [tr.headBottom, 'headBottom', tr.headBottomHint],
    [tr.bodyBottom, 'bodyBottom', tr.bodyBottomHint],
  ]
  const save = async () => {
    setSaving(true); setMsg(null)
    try {
      await apiPost('scripts/save', { idPage, headTop: d.headTop, headBottom: d.headBottom, bodyBottom: d.bodyBottom })
      setMsg({ ok: true, text: tr.scriptsSaved })
    } catch (e) { setMsg({ ok: false, text: (e as Error).message }) } finally { setSaving(false) }
    setTimeout(() => setMsg(null), 3500)
  }
  return (
    <div style={wrap}>
      {blocks.map(([title, key, hint]) => (
        <div key={key}>
          <label style={label}>{title}</label>
          <textarea style={codeBox} value={d[key]} onChange={(e) => set(key, e.target.value)} placeholder={tr.emptyPlaceholder} spellCheck={false} />
          <div style={{ fontSize: 11, color: 'var(--color-muted-foreground,#6b7280)', margin: '3px 0 4px' }}>{hint}</div>
        </div>
      ))}
      <div style={{ marginTop: 12 }}>
        <button onClick={save} disabled={saving} style={{ appearance: 'none', display: 'inline-flex', alignItems: 'center', gap: 6, height: 34, padding: '0 16px', borderRadius: 6, fontSize: 13, fontWeight: 600, cursor: saving ? 'not-allowed' : 'pointer', border: 0, background: 'var(--color-primary,#dc2626)', color: '#fff', opacity: saving ? 0.6 : 1 }}>{saving ? tr.savingScripts : tr.saveScripts}</button>
      </div>
      <Feedback msg={msg} />
    </div>
  )
}

// POST urlencodé vers un endpoint LEGACY (versioning : voir/restaurer/renommer). Réponse JSON.
async function legacyPost(url: string, params: Record<string, string | number>): Promise<{ success?: number; datas?: Record<string, unknown>; textMessage?: string }> {
  const body = new URLSearchParams()
  Object.entries(params).forEach(([k, v]) => body.set(k, String(v)))
  const res = await fetch(url, { method: 'POST', headers: { ...XHR, 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, credentials: 'same-origin', body: body.toString() })
  return await res.json().catch(() => ({}))
}
const smallBtn: React.CSSProperties = { appearance: 'none', display: 'inline-flex', alignItems: 'center', gap: 4, height: 26, padding: '0 8px', borderRadius: 5, fontSize: 12, fontWeight: 500, cursor: 'pointer', border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', color: 'var(--color-foreground,#111827)', whiteSpace: 'nowrap' }

// ═══ VERSIONING (modulaire) — pagination SERVEUR 20/page, dates locale BO — Voir / Restaurer / Renommer ═══
const VERSIONING_PER_PAGE = 20
type WfVersion = { id: number; number: number; name: string | null; editDate: string; user: string }
type VersData = { items: WfVersion[]; page: number; perPage: number; total: number }
export function VersioningTab({ idPage }: { idPage: number }) {
  const tr = peT()
  const [d, setD] = useState<VersData | null>(null)
  const [pageNum, setPageNum] = useState(1) // pagination serveur — jamais tout chargé
  const [reloadKey, setReloadKey] = useState(0)
  const [loading, setLoading] = useState(false)
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null)
  const [busy, setBusy] = useState<number | null>(null)
  const [editing, setEditing] = useState<{ id: number; name: string } | null>(null)
  const [confirmRestore, setConfirmRestore] = useState<number | null>(null) // modal React de confirmation de restauration
  const reload = useCallback(() => setReloadKey((k) => k + 1), []) // re-fetch la PAGE courante
  useEffect(() => { setPageNum(1) }, [idPage])
  useEffect(() => {
    let x = false; setLoading(true)
    apiGet<VersData>(`versioning?idPage=${idPage}&page=${pageNum}&perPage=${VERSIONING_PER_PAGE}`)
      .then((v) => { if (!x) setD(v) })
      .catch((e) => { if (!x) setMsg({ ok: false, text: e.message }) })
      .finally(() => { if (!x) setLoading(false) })
    return () => { x = true }
  }, [idPage, pageNum, reloadKey])
  // La publication (et la sauvegarde) crée une nouvelle version → recharger la liste sur demande de la coquille.
  useEffect(() => { const on = () => reload(); window.addEventListener('melis:cms-versioning-refresh', on); return () => window.removeEventListener('melis:cms-versioning-refresh', on) }, [reload])
  const flash = (ok: boolean, text: string) => { setMsg({ ok, text }); setTimeout(() => setMsg(null), 3500) }

  // Ouvrir la prévisualisation de la version (nouvelle fenêtre) — endpoint legacy getLinkSeeVersion.
  const doView = async (id: number) => {
    setBusy(id)
    try {
      const r = await legacyPost('/melis/MelisSmallBusiness/PageVersioning/getLinkSeeVersion', { idPage, idVersion: id })
      const link = (r.datas?.linkToPageVersion as string) || ''
      if (link) window.open(link, '_blank', 'noopener'); else flash(false, tr.previewUnavailable)
    } catch (e) { flash(false, (e as Error).message) } finally { setBusy(null) }
  }
  // Restaurer la version dans l'édition (rollback → melis_cms_page_saved) + recharger l'édition/l'en-tête.
  // Confirmation via modal React (setConfirmRestore) — plus de window.confirm natif.
  const doRestore = async (id: number) => {
    setBusy(id)
    try {
      const r = await legacyPost('/melis/MelisSmallBusiness/PageVersioning/rollBackVersion', { idPage, idVersion: id })
      if (r.success === 1) {
        notify('ok', 'Versioning', tr.versionRestored)
        window.dispatchEvent(new CustomEvent('melis:cms-reload-edition')) // recharge l'iframe d'édition + en-tête
        reload()
      } else notify('ko', 'Versioning', r.textMessage && !r.textMessage.startsWith('tr_') ? r.textMessage : tr.restoreFailed)
    } catch (e) { notify('ko', 'Versioning', (e as Error).message) } finally { setBusy(null); setConfirmRestore(null) }
  }
  // Renommer la version — endpoint legacy saveVersion.
  const doRename = async () => {
    if (!editing) return
    setBusy(editing.id)
    try {
      const r = await legacyPost('/melis/MelisSmallBusiness/PageVersioning/saveVersion', { pageVersionId: editing.id, page_v_version_name: editing.name })
      if (r.success === 1) { notify('ok', 'Versioning', tr.versionRenamed); setEditing(null); reload() }
      else notify('ko', 'Versioning', tr.renameFailed)
    } catch (e) { notify('ko', 'Versioning', (e as Error).message) } finally { setBusy(null) }
  }

  if (!d) return <div style={wrap}>{tr.loading}</div>
  const total = d.total ?? d.items.length
  const totalPages = Math.max(1, Math.ceil(total / VERSIONING_PER_PAGE))
  const curPage = Math.min(Math.max(1, pageNum), totalPages)
  const th: React.CSSProperties = { textAlign: 'left', fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)', padding: '8px 10px', borderBottom: '1px solid var(--color-border,#e5e7eb)' }
  const td: React.CSSProperties = { fontSize: 13, padding: '8px 10px', borderBottom: '1px solid var(--color-border,#f3f4f6)', verticalAlign: 'middle' }
  return (
    <div style={wrap}>
      <div style={{ fontSize: 13, fontWeight: 600, marginBottom: 8 }}>{tr.pageVersions}</div>
      {total === 0 ? <div style={{ fontSize: 13, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.noVersion}</div> : (
        <table style={{ width: '100%', borderCollapse: 'collapse', opacity: loading ? 0.5 : 1, transition: 'opacity .15s' }}>
          <thead><tr><th style={th}>{tr.colNumber}</th><th style={th}>{tr.colName}</th><th style={th}>{tr.colModifiedOn}</th><th style={th}>{tr.colBy}</th><th style={{ ...th, textAlign: 'right' }}>{tr.colActions}</th></tr></thead>
          <tbody>{d.items.map((r) => (
            <tr key={r.id}>
              <td style={td}>{r.number}</td>
              <td style={td}>{editing?.id === r.id
                ? <input autoFocus value={editing.name} onChange={(e) => setEditing({ id: r.id, name: e.target.value })} onKeyDown={(e) => { if (e.key === 'Enter') doRename(); if (e.key === 'Escape') setEditing(null) }} style={{ ...field, height: 28, width: 200 }} placeholder={tr.versionNamePlaceholder} />
                : (r.name || <span style={{ color: 'var(--color-muted-foreground,#6b7280)' }}>—</span>)}</td>
              <td style={td}>{fmtDate(r.editDate)}</td>
              <td style={td}>{r.user}</td>
              <td style={{ ...td, textAlign: 'right', whiteSpace: 'nowrap' }}>
                {editing?.id === r.id ? (<>
                  <button style={{ ...smallBtn, border: 0, background: 'var(--color-primary,#dc2626)', color: '#fff' }} disabled={busy === r.id} onClick={doRename}>{tr.save}</button>
                  <button style={{ ...smallBtn, marginLeft: 6 }} onClick={() => setEditing(null)}>{tr.cancel}</button>
                </>) : (<>
                  <button style={smallBtn} disabled={busy === r.id} onClick={() => doView(r.id)} title={tr.viewTip}>{tr.view}</button>
                  <button style={{ ...smallBtn, marginLeft: 6 }} disabled={busy === r.id} onClick={() => setConfirmRestore(r.id)} title={tr.restoreTip}>{tr.restore}</button>
                  <button style={{ ...smallBtn, marginLeft: 6 }} disabled={busy === r.id} onClick={() => setEditing({ id: r.id, name: r.name || '' })} title={tr.renameTip}>{tr.rename}</button>
                </>)}
              </td>
            </tr>
          ))}</tbody>
        </table>
      )}
      {/* Pagination serveur — 20 versions par page */}
      {totalPages > 1 && (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 12, marginTop: 10, paddingTop: 12, borderTop: '1px solid var(--color-border,#f3f4f6)' }}>
          <button style={{ ...smallBtn, opacity: curPage <= 1 || loading ? 0.5 : 1, cursor: curPage <= 1 || loading ? 'not-allowed' : 'pointer' }} disabled={curPage <= 1 || loading} onClick={() => setPageNum(curPage - 1)}>{tr.prev}</button>
          <span style={{ fontSize: 12.5, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.pageWord} {curPage} / {totalPages} · {total} {tr.versionsWord}</span>
          <button style={{ ...smallBtn, opacity: curPage >= totalPages || loading ? 0.5 : 1, cursor: curPage >= totalPages || loading ? 'not-allowed' : 'pointer' }} disabled={curPage >= totalPages || loading} onClick={() => setPageNum(curPage + 1)}>{tr.next}</button>
        </div>
      )}
      <Feedback msg={msg} />

      {/* Modal React de confirmation de RESTAURATION (remplace le window.confirm natif) */}
      {confirmRestore != null && (() => {
        const v = d.items.find((r) => r.id === confirmRestore)
        const busyThis = busy === confirmRestore
        return (
          <div onClick={() => !busyThis && setConfirmRestore(null)} style={{ position: 'fixed', inset: 0, background: 'rgba(15,23,42,.5)', zIndex: 1000, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }}>
            <div onClick={(e) => e.stopPropagation()} style={{ width: 480, maxWidth: '100%', background: 'var(--color-card,#fff)', borderRadius: 10, overflow: 'hidden', boxShadow: '0 20px 60px rgba(0,0,0,.35)' }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 18px', background: '#f59e0b', color: '#fff' }}>
                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8, fontSize: 15, fontWeight: 600 }}>{tr.restoreVersionTitle}</span>
                <button onClick={() => !busyThis && setConfirmRestore(null)} style={{ appearance: 'none', border: 0, background: 'transparent', color: '#fff', fontSize: 20, lineHeight: 1, cursor: 'pointer' }}>×</button>
              </div>
              <div style={{ padding: 18, fontSize: 13, color: 'var(--color-foreground,#111827)', lineHeight: 1.5 }}>
                {tr.restoreConfirm1a} <strong>{tr.colNumber}{v?.number}{v?.name ? ` — ${v.name}` : ''}</strong> {tr.restoreConfirm1b}<br />
                {tr.restoreConfirm2pre} <strong>{tr.restoreConfirm2a}</strong> {tr.restoreConfirm2b}
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', gap: 10, padding: '0 18px 18px' }}>
                <button onClick={() => setConfirmRestore(null)} disabled={busyThis} style={{ ...smallBtn, height: 34, padding: '0 14px' }}>{tr.cancel}</button>
                <button onClick={() => confirmRestore != null && doRestore(confirmRestore)} disabled={busyThis} style={{ ...smallBtn, height: 34, padding: '0 14px', border: 0, background: '#f59e0b', color: '#fff' }}>{busyThis ? tr.restoring : tr.restoreBtn}</button>
              </div>
            </div>
          </div>
        )
      })()}
    </div>
  )
}

// ═══ COMMENTAIRES → FRISE D'ÉVÉNEMENTS (modulaire) ═══
type TLItem = { kind: 'comment' | 'workflow'; date: string; user: string; text: string; title?: string; action?: string | null; toUser?: string | null; toRole?: string | null }
/** Métadonnées de badge workflow — les libellés dépendent de la langue (peT). */
function wfMeta(): Record<string, { label: string; color: string; bg: string }> {
  const tr = peT()
  return {
    VALIDATION: { label: tr.wfValidation, color: '#d97706', bg: 'rgba(245,158,11,.15)' },
    VALIDATED: { label: tr.wfValidated, color: '#059669', bg: 'rgba(16,185,129,.15)' },
    REFUSED: { label: tr.wfRefused, color: '#dc2626', bg: 'rgba(239,68,68,.15)' },
  }
}
const COMMENTS_PER_PAGE = 20
export function CommentsTab({ idPage }: { idPage: number }) {
  const tr = peT()
  const WF_META = wfMeta()
  const [items, setItems] = useState<TLItem[] | null>(null)
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null)
  const [text, setText] = useState('')
  const [sending, setSending] = useState(false)
  const [pageNum, setPageNum] = useState(1) // pagination client (1-based), 20 éléments/page
  const reload = useCallback(() => { apiGet<{ items: TLItem[] }>(`timeline?idPage=${idPage}`).then((v) => setItems(v.items)).catch((e) => setMsg({ ok: false, text: e.message })) }, [idPage])
  useEffect(() => { let x = false; apiGet<{ items: TLItem[] }>(`timeline?idPage=${idPage}`).then((v) => !x && setItems(v.items)).catch((e) => !x && setMsg({ ok: false, text: e.message })); return () => { x = true } }, [idPage])
  // Une action WORKFLOW (demande/validation/refus/commentaire, modale mutualisée small-business)
  // alimente aussi la timeline de commentaires → recharger l'onglet sans rouvrir la page.
  useEffect(() => {
    const on = (e: Event) => { const d = (e as CustomEvent<{ wfType?: string; wfId?: number | string }>).detail; if (!d || d.wfType == null || (d.wfType === 'PAGE' && Number(d.wfId) === idPage)) reload() }
    window.addEventListener('melis:workflow-action-done', on)
    return () => window.removeEventListener('melis:workflow-action-done', on)
  }, [reload, idPage])
  useEffect(() => { setPageNum(1) }, [idPage]) // nouvelle page → retour à la 1ʳᵉ page
  const add = async () => {
    if (!text.trim()) return
    setSending(true); setMsg(null)
    // timeline triée newest-first → le nouveau commentaire est en tête (page 1)
    try { await apiPost('comments/save', { idPage, text: text.trim() }); setText(''); setPageNum(1); reload() }
    catch (e) { setMsg({ ok: false, text: (e as Error).message }); setTimeout(() => setMsg(null), 3500) } finally { setSending(false) }
  }
  if (!items) return <div style={wrap}>{tr.loading}</div>
  const totalPages = Math.max(1, Math.ceil(items.length / COMMENTS_PER_PAGE))
  const curPage = Math.min(Math.max(1, pageNum), totalPages)
  const start = (curPage - 1) * COMMENTS_PER_PAGE
  const paged = items.slice(start, start + COMMENTS_PER_PAGE)
  return (
    <div style={wrap}>
      <div style={{ fontSize: 13, fontWeight: 600, marginBottom: 10 }}>{tr.pageActivity}</div>

      {/* Ajouter un commentaire */}
      <div style={{ display: 'flex', gap: 8, alignItems: 'flex-end', marginBottom: 18 }}>
        <textarea value={text} onChange={(e) => setText(e.target.value)} placeholder={tr.addCommentPlaceholder} rows={2}
          style={{ flex: 1, resize: 'vertical', minHeight: 40, borderRadius: 6, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', color: 'var(--color-foreground,#111827)', padding: 10, fontSize: 13, boxSizing: 'border-box' }} />
        <button onClick={add} disabled={sending || !text.trim()} style={{ appearance: 'none', height: 36, padding: '0 16px', borderRadius: 6, fontSize: 13, fontWeight: 600, cursor: (sending || !text.trim()) ? 'not-allowed' : 'pointer', border: 0, background: 'var(--color-primary,#dc2626)', color: '#fff', opacity: (sending || !text.trim()) ? 0.6 : 1, whiteSpace: 'nowrap' }}>{sending ? tr.sending : tr.comment}</button>
      </div>

      {items.length === 0 ? <div style={{ fontSize: 13, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.noActivity}</div> : (
        <div>{paged.map((it, i) => {
          const wf = it.kind === 'workflow' ? (WF_META[it.action || ''] || { label: it.action || 'Workflow', color: '#6b7280', bg: 'rgba(127,127,127,.12)' }) : null
          const dot = wf ? wf.color : 'var(--color-primary,#dc2626)'
          const last = i === paged.length - 1
          return (
            <div key={start + i} style={{ display: 'flex', gap: 12 }}>
              {/* Rail : pastille + trait vertical */}
              <div style={{ position: 'relative', width: 12, flexShrink: 0 }}>
                <div style={{ position: 'absolute', top: 6, left: 'calc(50% - 5px)', width: 10, height: 10, borderRadius: '50%', background: dot, zIndex: 1, boxShadow: '0 0 0 3px var(--color-background,#fff)' }} />
                {!last && <div style={{ position: 'absolute', top: 6, bottom: -6, left: 'calc(50% - 1px)', width: 2, background: 'var(--color-border,#e5e7eb)' }} />}
              </div>
              {/* Contenu */}
              <div style={{ flex: 1, paddingBottom: 18, minWidth: 0 }}>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8, alignItems: 'center' }}>
                  {wf
                    ? <span style={{ fontSize: 11, fontWeight: 600, borderRadius: 999, padding: '2px 8px', background: wf.bg, color: wf.color }}>{wf.label}</span>
                    : <span style={{ fontSize: 11, fontWeight: 600, borderRadius: 999, padding: '2px 8px', background: 'color-mix(in srgb, var(--color-primary,#dc2626) 12%, transparent)', color: 'var(--color-primary,#dc2626)' }}>{tr.commentBadge}</span>}
                  <span style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>{it.user}{wf && (it.toUser || it.toRole) ? ` → ${[it.toUser, it.toRole].filter(Boolean).join(' / ')}` : ''} · {it.date}</span>
                </div>
                {it.title && <div style={{ fontSize: 13, fontWeight: 600, marginTop: 4 }} dangerouslySetInnerHTML={{ __html: it.title }} />}
                {it.text && <div style={{ fontSize: 13, marginTop: 4, color: 'var(--color-foreground,#111827)', whiteSpace: 'pre-wrap' }} dangerouslySetInnerHTML={{ __html: it.text }} />}
              </div>
            </div>
          )
        })}</div>
      )}

      {/* Pagination — 20 éléments par page */}
      {totalPages > 1 && (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 12, marginTop: 8, paddingTop: 12, borderTop: '1px solid var(--color-border,#f3f4f6)' }}>
          <button style={{ ...smallBtn, opacity: curPage <= 1 ? 0.5 : 1, cursor: curPage <= 1 ? 'not-allowed' : 'pointer' }} disabled={curPage <= 1} onClick={() => setPageNum(curPage - 1)}>{tr.prev}</button>
          <span style={{ fontSize: 12.5, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.pageWord} {curPage} / {totalPages} · {items.length} {tr.itemsWord}</span>
          <button style={{ ...smallBtn, opacity: curPage >= totalPages ? 0.5 : 1, cursor: curPage >= totalPages ? 'not-allowed' : 'pointer' }} disabled={curPage >= totalPages} onClick={() => setPageNum(curPage + 1)}>{tr.next}</button>
        </div>
      )}
      <Feedback msg={msg} />
    </div>
  )
}

// ═══ HISTORIQUE (modulaire) — pagination SERVEUR 25/page, dates locale BO, badges couleur + filtre ═══
const HISTORIC_PER_PAGE = 25
type Hist = { id: number; date: string; action: string; user: string }
type HistData = { items: Hist[]; page: number; perPage: number; total: number; actionTypes: string[]; action: string }
/** Couleur d'un badge d'action (par mots-clefs). NB : « unpublish » testé AVANT « publish ». */
function histActionStyle(a: string): { color: string; bg: string } {
  const s = (a || '').toLowerCase()
  if (/unpublish|dépubli|depubli|offline|hors\s*ligne/.test(s)) return { color: '#b45309', bg: 'rgba(245,158,11,.16)' }
  if (/publish|publi|online|en\s*ligne/.test(s)) return { color: '#059669', bg: 'rgba(16,185,129,.16)' }
  if (/duplicat|copy|copie/.test(s)) return { color: '#7c3aed', bg: 'rgba(124,58,237,.16)' }
  if (/delet|suppr|remove/.test(s)) return { color: '#dc2626', bg: 'rgba(239,68,68,.16)' }
  if (/save|saved|enregist|brouillon|draft/.test(s)) return { color: '#2563eb', bg: 'rgba(37,99,235,.16)' }
  if (/creat|nouvel|new\b|add|ajout/.test(s)) return { color: '#0891b2', bg: 'rgba(6,182,212,.16)' }
  if (/rollback|restaur|revert/.test(s)) return { color: '#d97706', bg: 'rgba(217,119,6,.16)' }
  return { color: 'var(--color-muted-foreground,#6b7280)', bg: 'rgba(127,127,127,.14)' }
}
function ActionBadge({ action }: { action: string }) {
  const c = histActionStyle(action)
  return <span style={{ fontSize: 12, fontWeight: 600, borderRadius: 999, padding: '2px 10px', background: c.bg, color: c.color, whiteSpace: 'nowrap', display: 'inline-block' }}>{action}</span>
}
export function HistoricTab({ idPage }: { idPage: number }) {
  const tr = peT()
  const [d, setD] = useState<HistData | null>(null)
  const [pageNum, setPageNum] = useState(1) // pagination serveur — jamais tout chargé
  const [filter, setFilter] = useState('')  // filtre SERVEUR par type d'action ('' = toutes)
  const [reloadKey, setReloadKey] = useState(0) // re-fetch après save/publish/offline/duplication
  const [loading, setLoading] = useState(false)
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null)
  useEffect(() => { setPageNum(1); setFilter('') }, [idPage])
  // Une action d'édition (save/publish/dépublier/dupliquer) crée une entrée d'historique → recharger.
  useEffect(() => { const on = () => setReloadKey((k) => k + 1); window.addEventListener('melis:cms-historic-refresh', on); return () => window.removeEventListener('melis:cms-historic-refresh', on) }, [])
  useEffect(() => {
    let x = false; setLoading(true)
    apiGet<HistData>(`historic?idPage=${idPage}&page=${pageNum}&perPage=${HISTORIC_PER_PAGE}${filter ? `&action=${encodeURIComponent(filter)}` : ''}`)
      .then((v) => { if (!x) setD(v) })
      .catch((e) => { if (!x) setMsg({ ok: false, text: e.message }) })
      .finally(() => { if (!x) setLoading(false) })
    return () => { x = true }
  }, [idPage, pageNum, filter, reloadKey])
  if (!d) return <div style={wrap}>{tr.loading}</div>
  const total = d.total ?? d.items.length
  const totalPages = Math.max(1, Math.ceil(total / HISTORIC_PER_PAGE))
  const curPage = Math.min(Math.max(1, pageNum), totalPages)
  const th: React.CSSProperties = { textAlign: 'left', fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)', padding: '8px 10px', borderBottom: '1px solid var(--color-border,#e5e7eb)' }
  const td: React.CSSProperties = { fontSize: 13, padding: '8px 10px', borderBottom: '1px solid var(--color-border,#f3f4f6)' }
  return (
    <div style={wrap}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12, marginBottom: 8, flexWrap: 'wrap' }}>
        <div style={{ fontSize: 13, fontWeight: 600 }}>{tr.pageHistory}</div>
        {/* Filtre par type d'action (serveur → filtre tout l'historique, pas juste la page). */}
        {(d.actionTypes?.length ?? 0) > 0 && (
          <label style={{ display: 'inline-flex', alignItems: 'center', gap: 6, fontSize: 12.5, color: 'var(--color-muted-foreground,#6b7280)' }}>
            {tr.filter}
            <select value={filter} onChange={(e) => { setFilter(e.target.value); setPageNum(1) }} style={{ ...field, height: 32, width: 'auto', minWidth: 170 }}>
              <option value="">{tr.allActions}</option>
              {d.actionTypes.map((t) => <option key={t} value={t}>{t}</option>)}
            </select>
          </label>
        )}
      </div>
      {total === 0 ? <div style={{ fontSize: 13, color: 'var(--color-muted-foreground,#6b7280)' }}>{filter ? tr.noEntryForAction : tr.noHistory}</div> : (
        <table style={{ width: '100%', borderCollapse: 'collapse', opacity: loading ? 0.5 : 1, transition: 'opacity .15s' }}>
          <thead><tr><th style={th}>{tr.colDate}</th><th style={th}>{tr.colAction}</th><th style={th}>{tr.colUser}</th></tr></thead>
          <tbody>{d.items.map((r) => <tr key={r.id}><td style={td}>{fmtDate(r.date)}</td><td style={td}><ActionBadge action={r.action} /></td><td style={td}>{r.user}</td></tr>)}</tbody>
        </table>
      )}
      {/* Pagination serveur — 25 entrées par page */}
      {totalPages > 1 && (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 12, marginTop: 10, paddingTop: 12, borderTop: '1px solid var(--color-border,#f3f4f6)' }}>
          <button style={{ ...smallBtn, opacity: curPage <= 1 || loading ? 0.5 : 1, cursor: curPage <= 1 || loading ? 'not-allowed' : 'pointer' }} disabled={curPage <= 1 || loading} onClick={() => setPageNum(curPage - 1)}>{tr.prev}</button>
          <span style={{ fontSize: 12.5, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.pageWord} {curPage} / {totalPages} · {total} {tr.entriesWord}</span>
          <button style={{ ...smallBtn, opacity: curPage >= totalPages || loading ? 0.5 : 1, cursor: curPage >= totalPages || loading ? 'not-allowed' : 'pointer' }} disabled={curPage >= totalPages || loading} onClick={() => setPageNum(curPage + 1)}>{tr.next}</button>
        </div>
      )}
      <Feedback msg={msg} />
    </div>
  )
}

// ═══ LANGAGES (modulaire — avec DRAPEAUX) ═══
type Version = { pageId: number; langId: number; langName: string; locale: string; pageName: string }
type Langs = { idPage: number; initial: number; versions: Version[]; creatable: Ref[] }
export function LanguagesTab({ idPage }: { idPage: number }) {
  const tr = peT()
  const navigate = useNavigate()
  const [d, setD] = useState<Langs | null>(null)
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null)
  const [reloadKey, setReloadKey] = useState(0)
  const [creating, setCreating] = useState<string | null>(null) // locale en cours de création
  useEffect(() => { let x = false; apiGet<Langs>(`languages?idPage=${idPage}`).then((v) => !x && setD(v)).catch((e) => !x && setMsg({ ok: false, text: e.message })); return () => { x = true } }, [idPage, reloadKey])
  // Crée une version de langue via l'endpoint LEGACY (même flux que le tool jQuery pagelang.js) :
  // POST pageLangPageId + pageLangLocale → nouvelle page (statut hors ligne). On ouvre ensuite la page
  // créée dans un onglet du shell (comme l'arbre / la création de page React).
  const createLang = useCallback(async (locale: string) => {
    setCreating(locale); setMsg(null)
    try {
      const r = await legacyPost('/melis/MelisCms/PageLanguages/createNewPageLangVersion', { pageLangPageId: idPage, pageLangLocale: locale })
      const info = (r as { pageInfo?: { pageid?: number | string; name?: string } }).pageInfo
      if (r.success && info?.pageid) {
        notify('ok', tr.langVersions, tr.langCreated)
        setReloadKey((k) => k + 1) // rafraîchit la liste des versions
        const path = `/melis-cms/page/${info.pageid}`
        ;(window as unknown as { __melisOpenTab?: (t: { id: string; label: string; path: string }) => void }).__melisOpenTab?.({ id: path, label: (info.name || `Page ${info.pageid}`).toString().trim(), path })
        navigate(path)
      } else {
        const t = r.textMessage && !r.textMessage.startsWith('tr_') ? r.textMessage : tr.langCreateFailed
        notify('ko', tr.langVersions, t); setMsg({ ok: false, text: t })
      }
    } catch (e) { notify('ko', tr.langVersions, (e as Error).message); setMsg({ ok: false, text: (e as Error).message }) }
    finally { setCreating(null) }
  }, [idPage, navigate, tr])
  // Ouvre une AUTRE version de langue dans un onglet du shell (même mécanisme que l'arbre).
  const openPage = useCallback((pageId: number, name: string) => {
    const path = `/melis-cms/page/${pageId}`
    ;(window as unknown as { __melisOpenTab?: (t: { id: string; label: string; path: string }) => void }).__melisOpenTab?.({ id: path, label: (name || `Page ${pageId}`).toString().trim(), path })
    navigate(path)
  }, [navigate])
  if (!d) return <div style={wrap}>{tr.loading}</div>
  const th: React.CSSProperties = { textAlign: 'left', fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)', padding: '8px 10px', borderBottom: '1px solid var(--color-border,#e5e7eb)' }
  const td: React.CSSProperties = { fontSize: 13, padding: '8px 10px', borderBottom: '1px solid var(--color-border,#f3f4f6)' }
  const linkStyle: React.CSSProperties = { color: 'var(--color-primary,#dc2626)', textDecoration: 'none', cursor: 'pointer', fontWeight: 500 }
  return (
    <div style={wrap}>
      <div style={{ fontSize: 13, fontWeight: 600, marginBottom: 8 }}>{tr.langVersions}</div>
      <table style={{ width: '100%', borderCollapse: 'collapse' }}>
        <thead><tr><th style={th}>{tr.colLanguage}</th><th style={th}>{tr.colLocale}</th><th style={th}>{tr.colPageName}</th><th style={th}>{tr.colId}</th></tr></thead>
        <tbody>{d.versions.map((v) => {
          const isCurrent = v.pageId === idPage
          const open = () => openPage(v.pageId, v.pageName)
          const rowStyle: React.CSSProperties = isCurrent ? { background: 'color-mix(in srgb, var(--color-primary,#dc2626) 7%, transparent)' } : { cursor: 'pointer' }
          return (
            <tr key={v.pageId} style={rowStyle} title={isCurrent ? undefined : tr.openPageTitle}
              onClick={isCurrent ? undefined : open}
              onMouseEnter={isCurrent ? undefined : (e) => { (e.currentTarget as HTMLElement).style.background = 'var(--color-accent, rgba(127,127,127,.08))' }}
              onMouseLeave={isCurrent ? undefined : (e) => { (e.currentTarget as HTMLElement).style.background = 'transparent' }}>
              <td style={td}><span style={{ display: 'inline-flex', alignItems: 'center', gap: 8 }}><Flag locale={v.locale} />{v.langName || v.langId}</span></td>
              <td style={td}>{v.locale}</td>
              <td style={td}>
                {isCurrent
                  ? <span>{v.pageName || '—'} <span style={{ fontSize: 11, color: 'var(--color-muted-foreground,#6b7280)', fontWeight: 500 }}>· {tr.currentPage}</span></span>
                  : <a style={linkStyle} onClick={(e) => { e.stopPropagation(); open() }}>{v.pageName || `Page ${v.pageId}`}</a>}
              </td>
              <td style={td}>{v.pageId}</td>
            </tr>
          )
        })}</tbody>
      </table>
      <div style={{ marginTop: 20 }}>
        <div style={{ fontSize: 13, fontWeight: 600, marginBottom: 2 }}>{tr.creatableLangs}</div>
        {d.creatable.length > 0 ? (
          <>
            <div style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)', marginBottom: 10 }}>{tr.createLangHint}</div>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
              {d.creatable.map((c) => (
                <button key={c.id} type="button" style={{ ...smallBtn, height: 30, opacity: creating ? 0.6 : 1 }}
                  disabled={!!creating} onClick={() => createLang(c.locale)}>
                  <Flag locale={c.locale} size={16} />
                  {creating === c.locale ? tr.langCreating : `${tr.createLangBtn} · ${c.name}`}
                </button>
              ))}
            </div>
          </>
        ) : (
          <div style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>{tr.noCreatableLangs}</div>
        )}
      </div>
      <Feedback msg={msg} />
    </div>
  )
}
