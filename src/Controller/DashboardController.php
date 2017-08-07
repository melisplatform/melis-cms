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
use Zend\Session\Container;

/**
 * Dashboard controller for MelisCMS
 * 
 * Used to render dashboard components in MelisPlatform Back Office
 *
 */
class DashboardController extends AbstractActionController
{
    /**
     * Dashboard Page indicators
     */
    public function renderDashboardPagesIndicatorsAction(){
    
        $melisKey = $this->params()->fromRoute('melisKey', '');
    
        // Get the current language
        $container = new Container('meliscore');
        $locale = $container['melis-lang-locale'];
    
        // Variable Initializations
        $numSite = 0;
        $numPages = 0;
        $numPublished = 0;
        $numUnpublished = 0;
        // Pages ID handler that exist on Page Saved table
        $savePagesId = array();
    
        $pageSavedTable = $this->serviceLocator->get('MelisEngineTablePageSaved');
        $melisEngineTablePagePublished = $this->serviceLocator->get('MelisEngineTablePagePublished');
    
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
        $view->melisKey = $melisKey;
        // Assigning data array to view
        $view->data = array(
            'locale' => $locale,
            'numSite' => $numSite,
            'numPages' => $numPages,
            'numPublished' => $numPublished,
            'numUnpublished' => $numUnpublished
        );
        $view->pages = $pages;
    
        return $view;
    }
	
}
