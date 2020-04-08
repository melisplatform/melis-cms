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

use MelisCore\Listener\MelisCoreGeneralListener;

/**
 * This listener is executed when page publication is asked.
 *
 */
class MelisCmsPublishPageListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{
	
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $sharedEvents      = $events->getSharedManager();
        
        $callBackHandler = $sharedEvents->attach(
        	'MelisCms',
        	'meliscms_page_publish_start', 
        	function($event){

                $sm = $event->getTarget()->getEvent()->getApplication()->getServiceManager();
        		$melisCoreDispatchService = $sm->get('MelisCoreDispatch');
        		
        		$melisCmsRights = $sm->get('MelisCmsRights');
        		list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
                    $e,
                    'meliscms',
                    'action-page-tmp',
                    'MelisCms\Controller\Page',
                    ['action' => 'pageActionsRightCheck', 'actionwanted' => 'publish']
        		);
        		if (!$success)
        			return;
        		
        		// Move saved page to published page
	    		list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
                    $e,
                    'meliscms',
                    'action-page-tmp',
                    'MelisCms\Controller\Page',
                    array_merge(['action' => 'publishSavedPage'], $datas)
	    		);
	    		if (!$success)
	    			return;
	    		
        	},
        80);
        
        $this->listeners[] = $callBackHandler;
    }
}