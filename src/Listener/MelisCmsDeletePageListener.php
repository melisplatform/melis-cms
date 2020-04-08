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
 * This listener will activate when a page is deleted
 * 
 */
class MelisCmsDeletePageListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $sharedEvents      = $events->getSharedManager();
        
        $callBackHandler = $sharedEvents->attach(
        	'MelisCms',
        	'meliscms_page_delete_start', 
        	function($event){

                $sm = $event->getTarget()->getEvent()->getApplication()->getServiceManager();

        		$melisCoreDispatchService = $sm->get('MelisCoreDispatch');
     
        		// Check rights to delete
        		list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
                    $e,
                    'meliscms',
                    'action-page-tmp',
                    'MelisCms\Controller\Page',
                    ['action' => 'pageActionsRightCheck', 'actionwanted' => 'delete']
        		);
        		if (!$success)
        			return;
        		
    			// Re-assign page language initial
    			list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
    			    $e,
    			    'meliscms',
    			    'action-page-tmp',
    			    'MelisCms\Controller\Pagelanguages',
    			    array_merge(['action' => 'setInitialPageLanguage'])
                );
    			if (!$success)
    			    return;
        		
        		// Delete the page
        		list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
                    $e,
                    'meliscms',
                    'action-page-tmp',
                    'MelisCms\Controller\Page',
                    array_merge(['action' => 'deletePageTree'])
	    		);
	    		if (!$success)
	    			return; 
	    			
	    		// Delete the SEO datas linked to the page
	    		list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
                    $e,
                    'meliscms',
                    'action-page-tmp',
                    'MelisCms\Controller\PageSeo',
                    array_merge(['action' => 'deletePageSeo'])
	    		);
	    		if (!$success)
	    			return;
        	},
        80);
        
        $this->listeners[] = $callBackHandler;
    }
}