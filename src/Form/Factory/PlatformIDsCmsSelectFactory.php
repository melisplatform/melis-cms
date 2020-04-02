<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Form\Factory;

use Laminas\ServiceManager\ServiceLocatorInterface;
use MelisCore\Form\Factory\MelisSelectFactory;

/**
 * Cms Platfrom Ids select factory
 * This will return available platform the have not added to Cms Platform IDs
 */
class PlatformIDsCmsSelectFactory extends MelisSelectFactory
{
	protected function loadValueOptions(ServiceLocatorInterface $formElementManager)
	{
		$serviceManager = $formElementManager->getServiceLocator();

		$platformTable = $serviceManager->get('MelisEngineTablePlatformIds');
		$platforms = $platformTable->getAvailablePlatforms();

		$valueoptions = array();
		$max = $platforms->count();
		for ($i = 0; $i < $max; $i++)
		{
			$tpl = $platforms->current();
			$valueoptions[$tpl->plf_id] = $tpl->plf_name;
			$platforms->next();
		}

		return $valueoptions; 
	}

}