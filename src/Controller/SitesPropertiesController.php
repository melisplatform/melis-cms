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

class SitesPropertiesController extends AbstractActionController
{
    const TOOL_INDEX = 'meliscms';
    const TOOL_KEY = 'meliscms_tool_sites';

    public function renderToolSitesPropertiesAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        return $view;
    }

    public function renderToolSitesPropertiesContentAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $cmsLangSvc = $this->getServiceLocator()->get('MelisEngineLang');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);

        $cmsLangs = $cmsLangSvc->getAvailableLanguages();
        // GET FORMS
        $propertiesForm = $melisTool->getForm("meliscms_tool_sites_properties_form");
        $homepageForm = $melisTool->getForm("meliscms_tool_sites_properties_homepage_form");

        // SITE PROPERTIES
        $sitePropSvc = $this->getServiceLocator()->get("MelisCmsSitesPropertiesService");
        $siteProp = $sitePropSvc->getSitePropAnd404BySiteId($siteId);
        $siteLangHomepages = $sitePropSvc->getLangHomepages($siteId);

        //setting data for the properties form
        $propertiesForm->setData((array)$siteProp);

        //Filter the cms language that are only used by the current site
        $tempCmsLangs = $cmsLangs;
        $cmsLangs = array();
        foreach($tempCmsLangs as $tempCmsLang){
            foreach ($siteLangHomepages as $siteLangHomepage){
                if($tempCmsLang['lang_cms_id'] === $siteLangHomepage['shome_lang_id']){
                    array_push($cmsLangs,$tempCmsLang);
                }
            }
        }
        array_reverse($cmsLangs);

        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        $view->propertiesForm = $propertiesForm;
        $view->homepageForm = $homepageForm;
        $view->siteLangHomepages = $siteLangHomepages;
        $view->cmsLangs = $cmsLangs;
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
        $toolSvc->setMelisToolKey('MelisCmsUserAccount', 'melis_cms_user_account');
        return $toolSvc;
    }
}
