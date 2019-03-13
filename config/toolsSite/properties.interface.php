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
                                        'meliscms_tool_sites_edit_site_tabs_properties' => array(
                                            'conf' => array(
                                                'type' => 'melistoolsitesproperties/interface/meliscms_tool_sites_properties'
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
                        'name' => 'Properties',
                        'icon' => 'cogwheel',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'Sites',
                        'action' => 'render-tool-sites-properties',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                    'interface' => array(
                        'meliscms_tool_sites_properties_content' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_sites_properties_content',
                                'melisKey' => 'meliscms_tool_sites_properties_content',
                                'rightsDisplay' => 'true',
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Sites',
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