<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service\Factory;

use MelisCms\Service\MelisCmsSitesPropertiesService;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

class MelisCmsSitesPropertiesServiceFactory implements FactoryInterface
{
	public function createService(ServiceLocatorInterface $sl)
	{
        $melisCmsSitesPropertiesService = new MelisCmsSitesPropertiesService();
        $melisCmsSitesPropertiesService->setServiceLocator($sl);
        return $melisCmsSitesPropertiesService;
	}
}