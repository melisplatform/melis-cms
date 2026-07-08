import { useEffect, useMemo, useState } from 'react'
import { fetchSite, saveSiteEdit, fetchSiteConfig, fetchSiteModules, type SiteEditData, type SiteConfigData, type SiteModulesData, type SiteModule } from './sites-api'
import { PagePicker } from './PagePicker'
import { ViewToggle, type ViewMode } from './ViewToggle'
import { ConfigTab, buildConfigFields } from './site-tabs/ConfigTab'
import { ModuleLoaderTab } from './site-tabs/ModuleLoaderTab'
import { TranslationsTab } from './site-tabs/TranslationsTab'
import { ScriptsTab } from './site-tabs/ScriptsTab'
import { fetchSiteScript, saveSiteScript } from './site-scripts-api'

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

// Ordre identique à l'édition legacy : Propriétés, Module Loading, Domaines, Langues, Config,
// Scripts (conditionnel — module MelisCmsPageScriptEditor), Traductions.
const TABS = [
  { id: 'props', fr: 'Propriétés', en: 'Properties' },
  { id: 'modules', fr: 'Chargement de modules', en: 'Module Loading' },
  { id: 'domains', fr: 'Domaines', en: 'Domains' },
  { id: 'langs', fr: 'Langues', en: 'Languages' },
  { id: 'config', fr: 'Config du site', en: 'Site Config' },
  { id: 'scripts', fr: 'Scripts', en: 'Scripts' },
  { id: 'translations', fr: 'Traductions', en: 'Translations' },
] as const
type TabId = (typeof TABS)[number]['id']

interface DomainState { id: number; env: string; scheme: string; domain: string }

interface Props {
  siteId: number
  onSaved: () => void
  /** Remonte le libellé du site au conteneur (pour le sous-onglet). */
  onLabel: (label: string) => void
}

export default function SiteEditor({ siteId, onSaved, onLabel }: Props) {
  const [data, setData] = useState<SiteEditData | null>(null)
  const [loadErr, setLoadErr] = useState<string | null>(null)
  const [tab, setTab] = useState<TabId>('props')
  // L'onglet Scripts n'apparaît que si le module MelisCmsPageScriptEditor est actif : on sonde son
  // API (route inexistante si le module est désactivé → 404/erreur → onglet masqué), même logique
  // que le legacy où l'onglet n'est injecté que si la config du module est chargée.
  const [scriptsActive, setScriptsActive] = useState(false)
  // État des scripts du site — levé ici (et non dans ScriptsTab) pour être persisté par le Save
  // GLOBAL de l'éditeur, comme le legacy (le save du site déclenche le listener de save des scripts).
  const [scriptId, setScriptId] = useState<number | null>(null)
  const [scriptHeadTop, setScriptHeadTop] = useState('')
  const [scriptHeadBottom, setScriptHeadBottom] = useState('')
  const [scriptBodyBottom, setScriptBodyBottom] = useState('')
  const [mode, setMode] = useState<ViewMode>('react')
  const [frameLoaded, setFrameLoaded] = useState(false)
  const [saving, setSaving] = useState(false)
  const [savedAt, setSavedAt] = useState(0)
  const [error, setError] = useState<string | null>(null)

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
    fetchSiteScript(siteId).then((r) => {
      setScriptsActive(true)
      setScriptId(r.script?.id ?? null)
      setScriptHeadTop(r.script?.headTop ?? '')
      setScriptHeadBottom(r.script?.headBottom ?? '')
      setScriptBodyBottom(r.script?.bodyBottom ?? '')
    }).catch(() => setScriptsActive(false))
    fetchSiteConfig(siteId).then((c) => { setConfigData(c); setConfigFields(buildConfigFields(c)) }).catch(() => null)
    fetchSiteModules(siteId).then((m) => { setModulesData(m); setModuleList(m.modules) }).catch(() => null)
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
    }).catch((e) => setLoadErr(String((e as Error)?.message ?? e)))
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
    setSaving(true); setError(null)
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
        // Persiste aussi les scripts du site (onglet Scripts) dans le même Save global, comme le legacy.
        if (scriptsActive) {
          await saveSiteScript({ siteId, id: scriptId, headTop: scriptHeadTop, headBottom: scriptHeadBottom, bodyBottom: scriptBodyBottom })
          // récupère l'id créé à la 1ʳᵉ sauvegarde (évite un doublon au save suivant)
          const sc = await fetchSiteScript(siteId).catch(() => null)
          if (sc) setScriptId(sc.script?.id ?? null)
        }
        onLabel(label.trim() || `Site #${siteId}`)
        setSavedAt(Date.now())
        window.postMessage({ __melisNotif: true, kind: 'ok', title: tr('Sites', 'Sites'), message: tr('Enregistré ✓', 'Saved ✓') }, '*')
        onSaved()
      } else {
        setError(res.textMessage || tr('Échec de l’enregistrement.', 'Save failed.'))
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
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, padding: 24, height: '100%', boxSizing: 'border-box', overflow: 'auto' }}>
      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 }}>
        <div style={{ minWidth: 0 }}>
          <h1 style={{ fontSize: 20, fontWeight: 700, margin: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{tr('Éditer le site', 'Edit site')} — {data.site.label}</h1>
          <p style={{ fontSize: 14, color: 'var(--color-muted-foreground)', margin: '2px 0 0', fontFamily: 'monospace' }}>{data.site.name} · #{data.site.id}</p>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          <ViewToggle mode={mode} onChange={(m) => { setMode(m); if (m === 'iframe') setFrameLoaded(true) }} />
          {savedAt > 0 && mode === 'react' && <span style={{ fontSize: 12, color: '#15803d' }}>✓ {tr('Enregistré', 'Saved')}</span>}
          {can('edit') && mode === 'react' && (
            <button style={{ ...btnPrimary, opacity: saving ? 0.6 : 1 }} disabled={saving} onClick={submit}>
              {saving ? tr('Enregistrement…', 'Saving…') : tr('Enregistrer', 'Save')}
            </button>
          )}
        </div>
      </div>

      {/* Vue « Old » : ÉDITION legacy du site (zone meliscms_tool_sites_edit_site + siteId) */}
      {frameLoaded && (
        <div style={{ ...card, display: mode === 'iframe' ? 'flex' : 'none', flex: 1, minHeight: 480, overflow: 'hidden' }}>
          <iframe src={`/melis/react-tool-page?key=meliscms_tool_sites_edit_site&siteId=${siteId}`}
            style={{ flex: 1, width: '100%', border: 0 }} title="Site edit — Vue Melis"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-modals" />
        </div>
      )}

      {/* Tabs */}
      <div style={{ display: mode === 'react' ? 'flex' : 'none', gap: 4, borderBottom: '1px solid var(--color-border,#e5e7eb)' }}>
        {TABS.filter((t) => t.id !== 'scripts' || scriptsActive).map((t) => (
          <button key={t.id} onClick={() => setTab(t.id)}
            style={{ height: 38, padding: '0 16px', border: 0, background: 'transparent', cursor: 'pointer', fontSize: 14, fontWeight: 600,
              color: tab === t.id ? 'var(--color-primary,#cb4040)' : 'var(--color-muted-foreground)',
              borderBottom: tab === t.id ? '2px solid var(--color-primary,#cb4040)' : '2px solid transparent', marginBottom: -1 }}>
            {tr(t.fr, t.en)}
          </button>
        ))}
      </div>

      {mode === 'react' && error && <div style={{ ...card, padding: '12px 16px', color: '#b91c1c', fontSize: 13, borderColor: '#fca5a5' }}>{error}</div>}

      {/* PROPRIÉTÉS */}
      {mode === 'react' && tab === 'props' && (
        <div style={{ ...card, padding: 20, maxWidth: 760, display: 'flex', flexDirection: 'column', gap: 16 }}>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
            <div>
              <label style={lbl}>{tr('Libellé du site', 'Site label')} *</label>
              <input style={input} value={label} onChange={(e) => setLabel(e.target.value)} />
            </div>
            <div>
              <label style={lbl}>{tr('Nom du module', 'Module name')}</label>
              <input style={{ ...input, fontFamily: 'monospace', opacity: 0.7 }} value={data.site.name} disabled readOnly />
              <p style={hint}>{tr('Non modifiable après création.', 'Cannot be changed after creation.')}</p>
            </div>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
            <div>
              <label style={lbl}>{tr('Page d’accueil principale', 'Main home page')} *</label>
              <PagePicker value={mainPage.id} title={mainPage.title} onChange={(id, t) => setMainPage({ id, title: t })} />
            </div>
            <div>
              <label style={lbl}>{tr('Page 404', '404 page')} *</label>
              <PagePicker value={s404.id} title={s404.title} onChange={(id, t) => setS404({ id, title: t })} />
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
                <div key={l.id} style={{ display: 'grid', gridTemplateColumns: '220px 1fr', gap: 12, alignItems: 'center' }}>
                  <span style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 14 }}>
                    <Flag locale={l.locale} />
                    {l.name} <span style={{ color: 'var(--color-muted-foreground)', fontSize: 12 }}>({l.locale})</span>
                  </span>
                  <PagePicker value={homes[l.id]?.pageId ?? 0} title={homes[l.id]?.title}
                    placeholder={tr('— page d’accueil —', '— home page —')}
                    onChange={(id, t) => setHome(l.id, id, t)} />
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* DOMAINES */}
      {mode === 'react' && tab === 'domains' && (
        <div style={{ ...card, padding: 20, maxWidth: 760, display: 'flex', flexDirection: 'column', gap: 16 }}>
          <p style={hint}>{tr('Un domaine par environnement. Le domaine de l’environnement courant est obligatoire et doit être unique.', 'One domain per environment. The current environment domain is required and must be unique.')}</p>
          {domains.map((d) => (
            <div key={d.env} style={{ display: 'grid', gridTemplateColumns: '120px 110px 1fr', gap: 12, alignItems: 'end' }}>
              <div>
                <label style={lbl}>{tr('Environnement', 'Environment')}</label>
                <div style={{ ...input, display: 'flex', alignItems: 'center', fontWeight: 600 }}>
                  {d.env}{d.env === data.currentEnv && <span style={{ marginLeft: 6, fontSize: 11, color: 'var(--color-primary,#cb4040)' }}>●</span>}
                </div>
              </div>
              <div>
                <label style={lbl}>{tr('Schéma', 'Scheme')}</label>
                <select style={input} value={d.scheme} onChange={(e) => setDomain(d.env, { scheme: e.target.value })}>
                  <option value="http">http</option>
                  <option value="https">https</option>
                </select>
              </div>
              <div>
                <label style={lbl}>{tr('Domaine', 'Domain')}{d.env === data.currentEnv ? ' *' : ''}</label>
                <input style={input} value={d.domain} onChange={(e) => setDomain(d.env, { domain: e.target.value })} placeholder="exemple.com" />
              </div>
            </div>
          ))}
        </div>
      )}

      {/* LANGUES */}
      {mode === 'react' && tab === 'langs' && (
        <div style={{ ...card, padding: 20, maxWidth: 760, display: 'flex', flexDirection: 'column', gap: 16 }}>
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
        <div style={{ ...card, padding: 20 }}>
          {!configData ? <span style={{ fontSize: 14, color: 'var(--color-muted-foreground)' }}>{tr('Chargement…', 'Loading…')}</span>
            : <ConfigTab data={configData} fields={configFields} setField={setConfigField} />}
        </div>
      )}

      {/* SCRIPTS (onglet natif — module MelisCmsPageScriptEditor). Champs contrôlés + persistés par
          le Save global ; les exceptions restent autonomes (ajout/suppression immédiats). */}
      {mode === 'react' && tab === 'scripts' && scriptsActive && (
        <ScriptsTab
          siteId={siteId}
          headTop={scriptHeadTop} onHeadTop={setScriptHeadTop}
          headBottom={scriptHeadBottom} onHeadBottom={setScriptHeadBottom}
          bodyBottom={scriptBodyBottom} onBodyBottom={setScriptBodyBottom}
        />
      )}

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
