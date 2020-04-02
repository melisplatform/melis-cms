<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service\Factory;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\ServiceManager\FactoryInterface;
use MelisCms\Service\MelisCmsPageGetterService;

class MelisCmsPageGetterServiceFactory implements FactoryInterface
{
	public function createService(ServiceLocatorInterface $sl)
	{ 
	    $melisCmsPageGetterService = new MelisCmsPageGetterService();
	    $melisCmsPageGetterService->setServiceLocator($sl);
	    return $melisCmsPageGetterService;
	}
}