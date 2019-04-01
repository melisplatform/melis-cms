<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service\Factory;


use MelisCms\Service\MelisCmsGdprService;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MelisCmsGdprServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $sl)
    {
        $melisCmsGdprService = new MelisCmsGdprService();
        $melisCmsGdprService->setServiceLocator($sl);
        return $melisCmsGdprService;
    }
}