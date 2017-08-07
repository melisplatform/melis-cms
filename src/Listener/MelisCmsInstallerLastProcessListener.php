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
                $installHelper   = $sm->get('InstallerHelper');

                $environmentName   = $environment['default_environment']['wildcard']['sdom_env'];
                $environmentDomain = $environment['default_environment']['data']['sdom_domain'];
                
                // Getting current Platform
                $tablePlatform = $sm->get('MelisCoreTablePlatform');
                $platform = $tablePlatform->getEntryByField('plf_name', $environmentName)->current();
                
                $pageId = 1;
                
                // Platform IDs
                $tablePlatformIds = $sm->get('MelisEngineTablePlatformIds');
                $tablePlatformIds->save(array(
                    'pids_id' => $platform->plf_id,
                    'pids_page_id_start' => 1,
                    'pids_page_id_current' => 1,
                    'pids_page_id_end' => 1000,
                    'pids_tpl_id_start' => 1,
                    'pids_tpl_id_current' => 1,
                    'pids_tpl_id_end' => 1000
                ));
                
                $siteId = 1;
                
                if ($params['cms_data']['weboption'] == 'NewSite'){
                    
                    /**
                     * Generating Site, pages, template etc.
                     * for NewSite option using the MelisCms Service
                     */
                    $cmsSiteSrv = $sm->get('MelisCmsSiteService');
                    
                    $dataSite = array(
                        'site_name' => $params['cms_data']['web_form']['website_name']
                    );
                    
                    $dataDomain = array(
                        'sdom_env' => $environmentName,
                        'sdom_scheme' => 'http',
                        'sdom_domain' => $environmentDomain
                    );
                    
                    $dataSiteLang = $params['cms_data']['web_lang'];
                    
                    $genSiteModule = true;
                    
                    $siteModule = getenv('MELIS_MODULE');
                    
                    $saveSiteResult = $cmsSiteSrv->saveSite($dataSite, $dataDomain, array(), $dataSiteLang, null, $genSiteModule, $siteModule);
                    
                    if ($saveSiteResult['success']){
                        $siteId = $saveSiteResult['site_id'];
                    }
                }
                
                if(isset($environment['new']) && !empty($environment['new'])) {
                    foreach($environment['new'] as $sitePlatform => $siteDomains) {
                        foreach($siteDomains as $siteDomain) {
                            
                            unset($siteDomain['app_interface_conf']);
                            
                            $siteDomain['sdom_site_id'] = $siteId;
                            $tableSiteDomain->save($siteDomain);
                        }
                    }
                }
            },
        -1500);

        $this->listeners[] = $callBackHandler;
    }
}