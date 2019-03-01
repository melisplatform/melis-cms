<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service\Factory;

use MelisCms\Service\MelisCmsSitesModuleLoadService;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

class MelisCmsSitesModuleLoadServiceFactory implements FactoryInterface
{
	public function createService(ServiceLocatorInterface $sl)
	{
        $melisCmsSitesModuleLoadService = new MelisCmsSitesModuleLoadService();
        $melisCmsSitesModuleLoadService->setServiceLocator($sl);
        return $melisCmsSitesModuleLoadService;
	}
}