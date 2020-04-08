<?php

namespace MelisCms\Service;

use Laminas\Config\Config;
use Laminas\Config\Writer\PhpArray;
use MelisCore\Service\MelisGeneralService;

class MelisCmsSitesDomainsService extends MelisGeneralService
{


    /**
     * Returns the domins of a specific site
     * @param $siteId param int $siteId | id of the site who owns the domains to take
     * @return array
     */
    public function getEnvironments()
    {

        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmssite_service_get_site_domain_environment_start', $arrayParameters);

        // Service implementation start

        $envTable = $this->getServiceManager()->get('MelisCoreTablePlatform');
        $envData = $envTable->fetchAll();
        $domainData = $envData->toArray();


        // Service implementation end

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $domainData;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmssite_service_get_site_domain_environment_end', $arrayParameters);

        return $arrayParameters['results'];
    }

    /**
     * Returns the domins of a specific site and environment
     * @param int $siteId | id of the site who owns the domains to take
     * @param string $env | environment of the domain to be retrieved
     * @return array
     */
    public function getDomainBySiteIdAndEnv($siteId, $env)
    {

        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmssite_service_get_site_domain_by_id_and_env_start', $arrayParameters);

        // Service implementation start

        $domainTable = $this->getServiceManager()->get('MelisEngineTableSiteDomain');
        $domainData = $domainTable->getDataBySiteIdAndEnv($siteId, $env);
        $domainData = $domainData->toArray();

        // Service implementation end

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $domainData;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmssite_service_get_site_domain_by_id_and_env_end', $arrayParameters);

        return $arrayParameters['results'];
    }

    /**
     * Returns the domains of a specific site
     * @param int $siteId | id of the site who owns the domains to take
     * @return array
     */
    public function getDomainsBySiteId($siteId)
    {

        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmssite_service_get_site_domain_by_site_id_start', $arrayParameters);

        // Service implementation start

        $domainTable = $this->getServiceManager()->get('MelisEngineTableSiteDomain');
        $domainData = $domainTable->getEntryByField("sdom_site_id", $siteId);
        $domainData = $domainData->toArray();

        // Service implementation end

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $domainData;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmssite_service_get_site_domain_by_site_id_end', $arrayParameters);

        return $arrayParameters['results'];
    }

    /**
     * save the domains of a specific site
     * @param array $data | data of the site domain of the page
     * @return int $domainData | the id of the newly saved or updated site domain
     */
    public function saveSiteDomain($data)
    {

        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmssite_service_save_site_domain_start', $arrayParameters);


        // Service implementation start
        $domainTable = $this->getServiceManager()->get('MelisEngineTableSiteDomain');
        $sdom_id = (isset($data["sdom_id"]) || $data["sdom_id"] > 0) ? $data["sdom_id"] : null;
        if(isset($data["sdom_id"]))
            unset($data["sdom_id"]);
        $domainData = $domainTable->save($data, $sdom_id);

        // Service implementation end

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $domainData;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmssite_service_save_site_domain_end', $arrayParameters);

        return $arrayParameters['results'];
    }


}