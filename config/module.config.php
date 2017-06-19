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
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'MelisCms\Controller\Index' => 'MelisCms\Controller\IndexController',
            'MelisCms\Controller\TreeSites' => 'MelisCms\Controller\TreeSitesController',
            'MelisCms\Controller\Dashboard' => 'MelisCms\Controller\DashboardController',
            'MelisCms\Controller\Page' => 'MelisCms\Controller\PageController',
            'MelisCms\Controller\PageProperties' => 'MelisCms\Controller\PagePropertiesController',
            'MelisCms\Controller\PageSeo' => 'MelisCms\Controller\PageSeoController',
            'MelisCms\Controller\PageEdition' => 'MelisCms\Controller\PageEditionController',
            'MelisCms\Controller\ToolTemplate' => 'MelisCms\Controller\ToolTemplateController',
            'MelisCms\Controller\Site' => 'MelisCms\Controller\SiteController',
            'MelisCms\Controller\Language' => 'MelisCms\Controller\LanguageController',
            'MelisCms\Controller\Platform' => 'MelisCms\Controller\PlatformController',
            'MelisCms\Controller\SiteRedirect' => 'MelisCms\Controller\SiteRedirectController',
            'MelisCms\Controller\PageDuplication' => 'MelisCms\Controller\PageDuplicationController',
        ),
    ),
    'form_elements' => array(
        'factories' => array(
    		'MelisCmsTemplateSelect' => 'MelisCms\Form\Factory\TemplateSelectFactory',
            'MelisCmsPlatformSelect' => 'MelisCms\Form\Factory\PlatformSelectFactory',
            'MelisMultiValInput' => 'MelisCms\Form\Factory\MelisMultiValueInputFactory',
            'MelisCmsPlatformIDsSelect' => 'MelisCms\Form\Factory\PlatformIDsCmsSelectFactory', 
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'template_map' => array(
            'layout/layoutCms'           => __DIR__ . '/../view/layout/layoutCms.phtml',
            'melis-cms/index/index' => __DIR__ . '/../view/melis-cms/index/index.phtml',
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
