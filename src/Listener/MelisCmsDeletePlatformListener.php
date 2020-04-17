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
use Laminas\Mvc\MvcEvent;
use Laminas\Session\Container;

use MelisCore\Listener\MelisGeneralListener;

class MelisCmsDeletePlatformListener extends MelisGeneralListener implements ListenerAggregateInterface
{
	
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->attachEventListener(
            $events,
        	'MelisCore',
        	'meliscore_platform_delete_end',
        	function($event){

                $sm = $event->getTarget()->getEvent()->getApplication()->getServiceManager();
        		$params = $event->getParams();
        		
        		$platformIdTable = $sm->get('MelisEngineTablePlatformIds');
        		
        		$success = (int) $params['success'];
        		$id      = (int) $params['id'];
        		
        		if($success == 1)
        		    $platformIdTable->deleteById($id);
        	},
        100
        );
    }
}