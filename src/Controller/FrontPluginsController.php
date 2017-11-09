<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2017 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

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
    public function renderPluginsMenuAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        
        $config = $this->serviceLocator->get('config');
        $pluginsConfig = array();
        
        $siteModule = $this->params()->fromRoute('siteModule');
        
        foreach ($config['plugins'] as $moduleName => $conf)
        {
            if ($moduleName !== 'meliscommerce') // TEMPORARY: DIACTIVATING COMMERCE PLUGINS
            {
                if (!empty($conf['plugins']))
                {
                    foreach ($conf['plugins'] as $pluginName => $pluginConf)
                    {
                        if ($pluginName == 'MelisFrontDragDropZonePlugin')
                            continue; // Exception, dragdropplugin can't be found in menu...
                            
                        if (empty($pluginsConfig[$moduleName]))
                            $pluginsConfig[$moduleName] = array();
                        if (empty($pluginsConfig[$moduleName][$pluginName]))
                            $pluginsConfig[$moduleName][$pluginName] = array();
                            
                        foreach ($pluginConf['melis'] as $key => $value)
                        {
                            if (!is_array($value) && substr($value, 0, 3) == 'tr_')
                            {
                                $pluginConf['melis'][$key] = $translator->translate($value);
                            }
                        }
                        if (!empty($pluginConf['melis']['subcategory']))
                        {
                            foreach ($pluginConf['melis']['subcategory'] as $key => $value)
                            {
                                if (!is_array($value) && substr($value, 0, 3) == 'tr_')
                                {
                                    $pluginConf['melis']['subcategory'][$key] = $translator->translate($value);
                                }
                            }
                        }
                            
                        if (!empty($pluginConf['melis']['subcategory']) && !empty($pluginConf['melis']['subcategory']['id']))
                        {
                            $subcategoryId = $pluginConf['melis']['subcategory']['id'];
                            if (empty($pluginsConfig[$moduleName][$subcategoryId]))
                                $pluginsConfig[$moduleName][$subcategoryId] = array();
                                
                                $pluginsConfig[$moduleName][$subcategoryId][$pluginName] = $pluginConf;
                                unset($pluginsConfig[$moduleName][$pluginName]);
                        }
                        else
                            $pluginsConfig[$moduleName][$pluginName] = $pluginConf;
                    }
                }
            }
        }
        
        // reorganizing for subcategories
        $finalPluginList = array();
        foreach ($pluginsConfig as $moduleName => $pluginList)
        {
            
            foreach ($pluginList as $pluginName => $pluginConf)
            {
                if (empty($finalPluginList[$moduleName]))
                    $finalPluginList[$moduleName] = array('subcategories' => array(), 'plugins' => array());
                if (empty($pluginConf['front']))
                {
                    if (empty($finalPluginList[$moduleName]['subcategories'][$pluginName]))
                        $finalPluginList[$moduleName]['subcategories'][$pluginName] = array('name' => '', 'plugins' => array());
                        
                    $nameCategory = '';
                    foreach ($pluginConf as $codePlugin => $plugin)
                    {
                        if (!empty($plugin['melis']['subcategory']['title']))
                        {
                            $nameCategory = $plugin['melis']['subcategory']['title'];
                            break;
                        }
                    }
                    
                    $finalPluginList[$moduleName]['subcategories'][$pluginName]['name'] = $nameCategory;
                    $finalPluginList[$moduleName]['subcategories'][$pluginName]['plugins'] = $pluginConf;
                }
                else
                {
                    $finalPluginList[$moduleName]['plugins'][$pluginName] = $pluginConf;
                }
            }
        }
        
        $view = new ViewModel();
        $view->pluginsConfig = $finalPluginList;
        $view->siteModule = $siteModule;
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

}
