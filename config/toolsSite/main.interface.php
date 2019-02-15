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
                                            'conf' => array(
                                                'type' => '/meliscms/interface/meliscms_toolstree/interface/meliscms_tool_sites',
                                            ),
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
            'ressources' => array(
                'js' => array(
                    '/MelisCms/js/tools/sites/sites.tool.js',
                ),
                'css' => array(

                ),

            ),
            'interface' => array(
                'meliscms_toolstree' => array(
                    'interface' => array(
                        'meliscms_tool_sites' => array(
                            'conf' => array(
                                'id' => 'id_meliscms_tool_sites',
                                'name' => 'tr_meliscms_tool_sites',
                                'melisKey' => 'meliscms_tool_sites',
                                'icon' => 'fa-book',
                                'follow_regular_rendering' => false,
                            ),
                            'forward' => array(
                                'module' => 'MelisCms',
                                'controller' => 'Sites',
                                'action' => 'render-tool-sites',
                                'jscallback' => '',
                                'jsdatas' => array(),
                            ),
                            'interface' => array(
                                'meliscms_tool_sites_header' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_sites_header',
                                        'name' => 'tr_meliscore_tool_gen_header',
                                        'melisKey' => 'meliscms_tool_sites_header',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Sites',
                                        'action' => 'render-tool-sites-header',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'meliscms_tool_sites_header_add' => array(
                                            'conf' => array(
                                                'id' => 'id_meliscms_tool_sites_header_add',
                                                'name' => 'tr_meliscore_tool_gen_new',
                                                'melisKey' => 'meliscms_tool_sites_header_add',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-header-add',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                    ),
                                ), // end header
                                'meliscms_tool_sites_contents' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_sites_contents',
                                        'name' => 'tr_meliscore_tool_gen_content',
                                        'melisKey' => 'meliscms_tool_sites_contents',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Sites',
                                        'action' => 'render-tool-sites-content',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                ), // end contents
                                'meliscms_tool_sites_modal_container' => array(
                                    'conf' => array(
                                        'id' => 'id_meliscms_tool_sites_modal_container',
                                        'name' => 'meliscms_tool_sites_modal_container',
                                        'melisKey' => 'meliscms_tool_sites_modal_container',
                                    ),
                                    'forward' => array(
                                        'module' => 'MelisCms',
                                        'controller' => 'Sites',
                                        'action' => 'render-tool-sites-modal-container',
                                        'jscallback' => '',
                                        'jsdatas' => array()
                                    ),
                                    'interface' => array(
                                        'meliscms_tool_sites_modal_add' => array(
                                            'conf' => array(
                                                'id'   => 'id_meliscms_tool_sites_modal_add_content',
                                                'name' => 'tr_meliscms_tool_sites_modal_add_content',
                                                'melisKey' => 'meliscms_tool_sites_modal_add_content',
                                            ),
                                            'forward' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-modal-add',
                                                'jscallback' => '',
                                                'jsdatas' => array()
                                            ),
                                        ),
                                        'meliscms_tool_sites_modal_edit' => array(
                                            'id' => 'id_meliscms_tool_sites_modal_edit',
                                            'class' => 'glyphicons pencil',
                                            'tab-header' => '',
                                            'tab-text' => 'tr_meliscms_tool_sites_update_sites',
                                            'content' => array(
                                                'module' => 'MelisCms',
                                                'controller' => 'Sites',
                                                'action' => 'render-tool-sites-modal-edit',
                                            ),

                                        ),
                                    ),
                                ), // end modals
                            )
                        ),
                    ),
                ),
            ),
        ),
    ),
);