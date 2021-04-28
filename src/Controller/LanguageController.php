<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;


use Laminas\Session\Container;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use MelisCore\Controller\MelisAbstractActionController;

/**
 * This class deals with the languages button in the header
 */
class LanguageController extends MelisAbstractActionController
{
	const TOOL_INDEX = 'meliscms';
    const TOOL_KEY = 'meliscms_language_tool';
    const INTERFACE_KEY = 'meliscms_tool_language';
    

    public function renderToolLanguageContainerAction()
    {
        $translator = $this->getServiceManager()->get('translator');
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $noAccessPrompt = '';
        // Checks wether the user has access to this tools or not
        $melisCoreRights = $this->getServiceManager()->get('MelisCoreRights');
        if(!$melisCoreRights->canAccess(self::INTERFACE_KEY)) {
            $noAccessPrompt = $translator->translate('tr_tool_no_access');
        }
    
        $melisKey = $this->params()->fromRoute('melisKey', '');
    
        $view = new ViewModel();
        $view->melisKey = $melisKey;
    
        return $view;
    
    }

    
    public function renderToolLanguageHeaderAction()
    {
        $translator = $this->getServiceManager()->get('translator');
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
    
        $view = new ViewModel();
        $view->title = $melisTool->getTitle();
    
        return $view;
    }
    
    public function renderToolLanguageHeaderAddAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
    
        $view = new ViewModel();
        $view->melisKey = $melisKey;
    
        return $view;
    }
    

    public function renderToolLanguageContentAction()
    {
        $translator = $this->getServiceManager()->get('translator');
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
    
    
        $columns = $melisTool->getColumns();
        // pre-add Action Columns
        $columns['actions'] = array('text' => $translator->translate('tr_meliscms_action'));
    
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->tableColumns = $columns;
        $view->getToolDataTableConfig = $melisTool->getDataTableConfiguration(null,null,null,array('order' => '[[ 0, "desc" ]]'));
    
    
        return $view;
    }
    
    public function renderToolLanguageContentFiltersSearchAction()
    {
        return new ViewModel();
    }
    
    public function renderToolLanguageContentFiltersLimitAction()
    {
        return new ViewModel();
    }
    
    public function renderToolLanguageContentFiltersRefreshAction()
    {
        return new ViewModel();
    }
    
    public function renderToolLanguageContentActionDeleteAction()
    {
        return new ViewModel();
    }
    
    public function renderToolLanguageContentActionEditAction()
    {
        return new ViewModel();
    }
    
    
    public function renderToolLanguageModalAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->emptyModal = $melisTool->getModal('meliscms_tool_language_modal_content_empty');
        
        return $view;
    }
    
    public function renderToolLanguageModalEmptyHandlerAction()
    {
        return new ViewModel();
    }
    
    public function renderToolLanguageModalAddHandlerAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->addModalHandler = $melisTool->getModal('meliscms_tool_language_modal_content_new');
        
        return $view;
    }
    
    public function renderToolLanguageModalAddContentAction()
    {
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
    
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
    
        $view = new ViewModel();
    
        $view->setVariable('meliscms_tool_language_generic_form', $melisTool->getForm('meliscms_tool_language_generic_form'));
    
        return $view;
    }
    
    public function renderToolLanguageModalEditHandlerAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
    
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
    
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
    
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->editModalHandler = $melisTool->getModal('meliscms_tool_language_modal_content_edit');
    
        return $view;
    }
    
    public function renderToolLanguageModalEditContentAction(){
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        
        $view = new ViewModel();
        
        $view->setVariable('meliscms_tool_language_generic_form', $melisTool->getForm('meliscms_tool_language_generic_form'));
        
        return $view;
    }

    /**
     * get translations
     * return array
     */
    public function getTranslationsList()
    {
        $data = "";
        $melisCmsAuth = $this->getServiceManager()->get('MelisCoreAuth');
        $melisCmsRights = $this->getServiceManager()->get('MelisCoreRights');

        return $data;

    }
    
    public function getLanguagesAction()
    {


        $langTable = $this->getServiceManager()->get('MelisEngineTableCmsLang');
        $translator = $this->getServiceManager()->get('translator');

        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
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
    
            $search = $this->getRequest()->getPost('search');
            $search = $search['value'];
    
            $dataCount = $langTable->getTotalData();

            $getData = $langTable->getPagedData(array(
                'where' => array(
                    'key' => 'lang_cms_id',
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
                    $tableData[$ctr][$vKey] = $melisTool->limitedText($melisTool->escapeHtml($vValue));
                }
    
                // manually modify value of the desired row
                // no specific row to be modified
                
                // add DataTable RowID, this will be added in the <tr> tags in each rows
                $tableData[$ctr]['DT_RowId'] = $tableData[$ctr]['lang_cms_id'];
            }
        }
    
        return new JsonModel(array(
            'draw' => (int) $draw,
            'recordsTotal' => $dataCount,
            'recordsFiltered' =>  $langTable->getTotalFiltered(),
            'data' => $tableData,
        ));
    }
    
    public function getLanguageByIdAction()
    {
        $data = array();
        if($this->getRequest()->isPost())
        {
            $langId = $this->getRequest()->getPost('id');
            $langCmsTable = $this->getServiceManager()->get('MelisEngineTableCmsLang');
        
            $langData = $langCmsTable->getEntryById($langId);
        
            foreach($langData->current() as $roleKey => $roleValues) {
                $data[$roleKey] = $roleValues;
            }
        
        }
        
        return new JsonModel(array(
            'language' =>  $data
        ));
    }

   
    public function addLanguageAction()
    {
        $response = array();
        $this->getEventManager()->trigger('meliscms_language_new_start', $this, $response);
        $langTable = $this->getServiceManager()->get('MelisEngineTableCmsLang');
        $translator = $this->getServiceManager()->get('translator');

        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        $id = null;
        $form = $melisTool->getForm('meliscms_tool_language_generic_form');
    
        $success = 0;
        $errors  = array();
        $textTitle = 'tr_meliscms_tool_language';
        $textMessage = 'tr_meliscms_tool_language_add_failed';
        $melisTranslation = $this->getServiceManager()->get('MelisCoreTranslation');
        if($this->getRequest()->isPost()) {
    
            $postValues = $this->getRequest()->getPost()->toArray();
            $postValues = $melisTool->sanitize($postValues);
            $form->setData($postValues);

            if($form->isValid()) {
                $data = $form->getData();
                $isExistData = $langTable->getEntryByField('lang_cms_locale', $data['lang_cms_locale']);
                $isExistData = $isExistData->current();
                if (empty($isExistData)) {
                    $id = $langTable->save($data);
                    $textMessage = 'tr_meliscms_tool_language_add_success';
                    $success = 1;
                } else {
                    $errors = array(
                        'lang_cms_locale' => array(
                            'locale_exists' => $translator->translate('tr_meliscms_tool_language_add_exists')
                        ),
                    );
                }
            }
            else {
                $errors = $form->getMessages();
            }

            if ($errors) {
                $melisMelisCoreConfig = $this->getServiceManager()->get('MelisCoreConfig');
                $appConfigForm = $melisMelisCoreConfig->getItem('meliscms/tools/meliscms_language_tool/forms/meliscms_tool_language_generic_form');
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
        }
    
        $response = array(
            'success' => $success,
            'textTitle' => $textTitle,
            'textMessage' => $textMessage,
            'errors' => $errors
        );
    
        $this->getEventManager()->trigger('meliscms_language_new_end', $this, array_merge($response, array('typeCode' => 'CMS_LANGUAGE_ADD', 'itemId' => $id)));
         
        return new JsonModel($response);
    }

    /**
     * @return JsonModel
     */
    public function editLanguageAction()
    {
        $response = [];
        $this->getEventManager()->trigger('meliscms_platform_update_start', $this, $response);
        $platformTable = $this->getServiceManager()->get('MelisEngineTableCmsLang');

        $id = 0;
        $success = 0;
        $errors = [];
        $textTitle = 'tr_meliscms_tool_language';
        $textMessage = 'tr_meliscms_tool_language_prompts_edit_failed';

        if ($this->getRequest()->isPost()) {
            $melisTool = $this->getServiceManager()->get('MelisCoreTool');
            $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
            $form = $melisTool->getForm('meliscms_tool_language_generic_form');

            $postValues = $this->getRequest()->getPost()->toArray();
            $postValues = $melisTool->sanitize($postValues);
            $id = empty($postValues['id']) ? 0 : $postValues['id'];
            $form->setData($postValues);

            if ($form->isValid()) {
                $data = $form->getData();
                $data['lang_cms_id'] = $id;
                $platformTable->save($data, $id);
                $textMessage = 'tr_meliscms_tool_language_prompts_edit_success';
                $success = 1;
            } else {
                $errors = $form->getMessages();
            }

            $melisMelisCoreConfig = $this->getServiceManager()->get('MelisCoreConfig');
            $appConfigForm = $melisMelisCoreConfig->getItem('meliscms/tools/meliscms_language_tool/forms/meliscms_tool_language_generic_form');
            $appConfigForm = $appConfigForm['elements'];

            foreach ($errors as $keyError => $valueError) {
                foreach ($appConfigForm as $keyForm => $valueForm) {
                    if ($valueForm['spec']['name'] == $keyError &&
                        !empty($valueForm['spec']['options']['label']))
                        $errors[$keyError]['label'] = $valueForm['spec']['options']['label'];
                }
            }
        }

        $response = [
            'success' => $success,
            'textTitle' => $textTitle,
            'textMessage' => $textMessage,
            'errors' => $errors
        ];

        $this->getEventManager()->trigger(
            'meliscms_language_update_end',
            $this,
            array_merge(
                $response,
                ['typeCode' => 'CMS_LANGUAGE_UPDATE', 'itemId' => $id]
            )
        );

        return new JsonModel($response);
    }
    
    public function deleteLanguageAction()
    {
        $response = array();
        $this->getEventManager()->trigger('meliscms_language_delete_start', $this, $response);
        $translator = $this->getServiceManager()->get('translator');
        $langTable = $this->getServiceManager()->get('MelisEngineTableCmsLang');
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        $textMessage = 'tr_meliscms_tool_language_delete_failed';
        $melisTranslation = $this->getServiceManager()->get('MelisCoreTranslation');
    
        $id = null;
        $success = 0;
        $lang = '';
    
        if($this->getRequest()->isPost())
        {
            $id = (int) $this->getRequest()->getPost('id');
            if(is_numeric($id))
            {
                $langTable->deleteById($id);
                $textMessage = 'tr_meliscms_tool_language_delete_success';
                $success = 1;
            }
        }
    
        $response = array(
            'textTitle' => 'tr_meliscms_tool_language',
            'textMessage' => $textMessage,
            'success' => $success
        );
        
        $this->getEventManager()->trigger('meliscms_language_delete_end', $this, array_merge($response, array('typeCode' => 'CMS_LANGUAGE_DELETE', 'itemId' => $id)));
    
        return new JsonModel($response);
    }
}