<?php

namespace MelisCms\Service;

use MelisCore\Service\MelisCoreGeneralService;

class MelisCmsPageService extends MelisCoreGeneralService
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
            $pageTreeTbl = $this->getServiceLocator()->get('MelisEngineTablePageTree');
            
            if (is_null($arrayParameters['pageId']))
            {
                $corePlatformTbl = $this->getServiceLocator()->get('MelisCoreTablePlatform');
                $corePlatform = $corePlatformTbl->getEntryByField('plf_name', getenv('MELIS_PLATFORM'))->current();
                $corePlatformId = $corePlatform->plf_id;
                
                $cmsPlatformIdsTbl = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
                $pagePlatformIds = $cmsPlatformIdsTbl->getEntryById($corePlatformId)->current();
                
                $pageCurrentId = $pagePlatformIds->pids_page_id_current;
                
                $pageTreeData = $arrayParameters['pageTree'];
                
                if (!empty($pageTreeData['tree_father_page_id']))
                {
                    $pageParentPages = $pageTreeTbl->getEntryByField('tree_father_page_id', $pageTreeData['tree_father_page_id'])->count();
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
    
    public function savePagePublished($page, $pageId = null)
    {
        $results = null;
        
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        
        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_published_start', $arrayParameters);
        
        // Service implementation start
        
        $pageData = $arrayParameters['page'];
        
        $pagePublishedTbl = $this->getServiceLocator()->get('MelisEngineTablePagePublished');
        
        if (!is_null($arrayParameters['pageId']))
        {
            $pageData['page_edit_date'] = date('Y-m-d H:i:s');
        }
        else
        {
            $pageData['page_creation_date'] = date('Y-m-d H:i:s');
        }
        
        // Get Cureent User ID
        $melisCoreAuth = $this->getServiceLocator()->get('MelisCoreAuth');
        $userAuthDatas =  $melisCoreAuth->getStorage()->read();
        $userId = !empty($userAuthDatas) ? (int) $userAuthDatas->usr_id : null;
        
        $pageData['page_last_user_id'] = $userId;
        
        try 
        {
            $results = $pagePublishedTbl->save($pageData, $arrayParameters['pageId']);
        }
        catch (\Exception $e){}
        
        // Service implementation end
        
        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $results;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_published_end', $arrayParameters);
        
        return $arrayParameters['results'];
    }
    
    public function savePageSaved($page, $pageId = null)
    {
        $results = null;
        
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        
        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_saved_start', $arrayParameters);
        
        // Service implementation start
        
        $pageData = $arrayParameters['page'];
        
        $pageSavedTbl = $this->getServiceLocator()->get('MelisEngineTablePageSaved');
        
        if (!empty($arrayParameters['pageId']))
        {
            // Get Cureent User ID
            $melisCoreAuth = $this->getServiceLocator()->get('MelisCoreAuth');
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
        catch (\Exception $e){}
        
        // Service implementation end
        
        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $results;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_saved_end', $arrayParameters);
        
        return $arrayParameters['results'];
    }
    
    public function savePageSeo($pageSeo, $pageSeoId = null)
    {
        $results = null;
        
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        
        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_seo_start', $arrayParameters);
        
        // Service implementation start
        
        $pageSeoTbl = $this->getServiceLocator()->get('MelisEngineTablePageSeo');
        
        try
        {
            $results = $pageSeoTbl->save($arrayParameters['pageSeo'], $arrayParameters['pageSeoId']);
        }
        catch (\Exception $e){}
        
        // Service implementation end
        
        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $results;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_seo_end', $arrayParameters);
        
        return $arrayParameters['results'];
    }
    
    public function savePageLang($pageLang, $pageId = null)
    {
        $results = null;
        
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        
        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_lang_start', $arrayParameters);
        
        // Service implementation start
        
        $pageLangTbl = $this->getServiceLocator()->get('MelisEngineTablePageLang');
        
        try 
        {
            $results = $pageLangTbl->savePageLang($arrayParameters['pageLang'], $arrayParameters['pageId']);
        }
        catch(\Exception $e){}
        
        // Service implementation end
        
        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $results;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_lang_end', $arrayParameters);
        
        return $arrayParameters['results'];
    }
    
    public function savePageStyle($pageStyle, $pageId = null)
    {
        $results = null;
        
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        
        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_style_start', $arrayParameters);
        
        // Service implementation start
        
        $pageStyleTbl = $this->getServiceLocator()->get('MelisEngineTablePageStyle');
        
        try 
        {
            $results = $pageStyleTbl->savePageStyle($arrayParameters['pageStyle'], $arrayParameters['pageId']);
        }
        catch (\Exception $e){}
        
        // Service implementation end
        
        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $results;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmspage_service_save_page_style_end', $arrayParameters);
        
        return $arrayParameters['results'];
    }
}