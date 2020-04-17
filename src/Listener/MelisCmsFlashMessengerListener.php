<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Listener;


use Laminas\EventManager\EventInterface;
use MelisCore\Listener\MelisGeneralListener;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;

/**
 * The flash messenger will add logs by
 * listening to a lot of events
 *
 */
class MelisCmsFlashMessengerListener extends MelisGeneralListener implements ListenerAggregateInterface
{
    /**
     * @param EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $sharedEvents = $events->getSharedManager();
        /**
         * Attach a listener to an event emitted by components with specific identifiers.
         *
         * @param  string $identifier Identifier for event emitting component
         * @param  string $eventName
         * @param  callable $listener Listener that will handle the event.
         * @param  int $priority Priority at which listener should execute
         *
         * $sharedEvents->attach($identifier, $eventName, callable $listener, $priority);
         */
        $identifier = 'MelisCms';

        $eventsName = [
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
            'meliscms_tree_duplicate_page_trees_end',
            'meliscms_gdpr_save_banner_end',
            'meliscms_page_tree_export_end',
            'meliscms_page_tree_import_end'
        ];

        $priority = -1000;

        $this->attachEventListener($events, $identifier, $eventsName, [$this, 'logMessages'], $priority);
    }
}