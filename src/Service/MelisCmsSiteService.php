<?php

namespace MelisCms\Service;

use MelisCore\Service\MelisCoreGeneralService;

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
    
    public function saveSite($site, $siteDomain, $site404, $siteLangId = null, $siteId = null, $genSiteModule = false, $siteModule = null)
	{
	    $results = array(
	        'site_id' => null,
	        'success' => false,
	        'message' => null,
	    );
	    
	    // Event parameters prepare
	    $arrayParameters = $this->makeArrayFromParameters(__METHOD__, func_get_args());
	     
	    // Sending service start event
	    $arrayParameters = $this->sendEvent('meliscmssite_service_save_site_start', $arrayParameters);
	     
	    // Service implementation start
	    
        // Table services
        $siteTable = $this->getServiceLocator()->get('MelisEngineTableSite');
        $siteDomainTable = $this->getServiceLocator()->get('MelisEngineTableSiteDomain');
        $site404Table = $this->getServiceLocator()->get('MelisEngineTableSite404');
        
        // Site Name
        $siteName = $arrayParameters['site']['site_name'];
        
        $siteDomainId = null;
        $site404Id = null;
        
        $hasError = false;
        if (!is_null($arrayParameters['siteId']))
        {
            // Saving Site
            $savedSiteId = $siteTable->save($arrayParameters['site'], $arrayParameters['siteId']);
            
            // Retreiving the Site domain
            $domainData = $siteDomainTable->getDataBySiteIdAndEnv($arrayParameters['siteId'], $siteDomain['sdom_env'])->current();
            
            if (!empty($domainData))
            {
                // If exist, the action would be updating the existing data
                $siteDomainId = $domainData->sdom_id;
            }
            
            // Retreiving the Site 404 page
            $s404Data = $site404Table->getEntryByField('s404_site_id', $arrayParameters['siteId'])->current();
            
            if (!empty($s404Data))
            {
                // If exist, the action would be updating the existing data
                $site404Id = $s404Data->s404_id;
            }
        }
        else 
        {
            
            $curPlatform = !empty(getenv('MELIS_PLATFORM'))  ? getenv('MELIS_PLATFORM') : 'development';
            $corePlatformTable = $this->getServiceLocator()->get('MelisCoreTablePlatform');
            $corePlatformData = $corePlatformTable->getEntryByField('plf_name', $curPlatform)->current();
             
            if($corePlatformData)
            {
                $platformId = $corePlatformData->plf_id;
                $cmsPlatformTable = $this->getServiceLocator()->get('MelisEngineTablePlatformIds');
                $cmsPlatformData = $cmsPlatformTable->getEntryById($platformId)->current();
                
                if ($cmsPlatformData)
                {
                    $tempRes = array(
                        'success' => true
                    );
                    
                    $siteModuleName = null;
                    
                    if ($arrayParameters['genSiteModule'])
                    {
                        $siteModuleName = $this->generateModuleNameCase($arrayParameters['siteModule']);
                        
                        $tempRes = $this->createSiteModule($siteModuleName);
                    }
                    
                    if ($tempRes['success'])
                    {
                        $pageId = (int) $cmsPlatformData->pids_page_id_current;
                        $tplId = (int) $cmsPlatformData->pids_tpl_id_current;
                         
                        // Assigning the next page id from Platform Id's to Site main page id
                        $arrayParameters['site']['site_main_page_id'] = $pageId;
                        
                        // Saving Site
                        $savedSiteId = $siteTable->save($arrayParameters['site']);
                        
                        // Creating Site Homepage template
                        $templateId = $this->createSitePageTemplate($tplId, $savedSiteId, $siteModuleName, $siteName.' Home', 'Index', 'index', $platformId);
                        
                        // Creating Site homepage
                        $this->createSitePage($siteName, -1, $siteLangId, 'SITE', $pageId, $tplId, $platformId);
                        
                        if (!is_null($siteModuleName))
                        {
                            // Getting the DemoSite config
                            $melisSite = $_SERVER['DOCUMENT_ROOT'].'/../module/MelisSites';
                            $outputFileName = 'module.config.php';
                            $moduleConfigDir = $melisSite.'/'.$siteModuleName.'/config/'.$outputFileName;
                            
                            // Replacing the Site homepage id to site module sonfig
                            $moduleConfig = file_get_contents($moduleConfigDir);
                            $moduleConfig = str_replace('\'homePageId\'', $pageId, $moduleConfig);
                            file_put_contents($moduleConfigDir, $moduleConfig);
                        }
                        
                        // Creating Site 40 page template
                        $nxtTplId = ++$tplId;
                        $templateId = $this->createSitePageTemplate($nxtTplId, $savedSiteId, $siteModuleName, $siteName.' 404', 'Page404', 'index', $platformId);
                        
                        // Creating Site 404 page
                        $page404Id = $pageId + 1;
                        $this->createSitePage($siteName.'-404', $pageId, $siteLangId, 'PAGE', $page404Id, $nxtTplId, $platformId);
                         
                        $site404['s404_page_id'] = $page404Id;
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
        }
        
        
        // Checking if error occured
        if (!$hasError)
        {
            // Saving Site domain
            $siteDomain['sdom_site_id'] = $savedSiteId;
            $siteDomainTable->save($siteDomain, $siteDomainId);
            
            // Saving Site 404 page
            if (!empty($site404['s404_page_id']))
            {
                $site404['s404_site_id'] = $savedSiteId;
                $site404Table->save($site404, $site404Id);
            }
            elseif (!is_null($site404Id)) 
            {
                $site404Table->deleteById($site404Id);
            }
            
            $results = array(
                'site_id' => $savedSiteId,
                'success' => true,
                'message' => 'tr_meliscms_tool_site_save_success',
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
	 * This method creating Site page
	 * 
	 * @param String $siteName
	 * @param Int $fatherId
	 * @param Int $siteLangId
	 * @param String $pageType
	 * @param Int $pageId
	 * @param Int $templateId
	 * @param Int $platformId
	 * @return Int
	 */
	private function createSitePage($siteName, $fatherId, $siteLangId, $pageType, $pageId, $templateId, $platformId)
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
	        'plang_page_id_initial' => $arrayParameters['pageId']
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