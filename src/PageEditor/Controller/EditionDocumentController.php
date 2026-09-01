<?php

namespace MelisCms\PageEditor\Controller;

use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use MelisCore\Controller\MelisAbstractActionController;
use MelisCms\PageEditor\SessionContentStore;
use MelisCms\PageEditor\LayoutCatalog;

/**
 * EditionDocumentController — read side of the new React page-editor PHP layer.
 *
 * Returns the page's content as the structured JSON model the React canvas
 * consumes (PageContentDocument::toArray()): the ordered plugin nodes, zones and
 * their <plugin> refs, with each node's verbatim raw kept for opaque round-trip.
 * Draft-first (saved → published). Read-only.
 *
 * Route: GET /melis/react-api/cms-page/edition/document?idPage=X
 */
class EditionDocumentController extends MelisAbstractActionController
{
    use ReactApiPageGuardTrait;

    private const MELIS_KEY = 'meliscms_page';

    public function documentAction(): HttpResponse
    {
        if ($deny = $this->denyUnlessAccess()) {
            return $deny;
        }

        try {
            $idPage = (int) $this->params()->fromQuery('idPage', 0);
            if ($idPage <= 0) {
                return $this->jsonResponse(['success' => false, 'error' => 'idPage is required'], 400);
            }

            $sm = $this->getServiceManager();
            // Read the WORKING edit session (seeded from saved→published on first touch), so the
            // structure panel reflects in-progress edits — the same buffer the melis render shows.
            $store = new SessionContentStore($sm);
            $source = $store->ensureSeeded($idPage);
            $document = $store->readDocument($idPage);
            $render = $this->renderUrls($idPage);
            // The drag-and-drop schema catalog (for the layout picker) — the same
            // config the legacy Old editor exposes; sent so React can offer the icons.
            $layouts = LayoutCatalog::all($sm);

            if ($document === null) {
                // page exists but has no content yet (folder, empty draft) — valid, empty doc
                return $this->jsonResponse([
                    'success' => true,
                    'data'    => [
                        'idPage'  => $idPage,
                        'source'  => $source,
                        'empty'   => true,
                        'wrapper' => null,
                        'nodes'   => [],
                        'layouts' => $layouts,
                    ] + $render,
                ]);
            }

            $arr = $document->toArray();

            return $this->jsonResponse([
                'success' => true,
                'data'    => array_merge(
                    ['idPage' => $idPage, 'source' => $source, 'empty' => false],
                    $render,
                    ['layouts' => $layouts, 'pluginTitles' => $this->pluginTitles($sm, $arr['nodes'] ?? []), 'pluginThumbs' => $this->pluginThumbs($sm, $arr['nodes'] ?? [], $idPage)],
                    $arr
                ),
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
     * Human display name per plugin ref id (the legacy `melis.name`, translated) so the STRUCTURE panel
     * shows real names ("Breadcrumb", "Html tag", "Slider"…) instead of the technical class name. Mini-
     * templates (config-less on BO) derive their name from the ref name. Flat map { refId => title }.
     *
     * @param array<int,array<string,mixed>> $nodes
     * @return array<string,string>
     */
    private function pluginTitles($sm, array $nodes): array
    {
        $config = $sm->get('config');
        $translator = $sm->get('translator');
        $tr = static function ($k) use ($translator): string {
            $k = (string) $k;
            if ($k === '') return '';
            // Melis convention: a leading "\" marks a translatable string — strip it before lookup.
            if ($k[0] === '\\') $k = substr($k, 1);
            try { return (string) $translator->translate($k); } catch (\Throwable) { return $k; }
        };

        $map = [];
        $walk = function (array $list) use (&$walk, $config, $tr, &$map): void {
            foreach ($list as $n) {
                if (($n['kind'] ?? '') !== 'zone') {
                    continue;
                }
                foreach ((array) ($n['refs'] ?? []) as $ref) {
                    $id = (string) ($ref['id'] ?? '');
                    if ($id === '' || isset($map[$id])) {
                        continue;
                    }
                    $map[$id] = $this->pluginTitle($config, $tr, (string) ($ref['module'] ?? ''), (string) ($ref['name'] ?? ''));
                }
                $walk((array) ($n['zones'] ?? []));
            }
        };
        $walk($nodes);
        return $map;
    }

    /** One plugin's display name from config `melis.name` (translated); mini-templates from the ref name. */
    private function pluginTitle($config, callable $tr, string $module, string $name): string
    {
        if ($module === 'MelisMiniTemplate' && strpos($name, 'MiniTemplatePlugin_') === 0) {
            $rest = substr($name, strlen('MiniTemplatePlugin_'));
            $us = strrpos($rest, '_');
            return $us !== false ? substr($rest, 0, $us) : $rest;
        }
        $melisName = $config['plugins'][$module]['plugins'][$name]['melis']['name'] ?? '';
        if ($melisName !== '') {
            return $tr($melisName);
        }
        // fallback: strip the Melis*/Plugin decoration from the class name
        $clean = preg_replace('/Plugin$/', '', preg_replace('/^Melis(Front|Cms)/', '', $name));
        return $clean !== '' ? $clean : $name;
    }

    /**
     * Thumbnail URL per plugin ref id (the same image the add-plugin palette shows): a plugin's
     * `melis.thumbnail`, a mini-template's `imgSource`. Empty string when none. Flat map { refId => url }.
     * The mini-template tree (site-scoped, a bit costly) is fetched lazily — only if the page actually
     * references a mini-template.
     *
     * @param array<int,array<string,mixed>> $nodes
     * @return array<string,string>
     */
    private function pluginThumbs($sm, array $nodes, int $idPage): array
    {
        $config = $sm->get('config');
        $map = [];
        $miniThumbs = null; // lazily built name => imgSource

        $walk = function (array $list) use (&$walk, $config, $sm, $idPage, &$map, &$miniThumbs): void {
            foreach ($list as $n) {
                if (($n['kind'] ?? '') !== 'zone') {
                    continue;
                }
                foreach ((array) ($n['refs'] ?? []) as $ref) {
                    $id = (string) ($ref['id'] ?? '');
                    if ($id === '' || isset($map[$id])) {
                        continue;
                    }
                    $module = (string) ($ref['module'] ?? '');
                    $name   = (string) ($ref['name'] ?? '');
                    if ($module === 'MelisMiniTemplate' && strpos($name, 'MiniTemplatePlugin_') === 0) {
                        if ($miniThumbs === null) {
                            $miniThumbs = $this->miniTemplateThumbs($sm, $idPage);
                        }
                        $map[$id] = (string) ($miniThumbs[$name] ?? '');
                    } else {
                        $map[$id] = (string) ($config['plugins'][$module]['plugins'][$name]['melis']['thumbnail'] ?? '');
                    }
                }
                $walk((array) ($n['zones'] ?? []));
            }
        };
        $walk($nodes);
        return $map;
    }

    /**
     * Flat map of the site's mini-templates: `MiniTemplatePlugin_<t>_<site>` => imgSource (mirrors the
     * palette's miniEntry naming). Same source as EditionPaletteController::miniTemplateModule.
     *
     * @return array<string,string>
     */
    private function miniTemplateThumbs($sm, int $idPage): array
    {
        $out = [];
        if ($idPage <= 0) {
            return $out;
        }
        try {
            $tree = $sm->get('MelisEngineTree');
            $site = $tree->getSiteByPageId($idPage) ?: $tree->getSiteByPageId($idPage, 'saved');
            if (empty($site)) {
                return $out;
            }
            $siteName = (string) $site->site_name;
            $items = (array) $sm->get('MelisCmsMiniTemplateGetterService')->getMiniTemplates($site->site_id, '', null, true);
        } catch (\Throwable) {
            return $out;
        }
        $add = function (array $t) use (&$out, $siteName): void {
            $text = (string) ($t['text'] ?? '');
            $nm = 'MiniTemplatePlugin_' . strtolower(html_entity_decode($text)) . '_' . strtolower($siteName);
            $out[$nm] = (string) ($t['imgSource'] ?? $t['image'] ?? '');
        };
        foreach ($items as $node) {
            $type = $node['type'] ?? '';
            if ($type === 'category') {
                foreach ((array) ($node['plugins'] ?? []) as $t) {
                    $add($t);
                }
            } elseif ($type === 'mini-template') {
                $add($node);
            }
        }
        return $out;
    }

    /**
     * The FAITHFUL front-render URLs for the page — the exact ones the legacy edition
     * uses (render-pagetab-edition.phtml): `/id/<idPage>/renderMode/melis?melisSite=<ns>`
     * (editable canvas) and `/preview` (read-only). `melisSite` (= the site module folder,
     * template.tpl_zf2_website_folder) is what tells the front which site to load — without
     * it the render 500s. These render the page WITH its template + theme CSS, so the React
     * canvas shows the real page rather than a bag of plugins.
     *
     * @return array{namespace:string, previewUrl:?string, editUrl:?string}
     */
    private function renderUrls(int $idPage): array
    {
        $ns = '';
        try {
            $melisPage = $this->getServiceManager()->get('MelisEnginePage');
            $datasPage = $melisPage->getDatasPage($idPage, 'saved');
            $tpl = $datasPage ? $datasPage->getMelisTemplate() : null;
            if (empty($tpl) || empty($tpl->tpl_zf2_website_folder)) {
                $datasPage = $melisPage->getDatasPage($idPage, 'published');
                $tpl = $datasPage ? $datasPage->getMelisTemplate() : null;
            }
            if (!empty($tpl->tpl_zf2_website_folder)) {
                $ns = (string) $tpl->tpl_zf2_website_folder;
            }
        } catch (\Throwable) {
        }

        if ($ns === '') {
            return ['namespace' => '', 'previewUrl' => null, 'editUrl' => null];
        }

        $q = '?melisSite=' . rawurlencode($ns);
        return [
            'namespace'  => $ns,
            'previewUrl' => '/id/' . $idPage . '/preview' . $q,
            'editUrl'    => '/id/' . $idPage . '/renderMode/melis' . $q,
        ];
    }
}
