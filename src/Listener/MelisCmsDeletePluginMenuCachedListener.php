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
use MelisCore\Controller\PluginViewController;
use MelisCore\Listener\MelisGeneralListener;

/**
 * This listener handle deletion of plugin menu cache 
 * in the BO page edition
 */
class MelisCmsDeletePluginMenuCachedListener extends MelisGeneralListener implements ListenerAggregateInterface
{
	public function attach(EventManagerInterface $events, $priority = 1)
	{
		$this->attachEventListener(
			$events,
			'*',
			[
				'meliscms_mini_template_service_create_template_end',
				'meliscms_mini_template_service_update_template_end',
				'meliscms_mini_template_service_delete_template_end'
			], 
			function($event) {
				$sm = $event->getTarget()->getServiceManager();

				$params = $event->getParams();
				// only delete if action is success
				if ($params['results']['success']) {
					// delete cache
					$cacheKey = 'meliscms_plugins_menu_content_';
					$sm->get('MelisEngineCacheSystem')
						->deleteCacheByPrefix($cacheKey, PluginViewController::cacheConfig);
				}
			},
		80
		);
	}
}