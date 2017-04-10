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

class MelisCmsSavePageListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{
	
    public function attach(EventManagerInterface $events)
    {
        $sharedEvents      = $events->getSharedManager();
        
        $callBackHandler = $sharedEvents->attach(
        	'MelisCms',
        	array(
        		'meliscms_page_save_start',
        		'meliscms_page_publish_start',
        	),
        	function($e){

        		$sm = $e->getTarget()->getServiceLocator();
        		$melisCoreDispatchService = $sm->get('MelisCoreDispatch');
        		
        		list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
        				$e,
        				'meliscms',
        				'action-page-tmp',
        				'MelisCms\Controller\Page',
        				array('action' => 'pageActionsRightCheck',
        					  'actionwanted' => 'save')
        		);
        		if (!$success)
        			return;
        		
        		
        		// Create page entry / check existence of idPage in PageTree
        		list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
        				$e,
        				'meliscms',
        				'action-page-tmp',
        				'MelisCms\Controller\PageProperties',
        				array('action' => 'savePageTree')
        		); 
        		if (!$success)
        			return;

        		$idPage = $datas['idPage'];
        		$isNew = $datas['isNew'];
        				
        				
        		// Save properties tab
        		list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
        				$e,
        				'meliscms',
        				'action-page-tmp',
        				'MelisCms\Controller\PageProperties',
        				array_merge(array('action' => 'saveProperties'), $datas)
        		);
        		if (!$success)
        			return;
        				

        		// Save properties tab
        		list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
        				$e,
        				'meliscms',
        				'action-page-tmp',
        				'MelisCms\Controller\PageEdition',
        				array_merge(array('action' => 'saveEdition'), $datas)
        		);
        		if (!$success)
        			return;
        			
        		// Save seo tab
        		if (!$isNew)
        		{
	        		list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
	        				$e,
	        				'meliscms',
	        				'action-page-tmp',
	        				'MelisCms\Controller\PageSeo',
	        				array_merge(array('action' => 'saveSeo'), $datas)
	        		);
	        		if (!$success)
	        			return;
        		}
        	},
        100);
        
        $this->listeners[] = $callBackHandler;
    }
}