<?php

return array(
    'plugins' => array(
        'meliscms' => array(
            'tools' => array(
                'meliscms_tool_sites' => array(
                    'conf' => array(
                        'title' => 'tr_meliscms_tool_site',
                        'id' => 'id_meliscms_tool_site'
                    ),
                    'table' => array(
                        'target' => '#tableToolSites',
                        'ajaxUrl' => '/melis/MelisCms/Sites/getSiteData',
                        'dataFunction' => '',
                        'ajaxCallback' => '',
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
                                'text' => 'tr_meliscms_tool_site_site_label',
                                'css' => array('width' => '30%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                            'site_name' => array(
                                'text' => 'tr_meliscms_tool_site_new_site_name',
                                'css' => array('width' => '30%', 'padding-right' => '0'),
                                'sortable' => true,
                            ),
                        ),
                        'searchables' => array(
                            'melis_cms_site.site_id',
                            'melis_cms_site.site_name',
                            'melis_cms_site.site_label',

                        ),
                        'actionButtons' => array(
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
                                'id' => 'site-translation-form',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'is_multi_language',
                                        'type' => 'Select',
                                        'options' => array(
                                            'tooltip' => '',
                                            'label' => 'tr_melis_cms_sites_tool_add_step1_is_multi_lang',
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
                            'input_filter' => array(

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
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                //add other field here for step2
                            ),
                            'input_filter' => array(
                                //fields filter
                            ),
                        ),
                        'meliscms_tool_sites_modal_add_step2_form_single_language' => array(
                            'attributes' => array(
                                'name' => 'toolsitesadd_step2form-single_language',
                                'id' => 'step2form-single_language',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                //add other field here for step2
                            ),
                            'input_filter' => array(
                                //fields filter
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
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                array(
                                    'spec' => array(
                                        'name' => 'single_domain_name',
                                        'type' => 'MelisText',
                                        'options' => array(
                                            'tooltip' => '',
                                            'label' => 'tr_melis_cms_sites_tool_add_step1_is_multi_lang',
                                        ),
                                        'attributes' => array(
                                            'id' => 'single_domain_name',
                                        ),
                                    ),
                                ),
                            ),
                            'input_filter' => array(
                                //fields filter
                            ),
                        ),
                        'meliscms_tool_sites_modal_add_step3_form_multi_domain' => array(
                            'attributes' => array(
                                'name' => 'toolsitesadd_step3form-single_domain',
                                'id' => 'step3form-single_domain',
                                'method' => 'POST',
                                'action' => '',
                            ),
                            'hydrator'  => 'Zend\Stdlib\Hydrator\ArraySerializable',
                            'elements' => array(
                                //add other field here for step3 multi domain
                            ),
                            'input_filter' => array(
                                //fields filter
                            ),
                        ),
                    ),

                ), // end Melis CMS Site Tool
            ),
        ),
    ),
);  