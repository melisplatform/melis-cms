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
class MelisCmsDeleteSiteDomainListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{
	
    public function attach(EventManagerInterface $events)
    {
        $sharedEvents      = $events->getSharedManager();
        
        $callBackHandler = $sharedEvents->attach(
        	'MelisInstaller',
        	array(
                'melis_install_delete_environment_start'
        	),
        	function($e){
                $success = 1;
        		$sm = $e->getTarget()->getServiceLocator();
        		$params = $e->getParams();
        		$container = new Container('melisinstaller');
        		
         		$environments = $container['environments']['new'][$params['env']];
         		
         		for($x = 0; $x < count($environments); $x++) {
         		    if( $environments[$x]['sdom_env'] == $params['env'] &&
         		        $environments[$x]['sdom_domain'] == $params['url']) {
         		        array_splice($container['environments']['new'][$params['env']], $x, 1);
     		        }
         		}
         		
                
        		return array('success' => (int) $success);
        	},
        100);
        
        $this->listeners[] = $callBackHandler;
    }
}