<?php

return array(
    'plugins' => array(
        'meliscore' => array(
            'datas' => [],
            'interface' => array(
                'meliscore_leftmenu' => array(
                    'interface' => array(
                        'meliscms_sitetree' =>  array(
                            'conf' => array(
                                'id' => 'id_meliscms_menu_sitetree',
                                'name' => 'tr_meliscms_menu_sitetree_Name',
                                'type' => '/meliscms/interface/meliscms_sitetree',
                            ),
                        ),
                        'meliscms_toolstree_section' =>  array(
                            'interface' => array(
                                /* 'meliscms_sitetree' =>  array(
                                   'conf' => array(
                                       'id' => 'id_meliscms_menu_sitetree',
                                       'name' => 'tr_meliscms_menu_sitetree_Name',
                                       'melisKey' => 'meliscms_sitetree',
                                   ),
                                   'forward' => array(
                                       'module' => 'MelisCms',
                                       'controller' => 'TreeSites',
                                       'action' => 'get-tree-pages-by-page-id',
                                       'jscallback' => 'treeCallBack();',
                                       'jsdatas' => array()
                                   ),
                                   'interface' => [
                                       'meliscms_sitetree' =>  array(
                                           'conf' => array(
                                               'id' => 'id_meliscms_menu_sitetree',
                                               'name' => 'tr_meliscms_menu_sitetree_Name',
                                               'melisKey' => 'meliscms_sitetree',
                                               'rightsDisplay' => 'referencesonly',
                                           ),
                                           'forward' => array(
                                               'module' => 'MelisCms',
                                               'controller' => 'TreeSites',
                                               'action' => 'get-tree-pages-by-page-id',
                                               'jscallback' => 'treeCallBack();',
                                               'jsdatas' => array()
                                           ),
                                       ),
                                   ]
                               ), */
                                'meliscms_site_tools' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_menu_sitetree',
                                        'name' => 'tr_meliscms_sites_tools',
                                        'rights_checkbox_disable' => true,
                                        'melisKey' => 'meliscms_site_tools',
                                    ),
                                    'interface' => [

                                        'meliscms_tool_sites' => array(
                                            'conf' => array(
                                                'type' => '/meliscms/interface/meliscms_toolstree/interface/meliscms_tool_sites',
                                            ),
                                        ),
                                        'meliscms_tool_site_301' => array(
                                            'conf' => array(
                                                'type' => '/meliscms/interface/meliscms_toolstree/interface/meliscms_tool_site_301',
                                            ),
                                        ),
                                        'meliscms_tool_templates' => array(
                                            'conf' => array(
                                                'type' => '/meliscms/interface/meliscms_toolstree/interface/meliscms_tool_templates',
                                            ),
                                        ),
                                        'meliscms_tool_styles' => array(
                                            'conf' => array(
                                                'type' => '/meliscms/interface/meliscms_toolstree/interface/meliscms_tool_styles',
                                            ),
                                        ),
                                        'meliscms_tool_language' => array(
                                            'conf' => array(
                                                'type' => '/meliscms/interface/meliscms_toolstree/interface/meliscms_tool_language',
                                            ),
                                        ),
                                        'meliscms_tool_platform_ids' => array(
                                            'conf' => array(
                                                'type' => '/meliscms/interface/meliscms_toolstree/interface/meliscms_tool_platform_ids',
                                            ),
                                        ),
                                    ]
                                ),
                                // Mini Template Menu
                                'meliscms_mini_template_manager' => [
                                    'conf' => [
                                        'id' => 'id_meliscms_menu_mini_template_manager',
                                        'name' => 'tr_meliscms_mini_template_manager',
                                        'rights_checkbox_disable' => false,
                                        'melisKey' => 'meliscms_mini_template_manager',
                                        'icon' => 'fa-file-code-o',
                                    ],
                                    'interface' => [
                                        'meliscms_mini_template_manager_tool' => [
                                            'conf' => [
                                                'type' => '/meliscms/interface/meliscms_mini_template_manager/interface/meliscms_mini_template_manager_tool'
                                            ]
                                        ],
                                        'meliscms_mini_template_menu_manager_tool' => [
                                            'conf' => [
                                                'type' => '/meliscms/interface/meliscms_mini_template_manager/interface/meliscms_mini_template_menu_manager_tool'
                                            ]
                                        ],
                                    ],
                                ]
                            ),
                        ),
                    ),
                ),
                'meliscore_center' => array(
                    'interface' => array()
                ),
            )
        ),
        'meliscms' => array(
            'conf' => array(
                'id' => 'id_melis_cms',
                'name' => 'tr_meliscms_meliscms',
                'pluginResizable' => true
            ),
            'ressources' => array(
                'js' => array(
                    // melisCms core JS
                    '/MelisCms/js/cmsCore/melisCms.js',
                    // fancty tree init
                    '/MelisCms/js/fancytreeInit/fancyTreeInit.js',
                    '/MelisCms/js/tools/template.tool.js',
                    '/MelisCms/js/tools/langCms.tool.js',
                    '/MelisCms/js/tools/platform.tool.js',
                    '/MelisCms/js/tools/site-redirect.tool.js',
                    '/MelisCms/js/tools/findpage.tool.js',
                    '/MelisCms/js/tools/page-duplicate.tool.js',
                    '/MelisCms/js/tools/searchpage.tool.js',
                    '/MelisCms/js/tools/pagelang.js',
                    '/MelisCms/js/tools/style.tool.js',
                    '/MelisCms/js/tools/gdpr-banner.js',
                    '/MelisCms/js/tools/page-export-import.js',
                    // jsTree
                    '/MelisCms/assets/jstree/dist/jstree.min.js',
                    '/MelisCms/js/tools/sites/site-translation.js',
                ),
                'css' => array(
                    '/MelisCms/css/fancytree.custom.css',
                    '/MelisCms/css/styles.css',
                    //jstree
                    '/MelisCms/assets/jstree/dist/themes/default/style.min.css',
                ),
                /**
                 * the "build" configuration compiles all assets into one file to make
                 * lesser requests
                 */
                'build' => [
                    'disable_bundle' => false,
                    // lists of assets that will be loaded in the layout
                    'css' => [
                        '/MelisCms/build/css/bundle.css',

                    ],
                    'js' => [
                        '/MelisCms/build/js/bundle.js',
                    ]
                ]

            ),
            'datas' => array(
                /**
                 * Used to copy necessary file to
                 * main public/ folder (image/font/icon only)
                 */
                'bundle_all_needed_files' => [
                    //will be put inside css folder
                    'css' => [
                        '/build/css/fonts',
                        /* '/build/css/30px.png',
                        '/build/css/32px.png',
                        '/build/css/40px.png',
                        '/build/css/throbber.gif', */
                        '/assets/jstree/dist/themes/default/32px.png',
                        '/assets/jstree/dist/themes/default/40px.png',
                        '/assets/jstree/dist/themes/default/throbber.gif',
                    ],
                    //will be put inside js folder
                    'js' => []
                ]
            ),
            'interface' => array(
                'meliscms_sitetree' =>  array(
                    'conf' => array(
                        'id' => 'id_meliscms_menu_sitetree',
                        'name' => 'tr_meliscms_menu_sitetree_Name',
                        'melisKey' => 'meliscms_sitetree',
                        'rightsDisplay' => 'referencesonly',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'TreeSites',
                        'action' => 'get-tree-pages-by-page-id',
                        'jscallback' => 'treeCallBack();',
                        'jsdatas' => array()
                    ),
                ),
                'meliscms_toolstree' =>  array(
                    'conf' => array(
                        'name' => 'tr_meliscms_toolstree',
                        'rightsDisplay' => 'referencesonly',
                    ),
                    'interface' => array(
                        'meliscms_tool_templates' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_templates',
                                'name' => 'tr_meliscms_tool_templates',
                                'melisKey' => 'meliscms_tool_templates',
                                'icon' => 'fa-file-code-o',
                                'rights_checkbox_disable' => true,
                                'follow_regular_rendering' => false,
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'ToolTemplate',
                                'action' => 'render-tool-template',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                            'interface' => array(
                                'meliscms_tool_templates_header_buttons' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_templates_header_buttons',
                                        'name' => 'tr_meliscore_tool_gen_header',
                                        'melisKey' => 'meliscms_tool_templates_header_buttons',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'ToolTemplate',
                                        'action' => 'render-tool-template-header',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'tool_template_add_action' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_templates_header_add',
                                                'name' => 'tr_meliscore_tool_gen_new',
                                                'melisKey' => 'meliscms_tool_templates_header_add',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'ToolTemplate',
                                                'action' => 'render-tool-template-header-add',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                    ),
                                ),

                                'meliscms_tool_templates_contents' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_templates_content',
                                        'name' => 'tr_meliscore_tool_gen_content',
                                        'melisKey' => 'meliscms_tool_templates_content',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'ToolTemplate',
                                        'action' => 'render-tool-template-content',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'meliscms_tool_templates_action_edit' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_templates_action_edit',
                                                'name' => 'tr_meliscore_tool_gen_edit',
                                                'melisKey' => 'meliscms_tool_templates_action_edit',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'ToolTemplate',
                                                'action' => 'render-tool-templates-action-edit',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                        'meliscms_tool_templates_content_action_delete' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_templates_content_action_delete',
                                                'name' => 'tr_meliscore_tool_gen_delete',
                                                'melisKey' => 'meliscms_tool_templates_content_action_delete',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'ToolTemplate',
                                                'action' => 'render-tool-templates-action-delete',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                    ),

                                ),

                                'meliscms_tool_templates_content_modal' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_prospects_modal',
                                        'name' => 'tr_meliscore_tool_gen_modal',
                                        'melisKey' => 'meliscms_tool_prospects_modal',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'ToolTemplate',
                                        'action' => 'render-tool-template-modal-container',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'meliscms_tool_template_add_modal_handler' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_template_add_modal_handler',
                                                'name' => 'tr_meliscore_tool_gen_new',
                                                'melisKey' => 'meliscms_tool_template_add_modal_handler',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'ToolTemplate',
                                                'action' => 'render-tool-templates-modal-add-handler',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),

                                        'meliscms_tool_template_edit_modal_handler' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_template_edit_modal_handler',
                                                'name' => 'tr_meliscore_tool_gen_edit',
                                                'melisKey' => 'meliscms_tool_template_edit_modal_handler',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'ToolTemplate',
                                                'action' => 'render-tool-templates-modal-edit-handler',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ), // end tool templates
                        'meliscms_tool_styles' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_styles',
                                'name' => 'tr_meliscms_tool_styles',
                                'melisKey' => 'meliscms_tool_styles',
                                'icon' => 'fa-css3',
                                'rights_checkbox_disable' => true,
                                'follow_regular_rendering' => false,
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'ToolStyle',
                                'action' => 'render-tool-style',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                            'interface' => array(
                                'meliscms_tool_styles_header_buttons' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_styles_header_buttons',
                                        'name' => 'tr_meliscms_tool_styles_mgr',
                                        'melisKey' => 'meliscms_tool_styles_header_buttons',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'ToolStyle',
                                        'action' => 'render-tool-style-header',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'tool_style_add_action' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_styles_header_add',
                                                'name' => 'tr_meliscore_tool_styles_new',
                                                'melisKey' => 'meliscms_tool_styles_header_add',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'ToolStyle',
                                                'action' => 'render-tool-style-header-add',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                    ),
                                ),

                                'meliscms_tool_styles_content' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_styles_content',
                                        'name' => 'tr_meliscore_tool_gen_content',
                                        'melisKey' => 'meliscms_tool_styles_content',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'ToolStyle',
                                        'action' => 'render-tool-style-content',
                                    ),
                                ),

                                'meliscms_tool_styles_content_modal' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_prospects_modal',
                                        'name' => 'tr_meliscore_tool_gen_modal',
                                        'melisKey' => 'meliscms_tool_prospects_modal',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'ToolStyle',
                                        'action' => 'render-tool-style-modal-container',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'meliscms_tool_styles_modal_form_handler' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_styles_modal_form_handler',
                                                'name' => 'tr_meliscore_tool_gen_new',
                                                'melisKey' => 'meliscms_tool_styles_modal_form_handler',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'ToolStyle',
                                                'action' => 'render-tool-style-modal-form-handler',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ), // end tool styles
                        //                        'meliscms_tool_site' => array(
                        //                            'conf' => array(
                        //                                'id' =>  'id_meliscms_tool_site',
                        //                                'name' => 'tr_meliscms_tool_site',
                        //                                'melisKey' => 'meliscms_tool_site',
                        //                                'icon' => 'fa-book',
                        //                                'rights_checkbox_disable' => true,
                        //                                'follow_regular_rendering' => false,
                        //                            ),
                        //                            'forward' => array(
                        //                                'module' => 'MelisCms',
                        //                                'controller' => 'Site',
                        //                                'action' => 'render-tool-site',
                        //                                'jscallback' => '',
                        //                                'jsdatas' => array(),
                        //                            ),
                        //                            'interface' => array(
                        //                                'meliscms_tool_site_header' => array(
                        //                                    'conf' => array(
                        //                                        'id' => 'id_meliscms_tool_site_header',
                        //                                        'name' => 'tr_meliscore_tool_gen_header',
                        //                                        'melisKey' => 'meliscms_tool_site_header',
                        //                                    ),
                        //                                    'forward' => array(
                        //                                        'module' => 'MelisCms',
                        //                                        'controller' => 'Site',
                        //                                        'action' => 'render-tool-site-header',
                        //                                        'jscallback' => '',
                        //                                        'jsdatas' => array()
                        //                                    ),
                        //                                    'interface' => array(
                        //                                        'meliscms_tool_site_header_add' => array(
                        //                                            'conf' => array(
                        //                                                'id' => 'id_meliscms_tool_site_header_add',
                        //                                                'name' => 'tr_meliscore_tool_gen_new',
                        //                                                'melisKey' => 'meliscms_tool_site_header_add',
                        //                                            ),
                        //                                            'forward' => array(
                        //                                                'module' => 'MelisCms',
                        //                                                'controller' => 'Site',
                        //                                                'action' => 'render-tool-site-header-add',
                        //                                                'jscallback' => '',
                        //                                                'jsdatas' => array()
                        //                                            ),
                        //                                        ),
                        //                                    ),
                        //                                ), // end header
                        //                                'meliscms_tool_site_contents' => array(
                        //                                    'conf' => array(
                        //                                        'id' => 'id_meliscms_tool_site_contents',
                        //                                        'name' => 'tr_meliscore_tool_gen_content',
                        //                                        'melisKey' => 'meliscms_tool_site_contents',
                        //                                    ),
                        //                                    'forward' => array(
                        //                                        'module' => 'MelisCms',
                        //                                        'controller' => 'Site',
                        //                                        'action' => 'render-tool-site-content',
                        //                                        'jscallback' => '',
                        //                                        'jsdatas' => array()
                        //                                    ),
                        //                                    'interface' => array(
                        //                                        'meliscms_tool_site_new_site_confirmation_modal' => array(
                        //                                            'conf' => array(
                        //                                                'id' => 'id_meliscms_tool_site_new_site_confirmation_modal',
                        //                                                'name' => 'tr_meliscms_tool_site_new_site_confirmation_modal',
                        //                                                'melisKey' => 'meliscms_tool_site_new_site_confirmation_modal',
                        //                                                'rightsDisplay' => 'none',
                        //                                            ),
                        //                                            'forward' => array(
                        //                                                'module' => 'MelisCms',
                        //                                                'controller' => 'Site',
                        //                                                'action' => 'render-tool-site-new-site-confirmation-modal',
                        //                                                'jscallback' => '',
                        //                                                'jsdatas' => array()
                        //                                            ),
                        //                                        )
                        //                                    ),
                        //                                ), // end contents
                        //                                'meliscms_tool_site_modals' => array(
                        //                                    'conf' => array(
                        //                                        'id' => 'id_meliscms_tool_site_modals',
                        //                                        'name' => 'tr_meliscore_tool_gen_modal',
                        //                                        'melisKey' => 'meliscms_tool_site_modals',
                        //                                    ),
                        //                                    'forward' => array(
                        //                                        'module' => 'MelisCms',
                        //                                        'controller' => 'Site',
                        //                                        'action' => 'render-tool-site-modal-container',
                        //                                        'jscallback' => '',
                        //                                        'jsdatas' => array()
                        //                                    ),
                        //                                    'interface' => array(
                        //                                        'meliscms_tool_site_modal_add_handler' => array(
                        //                                            'conf' => array(
                        //                                                'id' => 'id_meliscms_tool_site_modal_add_handler',
                        //                                                'name' => 'tr_meliscore_tool_gen_new',
                        //                                                'melisKey' => 'meliscms_tool_site_modal_add_handler',
                        //                                            ),
                        //                                            'forward' => array(
                        //                                                'module' => 'MelisCms',
                        //                                                'controller' => 'Site',
                        //                                                'action' => 'render-tool-site-modal-add-handler',
                        //                                                'jscallback' => '',
                        //                                                'jsdatas' => array()
                        //                                            ),
                        //                                        ),
                        //                                        'meliscms_tool_site_modal_edit_handler' => array(
                        //                                            'conf' => array(
                        //                                                'id' => 'id_meliscms_tool_site_modal_edit_handler',
                        //                                                'name' => 'tr_meliscore_tool_gen_edit',
                        //                                                'melisKey' => 'meliscms_tool_site_modal_edit_handler',
                        //                                            ),
                        //                                            'forward' => array(
                        //                                                'module' => 'MelisCms',
                        //                                                'controller' => 'Site',
                        //                                                'action' => 'render-tool-site-modal-edit-handler',
                        //                                                'jscallback' => '',
                        //                                                'jsdatas' => array()
                        //                                            ),
                        //                                        ),
                        //                                    ),
                        //                                ), // end modals
                        //                            )
                        //                        ), // end site tool

                        // Site Redirect Tool
                        'meliscms_tool_site_301' => array(
                            'conf' => array(
                                'id' =>  'id_meliscms_tool_site_301',
                                'name' => 'tr_meliscms_tool_site_301',
                                'melisKey' => 'meliscms_tool_site_301',
                                'icon' => 'fa-share',
                                'rights_checkbox_disable' => true,
                                'follow_regular_rendering' => false,
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'SiteRedirect',
                                'action' => 'render-tool-site-redirect',
                            ),
                            'interface' => array(
                                'meliscms_tool_site_301_header' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_site_301_header',
                                        'name' => 'tr_meliscms_tool_site_301_header',
                                        'melisKey' => 'meliscms_tool_site_301_header',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'SiteRedirect',
                                        'action' => 'render-tool-site-redirect-header',
                                    ),
                                    'interface' => array(
                                        'meliscms_tool_site_301_add_site_redirect' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_site_301_add_site_redirect',
                                                'name' => 'tr_meliscms_tool_site_301_add_site_redirect',
                                                'melisKey' => 'meliscms_tool_site_301_add_site_redirect',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'SiteRedirect',
                                                'action' => 'render-tool-site-redirect-add',
                                            ),
                                        )
                                    )
                                ),
                                'meliscms_tool_site_301_content' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_site_301_content',
                                        'name' => 'tr_meliscms_tool_site_301_content',
                                        'melisKey' => 'meliscms_tool_site_301_content',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'SiteRedirect',
                                        'action' => 'render-tool-site-redirect-content',
                                    ),
                                    'interface' => array(
                                        'meliscms_tool_site_301_generic_form' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_site_301_generic_form',
                                                'melisKey' => 'meliscms_tool_site_301_generic_form',
                                                'name' => 'tr_meliscms_tool_site_301_generic_form',
                                                'rightsDisplay' => 'none',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'SiteRedirect',
                                                'action' => 'render-tool-site-redirect-generic-form',
                                            ),
                                        ),
                                    )
                                )
                            )
                        ),

                        //language tool
                        'meliscms_tool_language' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_language',
                                'name' => 'tr_meliscms_tool_language',
                                'melisKey' => 'meliscms_tool_language',
                                'icon' => 'fa-language',
                                'rights_checkbox_disable' => true,
                                'follow_regular_rendering' => false,
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Language',
                                'action' => 'render-tool-language-container',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                            'interface' => array(
                                'meliscms_tool_language_header' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_language_header',
                                        'name' => 'tr_meliscms_tool_language_header',
                                        'melisKey' => 'meliscms_tool_language_header',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Language',
                                        'action' => 'render-tool-language-header',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'meliscms_tool_language_header_add' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_language_header_add',
                                                'name' => 'tr_meliscms_tool_language_new',
                                                'melisKey' => 'meliscms_tool_language_header_add',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Language',
                                                'action' => 'render-tool-language-header-add',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ), //meliscms_tool_language_header_add
                                    ), //interface
                                ), //meliscms_tool_language_header
                                'meliscms_tool_language_content' => array( //body
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_language_content',
                                        'name' => 'tr_meliscms_tool_language_content',
                                        'melisKey' => 'meliscms_tool_language_content',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Language',
                                        'action' => 'render-tool-language-content',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                ), //meliscms_tool_language_content
                                //modal
                                'meliscms_tool_language_modal' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_language_modal',
                                        'name' => 'tr_meliscms_tool_language_modal',
                                        'melisKey' => 'meliscms_tool_language_modal',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Language',
                                        'action' => 'render-tool-language-modal',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'meliscms_tool_language_modal_handler_add' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_language_modal',
                                                'name' => 'tr_meliscms_tool_language_new',
                                                'melisKey' => 'meliscms_tool_language_modal',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Language',
                                                'action' => 'render-tool-language-modal-add-handler',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                        'meliscms_tool_language_modal_handler_edit' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_language_modal',
                                                'name' => 'tr_meliscms_tool_language_edit',
                                                'melisKey' => 'meliscms_tool_language_modal',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Language',
                                                'action' => 'render-tool-language-modal-edit-handler',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                    ),
                                ),
                            ), //interface
                        ), //end of Language Tool

                        //Platform ID tool
                        'meliscms_tool_platform_ids' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_platform_ids',
                                'name' => 'tr_meliscms_tool_platform_ids',
                                'melisKey' => 'meliscms_tool_platform_ids',
                                'icon' => 'fa-list-ol',
                                'rights_checkbox_disable' => true,
                                'follow_regular_rendering' => false,
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Platform',
                                'action' => 'render-container',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                            'interface' => array(
                                'meliscms_tool_platform_ids_header' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_platform_ids_header',
                                        'melisKey' => 'meliscms_tool_platform_ids_header',
                                        'name' => 'tr_meliscms_tool_platform_ids_header',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Platform',
                                        'action' => 'render-header',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'meliscms_tool_platform_ids_add_button' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_platform_ids_add_button',
                                                'melisKey' => 'meliscms_tool_platform_ids_add_button',
                                                'name' => 'tr_meliscms_tool_platform_ids_btn_add',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Platform',
                                                'action' => 'render-header-add-button',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        )
                                    )
                                ),
                                'meliscms_tool_platform_ids_content' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_platform_ids_content',
                                        'melisKey' => 'meliscms_tool_platform_ids_content',
                                        'name' => 'tr_meliscms_tool_platform_ids_content',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Platform',
                                        'action' => 'render-content',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'meliscms_tool_platform_ids_table' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_platform_ids_table',
                                                'melisKey' => 'meliscms_tool_platform_ids_table',
                                                'name' => 'tr_meliscms_tool_platform_ids_table',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Platform',
                                                'action' => 'render-content-platform-table',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                            'interface' => array(
                                                'meliscms_tool_platform_ids_modal_content' => array(
                                                    'conf' => array(
                                                        'id' => 'id_meliscms_tool_platform_ids_modal_contentt',
                                                        'melisKey' => 'meliscms_tool_platform_ids_modal_content',
                                                        'name' => 'tr_meliscms_tool_platform_ids_modal_content',
                                                    ),
                                                    'forward' => array(
                                                        'module' => 'MelisCms',
                                                        'controller' => 'Platform',
                                                        'action' => 'render-platform-modal-content',
                                                        'jscallback' => '',
                                                        'jsdatas' => array()
                                                    ),
                                                )
                                            )
                                        ),
                                    )
                                ),
                            ), //interface
                        ), //end of Language Tool

                    ), //interface
                ),
                'meliscms_page_actions' =>  array(
                    'conf' => array(
                        'id' => 'id_meliscms_page_actions',
                        'name' => 'tr_meliscms_page_actions',
                        'melisKey' => 'meliscms_page_actions',
                        'rightsDisplay' => 'referencesonly',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'Page',
                        'action' => 'render-pageaction',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                    'interface' => array(
                        'meliscms_page_action_new' =>  array(
                            'conf' => array(
                                'id' => 'id_meliscms_page_action_new',
                                'name' => 'tr_meliscms_page_actions_New',
                                'melisKey' => 'meliscms_page_action_new'
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Page',
                                'action' => 'render-pageaction-new',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                        ),
                        'meliscms_page_action_save' =>  array(
                            'conf' => array(
                                'id' => 'id_meliscms_page_action_save',
                                'name' => 'tr_meliscms_page_actions_Save',
                                'melisKey' => 'meliscms_page_action_save'
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Page',
                                'action' => 'render-pageaction-save',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                        ),
                        'meliscms_page_action_clear' =>  array(
                            'conf' => array(
                                'id' => 'id_meliscms_page_action_clear',
                                'name' => 'tr_meliscms_page_action_clear',
                                'melisKey' => 'meliscms_page_action_clear'
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Page',
                                'action' => 'render-pageaction-clear',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                        ),
                        'meliscms_page_action_publish' =>  array(
                            'conf' => array(
                                'id' => 'id_meliscms_page_action_publish',
                                'name' => 'tr_meliscms_page_actions_Publish',
                                'melisKey' => 'meliscms_page_action_publish'
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Page',
                                'action' => 'render-pageaction-publish',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                        ),
                        'meliscms_page_action_delete' =>  array(
                            'conf' => array(
                                'id' => 'id_meliscms_page_action_delete',
                                'name' => 'tr_meliscms_page_actions_Delete Page',
                                'melisKey' => 'meliscms_page_action_delete'
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Page',
                                'action' => 'render-pageaction-delete',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                        ),
                        'meliscms_page_action_view' =>  array(
                            'conf' => array(
                                'id' => 'id_meliscms_page_action_view',
                                'name' => 'tr_meliscms_page_actions_See',
                                'melisKey' => 'meliscms_page_action_view'
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Page',
                                'action' => 'render-pageaction-view',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                            'interface' => array(
                                'meliscms_page_action_preview' =>  array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_page_action_preview',
                                        'name' => 'tr_meliscms_page_actions_Preview',
                                        'melisKey' => 'meliscms_page_action_preview'
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Page',
                                        'action' => 'render-pageaction-preview',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                ),
                                'meliscms_page_action_seeonline' =>  array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_page_action_seeonline',
                                        'name' => 'tr_meliscms_page_actions_See Online',
                                        'melisKey' => 'meliscms_page_action_seeonline'
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Page',
                                        'action' => 'render-pageaction-seeonline',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                ),
                            )
                        ),
                        'meliscms_page_action_display' =>  array(
                            'conf' => array(
                                'id' => 'id_meliscms_page_action_display',
                                'name' => 'tr_meliscms_page_actions_display_Display',
                                'melisKey' => 'meliscms_page_action_display'
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Page',
                                'action' => 'render-pageaction-display',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                            'interface' => array(
                                'meliscms_page_action_display_mobile' =>  array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_page_action_display_mobile',
                                        'name' => 'tr_meliscms_page_actions_display_Mobile',
                                        'melisKey' => 'meliscms_page_action_display_mobile'
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Page',
                                        'action' => 'render-pageaction-display-mobile',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                ),
                                'meliscms_page_action_display_tablet' =>  array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_page_action_display_tablet',
                                        'name' => 'tr_meliscms_page_actions_display_Tablet',
                                        'melisKey' => 'meliscms_page_action_display_tablet'
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Page',
                                        'action' => 'render-pageaction-display-tablet',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                ),
                                'meliscms_page_action_display_desktop' =>  array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_page_action_display_desktop',
                                        'name' => 'tr_meliscms_page_actions_display_Desktop',
                                        'melisKey' => 'meliscms_page_action_display_desktop'
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Page',
                                        'action' => 'render-pageaction-display-desktop',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                ),
                            ),
                        ),
                        'meliscms_page_action_duplicate' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_page_action_duplicate',
                                'name' => 'tr_meliscms_page_action_duplicate',
                                'melisKey' => 'meliscms_page_action_duplicate'
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'PageDuplication',
                                'action' => 'render-page-duplicate-button',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                        )
                    ),
                ),
                'meliscms_page_creation_actions' =>  array(
                    'conf' => array(
                        'id' => 'id_meliscms_page_creation_actions',
                        'name' => 'tr_meliscms_page_creation_actions',
                        'melisKey' => 'meliscms_page_creation_actions',
                        'rightsDisplay' => 'none',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'Page',
                        'action' => 'render-pageaction',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                    'interface' => array(
                        'meliscms_page_action_save' =>  array(
                            'conf' => array(
                                'id' => 'id_meliscms_page_creation_action_save',
                                'name' => 'tr_meliscms_page_actions_Save',
                                'melisKey' => 'meliscms_page_creation_action_save'
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Page',
                                'action' => 'render-pageaction-save',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                        )
                    )
                ),
                'meliscms_page' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_page',
                        'name' => 'tr_meliscms_pages_Page',
                        'melisKey' => 'meliscms_page'
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'Page',
                        'action' => 'render-page',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                    'interface' => array(
                        'meliscms_pagehead' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_pagehead',
                                'name' => 'tr_meliscms_pages_Page_header',
                                'melisKey' => 'meliscms_pagehead'
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Page',
                                'action' => 'render-pagehead',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                            'interface' => array(
                                'meliscms_pagehead_title' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_pagehead_title',
                                        'name' => 'tr_meliscms_pages_Page_status_container',
                                        'melisKey' => 'meliscms_pagehead_title'
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Page',
                                        'action' => 'render-pagehead-title',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'meliscms_page_action_publishunpublish' =>  array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_page_action_publishunpublish',
                                                'name' => 'tr_meliscms_page_actions_Publish Unpublish',
                                                'melisKey' => 'meliscms_page_action_publishunpublish'
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Page',
                                                'action' => 'render-pageaction-publishunpublish',
                                                'jscallback' => 'setOnOff();',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                    ),
                                ),
                                'meliscms_pagehead_actions' => array(
                                    'conf' => array(
                                        'type' => '/meliscms/interface/meliscms_page_actions'
                                    ),
                                ),
                            ),
                        ),
                        'meliscms_tabs' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_center_page_tabs',
                                'name' => 'tr_meliscms_pages_Page Tabs',
                                'melisKey' => 'meliscms_tabs'
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Page',
                                'action' => 'render-pagetab',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                            'interface' => array(
                                'meliscms_page_edition' =>  array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_center_page_tabs_edition',
                                        'name' => 'tr_meliscms_page_tab_edition_Edition',
                                        'melisKey' => 'meliscms_page_edition',
                                        'load' => 'iframe',
                                        'icon' => 'edit',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'PageEdition',
                                        'action' => 'render-pagetab-edition',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                ),
                                'meliscms_page_properties' =>  array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_center_page_tabs_properties',
                                        'name' => 'tr_meliscms_page_tab_properties_Properties',
                                        'melisKey' => 'meliscms_page_properties',
                                        'icon' => 'tag',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'PageProperties',
                                        'action' => 'render-pagetab-properties',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    )
                                ),
                                'meliscms_page_seo' =>  array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_center_page_tabs_seo',
                                        'name' => 'tr_meliscms_page_tab_seo_Seo',
                                        'melisKey' => 'meliscms_page_seo',
                                        'icon' => 'search',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'PageSeo',
                                        'action' => 'render-pagetab-seo',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    )
                                ),
                                'meliscms_page_languages' =>  array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_center_page_language',
                                        'name' => 'tr_meliscms_page_languages',
                                        'melisKey' => 'meliscms_page_languages',
                                        'icon' => 'font',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'PageLanguages',
                                        'action' => 'render-pagetab-languages',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'meliscms_page_lang_list' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_page_lang_list',
                                                'name' => 'tr_meliscms_page_lang_list_right_interface',
                                                'melisKey' => 'meliscms_page_lang_list',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'PageLanguages',
                                                'action' => 'render-pagetab-lang-list',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                        'meliscms_page_lang_create' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_page_lang_create',
                                                'name' => 'tr_meliscms_page_lang_create_right_interface',
                                                'melisKey' => 'meliscms_page_lang_create',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'PageLanguages',
                                                'action' => 'render-pagetab-lang-create',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                    )
                                )
                            )
                        )
                    )
                ),
                'meliscms_page_creation' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_page',
                        'name' => 'tr_meliscms_pages_Page creation',
                        'melisKey' => 'meliscms_page_creation'
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'Page',
                        'action' => 'render-page',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                    'interface' => array(
                        'meliscms_pagehead' => array(
                            'conf' => array(
                                'type' => '/meliscms/interface/meliscms_page_creation_actions'
                            ),
                        ),
                        'meliscms_tabs' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_center_page_tabs',
                                'name' => 'tr_meliscms_pages_Page Tabs',
                                'melisKey' => 'meliscms_tabs',
                                'rightsDisplay' => 'none',
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Page',
                                'action' => 'render-pagetab',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                            'interface' => array(
                                'meliscms_page_properties' =>  array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_center_page_tabs_properties',
                                        'name' => 'tr_meliscms_page_tab_properties_Properties',
                                        'melisKey' => 'meliscms_page_properties',
                                        'icon' => 'tag',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'PageProperties',
                                        'action' => 'render-pagetab-properties',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    )
                                )
                            )
                        )
                    )
                ),
                'meliscms_page_export_import_modal_handler' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_page_export_modal_handler',
                        'name' => 'tr_meliscms_page_export_modal_handler',
                        'melisKey' => 'meliscms_page_export_modal_handler',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'Page',
                        'action' => 'render-page-export-modal-handler',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                    'interface' => array(
                        'meliscms_page_export_modal' => array(
                            'conf' => array(
                                'id'   => 'id_meliscms_page_export_modal',
                                'name' => 'tr_meliscms_page_export_modal',
                                'melisKey' => 'meliscms_page_export_modal',
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'PageExport',
                                'action' => 'render-page-export-modal',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                        ),
                        'meliscms_page_import_modal' => array(
                            'conf' => array(
                                'id'   => 'id_meliscms_page_import_modal',
                                'name' => 'tr_meliscms_page_import_modal',
                                'melisKey' => 'meliscms_page_import_modal',
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'PageImport',
                                'action' => 'render-page-import-modal',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                        ),
                    ),
                ),
            )
        ),
        'meliscms_page_modal' => array(
            'conf' => array(
                'id' => 'id_meliscms_page_modal',
                'name' => 'tr_meliscms_page_modal',
                'melisKey' => 'meliscms_page_modal',
                'rightsDisplay' => 'none',
            ),
            'forward' => array(
                'module' => 'MelisCms',
                'controller' => 'Page',
                'action' => 'render-page-modal',
            ),
            'interface' => array(
                'meliscms_find_page_tree' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_find_page_tree',
                        'name' => 'tr_meliscms_menu_sitetree_Name',
                        'melisKey' => 'meliscms_find_page_tree',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'Page',
                        'action' => 'render-page-tree-modal',
                    ),
                ),
                'meliscms_page_tree_id_selector' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_page_tree_id_selector',
                        'name' => 'tr_meliscms_menu_sitetree_Name',
                        'melisKey' => 'meliscms_page_tree_id_selector',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'Page',
                        'action' => 'render-page-tree-id-selector-modal',
                    ),
                ),
                'meliscms_input_page_tree' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_input_page_tree',
                        'name' => 'tr_meliscms_menu_sitetree_Name',
                        'melisKey' => 'meliscms_input_page_tree',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'Page',
                        'action' => 'render-input-tree-modal',
                    ),
                ),
            ),
        ),
        'meliscms_plugin_modal' => array(
            'conf' => array(
                'id' => 'id_meliscms_plugin_modal',
                'name' => 'tr_meliscms_plugin_modal',
                'melisKey' => 'meliscms_plugin_modal',
                'rightsDisplay' => 'none',
            ),
            'forward' => array(
                'module' => 'MelisCms',
                'controller' => 'FrontPluginsModal',
                'action' => 'render-plugins-modal',
            )
        ),
        'meliscms_tools_tree_content_modal' => array(
            'conf' => array(
                'id' => 'id_meliscms_tools_tree_content_modal',
                'name' => 'tr_meliscms_tools_tree_content_modal',
                'melisKey' => 'meliscms_tools_tree_content_modal',
                'rightsDisplay' => 'none',
            ),
            'forward' => array(
                'module' => 'MelisCms',
                'controller' => 'TreeSites',
                'action' => 'render-tree-sites-modal-container',
            ),
            'interface' => array(
                'meliscms_tools_tree_modal_form_handler' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_tools_tree_modal_form_handler',
                        'name' => 'tr_meliscms_menu_dupe',
                        'melisKey' => 'meliscms_tools_tree_modal_form_handler',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'TreeSites',
                        'action' => 'render-tree-sites-modal-form-handler',
                    ),
                ),
            ),
        ),
    )
);
