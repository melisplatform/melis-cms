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
                                'icon' => 'fa-bars',
                                'rights_checkbox_disable' => false,
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
                                'meliscms_mini_template_menu_manager_tool_add_category_container' => [
                                    'conf' => [
                                        'id' =>  'id_meliscms_mini_template_menu_manager_tool_add_category_container',
                                        'name' => 'tr_meliscms_mini_template_menu_manager_tool_add_category_container',
                                        'melisKey' => 'meliscms_mini_template_menu_manager_tool_add_category_container',
                                    ],
                                    'forward' => [
                                        'module' => 'MelisCms',
                                        'controller' => 'MiniTemplateMenuManager',
                                        'action' => 'render-menu-manager-tool-add-category-container',
                                    ],
                                    'interface' => [
                                        // Header zone
                                        'meliscms_mini_template_menu_manager_tool_add_category_header' => [
                                            'conf' => [
                                                'id' =>  'id_meliscms_mini_template_menu_manager_tool_add_category_header',
                                                'name' => 'tr_meliscms_mini_template_menu_manager_tool_add_category_header',
                                                'melisKey' => 'meliscms_mini_template_menu_manager_tool_add_category_header',
                                            ],
                                            'forward' => [
                                                'module' => 'MelisCms',
                                                'controller' => 'MiniTemplateMenuManager',
                                                'action' => 'render-menu-manager-tool-add-category-header',
                                            ],
                                        ],
                                        // Body zone
                                        'meliscms_mini_template_menu_manager_tool_add_category_body' => [
                                            'conf' => [
                                                'id' =>  'id_meliscms_mini_template_menu_manager_tool_add_category_body',
                                                'name' => 'tr_meliscms_mini_template_menu_manager_tool_add_category_body',
                                                'melisKey' => 'meliscms_mini_template_menu_manager_tool_add_category_body',
                                            ],
                                            'forward' => [
                                                'module' => 'MelisCms',
                                                'controller' => 'MiniTemplateMenuManager',
                                                'action' => 'render-menu-manager-tool-add-category-body',
                                            ],
                                            'interface' => [
                                                // Tabs
                                                'meliscms_mini_template_menu_manager_tool_add_category_body_tabs' => [
                                                    'conf' => [
                                                        'id' =>  'id_meliscms_mini_template_menu_manager_tool_add_category_body_tabs',
                                                        'name' => 'tr_meliscms_mini_template_menu_manager_tool_add_category_body_tabs',
                                                        'melisKey' => 'meliscms_mini_template_menu_manager_tool_add_category_body_tabs',
                                                    ],
                                                    'forward' => [
                                                        'module' => 'MelisCms',
                                                        'controller' => 'MiniTemplateMenuManager',
                                                        'action' => 'render-menu-manager-tool-add-category-body-tabs',
                                                    ],
                                                    'interface' => [
                                                        // Properties Tab
                                                        'meliscms_mini_template_menu_manager_tool_add_category_body_properties_tab' => [
                                                            'conf' => [
                                                                'id' =>  'id_meliscms_mini_template_menu_manager_tool_add_category_body_properties_tab',
                                                                'name' => 'tr_meliscms_mini_template_menu_manager_tool_add_category_body_properties_tab',
                                                                'melisKey' => 'meliscms_mini_template_menu_manager_tool_add_category_body_properties_tab',
                                                                'icon' => 'glyphicons tag'
                                                            ],
                                                        ],
                                                        // Plugins Tab
                                                        'meliscms_mini_template_menu_manager_tool_add_category_body_plugins_tab' => [
                                                            'conf' => [
                                                                'id' =>  'id_meliscms_mini_template_menu_manager_tool_add_category_body_plugins_tab',
                                                                'name' => 'tr_meliscms_mini_template_menu_manager_tool_add_category_body_plugins_tab',
                                                                'melisKey' => 'meliscms_mini_template_menu_manager_tool_add_category_body_plugins_tab',
                                                                'icon' => 'glyphicons tag'
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                                // Contents
                                                'meliscms_mini_template_menu_manager_tool_add_category_body_contents' => [
                                                    'conf' => [
                                                        'id' =>  'id_meliscms_mini_template_menu_manager_tool_add_category_body_contents',
                                                        'name' => 'tr_meliscms_mini_template_menu_manager_tool_add_category_body_contents',
                                                        'melisKey' => 'meliscms_mini_template_menu_manager_tool_add_category_body_contents',
                                                    ],
                                                    'forward' => [
                                                        'module' => 'MelisCms',
                                                        'controller' => 'MiniTemplateMenuManager',
                                                        'action' => 'render-menu-manager-tool-add-category-body-contents',
                                                    ],
                                                    'interface' => [
                                                        // Properties Content
                                                        'meliscms_mini_template_menu_manager_tool_add_category_body_properties_content' => [
                                                            'conf' => [
                                                                'id' =>  'id_meliscms_mini_template_menu_manager_tool_add_category_body_properties_content',
                                                                'name' => 'tr_meliscms_mini_template_menu_manager_tool_add_category_body_properties_content',
                                                                'melisKey' => 'meliscms_mini_template_menu_manager_tool_add_category_body_properties_content',
                                                            ],
                                                            'forward' => [
                                                                'module' => 'MelisCms',
                                                                'controller' => 'MiniTemplateMenuManager',
                                                                'action' => 'render-menu-manager-tool-add-category-body-properties-content',
                                                            ],
                                                            'interface' => [
                                                                'meliscms_mini_template_menu_manager_tool_add_category_body_properties_form' => [
                                                                    'conf' => [
                                                                        'id' =>  'id_meliscms_mini_template_menu_manager_tool_add_category_body_properties_form',
                                                                        'name' => 'tr_meliscms_mini_template_menu_manager_tool_add_category_body_properties_form',
                                                                        'melisKey' => 'meliscms_mini_template_menu_manager_tool_add_category_body_properties_form',
                                                                    ],
                                                                    'forward' => [
                                                                        'module' => 'MelisCms',
                                                                        'controller' => 'MiniTemplateMenuManager',
                                                                        'action' => 'render-menu-manager-tool-add-category-body-properties-form',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                        // Plugins Content
                                                        'meliscms_mini_template_menu_manager_tool_add_category_body_plugins_content' => [
                                                            'conf' => [
                                                                'id' =>  'id_meliscms_mini_template_menu_manager_tool_add_category_body_plugins_content',
                                                                'name' => 'tr_meliscms_mini_template_menu_manager_tool_add_category_body_plugins_content',
                                                                'melisKey' => 'meliscms_mini_template_menu_manager_tool_add_category_body_plugins_content',
                                                            ],
                                                            'forward' => [
                                                                'module' => 'MelisCms',
                                                                'controller' => 'MiniTemplateMenuManager',
                                                                'action' => 'render-menu-manager-tool-add-category-body-plugins-content',
                                                            ],
                                                            'interface' => [
                                                                'meliscms_mini_template_menu_manager_tool_add_category_body_plugins_table' => [
                                                                    'conf' => [
                                                                        'id' =>  'id_meliscms_mini_template_menu_manager_tool_add_category_body_plugins_table',
                                                                        'name' => 'tr_meliscms_mini_template_menu_manager_tool_add_category_body_plugins_table',
                                                                        'melisKey' => 'meliscms_mini_template_menu_manager_tool_add_category_body_plugins_table',
                                                                    ],
                                                                    'forward' => [
                                                                        'module' => 'MelisCms',
                                                                        'controller' => 'MiniTemplateMenuManager',
                                                                        'action' => 'render-menu-manager-tool-add-category-body-plugins-table',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ]
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
                    '/MelisCms/css/tools/mini-template/mini-template-menu-manager.css',
                ],
            ],
        ],
    ],
];