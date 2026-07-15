import { useEffect, useRef, useState } from 'react'

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
export function FlagSelect({ value, onChange, options, placeholder = 'Choisissez', disabled }: {
  value: number; onChange: (id: number) => void; options: FlagOpt[]; placeholder?: string; disabled?: boolean
}) {
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)
  useEffect(() => { const onDoc = (e: MouseEvent) => { if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false) }; document.addEventListener('mousedown', onDoc); return () => document.removeEventListener('mousedown', onDoc) }, [])
  const current = options.find((o) => o.id === value)
  return (
    <div ref={ref} style={{ position: 'relative' }}>
      <button type="button" disabled={disabled} onClick={() => !disabled && setOpen((o) => !o)}
        style={{ ...field, display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 8, cursor: disabled ? 'not-allowed' : 'pointer', opacity: disabled ? 0.7 : 1, textAlign: 'left' }}>
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8, overflow: 'hidden' }}>
          {current ? <><Flag locale={current.locale} /><span>{current.name}</span></> : <span style={{ color: 'var(--color-muted-foreground,#6b7280)' }}>{placeholder}</span>}
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

function Feedback({ msg }: { msg: { ok: boolean; text: string } | null }) {
  if (!msg) return null
  return <div style={{ marginTop: 12, padding: '8px 12px', borderRadius: 6, fontSize: 13, background: msg.ok ? '#dcfce7' : '#fee2e2', color: msg.ok ? '#166534' : '#991b1b' }}>{msg.text}</div>
}

export type Ref = { id: number; name: string; locale?: string }
export type Refs = { templates: Ref[]; languages: Ref[]; styles: Ref[]; types: string[]; menus: string[] }
export type PropsData = { idPage: number; name: string; type: string; menu: string; templateId: number; langId: number; styleId: number; taxonomy: string; creationDate: string | null }
export type SeoData = { idPage: number; url: string; urlRedirect: string; url301: string; metaTitle: string; metaDesc: string; canonical: string }

// ═══ PROPRIÉTÉS (contrôlé) ═══
export function PropertiesTab({ value, onChange, refs }: { value: PropsData; onChange: (v: PropsData) => void; refs: Refs }) {
  const set = (k: keyof PropsData, v: string | number) => onChange({ ...value, [k]: v })
  return (
    <div style={wrap}>
      <label style={label}>Nom *</label>
      <input style={field} value={value.name} onChange={(e) => set('name', e.target.value)} />
      <label style={label}>Type *</label>
      <select style={field} value={value.type} onChange={(e) => set('type', e.target.value)}>{refs.types.map((t) => <option key={t} value={t}>{t}</option>)}</select>
      <label style={label}>Template *</label>
      <select style={field} value={value.templateId} onChange={(e) => set('templateId', Number(e.target.value))}><option value={0}>—</option>{refs.templates.map((t) => <option key={t.id} value={t.id}>{t.name} ({t.id})</option>)}</select>
      <label style={label}>Langue (non modifiable après création)</label>
      <FlagSelect value={value.langId} onChange={() => {}} options={refs.languages} disabled />
      <div style={{ height: 4 }} />
      <label style={label}>Affichage menu *</label>
      <select style={field} value={value.menu} onChange={(e) => set('menu', e.target.value)}>{refs.menus.map((m) => <option key={m} value={m}>{m}</option>)}</select>
      <label style={label}>Style</label>
      <select style={field} value={value.styleId} onChange={(e) => set('styleId', Number(e.target.value))}><option value={0}>Choisissez</option>{refs.styles.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}</select>
      <label style={label}>Taxonomie</label>
      <input style={field} value={value.taxonomy} onChange={(e) => set('taxonomy', e.target.value)} placeholder="Séparez les mots-clefs avec une virgule" />
    </div>
  )
}

// ═══ SEO (contrôlé) ═══
export function SeoTab({ value, onChange }: { value: SeoData; onChange: (v: SeoData) => void }) {
  const set = (k: keyof SeoData, v: string) => onChange({ ...value, [k]: v })
  return (
    <div style={wrap}>
      <label style={label}>Titre (meta title)</label>
      <input style={field} value={value.metaTitle} onChange={(e) => set('metaTitle', e.target.value)} />
      <label style={label}>Description (meta description)</label>
      <textarea style={area} value={value.metaDesc} onChange={(e) => set('metaDesc', e.target.value)} />
      <label style={label}>URL personnalisée</label>
      <input style={field} value={value.url} onChange={(e) => set('url', e.target.value)} placeholder="ex: nos-services" />
      <label style={label}>URL de redirection</label>
      <input style={field} value={value.urlRedirect} onChange={(e) => set('urlRedirect', e.target.value)} />
      <label style={label}>Redirection 301</label>
      <input style={field} value={value.url301} onChange={(e) => set('url301', e.target.value)} />
      <label style={label}>Canonical</label>
      <input style={field} value={value.canonical} onChange={(e) => set('canonical', e.target.value)} />
    </div>
  )
}

// ═══ ANALYTICS (modulaire) ═══
export function AnalyticsTab({ idPage }: { idPage: number }) {
  const [d, setD] = useState<{ visits: number; sessions: number; lastVisit: string | null; recent: { date: string }[] } | null>(null)
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null)
  useEffect(() => { let x = false; apiGet<{ visits: number; sessions: number; lastVisit: string | null; recent: { date: string }[] }>(`analytics?idPage=${idPage}`).then((v) => !x && setD(v)).catch((e) => !x && setMsg({ ok: false, text: e.message })); return () => { x = true } }, [idPage])
  if (!d) return <div style={wrap}>Chargement…</div>
  const card: React.CSSProperties = { flex: 1, minWidth: 140, padding: 16, borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)' }
  return (
    <div style={wrap}>
      <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', marginBottom: 16 }}>
        <div style={card}><div style={{ fontSize: 24, fontWeight: 700 }}>{d.visits}</div><div style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>Visites</div></div>
        <div style={card}><div style={{ fontSize: 24, fontWeight: 700 }}>{d.sessions}</div><div style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>Sessions</div></div>
        <div style={card}><div style={{ fontSize: 15, fontWeight: 600 }}>{d.lastVisit ?? '—'}</div><div style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>Dernière visite</div></div>
      </div>
      {d.recent.length > 0 && <div style={{ fontSize: 13, fontWeight: 600, marginBottom: 6 }}>Visites récentes</div>}
      {d.recent.map((r, i) => <div key={i} style={{ fontSize: 13, padding: '4px 0', borderBottom: '1px solid var(--color-border,#f3f4f6)' }}>{r.date}</div>)}
      <Feedback msg={msg} />
    </div>
  )
}

// ═══ SCRIPTS (modulaire) ═══
export function ScriptsTab({ idPage }: { idPage: number }) {
  const [d, setD] = useState<{ headTop: string; headBottom: string; bodyBottom: string } | null>(null)
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null)
  useEffect(() => { let x = false; apiGet<{ headTop: string; headBottom: string; bodyBottom: string }>(`scripts?idPage=${idPage}`).then((v) => !x && setD(v)).catch((e) => !x && setMsg({ ok: false, text: e.message })); return () => { x = true } }, [idPage])
  if (!d) return <div style={wrap}>Chargement…</div>
  const codeBox: React.CSSProperties = { width: '100%', minHeight: 90, padding: 10, borderRadius: 6, border: '1px solid var(--color-border,#e5e7eb)', background: '#0f172a', color: '#e2e8f0', fontFamily: 'monospace', fontSize: 12, boxSizing: 'border-box', resize: 'vertical' as const }
  const blocks: [string, string][] = [['Head (haut)', d.headTop], ['Head (bas)', d.headBottom], ['Body (bas)', d.bodyBottom]]
  return (
    <div style={wrap}>
      {blocks.map(([title, val]) => (<div key={title}><label style={label}>{title}</label><textarea style={codeBox} value={val} readOnly placeholder="(vide)" /></div>))}
      <div style={{ marginTop: 10, fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>Lecture seule (édition via la vue Old pour l'instant).</div>
      <Feedback msg={msg} />
    </div>
  )
}

// ═══ VERSIONING (modulaire) ═══
export function VersioningTab({ idPage }: { idPage: number }) {
  const [rows, setRows] = useState<{ id: number; number: number; name: string | null; editDate: string; user: string }[] | null>(null)
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null)
  useEffect(() => { let x = false; apiGet<{ items: { id: number; number: number; name: string | null; editDate: string; user: string }[] }>(`versioning?idPage=${idPage}`).then((v) => !x && setRows(v.items)).catch((e) => !x && setMsg({ ok: false, text: e.message })); return () => { x = true } }, [idPage])
  if (!rows) return <div style={wrap}>Chargement…</div>
  const th: React.CSSProperties = { textAlign: 'left', fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)', padding: '8px 10px', borderBottom: '1px solid var(--color-border,#e5e7eb)' }
  const td: React.CSSProperties = { fontSize: 13, padding: '8px 10px', borderBottom: '1px solid var(--color-border,#f3f4f6)' }
  return (
    <div style={wrap}>
      <div style={{ fontSize: 13, fontWeight: 600, marginBottom: 8 }}>Versions de la page</div>
      {rows.length === 0 ? <div style={{ fontSize: 13, color: 'var(--color-muted-foreground,#6b7280)' }}>Aucune version.</div> : (
        <table style={{ width: '100%', borderCollapse: 'collapse' }}>
          <thead><tr><th style={th}>N°</th><th style={th}>Nom</th><th style={th}>Modifiée le</th><th style={th}>Par</th></tr></thead>
          <tbody>{rows.map((r) => <tr key={r.id}><td style={td}>{r.number}</td><td style={td}>{r.name || '—'}</td><td style={td}>{r.editDate}</td><td style={td}>{r.user}</td></tr>)}</tbody>
        </table>
      )}
      <Feedback msg={msg} />
    </div>
  )
}

// ═══ COMMENTAIRES (modulaire) ═══
export function CommentsTab({ idPage }: { idPage: number }) {
  const [rows, setRows] = useState<{ id: number; date: string; title: string; text: string; user: string }[] | null>(null)
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null)
  useEffect(() => { let x = false; apiGet<{ items: { id: number; date: string; title: string; text: string; user: string }[] }>(`comments?idPage=${idPage}`).then((v) => !x && setRows(v.items)).catch((e) => !x && setMsg({ ok: false, text: e.message })); return () => { x = true } }, [idPage])
  if (!rows) return <div style={wrap}>Chargement…</div>
  return (
    <div style={wrap}>
      <div style={{ fontSize: 13, fontWeight: 600, marginBottom: 8 }}>Commentaires</div>
      {rows.length === 0 ? <div style={{ fontSize: 13, color: 'var(--color-muted-foreground,#6b7280)' }}>Aucun commentaire.</div> : rows.map((r) => (
        <div key={r.id} style={{ padding: '10px 0', borderBottom: '1px solid var(--color-border,#f3f4f6)' }}>
          <div style={{ display: 'flex', gap: 8, alignItems: 'baseline' }}>
            <span style={{ fontSize: 13, fontWeight: 600 }} dangerouslySetInnerHTML={{ __html: r.title || '(sans titre)' }} />
            <span style={{ fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)' }}>— {r.user} · {r.date}</span>
          </div>
          <div style={{ fontSize: 13, marginTop: 4 }} dangerouslySetInnerHTML={{ __html: r.text || '' }} />
        </div>
      ))}
      <Feedback msg={msg} />
    </div>
  )
}

// ═══ HISTORIQUE (modulaire) ═══
type Hist = { id: number; date: string; action: string; user: string }
export function HistoricTab({ idPage }: { idPage: number }) {
  const [rows, setRows] = useState<Hist[] | null>(null)
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null)
  useEffect(() => { let x = false; apiGet<{ items: Hist[] }>(`historic?idPage=${idPage}`).then((d) => !x && setRows(d.items)).catch((e) => !x && setMsg({ ok: false, text: e.message })); return () => { x = true } }, [idPage])
  if (!rows) return <div style={wrap}>Chargement…</div>
  const th: React.CSSProperties = { textAlign: 'left', fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)', padding: '8px 10px', borderBottom: '1px solid var(--color-border,#e5e7eb)' }
  const td: React.CSSProperties = { fontSize: 13, padding: '8px 10px', borderBottom: '1px solid var(--color-border,#f3f4f6)' }
  return (
    <div style={wrap}>
      <div style={{ fontSize: 13, fontWeight: 600, marginBottom: 8 }}>Historique de la page</div>
      {rows.length === 0 ? <div style={{ fontSize: 13, color: 'var(--color-muted-foreground,#6b7280)' }}>Aucun historique.</div> : (
        <table style={{ width: '100%', borderCollapse: 'collapse' }}>
          <thead><tr><th style={th}>Date</th><th style={th}>Action</th><th style={th}>Utilisateur</th></tr></thead>
          <tbody>{rows.map((r) => <tr key={r.id}><td style={td}>{r.date}</td><td style={td}>{r.action}</td><td style={td}>{r.user}</td></tr>)}</tbody>
        </table>
      )}
      <Feedback msg={msg} />
    </div>
  )
}

// ═══ LANGAGES (modulaire — avec DRAPEAUX) ═══
type Version = { pageId: number; langId: number; langName: string; locale: string; pageName: string }
type Langs = { idPage: number; initial: number; versions: Version[]; creatable: Ref[] }
export function LanguagesTab({ idPage }: { idPage: number }) {
  const [d, setD] = useState<Langs | null>(null)
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null)
  useEffect(() => { let x = false; apiGet<Langs>(`languages?idPage=${idPage}`).then((v) => !x && setD(v)).catch((e) => !x && setMsg({ ok: false, text: e.message })); return () => { x = true } }, [idPage])
  if (!d) return <div style={wrap}>Chargement…</div>
  const th: React.CSSProperties = { textAlign: 'left', fontSize: 12, color: 'var(--color-muted-foreground,#6b7280)', padding: '8px 10px', borderBottom: '1px solid var(--color-border,#e5e7eb)' }
  const td: React.CSSProperties = { fontSize: 13, padding: '8px 10px', borderBottom: '1px solid var(--color-border,#f3f4f6)' }
  return (
    <div style={wrap}>
      <div style={{ fontSize: 13, fontWeight: 600, marginBottom: 8 }}>Versions de langue de cette page</div>
      <table style={{ width: '100%', borderCollapse: 'collapse' }}>
        <thead><tr><th style={th}>Langue</th><th style={th}>Locale</th><th style={th}>Nom de page</th><th style={th}>ID</th></tr></thead>
        <tbody>{d.versions.map((v) => (
          <tr key={v.pageId}>
            <td style={td}><span style={{ display: 'inline-flex', alignItems: 'center', gap: 8 }}><Flag locale={v.locale} />{v.langName || v.langId}</span></td>
            <td style={td}>{v.locale}</td><td style={td}>{v.pageName || '—'}</td><td style={td}>{v.pageId}</td>
          </tr>
        ))}</tbody>
      </table>
      {d.creatable.length > 0 && (
        <div style={{ marginTop: 16, fontSize: 13, color: 'var(--color-muted-foreground,#6b7280)' }}>
          Langues créables : {d.creatable.map((c) => <span key={c.id} style={{ display: 'inline-flex', alignItems: 'center', gap: 6, marginRight: 10 }}><Flag locale={c.locale} size={16} />{c.name}</span>)} — la création se fait via la vue <em>Old</em> pour l'instant.
        </div>
      )}
      <Feedback msg={msg} />
    </div>
  )
}
