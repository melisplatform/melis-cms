<?php 

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2017 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Listener;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container;
use MelisCore\Listener\MelisGeneralListener;

/**
 * This listener will call the plugin to get a formatting of the values
 */
class MelisCmsPluginSaveEditionSessionListener extends MelisGeneralListener implements ListenerAggregateInterface
{
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->attachEventListener(
            $events,
        	'MelisCms',
        	'meliscms_page_savesession_plugin_start', 
        	function($event){

        		$sm = $event->getTarget()->getEvent()->getApplication()->getServiceManager();

        		$params = $event->getParams();
        		
        		$postValues = $params['postValues'];
        		
        		$idPage     = $params['idPage'];
        		$plugin     = isset($postValues['melisPluginName']) ? $postValues['melisPluginName'] : null;
        		$tag        = isset($postValues['melisPluginTag'])  ? $postValues['melisPluginTag']  : null;
        		$id         = isset($postValues['melisPluginId'])   ? $postValues['melisPluginId']   : null;
                $fromResize = isset($postValues['resize']) ? $postValues['resize'] : null;

                /**
                 * check if plugin is came from the mini template
                 * to get its original plugin name
                 */
                if (strpos($plugin, 'MiniTemplatePlugin') !== false) {
                    //explode to get the original plugin name
                    $tplPlugin = explode('_', $plugin);
                    //set the original plugin name
                    $plugin = $tplPlugin[0];
                }

        		if (empty($plugin) || empty($idPage) || empty($id))
        		    return;
        		    
        		$xml = '';
        		try  {
        		    $melisPlugin = $sm->get('ControllerPluginManager')->get($plugin);
        		    $xml = $melisPlugin->savePluginConfigToXml($postValues);

        		} catch(\Exception $e) {
        		    return;
        		}

    		    // Save in session
    		    if ($xml != '') {
    		        // if request came from resizing plugin
                    // then we do not override session so that
                    // data in renderModalPlguin will not disappear
    		        if (!$fromResize) {
                        $container = new Container('meliscms');
                        $container['content-pages'][$idPage][$tag][$id] = $xml;
                    }
    		    }
        	},
            80
        );
    }
}