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
use MelisCore\Listener\MelisCoreGeneralListener;

class MelisCmsPlatformIdListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{
	
    public function attach(EventManagerInterface $events)
    {
        $sharedEvents      = $events->getSharedManager();
        
        $callBackHandler = $sharedEvents->attach(
        	'MelisCore',
        	'meliscore_platform_new_end', 
        	function($e){
        		
        		$sm = $e->getTarget()->getServiceLocator();
        		$params = $e->getParams();
        		
        		$platformIdTable = $sm->get('MelisEngineTablePlatformIds');
        		
        		$success = (int) $params['success'];
        		$id      = (int) $params['id'];
        		
        		if($success == 1) {
        		    $platformIdTable->save(array(
        		        'pids_id' => $id,
//         		        'pids_page_id_start' => 0,
//         		        'pids_page_id_current' => 0,
//         		        'pids_page_id_end' => 0,
//         		        'pids_tpl_id_start' => 0,
//         		        'pids_tpl_id_current' => 0,
//         		        'pids_tpl_id_end' => 0
        		    ));
        		}
        		

        	},
        100);
        
        $this->listeners[] = $callBackHandler;
    }
}