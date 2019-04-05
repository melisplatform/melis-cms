<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace SiteSample\Controller;

use MelisFront\Controller\MelisSiteActionController;
use MelisFront\Service\MelisSiteConfigService;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;

class BaseController extends MelisSiteActionController
{
    public $view = null;
    
    function __construct()
    {
        $this->view = new ViewModel();
    }
    
    public function onDispatch(MvcEvent $event)
    {
        // Getting the Site config "SiteSample.config.php"
        $sm = $event->getApplication()->getServiceManager();
        $pageId = $this->params()->fromRoute('idpage');

        /**
         * Add site config in the layout
         */
        /** @var MelisSiteConfigService $siteConfigSrv */
        $siteConfigSrv = $sm->get('MelisSiteConfigService');
        $siteConfig = $siteConfigSrv->getSiteConfigByPageId($pageId);
        $this->layout()->setVariable('siteConfig', $siteConfig['siteConfig']);
        $this->layout()->setVariable('allSites', $siteConfig['allSites']);

        return parent::onDispatch($event);
    }
}