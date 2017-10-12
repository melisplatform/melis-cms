<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Listener\Factory;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;
use MelisCms\Listener\MelisCmsPluginSaveEditionSessionListener;

class MelisCmsPluginSaveEditionSessionListenerFactory implements FactoryInterface
{
	public function createService(ServiceLocatorInterface $sl)
	{ 
    	$melisCmsPluginSaveEditionSessionListener = new MelisCmsPluginSaveEditionSessionListener($sl);
	    return $melisCmsPluginSaveEditionSessionListener;
	}
}