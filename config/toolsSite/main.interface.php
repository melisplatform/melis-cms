<?php

return array(
    'plugins' => array(
        'meliscms' => array(
            'ressources' => array(
                'js' => array(
                    '/MelisCms/js/tools/sites/sites.tool.js',
                    '/MelisCms/js/owl.carousel.js',
                ),
                'css' => array(
                    '/MelisCms/css/tools/sites/sites.tool.css',
                    '/MelisCms/css/owl/owl.carousel.css',
                ),

            ),
            'datas' => [
                /**
                 * This is the default steps and order when making a site,
                 * you can add a new step by overriding this in you config
                 */
                'site_creation_steps_order' => [
                    /**
                     * Key is the melisKey in you interface config
                     */
                    'meliscms_tool_sites_modal_add_step_multi_lingual' => [
                        'position' => 1,//the position in which it will be put
                        'title' => 'tr_melis_cms_sites_tool_add_header_title_multi_lingual',//step title
                        'beforeMove' => 'multiLingualProcess',//a javascript function that we call before move event
                        'afterMove' => '',//a javascript function that we call after after move event
                    ],
                    'meliscms_tool_sites_modal_add_step_languages' => [
                        'position' => 2,
                        'title' => 'tr_melis_cms_sites_tool_add_header_title_lang',
                        'beforeMove' => 'languagesProcess',
                    ],
                    'meliscms_tool_sites_modal_add_step_domains' => [
                        'position' => 3,
                        'title' => 'tr_melis_cms_sites_tool_add_header_title_domains',
                        'beforeMove' => 'domainsProcess',
                    ],
                    'meliscms_tool_sites_modal_add_step_modules' => [
                        'position' => 4,
                        'title' => 'tr_melis_cms_sites_tool_add_header_title_modules',
                        'beforeMove' => 'modulesProcess',
                    ],
                    'meliscms_tool_sites_modal_add_step_summary' => [
                        'position' => 100,//we make it big because this must be always at the end since its the summary
                        'title' => 'tr_melis_cms_sites_tool_add_header_title_site_summary',
                        'beforeMove' => 'summaryProcess',
                    ],
                ]
            ],
            'interface' => array(
                'meliscms_toolstree' => array(
                    'interface' => array(
                        'meliscms_tool_sites' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_sites',
                                'name' => 'tr_meliscms_tool_sites',
                                'melisKey' => 'meliscms_tool_sites',
                                'icon' => 'fa-book',
                                'rights_checkbox_disable' => true,
                                'follow_regular_rendering' => false,
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Sites',
                                'action' => 'render-tool-sites',
                                'jscallback' => '',
                                'jsdatas' => array(),
                            ),
                            'interface' => array(
                                'meliscms_tool_sites_header' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_sites_header',
                                        'name' => 'tr_melis_cms_sites_tool_content_header',
                                        'melisKey' => 'meliscms_tool_sites_header',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Sites',
                                        'action' => 'render-tool-sites-header',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'meliscms_tool_sites_header_add' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_sites_header_add',
                                                'name' => 'tr_melis_cms_sites_tool_add_site_button',
                                                'melisKey' => 'meliscms_tool_sites_header_add',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-header-add',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                    ),
                                ), // end header
                                //site content
                                'meliscms_tool_sites_contents' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_sites_contents',
                                        'name' => 'tr_melis_cms_sites_tool_content',
                                        'melisKey' => 'meliscms_tool_sites_contents',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Sites',
                                        'action' => 'render-tool-sites-content',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                ), // end contents
                                'meliscms_tool_sites_edit_site' => array(
                                    'conf' => array(
                                        'type' => '/meliscms/interface/meliscms_toolstree/interface/meliscms_tool_sites_edit_site',
                                    ),
                                ),
                            ),
                        ),
                        //add new site
                        'meliscms_tool_sites_modal_container' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_sites_modal_container',
                                'name' => 'meliscms_tool_sites_modal_container',
                                'melisKey' => 'meliscms_tool_sites_modal_container',
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Sites',
                                'action' => 'render-tool-sites-modal-container',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                            'interface' => array(
                                'meliscms_tool_sites_modal_add' => array(
                                    'conf' => array(
                                        'id'   => 'id_meliscms_tool_sites_modal_add_content',
                                        'name' => 'tr_meliscms_tool_sites_modal_add_content',
                                        'melisKey' => 'meliscms_tool_sites_modal_add_content',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Sites',
                                        'action' => 'render-tool-sites-modal-add',
                                        'jscallback' => 'initializeStep();',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array (
                                        'meliscms_tool_sites_modal_add_step_multi_lingual' => array(
                                            'conf' => array(
                                                'id'   => 'id_meliscms_tool_sites_modal_add_step_multi_lingual',
                                                'name' => 'tr_meliscms_tool_sites_modal_add_step_multi_lingual',
                                                'melisKey' => 'meliscms_tool_sites_modal_add_step_multi_lingual',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-modal-add-step-multi-lingual',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                        'meliscms_tool_sites_modal_add_step_languages' => array(
                                            'conf' => array(
                                                'id'   => 'id_meliscms_tool_sites_modal_add_step_languages',
                                                'name' => 'tr_meliscms_tool_sites_modal_add_step_languages',
                                                'melisKey' => 'meliscms_tool_sites_modal_add_step_languages',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-modal-add-step-languages',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                        'meliscms_tool_sites_modal_add_step_domains' => array(
                                            'conf' => array(
                                                'id'   => 'id_meliscms_tool_sites_modal_add_step_domains',
                                                'name' => 'tr_meliscms_tool_sites_modal_add_step_domains',
                                                'melisKey' => 'meliscms_tool_sites_modal_add_step_domains',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-modal-add-step-domains',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                        'meliscms_tool_sites_modal_add_step_modules' => array(
                                            'conf' => array(
                                                'id'   => 'id_meliscms_tool_sites_modal_add_step_modules',
                                                'name' => 'tr_meliscms_tool_sites_modal_add_step_modules',
                                                'melisKey' => 'meliscms_tool_sites_modal_add_step_modules',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-modal-add-step-modules',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                        'meliscms_tool_sites_modal_add_step_summary' => array(
                                            'conf' => array(
                                                'id'   => 'id_meliscms_tool_sites_modal_add_step_summary',
                                                'name' => 'tr_meliscms_tool_sites_modal_add_step_summary',
                                                'melisKey' => 'meliscms_tool_sites_modal_add_step_summary',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-modal-add-step-summary',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                    ),
                                ),
                                'meliscms_tool_sites_modal_edit' => array(
                                    'id' => 'id_meliscms_tool_sites_modal_edit',
                                    'class' => 'glyphicons pencil',
                                    'tab-header' => '',
                                    'tab-text' => 'tr_meliscms_tool_sites_update_sites',
                                    'content' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Sites',
                                        'action' => 'render-tool-sites-modal-edit',
                                    ),

                                ),
                            ),
                        ),
                        //start site edit interface
                        'meliscms_tool_sites_edit_site' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_sites_edit_site',
                                'melisKey' => 'meliscms_tool_sites_edit_site',
                                'name' => 'Edit Site',
                                'rights_checkbox_disable' => true,
                                'follow_regular_rendering' => false,
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Sites',
                                'action' => 'render-tool-sites-edit',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                            'interface' => array(
                                'meliscms_tool_sites_edit_site_header' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_sites_edit_site_header',
                                        'name' => 'tr_melis_cms_sites_tool_content_edit_header',
                                        'melisKey' => 'meliscms_tool_sites_edit_site_header',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Sites',
                                        'action' => 'render-tool-sites-edit-header',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'meliscms_tool_sites_edit_site_header_save' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_sites_edit_site_header_save',
                                                'name' => 'tr_melis_cms_sites_tool_content_edit_button_save',
                                                'melisKey' => 'meliscms_tool_sites_edit_site_header_save',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-edit-site-header-save',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                    ),
                                ), // end header
                                'meliscms_tool_sites_edit_site_tabs' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_sites_edit_site_tabs',
                                        'melisKey' => 'meliscms_tool_sites_edit_site_tabs',
                                        'name' => 'tr_melis_cms_sites_tool_content_edit_tab_list',
                                        'rights_checkbox_disable' => true,
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Sites',
                                        'action' => 'render-tool-sites-tabs',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' =>  array(

                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
);