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
use phpDocumentor\Reflection\Types\Boolean;

/**
 * Site Tool Plugin
 */
class SiteController extends AbstractActionController
{
    const TOOL_INDEX = 'meliscms';
    const TOOL_KEY = 'meliscms_tool_site';
    
    const SITE_TABLE_PREFIX = 'site_';
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
        // Checks wether the user has access to this tools or not
        $melisCoreRights = $this->getServiceLocator()->get('MelisCoreRights');
        if(!$melisCoreRights->canAccess(self::TOOL_KEY)) {
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
    
        $view->setVariable('meliscms_site_tool_creation_form', $melisTool->getForm('meliscms_site_tool_creation_form'));
    
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
    
        $view->setVariable('meliscms_site_tool_edition_form', $melisTool->getForm('meliscms_site_tool_edition_form'));
    
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
    
    public function renderToolSiteNewSiteConfirmationModalAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $siteId = $this->params()->fromQuery('siteId');
        
        $cmsSiteSrv = $this->getServiceLocator()->get('MelisCmsSiteService');
        $sitePages = $cmsSiteSrv->getSitePages($siteId);
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->sitePages = $sitePages;
        
        return $view;
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
            
            $start =   (int) $this->getRequest()->getPost('start');
            $length =  (int) $this->getRequest()->getPost('length');
            
            $search = $this->getRequest()->getPost('search');
            $search = $search['value'];
            
            $dataCount = $siteTable->getTotalData();

            $getData = $siteTable->getSitesData($search, $melisTool->getSearchableColumns(), $selCol, $sortOrder, $start, $length);
            $dataFilter = $siteTable->getSitesData($search, $melisTool->getSearchableColumns(), $selCol, $sortOrder, null, null);

            $tableData = $getData->toArray();
            for($ctr = 0; $ctr < count($tableData); $ctr++)
            {
                // apply text limits
                foreach($tableData[$ctr] as $vKey => $vValue)
                {
                    $tableData[$ctr][$vKey] = $melisTool->limitedText($melisTool->escapeHtml($vValue));
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
            'recordsFiltered' =>  $dataFilter->count(),
            'data' => $tableData,
        ));
        
    }
    /**
     * Return site domain
     *
     * return array()
     */
    public function getSiteDomainPlatform()
    {
        $data = array();

        if($this->getRequest()->isGet()){
            $siteDomain = $this->getServiceLocator()->get('SiteDomain');

            $data = $siteDomain->getSiteDomain();
        }


        return $data;
    }

    /**
     * Add New Site
     * @return \Zend\View\Model\JsonModel
     */
    public function saveSiteAction()
    {
        $request = $this->getRequest();
        $siteId = $request->getPost('site_id', null);
        $status  = 0;
        $errors  = array();
        $textMessage = '';
        $textTitle = '';
        
        $this->getEventManager()->trigger('meliscms_site_save_start', $this, array());
        
        $translator = $this->getServiceLocator()->get('translator');
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
       
        // Getting the Creation/Edition Site Form
        if (is_null($siteId))
        {
            $siteFormType = 'meliscms_site_tool_creation_form';
            $logTypeCode = 'CMS_SITE_ADD';
        }
        else
        {
            $siteFormType = 'meliscms_site_tool_edition_form';
            $logTypeCode = 'CMS_SITE_UPDATE';
        }
        
        $siteForm = $melisTool->getForm($siteFormType);
        
        if($request->isPost()) 
        {
            $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
            $domainTable = $this->getServiceLocator()->get('MelisEngineTableSiteDomain');
            $site404Table = $this->getServiceLocator()->get('MelisEngineTableSite404');
            
            $postValues = get_object_vars($request->getPost());
            $postValues = $melisTool->sanitizePost($postValues);
            $siteForm->setData($postValues);
            
            
            
            if($siteForm->isValid()) 
            {
                $data = $siteForm->getData();
                
                $dataSite = array();
                $dataDomain = array();
                $dataSite404 = array();
                $dataSiteLang= null;
                
                if (!empty($data['plang_lang_id'])){
                    $dataSiteLang = $data['plang_lang_id'];
                }
                
                foreach($data as $dataKey => $dataValue) 
                {
                    if(strpos($dataKey, self::SITE_TABLE_PREFIX) !== false) {
                        $dataSite[$dataKey] = $data[$dataKey];
                    }
                    
                    if(strpos($dataKey, self::SITE404_TABLE_PREFIX) !== false) {
                        $dataSite404[$dataKey] = $data[$dataKey];
                    }
                    
                    if(strpos($dataKey, self::DOMAIN_TABLE_PREFIX) !== false) {
                        $dataDomain[$dataKey] = $data[$dataKey];
                    }
                }
                
                $isValidName = false;
                // Checking if the Site name is existing on the site lists
                $siteName = $this->getRequest()->getPost('site_name');
                $siteData = $siteTable->getEntryByField('site_name', $siteName)->current();
                
                if (is_null($siteId))
                {
                    if (empty($siteData))
                    {
                        $isValidName = true;
                    }
                }
                else 
                {
                    if (!empty($siteData))
                    {
                        
                        if ($siteData->site_id == $siteId)
                        {
                            $isValidName = true;
                        }
                    }
                    else 
                    {
                        $isValidName = true;
                    }
                }
                
                if($isValidName) 
                {
                    $genSiteModule = !empty($request->getPost('gen_site_mod')) ? true : false;
                    
                    $cmsSiteSrv = $this->getServiceLocator()->get('MelisCmsSiteService');
                    $saveSiteResult = $cmsSiteSrv->saveSite($dataSite, $dataDomain, $dataSite404, $dataSiteLang, $siteId, $genSiteModule, $dataSite['site_name']);
                    
                    if ($saveSiteResult['success'])
                    {
                        if (is_null($siteId))
                        {
                            $textMessage = 'tr_meliscms_tool_site_add_success';
                        }
                        else 
                        {
                            $textMessage = 'tr_meliscms_tool_site_edit_success';
                        }
                        
                        $siteId = $saveSiteResult['site_id'];
                        $status = 1;
                    }
                    else 
                    {
                        if ($saveSiteResult['message'] == 'tr_meliscms_tool_site_no_platform_ids')
                        {
                            $textMessage = 'tr_meliscms_tool_site_error_prompt_add';
                            $errors['platformId'] = array(
                                'label' => $translator->translate('tr_meliscms_tool_platform_ids'),
                                'noPlatformIds' => $translator->translate('tr_meliscms_tool_site_no_platform_ids')
                            );
                        }
                        else 
                        {
                            $textMessage = $saveSiteResult['message'];
                        }
                    }
                    
                    unset($data);
                    unset($dataSite);
                    unset($dataSite404);
                    unset($dataDomain);
                }
                else 
                {
                    // if data already exists then add manually the foreign site id key
                    $textMessage = 'tr_meliscms_tool_site_error_prompt_add';
                    $errors = array(
                        'site_name' => array(
                            'siteAlreadyExists' => $translator->translate('tr_meliscms_tool_site_name_exists')
                        ),
                    );
                }
            }
            else 
            {
                if (is_null($siteId))
                {
                    $textMessage = 'tr_meliscms_tool_site_error_prompt_add';
                }
                else
                {
                    $textMessage = 'tr_meliscms_tool_site_edit_failed';
                }
                
                $errors = $siteForm->getMessages();
                $status = 0;
            }
            
            // insert labels and error messages in error array
            $melisMelisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');
            $appConfigForm = $melisMelisCoreConfig->getItem('meliscms/tools/meliscms_tool_site/forms/'.$siteFormType);
            $appConfigForm = $appConfigForm['elements'];
            
            foreach ($errors as $keyError => $valueError)
            {
                foreach ($appConfigForm as $keyForm => $valueForm)
                {
                    if ($valueForm['spec']['name'] == $keyError && !empty($valueForm['spec']['options']['label']))
                    {
                        $errors[$keyError]['label'] = $valueForm['spec']['options']['label'];
                    }
                }
            }
        }
        
        $response = array(
            'success' => $status,
            'textTitle' => 'tr_meliscms_tool_site',
            'textMessage' => $textMessage,
            'errors' => $errors,
            'test' => $errors,
        );
        
        $this->getEventManager()->trigger('meliscms_site_save_end', $this, array_merge($response, array('typeCode' => $logTypeCode, 'itemId' => $siteId)));
        
        if ($siteId)
        {
            $response['siteId'] = $siteId;
        }
        
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
            $siteID = (int) $request->getPost('id');
        
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
                    $requestedData = $data;
                }
            }
        }
        
        return new JsonModel($data);
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
