<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms; 

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\ModuleManager;
use Zend\Session\Container;
use Zend\Session\SessionManager;
use Zend\Stdlib\ArrayUtils;

use MelisCms\Listener\MelisCmsGetRightsTreeViewListener;
use MelisCms\Listener\MelisCmsSavePageListener;
use MelisCms\Listener\MelisCmsPublishPageListener;
use MelisCms\Listener\MelisCmsUnpublishPageListener;
use MelisCms\Listener\MelisCmsDeletePageListener;
use MelisCms\Listener\MelisCmsToolUserUpdateUserListener;
use MelisCms\Listener\MelisCmsFlashMessengerListener;
use MelisCms\Listener\MelisCmsSiteDomainDeleteListener;
use MelisCms\Listener\MelisCmsPlatformIdListener;
use MelisCms\Listener\MelisCmsNewSiteDomainListener;
use MelisCms\Listener\MelisCmsDeleteSiteDomainListener;
use MelisCms\Listener\MelisCmsInstallerLastProcessListener;
use MelisCms\Listener\MelisCmsToolUserNewUserListener;
use MelisCms\Listener\MelisCmsDeletePlatformListener;
use MelisCms\Listener\MelisCmsPageDefaultUrlsListener;
use MelisCms\Listener\MelisCmsPageGetterListener;
use MelisCms\Listener\MelisCmsPageEditionSavePluginSessionListener;
use MelisCms\Listener\MelisCmsAddPluginContainerListener;

/**
 * Class Module
 * @package MelisCms
 * @require melis-core|melis-engine|melis-front
 */
class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        /**
         * Create translations for this module
         * Init session container for meliscms
         */
        $this->createTranslations($e);
        $this->initSession();

        $sm = $e->getApplication()->getServiceManager();
        $routeMatch = $sm->get('router')->match($sm->get('request'));
        if (!empty($routeMatch))
        {
            $routeName = $routeMatch->getMatchedRouteName();
            $module = explode('/', $routeName);
             
            if (!empty($module[0]))
            {
                if ($module[0] == 'melis-backoffice')
                {
                    /**
                     * Set default layout for this module
                     */
                    $eventManager->getSharedManager()->attach(__NAMESPACE__,
                        MvcEvent::EVENT_DISPATCH, function ($e) {
                            $e->getTarget()->layout('layout/layoutCms');
                        });
                    
            
                    $eventManager->attach(new MelisCmsGetRightsTreeViewListener());
                    $eventManager->attach(new MelisCmsSavePageListener());
                    $eventManager->attach(new MelisCmsPublishPageListener());
                    $eventManager->attach(new MelisCmsUnpublishPageListener());
                    $eventManager->attach(new MelisCmsDeletePageListener());
                    $eventManager->attach(new MelisCmsToolUserUpdateUserListener());
                    $eventManager->attach(new MelisCmsFlashMessengerListener());
            //         $eventManager->attach(new MelisCmsSiteDomainDeleteListener());
                    $eventManager->attach(new MelisCmsPlatformIdListener());
                    $eventManager->attach(new MelisCmsNewSiteDomainListener());
                    $eventManager->attach(new MelisCmsDeleteSiteDomainListener());
                    $eventManager->attach(new MelisCmsInstallerLastProcessListener());
                    $eventManager->attach(new MelisCmsToolUserNewUserListener());
                    $eventManager->attach(new MelisCmsDeletePlatformListener());
                    $eventManager->attach(new MelisCmsPageDefaultUrlsListener());
                    $eventManager->attach(new MelisCmsPageEditionSavePluginSessionListener());

                    // Saving Plugin Tag values, Melis Side
                    $eventManager->attach($sm->get('MelisCms\Listener\MelisCmsPluginSaveEditionSessionListener'));
                }
                
                // Page Cache Listener
                $eventManager->attach(new MelisCmsPageGetterListener());
            }
        }
    }


    /**
     * Create module's session container
     */
    public function initSession()
    {
        $sessionManager = new SessionManager();
        $sessionManager->start();
        Container::setDefaultManager($sessionManager);
        $container = new Container('meliscms');

    }

    /**
     * Create translations for this module
     * @param MvcEvent $e
     */
    public function createTranslations($e)
    {
        $sm = $e->getApplication()->getServiceManager();
        $translator = $sm->get('translator');

        // Get the locale used from meliscore session
        $container = new Container('meliscore');
        $locale = $container['melis-lang-locale'];

        $locale = is_null($locale) ? 'en_EN' : $locale;
        // Load files

        if (!empty($locale))
        {   
            $translationType = array(
                'interface',
                'forms',
                'install',
            );
            
        $translationList = array();
            if(file_exists($_SERVER['DOCUMENT_ROOT'].'/../module/MelisModuleConfig/config/translation.list.php')){
                $translationList = include 'module/MelisModuleConfig/config/translation.list.php';
            }
            
            foreach($translationType as $type){
            
                $transPath = '';
                $moduleTrans = __NAMESPACE__."/$locale.$type.php";
                
                if(in_array($moduleTrans, $translationList)){
                    $transPath = "module/MelisModuleConfig/languages/".$moduleTrans;
                }
            
                if(empty($transPath)){
                    
                    // if translation is not found, use melis default translations
                    $defaultLocale = (file_exists(__DIR__ . "/../language/$locale.$type.php"))? $locale : "en_EN";
                    $transPath = __DIR__ . "/../language/$defaultLocale.$type.php";
                }
            
                $translator->addTranslationFile('phparray', $transPath);
            }
        }
    }

    /**
     * Get config files and specific ones for Melis Cms
     */
    public function getConfig()
    {
        $config = array();
        $configFiles = array(
            include __DIR__ . '/../config/module.config.php',
            include __DIR__ . '/../config/app.interface.php',
            include __DIR__ . '/../config/app.forms.php',
            include __DIR__ . '/../config/app.tools.php',
            include __DIR__ . '/../config/diagnostic.config.php',
            include __DIR__ . '/../config/diagnostic.config.php',
            include __DIR__ . '/../config/app.microservice.php',
            include __DIR__ . '/../config/app.install.php',
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
