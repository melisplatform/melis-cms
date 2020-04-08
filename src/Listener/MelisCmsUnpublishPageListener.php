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

use MelisCore\Listener\MelisCoreGeneralListener;

class MelisCmsUnpublishPageListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{
	
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $sharedEvents      = $events->getSharedManager();
        
        $callBackHandler = $sharedEvents->attach(
        	'MelisCms',
        	'meliscms_page_unpublish_start', 
        	function($event){

                $sm = $event->getTarget()->getEvent()->getApplication()->getServiceManager();
        		$melisCoreDispatchService = $sm->get('MelisCoreDispatch');
        		$melisCmsRights = $sm->get('MelisCmsRights');
        		
        		list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
                    $e,
                    'meliscms',
                    'action-page-tmp',
                    'MelisCms\Controller\Page',
                    ['action' => 'pageActionsRightCheck', 'actionwanted' => 'unpublish']
        		);
        		if (!$success)
        			return;
        		
        		// Move saved page to published page
        		list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
                    $e,
                    'meliscms',
                    'action-page-tmp',
                    'MelisCms\Controller\Page',
                    array_merge(['action' => 'unpublishPublishedPage'])
        		);
        		if (!$success)
        			return;
        	},
        100);
        
        $this->listeners[] = $callBackHandler;
    }
}