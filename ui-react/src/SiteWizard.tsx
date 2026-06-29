import { useEffect, useMemo, useState } from 'react'
import { fetchSiteLangs, createSite, type SiteLang } from './sites-api'

const LANG = (document.documentElement.lang || 'fr').slice(0, 2)
const tr = (fr: string, en: string) => (LANG === 'en' ? en : fr)

const card: React.CSSProperties = { borderRadius: 12, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', boxShadow: '0 1px 2px rgba(0,0,0,.04)' }
const btn: React.CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 36, padding: '0 14px', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'transparent', cursor: 'pointer', fontSize: 14 }
const btnPrimary: React.CSSProperties = { ...btn, border: 0, background: 'var(--color-primary,#cb4040)', color: '#fff', fontWeight: 600 }
const input: React.CSSProperties = { height: 36, width: '100%', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-background,#fff)', padding: '0 10px', fontSize: 14, boxSizing: 'border-box' }
const lbl: React.CSSProperties = { display: 'block', fontSize: 13, fontWeight: 600, marginBottom: 6 }

/** StudlyCase pour suggérer le nom de module à partir du libellé. */
function toModule(s: string): string {
  return (s || '').replace(/[^a-zA-Z0-9 ]/g, '').split(/\s+/).filter(Boolean).map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join('')
}

interface Props { onCancel: () => void; onCreated: (label: string) => void }

const STEPS = [
  { fr: 'Identité', en: 'Identity' },
  { fr: 'Langues', en: 'Languages' },
  { fr: 'Domaines', en: 'Domains' },
  { fr: 'Récapitulatif', en: 'Summary' },
]

export default function SiteWizard({ onCancel, onCreated }: Props) {
  const [langs, setLangs] = useState<SiteLang[]>([])
  const [step, setStep] = useState(0)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  // form
  const [label, setLabel] = useState('')
  const [name, setName] = useState('')
  const [nameEdited, setNameEdited] = useState(false)
  const [multiLang, setMultiLang] = useState(false)
  const [selectedLangIds, setSelectedLangIds] = useState<number[]>([])
  const [urlSetting, setUrlSetting] = useState(1) // 1 = domaine unique, 2 = multi-domaine
  const [domains, setDomains] = useState<Record<string, string>>({})

  useEffect(() => { fetchSiteLangs().then(setLangs).catch(() => null) }, [])
  // module name auto-suggéré tant que non édité manuellement
  useEffect(() => { if (!nameEdited) setName(toModule(label)) }, [label, nameEdited])

  const selectedLangs = useMemo(() => langs.filter((l) => selectedLangIds.includes(l.id)), [langs, selectedLangIds])

  function toggleLang(id: number) {
    setSelectedLangIds((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id])
  }
  function setSingleLang(id: number) { setSelectedLangIds(id ? [id] : []) }

  const effectiveUrlSetting = multiLang ? urlSetting : 1

  function canNext(): boolean {
    if (step === 0) return label.trim() !== '' && name.trim() !== ''
    if (step === 1) return selectedLangs.length > 0
    if (step === 2) {
      if (effectiveUrlSetting === 2) return selectedLangs.every((l) => (domains[l.locale] || '').trim() !== '')
      return (domains.single || '').trim() !== ''
    }
    return true
  }

  async function submit() {
    setSaving(true); setError(null)
    try {
      const r = await createSite({
        name: name.trim(),
        label: label.trim(),
        languages: selectedLangs.map((l) => ({ id: l.id, locale: l.locale })),
        domains: effectiveUrlSetting === 2
          ? Object.fromEntries(selectedLangs.map((l) => [l.locale, (domains[l.locale] || '').trim()]))
          : { single: (domains.single || '').trim() },
        urlSetting: effectiveUrlSetting,
        createModule: true,
        isNewSite: true,
      })
      onCreated(r.siteLabel || label)
    } catch (e) {
      setError(String((e as Error)?.message ?? e))
    } finally { setSaving(false) }
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, padding: 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 }}>
        <div>
          <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0 }}>{tr('Nouveau site', 'New site')}</h1>
          <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', margin: '2px 0 0' }}>
            {tr('Création guidée d’un site, étape par étape.', 'Guided site creation, step by step.')}
          </p>
        </div>
        <button style={btn} onClick={onCancel}>{tr('Annuler', 'Cancel')}</button>
      </div>

      {/* Stepper */}
      <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
        {STEPS.map((s, i) => (
          <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '6px 12px', borderRadius: 999,
              background: i === step ? 'var(--color-primary,#cb4040)' : i < step ? 'rgba(0,0,0,.06)' : 'transparent',
              color: i === step ? '#fff' : 'var(--color-foreground)', border: '1px solid var(--color-border,#e5e7eb)', fontSize: 13, fontWeight: 600 }}>
              <span style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 20, height: 20, borderRadius: 999,
                background: i === step ? 'rgba(255,255,255,.25)' : 'rgba(0,0,0,.06)', fontSize: 12 }}>{i < step ? '✓' : i + 1}</span>
              {tr(s.fr, s.en)}
            </div>
            {i < STEPS.length - 1 && <span style={{ color: 'var(--color-muted-foreground)' }}>→</span>}
          </div>
        ))}
      </div>

      {/* Contenu d'étape */}
      <div style={{ ...card, padding: 20, maxWidth: 720 }}>
        {step === 0 && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            <div>
              <label style={lbl}>{tr('Libellé du site', 'Site label')}</label>
              <input style={input} value={label} onChange={(e) => setLabel(e.target.value)} placeholder={tr('Mon nouveau site', 'My new site')} />
            </div>
            <div>
              <label style={lbl}>{tr('Nom du module', 'Module name')}</label>
              <input style={{ ...input, fontFamily: 'monospace' }} value={name}
                onChange={(e) => { setNameEdited(true); setName(e.target.value.replace(/[^a-zA-Z0-9]/g, '')) }} placeholder="MyNewSite" />
              <p style={{ fontSize: 12, color: 'var(--color-muted-foreground)', margin: '6px 0 0' }}>
                {tr('Le module sera créé sous module/MelisSites/.', 'The module will be created under module/MelisSites/.')}
              </p>
            </div>
            <label style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 14, cursor: 'pointer' }}>
              <input type="checkbox" checked={multiLang} onChange={(e) => setMultiLang(e.target.checked)} />
              {tr('Site multilingue', 'Multilingual site')}
            </label>
          </div>
        )}

        {step === 1 && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
            <label style={lbl}>{multiLang ? tr('Langues du site', 'Site languages') : tr('Langue du site', 'Site language')}</label>
            {multiLang ? (
              <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                {langs.map((l) => (
                  <label key={l.id} style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 14, cursor: 'pointer' }}>
                    <input type="checkbox" checked={selectedLangIds.includes(l.id)} onChange={() => toggleLang(l.id)} />
                    {l.name} <span style={{ color: 'var(--color-muted-foreground)', fontSize: 12 }}>({l.locale})</span>
                  </label>
                ))}
              </div>
            ) : (
              <select style={{ ...input, width: 'auto', minWidth: 240 }} value={selectedLangIds[0] ?? ''} onChange={(e) => setSingleLang(Number(e.target.value))}>
                <option value="">{tr('— choisir —', '— choose —')}</option>
                {langs.map((l) => <option key={l.id} value={l.id}>{l.name} ({l.locale})</option>)}
              </select>
            )}
          </div>
        )}

        {step === 2 && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            {multiLang && (
              <div>
                <label style={lbl}>{tr('Configuration des domaines', 'Domain configuration')}</label>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                  <label style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 14, cursor: 'pointer' }}>
                    <input type="radio" name="urlset" checked={urlSetting === 1} onChange={() => setUrlSetting(1)} />
                    {tr('Un domaine pour toutes les langues', 'One domain for all languages')}
                  </label>
                  <label style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 14, cursor: 'pointer' }}>
                    <input type="radio" name="urlset" checked={urlSetting === 2} onChange={() => setUrlSetting(2)} />
                    {tr('Un domaine par langue', 'One domain per language')}
                  </label>
                </div>
              </div>
            )}
            {effectiveUrlSetting === 2 ? (
              selectedLangs.map((l) => (
                <div key={l.locale}>
                  <label style={lbl}>{tr('Domaine', 'Domain')} — {l.name} ({l.locale})</label>
                  <input style={input} value={domains[l.locale] || ''} onChange={(e) => setDomains((d) => ({ ...d, [l.locale]: e.target.value }))} placeholder="exemple.com" />
                </div>
              ))
            ) : (
              <div>
                <label style={lbl}>{tr('Domaine', 'Domain')}</label>
                <input style={input} value={domains.single || ''} onChange={(e) => setDomains((d) => ({ ...d, single: e.target.value }))} placeholder="exemple.com" />
              </div>
            )}
          </div>
        )}

        {step === 3 && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10, fontSize: 14 }}>
            <Row k={tr('Libellé', 'Label')} v={label} />
            <Row k={tr('Module', 'Module')} v={name} />
            <Row k={tr('Multilingue', 'Multilingual')} v={multiLang ? tr('Oui', 'Yes') : tr('Non', 'No')} />
            <Row k={tr('Langues', 'Languages')} v={selectedLangs.map((l) => l.name).join(', ') || '—'} />
            <Row k={tr('Domaines', 'Domains')} v={effectiveUrlSetting === 2 ? selectedLangs.map((l) => `${l.locale}: ${domains[l.locale] || ''}`).join(' · ') : (domains.single || '')} />
            {error && <div style={{ color: '#b91c1c', fontSize: 13, marginTop: 6 }}>{error}</div>}
          </div>
        )}
      </div>

      {/* Navigation */}
      <div style={{ display: 'flex', justifyContent: 'space-between', maxWidth: 720 }}>
        <button style={btn} onClick={() => (step === 0 ? onCancel() : setStep((s) => s - 1))}>
          {step === 0 ? tr('Annuler', 'Cancel') : tr('Précédent', 'Back')}
        </button>
        {step < STEPS.length - 1 ? (
          <button style={{ ...btnPrimary, opacity: canNext() ? 1 : 0.5, pointerEvents: canNext() ? 'auto' : 'none' }} onClick={() => setStep((s) => s + 1)}>
            {tr('Suivant', 'Next')} →
          </button>
        ) : (
          <button style={{ ...btnPrimary, opacity: saving ? 0.6 : 1 }} disabled={saving} onClick={submit}>
            {saving ? tr('Création…', 'Creating…') : tr('Créer le site', 'Create site')}
          </button>
        )}
      </div>
    </div>
  )
}

function Row({ k, v }: { k: string; v: string }) {
  return (
    <div style={{ display: 'flex', gap: 12 }}>
      <div style={{ width: 140, color: 'var(--color-muted-foreground)' }}>{k}</div>
      <div style={{ fontWeight: 600 }}>{v || '—'}</div>
    </div>
  )
}
