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

class MelisCmsToolUserUpdateUserListener extends MelisGeneralListener implements ListenerAggregateInterface
{
	 
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->attachEventListener(
            $events,
        	'MelisCore',
        	'meliscore_tooluser_save_start', 
        	function($event){

                $sm = $event->getTarget()->getEvent()->getApplication()->getServiceManager();
        		$container = new Container('meliscore');
        		
        		// Add MelisCMS rights management
    			$request = $sm->get('request');
    			$postUser = $request->getPost();
    			$userId = null;
    			if (!empty($postUser['usr_id']))
    				$userId = $postUser['usr_id'];
    				
    			if (empty($container['action-tool-user-setrights-tmp']))
    				$container['action-tool-user-setrights-tmp'] = [];

    			$melisCmsRights = $sm->get('MelisCmsRights');
    			$melisCmsRights = $melisCmsRights->createXmlRightsValues($userId, $postUser);
    			$container['action-tool-user-setrights-tmp'] = array_merge($container['action-tool-user-setrights-tmp'], $melisCmsRights);
        	},
        110
        );
    }
}