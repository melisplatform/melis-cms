<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use MelisCore\Controller\MelisAbstractActionController;

/**
 * Site Tool Plugin
 */
class SitesDomainsController extends MelisAbstractActionController
{
    const TOOL_INDEX = 'meliscms';
    const TOOL_KEY = 'meliscms_tool_sites';

    public function renderToolSitesDomainsAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();

        $rightService = $this->getServiceManager()->get('MelisCoreRights');
        $canAccess = $rightService->canAccess('meliscms_tool_sites_domains_content');

        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        $view->canAccess = $canAccess;

        return $view;
    }

    /**
     * @return void|ViewModel
     */
    public function renderToolSitesDomainsContentAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        /**
         * Make sure site id is not empty
         */
        if(empty($siteId))
            return;

        $melisKey = $this->getMelisKey();
        $siteDomainsSvc = $this->getServiceManager()->get("MelisCmsSitesDomainsService");
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);

        $domainsForm = $melisTool->getForm("meliscms_tool_sites_domain_form");
        $siteEnvs = $siteDomainsSvc->getEnvironments();
        $siteDomains = $siteDomainsSvc->getDomainsBySiteId($siteId);


        $view = new ViewModel();
        $view->siteEnvs = $siteEnvs;
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        $view->domainsForm = $domainsForm;
        $view->siteDomains = $siteDomains;
        return $view;
    }

    public function checkDomainAction()
    {
        $domain = $this->params()->fromPost('domain', null);
        $domainTable = $this->getServiceManager()->get('MelisEngineTableSiteDomain');
        $siteTable = $this->getServiceManager()->get('MelisEngineTableSite');
        $siteName = null;
        $result = [];

        if (!is_null($domain)) {
            if (is_array($domain)) {
                $domains = $domain;

                foreach ($domains as $key => $domain) {
                    $dom = $domainTable->getEntryByField('sdom_domain', $domain)->toArray();

                    if (!empty($dom)) {
                        $site = $siteTable->getEntryById($dom[0]['sdom_site_id'])->current();
                        if(!empty($site))
                            $result[$key] = $site->site_label;
                    }
                }
            } else {
                $dom = $domainTable->getEntryByField('sdom_domain', $domain)->toArray();

                if (!empty($dom)) {
                    $site = $siteTable->getEntryById($dom[0]['sdom_site_id'])->current();
                    if(!empty($site))
                        $result[] = $site->site_label;
                }
            }
        }

        return new JsonModel([
            'result' => $result
        ]);
    }

    private function getMelisKey()
    {
        $melisKey = $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey'), null);
        return $melisKey;
    }

    private function getTool()
    {
        $toolSvc = $this->getServiceManager()->get('MelisCoreTool');
        $toolSvc->setMelisToolKey('meliscms', 'meliscms_tool_sites');
        return $toolSvc;
    }
}
