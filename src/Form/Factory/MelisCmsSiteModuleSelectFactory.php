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
 * This class creates a select box for site modules
 *
 */
class MelisCmsSiteModuleSelectFactory extends MelisSelectFactory
{
    protected function loadValueOptions(ServiceLocatorInterface $formElementManager)
    {
        $serviceManager = $formElementManager->getServiceLocator();
        $table = $serviceManager->get('MelisEngineTableSite');
        $cmsSiteData = $table->fetchAll();

        $valueoptions = array();

        foreach($cmsSiteData as $lang => $val) {
            $valueoptions[$val->site_name] = !empty($val->site_name) ? $val->site_name : $val->site_label;
        }

        return $valueoptions;
    }

}