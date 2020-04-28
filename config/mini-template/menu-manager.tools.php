<?php
return [
    'plugins' => [
        'meliscms' => [
            'tools' => [
                'meliscms_mini_template_menu_manager_tool' => [
                    'conf' => [
                        'title' => 'Menu manager',
                        'id' => 'menumanager',
                    ],
                    'table' => [
                        'target' => '#tableMiniTemplateMenuManagerPlugins',
                        'ajaxUrl' => '/melis/MelisCms/MiniTemplateMenuManager/getMiniTemplates',
                        'dataFunction' => 'initMiniTemplateMenuManagerPluginTables',
                        'ajaxCallback' => '',
                        'filters' => [
                            'left' => [],
                            'center' => [],
                            'right' => [
                                'mini-template-manager-tool-table-refresh' => [
                                    'module' => 'MelisCms',
                                    'controller' => 'MiniTemplateManager',
                                    'action' => 'render-mini-template-manager-tool-table-refresh',
                                ],
                            ]
                        ],
                        'columns' => [
                            'id' => [
                                'text' => '<i class="fa fa-plus"> </i> ',
                                'css' => array('width' => '1%', 'visible' => false),
                                'sortable' => true,
                            ],
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
                                'controller' => 'MiniTemplateManager',
                                'action' => 'render-mini-template-manager-tool-table-action-edit',
                            ],
                            'delete' => [
                                'module' => 'MelisCms',
                                'controller' => 'MiniTemplateManager',
                                'action' => 'render-mini-template-manager-tool-table-action-delete',
                            ],
                        ],
                    ],
                    'forms' => [
                        'menu_manager_tool_site_add_category' => [
                            'attributes' => [
                                'name' => 'menu_manager_tool_site_add_category',
                                'id' => 'id_menu_manager_tool_site_add_category',
                                'method' => 'POST',
                                'action' => ''
                            ],
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => [
                                [
                                    'spec' => [
                                        'name' => 'category_name',
                                        'type' => 'MelisText',
                                        'options' => [
                                            'label' => 'Category Name',
                                            'tooltip' => 'Site where the minitemplate will be created',
                                        ],
                                        'attributes' => [
                                            'id' => 'category_name',
                                            'value' => '',
                                        ],
                                    ],
                                ],
                            ],
                            'input_filter' => [

                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];