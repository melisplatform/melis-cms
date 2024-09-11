<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Form\Factory;

use Laminas\ServiceManager\ServiceManager;
use MelisCore\Form\Factory\MelisSelectFactory;

/**
 * This class creates a select box for melis site variety
 */
class MelisCmsSiteVarietySelectFactory extends MelisSelectFactory
{
	protected function loadValueOptions(ServiceManager $serviceManager)
	{
        $siteService = $serviceManager->get('MelisCmsSiteService');
        $siteVariety = $siteService->getSiteVariety();
		
		$valueoptions = [];
		foreach($siteVariety as $key => $var){
            $valueoptions[$key] = $var;
        }
		
		return $valueoptions;
	}

}