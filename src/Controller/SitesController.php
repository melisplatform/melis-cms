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

    const SITE_TABLE_PREFIX = 'site_';
    const DOMAIN_TABLE_PREFIX = 'sdom_';
    const SITE404_TABLE_PREFIX = 's404_';

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

    public function renderToolSitesSiteConfigAction() {

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

        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');

        // tell the Tool what configuration in the app.tools.php that will be used.
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        //prepare the step4 forms
        $moduleForm = $melisTool->getForm('meliscms_tool_sites_modal_add_step4_form_module');

        $view = new ViewModel();
        $view->setVariable('step4_form_module', $moduleForm);
        $view->melisKey = $melisKey;
        return $view;
    }
    public function renderToolSitesModalAddStep5Action()
    {
        $melisKey = $this->getMelisKey();
        $view = new ViewModel();
        $view->setTerminal(false);
        $view->melisKey = $melisKey;
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

            $start = (int)$this->getRequest()->getPost('start');
            $length = (int)$this->getRequest()->getPost('length');

            $search = $this->getRequest()->getPost('search');
            $search = $search['value'];

            $dataCount = $siteTable->getTotalData();

            $getData = $siteTable->getSitesData($search, $melisTool->getSearchableColumns(), $selCol, $sortOrder, $start, $length);
            $dataFilter = $siteTable->getSitesData($search, $melisTool->getSearchableColumns(), $selCol, $sortOrder, null, null);

            $tableData = $getData->toArray();
            for ($ctr = 0; $ctr < count($tableData); $ctr++) {
                // apply text limits
                foreach ($tableData[$ctr] as $vKey => $vValue) {
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
     * @return JsonModel
     */
    public function createNewSiteAction()
    {
        $errors = array();
        $status = false;
        $siteId = null;
        $siteName = '';
        $textMessage = '';
        $siteTablePrefix = self::SITE_TABLE_PREFIX;
        $domainTablePrefix = self::DOMAIN_TABLE_PREFIX;

        $translator = $this->getServiceLocator()->get('translator');

        if ($this->getRequest()->isPost()) {
            $sitesData = $this->getRequest()->getPost('data');
            if(!empty($sitesData)) {
                $createNewFile = false;
                $isNewSIte = false;
                $siteData = array();
                $siteLanguages = array();
                $site404Data = array();
                $domainData = array();
                $domainDataTemp = array();

                /**
                 * This will look for every specific data for each table(site, domains, etc..)
                 *
                 * The Domain is specific case cause there's a chance that the user will
                 * select multi domain for every site(depend on language) and even though
                 * the user select single domain, we will still need to prepare the data as
                 * equal to multi domain
                 */
                foreach ($sitesData as $key => $value) {
                    if (!empty($value['data']) && is_array($value['data'])) {
                        foreach ($value['data'] as $k => $val) {
                            if (!empty($val) && is_array($val)) {
                                if (!empty($val['name'])) {
                                    /**
                                     * add site data
                                     */
                                    if (strpos($val['name'], $siteTablePrefix) !== false) {
                                        $siteData[$val['name']] = $val['value'];
                                    }

                                    /**
                                     * add the domain data
                                     */
                                    if ($key == 'domains') {
                                        /**
                                         * if it is came from the domain form, we will put
                                         * it inside the main domain data container
                                         */
                                        if (strpos($val['name'], $domainTablePrefix) !== false) {
                                            $domainData[$k][$val['name']] = $val['value'];
                                        }
                                    } else {
                                        /**
                                         * we will put the domain data to temporary
                                         * container to add to main container later
                                         */
                                        if (strpos($val['name'], $domainTablePrefix) !== false) {
                                            $domainDataTemp[$val['name']] = $val['value'];
                                        }
                                    }
                                } else {
                                    /**
                                     * This will add the data that the key
                                     * is equal to the field name
                                     */
                                    foreach ($val as $field => $fieldValue) {
                                        /**
                                         * add site data
                                         */
                                        if (strpos($field, $siteTablePrefix) !== false) {
                                            $siteData[$field] = $fieldValue;
                                        }

                                        /**
                                         * add domain data
                                         */
                                        if (strpos($field, $domainTablePrefix) !== false) {
                                            $domainData[$k][$field] = $fieldValue;
                                        }
                                    }
                                }
                            } else {
                                /**
                                 * add the site data
                                 */
                                if (strpos($k, $siteTablePrefix) !== false) {
                                    $siteData[$k] = $val;
                                }

                                /**
                                 * Add the other domain data to temporary container
                                 * since it came from other form, were just gonna
                                 * add this to main domain container later
                                 */
                                if (strpos($k, $domainTablePrefix) !== false) {
                                    $domainDataTemp[$k] = $val;
                                }
                            }
                        }
                    } else {
                        foreach ($value as $fieldKey => $fieldValue) {
                            /**
                             * add the site data
                             */
                            if (strpos($fieldKey, $siteTablePrefix) !== false) {
                                $siteData[$fieldKey] = $fieldValue;
                            }

                            /**
                             * Add the other domain data to temporary container
                             * since it came from other form, were just gonna
                             * add this to main domain container later
                             */
                            if (strpos($fieldKey, $domainTablePrefix) !== false) {
                                $domainDataTemp[$fieldKey] = $fieldValue;
                            }
                        }
                    }

                    /**
                     * Check if it is a new site and if were are
                     * gonna create a file for this site
                     */
                    if ($key == 'module') {
                        $createNewFile = ($value['createFile'] === 'true');
                        $isNewSIte = ($value['newSite'] === 'true');
                    }

                    /**
                     * get the site languages
                     */
                    if ($key == 'languages') {
                        $siteLanguages = $value;
                    }
                }

                /**
                 * Fill the other fields with the default one
                 * if the fields are still empty
                 */
                //check if $domainData is empty
                if (empty($domainData) && !empty($domainDataTemp)) {
                    foreach ($siteLanguages as $locale => $langId) {
                        if($locale != 'sites_url_setting') {
                            foreach ($domainDataTemp as $dom => $val) {
                                $domainData[$locale] = array($dom => $val);
                            }
                        }
                    }
                }
                //we need to loop the domain to fill all fields
                foreach ($domainData as $domKey => $domVal) {
                    //add the temporary domain data to the main container
                    foreach ($domainDataTemp as $tempKey => $tempVal) {
                        if (empty($domainData[$domKey][$tempKey])) {
                            $domainData[$domKey][$tempKey] = $tempVal;
                        }
                    }
                    /**
                     * add some default data to domain
                     * if the fields does not exist
                     * or empty
                     */
                    $domainData[$domKey]['sdom_env'] = (!empty($domainData[$domKey]['sdom_env'])) ? $domainData[$domKey]['sdom_env'] : getenv('MELIS_PLATFORM');
                    $domainData[$domKey]['sdom_scheme'] = (!empty($domainData[$domKey]['sdom_scheme'])) ? $domainData[$domKey]['sdom_scheme'] : 'http';
                }
                //field the site data
                if (!empty($siteData)) {
                    $siteName = (!empty($siteData['site_name'])) ? $siteData['site_name'] : '';
                    $siteData['site_label'] = (!empty($siteData['site_label'])) ? $siteData['site_label'] : $siteName;
                }

                /**
                 * Before proceeding to save the site
                 * check if it is a new site and
                 * the site is not yet created
                 */
                $isValidName = true;
                if ($isNewSIte) {
                    $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
                    $siteDBData = $siteTable->getEntryByField('site_name', $siteName)->current();
                    if (!empty($siteDBData)) {
                        $isValidName = false;
                    }

                    /**
                     * Make the value of site_name empty
                     * on siteData since the user choice
                     * not to create a file, therefore
                     * this site has not module
                     */
                    if(!$createNewFile){
                        if(!empty($siteData['site_name'])){
                            $siteData['site_name'] = '';
                        }
                    }
                }

                if ($isValidName) {
                    $cmsSiteSrv = $this->getServiceLocator()->get('MelisCmsSiteService');
                    $saveSiteResult = $cmsSiteSrv->saveSite($siteData, $domainData, $siteLanguages, $site404Data, $siteName, $createNewFile, $isNewSIte);

                    if ($saveSiteResult['success'])
                    {
                        $siteId = $saveSiteResult['site_id'];
                        $textMessage = 'tr_melis_cms_sites_tool_add_create_site_success';
                        $status = true;
                    }
                    else
                    {
                        $textMessage = 'tr_melis_cms_sites_tool_add_unable_to_create_site';
                        $errors = array(
                            'Error' => array(
                                'siteAlreadyExists' => $translator->translate($saveSiteResult['message'])
                            ),
                        );
                        $status = false;
                    }
                }else{
                    $textMessage = 'tr_melis_cms_sites_tool_add_unable_to_create_site';
                    $errors = array(
                        'Error' => array(
                            'siteAlreadyExists' => $translator->translate('tr_meliscms_tool_site_name_exists')
                        ),
                    );
                    $status = false;
                }
            }
        }

        $response = array(
            'success' => $status,
            'textTitle' => 'tr_meliscms_tool_site',
            'textMessage' => $textMessage,
            'siteId' => $siteId,
            'siteName' => $siteName,
            'errors' => $errors
        );

        $this->getEventManager()->trigger('meliscms_site_save_end', $this, array_merge($response, array('typeCode' => 'CMS_SITE_ADD', 'itemId' => $siteId)));

       return new JsonModel($response);
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
        $siteId = (int) $this->params()->fromQuery('siteId', '');
        $request = $this->getRequest();
        $data = $request->getPost()->toArray();
        $melisCoreAuth = $this->serviceLocator->get('MelisCoreAuth');
        $userAuthDatas = $melisCoreAuth->getStorage()->read();
        $isAdmin = isset($userAuthDatas->usr_admin) || $userAuthDatas->usr_admin != "" ? $userAuthDatas->usr_admin : 0;

        $success = 0;
        $ctr = 0;
        $ctr1 = 0;

        $moduleList = [];
        $domainData = [];
        $sitePropData = [];
        $siteHomeData = [];
        $siteConfigTabData = [];

        foreach ($data as $datum => $val){
            //collecting data for site module load
            if ($isAdmin) {
                if (strstr($datum,'moduleLoad')) {
                    $datum = str_replace("moduleLoad", '', $datum);
                    array_push($moduleList, $datum);
                }
            }

            //collecting data for site domains
            if (strstr($datum,'sdom_')) {
                $key = substr($datum, (strpos($datum, '_') ?: -1) + 1);
                if(!empty($domainData[$ctr]))
                    if(array_key_exists($key, $domainData[$ctr]))
                        $ctr++;
                $domainData[$ctr][$key] = $val;
            }

            //collecting data for site properties
            if (strstr($datum,'siteprop_')) {
                $datum = str_replace("siteprop_", '', $datum);
                $sitePropData[$datum] = $val;
            }

            //collecting data for site language homepages
            if (strstr($datum,'shome_')) {
                $key = substr($datum, (strpos($datum, '_') ?: -1) + 1);
                if(!empty($siteHomeData[$ctr1]))
                    if(array_key_exists($key, $siteHomeData[$ctr1]))
                        $ctr1++;
                $siteHomeData[$ctr1][$key] = $val;
            }

            //data for site config
            if (strstr($datum,'sconf_')) {
                $lang = explode('_', $datum)[0];
                $key = substr($datum, (strpos($datum, '_') ?: -1) + 1);
                $tableColumns = [
                    'sconf_id',
                    'sconf_site_id',
                    'sconf_lang_id'
                ];

                if (in_array($key, $tableColumns)) {
                    $siteConfigTabData[$lang][$key] = $val;
                } else {
                    $siteConfigTabData[$lang]['config'][$key] = $val;
                }
            }
        }

        $this->saveSiteModules($isAdmin, $siteId, $moduleList);
        $this->saveSiteDomains($domainData, $errors, $status);
        $this->saveSiteHomePages($siteHomeData, $errors, $status);
        $this->saveSiteLanguagesTab($siteId, $data);
        $this->saveSiteConfig($siteId,$siteConfigTabData);
        $this->saveSiteProperties($siteId, $sitePropData, $errors, $status);

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

    private function saveSiteProperties($siteId, $sitePropData, &$errors, &$status)
    {
        $form = $this->getTool()->getForm('meliscms_tool_sites_properties_form');
        $form->setData($sitePropData);

        if ($form->isValid()) {
            $siteTbl = $this->getServiceLocator()->get('MelisEngineTableSite');
            $siteData = $siteTbl->getEntryById($siteId)->toArray()[0];
            $dataToUpdate = [];

            foreach ($siteData as $siteDatumKey => $siteDatum) {
                if (array_key_exists($siteDatumKey, $sitePropData)) {
                    if ($sitePropData[$siteDatumKey] != $siteDatum) {
                        $dataToUpdate[$siteDatumKey] = $sitePropData[$siteDatumKey];
                    }
                }
            }

            if (!empty($dataToUpdate)) {
                $siteTbl->update($dataToUpdate, 'site_id', $siteId);
            }

            $site404Tbl = $this->getServiceLocator()->get('MelisEngineTableSite404');
            $site404 = $site404Tbl->getEntryByField('s404_site_id', $siteId)->toArray()[0];

            if ($site404['s404_page_id'] != $sitePropData['s404_page_id']) {
                $site404Tbl->update(
                    [
                        's404_page_id' => $sitePropData['s404_page_id']
                    ],
                    's404_site_id',
                    $siteId
                );
            }
        } else {
            $err = [];

            foreach ($form->getMessages() as $key => $val) {
                $err[$key] = $val;
            }

            $errors = array_merge($errors, $err);
            $status = 0;
        }
    }

    private function saveSiteModules($isAdmin, $siteId, $moduleList)
    {
        $siteModuleLoadSvc = $this->getServiceLocator()->get("MelisCmsSiteModuleLoadService");

        if ($isAdmin) {
            $siteModuleLoadSvc->saveModuleLoad($siteId, $moduleList);
        }
    }

    private function saveSiteDomains($siteDomainData, &$errors, &$status)
    {
        $siteDomainsSvc = $this->getServiceLocator()->get("MelisCmsSitesDomainsService");

        foreach($siteDomainData as $domainDatum){
            $form = $this->getTool()->getForm('meliscms_tool_sites_domain_form');
            $form->setData($domainDatum);

            if($form->isValid()) {
                $siteDomainsSvc->saveSiteDomain($domainDatum);
            }else{
                $currErr = array();
                foreach ($form->getMessages() as $key => $err){
                    $currErr[$domainDatum["sdom_env"]."_".$key] = $err;
                }
                $errors = array_merge($errors,$currErr);
                $status = 0;
            }
        }
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

    private function saveSiteHomePages($siteHomeData, &$errors, &$status) {
        $sitePropSvc = $this->getServiceLocator()->get("MelisCmsSitesPropertiesService");

        foreach($siteHomeData as $siteHomeDatum) {
            $form = $this->getTool()->getForm('meliscms_tool_sites_properties_homepage_form');
            $form->setData($siteHomeDatum);

            if ($form->isValid()) {
                $sitePropSvc->saveSiteLangHome($siteHomeDatum);
            } else {
                $currErr = [];

                foreach ($form->getMessages() as $key => $err) {
                    $currErr[$siteHomeDatum["shome_lang_id"]."_".$key] = $err;
                }

                $errors = array_merge($errors, $currErr);
                $status = 0;
            }
        }
    }

    private function saveSiteLanguagesTab($siteId, $data) {
        $siteLangsTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteLangs');
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');

        // Saving languages
        $siteLangs = $siteLangsTable->getSiteLangs(null, $siteId, null, null)->toArray();
        $activeSiteLangs = $siteLangsTable->getSiteLangs(null, $siteId, null, true)->toArray();
        $selectedSiteLangs = $data['slang_lang_id'] ?? [];
        $noChangesOnSiteLangs = false;

        // Check if active languages and selected languages are the same
        if (count($activeSiteLangs) === count($selectedSiteLangs)) {
            foreach ($activeSiteLangs as $activeSiteLang) {
                if (in_array($activeSiteLang['slang_lang_id'], $selectedSiteLangs)) {
                    $noChangesOnSiteLangs = true;
                }
            }
        }

        // Catch if there are changes on the selected languages over the active languages
        if (!$noChangesOnSiteLangs) {
            // Disable all active languages of site
            $siteLangsTable->update(['status' => 0], 'slang_site_id', $siteId);

            // Because all of the active languages are disabled. All we have to do
            // is to save if it's a new language or to active(update) the language back
            foreach ($selectedSiteLangs as $selectedSiteLang) {
                $slangId = 0;

                foreach ($siteLangs as $siteLang) {
                    if ($selectedSiteLang == $siteLang['slang_lang_id']) {
                        $slangId = $siteLang['slang_id'];
                        break;
                    }
                }

                $siteLangsTable->save(
                    [
                        'slang_site_id' => $siteId,
                        'slang_lang_id' => $selectedSiteLang,
                        'status' => 1
                    ],
                    $slangId
                );
            }
        }

        // Update site to add site option language url
        $siteTable->save(['site_opt_lang_url' => $data['site_opt_lang_url']], $siteId);
    }

    private function saveSiteConfig($siteId, $siteConfigTabData) {
        $siteConfigTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteConfig');
        $siteLangsTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteLangs');

        $site = $this->getSiteDataById($siteId);
        $siteName = $site['site_name'];

        $config = $this->getSiteConfigById($siteId);
        $configFromDb = $this->getSiteConfigFromDbById($siteId);

        foreach ($siteConfigTabData as $langKey => $langValue) {
            $locale = $siteLangsTable->getSiteLangs(null, $siteId, $langKey, true, true)->toArray();
            $sconf_id = !empty($langValue['sconf_id']) ? $langValue['sconf_id'] : 0;
            $data = [];

            if (!empty($locale)) {
                $locale = $locale[0];
            }

            foreach ($langValue['config'] as $configKey => $configValue) {
                $confKey = substr($configKey, strpos($configKey, '_') + 1);

                if ($langKey == 'gen') {
                    $dataFromConfig = $config['site'][$siteName]['allSites'][$confKey];

                    if (!empty($configFromDb)) {
                        foreach ($configFromDb as $confDb) {
                            if ($confDb['sconf_lang_id'] == '-1') {
                                if (array_key_exists($confKey, unserialize($confDb['sconf_datas'])['site'][$siteName]['allSites'])) {
                                    $data['site'][$siteName]['allSites'][$confKey] = $this->getTool()->sanitize($configValue, true);
                                } else {
                                    if ($dataFromConfig !== $configValue) {
                                        $data['site'][$siteName]['allSites'][$confKey] = $this->getTool()->sanitize($configValue, true);
                                    }
                                }
                            } else {
                                if ($dataFromConfig !== $configValue) {
                                    $data['site'][$siteName]['allSites'][$confKey] = $this->getTool()->sanitize($configValue, true);
                                }
                            }
                        }
                    } else {
                        if ($dataFromConfig !== $configValue) {
                            $data['site'][$siteName]['allSites'][$confKey] = $this->getTool()->sanitize($configValue, true);
                        }
                    }
                } else {
                    $dataFromConfig = $config['site'][$siteName][$siteId][$locale['lang_cms_locale']][$confKey];

                    if (!empty($configFromDb)) {
                        foreach ($configFromDb as $confDb) {
                            if ($confDb['sconf_lang_id'] == $langKey) {
                                if (array_key_exists($confKey, unserialize($confDb['sconf_datas'])['site'][$siteName][$siteId])) {
                                    $data['site'][$siteName][$siteId][$locale['lang_cms_locale']][$confKey] = $this->getTool()->sanitize($configValue, true);
                                } else {
                                    if ($dataFromConfig !== $configValue) {
                                        $data['site'][$siteName][$siteId][$locale['lang_cms_locale']][$confKey] = $this->getTool()->sanitize($configValue, true);
                                    }
                                }
                            } else {
                                if ($dataFromConfig !== $configValue) {
                                    $data['site'][$siteName][$siteId][$locale['lang_cms_locale']][$confKey] = $this->getTool()->sanitize($configValue, true);
                                }
                            }
                        }
                    } else {
                        if ($dataFromConfig != $configValue) {
                            $data['site'][$siteName][$siteId][$locale['lang_cms_locale']][$confKey] = $this->getTool()->sanitize($configValue, true);
                        }
                    }
                }
            }

            if (!empty($data)) {
                $siteConfigTable->save(
                    [
                        'sconf_site_id' => $siteId,
                        'sconf_lang_id' => $langKey === 'gen' ? -1 : $langKey,
                        'sconf_datas' => serialize($data)
                    ],
                    $sconf_id
                );
            }
        }
    }

    /**
     * return site config (from db only)
     * @param $siteId
     * @return mixed
     */
    private function getSiteConfigFromDbById($siteId)
    {
        $siteConfigTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteConfig');

        return $siteConfigTable->getEntryByField('sconf_site_id', $siteId)->toArray();
    }

    /**
     * returns site config (merged)
     * @param $siteId
     * @return mixed
     */
    private function getSiteConfigById($siteId)
    {
        $siteConfigSrv = $this->getServiceLocator()->get('MelisSiteConfigService');

        return $siteConfigSrv->getSiteConfigById($siteId);
    }

    /**
     * returns site data
     * @param $siteId
     * @return array
     */
    private function getSiteDataById($siteId)
    {
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        $site = $siteTable->getEntryById($siteId)->toArray();

        return !empty($site) ? $site[0] : [];
    }

    /**
     * returns meliskey from route or from query
     * @return mixed
     */
    private function getMelisKey()
    {
        $melisKey = $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey'), null);

        return $melisKey;
    }

    /**
     * returns tools service
     * @return array|object
     */
    private function getTool()
    {
        $toolSvc = $this->getServiceLocator()->get('MelisCoreTool');
        $toolSvc->setMelisToolKey('meliscms', 'meliscms_tool_sites');

        return $toolSvc;
    }
}
