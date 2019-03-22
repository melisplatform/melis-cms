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

/**
 * Site Tool Plugin
 */
class SitesLanguagesController extends AbstractActionController
{
    public function renderToolSitesLanguagesAction() {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();

        $view = new ViewModel();

        $view->melisKey = $melisKey;
        $view->siteId = $siteId;

        return $view;
    }

    public function renderToolSitesLanguagesContentAction() {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $melisEngineLangSvc = $this->getServiceLocator()->get('MelisEngineLang');
        $siteLangsTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteLangs');
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        $selectedLanguages = [];

        $form = $this->getTool()->getForm('meliscms_tool_sites_languages_form');
        $languages = $melisEngineLangSvc->getAvailableLanguages();
        $activeSiteLangs = $siteLangsTable->getSiteLangs(null, $siteId, null, true)->toArray();

        foreach ($activeSiteLangs as $language) {
            array_push($selectedLanguages, $language['slang_lang_id']);
        }

        $siteOptLangUrl = $siteTable->getEntryById($siteId)->toArray()[0]['site_opt_lang_url'];

        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        $view->form = $form;
        $view->languages = $languages;
        $view->siteLanguages = $activeSiteLangs;
        $view->selectedLanguages = $selectedLanguages;
        $view->siteOptionLangUrl = $siteOptLangUrl;

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
