<?php 
    return array(
        'plugins' => array(
            'meliscms' => array(
                'ressources' => array(
                    'css' => array(
                    ),
                    'js' => array(
                    )
                ),
                'dashboard_plugins' => array(
                    'MelisCmsPagesIndicatorsPlugin' => array(
                        'plugin_id' => 'PagesIndicators',
                        'name' => 'tr_meliscms_dashboard_pages_indicators',
                        'description' => 'tr_meliscms_dashboard_pages_indicators_description',
                        'icon' => 'fa fa-sitemap',
                        'thumbnail' => '/MelisCms/plugins/images/MelisCmsPagesIndicatorsPlugin.jpg',
                        'jscallback' => '',
                        'height' => 4,
                        'section' => 'MelisCms',
                        'interface' => array(
                            'meliscms_page_indicators' => array(
                                'forward' => array(
                                    'module' => 'MelisCms',
                                    'plugin' => 'MelisCmsPagesIndicatorsPlugin',
                                    'function' => 'pageIndicators',
                                ),
                            ),
                        ),
                    )
                ),
            )
        ),
    );