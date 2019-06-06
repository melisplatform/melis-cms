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
                        'name' => 'tr_melis_cms_sites_tool_content_edit_site_config_tab',
                        'icon' => 'settings',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'SitesConfig',
                        'action' => 'render-tool-sites-site-config',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                    'interface' => array(
                        'meliscms_tool_sites_site_config_content' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_sites_site_config_content',
                                'melisKey' => 'meliscms_tool_sites_site_config_content',
                                'name' => 'tr_melis_cms_sites_tool_content_edit_site_config_tab_content',
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'SitesConfig',
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