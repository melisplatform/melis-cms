<?php

namespace MelisCms\Service;

use MelisCore\Service\MelisCoreGeneralService;
use ZendTest\XmlRpc\Server\TestAsset\Exception;

class MelisCmsSiteService extends MelisCoreGeneralService
{
    /**
     * This method will return the page of a site
     * @param Int $siteId
     * @return mixed
     */
    public function getSitePages($siteId)
    {
        $results = array();
        
        // Event parameters prepare
        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
        
        // Sending service start event
        $arrayParameters = $this->sendEvent('meliscmssite_service_get_site_pages_start', $arrayParameters);
        
        // Service implementation start
        
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        
        $site = $siteTable->getEntryById($arrayParameters['siteId'])->current();
        
        if (!empty($site))
        {
            $pages = $siteTable->getSiteSavedPagesById($site->site_id)->toArray();
            
            if (!empty($pages))
            {
                $site->pages = $pages;
            }
            
            $results = $site;
        }
        
        // Service implementation end
         
        // Adding results to parameters for events treatment if needed
        $arrayParameters['results'] = $results;
        // Sending service end event
        $arrayParameters = $this->sendEvent('meliscmssite_service_get_site_pages_start', $arrayParameters);
        
        return $arrayParameters['results'];
        
    }

    /**
     * Function to create multi lingual site
     *
     * @param $siteData
     * @param $domainData
     *          Example Format: array(
     *                                  'en_EN' => array(
     *                                       'sdom_domain' => 'english.com',
     *                                       'sdom_env'    => 'development',
     *                                       etc.....
     *                                  ),
     *                                  'fr_FR' => array(
     *                                       'sdom_domain' => 'french.com',
     *                                       'sdom_env'    => 'development',
     *                                       etc.....
     *                                  ),
     *                              )
     * @param $siteLanguages
     *          Example Format: array(
     *                                  'en_EN' => 1,
     *                                  'fr_FR' => 2,
     *                              )
     *
     * @param $site404
     * @param null $siteModuleName
     * @param bool $createModule
     * @param bool $isNewSite
     * @return mixed
     */
    public function saveSite($siteData, $domainData, $siteLanguages, $site404, $siteModuleName = null, $createModule = false, $isNewSite = false)
	{
	    // Event parameters prepare
	    $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
	     
	    // Sending service start event
	    $arrayParameters = $this->sendEvent('meliscmssite_service_save_site_start', $arrayParameters);
	     
	    // Service implementation start
	    
        // Table services
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        $siteDomainTable = $this->getServiceLocator()->get('MelisEngineTableSiteDomain');
        $site404Table = $this->getServiceLocator()->get('MelisEngineTableSite404');
        $siteHomeTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteHome');
        $sitelangsTable = $this->getServiceLocator()->get('MelisEngineTableCmsSiteLangs');

        // Site Name
        $siteName = $arrayParameters['siteData']['site_name'];
        // Site label
        $siteLabel = $arrayParameters['siteData']['site_label'];
        //module name
        $siteModuleName = $arrayParameters['siteModuleName'];

        //declare variables
        $siteDomainId = null;
        $site404Id = null;
        $hasError = false;
        $savedSiteId = null;
        $savedSiteIds = array();

        //declare default result data
        $results = array(
            'site_ids' => $savedSiteIds,
            'success' => false,
            'message' => null,
            'siteName' => $siteLabel,
        );

        /**
         * get the module path
         */
        //check if site is came from the vendor
        $moduleSrv = $this->getServiceLocator()->get('ModulesService');
        if(!empty($moduleSrv->getComposerModulePath($siteModuleName))){
            $modulePath = $moduleSrv->getComposerModulePath($siteModuleName);
        }else {
            $modulePath = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites/' . $siteModuleName;
        }

        $curPlatform = !empty(getenv('MELIS_PLATFORM'))  ? getenv('MELIS_PLATFORM') : 'development';
        $corePlatformTable = $this->getServiceLocator()->get('MelisCoreTablePlatform');
        $corePlatformData = $corePlatformTable->getEntryByField('plf_name', $curPlatform)->current();

        if($corePlatformData)
        {
            $platformId = $corePlatformData->plf_id;
            $cmsPlatformTable = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
            $cmsPlatformData = $cmsPlatformTable->getEntryById($platformId)->current();
            /**
             * Check if there is platform
             */
            if ($cmsPlatformData)
            {
                $mainPageId = (int) $cmsPlatformData->pids_page_id_current;

                $tempRes = array(
                    'success' => true
                );

                /**
                 * Check if the user want's to create a new module
                 */
                if ($arrayParameters['createModule'] && !empty($siteModuleName))
                {
                    $moduleName = $this->generateModuleNameCase($siteModuleName);
                    $tempRes = $this->createSiteModule($moduleName);
                    /**
                     * Check if the creation of module is successful
                     */
                    if($tempRes['success']) {
                        if (!is_null($siteModuleName)) {
                            // Getting the DemoSite config
                            $outputFileName = 'module.config.php';
                            $moduleConfigDir = $modulePath . '/config/' . $outputFileName;

                            // Replacing the Site homepage id to site module config
                            $moduleConfig = file_get_contents($moduleConfigDir);
                            $moduleConfig = str_replace('\'homePageId\'', $mainPageId, $moduleConfig);
                            file_put_contents($moduleConfigDir, $moduleConfig);
                        }
                    }
                }

                /**
                 * If success, then we proceed on creating the sites
                 * and its pages
                 */
                if ($tempRes['success'])
                {
                    /**
                     * Prepare the transaction so that
                     * we can rollback the db insertion if
                     * there are some error occurred
                     */
                    $db = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');//get db adapter
                    $con = $db->getDriver()->getConnection();//get db driver connection
                    $con->beginTransaction();//begin transaction
                    try {
                        // Assigning the next page id from Platform Id's
                        $arrayParameters['siteData']['site_main_page_id'] = $mainPageId;

                        if (!empty($arrayParameters['siteLanguages'])) {
                            $siteLangConfig = '';
                            $siteUrlSetting = 0;
                            if(!empty($arrayParameters['siteLanguages']['sites_url_setting'])) {
                                $siteUrlSetting = $arrayParameters['siteLanguages']['sites_url_setting'];
                                unset($arrayParameters['siteLanguages']['sites_url_setting']);
                            }

                            /**
                             * This will determine whether we are going to create
                             * a multiple site and multiple domain
                             */
                            $isMultiDomain = false;
                            if($siteUrlSetting == 2){
                               /**
                                * we create only one site and one domain
                                */
                                $isMultiDomain = true;
                            }

                            /***
                             * This will check if
                             * we are going to create a site again
                             */
                            $createSiteAndDomain = true;
                            /**
                             * This counter will help us
                             * determine whether we are going
                             * to put languages config
                             * on one or they have a lang config
                             * per site
                             */
                            $siteCtr = 0;
                            /**
                             * prepare page id initial
                             */
                            $pageIdInitial = 0;
                            /**
                             * Loop through each language
                             * to make a site per language
                             */
                            foreach ($arrayParameters['siteLanguages'] as $langLabel => $langId) {
                                /**
                                 * This will check if we are going to create a site
                                 * and domain per language
                                 * 
                                 * Ex Scenario:
                                 * 1. If the user choose to create a site with multi domain,
                                 * then we need to create a site PER LANGUAGE. So we need
                                 * to create a site for English and another site for French
                                 * for example if the user choose 2 languages.
                                 *
                                 * 2. If the user choose to create a site but not a multi domain
                                 * and have two languages. Then we only need to create one site
                                 * that has two languages.
                                 *
                                 * This condition will help us to achieve that kind of scenario.
                                 *
                                 */
                                if($createSiteAndDomain) {
                                    /**
                                     * Save site per language
                                     */
                                    $savedSiteId = $siteTable->save($arrayParameters['siteData']);
                                    /**
                                     * Process the insertion of domain by each language
                                     *
                                     * Get the domain data by language
                                     */
                                    $siteDomain = array();
                                    foreach ($arrayParameters['domainData'] as $langKey => $domain) {
                                        if ($langKey == $langLabel) {
                                            $siteDomain = $domain;
                                        }
                                    }
                                    //insert the domain
                                    if (!empty($siteDomain)) {
                                        $siteDomain['sdom_site_id'] = $savedSiteId;
                                        $siteDomainTable->save($siteDomain);
                                    }
                                    /**
                                     * increment counter
                                     */
                                    $siteCtr++;
                                    /**
                                     * clear the site lang config
                                     * to remove the previous data of the
                                     * created site
                                     */
                                    $siteLangConfig = '';
                                    /**
                                     * add saved site id to the array to return
                                     */
                                    array_push($savedSiteIds, $savedSiteId);
                                }

                                $langName = explode('_', $langLabel);
                                /**
                                 * get the current page id and template id
                                 * from the platform ids
                                 */
                                $cmsCurPlatformData = $cmsPlatformTable->getEntryById($platformId)->current();
                                $pageId = (int)$cmsCurPlatformData->pids_page_id_current;
                                $tplId = (int)$cmsCurPlatformData->pids_tpl_id_current;

                                /**
                                 * This will get the page id initial of the page
                                 */
                                if($createSiteAndDomain){
                                    $pageIdInitial = $pageId;
                                }else{
                                    if(!$pageIdInitial){
                                        $pageIdInitial = $pageId;
                                    }
                                }

                                /**
                                 * Create home page and template
                                 */
                                //Creating Site Homepage page and template
                                $this->createSitePageTemplate($tplId, $savedSiteId, $siteModuleName, $langName[1] . ': Home', 'Index', 'index', $platformId);
                                $this->createSitePage($langName[1] . ':' . $siteLabel . ' - Home', -1, $langId, 'SITE', $pageId, $tplId, $platformId, $pageIdInitial);
                                /**
                                 * This will save the homepage id
                                 * to the SiteName.config.php file
                                 */
                                $tab = '';
                                if ($siteLangConfig != '') {
                                    $tab = "\t\t\t\t";
                                }
                                //make an array of language
                                $siteLangConfig .= $tab . '\'' . $langLabel . '\'' . ' => array(' . "\n\t\t\t\t\t" .
                                    '\'' . 'homePageId' . '\'' . ' => ' . $pageId . "\n\t\t\t\t" .
                                    '),' . "\n";


                                /**
                                 * Create 404 template and page
                                 */
                                $nxtTplId = ++$tplId;
                                $this->createSitePageTemplate($nxtTplId, $savedSiteId, $siteModuleName, $langName[1] . ': 404', 'Page404', 'index', $platformId);
                                $page404Id = $pageId + 1;
                                $this->createSitePage($langName[1] . ':' . $siteLabel . ' - 404', $pageId, $langId, 'PAGE', $page404Id, $nxtTplId, $platformId);

                                /**
                                 * save the site home page language id
                                 */
                                $siteHomeData = array(
                                    'shome_site_id' => $savedSiteId,
                                    'shome_lang_id' => $langId,
                                    'shome_page_id' => $pageId
                                );
                                $siteHomeTable->save($siteHomeData);
                                /**
                                 * Save the site lang id
                                 */
                                $siteLangsData = array(
                                    'slang_site_id' =>   $savedSiteId,
                                    'slang_lang_id' => $langId,
                                );
                                $sitelangsTable->save($siteLangsData);

                                /**
                                 * save 404 data
                                 */
                                $arrayParameters['site404']['s404_page_id'] = $page404Id;
                                $arrayParameters['site404']['s404_site_id'] = $savedSiteId;
                                $site404Table->save($arrayParameters['site404']);

                                /**
                                 * check if we are going to create another site for this language
                                 */
                                if(!$isMultiDomain){
                                    $createSiteAndDomain = false;
                                }else{
                                    /**
                                     * This will create config for every site per language
                                     */
                                    if($siteCtr <= 1){
                                        $createMod = $arrayParameters['createModule'];
                                    }else{
                                        $createMod = false;
                                    }
                                    $this->updateSiteConfig($siteModuleName, $savedSiteId, $siteLangConfig, $arrayParameters['isNewSite'], $createMod);
                                }
                            }

                            if(!$isMultiDomain) {
                                /**
                                 * Update the config
                                 */
                                $this->updateSiteConfig($siteModuleName, $savedSiteId, $siteLangConfig, $arrayParameters['isNewSite'], $arrayParameters['createModule']);
                            }

                            /**
                             * If there is no error during the process,
                             * start inserting all the site data to the db
                             */
                            $con->commit();
                        } else {
                            $results['message'] = 'tr_melis_cms_sites_tool_add_create_site_no_site_language';
                            $hasError = true;
                        }
                    }catch (\Exception $ex){
                        /**
                         * if there are error, rollback
                         * the db insertion
                         */
                        $con->rollback();
                        $results['message'] = 'tr_melis_cms_sites_tool_add_create_site_unknown_error';
                        $hasError = true;
                    }
                }
                else
                {
                    // Error occured in creating the Site on MelisSites Directory
                    $results['message'] = $tempRes['message'];
                    $hasError = true;
                }
            }
            else
            {
                // if there is no Platform Id available
                $results['message'] = 'tr_meliscms_tool_site_no_platform_ids';
                $hasError = true;
            }
        }
        else
        {
            // If there is no Platform available on the database
            $results['message'] = 'tr_meliscore_error_message';
            $hasError = true;
        }
        
        
        // Checking if error occured
        if (!$hasError)
        {
            $results = array(
                'site_ids' => $savedSiteIds,
                'success' => true,
                'message' => 'tr_melis_cms_sites_tool_add_create_site_success',
                'siteName' => $siteLabel,
            );
        }
        
	    // Service implementation end
	    
	    // Adding results to parameters for events treatment if needed
	    $arrayParameters['results'] = $results;
	    // Sending service end event
	    $arrayParameters = $this->sendEvent('meliscmssite_service_save_site_end', $arrayParameters);
	     
	    return $arrayParameters['results'];
	}

    /**
     * Function to update site config
     *
     * @param $siteModuleName
     * @param $siteId
     * @param string $configData
     * @param bool $isNewSite
     * @param bool $isCreateModule
     */
	public function updateSiteConfig($siteModuleName, $siteId, $configData, $isNewSite = false, $isCreateModule = false)
    {
        /**
         * get the module path
         */
        //check if site is came from the vendor
        $moduleSrv = $this->getServiceLocator()->get('ModulesService');
        if(!empty($moduleSrv->getComposerModulePath($siteModuleName))){
            $modulePath = $moduleSrv->getComposerModulePath($siteModuleName);
        }else {
            $modulePath = $_SERVER['DOCUMENT_ROOT'] . '/../module/MelisSites/' . $siteModuleName;
        }
        /**
         * Modify the SiteName.config.php
         * to add every language in the site
         * config
         */
        $siteConfigName = $siteModuleName . '.config.php';
        $siteConfigDir = $modulePath . '/config/' . $siteConfigName;

        /**
         * Make an array for the site config per language
         */
        $siteLangArray = 'array(' . "\n\t\t\t\t" .
            $configData .
            "\t\t\t" . '),' . "\n";
        $siteLangConfig = '\'' . $siteId . '\' => '.$siteLangArray;
        /**
         * Check if it is a new site(new site module) so that we can determine
         * whether we are going to create a site config per language
         * or we're just going to update the site config
         */
        if($isNewSite) {
            /**
             * Check if site has a file created
             */
            if(file_exists($siteConfigDir)) {
                if ($isCreateModule) {
                    /**
                     * Update the site config to add
                     * the config per language
                     */
                    $moduleConfig = file_get_contents($siteConfigDir);
                    $moduleConfig = preg_replace('/(\'siteLangConfig\'\s*,)/im', $siteLangConfig, $moduleConfig);
                    file_put_contents($siteConfigDir, $moduleConfig);
                } else {
                    /**
                     * Update the SiteName.config.php
                     * to include the new site language config
                     *
                     * This will include the new site lang config above the
                     * allSites config array
                     */
                    $moduleConfig = preg_replace('/(\'allSites(?![\s\S]*\'allSites[\s\S]*$))/im', "$siteLangConfig\t\t\t$1", file_get_contents($siteConfigDir));
                    file_put_contents($siteConfigDir, $moduleConfig);
                }
            }
        }else{
            /**
             * Update the SiteName.config.php
             * to include the new site language config
             *
             * This will include the new site lang config above the
             * allSites config array
             */
            $moduleConfig = preg_replace('/(\'allSites(?![\s\S]*\'allSites[\s\S]*$))/im', "$siteLangConfig\t\t\t$1", file_get_contents($siteConfigDir));
            file_put_contents($siteConfigDir, $moduleConfig);
        }
    }

//    public function saveSite($site, $siteDomain, $site404, $siteLangId = null, $siteId = null, $genSiteModule = false, $siteModule = null)
//    {
//        $results = array(
//            'site_id' => null,
//            'success' => false,
//            'message' => null,
//        );
//
//        // Event parameters prepare
//        $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
//
//        // Sending service start event
//        $arrayParameters = $this->sendEvent('meliscmssite_service_save_site_start', $arrayParameters);
//
//        // Service implementation start
//
//        // Table services
//        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
//        $siteDomainTable = $this->getServiceLocator()->get('MelisEngineTableSiteDomain');
//        $site404Table = $this->getServiceLocator()->get('MelisEngineTableSite404');
//
//        // Site Name
//        $siteName = $arrayParameters['site']['site_name'];
//        # Site label
//        $siteLabel = $arrayParameters['site']['site_label'];
//
//        $siteDomainId = null;
//        $site404Id = null;
//
//        $hasError = false;
//        if (!is_null($arrayParameters['siteId']))
//        {
//            // Saving Site
//            $savedSiteId = $siteTable->save($arrayParameters['site'], $arrayParameters['siteId']);
//
//            // Retreiving the Site domain
//            $domainData = $siteDomainTable->getDataBySiteIdAndEnv($arrayParameters['siteId'], $siteDomain['sdom_env'])->current();
//
//            if (!empty($domainData))
//            {
//                // If exist, the action would be updating the existing data
//                $siteDomainId = $domainData->sdom_id;
//            }
//
//            // Retreiving the Site 404 page
//            $s404Data = $site404Table->getEntryByField('s404_site_id', $arrayParameters['siteId'])->current();
//
//            if (!empty($s404Data))
//            {
//                // If exist, the action would be updating the existing data
//                $site404Id = $s404Data->s404_id;
//            }
//        }
//        else
//        {
//
//            $curPlatform = !empty(getenv('MELIS_PLATFORM'))  ? getenv('MELIS_PLATFORM') : 'development';
//            $corePlatformTable = $this->getServiceLocator()->get('MelisCoreTablePlatform');
//            $corePlatformData = $corePlatformTable->getEntryByField('plf_name', $curPlatform)->current();
//
//            if($corePlatformData)
//            {
//                $platformId = $corePlatformData->plf_id;
//                $cmsPlatformTable = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
//                $cmsPlatformData = $cmsPlatformTable->getEntryById($platformId)->current();
//
//                if ($cmsPlatformData)
//                {
//                    $tempRes = array(
//                        'success' => true
//                    );
//
//                    $siteModuleName = null;
//
//                    if ($arrayParameters['genSiteModule'])
//                    {
//                        $siteModuleName = $this->generateModuleNameCase($arrayParameters['siteModule']);
//
//                        $tempRes = $this->createSiteModule($siteModuleName);
//                    }
//
//                    if ($tempRes['success'])
//                    {
//                        $pageId = (int) $cmsPlatformData->pids_page_id_current;
//                        $tplId = (int) $cmsPlatformData->pids_tpl_id_current;
//
//                        // Assigning the next page id from Platform Id's to Site main page id
//                        $arrayParameters['site']['site_main_page_id'] = $pageId;
//
//                        // Saving Site
//                        $savedSiteId = $siteTable->save($arrayParameters['site']);
//
//                        // Creating Site Homepage template
//                        $templateId = $this->createSitePageTemplate($tplId, $savedSiteId, $siteModuleName, $siteLabel.' Home', 'Index', 'index', $platformId);
//
//                        // Creating Site homepage
//                        $this->createSitePage($siteLabel, -1, $siteLangId, 'SITE', $pageId, $tplId, $platformId);
//
//                        if (!is_null($siteModuleName))
//                        {
//                            // Getting the DemoSite config
//                            $melisSite = $_SERVER['DOCUMENT_ROOT'].'/../module/MelisSites';
//                            $outputFileName = 'module.config.php';
//                            $moduleConfigDir = $melisSite.'/'.$siteModuleName.'/config/'.$outputFileName;
//
//                            // Replacing the Site homepage id to site module sonfig
//                            $moduleConfig = file_get_contents($moduleConfigDir);
//                            $moduleConfig = str_replace('\'homePageId\'', $pageId, $moduleConfig);
//                            file_put_contents($moduleConfigDir, $moduleConfig);
//                        }
//
//                        // Creating Site 40 page template
//                        $nxtTplId = ++$tplId;
//                        $templateId = $this->createSitePageTemplate($nxtTplId, $savedSiteId, $siteModuleName, $siteLabel.' 404', 'Page404', 'index', $platformId);
//
//                        // Creating Site 404 page
//                        $page404Id = $pageId + 1;
//                        $this->createSitePage($siteLabel.'-404', $pageId, $siteLangId, 'PAGE', $page404Id, $nxtTplId, $platformId);
//
//                        $site404['s404_page_id'] = $page404Id;
//                    }
//                    else
//                    {
//                        // Error occured in creating the Site on MelisSites Directory
//                        $results['message'] = $tempRes['message'];
//                        $hasError = true;
//                    }
//                }
//                else
//                {
//                    // if there is no Platform Id available
//                    $results['message'] = 'tr_meliscms_tool_site_no_platform_ids';
//                    $hasError = true;
//                }
//            }
//            else
//            {
//                // If there is no Platform available on the database
//                $results['message'] = 'tr_meliscore_error_message';
//                $hasError = true;
//            }
//        }
//
//
//        // Checking if error occured
//        if (!$hasError)
//        {
//            // Saving Site domain
//            $siteDomain['sdom_site_id'] = $savedSiteId;
//            $siteDomainTable->save($siteDomain, $siteDomainId);
//
//            // Saving Site 404 page
//            if (!empty($site404['s404_page_id']))
//            {
//                $site404['s404_site_id'] = $savedSiteId;
//                $site404Table->save($site404, $site404Id);
//            }
//            elseif (!is_null($site404Id))
//            {
//                $site404Table->deleteById($site404Id);
//            }
//
//            $results = array(
//                'site_id' => $savedSiteId,
//                'success' => true,
//                'message' => 'tr_meliscms_tool_site_save_success',
//            );
//        }
//
//        // Service implementation end
//
//        // Adding results to parameters for events treatment if needed
//        $arrayParameters['results'] = $results;
//        // Sending service end event
//        $arrayParameters = $this->sendEvent('meliscmssite_service_save_site_end', $arrayParameters);
//
//        return $arrayParameters['results'];
//    }
	
	/**
	 * This method creating Site page
	 * 
	 * @param String $siteName
	 * @param Int $fatherId
	 * @param Int $siteLangId
	 * @param String $pageType
	 * @param Int $pageId
	 * @param Int $templateId
	 * @param Int $platformId
	 * @param null $pageIdInitial
	 * @return Int
	 */
	private function createSitePage($siteName, $fatherId, $siteLangId, $pageType, $pageId, $templateId, $platformId, $pageIdInitial = null)
	{
	    // Event parameters prepare
	    $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
	    
	    // Sending service start event
	    $arrayParameters = $this->sendEvent('meliscmssite_service_save_site_page_start', $arrayParameters);
	    
	    // Service implementation start
	   
	    $pageTreeTable = $this->getServiceLocator()->get('MelisEngineTablePageTree');
	    $pageLangTable = $this->getServiceLocator()->get('MelisEngineTablePageLang');
	    $pageSavedTable = $this->getServiceLocator()->get('MelisEngineTablePageSaved');
	    $cmsPlatformTable = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
	    
	    /**
	     * Retrieving the Current Page tree
	     * with Father Id of -1 "root node of the page tree"
	     * to get the Order of the new entry
	     */
	    $treePageOrder = $pageTreeTable->getTotalData('tree_father_page_id', $fatherId);
	    // Saving Site page on Page tree
	    $pageTreeTable->save(array(
	        'tree_page_id' => $arrayParameters['pageId'],
	        'tree_father_page_id' => $fatherId,
	        'tree_page_order' => $treePageOrder + 1,
	    ));
	     
	    // Saving Site page Language
	    $pageLangTable->save(array(
	        'plang_page_id' => $arrayParameters['pageId'],
	        'plang_lang_id' => $arrayParameters['siteLangId'],
	        'plang_page_id_initial' => (!empty($arrayParameters['pageIdInitial']) ? $arrayParameters['pageIdInitial'] : $arrayParameters['pageId'] ),
	    ));
	    
	    // Saving Site page in Save Version as new page entry
	    $pageSavedTable->save(array(
	        'page_id' => $arrayParameters['pageId'],
	        'page_type' => $arrayParameters['pageType'],
	        'page_status' => 1,
	        'page_menu' => 'LINK',
	        'page_name' => $arrayParameters['siteName'],
	        'page_tpl_id' => $arrayParameters['templateId'],
	        'page_content' => '<?xml version="1.0" encoding="UTF-8"?><document type="MelisCMS" author="MelisTechnology" version="2.0"></document>',
	        'page_taxonomy' => '',
	        'page_creation_date' => date('Y-m-d H:i:s')
	    ));
	    
	    // Updating platform ids after site pages creation
	    $platform = array(
           'pids_page_id_current' => ++$arrayParameters['pageId']
        );
	    $cmsPlatformTable->save($platform, $arrayParameters['platformId']);
	    
	    // Service implementation end
	    
	    // Adding results to parameters for events treatment if needed
	    $arrayParameters['results'] = $arrayParameters['pageId'];
	    // Sending service end event
	    $arrayParameters = $this->sendEvent('meliscmssite_service_save_site_page_end', $arrayParameters);
	    
	    return $arrayParameters['results'];
	}
	
	/**
	 * This method creating Site page template
	 * 
	 * @param Int $tplId
	 * @param Int $siteId
	 * @param String $siteName
	 * @param String $tempName
	 * @param String $controler
	 * @param String $action
	 * @param Int $platformId
	 * @return Int
	 */
	private function createSitePageTemplate($tplId, $siteId, $siteName, $tempName, $controler, $action, $platformId)
	{
	    $cmsTemplateTbl = $this->getServiceLocator()->get('MelisEngineTableTemplate');
	    $cmsPlatformTable = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
	    
	    // Template data
	    $template = array(
	        'tpl_id' => $tplId,
	        'tpl_site_id' => $siteId,
	        'tpl_name' => $tempName,
	        'tpl_type' => 'ZF2',
	        'tpl_zf2_website_folder' => $siteName,
	        'tpl_zf2_layout' => 'defaultLayout',
	        'tpl_zf2_controller' => $controler,
	        'tpl_zf2_action' => $action,
	        'tpl_php_path' => '',
	        'tpl_creation_date' => date('Y-m-d H:i:s'),
	    );
	    
	    // Saving template
	    $templateId = $cmsTemplateTbl->save($template);
	    
	    // Updating platform ids after site pages creation
	    $platform = array(
	        'pids_tpl_id_current' => ++$tplId
	    );
	    $cmsPlatformTable->save($platform, $platformId);
	    
	    return $templateId;
	}
	
	/**
	 * Creating the Site module on /module/MelisSites directory
	 * 
	 * @param String $siteName, the name of the result Site module
	 * @return Array
	 */
	private function createSiteModule($siteName)
	{
	    $results = array(
	        'success' => false,
	        'message' => null,
	    );
	    $melisSitesDir = $_SERVER['DOCUMENT_ROOT'].'/../module/MelisSites';
	    
	    if (is_dir($melisSitesDir))
	    {
	        if (is_writable($melisSitesDir))
	        {
	            $moduleSvc = $this->getServiceLocator()->get('ModulesService');
	            
	            $melisCmsDir = $moduleSvc->getModulePath('MelisCms');
	            
	            $siteSampleDir = $melisCmsDir.'/etc/SiteSample';
	            
	            if (is_dir($siteSampleDir))
	            {
	                if (!is_dir($melisSitesDir.'/'.$siteName))
	                {
	                    $res = $this->xcopy($siteSampleDir, $melisSitesDir.'/'.$siteName, 0777);
	                    
	                    if ($res)
	                    {
	                        // replace file contents using the weptoption value as target module name with the new Module Name
	                        $this->mapDirectory($melisSitesDir.'/'.$siteName, 'SiteSample', $siteName);
	                        
	                        $results['success'] = true;
	                    }
	                }
	                else
	                {
	                    // The Site directory target is already exist
	                    $results['message'] = 'tr_meliscms_tool_site_directory_exist';
	                }
	            }
	        }
	        else
	        {
	            // MelisSites dir not writable
	            $results['message'] = 'tr_meliscms_tool_site_directory_not_writable';
	        }
	    }
	    else
	    {
	        // MelisSites directory not exist
	        $results['message'] = 'tr_meliscms_tool_site_melissites_directory_not_exist';
	    }
	    
	    return $results;
	    
	}
	
	/**
	 * Copy a file, or recursively copy a folder and its contents
	 * @author      Aidan Lister <aidan@php.net>
	 * @version     1.0.1
	 * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
	 * @param       string   $source    Source path
	 * @param       string   $dest      Destination path
	 * @param       int      $permissions New folder creation permissions
	 * @return      bool     Returns true on success, false on failure
	 */
	private function xcopy($source, $dest, $permissions = self::CHMOD_775)
	{
	    // Check for symlinks
	    if (is_link($source)) 
	    {
	        return symlink(readlink($source), $dest);
	    }
	    
	    // Simple copy for a file
	    if (is_file($source)) 
	    {
	        return copy($source, $dest);
	    }
	    
	    // Make destination directory
	    if (!is_dir($dest)) 
	    {
	        mkdir($dest, $permissions);
	    }
	    
	    // Loop through the folder
	    $dir = dir($source);
	    while (false !== $entry = $dir->read()) 
	    {
	        // Skip pointers
	        if ($entry == '.' || $entry == '..') 
	        {
	            continue;
	        }
	        // Deep copy directories
	        $this->xcopy("$source/$entry", "$dest/$entry", $permissions);
	    }
	    
	    // Clean up
	    $dir->close();
	    return true;
	}
	
	/**
	 * This method will map a directory to change some specific word
	 * that match the target and replace by new word
	 * 
	 * @param String $dir
	 * @param String $targetModuleName
	 * @param String $newModuleName
	 * @return Array
	 */
	private function mapDirectory($dir, $targetModuleName, $newModuleName) 
	{
	    $result = array();
	    $cdir = scandir($dir);
	    
	    $fileName = '';
	    foreach ($cdir as $key => $value) 
	    {
	        if (!in_array($value,array(".",".."))) 
	        {
	            if (is_dir($dir . '/' . $value)) 
	            {
	
	                if ($value == $targetModuleName) 
	                {
	                    rename($dir . '/' . $value, $dir . '/' . $newModuleName);
	                    $value = $newModuleName;
	                }
	                elseif ($value == $this->moduleNameToViewName($targetModuleName)) 
	                {
	                    $newModuleNameSnakeCase = $this->moduleNameToViewName($newModuleName);
	                    rename($dir . '/' . $value, $dir . '/' . $newModuleNameSnakeCase);
	                    
	                    $value = $newModuleNameSnakeCase;
	                }
	                
	                $result[$dir . '/' .$value] = $this->mapDirectory($dir . '/' . $value, $targetModuleName, $newModuleName);
	            }
	            else 
	            {
	                $newFileName = str_replace($targetModuleName, $newModuleName, $value);
	                if ($value != $newFileName) 
	                {
	                    rename($dir . '/' . $value, $dir . '/' . $newFileName);
	                    $value = $newFileName;
	                }
	                $result[$dir . '/' .$value] = $value;
	                $fileName = $dir . '/' .$value;
	                $this->replaceFileTextContent($fileName, $fileName, $targetModuleName, $newModuleName);
	            }
	        }
	    }
	    
	    return $result;
	}
	
	/**
	 * This will modified a string to valid zf2 module name
	 * @param string $str
	 * @return string
	 */
	private function generateModuleNameCase($str) {
	    $i = array("-","_");
	    $str = preg_replace('/([a-z])([A-Z])/', "$1 $2", $str);
	    $str = str_replace($i, ' ', $str);
	    $str = str_replace(' ', '', ucwords(strtolower($str)));
	    $str = strtolower(substr($str,0,1)).substr($str,1);
	    $str = ucfirst($str);
	    return $str;
	}
	
	/**
	 * This will modified a string to valid zf2 view name
	 * 
	 * @param String $string
	 * @return string
	 */
	private function moduleNameToViewName($string) 
	{
	    return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
	}
	
	/**
	 * This method is replacing a single string match on file content
	 * and store/save after replacing
	 * 
	 * @param String $fileName
	 * @param String $outputFileName
	 * @param String $lookupText
	 * @param String $replaceText
	 */
	private function replaceFileTextContent($fileName, $outputFileName, $lookupText, $replaceText)
	{
	    $file = @file_get_contents($fileName);
	    $file = str_replace($lookupText, $replaceText, $file);
	    @file_put_contents($outputFileName, $file);
	}
}