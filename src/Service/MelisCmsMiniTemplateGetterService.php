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
    public function getMiniTemplates($siteId = null, $prefix = "", $locale = null, $treeStyle = false)
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

        $data = [];
        /**
         * get mini template tree based from mini template manager service
         */
        if (! empty($siteId)) {
            $data = $this->getService('MelisCmsMiniTemplateService')->getTree($siteId, $locale ?? $this->getLocale());
        } else {
            // get all sites
            $sites = $this->getService('MelisEngineTableSite')->fetchAll()->toArray();
            if (! empty($sites)) {
                foreach ($sites as $site) {
                    $data = array_merge($data, $this->getService('MelisCmsMiniTemplateService')->getTree($site['site_id'], $locale ?? $this->getLocale()));
                }
            }
        }
         
        /**
         * format to tree style
         */
        if ($treeStyle) {
            $data = $this->groupByParentCategory($data); 
        }

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
     * format category data to tree style
     */
    private function groupByParentCategory($data)
    {
        $newData = [];
        if (! empty($data)) {
            foreach($data as $i => $val) {

                /**
                 * put the plugin based from its parent
                 */
                if ($val['parent'] != "#") {
                    if (!empty($newData)) {

                        foreach ($newData as $idx => $val2) {
                            if ($val2['id'] == $val['parent']) {
                                $newData[$idx]['plugins'][] = $val;
                            }
                        }
                    }
                } else {
                    // add only root parents
                    $newData[] = $val;
                }
            } 
        }
        
        return $newData;
    }

    /**
     * add url key in the data for phtml file 
     */
    private function addPhtmlUrl($template)
    {
        return $this->getService('MelisCmsMiniTemplateService')->getSrcHtml($template['module'], $template['text']);
        //return DIRECTORY_SEPARATOR . $template['module'] . DIRECTORY_SEPARATOR . self::MINI_TEMPLATE_FOLDER . DIRECTORY_SEPARATOR . $template['text'] . '.phtml';
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

    /**
     * get tinyMCE configuration
     */
    public function getTinyMCEByType($type)
    {
        $config = [];
        // prefix
        $prefix = "";
        // tinymce config
        $configTinyMce = $this->getService('config')['tinyMCE'];
        // config url path
        $configDir = $configTinyMce[$type] ?? null;
        // Getting the module name
        $nameModuleTab = explode('/', $configDir);
        // get module name
        $nameModule = $nameModuleTab[0] ?? null;
        // Getting the path of the Module
        $path = $this->getService('ModulesService')->getModulePath($nameModule);
        // Generating the directory of the requested TinyMCE configuration
        $file  = $path . str_replace($nameModule, '', $configDir);
        if (file_exists($file)) {
            // include file
            $config = include($file);

            return $config;
        }

        return $config;
    }

}
