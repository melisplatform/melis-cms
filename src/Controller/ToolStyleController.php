<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use MelisCore\Controller\MelisAbstractActionController;
use MelisCore\Service\MelisCoreRightsService;
use Laminas\Session\Container;
/**
 *
 * Cmys Styles Manager Tool Plugin
 *
 */
class ToolStyleController extends MelisAbstractActionController
{
    /**
     * This constant variable will map to the app.tool.php configuration file
     * using the corresponding values
     */
    const TOOL_TEMPLATES_CONFIG_PATH = 'meliscms/tools/meliscms_tool_styles';
    const TOOL_KEY = 'meliscms_tool_styles';

    /**
     * This is the main view of the Tool,
     * View File Name: render-tool-template-manager.phtml
     *
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolStyleAction()
    {
        $translator = $this->getServiceManager()->get('translator');
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $noAccessPrompt = '';
        // Checks wether the user has access to this tools or not
        $melisCoreRights = $this->getServiceManager()->get('MelisCoreRights');
        if(!$melisCoreRights->canAccess($this::TOOL_KEY)) {
            $noAccessPrompt = $translator->translate('tr_tool_no_access');
        }

        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');

        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_styles');


        $view = new ViewModel();

        $view->melisKey = $melisKey;
        $view->title = $melisTool->getTitle();;
        $view->noAccess  = $noAccessPrompt;

        return $view;
    }

    /**
     * This is where you place your buttons for the tools
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolStyleHeaderAction()
    {
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');

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
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolStyleHeaderAddAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();
        $view->melisKey = $melisKey;

        return $view;
    }

    /**
     * Renders to the Limit selection in the filter bar in the datatable
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolStyleContentFiltersLimitAction()
    {
        return new ViewModel();
    }

    /**
     * Renders to the search input in the filter bar in the datatable
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolStyleContentFiltersSearchAction()
    {
        return new ViewModel();
    }

    /**
     * Renders to the refresh button in the filter bar in the datatable
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolStyleContentFiltersRefreshAction()
    {
        return new ViewModel();
    }

    /**
     * displays the content of the tool
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolStyleContentAction()
    {

        $melisKey = $this->params()->fromRoute('melisKey', '');

        $translator = $this->getServiceManager()->get('translator');


        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');

        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_styles');

        $view = new ViewModel();

        // get the columns that has been set in the configuration (app.tools.php)
        $columns = $melisTool->getColumns();

        // add an extra column which is Action
        $columns['actions'] = array('text' => $translator->translate('tr_meliscms_action'), 'css' => 'width:10%');

        // get the column texts set, this will be used in the thead table
        $view->tableColumns = $columns;

        $view->melisKey = $melisKey;

        $view->getToolDataTableConfig = $melisTool->getDataTableConfiguration();

        return $view;
    }

    /**
     * Renders to the edit button in the table
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolStyleActionEditAction()
    {

        return new ViewModel();
    }

    /**
     * Renders to the delete button in the table
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolStyleActionDeleteAction()
    {

        return new ViewModel();
    }

    /**
     * The parent container of all modals, this is where you initialze your modal.
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolStyleModalContainerAction()
    {

        $id = $this->params()->fromQuery('id');
        $view = new ViewModel();
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $view->melisKey = $melisKey;
        $view->id = $id;
        return $view;
    }


    /**
     * Renders the form Tab and Content for the modal
     * @return \MelisCms\Controller\ViewModel
     */
    public function renderToolStyleModalFormHandlerAction()
    {
        $data = array();

        $styleId = (int) $this->params()->fromQuery('styleId', '');

        $melisKey = $this->params()->fromRoute('melisKey', '');
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        $melisCoreConfig = $this->getServiceManager()->get('MelisCoreConfig');
        $appConfigForm = $melisCoreConfig->getFormMergedAndOrdered('meliscms/tools/meliscms_tool_styles/forms/meliscms_tool_styles_form','meliscms_tool_styles_form');
        $factory = new \Laminas\Form\Factory();
        $formElements = $this->getServiceManager()->get('FormElementManager');
        $factory->setFormElementManager($formElements);
        $form = $factory->createForm($appConfigForm);

        // set title
        $title    = $melisTool->getTranslation('tr_meliscore_tool_styles_new');

        if(!empty($styleId)){

            $tableStyle = $this->getServiceManager()->get('MelisEngineTableStyle');
            $details = $tableStyle->getEntryById($styleId)->current();

            $data = (array)$details;
            $title = $melisTool->getTranslation('tr_meliscore_tool_styles_edit');

        }else{
            $form->get('style_id')->setAttribute('class', 'hidden');
            $form->get('style_id')->setOptions(array('label_attributes' => array('class'  => 'hidden')));
        }

        $form->setData($data);

        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->form = $form;
        $view->title = $title;
        $view->styleId = $styleId;
        return $view;
    }

    /**
     * Retrieves datatable's content
     * @return JsonModel
     */
    public function getStyleDataAction()
    {
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');

        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_styles');

        // Site Table
        /**
         * @var \MelisEngine\Model\Tables\MelisCmsStyleTable $tableStyle
         */
        $tableStyle = $this->getServiceManager()->get('MelisEngineTableStyle');

        $colId = [];
        $dataCount = 0;
        $draw = 0;
        $tableData = [];

        // make sure that the request is an AJAX call
        if ($this->getRequest()->isPost()) {
            $optionFilter = [];

            // get the tool columns
            $columns = $melisTool->getColumns();

            $colId = array_keys($melisTool->getColumns());

            $sortOrder = $this->getRequest()->getPost('order');
            $sortOrder = $sortOrder[0]['dir'];

            $selCol = $this->getRequest()->getPost('order');
            $selCol = $colId[$selCol[0]['column']];

            $draw = $this->getRequest()->getPost('draw');

            $start = $this->getRequest()->getPost('start');
            $length = $this->getRequest()->getPost('length');

            $search = $this->getRequest()->getPost('search');
            $search = $search['value'];

            $limit = $this->getRequest()->getPost('length');

            $styleData = $tableStyle->getStyleList($search, $melisTool->getSearchableColumns(), $selCol, $sortOrder, $start, $limit);
            /**
             * // $dataCount = $tableStyle->getStyleList($search,$melisTool->getSearchableColumns(),$selCol,$sortOrder);
             * Instead of a separate query just to get the data count, the count is now done in one db call.
             *
             * $dataCount is the integer used by the DataTable plugin as the total no. of entries to be shown
             * in a single pagination based on the $length variable/limit.
             */
            $dataCount = $styleData->getObjectPrototype()->getFilteredDataCount();

            /** $dataFilteredCount is the integer used by the Datatable plugin as the total no. of rows a table have. */
            $dataFilteredCount = $styleData->getObjectPrototype()->getUnfilteredDataCount();

            $tableData = $styleData->toArray();
            for ($ctr = 0; $ctr < count($tableData); $ctr++) {
                // Append style file status in the tables data
                $fileStatus = $this->getFilesStatus($tableData[$ctr]['style_path']);
                $tableData[$ctr]['style_files'] = '<span class="text-' . ($fileStatus ? 'success' : 'danger') . '"><i class="fa fa-fw fa-circle"></i></span>';

                // apply text limits
                foreach ($tableData[$ctr] as $vKey => $vValue) {
                    $tableData[$ctr][$vKey] = $vValue;
                    if ($vKey == 'style_status') {
                        $status = '<span class="text-success"><i class="fa fa-fw fa-circle"></i></span>';
                        if (!$vValue) {
                            $status = '<span class="text-danger"><i class="fa fa-fw fa-circle"></i></span>';
                        }

                        $tableData[$ctr][$vKey] = $status;
                    }
                }

                $tableData[$ctr]['DT_RowId'] = $tableData[$ctr]['style_id'];
            }
        }

        return new JsonModel([
            'draw' => (int)$draw,
            'recordsTotal' => $dataCount,
            'recordsFiltered' => $dataFilteredCount,
            'data' => $tableData,
        ]);
    }

    /**
     * Returns true: style File exists in the specified path
     * otherwise, false
     * @param string $filepath
     * @return bool
     */
    protected function getFilesStatus(string $filepath): bool
    {
        $status = false;
        $path = explode('/', $filepath);

        if (empty($path[0])) {
            // Internal Path
            $site = $path[1];
            $acceptables = ['css', 'CSS'];
            $filename = $path[count($path) - 1];

            // check if style file has correct extension
            $nameParts = explode('.', $filename);
            if (!in_array($nameParts[count($nameParts) - 1], $acceptables)) {
                return false;
            }

            // Getting the subfolder(s)
            $subfolders = '';
            for ($i = 2; $i <= count($path) - 2; $i++) {
                $subfolders .= $path[$i] . '/';
            }

            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites/' . $site . '/public/' . $subfolders . $filename;
            $fullVendorPath = $_SERVER['DOCUMENT_ROOT'] . '/../vendor/melisplatform/' . $site . '/public/' . $subfolders . $filename;

            if ((is_file($fullPath) && file_exists($fullPath))
                || is_file($fullVendorPath) && file_exists($fullVendorPath)) {
                return true;
            }
        } else {
            // External Path
            if (filter_var($filepath, FILTER_VALIDATE_URL)) {
                $header_response = get_headers($filepath, 1);
                if (strpos($header_response[0], "200") !== false) {
                    // File exists
                    return true;
                }
            }
        }

        return $status;
    }

    /** TOOL CRUD
     *  Below are the functions that will be used in
     *  Adding, updating, displaying and deleting an entry
     *  in our tool table. Most of the functions are triggered
     *  through AJAX call.
     */

    /**
     * -- CREATE || UPDATE --
     * Inserts or updates your style in style table
     */
    public function saveStyleDetailsAction()
    {

        $response = array();
        $success = 0;
        $errors  = array();
        $data = array();
        $styleId = null;
        $textMessage = 'tr_meliscms_tool_styles_save_fail';
        $textTitle = 'tr_meliscms_tool_styles';

        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        $styleService = $this->getServiceManager()->get('MelisEngineStyle');

        $melisCoreConfig = $this->getServiceManager()->get('MelisCoreConfig');
        $appConfigForm = $melisCoreConfig->getFormMergedAndOrdered('meliscms/tools/meliscms_tool_styles/forms/meliscms_tool_styles_form','meliscms_tool_styles_form');
        $factory = new \Laminas\Form\Factory();
        $formElements = $this->getServiceManager()->get('FormElementManager');
        $factory->setFormElementManager($formElements);
        $form = $factory->createForm($appConfigForm);

        $postValues = $this->getRequest()->getPost()->toArray();

        if($this->getRequest()->isPost()){

            $this->getEventManager()->trigger('meliscms_style_save_details_start', $this, array());

            $postValues = $this->getRequest()->getPost()->toArray();
            $postValues = $melisTool->sanitizePost($postValues);

            if (!empty($postValues['style_id'])){
                $logTypeCode = 'CMS_STYLE_DETAILS_UPDATE';
            }else{
                $logTypeCode = 'CMS_STYLE_DETAILS_ADD';
            }

            // set form data
            $form->setData($postValues);

            //validation
            if($form->isValid()) {

                $data = $form->getData();
                $data['style_status'] = $data['style_status'] ?? 0;

                $styleId = !empty($data['style_id'])? $data['style_id'] : null;
                unset($data['style_id']);

                $data['style_status'] = ($data['style_status']) ? 1 : 0;

                $resultId = $styleService->saveStyle($data, $styleId);

                if(!empty($resultId)){
                    $styleId = $resultId;
                    $success = 1;
                    $textMessage = 'tr_meliscms_tool_styles_save_success';
                }

            }
            else {

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

        $this->getEventManager()->trigger('meliscms_style_save_details_end', $this, array_merge($response, array('typeCode' => $logTypeCode, 'itemId' => $styleId)));
        return new JsonModel($response);
    }

    public function savePageStyleAction()
    {
        $errors = array();
        $success = 1;
        $result = array();
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $isNew = $this->params()->fromRoute('isNew', $this->params()->fromQuery('isNew', ''));
        $translator = $this->getServiceManager()->get('translator');
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        $styleService = $this->getServiceManager()->get('MelisEngineStyle');
        $pageStyleTable = $this->getServiceManager()->get('MelisEngineTablePageStyle');

        // Check if post
        $request = $this->getRequest();

        if ($request->isPost())
        {

            if(!isset($postValues['page_duplicate_event_flag'])) {
                $postValues = $request->getPost()->toArray();
                $postValues = $melisTool->sanitizePost($postValues);
            }

            if(!empty($postValues['style_id'])){

                $pageStyle = $pageStyleTable->getEntryByField('pstyle_page_id', $idPage)->current();
                $pageStyleId = null;

                if(!empty($pageStyle)){
                    $pageStyleId = $pageStyle->pstyle_id;
                }

                $pageStyleData = array(
                    'pstyle_page_id' => $idPage,
                    'pstyle_style_id' => $postValues['style_id'],
                );

                $res = $pageStyleTable->save($pageStyleData, $pageStyleId);

                if(empty($res)){
                    $errors = array(
                        'style_id' => array(
                            'errorMessage' => $translator->translate('tr_meliscms_page_form_page_p_lang_id_invalid'),
                            'label' => $translator->translate('tr_meliscms_tool_styles_save_page_style_fail'),
                        ),
                    );
                }else{
                    $success = 1;
                }
            }else{
                // if a style has been unassigned, remove style from db
                $pageStyleTable->deleteByField('pstyle_page_id', $idPage);
                $success = 1;
            }

            $result = array(
                'success' => $success,
                'errors' => array($errors)
            );
        }

        return new JsonModel($result);
    }

    /**
     * -- DELETE --
     * Deletes an specific entry in your tool table depending on the
     * ID provided.
     */
    public function deleteStyleAction()
    {
        $translator = $this->getServiceManager()->get('translator');

        $eventDatas = array();
        $this->getEventManager()->trigger('meliscms_style_delete_start', $this, $eventDatas);

        $request = $this->getRequest();
        $templateId = null;
        $status  = 0;
        $message = 'no data';
        $textMessage = 'tr_meliscms_tool_styles_delete_fail';
        $textTitle = 'tr_meliscms_tool_styles';
        // make sure it's a POST call
        if($request->isPost()) {

            // get the service for Templates Model & Table
            $tableStyle = $this->getServiceManager()->get('MelisEngineTableStyle');
            $styleId = (int) $request->getPost('styleId');

            // make sure our ID is not empty
            if(!empty($styleId))
            {
                $tableStyle->deleteById($styleId);
                $status = 1;
                $textMessage = 'tr_meliscms_tool_styles_delete_success';
            }
        }

        $response = array(
            'success' => $status ,
            'textTitle' => $textTitle,
            'textMessage' => $textMessage
        );

        $this->getEventManager()->trigger('meliscms_style_delete_end', $this, array_merge($response, array('typeCode' => 'CMS_STYLE_DELETE', 'itemId' => $styleId)));

        return new JsonModel($response);
    }

    /**
     *  Return style of a page
     *
     * return array
     */
    public function getStyleByPageId($pageId)
    {
        $style = "";

        $pageStyle  = $this->getServiceManager()->get('MelisPageStyle');
        if($pageStyle){
            $dataStyle  = $pageStyle->getStyleByPageId($pageId);

            $style = $dataStyle;
        }

        return $style;
    }
}
