<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service\Factory;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;
use MelisCms\Service\MelisCmsSiteService;

class MelisCmsSiteServiceFactory implements FactoryInterface
{
	public function createService(ServiceLocatorInterface $sl)
	{ 
        $melisCmsSiteService = new MelisCmsSiteService();
        $melisCmsSiteService->setServiceLocator($sl);
        return $melisCmsSiteService;
	}
}