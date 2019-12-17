<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service\Factory;

use MelisCms\Service\MelisCmsPageExportService;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

class MelisCmsPageExportServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $sl)
    {
        $melisCmsPageService = new MelisCmsPageExportService();
        $melisCmsPageService->setServiceLocator($sl);
        return $melisCmsPageService;
    }
}