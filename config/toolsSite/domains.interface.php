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
                                        'meliscms_tool_sites_edit_site_tabs_domains' => array(
                                            'conf' => array(
                                                'type' => 'melistoolsitesdomains/interface/meliscms_tool_sites_domains'
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
        'melistoolsitesdomains' => array(
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
                'meliscms_tool_sites_domains' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_tool_sites_domains',
                        'melisKey' => 'meliscms_tool_sites_domains',
                        'name' => 'Domains',
                        'icon' => 'google_maps',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'SitesDomains',
                        'action' => 'render-tool-sites-domains',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                    'interface' => array(
                        'meliscms_tool_sites_domains_content' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_sites_domains_content',
                                'melisKey' => 'meliscms_tool_sites_domains_content',
                                'name' => 'Domain Content',
                                'rightsDisplay' => 'true',
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'SitesDomains',
                                'action' => 'render-tool-sites-domains-content',
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