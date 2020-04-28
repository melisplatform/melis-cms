<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2017 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use MelisAssetManager\Service\MelisModulesService;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;
use MelisCms\Service\MelisCmsRightsService;

/**
 * This class renders Melis CMS Plugin Menu
 */
class FrontPluginsController extends AbstractActionController
{
    private $sectionHasNewPlugins = [];
    private $modulesHasNewPlugins = [];
    private $subsectionHasNewPlugins = [];

    public function renderPluginsMenuAction()
    {
        $config = $this->serviceLocator->get('config');
        $pluginsConfig = array();
        $siteModule = $this->params()->fromRoute('siteModule');
        // melis plugin service
        $pluginSvc = $this->getServiceLocator()->get('MelisCorePluginsService');
        // check for new plugins or manually installed and insert in db or fresh plugins
        $pluginSvc->checkTemplatingPlugins();
        $pluginList_ = $this->putSectionOnPlugins($config['plugins'], $siteModule);
        $newPluginList = $this->organizedPluginsBySection($pluginList_);
        // remove section that has no child under on it
        $newPluginList = array_filter($newPluginList);
        // add categories for the mini-templates
        $newPluginList = $this->categorizeMiniTemplates($siteModule, $newPluginList);

        // get the latest plugin installed
        $latesPlugin = $pluginSvc->getLatestPlugin($pluginSvc::TEMPLATING_PLUGIN_TYPE);
        // for new plugin notifications
        $pluginMenuHandler = $pluginSvc->getNewPluginMenuHandlerNotifDuration();

        $view = new ViewModel();
       // $view->pluginsConfig = $finalPluginList;
        $view->siteModule              = $siteModule;
        $view->newPluginList           = $newPluginList;
        $view->latestPlugin            = $latesPlugin;
        $view->sectionNewPlugins       = array_unique($this->sectionHasNewPlugins);
        $view->modulesHasNewPlugins    = array_unique($this->modulesHasNewPlugins);
        $view->subsectionHasNewPlugins = $this->subsectionHasNewPlugins;
        $view->newPluginNotification   = $pluginMenuHandler;

        return $view;
    }

    /**
     * This method will get the session
     * data of the current page
     */
    public function getSessionDataAction()
    {
        $success = 1;
        $data    = array();

        $translator = $this->getServiceLocator()->get('translator');
        $data       = "";

        return $data;
    }

    public function renderPluginModalAction()
    {
        $translator = $this->getServiceLocator()->get('translator');

        $parameters = $this->getRequest()->getQuery('parameters', array());

        $module = (!empty($parameters['module'])) ? $parameters['module'] : '';
        $pluginName = (!empty($parameters['pluginName'])) ? $parameters['pluginName'] : '';
        $pluginId = (!empty($parameters['pluginId'])) ? $parameters['pluginId'] : 1;
        $pageId = (!empty($parameters['melisActivePageId'])) ? $parameters['melisActivePageId'] : 1;
        $siteModule = (!empty($parameters['siteModule'])) ? $parameters['siteModule'] : '';
        $pluginHardcodedConfig = (!empty($parameters['pluginFrontConfig'])) ? $parameters['pluginFrontConfig'] : '';

        $pluginHardcodedConfig = html_entity_decode($pluginHardcodedConfig, ENT_QUOTES);
        $pluginHardcodedConfig = html_entity_decode($pluginHardcodedConfig, ENT_QUOTES);
        $pluginHardcodedConfig = unserialize($pluginHardcodedConfig);

        $errors = '';
        $tag = '';
        $tabs = array();
        $config = $this->getServiceLocator()->get('config');
        if (empty($module) || empty($pluginName) || empty($pageId) || empty($pluginId))
        {
            $errors = $translator->translate('tr_melisfront_generate_error_No module or plugin or idpage parameters');
        }
        else
        {
            if (empty($config['plugins'][$module]['plugins'][$pluginName]))
            {
                $errors = $translator->translate('tr_melisfront_generate_error_Plugin config not found');
            }
            else
            {
                $pluginConf = $config['plugins'][$module]['plugins'][$pluginName];

                try
                {
                    $pluginHardcodedConfig['id'] = $pluginId;
                    $pluginHardcodedConfig['pageId'] = $pageId;
                    $melisPlugin = $this->getServiceLocator()->get('ControllerPluginManager')->get($pluginName);
                    $melisPlugin->setUpdatesPluginConfig($pluginHardcodedConfig);
                    $melisPlugin->getPluginConfigs();
                    $tabs = $melisPlugin->createOptionsForms();
                    $tag = $melisPlugin->getPluginXmlDbKey();
                }
                catch (Exception $e)
                {
                    $errors = $translator->translate('tr_melisfront_generate_error_Plugin cant be created');
                }
            }
        }

        if ($errors != '' || count($tabs) == 0)
            $tabs[] = array('tabName' => 'Error', 'html' => $errors);

        $view = new ViewModel();
        $view->setTerminal(true);
        $view->tabs = $tabs;
        $view->idPage = $pageId;
        $view->module = $module;
        $view->pluginName = $pluginName;
        $view->pluginId = $pluginId;
        $view->tag = $tag;
        $view->pluginHardcodedConfig = $pluginHardcodedConfig;
        $view->siteModule = $siteModule;
        return $view;
    }


    public function validatePluginModalAction()
    {
        $translator = $this->getServiceLocator()->get('translator');

        $parameters = get_object_vars($this->getRequest()->getPost());

        $module = (!empty($parameters['melisModule'])) ? $parameters['melisModule'] : '';
        $pluginName = (!empty($parameters['melisPluginName'])) ? $parameters['melisPluginName'] : '';
        $pluginId = (!empty($parameters['melisPluginId'])) ? $parameters['melisPluginId'] : 1;
        $pageId = (!empty($parameters['melisIdPage'])) ? $parameters['melisIdPage'] : 1;

        $errors = '';
        $tag = '';
        $tabs = array();
        $config = $this->getServiceLocator()->get('config');
        if (empty($module) || empty($pluginName) || empty($pageId) || empty($pluginId))
        {
            $errors = $translator->translate('tr_melisfront_generate_error_No module or plugin or idpage parameters');
        }
        else
        {
            if (empty($config['plugins'][$module]['plugins'][$pluginName]))
            {
                $errors = $translator->translate('tr_melisfront_generate_error_Plugin config not found');
            }
            else
            {
                $pluginConf = $config['plugins'][$module]['plugins'][$pluginName];

                try
                {
                    $pluginHardcodedConfig['id'] = $pluginId;
                    $pluginHardcodedConfig['pageId'] = $pageId;
                    $melisPlugin = $this->getServiceLocator()->get('ControllerPluginManager')->get($pluginName);
                    $melisPlugin->setUpdatesPluginConfig($pluginHardcodedConfig);
                    $melisPlugin->getPluginConfigs();
                    $errorsTabs = $melisPlugin->createOptionsForms();
                }
                catch (Exception $e)
                {
                    $errors = $translator->translate('tr_melisfront_generate_error_Plugin cant be created');
                }
            }
        }

        $success = 1;
        $finalErrors = array();

        if ($errors != '')
        {
            $success = 0;
            $finalErrors = array('general' => $errors);
        }

        foreach($errorsTabs as $response) {
            if(!$response['success']) {
                $success = 0;
            }



        }
        $finalErrors = $errorsTabs;


        $result = array(
            'success' => $success,
            'errors' => $finalErrors,
        );

        return new JsonModel($result);
    }

    public function checkSessionPageAction()
    {
        $container = new Container('meliscms');
        $pages = $container->getArrayCopy();//$container['content-pages'];

//        \Zend\Debug\Debug::dump($pages);
        print_r($pages);

        die;
    }

    public function testingAreaAction()
    {
        $plugin = 'melisCmsSlider';
        $pluginId = 'showslider_1507005296';

        $text = '<melisCmsSlider id="showslider_1507009618" plugin_container_id="plugin_container_id_1507009628" plugin_container_id="plugin_container_id_1507009628" plugin_container_id="plugin_container_id_1507009628" plugin_container_id="plugin_container_id_1507009628" plugin_container_id="plugin_container_id_1507009628">	';
        $pattern = '/\splugin_container_id\=\"(.*?)\"/';

        $replace = $plugin . ' id="'.$pluginId.'" plugin_container_id="this_is_the_id_1823121"';

        $newValue = $text;
        if(preg_match($pattern, $text)) {
            $newValue = preg_replace($pattern, '', $text);
        }


        echo trim($newValue);


        die;
    }

    private function putSectionOnPlugins($configurations, $siteModule)
    {
        $pluginList = [];

        // get active modules in the platform
        $activeModules = include $_SERVER['DOCUMENT_ROOT'] . '/../config/melis.module.load.php';
        $activeModules = array_map('strtolower', $activeModules);
        // melis plugins configurations
        foreach ($configurations as $moduleName => $melisPluginsConfig) {
            // melis plugins configrations
            // this means the module has a templating plugins or plugins
            if (isset($melisPluginsConfig['plugins']) && ! empty($melisPluginsConfig['plugins'])) {
                if (in_array((strtolower($moduleName)),$activeModules) || $moduleName == "MelisMiniTemplate" ) {
                    foreach ($melisPluginsConfig['plugins'] as $pluginName =>  $pluginConfig) {
                        // list of excluded plugins
                        $excludedPlugins = [
                            'MelisFrontDragDropZonePlugin'
                        ];
                        if (!in_array($pluginName,$excludedPlugins)) {
                            // put site_module key under ['melis'] key
                            $pluginConfig['melis']['site_module'] = $siteModule;
                            // put section key  under['melis'] key
                            if ((! isset($pluginConfig['melis']['section']) || isset($pluginConfig['melis']['section'])) && empty($pluginConfig['melis']['section'])) {
                                // if there is no section key on [melis] config
                                // or there is a section key but empty
                                // we put it in the OTHER section directly
                                $pluginConfig['melis']['section'] = "Others";
                            }
                            // put a moduleName for easy sectioning of plugins
                            if (! isset($pluginConfig['melis']['moduleName']) && empty($pluginConfig['melis']['moduleName'])) {
                                $pluginConfig['melis']['moduleName'] = $moduleName;
                            }
                            $pluginList[$moduleName][$pluginName] = $pluginConfig;
                        }
                    }
                }
            }
        }

        return $pluginList;
    }
    private function organizedPluginsBySection($pluginList)
    {

        // get module categories
        /** @var MelisModulesService $moduleSvc */
        $moduleSvc = $this->getServiceLocator()->get('ModulesService');
        $configSvc = $this->getServiceLocator()->get('MelisCoreConfig');
        $engineComposer  = $this->getServiceLocator()->get('MelisEngineComposer');
        $melisPuginsSvc = $this->getServiceLocator()->get('MelisCorePluginsService');
        $marketPlaceModuleSection = $melisPuginsSvc->getPackagistCategories();
        $marketPlaceModuleSection = [];
        /*
         * In case there is no internet or cant connect to the markeplace domain
         * we put a predefined section just not destroy the plugins menu
         */
        if (empty($marketPlaceModuleSection)) {
            $fallbackSection = $configSvc->getItem('/meliscore/datas/fallBacksection');
            $marketPlaceModuleSection= $fallbackSection;
        }
        //custom sections
        $customSection = [
            'MelisCommerce', // special section
            'Others',
            'CustomProjects'
        ];
        // merge all sections
        $melisSection = array_merge($marketPlaceModuleSection, $customSection);
        // remove MelisCore section because there is no melis-core in front or templating plugins
        if (($key = array_search('MelisCore',$melisSection)) !== false) {
            unset($melisSection[$key]);
        }
        $newPluginList = [];
        // put the section in order
        if (! empty($melisSection)) {
            foreach ($melisSection as $idx => $val) {
                $newPluginList[$val] = [];
            }
        }
        if (! empty($pluginList)) {
            // get vendorModules
            $vendorModules = $engineComposer->getVendorModules();
            $vendorModules = array_unique(array_merge($moduleSvc->getActiveModules(),$vendorModules));

            // convert string to lower
            foreach ($vendorModules as $idx => $moduleName) {
                $vendorModules[$idx] = strtolower($moduleName);
            }

            /*
             * organized plugins with no subcategory
             */
            $publicModules = $melisPuginsSvc->getMelisPublicModules(true);
            foreach ($pluginList as $moduleName => $plugins) {
                // double check moduleName if it exisit on composer to avoid showing plugins that doesnt exists
                if (in_array(strtolower($moduleName),$vendorModules) || ($moduleName == "MelisMiniTemplate")) {
                   /*
                    * check first if the module is public or not
                    *  if public we will based the section on what is set from marketplace
                    */
                    $moduleSection = "";
                    if (array_key_exists($moduleName,$publicModules)) {
                        $moduleSection = $publicModules[$moduleName]['section'];
                    }

                    if (! empty($plugins)) {
                        foreach ($plugins as $pluginName => $pluginConfig) {
                            // put section for public module
                            if (! empty($moduleSection)) {
                                $pluginSection = $moduleSection;
                            } else {
                                // if it goes here means module is either private or there is no internet connection
                                $pluginSection = $pluginConfig['melis']['section'];

                            }
                            $module =  $moduleName ;
                            if (in_array($pluginSection,$melisSection)) {
                                // melis conifguration
                                $melisConfig = $pluginConfig['melis'];
                                if (isset($melisConfig['subcategory']) && ! empty($melisConfig['subcategory'])) {
                                    // this is for subsection
                                    $subsectionId = $melisConfig['subcategory']['id'] ?? null;
                                    $subsectionText = $melisConfig['subcategory']['title'] ?? null;
                                    $newPluginList[$pluginSection][$module]['hasSubsection'] = true;
                                    $newPluginList[$pluginSection][$module][$subsectionId][$pluginName] = $pluginConfig;
                                    // label of sub category
                                    $newPluginList[$pluginSection][$module][$subsectionId]['title'] = $subsectionText;
                                    // indication that the plugin is newly installed
                                    $isNew = $melisPuginsSvc->pluginIsNew($pluginName);
                                    $newPluginList[$pluginSection][$module][$subsectionId][$pluginName]['isNew'] = $isNew;
                                    if ($isNew) {
                                        $this->sectionHasNewPlugins[] = $pluginSection;
                                        $this->modulesHasNewPlugins[] = $module;
                                        $this->subsectionHasNewPlugins[] = $subsectionText;
                                    }
                                } else {
                                    // no subsection
                                    $newPluginList[$pluginSection][$module][$pluginName] = $pluginConfig;
                                    // indication that the plugin is newly installed
                                    $isNew = $melisPuginsSvc->pluginIsNew($pluginName);
                                    $newPluginList[$pluginSection][$module][$pluginName]['isNew'] = $isNew;
                                    if ($isNew) {
                                        $this->sectionHasNewPlugins[] = $pluginSection;
                                        $this->modulesHasNewPlugins[] = $module;
                                    }
                                }
                            } else {
                                /*
                                * if the section does not belong to the group it will go to the
                                * Others section direclty
                                */
                                $melisConfig = $pluginConfig['melis'];
                                if (isset($melisConfig['subcategory']) && ! empty($melisConfig['subcategory'])) {
                                    // this is for subsection
                                    $subsectionId = $melisConfig['subcategory']['id'] ?? null;
                                    $subsectionText = $melisConfig['subcategory']['title'] ?? null;
                                    $newPluginList['Others'][$module]['hasSubsection'] = true;
                                    $newPluginList['Others'][$module][$subsectionId][$pluginName] = $pluginConfig;
                                    // label of sub category
                                    $newPluginList['Others'][$module][$subsectionId]['title'] = $subsectionText;
                                    // indication that the plugin is newly installed
                                    $isNew = $melisPuginsSvc->pluginIsNew($pluginName);
                                    $newPluginList['Others'][$module][$subsectionId][$pluginName]['isNew'] = $isNew;
                                    if ($isNew) {
                                        $this->sectionHasNewPlugins[] = 'Others';
                                        $this->modulesHasNewPlugins[] = $module;
                                        $this->subsectionHasNewPlugins[] = $subsectionText;
                                    }
                                } else {
                                    $newPluginList['Others'][$module][$pluginName] = $pluginConfig;
                                    // indication that the plugin is newly installed
                                    $isNew = $melisPuginsSvc->pluginIsNew($pluginName);
                                    $newPluginList['Others'][$module][$pluginName]['isNew'] = $isNew;
                                    if ($isNew) {
                                        $this->sectionHasNewPlugins[] = 'Others';
                                        $this->modulesHasNewPlugins[] = $module;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $newPluginList;

    }

    /**
     * Categorizez the mini templates for each site
     * @param $site_module
     * @param $plugin_list
     * @return mixed
     */
    private function categorizeMiniTemplates($site_module, $plugin_list)
    {
        if (! empty($plugin_list['MelisCms']['MelisMiniTemplate']['miniTemplatePlugins_' . $site_module])) {
            $service = $this->getServiceLocator()->get('MelisCmsMiniTemplateService');
            $tree = $service->getTree($site_module, 'en_EN');

            $mini_templates = $plugin_list['MelisCms']['MelisMiniTemplate']['miniTemplatePlugins_' . $site_module];
            $plugin_list['MelisCms']['MelisMiniTemplate']['miniTemplatePlugins_' . $site_module] = [];
            $new_plugin_list = [];
            $new_plugin_list['title'] = $site_module;
            $in_active_categories = [];

            foreach ($tree as $key => $val) {
                $type = $val['type'];
                if ($type == 'category') {
                    if ($val['status']) {
                        $new_plugin_list[$val['text']] = [
                            'text' => $val['text'],
                            'isCategory' => true
                        ];
                    } else {
                        $in_active_categories[] = $val['text'];
                    }
                } else {
                    $exploded = explode('-', $val['parent']);
                    $parent = '';
                    if (count($exploded) > 2) {
                        unset($exploded[0]);
                        $parent = implode('-', $exploded);
                    } else if (count($exploded) == 2) {
                        unset($exploded[0]);
                        $parent = $exploded[1];
                    } else {
                        $parent = $val['parent'];
                    }

                    $title = 'MiniTemplatePlugin_' . $val['text'] . '_' . strtolower($site_module);
                    if ($val['parent'] != '#') {
                        if (!in_array($parent, $in_active_categories))
                            $new_plugin_list[$parent][$title] = $mini_templates[$title];
                    } else {
                        $new_plugin_list[$title] = $mini_templates[$title];
                    }
                }
            }

            $plugin_list['MelisCms']['MelisMiniTemplate']['miniTemplatePlugins_' . $site_module] = $new_plugin_list;
        }

        return $plugin_list;
    }
}
