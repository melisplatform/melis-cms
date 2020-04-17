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
use MelisCore\Listener\MelisGeneralListener;

class MelisCmsPlatformIdListener extends MelisGeneralListener implements ListenerAggregateInterface
{
	
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->attachEventListener(
            $events,
        	'MelisCore',
        	'meliscore_platform_save_end', 
        	function($event){

                $sm = $event->getTarget()->getEvent()->getApplication()->getServiceManager();
        		$params = $event->getParams();
        		
        		$platformIdTable = $sm->get('MelisEngineTablePlatformIds');
        		
        		$success = (int) $params['success'];
        		$id      = (int) $params['id'];

        		if($success == 1 && $params['typeCode'] == 'CORE_PLATFORM_ADD') {
        		    
        		    $data = $platformIdTable->getLastPlatformRange()->current();
        		    
        		    $pageMaxEnd = !empty($data->pids_page_id_end_max)? $data->pids_page_id_end_max : 0;
        		    $tplMaxEnd = !empty($data->pids_tpl_id_end_max)? $data->pids_tpl_id_end_max : 0;
        		    
        		    // computes the starting range based on the max page ids
        		    $pageIdRangeStart = ceil($pageMaxEnd / 1000) * 1000;
        		    $pageIdRangeEnd = ceil(($pageIdRangeStart + 1) / 1000) * 1000;
        		    
        		    //computes the starting range based onthe max tpl ids
        		    $tplIdRangeStart = ceil($tplMaxEnd / 1000) * 1000;
        		    $tplIdRangeEnd = ceil(($tplIdRangeStart + 1) / 1000) * 1000;
        		    
        		    $platformIdData = [
        		        'pids_id' => $id,
        		        'pids_page_id_start' => $pageIdRangeStart +1,
        		        'pids_page_id_current' => $pageIdRangeStart +1,
        		        'pids_page_id_end' => $pageIdRangeEnd,
        		        'pids_tpl_id_start' => $tplIdRangeStart +1,
        		        'pids_tpl_id_current' => $tplIdRangeStart +1,
        		        'pids_tpl_id_end' => $tplIdRangeEnd,
        		    ];
        		    
        		    $platformIdTable->save($platformIdData);
        		}
        	},
        100
        );
    }
}