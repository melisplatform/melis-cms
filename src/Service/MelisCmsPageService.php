<?php

namespace MelisCms\Service;


use MelisCore\Service\MelisGeneralService;

class MelisCmsPageService extends MelisGeneralService
{
    public function savePage($pageTree, $pagePublished = array(), $pageSaved = array(), $pageSeo = array(), $pageLang = array(), $pageStyle = array(), $pageId = null)
    {
        $results = null;
        
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        
        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_start', $arrayParameters);
        
        // Service implementation start
        
        $pageId = null;
        
        try
        {
            $pageTreeTbl = $this->getServiceManager()->get('MelisEngineTablePageTree');
            
            if (is_null($arrayParameters['pageId']))
            {
                $corePlatformTbl = $this->getServiceManager()->get('MelisCoreTablePlatform');
                $corePlatform = $corePlatformTbl->getEntryByField('plf_name', getenv('MELIS_PLATFORM'))->current();
                $corePlatformId = $corePlatform->plf_id;
                
                $cmsPlatformIdsTbl = $this->getServiceManager()->get('MelisEngineTablePlatformIds');
                $pagePlatformIds = $cmsPlatformIdsTbl->getEntryById($corePlatformId)->current();
                
                $pageCurrentId = $pagePlatformIds->pids_page_id_current;
                
                $pageTreeData = $arrayParameters['pageTree'];
                
                if (!empty($pageTreeData['tree_father_page_id']))
                {
                    $pageParentPages = $pageTreeTbl->getEntryByField('tree_father_page_id', $pageTreeData['tree_father_page_id'])->count() + 1;
                    $pageTreeData['tree_page_order'] = ($pageParentPages) ? $pageParentPages : 1;
                }
                else 
                {
                    $pageTreeData['tree_father_page_id'] = -1;
                    $pageTreeData['tree_page_order'] = 1;
                }
                
                $pageTreeData['tree_page_id'] = $pageCurrentId;
                
                $pageTreeTbl->save($pageTreeData);
                
                $pageId = $pageCurrentId;
                
                $cmsPlatformIdsTbl->save(array('pids_page_id_current' => ++$pageCurrentId), $corePlatformId);
            }
            else 
            {
                $pageTreeTbl->save($arrayParameters['pageTree'], $arrayParameters['pageId']);
                $pageId = $arrayParameters['pageId'];
            }
        }
        catch(\Exception $e){}
        
        if (!is_null($pageId))
        {
            if (!empty($arrayParameters['pagePublished']))
            {
                if (is_null($arrayParameters['pageId']))
                {
                    $arrayParameters['pagePublished']['page_id'] = $pageId;
                    $this->savePagePublished($arrayParameters['pagePublished']);
                }
                else
                {
                    $this->savePagePublished($arrayParameters['pagePublished'], $pageId);
                }
            }
            
            if (!empty($arrayParameters['pageSaved']))
            {
                if (is_null($arrayParameters['pageId']))
                {
                    $arrayParameters['pageSaved']['page_id'] = $pageId;
                    $this->savePageSaved($arrayParameters['pageSaved']);
                }
                else
                {
                    $this->savePageSaved($arrayParameters['pageSaved'], $pageId);
                }
            }
            
            if (!empty($arrayParameters['pageSeo']))
            {
                if (is_null($arrayParameters['pageId']))
                {
                    $arrayParameters['pageSeo']['pseo_id'] = $pageId;
                    $this->savePageSeo($arrayParameters['pageSeo']);
                }
                else
                {
                    $this->savePageSeo($arrayParameters['pageSeo'], $pageId);
                }
            }
            
            if (!empty($arrayParameters['pageLang']))
            {
                
                if (is_null($arrayParameters['pageId']))
                {
                    $arrayParameters['pageLang']['plang_page_id'] = $pageId;
                    $this->savePageLang($arrayParameters['pageLang']);
                }
                else
                {
                    $this->savePageLang($arrayParameters['pageLang'], $pageId);
                }
            }
            
            if (!empty($arrayParameters['pageStyle']))
            {
                if (is_null($arrayParameters['pageId']))
                {
                    $arrayParameters['pageStyle']['pstyle_page_id'] = $pageId;
                    $this->savePageStyle($arrayParameters['pageStyle']);
                }
                else
                {
                    $this->savePageStyle($arrayParameters['pageStyle'], $pageId);
                }
            }
        }
        
        $results = $pageId;
        
        // Service implementation end
        
        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $results;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_end', $arrayParameters);
        
        return $arrayParameters['results'];
    }

    /**
     * @param $page
     * @param null $pageId
     * @return mixed
     * @throws \Exception
     */
    public function savePagePublished($page, $pageId = null)
    {
        $results = null;
        
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        
        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_published_start', $arrayParameters);
        
        // Service implementation start
        
        $pageData = $arrayParameters['page'];
        
        $pagePublishedTbl = $this->getServiceManager()->get('MelisEngineTablePagePublished');
        
        if (!is_null($arrayParameters['pageId']))
        {
            $pageData['page_edit_date'] = date('Y-m-d H:i:s');
        }
        else
        {
            $pageData['page_creation_date'] = date('Y-m-d H:i:s');
        }
        
        // Get Cureent User ID
        $melisCoreAuth = $this->getServiceManager()->get('MelisCoreAuth');
        $userAuthDatas =  $melisCoreAuth->getStorage()->read();
        $userId = !empty($userAuthDatas) ? (int) $userAuthDatas->usr_id : null;
        
        $pageData['page_last_user_id'] = $userId;

        try
        {
            $results = $pagePublishedTbl->save($pageData, $arrayParameters['pageId']);
        }
        catch (\Exception $e){
            throw new \Exception('[page published] ' . $e->getMessage());
        }
        
        // Service implementation end
        
        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $results;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_published_end', $arrayParameters);
        
        return $arrayParameters['results'];
    }

    /**
     * @param $page
     * @param null $pageId
     * @return mixed
     * @throws \Exception
     */
    public function savePageSaved($page, $pageId = null)
    {
        $results = null;
        
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        
        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_saved_start', $arrayParameters);
        
        // Service implementation start
        
        $pageData = $arrayParameters['page'];
        
        $pageSavedTbl = $this->getServiceManager()->get('MelisEngineTablePageSaved');
        
        if (!empty($arrayParameters['pageId']))
        {
            // Get Cureent User ID
            $melisCoreAuth = $this->getServiceManager()->get('MelisCoreAuth');
            $userAuthDatas =  $melisCoreAuth->getStorage()->read();
            $userId = !empty($userAuthDatas) ? (int) $userAuthDatas->usr_id : null;
            
            $pageData['page_edit_date'] = date('Y-m-d H:i:s');
            $pageData['page_last_user_id'] = $userId;
        }
        else
        {
            $pageData['page_creation_date'] = date('Y-m-d H:i:s');
        }
        
        try
        {
            $results = $pageSavedTbl->save($pageData, $arrayParameters['pageId']);
        }
        catch (\Exception $e){
            throw new \Exception('[page saved] ' . $e->getMessage());
        }
        
        // Service implementation end
        
        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $results;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_saved_end', $arrayParameters);
        
        return $arrayParameters['results'];
    }

    /**
     * @param $pageSeo
     * @param null $pageSeoId
     * @return mixed
     * @throws \Exception
     */
    public function savePageSeo($pageSeo, $pageSeoId = null)
    {
        $results = null;
        
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        
        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_seo_start', $arrayParameters);
        
        // Service implementation start
        
        $pageSeoTbl = $this->getServiceManager()->get('MelisEngineTablePageSeo');
        
        try
        {
            $results = $pageSeoTbl->save($arrayParameters['pageSeo'], $arrayParameters['pageSeoId']);
        }
        catch (\Exception $e){
            throw new \Exception('[page seo] ' . $e->getMessage());
        }
        
        // Service implementation end
        
        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $results;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_seo_end', $arrayParameters);
        
        return $arrayParameters['results'];
    }

    /**
     * @param $pageLang
     * @param null $pageId
     * @return mixed
     * @throws \Exception
     */
    public function savePageLang($pageLang, $pageId = null)
    {
        $results = null;
        
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        
        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_lang_start', $arrayParameters);
        
        // Service implementation start
        
        $pageLangTbl = $this->getServiceManager()->get('MelisEngineTablePageLang');
        
        try 
        {
            $results = $pageLangTbl->savePageLang($arrayParameters['pageLang'], $arrayParameters['pageId']);
        }
        catch(\Exception $e){
            throw new \Exception('[page lang] ' . $e->getMessage());
        }
        
        // Service implementation end
        
        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $results;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_lang_end', $arrayParameters);
        
        return $arrayParameters['results'];
    }

    /**
     * @param $pageStyle
     * @param null $pageId
     * @return mixed
     * @throws \Exception
     */
    public function savePageStyle($pageStyle, $pageId = null)
    {
        $results = null;
        
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        
        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_style_start', $arrayParameters);
        
        // Service implementation start
        
        $pageStyleTbl = $this->getServiceManager()->get('MelisEngineTablePageStyle');
        
        try 
        {
            $results = $pageStyleTbl->savePageStyle($arrayParameters['pageStyle'], $arrayParameters['pageId']);
        }
        catch (\Exception $e){
            throw new \Exception('[page style] ' . $e->getMessage());
        }
        
        // Service implementation end
        
        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $results;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_style_end', $arrayParameters);
        
        return $arrayParameters['results'];
    }
    public function duplicatePage($pageId, $parentId = null, $langIdPageName = null,$pageRelation = null)
    {

        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('melis_cms_duplicate_page_start', $arrayParameters);
        $result = null;

        //Start
        $pageId      = (int) $pageId;
        $cachePageId = $pageId;
        $pageService = $this->getServiceManager()->get('MelisEnginePage');
        $pageData    = $pageService->getDatasPage($pageId);

        $tablePageLang = $this->getServiceManager()->get('MelisEngineTablePageLang');
        $cmsLang       = $tablePageLang->getEntryByField('plang_page_id', $pageId)->current();
        $initialPageId = $cmsLang->plang_page_id_initial;

        $tableLang  = $this->getServiceManager()->get('MelisEngineTableCmsLang');
        $langData   = $tableLang->getEntryById($langIdPageName)->current();
        $langPrefix = !empty($langData)? strtolower(substr($langData->lang_cms_locale, 0, 2)) : '';

        $hasEmptyPublished = empty($pageData->getMelisPageTree()->page_id) ? true : false;

        if($hasEmptyPublished) {
            $pageData = $pageService->getDatasPage($pageId, 'saved');
        }

        $templateData = $pageData->getMelisTemplate();
        $pageTreeData = $pageData->getMelisPageTree();

        // select page lang
        $langLocale = $pageTreeData->lang_cms_locale;
        $langName   = $pageTreeData->lang_cms_name;
        $langIdPage = $pageTreeData->lang_cms_id;


        if(!$parentId) {
            // parent ID of the selected page that will be duplicated
            $parentId   = $pageTreeData->tree_father_page_id;
        }


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
            'page_name' => $pageTreeData->page_name . ' ' . $this->getTool()->getTranslation('tr_melis_cms_duplicate_text_identifier') . " (" . $langPrefix . ")" ,
            'page_type' => $pageTreeData->page_type,
            'plang_lang_id' => $langIdPage,
            'page_menu' => $pageTreeData->page_menu,
            'page_tpl_id' => $pageTreeData->page_tpl_id,
            'page_taxonomy' => $pageTreeData->page_taxonomy,
            'fatherPageId'  => $parentId,
        );


        $results = $this->savePageTree(null, $parentId, $duplicatePageData,$initialPageId, $pageRelation,$langIdPageName);


        if(isset($results['datas']['idPage']) && ( (int) $results['datas']['idPage'])) {
            $pageId = (int) $results['datas']['idPage'];
            $duplicatePageData['page_id'] = $pageId;
            $duplicatePageData['idPage'] = $pageId;
            $results = $this->saveProperties($pageId, $duplicatePageData);
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
                'icon' => $iconTypes[$pageTreeData->page_type] ?? 'fa-file-o'
            );
            $success = 1;
            $message = $this->getTool()->getTranslation('tr_melis_cms_duplicate_success', [$pageTreeData->page_name]);
        }


        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $pageId;
        // Sending service end event
        $arrayParameters = $this->sendEvent('melis_cms_duplicate_page_end', $arrayParameters);

        return $arrayParameters['results'];
    }

    public function savePageTree($pageId, $parentId, $data,$pageIdInitial,$pageRelation = null,$langInitialId = null)
    {

        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscms_page_savetree_start', $arrayParameters);

        $idPage       = $pageId;
        $fatherPageId = $parentId;
        $translator = $this->getServiceManager()->get('translator');

        $eventDatas = array('idPage' => $idPage, 'fatherPageId' => $fatherPageId);

        $melisEngineTablePageTree = $this->getServiceManager()->get('MelisEngineTablePageTree');

        // Check if the page really exist to avoid ghost datas
        $exist = false;
        if (!empty($idPage))
        {
            $datasPageTree = $melisEngineTablePageTree->getEntryById($idPage);
            if ($datasPageTree)
            {
                $datasPageTree = $datasPageTree->toArray();
                if (count($datasPageTree) > 0)
                    $exist = true;
            }
        }


        /**
         * Get the form properly loaded
         * The page cannot be saved in page tree if at least the properties
         * are not filled correctly.
         */
        $errors = array();
        $propertyForm = $this->getPropertyPageForm($idPage, !$exist);


        $postValues = $data;

        $propertyForm->setData($postValues);

        if ($exist)
        {
            // Set Page lang id not required
            $propertyForm->getInputFilter()->get('plang_lang_id')->setRequired(false);
        }

        /**
         * If page does not exist, let's create
         * If page exist, nothing to do, properties are saved in save-page-properties, handled through event
         */
        if (!$exist)
        {
            // Get the order to be inserted
            $order = 0;
            $melisTree = $this->getServiceManager()->get('MelisEngineTree');
            $children = $melisTree->getPageChildren($fatherPageId);
            if ($children)
            {
                $order = count($children) + 1;
            }

            // Get the current page id from platform table
            $melisModuleName = getenv('MELIS_PLATFORM');
            $melisEngineTablePlatformIds = $this->getServiceManager()->get('MelisEngineTablePlatformIds');
            $datasPlatformIds = $melisEngineTablePlatformIds->getPlatformIdsByPlatformName($melisModuleName);
            $datasPlatformIds = $datasPlatformIds->current();

            if (!empty($datasPlatformIds))
            {

                /**
                 * Check if we reached the end of the band page ids defined in platform
                 */
                if ($datasPlatformIds->pids_page_id_current >= $datasPlatformIds->pids_page_id_end)
                {
                    return new JsonModel(array(
                        'success' => 0,
                        'datas' => array(),
                        'errors' => array(array('platform_current_page_id_max' => $translator->translate('tr_meliscms_page_save_error_Current page id has reached end of platform band')))
                    ));
                }

                // Try/catch to save in case of pagetree id error
                try
                {
                    // Save the new entry in page_tree
                    $melisEngineTablePageTree->save(array(
                        'tree_page_id' => $datasPlatformIds->pids_page_id_current,
                        'tree_father_page_id' => $fatherPageId,
                        'tree_page_order' => $order,
                    ));
                    $idPage = $datasPlatformIds->pids_page_id_current;
                }
                catch (\Exception $e)
                {
                    $results = array(
                        'success' => 0,
                        'datas' => array(),
                        'errors' => array(array('platform_current_page_id_used' => $translator->translate('tr_meliscms_page_save_error_Current page id defined in platform is already used')))
                    );
                }

                // save page lang relation
                $langId = 1;		// default
                $initial = 0;		// to be changed when functionality create page version lang done, this will set to plang_page_id_initial

                // Get langId from posted values or put default 1

                if (!empty($postValues['plang_lang_id']))
                    $langId = $postValues['plang_lang_id'];

                $melisEngineTablePageLang = $this->getServiceManager()->get('MelisEngineTablePageLang');

                /*
                 * If you want to duplicate with  page relationship
                 */

                if($pageRelation)
                {
                    $melisEngineTablePageLang->save(array(
                        'plang_page_id' => $datasPlatformIds->pids_page_id_current,
                        'plang_lang_id' => $langInitialId,
                        'plang_page_id_initial' => $pageIdInitial,//$initial,
                    ));
                }
                else{
                    /*
                     * If you want to duplicate with no page relationship
                     */
                    $melisEngineTablePageLang->save(array(
                        'plang_page_id' => $datasPlatformIds->pids_page_id_current,
                        'plang_lang_id' => $langInitialId,
                        'plang_page_id_initial' => $datasPlatformIds->pids_page_id_current,//$initial,
                    ));
                }



                // Update current page id in platform
                $melisEngineTablePlatformIds->save(array(
                    'pids_page_id_current' => $datasPlatformIds->pids_page_id_current + 1,
                ), $datasPlatformIds->pids_id);
            }
        }

        $result = array(
            'success' => 1,
            'datas' => array(
                'idPage' => $idPage,
                'isNew' => !$exist,
            ),
            'errors' => array()
        );

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $result;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscms_page_savetree_end', $arrayParameters);

        return $arrayParameters['results'];
    }
    public function saveProperties($pageId, $data, $isNew = null)
    {
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscms_page_save_page_properties', $arrayParameters);

        $idPage = $pageId;

        $translator = $this->getServiceManager()->get('translator');

        $eventDatas = array('idPage' => $idPage, 'isNew' => $isNew);
        //$this->getEventManager()->trigger('meliscms_page_saveproperties_start', null, $eventDatas);

        // Get the form properly loaded
        $propertyForm = $this->getPropertyPageForm($idPage, $isNew);

        $melisPageSavedTable = $this->getServiceManager()->get('MelisEngineTablePageSaved');
        $melisPagePublishedTable = $this->getServiceManager()->get('MelisEngineTablePagePublished');
        $melisTool               = $this->getServiceManager()->get('MelisCoreTool');

        // Get the current page id from platform table
        $melisModuleName = getenv('MELIS_PLATFORM');
        $melisEngineTablePlatformIds = $this->getServiceManager()->get('MelisEngineTablePlatformIds');
        $datasPlatformIds = $melisEngineTablePlatformIds->getPlatformIdsByPlatformName($melisModuleName);
        $datasPlatformIds = $datasPlatformIds->current();

        if (!empty($datasPlatformIds))
        {

            // Get datas validated
            $datas = $data;
            unset($datas['page_duplicate_event_flag']);
            unset($datas['fatherPageId']);
            unset($datas['idPage']);
            /**
             * First, let's copy the published table entry
             * inside the saved table if there's no entry in it.
             * Kind of special case, the page is not new, it's being edited after being published
             */
            if (!$isNew)
            {
                $dataSaved = $melisPageSavedTable->getEntryById($idPage);
                $dataSaved = $dataSaved->toArray();
                if (count($dataSaved) == 0)
                {
                    $dataPublished = $melisPagePublishedTable->getEntryById($idPage);
                    $dataPublished = $dataPublished->toArray();
                    if (count($dataPublished) > 0)
                    {
                        $dataPublished = $dataPublished[0];
                        $melisPageSavedTable->save($dataPublished, $idPage);
                    }
                }
            }
            else
            {
                // New page, create an empty content
                $newXmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                $newXmlContent .= '<document type="MelisCMS" author="MelisTechnology" version="2.0">' . "\n";
                $newXmlContent .= '</document>';
                $datas['page_content'] = $newXmlContent;
            }


            /**
             * Datas are all saved in PageSave once validated.
             * This allow to add fields in the app.form and fields in the db.
             * It will then works with the new field as long as it's in the same table.
             *
             * Useless fields from the form must be unset
             * Special fields must be set
             */


            $errors = array();
            $success = 0;
            $language = $datas['plang_lang_id'];
            $template = $datas['page_tpl_id'];

            unset($datas['page_submit']);
            unset($datas['page_creation_date']);
            unset($datas['plang_lang_id']);
            unset($datas['style_id']);

            $datas['page_id'] = $idPage;

            if(empty($template)) {
                $errors = array(
                    'page_tpl_id' => array(
                        'errorMessage' => $translator->translate('tr_meliscms_page_form_page_tpl_id_invalid'),
                        'label' => $translator->translate('tr_meliscms_page_tab_properties_form_Template'),
                    ),
                );
            }

            if ($isNew)
            {
                $datas['page_creation_date'] = date('Y-m-d H:i:s');

                if(empty($language)) {
                    $errors = array(
                        'plang_lang_id' => array(
                            'errorMessage' => $translator->translate('tr_meliscms_page_form_page_p_lang_id_invalid'),
                            'label' => $translator->translate('tr_meliscms_page_tab_properties_form_Language'),
                        ),
                    );
                }
            }
            else
            {
                $datas['page_edit_date'] = date('Y-m-d H:i:s');

                $melisCoreAuth = $this->getServiceManager()->get('MelisCoreAuth');
                $user = $melisCoreAuth->getIdentity();
                if (!empty($user))
                    $datas['page_last_user_id'] = $user->usr_id;
            }

            if(empty($errors))	{
                $success = 1;
                $res = $melisPageSavedTable->save($datas, $idPage);
            }


            $result = array(
                'success' => $success,
                'errors' => array($errors),
            );
        }
        else
        {
            $errors = array(
                'platform_ids' => array(
                    'noPlatformIds' => $translator->translate('tr_meliscms_no_available_platform_ids'),
                    'label' => $translator->translate('tr_meliscms_tool_platform_ids'),
                ),
            );

            $result = array(
                'success' => 0,
                'errors' => array($errors),
            );
        }

        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $result;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscms_page_saveproperties_end', $arrayParameters);

        return $arrayParameters['results'];
    }

    public function getPropertyPageForm($pageId, $isNew = null)
    {
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());

        // Sending service start event
        $arrayParameters = $this->sendEvent('get_property_page_form_start', $arrayParameters);

        $idPage = $pageId;
        $pathAppConfigForm = '/meliscms/forms/meliscms_page_properties';

        /**
         * Get the config for this form
         */
        $melisMelisCoreConfig = $this->getServiceManager()->get('MelisCoreConfig');

        $appConfigForm = $melisMelisCoreConfig->getFormMergedAndOrdered(
            $pathAppConfigForm,
            'meliscms_page_properties',
            $idPage . '_'
        );

        if ($isNew == false)
        {
            // Lang not changeable after creation
            $appConfigForm = $melisMelisCoreConfig->setFormFieldDisabled($appConfigForm, 'plang_lang_id', true);
            $appConfigForm = $melisMelisCoreConfig->setFormFieldRequired($appConfigForm, 'plang_lang_id', false);
        }

        /**
         * Generate the form through factory and change ElementManager to
         * have access to our custom Melis Elements
         */
        $factory = new \Laminas\Form\Factory();
        $formElements = $this->getServiceManager()->get('FormElementManager');
        $factory->setFormElementManager($formElements);
        $propertyForm = $factory->createForm($appConfigForm);


        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $propertyForm;
        // Sending service end event
        $arrayParameters = $this->sendEvent('get_property_page_form_end', $arrayParameters);

        return $arrayParameters['results'];
    }

    private function getTool()
    {
        $tool = $this->getServiceManager()->get('MelisCoreTool');
        return $tool;
    }


}
