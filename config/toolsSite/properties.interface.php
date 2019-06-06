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
                                            'interface' => array(
                                                'meliscms_tool_sites_edit_site_tabs_properties' => array(
                                                    'conf' => array(
                                                        'type' => 'melistoolsitesproperties/interface/meliscms_tool_sites_properties'
                                                    )
                                                ),
                                            )
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
            'interface' => array(
                'meliscms_toolstree' => array(
                    'interface' => array(
                        'meliscms_tool_sites_edit_site' => array(
                            'interface' => array(
                                'meliscms_tool_sites_edit_site_tabs' => array(
                                    'interface' => array(
                                        'meliscms_tool_sites_edit_site_tabs_properties' => array(
                                            'conf' => array(
                                                'type' => 'melistoolsitesproperties/interface/meliscms_tool_sites_properties'
                                            )
                                        ),
                                    ),
                                ),
                            ),
                        ),
//                        'meliscms_tool_sites' => array(
//                            'interface' => array(
//                                'meliscms_tool_sites_edit_site_tabs_properties' => array(
//                                    'conf' => array(
//                                        'type' => 'melistoolsitesproperties/interface/meliscms_tool_sites_properties'
//                                    )
//                                ),
//                            )
//                        ),
                    ),
                ),
            ),
        ),
        'melistoolsitesproperties' => array(
            'conf' => array(
                'id' => '',
                'name' => 'Properties',
                'rightsDisplay' => 'none',
            ),
            'ressources' => array(
                'js' => array(

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
                'meliscms_tool_sites_properties' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_tool_sites_properties',
                        'melisKey' => 'meliscms_tool_sites_properties',
                        'name' => 'tr_melis_cms_sites_tool_content_edit_properties_tab',
                        'icon' => 'tag',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'SitesProperties',
                        'action' => 'render-tool-sites-properties',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                    'interface' => array(
                        'meliscms_tool_sites_properties_content' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_sites_properties_content',
                                'melisKey' => 'meliscms_tool_sites_properties_content',
                                'name' => 'tr_melis_cms_sites_tool_content_edit_properties_tab_content',
                                'rightsDisplay' => 'true',
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'SitesProperties',
                                'action' => 'render-tool-sites-properties-content',
                                'jscallback' => '',
                                'jsdatas' => array()
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
);