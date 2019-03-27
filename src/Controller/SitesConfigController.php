<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Stdlib\ArrayUtils;

class SitesConfigController extends AbstractActionController
{
    public function renderToolSitesSiteConfigAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();

        $view = new ViewModel();

        $view->melisKey = $melisKey;
        $view->siteId = $siteId;

        return $view;
    }

    public function renderToolSitesSiteConfigContentAction() {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $melisTool = $this->getTool();

        // Get form
        $configForm = $melisTool->getForm('meliscms_tool_sites_siteconfig_form');

        // Get site active languages
        $siteLangsTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteLangs');
        $activeSiteLangs = $siteLangsTable->getSiteLangs(null, $siteId, null, true)->toArray();

        // Get site data
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        $site = $siteTable->getEntryById($siteId)->toArray()[0];
        $siteName = $site['site_name'];

        // Get site config | Priority = 2
        $config = include __DIR__ . "/../../../../../module/MelisSites/$siteName/config/$siteName.config.php";
        // Get site config on DB | Priority = 1

        // Merge config

        $view = new ViewModel();

        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        $view->activeSiteLangs = $activeSiteLangs;
        $view->configForm = $configForm;
        $view->config = $config;
        $view->siteName = $siteName;

        return $view;
    }

    private function getMelisKey()
    {
        $melisKey = $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey'), null);

        return $melisKey;
    }

    private function getTool()
    {
        $toolSvc = $this->getServiceLocator()->get('MelisCoreTool');
        $toolSvc->setMelisToolKey('meliscms', 'meliscms_tool_sites');

        return $toolSvc;
    }
}
