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
                                        'meliscms_tool_sites_edit_site_tabs_site_translations' => array(
                                            'conf' => array(
                                                'type' => 'melistoolsitessitetranslations/interface/meliscms_tool_sites_site_translations'
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
        'melistoolsitessitetranslations' => array(
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
                'meliscms_tool_sites_site_translations' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_tool_sites_site_translations',
                        'melisKey' => 'meliscms_tool_sites_site_translations',
                        'name' => 'Site Translations',
                        'icon' => 'font',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'SitesTranslation',
                        'action' => 'render-tool-sites-site-translations',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                    'interface' => array(

                    ),
                ),

                'meliscms_tool_sites_site_translations_modal_edit' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_tool_sites_site_translations_modal_edit',
                        'melisKey' => 'meliscms_tool_sites_site_translations_modal_edit',
                        'name' => 'tr_melis_site_translation_edit_translation',
                        'icon' => 'fa fa-pencil',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'SitesTranslation',
                        'action' => 'render-tool-sites-site-translation-modal-edit',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                ),
            ),
        ),
    ),
);