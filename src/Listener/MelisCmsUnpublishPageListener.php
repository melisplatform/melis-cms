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

class MelisCmsUnpublishPageListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{
	
    public function attach(EventManagerInterface $events)
    {
        $sharedEvents      = $events->getSharedManager();
        
        $callBackHandler = $sharedEvents->attach(
        	'MelisCms',
        	'meliscms_page_unpublish_start', 
        	function($e){
        		
        		$sm = $e->getTarget()->getServiceLocator();
        		$melisCoreDispatchService = $sm->get('MelisCoreDispatch');
        		$melisCmsRights = $sm->get('MelisCmsRights');
        		
        		list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
        				$e,
        				'meliscms',
        				'action-page-tmp',
        				'MelisCms\Controller\Page',
        				array('action' => 'pageActionsRightCheck',
        						'actionwanted' => 'unpublish')
        		);
        		if (!$success)
        			return;
        		
        		// Move saved page to published page
        		list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
        				$e,
        				'meliscms',
        				'action-page-tmp',
        				'MelisCms\Controller\Page',
        				array_merge(array('action' => 'unpublishPublishedPage'))
        		);
        		if (!$success)
        			return;
        	},
        100);
        
        $this->listeners[] = $callBackHandler;
    }
}