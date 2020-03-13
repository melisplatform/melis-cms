<?php
return [
    'plugins' => [
        'meliscms' => [
            'interface' => [
                'meliscms_mini_template_manager' => [
                    'interface' => [
                        'meliscms_mini_template_menu_manager_tool' => [
                            'conf' => [
                                'id' =>  'id_meliscms_mini_template_menu_manager_tool',
                                'name' => 'tr_meliscms_mini_template_menu_manager_tool',
                                'melisKey' => 'meliscms_mini_template_menu_manager_tool',
                                'icon' => 'fa-share',
                                'rights_checkbox_disable' => true,
                                'follow_regular_rendering' => false,
                            ],
                            'forward' => [
                                'module' => 'MelisCms',
                                'controller' => 'SiteRedirect',
                                'action' => 'render-tool-site-redirect',
                            ],
                            'interface' => [

                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];