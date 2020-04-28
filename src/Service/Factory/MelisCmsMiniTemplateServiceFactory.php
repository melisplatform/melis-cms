<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service\Factory;

use MelisCms\Service\MelisCmsMiniTemplateService;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

class MelisCmsMiniTemplateServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $sl)
    {
        $service = new MelisCmsMiniTemplateService();
        $service->setServiceLocator($sl);
        return $service;
    }
}