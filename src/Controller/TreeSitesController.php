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
use MelisCms\Service\MelisCmsRightsService;
use Zend\Http\Request;

/**
 * This class renders Melis CMS TreeView
 */
class TreeSitesController extends AbstractActionController
{
	/**
	 * Get the children of an idPage
	 * If called in http, will return an html view
	 * If called in xhr, will return a json view with the html and others informations
	 * like javascript datas.
	 *
	 * @return \Zend\View\Model\ViewModel|\Zend\View\Model\JsonModel
	 */
	public function getTreePagesByPageIdAction()
	{
		// Get the node id requested, or use default root -1
		$idPage = $this->params()->fromQuery('nodeId', -1);

		$this->getEventManager()->trigger('melis_cms_tree_get_pages_start', $this, array('idPage' => $idPage));

		if ($idPage == -1)
			$rootPages = $this->getRootForUser();
		else
			$rootPages = array($idPage);

		$final = $this->getPagesDatas($rootPages);

		$triggerResponse = $this->getEventManager()->trigger('melis_cms_tree_get_pages_end', $this, array('parentId' => $idPage, 'request' => $final));

		$response = null;
		if(isset($triggerResponse[0]) && !empty($triggerResponse[0]))
			$response = $this->formatTreeResponse($triggerResponse[0]);
		else
			$response = $this->formatTreeResponse($final);
			

		return $response;

	}
	
	public function checkUserPageTreeAccressAction()
	{
	    $idPage = $this->params()->fromQuery('idPage');
	    
	    $isAccessible = false;
	    
	    if ($idPage)
	    {
	        $melisCoreAuth = $this->getServiceLocator()->get('MelisCoreAuth');
	        $melisCmsRights = $this->getServiceLocator()->get('MelisCmsRights');
	        $xmlRights = $melisCoreAuth->getAuthRights();
	        $isAccessible = $melisCmsRights->isAccessible($xmlRights, MelisCmsRightsService::MELISCMS_PREFIX_PAGES, $idPage);
	    }
	    
	    $response = array(
	        'isAccessible' => $isAccessible
        );
	    
	    return new JsonModel($response);
	}

	/**
	 * Gets the root page when showing the tree of pages in rights tool
	 * so that all pages are displayed and not only the one you have access to
	 *
	 * @return Ambigous <\Zend\View\Model\ViewModel, \Zend\View\Model\JsonModel>
	 */
	public function getTreePagesForRightsManagementAction()
	{
		$idPage = $this->params()->fromQuery('nodeId', -1);

		if ($idPage == MelisCmsRightsService::MELISCMS_PREFIX_PAGES . '_root')
			$idPage = -1;
		else
			$idPage = str_replace(MelisCmsRightsService::MELISCMS_PREFIX_PAGES . '_', '', $idPage);

		$rootPages = array($idPage);
		$final = $this->getPagesDatas($rootPages);

		return $this->formatTreeResponse($final, true);
	}


	/**
	 * Get the pages' datas and format them to be sent back
	 *
	 * @param array $rootPages
	 * @return array
	 */
	private function getPagesDatas($rootPages)
	{
		$idPage = $this->params()->fromQuery('nodeId', -1);

		if ($idPage == MelisCmsRightsService::MELISCMS_PREFIX_PAGES . '_root')
			$idPage = -1;
		else
			$idPage = str_replace(MelisCmsRightsService::MELISCMS_PREFIX_PAGES . '_', '', $idPage);

		$pageMelisKey = 'meliscms_page';
		$melisAppConfig =$this->getServiceLocator()->get('MelisCoreConfig');
		$appsConfig = $melisAppConfig->getItem($pageMelisKey);

		if (!empty($appsConfig['conf']['id']))
			$zoneId = $appsConfig['conf']['id'];
		else
			$zoneId = 'id_meliscms_page';


		// Get the children
		$melisTree = $this->serviceLocator->get('MelisEngineTree');
		if (!empty($rootPages))
		{
			if ($rootPages[0] == -1 || $idPage != -1)
			{
				$children = $melisTree->getPageChildren($idPage);
				$children = $children->toArray();
			}
			else
			{
				$children = array();
				$tablePageTree = $this->getServiceLocator()->get('MelisEngineTablePageTree');
				foreach ($rootPages as $idPage)
				{
					$pageDetails = $tablePageTree->getFullDatasPage($idPage, '');
					$pageDetails = $pageDetails->toArray();
					$children = array_merge($children, $pageDetails);
				}
			}
		}
		else
		{
			return array();
		}


		// Making a new array with one unique page from published or save
		$final = array();
		foreach($children as $page)
		{
			$data_page_id = $page['tree_page_id'];

			/**
			 * Check which table must be used depending on if there's data
			 * in saved or published table.
			 * Saved results come withe same column name prefixed by s_
			 */
			if (!empty($page['s_page_id']))
			{
				$has_saved_version = 1;
				$prefixTable = 's_';
			}
			else
			{
				$has_saved_version = 0;
				$prefixTable = '';
			}

			// Prefix the id with the pageId
			$zoneIdTmp = $data_page_id . '_' . $zoneId;

			// Check if node has children
			$has_children = false;
			$childrenTmp = $melisTree->getPageChildren($data_page_id);
			if ($childrenTmp)
			{
				$childrenTmp = $childrenTmp->toArray();
				if (count($childrenTmp) > 0)
					$has_children = true;
			}

			// Check if the publish page is online to know for our result
			$is_online = 0;
			if (!empty($page['page_status']) && $page['page_status'] == 1)
				$is_online = 1;

			// Type of page is in table defined previously
			$data_page_type = $page[$prefixTable . 'page_type'];

			// We show always published name in tree, saved if no published
			if (!empty($page['page_name']))
				$data_page_name = $page['page_name'];
			else
			{
				if (!empty($page['s_page_name']))
					$data_page_name = $page['s_page_name'];
				else
					$data_page_name = $page['page_name'];
			}

			$data_icon = '';
			if ($data_page_type == 'PAGE')
				$data_icon = 'fa fa-file-o';
			if ($data_page_type == 'SITE')
				$data_icon = 'fa fa-home';
			if ($data_page_type == 'FOLDER')
				$data_icon = 'fa fa-folder-open-o';

			$newpage = array(
				// These datas are common to all treeviews
				'item_zoneid' => $zoneIdTmp,
				'item_melisKey' => $pageMelisKey,
				'item_name' => $data_page_id . ' - ' . $data_page_name,
				'item_is_on' => $is_online,
				'item_has_version' => $has_saved_version,
				'item_icon' => $data_icon,
				'item_has_children' => $has_children,
				'item_dragdrop' => $this->isAllowedDragDrop(),

				// These datas are added and will be sent back when calling the view from the melisKey
				'item_datas' => array(
					'page_title' => $data_page_name,
					'page_id' => $data_page_id,
					'page_has_saved_version' => $has_saved_version,
					'page_is_online' => $is_online,
					'page_type' => $data_page_type,
					'item_zoneid' => $zoneIdTmp,
					'item_melisKey' => $pageMelisKey,
				)
			);

			array_push($final, $newpage);
		}

		return $final;
	}

	/**
	 * Format the final response
	 *
	 * @param arry $final
	 * @param string $formatForRights
	 * @return \Zend\View\Model\ViewModel|\Zend\View\Model\JsonModel
	 */
	private function formatTreeResponse($final, $formatForRights = false)
	{
		// Check if we have an ajax request
		$isXmlHttpRequest = false;
		if ($this->getRequest()->isXmlHttpRequest())
			$isXmlHttpRequest = true;

		/**
		 * Switch the rendering strategy
		 */
		if (!$isXmlHttpRequest)
		{
			$melisKey = $this->params()->fromRoute('melisKey', '');

			$view = new ViewModel();
			$view->pages = $final;
			$view->melisKey = $melisKey;

			return $view;
		}
		else
		{

			$jsonresult = array();
			foreach ($final as $page)
			{
				if (!$formatForRights)
					$dragdrop = $page['item_dragdrop'];
				else
					$dragdrop = false;

				$idPage = (int) $page['item_datas']['page_id'];

				// this trigger allows to modify the title of the tree item
				$responseData = $this->getEventManager()->trigger('melis_cms_page_tree_items', $this, array_merge(array('idPage' => $idPage), $page));

				foreach($responseData as $response) {
					if($response) {
						// retrieve data from response
						$page = $response;
					}
				}

				$jsonpage = array(

					'folder' => false,
					'key' => $idPage,
					'lazy' => $page['item_has_children'],
					'title' => $page['item_name'],
					'iconTab' => $page['item_icon'],
					'addClass' => '',
					'melisData' => $page['item_datas'],
					'dragdrop' => $dragdrop

				);

				$melisCoreUser = $this->getServiceLocator()->get('MelisCoreUser');
				if ($formatForRights)
				{
					$userId = $this->params()->fromQuery('userId', -1);
					$roleId = $this->params()->fromQuery('roleId', -1);

					$userXml = '';
					if (!empty($roleId) && $roleId > 0)
					{
						$tableUserRole = $this->serviceLocator->get('MelisCoreTableUserRole');
						$datasRole = $tableUserRole->getEntryById($roleId);
						if ($datasRole)
						{
							$datasRole = $datasRole->current();
							if (!empty($datasRole))
								$userXml = $datasRole->urole_rights;
						}
						$idParam = '&roleId=' . $roleId;
					}
					else
					{
						$userXml = $melisCoreUser->getUserXmlRights($userId);
						$idParam = '&userId=' . $userId;
					}

					$selectedPage = $melisCoreUser->isItemRightChecked($userXml, MelisCmsRightsService::MELISCMS_PREFIX_PAGES, $page['item_datas']['page_id']);

					$jsonpage['key'] = MelisCmsRightsService::MELISCMS_PREFIX_PAGES . '_' . $jsonpage['key'];
					$jsonpage['selected'] = $selectedPage;
					$jsonpage['melisData']['lazyURL'] = '/melis/MelisCms/TreeSites/getTreePagesForRightsManagement?nodeId=' . $page['item_datas']['page_id'] . $idParam;
					$jsonpage['melisData']['colorSelected'] = '#99C975';
				}

				array_push($jsonresult, $jsonpage);
			}

			$jsonModel = new JsonModel();
			$jsonModel->setVariables($jsonresult);

			return $jsonModel;
		}
	}


	/**
	 * Creates a simple array with the page ids from
	 * the result of db
	 * @param array $breadcrumb
	 * @return array
	 */
	private function cleanBreadcrumb($breadcrumb)
	{
		$newArray = array();

		if (!empty($breadcrumb))
			foreach ($breadcrumb as $page)
			{
				if (!empty($page->tree_page_id))
					array_push($newArray, $page->tree_page_id);
			}

		return $newArray;
	}

	/**
	 * Gets the root pages for a user depending on his rights
	 * @return array
	 */
	private function getRootForUser()
	{
		$melisEngineTree = $this->serviceLocator->get('MelisEngineTree');
		$melisCoreAuth = $this->getServiceLocator()->get('MelisCoreAuth');
		$melisCmsRights = $this->getServiceLocator()->get('MelisCmsRights');

		// Get the rights of the user
		$xmlRights = $melisCoreAuth->getAuthRights();
		$rightsObj = simplexml_load_string($xmlRights);

		$rootPages = array();
		$breadcrumbRightPages = array();

		// Loop into page ids of the rights to determine what are the root pages
		// Deleting possible doublons with parent pages selected and also children pages

		$sectionId = MelisCmsRightsService::MELISCMS_PREFIX_PAGES;
		if (empty($rightsObj->$sectionId))
			return array();

		foreach ($rightsObj->$sectionId->id as $rightsPageId)
		{
			$rightsPageId = (int)$rightsPageId;

			// No need to continue, -1 is root, there's a full access
			if ($rightsPageId == -1)
				return array(-1);

			// Get the breadcrumb of the page and reformat it to a more simple array
			$breadcrumb = $melisEngineTree->getPageBreadcrumb($rightsPageId, 0, true);
			$breadcrumb = $this->cleanBreadcrumb($breadcrumb);


			/**
			 * Looping on the temporary array holding pages
			 * Making intersection to compare between the one checked and those already saved
			 * If the one checked is equal with the intersection, it means the one checked contains
			 * already the older one, the page is on top, then we will only keep this one and delete
			 * the old one
			 * Otherwise we save
			 */
			$add = true;
			for ($i = 0; $i < count($breadcrumbRightPages); $i++)
			{
				$result = array_intersect($breadcrumb, $breadcrumbRightPages[$i]);
				if ($result === $breadcrumb)
				{
					$add = false;
					$breadcrumbRightPages[$i] = $breadcrumb;
					break;
				}
				if ($result === $breadcrumbRightPages[$i])
				{
					$add = false;
					break;
				}
			}

			if ($add)
				$breadcrumbRightPages[count($breadcrumbRightPages)] = $breadcrumb;
		}

		/**
		 * Reformat final result to a simple array with the ids of pages
		 */
		foreach ($breadcrumbRightPages as $breadcrumbPage)
		{
			if (count($breadcrumbPage) > 0)
				array_push($rootPages, $breadcrumbPage[count($breadcrumbPage) - 1]);
		}

		return $rootPages;
	}
    
    /**
     * Determines if drag/drop is allowed in the treeview
     * If you can see the whole tree, then true
     * If you have access to only part of the tree, then false
     * 
     * @return boolean
     */
    private function isAllowedDragDrop()
    {
    	$melisCoreAuth = $this->getServiceLocator()->get('MelisCoreAuth');
    	 
    	// Get the rights of the user
    	$xmlRights = $melisCoreAuth->getAuthRights();
    	$rightsObj = simplexml_load_string($xmlRights);
    	 
    	$sectionId = MelisCmsRightsService::MELISCMS_PREFIX_PAGES;
    	foreach ($rightsObj->$sectionId->id as $rightsPageId)
    	{
    		if ($rightsPageId == -1)
    			return true;
    	}
    	
    	return false;
    }
    
    /**
     * Sends back the pageId breadcrumb
     * 
     * @return \Zend\View\Model\JsonModel
     */
    public function getPageIdBreadcrumbAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $idPage = ($idPage == 'root')? -1 : $idPage;
        $includeSelf = $this->params()->fromRoute('includeSelf', $this->params()->fromQuery('includeSelf', ''));
        
    	$melisEngineTree = $this->serviceLocator->get('MelisEngineTree');
		$breadcrumb = $melisEngineTree->getPageBreadcrumb($idPage, 0, true);
		
		$pageFatherId = $idPage;
		$jsonresult = array();
		
		if($includeSelf){
		    array_unshift($jsonresult, $pageFatherId);
		}
		
		while($pageFatherId != NULL){
		    
		    $page = $melisEngineTree->getPageFather($pageFatherId, 'saved')->current();
		    $pageFatherId = !empty($page)? $page->tree_father_page_id : NULL;
		    
		    if(!empty($pageFatherId)){
		        array_unshift($jsonresult, $pageFatherId);
		    }
		    
		}

		return new JsonModel($jsonresult);
	}
    
    /**
     * Sends back if a page can be edited by the user or not
     * 
     * @return \Zend\View\Model\JsonModel
     */
    public function canEditPagesAction()
    {
        $melisCoreAuth = $this->getServiceLocator()->get('MelisCoreAuth');
        $xmlRights = $melisCoreAuth->getAuthRights();
    	$rightsObj = simplexml_load_string($xmlRights);
        $sectionId = MelisCmsRightsService::MELISCMS_PREFIX_PAGES;
        if (empty($rightsObj->$sectionId->id))
            $edit = 0;
        else 
            $edit = 1;
            
        $result = array(
            'success' => 1,
            'edit' => $edit
        );
         
        return new JsonModel($result);
    }
    
    /**
     * The parent container of all modals, this is where you initialze your modal.
     * @return \Zend\View\Model\ViewModel
     */
    public function renderTreeSitesModalContainerAction()
    {
        $id = $this->params()->fromQuery('id');
        $view = new ViewModel();
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $view->melisKey = $melisKey;
        $view->id = $id;
        $view->setTerminal(true);
        return $view;
    }
    
    /**
     * Renders the form Tab and Content for the modal
     * @return \MelisCms\Controller\ViewModel
     */
    public function renderTreeSitesModalFormHandlerAction()
    {
        $data = array();
    
        $sourcePageId = (int) $this->params()->fromQuery('sourcePageId', '');
        
        $melisKey = $this->params()->fromRoute('melisKey', '');
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');
        $appConfigForm = $melisCoreConfig->getFormMergedAndOrdered('meliscms/tools/meliscms_tree_sites_tool/forms/meliscms_tree_sites_duplicate_tree_form','meliscms_tree_sites_duplicate_tree_form');
        $factory = new \Zend\Form\Factory();
        $formElements = $this->serviceLocator->get('FormElementManager');
        $factory->setFormElementManager($formElements);
        $form = $factory->createForm($appConfigForm);
        
        $data = array(
            'sourcePageId' => $sourcePageId  
        );
        
        $form->setData($data);
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->form = $form;
        $view->sourcePageId = $sourcePageId;
        return $view;
    }
    
    public function duplicateTreePageAction()
    {
        
        $response = array();
        $pageDupeResult = array();
        $success = 0;
        $errors  = array();
        $data = array();
        $translator = $this->serviceLocator->get('translator');
        $textMessage = $translator->translate('tr_meliscms_menu_dupe_fail');
        $textTitle = $translator->translate('tr_meliscms_menu_dupe');
        
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');
        $appConfigForm = $melisCoreConfig->getFormMergedAndOrdered('meliscms/tools/meliscms_tree_sites_tool/forms/meliscms_tree_sites_duplicate_tree_form','meliscms_tree_sites_duplicate_tree_form');
        $factory = new \Zend\Form\Factory();
        $formElements = $this->serviceLocator->get('FormElementManager');
        $factory->setFormElementManager($formElements);
        $form = $factory->createForm($appConfigForm);
        
        $postValues = get_object_vars($this->getRequest()->getPost());
        
        if($this->getRequest()->isPost()){
            $logTypeCode = 'CMS_DUPLICATE_TREE_PAGE';
            
            $this->getEventManager()->trigger('meliscms_tree_duplicate_page_trees_start', $this, array());
            
            $postValues = $melisTool->sanitizePost($postValues);
            $postValues['destinationPageId'] = ($postValues['use_root'])? -1 : $postValues['destinationPageId'];
            $form->setData($postValues);
            
            //validation
            if($form->isValid()) {
                
                $data = $form->getData();
                
                $pageTreeTable = $this->getServiceLocator()->get('MelisEngineTablePageTree');
                
                $page = $pageTreeTable->getEntryById($data['destinationPageId'])->toArray();
                
                $page = ($data['destinationPageId'] == -1)? true : $page;
               
                $sourcePage = $pageTreeTable->getFullDatasPage($data['sourcePageId'])->toArray();
                
                if(!empty($page) && !empty($sourcePage)){
                    
                    $pageDupeResult = $this->savePageTreeRecursiveAction($sourcePage[0], $data['destinationPageId'], $data['lang_id']);
                    
                    if(!empty($pageDupeResult['success'])){
                        $success = $pageDupeResult['success'];
                        $textMessage = $translator->translate('tr_meliscms_menu_dupe_success');
                    }
                    
                }else{
                    
                    // destination page not existing
                    if(empty($page)){
                        $errors = array(
                            'destinationPageId' => array(
                                'errorMessage' => $translator->translate('tr_meliscms_menu_dupe_destination_fail'),
                                'label' => $translator->translate('tr_meliscms_menu_dupe'),
                            ),
                        );
                    // source page not existing
                    }else{
                        $errors = array(
                            'sourcePageId' => array(
                                'errorMessage' => $translator->translate('tr_meliscms_menu_dupe_source_fail'),
                                'label' => $translator->translate('tr_meliscms_menu_dupe'),
                            ),
                        );
                    }
                }
                
            }else{
                
                $errors = $form->getMessages();
                
                foreach ($errors as $keyError => $valueError)
                {
                    foreach ($appConfigForm['elements'] as $keyForm => $valueForm)
                    {
                
                        if ($valueForm['spec']['name'] == $keyError &&
                            !empty($valueForm['spec']['options']['label']))
                            $errors[$keyError]['label'] = $valueForm['spec']['options']['label'];
                    }
                }
            }
            
            
        }
        
        $response = array(
            'success' => $success,
            'textTitle' => $textTitle,
            'textMessage' => $textMessage,
            'errors' => $errors,
            'chunk' => $data,
        );
        
        $response = !empty($pageDupeResult['errors'])? $pageDupeResult : $response;
        
        $this->getEventManager()->trigger('meliscms_tree_duplicate_page_trees_end', $this, array_merge($response, array('typeCode' => $logTypeCode, 'itemId' => $postValues['destinationPageId'])));
        return new JsonModel($response);
    }
    
    public function savePageTreeRecursiveAction($page, $fatherPageId, $langId)
    {
        $results = array();
        $data = array();
        $pageTreeTable = $this->getServiceLocator()->get('MelisEngineTablePageTree');
        $pagePublish = $this->getServiceLocator()->get('MelisEngineTablePagePublished');
        $tableLang = $this->getServiceLocator()->get('MelisCoreTableLang');
        $coalesceColumns = $pagePublish->getTableColumns();
        
        // re assign values if data are from save page
        foreach($coalesceColumns as $column){
            if(empty($page[$column])){
                if(!empty($page['s_'.$column])){
                    $page[$column] = $page['s_'.$column];
                }
            }
        }
        
        $langData = $tableLang->getEntryById($langId)->current();
        
        $langPrefix = !empty($langData)? strtolower(substr($langData->lang_locale, 0, 2)) : '';
        
        $page['plang_lang_id'] = $langId;
        $page['page_name'] = $page['page_name']. ' ('. $langPrefix .')';
        if(!empty($page['page_id'])){
            
           $data = $pageTreeTable->getPageTreeByFatherIdWithDetails($page['page_id'])->toArray();
           $parameters = $this->getRequest()->getPost();
           
           // set Post parameters
           foreach($page as $key => $val){
               $parameters->set($key, $val);
           }
           
           // trigger MelisCms\Controller\Page\savePageAction
           $results = $this->forward()->dispatch('MelisCms\Controller\Page', 
               array('action' => 'savePage', 'idPage' => 0, 'fatherPageId' => $fatherPageId)
           );
           
           $results = $results->getVariables();
           if(!empty($data) && !empty($results['datas'])){
               
               foreach ($data as $subPage){
                   
                   $pageDetails = $subPage;
                  
                   if(empty($subPage['page_id'])){
                       $pageDetails = $pageTreeTable->getFullDatasPage($subPage['tree_page_id'])->current()->getArrayCopy(); 
                   }
                   $this->savePageTreeRecursiveAction($pageDetails, $results['datas']['idPage'], $langId);
               }
           }
        }
        
        return $results;
    }
    
}
