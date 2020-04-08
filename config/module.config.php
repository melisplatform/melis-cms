<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */

return [
    'router' => [
        'routes' => [
            'melis-backoffice' => [
                'child_routes' => [
                    'application-MelisCms' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => 'MelisCms',
                            'defaults' => [
                                '__NAMESPACE__' => 'MelisCms\Controller',
                                'controller'    => 'Index',
                                'action'        => 'indexmelis',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'default' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/[:controller[/:action]]',
                                    'constraints' => [
                                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                    ],
                                ],
                            ],
                            'page' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/Page[/:action][/:idPage]',
                                    'constraints' => [
                                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        //			'idPage'     => '[0-9]*',
                                    ],
                                    'defaults' => [
                                        'controller' => 'Page',
                                        //			'idPage'     => 1,
                                    ],
                                ],
                            ],
                        ],

                    ],
                ],
            ],

            /*
             * This route will handle the
             * alone setup of a module
             */
            'setup-melis-cms' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/MelisCms',
                    'defaults' => [
                        '__NAMESPACE__' => 'MelisCms\Controller',
                        'controller'    => '',
                        'action'        => '',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => '/[:controller[/:action]]',
                            'constraints' => [
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [],
                        ],
                    ],
                    'setup' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/setup',
                            'defaults' => [
                                'controller' => 'MelisCms\Controller\MelisSetup',
                                'action' => 'setup-form',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'service_manager' => [
        'aliases' => [
            'MelisCmsRights'                    => \MelisCms\Service\MelisCmsRightsService::class,
            'MelisCmsSiteService'               => \MelisCms\Service\MelisCmsSiteService::class,
            'MelisCmsSiteModuleLoadService'     => \MelisCms\Service\MelisCmsSitesModuleLoadService::class,
            'MelisCmsSitesDomainsService'       => \MelisCms\Service\MelisCmsSitesDomainsService::class,
            'MelisCmsSitesPropertiesService'    => \MelisCms\Service\MelisCmsSitesPropertiesService::class,
            'MelisCmsPageGetterService'         => \MelisCms\Service\MelisCmsPageGetterService::class,
            'MelisCmsPageService'               => \MelisCms\Service\MelisCmsPageService::class,
            'MelisCmsPageExportService'         => \MelisCms\Service\MelisCmsPageExportService::class,
            'MelisCmsPageImportService'         => \MelisCms\Service\MelisCmsPageImportService::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            'MelisCms\Controller\Index'             => \MelisCms\Controller\IndexController::class,
            'MelisCms\Controller\TreeSites'         => \MelisCms\Controller\TreeSitesController::class,
            'MelisCms\Controller\Page'              => \MelisCms\Controller\PageController::class,
            'MelisCms\Controller\PageProperties'    => \MelisCms\Controller\PagePropertiesController::class,
            'MelisCms\Controller\PageSeo'           => \MelisCms\Controller\PageSeoController::class,
            'MelisCms\Controller\PageEdition'       => \MelisCms\Controller\PageEditionController::class,
            'MelisCms\Controller\ToolTemplate'      => \MelisCms\Controller\ToolTemplateController::class,
            'MelisCms\Controller\ToolStyle'         => \MelisCms\Controller\ToolStyleController::class,
            'MelisCms\Controller\Language'          => \MelisCms\Controller\LanguageController::class,
            'MelisCms\Controller\Platform'          => \MelisCms\Controller\PlatformController::class,
            'MelisCms\Controller\SiteRedirect'      => \MelisCms\Controller\SiteRedirectController::class,
            'MelisCms\Controller\FrontPlugins'      => \MelisCms\Controller\FrontPluginsController::class,
            'MelisCms\Controller\FrontPluginsModal' => \MelisCms\Controller\FrontPluginsModalController::class,
            'MelisCms\Controller\PageDuplication'   => \MelisCms\Controller\PageDuplicationController::class,
            'MelisCms\Controller\PageLanguages'     => \MelisCms\Controller\PageLanguagesController::class,
            'MelisCms\Controller\MelisSetup'        => \MelisCms\Controller\MelisSetupController::class,
            'MelisCms\Controller\Sites'             => \MelisCms\Controller\SitesController::class,
            'MelisCms\Controller\SitesProperties'   => \MelisCms\Controller\SitesPropertiesController::class,
            'MelisCms\Controller\SitesModuleLoader' => \MelisCms\Controller\SitesModuleLoaderController::class,
            'MelisCms\Controller\SitesTranslation'  => \MelisCms\Controller\SitesTranslationController::class,
            'MelisCms\Controller\SitesLanguages'    => \MelisCms\Controller\SitesLanguagesController::class,
            'MelisCms\Controller\SitesDomains'      => \MelisCms\Controller\SitesDomainsController::class,
            'MelisCms\Controller\SitesConfig'       => \MelisCms\Controller\SitesConfigController::class,
            'MelisCms\Controller\GdprBanner'        => \MelisCms\Controller\GdprBannerController::class,
            'MelisCms\Controller\PageExport'        => \MelisCms\Controller\PageExportController::class,
            'MelisCms\Controller\PageImport'        => \MelisCms\Controller\PageImportController::class,
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
            'MelisCmsPagesIndicatorsPlugin' => \MelisCms\Controller\DashboardPlugins\MelisCmsPagesIndicatorsPlugin::class,
        ]
    ],
    'form_elements' => [
        'factories' => [
            'MelisCmsTemplateSelect'            => \MelisCms\Form\Factory\TemplateSelectFactory::class,
            'MelisCmsPlatformSelect'            => \MelisCms\Form\Factory\PlatformSelectFactory::class,
            'MelisMultiValInput'                => \MelisCms\Form\Factory\MelisMultiValueInputFactory::class,
            'MelisCmsPlatformIDsSelect'         => \MelisCms\Form\Factory\PlatformIDsCmsSelectFactory::class,
            'MelisCmsPluginSiteSelect'          => \MelisCms\Form\Factory\Plugin\MelisCmsPluginSiteSelectFactory::class,
            'MelisCmsPluginSiteModuleSelect'    => \MelisCms\Form\Factory\Plugin\MelisCmsPluginSiteModuleSelectFactory::class,
            'MelisCmsStyleSelect'               => \MelisCms\Form\Factory\MelisCmsStyleSelectFactory::class,
            'MelisSwitch'                       => \MelisCms\Form\Factory\MelisSwitchFactory::class,
            'MelisCmsLanguageSelect'            => \MelisCms\Form\Factory\MelisCmsLanguageSelectFactory::class,
            'MelisCmsPageLanguagesSelect'       => \MelisCms\Form\Factory\MelisCmsPageLanguagesSelectFactory::class,
            'MelisCmsSiteModuleSelect'          => \MelisCms\Form\Factory\MelisCmsSiteModuleSelectFactory::class,
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'layout/layoutCms'                      => __DIR__ . '/../view/layout/layoutCms.phtml',
            'melis-cms/index/index'                 => __DIR__ . '/../view/melis-cms/index/index.phtml',
            // Dashboard plugin templates
            'melis-cms/dashboard/page-indicators'   => __DIR__ . '/../view/melis-cms/dashboard-plugins/page-indicators.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    // Config Files
    'tinyMCE' => [
        'html'      => 'MelisCms/public/js/tinyMCE/html.php',
        'textarea'  => 'MelisCms/public/js/tinyMCE/textarea.php',
        'media'     => 'MelisCms/public/js/tinyMCE/media.php',
    ],
];