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
use MelisCore\Listener\MelisGeneralListener;
use Laminas\Session\Container;

class MelisCmsDeleteSiteDomainListener extends MelisGeneralListener implements ListenerAggregateInterface
{
	
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->attachEventListener(
            $events,
        	'MelisInstaller',
            'melis_install_delete_environment_start',
        	function($event){
                $success = 1;
                $sm = $event->getTarget()->getEvent()->getApplication()->getServiceManager();
        		$params = $event->getParams();
        		$container = new Container('melisinstaller');
        		
         		$environments = $container['environments']['new'][$params['env']];
         		
         		for($x = 0; $x < count($environments); $x++) {
         		    if( $environments[$x]['sdom_env'] == $params['env'] &&
         		        $environments[$x]['sdom_domain'] == $params['url']) {
         		        array_splice($container['environments']['new'][$params['env']], $x, 1);
     		        }
         		}

        		return ['success' => (int) $success];
        	},
        100
        );
    }
}