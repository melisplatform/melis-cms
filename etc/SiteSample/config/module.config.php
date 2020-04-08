<?php

return [
    'router' => [
        'routes' => [
            'SiteSample-home' => [
                'type'    => 'regex',
                'options' => [
                    'regex'    => '.*/SiteSample/.*/id/(?<idpage>[0-9]+)',
                    'defaults' => [
                        'controller' => 'SiteSample\Controller\Index',
                        'action'     => 'indexsite',
                    ],
                    'spec' => '%idpage'
                ]
            ],
            'SiteSample-homepage' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller'     => 'MelisFront\Controller\Index',
                        'action'         => 'index',
                        'renderType'     => 'melis_zf2_mvc',
                        'renderMode'     => 'front',
                        'preview'        => false,
                        'idpage'         => 'homePageId'
                    ]
                ],
            ],
            // The following is a route to simplify getting started creating
            // new controllers and actions without needing to create a new
            // module. Simply drop new controllers in, and you can access them
            // using the path /application/:controller/:action
            'applicationSiteSample' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/SiteSample',
                    'defaults' => [
                        '__NAMESPACE__' => 'SiteSample\Controller',
                        'controller'    => 'Index',
                        'action'        => 'indexsite',
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
                ],
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'SiteSample\Controller\Home'    => SiteSample\Controller\HomeController::class,
            'SiteSample\Controller\Page404' => SiteSample\Controller\HomeController::class
        ],
    ],
    'view_manager' => [
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'controller_map' => [
            'SiteSample' => true,
        ],
        'template_map' => [
            'SiteSample/defaultLayout'  => __DIR__ . '/../view/layout/defaultLayout.phtml',
            'layout/errorLayout'        => __DIR__ . '/../view/error/404.phtml',

            // Errors layout
            'error/404'     => __DIR__ . '/../view/error/404.phtml',
            'error/index'   => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];