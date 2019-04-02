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

class SitesConfigController extends AbstractActionController
{
    /**
     * Renders Site Config Tab Container
     * @return ViewModel
     */
    public function renderToolSitesSiteConfigAction()
    {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();

        $view = new ViewModel();

        $view->melisKey = $melisKey;
        $view->siteId = $siteId;

        return $view;
    }

    /**
     * Renders Site Config Tab Content
     * @return ViewModel
     */
    public function renderToolSitesSiteConfigContentAction()
    {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $melisTool = $this->getTool();

        $configForm = $melisTool->getForm('meliscms_tool_sites_siteconfig_form');
        $activeSiteLangs = $this->getSiteActiveLanguages($siteId);

        $site = $this->getSiteDataById($siteId);
        $siteName = $site['site_name'];

        $config = $this->getSiteConfig($siteId);
        $siteConfig = $this->getSiteConfigFromDb($siteId);

        $conff = [];

        foreach ($siteConfig as $conf) {
            $conff[$conf['sconf_lang_id']] = $conf['sconf_id'];
        }

        // check if the data is from the db or not
        $valuesFromDb = [];

        foreach ($siteConfig as $dbConff) {
            if ($dbConff['sconf_lang_id'] === '-1') {
                if (array_key_exists('allSites', unserialize($dbConff['sconf_datas'])['site'][$siteName])) {
                    foreach (unserialize($dbConff['sconf_datas'])['site'][$siteName]['allSites'] as $key => $value) {
                        if ($config['site'][$siteName]['allSites'][$key] == $value) {
                            $valuesFromDb['allSites'][] = $key;
                        }
                    }
                }
            } else {
                if (array_key_exists($siteId, unserialize($dbConff['sconf_datas'])['site'][$siteName])) {
                    foreach (unserialize($dbConff['sconf_datas'])['site'][$siteName][$siteId] as $langKey => $langValue) {
                        foreach ($langValue as $confKey => $confVal) {
                            if ($config['site'][$siteName][$siteId][$langKey][$confKey] == $confVal) {
                                $valuesFromDb[$langKey][] = $confKey;
                            }
                        }
                    }
                }
            }
        }

        $this->kSortSiteConfig($config, $siteName, $siteId);

        $view = new ViewModel();

        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        $view->activeSiteLangs = $activeSiteLangs;
        $view->configForm = $configForm;
        $view->config = $config;
        $view->siteName = $siteName;
        $view->conff = $conff;
        $view->valuesFromDb = $valuesFromDb;

        return $view;
    }

    /**
     * Sorts site config key in ascending order
     * @param $config
     * @param $siteName
     * @param $siteId
     */
    private function kSortSiteConfig(&$config, $siteName, $siteId)
    {
        foreach ($config['site'][$siteName][$siteId] as $langKey => $langConfig) {
            ksort($config['site'][$siteName][$siteId][$langKey]);
        }
    }

    /**
     * Returns site config from db
     * @param $siteId
     * @return mixed
     */
    private function getSiteConfigFromDb($siteId)
    {
        $siteConfigTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteConfig');

        return $siteConfigTable->getEntryByField('sconf_site_id', $siteId)->toArray();
    }

    /**
     * Returns Site Config (merged | final)
     * @param $siteId
     * @return mixed
     */
    private function getSiteConfig($siteId)
    {
        $siteConfigSrv = $this->getServiceLocator()->get('MelisSiteConfigService');

        return $siteConfigSrv->getSiteConfigById($siteId);
    }

    /**
     * Returns Site Data
     * @param $siteId
     * @return mixed
     */
    private function getSiteDataById($siteId)
    {
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');

        return $siteTable->getEntryById($siteId)->toArray()[0];
    }

    /**
     * Returns Site Active Languages
     * @param $siteId
     * @return mixed
     */
    private function getSiteActiveLanguages($siteId)
    {
        $siteLangsTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteLangs');

        return $siteLangsTable->getSiteLangs(null, $siteId, null, true)->toArray();
    }

    /**
     * Returns Meliskey From Route | Query
     * @return mixed
     */
    private function getMelisKey()
    {
        return $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey'), null);
    }

    /**
     * Returns Tool Service
     * @return array|object
     */
    private function getTool()
    {
        $toolSvc = $this->getServiceLocator()->get('MelisCoreTool');
        $toolSvc->setMelisToolKey('meliscms', 'meliscms_tool_sites');

        return $toolSvc;
    }
}
