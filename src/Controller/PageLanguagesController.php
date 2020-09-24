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
use Laminas\Session\Container;
use MelisCms\Service\MelisCmsRightsService;
use Laminas\Form\Factory;
use MelisCore\Controller\MelisAbstractActionController;

/**
 * This class renders Melis CMS Page language
 */
class PageLanguagesController extends MelisAbstractActionController
{
    /**
     * This method render the Melis Page language
     *
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderPagetabLanguagesAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage'));
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;
        
        return $view;
    }
    
    /**
     * This menthod render the List of languages versions of the current page
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderPagetabLangListAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage'));
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        // Retrieving the list of Page languages
        $pageLangTbl = $this->getServiceManager()->get('MelisEngineTablePageLang');
        $pageLang = $pageLangTbl->getEntryByField('plang_page_id', $idPage)->current();
        
        $pagesData = array();
        
        $pageInitialId = null;
        
        if (!empty($pageLang))
        {
            $pageInitialId = $pageLang->plang_page_id_initial;
            
            $pages = $pageLangTbl->getEntryByField('plang_page_id_initial', $pageInitialId);
            
            $pageSrv = $this->getServiceManager()->get('MelisEnginePage');
            foreach ($pages As $val)
            {
                $pageData = $pageSrv->getDatasPage($val->plang_page_id, 'saved')->getMelisPageTree();
                
                if (empty($pageData->page_id))
                {
                    $pageData = $pageSrv->getDatasPage($val->plang_page_id)->getMelisPageTree();
                }
                
                if (!empty($pageData))
                {
                    // Adding language flag
                    $langFlag = '/MelisCms/images/lang-flags/default.png';
                    if (file_exists(__DIR__.'/../../public/images/lang-flags/'.$pageData->lang_cms_locale.'.png'))
                    {
                        $langFlag = '/MelisCms/images/lang-flags/'.$pageData->lang_cms_locale.'.png';
                    }
                    
                    $pageData->lang_cms_flag = $langFlag;
                    
                    array_push($pagesData, $pageData);
                }
                
            }
        }
        
        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;
        $view->pagesData= $pagesData;
        $view->initialPageId = $pageInitialId;
        
        return $view;
    }
    
    /**
     * This method render the Page create form 
     * 
     * @return \Laminas\View\Model\ViewModel
     */
    public function renderPagetabLangCreateAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage'));
        $melisKey = $this->params()->fromRoute('melisKey', '');
        // get page versions (by language)
        $pageLangTbl = $this->getServiceManager()->get('MelisEngineTablePageLang');
        $pageLang = $pageLangTbl->getEntryByField('plang_page_id', $idPage)->current(); 
        $pagesLang = [];
        // we keep track on languages that we already have for the page
        if (! empty($pageLang)) {
            $pageInitialId = $pageLang->plang_page_id_initial;
            $pageLang = $pageLangTbl->getEntryByField('plang_page_id_initial', $pageInitialId);
            
            foreach ($pageLang As $val)
                array_push($pagesLang, $val->plang_lang_id);
        }

        // get the site id
        $treeService = $this->getServiceManager()->get('MelisTreeService');
        $site = $treeService->getSiteByPageId($idPage);
        if (! empty($site))
            $siteId = $site->site_id;
        // get available and active site languages
        $siteLangTable = $this->getServiceManager()->get('MelisEngineTableCmsSiteLangs');
        if (! empty($siteId))
            $languages = $siteLangTable->getSiteLanguagesBySiteId($siteId)->toArray();
        else
            $languages = [];

        $langFlags = '';
        $langsToDisplay = [];
        foreach ($languages as $language) {
            // we will only display the language if we still don't have the page in that language
            if (! in_array($language['slang_lang_id'], $pagesLang)) {
                $langsToDisplay[$language['lang_cms_locale']] = $language['lang_cms_name'];
                // get flag
                $langFlag = '/MelisCms/images/lang-flags/default.png';
                if (file_exists(__DIR__.'/../../public/images/lang-flags/'.$language['lang_cms_locale'].'.png'))
                    $langFlag = '/MelisCms/images/lang-flags/'.$language['lang_cms_locale'].'.png';
                $langFlags .= '<span id="'.$idPage.$language['lang_cms_locale'].'"><img src="'.$langFlag.'" class="img-flag" /> '.$language['lang_cms_name'].'</span>'.PHP_EOL;
            }
        }
        
        // Page language create from
        $form = $this->pageLangCreateForm();
        // Set current page id value
        $form->get('pageLangPageId')->setValue($idPage);
        $form->get('pageLangLocale')->setValueOptions($langsToDisplay);
        
        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;
        $view->createPageLangform = $form;
        $view->langFlags = $langFlags;
        
        return $view;
    }

    public function convertLanguageByLocale()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));

        $cmsPageLangTbl = $this->getServiceManager()->get('MelisEngineTablePageLang');
        $cmsPageLang = $cmsPageLangTbl->getEntryByField('plang_page_id', $idPage)->current();

        if (!empty($cmsPageLang))
        {
            if ($cmsPageLang->plang_page_id_initial == $idPage)
            {
                $cmsPageLangs = $cmsPageLangTbl->getEntryByField('plang_page_id_initial', $idPage);

                $newInitialPageId = null;


            }
        }

        return new JsonModel(array('success' => 0));
    }

    /**
     * This method validate and save the submitted form
     * and create a new Page language version
     * 
     * @return \Laminas\View\Model\JsonModel
     */
    public function createNewPageLangVersionAction()
    {
        $request = $this->getRequest();
        $status  = 0;
        $textMessage = 'tr_meliscms_page_lang_create_failed';
        $errors  = array();
        $pageId = null;
        $pageInfo = array();
        
        if ($request->isPost())
        {
            $data = get_object_vars($request->getPost());
            
            // Page language create from
            $form = $this->pageLangCreateForm();
            $form->setData($data);
            
            if ($form->isValid())
            {
                
                $data = $form->getData();

                /**
                 * Page tree data of the new Page Language
                 */
                $pageTree = array(
                    'tree_father_page_id' => $data['pageLangPageId']
                );
                $pageLangIds = array();

                $cmsLangTbl      = $this->getServiceManager()->get('MelisEngineTableCmsLang');
                $pageLangTbl     = $this->getServiceManager()->get('MelisEngineTablePageLang');
                $pageInitialId   = $pageLangTbl->getEntryByField('plang_page_id',$data['pageLangPageId'])->current();
                $pageLangInitial = $pageLangTbl->getEntryByField('plang_page_id_initial',$pageInitialId->plang_page_id_initial)->toArray();

                foreach($pageLangInitial as $pagelangid => $pagelangVal)
                {
                    $pageLangIds[] = $pagelangVal['plang_lang_id'];
                }

                $cmsLangData     = $cmsLangTbl->getEntryByField('lang_cms_locale', $data['pageLangLocale'])->current();
                $checkPageLangId = in_array($cmsLangData->lang_cms_id, $pageLangIds);


                if(empty($checkPageLangId)){
                    $cmsLangPrefix = !empty($cmsLangData)? ' ('.strtolower(substr($cmsLangData->lang_cms_locale, 0, 2)).')': '';

                    /**
                     * Retrieving the published data of the current page and
                     * use to create the new Page
                     */
                    $pagePublishedTbl = $this->getServiceManager()->get('MelisEngineTablePagePublished');
                    $currentPage = $pagePublishedTbl->getEntryById($data['pageLangPageId'])->current();
                    $pagePublished = array();
                    if (!empty($currentPage))
                    {
                        $pagePublished = get_object_vars($currentPage);
                        // Adding prefix of the language to page name
                        $pagePublished['page_name'] .= $cmsLangPrefix;
                        // Set new page language version to unpublish
                        $pagePublished['page_status'] = 0;
                    }

                    /**
                     * Retrieving the saved data of the current page and
                     * use to create the new Page
                     */
                    $pageSavedTbl = $this->getServiceManager()->get('MelisEngineTablePageSaved');
                    $currentPage = $pageSavedTbl->getEntryById($data['pageLangPageId'])->current();
                    $pageSaved = array();
                    if (!empty($currentPage))
                    {
                        $pageSaved = get_object_vars($currentPage);
                        // Adding prefix of the language to page name
                        $pageSaved['page_name'] .= $cmsLangPrefix;
                        // Set new page language version to unpublish
                        $pageSaved['page_status'] = 0;
                    }

                    /**
                     * Retrieving the seo data of the current page and
                     * use to create the new Page
                     */
                    $pageSeoTbl = $this->getServiceManager()->get('MelisEngineTablePageSeo');
                    $currentPage = $pageSeoTbl->getEntryById($data['pageLangPageId'])->current();
                    $pageSeo = array();
                    if (!empty($currentPage))
                    {
                        $pageSeo = get_object_vars($currentPage);
                    }

                    /**
                     * Page lang data of the new Page Language
                     * using the current page as parent page id
                     */

                    $pageLang = array(
                        'plang_lang_id' => $cmsLangData->lang_cms_id,
                        'plang_page_id_initial' => $pageInitialId->plang_page_id_initial,
                    );

                    /**
                     * Retrieving the style data of the current page and
                     * use to create the new Page
                     */
                    $pageStyleTbl = $this->getServiceManager()->get('MelisEngineTablePageStyle');
                    $currentPage = $pageStyleTbl->getEntryByField('pstyle_page_id', $data['pageLangPageId'])->current();
                    $pageStyle = array();
                    if (!empty($currentPage))
                    {
                        $pageStyle = get_object_vars($currentPage);
                        unset($pageStyle['pstyle_id']);
                    }

                    // unsetting date edit
                    unset($pagePublished['page_edit_date']);
                    unset($pageSaved['page_edit_date']);

                    /**
                     * Saving the Page using the MelisCmsPageService Service
                     */
                    $pageSrv = $this->getServiceManager()->get('MelisCmsPageService');
                    $pageId = $pageSrv->savePage($pageTree, $pagePublished, $pageSaved, $pageSeo, $pageLang, $pageStyle);

                    if (!is_null($pageId))
                    {
                        /**
                         * Retrieving the new created page for response of the request call
                         */
                        $pageSrv = $this->getServiceManager()->get('MelisEnginePage');
                        $pageData = $pageSrv->getDatasPage($pageId, 'saved')->getMelisPageTree();

                        $pageInfo = array(
                            'tabicon' => 'fa-file-o',
                            'name' => $pageData->page_name,
                            'id' => $pageData->tree_page_id.'_id_meliscms_page',
                            'meliskey' => 'meliscms_page',
                            'pageid' => $pageData->tree_page_id
                        );

                        $textMessage = 'tr_meliscms_page_lang_create_success';
                        $status = 1;
                    }
                }else{
                    $textMessage = 'tr_meliscms_page_lang_create_failed';
                }

            }
            else
            {
                $errors = $form->getMessages();
                // insert labels and error messages in error array
                $melisMelisCoreConfig = $this->getServiceManager()->get('MelisCoreConfig');
                $appConfigForm = $melisMelisCoreConfig->getItem('meliscms/forms/meliscms_page_languages');
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
        }
        
        $response = array(
            'success' => $status,
            'textTitle' => 'tr_meliscms_page_lang_create_title',
            'textMessage' => $textMessage,
            'errors' => $errors,
            'pageInfo' => $pageInfo,
        );
        
        // End event of the request
        $this->getEventManager()->trigger('meliscms_create_new_page_lang_end', $this, array_merge($response, array('typeCode' => 'CMS_PAGE_LANGUAGE_VERSION_CREATE', 'itemId' => $pageId)));
        
        return new JsonModel($response);
    }
    
    /**
     * Form of the Page language for creating New Page langugae version
     * 
     * @return Laminas\Form\Factory
     */
    private function pageLangCreateForm()
    {
        /**
         * Get the config for this form
         */
        $melisMelisCoreConfig = $this->getServiceManager()->get('MelisCoreConfig');
        $appConfigForm = $melisMelisCoreConfig->getItem('/meliscms/forms/meliscms_page_languages', 'meliscms_page_languages');
        
        /**
         * Generate the form through factory and change ElementManager to
         * have access to our custom Melis Elements
         * Bind with datas
         */
        $factory = new Factory();
        $formElements = $this->getServiceManager()->get('FormElementManager');
        $factory->setFormElementManager($formElements);
        $form = $factory->createForm($appConfigForm);
        
        return $form;
    }

    
    /**
     * This method will set another page as initial page language
     * after deletion of the original page
     * 
     */
    public function setInitialPageLanguageAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        
        $cmsPageLangTbl = $this->getServiceManager()->get('MelisEngineTablePageLang');
        $cmsPageLang = $cmsPageLangTbl->getEntryByField('plang_page_id', $idPage)->current();
        
        if (!empty($cmsPageLang))
        {
            if ($cmsPageLang->plang_page_id_initial == $idPage)
            {
                $cmsPageLangs = $cmsPageLangTbl->getEntryByField('plang_page_id_initial', $idPage);
                
                $newInitialPageId = null;
                
                // Assigning new initail page to other pages
                foreach ($cmsPageLangs As $val)
                {
                    if ($val->plang_page_id !==  $idPage)
                    {
                        if (is_null($newInitialPageId))
                        {
                            $newInitialPageId = $val->plang_page_id;
                        }
                        
                        if (!is_null($newInitialPageId))
                        {
                            $cmsPageLangTbl->save(array(
                                'plang_page_id_initial' => $newInitialPageId
                            ), $val->plang_id);
                        }
                    }
                }
            }
        }
        
        return new JsonModel(array('success' => 1));
    }



}