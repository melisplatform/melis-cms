<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2019 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class SitesTranslationController extends AbstractActionController
{
    const TOOL_INDEX = 'meliscms';
    const TOOL_KEY = 'site_translation_tool';

    public function renderToolSitesSiteTranslationContentFiltersLimitAction()
    {
        return new ViewModel();
    }

    public function renderToolSitesSiteTranslationContentFiltersRefreshAction()
    {
        return new ViewModel();
    }

    public function renderToolSitesSiteTranslationContentFiltersSearchAction()
    {
        return new ViewModel();
    }

    public function renderToolSitesSiteTranslationActionEditAction()
    {
        return new ViewModel();
    }

    public function renderToolSitesSiteTranslationActionDeleteAction()
    {
        return new ViewModel();
    }

    /**
     * Function to render site translation
     *
     * @return ViewModel
     */
    public function renderMelisSiteTranslationAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $view = new ViewModel();
        $view->melisKey = $melisKey;
        return $view;
    }

    /**
     * Function to render site translation modal
     *
     * @return ViewModel
     */
    public function renderToolSitesSiteTranslationModalAction()
    {
        $id = $this->params()->fromRoute('id', $this->params()->fromQuery('id', ''));
        $melisKey = $this->params()->fromRoute('melisKey', $this->params()->fromQuery('melisKey', ''));

        $view = new ViewModel();
        $view->setTerminal(true);
        $view->id = $id;
        $view->melisKey = $melisKey;
        return $view;
    }

    /**
     * Function to render site translation edit modal
     *
     * @return ViewModel
     */
    public function renderToolSitesSiteTranslationModalEditAction()
    {
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $translationKey = $this->params()->fromQuery('translationKey', null);
        $siteId = $this->params()->fromQuery('siteId', 0);

        // declare the Tool service that we will be using to completely create our tool.
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');

        // tell the Tool what configuration in the app.tools.php that will be used.
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        //prepare the user profile form
        $form = $melisTool->getForm('sitestranslation_form');

        $melisSiteTranslationService = $this->getServiceLocator()->get('MelisSiteTranslationService');
        $transData = $melisSiteTranslationService->getSiteTranslation($translationKey, null, $siteId);

        $sitelangsTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteLangs');
        $siteLangs = $sitelangsTable->getSiteLanguagesBySiteId($siteId)->toArray();

        $view = new ViewModel();
        $view->setVariable('sitestranslation_form', $form);
        $view->melisKey = $melisKey;
        $view->siteLangs = $siteLangs;
        $view->transData = $transData;
        $view->transKey = $translationKey;
        return $view;
    }

    /**
     * Function to render the site translation content
     *
     * @return ViewModel
     */
    public function renderToolSitesSiteTranslationsAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        $melisKey = $this->params()->fromRoute('melisKey', '');
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
        $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
        $siteId = (int) $this->params()->fromQuery('siteId', '');

        $columns = $melisTool->getColumns();
        // pre-add Action Columns
        $columns['actions'] = array('text' => $translator->translate('tr_meliscore_global_action'), 'css' => 'width:10%');

        $view = new ViewModel();
        $view->melisKey = $melisKey;
        $view->tableColumns = $columns;
        $view->getToolDataTableConfig = $melisTool->getDataTableConfiguration('#'.$siteId.'_tableMelisSiteTranslation');
        $view->siteId = $siteId;

        return $view;
    }

    /**
     * Function to delete the translation text from the database only
     *
     * @return JsonModel
     */
    public function deleteTranslationAction()
    {
        $success = false;
        //get the request
        $request = $this->getRequest();
        $data = get_object_vars($request->getPost());

        $melisSiteTranslationService = $this->getServiceLocator()->get('MelisSiteTranslationService');
        $res = $melisSiteTranslationService->deleteTranslation($data);

        if($res)
            $success = true;

        //prepare the data to return
        $response = array(
            'success'  =>  $success,
        );
        return new JsonModel($response);
    }

    /**
     * Function to insert / update translation
     * The post data contains mst_data array, mstt_data array and the id of both(mst_id, mstt_id - for updating a record)
     * mst_data - contains the data to be insert / update in the melis_site_translation table
     * mstt_data - contains the data to be insert / update in the melis_site_translation_text table
     * mst_id - the id of the data to be update in melis_site_translation table
     * mstt_id the id of the data to be update in melis_site_translation_text table
     * @return JsonModel
     */
    public function saveTranslationAction()
    {
        $success = false;
        $errors = array();
        $langError = array();

        $melisMelisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');
        $melisTool = $this->getServiceLocator()->get('MelisCoreTool');

        $factory = new \Zend\Form\Factory();
        $formElements = $this->getServiceLocator()->get('FormElementManager');
        $factory->setFormElementManager($formElements);

        $siteTranslationData = array();

        //get the request
        $request = $this->getRequest();
        //check if request is post
        if($request->isPost())
        {
            $data = $request->getPost();
            foreach($data as $key => $val) {
                $fieldName = explode('-', $key);
                if(!empty($fieldName[1])){
                    if(!isset($siteTranslationData[$fieldName[0]])){
                        /**
                         * Prepare the data of the translation
                         * by each language
                         */
                        $siteTranslationData[$fieldName[0]] = array();
                        $siteTranslationData[$fieldName[0]]['mst_data'] = array();
                        $siteTranslationData[$fieldName[0]]['mstt_data'] = array();
                        /**
                         * get the mst and mstt id to determine
                         * whether we are going to update the translation
                         * or we just need to update
                         */
                        $siteTranslationData[$fieldName[0]]['mst_id'] = $data[$fieldName[0].'-mst_id'];
                        $siteTranslationData[$fieldName[0]]['mstt_id'] = $data[$fieldName[0].'-mstt_id'];
                    }

                    /**
                     * Prepare the mst and mstt data
                     */
                    if (strpos($fieldName[1], 'mst_') !== false) {
                        $siteTranslationData[$fieldName[0]]['mst_data'][$fieldName[1]] = $val;
                    }

                    if (strpos($fieldName[1], 'mstt_') !== false) {
                        $siteTranslationData[$fieldName[0]]['mstt_data'][$fieldName[1]] = $val;
                    }
                }
            }
            /**
             * validate translation form
             */
            foreach($siteTranslationData as $key => $transData){
                /**
                 * unset the mst and mstt id
                 * set the mstt_mst_id if not empty
                 */
                if(isset($transData['mst_id'])){
                    $mstId = $transData['mst_id'];
                    if(!empty($mstId)){
                        $siteTranslationData[$key]['mstt_data']['mstt_mst_id'] = $mstId;
                    }
                    unset($siteTranslationData[$key]['mst_data']['mst_id']);
                    unset($siteTranslationData[$key]['mstt_data']['mstt_id']);
                }
                $appConfigForm = $melisMelisCoreConfig->getFormMergedAndOrdered('meliscms/tools/site_translation_tool/forms/sitestranslation_form','sitestranslation_form');
                $propertyForm = $factory->createForm($appConfigForm);
                //we need to merge the data from mst_data and mstt_data array to validate the form
                $tempFormValidationData = array_merge($transData['mst_data'], $transData['mstt_data']);
                //assign the data to the form
                $propertyForm->setData($tempFormValidationData);
                //check if form is valid(if all the form field are match with the value that we pass from routes)
                if(!$propertyForm->isValid()) {
                    $appConfigForm = $appConfigForm['elements'];
                    $formErrors = $propertyForm->getMessages();
                    $errors = array_merge($errors, $this->processErrors($formErrors, $appConfigForm, $key));
                    array_push($langError, $key);
                }
            }

            if(empty($errors)){
                /**
                 * if form is valid, save the data
                 */
                //get and sanitize the data
                $postValues = $melisTool->sanitizeRecursive($siteTranslationData, array('mstt_text'), false, true);
                $melisSiteTranslationService = $this->getServiceLocator()->get('MelisSiteTranslationService');
                $res = $melisSiteTranslationService->saveTranslation($postValues);
                $success = $res['success'];
            }
        }

        //prepare the data to return
        $response = array(
            'success'  =>  $success,
            'errors' => $errors,
            'langErrorIds' => $langError,
        );
        return new JsonModel($response);
    }

    /**
     * Function to get all translation from both file and database
     *
     * @return JsonModel
     */
    public function getTranslationAction()
    {
        $dataCount = 0;
        $data = array();
        $draw = 0;
        $recordsFiltered = 0;
        $hasFilter = false;

        if($this->getRequest()->isPost()) {

            //get site id
            $siteId = $this->getRequest()->getPost('siteId');

            $melisTool = $this->getServiceLocator()->get('MelisCoreTool');
            $melisTool->setMelisToolKey(self::TOOL_INDEX, self::TOOL_KEY);
            $colId = array_keys($melisTool->getColumns());

            //get the datatable parameters
            //get draw(page number)
            $draw = $this->getRequest()->getPost('draw');
            //get search value
            $search = $this->getRequest()->getPost('search');
            $search = $search['value'];
            //get site
            $selectedLang = $this->getRequest()->getPost('site_translation_language_name');
            //get start(where to start to get package)
            $start = $this->getRequest()->getPost('start');
            //get length(how many package will be displayed)
            $length = $this->getRequest()->getPost('length');

            $melisSiteTranslationService = $this->getServiceLocator()->get('MelisSiteTranslationService');
            //get the current usded lang id from the session
            $container = new Container('meliscore');
            $langIdBO = $container['melis-lang-id'];
            $langId = null;
            //get the language information

            /**
             * check if the user filter the list by language
             */
            if(empty($selectedLang)) {
                /**
                 * since there are possibility that the back office language and front language are not the same
                 * we need to get the language information from back office and compare it from the cms language
                 * using the language locale of both to get the exact language id
                 * to make sure that we retrieve the exact translation
                 */
                $langCoreTbl = $this->getServiceLocator()->get('MelisCoreTableLang');
                $langDetails = $langCoreTbl->getEntryById($langIdBO)->toArray();
                if ($langDetails) {
                    $langCmsTbl = $this->getServiceLocator()->get('MelisEngineTableCmsLang');
                    foreach ($langDetails as $langBO) {
                        $localeBO = $langBO['lang_locale'];
                        $langCmsDetails = $langCmsTbl->getEntryByField('lang_cms_locale', $localeBO)->toArray();
                        foreach ($langCmsDetails as $langCms) {
                            $langId = $langCms['lang_cms_id'];
                        }
                    }
                }
            }else{
                /**
                 * assign the selected user lang as default
                 * language of the list
                 */
                $langId = $selectedLang;
            }
            //prepare the data to paginate
            $dataArr = $melisSiteTranslationService->getSiteTranslation(null, null ,$siteId);

            $a = [];

            /**
             * This will select only the translation
             * depending on the lang id given
             */
            $tempAr = [];
            $tempAr2 = [];
            $temp = [];

            //loop to separate the translation from the current BO language
            foreach($dataArr as $d){
                if($d['mstt_lang_id'] == $langId){
                    array_push($tempAr, $d);
                    array_push($temp, $d['mst_key']);
                }else{
                    array_push($tempAr2, $d);
                }
            }

            foreach($temp as $x){
                foreach($tempAr2 as $key => $val){
                    if($x == $val['mst_key']){
                        unset($tempAr2[$key]);
                    }
                }
            }

            /**
             * Lets check again to avoid duplicate translations
             *
             * This will check only those translations that
             * has different language from the BO
             *
             * Like if there are two or more translation keys inside the
             * array that has different language but not equal to the
             * BO lang, we need to get only one translations
             * to display
             */
            $otherTransKey = array();
            foreach($tempAr2 as $k => $v){
                if(in_array($v['mst_key'], $otherTransKey)){
                    unset($tempAr2[$k]);
                }else{
                    array_push($otherTransKey, $v['mst_key']);
                }
            }

            $data = array_merge($tempAr, $tempAr2);

            $data = array_values(array_unique($data, SORT_REGULAR));

            //striping tags to show full text on text column
            foreach ($data as $key =>$d){
                $data[$key]["mstt_text"] = strip_tags($data[$key]["mstt_text"]);
            }

            //process the translation list(pagination)
            for ($i = 0; $i < sizeof($data); $i++) {
                $data[$i]['mstt_text'] = $melisTool->sanitize($data[$i]['mstt_text']);

                //prepare the attribute for our row in the table
                $attrArray = array('data-lang-id'     => $data[$i]['mstt_lang_id'],
                    'data-mst-id'     => $data[$i]['mst_id'],
                    'data-mstt-id'    => $data[$i]['mstt_id'],
                    'data-site-id'    => $data[$i]['mst_site_id']);

                //assign attribute data to table row
                $data[$i]['DT_RowAttr'] = $attrArray;

                //check if search is not empty(to filter by search)
                if (!empty($search)) {
                    $hasFilter = true;
                    //loop through each field to get its text, and check if has contain the $search value
                    foreach ($colId as $key => $val) {
                        if (isset($data[$i][$val])) {
                            if (strpos(strtolower($data[$i][$val]), strtolower($search)) !== false) {
                                //if found push the data
                                array_push($a, $data[$i]);
                                break;
                            }
                        }
                    }
                }
            }

            if($hasFilter){
                //we need to make sure that there is no duplicate data in the array, and we need to re-index it again
                $data = array_values(array_unique($a, SORT_REGULAR));
                $recordsFiltered = $a;
            }else{
                $recordsFiltered = $data;
            }

            $data = array_splice($data, $start, $length);
            //sort the result by module name
            usort($data, function($a, $b){
                return strcasecmp($a['module'], $b['module']);
            });
        }

        return new JsonModel(array(
            'draw' => (int) $draw,
            'recordsTotal' => $dataCount,
            'recordsFiltered' =>  count($recordsFiltered),
            'data' => $data,
        ));
    }
    
    /**
     * Function to add site translation
     * filter by site language
     *
     * @return ViewModel
     */
    public function renderToolSitesSiteTranslationFiltersLanguagesAction()
    {
        $siteId = (int) $this->params()->fromQuery('siteId', '');

        $translator = $this->getServiceLocator()->get('translator');
        $sitelangsTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteLangs');
        $siteLangs = $sitelangsTable->getSiteLanguagesBySiteId($siteId)->toArray();

        $langs = array();
        $langs[] = '<option value="">'. $translator->translate('tr_melis_site_translation_choose') .'</option>';
        foreach($siteLangs as $lang){
            $langs[] = '<option value="'.$lang['lang_cms_id'].'">'. $lang['lang_cms_name'].'</option>';
        }

        $view = new ViewModel();
        $view->languages = $langs;
        return $view;
    }

    /**
     * Function to process the errors
     *
     * @param array $errors
     * @param Form $appConfigForm
     * @param int $langId
     * @return array $errors
     */
    private function processErrors($errors, $appConfigForm, $langId)
    {
        $modifiedError = array();
        //loop through each errors
        foreach ($errors as $keyError => $valueError)
        {
            $fieldName = $langId.'-'.$keyError;
            $modifiedError = array($fieldName => $valueError);
            //look in the form for every failed field to specify the errors
            foreach ($appConfigForm as $keyForm => $valueForm)
            {
                if(isset($valueForm['spec'])) {
                    //check if field name is equal with the error key to highlight the field
                    if ($valueForm['spec']['name'] == $keyError &&
                        !empty($valueForm['spec']['options']['label'])) {
                        $modifiedError[$fieldName]['label'] = $valueForm['spec']['options']['label'];
                    }
                }
            }
        }
        return $modifiedError;
    }
}