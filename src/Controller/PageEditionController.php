<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Laminas\EventManager\ResponseCollection;
use Laminas\Form\Factory;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use Laminas\Session\Container;
use MelisCore\Controller\MelisAbstractActionController;
use MelisCore\Service\MelisGeneralService;

/**
 * This class renders Melis CMS page tab edition
 */
class PageEditionController extends MelisAbstractActionController
{
	const MINI_TEMPLATES_FOLDER = 'miniTemplatesTinyMce';
    const TEMPLATE_FORM = 'meliscms/tools/meliscms_tool_templates/forms/meliscms_tool_template_generic_form';
    const TEMPLATE_FORM_CONFIG_MODIFY = 'meliscms_template_form_config';
	
	/**
	 * Makes the rendering of the Page Edition Tab
	 * @return \Laminas\View\Model\ViewModel
	 */
    public function renderPagetabEditionAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
    	$melisKey = $this->params()->fromRoute('melisKey', '');
    	
    	/**
         * Clearing the session data of the page in every open in page edition
         */
        $container = new Container('meliscms');
        if (!empty($container['content-pages']))
            if (!empty($container['content-pages'][$idPage]))
                $container['content-pages'][$idPage] = array();

        $melisCoreConf = $this->getServiceManager()->get('MelisConfig');
        $resizeConfig   = $melisCoreConf->getItem('meliscms/conf')['pluginResizable'] ?? null;

    	$melisPage = $this->getServiceManager()->get('MelisEnginePage');
    	$datasPage = $melisPage->getDatasPage($idPage, 'saved');


    	if($datasPage) {
    	    $datasPageTree = $datasPage->getMelisPageTree();
         	$datasTemplate = $datasPage->getMelisTemplate();
    	}
    	
    	$this->loadPageContentPluginsInSession($idPage);  
    	
    	$view = new ViewModel();
    	$view->idPage = $idPage;
    	$view->melisKey = $melisKey;
    	$view->resizablePlugin = $resizeConfig;

    	if (empty($datasPageTree->page_tpl_id) || $datasPageTree->page_tpl_id == -1)
    	    $view->noTemplate = true;
    	else
    	    $view->noTemplate = false;
    	
    	if(!empty($datasTemplate))
            $view->namespace = $datasTemplate->tpl_zf2_website_folder;
    	else 
    	    $view->namespace = '';

        /** Get the available templating modules */
        $activeTplModules = $this->getActiveTplModules();
        $view->hasTemplate = !empty($datasTemplate->tpl_type);   // Page has no template set
        $view->isTplModuleOK = empty($datasTemplate->tpl_type) ? false : in_array($datasTemplate->tpl_type, $activeTplModules);

    	return $view;
    }

    /**
     * Get all available templating module types
     * @return array
     */
    private function getActiveTplModules()
    {
        $activeTypes = $this->getTemplateForm();
        $activeTypes = empty($activeTypes->get('tpl_type')) ? [] : $activeTypes->get('tpl_type');
        $activeTypes = empty($activeTypes->getValueOptions()) ? [] : $activeTypes->getValueOptions();
        $activeTypes = empty($activeTypes) ? [] : array_keys($activeTypes);

        return $activeTypes;
    }

    /**
     * Template form creation
     * @return \Laminas\Form\ElementInterface
     */
    private function getTemplateForm()
    {
        $melisConfig = $this->getServiceManager()->get('MelisCoreConfig');
        $factory = new Factory();
        $formElementMgr = $this->getServiceManager()->get('FormElementManager');
        $factory->setFormElementManager($formElementMgr);
        $formConfig = $melisConfig->getItem(self::TEMPLATE_FORM);

        /**
         * Trigger listeners trying to modify the form config before form creation
         *
         *   - New Templating Engine? Register your template type using this event self::TEMPLATE_FORM_CONFIG_MODIFY

         * Sending event with string config position and config array retrieved as parameters
         *
         * @var MelisGeneralService $srv
         */
        $srv = $this->getServiceManager()->get('MelisGeneralService');
        $formConfig = $srv->sendEvent(self::TEMPLATE_FORM_CONFIG_MODIFY, ['formConfig' => $formConfig], $this)['formConfig'];

        return $factory->createForm($formConfig);
    }
    
    public function loadPageContentPluginsInSession($idPage)
    {
        // Create container if needed to save in session tags modified
        $container = new Container('meliscms');
        
        if (empty($container['content-pages']))
            $container['content-pages'] = array();
        if (empty($container['content-pages'][$idPage]))
            $container['content-pages'][$idPage] = array();
        else
            // We don't want to delete former values that can be in session
            // It might be useful to have them reloaded on opening of the page
            // In order to delete values in session, there is a "delete draft" button
            return; 

        $newcontentValuesArray = array();
        
        // Lets get the current content from database into an array
        $melisPageSavedTable = $this->getServiceManager()->get('MelisEngineTablePageSaved');
        $datas = $melisPageSavedTable->getEntryById($idPage);
        $datas = $datas->toArray();
        
        if (count($datas) == 0)
        {
            // No datas saved, then let's get the published datas
            $melisPagePublishedTable = $this->getServiceManager()->get('MelisEngineTablePagePublished');
            $datas = $melisPagePublishedTable->getEntryById($idPage);
            $datas = $datas->toArray();
        }
        
        if (count($datas) != 0)
        {
            $datas = $datas[0];
            $xml = simplexml_load_string($datas['page_content']);
            if ($xml)
            {
                foreach ($xml as $namePlugin => $valuePlugin)
                {
                    if (empty($newcontentValuesArray[$namePlugin]))
                        $newcontentValuesArray[$namePlugin] = array();
                         
                    $idPluginItem = (string)$valuePlugin->attributes()->id;
                     
                    $newcontentValuesArray[$namePlugin][$idPluginItem] = $valuePlugin->asXML();
                }
                
                $container['content-pages'][$idPage] = $newcontentValuesArray;
            }
        }
        
    }

    public function getContainerUniqueIdAction()
    {
        $success  = 1;
        $uniqueId = 'plugin_container_id_'.time();

        return new JsonModel(array(
            'success' => $success,
            'id'      => $uniqueId
        ));
    }
    
    /**
     * Saves datas edited in a page and posted to this function
     * Save is made in SESSION.
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function savePageSessionPluginAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $translator = $this->getServiceManager()->get('translator');
    
        $postValues = array();
        $request = $this->getRequest();
        if (!empty($idPage) && $request->isPost())
        {
            // Get values posted and set them in form
            $postValues = $request->getPost()->toArray();
            	
    
            // Send the event and let listeners do their job to catch and format their plugins values
            $eventDatas = array('idPage' => $idPage, 'postValues' => $postValues);
            $this->getEventManager()->trigger('meliscms_page_savesession_plugin_start', $this, $eventDatas);
             
            $result = array(
                'success' => 1,
                'errors' => array()
            );
        }
        else
        {
            $result = array(
                'success' => 0,
                'errors' => array(array('empty' => $translator->translate('tr_meliscms_form_common_errors_Empty datas')))
            );
        }
         
        return new JsonModel($result);
    }


    /**
     * Remove a specific plugin from a pageId
     * Save is made in SESSION.
     *
     * @return \Laminas\View\Model\JsonModel
     */
    public function removePageSessionPluginAction()
    {
        $module = $this->getRequest()->getQuery('module', null);
        $pluginName = $this->getRequest()->getQuery('pluginName', '');
        $pageId = $this->getRequest()->getQuery('pageId', null);
        $pluginId = $this->getRequest()->getQuery('pluginId', null);
        $pluginTag = $this->getRequest()->getQuery('pluginTag', null);
        
        $parameters = array(
            'module' => $module,
            'pluginName' => $pluginName,
            'pageId' => $pageId,
            'pluginId' => $pluginId,
            'pluginTag' => $pluginTag,
        );
        
        $translator = $this->getServiceManager()->get('translator');

        if (empty($module) || empty($pluginName) || empty($pageId) || empty($pluginId))
        {

            $result = array(
                'success' => 0,
                'errors' => array(array('empty' => $translator->translate('tr_meliscms_form_common_errors_Empty datas')))
            );
        }
        else
        {

            $this->getEventManager()->trigger('meliscms_page_removesession_plugin_start', null, $parameters);
            
            // removing plugin from session
            $container = new Container('meliscms');
            if (!empty($container['content-pages'][$pageId][$pluginTag][$pluginId]))
                unset($container['content-pages'][$pageId][$pluginTag][$pluginId]);
            
            $this->getEventManager()->trigger('meliscms_page_removesession_plugin_end', null, $parameters);
            
            $result = array(
                'success' => 1,
                'errors' => array()
            );
        }
         
        return new JsonModel($result);
    }

    /**
     * Returns edition log of page edition
     * @param pageId
     * return array
     */
    public function getEditionLogOfPageEditionById($pageId)
    {
        $data = array();

        $pageEdition = $this->getServiceManager()->get('MelisEnginePage');
        $dataLogs    = $pageEdition->getEditionLogsOfPage($pageId);

        $data = $dataLogs;

        return $data;
    }

	/**
	 * Save Page edition
	 * @return \Laminas\View\Model\JsonModel
	 */
	public function saveEditionAction()
	{
		$idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
		$translator = $this->getServiceManager()->get('translator');
		
		$eventDatas = array('idPage' => $idPage);
		$this->getEventManager()->trigger('meliscms_page_saveedition_start', null, $eventDatas);
		
		$container = new Container('meliscms');
		if (empty($idPage))
		{
			$result = array(
    				'success' => 0,
    				'errors' => array(array('empty' => $translator->translate('tr_meliscms_form_common_errors_Empty datas')))
    		);
		}
		else
		{
		    // Resave XML of the page
			if (!empty($container['content-pages'][$idPage]))
			{

				// Create the new XML
				$newXmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
				$newXmlContent .= '<document type="MelisCMS" author="MelisTechnology" version="2.0">' . "\n";
				foreach ($container['content-pages'][$idPage] as $namePlugin => $pluginEntries)
				{
                    if($namePlugin != 'private:melisPluginSettings') {
                        foreach ($pluginEntries as $idEntry => $valueEntry) {
                            $newXmlContent .= "\t" . $valueEntry . "\n";
                        }
                    }
				}
				$newXmlContent .= '</document>';
				
				// Save the result
                $melisPageSavedTable = $this->getServiceManager()->get('MelisEngineTablePageSaved');
				$melisPageSavedTable->save(array('page_content' => $newXmlContent), $idPage);
				
			}	

			$result = array(
					'success' => 1,
					'errors' => array(),
			);
		}

		$this->getEventManager()->trigger('meliscms_page_saveedition_end', null, $result);
		
		return new JsonModel($result);
	}
	
	/**
	 * This method sends back the list of mini-templates for TinyMCE
	 * It takes the page id as a parameter, determines the website folder
	 * in order to list only the mini-templates of the website and not
	 * all of them
	 * 
	 * @return \Laminas\View\Model\JsonModel
	 */
	public function getTinyTemplatesAction()
    {
		$idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
		$prefix = $this->params()->fromRoute('prefix', $this->params()->fromQuery('prefix', ''));
        // tinyMCE type
        $type   = $this->params()->fromRoute('type', $this->params()->fromQuery('type', ''));
		$success = 1;
		$tinyTemplates = array();

		// No pageId, return empty array 
		if (!empty($idPage))
		{
		    // Get datas from page
		    $melisPage = $this->getServiceManager()->get('MelisEnginePage');
		    $datasPage = $melisPage->getDatasPage($idPage, 'saved');
		    $siteId = $datasPage->getMelisTemplate()->tpl_site_id;

		    // No template, return empty array 
		    if (!empty($siteId))
		    {
                /**
                 * get mini templates baesd from mini template manager service
                 */
                $tinyTemplates = $this->getService('MelisCmsMiniTemplateGetterService')->getMiniTemplates($siteId, $prefix);
		    }
		}

		return new JsonModel($tinyTemplates);
    }

    /**
     * old method in getting the min templates
     */
    private function getMiniTemplates($idPage)
    {
        $tinyTemplates =[];
        // Get datas from page
        $melisPage = $this->getServiceManager()->get('MelisEnginePage');
        $datasPage = $melisPage->getDatasPage($idPage, 'saved');
        $datasTemplate = $datasPage->getMelisTemplate();

        // Get the path of mini-templates to this website
        $moduleName = $datasTemplate->tpl_zf2_website_folder;
        $publicPath = '/public/' . self::MINI_TEMPLATES_FOLDER;

        // Checking if the module path is vendor
        $composerSrv = $this->getServiceManager()->get('ModulesService');
        $path = $composerSrv->getComposerModulePath($moduleName);

        if (!empty($path)) {
            $folderSite = $path.$publicPath;
        }else{
            $folderSite = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites/' . $moduleName.$publicPath;
        }

        // List the mini-templates from the folder
        if (is_dir($folderSite))
        {
            if ($handle = opendir($folderSite))
            {
                while (false !== ($entry = readdir($handle)))
                {
                    if (is_dir($folderSite . '/' . $entry) || $entry == '.' || $entry == '..' || !$this->isImage($entry))
                        continue;
                    array_push($tinyTemplates,
                        array(
                            'title' => $entry,
                            'url' => "/" .  $datasTemplate->tpl_zf2_website_folder . '/' . 
                            self::MINI_TEMPLATES_FOLDER . '/' . $entry
                        ));
                }

                closedir($handle);
            }
        }

        return $tinyTemplates;
    }

    function isImage($fileName)
    {
        $image_ext = ['PNG', 'png', 'JPG', 'jpg', 'JPEG', 'jpeg'];
        foreach($image_ext as $ext){
            //if file is image, don't include it
            if(strpos($fileName, $ext) !== false) {
                return false;
            }
        }
        return true;
    }

    /**
     * get laminas service class
     */
    private function getService($serviceName)
    {
        return $this->getServiceManager()->get($serviceName);
    }

    /**
     * get tinyMCE configuration
     */
    private function getTinyMCEByType($type)
    {
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
            // for the melis_minitemplates configuration key
            if (isset($config['melis_minitemplates']) && $config['melis_minitemplates']) {
                // config
                $miniTmpConfig = $config['melis_minitemplates'];
                // prefix
                if (isset($miniTmpConfig['prefix'])) {
                    // set prefix
                    $prefix = $miniTmpConfig['prefix']; 
                }
            }
        }

        return $prefix;
    }
}

