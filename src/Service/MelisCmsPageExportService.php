<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service;

use MelisCore\Service\MelisCoreGeneralService;

class MelisCmsPageExportService extends MelisCoreGeneralService
{
    public function exportPageTree($pageId, $includeSubPages = true, $exportPageResources = false)
    {
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        // Sending service start event
        $arrayParameters = $this->sendEvent('melis_cms_page_tree_export_start', $arrayParameters);

        $db = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');//get db adapter
        $con = $db->getDriver()->getConnection();//get db driver connection
        $con->beginTransaction();//begin transaction
        try {

            $con->commit();
        } catch (\Exception $ex) {
            $con->rollback();
            $arrayParameters['results'] = false;
        }

        $arrayParameters = $this->sendEvent('melis_cms_page_tree_export_end', $arrayParameters);
        return $arrayParameters['results'];
    }

    public function exportPage()
    {
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        // Sending service start event
        $arrayParameters = $this->sendEvent('melis_cms_page_export_start', $arrayParameters);



        $arrayParameters = $this->sendEvent('melis_cms_page_export_end', $arrayParameters);
        return $arrayParameters['results'];
    }
}
