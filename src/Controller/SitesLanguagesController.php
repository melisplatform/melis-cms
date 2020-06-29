<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Laminas\View\Model\ViewModel;
use MelisCore\Controller\MelisAbstractActionController;

class SitesLanguagesController extends MelisAbstractActionController
{
    /**
     * Renders the languages tab
     * @return ViewModel
     */
    public function renderToolSitesLanguagesAction()
    {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();

        $rightService = $this->getServiceManager()->get('MelisCoreRights');
        $canAccess = $rightService->canAccess('meliscms_tool_sites_languages_content');

        $view = new ViewModel();

        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        $view->canAccess = $canAccess;

        return $view;
    }

    /**
     * Renders the languages tab content
     * @return ViewModel
     */
    public function renderToolSitesLanguagesContentAction()
    {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        /**
         * Make sure site id is not empty
         */
        if(empty($siteId))
            return;

        $melisKey = $this->getMelisKey();

        $availableLangs = $this->getCmsLanguages();
        $activeLangs = $this->getSiteActiveLanguages($siteId);
        $siteOptLangUrl = $this->getSiteField($siteId, 'site_opt_lang_url');
        $siteLangs = [];

        $form = $this->getTool()->getForm('meliscms_tool_sites_languages_form');
        $form->setData([
            'site_opt_lang_url' => $siteOptLangUrl
        ]);

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
     * Returns Site Field
     * @param $siteId
     * @param $field
     * @return mixed
     */
    private function getSiteField($siteId, $field)
    {
        $siteData = $this->getSiteData($siteId);

        if (!empty($siteData)) {
            $field = $siteData[0][$field];
        }

        return $field;
    }

    /**
     * Returns Site
     * @param $siteId
     * @return mixed
     */
    private function getSiteData($siteId)
    {
        $siteTable = $this->getServiceManager()->get('MelisEngineTableSite');
        return $siteTable->getEntryById($siteId)->toArray();
    }

    /**
     * Return Site Active Languages
     * @param $siteId
     * @return mixed
     */
    private function getSiteActiveLanguages($siteId)
    {
        $siteLangsTable = $this->getServiceManager()->get('MelisEngineTableCmsSiteLangs');
        return $siteLangsTable->getSiteLangs(null, $siteId, null, true)->toArray();
    }

    /**
     * Return Available Languages
     * @return mixed
     *
     */
    private function getCmsLanguages()
    {
        $melisEngineLangSvc = $this->getServiceManager()->get('MelisEngineLang');
        return $melisEngineLangSvc->getAvailableLanguages();
    }

    /**
     *  Returns Meliskey
     * @return mixed
     */
    private function getMelisKey()
    {
        $melisKey = $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey'), null);

        return $melisKey;
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
