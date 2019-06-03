<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Listener;


use MelisCore\Listener\MelisCoreGeneralListener;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * The flash messenger will add logs by
 * listening to a lot of events
 *
 */
class MelisCmsFlashMessengerListener extends MelisCoreGeneralListener implements ListenerAggregateInterface
{
    /**
     * @param EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events)
    {
        $sharedEvents = $events->getSharedManager();

        $callBackHandler = $sharedEvents->attach(
            'MelisCms',
            array(
                'meliscms_page_save_end',
                'meliscms_page_publish_end',
                'meliscms_page_unpublish_end',
                'meliscms_page_delete_end',
                'meliscms_page_move_end',
                'meliscms_template_savenew_end',
                'meliscms_template_save_end',
                'meliscms_template_delete_end',
                'meliscms_site_save_end',
                'meliscms_site_delete_end',
                'meliscms_site_delete_by_id_end',
                'meliscms_language_new_end',
                'meliscms_language_delete_end',
                'meliscms_language_update_end',
                'meliscms_page_clear_saved_page_end',
                'meliscms_platform_IDs_save_end',
                'meliscms_platform_IDs_delete_end',
                'meliscalendar_save_site_redirect_end',
                'meliscalendar_delete_site_redirect_end',
                'meliscms_page_duplicate_end',
                'meliscms_style_save_details_end',
                'meliscms_style_delete_end',
                'meliscms_create_new_page_lang_end',
                'meliscms_tree_duplicate_page_trees_end'
            ),
            function ($e) {
                $params = $e->getParams();

                if (isset($params['tmpModuleErrorMsg'])) {
                    $params['textMessage'] = $params['tmpModuleErrorMsg'];
                }

                $e->getTarget()->forward()->dispatch(
                    'MelisCore\Controller\MelisFlashMessenger',
                    array_merge(
                        ['action' => 'log'],
                        $params
                    ))->getVariables();
            },
            -1000
        );
        $this->listeners[] = $callBackHandler;
    }
}