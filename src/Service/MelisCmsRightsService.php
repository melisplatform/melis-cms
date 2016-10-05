<?php

namespace MelisCms\Service;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use MelisCore\Service\MelisCoreRightsServiceInterface;
use MelisCore\Service\MelisCoreRightsService;
use Zend\Json\Json;

class MelisCmsRightsService implements MelisCoreRightsServiceInterface, ServiceLocatorAwareInterface
{
	public $serviceLocator;
	
	const MELISCMS_PREFIX_PAGES = 'meliscms_pages';
	
	public function setServiceLocator(ServiceLocatorInterface $sl)
	{
		$this->serviceLocator = $sl;
		return $this;
	}
	
	public function getServiceLocator()
	{
		return $this->serviceLocator;
	}

	
	public function isAccessible($xmlRights, $sectionId, $itemId)
	{
		$rightsObj = simplexml_load_string($xmlRights);
		if (empty($rightsObj))
			return false;
		
		if ($sectionId == self::MELISCMS_PREFIX_PAGES)
		{
			// $itemId contains the pageId which is asked to be retrieved
			// Let's get the tree of pageId to this page and then look
			// if the page is allowed or parent allowed
    		$melisEngineTree = $this->serviceLocator->get('MelisEngineTree');
    		$breadcrumb = $melisEngineTree->getPageBreadcrumb($itemId, 0, true);
    		
    		if (empty($rightsObj->$sectionId))
    			return false;
    			
			foreach ($rightsObj->$sectionId->id as $pageId)
			{
				foreach ($breadcrumb as $parentPage)
					if ($pageId == -1 || $pageId == $parentPage->tree_page_id)
						return true;
			}
			
			return false;
		}
		
		return false;
	}
	
	public function getRightsValues($id, $isRole = false)
	{
		$translator = $this->serviceLocator->get('translator');
		$melisCoreUser = $this->getServiceLocator()->get('MelisCoreUser');
		if (!$isRole)
		{
			
			$xml = $melisCoreUser->getUserXmlRights($id);
		}
		else
		{
			$xml = '';
			$tableUserRole = $this->serviceLocator->get('MelisCoreTableUserRole');
			$datasRole = $tableUserRole->getEntryById($id);
			if ($datasRole)
			{
				$datasRole = $datasRole->current();
				if (!empty($datasRole))
					$xml = $datasRole->urole_rights;
			}
		}
		
		$selectedRootPages = $melisCoreUser->isItemRightChecked($xml, self::MELISCMS_PREFIX_PAGES, '-1');

		if ($isRole)
		    $idParam = '&roleId=' . $id;
		else
		    $idParam = '&userId=' . $id;
		
		$rightsItems = array(
			array(
				'key' => self::MELISCMS_PREFIX_PAGES . '_root',
				'title' => $translator->translate('tr_meliscms_rights_Pages'),
				'lazy' => true,
				'melisData' => array(
					'lazyURL' => '/melis/MelisCms/TreeSites/getTreePagesForRightsManagement?nodeId=' . self::MELISCMS_PREFIX_PAGES . '_root' . $idParam,
					'colorSelected' => '#99C975',
				),
				'selected' => $selectedRootPages,
				'iconTab' => '',
			)
		);
		return $rightsItems; 
	}
	
	private function extractPagesSeenNodes($fullNodesList)
	{
		$pagesSeen = array();
		foreach ($fullNodesList as $nodeId)
		{
			if (stripos($nodeId, self::MELISCMS_PREFIX_PAGES) === 0)
			{
				$idUnPrefixed = str_replace(self::MELISCMS_PREFIX_PAGES . '_', '', $nodeId);
				if ($idUnPrefixed == 'root')
					$idUnPrefixed = -1;
				$pagesSeen[] = $idUnPrefixed;
			}
		}
		
		return $pagesSeen;
	}
	
	private function addPageOrNot($pageId, $tabPagesAlreadyFound)
	{
		$melisTree = $this->getServiceLocator()->get('MelisEngineTree');
		
		$breacrumbPage = $melisTree->getPageBreadcrumb($pageId);
		$noAdd = false;
		foreach ($breacrumbPage as $pageB)
		{
			foreach ($tabPagesAlreadyFound as $pageIdAdded)
			{
				if ($pageIdAdded == -1 || $pageIdAdded == $pageB->tree_page_id)
				{
					// parent page already invloving this one
					$noAdd = true;
					break;
				}
			}
			if ($noAdd)
			{
				$noAdd = true;
				break;
			}
		}
		
		return $noAdd;
	}
	
	public function createXmlRightsValues($id, $datas, $isRole = false)
	{
		/**
		 * Treatment is different between user an role as pages are loaded in lazy mode
		 * Meaning not all pages are seen, meaning we have to copare what was checked with
		 * what was was existing in the previous XML.
		 * In consequence, we need to load the existing right XML from the user or from the role
		 */
		if (!$isRole)
		{
			$melisCoreUser = $this->getServiceLocator()->get('MelisCoreUser');
			$xml = $melisCoreUser->getUserXmlRights($id);
		}
		else
		{
			$xml = '';
			$tableUserRole = $this->serviceLocator->get('MelisCoreTableUserRole');
			$datasRole = $tableUserRole->getEntryById($id);
			if ($datasRole)
			{
				$datasRole = $datasRole->current();
				if (!empty($datasRole))
					$xml = $datasRole->urole_rights;
			}
		}
		
		$nodesSeen = Json::decode($datas['treeStatus']);
		$nodesSeen = $nodesSeen->treeStatus;
		$pagesSeen = $this->extractPagesSeenNodes($nodesSeen);
		$nodesPages = Json::decode($datas[self::MELISCMS_PREFIX_PAGES. '_root']);
		
		/**
		 * First, get the list of idPages, meaning:
		 * - getting the pages newly checked
		 * - adding to the list pages that could have been checked before but that
		 * are not in the list because the node was not deployed (lazy mode). These pages must
		 * be re-added or they will be gone.
		 */
		$pagestoAdd = array();
		$toolsPages  = self::MELISCMS_PREFIX_PAGES . '_root';
		if (!empty($nodesPages) && !empty($nodesPages->$toolsPages))
		{
			foreach ($nodesPages->$toolsPages as $idPage)
			{
				$idUnPrefixed = str_replace(self::MELISCMS_PREFIX_PAGES . '_', '', $idPage);
				if ($idUnPrefixed == 'root')
					$idUnPrefixed = -1;
				
				$noAdd = $this->addPageOrNot($idUnPrefixed, $pagestoAdd);
				
				if (!$noAdd)
					$pagestoAdd[] = $idUnPrefixed;
			}
			
		}
		
		if (!empty($xml))
		{
			$rightsObj = simplexml_load_string($xml);
			if (!empty($rightsObj))
			{
				$toolsPages  = self::MELISCMS_PREFIX_PAGES;
				if ($rightsObj->$toolsPages)
				{
					foreach ($rightsObj->$toolsPages->id as $oldPageId)
					{
						if (array_search((int)$oldPageId, $pagesSeen) === false)
						{
							// This node was not seen, it must be checked
							$noAdd = $this->addPageOrNot((int)$oldPageId, $pagestoAdd);
							if (!$noAdd)
								$pagestoAdd[] = (int)$oldPageId;
						}
					}
				}
			}
		}
		
		// Checking for exception root node -1
		foreach ($pagestoAdd as $pageId)
		{
			if ($pageId == -1)
			{
				$pagestoAdd = array(-1);
				break;
			}
		}
		
		// Creating interface xml
		$xmlRights = '<' . self::MELISCMS_PREFIX_PAGES . '>' . self::XML_ENDLINE;
		foreach ($pagestoAdd as $idPage)
			$xmlRights .= self::XML_SPACER . '<id>' . $idPage . '</id>' . self::XML_ENDLINE;
		$xmlRights .= '</' . self::MELISCMS_PREFIX_PAGES . '>' . self::XML_ENDLINE;
		
		return array('meliscms_rights' => $xmlRights);
	}
	
	/**
	 * This function returns whether or not a user has access to the "save", "delete", "publish", "unpublish"
	 * action buttons in the interface. Allowing to expand the rights to php saving functions, and updating
	 * treeview right menu
	 *
	 * @return int
	 */
	public function isActionButtonActive($actionwanted)
	{
		$active = 0;
		$pathArrayConfig = '';
		 
		switch ($actionwanted)
		{
			case 'save' : 		$pathArrayConfig = 'meliscms_page_action_save';
			break;
			case 'delete' : 	$pathArrayConfig = 'meliscms_page_action_delete';
			break;
			case 'publish' : 	$pathArrayConfig = 'meliscms_page_action_publishunpublish';
			break;
			case 'unpublish' : 	$pathArrayConfig = 'meliscms_page_action_publishunpublish';
			break;
		}
		
		$melisAppConfig = $this->getServiceLocator()->get('MelisCoreConfig');
		$melisKeys = $melisAppConfig->getMelisKeys();
		 
		$melisCoreAuth = $this->getServiceLocator()->get('MelisCoreAuth');
		$xmlRights = $melisCoreAuth->getAuthRights();

		$melisCoreRights = $this->getServiceLocator()->get('MelisCoreRights');
		
		if (!empty($melisKeys[$pathArrayConfig]))
		{
			$isAccessible = $melisCoreRights->isAccessible($xmlRights, MelisCoreRightsService::MELISCORE_PREFIX_INTERFACE, $melisKeys[$pathArrayConfig]);
			if ($isAccessible)
				$active = 1;
		}
		 
		return $active;
	}
}