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
use Zend\Mvc\MvcEvent;
use Zend\Session\Container;

use MelisCore\Listener\MelisCoreGeneralListener;

class MelisCmsDeletePlatformListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{
	
    public function attach(EventManagerInterface $events)
    {
        $sharedEvents      = $events->getSharedManager();
        
        $callBackHandler = $sharedEvents->attach(
        	'MelisCore',
        	'meliscore_platform_delete_end',
        	function($e){
        		
        		$sm = $e->getTarget()->getServiceLocator();
        		$params = $e->getParams();
        		
        		$platformIdTable = $sm->get('MelisEngineTablePlatformIds');
        		
        		$success = (int) $params['success'];
        		$id      = (int) $params['id'];
        		
        		if($success == 1) {
        		    $platformIdTable->deleteById($id);
        		}
        	},
        100);
        
        $this->listeners[] = $callBackHandler;
    }
}