import { useEffect, useMemo, useState } from 'react'
import { fetchSiteMeta, createSite, type SiteLang } from './sites-api'
import { useIsNarrow } from './shared/useIsNarrow'

/**
 * Assistant de création de site (brique MelisCms) — reprise FIDÈLE du wizard legacy en 5 étapes :
 *   1. Multilingue ?         (site mono ou multi-langue)
 *   2. Langues               (mono: 1 langue ; multi: N langues + reflet de la langue dans les URLs)
 *   3. Domaines              (domaine unique, ou 1 domaine par langue si "un domaine par langue")
 *   4. Module & fichiers     (nouveau module ou module existant, libellé, mode DnD, création des fichiers)
 *   5. Récapitulatif
 * La création réutilise le service legacy (POST /react-api/cms-sites/create → MelisCmsSiteService::saveSite).
 */

const LANG = (document.documentElement.lang || 'fr').slice(0, 2)
const tr = (fr: string, en: string) => (LANG === 'en' ? en : fr)

const card: React.CSSProperties = { borderRadius: 12, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', boxShadow: '0 1px 2px rgba(0,0,0,.04)' }
const btn: React.CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 36, padding: '0 14px', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'transparent', cursor: 'pointer', fontSize: 14 }
const btnPrimary: React.CSSProperties = { ...btn, border: 0, background: 'var(--color-primary,#cb4040)', color: '#fff', fontWeight: 600 }
const input: React.CSSProperties = { height: 36, width: '100%', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-background,#fff)', color: 'var(--color-foreground)', padding: '0 10px', fontSize: 14, boxSizing: 'border-box' }
const lbl: React.CSSProperties = { display: 'block', fontSize: 13, fontWeight: 600, marginBottom: 6 }
const subHint: React.CSSProperties = { fontSize: 12, color: 'var(--color-muted-foreground)', margin: '6px 0 0' }
const radioRow: React.CSSProperties = { display: 'flex', alignItems: 'center', gap: 8, fontSize: 14, cursor: 'pointer' }

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

// Regex de domaine (reprise du legacy sites.tool.js).
const DOMAIN_RE = /^(www\.)?(([a-zA-Z0-9-])+\.)?(([a-zA-Z0-9-])+\.)?(([a-zA-Z0-9-])+\.)?(([a-zA-Z0-9-])+\.)?[a-zA-Z0-9-]{1,}(\.([a-zA-Z]{2,}))$/
const isDomainOk = (d: string) => DOMAIN_RE.test(d.trim())
/** StudlyCase (module) — alpha uniquement, comme la validation legacy `^[A-Za-z]*$`. */
function toModule(s: string): string {
  return (s || '').replace(/[^a-zA-Z0-9 ]/g, '').split(/\s+/).filter(Boolean).map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join('')
}

interface Props { onCancel: () => void; onCreated: (label: string) => void }

// 5 étapes (comme le legacy).
const STEPS = [
  { fr: 'Multilingue', en: 'Multilingual' },
  { fr: 'Langues', en: 'Languages' },
  { fr: 'Domaines', en: 'Domains' },
  { fr: 'Module', en: 'Module' },
  { fr: 'Récapitulatif', en: 'Summary' },
]

export default function SiteWizard({ onCancel, onCreated }: Props) {
  const narrow = useIsNarrow()
  const [langs, setLangs] = useState<SiteLang[]>([])
  const [modules, setModules] = useState<string[]>([])
  const [defaultDomain, setDefaultDomain] = useState('')
  const [step, setStep] = useState(0)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  // Étape 1
  const [multiLang, setMultiLang] = useState(false)
  // Étape 2
  const [selectedLangIds, setSelectedLangIds] = useState<number[]>([])
  const [urlSetting, setUrlSetting] = useState(1) // 1 = locale après domaine, 2 = un domaine par langue, 3 = rien
  // Étape 3
  const [singleDomain, setSingleDomain] = useState('')
  const [domainsByLocale, setDomainsByLocale] = useState<Record<string, string>>({})
  // Étape 4
  const [isCreateNew, setIsCreateNew] = useState(true) // true = nouveau module, false = module existant
  const [moduleName, setModuleName] = useState('')     // nouveau module (alpha)
  const [moduleEdited, setModuleEdited] = useState(false)
  const [existingModule, setExistingModule] = useState('')
  const [label, setLabel] = useState('')               // libellé du site
  const [dndRenderMode, setDndRenderMode] = useState(false)
  const [createFile, setCreateFile] = useState(true)   // créer les fichiers du module

  useEffect(() => {
    fetchSiteMeta().then((m) => {
      setLangs(m.languages); setModules(m.modules); setDefaultDomain(m.defaultDomain)
      if (m.defaultDomain) setSingleDomain(m.defaultDomain)
    }).catch(() => null)
  }, [])
  // Nom de module auto-suggéré depuis le libellé tant que non édité à la main.
  useEffect(() => { if (!moduleEdited) setModuleName(toModule(label)) }, [label, moduleEdited])

  const selectedLangs = useMemo(() => langs.filter((l) => selectedLangIds.includes(l.id)), [langs, selectedLangIds])
  // Domaines par langue seulement en multilingue + "un domaine par langue" (urlSetting 2).
  const perLangDomains = multiLang && urlSetting === 2
  const effectiveUrlSetting = multiLang ? urlSetting : 1

  function toggleLang(id: number) {
    setSelectedLangIds((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id])
  }
  function setSingleLang(id: number) { setSelectedLangIds(id ? [id] : []) }

  function canNext(): boolean {
    if (step === 0) return true
    if (step === 1) return selectedLangs.length > 0
    if (step === 2) {
      if (perLangDomains) return selectedLangs.every((l) => isDomainOk(domainsByLocale[l.locale] || ''))
      return isDomainOk(singleDomain)
    }
    if (step === 3) {
      if (!label.trim()) return false
      if (isCreateNew) return /^[A-Za-z][A-Za-z0-9]*$/.test(moduleName.trim())
      return existingModule.trim() !== ''
    }
    return true
  }

  async function submit() {
    setSaving(true); setError(null)
    const domains: Record<string, string> = perLangDomains
      ? Object.fromEntries(selectedLangs.map((l) => [l.locale, (domainsByLocale[l.locale] || '').trim()]))
      : { single: singleDomain.trim() }
    const basePayload = {
      name: isCreateNew ? moduleName.trim() : '',
      label: label.trim(),
      languages: selectedLangs.map((l) => ({ id: l.id, locale: l.locale })),
      domains,
      urlSetting: effectiveUrlSetting,
      isNewSite: isCreateNew,
      existingModuleName: isCreateNew ? undefined : existingModule.trim(),
      dndRenderMode,
    }
    // Module EXISTANT → ne jamais (re)créer les fichiers (le dossier existe déjà) : createFile=false.
    const wantFiles = isCreateNew ? createFile : false
    try {
      let r
      try {
        r = await createSite({ ...basePayload, createFile: wantFiles })
      } catch (e) {
        // Cas « le dossier du module existe déjà » (createFile=true) : proposer de continuer sans
        // créer les fichiers (équivalent de la confirmation legacy), puis réessayer avec createFile=false.
        const msg = String((e as Error)?.message ?? e)
        if (wantFiles && /melissites/i.test(msg) &&
            window.confirm(tr('Le dossier du module existe déjà. Continuer sans créer les fichiers ?', 'The module folder already exists. Continue without creating the files?'))) {
          r = await createSite({ ...basePayload, createFile: false })
        } else {
          throw e
        }
      }
      // Un nouveau site crée des pages → recharger l'arbre de pages (menu gauche CMS).
      // PageTree écoute cet event (comme après la création d'une page) et reload ses racines.
      window.dispatchEvent(new CustomEvent('melis:cms-page-created', { detail: {} }))
      onCreated(r.siteLabel || label)
    } catch (e) {
      setError(String((e as Error)?.message ?? e))
    } finally { setSaving(false) }
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: narrow ? 14 : 20, padding: narrow ? 14 : 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      {/* Header — le retour se fait via la barre de sous-onglets (← Retour) ; pas de bouton redondant ici. */}
      <div>
        <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0 }}>{tr('Nouveau site', 'New site')}</h1>
        <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', margin: '2px 0 0' }}>
          {tr('Création guidée d’un site, étape par étape.', 'Guided site creation, step by step.')}
        </p>
      </div>

      {/* Stepper (5 étapes) — toutes les étapes restent nommées sur narrow (l'utilisateur doit voir
          où il va/d'où il vient, pas juste un numéro) : pastilles plus compactes + wrap sur 2 lignes,
          seules les flèches de liaison disparaissent (inutiles une fois le retour à la ligne actif). */}
      <div style={{ display: 'flex', gap: narrow ? 6 : 8, flexWrap: 'wrap' }}>
        {STEPS.map((s, i) => (
          <div key={i} style={{ display: 'flex', alignItems: 'center', gap: narrow ? 6 : 8 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: narrow ? 6 : 8, padding: narrow ? '5px 10px' : '6px 12px', borderRadius: 999,
              background: i === step ? 'var(--color-primary,#cb4040)' : i < step ? 'rgba(0,0,0,.06)' : 'transparent',
              color: i === step ? '#fff' : 'var(--color-foreground)', border: '1px solid var(--color-border,#e5e7eb)', fontSize: narrow ? 12 : 13, fontWeight: 600 }}>
              <span style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: 20, height: 20, borderRadius: 999,
                background: i === step ? 'rgba(255,255,255,.25)' : 'rgba(0,0,0,.06)', fontSize: 12, flexShrink: 0 }}>{i < step ? '✓' : i + 1}</span>
              {tr(s.fr, s.en)}
            </div>
            {i < STEPS.length - 1 && !narrow && <span style={{ color: 'var(--color-muted-foreground)' }}>→</span>}
          </div>
        ))}
      </div>

      {/* Contenu d'étape */}
      <div style={{ ...card, padding: narrow ? 14 : 20, maxWidth: narrow ? '100%' : 720 }}>
        {/* ── Étape 1 : Multilingue ? ── */}
        {step === 0 && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
            <label style={lbl}>{tr('Ce site sera-t-il multilingue ?', 'Will this site be multilingual?')}</label>
            <label style={radioRow}>
              <input type="radio" name="multi" checked={!multiLang} onChange={() => setMultiLang(false)} />
              {tr('Non — une seule langue', 'No — a single language')}
            </label>
            <label style={radioRow}>
              <input type="radio" name="multi" checked={multiLang} onChange={() => setMultiLang(true)} />
              {tr('Oui — plusieurs langues', 'Yes — multiple languages')}
            </label>
          </div>
        )}

        {/* ── Étape 2 : Langues (+ reflet dans les URLs si multi) ── */}
        {step === 1 && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            <div>
              <label style={lbl}>{multiLang ? tr('Choisissez les langues du site', 'Choose the site languages') : tr('Choisissez la langue du site', 'Choose the site language')}</label>
              {multiLang ? (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                  {langs.map((l) => (
                    <label key={l.id} style={radioRow}>
                      <input type="checkbox" checked={selectedLangIds.includes(l.id)} onChange={() => toggleLang(l.id)} />
                      <Flag locale={l.locale} />
                      {l.name} <span style={{ color: 'var(--color-muted-foreground)', fontSize: 12 }}>({l.locale})</span>
                    </label>
                  ))}
                </div>
              ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                  {langs.map((l) => (
                    <label key={l.id} style={radioRow}>
                      <input type="radio" name="singlelang" checked={selectedLangIds[0] === l.id} onChange={() => setSingleLang(l.id)} />
                      <Flag locale={l.locale} />
                      {l.name} <span style={{ color: 'var(--color-muted-foreground)', fontSize: 12 }}>({l.locale})</span>
                    </label>
                  ))}
                </div>
              )}
            </div>

            {multiLang && (
              <div>
                <label style={lbl}>{tr('Comment refléter la langue dans les URLs du site ?', "How do you want to reflect the language in the site's URLs?")}</label>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                  <label style={radioRow}>
                    <input type="radio" name="urlset" checked={urlSetting === 1} onChange={() => setUrlSetting(1)} />
                    {tr('La locale apparaît après le domaine (ex : www.monsite.com/fr/mapage)', 'The locale is shown after my domain (ex: www.mysite.com/en/myurl)')}
                  </label>
                  <label style={radioRow}>
                    <input type="radio" name="urlset" checked={urlSetting === 2} onChange={() => setUrlSetting(2)} />
                    {tr('Un domaine (ou sous-domaine) différent par langue', 'A different domain (or subdomain) per language')}
                  </label>
                  <label style={radioRow}>
                    <input type="radio" name="urlset" checked={urlSetting === 3} onChange={() => setUrlSetting(3)} />
                    {tr('Rien — l’URL est construite uniquement sur le nom de la page', "Nothing — the page URL is built solely on the page's name")}
                  </label>
                </div>
              </div>
            )}
          </div>
        )}

        {/* ── Étape 3 : Domaines ── */}
        {step === 2 && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            {perLangDomains ? (
              <>
                <label style={lbl}>{tr('Quels sont les domaines de ces sites ?', 'What are the domains of these sites?')}</label>
                {selectedLangs.map((l) => (
                  <div key={l.locale}>
                    <label style={{ ...lbl, display: 'flex', alignItems: 'center', gap: 8 }}><Flag locale={l.locale} />{l.name} <span style={{ color: 'var(--color-muted-foreground)', fontWeight: 400 }}>({l.locale})</span></label>
                    <input style={input} value={domainsByLocale[l.locale] || ''} onChange={(e) => setDomainsByLocale((d) => ({ ...d, [l.locale]: e.target.value }))} placeholder="exemple.com" />
                  </div>
                ))}
              </>
            ) : (
              <div>
                <label style={lbl}>{tr('Quel est le domaine de ce site ?', 'What is the domain of this site?')}</label>
                <input style={input} value={singleDomain} onChange={(e) => setSingleDomain(e.target.value)} placeholder={defaultDomain || 'exemple.com'} />
                <p style={subHint}>{tr('Le domaine de l’environnement courant (schéma http par défaut, modifiable ensuite).', 'Current environment domain (http scheme by default, editable later).')}</p>
              </div>
            )}
          </div>
        )}

        {/* ── Étape 4 : Module & fichiers ── */}
        {step === 3 && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            <div>
              <label style={lbl}>{tr('Module', 'Module')}</label>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                <label style={radioRow}>
                  <input type="radio" name="mod" checked={isCreateNew} onChange={() => setIsCreateNew(true)} />
                  {tr('Créer un nouveau module', 'Create a new module')}
                </label>
                <label style={radioRow}>
                  <input type="radio" name="mod" checked={!isCreateNew} onChange={() => setIsCreateNew(false)} />
                  {tr('Utiliser un module existant', 'Use an existing module')}
                </label>
              </div>
            </div>

            {isCreateNew ? (
              <div>
                <label style={lbl}>{tr('Nom du nouveau module', 'New module name')} *</label>
                <input style={{ ...input, fontFamily: 'monospace' }} value={moduleName}
                  onChange={(e) => { setModuleEdited(true); setModuleName(e.target.value.replace(/[^a-zA-Z0-9]/g, '')) }} placeholder="MonNouveauSite" />
                <p style={subHint}>{tr('Le module sera créé sous module/MelisSites/. Lettres et chiffres uniquement.', 'The module will be created under module/MelisSites/. Letters and digits only.')}</p>
              </div>
            ) : (
              <div>
                <label style={lbl}>{tr('Module attaché au site', 'Module attached to the site')} *</label>
                <select style={input} value={existingModule} onChange={(e) => setExistingModule(e.target.value)}>
                  <option value="">{tr('— Choisir un module —', '— Choose a module —')}</option>
                  {modules.map((m) => <option key={m} value={m}>{m}</option>)}
                </select>
              </div>
            )}

            <div>
              <label style={lbl}>{tr('Nom du site (libellé)', 'Site name (label)')} *</label>
              <input style={input} value={label} onChange={(e) => setLabel(e.target.value)} placeholder={tr('Mon nouveau site', 'My new site')} />
            </div>

            <label style={radioRow}>
              <input type="checkbox" checked={dndRenderMode} onChange={(e) => setDndRenderMode(e.target.checked)} />
              {tr('Activer le mode Drag & Drop (bootstrap)', 'Enable Drag & Drop mode (bootstrap)')}
            </label>

            {isCreateNew && (
              <label style={radioRow}>
                <input type="checkbox" checked={createFile} onChange={(e) => setCreateFile(e.target.checked)} />
                {tr('Créer les dossiers & fichiers du nouveau module', 'Create the folders & files for the new module')}
              </label>
            )}
          </div>
        )}

        {/* ── Étape 5 : Récapitulatif ── */}
        {step === 4 && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: narrow ? 14 : 10, fontSize: 14 }}>
            <p style={{ margin: 0, color: 'var(--color-muted-foreground)' }}>{tr('Un nouveau site va être créé avec les paramètres suivants :', 'A new site will be created with the following parameters:')}</p>
            <Row k={tr('Libellé', 'Label')} v={label} />
            <Row k={tr('Module', 'Module')} v={isCreateNew ? `${moduleName} ${tr('(nouveau)', '(new)')}` : `${existingModule} ${tr('(existant)', '(existing)')}`} />
            <Row k={tr('Multilingue', 'Multilingual')} v={multiLang ? tr('Oui', 'Yes') : tr('Non', 'No')} />
            <Row k={tr('Langues', 'Languages')} v={selectedLangs.map((l) => l.name).join(', ') || '—'} />
            {multiLang && <Row k={tr('URLs', 'URLs')} v={urlSetting === 1 ? tr('Locale après le domaine', 'Locale after domain') : urlSetting === 2 ? tr('Un domaine par langue', 'One domain per language') : tr('Nom de page seul', 'Page name only')} />}
            <Row k={tr('Domaines', 'Domains')} sub={perLangDomains ? tr('Un domaine par langue', 'One domain per language') : tr('Domaine unique', 'Single domain')}
              v={perLangDomains ? selectedLangs.map((l) => `${l.locale}: ${domainsByLocale[l.locale] || ''}`).join(' · ') : singleDomain} />
            <Row k={tr('Mode Drag & Drop', 'Drag & Drop mode')} v={dndRenderMode ? tr('Oui', 'Yes') : tr('Non', 'No')} />
            {isCreateNew && <Row k={tr('Créer les fichiers', 'Create files')} v={createFile ? tr('Oui', 'Yes') : tr('Non', 'No')} />}
            {error && <div style={{ color: '#b91c1c', fontSize: 13, marginTop: 6 }}>{error}</div>}
          </div>
        )}
      </div>

      {/* Navigation */}
      <div style={{ display: 'flex', justifyContent: 'space-between', maxWidth: narrow ? '100%' : 720 }}>
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

/**
 * Summary recap row — label above value stacked on narrow (a fixed-width label column next to
 * a long value like "Single domain — devsf6.melisplatform.com" wraps awkwardly on mobile).
 * `sub`, when present, renders as a small caption line above the value (e.g. "Single domain").
 */
function Row({ k, v, sub }: { k: string; v: string; sub?: string }) {
  const narrow = useIsNarrow()
  return (
    <div style={narrow
      ? { display: 'flex', flexDirection: 'column', gap: 2, padding: '8px 0', borderBottom: '1px solid var(--color-border,#e5e7eb)' }
      : { display: 'flex', gap: 12 }}>
      <div style={narrow ? { fontSize: 12, color: 'var(--color-muted-foreground)' } : { width: 160, flexShrink: 0, color: 'var(--color-muted-foreground)' }}>{k}</div>
      {narrow && sub && <div style={{ fontSize: 12, color: 'var(--color-muted-foreground)' }}>{sub}</div>}
      <div style={{ fontWeight: 600, overflowWrap: 'break-word' }}>{sub && !narrow ? `${sub} — ${v || '—'}` : (v || '—')}</div>
    </div>
  )
}
