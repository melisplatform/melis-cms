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
use Zend\View\View;

/**
 * Platform Tool Plugin
 */
class PlatformController extends AbstractActionController
{
    const TOOL_INDEX = 'meliscms';
    const TOOL_KEY = 'meliscms_platform_tool';
    const INTERFACE_KEY = 'meliscms_tool_platform_ids';
    
    /*
     * Render Tool Platform Container
     */
    public function renderContainerAction(){
        
        $translator = $this->getServiceLocator()->get('translator');
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        $noAccessPrompt = '';
        if($this->hasAccess(self::INTERFACE_KEY)) {
            $noAccessPrompt = $translator->translate('tr_tool_no_access');
        }
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->noAccessPrompt = $noAccessPrompt;
        return $view;
    }
    
    /*
     * Render Header Title
     */
    public function renderHeaderAction(){
        
        $view = new ViewModel();
        $view->melisKey = $this->params()->fromRoute('melisKey', '');
        return $view;
    }
    
    /*
     * Render Header Add Button 
     */
    public function renderHeaderAddButtonAction(){
    
        $view = new ViewModel();
        $view->melisKey = $this->params()->fromRoute('melisKey', '');
        return $view;
    }
    
    /*
     * Render Platform Content
     */
    public function renderContentAction(){
    
        $view = new ViewModel();
        $view->melisKey = $this->params()->fromRoute('melisKey', '');
        return $view;
    }
    
    /*
     * Render Platform Table
     */
    public function renderContentPlatformTableAction(){
        
        $translator = $this->getServiceLocator()->get('translator');
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        
        $columns = $melisTool->getColumns();
        // pre-add Action Columns
        $columns['actions'] = array('text' => $translator->translate('tr_meliscms_action'));
        $view = new ViewModel();
        $view->melisKey = $this->params()->fromRoute('melisKey', '');
        $view->tableColumns = $columns;
        $view->getToolDataTableConfig = $melisTool->getDataTableConfiguration();
        return $view;
    }
    
    /*
     * Render Button for Platform ID Edition
     */
    public function renderActionEditAction(){
        $view = new ViewModel();
        $view->melisKey = $this->params()->fromRoute('melisKey', '');
        return $view;
    }
    
    /*
     * Render Button for Platform ID Delete
     */
    public function renderActionDeleteAction(){
        $view = new ViewModel();
        $view->melisKey = $this->params()->fromRoute('melisKey', '');
        return $view;
    }
    
    /*
     * Render Table Header Rows Limit page page
     */
    public function renderContentPlatformTableLimitAction(){
        return new ViewModel();
    }
    
    /*
     * Render Refresh Page
     */
    public function renderContentPlatformTableRefreshAction(){
        return new ViewModel();
    }
    
    /*
     * Render Platform Container Modal
     */
    public function renderPlatformModalAction(){
        $view = new ViewModel();
        $view->setTerminal(false);
        
        $view->id = $this->params()->fromRoute('id', $this->params()->fromQuery('id', ''));
        $view->melisKey = $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey', ''));
        return $view;
        
    }
    
    /*
     * Render Platform Content Modal
     */
    public function renderPlatformModalContentAction(){
        
        $pids_id = $this->params()->fromQuery('id');
        
        // Get Cms Platform ID form from  App Tool
        $melisMelisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');
        $genericPlatformForm = $melisMelisCoreConfig->getFormMergedAndOrdered('meliscms/tools/meliscms_platform_tool/forms/meliscms_tool_platform_generic_form','meliscms_tool_platform_generic_form');
        
        // Factoring Calendar event and pass to view
        $factory = new \Zend\Form\Factory();
        $formElements = $this->serviceLocator->get('FormElementManager');
        $factory->setFormElementManager($formElements);
        $propertyForm = $factory->createForm($genericPlatformForm);
        
        $view = new ViewModel();
        
        // Check if Cms Platform Id is Set
        if (isset($pids_id)&&$pids_id!=''){
            
            // Get Platform ID Details
            $melisEngineTablePlatformIds = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
            $platformIdsData = $melisEngineTablePlatformIds->getEntryById($pids_id);
            $platformIdsData = $platformIdsData->current();
            
            $platformTable = $this->getServiceLocator()->get('MelisCoreTablePlatform');
            $platformData = $platformTable->getEntryById($pids_id);
            $platformData = $platformData->current();
            
            // Assign Platform name to Element Name of the Form from the App Tool
            $platformIdsData->pids_name_input = $platformData->plf_name;
            
            // Removing Select input 
            $propertyForm->remove('pids_name_select');
            // Binding datas to the Form
            $propertyForm->bind($platformIdsData);
            
            // Set variable to View
            $view->pids_id = $pids_id;
            $view->tabTitle = 'tr_meliscms_tool_platform_ids_btn_edit'; 
        }else{
            // Removing Id input and Platform input
            $propertyForm->remove('pids_id');
            $propertyForm->remove('pids_name_input');
            // Set variable to View
            $view->tabTitle = 'tr_meliscms_tool_platform_ids_btn_add';
        }
        
        $view->setVariable('meliscms_tool_platform_generic_form', $propertyForm);
        $view->melisKey = $this->params()->fromRoute('melisKey', '');
        return $view;
    }
    
    /*
     * Saving CMS Platform IDs
     * @return Json Array
     */
    public function savePlatformIdsRangeAction(){
        $translator = $this->getServiceLocator()->get('translator');
        
        $request = $this->getRequest();
        // Default Values
        $pids_id = null;
        $status  = 0;
        $textMessage = '';
        $errors  = array();
        $textTitle = '';
        $responseData = array();
        $this->getEventManager()->trigger('meliscms_platform_IDs_save_start', $this, array());
        // Get Cms Platform ID form from  App Tool
        $melisMelisCoreConfig = $this->getServiceLocator()->get('MelisCoreConfig');
        $appConfigForm = $melisMelisCoreConfig->getFormMergedAndOrdered('meliscms/tools/meliscms_platform_tool/forms/meliscms_tool_platform_generic_form','meliscms_tool_platform_generic_form');
         
        $factory = new \Zend\Form\Factory();
        $formElements = $this->serviceLocator->get('FormElementManager');
        $factory->setFormElementManager($formElements);
        $propertyForm = $factory->createForm($appConfigForm);
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');

        if($request->isPost()) {
             
            // Getting Post Datas
            $datas = get_object_vars($request->getPost());
            $postValues = $melisTool->sanitizePost($datas);
            // Response Messages Initialization
            $textTitle = $translator->translate('tr_meliscms_tool_platform_ids');
            if ($datas['pids_id']){
                $pids_id = $datas['pids_id'];
                $logTypeCode = 'CMS_PLATFORM_IDS_UPDATE';
                $textMessage = 'tr_meliscms_tool_platform_update_error';
            }else{
                $logTypeCode = 'CMS_PLATFORM_IDS_ADD';
                $textMessage = 'tr_meliscms_tool_platform_add_error';
            }
            
            // Checking if the Post Data has CMS Platform ID, If the ID has value
            // Means the current datas are existing on the database, and this is for updating
            if ($datas['pids_id']){
                $propertyForm->remove('pids_name_select');
                $propertyForm->getInputFilter()->remove('pids_name_select');
            }
             
            $propertyForm->setData($datas);
             
            if($propertyForm->isValid()) 
            {
                // Page extra validation
                if ($datas['pids_page_id_start']>$datas['pids_page_id_end']){
                    $errors['pids_page_id_start'] = array(
                       'isGreaterThan' => $translator->translate('tr_meliscms_tool_platform_pageIdStart_must_lessThan_or_equalTo_pageIdEnd')
                    );
                }
                
                if ($datas['pids_page_id_start']>$datas['pids_page_id_end']){
                    $errors['pids_page_id_end'] = array(
                        'isGreaterThan' => $translator->translate('tr_meliscms_tool_platform_pageIdEnd_must_greaterThan_or_equalTo_pageIdStart')
                    );
                }
                
                if($datas['pids_page_id_current']<$datas['pids_page_id_start']){
                    $errors['pids_page_id_current'] = array(
                        'isLessThan' => $translator->translate('tr_meliscms_tool_platform_pageIdCurrent_must_greaterThan_or_equalTo_PageIdStart')
                    );
                }
                
                if($datas['pids_page_id_current']>$datas['pids_page_id_end']){
                    $errors['pids_page_id_current'] = array(
                        'isGreaterThan' => $translator->translate('tr_meliscms_tool_platform_pageIdCurrent_must_lessThan_or_equalTo_pageIdEnd')
                    );
                }
                
                // Template extra validation
                if ($datas['pids_tpl_id_start']>$datas['pids_tpl_id_end']){
                    $errors['pids_tpl_id_start'] = array(
                        'isGreaterThan' => $translator->translate('tr_meliscms_tool_platform_tplIdStart_must_lessThan_or_equalTo_tplIdEnd')
                    );
                }
                
                if ($datas['pids_tpl_id_start']>$datas['pids_tpl_id_end']){
                    $errors['pids_tpl_id_end'] = array(
                        'isGreaterThan' => $translator->translate('tr_meliscms_tool_platform_tplIdEnd_must_greaterThan_or_equalTo_tplIdStart')
                    );
                }
                
                if($datas['pids_tpl_id_current']<$datas['pids_tpl_id_start']){
                    $errors['pids_tpl_id_current'] = array(
                        'isLessThan' => $translator->translate('tr_meliscms_tool_platform_tplIdCreent_must_greaterThan_or_equalTo_tplIdStart')
                    );
                }
                
                if($datas['pids_tpl_id_current']>$datas['pids_tpl_id_end']){
                    $errors['pids_tpl_id_current'] = array(
                        'isGreaterThan' => $translator->translate('tr_meliscms_tool_platform_tplIdCurrent_must_lessThan_or_equalTo_tplIdEnd')
                    );
                }
                
                if (empty($errors)){
                    $melisEngineTablePlatformIds = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
                    
                    // Checking if The Post datas Confliction to the Existing Datas in CMS Platform IDs
                    $pids_id = ($datas['pids_id']) ? $datas['pids_id'] : 0;
                    $rangeExist = $melisEngineTablePlatformIds->platformIdsRangeIsExist($datas['pids_page_id_start'],
                                                                                        $datas['pids_page_id_end'],
                                                                                        $datas['pids_tpl_id_start'],
                                                                                        $datas['pids_tpl_id_end'],
                                                                                        $pids_id);
                    
                    if ($rangeExist){
                        if ($datas['pids_id']){
                            // Updating exsiting Data
                            unset($datas['pids_name_select']);
                            $pids_id = $datas['pids_id'];
                            $melisEngineTablePlatformIds->save($datas,$datas['pids_id']);
                            $textMessage = 'tr_meliscms_tool_platform_update_success';
                            $status = 1;
                        }else{
                            // Saving new Data
                            $datas['pids_id'] = $datas['pids_name_select'];
                            $pids_id = $datas['pids_id'];
                            unset($datas['pids_name_select']);
                            $melisEngineTablePlatformIds->save($datas);
                            $textMessage = 'tr_meliscms_tool_platform_add_success';
                            $status = 1;
                        }
                    }else{
                        $textMessage = 'tr_meliscms_tool_platform_conflict_error';
                    }
                }
            }else{
                $errors = $propertyForm->getMessages();
            }
             
            $appConfigForm = $appConfigForm['elements'];
             
            foreach ($errors as $keyError => $valueError)
            {
                foreach ($appConfigForm as $keyForm => $valueForm)
                {
                    if ($valueForm['spec']['name'] == $keyError && !empty($valueForm['spec']['options']['label'])){
                        $errors[$keyError]['label'] = $valueForm['spec']['options']['label'];
                    }
                }
            }
        }
         
        $response = array(
            'success' => $status,
            'textTitle' => $textTitle,
            'textMessage' => $textMessage,
            'errors' => $errors,
            'event' => $responseData
        );
        $this->getEventManager()->trigger('meliscms_platform_IDs_save_end', $this, array_merge($response, array('typeCode' => $logTypeCode, 'itemId' => $pids_id)));
        return new JsonModel($response);
    }
    
    /*
     * Delete Platform ID
     *
     */
    public function deletePlatformIdAction(){
        $translator = $this->getServiceLocator()->get('translator');
        $this->getEventManager()->trigger('meliscms_platform_IDs_delete_start', $this, array());
        $request = $this->getRequest();
        $datas = get_object_vars($request->getPost());
        
        $pids_id = (int) $datas['pid_id'];
        $melisEngineTablePlatformIds = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
        $melisEngineTablePlatformIds->deleteById($pids_id);
        
        $response = array(
            'success' => 1,
            'textTitle' => 'tr_meliscms_tool_platform_ids',
            'textMessage' => 'tr_meliscms_tool_platform_ids_delete_success_msg',
        );
        $this->getEventManager()->trigger('meliscms_platform_IDs_delete_end', $this, array_merge($response, array('typeCode' => 'CMS_PLATFORM_IDS_DELETE', 'itemId' => $pids_id)));
        return new JsonModel($response);
    }
    
    /*
     * Get CMS Platform ID Datas for DataTable
     * @return Json
     */
    public function getPlatformDataAction(){
        $melisEngineTablePlatformIds = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
        $translator = $this->getServiceLocator()->get('translator');
        $platformTable = $this->getServiceLocator()->get('MelisCoreTablePlatform');
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        
        $colId = array();
        $dataCount = 0;
        $draw = 0;
        $tableData = array();
        
        if($this->getRequest()->isPost()) {
        
            $colId = array_keys($melisTool->getColumns());
        
            $sortOrder = $this->getRequest()->getPost('order');
            $sortOrder = $sortOrder[0]['dir'];
        
            $selCol = $this->getRequest()->getPost('order');
            $selCol = $colId[$selCol[0]['column']];
        
            $draw = $this->getRequest()->getPost('draw');
        
            $start = $this->getRequest()->getPost('start');
            $length =  $this->getRequest()->getPost('length');
        
            $dataCount = $melisEngineTablePlatformIds->getTotalData();
        
            $getData = $melisEngineTablePlatformIds->getPagedData(array(
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
                    $tableData[$ctr][$vKey] = $melisTool->limitedText($melisTool->escapeHtml($vValue));
                    
                    $tableData[$ctr]['pids_name'] = $translator->translate('Deleted ('.$tableData[$ctr]['pids_id'].')');
                    
                    $platformName = $platformTable->getEntryById($tableData[$ctr]['pids_id']);
                    
                    if (!empty($platformName)){
                        $platformName = $platformName->current();
                        if (!empty($platformName)){
                            
                            if (getenv('MELIS_PLATFORM')==$platformName->plf_name){
                                $tableData[$ctr]['DT_RowClass'] = 'noPlatformIdDeleteBtn';
                            }
                            
                            $tableData[$ctr]['pids_name'] = $melisTool->limitedText($platformName->plf_name,25);
                        }
                    }
                    
                    
                }
                
                // manually modify value of the desired row
                // no specific row to be modified
        
                // add DataTable RowID, this will be added in the <tr> tags in each rows
                $tableData[$ctr]['DT_RowId'] = $tableData[$ctr]['pids_id'];
            }
        }
        
        return new JsonModel(array(
            'draw' => (int) $draw,
            'recordsTotal' => $dataCount,
            'recordsFiltered' =>  $melisEngineTablePlatformIds->getTotalFiltered(),
            'data' => $tableData,
        ));
    }
    
    //USER ACCESS SECTION
    /**
     * Checks wether the user has access to this tools or not
     * @return boolean
     */
    private function hasAccess($key) {
        $melisCmsAuth = $this->getServiceLocator()->get('MelisCoreAuth');
        $melisCmsRights = $this->getServiceLocator()->get('MelisCoreRights');
        $xmlRights = $melisCmsAuth->getAuthRights();
        $isAccessible = $melisCmsRights->isAccessible($xmlRights, MelisCoreRightsService::MELISCORE_PREFIX_TOOLS, $key);
        return $isAccessible;
    }
}