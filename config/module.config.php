<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */


return array(
    'router' => array(
        'routes' => array(
        	'melis-backoffice' => array(
                'child_routes' => array(
            		'application-MelisCms' => array(
            				'type'    => 'Literal',
            				'options' => array(
            						'route'    => 'MelisCms',
            						'defaults' => array(
            								'__NAMESPACE__' => 'MelisCms\Controller',
            								'controller'    => 'Index',
            								'action'        => 'indexmelis',
            						), 
            				),
            				'may_terminate' => true,
            				'child_routes' => array(
            						'default' => array(
            								'type'    => 'Segment',
            								'options' => array(
                                					'route'    => '/[:controller[/:action]]',
            										'constraints' => array(
            												'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
            												'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
            										),
            										'defaults' => array(
            										),
            								),
            						),
            						'page' => array(
            								'type'    => 'Segment',
            								'options' => array(
                                					'route'    => '/Page[/:action][/:idPage]',
            										'constraints' => array(
            												'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
            									//			'idPage'     => '[0-9]*',
            										),
            										'defaults' => array(
            												'controller' => 'Page',
            									//			'idPage'     => 1,
            										),
            								),
            						), 
            				),
            		  
                    ),
                ),
            ),

            /*
             * This route will handle the
             * alone setup of a module
             */
            'setup-melis-cms' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/MelisCms',
                    'defaults' => array(
                        '__NAMESPACE__' => 'MelisCms\Controller',
                        'controller'    => '',
                        'action'        => '',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
//
                            ),
                        ),
                    ),
                    'setup' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/setup',
                            'defaults' => array(
                                'controller' => 'MelisCms\Controller\MelisSetup',
                                'action' => 'setup-form',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'translator' => array(
    	'locale' => 'en_EN',
	),
    'service_manager' => array(
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
        'factories' => array(
			'MelisCmsRights' => 'MelisCms\Service\Factory\MelisCmsRightsServiceFactory',
            'MelisCmsSiteService' => 'MelisCms\Service\Factory\MelisCmsSiteServiceFactory',
            'MelisCmsSiteModuleLoadService' => 'MelisCms\Service\Factory\MelisCmsSitesModuleLoadServiceFactory',
            'MelisCmsSitesDomainsService' => 'MelisCms\Service\Factory\MelisCmsSitesDomainsServiceFactory',
            'MelisCmsSitesPropertiesService' => 'MelisCms\Service\Factory\MelisCmsSitesPropertiesServiceFactory',
            'MelisCmsPageGetterService' => 'MelisCms\Service\Factory\MelisCmsPageGetterServiceFactory',
            'MelisCms\Listener\MelisCmsPluginSaveEditionSessionListener' => 'MelisCms\Listener\Factory\MelisCmsPluginSaveEditionSessionListenerFactory',
            'MelisCmsPageService' => 'MelisCms\Service\Factory\MelisCmsPageServiceFactory',
            'MelisCmsPageExportService' => 'MelisCms\Service\Factory\MelisCmsPageExportServiceFactory',
            'MelisCmsPageImportService' => 'MelisCms\Service\Factory\MelisCmsPageImportServiceFactory',
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'MelisCms\Controller\Index' => 'MelisCms\Controller\IndexController',
            'MelisCms\Controller\TreeSites' => 'MelisCms\Controller\TreeSitesController',
            'MelisCms\Controller\Page' => 'MelisCms\Controller\PageController',
            'MelisCms\Controller\PageProperties' => 'MelisCms\Controller\PagePropertiesController',
            'MelisCms\Controller\PageSeo' => 'MelisCms\Controller\PageSeoController',
            'MelisCms\Controller\PageEdition' => 'MelisCms\Controller\PageEditionController',
            'MelisCms\Controller\ToolTemplate' => 'MelisCms\Controller\ToolTemplateController',
            'MelisCms\Controller\ToolStyle' => 'MelisCms\Controller\ToolStyleController',
            'MelisCms\Controller\Language' => 'MelisCms\Controller\LanguageController',
            'MelisCms\Controller\Platform' => 'MelisCms\Controller\PlatformController',
            'MelisCms\Controller\SiteRedirect' => 'MelisCms\Controller\SiteRedirectController',
            'MelisCms\Controller\FrontPlugins' => 'MelisCms\Controller\FrontPluginsController',
            'MelisCms\Controller\FrontPluginsModal' => 'MelisCms\Controller\FrontPluginsModalController',
            'MelisCms\Controller\PageDuplication' => 'MelisCms\Controller\PageDuplicationController',
            'MelisCms\Controller\PageLanguages' => 'MelisCms\Controller\PageLanguagesController',
            'MelisCms\Controller\MelisSetup' => 'MelisCms\Controller\MelisSetupController',
            'MelisCms\Controller\Sites' => 'MelisCms\Controller\SitesController',
            'MelisCms\Controller\SitesProperties' => 'MelisCms\Controller\SitesPropertiesController',
            'MelisCms\Controller\SitesModuleLoader' => 'MelisCms\Controller\SitesModuleLoaderController',
            'MelisCms\Controller\SitesTranslation' => 'MelisCms\Controller\SitesTranslationController',
            'MelisCms\Controller\SitesLanguages' => 'MelisCms\Controller\SitesLanguagesController',
            'MelisCms\Controller\SitesDomains' => 'MelisCms\Controller\SitesDomainsController',
            'MelisCms\Controller\SitesConfig' => 'MelisCms\Controller\SitesConfigController',
            'MelisCms\Controller\GdprBanner' => 'MelisCms\Controller\GdprBannerController',
            'MelisCms\Controller\PageExport' => 'MelisCms\Controller\PageExportController',
            'MelisCms\Controller\PageImport' => 'MelisCms\Controller\PageImportController',
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'MelisCmsPagesIndicatorsPlugin' => 'MelisCms\Controller\DashboardPlugins\MelisCmsPagesIndicatorsPlugin',
        )
    ),
    'form_elements' => array(
        'factories' => array(
    		'MelisCmsTemplateSelect' => 'MelisCms\Form\Factory\TemplateSelectFactory',
            'MelisCmsPlatformSelect' => 'MelisCms\Form\Factory\PlatformSelectFactory',
            'MelisMultiValInput' => 'MelisCms\Form\Factory\MelisMultiValueInputFactory',
            'MelisCmsPlatformIDsSelect' => 'MelisCms\Form\Factory\PlatformIDsCmsSelectFactory', 
            'MelisCmsPluginSiteSelect' => 'MelisCms\Form\Factory\Plugin\MelisCmsPluginSiteSelectFactory', 
            'MelisCmsPluginSiteModuleSelect' => 'MelisCms\Form\Factory\Plugin\MelisCmsPluginSiteModuleSelectFactory', 
            'MelisCmsStyleSelect' => 'MelisCms\Form\Factory\MelisCmsStyleSelectFactory', 
            'MelisSwitch' => 'MelisCms\Form\Factory\MelisSwitchFactory',
            'MelisCmsLanguageSelect' => 'MelisCms\Form\Factory\MelisCmsLanguageSelectFactory',
            'MelisCmsPageLanguagesSelect' => 'MelisCms\Form\Factory\MelisCmsPageLanguagesSelectFactory',
            'MelisCmsSiteModuleSelect' => 'MelisCms\Form\Factory\MelisCmsSiteModuleSelectFactory',
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'template_map' => array(
            'layout/layoutCms'           => __DIR__ . '/../view/layout/layoutCms.phtml',
            'melis-cms/index/index' => __DIR__ . '/../view/melis-cms/index/index.phtml',
            
            // Dashboard plugin templates
            'melis-cms/dashboard/page-indicators' => __DIR__ . '/../view/melis-cms/dashboard-plugins/page-indicators.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
    // Config Files
    'tinyMCE' => array(
    	'html' => 'MelisCms/public/js/tinyMCE/html.php',
    	'textarea' => 'MelisCms/public/js/tinyMCE/textarea.php',
    	'media' => 'MelisCms/public/js/tinyMCE/media.php',
    ),
);
