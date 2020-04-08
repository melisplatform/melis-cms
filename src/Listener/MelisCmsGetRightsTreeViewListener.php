<?php 

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Listener;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Session\Container;

use MelisCore\Listener\MelisCoreGeneralListener;

/**
 * This listener adds the cms part for the rights in the treeview when editing user's rights.
 */
class MelisCmsGetRightsTreeViewListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{
	
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $sharedEvents      = $events->getSharedManager();
        
        $callBackHandler = $sharedEvents->attach(
        	'MelisCore',
        	'meliscore_tooluser_getrightstreeview_start', 
        	function($e){

                $sm = $e->getTarget()->getEvent()->getApplication()->getServiceManager();
        		$container = new Container('meliscore');
        		
        		// Add MelisCMS rights management
    			$userId = $sm->get('request')->getQuery()->get('userId');
    					
    			if (empty($container['action-tool-user-getrights-tmp']))
    				$container['action-tool-user-getrights-tmp'] = [];

    			$melisCmsRights = $sm->get('MelisCmsRights');
    			$rightsCms = $melisCmsRights->getRightsValues($userId);
    			
    					 
    			// Merge the CMS rights with other ones (from Core or other modules)
    			$container['action-tool-user-getrights-tmp'] = array_merge($container['action-tool-user-getrights-tmp'], $rightsCms);	 	
        	},
        100);
        
        $this->listeners[] = $callBackHandler;
    }
}