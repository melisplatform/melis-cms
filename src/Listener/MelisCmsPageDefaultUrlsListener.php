<?php 

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Listener;

use Laminas\EventManager\EventInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use MelisCore\Listener\MelisCoreGeneralListener;

/**
 * The flash messenger will add logs by
 * listening to a lot of events
 * 
 */
class MelisCmsPageDefaultUrlsListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{
	
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $sharedEvents      = $events->getSharedManager();

        $identifier = 'MelisCms';

        $eventsName = [
            'meliscms_page_save_end',
            'meliscms_page_publish_end',
            'meliscms_page_unpublish_end',
            'meliscms_page_delete_end',
            'meliscms_page_move_end',
        ];

        $priority = -1000;

        foreach ($eventsName As $event)
            $this->listeners[] = $sharedEvents->attach($identifier, $event, [$this, 'updateDefaultUrls'], $priority);
    }

    public function updateDefaultUrls(EventInterface $event)
    {
        $sm = $event->getTarget()->getEvent()->getApplication()->getServiceManager();
        $melisCoreDispatchService = $sm->get('MelisCoreDispatch');

        $params = $e->getParams();
        $results = $e->getTarget()->forward()->dispatch(
            'MelisCms\Controller\Page',
            array_merge(['action' => 'updateDefaultUrls'], $params))->getVariables();
    }
}