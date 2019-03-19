<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2015 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace SiteSample\Controller;

use MelisFront\Controller\MelisSiteActionController;
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
        $siteConfig = $sm->get('config');
        $siteConfig = $siteConfig['site']['SiteSample'];
        $allSitesConfig = $siteConfig['allSites'];

        $siteId = $this->layout()->getVariable('siteId');
        $langLocale = $this->layout()->getVariable('siteLangLocale');
        // Adding the SiteConfig to layout so views can access to the SiteConfig easily
        if(isset($siteConfig[$siteId][$langLocale])){
            $this->layout()->setVariable('siteConfig', $siteConfig[$siteId][$langLocale]);
        }
        $this->layout()->setVariable('allSites', $allSitesConfig);

        return parent::onDispatch($event);
    }
}