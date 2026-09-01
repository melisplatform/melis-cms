<?php

namespace MelisCms\PageEditor\Controller;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use MelisCore\Controller\MelisAbstractActionController;

/**
 * EditionPaletteController — the plugin PALETTE for the React page editor
 * (branch evo/page-edition-react). Feeds the "+" add-a-plugin picker.
 *
 * Lists every ADDABLE plugin the same way the legacy accordion does
 * (`FrontPluginsController::renderPluginsMenuContentAction`): straight from the merged
 * config `plugins[$module][plugins][$name]['melis']`. That config is already merged from
 * the ACTIVE modules only (loaded modules' config is all Laminas merged), so iterating it
 * yields exactly the active plugins — no separate activation table exists. We keep the
 * legacy filter: skip `MelisFrontDragDropZonePlugin` (it IS the zone), and require a
 * `melis.name` (a user-facing, addable plugin).
 *
 * Route: GET /melis/react-api/cms-page/edition/plugins  → JSON { data: [ {module,name,title,…} ] }
 */
class EditionPaletteController extends MelisAbstractActionController
{
    use ReactApiPageGuardTrait;

    private const MELIS_KEY = 'meliscms_page';

    /** Plugins that are never offered in the palette (structural / internal). */
    private const EXCLUDE = ['MelisFrontDragDropZonePlugin'];

    public function pluginsAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) {
            return $deny;
        }

        try {
            $sm = $this->getServiceManager();
            $config = $sm->get('config');
            $translator = $sm->get('translator');
            $tr = static function ($k) use ($translator): string {
                $k = (string) $k;
                if ($k === '') {
                    return '';
                }
                // Melis convention: a leading "\" marks a translatable string — strip it before lookup.
                if ($k[0] === '\\') $k = substr($k, 1);
                try { return (string) $translator->translate($k); } catch (\Throwable) { return $k; }
            };

            // Section ORDER — fallBacksection (offline default) + the custom sections, minus MelisCore,
            // exactly like organizedPluginsBySection() (which always uses fallBacksection: the packagist
            // category call is commented out upstream). The per-plugin section is resolved below.
            $sectionOrder = ['MelisCms', 'MelisMarketing', 'MelisCommerce', 'MelisSites', 'Others', 'CustomProjects'];
            try {
                $fb = $sm->get('MelisCoreConfig')->getItem('/meliscore/datas/fallBacksection');
                if (is_array($fb) && $fb) {
                    $merged = array_merge($fb, ['MelisCommerce', 'Others', 'CustomProjects']);
                    $sectionOrder = array_values(array_filter(array_unique($merged), static fn($s) => $s !== 'MelisCore'));
                }
            } catch (\Throwable) {
            }

            // Per-MODULE marketplace section (public modules) — OVERRIDES the per-plugin `melis.section`,
            // exactly like organizedPluginsBySection(). Reachable marketplace → melisfront=MelisCms, so
            // e.g. the GDPR plugins (module melisfront, no own section) land under MelisCms, not Others.
            $publicModules = [];
            try {
                $publicModules = (array) $sm->get('MelisCorePluginsService')->getMelisPublicModules(true);
            } catch (\Throwable) {
            }

            // SITE SCOPING: a plugin can only render on the FRONT if its module is loaded for the page's
            // site, so the palette must only offer those. `allowedSiteModules()` = always-loaded core/front
            // modules + the modules the site enables in its own config/module.load.php. Null (no page/site
            // context) → no filter. NB the legacy palette never scoped by site (harmless on a single-site
            // demo where every module is loaded, but on a fresh site it offered plugins that can't render).
            $idPage = (int) $this->params()->fromQuery('idPage', 0);
            $allowedModules = $this->allowedSiteModules($sm, $idPage);

            // Build the tree: sectionKey → moduleKey → groupId → {id,title,plugins[]}.
            $tree = [];
            $moduleLabels = [];
            foreach ((array) ($config['plugins'] ?? []) as $module => $modConf) {
                if ($module === 'MelisMiniTemplate') {
                    continue; // per-site dynamic tree — handled separately (mini-templates)
                }
                if ($allowedModules !== null && !isset($allowedModules[strtolower((string) $module)])) {
                    continue; // module not loaded for this site → its plugins can't render on the front
                }
                foreach ((array) ($modConf['plugins'] ?? []) as $name => $pconf) {
                    if (in_array($name, self::EXCLUDE, true)) {
                        continue;
                    }
                    $melis = $pconf['melis'] ?? null;
                    if (!is_array($melis) || empty($melis['name'])) {
                        continue; // not a user-facing, addable plugin
                    }
                    $sectionKey = (string) ($publicModules[$module]['section'] ?? ($melis['section'] ?? 'Others'));
                    if ($sectionKey === '' || !in_array($sectionKey, $sectionOrder, true)) {
                        $sectionKey = 'Others';
                    }
                    $gid    = (string) ($melis['subcategory']['id'] ?? '');
                    $gtitle = $tr($melis['subcategory']['title'] ?? '');

                    $tree[$sectionKey][$module]['groups'][$gid]['id']    = $gid;
                    $tree[$sectionKey][$module]['groups'][$gid]['title'] = $gtitle;
                    $tree[$sectionKey][$module]['groups'][$gid]['plugins'][] = [
                        'module'      => (string) $module,
                        'name'        => (string) $name,
                        'title'       => $tr($melis['name']),
                        'description' => $tr($melis['description'] ?? ''),
                        'thumbnail'   => (string) ($melis['thumbnail'] ?? ''),
                        'type'        => (string) ($pconf['front']['type'] ?? ''),
                    ];
                    if (!isset($moduleLabels[$module])) {
                        $lbl = $tr('tr_PluginSection_' . $module);
                        if ($lbl === 'tr_PluginSection_' . $module || $lbl === '') {
                            $lbl = $this->prettyModule($module);
                        }
                        $moduleLabels[$module] = $lbl;
                    }
                }
            }

            // Emit nested JSON: sections[] → modules[] → groups[]. ONLY the SECTION order is imposed
            // (fallBacksection, like the legacy accordion); modules, sub-categories and plugins keep their
            // CONFIG declaration order — we never re-sort them (legacy doesn't; imposing e.g. alphabetical
            // would override the order the tools/config define). Mini-template order comes from getTree.
            $sections = [];
            foreach ($sectionOrder as $sk) {
                if (empty($tree[$sk])) {
                    continue;
                }
                $modules = [];
                foreach ($tree[$sk] as $mk => $modNode) {
                    $groups = [];
                    foreach ($modNode['groups'] as $g) {
                        $groups[] = ['id' => $g['id'], 'title' => $g['title'], 'plugins' => array_values($g['plugins'])];
                    }
                    $modules[] = ['key' => (string) $mk, 'label' => $moduleLabels[$mk] ?? (string) $mk, 'groups' => $groups];
                }
                $sections[] = ['key' => $sk, 'label' => $tr($sk) !== '' ? $tr($sk) : $sk, 'modules' => $modules];
            }

            // Mini-templates: the special MelisCms → MelisMiniTemplate group (per-site predefined HTML
            // snippets). The config-listener that registers them is FRONT-only, so on this BO request the
            // config is empty — we build the group straight from the mini-template getter service instead.
            $idPage = (int) $this->params()->fromQuery('idPage', 0);
            $miniModule = $this->miniTemplateModule($sm, $idPage);
            if ($miniModule !== null) {
                $placed = false;
                foreach ($sections as &$sec) {
                    if ($sec['key'] === 'MelisCms') { $sec['modules'][] = $miniModule; $placed = true; break; }
                }
                unset($sec);
                if (!$placed) {
                    $sections[] = ['key' => 'MelisCms', 'label' => $tr('MelisCms') !== '' ? $tr('MelisCms') : 'MelisCms', 'modules' => [$miniModule]];
                }
            }

            return $this->jsonResponse(['success' => true, 'data' => ['sections' => $sections]]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => false,
                'error'   => $e->getMessage(),
                'where'   => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }

    /**
     * The set (keys = lowercased module keys) of plugin modules that can render on the FRONT for this
     * page's site: the always-loaded core/front modules + the modules the site enables in its own
     * config/module.load.php (MelisCmsSiteModuleLoadService::getModules — the per-site "Modules" tool).
     * Config-key ↔ module-name is matched case-insensitively (Melis convention: `meliscmsnews` ↔
     * `MelisCmsNews`, `melisfront` ↔ `MelisFront`). Returns null when there is no site context (idPage 0
     * or a lookup fails) → the caller then applies NO filter (safer than hiding wrongly).
     */
    private function allowedSiteModules($sm, int $idPage): ?array
    {
        if ($idPage <= 0) {
            return null;
        }
        try {
            $tree = $sm->get('MelisEngineTree');
            $site = $tree->getSiteByPageId($idPage);
            if (empty($site)) {
                $site = $tree->getSiteByPageId($idPage, 'saved');
            }
            if (empty($site) || empty($site->site_id)) {
                return null;
            }
            $siteId = (int) $site->site_id;
        } catch (\Throwable) {
            return null;
        }

        // Core / front modules always loaded on any site's front → their plugins are always available.
        $allowed = ['melisfront', 'meliscms', 'meliscore', 'melisengine', 'melisassetmanager', 'melismarketplace', 'melismoduleconfig', 'melisminitemplate'];
        if (!empty($site->site_name)) {
            $allowed[] = strtolower(str_replace(' ', '', (string) $site->site_name));
        }
        try {
            foreach ((array) $sm->get('MelisCmsSiteModuleLoadService')->getModules($siteId) as $mod => $active) {
                if ((int) $active === 1) {
                    $allowed[] = strtolower((string) $mod);
                }
            }
        } catch (\Throwable) {
            return null; // service unavailable → don't hide anything
        }
        return array_flip(array_values(array_unique($allowed)));
    }

    /**
     * Build the "Mini templates" module (under MelisCms) for the page's site: the per-site tree of
     * predefined HTML snippets, from MelisCmsMiniTemplateGetterService (treeStyle). Categories become
     * groups; root templates go in an untitled group. Returns null when there are none / no site.
     */
    private function miniTemplateModule($sm, int $idPage): ?array
    {
        if ($idPage <= 0) {
            return null;
        }
        try {
            $tree = $sm->get('MelisEngineTree');
            $site = $tree->getSiteByPageId($idPage);
            if (empty($site)) {
                $site = $tree->getSiteByPageId($idPage, 'saved');
            }
            if (empty($site)) {
                return null;
            }
            $siteId = $site->site_id;
            $siteName = (string) $site->site_name;
            $items = (array) $sm->get('MelisCmsMiniTemplateGetterService')->getMiniTemplates($siteId, '', null, true);
        } catch (\Throwable) {
            return null;
        }
        if (!$items) {
            return null;
        }

        $groups = [];
        $rootPlugins = [];
        foreach ($items as $node) {
            $type = $node['type'] ?? '';
            if ($type === 'category') {
                $plugins = [];
                foreach ((array) ($node['plugins'] ?? []) as $t) {
                    $plugins[] = $this->miniEntry($t, $siteName);
                }
                if ($plugins) {
                    $groups[] = ['id' => (string) ($node['id'] ?? ''), 'title' => html_entity_decode((string) ($node['text'] ?? '')), 'plugins' => $plugins];
                }
            } elseif ($type === 'mini-template') {
                $rootPlugins[] = $this->miniEntry($node, $siteName);
            }
        }
        if ($rootPlugins) {
            array_unshift($groups, ['id' => '', 'title' => '', 'plugins' => $rootPlugins]);
        }
        if (!$groups) {
            return null;
        }

        $label = '';
        try { $label = (string) $sm->get('translator')->translate('tr_PluginSection_MelisMiniTemplate'); } catch (\Throwable) {}
        if ($label === '' || $label === 'tr_PluginSection_MelisMiniTemplate') {
            $label = 'Mini templates';
        }
        return ['key' => 'MelisMiniTemplate', 'label' => $label, 'groups' => $groups];
    }

    /** One palette entry for a mini-template tree node (name mirrors the legacy MiniTemplatePlugin_<t>_<s>). */
    private function miniEntry(array $t, string $siteName): array
    {
        $text = (string) ($t['text'] ?? '');
        return [
            'module'      => 'MelisMiniTemplate',
            'name'        => 'MiniTemplatePlugin_' . strtolower(html_entity_decode($text)) . '_' . strtolower($siteName),
            'title'       => html_entity_decode($text),
            'description' => '',
            'thumbnail'   => (string) ($t['imgSource'] ?? $t['image'] ?? ''),
            'type'        => 'html',
        ];
    }

    /** Fallback module label when tr_PluginSection_<module> has no translation: e.g. melisfront → Melis Front. */
    private function prettyModule(string $module): string
    {
        $s = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $module);   // split camelCase
        $s = preg_replace('/^melis/i', 'Melis ', (string) $s);       // melisfront → Melis front
        $s = preg_replace('/\s+/', ' ', (string) $s);
        return ucwords(trim((string) $s));
    }
}
