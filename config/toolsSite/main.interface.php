<?php

return array(
    'plugins' => array(
        'meliscore' => array(
            'interface' => array(
                'meliscore_leftmenu' => array(
                    'interface' => array(
                        'meliscms_toolstree_section' => array(
                            'interface' => array(
                                'meliscms_site_tools' => array(
                                    'interface' => array(
                                        'meliscms_tool_sites' => array(
                                            'conf' => array(
                                                'type' => '/meliscms/interface/meliscms_toolstree/interface/meliscms_tool_sites',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
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
                                        'meliscms_tool_sites_modal_add_step1' => array(
                                            'conf' => array(
                                                'id'   => 'id_meliscms_tool_sites_modal_add_step1',
                                                'name' => 'tr_meliscms_tool_sites_modal_add_step1',
                                                'melisKey' => 'meliscms_tool_sites_modal_add_step1',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-modal-add-step1',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                        'meliscms_tool_sites_modal_add_step2' => array(
                                            'conf' => array(
                                                'id'   => 'id_meliscms_tool_sites_modal_add_step2',
                                                'name' => 'tr_meliscms_tool_sites_modal_add_step2',
                                                'melisKey' => 'meliscms_tool_sites_modal_add_step2',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-modal-add-step2',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                        'meliscms_tool_sites_modal_add_step3' => array(
                                            'conf' => array(
                                                'id'   => 'id_meliscms_tool_sites_modal_add_step3',
                                                'name' => 'tr_meliscms_tool_sites_modal_add_step3',
                                                'melisKey' => 'meliscms_tool_sites_modal_add_step3',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-modal-add-step3',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                        'meliscms_tool_sites_modal_add_step4' => array(
                                            'conf' => array(
                                                'id'   => 'id_meliscms_tool_sites_modal_add_step4',
                                                'name' => 'tr_meliscms_tool_sites_modal_add_step4',
                                                'melisKey' => 'meliscms_tool_sites_modal_add_step4',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-modal-add-step4',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                        'meliscms_tool_sites_modal_add_step5' => array(
                                            'conf' => array(
                                                'id'   => 'id_meliscms_tool_sites_modal_add_step5',
                                                'name' => 'tr_meliscms_tool_sites_modal_add_step5',
                                                'melisKey' => 'meliscms_tool_sites_modal_add_step5',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-modal-add-step5',
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