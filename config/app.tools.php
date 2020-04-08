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
                                'sortable' => false,
                            ),
                            'tpl_type' => array(
                                'text' => 'tr_meliscms_template_form_tpl_type',
                                'css' => array('width' => '5%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                            'site_label' => array(
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
                                'css' => array('width' => '15%', 'padding-right' => '0'),
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
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
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
                                        'type' => 'select',
                                        'options' => array(
                                            'label' => 'tr_meliscmstemplate_typ_label',
                                            'tooltip' => 'tr_meliscmstemplate_typ_label_tooltip',
                                            'value_options' => [
                                                'ZF2' => 'Zend Framework 2',
                                            ],
                                        ),
                                        'attributes' => array(
                                            'value' => 'ZF2',
                                            'id' => 'id_tpl_type',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
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
                                                    \Laminas\I18n\Validator\IsInt::NOT_INT => '',
                                                    \Laminas\I18n\Validator\IsInt::INVALID => '',
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
                                                    \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_template_form_tpl_name_error_high',
                                                    //    \Laminas\Validator\StringLength::TOO_SHORT => 'tr_meliscms_template_form_tpl_name_error_low',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_template_form_tpl_name_error_empty',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'regex', false,
                                            'options' => array(
                                                'pattern' => '/^[a-zA-Z0-9]+([_ -]?[a-zA-Z0-9])*$/',
                                                'messages' => array(\Laminas\Validator\Regex::NOT_MATCH => 'tr_melis_cms_tool_template_name_invalid'),
                                                'encoding' => 'UTF-8',
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
                                                'haystack' => array('ZF2'),
                                                'messages' => array(
                                                    \Laminas\Validator\InArray::NOT_IN_ARRAY => 'tr_meliscms_template_form_tpl_type_error_invalid_select',
                                                ),
                                            )
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_template_form_tpl_type_error_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                    ),
                                ),
                                'tpl_zf2_layout' => array(
                                    'name'     => 'tpl_zf2_layout',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name' => 'regex', false,
                                            'options' => array(
                                                'pattern' => '/^[a-zA-Z0-9]+([_ -]?[a-zA-Z0-9])*$/',
                                                'messages' => array(\Laminas\Validator\Regex::NOT_MATCH => 'tr_melis_cms_tool_template_layout_invalid'),
                                                'encoding' => 'UTF-8',
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'tpl_zf2_controller' => array(
                                    'name'     => 'tpl_zf2_controller',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name' => 'regex', false,
                                            'options' => array(
                                                'pattern' => '/^[a-zA-Z0-9]+([_ -]?[a-zA-Z0-9])*$/',
                                                'messages' => array(\Laminas\Validator\Regex::NOT_MATCH => 'tr_melis_cms_tool_template_controller_invalid'),
                                                'encoding' => 'UTF-8',
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                                'tpl_zf2_action' => array(
                                    'name'     => 'tpl_zf2_action',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name' => 'regex', false,
                                            'options' => array(
                                                'pattern' => '/^[a-zA-Z0-9]+([_ -]?[a-zA-Z0-9])*$/',
                                                'messages' => array(\Laminas\Validator\Regex::NOT_MATCH => 'tr_melis_cms_tool_template_action_invalid'),
                                                'encoding' => 'UTF-8',
                                            ),
                                        ),
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
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
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
                                                    \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_style_page_id_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'IsInt',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\I18n\Validator\IsInt::NOT_INT => 'tr_meliscms_tool_platform_not_digit',
                                                    \Laminas\I18n\Validator\IsInt::INVALID => 'tr_meliscms_tool_platform_not_digit',
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
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_template_form_tpl_site_id_error_empty',
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
                                                    \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_style_page_id_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_style_page_is_empty',
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
                                                    \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_style_page_id_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_style_page_is_empty',
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
                            'site_label' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_site_id',
                                'css' => array('width' => '20%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),
                            'style_files' => array(
                                'text' => 'tr_meliscms_tool_style_files',
                                'css' => array('width' => '5%', 'padding-right' => '0'),
                                'sortable' => false,
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
                            'melis_cms_style.style_name',
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
                //Sites Tool
                'meliscms_tool_sites' => array(
                    'conf' => array(
                        'title' => 'tr_meliscms_tool_site',
                        'id' => 'id_meliscms_tool_site'
                    ),
                    'table' => array(
                        'target' => '#tableToolSites',
                        'ajaxUrl' => '/melis/MelisCms/Sites/getSiteData',
                        'dataFunction' => '',
                        'ajaxCallback' => 'sitesTableCallback()',
                        'filters' => array(
                            'left' => array(
                                'site-tool-table-limit' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'Sites',
                                    'action' => 'render-tool-sites-content-filter-limit',
                                ),
                            ),
                            'center' => array(
                                'site-tool-table-search' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'Sites',
                                    'action' => 'render-tool-sites-content-filter-search',
                                ),
                            ),
                            'right' => array(
                                'site-tool-table-refresh' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'Sites',
                                    'action' => 'render-tool-sites-content-filter-refresh',
                                ),
                            ),
                        ),
                        'columns' => array(
                            'site_id' => array(
                                'text' => 'tr_meliscms_tool_site_col_site_id',
                                'css' => array('width' => '1%', 'padding-right' => '0'),
                                'sortable' => true,

                            ),
                            'site_label' => array(
                                'text' => 'Site',
                                'css' => array('width' => '30%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                            'site_name' => array(
                                'text' => 'Module',
                                'css' => array('width' => '30%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                            'site_langs' => array(
                                'text' => 'tr_meliscms_tool_sites_site_languages',
                                'css' => array('width' => '30%', 'padding-right' => '0'),
                                'sortable' => false,
                            ),
                        ),
                        'searchables' => array(
                            'melis_cms_site.site_id',
                            'melis_cms_site.site_name',
                            'melis_cms_site.site_label',
                            'site_langs'

                        ),
                        'actionButtons' => array(
                            'minify' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Sites',
                                'action' => 'render-tool-sites-content-action-minify-assets',
                            ),
                            'edit' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Sites',
                                'action' => 'render-tool-sites-content-action-edit',
                            ),
                            'delete' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Sites',
                                'action' => 'render-tool-sites-content-action-delete',
                            ),
                        ),
                    ),
                    'modals' => array(

                    ),
                    'forms' => array(
                        'meliscms_tool_sites_modal_add_step1_form' => array(
                            'attributes' => array(
                                'name' => 'toolsitesadd_step1form',
                                'id' => 'step1form-is_multi_lingual',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'is_multi_language',
                                        'type' => 'Select',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_tool_add_step1_is_multi_lang',
                                            'tooltip' => 'tr_melis_cms_sites_tool_add_step1_is_multi_lang tooltip',
                                            'checked_value' => 1,
                                            'unchecked_value' => 0,
                                            'switchOptions' => array(
                                                'label-on' => 'tr_meliscms_common_yes',
                                                'label-off' => 'tr_meliscms_common_no',
                                                'label' => "<i class='glyphicon glyphicon-resize-horizontal'></i>",
                                            ),
                                            'disable_inarray_validator' => true,
                                        ),
                                        'attributes' => array(
                                            'id' => 'is_multi_language',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        /**
                         * STEP 2 FORMS
                         */
                        'meliscms_tool_sites_modal_add_step2_form_multi_language' => array(
                            'attributes' => array(
                                'name' => 'toolsitesadd_step2form-multi_language',
                                'id' => 'step2form-multi_language',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'type' => 'Laminas\Form\Element\Radio',
                                        'name' => 'sites_url_setting',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_tool_add_step2_reflect_urls',
                                            'tooltip' => 'tr_melis_cms_sites_tool_add_step2_reflect_urls tooltip',
                                            'label_options' => array(
                                                'disable_html_escape' => true,
                                            ),
                                            'label_attributes' => array(
                                                'class' => 'err_sites_url_setting melis-radio-box',
                                            ),
                                            'value_options' => array(
                                                '1' => 'tr_melis_cms_sites_tool_add_step2_url_local_after_domain',
                                                '2' => 'tr_melis_cms_sites_tool_add_step2_url_different_domains',
                                                '3' => 'tr_melis_cms_sites_tool_add_step2_url_do_nothing',
                                            ),
                                        ),
                                        'attributes' => array(
                                            'required' => 'required',
                                        ),
                                    )
                                )
                            ),
                        ),
                        'meliscms_tool_sites_modal_add_step2_form_single_language' => array(
                            'attributes' => array(
                                'name' => 'toolsitesadd_step2form-single_language',
                                'id' => 'step2form-single_language',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
                            'elements' => array(
                                //add other field here for step2
                            ),
                        ),
                        /**
                         * STEP 3 FORMS
                         */
                        'meliscms_tool_sites_modal_add_step3_form_single_domain' => array(
                            'attributes' => array(
                                'name' => 'toolsitesadd_step3form-single_domain',
                                'id' => 'step3form-single_domain',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'sdom_domain',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_tool_add_step3_single_domain_name',
                                            'tooltip' => 'tr_melis_cms_sites_tool_add_step3_single_domain_name tooltip',
                                            'label_attributes' => array(
                                                'class' => 'err_sdom_domain',
                                            )
                                        ),
                                        'attributes' => array(
                                            'id' => 'sdom_domain',
                                            'required' => 'required',
                                            'tabindex' => '-1',
                                            'value' => $_SERVER['HTTP_HOST'],
                                            'title' => '',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'meliscms_tool_sites_modal_add_step3_form_multi_domain' => array(
                            'attributes' => array(
                                'name' => 'toolsitesadd_step3form-multi_domain',
                                'id' => 'step3form-multi_domain',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
                            'elements' => array(
                                //add other field here for step3 multi domain
                            ),
                        ),
                        /**
                         * STEP 4 FORMS
                         */
                        'meliscms_tool_sites_modal_add_step4_form_module' => array(
                            'attributes' => array(
                                'name' => 'toolsitesadd_step4form_module',
                                'id' => 'step4form_module',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'siteSelectModuleName',
                                        'type' => 'MelisCmsSiteModuleSelect',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_tool_add_step4_select_module',
                                            'tooltip' => 'tr_melis_cms_sites_tool_add_step4_select_module tooltip',
                                            'empty_option' => 'tr_melis_cms_sites_tool_add_step4_select_module_placeholder',
                                            'label_attributes' => array(
                                                'class' => 'err_siteSelectModuleName',
                                            )
                                        ),
                                        'attributes' => array(
                                            'id' => 'siteSelectModuleName',
                                            'class' => 'form-control',
                                            'required' => 'required',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'siteCreateModuleName',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_tool_add_step4_create_module',
                                            'tooltip' => 'tr_melis_cms_sites_tool_add_step4_create_module tooltip',
                                            'label_attributes' => array(
                                                'class' => 'err_siteCreateModuleName',
                                            )
                                        ),
                                        'attributes' => array(
                                            'id' => 'siteCreateModuleName',
                                            'class' => 'form-control',
                                            'value' => getenv('MELIS_MODULE'),
                                            'required' => 'required',
                                            'title' => '',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'site_label',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_tool_add_step4_site_label',
                                            'tooltip' => 'tr_melis_cms_sites_tool_add_step4_site_label tooltip',
                                            'label_attributes' => array(
                                                'class' => 'err_site_label',
                                            )
                                        ),
                                        'attributes' => array(
                                            'id' => 'site_label',
                                            'class' => 'form-control',
                                            'required' => 'required',
                                            'title' => '',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'type' => 'Laminas\Form\Element\Radio',
                                        'name' => 'create_sites_file',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_sites_tool_add_step4_create_file_for_website',
                                            'tooltip' => 'tr_melis_cms_sites_tool_add_step4_create_file_for_website tooltip',
                                            'label_options' => array(
                                                'disable_html_escape' => true,
                                            ),
                                            'label_attributes' => array(
                                                'class' => 'melis-radio-box err_create_sites_file',
                                            ),
                                            'value_options' => array(
                                                'yes' => 'tr_meliscms_tool_sites_yes',
                                                'no' => 'tr_meliscms_tool_sites_no',
                                            ),
                                        ),
                                        'attributes' => array(
                                            'required' => 'required',
                                        ),
                                    )
                                )
                            ),
                        ),

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
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
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
                                                    \Laminas\I18n\Validator\IsInt::NOT_INT => 'tr_meliscms_tool_platform_not_digit',
                                                    \Laminas\I18n\Validator\IsInt::INVALID => 'tr_meliscms_tool_platform_not_digit',
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
                                                    \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_language_lang_locale_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_language_lang_locale_empty',
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
                                                    \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_language_lang_name_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_language_lang_name_empty',
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
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
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
                                                    \Laminas\Validator\Digits::NOT_DIGITS => 'tr_meliscms_tool_platform_not_digit',
                                                    \Laminas\Validator\Digits::STRING_EMPTY => '',
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
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_platform_empty',
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
                                                    \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_platform_value_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'Digits',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\Digits::NOT_DIGITS => 'tr_meliscms_tool_platform_not_digit',
                                                    \Laminas\Validator\Digits::STRING_EMPTY => '',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_platform_empty',

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
                                                    \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_platform_value_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'Digits',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\Digits::NOT_DIGITS => 'tr_meliscms_tool_platform_not_digit',
                                                    \Laminas\Validator\Digits::STRING_EMPTY => '',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_platform_empty',
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
                                                    \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_platform_value_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'Digits',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\Digits::NOT_DIGITS => 'tr_meliscms_tool_platform_not_digit',
                                                    \Laminas\Validator\Digits::STRING_EMPTY => '',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_platform_empty',
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
                                                    \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_platform_value_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'Digits',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\Digits::NOT_DIGITS => 'tr_meliscms_tool_platform_not_digit',
                                                    \Laminas\Validator\Digits::STRING_EMPTY => '',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_platform_empty',
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
                                                    \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_platform_value_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'Digits',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\Digits::NOT_DIGITS => 'tr_meliscms_tool_platform_not_digit',
                                                    \Laminas\Validator\Digits::STRING_EMPTY => '',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_platform_empty',
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
                                                    \Laminas\Validator\StringLength::TOO_LONG => 'tr_meliscms_tool_platform_value_too_long',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'Digits',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\Digits::NOT_DIGITS => 'tr_meliscms_tool_platform_not_digit',
                                                    \Laminas\Validator\Digits::STRING_EMPTY => '',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_platform_empty',
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
                            'site_label' => array(
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
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
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
                                                    \Laminas\I18n\Validator\IsInt::NOT_INT => 'tr_meliscms_tool_platform_not_digit',
                                                    \Laminas\I18n\Validator\IsInt::INVALID => 'tr_meliscms_tool_platform_not_digit',
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
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'meliscms_tool_site_301_value_empty',
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
                                                    \Laminas\Validator\StringLength::TOO_LONG => 'meliscms_tool_site_301_value_too_long_255',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'meliscms_tool_site_301_value_empty',
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
                                                    \Laminas\Validator\StringLength::TOO_LONG => 'meliscms_tool_site_301_value_too_long_255',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'meliscms_tool_site_301_value_empty',
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
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
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
                                            'class' => 'melis-check-box',
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
                                array(
                                    'spec' => array(
                                        'name' => 'use_root',
                                        'type' => 'checkbox',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tree_sites_duplication_use_root',
                                            'tooltip' => 'tr_meliscms_tree_sites_duplication_root tooltip',
                                        ),
                                        'attributes' => array(
                                            'id' => 'use_root',
                                            'class' => 'use_root_orig_checkbox melis-check-box'
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
                                            'name' => 'NotEmpty',
                                            'break_chain_on_failure' => true,
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_duplicate_field_empty',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'IsInt',
                                            'break_chain_on_failure' => true,
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\I18n\Validator\IsInt::NOT_INT => 'tr_meliscms_tool_duplicate_field_digits',
                                                )
                                            )
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
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_page_form_plang_lang_id_empty',
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
                                            'name' => 'NotEmpty',
                                            'break_chain_on_failure' => true,
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_tool_duplicate_field_empty',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name'    => 'IsInt',
                                            'break_chain_on_failure' => true,
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\I18n\Validator\IsInt::NOT_INT => 'tr_meliscms_tool_duplicate_field_digits',
                                                )
                                            )
                                        ),
                                    ),
                                    'filters' => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
                            ),
                        ),
                        'meliscms_tree_sites_export_page_form' => array(
                            'attributes' => array(
                                'name' => 'meliscms_tree_sites_export_page_form',
                                'id' => 'pageExportForm',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'type' => 'hidden',
                                        'name' => 'selected_page_id',
                                        'attributes' => array(
                                            'id' => 'selected_page_id'
                                        ),
                                    )
                                ),
                                array(
                                    'spec' => array(
                                        'type' => 'Laminas\Form\Element\Radio',
                                        'name' => 'page_export_type',
                                        'options' => array(
                                            'label' => 'tr_melis_cms_tree_export_select_export_option',
                                            'tooltip' => 'tr_melis_cms_tree_export_select_export_option_tooltip',
                                            'label_options' => array(
                                                'disable_html_escape' => true,
                                            ),
                                            'label_attributes' => array(
                                                'class' => 'melis-radio-box',
                                            ),
                                            'value_options' => array(
                                                '1' => 'tr_melis_cms_tree_export_page_and_children',
                                                '2' => 'tr_melis_cms_tree_export_page_only',
                                            ),
                                        ),
                                        'attributes' => array(

                                        ),
                                    )
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'export_page_resources',
                                        'type' => 'MelisText',
                                        'options' => array(
                                        ),
                                        'attributes' => array(
                                            'id' => 'export_page_resources',
                                            'class' => 'export_page_resources',
                                            'value' => '',
                                            'data-label' => 'tr_melis_cms_tree_export_page_resources',
                                            'data-tooltip' => 'tr_melis_cms_tree_export_page_resources_tooltip'
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'meliscms_tree_sites_import_page_form' => array(
                            'attributes' => array(
                                'name' => 'meliscms_tree_sites_import_page_form',
                                'id' => 'id_meliscms_tree_sites_import_page_form',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Laminas\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'type' => 'File',
                                        'name' => 'page_tree_import',
                                        'options' => array(
                                            'label' => '',
                                            'tooltip' => 'tr_melis_cms_page_tree_import_modal_zip_tooltip',
                                            'label_options' => array(
                                                'disable_html_escape' => true,
                                            ),
                                            'filestyle_options' => array(
                                                'buttonBefore' => true,
                                                'buttonText' => 'tr_melis_cms_page_tree_import_zip_file',
                                            )
                                        ),
                                        'attributes' => array(
                                            'id' => 'pageImportFileUpload'
                                        ),
                                    )
                                ),
                            ),
                            'input_filter' => array(
                                'page_tree_import' => array(
                                    'name'     => 'page_tree_import',
                                    'required' => true,
                                    'validators' => array(
                                        [
                                            'name' => 'fileuploadfile',
                                            'break_chain_on_failure' => true,
                                            'options' => [
                                                'messages' => [
                                                    \Laminas\Validator\File\UploadFile::NO_FILE => 'tr_melis_cms_page_tree_import_ko_no_file',
                                                ],
                                            ],
                                        ],
                                        array(
                                            'name' => 'FileExtension',
                                            'break_chain_on_failure' => true,
                                            'options' => array(
                                                'messages' => array(
                                                    \Laminas\Validator\File\Extension::FALSE_EXTENSION => 'tr_melis_cms_page_tree_import_wrong_extension',
                                                ),
                                                'case' => true,
                                                'extension' => [
                                                    'zip'
                                                ]
                                            ),
                                        ),
                                    ),
                                    'filters' => array(
                                    ),
                                ),
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
                                        'attributes' => [
                                            'id' => 'melis_core_gdpr_search_form_site_id',
                                        ]
                                    ],
                                ]
                            ],
                            'input_filter' => [
                                'site_id' => [
                                    'name' => 'site_id',
                                    'required' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ),
);  
