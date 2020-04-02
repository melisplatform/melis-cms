<?php 

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Listener;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use MelisCore\Listener\MelisCoreGeneralListener;
use Laminas\Session\Container;
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
        		
        		$currentPlatform = $params['currentPlatform'];
        		$siteDomains = $params['siteDomain'];
        		$scheme = $sm->get('Application')->getMvcEvent()->getRequest()->getUri()->getScheme();
        		$defaultSiteID =  1;
        		$defaultEnv = getenv('MELIS_PLATFORM');

        		// update first the platform domain
        		$container    = new Container('melisinstaller');
                $displayError = $currentPlatform['error_reporting'] != '0' ? 1 : 0;
    		    $container->environments =  array('default_environment' => array(
    		            'data' =>array('sdom_domain' => $currentPlatform['platform_domain']),
    		            'wildcard' => array('sdom_env' => $defaultEnv),
                        'app_interface_conf' => [
                            'send_email'      => $currentPlatform['send_email'],
                            'error_reporting' => $currentPlatform['error_reporting'],
                            'display_error'   => $displayError
                        ]
		        )); 

        		foreach($siteDomains as $site) {
                    $displayError = $site['error_reporting'] != '0' ? 1 : 0;
        		    if(empty($container['environments']['new'][$site['environment']])) {
        		        // add new site domain
        		        $container['environments']['new'][$site['environment']][] = array(
        		           'sdom_site_id' => $defaultSiteID,
        		            'sdom_env' => $site['environment'],
        		            'sdom_scheme' => $scheme,
        		            'sdom_domain' => $site['domain'],
                            'app_interface_conf' => [
                                'send_email'      => $site['send_email']  == 'on' ? 1 : 0,
                                'error_reporting' => $site['error_reporting'],
                                'display_error'   => $displayError
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
                                'send_email'      => $site['send_email']  == 'on' ? 1 : 0,
                                'error_reporting' => $site['error_reporting'],
                                'display_error'   => $displayError
                            ]
        		        );
        		    }
        		}
        		

        	},
        100);
        
        $this->listeners[] = $callBackHandler;
    }
}