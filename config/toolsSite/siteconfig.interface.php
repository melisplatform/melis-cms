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
                                        'meliscms_tool_sites_edit_site_tabs_site_config' => array(
                                            'conf' => array(
                                                'type' => 'melistoolsitessiteconfig/interface/meliscms_tool_sites_site_config'
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
        'melistoolsitessiteconfig' => array(
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
                'meliscms_tool_sites_site_config' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_tool_sites_site_config',
                        'melisKey' => 'meliscms_tool_sites_site_config',
                        'name' => 'Site Config',
                        'icon' => 'settings',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'Sites',
                        'action' => 'render-tool-sites-site-config',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                    'interface' => array(
                        'meliscms_tool_sites_site_config_content' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_sites_site_config_content',
                                'melisKey' => 'meliscms_tool_sites_site_config_content',
                                'name' => '',
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Sites',
                                'action' => 'render-tool-sites-site-config-content',
                            ),
                            'interface' => array(

                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
);