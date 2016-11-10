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

/**
 * 
 * Template Manager Tool Plugin
 *
 */
class ToolTemplateController extends AbstractActionController
{
    /**
     * This constant variable will map to the app.tool.php configuration file
     * using the corresponding values
     */
    const TOOL_TEMPLATES_CONFIG_PATH = 'meliscms/tools/meliscms_tool_templates';
    const TOOL_KEY = 'meliscms_tool_templates';
    
    /**
     * This is the main view of the Tool, 
     * View File Name: render-tool-template-manager.phtml
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolTemplateAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $noAccessPrompt = '';
        
        if(!$this->hasAccess($this::TOOL_KEY)) {
            $noAccessPrompt = $translator->translate('tr_tool_no_access');
        }
        
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_templates');


        $view = new ViewModel();
        
        $view->melisKey = $melisKey;
        $view->title = $melisTool->getTitle();;
        $view->noAccess  = $noAccessPrompt;
        
        return $view;
    }
    
    /**
     * This is where you place your buttons for the tools
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolTemplateHeaderAction() 
    {
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_templates');
        
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $zoneConfig = $this->params()->fromRoute('zoneconfig', array());
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->title = $melisTool->getTitle();;
        
        return $view;
    }
    
    /**
     * Adds an ADD button in the Header section of the Tool
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolTemplateHeaderAddAction() 
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        
        return $view;
    }
    
    /**
     * Renders the refresh button into the Header section of the Tool
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolTemplateHeaderRefreshAction() 
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        
        return $view;
    }
    
    /**
     * Renders to the Limit selection in the filter bar in the datatable
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolTemplateContentFiltersLimitAction()
    {
        return new ViewModel();
    }
    
    /**
     * Renders to the search input in the filter bar in the datatable
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolTemplateContentFiltersSearchAction()
    {
        return new ViewModel();
    }
    
    /**
     * Renders to the refresh button in the filter bar in the datatable
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolTemplateContentFiltersRefreshAction()
    {
        return new ViewModel();
    }
    
    public function renderToolTemplateContentFiltersExportAction()
    {
        return new ViewModel();
    }
    
    /**
     * displays the content of the tool
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolTemplateContentAction() 
    {

        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        $translator = $this->getServiceLocator()->get('translator');
        

        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_templates');
        
        $view = new ViewModel();

        // get the columns that has been set in the configuration (app.tools.php)
        $columns = $melisTool->getColumns();
        
        // add an extra column which is Action
        $columns['actions'] = array('text' => $translator->translate('tr_meliscms_action'), 'css' => 'width:10%');
        
        // get the column texts set, this will be used in the thead table
        $view->tableColumns = $columns;

        
        // retrieve modals
        $view->templateModals = $melisTool->getAllModals();
        
        $view->melisKey = $melisKey;
        
        $view->getToolDataTableConfig = $melisTool->getDataTableConfiguration();
        
        return $view;
    }
    
    /**
     * Renders to the edit button in the table
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolTemplatesActionEditAction()
    {
        
        return new ViewModel();
    }
    
    /**
     * Renders to the delete button in the table
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolTemplatesActionDeleteAction()
    {
    
        return new ViewModel();
    }
    
    /**
     * The parent container of all modals, this is where you initialze your modal.
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolTemplateModalContainerAction() 
    {
        
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_templates');
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->emptyModal = $melisTool->getModal('meliscms_tool_prospects_empty_modal');
        
        return $view;
    }
    
    
    /**
     * Renders the Add Tab and Content for the modal
     * @return \MelisCms\Controller\ViewModel
     */
    public function renderToolTemplatesModalAddHandlerAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_templates');
        
        $view = new ViewModel();
        $view->addModalHandler = $melisTool->getModal('meliscms_tool_template_add_modal');
        $view->melisKey = $melisKey;
        
        return $view;
    }
    
    /**
     * Renders the Update Tab and Content for the modal
     * @return \MelisCms\Controller\ViewModel
     */
    public function renderToolTemplatesModalEditHandlerAction() 
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_templates');
        
        $view = new ViewModel();
        $view->editModalHandler = $melisTool->getModal('meliscms_tool_template_edit_modal');
        $view->melisKey = $melisKey;
        
        return $view;
    }
    
    /**
     * Handles the empty modal if there's no available modal form for the user.
     * @return \MelisCms\Controller\ViewModel
     */
    public function renderToolTemplatesModalEmptyHandlerAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        
        return $view;
    }
    
    /**
     * This will be used to render the add form in the modal tab
     */
    public function modalTabToolTemplateAddAction()
    {
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_templates');
        
        $view = new ViewModel();
        
        $view->setVariable('meliscms_tool_template_add', $melisTool->getForm('meliscms_tool_template_generic_form'));
        
        return $view;
    }
    
    /**
     * This will be used to render the edit form in the modal tab
     */
    public function modalTabToolTemplateEditAction()
    {
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_templates');
        
        $view = new ViewModel();
        
        $view->setVariable('meliscms_tool_template_edit', $melisTool->getForm('meliscms_tool_template_generic_form'));
        
        return $view;
    }
    
    /** TOOL CRUD
     *  Below are the functions that will be used in
     *  Adding, updating, displaying and deleting an entry 
     *  in our tool table. Most of the functions are triggered
     *  through AJAX call.
     */
    
    /**
     * -- CREATE --
     * Inserts new Template in your tool table
     */
    public function newTemplateDataAction()
    {
        $request = $this->getRequest();
        $status  = 0;
        $errors  = array();
        $textMessage = '';
        $textTitle = '';
        
        // translator
        $translator = $this->getServiceLocator()->get('translator');
        
        $eventDatas = array();
        $this->getEventManager()->trigger('meliscms_template_savenew_start', $this, $eventDatas);
        
        // get the service for Templates Model & Table
        $templatesModel = $this->getServiceLocator()->get('MelisEngineTableTemplate');
        
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_templates');

        // get the currently logged in user
        $melisCoreAuth = $this->serviceLocator->get('MelisCoreAuth');
        $userAuthDatas =  $melisCoreAuth->getStorage()->read();
        
        // get the form
        $templateUpdateForm = $melisTool->getForm('meliscms_tool_template_generic_form');
        
        if($request->isPost())
        {
        
            $postValues = get_object_vars($this->getRequest()->getPost());
            $templateUpdateForm->setData($postValues);
        
            if($templateUpdateForm->isValid())
            {
                // Get the current page id from platform table
                $melisModuleName = getenv('MELIS_PLATFORM');
                $melisEngineTablePlatformIds = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
                $datasPlatformIds = $melisEngineTablePlatformIds->getPlatformIdsByPlatformName($melisModuleName);
                $datasPlatformIds = $datasPlatformIds->current();
                
                if (!empty($datasPlatformIds)){
                    
                    $data = $templateUpdateForm->getData();
                    
                    // Set Template ID from Cms Platform ID
                    $data['tpl_id'] = $datasPlatformIds->pids_tpl_id_current;
                    
                    $site = $data['tpl_site_id'];
                    $data['tpl_creation_date'] = date('Y-m-d H:i:s');
                    $data['tpl_last_user_id'] = $userAuthDatas->usr_id;
                    
                    if($data['tpl_type'] == 'PHP') {
                        $phpPath = $data['tpl_php_path'];
                        
                        if(empty($phpPath)) {
                            $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                            $status = 0;
                            $errors= array(
                                'tpl_php_path' => array(
                                    'empty_path' => $translator->translate('tr_meliscms_template_form_tpl_path_error_empty')
                                ),
                            );
                        }
                        elseif(strlen($phpPath)>150) {
                            $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                            $status = 0;
                            $errors= array(
                                'tpl_php_path' => array(
                                    'path_too_long' => $translator->translate('tr_meliscms_template_form_tpl_path_error_high')
                                ),
                            );
                        }
                        elseif(empty($site)){
                            $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                            $status = 0;
                            $errors= array(
                                'tpl_site_id' => array(
                                    'invalid_selection' => $translator->translate('tr_meliscms_template_form_tpl_site_id_error_empty')
                                ),
                            );
                        }
                        else {
                            $templatesModel->save($data);
                            $textMessage = $translator->translate('tr_tool_template_fm_new_content');
                            $status = 1;
                        }
                    }
                    elseif($data['tpl_type'] == 'ZF2') {
                        $tmpError = array();
                        
                        $tplLayout = $data['tpl_zf2_layout'];
                        $tplController = $data['tpl_zf2_controller'];
                        $tplAction = $data['tpl_zf2_action'];
                        
                        
                        if(empty($tplLayout)) {
                            $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                            $status = 0;
                            $errors= array(
                                'tpl_zf2_layout' => array(
                                    'empty' => $translator->translate('tr_meliscms_template_form_tpl_layout_error_empty')
                                ),
                            );
                        }
                        elseif(strlen($tplLayout)>50) {
                            $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                            $status = 0;
                            $errors= array(
                                'tpl_zf2_layout' => array(
                                    'too_long' => $translator->translate('tr_meliscms_template_form_tpl_layout_error_high')
                                ),
                            );
                        }
                    
                    
                        if(empty($tplController)) {
                            $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                            $status = 0;
                            $errors= array(
                                'tpl_zf2_controller' => array(
                                    'empty' => $translator->translate('tr_meliscms_template_form_tpl_controller_error_empty')
                                ),
                            );
                        }
                        elseif(strlen($tplController)>50) {
                            $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                            $status = 0;
                            $errors= array(
                                'tpl_zf2_controller' => array(
                                    'too_long' => $translator->translate('tr_meliscms_template_form_tpl_controller_error_high')
                                ),
                            );
                        }
                    
                        if(empty($tplAction)) {
                            $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                            $status = 0;
                            $errors= array(
                                'tpl_zf2_action' => array(
                                    'empty' => $translator->translate('tr_meliscms_template_form_tpl_action_error_empty')
                                ),
                            );
                        }
                        elseif(strlen($tplAction)>50) {
                            $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                            $status = 0;
                            $errors= array(
                                'tpl_zf2_action' => array(
                                    'too_long' => $translator->translate('tr_meliscms_template_form_tpl_action_error_high')
                                ),
                            );
                        }
                        elseif(empty($site)){
                            $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                            $status = 0;
                            $errors= array(
                                'tpl_site_id' => array(
                                    'invalid_selection' => $translator->translate('tr_meliscms_template_form_tpl_site_id_error_empty')
                                ),
                            );
                        }
                        
                        
                        if(empty($errors)) {
                            $templatesModel->save($data);
                            $textMessage = $translator->translate('tr_tool_template_fm_new_content');
                            $status = 1;
                        }
                    }
                    
                    if ($status==1){
                        // Update current page id in platform
                        $melisEngineTablePlatformIds->save(array(
                            'pids_tpl_id_current' => $datasPlatformIds->pids_tpl_id_current + 1,
                        ), $datasPlatformIds->pids_id);
                    }
                }
                else 
                {
                    $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                    $errors = array(
                        'platform_ids' => array(
                            'noPlatformIds' => $translator->translate('tr_meliscms_no_available_platform_ids'),
                            'label' => $translator->translate('tr_meliscms_tool_platform_ids'),
                        ),
                    );
                }
            }
            else {
                $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                $errors = $templateUpdateForm->getMessages();
                $status = 0;
            }
            
            // insert labels and error messages in error array
            $melisMelisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');
            $appConfigForm = $melisMelisCoreConfig->getItem('meliscms/tools/meliscms_tool_templates/forms/meliscms_tool_template_generic_form');
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
            'textTitle' => $translator->translate('tr_tool_templates_modal_tab_text_add'),
            'textMessage' => $translator->translate($textMessage),
            'errors' => $errors,
        );
        
        $this->getEventManager()->trigger('meliscms_template_savenew_end', $this, $response);
        
        return new JsonModel($response);
    }
    
    /**
     * -- READ --
     * Returns all the templates data from the table,
     * usually being used whenever you want to refresh the data
     * of your table.
     * @return \Zend\View\Model\JsonModel
     */
    public function getToolTemplateDataAction()
    {
        // get the service for Templates Model & Table
        $templatesModel = $this->getServiceLocator()->get('MelisEngineTableTemplate');
        
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_templates');
        
        // Site Table
        $tableSite = $this->getServiceLocator()->get('MelisEngineTableSite');
        
        
        $colId = array();
        $dataCount = 0;
        $draw = 0;
        $tableData = array();
        
        // make sure that the request is an AJAX call
        if($this->getRequest()->isPost()) {

            // get the tool columns
            $columns = $melisTool->getColumns();
            
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
            
            $dataCount = $templatesModel->getTotalData();
            
            $getData = $templatesModel->getPagedData(array(
                'where' => array(
                    'key' => 'tpl_id',
                    'value' => $search,
                ),
                'order' => array(
                    'key' => $selCol,
                    'dir' => $sortOrder,
                ),
                'start' => $start,
                'limit' => $length,
                'columns' => $melisTool->getSearchableColumns(),
                'date_filter' => array(),
            ));

            $tableData = $getData->toArray();
            for($ctr = 0; $ctr < count($tableData); $ctr++)
            {
                // apply text limits
                foreach($tableData[$ctr] as $vKey => $vValue)
                {
                    $tableData[$ctr][$vKey] = $melisTool->limitedText($vValue);
                }
                
                $tableData[$ctr]['DT_RowId'] = $tableData[$ctr]['tpl_id'];
                // instead of showing the Site ID, replace it with Site name
                $siteData = $tableSite->getEntryById($tableData[$ctr]['tpl_site_id']);
                $siteText = $siteData->current();
                $tableData[$ctr]['tpl_site_id'] = !empty($siteText->site_name) ? $siteText->site_name : '';
                
                // display controller and action in Controller Column
                $tableData[$ctr]['tpl_zf2_controller'] = !empty($tableData[$ctr]['tpl_zf2_action']) ? $tableData[$ctr]['tpl_zf2_controller'] . '/' . $tableData[$ctr]['tpl_zf2_action'] : $tableData[$ctr]['tpl_zf2_controller'];

            }
        }
    
        return new JsonModel(array(
            'draw' => (int) $draw,
            'recordsTotal' => $dataCount,
            'recordsFiltered' => $templatesModel->getTotalFiltered(),
            'data' => $tableData,
        ));

    
    }

    /**
     * -- UPDATE --
     * This will be used whenever you want to update an specific 
     * entry on your tool table.
     */
    public function updateTemplateDataAction()
    {
        $request = $this->getRequest();
        $status  = 0;
        $errors  = array();
        $textTitle = 'tr_tool_template_fm_update_title';
        $textMessage = '';

        $eventDatas = array();
        $this->getEventManager()->trigger('meliscms_template_save_start', $this, $eventDatas);
        
        // translator
        $translator = $this->getServiceLocator()->get('translator');
        
        // get the service for Templates Model & Table
        $templatesModel = $this->getServiceLocator()->get('MelisEngineTableTemplate');
        
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_templates');
        
        $templateUpdateForm = $melisTool->getForm('meliscms_tool_template_generic_form');
        
        // get the currently logged in user
        $melisCoreAuth = $this->serviceLocator->get('MelisCoreAuth');
        $userAuthDatas =  $melisCoreAuth->getStorage()->read();
        
        // make sure it's a POST call
        if($request->isPost())
        {
            
            $postValues = get_object_vars($this->getRequest()->getPost());
            
            $templateId = $request->getPost('tpl_id');
            $templateUpdateForm->setData($postValues);

            if($templateUpdateForm->isValid())
            {
                $data = $templateUpdateForm->getData();
                $data['tpl_creation_date'] = date('Y-m-d H:i:s');
                $data['tpl_last_user_id'] = $userAuthDatas->usr_id;
                $site = $data['tpl_site_id'];
                
                if($data['tpl_type'] == 'PHP') {
                    $phpPath = $data['tpl_php_path'];
                
                    if(empty($phpPath)) {
                        $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                        $status = 0;
                        $errors= array(
                            'tpl_php_path' => array(
                                'empty_path' => $translator->translate('tr_meliscms_template_form_tpl_path_error_empty')
                            ),
                        );
                    }
                    elseif(strlen($phpPath)>150) {
                        $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                        $status = 0;
                        $errors= array(
                            'tpl_php_path' => array(
                                'path_too_long' => $translator->translate('tr_meliscms_template_form_tpl_path_error_high')
                            ),
                        );
                    }
                    elseif(empty($site)){
                        $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                        $status = 0;
                        $errors= array(
                            'tpl_site_id' => array(
                                'invalid_selection' => $translator->translate('tr_meliscms_template_form_tpl_site_id_error_empty')
                            ),
                        );
                    }
                    else {
                        $templatesModel->save($data, $templateId);
                        $textMessage = 'tr_tool_template_fm_update_content';
                        $status = 1;
                    }
                }
                elseif($data['tpl_type'] == 'ZF2') {
                    $tmpError = array();
                
                    $tplLayout = $data['tpl_zf2_layout'];
                    $tplController = $data['tpl_zf2_controller'];
                    $tplAction = $data['tpl_zf2_action'];
                
                    if(empty($tplLayout)) {
                        $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                        $status = 0;
                        $errors= array(
                            'tpl_zf2_layout' => array(
                                'empty' => $translator->translate('tr_meliscms_template_form_tpl_layout_error_empty')
                            ),
                        );
                    }
                    elseif(strlen($tplLayout)>50) {
                        $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                        $status = 0;
                        $errors= array(
                            'tpl_zf2_layout' => array(
                                'too_long' => $translator->translate('tr_meliscms_template_form_tpl_layout_error_high')
                            ),
                        );
                    }
                
                
                    if(empty($tplController)) {
                        $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                        $status = 0;
                        $errors= array(
                            'tpl_zf2_controller' => array(
                                'empty' => $translator->translate('tr_meliscms_template_form_tpl_controller_error_empty')
                            ),
                        );
                    }
                    elseif(strlen($tplController)>50) {
                        $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                        $status = 0;
                        $errors= array(
                            'tpl_zf2_controller' => array(
                                'too_long' => $translator->translate('tr_meliscms_template_form_tpl_controller_error_high')
                            ),
                        );
                    }
                
                    if(empty($tplAction)) {
                        $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                        $status = 0;
                        $errors= array(
                            'tpl_zf2_action' => array(
                                'empty' => $translator->translate('tr_meliscms_template_form_tpl_action_error_empty')
                            ),
                        );
                    }
                    elseif(strlen($tplAction)>50) {
                        $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                        $status = 0;
                        $errors= array(
                            'tpl_zf2_action' => array(
                                'too_long' => $translator->translate('tr_meliscms_template_form_tpl_action_error_high')
                            ),
                        );
                    }
                    elseif(empty($site)){
                        $textMessage = $translator->translate('tr_tool_template_fm_new_content_error');
                        $status = 0;
                        $errors= array(
                            'tpl_site_id' => array(
                                'invalid_selection' => $translator->translate('tr_meliscms_template_form_tpl_site_id_error_empty')
                            ),
                        );
                    }
                
                    if(empty($errors)) {
                        $templatesModel->save($data, $templateId);
                        $textMessage = 'tr_tool_template_fm_update_content';
                        $status = 1;
                    }
                }


            }
            else {
                $textMessage = 'tr_tool_template_fm_update_content_error';
                $status = 0;
                $errors = $templateUpdateForm->getMessages();
            }
            
            // insert labels and error messages in error array
            $melisMelisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');
            $appConfigForm = $melisMelisCoreConfig->getItem('meliscms/tools/meliscms_tool_templates/forms/meliscms_tool_template_generic_form');
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
            'textTitle' => $translator->translate($textTitle),
            'textMessage' => $translator->translate($textMessage),
            'errors' => $errors,
        );
        
        $this->getEventManager()->trigger('meliscms_template_save_end', $this, $response);
        
        return new JsonModel($response);
    }
    
    /**
     * -- DELETE -- 
     * Deletes an specific entry in your tool table depending on the
     * ID provided.
     */
    public function deleteTemplateDataAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        
    	$eventDatas = array();
    	$this->getEventManager()->trigger('meliscms_template_delete_start', $this, $eventDatas);
    	
        
        $request = $this->getRequest();
        $status  = false;
        $message = 'no data';
        $textMessage = '';
        $textTitle = 'tr_tool_template_fm_update_title';
        // make sure it's a POST call
        if($request->isPost()) {
            
            // get the service for Templates Model & Table
            $templatesModel = $this->getServiceLocator()->get('MelisEngineTableTemplate');
            $templateId = $request->getPost('templateId');
            
            // make sure our ID is not empty
            if(!empty($templateId))
            {
                $templatesModel->deleteById($templateId);
                $status = true;
                $message = $templateId . ' successfully deleted';
                $textMessage = 'tr_tool_template_fm_delete_content';
            }
        }

        $response = array(
           'success' => $status ,
            'textTitle' => $translator->translate($textTitle),
            'textMessage' => $translator->translate($textMessage)
        );
        $this->getEventManager()->trigger('meliscms_template_delete_end', $this, $response);
        
        return new JsonModel($response);
            
    }
    
    /**
     * Returns all information of the specific template data
     */
    public function getTemplateDataByIdAction() 
    {
        $request = $this->getRequest();
        $data    = array();
        
        if($request->isPost()) 
        {
            $templatesModel = $this->getServiceLocator()->get('MelisEngineTableTemplate');
            $templateId = $request->getPost('templateId');
            
            if(is_numeric($templateId))
                $data = $templatesModel->getEntryById($templateId);
        }
        
        return new JsonModel($data);
    }
    
    public function exportToCsvAction()
    {
        $templatesModel = $this->getServiceLocator()->get('MelisEngineTableTemplate');
        $translator = $this->getServiceLocator()->get('translator');
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_templates');
    
    
        $searched = $this->getRequest()->getQuery('filter');
        $columns  = $melisTool->getSearchableColumns();
        $data = $templatesModel->getDataForExport($searched, $columns);
    
        return $melisTool->exportDataToCsv($data->toArray());
    }
    
    
    /**
     * Checks wether the user has access to this tools or not
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

}