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
 * This class creates a select box for site modules
 *
 */
class MelisCmsSiteModuleSelectFactory extends MelisSelectFactory
{
    protected function loadValueOptions(ServiceManager $serviceManager)
    {
        $table = $serviceManager->get('MelisAssetManagerModulesService');
        $sites = $table->getSitesModules();

        $valueoptions = [];
        foreach($sites as $key => $val) {
            $valueoptions[$val] = $val;
        }

        return $valueoptions;
    }
}