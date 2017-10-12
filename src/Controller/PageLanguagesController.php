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
use Zend\Session\Container;
use MelisCms\Service\MelisCmsRightsService;
use Zend\Form\Factory;

/**
 * This class renders Melis CMS Page language
 */
class PageLanguagesController extends AbstractActionController
{
    /**
     * This method render the Melis Page language
     *
     * @return \Zend\View\Model\ViewModel
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
     * This method render the Header information zone of the page
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPagetabLangInfoAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage'));
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        $pageLangTbl = $this->getServiceLocator()->get('MelisEngineTablePageLang');
        
        $pageLang = $pageLangTbl->getEntryByField('plang_page_id', $idPage)->current();
        
        $pageInfo = null;
        
        if (!empty($pageLang))
        {
            $pageInitialId = $pageLang->plang_page_id_initial;
            
            $pageSrv = $this->getServiceLocator()->get('MelisEnginePage');
            $pageData = $pageSrv->getDatasPage($pageInitialId)->getMelisPageTree();
            
            if (!empty($pageData))
            {
                // Adding attribute of the Page name label
                $tabAttr = array(
                    'class="open-page-from-lan-tab"',
                    'data-opentab',
                    'data-tabicon="fa-file-o"',
                    'data-name="'.$pageData->page_name.'"',
                    'data-id="'.$pageData->tree_page_id.'_id_meliscms_page"',
                    'data-meliskey="meliscms_page"',
                    'data-pageid="'.$pageData->tree_page_id.'"'
                );
                
                $tabAttr = implode(' ', $tabAttr);
                
                $translate = $this->getServiceLocator()->get('translator');
                $pageInfo = sprintf($translate->translate('tr_meliscms_page_lang_info'),  $pageData->lang_cms_name, $tabAttr, $pageData->page_name.' ('.$pageData->tree_page_id.')');
            }
        }
        
        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;
        $view->pageInfo = $pageInfo;
        
        return $view;
    }
    
    /**
     * This menthod render the List of languages versions of the current page
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPagetabLangListAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage'));
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        // Retrieving the list of Page languages
        $pageLangTbl = $this->getServiceLocator()->get('MelisEngineTablePageLang');
        $pageLang = $pageLangTbl->getEntryByField('plang_page_id', $idPage)->current();
        
        $pagesData = array();
        
        if (!empty($pageLang))
        {
            $pageInitialId = $pageLang->plang_page_id_initial;
            
            $pages = $pageLangTbl->getEntryByField('plang_page_id_initial', $pageInitialId);
            
            $pageSrv = $this->getServiceLocator()->get('MelisEnginePage');
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
        
        return $view;
    }
    
    /**
     * This method render the Page create form 
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function renderPagetabLangCreateAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage'));
        $melisKey = $this->params()->fromRoute('melisKey', '');
        
        // Page language create from
        $form = $this->pageLangCreateForm();
        
        // Set current page id value
        $form->get('pageLangPageId')->setValue($idPage);
        
        $view = new ViewModel();
        $view->idPage = $idPage;
        $view->melisKey = $melisKey;
        $view->createPageLangform = $form;
        
        return $view;
    }
    
    /**
     * This method validate and save the submitted form
     * and create a new Page language version
     * 
     * @return \Zend\View\Model\JsonModel
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
                
                $cmsLangTbl = $this->getServiceLocator()->get('MelisEngineTableCmsLang');
                $cmsLangData = $cmsLangTbl->getEntryByField('lang_cms_locale', $data['pageLangLocale'])->current();
                
                $cmsLangPrefix = !empty($cmsLangData)? ' ('.strtolower(substr($cmsLangData->lang_cms_locale, 0, 2)).')': '';
                
                /**
                 * Retrieving the published data of the current page and 
                 * use to create the new Page
                 */
                $pagePublishedTbl = $this->getServiceLocator()->get('MelisEngineTablePagePublished');
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
                $pageSavedTbl = $this->getServiceLocator()->get('MelisEngineTablePageSaved');
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
                $pageSeoTbl = $this->getServiceLocator()->get('MelisEngineTablePageSeo');
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
                    'plang_page_id_initial' => $data['pageLangPageId'],
                );
                
                /**
                 * Retrieving the style data of the current page and
                 * use to create the new Page
                 */
                $pageStyleTbl = $this->getServiceLocator()->get('MelisEngineTablePageStyle');
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
                $pageSrv = $this->getServiceLocator()->get('MelisCmsPageService');
                $pageId = $pageSrv->savePage($pageTree, $pagePublished, $pageSaved, $pageSeo, $pageLang, $pageStyle);
                
                if (!is_null($pageId))
                {
                    /**
                     * Retrieving the new created page for response of the request call
                     */
                    $pageSrv = $this->getServiceLocator()->get('MelisEnginePage');
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
            }
            else
            {
                $errors = $form->getMessages();
                // insert labels and error messages in error array
                $melisMelisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');
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
     * @return Zend\Form\Factory
     */
    private function pageLangCreateForm()
    {
        /**
         * Get the config for this form
         */
        $melisMelisCoreConfig = $this->serviceLocator->get('MelisCoreConfig');
        $appConfigForm = $melisMelisCoreConfig->getItem('/meliscms/forms/meliscms_page_languages', 'meliscms_page_languages');
        
        /**
         * Generate the form through factory and change ElementManager to
         * have access to our custom Melis Elements
         * Bind with datas
         */
        $factory = new Factory();
        $formElements = $this->serviceLocator->get('FormElementManager');
        $factory->setFormElementManager($formElements);
        $form = $factory->createForm($appConfigForm);
        
        return $form;
    }
}