<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace SiteSample;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\ModuleManager;
use Zend\Stdlib\ArrayUtils;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function($e) {
        	$viewModel = $e->getViewModel();
        	$viewModel->setTemplate('layout/errorLayout');
        });
        $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, function($e) {
        	$viewModel = $e->getViewModel();
        	$viewModel->setTemplate('layout/errorLayout');
        }); 
    }
    
    public function init(ModuleManager $manager)
    {

    }

    public function getConfig()
    {
    	$config = array();
    	$configFiles = array(
    			include __DIR__ . '/config/module.config.php',
    			include __DIR__ . '/config/melis.plugins.config.php',
    			include __DIR__ . '/config/SiteSample.config.php',
    	);
    	
    	foreach ($configFiles as $file) {
    		$config = ArrayUtils::merge($config, $file);
    	} 
    	
    	return $config;
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}
