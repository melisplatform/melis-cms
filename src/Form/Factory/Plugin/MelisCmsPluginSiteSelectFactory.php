<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Form\Factory\Plugin;

use Laminas\ServiceManager\ServiceManager;
use MelisCore\Form\Factory\MelisSelectFactory;

/**
 * Cms Site select factory
 */
class MelisCmsPluginSiteSelectFactory extends MelisSelectFactory
{
	protected function loadValueOptions(ServiceManager $serviceManager)
	{
		$siteTable = $serviceManager->get('MelisEngineTableSite');
		
		$valueoptions = array();
		foreach ($siteTable->fetchAll() As $val) {
		    $valueoptions[$val->site_id] = !empty($val->site_label) ? $val->site_label : $val->site_name;
		}
		
		return $valueoptions; 
	}
}