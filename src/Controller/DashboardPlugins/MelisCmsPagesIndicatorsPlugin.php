<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Controller\DashboardPlugins;

use MelisCore\Controller\DashboardPlugins\MelisCoreDashboardTemplatingPlugin;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container;


class MelisCmsPagesIndicatorsPlugin extends MelisCoreDashboardTemplatingPlugin
{
    public function __construct()
    {
        $this->pluginModule = 'meliscms';
        parent::__construct();
    }
    
    public function pageIndicators()
    {
        // Get the current language
        $container = new Container('meliscore');
        $locale = $container['melis-lang-locale'];

        // Checks wether the user has access to this tools or not
        /** @var \MelisCore\Service\MelisCoreDashboardPluginsRightsService $dashboardPluginsService */
        $dashboardPluginsService = $this->getServiceLocator()->get('MelisCoreDashboardPluginsService');
        //get the class name to make it as a key
        $path = explode('\\', __CLASS__);
        $className = array_pop($path);

        $isAccessible = $dashboardPluginsService->canAccess($className);
        
        // Variable Initializations
        $numSite = 0;
        $numPages = 0;
        $numPublished = 0;
        $numUnpublished = 0;
        // Pages ID handler that exist on Page Saved table
        $savePagesId = array();
        
        $pageSavedTable = $this->getServiceLocator()->get('MelisEngineTablePageSaved');
        $melisEngineTablePagePublished = $this->getServiceLocator()->get('MelisEngineTablePagePublished');
        
        $currentPagesSaved = $pageSavedTable->fetchAll();
        if (!empty($currentPagesSaved))
        {
            $pages = $currentPagesSaved->toArray();
            
            if (!empty($pages))
            {
                for ($i = 0 ; $i < count($pages) ; $i++)
                {
                    // Add Page Id to Pages saved array handler
                    array_push($savePagesId, $pages[$i]['page_id']);
                    
                    //Check Page Type
                    if ($pages[$i]['page_type'] == 'SITE')
                    {
                        //Increament Number of Site
                        $numSite++;
                    }
                    
                    if ($pages[$i]['page_type'] == 'PAGE')
                    {
                        // Increament Number of Pages
                        $numPages++;
                        // Checking if Page Id Exist on publish table
                        $pagePublished = $melisEngineTablePagePublished->getEntryById($pages[$i]['page_id']);
                        if (!empty($pagePublished))
                        {
                            $pagesPub = $pagePublished->current();
                            if (empty($pagesPub))
                            {
                                // New Created Pages counted as unpublished pages
                                $numUnpublished++;
                            }
                        }
                    }
                }
            }
        }
        
        // Checking Page Current Status
        $currentPages = $melisEngineTablePagePublished->fetchAll();
        if (!empty($currentPages))
        {
            $pages = $currentPages->toArray();
            
            if (!empty($pages))
            {
                for ($i = 0 ; $i < count($pages) ; $i++)
                {
                    //Check Page Type
                    if ($pages[$i]['page_type'] == 'SITE')
                    {
                        // Check if Page ID is existing from Saved Array
                        // if this Page ID exist on array this Page ID already counted to return
                        if (!in_array($pages[$i]['page_id'], $savePagesId))
                        {
                            //Increament Number of Site
                            $numSite++;
                        }
                    }
                    
                    if ($pages[$i]['page_type'] == 'PAGE')
                    {
                        // Check if Page ID is existing from Saved Array
                        // if this Page ID exist on array this Page ID already counted to return
                        if (!in_array($pages[$i]['page_id'], $savePagesId))
                        {
                            // Increament Number of Pages
                            $numPages++;
                        }
                        
                        //Check Page Status
                        // 1 = Published
                        // 2 = Unblished
                        if ($pages[$i]['page_status'] == 1)
                        {
                            //Increament Number of Published
                            $numPublished++;
                        }
                        elseif ($pages[$i]['page_status'] == 0)
                        {
                            //Increament Number of Unpublished
                            $numUnpublished++;
                        }
                    }
                }
            }
        }
        
        
        $view = new ViewModel();
        $view->setTemplate('melis-cms/dashboard/page-indicators');
        // Assigning data array to view
        $view->data = array(
            'locale' => $locale,
            'numSite' => $numSite,
            'numPages' => $numPages,
            'numPublished' => $numPublished,
            'numUnpublished' => $numUnpublished
        );
        $view->pages = $pages;
        $view->toolIsAccessible = $isAccessible;

        return $view;
    }
}