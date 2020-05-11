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
                                'text' => 'tr_meliscms_mini_template_manager_tool_table_image',
                                'css' => [],
                                'sortable' => false
                            ],
                            'html_path' => [
                                'text' => 'tr_meliscms_mini_template_manager_tool_table_path',
                                'css' => [],
                                'sortable' => false
                            ],
                        ],
                        'searchables' => [
                            'html_path'
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
                        'mini_template_manager_tool_add_form' => [
                            'attributes' => [
                                'name' => 'mini_template_manager_tool_add',
                                'id' => 'id_mini_template_manager_tool_add',
                                'method' => 'POST',
                                'action' => ''
                            ],
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => [
                                [
                                    'spec' => [
                                        'name' => 'miniTemplateSite',
                                        'type' => 'MelisCoreSiteSelect',
                                        'options' => [
                                            'label' => 'tr_meliscms_mini_template_manager_tool_form_site',
                                            'tooltip' => 'tr_meliscms_mini_template_manager_tool_form_site_tooltip',
                                            'label_options' => [
                                                'disable_html_escape' => true,
                                            ],
                                            'empty_option' => 'No site',
                                            'disable_inarray_validator' => true,
                                        ],
                                        'attributes' => [
                                            'id' => 'miniTemplateSite',
                                            'value' => '',
                                        ],
                                    ],
                                ],
                                [
                                    'spec' => [
                                        'name' => 'miniTemplateName',
                                        'type' => 'MelisText',
                                        'options' => [
                                            'label' => 'tr_meliscms_mini_template_manager_tool_form_name',
                                            'tooltip' => 'tr_meliscms_mini_template_manager_tool_form_name_tooltip',
                                            'label_options' => [
                                                'disable_html_escape' => true,
                                            ],
                                        ],
                                        'attributes' => [
                                            'id' => 'miniTemplateName',
                                            'value' => ''
                                        ],
                                    ],
                                ],
                                [
                                    'spec' => [
                                        'name' => 'miniTemplateHtml',
                                        'type' => 'TextArea',
                                        'options' => [
                                            'label' => 'tr_meliscms_mini_template_manager_tool_form_html',
                                            'tooltip' => 'tr_meliscms_mini_template_manager_tool_form_html_tooltip',
                                            'label_options' => [
                                                'disable_html_escape' => true,
                                            ],
                                        ],
                                        'attributes' => [
                                            'id' => 'miniTemplateHTML',
                                            'value' => '',
                                            'class' => 'form-control editme',
                                            'style' => 'max-width:100%',
                                            'rows' => '4',
                                        ],
                                    ]
                                ],
                                [
                                    'spec' => [
                                        'name' => 'miniTemplateThumbnail',
                                        'type' => 'file',
                                        'options' => [
                                            'label' => 'tr_meliscms_mini_template_manager_tool_form_thumbnail',
                                            'tooltip' => 'tr_meliscms_mini_template_manager_tool_form_thumbnail_tool_tip',
                                            'label_options' => [
                                                'disable_html_escape' => true,
                                            ],
                                        ],
                                        'attributes' => [
                                            'id' => 'miniTemplateThumbnail',
                                            'accept' => '.gif,.jpg,.jpeg,.png',
                                            'value' => '',
                                            'placeholder' => 'tr_meliscms_mini_template_manager_tool_table_image',
                                            'onchange' => '',
                                            'class' => 'filestyle',
                                            'data-buttonText' => 'tr_meliscms_mini_template_manager_tool_form_thumbnail_btn_text',
                                        ],
                                    ],
                                ],
                            ],
                            'input_filter' => [
                                'miniTemplateSite' => [
                                    'name' => 'miniTemplateSite',
                                    'required' => true,
                                    'validators' => [
                                        [
                                            'name' => 'NotEmpty',
                                            'options' => [
                                                'messages' => [
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_mini_template_manager_tool_form_empty_field',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'filters' => [
                                        ['name' => 'StripTags'],
                                        ['name' => 'StringTrim'],
                                    ],
                                ],
                                'miniTemplateName' => [
                                    'name' => 'miniTemplateName',
                                    'required' => true,
                                    'validators' => [
                                        [
                                            'name' => 'NotEmpty',
                                            'options' => [
                                                'messages' => [
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_mini_template_manager_tool_form_empty_field',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'filters' => [

                                    ],
                                ],
                                'miniTemplateHtml' => [
                                    'name' => 'miniTemplateHtml',
                                    'required' => true,
                                    'validators' => [
                                        [
                                            'name' => 'NotEmpty',
                                            'options' => [
                                                'messages' => [
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_mini_template_manager_tool_form_empty_field',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'filters' => [

                                    ],
                                ],
                                'miniTemplateThumbnail' => [
                                    'name' => 'miniTemplateThumbnail',
                                    'required' => false,
                                    'validators' => [
                                        array(
                                            'name' => 'FileExtension',
                                            'break_chain_on_failure' => true,
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\File\Extension::FALSE_EXTENSION => 'tr_melis_cms_page_tree_import_wrong_extension',
                                                ),
                                                'case' => true,
                                                'extension' => [
                                                    'png', 'PNG', 'jpg', 'JPG', 'jpeg', 'JPEG', 'gif', 'GIF'
                                                ]
                                            ),
                                        ),
                                    ],
                                    'filters' => [

                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];