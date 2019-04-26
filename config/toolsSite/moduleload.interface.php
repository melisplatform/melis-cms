<?php
return array(
    'plugins' => array(
        'meliscms' => array(
            'interface' => array(
                'meliscms_toolstree' => array(
                    'interface' => array(
                        'meliscms_tool_sites_edit_site' => array(
                            'interface' => array(
                                'meliscms_tool_sites_edit_site_tabs' => array(
                                    'interface' => array(
                                        'meliscms_tool_sites_edit_site_tabs_module_load' => array(
                                            'conf' => array(
                                                'type' => 'melistoolsitesmoduleload/interface/meliscms_tool_sites_module_load'
                                            )
                                        ),
                                    ),
                                ),
                            ),
                        ),
//                        'meliscms_tool_sites' => array(
//                            'interface' => array(
//                                'meliscms_tool_sites_edit_site_tabs_module_load' => array(
//                                    'conf' => array(
//                                        'type' => 'melistoolsitesmoduleload/interface/meliscms_tool_sites_module_load'
//                                    )
//                                ),
//                            ),
//                        ),
                    ),
                ),
            ),
        ),
        'melistoolsitesmoduleload' => array(
            'conf' => array(
                'id' => '',
                'name' => 'Properties',
                'rightsDisplay' => 'referenceonly',
            ),
            'ressources' => array(
                'js' => array(
                    '/MelisCms/js/tools/sites/sitesModuleLoad.tool.js',
                ),
                'css' => array(

                ),
                /**
                 * the "build" configuration compiles all assets into one file to make
                 * lesser requests
                 */
                'build' => [
                    // lists of assets that will be loaded in the layout
                    'css' => [


                    ],
                    'js' => [

                    ]
                ]
            ),
            'datas' => array(

            ),
            'interface' => array(
                'meliscms_tool_sites_module_load' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_tool_sites_module_load',
                        'melisKey' => 'meliscms_tool_sites_module_load',
                        'name' => 'tr_melis_cms_sites_tool_content_edit_module_loading_tab',
                        'icon' => 'list',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'SitesModuleLoader',
                        'action' => 'render-tool-sites-module-load',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                    'interface' => array(
                        'meliscms_tool_sites_module_load_content' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_sites_module_load_content',
                                'melisKey' => 'meliscms_tool_sites_module_load_content',
                                'name' => 'tr_melis_cms_sites_tool_content_edit_module_loading_tab_content',
                                'rightsDisplay' => 'true',
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'SitesModuleLoader',
                                'action' => 'render-tool-sites-module-load-content',
                                'jscallback' => 'moduleLoadJsCallback();',
                                'jsdatas' => array()
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
);