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
    			
    			$jsonpage = array(
    	
    					'folder' => false,
    					'key' => $page['item_datas']['page_id'],
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
        
    	$melisEngineTree = $this->serviceLocator->get('MelisEngineTree');
		$breadcrumb = $melisEngineTree->getPageBreadcrumb($idPage, 0, true);
		
		$jsonresult = array();
		foreach ($breadcrumb as $page)
		{
			if (!empty($page->page_id))
				$tmpPageId = $page->page_id;
			else
				if (!empty($page->s_page_id))
					$tmpPageId = $page->s_page_id;
				else 
					$tmpPageId = null;
			
			if (empty($tmpPageId) || $tmpPageId == $idPage)
				continue;
			array_push($jsonresult, $tmpPageId);
		}
		
		$jsonModel = new JsonModel();
		$jsonModel->setVariables($jsonresult);
		
		return $jsonModel;
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
}
