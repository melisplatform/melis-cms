<?php

/**
 * Routes + contrôleurs React API fournis par MelisCms.
 *
 * Ces routes s'ajoutent aux child_routes de `melis-react-api` (défini dans MelisReactApi,
 * le bridge GÉNÉRIQUE). Modularité : les contrôleurs/routes/invokables d'un outil vivent
 * dans SON module, pas dans MelisReactApi. Laminas\Stdlib\ArrayUtils::merge() fusionne.
 * Les URLs ne changent pas. Capacités : cf. config/react.capabilities.php.
 * Mergé via MelisCms\Module::getConfig().
 */

return [
    'router' => [
        'routes' => [
            'melis-backoffice' => [
                'child_routes' => [
                    'melis-react-api' => [
                        'child_routes' => [
                            // ── Sites (MelisCms tool : liste + meta tunnel création) ──
                            'cms-sites-meta' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-sites/meta[/]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiCmsSites',
                                        'action'        => 'meta',
                                    ],
                                ],
                            ],
                            'cms-sites-create' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-sites/create[/]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiCmsSites',
                                        'action'        => 'create',
                                    ],
                                ],
                            ],
                            'cms-sites-config' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/cms-sites/:id/config[/]',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiCmsSites',
                                        'action'        => 'config',
                                    ],
                                ],
                            ],
                            'cms-sites-modules' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/cms-sites/:id/modules[/]',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiCmsSites',
                                        'action'        => 'modules',
                                    ],
                                ],
                            ],
                            'cms-sites-item' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/cms-sites/:id',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiCmsSites',
                                        'action'        => 'get',
                                    ],
                                ],
                            ],
                            'cms-sites-list' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-sites[/]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiCmsSites',
                                        'action'        => 'list',
                                    ],
                                ],
                            ],
                            // ── Site Redirects 301 (MelisCms tool, UI via brick) ──
                            'site-redirects-list' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/site-redirects[/]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiSiteRedirect',
                                        'action'        => 'list',
                                    ],
                                ],
                            ],
                            'site-redirects-stats' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/site-redirects/stats[/]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiSiteRedirect',
                                        'action'        => 'stats',
                                    ],
                                ],
                            ],
                            'site-redirects-sites' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/site-redirects/sites[/]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiSiteRedirect',
                                        'action'        => 'sites',
                                    ],
                                ],
                            ],
                            'site-redirects-save' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/site-redirects/save[/]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiSiteRedirect',
                                        'action'        => 'save',
                                    ],
                                ],
                            ],
                            'site-redirects-delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/site-redirects/delete/:id',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiSiteRedirect',
                                        'action'        => 'delete',
                                    ],
                                ],
                            ],
                            'site-redirects-item' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/site-redirects/:id',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiSiteRedirect',
                                        'action'        => 'get',
                                    ],
                                ],
                            ],
                            // ── Templates (MelisCms tool, UI via brick ; create/edit legacy) ──
                            'templates-list' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/templates[/]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiTemplate',
                                        'action'        => 'list',
                                    ],
                                ],
                            ],
                            'templates-stats' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/templates/stats[/]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiTemplate',
                                        'action'        => 'stats',
                                    ],
                                ],
                            ],
                            'templates-sites' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/templates/sites[/]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiTemplate',
                                        'action'        => 'sites',
                                    ],
                                ],
                            ],
                            'templates-delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/templates/delete/:id',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiTemplate',
                                        'action'        => 'delete',
                                    ],
                                ],
                            ],
                            'templates-item' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/templates/:id',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiTemplate',
                                        'action'        => 'get',
                                    ],
                                ],
                            ],
                            'templates-save' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/templates/save[/]',
                                    'defaults' => [
                                        '__NAMESPACE__' => 'MelisCms\Controller',
                                        'controller'    => 'MelisReactApiTemplate',
                                        'action'        => 'save',
                                    ],
                                ],
                            ],
                            // ── CMS Languages (MelisCms tool, UI via brick) ──
                            'cms-languages-list' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-languages[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsLanguage', 'action' => 'list'],
                                ],
                            ],
                            'cms-languages-stats' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-languages/stats[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsLanguage', 'action' => 'stats'],
                                ],
                            ],
                            'cms-languages-save' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-languages/save[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsLanguage', 'action' => 'save'],
                                ],
                            ],
                            'cms-languages-delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/cms-languages/delete/:id',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsLanguage', 'action' => 'delete'],
                                ],
                            ],
                            'cms-languages-item' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/cms-languages/:id',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsLanguage', 'action' => 'get'],
                                ],
                            ],

                            // ── CMS Platforms IDs (MelisCms tool, UI via brick) ──
                            'cms-platform-ids-list' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-platform-ids[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsPlatformId', 'action' => 'list'],
                                ],
                            ],
                            'cms-platform-ids-stats' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-platform-ids/stats[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsPlatformId', 'action' => 'stats'],
                                ],
                            ],
                            'cms-platform-ids-save' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-platform-ids/save[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsPlatformId', 'action' => 'save'],
                                ],
                            ],
                            'cms-platform-ids-delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/cms-platform-ids/delete/:id',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsPlatformId', 'action' => 'delete'],
                                ],
                            ],
                            'cms-platform-ids-item' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/cms-platform-ids/:id',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsPlatformId', 'action' => 'get'],
                                ],
                            ],

                            // ── CMS Styles (MelisCms tool, UI via brick) ──
                            'cms-styles-list' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-styles[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsStyle', 'action' => 'list'],
                                ],
                            ],
                            'cms-styles-stats' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-styles/stats[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsStyle', 'action' => 'stats'],
                                ],
                            ],
                            'cms-styles-sites' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-styles/sites[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsStyle', 'action' => 'sites'],
                                ],
                            ],
                            'cms-styles-save' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-styles/save[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsStyle', 'action' => 'save'],
                                ],
                            ],
                            'cms-styles-delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/cms-styles/delete/:id',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsStyle', 'action' => 'delete'],
                                ],
                            ],
                            'cms-styles-item' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/cms-styles/:id',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsStyle', 'action' => 'get'],
                                ],
                            ],

                            // ── CMS Mini-Template Manager (MelisCms tool, UI via brick) ──
                            // Identificateur composite (site_module + template_name) → pas de :id numérique.
                            // item / delete utilisent query params ou body JSON pour la clé composite.
                            'cms-mini-templates-list' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-mini-templates[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsMiniTemplate', 'action' => 'list'],
                                ],
                            ],
                            'cms-mini-templates-stats' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-mini-templates/stats[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsMiniTemplate', 'action' => 'stats'],
                                ],
                            ],
                            'cms-mini-templates-sites' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-mini-templates/sites[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsMiniTemplate', 'action' => 'sites'],
                                ],
                            ],
                            'cms-mini-templates-item' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-mini-templates/item[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsMiniTemplate', 'action' => 'item'],
                                ],
                            ],
                            'cms-mini-templates-save' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-mini-templates/save[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsMiniTemplate', 'action' => 'save'],
                                ],
                            ],
                            'cms-mini-templates-delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-mini-templates/delete[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsMiniTemplate', 'action' => 'delete'],
                                ],
                            ],
                            // ── CMS Menu Manager (MelisCms tool, UI via brique) ──
                            // Réutilise MelisCmsMiniTemplateService (getTree/saveTree/saveCategory/deleteCategory).
                            'menu-manager-sites' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/menu-manager/sites[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsMenuManager', 'action' => 'sites'],
                                ],
                            ],
                            'menu-manager-languages' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/menu-manager/languages[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsMenuManager', 'action' => 'languages'],
                                ],
                            ],
                            'menu-manager-tree-save' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/menu-manager/tree/save[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsMenuManager', 'action' => 'saveTree'],
                                ],
                            ],
                            'menu-manager-tree' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/menu-manager/tree[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsMenuManager', 'action' => 'tree'],
                                ],
                            ],
                            'menu-manager-category-save' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/menu-manager/category/save[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsMenuManager', 'action' => 'saveCategory'],
                                ],
                            ],
                            'menu-manager-category-delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/menu-manager/category/delete/:id',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsMenuManager', 'action' => 'deleteCategory'],
                                ],
                            ],
                            'menu-manager-category-item' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/menu-manager/category/:id',
                                    'constraints' => ['id' => '[0-9]+'],
                                    'defaults'    => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiCmsMenuManager', 'action' => 'category'],
                                ],
                            ],

                            // ── Éditeur de page CMS (meliscms_page) — coquille React full-React ──
                            // Structure MODULAIRE (onglets + boutons assemblés par fusion de config,
                            // incl. contributions d'autres modules) + en-tête page. Contenu des onglets
                            // chargé par melisKey via react-tool-page (Édition = drag'n'drop legacy).
                            'cms-page-structure' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/cms-page/structure[/]',
                                    'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPage', 'action' => 'structure'],
                                ],
                            ],
                            // Onglets natifs (Propriétés / SEO / Langages) : read + write + refs
                            'cms-page-refs' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/refs[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPage', 'action' => 'refs']],
                            ],
                            'cms-page-properties-save' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/properties/save[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPage', 'action' => 'save-properties']],
                            ],
                            'cms-page-properties' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/properties[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPage', 'action' => 'properties']],
                            ],
                            'cms-page-seo-save' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/seo/save[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPage', 'action' => 'save-seo']],
                            ],
                            'cms-page-seo' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/seo[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPage', 'action' => 'seo']],
                            ],
                            'cms-page-languages' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/languages[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPage', 'action' => 'languages']],
                            ],
                            // Chaîne d'ancêtres (racine → parent) d'une page → déployer l'arbre jusqu'à la page en cours.
                            'cms-page-ancestors' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/ancestors[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPage', 'action' => 'ancestors']],
                            ],
                            // ── NOUVELLE COUCHE éditeur React (evo/page-edition-react) — rendu STATELESS d'un plugin.
                            // Logique dans MelisCms\PageEditor\Controller\EditionRenderController (namespace séparé).
                            'cms-page-edition-render-plugin' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/edition/render-plugin[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPageEditorRender', 'action' => 'render-plugin']],
                            ],
                            // Modèle JSON de la page (PageContentDocument::toArray()) que consommera le canvas React.
                            'cms-page-edition-document' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/edition/document[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPageEditorDocument', 'action' => 'document']],
                            ],
                            // Save stateless : applique des ops structurelles (reorder/resize/zone) et écrit le brouillon.
                            'cms-page-edition-save' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/edition/save[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPageEditorSave', 'action' => 'save']],
                            ],
                            // Rendu d'édition PROPRE (path C) : page templatée + CSS, SANS le JS d'édition legacy, avec marqueurs → le canvas React possède l'interaction.
                            'cms-page-edition-render' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/edition/render[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPageEditorRenderPage', 'action' => 'page']],
                            ],
                            // Palette : liste des plugins actifs/ajoutables (config plugins[*].melis) pour le bouton "+".
                            'cms-page-edition-plugins' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/edition/plugins[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPageEditorPalette', 'action' => 'plugins']],
                            ],
                            // Titre d'une page par id → le sélecteur de page (PagePicker) affiche le NOM en React.
                            'cms-page-edition-page-title' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/edition/page-title[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPageEditorPluginConfig', 'action' => 'page-title']],
                            ],
                            // Options des champs (template_path…) pour les FORMS natifs React (PluginFormKit.fetchFieldOptions).
                            'cms-page-edition-plugin-config-options' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/edition/plugin-config/options[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPageEditorPluginConfig', 'action' => 'options']],
                            ],
                            // Declarative FORM SCHEMA (derived from createOptionsForms) → the runtime React
                            // SchemaForm renders any plugin's config natively, no per-plugin code / build.
                            'cms-page-edition-plugin-config-schema' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/edition/plugin-config/schema[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPageEditorPluginConfig', 'action' => 'schema']],
                            ],
                            // Config plugin (Part 2, générique) : page HTML autonome (iframe) prefill depuis le brouillon.
                            'cms-page-edition-plugin-config' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/edition/plugin-config[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPageEditorPluginConfig', 'action' => 'form']],
                            ],
                            // Config plugin : valide via createOptionsForms() puis savePluginConfigToXml() → setPluginXml (brouillon).
                            'cms-page-edition-plugin-config-save' => [
                                'type' => 'Segment',
                                'options' => ['route' => '/cms-page/edition/plugin-config/save[/]', 'defaults' => ['__NAMESPACE__' => 'MelisCms\Controller', 'controller' => 'MelisReactApiPageEditorPluginConfig', 'action' => 'save']],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'invokables' => [
            'MelisCms\Controller\MelisReactApiCmsSites' => \MelisCms\Controller\MelisReactApiCmsSitesController::class,
            'MelisCms\Controller\MelisReactApiSiteRedirect' => \MelisCms\Controller\MelisReactApiSiteRedirectController::class,
            'MelisCms\Controller\MelisReactApiTemplate' => \MelisCms\Controller\MelisReactApiTemplateController::class,
            'MelisCms\Controller\MelisReactApiCmsLanguage' => \MelisCms\Controller\MelisReactApiCmsLanguageController::class,
            'MelisCms\Controller\MelisReactApiCmsPlatformId' => \MelisCms\Controller\MelisReactApiCmsPlatformIdController::class,
            'MelisCms\Controller\MelisReactApiCmsStyle' => \MelisCms\Controller\MelisReactApiCmsStyleController::class,
            'MelisCms\Controller\MelisReactApiCmsMiniTemplate' => \MelisCms\Controller\MelisReactApiCmsMiniTemplateController::class,
            'MelisCms\Controller\MelisReactApiCmsMenuManager' => \MelisCms\Controller\MelisReactApiCmsMenuManagerController::class,
            'MelisCms\Controller\MelisReactApiPage' => \MelisCms\Controller\MelisReactApiPageController::class,
            'MelisCms\Controller\MelisReactApiPageEditorRender' => \MelisCms\PageEditor\Controller\EditionRenderController::class,
            'MelisCms\Controller\MelisReactApiPageEditorDocument' => \MelisCms\PageEditor\Controller\EditionDocumentController::class,
            'MelisCms\Controller\MelisReactApiPageEditorSave' => \MelisCms\PageEditor\Controller\EditionSaveController::class,
            'MelisCms\Controller\MelisReactApiPageEditorRenderPage' => \MelisCms\PageEditor\Controller\EditionRenderPageController::class,
            'MelisCms\Controller\MelisReactApiPageEditorPluginConfig' => \MelisCms\PageEditor\Controller\EditionPluginConfigController::class,
            'MelisCms\Controller\MelisReactApiPageEditorPalette' => \MelisCms\PageEditor\Controller\EditionPaletteController::class,
        ],
    ],
];
