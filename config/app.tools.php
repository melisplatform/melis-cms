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
                        'dataFunction' => '',
                        'ajaxCallback' => '',
                        'filters' => array(
                            'left' => array(
                                'toolTemplates-limit' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'ToolTemplate',
                                    'action' => 'render-tool-template-content-filters-limit',
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
                                'css' => array('width' => '1%', 'padding-right' => '0'),
                                'sortable' => true,
                                
                            ),
                            'tpl_name' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_name',
                                'css' => array('width' => '12%', 'padding-right' => '0'),
                                'sortable' => true,
                                
                            ),
                            'tpl_zf2_website_folder' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_zf2_website_folder',
                                'css' => array('width' => '12%', 'padding-right' => '0'),
                                'sortable' => true,
                                
                            ),
                            'tpl_zf2_layout' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_zf2_layout',
                                'css' => array('width' => '17%', 'padding-right' => '0'),
                                'sortable' => true,
                                
                            ),
                            'tpl_site_id' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_site_id',
                                'css' => array('width' => '12%', 'padding-right' => '0'),
                                'sortable' => true,
                                
                            ),                        
                      
                            'tpl_type' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_type',
                                'css' => array('width' => '12%', 'padding-right' => '0'),
                                'sortable' => true,
                                
                            ),                        
                                                 
                            'tpl_zf2_controller' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_zf2_controller',
                                'css' => array('width' => '12%', 'padding-right' => '0'),
                                'sortable' => true,
                                
                            ),                        
                            'tpl_php_path' => array(
                                'text' => 'tr_meliscms_tool_templates_tpl_php_path',
                                'css' => array('width' => '12%', 'padding-right' => '0'),
                                'sortable' => true,
                                
                            ),                                                                   
                        ),
                    
                        // define what columns can be used in searching
                        'searchables' => array('tpl_id', 'tpl_name', 'tpl_zf2_website_folder', 'tpl_zf2_layout', 'tpl_site_id', 'tpl_type', 'tpl_zf2_controller', 'tpl_php_path'),
                        
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
                                            'label' => 'tr_meliscms_tool_templates_tpl_id',
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
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_site_id',
                                            'value' => '',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'tpl_name',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_template_form_tpl_name',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_name',
                                            'value' => '',
                                            'maxlength' => 255,
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'tpl_type',
                                        'type' => 'Zend\Form\Element\Select',
                                        'options' => array(
                                            'label' => 'tr_meliscms_template_form_tpl_type',
                                            'empty_option' => 'tr_meliscms_form_common_Choose',
                                            'value_options' => array(
                                                'PHP' => 'PHP',
                                                'ZF2' => 'ZF2',
                                            ),
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_type',
                                            'value' => '',
                                            
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'tpl_zf2_website_folder',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_template_form_tpl_zf2_website_folder',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_zf2_website_folder',
                                            'value' => '',
                                            'maxlength' => 255,
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'tpl_zf2_layout',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_template_form_tpl_zf2_layout',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_zf2_layout',
                                            'value' => '',
                                            'maxlength' => 255,
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'tpl_zf2_controller',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_template_form_tpl_zf2_controller',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_zf2_controller',
                                            'value' => '',
                                            'maxlength' => 255,
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'tpl_zf2_action',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_template_form_tpl_zf2_action',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_zf2_action',
                                            'value' => '',
                                            'maxlength' => 255,
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'tpl_php_path',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_template_form_tpl_php_path',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_tpl_php_path',
                                            'value' => '',
                                            'maxlength' => 255,
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
                                'tpl_zf2_website_folder' => array(
                                    'name'     => 'tpl_zf2_website_folder',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name'    => 'StringLength',
                                            'options' => array(
                                                'encoding' => 'UTF-8',
                                                //'min'      => 1,
                                                'max'      => 50,
                                                'messages' => array(
                                                    \Zend\Validator\StringLength::TOO_LONG => 'tr_meliscms_template_form_tpl_website_folder_error_high',
                                                    //    \Zend\Validator\StringLength::TOO_SHORT => 'tr_meliscms_template_form_tpl_website_folder_error_low',
                                                ),
                                            ),
                                        ),
                                        array(
                                            'name' => 'NotEmpty',
                                            'options' => array(
                                                'messages' => array(
                                                    \Zend\Validator\NotEmpty::IS_EMPTY => 'tr_meliscms_template_form_tpl_website_folder_error_empty',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'filters'  => array(
                                        array('name' => 'StripTags'),
                                        array('name' => 'StringTrim'),
                                    ),
                                ),
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
                        'meliscms_site_tool_generic_form' => array(
                            'attributes' => array(
                                'name' => 'formsite',
                                'id' => 'idformsite',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'ssite_id',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_id',
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
                                        'name' => 'ssite_name',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_site_name',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_site_name',
                                            'value' => '',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'ssite_main_page_id',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_page_id',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_site_main_page_id',
                                            'value' => '',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 's404_page_id',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_404_page_id',
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
                                            'disable_inarray_validator' => true,
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_sdom_env',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'sdom_scheme',
                                        'type' => 'Zend\Form\Element\Select',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_scheme',
                                            'value_options' => array(
                                                'http' => 'http://',
                                                'https' => 'https://',
                                            ),
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_sdom_scheme',
                                            'value' => '',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'sdom_domain',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_domain',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_sdom_domain',
                                            'value' => '',
                                        ),
                                    ),
                                ),

                                
                            ), // end elements
                            'input_filter' => array(
                                'ssite_name' => array(
                                    'name'     => 'ssite_name',
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
                                'ssite_main_page_id' => array(
                                    'name'     => 'ssite_main_page_id',
                                    'required' => true,
                                    'validators' => array(
                                        array(
                                            'name' => 'regex', false,
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

                                's404_page_id' => array(
                                    'name'     => 's404_page_id',
                                    'required' => false,
                                    'validators' => array(
                                        array(
                                            'name' => 'regex', false,
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
                                'select_env' => array(
                                    'name'     => 'select_env',
                                    'required' => false,
                                    'validators' => array(
                                    ),
                                    'filters'  => array(
                                    ),
                                ),
                            ), // end input_filter
                            'align_elements' => array('ssite_id', 'ssite_name', 'ssite_main_page_id', 's404_page_id', 'select_env', 'sdom_env', 'sdom_scheme', 'sdom_domain
                                '),
                            
                            
                        ), // end form
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
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_lang_name',
                                            'value' => '',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'lang_cms_locale',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_language_lang_locale',
                                        ),
                                        'attributes' => array(
                                            'id' => 'id_lang_locale',
                                            'value' => '',
                                        ),
                                    ),
                                ),
                
                            ),
                            'input_filter' => array(
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
                                            'empty_option' => 'tr_meliscms_form_common_Choose',
                                            'disable_inarray_validator' => true,
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_name_select',
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
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_page_id_start',
                                            'value' => '',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'pids_page_id_current',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_platform_pids_page_id_current',
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_page_id_current',
                                            'value' => '',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'pids_page_id_end',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_platform_pids_page_id_end',
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_page_id_current',
                                            'value' => '',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'pids_tpl_id_start',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_platform_pids_tpl_id_start',
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_tpl_id_start',
                                            'value' => '',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'pids_tpl_id_current',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_platform_pids_tpl_id_current',
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_tpl_id_current',
                                            'value' => '',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 'pids_tpl_id_end',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_platform_pids_tpl_id_end',
                                        ),
                                        'attributes' => array(
                                            'id' => 'pids_tpl_id_end',
                                            'value' => '',
                                        ),
                                    ),
                                ),
                            ),
                            'input_filter' => array(
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
                                                    \Zend\Validator\Digits::STRING_EMPTY => 'tr_meliscms_tool_platform_empty',
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
                        'dataFunction' => '',
                        'ajaxCallback' => '',
                        'filters' => array(
                            'left' => array(
                                'tool-site-redirect-limit' => array(
                                    'module' => 'MelisCms',
                                    'controller' => 'SiteRedirect',
                                    'action' => 'render-tool-site-redirect-filters-limit',
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
                            's301_old_url' => array(
                                'text' => 'tr_meliscms_tool_site_301_s301_old_url',
                                'css' => array('width' => '45%'),
                                'sortable' => true,
                            ),
                            's301_new_url' => array(
                                'text' => 'tr_meliscms_tool_site_301_s301_new_url',
                                'css' => array('width' => '45%'),
                                'sortable' => true,
                            ),
                        ),
                        // define what columns can be used in searching
                        'searchables' => array('s301_id', 's301_old_url', 's301_new_url'),
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
                                        'name' => 's301_old_url',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_301_s301_old_url'
                                        ),
                                        'attributes' => array(
                                            'id' => 's301_old_url',
                                        ),
                                    ),
                                ),
                                array(
                                    'spec' => array(
                                        'name' => 's301_new_url',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'label' => 'tr_meliscms_tool_site_301_s301_new_url'
                                        ),
                                        'attributes' => array(
                                            'id' => 's301_old_url',
                                        ),
                                    ),
                                ),
                            ),
                            'input_filter' => array(
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
                )
            ),
        ),
    ),
);  