<?php 

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Listener;

use Laminas\EventManager\EventInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Session\Container;

use MelisCore\Listener\MelisGeneralListener;

class MelisCmsSavePageListener extends MelisGeneralListener implements ListenerAggregateInterface
{
	
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $identifier = 'MelisCms';

        $eventsName = [
            'meliscms_page_save_start',
            'meliscms_page_publish_start',
        ];

        $priority = 100;

        $this->attachEventListener($events, $identifier, $eventsName, [$this, 'pageSavePublish'], $priority);
    }

    public function pageSavePublish(EventInterface $event)
    {
        $sm = $event->getTarget()->getEvent()->getApplication()->getServiceManager();
        $melisCoreDispatchService = $sm->get('MelisCoreDispatch');

        list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
            $event,
            'meliscms',
            'action-page-tmp',
            'MelisCms\Controller\Page',
            ['action' => 'pageActionsRightCheck', 'actionwanted' => 'save']
        );
        if (!$success)
            return;

        $fatherPageId = $event->getParam('fatherPageId', '-1');
        // Create page entry / check existence of idPage in PageTree
        list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
            $event,
            'meliscms',
            'action-page-tmp',
            'MelisCms\Controller\PageProperties',
            ['action' => 'savePageTree', 'fatherPageId' => $fatherPageId]
        );
        if (!$success)
            return;

        $idPage = $datas['idPage'];
        $isNew = $datas['isNew'];


        // Save properties tab
        list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
            $event,
            'meliscms',
            'action-page-tmp',
            'MelisCms\Controller\PageProperties',
            array_merge(['action' => 'saveProperties'], $datas)
        );
        if (!$success)
            return;

        // Save page style
        list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
            $event,
            'meliscms',
            'action-page-tmp',
            'MelisCms\Controller\ToolStyle',
            array_merge(['action' => 'savePageStyle', 'actionwanted' => 'save'], $datas)
        );
        if (!$success)
            return;

        // Save properties tab
        list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
            $event,
            'meliscms',
            'action-page-tmp',
            'MelisCms\Controller\PageEdition',
            array_merge(['action' => 'saveEdition'], $datas)
        );
        if (!$success)
            return;

        // Save seo tab
        if (!$isNew) {
            list($success, $errors, $datas) = $melisCoreDispatchService->dispatchPluginAction(
                $event,
                'meliscms',
                'action-page-tmp',
                'MelisCms\Controller\PageSeo',
                array_merge(['action' => 'saveSeo'], $datas)
            );
            if (!$success)
                return;
        }
    }
}