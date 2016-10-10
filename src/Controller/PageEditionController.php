<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;

/**
 * This class renders Melis CMS page tab edition
 */
class PageEditionController extends AbstractActionController
{
	const MINI_TEMPLATES_FOLDER = 'miniTemplatesTinyMce';
	
	/**
	 * Makes the rendering of the Page Edition Tab
	 * @return \Zend\View\Model\ViewModel
	 */
    public function renderPagetabEditionAction()
    {
		$idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
    	$melisKey = $this->params()->fromRoute('melisKey', '');
    	
    	$melisPage = $this->getServiceLocator()->get('MelisEnginePage');
    	$datasPage = $melisPage->getDatasPage($idPage, 'saved');
    	if($datasPage)
    	{
    	    $datasPageTree = $datasPage->getMelisPageTree();
         	$datasTemplate = $datasPage->getMelisTemplate();
    	}
    	
    	$view = new ViewModel();
    	$view->idPage = $idPage;
    	$view->melisKey = $melisKey;
    	
    	if (empty($datasPageTree->page_tpl_id) || $datasPageTree->page_tpl_id == -1)
    	    $view->noTemplate = true;
    	else
    	    $view->noTemplate = false;
    	
    	if(!empty($datasTemplate))
            $view->namespace = $datasTemplate->tpl_zf2_website_folder;
    	else 
    	    $view->namespace = '';
    	
    	return $view;
    }
	
    /**
     * Saves datas edited in a page and posted to this function
     * Save is made in SESSION.
     * 
     * @return \Zend\View\Model\JsonModel
     */
    public function savePageTagSessionAction()
    {
    	$idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
		$translator = $this->serviceLocator->get('translator');
		
		$postValues = array();
    	$request = $this->getRequest();
    	if (!empty($idPage) && $request->isPost())
    	{
    		// Get values posted and set them in form
    		$postValues = get_object_vars($request->getPost());
			

			if(!empty($postValues['myArray']))
			{
				// Create container if needed to save in session tags modified
    			$container = new Container('meliscms');
    			if (empty($container['content-pages']))
    				$container['content-pages'] = array();
    			if (empty($container['content-pages'][$idPage]))
    				$container['content-pages'][$idPage] = array();
    			
    			// Get the value
    			for($i=0; $i <= (count($postValues['myArray'])/2);$i++){
					$container['content-pages'][$idPage][$postValues['myArray'][$i]['tagID']] = $postValues['myArray'][$i]['tagVal'];
				}
    			
				$result = array(
    					'success' => 1,
    					'errors' => array()
    			);				
				
			}
			else if (empty($postValues['tagId']))
    		{
    			$result = array(
    					'success' => 0,
    					'errors' => array(array('notagid' => $translator->translate('tr_meliscms_page_tab_edition_save_tag_Error while saving')))
    			);
    		} 
			else
			{
    			// Create container if needed to save in session tags modified
    			$container = new Container('meliscms');
    			if (empty($container['content-pages']))
    				$container['content-pages'] = array();
    			if (empty($container['content-pages'][$idPage]))
    				$container['content-pages'][$idPage] = array();
    			
    			// Get the value
    			$value = '';
    			if (!empty($postValues['tagValue']))
    				$value = $postValues['tagValue'];
    			
    			// Save in session
    			$container['content-pages'][$idPage][$postValues['tagId']] = $value;
    			
				$result = array(
    					'success' => 1,
    					'errors' => array()
    			);
    		}
    	}		
		else
    	{
    		$result = array(
    				'success' => 0,
    				'errors' => array(array('empty' => $translator->translate('tr_meliscms_form_common_errors_Empty datas')))
    		);
    	}
    	/* Saving elements to sessions */
		
		
    	return new JsonModel($result);
    }
    
	/**
	 * Save Page edition
	 * @return \Zend\View\Model\JsonModel
	 */
	public function saveEditionAction()
	{
		$idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
		$translator = $this->serviceLocator->get('translator');
		
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
			// If there's modifications, otherwise nothing to do
			if (!empty($container['content-pages'][$idPage]))
			{
				$melisPageSavedTable = $this->getServiceLocator()->get('MelisEngineTablePageSaved');
				$newTagsArray = array();
				
				// Lets get the current content from database into an array
				$dataSaved = $melisPageSavedTable->getEntryById($idPage);
				$dataSaved = $dataSaved->toArray();
				
				if (count($dataSaved) != 0)
				{
					$dataSaved = $dataSaved[0];
					$xml = simplexml_load_string($dataSaved['page_content']);
					if ($xml)
					{
						foreach ($xml->melisTag as $melisTag)
						{
							$id = (string)$melisTag->attributes()->id;
							$newTagsArray[$id] = (string)$melisTag;
						}
					}	
				}
				
				// Now lets merge it with the edited content
				$newTagsArray = array_merge($newTagsArray, $container['content-pages'][$idPage]);
				
				// Create the new XML
				$newXmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
				$newXmlContent .= '<document type="MelisCMS" author="MelisTechnology" version="2.0">' . "\n";
				foreach ($newTagsArray as $idTag => $valueTag)
				{
					$newXmlContent .= "\t" . '<melisTag id="' . $idTag . '"><![CDATA[' . $valueTag . ']]></melisTag>' . "\n";
				}
				$newXmlContent .= '</document>';
				
				// Save the result
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
	 * @return \Zend\View\Model\JsonModel
	 */
	public function getTinyTemplatesAction()
	{
		$idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
		$success = 1;
		$tinyTemplates = array();
		
		if (empty($idPage))
			$success = 0;
		else
		{
			// Get datas from page
			$melisPage = $this->getServiceLocator()->get('MelisEnginePage');
			$datasPage = $melisPage->getDatasPage($idPage, 'saved');
			$datasTemplate = $datasPage->getMelisTemplate();
			
			// No template, no success
			if (empty($datasTemplate))
				$success = 0;
			else
			{
				// Get the path of mini-templates to this website
				$folderSite = $datasTemplate->tpl_zf2_website_folder;
				$folderSite .= '/public/' . self::MINI_TEMPLATES_FOLDER;
				$folderSite = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites/' . $folderSite;
				
				// List the mini-templates from the folder
				if (is_dir($folderSite))
				{
					if ($handle = opendir($folderSite))
					{
						while (false !== ($entry = readdir($handle)))
						{
							if (is_dir($folderSite . '/' . $entry) || $entry == '.' || $entry == '..')
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
			}
		}
		
		// Send back results
		$result = array(
			'success' => $success,
			'templates' => $tinyTemplates
		);
		
		return new JsonModel($result);
	}
}

