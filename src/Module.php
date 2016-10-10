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
//         $eventManager->attach(new MelisCmsPlatformIdListener());
        $eventManager->attach(new MelisCmsNewSiteDomainListener());
        $eventManager->attach(new MelisCmsDeleteSiteDomainListener());
        $eventManager->attach(new MelisCmsInstallerLastProcessListener());
        $eventManager->attach(new MelisCmsToolUserNewUserListener());

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

    public function init(ModuleManager $mm)
    {
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
        $translator->addTranslationFile('phparray', __DIR__ . '/../language/' . $locale . '.interface.php');
        $translator->addTranslationFile('phparray', __DIR__ . '/../language/' . $locale . '.forms.php');
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

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'MelisCms\Service\MelisCmsRightsService' => function ($sm) {
                    $melisCmsRightsService = new \MelisCms\Service\MelisCmsRightsService();
                    $melisCmsRightsService->setServiceLocator($sm);

                    return $melisCmsRightsService;
                },
            ),
        );
    }
}
