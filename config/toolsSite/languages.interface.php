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
                                        'meliscms_tool_sites_edit_site_tabs_languages' => array(
                                            'conf' => array(
                                                'type' => 'melistoolsiteslanguages/interface/meliscms_tool_sites_languages'
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
        'melistoolsiteslanguages' => array(
            'conf' => array(
                'id' => '',
                'name' => 'Properties',
                'rightsDisplay' => 'none',
            ),
            'ressources' => array(
                'js' => [],
                'css' => [],
                /**
                 * the "build" configuration compiles all assets into one file to make
                 * lesser requests
                 */
                'build' => [
                    // lists of assets that will be loaded in the layout
                    'css' => [],
                    'js' => []
                ]
            ),
            'datas' => array(

            ),
            'interface' => array(
                'meliscms_tool_sites_languages' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_tool_sites_languages',
                        'melisKey' => 'meliscms_tool_sites_languages',
                        'name' => 'tr_melis_cms_sites_tool_content_edit_languages_tab',
                        'icon' => 'font',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'SitesLanguages',
                        'action' => 'render-tool-sites-languages',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                    'interface' => array(
                        'meliscms_tool_sites_languages_content' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_sites_languages_content',
                                'melisKey' => 'meliscms_tool_sites_languages_content',
                                'name' => 'tr_melis_cms_sites_tool_content_edit_languages_tab_content',
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'SitesLanguages',
                                'action' => 'render-tool-sites-languages-content',
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