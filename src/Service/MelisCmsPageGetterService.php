<?php

namespace MelisCms\Service;

use MelisCore\Service\MelisCoreGeneralService;

class MelisCmsPageGetterService extends MelisCoreGeneralService
{
    private $cachekey = 'cms_page_getter_';
    private $cacheConfig = 'meliscms_page';
    
    /**
     * This method will retieve Page content from cache file
     * 
     * @return Strin||null
     */
    public function getPageContent($pageId)
    {
        // Retrieve cache version if front mode to avoid multiple calls
        $melisEngineCacheSystem = $this->getServiceLocator()->get('MelisEngineCacheSystem');
        $pageContent = $melisEngineCacheSystem->getCacheByKey($this->cachekey.$pageId, $this->cacheConfig, true);
        
        return $pageContent;
    }
}