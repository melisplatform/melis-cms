import { useEffect, useMemo, useRef, useState } from 'react'
import { fetchSite, saveSiteEdit, fetchSiteConfig, fetchSiteModules, type SiteEditData, type SiteConfigData, type SiteModulesData, type SiteModule } from './sites-api'
import { PagePicker } from './PagePicker'
import { type ViewMode } from './ViewToggle'
import { ConfigTab, buildConfigFields } from './site-tabs/ConfigTab'
import { ModuleLoaderTab, activeFirst } from './site-tabs/ModuleLoaderTab'
import { TranslationsTab } from './site-tabs/TranslationsTab'
import { useSiteTabs, type SiteTabSaveFn } from './site-tab-registry'
import { useIsNarrow } from './shared/useIsNarrow'
import { parseSiteSaveErrors, byKey, type SiteFieldError } from './site-errors'

const MELIS_KEY = 'meliscms_tool_sites'
function can(cap: string): boolean {
  return (window as unknown as { MelisCan?: (k: string, c: string) => boolean }).MelisCan?.(MELIS_KEY, cap) ?? true
}

// Mini-i18n local (FR/EN) — même approche que SitesPage (pas d'import @/ hôte).
const LANG = (document.documentElement.lang || 'fr').slice(0, 2)
const tr = (fr: string, en: string) => (LANG === 'en' ? en : fr)

// Styles inline (la brique ne compile pas le Tailwind de l'hôte).
const card: React.CSSProperties = { borderRadius: 12, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-card,#fff)', boxShadow: '0 1px 2px rgba(0,0,0,.04)' }
const btn: React.CSSProperties = { display: 'inline-flex', alignItems: 'center', gap: 6, height: 36, padding: '0 14px', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'transparent', cursor: 'pointer', fontSize: 14 }
const btnPrimary: React.CSSProperties = { ...btn, border: 0, background: 'var(--color-primary,#cb4040)', color: '#fff', fontWeight: 600 }
const input: React.CSSProperties = { height: 36, width: '100%', borderRadius: 8, border: '1px solid var(--color-border,#e5e7eb)', background: 'var(--color-background,#fff)', padding: '0 10px', fontSize: 14, boxSizing: 'border-box' }
const lbl: React.CSSProperties = { display: 'block', fontSize: 13, fontWeight: 600, marginBottom: 6 }
const hint: React.CSSProperties = { fontSize: 12, color: 'var(--color-muted-foreground)', margin: '6px 0 0' }
const inputErr: React.CSSProperties = { borderColor: '#dc2626', boxShadow: '0 0 0 1px #dc2626' }

/** Messages d'erreur d'un champ, affichés juste sous lui (rouge). */
function FieldError({ messages }: { messages?: string[] }) {
  if (!messages?.length) return null
  return (
    <>
      {messages.map((m, i) => (
        <p key={i} style={{ margin: '6px 0 0', fontSize: 12, color: '#b91c1c' }}>{m}</p>
      ))}
    </>
  )
}

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

// Onglets NATIFS (ordre identique à l'édition legacy). Des modules peuvent AJOUTER des onglets via
// le registre générique (site-tab-registry) — ex. « Scripts » livré par MelisCmsPageScriptEditor.
const TABS = [
  { id: 'props', fr: 'Propriétés', en: 'Properties' },
  { id: 'modules', fr: 'Chargement de modules', en: 'Module Loading' },
  { id: 'domains', fr: 'Domaines', en: 'Domains' },
  { id: 'langs', fr: 'Langues', en: 'Languages' },
  { id: 'config', fr: 'Config du site', en: 'Site Config' },
  { id: 'translations', fr: 'Traductions', en: 'Translations' },
] as const

interface DomainState { id: number; env: string; scheme: string; domain: string }

interface Props {
  siteId: number
  onSaved: () => void
  /** Remonte le libellé du site au conteneur (pour le sous-onglet). */
  onLabel: (label: string) => void
  /** Le site n'a pas pu être chargé (supprimé entre-temps, id bidon dans l'URL) → le conteneur ferme le sous-onglet. */
  onLoadFail?: () => void
}

export default function SiteEditor({ siteId, onSaved, onLabel, onLoadFail }: Props) {
  const narrow = useIsNarrow()
  const [data, setData] = useState<SiteEditData | null>(null)
  const [loadErr, setLoadErr] = useState<string | null>(null)
  const [tab, setTab] = useState<string>('props')

  // Onglets contribués par des modules (registre générique). Ne sont présents que si le module est
  // actif (sa brique n'est chargée que dans ce cas). Montés à la 1ʳᵉ ouverture et gardés montés
  // (état + save préservés) ; leur save est déclenché par le Save GLOBAL de l'éditeur.
  const extraTabs = useSiteTabs()
  const [activatedTabs, setActivatedTabs] = useState<Set<string>>(() => new Set())
  const saveHandlers = useRef<Record<string, SiteTabSaveFn | null>>({})
  const registerFns = useRef<Record<string, (fn: SiteTabSaveFn | null) => void>>({})
  const registerSaveFor = (id: string) =>
    (registerFns.current[id] ??= (fn) => { saveHandlers.current[id] = fn })
  const openExtraTab = (id: string) => { setTab(id); setActivatedTabs((s) => s.has(id) ? s : new Set(s).add(id)) }
  const tabLabelOf = (l: string | { fr: string; en: string }) => (typeof l === 'string' ? l : (LANG === 'en' ? l.en : l.fr))

  const [mode] = useState<ViewMode>('react') // édition toujours en React (pas de vue Old ici)
  const [saving, setSaving] = useState(false)
  const [savedAt, setSavedAt] = useState(0)
  const [error, setError] = useState<string | null>(null)
  // Erreurs PAR CHAMP renvoyées par le save legacy (domaine déjà pris, champ requis…) : affichées
  // sous le champ concerné + récapitulées dans le bandeau, avec bascule sur l'onglet fautif.
  const [fieldErrors, setFieldErrors] = useState<SiteFieldError[]>([])
  const errsByKey = useMemo(() => byKey(fieldErrors), [fieldErrors])
  /** Efface l'erreur d'un champ dès que l'utilisateur le corrige. */
  const clearFieldError = (key: string) => setFieldErrors((prev) => prev.filter((e) => e.key !== key))
  // Champs qui savent afficher leur propre erreur (voir <FieldError> plus bas) ; les AUTRES clés
  // restent listées dans le bandeau, faute de quoi elles n'apparaîtraient nulle part.
  const ANCHORED_FIELDS = ['sdom_domain', 'sdom_scheme', 'site_label', 'site_main_page_id', 's404_page_id', 'shome_page_id']
  const orphanErrors = fieldErrors.filter((e) => !ANCHORED_FIELDS.includes(e.field))

  // form state
  const [label, setLabel] = useState('')
  const [dndMode, setDndMode] = useState('')
  const [s404, setS404] = useState({ id: 0, title: '' })
  const [mainPage, setMainPage] = useState({ id: 0, title: '' })
  const [optLangUrl, setOptLangUrl] = useState(1)
  const [activeLangIds, setActiveLangIds] = useState<number[]>([])
  const [initialActive, setInitialActive] = useState<number[]>([])
  const [homes, setHomes] = useState<Record<number, { pageId: number; shomeId: number; title: string }>>({})
  const [domains, setDomains] = useState<DomainState[]>([])
  // Onglets avancés
  const [configData, setConfigData] = useState<SiteConfigData | null>(null)
  const [configFields, setConfigFields] = useState<Record<string, string>>({})
  const [modulesData, setModulesData] = useState<SiteModulesData | null>(null)
  const [moduleList, setModuleList] = useState<SiteModule[]>([]) // ordonné (ordre de chargement)

  useEffect(() => {
    fetchSiteConfig(siteId).then((c) => { setConfigData(c); setConfigFields(buildConfigFields(c)) }).catch(() => null)
    fetchSiteModules(siteId).then((m) => { setModulesData(m); setModuleList(activeFirst(m.modules)) }).catch(() => null)
    fetchSite(siteId).then((d) => {
      setData(d)
      setLabel(d.site.label)
      onLabel(d.site.label || `Site #${siteId}`)
      setDndMode(d.site.dndRenderMode)
      setOptLangUrl(d.site.optLangUrl || 1)
      const titleOf = (id: number) => d.pageTitles[String(id)] || (id ? `Page #${id}` : '')
      setS404({ id: d.site.s404PageId, title: titleOf(d.site.s404PageId) })
      setMainPage({ id: d.site.mainPageId, title: titleOf(d.site.mainPageId) })
      const active = d.languages.filter((l) => l.active).map((l) => l.id)
      setActiveLangIds(active)
      setInitialActive(active)
      const hm: Record<number, { pageId: number; shomeId: number; title: string }> = {}
      for (const h of d.homepages) hm[h.langId] = { pageId: h.pageId, shomeId: h.shomeId, title: titleOf(h.pageId) }
      setHomes(hm)
      // un état domaine par environnement (réutilise l'existant ou défaut http/vide)
      const doms: DomainState[] = d.environments.map((env) => {
        const ex = d.domains.find((x) => x.env === env)
        return ex ? { ...ex } : { id: 0, env, scheme: 'http', domain: '' }
      })
      setDomains(doms)
    }).catch((e) => { setLoadErr(String((e as Error)?.message ?? e)); onLoadFail?.() })
  }, [siteId])

  const langById = useMemo(() => Object.fromEntries((data?.languages ?? []).map((l) => [l.id, l])), [data])
  const removedLangIds = useMemo(() => initialActive.filter((id) => !activeLangIds.includes(id)), [initialActive, activeLangIds])

  function toggleLang(id: number) {
    setActiveLangIds((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id])
  }
  function setHome(langId: number, pageId: number, title: string) {
    setHomes((prev) => ({ ...prev, [langId]: { pageId, shomeId: prev[langId]?.shomeId ?? 0, title } }))
  }
  function setDomain(env: string, patch: Partial<DomainState>) {
    setDomains((prev) => prev.map((d) => d.env === env ? { ...d, ...patch } : d))
  }
  function setConfigField(name: string, value: string) {
    setConfigFields((prev) => ({ ...prev, [name]: value }))
  }
  const transLangs = useMemo(
    () => activeLangIds.map((id) => langById[id]).filter(Boolean).map((l) => ({ id: l.id, locale: l.locale, name: l.name })),
    [activeLangIds, langById],
  )

  /** Libellé lisible d'un champ en erreur (le legacy ne renvoie que le nom technique). */
  function fieldLabel(f: SiteFieldError): string {
    const NAMES: Record<string, string> = {
      sdom_domain: tr('Domaine', 'Domain'),
      sdom_scheme: tr('Schéma', 'Scheme'),
      site_label: tr('Libellé du site', 'Site label'),
      site_name: tr('Nom du module', 'Module name'),
      s404_page_id: tr('Page 404', '404 page'),
      site_main_page_id: tr('Page d’accueil principale', 'Main home page'),
      shome_page_id: tr('Page d’accueil', 'Home page'),
    }
    const base = NAMES[f.field] ?? f.field
    if (f.field.startsWith('sdom_')) return `${base} (${f.scope})`
    if (f.field.startsWith('shome_')) return `${base} (${langById[Number(f.scope)]?.name ?? f.scope})`
    return base
  }

  function validate(): string | null {
    if (!label.trim()) return tr('Le libellé est requis.', 'Label is required.')
    if (!s404.id) return tr('La page 404 est requise.', '404 page is required.')
    if (!mainPage.id) return tr('La page d’accueil principale est requise.', 'Main home page is required.')
    if (activeLangIds.length === 0) return tr('Au moins une langue doit être active.', 'At least one language must be active.')
    for (const id of activeLangIds) {
      if (!homes[id]?.pageId) return tr(`Page d’accueil manquante pour ${langById[id]?.name ?? id}.`, `Missing home page for ${langById[id]?.name ?? id}.`)
    }
    if (data) {
      const cur = domains.find((d) => d.env === data.currentEnv)
      if (!cur || !cur.domain.trim()) return tr(`Le domaine de l’environnement « ${data.currentEnv} » est requis.`, `Domain for environment "${data.currentEnv}" is required.`)
    }
    return null
  }

  async function submit() {
    const v = validate()
    if (v) { setError(v); return }
    if (!data) return
    setSaving(true); setError(null); setFieldErrors([])
    try {
      const res = await saveSiteEdit({
        id: siteId,
        name: data.site.name,
        label: label.trim(),
        s404PageId: s404.id,
        mainPageId: mainPage.id,
        dndRenderMode: dndMode,
        optLangUrl,
        activeLangIds,
        removedLangIds,
        homepages: activeLangIds.map((langId) => ({ langId, pageId: homes[langId]?.pageId ?? 0, shomeId: homes[langId]?.shomeId ?? 0 })),
        domains: domains.filter((d) => d.domain.trim() !== '' || d.id > 0).map((d) => ({ env: d.env, scheme: d.scheme, domain: d.domain.trim(), id: d.id })),
        configFields,
        modules: modulesData ? { isAdmin: modulesData.isAdmin, activeNames: moduleList.filter((m) => m.active).map((m) => m.name) } : undefined,
      })
      if (res.success === true || (res.success as unknown) === 1) {
        // Persiste aussi les onglets contribués (ex. Scripts) via leur save enregistré — même Save global.
        for (const id of Object.keys(saveHandlers.current)) {
          const fn = saveHandlers.current[id]
          if (fn) { try { await fn() } catch { /* l'onglet gère sa propre erreur (toast) */ } }
        }
        onLabel(label.trim() || `Site #${siteId}`)
        setSavedAt(Date.now())
        window.postMessage({ __melisNotif: true, kind: 'ok', title: tr('Sites', 'Sites'), message: tr('Enregistré ✓', 'Saved ✓') }, '*')
        onSaved()
      } else {
        // Détail par champ : sans ça l'utilisateur n'a qu'un « Unable to save the site. » opaque.
        const fields = parseSiteSaveErrors(res.errors)
        setFieldErrors(fields)
        setError(res.textMessage || tr('Échec de l’enregistrement.', 'Save failed.'))
        // Bascule sur l'onglet du 1ᵉʳ champ fautif (l'erreur peut venir d'un onglet non affiché).
        if (fields.length > 0) setTab(fields[0].tab)
      }
    } catch (e) {
      setError(String((e as Error)?.message ?? e))
    } finally { setSaving(false) }
  }

  if (loadErr) {
    return (
      <div style={{ padding: 24 }}>
        <div style={{ ...card, padding: 24, color: '#b91c1c' }}>{loadErr}</div>
      </div>
    )
  }
  if (!data) return <div style={{ padding: 24, fontSize: 14, color: 'var(--color-muted-foreground)' }}>{tr('Chargement…', 'Loading…')}</div>

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: narrow ? 14 : 20, padding: narrow ? 14 : 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 }}>
        <div style={{ minWidth: 0 }}>
          <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{tr('Éditer le site', 'Edit site')} — {data.site.label}</h1>
          <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', margin: '2px 0 0', fontFamily: 'monospace' }}>{data.site.name} · #{data.site.id}</p>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexShrink: 0 }}>
          {/* Pas de toggle New/Old en ÉDITION (uniquement sur la liste). */}
          {savedAt > 0 && mode === 'react' && <span style={{ fontSize: 12, color: '#15803d' }}>✓ {tr('Enregistré', 'Saved')}</span>}
          {can('edit') && mode === 'react' && (
            <button style={{ ...btnPrimary, opacity: saving ? 0.6 : 1 }} disabled={saving} onClick={submit}>
              {saving ? tr('Enregistrement…', 'Saving…') : tr('Enregistrer', 'Save')}
            </button>
          )}
        </div>
      </div>

      {/* Tabs — wrap sur 2ᵉ ligne sur narrow (jamais de scroll horizontal) */}
      <div style={{ display: mode === 'react' ? 'flex' : 'none', gap: 4, borderBottom: '1px solid var(--color-border,#e5e7eb)', flexWrap: narrow ? 'wrap' : 'nowrap' }}>
        {TABS.map((t) => (
          <button key={t.id} onClick={() => setTab(t.id)}
            style={{ height: 38, padding: narrow ? '0 10px' : '0 16px', border: 0, background: 'transparent', cursor: 'pointer', fontSize: narrow ? 13 : 14, fontWeight: 600,
              color: tab === t.id ? 'var(--color-primary,#cb4040)' : 'var(--color-muted-foreground)',
              borderBottom: tab === t.id ? '2px solid var(--color-primary,#cb4040)' : '2px solid transparent', marginBottom: -1 }}>
            {tr(t.fr, t.en)}
          </button>
        ))}
        {extraTabs.map((et) => (
          <button key={et.id} onClick={() => openExtraTab(et.id)}
            style={{ height: 38, padding: narrow ? '0 10px' : '0 16px', border: 0, background: 'transparent', cursor: 'pointer', fontSize: narrow ? 13 : 14, fontWeight: 600,
              color: tab === et.id ? 'var(--color-primary,#cb4040)' : 'var(--color-muted-foreground)',
              borderBottom: tab === et.id ? '2px solid var(--color-primary,#cb4040)' : '2px solid transparent', marginBottom: -1 }}>
            {tabLabelOf(et.label)}
          </button>
        ))}
      </div>

      {mode === 'react' && error && (
        <div style={{ ...card, padding: '12px 16px', color: '#b91c1c', fontSize: 13, borderColor: '#fca5a5' }}>
          <div>{error}</div>
          {/* Les erreurs ancrées sont déjà affichées SOUS leur champ → on ne répète ici que celles
              qui n'ont pas de champ correspondant dans l'UI (sinon elles seraient invisibles). */}
          {orphanErrors.length > 0 && (
            <ul style={{ margin: '8px 0 0', paddingLeft: 18 }}>
              {orphanErrors.map((f) => (
                <li key={f.key} style={{ marginTop: 2 }}>
                  <button onClick={() => setTab(f.tab)}
                    style={{ border: 0, background: 'transparent', padding: 0, color: 'inherit', font: 'inherit', fontWeight: 700, cursor: 'pointer', textDecoration: 'underline' }}>
                    {fieldLabel(f)}
                  </button>
                  {' — '}{f.messages.join(' ')}
                </li>
              ))}
            </ul>
          )}
        </div>
      )}

      {/* PROPRIÉTÉS */}
      {mode === 'react' && tab === 'props' && (
        <div style={{ ...card, padding: narrow ? 14 : 20, maxWidth: narrow ? '100%' : 760, display: 'flex', flexDirection: 'column', gap: 16 }}>
          <div style={{ display: 'grid', gridTemplateColumns: narrow ? '1fr' : '1fr 1fr', gap: 16 }}>
            <div>
              <label style={lbl}>{tr('Libellé du site', 'Site label')} *</label>
              <input style={{ ...input, ...(errsByKey['siteprop_site_label'] ? inputErr : null) }} value={label}
                onChange={(e) => { setLabel(e.target.value); clearFieldError('siteprop_site_label') }} />
              <FieldError messages={errsByKey['siteprop_site_label']} />
            </div>
            <div>
              <label style={lbl}>{tr('Nom du module', 'Module name')}</label>
              <input style={{ ...input, fontFamily: 'monospace', opacity: 0.7 }} value={data.site.name} disabled readOnly />
              <p style={hint}>{tr('Non modifiable après création.', 'Cannot be changed after creation.')}</p>
            </div>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: narrow ? '1fr' : '1fr 1fr', gap: 16 }}>
            <div>
              <label style={lbl}>{tr('Page d’accueil principale', 'Main home page')} *</label>
              <PagePicker value={mainPage.id} title={mainPage.title}
                onChange={(id, t) => { setMainPage({ id, title: t }); clearFieldError('siteprop_site_main_page_id') }} />
              <FieldError messages={errsByKey['siteprop_site_main_page_id']} />
            </div>
            <div>
              <label style={lbl}>{tr('Page 404', '404 page')} *</label>
              <PagePicker value={s404.id} title={s404.title}
                onChange={(id, t) => { setS404({ id, title: t }); clearFieldError('siteprop_s404_page_id') }} />
              <FieldError messages={errsByKey['siteprop_s404_page_id']} />
            </div>
          </div>
          {/* Drag & Drop mode : choisi UNIQUEMENT à la création (comme le legacy) → lecture seule ici. */}
          <div>
            <label style={lbl}>{tr('Mode Drag & Drop', 'Drag & Drop mode')}</label>
            <div style={{ display: 'flex', gap: 16, opacity: 0.65 }}>
              <label style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 14, cursor: 'not-allowed' }}>
                <input type="radio" name="dnd" checked={dndMode === ''} disabled readOnly />
                {tr('Standard', 'Standard')}
              </label>
              <label style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 14, cursor: 'not-allowed' }}>
                <input type="radio" name="dnd" checked={dndMode === 'bootstrap'} disabled readOnly />
                Bootstrap
              </label>
            </div>
            <p style={hint}>{tr('Défini à la création, non modifiable ensuite.', 'Set at creation, cannot be changed afterwards.')}</p>
          </div>

          {/* Pages d'accueil par langue (comme le legacy — pour chaque langue ACTIVE, cf. onglet Langues). */}
          <div>
            <label style={lbl}>{tr('Pages d’accueil par langue', 'Home pages per language')}</label>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
              {activeLangIds.length === 0 ? (
                <p style={hint}>{tr('Aucune langue active — activez-en dans l’onglet Langues.', 'No active language — enable one in the Languages tab.')}</p>
              ) : data.languages.filter((l) => activeLangIds.includes(l.id)).map((l) => (
                <div key={l.id} style={{ display: 'grid', gridTemplateColumns: narrow ? '1fr' : '220px 1fr', gap: 12, alignItems: narrow ? 'start' : 'center' }}>
                  <span style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 14 }}>
                    <Flag locale={l.locale} />
                    {l.name} <span style={{ color: 'var(--color-muted-foreground)', fontSize: 12 }}>({l.locale})</span>
                  </span>
                  <div>
                    <PagePicker value={homes[l.id]?.pageId ?? 0} title={homes[l.id]?.title}
                      placeholder={tr('— page d’accueil —', '— home page —')}
                      onChange={(id, t) => { setHome(l.id, id, t); clearFieldError(`${l.id}_shome_page_id`) }} />
                    <FieldError messages={errsByKey[`${l.id}_shome_page_id`]} />
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* DOMAINES */}
      {mode === 'react' && tab === 'domains' && (
        <div style={{ ...card, padding: narrow ? 14 : 20, maxWidth: narrow ? '100%' : 760, display: 'flex', flexDirection: 'column', gap: 16 }}>
          <p style={hint}>{tr('Un domaine par environnement. Le domaine de l’environnement courant est obligatoire et doit être unique.', 'One domain per environment. The current environment domain is required and must be unique.')}</p>
          {domains.map((d) => (
            <div key={d.env} style={{ display: 'grid', gridTemplateColumns: narrow ? '1fr' : '120px 110px 1fr', gap: 12, alignItems: narrow ? 'stretch' : 'end' }}>
              <div>
                <label style={lbl}>{tr('Environnement', 'Environment')}</label>
                <div style={{ ...input, display: 'flex', alignItems: 'center', fontWeight: 600 }}>
                  {d.env}{d.env === data.currentEnv && <span style={{ marginLeft: 6, fontSize: 11, color: 'var(--color-primary,#cb4040)' }}>●</span>}
                </div>
              </div>
              <div>
                <label style={lbl}>{tr('Schéma', 'Scheme')}</label>
                <select style={{ ...input, ...(errsByKey[`${d.env}_sdom_scheme`] ? inputErr : null) }} value={d.scheme}
                  onChange={(e) => { setDomain(d.env, { scheme: e.target.value }); clearFieldError(`${d.env}_sdom_scheme`) }}>
                  <option value="http">http</option>
                  <option value="https">https</option>
                </select>
                <FieldError messages={errsByKey[`${d.env}_sdom_scheme`]} />
              </div>
              <div>
                <label style={lbl}>{tr('Domaine', 'Domain')}{d.env === data.currentEnv ? ' *' : ''}</label>
                <input style={{ ...input, ...(errsByKey[`${d.env}_sdom_domain`] ? inputErr : null) }} value={d.domain}
                  onChange={(e) => { setDomain(d.env, { domain: e.target.value }); clearFieldError(`${d.env}_sdom_domain`) }}
                  placeholder="exemple.com" />
                <FieldError messages={errsByKey[`${d.env}_sdom_domain`]} />
              </div>
            </div>
          ))}
        </div>
      )}

      {/* LANGUES */}
      {mode === 'react' && tab === 'langs' && (
        <div style={{ ...card, padding: narrow ? 14 : 20, maxWidth: narrow ? '100%' : 760, display: 'flex', flexDirection: 'column', gap: 16 }}>
          <div>
            <label style={lbl}>{tr('Langues actives', 'Active languages')}</label>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
              {data.languages.map((l) => {
                const active = activeLangIds.includes(l.id)
                return (
                  <label key={l.id} style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 14, cursor: 'pointer' }}>
                    <input type="checkbox" checked={active} onChange={() => toggleLang(l.id)} />
                    <Flag locale={l.locale} />
                    {l.name} <span style={{ color: 'var(--color-muted-foreground)', fontSize: 12 }}>({l.locale})</span>
                  </label>
                )
              })}
            </div>
            {removedLangIds.length > 0 && (
              <p style={{ ...hint, color: '#b45309' }}>
                {tr('Les langues décochées et leurs données associées (config, accueil) seront supprimées.', 'Unchecked languages and their associated data (config, home) will be deleted.')}
              </p>
            )}
          </div>
          <div>
            <label style={lbl}>{tr('Comment souhaitez-vous refléter la langue dans les URLs du site ?', "How do you want to reflect the language in the site's URLs?")}</label>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
              <label style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 14, cursor: 'pointer' }}>
                <input type="radio" name="optlang" checked={optLangUrl === 1} onChange={() => setOptLangUrl(1)} />
                {tr('Je veux que la locale apparaisse après mon domaine (ex : www.monsite.com/fr/mapage)', 'I want the locale shown after my domain (ex: www.mysite.com/en/myurl)')}
              </label>
              <label style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 14, cursor: 'pointer' }}>
                <input type="radio" name="optlang" checked={optLangUrl === 2} onChange={() => setOptLangUrl(2)} />
                {tr('Je ne veux rien, l’URL de ma page sera construite uniquement sur le nom de la page', "I want nothing, my page url will be solely built on the page's name")}
              </label>
            </div>
          </div>
        </div>
      )}

      {/* CONFIG */}
      {mode === 'react' && tab === 'config' && (
        <div style={{ ...card, padding: narrow ? 14 : 20 }}>
          {!configData ? <span style={{ fontSize: 14, color: 'var(--color-muted-foreground)' }}>{tr('Chargement…', 'Loading…')}</span>
            : <ConfigTab data={configData} fields={configFields} setField={setConfigField} />}
        </div>
      )}

      {/* Onglets contribués par des modules (ex. « Scripts » via MelisCmsPageScriptEditor). Montés à
          la 1ʳᵉ ouverture puis gardés montés (état + save préservés) ; masqués quand un autre onglet
          est actif. Leur save est déclenché par le Save global (voir submit()). */}
      {mode === 'react' && extraTabs.map((et) => (
        activatedTabs.has(et.id) ? (
          <div key={et.id} style={{ display: tab === et.id ? 'block' : 'none' }}>
            <et.Component siteId={siteId} registerSave={registerSaveFor(et.id)} />
          </div>
        ) : null
      ))}

      {/* TRADUCTIONS (CRUD autonome) */}
      {mode === 'react' && tab === 'translations' && (
        <TranslationsTab siteId={siteId} langs={transLangs} />
      )}

      {/* MODULES */}
      {mode === 'react' && tab === 'modules' && (
        !modulesData ? <div style={{ ...card, padding: 20, fontSize: 14, color: 'var(--color-muted-foreground)' }}>{tr('Chargement…', 'Loading…')}</div>
          : <ModuleLoaderTab siteId={siteId} isAdmin={modulesData.isAdmin} modules={moduleList} setModules={setModuleList} />
      )}
    </div>
  )
}
