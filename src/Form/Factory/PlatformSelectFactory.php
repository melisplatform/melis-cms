<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Form\Factory;

use Zend\ServiceManager\ServiceLocatorInterface;
use MelisCore\Form\Factory\MelisSelectFactory;

/**
 * Template select factory to fill the template list
 */
class PlatformSelectFactory extends MelisSelectFactory
{
	protected function loadValueOptions(ServiceLocatorInterface $formElementManager)
	{
		$serviceManager = $formElementManager->getServiceLocator();

		$platformTable = $serviceManager->get('MelisCoreTablePlatform');
		$platforms = $platformTable->fetchAll();

		$valueoptions = array();
		$max = $platforms->count();
		for ($i = 0; $i < $max; $i++)
		{
			$tpl = $platforms->current();
			$valueoptions[$tpl->plf_name] = $tpl->plf_name;
			$platforms->next();
		}

		return $valueoptions;
	}

}