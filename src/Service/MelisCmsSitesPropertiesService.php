<?php

namespace MelisCms\Service;

use Laminas\Config\Config;
use Laminas\Config\Writer\PhpArray;
use MelisCore\Service\MelisGeneralService;

class MelisCmsSitesPropertiesService extends MelisGeneralService
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
        $siteProp = [];
        $sitePropTable = $this->getServiceManager()->get('MelisEngineTableSite');
        $site404Table = $this->getServiceManager()->get('MelisEngineTableSite404');
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
        $langHompageTable = $this->getServiceManager()->get('MelisEngineTableCmsSiteHome');

        if (is_numeric($siteId)) {
            $langHompages = $langHompageTable->getEntryByField('shome_site_id', $siteId)->toArray();
        }

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
        $siteLangHomeTable = $this->getServiceManager()->get('MelisEngineTableCmsSiteHome');
        $siteLangHomeId = (isset($arrayParameters['data']['shome_id']) && $arrayParameters['data']['shome_id'] > 0) ? $arrayParameters['data']['shome_id'] : null;

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

    /**
     * save the 404 page id of each language of a specific site
     * @param array $data | data of the site domain of the page
     * @return int siteLang404Data | the id of the newly saved or updated site language 404 page
     */
    public function saveSiteLang404($data)
    {
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmssite_service_save_site_404_pages_start', $arrayParameters);

        // Service implementation start
        $siteLang404Table = $this->getServiceManager()->get('MelisEngineTableSite404');
        $siteLang404Id = (isset($arrayParameters['data']['s404_id']) && $arrayParameters['data']['s404_id'] > 0) ? $arrayParameters['data']['s404_id'] : null;

        $siteLang404Data = $siteLang404Table->save(
            [
                's404_site_id' => $arrayParameters['data']['s404_site_id'],
                's404_lang_id' => $arrayParameters['data']['s404_lang_id'],
                's404_page_id' => $arrayParameters['data']['s404_page_id']
            ],
            $siteLang404Id
        );
        // Service implementation end

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $siteLang404Data;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmssite_service_save_site_404_pages_end', $arrayParameters);

        return $arrayParameters['results'];
    }


     /**
     * Returns the 404 pages of each language of a specific site
     * @param $siteId param int $siteId | id of the site property to retrieve
     * @return array
     */
    public function getLang404pages($siteId)
    {
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmssite_service_get_site_property_start', $arrayParameters);

        // Service implementation start
        $lang404pageTable = $this->getServiceManager()->get('MelisEngineTableSite404');

        if (is_numeric($siteId)) {
            $lang404pages = $lang404pageTable->getEntryByField('s404_site_id', $siteId)->toArray();
        }

        // Service implementation end

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $lang404pages;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmssite_service_get_site_property_end', $arrayParameters);

        return $arrayParameters['results'];
    }
}