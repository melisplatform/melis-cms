<?php

namespace MelisCms\Controller;

use MelisReactApi\Controller\CapabilityGuardTrait;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use MelisCore\Controller\MelisAbstractActionController;

/**
 * API REST pour l'outil "Sites" de MelisCms (liste + métadonnées du tunnel de création).
 *
 *   GET /melis/react-api/cms-sites        → { items: [{id,name,label,languages,moduleFound}], total }
 *   GET /melis/react-api/cms-sites/meta   → { languages: [{id,locale,name}] } (pour le tunnel React)
 *
 * La SUPPRESSION et la CRÉATION réutilisent les endpoints legacy éprouvés
 * (`/melis/MelisCms/Sites/deleteSite`, `/melis/MelisCms/Sites/createNewSite`) — toute la logique
 * métier (scaffolding module, pages, domaines, langues, transactions) reste côté Melis.
 */
class MelisReactApiCmsSitesController extends MelisAbstractActionController
{
    use CapabilityGuardTrait;

    private const MELIS_KEY = 'meliscms_tool_sites';

    // ─── GET /cms-sites ───────────────────────────────────────────────────────

    public function listAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $siteTable = $this->getServiceManager()->get('MelisEngineTableSite');
            $search    = (string) $this->params()->fromQuery('search', '');
            $rows      = $siteTable->getSitesData($search, ['site_name', 'site_label'], 'site_name', 'asc', null, null)->toArray();

            // Carte des langues CMS disponibles (lang_cms_id → {id, locale, name}). Le slang_lang_id
            // d'un site référence ce lang_cms_id (cf. getAction).
            $langMap = [];
            try {
                foreach ((array) $this->getServiceManager()->get('MelisEngineLang')->getAvailableLanguages() as $l) {
                    $l  = (array) $l;
                    $id = (int) ($l['lang_cms_id'] ?? $l['lang_id'] ?? 0);
                    $langMap[$id] = [
                        'id'     => $id,
                        'locale' => (string) ($l['lang_cms_locale'] ?? $l['lang_locale'] ?? ''),
                        'name'   => (string) ($l['lang_cms_name'] ?? $l['lang_name'] ?? ''),
                    ];
                }
            } catch (\Throwable) {}

            $siteLangsTable = $this->getServiceManager()->get('MelisEngineTableCmsSiteLangs');
            $cmsSiteSrv     = $this->getServiceManager()->get('MelisCmsSiteService');

            $items = [];
            foreach ($rows as $r) {
                $siteId = (int) ($r['site_id'] ?? 0);
                // Langues ACTIVES du site (drapeaux en front).
                $languages = [];
                try {
                    foreach ($siteLangsTable->getSiteLangs(null, $siteId, null, true)->toArray() as $sl) {
                        $lid = (int) $sl['slang_lang_id'];
                        if (isset($langMap[$lid])) { $languages[] = $langMap[$lid]; }
                    }
                } catch (\Throwable) {}
                // Module trouvé sur disque ? (désactive le bouton "Minifier les assets" — même
                // check que SitesController::renderToolSitesContentAction() / DT_RowAttr[data-mod-found]).
                $moduleFound = false;
                try {
                    $moduleFound = file_exists($cmsSiteSrv->getModulePath((string) ($r['site_name'] ?? '')));
                } catch (\Throwable) {}
                $items[] = [
                    'id'          => $siteId,
                    'name'        => (string) ($r['site_name'] ?? ''),
                    'label'       => (string) ($r['site_label'] ?? ''),
                    'languages'   => $languages, // tableau [{id, locale, name}]
                    'moduleFound' => $moduleFound,
                ];
            }

            return $this->jsonResponse(['success' => true, 'data' => ['items' => $items, 'total' => count($items)]]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── GET /cms-sites/meta ──────────────────────────────────────────────────

    public function metaAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            // Mêmes langues que le tunnel legacy (langues CMS) : MelisEngineLang::getAvailableLanguages().
            $languages = [];
            try {
                $list = (array) $this->getServiceManager()->get('MelisEngineLang')->getAvailableLanguages();
                foreach ($list as $l) {
                    $l = (array) $l;
                    $languages[] = [
                        'id'     => (int) ($l['lang_cms_id'] ?? $l['lang_id'] ?? 0),
                        'locale' => (string) ($l['lang_cms_locale'] ?? $l['lang_locale'] ?? ''),
                        'name'   => (string) ($l['lang_cms_name'] ?? $l['lang_name'] ?? ''),
                    ];
                }
            } catch (\Throwable) {}

            // Modules existants (site_name) — pour l'étape « utiliser un module existant » du wizard.
            $modules = [];
            try {
                $rows = $this->getServiceManager()->get('MelisEngineTableSite')->fetchAll();
                foreach ($rows as $r) {
                    $r = (array) $r;
                    $n = (string) ($r['site_name'] ?? '');
                    if ($n !== '') { $modules[$n] = $n; }
                }
            } catch (\Throwable) {}
            $modules = array_values($modules);
            sort($modules);

            return $this->jsonResponse(['success' => true, 'data' => [
                'languages'     => $languages,
                'modules'       => $modules,
                'defaultDomain' => (string) ($_SERVER['HTTP_HOST'] ?? ''),
                'platform'      => (string) (getenv('MELIS_PLATFORM') ?: ''),
            ]]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── GET /cms-sites/:id ───────────────────────────────────────────────────

    /**
     * Données d'édition complètes d'un site (onglets cœur : Propriétés, Domaines, Langues).
     *
     * La SAUVEGARDE n'est pas ici : elle réutilise l'endpoint legacy transactionnel
     * `POST /melis/MelisCms/Sites/saveSite?siteId=X` (toute la logique métier — validation,
     * transaction, cache, régénération d'URL — reste côté Melis). Cette action ne fait que
     * SHAPER en JSON les données nécessaires au formulaire React.
     */
    public function getAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $siteId = (int) $this->params()->fromRoute('id', 0);
            if (!$siteId) { return $this->jsonResponse(['success' => false, 'error' => 'Invalid site id'], 422); }

            $sm = $this->getServiceManager();

            // Propriétés du site (+ page 404).
            $siteRow = $sm->get('MelisEngineTableSite')->getEntryById($siteId)->current();
            if (empty($siteRow)) { return $this->jsonResponse(['success' => false, 'error' => 'Site not found'], 404); }
            $s404 = $sm->get('MelisEngineTableSite404')->getEntryByField('s404_site_id', $siteId)->current();

            $site = [
                'id'            => (int) $siteRow->site_id,
                'name'          => (string) $siteRow->site_name,
                'label'         => (string) $siteRow->site_label,
                'mainPageId'    => (int) $siteRow->site_main_page_id,
                'dndRenderMode' => (string) ($siteRow->site_dnd_render_mode ?? ''),
                'optLangUrl'    => (int) $siteRow->site_opt_lang_url,
                's404PageId'    => !empty($s404) ? (int) $s404->s404_page_id : 0,
            ];

            // Langues CMS disponibles + drapeau actif sur le site.
            $available = [];
            try {
                foreach ((array) $sm->get('MelisEngineLang')->getAvailableLanguages() as $l) {
                    $l = (array) $l;
                    $available[] = [
                        'id'     => (int) ($l['lang_cms_id'] ?? $l['lang_id'] ?? 0),
                        'locale' => (string) ($l['lang_cms_locale'] ?? $l['lang_locale'] ?? ''),
                        'name'   => (string) ($l['lang_cms_name'] ?? $l['lang_name'] ?? ''),
                    ];
                }
            } catch (\Throwable) {}

            $activeLangIds = [];
            foreach ($sm->get('MelisEngineTableCmsSiteLangs')->getSiteLangs(null, $siteId, null, true)->toArray() as $sl) {
                $activeLangIds[] = (int) $sl['slang_lang_id'];
            }
            $languages = array_map(static function ($l) use ($activeLangIds) {
                $l['active'] = in_array($l['id'], $activeLangIds, true);
                return $l;
            }, $available);

            // Pages d'accueil par langue.
            $homepages = [];
            foreach ($sm->get('MelisCmsSitesPropertiesService')->getLangHomepages($siteId) as $h) {
                $h = (array) $h;
                $homepages[] = [
                    'shomeId' => (int) ($h['shome_id'] ?? 0),
                    'langId'  => (int) ($h['shome_lang_id'] ?? 0),
                    'pageId'  => (int) ($h['shome_page_id'] ?? 0),
                ];
            }

            // Domaines par environnement.
            $domains = [];
            foreach ($sm->get('MelisCmsSitesDomainsService')->getDomainsBySiteId($siteId) as $d) {
                $d = (array) $d;
                $domains[] = [
                    'id'     => (int) ($d['sdom_id'] ?? 0),
                    'env'    => (string) ($d['sdom_env'] ?? ''),
                    'scheme' => (string) ($d['sdom_scheme'] ?? 'http'),
                    'domain' => (string) ($d['sdom_domain'] ?? ''),
                ];
            }

            // Environnements de la plateforme (plf_name).
            $environments = [];
            foreach ((array) $sm->get('MelisCmsSitesDomainsService')->getEnvironments() as $e) {
                $e = (array) $e;
                if (!empty($e['plf_name'])) { $environments[] = (string) $e['plf_name']; }
            }
            $currentEnv = getenv('MELIS_PLATFORM') ?: 'development';

            // Titres des pages référencées (404, accueil principal, homepages) pour l'affichage.
            $pageIds = array_filter(array_unique(array_merge(
                [$site['mainPageId'], $site['s404PageId']],
                array_map(static fn ($h) => $h['pageId'], $homepages)
            )));
            $pageTitles = $this->resolvePageTitles($pageIds);

            return $this->jsonResponse(['success' => true, 'data' => [
                'site'         => $site,
                'languages'    => $languages,
                'homepages'    => $homepages,
                'domains'      => $domains,
                'environments' => $environments,
                'currentEnv'   => $currentEnv,
                'pageTitles'   => $pageTitles,
            ]]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── GET /cms-sites/:id/config ────────────────────────────────────────────

    /**
     * Onglet "Config" : configuration fusionnée (fichier + DB) par langue + section "Général"
     * (allSites). Reproduit ce que rend SitesConfigController (merge via MelisSiteConfigService).
     * La sauvegarde réutilise le legacy saveSite (champs `gen_sconf_*` / `<langId>_sconf_*`).
     */
    public function configAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $siteId = (int) $this->params()->fromRoute('id', 0);
            if (!$siteId) { return $this->jsonResponse(['success' => false, 'error' => 'Invalid site id'], 422); }

            $sm = $this->getServiceManager();
            $siteRow = $sm->get('MelisEngineTableSite')->getEntryById($siteId)->current();
            if (empty($siteRow)) { return $this->jsonResponse(['success' => false, 'error' => 'Site not found'], 404); }
            $siteName = (string) $siteRow->site_name;

            $merged = (array) $sm->get('MelisSiteConfigService')->getSiteConfig($siteId, true);

            // sconf_id par langue (-1 = général).
            $dbConfIds = [];
            foreach ($sm->get('MelisEngineTableCmsSiteConfig')->getEntryByField('sconf_site_id', $siteId)->toArray() as $c) {
                $dbConfIds[(int) $c['sconf_lang_id']] = (int) $c['sconf_id'];
            }

            $general = [
                'sconfId' => $dbConfIds[-1] ?? 0,
                'items'   => $this->configItems($merged['site'][$siteName]['allSites'] ?? []),
            ];

            $perLang = [];
            foreach ($sm->get('MelisEngineTableCmsSiteLangs')->getSiteLangs(null, $siteId, null, true)->toArray() as $sl) {
                $langId = (int) $sl['slang_lang_id'];
                $locale = (string) ($sl['lang_cms_locale'] ?? '');
                $perLang[] = [
                    'langId'  => $langId,
                    'locale'  => $locale,
                    'name'    => (string) ($sl['lang_cms_name'] ?? $locale),
                    'sconfId' => $dbConfIds[$langId] ?? 0,
                    'items'   => $this->configItems($merged['site'][$siteName][$siteId][$locale] ?? []),
                ];
            }

            return $this->jsonResponse(['success' => true, 'data' => ['general' => $general, 'perLang' => $perLang]]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /** Transforme une map de config en items éditables : scalaires + tableaux à 1 niveau. */
    private function configItems(array $config): array
    {
        $items = [];
        foreach ($config as $key => $val) {
            if (is_array($val)) {
                $entries = [];
                foreach ($val as $k => $v) {
                    if (!is_array($v)) { $entries[] = ['key' => (string) $k, 'value' => (string) $v, 'isInt' => is_int($k)]; }
                }
                $items[] = ['key' => (string) $key, 'type' => 'array', 'entries' => $entries];
            } else {
                $items[] = ['key' => (string) $key, 'type' => 'scalar', 'value' => (string) $val];
            }
        }
        return $items;
    }

    // ─── GET /cms-sites/:id/modules ───────────────────────────────────────────

    /**
     * Onglet "Module Loader" : modules disponibles + état (actif/inactif) pour ce site, version,
     * et le drapeau isAdmin (la sauvegarde des modules est réservée aux admins côté legacy).
     */
    public function modulesAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('list')) { return $denyCap; }

        try {
            $siteId = (int) $this->params()->fromRoute('id', 0);
            if (!$siteId) { return $this->jsonResponse(['success' => false, 'error' => 'Invalid site id'], 422); }

            $sm = $this->getServiceManager();
            $moduleSvc = $sm->get('ModulesService');
            $state    = (array) $sm->get('MelisCmsSiteModuleLoadService')->getModules($siteId); // name => 0|1 (ordre = module.load.php)
            $versions = (array) $moduleSvc->getModulesAndVersions();

            // Graphe de dépendances (forward) limité aux modules activables du site — comme l'outil
            // Modules BO (MelisReactApiModulesController) : requires + dependents pour la cascade React.
            $toggleable  = array_keys($state);
            $requiresMap = [];
            foreach ($toggleable as $module) {
                $deps = [];
                try { $deps = (array) $moduleSvc->getDependencies($module); } catch (\Throwable) {}
                $requiresMap[$module] = array_values(array_filter($deps, static fn($d) => in_array($d, $toggleable, true)));
            }
            $dependentsMap = array_fill_keys($toggleable, []);
            foreach ($requiresMap as $module => $deps) {
                foreach ($deps as $dep) { $dependentsMap[$dep][] = $module; }
            }

            $modules = [];
            foreach ($state as $name => $active) {
                $modules[] = [
                    'name'       => (string) $name,
                    'active'     => (bool) $active,
                    'version'    => (string) ($versions[$name]['version'] ?? ''),
                    'package'    => (string) ($versions[$name]['package'] ?? ''),
                    'requires'   => $requiresMap[$name] ?? [],
                    'dependents' => $dependentsMap[$name] ?? [],
                ];
            }

            $auth = $sm->get('MelisCoreAuth')->getStorage()->read();
            $isAdmin = !empty($auth->usr_admin);

            return $this->jsonResponse(['success' => true, 'data' => ['modules' => $modules, 'isAdmin' => $isAdmin]]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /** id de page → "id - nom" (depuis melis_cms_page_saved). */
    private function resolvePageTitles(array $pageIds): array
    {
        $titles = [];
        if (empty($pageIds)) { return $titles; }
        try {
            $db = $this->getServiceManager()->get('Laminas\Db\Adapter\AdapterInterface');
            $in = implode(',', array_map('intval', $pageIds));
            // Une page peut n'exister qu'en "published" (live) et pas en "saved" (brouillon) : on
            // lit les deux, "saved" prioritaire.
            foreach (['melis_cms_page_published', 'melis_cms_page_saved'] as $table) {
                $rows = $db->query("SELECT page_id, page_name FROM $table WHERE page_id IN ($in)", []);
                foreach ($rows as $r) {
                    $titles[(string) $r['page_id']] = $r['page_id'] . ' - ' . $r['page_name'];
                }
            }
        } catch (\Throwable) {}
        return $titles;
    }

    // ─── POST /cms-sites/create ───────────────────────────────────────────────

    /**
     * Création de site — réutilise MelisCmsSiteService::saveSite() (scaffolding module, pages,
     * domaines, langues, traductions, en transaction) en reproduisant le pré/post-traitement de
     * SitesController::createNewSiteAction (generateModuleNameCase, defaults domaine, unicité,
     * suppression du cache des chemins de modules).
     *
     * Body JSON : { name, label?, languages:[{id,locale}], domains:{locale|single: domain},
     *               urlSetting?(1|2|3), createModule?(bool), isNewSite?(bool) }
     */
    public function createAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) { return $deny; }
        if ($denyCap = $this->denyUnlessCan('create')) { return $denyCap; }

        try {
            if (!$this->getRequest()->isPost()) {
                return $this->jsonResponse(['success' => false, 'error' => 'POST required'], 405);
            }
            $body      = json_decode($this->getRequest()->getContent(), true) ?? [];
            $name      = trim((string) ($body['name'] ?? ''));
            $label     = trim((string) ($body['label'] ?? ''));
            $languages = is_array($body['languages'] ?? null) ? $body['languages'] : [];
            $domains   = is_array($body['domains'] ?? null) ? $body['domains'] : [];
            $urlSetting   = (int) ($body['urlSetting'] ?? 1) ?: 1;
            // isNewSite = créer un NOUVEAU module ; sinon on rattache le site à un module EXISTANT.
            $isNewSite    = array_key_exists('isNewSite', $body) ? (bool) $body['isNewSite'] : true;
            // createFile = 6e arg de saveSite (créer dossiers/fichiers du module). Rétro-compat `createModule`.
            $createFile   = array_key_exists('createFile', $body) ? (bool) $body['createFile'] : (bool) ($body['createModule'] ?? false);
            $dndRenderMode  = (bool) ($body['dndRenderMode'] ?? false);
            $existingModule = trim((string) ($body['existingModuleName'] ?? ''));

            // Nom du module : module existant choisi (rattachement) sinon nom saisi (nouveau module).
            $moduleSource = (!$isNewSite && $existingModule !== '') ? $existingModule : $name;
            if ($moduleSource === '') { return $this->jsonResponse(['success' => false, 'error' => 'Site name required'], 422); }
            if (empty($languages))    { return $this->jsonResponse(['success' => false, 'error' => 'At least one language required'], 422); }

            $svc       = $this->getServiceManager()->get('MelisCmsSiteService');
            $siteName  = $svc->generateModuleNameCase($moduleSource);
            $siteLabel = $label !== '' ? $label : $siteName;
            $siteData  = ['site_name' => $siteName, 'site_label' => $siteLabel];
            // DnD (Drag & Drop) render mode : 'bootstrap' si activé, comme le contrôleur legacy.
            if ($dndRenderMode) { $siteData['site_dnd_render_mode'] = 'bootstrap'; }

            // Unicité du nom de module (comme le contrôleur legacy).
            if ($isNewSite) {
                $existing = $this->getServiceManager()->get('MelisEngineTableSite')->getEntryByField('site_name', $siteName)->current();
                if (!empty($existing)) {
                    return $this->jsonResponse(['success' => false, 'error' => 'Module name already exists'], 409);
                }
            }

            // siteLanguages = { locale => langId } ; domainData keyé par locale (defaults env/scheme).
            $env = getenv('MELIS_PLATFORM') ?: 'development';
            $siteLanguages = [];
            $domainData    = [];
            foreach ($languages as $lang) {
                $locale = (string) ($lang['locale'] ?? '');
                $langId = (int) ($lang['id'] ?? 0);
                if ($locale === '' || !$langId) { continue; }
                $siteLanguages[$locale] = $langId;
                $dom = trim((string) ($domains[$locale] ?? $domains['single'] ?? ''));
                $domainData[$locale] = ['sdom_domain' => $dom, 'sdom_env' => $env, 'sdom_scheme' => 'http'];
            }
            if (empty($siteLanguages)) {
                return $this->jsonResponse(['success' => false, 'error' => 'Invalid languages'], 422);
            }
            $siteLanguages['sites_url_setting'] = $urlSetting;

            $result = $svc->saveSite($siteData, $domainData, $siteLanguages, [], $siteName, $createFile, $isNewSite);
            if (empty($result['success'])) {
                // saveSite renvoie une CLÉ de traduction (ex. tr_melis_cms_sites_tool_add_create_site_unknown_error)
                // → on la traduit ici pour que React affiche un message lisible plutôt que la clé brute.
                $msgKey = $result['message'] ?? 'tr_melis_cms_sites_tool_add_create_site_unknown_error';
                $translator = $this->getServiceManager()->get('translator');
                $error = $translator->translate($msgKey);
                if ($error === $msgKey) { $error = $translator->translate('tr_melis_cms_sites_tool_add_create_site_unknown_error'); }
                return $this->jsonResponse(['success' => false, 'error' => $error], 500);
            }

            // Invalide le cache des chemins de modules (regenerateModulesPath legacy).
            @unlink($_SERVER['DOCUMENT_ROOT'] . '/../config/melis.modules.path.php');

            return $this->jsonResponse(['success' => true, 'data' => ['siteIds' => $result['site_ids'] ?? [], 'siteName' => $siteName, 'siteLabel' => $siteLabel]]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function isAuthenticated(): bool
    {
        return $this->getServiceManager()->get('MelisCoreAuth')->hasIdentity();
    }

    private function denyUnlessAccess(): ?HttpResponse
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['success' => false, 'error' => 'Unauthenticated'], 401);
        }
        try {
            if (!$this->getServiceManager()->get('MelisCoreRights')->canAccess(self::MELIS_KEY)) {
                return $this->jsonResponse(['success' => false, 'error' => 'Forbidden'], 403);
            }
        } catch (\Throwable) {}
        return null;
    }

    private function jsonResponse(array $data, int $status = 200): HttpResponse
    {
        /** @var HttpResponse $response */
        $response = $this->getResponse();
        $response->setStatusCode($status);
        $response->getHeaders()->addHeaders([
            'Content-Type'           => 'application/json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setContent(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response;
    }

    private function errorResponse(\Throwable $e, int $status = 500): HttpResponse
    {
        return $this->jsonResponse([
            'success' => false,
            'error'   => $e->getMessage(),
            'file'    => basename($e->getFile()) . ':' . $e->getLine(),
        ], $status);
    }
}
