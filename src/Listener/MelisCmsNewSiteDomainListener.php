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
class MelisCmsNewSiteDomainListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{
	
    public function attach(EventManagerInterface $events)
    {
        $sharedEvents      = $events->getSharedManager();
        
        $callBackHandler = $sharedEvents->attach(
        	'MelisInstaller',
        	array(
                'melis_install_new_platform_start'
        	),
        	function($e){

        		$sm = $e->getTarget()->getServiceLocator();
        		$params = $e->getParams();
        		
        		$updatePlatformDomain = $params['platformDomain'];
        		$siteDomains = $params['siteDomain'];
        		$scheme = $sm->get('Application')->getMvcEvent()->getRequest()->getUri()->getScheme();
        		$defaultSiteID =  1;
        		$defaultEnv = getenv('MELIS_PLATFORM');

        		// update first the platform domain
        		$container = new Container('melisinstaller');
        		
    		    $container->environments =  array('default_environment' => array(
    		            'data' =>array('sdom_domain' => $updatePlatformDomain),
    		            'wildcard' => array('sdom_env' => $defaultEnv),
                        'app_interface_conf' => [

                        ]
		        )); 

        		foreach($siteDomains as $site) {
        		    
        		    //$siteData = $tableSiteDomains->getEntryByField('sdom_env', $site['environment']);
        		    if(empty($container['environments']['new'][$site['environment']])) {
        		        // add new site domain
        		        $container['environments']['new'][$site['environment']][] = array(
        		           'sdom_site_id' => $defaultSiteID,
        		            'sdom_env' => $site['environment'],
        		            'sdom_scheme' => $scheme,
        		            'sdom_domain' => $site['domain'],
                            'app_interface_conf' => [
                                'error_reporting' => $site['error_reporting'],
                                'display_error'   => $site['display_error'] == 'on' ? 1 : 0
                            ]
        		        );
        		    }
        		    else {
        		        // update site domain
        		        $container['environments']['new'][$site['environment']][] = array(
        		           'sdom_site_id' => $defaultSiteID,
        		            'sdom_env' => $site['environment'],
        		            'sdom_scheme' => $scheme,
        		            'sdom_domain' => $site['domain'],
                            'app_interface_conf' => [
                                'error_reporting' => $site['error_reporting'],
                                'display_error'   => $site['display_error'] == 'on' ? 1 : 0
                            ]
        		        );
        		    }
        		}
        		

        	},
        100);
        
        $this->listeners[] = $callBackHandler;
    }
}