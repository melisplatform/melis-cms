import { useState } from 'react'
import type { SiteConfigData, ConfigSection } from '../sites-api'

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
  const sk = sectionKey(sect)
  if (sect.items.length === 0) {
    return <div style={{ ...hint, padding: '16px 0' }}>{tr('Aucune donnée de configuration.', 'No configuration data.')}</div>
  }
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 14, maxWidth: 520 }}>
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
  const sections: { key: string; label: string; sect: ConfigSection }[] = [
    { key: 'gen', label: tr('Général', 'General'), sect: data.general },
    ...data.perLang.map((s) => ({ key: String(s.langId), label: s.name || s.locale || String(s.langId), sect: s })),
  ]
  const current = sections.find((s) => s.key === sub) ?? sections[0]
  return (
    <div style={{ display: 'flex', gap: 20 }}>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 4, minWidth: 150 }}>
        {sections.map((s) => (
          <button key={s.key} onClick={() => setSub(s.key)}
            style={{ textAlign: 'left', padding: '8px 12px', borderRadius: 8, border: 0, cursor: 'pointer', fontSize: 13, fontWeight: 600,
              background: sub === s.key ? 'var(--color-primary,#cb4040)' : 'transparent', color: sub === s.key ? '#fff' : 'var(--color-foreground)' }}>
            {s.label}
          </button>
        ))}
      </div>
      <div style={{ flex: 1 }}>
        <SectionForm sect={current.sect} fields={fields} setField={setField} />
      </div>
    </div>
  )
}

export default ConfigTab
