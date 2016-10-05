<?php

return array(

    'plugins' => array(
        'diagnostic' => array(
            'MelisCms' => array(
                
                // tests to execute
                'basicTest' => array(
                    'controller' => 'Diagnostic',
                    'action' => 'basicTest',
                    'payload' => array(
                        'label' => 'tr_melis_module_basic_action_test',
                        'module' => 'MelisCms'
                    )
                ),
        
                'fileCreationTest' => array(
                    'controller' => 'Diagnostic',
                    'action' => 'fileCreationTest',
                    'payload' => array(
                        'label' => 'tr_melis_module_rights_dir',
                        'path' => MELIS_MODULES_FOLDER.'MelisCms/public/',
                        'file' => 'test_file_creation.txt'
                    ),
                ),
        
                'testModuleTables' => array(
                    'controller' => 'Diagnostic',
                    'action' => 'testModuleTables',
                    'payload' => array(
                        'label' => 'tr_melis_module_db_test',
                        'tables' => array(
                            'melis_cms_lang', 
                            'melis_cms_page_lang',
                            'melis_cms_page_published',
                            'melis_cms_page_saved',
                            'melis_cms_platform_ids',
                            'melis_cms_page_tree',
                            'melis_cms_site',
                            'melis_cms_site_404',
                            'melis_cms_site_domain',
                        )
                    ),
                ),
            ),
        
        ),
    ),


);

