<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'router' => array(
        'routes' => array(
        	'SiteSample-home' => array(
				'type'    => 'regex',
				'options' => array(
					'regex'    => '.*/SiteSample/.*/id/(?<idpage>[0-9]+)',
					'defaults' => array(
						'controller' => 'SiteSample\Controller\Index',
						'action'     => 'indexsite',
						),
					'spec' => '%idpage'
					)
			),
            'SiteSample-homepage' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller'     => 'MelisFront\Controller\Index',
                        'action'         => 'index',
                        'renderType'     => 'melis_zf2_mvc',
                        'renderMode'     => 'front',
                        'preview'        => false,
                        'idpage'         => 'homePageId'
                    )
                ),
            ),
            // The following is a route to simplify getting started creating
            // new controllers and actions without needing to create a new
            // module. Simply drop new controllers in, and you can access them
            // using the path /application/:controller/:action
            'applicationSiteSample' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/SiteSample',
                    'defaults' => array(
                        '__NAMESPACE__' => 'SiteSample\Controller',
                        'controller'    => 'Index',
                        'action'        => 'indexsite',
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
                ),
            ), 
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
    ),
    'translator' => array(
    ),
    'controllers' => array(
        'invokables' => array(
            'SiteSample\Controller\Index' => 'SiteSample\Controller\IndexController',
            'SiteSample\Controller\Page404' => 'SiteSample\Controller\Page404Controller'
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'controller_map' => array(
            'SiteSample' => true,
        ),
        'template_map' => array(
            'SiteSample/defaultLayout'  	=> __DIR__ . '/../view/layout/defaultLayout.phtml',
            'layout/errorLayout'            => __DIR__ . '/../view/error/404.phtml',
            
            // Errors layout
            'error/404'               		    => __DIR__ . '/../view/error/404.phtml',
            'error/index'             		    => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
