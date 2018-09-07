<?php

return array(
    'plugins' => array(
        'meliscms' => array(
            'tools' => array(
                'meliscms_tool_templates' => array(
                    'conf' => array(
                        'title' => 'tr_meliscms_tool_templates',
                        'id' => 'id_meliscms_tool_templates',
                    ),
                    'table' => array(
                        // table ID
                        'target' => '#tableToolTemplateManager',
                        'ajaxUrl' => '/melis/MelisCms/ToolTemplate/getToolTemplateData',
                        'dataFunction' => 'initTemplateList',
                        'ajaxCallback' => '',
                        'filters' => array(
                            'left' => array(
                                'toolTemplates-limit' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'ToolTemplate',
                                    'action' => 'render-tool-template-content-filters-limit',
                                ),
                                'toolTemplates-sites' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'ToolTemplate',
                                    'action' => 'render-tool-template-content-filters-sites',
                                ),
                            ),
                            'center' => array(
                                'toolTemplates-search' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'ToolTemplate',
                                    'action' => 'render-tool-template-content-filters-search',
                                ),
                            ),
                            'right' => array(
                                'toolTemplates-export' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'ToolTemplate',
                                    'action' => 'render-tool-template-content-filters-export',
                                ),
                                'toolTemplates-refresh' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'ToolTemplate',
                                    'action' => 'render-tool-template-content-filters-refresh',
                                ),
                            ),
                        ),
                        'columns' => array(
                            'tpl_id' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_id',
                                'css' => array('width' => '5%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),
                            
                            'tpl_status' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_status',
                                'css' => array('width' => '5%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),

                            'site_name' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_site_id',
                                'css' => array('width' => '20%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),

                            'tpl_name' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_name',
                                'css' => array('width' => '20%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),

                            'tpl_zf2_layout' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_zf2_layout',
                                'css' => array('width' => '20%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),

                            'tpl_zf2_controller' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_zf2_controller',
                                'css' => array('width' => '20%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),
                        ),

                        // define what columns can be used in searching
                        'searchables' => array('tpl_id', 'site_name', 'tpl_name', 'tpl_zf2_website_folder', 'tpl_zf2_layout', 'tpl_site_id', 'tpl_type', 'tpl_zf2_controller', 'tpl_php_path'),

                        'actionButtons' => array(
                            'edit' => array(
                                'module' => 'MelisCms',
                                'controller' => 'ToolTemplate',
                                'action' => 'render-tool-templates-action-edit',
                            ),
                            'delete' => array(
                                'module' => 'MelisCms',
                                'controller' => 'ToolTemplate',
                                'action' => 'render-tool-templates-action-delete',
                            ),
                        )
                    ),
                    'export' => array(
                        'csvFileName' => 'meliscms_templates_export.csv',
                    ),
                    // define what columns can be used in searching
                    'modals' => array(
                        'meliscms_tool_template_add_modal' => array(
                            'id' => 'id_modal_tool_template_add',
                            'class' => 'glyphicons plus',
                            'tab-header' => '',
                            'tab-text' => 'tr_tool_templates_modal_tab_text_add',
                            'content' => array(
                                'module' => 'MelisCms',
                                'controller' => 'ToolTemplate',
                                'action' => 'modal-tab-tool-template-add',
                            ),
                        ),
                        'meliscms_tool_template_edit_modal' => array(
                            'id' => 'id_modal_tool_template_edit',
                            'class' => 'glyphicons pencil',
                            'tab-header' => '',
                            'tab-text' => 'tr_tool_templates_modal_tab_text_edit',
                            'content' => array(
                                'module' => 'MelisCms',
                                'controller' => 'ToolTemplate',
                                'action' => 'modal-tab-tool-template-edit',
                            ),
                        ),
                        'meliscms_tool_prospects_empty_modal' => array( // will be used when a user doesn't have access to the modals
                            'id' => 'id_meliscms_tool_templates_empty_modal',
                            'class' => 'glyphicons pencil',
                            'tab-header' => '',
                            'tab-text' => 'tr_tool_text_templates_manager_empty_modal',
                            'content' => array(
                                'module' => 'MelisCms',
                                'controller' => 'ToolTemplate',
                                'action' => 'render-tool-templates-modal-empty-handler'
                            ),
                        ),
                    ),
                    'forms' => array(
                        'meliscms_tool_template_generic_form' => array(
                            'attributes' => array(
                                'name' => 'tool_template_generic_form',
                                'id' => 'id_tool_template_generic_form',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'tpl_id',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_template_form_tpl_id',
                                            'tooltip' => 'tr_meliscms_template_form_tpl_id tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_id',
                                            'value' => '',
                                            'disabled' => 'disabled'
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'tpl_site_id',
                                        'type' => 'MelisCoreSiteSelect',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_templates_tpl_site_id',
                                            'tooltip' => 'tr_meliscms_tool_templates_tpl_site_id tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_site_id',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'tpl_name',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_template_form_tpl_name',
                                            'tooltip' => 'tr_meliscms_template_form_tpl_name tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_name',
                                            'value' => '',
                                            'maxlength' => 255,
                                            'required' => 'required'
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'tpl_type',
                                        'type' => 'hidden',
                                        'options' => array(
                                            'label' => 'tr_meliscms_template_form_tpl_type',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_type',
                                            'value' => 'ZF2',
                                        ),
                                    ),
                                ),
//                                 array(
//                                     'spec' => array(
//                                         'name' => 'tpl_zf2_website_folder',
//                                         'type' => 'MelisText',
//                                         'options' => array(
//                                             'label' => 'tr_meliscms_template_form_tpl_zf2_website_folder',
//                                         ),
//                                         'attributes' => array(
//                                             'id' => 'id_tpl_zf2_website_folder',
//                                             'value' => '',
//                                             'maxlength' => 255,
//                                             'required' => 'required'
//                                         ),
//                                     ),
//                                 ),
                                array(
                                    'spec' => array(
                                        'name' => 'tpl_zf2_layout',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_template_form_tpl_zf2_layout',
                                            'tooltip' => 'tr_meliscms_template_form_tpl_zf2_layout tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_zf2_layout',
                                            'value' => '',
                                            'maxlength' => 255,
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'tpl_zf2_controller',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_template_form_tpl_zf2_controller',
                                            'tooltip' => 'tr_meliscms_template_form_tpl_zf2_controller tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_zf2_controller',
                                            'value' => '',
                                            'maxlength' => 255,
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'tpl_zf2_action',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_template_form_tpl_zf2_action',
                                            'tooltip' => 'tr_meliscms_template_form_tpl_zf2_action tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_zf2_action',
                                            'value' => '',
                                            'maxlength' => 255,
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
//                                 array(
//                                     'spec' => array(
//                                         'name' => 'tpl_php_path',
//                                         'type' => 'MelisText',
//                                         'options' => array(
//                                             'label' => 'tr_meliscms_template_form_tpl_php_path',
//                                         ),
//                                         'attributes' => array(
//                                             'id' => 'id_tpl_php_path',
//                                             'value' => '',
//                                             'maxlength' => 255,
//                                         ),
//                                     ),
//                                 ),



                            ), // end elements
                            'input_filter' => array(
                                'tpl_id' => array(
                                    'name'     => 'tpl_id',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name'    => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => '',
                                                    \Zend\I18n\Validator\IsInt::INVALID => '',
                                                )
                                            )
                                        ),
                                    ),
                                    'filters' => array(
                                    ),
                                ),
                                'tpl_name' => array(
                                    'name'     => 'tpl_name',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                //'min'      => 5,
                                                'max'      => 255,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_template_form_tpl_name_error_high',
                                                    //    \Zend\Validator\StringLength::TOO_SHORT => 'tr_meliscms_template_form_tpl_name_error_low',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_template_form_tpl_name_error_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'tpl_site_id' => array(
                                    'name' => 'tpl_site_id',
                                    'required' => false,
                                    'validators' => array(),
                                    'filters' => array(),
                                ),
                                'tpl_type' => array(
                                    'name'     => 'tpl_type',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'InArray',
                                            'options' => array(
                                                'haystack' => array('PHP', 'ZF2'),
                                                'messages' => array(
                                                    \Zend\Validator\InArray::NOT_IN_ARRAY => 'tr_meliscms_template_form_tpl_type_error_invalid_select',
                                                ),
                                            )
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_template_form_tpl_type_error_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                    ),
                                ),
//                                 'tpl_zf2_website_folder' => array(
//                                     'name'     => 'tpl_zf2_website_folder',
//                                     'required' => true,
//                                     'validators' => array(
//                                         array(
//                                             'name'    => 'StringLength',
//                                             'options' => array(
//                                                 'encoding' => 'UTF-8',
//                                                 //'min'      => 1,
//                                                 'max'      => 50,
//                                                 'messages' => array(
//                                                     \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_template_form_tpl_website_folder_error_high',
//                                                     //    \Zend\Validator\StringLength::TOO_SHORT => 'tr_meliscms_template_form_tpl_website_folder_error_low',
//                                                 ),
//                                             ),
//                                         ),
//                                         array(
//                                             'name' => 'NotEmpty',
//                                             'options' => array(
//                                                 'messages' => array(
//                                                     \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_template_form_tpl_website_folder_error_empty',
//                                                 ),
//                                             ),
//                                         ),
//                                     ),
//                                     'filters'  => array(
//                                         array('name' => 'StripTags'),
//                                         array('name' => 'StringTrim'),
//                                     ),
//                                 ),
                                'tpl_zf2_layout' => array(
                                    'name'     => 'tpl_zf2_layout',
                                    'required' => false,
                                    'validators' => array(
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'tpl_zf2_controller' => array(
                                    'name'     => 'tpl_zf2_controller',
                                    'required' => false,
                                    'validators' => array(
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'tpl_zf2_action' => array(
                                    'name'     => 'tpl_zf2_action',
                                    'required' => false,
                                    'validators' => array(
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'tpl_php_path' => array(
                                    'name'     => 'tpl_php_path',
                                    'required' => false,
                                    'validators' => array(
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                            ), // end input filter
                        ),
                    ), // end forms

                ), // end Melis CMS Tool Template Manager Plugin
                'meliscms_tool_styles' => array(
                    'conf' => array(
                        'title' => 'tr_meliscms_tool_styles',
                        'id' => 'id_meliscms_tool_styles',
                    ),
                    'forms' => array(
                        'meliscms_tool_styles_form' => array(
                            'attributes' => array(
                                'name' => 'stylesForm',
                                'id' => 'stylesForm',
                                'method' => '',
                                'action' => '',
                                'novalidate' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'style_status',
                                        'type' => 'Select',
                                        'disable_html_escape' => true,
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_style_page_status',
                                            'tooltip' => 'tr_meliscms_tool_style_page_status tooltip',
                                            'use_hidden_element' => false,
                                            'checked_value' => 1,
                                            'unchecked_value' => 0,
                                            'switchOptions' => array(
                                                'label' => "<i class='glyphicon glyphicon-resize-horizontal'></i>",
                                                'label-off' => 'tr_meliscms_tool_style_page_status_off',
                                                'label-on' => 'tr_meliscms_tool_style_page_status_on',
                                            ),
                                            'disable_inarray_validator' => true
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_style_page_id',
                                        ), 
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'style_id',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_style_id',
                                            'tooltip' => 'tr_meliscms_tool_style_id tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_style_id',
                                            'readonly' => 'readonly',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'style_site_id',
                                        'type' => 'MelisCmsPluginSiteSelect',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_templates_tpl_site_id',
                                            'tooltip' => 'tr_meliscms_tool_templates_tpl_site_id',
                                            'empty_option' => 'tr_meliscms_form_common_Choose',
                                            'disable_inarray_validator' => true,
                                        ),
                                        'attributes' => array(
                                            'id' => 'style_site_id',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'style_name',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_style_name',
                                            'tooltip' => 'tr_meliscms_tool_style_name tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_style_page_id',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'style_path',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_style_path',
                                            'tooltip' => 'tr_meliscms_tool_style_path tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_style_path',
                                            'required' => 'required',
                                            'placeholder' => '/Sitefolder/subfolder/myNewStyle.css',
                                        ),
                                    ),
                                ),
                            ),// end elements
                            'input_filter' => array(
                                'style_id' => array(
                                    'name'     => 'style_id',
                                    'break_chain_on_failure' => true,
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 10,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_style_page_id_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => 'tr_meliscms_tool_platform_not_digit',
                                                    \Zend\I18n\Validator\IsInt::INVALID => 'tr_meliscms_tool_platform_not_digit',
                                                )
                                            )
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'style_site_id' => array(
                                    'name'     => 'style_site_id',
                                    'required' => true,
                                    'validators' => [
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_template_form_tpl_site_id_error_empty',
                                                ),
                                            ),
                                        ),
                                    ],
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'style_status' => array(
                                    'name'     => 'style_status',
                                    'required' => false,
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'style_name' => array(
                                    'name'     => 'style_name',
                                    'break_chain_on_failure' => true,
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 255,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_style_page_id_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_style_page_is_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'style_status' => array(
                                    'name'     => 'style_status',
                                    'required' => false,
                                    'validators' => array(

                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'style_path' => array(
                                    'name'     => 'style_path',
                                    'break_chain_on_failure' => true,
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 255,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_style_page_id_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_style_page_is_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                            ),// end input filters
                        ),
                    ),
                    'table' => array(
                        'target' => '#tableToolStyles',
                        'ajaxUrl' => '/melis/MelisCms/ToolStyle/getStyleData',
                        'dataFunction' => '',
                        'ajaxCallback' => '',
                        'filters' => array(
                            'left' => array(
                                'style-tool-table-limit' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'ToolStyle',
                                    'action' => 'render-tool-style-content-filters-limit',
                                ),
                            ),
                            'center' => array(
                                'style-tool-table-search' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'ToolStyle',
                                    'action' => 'render-tool-style-content-filters-search',
                                ),
                            ),
                            'right' => array(
                                'style-tool-table-refresh' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'ToolStyle',
                                    'action' => 'render-tool-style-content-filters-refresh',
                                ),
                            ),
                        ),
                        'columns' => array(
                            'style_id' => array(
                                'text' => 'tr_meliscms_tool_style_id',
                                'css' => array('width' => '5%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),
                            'site_name' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_site_id',
                                'css' => array('width' => '20%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),
                            'style_files' => array(
                                'text' => 'tr_meliscms_tool_style_files',
                                'css' => array('width' => '5%', 'padding-right' => '0'),
//                                'sortable' => true,
                            ),
                            'style_status' => array(
                                'text' => 'tr_meliscms_tool_style_page_status',
                                'css' => array('width' => '5%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),
                            'style_name' => array(
                                'text' => 'tr_meliscms_tool_style_name',
                                'css' => array('width' => '15%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),
                            'style_path' => array(
                                'text' => 'tr_meliscms_tool_style_path',
                                'css' => array('width' => '40%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),

                        ),
                        'searchables' => array(
                            'melis_cms_style.style_id',
                            'melis_cms_style.style_path',
                        ),
                        'actionButtons' => array(
                            'edit' => array(
                                'module' => 'MelisCms',
                                'controller' => 'ToolStyle',
                                'action' => 'render-tool-style-action-edit',
                            ),
                            'delete' => array(
                                'module' => 'MelisCms',
                                'controller' => 'ToolStyle',
                                'action' => 'render-tool-style-action-delete',
                            ),
                        ),
                    ),
                ), // end Melis CMS Tool Styles Manage Plugin
                'meliscms_tool_site' => array(
                    'conf' => array(
                        'title' => 'tr_meliscms_tool_site',
                        'id' => 'id_meliscms_tool_site'
                    ),
                    'table' => array(
                        'target' => '#tableToolSites',
                        'ajaxUrl' => '/melis/MelisCms/Site/getSiteData',
                        'dataFunction' => '',
                        'ajaxCallback' => '',
                        'filters' => array(
                            'left' => array(
                                'site-tool-table-limit' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'Site',
                                    'action' => 'render-tool-site-content-filter-limit',
                                ),
                            ),
                            'center' => array(
                                'site-tool-table-search' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'Site',
                                    'action' => 'render-tool-site-content-filter-search',
                                ),
                            ),
                            'right' => array(
                                'site-tool-table-refresh' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'Site',
                                    'action' => 'render-tool-site-content-filter-refresh',
                                ),
                            ),
                        ),
                        'columns' => array(
                            'site_id' => array(
                                'text' => 'tr_meliscms_tool_site_col_site_id',
                                'css' => array('width' => '1%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),
                            'site_name' => array(
                                'text' => 'tr_meliscms_tool_site_col_site_name',
                                'css' => array('width' => '89%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),

                        ),
                        'searchables' => array(
                            'melis_cms_site.site_id',
                            'melis_cms_site.site_name',

                        ),
                        'actionButtons' => array(
                            'edit' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Site',
                                'action' => 'render-tool-site-content-action-edit',
                            ),
                            'delete' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Site',
                                'action' => 'render-tool-site-content-action-delete',
                            ),
                        ),
                    ),
                    'modals' => array(
                        'meliscms_tool_site_modal_empty_handler' => array(
                            'id' => 'id_meliscms_tool_site_modal_empty_handler',
                            'class' => 'glyphicons book',
                            'tab-header' => 'tr_meliscms_tool_site',
                            'tab-text' => '',
                            'content' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Site',
                                'action' => 'render-tool-site-modal-empty-handler',
                            ),

                        ),
                        'meliscms_tool_site_modal_add' => array(
                            'id' => 'id_meliscms_tool_site_modal_add',
                            'class' => 'glyphicons plus',
                            'tab-header' => '',
                            'tab-text' => 'tr_meliscms_tool_site_new_site',
                            'content' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Site',
                                'action' => 'render-tool-site-modal-add',
                            ),

                        ),
                        'meliscms_tool_site_modal_edit' => array(
                            'id' => 'id_meliscms_tool_site_modal_edit',
                            'class' => 'glyphicons pencil',
                            'tab-header' => '',
                            'tab-text' => 'tr_meliscms_tool_site_update_site',
                            'content' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Site',
                                'action' => 'render-tool-site-modal-edit',
                            ),

                        ),
                    ),
                    'forms' => array(
                        'meliscms_site_tool_creation_form' => array(
                            'attributes' => array(
                                'name' => 'siteCreationForm',
                                'id' => 'siteCreationForm',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'site_name',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_site_name',
                                            'tooltip' => 'tr_meliscms_tool_site_site_name tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_site_name',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'plang_lang_id',
                                        'type' => 'MelisCmsLanguageSelect',
                                        'options' => array(
                                            'label' => 'tr_meliscms_page_tab_properties_form_Language',
                                            'tooltip' => 'tr_meliscms_page_tab_properties_form_Language tooltip',
                                            'empty_option' => 'tr_meliscms_form_common_Choose',
                                            'disable_inarray_validator' => true,
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_plang_lang_id',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'sdom_env',
                                        'type' => 'MelisCmsPlatformSelect',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_enviroment',
                                            'tooltip' => 'tr_meliscms_tool_site_enviroment tooltip',
                                            'disable_inarray_validator' => true,
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_sdom_env',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'sdom_scheme',
                                        'type' => 'Zend\Form\Element\Select',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_scheme',
                                            'tooltip' => 'tr_meliscms_tool_site_scheme tooltip',
                                            'value_options' => array(
                                                'http' => 'http://',
                                                'https' => 'https://',
                                            ),
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_sdom_scheme',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'sdom_domain',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_domain',
                                            'tooltip' => 'tr_meliscms_tool_site_domain tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_sdom_domain',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                            ), // end elements
                            'input_filter' => array(
                                'site_name' => array(
                                    'name'     => 'site_name',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 45,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_site_site_name_error_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_site_site_name_error_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'plang_lang_id' => array(
                                    'name'     => 'plang_lang_id',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_page_form_plang_lang_id_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters' => array(
                                    ),
                                ),
                                'sdom_env' => array(
                                    'name'     => 'sdom_env',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 50,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_site_enviroment_error_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_site_enviroment_error_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'sdom_scheme' => array(
                                    'name'     => 'sdom_scheme',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'InArray',
                                            'options' => array(
                                                'haystack' => array('http', 'https'),
                                                'messages' => array(
                                                    \Zend\Validator\InArray::NOT_IN_ARRAY => 'tr_meliscms_tool_site_scheme_invalid_selection',
                                                ),
                                            )
                                        ),
                                        array(
                                            'name'    => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_site_scheme_error_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                    ),
                                ),
                                'sdom_domain' => array(
                                    'name'     => 'sdom_domain',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 50,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_site_domain_error_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_site_domain_error_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                            ), // end input_filter
                        ), // end of Site creation form
                        'meliscms_site_tool_edition_form' => array(
                            'attributes' => array(
                                'name' => 'siteEditionForm',
                                'id' => 'siteEditionForm',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'site_id',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_id',
                                            'tooltip' => 'tr_meliscms_tool_site_id tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_site_id',
                                            'value' => '',
                                            'disabled' => 'disabled',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'site_name',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_site_name',
                                            'tooltip' => 'tr_meliscms_tool_site_site_name tooltip'
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_site_name',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'site_main_page_id',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_page_id',
                                            'tooltip' => 'tr_meliscms_tool_site_page_id tooltip',
                                            'button' => 'fa fa-sitemap',
                                            'button-id' => 'siteMainPageId',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_site_main_page_id',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 's404_page_id',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_404_page_id',
                                            'tooltip' => 'tr_meliscms_tool_site_404_page_id tooltip',
                                            'button' => 'fa fa-sitemap',
                                            'button-id' => 's404PageId',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_s404_page_id',
                                            'value' => '',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'sdom_env',
                                        'type' => 'MelisCmsPlatformSelect',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_enviroment',
                                            'tooltip' => 'tr_meliscms_tool_site_enviroment tooltip',
                                            'disable_inarray_validator' => true,
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_sdom_env',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'sdom_scheme',
                                        'type' => 'Zend\Form\Element\Select',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_scheme',
                                            'tooltip' => 'tr_meliscms_tool_site_scheme tooltip',
                                            'value_options' => array(
                                                'http' => 'http://',
                                                'https' => 'https://',
                                            ),
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_sdom_scheme',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'sdom_domain',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_domain',
                                            'tooltip' => 'tr_meliscms_tool_site_domain tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_sdom_domain',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),


                            ), // end elements
                            'input_filter' => array(
                                'site_id' => array(
                                    'name'     => 'site_id',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name'    => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => 'tr_meliscms_tool_site_id_invalid',
                                                    \Zend\I18n\Validator\IsInt::INVALID => 'tr_meliscms_tool_site_id_invalid',
                                                )
                                            )
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'site_name' => array(
                                    'name'     => 'site_name',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 45,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_site_site_name_error_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_site_site_name_error_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'site_main_page_id' => array(
                                    'name'     => 'site_main_page_id',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name' => 'regex',
                                            'options' => array(
                                                'pattern' => '/[0-9]/',
                                                'messages' => array(\Zend\Validator\Regex::NOT_MATCH => 'tr_meliscms_tool_site_page_id_not_int'),
                                                'encoding' => 'UTF-8',
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_site_page_id_error_empty',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 11,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_site_page_id_error_long',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'sdom_env' => array(
                                    'name'     => 'sdom_env',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 50,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_site_enviroment_error_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_site_enviroment_error_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'sdom_scheme' => array(
                                    'name'     => 'sdom_scheme',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'InArray',
                                            'options' => array(
                                                'haystack' => array('http', 'https'),
                                                'messages' => array(
                                                    \Zend\Validator\InArray::NOT_IN_ARRAY => 'tr_meliscms_tool_site_scheme_invalid_selection',
                                                ),
                                            )
                                        ),
                                        array(
                                            'name'    => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_site_scheme_error_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                    ),
                                ),
                                'sdom_domain' => array(
                                    'name'     => 'sdom_domain',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 50,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_site_domain_error_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'regex',
                                            'options' => array(
                                                'pattern' => '/(www\.)?([a-zA-Z0-9-_]+)\.([a-z]+)/',
                                                'messages' => array(\Zend\Validator\Regex::NOT_MATCH => 'tr_meliscms_tool_site_domain_invalid'),
                                                'encoding' => 'UTF-8',
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_site_domain_error_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                's404_page_id' => array(
                                    'name'     => 's404_page_id',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name' => 'regex',
                                            'options' => array(
                                                'pattern' => '/[0-9]/',
                                                'messages' => array(\Zend\Validator\Regex::NOT_MATCH => 'tr_meliscms_tool_site_404_page_id_not_int'),
                                                'encoding' => 'UTF-8',
                                            ),
                                        ),
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 11,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_site_404_page_id_error_long',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                            ), // end input_filter

                        ), // End of Site edition form
                    ),

                ), // end Melis CMS Site Tool

                //Language Tool
                'meliscms_language_tool' => array(
                    'conf' => array(
                        'title' => 'tr_meliscms_tool_language',
                        'id' => 'id_meliscms_language_tool',
                    ),
                    'table' => array(
                        'target' => '#tableLanguagesCms',
                        'ajaxUrl' => '/melis/MelisCms/Language/getLanguages',
                        'dataFunction' => '',
                        'ajaxCallback' => 'initLangJs()',
                        'filters' => array(
                            'left' => array(
                                'meliscms_tool_language_content_filters_limit' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'Language',
                                    'action' => 'render-tool-language-content-filters-limit',
                                ),
                            ),
                            'center' => array(
                                'meliscms_tool_language_content_filters_search' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'Language',
                                    'action' => 'render-tool-language-content-filters-search',
                                ),
                            ),
                            'right' => array(
                                'meliscms_tool_language_content_filters_refresh' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'Language',
                                    'action' => 'render-tool-language-content-filters-refresh',
                                ),
                            ),
                        ),
                        'columns' => array(
                            'lang_cms_id' => array(
                                'text' => 'tr_meliscms_tool_language_lang_id',
                                'css' => array('width' => '1%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                            'lang_cms_name' => array(
                                'text' => 'tr_meliscms_tool_language_lang_name',
                                'css' => array('width' => '49%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                            'lang_cms_locale' => array(
                                'text' => 'tr_meliscms_tool_language_lang_locale',
                                'css' => array('width' => '40%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                        ),
                        'searchables' => array(
                            'lang_cms_id', 'lang_cms_locale', 'lang_cms_name'
                        ),
                        'actionButtons' => array(
//                             'meliscms_tool_language_content_apply' => array(
//                                 'module' => 'MelisCms',
//                                 'controller' => 'Language',
//                                 'action' => 'render-tool-language-content-action-apply',
//                             ),
                            'meliscms_tool_language_edit' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Language',
                                'action' => 'render-tool-language-content-action-edit',
                            ),
                            'meliscms_tool_language_content_delete' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Language',
                                'action' => 'render-tool-language-content-action-delete',
                            ),
                        ),
                    ), // end table
                    'modals' => array(
                        'meliscms_tool_language_modal_content_empty' => array( // empty modal content
                            'id' => 'id_meliscms_tool_language_modal_content_empty',
                            'class' => 'glyphicons remove',
                            'tab-header' => 'tr_meliscms_tool_user',
                            'tab-text' => 'tr_meliscms_tool_user_modal_empty',
                            'content' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Language',
                                'action' => 'render-tool-language-modal-empty-handler'
                            ),
                        ),
                        'meliscms_tool_language_modal_content_new' => array(
                            'id' => 'id_meliscms_tool_language_modal_content_new',
                            'class' => 'glyphicons plus',
                            'tab-header' => '',
                            'tab-text' => 'tr_meliscms_tool_language_new',
                            'content' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Language',
                                'action' => 'render-tool-language-modal-add-content'
                            ),
                        ),
                        'meliscms_tool_language_modal_content_edit' => array(
                            'id' => 'id_meliscms_tool_language_modal_content_edit',
                            'class' => 'glyphicons pencil',
                            'tab-header' => '',
                            'tab-text' => 'tr_meliscms_tool_language_edit',
                            'content' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Language',
                                'action' => 'render-tool-language-modal-edit-content'
                            ),
                        ),
                    ), //end modals
                    'forms' => array(
                        'meliscms_tool_language_generic_form' => array(
                            'attributes' => array(
                                'name' => 'formlang',
                                'id' => 'idformlang',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'lang_cms_id',
                                        'type' => 'hidden',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_language_lang_id',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_lang_id',
                                            'disabled' => 'disabled',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'lang_cms_name',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_language_lang_name',
                                            'tooltip' => 'tr_meliscore_tool_language_lang_name tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_lang_name',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'lang_cms_locale',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_language_lang_locale',
                                            'tooltip' => 'tr_meliscore_tool_language_lang_locale2 tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_lang_locale',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),

                            ),
                            'input_filter' => array(
                                'lang_cms_id' => array(
                                    'name'     => 'lang_cms_id',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name'    => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => 'tr_meliscms_tool_platform_not_digit',
                                                    \Zend\I18n\Validator\IsInt::INVALID => 'tr_meliscms_tool_platform_not_digit',
                                                )
                                            )
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'lang_cms_locale' => array(
                                    'name'     => 'lang_cms_locale',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 10,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_language_lang_locale_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_language_lang_locale_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'lang_cms_name' => array(
                                    'name'     => 'lang_cms_name',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 45,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_language_lang_name_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_language_lang_name_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                            ),
                        ),
                    ), // end form
                ),
                // end Language tool
                /* CMS PLATFORM TOOL */
                'meliscms_platform_tool' => array(
                    'conf' => array(
                        'title' => 'tr_meliscms_platform_tool',
                        'id' => 'id_meliscms_platform_tool',
                    ),
                    'table' => array(
                        'target' => '#platformToolTable',
                        'ajaxUrl' => '/melis/MelisCms/Platform/getPlatformData',
                        'dataFunction' => '',
                        'ajaxCallback' => 'initPlatformIdTbl()',
                        'filters' => array(
                            'left' => array(
                                'toolPlatform-limit' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'Platform',
                                    'action' => 'render-content-platform-table-limit',
                                ),
                            ),
                            'center' => array(
                            ),
                            'right' => array(
                                'toolPlatform-refresh' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'Platform',
                                    'action' => 'render-content-platform-table-refresh',
                                ),
                            ),
                        ),
                        'columns' => array(
                            'pids_id' => array(
                                'text' => 'tr_meliscms_tool_platform_pids_id',
                                'css' => array('width' => '1%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                            'pids_name' => array(
                                'text' => 'tr_meliscms_tool_platform_pids_name',
                                'css' => array('width' => '1%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                            'pids_page_id_start' => array(
                                'text' => 'tr_meliscms_tool_platform_pids_page_id_start',
                                'css' => array('width' => '15%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                            'pids_page_id_current' => array(
                                'text' => 'tr_meliscms_tool_platform_pids_page_id_current',
                                'css' => array('width' => '15%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                            'pids_page_id_end' => array(
                                'text' => 'tr_meliscms_tool_platform_pids_page_id_end',
                                'css' => array('width' => '15%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                            'pids_tpl_id_start' => array(
                                'text' => 'tr_meliscms_tool_platform_pids_tpl_id_start',
                                'css' => array('width' => '15%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                            'pids_tpl_id_current' => array(
                                'text' => 'tr_meliscms_tool_platform_pids_tpl_id_current',
                                'css' => array('width' => '15%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),

                            'pids_tpl_id_end' => array(
                                'text' => 'tr_meliscms_tool_platform_pids_tpl_id_end',
                                'css' => array('width' => '15%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                        ),
                        'searchables' => array(),
                        'actionButtons' => array(
                            'edit' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Platform',
                                'action' => 'render-action-edit',
                            ),
                            'delete' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Platform',
                                'action' => 'render-action-delete',
                            ),
                        )
                    ), // END TABLE
                    'forms' => array(
                        'meliscms_tool_platform_generic_form' => array(
                            'attributes' => array(
                                'name' => 'formplatform',
                                'id' => 'idformplatform',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'pids_id',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_platform_pids_id',
                                            'tooltip' => 'tr_meliscms_tool_platform_pids_id tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_id',
                                            'value' => '',
                                            'disabled' => 'disabled',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'pids_name_input',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_platform_pids_name',
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_name',
                                            'value' => '',
                                            'disabled' => 'disabled',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'pids_name_select',
                                        'type' => 'MelisCmsPlatformIDsSelect',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_platform_pids_name',
                                            'tooltip' => 'tr_meliscms_tool_platform_pids_name tooltip',
                                            'empty_option' => 'tr_meliscms_form_common_Choose',
                                            'disable_inarray_validator' => true,
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_name_select',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                /* END OF PLATFORM NAME OPTION */
                                array(
                                    'spec' => array(
                                        'name' => 'pids_page_id_start',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_platform_pids_page_id_start',
                                            'tooltip' => 'tr_meliscms_tool_platform_pids_page_id_start tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_page_id_start',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'pids_page_id_current',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_platform_pids_page_id_current',
                                            'tooltip' => 'tr_meliscms_tool_platform_pids_page_id_current tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_page_id_current',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'pids_page_id_end',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_platform_pids_page_id_end',
                                            'tooltip' => 'tr_meliscms_tool_platform_pids_page_id_end tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_page_id_current',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'pids_tpl_id_start',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_platform_pids_tpl_id_start',
                                            'tooltip' => 'tr_meliscms_tool_platform_pids_tpl_id_start tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_tpl_id_start',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'pids_tpl_id_current',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_platform_pids_tpl_id_current',
                                            'tooltip' => 'tr_meliscms_tool_platform_pids_tpl_id_current tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_tpl_id_current',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'pids_tpl_id_end',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_platform_pids_tpl_id_end',
                                            'tooltip' => 'tr_meliscms_tool_platform_pids_tpl_id_end tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_tpl_id_end',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                            ),
                            'input_filter' => array(
                                'pids_id' => array(
                                    'name'     => 'pids_page_id_start',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name'    => 'Digits',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\Digits::NOT_DIGITS => 'tr_meliscms_tool_platform_not_digit',
                                                    \Zend\Validator\Digits::STRING_EMPTY => '',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'pids_name_select' => array(
                                    'name'     => 'pids_name_select',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_platform_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'pids_page_id_start' => array(
                                    'name'     => 'pids_page_id_start',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 11,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_platform_value_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'Digits',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\Digits::NOT_DIGITS => 'tr_meliscms_tool_platform_not_digit',
                                                    \Zend\Validator\Digits::STRING_EMPTY => '',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_platform_empty',

                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'pids_page_id_current' => array(
                                    'name'     => 'pids_page_id_current',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 11,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_platform_value_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'Digits',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\Digits::NOT_DIGITS => 'tr_meliscms_tool_platform_not_digit',
                                                    \Zend\Validator\Digits::STRING_EMPTY => '',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_platform_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'pids_page_id_end' => array(
                                    'name'     => 'pids_page_id_end',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 11,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_platform_value_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'Digits',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\Digits::NOT_DIGITS => 'tr_meliscms_tool_platform_not_digit',
                                                    \Zend\Validator\Digits::STRING_EMPTY => '',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_platform_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'pids_tpl_id_start' => array(
                                    'name'     => 'pids_tpl_id_start',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 11,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_platform_value_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'Digits',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\Digits::NOT_DIGITS => 'tr_meliscms_tool_platform_not_digit',
                                                    \Zend\Validator\Digits::STRING_EMPTY => '',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_platform_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'pids_tpl_id_current' => array(
                                    'name'     => 'pids_tpl_id_current',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 11,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_platform_value_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'Digits',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\Digits::NOT_DIGITS => 'tr_meliscms_tool_platform_not_digit',
                                                    \Zend\Validator\Digits::STRING_EMPTY => '',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_platform_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'pids_tpl_id_end' => array(
                                    'name'     => 'pids_tpl_id_end',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 11,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_platform_value_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'Digits',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\Digits::NOT_DIGITS => 'tr_meliscms_tool_platform_not_digit',
                                                    \Zend\Validator\Digits::STRING_EMPTY => '',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_platform_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                            ),
                        ),
                    ), // END FORM
                ),
                /* END OF CMS PLATFORM TOOL */

                // Site Redirect Tool
                'meliscms_tool_site_301' => array(
                    'conf' => array(
                        'title' => 'tr_meliscms_tool_site_301',
                        'id' => 'id_meliscms_tool_site_301',
                    ),
                    'table' => array(
                        // table ID
                        'target' => '#tableToolSite301',
                        'ajaxUrl' => '/melis/MelisCms/SiteRedirect/getSiteRedirect',
                        'dataFunction' => 'initRedirectTemplateList',
                        'ajaxCallback' => '',
                        'filters' => array(
                            'left' => array(
                                'tool-site-redirect-limit' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'SiteRedirect',
                                    'action' => 'render-tool-site-redirect-filters-limit',
                                ),
                                'tool-site-redirect-sites' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'SiteRedirect',
                                    'action' => 'render-tool-site-redirect-filters-sites',
                                ),
                            ),
                            'center' => array(
                                'tool-site-redirect-search' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'SiteRedirect',
                                    'action' => 'render-tool-site-redirect-filters-search',
                                ),
                            ),
                            'right' => array(
                                'tool-site-redirect-refresh' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'SiteRedirect',
                                    'action' => 'render-tool-site-redirect-filters-refresh',
                                ),
                            ),
                        ),
                        'columns' => array(
                            's301_id' => array(
                                'text' => 'tr_meliscms_tool_site_301_s301_id',
                                'css' => array('width' => '1%'),
                                'sortable' => true,
                            ),
                            'site_name' => array(
                                'text' => 'tr_meliscms_tool_site_301_s301_site',
                                'css' => array('width' => '30%'),
                                'sortable' => true,
                            ),
                            's301_old_url' => array(
                                'text' => 'tr_meliscms_tool_site_301_s301_old_url',
                                'css' => array('width' => '30%'),
                                'sortable' => true,
                            ),
                            's301_new_url' => array(
                                'text' => 'tr_meliscms_tool_site_301_s301_new_url',
                                'css' => array('width' => '30%'),
                                'sortable' => true,
                            ),
                        ),
                        // define what columns can be used in searching
                        'searchables' => array('site_name', 's301_old_url', 's301_new_url'),
                        'actionButtons' => array(
                            'test' => array(
                                'module' => 'MelisCms',
                                'controller' => 'SiteRedirect',
                                'action' => 'render-tool-site-redirect-test',
                            ),
                            'edit' => array(
                                'module' => 'MelisCms',
                                'controller' => 'SiteRedirect',
                                'action' => 'render-tool-site-redirect-edit',
                            ),
                            'delete' => array(
                                'module' => 'MelisCms',
                                'controller' => 'SiteRedirect',
                                'action' => 'render-tool-site-redirect-delete',
                            ),
                        )
                    ),
                    'forms' => array(
                        'meliscms_tool_site_301_generic_form' => array(
                            'attributes' => array(
                                'name' => 'siteRedirectForm',
                                'id' => 'siteRedirectForm',
                                'method' => 'Post',
                                'action' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 's301_id',
                                        'type' => 'hidden',
                                        'options' => array(
                                            'label' => ''
                                        ),
                                        'attributes' => array(
                                            'id' => 's301_id',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 's301_site_id',
                                        'type' => 'MelisCoreSiteSelect',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_301_s301_site',
                                            'empty_option' => 'tr_meliscms_form_common_Choose',
                                            'tooltip' => 'tr_meliscms_tool_site_301_s301_site tooltip',
                                            'disable_inarray_validator' => true,
                                        ),
                                        'attributes' => array(
                                            'id' => 's301_site_id',
                                            'value' => '',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 's301_old_url',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_301_s301_old_url',
                                            'tooltip' => 'tr_meliscms_tool_site_301_s301_old_url tooltip'
                                        ),
                                        'attributes' => array(
                                            'id' => 's301_old_url',
                                            'required' => 'required',
                                            'placeholder' => 'tr_meliscms_tool_site_301_s301_old_url holder',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 's301_new_url',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_301_s301_new_url',
                                            'tooltip' => 'tr_meliscms_tool_site_301_s301_new_url tooltip'
                                        ),
                                        'attributes' => array(
                                            'id' => 's301_old_url',
                                            'required' => 'required',
                                            'placeholder' => 'tr_meliscms_tool_site_301_s301_new_url holder',
                                        ),
                                    ),
                                ),
                            ),
                            'input_filter' => array(
                                's301_id' => array(
                                    'name'     => 's301_id',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name'    => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => 'tr_meliscms_tool_platform_not_digit',
                                                    \Zend\I18n\Validator\IsInt::INVALID => 'tr_meliscms_tool_platform_not_digit',
                                                )
                                            )
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                's301_site_id' => array(
                                    'name'     => 's301_site_id',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'meliscms_tool_site_301_value_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                's301_old_url' => array(
                                    'name'     => 's301_old_url',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 255,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'meliscms_tool_site_301_value_too_long_255',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'meliscms_tool_site_301_value_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                's301_new_url' => array(
                                    'name'     => 's301_new_url',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                'max'      => 255,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'meliscms_tool_site_301_value_too_long_255',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'meliscms_tool_site_301_value_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'meliscms_tree_sites_tool' => array(
                    'forms' => array(
                        'meliscms_tree_sites_duplicate_tree_form' => array(
                            'attributes' => array(
                                'name' => 'duplicatePageTreeForm',
                                'id' => 'duplicatePageTreeForm',
                                'method' => 'Post',
                                'action' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'sourcePageId',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tree_sites_duplication_source',
                                            'tooltip' => 'tr_meliscms_tree_sites_duplication_source tooltip',
                                            'button' => 'fa fa-sitemap',
                                            'button-id' => 'sourcePageIdFindPageTree',
                                        ),
                                        'attributes' => array(
                                            'id' => 'sourcePageId',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'lang_id',
                                        'type' => 'MelisCmsLanguageSelect',
                                        'options' => array(
                                            'label' => 'tr_meliscms_page_tab_properties_form_Language',
                                            'tooltip' => 'tr_meliscms_page_tab_properties_form_Language tooltip',
                                            'empty_option' => 'tr_meliscms_form_common_Choose',
                                            'disable_inarray_validator' => true,
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_plang_lang_id',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'pageRelation',
                                        'type' => 'checkbox',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tree_sites_duplication_page_relation',
                                            'tooltip' => 'tr_meliscms_tree_sites_duplication_page_relation tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'pageRelation',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'destinationPageId',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tree_sites_duplication_destination',
                                            'tooltip' => 'tr_meliscms_tree_sites_duplication_destination tooltip',
                                            'button' => 'fa fa-sitemap',
                                            'button-id' => 'destinationPageIdFindPageTree',
                                        ),
                                        'attributes' => array(
                                            'id' => 'destinationPageId',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),

                            ),
                            'input_filter' => array(
                                'sourcePageId' => array(
                                    'name'     => 'sourcePageId',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'IsInt',
                                            'break_chain_on_failure' => true,
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => 'tr_meliscms_tool_duplicate_field_digits',
                                                )
                                            )
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'break_chain_on_failure' => true,
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_duplicate_field_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'lang_id' => array(
                                    'name'     => 'lang_id',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_page_form_plang_lang_id_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters' => array(
                                    ),
                                ),
                                'destinationPageId' => array(
                                    'name'     => 'destinationPageId',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'IsInt',
                                            'break_chain_on_failure' => true,
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\I18n\Validator\IsInt::NOT_INT => 'tr_meliscms_tool_duplicate_field_digits',
                                                )
                                            )
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'break_chain_on_failure' => true,
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_duplicate_field_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'meliscore' => [
                    'tools' => [
                        'melis_core_gdpr_tool' => [
                            'forms' => [
                                'melis_core_gdpr_search_form' => [
                                    'elements' => [
                                        [
                                            'spec' => [
                                                'name' => 'site_id',
                                                'type' => 'MelisCoreSiteSelect',
                                                'options' => [
                                                    'label' => 'tr_melis_core_gdpr_form_site',
                                                    'form_type' => 'form-horizontal',
                                                    'empty_option' => 'tr_meliscore_common_choose',
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ),
        ),
    ),
);  