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
class SitesController extends AbstractActionController
{
    const TOOL_INDEX = 'meliscms';
    const TOOL_KEY = 'meliscms_tool_sites';


    /**
     * Main container of the tool, this holds all the components of the tools
     * @return ViewModel();
     */
    public function renderToolSitesAction() {
        
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        return $view;
    }

    /**
     * @return ViewModel();
     */
    public function renderToolSitesEditAction() {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;

        return $view;
    }

    /**
     * @return ViewModel();
     */
    public function renderToolSitesEditHeaderAction() {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        return $view;
    }

    /**
     * @return ViewModel();
     */
    public function renderToolSitesEditSiteHeaderSaveAction() {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        return $view;
    }

    public function renderToolSitesTabsAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        return $view;
    }

    public function renderToolSitesPropertiesAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        return $view;
    }

    public function renderToolSitesModuleLoadAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        return $view;
    }

    public function renderToolSitesModuleLoadContentAction() {

        $siteModuleLoadSvc = $this->getServiceLocator()->get("MelisCmsSiteModuleLoadService");
        $modulesSvc = $this->getServiceLocator()->get('ModulesService');
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisCoreAuth = $this->serviceLocator->get('MelisCoreAuth');

        $userAuthDatas = $melisCoreAuth->getStorage()->read();

        $isAdmin = isset($userAuthDatas->usr_admin) || $userAuthDatas->usr_admin != "" ? $userAuthDatas->usr_admin : 0;

        $modulesInfo = $modulesSvc->getModulesAndVersions();
        $modules = $siteModuleLoadSvc->getModules($siteId);
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->modulesInfo = $modulesInfo;
        $view->modules = $modules;
        $view->melisKey = $melisKey;
        $view->isAdmin = $isAdmin;
        $view->siteId = $siteId;
        return $view;
    }

    public function renderToolSitesDomainsAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        return $view;
    }

    public function renderToolSitesDomainsContentAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $siteDomainsSvc = $this->getServiceLocator()->get("MelisCmsSitesDomainsService");
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);

        $domainsForm = $melisTool->getForm("meliscms_tool_sites_domain_form");
        $siteEnvs = $siteDomainsSvc->getEnvironments();
        $siteDomains = $siteDomainsSvc->getDomainsBySiteId($siteId);


        $view = new ViewModel();
        $view->siteEnvs = $siteEnvs;
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        $view->domainsForm = $domainsForm;
        $view->siteDomains = $siteDomains;
        return $view;
    }

    public function renderToolSitesLanguagesAction() {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();

        $view = new ViewModel();

        $view->melisKey = $melisKey;
        $view->siteId = $siteId;

        return $view;
    }

    public function renderToolSitesLanguagesContentAction() {
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $melisEngineLangSvc = $this->getServiceLocator()->get('MelisEngineLang');
        $siteLangsTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteLangs');
        $selectedLanguages = [];

        $languages = $melisEngineLangSvc->getAvailableLanguages();
        $form = $this->getTool()->getForm('meliscms_tool_sites_languages_form');

        $siteLanguages = $siteLangsTable->getEntryByField('slang_site_id', $siteId)->toArray();

        foreach ($siteLanguages as $language) {
            array_push($selectedLanguages, $language['slang_lang_id']);
        }

        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        $view->form = $form;
        $view->languages = $languages;
        $view->siteLanguages = $siteLanguages;
        $view->selectedLanguages = $selectedLanguages;

        return $view;
    }

    public function renderToolSitesSiteConfigAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        return $view;
    }

    public function renderToolSitesSiteTranslationsAction() {

        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->siteId = $siteId;
        return $view;
    }
    /**
     * Renders to the header section of the tool
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSitesHeaderAction() {

        $melisKey = $this->getMelisKey();

        $view              = new ViewModel();
        $view->melisKey    = $melisKey;
        $view->headerTitle = $this->getTool()->getTranslation('tr_meliscms_tool_sites_header_title');
        $view->subTitle    = $this->getTool()->getTranslation('tr_meliscms_tool_sites_header_sub_title');
        return $view;
    }

    public function renderToolSitesHeaderAddAction()
    {
        $view = new ViewModel();
        return $view;
    }

    /**
     * Renders to the refresh button in the table filter bar
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSitesContentFilterRefreshAction()
    {
        return new ViewModel();
    }

    /**
     * Renders to the Search input in the table filter bar
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSitesContentFilterSearchAction()
    {
        return new ViewModel();
    }

    /**
     * Renders to the limit selection in the table filter bar
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSitesContentFilterLimitAction()
    {
        return new ViewModel();
    }
    
    /**
     * Renders to the center content of the tool
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSitesContentAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        $melisKey = $this->getMelisKey();
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
     * This is the container of the modal
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSitesModalContainerAction()
    {
        $melisKey = $this->getMelisKey();

        $id = $this->params()->fromRoute('id', $this->params()->fromQuery('id', ''));

        $view = new ViewModel();
        $view->setTerminal(false);
        $view->melisKey = $melisKey;
        $view->id = $id;

        return $view;
    }
    
    /**
     * Renders to the empty modal display, this will be displayed if the user doesn't have access to the modal tabs
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSitesModalEmptyAction()
    {
        $config = $this->getServiceLocator()->get('MelisCoreConfig');
        $tool = $config->getItem('/meliscms/interface/meliscms_toolstree/interface/meliscms_tool_sites/interface/meliscms_tool_sites_modals');
        return new ViewModel();
    }
    

    /**
     * Displays the add form in the modal
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSitesModalAddAction()
    {
        // declare the Tool service that we will be using to completely create our tool.
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->setTerminal(false);
        $view->melisKey  = $melisKey;
        return $view;
    }

    public function renderToolSitesModalAddStep1Action()
    {
        $melisKey = $this->getMelisKey();

        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');

        // tell the Tool what configuration in the app.tools.php that will be used.
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        //prepare the user profile form
        $form = $melisTool->getForm('meliscms_tool_sites_modal_add_step1_form');

        $view = new ViewModel();
        $view->setVariable('step1_form', $form);
        $view->melisKey = $melisKey;

        return $view;
    }
    public function renderToolSitesModalAddStep2Action()
    {
        $melisKey = $this->getMelisKey();

        //get the lang list
        $langService = $this->getServiceLocator()->get('MelisEngineLang');
        $langList = $langService->getAvailableLanguages();

        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');

        // tell the Tool what configuration in the app.tools.php that will be used.
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        //prepare the step2 forms
        $formMultiLingual = $melisTool->getForm('meliscms_tool_sites_modal_add_step2_form_multi_language');
        $formSingleLanguage = $melisTool->getForm('meliscms_tool_sites_modal_add_step2_form_single_language');

        $view = new ViewModel();
        $view->setVariable('step2_form_multi_language', $formMultiLingual);
        $view->setVariable('step2_form_single_language', $formSingleLanguage);
        $view->melisKey = $melisKey;
        $view->langList = $langList;

        return $view;
    }
    public function renderToolSitesModalAddStep3Action()
    {
        $melisKey = $this->getMelisKey();

        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');

        // tell the Tool what configuration in the app.tools.php that will be used.
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        //prepare the step2 forms
        $formMultiDomain = $melisTool->getForm('meliscms_tool_sites_modal_add_step3_form_multi_domain');
        $formSingleDomain = $melisTool->getForm('meliscms_tool_sites_modal_add_step3_form_single_domain');

        $view = new ViewModel();
        $view->setVariable('step3_form_multi_domain', $formMultiDomain);
        $view->setVariable('step3_form_single_domain', $formSingleDomain);
        $view->melisKey  = $melisKey;
        return $view;
    }
    public function renderToolSitesModalAddStep4Action()
    {
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->setTerminal(false);
        $view->melisKey  = $melisKey;
        return $view;
    }
    public function renderToolSitesModalAddStep5Action()
    {
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->setTerminal(false);
        $view->melisKey  = $melisKey;
        return $view;
    }
    

    public function renderToolSitesModalEditAction()
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
    public function renderToolSitesContentActionEditAction()
    {
        return new ViewModel();
    }
    
    /**
     * Renders to the delete button in the table
     * @return \Zend\View\Model\ViewModel
     */
    public function renderToolSitesContentActionDeleteAction()
    {
        return new ViewModel();
    }
    
    public function renderToolSitesNewSiteConfirmationModalAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();
        $view->melisKey = $melisKey;

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
     * Add New Site
     * @return \Zend\View\Model\JsonModel
     */
    public function saveSiteAction()
    {
        $eventDatas = array();
        $this->getEventManager()->trigger('meliscms_sites_save_start', $this, $eventDatas);

        $status  = 1;
        $errors  = array();
        $textMessage = 'tr_melis_cms_site_save_ko';
        $logTypeCode = '';
        $translator = $this->getServiceLocator()->get('translator');
        $siteModuleLoadSvc = $this->getServiceLocator()->get("MelisCmsSiteModuleLoadService");
        $siteDomainsSvc = $this->getServiceLocator()->get("MelisCmsSitesDomainsService");
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $request = $this->getRequest();
        $data = $request->getPost()->toArray();
        $melisCoreAuth = $this->serviceLocator->get('MelisCoreAuth');
        $userAuthDatas = $melisCoreAuth->getStorage()->read();
        $isAdmin = isset($userAuthDatas->usr_admin) || $userAuthDatas->usr_admin != "" ? $userAuthDatas->usr_admin : 0;
        $siteLangsTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteLangs');
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');

        $success = 0;
        $ctr = 0;

        $moduleList = array();
        $domainData = array();

        foreach ($data as $datum => $val){

            //collecting data for site module load
            if($isAdmin) {
                if (strstr($datum,'moduleLoad')) {
                    $datum = str_replace("moduleLoad", '', $datum);
                    array_push($moduleList, $datum);
                }
            }

            //collecting data for site domains
            if (strstr($datum,'sdom')) {
                $key = substr($datum, (strpos($datum, '_') ?: -1) + 1);
                if(!empty($domainData[$ctr]))
                    if(array_key_exists($key, $domainData[$ctr]))
                        $ctr++;
                $domainData[$ctr][$key] = $val;
            }

        }

        //saving module load
        $siteModuleLoadSvc->saveModuleLoad($siteId,$moduleList);

        //saving site domains
        foreach($domainData as $domainDatum){
            $form = $this->getTool()->getForm('meliscms_tool_sites_domain_form');
            $form->setData($domainDatum);

            if($form->isValid()) {
                $success = $siteDomainsSvc->saveSiteDomain($domainDatum);
                if($success){
                    $textMessage = $translator->translate("tr_melis_cms_site_save_ok");
                    $status = 1;
                }else{
                    $response = array(
                        'success' => $success,
                        'textTitle' => $translator->translate('tr_meliscms_tool_site'),
                        'textMessage' => $translator->translate('tr_melis_cms_site_save_ko'),
                        'errors' => $errors,
                    );
                    return new JsonModel($response);
                }
            }else{
                $textMessage = 'tr_melis_cms_site_save_ko';
                $errors = $form->getMessages();
                $status = 0;
            }

        }

        // saving languages
        foreach ($data['slang_lang_id'] as $lang) {
            $siteLangs = $siteLangsTable->getEntryByField('slang_site_id', $siteId)->toArray();

            foreach ($siteLangs as $siteLang) {
                if ($siteLang['slang_lang_id'] !== $lang) {
                    $siteLangsTable->save([
                        'slang_site_id' => $siteId,
                        'slang_lang_id' => $lang
                    ]);
                }
            }
        }

        // update site to add site option language url
        $siteTable->save([
            'site_opt_lang_url' => $data['site_opt_lang_url']
        ]);

        $response = array(
            'success' => $status,
            'textTitle' => $translator->translate('tr_meliscms_tool_site'),
            'textMessage' => $translator->translate($textMessage),
            'errors' => $errors,
        );

        if ($siteId)
        {
            $response['siteId'] = $siteId;
        }

        $this->getEventManager()->trigger('meliscms_sites_save_end', $this, array_merge($response, array('typeCode' => $logTypeCode, 'itemId' => $siteId)));

        return new JsonModel($response);
    }
    
    public function deleteSiteAction()
    {
        $request = $this->getRequest();
        $status  = 0;
        $textMessage = '';
        $eventDatas = array();
        $this->getEventManager()->trigger('meliscms_site_delete_start', $this, $eventDatas);
        $siteId = (int) $this->params()->fromQuery('siteId', '');
         

        $response = array(
            'success' => $status ,
            'textTitle' => 'tr_meliscms_tool_site',
            'textMessage' => $textMessage
        );
        $this->getEventManager()->trigger('meliscms_site_delete_end', $this, array_merge($response, array('typeCode' => 'CMS_SITE_DELETE', 'itemId' => $siteId)));
        
        return new JsonModel($response);
    }
    private function getMelisKey()
    {
        $melisKey = $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey'), null);
        return $melisKey;
    }
    /**
     * this method will get the meliscore tool
     */
    private function getTool()
    {
        $toolSvc = $this->getServiceLocator()->get('MelisCoreTool');
        $toolSvc->setMelisToolKey('meliscms', 'meliscms_tool_sites');
        return $toolSvc;
    }

}
