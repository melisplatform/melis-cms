<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms; 

use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\ArrayUtils;

use MelisCms\Listener\MelisCmsGetRightsTreeViewListener;
use MelisCms\Listener\MelisCmsPluginSaveEditionSessionListener;
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
        if (!empty($routeMatch)) {
            $routeName = $routeMatch->getMatchedRouteName();
            $module = explode('/', $routeName);
             
            if (!empty($module[0])) {
                if ($module[0] == 'melis-backoffice') {
                    /**
                     * Set default layout for this module
                     */
                    $eventManager->getSharedManager()->attach(__NAMESPACE__,
                        MvcEvent::EVENT_DISPATCH, function ($e) {
                            $e->getTarget()->layout('layout/layoutCms');
                        });

                    (new MelisCmsGetRightsTreeViewListener())->attach($eventManager);
                    (new MelisCmsSavePageListener())->attach($eventManager);
                    (new MelisCmsPublishPageListener())->attach($eventManager);
                    (new MelisCmsUnpublishPageListener())->attach($eventManager);
                    (new MelisCmsDeletePageListener())->attach($eventManager);
                    (new MelisCmsToolUserUpdateUserListener())->attach($eventManager);
                    (new MelisCmsFlashMessengerListener())->attach($eventManager);
                    // (new MelisCmsSiteDomainDeleteListener())->attach($eventManager);
                    (new MelisCmsPlatformIdListener())->attach($eventManager);
                    (new MelisCmsNewSiteDomainListener())->attach($eventManager);
                    (new MelisCmsDeleteSiteDomainListener())->attach($eventManager);
                    (new MelisCmsToolUserNewUserListener())->attach($eventManager);
                    (new MelisCmsDeletePlatformListener())->attach($eventManager);
                    (new MelisCmsPageDefaultUrlsListener())->attach($eventManager);
                    (new MelisCmsPageEditionSavePluginSessionListener())->attach($eventManager);
                    // Saving Plugin Tag values, Melis Side
                    (new MelisCmsPluginSaveEditionSessionListener())->attach($eventManager);
                }
                
                // Page Cache Listener
                (new MelisCmsPageGetterListener())->attach($eventManager);
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

        if (!empty($locale)) {
            $translationType = [
                'interface',
                'forms',
                'install',
            ];
            
            $translationList = [];

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
        $config = [];
        $configFiles = [
            include __DIR__ . '/../config/module.config.php',
            include __DIR__ . '/../config/app.interface.php',
            include __DIR__ . '/../config/app.forms.php',
            include __DIR__ . '/../config/app.tools.php',
            include __DIR__ . '/../config/diagnostic.config.php',
            include __DIR__ . '/../config/diagnostic.config.php',
            include __DIR__ . '/../config/app.microservice.php',
            include __DIR__ . '/../config/toolsSite/main.interface.php',
            include __DIR__ . '/../config/toolsSite/domains.tools.php',
            include __DIR__ . '/../config/toolsSite/properties.tools.php',
            include __DIR__ . '/../config/toolsSite/properties.interface.php',
            include __DIR__ . '/../config/toolsSite/moduleload.interface.php',
            include __DIR__ . '/../config/toolsSite/domains.interface.php',
            include __DIR__ . '/../config/toolsSite/languages.interface.php',
            include __DIR__ . '/../config/toolsSite/languages.tools.php',
            include __DIR__ . '/../config/toolsSite/siteconfig.interface.php',
            include __DIR__ . '/../config/toolsSite/siteconfig.tools.php',
            include __DIR__ . '/../config/toolsSite/sitetranslations.interface.php',
            include __DIR__ . '/../config/toolsSite/sitetranslations.tools.php',
            include __DIR__ . '/../config/dashboard-plugins/MelisCmsPagesIndicatorsPlugin.config.php',
            include __DIR__ . '/../config/gdpr.banner.interface.php',
            include __DIR__ . '/../config/mini-template/manager-tool.interface.php',
            include __DIR__ . '/../config/mini-template/manager-tool.tools.php',
            include __DIR__ . '/../config/mini-template/menu-manager-tool.interface.php',
            include __DIR__ . '/../config/mini-template/menu-manager-tool.tools.php',
        ];

        foreach ($configFiles as $file)
            $config = ArrayUtils::merge($config, $file);

        return $config;
    }

    public function getAutoloaderConfig()
    {
        return [
            'Laminas\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }
}
