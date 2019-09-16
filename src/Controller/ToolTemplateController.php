<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Zend\EventManager\ResponseCollection;
use Zend\Form\Element\Select;
use Zend\Form\Factory;
use Zend\Form\Form;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

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
    const TEMPLATE_FORM = 'meliscms/tools/meliscms_tool_templates/forms/meliscms_tool_template_generic_form';
    const TEMPLATE_FORM_CONFIG_MODIFY = 'meliscms_template_form_config';

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
        // Checks wether the user has access to this tools or not
        $melisCoreRights = $this->getServiceLocator()->get('MelisCoreRights');
        if(!$melisCoreRights->canAccess($this::TOOL_KEY)) {
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
     * Renders to the site filter selection in the filter bar in the datatable
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolTemplateContentFiltersSitesAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');

        $sites = array();
        $sites[] = '<option value="">'. $translator->translate('tr_meliscms_tool_templates_tpl_label_choose') .'</option>';

       foreach($siteTable->fetchAll() as $site){
           $siteName = !empty($site->site_label) ? $site->site_label : $site->site_name;
           $sites[] = '<option value="'.$site->site_id.'">'. $siteName .'</option>';
       }

       $view = new ViewModel();
       $view->sites = $sites;
       return $view;
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

        $view->getToolDataTableConfig = $melisTool->getDataTableConfiguration('#tableToolTemplateManager',false,false,array('order' => '[[ 0, "desc" ]]'));

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
        /**
         * Template form creation
         * @var Form $form
         */
        $melisConfig = $this->getServiceLocator()->get('MelisCoreConfig');
        $factory = new Factory();
        $formElementMgr = $this->getServiceLocator()->get('FormElementManager');
        $factory->setFormElementManager($formElementMgr);
        $formConfig = $melisConfig->getItem(self::TEMPLATE_FORM);

        /**
         * Trigger listeners trying to modify the form config before form creation
         *  @var \Zend\EventManager\ResponseCollection $result
         */
        $result = $this->getEventManager()->trigger(self::TEMPLATE_FORM_CONFIG_MODIFY, $this, ['formConfig' => $formConfig]);
        $formConfig = $result instanceof ResponseCollection && $result->count() > 0 ? $result->last() : $formConfig;

        $form = $factory->createForm($formConfig);

        if ($form->get('tpl_type') instanceof Select) {
            /** Set default template type */
            $form->get('tpl_type')->setValue('ZF2');
        }

        $view = new ViewModel();
        $view->setVariable('meliscms_tool_template_add', $form);

        return $view;
    }

    /**
     * This will be used to render the edit form in the modal tab
     */
    public function modalTabToolTemplateEditAction()
    {
        /**
         * Template form creation
         * @var Form $form
         */
        $melisConfig = $this->getServiceLocator()->get('MelisCoreConfig');
        $factory = new Factory();
        $formElementMgr = $this->getServiceLocator()->get('FormElementManager');
        $factory->setFormElementManager($formElementMgr);
        $formConfig = $melisConfig->getItem(self::TEMPLATE_FORM);

        /**
         * Trigger listeners trying to modify the form config before form creation
         *  @var \Zend\EventManager\ResponseCollection $result
         */
        $result = $this->getEventManager()->trigger(self::TEMPLATE_FORM_CONFIG_MODIFY, $this, ['formConfig' => $formConfig]);
        $formConfig = $result instanceof ResponseCollection && $result->count() > 0 ? $result->last() : $formConfig;

        $form = $factory->createForm($formConfig);

        if ($form->get('tpl_type') instanceof Select) {
            /** Set default template type */
            $form->get('tpl_type')->setValue('ZF2');
        }

        $view = new ViewModel();
        $view->setVariable('meliscms_tool_template_edit', $form);

        return $view;
    }

    /**
     * Return template of a page
     *
     * return array
     */
    public function getTemplateByPageIdAction()
    {
        $success = 0;
        $request = $this->getRequest();
        $data    = array();
        if($request->isPost())
        {
            $template = $this->getServiceLocator()->get("MelisPageTemplate");
            $success = 2;

            $data = $template->getTemplate($pageId);
        }

        return $data;

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
        $tplId = null;
        $status  = 0;
        $errors  = array();
        $textTitle = '';
        $textMessage = '';

        // translator
        $translator = $this->getServiceLocator()->get('translator');

        $eventDatas = array();
        $this->getEventManager()->trigger('meliscms_template_savenew_start', $this, $eventDatas);

        // get the service for Templates Model & Table
        $templatesModel = $this->getServiceLocator()->get('MelisEngineTableTemplate');
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
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
            $postValues = $melisTool->sanitizePost($postValues);
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
                    $tplId = $datasPlatformIds->pids_tpl_id_current;
                    $data['tpl_id'] = $tplId;

                    $site = $data['tpl_site_id'];
                    $data['tpl_creation_date'] = date('Y-m-d H:i:s');
                    $data['tpl_last_user_id'] = $userAuthDatas->usr_id;

                    $siteData = $siteTable->getEntryById($site)->current();
                    $data['tpl_zf2_website_folder'] = !empty($siteData->site_name)? $siteData->site_name : '';

                    if($data['tpl_type'] == 'PHP') {
                        $phpPath = $data['tpl_php_path'];

                        if(empty($phpPath)) {
                            $textMessage = 'tr_tool_template_fm_new_content_error';
                            $status = 0;
                            $errors= array(
                                'tpl_php_path' => array(
                                    'empty_path' => $translator->translate('tr_meliscms_template_form_tpl_path_error_empty')
                                ),
                            );
                        }
                        elseif(strlen($phpPath)>150) {
                            $textMessage = 'tr_tool_template_fm_new_content_error';
                            $status = 0;
                            $errors= array(
                                'tpl_php_path' => array(
                                    'path_too_long' => $translator->translate('tr_meliscms_template_form_tpl_path_error_high')
                                ),
                            );
                        }
                        elseif(empty($site)){
                            $textMessage = 'tr_tool_template_fm_new_content_error';
                            $status = 0;
                            $errors= array(
                                'tpl_site_id' => array(
                                    'invalid_selection' => $translator->translate('tr_meliscms_template_form_tpl_site_id_error_empty')
                                ),
                            );
                        }
                        else {
                            $templatesModel->save($data);
                            $textMessage = 'tr_tool_template_fm_new_content';
                            $status = 1;
                        }
                    }
                    elseif($data['tpl_type'] == 'ZF2' || $data['tpl_type'] == 'TWG') {
                        $tmpError = array();

                        $tplLayout = $data['tpl_zf2_layout'];
                        $tplController = $data['tpl_zf2_controller'];
                        $tplAction = $data['tpl_zf2_action'];


                        if(empty($tplLayout)) {
                            $textMessage = 'tr_tool_template_fm_new_content_error';
                            $status = 0;
                            $errors= array(
                                'tpl_zf2_layout' => array(
                                    'empty' => $translator->translate('tr_meliscms_template_form_tpl_layout_error_empty')
                                ),
                            );
                        }
                        elseif(strlen($tplLayout)>50) {
                            $textMessage = 'tr_tool_template_fm_new_content_error';
                            $status = 0;
                            $errors= array(
                                'tpl_zf2_layout' => array(
                                    'too_long' => $translator->translate('tr_meliscms_template_form_tpl_layout_error_high')
                                ),
                            );
                        }


                        if(empty($tplController)) {
                            $textMessage = 'tr_tool_template_fm_new_content_error';
                            $status = 0;
                            $errors= array(
                                'tpl_zf2_controller' => array(
                                    'empty' => $translator->translate('tr_meliscms_template_form_tpl_controller_error_empty')
                                ),
                            );
                        }
                        elseif(strlen($tplController)>50) {
                            $textMessage = 'tr_tool_template_fm_new_content_error';
                            $status = 0;
                            $errors= array(
                                'tpl_zf2_controller' => array(
                                    'too_long' => $translator->translate('tr_meliscms_template_form_tpl_controller_error_high')
                                ),
                            );
                        }

                        if(empty($tplAction)) {
                            $textMessage = 'tr_tool_template_fm_new_content_error';
                            $status = 0;
                            $errors= array(
                                'tpl_zf2_action' => array(
                                    'empty' => $translator->translate('tr_meliscms_template_form_tpl_action_error_empty')
                                ),
                            );
                        }
                        elseif(strlen($tplAction)>50) {
                            $textMessage = 'tr_tool_template_fm_new_content_error';
                            $status = 0;
                            $errors= array(
                                'tpl_zf2_action' => array(
                                    'too_long' => $translator->translate('tr_meliscms_template_form_tpl_action_error_high')
                                ),
                            );
                        }
                        elseif(empty($site)){
                            $textMessage ='tr_tool_template_fm_new_content_error';
                            $status = 0;
                            $errors= array(
                                'tpl_site_id' => array(
                                    'invalid_selection' => $translator->translate('tr_meliscms_template_form_tpl_site_id_error_empty')
                                ),
                            );
                        }


                        if(empty($errors)) {
                            $templatesModel->save($data);
                            $textMessage = 'tr_tool_template_fm_new_content';
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
                    $textMessage = 'tr_tool_template_fm_new_content_error';
                    $errors = array(
                        'platform_ids' => array(
                            'noPlatformIds' => $translator->translate('tr_meliscms_no_available_platform_ids'),
                            'label' => $translator->translate('tr_meliscms_tool_platform_ids'),
                        ),
                    );
                }
            }
            else {
                $textMessage = 'tr_tool_template_fm_new_content_error';
                $errors = $templateUpdateForm->getMessages();
                $status = 0;
            }

            // insert labels and error messages in error array
            $melisMelisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');
            $appConfigForm = $melisMelisCoreConfig->getItem(self::TEMPLATE_FORM);
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
            'textTitle' => 'tr_tool_templates_modal_tab_text_add',
            'textMessage' => $textMessage,
            'errors' => $errors,
        );

        $this->getEventManager()->trigger('meliscms_template_savenew_end', $this, array_merge($response, array('typeCode' => 'CMS_TEMPLATE_ADD', 'itemId' => $tplId)));

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

            $siteId = $this->getRequest()->getPost('tpl_site_id');
            $siteId = !empty($siteId)? $siteId : null;

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

            $getData = $templatesModel->getData($search, $siteId, $melisTool->getSearchableColumns(), $selCol, $sortOrder, $start, $length);
            $tableData = $getData->toArray();

            for($ctr = 0; $ctr < count($tableData); $ctr++)
            {
                // apply text limits
                foreach($tableData[$ctr] as $vKey => $vValue)
                {
                    $tableData[$ctr][$vKey] = $melisTool->limitedText($vValue);
                }

                $tableData[$ctr]['DT_RowId'] = $tableData[$ctr]['tpl_id'];

                // Append Template status: Green Circle = Active || Red Circle = Inctive
                try {
                    $tpl = [
                        'module'        => $tableData[$ctr]['tpl_zf2_website_folder'],
                        'controller'    => $tableData[$ctr]['tpl_zf2_controller'],
                        'action'        => $tableData[$ctr]['tpl_zf2_action']
                    ];
                    $tpl_status                     = $this->getTemplateStatus($tpl);
                    $tableData[$ctr]['tpl_status']  = '<span class="text-'.($tpl_status? 'success' : 'danger').'"><i class="fa fa-fw fa-circle"></i></span>';
                } catch (\Exception $e) {
                    // Place handling here
                }

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
     * Returns true: Module, & Controller, & Action exists
     * otherwise, false
     * @return bool
     */
    private function getTemplateStatus(array $template) : bool
    {
        $status      = false;
        $fromMelisSites = false;

        $moduleSrv = $this->getServiceLocator()->get('ModulesService');
        $modulePath = $moduleSrv->getModulePath($template['module']);

        /**
         * if path is still empty,
         * try to get it inside MelisSites
         */
        if(empty($modulePath)){
            $modulePath = $moduleSrv->getUserSitePath($template['module']);
            $fromMelisSites = true;
        }

        if (!empty($modulePath)){

            $viewPath = $modulePath.'/view/'.$this->moduleNameToViewName($template['module']);
            $ctrlPath = ($fromMelisSites) ? $modulePath.'/src/'.$template['module'].'/Controller' : $modulePath.'/src/Controller';

            if (strpos($modulePath, '/module/') != false){
                $ctrlPath = ($fromMelisSites) ? $modulePath.'/src/'.$template['module'].'/Controller' : $modulePath.'/'.$template['module'].'/src/Controller';
            }

            $ctrlFile = $ctrlPath.'/'.$template['controller'].'Controller.php';

            if (file_exists($ctrlFile)){

                $ctrlFileContent = file_get_contents($ctrlFile);

                // Check if template's Action exists
                $actionPattern = '/function.*'.$template['action'].'Action/';
                if (preg_match($actionPattern, $ctrlFileContent)) {

                    $viewFile = $viewPath . '/' . $this->moduleNameToViewName($template['controller']) . '/' . $this->moduleNameToViewName($template['action']);

                    // Template Manager can look for additional view file types that are added here
                    if (file_exists($viewFile . '.phtml') || file_exists($viewFile . '.twig')) {
                        $status = true;
                    }
                }
            }
        }

        return $status;
    }

    /**
     * This method converting a Module name to a valid view name  directory
     * @param $string - Module name
     * @return string
     */
    private function moduleNameToViewName($string)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
    }

    /**
     * Returns the list of Modules (MelisSites)
     * @return array
     */
    protected function getSiteModules() : array
    {
        $modules    = [];
        $modulePath = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites';

        if (is_dir($modulePath)){
            foreach (scandir($modulePath) as $module) {
                // Skip directory pointers
                if ($module == '.' || $module == '..') continue;

                if (is_dir($modulePath . '/' . $module)) {
                    $modules[$module] = $this->getControllers($modulePath . '/' . $module . '/src/' . $module . '/Controller');
                }
            }
        }

        return $modules;
    }

    /**
     * Returns a module's controllers
     * @return array
     */
    protected function getControllers(string $controllersPath) : array
    {
        $controllers = [];

        // List all controller files inside the ..src/controller folder
        if (is_dir($controllersPath)) {
            foreach (scandir($controllersPath) as $controller) {
                // Skip directory pointers
                if ($controller == '.' || $controller == '..' || is_dir($controllersPath . '/' . $controller)) {
                    continue;
                }

                // Check if file's extension is ".php"
                $filePath   = $controllersPath . '/' . $controller;
                $fileExt    = pathinfo($filePath)['extension'];
                if ($fileExt == 'php' && is_file($filePath)) {
                    $controllers[] = $controller;
                }
            }
        }

        return $controllers;
    }

    /**
     * -- UPDATE --
     * This will be used whenever you want to update an specific
     * entry on your tool table.
     */
    public function updateTemplateDataAction()
    {
        $request = $this->getRequest();
        $templateId = null;
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
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
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
            $postValues = $melisTool->sanitizePost($postValues);

            $templateId = (int) $request->getPost('tpl_id');
            $templateUpdateForm->setData($postValues);

            if($templateUpdateForm->isValid())
            {
                $data = $templateUpdateForm->getData();
                $data['tpl_creation_date'] = date('Y-m-d H:i:s');
                $data['tpl_last_user_id'] = $userAuthDatas->usr_id;
                $site = $data['tpl_site_id'];

                $siteData = $siteTable->getEntryById($site)->current();
                $data['tpl_zf2_website_folder'] = !empty($siteData->site_name)? $siteData->site_name : '';

                if($data['tpl_type'] == 'PHP') {
                    $phpPath = $data['tpl_php_path'];

                    if(empty($phpPath)) {
                        $textMessage = 'tr_tool_template_fm_new_content_error';
                        $status = 0;
                        $errors= array(
                            'tpl_php_path' => array(
                                'empty_path' => $translator->translate('tr_meliscms_template_form_tpl_path_error_empty')
                            ),
                        );
                    }
                    elseif(strlen($phpPath)>150) {
                        $textMessage = 'tr_tool_template_fm_new_content_error';
                        $status = 0;
                        $errors= array(
                            'tpl_php_path' => array(
                                'path_too_long' => $translator->translate('tr_meliscms_template_form_tpl_path_error_high')
                            ),
                        );
                    }
                    elseif(empty($site)){
                        $textMessage = 'tr_tool_template_fm_new_content_error';
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
                elseif($data['tpl_type'] == 'ZF2' || $data['tpl_type'] == 'TWG') {
                    $tmpError = array();

                    $tplLayout = $data['tpl_zf2_layout'];
                    $tplController = $data['tpl_zf2_controller'];
                    $tplAction = $data['tpl_zf2_action'];

                    if(empty($tplLayout)) {
                        $textMessage = 'tr_tool_template_fm_new_content_error';
                        $status = 0;
                        $errors= array(
                            'tpl_zf2_layout' => array(
                                'empty' => $translator->translate('tr_meliscms_template_form_tpl_layout_error_empty')
                            ),
                        );
                    }
                    elseif(strlen($tplLayout)>50) {
                        $textMessage = 'tr_tool_template_fm_new_content_error';
                        $status = 0;
                        $errors= array(
                            'tpl_zf2_layout' => array(
                                'too_long' => $translator->translate('tr_meliscms_template_form_tpl_layout_error_high')
                            ),
                        );
                    }


                    if(empty($tplController)) {
                        $textMessage = 'tr_tool_template_fm_new_content_error';
                        $status = 0;
                        $errors= array(
                            'tpl_zf2_controller' => array(
                                'empty' => $translator->translate('tr_meliscms_template_form_tpl_controller_error_empty')
                            ),
                        );
                    }
                    elseif(strlen($tplController)>50) {
                        $textMessage = 'tr_tool_template_fm_new_content_error';
                        $status = 0;
                        $errors= array(
                            'tpl_zf2_controller' => array(
                                'too_long' => $translator->translate('tr_meliscms_template_form_tpl_controller_error_high')
                            ),
                        );
                    }

                    if(empty($tplAction)) {
                        $textMessage = 'tr_tool_template_fm_new_content_error';
                        $status = 0;
                        $errors= array(
                            'tpl_zf2_action' => array(
                                'empty' => $translator->translate('tr_meliscms_template_form_tpl_action_error_empty')
                            ),
                        );
                    }
                    elseif(strlen($tplAction)>50) {
                        $textMessage = 'tr_tool_template_fm_new_content_error';
                        $status = 0;
                        $errors= array(
                            'tpl_zf2_action' => array(
                                'too_long' => $translator->translate('tr_meliscms_template_form_tpl_action_error_high')
                            ),
                        );
                    }
                    elseif(empty($site)){
                        $textMessage = 'tr_tool_template_fm_new_content_error';
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
            $appConfigForm = $melisMelisCoreConfig->getItem(self::TEMPLATE_FORM);
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
            'textTitle' => $textTitle,
            'textMessage' => $textMessage,
            'errors' => $errors,
        );

        $this->getEventManager()->trigger('meliscms_template_save_end', $this, array_merge($response, array('typeCode' => 'CMS_TEMPLATE_UPDATE', 'itemId' => $templateId)));

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
        $templateId = null;
        $status  = false;
        $message = 'no data';
        $textMessage = '';
        $textTitle = 'tr_tool_template_fm_update_title';
        // make sure it's a POST call
        if($request->isPost()) {

            // get the service for Templates Model & Table
            $templatesModel = $this->getServiceLocator()->get('MelisEngineTableTemplate');
            $templateId = (int) $request->getPost('templateId');

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
            'textTitle' => $textTitle,
            'textMessage' => $textMessage
        );

        $this->getEventManager()->trigger('meliscms_template_delete_end', $this, array_merge($response, array('typeCode' => 'CMS_TEMPLATE_DELETE', 'itemId' => $templateId)));

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


        //remove the sitename from the where clause to avoid error since it doesn't exist in the template table
        for($i = 0; $i < sizeof($columns); $i++)
        {
            if($columns[$i] == 'site_name'){
                unset($columns[$i]);
            }
        }

        $data = $templatesModel->getDataForExport($searched, $columns);

        return $melisTool->exportDataToCsv($data->toArray());
    }
}