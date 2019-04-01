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

        $langLocale = $this->params()->fromRoute('pageLangLocale');
        $pageId = $this->params()->fromRoute('idpage');
        /**
         * get site id using page id
         */
        $siteId = 0;
        $pageTreeSrv = $sm->get('MelisEngineTree');
        $siteData = $pageTreeSrv->getSiteByPageId($pageId);
        if(!empty($siteData)){
            $siteId = $siteData->site_id;
        }

        /**
         * Adding the SiteConfig to layout so views can access to the SiteConfig easily
         */
        if(isset($siteConfig[$siteId][$langLocale])){
            $this->layout()->setVariable('siteConfig', $siteConfig[$siteId][$langLocale]);
        }
        $this->layout()->setVariable('allSites', $allSitesConfig);

        return parent::onDispatch($event);
    }
}