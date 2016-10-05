<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
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
     * Dispatch to a module/controller/action to get back a json result
     * With success/errors/datas returned
     * Use the meliscms session container to add result in a queue for further use
     * Used for handling save page
     *
     * @param MvcEvent $e
     * @param String $nameVarSession
     * @param String $disptachController
     * @param array $dispatchVars
     * @return array
     */
    /*   public function dispatchPluginAction($e, $nameVarSession, $disptachController, $dispatchVars)
       {
           // Get session of module
           $container = new Container('meliscms');
           
           // Get the controller to be able to use forward
           $oController = $e->getTarget();
           $success = $container[$nameVarSession]['success'];
           $errors = $container[$nameVarSession]['errors'];
           $datas = $container[$nameVarSession]['datas'];
           $resultTmp = $oController->forward()->dispatch($disptachController, $dispatchVars)->getVariables();
           
           // Check the result
           if ($resultTmp['success'] == 0)
               $success = 0;
           
           // Add errors to previously existing ones in session
           if ($resultTmp['success'] == 0)
           {
               foreach ($resultTmp['errors'] as $error)
               {
                   foreach ($error as $keyError => $valError)
                       $errors[$keyError] = $valError;
               }
           }
           
           // add datas to session
           if (!empty($resultTmp['datas']))
               $datas = array_merge($datas, $resultTmp['datas']);
           
           // Final table to send back
           $result = array('success' => $success, 'errors' => $errors, 'datas' => $datas);
           
           // Copy new results in session
           $container[$nameVarSession] = $result;
           
           // also return results
           return array($success, $errors, $datas);
       } */

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
