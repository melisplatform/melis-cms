<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Form\Factory\Plugin;

use Zend\ServiceManager\ServiceLocatorInterface;
use MelisCore\Form\Factory\MelisSelectFactory;

/**
 * Cms Site select factory
 */
class MelisCmsPluginSiteSelectFactory extends MelisSelectFactory
{
	protected function loadValueOptions(ServiceLocatorInterface $formElementManager)
	{
		$serviceManager = $formElementManager->getServiceLocator();
		
		$siteTable = $serviceManager->get('MelisEngineTableSite');
		
		$valueoptions = array();
		foreach ($siteTable->fetchAll() As $val)
		{
		    $valueoptions[$val->site_id] = $val->site_name;
		}
		
		return $valueoptions; 
	}
}