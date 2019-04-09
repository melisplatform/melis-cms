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
        $dbConfigs = $this->getSiteConfigFromDb($siteId);
        $dbConfIds = $this->getDbConfigIds($dbConfigs);
        $this->kSortSiteConfig($config, $siteName, $siteId);
        $this->prepareDbConfigs($siteId, $siteName, $dbConfigs);
        $valuesFromDb = $this->getDbConfigKeys($siteId, $siteName, $dbConfigs, $config);

        $view = new ViewModel();

        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        $view->activeSiteLangs = $activeSiteLangs;
        $view->configForm = $configForm;
        $view->config = $config;
        $view->siteName = $siteName;
        $view->dbConfIds = $dbConfIds;
        $view->valuesFromDb = $valuesFromDb;

        return $view;
    }

    private function getDbConfigIds($dbConfig)
    {
        $dbConfIds = [];

        foreach ($dbConfig as $conf) {
            $dbConfIds[$conf['sconf_lang_id']] = $conf['sconf_id'];
        }

        return $dbConfIds;
    }

    private function getDbConfigKeys($siteId, $siteName, $dbConfigs, $config)
    {
        $valuesFromDb = [];

        foreach ($dbConfigs as $dbConfig) {
            if ($dbConfig['sconf_lang_id'] == '-1') {
                foreach ($dbConfig['sconf_datas']['site'][$siteName]['allSites'] as $key => $value) {
                    $valuesFromDb['allSites'][] = $key;
                }
            } else {
                foreach ($dbConfig['sconf_datas']['site'][$siteName][$siteId] as $langKey => $langValue) {
                    foreach ($langValue as $confKey => $confVal) {
                        $valuesFromDb[$langKey][] = $confKey;
                    }
                }
            }
        }

        return $valuesFromDb;
    }

    /**
     * Prepares the db config. unserialize array & form the complete config
     * @param $siteId
     * @param $siteName
     * @param $dbConfigs
     */
    private function prepareDbConfigs($siteId, $siteName, &$dbConfigs)
    {
        foreach ($dbConfigs as &$dbConfig) {
            if ($dbConfig['sconf_lang_id'] == '-1') {
                $dbConfig['sconf_datas'] = [
                    'site' => [
                        $siteName => unserialize($dbConfig['sconf_datas'])
                    ],
                ];
            } else {
                $dbConfig['sconf_datas'] = [
                    'site' => [
                        $siteName => [
                            $siteId => unserialize($dbConfig['sconf_datas'])
                        ],
                    ],
                ];
            }
        }
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

        return $siteConfigSrv->getSiteConfig($siteId, true);
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
