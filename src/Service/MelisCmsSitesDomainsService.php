<?php

namespace MelisCms\Service;

use MelisCore\Service\MelisCoreGeneralService;
use Zend\Config\Config;
use Zend\Config\Writer\PhpArray;

class MelisCmsSitesDomainsService extends MelisCoreGeneralService
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
        $arrayParameters = $this->sendEvent('meliscmssite_service_module_load_start', $arrayParameters);

        // Service implementation start

        $envTable = $this->getServiceLocator()->get('MelisCoreTablePlatform');
        $envData = $envTable->fetchAll();
        $domainData = $envData->toArray();


        // Service implementation end

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $domainData;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmssite_service_module_load_end', $arrayParameters);

        return $arrayParameters['results'];
    }

    /**
     * Returns the domins of a specific site
     * @param int $siteId | id of the site who owns the domains to take
     * @param string $env | environment of the domain to be retrieved
     * @return array
     */
    public function getDomainBySiteIdAndEnv($siteId, $env)
    {

        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmssite_service_module_load_start', $arrayParameters);

        // Service implementation start

        $domainTable = $this->getServiceLocator()->get('MelisEngineTableSiteDomain');
        $domainData = $domainTable->getDataBySiteIdAndEnv($siteId, $env);
        $domainData = $domainData->toArray();

        // Service implementation end

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $domainData;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmssite_service_module_load_end', $arrayParameters);

        return $arrayParameters['results'];
    }


}