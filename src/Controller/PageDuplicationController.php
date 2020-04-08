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
use MelisCore\Controller\AbstractActionController;

/**
 * This class handles the display of the page duplicate button and its' events
 */
class PageDuplicationController extends AbstractActionController
{

    /**
     * Renders the display of the duplicate button within the melis cms page edition
     * @return ViewModel
     */
    public function renderPageDuplicateButtonAction()
    {
        $idPage = $this->params()->fromRoute('idPage', $this->params()->fromQuery('idPage', ''));
        $melisKey = $this->params()->fromRoute('melisKey', '');

        $view = new ViewModel();

        $view->idPage   = $idPage;
        $view->melisKey = $melisKey;

        return $view;
    }

    /**
     * Return the origin of the page by id
     *
     * return array
     */
    public function getOriginOfPageByPageIdAction()
    {
        $data = array();
        $tool = $this->getServiceManager()->get('MelisCmsPage');
        $data = $tool->getOriginOfPage()->toArray();

        return $data;

    }

    /**
     * Mocks the event for creating a new page and updating its' content
     * so it can create a real copy of the select page.
     * @return JsonModel
     */
    public function duplicatePageAction()
    {
        $success = 0;
        $title   = 'tr_meliscms_duplicate_page_title';
        $message = 'tr_meliscms_duplicate_error';
        $pageId  = null;
        $errors  = array();
        $data    = array();
        $request = $this->getRequest();

        if($request->isPost()) {
            $pageId = (int) $request->getPost('id');
            $cachePageId = $pageId;
            $pageService = $this->getServiceManager()->get('MelisEnginePage');
            $pageData    = $pageService->getDatasPage($pageId);
            if(empty($pageData->getMelisTemplate())) {
                $pageData    = $pageService->getDatasPage($pageId, 'saved');
            }
            
            $templateData = $pageData->getMelisTemplate();
            $pageTreeData = $pageData->getMelisPageTree();

            // select page lang
            $langLocale = $pageTreeData->lang_cms_locale;
            $langName   = $pageTreeData->lang_cms_name;
            $langId     = $pageTreeData->lang_cms_id;

            // parent ID of the selected page that will be duplicated
            $parentId   = $pageTreeData->tree_father_page_id;

            // if set to "true" then if duplication is successful,
            // it will automatically open's the duplicated page
            $openPageAfterDuplicate = true;

            $iconTypes = array(
                'SITE'   => 'fa-home',
                'FOLDER' => 'fa-folder-open-o',
                'PAGE'   => 'fa-file-o',
                'NEWSLETTER' => 'fa fa-newspaper-o',
                'NEWS_DETAIL'   => 'fa-file-o',
                'BLOG_DETAIL'   => 'fa-file-o',
            );

            $duplicatePageData = array(
                'page_duplicate_event_flag' => true,
                'page_name' => $pageTreeData->page_name . ' ' . $this->getTool()->getTranslation('tr_melis_cms_duplicate_text_identifier'),
                'page_type' => $pageTreeData->page_type,
                'plang_lang_id' => $langId,
                'page_menu' => $pageTreeData->page_menu,
                'page_tpl_id' => $pageTreeData->page_tpl_id,
                'page_taxonomy' => $pageTreeData->page_taxonomy,
                'fatherPageId'  => $pageId,
                'page_search_type' => $pageTreeData->page_search_type,
            );


            $results = $this->forward()->dispatch('MelisCms\Controller\PageProperties', array_merge(['action' => 'savePageTree'], $duplicatePageData))->getVariables();
            if(isset($results['datas']['idPage']) && ( (int) $results['datas']['idPage'])) {
                $pageId = (int) $results['datas']['idPage'];
                $duplicatePageData['page_id'] = $pageId;
                $duplicatePageData['idPage'] = $pageId;
                $results = $this->forward()->dispatch('MelisCms\Controller\PageProperties', array_merge(['action' => 'saveProperties'], $duplicatePageData))->getVariables();
            }

            if($pageId) {
                // page content
                $melisPageSavedTable = $this->getServiceManager()->get('MelisEngineTablePageSaved');
                $melisPageSavedTable->save([
                    'page_content' => $pageTreeData->page_content,
                    'page_creation_date' => date('Y-m-d H:i:s'),
                ], $pageId);
                
                // page SEO
                $pageSeoTable = $this->getServiceManager()->get('MelisEngineTablePageSeo');
                $pageSeoData  = $pageSeoTable->getEntryById($cachePageId)->current();
                if($pageSeoData) {
                    $pageSeoTable->save([
                        'pseo_id' =>  $pageId,
                        'pseo_url' => '',
                        'pseo_url_redirect' => '',
                        'pseo_url_301' => '',
                        'pseo_meta_title' => $pageSeoData->pseo_meta_title,
                        'pseo_meta_description' => $pageSeoData->pseo_meta_description
                    ]);
                }

                // response data
                $data = array(
                    'name'   => $duplicatePageData['page_name'],
                    'pageId' => $pageId,
                    'openPageAfterDuplicate' => $openPageAfterDuplicate,
                    'icon' => $iconTypes[$pageTreeData->page_type]
                );
                $success = 1;
                $message = $this->getTool()->getTranslation('tr_melis_cms_duplicate_success', [$pageTreeData->page_name]);
            }

        }

        $response = array(
            'success' => $success,
            'textTitle' => $this->getTool()->getTranslation($title),
            'textMessage' => $this->getTool()->getTranslation($message),
            'pageId'  => $pageId,
            'response' => $data,
            'errors'  => $errors
        );

        $this->getEventManager()->trigger('meliscms_page_duplicate_end', $this, $response);

        return new JsonModel($response);
    }

    private function getTool()
    {
        $tool = $this->getServiceManager()->get('MelisCoreTool');
        return $tool;
    }


}