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
        ],
    ],
];
