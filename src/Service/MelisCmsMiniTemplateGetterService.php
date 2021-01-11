<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Service;

use MelisCore\Service\MelisGeneralService;
use Laminas\Session\Container;

class MelisCmsMiniTemplateGetterService extends MelisGeneralService 
{

    /**
     * mini template type
     */
    const MINI_TEMPLATE = "mini-template";

    /**
     * mini template category type
     */
    const MINI_TEMPLATE_CATEGORY = "category";

    /**
     * mini template folder
     */
    const MINI_TEMPLATE_FOLDER = 'miniTemplatesTinyMce';

    /**
     * get mini templates by site id with prefix
     * 
     * @param $siteId
     * @param prefix = null
     *
     * @return array
     */
    public function getMiniTemplates($siteId, $prefix = "")
    {
         // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        // Sending service start event
        $arrayParameters = $this->sendEvent('melis_cms_mini_template_getter_get_mini_templates_start', $arrayParameters);
        // results
        $arrayParameters['results'] = [];
        // get the siteId
        $siteId = $arrayParameters['siteId'];
        // prefix
        $prefix = $arrayParameters['prefix'];

        /**
         * get mini template tree
         */
        $data = $this->getService('MelisCmsMiniTemplateService')->getTree($siteId, $this->getLocale());
         
        // get template files by prefix
        if (! empty($prefix)) {
            // set new data
            $data = $this->getTemplatesByPrefix($data, $prefix);
        }

        // add url of phtml file and set as the results
        $arrayParameters['results'] = $this->addPhtmlUrl($data);

        // Sending service end event
        $arrayParameters = $this->sendEvent('melis_cms_mini_template_getter_get_mini_templates_end', $arrayParameters);

        return $arrayParameters['results'];
    }

    private function getTemplatesByPrefix($data, $prefix)
    {
        return $data;
    }

    /**
     * add url key in the data for phtml file 
     */
    private function addPhtmlUrl($data)
    {
        if (! empty($data)) {
            foreach ($data as $i => $val) {
                // check if data is mini template
                if ($val['type'] == self::MINI_TEMPLATE) {
                    // add url key for the phtml file
                    $data[$i]['url'] = DIRECTORY_SEPARATOR . $val['module'] . DIRECTORY_SEPARATOR . self::MINI_TEMPLATE_FOLDER . DIRECTORY_SEPARATOR . $val['text'] . '.phtml';
                }
            }
        }

        return $data;
    }

    /**
     * get laminas service class
     */
    private function getService($serviceName)
    {
        return $this->getServiceManager()->get($serviceName);
    }

    /**
     * Get the locale used from meliscore session
     */
    private function getLocale()
    {
        return (new Container('meliscore'))['melis-lang-locale'];
    }

}
