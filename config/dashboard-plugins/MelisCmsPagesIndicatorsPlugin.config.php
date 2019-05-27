<?php
    return array(
        'plugins' => array(
            'meliscore' => [
                'interface' => [
                    'melis_dashboardplugin' => [
                        'conf' => [
                            'dashboard_plugin' => true
                        ],
                        'interface' => [
                            'melisdashboardplugin_section' => [
                                'interface' => [
                                    'MelisCmsPagesIndicatorsPlugin' => [
                                        'conf' => [
                                            'type' => '/meliscms/interface/MelisCmsPagesIndicatorsPlugin'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'meliscms' => array(
                'ressources' => array(
                    'css' => array(
                    ),
                    'js' => array(
                    )
                ),
                'interface' => [
                    'MelisCmsPagesIndicatorsPlugin' => array(
                        'conf' => [
                            'name' => 'MelisCmsPagesIndicatorsPlugin',
                            'melisKey' => 'MelisCmsPagesIndicatorsPlugin'
                        ],
                        'datas' => [
                            'plugin_id' => 'PagesIndicators',
                            'name' => 'tr_meliscms_dashboard_pages_indicators',
                            'description' => 'tr_meliscms_dashboard_pages_indicators_description',
                            'icon' => 'fa fa-sitemap',
                            'thumbnail' => '/MelisCms/plugins/images/MelisCmsPagesIndicatorsPlugin.jpg',
                            'jscallback' => '',
                            'max_lines' => 8,
                            'height' => 4,
                            'width' => 6,
                            'x-axis' => 0,
                            'y-axis' => 0,
                            /*
                             * if set this plugin will belong to a specific marketplace section,
                               * if not it will go directly to ( Others ) section
                               *  - available section for dashboard plugins as of 2019-05-16
                               *    - MelisCore
                               *    - MelisCms
                               *    - MelisMarketing
                               *    - MelisSite
                               *    - MelisCommerce
                               *    - Others
                               *    - CustomProjects
                             */
                            'section' => 'MelisCms',
                        ],
                        'forward' => array(
                            'module' => 'MelisCms',
                            'plugin' => 'MelisCmsPagesIndicatorsPlugin',
                            'function' => 'pageIndicators',
                            'jscallback' => '',
                            'jsdatas' => array()
                        ),
                    ),
                ],
            ),
        ),
    );