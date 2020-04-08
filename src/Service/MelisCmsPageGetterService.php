<?php

namespace MelisCms\Service;


use MelisCore\Service\MelisGeneralService;

class MelisCmsPageGetterService extends MelisGeneralService
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
        $melisEngineCacheSystem = $this->getServiceManager()->get('MelisEngineCacheSystem');
        $pageContent = $melisEngineCacheSystem->getCacheByKey($this->cachekey.$pageId, $this->cacheConfig, true);
        
        return $pageContent;
    }
}