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
use Zend\View\Model\JsonModel;

/**
 * Site Tool Plugin
 */
class SitesDomainsController extends AbstractActionController
{
    const TOOL_INDEX = 'meliscms';
    const TOOL_KEY = 'meliscms_tool_sites';

    public function renderToolSitesDomainsAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();

        $rightService = $this->getServiceLocator()->get('MelisCoreRights');
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
        $siteDomainsSvc = $this->getServiceLocator()->get("MelisCmsSitesDomainsService");
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
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
        $domainTable = $this->getServiceLocator()->get('MelisEngineTableSiteDomain');
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
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
        $toolSvc = $this->getServiceLocator()->get('MelisCoreTool');
        $toolSvc->setMelisToolKey('meliscms', 'meliscms_tool_sites');
        return $toolSvc;
    }
}
