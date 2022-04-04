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
                                'sortable' => true
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
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
                            'elements' => [
                                [
                                    'spec' => [
                                        'name' => 'miniTemplateSiteModule',
                                        'type' => 'select',
                                        'options' => [
                                            'label' => 'tr_meliscms_mini_template_manager_tool_form_site_module',
                                            'tooltip' => 'tr_meliscms_mini_template_manager_tool_form_site_tooltip',
                                            'label_options' => [
                                                'disable_html_escape' => true,
                                            ],
                                            'empty_option' => 'No site',
                                            'disable_inarray_validator' => true,
                                        ],
                                        'attributes' => [
                                            'id' => 'miniTemplateSiteModule',
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
                                        'type' => 'Textarea',
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
                                            'class' => 'form-control',
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
                                            'onchange' => 'thumbnailPreview(".new-minitemplate-thumbnail", this);',
                                            'class' => 'miniTemplateThumbnail',
                                            'data-buttonText' => 'tr_meliscms_mini_template_manager_tool_form_thumbnail_btn_text',
                                        ],
                                    ],
                                ],
                            ],
                            'input_filter' => [
                                'miniTemplateSiteModule' => [
                                    'name' => 'miniTemplateSiteModule',
                                    'required' => true,
                                    'validators' => [
                                        [
                                            'name' => 'NotEmpty',
                                            'options' => [
                                                'messages' => [
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_mini_template_manager_tool_form_empty_field',
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
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_mini_template_manager_tool_form_empty_field',
                                                ],
                                            ],
                                        ],
                                        [
                                            'name' => 'regex', false,
                                            'options' => array(
                                                'pattern' => '/^[a-zA-Z0-9_-]*$/',
                                                'messages' => array(\Laminas\Validator\Regex::NOT_MATCH => 'tr_meliscms_mini_template_form_invalid_name'),
                                                'encoding' => 'UTF-8',
                                            ),
                                        ]
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
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_mini_template_manager_tool_form_empty_field',
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
                                                    \Laminas\Validator\File\Extension::FALSE_EXTENSION => 'tr_melis_cms_page_tree_import_wrong_extension',
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