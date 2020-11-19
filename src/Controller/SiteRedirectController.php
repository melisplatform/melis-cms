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

/**
 * Site Redirect Tool
 */
class SiteRedirectController extends MelisAbstractActionController
{
    const TOOL_KEY = 'meliscms_tool_site_301';
    
    /**
     * Render Site Redirect Content
     * This will retrieve the user accessiblity to the page
     * and check if the current user is allowed to access the page
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolSiteRedirectAction()
    {
        $translator = $this->getServiceManager()->get('translator');
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $noAccessPrompt = '';
        // Checks wether the user has access to this tools or not
        $melisCoreRights = $this->getServiceManager()->get('MelisCoreRights');
        if(!$melisCoreRights->canAccess($this::TOOL_KEY)) {
            $noAccessPrompt = $translator->translate('tr_tool_no_access');
        }
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->noAccess  = $noAccessPrompt;
        return $view;
    }
    
    /**
     * Render Site Redirect page header
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolSiteRedirectHeaderAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        return $view;
    }
    
    /**
     * Render Add action button from page header
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolSiteRedirectAddAction()
    {
        $s301Id = $this->params()->fromQuery('s301Id');
        
        $view = new ViewModel();
        $view->s301Id = $s301Id;
        return $view;
    }
    
    /**
     * Render Site Redirect List table
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolSiteRedirectContentAction()
    {
        $translator = $this->getServiceManager()->get('translator');
        
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        // tell the Tool what configuration in the app.tool.php that will be used.
        $melisTool->setMelisToolKey('meliscms', 'meliscms_tool_site_301');
        // get the columns that has been set in the configuration (app.tools.php)
        $columns = $melisTool->getColumns();
        // add an extra column which is Action
        $columns['actions'] = array('text' => $translator->translate('tr_meliscms_action'), 'css' => 'width:10%');
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->tableColumns = $columns;
        $view->getToolDataTableConfig = $melisTool->getDataTableConfiguration();
        return $view;
    }
    
    /**
     * Retrieving Site Redirect list for dataTable data
     * 
     * @return \Laminas\View\Model\JsonModel
     */
    public function getSiteRedirectAction()
    {
        $site301Table = $this->getServiceManager()->get('MelisEngineTableSite301');
        $siteTable = $this->getServiceManager()->get('MelisEngineTableSite');
        $translator = $this->getServiceManager()->get('translator');
        // Getting the Site Redirect Table configuration from Tool config
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        $melisTool->setMelisToolKey('meliscms', self::TOOL_KEY);
        
        $colId = array();
        $dataCount = 0;
        $draw = 0;
        $tableData = array();
        
        if($this->getRequest()->isPost())
        {

            $optionFilter = array();

            if(!empty($this->getRequest()->getPost('s301_site_id'))){
                $optionFilter['s301_site_id'] = $this->getRequest()->getPost('s301_site_id');
            }

            $siteId = $this->getRequest()->getPost('s301_site_id');
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

            $dataCount = $site301Table->getTotalData();

            $getData = $site301Table->getData($search, $siteId, $melisTool->getSearchableColumns(), $selCol, $sortOrder, $start, $length);

            $tableData = $getData->toArray();

            foreach ($tableData As $key => $val)
            {
                $tableData[$key]['DT_RowId'] = $val['s301_id'];
            }
        }

        return new JsonModel(array(
            'draw' => (int) $draw,
            'recordsTotal' => $dataCount,
            'recordsFiltered' =>  $site301Table->getTotalFiltered(),
            'data' => $tableData,
        ));
    }
    
    /**
     * Render Limit dropdown for Site Redirect Table
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolSiteRedirectFiltersLimitAction()
    {
        $view = new ViewModel();
        return $view;
    }
    
    /**
     * Render Search input for Site Rdirect Table
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolSiteRedirectFiltersSearchAction()
    {
        $view = new ViewModel();
        return $view;
    }
    
    /**
     * Render Refresh page button
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolSiteRedirectFiltersRefreshAction()
    {
        $view = new ViewModel();
        return $view;
    }
    
    /**
     * Render site dropdown filter
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolSiteRedirectFiltersSitesAction()
    {
        $translator = $this->getServiceManager()->get('translator');
        $siteTable = $this->getServiceManager()->get('MelisEngineTableSite');
        
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
     * Render Test button for particular site redirect
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolSiteRedirectTestAction()
    {
        $view = new ViewModel();
        return $view;
    }
    
    /**
     * Render Edit button for particular site redirect
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolSiteRedirectEditAction()
    {
        $view = new ViewModel();
        return $view;
    }
    
    /**
     * Render Delete button for particular site redirect
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolSiteRedirectDeleteAction()
    {
        $view = new ViewModel();
        return $view;
    }

    /**
     * Return original page direction
     *
     * @param pageId
     * return link
     */
    public function getOriginalPageDirectionAction($pageId)
    {
        $success      = 0;
        $link         = "";
        $siteRedirect = $this->getServiceManager()->get("Sitedomain");

        if($this->getRequest()->isPost()){

            $link = $siteRedirect->getOriginalPageDirection($pageId);
        }

        return $link;
    }

    /**
     * Render Site Redirect Modal container
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolSiteRedirectModalAction()
    {
        $id = $this->params()->fromQuery('id');
        $melisKey = $this->params()->fromQuery('melisKey');
        
        $view = new ViewModel();
        $view->setTerminal(true);
        $view->id = $id;
        $view->melisKey = $melisKey;
        return $view;
    }
    
    /**
     * Render Site Redirect form, this will appear on modal
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderToolSiteRedirectGenericFormAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $s301Id = $this->params()->fromQuery('s301Id');
        
        // Getting the Site Redirect Form from Tool config
        $melisMelisCoreConfig = $this->getServiceManager()->get('MelisCoreConfig');
        $appConfigForm = $melisMelisCoreConfig->getFormMergedAndOrdered('meliscms/tools/meliscms_tool_site_301/forms/meliscms_tool_site_301_generic_form','meliscms_tool_site_301_generic_form');
        
        $factory = new \Laminas\Form\Factory();
        $formElements = $this->getServiceManager()->get('FormElementManager');
        $factory->setFormElementManager($formElements);
        $propertyForm = $factory->createForm($appConfigForm);
        
        $title = 'tr_meliscms_tool_site_301_add_site_redirect';
        // Checking if the Site Redirect Id has value,
        // If the Id has a value this will Retrieve Details and Binded to the Form, else Form will be blank
        if ($s301Id)
        {
            $title = 'tr_meliscms_tool_site_301_edit_site_redirect';
            
            $site301Table = $this->getServiceManager()->get('MelisEngineTableSite301');
            $s301Data = $site301Table->getEntryById($s301Id)->current();
            
            if (!empty($s301Data))
            {
                $propertyForm->bind($s301Data);
            }
        }
        
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->s301Id = $s301Id;
        $view->title = $title;
        $view->setVariable('meliscms_tool_site_301_generic_form', $propertyForm);
        return $view;
    }
    
    /**
     * This method will saving the Site Redirect form
     * 
     * @return \Laminas\View\Model\JsonModel
     */
    public function saveSiteRedirectAction()
    {
        $translator = $this->getServiceManager()->get('translator');
        
        $request = $this->getRequest();
        $s301_id = null;
        $status  = 0;
        $textTitle = '';
        $textMessage = '';
        $errors  = array();

        $siteDomainTable = $this->getServiceManager()->get('MelisEngineTableSiteDomain');
        
        $melisTool = $this->getServiceManager()->get('MelisCoreTool');
        if($request->isPost()) {
             
            $postValues = $request->getPost()->toArray();
            $postValues = $melisTool->sanitizePost($postValues);
             
            if ($postValues['s301_id'])
            {
                $s301_id = $postValues['s301_id'];
                $logTypCode = 'CMS_SITE_REDIRECT_UPDATE';
            }
            else 
            {
                $logTypCode = 'CMS_SITE_REDIRECT_ADD';
            }
            
            if (!empty($postValues)){
                
                // Getting the Site Redirect Form from Tool config
                $melisMelisCoreConfig = $this->getServiceManager()->get('MelisCoreConfig');
                $appConfigForm = $melisMelisCoreConfig->getFormMergedAndOrdered('meliscms/tools/meliscms_tool_site_301/forms/meliscms_tool_site_301_generic_form','meliscms_tool_site_301_generic_form');
                
                $factory = new \Laminas\Form\Factory();
                $formElements = $this->getServiceManager()->get('FormElementManager');
                $factory->setFormElementManager($formElements);
                $propertyForm = $factory->createForm($appConfigForm);
                // Set Data to Form from Posted Data
                $propertyForm->setData($postValues);
                // Checking if the Form is valid
                if ($propertyForm->isValid())
                {
                    $data = $propertyForm->getData();
                    
                    $site301Table = $this->getServiceManager()->get('MelisEngineTableSite301');
                    
                    $textTitle = 'tr_meliscms_tool_site_301_add_site_redirect';
                    $textMessage = 'meliscms_tool_site_301_add_success';
                    if ($data['s301_id'])
                    {
                        $textTitle = 'tr_meliscms_tool_site_301_edit_site_redirect';
                        $textMessage = 'meliscms_tool_site_301_edit_success';
                    }
                    
                    // Checking if the Old Url is existing on database
                    $s301Data = $site301Table->getEntryByField('s301_old_url', $data['s301_old_url']);
                    
                    foreach($s301Data as $s301){
                    
                        if ($s301->s301_site_id == $data['s301_site_id'] && $data['s301_id'] != $s301->s301_id)
                        {
                            $textMessage = 'meliscms_tool_site_301_unable_to_add';
                    
                            $errors['s301_old_url'] = array(
                                'label' => $translator->translate('tr_meliscms_tool_site_301_s301_old_url'),
                                'isExist' => $translator->translate('meliscms_tool_site_301_old_url_exist')
                            );
                        }
                    }
                    
                    
                    if (empty($errors))
                    {
                        unset($data['s301_id']);
                        $s301_id = $site301Table->save($data, $s301_id);
                        $status  = 1;
                    }
                }
                else 
                {
                    $textTitle = 'tr_meliscms_tool_site_301_add_site_redirect';
                    $textMessage = 'meliscms_tool_site_301_unable_to_add';
                    if ($postValues['s301_id'])
                    {
                        $textTitle = 'tr_meliscms_tool_site_301_edit_site_redirect';
                        $textMessage = 'meliscms_tool_site_301_unable_to_edit';
                    }
                    
                    $errors = $propertyForm->getMessages();
                    
                    $appConfigForm = $appConfigForm['elements'];
                     
                    foreach ($errors as $keyError => $valueError)
                    {
                        // Translating manually the error messags from form validation
                        foreach ($valueError As $keyErr => $valErr)
                        {
                            $errors[$keyError][$keyErr] = $translator->translate($valErr);
                        }
                        
                        foreach ($appConfigForm as $keyForm => $valueForm)
                        {
                            if ($valueForm['spec']['name'] == $keyError && !empty($valueForm['spec']['options']['label']))
                            {
                                $errors[$keyError]['label'] = $valueForm['spec']['options']['label'];
                            }
                        }
                    }
                }
            }
        }
         
        $response = array(
            'success' => $status,
            'textTitle' => $textTitle,
            'textMessage' => $textMessage,
            'errors' => $errors,
        );
        
        $this->getEventManager()->trigger('meliscalendar_save_site_redirect_end', $this, array_merge($response, array('typeCode' => $logTypCode, 'itemId' => $s301_id)));
         
        return new JsonModel($response);
    }
    
    /**
     * This method will delete the selected Site Redirect
     * 
     * @return \Laminas\View\Model\JsonModel
     */
    public function deleteSiteRedirectAction(){
        $translator = $this->getServiceManager()->get('translator');
    
        $request = $this->getRequest();
        // Default Values
        $s301_id = null;
        $status  = 0;
        $textMessage = 'meliscms_tool_site_301_unable_to_delete';
        $errors  = array();
         
        if($request->isPost()) {
             
            $postValues = $request->getPost()->toArray();
             
            // Checking if the Site Redirect Id has value
            if (!empty($postValues['s301Id'])){
                
                $s301_id = $postValues['s301Id'];
                // Deleting Site Redirect using Service manager
                $site301Table = $this->getServiceManager()->get('MelisEngineTableSite301');
                $site301Table->deleteById($postValues['s301Id']);
                
                $textMessage = 'meliscms_tool_site_301_delete_success';
                $status = 1;
            }
        }
         
        $response = array(
            'success' => $status,
            'textTitle' => 'tr_meliscms_tool_site_301_delete_site_redirect',
            'textMessage' => $textMessage,
            'errors' => $errors,
        );
        
        $this->getEventManager()->trigger('meliscalendar_delete_site_redirect_end', $this, array_merge($response, array('typeCode' => 'CMS_SITE_REDIRECT_DELETE', 'itemId' => $s301_id)));
         
        return new JsonModel($response);
    }


}