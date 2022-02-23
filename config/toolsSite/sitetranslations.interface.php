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
            ),
            'datas' => array(

            ),
            'interface' => array(
                'meliscms_tool_sites_site_translations' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_tool_sites_site_translations',
                        'melisKey' => 'meliscms_tool_sites_site_translations',
                        'name' => 'tr_melis_cms_sites_tool_content_edit_site_translations_tab',
                        'icon' => 'language',
                    ),
                    'forward' => array(
                        'module' => 'MelisCms',
                        'controller' => 'SitesTranslation',
                        'action' => 'render-tool-sites-site-translations',
                        'jscallback' => '',
                        'jsdatas' => array()
                    ),
                    'interface' => array(
                        'meliscms_tool_sites_site_translations_content' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_sites_site_translations_content',
                                'melisKey' => 'meliscms_tool_sites_site_translations_content',
                                'name' => 'tr_melis_cms_sites_tool_content_edit_site_translations_tab_content',
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'SitesTranslation',
                                'action' => 'render-tool-sites-site-translations-content',
                            ),
                        ),
                    ),
                ),

                'meliscms_tool_sites_site_translations_modal_edit' => array(
                    'conf' => array(
                        'id' => 'id_meliscms_tool_sites_site_translations_modal_edit',
                        'melisKey' => 'meliscms_tool_sites_site_translations_modal_edit',
                        'name' => 'Site Translation Content',
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