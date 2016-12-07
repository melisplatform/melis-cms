<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Listener;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use MelisCore\Listener\MelisCoreGeneralListener;
use Zend\Session\Container;
class MelisCmsInstallerLastProcessListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{

    public function attach(EventManagerInterface $events)
    {
        $sharedEvents      = $events->getSharedManager();

        $callBackHandler = $sharedEvents->attach(
            'MelisInstaller',
            array(
                'melis_install_last_process_start'
            ),
            function($e){

                $sm = $e->getTarget()->getServiceLocator();
                $params = $e->getParams();
                $container = new Container('melisinstaller');
                $environment = $params['environments'];
                $tableSiteDomain = $sm->get('MelisEngineTableSiteDomain');
                $tableSite       = $sm->get('MelisEngineTableSite');
                $installHelper   = $sm->get('InstallerHelper');

                $environmentName   = $environment['default_environment']['wildcard']['sdom_env'];
                $environmentDomain = $environment['default_environment']['data']['sdom_domain'];
                $siteName          = $params['cms_data']['website_module'];

                $tableSiteDomain->save(array(
                    'sdom_site_id' => 1,
                    'sdom_env' => $environmentName,
                    'sdom_scheme' => 'http',
                    'sdom_domain' => $environmentDomain
                ));

                $tableSite->save(array(
                    'site_name' => $siteName,
                    'site_main_page_id' => 1
                ));


                if(isset($environment['new']) && !empty($environment['new'])) {
                    foreach($environment['new'] as $sitePlatform => $siteDomains) {
                        foreach($siteDomains as $siteDomain) {
                            $tableSiteDomain->save($siteDomain);
                        }
                    }
                }

                // Create Template
                $tableTemplate = $sm->get('MelisEngineTableTemplate');
                $tableTemplate->save(array(
                    'tpl_id' => 1,
                    'tpl_site_id' => 1,
                    'tpl_name' => $siteName . ': Porto Test',
                    'tpl_type' => 'ZF2',
                    'tpl_zf2_website_folder' => $siteName,
                    'tpl_zf2_layout' => 'layout'.$siteName,
                    'tpl_zf2_controller' => 'Index',
                    'tpl_zf2_action' => 'home',
                    'tpl_creation_date' => date('Y-m-d H:i:s'),
                ));

                // Create Page Lang
                $selLang = $params['cms_data']['language'];
                $tablePageLang = $sm->get('MelisEngineTablePageLang');
                $tablePageLang->save(array(
                    'plang_page_id' => 1,
                    'plang_lang_id' => $selLang,
                    'plang_page_id_initial' => 1
                ));

                // Page Tree
                $tablePageTree = $sm->get('MelisEngineTablePageTree');
                $tablePageTree->save(array(
                    'tree_page_id' => 1,
                    'tree_father_page_id' => -1,
                    'tree_page_order' => 1
                ));

                // Platform IDs || @todo not sure yet if correct data
                $tablePlatformIds = $sm->get('MelisEngineTablePlatformIds');
                $tablePlatformIds->save(array(
                    'pids_page_id_start' => 1,
                    'pids_page_id_current' => 3,
                    'pids_page_id_end' => 1000,
                    'pids_tpl_id_start' => 1,
                    'pids_tpl_id_current' => 3,
                    'pids_tpl_id_end' => 1000
                ));

                $tablePageSaved = $sm->get('MelisEngineTablePageSaved');
                $tablePageSaved->save(array(
                    'page_id' => 1,
                    'page_type' => 'SITE',
                    'page_menu' => 'LINK',
                    'page_name' => $siteName,
                    'page_tpl_id' => 1,
                    'page_content' => '<?xml version="1.0" encoding="UTF-8"?><document type="MelisCMS" author="MelisTechnology" version="2.0"></document>',
                    'page_taxonomy' => '',
                    'page_creation_date' => date('Y-m-d H:i:s'),
                    'page_last_user_id' => 1
                ));


            },
            -1600);

        $this->listeners[] = $callBackHandler;
    }
}