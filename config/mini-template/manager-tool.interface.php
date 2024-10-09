<?php
return [
    'plugins' => [
        'meliscms' => [
            'interface' => [
                'meliscms_mini_template_manager' => [
                    'conf' => [
                        'id' =>  'id_meliscms_mini_template_manager',
                        'name' => 'tr_meliscms_mini_template_manager',
                        'melisKey' => 'meliscms_mini_template_manager',
                        'icon' => 'fa-tasks',
                        'rights_checkbox_disable' => false,
                        'follow_regular_rendering' => false,
                        'rightsDisplay' => 'none',
                    ],
                    'interface' => [
                        // Mini template manager tool
                        'meliscms_mini_template_manager_tool' => [
                            'conf' => [
                                'id' =>  'id_meliscms_mini_template_manager_tool',
                                'name' => 'tr_meliscms_mini_template_manager_tool',
                                'melisKey' => 'meliscms_mini_template_manager_tool',
                                'icon' => 'fa-tasks',
                                'rights_checkbox_disable' => false,
                                'follow_regular_rendering' => true,
                            ],
                            'forward' => [
                                'module' => 'MelisCms',
                                'controller' => 'MiniTemplateManager',
                                'action' => 'render-mini-template-manager-tool',
                            ],
                            'interface' => [
                                // Header
                                'meliscms_mini_template_manager_tool_header' => [
                                    'conf' => [
                                        'id' =>  'id_meliscms_mini_template_manager_tool_header',
                                        'name' => 'tr_meliscms_mini_template_manager_tool_header',
                                        'melisKey' => 'meliscms_mini_template_manager_tool_header',
                                    ],
                                    'forward' => [
                                        'module' => 'MelisCms',
                                        'controller' => 'MiniTemplateManager',
                                        'action' => 'render-mini-template-manager-tool-header',
                                    ],
                                    'interface' => [
                                        // Header - Add button
                                        'meliscms_mini_template_manager_tool_header_add_btn' => [
                                            'conf' => [
                                                'id' =>  'id_meliscms_mini_template_manager_tool_header_add_btn',
                                                'name' => 'tr_meliscms_mini_template_manager_tool_header_add_btn',
                                                'melisKey' => 'meliscms_mini_template_manager_tool_header_add_btn',
                                            ],
                                            'forward' => [
                                                'module' => 'MelisCms',
                                                'controller' => 'MiniTemplateManager',
                                                'action' => 'render-mini-template-manager-tool-header-add-btn',
                                            ],
                                        ],
                                    ],
                                ],
                                // Body/content
                                'meliscms_mini_template_manager_tool_body' => [
                                    'conf' => [
                                        'id' =>  'id_meliscms_mini_template_manager_tool_body',
                                        'name' => 'tr_meliscms_mini_template_manager_tool_body',
                                        'melisKey' => 'meliscms_mini_template_manager_tool_body',
                                    ],
                                    'forward' => [
                                        'module' => 'MelisCms',
                                        'controller' => 'MiniTemplateManager',
                                        'action' => 'render-mini-template-manager-tool-body',
                                    ],
                                    'interface' => [
                                        // Data table
                                        'meliscms_mini_template_manager_tool_body_data_table' => [
                                            'conf' => [
                                                'id' =>  'id_meliscms_mini_template_manager_tool_body_data_table',
                                                'name' => 'tr_meliscms_mini_template_manager_tool_body_data_table',
                                                'melisKey' => 'meliscms_mini_template_manager_tool_body_data_table',
                                            ],
                                            'forward' => [
                                                'module' => 'MelisCms',
                                                'controller' => 'MiniTemplateManager',
                                                'action' => 'render-mini-template-manager-tool-body-data-table',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        // Mini template manager tool add mini template
                        'meliscms_mini_template_manager_tool_add' => [
                            'conf' => [
                                'id' =>  'id_meliscms_mini_template_manager_tool_add',
                                'name' => 'tr_meliscms_mini_template_manager_tool_add',
                                'melisKey' => 'meliscms_mini_template_manager_tool_add',
                                'rights_checkbox_disable' => false,
                                'icon' => 'fa-tasks',
                            ],
                            'forward' => [
                                'module' => 'MelisCms',
                                'controller' => 'MiniTemplateManager',
                                'action' => 'render-mini-template-manager-tool-add',
                            ],
                            'interface' => [
                                // Header
                                'meliscms_mini_template_manager_tool_add_header' => [
                                    'conf' => [
                                        'id' =>  'id_meliscms_mini_template_manager_tool_add_header',
                                        'name' => 'tr_meliscms_mini_template_manager_tool_add_header',
                                        'melisKey' => 'meliscms_mini_template_manager_tool_add_header',
                                    ],
                                    'forward' => [
                                        'module' => 'MelisCms',
                                        'controller' => 'MiniTemplateManager',
                                        'action' => 'render-mini-template-manager-tool-add-header',
                                    ],
                                ],
                                // Body
                                'meliscms_mini_template_manager_tool_add_body' => [
                                    'conf' => [
                                        'id' =>  'id_meliscms_mini_template_manager_tool_add_body',
                                        'name' => 'tr_meliscms_mini_template_manager_tool_add_body',
                                        'melisKey' => 'meliscms_mini_template_manager_tool_add_body',
                                    ],
                                    'forward' => [
                                        'module' => 'MelisCms',
                                        'controller' => 'MiniTemplateManager',
                                        'action' => 'render-mini-template-manager-tool-add-body',
                                    ],
                                    'interface' => [
                                        // Body - form
                                        'meliscms_mini_template_manager_tool_add_body_form' => [
                                            'conf' => [
                                                'id' =>  'id_meliscms_mini_template_manager_tool_add_body_form',
                                                'name' => 'tr_meliscms_mini_template_manager_tool_add_body_form',
                                                'melisKey' => 'meliscms_mini_template_manager_tool_add_body_form',
                                            ],
                                            'forward' => [
                                                'module' => 'MelisCms',
                                                'controller' => 'MiniTemplateManager',
                                                'action' => 'render-mini-template-manager-tool-add-body-form',
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
                    '/MelisCms/js/tools/mini-template/manager-tool.js',
                ],
                'css' => [
                    '/MelisCms/css/tools/mini-template/manager-tool.css',
                ],
            ],
        ],
    ],
];