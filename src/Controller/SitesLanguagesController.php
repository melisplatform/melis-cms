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
    /**
     * Renders the languages tab
     * @return ViewModel
     */
    public function renderToolSitesLanguagesAction() {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();

        $view = new ViewModel();

        $view->melisKey = $melisKey;
        $view->siteId = $siteId;

        return $view;
    }

    /**
     * Renders the languages tab content
     * @return ViewModel
     */
    public function renderToolSitesLanguagesContentAction() {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();

        // Tables & Services
        $melisEngineLangSvc = $this->getServiceLocator()->get('MelisEngineLang');
        $siteLangsTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteLangs');
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');

        // Get languages form
        $form = $this->getTool()->getForm('meliscms_tool_sites_languages_form');

        // Get all site languages
        $availableLangs = $melisEngineLangSvc->getAvailableLanguages();
        // Get active site languages
        $activeLangs = $siteLangsTable->getSiteLangs(null, $siteId, null, true)->toArray();

        $siteLangs = [];

        // Get site language url option
        $siteOptLangUrl = $siteTable->getEntryById($siteId)->toArray();

        if (!empty($siteOptLangUrl)) {
            $siteOptLangUrl = $siteOptLangUrl[0]['site_opt_lang_url'];
        }

        // Store all active lang ids. This will be used in the view to check for active languages
        foreach ($activeLangs as $language) {
            array_push($siteLangs, $language['slang_lang_id']);
        }

        $activeLangs = $siteLangs;

        $view = new ViewModel();

        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        $view->form = $form;
        $view->activeLangs = $activeLangs;
        $view->availableLangs = $availableLangs;
        $view->siteOptLangUrl = $siteOptLangUrl;

        return $view;
    }

    /**
     *  Returns meliskey
     * @return mixed
     */
    private function getMelisKey()
    {
        $melisKey = $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey'), null);

        return $melisKey;
    }

    /**
     * Returns tool
     * @return array|object
     */
    private function getTool()
    {
        $toolSvc = $this->getServiceLocator()->get('MelisCoreTool');
        $toolSvc->setMelisToolKey('meliscms', 'meliscms_tool_sites');

        return $toolSvc;
    }
}
