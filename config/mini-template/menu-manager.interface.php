<?php
return [
    'plugins' => [
        'meliscms' => [
            'interface' => [
                // Main zone
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
                                'controller' => 'MiniTemplateMenuManager',
                                'action' => 'render-menu-manager-tool',
                            ],
                            'interface' => [
                                // Header zone
                                'meliscms_mini_template_menu_manager_tool_header' => [
                                    'conf' => [
                                        'id' =>  'id_meliscms_mini_template_menu_manager_tool_header',
                                        'name' => 'tr_meliscms_mini_template_menu_manager_tool_header',
                                        'melisKey' => 'meliscms_mini_template_menu_manager_tool_header',
                                    ],
                                    'forward' => [
                                        'module' => 'MelisCms',
                                        'controller' => 'MiniTemplateMenuManager',
                                        'action' => 'render-menu-manager-tool-header',
                                    ],
                                ],
                                // Body zone
                                'meliscms_mini_template_menu_manager_tool_body' => [
                                    'conf' => [
                                        'id' =>  'id_meliscms_mini_template_menu_manager_tool_body',
                                        'name' => 'tr_meliscms_mini_template_menu_manager_tool_body',
                                        'melisKey' => 'meliscms_mini_template_menu_manager_tool_body',
                                    ],
                                    'forward' => [
                                        'module' => 'MelisCms',
                                        'controller' => 'MiniTemplateMenuManager',
                                        'action' => 'render-menu-manager-tool-body',
                                    ],
                                    'interface' => [
                                        // left side zone
                                        'meliscms_mini_template_menu_manager_tool_body_left' => [
                                            'conf' => [
                                                'id' =>  'id_meliscms_mini_template_menu_manager_tool_body_left',
                                                'name' => 'tr_meliscms_mini_template_menu_manager_tool_body_left',
                                                'melisKey' => 'meliscms_mini_template_menu_manager_tool_body_left',
                                            ],
                                            'forward' => [
                                                'module' => 'MelisCms',
                                                'controller' => 'MiniTemplateMenuManager',
                                                'action' => 'render-menu-manager-tool-body-left',
                                            ],
                                        ],
                                        // right size zone
                                        'meliscms_mini_template_menu_manager_tool_body_right' => [
                                            'conf' => [
                                                'id' =>  'id_meliscms_mini_template_menu_manager_tool_body_right',
                                                'name' => 'tr_meliscms_mini_template_menu_manager_tool_body_right',
                                                'melisKey' => 'meliscms_mini_template_menu_manager_tool_body_right',
                                            ],
                                            'forward' => [
                                                'module' => 'MelisCms',
                                                'controller' => 'MiniTemplateMenuManager',
                                                'action' => 'render-menu-manager-tool-body-right',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'ressources' => [
                'js' => [
                    '/MelisCms/js/tools/mini-template/menu-manager-tool.js',
                ],
                'css' => [
                    '',
                ],
            ],
        ],
    ],
];