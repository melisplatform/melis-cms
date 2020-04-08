<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Laminas\View\Model\ViewModel;
use MelisCore\Controller\AbstractActionController;

class SitesPropertiesController extends AbstractActionController
{
    /**
     * Render Site Properties Container
     * @return ViewModel
     */
    public function renderToolSitesPropertiesAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();

        $rightService = $this->getServiceManager()->get('MelisCoreRights');
        $canAccess = $rightService->canAccess('meliscms_tool_sites_properties_content');

        $view = new ViewModel();

        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        $view->canAccess = $canAccess;

        return $view;
    }

    /**
     * Render Site Properties Content
     * @return ViewModel
     */
    public function renderToolSitesPropertiesContentAction() {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        /**
         * Make sure site id is not empty
         */
        if(empty($siteId))
            return;

        $melisKey = $this->getMelisKey();
        $melisTool = $this->getTool();

        // GET FORMS
        $propertiesForm = $melisTool->getForm("meliscms_tool_sites_properties_form");
        $homepageForm = $melisTool->getForm("meliscms_tool_sites_properties_homepage_form");

        // SITE PROPERTIES
        $sitePropSvc = $this->getServiceManager()->get("MelisCmsSitesPropertiesService");
        $siteProp = $sitePropSvc->getSitePropAnd404BySiteId($siteId);
        $siteLangHomepages = $sitePropSvc->getLangHomepages($siteId);

        // SET DATA TO FORM
        $propertiesForm->setData((array)$siteProp);

        // LANGUAGES GET ACTIVE LANGUAGES OF THE SITE
        $siteLangsTable = $this->getServiceManager()->get('MelisEngineTableCmsSiteLangs');
        $activeSiteLangs = $siteLangsTable->getSiteLangs(null, $siteId, null, true)->toArray();

        // GET SITE HOME PAGE IDS
        $siteHomePageIds = $this->getSiteHomePageIds($siteId, null);

        $view = new ViewModel();

        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        $view->propertiesForm = $propertiesForm;
        $view->homepageForm = $homepageForm;
        $view->siteLangHomepages = $siteLangHomepages;
        $view->activeSiteLangs = $activeSiteLangs;
        $view->siteHomePageIds = $siteHomePageIds;

        return $view;
    }

    /**
     * Returns Site Home Page Ids
     * @param $siteId
     * @param $langId
     * @return mixed
     */
    private function getSiteHomePageIds($siteId, $langId) {
        $siteLangHomeTbl = $this->getServiceManager()->get('MelisEngineTableCmsSiteHome');

        return $siteLangHomeTbl->getHomePageBySiteIdAndLangId($siteId, $langId)->toArray();
    }

    /**
     * Returns Melis Key
     * @return mixed
     */
    private function getMelisKey()
    {
        return $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey'), null);
    }

    /**
     * Returns Tool
     * @return array|object
     */
    private function getTool()
    {
        $toolSvc = $this->getServiceManager()->get('MelisCoreTool');
        $toolSvc->setMelisToolKey('meliscms', 'meliscms_tool_sites');

        return $toolSvc;
    }
}
