<?php

namespace MelisCms\PageEditor\Controller;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use MelisCore\Controller\MelisAbstractActionController;
use MelisCms\PageEditor\SessionContentStore;
use MelisCms\PageEditor\PageContentDocument;
use MelisCms\PageEditor\LayoutCatalog;

/**
 * EditionSaveController — stateless write side of the new React page editor
 * (branch evo/page-edition-react).
 *
 * Applies STRUCTURAL edits to the page content and persists them into the WORKING EDIT
 * SESSION (the same `meliscms` `content-pages` buffer the legacy Old editor uses), NOT the
 * draft. The client sends OPERATIONS (not raw XML); the server replays them on the tested
 * PageContentDocument model and re-serialises to the exact page_content XML → so unknown/
 * other-module plugin nodes are preserved verbatim (retro-compat).
 *
 * Ops (mirroring PageContentDocument): reorderNodes {ids}, setWidths {id,desktop,tablet,
 * mobile}, reorderZoneRefs {zoneId,refIds}, setZoneRefs, addPlugin, setTagContent, applyLayout.
 *
 * Persistence contract (aligned with legacy): editing writes ONLY the session — it never
 * touches the DB. The melis render reads that session in priority, so edits show live; the
 * top toolbar's Save button (meliscms_page_save_start → saveEdition) flushes the session
 * into `melis_cms_page_saved`, and Publish copies it on to published. So NO `_end` event is
 * fired here (that would log a save that hasn't happened) — the save/publish events belong
 * to the top toolbar chain. See SessionContentStore.
 *
 * Route: POST /melis/react-api/cms-page/edition/save   body JSON { idPage, ops:[...] }
 */
class EditionSaveController extends MelisAbstractActionController
{
    use ReactApiPageGuardTrait;

    private const MELIS_KEY = 'meliscms_page';

    public function saveAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) {
            return $deny;
        }

        try {
            $payload = json_decode((string) $this->getRequest()->getContent(), true);
            $idPage = (int) ($payload['idPage'] ?? 0);
            $ops = is_array($payload['ops'] ?? null) ? $payload['ops'] : [];
            if ($idPage <= 0) {
                return $this->jsonResponse(['success' => false, 'error' => 'idPage is required'], 400);
            }

            $sm = $this->getServiceManager();

            // Edits go into the WORKING EDIT SESSION, not the draft (legacy model): load the
            // in-session document (seeded from saved→published on first touch), replay the ops,
            // write it straight back to the session. The top toolbar's Save flushes it to the DB.
            $store = new SessionContentStore($sm);
            $doc = $store->readDocument($idPage);
            if ($doc === null) {
                return $this->jsonResponse(['success' => false, 'error' => 'page has no content'], 422);
            }

            $applied = 0;
            foreach ($ops as $op) {
                switch ($op['op'] ?? '') {
                    case 'reorderNodes':
                        $doc->reorderNodes(array_values(array_map('strval', (array) ($op['ids'] ?? []))));
                        $applied++;
                        break;
                    case 'setWidths':
                        $doc->setWidths((string) ($op['id'] ?? ''), (string) ($op['desktop'] ?? ''), (string) ($op['tablet'] ?? ''), (string) ($op['mobile'] ?? ''));
                        $applied++;
                        break;
                    case 'reorderZoneRefs':
                        $doc->reorderZoneRefs((string) ($op['zoneId'] ?? ''), array_values(array_map('strval', (array) ($op['refIds'] ?? []))));
                        $applied++;
                        break;
                    case 'setZoneRefs':
                        // exact set (drops unlisted refs) — used for reorder AND remove
                        $doc->setZoneRefs((string) ($op['zoneId'] ?? ''), array_values(array_map('strval', (array) ($op['refIds'] ?? []))));
                        $applied++;
                        break;
                    case 'ensureZones':
                        // Seed a FRESH page's template drag-drop zones into the model (they live only in
                        // the template render until the page has content) so the structure panel shows them
                        // and later ops can target them. Client sends the top-level zone ids read off the
                        // rendered canvas. No-op for zones already present.
                        foreach ((array) ($op['zones'] ?? []) as $zid) {
                            if ($doc->ensureZone((string) $zid)) {
                                $applied++;
                            }
                        }
                        break;
                    case 'addPlugin':
                        // Add ANY active plugin to a zone (from the React "+" palette). Tag blocks
                        // (html/textarea/media) get a CDATA node; generic plugins an empty node they
                        // render defaults from. Back-compat: kind=html with no name = html quick-add.
                        if ($this->applyAddPlugin($doc, $sm, $op, $idPage)) {
                            $applied++;
                        }
                        break;
                    case 'setTagContent':
                        // edit a text/html block's content (its config). CDATA handled in the model.
                        $id = (string) ($op['id'] ?? '');
                        if ($id !== '') {
                            $doc->setTagContent($id, (string) ($op['content'] ?? ''));
                            $applied++;
                        }
                        break;
                    case 'applyLayout':
                        // apply a drag-and-drop SCHEMA to a zone (V2): splits it into
                        // <zoneId>_1.._N nested cells. cols is resolved SERVER-SIDE from the
                        // template (authoritative + allow-list): an unknown template → null → skip.
                        $zoneId = (string) ($op['zoneId'] ?? '');
                        $template = (string) ($op['template'] ?? '');
                        if ($zoneId !== '') {
                            $cols = LayoutCatalog::cols($sm, $template);
                            if ($cols !== null) {
                                $doc->applyLayout($zoneId, $template, $cols);
                                $applied++;
                            }
                        }
                        break;
                    default:
                        // unknown op — ignore (forward compatibility)
                }
            }

            // Persist into the working edit session (NOT the DB) — no save/publish event here.
            $store->writeDocument($idPage, $doc);

            return $this->jsonResponse([
                'success' => true,
                'data'    => ['idPage' => $idPage, 'source' => 'session', 'opsApplied' => $applied],
            ]);
        } catch (\Throwable $e) {
            return $this->jsonResponse([
                'success' => false,
                'error'   => $e->getMessage(),
                'where'   => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }

    /**
     * Apply an `addPlugin` op: insert a `<plugin module name id/>` ref into the zone AND a top-level
     * data node. Tag plugins (xmlDbKey=melisTag: html/textarea/media) get a CDATA body; every other
     * plugin gets an empty node with full-width defaults that it renders from — its real config is
     * written later by the config modal (savePluginConfigToXml → setPluginXml). Refuses unknown plugins.
     */
    private function applyAddPlugin(PageContentDocument $doc, $sm, array $op, int $idPage = 0): bool
    {
        $zoneId = (string) ($op['zoneId'] ?? '');
        if ($zoneId === '') {
            return false;
        }

        $module = (string) ($op['module'] ?? '');
        $name   = (string) ($op['name'] ?? '');
        $kind   = (string) ($op['kind'] ?? '');

        // Mini-templates are predefined HTML snippets: adding one bakes the .phtml content into a
        // melisTag (exactly what the legacy drop produces — see page_content tag-miniTpl_* nodes).
        if ($module === 'MelisMiniTemplate' && strpos($name, 'MiniTemplatePlugin_') === 0) {
            return $this->applyAddMiniTemplate($doc, $sm, $zoneId, $name, $idPage);
        }

        // Back-compat: the old html quick-add sends kind=html with no plugin name.
        if ($name === '' && ($kind === 'html' || $kind === '')) {
            $module = 'melisfront';
            $name   = 'MelisFrontTagHtmlPlugin';
        }
        if ($module === '' || $name === '') {
            return false;
        }

        $config = $sm->get('config');
        $pconf  = $config['plugins'][$module]['plugins'][$name] ?? null;
        if ($pconf === null) {
            return false; // not a registered/active plugin — refuse (defensive)
        }

        // The plugin's XML element name (pluginXmlDbKey) is hardcoded in its class → instantiate to read it.
        $xmlKey = '';
        try {
            $plugin = $sm->get('ControllerPluginManager')->get($name);
            if (method_exists($plugin, 'getPluginXmlDbKey')) {
                $xmlKey = (string) $plugin->getPluginXmlDbKey();
            }
        } catch (\Throwable) {
        }
        if ($xmlKey === '') {
            return false;
        }

        $clientId = preg_replace('/[^A-Za-z0-9_]/', '', (string) ($op['id'] ?? ''));
        $newId = $clientId !== '' ? $clientId : $this->newPluginId($doc, $pconf, $name);

        if ($xmlKey === 'melisTag') {
            $type = (string) ($pconf['front']['type'] ?? ($op['type'] ?? 'html'));
            $content = (string) ($op['content'] ?? $this->defaultTagContent($type));
            $content = str_replace(']]>', ']]]]><![CDATA[>', $content); // keep CDATA intact
            $raw = '<melisTag id="' . $newId . '" plugin_container_id="" type="' . htmlspecialchars($type, ENT_QUOTES) . '"'
                . ' width_desktop="100" width_tablet="100" width_mobile="100">'
                . '<![CDATA[' . $content . ']]></melisTag>';
        } else {
            // Empty data node — full-width defaults (without width_* the block collapses in a multi-col cell).
            $raw = '<' . $xmlKey . ' id="' . $newId . '" plugin_container_id=""'
                . ' width_desktop="100" width_tablet="100" width_mobile="100"/>';
        }

        $doc->addPlugin($zoneId, $module, $name, $newId, $raw);
        return true;
    }

    /**
     * Add a mini-template: resolve `MiniTemplatePlugin_<template>_<site>` to its .phtml snippet on disk
     * and insert it as a melisTag (predefined HTML) + the MelisMiniTemplate ref — byte-shape identical
     * to the legacy drop. Refuses silently if the snippet can't be read (never corrupts the draft).
     */
    private function applyAddMiniTemplate(PageContentDocument $doc, $sm, string $zoneId, string $name, int $idPage = 0): bool
    {
        $rest = substr($name, strlen('MiniTemplatePlugin_'));
        $us = strrpos($rest, '_');
        if ($us === false) {
            return false;
        }
        $tplName  = substr($rest, 0, $us);   // template name (lowercased in the plugin name)
        $siteName = substr($rest, $us + 1);  // site module (from the name — lowercased)

        // Prefer the page's REAL site name (correct case) — getModulePath is case-sensitive.
        if ($idPage > 0) {
            try {
                $tree = $sm->get('MelisEngineTree');
                $site = $tree->getSiteByPageId($idPage) ?: $tree->getSiteByPageId($idPage, 'saved');
                if (!empty($site) && !empty($site->site_name)) {
                    $siteName = (string) $site->site_name;
                }
            } catch (\Throwable) {
            }
        }

        $content = null;
        try {
            $folder = (string) $sm->get('MelisCmsMiniTemplateService')->getMiniTemplatePathByTemplateName($siteName, $tplName);
            $folder = rtrim($folder, "/\\");
            $c = @file_get_contents($folder . '/' . $tplName . '.phtml');
            if ($c === false) {
                // case-insensitive fallback (the plugin-name lowercased the file name)
                foreach ((array) @glob($folder . '/*.phtml') as $f) {
                    if (strcasecmp((string) pathinfo($f, PATHINFO_FILENAME), $tplName) === 0) {
                        $c = @file_get_contents($f);
                        break;
                    }
                }
            }
            $content = $c === false ? null : $c;
        } catch (\Throwable) {
            return false;
        }
        if ($content === null) {
            return false;
        }

        $content = str_replace(']]>', ']]]]><![CDATA[>', (string) $content);
        $newId = 'tag-miniTpl_' . time();
        $existing = [];
        foreach ($doc->nodes() as $n) {
            if (!empty($n['id'])) {
                $existing[(string) $n['id']] = true;
            }
        }
        while (isset($existing[$newId])) {
            $newId = 'tag-miniTpl_' . time() . substr(bin2hex(random_bytes(2)), 0, 3);
        }

        $raw = '<melisTag id="' . $newId . '" plugin_container_id="" type="html"'
            . ' width_desktop="100" width_tablet="100" width_mobile="100">'
            . '<![CDATA[' . $content . ']]></melisTag>';
        $doc->addPlugin($zoneId, 'MelisMiniTemplate', $name, $newId, $raw);
        return true;
    }

    /** A fresh unique plugin id: `<config front.id or class slug>_<time>`, guarded against collision. */
    private function newPluginId(PageContentDocument $doc, array $pconf, string $name): string
    {
        $prefix = (string) ($pconf['front']['id'] ?? '');
        if ($prefix === '') {
            $prefix = strtolower(preg_replace('/[^A-Za-z0-9]/', '', preg_replace('/Plugin$/', '', $name)));
            if ($prefix === '') {
                $prefix = 'plugin';
            }
        }
        $existing = [];
        foreach ($doc->nodes() as $n) {
            if (!empty($n['id'])) {
                $existing[(string) $n['id']] = true;
            }
        }
        $id = $prefix . '_' . time();
        while (isset($existing[$id])) {
            $id = $prefix . '_' . time() . substr(bin2hex(random_bytes(2)), 0, 3);
        }
        return $id;
    }

    private function defaultTagContent(string $type): string
    {
        return match ($type) {
            'textarea' => 'Nouveau texte',
            'media'    => '',
            default    => '<p>Nouveau bloc — cliquez pour éditer</p>',
        };
    }
}
