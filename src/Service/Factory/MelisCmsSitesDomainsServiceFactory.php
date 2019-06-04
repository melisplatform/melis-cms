<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service\Factory;

use MelisCms\Service\MelisCmsSitesDomainsService;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

class MelisCmsSitesDomainsServiceFactory implements FactoryInterface
{
	public function createService(ServiceLocatorInterface $sl)
	{
        $melisCmsSitesDomainsService = new MelisCmsSitesDomainsService();
        $melisCmsSitesDomainsService->setServiceLocator($sl);
        return $melisCmsSitesDomainsService;
	}
}