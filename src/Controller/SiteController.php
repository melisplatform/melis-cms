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
use MelisCore\Service\MelisCoreRightsService;
use Zend\Config\Reader\Json;

/**
 * Site Tool Plugin
 */
class SiteController extends AbstractActionController
{
    const TOOL_INDEX = 'meliscms';
    const TOOL_KEY = 'meliscms_tool_site';
    
    const SITE_TABLE_PREFIX = 'ssite_';
    const DOMAIN_TABLE_PREFIX = 'sdom_';
    const SITE404_TABLE_PREFIX = 's404_';

    /**
     * Main container of the tool, this holds all the components of the tools
     * @return ViewModel();
     */
    public function renderToolSiteAction() {
        
        $translator = $this->getServiceLocator()->get('translator');
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $noAccessPrompt = '';
        
        if(!$this->hasAccess(self::TOOL_KEY)) {
            $noAccessPrompt = $translator->translate('tr_tool_no_access');
        }

        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        
        $view = new ViewModel();
        
        $view->melisKey = $melisKey;
        $view->title = $melisTool->getTitle();;
        $view->noAccess  = $noAccessPrompt;
        
        return $view;
    }
    
    /**
     * Renders to the header section of the tool
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSiteHeaderAction() {
        
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        
        $view = new ViewModel();
        $view->title = $melisTool->getTitle();
        
        return $view;
    }
    
    /**
     * Renders to the center content of the tool
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSiteContentAction() 
    {
        $translator = $this->getServiceLocator()->get('translator');
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        
        $columns = $melisTool->getColumns();
        // pre-add Action Columns
        $columns['actions'] = array('text' => $translator->translate('tr_meliscms_action'));
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->tableColumns = $columns;
        $view->getToolDataTableConfig = $melisTool->getDataTableConfiguration();
        
        return $view;
    }
    
    /**
     * Renders to the refresh button in the table filter bar
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSiteContentFilterRefreshAction()
    {
        return new ViewModel();
    }
    
    /**
     * Renders to the Search input in the table filter bar
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSiteContentFilterSearchAction()
    {
        return new ViewModel();
    }
    
    /**
     * Renders to the limit selection in the table filter bar
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSiteContentFilterLimitAction()
    {
        return new ViewModel();
    }
    
    public function renderToolSiteHeaderAddAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        $view = new ViewModel();
        
        return $view;
    }
    
    
    /**
     * This is the container of the modal
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSiteModalContainerAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->emptyModal = $melisTool->getModal('meliscms_tool_site_modal_empty_handler');
        
        return $view;
    }
    
    /**
     * Renders to the empty modal display, this will be displayed if the user doesn't have access to the modal tabs
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSiteModalEmptyHandlerAction()
    {
        return new ViewModel();
    }
    
    /**
     * This handler will be used whenever if the add tab should be displayed or not
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSiteModalAddHandlerAction() 
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        
        $view = new ViewModel();
        $view->addModalHandler = $melisTool->getModal('meliscms_tool_site_modal_add');
        $view->melisKey = $melisKey;
        
        return $view;
    }
    
    /**
     * Displays the add form in the modal
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSiteModalAddAction() 
    {
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
    
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
    
        $view = new ViewModel();
    
        $view->setVariable('meliscms_site_tool_generic_form', $melisTool->getForm('meliscms_site_tool_generic_form'));
    
        return $view;
    }
    
    public function renderToolSiteModalEditHandlerAction() 
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
    
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
    
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
    
        $view = new ViewModel();
        $view->editModalHandler = $melisTool->getModal('meliscms_tool_site_modal_edit');
        $view->melisKey = $melisKey;
    
        return $view;
    }
    
    public function renderToolSiteModalEditAction() 
    {
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
    
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
    
        $view = new ViewModel();
    
        $view->setVariable('meliscms_site_tool_generic_form', $melisTool->getForm('meliscms_site_tool_generic_form'));
    
        return $view;
    }
    
    /**
     * Renders to the edit button in the table
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSiteContentActionEditAction()
    {
        return new ViewModel();
    }
    
    /**
     * Renders to the delete button in the table
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSiteContentActionDeleteAction()
    {
        return new ViewModel();
    }
    
    /**
     * Checks whether the user has access to this tools or not
     * @return boolean
     */
    private function hasAccess($key)
    {
        $melisCoreAuth = $this->getServiceLocator()->get('MelisCoreAuth');
        $melisCoreRights = $this->getServiceLocator()->get('MelisCoreRights');
        $xmlRights = $melisCoreAuth->getAuthRights();
    
        $isAccessible = $melisCoreRights->isAccessible($xmlRights, MelisCoreRightsService::MELISCORE_PREFIX_TOOLS, $key);
    
        return $isAccessible;
    }
    
    /**
     * Returns all the data from the site table, site domain and site 404
     */
    public function getSiteDataAction() 
    {
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        $translator = $this->getServiceLocator()->get('translator');
        
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        
        $colId = array();
        $dataCount = 0;
        $draw = 0;
        $tableData = array();
        
        if($this->getRequest()->isPost()) 
        {
            $colId = array_keys($melisTool->getColumns());
            
            $sortOrder = $this->getRequest()->getPost('order');
            $sortOrder = $sortOrder[0]['dir'];
            
            $selCol = $this->getRequest()->getPost('order');
            $selCol = $colId[$selCol[0]['column']];
            
            $draw = $this->getRequest()->getPost('draw');
            
            $start = $this->getRequest()->getPost('start');
            $length =  $this->getRequest()->getPost('length');
            
            $search = $this->getRequest()->getPost('search');
            $search = $search['value'];
            
            $dataCount = $siteTable->getTotalData();
            
            $getData = $siteTable->getSitesData(array(
                'where' => array(
                    'key' => 'site_id',
                    'value' => $search,
                ),
                'order' => array(
                    'key' => $selCol,
                    'dir' => $sortOrder,
                ),
                'start' => $start,
                'limit' => $length,
                'columns' => $melisTool->getSearchableColumns(),
                'date_filter' => array()
            ));

            $tableData = $getData->toArray();
            for($ctr = 0; $ctr < count($tableData); $ctr++)
            {
                // apply text limits
                foreach($tableData[$ctr] as $vKey => $vValue)
                {
                    $tableData[$ctr][$vKey] = $melisTool->limitedText($vValue);
                }
            
                // manually modify value of the desired row
                // no specific row to be modified
                
                // add DataTable RowID, this will be added in the <tr> tags in each rows
                $tableData[$ctr]['DT_RowId'] = $tableData[$ctr]['site_id'];
            }
        }
        
        return new JsonModel(array(
            'draw' => (int) $draw,
            'recordsTotal' => $dataCount,
            'recordsFiltered' =>  $siteTable->getTotalFiltered(),
            'data' => $tableData,
        ));
        
    }
    
    /**
     * Add New Site
     * @return \Zend\View\Model\JsonModel
     */
    public function addSiteAction()
    {
        $request = $this->getRequest();
        $siteId = null;
        $status  = 0;
        $errors  = array();
        $textMessage = '';
        $textTitle = '';
        
        $eventDatas = array();
        $this->getEventManager()->trigger('meliscms_site_save_new_start', $this, $eventDatas);
        
        $translator = $this->getServiceLocator()->get('translator');
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
       
        // get the site form
        $newSiteForm = $melisTool->getForm('meliscms_site_tool_generic_form');
        
        if($request->isPost()) 
        {
            $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
            $domainTable = $this->getServiceLocator()->get('MelisEngineTableSiteDomain');
            $site404Table = $this->getServiceLocator()->get('MelisEngineTableSite404');
            
            $postValues = get_object_vars($request->getPost());
            $newSiteForm->setData($postValues);

            if($newSiteForm->isValid()) 
            {
                $data = $newSiteForm->getData();
                
                $dataSite = array();
                $dataDomain = array();
                $dataSite404 = array();
                
                foreach($data as $dataKey => $dataValue) 
                {
                    if(strpos($dataKey, self::SITE_TABLE_PREFIX) !== false) {
                        $sitePrefix = str_replace('ssite_', 'site_', $dataKey);
                        $dataSite[$sitePrefix] = $data[$dataKey];
                    }
                    
                    if(strpos($dataKey, self::SITE404_TABLE_PREFIX) !== false) {
                        $dataSite404[$dataKey] = $data[$dataKey];
                    }
                    
                    if(strpos($dataKey, self::DOMAIN_TABLE_PREFIX) !== false) {
                        $dataDomain[$dataKey] = $data[$dataKey];
                    }
                }

                $siteData = $siteTable->getEntryByField('site_name', $this->getRequest()->getPost('ssite_name'))->current();

                if(empty($siteData)) {
                    
                    // create a site for this
                    $curPlatform = !empty(getenv('MELIS_PLATFORM'))  ? getenv('MELIS_PLATFORM') : 'development';
                    $corePlatformTable = $this->getServiceLocator()->get('MelisCoreTablePlatform');
                    $corePlatformData = $corePlatformTable->getEntryByField('plf_name', $curPlatform)->current();
                    
                    if($corePlatformData) {
                        
                        $platformId = $corePlatformData->plf_id;
                        $cmsPlatformTable = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
                        $cmsPlatformData = $cmsPlatformTable->getEntryById($platformId)->current();
                        
                        if ($cmsPlatformData) {
                            
                            $currentPageId = (int) $cmsPlatformData->pids_page_id_current;
                            
                            $siteId = $siteTable->save($dataSite);
                            
                            // after adding make sure to fetch again the data so the other site table can use the site id
                            $siteData = $siteTable->getEntryByField('site_name', $dataSite['site_name']);
                            $siteData = $siteData->current();
                            
                            // add the foreign id on the data arrays
                            $dataDomain['sdom_site_id'] = $siteId;
                            $dataSite404['s404_site_id'] = $siteId;
                            
                            $domainTable->save($dataDomain);
                            
                            // save if 404 page id has value
                            if (!empty($dataSite404['s404_page_id'])) {
                                $site404Table->save($dataSite404);
                            }
                            
                            
                            $pageTreeTable = $this->getServiceLocator()->get('MelisEngineTablePageTree');
                            $treePageOrder = $pageTreeTable->getTotalData('tree_father_page_id','-1');
                            
                            $pageTreeTable->save(array(
                               'tree_page_id' => $currentPageId,
                               'tree_father_page_id' => -1,
                               'tree_page_order' => $treePageOrder + 1,
                            ));
                            
                            $pageLangTable = $this->getServiceLocator()->get('MelisEngineTablePageLang');
                            $pageLangTable->save(array(
                               'plang_page_id' => $currentPageId,
                               'plang_lang_id' => 1,
                               'plang_page_id_initial' => 1 
                            ));
                            
                            $pageSavedTable = $this->getServiceLocator()->get('MelisEngineTablePageSaved');
                            $pageSavedTable->save(array(
                                'page_id' => $currentPageId,
                                'page_type' => 'SITE',
                                'page_status' => 1,
                                'page_menu' => 'LINK',
                                'page_name' => $dataSite['site_name'],
                                'page_tpl_id' => -1,
                                'page_content' => '<?xml version="1.0" encoding="UTF-8"?>\n<document type="MelisCMS" author="MelisTechnology" version="2.0">\n	<melisTag id="home_002"><![CDATA[13:37Porto is <strong class="inverted"> <span class="word-rotate active" style="height: 54px;"> <span class="word-rotate-items"> incredibly especially extremely incredibly</span> </span> </strong> beautiful and fully responsive.]]></melisTag>\n</document>',
                                'page_taxonomy' => '',
                                'page_creation_date' => date('Y-m-d H:i:s')
                            ));
                            
                            $cmsPlatformTable->save(array(
                                'pids_page_id_current' => ++$currentPageId //((int) $currentPageId + 2)
                            ), $platformId);
                            
                            // free-up memory
                            unset($data);
                            unset($dataSite);
                            unset($dataSite404);
                            unset($dataDomain);
                            
                            $textMessage = 'tr_meliscms_tool_site_add_success';
                            $status = 1;
                        }
                        else
                        {
                            // if there is no Platform Id available
                            $textMessage = 'tr_meliscms_tool_site_error_prompt_add';
                            $errors['platformId'] = array(
                                'label' => $translator->translate('tr_meliscms_tool_platform_ids'),
                                'noPlatformIds' => $translator->translate('tr_meliscms_tool_site_no_platform_ids')
                            );
                        }
                    }
                    else 
                    {
                        // If there is no Platform available on the database
                        $textMessage = 'tr_meliscore_error_message';
                    }
                }
                else 
                {
                    // if data already exists then add manually the foreign site id key
                    $textMessage = 'tr_meliscms_tool_site_error_prompt_add';
                    $errors= array(
                        'ssite_name' => array(
                            'siteAlreadyExists' => $translator->translate('tr_meliscms_tool_site_name_exists')
                        ),
                    );
                }
            }
            else 
            {
                $errors = $newSiteForm->getMessages();
                $textMessage = 'tr_meliscms_tool_site_error_prompt_add';
                $status = 0;
            }
            
            // insert labels and error messages in error array
            $melisMelisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');
            $appConfigForm = $melisMelisCoreConfig->getItem('meliscms/tools/meliscms_tool_site/forms/meliscms_site_tool_generic_form');
            $appConfigForm = $appConfigForm['elements'];
            
            foreach ($errors as $keyError => $valueError)
            {
                foreach ($appConfigForm as $keyForm => $valueForm)
                {
                    if ($valueForm['spec']['name'] == $keyError &&
                        !empty($valueForm['spec']['options']['label']))
                        $errors[$keyError]['label'] = $valueForm['spec']['options']['label'];
                }
            }
            
        }
        
        $response = array(
            'success' => $status,
            'textTitle' => 'tr_meliscms_tool_site',
            'textMessage' => $textMessage,
            'errors' => $errors,
        );
        
        $this->getEventManager()->trigger('meliscms_site_save_new_end', $this, array_merge($response, array('typeCode' => 'CMS_SITE_ADD', 'itemId' => $siteId)));
        
        return new JsonModel($response);
    }
    
    public function updateSiteAction() 
    {
        $request = $this->getRequest();
        $siteID = null;
        $status  = 0;
        $errors  = array();
        $textMessage = '';
        $textTitle = '';
        
        $eventDatas = array();
        $this->getEventManager()->trigger('meliscms_site_save_start', $this, $eventDatas);
        
        $translator = $this->getServiceLocator()->get('translator');
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
         
        // get the site form
        $updateSiteForm = $melisTool->getForm('meliscms_site_tool_generic_form');
        
        if($request->isPost()) {
        
            $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
            $domainTable = $this->getServiceLocator()->get('MelisEngineTableSiteDomain');
            $site404Table = $this->getServiceLocator()->get('MelisEngineTableSite404');
            
            $siteID = (int) $this->getRequest()->getPost('siteID');
            $siteEnv = $this->getRequest()->getPost('sdom_env');
            $isNew = (int) $this->getRequest()->getPost('isNew');
            $oldSite404PageId = (int) $this->getRequest()->getPost('oldsite404pageid');

        
            $postValues = get_object_vars($request->getPost());
            $updateSiteForm->setData($postValues);
        
            if($updateSiteForm->isValid()) {
                $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
                $domainData = $siteTable->getSiteById($siteID, $siteEnv, true)->current();
            
                $data = $updateSiteForm->getData();
                
                $dataSite = array();
                $dataDomain = array();
                $dataSite404 = array();
                
                foreach($data as $dataKey => $dataValue)
                {
                    if(strpos($dataKey, self::SITE_TABLE_PREFIX) !== false) {
                        $sitePrefix = str_replace('ssite_', 'site_', $dataKey);
                        $dataSite[$sitePrefix] = $data[$dataKey];
                    }
                
                    if(strpos($dataKey, self::SITE404_TABLE_PREFIX) !== false) {
                        $dataSite404[$dataKey] = $data[$dataKey];
                    }
                
                    if(strpos($dataKey, self::DOMAIN_TABLE_PREFIX) !== false) {
                        $dataDomain[$dataKey] = $data[$dataKey];
                    }
                
                }
                 
                $dataDomain['sdom_site_id'] = $siteID;
                $dataSite404['s404_site_id'] = $siteID;
                
                $dataSite['site_id'] = $siteID;
                
                $domainData = $domainTable->getDataBySiteIdAndEnv($siteID,$siteEnv)->current();
                $s404Data = $site404Table->getDataBySiteIdAndPageId($siteID, $oldSite404PageId)->current();
                
                // update only
                if($domainData) {
                    
                    $siteTable->save($dataSite, $siteID);
                    if($domainData) {
                        $domainID = (int) $domainData->sdom_id;
                        $dataDomain['sdom_id'] = $domainID;
                        $domainTable->save($dataDomain, $domainID);
                        
                        if (!empty($s404Data)) {
                            $dataSite404['s404_id'] = $s404Data->s404_id;
                            $site404Table->save($dataSite404, $s404Data->s404_id);
                        }
                    }
                }
                else {
                    $id = $domainTable->save($dataDomain);
                }
                
                
                $textMessage = 'tr_meliscms_tool_site_edit_success';
                $status = 1;
                
  
            }
            else {
                $errors = $updateSiteForm->getMessages();
                $textMessage = 'tr_meliscms_tool_site_edit_failed';
                $status = 0;
            }
        
            // insert labels and error messages in error array
            $melisMelisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');
            $appConfigForm = $melisMelisCoreConfig->getItem('meliscms/tools/meliscms_tool_site/forms/meliscms_site_tool_generic_form');
            $appConfigForm = $appConfigForm['elements'];
        
            foreach ($errors as $keyError => $valueError)
            {
                foreach ($appConfigForm as $keyForm => $valueForm)
                {
                    if ($valueForm['spec']['name'] == $keyError &&
                        !empty($valueForm['spec']['options']['label']))
                        $errors[$keyError]['label'] = $valueForm['spec']['options']['label'];
                }
            }
        
        }
        
        $response = array(
            'success' => $status,
            'textTitle' => 'tr_meliscms_tool_site',
            'textMessage' => $textMessage,
            'errors' => $errors,
        );
        
        $this->getEventManager()->trigger('meliscms_site_save_end', $this, array_merge($response, array('typeCode' => 'CMS_SITE_UPDATE', 'itemId' => $siteID)));
        
        return new JsonModel($response);
    }
    
    public function deleteSiteAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        // deleteByField($field, $value)
        $eventDatas = array();
        $this->getEventManager()->trigger('meliscms_site_delete_start', $this, $eventDatas);
         
        $request = $this->getRequest();
        $siteID = null;
        $status  = false;
        $textMessage = '';
        $textTitle = 'tr_meliscms_tool_site';
        // make sure it's a POST call
        if($request->isPost()) {
        
            $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
            $domainTable = $this->getServiceLocator()->get('MelisEngineTableSiteDomain');
            $site404Table = $this->getServiceLocator()->get('MelisEngineTableSite404');
            $siteID = $request->getPost('id');
        
            // make sure our ID is not empty
            if(!empty($siteID))
            {
                $siteTable->deleteByField('site_id', $siteID);
                $domainTable->deleteByField('sdom_site_id', $siteID);
                $site404Table->deleteByField('s404_site_id', $siteID);
                $status = true;
                $textMessage = 'tr_meliscms_tool_site_delete_success';
            }
        }
        
        $response = array(
            'success' => $status ,
            'textTitle' => $textTitle,
            'textMessage' => $textMessage
        );
        $this->getEventManager()->trigger('meliscms_site_delete_end', $this, array_merge($response, array('typeCode' => 'CMS_SITE_DELETE', 'itemId' => $siteID)));
        
        return new JsonModel($response);
    }
    
    
    public function getSiteByIdAndEnvironmentAction()
    {
        $request = $this->getRequest();
        $requestedData = array();
        
        if($request->isPost())
        {
            $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
            
            $siteId = $request->getPost('siteId');
            $siteEnv = $request->getPost('siteEnv') === 'selnewsite' ? '' : $request->getPost('siteEnv');
        
            if(is_numeric($siteId)) {
                $data = $siteTable->getSiteById($siteId, $siteEnv, true)->toArray();
                //print_r($data);
                if($data) {
                    // replace site_ prefix into ssite_ prefix, this will be the columns to be replaced
                    $columns = array('site_id', 'site_main_page_id', 'site_name');
                    
                    $ctr = 0;
                    foreach($data as $dataKey => $dataVal) {

                        foreach((array)$dataVal as $key => $value) {
                            if(in_array($key, $columns)) {
                                $requestedData[$ctr]['s'.$key] = $dataVal[$key];
                            }
                            else {
                                $requestedData[$ctr][$key] = $dataVal[$key];
                            }
                        }
                        $ctr++;

                    }
                    
                }
            }
                
        }
        
        return new JsonModel($requestedData);
    }
    
    /**
     * Fetching Current Platform on specific Site
     * @return Json - Name of the Platform
     */
    public function getSiteEnvironmentAction()
    {
        $json = array();
        $siteId = (int) $this->params()->fromQuery('siteId');
        
        $melisEngineTableSiteDomain = $this->getServiceLocator()->get('MelisEngineTableSiteDomain');
        $sitePlatform = $melisEngineTableSiteDomain->getEntryByField('sdom_site_id', $siteId);
        $sitePlatform = $sitePlatform->current();
        
        $siteDomainEnv = ($sitePlatform) ? $sitePlatform->sdom_env : null;
        
        return new JsonModel(array('data' => $siteDomainEnv));
    }
    
    /**
     * Fetching all Core Platforms
     * @return Json
     */
    public function getSiteEnvironmentsAction()
    {
        $json = array();
        $siteId = (int) $this->params()->fromQuery('siteId');
        
        $domainTable = $this->getServiceLocator()->get('MelisCoreTablePlatform');
        $domainData = $domainTable->fetchAll();
        $domainData = $domainData->toArray();
        
        if($domainData) {
            foreach($domainData as $domainValues) {
                $json[] = $domainValues['plf_name'];
            }
        }
        
        return new JsonModel(array('data' => $json));
    }
    
    /**
     * Deletes the specific information of an environment
     * @return \Zend\View\Model\JsonModel
     */
    public function deleteSiteByIdAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        $request = $this->getRequest();
        $domainId = null;
        $success = 0;
        $textTitle = 'tr_meliscms_tool_site';
        $textMessage = '';
        
        $eventDatas = array();
        $this->getEventManager()->trigger('meliscms_site_delete_by_id_start', $this, $eventDatas);
        
        if($request->isPost()) {

            $domainTable = $this->getServiceLocator()->get('MelisEngineTableSiteDomain');
            $site404Table = $this->getServiceLocator()->get('MelisEngineTableSite404');
            
            $siteID = (int) $request->getPost('siteid');
            $siteEnv = $request->getPost('env');
            $site404PageId = $request->getPost('site404Page');
            
            $domainData = $domainTable->getDataBySiteIdAndEnv($siteID,$siteEnv);
            $domainData = $domainData->current();
            
            if($domainData)
            {
                $domainId = $domainData->sdom_id;
                
                $domainTable->deleteByField('sdom_id', $domainId);
                
                $success = 1;
                $textMessage = 'tr_meliscms_tool_site_delete_env_success';
            }
            else
            {
                $textMessage = 'tr_meliscms_tool_site_delete_env_failed';
            }
        }
        
        $response = array(
            'success' => $success,
            'textTitle' => $textTitle,
            'textMessage' => $textMessage,
        );
        
        $this->getEventManager()->trigger('meliscms_site_delete_by_id_end', $this, array_merge($response, array('typeCode' => 'CMS_SITE_ENV_DELETE', 'itemId' => $domainId)));
        
        return new JsonModel($response);
    }
    
    public function deleteSiteDomainPlatformAction()
    {
        $platform   = $this->params()->fromRoute('platform', $this->params()->fromQuery('platform', ''));
        $id         = $this->params()->fromRoute('id', $this->params()->fromQuery('id', ''));
        $success    = (int) $this->params()->fromRoute('success', $this->params()->fromQuery('success', ''));

        if($success == 1) {
            $domainTable = $this->getServiceLocator()->get('MelisEngineTableSiteDomain');
            $platformIdTable = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
            
            $platformIdTable->deleteByField('pids_id', $id);
            $domainTable->deleteByField('sdom_env', $platform);
        }
    }
}