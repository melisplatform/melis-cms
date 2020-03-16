<?php
return [
    'plugins' => [
        'meliscms' => [
            'tools' => [
                'meliscms_mini_template_manager_tool' => [
                    'conf' => [
                        'title' => 'tr_meliscms_mini_template_manager_tool',
                        'id' => 'id_meliscms_mini_template_manager_tool',
                    ],
                    'table' => [
                        'target' => '#tableMiniTemplateManager',
                        'ajaxUrl' => '/melis/MelisCms/MiniTemplateManager/getMiniTemplates',
                        'dataFunction' => 'initMiniTemplateManagerToolTableSites',
                        'ajaxCallback' => 'miniTemplateManagerToolTableCallback()',
                        'filters' => [
                            'left' => [
                                'mini-template-manager-tool-table-limit' => [
                                    'module' => 'MelisCms',
                                    'controller' => 'MiniTemplateManager',
                                    'action' => 'render-mini-template-manager-tool-table-limit',
                                ],
                                'mini-template-manager-tool-table-sites' => [
                                    'module' => 'MelisCms',
                                    'controller' => 'MiniTemplateManager',
                                    'action' => 'render-mini-template-manager-tool-table-sites',
                                ],
                            ],
                            'center' => [
                                'mini-template-manager-tool-table-search' => [
                                    'module' => 'MelisCms',
                                    'controller' => 'MiniTemplateManager',
                                    'action' => 'render-mini-template-manager-tool-table-search',
                                ],
                            ],
                            'right' => [
                                'mini-template-manager-tool-table-refresh' => [
                                    'module' => 'MelisCms',
                                    'controller' => 'MiniTemplateManager',
                                    'action' => 'render-mini-template-manager-tool-table-refresh',
                                ],
                            ]
                        ],
                        'columns' => [
                            'image' => [
                                'text' => 'Image',
                                'css' => [],
                                'sortable' => true
                            ],
                            'html_path' => [
                                'text' => 'Path',
                                'css' => [],
                                'sortable' => true
                            ],
                        ],
                        'searchables' => [

                        ],
                        'actionButtons' => [
                            'edit' => [
                                'module' => 'MelisCms',
                                'controller' => 'Sites',
                                'action' => 'render-tool-sites-content-action-edit',
                            ],
                            'delete' => [
                                'module' => 'MelisCms',
                                'controller' => 'Sites',
                                'action' => 'render-tool-sites-content-action-delete',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];