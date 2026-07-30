import { useState } from 'react'
import type { SiteConfigData, ConfigSection } from '../sites-api'
import { useIsNarrow } from '../shared/useIsNarrow'

/**
 * Onglet "Config" : configuration fusionnée (fichier + DB) éditable, sous-onglets Général + langues.
 * Les champs sont nommés EXACTEMENT comme le legacy (`gen_sconf_<clé>` / `<langId>_sconf_<clé>[<sous>]`)
 * pour que le POST `saveSite` les regroupe (diff vs fichier côté Melis). La map `fields` (clé = nom de
 * champ) est l'état remonté à l'éditeur pour la sauvegarde.
 */

const tr = (fr: string, en: string) => ((document.documentElement.lang || 'fr').slice(0, 2) === 'en' ? en : fr)
const lbl: React.CSSProperties = { display: 'block', fontSize: 13, fontWeight: 600, marginBottom: 4 }
const input: React.CSSProperties = { height: 34, width: '100%', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-background,#fff)', padding: '0 10px', fontSize: 14, boxSizing: 'border-box' }
const hint: React.CSSProperties = { fontSize: 12, color: 'var(--color-muted-foreground)' }

/** Pilule de sélection (Général + une par langue) — même design que platform theme / login. */
const pill = (active: boolean): React.CSSProperties => ({
  display: 'inline-flex', alignItems: 'center', gap: 8, height: 36, padding: '0 14px', borderRadius: 8,
  border: active ? '1.5px solid var(--color-primary,#cb4040)' : '1px solid var(--color-border,#e5e7eb)',
  background: active ? 'color-mix(in srgb, var(--color-primary,#cb4040) 12%, transparent)' : 'transparent',
  color: active ? 'var(--color-primary,#cb4040)' : 'var(--color-foreground)', fontSize: 14, fontWeight: 600, cursor: 'pointer',
})
/** Drapeau de langue (image servie par MelisCore, comme dans les autres outils). */
function Flag({ locale }: { locale: string }) {
  const short = (locale || '').slice(0, 2).toLowerCase()
  if (!short) return null
  return (
    <img src={`/MelisCore/assets/images/lang/${short}.png`} alt="" width={18} height={12}
      style={{ borderRadius: 2, objectFit: 'cover', boxShadow: '0 0 0 1px rgba(0,0,0,.1)', flexShrink: 0 }}
      onError={(e) => { (e.currentTarget as HTMLImageElement).style.display = 'none' }} />
  )
}

const sectionKey = (s: ConfigSection) => (s.langId == null ? 'gen' : String(s.langId))

/** Nom de champ POST legacy pour une valeur de config. */
export function cfgField(sect: string, key: string, entryKey?: string): string {
  return entryKey != null ? `${sect}_sconf_${key}[${entryKey}]` : `${sect}_sconf_${key}`
}

/** Construit la map initiale des champs (valeurs + sconf_id) depuis les données chargées. */
export function buildConfigFields(data: SiteConfigData): Record<string, string> {
  const out: Record<string, string> = {}
  const fill = (s: ConfigSection) => {
    const sk = sectionKey(s)
    out[`${sk}_sconf_id`] = String(s.sconfId || 0)
    for (const it of s.items) {
      if (it.type === 'array') for (const e of it.entries ?? []) out[cfgField(sk, it.key, e.key)] = e.value
      else out[cfgField(sk, it.key)] = it.value ?? ''
    }
  }
  fill(data.general)
  data.perLang.forEach(fill)
  return out
}

function SectionForm({ sect, fields, setField }: { sect: ConfigSection; fields: Record<string, string>; setField: (n: string, v: string) => void }) {
  const narrow = useIsNarrow()
  const sk = sectionKey(sect)
  if (sect.items.length === 0) {
    return <div style={{ ...hint, padding: '16px 0' }}>{tr('Aucune donnée de configuration.', 'No configuration data.')}</div>
  }
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 14, maxWidth: narrow ? '100%' : 520 }}>
      {sect.items.map((it) => it.type === 'scalar' ? (
        <div key={it.key}>
          <label style={lbl}>{it.key}</label>
          <input style={input} value={fields[cfgField(sk, it.key)] ?? ''} onChange={(e) => setField(cfgField(sk, it.key), e.target.value)} />
        </div>
      ) : (
        <div key={it.key}>
          <label style={lbl}>{it.key} <span style={hint}>(array)</span></label>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 8, paddingLeft: 16, borderLeft: '2px solid var(--color-border,#eee)' }}>
            {(it.entries ?? []).map((e) => (
              <div key={e.key}>
                {!e.isInt && <label style={{ ...lbl, fontWeight: 400, color: 'var(--color-muted-foreground)' }}>{e.key}</label>}
                <input style={input} value={fields[cfgField(sk, it.key, e.key)] ?? ''} onChange={(ev) => setField(cfgField(sk, it.key, e.key), ev.target.value)} />
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  )
}

export function ConfigTab({ data, fields, setField }: { data: SiteConfigData; fields: Record<string, string>; setField: (n: string, v: string) => void }) {
  const [sub, setSub] = useState<string>('gen')
  const sections: { key: string; label: string; locale: string | null; sect: ConfigSection }[] = [
    { key: 'gen', label: tr('Général', 'General'), locale: null, sect: data.general },
    ...data.perLang.map((s) => ({ key: String(s.langId), label: s.name || s.locale || String(s.langId), locale: s.locale ?? null, sect: s })),
  ]
  const current = sections.find((s) => s.key === sub) ?? sections[0]
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
      {/* Sélecteur : « Général » + une pilule par langue (drapeau + nom), design cohérent avec platform theme. */}
      <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
        {sections.map((s) => (
          <button key={s.key} onClick={() => setSub(s.key)} style={pill(sub === s.key)}>
            {s.locale && <Flag locale={s.locale} />}
            {s.label}
          </button>
        ))}
      </div>
      <div>
        <SectionForm sect={current.sect} fields={fields} setField={setField} />
      </div>
    </div>
  )
}

export default ConfigTab
