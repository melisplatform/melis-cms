<?php

namespace MelisCms\Service;

use MelisCore\Service\MelisCoreGeneralService;
use Zend\Config\Config;
use Zend\Config\Writer\PhpArray;

class MelisCmsSitesPropertiesService extends MelisCoreGeneralService
{


    /**
     * Returns the site property of a specific site
     * @param $siteId param int $siteId | id of the site property to retrieve
     * @return array
     */
    public function getSitePropAnd404BySiteId($siteId)
    {
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmssite_service_get_site_property_start', $arrayParameters);

        // Service implementation start
        $siteProp = array();
        $sitePropTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        $site404Table = $this->getServiceLocator()->get('MelisEngineTableSite404');
        if(is_numeric($siteId)) {
            $siteProp = $sitePropTable->getEntryById($siteId)->current();
            $site404 = $site404Table->getEntryByField("s404_site_id",$siteId)->current();
            if(isset($site404->s404_page_id))
                $siteProp->{"s404_page_id"} = $site404->s404_page_id;

        }
        // Service implementation end

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $siteProp;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmssite_service_get_site_property_end', $arrayParameters);

        return $arrayParameters['results'];
    }


    /**
     * Returns the site language homepages of a specific site
     * @param $siteId param int $siteId | id of the site property to retrieve
     * @return array
     */
    public function getLangHomepages($siteId)
    {
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmssite_service_get_site_property_start', $arrayParameters);

        // Service implementation start
        $langHompageTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteHome');

        if (is_numeric($siteId)) {
            $langHompages = $langHompageTable->getEntryByField('shome_site_id', $siteId)->toArray();
        }

        print_r($langHompages);
        exit;
        // Service implementation end

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $langHompages;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmssite_service_get_site_property_end', $arrayParameters);

        return $arrayParameters['results'];
    }


    /**
     * save the domains of a specific site
     * @param array $data | data of the site domain of the page
     * @return int SiteLangHome | the id of the newly saved or updated site language homepage
     */
    public function saveSiteLangHome($data)
    {
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmssite_service_save_site_home_pages_start', $arrayParameters);

        // Service implementation start
        $siteLangHomeTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteHome');
        $siteLangHomeId = (isset($arrayParameters['data']['shome_id']) || $arrayParameters['data']['shome_id'] > 0) ? $arrayParameters['data']['shome_id'] : null;

        $siteLangHomeData = $siteLangHomeTable->save(
            [
                'shome_site_id' => $arrayParameters['data']['shome_site_id'],
                'shome_lang_id' => $arrayParameters['data']['shome_lang_id'],
                'shome_page_id' => $arrayParameters['data']['shome_page_id']
            ],
            $siteLangHomeId
        );
        // Service implementation end

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $siteLangHomeData;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmssite_service_save_site_home_pages_end', $arrayParameters);

        return $arrayParameters['results'];
    }
}