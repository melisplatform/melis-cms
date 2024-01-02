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
use Laminas\Mvc\MvcEvent;
use Laminas\Session\Container;

/**
 * Page Cache Listener
 */
class MelisCmsPageGetterListener implements ListenerAggregateInterface
{
    public $listeners = [];

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $callBackHandler = $events->attach(
            MvcEvent::EVENT_FINISH,
            function(MvcEvent $e){
                
                // Get route match to know if we are displaying in back or front
                $routeMatch = $e->getRouteMatch();
                $params = $routeMatch->getParams();
                
                // Service manager
                $sm = $e->getApplication()->getServiceManager();
                $request = $sm->get('Request');

                $container = new Container('meliscms');

                // Checking if the request is from Publish action on Page Edtion
                if ($request->getQuery('idPage') && $params['action'] == 'publishPage') {
                    $pageId = $request->getQuery('idPage');
                    /**
                     * This will initialize a Page Cache flag to excute the caching procedure
                     * using the page id as identity of the flag
                     */
                    $container['page-cache-getter-temp'] = array();
                    $container['page-cache-getter-temp'][$pageId] = 1;

                }
                /**
                 * Checking the request is from retrieving the site from preview
                 * that has a query data of "melisSite" and Caching session flag from Publish action
                 */
                elseif ($request->getQuery('melisSite') && !empty($container['page-cache-getter-temp'])) {
                    if (! empty($params['idpage'])) {
                        $pageId = $params['idpage'];
                        if (!empty($container['page-cache-getter-temp'][$pageId])) {
                            // Page cache config
                            $cacheKey = 'cms_page_getter_'.$pageId;
                            $cacheConfig = 'meliscms_page';

                            // Retrieving page cache
                            $melisEngineCacheSystem = $sm->get('MelisEngineCacheSystem');
                            $results = $melisEngineCacheSystem->getCacheByKey($cacheKey, $cacheConfig, true);
                            if (!empty($results)) {
                                // Deleting page cached
                                $melisEngineCacheSystem->deleteCacheByPrefix($cacheKey, $cacheConfig);
                            }
                            // Unseting Publish flag from session
                            unset($container['page-cache-getter-temp'][$pageId]);
                        }
                    }
                }
            }
        );
        $this->listeners[] = $callBackHandler;
    }
    
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }
}