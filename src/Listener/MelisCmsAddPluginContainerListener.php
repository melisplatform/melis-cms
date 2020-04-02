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
use Laminas\Stdlib\ArrayUtils;
class MelisCmsAddPluginContainerListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{

    public function attach(EventManagerInterface $events)
    {
        $priority        = -11000;
        $sharedEvents    = $events->getSharedManager();
        $callBackHandler = $sharedEvents->attach(
            'MelisCms',
            array(
                'meliscms_page_savesession_plugin_start',
            ),
            function($e){

                $sm = $e->getTarget()->getServiceLocator();
                $params = $e->getParams();

                if($params) {
                    $container    = new Container('meliscms');

                    $pageId       = isset($params['idPage'])     ? $params['idPage']     : null;
                    $post         = isset($params['postValues']) ? $params['postValues'] : null;

                    if($pageId && $post) {

                        $pageContents = $container['content-pages'];
                        $plugins      = isset($post['melisDragDropZoneListPlugin']) ? $post['melisDragDropZoneListPlugin'] : null ;

                        if($plugins) {
                            foreach($plugins as $idx => $config) {
                                $plugin            = $config['melisPluginTag'];
                                $pluginId          = $config['melisPluginId'];
                                $pluginContainerId = isset($config['melisPluginContainer']) ? $config['melisPluginContainer'] : null;

                                if($plugin && $pluginId) {
                                    $pluginContent = isset($pageContents[$pageId][$plugin][$pluginId]) ?
                                        $pageContents[$pageId][$plugin][$pluginId] : null;

                                    // remove first the attribute if exists
                                    $pluginContainerPattern = '/\splugin_container_id\=\"(.*?)\"/';
                                    if(preg_match($pluginContainerPattern, $pluginContent)) {
                                        $pluginContent = preg_replace($pluginContainerPattern, '', $pluginContent);
                                    }

                                    $pattern = '/'.$plugin.'\sid\=\"(.*?)\"/';
                                    $replace = $plugin . ' id="'.$pluginId.'" plugin_container_id="'.$pluginContainerId.'"';
                                    $newValue = preg_replace($pattern, $replace, $pluginContent);

                                    $_SESSION['meliscms']['content-pages'][$pageId][$plugin][$pluginId] = $newValue;

                                    // virtual settings
                                    $data = array('plugin_container_id' => $pluginContainerId);

                                    if(isset($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId])) {
                                        $currentData = (array) json_decode($_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId]);
                                        $data        = array_merge($currentData, $data);
                                    }

                                    $_SESSION['meliscms']['content-pages'][$pageId]['private:melisPluginSettings'][$pluginId] = json_encode($data);
                                }
                            }
                        }
                    }
                }
            },
            // lowest priority so we can process this algorithm in the background
            $priority);

        $this->listeners[] = $callBackHandler;
    }

}