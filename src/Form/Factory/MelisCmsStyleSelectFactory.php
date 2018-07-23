<?php

/**
 * Melis Technology (http://www.melistechnology.com)
 *
 * @copyright Copyright (c) 2016 Melis Technology (http://www.melistechnology.com)
 *
 */

namespace MelisCms\Form\Factory;

use Zend\ServiceManager\ServiceLocatorInterface;
use MelisCore\Form\Factory\MelisSelectFactory;
/**
 * Cms Platfrom Ids select factory
 * This will return available platform the have not added to Cms Platform IDs
 */
class MelisCmsStyleSelectFactory extends MelisSelectFactory
{
    const SITE = 'SITE';
    const NEW_SITE = '';

    /**
     * @param ServiceLocatorInterface $formElementManager
     * @return array
     */
	protected function loadValueOptions(ServiceLocatorInterface $formElementManager)
	{
        /**
         * @var \Zend\ServiceManager\ServiceLocatorInterface $serviceManager
         */
		$serviceManager = $formElementManager->getServiceLocator();
		$request = $serviceManager->get('Request');
		$idPage = (int) $request->getQuery('idPage', $request->getPost('idPage', ''));
        $styles = [];

        /**
         * @var \MelisEngine\Model\Tables\MelisCmsStyleTable $styleTable
         */
        $styleTable = $serviceManager->get('MelisEngineTableStyle');
        /**
         * @var \MelisEngine\Service\MelisTreeService $pageTree
         */
        $pageTree = $serviceManager->get('MelisEngineTree');
        /**
         * @var \MelisEngine\Service\MelisPageService $pageSvc
         */
        $pageSvc  = $serviceManager->get('MelisEnginePage');

        $pageData = $pageSvc->getDatasPage($idPage)->getMelisPageTree();
        if (!$pageData) {
            $pageData = $pageSvc->getDatasPage($idPage, 'saved')->getMelisPageTree();
        }

        if ($pageData->page_type == self::SITE || $pageData->page_type == self::NEW_SITE) {
            $idPage = $this->getCorrespondingSiteId($serviceManager, $idPage);
            $styles = $styleTable->getEntryByField('style_site_id', $idPage);
        } else {
            $parentId = $pageTree->getPageFather($idPage)->current();
            if (isset($parentId->tree_father_page_id)) {
                $parentId = $this->getCorrespondingSiteId($serviceManager, $parentId->tree_father_page_id);
                $styles = $styleTable->getEntryByField('style_site_id', $parentId);
            } else {
                // try searching in the saved version
                $parentId = $pageTree->getPageFather($idPage, 'saved')->current();
                if (isset($parentId->tree_father_page_id)) {
                    $parentId = $this->getCorrespondingSiteId($serviceManager, $parentId->tree_father_page_id);
                    $styles = $styleTable->getEntryByField('style_site_id', $parentId);
                }
            }
        }
        
		$valueoptions = array();

        if ($styles) {
            $max = $styles->count();
            for ($i = 0; $i < $max; $i++)
            {
                $style = $styles->current();
                if(true === (bool) $style->style_status) {
                    $valueoptions[] = array(
                        'label' => $style->style_name,
                        'value' => $style->style_id,
                        'attributes' => array(
                            'data-link' => $style->style_path,
                        )
                    );
                }
                $styles->next();
            }
        }

		return $valueoptions; 
	}

    /**
     * @param ServiceLocatorInterface $sl
     * @param int                     $pageId
     * @return int
     */
	private function getCorrespondingSiteId(ServiceLocatorInterface $sl, $pageId) : int
    {
        /**
         * @var \MelisEngine\Model\Tables\MelisSiteTable $siteTable
         */
        $siteTable = $sl->get('MelisEngineTableSite');
        $site = $siteTable->getEntryByField('site_main_page_id', (int) $pageId)->current();
        if ($site) {
            return $site->site_id;
        }

        return 0;
    }

}