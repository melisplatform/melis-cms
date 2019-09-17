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

        if ($idPage) {
            $pageData = $pageSvc->getDatasPage($idPage)->getMelisPageTree();

            if (! empty($pageData->page_id)) {
                $pageData = $pageSvc->getDatasPage($idPage, 'saved')->getMelisPageTree();
            }

            if ($pageData->page_type === self::SITE || $pageData->page_type === self::NEW_SITE) {

            } else {
                $idPage = $this->getRootPageId($serviceManager, $idPage);
            }

            $idPage = $this->getCorrespondingSiteId($serviceManager, $idPage);
            $styles = $styleTable->getEntryByField('style_site_id', $idPage);
        }

		$valueoptions = array();

        if ($styles) {
            $max = $styles->count();
            for ($i = 0; $i < $max; $i++)
            {
                $style = $styles->current();
                if(true === (bool) $style->style_status) {
                    $valueoptions[] = [
                        'label' => $style->style_name,
                        'value' => $style->style_id,
                        'attributes' => [
                            'data-link' => $style->style_path,
                        ]
                    ];
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

    private function getRootPageId(ServiceLocatorInterface $sl, $pageId)
    {
        $treeService = $sl->get('MelisEngineTree');
        $page = $treeService->getPageFather($pageId)->current();

        if (! empty($page)) {
            if ($page->page_type == self::SITE || $page->page_type == self::NEW_SITE) {
                $rootPageId = $page->page_id;
            } else {
                $rootPageId = $this->getRootPageId($sl, $page->page_id);
            }
        }

        return $rootPageId ?? null;
    }
}