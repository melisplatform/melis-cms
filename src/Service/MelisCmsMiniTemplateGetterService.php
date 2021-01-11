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
    public function getMiniTemplates($siteId, $prefix = "", $locale = null)
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
         * get mini template tree based from mini template manager service
         */
        $data = $this->getService('MelisCmsMiniTemplateService')->getTree($siteId, $locale ?? $this->getLocale());
         
        // check data if not empty
        if (! empty($data)) {
            $tmp = [];
            foreach ($data as $i => $val) {
                // add url only for mini templates
                if ($val['type'] == self::MINI_TEMPLATE) {
                    // check for prefix
                    if ($prefix) {
                        // add url for phtml file
                        $val['url'] = $this->addPhtmlUrl($val);
                        // get only templates with the given prefix
                        if (preg_match('/^' . $prefix . '/', $val['text'])) {
                            $tmp[] = $val;
                        }
                    } else {
                        // add url key
                        $data[$i]['url'] = $this->addPhtmlUrl($val);
                    }
                } else {
                    // add value to the new array 
                    if ($prefix) {
                        $tmp[] = $val;
                    }
                }
            }

            // override data if there is a prefix
            if (! empty($prefix)) {
                // set new data
                $data = $tmp;
            }
        } 

        // add url of phtml file and set as the results
        $arrayParameters['results'] = $data;

        // Sending service end event
        $arrayParameters = $this->sendEvent('melis_cms_mini_template_getter_get_mini_templates_end', $arrayParameters);

        return $arrayParameters['results'];
    }

    /**
     * add url key in the data for phtml file 
     */
    private function addPhtmlUrl($template)
    {
        return DIRECTORY_SEPARATOR . $template['module'] . DIRECTORY_SEPARATOR . self::MINI_TEMPLATE_FOLDER . DIRECTORY_SEPARATOR . $template['text'] . '.phtml';
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
